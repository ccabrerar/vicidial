#!/usr/bin/perl
#
# AST_CRON_audio_2_encrypt.pl
#
# This is a STEP-2 program in the audio archival process(should happen AFTER any compression)
#
# runs every 3 minutes and encrypts the recording files to GPG format by default
# 
# put an entry into the cron of of your asterisk machine to run this script 
# every 3 minutes or however often you desire
#
# You MUST define the type of audio file that this process will pull from: WAV, GSM, MP3, OGG, GSW
#
# ### recording mixing/compressing/encrypting/ftping scripts
##0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_mix.pl
# 0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_VDonly.pl
# 1,4,7,10,13,16,19,22,25,28,31,34,37,40,43,46,49,52,55,58 * * * * /usr/share/astguiclient/AST_CRON_audio_2_compress.pl --GSM
# 2,5,8,11,14,17,20,23,26,29,32,35,38,41,44,47,50,53,56,59 * * * * /usr/share/astguiclient/AST_CRON_audio_2_encrypt.pl --GPG --GSM --recipients=gpg@vicidial.com
# 0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_3_ftp.pl --GPG
#
# FLAGS FOR ENCRYPTION OPTIONS
# --GPG = GnuPG encryption(assumes recipient public keys are loaded on server)
#
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 150911-1814 - First Build based upon 110524-1059 AST_CRON_audio_2_compress.pl
# 160523-0651 - Added --HTTPS option to use https instead of http in local location
#

$WAV=0;   $GSM=0;   $MP3=0;   $OGG=0;   $GSW=0;
$GPG=0;
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
		print "  [--help] = this screen\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [-t] = test\n";
		print "  [--GPG] = encrypt into GPG codec\n";
		print "  [--WAV] = look for uncompressed WAV files\n";
		print "  [--GSM] = look for already compressed GSM files\n";
		print "  [--MP3] = look for already compressed MPEG-Layer-3 files\n";
		print "  [--OGG] = look for already compressed OGG Vorbis files\n";
		print "  [--GSW] = look for already compressed GSM codec with RIFF headers and .wav extension files\n";
		print "  [--HTTPS] = use https instead of http in local location\n";
		print "  [--run-check] = concurrency check, die if another instance is running\n";
		print "  [--max-files=x] = maximum number of files to process, defaults to 100000\n";
		print "  [--recipients=x\@y.com---z\@a.net] = encryption recipients addresses, REQUIRED\n";
		print "    NOTE: all recipients must be loaded into keys on local server and accessible to gpg binary\n";
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
		if ($args =~ /--recipients=/i)
			{
			@data_in = split(/--recipients=/,$args);
			$CLIrecipient = $data_in[1];
			$CLIrecipient =~ s/ .*$//gi;
			if ($DB) {print "\n----- RECIPIENTS: $CLIrecipient -----\n\n";}
			if ($CLIrecipient =~ /---/)
				{
				$CLIrecipient =~ s/---/ --recipient /gi;
				}
			}
		else
			{print "ERROR: recipient is required, exiting...";   exit;}
		if ($args =~ /--max-files=/i)
			{
			@data_in = split(/--max-files=/,$args);
			$maxfiles = $data_in[1];
			$maxfiles =~ s/ .*$//gi;
			$maxfiles = ($maxfiles + 2); # account for directory lists
			}
		else
			{$maxfiles = 100000;}
		if ($DB) {print "\n----- MAX FILES: $maxfiles -----\n\n";}
		if ($args =~ /--GPG/i)
			{
			$GPG=1;
			if ($DB) {print "GPG encryption\n";}
			}
		else
			{
			$GPG=1;
			if ($DB) {print "Defaulting to use GPG encryption\n";}
			}
		if ($args =~ /--HTTPS/i)
			{
			$HTTPS=1;
			if ($DB) {print "HTTPS location option enabled\n";}
			}
		if ($args =~ /--WAV/i)
			{
			$WAV=1;
			if ($DB) {print "WAV compression\n";}
			}
		else
			{
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
						else
							{
							print "ERROR: no audio format defined, exiting...";   exit;
							}
						}
					}
				}
			}
		}
	}
else
	{
	print "ERROR: cannot run script without any command line options, exiting...";   exit;
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


if ($GPG > 0)
	{
	### find GnuPG encryption binary to do the encryption
	$gpgbin = '';
	if ( -e ('/usr/bin/gpg')) {$gpgbin = '/usr/bin/gpg';}
	else 
		{
		if ( -e ('/usr/local/bin/gpg')) {$gpgbin = '/usr/local/bin/gpg';}
		else
			{
			print "Can't find gpg binary! Exiting...\n";
			exit;
			}
		}
	}

### directory where -all recordings are
if ($WAV > 0) {$dir2 = "$PATHDONEmonitor";}
if ($MP3 > 0) {$dir2 = "$PATHDONEmonitor/MP3";}
if ($GSM > 0) {$dir2 = "$PATHDONEmonitor/GSM";}
if ($OGG > 0) {$dir2 = "$PATHDONEmonitor/OGG";}
if ($GSW > 0) {$dir2 = "$PATHDONEmonitor/GSW";}

if ($DBX > 0)
	{
	print "GPG: $gpgbin    DIR: $dir2\n";
	}

if (!-e "$PATHDONEmonitor/GPG") 
	{
	print "Creating directory: $PATHDONEmonitor/GPG \n";
	`mkdir -p $PATHDONEmonitor/GPG`;
	}

opendir(FILE, "$dir2/");
@FILES = readdir(FILE);


### Loop through files first to gather filesizes
$i=0;
foreach(@FILES)
	{
	$FILEsize1[$i] = 0;
	if ( (length($FILES[$i]) > 4) && (!-d "$dir2/$FILES[$i]") )
		{
		$FILEsize1[$i] = (-s "$dir2/$FILES[$i]");
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

	if ( (length($FILES[$i]) > 4) && (!-d "$dir2/$FILES[$i]") && ($maxfiles > $i) )
		{
		$FILEsize2[$i] = (-s "$dir2/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize2[$i]\n\n";}

		if ( ($FILES[$i] !~ /out\.|in\.|lost\+found/i) && ($FILEsize1[$i] eq $FILEsize2[$i]) && (length($FILES[$i]) > 4))
			{
			$recording_id='';
			$ALLfile = $FILES[$i];
			$SQLFILE = $FILES[$i];
			$SQLFILE =~ s/-all\.wav|-all\.gsm|-all\.mp3|-all\.gsw|-all\.ogg//gi;

			$stmtA = "select recording_id from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$recording_id =	"$aryA[0]";
				}
			$sthA->finish();

			$HTTP='http';
			if ($HTTPS > 0) {$HTTP='https';}

			if ($GPG > 0)
				{
				$GPGfile = $FILES[$i];
				$GPGfile =~ s/-all\.wav/-all.wav.gpg/gi;
				$GPGfile =~ s/-all\.gsm/-all.gsm.gpg/gi;
				$GPGfile =~ s/-all\.mp3/-all.mp3.gpg/gi;
				$GPGfile =~ s/-all\.gsw/-all.gsw.gpg/gi;
				$GPGfile =~ s/-all\.ogg/-all.ogg.gpg/gi;

				if ($DB) {print "$i|$recording_id|$ALLfile|$GPGfile|     |$SQLfile|\n";}

				`$gpgbin --trust-model always --output $PATHDONEmonitor/GPG/$GPGfile --encrypt --recipient $CLIrecipient $dir2/$ALLfile`;

				$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/GPG/$GPGfile' where recording_id='$recording_id';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				}


			if (!$T)
				{
				`rm -f "$dir2/$ALLfile"`;
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
