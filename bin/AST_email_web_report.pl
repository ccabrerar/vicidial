#!/usr/bin/perl
#
# AST_email_web_report.pl                version: 2.14
#
# This script is designed to wget a web report and then email it as an attachment or send it to an FTP server
# this script should be run from a vicidial web server.
#
# NOTE: you need to either use Automated Reports in the admin web screens, or alter the URL within this script to change the report that is run by this script
#
# /usr/share/astguiclient/AST_email_web_report.pl --email-list=test@gmail.com --email-sender=test@test.com
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 90225-1247 - First version
# 111004-1057 - Added ftp options
# 111012-2220 - Added optional begin date flag
# 111214-0922 - Added remove images and links options as well as email subject option
# 160415-1421 - Fixed minor bug in file to attach to email
# 170304-1400 - Added options for running a defined Automated Report, and admin logging
# 170306-0915 - Fixed proper file extensions for different download report types
# 170719-1242 - Added more debug and '8days' date option
# 170825-1114 - Added report filename_override option
#

$txt = '.txt';
$html = '.html';
$US = '_';
$MT[0] = '';
$log_to_adminlog=0;
$report_id='';


$secX = time();
$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$filedate = "$year$mon$mday";
$filedatetime = "$year$mon$mday$hour$min$sec";
$shipdate = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$datestamp = "$year/$mon/$mday $hour:$min";
$hms = "$hour$min$sec";

use Time::Local;

### find epoch of 2AM today
$TWOAMsec = ( ($secX - ($sec + ($min * 60) + ($hour * 3600) ) ) + 7200);
### find epoch of 2AM yesterday
$TWOAMsecY = ($TWOAMsec - 86400);
### find epoch of 2AM 6 days ago
$TWOAMsecF = ($TWOAMsec - 518400);
### find epoch of 2AM 7 days ago
$TWOAMsecG = ($TWOAMsec - 604800);
### find epoch of 2AM 8 days ago
$TWOAMsecM = ($TWOAMsec - 691200);
### find epoch of 2AM 13 days ago
$TWOAMsecJ = ($TWOAMsec - 1123200);
### find epoch of 2AM 14 days ago
$TWOAMsecK = ($TWOAMsec - 1209600);
### find epoch of 2AM 15 days ago
$TWOAMsecL = ($TWOAMsec - 1296000);
### find epoch of 2AM 30 days ago
$TWOAMsecH = ($TWOAMsec - 2592000);

($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TWOAMsecY);
$Tyear = ($Tyear + 1900);
$Tmon++;
if ($Tmon < 10) {$Tmon = "0$Tmon";}
if ($Tmday < 10) {$Tmday = "0$Tmday";}
if ($Thour < 10) {$Thour = "0$Thour";}
if ($Tmin < 10) {$Tmin = "0$Tmin";}
if ($Tsec < 10) {$Tsec = "0$Tsec";}

($Fsec,$Fmin,$Fhour,$Fmday,$Fmon,$Fyear,$Fwday,$Fyday,$Fisdst) = localtime($TWOAMsecF);
$Fyear = ($Fyear + 1900);
$Fmon++;
if ($Fmon < 10) {$Fmon = "0$Fmon";}
if ($Fmday < 10) {$Fmday = "0$Fmday";}
if ($Fhour < 10) {$Fhour = "0$Fhour";}
if ($Fmin < 10) {$Fmin = "0$Fmin";}
if ($Fsec < 10) {$Fsec = "0$Fsec";}

($Gsec,$Gmin,$Ghour,$Gmday,$Gmon,$Gyear,$Gwday,$Gyday,$Gisdst) = localtime($TWOAMsecG);
$Gyear = ($Gyear + 1900);
$Gmon++;
if ($Gmon < 10) {$Gmon = "0$Gmon";}
if ($Gmday < 10) {$Gmday = "0$Gmday";}
if ($Ghour < 10) {$Ghour = "0$Ghour";}
if ($Gmin < 10) {$Gmin = "0$Gmin";}
if ($Gsec < 10) {$Gsec = "0$Gsec";}

($Hsec,$Hmin,$Hhour,$Hmday,$Hmon,$Hyear,$Hwday,$Hyday,$Hisdst) = localtime($TWOAMsecH);
$Hyear = ($Hyear + 1900);
$Hmon++;
if ($Hmon < 10) {$Hmon = "0$Hmon";}
if ($Hmday < 10) {$Hmday = "0$Hmday";}
if ($Hhour < 10) {$Hhour = "0$Hhour";}
if ($Hmin < 10) {$Hmin = "0$Hmin";}
if ($Hsec < 10) {$Hsec = "0$Hsec";}

($Jsec,$Jmin,$Jhour,$Jmday,$Jmon,$Jyear,$Jwday,$Jyday,$Jisdst) = localtime($TWOAMsecJ);
$Jyear = ($Jyear + 1900);
$Jmon++;
if ($Jmon < 10) {$Jmon = "0$Jmon";}
if ($Jmday < 10) {$Jmday = "0$Jmday";}
if ($Jhour < 10) {$Jhour = "0$Jhour";}
if ($Jmin < 10) {$Jmin = "0$Jmin";}
if ($Jsec < 10) {$Jsec = "0$Jsec";}

($Ksec,$Kmin,$Khour,$Kmday,$Kmon,$Kyear,$Kwday,$Kyday,$Kisdst) = localtime($TWOAMsecK);
$Kyear = ($Kyear + 1900);
$Kmon++;
if ($Kmon < 10) {$Kmon = "0$Kmon";}
if ($Kmday < 10) {$Kmday = "0$Kmday";}
if ($Khour < 10) {$Khour = "0$Khour";}
if ($Kmin < 10) {$Kmin = "0$Kmin";}
if ($Ksec < 10) {$Ksec = "0$Ksec";}

($Lsec,$Lmin,$Lhour,$Lmday,$Lmon,$Lyear,$Lwday,$Lyday,$Lisdst) = localtime($TWOAMsecL);
$Lyear = ($Lyear + 1900);
$Lmon++;
if ($Lmon < 10) {$Lmon = "0$Lmon";}
if ($Lmday < 10) {$Lmday = "0$Lmday";}
if ($Lhour < 10) {$Lhour = "0$Lhour";}
if ($Lmin < 10) {$Lmin = "0$Lmin";}
if ($Lsec < 10) {$Lsec = "0$Lsec";}

($Msec,$Mmin,$Mhour,$Mmday,$Mmon,$Myear,$Mwday,$Myday,$Misdst) = localtime($TWOAMsecM);
$Myear = ($Myear + 1900);
$Mmon++;
if ($Mmon < 10) {$Mmon = "0$Mmon";}
if ($Mmday < 10) {$Mmday = "0$Mmday";}
if ($Mhour < 10) {$Mhour = "0$Mhour";}
if ($Mmin < 10) {$Mmin = "0$Mmin";}
if ($Msec < 10) {$Msec = "0$Msec";}


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
		print "  [--quiet] = quiet\n";
		print "  [--test] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
		print "  [--remove-images] = Will parse through the output file and remove any images in the HTML\n";
		print "  [--remove-links] = Will parse through the output file and remove any links in the HTML\n";
		print "  [--log-to-adminlog] = Put an entry in the admin log after this report runs\n";
		print "  [--report-id=XXXXXXXX] = Report ID in the system to gather settings from\n";
		print "    NOTE: this will override any other options set below this one\n";
		print "  [--email-subject=XYZ] = Email message subject, which will be followed by the date of the report\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--date=YYYY-MM-DD] = date override, can also use 'today' and 'yesterday'\n";
		print "  [--begin-date=YYYY-MM-DD] = begin date override, can also use '6days', '7days', '8days' and '30days', if not filled in will only use the --date\n";
		print "  [--ftp-server=XXXXXXXX] = FTP server to send file to\n";
		print "  [--ftp-login=XXXXXXXX] = FTP user\n";
		print "  [--ftp-pass=XXXXXXXX] = FTP pass\n";
		print "  [--ftp-dir=XXXXXXXX] = remote FTP server directory to post files to\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG MODE -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG MODE -----\n\n";
			}
		if ($args =~ /-q/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			if ($DB > 0) {print "\n----- TESTING -----\n\n";}
			}
		if ($args =~ /--remove-images/i)
			{
			$remove_images=1;
			if ($DB > 0) {print "\n----- REMOVE IMAGES -----\n\n";}
			}
		if ($args =~ /--remove-links/i)
			{
			$remove_links=1;
			if ($DB > 0) {print "\n----- REMOVE LINKS -----\n\n";}
			}
		if ($args =~ /--log-to-adminlog/) 
			{
			$log_to_adminlog=1;
			if ($DB > 0) {print "\n----- LOGGING TO THE ADMIN LOG -----\n\n";}
			}
		if ($args =~ /--report-id=/i) 
			{
			@data_in = split(/--report-id=/,$args);
			$report_id = $data_in[1];
			$report_id =~ s/ .*//gi;
			$report_id =~ s/:/,/gi;
			if ($DB > 0) {print "\n----- REPORT ID: $report_id -----\n\n";}
			}
		else
			{
			if ($args =~ /--ftp-server=/i)
				{
				@data_in = split(/--ftp-server=/,$args);
				$VARREPORT_host = $data_in[1];
				$VARREPORT_host =~ s/ .*//gi;
				$VARREPORT_host =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- FTP SERVER: $VARREPORT_host -----\n\n";}
				}
			else
				{$VARREPORT_host = '';}
			if ($args =~ /--ftp-login=/i)
				{
				@data_in = split(/--ftp-login=/,$args);
				$VARREPORT_user = $data_in[1];
				$VARREPORT_user =~ s/ .*//gi;
				$VARREPORT_user =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- FTP LOGIN: $VARREPORT_user -----\n\n";}
				}
			else
				{$VARREPORT_user = '';}
			if ($args =~ /--ftp-pass=/i)
				{
				@data_in = split(/--ftp-pass=/,$args);
				$VARREPORT_pass = $data_in[1];
				$VARREPORT_pass =~ s/ .*//gi;
				$VARREPORT_pass =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- FTP PASS: <SET> -----\n\n";}
				}
			else
				{$VARREPORT_pass = '';}
			if ($args =~ /--ftp-dir=/i)
				{
				@data_in = split(/--ftp-dir=/,$args);
				$VARREPORT_dir = $data_in[1];
				$VARREPORT_dir =~ s/ .*//gi;
				$VARREPORT_dir =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- FTP DIR: $VARREPORT_dir -----\n\n";}
				}
			else
				{$VARREPORT_dir = '';}

			if ($args =~ /--email-subject=/i)
				{
				@data_in = split(/--email-subject=/,$args);
				$email_subject = $data_in[1];
				$email_subject =~ s/ .*//gi;
				$email_subject =~ s/\+/ /gi;
				if ($DB > 0) {print "\n----- EMAIL SUBJECT: $email_subject -----\n\n";}
				}
			else
				{$email_subject = 'VICIDIAL REPORT';}
			if ($args =~ /--email-list=/i)
				{
				@data_in = split(/--email-list=/,$args);
				$email_list = $data_in[1];
				$email_list =~ s/ .*//gi;
				$email_list =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";}
				}
			else
				{$email_list = '';}
			if ($args =~ /--email-sender=/i)
				{
				@data_in = split(/--email-sender=/,$args);
				$email_sender = $data_in[1];
				$email_sender =~ s/ .*//gi;
				$email_sender =~ s/:/,/gi;
				if ($DB > 0) {print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";}
				}
			else
				{$email_sender = 'vicidial@localhost';}
			if ($args =~ /--date=/i)
				{
				@data_in = split(/--date=/,$args);
				$shipdate = $data_in[1];
				$shipdate =~ s/ .*//gi;
				if ($shipdate =~ /today/)
					{
					$shipdate="$year-$mon-$mday";
					$time = $TWOAMsec;
					}
				else
					{
					if ($shipdate =~ /yesterday/)
						{
						$shipdate="$Tyear-$Tmon-$Tmday";
						$year = $Tyear;
						$mon =	$Tmon;
						$mday = $Tmday;
						$time=$TWOAMsecY;
						}
					else
						{
						@cli_date = split("-",$shipdate);
						$year = $cli_date[0];
						$mon =	$cli_date[1];
						$mday = $cli_date[2];
						$cli_date[1] = ($cli_date[1] - 1);
						$time = timelocal(0,0,2,$cli_date[2],$cli_date[1],$cli_date[0]);
						}
					}
				$start_date = $shipdate;
				$start_date =~ s/-//gi;
				if (!$Q) {print "\n----- DATE OVERRIDE: $shipdate($start_date) -----\n\n";}
				}
			else
				{
				$time=$TWOAMsec;
				}
			if ($args =~ /--begin-date=/i)
				{
				@data_in = split(/--begin-date=/,$args);
				$begindate = $data_in[1];
				$begindate =~ s/ .*//gi;
				if ($begindate =~ /6days/)
					{
					$begindate="$Fyear-$Fmon-$Fmday";
					$Byear = $Fyear;
					$Bmon =	$Fmon;
					$Bmday = $Fmday;
					$Btime=$TWOAMsecF;
					}
				else
					{
					if ($begindate =~ /7days/)
						{
						$begindate="$Gyear-$Gmon-$Gmday";
						$Byear = $Gyear;
						$Bmon =	$Gmon;
						$Bmday = $Gmday;
						$Btime=$TWOAMsecG;
						}
					else
						{
						if ($begindate =~ /30days/)
							{
							$begindate="$Hyear-$Hmon-$Hmday";
							$Byear = $Hyear;
							$Bmon =	$Hmon;
							$Bmday = $Hmday;
							$Btime=$TWOAMsecH;
							}
						else
							{
							@cli_date = split("-",$begindate);
							$Byear = $cli_date[0];
							$Bmon =	$cli_date[1];
							$Bmday = $cli_date[2];
							$cli_date[1] = ($cli_date[1] - 1);
							$Btime = timelocal(0,0,2,$cli_date[2],$cli_date[1],$cli_date[0]);
							}
						}
					}
				$Bstart_date = $begindate;
				$Bstart_date =~ s/-//gi;
				if (!$Q) {print "\n----- DATE OVERRIDE: $begindate($Bstart_date) -----\n\n";}
				}
			}
		}
	}
else
	{
	print "no command line options set, using defaults.\n";
	$email_subject = 'VICIDIAL REPORT';
	}
### end parsing run-time options ###

if (length($Bstart_date) < 8)
	{$Bstart_date = $start_date;}
if (length($begindate) < 8) 
	{$begindate = $shipdate;}

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
#	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
#		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
#		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
#		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
#		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_email_web_report.pl --\n\n";
	print "This program is designed to wget a web report and email or FTP it. \n";
	print "\n";
	}

if ( (length($report_id) > 1) || ($log_to_adminlog > 0) ) 
	{
	use DBI;	  

	$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	 or die "Couldn't connect to database: " . DBI->errstr;
	}

if (length($report_id) > 1) 
	{
	### Grab Server values from the database
	$stmtA = "SELECT report_destination,email_from,email_to,email_subject,ftp_server,ftp_user,ftp_pass,ftp_directory,report_url,filename_override from vicidial_automated_reports where report_id='$report_id';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$report_destination =	$aryA[0];
		if ($report_destination =~ /EMAIL/) 
			{
			$email_sender =			$aryA[1];
			$email_list =			$aryA[2];
			$email_subject =		$aryA[3];
			$email_subject =~ s/--A--date--B--|--A--today--B--/$year-$mon-$mday/gi;
			$email_subject =~ s/--A--yesterday--B--/$Tyear-$Tmon-$Tmday/gi;
			$email_subject =~ s/--A--datetime--B--/$year-$mon-$mday $hour:$min:$sec/gi;
			}
		if ($report_destination =~ /FTP/) 
			{
			$VARREPORT_host =		$aryA[4];
			$VARREPORT_user =		$aryA[5];
			$VARREPORT_pass =		$aryA[6];
			$VARREPORT_dir =		$aryA[7];
			}
		$location =				$aryA[8];
		$filename_override =	$aryA[9];
		if ($DB) {print "RAW LOCATION: |$location|\n";}
		$location =~ s/\&/\\&/gi;
		$location =~ s/--A--today--B--/$year-$mon-$mday/gi;
		$location =~ s/--A--yesterday--B--/$Tyear-$Tmon-$Tmday/gi;
		$location =~ s/--A--6days--B--/$Fyear-$Fmon-$Fmday/gi;
		$location =~ s/--A--7days--B--/$Gyear-$Gmon-$Gmday/gi;
		$location =~ s/--A--8days--B--/$Myear-$Mmon-$Mmday/gi;
		$location =~ s/--A--13days--B--/$Jyear-$Jmon-$Jmday/gi;
		$location =~ s/--A--14days--B--/$Kyear-$Kmon-$Kmday/gi;
		$location =~ s/--A--15days--B--/$Lyear-$Lmon-$Lmday/gi;
		$location =~ s/--A--30days--B--/$Hyear-$Hmon-$Hmday/gi;
		$filename_override =~ s/[^-_0-9a-zA-Z]//gi;
		$filename_override =~ s/--A--today--B--/$year-$mon-$mday/gi;
		$filename_override =~ s/--A--yesterday--B--/$Tyear-$Tmon-$Tmday/gi;
		$filename_override =~ s/--A--6days--B--/$Fyear-$Fmon-$Fmday/gi;
		$filename_override =~ s/--A--7days--B--/$Gyear-$Gmon-$Gmday/gi;
		$filename_override =~ s/--A--8days--B--/$Myear-$Mmon-$Mmday/gi;
		$filename_override =~ s/--A--13days--B--/$Jyear-$Jmon-$Jmday/gi;
		$filename_override =~ s/--A--14days--B--/$Kyear-$Kmon-$Kmday/gi;
		$filename_override =~ s/--A--15days--B--/$Lyear-$Lmon-$Lmday/gi;
		$filename_override =~ s/--A--30days--B--/$Hyear-$Hmon-$Hmday/gi;
		$filename_override =~ s/--A--filedatetime--B--/$filedatetime/gi;
		if ($DB) {print "FINAL LOCATION:    |$location|\n";}
		if ($DB) {print "FILENAME OVERRIDE: |$filename_override|\n";}
		}
	else
		{
		if (!$Q) {print "ERROR: Report not found: $report_id\n";}
		exit;
		}
	$sthA->finish();

	### gather user information for last manager who edited the automated report
	$stmtA = "SELECT user from vicidial_admin_log where event_section='AUTOREPORTS' and record_id='$report_id' and event_type IN('ADD','MODIFY','COPY') order by admin_log_id desc limit 1;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$http_user =	$aryA[0];
		}
	else
		{
		if (!$Q) {print "ERROR: No report manager user found: $report_id\n";}
		exit;
		}
	$sthA->finish();
	### gather user information for last manager who edited the automated report
	$stmtA = "SELECT pass,custom_five from vicidial_users where user='$http_user';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$http_pass =	$aryA[0];
		if (length($http_pass) < 1) 
			{$http_pass =	$aryA[1];}
		}
	else
		{
		if (!$Q) {print "ERROR: No report manager user found: $http_user\n";}
		exit;
		}
	$sthA->finish();

	$file_extension = $html;
	if ($location =~ /call_report_export|lead_report_export|call_report_export_carrier|list_download/) 
		{$file_extension = '.txt'}
	else
		{
		if ($location =~ /&file_download=\d/) 
			{$file_extension = '.csv'}
		}
	$HTMLfile = $report_id . "_" . $start_date . "_" . $hms . '' . $file_extension;
	if (length($filename_override) > 0) 
		{$HTMLfile = "$filename_override$file_extension";}
	$HTMLfileLOG = $report_id . "_" . $start_date . "_" . $hms . '' . $txt;
	$shipdate='';
	}
else
	{
	######################################################
	##### CHANGE REPORT NAME AND URL HERE!!!!!!!!!
	######################################################
	$file_extension = $html;
	#$HTMLfile = "Outbound_Report_ALL_$shipdate$html";
	$HTMLfile = "DISCH_SURVEY_$start_date$file_extension";
	$HTMLfileLOG = "DISCH_SURVEY_$start_date$txt";

	#$location = "http://6666:1234\@127.0.0.1/vicidial/fcstats.php?db_log=1\\&query_date=$shipdate\\&group=$group\\&shift=$shift\\&archive=1";
	#$location = "http://6666:1234\@192.168.1.101/vicidial/AST_VDADstats.php?query_date=$shipdate\\&end_date=$shipdate\\&group[]=--ALL--\\&shift=ALL";
	#$location = "http://8888:8888\@127.0.0.1/vicidial/AST_VDADstats.php?query_date=$shipdate\\&end_date=$shipdate\\&group[]=--ALL--\\&shift=ALL";
	#$location = "http://6666:1234\@127.0.0.1/vicidial/AST_inbound_daily_report.php?query_date=$shipdate\\&end_date=$shipdate\\&group=5001\\&shift=ALL\\&SUBMIT=SUBMIT\\&hourly_breakdown=checked";
	#$location = "http://6666:1234\@127.0.0.1/vicidial/AST_agent_performance_detail.php?DB=\\&query_date=$shipdate\\&end_date=$shipdate\\&group[]=2001\\&user_group[]=--ALL--\\&shift=ALL\\&SUBMIT=SUBMIT";

	# Outbound IVR Export Report:
	$location = "http://127.0.0.1/vicidial/call_report_export.php?query_date=$begindate\\&end_date=$shipdate\\&list_id[]=--ALL--\\&status[]=--ALL--\\&campaign[]=RSIDESVY\\&run_export=1\\&ivr_export=YES\\&export_fields=EXTENDED\\&header_row=NO\\&rec_fields=NONE\\&custom_fields=NO\\&call_notes=NO";

	$http_user = '6666';
	$http_pass = '1234';
	######################################################
	######################################################
	}

### GRAB THE REPORT
if (!$Q) {print "Running Report $ship_date\n$location\n";}
`/usr/bin/wget --auth-no-challenge --http-user=$http_user --http-password=$http_pass --output-document=/tmp/X$HTMLfile --output-file=/tmp/Y$HTMLfileLOG $location `;

if ($DBX > 0) {print "DEBUG: |/usr/bin/wget --auth-no-challenge --http-user=$http_user --http-password=$http_pass --output-document=/tmp/X$HTMLfile --output-file=/tmp/Y$HTMLfileLOG |";}

if ( ($remove_images > 0) || ($remove_links > 0) )
	{
	open(input, "/tmp/X$HTMLfile") || die "can't open /tmp/X$HTMLfile: $!\n";
	@input = <input>;
	close(input);
	$i=0;
	$newfile='';
	foreach(@input)
		{
		$Xline = $input[$i];
		if ($remove_images > 0)
			{$Xline =~ s/<img .*>//gi;}
		if ($remove_links > 0)
			{$Xline =~ s/\<a [^>]*\>//gi;}
		$newfile .= "$Xline";
		$i++;
		}
	open(output, ">/tmp/$HTMLfile") || die "can't open /tmp/$HTMLfile: $!\n";
	print output "$newfile";
	close(output);
	}
else
	{`mv /tmp/X$HTMLfile /tmp/$HTMLfile `;}

###### BEGIN EMAIL SECTION #####
if (length($email_list) > 3)
	{
	if (!$Q) {print "Sending email: $email_list\n";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "$email_subject $shipdate";

	%mail = ( To      => "$email_list",
					From    => "$email_sender",
					Subject => "$mailsubject",
			   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "$email_subject $shipdate:\n\n Attachment: $HTMLfile" );

	$Zfile = "/tmp/$HTMLfile";

	open (F, $Zfile) or die "Cannot read $Zfile: $!";
	binmode F; undef $/;
	$attachment = encode_base64(<F>);
	close F;

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: application/octet-stream; name=\"$HTMLfile\"\n";
	$mail{body} .= "Content-Transfer-Encoding: base64\n";
	$mail{body} .= "Content-Disposition: attachment; filename=\"$HTMLfile\"\n\n";
	$mail{body} .= "$attachment\n";
	$mail{body} .= "$boundary";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	if (!$Q) {print "ok. log says:\n", $mail::sendmail::log;} ### print mail log for status
	}
###### END EMAIL SECTION #####


###### BEGIN FTP SECTION #####

# FTP overrides-
#	$VARREPORT_host =	'10.0.0.4';
#	$VARREPORT_port =	'21';
#	$VARREPORT_user =	'cron';
#	$VARREPORT_pass =	'test';
#	$VARREPORT_dir =	'';

if (length($VARREPORT_host) > 7)
	{
	if (length($VARREPORT_port) < 1) {$VARREPORT_port = '21';}

	use Net::FTP;

	if (!$Q) {print "Sending File Over FTP: $HTMLfile\n";}
	$ftp = Net::FTP->new("$VARREPORT_host", Port => $VARREPORT_port, Debug => $DBX);
	$ftp->login("$VARREPORT_user","$VARREPORT_pass");
	$ftp->cwd("$VARREPORT_dir");
	$ftp->put("/tmp/$HTMLfile", "$HTMLfile");
	$ftp->quit;
	}
###### END FTP SECTION #####


$secY = time();
$secRUN = ($secY - $secX);
if ($secRUN < 1) {$secRUN=1;}

if (length($report_id) > 1) 
	{
	$stmtA="UPDATE vicidial_automated_reports set report_last_run=NOW(), report_last_length='$secRUN' where report_id='$report_id';";
	$affected_rows = $dbhA->do($stmtA);

	if ($log_to_adminlog > 0)
		{
		$location =~ s/;|\\|\'|\"//gi;
		$stmtB="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$http_user', ip_address='$server_ip', event_section='AUTOREPORTS', event_type='EXPORT', record_id='$report_id', event_code='ADMIN REPORT EXPORT RUN $report_destination', event_sql=\"$location\", event_notes='Run time: $secRUN seconds. Sent $report_destination';";
		$Iaffected_rows = $dbhA->do($stmtB);
		if ($DB) {print "ADMIN LOGGING FINISHED:   $affected_rows|$Iaffected_rows|$stmtA|$stmtB\n";}
		}
	}
else
	{
	if ($log_to_adminlog > 0)
		{
		$location =~ s/;|\\|\'|\"//gi;
		$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$http_user', ip_address='$server_ip', event_section='REPORTS', event_type='EXPORT', record_id='email_web_report', event_code='ADMIN REPORT EXPORT RUN', event_sql=\"$location\", event_notes='Run time: $secRUN seconds';";
		$Iaffected_rows = $dbhA->do($stmtA);
		if ($DB) {print "ADMIN LOGGING FINISHED:   $Iaffected_rows|$stmtA\n";}
		}
	}

exit;
