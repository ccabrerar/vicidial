#!/usr/bin/perl
#
# AST_rec_monitor.pl version 2.14
#
# DESCRIPTION:
# Monitors the recording directory and logs to a file when  a file is create or deleted
#
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG:
# 230919-1309 - Initial Build
#

$sleepms=1000;
$DB=0;
$DBX=0;
$quiet=0;

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
		print "  [--help] = this screen\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--sleepms=XXX] = how many milliseconds to sleep between loops\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag, set to 0 for no debug messages
			print "\n-- DEBUG --\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DB=1;
			$DBX=1; # Debug flag, set to 0 for no debug messages
			print "\n-- SUPER DEBUG --\n\n";
			}
		if ($args =~ /--quiet/i)
			{
			$quiet=1; # Debug flag, set to 0 for no debug messages
			$DB=0;
			$DBX=0;
			}
		if ($args =~ /--sleepms=/i)
			{
			@data_in = split(/--sleepms=/,$args);
			@data_in2 = split(/ /,$data_in[1]);
			$sleepms = @data_in2[0];
			print "\n-- sleep = $sleepms ms --\n\n";
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###


# default path to astguiclient configuration file:
$PATHconf =	'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHhome/) && ($CLIhome < 1) )
		{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^PATHagi/) && ($CLIagi < 1) )
		{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ( ($line =~ /^PATHweb/) && ($CLIweb < 1) )
		{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ( ($line =~ /^PATHsounds/) && ($CLIsounds < 1) )
		{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ( ($line =~ /^PATHmonitor/) && ($CLImonitor < 1) )
		{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ( ($line =~ /^VARserver_ip/) && ($CLIserver_ip < 1) )
		{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_server/) && ($CLIDB_server < 1) )
		{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_database/) && ($CLIDB_database < 1) )
		{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_user/) && ($CLIDB_user < 1) )
		{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_pass/) && ($CLIDB_pass < 1) )
		{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_port/) && ($CLIDB_port < 1) )
		{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

use Term::ANSIColor;
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use Time::Piece;


$RecLogFile = "$PATHlogs/rec_log.$year-$mon-$mday";

# hold all the files we are actively monitoring
@active_files = ();

# directory paths to monitor
@paths = ( "$PATHmonitor", "$PATHmonitor/MIX", "$PATHmonitor/STEREO" );

# files / directories to ignore
@ignores = ( "MIX", "STEREO", ".", ".." );


$startup = 1;


while(1) {
	($sec,$usec) = gettimeofday();

	$time_str = sprintf '%s.%06d', localtime($sec)->strftime('%Y-%m-%d %H:%M:%S'), $usec;

	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($sec);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}



	$RecLogFile = "$PATHlogs/rec_log.$year-$mon-$mday";

	open( RLFH, '>>', $RecLogFile) or die $!;

	my $cli_string = "";


	if ( $startup )
		{
		$log_string = "$time_str :\tRecording Monitoring Starting\n";

		$cli_string = $cli_string.$log_string;

		print RLFH $log_string;
		}


	# holds all the files listed on this loop with their absolute path
	my @all_files = ();

	foreach( @paths )
		{
		$path = $_;

		# make sure the path exists
		if ( -d "$path" ) 
			{
			# open the directory for reading
			opendir my $dir, "$path" or die "Cannot open directory: $!";

			# read the files
			my @list_files = readdir $dir;

			# close the directory
			closedir $dir;

			foreach ( @list_files )
				{
				$list_file = $_;
				if ( grep( /^$list_file$/, @ignores ) )
					{
					if ( $DBX ) { print "ignoring file $list_file\n"; }
					}
				else
					{
					$abs_file = "$path/$list_file";
					if ( $DBX ) { print "$abs_file\n"; }

					# add the absolute path to the file to the @all_files array
					push ( @all_files, $abs_file );
					}
				}
			}
		}

	# find all the new files
	foreach( @all_files )
		{
		$allfile = $_;

		if ( grep( /^$allfile$/, @active_files ) )
			{
			if ( $DB ) { print "$allfile already in active_files. Do nothing.\n"; }
			}
		else
			{
			if ( $DB ) { print "$allfile not in active_files. Add it and log it.\n"; }

			push( @active_files, $allfile );

			if ( $startup )
				{
				$log_string = "$time_str :\texisting recording detected\t$allfile\n";
				}
			else
				{
				$log_string = "$time_str :\tnew recording detected\t$allfile\n";
				}


			$cli_string = $cli_string.$log_string;

			print RLFH $log_string;

			}
		}


	@new_active_files = ();

	# array of the index positions of the files to delete
	my @del_index_arr = ();
	foreach( @active_files )
		{
		$activefile = $_;

		if ( grep( /^$activefile$/, @all_files ) )
			{
			if ( $DB ) { print "$activefile is in all_files. So it is still there. Add it to new_active_files.\n"; }
			push( @new_active_files, $activefile );
			}
		else
			{
			if ( $DB ) { print "$activefile is not in all_files. It has been removed from active_files and log it.\n"; }

			push( @del_index_arr, $arr_index );


			$sus = "";
			if ( $sec > 20 ) { $sus = "suspicious"; }

			$log_string = "$time_str :\told recording removed\t$activefile\t$sus\n";

			$cli_string = $cli_string.$log_string;
		       
			print RLFH $log_string;
			}
		}

	# switch to the new active file list
	@active_files = @new_active_files;


	if ( ! $quiet )
		{
		print color('bold blue');
		print "$cli_string";
		print color('reset');
		}


	close( RLFH );

	if ( $startup ) { $startup = 0 };

	### sleep for sleepms milliseconds and do it all over again 
	usleep(1*$sleepms*1000);
}


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;
