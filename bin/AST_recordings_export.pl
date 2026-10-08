#!/usr/bin/perl
#
# AST_recordings_export.pl                version: 2.14
#
# This script is designed to gather recordings into a temp directory from
# set in-groups and campaigns and then FTP transfer them out
#
# There are two main differences between this script and the AST_VDsales_export
# script,
#   1. no text file is generated in this script, only recordings are gathered
#   2. recordings are only pulled based upon the vicidial_id of the call
#
# /usr/share/astguiclient/AST_recordings_export.pl --campaign=GOODB-GROUP1-GROUP3-GROUP4-SPECIALS-DNC_BEDS --output-format=fixed-as400 --sale-statuses=SALE --debug --filename=BEDSsaleMMDD.txt --date=yesterday --email-list=test@gmail.com --email-sender=test@test.com
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130623-1739 - First version based upon AST_VDsales_export.pl
# 141020-0612 - Added --did-only option
# 150916-0628 - Several small fixes
# 170413-0743 - Added --with-archive-logs option
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$uniqueidLIST='|';
$ftp_audio_transfer=1;
$archive_logs=0;
$NODATEDIR = 0;	# Don't use dated directories for audio
$YEARDIR = 1;	# put dated directories in a year directory first

# Default FTP account variables
$VARREPORT_host = '10.0.0.4';
$VARREPORT_user = 'cron';
$VARREPORT_pass = 'test';
$VARREPORT_port = '21';
$VARREPORT_dir  = 'REPORTS';

# default CLI values
$campaign = 'TESTCAMP';
$sale_statuses = 'SALE-UPSELL';
$outbound_calltime_ignore=0;
$totals_only=0;
$OUTcalls=0;
$OUTtalk=0;
$OUTtalkmin=0;
$INcalls=0;
$INtalk=0;
$INtalkmin=0;
$email_post_audio=0;
$did_only=0;

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
$shipdate = "$year-$mon-$mday";
$shipdate_end = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$end_date = "$year$mon$mday";


use Time::Local;

### find epoch of 2AM today
$TWOAMsec = ( ($secX - ($sec + ($min * 60) + ($hour * 3600) ) ) + 7200);
### find epoch of 2AM yesterday
$TWOAMsecY = ($TWOAMsec - 86400);

($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TWOAMsecY);
$Tyear = ($Tyear + 1900);
$Tmon++;
if ($Tmon < 10) {$Tmon = "0$Tmon";}
if ($Tmday < 10) {$Tmday = "0$Tmday";}
if ($Thour < 10) {$Thour = "0$Thour";}
if ($Tmin < 10) {$Tmin = "0$Tmin";}
if ($Tsec < 10) {$Tsec = "0$Tsec";}


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
	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP


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
		print "  [--date=YYYY-MM-DD] = date override\n";
		print "  [--end-date=YYYY-MM-DD] = For more than one day, use date as the start\n";
		print "  [--with-archive-logs] = Will check in both live logs and archive logs\n";
		print "  [--campaign=XXX] = Campaign that sales will be pulled from\n";
		print "  [--without-camp=XXX] = Campaign that will be excluded from ALL\n";
		print "  [--sale-statuses=XXX-XXY] = Statuses that are deemed to be \"Sales\". Default SALE\n";
		print "    NOTE: To include all statuses in the export, use \"--sale-statuses=---ALL---\"\n";
		print "  [--with-inbound=XXX-XXY] = include the following inbound groups\n";
		print "  [--without-in=XXX-XXY] = inbound groups that will be excluded from ALL\n";
		print "  [--calltime=XXX] = filter results to only include those calls during this call time\n";
		print "  [--outbound-calltime-ignore] = for outbound calls ignores call time\n";
		print "  [--totals-only] = print totals of time and calls only\n";
		print "  [--did-only] = run for DID recordings only(defined in with-inbound flag)\n";
		print "  [--ftp-norun] = Stop program when you get to the FTP transfer\n";
		print "  [--ftp-temp-only] = Do not look up sales, only FTP transfer what is already in temp directory\n";
		print "  [--ftp-server=XXXXXXXX] = FTP server to send file to\n";
		print "  [--ftp-login=XXXXXXXX] = FTP user\n";
		print "  [--ftp-pass=XXXXXXXX] = FTP pass\n";
		print "  [--ftp-dir=XXXXXXXX] = remote FTP server directory to post files to\n";
		print "  [--temp-dir=XXX] = If running more than one instance at a time, specify a unique temp directory suffix\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--email-post-audio] = send an email after sending audio over FTP\n";
		print "  [--quiet] = quiet\n";
		print "  [--test] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
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
		if ($args =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /-totals-only/i)
			{$totals_only=1;}
		if ($args =~ /-did-only/i)
			{$did_only=1;}

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
		if ($args =~ /--end-date=/i)
			{
			@data_in = split(/--end-date=/,$args);
			$shipdate_end = $data_in[1];
			$shipdate_end =~ s/ .*//gi;
			if ($shipdate_end =~ /today/)
				{
				$shipdate_end="$year-$mon-$mday";
				$time = $TWOAMsec;
				}
			else
				{
				if ($shipdate_end =~ /yesterday/)
					{
					$shipdate_end="$Tyear-$Tmon-$Tmday";
					$year = $Tyear;
					$mon =	$Tmon;
					$mday = $Tmday;
					$time=$TWOAMsecY;
					}
				else
					{
					@cli_date = split("-",$shipdate_end);
					$year = $cli_date[0];
					$mon =	$cli_date[1];
					$mday = $cli_date[2];
					$cli_date[1] = ($cli_date[1] - 1);
					$time = timelocal(0,0,2,$cli_date[2],$cli_date[1],$cli_date[0]);
					}
				}
			$end_date = $shipdate_end;
			$end_date =~ s/-//gi;
			if (!$Q) {print "\n----- END DATE OVERRIDE: $shipdate_end($end_date) -----\n\n";}
			}
		else
			{
			$shipdate_end = $shipdate;
			$end_date = $start_date;
			}
		if ($args =~ /--with-archive-logs/i)
			{
			if (!$Q)
				{print "\n----- WILL SEARCH ARCHIVE LOGS AND ACTIVE LOGS -----\n\n";}
			$archive_logs=1;
			}
		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
			$campaign = $data_in[1];
			$campaign =~ s/ .*$//gi;
			$campaignSQL = $campaign;
			if ($campaignSQL =~ /-/) 
				{
				$campaignSQL =~ s/-/','/gi;
				}
			$campaignSQL = "'$campaignSQL'";
			}
		if ($args =~ /--without-camp=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--without-camp=/,$args);
			$NOTcampaign = $data_in[1];
			$NOTcampaign =~ s/ .*$//gi;
			$NOTcampaignSQL = $NOTcampaign;
			if ($NOTcampaignSQL =~ /-/) 
				{
				$NOTcampaignSQL =~ s/-/','/gi;
				}
			$NOTcampaignSQL = "'$NOTcampaignSQL'";
			}
		if ($args =~ /--sale-statuses=/i)
			{
			@data_in = split(/--sale-statuses=/,$args);
			$sale_statuses = $data_in[1];
			$sale_statuses =~ s/ .*$//gi;
			if ($sale_statuses =~ /---ALL---/)
				{if (!$Q) {print "\n----- EXPORT ALL STATUSES -----\n\n";} }
			}
		if ($args =~ /--with-inbound=/i)
			{
			@data_in = split(/--with-inbound=/,$args);
			$with_inbound = $data_in[1];
			$with_inbound =~ s/ .*$//gi;
			}
		if ($args =~ /--without-in=/i)
			{
			@data_in = split(/--without-in=/,$args);
			$NOTwith_inbound = $data_in[1];
			$NOTwith_inbound =~ s/ .*$//gi;
			}
		if ($args =~ /--calltime=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--calltime=/,$args);
			$call_time = $data_in[1];
			$call_time =~ s/ .*$//gi;
			}
		if ($args =~ /--outbound-calltime-ignore/i)
			{
			$outbound_calltime_ignore=1;
			if (!$Q)
				{print "\n----- IGNORE CALLTIME FOR OUTBOUND -----\n\n";}
			}
		if ($args =~ /--temp-dir=/i)
			{
			@data_in = split(/--temp-dir=/,$args);
			$temp_dir = $data_in[1];
			$temp_dir =~ s/ .*//gi;
			$temp_dir =~ s/:/,/gi;
			if ($DB > 0) {print "\n----- TEMP DIR: $temp_dir -----\n\n";}
			}
		else
			{$temp_dir = '';}
		if ($args =~ /-ftp-transfer/i)
			{
			if (!$Q)
				{print "\n----- FTP TRANSFER MODE -----\n\n";}
			$ftp_transfer=1;
			}
		if ($args =~ /-ftp-temp-only/i)
			{
			if (!$Q)
				{print "\n----- FTP TEMP DIR TRANSFER ONLY MODE -----\n\n";}
			$ftp_transfer_only=1;
			}
		if ($args =~ /-ftp-norun/i)
			{
			if (!$Q)
				{print "\n----- FTP NORUN MODE -----\n\n";}
			$ftp_norun=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--email-list=/i)
			{
			@data_in = split(/--email-list=/,$args);
			$email_list = $data_in[1];
			$email_list =~ s/ .*//gi;
			$email_list =~ s/:/,/gi;
			print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";
			}
		else
			{$email_list = '';}

		if ($args =~ /--email-sender=/i)
			{
			@data_in = split(/--email-sender=/,$args);
			$email_sender = $data_in[1];
			$email_sender =~ s/ .*//gi;
			$email_sender =~ s/:/,/gi;
			print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";
			}
		else
			{$email_sender = 'vicidial@localhost';}

		if ($args =~ /--email-post-audio/i)
			{
			$email_post_audio=1;
			print "\n----- EMAIL POST AUDIO: $email_post_audio -----\n\n";
			}

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
		}
	}
else
	{
	print "no command line options set, using defaults.\n";
	}
### end parsing run-time options ###


### find wget binary
$wgetbin = '';
if ( -e ('/bin/wget')) {$wgetbin = '/bin/wget';}
else 
	{
	if ( -e ('/usr/bin/wget')) {$wgetbin = '/usr/bin/wget';}
	else 
		{
		if ( -e ('/usr/local/bin/wget')) {$wgetbin = '/usr/local/bin/wget';}
		else
			{
			print "Can't find wget binary! Exiting...\n";
			exit
			}
		}
	}
### find find binary
$findbin = '';
if ( -e ('/bin/find')) {$findbin = '/bin/find';}
else 
	{
	if ( -e ('/usr/bin/find')) {$findbin = '/usr/bin/find';}
	else 
		{
		if ( -e ('/usr/local/bin/find')) {$findbin = '/usr/local/bin/find';}
		else
			{
			print "Can't find find binary! Exiting...\n";
			exit
			}
		}
	}

$tempdir = "/root/tempaudioexport$temp_dir";
if (!-e "$tempdir") 
	{`mkdir -p $tempdir`;}


### BEGIN ftp transfer only ###
if ($ftp_transfer_only > 0)
	{
	use Net::FTP;
	opendir(FILE, "$tempdir/");
	@FILES = readdir(FILE);

	if (!$Q) {print "Sending Audio Over FTP: $#FILES\n";}

	if ($ftp_norun > 0)
		{exit;}

	$i=0;
	foreach(@FILES)
		{
		if ( (length($FILES[$i]) > 4) && (!-d "$tempdir/$FILES[$i]") )
			{
			$ftp = Net::FTP->new("$VARREPORT_host", Port => "$VARREPORT_port", Debug => "$DB", Passive => "1");
			$ftp->login("$VARREPORT_user","$VARREPORT_pass");
			if (length($VARREPORT_dir) > 0)
				{$ftp->cwd("$VARREPORT_dir");}
			if ($NODATEDIR < 1)
				{
				if ($YEARDIR > 0)
					{
					$ftp->mkdir("$year");
					$ftp->cwd("$year");
					}
				$ftp->mkdir("$start_date");
				$ftp->cwd("$start_date");
				}
			$start_date_PATH = "$start_date/";
			$ftp->binary();
			$ftp->put("$tempdir/$FILES[$i]", "$FILES[$i]");
			$ftp->quit;
			}
		$i++;

		if ($DB > 0)
			{
			if ($i =~ /0$/i) {print "$i/$#FILES\n";}
			}
		}
	if (!$Q) {print "FTP ONLY DONE!  $i/$#FILES\n";}

	exit;
	}
### END ftp transfer only ###

## remove old audio files from directory
`$findbin $tempdir/ -maxdepth 1 -type f -mtime +0 -print | xargs rm -f`;


if ($sale_statuses =~ /---ALL---/)
	{
	$sale_statusesSQL='';
	$close_statusesSQL='';
	}
else
	{
	$sale_statusesSQL = $sale_statuses;
	$sale_statusesSQL =~ s/-/','/gi;
	$sale_statusesSQL = "'$sale_statusesSQL'";
	$close_statusesSQL = $sale_statusesSQL;
	$archive_sale_statusesSQL = $sale_statusesSQL;
	$archive_close_statusesSQL = $sale_statusesSQL;
	$sale_statusesSQL = " and vicidial_log.status IN($sale_statusesSQL)";
	$close_statusesSQL = " and vicidial_closer_log.status IN($close_statusesSQL)";
	$archive_sale_statusesSQL = " and vicidial_log_archive.status IN($archive_sale_statusesSQL)";
	$archive_close_statusesSQL = " and vicidial_closer_log_archive.status IN($archive_close_statusesSQL)";
	}


if (length($with_inbound) < 2)
	{
	if (length($NOTwith_inbound) < 2)
		{$with_inboundSQL = '';}
	else
		{
		$with_inboundSQL = $NOTwith_inbound;
		$with_inboundSQL =~ s/-/','/gi;
		$archive_with_inboundSQL = $with_inboundSQL;
		$with_inboundSQL = "vicidial_closer_log.campaign_id NOT IN('$with_inboundSQL')";
		$archive_with_inboundSQL = "vicidial_closer_log_archive.campaign_id NOT IN('$archive_with_inboundSQL')";
		}
	}
else
	{
	$with_inboundSQL = $with_inbound;
	$with_inboundSQL =~ s/-/','/gi;
	$archive_with_inboundSQL = $with_inboundSQL;
	if ($did_only > 0)
		{
		$with_inboundSQL = "recording_log.user IN('$with_inboundSQL')";
		$archive_with_inboundSQL = "recording_log_archive.user IN('$archive_with_inboundSQL')";
		}
	else
		{
		$with_inboundSQL = "vicidial_closer_log.campaign_id IN('$with_inboundSQL')";
		$archive_with_inboundSQL = "vicidial_closer_log_archive.campaign_id IN('$archive_with_inboundSQL')";
		}
	}

if (length($campaignSQL) < 2)
	{
	if (length($NOTcampaignSQL) < 2)
		{
		$campaignSQL = "vicidial_log.campaign_id NOT IN('')";
		$archive_campaignSQL = "vicidial_log_archive.campaign_id NOT IN('')";
		}
	else
		{
		$campaignSQL = "vicidial_log.campaign_id NOT IN($NOTcampaignSQL)";
		$archive_campaignSQL = "vicidial_log_archive.campaign_id NOT IN($NOTcampaignSQL)";
		}
	}
else
	{
	$archive_campaignSQL = $campaignSQL;
	$campaignSQL = "vicidial_log.campaign_id IN($campaignSQL)";
	$archive_campaignSQL = "vicidial_log_archive.campaign_id IN($archive_campaignSQL)";
	}

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_recordings_export.pl --\n\n";
	print "This program is designed to gather recordings from campaigns and in-groups and post them to an FTP site. \n";
	print "\n";
	print "Campaign:      $campaign    $campaignSQL\n";
	print "Sale Statuses: $sale_statuses     $sale_statusesSQL\n";
	print "With Inbound:  $with_inbound     $with_inboundSQL\n";
	print "DID:  $did_only\n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$TOTAL_SALES=0;
$TOTAL_RECORDINGS=0;

$timezone='-5';
$stmtA = "SELECT local_gmt FROM servers where server_ip='$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
	if ($DBX) {print "   $sthArows|$stmtA|\n";}
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$timezone =	$aryA[0];
	$sthA->finish();
	}
$offset_timezone = ($timezone + $hour_offset);
$SQLtimezone = sprintf("%10.2f",$timezone);
$SQLtimezone =~ s/\./:/gi;
$SQLtimezone =~ s/:50/:30/gi;
$SQLtimezone =~ s/ //gi;
$SQLoffset_timezone = sprintf("%10.2f",$offset_timezone);
$SQLoffset_timezone =~ s/\./:/gi;
$SQLoffset_timezone =~ s/:50/:30/gi;
$SQLoffset_timezone =~ s/ //gi;
$convert_tz = "'$SQLtimezone','$SQLoffset_timezone'";

if (!$Q)
	{print "\n----- SQL CONVERT_TZ: $SQLtimezone|$SQLoffset_timezone     $convert_tz -----\n\n";}


$stmtA = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$call_time';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
	if ($DBX) {print "   $sthArows|$stmtA|\n";}
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$Gct_default_start =	$aryA[3];
	$Gct_default_stop =		$aryA[4];
	$Gct_sunday_start =		$aryA[5];
	$Gct_sunday_stop =		$aryA[6];
	$Gct_monday_start =		$aryA[7];
	$Gct_monday_stop =		$aryA[8];
	$Gct_tuesday_start =	$aryA[9];
	$Gct_tuesday_stop =		$aryA[10];
	$Gct_wednesday_start =	$aryA[11];
	$Gct_wednesday_stop =	$aryA[12];
	$Gct_thursday_start =	$aryA[13];
	$Gct_thursday_stop =	$aryA[14];
	$Gct_friday_start =		$aryA[15];
	$Gct_friday_stop =		$aryA[16];
	$Gct_saturday_start =	$aryA[17];
	$Gct_saturday_stop =	$aryA[18];
	$sthA->finish();
	}
else
	{
	if ($DB > 0) {print "CALL TIME NOT FOUND: $call_time\n";}
	$call_time='';
	}


if ($did_only > 0)
	{
	#################################################################################
	########### CURRENT DAY DID RECORDINGS GATHERING did-only: recording_log  ######
	#################################################################################
	$stmtA = "SELECT lead_id,user,vicidial_id,vicidial_id,length_in_sec,UNIX_TIMESTAMP(start_time) from recording_log where $with_inboundSQL and start_time >= '$shipdate 00:00:00' and start_time <= '$shipdate_end 23:59:59' order by start_time;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DB) {print "$sthArows|$stmtA|\n";}
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$lead_id =		$aryA[0];
		$xfercallid =	$aryA[1];
		$vicidial_id =	$aryA[2];
		$uniqueid =		$aryA[3];
		$length_in_sec = $aryA[4];
		$epoch =		$aryA[5];

		$outbound = 'N';
		$domestic = 'Y';
		$user = '';
		$agent_name='';

		$archive_search=0;
		&select_format_loop;

		$TOTAL_SALES++;
		}
	$sthA->finish();

	if ($archive_logs > 0) 
		{
		$stmtA = "SELECT lead_id,user,vicidial_id,vicidial_id,length_in_sec,UNIX_TIMESTAMP(start_time) from recording_log_archive where $archive_with_inboundSQL and start_time >= '$shipdate 00:00:00' and start_time <= '$shipdate_end 23:59:59' order by start_time;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DB) {print "$sthArows|$stmtA|\n";}
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$lead_id =		$aryA[0];
			$xfercallid =	$aryA[1];
			$vicidial_id =	$aryA[2];
			$uniqueid =		$aryA[3];
			$length_in_sec = $aryA[4];
			$epoch =		$aryA[5];

			$outbound = 'N';
			$domestic = 'Y';
			$user = '';
			$agent_name='';

			$archive_search=1;
			&select_format_loop;

			$TOTAL_SALES++;
			}
		$sthA->finish();
		}
	}
else
	{
	###########################################################################
	########### CURRENT DAY SALES GATHERING outbound-only: vicidial_log  ######
	###########################################################################
	$stmtA = "SELECT vicidial_list.lead_id,uniqueid,length_in_sec,UNIX_TIMESTAMP(vicidial_log.call_date) from vicidial_list,vicidial_log where $campaignSQL $sale_statusesSQL and call_date >= '$shipdate 00:00:00' and call_date <= '$shipdate_end 23:59:59' and vicidial_log.lead_id=vicidial_list.lead_id order by call_date;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
		if ($DB) {print "$sthArows|$stmtA|\n";}
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$lead_id =			$aryA[0];
		$vicidial_id =		$aryA[1];
		$uniqueid =			$aryA[1];
		$length_in_sec =	$aryA[2];
		$epoch =			$aryA[3];

		$outbound = 'Y';
		$domestic = 'Y';
		$queue_seconds = '0';
		$closer='';

		$archive_search=0;
		&select_format_loop;

		$TOTAL_SALES++;
		}
	$sthA->finish();

	if ($archive_logs > 0) 
		{
		$stmtA = "SELECT vicidial_list.lead_id,uniqueid,length_in_sec,UNIX_TIMESTAMP(vicidial_log_archive.call_date) from vicidial_list,vicidial_log_archive where $archive_campaignSQL $archive_sale_statusesSQL and call_date >= '$shipdate 00:00:00' and call_date <= '$shipdate_end 23:59:59' and vicidial_log_archive.lead_id=vicidial_list.lead_id order by call_date;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
			if ($DB) {print "$sthArows|$stmtA|\n";}
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$lead_id =			$aryA[0];
			$vicidial_id =		$aryA[1];
			$uniqueid =			$aryA[1];
			$length_in_sec =	$aryA[2];
			$epoch =			$aryA[3];

			$outbound = 'Y';
			$domestic = 'Y';
			$queue_seconds = '0';
			$closer='';

			$archive_search=1;
			&select_format_loop;

			$TOTAL_SALES++;
			}
		$sthA->finish();
		}

	if (length($with_inboundSQL)>3)
		{
		#################################################################################
		########### CURRENT DAY SALES GATHERING inbound-only: vicidial_closer_log  ######
		#################################################################################
		$stmtA = "SELECT vicidial_list.lead_id,xfercallid,closecallid,uniqueid,length_in_sec,UNIX_TIMESTAMP(vicidial_closer_log.call_date) from vicidial_list,vicidial_closer_log where $with_inboundSQL $close_statusesSQL and call_date >= '$shipdate 00:00:00' and call_date <= '$shipdate_end 23:59:59' and vicidial_closer_log.lead_id=vicidial_list.lead_id order by call_date;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DB) {print "$sthArows|$stmtA|\n";}
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$lead_id =			$aryA[0];
			$xfercallid =		$aryA[1];
			$vicidial_id =		$aryA[2];
			$uniqueid =			$aryA[3];
			$length_in_sec =	$aryA[4];
			$epoch =			$aryA[5];

			$outbound = 'N';
			$domestic = 'Y';
			$user = '';
			$agent_name='';

			$archive_search=0;
			&select_format_loop;

			$TOTAL_SALES++;
			}
		$sthA->finish();

		if ($archive_logs > 0) 
			{
			$stmtA = "SELECT vicidial_list.lead_id,xfercallid,closecallid,uniqueid,length_in_sec,UNIX_TIMESTAMP(vicidial_closer_log_archive.call_date) from vicidial_list,vicidial_closer_log_archive where $archive_with_inboundSQL $archive_close_statusesSQL and call_date >= '$shipdate 00:00:00' and call_date <= '$shipdate_end 23:59:59' and vicidial_closer_log_archive.lead_id=vicidial_list.lead_id order by call_date;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DB) {print "$sthArows|$stmtA|\n";}
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$lead_id =			$aryA[0];
				$xfercallid =		$aryA[1];
				$vicidial_id =		$aryA[2];
				$uniqueid =			$aryA[3];
				$length_in_sec =	$aryA[4];
				$epoch =			$aryA[5];

				$outbound = 'N';
				$domestic = 'Y';
				$user = '';
				$agent_name='';

				$archive_search=1;
				&select_format_loop;

				$TOTAL_SALES++;
				}
			$sthA->finish();
			}
		}
	}

###### EMAIL SECTION

if ( (length($Ealert)>5) && (length($email_list) > 3) )
	{
	print "Sending email: $email_list\n";

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "Recording Export $outfile";

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$mailsubject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "Recording Export:\n\n Total Records: $TOTAL_SALES\n Total Recordings: $TOTAL_RECORDINGS\n" );

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary\n";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	print "ok. log says:\n", $mail::sendmail::log;  ### print mail log for status

	}



# FTP overrides-
#	$VARREPORT_host =	'10.0.0.4';
#	$VARREPORT_port =	'21';
#	$VARREPORT_user =	'cron';
#	$VARREPORT_pass =	'test';
#	$VARREPORT_dir =	'';


if ( ( ($DB) || ($totals_only > 0) ) && ($output_format =~ /^tab-QMcustomUSA$|^tab-SCcustomUSA$/) )
	{
	if ($OUTtalk > 0) {$OUTtalkmin = ($OUTtalk / 60);}
	if ($INtalk > 0) {$INtalkmin = ($INtalk / 60);}
	$OUTtalkmin = sprintf("%10.2f",$OUTtalkmin);
	$INtalkmin = sprintf("%10.2f",$INtalkmin);
	$OUTcalls = sprintf("%10s",$OUTcalls);
	$INcalls = sprintf("%10s",$INcalls);
	$OUTtalk = sprintf("%10s",$OUTtalk);
	$INtalk = sprintf("%10s",$INtalk);
	print "OUTBOUND CALLS:   $OUTcalls\n";
	print "OUTBOUND SECONDS: $OUTtalk\n";
	print "OUTBOUND MINUTES: $OUTtalkmin\n";
	print "INBOUND CALLS:    $INcalls\n";
	print "INBOUND SECONDS:  $INtalk\n";
	print "INBOUND MINUTES:  $INtalkmin\n";
	}

if ($ftp_audio_transfer > 0)
	{
	use Net::FTP;
	opendir(FILE, "$tempdir/");
	@FILES = readdir(FILE);

	if (!$Q) {print "Sending Audio Over FTP: $#FILES\n";}

	if ($ftp_norun > 0)
		{exit;}

	$i=0;
	foreach(@FILES)
		{
		if ( (length($FILES[$i]) > 4) && (!-d "$tempdir/$FILES[$i]") )
			{
			$ftp = Net::FTP->new("$VARREPORT_host", Port => "$VARREPORT_port", Debug => "$DB", Passive => "1");
			$ftp->login("$VARREPORT_user","$VARREPORT_pass");
			if (length($VARREPORT_dir) > 0)
				{$ftp->cwd("$VARREPORT_dir");}
			if ($NODATEDIR < 1)
				{
				if ($YEARDIR > 0)
					{
					$ftp->mkdir("$year");
					$ftp->cwd("$year");
					}
				$ftp->mkdir("$start_date");
				$ftp->cwd("$start_date");
				}
			$start_date_PATH = "$start_date/";
			$ftp->binary();
			$ftp->put("$tempdir/$FILES[$i]", "$FILES[$i]");
			$ftp->quit;
			}
		$i++;
		if ($DB > 0)
			{
			if ($i =~ /0$/i) {print "$i/$#FILES\n";}
			}
		}
	}


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) {print "SALES EXPORT FOR $shipdate\n";}
if (!$Q) {print "TOTAL SALES: $TOTAL_SALES\n";}
if (!$Q) {print "TOTAL EXCLUDED BY CALLTIME: $CALLTIME_KICK\n";}
if (!$Q) {print "TOTAL RECORDINGS: $TOTAL_RECORDINGS\n";}
if (!$Q) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}

###### POST FTP EMAIL SECTION

if ( (length($Ealert)>5) && (length($email_list) > 3) && ($email_post_audio > 0) )
	{
	print "Sending post-FTP email: $email_list\n";

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "Recording Export POST AUDIO TRANSFER";

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$mailsubject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "Recording Export POST AUDIO TRANSFER:\n\n Total Records: $TOTAL_SALES\n Total Recordings: $TOTAL_RECORDINGS\n" );

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary\n";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	print "ok. log says:\n", $mail::sendmail::log;  ### print mail log for status
	}

exit;





### Subroutine for formatting of the output ###
sub select_format_loop
	{
	$within_calltime = 1;

	if (length($call_time) > 0)
		{
		$CTtarget = ($secX - 10);
		($Csec,$Cmin,$Chour,$Cmday,$Cmon,$Cyear,$Cwday,$Cyday,$Cisdst) = localtime($epoch);
		if ($Chour < 10) {$Chour = "0$Chour";}
		if ($Cmin < 10) {$Cmin = "0$Cmin";}
		$CThourminute = "$Chour$Cmin";

		$CTstart = $Gct_default_start;
		$CTstop = $Gct_default_stop;

		if ( ($Cwday == 0) && ( ($Gct_sunday_start > 0) && ($Gct_sunday_stop > 0) ) )
			{$CTstart = $Gct_sunday_start;   $CTstop = $Gct_sunday_stop;}
		if ( ($Cwday == 1) && ( ($Gct_monday_start > 0) && ($Gct_monday_stop > 0) ) )
			{$CTstart = $Gct_monday_start;   $CTstop = $Gct_monday_stop;}
		if ( ($Cwday == 2) && ( ($Gct_tuesday_start > 0) && ($Gct_tuesday_stop > 0) ) )
			{$CTstart = $Gct_tuesday_start;   $CTstop = $Gct_tuesday_stop;}
		if ( ($Cwday == 3) && ( ($Gct_wednesday_start > 0) && ($Gct_wednesday_stop > 0) ) )
			{$CTstart = $Gct_wednesday_start;   $CTstop = $Gct_wednesday_stop;}
		if ( ($Cwday == 4) && ( ($Gct_thursday_start > 0) && ($Gct_thursday_stop > 0) ) )
			{$CTstart = $Gct_thursday_start;   $CTstop = $Gct_thursday_stop;}
		if ( ($Cwday == 5) && ( ($Gct_friday_start > 0) && ($Gct_friday_stop > 0) ) )
			{$CTstart = $Gct_friday_start;   $CTstop = $Gct_friday_stop;}
		if ( ($Cwday == 6) && ( ($Gct_saturday_start > 0) && ($Gct_saturday_stop > 0) ) )
			{$CTstart = $Gct_saturday_start;   $CTstop = $Gct_saturday_stop;}

		if ( ($CThourminute < $CTstart) || ($CThourminute > $CTstop) )
			{
			if ($DB > 0) {print "Call is outside of defined call time: $CThourminute|$Cwday|   |$CTstart|$CTstop| \n";}
			$within_calltime=0;
			}
		}
	
	if ( ($within_calltime > 0) || ( ($outbound_calltime_ignore > 0) && ($outbound =~ /Y/) ) )
		{
		$str='';
		$call_data = "|$outbound|$lead_id|$vicidial_id|$uniqueid|$length_in_sec|$epoch|";

		##### BEGIN standard audio lookup #####
		$ivr_id = '0';
		$ivr_filename = '';
		$recording_found=0;

		$stmtB = "SELECT recording_id,filename,location from recording_log where lead_id='$lead_id' and vicidial_id='$vicidial_id' and start_time >= '$shipdate 00:00:00' and start_time <= '$shipdate_end 23:59:59' order by start_time desc limit 100;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		if ($DBX) {print "$sthBrows|$stmtB|\n";}
		$rec_countB=0;
		while ($sthBrows > $rec_countB)
			{
			@aryB = $sthB->fetchrow_array;
			$ivr_id =		$aryB[0];
			$ivr_filename = $aryB[1];
			$ivr_location = $aryB[2];
			$rec_countB++;
			$recording_found++;

			if ( ($ftp_audio_transfer > 0) && (length($ivr_filename) > 3) && (length($ivr_location) > 5) )
				{
				@ivr_path = split(/\//,$ivr_location);
				$path_file = $ivr_path[$#ivr_path];
				`$wgetbin -q --output-document=$tempdir/$path_file $ivr_location `;
				$TOTAL_RECORDINGS++;
				}
			}
		$sthB->finish();

		if ( ($archive_logs > 0) && ($recording_found < 1) )
			{
			$stmtB = "SELECT recording_id,filename,location from recording_log_archive where lead_id='$lead_id' and vicidial_id='$vicidial_id' and start_time >= '$shipdate 00:00:00' and start_time <= '$shipdate_end 23:59:59' order by start_time desc limit 100;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($DBX) {print "$sthBrows|$stmtB|\n";}
			$rec_countB=0;
			while ($sthBrows > $rec_countB)
				{
				@aryB = $sthB->fetchrow_array;
				$ivr_id =		$aryB[0];
				$ivr_filename = $aryB[1];
				$ivr_location = $aryB[2];
				$rec_countB++;
				$recording_found++;

				if ( ($ftp_audio_transfer > 0) && (length($ivr_filename) > 3) && (length($ivr_location) > 5) )
					{
					@ivr_path = split(/\//,$ivr_location);
					$path_file = $ivr_path[$#ivr_path];
					`$wgetbin -q --output-document=$tempdir/$path_file $ivr_location `;
					$TOTAL_RECORDINGS++;
					}
				}
			$sthB->finish();
			}
		##### END standard audio lookup #####

		$Ealert .= "$TOTAL_SALES   $TOTAL_RECORDINGS   $rec_countB   $call_data\n"; 

		if ($DBX) {print "$TOTAL_SALES   $TOTAL_RECORDINGS   $rec_countB   $call_data\n";}
		}
	else
		{$CALLTIME_KICK++;}

	if ($DB > 0)
		{
		if ($rec_count =~ /10$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /20$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /30$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /40$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /50$/i) {print STDERR " GATHE* $rec_count\r";}
		if ($rec_count =~ /60$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /70$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /80$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /90$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /00$/i) {print STDERR " *ATHER $rec_count\r";}
		if ($rec_count =~ /00$/i) {print "        |$rec_count|$TOTAL_SALES|$CALLTIME_KICK|$TOTAL_RECORDINGS|         |$lead_id|\n";}
		}
	$rec_count++;
	}


#	if ($DB > 0)
#		{
#		if ($rec_count =~ /10$/i) {print STDERR "  *     $rec_count\r";}
#		if ($rec_count =~ /20$/i) {print STDERR "   *    $rec_count\r";}
#		if ($rec_count =~ /30$/i) {print STDERR "    *   $rec_count\r";}
#		if ($rec_count =~ /40$/i) {print STDERR "     *  $rec_count\r";}
#		if ($rec_count =~ /50$/i) {print STDERR "      * $rec_count\r";}
#		if ($rec_count =~ /60$/i) {print STDERR "     *  $rec_count\r";}
#		if ($rec_count =~ /70$/i) {print STDERR "    *   $rec_count\r";}
#		if ($rec_count =~ /80$/i) {print STDERR "   *    $rec_count\r";}
#		if ($rec_count =~ /90$/i) {print STDERR "  *     $rec_count\r";}
#		if ($rec_count =~ /00$/i) {print STDERR " *      $rec_count\r";}
#		if ($rec_count =~ /00$/i) {print "        |$rec_count|$TOTAL_SALES|$CALLTIME_KICK|$TOTAL_RECORDINGS|         |$lead_id|\n";}
#		}
