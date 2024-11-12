#!/usr/bin/perl
#
# AST_CRON_audio_1_gateway_stereo.pl
#
# This is a STEP-1 program in the audio archival process
#
# runs every 3 minutes and copies and mixes the -in and -out recordings in the 
# monitor/STEREO to the DONE directory for further processing. Very important for 
# RAM-drive usage
# 
# put an entry into the cron of of your asterisk machine to run this script 
# every 3 minutes or however often you desire
#
# ### recording mixing/compressing/ftping scripts
# 0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_gateway_stereo.pl --STEREO
# 2,5,8,11,14,17,20,23,26,29,32,35,38,41,44,47,50,53,56,59 * * * * /usr/share/astguiclient/AST_CRON_audio_3_ftp.pl --WAV
#
# make sure that the following directories exist:
# /var/spool/asterisk/monitor		# default Asterisk recording directory
# /var/spool/asterisk/monitorDONE	# where the merged audio files are put
# 
# This program assumes that recordings are saved by Asterisk as .wav
# should be easy to change this code if you use .gsm instead
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 230131-2244 - First Build, based on AST_CRON_audio_1_move_mix.pl script
# 231019-2204 - Changed sleep time between directory scans from 5 to 15 seconds
#

$MIX=0;
$STEREO=0;
$HTTPS=0;

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
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [-t] = test\n";
		print "  [--MIX] = mix audio files in MIX directory\n";
		print "  [--STEREO] = mix audio files in STEREO directory\n";
		print "  [--HTTPS] = use https instead of http in local location\n";
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
		if ($args =~ /-t/i)
			{
			$T=1;   $TEST=1;
			print "\n-----TESTING -----\n\n";
			}
		if ($args =~ /--MIX/i)
			{
			$MIX=1;
			if ($DB) {print "MIX directory audio processing only\n";}
			}
		if ($args =~ /--STEREO/i)
			{
			$STEREO=1;
			if ($DB) {print "STEREO directory audio processing only\n";}
			}
		if ($args =~ /--HTTPS/i)
			{
			$HTTPS=1;
			if ($DB) {print "HTTPS location option enabled\n";}
			}
		}
	}
else
	{
	#print "no command line options set\n";
	}


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

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### find soxmix or sox to do the mixing
$soxmixbin = '';
if ( -e ('/usr/bin/soxmix')) {$soxmixbin = '/usr/bin/soxmix';}
else 
	{
	if ( -e ('/usr/local/bin/soxmix')) {$soxmixbin = '/usr/local/bin/soxmix';}
	else
		{
		if ($DB) {print "Can't find soxmix binary! Trying sox...\n";}
		if ( -e ('/usr/bin/sox')) {$soxmixbin = '/usr/bin/sox -m';}
		else 
			{
			if ( -e ('/usr/local/bin/sox')) {$soxmixbin = '/usr/local/bin/sox -m';}
			else
				{
				print "Can't find sox binary! Exiting...\n";
				exit;
				}
			}
		}
	}

### find soxi to gather the length info if needed
$soxibin = '';
if ( -e ('/usr/bin/soxi')) {$soxibin = '/usr/bin/soxi';}
else 
	{
	if ( -e ('/usr/local/bin/soxi')) {$soxibin = '/usr/local/bin/soxi';}
	else
		{
		if ( -e ('/usr/sbin/soxi')) {$soxibin = '/usr/sbin/soxi';}
		else 
			{
			if ($DB) {print "Can't find soxi binary! No length calculations will be available...\n";}
			}
		}
	}

### directory where in/out recordings are saved to by Asterisk
$dir1 = "$PATHmonitor";
$dir2 = "$PATHDONEmonitor";
$STEREOarch='';

if ($MIX > 0)
	{$dir1 = "$PATHmonitor/MIX";}
if ($STEREO > 0)
	{
	$dir1 = "$PATHmonitor/STEREO";
	$dir2 = "$PATHDONEmonitor";
#	$STEREOarch='/STEREO';
	}

opendir(FILE, "$dir1/");
@FILES = readdir(FILE);


### Loop through files first to gather filesizes
$i=0;
$calls=0;
foreach(@FILES)
	{
	$FILEsize1[$i] = 0;
	if ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") )
		{
		$FILEsize1[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize1[$i]\n";}
		if ($FILES[$i] !~ /^DIALER_/i) {$calls++;}
		}
	$i++;
	}

if ($DB) {print "Total recording files found: $i (calls: $calls)\n";}

sleep(15);


### Loop through files a second time to gather filesizes again 5 seconds later
$i=0;
foreach(@FILES)
	{
	$FILEsize2[$i] = 0;

	if ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") )
		{

		$FILEsize2[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize2[$i]\n\n";}

		if ( ($FILES[$i] !~ /^CARRIER_|lost\+found/i) && ($FILEsize1[$i] eq $FILEsize2[$i]) && (length($FILES[$i]) > 4))
			{
			$INfile = $FILES[$i];
			$OUTfile = $FILES[$i];
			$OUTfile =~ s/DIALER_/CARRIER_/gi;
			$ALLfile = $FILES[$i];
			$ALLfile =~ s/DIALER_//gi;
			$SQLFILE = $FILES[$i];
			$SQLFILE =~ s/DIALER_|\.wav//gi;

			$length_in_sec=0;
			$stmtA = "select recording_id,length_in_sec from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$recording_id =		$aryA[0];
				$length_in_sec =	$aryA[1];
				}
			$sthA->finish();

			if ($DB) {print "|$recording_id|$length_in_sec|$INfile|$OUTfile|     |$ALLfile|\n";}

			`$soxmixbin -M "$dir1/$INfile" "$dir1/$OUTfile" "$dir2/$ALLfile"`;

			$lengthSQL='';
			if ( ( ($length_in_sec < 1) || ($length_in_sec =~ /^NULL$/i) || (length($length_in_sec)<1) ) && (length($soxibin) > 3) )
				{
				@soxi_output = `$soxibin -D $dir2/$ALLfile`;
				$soxi_sec = $soxi_output[0];
				$soxi_sec =~ s/\..*|\n|\r| //gi;
				$soxi_min = ($soxi_sec / 60);
				$soxi_min = sprintf("%.2f", $soxi_min);
				$lengthSQL = ",length_in_sec='$soxi_sec',length_in_min='$soxi_min'";
				}

			$HTTP='http';
			if ($HTTPS > 0) {$HTTP='https';}
			$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS$STEREOarch/$ALLfile' $lengthSQL where recording_id='$recording_id';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

			if (!$T)
				{
				`mv -f "$dir1/$INfile" "$dir2/ORIG/$INfile"`;
				`mv -f "$dir1/$OUTfile" "$dir2/ORIG/$OUTfile"`;
				}

			### sleep for twenty hundredths of a second to not flood the server with disk activity
			usleep(1*200*1000);
			}
		}
	$i++;
	}

if ($DB) {print "DONE... EXITING\n\n";}

$dbhA->disconnect();


exit;
