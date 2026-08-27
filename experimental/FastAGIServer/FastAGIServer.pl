#!/usr/bin/env perl
#
# FastAGIServer.pl
#
# Runs Vicidial AGI Modules as a FastAGI server
#
# Copyright (C) 2026  Matt Florell, Mike Cargile <vicidial@gmail.com>	LICENSE: AGPLv2
#
# ; VICIDIAL_auto_dialer transfer script Load Balanced:
# exten => 8380,1,AGI(agi://127.0.0.1:4577/call_log)
# exten => 8380,n,Set(AGIARGS=NORMAL,LB,${CONNECTEDLINE(name)})
# exten => 8380,n,AGI(agi://127.0.0.1:4573/VDAD_ALL_outbound)
# exten => 8380,n,Hangup()
#
# changes:
# 260826-1814 - Initial Build
#

use strict;
use warnings;

# tell perl where our modules are
use FindBin;
use lib "$FindBin::Bin";

# load standard modules
use base qw(Net::Server::PreFork);
use Asterisk::AGI;
use JSON::PP;
use DBI;
use Time::HiRes ('gettimeofday');
use POSIX qw(strftime);
use Cwd qw(abs_path);
use Getopt::Long qw(GetOptions);

BEGIN { $0 = abs_path($0); }

my $nofork = 0;
my $loglevel = 3;
my $logfile = '/var/log/astguiclient/FastAGI-server.log';

# Bind options to variables
GetOptions(
	'nofork'  => \$nofork,
	'loglevel=i'  => \$loglevel,
	'help|h|?' => \&show_help,
) or die "Error in command line arguments\n";

my $agc_config_path = '/etc/astguiclient.conf';

# load /etc/astguiclient.conf
my %agc_config = parse_config( $agc_config_path );

# connect to vicidial DB
my $db_connect_str = "DBI:mysql:$agc_config{'VARDB_database'}:$agc_config{'VARDB_server'}:$agc_config{'VARDB_port'}";
my $dbh = DBI->connect(
	$db_connect_str, 
	$agc_config{'VARDB_user'}, 
	$agc_config{'VARDB_pass'}
) or die "Couldn't connect to database: " . DBI->errstr;

# get the FAST_AGI_SERVER_CONFIG settings container
my $stmt = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = 'FAST_AGI_SERVER_CONFIG';";
my $sth = $dbh->prepare($stmt) or die "preparing: ",$dbh->errstr;
$sth->execute or die "executing: $stmt ", $dbh->errstr;

if ( $sth->rows == 0 ) { die "FAST_AGI_SERVER_CONFIG Settings Container Not Found!!"; }

my @fagi_settiongs_ary = $sth->fetchrow_array;
my $fagi_config_json = $fagi_settiongs_ary[0];

$sth->finish();

# log to the internal_process_log
$stmt = "INSERT INTO vicidial_internal_log SET db_time=NOW(), up_time=NOW(), action='launched', stage='Run seconds: 0', process='FastAGIServer.pl', server_ip='$agc_config{'VARserver_ip'}';";
$sth = $dbh->prepare($stmt) or die "preparing: ",$dbh->errstr;
$sth->execute or die "executing: $stmt ", $dbh->errstr;

$sth->finish();
$dbh->disconnect();

my $fagi_config = decode_json($fagi_config_json);


if ( $loglevel > 4 ) {
	$loglevel = 4;
}
if ( $loglevel < 0 ) {
	$loglevel = 0;
}
	

# Set the server options
my $server_opts = $fagi_config->{server} || {};
$server_opts->{host}		//= '0.0.0.0';
$server_opts->{port}		//= 4573;
$server_opts->{min_servers}	//= 5;
$server_opts->{max_servers}	//= 20;
$server_opts->{max_requests}	//= 1000;
$server_opts->{log_level}       = $loglevel;

if ( $nofork == 1 ) {
	print( "No Fork active.\n");
	$server_opts->{background}	= 0;
	$server_opts->{log_file}	= undef;
} else {
	$server_opts->{background}      = 1;
	$server_opts->{log_file}        //= $logfile;	
}


my $server = __PACKAGE__->new(%$server_opts);

$server->{agc_config} = \%agc_config;
$server->{fagi_config} = $fagi_config;

my ($epoch, $usec) = gettimeofday();

$server->{conf_epoch} = $epoch;
$server->{conf_usec} = $usec;

$server->run();

sub show_help {
    print << "EOF";
Usage: $0 [options]

Options:
  --help       Show this help message and exit.
  --nofork     Do not fork a background process and show all log data in the CLI.
  --loglevel   On a scale of 0 to 4 how verbose do you want the logs. (Defaults to 3)

Examples:
  $0 --nofork --loglevel 4
  $0 --help
  $0
EOF
    exit(0); # Exit cleanly since help was explicitly requested
}


# Overriding the default log writer hook cleanly
sub write_to_log_hook {
	my ($self, $level, $msg) = @_;
	
	# 1. Strip trailing newlines from the incoming message
	$msg =~ s/\s+$//;
	
	# 2. Build your custom timestamp format
	my ($log_epoch,$log_usec) = gettimeofday();
	my $log_msec = $log_usec / 1000;
	my $timestamp = strftime("%Y-%m-%d %H:%M:%S", localtime($log_epoch)) . sprintf(".%03d", $log_msec);
	
	# 3. Create your custom log format string
	my $formatted_line = sprintf("[%s] [PID: %d] [LVL: %s] %s\n", $timestamp, $$, $level, $msg);
		
	# 4. Write it out to the configured file handle
	if (my $fp = $self->{server}->{log_fp}) {
		print $fp $formatted_line;
	} else {
		print STDERR $formatted_line;
	}
}

sub idle_loop_hook {
	my ($self, $readable_fh_ref) = @_;

	$self->SUPER::idle_loop_hook($readable_fh_ref);

	my ($idle_epoch,$idle_usec) = gettimeofday();

	if ($idle_epoch - 30 > $self->{conf_epoch} )  {
		my $old_fagi_config = $server->{fagi_config};

		# connect to vicidial DB
		my $db_connect_str = "DBI:mysql:$self->{agc_config}->{'VARDB_database'}:$self->{agc_config}->{'VARDB_server'}:$self->{agc_config}->{'VARDB_port'}";
		my $dbh = DBI->connect(
		        $db_connect_str,
		        $self->{agc_config}->{'VARDB_user'}, 
		        $self->{agc_config}->{'VARDB_pass'}
		) or die "Couldn't connect to database: " . DBI->errstr;

		# get the FAST_AGI_SERVER_CONFIG settings container
		my $stmt = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = 'FAST_AGI_SERVER_CONFIG';";
		my $sth = $dbh->prepare($stmt) or die "preparing: ",$dbh->errstr;
		$sth->execute or die "executing: $stmt ", $dbh->errstr;

		if ( $sth->rows == 0 ) { die "FAST_AGI_SERVER_CONFIG Settings Container Not Found!!"; }

		my @fagi_settiongs_ary = $sth->fetchrow_array;
		my $fagi_config_json = $fagi_settiongs_ary[0];

		$sth->finish();
		$dbh->disconnect();

		my $fagi_config = eval { decode_json($fagi_config_json); };

		if ($@) {
			$self->log(3, "FAST_AGI_SERVER_CONFIG settings container contains invalid JSON. Keeping old config");
		} else {
			my ($epoch, $usec) = gettimeofday();
			$self->{conf_epoch} = $epoch;
			$self->{conf_usec} = $usec;

			# Settings are good. update the server
			my $server_opts = $fagi_config->{server} || {};

			if ($self->_config_has_changed($self->{fagi_config}->{server}, $server_opts)) {
				$self->{fagi_config} = $fagi_config;

				$self->log(3, "Server config has changed. Reloading." );
				for my $key ( keys $server_opts->%* ) {
					# skip keys that cannot be changed
					next if $key =~ /^(port|host|proto|user|group|chroot|background)$/;

					$self->log( 3, "\t" . $key . "=" . $server_opts->{$key} );
					$self->{server}->{$key} = $server_opts->{$key};
				}


				$self->{server}->{count_servers} = 1;
			}
		}		
	}
}

sub _config_has_changed {
	my ($self, $old_hash_ref, $new_hash_ref) = @_;
	# Check if any keys in the new hash are different or completely missing from old
	for my $key ( keys $new_hash_ref->%* ) {
		if (!exists $old_hash_ref->{$key} || $old_hash_ref->{$key} ne $new_hash_ref->{$key}) {
			return 1; # Change found!
		}
	}
	# Check if any old keys were deleted in the new database update
	for my $key ( keys $old_hash_ref->%* ) {
		if (!exists $new_hash_ref->{$key}) {
			return 1; # Change found!
		}
	}
	return 0; # The two hash references are identical
}

# Net::Server Initialization Function Hook
sub post_configure_hook {
	my $self = shift;
	
	# Save the route mapping globally inside the runtime server object
	$self->{_agi_routes} = $fagi_config->{routes} || {};
	
	$self->log(3, "Loading and verifying routing configurations...");
	
	# Dynamically load the configured call handler modules
	foreach my $path (keys %{ $self->{_agi_routes} }) {
		my $package = $self->{_agi_routes}->{$path};
		
		my $file = $package;
		$file =~ s/::/\//g;
		$file .= ".pm";
		
		$self->log(3, "Dynamically loading module: $package for route $path");
		
		eval { require $file; };
		if ($@) {
			die "Fatal error loading module $package for route $path: $@\n";
		}
	}
}

# Request Handler
sub process_request {
	my $self = shift;
	
	my $AGI = Asterisk::AGI->new(
		stdin  => \*STDIN,
		stdout => \*STDOUT
	);

	$AGI->{debug} =  0;

	my %agc_conf = parse_config( $agc_config_path );
	
	# Parse and extract ALL environment variables into a hash
	my %input = $AGI->ReadParse();

	my $request_uri = $input{'request'} || '';
	my $path = '';
	if ($request_uri =~ m{agi://[^/]+(/[^?#\s]*)}) {
		$path = $1;
	}

	my $handler_class = $self->{_agi_routes}->{$path};
	
	if ($handler_class) {
	my $handler_conf = $fagi_config->{handler_settings}->{$handler_class} || {};

		$self->log(3, "Routing $path to handler $handler_class");
		eval {
			my $worker = $handler_class->new($self, $AGI, $handler_conf, \%agc_conf, \%input );
			$worker->run();
		};
		if ($@) {
			$self->log(1, "Runtime crash in handler $handler_class: $@");
			$AGI->verbose("AGI internal server execution error", 1);
			$AGI->hangup();
		}
	}
	else {
		$self->log(2, "No configuration route found for path: $path");
		$AGI->verbose("Unknown route specified: $path", 3);
		$AGI->hangup();
	}
}

# Config Parser
sub parse_config {
	my ($filepath) = @_;
	my %config;

	open(my $fh, '<', $filepath) or die "Cannot open $filepath: $!";

	while (my $line = <$fh>) {
		chomp($line);

		$line =~ s/#.*//;		# Remove comments
		$line =~ s/^\s+|\s+$//g;	# Trim whitespace
		next if $line eq '';		# Skip empty lines

		# Match key => value or key = value
		if ($line =~ /^([^=\s>]+)\s*(?:=>|=)\s*(.+)$/) {
			my $key = $1;
			my $val = $2;

			$val =~ s/^['"]|['"]$//g; # Strip quotes
			$config{$key} = $val;
		}
	}
	close($fh);
	return %config;
}
