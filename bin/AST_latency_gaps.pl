#!/usr/bin/perl
#
# AST_latency_gaps.pl    version 2.12
#
# DESCRIPTION:
# - checks for latency gaps in agent screen logs
#
# This script can be run manually as needed, or put into the crontab on one server
#
#	Latency connection problem detector program:
#	- Live-Mode: check on vicidial_live_agents agents, check for the last minute of latency log records, count number, if less than 55 then run deeper analysis
#	- Recent-Mode: check on 30 seconds ago to 90 seconds ago, if less than 55 records then run deeper analysis
#	- Long-Recent-Mode: check on agents that were logged in 1 minute ago to 62 minutes ago, if less than X records, run further analysis
#	- 24-hour-Mode: run at TEOD check for each agent LOGIN to LOGOUT, count number records comparing against number of seconds logged in
#
#	Settings Container settings:
#		minimum_gap => 10
#		email_sender => info@vicidial.com
#		email_list => info@vicidial.com,test@vicidial.com
#		email_subject => Agent Latency Gap Detected
#
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 230430-0800 - First build
#

$build = '230430-0800';
$MT[0]='';
$minimum_gap=10;
$CLIminimum_gap='';
$check_user_sessions=0;
$vicidial_agent_latency_log = 'vicidial_agent_latency_log';
$gaps_found=0;
$email_gaps_notice=0;
$email_message='';
$day_end_report=0;
$day_end_hours=18;
$day_end_groups='';
$group_groups[0]='';
$group_email[0]='';
$group_email_message[0]='';
$group_group_count=0;
$group_group_list='|';

use Time::Local;

### begin parsing run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--version/i)
		{
		print "build: $build\n";

		exit;
		}
	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [-q] = quiet\n";
		print "  [-t] = test\n";
		print "  [--version] = show version of this script\n";
		print "  [--help] = this screen\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Extra debugging messages\n";
		print "  [--container=XXX] = REQUIRED, the specifications to run these checks with\n";
		print "  [--live] = check current logged-in agents only for the last 2 minutes\n";
		print "  [--fresh] = check agent records for 30-150 seconds ago\n";
		print "  [--recent] = check agent records for 1-63 minutes ago\n";
		print "  [--24-hours] = check agent records for the last 24 hours(until last timeclock-end-of-day)\n";
		print "  [--date=XXX] = check agent records for this specific date YYYY-MM-DD\n";
		print "  [--minimum-gap=XXX] = override settings container with this gap seconds\n";
		print "  [--check-user-sessions] = check by all user sessions (recommended only for --date=X or --24-hours runs)\n";
		print "  [--email-gaps-notice] = if gaps are found, send email using container settings\n";
		print "  [--day-end-report] = only run day-end report, now\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /-q/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUGGING -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- EXTRA DEBUGGING -----\n\n";
			}
		if ($args =~ /-t|--test/i)
			{
			$T=1; $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--container=/i)
			{
			@data_in = split(/--container=/,$args);
			$container = $data_in[1];
			$container =~ s/ .*$//gi;
			if ($Q < 1)
				{print "\n----- SETTINGS CONTAINER: $container -----\n\n";}
			}
		if (length($container) < 1)
			{
			print "ERROR! Invalid Settings Container: $container     Exiting...\n";
			exit;
			}
		if ($args =~ /--date=/i)
			{
			@data_in = split(/--date=/,$args);
			$date = $data_in[1];
			$date =~ s/ .*$//gi;
			if ($Q < 1)
				{print "\n----- DATE SET: $date -----\n\n";}
			}
		if ($args =~ /--minimum-gap=/i)
			{
			@data_in = split(/--minimum-gap=/,$args);
			$CLIminimum_gap = $data_in[1];
			$CLIminimum_gap =~ s/ .*$//gi;
			$CLIminimum_gap =~ s/\D//gi;
			if ($Q < 1)
				{print "\n----- MINIMUM GAP OVERRIDE SET: $CLIminimum_gap -----\n\n";}
			}
		if ($args =~ /--live/i)
			{
			$check_live=1;
			if ($Q < 1)
				{print "\n----- LIVE CHECK -----\n\n";}
			}
		if ($args =~ /--fresh/i)
			{
			$check_fresh=1;
			if ($Q < 1)
				{print "\n----- FRESH CHECK -----\n\n";}
			}
		if ($args =~ /--recent/i)
			{
			$check_recent=1;
			if ($Q < 1)
				{print "\n----- RECENT CHECK -----\n\n";}
			}
		if ($args =~ /--24-hours/i)
			{
			$check_oneday=1;
			if ($Q < 1)
				{print "\n----- 24-HOUR CHECK -----\n\n";}
			}
		if ($args =~ /--check-user-sessions/i)
			{
			$check_user_sessions=1;
			if ($Q < 1)
				{print "\n----- CHECK BY USER SESSIONS: $check_user_sessions -----\n\n";}
			}
		if ($args =~ /--email-gaps-notice/i)
			{
			$email_gaps_notice=1;
			if ($Q < 1)
				{print "\n----- EMAIL GAPS NOTICE: $email_gaps_notice -----\n\n";}
			}
		if ($args =~ /--day-end-report/i)
			{
			$day_end_report=1;
			if ($Q < 1)
				{print "\n----- DAY END REPORT: $day_end_report -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set   Exiting...\n";
	}
### end parsing run-time options ###

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NOW="$year-$mon-$mday $hour:$min:$sec";
$SQLdate_MIDNIGHT="$year-$mon-$mday 00:00:00";

### get date-time of 24 hours ago ###
$VDL_one = ($secX - (60 * 1440));
($Dsec,$Dmin,$Dhour,$Dmday,$Dmon,$Dyear,$Dwday,$Dyday,$Disdst) = localtime($VDL_one);
$Dyear = ($Dyear + 1900);
$Dmon++;
if ($Dmon < 10) {$Dmon = "0$Dmon";}
if ($Dmday < 10) {$Dmday = "0$Dmday";}
if ($Dhour < 10) {$Dhour = "0$Dhour";}
if ($Dmin < 10) {$Dmin = "0$Dmin";}
if ($Dsec < 10) {$Dsec = "0$Dsec";}
$VDL_day = "$Dyear-$Dmon-$Dmday $Dhour:$Dmin:$Dsec";

### get date-time of one hour ago ###
if ($check_live > 0) 
	{
	$VDL_secBEGIN = ($secX - 0);
	$VDL_secEND = ($secX - 120);
	$query_length_check = ($VDL_secBEGIN - $VDL_secEND);
	}
if ($check_fresh > 0) 
	{
	$VDL_secBEGIN = ($secX - 30);
	$VDL_secEND = ($secX - 150);
	$query_length_check = ($VDL_secBEGIN - $VDL_secEND);
	}
if ($check_recent > 0) 
	{
	$VDL_secBEGIN = ($secX - (60 * 1));
	$VDL_secEND = ($secX - (60 * 63));
	$query_length_check = ($VDL_secBEGIN - $VDL_secEND);
	}
if ($check_oneday > 0) 
	{
	$VDL_secBEGIN = ($secX - (60 * 1));
	$VDL_secEND = ($secX - (60 * 1441));
	$query_length_check = ($VDL_secBEGIN - $VDL_secEND);
	}
if (length($date) > 9) 
	{
	$vicidial_agent_latency_log = 'vicidial_agent_latency_log_archive';
	$query_length_check = 86400;
	$VDL_hourEND = "$date 00:00:00";
		@cli_dateB = split("-",$date);
		$cli_dateB[1] = ($cli_dateB[1] - 1);
		$VDL_secEND = timelocal(0,0,0,$cli_dateB[2],$cli_dateB[1],$cli_dateB[0]);
	$XDL_hourBEGIN = "$date 23:59:59";
		$VDL_secBEGIN = timelocal(59,59,23,$cli_dateB[2],$cli_dateB[1],$cli_dateB[0]);
	$VDL_day = $VDL_hourEND;
	}

# generate begin date/time
($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_secBEGIN);
$Vyear = ($Vyear + 1900);
$Vmon++;
if ($Vmon < 10) {$Vmon = "0$Vmon";}
if ($Vmday < 10) {$Vmday = "0$Vmday";}
if ($Vhour < 10) {$Vhour = "0$Vhour";}
if ($Vmin < 10) {$Vmin = "0$Vmin";}
if ($Vsec < 10) {$Vsec = "0$Vsec";}
$VDL_hourBEGIN = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

# generate end date/time
($Xsec,$Xmin,$Xhour,$Xmday,$Xmon,$Xyear,$Xwday,$Xyday,$Xisdst) = localtime($VDL_secEND);
$Xyear = ($Xyear + 1900);
$Xmon++;
if ($Xmon < 10) {$Xmon = "0$Xmon";}
if ($Xmday < 10) {$Xmday = "0$Xmday";}
if ($Xhour < 10) {$Xhour = "0$Xhour";}
if ($Xmin < 10) {$Xmin = "0$Xmin";}
if ($Xsec < 10) {$Xsec = "0$Xsec";}
$XDL_hourEND = "$Xyear-$Xmon-$Xmday $Xhour:$Xmin:$Xsec";


if (!$Q) {print "TEST\n\n";}
if (!$Q) {print "NOW DATETIME:         $SQLdate_NOW\n";}
if (!$Q) {print "ANALYSIS DATE RANGE:  $VDL_hourBEGIN - $XDL_hourEND\n";}

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
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass", {PrintError => 0, RaiseError => 0})
 or die "Couldn't connect to database: " . DBI->errstr;

### Grab container content from the database
$container_sql='';
$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id = '$container';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$container_sql = $aryA[0];
	}
$sthA->finish();

if (length($container_sql)>5) 
	{
	@container_lines = split(/\n/,$container_sql);
	$i=0;
	foreach(@container_lines)
		{
		$container_lines[$i] =~ s/;.*//gi;
		if (length($container_lines[$i])>5)
			{
			if ($container_lines[$i] =~ /^minimum_gap/i)
				{
				$minimum_gap = $container_lines[$i];
				$minimum_gap =~ s/minimum_gap=>|minimum_gap => //gi;
				$minimum_gap =~ s/\D//gi;
				if($DBX){print "Minimum Gap set: $minimum_gap \n";}
				}
			if ($container_lines[$i] =~ /^email_sender/i)
				{
				$email_sender = $container_lines[$i];
				$email_sender =~ s/email_sender=>|email_sender => //gi;
				$email_sender =~ s/\n|\r|\t//gi;
				if($DBX){print "Email Sender set: $email_sender \n";}
				}
			if ($container_lines[$i] =~ /^email_list/i)
				{
				$email_list = $container_lines[$i];
				$email_list =~ s/email_list=>|email_list => //gi;
				$email_list =~ s/\n|\r|\t//gi;
				if($DBX){print "Email List set: $email_list \n";}
				}
			if ($container_lines[$i] =~ /^email_subject/i)
				{
				$email_subject = $container_lines[$i];
				$email_subject =~ s/email_subject=>|email_subject => //gi;
				$email_subject =~ s/\n|\r|\t//gi;
				if($DBX){print "Email Subject set: $email_subject \n";}
				}

			if ( ($container_lines[$i] =~ /^day_end_minimum_gap/i) && ($day_end_report > 0) )
				{
				$minimum_gap = $container_lines[$i];
				$minimum_gap =~ s/day_end_minimum_gap=>|day_end_minimum_gap => //gi;
				$minimum_gap =~ s/\D//gi;
				if($DBX){print "Day End Minimum Gap set: $minimum_gap \n";}
				}
			if ( ($container_lines[$i] =~ /^day_end_email_list/i) && ($day_end_report > 0) )
				{
				$email_list = $container_lines[$i];
				$email_list =~ s/day_end_email_list=>|day_end_email_list => //gi;
				$email_list =~ s/\n|\r|\t//gi;
				if($DBX){print "Day End Email List set: $email_list \n";}
				}
			if ( ($container_lines[$i] =~ /^day_end_email_subject/i) && ($day_end_report > 0) )
				{
				$email_subject = $container_lines[$i];
				$email_subject =~ s/day_end_email_subject=>|day_end_email_subject => //gi;
				$email_subject =~ s/\n|\r|\t//gi;
				if($DBX){print "Day End Email Subject set: $email_subject \n";}
				}
			if ( ($container_lines[$i] =~ /^day_end_hours/i) && ($day_end_report > 0) )
				{
				$day_end_hours = $container_lines[$i];
				$day_end_hours =~ s/day_end_hours=>|day_end_hours => //gi;
				$day_end_hours =~ s/\n|\r|\t//gi;
				if($DBX){print "Day End Hours set: $day_end_hours \n";}
				}
			if ( ($container_lines[$i] =~ /^day_end_groups/i) && ($day_end_report > 0) )
				{
				$day_end_groups = $container_lines[$i];
				$day_end_groups =~ s/day_end_groups=>|day_end_groups => //gi;
				$day_end_groups =~ s/\n|\r|\t//gi;
				if($DBX){print "Day End User Groups set: $day_end_groups \n";}
				}
			if ($container_lines[$i] =~ /^group\d\d_groups/i)
				{
				$temp_group_groups = $container_lines[$i];
				$temp_group_groups =~ s/^group\d\d_groups=>|^group\d\d_groups => //gi;
				$temp_group_groups =~ s/\n|\r|\t//gi;
				$temp_group_groups_num = $container_lines[$i];
				$temp_group_groups_num =~ s/^group|_groups.*//gi;
				$temp_group_groups_num = ($temp_group_groups_num - 1);
				$group_groups[$temp_group_groups_num] = ",$temp_group_groups,";
				$group_email_message[$temp_group_groups_num]='';
				if ($group_group_list !~ /\|$temp_group_groups\|/) 
					{
					$group_group_list .= "$temp_group_groups|";
					$group_group_count++;
					}
				if($DBX){print "User Group Email User Group Restriction set: $temp_group_groups_num|$group_groups[$temp_group_groups_num]|$group_group_count| \n";}
				}
			if ($container_lines[$i] =~ /^group\d\d_email/i)
				{
				$temp_group_email = $container_lines[$i];
				$temp_group_email =~ s/^group\d\d_email=>|^group\d\d_email => //gi;
				$temp_group_email =~ s/\n|\r|\t//gi;
				$temp_group_email_num = $container_lines[$i];
				$temp_group_email_num =~ s/^group|_email.*//gi;
				$temp_group_email_num = ($temp_group_email_num - 1);
				$group_email[$temp_group_email_num] = $temp_group_email;
				if($DBX){print "User Group Email Group set: $temp_group_email_num|$group_email[$temp_group_email_num]| \n";}
				}
			}
		$i++;
		}
	}
else
	{
	if ($Q < 1)
		{print "ERROR: SETTINGS CONTAINER EMPTY: $container $container_sql\n";}
	}

if (length($CLIminimum_gap) > 0) 
	{$minimum_gap = $CLIminimum_gap;}

$query_length_check_net = ($query_length_check - $minimum_gap);
if ($DB) {print "Net check seconds:  $query_length_check_net = ($query_length_check - $minimum_gap)\n";}

$VDL_secBEGIN_master = $VDL_secBEGIN;
$VDL_hourBEGIN_master = $VDL_hourBEGIN;
$VDL_secEND_master = $VDL_secEND;
$XDL_hourEND_master = $XDL_hourEND;



##### BEGIN run the Day End Report #####
if ($day_end_report > 0) 
	{
	### get date-time of X hours ago ###
	$DE_sec = ($secX - (60 * 60 * $day_end_hours));
	($Dsec,$Dmin,$Dhour,$Dmday,$Dmon,$Dyear,$Dwday,$Dyday,$Disdst) = localtime($DE_sec);
	$Dyear = ($Dyear + 1900);
	$Dmon++;
	if ($Dmon < 10) {$Dmon = "0$Dmon";}
	if ($Dmday < 10) {$Dmday = "0$Dmday";}
	if ($Dhour < 10) {$Dhour = "0$Dhour";}
	if ($Dmin < 10) {$Dmin = "0$Dmin";}
	if ($Dsec < 10) {$Dsec = "0$Dsec";}
	$DE_date = "$Dyear-$Dmon-$Dmday $Dhour:$Dmin:$Dsec";

	$day_end_groupsSQL='';
	if (length($day_end_groups) > 0) 
		{
		$day_end_groupsSQL = $day_end_groups;
		$day_end_groupsSQL =~ s/,|\|/','/gi;
		$day_end_groupsSQL = "and user_group IN('$day_end_groupsSQL')";
		}
	if ($DB) {print "Running the day-end report, for $day_end_hours hours ($day_end_groupsSQL) \n";}

	$stmtA = "SELECT user,user_ip,gap_date,gap_length FROM vicidial_latency_gaps where gap_date > \"$DE_date\" and (gap_length >= $minimum_gap) order by gap_date limit 1000;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DB) {print "|$sthArows|$stmtA|\n";}
	$ug=0;
	while ($sthArows > $ug)
		{
		@aryA = $sthA->fetchrow_array;
		$DEuser[$ug] =			$aryA[0];
		$DEuser_ip[$ug] =		$aryA[1];
		$DEgap_date[$ug] =		$aryA[2];
		$DEgap_length[$ug] =	$aryA[3];
		$ug++;
		}
	$sthA->finish();
	if ($DB) {print "Gaps found:  $ug |$usersSQL|\n";}

	if ($ug > 0) 
		{
		$ug=0;
		while ($sthArows > $ug)
			{
			$temp_ug = ($ug + 1);
			$user_fullname = '';
			$stmtA = "SELECT full_name,user_group FROM vicidial_users where user='$DEuser[$ug]' $day_end_groupsSQL limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsU=$sthA->rows;
			if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
			if ($sthArowsU > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$user_fullname = " - $aryA[0]";
				$email_message .= "\nGap Detected: $temp_ug \nUser:$DEuser[$ug]$user_fullname \nWeb IP: $DEuser_ip[$ug] \nGap Start: $DEgap_date[$ug] \nGap Length: $DEgap_length[$ug] \n";
				if ($DBX) {print "LG Gap Debug:  $DEuser[$ug]$user_fullname|$DEuser_ip[$ug]|$DEgap_date[$ug]|$DEgap_length[$ug]|\n";}
				}
			else
				{
				if ($DBX) {print "LG Gap Debug Excluded:  $DEuser[$ug]$user_fullname|$DEuser_ip[$ug]|$DEgap_date[$ug]|$DEgap_length[$ug]|\n";}
				}
			$sthA->finish();

			$ug++;
			}
		$sthA->finish();
		}

	if ( ($ug > 0) && (length($email_message)>5) && (length($email_list) > 3) && (length($email_sender) > 3) && (length($email_subject) > 3) )
		{
		if (!$Q) {print "Sending email: $email_list\n";}

		use MIME::QuotedPrint;
		use MIME::Base64;
		use Mail::Sendmail;

		%mail = ( To      => "$email_list",
							From    => "$email_sender",
							Subject => "$email_subject",
					   );
		$boundary = "====" . time() . "====";
		$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

		$message = encode_qp($email_message );

		$boundary = '--'.$boundary;
		$mail{body} .= "$boundary\n";
		$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
		$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
		$mail{body} .= "$message\n";
		$mail{body} .= "$boundary\n";
		$mail{body} .= "--\n";

		sendmail(%mail) or die $mail::Sendmail::error;
		if (!$Q) {print "ok. log says:\n", $mail::sendmail::log;}  ### print mail log for status
		}
	exit;
	}
##### END run the Day End Report #####



##### BEGIN run check for LIVE AGENTS #####
# $stmtA = "SELECT user FROM vicidial_live_agents where ra_user='' order by user;";
if ($check_live > 0) 
	{
	$VDL_secBEGIN_master = $VDL_secBEGIN;
	$VDL_hourBEGIN_master = $VDL_hourBEGIN;
	$VDL_secEND_master = $VDL_secEND;
	$XDL_hourEND_master = $XDL_hourEND;

	$stmtA = "SELECT user FROM vicidial_live_agents where ra_user='' order by user;";
	$active_users='';
	$active_users_count=0;
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DB) {print "|$sthArows|$stmtA|\n";}
	while ($sthArows > $active_users_count)
		{
		@aryA = $sthA->fetchrow_array;
		$users[$active_users_count] =	"$aryA[0]";
		$hourEND[$active_users_count] = $XDL_hourEND;
		$secEND[$active_users_count] =	$VDL_secEND;
		$usersSQL .= "'$aryA[0]',";
		$active_users_count++;
		}
	$sthA->finish();
	if (length($usersSQL) > 3) {$usersSQL =~ s/,$//gi;}
	if ($DB) {print "Active Users:  $active_users_count |$usersSQL|\n";}

	$i=0;
	while ($active_users_count > $i) 
		{
		$VDL_secBEGIN = $VDL_secBEGIN_master;
		$VDL_hourBEGIN = $VDL_hourBEGIN_master;
		$VDL_secEND = $secEND[$i];
		$XDL_hourEND = $hourEND[$i];
		if ($DB) {print "\nAnalyzing user activity:  $users[$i] ($i)    starting at $XDL_hourEND ($VDL_secEND)\n";}
		$ping_count=0;
		$stmtA = "SELECT count(*) FROM $vicidial_agent_latency_log where user='$users[$i]' and log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "|$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ping_count = $aryA[0];
			}
		$sthA->finish();

		if ($ping_count < $query_length_check_net) 
			{
			$last_login_date = $XDL_hourEND;
			$last_login_date_epoch = $VDL_secEND;
			$last_logout_date = $VDL_hourBEGIN;
			$last_logout_date_epoch = $VDL_secBEGIN;
			# checking this user for last login

			$last_login_date_epoch = 0;
			$stmtA = "SELECT event_date,UNIX_TIMESTAMP(event_date) FROM vicidial_user_log where user='$users[$i]' and event='LOGIN' and event_date <= \"$VDL_hourBEGIN\" and event_date > \"$VDL_day\" order by event_date desc limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsU=$sthA->rows;
			if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
			if ($sthArowsU > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$last_login_date =			$aryA[0];
				$last_login_date_epoch =	$aryA[1];
				}
			$sthA->finish();

			# checking this user for last logout, if after last login and before the end of the check time
			$stmtA = "SELECT event_date,UNIX_TIMESTAMP(event_date) FROM vicidial_user_log where user='$users[$i]' and event IN('LOGOUT','TIMEOUTLOGOUT') and event_date <= \"$VDL_hourBEGIN\" and event_date > \"$last_login_date\" and event_date > \"$XDL_hourEND\" order by event_date limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsU=$sthA->rows;
			if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
			if ($sthArowsU > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$last_logout_date =			$aryA[0];
				$last_logout_date_epoch =	$aryA[1];
				if ($DB) {print "Last Logout within check range, overriding check begin: |$users[$i]|$last_logout_date|   |$VDL_hourBEGIN|$XDL_hourEND|\n";}

				$VDL_secBEGIN = $last_logout_date_epoch;
				$VDL_hourBEGIN = $last_logout_date;
				}
			$sthA->finish();

			if ($DBX) {print "Last Login check: |$users[$i]|$last_login_date|   |($last_login_date_epoch >= $VDL_secEND) && ($last_login_date_epoch <= $VDL_secBEGIN)|\n";}
			if ( ($last_login_date_epoch >= $VDL_secEND) && ($last_login_date_epoch <= $VDL_secBEGIN) ) 
				{
				if ($DB) {print "Last Login within check range, overriding check end: |$users[$i]|$last_login_date|   |$VDL_hourBEGIN|$XDL_hourEND|\n";}

				$VDL_secEND = $last_login_date_epoch;
				$XDL_hourEND = $last_login_date;
				}

			# run through all latency logs looking for gap
			$last_ping_date='1970-01-01 00:00:00';
			$last_ping_date_epoch=0;
			$last_ping_web_ip='';
			$last_ping_record=0;
			$temp_ping_gap_test=0;
			$stmtA = "SELECT log_date,UNIX_TIMESTAMP(log_date),web_ip FROM $vicidial_agent_latency_log where user='$users[$i]' and log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX) {print "|$sthArows|$stmtA|\n";}
			$p=0;
			while ($sthArows > $p)
				{
				@aryA = $sthA->fetchrow_array;
				if ($last_ping_record != $aryA[1])
					{
					if ($last_ping_date_epoch < 1) 
						{
						$last_ping_date =		$aryA[0];
						$last_ping_date_epoch = $aryA[1];
						$last_ping_web_ip =		$aryA[2];
						$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
						$last_ping_record =		$aryA[1];
						}
					else
						{
						if ( ($aryA[1] >= $temp_ping_gap_test) && (length($last_ping_web_ip) > 3) )
							{
							# minimum gap flagged
							$gaps_found++;
							if ($DB) {print "Ping gap found! |$aryA[1]|$temp_ping_gap_test|\n";}
							$temp_gap_sec = ($aryA[1] - $last_ping_date_epoch);

							### Set all filled vicidial_demographic_quotas_goals records for this campaign to FPENDING status ahead of updates
							$stmtA = "INSERT IGNORE INTO vicidial_latency_gaps SET user='$users[$i]',user_ip='$last_ping_web_ip',gap_date='$last_ping_date',gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW' ON DUPLICATE KEY UPDATE gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "LC DEBUG:  $affected_rows|$stmtA|\n";}

							if ($affected_rows eq '1') 
								{
								$user_fullname = '';
								$user_user_group = '';
								$stmtA = "SELECT full_name,user_group FROM vicidial_users where user='$users[$i]' limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsU=$sthA->rows;
								if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
								if ($sthArowsU > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$user_fullname = " - $aryA[0]";
									$user_user_group = $aryA[1];
									}
								$sthA->finish();
								if (length($group_groups[0]) > 0) 
									{
									$ggc=0;
									while ($ggc < $group_group_count) 
										{
										if ($group_groups[$ggc] =~ /,$user_user_group,/)
											{
											$group_email_message[$ggc] .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
											}
										$ggc++;
										}
									}
								else
									{
									$email_message .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
									}
								}
							$last_ping_date =		$aryA[0];
							$last_ping_date_epoch = $aryA[1];
							$last_ping_web_ip =		$aryA[2];
							$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
							$last_ping_record =		$aryA[1];
							}
						else
							{
							$last_ping_date =		$aryA[0];
							$last_ping_date_epoch = $aryA[1];
							$last_ping_web_ip =		$aryA[2];
							$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
							$last_ping_record =		$aryA[1];
							}
						}
					}
				$p++;
				}
			$sthA->finish();

			if ( ($VDL_secBEGIN >= $temp_ping_gap_test) && ($p > 0) && (length($last_ping_web_ip) > 3) )
				{
				# minimum gap flagged
				$gaps_found++;
				if ($DB) {print "Ping gap found! |$VDL_secBEGIN|$temp_ping_gap_test|\n";}
				$temp_gap_sec = ($VDL_secBEGIN - $last_ping_date_epoch);

				### Insert log record for latency gap
				$stmtA = "INSERT IGNORE INTO vicidial_latency_gaps SET user='$users[$i]',user_ip='$last_ping_web_ip',gap_date='$last_ping_date',gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW' ON DUPLICATE KEY UPDATE gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "LC DEBUG:  $affected_rows|$stmtA|\n";}
				if ($affected_rows eq '1') 
					{
					$user_fullname = '';
					$user_user_group = '';
					$stmtA = "SELECT full_name,user_group FROM vicidial_users where user='$users[$i]' limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsU=$sthA->rows;
					if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
					if ($sthArowsU > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$user_fullname = " - $aryA[0]";
						$user_user_group = $aryA[1];
						}
					$sthA->finish();

					if (length($group_groups[0]) > 0) 
						{
						$ggc=0;
						while ($ggc < $group_group_count) 
							{
							if ($group_groups[$ggc] =~ /,$user_user_group,/)
								{
								$group_email_message[$ggc] .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
								}
							$ggc++;
							}
						}
					else
						{
						$email_message .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
						}
					}
				}
			}
		else
			{
			if ($DBX) {print "LC DEBUG: normal ping count for user: $users[$i] {$ping_count >= $query_length_check_net}\n";}
			}
		$i++;
		}
	}
##### END run check for LIVE AGENTS #####




##### BEGIN time-based checks within last 24 hours #####
# 	$stmtA = "SELECT distinct user FROM $vicidial_agent_latency_log where log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\" order by user;";
# 	$stmtA = "SELECT distinct user FROM $vicidial_agent_latency_log where log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\" order by user;";
if ( ($check_fresh > 0) || ($check_recent > 0) || ($check_oneday > 0) || (length($date) > 9) )
	{
	$VDL_secBEGIN_master = $VDL_secBEGIN;
	$VDL_hourBEGIN_master = $VDL_hourBEGIN;
	$VDL_secEND_master = $VDL_secEND;
	$XDL_hourEND_master = $XDL_hourEND;

	if ($check_user_sessions > 0) 
		{
		$stmtA = "SELECT user,event_date,UNIX_TIMESTAMP(event_date) FROM vicidial_user_log where event='LOGIN' and event_date <= \"$VDL_hourBEGIN\" and event_date > \"$XDL_hourEND\" order by user,event_date limit 1000000;";
		$active_users='';
		$active_users_count=0;
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DB) {print "|$sthArows|$stmtA|\n";}
		while ($sthArows > $active_users_count)
			{
			@aryA = $sthA->fetchrow_array;
			$users[$active_users_count] =	"$aryA[0]";
			$hourEND[$active_users_count] = "$aryA[1]";
			$secEND[$active_users_count] =	"$aryA[2]";
			$usersSQL .= "'$aryA[0]',";
			$active_users_count++;
			}
		$sthA->finish();
		if (length($usersSQL) > 3) {$usersSQL =~ s/,$//gi;}
		if ($DB) {print "Active User Sessions:  $active_users_count |$usersSQL|\n";}
		}
	else
		{
		$stmtA = "SELECT distinct user FROM $vicidial_agent_latency_log where log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\" order by user;";
		$active_users='';
		$active_users_count=0;
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DB) {print "|$sthArows|$stmtA|\n";}
		while ($sthArows > $active_users_count)
			{
			@aryA = $sthA->fetchrow_array;
			$users[$active_users_count] =	"$aryA[0]";
			$hourEND[$active_users_count] = $XDL_hourEND;
			$secEND[$active_users_count] =	$VDL_secEND;
			$usersSQL .= "'$aryA[0]',";
			$active_users_count++;
			}
		$sthA->finish();
		if (length($usersSQL) > 3) {$usersSQL =~ s/,$//gi;}
		if ($DB) {print "Active Users:  $active_users_count |$usersSQL|\n";}
		}

	$i=0;
	while ($active_users_count > $i) 
		{
		$VDL_secBEGIN = $VDL_secBEGIN_master;
		$VDL_hourBEGIN = $VDL_hourBEGIN_master;
		$VDL_secEND = $secEND[$i];
		$XDL_hourEND = $hourEND[$i];
		if ($DB) {print "\nAnalyzing user activity:  $users[$i] ($i)    starting at $XDL_hourEND ($VDL_secEND)\n";}
		$ping_count=0;
		$stmtA = "SELECT count(*) FROM $vicidial_agent_latency_log where user='$users[$i]' and log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "|$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ping_count = $aryA[0];
			}
		$sthA->finish();

		if ($ping_count < $query_length_check_net) 
			{
			$last_login_date = $XDL_hourEND;
			$last_login_date_epoch = $VDL_secEND;
			$last_logout_date = $VDL_hourBEGIN;
			$last_logout_date_epoch = $VDL_secBEGIN;
			# checking this user for last login
			if ($check_user_sessions < 1) 
				{
				$last_login_date_epoch = 0;
				$stmtA = "SELECT event_date,UNIX_TIMESTAMP(event_date) FROM vicidial_user_log where user='$users[$i]' and event='LOGIN' and event_date <= \"$VDL_hourBEGIN\" and event_date > \"$VDL_day\" order by event_date desc limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsU=$sthA->rows;
				if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
				if ($sthArowsU > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$last_login_date =			$aryA[0];
					$last_login_date_epoch =	$aryA[1];
					}
				$sthA->finish();
				}

			# checking this user for last logout, if after last login and before the end of the check time
			$stmtA = "SELECT event_date,UNIX_TIMESTAMP(event_date) FROM vicidial_user_log where user='$users[$i]' and event IN('LOGOUT','TIMEOUTLOGOUT') and event_date <= \"$VDL_hourBEGIN\" and event_date > \"$last_login_date\" and event_date > \"$XDL_hourEND\" order by event_date limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsU=$sthA->rows;
			if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
			if ($sthArowsU > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$last_logout_date =			$aryA[0];
				$last_logout_date_epoch =	$aryA[1];
				if ($DB) {print "Last Logout within check range, overriding check begin: |$users[$i]|$last_logout_date|   |$VDL_hourBEGIN|$XDL_hourEND|\n";}

				$VDL_secBEGIN = $last_logout_date_epoch;
				$VDL_hourBEGIN = $last_logout_date;
				}
			$sthA->finish();

			if ($DBX) {print "Last Login check: |$users[$i]|$last_login_date|   |($last_login_date_epoch >= $VDL_secEND) && ($last_login_date_epoch <= $VDL_secBEGIN)|\n";}
			if ( ($last_login_date_epoch >= $VDL_secEND) && ($last_login_date_epoch <= $VDL_secBEGIN) ) 
				{
				if ($DB) {print "Last Login within check range, overriding check end: |$users[$i]|$last_login_date|   |$VDL_hourBEGIN|$XDL_hourEND|\n";}

				$VDL_secEND = $last_login_date_epoch;
				$XDL_hourEND = $last_login_date;
				}

			# run through all latency logs looking for gap
			$last_ping_date='1970-01-01 00:00:00';
			$last_ping_date_epoch=0;
			$last_ping_web_ip='';
			$last_ping_record=0;
			$temp_ping_gap_test=0;
			$stmtA = "SELECT log_date,UNIX_TIMESTAMP(log_date),web_ip FROM $vicidial_agent_latency_log where user='$users[$i]' and log_date <= \"$VDL_hourBEGIN\" and log_date > \"$XDL_hourEND\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX) {print "|$sthArows|$stmtA|\n";}
			$p=0;
			while ($sthArows > $p)
				{
				@aryA = $sthA->fetchrow_array;
				if ($last_ping_record != $aryA[1])
					{
					if ($last_ping_date_epoch < 1) 
						{
						$last_ping_date =		$aryA[0];
						$last_ping_date_epoch = $aryA[1];
						$last_ping_web_ip =		$aryA[2];
						$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
						$last_ping_record =		$aryA[1];
						}
					else
						{
						if ( ($aryA[1] >= $temp_ping_gap_test) && (length($last_ping_web_ip) > 3) )
							{
							# minimum gap flagged
							$gaps_found++;
							if ($DB) {print "Ping gap found! |$aryA[1]|$temp_ping_gap_test|\n";}
							$temp_gap_sec = ($aryA[1] - $last_ping_date_epoch);

							### Set all filled vicidial_demographic_quotas_goals records for this campaign to FPENDING status ahead of updates
							$stmtA = "INSERT IGNORE INTO vicidial_latency_gaps SET user='$users[$i]',user_ip='$last_ping_web_ip',gap_date='$last_ping_date',gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW' ON DUPLICATE KEY UPDATE gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "LC DEBUG:  $affected_rows|$stmtA|\n";}

							if ($affected_rows eq '1') 
								{
								$user_fullname = '';
								$user_user_group = '';
								$stmtA = "SELECT full_name,user_group FROM vicidial_users where user='$users[$i]' limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsU=$sthA->rows;
								if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
								if ($sthArowsU > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$user_fullname = " - $aryA[0]";
									$user_user_group = $aryA[1];
									}
								$sthA->finish();

								if (length($group_groups[0]) > 0) 
									{
									$ggc=0;
									while ($ggc < $group_group_count) 
										{
										if ($group_groups[$ggc] =~ /,$user_user_group,/)
											{
											$group_email_message[$ggc] .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
											}
										$ggc++;
										}
									}
								else
									{
									$email_message .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
									}
								}
							$last_ping_date =		$aryA[0];
							$last_ping_date_epoch = $aryA[1];
							$last_ping_web_ip =		$aryA[2];
							$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
							$last_ping_record =		$aryA[1];
							}
						else
							{
							$last_ping_date =		$aryA[0];
							$last_ping_date_epoch = $aryA[1];
							$last_ping_web_ip =		$aryA[2];
							$temp_ping_gap_test = ($last_ping_date_epoch + $minimum_gap);
							$last_ping_record =		$aryA[1];
							}
						}
					}
				$p++;
				}
			$sthA->finish();

			if ( ($VDL_secBEGIN >= $temp_ping_gap_test) && ($p > 0) && (length($last_ping_web_ip) > 3) )
				{
				# minimum gap flagged
				$gaps_found++;
				if ($DB) {print "Ping gap found! |$VDL_secBEGIN|$temp_ping_gap_test|\n";}
				$temp_gap_sec = ($VDL_secBEGIN - $last_ping_date_epoch);

				### Insert log record for latency gap
				$stmtA = "INSERT IGNORE INTO vicidial_latency_gaps SET user='$users[$i]',user_ip='$last_ping_web_ip',gap_date='$last_ping_date',gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW' ON DUPLICATE KEY UPDATE gap_length='$temp_gap_sec',last_login_date='$last_login_date',check_date='$SQLdate_NOW';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "LC DEBUG:  $affected_rows|$stmtA|\n";}
				if ($affected_rows eq '1') 
					{
					$user_fullname = '';
					$user_user_group = '';
					$stmtA = "SELECT full_name,user_group FROM vicidial_users where user='$users[$i]' limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsU=$sthA->rows;
					if ($DBX) {print "|$sthArowsU|$stmtA|\n";}
					if ($sthArowsU > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$user_fullname = " - $aryA[0]";
						$user_user_group = $aryA[1];
						}
					$sthA->finish();

					if (length($group_groups[0]) > 0) 
						{
						$ggc=0;
						while ($ggc < $group_group_count) 
							{
							if ($group_groups[$ggc] =~ /,$user_user_group,/)
								{
								$group_email_message[$ggc] .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
								}
							$ggc++;
							}
						}
					else
						{
						$email_message .= "\nGap Detected: $gaps_found \nUser:$users[$i]$user_fullname \nWeb IP: $last_ping_web_ip \nGap Start: $last_ping_date \nGap Length: $temp_gap_sec \n";
						}
					}
				}
			}
		else
			{
			if ($DBX) {print "LC DEBUG: normal ping count for user: $users[$i] {$ping_count >= $query_length_check_net}\n";}
			}
		$i++;
		}
	}
##### END time-based checks within last 24 hours #####






$dbhA->disconnect();



if ( ($gaps_found > 0) && ($email_gaps_notice > 0) && (length($email_message)>5) && (length($email_list) > 3) && (length($email_sender) > 3) && (length($email_subject) > 3) )
	{
	if (!$Q) {print "Sending email: $email_list\n";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$email_subject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp($email_message );

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary\n";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	if (!$Q) {print "ok. log says:\n", $mail::sendmail::log;}  ### print mail log for status
	}


# emails for group groups
if ( ($gaps_found > 0) && ($group_group_count > 0) )
	{
	$ggc=0;
	while ($ggc < $group_group_count) 
		{
		if ( (length($group_email_message[$ggc]) > 10) && (length($group_email[$ggc]) > 5) && (length($email_sender) > 5) && (length($email_subject) > 1) )
			{
			if (!$Q) {print "Sending group group email: $group_email[$ggc]|$group_groups[$ggc]|\n";}

			use MIME::QuotedPrint;
			use MIME::Base64;
			use Mail::Sendmail;

			%mail = ( To      => "$group_email[$ggc]",
								From    => "$email_sender",
								Subject => "$email_subject",
						   );
			$boundary = "====" . time() . "====";
			$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

			$message = encode_qp($group_email_message[$ggc] );

			$boundary = '--'.$boundary;
			$mail{body} .= "$boundary\n";
			$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
			$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
			$mail{body} .= "$message\n";
			$mail{body} .= "$boundary\n";
			$mail{body} .= "--\n";

			sendmail(%mail) or die $mail::Sendmail::error;
			if (!$Q) {print "ok. log says:\n", $mail::sendmail::log;}  ### print mail log for status
			}
		$ggc++;
		}
	}

$secY = time();
$secZ = ($secY - $secX);

if (!$q) {print "\nDONE. Gaps found: $gaps_found   Script execution time in seconds: $secZ\n";}

if (!$Q) {print "Script exiting...\n";}
exit;
