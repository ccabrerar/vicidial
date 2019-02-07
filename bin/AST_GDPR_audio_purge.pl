#!/usr/bin/perl
#
# AST_GDPR_audio_purge.pl
#
# This script queries the recording log deletion queue and purges any audio recordings
# inserted into this table (done through the GDPR feature in the administrator interface)
# Set to purge nightly, or as often as desired
#
# 1 0 * * * /usr/share/astguiclient/AST_GDPR_audio_purge.pl --recording_dir=/var/spool/asterisk/monitorDONE
#
#
# FLAG FOR RECORDING DIRECTORY ON SERVER
# *** REQUIRED ***
# --recording_dir
#
# FLAG FOR NO DATE DIRECTORY ON SERVER
# --nodatedir
#
# FLAG FOR Y/M/D DATE DIRECTORY ON SERVER
# --YMDdatedir
#
# PERFORM ADDITIONAL DIRECTORY SEARCHES
# --location_search
#
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# 180223-0120 - First Build
# 180228-0200 - Added location_search option


($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
$FTPdate = "$year-$mon-$mday";

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
	if ( ($line =~ /^PATHDONEmonitor/) && ($CLIDONEmonitor < 1) )
		{$PATHDONEmonitor = $line;   $PATHDONEmonitor =~ s/.*=//gi;}
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
	if ( ($line =~ /^VARFTP_host/) && ($CLIFTP_host < 1) )
		{$VARFTP_host = $line;   $VARFTP_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_user/) && ($CLIFTP_user < 1) )
		{$VARFTP_user = $line;   $VARFTP_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_pass/) && ($CLIFTP_pass < 1) )
		{$VARFTP_pass = $line;   $VARFTP_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_port/) && ($CLIFTP_port < 1) )
		{$VARFTP_port = $line;   $VARFTP_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_dir/) && ($CLIFTP_dir < 1) )
		{$VARFTP_dir = $line;   $VARFTP_dir =~ s/.*=//gi;}
	if ( ($line =~ /^VARHTTP_path/) && ($CLIHTTP_path < 1) )
		{$VARHTTP_path = $line;   $VARHTTP_path =~ s/.*=//gi;}
	$i++;
	}

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
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--test] = test\n";
		print "  [--recording_dir] = base directory to look for queued recordings (REQUIRED)\n";
		print "  [--nodatedir] = do not look in dated sub-directories in the base directory\n";
		print "  [--YMDdatedir] = look in Year/Month/Day dated sub-directories in the base directory\n";
		print "  [--location_search] = if recording is not found using command line setting, search\n";
		print "                        using the remaining 2 directory options\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--YMDdatedir/i)
			{
			$YMDdatedir=1;
			if ($DB) {print "\n----- Y/M/D DATED DIRECTORIES -----\n\n";}
			}
        if ($args =~ /--nodatedir/i) 
			{
			$nodatedir=1;
			if ($DB) {print "\n----- NO DATE DIRECTORIES -----\n\n";}
			}
        if ($args =~ /--location_search/i) 
			{
			$location_search=1;
			if ($DB) {print "\n----- SEARCH VIA LOCATION IF NOT FOUND -----\n\n";}
			}
		if ($args =~ /--recording_dir=/i) 
			{
			my @data_in = split(/--recording_dir=/,$args);
			$recording_dir = $data_in[1];
			$recording_dir =~ s/ .*//gi;
			if ($DB > 0) 
				{print "\n----- MAIN RECORDING DIRECTORY: $recording_dir -----\n\n";}
			}
		}
	}

if (!$recording_dir || (!-d "$recording_dir") ) 
	{
	print "Script requires recording directory to be set and that the directory exists.\n";
	exit;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$stmt="select * from recording_log_deletion_queue where date_deleted is null order by date_queued asc";
if ($DBX) {print "\n$stmt\n";}
$rslt=$dbhA->prepare($stmt);
$rslt->execute();
if ($rslt->rows>0) 
	{
	while (@row=$rslt->fetchrow_array) 
		{
		$recording_id=$row[0]; 
		$filename=$row[2];
		$location=$row[3];

		if ($DB) {print " - $filename\n";}

		if ($nodatedir) 
			{
			$start_time_dir="";
			} 
		else 
			{
			$et_stmt="select substr(start_time, 1, 10) from recording_log where recording_id='$recording_id'";
			if ($DBX) {print " - $et_stmt\n";}
			$et_rslt=$dbhB->prepare($et_stmt);
			$et_rslt->execute();
			if ($et_rslt->rows>0) 
				{
				@et_row=$et_rslt->fetchrow_array;
				if (!$YMDdatedir) 
					{
					$start_time_dir="/".$et_row[0];
					}
				else 
					{
					@et_array=split(/\-/, $et_row[0]);
					$start_time_dir="/".$et_array[0]."/".$et_array[1]."/".$et_array[2];
					}
				}
			$et_rslt->finish();
			}

		$filepath=$recording_dir.$start_time_dir."/".$filename."*";
		$filepath=~s/\/+/\//gi;
		if ($DB) {print " -- SEARCHING FOR $filepath\n";}

		@found_files = glob("$filepath");
		if (scalar(@found_files)>0) 
			{
			for ($i=0; $i<scalar(@found_files); $i++) 
				{
				if ($DB) {print " --- $found_files[$i] FOUND, DELETING...\n\n";}
				$upd_stmt="update recording_log_deletion_queue set date_deleted=now() where recording_id='$recording_id'";
				if ($T) 
					{
					if ($DBX) {print "\n$upd_stmt\n";}
					}
				else
					{
					`rm $found_files[$i]`;
					$upd_rslt=$dbhB->prepare($upd_stmt);
					$upd_rslt->execute();
					}
				}
			}
		elsif ($VARFTP_host eq "localhost" && $location_search) 
			{ 
			# Attempt to locate the file locally
			$filepath="";
			$nodatepath=$recording_dir."/".$filename."*";
			if ($DB) {print " --- FILE NOT FOUND, SEARCHING LOCAL SERVER...\n";}
			if ($nodatedir) 
				{
				if ($DB) {print " ---- SEARCHING DATE DIRECTORIES...\n";}
				if ($location=~/((\/20\d{2}-[01]\d-[0-3]\d\/$filename.*)|(\/20\d{2}\/[01]\d\/[0-3]\d\/$filename.*))/) 
					{
					$filepath=$recording_dir.$1;
					}
				}
			elsif ($YMDdatedir) 
				{
				if ($DB) {print " ---- SEARCHING DEFAULT YYYY-MM-DD AND NO-DATE BASE DIRECTORIES...\n";}
				if ($location=~/(\/20\d{2}-[01]\d-[0-3]\d\/$filename.*)/) 
					{
					$filepath=$recording_dir.$1;
					}
				else
					{
					$filepath=$nodatepath;
					}
				}
			else 
				{
				if ($DB) {print " ---- SEARCHING SPLIT YYYY/MM/DD AND NO-DATE BASE DIRECTORIES...\n";}
				if ($location=~/(\/20\d{2}\/[01]\d\/[0-3]\d\/$filename.*)/) 
					{
					$filepath=$recording_dir.$1;
					}
				else
					{
					$filepath=$nodatepath;
					}
				}
			if ($filepath) 
				{
				@found_files = glob("$filepath");
				if (scalar(@found_files)>0) 
					{
					for ($i=0; $i<scalar(@found_files); $i++) 
						{
						if ($DB) {print " ----- $found_files[$i] FOUND, DELETING...\n\n";}
						$upd_stmt="update recording_log_deletion_queue set date_deleted=now() where recording_id='$recording_id'";
						if ($T) 
							{
							if ($DBX) {print "\n$upd_stmt\n";}
							}
						else
							{
							`rm $found_files[$i]`;
							$upd_rslt=$dbhB->prepare($upd_stmt);
							$upd_rslt->execute();
							}
						}
					}
				else 
					{
					if ($DB) {print " ----- LOCATION LOOKUP $filepath FILES NOT FOUND\n\n";}
					}
				}
			else
				{
				if ($DB) {print " ---- LOCATION DIRECTORY NOT FOUND\n\n";}
				}
			}
		else 
			{
			if ($DB) {print " --- $filepath NOT FOUND\n\n";}
			}
		}
	}
$rslt->finish();

$dbhA->disconnect();
$dbhB->disconnect();
