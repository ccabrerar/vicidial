#!/usr/bin/perl
#
# AST_VDhopper.pl version 2.14
#
# DESCRIPTION:
# Updates the VICIDIAL leads hopper for the streamlined approach of allocating 
# leads to dialers. Also allows for larger lists and faster dialing.
#
# SUMMARY:
# For VICIDIAL outbound dialing, this program must be in the crontab on only one
# server, running every minute during operating hours. For manual dialing
# campaigns, there is a campaign option for no-hopper-dialing that can operate
# without this script running, but the list size will be limited under that.
# 
# hopper sources:
#  - A = Auto-alt-dial
#  - C = Scheduled Callbacks
#  - N = Xth New lead order
#  - P = Non-Agent API hopper load
#  - Q = No-hopper queue insert
#  - R = Recycled leads
#  - S = Standard hopper load
#  - D = Campaign Drop-Run
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 50810-1613 - Added database server variable definitions lookup
# 60215-1106 - Added Scheduled Callback release functionality
# 60228-1623 - Change Callback activation to set the called_since_last_reset=N
# 60228-1735 - Added hopper gmt validation to remove gmt outside of time range
# 60320-0932 - Added inactive lead list hopper deletion (Thanks Vic Jolin)
# 60322-1030 - Added super debug output
# 60418-0947 - Added lead filter per campaign
# 60509-1416 - Rewrite of local_call_time functions
# 60511-1150 - Added inserts into vicidial_campaign_stats table
# 60609-1451 - Added ability to filter by DNC list vicidial_dnc
# 60614-1159 - Added campaign lead recycling ability
# 60715-2251 - Changed to use /etc/astguiclient.conf for configs
# 60801-1634 - Fixed Callback activation bug 000008
# 60814-1720 - Added option for no logging to file
# 60822-1527 - Added campaign_stats and logging options for adaptive dialing
# 60925-1330 - Fixed recycling leads issues
# 61110-1513 - Changed Xth NEW to fill to hopper_level with standard if not enough NEW
# 70219-1247 - Changed to use dial_statuses field instead of dial_status_x fields
# 71029-1929 - Added 5th and 6th NEW to list order
# 71030-2043 - Added hopper priority for callbacks
# 80112-0221 - Added 2nd, 3rd,... NEW for LAST NAME/PHONE Sort
# 80125-0821 - Added detail logging of each lead inserted
# 80713-0028 - Changed Recycling methodology
# 80909-1901 - Added support for campaign-specific DNC lists
# 90430-0117 - Added last call time and random sorting options
# 90430-1022 - Changed this script to allow for List Mix ability
# 90601-2111 - Added allow_inactive_list_leads to allow for inactive lists while in List Mix mode
# 90603-1157 - Fixed rare bug in list mix where statuses field do not end with -
# 90608-1201 - Added Drop Lockout Time Campaign setting option
# 90723-0842 - Added no hopper dial option to clear hopper leads
# 90809-0347 - Quick fix for null list_id loading when no active campaign lists
# 90904-1612 - Added timezone ordering
# 90907-2132 - Fixed order issues
# 91020-0054 - Fixed Auto-alt-dial DNC issues
# 91026-1207 - Added AREACODE DNC option
# 100409-1101 - Fix for rare dial-time duplicate hopper load issue
# 100427-0429 - Fix for list mix no-status-selected issue
# 100529-0843 - Changed dialable leads to calculate every run for active campaigns
# 100706-2332 - Added ability to purge only one campaign's leads in the hopper
# 101108-1451 - Added ability for the hopper level to be set automatically and remove excess leads from the hopper (MikeC)
# 110103-1118 - Added lead_order_randomize option
# 110212-2255 - Added scheduled callback custom statuses capability
# 110214-2319 - Added lead_order_secondary option
# 111006-1416 - Added call_count_limit option
# 120109-1510 - Fixed list mix bug
# 120210-1735 - Added vendor_lead_code duplication check per campaign option 
# 120402-2211 - Fixed call count limit bug
# 121124-2052 - Added List Expiration Date and Other Campaign DNC options
# 121205-1621 - Added parentheses around filter SQL when in SQL queries
# 121223-1540 - Fix for issue #627 preventing issues when filter is deleted, DomeDan
# 130219-1501 - Fixed issue with other campaign dnc
# 130510-0904 - Added state call time holidays functionality
# 131008-1518 - Changed campaign flag to allow multiple campaigns
#               Changed and added several CLI flags, including -t to --test, added --version, --count-only
#               Added code to restrict all functions if campaign flag is used
# 140612-2124 - Fixed date issue with wrong variable #772
# 150111-1546 - Added lists option: local call time and enabled whole-campaign outbound call time holidays, Issue #812
# 150114-1204 - Optimization of gmt code, Issue #812
# 150117-1415 - Added list local call time validation
# 150312-1459 - Allow for single quotes in data fields without crashing
# 150717-1050 - Added force index to some vicidial_list queries, set with $VLforce_index variable
# 150728-1050 - Added option for secondary sorting by vendor_lead_code, Issue #833
# 150908-1544 - Added debug output for vendor_lead_code duplicate rejections count
# 170531-0837 - Fixed issue #1019
# 180111-1559 - Added anyone_callback_inactive_lists option
# 180301-1453 - Fix to allow for commented(#) lines in filters
# 180419-1109 - Fix for list mix to use call count limit on initial count, issue #1094
# 180924-1734 - Added callback_dnc campaign option
# 190213-1207 - Added additional $VLforce_index flags, for high-volume dialing systems
# 190524-1228 - Fix for lead filters with 'NONE' in the filter ID
# 190703-1650 - Allow for single-quotes in state field
# 200814-2132 - Added support for International DNC scrubbing
# 201111-1359 - Added support for hopper_drop_run_trigger
# 201122-1039 - Added support for daily call count limits
# 201220-1032 - Changes for shared agent campaigns
# 210405-1008 - Added hopper_drop_run_trigger=A option
# 210407-1704 - Modified the Hopper Drop-Run process to modify the priority and source of DROPs already in the hopper
# 210713-1317 - Added call_limit_24hour feature support
# 210718-0343 - Fixes for 24-Hour Call Count Limits with standard Auto-Alt-Dialing
# 210719-1519 - Added additional state override methods for call_limit_24hour
# 220822-0938 - Change DNC check queries to put phone_number in double-quotes instead of single-quotes
# 230428-2017 - Added demographic_quotas code
# 231116-0821 - Added hopper_hold_inserts system and campaign settings options
# 231126-1748 - Added RQUEUE hopper status
# 231129-1051 - Added daily_phone_number_call_limit campaign setting
# 240225-0954 - Added AUTONEXT hopper_hold_inserts campaign option
#

# constants
$build = '240225-0954';
$script='AST_VDhopper';
$DB=0;  # Debug flag, set to 0 for no debug messages. Can be overriden with CLI --debug flag
$US='__';
$MT[0]='';
#$vicidial_hopper='TEST_vicidial_hopper';	# for testing only
$vicidial_hopper='vicidial_hopper';
$count_only=0;
$run_check=0;

# options
$insert_auto_CB_to_hopper	= 1; # set to 1 to automatically insert ANYONE callbacks into the hopper, default = 1
$VLforce_index = 'FORCE INDEX(list_id)'; # to disable, set to ''

### gather date and time
$secT = time();
$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
$wtoday = $wday;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$file_date = "$year-$mon-$mday";
$now_date = "$year-$mon-$mday $hour:$min:$sec";
$VDL_date = "$year-$mon-$mday 00:00:01";
$YMD = "$year-$mon-$mday";

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

### get date-time of one minute ago ###
$VDL_one = ($secX - (1 * 60));
($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_one);
$Vyear = ($Vyear + 1900);
$Vmon++;
if ($Vmon < 10) {$Vmon = "0$Vmon";}
if ($Vmday < 10) {$Vmday = "0$Vmday";}
$VDL_one = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

### get date-time of 10 seconds ago ###
$VDL_tensec = ($secX - 10);
($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_tensec);
$Vyear = ($Vyear + 1900);
$Vmon++;
if ($Vmon < 10) {$Vmon = "0$Vmon";}
if ($Vmday < 10) {$Vmday = "0$Vmday";}
$VDL_tensec = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

### begin parsing CLI run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	$allow_inactive_list_leads=0;
		while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--help/i)
		{
		print "allowed run time options(must stay in this order):\n";
		print "  [--test] = test\n";
		print "  [--help] = this screen\n";
		print "  [--version] = print version of this script, then exit\n";
		print "  [--count-only] = only display the number of leads in the hopper, then exit\n";
		print "  [--run-check] = concurrency check, exit if already running\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--dbgmt] = show GMT offset of records as they are inserted into hopper\n";
		print "  [--dbdetail] = additional level of logging the leads that are inserted into the hopper\n";
		print "  [--allow-inactive-list-leads] = do not delete inactive list leads\n";
		print "  [--level=XXX] = force a hopper_level of XXX\n";
		print "  [--campaign=XXX] = run for campaign XXX only(or more campaigns if separated by triple dash ---)\n";
		print "  [--wipe-hopper-clean] = deletes everything from the hopper    USE WITH CAUTION!!!\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--version/i)
			{
			print "version: $build\n";
			exit;
			}
		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
			$CLIcampaign = $data_in[1];
			$CLIcampaign =~ s/ .*$//gi;
			if ($CLIcampaign =~ /---/)
				{
				$CLIcampaign =~ s/---/','/gi;
				}
			}
		else
			{$CLIcampaign = '';}
		if ($args =~ /--level=/i)
			{
			@data_in = split(/--level=/,$args);
			$CLIlevel = $data_in[1];
			$CLIlevel =~ s/ .*$//gi;
			$CLIlevel =~ s/\D//gi;
			print "\n-----HOPPER LEVEL OVERRIDE: $CLIlevel -----\n\n";
			}
		else
			{$CLIlevel = '';}
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
		if ($args =~ /--dbgmt/i)
			{
			$DB_show_offset=1;
			print "\n-----DEBUG GMT -----\n\n";
			}
		if ($args =~ /--dbdetail/i)
			{
			$DB_detail=1;
	#		print "\n-----DEBUG DETAIL -----\n\n";
			}
		if ($args =~ /--allow-inactive-list-leads/i)
			{
			$allow_inactive_list_leads=1;
	#		print "\n-----DEBUG DETAIL -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n-----TESTING -----\n\n";
			}
		if ($args =~ /--wipe-hopper-clean/i)
			{
			$wipe_hopper_clean=1;
			}
		if ($args =~ /--count-only/i)
			{
			$count_only=1;
			}
		if ($args =~ /--run-check/i)
			{
			$run_check=1;
			if ($DB) {print "\n----- CONCURRENCY CHECK -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}

### concurrency check (hopper runs should be unique)
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 1)
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		$event_string = "I am not alone! Another $0 is running! Exiting...";
		&event_logger;
		exit 1;
		}
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

if (!$VDHLOGfile) {$VDHLOGfile = "$PATHlogs/hopper.$year-$mon-$mday";}
if (!$VDHDLOGfile) {$VDHDLOGfile = "$PATHlogs/hopper-detail.$year-$mon-$mday";}
if (!$AADLOGfile) {$AADLOGfile = "$PATHlogs/auto-alt-dial.$year-$mon-$mday";}
if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### Grab system_settings values from the database
$anyone_callback_inactive_lists='default';
$stmtA = "SELECT anyone_callback_inactive_lists,enable_international_dncs,daily_call_count_limit,use_non_latin,call_limit_24hour,UNIX_TIMESTAMP(call_limit_24hour_reset),UNIX_TIMESTAMP(NOW()),hopper_hold_inserts FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$anyone_callback_inactive_lists =	$aryA[0];
	$enable_international_dncs =		$aryA[1];
	$SSdaily_call_count_limit =			$aryA[2];
	$non_latin = 						$aryA[3];
	$SScall_limit_24hour =				$aryA[4];
	$SScall_limit_24hour_reset =		$aryA[5];
	$SScall_limit_24hour_now =			$aryA[6];
	$SShopper_hold_inserts =			$aryA[7];
	}
$sthA->finish();

### Grab Server values from the database
$stmtA = "SELECT vd_server_logs,local_gmt FROM servers where server_ip = '$VARserver_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows==0) {die "Server IP $VARserver_ip does not have an entry in the servers table\n\n";}	
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DBvd_server_logs =			$aryA[0];
	$DBSERVER_GMT		=		$aryA[1];
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
	else {$SYSLOG = '0';}
	if (length($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
	}
$sthA->finish();

if ($non_latin > 0) 
	{
	$stmtA = "SET NAMES 'UTF8';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthA->finish();
	}

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug (campaign_id,server_ip) select campaign_id,'HOPPER' from vicidial_campaigns;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

### Grab "blocked" lists from settings container if international DNCs activated
if ($enable_international_dncs) 
	{
	$dnc_list_stmt="select container_entry from vicidial_settings_containers where container_id='DNC_CURRENT_BLOCKED_LISTS' and container_entry!=''";
	if ($DBX) {print "$dnc_list_stmt\n";}
	$dnc_list_rslt=$dbhA->prepare($dnc_list_stmt);
	$dnc_list_rslt->execute();
	if ($dnc_list_rslt->rows>0) 
		{
		$dnc_blocked_lists="";
		@dnc_list_row=$dnc_list_rslt->fetchrow_array;

		@blocked_dnc_lists=split(/\n/, $dnc_list_row[0]);
		for ($i=0; $i<scalar(@blocked_dnc_lists); $i++) 
			{
			@current_scrub=split(/\=\>/, $blocked_dnc_lists[$i]);
			$dnc_list_id=$current_scrub[0];
			$dnc_list_id=~s/[^0-9]//g;
			$dnc_blocked_lists.="'$dnc_list_id', ";
			}
		$dnc_blocked_lists=~s/, $//;
		}
	if ($DBX) {print "BLOCKED LISTS: $dnc_blocked_lists\n";}
	if (length($dnc_blocked_lists)>0) 
		{
		$dnc_blocked_lists_SQL="and list_id not in ($dnc_blocked_lists)";
		}
	$dnc_list_rslt->finish;
	}

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

if ($DB) {print "TIME DEBUG: $LOCAL_GMT_OFF_STD|$LOCAL_GMT_OFF|$isdst|   GMT: $hour:$min\n";}

### Wipe hopper clean process
if ($wipe_hopper_clean)
	{
	if (length($CLIcampaign)>0)
		{
		$stmtA = "DELETE from $vicidial_hopper where campaign_id IN('$CLIcampaign');";
		}
	else
		{
		$stmtA = "DELETE from $vicidial_hopper;";
		}
	$affected_rows = $dbhA->do($stmtA);
	if ($DB) {print "Hopper Wiped Clean:  $affected_rows\n";}
	$event_string = "|HOPPER WIPE CLEAN|";
	&event_logger;

	exit;
	}

### Count only process
if ($count_only)
	{
	if (length($CLIcampaign)>0)
		{
		$stmtA = "SELECT count(*) from $vicidial_hopper where campaign_id IN('$CLIcampaign');";
		}
	else
		{
		$stmtA = "SELECT count(*) from $vicidial_hopper;";
		}
	$hopper_count_only=0;
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$hopper_count_only = $aryA[0];
		}
	$sthA->finish();
	if ($DB) 
		{print "Hopper count: $hopper_count_only|$stmtA|\n";}
	else 
		{print "Hopper count: $hopper_count_only\n";}
	$event_string = "|HOPPER COUNT: $hopper_count_only|";
	&event_logger;

	exit;
	}

### Delete leads from inactive lists if there are any
if (length($CLIcampaign)>0)
	{
	$stmtA = "SELECT list_id FROM vicidial_lists where ( ( (active='N') or ( (active='Y') and (expiration_date < \"$file_date\") ) ) and (campaign_id IN('$CLIcampaign') ) );";
	}
else
	{
	$stmtA = "SELECT list_id FROM vicidial_lists where ( (active='N') or ( (active='Y') and (expiration_date < \"$file_date\") ) );";
	}
if ($DB) {print $stmtA;}
$inactive_lists='';
$inactive_lists_count=0;
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
while ($sthArows > $inactive_lists_count)
	{
	@aryA = $sthA->fetchrow_array;
	$inactive_list = $aryA[0];
	$inactive_lists .= "'$inactive_list',";
	$inactive_lists_count++;
	}
$sthA->finish();
if ($DB) {print "Inactive Lists:  $inactive_lists_count\n";}

if ($inactive_lists_count > 0)
	{
	chop($inactive_lists);
	if ($allow_inactive_list_leads < 1)
		{
		$KEEPanyone_callback_inactive_listsSQL='';
		if ($anyone_callback_inactive_lists =~ /KEEP_IN_HOPPER/i) 
			{$KEEPanyone_callback_inactive_listsSQL = "and source!='C'";}
		$stmtA = "DELETE from $vicidial_hopper where list_id IN($inactive_lists) $KEEPanyone_callback_inactive_listsSQL;";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "Inactive and Expired List Leads Deleted:  $affected_rows |$stmtA|\n";}
			$event_string = "|INACTIVE LIST DEL|$affected_rows|";
			&event_logger;
		}
	}


##### BEGIN Change CBHOLD status leads to CALLBK if their vicidial_callbacks time has passed
$vcNOanyone_callback_inactive_listsSQL='';
$vlNOanyone_callback_inactive_listsSQL='';
if ($anyone_callback_inactive_lists =~ /NO_ADD_TO_HOPPER/i) 
	{
	$vcNOanyone_callback_inactive_listsSQL = "and vicidial_callbacks.list_id NOT IN($inactive_lists)";
	$vlNOanyone_callback_inactive_listsSQL = "and vicidial_list.list_id NOT IN($inactive_lists)";
	}

if (length($CLIcampaign)>0)
	{
	$stmtA = "SELECT count(*) FROM vicidial_callbacks where callback_time <= '$now_date' and status IN('ACTIVE','TFHCCL') and campaign_id IN('$CLIcampaign') $vcNOanyone_callback_inactive_listsSQL;";
	}
else
	{
	$stmtA = "SELECT count(*) FROM vicidial_callbacks where callback_time <= '$now_date' and status IN('ACTIVE','TFHCCL') $vcNOanyone_callback_inactive_listsSQL;";
	}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
@aryA = $sthA->fetchrow_array;
$CBHOLD_count = $aryA[0];
if ($DB) {print "CALLBACK HOLD: $CBHOLD_count|$stmtA|\n";}
$sthA->finish();

if ($CBHOLD_count > 0)
	{
	$update_leads='';
	$cbc=0;
	$cba=0;
	if (length($CLIcampaign)>0)
		{
		$stmtA = "SELECT vicidial_callbacks.lead_id,recipient,campaign_id,vicidial_callbacks.list_id,gmt_offset_now,state,vicidial_callbacks.lead_status,vendor_lead_code,phone_number,phone_code,postal_code FROM vicidial_callbacks,vicidial_list where callback_time <= '$now_date' and vicidial_callbacks.status IN('ACTIVE','TFHCCL') and vicidial_callbacks.lead_id=vicidial_list.lead_id and vicidial_callbacks.campaign_id IN('$CLIcampaign') $vlNOanyone_callback_inactive_listsSQL;";
		}
	else
		{
		$stmtA = "SELECT vicidial_callbacks.lead_id,recipient,campaign_id,vicidial_callbacks.list_id,gmt_offset_now,state,vicidial_callbacks.lead_status,vendor_lead_code,phone_number,phone_code,postal_code FROM vicidial_callbacks,vicidial_list where callback_time <= '$now_date' and vicidial_callbacks.status IN('ACTIVE','TFHCCL') and vicidial_callbacks.lead_id=vicidial_list.lead_id $vlNOanyone_callback_inactive_listsSQL;";
		}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $cbc)
		{
		@aryA = $sthA->fetchrow_array;
		$lead_ids[$cbc] = $aryA[0];
		$recipient = $aryA[1];
		$update_leads .= "'$lead_ids[$cbc]',";
		if ($recipient =~ /ANYONE/)
			{
			$CA_lead_id[$cba] =				$aryA[0];
			$CA_campaign_id[$cba] =			$aryA[2];
			$CA_list_id[$cba] =				$aryA[3];
			$CA_gmt_offset_now[$cba] =		$aryA[4];
			$CA_state[$cba] =				$aryA[5];
			$CA_status[$cba] =				$aryA[6];
			$CA_vendor_lead_code[$cba] =	$aryA[7];
			$CA_phone_number[$cba] =		$aryA[8];
			$CA_phone_code[$cba] =			$aryA[9];
			$CA_postal_code[$cba] =			$aryA[10];
			$cba++;
			}
		$cbc++;
		}
	$sthA->finish();
	if ($cbc > 0)
		{
		chop($update_leads);

		$stmtA = "UPDATE vicidial_callbacks set status='LIVE' where lead_id IN($update_leads) and status NOT IN('INACTIVE','DEAD','ARCHIVE');";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "Scheduled Callbacks Activated:  $affected_rows\n";}
		$event_string = "|CALLBACKS CB ACT |$affected_rows|";
		&event_logger;
		}
	### INSERT ANYONE CALLBACKS INTO HOPPER DIRECTLY ###
	if ( ($cba > 0) && ($insert_auto_CB_to_hopper) )
		{
		if ($DB) {print "ANYONE Scheduled Callbacks to Insert into hopper:  $cba\n";}
		$event_string = "|ANYONE CB HOPPER |$cba|";
		&event_logger;
		$CAu=0;
		foreach(@CA_lead_id)
			{
			$DNClead=0;
			$DNCC=0;
			$DNCL=0;

			### look up callback DNC settings for campaign
			$stmtA = "SELECT callback_dnc,use_internal_dnc,use_campaign_dnc,use_other_campaign_dnc,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,hopper_hold_inserts FROM vicidial_campaigns where campaign_id='$CA_campaign_id[$CAu]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VD_callback_dnc =					$aryA[0];
				$VD_use_internal_dnc =				$aryA[1];
				$VD_use_campaign_dnc =				$aryA[2];
				$VD_use_other_campaign_dnc =		$aryA[3];
				$VD_call_limit_24hour_method =		$aryA[4];
				$VD_call_limit_24hour_scope =		$aryA[5];
				$VD_call_limit_24hour =				$aryA[6];
				$VD_call_limit_24hour_override =	$aryA[7];
				$VD_hopper_hold_inserts =			$aryA[8];
				if ($SShopper_hold_inserts < 1) {$VD_hopper_hold_inserts = 'DISABLED';}
				}
			$sthA->finish();

			if ($VD_callback_dnc =~ /ENABLED/i) 
				{
				$VD_phone_number=$CA_phone_number[$CAu];

				if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
					{
					if ($VD_use_internal_dnc =~ /AREACODE/)
						{
						$pth_areacode = substr($VD_phone_number, 0, 3);
						$pth_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_phone_number','$pth_areacode');";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number=\"$VD_phone_number\";";}
					if ($DB) {print "     Doing DNC Check: $VD_phone_number - $VD_use_internal_dnc\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$DNClead =		 $aryA[0];
						}
					$sthA->finish();
					if ($DNClead != '0')
						{
						$DNCL++;
						$stmtA = "UPDATE vicidial_list SET status='DNCL' where lead_id='$CA_lead_id[$CAu]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "Flagging DNC lead:     $affected_rows  $VD_phone_number\n";}

						$stmtA = "UPDATE vicidial_callbacks SET status='DEAD' where lead_id='$CA_lead_id[$CAu]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "Setting Callback entry to DEAD:     $affected_rows  $CA_lead_id[$CAu]\n";}
						}
					}
				if ( ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) ) && ($DNClead == '0') )
					{
					$temp_campaign_id = $CA_campaign_id[$CAu];
					if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc}
					if ($VD_use_campaign_dnc =~ /AREACODE/)
						{
						$pth_areacode = substr($VD_phone_number, 0, 3);
						$pth_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_phone_number','$pth_areacode') and campaign_id='$temp_campaign_id';";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number=\"$VD_phone_number\" and campaign_id='$temp_campaign_id';";}
					if ($DBX) {print "$VD_use_other_campaign_dnc|$stmtA\n";}
					if ($DB) {print "Doing CAMP DNC Check: $VD_phone_number - $VD_use_campaign_dnc - $temp_campaign_id\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$DNClead =	($DNClead + $aryA[0]);
						}
					$sthA->finish();
					if ($aryA[0] != '0')
						{
						$DNCC++;
						$stmtA = "UPDATE vicidial_list SET status='DNCC' where lead_id='$CA_lead_id[$CAu]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "Flagging DNC lead:     $affected_rows  $VD_phone_number $CA_campaign_id[$CAu]\n";}

						$stmtA = "UPDATE vicidial_callbacks SET status='DEAD' where lead_id='$CA_lead_id[$CAu]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DBX) {print "Setting Callback entry to DEAD:     $affected_rows  $CA_lead_id[$CAu]\n";}
						}
					}
				}

			if ($DNClead < 1) 
				{
				$passed_24hour_call_count=1;
				if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
					{
					$temp_24hour_phone =				$CA_phone_number[$CAu];
					$temp_24hour_phone_code =			$CA_phone_code[$CAu];
					$temp_24hour_state =				$CA_state[$CAu];
					$temp_24hour_postal_code =			$CA_postal_code[$CAu];
					$TEMPlead_id =						$CA_lead_id[$CAu];
					$TEMPcampaign_id =					$CA_campaign_id[$CAu];
					$TEMPcall_limit_24hour_method =		$VD_call_limit_24hour_method;
					$TEMPcall_limit_24hour_scope =		$VD_call_limit_24hour_scope;
					$TEMPcall_limit_24hour =			$VD_call_limit_24hour;
					$TEMPcall_limit_24hour_override	=	$VD_call_limit_24hour_override;
					$TFHCCLalt=0;
					if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$TEMPcall_limit_24hour_method|$TEMPlead_id|\n";}
					&check_24hour_call_count;
					}
				if ($passed_24hour_call_count > 0) 
					{
					$stmtA = "UPDATE vicidial_list set status='$CA_status[$CAu]', called_since_last_reset='N' where lead_id='$CA_lead_id[$CAu]';";
					$affected_rows = $dbhA->do($stmtA);
					if ($DB) {print "Scheduled Callbacks Activated:  $affected_rows\n";}
					$event_string = "|CALLBACKS LISTACT|$affected_rows|";
					&event_logger;

					$hopper_statusSQL='';
					if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_statusSQL = ",status='RHOLD'";}
					$stmtA = "INSERT INTO $vicidial_hopper SET lead_id='$CA_lead_id[$CAu]',campaign_id='$CA_campaign_id[$CAu]',list_id='$CA_list_id[$CAu]',gmt_offset_now='$CA_gmt_offset_now[$CAu]',user='',state=\"$CA_state[$CAu]\",priority='50',source='C',vendor_lead_code=\"$CA_vendor_lead_code[$CAu]\"$hopper_statusSQL;";
					$affected_rows = $dbhA->do($stmtA);
					if ($DB) {print "ANYONE Scheduled Callback Inserted into hopper:  $affected_rows|$CA_lead_id[$CAu]\n";}
					}
				else
					{
					$stmtA = "UPDATE vicidial_callbacks SET status='TFHCCL' where lead_id='$CA_lead_id[$CAu]';";
					$affected_rows = $dbhA->do($stmtA);

					if ($DB) {print "ANYONE Scheduled Callback NOT Inserted into hopper:  24-Hour deferred|$affected_rows|$passed_24hour_call_count|$CA_lead_id[$CAu]\n";}
					}
				}
			$CAu++;
			}
		}
	}
##### END Change CBHOLD status leads to CALLBK if their vicidial_callbacks time has passed


##### BEGIN Auto-Alt-Dial DNC check and update or delete
### Find out how many leads in the hopper are set to DNC status
$hopper_dnc_count=0;
if (length($CLIcampaign)>0)
	{
	$stmtA = "SELECT count(*) from $vicidial_hopper where status='DNC' and campaign_id IN('$CLIcampaign');";
	}
else
	{
	$stmtA = "SELECT count(*) from $vicidial_hopper where status='DNC';";
	}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$hopper_dnc_count = $aryA[0];
	if ($DB) {print "     hopper DNC count:   $hopper_dnc_count\n";}
	if ($DBX) {print "     |$stmtA|\n";}
	}
$sthA->finish();
if ($hopper_dnc_count > 0)
	{
	$event_string = "|hopper DNC count: $hopper_dnc_count||";
	&event_logger;

	### Gather all DNC statused vicidial_hopper entries
	if (length($CLIcampaign)>0)
		{
		$stmtA = "SELECT hopper_id,lead_id,alt_dial,campaign_id FROM $vicidial_hopper where status='DNC' and campaign_id IN('$CLIcampaign');";
		}
	else
		{
		$stmtA = "SELECT hopper_id,lead_id,alt_dial,campaign_id FROM $vicidial_hopper where status='DNC';";
		}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsVHdnc=$sthA->rows;
	$aad=0;
	while ($sthArowsVHdnc > $aad)
		{
		@aryA = $sthA->fetchrow_array;
		$AAD_hopper_id[$aad] =		$aryA[0];
		$AAD_lead_id[$aad] =		$aryA[1];
		$AAD_alt_dial[$aad] =		$aryA[2];
		$AAD_campaign_id[$aad] =	$aryA[3];
		$aad++;
		}
	$sthA->finish();

	### Go through all DNC statused vicidial_hopper entries and look for next number. If found, set the next phone number, alt dial and change status to READY
	$aad=0;
	while ($sthArowsVHdnc > $aad)
		{
		$VD_auto_alt_dial='';
		$VD_use_internal_dnc='';
		$VD_use_campaign_dnc='';
		$VD_use_other_campaign_dnc='';
		$alt_skip_reason='';   $addr3_skip_reason='';
		$VD_alt_dial =		$AAD_alt_dial[$aad];
		$VD_campaign_id =	$AAD_campaign_id[$aad];
		$VD_lead_id =		$AAD_lead_id[$aad];
		### look up auto-alt-dial settings for campaign
		$stmtA = "SELECT auto_alt_dial,use_internal_dnc,use_campaign_dnc,use_other_campaign_dnc,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,hopper_hold_inserts FROM vicidial_campaigns where campaign_id='$AAD_campaign_id[$aad]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VD_auto_alt_dial =					$aryA[0];
			$VD_use_internal_dnc =				$aryA[1];
			$VD_use_campaign_dnc =				$aryA[2];
			$VD_use_other_campaign_dnc =		$aryA[3];
			$VD_call_limit_24hour_method =		$aryA[4];
			$VD_call_limit_24hour_scope =		$aryA[5];
			$VD_call_limit_24hour =				$aryA[6];
			$VD_call_limit_24hour_override =	$aryA[7];
			$VD_hopper_hold_inserts =			$aryA[8];
			if ($SShopper_hold_inserts < 1) {$VD_hopper_hold_inserts = 'DISABLED';}
			}
		$sthA->finish();

		$event_string = "|DNC record: $AAD_lead_id[$aad]|$AAD_campaign_id[$aad]|$VD_auto_alt_dial|$AAD_alt_dial[$aad]";
		&event_logger;

		if ( ($VD_auto_alt_dial =~ /(ALT_ONLY|ALT_AND_ADDR3|ALT_AND_EXTENDED)/) && ($VD_alt_dial =~ /NONE|MAIN/) )
			{
			$alt_dial_skip=0;
			$VD_alt_phone='';
			$stmtA="SELECT alt_phone,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$AAD_lead_id[$aad]';";
				if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VD_alt_phone =			$aryA[0];
				$VD_alt_phone =~ s/\D//gi;
				$VD_gmt_offset_now =	$aryA[1];
				$VD_state =				$aryA[2];
				$VD_list_id =			$aryA[3];
				$VD_phone_code =		$aryA[4];
				$VD_postal_code =		$aryA[5];
				}
			$sthA->finish();
			if (length($VD_alt_phone)>5)
				{
				if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
					{
					if ($VD_use_internal_dnc =~ /AREACODE/)
						{
						$alt_areacode = substr($VD_alt_phone, 0, 3);
						$alt_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_alt_phone','$alt_areacode');";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number=\"$VD_alt_phone\";";}
						if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VD_alt_dnc_count =	$aryA[0];
						}
					$sthA->finish();
					}
				else {$VD_alt_dnc_count=0;}
				if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
					{
					$temp_VD_campaign_id = $VD_campaign_id;
					if (length($VD_use_other_campaign_dnc) > 0) {$temp_VD_campaign_id = $VD_use_other_campaign_dnc;}
					if ($VD_use_campaign_dnc =~ /AREACODE/)
						{
						$alt_areacode = substr($VD_alt_phone, 0, 3);
						$alt_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_alt_phone','$alt_areacode') and campaign_id='$temp_VD_campaign_id';";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number=\"$VD_alt_phone\" and campaign_id='$temp_VD_campaign_id';";}
						if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
						}
					$sthA->finish();
					}
				if ($VD_alt_dnc_count < 1)
					{
					$passed_24hour_call_count=1;
					if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
						{
						$temp_24hour_phone =				$VD_alt_phone;
						$temp_24hour_phone_code =			$VD_phone_code;
						$temp_24hour_state =				$VD_state;
						$temp_24hour_postal_code =			$VD_postal_code;
						$TEMPlead_id =						$AAD_lead_id[$aad];
						$TEMPcampaign_id =					$AAD_campaign_id[$aad];
						$TEMPcall_limit_24hour_method =		$VD_call_limit_24hour_method;
						$TEMPcall_limit_24hour_scope =		$VD_call_limit_24hour_scope;
						$TEMPcall_limit_24hour =			$VD_call_limit_24hour;
						$TEMPcall_limit_24hour_override	=	$VD_call_limit_24hour_override;
						$TFHCCLalt=1;
						if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$TEMPcall_limit_24hour_method|$TEMPlead_id|\n";}
						&check_24hour_call_count;
						}
					if ($passed_24hour_call_count > 0) 
						{
						$hopper_status='READY';
						if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
						$stmtA = "UPDATE $vicidial_hopper SET status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ALT',user='',priority='25' where hopper_id='$AAD_hopper_id[$aad]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DB) {$event_string = "--    VDH record updated: |$affected_rows|   |$stmtA|";   &event_logger;}
						$aad_string = "$AAD_lead_id[$aad]|$VD_alt_phone|$AAD_campaign_id[$aad]|ALT|25|hopper insert|";   &aad_output;
						}
					else
						{$alt_dial_skip=1;   $alt_skip_reason='24-hour call count limit failed';}
					}
				else
					{$alt_dial_skip=1;   $alt_skip_reason='DNC check failed';}
				}
			else
				{$alt_dial_skip=1;   $alt_skip_reason='ALT phone invalid';}
			if ($alt_dial_skip > 0)
				{
				$VD_alt_dial='ALT';
				$aad_string = "$AAD_lead_id[$aad]|$VD_alt_phone|$AAD_campaign_id[$aad]|ALT|0|hopper skip|$alt_skip_reason|";   &aad_output;
				}
			}
		if ( ( ($VD_auto_alt_dial =~ /(ADDR3_ONLY)/) && ($VD_alt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /(ALT_AND_ADDR3)/) && ($VD_alt_dial =~ /ALT/) ) )
			{
			$addr3_dial_skip=0;
			$VD_address3='';
			$stmtA="SELECT address3,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$AAD_lead_id[$aad]';";
				if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VD_address3 =			$aryA[0];
				$VD_address3 =~ s/\D//gi;
				$VD_gmt_offset_now =	$aryA[1];
				$VD_state =				$aryA[2];
				$VD_list_id =			$aryA[3];
				$VD_phone_code =		$aryA[4];
				$VD_postal_code =		$aryA[5];
				}
			$sthA->finish();
			if (length($VD_address3)>5)
				{
				if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
					{
					if ($VD_use_internal_dnc =~ /AREACODE/)
						{
						$addr3_areacode = substr($VD_address3, 0, 3);
						$addr3_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_address3','$addr3_areacode');";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number=\"$VD_address3\";";}
						if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VD_alt_dnc_count =	$aryA[0];
						}
					$sthA->finish();
					}
				else {$VD_alt_dnc_count=0;}
				if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
					{
					$temp_VD_campaign_id = $VD_campaign_id;
					if (length($VD_use_other_campaign_dnc) > 0) {$temp_VD_campaign_id = $VD_use_other_campaign_dnc;}
					if ($VD_use_campaign_dnc =~ /AREACODE/)
						{
						$addr3_areacode = substr($VD_address3, 0, 3);
						$addr3_areacode .= "XXXXXXX";
						$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_address3','$addr3_areacode') and campaign_id='$temp_VD_campaign_id';";
						}
					else
						{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number=\"$VD_address3\" and campaign_id='$temp_VD_campaign_id';";}
						if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
						}
					$sthA->finish();
					}
				if ($VD_alt_dnc_count < 1)
					{
					$passed_24hour_call_count=1;
					if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
						{
						$temp_24hour_phone =				$VD_address3;
						$temp_24hour_phone_code =			$VD_phone_code;
						$temp_24hour_state =				$VD_state;
						$temp_24hour_postal_code =			$VD_postal_code;
						$TEMPlead_id =						$AAD_lead_id[$aad];
						$TEMPcampaign_id =					$VD_campaign_id;
						$TEMPcall_limit_24hour_method =		$VD_call_limit_24hour_method;
						$TEMPcall_limit_24hour_scope =		$VD_call_limit_24hour_scope;
						$TEMPcall_limit_24hour =			$VD_call_limit_24hour;
						$TEMPcall_limit_24hour_override	=	$VD_call_limit_24hour_override;
						$TFHCCLalt=1;
						if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$TEMPcall_limit_24hour_method|$TEMPlead_id|\n";}
						&check_24hour_call_count;
						}
					if ($passed_24hour_call_count > 0) 
						{
						$hopper_status='READY';
						if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
						$stmtA = "UPDATE $vicidial_hopper SET status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ADDR3',user='',priority='20' where hopper_id='$AAD_hopper_id[$aad]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DB) {$event_string = "--    VDH record updated: |$affected_rows|   |$stmtA|";   &event_logger;}
						$aad_string = "$AAD_lead_id[$aad]|$VD_address3|$AAD_campaign_id[$aad]|ADDR3|20|hopper insert|";   &aad_output;
						}
					else
						{$addr3_dial_skip=1;   $addr3_skip_reason='24-hour call count limit failed';}
					}
				else
					{$addr3_dial_skip=1;   $addr3_skip_reason='DNC check failed';}
				}
			else
				{$addr3_dial_skip=1;   $addr3_skip_reason='ADDR3 phone invalid';}
			if ($addr3_dial_skip > 0)
				{
				$VD_alt_dial='ADDR3';
				$aad_string = "$AAD_lead_id[$aad]|$VD_address3|$AAD_campaign_id[$aad]|ADDR3|0|hopper skip|$addr3_skip_reason|";   &aad_output;
				}
			}
		if ( ( ($VD_auto_alt_dial =~ /(EXTENDED_ONLY)/) && ($VD_alt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /(ALT_AND_EXTENDED)/) && ($VD_alt_dial =~ /ALT/) ) || ( ($VD_auto_alt_dial =~ /ADDR3_AND_EXTENDED|ALT_AND_ADDR3_AND_EXTENDED/) && ($VD_alt_dial =~ /ADDR3/) ) || ( ($VD_auto_alt_dial =~ /(EXTENDED)/) && ($VD_alt_dial =~ /X/) && ($VD_alt_dial !~ /XLAST/) ) )
			{
			if ($VD_alt_dial =~ /ADDR3/) {$Xlast=0;}
			else
				{$Xlast = $VD_alt_dial;}
			$Xlast =~ s/\D//gi;
			if (length($Xlast)<1)
				{$Xlast=0;}
			$VD_altdialx='';
			$stmtA="SELECT gmt_offset_now,state,list_id,postal_code FROM vicidial_list where lead_id='$VD_lead_id';";
				if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VD_gmt_offset_now =	$aryA[0];
				$VD_state =				$aryA[1];
				$VD_list_id =			$aryA[2];
				$VD_postal_code =		$aryA[3];
				}
			$sthA->finish();
			$alt_dial_phones_count=0;
			$stmtA="SELECT count(*) FROM vicidial_list_alt_phones where lead_id='$AAD_lead_id[$aad]';";
				if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$alt_dial_phones_count = $aryA[0];
				}
			$sthA->finish();

			if ($alt_dial_phones_count <= $Xlast) 
				{
				$stmtA = "DELETE FROM $vicidial_hopper where hopper_id='$AAD_hopper_id[$aad]';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DB) {$event_string = "--    VDH record DNC deleted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;}
				}
				
			while ( ($alt_dial_phones_count > 0) && ($alt_dial_phones_count > $Xlast) )
				{
				$Xlast++;
				$stmtA="SELECT alt_phone_id,phone_number,active,phone_code FROM vicidial_list_alt_phones where lead_id='$VD_lead_id' and alt_phone_count='$Xlast';";
					if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$VD_altdial_id =			$aryA[0];
					$VD_altdial_phone = 		$aryA[1];
					$VD_altdial_active = 		$aryA[2];
					$VD_altdial_phone_code = 	$aryA[3];
					}
				else
					{$Xlast=9999999999;}
				$sthA->finish();

				if ($VD_altdial_active =~ /Y/)
					{
					if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
						{
						if ($VD_use_internal_dnc =~ /AREACODE/)
							{
							$ad_areacode = substr($VD_altdial_phone, 0, 3);
							$ad_areacode .= "XXXXXXX";
							$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_altdial_phone','$ad_areacode');";
							}
						else
							{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number=\"$VD_altdial_phone\";";}
							if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VD_alt_dnc_count =	$aryA[0];
							}
						$sthA->finish();
						}
					else {$VD_alt_dnc_count=0;}
					if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
						{
						$temp_VD_campaign_id = $VD_campaign_id;
						if (length($VD_use_other_campaign_dnc) > 0) {$temp_VD_campaign_id = $VD_use_other_campaign_dnc;}
						if ($VD_use_campaign_dnc =~ /AREACODE/)
							{
							$ad_areacode = substr($VD_altdial_phone, 0, 3);
							$ad_areacode .= "XXXXXXX";
							$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_altdial_phone','$ad_areacode') and campaign_id='$temp_VD_campaign_id';";
							}
						else
							{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number=\"$VD_altdial_phone\" and campaign_id='$temp_VD_campaign_id';";}
							if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
							}
						$sthA->finish();
						}
					if ($VD_alt_dnc_count < 1)
						{
						if ($alt_dial_phones_count eq '$Xlast') 
							{$Xlast = 'LAST';}
						$passed_24hour_call_count=1;
						if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
							{
							$temp_24hour_phone =				$VD_altdial_phone;
							$temp_24hour_phone_code =			$VD_altdial_phone_code;
							$temp_24hour_state =				$VD_state;
							$temp_24hour_postal_code =			$VD_postal_code;
							$TEMPlead_id =						$AAD_lead_id[$aad];
							$TEMPcampaign_id =					$VD_campaign_id;
							$TEMPcall_limit_24hour_method =		$VD_call_limit_24hour_method;
							$TEMPcall_limit_24hour_scope =		$VD_call_limit_24hour_scope;
							$TEMPcall_limit_24hour =			$VD_call_limit_24hour;
							$TEMPcall_limit_24hour_override	=	$VD_call_limit_24hour_override;
							$TFHCCLalt=1;
							if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$TEMPcall_limit_24hour_method|$TEMPlead_id|\n";}
							&check_24hour_call_count;
							}
						if ($passed_24hour_call_count > 0) 
							{
							$hopper_status='READY';
							if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
							$stmtA = "UPDATE $vicidial_hopper SET status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15' where hopper_id='$AAD_hopper_id[$aad]';";
							$affected_rows = $dbhA->do($stmtA);
							if ($DB) {$event_string = "--    VDH record updated: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;}
							$aad_string = "$AAD_lead_id[$aad]|$VD_altdial_phone|$AAD_campaign_id[$aad]|X$Xlast|15|hopper insert|";   &aad_output;
							$Xlast=9999999999;
							$DNC_hopper_trigger=0;
							}
						else
							{$DNC_hopper_trigger=1;}
						}
					else
						{$DNC_hopper_trigger=1;}
					if ($DNC_hopper_trigger > 0)
						{
						if ($alt_dial_phones_count eq '$Xlast') 
							{$Xlast = 'LAST';}
						$stmtA = "UPDATE $vicidial_hopper SET status='DNC',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15' where hopper_id='$AAD_hopper_id[$aad]';";
						$affected_rows = $dbhA->do($stmtA);
						if ($DB) {$event_string = "--    VDH record DNC updated: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;}
						$Xlast=9999999999;
						if ($DB) {$event_string = "--    VDH alt dial is DNC|X$Xlast|$VD_altdial_phone|";   &event_logger;}
						$aad_string = "$AAD_lead_id[$aad]|$VD_altdial_phone|$AAD_campaign_id[$aad]|DNC|15|hopper insert|";   &aad_output;
						}
					}
				}
			}
		if ($VD_alt_dial =~ /XLAST/)
			{
			$stmtA = "DELETE FROM $vicidial_hopper where hopper_id='$AAD_hopper_id[$aad]';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DB) {$event_string = "--    VDH record DNC deleted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;}
			}

		$aad++;
		}
	}
##### END Auto-Alt-Dial DNC check and update or delete


### BEGIN reset 24-Hour Call Count Limit skipped leads(skipped over 1 hour ago)
if ($SScall_limit_24hour > 0)
	{
	$TEMPtime_since_reset = ($SScall_limit_24hour_now - $SScall_limit_24hour_reset);
	if ($DBX) 
		{
		$event_string = "Checking for 24-Hour Call Limit skipped leads reset: |$TEMPtime_since_reset = ($SScall_limit_24hour_now - $SScall_limit_24hour_reset)|";
		print "$event_string\n";
		&event_logger;
		}

	if ($TEMPtime_since_reset > 3600) 
		{
		$stmtA = "UPDATE vicidial_list SET called_since_last_reset='N' where called_since_last_reset='D' and (modify_date <= (NOW() - INTERVAL 1 HOUR));";
		$affected_rows = $dbhA->do($stmtA);

		$stmtB = "UPDATE system_settings SET call_limit_24hour_reset=NOW();";
		$affected_rowsB = $dbhA->do($stmtB);

		if ($DB) {$event_string = "24-Hour Call Limit skipped leads reset: |$affected_rows|$affected_rowsB|   |$stmtA|$stmtB|";   print "$event_string\n";   &event_logger;}
		}
	}
### END reset 24-Hour Call Count Limit skipped leads(skipped over 1 hour ago)


##### BEGIN check for active campaigns that need the hopper run for them
@campaign_id=@MT; 
$ANY_hopper_vlc_dup_check='N';

if (length($CLIcampaign)>1)
	{
	$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,lead_filter_id,use_internal_dnc,dial_method,available_only_ratio_tally,adaptive_dropped_percentage,adaptive_maximum_level,dial_statuses,list_order_mix,use_campaign_dnc,drop_lockout_time,no_hopper_dialing,auto_alt_dial_statuses,dial_timeout,auto_hopper_multi,use_auto_hopper,auto_trim_hopper,lead_order_randomize,lead_order_secondary,call_count_limit,hopper_vlc_dup_check,use_other_campaign_dnc,callback_dnc,hopper_drop_run_trigger,daily_call_count_limit,daily_limit_manual,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,demographic_quotas,demographic_quotas_container,hopper_hold_inserts,daily_phone_number_call_limit from vicidial_campaigns where campaign_id IN('$CLIcampaign');";
	}
else
	{
	$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,lead_filter_id,use_internal_dnc,dial_method,available_only_ratio_tally,adaptive_dropped_percentage,adaptive_maximum_level,dial_statuses,list_order_mix,use_campaign_dnc,drop_lockout_time,no_hopper_dialing,auto_alt_dial_statuses,dial_timeout,auto_hopper_multi,use_auto_hopper,auto_trim_hopper,lead_order_randomize,lead_order_secondary,call_count_limit,hopper_vlc_dup_check,use_other_campaign_dnc,callback_dnc,hopper_drop_run_trigger,daily_call_count_limit,daily_limit_manual,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,demographic_quotas,demographic_quotas_container,hopper_hold_inserts,daily_phone_number_call_limit from vicidial_campaigns where active='Y';";
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
		{$hopper_level[$rec_count] = "$CLIlevel";}
	$auto_dial_level[$rec_count] =				$aryA[3];
	$local_call_time[$rec_count] =				$aryA[4];
	$lead_filter_id[$rec_count] =				$aryA[5];
	$use_internal_dnc[$rec_count] =				$aryA[6];
	$dial_method[$rec_count] =					$aryA[7];
	$available_only_ratio_tally[$rec_count] =	$aryA[8];
	$adaptive_dropped_percentage[$rec_count] =	$aryA[9];
	$adaptive_maximum_level[$rec_count] =		$aryA[10];
	$dial_statuses[$rec_count] =				$aryA[11];
	$list_order_mix[$rec_count] =				$aryA[12];
	$use_campaign_dnc[$rec_count] =				$aryA[13];
	$drop_lockout_time[$rec_count] =			$aryA[14];
	$no_hopper_dialing[$rec_count] = 			$aryA[15];
	$auto_alt_dial_statuses[$rec_count] = 		$aryA[16];
	$dial_timeout[$rec_count] = 				$aryA[17];
	$auto_hopper_multi[$rec_count] =			$aryA[18];
	$use_auto_hopper[$rec_count] = 				$aryA[19];
	$auto_trim_hopper[$rec_count] =				$aryA[20];
	$lead_order_randomize[$rec_count] =			$aryA[21];
	$lead_order_secondary[$rec_count] =			$aryA[22];
	$call_count_limit[$rec_count] =				$aryA[23];
	$hopper_vlc_dup_check[$rec_count] =			$aryA[24];
	$use_other_campaign_dnc[$rec_count] =		$aryA[25];
	$callback_dnc[$rec_count] =					$aryA[26];
	$hopper_drop_run_trigger[$rec_count] =		$aryA[27];
	$daily_call_count_limit[$rec_count] =		$aryA[28];
	$daily_limit_manual[$rec_count] =			$aryA[29];
	$call_limit_24hour_method[$rec_count] =		$aryA[30];
	$call_limit_24hour_scope[$rec_count] =		$aryA[31];
	$call_limit_24hour[$rec_count] =			$aryA[32];
	$call_limit_24hour_override[$rec_count] =	$aryA[33];
	$demographic_quotas[$rec_count] =			$aryA[34];
	$demographic_quotas_container[$rec_count] =	$aryA[35];
	$demographic_quotasSQL[$rec_count] =		'';
	$hopper_hold_inserts[$rec_count] =			$aryA[36];
	if ($SShopper_hold_inserts < 1) {$hopper_hold_inserts[$rec_count] = 'DISABLED';}
	$daily_phone_number_call_limit[$rec_count] =$aryA[37];

	if ( ($demographic_quotas[$rec_count] =~ /ENABLED|COMPLETE/) && ( (length($demographic_quotas_container[$rec_count]) > 0) && ($demographic_quotas_container[$rec_count] !~ /DISABLED/) ) ) 
		{$demographic_quotasSQL[$rec_count] = "and rank!='-9999'";}
	if ($hopper_vlc_dup_check[$rec_count] =~ /Y/)
		{$ANY_hopper_vlc_dup_check = 'Y';}

	### Auto Hopper Level
	if ( $use_auto_hopper[$rec_count] =~ /Y/) 
		{
		### Find the number of agents
		$stmtB = "SELECT COUNT(*) FROM vicidial_live_agents WHERE ( (campaign_id='$campaign_id[$rec_count]') or (dial_campaign_id='$campaign_id[$rec_count]') ) and status IN ('READY','QUEUE','INCALL','CLOSER') and last_update_time >= '$VDL_tensec'";
		$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhA->errstr;
		@aryAgent = $sthB->fetchrow_array;
		$num_agents = $aryAgent[0];
		$sthB->finish();

		$stmtB = "SELECT COUNT(*) FROM vicidial_live_agents WHERE ( (campaign_id='$campaign_id[$rec_count]') or (dial_campaign_id='$campaign_id[$rec_count]') ) and status IN ('PAUSED') and last_update_time >= '$VDL_tensec' and last_state_change >= '$VDL_one'";
		$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhA->errstr;
		@aryAgent = $sthB->fetchrow_array;
		$num_paused_agents = $aryAgent[0];
		$sthB->finish();

		### Make sure the auto hopper multiplier is not set to something stupid
		if ( $auto_hopper_multi[$rec_count] <= 0 ) { $auto_hopper_multi[$rec_count] = 0.1; }

		### Save for debug info
		$minimum_hopper_level = $hopper_level[$rec_count];

		### Number of calls per minute
		$dial_timeout_mult = 60 / $dial_timeout[$rec_count];

		$auto_hopper_level = roundup( $auto_hopper_multi[$rec_count] * ( $num_agents + $num_paused_agents ) * $auto_dial_level[$rec_count] * $dial_timeout_mult );	

		### Make sure we are greater than the minimum hopper level
		if ( $auto_hopper_level >= $minimum_hopper_level )
			{
			$hopper_level[$rec_count] = $auto_hopper_level
			}
		else
			{
			$hopper_level[$rec_count] = $minimum_hopper_level;
			}

		if ($DB) 
			{	
			print "---------------Auto Hopper Level Enabled For $campaign_id[$rec_count]---------------------\n";
			print "Number of Agents = $num_agents\n";
			print "Number of Paused Agents = $num_paused_agents\n";
			print "Auto Dial Level = $auto_dial_level[$rec_count]\n";
			print "Dial Timeout = $dial_timeout[$rec_count]\n";
			print "Dial Timeout Multiplier = $dial_timeout_mult\n";
			print "Auto Hopper Multipier = $auto_hopper_multi[$rec_count]\n";
			print "Minimum Hopper Level = $minimum_hopper_level\n";
			print "Auto Hopper Level Adjustment = $auto_hopper_level\n";
			print "Final Hopper Level = $hopper_level[$rec_count]\n\n";
			}

		$stmtB = "UPDATE vicidial_campaigns set auto_hopper_level = '$auto_hopper_level' where campaign_id='$campaign_id[$rec_count]'";
		$affected_rows = $dbhA->do($stmtB);
		}

	### Auto Trim Hopper code
	if ( $auto_trim_hopper[$rec_count] =~ /Y/) 
		{
		if ($DB) { print "---------------Auto Trim Hopper Enabled For $campaign_id[$rec_count]---------------------\n"; }
	
		$stmtB = "SELECT COUNT(*) FROM $vicidial_hopper WHERE campaign_id='$campaign_id[$rec_count]' and status IN ('READY','RHOLD','RQUEUE') and source IN('S','N');";
		$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhA->errstr;
		@aryLead = $sthB->fetchrow_array;
		$camp_leads = $aryLead[0];
		$sthB->finish();

		if ($DB) 
			{ 
			print "Leads in Hopper for this Campaign = $camp_leads\n";
			print "Hopper Level for this Campaign = $hopper_level[$rec_count]\n";
			}

		if ( $camp_leads > ( 2 * $hopper_level[$rec_count] ) ) 
			{
			$num_to_delete = $camp_leads - 2 * $hopper_level[$rec_count];
			$stmtB = "DELETE FROM $vicidial_hopper WHERE campaign_id='$campaign_id[$rec_count]' AND source='S' AND status IN ('READY','RHOLD','RQUEUE') LIMIT $num_to_delete";
			$affected_rows = $dbhA->do($stmtB);
			
			if ($DB) 
				{ 
				print "TOO MANY LEADS IN THE HOPPER ( $camp_leads > 2 * $hopper_level[$rec_count] ). \n"; 
				print "DELETING $num_to_delete LEADS FROM THE HOPPER.  |$affected_rows|\n";
				}
			}
		if ($DB) {print "\n";}
		}	
	if (length($use_other_campaign_dnc[$rec_count]) > 0) 
		{
		if ($DB) {print "OTHER CAMPAIGN DNC SELECTED: $use_other_campaign_dnc[$rec_count]\n";}
		}
	$rec_count++;
	}
$sthA->finish();
if ($DB) {print "CAMPAIGNS TO PROCESS HOPPER FOR:  $rec_count|$#campaign_id\n";}
##### END check for active campaigns that need the hopper run for them


##### BEGIN if vendor_lead_code duplicate check, grab the vlc of all vicidial_auto_calls and vicidial_live_agent sessions
if ($ANY_hopper_vlc_dup_check =~ /Y/) 
	{
	$live_leads='';
	$live_vlc='';
	$vacLIVE=0;
	$vlaLIVE=0;
	$vlLIVE=0;
	$stmtA = "SELECT lead_id FROM vicidial_auto_calls where lead_id NOT IN('0');";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $vacLIVE)
		{
		@aryA = $sthA->fetchrow_array;
		$live_leads .= "'$aryA[0]',";
		$vacLIVE++;
		}
	chop($live_leads);
	if (length($live_leads) < 2) {$live_leads = "''";}
	$sthA->finish();
	$stmtA = "SELECT lead_id FROM vicidial_live_agents where lead_id NOT IN('0',$live_leads);";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $vlaLIVE)
		{
		@aryA = $sthA->fetchrow_array;
		$live_leads .= ",'$aryA[0]'";
		$vlaLIVE++;
		}
	$sthA->finish();

	$stmtA = "SELECT vendor_lead_code FROM vicidial_list where lead_id IN($live_leads) and vendor_lead_code!='';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $vlLIVE)
		{
		@aryA = $sthA->fetchrow_array;
		if ( (length($aryA[0]) > 0) && ($aryA[0] !~ /^NULL/) )
			{$live_vlc .= "\"$aryA[0]\",";}
		$vlLIVE++;
		}
	$sthA->finish();

	if ($DB) {print "VLC Dup Check Live Calls: $vacLIVE|$vlaLIVE|$vlLIVE\n";}
	if ($DBX) {print "     $live_leads|$live_vlc\n";}
	}
##### END if vendor_lead_code duplicate check, grab the vlc of all vicidial_auto_calls and vicidial_live_agent sessions


##### LOOP THROUGH EACH CAMPAIGN AND PROCESS THE HOPPER #####
$i=0;
foreach(@campaign_id)
	{
	$DNCskip=0;
	$DCCLskip=0;
	$TFHCCLskip=0;
	$hopper_begin_output='';
	$insert_end_outputSQL='';
	if ($no_hopper_dialing[$i] =~ /Y/)
		{
		if ($DB) {print "Campaign $campaign_id[$i] set to no-hopper-dialing: $no_hopper_dialing[$i]\n";}
		$hopper_begin_output = "Campaign $campaign_id[$i] set to no-hopper-dialing: $no_hopper_dialing[$i]\n";

		### get count of no-hopper dialing hopper entries
		$stmtA = "SELECT count(*) FROM $vicidial_hopper where campaign_id='$campaign_id[$i]' and status NOT IN('QUEUE','HOLD');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		@aryA = $sthA->fetchrow_array;
		$NOHOPPER_count = $aryA[0];
		if ($DBX) {print "NO HOPPER COUNT: $NOHOPPER_count|$stmtA|\n";}
		$sthA->finish();

		if ($NOHOPPER_count > 0)
			{
			$stmtA = "DELETE from $vicidial_hopper where campaign_id='$campaign_id[$i]' and status NOT IN('QUEUE','HOLD');";
			$affected_rows = $dbhA->do($stmtA);
			if ($DB) {print "No Hopper delete:  $affected_rows\n";}
			$event_string = "|NO HOPPER DELETE|$affected_rows|";
			&event_logger;
			}
		}
	else
		{
		$vicidial_log = 'vicidial_log';
		$VCSdialable_leads[$i]=0;

		if ($list_order_mix[$i] =~ /DISABLED/)
			{
			$dial_statuses[$i] =~ s/ -$//gi;
			@Dstatuses = split(/ /,$dial_statuses[$i]);
			$Ds_to_print = (($#Dstatuses) + 0);
			$STATUSsql[$i]='';
			$o=0;
			while ($Ds_to_print > $o) 
				{
				$o++;
				$STATUSsql[$i] .= "'$Dstatuses[$o]',";
				}
			if (length($STATUSsql[$i])<3) {$STATUSsql[$i]="''";}
			else {chop($STATUSsql[$i]);}
			}

		### BEGIN - GATHER STATS FROM THE vicidial_campaign_stats TABLE ###
		$stmtA = "SELECT dialable_leads from vicidial_campaign_stats where campaign_id='$campaign_id[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$VCSdialable_leads[$i] = $aryA[0];
			$rec_count++;
			}
		$sthA->finish();
		### END - GATHER STATS FROM THE vicidial_campaign_stats TABLE ###

		if ($DB) {print "\nStarting hopper run for $campaign_id[$i] campaign- GMT: $local_call_time[$i]   HOPPER LEVEL: $hopper_level[$i]   ORDER: $lead_order[$i]|$lead_order_randomize[$i]|$lead_order_secondary[$i]\n";}

		$hopper_begin_output .= "Starting hopper run for $campaign_id[$i] campaign- GMT: $local_call_time[$i]   HOPPER LEVEL: $hopper_level[$i]   ORDER: $lead_order[$i]|$lead_order_randomize[$i]|$lead_order_secondary[$i]\n";

		##### BEGIN calculate what gmt_offset_now values are within the allowed local_call_time setting ###
		$g=0;
		$p='13';
		$GMT_gmt[0] = '';
		$GMT_hour[0] = '';
		$GMT_day[0] = '';
		if ($DBX) {print "\n   |GMT-DAY-HOUR|   ";}
		while ($p > -13)
			{
			$pzone = ($GMT_now + ($p * 3600));
			($psec,$pmin,$phour,$pmday,$pmon,$pyear,$pday,$pyday,$pisdst) = localtime($pzone);
			$phour=($phour * 100);
			$tz = sprintf("%.2f", $p);	
			$GMT_gmt[$g] = "$tz";
			$GMT_day[$g] = "$pday";
			$GMT_hour[$g] = ($phour + $pmin);
			$p = ($p - 0.25);
			if ($DBX) {print "|$GMT_gmt[$g]-$GMT_day[$g]-$GMT_hour[$g]|";}
			$g++;
			}
		if ($DBX) {print "\n";}
		
		$stmtA = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times,ct_holidays FROM vicidial_call_times where call_time_id='$local_call_time[$i]';";
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
				if ($DB) {print "     CALL TIME HOLIDAY FOUND!   $local_call_time[$i]|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";}
				$hopper_begin_output .= "     CALL TIME HOLIDAY FOUND!   $local_call_time[$i]|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";
				}
			$sthC->finish();
			}
		### END Check for outbound call time holiday ###

		### BEGIN For lead recycling find out the no-call gap time and begin dial time for today
		$lct_gap=0; # number of seconds from stopping of calling to starting of calling based on local call time
		$lct_begin=0; # hour and minute of the begin time for the local call time
		$lct_end=0; # hour and minute of the end time for the local call time

		$secYESTERDAY = ($secX - (24 * 3600));
			($ksec,$kmin,$khour,$kmday,$kmon,$kyear,$wyesterday,$kyday,$kisdst) = localtime($secYESTERDAY);

		if ($wtoday < 1)	#### Sunday local time
			{
			if (($Gct_sunday_start < 1) && ($Gct_sunday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_sunday_start;}
			}
		if ($wtoday==1)	#### Monday local time
			{
			if (($Gct_monday_start < 1) && ($Gct_monday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_monday_start;}
			}
		if ($wtoday==2)	#### Tuesday local time
			{
			if (($Gct_tuesday_start < 1) && ($Gct_tuesday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_tuesday_start;}
			}
		if ($wtoday==3)	#### Wednesday local time
			{
			if (($Gct_wednesday_start < 1) && ($Gct_wednesday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_wednesday_start;}
			}
		if ($wtoday==4)	#### Thursday local time
			{
			if (($Gct_thursday_start < 1) && ($Gct_thursday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_thursday_start;}
			}
		if ($wtoday==5)	#### Friday local time
			{
			if (($Gct_friday_start < 1) && ($Gct_friday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_friday_start;}
			}
		if ($wtoday==6)	#### Saturday local time
			{
			if (($Gct_saturday_start < 1) && ($Gct_saturday_stop < 1)) {$lct_begin = $Gct_default_start;}
			else {$lct_begin = $Gct_saturday_start;}
			}


		$dayBACKsec=0;
		$weekBACK=0;
		while ( ($lct_end < 1) && ($weekBACK <= 1) )
			{
			if ($wyesterday==6)	#### Saturday local time
				{
				if ($Gct_saturday_start > 2399) {$wyesterday = 0;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_saturday_start < 1) && ($Gct_saturday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_saturday_stop;}
					}
				}
			if ($wyesterday==5)	#### Friday local time
				{
				if ($Gct_friday_start > 2399) {$wyesterday = 1;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_friday_start < 1) && ($Gct_friday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_friday_stop;}
					}
				}
			if ($wyesterday==4)	#### Thursday local time
				{
				if ($Gct_thursday_start > 2399) {$wyesterday = 2;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_thursday_start < 1) && ($Gct_thursday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_thursday_stop;}
					}
				}
			if ($wyesterday==3)	#### Wednesday local time
				{
				if ($Gct_wednesday_start > 2399) {$wyesterday = 3;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_wednesday_start < 1) && ($Gct_wednesday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_wednesday_stop;}
					}
				}
			if ($wyesterday==2)	#### Tuesday local time
				{
				if ($Gct_tuesday_start > 2399) {$wyesterday = 4;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_tuesday_start < 1) && ($Gct_tuesday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_tuesday_stop;}
					}
				}
			if ($wyesterday==1)	#### Monday local time
				{
				if ($Gct_monday_start > 2399) {$wyesterday = 5;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_monday_start < 1) && ($Gct_monday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_monday_stop;}
					}
				}
			if ($wyesterday==0)	#### Sunday local time
				{
				if ($Gct_sunday_start > 2399) {$wyesterday = 6;   $dayBACKsec = ($dayBACKsec + 86400);   if ($DBX) {print "DayBACK: $wyesterday\n";}}
				else
					{
					if (($Gct_sunday_start < 1) && ($Gct_sunday_stop < 1)) {$lct_end = $Gct_default_stop;}
					else {$lct_end = $Gct_sunday_stop;}
					}
				}
			$weekBACK++;
			}

		$lct_end = sprintf("%04d", $lct_end);
		$lct_end_hour = substr($lct_end, 0, 2);
		$lct_end_min = substr($lct_end, 2, 2);
		$lct_begin = sprintf("%04d", $lct_begin);
		$lct_begin_hour = substr($lct_begin, 0, 2);
		$lct_begin_min = substr($lct_begin, 2, 2);

		$lct_gap = ( ( ( ( ( (24 - $lct_end_hour) + $lct_begin_hour) * 3600) + ($lct_begin_min * 60) ) - ($lct_end_min * 60) ) + $dayBACKsec);

		if ($DBX) {print "LocalCallTime No-Call Gap: |$lct_gap|$lct_end($lct_end_hour $lct_end_min)|$lct_begin($lct_begin_hour $lct_begin_min)|$wtoday|$wyesterday|\n";}

		### END For lead recycling find out the no-call gap time and begin dial time for today


		$ct_states = '';
		$ct_state_gmt_SQL = '';
		$del_state_gmt_SQL = '';
		$ct_srs=0;
		$b=0;
		if (length($Gct_state_call_times)>2)
			{
			@state_rules = split(/\|/,$Gct_state_call_times);
			$ct_srs = ($#state_rules - 0);
			}
		while($ct_srs >= $b)
			{
			if (length($state_rules[$b])>1)
				{
				if ($DBX) {print "    Processing state rule $state_rules[$b]|";}
				$stmtA = "SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop,ct_holidays from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
				if ($DBX) {print "   |$stmtA|\n";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$Gstate_call_time_id =		$aryA[0];
					$Gstate_call_time_state =	$aryA[1];
					$Gsct_default_start =		$aryA[4];
					$Gsct_default_stop =		$aryA[5];
					$Gsct_sunday_start =		$aryA[6];
					$Gsct_sunday_stop =			$aryA[7];
					$Gsct_monday_start =		$aryA[8];
					$Gsct_monday_stop =			$aryA[9];
					$Gsct_tuesday_start =		$aryA[10];
					$Gsct_tuesday_stop =		$aryA[11];
					$Gsct_wednesday_start =		$aryA[12];
					$Gsct_wednesday_stop =		$aryA[13];
					$Gsct_thursday_start =		$aryA[14];
					$Gsct_thursday_stop =		$aryA[15];
					$Gsct_friday_start =		$aryA[16];
					$Gsct_friday_stop =			$aryA[17];
					$Gsct_saturday_start =		$aryA[18];
					$Gsct_saturday_stop =		$aryA[19];
					$Sct_holidays = 			$aryA[20];
					$ct_states .="'$Gstate_call_time_state',";
					}
				$sthA->finish();

				### BEGIN Check for outbound state holiday ###
				$Sholiday_id = '';
				if ( (length($Sct_holidays)>2) || ( (length($holiday_id)>2) && (length($Sholiday_id)<2) ) ) 
					{
					#Apply state holiday
					if (length($Sct_holidays)>2)
						{
						$Sct_holidaysSQL = $Sct_holidays;
						$Sct_holidaysSQL =~ s/^\||\|$//gi;
						$Sct_holidaysSQL =~ s/\|/','/gi;
						$Sct_holidaysSQL = "'$Sct_holidaysSQL'";					
						$stmtA = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Sct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
						$holidaytype = "     STATE CALL TIME HOLIDAY FOUND!   ";
						}
					#Apply call time wide holiday
					elsif ( (length($holiday_id)>2) && (length($Sholiday_id)<2) )
						{
						$stmtA = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id='$holiday_id' and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
						$holidaytype = "     NO STATE HOLIDAY APPLYING CALL TIME HOLIDAY!   ";
						}				
					if ($DBX) {print "   |$stmtA|\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$Sholiday_id =				$aryA[0];
						$Sholiday_date =			$aryA[1];
						$Sholiday_name =			$aryA[2];
						if ( ($Gsct_default_start < $aryA[3]) && ($Gsct_default_stop > 0) )		{$Gsct_default_start = $aryA[3];}
						if ( ($Gsct_default_stop > $aryA[4]) && ($Gsct_default_stop > 0) )		{$Gsct_default_stop = $aryA[4];}
						if ( ($Gsct_sunday_start < $aryA[3]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_start = $aryA[3];}
						if ( ($Gsct_sunday_stop > $aryA[4]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_stop = $aryA[4];}
						if ( ($Gsct_monday_start < $aryA[3]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_start = $aryA[3];}
						if ( ($Gsct_monday_stop >	$aryA[4]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_stop =	$aryA[4];}
						if ( ($Gsct_tuesday_start < $aryA[3]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_start = $aryA[3];}
						if ( ($Gsct_tuesday_stop > $aryA[4]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_stop = $aryA[4];}
						if ( ($Gsct_wednesday_start < $aryA[3]) && ($Gsct_wednesday_stop > 0) ) {$Gsct_wednesday_start = $aryA[3];}
						if ( ($Gsct_wednesday_stop > $aryA[4]) && ($Gsct_wednesday_stop > 0) )	{$Gsct_wednesday_stop = $aryA[4];}
						if ( ($Gsct_thursday_start < $aryA[3]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_start = $aryA[3];}
						if ( ($Gsct_thursday_stop > $aryA[4]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_stop = $aryA[4];}
						if ( ($Gsct_friday_start < $aryA[3]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_start = $aryA[3];}
						if ( ($Gsct_friday_stop > $aryA[4]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_stop = $aryA[4];}
						if ( ($Gsct_saturday_start < $aryA[3]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_start = $aryA[3];}
						if ( ($Gsct_saturday_stop > $aryA[4]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_stop = $aryA[4];}
						if ($DB) {print "$holidaytype|$Gstate_call_time_id|$Gstate_call_time_state|$Sholiday_id|$Sholiday_date|$Sholiday_name|$Gsct_default_start|$Gsct_default_stop|\n";}
						}
					$sthA->finish();
					}
				### END Check for outbound state holiday ###

				$r=0;
				$state_gmt='';
				while($r < $g)
					{
					if ($GMT_day[$r]==0)	#### Sunday local time
						{
						if (($Gsct_sunday_start==0) && ($Gsct_sunday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_sunday_start) && ($GMT_hour[$r]<$Gsct_sunday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==1)	#### Monday local time
						{
						if (($Gsct_monday_start==0) && ($Gsct_monday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_monday_start) && ($GMT_hour[$r]<$Gsct_monday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==2)	#### Tuesday local time
						{
						if (($Gsct_tuesday_start==0) && ($Gsct_tuesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) && ($GMT_hour[$r]<$Gsct_tuesday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==3)	#### Wednesday local time
						{
						if (($Gsct_wednesday_start==0) && ($Gsct_wednesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) && ($GMT_hour[$r]<$Gsct_wednesday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==4)	#### Thursday local time
						{
						if (($Gsct_thursday_start==0) && ($Gsct_thursday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_thursday_start) && ($GMT_hour[$r]<$Gsct_thursday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==5)	#### Friday local time
						{
						if (($Gsct_friday_start==0) && ($Gsct_friday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_friday_start) && ($GMT_hour[$r]<$Gsct_friday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==6)	#### Saturday local time
						{
						if (($Gsct_saturday_start==0) && ($Gsct_saturday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gsct_saturday_start) && ($GMT_hour[$r]<$Gsct_saturday_stop) )
								{$state_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					$r++;
					}
				$state_gmt = "$state_gmt'99'";
				$ct_state_gmt_SQL .= "or (state='$Gstate_call_time_state' && gmt_offset_now IN($state_gmt)) ";
				$del_state_gmt_SQL .= "or (state='$Gstate_call_time_state' && gmt_offset_now NOT IN($state_gmt)) ";
				}

			$b++;
			}
		if (length($ct_states)>2)
			{
			$ct_states =~ s/,$//gi;
			$ct_statesSQL = "and state NOT IN($ct_states)";
			}
		else
			{
			$ct_statesSQL = "";
			}

		$r=0;
		@default_gmt_ARY=@MT;
		$dgA=0;
		$default_gmt='';
		while($r < $g)
			{
			if ($GMT_day[$r]==0)	#### Sunday local time
				{
				if (($Gct_sunday_start==0) && ($Gct_sunday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_sunday_start) && ($GMT_hour[$r]<$Gct_sunday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==1)	#### Monday local time
				{
				if (($Gct_monday_start==0) && ($Gct_monday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_monday_start) && ($GMT_hour[$r]<$Gct_monday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==2)	#### Tuesday local time
				{
				if (($Gct_tuesday_start==0) && ($Gct_tuesday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_tuesday_start) && ($GMT_hour[$r]<$Gct_tuesday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==3)	#### Wednesday local time
				{
				if (($Gct_wednesday_start==0) && ($Gct_wednesday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_wednesday_start) && ($GMT_hour[$r]<$Gct_wednesday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==4)	#### Thursday local time
				{
				if (($Gct_thursday_start==0) && ($Gct_thursday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_thursday_start) && ($GMT_hour[$r]<$Gct_thursday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==5)	#### Friday local time
				{
				if (($Gct_friday_start==0) && ($Gct_friday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_friday_start) && ($GMT_hour[$r]<$Gct_friday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			if ($GMT_day[$r]==6)	#### Saturday local time
				{
				if (($Gct_saturday_start==0) && ($Gct_saturday_stop==0))
					{
					if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				else
					{
					if ( ($GMT_hour[$r]>=$Gct_saturday_start) && ($GMT_hour[$r]<$Gct_saturday_stop) )
						{$default_gmt.="'$GMT_gmt[$r]',";   $default_gmt_ARY[$dgA] = "$GMT_gmt[$r]";   $dgA++;}
					}
				}
			$r++;
			}

		$default_gmt = "$default_gmt'99'";
		$all_gmtSQL[$i] = "(gmt_offset_now IN($default_gmt) $ct_statesSQL) $ct_state_gmt_SQL";
		$del_gmtSQL[$i] = "(gmt_offset_now NOT IN($default_gmt) $ct_statesSQL) $del_state_gmt_SQL";

		##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###

		##### BEGIN lead recycling parsing and prep ###

		$stmtA = "SELECT recycle_id,campaign_id,status,attempt_delay,attempt_maximum,active from vicidial_lead_recycle where campaign_id='$campaign_id[$i]' and active='Y';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		@recycle_status=@MT;
		@recycle_delay=@MT;
		@recycle_maximum=@MT;
		@RSQLdate=@MT;
		$r_ct=0;
		$rec_ct[$i]=0;
		while ($sthArows > $r_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$recycle_status[$r_ct] =	 $aryA[2];
			$recycle_delay[$r_ct] =		 $aryA[3];
			$recycle_maximum[$r_ct] =	 $aryA[4];
			$r_ct++;
			$rec_ct[$i]++;
			}
		$sthA->finish();

		if ($rec_ct[$i] > 0)
			{
			$rc=0;
			$recycle_SQL[$i] = "( ";
			while($rc < $rec_ct[$i])
				{
				$Y=1;
				$recycle_Y = "'Y'";
				while ($Y < $recycle_maximum[$rc])
					{
					$recycle_Y .= ",'Y$Y'";
					$Y++;
					}

				if ($rc > 0) {$recycle_SQL[$i] .= " or ";}

				$recycle_SQL[$i] .= "( (called_since_last_reset IN($recycle_Y)) and (status='$recycle_status[$rc]') and (";

				$dgA=0;
				foreach(@default_gmt_ARY)
					{
					$secX = time();
					$LLCT_DATE_offset = ($LOCAL_GMT_OFF - $default_gmt_ARY[$dgA]);
					$LLCT_DATE_offset_epoch = ( $secX - ($LLCT_DATE_offset * 3600) );
					$Rtarget = ($LLCT_DATE_offset_epoch - $recycle_delay[$rc]);
					($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($Rtarget);
					$Ryear = ($Ryear + 1900);
					$Rmon++;
					if ($Rmon < 10) {$Rmon = "0$Rmon";}
					if ($Rmday < 10) {$Rmday = "0$Rmday";}
					if ($Rhour < 10) {$Rhour = "0$Rhour";}
					if ($Rmin < 10) {$Rmin = "0$Rmin";}
					if ($Rsec < 10) {$Rsec = "0$Rsec";}
					$Rhourmin = "$Rhour$Rmin";
					if ( ($Rhourmin < $lct_begin) || ($Rhourmin > $lct_end) ) 
						{
						$RGtarget = ($Rtarget - $lct_gap);
						($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($RGtarget);
						$Ryear = ($Ryear + 1900);
						$Rmon++;
						if ($Rmon < 10) {$Rmon = "0$Rmon";}
						if ($Rmday < 10) {$Rmday = "0$Rmday";}
						if ($Rhour < 10) {$Rhour = "0$Rhour";}
						if ($Rmin < 10) {$Rmin = "0$Rmin";}
						if ($Rsec < 10) {$Rsec = "0$Rsec";}
						if ($DBX) {print "RECYCLE DELAY GAP: |$campaign_id[$i]|$Rhourmin|$RGtarget|Rtarget|($recycle_delay[$rc] $lct_gap)\n";}
						}
					$RSQLdate[$rc] = "$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

					if ($dgA > 0) {$recycle_SQL[$i] .= " or ";}

					$recycle_SQL[$i] .= "( (gmt_offset_now='$default_gmt_ARY[$dgA]') and (last_local_call_time < \"$RSQLdate[$rc]\") )";

					$dgA++;
					}
				if ($DBX) {print "RECYCLE: |$campaign_id[$i]|$recycle_status[$rc]|$recycle_delay[$rc]|$recycle_maximum[$rc]|$RSQLdate[$rc]|\n";}

				$recycle_SQL[$i] .= " ) )";

				$rc++;
				}

			$recycle_SQL[$i] .= " )";

			if ($DBX) {print "RECYCLE SQL: |$recycle_SQL[$i]|\n";}
			}
		##### END lead recycling parsing and prep ###


		### Find out how many leads are READY in the hopper from a specific campaign
		$hopper_ready_count=0;
		$stmtA = "SELECT count(*) from $vicidial_hopper where campaign_id='$campaign_id[$i]' and status IN('READY','RHOLD','RQUEUE');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$hopper_ready_count = $aryA[0];
			if ($DB) {print "     hopper READY count:   $hopper_ready_count\n";}
			if ($DBX) {print "     |$stmtA|\n";}
			}
		$sthA->finish();
		$event_string = "|$campaign_id[$i]|$hopper_level[$i]|$hopper_ready_count|$local_call_time[$i]||";
		&event_logger;

		$hopper_begin_output .= "     Hopper Ready count: $hopper_ready_count     Local Call Time: $local_call_time[$i] \n";


		### Get list of the lists in the campaign ###
		$stmtY = "SELECT list_id,active FROM vicidial_lists where campaign_id='$campaign_id[$i]' and expiration_date >= \"$file_date\";";
		$sthY = $dbhA->prepare($stmtY) or die "preparing: ",$dbhA->errstr;
		$sthY->execute or die "executing: $stmtY", $dbhA->errstr;
		$sthYrows=$sthY->rows;
		# Total list for campaign active & inactive
		$rec_countLISTS=0;
		# Total Active campaigns for the list
		$act_rec_countLISTS=0;
		# list active status
		$list_id_act='';
		# set hash for individual list sql
		my %indv_list_gmt_sql = ();
		while ($sthYrows > $rec_countLISTS)
			{
			# Start getting list GMT and putting together statements for filtering GMT
			@aryY = $sthY->fetchrow_array;		
			# Set List ID Variable
			$cur_list_id = $aryY[0];
			if ($DB) {print "   Processing GMT for list $cur_list_id\n";}
			# Set active list variable
			$list_id_act = $aryY[1];
			# Allow for inactive leads
			if ( ($list_order_mix[$i] !~ /DISABLED/) && ($allow_inactive_list_leads > 0) )
				{$allow_inactive = "Y";}
			# Pull the call times for the lists
			$stmtB = "SELECT local_call_time FROM vicidial_lists where list_id = \"$cur_list_id\"";
			$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
			$sthB->execute or die "executing: $stmtB", $dbhA->errstr;
			@aryB = $sthB->fetchrow_array;
			$cur_call_time = $aryB[0];
			
			# check that call time exists
			if ($cur_call_time !~ /^campaign$/) 
				{
				$stmtB = "SELECT count(*) from vicidial_call_times where call_time_id = \"$cur_call_time\"";
				$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
				$sthB->execute or die "executing: $stmtB", $dbhA->errstr;
				@aryB = $sthB->fetchrow_array;
				$call_time_exists = $aryB[0];
				if ($call_time_exists < 1) 
					{$cur_call_time = 'campaign';}
				}
			
			$sthB->finish();
			# Handle Passing through and skip GMT code for speed if we don't need it
			if ($cur_call_time eq "campaign")
				{
				# only create sql for pulling leads if the list is active
				if ( ($list_id_act eq "Y") || ($allow_inactive eq "Y") )
					{
					if ( ( ($act_rec_countLISTS == 0) && ($allow_inactive ne "Y") ) || ( ($rec_countLISTS == 0) && ($allow_inactive eq "Y") ) ) 
						{
						$list_id_sql[$i] = "(list_id IN('$cur_list_id'";
						}
					else 
						{
						if (length($list_id_sql[$i]) < 3) 
							{
							$list_id_sql[$i] = "(list_id IN('$cur_list_id'";
							}
						else
							{
							$list_id_sql[$i] .= ",'$cur_list_id'";
							}
						}
					$act_rec_countLISTS++;
					}
				# set variable for List Mix
				$indv_list_gmt_sql{ "$cur_list_id" } = "(list_id=\"$cur_list_id\")";
				}
			else
				{
				##### BEGIN calculate what gmt_offset_now values are within the allowed list local_call_time setting ###
				$stmtC = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times,ct_holidays FROM vicidial_call_times where call_time_id='$cur_call_time';";
				if ($DBX) {print "   |$stmtC|\n";}
				$sthC = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
				$sthC->execute or die "executing: $stmtC ", $dbhA->errstr;
				$sthCrows=$sthC->rows;
				$rec_count=0;
				if ($sthCrows > 0)
					{
					@aryC = $sthC->fetchrow_array;
					$Gct_default_start =	$aryC[3];
					$Gct_default_stop =		$aryC[4];
					$Gct_sunday_start =		$aryC[5];
					$Gct_sunday_stop =		$aryC[6];
					$Gct_monday_start =		$aryC[7];
					$Gct_monday_stop =		$aryC[8];
					$Gct_tuesday_start =	$aryC[9];
					$Gct_tuesday_stop =		$aryC[10];
					$Gct_wednesday_start =	$aryC[11];
					$Gct_wednesday_stop =	$aryC[12];
					$Gct_thursday_start =	$aryC[13];
					$Gct_thursday_stop =	$aryC[14];
					$Gct_friday_start =		$aryC[15];
					$Gct_friday_stop =		$aryC[16];
					$Gct_saturday_start =	$aryC[17];
					$Gct_saturday_stop =	$aryC[18];
					$Gct_state_call_times = $aryC[19];
					$Gct_holidays =			$aryC[20];
					$rec_count++;
					}
				$sthC->finish();
				
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
						if ($DB) {print "     LIST CALL TIME HOLIDAY FOUND!   $cur_call_time|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";}
						}
					$sthC->finish();
					}
				### END Check for outbound call time holiday ###
				$ct_states = '';
				$ct_state_gmt_SQL = ''; 
				$list_state_gmt_SQL = '';
				$ct_srs=0;
				$b=0;
				if (length($Gct_state_call_times)>2)
					{
					@state_rules = split(/\|/,$Gct_state_call_times);
					$ct_srs = ($#state_rules - 0);
					}
				while($ct_srs >= $b)
					{
					if (length($state_rules[$b])>1)
						{
						if ($DBX) {print "    Processing state rule $state_rules[$b] | ";}
						$stmtC = "SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop,ct_holidays from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
						if ($DBX) {print "   |$stmtC|\n";}
						$sthC = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
						$sthC->execute or die "executing: $stmtC ", $dbhA->errstr;
						$sthCrows=$sthC->rows;
						if ($sthCrows > 0)
							{
							@aryC = $sthC->fetchrow_array;
							$Gstate_call_time_id =		$aryC[0];
							$Gstate_call_time_state =	$aryC[1];
							$Gsct_default_start =		$aryC[4];
							$Gsct_default_stop =		$aryC[5];
							$Gsct_sunday_start =		$aryC[6];
							$Gsct_sunday_stop =			$aryC[7];
							$Gsct_monday_start =		$aryC[8];
							$Gsct_monday_stop =			$aryC[9];
							$Gsct_tuesday_start =		$aryC[10];
							$Gsct_tuesday_stop =		$aryC[11];
							$Gsct_wednesday_start =		$aryC[12];
							$Gsct_wednesday_stop =		$aryC[13];
							$Gsct_thursday_start =		$aryC[14];
							$Gsct_thursday_stop =		$aryC[15];
							$Gsct_friday_start =		$aryC[16];
							$Gsct_friday_stop =			$aryC[17];
							$Gsct_saturday_start =		$aryC[18];
							$Gsct_saturday_stop =		$aryC[19];
							$Sct_holidays = 			$aryC[20];
							$ct_states .="'$Gstate_call_time_state',";
							}
		
						$sthC->finish();
	
						### BEGIN Check for outbound state holiday ###
						$Sholiday_id = '';
						if ( (length($Sct_holidays)>2) || ((length($holiday_id)>2) && (length($Sholiday_id)<2))) 
							{
							# Apply state holiday
							if (length($Sct_holidays)>2)
								{
								$Sct_holidaysSQL = $Sct_holidays;
								$Sct_holidaysSQL =~ s/^\||\|$//gi;
								$Sct_holidaysSQL =~ s/\|/','/gi;
								$Sct_holidaysSQL = "'$Sct_holidaysSQL'";					
								$stmtA = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Sct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
								$holidaytype = "     STATE CALL TIME HOLIDAY FOUND!   ";
								}
							# Apply call time wide holiday
							elsif ( (length($holiday_id)>2) && (length($Sholiday_id)<2) )
								{
								$stmtA = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id='$holiday_id' and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
								$holidaytype = "     NO STATE HOLIDAY APPLYING CALL TIME HOLIDAY!   ";
								}				
							if ($DBX) {print "   |$stmtA|\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$Sholiday_id =				$aryA[0];
								$Sholiday_date =			$aryA[1];
								$Sholiday_name =			$aryA[2];
								if ( ($Gsct_default_start < $aryC[3]) && ($Gsct_default_stop > 0) )		{$Gsct_default_start = $aryC[3];}
								if ( ($Gsct_default_stop > $aryC[4]) && ($Gsct_default_stop > 0) )		{$Gsct_default_stop = $aryC[4];}
								if ( ($Gsct_sunday_start < $aryC[3]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_start = $aryC[3];}
								if ( ($Gsct_sunday_stop > $aryC[4]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_stop = $aryC[4];}
								if ( ($Gsct_monday_start < $aryC[3]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_start = $aryC[3];}
								if ( ($Gsct_monday_stop >	$aryC[4]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_stop =	$aryC[4];}
								if ( ($Gsct_tuesday_start < $aryC[3]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_start = $aryC[3];}
								if ( ($Gsct_tuesday_stop > $aryC[4]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_stop = $aryC[4];}
								if ( ($Gsct_wednesday_start < $aryC[3]) && ($Gsct_wednesday_stop > 0) ) {$Gsct_wednesday_start = $aryC[3];}
								if ( ($Gsct_wednesday_stop > $aryC[4]) && ($Gsct_wednesday_stop > 0) )	{$Gsct_wednesday_stop = $aryC[4];}
								if ( ($Gsct_thursday_start < $aryC[3]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_start = $aryC[3];}
								if ( ($Gsct_thursday_stop > $aryC[4]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_stop = $aryC[4];}
								if ( ($Gsct_friday_start < $aryC[3]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_start = $aryC[3];}
								if ( ($Gsct_friday_stop > $aryC[4]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_stop = $aryC[4];}
								if ( ($Gsct_saturday_start < $aryC[3]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_start = $aryC[3];}
								if ( ($Gsct_saturday_stop > $aryC[4]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_stop = $aryC[4];}
								if ($DB) {print "$holidaytype|$Gstate_call_time_id|$Gstate_call_time_state|$Sholiday_id|$Sholiday_date|$Sholiday_name|$Gsct_default_start|$Gsct_default_stop|\n";}
								}
							$sthA->finish();
							}
						### END Check for outbound state holiday ###
						$r=0;
						$state_gmt='';
						while($r < $g)
							{
							if ($GMT_day[$r]==0)	#### Sunday local time
								{
								if (($Gsct_sunday_start==0) && ($Gsct_sunday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_sunday_start) && ($GMT_hour[$r]<$Gsct_sunday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==1)	#### Monday local time
								{
								if (($Gsct_monday_start==0) && ($Gsct_monday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_monday_start) && ($GMT_hour[$r]<$Gsct_monday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==2)	#### Tuesday local time
								{
								if (($Gsct_tuesday_start==0) && ($Gsct_tuesday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) && ($GMT_hour[$r]<$Gsct_tuesday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==3)	#### Wednesday local time
								{
								if (($Gsct_wednesday_start==0) && ($Gsct_wednesday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) && ($GMT_hour[$r]<$Gsct_wednesday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==4)	#### Thursday local time
								{
								if (($Gsct_thursday_start==0) && ($Gsct_thursday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_thursday_start) && ($GMT_hour[$r]<$Gsct_thursday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==5)	#### Friday local time
								{
								if (($Gsct_friday_start==0) && ($Gsct_friday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_friday_start) && ($GMT_hour[$r]<$Gsct_friday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==6)	#### Saturday local time
								{
								if (($Gsct_saturday_start==0) && ($Gsct_saturday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_saturday_start) && ($GMT_hour[$r]<$Gsct_saturday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							$r++;
							}
						$state_gmt = "$state_gmt'99'";
						$list_state_gmt_SQL .= "or (list_id='$cur_list_id' and state='$Gstate_call_time_state' and gmt_offset_now IN($state_gmt)) ";			
						$del_list_state_gmt_SQL .= "or (list_id='$cur_list_id' and state='$Gstate_call_time_state' && gmt_offset_now NOT IN($state_gmt)) ";
						}

					$b++;
					}
				if (length($ct_states)>2)
					{
					$ct_states =~ s/,$//gi;
					$ct_statesSQL = "and state NOT IN($ct_states)";
					}
				else
					{
					$ct_statesSQL = "";
					}

				$r=0;
				@default_gmt_ARY=@MT;
				$dgA=0;
				$list_default_gmt='';
				while($r < $g)
					{
					if ($GMT_day[$r]==0)	#### Sunday local time
						{
						if (($Gct_sunday_start==0) && ($Gct_sunday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_sunday_start) && ($GMT_hour[$r]<$Gct_sunday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==1)	#### Monday local time
						{
						if (($Gct_monday_start==0) && ($Gct_monday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_monday_start) && ($GMT_hour[$r]<$Gct_monday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==2)	#### Tuesday local time
						{
						if (($Gct_tuesday_start==0) && ($Gct_tuesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_tuesday_start) && ($GMT_hour[$r]<$Gct_tuesday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==3)	#### Wednesday local time
						{
						if (($Gct_wednesday_start==0) && ($Gct_wednesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_wednesday_start) && ($GMT_hour[$r]<$Gct_wednesday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==4)	#### Thursday local time
						{
						if (($Gct_thursday_start==0) && ($Gct_thursday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_thursday_start) && ($GMT_hour[$r]<$Gct_thursday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==5)	#### Friday local time
						{
						if (($Gct_friday_start==0) && ($Gct_friday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_friday_start) && ($GMT_hour[$r]<$Gct_friday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==6)	#### Saturday local time
						{
						if (($Gct_saturday_start==0) && ($Gct_saturday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_saturday_start) && ($GMT_hour[$r]<$Gct_saturday_stop) )
								{$list_default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					$r++;
					}

				$list_default_gmt = "$list_default_gmt'99'";
				
				#set variable for List Mix
				$indv_list_gmt_sql{ "$cur_list_id" } = "((list_id=\"$cur_list_id\" and gmt_offset_now IN($list_default_gmt) $ct_statesSQL) $list_state_gmt_SQL)";

				#only add for use if the list is active
				if ( ($list_id_act eq "Y") || ($allow_inactive eq "Y") )
					{
					$LCTlist_id_sql[$i] .= " or ((list_id='$cur_list_id' and gmt_offset_now IN($list_default_gmt) $ct_statesSQL) $list_state_gmt_SQL)";
					$del_list_id_sql[$i] .= "((list_id='$cur_list_id' and gmt_offset_now NOT IN($list_default_gmt) $ct_statesSQL) $del_list_state_gmt_SQL) or";
					$act_rec_countLISTS++;
					}
				}
				##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###

			# Add 1 to row count
			$rec_countLISTS++;
			}
		$sthY->finish();
		# Protect against campaigns with no list by making it an impossible list
		if (length($list_id_sql[$i]) < 1) {$list_id_sql[$i]="list_id='999876543210'";}
		else {$list_id_sql[$i] .= "))";}
		if (length($LCTlist_id_sql[$i]) > 0) {$list_id_sql[$i] .= "$LCTlist_id_sql[$i]";}

		if ($DB) {print "     campaign lists count ACTIVE:$act_rec_countLISTS | TOTAL:$rec_countLISTS \n";}
		if ($DBX) {print "     LIST ID SQL $list_id_sql[$i]";}
		if ($DBX) {print "     |$stmtA|\n";}
		$hopper_begin_output .= "     campaign lists count ACTIVE: $act_rec_countLISTS | TOTAL: $rec_countLISTS \n";


		### Delete the DONE leads if there are any
		$stmtA = "DELETE from $vicidial_hopper where campaign_id='$campaign_id[$i]' and status IN('DONE');";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "     hopper DONE cleared:  $affected_rows\n";}
		if ($DBX) {print "     |$stmtA|\n";}
		# Update the hopper ready count
		if ($affected_rows ne "0E0") 
			{		
			$hopper_ready_pre_count = $hopper_ready_count;
			$hopper_ready_count = $hopper_ready_count - $affected_rows;
			if ($DB) 
				{
				if($hopper_ready_pre_count == $hopper_ready_count) 
					{print "     hopper READY count minus deleted DONE leads:   $hopper_ready_count\n";}
				}
			}
		
		### Delete the leads that are out of GMT time range if there are any
		$stmtA = "DELETE from $vicidial_hopper where $del_list_id_sql[$i] (campaign_id='$campaign_id[$i]' and ($del_gmtSQL[$i]));";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "     hopper GMT BAD cleared:  $affected_rows\n";}
		if ($DBX) {print "     |$stmtA|\n";}
		
		# Update the hopper ready count
		if ($affected_rows ne "0E0") 
			{		
			$hopper_ready_pre_count = $hopper_ready_count;
			$hopper_ready_count = $hopper_ready_count - $affected_rows;
			if ($DB) 
				{
				if($hopper_ready_pre_count == $hopper_ready_count) 
					{print "     hopper READY count minus deleted GMT leads:   $hopper_ready_count\n";}
				}
			}
		
		# Get Campaign List Mix Settings
		if ($list_order_mix[$i] !~ /DISABLED/)
			{
			$stmtA = "SELECT vcl_id,vcl_name,list_mix_container,mix_method FROM vicidial_campaigns_list_mix where campaign_id='$campaign_id[$i]' and status='ACTIVE';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$vcl_id[$i] =				$aryA[0];
				$vcl_name[$i] =				$aryA[1];
				$list_mix_container[$i] =	$aryA[2];
				$mix_method[$i] =			$aryA[3];
				$rec_count++;
				}
			$sthA->finish();

			@list_mixARY=@MT;
			$list_mix_dialableSQL='';
			@list_mixARY = split(/:/,$list_mix_container[$i]);
			$x=0;
			foreach(@list_mixARY)
				{
				if ($x > 0) {$list_mix_dialableSQL .= " or ";}
				@list_mix_stepARY = split(/\|/,$list_mixARY[$x]);
				$list_mix_stepARY[3] =~ s/ /\',\'/gi;
				$list_mix_stepARY[3] =~ s/^\',|,\'-//gi;
				if ($list_mix_stepARY[3] !~ /\'$/)
					{$list_mix_stepARY[3] = "$list_mix_stepARY[3]'";}
				if ($DBX) {print "     LM $x ++$list_mixARY[$x]++ |$list_mix_stepARY[0]|$list_mix_stepARY[2]|$list_mix_stepARY[3]|\n";}
				$list_mix_dialableSQL .= "(($indv_list_gmt_sql{ \"$list_mix_stepARY[0]\" }) and status IN($list_mix_stepARY[3]))";
				$x++;
				}
			if ($DB) {print "     campaign mix: $list_order_mix[$i] |$vcl_id[$i] - $vcl_name[$i]|$list_mix_container[$i]|$x|$mix_method[$i]|\n";}
			$hopper_begin_output .= "     campaign mix: $list_order_mix[$i] |$vcl_id[$i] - $vcl_name[$i]|$list_mix_container[$i]|$x|$mix_method[$i]| \n";
			}

		if ( ($lead_filter_id[$i] !~ /^NONE$/) && (length($lead_filter_id[$i])>0) )
			{
			### Get SQL of lead filter for the campaign ###
			$stmtA = "SELECT lead_filter_sql FROM vicidial_lead_filters where lead_filter_id='$lead_filter_id[$i]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$lead_filter_sql[$i] = $aryA[0];
				$lead_filter_sql[$i] =~ s/\\//gi;
				$rec_count++;
				}
			$sthA->finish();
			if ($lead_filter_sql[$i] eq '' || !defined($lead_filter_sql[$i]))
				{
				if ($DB) {print "     lead filter $lead_filter_id[$i] does not exist\n";}
				if ($DBX) {print "     |$lead_filter_id[$i]|\n";}
				}
			else
				{
				$lead_filter_sql[$i] =~ s/^and|and$|^or|or$|^ and|and $|^ or|or $//gi;
				$lead_filter_sql[$i] =~ s/#.*\n//gi;
				$lead_filter_sql[$i] =~ s/\r|\n|\t/ /gi;
				$lead_filter_sql[$i] = "and ($lead_filter_sql[$i])";
				if ($DB) {print "     lead filter $lead_filter_id[$i] defined for $campaign_id[$i]\n";}
				if ($DBX) {print "     |$lead_filter_sql[$i]|\n";}
				$hopper_begin_output .= "     lead filter $lead_filter_id[$i] defined for $campaign_id[$i] \n";
				}
			}
		else
			{
			$lead_filter_sql[$i] = '';
			if ($DB) {print "     no lead filter defined for campaign: $campaign_id[$i]\n";}
			if ($DBX) {print "     |$lead_filter_id[$i]|\n";}
			}

		$DLTsql[$i]='';
		if ($drop_lockout_time[$i] > 0)
			{
			$DLseconds[$i] = ($drop_lockout_time[$i] * 3600);
			$DLTsql[$i] = "and ( ( (status IN('DROP','XDROP')) and (last_local_call_time < CONCAT(DATE_ADD(NOW(), INTERVAL -$DLseconds[$i] SECOND),' ',CURTIME()) ) ) or (status NOT IN('DROP','XDROP')) )";
			if ($DB) {print "     drop lockout time $drop_lockout_time[$i]($DLseconds[$i]) defined for $campaign_id[$i]\n";}
			if ($DBX) {print "     |$DLTsql[$i]|\n";}
			$hopper_begin_output .= "     drop lockout time $drop_lockout_time[$i]($DLseconds[$i]) defined for $campaign_id[$i] \n";
			}

		$CCLsql[$i]='';
		if ($call_count_limit[$i] > 0)
			{
			$CCLsql[$i] = "and (called_count < $call_count_limit[$i])";
			if ($DB) {print "     total call count limit $call_count_limit[$i] defined for $campaign_id[$i]\n";}
			if ($DBX) {print "     |$CCLsql[$i]|\n";}
			$hopper_begin_output .= "     total call count limit $call_count_limit[$i] defined for $campaign_id[$i] \n";
			}

		##### Set default variables for standard hopper inserts #####
		$cslrSQL = "called_since_last_reset='N' and";
		$hopperSOURCE='S';
		$hopperPRIORITY='0';

		##### Load overrides if Hopper Drop-Run is triggered #####
		if ($hopper_drop_run_trigger[$i] =~ /Y|A/)
			{
			if ($hopper_drop_run_trigger[$i] =~ /A/)
				{
				$ALL_drop_statuses="'DROP','PDROP','XDROP'";
				$stmtA = "SELECT status FROM vicidial_statuses where status LIKE \"%DROP%\" or status_name LIKE \"%DROP%\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsA=$sthA->rows;
				$rec_count=0;
				while ($sthArowsA > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$ALL_drop_statuses .= ",'$aryA[0]'";
					$rec_count++;
					}
				$sthA->finish();
				$stmtA = "SELECT status FROM vicidial_campaign_statuses where status LIKE \"%DROP%\" or status_name LIKE \"%DROP%\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsA=$sthA->rows;
				$rec_count=0;
				while ($sthArowsA > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$ALL_drop_statuses .= ",'$aryA[0]'";
					$rec_count++;
					}
				$sthA->finish();
				}
			else
				{$ALL_drop_statuses="'DROP'";}
			$cslrSQL='';
			$STATUSsql[$i] = $ALL_drop_statuses;
			$list_order_mix[$i]='DISABLED';
			$lead_order[$i]='DOWN LAST CALL TIME';
			$hopper_level[$i]='20000';
			$hopperSOURCE='D';
			$hopperPRIORITY='99';

			### search for existing DROPs in the hopper, and update them if found
			$HOPPER_leads='';
			$stmtA = "SELECT lead_id FROM $vicidial_hopper where campaign_id='$campaign_id[$i]' and source!='D' and priority!='99';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsL=$sthA->rows;
			if ($DBX) {print "CAMPAIGN Hopper Drop-Run Existing Drops Check 1: $sthArowsL|$stmtA|\n";}
			$rec_count=0;
			while ($sthArowsL > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				if ($rec_count > 0) {$HOPPER_leads .= ',';}
				$HOPPER_leads .= "'$aryA[0]'";
				$rec_count++;
				}
			if ($rec_count > 0) 
				{
				$HOPPER_DROP_leads='';
				$stmtA = "SELECT lead_id FROM vicidial_list where lead_id IN($HOPPER_leads) and status IN($STATUSsql[$i]);";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsLD=$sthA->rows;
				if ($DBX) {print "CAMPAIGN Hopper Drop-Run Existing Drops Check 2: $sthArowsLD|$stmtA|\n";}
				$rec_count=0;
				while ($sthArowsLD > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					if ($rec_count > 0) {$HOPPER_DROP_leads .= ',';}
					$HOPPER_DROP_leads .= "'$aryA[0]'";
					$rec_count++;
					}
				if ($rec_count > 0) 
					{
					$stmtA = "UPDATE $vicidial_hopper SET priority='99',source='D' where lead_id IN($HOPPER_DROP_leads);";
					$affected_rowsU = $dbhA->do($stmtA);
					if ($DBX) {print "CAMPAIGN Hopper Drop-Run UPDATE: $affected_rowsU|$stmtA|\n";}
					}
				}

			$stmtA = "UPDATE vicidial_campaigns SET hopper_drop_run_trigger='N' where campaign_id='$campaign_id[$i]';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "CAMPAIGN Hopper Drop-Run RESET: $affected_rows|$stmtA|\n";}

			if ($DB) {print "Campaign Drop-Run triggered for $campaign_id[$i]($hopper_drop_run_trigger[$i]) |$STATUSsql[$i]|$list_order_mix[$i]|$affected_rows|$affected_rowsU|\n";}
			$hopper_begin_output .= "Campaign Drop-Run triggered for $campaign_id[$i]($hopper_drop_run_trigger[$i]) |$STATUSsql[$i]|$list_order_mix[$i]|$affected_rows|$affected_rowsU| \n";
			}

		##### Get count of leads that are dialable #####
		if ($list_order_mix[$i] =~ /DISABLED/)
			{
			$stmtA = "SELECT count(*) FROM vicidial_list $VLforce_index where $cslrSQL status IN($STATUSsql[$i]) and ($list_id_sql[$i]) and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $DLTsql[$i] $CCLsql[$i] $demographic_quotasSQL[$i];";
			}
		else
			{
			if (length($list_mix_dialableSQL)<3) {$list_mix_dialableSQL="called_count < 0";}
			$stmtA = "SELECT count(*) FROM vicidial_list $VLforce_index where $cslrSQL ($list_mix_dialableSQL) and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $DLTsql[$i] $CCLsql[$i] $demographic_quotasSQL[$i];";
			}
			if ($DBX) {print "     |$stmtA|\n";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$campaign_leads_to_call[$i] = $aryA[0];
			if ($DB) {print "     leads to call count:  $campaign_leads_to_call[$i]\n";}
			if ($DBX) {print "     |$stmtA|\n";}
			$hopper_begin_output .= "     leads to call count:  $campaign_leads_to_call[$i] \n";
			}
		$sthA->finish();

		if ( ($lead_order[$i] =~ / 2nd NEW$| 3rd NEW$| 4th NEW$| 5th NEW$| 6th NEW$/) && ($list_order_mix[$i] =~ /DISABLED/) )
			{
			$stmtA = "SELECT count(*) FROM vicidial_list $VLforce_index where $cslrSQL status IN('NEW') and ($list_id_sql[$i]) and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $DLTsql[$i] $demographic_quotasSQL[$i];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$NEW_campaign_leads_to_call[$i] = $aryA[0];
				if ($DB) {print "     NEW leads to call count:  $NEW_campaign_leads_to_call[$i]\n";}
				if ($DBX) {print "     |$stmtA|\n";}
				$hopper_begin_output .= "     NEW leads to call count:  $NEW_campaign_leads_to_call[$i] \n";
				}
			$sthA->finish();
			}

		##### IF no NEW leads to be called, error out of this campaign #####
		if ( ($lead_order[$i] =~ / 2nd NEW$| 3rd NEW$| 4th NEW$| 5th NEW$| 6th NEW$/) && ($NEW_campaign_leads_to_call[$i] > 0) && ($list_order_mix[$i] =~ /DISABLED/) ) {$GOOD=1;}
		else
			{
			if ($lead_order[$i] !~ / 2nd NEW$| 3rd NEW$| 4th NEW$| 5th NEW$| 6th NEW$/)
				{
				if ($DB) {print "     NO SHUFFLE-NEW-LEADS INTO HOPPER DEFINED FOR LEAD ORDER\n";}
				}
			else
				{
				if ($DB) {print "     ERROR CANNOT ADD ANY NEW LEADS TO HOPPER\n";}
				}
			}

		##### UPDATE the leads to be dialed for this campaign #####
		if ( ($campaign_leads_to_call[$i] < 1) && ($rec_ct[$i] < 1) )
			{
			if ($DB) {print "     NO DIALABLE LEADS FOR THIS CAMPAIGN\n";}
			if ($VCSdialable_leads[$i] > 0)
				{
				$stmtA = "UPDATE vicidial_campaign_stats SET dialable_leads='0' where campaign_id='$campaign_id[$i]';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "CAMPAIGN STATS: $affected_rows|$stmtA|\n";}
				}
			}
		else
			{
			if ($VCSdialable_leads[$i] != $campaign_leads_to_call[$i])
				{
				$stmtA = "UPDATE vicidial_campaign_stats SET dialable_leads='$campaign_leads_to_call[$i]' where campaign_id='$campaign_id[$i]';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "CAMPAIGN STATS: $affected_rows|$stmtA|\n";}
				}
			}
		$ENDoutput .= "$campaign_id[$i]     $campaign_leads_to_call[$i]     $hopper_level[$i]     $hopper_ready_count\n";

		##### IF hopper level is below set minimum, then try to add more leads #####
		if ($hopper_ready_count < $hopper_level[$i])
			{
			if ($DB) {print "     hopper too low ($hopper_ready_count|$hopper_level[$i]) starting hopper dump\n";}
			$hopper_begin_output .= "     hopper too low ($hopper_ready_count|$hopper_level[$i]) starting hopper dump \n";

			##### IF no leads to be called, error out of this campaign #####
			if ( ($campaign_leads_to_call[$i] < 1) && ($rec_ct[$i] < 1) )
				{
				if ($DB) {print "     ERROR CANNOT ADD ANY LEADS TO HOPPER\n";}
				$hopper_begin_output .= "     ERROR CANNOT ADD ANY LEADS TO HOPPER \n";
				}
			else
				{
				if ($DB) {print "     Getting Leads to add to hopper\n";}
				### grab leads already in hopper so we don't duplicate
				$stmtA = "SELECT lead_id,vendor_lead_code FROM $vicidial_hopper where campaign_id='$campaign_id[$i]';";
				if ($DBX) {print "     |$stmtA|\n";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$lead_id_lists = '';
				$vlc_lists = '';
				$rec_countLISTS=0;
				while ($sthArows > $rec_countLISTS)
					{
					@aryA = $sthA->fetchrow_array;
					$lead_id_lists .= "'$aryA[0]',";
					if (length($aryA[1]) > 0)
						{$vlc_lists .= "\"$aryA[1]\",";}
					$rec_countLISTS++;
					}
				$sthA->finish();
				$lead_id_lists .= "'0'";
				$vlc_lists .= "\"--99999999987654321--\"";
				$order_stmt='';
				$NEW_count = 0;
				$NEW_level = 0;
				$OTHER_level = $hopper_level[$i];

				if ($lead_order_randomize[$i] =~ /Y/) {$last_order = "RAND()";}
				else 
					{
					$last_order = "lead_id asc";
					if ($lead_order_secondary[$i] =~ /LEAD_ASCEND/) {$last_order = "lead_id asc";}
					if ($lead_order_secondary[$i] =~ /LEAD_DESCEND/) {$last_order = "lead_id desc";}
					if ($lead_order_secondary[$i] =~ /CALLTIME_ASCEND/) {$last_order = "last_local_call_time asc";}
					if ($lead_order_secondary[$i] =~ /CALLTIME_DESCEND/) {$last_order = "last_local_call_time desc";}
					if ($lead_order_secondary[$i] =~ /VENDOR_ASCEND/) {$last_order = "vendor_lead_code+0 asc, vendor_lead_code asc";}
					if ($lead_order_secondary[$i] =~ /VENDOR_DESCEND/) {$last_order = "vendor_lead_code+0 desc, vendor_lead_code desc";}
					}

				if ($lead_order[$i] =~ /^DOWN/) {$order_stmt = "order by lead_id asc";}
				if ($lead_order[$i] =~ /^UP/) {$order_stmt = "order by lead_id desc";}
				if ($lead_order[$i] =~ /^UP LAST NAME/) {$order_stmt = "order by last_name desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN LAST NAME/) {$order_stmt = "order by last_name, $last_order";}
				if ($lead_order[$i] =~ /^UP PHONE/) {$order_stmt = "order by phone_number desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN PHONE/) {$order_stmt = "order by phone_number, $last_order";}
				if ($lead_order[$i] =~ /^UP COUNT/) {$order_stmt = "order by called_count desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN COUNT/) {$order_stmt = "order by called_count, $last_order";}
				if ($lead_order[$i] =~ /^UP LAST CALL TIME/) {$order_stmt = "order by last_local_call_time desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN LAST CALL TIME/) {$order_stmt = "order by last_local_call_time, $last_order";}
				if ($lead_order[$i] =~ /^RANDOM/) {$order_stmt = "order by RAND()";}
				if ($lead_order[$i] =~ /^UP RANK/) {$order_stmt = "order by rank desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN RANK/) {$order_stmt = "order by rank, $last_order";}
				if ($lead_order[$i] =~ /^UP OWNER/) {$order_stmt = "order by owner desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN OWNER/) {$order_stmt = "order by owner, $last_order";}
				if ($lead_order[$i] =~ /^UP TIMEZONE/) {$order_stmt = "order by gmt_offset_now desc, $last_order";}
				if ($lead_order[$i] =~ /^DOWN TIMEZONE/) {$order_stmt = "order by gmt_offset_now, $last_order";}
				if ($lead_order[$i] =~ / 2nd NEW$/) {$NEW_count = 2;}
				if ($lead_order[$i] =~ / 3rd NEW$/) {$NEW_count = 3;}
				if ($lead_order[$i] =~ / 4th NEW$/) {$NEW_count = 4;}
				if ($lead_order[$i] =~ / 5th NEW$/) {$NEW_count = 5;}
				if ($lead_order[$i] =~ / 6th NEW$/) {$NEW_count = 6;}


			### BEGIN recycle grab leads ###
				$REC_rec_countLEADS=0;
				@REC_leads_to_hopper=@MT;
				@REC_lists_to_hopper=@MT;
				@REC_phone_to_hopper=@MT;
				@REC_phone_code_to_hopper=@MT;
				@REC_postal_code_to_hopper=@MT;
				@REC_gmt_to_hopper=@MT;
				@REC_state_to_hopper=@MT;
				@REC_status_to_hopper=@MT;
				@REC_modify_to_hopper=@MT;
				@REC_user_to_hopper=@MT;
				@REC_vlc_to_hopper=@MT;
				@REC_source_to_hopper=@MT;
				if ($rec_ct[$i] > 0)
					{
					if ($DB) {print "     looking for RECYCLE leads, maximum of $hopper_level[$i]\n";}
					$vlc_dup_check_SQL='';
					if ($hopper_vlc_dup_check[$i] =~ /Y/) 
						{$vlc_dup_check_SQL = "and vendor_lead_code NOT IN($live_vlc$vlc_lists)";}

					$stmtA = "SELECT lead_id,list_id,gmt_offset_now,phone_number,state,status,modify_date,user,vendor_lead_code,phone_code,postal_code FROM vicidial_list $VLforce_index where $recycle_SQL[$i] and ($list_id_sql[$i]) and lead_id NOT IN($lead_id_lists) $vlc_dup_check_SQL and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $CCLsql[$i] $DLTsql[$i] $demographic_quotasSQL[$i] $dnc_blocked_lists_SQL $order_stmt limit $hopper_level[$i];";
					if ($DBX) {print "     |$stmtA|\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($DB) {print "     RECYCLE leads to call count:  $sthArows\n";}
					if ($DBX) {print "     |$stmtA|\n";}
					while ($sthArows > $REC_rec_countLEADS)
						{
						@aryA = $sthA->fetchrow_array;
						$REC_leads_to_hopper[$REC_rec_countLEADS] =		$aryA[0];
						$REC_lists_to_hopper[$REC_rec_countLEADS] =		$aryA[1];
						$REC_gmt_to_hopper[$REC_rec_countLEADS] =		$aryA[2];
						$REC_phone_to_hopper[$REC_rec_countLEADS] =		$aryA[3];
						$REC_state_to_hopper[$REC_rec_countLEADS] =		$aryA[4];
						$REC_status_to_hopper[$REC_rec_countLEADS] =	$aryA[5];
						$REC_modify_to_hopper[$REC_rec_countLEADS] =	$aryA[6];
						$REC_user_to_hopper[$REC_rec_countLEADS] =		$aryA[7];
						$REC_vlc_to_hopper[$REC_rec_countLEADS] =		$aryA[8];
						$REC_phone_code_to_hopper[$REC_rec_countLEADS] =$aryA[9];
						$REC_postal_code_to_hopper[$REC_rec_countLEADS] =$aryA[10];
						$REC_source_to_hopper[$REC_rec_countLEADS] =	'R';
						if ($DB_show_offset) {print "LEAD_ADD: $aryA[2] $aryA[3] $aryA[4]\n";}
						$REC_rec_countLEADS++;

						if (length($aryA[8]) > 0)
							{$vlc_lists .= ",\"$aryA[8]\"";}
						}
					$sthA->finish();
					}
				else
					{
					if ($DB) {print "     NO RECYCLE-LEADS INTO HOPPER DEFINED\n";}
					}
				$hopper_begin_output .= "     Recycle leads to insert into the hopper: $REC_rec_countLEADS \n";
			### END recycle grab leads ###


			### BEGIN NEW grab leads ###
				$NEW_rec_countLEADS=0;
				@NEW_leads_to_hopper=@MT;
				@NEW_lists_to_hopper=@MT;
				@NEW_phone_to_hopper=@MT;
				@NEW_phone_code_to_hopper=@MT;
				@NEW_postal_code_to_hopper=@MT;
				@NEW_gmt_to_hopper=@MT;
				@NEW_state_to_hopper=@MT;
				@NEW_status_to_hopper=@MT;
				@NEW_modify_to_hopper=@MT;
				@NEW_user_to_hopper=@MT;
				@NEW_vlc_to_hopper=@MT;
				@NEW_source_to_hopper=@MT;
				if ( ($NEW_count > 0) && ($list_order_mix[$i] =~ /DISABLED/) )
					{
					$NEW_level = int($hopper_level[$i] / $NEW_count);   
					$OTHER_level = ($hopper_level[$i] - $NEW_level);   
					if ($DB) {print "     looking for $NEW_level NEW leads mixed in with $OTHER_level other leads\n";}
					$vlc_dup_check_SQL='';
					if ($hopper_vlc_dup_check[$i] =~ /Y/) 
						{$vlc_dup_check_SQL = "and vendor_lead_code NOT IN($live_vlc$vlc_lists)";}

					$stmtA = "SELECT lead_id,list_id,gmt_offset_now,phone_number,state,status,modify_date,user,vendor_lead_code,phone_code,postal_code FROM vicidial_list $VLforce_index where $cslrSQL status IN('NEW') and ($list_id_sql[$i]) and lead_id NOT IN($lead_id_lists) $vlc_dup_check_SQL and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $CCLsql[$i] $DLTsql[$i] $demographic_quotasSQL[$i] $dnc_blocked_lists_SQL $order_stmt limit $NEW_level;";
					if ($DBX) {print "     |$stmtA|\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					while ($sthArows > $NEW_rec_countLEADS)
						{
						@aryA = $sthA->fetchrow_array;
						$NEW_leads_to_hopper[$NEW_rec_countLEADS] =		$aryA[0];
						$NEW_lists_to_hopper[$NEW_rec_countLEADS] =		$aryA[1];
						$NEW_gmt_to_hopper[$NEW_rec_countLEADS] =		$aryA[2];
						$NEW_phone_to_hopper[$NEW_rec_countLEADS] =		$aryA[3];
						$NEW_state_to_hopper[$NEW_rec_countLEADS] =		$aryA[4];
						$NEW_status_to_hopper[$NEW_rec_countLEADS] =	$aryA[5];
						$NEW_modify_to_hopper[$NEW_rec_countLEADS] =	$aryA[6];
						$NEW_user_to_hopper[$NEW_rec_countLEADS] =		$aryA[7];
						$NEW_vlc_to_hopper[$NEW_rec_countLEADS] =		$aryA[8];
						$NEW_phone_code_to_hopper[$NEW_rec_countLEADS] =$aryA[9];
						$NEW_postal_code_to_hopper[$NEW_rec_countLEADS] =$aryA[10];
						$NEW_source_to_hopper[$NEW_rec_countLEADS] =	'N';
						if ($DB_show_offset) {print "LEAD_ADD: $aryA[2] $aryA[3] $aryA[4]\n";}
						$NEW_rec_countLEADS++;

						if (length($aryA[8]) > 0)
							{$vlc_lists .= ",\"$aryA[8]\"";}
						}
					$OTHER_level = ($hopper_level[$i] - $NEW_rec_countLEADS);
					$sthA->finish();
					$hopper_begin_output .= "     NEW leads to insert into the hopper: $NEW_rec_countLEADS \n";
					}

			### BEGIN standard grab leads ###
				$rec_countLEADS=0;
				$NEW_dec=99;
				$NEW_in=0;
				$rec_count=0;
				$REC_insert_count=0;
				$vlc_dup_check_SKIP_COUNT=0;
				@leads_to_hopper=@MT;
				@lists_to_hopper=@MT;
				@gmt_to_hopper=@MT;
				@state_to_hopper=@MT;
				@phone_to_hopper=@MT;
				@phone_code_to_hopper=@MT;
				@postal_code_to_hopper=@MT;
				@status_to_hopper=@MT;
				@modify_to_hopper=@MT;
				@user_to_hopper=@MT;
				@vlc_to_hopper=@MT;
				@source_to_hopper=@MT;
				if ($campaign_leads_to_call[$i] > 0)
					{
					if ($DB) {print "     lead call order:      $order_stmt\n";}
					$vlc_dup_check_SQL='';
					if ($hopper_vlc_dup_check[$i] =~ /Y/) 
						{$vlc_dup_check_SQL = "and vendor_lead_code NOT IN($live_vlc$vlc_lists)";}

					if ($list_order_mix[$i] =~ /DISABLED/)
						{
						$stmtA = "SELECT lead_id,list_id,gmt_offset_now,phone_number,state,status,modify_date,user,vendor_lead_code,phone_code,postal_code FROM vicidial_list $VLforce_index where $cslrSQL status IN($STATUSsql[$i]) and ($list_id_sql[$i]) and lead_id NOT IN($lead_id_lists) $vlc_dup_check_SQL and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $CCLsql[$i] $DLTsql[$i] $demographic_quotasSQL[$i] $dnc_blocked_lists_SQL $order_stmt limit $OTHER_level;";
						if ($DBX) {print "     |$stmtA|\n";}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						while ($sthArows > $rec_count)
							{
							@aryA = $sthA->fetchrow_array;
							if ( ($NEW_count > 0) && ($NEW_rec_countLEADS > $NEW_in) )
								{
								if ($DB_show_offset) {print "NEW_COUNT: $NEW_count|$NEW_dec|$NEW_in|$NEW_rec_countLEADS\n";}
								if ($NEW_count > $NEW_dec) 
									{
									$NEW_dec++;
									}
								else
									{
									$leads_to_hopper[$rec_countLEADS] =		$NEW_leads_to_hopper[$NEW_in];
									$lists_to_hopper[$rec_countLEADS] =		$NEW_lists_to_hopper[$NEW_in];
									$gmt_to_hopper[$rec_countLEADS] =		$NEW_gmt_to_hopper[$NEW_in];
									$state_to_hopper[$rec_countLEADS] =		$NEW_state_to_hopper[$NEW_in];
									$phone_to_hopper[$rec_countLEADS] =		$NEW_phone_to_hopper[$NEW_in];
									$phone_code_to_hopper[$rec_countLEADS] =$NEW_phone_code_to_hopper[$NEW_in];
									$postal_code_to_hopper[$rec_countLEADS] =$NEW_postal_code_to_hopper[$NEW_in];
									$status_to_hopper[$rec_countLEADS] =	$NEW_status_to_hopper[$NEW_in];
									$modify_to_hopper[$rec_countLEADS] =	$NEW_modify_to_hopper[$NEW_in];
									$user_to_hopper[$rec_countLEADS] =		$NEW_user_to_hopper[$NEW_in];
									$vlc_to_hopper[$rec_countLEADS] =		$NEW_vlc_to_hopper[$NEW_in];
									$source_to_hopper[$rec_countLEADS] =	$NEW_source_to_hopper[$NEW_in];
									if ($DB_show_offset) {print "LEAD_ADD:    $NEW_leads_to_hopper[$NEW_in]   $NEW_phone_to_hopper[$NEW_in]\n";}
									$rec_countLEADS++;
									$NEW_in++;
									$NEW_dec=2;
									}
								}
							if ($REC_rec_countLEADS > $REC_insert_count)
								{
								$leads_to_hopper[$rec_countLEADS] =		$REC_leads_to_hopper[$REC_insert_count];
								$lists_to_hopper[$rec_countLEADS] =		$REC_lists_to_hopper[$REC_insert_count];
								$gmt_to_hopper[$rec_countLEADS] =		$REC_gmt_to_hopper[$REC_insert_count];
								$state_to_hopper[$rec_countLEADS] =		$REC_state_to_hopper[$REC_insert_count];
								$phone_to_hopper[$rec_countLEADS] =		$REC_phone_to_hopper[$REC_insert_count];
								$phone_code_to_hopper[$rec_countLEADS] =$REC_phone_code_to_hopper[$REC_insert_count];
								$postal_code_to_hopper[$rec_countLEADS] =$REC_postal_code_to_hopper[$REC_insert_count];
								$status_to_hopper[$rec_countLEADS] =	$REC_status_to_hopper[$REC_insert_count];
								$modify_to_hopper[$rec_countLEADS] =	$REC_modify_to_hopper[$REC_insert_count];
								$user_to_hopper[$rec_countLEADS] =		$REC_user_to_hopper[$REC_insert_count];
								$vlc_to_hopper[$rec_countLEADS] =		$REC_vlc_to_hopper[$REC_insert_count];
								$source_to_hopper[$rec_countLEADS] =	$REC_source_to_hopper[$REC_insert_count];
								$rec_countLEADS++;
								$REC_insert_count++;
								}
							$leads_to_hopper[$rec_countLEADS] =		$aryA[0];
							$lists_to_hopper[$rec_countLEADS] =		$aryA[1];
							$gmt_to_hopper[$rec_countLEADS] =		$aryA[2];
							$state_to_hopper[$rec_countLEADS] =		$aryA[4];
							$phone_to_hopper[$rec_countLEADS] =		$aryA[3];
							$status_to_hopper[$rec_countLEADS] =	$aryA[5];
							$modify_to_hopper[$rec_countLEADS] =	$aryA[6];
							$user_to_hopper[$rec_countLEADS] =		$aryA[7];
							$vlc_to_hopper[$rec_countLEADS] =		$aryA[8];
							$phone_code_to_hopper[$rec_countLEADS] =$aryA[9];
							$postal_code_to_hopper[$rec_countLEADS] =$aryA[10];
							$source_to_hopper[$rec_countLEADS] =	$hopperSOURCE;
							if ($DB_show_offset) {print "LEAD_ADD: $aryA[2] $aryA[3] $aryA[4]\n";}
							$rec_countLEADS++;
							$rec_count++;

							if (length($aryA[8]) > 0)
								{$vlc_lists .= ",\"$aryA[8]\"";}
							}
						$sthA->finish();
						}

				##### LIST MIX LEADS GRAB #####
					else
						{
						$USX='_____';
						$x=0;
						$z=0;
						@LM_results=@MT;
						foreach(@list_mixARY)
							{
							$rec_count=0;
							@list_mix_stepARY=@MT;

							@list_mix_stepARY = split(/\|/,$list_mixARY[$x]);
							if ($list_mix_stepARY[2] > 0)
								{
								$LM_step_goal[$x] = int( ($list_mix_stepARY[2] / 100) * $hopper_level[$i]);
								$LM_step_even[$x] = int( (100 / $list_mix_stepARY[2]) * 100000);
								if ($LM_step_goal[$x] < 1)	{$LM_step_goal[$x] = 1;}
								if ($LM_step_even[$x] < 1)	{$LM_step_even[$x] = 1;}
								}
							else
								{
								$LM_step_goal[$x] = 0;
								$LM_step_even[$x] = 0;
								}
							$list_mix_stepARY[3] =~ s/ /','/gi;
							$list_mix_stepARY[3] =~ s/^',|,'-//gi;
							if ($list_mix_stepARY[3] !~ /\'$/)
								{$list_mix_stepARY[3] = "$list_mix_stepARY[3]'";}
							if ($DBX) {print "  LM $x |$list_mix_stepARY[0]|$list_mix_stepARY[2]|$LM_step_goal[$x]|$list_mix_stepARY[3]|\n";}
							$list_mix_dialableSQL = "(($indv_list_gmt_sql{ \"$list_mix_stepARY[0]\" }) and status IN($list_mix_stepARY[3]))";
							$vlc_dup_check_SQL='';
							if ($hopper_vlc_dup_check[$i] =~ /Y/) 
								{$vlc_dup_check_SQL = "and vendor_lead_code NOT IN($live_vlc$vlc_lists)";}

							$stmtA = "SELECT lead_id,list_id,gmt_offset_now,phone_number,state,status,modify_date,user,vendor_lead_code,phone_code,postal_code FROM vicidial_list $VLforce_index where $cslrSQL ($list_mix_dialableSQL) and lead_id NOT IN($lead_id_lists) $vlc_dup_check_SQL and ($all_gmtSQL[$i]) $lead_filter_sql[$i] $CCLsql[$i] $DLTsql[$i] $demographic_quotasSQL[$i] $dnc_blocked_lists_SQL $order_stmt limit $LM_step_goal[$x];";
							if ($DBX) {print "     |$stmtA|\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							while ($sthArows > $rec_count)
								{
								@aryA = $sthA->fetchrow_array;
								if ($mix_method[$i] =~ /RANDOM/) 
									{
									$order = int( rand(9999999)) + 10000000;
									}
								else 
									{
									if ($mix_method[$i] =~ /EVEN_MIX/) 
										{
										$order = ( ($rec_count * $LM_step_even[$x]) + $x);
										}
									else
										{
										$order = ( ($x * 1000000) + $rec_count);
										}
									}
								$LM_results[$z] = "$order$USX$aryA[0]$USX$aryA[1]$USX$aryA[2]$USX$aryA[3]$USX$aryA[4]$USX$aryA[5]$USX$aryA[6]$USX$aryA[7]$USX$aryA[8]$USX$aryA[9]$USX$aryA[10]";
							#	if ($DBX) {print "     $z|$LM_results[$z]\n";}

								$rec_count++;
								$z++;
								}
							$sthA->finish();

							$x++;
							}

						@LM_results_SORT = sort { $a <=> $b } @LM_results;

						$w=0;
						while ($z > $w)
							{
							@aryA = split(/_____/,$LM_results_SORT[$w]);
							if ($REC_rec_countLEADS > $REC_insert_count)
								{
								$leads_to_hopper[$rec_countLEADS] =		$REC_leads_to_hopper[$REC_insert_count];
								$lists_to_hopper[$rec_countLEADS] =		$REC_lists_to_hopper[$REC_insert_count];
								$gmt_to_hopper[$rec_countLEADS] =		$REC_gmt_to_hopper[$REC_insert_count];
								$state_to_hopper[$rec_countLEADS] =		$REC_state_to_hopper[$REC_insert_count];
								$phone_to_hopper[$rec_countLEADS] =		$REC_phone_to_hopper[$REC_insert_count];
								$phone_code_to_hopper[$rec_countLEADS] =$REC_phone_code_to_hopper[$REC_insert_count];
								$postal_code_to_hopper[$rec_countLEADS] =$REC_postal_code_to_hopper[$REC_insert_count];
								$status_to_hopper[$rec_countLEADS] =	$REC_status_to_hopper[$REC_insert_count];
								$modify_to_hopper[$rec_countLEADS] =	$REC_modify_to_hopper[$REC_insert_count];
								$user_to_hopper[$rec_countLEADS] =		$REC_user_to_hopper[$REC_insert_count];
								$vlc_to_hopper[$rec_countLEADS] =		$REC_vlc_to_hopper[$REC_insert_count];
								$source_to_hopper[$rec_countLEADS] =	$REC_source_to_hopper[$REC_insert_count];
								$rec_countLEADS++;
								$REC_insert_count++;
								}
							$leads_to_hopper[$rec_countLEADS] =		$aryA[1];
							$lists_to_hopper[$rec_countLEADS] =		$aryA[2];
							$gmt_to_hopper[$rec_countLEADS] =		$aryA[3];
							$phone_to_hopper[$rec_countLEADS] =		$aryA[4];
							$state_to_hopper[$rec_countLEADS] =		$aryA[5];
							$status_to_hopper[$rec_countLEADS] =	$aryA[6];
							$modify_to_hopper[$rec_countLEADS] =	$aryA[7];
							$user_to_hopper[$rec_countLEADS] =		$aryA[8];
							$vlc_to_hopper[$rec_countLEADS] =		$aryA[9];
							$phone_code_to_hopper[$rec_countLEADS] =$aryA[10];
							$postal_code_to_hopper[$rec_countLEADS] =$aryA[11];
							$source_to_hopper[$rec_countLEADS] =	$hopperSOURCE;
							if ($DB_show_offset) {print "LEAD_ADD: $aryA[3] $aryA[4] $aryA[5]\n";}
							if ($DBX) {print "     $w|$LM_results[$w]\n";}
							$rec_countLEADS++;
							$w++;

							if (length($aryA[9]) > 0)
								{$vlc_lists .= ",\"$aryA[9]\"";}
							}
						}
					}
				### finish inserting any recycled leads if any
				while ($REC_rec_countLEADS > $REC_insert_count)
					{
					$leads_to_hopper[$rec_countLEADS] =		$REC_leads_to_hopper[$REC_insert_count];
					$lists_to_hopper[$rec_countLEADS] =		$REC_lists_to_hopper[$REC_insert_count];
					$gmt_to_hopper[$rec_countLEADS] =		$REC_gmt_to_hopper[$REC_insert_count];
					$state_to_hopper[$rec_countLEADS] =		$REC_state_to_hopper[$REC_insert_count];
					$phone_to_hopper[$rec_countLEADS] =		$REC_phone_to_hopper[$REC_insert_count];
					$phone_code_to_hopper[$rec_countLEADS] =$REC_phone_code_to_hopper[$REC_insert_count];
					$postal_code_to_hopper[$rec_countLEADS] =$REC_postal_code_to_hopper[$REC_insert_count];
					$status_to_hopper[$rec_countLEADS] =	$REC_status_to_hopper[$REC_insert_count];
					$modify_to_hopper[$rec_countLEADS] =	$REC_modify_to_hopper[$REC_insert_count];
					$user_to_hopper[$rec_countLEADS] =		$REC_user_to_hopper[$REC_insert_count];
					$vlc_to_hopper[$rec_countLEADS] =		$REC_vlc_to_hopper[$REC_insert_count];
					$source_to_hopper[$rec_countLEADS] =	$REC_source_to_hopper[$REC_insert_count];
					$rec_countLEADS++;
					$REC_insert_count++;
					}

				if ($DB) {print "     Adding to hopper:     $rec_countLEADS\n";}
				$event_string = "|$campaign_id[$i]|Added to hopper $rec_countLEADS|";
				&event_logger;

				$h=0;
				$h_attempted=0;
				$DBinserted=0;
				foreach(@leads_to_hopper)
					{
					if ($leads_to_hopper[$h] != '0')
						{
						$DNClead=0;
						$DCCLlead=0;
						$DNCC=0;
						$DNCL=0;
						$DCCL=0;
						$TFHCCLlead=0;
						$TFHCCL=0;
						## Check for system DNC for this lead
						if ( ($use_internal_dnc[$i] =~ /Y/) || ($use_internal_dnc[$i] =~ /AREACODE/) )
							{
							if ($use_internal_dnc[$i] =~ /AREACODE/)
								{
								$pth_areacode = substr($phone_to_hopper[$h], 0, 3);
								$pth_areacode .= "XXXXXXX";
								$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$phone_to_hopper[$h]','$pth_areacode');";
								}
							else
								{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number=\"$phone_to_hopper[$h]\";";}
							if ($DB) {print "     Doing DNC Check: $phone_to_hopper[$h] - $use_internal_dnc[$i]\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$DNClead =		 $aryA[0];
								}
							$sthA->finish();
							if ($DNClead != '0')
								{
								$DNCL++;
								$stmtA = "UPDATE vicidial_list SET status='DNCL' where lead_id='$leads_to_hopper[$h]';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "Flagging DNC lead:     $affected_rows  $phone_to_hopper[$h]\n";}
								}
							}
						## Check for campaign DNC for this lead
						if ( ( ($use_campaign_dnc[$i] =~ /Y/) || ($use_campaign_dnc[$i] =~ /AREACODE/) ) && ($DNClead == '0') )
							{
							$temp_campaign_id = $campaign_id[$i];
							if (length($use_other_campaign_dnc[$i]) > 0) {$temp_campaign_id = $use_other_campaign_dnc[$i];}
							if ($use_campaign_dnc[$i] =~ /AREACODE/)
								{
								$pth_areacode = substr($phone_to_hopper[$h], 0, 3);
								$pth_areacode .= "XXXXXXX";
								$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$phone_to_hopper[$h]','$pth_areacode') and campaign_id='$temp_campaign_id';";
								}
							else
								{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number=\"$phone_to_hopper[$h]\" and campaign_id='$temp_campaign_id';";}
							if ($DBX) {print "$use_other_campaign_dnc[$i]|$stmtA\n";}
							if ($DB) {print "Doing CAMP DNC Check: $phone_to_hopper[$h] - $use_campaign_dnc[$i] - $temp_campaign_id\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$DNClead =	($DNClead + $aryA[0]);
								}
							$sthA->finish();
							if ($aryA[0] != '0')
								{
								$DNCC++;
								$stmtA = "UPDATE vicidial_list SET status='DNCC' where lead_id='$leads_to_hopper[$h]';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "Flagging DNC lead:     $affected_rows  $phone_to_hopper[$h] $campaign_id[$i]\n";}
								}
							}
						
						## Check for daily call count limit for this lead
						if ( ($SSdaily_call_count_limit > 0) && ($daily_call_count_limit[$i] > 0) ) 
							{
							if ($daily_limit_manual[$i] =~ /COUNT/)
								{
								$stmtA="SELECT count(*) FROM vicidial_lead_call_daily_counts where lead_id='$leads_to_hopper[$h]' and called_count_total >= '$daily_call_count_limit[$i]';";
								}
							else
								{
								$stmtA="SELECT count(*) FROM vicidial_lead_call_daily_counts where lead_id='$leads_to_hopper[$h]' and called_count_auto >= '$daily_call_count_limit[$i]';";
								}
							if ($DB) {print "     Doing Daily Call Count Check: $leads_to_hopper[$h] - $daily_call_count_limit[$i]\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$DCCLlead =		 $aryA[0];
								}
							$sthA->finish();
							if ($DCCLlead != '0')
								{
								$DCCL++;
								$stmtA = "UPDATE vicidial_list SET called_since_last_reset='Y' where lead_id='$leads_to_hopper[$h]';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "Flagging DCCL lead:     $affected_rows  $leads_to_hopper[$h]\n";}
								}
							}

						## Check for system-wide daily call count limit for this phone number
						if ( ($SSdaily_call_count_limit > 0) && ($daily_phone_number_call_limit[$i] > 0) )
							{
							$stmtA="SELECT count(*) FROM vicidial_phone_number_call_daily_counts where phone_number='$phone_to_hopper[$h]' and called_count >= '$daily_phone_number_call_limit[$i]';";
							if ($DB) {print "     Doing Daily Phone Call Count Check: $phone_to_hopper[$h] - $daily_phone_number_call_limit[$i]\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$DCCLlead =		 $aryA[0];
								}
							$sthA->finish();
							if ($DCCLlead != '0')
								{
								$DCCL++;
								$stmtA = "UPDATE vicidial_list SET called_since_last_reset='Y' where lead_id='$leads_to_hopper[$h]';";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "Flagging DCCLP lead:     $affected_rows  $leads_to_hopper[$h] ($phone_to_hopper[$h]) \n";}
								}
							}

						## BEGIN Check for 24-Hour call count limit for this lead/phone_number
						$passed_24hour_call_count=1;
						if ( ($SScall_limit_24hour > 0) && ($call_limit_24hour_method[$i] =~ /PHONE_NUMBER|LEAD/) )
							{
							$temp_24hour_phone =				$phone_to_hopper[$h];
							$temp_24hour_phone_code =			$phone_code_to_hopper[$h];
							$temp_24hour_state =				$state_to_hopper[$h];
							$temp_24hour_postal_code =			$postal_code_to_hopper[$h];
							$TEMPlead_id =						$leads_to_hopper[$h];
							$TEMPcampaign_id =					$campaign_id[$i];
							$TEMPcall_limit_24hour_method =		$call_limit_24hour_method[$i];
							$TEMPcall_limit_24hour_scope =		$call_limit_24hour_scope[$i];
							$TEMPcall_limit_24hour =			$call_limit_24hour[$i];
							$TEMPcall_limit_24hour_override	=	$call_limit_24hour_override[$i];
							$TFHCCLalt=0;
							if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$TEMPcall_limit_24hour_method|$TEMPcall_limit_24hour_scope|$TEMPlead_id|\n";}
							&check_24hour_call_count;
							}
						## END Check for 24-Hour call count limit for this lead/phone_number	

						$VAC_exist=0;
						$stmtA="SELECT count(*) FROM vicidial_auto_calls where lead_id='$leads_to_hopper[$h]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VAC_exist =	$aryA[0];
							}
						$sthA->finish();

						$VLA_exist=0;
						$stmtA="SELECT count(*) FROM vicidial_live_agents where lead_id='$leads_to_hopper[$h]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VLA_exist =	$aryA[0];
							}
						$sthA->finish();

						$VLC_exist=0;
						if ( ($hopper_vlc_dup_check[$i] =~ /Y/) && (length($vlc_to_hopper[$h]) > 0) )
							{
							$stmtA="SELECT count(*) FROM $vicidial_hopper where vendor_lead_code=\"$vlc_to_hopper[$h]\";";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VLC_exist =	$aryA[0];
								}
							$sthA->finish();
							}

						if ( ($VAC_exist > 0) || ($VLA_exist > 0) )
							{
							$detail_string = "LIVE CALL SKIPPING     |$VAC_exist|$VLA_exist|     |$campaign_id[$i]|$leads_to_hopper[$h]|$phone_to_hopper[$h]|$state_to_hopper[$h]|$gmt_to_hopper[$h]|$status_to_hopper[$h]|$modify_to_hopper[$h]|$user_to_hopper[$h]|$vlc_to_hopper[$h]|$phone_code_to_hopper[$h]|$postal_code_to_hopper[$h]|";
							&detail_logger;
							}
						else
							{
							if ( ($VLC_exist > 0) && ($hopper_vlc_dup_check[$i] =~ /Y/) )
								{
								$detail_string = "VLC CALL SKIPPING     |$VLC_exist|$hopper_vlc_dup_check[$i]|     |$campaign_id[$i]|$leads_to_hopper[$h]|$phone_to_hopper[$h]|$state_to_hopper[$h]|$gmt_to_hopper[$h]|$status_to_hopper[$h]|$modify_to_hopper[$h]|$user_to_hopper[$h]|$vlc_to_hopper[$h]|$phone_code_to_hopper[$h]|$postal_code_to_hopper[$h]|";
								&detail_logger;
								$vlc_dup_check_SKIP_COUNT++;
								}
							else
								{
								if ($DNClead == '0')
									{
									if ( ($DCCLlead == '0') && ($TFHCCLlead == '0') )
										{
										$hopper_status='READY';
										if ($hopper_hold_inserts[$i] =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
										$stmtA = "INSERT INTO $vicidial_hopper (lead_id,campaign_id,status,user,list_id,gmt_offset_now,state,priority,source,vendor_lead_code) values('$leads_to_hopper[$h]','$campaign_id[$i]','$hopper_status','','$lists_to_hopper[$h]','$gmt_to_hopper[$h]',\"$state_to_hopper[$h]\",'$hopperPRIORITY','$source_to_hopper[$h]',\"$vlc_to_hopper[$h]\");";
										$affected_rows = $dbhA->do($stmtA);
										$DBinserted = ($DBinserted + $affected_rows);
										if ($DBX) {print "LEAD INSERTED: $affected_rows|$leads_to_hopper[$h]|\n";}
										if ($DB_detail) 
											{
											$detail_string = "|$campaign_id[$i]|$leads_to_hopper[$h]|$phone_to_hopper[$h]|$state_to_hopper[$h]|$gmt_to_hopper[$h]|$status_to_hopper[$h]|$modify_to_hopper[$h]|$user_to_hopper[$h]|$vlc_to_hopper[$h]|$source_to_hopper[$h]|$phone_code_to_hopper[$h]|$postal_code_to_hopper[$h]|$hopper_status|";
											&detail_logger;
											}
										}
									else
										{
										if ($DCCLlead > 0)	{$DCCLskip++;}
										if ($TFHCCLlead > 0) {$TFHCCLskip++;}
										$detail_string = "DAILY/24-HOUR CALL COUNT LIMIT SKIPPING     |$DCCLlead|$TFHCCLlead|($TFhourCOUNT >= $TEMPcall_limit_24hour)|     |$campaign_id[$i]|$leads_to_hopper[$h]|$phone_to_hopper[$h]|$state_to_hopper[$h]|$gmt_to_hopper[$h]|$status_to_hopper[$h]|$modify_to_hopper[$h]|$user_to_hopper[$h]|$vlc_to_hopper[$h]|$source_to_hopper[$h]|$phone_code_to_hopper[$h]|$postal_code_to_hopper[$h]|";
										&detail_logger;
										}
									}
								else
									{
									##### Auto-Alt-Dial if DNCC or DNCL are set to campaign auto-alt-dial statuses, insert lead into hopper as DNC status
									if ( ( ($auto_alt_dial_statuses[$i] =~ / DNCC /) && ($DNCC > 0) ) || ( ($auto_alt_dial_statuses[$i] =~ / DNCL /) && ($DNCL > 0) ) || ( ($auto_alt_dial_statuses[$i] =~ / TFHCCL /) && ($TFHCCL > 0) ) )
										{
										$stmtA = "INSERT INTO $vicidial_hopper (lead_id,campaign_id,status,user,list_id,gmt_offset_now,state,priority,source,vendor_lead_code) values('$leads_to_hopper[$h]','$campaign_id[$i]','DNC','','$lists_to_hopper[$h]','$gmt_to_hopper[$h]',\"$state_to_hopper[$h]\",'$hopperPRIORITY','$source_to_hopper[$h]',\"$vlc_to_hopper[$h]\");";
										$affected_rows = $dbhA->do($stmtA);
										$DBinserted = ($DBinserted + $affected_rows);
										if ($DBX) {print "LEAD INSERTED AS DNC: $affected_rows|$leads_to_hopper[$h]|\n";}
										if ($DB_detail) 
											{
											$detail_string = "|$campaign_id[$i]|$leads_to_hopper[$h]|$phone_to_hopper[$h]|$state_to_hopper[$h]|$gmt_to_hopper[$h]|$status_to_hopper[$h]|$modify_to_hopper[$h]|$user_to_hopper[$h]|$vlc_to_hopper[$h]|$source_to_hopper[$h]|$phone_code_to_hopper[$h]|$postal_code_to_hopper[$h]|DNC|";
											&detail_logger;
											}
										}
									else
										{
										$DNCskip++;
										}
									}
								}
							}
						$h_attempted++;
						}
					$h++;
					}
				if ($h_attempted > 0) 
					{
					$insert_end_output='';
					$insert_end_output .= "Attempted hopper inserts for this campaign:          $h_attempted    $now_date \n";
					$insert_end_output .= "     Leads inserted into the hopper:                 $DBinserted \n";
					$insert_end_output .= "     VLC Dup Check Rejected:                         $vlc_dup_check_SKIP_COUNT \n";
					$insert_end_output .= "     DNC lead skipped:                               $DNCskip \n";
					$insert_end_output .= "     Daily call count limit lead skipped:            $DCCLskip \n";
					$insert_end_output .= "     24-Hour call count limit lead skipped:          $TFHCCLskip \n";
					if ($DB) {print "$insert_end_output";}
					$insert_end_outputSQL = ",adapt_output=\"$hopper_begin_output\n$insert_end_output\"";
					}
				}
			}
		}
	# update debug output
	$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',debug_output=\"$hopper_begin_output\"$insert_end_outputSQL where campaign_id='$campaign_id[$i]' and server_ip='HOPPER';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DBX) {print "vicidial_campaign_stats_debug UPDATE: $affected_rows|$stmtA|\n";}

	$i++;
	}


$dbhA->disconnect();

if($DB)
	{
	if (length($ENDoutput) > 10)
		{
		print "\n";
		print "SUMMARY:     $i campaigns\n";
		print "$ENDoutput";
		}
	### calculate time to run script ###
	$secY = time();
	$secZ = ($secY - $secT);

	if (!$q) {print "DONE. Script execution time in seconds: $secZ\n";}
	}
exit 0;


##### SUBROUTINES #####
sub check_24hour_call_count
	{
	$passed_24hour_call_count=0;
	$limit_scopeSQL='';
	if ($TEMPcall_limit_24hour_scope =~ /CAMPAIGN_LISTS/) 
		{
		$limit_scopeCAMP='';
		$stmtY = "SELECT list_id FROM vicidial_lists where campaign_id='$TEMPcampaign_id';";
		$sthY = $dbhA->prepare($stmtY) or die "preparing: ",$dbhA->errstr;
		$sthY->execute or die "executing: $stmtY", $dbhA->errstr;
		$sthYrows=$sthY->rows;
		$rec_campLISTS=0;
		while ($sthYrows > $rec_campLISTS)
			{
			@aryY = $sthY->fetchrow_array;
			$limit_scopeCAMP .= "'$aryY[0]',";
			$rec_campLISTS++;
			}
		if (length($limit_scopeCAMP) < 2) {$limit_scopeCAMP="'1'";}
		else {chop($limit_scopeCAMP);}
		$limit_scopeSQL = "and list_id IN($limit_scopeCAMP)";
		}
	if ($TEMPcall_limit_24hour_method =~ /PHONE_NUMBER/)
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where phone_number=\"$temp_24hour_phone\" and phone_code=\"$temp_24hour_phone_code\" and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	else
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where lead_id='$TEMPlead_id' and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	if ($DB) {print "     Doing 24-Hour Call Count Check: $TEMPlead_id|$temp_24hour_phone_code|$temp_24hour_phone|$temp_24hour_state|$temp_24hour_postal_code - $TEMPcall_limit_24hour_method - $TEMPcall_limit_24hour_scope - $TEMPcall_limit_24hour|$TEMPcall_limit_24hour_scope\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$TFhourCOUNT=0;
	$TFhourSTATE='';
	$TFhourCOUNTRY='';
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$TFhourCOUNT =		($TFhourCOUNT + $aryA[0]);
		}
	$sthA->finish();

	if ($DBX) {print "     24-Hour Call Limit Count DEBUG:     $TFhourCOUNT|$stmtA|\n";}

	if ( ($TEMPcall_limit_24hour_override !~ /^DISABLED$/) && (length($TEMPcall_limit_24hour_override) > 0) ) 
		{
		$TFH_areacode = substr($temp_24hour_phone, 0, 3);
		$stmtY = "SELECT state,country FROM vicidial_phone_codes where country_code='$temp_24hour_phone_code' and areacode='$TFH_areacode';";
		$sthY = $dbhA->prepare($stmtY) or die "preparing: ",$dbhA->errstr;
		$sthY->execute or die "executing: $stmtY", $dbhA->errstr;
		$sthYrows=$sthY->rows;
		if ($sthYrows > 0)
			{
			@aryY = $sthY->fetchrow_array;
			$TFhourSTATE =		$aryY[0];
			$TFhourCOUNTRY =	$aryY[1];
			}
		$sthA->finish();

		$TEMP_TFhour_OR_entry='';
		$TFH_OR_method='state_areacode';
		$TFH_OR_postcode_field_match=0;
		$TFH_OR_state_field_match=0;
		$TFH_OR_postcode_state='';
		$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$TEMPcall_limit_24hour_override';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "$sthArows|$stmtA\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$TEMP_TFhour_OR_entry = $aryA[0];
			}
		$sthA->finish();

		if (length($TEMP_TFhour_OR_entry) > 2) 
			{
			@container_lines = split(/\n/,$TEMP_TFhour_OR_entry);
			$c=0;
			foreach(@container_lines)
				{
				$container_lines[$c] =~ s/;.*|\r|\t//gi;
				$container_lines[$c] =~ s/ => |=> | =>/=>/gi;
				if (length($container_lines[$c]) > 3)
					{
					# define core settings
					if ($container_lines[$c] =~ /^method/i)
						{
						#$container_lines[$c] =~ s/method=>//gi;
						$TFH_OR_method = $container_lines[$c];

						if ( ($TFH_OR_method =~ /state$/) && ($TFhourSTATE ne $temp_24hour_state) )
							{
							$TFH_OR_state_field_match=1;
							}
						if ( ($TFH_OR_method =~ /postcode/) && (length($temp_24hour_postal_code) > 0) )
							{
							if ($TFhourCOUNTRY == 'USA') 
								{
								$temp_24hour_postal_code =~ s/\D//gi;
								$temp_24hour_postal_code = substr($temp_24hour_postal_code,0,5);
								}
							if ($TFhourCOUNTRY == 'CAN') 
								{
								$temp_24hour_postal_code =~ s/[^a-zA-Z0-9]//gi;
								$temp_24hour_postal_code = substr($temp_24hour_postal_code,0,6);
								}
							$stmtA = "SELECT state FROM vicidial_postal_codes where postal_code='$temp_24hour_postal_code';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($DBX) {print "$sthArows|$stmtA\n";}
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$TFH_OR_postcode_state =		$aryA[0];
								$TFH_OR_postcode_field_match=1;
								}
							$sthA->finish();
							}
						}
					else
						{
						if ($container_lines[$c] =~ /^state/i)
							{
							$container_lines[$c] =~ s/state=>//gi;	# USA,GA,4
							@TEMP_state_ARY = split(/,/,$container_lines[$c]);
							
							if ($TFhourCOUNTRY eq $TEMP_state_ARY[0]) 
								{
								$TEMP_state_ARY[2] =~ s/\D//gi;
								if ( ($TFhourSTATE eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) )
									{
									if ($DB) {print "     24-Hour Call Count State Override Triggered: $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
									}
								if ( ($TFH_OR_postcode_state eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) && ($TFH_OR_postcode_field_match > 0) )
									{
									if ($DBX) {print "     24-Hour Call Count State Override Match(postcode $TFH_OR_postcode_state): $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									if ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)
										{
										if ($DBX) {print "          POSTCODE field override of override triggered: ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)\n";}
										$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
										}
									}
								if ( ($temp_24hour_state eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) && ($TFH_OR_state_field_match > 0) )
									{
									if ($DBX) {print "     24-Hour Call Count State Override Match(state $temp_24hour_state): $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									if ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)
										{
										if ($DBX) {print "          STATE field override of override triggered: ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)\n";}
										$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
										}
									}
								}
							}
						}
					}
				if ($DBX) {print "     24-Hour Call Count State Override DEBUG: |$container_lines[$c]|\n";}
				$c++;
				}
			}
		}

	if ( ($TFhourCOUNT > 0) && ($TFhourCOUNT >= $TEMPcall_limit_24hour) )
		{
		$TFHCCLlead=1;
		$TFHCCL++;
		$passed_24hour_call_count=0;
		if ($TFHCCLalt < 1) 
			{
			$stmtA = "UPDATE vicidial_list SET called_since_last_reset='D' where lead_id='$TEMPlead_id';";
			$affected_rows = $dbhA->do($stmtA);
			}
		if ($DBX) {print "Flagging 24-Hour Call Limit lead:     $affected_rows  $TEMPlead_id ($TFhourCOUNT >= $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	else
		{
		$passed_24hour_call_count=1;
		if ($DBX) {print "     24-Hour Call Limit check passed:     $TEMPlead_id ($TFhourCOUNT < $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	}

sub event_logger
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$VDHLOGfile")
				|| die "Can't open $VDHLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}

sub detail_logger
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(LDout, ">>$VDHDLOGfile")
				|| die "Can't open $VDHDLOGfile: $!\n";
		print LDout "$now_date|$detail_string|\n";
		close(LDout);
		}
	$detail_string='';
	}

sub aad_output
	{
	if ($SYSLOG > 0)
		{
		### open the log file for writing ###
		open(Aout, ">>$AADLOGfile") || die "Can't open $AADLOGfile: $!\n";
		print Aout "$now_date|$script|$aad_string\n";
		close(Aout);
		}
	$aad_string='';
	}

sub roundup 
	{
	my $n = shift;
	return(($n == int($n)) ? $n : int($n + 1))
	}
