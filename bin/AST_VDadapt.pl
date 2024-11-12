#!/usr/bin/perl
#
# AST_VDadapt.pl version 2.14
#
# DESCRIPTION:
# adjusts the auto_dial_level for vicidial adaptive-predictive campaigns. 
# gather call stats for campaigns and in-groups
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 60823-1302 - First build from AST_VDhopper.pl
# 60825-1734 - Functional alpha version, no loop
# 60826-0857 - Added loop and CLI flag options
# 60827-0035 - Separate Drop calculation and target dial level calculation into different subroutines
#            - Alter code so that DROP percentages would calculate only about once a minute no matter he loop delay
# 60828-1149 - Add field for target dial_level difference, -1 would target one agent waiting, +1 would target 1 customer waiting
# 60919-1243 - Changed variables to use arrays for all campaign-specific values
# 61215-1110 - Added answered calls stats and use drops as percentage of answered for today
# 70111-1600 - Added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70205-1429 - Added code for campaign_changedate and campaign_stats_refresh updates
# 70213-1221 - Added code for QueueMetrics queue_log QUEUESTART record
# 70219-1249 - Removed unused references to dial_status_x fields
# 70409-1219 - Removed CLOSER-type campaign restriction
# 70521-1038 - Fixed bug when no live campaigns are running, define $vicidial_log
# 70619-1339 - Added Status Category tally calculations
# 71029-1906 - Changed CLOSER-type campaign_id restriction
# 81021-2201 - Deactivated queue_log QUEUESTART event
# 81022-0713 - Added gathering of vicidial_inbound_groups stats(day only)
# 81108-0808 - Added more inbound stats with some debug output and added campaign agent non-pause time
# 90415-0925 - Fixed rare division by zero bug
# 90512-1549 - Formatting fixes and calculation bugs in blended
# 90628-2001 - Added drop rate group functions
# 91115-0929 - Added auto-kill of script at timeclock reset time of day to facilitate cleaner clearing of daily stats
# 91206-2203 - Added campaign_calldate within last 5 minute as an override to recalculate stats
# 100206-1453 - Fixed calculation of hold_sec stats (service level) for in-groups
# 110513-0721 - Added debug DB table, dial level and available only tally threshold options
# 111103-0626 - Added MAXCAL as a drop status
# 111219-1420 - Added daily stats updates to new table for total, in-group and campaign
# 111223-0924 - Added check for logged-in agents
# 131122-1314 - Added several missing sthA->finish
# 141109-1957 - Fixed issue #793
# 141113-1616 - Added concurrency check
# 151124-1235 - Added function to cache carrier log stats
# 160515-2016 - Added code for new UK OFCOM drop calculations
# 161102-1029 - Fixed QM partition problem
# 170724-2352 - Added option for cached hour counts for vicidial_log entries per campaign and carrier log totals
# 171221-1049 - Added caching of inbound call stats
# 180203-1728 - Added function to check for inbound callback queue calls to be placed
# 180519-2303 - Added Waiting Call On/Off URL feature for in-groups
# 190216-0809 - Fix for user-group, in-group and campaign allowed/permissions matching issues
# 190720-2122 - Added audit and fixing of missing outbound auto-dial logs if Call Quotas is enabled
# 190722-1004 - Added more logging and log-audit portions to Call Quotas log audit code
# 200811-1600 - Include live agents from other campaigns if they have the campaign Drop-InGroup selected and drop sec < 0
# 201106-2141 - Added calculation and caching of park_log stats per campaign/in-group
# 201214-0857 - Added SHARED_ campaign agent rotation functions
# 210207-1205 - Added more logging and debug code for SHARED agent campaigns
# 210707-2215 - Fixes for several rare logging and stats issues
# 211022-1638 - Added incall_tally_threshold_seconds campaign feature
# 212207-2207 - Added IQNANQ to drop SQL calculation queries
# 211122-1457 - Fix for logging bug and modification to drop percentage calculation
# 230309-1009 - Added abandon_check_queue feature
# 240219-1514 - Added vicidial_live_inbound_agents.daily_limit parameter
#

$build='240219-1514';
# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, On an active system this will generate lots of lines of output per minute
$US='__';
$MT[0]='';
$run_check=1; # concurrency check
$VLhour_counts=1; # use cached hour counts for vicidial_log entries per campaign
$VCLhour_counts=1; # use cached hour counts for vicidial_closer_log entries per in-group
$new_agent_multicampaign=1; # new process for handling multi-campaign agents

##### table definitions(used to force index usage for better performance):
	$vicidial_log = 'vicidial_log FORCE INDEX (call_date) ';
#	$vicidial_log = 'vicidial_log';
	$vicidial_closer_log = 'vicidial_closer_log FORCE INDEX (call_date) ';
#	$vicidial_closer_log = 'vicidial_closer_log';

$generate_carrier_stats=1;
$SSofcom_uk_drop_calc=0;

$i=0;
$daily_stats=1;
$drop_count_updater=0;
$shared_agent_count_updater=0;
$stat_it=15;
$diff_ratio_updater=0;
$stat_count=1;
$ofcom_uk_drop_calc_ANY=0;
$VCScalls_today[$i]=0;
$VCSdrops_today[$i]=0;
$VCSdrops_today_pct[$i]=0;
$VCScalls_hour[$i]=0;
$VCSdrops_hour[$i]=0;
$VCSdrops_hour_pct[$i]=0;
$VCScalls_halfhour[$i]=0;
$VCSdrops_halfhour[$i]=0;
$VCSdrops_halfhour_pct[$i]=0;
$VCScalls_five[$i]=0;
$VCSdrops_five[$i]=0;
$VCSdrops_five_pct[$i]=0;
$VCScalls_one[$i]=0;
$VCSdrops_one[$i]=0;
$VCSdrops_one_pct[$i]=0;
$total_agents[$i]=0;
$ready_agents[$i]=0;
$waiting_calls[$i]=0;
$ready_diff_total[$i]=0;
$waiting_diff_total[$i]=0;
$total_agents_total[$i]=0;
$ready_diff_avg[$i]=0;
$waiting_diff_avg[$i]=0;
$total_agents_avg[$i]=0;
$stat_differential[$i]=0;
$VCSINCALL[$i]=0;
$VCSREADY[$i]=0;
$VCSCLOSER[$i]=0;
$VCSPAUSED[$i]=0;
$VCSagents[$i]=0;
$VCSagents_calc[$i]=0;
$VCSagents_active[$i]=0;
$cwu_any_on=0;

# set to 61 initially so that a baseline drop count is pulled
$drop_count_updater=61;
# set to 61 initially so that a baseline shared agent process is run
$shared_agent_count_updater=61;

$secT = time();

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
		print "allowed run time options(must stay in this order):\n";
		print "  [--loops=XXX] = force a number of loops of XXX\n";
		print "  [--delay=XXX] = force a loop delay of XXX seconds\n";
		print "  [--campaign=XXX] = run for campaign XXX only\n";
		print "  [--no-daily-stats] = will not calculate daily stats for total, ingroup, campaign\n";
		print "  [--force] = force calculation of suggested predictive dial_level\n";
		print "  [--test] = test only, do not alter dial_level\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--debugXXX] = extra super debug\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--campaign=/i) # CLI defined campaign
			{
			@CLIvarARY = split(/--campaign=/,$args);
			@CLIvarARX = split(/ /,$CLIvarARY[1]);
			if (length($CLIvarARX[0])>2)
				{
				$CLIcampaign = $CLIvarARX[0];
				$CLIcampaign =~ s/\/$| |\r|\n|\t//gi;
				}
			else
				{$CLIcampaign = '';}
			@CLIvarARY=@MT;   @CLIvarARY=@MT;
			}
		else
			{$CLIcampaign = '';}
		if ($args =~ /--level=/i) # CLI defined level
			{
			@CLIvarARY = split(/--level=/,$args);
			@CLIvarARX = split(/ /,$CLIvarARY[1]);
			if (length($CLIvarARX[0])>2)
				{
				$CLIlevel = $CLIvarARX[0];
				$CLIlevel =~ s/\/$| |\r|\n|\t//gi;
				$CLIlevel =~ s/\D//gi;
				}
			else
				{$CLIlevel = '';}
			@CLIvarARY=@MT;   @CLIvarARY=@MT;
			}
		else
			{$CLIlevel = '';}
		if ($args =~ /--loops=/i) # CLI defined loops
			{
			@CLIvarARY = split(/--loops=/,$args);
			@CLIvarARX = split(/ /,$CLIvarARY[1]);
			if (length($CLIvarARX[0])>2)
				{
				$CLIloops = $CLIvarARX[0];
				$CLIloops =~ s/\/$| |\r|\n|\t//gi;
				$CLIloops =~ s/\D//gi;
				}
			else
				{$CLIloops = '1000000';}
			@CLIvarARY=@MT;   @CLIvarARY=@MT;
			}
		else
			{$CLIloops = '1000000';}
		if ($args =~ /--delay=/i) # CLI defined delay
			{
			@CLIvarARY = split(/--delay=/,$args);
			@CLIvarARX = split(/ /,$CLIvarARY[1]);
			if (length($CLIvarARX[0])>2)
				{
				$CLIdelay = $CLIvarARX[0];
				$CLIdelay =~ s/\/$| |\r|\n|\t//gi;
				$CLIdelay =~ s/\D//gi;
				}
			else
				{$CLIdelay = '1';}
			@CLIvarARY=@MT;   @CLIvarARY=@MT;
			}
		else
			{$CLIdelay = '1';}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n";
			print "----- SUPER DEBUG -----\n";
			print "VARS-\n";
			print "CLIcampaign- $CLIcampaign\n";
			print "CLIlevel-    $CLIlevel\n";
			print "CLIloops-    $CLIloops\n";
			print "CLIdelay-    $CLIdelay\n";
			print "\n";
			}
		if ($args =~ /--debugXXX/i)
			{
			$DBXXX=1;
			print "\n----- EXTRA SUPER DEBUG -----\n\n";
			}
		if ($args =~ /--force/i)
			{
			$force_test=1;
			print "\n----- FORCE TESTING -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--no-daily-stats/i)
			{
			$daily_stats=0;
			print "\n----- NO DAILY STATS -----\n\n";
			}
		}
	}
else
	{
	$CLIcampaign = '';
	$CLIlevel = '';
	$CLIloops = '1000000';
	$CLIdelay = '1';
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

if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use Time::Local;
use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

if ($DBX) {print "CONNECTED TO DATABASE:  $VARDB_server|$VARDB_database\n";}

##### gather relevent system settings
$stmtA = "SELECT cache_carrier_stats_realtime,ofcom_uk_drop_calc,call_quota_lead_ranking,use_non_latin,allow_shared_dial,abandon_check_queue from system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$generate_carrier_stats =		$aryA[0];
	$SSofcom_uk_drop_calc =			$aryA[1];
	$SScall_quota_lead_ranking =	$aryA[2];
	$non_latin = 					$aryA[3];
	$SSallow_shared_dial =			$aryA[4];
	$SSabandon_check_queue =		$aryA[5];
	}
$sthA->finish();

if ($non_latin > 0) 
	{
	$stmtA = "SET NAMES 'UTF8';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthA->finish();
	}

# make sure the vicidial_campaign_stats table has all of the campaigns.  
# They should exist, but sometimes they get accidently removed during db moves and the like.
$stmtA = "INSERT IGNORE into vicidial_campaign_stats (campaign_id) select campaign_id from vicidial_campaigns;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug (campaign_id,server_ip) select campaign_id,'ADAPT' from vicidial_campaigns;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug SET campaign_id='--ALL--',server_ip='SHARED';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug SET campaign_id='--CALLBACK-QUEUE--',server_ip='ADAPT';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug SET campaign_id='--ABANDON-QUEUE--',server_ip='ADAPT';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug (campaign_id,server_ip) select campaign_id,'SHARED' from vicidial_campaigns;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();


&get_time_now;

#############################################
##### START QUEUEMETRICS LOGGING LOOKUP #####
# Disabled per Lorenzo at QueueMetrics because this Asterisk event is apparently useless
#$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id FROM system_settings;";
#$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
#$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
#$sthArows=$sthA->rows;
#$rec_count=0;
#while ($sthArows > $rec_count)
#	{
#	 @aryA = $sthA->fetchrow_array;
#		$enable_queuemetrics_logging =	"$aryA[0]";
#		$queuemetrics_server_ip	=		"$aryA[1]";
#		$queuemetrics_dbname	=		"$aryA[2]";
#		$queuemetrics_login	=			"$aryA[3]";
#		$queuemetrics_pass	=			"$aryA[4]";
#		$queuemetrics_log_id =			"$aryA[5]";
#	 $rec_count++;
#	}
#$sthA->finish();
#
#if ($enable_queuemetrics_logging > 0)
#	{
#	$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
#	 or die "Couldn't connect to database: " . DBI->errstr;
#
#	if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}
#
#	$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secT',call_id='NONE',queue='NONE',agent='NONE',verb='QUEUESTART',serverid='$queuemetrics_log_id';";
#	$Baffected_rows = $dbhB->do($stmtB);
#
#	$dbhB->disconnect();
#	}
##### END QUEUEMETRICS LOGGING LOOKUP #####
###########################################

### concurrency check (SCREEN uses script path, so check for more than 2 entries)
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 2) 
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		$event_string = "I am not alone! Another $0 is running! Exiting...";
		&event_logger;
		exit;
		}
	}

$master_loop=0;

### Start master loop ###
while ($master_loop < $CLIloops) 
	{
	&get_time_now;

	### Grab Server values from the database
	$stmtA = "SELECT vd_server_logs,local_gmt FROM servers where server_ip='$VARserver_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$DBvd_server_logs =		$aryA[0];
		$DBSERVER_GMT =			$aryA[1];
		if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
		else {$SYSLOG = '0';}
		if (length($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
		$rec_count++;
		}
	$sthA->finish();

	$secX = time();
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($secX);
	$LOCAL_GMT_OFF = $SERVER_GMT;
	$LOCAL_GMT_OFF_STD = $SERVER_GMT;
	if ($isdst) {$LOCAL_GMT_OFF++;} 

	$GMT_now = ($secX - ($LOCAL_GMT_OFF * 3600));
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($GMT_now);
	$mon++;
	$year = ($year + 1900);
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	if ($DBXXX) {print "TIME DEBUG: $master_loop   $LOCAL_GMT_OFF_STD|$LOCAL_GMT_OFF|$isdst|   GMT: $hour:$min\n";}

	@campaign_id=@MT; 
	@lead_order=@MT;
	@hopper_level=@MT;
	@auto_dial_level=@MT;
	@local_call_time=@MT;
	@lead_filter_id=@MT;
	@use_internal_dnc=@MT;
	@dial_method=@MT;
	@available_only_ratio_tally=@MT;
	@adaptive_dropped_percentage=@MT;
	@adaptive_maximum_level=@MT;
	@adaptive_latest_server_time=@MT;
	@adaptive_intensity=@MT;
	@adaptive_dl_diff_target=@MT;
	@campaign_changedate=@MT;
	@campaign_stats_refresh=@MT;
	@campaign_allow_inbound=@MT;
	@drop_rate_group=@MT;
	@available_only_tally_threshold=@MT;
	@available_only_tally_threshold_agents=@MT;
	@incall_tally_threshold_seconds=@MT;
	@dial_level_threshold=@MT;
	@dial_level_threshold_agents=@MT;

	if ($CLIcampaign)
		{
		$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,lead_filter_id,use_internal_dnc,dial_method,available_only_ratio_tally,adaptive_dropped_percentage,adaptive_maximum_level,adaptive_latest_server_time,adaptive_intensity,adaptive_dl_diff_target,UNIX_TIMESTAMP(campaign_changedate),campaign_stats_refresh,campaign_allow_inbound,drop_rate_group,UNIX_TIMESTAMP(campaign_calldate),realtime_agent_time_stats,available_only_tally_threshold,available_only_tally_threshold_agents,dial_level_threshold,dial_level_threshold_agents,ofcom_uk_drop_calc,drop_call_seconds,drop_action,drop_inbound_group,incall_tally_threshold_seconds from vicidial_campaigns where campaign_id='$CLIcampaign'";
		}
	else
		{
		$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,lead_filter_id,use_internal_dnc,dial_method,available_only_ratio_tally,adaptive_dropped_percentage,adaptive_maximum_level,adaptive_latest_server_time,adaptive_intensity,adaptive_dl_diff_target,UNIX_TIMESTAMP(campaign_changedate),campaign_stats_refresh,campaign_allow_inbound,drop_rate_group,UNIX_TIMESTAMP(campaign_calldate),realtime_agent_time_stats,available_only_tally_threshold,available_only_tally_threshold_agents,dial_level_threshold,dial_level_threshold_agents,ofcom_uk_drop_calc,drop_call_seconds,drop_action,drop_inbound_group,incall_tally_threshold_seconds from vicidial_campaigns where ( (active='Y') or (campaign_stats_refresh='Y') )";
		}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$campaign_id[$rec_count] =					$aryA[0];
		$lead_order[$rec_count] =					$aryA[1];
		if (!$CLIlevel) 
			{$hopper_level[$rec_count] =			$aryA[2];}
		else
			{$hopper_level[$rec_count] =			$CLIlevel;}
		$auto_dial_level[$rec_count] =				$aryA[3];
		$local_call_time[$rec_count] =				$aryA[4];
		$lead_filter_id[$rec_count] =				$aryA[5];
		$use_internal_dnc[$rec_count] =				$aryA[6];
		$dial_method[$rec_count] =					$aryA[7];
		$available_only_ratio_tally[$rec_count] =	$aryA[8];
		$adaptive_dropped_percentage[$rec_count] =	$aryA[9];
		$adaptive_maximum_level[$rec_count] =		$aryA[10];
		$adaptive_latest_server_time[$rec_count] =	$aryA[11];
		$adaptive_intensity[$rec_count] =			$aryA[12];
		$adaptive_dl_diff_target[$rec_count] =		$aryA[13];
		$campaign_changedate[$rec_count] =			$aryA[14];
		$campaign_stats_refresh[$rec_count] =		$aryA[15];
		$campaign_allow_inbound[$rec_count] =		$aryA[16];
		$drop_rate_group[$rec_count] =				$aryA[17];
		$campaign_calldate_epoch[$rec_count] =		$aryA[18];
		$realtime_agent_time_stats[$rec_count] =	$aryA[19];
		$available_only_tally_threshold[$rec_count] =	$aryA[20];
		$available_only_tally_threshold_agents[$rec_count] =	$aryA[21];
		$dial_level_threshold[$rec_count] =			$aryA[22];
		$dial_level_threshold_agents[$rec_count] =	$aryA[23];
		$ofcom_uk_drop_calc[$rec_count] =			$aryA[24];
		$drop_call_seconds[$rec_count] =			$aryA[25];
		$drop_action[$rec_count] =					$aryA[26];
		$drop_inbound_group[$rec_count] =			$aryA[27];
		$incall_tally_threshold_seconds[$rec_count] =	$aryA[28];

		$rec_count++;
		}
	$sthA->finish();
	if ($DB) {print "$now_date CAMPAIGNS TO PROCESSES ADAPT FOR:  $rec_count|$#campaign_id       IT: $master_loop\n";}

	$five_min_ago = time();
	$five_min_ago = ($five_min_ago - 300);
	$ten_min_ago = ($five_min_ago - 3600*6);

	##### LOOP THROUGH EACH CAMPAIGN AND PROCESS THE DATA #####
	$i=0;
	foreach(@campaign_id)
		{
		$debug_camp_output='';
		$hopper_ready_count=0;
		$agents_loggedin_count=0;
		### Find out how many leads are in the hopper from a specific campaign
		$stmtA = "SELECT count(*) from vicidial_hopper where campaign_id='$campaign_id[$i]' and status IN('READY','RHOLD','RQUEUE');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$hopper_ready_count = $aryA[0];
			if ($DB) {print "     $campaign_id[$i] hopper READY count:   $hopper_ready_count";}
			$debug_camp_output .= "     $campaign_id[$i] hopper READY count:   $hopper_ready_count\n";
			if ($DBXXX) {print "     |$stmtA|\n";}
			}
		$sthA->finish();

		### Find out how many agents are logged in to a specific campaign
		# check if drop-in-group agents should be included ---NONE---
		$drop_ingroup_SQL[$i]='';
		if ( ($drop_call_seconds[$i] < 0) && ($drop_action[$i] =~ /IN_GROUP/i) && ($drop_inbound_group[$i] !~ /^---NONE---$/) && (length($drop_inbound_group[$i]) > 0) && ($new_agent_multicampaign > 0) && ($dial_method[$i] !~ /SHARED_/i) )
			{
			$SQL_group_id=$drop_inbound_group[$i];   $SQL_group_id =~ s/_/\\_/gi;
			$drop_ingroup_SQL[$i] = " or ( (campaign_id!='$campaign_id[$i]') and (closer_campaigns LIKE \"% $SQL_group_id %\") )";
			if ($DB) {print "     $campaign_id[$i] Drop In-Group agents included from:   $drop_inbound_group[$i]";}
			$debug_camp_output .= "     $campaign_id[$i] Drop In-Group agents included from:   $drop_inbound_group[$i]\n";
			}
		$stmtA = "SELECT count(*) from vicidial_live_agents where ( (campaign_id='$campaign_id[$i]') or (dial_campaign_id='$campaign_id[$i]') $drop_ingroup_SQL[$i] ) and last_update_time > '$VDL_one';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$agents_loggedin_count = $aryA[0];
			if ($DB) {print "     $campaign_id[$i] agents LOGGED-IN count:   $agents_loggedin_count";}
			$debug_camp_output .= "     $campaign_id[$i] agents LOGGED-IN count:   $agents_loggedin_count\n";
			if ($DBXXX) {print "     |$stmtA|\n";}
			}
		$sthA->finish();

		$stat_stmt = "select count(*) From vicidial_campaign_hour_counts where type='CALLS' and calls>0 and campaign_id='$campaign_id[$i]' and last_update>='$ten_min_ago'";
		$sthA = $dbhA->prepare($stat_stmt) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$recently_dialed_campaign = $aryA[0];
			if ($DB) {print "     $campaign_id[$i] recently dialed:   $recently_dialed_campaign";}
			$debug_camp_output .= "     $campaign_id[$i] recently dialed:   $recently_dialed_campaign\n";
			if ($DBXXX) {print "     |$stat_stmt|\n";}
			}

		$event_string = "|$campaign_id[$i]|$hopper_level[$i]|$hopper_ready_count|$local_call_time[$i]|$diff_ratio_updater|$drop_count_updater|$shared_agent_count_updater|$agents_loggedin_count|$recently_dialed_campaign";
		if ($DBX) {print "$i     $event_string\n";}
		$debug_camp_output .= "$i     $event_string\n";
		&event_logger;	

		if ($DBX) {print "     TIME CALL CHECK: $five_min_ago/$campaign_calldate_epoch[$i]\n";}
		$debug_camp_output .= "     TIME CALL CHECK: $five_min_ago/$campaign_calldate_epoch[$i]\n";

		if ($DBX) {print "     TIME NO-AGENT OVERRIDE CALL CHECK: $ten_min_ago/$recently_dialed_campaign\n";}
		$debug_camp_output .= "     TIME NO-AGENT OVERRIDE CALL CHECK: $ten_min_ago/$recently_dialed_campaign\n";

		##### IF THERE ARE NO LEADS IN THE HOPPER OR AGENTS LOGGED-IN FOR THE CAMPAIGN WE DO NOT WANT TO ADJUST THE DIAL_LEVEL
		if ( ($hopper_ready_count > 0) || ($agents_loggedin_count > 0) || ($agents_loggedin_count==0 && $recently_dialed_campaign>0))
			{
			### BEGIN - GATHER STATS FOR THE vicidial_campaign_stats TABLE ###
			$differential_onemin[$i]=0;
			$agents_average_onemin[$i]=0;

			&count_agents_lines;

			if ($total_agents_avg[$i] > 0)
				{
				### Update Drop counter every 60 seconds
				if ($drop_count_updater >= 60)
					{
					&calculate_drops;
					}

				### Calculate and update Dial level every 15 seconds
				if ($diff_ratio_updater >= 15)
					{
					&calculate_dial_level;
					}
				}
			elsif ($agents_loggedin_count==0 && $recently_dialed_campaign>0)
				{
				if ($drop_count_updater >= 60)
					{
					if ($DB) {print "     NO-AGENT RECENCY OVERRIDE: $campaign_id[$i]\n";}
					$debug_camp_output .= "     NO-AGENT RECENCY OVERRIDE: $campaign_id[$i]\n";
					&calculate_drops;
					
					$RESETdrop_count_updater++;
					}
				}
			else
				{
				if ( ($campaign_stats_refresh[$i] =~ /Y/) || ($five_min_ago < $campaign_calldate_epoch[$i]) )
					{
					if ($drop_count_updater >= 60)
						{
						if ($DB) {print "     REFRESH OVERRIDE: $campaign_id[$i]\n";}
						$debug_camp_output .= "     REFRESH OVERRIDE: $campaign_id[$i]\n";

						&calculate_drops;

						$RESETdrop_count_updater++;

						$stmtA = "UPDATE vicidial_campaigns SET campaign_stats_refresh='N' where campaign_id='$campaign_id[$i]';";
						$affected_rows = $dbhA->do($stmtA);
						}
					}
				else
					{
					if ($campaign_changedate[$i] >= $VDL_ninty)
						{
						if ($drop_count_updater >= 60)
							{
							if ($DB) {print "     CHANGEDATE OVERRIDE: $campaign_id[$i]\n";}
							$debug_camp_output .= "     CHANGEDATE OVERRIDE: $campaign_id[$i]\n";

							&calculate_drops;

							$RESETdrop_count_updater++;
							}
						}
					}
				}
			}
		else
			{
			if ( ($campaign_stats_refresh[$i] =~ /Y/) || ($five_min_ago < $campaign_calldate_epoch[$i]) )
				{
				if ($drop_count_updater >= 60)
					{
					if ($DB) {print "     REFRESH OVERRIDE: $campaign_id[$i]\n";}
					$debug_camp_output .= "     REFRESH OVERRIDE: $campaign_id[$i]\n";

					&calculate_drops;

					$RESETdrop_count_updater++;

					$stmtA = "UPDATE vicidial_campaigns SET campaign_stats_refresh='N' where campaign_id='$campaign_id[$i]';";
					$affected_rows = $dbhA->do($stmtA);
					}
				}
			else
				{
				if ($campaign_changedate[$i] >= $VDL_ninty)
					{
					if ($drop_count_updater >= 60)
						{
						if ($DB) {print "     CHANGEDATE OVERRIDE: $campaign_id[$i]\n";}
						$debug_camp_output .= "     CHANGEDATE OVERRIDE: $campaign_id[$i]\n";

						&calculate_drops;

						$RESETdrop_count_updater++;
						}
					}
				}
			}
		$i++;
		}

	if ($stat_count =~ /1$/)
		{
		&drop_rate_group_gather;
		}

	if ( ($stat_count =~ /00$|50$/) || ($stat_count==1) )
		{
		&launch_inbound_gather;
		}

	if ( ($daily_stats > 0) && (($stat_count =~ /0$|5$/) || ($stat_count==1)) )
		{
		&launch_max_calls_gather;
		}

	### Update Drop counter every 60 seconds
	if ($shared_agent_count_updater >= 60)
		{
		&shared_agent_process;
		}

	if ( (($stat_count =~ /00$|50$/) || ($stat_count==1)) )
		{
		$stmtA = "SELECT cache_carrier_stats_realtime,ofcom_uk_drop_calc from system_settings;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$generate_carrier_stats =	$aryA[0];
			$SSofcom_uk_drop_calc =		$aryA[1];
			}
		$sthA->finish();
		if ($generate_carrier_stats > 0)
			{
			&launch_carrier_stats_gather;
			}
		}

	if ($RESETdiff_ratio_updater > 0) {$RESETdiff_ratio_updater=0;   $diff_ratio_updater=0;}
	if ($RESETdrop_count_updater > 0) {$RESETdrop_count_updater=0;   $drop_count_updater=0;}
	if ($RESETshared_agent_count_updater > 0) {$RESETshared_agent_count_updater=0;   $shared_agent_count_updater=0;}
	$diff_ratio_updater = ($diff_ratio_updater + $CLIdelay);
	$drop_count_updater = ($drop_count_updater + $CLIdelay);
	$shared_agent_count_updater = ($shared_agent_count_updater + $CLIdelay);



	##########################################################
	##### BEGIN check for inbound callback queue entries #####
	##########################################################
	$stmtA = "SELECT count(*) from vicidial_inbound_callback_queue where icbq_status='LIVE';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsICBQ=$sthA->rows;
	if ($sthArowsICBQ > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$ICBQcount =	$aryA[0];
		}
	$sthA->finish();

	if ($DB) {print "\nLive Inbound Callback Queue Entries: |$ICBQcount|$stat_count|$stmtA|\n";}

	if ($ICBQcount > 0)
		{
		$vci=0;
		$INBOUNDcampsSQL="''";
		$stmtA = "SELECT campaign_id FROM vicidial_campaigns where active='Y' and campaign_allow_inbound='Y';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		while ($sthArows > $vci)
			{
			@aryA = $sthA->fetchrow_array;
			if ($vci < 1) {$INBOUNDcampsSQL	= "'$aryA[0]'";}
			else {$INBOUNDcampsSQL	.= ",'$aryA[0]'";}
			$vci++;
			}
		$sthA->finish();

		$now_epoch = time();
		$BDtarget = ($now_epoch - 7);
		($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
		$Byear = ($Byear + 1900);
		$Bmon++;
		if ($Bmon < 10) {$Bmon = "0$Bmon";}
		if ($Bmday < 10) {$Bmday = "0$Bmday";}
		if ($Bhour < 10) {$Bhour = "0$Bhour";}
		if ($Bmin < 10) {$Bmin = "0$Bmin";}
		if ($Bsec < 10) {$Bsec = "0$Bsec";}
			$BDtsSQLdate = "$Byear$Bmon$Bmday$Bhour$Bmin$Bsec";

		@ICBQicbq_id=@MT;
		@ICBQlead_id=@MT;
		@ICBQicbq_date=@MT;
		@ICBQgroup_id=@MT;
		@ICBQcall_date=@MT;
		@ICBQqueue_priority=@MT;
		@ICBQicbq_phone_number=@MT;
		@ICBQicbq_phone_code=@MT;
		@ICBQicbq_date_epoch=@MT;
		$routed_ingroup_list='|';
		if ( (($stat_count =~ /0$|5$/) || ($stat_count==1)) )
			{$routed_user_list='';}

		$stmtA = "SELECT icbq_id,lead_id,icbq_date,group_id,call_date,queue_priority,icbq_phone_number,icbq_phone_code,UNIX_TIMESTAMP(icbq_date) from vicidial_inbound_callback_queue where icbq_status='LIVE' order by queue_priority desc,call_date limit 1000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsICBQr=$sthA->rows;
		$r=0;
		while ($sthArowsICBQr > $r)
			{
			@aryA = $sthA->fetchrow_array;
			$ICBQicbq_id[$r] =				$aryA[0];
			$ICBQlead_id[$r] =				$aryA[1];
			$ICBQicbq_date[$r] =			$aryA[2];
			$ICBQgroup_id[$r] =				$aryA[3];
			$ICBQcall_date[$r] =			$aryA[4];
			$ICBQqueue_priority[$r] =		$aryA[5];
			$ICBQicbq_phone_number[$r] =	$aryA[6];
			$ICBQicbq_phone_code[$r] =		$aryA[7];
			$ICBQicbq_date_epoch[$r] =		$aryA[8];

			$r++;
			}
		$sthA->finish();

		$r=0;
		while ($sthArowsICBQr > $r)
			{
			$temp_ingroup = $ICBQgroup_id[$r];
			if ($routed_ingroup_list !~ /\|$temp_ingroup\|/) 
				{
				$routed_ingroup_list .= "$temp_ingroup|";
				if ($DBX) {print "Live Inbound Callback Queue Entry: $r|$ICBQicbq_id[$r]|$ICBQlead_id[$r]|$ICBQgroup_id[$r]|$ICBQcall_date[$r]|\n";}
				### Grab inbound group settings from the database
				$stmtA = "SELECT next_agent_call,queue_priority,active,dial_ingroup_cid,icbq_expiration_hours FROM vicidial_inbound_groups where group_id='$ICBQgroup_id[$r]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsIG=$sthA->rows;
				if ($sthArowsIG > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$CAMP_callorder	=			$aryA[0];
					$queue_priority =			$aryA[1];
					$ingroup_active =			$aryA[2];
					$dial_ingroup_cid =			$aryA[3];
					$icbq_expiration_hours =	$aryA[4];
					$expire_epoch = ($now_epoch - ($icbq_expiration_hours * 3600));
					}
				$sthA->finish();

				$agent_call_order='order by last_call_finish';
				if ($CAMP_callorder =~ /longest_wait_time/i)	{$agent_call_order = 'order by vicidial_live_agents.last_state_change';}
				if ($CAMP_callorder =~ /overall_user_level/i)	{$agent_call_order = 'order by user_level desc,last_call_finish';}
				if ($CAMP_callorder =~ /oldest_call_start/i)	{$agent_call_order = 'order by vicidial_live_agents.last_call_time';}
				if ($CAMP_callorder =~ /oldest_call_finish/i)	{$agent_call_order = 'order by vicidial_live_agents.last_call_finish';}
				if ($CAMP_callorder =~ /oldest_inbound_call_start/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_time';}
				if ($CAMP_callorder =~ /oldest_inbound_call_finish/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_finish';}
				if ($CAMP_callorder =~ /random/i)				{$agent_call_order = 'order by random_id';}
				if ($CAMP_callorder =~ /campaign_rank/i)		{$agent_call_order = 'order by campaign_weight desc,last_call_finish';}
				if ($CAMP_callorder =~ /fewest_calls_campaign/i) {$agent_call_order = 'order by vicidial_live_agents.calls_today,vicidial_live_agents.last_call_finish';}
				if ($CAMP_callorder =~ /inbound_group_rank/i)	{$aco_sub=1;	$agent_call_order = 'order by group_weight desc,vicidial_live_inbound_agents.last_call_finish';}
				if ($CAMP_callorder =~ /ingroup_grade_random/i)	{$aco_sub=1;	$agent_call_order = 'order by random_id';}
				if ($CAMP_callorder =~ /campaign_grade_random/i) {$aco_sub=1;	$agent_call_order = 'order by random_id';}
				if ($CAMP_callorder =~ /fewest_calls$/i)		{$aco_sub=1;	$agent_call_order = 'order by vicidial_live_inbound_agents.calls_today,vicidial_live_inbound_agents.last_call_finish';}
				if ($CAMP_callorder =~ /ring_all$/i)			{$aco_sub=0;	$agent_call_order = 'order by vicidial_live_agents.last_state_change';}

				$rec_countWAITrem=0;
				### Get count of number of calls in this group that are ahead of this call
				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where status = 'LIVE' and campaign_id='$ICBQgroup_id[$r]' and call_time < \"$ICBQcall_date[$r]\" and lead_id != '$ICBQlead_id[$r]' and queue_priority >= '$ICBQqueue_priority[$r]' $ADfindSQL;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$rec_countWAITrem = $aryA[0];
					}
				$sthA->finish();
				if ($DBX) {$agi_string = "$rec_countWAITrem|$stmtA|";   print "$agi_string\n";}
				
				$rec_countWAITqueue=0;
				### Get count of number of calls in the callback-queue for this group that are ahead of this call
				$stmtA = "SELECT count(*) FROM vicidial_inbound_callback_queue where icbq_status IN('LIVE','SENDING') and group_id='$ICBQgroup_id[$r]' and call_date < \"$ICBQcall_date[$r]\" and lead_id != '$ICBQlead_id[$r]' and queue_priority >= '$ICBQqueue_priority[$r]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$rec_countWAITqueue = $aryA[0];
					}
				$sthA->finish();
				if ($DBX) {$agi_string = "$rec_countWAITqueue|$stmtA|";   print "$agi_string\n";}
				
				if ( ($rec_countWAITrem < 1) && ($rec_countWAITqueue < 1) )
					{
					$sthArowsFA=0;
					$qp_countWAIT=0;
					### Get count of number of waiting calls in higher priority groups than this call or the same priority with longer wait time
					$stmtA = "SELECT count(*) FROM vicidial_auto_calls where status = 'LIVE' and lead_id != '$ICBQlead_id[$r]' $ADfindSQL and ( (queue_priority > '$ICBQqueue_priority[$r]') or (queue_priority = '$ICBQqueue_priority[$r]' and call_time < \"$ICBQcall_date[$r]\") );";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$qp_countWAIT = $aryA[0];
						}
					$sthA->finish();
					if ($DBX) {$agi_string = "$qp_countWAIT|$stmtA|";   print "$agi_string\n";}

					$qp_countWAITqueue=0;
					### Get count of number of waiting calls in higher priority groups than this call or the same priority with longer wait time
					$stmtA = "SELECT count(*) FROM vicidial_inbound_callback_queue where icbq_status IN('LIVE','SENDING') and lead_id != '$ICBQlead_id[$r]' and ( (queue_priority > '$ICBQqueue_priority[$r]') or (queue_priority = '$ICBQqueue_priority[$r]' and call_date < \"$ICBQcall_date[$r]\") );";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$qp_countWAITqueue = $aryA[0];
						}
					$sthA->finish();
					if ($DBX) {$agi_string = "$qp_countWAITqueue|$stmtA|";   print "$agi_string\n";}

					$qp_groupWAIT='';
					$qp_groupWAIT_SQL='';
					$qp_groupWAIT_aco='';
					$qp_groupWAIT_aco_SQL='';
					$qp_groupWAIT_camp_SQL='';
					if ( ($qp_countWAIT > 0) || ($qp_countWAITqueue > 0) )
						{
						### Get group/campaign ids of calls in higher priority groups than this call or the same priority with longer wait time
						$qp_groupWAIT='';
						$stmtA = "SELECT distinct campaign_id FROM vicidial_auto_calls where status = 'LIVE' and lead_id != '$ICBQlead_id[$r]' $ADfindSQL and ( (queue_priority > '$ICBQqueue_priority[$r]') or (queue_priority = '$ICBQqueue_priority[$r]' and call_time < \"$ICBQcall_date[$r]\") );";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						$dbc=0;
						while ($sthArows > $dbc)
							{
							@aryA = $sthA->fetchrow_array;
							$qp_groupWAIT_aco .= "'$aryA[0]',";
							if ($dbc > 0) 
								{
								$qp_groupWAIT .= "and ";
								}
							$SQL_group_id=$aryA[0];   $SQL_group_id =~ s/_/\\_/gi;
							$qp_groupWAIT .= "closer_campaigns NOT LIKE \"% $SQL_group_id %\" ";
							$dbc++;
							}

						$stmtA = "SELECT distinct group_id FROM vicidial_inbound_callback_queue where icbq_status IN('LIVE','SENDING') and lead_id != '$ICBQlead_id[$r]' $ADfindSQL and ( (queue_priority > '$ICBQqueue_priority[$r]') or (queue_priority = '$ICBQqueue_priority[$r]' and call_date < \"$ICBQcall_date[$r]\") );";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						$dbcQ=0;
						while ($sthArows > $dbcQ)
							{
							@aryA = $sthA->fetchrow_array;
							$qp_groupWAIT_aco .= "'$aryA[0]',";
							if ($dbc > 0) 
								{
								$qp_groupWAIT .= "and ";
								}
							$SQL_group_id=$aryA[0];   $SQL_group_id =~ s/_/\\_/gi;
							$qp_groupWAIT .= "closer_campaigns NOT LIKE \"% $SQL_group_id %\" ";
							$dbc++;
							$dbcQ++;
							}

						if (length($qp_groupWAIT_aco)>2)
							{chop($qp_groupWAIT_aco);}
						else
							{
							$qp_groupWAIT_aco="''";
							$qp_groupWAIT = "closer_campaigns != ''";
							}

						$qp_groupWAIT_SQL = "and ($qp_groupWAIT)";
						$qp_groupWAIT_aco_SQL = "and vicidial_live_inbound_agents.group_id NOT IN($qp_groupWAIT_aco)";
						$qp_groupWAIT_camp_SQL = "and campaign_id NOT IN($qp_groupWAIT_aco)";

						if ($DBX) {$agi_string = "$qp_groupWAIT_SQL|$stmtA|";   print "$agi_string\n";}
						$sthA->finish();
						}

					$VACagent_grab='|';
					$stmtA = "SELECT agent_grab from vicidial_auto_calls where agent_grab!='';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsGRAB=$sthA->rows;
					if ($sthArowsGRAB > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VACagent_grab .=			"$aryA[0]|";
						}
					$sthA->finish();
					if ($DBX) {$agi_string = "     AGENTS TAKING CALLS CHECK: $sthArowsGRAB|$VACagent_grab|$stmtA|";   print "$agi_string\n";}

					if ($aco_sub > 0)
						{
						### BEGIN in-group-rank-based next-agent-call processing ###
						$stmtA = "LOCK TABLES vicidial_live_agents WRITE, vicidial_live_inbound_agents WRITE;";
						my $LOCKaffected_rows = $dbhA->do($stmtA);

						if (length($qp_groupWAIT_aco)<2)
							{$qp_groupWAIT_aco="''";}
						### Get list of users that should take higher priority inbound calls first
						$stmtA = "SELECT distinct user from vicidial_live_inbound_agents where group_id IN($qp_groupWAIT_aco) and ( (daily_limit = '-1') or (daily_limit > calls_today) );";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						$dbc=0;
						$vlia_users='';
						while ($sthArows > $dbc)
							{
							@aryA = $sthA->fetchrow_array;
							$vlia_users .= "'$aryA[0]',";
							$dbc++;
							}
						if (length($vlia_users)>2)
							{chop($vlia_users);}
						else
							{$vlia_users="''";}

						### if ringing an agent, do not look for one, else, look for one
						if ($RING_agent > 0)
							{$sthArows=0;}
						else
							{
							### BEGIN grade random next-agent-call routing ###
							if ($CAMP_callorder =~ /grade/)
								{
								@GRADEuser=@MT;
								@GRADEgrade=@MT;
								@userGRADEarray=@MT;
								$stmtA = "SELECT vicidial_live_agents.user,vicidial_live_inbound_agents.group_grade,vicidial_live_agents.campaign_grade from vicidial_live_agents, vicidial_live_inbound_agents WHERE vicidial_live_agents.user=vicidial_live_inbound_agents.user and status IN('CLOSER','READY') and lead_id<1 $ADUfindSQL and vicidial_live_inbound_agents.group_id='$ICBQgroup_id[$r]' and last_update_time > '$BDtsSQLdate' and vicidial_live_agents.user NOT IN($vlia_users) and ring_callerid='' and ( (vicidial_live_inbound_agents.daily_limit = '-1') or (vicidial_live_inbound_agents.daily_limit > vicidial_live_inbound_agents.calls_today) ) $qp_groupWAIT_camp_SQL limit 1000;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								$gg=0;
								$ga=0;
								while ($gg < $sthArows)
									{
									@aryA = $sthA->fetchrow_array;
									$GRADEuser[$gg] =	$aryA[0];
									if ($CAMP_callorder =~ /ingroup_grade_random/)
										{$GRADEgrade[$gg] =		$aryA[1];}
									else
										{$GRADEgrade[$gg] =		$aryA[2];}
									if ($GRADEgrade[$gg] < 1)
										{$GRADEgrade[$gg] =	1;}
									$gi=0;
									while ($gi < $GRADEgrade[$gg]) 
										{
										$userGRADEarray[$ga] =	$GRADEuser[$gg];
									#	print STDERR "     GRADE ENTRY: $userGRADEarray[$ga]|$ga|$gi|$GRADEgrade[$gg]\n";
										$gi++;
										$ga++;
										}
									$gg++;
									}
								$sthA->finish();

								$sthArows=0;
								if ($ga > 0)
									{
									$sthArowsFA=0;
									$VDADuser='';
									$GRADErandom = int( rand($ga));
									$userGRADEchosen = $userGRADEarray[$GRADErandom];

									if ($DBX) {$agi_string = "GRADE RANDOM: $userGRADEchosen|$GRADErandom|$CAMP_callorder|$gg|$ga|$callerid";   print "$agi_string\n";}

									$stmtA = "SELECT vicidial_live_agents.conf_exten,vicidial_live_agents.user,vicidial_live_agents.extension,vicidial_live_agents.server_ip,vicidial_live_inbound_agents.group_weight,ra_user,vicidial_live_agents.campaign_id,on_hook_agent,on_hook_ring_time,vicidial_live_inbound_agents.group_grade,vicidial_live_agents.campaign_grade from vicidial_live_agents, vicidial_live_inbound_agents WHERE vicidial_live_agents.user='$userGRADEchosen' and vicidial_live_agents.user NOT IN($routed_user_list'') and status IN('CLOSER','READY') limit 1;";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsFA=$sthA->rows;
									if ($sthArowsFA > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$VDADconf_exten =			$aryA[0];
										$VDADuser =					$aryA[1];
										$VDADextension =			$aryA[2];
										$VDADserver_ip =			$aryA[3];
										$VDADgroup_weight =			$aryA[4];
										$ra_user =					$aryA[5];
										$log_campaign =				$aryA[6];
										$VDADon_hook_agent =		$aryA[7];
										$VDADon_hook_ring_time =	$aryA[8];
										$group_grade =				$aryA[9];
										$campaign_grade =			$aryA[10];
										}
									$sthA->finish();
									}
								if ($DBX) {$agi_string = "$VDADuser|$VDADgroup_weight|$stmtA|";   print "$agi_string\n";}
								}
							### END grade random next-agent-call routing ###
							else
								{
								$sthArowsFA=0;
								$VDADuser='';
								$stmtA = "SELECT vicidial_live_agents.conf_exten,vicidial_live_agents.user,vicidial_live_agents.extension,vicidial_live_agents.server_ip,vicidial_live_inbound_agents.group_weight,ra_user,vicidial_live_agents.campaign_id,on_hook_agent,on_hook_ring_time,vicidial_live_inbound_agents.group_grade,vicidial_live_agents.campaign_grade from vicidial_live_agents, vicidial_live_inbound_agents WHERE vicidial_live_agents.user=vicidial_live_inbound_agents.user and status IN('CLOSER','READY') and lead_id<1 $ADUfindSQL and vicidial_live_inbound_agents.group_id='$ICBQgroup_id[$r]' and last_update_time > '$BDtsSQLdate' and vicidial_live_agents.user NOT IN($routed_user_list$vlia_users) and ring_callerid='' and ( (vicidial_live_inbound_agents.daily_limit = '-1') or (vicidial_live_inbound_agents.daily_limit > vicidial_live_inbound_agents.calls_today) ) $qp_groupWAIT_camp_SQL $agent_call_order limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsFA=$sthA->rows;
								if ($sthArowsFA > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$VDADconf_exten =			$aryA[0];
									$VDADuser =					$aryA[1];
									$VDADextension =			$aryA[2];
									$VDADserver_ip =			$aryA[3];
									$VDADgroup_weight =			$aryA[4];
									$ra_user =					$aryA[5];
									$log_campaign =				$aryA[6];
									$VDADon_hook_agent =		$aryA[7];
									$VDADon_hook_ring_time =	$aryA[8];
									$group_grade =				$aryA[9];
									$campaign_grade =			$aryA[10];
									}
								$sthA->finish();
								if ($DBX) {$agi_string = "$VDADuser|$VDADgroup_weight|$stmtA|";   print "$agi_string\n";}
								}
							}
						### END in-group-rank-based next-agent-call processing ###
						}
					else
						{
						### BEGIN standard next-agent-call processing ###
						$stmtA = "LOCK TABLES vicidial_live_agents WRITE;";
						my $LOCKaffected_rows = $dbhA->do($stmtA);

						### if ringing an agent, do not look for one, else, look for one
						if ($RING_agent > 0)
							{$sthArowsFA=0;}
						else
							{
							$sthArowsFA=0;
							$SQL_group_id=$ICBQgroup_id[$r];   $SQL_group_id =~ s/_/\\_/gi;
							$stmtA = "SELECT conf_exten,user,extension,server_ip,last_call_time,ra_user,campaign_id,on_hook_agent,on_hook_ring_time FROM vicidial_live_agents where status IN('CLOSER','READY') and lead_id<1 $ADUfindSQL and campaign_id IN($INBOUNDcampsSQL) and closer_campaigns LIKE \"% $SQL_group_id %\" and last_update_time > '$BDtsSQLdate' and user NOT IN($routed_user_list'') $qp_groupWAIT_SQL $qp_groupWAIT_camp_SQL $agent_call_order limit 1;";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsFA=$sthA->rows;
							if ($sthArowsFA > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VDADconf_exten =			$aryA[0];
								$VDADuser =					$aryA[1];
								$VDADextension =			$aryA[2];
								$VDADserver_ip =			$aryA[3];
								$VDADlast_call_time =		$aryA[4];
								$ra_user =					$aryA[5];
								$log_campaign =				$aryA[6];
								$VDADon_hook_agent =		$aryA[7];
								$VDADon_hook_ring_time =	$aryA[8];
								}
							$sthA->finish();
							if ($DBX) {$agi_string = "$VDADuser|$VDADlast_call_time|$stmtA|";   print "$agi_string\n";}
							}
						### END standard next-agent-call processing ###
						}

					if ( ($sthArowsFA > 0) && (length($VDADuser) > 0) )
						{
						$Faffected_rows=0;
						if ($VACagent_grab !~ /\|$VDADuser\|/) 
							{
							$stmtA = "UPDATE vicidial_live_agents set external_dial='$ICBQicbq_phone_number[$r]!$ICBQicbq_phone_code[$r]!NO!NO!NO!!$secX!!!$dial_ingroup_cid!!$ICBQlead_id[$r]!!$ICBQgroup_id[$r]' where user='$VDADuser' and status IN('CLOSER','READY');";
							$Faffected_rows = $dbhA->do($stmtA);

							if ($Faffected_rows > 0) 
								{
								### clear the ringing hold on the ring agents
								$stmtB = "UPDATE vicidial_live_agents SET ring_callerid='' where ring_callerid='$callerid';";
								$CRHaffected_rows = $dbhA->do($stmtB);

								$routed_user_list .= "'$VDADuser',";
								}
							if ($DBX) {$agi_string = "$Faffected_rows|$stmtA\n$CRHaffected_rows|$stmtB|$routed_user_list|";   print "$agi_string\n";}
							}
						else
							{
							if ($DBX) {$agi_string = "     SELECTED AGENT GRABBING ANOTHER CALL: $VDADuser   $VACagent_grab";   print "$agi_string\n";}
							}
						}
					else
						{
						$Faffected_rows=0;
						}
					$found_agents=$Faffected_rows;

					$stmtA = "UNLOCK TABLES;";
					my $LOCKaffected_rows = $dbhA->do($stmtA);
					if ($found_agents > 0)
						{
						### set the icbq record as SENT
						$stmtC = "UPDATE vicidial_inbound_callback_queue SET icbq_status='SENDING' where icbq_id='$ICBQicbq_id[$r]';";
						$ICBQaffected_rows = $dbhA->do($stmtC);

						if ($DBX) {$agi_string = "$ICBQaffected_rows|$stmtC|";   print "$agi_string\n";}
						}
					}
				if ($ICBQicbq_date_epoch[$r] < $expire_epoch)
					{
					### set the icbq record as EXPIRED
					$stmtC = "UPDATE vicidial_inbound_callback_queue SET icbq_status='EXPIRED' where icbq_id='$ICBQicbq_id[$r]' and icbq_status!='SENT';";
					$ICBQaffected_rowsEXP = $dbhA->do($stmtC);

					if ($DBX) {$agi_string = "$ICBQaffected_rowsEXP|$stmtC|";   print "$agi_string\n";}
					}
				}
			else
				{
				if ($DBX) {print "     Already looked at ICBQ Entry for this In-Group: $r|$ICBQicbq_id[$r]|$ICBQgroup_id[$r]|$ICBQcall_date[$r]| ($routed_ingroup_list)\n";}
				}
			$r++;
			}
		}
	
	$icbq_NEW_ct=0;
	$stmtA = "SELECT count(*) from vicidial_inbound_callback_queue where icbq_status='NEW';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsICBQ=$sthA->rows;
	if ($sthArowsICBQ > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$icbq_NEW_ct =	$aryA[0];
		}
	$sthA->finish();
	##########################################################
	##### END check for inbound callback queue entries #####
	##########################################################



	##########################################################
	##### BEGIN check if active inbound callback queue records are able to be dialed right now #####
	##########################################################
	if ( ($stat_count =~ /00$|20$|40$|60$|80$/) || ($stat_count==1) || ($icbq_NEW_ct > 0) )
		{
		$stmtA = "SELECT count(*) from vicidial_inbound_callback_queue where icbq_status IN('NEW','LIVE','NOCALLTIME');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsICBQa=$sthA->rows;
		if ($sthArowsICBQa > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ICBQcountA =	$aryA[0];
			}
		$sthA->finish();

		if ($DB) {print "\nActive Inbound Callback Queue Entries: |$ICBQcountA|$stmtA|\n";}
		$callback_debug = "\nActive Inbound Callback Queue Entries: |$ICBQcountA|\n";

		if ($ICBQcountA > 0)
			{
			@ICBQicbq_id=@MT;
			@ICBQlead_id=@MT;
			@ICBQgroup_id=@MT;
			@ICBQicbq_phone_number=@MT;
			@ICBQicbq_phone_code=@MT;
			@ICBQgmt_offset_now=@MT;
			@ICBQicbq_status=@MT;

			$stmtA = "SELECT icbq_id,group_id,icbq_phone_number,icbq_phone_code,gmt_offset_now,icbq_status,lead_id from vicidial_inbound_callback_queue where icbq_status IN('NEW','LIVE','NOCALLTIME') order by group_id,call_date limit 1000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsICBQr=$sthA->rows;
			$r=0;
			while ($sthArowsICBQr > $r)
				{
				@aryA = $sthA->fetchrow_array;
				$ICBQicbq_id[$r] =				$aryA[0];
				$ICBQgroup_id[$r] =				$aryA[1];
				$ICBQicbq_phone_number[$r] =	$aryA[2];
				$ICBQicbq_phone_code[$r] =		$aryA[3];
				$ICBQgmt_offset_now[$r] =		$aryA[4];
				$ICBQicbq_status[$r] =			$aryA[5];
				$ICBQlead_id[$r] =				$aryA[6];

				$r++;
				}
			$sthA->finish();

			$r=0;
			while ($sthArowsICBQr > $r)
				{
				if ($DBX) {print "Active Inbound Callback Queue Entry: $r|$ICBQicbq_id[$r]|$ICBQicbq_status[$r]|$ICBQgroup_id[$r]|$ICBQicbq_phone_number[$r]|$ICBQicbq_phone_code[$r]|$ICBQgmt_offset_now[$r]|\n";}

				### Grab inbound group settings from the database
				$stmtA = "SELECT icbq_call_time_id,icbq_dial_filter FROM vicidial_inbound_groups where group_id='$ICBQgroup_id[$r]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsIG=$sthA->rows;
				if ($sthArowsIG > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$icbq_call_time_id	=	$aryA[0];
					$icbq_dial_filter =		$aryA[1];
					}
				$sthA->finish();
	

				### BEGIN check of call time dialable for this record
				$dialable=0;
				$now_epoch = time();
				$GMT_now = ($now_epoch - (($LOCAL_GMT_OFF - $ICBQgmt_offset_now[$r]) * 3600));
				($Gsec,$Gmin,$Ghour,$Gmday,$Gmon,$Gyear,$Gwday,$Gyday,$Gisdst) = localtime($GMT_now);
				$Gmon++;
				$Gyear = ($Gyear + 1900);
				if ($Gmon < 10) {$Gmon = "0$Gmon";}
				if ($Gmday < 10) {$Gmday = "0$Gmday";}
				if ($Ghour < 10) {$Ghour = "0$Ghour";}
				if ($Gmin < 10) {$Gmin = "0$Gmin";}
				if ($Gsec < 10) {$Gsec = "0$Gsec";}
				$Ghourmin = "$Ghour$Gmin";
				$YMD = "$Gyear-$Gmon-$Gmday";

				$stmtA = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times,ct_holidays FROM vicidial_call_times where call_time_id='$icbq_call_time_id';";
					if ($DBX) {print "   |$stmtA|\n";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				while ($sthArows > $rec_count)
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
					$Gct_state_call_times = $aryA[19];
					$Gct_holidays =			$aryA[20];
					$rec_count++;
					}
				$sthA->finish();
				### BEGIN Check for outbound call time holiday ###
				$holiday_id = '';
				if (length($Gct_holidays)>2)
					{
					$Gct_holidaysSQL = $Gct_holidays;
					$Gct_holidaysSQL =~ s/^\||\|$//gi;
					$Gct_holidaysSQL =~ s/\|/','/gi;
					$Gct_holidaysSQL = "'$Gct_holidaysSQL'";

					$stmtC = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Gct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
					if ($DBX) {print "   |$stmtC|\n";}
					$sthC = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhA->errstr;
					$sthCrows=$sthC->rows;
					if ($sthCrows > 0)
						{
						@aryC = $sthC->fetchrow_array;
						$holiday_id =				$aryC[0];
						$holiday_date =				$aryC[1];
						$holiday_name =				$aryC[2];
						if ( ($Gct_default_start < $aryC[3]) && ($Gct_default_stop > 0) )		{$Gct_default_start = $aryC[3];}
						if ( ($Gct_default_stop > $aryC[4]) && ($Gct_default_stop > 0) )		{$Gct_default_stop = $aryC[4];}
						if ( ($Gct_sunday_start < $aryC[3]) && ($Gct_sunday_stop > 0) )			{$Gct_sunday_start = $aryC[3];}
						if ( ($Gct_sunday_stop > $aryC[4]) && ($Gct_sunday_stop > 0) )			{$Gct_sunday_stop = $aryC[4];}
						if ( ($Gct_monday_start < $aryC[3]) && ($Gct_monday_stop > 0) )			{$Gct_monday_start = $aryC[3];}
						if ( ($Gct_monday_stop >	$aryC[4]) && ($Gct_monday_stop > 0) )		{$Gct_monday_stop =	$aryC[4];}
						if ( ($Gct_tuesday_start < $aryC[3]) && ($Gct_tuesday_stop > 0) )		{$Gct_tuesday_start = $aryC[3];}
						if ( ($Gct_tuesday_stop > $aryC[4]) && ($Gct_tuesday_stop > 0) )		{$Gct_tuesday_stop = $aryC[4];}
						if ( ($Gct_wednesday_start < $aryC[3]) && ($Gct_wednesday_stop > 0) ) 	{$Gct_wednesday_start = $aryC[3];}
						if ( ($Gct_wednesday_stop > $aryC[4]) && ($Gct_wednesday_stop > 0) )	{$Gct_wednesday_stop = $aryC[4];}
						if ( ($Gct_thursday_start < $aryC[3]) && ($Gct_thursday_stop > 0) )		{$Gct_thursday_start = $aryC[3];}
						if ( ($Gct_thursday_stop > $aryC[4]) && ($Gct_thursday_stop > 0) )		{$Gct_thursday_stop = $aryC[4];}
						if ( ($Gct_friday_start < $aryC[3]) && ($Gct_friday_stop > 0) )			{$Gct_friday_start = $aryC[3];}
						if ( ($Gct_friday_stop > $aryC[4]) && ($Gct_friday_stop > 0) )			{$Gct_friday_stop = $aryC[4];}
						if ( ($Gct_saturday_start < $aryC[3]) && ($Gct_saturday_stop > 0) )		{$Gct_saturday_start = $aryC[3];}
						if ( ($Gct_saturday_stop > $aryC[4]) && ($Gct_saturday_stop > 0) )		{$Gct_saturday_stop = $aryC[4];}
						if ($DBX) {print "     CALL TIME HOLIDAY FOUND!   $local_call_time[$i]|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";}
						}
					$sthC->finish();
					}
				### END Check for outbound call time holiday ###

				if ($Gwday==0)	#### Sunday local time
					{
					if (($Gct_sunday_start==0) && ($Gct_sunday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_sunday_start) && ($Ghourmin<$Gct_sunday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==1)	#### Monday local time
					{
					if (($Gct_monday_start==0) && ($Gct_monday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_monday_start) && ($Ghourmin<$Gct_monday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==2)	#### Tuesday local time
					{
					if (($Gct_tuesday_start==0) && ($Gct_tuesday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_tuesday_start) && ($Ghourmin<$Gct_tuesday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==3)	#### Wednesday local time
					{
					if (($Gct_wednesday_start==0) && ($Gct_wednesday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_wednesday_start) && ($Ghourmin<$Gct_wednesday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==4)	#### Thursday local time
					{
					if (($Gct_thursday_start==0) && ($Gct_thursday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_thursday_start) && ($Ghourmin<$Gct_thursday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==5)	#### Friday local time
					{
					if (($Gct_friday_start==0) && ($Gct_friday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_friday_start) && ($Ghourmin<$Gct_friday_stop) )
							{$dialable++;}
						}
					}
				if ($Gwday==6)	#### Saturday local time
					{
					if (($Gct_saturday_start==0) && ($Gct_saturday_stop==0))
						{
						if ( ($Ghourmin>=$Gct_default_start) && ($Ghourmin<$Gct_default_stop) )
							{$dialable++;}
						}
					else
						{
						if ( ($Ghourmin>=$Gct_saturday_start) && ($Ghourmin<$Gct_saturday_stop) )
							{$dialable++;}
						}
					}
				if ($dialable > 0) 
					{$NEWstatus = 'LIVE';}
				else
					{$NEWstatus = 'NOCALLTIME';}
				if ($DBX) {print "     CALL TIME ICBQ DIALABLE CHECK   $Ghourmin|$Gwday|$dialable|$NEWstatus|$Gct_default_start|$Gct_default_stop|\n";}
				### END check of call time dialable for this record


				### BEGIN check of DNC filtering for this record
				$dnc_internal_match=0;
				$dnc_campaign_match=0;
				if ( ($icbq_dial_filter !~ /NONE/i) && (length($icbq_dial_filter) > 4) ) 
					{
					$alt_areacode='';
					if ($icbq_dial_filter =~ /AREACODE/)
						{
						$alt_areacode = substr($ICBQicbq_phone_number[$r], 0, 3);
						$alt_areacode .= "XXXXXXX";
						$alt_areacode = ",'$alt_areacode'";
						}
					if ($icbq_dial_filter =~ /INTERNAL/)
						{
						$stmtA = "SELECT count(*) FROM vicidial_dnc where phone_number IN('$ICBQicbq_phone_number[$r]'$alt_areacode);";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$dnc_internal_match =	$aryA[0];
							}
						$sthA->finish();
						if ($DBX) {print "     CALLBACK DIAL FILTER DNC INTERNAL: |$dnc_internal_match|$stmtA|\n";}
						}
					if ($icbq_dial_filter =~ /CAMPAIGN/)
						{
						$dnc_campaign_list='';
						$stmtA = "SELECT list_id FROM vicidial_list where lead_id='$ICBQlead_id[$r]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$dnc_campaign_list =	$aryA[0];
							}
						$sthA->finish();

						if (length($dnc_campaign_list) > 1) 
							{
							$dnc_campaign='';
							$stmtA = "SELECT campaign_id FROM vicidial_lists where list_id='$dnc_campaign_list';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$dnc_campaign =	$aryA[0];
								}
							$sthA->finish();

							if (length($dnc_campaign) > 0) 
								{
								$stmtA = "SELECT count(*) FROM vicidial_campaign_dnc where campaign_id='$dnc_campaign' and phone_number IN('$ICBQicbq_phone_number[$r]'$alt_areacode);";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$dnc_campaign_match =	$aryA[0];
									}
								$sthA->finish();
								}
							}
						if ($DBX) {print "     CALLBACK DIAL FILTER DNC CAMPAIGN: |$dnc_campaign_match|$dnc_campaign_list|$stmtA|\n";}
						}

					if ($dnc_internal_match > 0) 
						{$NEWstatus = 'DNCL';}
					else
						{
						if ($dnc_campaign_match > 0) 
							{$NEWstatus = 'DNCC';}
						}
					}
				### END check of DNC filtering for this record


				if ($ICBQicbq_status[$r] !~ /$NEWstatus/) 
					{
					### set the icbq record to new status
					$stmtC = "UPDATE vicidial_inbound_callback_queue SET icbq_status='$NEWstatus' where icbq_id='$ICBQicbq_id[$r]' and icbq_status!='SENT';";
					$ICBQaffected_rowsNEW = $dbhA->do($stmtC);

					if ($DBX) {$agi_string = "$ICBQaffected_rowsNEW|$stmtC|";   print "$agi_string\n";}
					}
				$r++;
				}
			}
		}
	##########################################################
	##### END check if active inbound callback queue records are able to be dialed right now #####
	##########################################################



	##########################################################
	##### BEGIN check for stuck SENDING inbound callback queue records #####
	##########################################################
	if ( ($stat_count =~ /00$|10$|20$|30$|40$|50$|60$|70$|80$|90$/) || ($stat_count==1) )
		{
		$now_epoch = time();
		$BDtarget = ($now_epoch - 10);
		($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
		$Byear = ($Byear + 1900);
		$Bmon++;
		if ($Bmon < 10) {$Bmon = "0$Bmon";}
		if ($Bmday < 10) {$Bmday = "0$Bmday";}
		if ($Bhour < 10) {$Bhour = "0$Bhour";}
		if ($Bmin < 10) {$Bmin = "0$Bmin";}
		if ($Bsec < 10) {$Bsec = "0$Bsec";}
			$BDtsSQLdate = "$Byear-$Bmon-$Bmday $Bhour:$Bmin:$Bsec";

		$ICBQcountS=0;
		$stmtA = "SELECT count(*) from vicidial_inbound_callback_queue where icbq_status IN('SENDING') and modify_date < \"$BDtsSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsICBQs=$sthA->rows;
		if ($sthArowsICBQs > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ICBQcountS =	$aryA[0];
			}
		$sthA->finish();

		if ($DB) {print "\nOrphan SENDING Inbound Callback Queue Entries: |$ICBQcountS|$stmtA|\n";}
		$callback_send_debug = "\nOrphan SENDING Inbound Callback Queue Entries: |$ICBQcountS|\n";
		$callback_debug = "$callback_debug$callback_send_debug";

		if ($ICBQcountS > 0)
			{
			### set the icbq record to new status
			$stmtC = "UPDATE vicidial_inbound_callback_queue SET icbq_status='ORPHAN' where icbq_status IN('SENDING') and modify_date < \"$BDtsSQLdate\";";
			$ICBQaffected_rowsORPHAN = $dbhA->do($stmtC);

			if ($DBX) {$agi_string = "$ICBQaffected_rowsORPHAN|$stmtC|";   print "$agi_string\n";}
			}
		$callback_debug_flag='--CALLBACK-QUEUE--';
		&callback_logger;
		}
	##########################################################
	##### END check for stuck SENDING inbound callback queue records #####
	##########################################################



	##########################################################
	##### BEGIN check for inbound group Calls Waiting URL On/Off #####
	##########################################################
	$sthArowsWCU=0;   $WCUlist='';
	### Find out how many In-Groups are set to use the Waiting-Call URL feature
	$stmtA = "SELECT group_id from vicidial_inbound_groups where active='Y' and waiting_call_url_on!='' and waiting_call_url_off!='' and waiting_call_url_on IS NOT NULL and waiting_call_url_off IS NOT NULL order by group_id;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsWCU=$sthA->rows;
	$wcu=0;
	while ($sthArowsWCU > $wcu)
		{
		@aryA = $sthA->fetchrow_array;
		if ($wcu > 0) {$WCUlist .=	",";}
		$WCUlist .=	"'$aryA[0]'";
		$wcu++;
		}
	$sthA->finish();
	if ($DB) {print "Waiting Call URL In-Groups enabled: |$sthArowsWCU|$stmtA|\n";}

	if ($sthArowsWCU > 0)
		{
		### Count number of waiting calls in WCU-configured in-groups
		$vacWCUcount=0;
		$stmtA = "SELECT count(*) from vicidial_auto_calls where campaign_id IN($WCUlist) and status='LIVE';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsWCUagc=$sthA->rows;
		if ($sthArowsWCUagc > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vacWCUcount =	$aryA[0];
			}
		$sthA->finish();
		if ($DB) {print "Waiting Call URL In-Group calls waiting: |$vacWCUcount($cwu_any_on)|$stmtA|\n";}

		### if there are waiting calls in WCU in-groups, or the WCU indicator had been on after the last loop, run through the Waiting Call URL process
		if ( ($cwu_any_on > 0) || ($vacWCUcount > 0) ) 
			{
			$cwu_any_onNEW=0;   $last_group_id='';
			if ($DB) {print "Waiting Call URL calculations starting: |$cwu_any_on|$vacWCUcount|\n";}

			$sthArowsWCUg=0;   $WCUlistGROUP='';
			### Gather unique On/Off Waiting-Call-URLs for selected in-groups
			$stmtA = "SELECT waiting_call_url_on,waiting_call_url_off,count(*) from vicidial_inbound_groups where group_id IN($WCUlist) group by waiting_call_url_on,waiting_call_url_off;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsWCUg=$sthA->rows;
			$wcu=0;
			while ($sthArowsWCUg > $wcu)
				{
				@aryA = $sthA->fetchrow_array;
				$WCUon[$wcu] =	$aryA[0];
				$WCUoff[$wcu] =	$aryA[1];
				$WCUct[$wcu] =	$aryA[2];
				$wcu++;
				}
			$sthA->finish();
			if ($DB) {print "Waiting Call URL unique On/Off URLs gathered: |$sthArowsWCUg|$stmtA|\n";}

			$wcu=0;
			while ($sthArowsWCUg > $wcu)
				{
				$sthArowsWCUnf=0;   $WCUlistNF='';   $WCUlistNFct=0;   $WCUgroupNF=$MT;   $WCUgroupNFct=$MT;   $WCUgroupNFctNEW=$MT;
				### Find out the In-Groups that are set to use each set of specific Waiting-Call URL On/Off settings
				$stmtA = "SELECT group_id,waiting_call_count from vicidial_inbound_groups where waiting_call_url_on=\"$WCUon[$wcu]\" and waiting_call_url_off=\"$WCUoff[$wcu]\" order by group_id;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsWCUnf=$sthA->rows;
				$wcunf=0;
				while ($sthArowsWCUnf > $wcunf)
					{
					@aryA = $sthA->fetchrow_array;
					if ($wcunf > 0) {$WCUlistNF .=	",";}
					$WCUlistNF .=	"'$aryA[0]'";
					$WCUlistNFct = ($WCUlistNFct + $aryA[1]);
					$WCUgroupNF[$wcunf] =	$aryA[0];
					$WCUgroupNFct[$wcunf] = $aryA[1];
					$last_group_id =		$aryA[0];
					$wcunf++;
					}
				$sthA->finish();
				if ($DB) {print "Waiting Call URL In-Group DB calls waiting: |$WCUlistNFct($wcunf)|$stmtA|\n";}

				### Count number of waiting calls for this set of specific Waiting-Call URL On/Off settings
				$vacWCUcountNF=0;   $WCUgroupNFvac=$MT;   $WCUgroupNFvacct=$MT;
				$stmtA = "SELECT campaign_id,count(*) from vicidial_auto_calls where campaign_id IN($WCUlistNF) and status='LIVE' group by campaign_id order by campaign_id;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsWCUagcNF=$sthA->rows;
				$wcuvac=0;
				while ($sthArowsWCUagcNF > $wcuvac)
					{
					@aryA = $sthA->fetchrow_array;
					$WCUgroupNFvac[$wcuvac] =	$aryA[0];
					$WCUgroupNFvacct[$wcuvac] =	$aryA[1];
					$vacWCUcountNF = ($vacWCUcountNF + $aryA[1]);
					if ($DBX) {print "          WCU LIVEvac check: |$WCUgroupNFvac[$wcuvac]|$WCUgroupNFvacct[$wcuvac]|$wcuvac|\n";}
					$wcuvac++;
					}
				$sthA->finish();
				$cwu_any_onNEW = ($cwu_any_onNEW + $vacWCUcountNF);
				if ($DB) {print "Waiting Call URL In-Group calls waiting: |$vacWCUcountNF($wcuvac)|$stmtA|\n";}

				$wcunf=0;
				while ($sthArowsWCUnf > $wcunf)
					{
					$wcut=0; $wcut_found=0; $wcut_new=0;
					while ($wcut < $wcuvac) 
						{
						$temp_group = $WCUgroupNFvac[$wcut];
						if ($WCUgroupNF[$wcunf] =~ /^$temp_group$/i) 
							{
							$wcut_found++;
							$wcut_new = $WCUgroupNFvacct[$wcut];
							}
						$wcut++;
						if ($DBX) {print "        $wcunf|$wcut  WCU compare check: |$WCUgroupNF[$wcunf]|$temp_group|$wcut|$wcut_new|$wcut_found|\n";}
						}
					if ( ($wcut_new > $WCUgroupNFct[$wcunf]) || ($wcut_new < $WCUgroupNFct[$wcunf]) ) 
						{
						$stmtA = "UPDATE vicidial_inbound_groups SET waiting_call_count='$wcut_new' where group_id='$WCUgroupNF[$wcunf]';";
						$WCUaffected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "     WCU change update($wcut_new|$WCUgroupNFct[$wcunf]): |$WCUaffected_rows|$stmtA|\n";}
						}
					$wcunf++;
					}
				### If last waiting-count was 0 and new waiting-count is greater than 0, trigger ON URL
				if ( ($vacWCUcountNF > 0) && ($WCUlistNFct < 1) ) 
					{
					$launch = $PATHhome . "/AST_send_URL.pl";
					$launch .= " --SYSLOG" if ($SYSLOG);
					$launch .= " --lead_id=0";
					$launch .= " --phone_number=0";
					$launch .= " --user=0";
					$launch .= " --call_type=IN";
					$launch .= " --campaign=" . $last_group_id;
					$launch .= " --uniqueid=0";
					$launch .= " --call_id=0";
					$launch .= " --list_id=0";
					$launch .= " --alt_dial=MAIN";
					$launch .= " --function=INGROUP_WCU_ON";
					system($launch . ' &');
					if ($DB) {print "     LAUNCHING WCU ON URL: |$vacWCUcountNF|$WCUlistNFct|$launch|\n";}
					}
				else
					{
					### If last waiting-count greater than 0 and new waiting-count is 0, trigger OFF URL
					if ( ($vacWCUcountNF < 1) && ($WCUlistNFct > 0) ) 
						{
						$launch = $PATHhome . "/AST_send_URL.pl";
						$launch .= " --SYSLOG" if ($SYSLOG);
						$launch .= " --lead_id=0";
						$launch .= " --phone_number=0";
						$launch .= " --user=0";
						$launch .= " --call_type=IN";
						$launch .= " --campaign=" . $last_group_id;
						$launch .= " --uniqueid=0";
						$launch .= " --call_id=0";
						$launch .= " --list_id=0";
						$launch .= " --alt_dial=MAIN";
						$launch .= " --function=INGROUP_WCU_OFF";
						system($launch . ' &');
						if ($DB) {print "     LAUNCHING WCU OFF URL: |$vacWCUcountNF|$WCUlistNFct|$launch|\n";}
						}
					}

				$wcu++;
				}
			if ( ($cwu_any_onNEW > $cwu_any_on) || ($cwu_any_onNEW < $cwu_any_on) ) 
				{
				if ($DBX) {print "     Updating CWA any variable: |$cwu_any_on|$cwu_any_onNEW|\n";}
				$cwu_any_on = $cwu_any_onNEW;
				}
			}
		if ($DB) {print "Waiting Call URL In-Groups check loop completed \n";}
		}
	##########################################################
	##### END check for inbound group Calls Waiting URL On/Off #####
	##########################################################


	#############################################################
	##### BEGIN check for unlogged outbound auto-dial calls #####
	#############################################################
	if ( ( ($stat_count =~ /30$|80$/) || ($stat_count==1) ) && ($SScall_quota_lead_ranking > 0) )
		{
		$audit_inserts=0;
		$updated_dial_log_no_uid=0;
		$count_dial_log_no_uid=0;

		$now_epoch = time();
		$NBtarget = ($now_epoch - 420);
		($Nsec,$Nmin,$Nhour,$Nmday,$Nmon,$Nyear,$Nwday,$Nyday,$Nisdst) = localtime($NBtarget);
		$Nyear = ($Nyear + 1900);
		$Nmon++;
		if ($Nmon < 10) {$Nmon = "0$Nmon";}
		if ($Nmday < 10) {$Nmday = "0$Nmday";}
		if ($Nhour < 10) {$Nhour = "0$Nhour";}
		if ($Nmin < 10) {$Nmin = "0$Nmin";}
		if ($Nsec < 10) {$Nsec = "0$Nsec";}
			$NBtsSQLdate = "$Nyear-$Nmon-$Nmday $Nhour:$Nmin:$Nsec";

		$NEtarget = ($now_epoch - 240);
		($Nsec,$Nmin,$Nhour,$Nmday,$Nmon,$Nyear,$Nwday,$Nyday,$Nisdst) = localtime($NEtarget);
		$Nyear = ($Nyear + 1900);
		$Nmon++;
		if ($Nmon < 10) {$Nmon = "0$Nmon";}
		if ($Nmday < 10) {$Nmday = "0$Nmday";}
		if ($Nhour < 10) {$Nhour = "0$Nhour";}
		if ($Nmin < 10) {$Nmin = "0$Nmin";}
		if ($Nsec < 10) {$Nsec = "0$Nsec";}
			$NEtsSQLdate = "$Nyear-$Nmon-$Nmday $Nhour:$Nmin:$Nsec";

		$stmtA = "SELECT caller_code,lead_id,server_ip,call_date,uniqueid from vicidial_dial_log where call_date > \"$NBtsSQLdate\" and call_date < \"$NEtsSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsMVL=$sthA->rows;
		$mvl=0;
		while ($sthArowsMVL > $mvl)
			{
			@aryA = $sthA->fetchrow_array;
			$Mcaller_code[$mvl] =	$aryA[0];
			$Mlead_id[$mvl] =		$aryA[1];
			$Mserver_ip[$mvl] =		$aryA[2];
			$Mcall_date[$mvl] =		$aryA[3];
			$Muniqueid[$mvl] =		$aryA[4];
			$mvl++;
			}
		$sthA->finish();

		if ($DB) {print "\nTotal calls placed 4-7 minutes ago: |$mvl|$stmtA|\n";}

		if ($mvl > 0)
			{
			$mvl=0;
			while ($sthArowsMVL > $mvl)
				{
				### see if call was logged properly to vicidial_log_extended
				$VLEuniqueid='';
				$stmtA = "SELECT uniqueid from vicidial_log_extended where caller_code='$Mcaller_code[$mvl]' and lead_id='$Mlead_id[$mvl]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsVLEc=$sthA->rows;
				if ($sthArowsVLEc > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VLEuniqueid =	$aryA[0];
					}
				$sthA->finish();
				if ( (length($Muniqueid[$mvl]) < 9) && (length($VLEuniqueid) >= 9) )
					{
					$Muniqueid[$mvl] = $VLEuniqueid;
					# Update dial log record with correct uniqueid
					$stmtA = "UPDATE vicidial_dial_log SET uniqueid='$VLEuniqueid' where caller_code='$Mcaller_code[$mvl]' and lead_id='$Mlead_id[$mvl]' limit 1;";
					$affected_rows = $dbhA->do($stmtA);
					if ($DBX) {print "vicidial_dial_log UPDATED: $affected_rows|$stmtA|\n";}
					$updated_dial_log_no_uid = ($updated_dial_log_no_uid + $affected_rows);
					}

				if ($sthArowsVLEc > 0) 
					{$do_nothing=1;}
				else
					{
					if (length($Muniqueid[$mvl]) < 9)
						{$count_dial_log_no_uid++;}
					else
						{
						### see if call was logged properly to vicidial_log
						$VLcount=0;
						$temp_uniquiid = $Muniqueid[$mvl];
						$temp_uniquiid =~ s/\..*//gi;
						$stmtA = "SELECT count(*) from vicidial_log where uniqueid LIKE \"$temp_uniquiid%\" and lead_id='$Mlead_id[$mvl]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArowsVLc=$sthA->rows;
						if ($sthArowsVLc > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VLcount =	$aryA[0];
							}
						$sthA->finish();

						if ($VLcount > 0) 
							{$do_nothing=1;}
						else
							{
							# no vicidial_log entry found, look for call_log entry
							$VLcount=0;
							$stmtA = "SELECT number_dialed,length_in_sec,end_time,end_epoch from call_log where uniqueid='$Muniqueid[$mvl]' limit 1;";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsCLc=$sthA->rows;
							if ($sthArowsCLc > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$Mnumber_dialed[$mvl] =		$aryA[0];
								$Mlength_in_sec[$mvl] =		$aryA[1];
								$Mend_time[$mvl] =			$aryA[2];
								$Mend_epoch[$mvl] =			$aryA[3];
								}
							$sthA->finish();

							if ($sthArowsCLc > 0)
								{
								# call_log entry found, look for lead details in vicidial_list
								$stmtA = "SELECT list_id,status,phone_code,phone_number,alt_phone,address3,called_count from vicidial_list where lead_id='$Mlead_id[$mvl]' limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsLEADc=$sthA->rows;
								if ($sthArowsLEADc > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$Llist_id =			$aryA[0];
									$Lstatus =			$aryA[1];
									$Lphone_code =		$aryA[2];
									$Lphone_number =	$aryA[3];
									$Lalt_phone =		$aryA[4];
									$Laddress3 =		$aryA[5];
									$Lcalled_count =	$aryA[6];
									}
								$sthA->finish();

								if ($sthArowsLEADc < 1)
									{$no_lead_found=1;}
								else
									{
									# vicidial_list entry found, look for list details in vicidial_lists
									$stmtA = "SELECT campaign_id from vicidial_lists where list_id='$Llist_id' limit 1;";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsLEADc=$sthA->rows;
									if ($sthArowsLEADc > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$Lcampaign_id =			$aryA[0];
										}
									$sthA->finish();

									$Lalt='MAIN';
									if ( ($Mnumber_dialed[$mvl] =~ /$Lphone_number$/) && (length($Lphone_number) >= 6) ) {$Lalt='MAIN';}
									else
										{
										if ( ($Mnumber_dialed[$mvl] =~ /$Lalt_phone$/) && (length($Lalt_phone) >= 6) ) {$Lalt='ALT';}
										else
											{
											if ( ($Mnumber_dialed[$mvl] =~ /$Laddress3$/) && (length($Laddress3) >= 6) ) {$Lalt='ADDR3';}
											}
										}

									$stmtVL = "INSERT INTO vicidial_log SET uniqueid='$Muniqueid[$mvl]',lead_id='$Mlead_id[$mvl]',campaign_id='$Lcampaign_id',call_date='$Mend_time[$mvl]',start_epoch='$Mend_epoch[$mvl]',status='NA',phone_code='$Lphone_code',phone_number='$Lphone_number',user='VDAD',processed='N',length_in_sec=0,end_epoch='$Mend_epoch[$mvl]',alt_dial='$Lalt',list_id='$Llist_id',called_count='$Lcalled_count',comments='AUTONA',term_reason='NONE';";
									$affected_rowsVL = $dbhA->do($stmtVL);
									if($DBX){print "$mvl|$affected_rowsVL|$stmtVL|\n";}
									$audit_inserts = ($audit_inserts + $affected_rowsVL);

									$stmtVLE = "INSERT INTO vicidial_log_extended set uniqueid='$Muniqueid[$mvl]',server_ip='$Mserver_ip[$mvl]',call_date='$Mend_time[$mvl]',lead_id = '$Mlead_id[$mvl]',caller_code='$Mcaller_code[$mvl]',custom_call_id='';";
									$affected_rowsVLE = $dbhA->do($stmtVLE);
									if($DBX){print "$mvl|$affected_rowsVLE|$stmtVLE|\n";}

									if ($Lstatus ne 'NA') 
										{
										$stmtLEAD = "UPDATE vicidial_list set status='NA' where lead_id='$Mlead_id[$mvl]';";
										$affected_rowsLEAD = $dbhA->do($stmtLEAD);
										if($DBX){print "$mvl|$affected_rowsLEAD|$stmtLEAD|\n";}
										}
									if ($SScall_quota_lead_ranking > 0) 
										{
										$VD_call_quota_lead_ranking='DISABLED';
										# find call quota setting for campaign, to see if call quota logging is enabled
										$stmtA = "SELECT call_quota_lead_ranking from vicidial_campaigns where campaign_id='$Lcampaign_id' limit 1;";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArowsCAMPc=$sthA->rows;
										if ($sthArowsCAMPc > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$VD_call_quota_lead_ranking =			$aryA[0];
											}
										$sthA->finish();

										if ($VD_call_quota_lead_ranking !~ /^DISABLED$/i)
											{
											$CIDlead_id = $Mlead_id[$mvl];
											$temp_status = 'NA';
											&call_quota_logging;
											}
										}
									}
								}
							}
						}
					}
				$mvl++;
				if ($DB) 
					{
					if ($mvl =~ /10$/i) {print STDERR "0     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /20$/i) {print STDERR "+     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /30$/i) {print STDERR "|     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /40$/i) {print STDERR "\\     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /50$/i) {print STDERR "-     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /60$/i) {print STDERR "/     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /70$/i) {print STDERR "|     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /80$/i) {print STDERR "+     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /90$/i) {print STDERR "0     $mvl / $sthArowsMVL \r";}
					if ($mvl =~ /00$/i) 
						{
						print "$mvl / $sthArowsMVL   ($log_updated|$log_inserted|$count_dial_log_no_uid|$updated_dial_log_no_uid|   |$LEADlist_id[$mvl]|$LEADINFOrank| \n";
						}
					}
				}
			}
		if ($DB) {print "Outbound call log audit -   calls: $mvl   inserts: $audit_inserts ($count_dial_log_no_uid|$updated_dial_log_no_uid) \n";}
		}
	##########################################################
	##### END check for stuck SENDING inbound callback queue records #####
	##########################################################





	#############################################################
	##### BEGIN check for abandon_check_queue calls #####
	#############################################################
	if ( ( ($stat_count =~ /30$|80$/) || ($stat_count==1) ) && ($SSabandon_check_queue > 0) )
		{
		$acq_rejected=0;
		$acq_check_dead=0;
		$acq_complete_dead=0;
		$acq_active_call=0;
		$hopper_insert_sent=0;

		$now_date_epoch = time();
		$epochTWENTYFOURhoursAGO = ($now_date_epoch - 86400);
		($Ssec,$Smin,$Shour,$Smday,$Smon,$Syear,$Swday,$Syday,$Sisdst) = localtime($epochTWENTYFOURhoursAGO);
		$Smon++;	$Syear = ($Syear + 1900);
		if ($Smon < 10) {$Smon = "0$Smon";}
		if ($Smday < 10) {$Smday = "0$Smday";}
		if ($Shour < 10) {$Shour = "0$Shour";}
		if ($Smin < 10) {$Smin = "0$Smin";}
		if ($Ssec < 10) {$Ssec = "0$Ssec";}
		$timeTWENTYFOURhoursAGO = "$Syear-$Smon-$Smday $Shour:$Smin:$Ssec";

		$stmtA = "SELECT abandon_check_id,call_id,lead_id,abandon_time,reject_reason,check_status,UNIX_TIMESTAMP(abandon_time),phone_number,source from vicidial_abandon_check_queue where abandon_time > \"$timeTWENTYFOURhoursAGO\" and check_status IN('NEW','PROCESSING');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsACQ=$sthA->rows;
		$acq=0;
		while ($sthArowsACQ > $acq)
			{
			@aryA = $sthA->fetchrow_array;
			$Aabandon_check_id[$acq] =	$aryA[0];
			$Acall_id[$acq] =			$aryA[1];
			$Alead_id[$acq] =			$aryA[2];
			$Aabandon_time[$acq] =		$aryA[3];
			$Areject_reason[$acq] =		$aryA[4];
			$Acheck_status[$acq] =		$aryA[5];
			$Aabandon_timeEPOCH[$acq] =	$aryA[6];
			$Aphone_number[$acq] =		$aryA[7];
			$Asource[$acq] =			$aryA[8];
			$acq++;
			}
		$sthA->finish();

		if ($DB) {print "\nTotal abandon check queue calls: |$acq|$stmtA|\n";}
		$callback_debug = "\nTotal abandon check queue calls: |$acq|\n";

		if ($acq > 0)
			{
			$acq=0;
			while ($sthArowsACQ > $acq)
				{
				### see if lead is in the hopper right now
				$VHcount='';
				$stmtA = "SELECT count(*) from vicidial_hopper where lead_id='$Alead_id[$acq]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsACQh=$sthA->rows;
				if ($sthArowsACQh > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VHcount =	$aryA[0];
					}
				$sthA->finish();

				if ($VHcount > 0)
					{
					# Update vicidial_abandon_check_queue record with REJECT status
					$stmtA = "UPDATE vicidial_abandon_check_queue SET check_status='REJECT', reject_reason='Lead already in hopper, process' where abandon_check_id='$Aabandon_check_id[$acq]';";
					$affected_rows = $dbhA->do($stmtA);
					if ($DBX) {print "vicidial_abandon_check_queue UPDATED: $affected_rows|$stmtA|\n";}
					$acq_rejected = ($acq_rejected + $affected_rows);
					$callback_debug .= "     Abandon Already in hopper REJECT: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
					}
				else
					{
					### see if lead is in a call with an agent right now
					$VLAcount='';
					$stmtA = "SELECT count(*) from vicidial_live_agents where lead_id='$Alead_id[$acq]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsACQa=$sthA->rows;
					if ($sthArowsACQa > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VLAcount =	$aryA[0];
						}
					$sthA->finish();

					if ($VLAcount > 0)
						{
						# Update vicidial_abandon_check_queue record with REJECT status
						$stmtA = "UPDATE vicidial_abandon_check_queue SET check_status='REJECT', reject_reason='Lead in agent call, process' where abandon_check_id='$Aabandon_check_id[$acq]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "vicidial_abandon_check_queue UPDATED: $affected_rows|$stmtA|\n";}
						$acq_rejected = ($acq_rejected + $affected_rows);
						$callback_debug .= "     Abandon AGENT CALL REJECT: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
						}
					else
						{
						### see if lead is an active call right now
						$VACcount='';
						$stmtA = "SELECT count(*) from vicidial_auto_calls where lead_id='$Alead_id[$acq]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArowsACQc=$sthA->rows;
						if ($sthArowsACQc > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VACcount =	$aryA[0];
							}
						$sthA->finish();

						if ($VACcount > 0)
							{
							if ($DBX) {print "vicidial_abandon_check ACTIVE CALL: $Alead_id[$acq]|$Aabandon_check_id[$acq]|$Acall_id[$acq]|$Aabandon_time[$acq]|\n";}
							$acq_active_call++;
							}
						else
							{
							### see if lead was handled by an agent after abandon
							$VALcount='';
							$stmtA = "SELECT count(*) from vicidial_agent_log where lead_id='$Alead_id[$acq]' and event_time > \"$VDL_eighteen\" and talk_epoch > $Aabandon_timeEPOCH[$acq];";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsACQv=$sthA->rows;
							if ($sthArowsACQv > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VALcount =	$aryA[0];
								}
							$sthA->finish();

							if ($VALcount > 0)
								{
								# Update vicidial_abandon_check_queue record with REJECT status
								$stmtA = "UPDATE vicidial_abandon_check_queue SET check_status='REJECT', reject_reason='Lead handled by agent, process' where abandon_check_id='$Aabandon_check_id[$acq]';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "vicidial_abandon_check_queue UPDATED agent: $affected_rows|$stmtA|\n";}
								$acq_rejected = ($acq_rejected + $affected_rows);
								$callback_debug .= "     Abandon UPDATED, Agent REJECT: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
								}
							else
								{
								### see if lead was recently in the live_inboud_log table (Y2281224360000140002)
								$Alive_channel=0;
								$Achannel='';   $Aserver_ip='';   $Acaller_id='';   $Astart_time='';   $Acomment_d='';
								$stmtA = "SELECT channel,server_ip,caller_id,start_time,comment_d from live_inbound_log where start_time > \"$VDL_hour\" and caller_id LIKE \"%$Alead_id[$acq]\" order by start_time desc limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsACQc=$sthA->rows;
								if ($sthArowsACQc > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$Achannel =		$aryA[0];
									$Aserver_ip =	$aryA[1];
									$Acaller_id =	$aryA[2];
									$Astart_time =	$aryA[3];
									$Acomment_d =	$aryA[4];
									}
								$sthA->finish();

								if ($sthArowsACQc > 0)
									{
									$stmtA = "SELECT count(*) from live_channels where server_ip='$Aserver_ip' and channel='$Achannel';";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsACQlc=$sthA->rows;
									if ($sthArowsACQlc > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$Alive_channel =		$aryA[0];
										}
									$sthA->finish();
									}
								
								if ($Alive_channel > 0) 
									{
									if ($DBX) {print "vicidial_abandon_check ACTIVE CHANNEL: $Alead_id[$acq]|$Aabandon_check_id[$acq]|$Acall_id[$acq]|$Aabandon_time[$acq]|$Aserver_ip|$Achannel|\n";}
									$acq_active_call++;
									$callback_debug .= "     Abandon ACTIVE CHANNEL: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
									}
								else
									{
									if ( ($Areject_reason[$acq] =~ /DeadCheck/) && ($Acheck_status[$acq] =~ /PROCESSING/) ) 
										{
										### call is dead, 2nd check, time to insert lead into hopper through the Non-Agent API update_lead function
										$stmtA = "UPDATE vicidial_abandon_check_queue SET check_status='COMPLETE', reject_reason='DEAD: |$now_date|$Aserver_ip|$Achannel' where abandon_check_id='$Aabandon_check_id[$acq]';";
										$affected_rows = $dbhA->do($stmtA);
										if ($DBX) {print "vicidial_abandon_check_queue UPDATED, Complete: $affected_rows|$stmtA|\n";}
										$acq_complete_dead = ($acq_complete_dead + $affected_rows);
										$callback_debug .= "     Abandon UPDATED, Complete DEAD: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";

										### find wget binary
										$exit_no_wget=0;
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
													if ($AGILOG) {$agi_string = "Can't find wget binary! Exiting...";   &agi_output;}
													$exit_no_wget=1;
													}
												}
											}
										if ($exit_no_wget > 0) 
											{
											if ($DB) {print "vicidial_abandon_check_queue ERROR, wget not found!\n";}
											$callback_debug .= "     Abandon ERROR: wget not found!\n";
											}
										else
											{
											$abandon_hopper_url='';
											$stmtA= "SELECT container_entry from vicidial_settings_containers where container_id='ABANDON_HOPPER_URL';";
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArowsAHU=$sthA->rows;
											if ($sthArowsAHU > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$abandon_hopper_url	= $aryA[0];
												}
											$sthA->finish();

											if (length($abandon_hopper_url) < 5) 
												{
												if ($DB) {print "vicidial_abandon_check_queue ERROR, ABANDON_HOPPER_URL not valid! |$abandon_hopper_url|\n";}
												$callback_debug .= "     Abandon ERROR: URL not valid! |$abandon_hopper_url|\n";
												}
											else
												{
												$temp_lead_id =			$Alead_id[$acq];
												$temp_phone_number =	$Aphone_number[$acq];
												$temp_source =			$Asource[$acq];
												$temp_call_id =			$Acall_id[$acq];
												$temp_abandon_time =	$Aabandon_time[$acq];
												$temp_reject_reason =	$Areject_reason[$acq];
												$temp_check_status =	$Acheck_status[$acq];
												$abandon_hopper_url =~ s/--A--lead_id--B--/$temp_lead_id/gi;
												$abandon_hopper_url =~ s/--A--phone_number--B--/$temp_phone_number/gi;
												$abandon_hopper_url =~ s/--A--source--B--/$temp_source/gi;
												$abandon_hopper_url =~ s/--A--call_id--B--/$temp_call_id/gi;
												$abandon_hopper_url =~ s/--A--abandon_time--B--/$temp_abandon_time/gi;
												$abandon_hopper_url =~ s/--A--reject_reason--B--/$temp_reject_reason/gi;
												$abandon_hopper_url =~ s/--A--check_status--B--/$temp_check_status/gi;
												$abandon_hopper_url =~ s/ /+/gi;
												$abandon_hopper_url =~ s/&/\\&/gi;

												### insert a new url log entry
												$SQL_log = "$abandon_hopper_url";
												$SQL_log =~ s/;|\||\\//gi;
												$stmtA = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$now_date',url_type='abandonchk',url='$SQL_log',url_response='';";
												$affected_rows = $dbhA->do($stmtA);
												$stmtB = "SELECT LAST_INSERT_ID() LIMIT 1;";
												$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$url_id = $aryA[0];
													}
												$sthA->finish();

												$url = $abandon_hopper_url;
												$url =~ s/'/\\'/gi;
												$url =~ s/"/\\"/gi;

												$secW = time();

												`$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url `;

												$secY = time();
												$response_sec = ($secY - $secW);

												open(Wdoc, "/tmp/ASUBtmpD$US$url_id$US$secX") || die "can't open /tmp/ASUBtmpD$US$url_id$US$secX: $!\n";
												@Wdoc = <Wdoc>;
												close(Wdoc);
												$i=0;
												$Wdocline_cat='';
												foreach(@Wdoc)
													{
													$Wdocline = $Wdoc[$i];
													$Wdocline =~ s/\n|\r//gi;
													$Wdocline =~ s/\t|\`|\"//gi;
													$Wdocline_cat .= "$Wdocline";
													$i++;
													}
												if (length($Wdocline_cat) < 1) 
													{
													$Wdocline_cat='<RESPONSE EMPTY>';
													}

												open(Wfile, "/tmp/ASUBtmpF$US$url_id$US$secX") || die "can't open /tmp/ASUBtmpF$US$url_id$US$secX: $!\n";
												@Wfile = <Wfile>;
												close(Wfile);
												$i=0;
												$Wfileline_cat='';
												foreach(@Wfile)
													{
													$Wfileline = $Wfile[$i];
													$Wfileline =~ s/\n|\r/!/gi;
													$Wfileline =~ s/\"|\`/'/gi;
													$Wfileline =~ s/  |\t|\`//gi;
													$Wfileline_cat .= "$Wfileline";
													$i++;
													}
												if (length($Wfileline_cat)<1) 
													{$Wfileline_cat='<HEADER EMPTY>';}

												### update url log entry
												$stmtA = "UPDATE vicidial_url_log SET url_response=\"$Wdocline_cat|$Wfileline_cat\",response_sec='$response_sec' where url_log_id='$url_id';";
												$affected_rows = $dbhA->do($stmtA);
												if ($DBX) {print "vicidial_abandon_check_queue SENT, vicidial_url_log: $url_id|$response_sec|\n";}
												$hopper_insert_sent++;
												$callback_debug .= "     Abandon SENT: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
												}
											}
										}
									else
										{
										### call is dead, 1st check, flag record
										$stmtA = "UPDATE vicidial_abandon_check_queue SET check_status='PROCESSING', reject_reason='DeadCheck $now_date' where abandon_check_id='$Aabandon_check_id[$acq]';";
										$affected_rows = $dbhA->do($stmtA);
										if ($DBX) {print "vicidial_abandon_check_queue UPDATED, Dead Check: $affected_rows|$stmtA|\n";}
										$acq_check_dead = ($acq_check_dead + $affected_rows);
										$callback_debug .= "     Abandon UPDATED, Dead Check: $affected_rows|$acq|$Aabandon_check_id[$acq]|$Alead_id[$acq]\n";
										}
									}
								}
							}
						}
					}

				$acq++;
				if ($DB) 
					{
					if ($acq =~ /10$/i) {print STDERR "0     $acq / $sthArowsACQ \r";}
					if ($acq =~ /20$/i) {print STDERR "+     $acq / $sthArowsACQ \r";}
					if ($acq =~ /30$/i) {print STDERR "|     $acq / $sthArowsACQ \r";}
					if ($acq =~ /40$/i) {print STDERR "\\     $acq / $sthArowsACQ \r";}
					if ($acq =~ /50$/i) {print STDERR "-     $acq / $sthArowsACQ \r";}
					if ($acq =~ /60$/i) {print STDERR "/     $acq / $sthArowsACQ \r";}
					if ($acq =~ /70$/i) {print STDERR "|     $acq / $sthArowsACQ \r";}
					if ($acq =~ /80$/i) {print STDERR "+     $acq / $sthArowsACQ \r";}
					if ($acq =~ /90$/i) {print STDERR "0     $acq / $sthArowsACQ \r";}
					if ($acq =~ /00$/i) 
						{
						print "$acq / $sthArowsACQ   ($acq_rejected|$acq_check_dead|$acq_complete_dead|$acq_active_call|$hopper_insert_sent|   |$Alead_id[$acq]|$Aabandon_check_id[$acq]|$Acall_id[$acq]|$Aabandon_time[$acq]| \n";
						}
					}
				}
			}
		if ($DB) {print "Abandon check queue finished -   calls: $acq   rejected: $acq_rejected ($acq_check_dead|$acq_complete_dead|$acq_active_call|$hopper_insert_sent) \n";}
		$callback_debug .= "\nAbandon check queue finished -   calls: $acq   rejected: $acq_rejected ($acq_check_dead|$acq_complete_dead|$acq_active_call|$hopper_insert_sent)\n";

		$callback_debug_flag='--ABANDON-QUEUE--';
		&callback_logger;
		}
	##########################################################
	##### END check for abandon_check_queue calls #####
	##########################################################



	usleep($CLIdelay*1000*1000);

	$stat_count++;
	$master_loop++;
	}

$dbhA->disconnect();

if($DB)
	{
	### calculate time to run script ###
	$secY = time();
	$secZ = ($secY - $secT);

	if (!$q) {print "DONE. Script execution time in seconds: $secZ\n";}
	}

exit;





### SUBROUTINES ###############################################################

sub event_logger
	{
	if ($SYSLOG)
		{
		if (!$VDHLOGfile) {$VDHLOGfile = "$PATHlogs/adapt.$year-$mon-$mday";}

		### open the log file for writing ###
		open(Lout, ">>$VDHLOGfile")
				|| die "Can't open $VDHLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}


sub adaptive_logger
	{
	if ($SYSLOG)
		{
		$VDHCLOGfile = "$PATHlogs/VDadaptive-$campaign_id[$i].$file_date";

		### open the log file for writing ###
		open(Aout, ">>$VDHCLOGfile")
				|| die "Can't open $VDHCLOGfile: $!\n";
		print Aout "$now_date$adaptive_string\n";
		close(Aout);
		}

	$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',adapt_output='$adaptive_string' where campaign_id='$campaign_id[$i]' and server_ip='ADAPT';";
	$affected_rows = $dbhA->do($stmtA);

	$adaptive_string='';
	}


sub callback_logger
	{
	if ($SYSLOG)
		{
		$VDHCLOGfile = "$PATHlogs/VDadaptive-$callback_debug_flag.$file_date";

		### open the log file for writing ###
		open(Aout, ">>$VDHCLOGfile")
				|| die "Can't open $VDHCLOGfile: $!\n";
		print Aout "$now_date$adaptive_string\n";
		close(Aout);
		}

	$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',adapt_output='$callback_debug' where campaign_id='$callback_debug_flag' and server_ip='ADAPT';";
	$affected_rows = $dbhA->do($stmtA);

	$adaptive_string='';
	}


sub get_time_now
	{
	$secX = time();
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}
	$file_date = "$year-$mon-$mday";
	$now_date = "$year-$mon-$mday $hour:$min:$sec";
	$VDL_date = "$year-$mon-$mday 00:00:00";
	$current_hourmin = "$hour$min";

	### get date-time of one hour ago ###
	$VDL_hour = ($secX - (60 * 60));
	($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_hour);
	$Vyear = ($Vyear + 1900);
	$Vmon++;
	if ($Vmon < 10) {$Vmon = "0$Vmon";}
	if ($Vmday < 10) {$Vmday = "0$Vmday";}
	$VDL_hour = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

	### get date-time of half hour ago ###
	$VDL_halfhour = ($secX - (30 * 60));
	($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_halfhour);
	$Vyear = ($Vyear + 1900);
	$Vmon++;
	if ($Vmon < 10) {$Vmon = "0$Vmon";}
	if ($Vmday < 10) {$Vmday = "0$Vmday";}
	$VDL_halfhour = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

	### get date-time of five minutes ago ###
	$VDL_five = ($secX - (5 * 60));
	($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_five);
	$Vyear = ($Vyear + 1900);
	$Vmon++;
	if ($Vmon < 10) {$Vmon = "0$Vmon";}
	if ($Vmday < 10) {$Vmday = "0$Vmday";}
	$VDL_five = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

	### get epoch of ninty seconds ago ###
	$VDL_ninty = ($secX - (1 * 90));

	### get date-time of one minute ago ###
	$VDL_one = ($secX - (1 * 60));
	($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_one);
	$Vyear = ($Vyear + 1900);
	$Vmon++;
	if ($Vmon < 10) {$Vmon = "0$Vmon";}
	if ($Vmday < 10) {$Vmday = "0$Vmday";}
	$VDL_one = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

	### get date-time of 18 hours ago ###
	$VDL_eighteen = ($secX - (18 * 60 * 60));
	($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_eighteen);
	$Vyear = ($Vyear + 1900);
	$Vmon++;
	if ($Vmon < 10) {$Vmon = "0$Vmon";}
	if ($Vmday < 10) {$Vmday = "0$Vmday";}
	$VDL_eighteen = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

	$timeclock_end_of_day_NOW=0;
	### Grab system_settings values from the database
	$stmtA = "SELECT count(*) from system_settings where timeclock_end_of_day LIKE \"%$current_hourmin%\";";
	if ($DBX) {print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$timeclock_end_of_day_NOW =	$aryA[0];
		}
	$sthA->finish();

	if ($timeclock_end_of_day_NOW > 0)
		{
		$event_string = "End of Day, shutting down this script in 10 seconds, script will resume in 60 seconds...";
			if ($DB) {print "\n$event_string\n\n\n";}
		&event_logger;	

		usleep(10*1000*1000);

		exit;
		}
	}


sub count_agents_lines
	{
	### Calculate campaign-wide agent waiting and calls waiting differential
	$stat_it=15;
	$total_agents[$i]=0;
	$total_remote_agents[$i]=0;
	$ready_agents[$i]=0;
	$waiting_calls[$i]=0;
	$ready_diff_total[$i]=0;
	$waiting_diff_total[$i]=0;
	$total_agents_total[$i]=0;
	$ready_diff_avg[$i]=0;
	$waiting_diff_avg[$i]=0;
	$total_agents_avg[$i]=0;
	$stat_differential[$i]=0;

	$stmtA = "SELECT count(*),status from vicidial_live_agents where ( (campaign_id='$campaign_id[$i]') or (dial_campaign_id='$campaign_id[$i]') $drop_ingroup_SQL[$i] ) and last_update_time > '$VDL_one' group by status;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$VCSagent_count[$i] =	$aryA[0];
		$VCSagent_status[$i] =	$aryA[1];
		$rec_count++;
		if ($VCSagent_status[$i] =~ /READY|DONE/) {$ready_agents[$i] = ($ready_agents[$i] + $VCSagent_count[$i]);}
		$total_agents[$i] = ($total_agents[$i] + $VCSagent_count[$i]);
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) FROM vicidial_auto_calls where campaign_id='$campaign_id[$i]' and status IN('LIVE');";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$waiting_calls[$i] = $aryA[0];
		}
	$sthA->finish();

	$stat_ready_agents[$i][$stat_count] = $ready_agents[$i];
	$stat_waiting_calls[$i][$stat_count] = $waiting_calls[$i];
	$stat_total_agents[$i][$stat_count] = $total_agents[$i];

	if ($stat_count < 15) 
		{
		$stat_it = $stat_count;
		$stat_B = 1;
		}
	else
		{
		$stat_B = ($stat_count - 14);
		}

	$it=0;
	while($it < $stat_it)
		{
		$it_ary = ($it + $stat_B);
		$ready_diff_total[$i] = ($ready_diff_total[$i] + $stat_ready_agents[$i][$it_ary]);
		$waiting_diff_total[$i] = ($waiting_diff_total[$i] + $stat_waiting_calls[$i][$it_ary]);
		$total_agents_total[$i] = ($total_agents_total[$i] + $stat_total_agents[$i][$it_ary]);
	#		$event_string="$stat_count $it_ary   $stat_total_agents[$i][$it_ary]|$stat_ready_agents[$i][$it_ary]|$stat_waiting_calls[$i][$it_ary]";
	#		if ($DB) {print "     $event_string\n";}
	#		&event_logger;
		$it++;
		}

	# LIVE CAMPAIGN CALLS RIGHT NOW
	if ($daily_stats > 0)
		{
		$stmtA = "SELECT count(*) from vicidial_live_agents where ( (campaign_id='$campaign_id[$i]') or (dial_campaign_id='$campaign_id[$i]') $drop_ingroup_SQL[$i] ) and last_update_time > '$VDL_one' and extension LIKE \"R/%\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$total_remote_agents[$i] = $aryA[0];
			}
		$sthA->finish();
		}

	if ($ready_diff_total[$i] > 0) 
		{$ready_diff_avg[$i] = ($ready_diff_total[$i] / $stat_it);}
	if ($waiting_diff_total[$i] > 0) 
		{$waiting_diff_avg[$i] = ($waiting_diff_total[$i] / $stat_it);}
	if ($total_agents_total[$i] > 0) 
		{$total_agents_avg[$i] = ($total_agents_total[$i] / $stat_it);}
	$stat_differential[$i] = ($ready_diff_avg[$i] - $waiting_diff_avg[$i]);

	$event_string="CAMPAIGN DIFFERENTIAL: $total_agents_avg[$i]   $stat_differential[$i]   ($ready_diff_avg[$i] - $waiting_diff_avg[$i])";
	if ($DBX) {print "$campaign_id[$i]|$event_string\n";}
	if ($DB) {print "     $event_string\n";}
	$debug_camp_output .= "$event_string\n";

	&event_logger;

	#	$stmtA = "UPDATE vicidial_campaign_stats SET differential_onemin[$i]='$stat_differential[$i]', agents_average_onemin[$i]='$total_agents_avg[$i]' where campaign_id='$DBIPcampaign[$i]';";
	#	$affected_rows = $dbhA->do($stmtA);
	}



sub shared_agent_process
	{
	$RESETshared_agent_count_updater++;
	$SHARED_campaigns_ct=0;
	$SHARED_agents_ct=0;
	@SHARED_campaigns=@MT;
	@SHARED_campaigns_drop_ig=@MT;
	@SHARED_campaigns_agnt_ct=@MT;
	@SHARED_campaigns_agnt_dl=@MT;
	@SHARED_campaigns_dial_ct=@MT;
	@SHARED_agents=@MT;
	@SHARED_agents_cig=@MT;
	@SHARED_agents_dig=@MT;
	$debug_shared_output='';
	if ($SSallow_shared_dial > 0) 
		{
		$camp_SHARED_SQL='';
		$drop_SHARED_SQL='';
		# Get list of active SHARED_ campaigns
		$stmtA = "SELECT campaign_id,drop_inbound_group from vicidial_campaigns where active='Y' and dial_method IN('SHARED_RATIO','SHARED_ADAPT_HARD_LIMIT','SHARED_ADAPT_TAPERED','SHARED_ADAPT_AVERAGE');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		while ($sthArows > $SHARED_campaigns_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$camp_SHARED_SQL .=	 "'$aryA[0]',";
			$SHARED_campaigns[$SHARED_campaigns_ct] = $aryA[0];
			$SHARED_campaigns_agnt_ct[$SHARED_campaigns_ct] = 0;
			$SHARED_campaigns_agnt_dl[$SHARED_campaigns_ct] = 0;
			$SHARED_campaigns_dial_ct[$SHARED_campaigns_ct] = 0;
			if (length($aryA[1]) > 0) 
				{
				$SHARED_campaigns_drop_ig[$SHARED_campaigns_ct] = $aryA[1];
				$SQL_group_id=$aryA[1];   $SQL_group_id =~ s/_/\\_/gi;
				$temp_drop_ingroup_SQL = "(closer_campaigns LIKE \"% $SQL_group_id %\")";
				if (length($drop_SHARED_SQL) > 10) {$drop_SHARED_SQL .=	 " or ";}
				$drop_SHARED_SQL .=	 "$temp_drop_ingroup_SQL";
				}
			else {if ($DBX) {print "Empty Drop In-Group for campaign: $aryA[0]\n";}}
			$SHARED_campaigns_ct++;
			}
		$sthA->finish();
		chop($camp_SHARED_SQL);
		if (length($camp_SHARED_SQL)<2) 
			{$camp_SHARED_SQL="'-NONE-'";}
		if (length($drop_SHARED_SQL)<2) 
			{$drop_SHARED_SQL="closer_campaigns='----------'";}
		$drop_SHARED_SQL = "and ($drop_SHARED_SQL)";

		$now_epoch = time();
		$BDtarget = ($now_epoch - 10);
		($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
		$Byear = ($Byear + 1900);
		$Bmon++;
		if ($Bmon < 10) {$Bmon = "0$Bmon";}
		if ($Bmday < 10) {$Bmday = "0$Bmday";}
		if ($Bhour < 10) {$Bhour = "0$Bhour";}
		if ($Bmin < 10) {$Bmin = "0$Bmin";}
		if ($Bsec < 10) {$Bsec = "0$Bsec";}
			$BDtsSQLdate = "$Byear$Bmon$Bmday$Bhour$Bmin$Bsec";

		$agent_SHARED_SQL='';
		# Get list of active SHARED_ campaigns
		$stmtA = "SELECT user,closer_campaigns,dial_campaign_id from vicidial_live_agents where last_update_time > '$BDtsSQLdate' $drop_SHARED_SQL;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "$sthArows|$stmtA|\n";}
		$SHARED_agents_ct=0;
		while ($sthArows > $SHARED_agents_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$SHARED_agents[$SHARED_agents_ct] = $aryA[0];
			$SHARED_agents_cig[$SHARED_agents_ct] = $aryA[1];
			$SHARED_agents_dig[$SHARED_agents_ct] = $aryA[2];
			$agent_SHARED_SQL .=	 "'$aryA[0]',";
			$SHARED_agents_ct++;
			}
		$sthA->finish();
		chop($agent_SHARED_SQL);
		if (length($agent_SHARED_SQL)<2) 
			{$agent_SHARED_SQL="'-NONE-'";}

		$event_string="SHARED CAMPAIGNS: $SHARED_campaigns_ct   $SHARED_agents_ct";
		$DBX_string="CAMP SQL: $camp_SHARED_SQL\nDROP SQL: $drop_SHARED_SQL\nAGNT SQL: $agent_SHARED_SQL\n";
		if ($DBX) {print "$DBX_string";}
		if ($DB) {print "     $event_string\n";}
		$DBX_string =~ s/'//gi;
		$debug_shared_output .= "$event_string\n$DBX_string";

		&event_logger;

		# update debug output
		$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',debug_output='$debug_shared_output' where campaign_id='--ALL--' and server_ip='SHARED';";
		$affected_rows = $dbhA->do($stmtA);

		if ($SSallow_shared_dial > 1) 
			{
			$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$SHARED_agents_ct',total_calls='0',debug_output='BUILD: $build\n$debug_shared_output',campaign_id='--ALL--',server_ip='SHARED';";
			$affected_rows = $dbhA->do($stmtA);
			}

		### If there are active SHARED_ campaigns and agents set to take calls from the drop-in-groups of those campaigns, then start processing SHARED_ agents
		if ( ($SHARED_campaigns_ct > 0) and ($SHARED_agents_ct > 0) ) 
			{
			$agent_shared_output="Shared Agent Rotation process last ran: $now_date \n";

			$sh_ct=0;
			while($SHARED_agents_ct > $sh_ct)
				{
				$cp_ct=0;
				while($SHARED_campaigns_ct > $cp_ct)
					{
					$temp_drop = $SHARED_campaigns_drop_ig[$cp_ct];
					if ( ($SHARED_agents_cig[$sh_ct] =~ / $temp_drop /) && (length($temp_drop) > 0) )
						{
						$SHARED_campaigns_agnt_ct[$cp_ct]++;
						# insert/update vicidial_agent_dial_campaigns record for this agent/campaign
						$stmtA = "INSERT IGNORE INTO vicidial_agent_dial_campaigns SET validate_time='$now_date',group_id='$temp_drop',campaign_id='$SHARED_campaigns[$cp_ct]',user='$SHARED_agents[$sh_ct]',dial_time='2020-01-01 00:00:00' ON DUPLICATE KEY UPDATE validate_time='$now_date',group_id='$temp_drop';";
						$affected_rows_vadc_up = $dbhA->do($stmtA);
						}
					$cp_ct++;
					}
				# delete old vicidial_agent_dial_campaigns entries for this agent
				$stmtA = "DELETE FROM vicidial_agent_dial_campaigns WHERE validate_time < \"$now_date\" and user='$SHARED_agents[$sh_ct]';";
				$affected_rows_vadc_del = $dbhA->do($stmtA);

				# gather oldest dial campaign for this agent
				$next_dial_campaign_id='';
				$stmtA = "SELECT campaign_id from vicidial_agent_dial_campaigns where user='$SHARED_agents[$sh_ct]' order by dial_time asc limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($DBX) {print "$sthArows|$stmtA|\n";}
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$next_dial_campaign_id = $aryA[0];

					$cp_ct=0;
					while($SHARED_campaigns_ct > $cp_ct)
						{
						if ($SHARED_campaigns[$cp_ct] =~ /^$next_dial_campaign_id$/)
							{
							$SHARED_campaigns_agnt_dl[$cp_ct]++;
							}
						$cp_ct++;
						}

					}
				$sthA->finish();

				# update dial campaign for this agent vicidial_agent_dial_campaigns
				$stmtA = "UPDATE vicidial_agent_dial_campaigns SET dial_time = \"$now_date\" where user='$SHARED_agents[$sh_ct]' and campaign_id='$next_dial_campaign_id';";
				$affected_rows_vadc_dial = $dbhA->do($stmtA);

				# update dial campaign for this agent in vicidial_live_agents
				$stmtA = "UPDATE vicidial_live_agents SET dial_campaign_id='$next_dial_campaign_id' where user='$SHARED_agents[$sh_ct]';";
				$affected_rows_vla_dial = $dbhA->do($stmtA);

				$agent_temp = $SHARED_agents[$sh_ct];
				$sh_ct++;
				$agent_shared_output .= "Agent $sh_ct: $agent_temp   |$next_dial_campaign_id|$affected_rows_vadc_up|$affected_rows_vadc_del|$affected_rows_vadc_dial|$affected_rows_vla_dial|\n";
				}

			$cp_ct=0;
			$agent_shared_output .= "\nSHARED Campaigns:\n";
			$total_shared_calls_counter=0;
			while($SHARED_campaigns_ct > $cp_ct)
				{
				$shared_camp_output="$SHARED_campaigns[$cp_ct]   Drop In-Group: $SHARED_campaigns_drop_ig[$cp_ct]   Shared Agents: $SHARED_campaigns_agnt_ct[$cp_ct] (Dial Agents: $SHARED_campaigns_agnt_dl[$cp_ct])";
				$agent_shared_output .= "$shared_camp_output\n";

				# update debug output for each campaign
				$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',adapt_output='$shared_camp_output' where campaign_id='$SHARED_campaigns[$cp_ct]' and server_ip='SHARED';";
				$affected_rows = $dbhA->do($stmtA);

				### see how many total VDAD calls are going on right now for this shared campaign
				$temp_calls_counter=0;
				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where campaign_id IN('$SHARED_campaigns_drop_ig[$cp_ct]','$SHARED_campaigns[$cp_ct]') and status IN('SENT','RINGING','LIVE','XFER','CLOSER','IVR');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$temp_calls_counter = $aryA[0];
					$total_shared_calls_counter = ($total_shared_calls_counter + $temp_calls_counter);
					}
				$sthA->finish();

				if ($SSallow_shared_dial > 1) 
					{
					$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$SHARED_campaigns_agnt_dl[$cp_ct]',total_calls='$temp_calls_counter',adapt_output='BUILD: $build\n$shared_camp_output',campaign_id='$SHARED_campaigns[$cp_ct]',server_ip='SHARED';";
					$affected_rows = $dbhA->do($stmtA);
					}
				$cp_ct++;
				}

			# update debug output
			$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',adapt_output='$agent_shared_output' where campaign_id='--ALL--' and server_ip='SHARED';";
			$affected_rows = $dbhA->do($stmtA);

			if ($SSallow_shared_dial > 1) 
				{
				$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$SHARED_agents_ct',total_calls='$total_shared_calls_counter',adapt_output='BUILD: $build\n$agent_shared_output',campaign_id='--ALL--',server_ip='SHARED';";
				$affected_rows = $dbhA->do($stmtA);
				}
			}
		}
	}

sub calculate_drops
	{
	$ofcom_uk_drop_calc_ANY=0;
	$camp_ANS_STAT_SQL='';
	# GET LIST OF HUMAN-ANSWERED STATUSES
	$stmtA = "SELECT status from vicidial_statuses where human_answered='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_ANS_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();

	$stmtA = "SELECT status from vicidial_campaign_statuses where campaign_id='$campaign_id[$i]' and human_answered='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_ANS_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();

	chop($camp_ANS_STAT_SQL);
	if (length($camp_ANS_STAT_SQL)<2) 
		{$camp_ANS_STAT_SQL="'-NONE-'";}

	$debug_camp_output .= "     CAMPAIGN ANSWERED STATUSES: $campaign_id[$i]|$camp_ANS_STAT_SQL|\n";
	if ($DBX) {print "     CAMPAIGN ANSWERED STATUSES: $campaign_id[$i]|$camp_ANS_STAT_SQL|\n";}


	$camp_AM_STAT_SQL='';
	# GET LIST OF HUMAN-ANSWERED STATUSES
	$stmtA = "SELECT status from vicidial_statuses where answering_machine='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_AM_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();

	$stmtA = "SELECT status from vicidial_campaign_statuses where campaign_id='$campaign_id[$i]' and answering_machine='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_AM_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();

	chop($camp_AM_STAT_SQL);
	if (length($camp_AM_STAT_SQL)<2) 
		{$camp_AM_STAT_SQL="'-NONE-'";}

	$debug_camp_output .= "     CAMPAIGN ANSWERING MACHINE STATUSES: $campaign_id[$i]|$camp_AM_STAT_SQL|\n";
	if ($DBX) {print "     CAMPAIGN ANSWERING MACHINE STATUSES: $campaign_id[$i]|$camp_AM_STAT_SQL|\n";}

	$RESETdrop_count_updater++;
	$VCScalls_today[$i]=0;
	$VCSanswers_today[$i]=0;
	$VCSagenthandled_today[$i]=0;
	$VCSam_today[$i]=0;
	$VCSdrops_today[$i]=0;
	$VCSdrops_today_pct[$i]=0;
	$VCSdrops_answers_today_pct[$i]=0;
	$VCScalls_hour[$i]=0;
	$VCSanswers_hour[$i]=0;
	$VCSdrops_hour[$i]=0;
	$VCSdrops_hour_pct[$i]=0;
	$VCScalls_halfhour[$i]=0;
	$VCSanswers_halfhour[$i]=0;
	$VCSdrops_halfhour[$i]=0;
	$VCSdrops_halfhour_pct[$i]=0;
	$VCScalls_five[$i]=0;
	$VCSanswers_five[$i]=0;
	$VCSdrops_five[$i]=0;
	$VCSdrops_five_pct[$i]=0;
	$VCScalls_one[$i]=0;
	$VCSanswers_one[$i]=0;
	$VCSdrops_one[$i]=0;
	$VCSdrops_one_pct[$i]=0;
	$VCSagent_nonpause_time[$i]=0;
	$VCSagent_pause_today[$i]=0;
	$VCSagent_wait_today[$i]=0;
	$VCSagent_custtalk_today[$i]=0;
	$VCSagent_acw_today[$i]=0;
	$VCSagent_calls_today[$i]=0;
	$VCSlive_calls[$i]=0;
	$VCSpark_calls_today[$i]=0;
	$VCSpark_sec_today[$i]=0;

	# LAST ONE MINUTE CALL AND DROP STATS
	$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_one';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$VCScalls_one[$i] =	$aryA[0];
		}
	$sthA->finish();

	if ($VCScalls_one[$i] > 0)
		{
		# LAST MINUTE ANSWERS
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_one' and status IN($camp_ANS_STAT_SQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSanswers_one[$i] =	$aryA[0];
			}
		$sthA->finish();

		# LAST MINUTE DROPS
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_one' and status IN('DROP','XDROP');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSdrops_one[$i] =	$aryA[0];
			if ($VCSdrops_one[$i] > 0)
				{
				$VCSdrops_one_pct[$i] = ( ($VCSdrops_one[$i] / $VCScalls_one[$i]) * 100 );
				$VCSdrops_one_pct[$i] = sprintf("%.2f", $VCSdrops_one_pct[$i]);	
				}
			}
		$sthA->finish();
		}

	########## TODAY CALL, ANSWERED, MACHINE, AGENT AND DROP STATS ##########
	### BEGIN TODAY TOTAL CALLS

	## BEGIN CACHED HOURLY ANALYSIS: CALLS
	if ($VLhour_counts > 0) 
		{
		$secH = time();
		($HRsec,$HRmin,$HRhour,$HRmday,$HRmon,$HRyear,$HRwday,$HRyday,$HRisdst) = localtime(time);
		($HRsec_prev,$HRmin_prev,$HRhour_prev,$HRmday_prev,$HRmon_prev,$HRyear_prev,$HRwday_prev,$HRyday_prev,$HRisdst_prev) = localtime(time-3600);
		$HRyear = ($HRyear + 1900);
		$HRmon++;
		$HRhour_test = $HRhour;
		if ($HRmon < 10) {$HRmon = "0$HRmon";}
		if ($HRmday < 10) {$HRmday = "0$HRmday";}
		if ($HRhour < 10) {$HRhour = "0$HRhour";}

		$HRyear_prev = ($HRyear_prev + 1900);
		$HRmon_prev++;
		if ($HRmon_prev < 10) {$HRmon_prev = "0$HRmon_prev";}
		if ($HRmday_prev < 10) {$HRmday_prev = "0$HRmday_prev";}
		if ($HRhour_prev < 10) {$HRhour_prev = "0$HRhour_prev";}

		$VL_today = "$HRyear-$HRmon-$HRmday";
		$VL_current_hour_date = "$HRyear-$HRmon-$HRmday $HRhour:00:00";
		$VL_previous_hour_date = "$HRyear_prev-$HRmon_prev-$HRmday_prev $HRhour_prev:00:00";
		$VL_day_start_date = "$HRyear-$HRmon-$HRmday 00:00:00";
		$VL_prev_day_start_date = "$HRyear_prev-$HRmon_prev-$HRmday_prev 00:00:00";
		$VL_current_hour_calls=0;

		### get date-time of start of next hour ###
		$VL_next_hour = ($secH + (60 * 60));
		($NHsec,$NHmin,$NHhour,$NHmday,$NHmon,$NHyear,$NHwday,$NHyday,$NHisdst) = localtime($VL_next_hour);
		$NHyear = ($NHyear + 1900);
		$NHmon++;
		if ($NHmon < 10) {$NHmon = "0$NHmon";}
		if ($NHmday < 10) {$NHmday = "0$NHmday";}
		$VL_next_hour_date = "$NHyear-$NHmon-$NHmday $NHhour:00:00";

		if ($DBX > 1) {print "STARTING CACHED HOURLY TOTAL CALLS:  |$VL_current_hour_date|$VL_next_hour_date|\n";}

		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_current_hour_date';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VL_current_hour_calls =	$aryA[0];
			}
		$sthA->finish();
		if ($DBX) {print "VCHC CURRENT HOUR CALLS: |$sthArows|$VL_current_hour_calls|$stmtA|\n";}

		$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_current_hour_date',type='CALLS',next_hour='$VL_next_hour_date',last_update=NOW(),calls='$VL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}

		if ($HRhour_test > 0)
			{
			# check to see if cached hour totals already exist
			$VCHC_entry_count=0;
			$stmtA = "SELECT count(*) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='CALLS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCHC_entry_count =	$aryA[0];
				}
			$sthA->finish();
			if ($DBX) {print "VCHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

			# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
			if ($VCHC_entry_count >= $HRhour_test) 
				{
				$VCHC_cache_calls=0;
				$stmtA = "SELECT sum(calls) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='CALLS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_cache_calls =	$aryA[0];
					}
				$sthA->finish();
				$VCScalls_today[$i] = ($VCHC_cache_calls + $VL_current_hour_calls);
				if ($DBX) {print "VCHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VCScalls_today[$i])|$stmtA|\n";}
				}
			else
				{
				@VCHC_hour=@MT;
				@VCHC_date_hour=@MT;
				@VCHC_next_hour=@MT;
				@VCHC_last_update=@MT;
				@VCHC_calls=@MT;
				$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='CALLS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour order by hr;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows_hr=$sthA->rows;
				$j=0;
				while ($sthArows_hr > $j)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_hour[$j] =		$aryA[0];
					$VCHC_date_hour[$j] =	$aryA[1];
					$VCHC_next_hour[$j] =	$aryA[2];
					$VCHC_last_update[$j] =	$aryA[3];
					$VCHC_calls[$j] =		$aryA[4];
					$j++;
					}
				$sthA->finish();
				if ($DBX) {print "VCHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

				$j=0;
				while ($j < $HRhour_test) 
					{
					$k=0;
					$cache_hour_found=0;
					while ($sthArows_hr > $k)
						{
						if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # JCJ changed || to &&
							{
							$VCScalls_today[$i] = ($VCScalls_today[$i] + $VCHC_calls[$k]);
							$cache_hour_found++;
							if ($DBX) {print "VCHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VCScalls_today[$i]|\n";}
							}
						$k++;
						}
					if ($cache_hour_found < 1) 
						{
						$j_next = ($j + 1);
						$VL_this_hour_calls=0;
						$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_today $j:00:00' and call_date < '$VL_today $j_next:00:00';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VL_this_hour_calls =	$aryA[0];
							$VCScalls_today[$i] = ($VCScalls_today[$i] + $VL_this_hour_calls);
							}
						$sthA->finish();
						if ($DBX) {print "VCHC CACHED HOUR QUERY: |$sthArows|$VL_this_hour_calls|$stmtA|\n";}

						$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_today $j:00:00',type='CALLS',next_hour='$VL_today $j_next:00:00',last_update=NOW(),calls='$VL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_this_hour_calls';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "VCHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
						}
					$j++;
					}
				$VCScalls_today[$i] = ($VCScalls_today[$i] + $VL_current_hour_calls);
				}
			}
		else
			{
			# midnight hour, so total is only current hour stats
			$VCScalls_today[$i] =	$VL_current_hour_calls;
			if ($DBX) {print "VCHC MIDNIGHT HOUR: |$VCScalls_today[$i]|\n";}
			}
		}
	## END CACHED HOURLY ANALYSIS: CALLS
	else
		{
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCScalls_today[$i] =	$aryA[0];
			}
		$sthA->finish();
		}
	### END TODAY TOTAL CALLS

	if ($VCScalls_today[$i] > 0)
		{
		# TODAY ANSWERS
		## BEGIN CACHED HOURLY ANALYSIS: ANSWERS
		if ($VLhour_counts > 0) 
			{
			$VL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_previous_hour_date' and status IN($camp_ANS_STAT_SQL);";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_previous_hour_date' and status IN($camp_ANS_STAT_SQL) group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VCL_current_hour_date_int, 11, 2);
					$VL_current_hour_calls =	$aryA[1];
					$VL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCHC CURRENT HOUR CALLS ANSWERS: |$sthArows|$VL_current_hour_date_int|$VL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_current_hour_date_int',type='ANSWERS',next_hour='$VL_next_hour_date_int',last_update=NOW(),calls='$VL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='ANSWERS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='ANSWERS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$VCSanswers_today[$i] = ($VCHC_cache_calls + $VL_current_hour_calls);
					if ($DBX) {print "VCHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VCSanswers_today[$i])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='ANSWERS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$VCSanswers_today[$i] = ($VCSanswers_today[$i] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VCSanswers_today[$i]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_today $j:00:00' and call_date < '$VL_today $j_next:00:00' and status IN($camp_ANS_STAT_SQL);";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VL_this_hour_calls =	$aryA[0];
								$VCSanswers_today[$i] = ($VCSanswers_today[$i] + $VL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCHC CACHED HOUR QUERY: |$sthArows|$VL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_today $j:00:00',type='ANSWERS',next_hour='$VL_today $j_next:00:00',last_update=NOW(),calls='$VL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$VCSanswers_today[$i] = ($VCSanswers_today[$i] + $VL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$VCSanswers_today[$i] =	$VL_current_hour_calls;
				if ($DBX) {print "VCHC MIDNIGHT HOUR: |$VCSanswers_today[$i]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: ANSWERS
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date' and status IN($camp_ANS_STAT_SQL);";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCSanswers_today[$i] =	$aryA[0];
				}
			$sthA->finish();
			}

		# TODAY ANSWERING MACHINES CODED BY AGENTS FOR UK OFCOM CALCULATION
		## BEGIN CACHED HOURLY ANALYSIS: MACHINES
		if ($VLhour_counts > 0) 
			{
			$VL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_current_hour_date' and status IN($camp_AM_STAT_SQL) and user != 'VDAD';";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_previous_hour_date' and status IN($camp_AM_STAT_SQL) and user != 'VDAD' group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VL_current_hour_date_int, 11, 2);
					$VL_current_hour_calls =	$aryA[1];
					$VL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCHC CURRENT HOUR CALLS ANSWERS: |$sthArows|$VL_current_hour_date_int|$VL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_current_hour_date_int',type='MACHINES',next_hour='$VL_next_hour_date_int',last_update=NOW(),calls='$VL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='MACHINES' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='MACHINES' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$VCSam_today[$i] = ($VCHC_cache_calls + $VL_current_hour_calls);
					if ($DBX) {print "VCHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VCSam_today[$i])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='MACHINES' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$VCSam_today[$i] = ($VCSam_today[$i] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VCSam_today[$i]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_today $j:00:00' and call_date < '$VL_today $j_next:00:00' and status IN($camp_AM_STAT_SQL) and user != 'VDAD';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VL_this_hour_calls =	$aryA[0];
								$VCSam_today[$i] = ($VCSam_today[$i] + $VL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCHC CACHED HOUR QUERY: |$sthArows|$VL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_today $j:00:00',type='MACHINES',next_hour='$VL_today $j_next:00:00',last_update=NOW(),calls='$VL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$VCSam_today[$i] = ($VCSam_today[$i] + $VL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$VCSam_today[$i] =	$VL_current_hour_calls;
				if ($DBX) {print "VCHC MIDNIGHT HOUR: |$VCSam_today[$i]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: MACHINES
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date' and status IN($camp_AM_STAT_SQL) and user != 'VDAD';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCSam_today[$i] =	$aryA[0];
				}
			$sthA->finish();
			}

		# TODAY AGENT HANDLED ANSWERED CALLS FOR UK OFCOM CALCULATION
		## BEGIN CACHED HOURLY ANALYSIS: AGENTS
		if ($VLhour_counts > 0) 
			{
			$VL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_current_hour_date' and status IN($camp_ANS_STAT_SQL) and user != 'VDAD';";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_previous_hour_date' and status IN($camp_ANS_STAT_SQL) and user != 'VDAD' group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VL_current_hour_date_int, 11, 2);
					$VL_current_hour_calls =	$aryA[1];
					$VL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCHC CURRENT HOUR CALLS ANSWERS: |$sthArows|$VL_current_hour_date_int|$VL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_current_hour_date_int',type='AGENTS',next_hour='$VL_next_hour_date_int',last_update=NOW(),calls='$VL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='AGENTS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='AGENTS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$VCSagenthandled_today[$i] = ($VCHC_cache_calls + $VL_current_hour_calls);
					if ($DBX) {print "VCHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VCSagenthandled_today[$i])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='AGENTS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$VCSagenthandled_today[$i] = ($VCSagenthandled_today[$i] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VCSagenthandled_today[$i]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_today $j:00:00' and call_date < '$VL_today $j_next:00:00' and status IN($camp_ANS_STAT_SQL) and user != 'VDAD';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VL_this_hour_calls =	$aryA[0];
								$VCSagenthandled_today[$i] = ($VCSagenthandled_today[$i] + $VL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCHC CACHED HOUR QUERY: |$sthArows|$VL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_today $j:00:00',type='AGENTS',next_hour='$VL_today $j_next:00:00',last_update=NOW(),calls='$VL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$VCSagenthandled_today[$i] = ($VCSagenthandled_today[$i] + $VL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$VCSagenthandled_today[$i] =	$VL_current_hour_calls;
				if ($DBX) {print "VCHC MIDNIGHT HOUR: |$VCSagenthandled_today[$i]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: AGENTS
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date' and status IN($camp_ANS_STAT_SQL) and user != 'VDAD';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCSagenthandled_today[$i] =	$aryA[0];
				}
			$sthA->finish();
			}

		# TODAY DROPS
		## BEGIN CACHED HOURLY ANALYSIS: DROPS
		if ($VLhour_counts > 0) 
			{
			$VL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_current_hour_date' and status IN('DROP','XDROP');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_previous_hour_date' and status IN('DROP','XDROP') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VL_current_hour_date_int, 11, 2);
					$VL_current_hour_calls =	$aryA[1];
					$VL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCHC CURRENT HOUR CALLS ANSWERS: |$sthArows|$VL_current_hour_date_int|$VL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_current_hour_date_int',type='DROPS',next_hour='$VL_next_hour_date_int',last_update=NOW(),calls='$VL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='DROPS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='DROPS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$VCSdrops_today[$i] = ($VCHC_cache_calls + $VL_current_hour_calls);
					if ($DBX) {print "VCHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VCSdrops_today[$i])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_campaign_hour_counts where campaign_id='$campaign_id[$i]' and type='DROPS' and date_hour >= '$VL_day_start_date' and date_hour < '$VL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$VCSdrops_today[$i] = ($VCSdrops_today[$i] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VCSdrops_today[$i]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VL_today $j:00:00' and call_date < '$VL_today $j_next:00:00' and status IN('DROP','XDROP');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VL_this_hour_calls =	$aryA[0];
								}
							$sthA->finish();
							if ($DBX) {print "VCHC CACHED HOUR QUERY: |$sthArows|$VL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_campaign_hour_counts SET campaign_id='$campaign_id[$i]',date_hour='$VL_today $j:00:00',type='DROPS',next_hour='$VL_today $j_next:00:00',last_update=NOW(),calls='$VL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$VCSdrops_today[$i] = ($VCSdrops_today[$i] + $VL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$VCSdrops_today[$i] =	$VL_current_hour_calls;
				if ($DBX) {print "VCHC MIDNIGHT HOUR: |$VCSdrops_today[$i]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: DROPS
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date' and status IN('DROP','XDROP');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			if ($DBX > 0) {print "$stmtA\n";}
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCSdrops_today[$i] =	$aryA[0];
				}
			$sthA->finish();
			}

		if ($VCSdrops_today[$i] > 0)
			{
			$temp_raw_drop = $VCSdrops_today[$i];
			if ( ($SSofcom_uk_drop_calc > 0) && ($ofcom_uk_drop_calc[$i] =~ /Y/) ) 
				{
				$ofcom_uk_drop_calc_ANY++;

				$debug_camp_output .= "UK OFCOM DROP CALCULATION TRIGGERED FOR THIS CAMPAIGN: ($SSofcom_uk_drop_calc|$ofcom_uk_drop_calc[$i]|$ofcom_uk_drop_calc_ANY)\n";
				if ($DBX) {print "UK OFCOM DROP CALCULATION TRIGGERED FOR THIS CAMPAIGN: ($SSofcom_uk_drop_calc|$ofcom_uk_drop_calc[$i]|$ofcom_uk_drop_calc_ANY)\n";}

				# As of December 2015, OFCOM in the UK changed their method for calculating 
				# the drop(or abandon) percentage for an outbound dialing campaign. The new 
				# formula is to estimate the number of drops that were answering machines. 
				# They do this by using the agent-answered percentage of answering machines 
				# and subtracting that percentage from the number of drops. Then that new 
				# drop number is divided by the total agent-answered human-answered calls 
				# PLUS the number of drops. This differs in several ways from the way it had 
				# been done, as well as the way the drop percentage has been calculated in 
				# the USA and Canada. This new UK drop calculation method is activated as a
				# system setting AND a campaign option. Both must be enabled for the campaign
				# to use the new method. You will see code similar to the section below for 
				# each time period that the drop percentage is calculated. In order for agent-
				# statused answering machines to be calculated properly, we have added an 
				# answering machine status flag that is used to gather those statuses.

				if ($VCSam_today[$i] > 0)
					{
					$temp_am_pct = ($VCSam_today[$i] / ($VCSagenthandled_today[$i] + $VCSam_today[$i]) );
					$temp_am_drops = ($VCSdrops_today[$i] * $temp_am_pct);
					$VCSdrops_today[$i] = ($VCSdrops_today[$i] - $temp_am_drops);
					}
				$VCSdrops_today[$i] = sprintf("%.3f", $VCSdrops_today[$i]);
				$temp_answers_today = ($VCSagenthandled_today[$i] + $VCSdrops_today[$i]);
				if ($temp_answers_today < 1) {$temp_answers_today = 1;}
				$VCSdrops_answers_today_pct[$i] = ( ($VCSdrops_today[$i] / $temp_answers_today) * 100 );
				$VCSdrops_answers_today_pct[$i] = sprintf("%.2f", $VCSdrops_answers_today_pct[$i]);
				}
			else
				{
				if ($VCSanswers_today[$i] < 1) {$VCSanswers_today[$i] = 1;}
				$VCSdrops_answers_today_pct[$i] = ( ($VCSdrops_today[$i] / $VCSanswers_today[$i]) * 100 );
				$VCSdrops_answers_today_pct[$i] = sprintf("%.2f", $VCSdrops_answers_today_pct[$i]);
				}
			$VCSdrops_today_pct[$i] = ( ($VCSdrops_today[$i] / $VCScalls_today[$i]) * 100 );
			$VCSdrops_today_pct[$i] = sprintf("%.2f", $VCSdrops_today_pct[$i]);
			}

		$debug_camp_output .= "     CALLS: $VCScalls_today[$i]   ANSWERs: $VCSanswers_today[$i]   AGENT-ANS: $VCSagenthandled_today[$i]   AGENT-AMs: $VCSam_today[$i]   DROPs: $VCSdrops_today[$i]($temp_raw_drop)   DROP-TOT PCT: $VCSdrops_today_pct[$i]   DROP-ANS PCT: $VCSdrops_answers_today_pct[$i]\n";
		if ($DBX) {print "     CALLS: $VCScalls_today[$i]   ANSWERs: $VCSanswers_today[$i]   AGENT-ANS: $VCSagenthandled_today[$i]   AGENT-AMs: $VCSam_today[$i]   DROPs: $VCSdrops_today[$i]($temp_raw_drop)   DROP-TOT PCT: $VCSdrops_today_pct[$i]   DROP-ANS PCT: $VCSdrops_answers_today_pct[$i]\n";}
		}

	# LAST HOUR CALL AND DROP STATS
	$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_hour';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$VCScalls_hour[$i] =	$aryA[0];
		}
	$sthA->finish();

	if ($VCScalls_hour[$i] > 0)
		{
		# ANSWERS LAST HOUR
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_hour' and status IN($camp_ANS_STAT_SQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSanswers_hour[$i] =	$aryA[0];
			}
		$sthA->finish();

		# DROP LAST HOUR
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_hour' and status IN('DROP','XDROP');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSdrops_hour[$i] =	$aryA[0];
			if ($VCSdrops_hour[$i] > 0)
				{
				$VCSdrops_hour_pct[$i] = ( ($VCSdrops_hour[$i] / $VCScalls_hour[$i]) * 100 );
				$VCSdrops_hour_pct[$i] = sprintf("%.2f", $VCSdrops_hour_pct[$i]);	
				}
			$rec_count++;
			}
		$sthA->finish();
		}

	# LAST HALFHOUR CALL AND DROP STATS
	$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_halfhour';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$VCScalls_halfhour[$i] =	$aryA[0];
		}
	$sthA->finish();

	if ($VCScalls_halfhour[$i] > 0)
		{
		# ANSWERS HALFHOUR
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_halfhour' and status IN($camp_ANS_STAT_SQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSanswers_halfhour[$i] =	$aryA[0];
			}
		$sthA->finish();

		# DROPS HALFHOUR
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_halfhour' and status IN('DROP','XDROP');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSdrops_halfhour[$i] =	$aryA[0];
			if ($VCSdrops_halfhour[$i] > 0)
				{
				$VCSdrops_halfhour_pct[$i] = ( ($VCSdrops_halfhour[$i] / $VCScalls_halfhour[$i]) * 100 );
				$VCSdrops_halfhour_pct[$i] = sprintf("%.2f", $VCSdrops_halfhour_pct[$i]);	
				}
			}
		$sthA->finish();
		}

	# LAST FIVE MINUTE CALL AND DROP STATS
	$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_five';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$VCScalls_five[$i] =	$aryA[0];
		}
	$sthA->finish();

	if ($VCScalls_five[$i] > 0)
		{
		# ANSWERS FIVEMINUTE
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_five' and status IN($camp_ANS_STAT_SQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSanswers_five[$i] =	$aryA[0];
			}
		$sthA->finish();

		# DROPS FIVEMINUTE
		$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date > '$VDL_five' and status IN('DROP','XDROP');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSdrops_five[$i] =	$aryA[0];
			if ($VCSdrops_five[$i] > 0)
				{
				$VCSdrops_five_pct[$i] = ( ($VCSdrops_five[$i] / $VCScalls_five[$i]) * 100 );
				$VCSdrops_five_pct[$i] = sprintf("%.2f", $VCSdrops_five_pct[$i]);	
				}
			}
		$sthA->finish();
		}
	$debug_camp_output .= "$campaign_id[$i]|$VCSdrops_five_pct[$i]|$VCSdrops_today_pct[$i]|     |$VCSdrops_today[$i] / $VCScalls_today[$i] / $VCSanswers_today[$i]|   $i\n";
	if ($DBX) {print "$campaign_id[$i]|$VCSdrops_five_pct[$i]|$VCSdrops_today_pct[$i]|     |$VCSdrops_today[$i] / $VCScalls_today[$i] / $VCSanswers_today[$i]|   $i\n";}

	# DETERMINE WHETHER TO GATHER STATUS CATEGORY STATISTICS
	$VSC_categories=0;
	$VSCupdateSQL='';
	$stmtA = "SELECT vsc_id from vicidial_status_categories where tovdad_display='Y' limit 4;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$VSC_categories[$rec_count] =	$aryA[0];
		$rec_count++;
		}
	$sthA->finish();

	$g=0;
	foreach (@VSC_categories)
		{
		$VSCcategory=$VSC_categories[$g];
		$VSCtally='';
		$CATstatusesSQL='';
		# FIND STATUSES IN STATUS CATEGORY
		$stmtA = "SELECT status from vicidial_statuses where category='$VSCcategory';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$CATstatusesSQL .=		 "'$aryA[0]',";
			$rec_count++;
			}
		$sthA->finish();

		# FIND CAMPAIGN_STATUSES IN STATUS CATEGORY
		$stmtA = "SELECT status from vicidial_campaign_statuses where category='$VSCcategory' and campaign_id='$campaign_id[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$CATstatusesSQL .=		 "'$aryA[0]',";
			$rec_count++;
			}
		$sthA->finish();

		chop($CATstatusesSQL);
		if (length($CATstatusesSQL) > 2)
			{
			# FIND STATUSES IN STATUS CATEGORY
			$stmtA = "SELECT count(*) from $vicidial_log where campaign_id='$campaign_id[$i]' and call_date >= '$VDL_date' and status IN($CATstatusesSQL);";
			#	if ($DBX) {print "|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VSCtally =		 $aryA[0];
				}
			$sthA->finish();
			}
		$g++;
		$debug_camp_output .= "     $campaign_id[$i]|$VSCcategory|$VSCtally|$CATstatusesSQL|\n";
		if ($DBX) {print "     $campaign_id[$i]|$VSCcategory|$VSCtally|$CATstatusesSQL|\n";}
		$VSCupdateSQL .= "status_category_$g='$VSCcategory',status_category_count_$g='$VSCtally',";
		}
	while ($g < 4)
		{
		$g++;
		$VSCupdateSQL .= "status_category_$g='',status_category_count_$g='0',";
		}
	chop($VSCupdateSQL);

	# AGENT NON-PAUSE TIME PULL
	$stmtA = "SELECT sum(wait_sec + talk_sec + dispo_sec) from vicidial_agent_log where campaign_id='$campaign_id[$i]' and event_time > '$VDL_date';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		if ($aryA[0] > 0)
			{$VCSagent_nonpause_time[$i] = $aryA[0];}
		}
	$sthA->finish();

	# LIVE CAMPAIGN CALLS RIGHT NOW
	if ($daily_stats > 0)
		{
		$stmtA = "SELECT count(*) from vicidial_auto_calls where campaign_id='$campaign_id[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSlive_calls[$i] = $aryA[0];
			$debug_camp_output .= "     LIVE CALLS: $aryA[0]|$stmtA\n";
			}
		$sthA->finish();
		}

	# If campaign is using a drop rate group, gather its stats
	if ($drop_rate_group[$i] !~ /DISABLED/)
		{
		$stmtA = "SELECT drops_answers_today_pct,calls_today,answers_today,drops_today,answering_machines_today,agenthandled_today from vicidial_drop_rate_groups where group_id='$drop_rate_group[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$debug_camp_output .= "     DROP RATE GROUP USED: $drop_rate_group[$i]     $aryA[0]($VCSdrops_answers_today_pct[$i])   calls:$aryA[1]($VCScalls_today[$i])   ans:$aryA[2]($VCSanswers_today[$i])   drops:$aryA[3]($VCSdrops_today[$i])   agnt-am-ans:$aryA[4]($VCSam_today[$i])$aryA[5]($VCSagenthandled_today[$i])\n";
			if ($DBX) {print "     DROP RATE GROUP USED: $drop_rate_group[$i]     $aryA[0]($VCSdrops_answers_today_pct[$i])   calls:$aryA[1]($VCScalls_today[$i])   ans:$aryA[2]($VCSanswers_today[$i])   drops:$aryA[3]($VCSdrops_today[$i])   agnt-am-ans:$aryA[4]($VCSam_today[$i])$aryA[5]($VCSagenthandled_today[$i])\n";}
			$VCSdrops_answers_today_pct[$i] =	$aryA[0];
			}
		$sthA->finish();
		}
	
	# if campaign realtime agent time stats is enabled, gather those here
	if ($realtime_agent_time_stats[$i] =~ /WAIT_CUST_ACW/)
		{
		$stmtA = "SELECT sum(pause_sec),sum(wait_sec),sum(talk_sec) - sum(dead_sec) as custtalk,sum(dispo_sec) + sum(dead_sec) as acw from vicidial_agent_log where event_time > '$VDL_date' and campaign_id='$campaign_id[$i]' and pause_sec < 65000 and wait_sec < 65000 and talk_sec < 65000 and dispo_sec < 65000 and dead_sec < 65000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$debug_camp_output .= "     AGENT TIME STATS: $aryA[0] $aryA[1] $aryA[2] $aryA[3]|$stmtA\n";
			if ($DBX) {print "     AGENT TIME STATS: $aryA[0] $aryA[1] $aryA[2] $aryA[3]|$stmtA\n";}
			$VCSagent_pause_today[$i] =		$aryA[0];
			$VCSagent_wait_today[$i] =		$aryA[1];
			$VCSagent_custtalk_today[$i] =	$aryA[2];
			$VCSagent_acw_today[$i] =		$aryA[3];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_agent_log where event_time > '$VDL_date' and campaign_id='$campaign_id[$i]' and lead_id > 0;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$debug_camp_output .= "     AGENT CALLS: $aryA[0]|$stmtA\n";
			if ($DBX) {print "     AGENT CALLS: $aryA[0]|$stmtA\n";}
			$VCSagent_calls_today[$i] =		$aryA[0];
			}
		$sthA->finish();
		}

	$stmtA = "SELECT count(*),sum(parked_sec) from park_log where campaign_id='$campaign_id[$i]' and parked_time > '$VDL_date';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$VCSpark_calls_today[$i] =	$aryA[0];
		$VCSpark_sec_today[$i] =	$aryA[1];
		}
	$sthA->finish();

	$stmtA = "UPDATE vicidial_campaign_stats SET calls_today='$VCScalls_today[$i]',answers_today='$VCSanswers_today[$i]',drops_today='$VCSdrops_today[$i]',drops_today_pct='$VCSdrops_today_pct[$i]',drops_answers_today_pct='$VCSdrops_answers_today_pct[$i]',calls_hour='$VCScalls_hour[$i]',answers_hour='$VCSanswers_hour[$i]',drops_hour='$VCSdrops_hour[$i]',drops_hour_pct='$VCSdrops_hour_pct[$i]',calls_halfhour='$VCScalls_halfhour[$i]',answers_halfhour='$VCSanswers_halfhour[$i]',drops_halfhour='$VCSdrops_halfhour[$i]',drops_halfhour_pct='$VCSdrops_halfhour_pct[$i]',calls_fivemin='$VCScalls_five[$i]',answers_fivemin='$VCSanswers_five[$i]',drops_fivemin='$VCSdrops_five[$i]',drops_fivemin_pct='$VCSdrops_five_pct[$i]',calls_onemin='$VCScalls_one[$i]',answers_onemin='$VCSanswers_one[$i]',drops_onemin='$VCSdrops_one[$i]',drops_onemin_pct='$VCSdrops_one_pct[$i]',agent_non_pause_sec='$VCSagent_nonpause_time[$i]',agent_calls_today='$VCSagent_calls_today[$i]',agent_pause_today='$VCSagent_pause_today[$i]',agent_wait_today='$VCSagent_wait_today[$i]',agent_custtalk_today='$VCSagent_custtalk_today[$i]',agent_acw_today='$VCSagent_acw_today[$i]',answering_machines_today='$VCSam_today[$i]',agenthandled_today='$VCSagenthandled_today[$i]',park_calls_today='$VCSpark_calls_today[$i]',park_sec_today='$VCSpark_sec_today[$i]',$VSCupdateSQL where campaign_id='$campaign_id[$i]';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DBX) {print "OUTBOUND $campaign_id[$i]|$affected_rows|$stmtA|\n";}

	$debug_camp_output =~ s/;|\\\\|\/|\'//gi;
	$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',debug_output='$debug_camp_output' where campaign_id='$campaign_id[$i]' and server_ip='ADAPT';";
	$affected_rows = $dbhA->do($stmtA);
	$debug_camp_output='';

	##### BEGIN - DAILY STATS UPDATE CAMPAIGN
	if ($daily_stats > 0)
		{
		$STATSmax_outbound=0;
		$STATSmax_agents=0;
		$STATSmax_remote_agents=0;
		$STATStotal_calls=0;
		$stmtA = "SELECT max_outbound,max_agents,max_remote_agents,total_calls from vicidial_daily_max_stats where campaign_id='$campaign_id[$i]' and stats_type='CAMPAIGN' and stats_flag='OPEN' order by update_time desc limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$STATSmax_outbound =		$aryA[0];
			$STATSmax_agents =			$aryA[1];
			$STATSmax_remote_agents =	$aryA[2];
			$STATStotal_calls =			$aryA[3];
			$sthA->finish();

			$update_SQL='';
			if ($STATSmax_outbound < $VCSlive_calls[$i])
				{$update_SQL .= ",max_outbound='$VCSlive_calls[$i]'";}
			if ($STATSmax_agents < $total_agents[$i]) 
				{$update_SQL .= ",max_agents='$total_agents[$i]'";}
			if ($STATSmax_remote_agents < $total_remote_agents[$i])
				{$update_SQL .= ",max_remote_agents='$total_remote_agents[$i]'";}
			if ($STATStotal_calls != $VCScalls_today[$i]) 
				{$update_SQL .= ",total_calls='$VCScalls_today[$i]'";}
			if (length($update_SQL) > 5) 
				{
				$stmtA = "UPDATE vicidial_daily_max_stats SET update_time=NOW()$update_SQL where campaign_id='$campaign_id[$i]' and stats_type='CAMPAIGN' and stats_flag='OPEN';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DAILY STATS UPDATE $campaign_id[$i]|$affected_rows|$stmtA|\n";}
				}
			}
		else
			{
			$sthA->finish();

			$stmtA = "INSERT INTO vicidial_daily_max_stats SET stats_date='$file_date',update_time=NOW(),max_outbound='$VCSlive_calls[$i]',max_agents='$total_agents[$i]',max_remote_agents='$total_remote_agents[$i]',total_calls='$VCScalls_today[$i]',campaign_id='$campaign_id[$i]',stats_type='CAMPAIGN',stats_flag='OPEN';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "DAILY STATS INSERT $campaign_id[$i]|$affected_rows|$stmtA|\n";}
			}
		}
	##### END - DAILY STATS UPDATE CAMPAIGN
	}



sub drop_rate_group_gather
	{
	################################################################################
	#### BEGIN gather drop rate group stats
	################################################################################
	if ($DB) {print "\n     STARTING DROP RATE GROUP SECTION:\n";}

	# Gather drop rate groups
	@DRgroup=@MT;
	$stmtA = "SELECT group_id from vicidial_drop_rate_groups;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsDR=$sthA->rows;
	$dr=0;
	while ($sthArowsDR > $dr)
		{
		@aryA = $sthA->fetchrow_array;
		$DRgroup[$dr] =		 $aryA[0];
		$dr++;
		}
	$sthA->finish();

	$dr=0;
	while ($sthArowsDR > $dr)
		{
		$DRcalls_today=0;
		$DRanswers_today=0;
		$DRdrops_today=0;
		$DRdrops_today_pct=0;
		$DRdrops_answers_today_pct=0;
		$DRanswering_machines_today=0;
		$DRagenthandled_today=0;
		$stmtA = "SELECT count(*),sum(calls_today),sum(answers_today),sum(drops_today),sum(answering_machines_today),sum(agenthandled_today) from vicidial_campaign_stats vcs, vicidial_campaigns vc where vcs.campaign_id=vc.campaign_id and vc.drop_rate_group='$DRgroup[$dr]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			if ($aryA[0] > 0)
				{
				$DRcalls_today =				$aryA[1];
				$DRanswers_today =				$aryA[2];
				$DRdrops_today =				$aryA[3];
				$DRanswering_machines_today =	$aryA[4];
				$DRagenthandled_today =			$aryA[5];
				}
			}
		$sthA->finish();
		if ($DRdrops_today > 0)
			{
			if ($DRanswers_today < 1) {$DRanswers_today = 1;}

			##### UK OFCOM DROP PCT CALCULATION
			if ($DB) {print "     UK OFCOM DROP RATE CALCULATION CHECK: ($SSofcom_uk_drop_calc|$ofcom_uk_drop_calc_ANY)\n";}
			if ( ($SSofcom_uk_drop_calc > 0) && ($ofcom_uk_drop_calc_ANY > 0) )
				{
				# Keeping in mind that the campaign drop numbers are already adjusted to the OFCOM
				# number of drops, so we just have to add the drops to answers to generate the new
				# drop-group drop-answer percentage

				$temp_answers_today = ($DRagenthandled_today + $DRdrops_today);
				if ($temp_answers_today < 1) {$temp_answers_today = 1;}
				$DRdrops_answers_today_pct = ( ($DRdrops_today / $temp_answers_today) * 100 );
				$DRdrops_answers_today_pct = sprintf("%.2f", $DRdrops_answers_today_pct);
				}
			else
				{
				$DRdrops_answers_today_pct = ( ($DRdrops_today / $DRanswers_today) * 100 );
				$DRdrops_answers_today_pct = sprintf("%.2f", $DRdrops_answers_today_pct);
				}
			$DRdrops_today_pct = ( ($DRdrops_today / $DRcalls_today) * 100 );
			$DRdrops_today_pct = sprintf("%.2f", $DRdrops_today_pct);
			}

		$stmtA = "UPDATE vicidial_drop_rate_groups SET calls_today='$DRcalls_today',answers_today='$DRanswers_today',drops_today='$DRdrops_today',drops_today_pct='$DRdrops_today_pct',drops_answers_today_pct='$DRdrops_answers_today_pct',answering_machines_today='$DRanswering_machines_today',agenthandled_today='$DRagenthandled_today' where group_id='$DRgroup[$dr]';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "DROP RATE GROUP UPDATE: $DRgroup[$dr]|$affected_rows|$stmtA|\n";}

		$stmtA = "UPDATE vicidial_campaign_stats vcs, vicidial_campaigns vc SET vcs.drops_answers_today_pct='$DRdrops_answers_today_pct'  where vcs.campaign_id=vc.campaign_id and vc.drop_rate_group='$DRgroup[$dr]';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "VCS update: $affected_rows|$stmtA|\n";}

		$dr++;
		}

	################################################################################
	#### END gather drop rate group stats
	################################################################################
	}


sub launch_max_calls_gather
	{
	################################################################################
	#### BEGIN gather stats for max channels and max calls
	################################################################################
	$serversSQL='';
	# Get list of active asterisk servers
	$stmtA = "SELECT server_ip from servers where active_asterisk_server='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$serversSQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();
	chop($serversSQL);

	if (length($serversSQL) > 5)
		{
		$stmtA = "SELECT count(*) from live_sip_channels where server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$lsc_count=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$lsc_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from live_channels where server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$lc_count=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$lc_count =		$aryA[0];
			}
		$sthA->finish();
		$tc_count = ($lsc_count + $lc_count);

		$stmtA = "SELECT count(*) from live_channels where channel NOT LIKE \"%pseudo%\" and server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$tcalls_count=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$tcalls_count =		$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_auto_calls where call_type='IN' and server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$incalls_count=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$incalls_count =		$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_auto_calls where call_type IN('OUT','OUTBALANCE') and server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$outcalls_count=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$outcalls_count =		$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_live_agents where last_update_time > '$VDL_one' and server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$live_agents=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$live_agents =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_live_agents where last_update_time > '$VDL_one' and extension LIKE \"R/%\" and server_ip IN($serversSQL);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$live_remote_agents=0;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$live_remote_agents =	$aryA[0];
			}
		$sthA->finish();

		if ($DB) {print "$now_date MAX CHANNELS TOTALS:  $tc_count($lsc_count+$lc_count)|$tcalls_count|$incalls_count|$outcalls_count|$live_agents|$live_remote_agents\n";}


		$STATSmax_channels=0;
		$STATSmax_calls=0;
		$stmtA = "SELECT max_channels,max_calls,max_inbound,max_outbound,max_agents,max_remote_agents from vicidial_daily_max_stats where campaign_id='' and stats_type='TOTAL' and stats_flag='OPEN' order by update_time desc limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$STATSmax_channels =		$aryA[0];
			$STATSmax_calls =			$aryA[1];
			$STATSmax_inbound =			$aryA[2];
			$STATSmax_outbound =		$aryA[3];
			$STATSmax_agents =			$aryA[4];
			$STATSmax_remote_agents =	$aryA[5];
			$sthA->finish();

			$update_SQL='';
			if ($STATSmax_channels < $tc_count) 
				{$update_SQL .= ",max_channels='$tc_count'";}
			if ($STATSmax_calls < $tcalls_count) 
				{$update_SQL .= ",max_calls='$tcalls_count'";}
			if ($STATSmax_inbound < $incalls_count) 
				{$update_SQL .= ",max_inbound='$incalls_count'";}
			if ($STATSmax_outbound < $outcalls_count) 
				{$update_SQL .= ",max_outbound='$outcalls_count'";}
			if ($STATSmax_agents < $live_agents) 
				{$update_SQL .= ",max_agents='$live_agents'";}
			if ($STATSmax_remote_agents < $live_remote_agents) 
				{$update_SQL .= ",max_remote_agents='$live_remote_agents'";}

			if (length($update_SQL) > 5) 
				{
				$stmtA = "UPDATE vicidial_daily_max_stats SET update_time=NOW()$update_SQL where campaign_id='' and stats_type='TOTAL' and stats_flag='OPEN';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DAILY STATS UPDATE $campaign_id[$i]|$affected_rows|$stmtA|\n";}
				}
			}
		else
			{
			$sthA->finish();

			$stmtA = "INSERT INTO vicidial_daily_max_stats SET stats_date='$file_date',update_time=NOW(),max_channels='$tc_count',max_calls='$tcalls_count',max_inbound='$incalls_count',max_outbound='$outcalls_count',max_agents='$live_agents',max_remote_agents='$live_remote_agents',campaign_id='',stats_type='TOTAL',stats_flag='OPEN';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "DAILY STATS INSERT    TOTAL|$affected_rows|$stmtA|\n";}
			}
		}
	################################################################################
	#### END gather stats for max channels and max calls
	################################################################################
	}



sub launch_carrier_stats_gather
	{
	################################################################################
	#### BEGIN gather carrier stats for real-time report
	################################################################################
	$TFhour_total=0; @TFhour_status=@MT; @TFhour_count=@MT;
	$SIXhour_total=0; @SIXhour_status=@MT; @SIXhour_count=@MT;
	$ONEhour_total=0; @ONEhour_status=@MT; @ONEhour_count=@MT;
	$FTminute_total=0; @FTminute_status=@MT; @FTminute_count=@MT;
	$FIVEminute_total=0; @FIVEminute_status=@MT; @FIVEminute_count=@MT;
	$ONEminute_total=0; @ONEminute_status=@MT; @ONEminute_count=@MT;

	### BEGIN calculate times needed for queries ###
	$secC = time();
	$epochONEminuteAGO = ($secC - 60);
	$epochFIVEminutesAGO = ($secC - 300);
	$epochFIFTEENminutesAGO = ($secC - 900);
	$epochONEhourAGO = ($secC - 3600);
	$epochSIXhoursAGO = ($secC - 21600);
	$epochTWENTYFOURhoursAGO = ($secC - 86400);

	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($secC);
	$mon++;	$year = ($year + 1900);
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}
	$timeNOW = "$year-$mon-$mday $hour:$min:$sec";

	($Osec,$Omin,$Ohour,$Omday,$Omon,$Oyear,$Owday,$Oyday,$Oisdst) = localtime($epochONEminuteAGO);
	$Omon++;	$Oyear = ($Oyear + 1900);
	if ($Omon < 10) {$Omon = "0$Omon";}
	if ($Omday < 10) {$Omday = "0$Omday";}
	if ($Ohour < 10) {$Ohour = "0$Ohour";}
	if ($Omin < 10) {$Omin = "0$Omin";}
	if ($Osec < 10) {$Osec = "0$Osec";}
	$timeONEminuteAGO = "$Oyear-$Omon-$Omday $Ohour:$Omin:$Osec";

	($Fsec,$Fmin,$Fhour,$Fmday,$Fmon,$Fyear,$Fwday,$Fyday,$Fisdst) = localtime($epochFIVEminutesAGO);
	$Fmon++;	$Fyear = ($Fyear + 1900);
	if ($Fmon < 10) {$Fmon = "0$Fmon";}
	if ($Fmday < 10) {$Fmday = "0$Fmday";}
	if ($Fhour < 10) {$Fhour = "0$Fhour";}
	if ($Fmin < 10) {$Fmin = "0$Fmin";}
	if ($Fsec < 10) {$Fsec = "0$Fsec";}
	$timeFIVEminutesAGO = "$Fyear-$Fmon-$Fmday $Fhour:$Fmin:$Fsec";

	($Isec,$Imin,$Ihour,$Imday,$Imon,$Iyear,$Iwday,$Iyday,$Iisdst) = localtime($epochFIFTEENminutesAGO);
	$Imon++;	$Iyear = ($Iyear + 1900);
	if ($Imon < 10) {$Imon = "0$Imon";}
	if ($Imday < 10) {$Imday = "0$Imday";}
	if ($Ihour < 10) {$Ihour = "0$Ihour";}
	if ($Imin < 10) {$Imin = "0$Imin";}
	if ($Isec < 10) {$Isec = "0$Isec";}
	$timeFIFTEENminutesAGO = "$Iyear-$Imon-$Imday $Ihour:$Imin:$Isec";

	($Hsec,$Hmin,$Hhour,$Hmday,$Hmon,$Hyear,$Hwday,$Hyday,$Hisdst) = localtime($epochONEhourAGO);
	$Hmon++;	$Hyear = ($Hyear + 1900);
	if ($Hmon < 10) {$Hmon = "0$Hmon";}
	if ($Hmday < 10) {$Hmday = "0$Hmday";}
	if ($Hhour < 10) {$Hhour = "0$Hhour";}
	if ($Hmin < 10) {$Hmin = "0$Hmin";}
	if ($Hsec < 10) {$Hsec = "0$Hsec";}
	$timeONEhourAGO = "$Hyear-$Hmon-$Hmday $Hhour:$Hmin:$Hsec";

	($Ssec,$Smin,$Shour,$Smday,$Smon,$Syear,$Swday,$Syday,$Sisdst) = localtime($epochSIXhoursAGO);
	$Smon++;	$Syear = ($Syear + 1900);
	if ($Smon < 10) {$Smon = "0$Smon";}
	if ($Smday < 10) {$Smday = "0$Smday";}
	if ($Shour < 10) {$Shour = "0$Shour";}
	if ($Smin < 10) {$Smin = "0$Smin";}
	if ($Ssec < 10) {$Ssec = "0$Ssec";}
	$timeSIXhoursAGO = "$Syear-$Smon-$Smday $Shour:$Smin:$Ssec";

	($Wsec,$Wmin,$Whour,$Wmday,$Wmon,$Wyear,$Wwday,$Wyday,$Wisdst) = localtime($epochTWENTYFOURhoursAGO);
	$Wmon++;	$Wyear = ($Wyear + 1900);
	if ($Wmon < 10) {$Wmon = "0$Wmon";}
	if ($Wmday < 10) {$Wmday = "0$Wmday";}
	if ($Whour < 10) {$Whour = "0$Whour";}
	if ($Wmin < 10) {$Wmin = "0$Wmin";}
	if ($Wsec < 10) {$Wsec = "0$Wsec";}
	$timeTWENTYFOURhoursAGO = "$Wyear-$Wmon-$Wmday $Whour:$Wmin:$Wsec";

	if ($DB > 0) 
		{
		print "Carrier stats gather starting now:\n";
		print "Time now:                    $timeNOW\n";
		}
	if ($DBX > 0) 
		{
		print "Time one minute ago:         $timeONEminuteAGO\n";
		print "Time five minutes ago:       $timeFIVEminutesAGO\n";
		print "Time fifteen minutes ago:    $timeFIFTEENminutesAGO\n";
		print "Time one hour ago:           $timeONEhourAGO\n";
		print "Time six hours ago:          $timeSIXhoursAGO\n";
		print "Time twenty four hours ago:  $timeTWENTYFOURhoursAGO\n";
		}
	### END calculate times needed for queries ###

	$CARRIERstatsHTML='';
	## BEGIN CACHED HOURLY ANALYSIS: CARRIER LOG - 24 hours
	if ($VLhour_counts > 0) 
		{
		$secH = time();
		($HRsec,$HRmin,$HRhour,$HRmday,$HRmon,$HRyear,$HRwday,$HRyday,$HRisdst) = localtime(time);
		$HRyear = ($HRyear + 1900);
		$HRmon++;
		$HRhour_test = $HRhour;
		if ($HRmon < 10) {$HRmon = "0$HRmon";}
		if ($HRmday < 10) {$HRmday = "0$HRmday";}
		if ($HRhour < 10) {$HRhour = "0$HRhour";}
		$CL_today = "$HRyear-$HRmon-$HRmday";
		$CL_current_hour_date = "$HRyear-$HRmon-$HRmday $HRhour:00:00";
		$CL_current_hour_calls=0;

		### get date-time of start of next hour ###
		$CL_next_hour = ($secH + (60 * 60));
		($NHsec,$NHmin,$NHhour,$NHmday,$NHmon,$NHyear,$NHwday,$NHyday,$NHisdst) = localtime($CL_next_hour);
		$NHyear = ($NHyear + 1900);
		$NHmon++;
		if ($NHmon < 10) {$NHmon = "0$NHmon";}
		if ($NHmday < 10) {$NHmday = "0$NHmday";}
		$CL_next_hour_date = "$NHyear-$NHmon-$NHmday $NHhour:00:00";

		if ($DBX > 1) {print "STARTING CARRIER CACHED HOURLY TOTAL CALLS:  |$CL_current_hour_date|$CL_next_hour_date|\n";}

		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= '$CL_current_hour_date' group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$ctp=0;
		while ($sthArows > $ctp)
			{
			@aryA = $sthA->fetchrow_array;
			$cTFhour_status[$ctp] =	$aryA[0];
			$cTFhour_count[$ctp] =	$aryA[1];
			$cTFhour_total = ($cTFhour_total + $aryA[1]);
			$ctp++;
			}
		$sthA->finish();
		if ($DBX) {print "VCLHC CURRENT HOUR CALLS: |$sthArows|$cTFhour_total|$ctp|$stmtA|\n";}

		$ctp=0;
		while ($sthArows > $ctp)
			{
			$stmtA="INSERT IGNORE INTO vicidial_carrier_hour_counts SET date_hour='$CL_current_hour_date',type='$cTFhour_status[$ctp]',next_hour='$CL_next_hour_date',last_update=NOW(),calls='$cTFhour_count[$ctp]',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$cTFhour_count[$ctp]';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "VCLHC CURRENT HOUR INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}
			$ctp++;
			}
		if ($ctp < 1) 
			{
			$stmtA="INSERT IGNORE INTO vicidial_carrier_hour_counts SET date_hour='$CL_current_hour_date',type='ANSWER',next_hour='$CL_next_hour_date',last_update=NOW(),calls='0',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='0';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "VCLHC CURRENT HOUR EMPTY INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}
			}

		### get date-time of start of 24 hours ago ###
		$temp_sub_sec = (24 * 3600);
		$temp_24_sec = ($secH - $temp_sub_sec);
		($cSHsec,$cSHmin,$cSHhour,$cSHmday,$cSHmon,$cSHyear,$cSHwday,$cSHyday,$cSHisdst) = localtime($temp_24_sec);
		$cSHyear = ($cSHyear + 1900);
		$cSHmon++;
		$cSHhour_test = $cSHhour;
		if ($cSHmon < 10) {$cSHmon = "0$cSHmon";}
		if ($cSHmday < 10) {$cSHmday = "0$cSHmday";}
		$sCL_first_hour_date = "$cSHyear-$cSHmon-$cSHmday $cSHhour:00:00";

		# check to see if cached hour totals already exist
		$VCHC_entry_count=0;
		$stmtA = "SELECT count(distinct hr) from vicidial_carrier_hour_counts where date_hour >= '$sCL_first_hour_date' and date_hour < '$CL_next_hour_date' and last_update > next_hour;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCLHC_entry_count =	$aryA[0];
			}
		$sthA->finish();
		if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCLHC_entry_count|$stmtA|\n";}

		# if cached not equal the number of hours, then run go hour-by-hour and generate it
		if ($VCLHC_entry_count < 24) 
			{
			$start_24_hour=24;
			while ($start_24_hour > 0)
				{
				$temp_sub_sec = ($start_24_hour * 3600);
				$temp_24_sec = ($secH - $temp_sub_sec);
				$temp_24_sec_next = ($temp_24_sec + 3600);
				### get date-time of start of hour ###
				($cSHsec,$cSHmin,$cSHhour,$cSHmday,$cSHmon,$cSHyear,$cSHwday,$cSHyday,$cSHisdst) = localtime($temp_24_sec);
				$cSHyear = ($cSHyear + 1900);
				$cSHmon++;
				$cSHhour_test = $cSHhour;
				if ($cSHmon < 10) {$cSHmon = "0$cSHmon";}
				if ($cSHmday < 10) {$cSHmday = "0$cSHmday";}
				$sCL_start_hour_date = "$cSHyear-$cSHmon-$cSHmday $cSHhour:00:00";
				### get date-time of start of next hour ###
				($cNHsec,$cNHmin,$cNHhour,$cNHmday,$cNHmon,$cNHyear,$cNHwday,$cNHyday,$cNHisdst) = localtime($temp_24_sec_next);
				$cNHyear = ($cNHyear + 1900);
				$cNHmon++;
				if ($cNHmon < 10) {$cNHmon = "0$cNHmon";}
				if ($cNHmday < 10) {$cNHmday = "0$cNHmday";}
				$sCL_next_hour_date = "$cNHyear-$cNHmon-$cNHmday $cNHhour:00:00";

				@cTFhour_status=@MT;
				@cTFhour_count=@MT;
				@cTFhour_total=0;
				if ($DBX > 1) {print "STARTING CARRIER CACHED HOURLY TOTAL CALLS FOR LAST 24 HOURS:  |$CL_current_hour_date|$CL_next_hour_date|\n";}

				$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= '$sCL_start_hour_date' and call_date < '$sCL_next_hour_date' group by dialstatus;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$ctp=0;
				while ($sthArows > $ctp)
					{
					@aryA = $sthA->fetchrow_array;
					$cTFhour_status[$ctp] =	$aryA[0];
					$cTFhour_count[$ctp] =	$aryA[1];
					$cTFhour_total = ($cTFhour_total + $aryA[1]);
					$ctp++;
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CURRENT HOUR CALLS: |$sthArows|$cTFhour_total|$ctp|$start_24_hour|$stmtA|\n";}

				$ctp=0;
				while ($sthArows > $ctp)
					{
					$stmtA="INSERT IGNORE INTO vicidial_carrier_hour_counts SET date_hour='$sCL_start_hour_date',type='$cTFhour_status[$ctp]',next_hour='$sCL_next_hour_date',last_update=NOW(),calls='$cTFhour_count[$ctp]',hr='$cSHhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$cTFhour_count[$ctp]';";
					$affected_rows = $dbhA->do($stmtA);
					if ($DBX) {print "VCLHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}
					$ctp++;
					}
				if ($ctp < 1) 
					{
					$stmtA="INSERT IGNORE INTO vicidial_carrier_hour_counts SET date_hour='$sCL_start_hour_date',type='ANSWER',next_hour='$sCL_next_hour_date',last_update=NOW(),calls='0',hr='$cSHhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='0';";
					$affected_rows = $dbhA->do($stmtA);
					if ($DBX) {print "VCLHC STATS EMPTY INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}
					}
				$start_24_hour = ($start_24_hour - 1);
				}
			}
		### get date-time of start of next hour ###
		$secH = time();
		$CL_next_hour = ($secH + (60 * 60));
		($NHsec,$NHmin,$NHhour,$NHmday,$NHmon,$NHyear,$NHwday,$NHyday,$NHisdst) = localtime($CL_next_hour);
		$NHyear = ($NHyear + 1900);
		$NHmon++;
		if ($NHmon < 10) {$NHmon = "0$NHmon";}
		if ($NHmday < 10) {$NHmday = "0$NHmday";}
		$CL_next_hour_date = "$NHyear-$NHmon-$NHmday $NHhour:00:00";

		$stmtA="SELECT type,sum(calls) from vicidial_carrier_hour_counts where date_hour >= '$sCL_first_hour_date' and date_hour < '$CL_next_hour_date' group by type;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$ctp=0;
		while ($sthArows > $ctp)
			{
			@aryA = $sthA->fetchrow_array;
			$TFhour_status[$ctp] =	$aryA[0];
			$TFhour_count[$ctp] =	$aryA[1];
			$TFhour_total = ($TFhour_total + $aryA[1]);
			$dialstatuses .= "'$aryA[0]',";
			$ctp++;
			}
		$sthA->finish();
		if ($DBX) {print "VCLHC CACHED 24-HOUR SINGLE QUERY: |$sthArows|$ctp($dialstatuses)|$stmtA|\n";}
		}
	## END CACHED HOURLY ANALYSIS: CARRIER LOG - 24 hours
	else
		{
		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeTWENTYFOURhoursAGO\" group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$ctp=0;
		while ($sthArows > $ctp)
			{
			@aryA = $sthA->fetchrow_array;
			$TFhour_status[$ctp] =	$aryA[0];
			$TFhour_count[$ctp] =	$aryA[1];
			$TFhour_total = ($TFhour_total + $aryA[1]);
			$dialstatuses .= "'$aryA[0]',";
			$ctp++;
			}
		$sthA->finish();
		}
	$dialstatuses =~ s/,$//gi;

	$CARRIERstatsHTML .= "<TR BGCOLOR=white><TD ALIGN=left COLSPAN=8>";
	$CARRIERstatsHTML .= "<TABLE CELLPADDING=1 CELLSPACING=1 BORDER=0 BGCOLOR=white>";
	$CARRIERstatsHTML .= "<TR BGCOLOR='#E6E6E6'>";
	$CARRIERstatsHTML .= "<TD ALIGN=LEFT><font size=1 face='helvetica'><B>CARRIER STATS: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=LEFT><font size=1 face='helvetica'><B>&nbsp; HANGUP STATUS &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 24 HOURS &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 6 HOURS &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 1 HOUR &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 15 MIN &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 5 MIN &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font size=1 face='helvetica'><B>&nbsp; 1 MIN &nbsp; </B></TD>";
	$CARRIERstatsHTML .= "</TR>";

	if (length($dialstatuses) > 1)
		{
		## BEGIN CACHED HOURLY ANALYSIS: CARRIER LOG - 6 hours
		if ($VLhour_counts > 0) 
			{
			if ($DBX > 1) {print "STARTING CARRIER CACHED HOURLY TOTAL CALLS:  |$CL_current_hour_date|$CL_next_hour_date|\n";}

			### get date-time of start of 6 hours ago ###
			$secH = time();
			$temp_sub_sec = (6 * 3600);
			$temp_24_sec = ($secH - $temp_sub_sec);
			($cSHsec,$cSHmin,$cSHhour,$cSHmday,$cSHmon,$cSHyear,$cSHwday,$cSHyday,$cSHisdst) = localtime($temp_24_sec);
			$cSHyear = ($cSHyear + 1900);
			$cSHmon++;
			$cSHhour_test = $cSHhour;
			if ($cSHmon < 10) {$cSHmon = "0$cSHmon";}
			if ($cSHmday < 10) {$cSHmday = "0$cSHmday";}
			$sxCL_first_hour_date = "$cSHyear-$cSHmon-$cSHmday $cSHhour:00:00";

			$stmtA="SELECT type,sum(calls) from vicidial_carrier_hour_counts where date_hour >= '$sxCL_first_hour_date' and date_hour < '$CL_next_hour_date' group by type;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$print_sctp=0;
			while ($sthArows > $print_sctp)
				{
				@aryA = $sthA->fetchrow_array;
				$SIXhour_total = ($SIXhour_total + $aryA[1]);
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					$temp_status = $aryA[0];
					if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
						{
						$SIXhour_count[$print_ctp] = $aryA[1];
						}
					$print_ctp++;
					}
				$print_sctp++;
				}
			$sthA->finish();
			if ($DBX) {print "VCLHC CACHED 6-HOUR SINGLE QUERY: |$sthArows|$ctp($dialstatuses)|$stmtA|\n";}
			}
		## END CACHED HOURLY ANALYSIS: CARRIER LOG - 6 hours
		else
			{
			$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeSIXhoursAGO\" group by dialstatus;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$print_sctp=0;
			while ($sthArows > $print_sctp)
				{
				@aryA = $sthA->fetchrow_array;
				$SIXhour_total = ($SIXhour_total + $aryA[1]);
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					$temp_status = $aryA[0];
					if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
						{
						$SIXhour_count[$print_ctp] = $aryA[1];
						}
					$print_ctp++;
					}
				$print_sctp++;
				}
			$sthA->finish();
			}

		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeONEhourAGO\" group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$print_sctp=0;
		while ($sthArows > $print_sctp)
			{
			@aryA = $sthA->fetchrow_array;
			$ONEhour_total = ($ONEhour_total + $aryA[1]);
			$print_ctp=0;
			while ($print_ctp < $ctp)
				{
				$temp_status = $aryA[0];
				if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
					{$ONEhour_count[$print_ctp] = $aryA[1];}
				$print_ctp++;
				}
			$print_sctp++;
			}
		$sthA->finish();

		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeFIFTEENminutesAGO\" group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$print_sctp=0;
		while ($sthArows > $print_sctp)
			{
			@aryA = $sthA->fetchrow_array;
			$FTminute_total = ($FTminute_total + $aryA[1]);
			$print_ctp=0;
			while ($print_ctp < $ctp)
				{
				$temp_status = $aryA[0];
				if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
					{$FTminute_count[$print_ctp] = $aryA[1];}
				$print_ctp++;
				}
			$print_sctp++;
			}
		$sthA->finish();

		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeFIVEminutesAGO\" group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$print_sctp=0;
		while ($sthArows > $print_sctp)
			{
			@aryA = $sthA->fetchrow_array;
			$FIVEminute_total = ($FIVEminute_total + $aryA[1]);
			$print_ctp=0;
			while ($print_ctp < $ctp)
				{
				$temp_status = $aryA[0];
				if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
					{$FIVEminute_count[$print_ctp] = $aryA[1];}
				$print_ctp++;
				}
			$print_sctp++;
			}
		$sthA->finish();

		$stmtA="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeONEminuteAGO\" group by dialstatus;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$print_sctp=0;
		while ($sthArows > $print_sctp)
			{
			@aryA = $sthA->fetchrow_array;
			$ONEminute_total = ($ONEminute_total + $aryA[1]);
			$print_ctp=0;
			while ($print_ctp < $ctp)
				{
				$temp_status = $aryA[0];
				if ($TFhour_status[$print_ctp] =~ /^$temp_status$/)
					{$ONEminute_count[$print_ctp] = $aryA[1];}
				$print_ctp++;
				}
			$print_sctp++;
			}
		$sthA->finish();

		$print_ctp=0;
		while ($print_ctp < $ctp)
			{
			if (length($TFhour_count[$print_ctp])<1) {$TFhour_count[$print_ctp]=0;}
			if (length($SIXhour_count[$print_ctp])<1) {$SIXhour_count[$print_ctp]=0;}
			if (length($ONEhour_count[$print_ctp])<1) {$ONEhour_count[$print_ctp]=0;}
			if (length($FTminute_count[$print_ctp])<1) {$FTminute_count[$print_ctp]=0;}
			if (length($FIVEminute_count[$print_ctp])<1) {$FIVEminute_count[$print_ctp]=0;}
			if (length($ONEminute_count[$print_ctp])<1) {$ONEminute_count[$print_ctp]=0;}

			$dividend=$TFhour_count[$print_ctp]; $divisor=$TFhour_total;	$TFhour_prw = &MathZDC;
			$dividend=$SIXhour_count[$print_ctp]; $divisor=$SIXhour_total;	$SIXhour_prw = &MathZDC;
			$dividend=$ONEhour_count[$print_ctp]; $divisor=$ONEhour_total;	$ONEhour_prw = &MathZDC;
			$dividend=$FTminute_count[$print_ctp]; $divisor=$FTminute_total;	$TFminute_prw = &MathZDC;
			$dividend=$FIVEminute_count[$print_ctp]; $divisor=$FIVEminute_total;	$FIVEminute_prw = &MathZDC;
			$dividend=$ONEminute_count[$print_ctp]; $divisor=$ONEminute_total;	$ONEminute_prw = &MathZDC;

			$TFhour_pct = (100 * $TFhour_prw);
			$SIXhour_pct = (100 * $SIXhour_prw);
			$ONEhour_pct = (100 * $ONEhour_prw);
			$TFminute_pct = (100 * $TFminute_prw);
			$FIVEminute_pct = (100 * $FIVEminute_prw);
			$ONEminute_pct = (100 * $ONEminute_prw);

		#	if ($DBX) 
		#		{
		#		print "$TFhour_prw  | ($TFhour_count[$print_ctp], $TFhour_total)\n";
		#		print "$SIXhour_prw  | ($SIXhour_count[$print_ctp], $SIXhour_total)\n";
		#		print "$ONEhour_prw  | ($ONEhour_count[$print_ctp], $ONEhour_total)\n";
		#		print "$TFminute_prw  | ($FTminute_count[$print_ctp], $FTminute_total)\n";
		#		print "$FIVEminute_prw  | ($FIVEminute_count[$print_ctp], $FIVEminute_total)\n";
		#		print "$ONEminute_prw  | ($ONEminute_count[$print_ctp], $ONEminute_total)\n";
		#		}

			$CARRIERstatsHTML .= "<TR>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=white><font size=1 face='helvetica'>&nbsp;</TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=LEFT><font size=1 face='helvetica'>&nbsp; &nbsp; $TFhour_status[$print_ctp] </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $TFhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $TFhour_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $SIXhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $SIXhour_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $ONEhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $ONEhour_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $FTminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $TFminute_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $FIVEminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $FIVEminute_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'> $ONEminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000' face='helvetica'>".sprintf("%01.1f", $ONEminute_pct)."%</font></TD>";
			$CARRIERstatsHTML .= "</TR>";
			$print_ctp++;
			}
		$CARRIERstatsHTML .= "<TR>";
		$CARRIERstatsHTML .= "<TD BGCOLOR=white><font size=1 face='helvetica'>generated: $timeNOW</TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=LEFT><font size=1 face='helvetica'><B>&nbsp; &nbsp; TOTALS</B></TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($TFhour_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($SIXhour_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($ONEhour_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($FTminute_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($FIVEminute_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "<TD BGCOLOR='#E6E6E6' ALIGN=CENTER><font size=1 face='helvetica'><B> ".($ONEminute_total+0)."</B> </TD>";
		$CARRIERstatsHTML .= "</TR>";
		}
	else
		{
		$CARRIERstatsHTML .= "<TR><TD BGCOLOR=white colspan=7><font size=1 face='helvetica'>no carrier log entries in last 24 hours</TD></TR>";
		}
	$CARRIERstatsHTML .= "</TABLE>";
	$CARRIERstatsHTML .= "</TD></TR>";

	$stmtA="INSERT IGNORE INTO vicidial_html_cache_stats SET stats_type='carrier_stats',stats_id='ALL',stats_date=NOW(),stats_count='$TFhour_total',stats_html=\"$CARRIERstatsHTML\" ON DUPLICATE KEY UPDATE stats_date=NOW(),stats_count='$TFhour_total',stats_html=\"$CARRIERstatsHTML\";";
	$affected_rows = $dbhA->do($stmtA);
	if ($DB) {print "CARRIER STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}
	################################################################################
	#### END gather carrier stats for real-time report
	################################################################################
	}

sub launch_inbound_gather
	{
	################################################################################
	#### BEGIN gather stats for inbound groups for the real-time display
	################################################################################
	$stmtA = "SELECT group_id from vicidial_inbound_groups where active='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$ibg_count=0;
	while ($sthArows > $ibg_count)
		{
		@aryA = $sthA->fetchrow_array;
		$group_id[$ibg_count] =		$aryA[0];
		$ibg_count++;
		}
	$sthA->finish();
	if ($DB) {print "$now_date INBOUND GROUPS TO GET STATS FOR:  $ibg_count|$#group_id       IT: $master_loop\n";}


	##### LOOP THROUGH EACH INBOUND GROUP AND GATHER STATS #####
	$p=0;
	foreach(@group_id)
		{
		&calculate_drops_inbound;

		$p++;
		}

	################################################################################
	#### END gather stats for inbound groups for the real-time display
	################################################################################
	}



sub calculate_drops_inbound
	{
	$debug_ingroup_output='';
	$answer_sec_pct_rt_stat_one = '20';
	$answer_sec_pct_rt_stat_two = '30';
	# GET inbound group hold stat seconds settings
	$stmtA = "SELECT answer_sec_pct_rt_stat_one,answer_sec_pct_rt_stat_two from vicidial_inbound_groups where group_id='$group_id[$p]';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$answer_sec_pct_rt_stat_one = $aryA[0];
		$answer_sec_pct_rt_stat_two = $aryA[1];
		}
	$sthA->finish();

	$camp_ANS_STAT_SQL='';
	# GET LIST OF HUMAN-ANSWERED STATUSES
	$stmtA = "SELECT status from vicidial_statuses where human_answered='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_ANS_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();

	$stmtA = "SELECT distinct(status) from vicidial_campaign_statuses where human_answered='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$camp_ANS_STAT_SQL .=	 "'$aryA[0]',";
		$rec_count++;
		}
	$sthA->finish();
	chop($camp_ANS_STAT_SQL);
	if (length($camp_ANS_STAT_SQL)<2) 
		{$camp_ANS_STAT_SQL="'-NONE-'";}

	if ($DBX) {print "     ANSWERED STATUSES: $group_id[$p]|$camp_ANS_STAT_SQL|\n";}
	$debug_ingroup_output .= "     ANSWERED STATUSES: $group_id[$p]|$camp_ANS_STAT_SQL|\n";

	#$RESETdrop_count_updater++;
	$iVCScalls_today[$p]=0;
	$iVCSanswers_today[$p]=0;
	$iVCSdrops_today[$p]=0;
	$iVCSdrops_today_pct[$p]=0;
	$iVCSdrops_answers_today_pct[$p]=0;
	$answer_sec_pct_rt_stat_one_PCT[$p]=0;
	$answer_sec_pct_rt_stat_two_PCT[$p]=0;
	$hold_sec_answer_calls[$p]=0;
	$hold_sec_drop_calls[$p]=0;
	$hold_sec_queue_calls[$p]=0;
	$iVCSlive_calls[$p]=0;
	$itotal_agents[$p]=0;
	$itotal_remote_agents[$p]=0;
	$iVCSpark_calls_today[$p]=0;
	$iVCSpark_sec_today[$p]=0;

	# DAILY STATS UPDATE
	if ($daily_stats > 0)
		{
		$stmtA = "SELECT count(*) from vicidial_auto_calls where campaign_id='$group_id[$p]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$iVCSlive_calls[$p] =	$aryA[0];
			}
		$sthA->finish();

		$SQL_group_id=$group_id[$p];   $SQL_group_id =~ s/_/\\_/gi;
		$stmtA = "SELECT count(*) from vicidial_live_agents where closer_campaigns LIKE \"% $SQL_group_id %\" and last_update_time > '$VDL_one';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$itotal_agents[$p] =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_live_agents where closer_campaigns LIKE \"% $SQL_group_id %\" and last_update_time > '$VDL_one' and extension LIKE \"R/%\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$itotal_remote_agents[$p] =	$aryA[0];
			}
		$sthA->finish();

		$debug_ingroup_output .= "     DAILY STATS|$iVCSlive_calls[$p]|$itotal_agents[$p]|$itotal_remote_agents[$p]|";
		}

	# TODAY CALL AND DROP STATS
	## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS
	if ($VCLhour_counts > 0) 
		{
		$secH = time();
		($HRsec,$HRmin,$HRhour,$HRmday,$HRmon,$HRyear,$HRwday,$HRyday,$HRisdst) = localtime(time);
		($HRsec_prev,$HRmin_prev,$HRhour_prev,$HRmday_prev,$HRmon_prev,$HRyear_prev,$HRwday_prev,$HRyday_prev,$HRisdst_prev) = localtime(time-3600);
		$HRyear = ($HRyear + 1900);
		$HRmon++;
		$HRhour_test = $HRhour;
		if ($HRmon < 10) {$HRmon = "0$HRmon";}
		if ($HRmday < 10) {$HRmday = "0$HRmday";}
		if ($HRhour < 10) {$HRhour = "0$HRhour";}

		$HRyear_prev = ($HRyear_prev + 1900);
		$HRmon_prev++;
		if ($HRmon_prev < 10) {$HRmon_prev = "0$HRmon_prev";}
		if ($HRmday_prev < 10) {$HRmday_prev = "0$HRmday_prev";}
		if ($HRhour_prev < 10) {$HRhour_prev = "0$HRhour_prev";}

		$VCL_today = "$HRyear-$HRmon-$HRmday";
		$VCL_current_hour_date = "$HRyear-$HRmon-$HRmday $HRhour:00:00";
		$VCL_previous_hour_date = "$HRyear_prev-$HRmon_prev-$HRmday_prev $HRhour_prev:00:00";
		$VCL_day_start_date = "$HRyear-$HRmon-$HRmday 00:00:00";
		$VCL_prev_day_start_date = "$HRyear_prev-$HRmon_prev-$HRmday_prev 00:00:00";
		$VCL_current_hour_calls=0;

		### get date-time of start of next hour ###
		$VCL_next_hour = ($secH + (60 * 60));
		($NHsec,$NHmin,$NHhour,$NHmday,$NHmon,$NHyear,$NHwday,$NHyday,$NHisdst) = localtime($VCL_next_hour);
		$NHyear = ($NHyear + 1900);
		$NHmon++;
		if ($NHmon < 10) {$NHmon = "0$NHmon";}
		if ($NHmday < 10) {$NHmday = "0$NHmday";}
		$VCL_next_hour_date = "$NHyear-$NHmon-$NHmday $NHhour:00:00";

		if ($DBX > 1) {print "STARTING CACHED HOURLY TOTAL INBOUND CALLS:  |$VCL_current_hour_date|$VCL_next_hour_date|\n";}

		$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCL_current_hour_calls =	$aryA[0];
			}
		$sthA->finish();
		if ($DBX) {print "VCLHC CURRENT HOUR CALLS: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

		$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date',type='CALLS',next_hour='$VCL_next_hour_date',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "VCLHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}

		if ($HRhour_test > 0)
			{
			# check to see if cached hour totals already exist
			$VCHC_entry_count=0;
			$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='CALLS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VCHC_entry_count =	$aryA[0];
				}
			$sthA->finish();
			if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

			# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
			if ($VCHC_entry_count >= $HRhour_test) 
				{
				$VCHC_cache_calls=0;
				$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='CALLS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_cache_calls =	$aryA[0];
					}
				$sthA->finish();
				$iVCScalls_today[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
				if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($iVCScalls_today[$p])|$stmtA|\n";}
				}
			else
				{
				@VCHC_hour=@MT;
				@VCHC_date_hour=@MT;
				@VCHC_next_hour=@MT;
				@VCHC_last_update=@MT;
				@VCHC_calls=@MT;
				$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='CALLS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour order by hr;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows_hr=$sthA->rows;
				$j=0;
				while ($sthArows_hr > $j)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_hour[$j] =		$aryA[0];
					$VCHC_date_hour[$j] =	$aryA[1];
					$VCHC_next_hour[$j] =	$aryA[2];
					$VCHC_last_update[$j] =	$aryA[3];
					$VCHC_calls[$j] =		$aryA[4];
					$j++;
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

				$j=0;
				while ($j < $HRhour_test) 
					{
					$k=0;
					$cache_hour_found=0;
					while ($sthArows_hr > $k)
						{
						if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
							{
							$iVCScalls_today[$p] = ($iVCScalls_today[$p] + $VCHC_calls[$k]);
							$cache_hour_found++;
							if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$iVCScalls_today[$p]|\n";}
							}
						$k++;
						}
					if ($cache_hour_found < 1) 
						{
						$j_next = ($j + 1);
						$VCL_this_hour_calls=0;
						$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VCL_this_hour_calls =	$aryA[0];
							$iVCScalls_today[$p] = ($iVCScalls_today[$p] + $VCL_this_hour_calls);
							}
						$sthA->finish();
						if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

						$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='CALLS',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
						}
					$j++;
					}
				$iVCScalls_today[$p] = ($iVCScalls_today[$p] + $VCL_current_hour_calls);
				}
			}
		else
			{
			# midnight hour, so total is only current hour stats
			$iVCScalls_today[$p] =	$VCL_current_hour_calls;
			if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$iVCScalls_today[$p]|\n";}
			}
		}
	## END CACHED HOURLY ANALYSIS: INBOUND CALLS
	else
		{
		$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$iVCScalls_today[$p] =	$aryA[0];
			}
		$sthA->finish();
		}

	if ($iVCScalls_today[$p] > 0)
		{
		# TODAY ANSWERS
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS ANSWERS
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VL_current_hour_date_int, 11, 2);
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCHC CURRENT HOUR CALLS ANSWERS: |$sthArows|$VL_current_hour_date_int|$VL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='ANSWERS',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='ANSWERS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='ANSWERS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$iVCSanswers_today[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($iVCSanswers_today[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='ANSWERS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$iVCSanswers_today[$p] = ($iVCSanswers_today[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$iVCSanswers_today[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$iVCSanswers_today[$p] = ($iVCSanswers_today[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='ANSWERS',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$iVCSanswers_today[$p] = ($iVCSanswers_today[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$iVCSanswers_today[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$iVCSanswers_today[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS ANSWERS
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$iVCSanswers_today[$p] =	$aryA[0];
				}
			$sthA->finish();
			}

		# TODAY DROPS
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS DROPS
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and status IN('DROP','XDROP');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date'  and status IN('DROP','XDROP') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$current_hr=substr($VCL_current_hour_date_int, 11, 2);
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS DROPS: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='DROPS',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$current_hr' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCLHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='DROPS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='DROPS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$iVCSdrops_today[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($iVCSdrops_today[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='DROPS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$iVCSdrops_today[$p] = ($iVCSdrops_today[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$iVCSdrops_today[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and status IN('DROP','XDROP');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$iVCSdrops_today[$p] = ($iVCSdrops_today[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='DROPS',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$iVCSdrops_today[$p] = ($iVCSdrops_today[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$iVCSdrops_today[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$iVCSdrops_today[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS DROPS
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and status IN('DROP','XDROP');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX > 0) {print "$stmtA\n";}
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$iVCSdrops_today[$p] =	$aryA[0];
				}
			$sthA->finish();
			}

		if ($iVCSdrops_today[$p] > 0)
			{
			$iVCSdrops_today_pct[$p] = ( ($iVCSdrops_today[$p] / $iVCScalls_today[$p]) * 100 );
			$iVCSdrops_today_pct[$p] = sprintf("%.2f", $iVCSdrops_today_pct[$p]);
			# if ($iVCSanswers_today[$p] < 1) {$iVCSanswers_today[$p] = 1;}
			if ($iVCSanswers_today[$p] < 1) 
				{
				$iVCSdrops_answers_today_pct[$p] = 0;
				}
			else
				{
				$iVCSdrops_answers_today_pct[$p] = ( ($iVCSdrops_today[$p] / $iVCSanswers_today[$p]) * 100 );
				}
			$iVCSdrops_answers_today_pct[$p] = sprintf("%.2f", $iVCSdrops_answers_today_pct[$p]);
			}

		# TODAY ANSWER PERCENT OF HOLD SECONDS one and two
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SECONDS 1
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and queue_seconds <= $answer_sec_pct_rt_stat_one and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' and queue_seconds <= $answer_sec_pct_rt_stat_one and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS HOLD SECONDS 1: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='HOLDSEC1',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC1' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC1' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$answer_sec_pct_rt_stat_one_PCT[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($answer_sec_pct_rt_stat_one_PCT[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC1' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$answer_sec_pct_rt_stat_one_PCT[$p] = ($answer_sec_pct_rt_stat_one_PCT[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$answer_sec_pct_rt_stat_one_PCT[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and queue_seconds <= $answer_sec_pct_rt_stat_one and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$answer_sec_pct_rt_stat_one_PCT[$p] = ($answer_sec_pct_rt_stat_one_PCT[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='HOLDSEC1',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$answer_sec_pct_rt_stat_one_PCT[$p] = ($answer_sec_pct_rt_stat_one_PCT[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$answer_sec_pct_rt_stat_one_PCT[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$answer_sec_pct_rt_stat_one_PCT[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SECONDS 1
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and queue_seconds <= $answer_sec_pct_rt_stat_one and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$answer_sec_pct_rt_stat_one_PCT[$p] = $aryA[0];
				}
			$sthA->finish();
			}

		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SECONDS 2
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and queue_seconds <= $answer_sec_pct_rt_stat_two and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, count(*), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' and queue_seconds <= $answer_sec_pct_rt_stat_two and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS HOLD SECONDS 2: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='ANSWERS',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC2' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC2' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$answer_sec_pct_rt_stat_two_PCT[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($answer_sec_pct_rt_stat_two_PCT[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HOLDSEC2' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$answer_sec_pct_rt_stat_two_PCT[$p] = ($answer_sec_pct_rt_stat_two_PCT[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$answer_sec_pct_rt_stat_two_PCT[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and queue_seconds <= $answer_sec_pct_rt_stat_two and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$answer_sec_pct_rt_stat_two_PCT[$p] = ($answer_sec_pct_rt_stat_two_PCT[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='HOLDSEC2',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$answer_sec_pct_rt_stat_two_PCT[$p] = ($answer_sec_pct_rt_stat_two_PCT[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$answer_sec_pct_rt_stat_two_PCT[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$answer_sec_pct_rt_stat_two_PCT[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SECONDS 2
		else
			{
			$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and queue_seconds <= $answer_sec_pct_rt_stat_two and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$answer_sec_pct_rt_stat_two_PCT[$p] = $aryA[0];
				}
			$sthA->finish();
			}

		# TODAY TOTAL HOLD TIME FOR ANSWERED CALLS
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SEC ANSWERED
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','INBND','MAXCAL');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, sum(queue_seconds), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS HOLD SECONDS OF ANSWERED CALLS: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='HDSECANS',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECANS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECANS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$hold_sec_answer_calls[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($hold_sec_answer_calls[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECANS' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$hold_sec_answer_calls[$p] = ($hold_sec_answer_calls[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$hold_sec_answer_calls[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$hold_sec_answer_calls[$p] = ($hold_sec_answer_calls[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='HDSECANS',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$hold_sec_answer_calls[$p] = ($hold_sec_answer_calls[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$hold_sec_answer_calls[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$hold_sec_answer_calls[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SEC ANSWERED
		else
			{
			$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if ($aryA[0] > 0)
					{$hold_sec_answer_calls[$p] = $aryA[0];}
				}
			$sthA->finish();
			}

		# TODAY TOTAL HOLD TIME FOR DROP CALLS
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD TIME DROPS
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and status IN('DROP','XDROP');";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, sum(queue_seconds), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' and status IN('DROP','XDROP') group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS HOLD SECONDS OF DROPPED CALLS: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='HDSECDRP',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECDRP' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECDRP' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$hold_sec_drop_calls[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($hold_sec_drop_calls[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECDRP' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$hold_sec_drop_calls[$p] = ($hold_sec_drop_calls[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$hold_sec_drop_calls[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and status IN('DROP','XDROP');";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$hold_sec_drop_calls[$p] = ($hold_sec_drop_calls[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='HDSECDRP',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$hold_sec_drop_calls[$p] = ($hold_sec_drop_calls[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$hold_sec_drop_calls[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$hold_sec_drop_calls[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD TIME DROPS
		else
			{
			$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and status IN('DROP','XDROP');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if ($aryA[0] > 0)
					{$hold_sec_drop_calls[$p] = $aryA[0];}
				}
			$sthA->finish();
			}

		# TODAY TOTAL QUEUE TIME FOR QUEUE CALLS
		## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SEC ALL
		if ($VCLhour_counts > 0) 
			{
			$VCL_current_hour_calls=0;
			# $stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date';";
			$stmtA = "SELECT CONCAT(substr(call_date, 1, 13), ':00:00') as hour_int, sum(queue_seconds), CONCAT(substr(call_date+INTERVAL 1 HOUR, 1, 13), ':00:00') as next_hour from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_previous_hour_date' group by hour_int, next_hour order by hour_int;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$q=0;
				while (@aryA = $sthA->fetchrow_array) 
					{
					$VCL_current_hour_date_int =	$aryA[0];
					$VCL_current_hour_calls =	$aryA[1];
					$VCL_next_hour_date_int =	$aryA[2];
					if ($DBX) {print "VCLHC CURRENT HOUR CALLS HOLD SECONDS OF ALL CALLS: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

					$stmtB="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date_int',type='HDSECALL',next_hour='$VCL_next_hour_date_int',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
					$affected_rows = $dbhB->do($stmtB);
					if ($DBX) {print "VCHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtB|\n";}
					}				
				}
			$sthA->finish();

			if ($HRhour_test > 0)
				{
				# check to see if cached hour totals already exist
				$VCHC_entry_count=0;
				$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECALL' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCHC_entry_count =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

				# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
				if ($VCHC_entry_count >= $HRhour_test) 
					{
					$VCHC_cache_calls=0;
					$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECALL' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_cache_calls =	$aryA[0];
						}
					$sthA->finish();
					$hold_sec_queue_calls[$p] = ($VCHC_cache_calls + $VCL_current_hour_calls);
					if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($hold_sec_queue_calls[$p])|$stmtA|\n";}
					}
				else
					{
					@VCHC_hour=@MT;
					@VCHC_date_hour=@MT;
					@VCHC_next_hour=@MT;
					@VCHC_last_update=@MT;
					@VCHC_calls=@MT;
					$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='HDSECALL' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour+INTERVAL 1 HOUR order by hr;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows_hr=$sthA->rows;
					$j=0;
					while ($sthArows_hr > $j)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_hour[$j] =		$aryA[0];
						$VCHC_date_hour[$j] =	$aryA[1];
						$VCHC_next_hour[$j] =	$aryA[2];
						$VCHC_last_update[$j] =	$aryA[3];
						$VCHC_calls[$j] =		$aryA[4];
						$j++;
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

					$j=0;
					while ($j < $HRhour_test) 
						{
						$k=0;
						$cache_hour_found=0;
						while ($sthArows_hr > $k)
							{
							if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
								{
								$hold_sec_queue_calls[$p] = ($hold_sec_queue_calls[$p] + $VCHC_calls[$k]);
								$cache_hour_found++;
								if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$hold_sec_queue_calls[$p]|\n";}
								}
							$k++;
							}
						if ($cache_hour_found < 1) 
							{
							$j_next = ($j + 1);
							$VCL_this_hour_calls=0;
							$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VCL_this_hour_calls =	$aryA[0];
								$hold_sec_queue_calls[$p] = ($hold_sec_queue_calls[$p] + $VCL_this_hour_calls);
								}
							$sthA->finish();
							if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

							$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='HDSECALL',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
							}
						$j++;
						}
					$hold_sec_queue_calls[$p] = ($hold_sec_queue_calls[$p] + $VCL_current_hour_calls);
					}
				}
			else
				{
				# midnight hour, so total is only current hour stats
				$hold_sec_queue_calls[$p] =	$VCL_current_hour_calls;
				if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$hold_sec_queue_calls[$p]|\n";}
				}
			}
		## END CACHED HOURLY ANALYSIS: INBOUND CALLS HOLD SEC ALL
		else
			{
			$stmtA = "SELECT sum(queue_seconds) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if ($aryA[0] > 0)
					{$hold_sec_queue_calls[$p] = $aryA[0];}
				}
			$sthA->finish();
			}

		$stmtA = "SELECT count(*),sum(parked_sec) from park_log where campaign_id='$group_id[$p]' and parked_time > '$VDL_date';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$iVCSpark_calls_today[$p] =	$aryA[0];
			$iVCSpark_sec_today[$p] =	$aryA[1];
			}
		$sthA->finish();
		}



	# DETERMINE WHETHER TO GATHER STATUS CATEGORY STATISTICS
	$VSC_categories=0;
	$VSCupdateSQL='';
	$stmtA = "SELECT vsc_id from vicidial_status_categories where tovdad_display='Y' limit 4;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$VSC_categories[$rec_count] =	$aryA[0];
		$rec_count++;
		}
	$sthA->finish();

	$g=0;
	foreach (@VSC_categories)
		{
		$VSCcategory=$VSC_categories[$g];
		$VSCtally='';
		$CATstatusesSQL='';
		# FIND STATUSES IN STATUS CATEGORY
		$stmtA = "SELECT status from vicidial_statuses where category='$VSCcategory';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$CATstatusesSQL .=		 "'$aryA[0]',";
			$rec_count++;
			}
		$sthA->finish();

		# FIND CAMPAIGN_STATUSES IN STATUS CATEGORY
		$stmtA = "SELECT status from vicidial_campaign_statuses where category='$VSCcategory';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$CATstatusesSQL .=		 "'$aryA[0]',";
			$rec_count++;
			}
		$sthA->finish();

		chop($CATstatusesSQL);
		if (length($CATstatusesSQL)>2)
			{
			# FIND STATUSES IN STATUS CATEGORY
			## BEGIN CACHED HOURLY ANALYSIS: INBOUND CALLS
			if ($VCLhour_counts > 0) 
				{
				$VCL_current_hour_calls=0;
				$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_current_hour_date' and status IN($CATstatusesSQL);";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VCL_current_hour_calls =	$aryA[0];
					}
				$sthA->finish();
				if ($DBX) {print "VCLHC CURRENT HOUR CALLS CATEGORY $VSCcategory: |$sthArows|$VCL_current_hour_calls|$stmtA|\n";}

				$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_current_hour_date',type='C_$VSCcategory',next_hour='$VCL_next_hour_date',last_update=NOW(),calls='$VCL_current_hour_calls',hr='$HRhour_test' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_current_hour_calls';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "VCLHC STATS INSERT/UPDATE    TOTAL|$affected_rows|$stmtA|\n";}

				if ($HRhour_test > 0)
					{
					# check to see if cached hour totals already exist
					$VCHC_entry_count=0;
					$stmtA = "SELECT count(*) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='C_$VSCcategory' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VCHC_entry_count =	$aryA[0];
						}
					$sthA->finish();
					if ($DBX) {print "VCLHC CACHED HOUR CHECK: |$sthArows|$VCHC_entry_count|$stmtA|\n";}

					# if cached totals equal the number of hours, then run single query to get sums of calls, if not, go hour-by-hour
					if ($VCHC_entry_count >= $HRhour_test) 
						{
						$VCHC_cache_calls=0;
						$stmtA = "SELECT sum(calls) from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='C_$VSCcategory' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VCHC_cache_calls =	$aryA[0];
							}
						$sthA->finish();
						$VSCtally = ($VCHC_cache_calls + $VCL_current_hour_calls);
						if ($DBX) {print "VCLHC CACHED HOUR SINGLE QUERY: |$sthArows|$VCHC_cache_calls($VSCtally)|$stmtA|\n";}
						}
					else
						{
						@VCHC_hour=@MT;
						@VCHC_date_hour=@MT;
						@VCHC_next_hour=@MT;
						@VCHC_last_update=@MT;
						@VCHC_calls=@MT;
						$stmtA = "SELECT hr,date_hour,next_hour,last_update,calls from vicidial_ingroup_hour_counts where group_id='$group_id[$p]' and type='C_$VSCcategory' and date_hour >= '$VCL_day_start_date' and date_hour < '$VCL_current_hour_date' and last_update > next_hour order by hr;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows_hr=$sthA->rows;
						$j=0;
						while ($sthArows_hr > $j)
							{
							@aryA = $sthA->fetchrow_array;
							$VCHC_hour[$j] =		$aryA[0];
							$VCHC_date_hour[$j] =	$aryA[1];
							$VCHC_next_hour[$j] =	$aryA[2];
							$VCHC_last_update[$j] =	$aryA[3];
							$VCHC_calls[$j] =		$aryA[4];
							$j++;
							}
						$sthA->finish();
						if ($DBX) {print "VCLHC CACHED HOUR LIST QUERY: |$sthArows_hr|$stmtA|\n";}

						$j=0;
						while ($j < $HRhour_test) 
							{
							$k=0;
							$cache_hour_found=0;
							while ($sthArows_hr > $k)
								{
								if ( ($VCHC_hour[$k] == $j) && ($VCHC_hour[$j] eq "$j") ) # changed || to &&
									{
									$VSCtally = ($VSCtally + $VCHC_calls[$k]);
									$cache_hour_found++;
									if ($DBX) {print "VCLHC CACHED HOUR FOUND: |$VCHC_hour[$k]|$j|$k|$VSCtally|\n";}
									}
								$k++;
								}
							if ($cache_hour_found < 1) 
								{
								$j_next = ($j + 1);
								$VCL_this_hour_calls=0;
								$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date >= '$VCL_today $j:00:00' and call_date < '$VCL_today $j_next:00:00' and status IN($CATstatusesSQL);";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$VCL_this_hour_calls =	$aryA[0];
									$VSCtally = ($VSCtally + $VCL_this_hour_calls);
									}
								$sthA->finish();
								if ($DBX) {print "VCLHC CACHED HOUR QUERY: |$sthArows|$VCL_this_hour_calls|$stmtA|\n";}

								$stmtA="INSERT IGNORE INTO vicidial_ingroup_hour_counts SET group_id='$group_id[$p]',date_hour='$VCL_today $j:00:00',type='C_$VSCcategory',next_hour='$VCL_today $j_next:00:00',last_update=NOW(),calls='$VCL_this_hour_calls',hr='$j' ON DUPLICATE KEY UPDATE last_update=NOW(),calls='$VCL_this_hour_calls';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "VCLHC STATS INSERT/UPDATE    HOUR|$j|$affected_rows|$stmtA|\n";}
								}
							$j++;
							}
						$VSCtally = ($VSCtally + $VCL_current_hour_calls);
						}
					}
				else
					{
					# midnight hour, so total is only current hour stats
					$VSCtally =	$VCL_current_hour_calls;
					if ($DBX) {print "VCLHC MIDNIGHT HOUR: |$VSCtally|\n";}
					}
				}
			## END CACHED HOURLY ANALYSIS: INBOUND CALLS
			else
				{
				$stmtA = "SELECT count(*) from $vicidial_closer_log where campaign_id='$group_id[$p]' and call_date > '$VDL_date' and status IN($CATstatusesSQL);";
				#	if ($DBX) {print "|$stmtA|\n";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VSCtally =		$aryA[0];
					}
				$sthA->finish();
				}
			}
		$g++;
		if ($DBX) {print "     $group_id[$p]|$VSCcategory|$VSCtally|$CATstatusesSQL|\n";}
		$VSCupdateSQL .= "status_category_$g='$VSCcategory',status_category_count_$g='$VSCtally',";
		$debug_ingroup_output .= "     $group_id[$p]|$VSCcategory|$VSCtally|$CATstatusesSQL|";
		}
	while ($g < 4)
		{
		$g++;
		$VSCupdateSQL .= "status_category_$g='',status_category_count_$g='0',";
		}
	chop($VSCupdateSQL);

	$vcs_exists=1;
	$stmtA = "SELECT count(*) from vicidial_campaign_stats where campaign_id='$group_id[$p]';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vcs_exists =		 $aryA[0];
		}
	$sthA->finish();

	if ($vcs_exists < 1)
		{
		$stmtA = "INSERT INTO vicidial_campaign_stats (campaign_id) values('$group_id[$p]');";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "$group_id[$p]|$stmtA|\n";}
		}

	$stmtA = "UPDATE vicidial_campaign_stats SET calls_today='$iVCScalls_today[$p]',answers_today='$iVCSanswers_today[$p]',drops_today='$iVCSdrops_today[$p]',drops_today_pct='$iVCSdrops_today_pct[$p]',drops_answers_today_pct='$iVCSdrops_answers_today_pct[$p]',hold_sec_stat_one='$answer_sec_pct_rt_stat_one_PCT[$p]',hold_sec_stat_two='$answer_sec_pct_rt_stat_two_PCT[$p]',hold_sec_answer_calls='$hold_sec_answer_calls[$p]',hold_sec_drop_calls='$hold_sec_drop_calls[$p]',hold_sec_queue_calls='$hold_sec_queue_calls[$p]',park_calls_today='$iVCSpark_calls_today[$p]',park_sec_today='$iVCSpark_sec_today[$p]',$VSCupdateSQL where campaign_id='$group_id[$p]';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DBX) {print "INBOUND $group_id[$p]|$affected_rows|$stmtA|\n";}

	print "$p         IN-GROUP: $group_id[$p]   CALLS: $iVCScalls_today[$p]   ANSWER: $iVCSanswers_today[$p]   DROPS: $iVCSdrops_today[$p]\n";
	print "               Stat1: $answer_sec_pct_rt_stat_one_PCT[$p]   Stat2: $answer_sec_pct_rt_stat_two_PCT[$p]   Hold: $hold_sec_queue_calls[$p]|$hold_sec_answer_calls[$p]|$hold_sec_drop_calls[$p]\n";
	$debug_ingroup_output .= "$p         IN-GROUP: $group_id[$p]   CALLS: $iVCScalls_today[$p]   ANSWER: $iVCSanswers_today[$p]   DROPS: $iVCSdrops_today[$p]\n";
	$debug_ingroup_output .= "               Stat1: $answer_sec_pct_rt_stat_one_PCT[$p]   Stat2: $answer_sec_pct_rt_stat_two_PCT[$p]   Hold: $hold_sec_queue_calls[$p]|$hold_sec_answer_calls[$p]|$hold_sec_drop_calls[$p]\n";

	$debug_ingroup_output =~ s/;|\\\\|\/|\'//gi;
	$stmtA="INSERT IGNORE INTO vicidial_campaign_stats_debug SET server_ip='INBOUND',campaign_id='$group_id[$p]',entry_time='$now_date',debug_output='$debug_ingroup_output' ON DUPLICATE KEY UPDATE entry_time='$now_date',debug_output='$debug_ingroup_output';";
	$affected_rows = $dbhA->do($stmtA);

	##### BEGIN - DAILY STATS UPDATE INGROUP
	if ($daily_stats > 0)
		{
		$STATSmax_inbound=0;
		$STATSmax_agents=0;
		$STATSmax_remote_agents=0;
		$STATStotal_calls=0;
		$stmtA = "SELECT max_inbound,max_agents,max_remote_agents,total_calls from vicidial_daily_max_stats where campaign_id='$group_id[$p]' and stats_type='INGROUP' and stats_flag='OPEN' order by update_time desc limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$STATSmax_inbound =			$aryA[0];
			$STATSmax_agents =			$aryA[1];
			$STATSmax_remote_agents =	$aryA[2];
			$STATStotal_calls =			$aryA[3];
			$sthA->finish();

			$update_SQL='';
			if ($STATSmax_inbound < $iVCSlive_calls[$p]) 
				{$update_SQL .= ",max_inbound='$iVCSlive_calls[$p]'";}
			if ($STATSmax_agents < $itotal_agents[$p]) 
				{$update_SQL .= ",max_agents='$itotal_agents[$p]'";}
			if ($STATSmax_remote_agents < $itotal_remote_agents[$p])
				{$update_SQL .= ",max_remote_agents='$itotal_remote_agents[$p]'";}
			if ($STATStotal_calls != $iVCScalls_today[$p]) 
				{$update_SQL .= ",total_calls='$iVCScalls_today[$p]'";}
			if (length($update_SQL) > 5) 
				{
				$stmtA = "UPDATE vicidial_daily_max_stats SET update_time=NOW()$update_SQL where campaign_id='$group_id[$p]' and stats_type='INGROUP' and stats_flag='OPEN';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DAILY STATS UPDATE $campaign_id[$p]|$affected_rows|$stmtA|\n";}
				}
			}
		else
			{
			$sthA->finish();

			$stmtA = "INSERT INTO vicidial_daily_max_stats SET stats_date='$file_date',update_time=NOW(),max_inbound='$iVCSlive_calls[$p]',max_agents='$itotal_agents[$p]',max_remote_agents='$itotal_remote_agents[$p]',total_calls='$iVCScalls_today[$p]',campaign_id='$group_id[$p]',stats_type='INGROUP',stats_flag='OPEN';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "DAILY STATS INSERT $group_id[$p]|$affected_rows|$stmtA|\n";}
			}
		}
	##### END - DAILY STATS UPDATE INGROUP
	}
##### END calculate_drops_inbound



##### BEGIN calculate the proper dial level #####
sub calculate_dial_level
	{
	$RESETdiff_ratio_updater++;
	$VCSINCALL[$i]=0;
	$VCSINCALLthresh[$i]=0;
	$VCSINCALLdiff[$i]=0;
	$VCSREADY[$i]=0;
	$VCSCLOSER[$i]=0;
	$VCSPAUSED[$i]=0;
	$VCSagents[$i]=0;
	$VCSagents_calc[$i]=0;
	$VCSagents_active[$i]=0;

	$adaptive_string  = "\n";
	$adaptive_string .= "CAMPAIGN:   $campaign_id[$i]     $i\n";

	# COUNTS OF STATUSES OF AGENTS IN THIS CAMPAIGN
	$stmtA = "SELECT count(*),status from vicidial_live_agents where ( (campaign_id='$campaign_id[$i]') or (dial_campaign_id='$campaign_id[$i]') $drop_ingroup_SQL[$i] ) and last_update_time > '$VDL_one' group by status;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$VCSagent_count[$i] =	$aryA[0];
		$VCSagent_status[$i] =	$aryA[1];
		$rec_count++;
		if ($VCSagent_status[$i] =~ /INCALL|QUEUE/) {$VCSINCALL[$i] = ($VCSINCALL[$i] + $VCSagent_count[$i]);}
		if ($VCSagent_status[$i] =~ /READY/) {$VCSREADY[$i] = ($VCSREADY[$i] + $VCSagent_count[$i]);}
		if ($VCSagent_status[$i] =~ /CLOSER/) {$VCSCLOSER[$i] = ($VCSCLOSER[$i] + $VCSagent_count[$i]);}
		if ($VCSagent_status[$i] =~ /PAUSED/) {$VCSPAUSED[$i] = ($VCSPAUSED[$i] + $VCSagent_count[$i]);}
		$VCSagents[$i] = ($VCSagents[$i] + $VCSagent_count[$i]);
		}
	$sthA->finish();
	$VCSINCALLthresh[$i] = $VCSINCALL[$i];

	# If Agent In-Call Tally Seconds Threshold is enabled, find the number of agents INCALL at-or-below the incall_tally_threshold_seconds
	if ($incall_tally_threshold_seconds[$i] > 0) 
		{
		$VCSINCALLthresh[$i]=0;
		$stmtA = "SELECT count(*) from vicidial_live_agents where ( (campaign_id='$campaign_id[$i]') or (dial_campaign_id='$campaign_id[$i]') $drop_ingroup_SQL[$i] ) and last_update_time > '$VDL_one' and status IN('INCALL','QUEUE') and ( (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_call_time)) <= $incall_tally_threshold_seconds[$i]);";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSINCALLthresh[$i] = ($VCSINCALLthresh[$i] + $aryA[0]);
			}
		$sthA->finish();

		$VCSINCALLdiff[$i] = ($VCSINCALL[$i] - $VCSINCALLthresh[$i]);

		$adaptive_string .= "   !! AGENT IN-CALL TALLY SECONDS THRESHOLD ENABLED for INCALL AGENTS: $incall_tally_threshold_seconds[$i] seconds  |all: $VCSINCALL[$i]   under thresh: $VCSINCALLthresh[$i] (diff: $VCSINCALLdiff[$i])|\n";
		$VCSINCALL[$i] = $VCSINCALLthresh[$i];
		}

	# If AVAILABLE ONLY TALLY is enabled, find proper agent counts
	if ($available_only_ratio_tally[$i] =~ /Y/) 
		{$VCSagents_calc[$i] = $VCSREADY[$i];}
	else
		{
		$VCSagents_calc[$i] = ($VCSINCALL[$i] + $VCSREADY[$i]);
		if ( ($available_only_tally_threshold[$i] =~ /LOGGED-IN_AGENTS/) && ($available_only_tally_threshold_agents[$i] > $VCSagents[$i]) )
			{
			$adaptive_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for LOGGED-IN_AGENTS: ($available_only_tally_threshold_agents[$i] > $VCSagents[$i])\n";
			$VCSagents_calc[$i] = $VCSREADY[$i];
			$available_only_ratio_tally[$i] = 'Y*';
			}
		if ( ($available_only_tally_threshold[$i] =~ /NON-PAUSED_AGENTS/) && ($available_only_tally_threshold_agents[$i] > $VCSagents_calc[$i]) )
			{
			$adaptive_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for NON-PAUSED_AGENTS: ($available_only_tally_threshold_agents[$i] > $VCSagents_calc[$i])\n";
			$VCSagents_calc[$i] = $VCSREADY[$i];
			$available_only_ratio_tally[$i] = 'Y*';
			}
		if ( ($available_only_tally_threshold[$i] =~ /WAITING_AGENTS/) && ($available_only_tally_threshold_agents[$i] > $VCSREADY[$i]) )
			{
			$adaptive_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for WAITING_AGENTS: ($available_only_tally_threshold_agents[$i] > $VCSREADY[$i])\n";
			$VCSagents_calc[$i] = $VCSREADY[$i];
			$available_only_ratio_tally[$i] = 'Y*';
			}
		}
	$VCSagents_active[$i] = ($VCSINCALL[$i] + $VCSREADY[$i] + $VCSCLOSER[$i]);
	### END - GATHER STATS FOR THE vicidial_campaign_stats TABLE ###

	if ($campaign_allow_inbound[$i] =~ /Y/)
		{
		# GET AVERAGES FROM THIS CAMPAIGN
		$stmtA = "SELECT differential_onemin,agents_average_onemin from vicidial_campaign_stats where campaign_id='$campaign_id[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$differential_onemin[$i] =		$aryA[0];
			$agents_average_onemin[$i] =	$aryA[1];
			$rec_count++;
			}
		$sthA->finish();
		}
	else
		{
		$agents_average_onemin[$i] =	$total_agents_avg[$i];  
		$differential_onemin[$i] =		$stat_differential[$i];
		}

	if ( ($dial_method[$i] =~ /ADAPT_HARD_LIMIT|ADAPT_AVERAGE|ADAPT_TAPERED/) || ($force_test>0) )
		{
		# Calculate the optimal dial_level differential for the past minute
		$differential_target[$i] = ($differential_onemin[$i] + $adaptive_dl_diff_target[$i]);
		if ( ($differential_target[$i] != 0) && ($agents_average_onemin[$i] != 0) )
			{
			$differential_mul[$i] = ($differential_target[$i] / $agents_average_onemin[$i]);
			$differential_pct_raw[$i] = ($differential_mul[$i] * 100);
			}
		else
			{
			$differential_mul[$i] = 0;
			$differential_pct_raw[$i] = 0;
			}
		$differential_pct[$i] = sprintf("%.2f", $differential_pct_raw[$i]);

		# Factor in the intensity setting
		$intensity_mul[$i] = ($adaptive_intensity[$i] / 100);
		if ($differential_pct_raw[$i] < 0)
			{
			$abs_intensity_mul[$i] = abs($intensity_mul[$i] - 1);
			$intensity_diff[$i] = ($differential_pct_raw[$i] * $abs_intensity_mul[$i]);
			}
		else
			{$intensity_diff[$i] = ($differential_pct_raw[$i] * ($intensity_mul[$i] + 1) );}
		$intensity_pct[$i] = sprintf("%.2f", $intensity_diff[$i]);	
		$intensity_diff_mul[$i] = ($intensity_diff[$i] / 100);

		# Suggested dial_level based on differential
		$suggested_dial_level[$i] = ($auto_dial_level[$i] * ($differential_mul[$i] + 1) );
		$suggested_dial_level[$i] = sprintf("%.3f", $suggested_dial_level[$i]);

		# Suggested dial_level based on differential with intensity setting
		$intensity_dial_level[$i] = ($auto_dial_level[$i] * ($intensity_diff_mul[$i] + 1) );
		$intensity_dial_level[$i] = sprintf("%.3f", $intensity_dial_level[$i]);

		# Calculate last timezone target for ADAPT_TAPERED
		$last_target_hour_final[$i] = $adaptive_latest_server_time[$i];
	#	if ($last_target_hour_final[$i]>2400) {$last_target_hour_final[$i]=2400;}
		$tapered_hours_left[$i] = ($last_target_hour_final[$i] - $current_hourmin);
		if ($tapered_hours_left[$i] > 1000)
			{$tapered_rate[$i] = 1;}
		else
			{$tapered_rate[$i] = ($tapered_hours_left[$i] / 1000);}

		$adaptive_string .= "SETTINGS-\n";
		$adaptive_string .= "   DIAL LEVEL:    $auto_dial_level[$i]\n";
		$adaptive_string .= "   DIAL METHOD:   $dial_method[$i]\n";
		$adaptive_string .= "   AVAIL ONLY:    $available_only_ratio_tally[$i]\n";
		$adaptive_string .= "   DROP PERCENT:  $adaptive_dropped_percentage[$i]\n";
		$adaptive_string .= "   MAX LEVEL:     $adaptive_maximum_level[$i]\n";
		$adaptive_string .= "   SERVER TIME:   $current_hourmin\n";
		$adaptive_string .= "   LATE TARGET:   $last_target_hour_final[$i]     ($tapered_hours_left[$i] left|$tapered_rate[$i])\n";
		$adaptive_string .= "   INTENSITY:     $adaptive_intensity[$i]\n";
		$adaptive_string .= "   DLDIFF TARGET: $adaptive_dl_diff_target[$i]\n";
		$adaptive_string .= "CURRENT STATS-\n";
		$adaptive_string .= "   AVG AGENTS:      $agents_average_onemin[$i]\n";
		$adaptive_string .= "   AGENTS:          $VCSagents[$i]  ACTIVE: $VCSagents_active[$i]   CALC: $VCSagents_calc[$i]  INCALL: $VCSINCALL[$i]    READY: $VCSREADY[$i]\n";
		$adaptive_string .= "   DL DIFFERENTIAL: $differential_target[$i] = ($differential_onemin[$i] + $adaptive_dl_diff_target[$i])\n";
		$adaptive_string .= "DIAL LEVEL SUGGESTION-\n";
		$adaptive_string .= "      PERCENT DIFF: $differential_pct[$i]\n";
		$adaptive_string .= "      SUGGEST DL:   $suggested_dial_level[$i] = ($auto_dial_level[$i] * ($differential_mul[$i] + 1) )\n";
		$adaptive_string .= "      INTENSE DIFF: $intensity_pct[$i]\n";
		$adaptive_string .= "      INTENSE DL:   $intensity_dial_level[$i] = ($auto_dial_level[$i] * ($intensity_diff_mul[$i] + 1) )\n";
		if ($intensity_dial_level[$i] > $adaptive_maximum_level[$i])
			{
			$adaptive_string .= "      DIAL LEVEL OVER CAP! SETTING TO CAP: $adaptive_maximum_level[$i]\n";
			$intensity_dial_level[$i] = $adaptive_maximum_level[$i];
			}
		if ($intensity_dial_level[$i] < 1)
			{
			$adaptive_string .= "      DIAL LEVEL TOO LOW! SETTING TO 1\n";
			$intensity_dial_level[$i] = "1.0";
			}
		$adaptive_string .= "DROP STATS-\n";
		$adaptive_string .= "   TODAY DROPS:     $VCScalls_today[$i]   $VCSdrops_today[$i]   $VCSdrops_today_pct[$i]%\n";
		$adaptive_string .= "     ANSWER DROPS:     $VCSanswers_today[$i]   $VCSdrops_answers_today_pct[$i]%\n";
		$adaptive_string .= "   ONE HOUR DROPS:  $VCScalls_hour[$i]/$VCSanswers_hour[$i]   $VCSdrops_hour[$i]   $VCSdrops_hour_pct[$i]%\n";
		$adaptive_string .= "   HALF HOUR DROPS: $VCScalls_halfhour[$i]/$VCSanswers_halfhour[$i]   $VCSdrops_halfhour[$i]   $VCSdrops_halfhour_pct[$i]%\n";
		$adaptive_string .= "   FIVE MIN DROPS:  $VCScalls_five[$i]/$VCSanswers_five[$i]   $VCSdrops_five[$i]   $VCSdrops_five_pct[$i]%\n";
		$adaptive_string .= "   ONE MIN DROPS:   $VCScalls_one[$i]/$VCSanswers_one[$i]   $VCSdrops_one[$i]   $VCSdrops_one_pct[$i]%\n";

		### DROP PERCENTAGE RULES TO LOWER DIAL_LEVEL ###
		if ( ($VCScalls_one[$i] > 20) && ($VCSdrops_one_pct[$i] > 50) )
			{
			$intensity_dial_level[$i] = ($intensity_dial_level[$i] / 2);
			$adaptive_string .= "      DROP RATE OVER 50% FOR LAST MINUTE! CUTTING DIAL LEVEL TO: $intensity_dial_level[$i]\n";
			}
		if ( ($VCScalls_today[$i] > 50) && ($VCSdrops_answers_today_pct[$i] > $adaptive_dropped_percentage[$i]) )
			{
			if ($dial_method[$i] =~ /ADAPT_HARD_LIMIT/) 
				{
				$intensity_dial_level[$i] = "1.0";
				$adaptive_string .= "      DROP RATE OVER HARD LIMIT FOR TODAY! HARD DIAL LEVEL TO: 1.0\n";
				}
			if ($dial_method[$i] =~ /ADAPT_AVERAGE/) 
				{
				$intensity_dial_level[$i] = ($intensity_dial_level[$i] / 2);
				$adaptive_string .= "      DROP RATE OVER LIMIT FOR TODAY! AVERAGING DIAL LEVEL TO: $intensity_dial_level[$i]\n";
				}
			if ($dial_method[$i] =~ /ADAPT_TAPERED/) 
				{
				if ($tapered_hours_left[$i] < 0) 
					{
					$intensity_dial_level[$i] = "1.0";
					$adaptive_string .= "      DROP RATE OVER LAST HOUR LIMIT FOR TODAY! TAPERING DIAL LEVEL TO: 1.0\n";
					}
				else
					{
					$intensity_dial_level[$i] = ($intensity_dial_level[$i] * $tapered_rate[$i]);
					$adaptive_string .= "      DROP RATE OVER LIMIT FOR TODAY! TAPERING DIAL LEVEL TO: $intensity_dial_level[$i]\n";
					}
				}
			}

		### BEGIN Dial Level Threshold Check ###
		$VCSagents_nonpaused_temp = ($VCSINCALL[$i] + $VCSREADY[$i]);
		if ( ($dial_level_threshold[$i] =~ /LOGGED-IN_AGENTS/) && ($dial_level_threshold_agents[$i] > $VCSagents[$i]) )
			{
			$adaptive_string .= "   !! DIAL LEVEL THRESHOLD triggered for LOGGED-IN_AGENTS: ($dial_level_threshold_agents[$i] > $VCSagents[$i])\n";
			$intensity_dial_level[$i] = "1.0";
			}
		if ( ($dial_level_threshold[$i] =~ /NON-PAUSED_AGENTS/) && ($dial_level_threshold_agents[$i] > $VCSagents_nonpaused_temp) )
			{
			$adaptive_string .= "   !! DIAL LEVEL THRESHOLD triggered for NON-PAUSED_AGENTS: ($dial_level_threshold_agents[$i] > $VCSagents_nonpaused_temp)\n";
			$intensity_dial_level[$i] = "1.0";
			}
		if ( ($dial_level_threshold[$i] =~ /WAITING_AGENTS/) && ($dial_level_threshold_agents[$i] > $VCSREADY[$i]) )
			{
			$adaptive_string .= "   !! DIAL LEVEL THRESHOLD triggered for WAITING_AGENTS: ($dial_level_threshold_agents[$i] > $VCSREADY[$i])\n";
			$intensity_dial_level[$i] = "1.0";
			}
		### END Dial Level Threshold Check ###


		### ALWAYS RAISE DIAL_LEVEL TO 1.0 IF IT IS LOWER ###
		if ($intensity_dial_level[$i] < 1)
			{
			$adaptive_string .= "      DIAL LEVEL TOO LOW! SETTING TO 1\n";
			$intensity_dial_level[$i] = "1.0";
			}

		if (!$TEST)
			{
			$stmtA = "UPDATE vicidial_campaigns SET auto_dial_level='$intensity_dial_level[$i]' where campaign_id='$campaign_id[$i]';";
			$Uaffected_rows = $dbhA->do($stmtA);
			}

		$adaptive_string .= "DIAL LEVEL UPDATED TO: $intensity_dial_level[$i]          CONFIRM: $Uaffected_rows\n";
		}

	if ($DB) {print "campaign stats updated:  $campaign_id[$i]   $adaptive_string\n";}

	&adaptive_logger;
	}
##### END calculate the proper dial level #####


##### BEGIN Call Quota Lead Ranking logging #####
sub call_quota_logging
	{
	# Gather settings container for Call Quota Lead Ranking
	$CQcontainer_entry='';
	$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$VD_call_quota_lead_ranking';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$CQcontainer_entry = $aryA[0];
		$CQcontainer_entry =~ s/\\//gi;
		}
	$sthA->finish();

	# Define variables for Call Quota settings
	$session_one='';
	$session_two='';
	$session_three='';
	$session_four='';
	$session_five='';
	$session_six='';
	$settings_session_score=0;
	$zero_rank_after_call=0;

	if (length($CQcontainer_entry) > 5) 
		{
		@container_lines = split(/\n/,$CQcontainer_entry);
		$c=0;
		foreach(@container_lines)
			{
			$container_lines[$c] =~ s/;.*|\r|\t| //gi;
			if (length($container_lines[$c]) > 5)
				{
				# define core settings
				if ($container_lines[$c] =~ /^zero_rank_after_call/i)
					{
					$container_lines[$c] =~ s/zero_rank_after_call=>//gi;
					if ( ($container_lines[$c] >= 0) && ($container_lines[$c] <= 1) ) 
						{
						$zero_rank_after_call = $container_lines[$c];
						}
					}
				# define sessions
				if ($container_lines[$c] =~ /^session_one/i)
					{
					$session_one_valid=0; $session_one_start=''; $session_one_end='';
					$session_one = $container_lines[$c];
					$session_one =~ s/session_one=>//gi;
					if ( (length($session_one) > 0) && (length($session_one) <= 9) && ($session_one =~ /,/) ) 
						{
						@session_oneARY = split(/,/,$session_one);
						$session_one_start = $session_oneARY[0];
						$session_one_end = $session_oneARY[1];
						if ( (length($session_one_start) >= 4) && (length($session_one_end) >= 4) && ($session_one_start < $session_one_end) && ($session_one_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_one_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_two/i)
					{
					$session_two_valid=0; $session_two_start=''; $session_two_end='';
					$session_two = $container_lines[$c];
					$session_two =~ s/session_two=>//gi;
					if ( (length($session_two) > 0) && (length($session_two) <= 9) && ($session_two =~ /,/) ) 
						{
						@session_twoARY = split(/,/,$session_two);
						$session_two_start = $session_twoARY[0];
						$session_two_end = $session_twoARY[1];
						if ( (length($session_two_start) >= 4) && (length($session_two_end) >= 4) && ($session_one_valid > 0) && ($session_one_end <= $session_two_start) && ($session_two_start < $session_two_end) && ($session_two_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_two_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_three/i)
					{
					$session_three_valid=0; $session_three_start=''; $session_three_end='';
					$session_three = $container_lines[$c];
					$session_three =~ s/session_three=>//gi;
					if ( (length($session_three) > 0) && (length($session_three) <= 9) && ($session_three =~ /,/) ) 
						{
						@session_threeARY = split(/,/,$session_three);
						$session_three_start = $session_threeARY[0];
						$session_three_end = $session_threeARY[1];
						if ( (length($session_three_start) >= 4) && (length($session_three_end) >= 4) && ($session_two_valid > 0) && ($session_two_end <= $session_three_start) && ($session_three_start < $session_three_end) && ($session_three_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_three_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_four/i)
					{
					$session_four_valid=0; $session_four_start=''; $session_four_end='';
					$session_four = $container_lines[$c];
					$session_four =~ s/session_four=>//gi;
					if ( (length($session_four) > 0) && (length($session_four) <= 9) && ($session_four =~ /,/) ) 
						{
						@session_fourARY = split(/,/,$session_four);
						$session_four_start = $session_fourARY[0];
						$session_four_end = $session_fourARY[1];
						if ( (length($session_four_start) >= 4) && (length($session_four_end) >= 4) && ($session_three_valid > 0) && ($session_three_end <= $session_four_start) && ($session_four_start < $session_four_end) && ($session_four_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_four_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_five/i)
					{
					$session_five_valid=0; $session_five_start=''; $session_five_end='';
					$session_five = $container_lines[$c];
					$session_five =~ s/session_five=>//gi;
					if ( (length($session_five) > 0) && (length($session_five) <= 9) && ($session_five =~ /,/) ) 
						{
						@session_fiveARY = split(/,/,$session_five);
						$session_five_start = $session_fiveARY[0];
						$session_five_end = $session_fiveARY[1];
						if ( (length($session_five_start) >= 4) && (length($session_five_end) >= 4) && ($session_four_valid > 0) && ($session_four_end <= $session_five_start) && ($session_five_start < $session_five_end) && ($session_five_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_five_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_six/i)
					{
					$session_six_valid=0; $session_six_start=''; $session_six_end='';
					$session_six = $container_lines[$c];
					$session_six =~ s/session_six=>//gi;
					if ( (length($session_six) > 0) && (length($session_six) <= 9) && ($session_six =~ /,/) ) 
						{
						@session_sixARY = split(/,/,$session_six);
						$session_six_start = $session_sixARY[0];
						$session_six_end = $session_sixARY[1];
						if ( (length($session_six_start) >= 4) && (length($session_six_end) >= 4) && ($session_five_valid > 0) && ($session_five_end <= $session_six_start) && ($session_six_start < $session_six_end) && ($session_six_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_six_valid++;
							}
						}
					}
				}
			else
				{if ($DBX > 0) {print "     blank line: $c|$container_lines[$c]|\n";}}
			$c++;
			}
		if ($settings_session_score >= 1)
			{
			$stmtA = "SELECT list_id,called_count,rank FROM vicidial_list where lead_id='$CIDlead_id';";
			$event_string = "|$stmtA|";   &event_logger;
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VLlist_id =			$aryA[0];
				$VLcalled_count =		$aryA[1];
				$VLrank =				$aryA[2];
				$tempVLrank = $VLrank;
				if ( ($zero_rank_after_call > 0) && ($VLrank > 0) ) {$tempVLrank=0;}
				}
			$sthA->finish();

			$secX = time();
			$CQtarget = ($secX - 14400);	# look back 4 hours
			($CQsec,$CQmin,$CQhour,$CQmday,$CQmon,$CQyear,$CQwday,$CQyday,$CQisdst) = localtime($CQtarget);
			$CQyear = ($CQyear + 1900);
			$CQmon++;
			if ($CQmon < 10) {$CQmon = "0$CQmon";}
			if ($CQmday < 10) {$CQmday = "0$CQmday";}
			if ($CQhour < 10) {$CQhour = "0$CQhour";}
			if ($CQmin < 10) {$CQmin = "0$CQmin";}
			if ($CQsec < 10) {$CQsec = "0$CQsec";}
			$CQSQLdate = "$CQyear-$CQmon-$CQmday $CQhour:$CQmin:$CQsec";

			$VDL_call_datetime='';
			$stmtA = "SELECT call_date from vicidial_dial_log where lead_id='$CIDlead_id' and call_date > \"$CQSQLdate\" and caller_code LIKE \"%$CIDlead_id\" order by call_date desc limit 1;";
			$event_string = "|$stmtA|";   &event_logger;
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VDLcall_datetime = 	$aryA[0];
				@VDLcall_datetimeARY = split(/ /,$VDLcall_datetime);
				@VDLcall_timeARY = split(/:/,$VDLcall_datetimeARY[1]);
				$VDLcall_hourmin = "$VDLcall_timeARY[0]$VDLcall_timeARY[1]";

				if ( ($session_one_start <= $VDLcall_hourmin) and ($session_one_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=1; 
					$session_newSQL=",session_one_calls='1',session_one_today_calls='1'"; 
					$session_updateSQL=",session_one_calls=(session_one_calls + 1),session_one_today_calls=(session_one_today_calls + 1)";
					}
				if ( ($session_two_start <= $VDLcall_hourmin) and ($session_two_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=2; 
					$session_newSQL=",session_two_calls='1',session_two_today_calls='1'"; 
					$session_updateSQL=",session_two_calls=(session_two_calls + 1),session_two_today_calls=(session_two_today_calls + 1)";
					}
				if ( ($session_three_start <= $VDLcall_hourmin) and ($session_three_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=3; 
					$session_newSQL=",session_three_calls='1',session_three_today_calls='1'"; 
					$session_updateSQL=",session_three_calls=(session_three_calls + 1),session_three_today_calls=(session_three_today_calls + 1)";
					}
				if ( ($session_four_start <= $VDLcall_hourmin) and ($session_four_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=4; 
					$session_newSQL=",session_four_calls='1',session_four_today_calls='1'"; 
					$session_updateSQL=",session_four_calls=(session_four_calls + 1),session_four_today_calls=(session_four_today_calls + 1)";
					}
				if ( ($session_five_start <= $VDLcall_hourmin) and ($session_five_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=5; 
					$session_newSQL=",session_five_calls='1',session_five_today_calls='1'"; 
					$session_updateSQL=",session_five_calls=(session_five_calls + 1),session_five_today_calls=(session_five_today_calls + 1)";
					}
				if ( ($session_six_start <= $VDLcall_hourmin) and ($session_six_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=6; 
					$session_newSQL=",session_six_calls='1',session_six_today_calls='1'"; 
					$session_updateSQL=",session_six_calls=(session_six_calls + 1),session_six_today_calls=(session_six_today_calls + 1)";
					}

				$event_string = "CQ-Debug 2: $VDLcall_datetime|$VDLcall_hourmin|$timeclock_end_of_day|$session_one_start|$session_one_end|$call_in_session|";   &event_logger;

				if ($call_in_session > 0)
					{
					if (length($timeclock_end_of_day) < 1) {$timeclock_end_of_day='0000';}
					$timeclock_end_of_day_hour = (substr($timeclock_end_of_day, 0, 2) + 0);
					$timeclock_end_of_day_min = (substr($timeclock_end_of_day, 2, 2) + 0);

					$today_start_epoch = timelocal('0',$timeclock_end_of_day_min,$timeclock_end_of_day_hour,$mday,($mon-1),$year);
					if ($timeclock_end_of_day > $VDLcall_hourmin)
						{$today_start_epoch = ($today_start_epoch - 86400);}
					$day_two_start_epoch = ($today_start_epoch - (86400 * 1));
					$day_three_start_epoch = ($today_start_epoch - (86400 * 2));
					$day_four_start_epoch = ($today_start_epoch - (86400 * 3));
					$day_five_start_epoch = ($today_start_epoch - (86400 * 4));
					$day_six_start_epoch = ($today_start_epoch - (86400 * 5));
					$day_seven_start_epoch = ($today_start_epoch - (86400 * 6));

					# Gather the details on existing vicidial_lead_call_quota_counts for this lead, if there is one
					$stmtA = "SELECT first_call_date,UNIX_TIMESTAMP(first_call_date),last_call_date from vicidial_lead_call_quota_counts where lead_id='$CIDlead_id' and list_id='$VLlist_id';";
					$event_string = "|$stmtA|";   &event_logger;
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$VLCQCinfo_ct=$sthA->rows;
					if ($VLCQCinfo_ct > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VLCQCfirst_call_datetime =		$aryA[0];
						$VLCQCfirst_call_epoch =		$aryA[1];
						$VLCQClast_call_date =			$aryA[2];

						if ($VDLcall_datetime ne $VLCQClast_call_date) 
							{
							if ($VLCQCfirst_call_epoch >= $today_start_epoch) 
								{$day_updateSQL=',day_one_calls=(day_one_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_two_start_epoch) and ($VLCQCfirst_call_epoch < $today_start_epoch) )
								{$day_updateSQL=',day_two_calls=(day_two_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_three_start_epoch) and ($VLCQCfirst_call_epoch < $day_two_start_epoch) )
								{$day_updateSQL=',day_three_calls=(day_three_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_four_start_epoch) and ($VLCQCfirst_call_epoch < $day_three_start_epoch) )
								{$day_updateSQL=',day_four_calls=(day_four_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_five_start_epoch) and ($VLCQCfirst_call_epoch < $day_four_start_epoch) )
								{$day_updateSQL=',day_five_calls=(day_five_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_six_start_epoch) and ($VLCQCfirst_call_epoch < $day_five_start_epoch) )
								{$day_updateSQL=',day_six_calls=(day_six_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_seven_start_epoch) and ($VLCQCfirst_call_epoch < $day_six_start_epoch) )
								{$day_updateSQL=',day_seven_calls=(day_seven_calls+1)';}
							# Update in the vicidial_lead_call_quota_counts table for this lead
							$stmtA="UPDATE vicidial_lead_call_quota_counts SET last_call_date='$VDLcall_datetime',status='$temp_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() $session_updateSQL $day_updateSQL where lead_id='$CIDlead_id' and list_id='$VLlist_id';";
							}
						else
							{
							# Update in the vicidial_lead_call_quota_counts table for this lead
							$stmtA="UPDATE vicidial_lead_call_quota_counts SET status='$temp_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() where lead_id='$CIDlead_id' and list_id='$VLlist_id';";
							}
						$VLCQCaffected_rows_update = $dbhA->do($stmtA);
						$event_string = "--    VLCQC record updated: |$VLCQCaffected_rows_update|   |$stmtA|";   &event_logger;
						}
					else
						{
						# Insert new record into vicidial_lead_call_quota_counts table for this lead
						$stmtA="INSERT INTO vicidial_lead_call_quota_counts SET lead_id='$CIDlead_id',list_id='$VLlist_id',first_call_date='$VDLcall_datetime',last_call_date='$VDLcall_datetime',status='$temp_status',called_count='$VLcalled_count',day_one_calls='1',rank='$tempVLrank',modify_date=NOW() $session_newSQL;";
						$VLCQCaffected_rows_update = $dbhA->do($stmtA);
						$event_string = "--    VLCQC record inserted: |$VLCQCaffected_rows_update|   |$stmtA|";   &event_logger;
						}

					if ( ($zero_rank_after_call > 0) && ($VLrank > 0) )
						{
						# Update this lead to rank=0
						$stmtA="UPDATE vicidial_list SET rank='0' where lead_id='$CIDlead_id';";
						$VLCQCaffected_rows_zero_rank = $dbhA->do($stmtA);
						$event_string = "--    VLCQC lead rank zero: |$VLCQCaffected_rows_zero_rank|   |$stmtA|";   &event_logger;
						}
					}
				}
			$sthA->finish();
			}
		}
	}
##### END Call Quota Lead Ranking logging #####


##### BEGIN math divisor sub #####
sub MathZDC
	{
	$quotient=0;
#	if ($DBX) {print "MathZDC sub DEBUG:      dividend: $dividend   divisor:  $divisor\n";}

	if ( ($divisor == '0') || (length($divisor) < 1) )
		{
		return $quotient;
		}
	else 
		{
		if ($dividend == '0')
			{
			return 0;
			}
		else 
			{
			$percent = ($dividend/$divisor);
			return $percent;
			}
		}
	}
