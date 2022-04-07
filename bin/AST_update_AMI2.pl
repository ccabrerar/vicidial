#!/usr/bin/perl
#
# AST_update_AMI2.pl version 2.14
#
# This script uses the Asterisk Manager interface to update the live_channels
# tables and verify the parked_channels table in the asterisk MySQL database
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 170915-2110 - Initial version for Asterisk 13, based upon AST_update.pl
# 171002-1111 - Fixed timeout erase channels issue, added more debug output
# 171228-1832 - Added more debug logging
# 180511-1146 - Changed to use server-specific cid_channels_recent table
# 181003-1728 - Fix for RINGAGENT calls
# 190102-1509 - More fixes for RINGAGENT Calls
# 190121-1505 - Added RA_USER_PHONE On-Hook CID to solve last RINGAGENT issues
# 201119-2119 - Fix for time logging inserts/updates to database
# 210315-1045 - Populate the CIDname in live_sip_channels/live_channels tables, Issue #1255
# 210827-0930 - Added PJSIP compatibility
# 220310-1136 - Fix for issue dealing with bad carrier 'P-Asserted-Identity' input
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$DBX=0;
$run_check=1; # concurrency check

# loop time in seconds
$loop_time = 0.4;

# how often settings should be reloaded from the DB in seconds
$settings_reload_interval = 10;

# how often the server stats are updated
$server_stats_update_interval = 30;

# how often performance logging is triggered
$performance_logging_interval = 5;

### begin parsing run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [--test] = test\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = Extra-verbose debug messages\n";
		print "  [--help] = this screen\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			print "\n----- DEBUGGING ENABLED -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER-DUPER DEBUGGING -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		}
	}
### end parsing run-time options ###

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ($line =~ /^PATHhome/)	{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ($line =~ /^PATHlogs/)	{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ($line =~ /^PATHagi/)	{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ($line =~ /^PATHweb/)	{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ($line =~ /^PATHsounds/)	{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ($line =~ /^PATHmonitor/)	{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ($line =~ /^VARserver_ip/)	{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ($line =~ /^VARDB_server/)	{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ($line =~ /^VARDB_database/)	{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ($line =~ /^VARDB_user/)	{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_pass/)	{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_custom_user/)	{$VARDB_custom_user = $line;   $VARDB_custom_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_custom_pass/)	{$VARDB_custom_pass = $line;   $VARDB_custom_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_port/)	{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP


### find df binary (partition usage)
$dfbin = '';
if ( -e ('/bin/df')) {$dfbin = '/bin/df';}
else
	{
	if ( -e ('/usr/bin/df')) {$dfbin = '/usr/bin/df';}
	else
		{
		if ( -e ('/usr/local/bin/df')) {$dfbin = '/usr/local/bin/df';}
		else
			{
			print "Can't find df binary! Exiting...\n";
			exit;
			}
		}
	}


if (!$VARDB_port) {$VARDB_port='3306';}

&get_time_now;

#use lib './lib', '../lib';
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Net::Telnet ();
use Switch;
use POSIX;
use Scalar::Util qw(looks_like_number);
use Term::ANSIColor qw(:constants);


$module = 'String::Escape qw( backslash unbackslash )';
$bs_loaded=0;
if (try_load($module)) 
	{
	$bs_loaded=1;
	}


$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,vd_server_logs,asterisk_version FROM servers where server_ip = '$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DBtelnet_host	=		$aryA[0];
	$DBtelnet_port	=		$aryA[1];
	$DBASTmgrUSERNAME =		$aryA[2];
	$DBASTmgrSECRET	=		$aryA[3];
	$DBASTmgrUSERNAMEupdate	=	$aryA[4];
	$DBASTmgrUSERNAMElisten	=	$aryA[5];
	$DBASTmgrUSERNAMEsend	=	$aryA[6];
	$DBmax_vicidial_trunks	=	$aryA[7];
	$DBanswer_transfer_agent=	$aryA[8];
	$DBSERVER_GMT		=	$aryA[9];
	$DBext_context		=	$aryA[10];
	$DBvd_server_logs 	=	$aryA[11];
	$asterisk_version 	=	$aryA[12];
	if ($DBtelnet_host)		{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)		{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)		{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)		{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMEsend)	{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	if ($DBmax_vicidial_trunks)	{$max_vicidial_trunks = $DBmax_vicidial_trunks;}
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if ($DBSERVER_GMT)		{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)		{$ext_context = $DBext_context;}
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
		else {$SYSLOG = '0';}
	}

if (!$telnet_port) {$telnet_port = '5038';}


##### Check for a server_updater record, and if not present, insert one
$SUrec=0;
$stmtA = "SELECT count(*) FROM server_updater where server_ip='$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
@aryA = $sthA->fetchrow_array;
$SUrec = $aryA[0];
if($DB){print STDERR "\n|$SUrec|$stmtA|\n";}

if ($SUrec < 1)
	{
	&get_time_now;

	$stmtU = "INSERT INTO server_updater set server_ip='$server_ip', last_update='$now_date';";
	if($DB){print STDERR "\n$stmtU\n";}
	$affected_rows = $dbhA->do($stmtU);
	}

$event_string='LOGGED INTO MYSQL SERVER ON 1 CONNECTION|';
event_logger($SYSLOG,$event_string);


##### BEGIN Check for a cid_channels_recent_IPXXXXX... table, and if not present, create one
$cid_channels_recent = 'cid_channels_recent';
$PADserver_ip = $server_ip;
$PADserver_ip =~ s/(\d+)(\.|$)/sprintf "%3.3d$2",$1/eg; 
$PADserver_ip =~ s/\.//eg; 
$CCRrec=0;   $affected_rowsCCR=0;
$stmtA = "SHOW TABLES LIKE \"cid_channels_recent_$PADserver_ip\";";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$CCRrec=$sthA->rows;
if($DB){print STDERR "\n|$CCRrec|$stmtA|\n";}

if ($CCRrec > 0)
	{$cid_channels_recent = "cid_channels_recent_$PADserver_ip";}
else
	{
	$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_custom_user", "$VARDB_custom_pass")
	or warn "Couldn't connect to database: " . DBI->errstr;

	$stmtB = "CREATE TABLE cid_channels_recent_$PADserver_ip (caller_id_name VARCHAR(30) NOT NULL, connected_line_name VARCHAR(30) NOT NULL, call_date DATETIME, channel VARCHAR(100) DEFAULT '', dest_channel VARCHAR(100) DEFAULT '', linkedid VARCHAR(20) DEFAULT '', dest_uniqueid VARCHAR(20) DEFAULT '', uniqueid VARCHAR(20) DEFAULT '', index(call_date)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	if($DB){print STDERR "\n$stmtB\n";}
	eval { $affected_rowsCCR = $dbhB->do($stmtB) };
	print "Query failed: $@\n" if $@;

	eval { $dbhB->disconnect() };
	print "Disconnect failed: $@\n" if $@;

	$stmtA = "SHOW TABLES LIKE \"cid_channels_recent_$PADserver_ip\";";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$CCRrec=$sthA->rows;
	if($DB){print STDERR "\n|$CCRrec|$stmtA|\n";}

	if ($CCRrec > 0)
		{$cid_channels_recent = "cid_channels_recent_$PADserver_ip";}
	}
print STDERR "$CCRrec|$cid_channels_recent|$stmtA\n";

$event_string='TABLE CHECK cid_channels_recent_$PADserver_ip complete|$CCRrec|$affected_rowsCCR|';
event_logger($SYSLOG,$event_string);
##### END Check for a cid_channels_recent_IPXXXXX... table, and if not present, create one


### concurrency check (SCREEN uses script path, so check for more than 2 entries)
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 2) 
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		$event_string = "I am not alone! Another $0 is running! Exiting...";
		&event_logger($SYSLOG,$event_string);
		exit;
		}
	}


$event_string="STARTING NEW MANAGER TELNET CONNECTION||ATTEMPT|ONE DAY INTERVAL:$one_day_interval|";
event_logger($SYSLOG,$event_string);

$max_buffer = 4*1024*1024; # 4 meg buffer

### connect to asterisk manager through telnet
$tn = new Net::Telnet (
	Port => $telnet_port,
	Prompt => '/\r\n/',
	Output_record_separator => "\n\n",
	Max_buffer_length => $max_buffer, 
	Telnetmode => 0,
);

$UPtelnetlog = "$PATHlogs/update_telnet_log.txt";  # uncomment for telnet log
#$fh = $tn->dump_log("$UPtelnetlog");  # uncomment for telnet log

if (length($ASTmgrUSERNAMEupdate) > 3) {$telnet_login = $ASTmgrUSERNAMEupdate;}
else {$telnet_login = $ASTmgrUSERNAME;}
$tn->open("$telnet_host"); 
$tn->waitfor('/Asterisk Call Manager\//');

# get the AMI version number
$ami_version = $tn->getline(Errmode => Return, Timeout => 1,);
$ami_version =~ s/\n//gi;
print "----- AMI Version $ami_version -----\n";

# Login
$tn->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET");
$tn->waitfor('/Authentication accepted/');		# waitfor auth accepted

$tn->buffer_empty;

$event_string="STARTING NEW MANAGER TELNET CONNECTION|$telnet_login|CONFIRMED CONNECTION|AMI Version $ami_version|ONE DAY INTERVAL:$one_day_interval|";
event_logger($SYSLOG,$event_string);

%ast_ver_str = parse_asterisk_version($asterisk_version);
if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 13))
	{
	print "Asterisk version too low for this script. Exiting.\n\n\n";
	$event_string = "Asterisk version too low for this script. Exiting.";
	&event_logger($SYSLOG,$event_string);
	exit;
	}
else 
	{
	### BEGIN manager event handling for asterisk version >= 13
	$endless_loop = 1;
	$loop_count = 0;

	# get the number of microseconds the loop is supposed to take
	$loop_time_usec = $loop_time * 1000000;
	$loop_time_usec = ceil($loop_time_usec); # take care of any floating point math

	# total of the number of bad channel grabs from Asterisk
	$total_bad_grabs = 0;

	# the last time our settings were loaded from the DB
	$last_config_reload = 0;

	# the last time the server stats were updated
	$last_stats_update = 0;

	# the last time the performance data was logged
	$last_perf_log = 0;
	
	# the various channel counts from the last loop
	$counts->{'psuedo'} = 0;
	$counts->{'total'} = 0;
	$counts->{'trunks'} = 0;
	$counts->{'clients'} = 0;
	$counts->{'sip'} = 0;
	$counts->{'iax'} = 0;
	$counts->{'dahdi'} = 0;
	$counts->{'local'} = 0;
	$counts->{'other'} = 0;

	%parked_channels_hash = {};
	$parked_channels = \%parked_channels_hash;

	while($endless_loop)
		{
		# increment total loop counter
		$loop_count++;

		# make a copy of our counts
		%old_counts_hash = %{ $counts };
		$old_counts = \%old_counts_hash;

		# get the current time
		( $now_sec, $now_micro_sec ) = gettimeofday();

		# figure out how many micro seconds since epoch
		$now_micro_epoch = $now_sec * 1000000;
		$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

		$begin_micro_epoch = $now_micro_epoch;
		
		# create a new action id
		$action_id = "$now_sec.$now_micro_sec";

		$modechange = $tn->errmode('return');

		# ask the AMI for the channels
		$action_string = "Action: CoreShowChannels\nActionID: $action_id";
		$tn->print($action_string);

		# wait till we get the response
		$tn->waitfor('/Message: Channels will follow\n\n/');
		$msg = $tn->errmsg;
		if (  $msg ne '' ) 
			{
			$event_string =  "WAITFOR ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|Command: $action_string|";
			&event_logger($SYSLOG,$event_string);
			print $event_string."\n";
			}

		# initialize the channal array
		@chan_array = ();

		# prepare to loop
		$get_channels = 1;

		# total number of channels pulled
		$total_channels = 0;

		# whether this channel pull was bad
		$bad_grab = 0;

		&get_time_now;
		if ($DB) { print "\n\n$now_date\n|Channel List:\n"; }

		# Loop to get all of the channels
		$event_ref = {};

		$match_string = '/Event: CoreShowChannelsComplete\nActionID: ' . $action_id . '\nEventList: Complete\nListItems: \d+/';

		# get an entire record
		( $read_input_buf, $match ) = $tn->waitfor(Errmode => Return, Timeout => 1, Match => "$match_string" );

		chomp( $read_input_buf );

		if (($DBX) && ($bs_loaded) && (length($read_input_buf) > 0)) { print "|read|" . backslash( $read_input_buf ) . "|\n"; }

		if (($DBX) && ($bs_loaded) && (length($read_input_buf) > 0)) { print "|match|" . backslash( $match ) . "|\n"; }


		( $junk, $list_items) = split( /ListItems: /, $match );

		if ($DBX) { print "|total_channels|$list_items\n"; }

		# check if there were any errors
		$msg='';
		$msg = $tn->errmsg;
		if ( ( $msg ne '' ) && ( $msg !~  /read timed-out/i ) )
			{
			if ($msg =~ /filehandle isn\'t open/i)
				{
				# we have lost connection
				$endless_loop=0;
				$one_day_interval=0;
				print "ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|\n";
				print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
				$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|$msg|";
				event_logger($SYSLOG,$event_string);
				}
			else
				{
				# something happened
				print "ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|Command: $action_string|Match String: $match_string|\n";
				$bad_grab = 1;
				}
			}
		else
			{
			@records = split( /\n\n/, $read_input_buf );
			foreach my $record ( @records )
				{			
				# Loop to get all of the channels
				$event_ref = {};

				# break the record into lines
				@lines = split( /\n/, $record );
				foreach my $line ( @lines)
					{
					# add the line to the hash
					($key,$value) = split( /: /, $line );
					if ($key ne "")
						{
						$value =~ s/\n//gi;	# remove new lines
						$value =~ s/^ +//gi;	# remove leading white space
						$value =~ s/ +$//gi;	# remove trailing white space
					
						# change certain strings to conform with Asterisk 1.0 behavior
						$value =~ s/Congestion\s+\(Empty\)/ SIP\/CONGEST/gi;
						$value =~ s/\(Outgoing Line\)|\(None\)/SIP\/ring/gi;
						$value =~ s/\(Empty\)/SIP\/internal/gi;
						$event_ref->{"$key"} = $value;
						}
					}

				$hash_size = keys(%$event_ref);

				if ( ($DB) && (  $hash_size > 2 ) )
					{
					if ( $event_ref->{'Event'} eq "CoreShowChannel" )
						{
						if ( $total_channels == 0 )
							{
							print "|Channel|ChannelState|ChannelStateDesc|Exten|Priority|Context|CallerIDName|CallerIDNum|ConnectedLineName|ConnectedLineNum|Uniqueid|Linkedid|Application|ApplicationData|BridgeId|AccountCode|Language|Duration|\n";
							}
							
						print "|$event_ref->{'Channel'}";
						print "|$event_ref->{'ChannelState'}";
						print "|$event_ref->{'ChannelStateDesc'}";
						print "|$event_ref->{'Exten'}";
						print "|$event_ref->{'Priority'}";
						print "|$event_ref->{'Context'}";
						print "|$event_ref->{'CallerIDName'}";
						print "|$event_ref->{'CallerIDNum'}";
						print "|$event_ref->{'ConnectedLineName'}";
						print "|$event_ref->{'ConnectedLineNum'}";
						print "|$event_ref->{'Uniqueid'}";
						print "|$event_ref->{'Linkedid'}";
						print "|$event_ref->{'Application'}";
						print "|$event_ref->{'ApplicationData'}";
						print "|$event_ref->{'BridgeId'}";
						print "|$event_ref->{'AccountCode'}";
						print "|$event_ref->{'Language'}";
						print "|$event_ref->{'Duration'}|";
						print "\n";
						}
					}

				$event_ref->{'AMIVersion'} = $ami_version;
				$event_ref->{'ServerIP'} = $server_ip;


				$retcode = 1;
				# make sure this is an event not a response
				if (exists($event_ref->{"Event"})) 
					{
					if ( $event_ref->{"Event"} eq "CoreShowChannel" )
						{
						$total_channels++;
						# We got a channel
						if ( $event_ref->{"ActionID"} eq $action_id )
							{
							push( @chan_array, $event_ref );
							}
						else
							{
							if ($DB) { print STDERR "We got the wrong Action ID!!!!\n"; }
							}
						}
					else
						{
						# We got something else???
						}
					}
				elsif ( exists($event_ref->{"Response"}) ) 
					{
					# we got the response. Channels should follow.
					}
				}
			}
		
		if ( $total_channels != $list_items ) 
			{
			# something is wrong. we did not process the
			# same number of channels as were reported.
			$bad_grab = 1; 
			$total_bad_grabs++;
			}

		if ($DB) { print STDERR "$total_channels channels recieved out of $list_items reported.|$bad_grab|\n"; }
		
		$chan_array_ref = \@chan_array;

		# reload the settings from the DB at a fixed interval
		$epoch = time();
		if ( $epoch - $last_config_reload >= $settings_reload_interval ) 
			{
			# reload the server settings
			$settings_ref = get_server_settings($dbhA,$server_ip);
			%settings = %$settings_ref;

			# reload the phone settings
			$phones_ref = get_phones_settings($dbhA,$server_ip);
			%phones = %$phones_ref;

			$last_config_reload = time();
			}

		
		# update our time in the DB
		$stmtA = "UPDATE server_updater set last_update='$now_date' where server_ip='$server_ip'";
		if($DB){print STDERR "\n$stmtA\n";}
		$dbhA->do($stmtA);

		# get the channels currently in the db
		my ($db_trunks_ref, $db_clients_ref) = get_db_channels($dbhA,$server_ip);

		# gather the various stats specific to the  server stats
		if ( $epoch - $last_stats_update >= $server_stats_update_interval ) 
			{
			$disk_usage = get_disk_space();
			}

		# gather the various stats specific to the performance log
		if (( $settings_ref->{'sys_perf_log'} ) && ( $epoch - $last_perf_log >= $performance_logging_interval )) 
			{
			( $mem_total, $mem_used, $mem_free ) = get_mem_usage();
			$num_processes = get_num_processes();
			( $reads, $writes ) = get_disk_rw();
			}

		# gather stats common to the performance log and server stats
		if ( ( $epoch - $last_stats_update >= $server_stats_update_interval ) ||
			(( $settings_ref->{'sys_perf_log'} ) && ( $epoch - $last_perf_log >= $performance_logging_interval )) )
			{
			$server_load = get_cpu_load();

			(
				$cpu_user_percent,
				$cpu_idle_percent,
				$cpu_sys_percent,
				$cpu_vm_percent,
				$user_diff,
				$nice_diff,
				$system_diff,
				$idle_diff,
				$iowait_diff,
				$irq_diff,
				$softirq_diff,
				$steal_diff,
				$guest_diff,
				$guest_nice_diff
			) = get_cpu_percent();

			}


		# check if there was something wrong with our channel grab
		if ( $bad_grab )
 			{
			if($DB){print STDERR "BAD GRAB!!!\n";}

			# check if it is time to update the server stats
			# if so use the old counts as the new ones are off
			if ( $epoch - $last_stats_update >= $server_stats_update_interval ) 
				{
       			server_stats_update( $dbhA, $server_ip, $old_counts, $server_load, $cpu_idle_percent, $disk_usage );
	       		$last_stats_update = time();
				}

			# check if it is time to log the performance stats
			# if so use the old counts as the new ones are off
			if (( $settings_ref->{'sys_perf_log'} ) && ( $epoch - $last_perf_log >= $performance_logging_interval )) 
				{
				server_perf_log( $dbhA, $server_ip, $old_counts, $server_load, $mem_free, $mem_used, $num_processes, $cpu_user_percent, $cpu_sys_percent, $cpu_idle_percent, $reads, $writes );
				$last_perf_log = time();
				}

			usleep(10000); # sleep a hundreth of a second and try again
			}
		else
			{

			# Process the channel list
			$counts = process_channels($dbhA,$chan_array_ref,$phones_ref,$db_trunks_ref,$db_clients_ref,$server_ip,$old_counts);

			# make sure all the parked calls are valid
			$parked_channels = validate_parked_channels( $dbhA, $parked_channels, $chan_array_ref, $server_ip );
			
			# check if it is time to update the server stats
			if ( $epoch - $last_stats_update >= $server_stats_update_interval ) 
				{
				server_stats_update( $dbhA, $server_ip, $counts, $server_load, $cpu_idle_percent, $disk_usage );
				$last_stats_update = time();
				}

			# check if it is time to log the performance stats
			if (( $settings_ref->{'sys_perf_log'} ) && ( $epoch - $last_perf_log >= $performance_logging_interval )) 
				{
				server_perf_log( $dbhA, $server_ip, $counts, $server_load, $mem_free, $mem_used, $num_processes, $cpu_user_percent, $cpu_sys_percent, $cpu_idle_percent, $reads, $writes );
				$last_perf_log = time();
				}

			# figure out how long that loop took.
			# get the current time
			( $now_sec, $now_micro_sec ) = gettimeofday();

			# figure out how many micro seconds since epoch
			$now_micro_epoch = $now_sec * 1000000;
			$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

			$end_micro_epoch = $now_micro_epoch;

			# subtract the current time in usec from time at the beginning of the loop
			$loop_usec = $end_micro_epoch - $begin_micro_epoch;
	
			# figure out how long to sleep
			$sleep_usec = $loop_time_usec - $loop_usec;

			# cannot sleep negative time
			if ( $sleep_usec > 0 ) 
				{
				if ($DB) { print STDERR "loop took $loop_usec microseconds. sleeping for $sleep_usec microseconds to compensate\n"; }

				# sleep it
				usleep($sleep_usec);

				}
			}
		}
	}



if($DB){print "DONE... Exiting... Goodbye... See you later... Not really, initiating next loop...$one_day_interval left\n";}

$event_string='HANGING UP|';
event_logger($SYSLOG,$event_string);

@hangup = $tn->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/", Errmode    => Return, Timeout    => 1); 

$tn->buffer_empty;
$tn->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
$ok = $tn->close;


$event_string='CLOSING DB CONNECTION|';
event_logger($SYSLOG,$event_string);


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;


sub process_channels
	{
	my ($dbhA,$chan_array_ref,$phones_ref,$db_trunks_ref,$db_clients_ref,$server_ip,$old_counts) = @_;

	$client_insert_sql = "";
	$client_insert_count = 0;
	$client_delete_sql = "";
	$client_delete_count = 0;
	$trunk_insert_sql = "";
	$trunk_insert_count = 0;
	$trunk_delete_sql = "";
	$trunk_delete_count = 0;

	$counts->{'psuedo'} = 0;
	$counts->{'total'} = 0;
	$counts->{'trunks'} = 0;
	$counts->{'clients'} = 0;
	$counts->{'sip'} = 0;
	$counts->{'iax'} = 0;
	$counts->{'dahdi'} = 0;
	$counts->{'local'} = 0;
	$counts->{'other'} = 0;

	%cid_chan_hash = {};

	foreach $channel_ref (@$chan_array_ref)
		{
		$call_id = get_valid_callid($channel_ref->{'CallerIDName'},$channel_ref->{'ConnectedLineName'});

		# only need to match local channels to real channels on VDAD and RINGAGENT calls
		if ( (( $call_id =~ /^V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d$/ ) && ( $channel_ref->{'ConnectedLineName'} ne "<unknown>" )) || ( $call_id =~ /^RINGAGENT|^RA_/ ) )
			{
			if ( $channel_ref->{'Channel'} =~ /^Local/ ) 
				{
				$cid_chan_hash{"$call_id"}->{'channel'} = $channel_ref->{'Channel'};
				$cid_chan_hash{"$call_id"}->{'uniqueid'} = $channel_ref->{'Uniqueid'};
				$cid_chan_hash{"$call_id"}->{'linkedid'} = $channel_ref->{'Linkedid'};
				$cid_chan_hash{"$call_id"}->{'calleridname'} = $channel_ref->{'CallerIDName'};
				$cid_chan_hash{"$call_id"}->{'connectedlinename'} = $channel_ref->{'ConnectedLineName'};
				}
			else
				{
				$cid_chan_hash{"$call_id"}->{'dest_channel'} = $channel_ref->{'Channel'};
				$cid_chan_hash{"$call_id"}->{'dest_uniqueid'} = $channel_ref->{'Uniqueid'};
				$cid_chan_hash{"$call_id"}->{'linkedid'} = $channel_ref->{'Linkedid'};
				$cid_chan_hash{"$call_id"}->{'calleridname'} = $channel_ref->{'CallerIDName'};
				$cid_chan_hash{"$call_id"}->{'connectedlinename'} = $channel_ref->{'ConnectedLineName'};
				}
			}
	

		$extension = $channel_ref->{'ApplicationData'};
		$extension =~ s/^SIP\///gi; # remove "SIP/" from the beginning
		$extension =~ s/^PJSIP\///gi; # remove "PJSIP/" from the beginning
		$extension =~ s/-\S+$//gi; # remove everything after a -
		$extension =~ s/\|.*//gi; # remove everything after the |
		$extension =~ s/,.*//gi; # remove everything after the ,
		$CIDnameSQL = $channel_ref->{'CallerIDName'};
		$CIDnameSQL =~ s/'|"|;//gi; # remove quotes and semi-colon
		if (length($CIDnameSQL) > 30) 
			{$CIDnameSQL = substr($CIDnameSQL,0,30);}

		# make sure the channel is a channel type we care about
		if ( $channel_ref->{'Channel'} =~ /^IAX2|^SIP|^Local|^DAHDI|^PJSIP/)
			{
			# assume all channels are trunks then check if they are not
			$line_type = 'TRUNK';			
 
			$channel_match = $channel_ref->{'Channel'};
			

			# Local channels are not considered trunks
			if ( $channel_match =~ /^Local/)
				{
				$line_type = 'CLIENT';
				$counts->{'local'}++;
				$counts->{'clients'}++; 
				}
			elsif ( $channel_match =~ /^IAX2/) 
				{
				# clean up the channel to get the extension
				$channel_match =~ s/\/\d+$|-\d+$//gi;
				$channel_match =~ s/^IAX2\///gi;
				$channel_match =~ s/\*/\\\*/gi;

				# if the channels extension is in phones 
				# and it is and IAX phone then it is a client
				if ( ( exists ( $phones_ref->{ $channel_match } ) ) && ( $phones_ref->{ $channel_match }->{'protocol'} eq 'IAX2' ) )
					{
					$line_type = 'CLIENT';
					$counts->{'iax'}++;
					$counts->{'clients'}++;
					}
				}
			elsif (($channel_ref->{'Channel'} =~ /^SIP/) || ($channel_ref->{'Channel'} =~ /^PJSIP/)) 
				{
				# clean up the channel to get the extension
				$channel_match =~ s/-\S+$//gi;
				$channel_match =~ s/^SIP\///gi;
				$channel_match =~ s/^PJSIP\///gi;
				$channel_match =~ s/\*/\\\*/gi;	

				# if the channels extension is in phones
				# and it is and SIP phone then it is a client
				if ( ( exists ( $phones_ref->{ $channel_match } ) ) && ( ( $phones_ref->{ $channel_match }->{'protocol'} eq 'SIP' ) || ( $phones_ref->{ $channel_match }->{'protocol'} eq 'PJSIP' )) )
					{
					$line_type = 'CLIENT';
					$counts->{'sip'}++;
					$counts->{'clients'}++;
					}
				}
			# no point logging pseudo channels, just increment the psuedo count
			elsif ( $channel_ref->{'Channel'} =~ /^DAHDI\/pseudo/ )
				{
				$line_type = 'PSEUDO';
				$counts->{'psuedo'}++;
				}

			elsif ($channel_ref->{'Channel'} =~ /^DAHDI/) 
				{
				# clean up the channel to get the extension
				$channel_match =~ s/^DAHDI\///gi;
				$channel_match =~ s/\*/\\\*/gi;

				# if the channels extension is in phones
				# and it is and DAHDI/ZAP phone then it is a client
				if ( ( exists ( $phones_ref->{ $channel_match } ) ) && ( $phones_ref->{$channel_match}->{'protocol'} =~ /^ZAP|^DAHDI/) )
					{
					$line_type = 'CLIENT';
					$counts->{'dahdi'}++;
					$counts->{'clients'}++;
					}
				}

			# count what is left of the trunks
			if ( $line_type eq 'TRUNK' ) { $counts->{'trunks'}++; }

			}
		else
			{
			$counts->{'other'}++;
			}
	
		# count the total we have processed
		$counts->{'total'}++;
		
		# process the CLIENTs first
		if ( $line_type eq 'CLIENT' )
			{
			# check if the channel is in the DB already
			$in_db = 0;
			foreach $db_client ( @$db_clients_ref )
				{
				if ( ( $db_client->{'channel'} eq $channel_ref->{'Channel'} ) && ( $db_client->{'extension'} eq $extension ) )
					{ 
					if ($DBX) { print STDERR "marking $db_client->{'channel'} active in db_clients\n"; }
					$in_db = 1;			# not that the channel is in the DB
					$db_client->{'active'} = 1; 	# mark that this channel is still active
					last;		 		# break out of this foreach loop
					}
				}

			if ( $in_db )
				{
				# channel already exists in the DB
				}
			else
				{
				# Add this channel to the client insert
				if ( $client_insert_count > 0 ) { $client_insert_sql .= ", \n\t" }
				$client_insert_sql .= "('$channel_ref->{'Channel'}','$server_ip','$extension','$CIDnameSQL','$channel_ref->{'ApplicationData'}')";
				$client_insert_count++;				
				}
			}

		# if not a client it must be a TRUNK
		elsif ( $line_type eq 'TRUNK' )
			{
			# check if the channel is in the DB already
			$in_db = 0;
			foreach $db_trunk ( @$db_trunks_ref )
				{
				if ( ( $db_trunk->{'channel'} eq $channel_ref->{'Channel'} ) && ( $db_trunk->{'extension'} eq $extension) ) 
					{
					if ($DBX) { print STDERR "marking $db_trunk->{'channel'} active in db_trunks\n"; }
					$in_db = 1;			# not that the channel is in the DB
					$db_trunk->{'active'} = 1;	# mark that this channel is still active
					last;				# break out of this foreach loop
					}
				}

			if ( $in_db )
				{
				# channel already exists in the DB
				}
			else
				{
				# Add this channel to the trunk insert
				if ( $trunk_insert_count > 0 ) { $trunk_insert_sql .= ", \n\t"; }
				$trunk_insert_sql .= "('$channel_ref->{'Channel'}','$server_ip','$extension','$CIDnameSQL','$channel_ref->{'ApplicationData'}')";
				$trunk_insert_count++;
				}
			}
		}

	if ( bad_grab_check( $counts, $old_counts ) )
		{
		if($DB){print STDERR "BAD GRAB!!!\n";}

		usleep(10000); # sleep a hundreth of a second and try again

		return $old_counts;
		}
	else
		{
		if ( $client_insert_count > 0 )
			{
			# insert all the new clients
			my $stmtA = "INSERT INTO live_sip_channels (channel,server_ip,extension,channel_group,channel_data) values \n\t" . $client_insert_sql;
			if ($DB) { print STDERR "$stmtA\n"; }
			$stmtA =~ s/\n|\t//gi;
			$dbhA->do($stmtA);
			}
		elsif ($DB) { print STDERR "|no client channels to insert|\n"; }

		if ( $trunk_insert_count > 0 )
			{
			# insert all the new trunks
			my $stmtA = "INSERT INTO live_channels (channel,server_ip,extension,channel_group,channel_data) values \n\t" . $trunk_insert_sql;
			if ($DB) { print STDERR "$stmtA\n"; }
			$stmtA =~ s/\n|\t//gi;
			$dbhA->do($stmtA);
			}
		elsif ($DB) { print STDERR "|no trunk channels to insert|\n"; }

		# find any clients that have hung up
		foreach $db_client ( @$db_clients_ref )
			{
			# if DB client not in current channel list add it to the client delete
			if ( ( !(exists( $db_client->{'active'} ) ) ) || ($db_client->{'active'} == 0 ) )
				{
				if ( $client_delete_count > 0 ) { $client_delete_sql .= " or \n\t"; }
				$client_delete_sql .= "(server_ip='$server_ip' and channel='$db_client->{'channel'}' and extension='$db_client->{'extension'}')";
				$client_delete_count++;
				}
			} 
		
		# find any trunks that have hung up
		foreach $db_trunk ( @$db_trunks_ref )
			{
			# if DB trunk not in current channel list add it to the trunk delte
			if ( ( !(exists( $db_trunk->{'active'} ) ) ) || ( $db_trunk->{'active'} == 0 ) )
				{
				if ( $trunk_delete_count > 0 ) { $trunk_delete_sql .= " or \n\t"; }
				$trunk_delete_sql .= "(server_ip='$server_ip' and channel='$db_trunk->{'channel'}' and extension='$db_trunk->{'extension'}')";
				$trunk_delete_count++;
				}
			}

		# delete all the hung up clients
		if ( $client_delete_count > 0 )
			{
			my $stmtA = "DELETE FROM live_sip_channels where \n\t(" . $client_delete_sql . " )";
			if ($DB) { print STDERR "$stmtA\n"; }
			$stmtA =~ s/\n|\t//gi;
			$affected_rows = $dbhA->do($stmtA);
			}
		elsif ($DB) { print STDERR "|no client channels to delete|\n"; }
			
		# delete all the hung up trunks
		if ( $trunk_delete_count > 0 )
			{
			my $stmtA = "DELETE FROM live_channels where \n\t(" . $trunk_delete_sql . " )";
			if ($DB) { print STDERR "$stmtA\n"; }
			$stmtA =~ s/\n|\t//gi;
			$affected_rows = $dbhA->do($stmtA);
			}
		elsif ($DB) { print STDERR "|no trunk channels to delete|\n"; }

		# build the cid_channels_recent_$PADserver_ip insert
		$cid_chan_sql = '';
		$cid_chan_count = 0;
		foreach $call_id (keys %cid_chan_hash)
			{
			if (  ( $cid_chan_hash{"$call_id"}->{'channel'} ne "" ) && ( $cid_chan_hash{"$call_id"}->{'dest_channel'} ne "" ) )
				{
				if ( $cid_chan_count > 0 ) { $cid_chan_sql .= ",\n\t"; }
				$cid_chan_sql .= "( ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'calleridname'} . "', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'connectedlinename'} . "', ";
			#	$cid_chan_sql .= "'$server_ip', ";
				$cid_chan_sql .= "'$now_date', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'channel'} . "', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'dest_channel'} . "', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'linkedid'} . "', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'uniqueid'} . "', ";
				$cid_chan_sql .= "'" . $cid_chan_hash{"$call_id"}->{'dest_uniqueid'} . "'";
				$cid_chan_sql .= " )";
				$cid_chan_count++;
				}
			}

		if ( $cid_chan_count > 0 ) 
			{
		#	$stmtA = "INSERT IGNORE INTO cid_channels_recent (caller_id_name, connected_line_name, server_ip, call_date, channel, dest_channel, linkedid, dest_uniqueid, uniqueid) values \n\t";
			$stmtA = "INSERT IGNORE INTO $cid_channels_recent (caller_id_name, connected_line_name, call_date, channel, dest_channel, linkedid, dest_uniqueid, uniqueid) values \n\t";
			$stmtA .= $cid_chan_sql;
			if ($DB) { print STDERR "$stmtA\n"; }
			$stmtA =~ s/\n|\t//gi;
			$dbhA->do($stmtA);
			}

		return $counts;
		}
	}	

sub validate_parked_channels
	{
	( $dbhA, $parked_channels, $chan_array_ref, $server_ip ) = @_;

	my $park_delete_sql = "";
	my $park_delete_count = 0;
	my $auto_delete_sql = "(";

	# remove the duplicates caused by agents quickly parking, unparking, and parking again calls
	$stmtA = "DELETE pc1 FROM parked_channels pc1, parked_channels pc2 WHERE pc1.parked_time < pc2.parked_time AND pc1.parked_time <> pc2.parked_time AND pc1.channel = pc2.channel AND pc1.server_ip='$server_ip' and pc2.server_ip='$server_ip'";
	$deleted_rows = $dbhA->do($stmtA);
	if ($DB) { print STDERR "Park Dup Del|$stmtA|$deleted_rows\n"; }

	# get the channels currently parked
	$stmtA = "SELECT channel,extension,parked_time,UNIX_TIMESTAMP(parked_time),channel_group FROM parked_channels where server_ip = '$server_ip' order by channel desc, parked_time desc;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$ref = $sthA->fetchall_arrayref;

	# loop through the parked channels
	foreach my $row ( @{ $ref } )
		{
		$channel = $row->[0];
		$extension = $row->[1];
		$parked_time = $row->[2];
		$unix_parked_time = $row->[3];
		$channel_group = $row->[4];

		# check if we already saw the parked channel before
		if ( exists( $parked_channels->{"$channel"} ) ) 
			{
			# if we have look for it in the current active channels
			$channel_found = 0;
			foreach $channel_ref (@$chan_array_ref)
				{
				# if we found it note that it there
				if  ( $channel_ref->{'Channel'} eq $channel ) { $channel_found = 1; }
				}

			# if it was found set its count back to 0
			if ($channel_found) { $parked_channels->{"$channel"} = 0; }
			# otherwise increment its count
			else { $parked_channels->{"$channel"}++; }
			}
		else
			{
			# if not add it to our list and set the count to 0
			$parked_channels->{"$channel"} = 0;
			}

		# if a channel has been gone for more than 10 seconds (25 calls to this function)
		if ( $parked_channels->{"$channel"} >= 25 )
			{
			# build the SQL for deleting stale values
			if ( $park_delete_count > 0 )
				{ 
				$park_delete_sql .= " or "; 
				$auto_delete_sql .= ", ";
				}
			$park_delete_sql .= "(channel='$channel' and extension='$extension')";
			$auto_delete_sql .= "'$channel_group'";
			$park_delete_count++;
			}
		}

	$auto_delete_sql .= " )";

	# if we have records to delete
	if ( $park_delete_count > 0 )
		{
		# delete the records from parked_channels
		$stmtA = "DELETE FROM parked_channels WHERE (" . $park_delete_sql . ") and server_ip='$server_ip'";
		$deleted_rows = $dbhA->do($stmtA);
		if ($DB) { print STDERR "Park Del|$stmtA|$deleted_rows\n"; }

		# delete the records from vicidial_auto_calls
		$stmtA = "DELETE FROM vicidial_auto_calls WHERE callerid IN " . $auto_delete_sql;
		$deleted_rows = $dbhA->do($stmtA);
		if ($DB) { print STDERR "Auto Calls Del|$stmtA|$deleted_rows\n"; }
		}

	return $parked_channels;
	}
	
sub bad_grab_check
	{
	my ($counts,$old_counts) = @_;
	my $bad_grab = 0;

	# calculate the percentage of the total number of channels that changed
	if ( (!$old_counts->{'total'}) or ($counts->{'total'} < 2) )
		{$percent_total_static = 0;}
	else
		{
		$percent_total_static = ( ($counts->{'total'} / $old_counts->{'total'}) * 100);
		$percent_total_static = sprintf("%6.2f", $percent_total_static);
		}

	# calculate the percentage of DAHDI client channels that changed including pseudo channels
	$current_dahdi = $counts->{'dahdi'} + $counts->{'pseudo'};
	$old_dahdi = $old_counts->{'dahdi'} + $_oldcounts->{'pseudo'};
	if ( (!$current_dahdi) or ($old_dahdi < 2) )
		{$percent_dahdi_client_static = 0;}
	else
		{
		$percent_dahdi_client_static = ( ($current_dahdi / $old_dahdi) * 100);
		$percent_dahdi_client_static = sprintf("%6.2f", $percent_dahdi_client_static);
		}

	# calculate the percentage of IAX client channels that changed
	if ( (!$counts->{'iax'}) or ($old_counts->{'iax'} < 2) )
		{$percent_iax_client_static = 0;}
	else
		{
		$percent_iax_client_static = ( ($counts->{'iax'} / $old_counts->{'iax'}) * 100);
		$percent_iax_client_static = sprintf("%6.2f", $percent_iax_client_static);
		}

	# calculate the percentage of Local client channels that changed
	if ( (!$counts->{'local'}) or ($old_counts->{'local'} < 2) )
		{$percent_local_client_static = 0;}
	else
		{
		$percent_local_client_static = ( ($counts->{'local'} / $old_counts->{'local'}) * 100);
		$percent_local_client_static = sprintf("%6.2f", $percent_local_client_static);
		}

	# calculate the percentage of SIP client channels that changed
	if ( (!$counts->{'sip'}) or ($old_counts->{'sip'} < 2) )
		{$percent_sip_client_static = 0;}
	else
		{
		$percent_sip_client_static = ( ($counts->{'sip'} / $old_counts->{'sip'}) * 100);
		$percent_sip_client_static = sprintf("%6.2f", $percent_sip_client_static);
		}

	if ($DB) { print "BG_check_stats [$percent_total_static|$counts->{'total'}:$old_counts->{'total'}\t$percent_dahdi_client_static|$counts->{'dahdi'}:$old_counts->{'dahdi'}\t$percent_iax_client_static|$counts->{'iax'}:$old_counts->{'iax'}\t$percent_local_client_static|$counts->{'local'}:$old_counts->{'local'}\t$percent_sip_client_static|$counts->{'sip'}:$old_counts->{'sip'}]\n"; }

	# MASSIVE IF STATEMENT FOR CHECKING IF IT WAS A BAD GRAB
	if ( 
		# if we had > 3 trunks or > 4 clients and we now have less than 10% of those remaining
		( ($percent_total_static < 10) && ( ($old_counts->{'trunk'} > 3) or ($old_counts->{'client'} > 4) ) ) or
		
		# or if we had > 10 trunks or > 10 clients and we now have less than 20% of those remaining
		( ($percent_total_static < 20) && ( ($old_counts->{'trunk'} > 10) or ($old_counts->{'client'} > 10) ) ) or
		
		# or if we had > 20 trunks or > 20 clients and we now have less than 30% of those remaining
		( ($percent_total_static < 30) && ( ($old_counts->{'trunk'} > 20) or ($old_counts->{'client'} > 20) ) ) or
		
		# or if we had > 30 trunks or > 30 clients and we now have less than 40% of those remaining
		( ($percent_total_static < 40) && ( ($old_counts->{'trunk'} > 30) or ($old_counts->{'client'} > 30) ) ) or
		
		# or if we had > 40 trunks or > 40 clients and we now have less than 50% of those remaining
		( ($percent_total_static < 50) && ( ($old_counts->{'trunk'} > 40) or ($old_counts->{'client'} > 40) ) ) or
		
		# or if we had > 3 dahdi clients and we now have less than 20% of those remaining
		( ($percent_dahdi_client_static < 20) && ( $old_counts->{'dahdi'} > 3 ) ) or
		
		# or if we had > 9 dahdi clients and we now have less than 40% of those remaining
		( ($percent_dahdi_client_static < 40) && ( $old_counts->{'dahdi'} > 9 ) ) or
		
		# or if we had > 3 iax clients and we now have less than 20% of those remaining
		( ($percent_iax_client_static < 20) && ( $old_counts->{'iax'} > 3 ) ) or
		
		# or if we had > 9 iax clients and we now have less than 40% of those remaining
		( ($percent_iax_client_static < 40) && ( $old_counts->{'iax'} > 9 ) ) or
		
		# or if we had > 3 sip clients and we now have less than 20% of those remaining
		( ($percent_sip_client_static < 20) && ( $old_counts->{'sip'} > 3 ) ) or
		
		# or if we had > 9 sip clients and we now have less than 40% of those remaining
		( ($percent_sip_client_static < 40) && ( $old_counts->{'sip'} > 9 ) )
	)
		{
		$bad_grab = 1;
		$event_string="------ UPDATER BAD GRAB!!!\n$percent_total_static|$counts->{'total'}:$old_counts->{'total'}\t$percent_dahdi_client_static|$counts->{'dahdi'}:$old_counts->{'dahdi'}\t$percent_iax_client_static|$counts->{'iax'}:$old_counts->{'iax'}\t$percent_local_client_static|$counts->{'local'}:$old_counts->{'local'}\t$percent_sip_client_static|$counts->{'sip'}:$old_counts->{'sip'}\n";
		&event_logger($SYSLOG,$event_string);
		}
	
	return $bad_grab;
	}



sub server_stats_update
	{
	( $dbhA, $server_ip, $channel_counts, $server_load, $cpu_idle_percent, $disk_usage ) = @_;

	$stmt = "UPDATE servers SET sysload='$server_load', channels_total='$channel_counts->{'total'}', cpu_idle_percent='$cpu_idle_percent', disk_usage='$disk_usage' where server_ip='$server_ip';";
	$rows = $dbhA->do($stmt);
	
	if ($DB) 
		{ 
		print STDERR BRIGHT_RED BOLD, "\nServer Stats Updated", RESET;
		print STDERR "|$stmt|$rows\n"; 
		}
	}

sub server_perf_log
	{
	( $dbhA, $server_ip, $channel_counts, $server_load, $mem_free, $mem_used, $num_processes, $cpu_user_percent, $cpu_sys_percent, $cpu_idle_percent, $reads, $writes ) = @_;

	$stmt = "INSERT INTO server_performance (start_time, server_ip, sysload, freeram, usedram, processes, channels_total, trunks_total, clients_total, clients_zap, clients_iax, clients_local, clients_sip, live_recordings, cpu_user_percent, cpu_system_percent, cpu_idle_percent, disk_reads, disk_writes) values('$now_date', '$server_ip', '$server_load', '$mem_free', '$mem_used', '$num_processes', '$channel_counts->{'total'}', '$channel_counts->{'trunks'}', '$channel_counts->{'clients'}', '$channel_counts->{'dahdi'}', '$channel_counts->{'iax'}', '$channel_counts->{'local'}', '$channel_counts->{'sip'}', '0', '$cpu_user_percent', '$cpu_sys_percent', '$cpu_idle_percent', '$reads', '$writes')";
	$dbhA->do($stmt);
	if ($DB) 
		{
		print STDERR BRIGHT_BLUE BOLD, "\nPerformance Log Record Inserted", RESET;
		print STDERR "|$stmt\n\n";
		}
	}


sub get_server_settings 
	{
	my ($dbhA,$server_ip) = @_;

	### Grab Server values from the database
	$stmtA = "SELECT sys_perf_log,vd_server_logs FROM servers where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$settings_ref = $sthA->fetchrow_hashref;

	if ( $settings_ref->{'sys_perf_log'} eq 'Y' ) { $settings_ref->{'sys_perf_log'} = 1;}
		else { $settings_ref->{'sys_perf_log'} = 0;}
	if ( $settings_ref->{'vd_server_logs'} eq 'Y' ) { $settings_ref->{'vd_server_logs'} = 1;}
		else { $settings_ref->{'vd_server_logs'} = 0;}
	
	return $settings_ref;
	}

sub get_phones_settings 
	{
	my ($dbhA,$server_ip) = @_;

	$stmtA = "SELECT extension, protocol FROM phones where server_ip='$server_ip' and phone_type NOT LIKE \"%trunk%\"";
	if($DB){print STDERR "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$phones_ref = $sthA->fetchall_hashref('extension');

	$zap_phones = 0;
	$sip_phones = 0;
	$iax_phones = 0;

	$zap_phone_list = "|";
	$sip_phone_list = "|";
	$iax_phone_list = "|";
	foreach $key ( sort (keys %$phones_ref) )
		{
		if ($DB) { print STDERR "$key|$phones_ref->{$key}->{'protocol'}\n"; }

		if ( $phones_ref->{$key}->{'protocol'} eq 'ZAP' )
			{
			$zap_phones++;
			$zap_phone_list .= "$key|";
			}
		elsif (( $phones_ref->{$key}->{'protocol'} eq 'SIP' ) || ( $phones_ref->{$key}->{'protocol'} eq 'PJSIP' ))
			{
			$sip_phones++;
			$sip_phone_list .= "$key|";
			}
		elsif ( $phones_ref->{$key}->{'protocol'} eq 'IAX' )
			{
			$iax_phones++;
			$iax_phone_list .= "$keys|";
			}
		}

	$phones_ref->{'zap_phones'} = $zap_phones;
	$phones_ref->{'sip_phones'} = $sip_phones;
	$phones_ref->{'iax_phones'} = $iax_phones;
	$phones_ref->{'zap_phone_list'} = $zap_phone_list;
	$phones_ref->{'sip_phone_list'} = $sip_phone_list;
	$phones_ref->{'iax_phone_list'} = $iax_phone_list;

	return $phones_ref;
	}

sub get_db_channels
	{
	my ($dbhA,$server_ip) = @_;

	my @db_trunks;
	my @db_clients;

	my $stmtA = "SELECT channel,extension FROM live_channels where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

	while ( $hash_ref = $sthA->fetchrow_hashref )
		{
		push( @db_trunks, $hash_ref );
		}

	my $stmtA = "SELECT channel,extension FROM live_sip_channels where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	while ( $hash_ref = $sthA->fetchrow_hashref )
		{
		push( @db_clients, $hash_ref );
		}
	
	return (\@db_trunks, \@db_clients);
	}


# function to validate whether a CID Name is a Vicidial Call ID that we care about
sub validate_cid_name
	{
	my ($cid_name) = @_;

	# check if it is a valid CID Name
	if (
		( $cid_name =~ /DC\d\d\d\d\d\dW\d\d\d\d\d\d\d\d\d\dW/ ) ||	# 3way transfers
		( $cid_name =~ /M\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Manual Dials
		( $cid_name =~ /V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Auto Dials
		( $cid_name =~ /Y\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Inbound Calls
		( $cid_name =~ /^RINGAGENT|^RA_/ )
	) 
		{
		return 1; # if so return 1 for true
		} 
	else 
		{
		return 0; # if not return 0 for false
		}

	}

# function to return the valid callid
sub get_valid_callid
	{
	my ( $CallerIDName, $ConnectedLineName ) = @_;

	# remove everything after the space for Orex
	if ( $CallerIDName =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$CallerIDName =~ s/ .*//gi;}
	if ( $ConnectedLineName =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$ConnectedLineName =~ s/ .*//gi;}
	
	# if the CallerIDName variable does not have a valid Vicidial Call ID
	# but the ConnectedLineName does use the ConnectedLineName
	if (  !(validate_cid_name($CallerIDName)) && (validate_cid_name($ConnectedLineName)) )
		{
		return $ConnectedLineName;
		}
	else
		{
		return $CallerIDName;
		}
	}


sub event_logger 
	{
	my ($SYSLOG,$event_string) = @_;
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$PATHlogs/update.$action_log_date")
				|| die "Can't open $PATHlogs/update.$action_log_date: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}



# subroutine to parse the asterisk version
# and return a hash with the various part
sub parse_asterisk_version
	{
	# grab the arguments
	my $ast_ver_str = $_[0];

	# get everything after the - and put it in $ast_ver_postfix
	my @hyphen_parts = split( /-/ , $ast_ver_str );

	my $ast_ver_postfix = $hyphen_parts[1];

	# now split everything before the - up by the .
	my @dot_parts = split( /\./ , $hyphen_parts[0] );

	my %ast_ver_hash;

	if ( $dot_parts[0] <= 1 )
		{
			%ast_ver_hash = (
				"major" => $dot_parts[0],
				"minor" => $dot_parts[1],
				"build" => $dot_parts[2],
				"revision" => $dot_parts[3],
				"postfix" => $ast_ver_postfix
			);
		}

	# digium dropped the 1 from asterisk 10 but we still need it
	if ( $dot_parts[0] > 1 )
		{
			%ast_ver_hash = (
				"major" => 1,
				"minor" => $dot_parts[0],
				"build" => $dot_parts[1],
				"revision" => $dot_parts[2],
				"postfix" => $ast_ver_postfix
			);
		}

	return ( %ast_ver_hash );
	}

sub get_time_now	#get the current date and time and epoch for logging call lengths and datetimes
	{
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	$now_date_epoch = time();
	$now_date = "$year-$mon-$mday $hour:$min:$sec";
	$action_log_date = "$year-$mon-$mday";
	}


# try to load a module
sub try_load 
	{
	 my $mod = shift;

	eval("use $mod");

	if ($@) 
		{
		#print "\$@ = $@\n";
		return(0);
		}
	else 
		{
		return(1);
		}
	}


sub print_now 
	{
	my $string = shift;

	# get the current time in microseconds
	my ( $now_sec, $now_micro_sec ) = gettimeofday();

	# figure out how many micro seconds since epoch
	my $now_micro_epoch = $now_sec * 1000000;
	$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

	print "\n$string|$now_micro_epoch\n\n";
	}

sub get_cpu_percent
	{
	# stat file in proc
	$stat = '/proc/stat';
	
	# read it
	open( FH , "<$stat" );
	@lines = <FH>;
	close( FH );

	# get the various times spent doing stuff ( $cpu is useless )
	($cpu, $user, $nice, $system, $idle, $iowait, $irq, $softirq, $steal, $guest, $guest_nice ) = split( ' ', $lines[0] );

	# total the different catagories of time
	$cpu_user = $user + $nice;
	$cpu_idle = $idle + $iowait;
	$cpu_sys = $system + $irq + $softirq;
	$cpu_vm = $steal + $guest + $guest_nice;

	# check if we have previous values
	if ( defined ( $prev_user ) ) 
		{
		# get the differential from last time
		$cpu_user_diff = $cpu_user - $prev_cpu_user;
		$cpu_idle_diff = $cpu_idle - $prev_cpu_idle;
		$cpu_sys_diff = $cpu_sys - $prev_cpu_sys;
		$cpu_vm_diff = $cpu_vm - $prev_cpu_vm;

		$user_diff = $user - $prev_user;
		$nice_diff = $nice - $prev_nice;
		$system_diff = $system - $prev_system;
		$idle_diff = $idle - $prev_idle;
		$iowait_diff = $iowait - $prev_iowait;
		$irq_diff = $irq - $prev_irq;
		$softirq_diff = $softirq - $prev_softirq;
		$steal_diff = $steal - $prev_steal;
		$guest_diff = $guest - $prev_guest;
		$guest_nice_diff = $guest_nice - $prev_guest_nice;

		# total the differentials
		$cpu_total_diff = $cpu_user_diff + $cpu_idle_diff + $cpu_sys_diff + $cpu_vm_diff;

		$cpu_user_percent  = sprintf("%.0f", (( $cpu_user_diff / $cpu_total_diff ) * 100 ));
		$cpu_idle_percent  = sprintf("%.0f", (( $cpu_idle_diff / $cpu_total_diff ) * 100 ));
		$cpu_sys_percent   = sprintf("%.0f", (( $cpu_sys_diff / $cpu_total_diff ) * 100 ));
		$cpu_vm_percent    = sprintf("%.0f", (( $cpu_vm_diff / $cpu_total_diff ) * 100 ));
		}
	else 
		{
		# first time runnings so there are no previous values.
		# all we can do is base it off the system totals since boot.
		$cpu_total = $cpu_user + $cpu_idle + $cpu_sys + $cpu_vm;

		$cpu_user_percent  = sprintf("%.0f", (( $cpu_user / $cpu_total ) * 100 ));
		$cpu_idle_percent  = sprintf("%.0f", (( $cpu_idle / $cpu_total ) * 100 ));
		$cpu_sys_percent   = sprintf("%.0f", (( $cpu_sys / $cpu_total ) * 100 ));
		$cpu_vm_percent    = sprintf("%.0f", (( $cpu_vm / $cpu_total ) * 100 ));		
		}
	
	# store the values for the next call
	$prev_user = $user;
	$prev_nice = $nice;
	$prev_system = $system;
	$prev_idle = $idle;
	$prev_iowait = $iowait;
	$prev_irq = $irq;
	$prev_softirq = $softirq;
	$prev_steal = $steal;
	$prev_guest = $guest;
	$prev_guest_nice = $guest_nice;
	$prev_cpu_user = $cpu_user;
	$prev_cpu_idle = $cpu_idle;
	$prev_cpu_sys = $cpu_sys;
	$prev_cpu_vm = $cpu_vm;


	return ( 
		$cpu_user_percent, 
		$cpu_idle_percent, 
		$cpu_sys_percent, 
		$cpu_vm_percent, 
		$user_diff, 
		$nice_diff, 
		$system_diff, 
		$idle_diff, 
		$iowait_diff, 
		$irq_diff, 
		$softirq_diff, 
		$steal_diff, 
		$guest_diff, 
		$guest_nice_diff 
	);
	}

sub get_cpu_load
	{
	$lavg = '/proc/loadavg';
	
	open( FH , "<$lavg" );
	@lines = <FH>;
	close( FH );

	$server_load = $lines[0];
	$server_load =~ s/ .*//gi;
	$server_load =~ s/\D//gi;
	
	return $server_load;
	}

sub get_mem_usage
	{
	$meminfo = '/proc/meminfo';
	
	open( FH , "<$meminfo" );
	@lines = <FH>;
	close( FH );
	
	$mem_total = $lines[0];
	$mem_total =~ s/MemTotal: *//g;
	
	$mem_free = $lines[1];
	$mem_free =~ s/MemFree: *//g;

	$mem_used = $mem_total - $mem_free;

	$mem_used = floor( $mem_used / 1024 );
	$mem_total = floor( $mem_total / 1024 );
	$mem_free = floor( $mem_free / 1023 );

	return ( $mem_total, $mem_used, $mem_free );

	}

sub get_disk_space
	{
	@serverDISK = `$dfbin -B 1048576 -x nfs -x cifs -x sshfs -x ftpfs`;
	$ct=0;
	$ct_PCT=0;
	$disk_usage = '';
	foreach(@serverDISK)
		{
		if ($serverDISK[$ct] =~ /(\d+\%)/)
			{
			$ct_PCT++;
			$usage = $1;
			$usage =~ s/\%//gi;
			$disk_usage .= "$ct_PCT $usage|";
			}
		$ct++;
		}

	return $disk_usage;
	}

sub get_num_processes
	{
	$num_processes = 0;

	opendir( DH, '/proc');
	while ( readdir( DH ) )
		{
		if ( looks_like_number( $_ ) ) { $num_processes++; }
		}
	closedir( DH );

	return ($num_processes);
	}


sub get_disk_rw
	{
	$total_reads = 0;
	$total_writes = 0;

	$diskstats = "/proc/diskstats";

	open( FH , "<$diskstats" );
	@lines = <FH>;
	close( FH );

	foreach $line ( @lines )
		{
		# remove excess white space
		$line =~ s/\h+/ /g;
		$line =~ s/^\s+|\s+$//g;

		# split the line
		(
			$major,
			$minor,
			$device,
			$reads_completed,
			$reads_merged,
			$sectors_read,
			$time_reading,
			$writes_completed,
			$writes_merged,
			$sectors_written,
			$time_writing,
			$current_ios,
			$ios_time,
			$weighted_ios_time
		) =   split( ' ', $line );

		# check if device is a drive or partition or raid
		if ( $device !~ /\d/ )
			{
			$total_reads += $sectors_read;
			$total_writes += $sectors_written;
			}
		}

	# check if we previously got the total reads
	if ( defined ( $prev_total_reads ) )
		{
		$reads = $total_reads - $prev_total_reads;
		$writes = $total_writes - $prev_total_writes;
		}
	else
		{
		# cannot figure out the difference
		# if we don't know what it was before
		$reads = 0;
		$writes = 0;
		}

	$prev_total_reads = $total_reads;
	$prev_total_writes = $total_writes;

	return ( $reads, $writes );
	}
