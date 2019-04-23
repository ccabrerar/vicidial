#!/usr/bin/perl
#
# AST_activity_log_export.pl                version: 2.14
#
# This script is designed to gather specific fields from the vicidial_agent_log 
# and recording_log tables, as well as recently changed vicidial_list records and
# post them to a directory. 
#
# /usr/share/astguiclient/AST_activity_log_export.pl --campaign=GOODB-GROUP1-GROUP3-GROUP4-SPECIALS-DNC_BEDS --output-format=tab-basic --debug --filename=BEDSsaleDATETIME.txt --email-list=test@gmail.com --email-sender=test@test.com
#
# /usr/share/astguiclient/AST_activity_log_export.pl --campaign=---ALL--- --output-format=csv-basic --debug --filename=TABLE --email-list=test@gmail.com --email-sender=test@test.com
#
# NOTE: You might want to add these database indexes before you run this script:
# create index rec_start_time on recording_log(start_time);
# create index vl_modify_date on vicidial_list(modify_date);
#
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 190323-0549 - First version based on AST_call_log_export.pl
# 190329-1451 - Added agent_log_id to vicidial_agent_log export
# 190420-1702 - Added --quoted-vl option
#

$txt = '.txt';
$csv = '.csv';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$uniqueidLIST='|';
$filename='';
$dateset=0;
$quoted_vl=0;

# Default FTP account variables
$VARREPORT_host = '10.0.0.4';
$VARREPORT_user = 'cron';
$VARREPORT_pass = 'test';
$VARREPORT_port = '21';
$VARREPORT_dir  = 'REPORTS';

# default CLI values
$campaign = 'TESTCAMP';
$output_format = 'csv-basic';
$vicidial_agent_log = 'vicidial_agent_log';
$recording_log = 'recording_log';
$vicidial_list = 'vicidial_list';

$secX = time();
$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$yy = ($year - 2000);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$filedate = "$year$mon$mday";
$filedatetime = "$year$mon$mday$hour$min$sec";
$ABIfiledate = "$mon-$mday-$year$us$hour$min$sec";
$shipdate = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$datestamp = "$year/$mon/$mday $hour:$min";


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
		print "  [--date=YYYY-MM-DD] = date override, otherwise will use processed=N\n";
		print "  [--hour-offset=X] = print datetime strings with this hour offset\n";
		print "  [--filename=XXX] = Name prefix to be used for file\n";
		print "  [--campaign=XXX] = Campaign that sales will be pulled from, use ---ALL--- for all campaigns\n";
		print "  [--without-camp=XXX] = Campaign that will be excluded from ALL\n";
		print "  [--output-format=XXX] = Format of file. Default \"pipe-standard\"\n";
		print "  [--quoted-vl] = Put vicidial_list export fields in quotes: \"field\"\n";
		print "  [--ftp-transfer] = Send results file by FTP to another server\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--quiet] = quiet\n";
		print "  [--archive-tables] = use archive tables\n";
		print "  [--test] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
		print "\n";
		print "  format options:\n";
		print "   tab-basic:\n";
		print "   call_date  phone_number  vendor_id  status  user  first_name  last_name  lead_id  list_id  campaign_id  length_in_sec  etc...\n";
		print "   pipe-basic:\n";
		print "   call_date|phone_number|vendor_id|status|user|first_name|last_name|lead_id|list_id|campaign_id|length_in_sec|source_id|etc...\n";
		print "   csv-basic:\n";
		print "   call_date,phone_number,vendor_id,status,user,first_name,last_name,lead_id,list_id,campaign_id,length_in_sec,etc...\n";
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

		if ($args =~ /--hour-offset=/i)
			{
			@data_in = split(/--hour-offset=/,$args);
			$hour_offset = $data_in[1];
			$hour_offset =~ s/ .*//gi;
			if (!$Q) {print "\n----- HOUR OFFSET: $hour_offset -----\n\n";}
			}
		else
			{$hour_offset=0;}
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
			if (!$Q) {print "\n----- DATE OVERRIDE: $shipdate -----\n\n";}
			$dateset=1;
			}
		else
			{
			$time=$TWOAMsec;
			$dateset=0;
			}


		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
			$campaign = $data_in[1];
			$campaign =~ s/ .*$//gi;
			$campaignSQL = $campaign;
			if ($campaignSQL =~ /---ALL---/)
				{$campaignSQL='';}
			else
				{
				if ($campaignSQL =~ /-/) 
					{
					$campaignSQL =~ s/-/','/gi;
					}
				$campaignSQL = "'$campaignSQL'";
				}
			if (!$Q) {print "\n----- CAMPAIGNS: $campaign ($campaignSQL) -----\n\n";}
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
			if (!$Q) {print "\n----- WITHOUT CAMPAIGNS: $NOTcampaign ($NOTcampaignSQL) -----\n\n";}
			}
		if ($args =~ /--filename=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--filename=/,$args);
			$yy = ($year - 2000);
			$filename = $data_in[1];
			$filename =~ s/ .*$//gi;
			$filename =~ s/YYYY/$year/gi;
			$filename =~ s/MM/$mon/gi;
			$filename =~ s/DD/$mday/gi;
			$filename =~ s/DATETIME/$year$mon$mday$hour$min$sec/gi;
			$filename =~ s/DATESPEC/$yy$mon$mday-$hour$min$sec/gi;
			$filename_override=1;
			if (!$Q) {print "\n----- FILENAME PREFIX: $filename -----\n\n";}
			}
		if ($args =~ /--output-format=/i)
			{
			@data_in = split(/--output-format=/,$args);
			$output_format = $data_in[1];
			$output_format =~ s/ .*$//gi;
			if (!$Q) {print "\n----- OUTPUT FORMAT: $output_format -----\n\n";}
			}
		if ($args =~ /--quoted-vl/i)
			{
			$quoted_vl=1;
			if (!$Q) {print "\n----- QUOTED VICIDIAL_LIST FIELDS: $quoted_vl -----\n\n";}
			}
		if ($args =~ /-ftp-transfer/i)
			{
			if (!$Q) {print "\n----- FTP TRANSFER MODE -----\n\n";}
			$ftp_transfer=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			if (!$Q) {print "\n----- TESTING -----\n\n";}
			}
		if ($args =~ /--archive-tables/i)
			{
			$archive_tables=1;
			$vicidial_agent_log = 'vicidial_agent_log_archive';
			$recording_log = 'recording_log';
			$vicidial_list = 'vicidial_list';
			if (!$Q) {print "\n----- USING ARCHIVE LOG TABLES -----\n\n";}
			}
		if ($args =~ /--email-list=/i)
			{
			@data_in = split(/--email-list=/,$args);
			$email_list = $data_in[1];
			$email_list =~ s/ .*//gi;
			$email_list =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";}
			}
		else
			{$email_list = '';}

		if ($args =~ /--email-sender=/i)
			{
			@data_in = split(/--email-sender=/,$args);
			$email_sender = $data_in[1];
			$email_sender =~ s/ .*//gi;
			$email_sender =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";}
			}
		else
			{$email_sender = 'vicidial@localhost';}
		}
	}
else
	{
	print "no command line options set, using defaults.\n";
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

if ($output_format =~ /^tab-basic$/) 
	{$DLT = "\t";   $txt='.txt';   if (!$Q) {print "---- tab-basic ----\n";}}
if ($output_format =~ /^pipe-basic$/) 
	{$DLT = "|";   $txt='.txt';   if (!$Q) {print "---- pipe-basic ----\n";}}
if ($output_format =~ /^csv-basic$/) 
	{$DLT = ",";   $txt='.csv';   if (!$Q) {print "---- ncad14csv ----\n";}}

if (length($campaignSQL) < 2)
	{
	if (length($NOTcampaignSQL) < 2)
		{
		$VALcampaignSQL = $vicidial_agent_log.".campaign_id NOT IN('')";
		}
	else
		{
		$VALcampaignSQL = $vicidial_agent_log.".campaign_id NOT IN($NOTcampaignSQL)";
		}
	}
else
	{
	$VALcampaignSQL = $vicidial_agent_log.".campaign_id IN($campaignSQL)";
	}

if ($dateset > 0)
	{
	$VALdateSQL = "event_time >= '$shipdate 00:00:00' and event_time <= '$shipdate 23:59:59'";
	$RECdateSQL = "start_time >= '$shipdate 00:00:00' and start_time <= '$shipdate 23:59:59'";
	$VLdateSQL = "( (modify_date >= '$shipdate 00:00:00' and modify_date <= '$shipdate 23:59:59') or (last_local_call_time >= '$shipdate 00:00:00' and last_local_call_time <= '$shipdate 23:59:59') )";
	}

$filename_prefix='';
if (length($filename)>0) {$filename_prefix = $filename.'_';}
$VALoutfile = $filename_prefix."vicidial_agent_log".$US.$shipdate.$txt;
$RECoutfile = $filename_prefix."recording_log".$US.$shipdate.$txt;
$VLoutfile = $filename_prefix."vicidial_list".$US.$shipdate.$txt;

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_activity_log_export.pl --\n\n";
	print "This program is designed to gather agent activity and  logs post them to a file over FTP. \n";
	print "\n";
	print "Campaign:      $campaign    $VALcampaignSQL \n";
	print "Output Format: $output_format \n";
	print "VAL file:      $VALoutfile \n";
	print "REC file:      $RECoutfile \n";
	print "VL file:       $VLoutfile \n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$TOTAL_VAL=0;
$TOTAL_REC=0;
$TOTAL_VL=0;


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


###########################################################################
########### SELECTED DAY AGENT ACTIVITY GATHERING: vicidial_agent_log  ######
###########################################################################
if ($DB > 0) {print "Starting VAL file pull...\n";}

### open the VAL out file for writing ###
$PATHoutfileVAL = "$PATHweb/vicidial/server_reports/$VALoutfile";
open(VALout, ">$PATHoutfileVAL")
		|| die "Can't open $PATHoutfileVAL: $!\n";

# user,event_time,lead_id,campaign_id,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,status
$stmtA = "SELECT user,CONVERT_TZ(event_time,$convert_tz),lead_id,campaign_id,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,status,agent_log_id from $vicidial_agent_log where $VALcampaignSQL and $VALdateSQL order by agent_log_id;";
if ($DBX) {print "|$stmtA|\n";}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$user =				$aryA[0];
	$event_time =		$aryA[1];
	$lead_id =			$aryA[2];
	$campaign_id =		$aryA[3];
	$pause_sec =		$aryA[4];
	$wait_sec =			$aryA[5];
	$talk_sec =			$aryA[6];
	$dispo_sec =		$aryA[7];
	$dead_sec =			$aryA[8];
	$status =			$aryA[9];
	$agent_log_id =		$aryA[10];

	if ($output_format =~ /^pipe-basic$/) 
		{
		$str = "$user|$event_time|$lead_id|$campaign_id|$pause_sec|$wait_sec|$talk_sec|$dispo_sec|$dead_sec|$status|$agent_log_id\n";
		}

	if ($output_format =~ /^tab-basic$/) 
		{
		$str = "$user\t$event_time\t$lead_id\t$campaign_id\t$pause_sec\t$wait_sec\t$talk_sec\t$dispo_sec\t$dead_sec\t$status\t$agent_log_id\n";
		}

	if ($output_format =~ /^csv-basic$/) 
		{		
		$str = "$user,$event_time,$lead_id,$campaign_id,$pause_sec,$wait_sec,$talk_sec,$dispo_sec,$dead_sec,$status,$agent_log_id\n";
		}

	print VALout "$str"; 
	if ($DBX) {print "$str\n";}

	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /200$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /300$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /400$/i) {print STDERR "\\     $rec_count \r";}
		if ($rec_count =~ /500$/i) {print STDERR "-     $rec_count \r";}
		if ($rec_count =~ /600$/i) {print STDERR "/     $rec_count \r";}
		if ($rec_count =~ /700$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /800$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /900$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /000$/i) {print "$rec_count|$TOTAL_VAL|         |$user|$lead_id|$event_time|\n";}
		}

	$TOTAL_VAL++;
	$rec_count++;
	}
$sthA->finish();
close(VALout);

if ($DB > 0) {print "VAL file done: $TOTAL_VAL\n";}


###########################################################################
########### SELECTED DAY RECORDING LOG GATHERING: recording_log  ######
###########################################################################
if ($DB > 0) {print "Starting REC file pull...\n";}

### open the REC out file for writing ###
$PATHoutfileREC = "$PATHweb/vicidial/server_reports/$RECoutfile";
open(RECout, ">$PATHoutfileREC")
		|| die "Can't open $PATHoutfileREC: $!\n";

# user,location,lead_id,recording_id,start_time
$stmtA = "SELECT user,location,lead_id,recording_id,CONVERT_TZ(start_time,$convert_tz) from $recording_log where $RECdateSQL order by recording_id;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$user =				$aryA[0];
	$location =			$aryA[1];
	$lead_id =			$aryA[2];
	$recording_id =		$aryA[3];
	$start_time =		$aryA[4];

	if ($output_format =~ /^pipe-basic$/) 
		{
		$str = "$user|$location|$lead_id|$recording_id|$start_time\n";
		}

	if ($output_format =~ /^tab-basic$/) 
		{
		$str = "$user\t$location\t$lead_id\t$recording_id\t$start_time\n";
		}

	if ($output_format =~ /^csv-basic$/) 
		{		
		$str = "$user,$location,$lead_id,$recording_id,$start_time\n";
		}

	print RECout "$str"; 
	if ($DBX) {print "$str\n";}

	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /200$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /300$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /400$/i) {print STDERR "\\     $rec_count \r";}
		if ($rec_count =~ /500$/i) {print STDERR "-     $rec_count \r";}
		if ($rec_count =~ /600$/i) {print STDERR "/     $rec_count \r";}
		if ($rec_count =~ /700$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /800$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /900$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /000$/i) {print "$rec_count|$TOTAL_REC|         |$user|$recording_id|$start_time|\n";}
		}

	$TOTAL_REC++;
	$rec_count++;
	}
$sthA->finish();
close(RECout);

if ($DB > 0) {print "REC file done: $TOTAL_REC\n";}



###########################################################################
########### SELECTED DAY LIST LOG GATHERING: vicidial_list  ######
###########################################################################
if ($DB > 0) {print "Starting VL file pull...\n";}

### open the VL out file for writing ###
$PATHoutfileVL = "$PATHweb/vicidial/server_reports/$VLoutfile";
open(VLout, ">$PATHoutfileVL")
		|| die "Can't open $PATHoutfileVL: $!\n";

# lead_id,etc...
$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from $vicidial_list where $VLdateSQL order by lead_id;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$lead_id =					$aryA[0];   if ($quoted_vl > 0) {$lead_id = '"'.$lead_id.'"';}
	$entry_date =				$aryA[1];   if ($quoted_vl > 0) {$entry_date = '"'.$entry_date.'"';}
	$modify_date =				$aryA[2];   if ($quoted_vl > 0) {$modify_date = '"'.$modify_date.'"';}
	$status =					$aryA[3];   if ($quoted_vl > 0) {$status = '"'.$status.'"';}
	$user =						$aryA[4];   if ($quoted_vl > 0) {$user = '"'.$user.'"';}
	$vendor_lead_code =			$aryA[5];   if ($quoted_vl > 0) {$vendor_lead_code = '"'.$vendor_lead_code.'"';}
	$source_id =				$aryA[6];   if ($quoted_vl > 0) {$source_id = '"'.$source_id.'"';}
	$list_id =					$aryA[7];   if ($quoted_vl > 0) {$list_id = '"'.$list_id.'"';}
	$gmt_offset_now =			$aryA[8];   if ($quoted_vl > 0) {$gmt_offset_now = '"'.$gmt_offset_now.'"';}
	$called_since_last_reset =	$aryA[9];   if ($quoted_vl > 0) {$called_since_last_reset = '"'.$called_since_last_reset.'"';}
	$phone_code =				$aryA[10];   if ($quoted_vl > 0) {$phone_code = '"'.$phone_code.'"';}
	$phone_number =				$aryA[11];   if ($quoted_vl > 0) {$phone_number = '"'.$phone_number.'"';}
	$title =					$aryA[12];   if ($quoted_vl > 0) {$title = '"'.$title.'"';}
	$first_name =				$aryA[13];   if ($quoted_vl > 0) {$first_name = '"'.$first_name.'"';}
	$middle_initial =			$aryA[14];   if ($quoted_vl > 0) {$middle_initial = '"'.$middle_initial.'"';}
	$last_name =				$aryA[15];   if ($quoted_vl > 0) {$last_name = '"'.$last_name.'"';}
	$address1 =					$aryA[16];   if ($quoted_vl > 0) {$address1 = '"'.$address1.'"';}
	$address2 =					$aryA[17];   if ($quoted_vl > 0) {$address2 = '"'.$address2.'"';}
	$address3 =					$aryA[18];   if ($quoted_vl > 0) {$address3 = '"'.$address3.'"';}
	$city =						$aryA[19];   if ($quoted_vl > 0) {$city = '"'.$city.'"';}
	$state =					$aryA[20];   if ($quoted_vl > 0) {$state = '"'.$state.'"';}
	$province =					$aryA[21];   if ($quoted_vl > 0) {$province = '"'.$province.'"';}
	$postal_code =				$aryA[22];   if ($quoted_vl > 0) {$postal_code = '"'.$postal_code.'"';}
	$country_code =				$aryA[23];   if ($quoted_vl > 0) {$country_code = '"'.$country_code.'"';}
	$gender =					$aryA[24];   if ($quoted_vl > 0) {$gender = '"'.$gender.'"';}
	$date_of_birth =			$aryA[25];   if ($quoted_vl > 0) {$date_of_birth = '"'.$date_of_birth.'"';}
	$alt_phone =				$aryA[26];   if ($quoted_vl > 0) {$alt_phone = '"'.$alt_phone.'"';}
	$email =					$aryA[27];   if ($quoted_vl > 0) {$email = '"'.$email.'"';}
	$security_phrase =			$aryA[28];   if ($quoted_vl > 0) {$security_phrase = '"'.$security_phrase.'"';}
	$comments =					$aryA[29];   if ($quoted_vl > 0) {$comments = '"'.$comments.'"';}
	$called_count =				$aryA[30];   if ($quoted_vl > 0) {$called_count = '"'.$called_count.'"';}
	$last_local_call_time =		$aryA[31];   if ($quoted_vl > 0) {$last_local_call_time = '"'.$last_local_call_time.'"';}
	$rank =						$aryA[32];   if ($quoted_vl > 0) {$rank = '"'.$rank.'"';}
	$owner =					$aryA[33];   if ($quoted_vl > 0) {$owner = '"'.$owner.'"';}
	$entry_list_id =			$aryA[34];   if ($quoted_vl > 0) {$entry_list_id = '"'.$entry_list_id.'"';}

	if ($output_format =~ /^pipe-basic$/) 
		{
		$str = "$lead_id|$entry_date|$modify_date|$status|$user|$vendor_lead_code|$source_id|$list_id|$gmt_offset_now|$called_since_last_reset|$phone_code|$phone_number|$title|$first_name|$middle_initial|$last_name|$address1|$address2|$address3|$city|$state|$province|$postal_code|$country_code|$gender|$date_of_birth|$alt_phone|$email|$security_phrase|$comments|$called_count|$last_local_call_time|$rank|$owner|$entry_list_id\n";
		}

	if ($output_format =~ /^tab-basic$/) 
		{
		$str = "$lead_id\t$entry_date\t$modify_date\t$status\t$user\t$vendor_lead_code\t$source_id\t$list_id\t$gmt_offset_now\t$called_since_last_reset\t$phone_code\t$phone_number\t$title\t$first_name\t$middle_initial\t$last_name\t$address1\t$address2\t$address3\t$city\t$state\t$province\t$postal_code\t$country_code\t$gender\t$date_of_birth\t$alt_phone\t$email\t$security_phrase\t$comments\t$called_count\t$last_local_call_time\t$rank\t$owner\t$entry_list_id\n";
		}

	if ($output_format =~ /^csv-basic$/) 
		{		
		$str = "$lead_id,$entry_date,$modify_date,$status,$user,$vendor_lead_code,$source_id,$list_id,$gmt_offset_now,$called_since_last_reset,$phone_code,$phone_number,$title,$first_name,$middle_initial,$last_name,$address1,$address2,$address3,$city,$state,$province,$postal_code,$country_code,$gender,$date_of_birth,$alt_phone,$email,$security_phrase,\"$comments\",$called_count,$last_local_call_time,$rank,$owner,$entry_list_id\n";
		}

	print VLout "$str"; 
	if ($DBX) {print "$str\n";}

	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /200$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /300$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /400$/i) {print STDERR "\\     $rec_count \r";}
		if ($rec_count =~ /500$/i) {print STDERR "-     $rec_count \r";}
		if ($rec_count =~ /600$/i) {print STDERR "/     $rec_count \r";}
		if ($rec_count =~ /700$/i) {print STDERR "|     $rec_count \r";}
		if ($rec_count =~ /800$/i) {print STDERR "+     $rec_count \r";}
		if ($rec_count =~ /900$/i) {print STDERR "0     $rec_count \r";}
		if ($rec_count =~ /000$/i) {print "$rec_count|$TOTAL_VL|         |$user|$lead_id|$modify_date|\n";}
		}

	$TOTAL_VL++;
	$rec_count++;
	}
$sthA->finish();
close(VLout);

if ($DB > 0) {print "VL file done: $TOTAL_VL\n";}




###### EMAIL SECTION

if ( (length($email_sender)>5) && (length($email_list) > 3) )
	{
	if ($q < 1) {print "Sending email: $email_list\n";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "VICIDIAL Activity Export $shipdate";

	  %mail = ( To      => "$email_list",
				From    => "$email_sender",
				Subject => "$mailsubject",
				Message => "VICIDIAL Activity Export $shipdate Complete\n\nAGENT FILE:     $TOTAL_VAL ($VALoutfile) \nRECORDING FILE: $TOTAL_REC ($RECoutfile) \nLIST FILE:      $TOTAL_VL ($VLoutfile) \n"
				);
	sendmail(%mail) or die $mail::Sendmail::error;
	if ($DB > 0) {print "ok. log says:\n", $mail::sendmail::log;}  ### print mail log for status
	}


# FTP overrides- 
#	$VARREPORT_host =	'10.0.0.4';
#	$VARREPORT_port =	'21';
#	$VARREPORT_user =	'vici';
#	$VARREPORT_pass =	'test';
#	$VARREPORT_dir =	'Activity_export';

if ($ftp_transfer > 0)
	{
	use Net::FTP;

	if (!$Q) {print "Sending Files Over FTP... \n";}
	$ftp = Net::FTP->new("$VARREPORT_host", Port => $VARREPORT_port, Debug => $DB);
	$ftp->login("$VARREPORT_user","$VARREPORT_pass");
	$ftp->cwd("$VARREPORT_dir");
	$ftp->binary();
	$ftp->put("$PATHoutfileVAL", "$VALoutfile");
	$ftp->put("$PATHoutfileREC", "$RECoutfile");
	$ftp->put("$PATHoutfileVL", "$VLoutfile");
	$ftp->quit;
	}


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) 
	{
	print "PROCESS COMPLETE! ($secZ seconds) \n";
	print "AGENT FILE:     $TOTAL_VAL ($VALoutfile) \n";
	print "RECORDING FILE: $TOTAL_REC ($RECoutfile) \n";
	print "LIST FILE:      $TOTAL_VL ($VLoutfile) \n";
	}

exit;

