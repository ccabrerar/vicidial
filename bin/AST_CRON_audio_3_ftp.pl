#!/usr/bin/perl
#
# AST_CRON_audio_3_ftp.pl
#
# This is a STEP-3 program in the audio archival process
#
# runs every 3 minutes and copies the recording files to an FTP server
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
# FLAGS FOR COMPRESSION FILES TO TRANSFER
# --GSM = GSM 6.10 files
# --MP3 = MPEG Layer3 files
# --OGG = OGG Vorbis files
# --WAV = WAV files
# --GSW = GSM 6.10 codec with RIFF headers (.wav extension)
# --GPG = GnuPG encrypted audio files
#
# FLAG FOR NO DATE DIRECTORY ON FTP (default is to create YYYYMMDD directory)
# --NODATEDIR
#
# FLAG FOR YYYY/MM/DD DATE DIRECTORIES ON FTP
# --YMDdatedir
#
# FLAG FOR YYYY/YYYYMMDD DATE DIRECTORIES ON FTP
# --YearYMDdatedir
#
# if pinging is not working, try the 'icmp' Ping command in the code instead
# 
# make sure that the following directories exist:
# /var/spool/asterisk/monitorDONE	# where the mixed -all files are put
# 
# This program assumes that recordings are saved by Asterisk as .wav
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 80302-1958 - First Build
# 80317-2349 - Added FTP debug if debugX
# 80731-2253 - Changed size comparisons for more efficiency
# 90727-1458 - Added GSW format option
# 90730-1454 - Fixed non-VicDial recordings date directory, added secondary icmp ping if standard ping fails
# 90818-0953 - Fixed a bug where the filesizes for all files were null
# 90818-1017 - Added a transfer limit and list limit options. 
# 90827-2013 - Fixed list limit option
# 91123-0037 - Added FTP CLI options
# 110524-1049 - Added run-check concurrency check option
# 111128-1615 - Added Ftp persistence option
# 111130-1751 - Added Ftp validate option
# 130116-1536 - Added ftp port CLI flag
# 150911-2336 - Added GPG encrypted audio file compatibility
# 160406-2055 - Added YMDdatedir option
# 180511-2018 - Added --YearYMDdatedir option
# 180616-2248 - Added --localdatedir option
# 230131-2321 - Allowed for handling of stereo gateway recordings
# 231116-0757 - Added --ftp-active option flag for Active (non-Passive) FTP connections
#

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
$FTPdate = "$year-$mon-$mday";

$GSM=0;   $MP3=0;   $OGG=0;   $WAV=0;   $GSW=0;   $GPG=0;   $NODATEDIR=0;   $YMDdatedir=0;

# Default variables for FTP
$VARFTP_host = '10.0.0.4';
$VARFTP_user = 'cron';
$VARFTP_pass = 'test';
$VARFTP_port = '21';
$VARFTP_dir  = 'RECORDINGS';
$VARHTTP_path = 'http://10.0.0.4';

$file_limit = 1000;
$list_limit = 1000;
$FTPpersistent=0;
$FTPvalidate=0;
$FTPpassive=1;

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
		print "  [--transfer-limit=XXX] = number of files to transfer before exiting\n";
		print "  [--list-limit=XXX] = number of files to list in the directory before moving on\n";
		print "  [--GSM] = copy GSM files\n";
		print "  [--MP3] = copy MPEG-Layer-3 files\n";
		print "  [--OGG] = copy OGG Vorbis files\n";
		print "  [--WAV] = copy WAV files\n";
		print "  [--GSW] = copy GSM with RIFF headers and .wav extension files\n";
		print "  [--GPG] = copy GPG encrypted files\n";
		print "  [--nodatedir] = do not put into dated directories\n";
		print "  [--YMDdatedir] = put into Year/Month/Day dated directories\n";
		print "  [--YearYMDdatedir] = put into Year/YYYYMMDD dated directories\n";
		print "  [--localdatedir] = create dated directories inside of FTP directory on local server\n";
		print "  [--run-check] = concurrency check, die if another instance is running\n";
		print "  [--ftp-server=XXX] = FTP server\n";
		print "  [--ftp-port=XXX] = FTP server port\n";
		print "  [--ftp-login=XXX] = FTP server login account\n";
		print "  [--ftp-pass=XXX] = FTP server password\n";
		print "  [--ftp-dir=XXX] = FTP server directory\n";
		print "  [--ftp-persistent] = Does not log out between every file transmission\n";
		print "  [--ftp-validate] = Checks for a file size on the file after transmission\n";
		print "  [--ftp-active] = Use Active (non-Passive) FTP connection\n";
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
		if ($args =~ /--nodatedir/i)
			{
			$NODATEDIR=1;
			if ($DB) {print "\n----- NO DATE DIRECTORIES -----\n\n";}
			}
		if ($args =~ /--YMDdatedir/i)
			{
			$YMDdatedir=1;
			if ($DB) {print "\n----- Y/M/D DATED DIRECTORIES -----\n\n";}
			}
		if ($args =~ /--YearYMDdatedir/i)
			{
			$YearYMDdatedir=1;
			if ($DB) {print "\n----- Year/YYYYMMDD DATED DIRECTORIES -----\n\n";}
			}
		if ($args =~ /--localdatedir/i)
			{
			$localdatedir=1;
			if ($DB) {print "\n----- CREATE LOCAL DATED DIRECTORIES: $localdatedir -----\n\n";}
			}
		if ($args =~ /--run-check/i)
			{
			$run_check=1;
			if ($DB) {print "\n----- CONCURRENCY CHECK -----\n\n";}
			}
		if ($args =~ /--transfer-limit=/i) 
			{
			my @data_in = split(/--transfer-limit=/,$args);
			$file_limit = $data_in[1];
			$file_limit =~ s/ .*//gi;
			print "\n----- FILE TRANSFER LIMIT: $file_limit -----\n\n";
			}
		if ($args =~ /--list-limit=/i) 
			{
			my @data_in = split(/--list-limit=/,$args);
			$list_limit = $data_in[1];
			$list_limit =~ s/ .*//gi;
			print "\n----- FILE LIST LIMIT: $list_limit -----\n\n";
			}
		if ($args =~ /--GSM/i)
			{
			$GSM=1;
			if ($DB) {print "GSM audio files\n";}
			}
		else
			{
			if ($args =~ /--MP3/i)
				{
				$MP3=1;
				if ($DB) {print "MP3 audio files\n";}
				}
			else
				{
				if ($args =~ /--OGG/i)
					{
					$OGG=1;
					if ($DB) {print "OGG audio files\n";}
					}
				else
					{
					if ($args =~ /--WAV/i)
						{
						$WAV=1;
						if ($DB) {print "WAV audio files\n";}
						}
					else
						{
						if ($args =~ /--GSW/i)
							{
							$GSW=1;
							if ($DB) {print "GSW audio files\n";}
							}
						else
							{
							if ($args =~ /--GPG/i)
								{
								$GPG=1;
								if ($DB) {print "GPG encrypted audio files\n";}
								}
							}
						}
					}
				}
			}
		if ($args =~ /--ftp-server=/i) 
			{
			my @data_in = split(/--ftp-server=/,$args);
			$VARFTP_host = $data_in[1];
			$VARFTP_host =~ s/ .*//gi;
			$CLIFTP_host=1;
			if ($DB > 0) 
				{print "\n----- FTP SERVER: $VARFTP_host -----\n\n";}
			}
		if ($args =~ /--ftp-port=/i)
			{
			my @data_in = split(/--ftp-port=/,$args);
			$VARFTP_port = $data_in[1];
			$VARFTP_port =~ s/ .*//gi;
			$CLIFTP_port=1;
			if ($DB > 0)
				{print "\n----- FTP PORT: $VARFTP_port -----\n\n";}
			}
		if ($args =~ /--ftp-login=/i) 
			{
			my @data_in = split(/--ftp-login=/,$args);
			$VARFTP_user = $data_in[1];
			$VARFTP_user =~ s/ .*//gi;
			$CLIFTP_user=1;
			if ($DB > 0) 
				{print "\n----- FTP LOGIN: $VARFTP_user -----\n\n";}
			}
		if ($args =~ /--ftp-pass=/i) 
			{
			my @data_in = split(/--ftp-pass=/,$args);
			$VARFTP_pass = $data_in[1];
			$VARFTP_pass =~ s/ .*//gi;
			$CLIFTP_pass=1;
			if ($DB > 0) 
				{print "\n----- FTP PASS: $VARFTP_pass -----\n\n";}
			}
		if ($args =~ /--ftp-dir=/i) 
			{
			my @data_in = split(/--ftp-dir=/,$args);
			$VARFTP_dir = $data_in[1];
			$VARFTP_dir =~ s/ .*//gi;
			$CLIFTP_dir=1;
			if ($DB > 0) 
				{print "\n----- FTP DIRECTORY: $VARFTP_dir -----\n\n";}
			}
		if ($args =~ /--ftp-persistent/i) 
			{
			$FTPpersistent=1;
			if ($DB > 0) 
				{print "\n----- FTP PERSISTENT: $FTPpersistent -----\n\n";}
			}
		if ($args =~ /--ftp-validate/i) 
			{
			$FTPvalidate=1;
			if ($DB > 0) 
				{print "\n----- FTP VALIDATE: $FTPvalidate -----\n\n";}
			}
		if ($args =~ /--ftp-active/i) 
			{
			$FTPpassive=0;
			if ($DB > 0) 
				{print "\n----- FTP ACTIVE(non-Passive) connection: 1 ($FTPpassive) -----\n\n";}
			}
		}
	}
else
	{
	#print "no command line options set\n";
	$WAV=1;
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

use Net::Ping;
use Net::FTP;

### directory where -all recordings are
if ($WAV > 0) {$dir2 = "$PATHDONEmonitor";}
if ($MP3 > 0) {$dir2 = "$PATHDONEmonitor/MP3";}
if ($GSM > 0) {$dir2 = "$PATHDONEmonitor/GSM";}
if ($OGG > 0) {$dir2 = "$PATHDONEmonitor/OGG";}
if ($GSW > 0) {$dir2 = "$PATHDONEmonitor/GSW";}
if ($GPG > 0) {$dir2 = "$PATHDONEmonitor/GPG";}

opendir(FILE, "$dir2/");
@FILES = readdir(FILE);

### Loop through files first to gather filesizes
$i=0;
$files_that_count=0;
foreach(@FILES)
	{
	$FILEsize1[$i] = 0;
	if ( (length($FILES[$i]) > 4) && (!-d "$dir2/$FILES[$i]") )
		{
		$FILEsize1[$i] = (-s "$dir2/$FILES[$i]");
		if ($DBX) {print "$dir2/$FILES[$i] $FILEsize1[$i]\n";}
		$files_that_count++;
		}
	$i++;
	if ($files_that_count >= $list_limit)
		{
			last();
		}		
	}

sleep(5);

$transfered_files = 0;

### Loop through files a second time to gather filesizes again 5 seconds later
$i=0;
foreach(@FILES)
	{
	$FILEsize2[$i] = 0;

	if ( (length($FILES[$i]) > 4) && (!-d "$dir2/$FILES[$i]") )
		{

		$FILEsize2[$i] = (-s "$dir2/$FILES[$i]");
		if ($DBX) {print "$dir2/$FILES[$i] $FILEsize2[$i]\n\n";}
		
		if ($FILEsize1[$i] ne $FILEsize2[$i]) 
			{
			if ($DBX) {print "not transfering $dir2/$FILES[$i]. File size mismatch $FILEsize2[$i] != $FILEsize1[$i]\n\n";}
			}

		if ( ($FILES[$i] !~ /out\.|in\.|lost\+found/i) && ($FILEsize1[$i] eq $FILEsize2[$i]) && (length($FILES[$i]) > 4))
			{
			$recording_id='';
			$ALLfile = $FILES[$i];
			$SQLFILE = $FILES[$i];
			$SQLFILE =~ s/\.gpg//gi;
			$SQLFILE =~ s/-all\.wav|-all\.gsm|-all\.ogg|-all\.mp3//gi;
			$SQLFILE =~ s/\.wav|\.gsm|\.ogg|\.mp3//gi;

			$stmtA = "select recording_id,start_time from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$recording_id =	"$aryA[0]";
				$start_date =	"$aryA[1]";
				$start_date =~ s/ .*//gi;

				@filedate = split(/-/,$start_date);
				$year = $filedate[0];
				$mon = $filedate[1];
				$mday = $filedate[2];
				}
			$sthA->finish();

			if (length($start_date) < 6)
				{$start_date = $FTPdate;}

			if ($DB) {print "|$recording_id|$start_date|$ALLfile|     |$SQLfile|\n";}

	### BEGIN Remote file transfer
			if ( ($FTPpersistent > 0) && ($ping_good > 0) )
				{$ping_good=1;}
			else
				{
				$p = Net::Ping->new();
				$ping_good = $p->ping("$VARFTP_host");
				}

			if (!$ping_good)
				{
				$p = Net::Ping->new("icmp");
				$ping_good = $p->ping("$VARFTP_host");
				}

			if ($ping_good)
				{	
				$transfered_files++;
				
				$start_date_PATH='';
				$FTPdb=0;
				if ($DBX>0) {$FTPdb=1;}
				if ( ($FTPpersistent > 0) && ($transfered_files > 1) )
					{
					if($DBX){print STDERR "FTP PERSISTENT, skipping login\n";}
					if ($NODATEDIR < 1)
						{
						if ($YMDdatedir > 0) 
							{
							$ftp->cwd("../../../");
							}
						else
							{
							if ($YearYMDdatedir > 0) 
								{
								$ftp->cwd("../../");
								}
							else
								{
								$ftp->cwd("../");
								}
							}
						}
					}
				else
					{
					if ($FTPpassive > 0) 
						{
						if($DBX){print STDERR "Connecting to FTP server in passive mode...\n";}
						$ftp = Net::FTP->new("$VARFTP_host", Port => $VARFTP_port, Debug => $FTPdb);
						}
					else
						{
						if($DBX){print STDERR "Connecting to FTP server in active mode...\n";}
						$ftp = Net::FTP->new("$VARFTP_host", Port => $VARFTP_port, Debug => $FTPdb, Passive => 0);
						}
					$ftp = Net::FTP->new("$VARFTP_host", Port => $VARFTP_port, Debug => $FTPdb);
					$ftp->login("$VARFTP_user","$VARFTP_pass");
					$ftp->cwd("$VARFTP_dir");

					if ($FTPpassive < 1) 
						{
						if($DBX){print STDERR "Forcing FTP connection to active mode...\n";}
						$ftp->passive("0");
						}
					}
				if ($NODATEDIR < 1)
					{
					if ($YMDdatedir > 0) 
						{
						$ftp->mkdir("$year");
						$ftp->cwd("$year");
						$ftp->mkdir("$mon");
						$ftp->cwd("$mon");
						$ftp->mkdir("$mday");
						$ftp->cwd("$mday");
						$start_date_PATH = "$year/$mon/$mday/";
						}
					else
						{
						if ($YearYMDdatedir > 0) 
							{
							$ftp->mkdir("$year");
							$ftp->cwd("$year");
							$ftp->mkdir("$start_date");
							$ftp->cwd("$start_date");
							$start_date_PATH = "$year/$start_date/";
							}
						else
							{
							$ftp->mkdir("$start_date");
							$ftp->cwd("$start_date");
							$start_date_PATH = "$start_date/";
							}
						}
					}
				$ftp->binary() or die "Cannot set binary transfer, is server connected?";
				$ftp->put("$dir2/$ALLfile", "$ALLfile");
				if ($FTPvalidate > 0)
					{
					$FTPfilesize = $ftp->size("$ALLfile");
					if($DBX){print STDERR "FTP FILESIZE:   $FTPfilesize/$FILEsize1[$i] | $ALLfile\n";}

					if ( ($FILEsize1[$i] > 100) && ($FTPfilesize < 100) )
						{
						print "ERROR! File did not transfer, exiting:   $FTPfilesize/$FILEsize1[$i] | $ALLfile\n";
						exit;
						}
					}
				if ($FTPpersistent < 1)
					{
					$ftp->quit;
					}

				$stmtA = "UPDATE recording_log set location='$VARHTTP_path/$start_date_PATH$ALLfile' where recording_id='$recording_id';";
					if($DB){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

				if (!$T)
					{
					$localDIR='';
					if ( ($localdatedir > 0) && ($NODATEDIR < 1) )
						{
						if ($YMDdatedir > 0) 
							{
							if (-d "$PATHDONEmonitor/FTP/$year") 
								{if($DBX) {print "Year directory exists: $PATHDONEmonitor/FTP/$year\n";}}
							else 
								{mkdir("$PATHDONEmonitor/FTP/$year",0755);   if($DBX) {print "Year directory created: $PATHDONEmonitor/FTP/$year\n";}}
							if (-d "$PATHDONEmonitor/FTP/$year/$mon") 
								{if($DBX) {print "Month directory exists: $PATHDONEmonitor/FTP/$year/$mon\n";}}
							else 
								{mkdir("$PATHDONEmonitor/FTP/$year/$mon",0755);   if($DBX) {print "Month directory created: $PATHDONEmonitor/FTP/$year/$mon\n";}}
							if (-d "$PATHDONEmonitor/FTP/$year/$mon/$mday") 
								{if($DBX) {print "Day directory exists: $PATHDONEmonitor/FTP/$year/$mon/$mday\n";}}
							else 
								{mkdir("$PATHDONEmonitor/FTP/$year/$mon/$mday",0755);   if($DBX) {print "Day directory created: $PATHDONEmonitor/FTP/$year/$mon/$mday\n";}}
							$localDIR = "$year/$mon/$mday/";
							}
						else
							{
							if ($YearYMDdatedir > 0) 
								{
								if (-d "$PATHDONEmonitor/FTP/$year") 
									{if($DBX) {print "Year directory exists: $PATHDONEmonitor/FTP/$year\n";}}
								else 
									{mkdir("$PATHDONEmonitor/FTP/$year",0755);   if($DBX) {print "Year directory created: $PATHDONEmonitor/FTP/$year\n";}}
								if (-d "$PATHDONEmonitor/FTP/$year/$start_date") 
									{if($DBX) {print "Full-date directory exists: $PATHDONEmonitor/FTP/$year/$start_date\n";}}
								else 
									{mkdir("$PATHDONEmonitor/FTP/$year/$start_date",0755);   if($DBX) {print "Year directory created: $PATHDONEmonitor/FTP/$year/$start_date\n";}}
								$localDIR = "$year/$start_date/";
								}
							else
								{
								if (-d "$PATHDONEmonitor/FTP/$start_date") 
									{if($DBX) {print "Full-date directory exists: $PATHDONEmonitor/FTP/$start_date\n";}}
								else 
									{mkdir("$PATHDONEmonitor/FTP/$start_date",0755);   if($DBX) {print "Year directory created: $PATHDONEmonitor/FTP/$start_date\n";}}
								$localDIR = "$start_date/";
								}
							}
						}
					`mv -f "$dir2/$ALLfile" "$PATHDONEmonitor/FTP/$localDIR$ALLfile"`;
					}
				
				if($DBX){print STDERR "Transfered $transfered_files files\n";}
				
				if ( $transfered_files == $file_limit) 
					{
					if($DBX){print STDERR "Transfer limit of $file_limit reached breaking out of the loop\n";}
					last();
					}
				}
			else
				{
				if($DB){print "ERROR: Could not ping server $VARFTP_host\n";}
				}
	### END Remote file transfer

			### sleep for twenty hundredths of a second to not flood the server with disk activity
			usleep(1*200*1000);

			}
		}
	$i++;
	}

if ($DB) {print "DONE... EXITING\n\n";}

$dbhA->disconnect();


exit;
