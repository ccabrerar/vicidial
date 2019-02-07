#!/usr/bin/perl
#
# AST_inbound_export.pl                version: 2.12
#
# This script is designed to gather inbound call data and dump 
# into a tab-delimited text file
#
# /usr/share/astguiclient/AST_inbound_export.pl --ingroups=---ALL--- --start-date=2016-01-01 --end-date=2016-06-30 --debug
#
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 161201-1913 - First version based upon AST_list_export.pl 
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$uniqueidLIST='|';
$ftp_audio_transfer=1;
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
$vicidial_closer_log='vicidial_closer_log';

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
$file_date = "$year$mon$mday-$hour$min$sec";


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
		print "  [--start-date=YYYY-MM-DD] = Use date as the start\n";
		print "  [--end-date=YYYY-MM-DD] = For more than one day, use start-date as the start\n";
		print "  [--ingroups=XXX-YYY] = In-groups that sales will be pulled from. REQUIRED\n";
		print "  [--temp-dir=XXX] = If running more than one instance at a time, specify a unique temp directory suffix\n";
		print "  [--archive] = user archive table\n";
		print "  [--agent-only] = only export calls that went to agents\n";
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
		if ($args =~ /--archive/i)
			{
			$vicidial_closer_log='vicidial_closer_log_archive';
			if (!$Q) {print "\n----- ARCHIVE TABLE: ($vicidial_closer_log) -----\n\n";}
			}
		if ($args =~ /--agent-only/i)
			{
			$agent_only=1;
			if (!$Q) {print "\n----- AGENT CALLS ONLY: ($agent_only) -----\n\n";}
			}
		if ($args =~ /--ingroups=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--ingroups=/,$args);
			$ingroups = $data_in[1];
			$ingroups =~ s/ .*$//gi;
			$ingroupsSQL = $ingroups;
			if ($ingroupsSQL =~ /--ALL--/) 
				{
				$ingroupsSQL='';
				}
			else
				{
				if ($ingroupsSQL =~ /-/) 
					{
					$ingroupsSQL =~ s/-/','/gi;
					}
				$ingroupsSQL = "and campaign_id IN('$ingroupsSQL')";
				}
			}
		else
			{
			print "no ingroups defined, exiting.\n";
			exit;
			}
		if ($args =~ /--start-date=/i)
			{
			@data_in = split(/--start-date=/,$args);
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
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		}
	}
else
	{
	print "no command line options set, exiting.\n";
	exit;
	}
### end parsing run-time options ###



if (length($ingroups) < 1)
	{
	print "no ingroups defined, exiting...";
	exit;
	}

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_inbound_export.pl --\n\n";
	print "This program is designed to gather ingroup log records including vicidial_list fields and export them to a data file. \n";
	print "\n";
	print "Ingroups:      $ingroups    $ingroupsSQL\n";
	print "Start Date:    $shipdate\n";
	print "End Date:      $shipdate_end\n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$TOTAL_CALLS=0;
$TOTAL_LEADS=0;
$SKIP_LEAD=0;


###########################################################################
########### CURRENT DAY SALES GATHERING inbound vicidial_closer_log  ######
###########################################################################
$stmtA = "SELECT lead_id,call_date,length_in_sec,phone_number,status,user,campaign_id from $vicidial_closer_log where call_date >= \"$shipdate 00:00:00\" and call_date <= \"$shipdate_end 23:59:59\" $ingroupsSQL;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
	if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$lead_id =					$aryA[0];
	$call_date =				$aryA[1];
	$length_in_sec =			$aryA[2];
	$phone_number =				$aryA[3];
	$status =					$aryA[4];
	$user =						$aryA[5];
	$campaign_id = 				$aryA[6];

	$look_up_lead=1;
	if ($agent_only > 0) 
		{
		if ( ($user =~ /VDAD|VDIC|VDCL/) || (length($user)<1) ) 
			{
			$look_up_lead=0;
			$SKIP_LEAD++;
			}
		}
	if ($look_up_lead > 0)
		{
		&select_format_loop;
		$TOTAL_CALLS++;
		}

	$rec_count++;
	}
$sthA->finish();


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) {print "SALES EXPORT FOR $ingroups   ($PATHhome/$file_date$US$ingroups.txt)\n";}
if (!$Q) {print "Total records scanned:   $rec_count\n";}
if (!$Q) {print "TOTAL CALLS:             $TOTAL_CALLS\n";}
if (!$Q) {print "TOTAL LEADS:             $TOTAL_LEADS\n";}
if (!$Q) {print "SKIPPED RECORDS:         $SKIP_LEAD\n";}
if (!$Q) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}




### Subroutine for formatting of the output ###
sub select_format_loop
	{
	$str='';
	$full_name='';
	$vendor_lead_code='';
	$list_id='';
	$first_name='';
	$last_name='';
	$address1='';
	$address2='';
	$address3='';
	$city='';
	$state='';
	$country_code='';
	$security_phrase='';

	$stmtB = "SELECT full_name from vicidial_users where user='$user';";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($DBX) {print "$sthBrows|$stmtB|\n";}
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$full_name =		$aryB[0];
		}
	$sthB->finish();

	$stmtB = "SELECT vendor_lead_code,list_id,first_name,last_name,address1,address2,address3,city,state,country_code,security_phrase from vicidial_list where lead_id=$lead_id;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($DBX) {print "$sthBrows|$stmtB|\n";}
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$vendor_lead_code =		$aryB[0];
		$list_id =				$aryB[1];
		$first_name =			$aryB[2];
		$last_name =			$aryB[3];
		$address1 =				$aryB[4];
		$address2 =				$aryB[5];
		$address3 =				$aryB[6];
		$city =					$aryB[7];
		$state =				$aryB[8];
		$country_code =			$aryB[9];
		$security_phrase =		$aryB[10];
		$TOTAL_LEADS++;
		}
	$sthB->finish();

	$call_data = "$call_date\t$phone_number\t$status\t$user\t$full_name\t$campaign_id\t$vendor_lead_code\t$list_id\t$first_name\t$last_name\t$address1\t$address2\t$address3\t$city\t$state\t$country_code\t$security_phrase\t$length_in_sec";

	$Ealert .= "$TOTAL_LEADS   $rec_countB   $call_data\n"; 

	if ($DBX) {print "$TOTAL_LEADS   $rec_countB   $call_data\n";}

	open(Sout, ">>$PATHhome/$file_date$US$ingroups.txt")
			|| die "Can't open $file_date$US$ingroups.txt: $!\n";
	print Sout "$call_data\n";
	close(Sout);


	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /200$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /300$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /400$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /500$/i) {print STDERR " GATHE* $rec_count\r";}
		if ($rec_count =~ /600$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /700$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /800$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /900$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /000$/i) {print STDERR " *ATHER $rec_count\r";}
		if ($rec_count =~ /000$/i) {print "        |$rec_count|$TOTAL_LEADS|         |$lead_id|\n";}
		}
	}

exit;

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
