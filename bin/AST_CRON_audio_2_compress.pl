#!/usr/bin/perl
#
# AST_CRON_audio_2_compress.pl
#
# This is a STEP-2 program in the audio archival process
#
# runs every 3 minutes and compresses the -all recording files to GSM format
# 
# put an entry into the cron of of your asterisk machine to run this script 
# every 3 minutes or however often you desire
#
# ### recording mixing/compressing/ftping scripts
##0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_mix.pl
# 0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_VDonly.pl
# 1,4,7,10,13,16,19,22,25,28,31,34,37,40,43,46,49,52,55,58 * * * * /usr/share/astguiclient/AST_CRON_audio_2_compress.pl --GSM
# 2,5,8,11,14,17,20,23,26,29,32,35,38,41,44,47,50,53,56,59 * * * * /usr/share/astguiclient/AST_CRON_audio_3_ftp.pl --GSM
#
# FLAGS FOR COMPRESSION CODECS
# --GSM = GSM 6.10 codec
# --MP3 = MPEG Layer3 codec
# --OGG = OGG Vorbis codec
# --GSW = GSM 6.10 codec with RIFF headers (.wav extension)
#
# make sure that the following directories exist:
# /var/spool/asterisk/monitorDONE	# where the mixed -all files are put
# 
# NOTE: If the "--file-sorting" flag is use, the step 3 FTP archiving scripts cannot be used.
#
# This program assumes that recordings are saved by Asterisk as .wav
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 80302-1958 - First Build
# 80731-2253 - Changed size comparisons for more efficiency
# 90727-1417 - Added GSW format option
# 101207-1024 - Change to GSW option because of SoX flag changes in 14.3.0
# 110524-1059 - Added run-check concurrency check option
# 160523-0652 - Added --HTTPS option to use https instead of http in local location
# 170212-0732 - Added --file-sorting option to put files into dated directories (THIS WILL NOT ALLOW FTP ARCHIVING)
#

$GSM=0;   $MP3=0;   $OGG=0;   $GSW=0;
$HTTPS=0;
$maxfiles = 100000;
$location='';

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
		print "  [-t] = test\n";
		print "  [--GSM] = compress into GSM codec\n";
		print "  [--MP3] = compress into MPEG-Layer-3 codec\n";
		print "  [--OGG] = compress into OGG Vorbis codec\n";
		print "  [--GSW] = compress into GSM codec with RIFF headers and .wav extension\n";
		print "  [--HTTPS] = use https instead of http in local location\n";
		print "  [--run-check] = concurrency check, die if another instance is running\n";
		print "  [--max-files=x] = maximum number of files to process, default is 100000\n";
		print "  [--file-sorting] = sort files into subfolders in YYYY-MM-DD format (THIS WILL NOT ALLOW FTP ARCHIVING)\n";
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
		if ($args =~ /--run-check/i)
			{
			$run_check=1;
			if ($DB) {print "\n----- CONCURRENCY CHECK -----\n\n";}
			}
		if ($args =~ /--max-files=/i)
			{
			@data_in = split(/--max-files=/,$args);
			$maxfiles = $data_in[1];
			$maxfiles =~ s/ .*$//gi;
			}
		if ($args =~ /--file-sorting/i)
			{
			$FILESORT=1;
			if ($DB) {print "Dated file sorting option enabled\n";}
			}
		if ($args =~ /--HTTPS/i)
			{
			$HTTPS=1;
			if ($DB) {print "HTTPS location option enabled\n";}
			}
		if ($args =~ /--GSM/i)
			{
			$GSM=1;
			if ($DB) {print "GSM compression\n";}
			}
		else
			{
			if ($args =~ /--MP3/i)
				{
				$MP3=1;
				if ($DB) {print "MP3 compression\n";}
				}
			else
				{
				if ($args =~ /--OGG/i)
					{
					$OGG=1;
					if ($DB) {print "OGG compression\n";}
					}
				else
					{
					if ($args =~ /--GSW/i)
						{
						$GSW=1;
						if ($DB) {print "GSW compression\n";}
						}
					}
				}
			}
		}
	}
else
	{
	#print "no command line options set\n";
	$GSM=1;
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

### concurrency check
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 1) 
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		exit;
		}
	}


# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


if ( ($GSM > 0) || ($OGG > 0) || ($GSW > 0) )
	{
	### find sox binary to do the compression
	$soxbin = '';
	if ( -e ('/usr/bin/sox')) {$soxbin = '/usr/bin/sox';}
	else 
		{
		if ( -e ('/usr/local/bin/sox')) {$soxbin = '/usr/local/bin/sox';}
		else
			{
			print "Can't find sox binary! Exiting...\n";
			exit;
			}
		}
	}

if ($MP3 > 0)
	{
	### find lame mp3 encoder binary to do the compression
	$lamebin = '';
	if ( -e ('/usr/bin/lame')) {$lamebin = '/usr/bin/lame';}
	else 
		{
		if ( -e ('/usr/local/bin/lame')) {$lamebin = '/usr/local/bin/lame';}
		else
			{
			print "Can't find lame binary! Exiting...\n";
			exit;
			}
		}
	}

if ($DBX > 0)
	{
	print "SOX: $soxbin    LAME: $lamebin\n";
	}

### directory where -all recordings are
$dir2 = "$PATHDONEmonitor";

opendir(FILE, "$dir2/");
@FILES = readdir(FILE);


### Loop through files first to gather filesizes
$i=0;
foreach(@FILES)
	{
	$FILEsize1[$i] = 0;
	if ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") )
		{
		$FILEsize1[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize1[$i]\n";}
		}
	$i++;
	}

sleep(5);


### Loop through files a second time to gather filesizes again 5 seconds later
$i=0;
foreach(@FILES)
	{
	$FILEsize2[$i] = 0;

	if ( ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") ) && ($maxfiles > $i) )
		{
		$FILEsize2[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize2[$i]\n\n";}

		if ( ($FILES[$i] !~ /out\.|in\.|lost\+found/i) && ($FILEsize1[$i] eq $FILEsize2[$i]) && (length($FILES[$i]) > 4))
			{
			$recording_id='';
			$ALLfile = $FILES[$i];
			$SQLFILE = $FILES[$i];
			$SQLFILE =~ s/-all\.wav|-all\.gsm//gi;

			$stmtA = "select recording_id, LEFT(start_time,10) AS file_date from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$recording_id =	"$aryA[0]";
				$file_date =	"$aryA[1]";
				}
			$sthA->finish();

			$HTTP='http';
			if ($HTTPS > 0) {$HTTP='https';}

			### In case --file-sorting is ON, we check if we need to create the location
			if ($MP3 > 0)
				{$location = "MP3/";}
			if ($GSM > 0)
				{$location = "GSM/";}
			if ($OGG > 0)
				{$location = "OGG/";}
			if ($GSW > 0)
				{$location = "GSW/";}
			### We try to create the destination folder (in case it doesn't exist). If we fail, we die
			if ($FILESORT) {$location = "$location$file_date/";}
			if (! -d "$dir2/$location") {mkdir "$dir2/$location";}
			if (! -d "$dir2/$location") {die("Cannot create $dir2/$location");}
				
			
			if ($GSM > 0)
				{
				$GSMfile = $FILES[$i];
				$GSMfile =~ s/-all\.wav/-all.gsm/gi;

				if ($DB) {print "|$recording_id|$ALLfile|$dir2/$location$GSMfile|     |$SQLfile|\n";}

				`$soxbin "$dir2/$ALLfile" "$dir2/$location$GSMfile"`;

				$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/$location$GSMfile' where recording_id='$recording_id';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				}

			if ($OGG > 0)
				{
				$OGGfile = $FILES[$i];
				$OGGfile =~ s/-all\.wav/-all.ogg/gi;

				if ($DB) {print "|$recording_id|$ALLfile|$dir2/$location$OGGfile|     |$SQLfile|\n";}

				`$soxbin "$dir2/$ALLfile" "$dir2/$location$OGGfile"`;

				$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/$location$OGGfile' where recording_id='$recording_id';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				}

			if ($MP3 > 0)
				{
				$MP3file = $FILES[$i];
				$MP3file =~ s/-all\.wav/-all.mp3/gi;

				if ($DB) {print "|$recording_id|$ALLfile|$dir2/$location$MP3file|     |$SQLfile|\n";}

				`$lamebin -b 16 -m m --silent "$dir2/$ALLfile" "$dir2/$location$MP3file"`;

				$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/$location$MP3file' where recording_id='$recording_id';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				}

			if ($GSW > 0)
				{
				$GSWfile = $FILES[$i];

				if ($DB) {print "|$recording_id|$ALLfile|$GSWfile|     |$SQLfile|$soxbin \"$dir2/$ALLfile\" -e gsm-full-rate \"$dir2/$location$GSWfile\"|\n";}

				# for SoX versions before 14.3.X
			#	`$soxbin "$dir2/$ALLfile" -g -b "$dir2/GSW/$GSWfile"`;
				# for SoX versions 14.3.0 and after
				`$soxbin "$dir2/$ALLfile" -e gsm-full-rate "$dir2/$location$GSWfile"`;

				$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/$location$GSWfile' where recording_id='$recording_id';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				}


			if (!$T)
				{
				if (! -d "$dir2/ORIG/$file_date") {mkdir "$dir2/ORIG/$file_date";}
				if (! -d "$dir2/ORIG/$file_date") {die("Cannot create $dir2/ORIG/$file_date");}
				`mv -f "$dir2/$ALLfile" "$dir2/ORIG/$file_date/$ALLfile"`;
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
