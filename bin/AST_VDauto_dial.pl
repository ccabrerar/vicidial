#!/usr/bin/perl
#
# AST_VDauto_dial.pl version 2.14
#
# DESCRIPTION:
# Places auto_dial calls on the VICIDIAL dialer system
#
# SUMMARY:
# This program was designed for people using the Asterisk PBX with VICIDIAL
#
# For the client to use VICIDIAL, this program must be in the cron constantly
#
# For this program to work you need to have the "asterisk" MySQL database
# created and create the tables listed in the CONF_MySQL.txt file, also make sure
# that the machine running this program has read/write/update/delete access
# to that database
#
# It is recommended that you run this program on the local Asterisk machine
#
# This script is to run perpetually querying every second to place new phone
# calls from the vicidial_hopper based upon how many available agents there are
# and the value of the auto_dial_level setting in the campaign screen of the
# admin web page
#
# It is good practice to keep this program running by placing the associated
# KEEPALIVE script running every minute to ensure this program is always running
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG:
# 50125-1201 - Changed dial timeout to 120 seconds from 180 seconds
# 50317-0954 - Added duplicate check per cycle to account for DB lockups
# 50322-1302 - Added campaign custom callerid feature
# 50324-1353 - Added optional variable to record ring time thru separate diap prefix
# 50606-2308 - Added code to ensure no calls placed out on inactive campaign
# 50617-1248 - Added code to place LOGOUT entry in auto-paused user logs
# 50620-1349 - Added custom vdad transfer AGI extension per campaign
# 50810-1610 - Added database server variable definitions lookup
# 50810-1630 - Added max_vicidial_trunks server limiter on active lines
# 50812-0957 - Corrected max_trunks logic, added update of server every 25 sec
# 60120-1522 - Corrected time error for hour variable, caused agi problems
# 60427-1223 - Fixed Blended in/out CLOSER campaign issue
# 60608-1134 - Altered vac table field of call_type for IN OUT distinction
# 60612-1324 - Altered dead call section to accept BUSY detection from VD_hangup
#            - Altered dead call section to accept DISCONNECT detection from VD_hangup
# 60614-1142 - Added code to work with recycled leads, multi called_since_last_reset values
#            - Removed gmt lead validation because it is already done by VDhopper
# 60807-1438 - Changed to DBI
#            - Changed to use /etc/astguiclient.conf for configs
# 60814-1749 - Added option for no logging to file
# 60821-1546 - Added option to not dial phone_code per campaign
# 60824-1437 - Added available_only_ratio_tally option
# 61003-1353 - Added restrictions for server trunks
# 61113-1625 - Added code for clearing VDAC LIVE jams
# 61115-1725 - Added OUTBALANCE to call calculation for call_type for balance dialing
# 70111-1600 - Added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70115-1635 - Added initial auto-alt-dial functionality
# 70116-1619 - Added VDAD Ring-No-Answer Auto Alt Dial code
# 70118-1539 - Added user_group logging to vicidial_user_log
# 70131-1550 - Fixed Manual dialing trunk shortage bug
# 70205-1414 - Added code for last called date update
# 70207-1031 - Fixed Tally-only-available bug with customer hangups
# 70215-1123 - Added queue_log ABANDON logging
# 70302-1412 - Fixed max_vicidial_trunks update if set to 0
# 70320-1458 - Fixed several errors in calculating trunk shortage for campaigns
# 71029-1909 - Changed CLOSER-type campaign_id restriction
# 71030-2054 - Added hopper priority sorting
# 80227-0406 - added queue_priority
# 80525-1040 - Added IVR vac status compatibility for inbound calls
# 80713-0624 - Added vicidial_list_last_local_call_time field
# 80829-2359 - Added extended alt dial and dnc checkon all alt dial hopper insertions
# 80909-0845 - Added support for campaign-specific DNC lists
# 81013-2216 - Fixed improper deletion of auto_calls records
# 81020-0125 - Bug fixes from changes to auto_calls deletion changes
# 90124-0721 - Added parameter to ensure no auto-dial calls are placed for MANUAL campaigns
# 90202-0203 - Added outbound_autodial_active option to halt all dialing
# 90306-1845 - Added configurable calls-per-second option
# 90611-0554 - Bug fix for Manual dial calls and logging
# 90619-1948 - Format fixing
# 90630-2252 - Added Sangoma CDP pre-Answer call processing
# 90816-0057 - Changed default vicidial_log time to 0 from 1 second
# 90827-1227 - Added list_id logging in vicidial_log on NA calls
# 90907-0919 - Added LAGGED pause code update for paused agents, reduced logging if no issues
# 90909-0640 - Parked bug fix and code optimizations
# 90917-1432 - Fixed issue on high-volume systems with lagged agents
# 90924-0914 - Added List callerid override option
# 91026-1218 - Added AREACODE DNC option
# 91108-2122 - Added LAGGED PAUSEREASON QM entry for lagged agents
# 91123-1802 - Added outbound_autodial field, and exception for outbound-only agents on blended campaign
# 91213-1856 - Added queue_position to queue_log ABANDON records
# 100309-0551 - Added queuemetrics_loginout option
# 100327-1359 - Fixed LAGGED issues
# 100903-0041 - Changed lead_id max length to 10 digits
# 101111-1556 - Added source to vicidial_hopper inserts
# 101117-1656 - Added accounting for DEAD agent calls when in available-only-tally dialing
# 101207-0713 - Added more info to Originate for rare VDAC issue
# 110103-1227 - Added queuemetrics_loginout NONE option
# 110124-1134 - Small query fix for large queue_log tables
# 110224-1408 - Fixed trunk reservation bug
# 110224-1859 - Added compatibility with QM phone environment logging
# 110303-1710 - Added clearing of ring_callerid when vicidial_auto_calls deleted
# 110513-1745 - Added double-check for dial level difference target, and dial level and avail-only-tally features
# 110525-1940 - Allow for auto-dial IVR transfers
# 110602-0953 - Added dl_diff_target_method option
# 110723-1256 - Added extra debug for vac deletes and fix for long ring/drop time
# 110731-2127 - Added sections for handling MULTI_LEAD auto-alt-dial and na-call-url functions
# 110809-1516 - Added section for noanswer logging
# 110901-1125 - Added campaign areacode cid function
# 110922-1202 - Added logging of last calltime to campaign
# 120403-1839 - Changed auto-alt-dial multi-lead processing to use server max recording time value for time
# 120831-1503 - Added vicidial_dial_log outbound call logging
# 121124-2249 - Added Other Campaign DNC option
# 121129-1840 - Fix for issue #600
# 130706-2024 - Added disable_auto_dial system option
# 130811-1403 - Fix for issue #690
# 131016-0659 - Fix for disable_auto_dial system option
# 131122-1233 - Added several missing sthA->finish
# 131209-1557 - Added called_count logging
# 140426-1941 - Added pause_type to vicidial_agent_log
# 141113-1556 - Added concurrency check
# 141211-2134 - Added na_call_url list_id override option
# 151006-0936 - Changed campaign_cid_areacodes to operate with 2-5 digit areacodes
# 161102-1031 - Fixed QM partition problem
# 170313-1041 - Added CHAT option to inbound_queue_no_dial
# 170325-1106 - Added optional vicidial_drop_log logging
# 170527-2347 - Fix for rare inbound logging issue #1017
# 170915-1817 - Added support for per server routing prefix needed for Asterisk 13+
# 180130-1346 - Added inbound_no_agents_no_dial campaign options
# 180214-1559 - Added CID Group functionality
# 180301-1338 - Small changes for Inbound Queue No Dial
# 180812-1026 - Added code for scheduled_callbacks_auto_reschedule campaign feature
# 190709-2239 - Added Call Quota logging
# 191108-0922 - Added Dial Timeout Lead override function
# 200108-1315 - Added CID Group type of NONE
# 200122-1851 - Added code for CID Group auto-rotate feature
# 200825-0032 - Include live agents from other campaigns if they have the campaign Drop-InGroup selected and drop sec < 0
# 201122-0928 - Added code for dialy call count limits
# 201218-2224 - Added code for SHARED agent campaigns
# 210207-0952 - Added more logging, debug code and fixes for SHARED agent campaigns
# 210423-2241 - Fix for campaign ID underscore issue #1265
# 210606-1006 - Added TILTX features for pre-carrier call filtering
# 210713-1322 - Added call_limit_24hour feature support
# 210718-0359 - Fixes for 24-Hour Call Count Limits with standard Auto-Alt-Dialing
# 210719-1520 - Added additional state override methods for call_limit_24hour
# 210731-0952 - Added cid_group_id_two campaign option
# 210827-1044 - Fix for Extended auto-alt-dialing issue #1323
# 210901-1020 - Another fix for Extended auto-alt-dialing issue #1323
# 211022-1637 - Added incall_tally_threshold_seconds campaign feature
# 220118-0938 - Added $ADB auto-alt-dial extra debug output option, fix for extended auto-alt-dial issue #1337
# 220118-2206 - Added auto_alt_threshold campaign & list settings
# 220311-1920 - Added List CID Group Override option
# 220328-1310 - Small change made per Issue #1337
# 220623-1621 - Added List dial_prefix override
# 221221-2135 - Added enhanced_disconnect_logging=3 support, issue #1367
# 230204-2144 - Added ability to use ALT na_call_url entries
# 230215-1534 - Fix for AGENTDIRECT No-Agent Call URL calls, Issue #1396
# 230223-0826 - Fix for enhanced_disconnect_logging=3 issue
# 231116-0911 - Added hopper_hold_inserts option
# 231129-0901 - Added vicidial_phone_number_call_daily_counts updates/inserts
# 240219-1524 - Added daily_limit in-group parameter
# 240225-0951 - Added AUTONEXT hopper_hold_inserts option
# 240731-0814 - Fix for 2nd DB connection timing out while running
# 240917-1719 - Change multiple after-call processing to only run for active_voicemail_server
#

$build='240917-1719';
$script='AST_VDauto_dial';
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
		print "  [-t] = test\n";
		print "  [-debug] = verbose debug messages\n";
		print "  [-debugX] = Extra verbose debug messages\n";
		print "  [--alt-debug] = Extra verbose Alt-Dialing debug messages\n";
		print "  [--delay=XXX] = delay of XXX seconds per loop, default 2.5 seconds\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--delay=/i)
			{
			@data_in = split(/--delay=/,$args);
			$loop_delay = $data_in[1];
			print "     LOOP DELAY OVERRIDE!!!!! = $loop_delay seconds\n\n";
			$loop_delay = ($loop_delay * 1000);
			}
		else
			{
			$loop_delay = '2500';
			}
		if ($args =~ /-debug/i)
			{
			$DB=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /-debugX/i)
			{
			$DBX=1; # Extra Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--alt-debug/i)
			{
			$ADB=1; # Alt-dial Debug flag, set to 0 for no alt-dial debug messages.
			}
		if ($args =~ /-t/i)
			{
			$TEST=1;
			$T=1;
			}
		}
	}
else
	{
	print "no command line options set\n";
	$loop_delay = '2500';
	$DB=1;
	}
### end parsing run-time options ###


# constants
$US='__';
$MT[0]='';
$RECcount=''; ### leave blank for no REC count
$RECprefix='7'; ### leave blank for no REC prefix
$useJAMdebugFILE='1'; ### leave blank for no Jam call debug file writing
$max_vicidial_trunks=0; ### setting a default value for max_vicidial_trunks
$run_check=1; # concurrency check
$new_agent_multicampaign=1; # new process for handling multi-campaign agents

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

	&get_time_now;	# update time/date variables

if (!$VDADLOGfile) {$VDADLOGfile = "$PATHlogs/vdautodial.$year-$mon-$mday";}
if (!$JAMdebugFILE) {$JAMdebugFILE = "$PATHlogs/vdad-JAM.$year-$mon-$mday";}
if (!$AADLOGfile) {$AADLOGfile = "$PATHlogs/auto-alt-dial.$year-$mon-$mday";}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use Time::Local;
use POSIX;
use DBI;

### connect to MySQL database defined in the conf file
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;
$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,vd_server_logs,vicidial_recording_limit,asterisk_version,routing_prefix FROM servers where server_ip = '$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DBtelnet_host	=			$aryA[0];
	$DBtelnet_port	=			$aryA[1];
	$DBASTmgrUSERNAME	=		$aryA[2];
	$DBASTmgrSECRET	=			$aryA[3];
	$DBASTmgrUSERNAMEupdate	=	$aryA[4];
	$DBASTmgrUSERNAMElisten	=	$aryA[5];
	$DBASTmgrUSERNAMEsend	=	$aryA[6];
	$DBmax_vicidial_trunks	=	$aryA[7];
	$DBanswer_transfer_agent=	$aryA[8];
	$DBSERVER_GMT		=		$aryA[9];
	$DBext_context	=			$aryA[10];
	$DBvd_server_logs =			$aryA[11];
	$DBvicidial_recording_limit = $aryA[12];
	$asterisk_version = 		$aryA[13];
	$routing_prefix = 			$aryA[14];
	if ($DBtelnet_host)				{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)				{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)			{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)			{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMElisten)	{$ASTmgrUSERNAMElisten = $DBASTmgrUSERNAMElisten;}
	if ($DBASTmgrUSERNAMEsend)		{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	$max_vicidial_trunks = $DBmax_vicidial_trunks;
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if (length($DBSERVER_GMT) > 0)	{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)				{$ext_context = $DBext_context;}
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
	else {$SYSLOG = '0';}
	%ast_ver_str = parse_asterisk_version($asterisk_version);
	}
$sthA->finish();

$event_string='LOGGED INTO MYSQL SERVER ON 1 CONNECTION|';
&event_logger;

#############################################
##### START QUEUEMETRICS LOGGING LOOKUP #####
$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,outbound_autodial_active,queuemetrics_loginout,queuemetrics_addmember_enabled,queuemetrics_pause_type,enable_drop_lists,call_quota_lead_ranking,timeclock_end_of_day,allow_shared_dial,call_limit_24hour,hopper_hold_inserts,active_voicemail_server FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$enable_queuemetrics_logging =		$aryA[0];
	$queuemetrics_server_ip	=			$aryA[1];
	$queuemetrics_dbname =				$aryA[2];
	$queuemetrics_login=				$aryA[3];
	$queuemetrics_pass =				$aryA[4];
	$queuemetrics_log_id =				$aryA[5];
	$outbound_autodial_active =			$aryA[6];
	$queuemetrics_loginout =			$aryA[7];
	$queuemetrics_addmember_enabled =	$aryA[8];
	$queuemetrics_pause_type =			$aryA[9];
	$enable_drop_lists =				$aryA[10];
	$SScall_quota_lead_ranking =		$aryA[11];
	$timeclock_end_of_day =				$aryA[12];
	$allow_shared_dial =				$aryA[13];
	$SScall_limit_24hour =				$aryA[14];
	$SShopper_hold_inserts =			$aryA[15];
	$active_voicemail_server =			$aryA[16];
	}
$sthA->finish();
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

### Grab system_settings values from the database
$stmtA = "SELECT use_non_latin FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$non_latin = 						$aryA[0];
	}
$sthA->finish();

if ($non_latin > 0) 
	{
	$stmtA = "SET NAMES 'UTF8';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthA->finish();
	}

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug SET campaign_id='-SHARE-',server_ip='$VARserver_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();


$one_day_interval = 12;		# 1 month loops for one year
while($one_day_interval > 0)
	{
	$endless_loop=5760000;		# 30 days minutes at XXX seconds per loop
	$stat_count=1;

	while($endless_loop > 0)
		{
		&get_time_now;

		$VDADLOGfile = "$PATHlogs/vdautodial.$year-$mon-$mday";

	###############################################################################
	###### first figure out how many calls should be placed for each campaign per server
	###############################################################################
		@DBlive_user=@MT;
		@DBlive_server_ip=@MT;
		@DBlive_campaign=@MT;
		@DBlive_campaign_rank=@MT;
		@DBlive_campaign_sort=@MT;
		@DBlive_campaign_sorted=@MT;
		@DBlive_campaign_level=@MT;
		@DBlive_dial_campaign=@MT;
		@DBlive_conf_exten=@MT;
		@DBlive_status=@MT;
		@DBlive_call_id=@MT;
		@DBlive_last_call_epoch=@MT;
		@DBcampaigns=@MT;
		@DBIPaddress=@MT;
		@DBIPcampaign=@MT;
		@DBIPcampaign_sort=@MT;
		@DBIPcampaign_sorted=@MT;
		@DBIPactive=@MT;
		@DBIPvdadexten=@MT;
		@DBIPcount=@MT;
		@DBIPcountT=@MT;
		@DBIPACTIVEcount=@MT;
		@DBIPINCALLcount=@MT;
		@DBIPINCALLthresh=@MT;
		@DBIPINCALLdiff=@MT;
		@DBIPINCALLdeadT=@MT;
		@DBIPDEADcount=@MT;
		@DBIPadlevel=@MT;
		@DBIPdialtimeout=@MT;
		@DBIPdialprefix=@MT;
		@DBIPcampaigncid=@MT;
		@DBIPexistcalls=@MT;
		@DBIPexistcalls_IN=@MT;
		@DBIPexistcalls_IN_ALL=@MT;
		@DBIPexistchats_IN_LIVE=@MT;
		@DBIPexistcalls_IN_LIVE=@MT;
		@DBIPexistcalls_QUEUE_LIVE=@MT;
		@DBIPexistcalls_OUT=@MT;
		@DBIPgoalcalls=@MT;
		@DBIPmakecalls=@MT;
		@DBIPlivecalls=@MT;
		@DBIPclosercamp=@MT;
		@DBIPomitcode=@MT;
		@DBIPautoaltdial=@MT;
		@DBIPtrunk_shortage=@MT;
		@DBIPcampaign_ready_agents=@MT;
		@DBIPold_trunk_shortage=@MT;
		@DBIPserver_trunks_limit=@MT;
		@DBIPserver_trunks_other=@MT;
		@DBIPserver_trunks_allowed=@MT;
		@DBIPqueue_priority=@MT;
		@DBIPdial_method=@MT;
		@DBIPuse_custom_cid=@MT;
		@DBIPcid_group_id=@MT;
		@DBIPcid_group_id_two=@MT;
		@DBIPinbound_queue_no_dial=@MT;
		@DBIPinbound_no_agents_no_dial=@MT;
		@DBIPinbound_no_agents_no_dial_threshold=@MT;
		@DBIPinbound_no_agents_no_dial_trigger=@MT;
		@DBIPinand_agent_ready_count=@MT;
		@DBIPinand_users=@MT;
		@DBIPavailable_only_tally=@MT;
		@DBIPavailable_only_tally_threshold=@MT;
		@DBIPavailable_only_tally_threshold_agents=@MT;
		@DBIPincall_tally_threshold_seconds=@MT;
		@DBIPdial_level_threshold=@MT;
		@DBIPdial_level_threshold_agents=@MT;
		@DBIPadaptive_dl_diff_target=@MT;
		@DBIPdl_diff_target_method=@MT;
		@DBIPscheduled_callbacks_auto_reschedule=@MT;
		@DBIPcall_quota_lead_ranking=@MT;
		@DBIPdial_timeout_lead_container=@MT;
		@DBIPdial_timeout_lead_container_entry=@MT;
		@DBIPdrop_call_seconds=@MT;
		@DBIPdrop_action=@MT;
		@DBIPdrop_inbound_group=@MT;
		@DBshare_camp=@MT;
		@DBshare_camp_rank=@MT;
		@DBshare_dial_level=@MT;
		$active_line_counter=0;
		$user_counter=0;
		$user_campaigns = '|';
		$user_campaignsSQL = "''";
		$user_campaigns_counter = 0;
		$user_campaignIP = '|';
		$user_CIPct = 0;
		$active_agents = "'READY','QUEUE','INCALL','DONE'";
		$lists_update = '';
		$LUcount=0;
		$campaigns_update = '';
		$CPcount=0;
		$shared_dial_camp_override=0;

		##### Get maximum calls per second that this process can send out
		$stmtC = "SELECT outbound_calls_per_second,vicidial_recording_limit FROM servers where server_ip='$server_ip';";
		$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
		$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
		$sthCrows=$sthC->rows;
		if ($sthCrows > 0)
			{
			@aryC = $sthC->fetchrow_array;
			$outbound_calls_per_second =	$aryC[0];
			$DBvicidial_recording_limit =	$aryC[1];
			}
		$sthC->finish();

		if ( ($outbound_calls_per_second > 0) && ($outbound_calls_per_second < 201) )
			{$per_call_delay = (1000 / $outbound_calls_per_second);}
		else
			{$per_call_delay = '25';}

		$event_string="SERVER CALLS PER SECOND MAXIMUM SET TO: $outbound_calls_per_second |$per_call_delay|";
		&event_logger;



		#############################################
		##### Check if auto-dialing is enabled
		$stmtA = "SELECT outbound_autodial_active,noanswer_log,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass,tables_use_alt_log_db,disable_auto_dial,allow_shared_dial FROM system_settings;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$outbound_autodial_active =		$aryA[0];
			$noanswer_log =					$aryA[1];
			$alt_log_server_ip =			$aryA[2];
			$alt_log_dbname =				$aryA[3];
			$alt_log_login =				$aryA[4];
			$alt_log_pass =					$aryA[5];
			$tables_use_alt_log_db =		$aryA[6];
			$disable_auto_dial =			$aryA[7];
			$allow_shared_dial =			$aryA[8];
			}
		$sthA->finish();

		#############################################
		##### Gather SHARED campaigns info
		$debug_shared_output='';
		$initial_debug_shared_output='';
		$shared_SQL='';
		$shared_callsTOTAL=0;
		$shared_callsSERVER=0;
		$shared_dropsTOTAL=0;
		$shared_dropsSERVER=0;
		$shared_agentsTOTAL=0;
		$shared_agentsSERVER=0;
		$share_dial_level_total=0;
		$share_dial_level_average=0;
		$share_dial_max=0;
		$shared_campaigns_list='|';
		$agent_shared_campaigns_ct=0;
		if ($allow_shared_dial > 0) 
			{
			$stmtA = "SELECT campaign_id,drop_inbound_group,shared_dial_rank,auto_dial_level FROM vicidial_campaigns where active='Y' and dial_method IN('SHARED_RATIO','SHARED_ADAPT_HARD_LIMIT','SHARED_ADAPT_TAPERED','SHARED_ADAPT_AVERAGE') order by shared_dial_rank desc;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$DBshare_camp[$rec_count] =			$aryA[0];
				$DBshare_camp_rank[$rec_count] =	$aryA[2];
				$DBshare_dial_level[$rec_count] =	$aryA[3];
				$shared_campaigns_list .= "$aryA[0]|";
				if (length($DBshare_camp_rank[$rec_count])<2) {$DBshare_camp_rank[$rec_count] = "0$DBshare_camp_rank[$rec_count]";}
				$shared_SQL .= "'$aryA[0]','$aryA[1]',";
				$debug_shared_output .= "$aryA[0]   Drop-In-Group: $aryA[1]   Shared-Rank: $DBshare_camp_rank[$rec_count]   Dial Level: $DBshare_dial_level[$rec_count]\n";
				$rec_count++;
				}
			chop($shared_SQL);
			if (length($shared_SQL)<2)
				{$shared_SQL="'-NONE-'";}

			$stmtA = "SELECT count(*) FROM vicidial_shared_drops where campaign_id IN($shared_SQL) and drop_time > \"$FVtsSQLdate\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_dropsTOTAL = $aryA[0];
				}
			$stmtA = "SELECT count(*) FROM vicidial_auto_calls where campaign_id IN($shared_SQL);";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_callsTOTAL = ($aryA[0] + $shared_dropsTOTAL);
				}

			$stmtA = "SELECT count(*) FROM vicidial_shared_drops where campaign_id IN($shared_SQL) and server_ip='$VARserver_ip' and drop_time > \"$FVtsSQLdate\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_dropsSERVER = $aryA[0];
				}
			$stmtA = "SELECT count(*) FROM vicidial_auto_calls where campaign_id IN($shared_SQL) and server_ip='$VARserver_ip';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_callsSERVER = ($aryA[0] + $shared_dropsSERVER);
				}

			$stmtA = "SELECT count(*) FROM vicidial_live_agents where dial_campaign_id IN($shared_SQL) and status IN($active_agents) and outbound_autodial='Y' and last_update_time > '$BDtsSQLdate' order by last_call_time";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_agentsTOTAL = $aryA[0];
				}

			$stmtA = "SELECT count(*) FROM vicidial_live_agents where dial_campaign_id IN($shared_SQL) and status IN($active_agents) and outbound_autodial='Y' and server_ip='$server_ip' and last_update_time > '$BDtsSQLdate' order by last_call_time";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$shared_agentsSERVER = $aryA[0];
				}

			$event_string="SHARED-AGENT CAMPAIGNS ON SYSTEM: $rec_count   TOTAL CALLS: $shared_callsTOTAL($shared_dropsTOTAL)   SERVER CALLS: $shared_callsSERVER($shared_dropsSERVER)\n   TOTAL AGENTS: $shared_agentsTOTAL   SERVER AGENTS: $shared_agentsSERVER";
			$debug_shared_output .= "$event_string\n";
			&event_logger;

			$initial_debug_shared_output = $debug_shared_output;
			}
		$debug_shared_output='';

		##### Get a listing of the users that are active and ready to take calls
		##### Also get a listing of the campaigns and campaigns/serverIP that will be used
		$stmtA = "SELECT user,server_ip,campaign_id,conf_exten,status,callerid,dial_campaign_id,UNIX_TIMESTAMP(last_call_time) FROM vicidial_live_agents where status IN($active_agents) and outbound_autodial='Y' and server_ip='$server_ip' and last_update_time > '$BDtsSQLdate' order by last_call_time";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$DBlive_user[$user_counter] =		$aryA[0];
			$DBlive_server_ip[$user_counter] =	$aryA[1];
			$DBlive_campaign[$user_counter] =	$aryA[2];
			$DBlive_conf_exten[$user_counter] =	$aryA[3];
			$DBlive_status[$user_counter] =		$aryA[4];
			$DBlive_call_id[$user_counter] =	$aryA[5];
			$DBlive_dial_campaign[$user_counter] =		$aryA[6];
			$DBlive_last_call_epoch[$user_counter] =	$aryA[7];
			$DBlive_campaign_rank[$user_counter] =		0;
			$DBlive_campaign_sort[$user_counter] =		0;
			$DBlive_campaign_level[$user_counter] =		0;

			if (length($DBlive_dial_campaign[$user_counter]) > 0)
				{
				if ($DBlive_dial_campaign[$user_counter] ne $DBlive_campaign[$user_counter]) 
					{
					$shared_dial_camp_override++;
					if ($DBX) {print "Shared-Dial-Agent Dial-Campaign Override: $shared_dial_camp_override/$rec_count - $DBlive_user[$user_counter] ($DBlive_campaign[$user_counter] -> $DBlive_dial_campaign[$user_counter])\n";}
					$debug_shared_output .= "Agent Dial-Campaign Override: $shared_dial_camp_override/$rec_count - $DBlive_user[$user_counter] ($DBlive_campaign[$user_counter] -> $DBlive_dial_campaign[$user_counter])\n";
					$DBlive_campaign[$user_counter] = $DBlive_dial_campaign[$user_counter];
					}
				else
					{
					$debug_shared_output .= "Agent Dial-Campaign No-Change: $rec_count - $DBlive_user[$user_counter] ($DBlive_campaign[$user_counter] -> $DBlive_dial_campaign[$user_counter])\n";
					}
				}
			$temp_ct=0;
			foreach(@DBshare_camp)
				{
				if ($DBlive_campaign[$user_counter] eq $DBshare_camp[$temp_ct]) 
					{
					$DBlive_campaign_rank[$user_counter] = $DBshare_camp_rank[$temp_ct];
					$DBlive_campaign_level[$user_counter] = $DBshare_dial_level[$temp_ct];
					}
				$temp_ct++;
				}
			if ($user_campaigns !~ /\|$DBlive_campaign[$user_counter]\|/i)
				{
				if ($campaigns_update !~ /'$DBlive_campaign[$user_counter]'/) {$campaigns_update .= "'$DBlive_campaign[$user_counter]',"; $CPcount++;}
				$user_campaigns .= "$DBlive_campaign[$user_counter]|";
				$user_campaignsSQL .= ",'$DBlive_campaign[$user_counter]'";
				if ( ($allow_shared_dial > 0) && ($shared_campaigns_list =~ /\|$DBlive_campaign[$user_counter]\|/i) )
					{
					$share_dial_level_total = ($share_dial_level_total + $DBlive_campaign_level[$user_counter]);
					$agent_shared_campaigns_ct++;
					}
				$DBcampaigns[$user_campaigns_counter] = $DBlive_campaign[$user_counter];
				$user_campaigns_counter++;
				}
			if ($user_campaignIP !~ /\|$DBlive_campaign[$user_counter]__$DBlive_server_ip[$user_counter]\|/i)
				{
				$user_campaignIP .= "$DBlive_campaign[$user_counter]__$DBlive_server_ip[$user_counter]|";
				$DBIPcampaign[$user_CIPct] = "$DBlive_campaign[$user_counter]";
				$DBIPaddress[$user_CIPct] = "$DBlive_server_ip[$user_counter]";
				$DBIPcampaign_sort[$user_CIPct] = $DBlive_campaign_rank[$user_counter].'---'.$DBlive_last_call_epoch[$user_counter].'---'.$DBlive_campaign[$user_counter].'---'.$user_CIPct;
				if ($DBX) {print "Debug agent campaign sort: $DBIPcampaign_sort[$user_CIPct]\n";}
				$user_CIPct++;
				}
			$DBlive_campaign_sort[$user_counter] = $DBlive_campaign_rank[$user_counter].'_'.$DBlive_last_call_epoch[$user_counter].'_'.$DBlive_campaign[$user_counter].'_'.$user_counter;
			if ($DBX) {print "Debug live agent sort: $DBlive_campaign_sort[$user_counter]\n";}
			$user_counter++;
			$rec_count++;
			}
		$sthA->finish();

		### see how many total VDAD calls are going on right now for max limiter
		$temp_dropsSERVER=0;
		if ($allow_shared_dial > 0) 
			{
			$stmtA = "SELECT count(*) FROM vicidial_shared_drops where status IN('SENT','RINGING','LIVE','XFER','CLOSER','IVR') and server_ip='$VARserver_ip' and drop_time > \"$FVtsSQLdate\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$temp_dropsSERVER = $aryA[0];
				}
			}
		$stmtA = "SELECT count(*) FROM vicidial_auto_calls where server_ip='$server_ip' and status IN('SENT','RINGING','LIVE','XFER','CLOSER','IVR');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$active_line_counter = ($aryA[0] + $temp_dropsSERVER);
			}
		$sthA->finish();

		$event_string="LIVE AGENTS LOGGED IN: $user_counter   ACTIVE CALLS: $active_line_counter";
		&event_logger;

		$stmtA = "UPDATE vicidial_campaign_server_stats set local_trunk_shortage='0' where server_ip='$server_ip' and campaign_id NOT IN($user_campaignsSQL);";
		$UVCSSaffected_rows = $dbhA->do($stmtA);
		if ($UVCSSaffected_rows > 0)
			{
			$event_string="OLD TRUNK SHORTS CLEARED: $UVCSSaffected_rows |$user_campaignsSQL|";
			&event_logger;
			}

		### Calculate maximum share campaigns total calls and log debug shared campaign output
		if ($allow_shared_dial > 0) 
			{
			if ( ($user_campaigns_counter > 0) && ($share_dial_level_total > 0) ) 
				{
				$share_dial_level_average = ($share_dial_level_total / $agent_shared_campaigns_ct);
				$share_dial_max = ($share_dial_level_average * $shared_agentsTOTAL);
				$share_dial_max = ceil($share_dial_max);
				}
			$temp_max_warning='';
			if ($shared_callsTOTAL > $share_dial_max) 
				{
				$max_debug_string='';
				if ($allow_shared_dial > 2) 
					{
					$stmtA = "SELECT callerid,server_ip,campaign_id,status,lead_id,phone_number,call_time,call_type FROM vicidial_shared_drops where campaign_id IN($shared_SQL) and drop_time > \"$FVtsSQLdate\";";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$temp_ct=0;
					while ($sthArows > $temp_ct)
						{
						@aryA = $sthA->fetchrow_array;
						$temp_ct++;
						$max_debug_string .= "SD $temp_ct $aryA[0] $aryA[1] $aryA[2] $aryA[3] $aryA[4] $aryA[5] $aryA[6] $aryA[7]\n";
						}
					$stmtA = "SELECT callerid,server_ip,campaign_id,status,lead_id,phone_number,call_time,call_type FROM vicidial_auto_calls where campaign_id IN($shared_SQL);";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$temp_ct=0;
					while ($sthArows > $temp_ct)
						{
						@aryA = $sthA->fetchrow_array;
						$temp_ct++;
						$max_debug_string .= "   $temp_ct $aryA[0] $aryA[1] $aryA[2] $aryA[3] $aryA[4] $aryA[5] $aryA[6] $aryA[7]\n";
						}
					}

				$temp_max_warning = "\nSHARED MAX OVER LIMIT!  $shared_callsTOTAL > $share_dial_max \n$max_debug_string";
				}

			$event_string="SHARED-AGENT CAMPAIGNS DIAL LEVEL LIMITS:    Shared campaigns with active agents: $agent_shared_campaigns_ct   DL AVG: $share_dial_level_average   SHARED MAX CALLS: $share_dial_max$temp_max_warning";
			$initial_debug_shared_output .= "$event_string\n";
			&event_logger;

			# update debug output
			$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',debug_output='$initial_debug_shared_output' where campaign_id='-SHARE-' and server_ip='$VARserver_ip';";
			$affected_rows = $dbhA->do($stmtA);

			if ($allow_shared_dial > 1) 
				{
				$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$shared_agentsSERVER',total_calls='$shared_callsSERVER',debug_output='BUILD: $build\n$initial_debug_shared_output',campaign_id='-SHARE-',server_ip='$VARserver_ip';";
				$affected_rows = $dbhA->do($stmtA);
				}
			}

		### sort campaigns by shared campaign rank, then last agent call handle time
		@DBIPcampaign_sorted = sort { $a cmp $b } @DBIPcampaign_sort;

		$user_CIPct_sort = 0;
		foreach(@DBIPcampaign_sorted)
			{
			@camp_sort_line = split(/---/,$DBIPcampaign_sorted[$user_CIPct_sort]);
			$user_CIPct = $camp_sort_line[3];
			$user_last_call_epoch_CIP = $camp_sort_line[1];
			$debug_string='';
			$user_counter=0;
			foreach(@DBlive_campaign)
				{
				if ( ($DBlive_campaign[$user_counter] =~ /$DBIPcampaign[$user_CIPct]/i) && (length($DBlive_campaign[$user_counter]) == length($DBIPcampaign[$user_CIPct])) && ($DBlive_server_ip[$user_counter] =~ /$DBIPaddress[$user_CIPct]/i) )
					{
					$DBIPcount[$user_CIPct]++;
					$DBIPACTIVEcount[$user_CIPct] = ($DBIPACTIVEcount[$user_CIPct] + 0);
					$DBIPINCALLcount[$user_CIPct] = ($DBIPINCALLcount[$user_CIPct] + 0);
					$DBIPDEADcount[$user_CIPct] = ($DBIPDEADcount[$user_CIPct] + 0);
					if ($DBlive_status[$user_counter] =~ /READY|DONE/)
						{
						$DBIPACTIVEcount[$user_CIPct]++;
						}
					else
						{
						$stmtA = "SELECT count(*) FROM vicidial_auto_calls where callerid='$DBlive_call_id[$user_counter]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							if ($aryA[0] > 0)
								{
								$DBIPINCALLcount[$user_CIPct]++;
								}
							else
								{
								$DBIPDEADcount[$user_CIPct]++;
								}
							}
						else
							{
							$DBIPINCALLcount[$user_CIPct]++;
							}
						$sthA->finish();
						}
					}
				$user_counter++;
				}

			### get count of READY-status agents in this campaign
			$stmtA = "SELECT count(*) FROM vicidial_live_agents where ( (campaign_id='$DBIPcampaign[$user_CIPct]') or (dial_campaign_id='$DBIPcampaign[$user_CIPct]') ) and server_ip='$server_ip' and status='READY';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPcampaign_ready_agents[$user_CIPct] =		$aryA[0];
				}
			$sthA->finish();

			### check for vicidial_campaign_server_stats record, if non present then create it
			$stmtA = "SELECT local_trunk_shortage FROM vicidial_campaign_server_stats where campaign_id='$DBIPcampaign[$user_CIPct]' and server_ip='$server_ip';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPold_trunk_shortage[$user_CIPct] =		$aryA[0];
				$rec_count++;
				}
			$sthA->finish();

			if ($rec_count < 1)
				{
				$stmtA = "INSERT INTO vicidial_campaign_server_stats SET local_trunk_shortage='0', server_ip='$server_ip',campaign_id='$DBIPcampaign[$user_CIPct]';";
				$affected_rows = $dbhA->do($stmtA);

				$DBIPold_trunk_shortage[$user_CIPct]=0;

				$event_string="VCSS ENTRY INSERTED: $affected_rows";
				$debug_string .= "$event_string\n";
				&event_logger;
				}

			$DBIPserver_trunks_limit[$user_CIPct] = '';
			$DBIPserver_trunks_other[$user_CIPct] = 0;
			$DBIPserver_trunks_allowed[$user_CIPct] = $max_vicidial_trunks;
			### check for vicidial_server_trunks record
			$stmtA = "SELECT dedicated_trunks FROM vicidial_server_trunks where campaign_id='$DBIPcampaign[$user_CIPct]' and server_ip='$server_ip' and trunk_restriction='MAXIMUM_LIMIT';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPserver_trunks_limit[$user_CIPct] =		$aryA[0];
				$rec_count++;
				}
			$sthA->finish();

			$stmtA = "SELECT sum(dedicated_trunks) FROM vicidial_server_trunks where campaign_id NOT IN('$DBIPcampaign[$user_CIPct]') and server_ip='$server_ip';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPserver_trunks_other[$user_CIPct] =		$aryA[0];
				$rec_count++;
				}
			$sthA->finish();

			$DBIPserver_trunks_allowed[$user_CIPct] = ($max_vicidial_trunks - $DBIPserver_trunks_other[$user_CIPct]);


			### grab the dial_level and multiply by active agents to get your goalcalls
			$DBIPadlevel[$user_CIPct]=0;
			$stmtA = "SELECT auto_dial_level,local_call_time,dial_timeout,dial_prefix,campaign_cid,active,campaign_vdad_exten,closer_campaigns,omit_phone_code,available_only_ratio_tally,auto_alt_dial,campaign_allow_inbound,queue_priority,dial_method,use_custom_cid,inbound_queue_no_dial,available_only_tally_threshold,available_only_tally_threshold_agents,dial_level_threshold,dial_level_threshold_agents,adaptive_dl_diff_target,dl_diff_target_method,inbound_no_agents_no_dial_container,inbound_no_agents_no_dial_threshold,cid_group_id,scheduled_callbacks_auto_reschedule,call_quota_lead_ranking,dial_timeout_lead_container,drop_call_seconds,drop_action,drop_inbound_group,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,cid_group_id_two,incall_tally_threshold_seconds,auto_alt_threshold,hopper_hold_inserts FROM vicidial_campaigns where campaign_id='$DBIPcampaign[$user_CIPct]'";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			$active_only=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPadlevel[$user_CIPct] =			$aryA[0];
				$DBIPcalltime[$user_CIPct] =		$aryA[1];
				$DBIPdialtimeout[$user_CIPct] =		$aryA[2];
				$DBIPdialprefix[$user_CIPct] =		$aryA[3];
				$DBIPcampaigncid[$user_CIPct] =		$aryA[4];
				$DBIPactive[$user_CIPct] =			$aryA[5];
				$DBIPvdadexten[$user_CIPct] =		$aryA[6];
				$DBIPclosercamp[$user_CIPct] =		$aryA[7];
				$omit_phone_code =					$aryA[8];
				if ($omit_phone_code =~ /Y/) {$DBIPomitcode[$user_CIPct] = 1;}
				else {$DBIPomitcode[$user_CIPct] = 0;}
				$DBIPavailable_only_tally[$user_CIPct] =		$aryA[9];
				$DBIPautoaltdial[$user_CIPct] =					$aryA[10];
				$DBIPcampaign_allow_inbound[$user_CIPct] =		$aryA[11];
				$DBIPqueue_priority[$user_CIPct] =				$aryA[12];
				$DBIPdial_method[$user_CIPct] =					$aryA[13];
				$DBIPuse_custom_cid[$user_CIPct] =				$aryA[14];
				$DBIPinbound_queue_no_dial[$user_CIPct] =		$aryA[15];
				$DBIPavailable_only_tally_threshold[$user_CIPct] = $aryA[16];
				$DBIPavailable_only_tally_threshold_agents[$user_CIPct] = $aryA[17];
				$DBIPdial_level_threshold[$user_CIPct] =		$aryA[18];
				$DBIPdial_level_threshold_agents[$user_CIPct] = $aryA[19];
				$DBIPadaptive_dl_diff_target[$user_CIPct] =		$aryA[20];
				$DBIPdl_diff_target_method[$user_CIPct] =		$aryA[21];
				$DBIPinbound_no_agents_no_dial[$user_CIPct] =	$aryA[22];
				$DBIPinbound_no_agents_no_dial_threshold[$user_CIPct] =	$aryA[23];
				$DBIPcid_group_id[$user_CIPct] =	$aryA[24];
				$DBIPscheduled_callbacks_auto_reschedule[$user_CIPct] =	$aryA[25];
				$DBIPcall_quota_lead_ranking[$user_CIPct] =	$aryA[26];
				$DBIPdial_timeout_lead_container[$user_CIPct] =	$aryA[27];
				$DBIPdial_timeout_lead_container_entry[$user_CIPct]='';
				$DBIPdrop_call_seconds[$user_CIPct] =	$aryA[28];
				$DBIPdrop_action[$user_CIPct] =			$aryA[29];
				$DBIPdrop_inbound_group[$user_CIPct] =	$aryA[30];
				$DBIPcall_limit_24hour_method[$user_CIPct] =	$aryA[31];
				$DBIPcall_limit_24hour_scope[$user_CIPct] =		$aryA[32];
				$DBIPcall_limit_24hour[$user_CIPct] =			$aryA[33];
				$DBIPcall_limit_24hour_override[$user_CIPct] =	$aryA[34];
				$DBIPcid_group_id_two[$user_CIPct] =	$aryA[35];
				$DBIPincall_tally_threshold_seconds[$user_CIPct] =	$aryA[36];
				$DBIPauto_alt_threshold[$user_CIPct] =			$aryA[37];
				$DBIPhopper_hold_inserts[$user_CIPct] =			$aryA[38];
				if ($SShopper_hold_inserts < 1) {$DBIPhopper_hold_inserts[$user_CIPct] = 'DISABLED';}

				# check for Dial Timeout Lead override
				if ( (length($DBIPdial_timeout_lead_container[$user_CIPct]) > 1) && ($DBIPdial_timeout_lead_container[$user_CIPct] !~ /^DISABLED$/i) )
					{
					# Gather settings container for Call Quota Lead Ranking
					$stmtC = "SELECT container_entry FROM vicidial_settings_containers where container_id='$DBIPdial_timeout_lead_container[$user_CIPct]';";
					$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
					$sthCrows=$sthC->rows;
					if ($sthCrows > 0)
						{
						@aryC = $sthC->fetchrow_array;
						$DBIPdial_timeout_lead_container_entry[$user_CIPct] = $aryC[0];
						$DBIPdial_timeout_lead_container_entry[$user_CIPct] =~ s/\r|\t|\'|\"|\\//gi;
						}
					$sthC->finish();
					}

				$DBIPINCALLdiff[$user_CIPct]=0;
				$DBIPINCALLdeadT[$user_CIPct]=0;

				# If Agent In-Call Tally Seconds Threshold is enabled, find the number of agents INCALL at-or-below the incall_tally_threshold_seconds
				if ($DBIPincall_tally_threshold_seconds[$user_CIPct] > 0)
					{
					$all_callerids='';
					$stmtC = "SELECT callerid from vicidial_auto_calls;";
					$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
					$sthCrows=$sthC->rows;
					$Crc=0;
					while ($sthCrows > $Crc)
						{
						@aryC = $sthC->fetchrow_array;
						if ($Crc > 0) {$all_callerids .= ",";}
						$all_callerids .= "'$aryC[0]'";
						$Crc++;
						}
					$sthC->finish();
					if ($DBX) {print "     DEBUG2: |$sthCrows|$all_callerids|$stmtC|\n";}
					if ($Crc < 1) {$all_callerids = "''";}

					$DBIPINCALLthresh[$user_CIPct]=0;
					$stmtC = "SELECT count(*) from vicidial_live_agents where ( (campaign_id='$DBIPcampaign[$user_CIPct]') or (dial_campaign_id='$DBIPcampaign[$user_CIPct]') ) and last_update_time > '$BDtsSQLdate' and status IN('INCALL','QUEUE') and ( (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_call_time)) <= $DBIPincall_tally_threshold_seconds[$user_CIPct]) and (callerid IN($all_callerids));";
					$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
					$sthCrows=$sthC->rows;
					if ($sthCrows > 0)
						{
						@aryC = $sthC->fetchrow_array;
						$DBIPINCALLthresh[$user_CIPct] = $aryC[0];
						}
					$sthC->finish();

					$stmtC = "SELECT count(*) from vicidial_live_agents where ( (campaign_id='$DBIPcampaign[$user_CIPct]') or (dial_campaign_id='$DBIPcampaign[$user_CIPct]') ) and last_update_time > '$BDtsSQLdate' and status IN('INCALL','QUEUE') and (callerid NOT IN($all_callerids));";
					$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
					$sthCrows=$sthC->rows;
					if ($sthCrows > 0)
						{
						@aryC = $sthC->fetchrow_array;
						$DBIPINCALLdeadT[$user_CIPct] = $aryC[0];
						}
					$sthC->finish();

					$DBIPcountT[$user_CIPct] = ($DBIPACTIVEcount[$user_CIPct] + $DBIPINCALLthresh[$user_CIPct]);
					if ($DBX) {print "     DEBUG1: |$sthCrows|$DBIPACTIVEcount[$user_CIPct]|$DBIPINCALLthresh[$user_CIPct]|$stmtC|\n";}

					$DBIPINCALLdiff[$user_CIPct] = ($DBIPINCALLcount[$user_CIPct] - $DBIPINCALLthresh[$user_CIPct]);

					if ($DB) {print "     Debug: AGENT IN-CALL TALLY SECONDS THRESHOLD: $DBIPcampaign[$user_CIPct] $DBIPincall_tally_threshold_seconds[$user_CIPct] seconds   |all: $DBIPINCALLcount[$user_CIPct]  thresh: $DBIPINCALLthresh[$user_CIPct] (diff: $DBIPINCALLdiff[$user_CIPct] dead: $DBIPINCALLdeadT[$user_CIPct])|   |total: $DBIPcount[$user_CIPct]   thresh tot: $DBIPcountT[$user_CIPct]|$stmtC|\n";}
					$debug_string .= "   !! AGENT IN-CALL TALLY SECONDS THRESHOLD ENABLED for INCALL AGENTS: $DBIPincall_tally_threshold_seconds[$user_CIPct] seconds   |all: $DBIPINCALLcount[$user_CIPct]  thresh: $DBIPINCALLthresh[$user_CIPct] (diff: $DBIPINCALLdiff[$user_CIPct] dead: $DBIPINCALLdeadT[$user_CIPct])|   |total: $DBIPcount[$user_CIPct]   thresh tot: $DBIPcountT[$user_CIPct]|\n";
					$DBIPINCALLcount[$user_CIPct] = $DBIPINCALLthresh[$user_CIPct];
					$DBIPcount[$user_CIPct] = $DBIPcountT[$user_CIPct];
					}


				# BEGIN check if drop-in-group agents should be included ---NONE--- #
				if ( ($DBIPdrop_call_seconds[$user_CIPct] < 0) && ($DBIPdrop_action[$user_CIPct] =~ /IN_GROUP/i) && ($DBIPdrop_inbound_group[$user_CIPct] !~ /^---NONE---$/) && (length($DBIPdrop_inbound_group[$user_CIPct]) > 0) && ($new_agent_multicampaign > 0) && ($DBIPdial_method[$user_CIPct] !~ /SHARED_/i) ) 
					{
					if ($DBIPcount[$user_CIPct] > 0) {$DBIPcount[$user_CIPct] = ($DBIPcount[$user_CIPct] - 1);}
					if ($DBIPACTIVEcount[$user_CIPct] > 0) {$DBIPACTIVEcount[$user_CIPct] = ($DBIPACTIVEcount[$user_CIPct] - 1);}
					$SQL_group_id=$DBIPdrop_inbound_group[$user_CIPct];   $SQL_group_id =~ s/_/\\_/gi;
					$temp_drop_ingroup_SQL = "and ( (campaign_id!='$DBIPcampaign[$user_CIPct]') and (closer_campaigns LIKE \"% $SQL_group_id %\") )";

					$stmtC = "SELECT user,status from vicidial_live_agents where last_update_time > '$BDtsSQLdate' $temp_drop_ingroup_SQL;";
					$sthC = $dbhC->prepare($stmtC) or die "preparing: ",$dbhC->errstr;
					$sthC->execute or die "executing: $stmtC ", $dbhC->errstr;
					$sthCrows=$sthC->rows;
					$temp_dig_ct=0;
					$temp_dig_ACTIVE=0;
					$temp_dig_INCALL=0;
					while ($sthCrows > $temp_dig_ct)
						{
						@aryC = $sthC->fetchrow_array;
						$temp_agent_status = $aryC[1];

						if ($temp_agent_status =~ /READY|DONE|CLOSER/) 
							{
							$DBIPcount[$user_CIPct]++;
							$DBIPACTIVEcount[$user_CIPct]++;
							$temp_dig_ACTIVE++;
							}
						else
							{
							if ($temp_agent_status =~ /INCALL/) 
								{
								$DBIPcount[$user_CIPct]++;
								$DBIPINCALLcount[$user_CIPct]++;
								$temp_dig_INCALL++;
								}
							}
						$temp_dig_ct++;
						}
					$sthC->finish();
					if ($DB) {print "     $DBIPcampaign[$user_CIPct] Drop In-Group agents included from $DBIPdrop_inbound_group[$user_CIPct]: READY $temp_dig_ACTIVE + INCALL $temp_dig_INCALL  ($temp_dig_ct)\n";}
					$debug_string .= "     $DBIPcampaign[$user_CIPct] Drop In-Group agents included from $DBIPdrop_inbound_group[$user_CIPct]: READY $temp_dig_ACTIVE + INCALL $temp_dig_INCALL  ($temp_dig_ct)\n";
					}
				# END check if drop-in-group agents should be included ---NONE--- #

				if ($DBIPdl_diff_target_method[$user_CIPct] =~ /ADAPT_CALC_ONLY/)
					{
					if ( ($DBIPadaptive_dl_diff_target[$user_CIPct] < 0) || ($DBIPadaptive_dl_diff_target[$user_CIPct] > 0) )
						{
						$debug_string .= "   !! DL DIFF TARGET SET TO 0, CALC-ONLY MODE: $DBIPdl_diff_target_method[$user_CIPct]|$DBIPadaptive_dl_diff_target[$user_CIPct]\n";
						}
					$DBIPadaptive_dl_diff_target[$user_CIPct] = 0;
					}
				if ($DBIPavailable_only_tally[$user_CIPct] =~ /Y/)
					{
					$DBIPcount[$user_CIPct] = $DBIPACTIVEcount[$user_CIPct];
					$active_only=1;
					}
				else
					{
					### Check for available only tally threshold ###
					if ( ($DBIPavailable_only_tally_threshold[$user_CIPct] =~ /LOGGED-IN_AGENTS/) && ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $user_counter) )
						{
						$debug_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for LOGGED-IN_AGENTS: ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $user_counter)\n";
						$active_only=1;
						}
					if ( ($DBIPavailable_only_tally_threshold[$user_CIPct] =~ /NON-PAUSED_AGENTS/) && ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $DBIPcount[$user_CIPct]) )
						{
						$debug_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for NON-PAUSED_AGENTS: ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $DBIPcount[$user_CIPct])\n";
						$active_only=1;
						}
					if ( ($DBIPavailable_only_tally_threshold[$user_CIPct] =~ /WAITING_AGENTS/) && ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $DBIPACTIVEcount[$user_CIPct]) )
						{
						$debug_string .= "   !! AVAILABLE ONLY TALLY THRESHOLD triggered for WAITING_AGENTS: ($DBIPavailable_only_tally_threshold_agents[$user_CIPct] > $DBIPACTIVEcount[$user_CIPct])\n";
						$active_only=1;
						}

					if ($active_only > 0)
						{$DBIPcount[$user_CIPct] = $DBIPACTIVEcount[$user_CIPct];}
					else
						{
						if ($DBX > 0) {print "     DEBUG: dialable agent count: ($DBIPcount[$user_CIPct] - $DBIPDEADcount[$user_CIPct] + $DBIPINCALLdeadT[$user_CIPct])\n";}
						$DBIPcount[$user_CIPct] = ($DBIPcount[$user_CIPct] - $DBIPDEADcount[$user_CIPct] + $DBIPINCALLdeadT[$user_CIPct]);
						}

					if ($DBIPcount[$user_CIPct] < 0)
						{$DBIPcount[$user_CIPct]=0;}
					}

				$rec_count++;
				}
			$sthA->finish();

			### Check for dial level threshold ###
			$nonpause_agents_temp = ($DBIPACTIVEcount[$user_CIPct] + $DBIPINCALLcount[$user_CIPct]);
			if ( ($DBIPdial_level_threshold[$user_CIPct] =~ /LOGGED-IN_AGENTS/) && ($DBIPdial_level_threshold_agents[$user_CIPct] > $user_counter) )
				{
				$debug_string .= "   !! DIAL LEVEL THRESHOLD triggered for LOGGED-IN_AGENTS: ($DBIPdial_level_threshold_agents[$user_CIPct] > $user_counter)\n";
				$DBIPadlevel[$user_CIPct]=1;
				}
			if ( ($DBIPdial_level_threshold[$user_CIPct] =~ /NON-PAUSED_AGENTS/) && ($DBIPdial_level_threshold_agents[$user_CIPct] > $nonpause_agents_temp) )
				{
				$debug_string .= "   !! DIAL LEVEL THRESHOLD triggered for NON-PAUSED_AGENTS: ($DBIPdial_level_threshold_agents[$user_CIPct] > $nonpause_agents_temp)\n";
				$DBIPadlevel[$user_CIPct]=1;
				}
			if ( ($DBIPdial_level_threshold[$user_CIPct] =~ /WAITING_AGENTS/) && ($DBIPdial_level_threshold_agents[$user_CIPct] > $DBIPACTIVEcount[$user_CIPct]) )
				{
				$debug_string .= "   !! DIAL LEVEL THRESHOLD triggered for WAITING_AGENTS: ($DBIPdial_level_threshold_agents[$user_CIPct] > $DBIPACTIVEcount[$user_CIPct])\n";
				$DBIPadlevel[$user_CIPct]=1;
				}

			### apply dial level difference target ###
			$DBIPcount[$user_CIPct] = ($DBIPcount[$user_CIPct] + $DBIPadaptive_dl_diff_target[$user_CIPct]);
			if ($DBIPcount[$user_CIPct] < 0)
				{$DBIPcount[$user_CIPct]=0;}

			$DBIPgoalcalls[$user_CIPct] = ($DBIPadlevel[$user_CIPct] * $DBIPcount[$user_CIPct]);
			$tally_xfer_line_counter=0;
			$temp_drop_IG='';
			if ($DBIPdrop_action[$user_CIPct] =~ /IN_GROUP/i) 
				{$temp_drop_IG=",'$DBIPdrop_inbound_group[$user_CIPct]'";}
			if ($active_only > 0) 
				{
				### see how many VDAD calls are live as XFERs to agents
				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where server_ip='$DBIPaddress[$user_CIPct]' and campaign_id IN('$DBIPcampaign[$user_CIPct]'$temp_drop_IG) and status IN('XFER','CLOSER');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$tally_xfer_line_counter = $aryA[0];
					}
				$sthA->finish();

				$DBIPgoalcalls[$user_CIPct] = ($DBIPgoalcalls[$user_CIPct] + $tally_xfer_line_counter);
				}
			if ($DBIPactive[$user_CIPct] =~ /N/) {$DBIPgoalcalls[$user_CIPct] = 0;}
			$DBIPgoalcalls[$user_CIPct] = sprintf("%.0f", $DBIPgoalcalls[$user_CIPct]);

			$event_string="$user_CIPct_sort $user_CIPct   $DBIPcampaign[$user_CIPct] $DBIPaddress[$user_CIPct]: agents: $DBIPcount[$user_CIPct] (READY: $DBIPcampaign_ready_agents[$user_CIPct])    dial_level: $DBIPadlevel[$user_CIPct]     ($DBIPACTIVEcount[$user_CIPct]|$DBIPINCALLcount[$user_CIPct]|$DBIPDEADcount[$user_CIPct])   $DBIPadaptive_dl_diff_target[$user_CIPct]";
			$debug_string .= "$event_string\n";
			&event_logger;


			### see how many calls are already active per campaign per server and
			### subtract that number from goalcalls to determine how many new
			### calls need to be placed in this loop
			if ($DBIPcampaign_allow_inbound[$user_CIPct] =~ /Y/)
				{
				if (length($DBIPclosercamp[$user_CIPct]) > 2)
					{
					$DBIPclosercamp[$user_CIPct] =~ s/^ | -$//gi;
					$DBIPclosercamp[$user_CIPct] =~ s/ /','/gi;
					$DBIPclosercamp[$user_CIPct] = "'$DBIPclosercamp[$user_CIPct]'";
					}
				else {$DBIPclosercamp[$user_CIPct]="''";}

				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (call_type='IN' and campaign_id IN($DBIPclosercamp[$user_CIPct])) and server_ip='$DBIPaddress[$user_CIPct]' and status IN('SENT','RINGING','LIVE','XFER','CLOSER');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPexistcalls_IN[$user_CIPct] = $aryA[0];
					}
				$sthA->finish();

				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (call_type='IN' and campaign_id IN($DBIPclosercamp[$user_CIPct])) and status IN('SENT','RINGING','LIVE','XFER','CLOSER');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPexistcalls_IN_ALL[$user_CIPct] = $aryA[0];
					}
				$sthA->finish();

				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where call_type='IN' and campaign_id IN($DBIPclosercamp[$user_CIPct]) and status IN('LIVE');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPexistcalls_IN_LIVE[$user_CIPct] = $aryA[0];
					}
				$sthA->finish();

				$stmtA = "SELECT count(*) FROM vicidial_live_chats where group_id IN($DBIPclosercamp[$user_CIPct]) and status IN('WAITING');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPexistchats_IN_LIVE[$user_CIPct] = $aryA[0];
					}
				$sthA->finish();

				$stmtA = "SELECT count(*) FROM vicidial_inbound_callback_queue where icbq_status IN('LIVE','SENDING','NEW') and group_id IN($DBIPclosercamp[$user_CIPct]);";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPexistcalls_QUEUE_LIVE[$user_CIPct] = $aryA[0];
					}
				$sthA->finish();
				}
			else
				{
				$DBIPexistcalls_IN[$user_CIPct]=0;
				$DBIPexistcalls_IN_ALL[$user_CIPct]=0;
				$DBIPexistcalls_IN_LIVE[$user_CIPct]=0;
				$DBIPexistchats_IN_LIVE[$user_CIPct]=0;
				$DBIPexistcalls_QUEUE_LIVE[$user_CIPct]=0;
				}

			### Check for inbound no-agents no-dial
			$DBIPinbound_no_agents_no_dial_trigger[$user_CIPct]=0;
			if ($DBX)
				{
				print "INBOUND NO-AGENTS NO-DIAL CHECK START($DBIPcampaign[$user_CIPct]): $DBIPinbound_no_agents_no_dial[$user_CIPct]|$DBIPinbound_no_agents_no_dial_threshold[$user_CIPct]\n";
				}
			if ( ($DBIPinbound_no_agents_no_dial[$user_CIPct] !~ /DISABLED/) && (length($DBIPinbound_no_agents_no_dial[$user_CIPct]) > 0) && ($DBIPinbound_no_agents_no_dial_threshold[$user_CIPct] > 0) )
				{
				$DBIPinand_container_entry[$user_CIPct]='';
				$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$DBIPinbound_no_agents_no_dial[$user_CIPct]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($DBX) {print "$sthArows|$stmtA\n";}
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBIPinand_container_entry[$user_CIPct] = $aryA[0];
					$DBIPinand_container_entry[$user_CIPct] =~ s/\n/','/gi;
					$DBIPinand_container_entry[$user_CIPct] =~ s/\r|\t| |''|,$|^,//gi;
					$DBIPinand_container_entry[$user_CIPct] =~ s/,,/,/gi;
					$DBIPinand_container_entry[$user_CIPct] =~ s/,''//gi;
					$DBIPinand_container_entry[$user_CIPct] =~ s/','$//gi;
					}
				$sthA->finish();

				if (length($DBIPinand_container_entry[$user_CIPct]) > 2)
					{
					$DBIPinbound_no_agents_no_dial_trigger[$user_CIPct]=1;
					$DBIPinand_users[$user_CIPct]='';
					$stmtA = "SELECT distinct(user) FROM vicidial_live_inbound_agents where group_id IN('$DBIPinand_container_entry[$user_CIPct]') and ( (daily_limit = '-1') or (daily_limit > calls_today) );";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($DBX) {print "$sthArows|$stmtA\n";}
					$u=0;
					while ($sthArows > $u)
						{
						@aryA = $sthA->fetchrow_array;
						$DBIPinand_users[$user_CIPct] .= "'$aryA[0]',";
						$u++;
						}
					$DBIPinand_users[$user_CIPct] =~ s/,$//gi;
					$sthA->finish();

					if (length($DBIPinand_users[$user_CIPct]) > 2)
						{
						$DBIPinand_agent_ready_count[$user_CIPct]=0;
						$stmtA = "SELECT count(*) FROM vicidial_live_agents where user IN($DBIPinand_users[$user_CIPct]) and status IN('READY','CLOSER');";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "$sthArows|$stmtA\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$DBIPinand_agent_ready_count[$user_CIPct] = $aryA[0];
							if ($DBIPinand_agent_ready_count[$user_CIPct] >= $DBIPinbound_no_agents_no_dial_threshold[$user_CIPct])
								{
								$DBIPinbound_no_agents_no_dial_trigger[$user_CIPct]=0;
								}
							else
								{$debug_string .= "   !! INBOUND NO-AGENT NO-DIAL: Agents: $DBIPinand_agent_ready_count[$user_CIPct]  Threshold: $DBIPinbound_no_agents_no_dial_threshold[$user_CIPct]\n";}
							if ($DBX) {print "DEBUG: INBOUND NO-AGENT    Agents: $DBIPinand_agent_ready_count[$user_CIPct]  Threshold: $DBIPinbound_no_agents_no_dial_threshold[$user_CIPct]\n";}
							}
						$sthA->finish();
						}
					else
						{$debug_string .= "   !! INBOUND NO-AGENT NO-DIAL: Agents: $DBIPinand_agent_ready_count[$user_CIPct]  Threshold: $DBIPinbound_no_agents_no_dial_threshold[$user_CIPct]\n";}
					}
				}

			$temp_dropsSERVER=0;
			if ($allow_shared_dial > 0) 
				{
				$stmtA = "SELECT count(*) FROM vicidial_shared_drops where (campaign_id='$DBIPcampaign[$user_CIPct]' and call_type IN('OUT','OUTBALANCE')) and server_ip='$DBIPaddress[$user_CIPct]' and status IN('SENT','RINGING','LIVE','XFER','CLOSER') and drop_time > \"$FVtsSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$temp_dropsSERVER = $aryA[0];
					}
				}
			$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (campaign_id IN('$DBIPcampaign[$user_CIPct]'$temp_drop_IG) and call_type IN('IN','OUT','OUTBALANCE')) and server_ip='$DBIPaddress[$user_CIPct]' and status IN('SENT','RINGING','LIVE','XFER','CLOSER');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX) {print "$sthArows|$stmtA\n";}
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$DBIPexistcalls_OUT[$user_CIPct] = ($aryA[0] + $temp_dropsSERVER);
				}
			$sthA->finish();

			$DBIPexistcalls[$user_CIPct] = ($DBIPexistcalls_IN[$user_CIPct] + $DBIPexistcalls_OUT[$user_CIPct]);
			if ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /ALL_SERVERS/)
				{$DBIPexistcalls[$user_CIPct] = ($DBIPexistcalls_IN_ALL[$user_CIPct] + $DBIPexistcalls_OUT[$user_CIPct]);}
			if ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /CHAT/)
				{$DBIPexistcalls[$user_CIPct] = ($DBIPexistcalls[$user_CIPct] + $DBIPexistchats_IN_LIVE[$user_CIPct]);}

			if ( ($DBIPcampaign_ready_agents[$user_CIPct] > 0) && ($DBIPexistcalls_IN[$user_CIPct] > 0) )
				{
				$event_string="     BLENDED-OUTBOUND-AGENTS-WAITING OVERRIDE: $DBIPcampaign[$user_CIPct] $DBIPexistcalls[$user_CIPct] [$DBIPexistcalls_IN[$user_CIPct] + $DBIPexistcalls_OUT[$user_CIPct]])";
				$debug_string .= "$event_string\n";
				&event_logger;

				$DBIPexistcalls[$user_CIPct] = $DBIPexistcalls_OUT[$user_CIPct];
				}
			if ( ($DBIPINCALLdiff[$user_CIPct] > 0) && ($DBIPexistcalls[$user_CIPct] >= $DBIPINCALLdiff[$user_CIPct]) )
				{$DBIPexistcalls[$user_CIPct] = ($DBIPexistcalls[$user_CIPct] - $DBIPINCALLdiff[$user_CIPct])}

			$active_line_goal=0;
			$DBIPmakecalls[$user_CIPct] = ($DBIPgoalcalls[$user_CIPct] - $DBIPexistcalls[$user_CIPct]);
			$DBIPmakecallsGOAL = $DBIPmakecalls[$user_CIPct];
			$MVT_msg = '';
			$DBIPtrunk_shortage[$user_CIPct] = 0;
			$active_line_goal = ($active_line_counter + $DBIPmakecalls[$user_CIPct]);
			if ($active_line_goal > $max_vicidial_trunks)
				{
				$NEWmakecallsgoal = ($max_vicidial_trunks - $active_line_counter);
				if ($DBIPmakecalls[$user_CIPct] > $NEWmakecallsgoal)
					{$DBIPmakecalls[$user_CIPct] = $NEWmakecallsgoal;}
				$DBIPtrunk_shortage[$user_CIPct] = ($active_line_goal - $max_vicidial_trunks);
				if ($DBIPtrunk_shortage[$user_CIPct] > $DBIPmakecallsGOAL)
					{$DBIPtrunk_shortage[$user_CIPct] = $DBIPmakecallsGOAL}
				$MVT_msg .= "     MVT override: $max_vicidial_trunks |$DBIPmakecalls[$user_CIPct] $DBIPtrunk_shortage[$user_CIPct]|";
				}
			if (length($DBIPserver_trunks_limit[$user_CIPct])>0)
				{
				if ($DBIPserver_trunks_limit[$user_CIPct] < $DBIPgoalcalls[$user_CIPct])
					{
					$MVT_msg .= "     TRUNK LIMIT override: $DBIPserver_trunks_limit[$user_CIPct]";
					$DBIPtrunk_shortage[$user_CIPct] = ($DBIPgoalcalls[$user_CIPct] - $DBIPserver_trunks_limit[$user_CIPct]);
					$DBIPmakecalls[$user_CIPct] = ($DBIPserver_trunks_limit[$user_CIPct] - $DBIPexistcalls[$user_CIPct]);
					if ($DBIPtrunk_shortage[$user_CIPct] > $DBIPmakecallsGOAL)
						{$DBIPtrunk_shortage[$user_CIPct] = $DBIPmakecallsGOAL}
					$active_line_goal = $DBIPserver_trunks_limit[$user_CIPct];
					}
				}
			else
				{
				if ($DBIPserver_trunks_allowed[$user_CIPct] < $active_line_goal)
					{
					$MVT_msg .= "     OTHER LIMIT override: $DBIPserver_trunks_allowed[$user_CIPct]";
					$DBIPtrunk_shortage[$user_CIPct] = ($active_line_goal - $DBIPserver_trunks_allowed[$user_CIPct]);
					if ($DBIPtrunk_shortage[$user_CIPct] > $DBIPmakecallsGOAL)
						{$DBIPtrunk_shortage[$user_CIPct] = $DBIPmakecallsGOAL}
					$active_line_goal = $DBIPserver_trunks_allowed[$user_CIPct];
					$NEWmakecallsgoal = ($active_line_goal - $active_line_counter);
					if ($DBIPmakecalls[$user_CIPct] > $NEWmakecallsgoal)
						{$DBIPmakecalls[$user_CIPct] = $NEWmakecallsgoal;}
					}
				}

			$sharedMAX_msg='';
			if ($DBIPmakecalls[$user_CIPct] > 0)
				{
				$active_line_counter = ($DBIPmakecalls[$user_CIPct] + $active_line_counter);

				if ($DBIPdial_method[$user_CIPct] =~ /SHARED_/i) 
					{
					$temp_shared_make_calls = ($shared_callsTOTAL + $DBIPmakecalls[$user_CIPct]);
					if ($temp_shared_make_calls > $share_dial_max)
						{
						$temp_goal_diff = ($temp_shared_make_calls - $share_dial_max);
						$new_goal_calls = ($DBIPmakecalls[$user_CIPct] - $temp_goal_diff);
						if ($new_goal_calls < 0) {$new_goal_calls=0;}
						$sharedMAX_msg = "\nSHARED MAX CALLS EXCEEDED!   Shared Calls: $shared_callsTOTAL   Shared Max: $share_dial_max   Camp Goal Calls: $DBIPmakecalls[$user_CIPct]   New Goal Calls: $new_goal_calls";
						$DBIPmakecalls[$user_CIPct] = $new_goal_calls;
						}
					}
				}

			$max_debug_string='';
			if ($allow_shared_dial > 2) 
				{
				$stmtA = "SELECT callerid,server_ip,campaign_id,status,lead_id,phone_number,call_time,call_type FROM vicidial_shared_drops where campaign_id IN($shared_SQL) and drop_time > \"$FVtsSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$temp_ct=0;
				while ($sthArows > $temp_ct)
					{
					@aryA = $sthA->fetchrow_array;
					$temp_ct++;
					$max_debug_string .= "SD $temp_ct $aryA[0] $aryA[1] $aryA[2] $aryA[3] $aryA[4] $aryA[5] $aryA[6] $aryA[7]\n";
					}
				$stmtA = "SELECT callerid,server_ip,campaign_id,status,lead_id,phone_number,call_time,call_type FROM vicidial_auto_calls where campaign_id IN($shared_SQL);";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$temp_ct=0;
				while ($sthArows > $temp_ct)
					{
					@aryA = $sthA->fetchrow_array;
					$temp_ct++;
					$max_debug_string .= "   $temp_ct $aryA[0] $aryA[1] $aryA[2] $aryA[3] $aryA[4] $aryA[5] $aryA[6] $aryA[7]\n";
					}
				}

			$event_string="$user_CIPct_sort $user_CIPct   $DBIPcampaign[$user_CIPct] $DBIPaddress[$user_CIPct]: Calls to place: $DBIPmakecalls[$user_CIPct] ($DBIPgoalcalls[$user_CIPct] - $DBIPexistcalls[$user_CIPct] [$DBIPexistcalls_IN[$user_CIPct] + $DBIPexistcalls_OUT[$user_CIPct]|$DBIPexistcalls_IN_ALL[$user_CIPct]|$DBIPexistcalls_IN_LIVE[$user_CIPct]|$DBIPexistcalls_QUEUE_LIVE[$user_CIPct]]) $active_line_counter $MVT_msg $sharedMAX_msg \n$max_debug_string";
			$debug_string .= "$event_string\n";
			&event_logger;

			### Calculate campaign-wide agent waiting and calls waiting differential
			### This is used by the AST_VDadapt script to see if the current dial_level
			### should be changed at all
			$total_agents=0;
			$ready_agents=0;
			$waiting_calls=0;
			$waiting_calls_IN=0;
			$waiting_calls_OUT=0;

			$stmtA = "SELECT count(*),status from vicidial_live_agents where ( (campaign_id='$DBIPcampaign[$user_CIPct]') or (dial_campaign_id='$DBIPcampaign[$user_CIPct]') ) and last_update_time > '$halfminSQLdate' group by status;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$VCSagent_count =		 $aryA[0];
				$VCSagent_status =		 $aryA[1];
				$rec_count++;
				if ($VCSagent_status =~ /READY|DONE/) {$ready_agents = ($ready_agents + $VCSagent_count);}
				$total_agents = ($total_agents + $VCSagent_count);
				}
			$sthA->finish();

			if ($DBIPcampaign_allow_inbound[$user_CIPct] =~ /Y/)
				{
				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (call_type='IN' and campaign_id IN($DBIPclosercamp[$user_CIPct])) and status IN('LIVE');";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$waiting_calls_IN = $aryA[0];
					}
				$sthA->finish();
				}
			else
				{$waiting_calls_IN=0;}

			$temp_dropsTOTAL=0;
			if ($allow_shared_dial > 0) 
				{
				$stmtA = "SELECT count(*) FROM vicidial_shared_drops where (campaign_id='$DBIPcampaign[$user_CIPct]' and call_type IN('OUT','OUTBALANCE')) and status IN('LIVE') and drop_time > \"$FVtsSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$temp_dropsTOTAL = $aryA[0];
					}
				}
			$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (campaign_id='$DBIPcampaign[$user_CIPct]' and call_type IN('OUT','OUTBALANCE')) and status IN('LIVE');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$waiting_calls_OUT = ($aryA[0] + $temp_dropsTOTAL);
				}
			$sthA->finish();

			# check for drop-ingroup calls active in the system
			if ( ($DBIPdrop_call_seconds[$user_CIPct] < 0) && ($DBIPdrop_action[$user_CIPct] =~ /IN_GROUP/i) && ($DBIPdrop_inbound_group[$user_CIPct] !~ /^---NONE---$/) && (length($DBIPdrop_inbound_group[$user_CIPct]) > 0) && ($new_agent_multicampaign > 0) ) 
				{
				$stmtA = "SELECT count(*) FROM vicidial_auto_calls where (campaign_id='$DBIPdrop_inbound_group[$user_CIPct]' and call_type IN('IN','OUT','OUTBALANCE')) and  ( (status IN('LIVE')) or ( (status IN('CLOSER')) and (call_time > \"$FVtsSQLdate\") ) );";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$waiting_calls_OUT = ($aryA[0] + $waiting_calls_OUT);
					$event_string="     $DBIPcampaign[$user_CIPct] Drop In-Group calls: $aryA[0]  (New Waiting Calls: $waiting_calls_OUT)\n";
					$debug_string .= "$event_string\n";
					&event_logger;
					}
				$sthA->finish();
				}

			$waiting_calls = ($waiting_calls_IN + $waiting_calls_OUT);

			$stat_ready_agents[$user_CIPct][$stat_count] = $ready_agents;
			$stat_waiting_calls[$user_CIPct][$stat_count] = $waiting_calls;
			$stat_total_agents[$user_CIPct][$stat_count] = $total_agents;

			$stat_it=20;
			$ready_diff_total=0;
			$waiting_diff_total=0;
			$total_agents_total=0;
			$ready_diff_avg=0;
			$waiting_diff_avg=0;
			$total_agents_avg=0;
			$stat_differential=0;
			if ($stat_count < 20)
				{
				$stat_it = $stat_count;
				$stat_B = 1;
				}
			else
				{
				$stat_B = ($stat_count - 19);
				}

			$it=0;
			while($it < $stat_it)
				{
				$it_ary = ($it + $stat_B);
				$ready_diff_total = ($ready_diff_total + $stat_ready_agents[$user_CIPct][$it_ary]);
				$waiting_diff_total = ($waiting_diff_total + $stat_waiting_calls[$user_CIPct][$it_ary]);
				$total_agents_total = ($total_agents_total + $stat_total_agents[$user_CIPct][$it_ary]);
		#		$event_string="$stat_count $it_ary   $stat_total_agents[$user_CIPct][$it_ary]|$stat_ready_agents[$user_CIPct][$it_ary]|$stat_waiting_calls[$user_CIPct][$it_ary]";
		#		&event_logger;
				$it++;
				}

			if ($ready_diff_total > 0)
				{$ready_diff_avg = ($ready_diff_total / $stat_it);}
			if ($waiting_diff_total > 0)
				{$waiting_diff_avg = ($waiting_diff_total / $stat_it);}
			if ($total_agents_total > 0)
				{$total_agents_avg = ($total_agents_total / $stat_it);}
			$stat_differential = ($ready_diff_avg - $waiting_diff_avg);

			$event_string="CAMPAIGN DIFFERENTIAL: $total_agents_avg   $stat_differential   ($ready_diff_avg - $waiting_diff_avg)";
			$debug_string .= "$event_string\n";
			&event_logger;

			$stmtA = "UPDATE vicidial_campaign_stats SET differential_onemin='$stat_differential', agents_average_onemin='$total_agents_avg' where campaign_id='$DBIPcampaign[$user_CIPct]';";
			$affected_rows = $dbhA->do($stmtA);

			if ( ($DBIPold_trunk_shortage[$user_CIPct] > $DBIPtrunk_shortage[$user_CIPct]) || ($DBIPold_trunk_shortage[$user_CIPct] < $DBIPtrunk_shortage[$user_CIPct]) )
				{
				if ( ($DBIPadlevel[$user_CIPct] < 1) || ($DBIPdial_method[$user_CIPct] =~ /MANUAL|INBOUND_MAN/) )
					{
					$event_string="Manual Dial Override for Shortage |$DBIPadlevel[$user_CIPct]|$DBIPtrunk_shortage[$user_CIPct]|";
					$debug_string .= "$event_string\n";
					&event_logger;
					$DBIPtrunk_shortage[$user_CIPct] = 0;
					}
				if ( ( ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /ENABLED|ALL_SERVERS/) && ( ($DBIPexistcalls_IN_LIVE[$user_CIPct] > 0) || ($DBIPexistcalls_QUEUE_LIVE[$user_CIPct] > 0) ) ) || ( ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /CHAT/) && ($DBIPexistchats_IN_LIVE[$user_CIPct] > 0) ) )
					{
					$event_string="Inbound Queue No-Dial Override for Shortage |$DBIPexistcalls_IN_LIVE[$user_CIPct]|$DBIPexistcalls_QUEUE_LIVE[$user_CIPct]|$DBIPtrunk_shortage[$user_CIPct]|";
					$debug_string .= "$event_string\n";
					&event_logger;
					$DBIPtrunk_shortage[$user_CIPct] = 0;
					}
				if ($DBIPinbound_no_agents_no_dial_trigger[$user_CIPct] > 0)
					{
					$event_string="Inbound No-Agents No-Dial Override for Shortage |$DBIPinbound_no_agents_no_dial_trigger[$user_CIPct]|$DBIPtrunk_shortage[$user_CIPct]|";
					$debug_string .= "$event_string\n";
					&event_logger;
					$DBIPtrunk_shortage[$user_CIPct] = 0;
					}

				$stmtA = "UPDATE vicidial_campaign_server_stats SET local_trunk_shortage='$DBIPtrunk_shortage[$user_CIPct]',update_time='$now_date' where server_ip='$server_ip' and campaign_id='$DBIPcampaign[$user_CIPct]';";
				$affected_rows = $dbhA->do($stmtA);
				}
			else
				{
				$stmtA = "UPDATE vicidial_campaign_server_stats SET update_time='$now_date' where server_ip='$server_ip' and campaign_id='$DBIPcampaign[$user_CIPct]';";
				$affected_rows = $dbhA->do($stmtA);
				}

			$event_string="LOCAL TRUNK SHORTAGE: $DBIPtrunk_shortage[$user_CIPct]|$DBIPold_trunk_shortage[$user_CIPct]  ($active_line_goal - $max_vicidial_trunks)";
			$debug_string .= "$event_string\n";
			&event_logger;

			$stmtA="INSERT IGNORE INTO vicidial_campaign_stats_debug SET server_ip='$server_ip',campaign_id='$DBIPcampaign[$user_CIPct]',entry_time='$now_date',debug_output='$debug_string' ON DUPLICATE KEY UPDATE entry_time='$now_date',debug_output='$debug_string';";
			$affected_rows = $dbhA->do($stmtA);

			if ($allow_shared_dial > 1) 
				{
				$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$total_agents',total_calls='$waiting_calls_OUT',debug_output='DIALING PROCESS DEBUG OUTPUT:\n$event_string\n$debug_string',server_ip='$server_ip',campaign_id='$DBIPcampaign[$user_CIPct]';";
				$affected_rows = $dbhA->do($stmtA);
				}

			$user_CIPct_sort++;
			}

	###############################################################################
	###### second lookup leads and place calls for each campaign/server_ip
	######     go one lead at a time and place the call by inserting a record into vicidial_manager
	###############################################################################

		$user_CIPct = 0;
		foreach(@DBIPcampaign)
			{
			$calls_placed=0;
			if ( ($DBIPdial_method[$user_CIPct] =~ /MANUAL|INBOUND_MAN/) || ($outbound_autodial_active < 1) || ($disable_auto_dial > 0) )
				{
				$event_string="$DBIPcampaign[$user_CIPct] $DBIPaddress[$user_CIPct]: MANUAL DIAL CAMPAIGN, NO DIALING";
				&event_logger;
				}
			else
				{
				if ( ( ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /ENABLED|ALL_SERVERS/) && ( ($DBIPexistcalls_IN_LIVE[$user_CIPct] > 0) || ($DBIPexistcalls_QUEUE_LIVE[$user_CIPct] > 0) ) ) || ( ($DBIPinbound_queue_no_dial[$user_CIPct] =~ /CHAT/) && ($DBIPexistchats_IN_LIVE[$user_CIPct] > 0) ) )
					{
					$event_string="$DBIPcampaign[$user_CIPct] $DBIPexistcalls_IN_LIVE[$user_CIPct] $DBIPexistcalls_QUEUE_LIVE[$user_CIPct] $DBIPexistchats_IN_LIVE[$user_CIPct]: INBOUND QUEUE NO DIAL, NO DIALING ($DBIPinbound_queue_no_dial[$user_CIPct])";
					&event_logger;
					}
				else
					{
					if ($DBIPinbound_no_agents_no_dial_trigger[$user_CIPct] > 0)
						{
						$event_string="$DBIPcampaign[$user_CIPct] ($DBIPinand_agent_ready_count[$user_CIPct] <> $DBIPinbound_no_agents_no_dial_threshold[$user_CIPct]) $DBIPinand_users[$user_CIPct] '$DBIPinand_container_entry[$user_CIPct]' $DBIPinbound_no_agents_no_dial_trigger[$user_CIPct]: INBOUND NO-AGENT NO DIAL, NO DIALING ($DBIPinbound_no_agents_no_dial[$user_CIPct])";
						&event_logger;
						}
					else
						{
						$event_string="$DBIPcampaign[$user_CIPct] $DBIPaddress[$user_CIPct]: CALLING";
						&event_logger;
						$call_CMPIPct=0;
						$lead_id_call_list='|';
						my $UDaffected_rows=0;
 
						$shared_clear=1;
						if ( ($DBIPdial_method[$user_CIPct] =~ /SHARED_/i) && ($shared_callsTOTAL > $share_dial_max) )
							{
							$event_string="SHARED CAMPAIGN DIAL MAX EXCEEDED, NO DIALING! ($shared_callsTOTAL > $share_dial_max)";
							&event_logger;
							$shared_clear=0;
							}

						if ( ($call_CMPIPct < $DBIPmakecalls[$user_CIPct]) && ($shared_clear > 0) )
							{
							$stmtA = "UPDATE vicidial_hopper set status='QUEUE', user='VDAD_$server_ip' where campaign_id='$DBIPcampaign[$user_CIPct]' and status='READY' order by priority desc,hopper_id LIMIT $DBIPmakecalls[$user_CIPct]";
							print "|$stmtA|\n";
							$UDaffected_rows = $dbhA->do($stmtA);
							print "hopper rows updated to QUEUE: |$UDaffected_rows|\n";

							if ($UDaffected_rows)
								{
								$lead_id=''; $phone_code=''; $phone_number=''; $called_count='';
								while ($call_CMPIPct < $UDaffected_rows)
									{
									$stmtA = "SELECT lead_id,alt_dial,source FROM vicidial_hopper where campaign_id='$DBIPcampaign[$user_CIPct]' and status='QUEUE' and user='VDAD_$server_ip' order by priority desc,hopper_id LIMIT 1";
									print "|$stmtA|\n";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									$rec_count=0;
									$rec_countCUSTDATA=0;
									while ($sthArows > $rec_count)
										{
										@aryA = $sthA->fetchrow_array;
										$lead_id =			$aryA[0];
										$alt_dial =			$aryA[1];
										$hopper_source =	$aryA[2];
										$rec_count++;
										}
									$sthA->finish();

									if ($lead_id_call_list =~ /\|$lead_id\|/)
										{
										print "!!!!!!!!!!!!!!!!duplicate lead_id for this run: |$lead_id|     $lead_id_call_list\n";
										if ($SYSLOG)
											{
											open(DUPout, ">>$PATHlogs/VDAD_DUPLICATE.$file_date")
													|| die "Can't open $PATHlogs/VDAD_DUPLICATE.$file_date: $!\n";
											print DUPout "$now_date-----$lead_id_call_list-----$lead_id\n";
											close(DUPout);
											}
										}
									else
										{
										$stmtA = "UPDATE vicidial_hopper set status='INCALL' where lead_id=$lead_id";
										print "|$stmtA|\n";
										$UQaffected_rows = $dbhA->do($stmtA);
										print "hopper row updated to INCALL: |$UQaffected_rows|$lead_id|\n";

										### Gather lead data
										$stmtA = "SELECT list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,address3,alt_phone,called_count,security_phrase,state,vendor_lead_code,source_id,title,first_name,middle_initial,last_name,address1,address2,city,province,postal_code,country_code,date_of_birth,email,comments,rank,owner FROM vicidial_list where lead_id=$lead_id;";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										$rec_count=0;
										$rec_countCUSTDATA=0;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$list_id =					$aryA[0];
											$gmt_offset_now	=			$aryA[1];
											$called_since_last_reset =	$aryA[2];
											$phone_code	=				$aryA[3];
											$phone_number =				$aryA[4];
											$address3 =					$aryA[5];
											$alt_phone =				$aryA[6];
											$called_count =				$aryA[7];
											$security_phrase =			$aryA[8];
											$state =					$aryA[9];
											$vendor_id =				$aryA[10];
											$source_id =				$aryA[11];
											$title =					$aryA[12];
											$first_name =				$aryA[13];
											$middle_initial =			$aryA[14];
											$last_name =				$aryA[15];
											$address1 =					$aryA[16];
											$address2 =					$aryA[17];
											$city =						$aryA[18];
											$province =					$aryA[19];
											$postal_code =				$aryA[20];
											$country_code =				$aryA[21];
											$date_of_birth =			$aryA[22];
											$email =					$aryA[23];
											$comments =					$aryA[24];
											$rank =						$aryA[25];
											$owner =					$aryA[26];

											$rec_countCUSTDATA++;
											$rec_count++;
											}
										$sthA->finish();

										if ($rec_countCUSTDATA)
											{
											$campaign_cid_override='';
											$LIST_cid_group_override='';
											$LIST_dial_prefix_override='';
											$auto_alt_threshold=-1;
											### gather list_id overrides
											$stmtA = "SELECT campaign_cid_override,auto_alt_threshold,cid_group_id,dial_prefix FROM vicidial_lists where list_id='$list_id';";
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArowsL=$sthA->rows;
											if ($sthArowsL > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$campaign_cid_override =	$aryA[0];
												$auto_alt_threshold =		$aryA[1];
												$LIST_cid_group_override =	$aryA[2];
												$LIST_dial_prefix_override =	$aryA[3];
												}
											$sthA->finish();

											$temp_auto_alt_threshold = $DBIPauto_alt_threshold[$user_CIPct];
											if ($auto_alt_threshold > -1 ) {$temp_auto_alt_threshold = $auto_alt_threshold;}
											$auto_alt_lead_disabled=0;
											if ( ($temp_auto_alt_threshold > 0) && ($called_count >= $temp_auto_alt_threshold) ) 
												{
												$auto_alt_lead_disabled=1;
												if ($ADB > 0) {$aad_string = "ALT-50: $lead_id|$alt_dial|$DBIPautoaltdial[$user_CIPct]|$DBIPauto_alt_threshold[$user_CIPct]|$auto_alt_threshold|($called_count <> $temp_auto_alt_threshold)|auto_alt_lead_disabled: $auto_alt_lead_disabled|";   &aad_output;}
												}

											### update called_count
											$called_count++;
											if ($called_since_last_reset =~ /^Y/)
												{
												if ($called_since_last_reset =~ /^Y$/) {$CSLR = 'Y1';}
												else
													{
													$called_since_last_reset =~ s/^Y//gi;
													$called_since_last_reset++;
													$CSLR = "Y$called_since_last_reset";
													}
												}
											else {$CSLR = 'Y';}

											$LLCT_DATE_offset = ($LOCAL_GMT_OFF - $gmt_offset_now);
											$LLCT_DATE_offset_epoch = ( $secX - ($LLCT_DATE_offset * 3600) );
											($Lsec,$Lmin,$Lhour,$Lmday,$Lmon,$Lyear,$Lwday,$Lyday,$Lisdst) = localtime($LLCT_DATE_offset_epoch);
											$Lyear = ($Lyear + 1900);
											$Lmon++;
											if ($Lmon < 10) {$Lmon = "0$Lmon";}
											if ($Lmday < 10) {$Lmday = "0$Lmday";}
											if ($Lhour < 10) {$Lhour = "0$Lhour";}
											if ($Lmin < 10) {$Lmin = "0$Lmin";}
											if ($Lsec < 10) {$Lsec = "0$Lsec";}
												$LLCT_DATE = "$Lyear-$Lmon-$Lmday $Lhour:$Lmin:$Lsec";

											if ( ($alt_dial =~ /ALT|ADDR3|X/) && ($DBIPautoaltdial[$user_CIPct] =~ /ALT|ADDR|X/) )
												{
												if ($ADB > 0) {$aad_string = "ALT-51: $lead_id|$alt_dial|$DBIPautoaltdial[$user_CIPct]|";   &aad_output;}
												if ( ($alt_dial =~ /ALT/) && ($DBIPautoaltdial[$user_CIPct] =~ /ALT/) )
													{
													$alt_phone =~ s/\D//gi;
													$phone_number = $alt_phone;
													}
												if ( ($alt_dial =~ /ADDR3/) && ($DBIPautoaltdial[$user_CIPct] =~ /ADDR3/) )
													{
													$address3 =~ s/\D//gi;
													$phone_number = $address3;
													}
												if  ( ($alt_dial =~ /X/) && ($DBIPautoaltdial[$user_CIPct] =~ /X/) )
													{
													if ($ADB > 0) {$aad_string = "ALT-52: $lead_id|$alt_dial|$DBIPautoaltdial[$user_CIPct]|";   &aad_output;}
													if ($alt_dial =~ /LAST|99999/) 
														{
														$stmtA = "SELECT phone_code,phone_number FROM vicidial_list_alt_phones where lead_id=$lead_id order by alt_phone_count desc limit 1;";
														}
													else
														{
														$Talt_dial = $alt_dial;
														$Talt_dial =~ s/\D//gi;
														$stmtA = "SELECT phone_code,phone_number FROM vicidial_list_alt_phones where lead_id=$lead_id and alt_phone_count='$Talt_dial';";
														}
													$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
													$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
													$sthArows=$sthA->rows;
													if ($sthArows > 0)
														{
														@aryA = $sthA->fetchrow_array;
														$phone_code	=	$aryA[0];
														$phone_number =	$aryA[1];
														$phone_number =~ s/\D//gi;
														}
													$sthA->finish();
													}
												if ($ADB > 0) {$aad_string = "ALT-53: $lead_id|$alt_dial|$DBIPautoaltdial[$user_CIPct]|$phone_number|";   &aad_output;}

												$stmtA = "UPDATE vicidial_list set called_since_last_reset='$CSLR',called_count='$called_count',user='VDAD',last_local_call_time='$LLCT_DATE' where lead_id=$lead_id";
												}
											else
												{
												$stmtA = "UPDATE vicidial_list set called_since_last_reset='$CSLR', called_count='$called_count',user='VDAD',last_local_call_time='$LLCT_DATE' where lead_id=$lead_id";
												}
											$affected_rows = $dbhA->do($stmtA);

											# update daily called counts for this lead
											$stmtDC = "INSERT IGNORE INTO vicidial_lead_call_daily_counts SET lead_id='$lead_id',modify_date=NOW(),list_id='$list_id',called_count_total='1',called_count_auto='1' ON DUPLICATE KEY UPDATE modify_date=NOW(),list_id='$list_id',called_count_total=(called_count_total + 1),called_count_auto=(called_count_auto + 1);";
											$affected_rowsDC = $dbhA->do($stmtDC);

											# update daily called counts for this phone_number
											$stmtPDC = "INSERT IGNORE INTO vicidial_phone_number_call_daily_counts SET phone_number='$phone_number',modify_date=NOW(),called_count='1' ON DUPLICATE KEY UPDATE modify_date=NOW(),called_count=(called_count + 1);";
											$affected_rowsPDC = $dbhA->do($stmtPDC);

											if ($DB) {print "LEAD UPDATE: $affected_rows|$stmtA|\n";}
											if ($DB) {print "LEAD CALL COUNT UPDATE: $affected_rowsDC|$stmtDC|\n";}
											if ($DB) {print "PHONE CALL COUNT UPDATE: $affected_rowsPDC|$stmtPDC|\n";}

											$PADlead_id = sprintf("%010s", $lead_id);	while (length($PADlead_id) > 10) {chop($PADlead_id);}
											# VmddhhmmssLLLLLLLLLL Set the callerIDname to a unique call_id string
											$VqueryCID = "V$CIDdate$PADlead_id";

											if ( ( ($DBIPscheduled_callbacks_auto_reschedule[$user_CIPct] !~ /DISABLED/) && (length($DBIPscheduled_callbacks_auto_reschedule[$user_CIPct]) > 0) ) || ($hopper_source eq 'C') )
												{
												### gather vicidial_callbacks information for this lead, if any
												$stmtA = "SELECT callback_id,callback_time,lead_status,list_id FROM vicidial_callbacks where lead_id=$lead_id and status='LIVE' and recipient='ANYONE' order by callback_id limit 1;";
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArowsPSCB=$sthA->rows;
												if ($sthArowsPSCB > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$PSCBcallback_id =		$aryA[0];
													$PSCBcallback_time =	$aryA[1];
													$PSCBlead_status =		$aryA[2];
													$PSCBlist_id =			$aryA[3];
													}
												$sthA->finish();
												### insert record in recent callbacks table
												if ($sthArowsPSCB > 0)
													{
													$stmtA = "INSERT INTO vicidial_recent_ascb_calls SET call_date='$SQLdate',callback_date='$PSCBcallback_time',callback_id='$PSCBcallback_id',caller_code='$VqueryCID',lead_id=$lead_id,server_ip='$DBIPaddress[$user_CIPct]',orig_status='$PSCBlead_status',reschedule='$DBIPscheduled_callbacks_auto_reschedule[$user_CIPct]',list_id='$PSCBlist_id',rescheduled='U';";
													$affected_rows = $dbhA->do($stmtA);
													}
												}

											### BEGIN check for Dial Timeout Lead override for this lead ###
											$DTL_override=0;
											if ( (length($DBIPdial_timeout_lead_container[$user_CIPct]) > 1) && ($DBIPdial_timeout_lead_container[$user_CIPct] !~ /^DISABLED$/i) )
												{
												$AD_field='';
												$skip_line=0;   $DTLOset=0;
												$temp_DTL = $DBIPdial_timeout_lead_container_entry[$user_CIPct];
												if (length($temp_DTL) > 5)
													{
													@container_lines = split(/\n/,$temp_DTL);
													$c=0;
													foreach(@container_lines)
														{
														$container_lines[$c] =~ s/;.*|\r|\t//gi;
														$container_lines[$c] =~ s/ => |=> | =>/=>/gi;
														if (length($container_lines[$c]) > 3)
															{
															# define core settings
															if ($container_lines[$c] =~ /^auto_dial_field/i)
																{
																$container_lines[$c] =~ s/auto_dial_field=>//gi;
																$AD_field = $container_lines[$c];
																}
															else
																{
																if ($container_lines[$c] =~ /^manual_dial_field|^;/i)
																	{$skip_line++;}
																else
																	{
																	# define dial timeouts
																	@temp_key_value = split(/=>/,$container_lines[$c]);
																	if ( ($AD_field eq 'vendor_lead_code') and ($vendor_id eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'source_id') and ($source_id eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'list_id') and ($list_id eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'gmt_offset_now') and ($gmt_offset_now eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'phone_code') and ($phone_code eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'title') and ($title eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'first_name') and ($first_name eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'middle_initial') and ($middle_initial eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'last_name') and ($last_name eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'address1') and ($address1 eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'address2') and ($address2 eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'address3') and ($address3 eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'city') and ($city eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'state') and ($state eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'province') and ($province eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'postal_code') and ($postal_code eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'country_code') and ($country_code eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'date_of_birth') and ($date_of_birth eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'alt_phone') and ($alt_phone eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'email') and ($email eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'security_phrase') and ($security_phrase eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'comments') and ($comments eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'called_count') and ($called_count eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'rank') and ($rank eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}
																	if ( ($AD_field eq 'owner') and ($owner eq $temp_key_value[0]) )
																		{$DTL_override = $temp_key_value[1];   $DTLOset++;}

																#	$event_string = "|     Dial Timeout Lead DEBUG: $c|$DTLOset|$AD_field|$lead_id|$temp_key_value[0]|$temp_key_value[1]|$DTL_override|$skip_line|$source_id|";
																#	 &event_logger;

																	}
																}
															}
														$c++;
														}
													# Dial Timeout Lead debug logging
													$event_string = "|     Dial Timeout Lead override: $DTLOset|$AD_field|$lead_id|$temp_key_value[0]|$temp_key_value[1]|$DTL_override|$skip_line|$c|";
													 &event_logger;
													}
												}
											### END check for Dial Timeout Lead override for this lead ###

											$stmtA = "DELETE FROM vicidial_hopper where lead_id=$lead_id";
											$affected_rows = $dbhA->do($stmtA);

											$CCID_on=0;   $CCID='';   $CCIDtype='';
											$local_DEF = 'Local/';
											$local_AMP = '@';
											$Local_out_prefix = '9';
											$Local_dial_timeout = '60';
											if ($DBIPdialtimeout[$user_CIPct] > 4) {$Local_dial_timeout = $DBIPdialtimeout[$user_CIPct];}
											if ($DTL_override > 4) {$Local_dial_timeout = $DTL_override;}
											$Local_dial_timeout = ($Local_dial_timeout * 1000);
											if (length($DBIPdialprefix[$user_CIPct]) > 0) {$Local_out_prefix = "$DBIPdialprefix[$user_CIPct]";}
											if (length($LIST_dial_prefix_override) > 0) {$Local_out_prefix = "$LIST_dial_prefix_override";}
											if (length($DBIPvdadexten[$user_CIPct]) > 0) {$VDAD_dial_exten = "$DBIPvdadexten[$user_CIPct]";}
											else {$VDAD_dial_exten = "$answer_transfer_agent";}

											# add the routing prefix if using Asterisk 13
											if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} > 11))
												{
												$VDAD_dial_exten = $routing_prefix . $VDAD_dial_exten;
												}

											$temp_CID='';
											if ( (length($LIST_cid_group_override) > 0) && ($LIST_cid_group_override !~ /\-\-\-DISABLED\-\-\-/) )
												{
												$stmtA = "SELECT cid_group_type FROM vicidial_cid_groups where cid_group_id='$LIST_cid_group_override';";
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$cid_group_type =	$aryA[0];
													$temp_CID='';
													$temp_vcca='';
													$temp_ac='';

													if ($cid_group_type =~ /AREACODE/)
														{
														$temp_ac_two = substr("$phone_number", 0, 2);
														$temp_ac_three = substr("$phone_number", 0, 3);
														$temp_ac_four = substr("$phone_number", 0, 4);
														$temp_ac_five = substr("$phone_number", 0, 5);
														$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$LIST_cid_group_override' and areacode IN('$temp_ac_two','$temp_ac_three','$temp_ac_four','$temp_ac_five') and active='Y' order by CAST(areacode as SIGNED INTEGER) asc, call_count_today desc limit 100000;";
														}
													if ($cid_group_type =~ /STATE/)
														{
														$temp_state = $state;
														$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$LIST_cid_group_override' and areacode IN('$temp_state') and active='Y' order by call_count_today desc limit 100000;";
														}
													if ($cid_group_type =~ /NONE/)
														{
														$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$LIST_cid_group_override' and active='Y' order by call_count_today desc limit 100000;";
														}
													$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
													$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
													$sthArows=$sthA->rows;
													$act=0;
													while ($sthArows > $act)
														{
														@aryA = $sthA->fetchrow_array;
														$temp_vcca =	$aryA[0];
														$temp_ac =		$aryA[1];
														$act++;
														}
													if ($act > 0) 
														{
														$sthA->finish();
														$stmtA="UPDATE vicidial_campaign_cid_areacodes set call_count_today=(call_count_today + 1) where campaign_id='$LIST_cid_group_override' and areacode='$temp_ac' and outbound_cid='$temp_vcca';";
														$affected_rows = $dbhA->do($stmtA);

														if ($cid_group_type =~ /NONE/)
															{
															$stmtA="UPDATE vicidial_cid_groups set cid_auto_rotate_calls=(cid_auto_rotate_calls + 1) where cid_group_id='$LIST_cid_group_override';";
															$affected_rows = $dbhA->do($stmtA);
															}
														}
											else
														{$sthA->finish();}
													$temp_CID = $temp_vcca;
													$temp_CID =~ s/\D//gi;
													}
												}
											if ( (length($campaign_cid_override) > 6) || (length($temp_CID) > 6) )
												{
												if (length($temp_CID) > 6) 
													{$CCID = "$temp_CID";   $CCID_on++;   $CCIDtype='LIST_CID_GROUP';}
												else
													{$CCID = "$campaign_cid_override";   $CCID_on++;   $CCIDtype='LIST_CID';}
												}
											else
												{
												if (length($DBIPcampaigncid[$user_CIPct]) > 6) {$CCID = "$DBIPcampaigncid[$user_CIPct]";   $CCID_on++;   $CCIDtype='CAMPAIGN_CID';}
												if ($DBIPuse_custom_cid[$user_CIPct] =~ /Y/)
													{
													$temp_CID = $security_phrase;
													$temp_CID =~ s/\D//gi;
													if (length($temp_CID) > 6)
														{$CCID = "$temp_CID";   $CCID_on++;   $CCIDtype='CUSTOM_CID';}
													}
												$CIDG_set=0;
												if ($DBIPcid_group_id[$user_CIPct] !~ /\-\-\-DISABLED\-\-\-/)
													{
													$stmtA = "SELECT cid_group_type FROM vicidial_cid_groups where cid_group_id='$DBIPcid_group_id[$user_CIPct]';";
													$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
													$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
													$sthArows=$sthA->rows;
													if ($sthArows > 0)
														{
														@aryA = $sthA->fetchrow_array;
														$cid_group_type =	$aryA[0];
														$temp_CID='';
														$temp_vcca='';
														$temp_ac='';

														if ($cid_group_type =~ /AREACODE/)
															{
															$temp_ac_two = substr("$phone_number", 0, 2);
															$temp_ac_three = substr("$phone_number", 0, 3);
															$temp_ac_four = substr("$phone_number", 0, 4);
															$temp_ac_five = substr("$phone_number", 0, 5);
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id[$user_CIPct]' and areacode IN('$temp_ac_two','$temp_ac_three','$temp_ac_four','$temp_ac_five') and active='Y' order by CAST(areacode as SIGNED INTEGER) asc, call_count_today desc limit 100000;";
															}
														if ($cid_group_type =~ /STATE/)
															{
															$temp_state = $state;
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id[$user_CIPct]' and areacode IN('$temp_state') and active='Y' order by call_count_today desc limit 100000;";
															}
														if ($cid_group_type =~ /NONE/)
															{
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id[$user_CIPct]' and active='Y' order by call_count_today desc limit 100000;";
															}
														$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
														$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
														$sthArows=$sthA->rows;
														$act=0;
														while ($sthArows > $act)
															{
															@aryA = $sthA->fetchrow_array;
															$temp_vcca =	$aryA[0];
															$temp_ac =		$aryA[1];
															$act++;
															}
														if ($act > 0)
															{
															$sthA->finish();
															$stmtA="UPDATE vicidial_campaign_cid_areacodes set call_count_today=(call_count_today + 1) where campaign_id='$DBIPcid_group_id[$user_CIPct]' and areacode='$temp_ac' and outbound_cid='$temp_vcca';";
															$affected_rows = $dbhA->do($stmtA);

															if ($cid_group_type =~ /NONE/)
																{
																$stmtA="UPDATE vicidial_cid_groups set cid_auto_rotate_calls=(cid_auto_rotate_calls + 1) where cid_group_id='$DBIPcid_group_id[$user_CIPct]';";
																$affected_rows = $dbhA->do($stmtA);
																}
															}
														else
															{$sthA->finish();}
														$temp_CID = $temp_vcca;
														$temp_CID =~ s/\D//gi;
														if (length($temp_CID) > 6)
															{$CCID = "$temp_CID";   $CCID_on++;   $CIDG_set++;   $CCIDtype='CID_GROUP';}
														}
													}
												if ( ($DBIPcid_group_id_two[$user_CIPct] !~ /\-\-\-DISABLED\-\-\-/) && ($CIDG_set < 1) )
													{
													$stmtA = "SELECT cid_group_type FROM vicidial_cid_groups where cid_group_id='$DBIPcid_group_id_two[$user_CIPct]';";
													$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
													$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
													$sthArows=$sthA->rows;
													if ($sthArows > 0)
														{
														@aryA = $sthA->fetchrow_array;
														$cid_group_type =	$aryA[0];
														$temp_CID='';
														$temp_vcca='';
														$temp_ac='';

														if ($cid_group_type =~ /AREACODE/)
															{
															$temp_ac_two = substr("$phone_number", 0, 2);
															$temp_ac_three = substr("$phone_number", 0, 3);
															$temp_ac_four = substr("$phone_number", 0, 4);
															$temp_ac_five = substr("$phone_number", 0, 5);
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id_two[$user_CIPct]' and areacode IN('$temp_ac_two','$temp_ac_three','$temp_ac_four','$temp_ac_five') and active='Y' order by CAST(areacode as SIGNED INTEGER) asc, call_count_today desc limit 100000;";
															}
														if ($cid_group_type =~ /STATE/)
															{
															$temp_state = $state;
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id_two[$user_CIPct]' and areacode IN('$temp_state') and active='Y' order by call_count_today desc limit 100000;";
															}
														if ($cid_group_type =~ /NONE/)
															{
															$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcid_group_id_two[$user_CIPct]' and active='Y' order by call_count_today desc limit 100000;";
															}
														$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
														$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
														$sthArows=$sthA->rows;
														$act=0;
														while ($sthArows > $act)
															{
															@aryA = $sthA->fetchrow_array;
															$temp_vcca =	$aryA[0];
															$temp_ac =		$aryA[1];
															$act++;
															}
														if ($act > 0) 
															{
															$sthA->finish();
															$stmtA="UPDATE vicidial_campaign_cid_areacodes set call_count_today=(call_count_today + 1) where campaign_id='$DBIPcid_group_id_two[$user_CIPct]' and areacode='$temp_ac' and outbound_cid='$temp_vcca';";
															$affected_rows = $dbhA->do($stmtA);

															if ($cid_group_type =~ /NONE/)
																{
																$stmtA="UPDATE vicidial_cid_groups set cid_auto_rotate_calls=(cid_auto_rotate_calls + 1) where cid_group_id='$DBIPcid_group_id_two[$user_CIPct]';";
																$affected_rows = $dbhA->do($stmtA);
																}
															}
														else
															{$sthA->finish();}
														$temp_CID = $temp_vcca;
														$temp_CID =~ s/\D//gi;
														if (length($temp_CID) > 6) 
															{$CCID = "$temp_CID";   $CCID_on++;   $CIDG_set++;   $CCIDtype='CID_GROUP_TWO';}
														}
													}
												if ( ($DBIPuse_custom_cid[$user_CIPct] =~ /AREACODE/) && ($CIDG_set < 1) )
													{
													$temp_CID='';
													$temp_vcca='';
													$temp_ac='';
													$temp_ac_two = substr("$phone_number", 0, 2);
													$temp_ac_three = substr("$phone_number", 0, 3);
													$temp_ac_four = substr("$phone_number", 0, 4);
													$temp_ac_five = substr("$phone_number", 0, 5);
													$stmtA = "SELECT outbound_cid,areacode FROM vicidial_campaign_cid_areacodes where campaign_id='$DBIPcampaign[$user_CIPct]' and areacode IN('$temp_ac_two','$temp_ac_three','$temp_ac_four','$temp_ac_five') and active='Y' order by CAST(areacode as SIGNED INTEGER) asc, call_count_today desc limit 100000;";
													$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
													$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
													$sthArows=$sthA->rows;
													$act=0;
													while ($sthArows > $act)
														{
														@aryA = $sthA->fetchrow_array;
														$temp_vcca =	$aryA[0];
														$temp_ac =		$aryA[1];
														$act++;
														}
													if ($act > 0)
														{
														$sthA->finish();
														$stmtA="UPDATE vicidial_campaign_cid_areacodes set call_count_today=(call_count_today + 1) where campaign_id='$DBIPcampaign[$user_CIPct]' and areacode='$temp_ac' and outbound_cid='$temp_vcca';";
														$affected_rows = $dbhA->do($stmtA);
														}
													else
														{$sthA->finish();}
													$temp_CID = $temp_vcca;
													$temp_CID =~ s/\D//gi;
													if (length($temp_CID) > 6)
														{$CCID = "$temp_CID";   $CCID_on++;   $CCIDtype='AC_CID';}
													}
												}

											if ($Local_out_prefix =~ /x/i) {$Local_out_prefix = '';}

											if ($RECcount)
												{
												if ( (length($RECprefix)>0) && ($called_count < $RECcount) )
												   {$Local_out_prefix .= "$RECprefix";}
												}

											if ($lists_update !~ /'$list_id'/) {$lists_update .= "'$list_id',"; $LUcount++;}

											$lead_id_call_list .= "$lead_id|";

											if (length($alt_dial)<1) {$alt_dial='MAIN';}

											$TFhourSTATE='';
											if ($SScall_limit_24hour > 0) 
												{
												$TFH_areacode = substr($phone_number, 0, 3);
												$stmtY = "SELECT state,country FROM vicidial_phone_codes where country_code='$phone_code' and areacode='$TFH_areacode';";
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
												}

											### whether to omit phone_code or not
											if ($DBIPomitcode[$user_CIPct] > 0)
												{$Ndialstring = "$Local_out_prefix$phone_number";}
											else
												{$Ndialstring = "$Local_out_prefix$phone_code$phone_number";}

											if (length($ext_context) < 1) {$ext_context='default';}
											### use manager middleware-agi to connect the next call to the meetme room of an agent after call is answered
											if ($CCID_on) {$CIDstring = "\"$VqueryCID\" <$CCID>";}
											else {$CIDstring = "$VqueryCID";}

										### insert a NEW record to the vicidial_manager table to be processed
											$stmtA = "INSERT INTO vicidial_manager values('','','$SQLdate','NEW','N','$DBIPaddress[$user_CIPct]','','Originate','$VqueryCID','Exten: $VDAD_dial_exten','Context: $ext_context','Channel: $local_DEF$Ndialstring$local_AMP$ext_context','Priority: 1','Callerid: $CIDstring','Timeout: $Local_dial_timeout','','','','VDACnote: $DBIPcampaign[$user_CIPct]|$lead_id|$phone_code|$phone_number|OUT|$alt_dial|$DBIPqueue_priority[$user_CIPct]')";
											$affected_rowsA = $dbhA->do($stmtA);

										### insert a SENT record to the vicidial_auto_calls table
											$stmtB = "INSERT INTO vicidial_auto_calls (server_ip,campaign_id,status,lead_id,callerid,phone_code,phone_number,call_time,call_type,alt_dial,queue_priority,last_update_time) values('$DBIPaddress[$user_CIPct]','$DBIPcampaign[$user_CIPct]','SENT',$lead_id,'$VqueryCID','$phone_code','$phone_number','$SQLdate','OUT','$alt_dial','$DBIPqueue_priority[$user_CIPct]','$SQLdate')";
											$affected_rowsB = $dbhA->do($stmtB);

										### insert log record into vicidial_dial_log table
											$stmtC = "INSERT INTO vicidial_dial_log SET caller_code='$VqueryCID',lead_id=$lead_id,server_ip='$DBIPaddress[$user_CIPct]',call_date='$SQLdate',extension='$VDAD_dial_exten',channel='$local_DEF$Ndialstring$local_AMP$ext_context',timeout='$Local_dial_timeout',outbound_cid='$CIDstring',context='$ext_context';";
											$affected_rowsC = $dbhA->do($stmtC);

										### insert log record into vicidial_lead_24hour_calls table 
											$stmtD = "INSERT INTO vicidial_lead_24hour_calls SET lead_id='$lead_id',list_id='$list_id',call_date=NOW(),phone_number='$phone_number',phone_code='$phone_code',state='$TFhourSTATE',call_type='AUTO';";
											$affected_rowsD = $dbhA->do($stmtD);

										### insert log record into vicidial_dial_cid_log table 
											$stmtE = "INSERT INTO vicidial_dial_cid_log SET caller_code='$VqueryCID',call_date=NOW(),call_type='OUT',call_alt='$alt_dial',outbound_cid='$CCID',outbound_cid_type='$CCIDtype';";
											$affected_rowsE = $dbhA->do($stmtE);

											$event_string = "|     number call dialed|$DBIPcampaign[$user_CIPct]|$VqueryCID|$affected_rowsA|$stmtA|$gmt_offset_now|$alt_dial|$affected_rowsB|$affected_rowsC|$affected_rowsD|$affected_rowsE|$CCIDtype|";
											 &event_logger;

											### sleep for a five hundredths of a second to not flood the server with new calls
										#	usleep(1*50*1000);
											usleep(1*$per_call_delay*1000);
											$calls_placed++;
											}
										}
									$call_CMPIPct++;
									}
								}
							}
						}
					}
				}
			if ($calls_placed > 0)
				{
				$stmtA="UPDATE vicidial_campaigns SET campaign_calldate='$now_date' where campaign_id='$DBIPcampaign[$user_CIPct]';";
				$affected_rows = $dbhA->do($stmtA);
				$calls_placed=0;
				}
			$user_CIPct++;
			}





	&get_time_now;

	###############################################################################
	###### third we will grab the callerids of the vicidial_auto_calls records and check for dead calls
	######    we also check to make sure that it isn't a call that has been transferred,
	######    if it has been we need to leave the vicidial_list status alone
	###############################################################################

		@KLcallerid = @MT;
		@KLserver_ip = @MT;
		@KLchannel = @MT;
		@KLuniqueid = @MT;
		@KLstatus = @MT;
		@KLcalltime = @MT;
		$kill_vac=0;
		$dead_call_ct=0;

		$stmtA = "SELECT callerid,server_ip,channel,uniqueid,status,call_time FROM vicidial_auto_calls where server_ip='$server_ip' order by call_time;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		$rec_countCUSTDATA=0;
		$kill_vac=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$KLcallerid[$kill_vac]		= $aryA[0];
			$KLserver_ip[$kill_vac]		= $aryA[1];
			$KLchannel[$kill_vac]		= $aryA[2];
			$KLuniqueid[$kill_vac]		= $aryA[3];
			$KLstatus[$kill_vac]		= $aryA[4];
			$KLcalltime[$kill_vac]		= $aryA[5];
			$kill_vac++;
			$rec_count++;
			}
		$sthA->finish();

		$kill_vac=0;
		foreach(@KLcallerid)
			{
			if (length($KLserver_ip[$kill_vac]) > 7)
				{
				$end_epoch=0;   $CLuniqueid='';   $start_epoch=0;   $CLlast_update_time=0;
				$KLcalleridCHECK[$kill_vac]=$KLcallerid[$kill_vac];
				$KLcalleridCHECK[$kill_vac] =~ s/\W//gi;

				if ( (length($KLcalleridCHECK[$kill_vac]) > 17) && ($KLcalleridCHECK[$kill_vac] =~ /\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) )
					{
					$stmtA = "SELECT end_epoch,uniqueid,start_epoch FROM call_log where caller_code='$KLcallerid[$kill_vac]' and server_ip='$KLserver_ip[$kill_vac]' order by end_epoch, start_time desc limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$rec_count=0;
					$rec_countCUSTDATA=0;
					while ($sthArows > $rec_count)
						{
						@aryA = $sthA->fetchrow_array;
						$end_epoch		= $aryA[0];
						$CLuniqueid		= $aryA[1];
						$start_epoch	= $aryA[2];
						$rec_count++;
						}
					$sthA->finish();
					}

				if ( (length($KLuniqueid[$kill_vac]) > 11) && (length($CLuniqueid) < 12) )
					{
					$stmtA = "SELECT end_epoch,uniqueid,start_epoch FROM call_log where uniqueid='$KLuniqueid[$kill_vac]' and server_ip='$KLserver_ip[$kill_vac]' order by end_epoch, start_time desc limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$rec_count=0;
					$rec_countCUSTDATA=0;
					while ($sthArows > $rec_count)
						{
						@aryA = $sthA->fetchrow_array;
						$end_epoch		= $aryA[0];
						$CLuniqueid		= $aryA[1];
						$start_epoch	= $aryA[2];
						$rec_count++;
						}
					$sthA->finish();
					}

				# Find out if (the call is parked
				$PARKchannel=0;
				if (length($KLcallerid[$kill_vac]) > 17)
					{
					$stmtA = "SELECT count(*) from parked_channels where channel_group='$KLcallerid[$kill_vac]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsPK=$sthA->rows;
					if ($sthArowsPK > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$PARKchannel	= $aryA[0];
						}
					$sthA->finish();
					}

				$CLlead_id=''; $auto_call_id=''; $CLstatus=''; $CLcampaign_id=''; $CLphone_number=''; $CLphone_code=''; $CLuser='';

				$stmtA = "SELECT auto_call_id,lead_id,phone_number,status,campaign_id,phone_code,alt_dial,stage,call_type,UNIX_TIMESTAMP(last_update_time) FROM vicidial_auto_calls where callerid='$KLcallerid[$kill_vac]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				$rec_countCUSTDATA=0;
				while ($sthArows > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$auto_call_id	=		$aryA[0];
					$CLlead_id		=		$aryA[1];
					$CLphone_number	=		$aryA[2];
					$CLstatus		=		$aryA[3];
					$CLcampaign_id	=		$aryA[4];
					$CLphone_code	=		$aryA[5];
					$CLalt_dial		=		$aryA[6];
					$CLstage		=		$aryA[7];
					$CLcall_type	=		$aryA[8];
					$CLlast_update_time =	$aryA[9];
					$rec_count++;
					}
				$sthA->finish();

				$stmtA = "SELECT user FROM vicidial_live_agents where callerid='$KLcallerid[$kill_vac]'";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$CLuser	= $aryA[0];
					}
				$sthA->finish();

				if ( (length($CLlead_id) < 1) || ($CLlead_id < 1) )
					{
					$CLlead_id = $KLcallerid[$kill_vac];
					$CLlead_id = substr($CLlead_id, 10, 10);
					$CLlead_id = ($CLlead_id + 0);
					}

				$CLauto_alt_threshold=0;
				if ($CLcall_type =~ /IN/)
					{
					$stmtA = "SELECT drop_call_seconds FROM vicidial_inbound_groups where group_id='$CLcampaign_id';";
					$timeout_leeway = 30;
					}
				else
					{
					$stmtA = "SELECT dial_timeout,drop_call_seconds,auto_alt_threshold FROM vicidial_campaigns where campaign_id='$CLcampaign_id';";
					$timeout_leeway = 7;
					}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$CLdial_timeout	=		$aryA[0];
					$CLdrop_call_seconds =	$aryA[1];
					$CLauto_alt_threshold =	$aryA[2];
					}
				$sthA->finish();

				$dialtime_log = ($end_epoch - $start_epoch);
				$dialtime_catch = ($now_date_epoch - ($start_epoch + $timeout_leeway));
				$hanguptime_manual = ($end_epoch + 60);
				if ($dialtime_catch > 100000) {$dialtime_catch=0;}
				$call_timeout = ($CLdial_timeout + $CLdrop_call_seconds);
				if ($CLstage =~ /SURVEY|REMIND/) {$call_timeout = ($call_timeout + 120);}
				if ( ($CLstage =~ /SENT/) && ($call_timeout > 110) ) {$call_timeout = 110;}

		#		$event_string = "|     vac test: |$auto_call_id|$CLstatus|$KLcalltime[$kill_vac]|$CLlead_id|$KLcallerid[$kill_vac]|$end_epoch|$KLchannel[$kill_vac]|$CLcall_type|$CLdial_timeout|$CLdrop_call_seconds|$call_timeout|$dialtime_log|$dialtime_catch|$PARKchannel|";
		#		&event_logger;

				if ( ( ($dialtime_log >= $call_timeout) || ($dialtime_catch >= $call_timeout) || ($CLstatus =~ /BUSY|DISCONNECT|XFER|CLOSER|CARRIERFAIL/) ) && ($PARKchannel < 1) )
					{
					if ( ($CLcall_type !~ /IN/) && ($CLstatus !~ /IVR/) )
						{
						if ($CLstatus !~ /XFER|CLOSER/)
							{
							$stmtA = "DELETE from vicidial_auto_calls where auto_call_id='$auto_call_id'";
							$affected_rows = $dbhA->do($stmtA);

							$stmtA = "UPDATE vicidial_live_agents set ring_callerid='' where ring_callerid='$KLcallerid[$kill_vac]';";
							$affected_rowsX = $dbhA->do($stmtA);

							$event_string = "|     dead call vac deleted|$auto_call_id|$CLstatus|$CLlead_id|$KLcallerid[$kill_vac]|$end_epoch|$affected_rows|$KLchannel[$kill_vac]|$CLcall_type|$CLdial_timeout|$CLdrop_call_seconds|$call_timeout|$dialtime_log|$dialtime_catch|$affected_rowsX|";
							 &event_logger;

							$CLstage =~ s/LIVE|-//gi;
							if ($CLstage < 0.25) {$CLstage=0;}

							$xCLlist_id=0;
							$called_count=0;
							$stmtA="SELECT list_id,called_count from vicidial_list where lead_id='$CLlead_id' limit 1;";
								if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsVLL=$sthA->rows;
							if ($sthArowsVLL > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$xCLlist_id = 	$aryA[0];
								$called_count = $aryA[1];
								}
							$sthA->finish();

							$LISTauto_alt_threshold=-1;
							$stmtA="SELECT auto_alt_threshold from vicidial_lists where list_id='$xCLlist_id' limit 1;";
								if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsVLL=$sthA->rows;
							if ($sthArowsVLL > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$LISTauto_alt_threshold = 	$aryA[0];
								}
							$sthA->finish();

							$temp_auto_alt_threshold = $CLauto_alt_threshold;
							if ($LISTauto_alt_threshold > -1 ) {$temp_auto_alt_threshold = $LISTauto_alt_threshold;}
							$auto_alt_lead_disabled=0;
							if ( ($temp_auto_alt_threshold > 0) && ($called_count >= $temp_auto_alt_threshold) ) 
								{
								$auto_alt_lead_disabled=1;
								if ($ADB > 0) {$aad_string = "ALT-00: $CLlead_id|$VD_auto_alt_dial|$CLauto_alt_threshold|$LISTauto_alt_threshold|($called_count <> $temp_auto_alt_threshold)|auto_alt_lead_disabled: $auto_alt_lead_disabled|";   &aad_output;}
								}

							if ($CLstatus =~ /BUSY/) {$CLnew_status = 'AB';}
							else
								{
								$new_status_set=0;
								if ($CLstatus =~ /DISCONNECT/) {$CLnew_status = 'ADC';   $new_status_set=1;}
								if ($CLstatus =~ /ADCCAR/) {$CLnew_status = 'ADCCAR';   $new_status_set=1;}
								if ($CLstatus =~ /DNCCAR/) {$CLnew_status = 'DNCCAR';   $new_status_set=1;}
								if ($new_status_set < 1) {$CLnew_status = 'NA';}
								}
							if ($CLstatus =~ /LIVE/) {$CLnew_status = 'DROP';}
							else
								{
								$insertVLuser = 'VDAD';
								$insertVLcount=0;
								if ($KLcallerid[$kill_vac] =~ /^M\d\d\d\d\d\d\d\d\d\d/)
									{
									$beginUNIQUEID = $CLuniqueid;
									$beginUNIQUEID =~ s/\..*//gi;
									$insertVLuser = $CLuser;
									$stmtA="SELECT count(*) from vicidial_log where lead_id='$CLlead_id' and user='$CLuser' and phone_number='$CLphone_number' and uniqueid LIKE \"$beginUNIQUEID%\";";
										if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsVL=$sthA->rows;
									if ($sthArowsVL > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$insertVLcount = 	$aryA[0];
										}
									$sthA->finish();
									}

								##############################################################
								### BEGIN - CPD Look for result for NA/B/DC calls
								##############################################################
								$stmtA = "SELECT result FROM vicidial_cpd_log where callerid='$KLcallerid[$kill_vac]' order by cpd_id desc limit 1;";
									if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$cpd_result		= $aryA[0];
									$CPDfound=0;
									if ($cpd_result =~ /Busy/i)					{$CLnew_status='CPDB';   $CPDfound++;}
									if ($cpd_result =~ /Unknown/i)				{$CLnew_status='CPDUK';   $CPDfound++;}
									if ($cpd_result =~ /All-Trunks-Busy/i)		{$CLnew_status='CPDATB';   $CPDfound++;}
									if ($cpd_result =~ /No-Answer/i)			{$CLnew_status='CPDNA';   $CPDfound++;}
									if ($cpd_result =~ /Reject/i)				{$CLnew_status='CPDREJ';   $CPDfound++;}
									if ($cpd_result =~ /Invalid-Number/i)		{$CLnew_status='CPDINV';   $CPDfound++;}
									if ($cpd_result =~ /Service-Unavailable/i)	{$CLnew_status='CPDSUA';   $CPDfound++;}
									if ($cpd_result =~ /Sit-Intercept/i)		{$CLnew_status='CPDSI';   $CPDfound++;}
									if ($cpd_result =~ /Sit-No-Circuit/i)		{$CLnew_status='CPDSNC';   $CPDfound++;}
									if ($cpd_result =~ /Sit-Reorder/i)			{$CLnew_status='CPDSR';   $CPDfound++;}
									if ($cpd_result =~ /Sit-Unknown/i)			{$CLnew_status='CPDSUK';   $CPDfound++;}
									if ($cpd_result =~ /Sit-Vacant/i)			{$CLnew_status='CPDSV';   $CPDfound++;}
									if ($cpd_result =~ /\?\?\?/i)				{$CLnew_status='CPDERR';   $CPDfound++;}
									if ($cpd_result =~ /Fax|Modem/i)			{$CLnew_status='AFAX';   $CPDfound++;}
									if ($cpd_result =~ /Answering-Machine/i)	{$CLnew_status='AA';   $CPDfound++;}
									}
								$sthA->finish();

								##############################################################
								### END - CPD Look for result for NA/B/DC calls
								##############################################################

								$end_epoch = $now_date_epoch;
								if ($insertVLcount < 1)
									{
									$stmtA = "INSERT INTO vicidial_log (uniqueid,lead_id,campaign_id,call_date,start_epoch,status,phone_code,phone_number,user,processed,length_in_sec,end_epoch,alt_dial,list_id,called_count) values('$CLuniqueid','$CLlead_id','$CLcampaign_id','$SQLdate','$now_date_epoch','$CLnew_status','$CLphone_code','$CLphone_number','$insertVLuser','N','$CLstage','$end_epoch','$CLalt_dial','$xCLlist_id','$called_count')";
										if($M){print STDERR "\n|$stmtA|\n";}
									$affected_rows = $dbhA->do($stmtA);

									$stmtB = "INSERT INTO vicidial_log_extended set uniqueid='$CLuniqueid',server_ip='$server_ip',call_date='$SQLdate',lead_id = '$CLlead_id',caller_code='$KLcallerid[$kill_vac]',custom_call_id='';";
									$affected_rowsB = $dbhA->do($stmtB);

									if ( ($enable_drop_lists > 1) && ($CLnew_status =~ /DROP/) )
										{
										$stmtC="INSERT IGNORE INTO vicidial_drop_log SET uniqueid='$CLuniqueid',server_ip='$server_ip',drop_date=NOW(),lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='DROP',phone_code='$CLphone_code',phone_number='$CLphone_number';";
										$affected_rowsC = $dbhA->do($stmtC);
										$event_string = "--    DROP vicidial_drop_log insert: |$affected_rowsC|$CLuniqueid|$CLlead_id|$CLnew_status|";
										 &event_logger;
										}

									$event_string = "|     dead NA call added to log $CLuniqueid|$CLlead_id|$CLphone_number|$CLstatus|$CLnew_status|$affected_rows|$affected_rowsB|$insertVLcount";
									 &event_logger;
									}
								else
									{
									$stmtA = "UPDATE vicidial_log SET status='$CLnew_status',length_in_sec='$CLstage',end_epoch='$end_epoch',alt_dial='$CLalt_dial' where lead_id='$CLlead_id' and user='$CLuser' and phone_number='$CLphone_number' and uniqueid LIKE \"$beginUNIQUEID%\";";
										if($M){print STDERR "\n|$stmtA|\n";}
									$affected_rows = $dbhA->do($stmtA);

									$event_string = "|     dead NA call updated in log $CLuniqueid|$CLlead_id|$CLphone_number|$CLstatus|$CLnew_status|$affected_rows|$insertVLcount|$beginUNIQUEID";
									 &event_logger;
									}
								}

							if ( ($CLlead_id > 0) && ($CLstatus !~ /CARRIERFAIL/i) )
								{
								$stmtA = "UPDATE vicidial_list set status='$CLnew_status' where lead_id='$CLlead_id'";
								$affected_rows = $dbhA->do($stmtA);

								$event_string = "|     dead call vac lead marked $CLnew_status|$CLlead_id|$CLphone_number|$CLstatus|";
								 &event_logger;
								}
							else
								{
								$event_string = "|     dead call vac lead NOT UPDATED $CLnew_status|$CLlead_id|$CLphone_number|$CLstatus|";
								 &event_logger;
								}

					#		if ($KLcallerid[$kill_vac] !~ /^M\d\d\d\d\d\d\d\d\d\d/)
					#			{
					#			$stmtA = "UPDATE vicidial_live_agents set status='PAUSED',random_id='10' where callerid='$KLcallerid[$kill_vac]';";
					#			$Vaffected_rows = $dbhA->do($stmtA);
					#			if ($Vaffected_rows > 0)
					#				{
					#				$stmtA = "SELECT agent_log_id,user from vicidial_live_agents where callerid='$KLcallerid[$kill_vac]';";
					#				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					#				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					#				$sthArowsML=$sthA->rows;
					#				$lagged_ids=0;
					#				while ($sthArowsML > $lagged_ids)
					#					{
					#					@aryA = $sthA->fetchrow_array;
					#					$MLAGagent_log_id[$lagged_ids] =	$aryA[0];
					#					$MLAGuser[$lagged_ids] =			$aryA[1];
					#					$lagged_ids++;
					#					}
					#				$sthA->finish();
					#				$lagged_ids=0;
					#				while ($sthArowsML > $lagged_ids)
					#					{
					#					$stmtA = "UPDATE vicidial_agent_log set sub_status='LAGGED' where agent_log_id='$MLAGagent_log_id[$lagged_ids]';";
					#					$VLaffected_rows = $dbhA->do($stmtA);
					#					if ($enable_queuemetrics_logging > 0)
					#						{
					#						$secX = time();
					#						$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
					#						 or die "Couldn't connect to database: " . DBI->errstr;
					#						if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}
					#						$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='NONE',queue='NONE',agent='Agent/$MLAGuser[$lagged_ids]',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LAGGED';";
					#						$Baffected_rows = $dbhB->do($stmtB);
					#						$dbhB->disconnect();
					#						}
					#					$lagged_ids++;
					#					}
					#				$event_string = "|     dead call vla agent PAUSED $Vaffected_rows|$VLaffected_rows|$CLlead_id|$CLphone_number|$CLstatus|";
					#				 &event_logger;
					#				}
					#			}

							if ( ($enable_queuemetrics_logging > 0) && ($CLstatus =~ /LIVE/) )
								{
								$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
								 or die "Couldn't connect to database: " . DBI->errstr;

								if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

								$secX = time();
								$Rtarget = ($secX - 21600);	# look for VDCL entry within last 6 hours
								($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($Rtarget);
								$Ryear = ($Ryear + 1900);
								$Rmon++;
								if ($Rmon < 10) {$Rmon = "0$Rmon";}
								if ($Rmday < 10) {$Rmday = "0$Rmday";}
								if ($Rhour < 10) {$Rhour = "0$Rhour";}
								if ($Rmin < 10) {$Rmin = "0$Rmin";}
								if ($Rsec < 10) {$Rsec = "0$Rsec";}
									$RSQLdate = "$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

								### find original queue position of the call
								$queue_position=1;
								$stmtA = "SELECT queue_position,call_date FROM vicidial_closer_log where lead_id='$CLlead_id' and campaign_id='$CLcampaign_id' and call_date > \"$RSQLdate\" order by closecallid desc limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$queue_position =	$aryA[0];
									$VCLcall_date =		$aryA[1];
									}
								$sthA->finish();

								### find current number of calls in this queue to find position when channel hung up
								$current_position=1;
								$stmtA = "SELECT count(*) FROM vicidial_auto_calls where status = 'LIVE' and campaign_id='$CLcampaign_id' and call_time < '$VCLcall_date';";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$current_position =	($aryA[0] + 1);
									}
								$sthA->finish();

								$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$KLcallerid[$kill_vac]',queue='$CLcampaign_id',agent='NONE',verb='ABANDON',data1='$current_position',data2='$queue_position',data3='$CLstage',serverid='$queuemetrics_log_id';";
								$Baffected_rows = $dbhB->do($stmtB);

								$dbhB->disconnect();
								}

							### check to see if campaign has alt_dial or call quotas enabled
							$VD_auto_alt_dial = 'NONE';
							$VD_auto_alt_dial_statuses='';
							$VD_call_quota_lead_ranking='DISABLED';
							$stmtA="SELECT auto_alt_dial,auto_alt_dial_statuses,use_internal_dnc,use_campaign_dnc,use_other_campaign_dnc,call_quota_lead_ranking,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,auto_alt_threshold,hopper_hold_inserts FROM vicidial_campaigns where campaign_id='$CLcampaign_id';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$VD_auto_alt_dial	=			$aryA[0];
								$VD_auto_alt_dial_statuses	=	$aryA[1];
								$VD_use_internal_dnc =			$aryA[2];
								$VD_use_campaign_dnc =			$aryA[3];
								$VD_use_other_campaign_dnc =	$aryA[4];
								$VD_call_quota_lead_ranking =	$aryA[5];
								$VD_call_limit_24hour_method =		$aryA[6];
								$VD_call_limit_24hour_scope =		$aryA[7];
								$VD_call_limit_24hour =				$aryA[8];
								$VD_call_limit_24hour_override =	$aryA[9];
								$VD_auto_alt_threshold =			$aryA[10];
								$VD_hopper_hold_inserts =			$aryA[11];
								if ($SShopper_hold_inserts < 1) {$VD_hopper_hold_inserts = 'DISABLED';}
								}
							$sthA->finish();

							##### BEGIN Call Quota Lead Ranking logging #####
							if ( ($SScall_quota_lead_ranking > 0) && ($VD_call_quota_lead_ranking !~ /^DISABLED$/i) )
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
										$stmtA = "SELECT list_id,called_count,rank FROM vicidial_list where lead_id='$CLlead_id';";
										$event_string = "|$stmtA|"; &event_logger;
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
										$stmtA = "SELECT call_date from vicidial_dial_log where lead_id='$CLlead_id' and call_date > \"$CQSQLdate\" and caller_code LIKE \"%$CLlead_id\" order by call_date desc limit 1;";
										$event_string = "|$stmtA|"; &event_logger;
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

											$event_string = "CQ-Debug 2: $VDLcall_datetime|$VDLcall_hourmin|$timeclock_end_of_day|$session_one_start|$session_one_end|$call_in_session|"; &event_logger;

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
												$stmtA = "SELECT first_call_date,UNIX_TIMESTAMP(first_call_date),last_call_date from vicidial_lead_call_quota_counts where lead_id='$CLlead_id' and list_id='$VLlist_id';";
												$event_string = "|$stmtA|"; &event_logger;
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
														$stmtA="UPDATE vicidial_lead_call_quota_counts SET last_call_date='$VDLcall_datetime',status='$CLnew_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() $session_updateSQL $day_updateSQL where lead_id='$CLlead_id' and list_id='$VLlist_id' and modify_date < NOW();";
														}
													else
														{
														# Update in the vicidial_lead_call_quota_counts table for this lead
														$stmtA="UPDATE vicidial_lead_call_quota_counts SET status='$CLnew_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() where lead_id='$CLlead_id' and list_id='$VLlist_id';";
														}
													$VLCQCaffected_rows_update = $dbhA->do($stmtA);
													$event_string = "--    VLCQC record updated: |$VLCQCaffected_rows_update|   |$stmtA|"; &event_logger;
													}
												else
													{
													# Insert new record into vicidial_lead_call_quota_counts table for this lead
													$stmtA="INSERT INTO vicidial_lead_call_quota_counts SET lead_id='$CLlead_id',list_id='$VLlist_id',first_call_date='$VDLcall_datetime',last_call_date='$VDLcall_datetime',status='$CLnew_status',called_count='$VLcalled_count',day_one_calls='1',rank='$tempVLrank',modify_date=NOW() $session_newSQL;";
													$VLCQCaffected_rows_update = $dbhA->do($stmtA);
													$event_string = "--    VLCQC record inserted: |$VLCQCaffected_rows_update|   |$stmtA|"; &event_logger;
													}

												if ( ($zero_rank_after_call > 0) && ($VLrank > 0) )
													{
													# Update this lead to rank=0
													$stmtA="UPDATE vicidial_list SET rank='0' where lead_id='$CLlead_id';";
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


							##### BEGIN AUTO ALT PHONE DIAL SECTION #####
								$event_string = "|$stmtA|$VD_auto_alt_dial|$VD_auto_alt_dial_statuses|$CLnew_status|$CLlead_id|$CLalt_dial";   &event_logger;
							if ($ADB > 0) {$aad_string = "ALT-01: $CLlead_id|$VD_auto_alt_dial|$CLalt_dial|$VD_auto_alt_dial_statuses|$auto_alt_lead_disabled|";   &aad_output;}
							if ( ($VD_auto_alt_dial_statuses =~ / $CLnew_status /) && ($CLlead_id > 0) && ($auto_alt_lead_disabled < 1) )
								{
								$alt_skip_reason='';   $addr3_skip_reason='';
								if ( ($VD_auto_alt_dial =~ /ALT_ONLY|ALT_AND_ADDR3|ALT_AND_EXTENDED/) && ($CLalt_dial =~ /NONE|MAIN/) )
									{
									$alt_dial_skip=0;
									$VD_alt_phone='';
									$VD_phone_code='';
									$temp_24hour_phone='';
									$temp_24hour_phone_code='';
									$stmtA="SELECT alt_phone,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$CLlead_id';";
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
										$event_string = "|$stmtA|$VD_alt_phone|";   &event_logger;
									if ($ADB > 0) {$aad_string = "ALT-02: $CLlead_id|$CLalt_dial|$VD_alt_phone|";   &aad_output;}
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
												{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_alt_phone';";}
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
											$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
										if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
											{
											$temp_campaign_id = $CLcampaign_id;
											if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
											if ($VD_use_campaign_dnc =~ /AREACODE/)
												{
												$alt_areacode = substr($VD_alt_phone, 0, 3);
												$alt_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_alt_phone','$alt_areacode') and campaign_id='$temp_campaign_id';";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_alt_phone' and campaign_id='$temp_campaign_id';";}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
												}
											$sthA->finish();
												$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
											}
										if ($VD_alt_dnc_count < 1)
											{
											$passed_24hour_call_count=1;
											if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
												{
												$temp_24hour_phone =		$VD_alt_phone;
												$temp_24hour_phone_code =	$VD_phone_code;
												$temp_24hour_state =		$VD_state;
												$temp_24hour_postal_code =	$VD_postal_code;
												if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$CLlead_id|\n";}
												&check_24hour_call_count;
												}
											if ($passed_24hour_call_count > 0) 
												{
																								$hopper_status='READY';
												if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
												$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ALT',user='',priority='25',source='A';";
												$affected_rows = $dbhA->do($stmtA);
												$event_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|";   &event_logger;
												$aad_string = "$CLlead_id|$VD_alt_phone|$CLcampaign_id|ALT|25|hopper insert|";   &aad_output;
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
										if ($ADB > 0) {$aad_string = "ALT-03: $CLlead_id|$CLalt_dial|ALT-skip|$alt_skip_reason|";   &aad_output;}
										$CLalt_dial='ALT';
										$aad_string = "$CLlead_id|$VD_alt_phone|$CLcampaign_id|ALT|0|hopper skip|$alt_skip_reason|";   &aad_output;
									}
									}
								if ($ADB > 0) {$aad_string = "ALT-04: $CLlead_id|$VD_auto_alt_dial|$CLalt_dial|";   &aad_output;}
								if ( ( ($VD_auto_alt_dial =~ /ADDR3_ONLY/) && ($CLalt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /ALT_AND_ADDR3/) && ($CLalt_dial =~ /ALT/) ) )
									{
									$addr3_dial_skip=0;
									$VD_address3='';
									$stmtA="SELECT address3,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$CLlead_id';";
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
										$event_string = "|$stmtA|$VD_address3|";   &event_logger;
									if ($ADB > 0) {$aad_string = "ALT-05: $CLlead_id|$CLalt_dial|$VD_address3|";   &aad_output;}
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
												{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_address3';";}
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
											$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
										if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
											{
											$temp_campaign_id = $CLcampaign_id;
											if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
											if ($VD_use_campaign_dnc =~ /AREACODE/)
												{
												$addr3_areacode = substr($VD_address3, 0, 3);
												$addr3_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_address3','$addr3_areacode') and campaign_id='$temp_campaign_id';";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_address3' and campaign_id='$temp_campaign_id';";}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
												}
											$sthA->finish();
												$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
											}
										if ($VD_alt_dnc_count < 1)
											{
											$passed_24hour_call_count=1;
											if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
												{
												$temp_24hour_phone =		$VD_address3;
												$temp_24hour_phone_code =	$VD_phone_code;
												$temp_24hour_state =		$VD_state;
												$temp_24hour_postal_code =	$VD_postal_code;
												if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$CLlead_id|\n";}
												&check_24hour_call_count;
												}
											if ($passed_24hour_call_count > 0) 
												{
												$hopper_status='READY';
												if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
												$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ADDR3',user='',priority='20',source='A';";
												$affected_rows = $dbhA->do($stmtA);
												$event_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|";   &event_logger;
												$aad_string = "$CLlead_id|$VD_address3|$CLcampaign_id|ADDR3|20|hopper insert|";   &aad_output;
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
										if ($ADB > 0) {$aad_string = "ALT-06: $CLlead_id|$CLalt_dial|ADDR3-skip|$addr3_skip_reason|";   &aad_output;}
										$CLalt_dial='ADDR3';
										if ($SYSLOG) {$aad_string = "$CLlead_id|$VD_address3|$CLcampaign_id|ADDR3|0|hopper skip|$addr3_skip_reason|";   &aad_output;}
									}
									}
								if ($ADB > 0) {$aad_string = "ALT-07: $CLlead_id|$VD_auto_alt_dial|$CLalt_dial|";   &aad_output;}
								if ( ( ($VD_auto_alt_dial =~ /EXTENDED_ONLY/) && ($CLalt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /ALT_AND_EXTENDED/) && ($CLalt_dial =~ /ALT/) ) || ( ($VD_auto_alt_dial =~ /ADDR3_AND_EXTENDED|ALT_AND_ADDR3_AND_EXTENDED/) && ($CLalt_dial =~ /ADDR3/) ) || ( ($VD_auto_alt_dial =~ /EXTENDED/) && ($CLalt_dial =~ /^X/) && ($CLalt_dial !~ /XLAST/) ) )
									{
									if ($CLalt_dial =~ /ADDR3/) {$Xlast=0;}
									else
										{
										$Xlast = $CLalt_dial;
										if ($CLalt_dial =~ /LAST/) {$Xlast=66000;}
										}
									if ($ADB > 0) {$aad_string = "ALT-08: $CLlead_id|$VD_auto_alt_dial|$CLalt_dial|$Xlast|";   &aad_output;}
									$Xlast =~ s/\D//gi;
									if (length($Xlast)<1)
										{$Xlast=0;}
									$VD_altdialx='';
									$stmtA="SELECT gmt_offset_now,state,list_id,postal_code FROM vicidial_list where lead_id='$CLlead_id';";
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
									$stmtA="SELECT count(*) FROM vicidial_list_alt_phones where lead_id='$CLlead_id';";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									if ($sthArows > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$alt_dial_phones_count = $aryA[0];
										}
									$sthA->finish();
										$event_string = "|$stmtA|$alt_dial_phones_count";   &event_logger;

									if ($ADB > 0) {$aad_string = "ALT-09: $CLlead_id|$CLalt_dial|$Xlast|$alt_dial_phones_count|";   &aad_output;}
									while ( ($alt_dial_phones_count > 0) && ($alt_dial_phones_count > $Xlast) && ($Xlast ne 'LAST') )
										{
										$Xlast++;
										$stmtA="SELECT alt_phone_id,phone_number,active,phone_code FROM vicidial_list_alt_phones where lead_id='$CLlead_id' and alt_phone_count='$Xlast';";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$VD_altdial_id =		$aryA[0];
											$VD_altdial_phone = 	$aryA[1];
											$VD_altdial_active = 	$aryA[2];
											$VD_phone_code = 		$aryA[3];
											}
										else
											{$Xlast=99999;}
											$event_string = "|$stmtA|$VD_altdial_phone|$Xlast|";   &event_logger;
										$sthA->finish();
										if ($ADB > 0) {$aad_string = "ALT-10: $CLlead_id|$CLalt_dial|$Xlast|$VD_altdial_phone|";   &aad_output;}

										$DNCC=0;
										$DNCL=0;
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
													{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_altdial_phone';";}
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$VD_alt_dnc_count =	$aryA[0];
													$DNCL =				$aryA[0];
													}
												$sthA->finish();
													$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
												}
											else {$VD_alt_dnc_count=0;}
											if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
												{
												$temp_campaign_id = $CLcampaign_id;
												if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
												if ($VD_use_campaign_dnc =~ /AREACODE/)
													{
													$ad_areacode = substr($VD_altdial_phone, 0, 3);
													$ad_areacode .= "XXXXXXX";
													$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_altdial_phone','$ad_areacode') and campaign_id='$temp_campaign_id';";
													}
												else
													{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_altdial_phone' and campaign_id='$temp_campaign_id';";}
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
													$DNCC =	$aryA[0];
													}
												$sthA->finish();
													$event_string = "|$stmtA|$VD_alt_dnc_count|";   &event_logger;
												}
											if ($VD_alt_dnc_count < 1)
												{
												if ($alt_dial_phones_count == $Xlast)
													{$Xlast = 'LAST';}
												$passed_24hour_call_count=1;
												if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
													{
													$temp_24hour_phone =		$VD_altdial_phone;
													$temp_24hour_phone_code =	$VD_phone_code;
													$temp_24hour_state =		$VD_state;
													$temp_24hour_postal_code =	$VD_postal_code;
													if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$CLlead_id|\n";}
													&check_24hour_call_count;
													}
												if ($passed_24hour_call_count > 0) 
													{
													$hopper_status='READY';
													if ($VD_hopper_hold_inserts =~ /ENABLED|AUTONEXT/) {$hopper_status='RHOLD';}
													$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='$hopper_status',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15',source='A';";
													$affected_rows = $dbhA->do($stmtA);
													$event_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;
													$aad_string = "$CLlead_id|$VD_altdial_phone|$CLcampaign_id|X$Xlast|15|hopper insert|";   &aad_output;
													$DNC_hopper_trigger=0;
													if ($ADB > 0) {$aad_string = "ALT-11: $CLlead_id|$CLalt_dial|X$Xlast|$VD_altdial_phone|";   &aad_output;}
												}
											else
													{$DNC_hopper_trigger=1;}
												}
											else
												{$DNC_hopper_trigger=1;}
											if ($DNC_hopper_trigger > 0)
												{
												if ( ( ($VD_auto_alt_dial_statuses =~ / DNCC /) && ($DNCC > 0) ) || ( ($VD_auto_alt_dial_statuses =~ / DNCL /) && ($DNCL > 0) ) || ( ($auto_alt_dial_statuses[$i] =~ / TFHCCL /) && ($TFHCCL > 0) ) )
													{
													if ($alt_dial_phones_count == $Xlast)
														{$Xlast = 'LAST';}
													$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='DNC',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15',source='A';";
													$affected_rows = $dbhA->do($stmtA);
													$event_string = "--    VDH record DNC inserted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;
													$aad_string = "$CLlead_id|$VD_altdial_phone|$CLcampaign_id|X$Xlast|15|hopper DNC insert|";   &aad_output;
													if ($ADB > 0) {$aad_string = "ALT-12: $CLlead_id|$CLalt_dial|X$Xlast|$VD_altdial_phone|";   &aad_output;}
													$Xlast=99999;
													}
												else
													{
													$event_string = "--    VDH record DNC not-inserted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &event_logger;
													$aad_string = "$CLlead_id|$VD_altdial_phone|$CLcampaign_id|X$Xlast|15|hopper DNC skip|";   &aad_output;
													if ($ADB > 0) {$aad_string = "ALT-13: $CLlead_id|$CLalt_dial|X$Xlast|$VD_altdial_phone|";   &aad_output;}
													}
												}
											}
										}
									}
								if ($ADB > 0) {$aad_string = "ALT-14: $CLlead_id|$CLalt_dial|END|";   &aad_output;}
								}
							##### END AUTO ALT PHONE DIAL SECTION #####
							}
						else
							{
							if ( ($KLcallerid[$kill_vac] =~ /^M\d\d\d\d\d\d\d\d\d\d/) && ($CLlast_update_time < $TDtarget) )
								{
								$stmtA = "DELETE from vicidial_auto_calls where auto_call_id='$auto_call_id'";
								$affected_rows = $dbhA->do($stmtA);

								$stmtA = "UPDATE vicidial_live_agents set ring_callerid='' where ring_callerid='$KLcallerid[$kill_vac]';";
								$affected_rowsX = $dbhA->do($stmtA);

								$event_string = "|   M dead call vac deleted|$auto_call_id|$CLlead_id|$KLcallerid[$kill_vac]|$end_epoch|$affected_rows|$KLchannel[$kill_vac]|$CLcall_type|$CLlast_update_time < $XDtarget|$affected_rowsX|";
								 &event_logger;
								$dead_call_ct++;
								}
							else
								{
								$event_string = "|     dead call vac XFERd do nothing|$CLlead_id|$CLphone_number|$CLstatus|";
								 &event_logger;
								$dead_call_ct++;
								}
							}
						}
					else
						{
						$event_string = "|     dead call vac INBOUND do nothing|$CLlead_id|$CLphone_number|$CLstatus|";
						 &event_logger;
						$dead_call_ct++;
						}
					}
				}
			$kill_vac++;
			}



		### pause agents that have disconnected or closed their apps over 30 seconds ago
		$toPAUSEcount=0;
		$stmtA = "SELECT count(*) FROM vicidial_live_agents where server_ip='$server_ip' and last_update_time < '$PDtsSQLdate' and status NOT IN('PAUSED');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsC=$sthA->rows;
		if ($sthArowsC > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$toPAUSEcount =		$aryA[0];
			}
		$sthA->finish();
		$affected_rows=0;

		if ($toPAUSEcount > 0)
			{
			$stmtA = "SELECT agent_log_id,user,server_ip,campaign_id,last_update_time from vicidial_live_agents where server_ip='$server_ip' and last_update_time < '$PDtsSQLdate' and status NOT IN('PAUSED');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsL=$sthA->rows;
			$lagged_ids=0;
			while ($sthArowsL > $lagged_ids)
				{
				@aryA = $sthA->fetchrow_array;
				$LAGagent_log_id[$lagged_ids] =	$aryA[0];
				$LAGuser[$lagged_ids] =			$aryA[1];
				$LAGserver_ip[$lagged_ids] =	$aryA[2];
				$LAGcampaign_id[$lagged_ids] =	$aryA[3];
				$LAGlast_update_time[$lagged_ids] =	$aryA[4];
				if ($DB > 0) {print "LAGGED DEBUG: $lagged_ids|$PDtsSQLdate|$aryA[4]|$aryA[3]|$aryA[2]|$aryA[1]|$aryA[0]|$stmtA|\n";}
				$lagged_ids++;
				}
			$sthA->finish();
			$lagged_ids=0;
			while ($sthArowsL > $lagged_ids)
				{
				$secX = time();
				$stmtA = "UPDATE vicidial_live_agents set pause_code='LAGGED' where user='$LAGuser[$lagged_ids]';";
				$VLaffected_rows = $dbhA->do($stmtA);

				$stmtA = "UPDATE vicidial_agent_log set sub_status='LAGGED',pause_type='SYSTEM' where agent_log_id='$LAGagent_log_id[$lagged_ids]';";
				$VLaffected_rows = $dbhA->do($stmtA);

				$stmtA = "SELECT user_group from vicidial_users where user='$LAGuser[$lagged_ids]' limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsLU=$sthA->rows;
				if ($sthArowsLU > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$LAGuser_group[$lagged_ids] =	$aryA[0];
					}
				$sthA->finish();

				$stmtA = "INSERT INTO vicidial_agent_log set event_time='$now_date',server_ip='$LAGserver_ip[$lagged_ids]',campaign_id='$LAGcampaign_id[$lagged_ids]',user_group='$LAGuser_group[$lagged_ids]',user='$LAGuser[$lagged_ids]',pause_epoch='$secX',pause_sec='0',wait_epoch='$secX',pause_type='SYSTEM';";
				$VLaffected_rows = $dbhA->do($stmtA);

				if ($enable_queuemetrics_logging > 0)
					{
					$pause_typeSQL='';
					if ($queuemetrics_pause_type > 0)
						{$pause_typeSQL=",data5='SYSTEM'";}
					$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
					 or die "Couldn't connect to database: " . DBI->errstr;

					if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

					$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='NONE',queue='NONE',agent='Agent/$LAGuser[$lagged_ids]',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LAGGED'$pause_typeSQL;";
					$Baffected_rows = $dbhB->do($stmtB);

					$dbhB->disconnect();
					}

				$lagged_ids++;
				}

			$stmtA = "UPDATE vicidial_live_agents set status='PAUSED',random_id='10' where server_ip='$server_ip' and last_update_time < '$PDtsSQLdate' and status NOT IN('PAUSED');";
			$affected_rows = $dbhA->do($stmtA);

			$event_string = "|     lagged call vla agent PAUSED $affected_rows|$VLaffected_rows|$PDtsSQLdate|$BDtsSQLdate|$tsSQLdate|";
			 &event_logger;

			if ($affected_rows > 0)
				{
				@VALOuser=@MT; @VALOcampaign=@MT; @VALOtimelog=@MT; @VALOextension=@MT;
				$logcount=0;
				$stmtA = "SELECT user,campaign_id,last_update_time,extension FROM vicidial_live_agents where server_ip='$server_ip' and status='PAUSED' and random_id='10' order by last_update_time desc limit $affected_rows";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				$rec_countCUSTDATA=0;
				while ($sthArows > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$VALOuser[$logcount] =		$aryA[0];
					$VALOcampaign[$logcount] =	$aryA[1];
					$VALOtimelog[$logcount]	=	$aryA[2];
					$VALOextension[$logcount] = $aryA[3];
					$logcount++;
					$rec_count++;
					}
				$sthA->finish();
				$logrun=0;
				foreach(@VALOuser)
					{
					$VALOuser_group='';
					$stmtA = "SELECT user_group FROM vicidial_users where user='$VALOuser[$logrun]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$UGrec_count=0;
					while ($sthArows > $UGrec_count)
						{
						@aryA = $sthA->fetchrow_array;
						$VALOuser_group =		$aryA[0];
						$UGrec_count++;
						}
					$sthA->finish();
					$stmtA = "INSERT INTO vicidial_user_log (user,event,campaign_id,event_date,event_epoch,user_group) values('$VALOuser[$logrun]','LOGOUT','$VALOcampaign[$logrun]','$SQLdate','$now_date_epoch','$VALOuser_group');";
					$affected_rows = $dbhA->do($stmtA);

					if ($enable_queuemetrics_logging > 0)
						{
						$QM_LOGOFF = 'AGENTLOGOFF';
						if ($queuemetrics_loginout =~ /CALLBACK/)
							{$QM_LOGOFF = 'AGENTCALLBACKLOGOFF';}

						$secX = time();
						$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
						 or die "Couldn't connect to database: " . DBI->errstr;

						if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

						$agents='@agents';
						$time_logged_in='';
						$data4='';
						$RAWtime_logged_in=$TDtarget;
						if ($queuemetrics_loginout !~ /NONE/)
							{
							$stmtB = "SELECT time_id,data1,data4 FROM queue_log where agent='Agent/$VALOuser[$logrun]' and verb IN('AGENTLOGIN','AGENTCALLBACKLOGIN') and time_id > $check_time order by time_id desc limit 1;";
							$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
							$sthB->execute or die "executing: $stmtA ", $dbhB->errstr;
							$sthBrows=$sthB->rows;
							if ($sthBrows > 0)
								{
								@aryB = $sthB->fetchrow_array;
								$time_logged_in =		$aryB[0];
								$RAWtime_logged_in =	$aryB[0];
								$phone_logged_in =		$aryB[1];
								$data4 =				$aryB[2];
								}
							$sthB->finish();

							$time_logged_in = ($secX - $logintime);
							if ($time_logged_in > 1000000) {$time_logged_in=1;}
							$LOGOFFtime = ($secX + 1);

							$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$LOGOFFtime',call_id='NONE',queue='NONE',agent='Agent/$VALOuser[$logrun]',verb='$QM_LOGOFF',serverid='$queuemetrics_log_id',data1='$phone_logged_in',data2='$time_logged_in',data4='$data4';";
							$Baffected_rows = $dbhB->do($stmtB);
							}

						if ($queuemetrics_addmember_enabled > 0)
							{
							if ( (length($time_logged_in) < 1) || ($queuemetrics_loginout =~ /NONE/) )
								{
								$stmtB = "SELECT time_id,data3,data4 FROM queue_log where agent='Agent/$VALOuser[$logrun]' and verb='PAUSEREASON' and data1='LOGIN' order by time_id desc limit 1;";
								$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
								$sthB->execute or die "executing: $stmtA ", $dbhB->errstr;
								$sthBrows=$sthB->rows;
								if ($sthBrows > 0)
									{
									@aryB = $sthB->fetchrow_array;
									$time_logged_in =		$aryB[0];
									$RAWtime_logged_in =	$aryB[0];
									$phone_logged_in =		$aryB[1];
									$data4 =				$aryB[2];
									}
								$sthB->finish();

								$time_logged_in = ($secX - $logintime);
								if ($time_logged_in > 1000000) {$time_logged_in=1;}
								$LOGOFFtime = ($secX + 1);
								}
							if ($queuemetrics_loginout =~ /NONE/)
								{
								$pause_typeSQL='';
								if ($queuemetrics_pause_type > 0)
									{$pause_typeSQL=",data5='SYSTEM'";}
								$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$LOGOFFtime',call_id='NONE',queue='NONE',agent='Agent/$VALOuser[$logrun]',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LOGOFF'$pause_typeSQL;";
								$Baffected_rows = $dbhB->do($stmtB);
								}
							$stmtB = "SELECT distinct queue FROM queue_log where time_id >= $RAWtime_logged_in and agent='Agent/$VALOuser[$logrun]' and verb IN('ADDMEMBER','ADDMEMBER2') and queue != '$VALOcampaign[$logrun]' order by time_id desc;";
							$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
							$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
							$sthBrows=$sthB->rows;
							$rec_count=0;
							while ($sthBrows > $rec_count)
								{
								@aryB = $sthB->fetchrow_array;
								$AM_queue[$rec_count] =		$aryB[0];
								$rec_count++;
								}
							$sthB->finish();

							$AM_queue[$rec_count] =	$VALOcampaign[$logrun];
							$rec_count++;
							$sthBrows++;

							$rec_count=0;
							while ($sthBrows > $rec_count)
								{
								$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$LOGOFFtime',call_id='NONE',queue='$AM_queue[$rec_count]',agent='Agent/$VALOuser[$logrun]',verb='REMOVEMEMBER',data1='$phone_logged_in',serverid='$queuemetrics_log_id',data4='$data4';";
								$Baffected_rows = $dbhB->do($stmtB);
								$rec_count++;
								}
							}

						$dbhB->disconnect();
						}

					$event_string = "|          lagged agent LOGOUT entry inserted $VALOuser[$logrun]|$VALOcampaign[$logrun]|$VALOextension[$logcount]|";
					 &event_logger;

					$logrun++;
					}
				}
			}


		### display and delete call records that are SENT for over 2 minutes
		$stmtA = "SELECT status,lead_id,last_update_time FROM vicidial_auto_calls where server_ip='$server_ip' and call_time < '$XDSQLdate' and status NOT IN('XFER','CLOSER','LIVE','IVR');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$dead_delete_string='';
		$dead_delete_ct=0;
		while ($sthArows > $dead_delete_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$dead_delete_string .= "$dead_delete_ct-$aryA[0]-$aryA[1]-$aryA[2]|";
			$dead_delete_ct++;
			}
		$sthA->finish();

		if ($dead_delete_ct > 0)
			{
			$stmtA = "DELETE FROM vicidial_auto_calls where server_ip='$server_ip' and call_time < '$XDSQLdate' and status NOT IN('XFER','CLOSER','LIVE','IVR');";
			$VACaffected_rows = $dbhA->do($stmtA);

			if ($VACaffected_rows > 0)
				{
				$event_string = "|     lagged call vac 2-minutes DELETED $VACaffected_rows|$XDSQLdate|$dead_delete_string";
				&event_logger;
				}
			}


		### Delete old shared campaign drop records
		$stmtA = "DELETE FROM vicidial_shared_drops where server_ip='$server_ip' and drop_time < \"$BDtsSQLdate\";";
		$SDaffected_rows = $dbhA->do($stmtA);
		if ($SDaffected_rows > 0)
			{
			$event_string = "|     old shared drop records DELETED $SDaffected_rows|$BDtsSQLdate|";
			&event_logger;
			}


		### For debugging purposes, try to grab Jammed calls and log them to jam logfile
		if ($useJAMdebugFILE)
			{
			$stmtA = "SELECT auto_call_id,server_ip,campaign_id,status,lead_id,uniqueid,callerid,channel,phone_code,phone_number,call_time,call_type,stage,last_update_time,alt_dial,queue_priority,agent_only,agent_grab FROM vicidial_auto_calls where server_ip='$server_ip' and last_update_time < '$BDtsSQLdate' and status IN('LIVE');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$JAMrec_count=0;
			while ($sthArows > $JAMrec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$jam_string = "$JAMrec_count|$BDtsSQLdate|     |$aryA[0]|$aryA[1]|$aryA[2]|$aryA[3]|$aryA[4]|$aryA[5]|$aryA[6]|$aryA[7]|$aryA[8]|$aryA[9]|$aryA[10]|$aryA[11]|$aryA[12]|$aryA[13]|$aryA[14]|";
				 &jam_event_logger;
				$JAMrec_count++;
				}
			$sthA->finish();
			}

		### find call records that are LIVE and not updated for over 10 seconds
		$stmtA = "SELECT auto_call_id,lead_id,phone_number,status,campaign_id,phone_code,alt_dial,stage,callerid,uniqueid from vicidial_auto_calls where server_ip='$server_ip' and last_update_time < '$BDtsSQLdate' and status IN('LIVE','IVR');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;
			$auto_call_id =		$aryA[0];
			$CLlead_id =		$aryA[1];
			$CLphone_number =	$aryA[2];
			$CLstatus =			$aryA[3];
			$CLcampaign_id =	$aryA[4];
			$CLphone_code =		$aryA[5];
			$CLalt_dial =		$aryA[6];
			$CLstage =			$aryA[7];
			$CLcallerid	=		$aryA[8];
			$CLuniqueid	=		$aryA[9];
			$rec_count++;
			}
		$sthA->finish();

		### delete call records that are LIVE and not updated for over 10 seconds
		$rec_count=0;
		while ($sthArows > $rec_count)
			{
			$stmtA = "DELETE from vicidial_auto_calls where auto_call_id='$auto_call_id';";
			$affected_rows = $dbhA->do($stmtA);

			$stmtA = "UPDATE vicidial_live_agents set ring_callerid='' where ring_callerid='$CLcallerid';";
			$affected_rowsX = $dbhA->do($stmtA);

			$event_string = "|     lagged call vdac call DELETED $affected_rows|$affected_rowsX|$BDtsSQLdate|$auto_call_id|$CLcallerid|$CLuniqueid|$CLphone_number|$CLstatus|";
			 &event_logger;

			if ( ($affected_rows > 0) && ($CLlead_id > 0) )
				{
				$xCLlist_id=0;
				$called_count=0;
				$stmtA="SELECT list_id,called_count from vicidial_list where lead_id='$CLlead_id' limit 1;";
					if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsVLL=$sthA->rows;
				if ($sthArowsVLL > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$xCLlist_id = 	$aryA[0];
					$called_count = $aryA[1];
					}
				$sthA->finish();

				$jam_string = "|     lagged call vdac call DELETED $affected_rows|$BDtsSQLdate|$auto_call_id|$CLcallerid|$CLuniqueid|$CLphone_number|$CLstatus|$xCLlist_id|";
				 &jam_event_logger;

				$CLstage =~ s/LIVE|-//gi;
				if ($CLstage < 0.25) {$CLstage=0;}

				$end_epoch = $now_date_epoch;
				$stmtA = "INSERT INTO vicidial_log (uniqueid,lead_id,campaign_id,call_date,start_epoch,status,phone_code,phone_number,user,processed,length_in_sec,end_epoch,alt_dial,list_id,called_count) values('$CLuniqueid','$CLlead_id','$CLcampaign_id','$SQLdate','$now_date_epoch','DROP','$CLphone_code','$CLphone_number','VDAD','N','$CLstage','$end_epoch','$CLalt_dial','$xCLlist_id','$called_count')";
					if($M){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA);

				$stmtB = "INSERT INTO vicidial_log_extended set uniqueid='$CLuniqueid',server_ip='$server_ip',call_date='$SQLdate',lead_id = '$CLlead_id',caller_code='$CLcallerid',custom_call_id='';";
				$affected_rowsB = $dbhA->do($stmtB);

				if ($enable_drop_lists > 1)
					{
					$stmtC="INSERT IGNORE INTO vicidial_drop_log SET uniqueid='$CLuniqueid',server_ip='$server_ip',drop_date=NOW(),lead_id='$CLlead_id',campaign_id='$CLcampaign_id',status='DROP',phone_code='$CLphone_code',phone_number='$CLphone_number';";
					$affected_rowsC = $dbhA->do($stmtC);
					$event_string = "--    DROP vicidial_drop_log insert: |$affected_rowsC|$CLuniqueid|$CLlead_id|";
					 &event_logger;
					}

				$event_string = "|     dead NA call added to logs $CLuniqueid|$CLlead_id|$CLphone_number|$CLstatus|DROP|$affected_rows|$affected_rowsB|";
				 &event_logger;

				if ($enable_queuemetrics_logging > 0)
					{
					$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
					 or die "Couldn't connect to database: " . DBI->errstr;

					if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

					$secX = time();
					$Rtarget = ($secX - 21600);	# look for VDCL entry within last 6 hours
					($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($Rtarget);
					$Ryear = ($Ryear + 1900);
					$Rmon++;
					if ($Rmon < 10) {$Rmon = "0$Rmon";}
					if ($Rmday < 10) {$Rmday = "0$Rmday";}
					if ($Rhour < 10) {$Rhour = "0$Rhour";}
					if ($Rmin < 10) {$Rmin = "0$Rmin";}
					if ($Rsec < 10) {$Rsec = "0$Rsec";}
						$RSQLdate = "$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

					### find original queue position of the call
					$queue_position=1;
					$stmtA = "SELECT queue_position,call_date FROM vicidial_closer_log where lead_id='$CLlead_id' and campaign_id='$CLcampaign_id' and call_date > \"$RSQLdate\" order by closecallid desc limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$queue_position =	$aryA[0];
						$VCLcall_date =		$aryA[1];
						}
					$sthA->finish();

					### find current number of calls in this queue to find position when channel hung up
					$current_position=1;
					$stmtA = "SELECT count(*) FROM vicidial_auto_calls where status = 'LIVE' and campaign_id='$CLcampaign_id' and call_time < '$VCLcall_date';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$current_position =	($aryA[0] + 1);
						}
					$sthA->finish();

					$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$CLcallerid',queue='$CLcampaign_id',agent='NONE',verb='ABANDON',data1='$current_position',data2='$queue_position',data3='$CLstage',serverid='$queuemetrics_log_id';";
					$Baffected_rows = $dbhB->do($stmtB);

					$dbhB->disconnect();
					}

				}

			$rec_count++;
			}
		$sthA->finish();


		if ($LUcount > 0)
			{
			chop($lists_update);
			$stmtA = "UPDATE vicidial_lists SET list_lastcalldate='$SQLdate' where list_id IN($lists_update);";
			$affected_rows = $dbhA->do($stmtA);
			$event_string = "|     lastcalldate UPDATED $affected_rows|$lists_update|";
			 &event_logger;
			}

		if ($CPcount > 0)
			{
			chop($campaigns_update);
			$stmtA = "UPDATE vicidial_campaigns SET campaign_logindate='$SQLdate' where campaign_id IN($campaigns_update);";
			$affected_rows = $dbhA->do($stmtA);
			$event_string = "|     logindate UPDATED $affected_rows|$campaigns_update|";
			 &event_logger;
			}


	&get_time_now;

	if ( ($active_voicemail_server =~ /$server_ip/) && ((length($active_voicemail_server)) eq (length($server_ip))) )
		{
		if ($DB) {print "Active voicemail server match! Starting MULTI_LEAD,no-agent-url,no_answer,recent_ascb_calls processing for all servers...\n";}

	###############################################################################
	###### fourth we will check to see if any campaign is running MULTI_LEAD
	######    auto-alt-dial. if yes, then go through the unprocessed extended
	######    log entries for all server_ips and process them.
	############################################################################### server_ip='$server_ip' and 

		$MLincall='|INCALL|QUEUE|DISPO|';
		$multi_alt_count=0;
		$stmtA = "SELECT count(*) FROM vicidial_campaigns where auto_alt_dial='MULTI_LEAD' and dial_method NOT IN('MANUAL','INBOUND_MAN') and campaign_calldate > \"$MCDSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$multi_alt_count	= $aryA[0];
			}
		$sthA->finish();

		if ($multi_alt_count > 0)
			{
			@MLcamp_id = @MT;
			@MLaad_statuses = @MT;
			@MLlists = @MT;

			$MLcampaigns='|';
			$stmtA = "SELECT campaign_id,auto_alt_dial_statuses FROM vicidial_campaigns where auto_alt_dial='MULTI_LEAD' and dial_method NOT IN('MANUAL','INBOUND_MAN') and campaign_calldate > \"$MCDSQLdate\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$MLcamp_count=0;
			while ($sthArows > $MLcamp_count)
				{
				@aryA = $sthA->fetchrow_array;
				$MLcampaigns	.= "$aryA[0]|";
				$MLcamp_id[$MLcamp_count]		= $aryA[0];
				$MLaad_statuses[$MLcamp_count]	= $aryA[1];
				$MLcamp_count++;
				}
			$sthA->finish();

			$MLcamp_count=0;
			foreach(@MLcamp_id)
				{
				$MLlists[$MLcamp_count]='';
				$stmtA = "SELECT list_id FROM vicidial_lists where campaign_id='$MLcamp_id[$MLcamp_count]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$MLlist_count=0;
				while ($sthArows > $MLlist_count)
					{
					@aryA = $sthA->fetchrow_array;
					$MLlists[$MLcamp_count]		.= "'$aryA[0]',";
					$MLlist_count++;
					}
				$sthA->finish();
				$MLlists[$MLcamp_count] =~ s/,$//gi;
				if (length($MLlists[$MLcamp_count]) < 2)
					{$MLlists[$MLcamp_count]="''";}
				$MLcamp_count++;
				}

			$event_string = "     MULTI_LEAD auto-alt-dial check:   $multi_alt_count active($MCDSQLdate), checking unprocessed calls...";
			 &event_logger;

			@MLuniqueid = @MT;
			@MLleadid = @MT;
			@MLcalldate = @MT;
			@MLcallerid = @MT;
			@MLflag = @MT;
			@MLcampaign = @MT;
			@MLstatus = @MT;

			$stmtA = "SELECT uniqueid,lead_id,call_date,caller_code FROM vicidial_log_extended where call_date > \"$MCDSQLdate\" and multi_alt_processed='N' order by call_date,lead_id limit 100000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$vle_count=0;
			$vle_auto_count=0;
			while ($sthArows > $vle_count)
				{
				$MLflag[$vle_count]	= 0;
				@aryA = $sthA->fetchrow_array;
				if ($aryA[3] =~ /^V/)
					{
					$vle_auto_count++;
					$MLflag[$vle_count]	= 1;
					}
				$MLuniqueid[$vle_count]	= $aryA[0];
				$MLleadid[$vle_count]	= $aryA[1];
				$MLcalldate[$vle_count]	= $aryA[2];
				$MLcallerid[$vle_count]	= $aryA[3];
				$vle_count++;
				}
			$sthA->finish();

			$event_string = "     MULTI_LEAD auto-alt-dial vle records count:   $vle_count|$vle_auto_count";
			 &event_logger;

			$vle_count=0;
			foreach(@MLuniqueid)
				{
				if ($MLflag[$vle_count] > 0)
					{
					$vac_count=0;
					$stmtA = "SELECT count(*) FROM vicidial_auto_calls where callerid='$MLcallerid[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$vac_count	= $aryA[0];
						}
					$sthA->finish();

					if ($vac_count < 1)
						{
						$stmtA = "SELECT campaign_id,status FROM vicidial_log where uniqueid='$MLuniqueid[$vle_count]' and lead_id='$MLleadid[$vle_count]' and call_date > \"$MCDSQLdate\";";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$MLcampaign[$vle_count]	= $aryA[0];
							$MLstatus[$vle_count]	= $aryA[1];
							$sthA->finish();

							if ($MLcampaigns =~ /\|$MLcampaign[$vle_count]\|/i)
								{
								if ($MLincall !~ /\|$MLstatus[$vle_count]\|/i)
									{
									$MLaads_value='';
									$MLlists_value="''";
									$MLcamp_match=0;
									$MLcamp_loop=0;
									while ( ($MLcamp_match < 1) && ($MLcamp_loop <= $MLcamp_count) )
										{
										if ( ($MLcampaign[$vle_count] =~ /$MLcamp_id[$MLcamp_loop]/i) && (length($MLcampaign[$vle_count]) == length($MLcamp_id[$MLcamp_loop])) )
											{
											$MLcamp_match++;
											$MLaads_value = $MLaad_statuses[$MLcamp_loop];
											$MLlists_value = $MLlists[$MLcamp_loop];
											}
										$MLcamp_loop++;
										}
									if ($MLaads_value !~ / $MLstatus[$vle_count] /i)
										{
										$event_string = "        ML status non-match, looking for matching accounts:   $MLcallerid[$vle_count]|$MLstatus[$vle_count]";
										 &event_logger;

										$MLnonmatch_output='';
										$MLnonmatch_leadids='';
										$MLvendor_lead_code='';
										$stmtA = "SELECT vendor_lead_code FROM vicidial_list where lead_id='$MLleadid[$vle_count]';";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$MLvlc[$vle_count]	= $aryA[0];
											}
										$sthA->finish();

										if (length($MLvlc[$vle_count]) > 1)
											{
											$stmtA = "SELECT lead_id,status FROM vicidial_list where vendor_lead_code='$MLvlc[$vle_count]' and list_id IN($MLlists_value) and lead_id!='$MLleadid[$vle_count]';";
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											$MLnm_count=0;
											while ($sthArows > $MLnm_count)
												{
												@aryA = $sthA->fetchrow_array;
												$MLnonmatch_output	.= "$aryA[0]-$aryA[1]|";
												$MLnonmatch_leadids	.= "'$aryA[0]',";
												$MLnm_count++;
												}
											$sthA->finish();

											if ($MLnm_count > 0)
												{
												chop($MLnonmatch_leadids);
												if (length($MLnonmatch_leadids)<2)
													{$MLnonmatch_leadids="''";}

												$event_string = "        ML status non-match, $MLnm_count matching accounts found:   $MLcallerid[$vle_count]|$MLvlc[$vle_count]\n          $MLnonmatch_output";
												 &event_logger;

												$stmtA = "UPDATE vicidial_list SET status='MLINAT' where lead_id IN($MLnonmatch_leadids);";
												$affected_rows = $dbhA->do($stmtA);

												$stmtB = "DELETE FROM vicidial_hopper where lead_id IN($MLnonmatch_leadids);";
												$affected_rowsH = $dbhA->do($stmtB);

												$event_string = "        ML status non-match accounts inactivated:   $MLnonmatch_leadids|$affected_rows|$affected_rowsH|MLINAT";
												 &event_logger;
												}

											### set multi-lead status non-match record to Y for processed
											$stmtA = "UPDATE vicidial_log_extended SET multi_alt_processed='Y' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
											$affected_rows = $dbhA->do($stmtA);

											$event_string = "        ML status non-match, log processed:   $MLcallerid[$vle_count]|$affected_rows";
											 &event_logger;
											}
										else
											{
											### set multi-lead status non-match, no vendor_lead_code record to Y for processed
											$stmtA = "UPDATE vicidial_log_extended SET multi_alt_processed='Y' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
											$affected_rows = $dbhA->do($stmtA);

											$event_string = "        ML status non-match, no vlc, log processed:   $MLcallerid[$vle_count]|$MLvlc[$vle_count]|$affected_rows";
											 &event_logger;
											}
										}
									else
										{
										### set multi-lead status match record to Y for processed
										$stmtA = "UPDATE vicidial_log_extended SET multi_alt_processed='Y' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
										$affected_rows = $dbhA->do($stmtA);

										$event_string = "        ML match, log processed:   $MLcallerid[$vle_count]|$affected_rows";
										 &event_logger;
										}
									}
								else
									{
									$event_string = "        ML status in-call, do nothing:   $MLcallerid[$vle_count]|$MLuniqueid[$vle_count]|$MLstatus[$vle_count]";
									 &event_logger;
									}
								}
							else
								{
								### set non-multi-lead campaign record to U for unqualified for MULTI_LEAD processing
								$stmtA = "UPDATE vicidial_log_extended SET multi_alt_processed='U' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
								$affected_rows = $dbhA->do($stmtA);
								}
							}
						else
							{
							$sthA->finish();
							$event_string = "        ML no log entry, do nothing:   $MLcallerid[$vle_count]|$MLuniqueid[$vle_count]";
							 &event_logger;
							}
						}
					else
						{
						$event_string = "        ML active call, do nothing:   $MLcallerid[$vle_count]";
						 &event_logger;
						}
					}
				else
					{
					### set non-auto-dial record to U for unqualified for MULTI_LEAD processing
					$stmtA = "UPDATE vicidial_log_extended SET multi_alt_processed='U' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
					$affected_rows = $dbhA->do($stmtA);
					}
				$vle_count++;
				}
			}


	###############################################################################
	###### fifth we will check to see if any campaign or in-group has na_call_url
	######    populated. if yes, then go through the unprocessed extended log
	######    entries for all server_ips and process them.
	############################################################################### server_ip='$server_ip' and 
		$NCUincall='|INCALL|QUEUE|DISPO|';
		$ncu_count=0;
		$ncu_in_count=0;
		$stmtA = "SELECT count(*) FROM vicidial_campaigns where na_call_url IS NOT NULL and na_call_url!='' and campaign_calldate > \"$RMSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ncu_count	= $aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) FROM vicidial_inbound_groups where na_call_url IS NOT NULL and na_call_url!='' and group_calldate > \"$RMSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ncu_in_count	= $aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) FROM vicidial_lists where na_call_url IS NOT NULL and na_call_url!='' and list_lastcalldate > \"$RMSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$ncu_list_count	= $aryA[0];
			}
		$sthA->finish();

		if ( ($ncu_count > 0) || ($ncu_in_count > 0) || ($ncu_list_count > 0) )
			{
			@NCUcamp_id = @MT;
			@NCUncurl = @MT;
			$ncu_total_count=0;
			$NCUcampaigns='|';

			if ($ncu_count > 0)
				{
				$stmtA = "SELECT campaign_id,na_call_url FROM vicidial_campaigns where na_call_url IS NOT NULL and na_call_url!='' and campaign_calldate > \"$RMSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$NCUcamp_count=0;
				while ($sthArows > $NCUcamp_count)
					{
					@aryA = $sthA->fetchrow_array;
					$NCUcampaigns	.= "$aryA[0]|";
					$NCUcamp_id[$ncu_total_count] = $aryA[0];
					$NCUncurl[$ncu_total_count]	=	$aryA[1];
					$NCUcamp_count++;
					$ncu_total_count++;
					}
				$sthA->finish();
				}
			if ($ncu_in_count > 0)
				{
				$stmtA = "SELECT group_id,na_call_url FROM vicidial_inbound_groups where na_call_url IS NOT NULL and na_call_url!='' and group_calldate > \"$RMSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$NCUig_count=0;
				while ($sthArows > $NCUig_count)
					{
					@aryA = $sthA->fetchrow_array;
					$NCUcampaigns	.= "$aryA[0]|";
					$NCUcamp_id[$ncu_total_count] = $aryA[0];
					$NCUncurl[$ncu_total_count]	=	$aryA[1];
					$NCUig_count++;
					$ncu_total_count++;
					}
				$sthA->finish();
				}
			if ($ncu_list_count > 0)
				{
				$stmtA = "SELECT list_id,na_call_url FROM vicidial_lists where na_call_url IS NOT NULL and na_call_url!='' and list_lastcalldate > \"$RMSQLdate\";";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$NCUlist_count=0;
				while ($sthArows > $NCUlist_count)
					{
					@aryA = $sthA->fetchrow_array;
					$NCUlists	.= "$aryA[0]|";
					$NCUlist_id[$ncu_total_count] = $aryA[0];
					$NCUncurl[$ncu_total_count]	=	$aryA[1];
					$NCUlist_count++;
					$ncu_total_count++;
					}
				$sthA->finish();
				}
			$event_string = "     NA-CALL-URL check:   $ncu_total_count active, checking unprocessed calls...";
			 &event_logger;

			@NCUuniqueid = @MT;
			@NCUleadid = @MT;
			@NCUcalldate = @MT;
			@NCUcallerid = @MT;
			@NCUcampaign = @MT;
			@NCUstatus = @MT;
			@NCUuser = @MT;
			@NCUphone = @MT;
			@NCUaltdial = @MT;
			@NCUcalltype = @MT;

			$stmtA = "SELECT uniqueid,lead_id,call_date,caller_code FROM vicidial_log_extended where call_date > \"$RMSQLdate\" and dispo_url_processed='N' order by call_date,lead_id limit 100000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$vle_count=0;
			$vle_auto_count=0;
			while ($sthArows > $vle_count)
				{
				@aryA = $sthA->fetchrow_array;
				$NCUuniqueid[$vle_count] =	$aryA[0];
				$NCUleadid[$vle_count] =	$aryA[1];
				$NCUcalldate[$vle_count] =	$aryA[2];
				$NCUcallerid[$vle_count] =	$aryA[3];
				$vle_count++;
				}
			$sthA->finish();

			$event_string = "     NA-CALL-URL vle records count:   $vle_count|$vle_auto_count";
			 &event_logger;

			$vle_count=0;
			if (length($NCUuniqueid[0]) > 5)
				{
				foreach(@NCUuniqueid)
					{
					if ( (length($NCUcallerid[$vle_count]) > 10) && ($NCUcallerid[$vle_count] !~ /^M/) && ($NCUcallerid[$vle_count] =~ /^V|^Y/) )
						{
						$vac_count=0;
						$stmtA = "SELECT count(*) FROM vicidial_auto_calls where callerid='$NCUcallerid[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$vac_count	= $aryA[0];
							}
						$sthA->finish();

						if ($vac_count < 1)
							{
							if ($NCUcallerid[$vle_count] =~ /^Y/)
								{
								$stmtA = "SELECT campaign_id,status,user,phone_number,'MAIN',list_id FROM vicidial_closer_log where uniqueid='$NCUuniqueid[$vle_count]' and lead_id='$NCUleadid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' order by closecallid desc;";
								$NCUcalltype[$vle_count] = 'IN';
								}
							else
								{
								$stmtA = "SELECT campaign_id,status,user,phone_number,alt_dial,list_id FROM vicidial_log where uniqueid='$NCUuniqueid[$vle_count]' and lead_id='$NCUleadid[$vle_count]' and call_date > \"$RMSQLdate\";";
								$NCUcalltype[$vle_count] = 'OUT';
								}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$NCUcampaign[$vle_count] =	$aryA[0];
								$NCUstatus[$vle_count] =	$aryA[1];
								$NCUuser[$vle_count] =		$aryA[2];
								$NCUphone[$vle_count] =		$aryA[3];
								$NCUaltdial[$vle_count] =	$aryA[4];
								$NCUlist[$vle_count] =		$aryA[5];
								$sthA->finish();

								if ($NCUcampaigns =~ /\|$NCUcampaign[$vle_count]\|/i)
									{
									if ($NCUincall !~ /\|$NCUstatus[$vle_count]\|/i)
										{
										if ( ($NCUuser[$vle_count] =~ /VDAD|VDCL/i) || ( ($NCUcalltype[$vle_count] =~ /IN/) && ($NCUcampaign[$vle_count] =~ /AGENTDIRECT/i) && ($NCUstatus[$vle_count] =~ /ACFLTR|AFTHRS|CLOSOP|^DROP$|HOLDTO|^HXFER$|IQNANQ|MAXCAL|NANQUE|QVMAIL|TIMEOT|WAITTO/) ) )
											{
											$NCUncurl_value='';
											$NCUcamp_match=0;
											$NCUcamp_loop=0;
											while ( ($NCUcamp_match < 1) && ($NCUcamp_loop <= $ncu_total_count) )
												{
												if ( ($NCUcampaign[$vle_count] =~ /$NCUcamp_id[$NCUcamp_loop]/i) && (length($NCUcampaign[$vle_count]) == length($NCUcamp_id[$NCUcamp_loop])) )
													{
													$NCUcamp_match++;
													$NCUncurl_value = $NCUncurl[$NCUcamp_loop];
													}
												$NCUcamp_loop++;
												}
											if ( (length($NCUncurl_value) > 10) || ($NCUncurl_value =~ /^ALT$/) )
												{
												$event_string = "        NCU url defined, launching web GET:   $NCUcallerid[$vle_count]|$NCUstatus[$vle_count]";
												 &event_logger;

												### set dispo_url_processed match record to Y for processed and sent
												$stmtA = "UPDATE vicidial_log_extended SET dispo_url_processed='XY' where uniqueid='$NCUuniqueid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
												$affected_rows = $dbhA->do($stmtA);

												$launch = $PATHhome . "/AST_send_URL.pl";
												$launch .= " --SYSLOG" if ($SYSLOG);
												$launch .= " --lead_id=" . $NCUleadid[$vle_count];
												$launch .= " --phone_number=" . $NCUphone[$vle_count];
												$launch .= " --user=" . $NCUuser[$vle_count];
												$launch .= " --call_type=" . $NCUcalltype[$vle_count];
												$launch .= " --campaign=" . $NCUcampaign[$vle_count];
												$launch .= " --uniqueid=" . $NCUuniqueid[$vle_count];
												$launch .= " --alt_dial=" . $NCUaltdial[$vle_count];
												$launch .= " --call_id=" . $NCUcallerid[$vle_count];
												$launch .= " --list_id=" . $NCUlist[$vle_count];
												$launch .= " --status=" . $NCUstatus[$vle_count];
												$launch .= " --function=NA_CALL_URL";

												system($launch . ' &');

												if ($DBX > 0) {print "     NCU debugX: |$NCUlist[$vle_count]|$NCUcampaign[$vle_count]|$launch|$NCUncurl_value|\n";}

												$event_string = "        NCU url sent processed:   $NCUcallerid[$vle_count]|$NCUcampaign[$vle_count]|$NCUuser[$vle_count]";
												 &event_logger;
												}
											else
												{
												### set dispo_url_processed match record to Y for processed but not sent, invalid url
												$stmtA = "UPDATE vicidial_log_extended SET dispo_url_processed='XY' where uniqueid='$NCUuniqueid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
												$affected_rows = $dbhA->do($stmtA);

												$event_string = "        NCU invalid url defined, log processed:   $NCUcallerid[$vle_count]|$affected_rows|$NCUcampaign[$vle_count]";
												 &event_logger;
												}
											}
										else
											{
											### set dispo_url_processed record to U for unqualified processing because call sent to agent
											$stmtA = "UPDATE vicidial_log_extended SET dispo_url_processed='XY' where uniqueid='$NCUuniqueid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
											$affected_rows = $dbhA->do($stmtA);

											$event_string = "        NCU call sent to agent, log processed:   $NCUcallerid[$vle_count]|$affected_rows|$NCUuser[$vle_count]";
											 &event_logger;
											}
										}
									else
										{
										$event_string = "        NCU status in-call, do nothing:   $NCUcallerid[$vle_count]|$NCUuniqueid[$vle_count]|$NCUstatus[$vle_count]";
										 &event_logger;
										}
									}
								else
									{
									### set dispo_url_processed record to XU for unqualified processing because no url defined
									$stmtA = "UPDATE vicidial_log_extended SET dispo_url_processed='XU' where uniqueid='$NCUuniqueid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
									$affected_rows = $dbhA->do($stmtA);
									}
								}
							else
								{
								$sthA->finish();
								$event_string = "        NCU no log entry, do nothing:   $NCUcallerid[$vle_count]|$NCUuniqueid[$vle_count]";
								 &event_logger;
								}
							}
						else
							{
							$event_string = "        NCU active call, do nothing:   $NCUcallerid[$vle_count]";
							 &event_logger;
							}
						}
					else
						{
						### set log extended record to XU for invalid callid or manual dial call
						$stmtA = "UPDATE vicidial_log_extended SET dispo_url_processed='XU' where uniqueid='$NCUuniqueid[$vle_count]' and call_date='$NCUcalldate[$vle_count]' and lead_id='$NCUleadid[$vle_count]';";
						$affected_rows = $dbhA->do($stmtA);

						$event_string = "        NCU invalid call, mark as XU:   $NCUcallerid[$vle_count]|$NCUuniqueid[$vle_count]";
						 &event_logger;
						}
					$vle_count++;
					}
				}
			}


	###############################################################################
	###### sixth, if noanswer_log is enabled in the system settings then look for
	######    unprocessed entries for all server_ips and process them.
	############################################################################### server_ip='$server_ip' and 
		$MLincall='|INCALL|QUEUE|DISPO|';
		$MLnoanswer='|NA|B|AB|DC|ADC|CPDATB|CPDB|CPDNA|CPDREJ|CPDINV|CPDSUA|CPDSI|CPDSNC|CPDSR|CPDSUK|CPDSV|CPDUK|CPDERR|';

		if ($noanswer_log =~ /Y/)
			{
			$event_string = "     NO-ANSWER log check:   $noanswer_log, active, checking unprocessed calls...";
			 &event_logger;

			@MLuniqueid = @MT;
			@MLleadid = @MT;
			@MLcalldate = @MT;
			@MLcallerid = @MT;
			@MLflag = @MT;
			@MLcampaign = @MT;
			@MLstatus = @MT;

			$stmtA = "SELECT uniqueid,lead_id,call_date,caller_code FROM vicidial_log_extended where call_date > \"$RMSQLdate\" and noanswer_processed='N' order by call_date,lead_id limit 100000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$vle_count=0;
			$vle_auto_count=0;
			while ($sthArows > $vle_count)
				{
				$MLflag[$vle_count]	= 0;
				@aryA = $sthA->fetchrow_array;
				if ($aryA[3] =~ /^V/)
					{
					$vle_auto_count++;
					$MLflag[$vle_count]	= 1;
					}
				$MLuniqueid[$vle_count]	= $aryA[0];
				$MLleadid[$vle_count]	= $aryA[1];
				$MLcalldate[$vle_count]	= $aryA[2];
				$MLcallerid[$vle_count]	= $aryA[3];
				$vle_count++;
				}
			$sthA->finish();

			$event_string = "     NO-ANSWER log vle records count:   $vle_count|$vle_auto_count";
			 &event_logger;

			$vle_count=0;
			foreach(@MLuniqueid)
				{
				if ($MLflag[$vle_count] > 0)
					{
					$vac_count=0;
					$stmtA = "SELECT count(*) FROM vicidial_auto_calls where callerid='$MLcallerid[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$vac_count	= $aryA[0];
						}
					$sthA->finish();
					if ($vac_count < 1)
						{
						$stmtA = "SELECT status FROM vicidial_log where uniqueid='$MLuniqueid[$vle_count]' and lead_id='$MLleadid[$vle_count]' and call_date > \"$RMSQLdate\";";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$MLstatus[$vle_count]	= $aryA[0];
							$sthA->finish();

							if ($MLincall !~ /\|$MLstatus[$vle_count]\|/i)
								{
								if ($MLnoanswer =~ /\|$MLstatus[$vle_count]\|/i)
									{
									if ( ($tables_use_alt_log_db =~ /log_noanswer/i) && (length($alt_log_server_ip)>4) && (length($alt_log_dbname)>0) )
										{
										$stmtA = "SELECT uniqueid,lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed,user_group,term_reason,alt_dial FROM vicidial_log where uniqueid='$MLuniqueid[$vle_count]' and lead_id='$MLleadid[$vle_count]' and call_date > \"$RMSQLdate\";";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;

											$dbhD = DBI->connect("DBI:mysql:$alt_log_dbname:$alt_log_server_ip:3306", "$alt_log_login", "$alt_log_pass")
											 or die "Couldn't connect to database: " . DBI->errstr;

											if ($DB) {print "CONNECTED TO ALT-LOG DATABASE:  $alt_log_server_ip|$alt_log_dbname\n";}

											$stmtD = "INSERT INTO vicidial_log_noanswer SET uniqueid='$aryA[0]',lead_id='$aryA[1]',list_id='$aryA[2]',campaign_id='$aryA[3]',call_date='$aryA[4]',start_epoch='$aryA[5]',end_epoch='$aryA[6]',length_in_sec='$aryA[7]',status='$aryA[8]',phone_code='$aryA[9]',phone_number='$aryA[10]',user='$aryA[11]',comments='$aryA[12]',processed='$aryA[13]',user_group='$aryA[14]',term_reason='$aryA[15]',alt_dial='$aryA[16]',caller_code='$MLcallerid[$vle_count]';";
											$affected_rows = $dbhD->do($stmtD);

											$dbhD->disconnect();
											}
										else
											{
											$event_string = "        VNA ERROR:   $MLcallerid[$vle_count]|$MLuniqueid[$vle_count]|$MLleadid[$vle_count]";
											 &event_logger;
											}
										$sthA->finish();
										}
									else
										{
										### insert vicidial_log_noanswer entry
										$stmtA = "INSERT INTO vicidial_log_noanswer SELECT uniqueid,lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed,user_group,term_reason,alt_dial,\"$MLcallerid[$vle_count]\" from vicidial_log where uniqueid='$MLuniqueid[$vle_count]' and lead_id='$MLleadid[$vle_count]' LIMIT 1;";
										$affected_rows = $dbhA->do($stmtA);
										}

									### set noanswer to Y for processed
									$stmtB = "UPDATE vicidial_log_extended SET noanswer_processed='Y' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
									$affected_rowsB = $dbhA->do($stmtB);

									$event_string = "        VNA match, log processed:   $MLcallerid[$vle_count]|$affected_rows|$affected_rowsB";
									 &event_logger;
									}
								else
									{
									### set noanswer record to U for unqualified for noanswer logging
									$stmtA = "UPDATE vicidial_log_extended SET noanswer_processed='U' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
									$affected_rows = $dbhA->do($stmtA);

									$event_string = "        VNA no-match, log processed:   $MLcallerid[$vle_count]|$affected_rows";
									 &event_logger;
									}
								}
							else
								{
								$event_string = "        VNA status in-call, do nothing:   $MLcallerid[$vle_count]|$MLuniqueid[$vle_count]|$MLstatus[$vle_count]";
								 &event_logger;
								}
							}
						else
							{
							$sthA->finish();
							$event_string = "        VNA status no vicidial_log record, do nothing:   $MLcallerid[$vle_count]|$MLuniqueid[$vle_count]|$MLstatus[$vle_count]";
							 &event_logger;
							}
						}
					else
						{
						$event_string = "        VNA active call, do nothing:   $MLcallerid[$vle_count]";
						 &event_logger;
						}
					}
				else
					{
					### set noanswer record to U for unqualified for noanswer logging
					$stmtA = "UPDATE vicidial_log_extended SET noanswer_processed='U' where uniqueid='$MLuniqueid[$vle_count]' and call_date='$MLcalldate[$vle_count]' and lead_id='$MLleadid[$vle_count]';";
					$affected_rows = $dbhA->do($stmtA);
					}
				$vle_count++;
				}
			}


	###############################################################################
	###### seventh we will check to see if any campaign has scheduled callbacks
	######    auto reschedule enabled. if yes, then go through the unprocessed
	######    vicidial_recent_ascb_calls entries for all server_ips and process them.
	############################################################################### server_ip='$server_ip' and 

		$SCARincall='|INCALL|QUEUE|DISPO|';
		$scheduled_callbacks_auto_reschedule_count=0;
		$stmtA = "SELECT count(*) FROM vicidial_campaigns where scheduled_callbacks_auto_reschedule!='DISABLED' and campaign_calldate > \"$MCDSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$scheduled_callbacks_auto_reschedule_count	= $aryA[0];
			}
		$sthA->finish();

		if ($scheduled_callbacks_auto_reschedule_count > 0)
			{
			@SCARcamp_id = @MT;
			@SCARcall_time = @MT;
			@SCARcamp_reschedule = @MT;
			@SCARstatuses = @MT;
			@SCARlists = @MT;
			@SCARstatus_groups = @MT;

			$SCARcampaigns='|';
			$stmtA = "SELECT campaign_id,local_call_time,scheduled_callbacks_auto_reschedule FROM vicidial_campaigns where scheduled_callbacks_auto_reschedule!='DISABLED' and campaign_calldate > \"$MCDSQLdate\";";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$SCARcamp_count=0;
			while ($sthArows > $SCARcamp_count)
				{
				@aryA = $sthA->fetchrow_array;
				$SCARcampaigns	.= "$aryA[0]|";
				$SCARcamp_id[$SCARcamp_count] =			$aryA[0];
				$SCARcall_time[$SCARcamp_count] =		$aryA[1];
				$SCARcamp_reschedule[$SCARcamp_count] = $aryA[2];
				$SCARcamp_count++;
				}
			$sthA->finish();

			$SCARcamp_count=0;
			foreach(@SCARcamp_id)
				{
				$SCARlists[$SCARcamp_count]='';
				$stmtA = "SELECT list_id FROM vicidial_lists where campaign_id='$SCARcamp_id[$SCARcamp_count]';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$SCARlist_count=0;
				while ($sthArows > $SCARlist_count)
					{
					@aryA = $sthA->fetchrow_array;
					$SCARlists[$SCARcamp_count]		.= "'$aryA[0]',";
					$SCARlist_count++;
					}
				$sthA->finish();
				$SCARlists[$SCARcamp_count] =~ s/,$//gi;
				if (length($SCARlists[$SCARcamp_count]) < 2)
					{$SCARlists[$SCARcamp_count]="''";}

				$SCARstatus_groups[$SCARcamp_count]='';
				if ($SCARlist_count > 0)
					{
					$stmtA = "SELECT distinct status_group_id FROM vicidial_lists where list_id IN($SCARlists[$SCARcamp_count]);";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$SCARstatus_groups_count=0;
					while ($sthArows > $SCARstatus_groups_count)
						{
						@aryA = $sthA->fetchrow_array;
						$SCARstatus_groups[$SCARcamp_count]		.= "'$aryA[0]',";
						$SCARstatus_groups_count++;
						}
					$sthA->finish();
					$SCARstatus_groups[$SCARcamp_count] =~ s/,$//gi;
					}
				if (length($SCARstatus_groups[$SCARcamp_count]) < 2)
					{$SCARstatus_groups[$SCARcamp_count]="''";}

				$SCARstatuses[$SCARcamp_count]=' ';
				$stmtA = "SELECT distinct status from vicidial_statuses where human_answered='Y' UNION select distinct status from vicidial_campaign_statuses where campaign_id='$SCARcamp_id[$SCARcamp_count]' and human_answered='Y' UNION select distinct status from vicidial_campaign_statuses where campaign_id IN($SCARstatus_groups[$SCARcamp_count]) and human_answered='Y';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$SCARstatuses_count=0;
				while ($sthArows > $SCARstatuses_count)
					{
					@aryA = $sthA->fetchrow_array;
					$SCARstatuses[$SCARcamp_count]		.= "$aryA[0] ";
					$SCARstatuses_count++;
					}
				$sthA->finish();

				$SCARcamp_count++;
				}

			$event_string = "     Scheduled Callbacks Auto Reschedule check:   $scheduled_callbacks_auto_reschedule_count active($MCDSQLdate), checking unprocessed calls...";
			 &event_logger;

			@SCARleadid = @MT;
			@SCARcalldate = @MT;
			@SCARcalldateFIVE = @MT;
			@SCARcallback_date = @MT;
			@SCARcallerid = @MT;
			@SCARorig_status = @MT;
			@SCARreschedule = @MT;
			@SCARlist_id = @MT;
			@SCARcallback_id = @MT;
			@SCARflag = @MT;
			@SCARcampaign = @MT;
			@SCARstatus = @MT;

			$stmtA = "SELECT lead_id,call_date,caller_code,orig_status,reschedule,list_id,callback_id,(call_date + INTERVAL 5 MINUTE),callback_date FROM vicidial_recent_ascb_calls where call_date > \"$MCDSQLdate\" and rescheduled='U' order by call_date,lead_id limit 100000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$vrac_count=0;
			$vrac_auto_count=0;
			while ($sthArows > $vrac_count)
				{
				$SCARflag[$vrac_count]	= 0;
				@aryA = $sthA->fetchrow_array;
				if ($aryA[2] =~ /^V|^M/)
					{
					$vrac_auto_count++;
					$SCARflag[$vrac_count]	= 1;
					}
				$SCARleadid[$vrac_count] =			$aryA[0];
				$SCARcalldate[$vrac_count] =		$aryA[1];
				$SCARcallerid[$vrac_count] =		$aryA[2];
				$SCARorig_status[$vrac_count] =		$aryA[3];
				$SCARreschedule[$vrac_count] =		$aryA[4];
				$SCARlist_id[$vrac_count] =			$aryA[5];
				$SCARcallback_id[$vrac_count] =		$aryA[6];
				$SCARcalldateFIVE[$vrac_count] =	$aryA[7];
				$SCARcallback_date[$vrac_count] =	$aryA[8];
				$vrac_count++;
				}
			$sthA->finish();

			$event_string = "     Scheduled Callbacks Auto Reschedule records count:   $vrac_count|$vrac_auto_count";
			 &event_logger;

			$vle_count=0;
			foreach(@SCARcallerid)
				{
				if ($SCARflag[$vle_count] > 0)
					{
					$vac_count=0;
					$stmtA = "SELECT count(*) FROM vicidial_auto_calls where callerid='$SCARcallerid[$vle_count]' and lead_id='$SCARleadid[$vle_count]';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$vac_count	= $aryA[0];
						}
					$sthA->finish();

					if ($vac_count < 1)
						{
						$stmtA = "SELECT campaign_id,status FROM vicidial_log where lead_id='$SCARleadid[$vle_count]' and call_date>=\"$SCARcalldate[$vle_count]\" and call_date < \"$SCARcalldateFIVE[$vle_count]\" order by call_date limit 1;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$SCARcampaign[$vle_count]	= $aryA[0];
							$SCARstatus[$vle_count]	= $aryA[1];
							$sthA->finish();

							if ($SCARcampaigns =~ /\|$SCARcampaign[$vle_count]\|/i)
								{
								if ($SCARincall !~ /\|$SCARstatus[$vle_count]\|/i)
									{
									$SCARaads_value='';
									$SCARlists_value="''";
									$SCARcall_time_value='';
									$SCARreschedule_value='';
									$SCARcamp_match=0;
									$SCARcamp_loop=0;
									while ( ($SCARcamp_match < 1) && ($SCARcamp_loop <= $SCARcamp_count) )
										{
										if ( ($SCARcampaign[$vle_count] =~ /$SCARcamp_id[$SCARcamp_loop]/i) && (length($SCARcampaign[$vle_count]) == length($SCARcamp_id[$SCARcamp_loop])) )
											{
											$SCARcamp_match++;
											$SCARaads_value = $SCARstatuses[$SCARcamp_loop];
											$SCARlists_value = $SCARlists[$SCARcamp_loop];
											$SCARcall_time_value = $SCARcall_time[$SCARcamp_loop];
											$SCARreschedule_value = $SCARcamp_reschedule[$SCARcamp_loop];
											}
										$SCARcamp_loop++;
										}
									if ($SCARaads_value !~ / $SCARstatus[$vle_count] /i)
										{
										$event_string = "        SCAR human-answered status no-match, starting auto-reschedule:   $SCARcallerid[$vle_count]|$SCARstatus[$vle_count]|$SCARcallback_date[$vle_count]|$SCARreschedule_value|";
										 &event_logger;

										@temp_dateARY = split(" ",$SCARcallback_date[$vle_count]);
										@temp_orig_date = split("-",$temp_dateARY[0]);
										@temp_orig_time = split(":",$temp_dateARY[1]);
										$temp_orig_date[1] = ($temp_orig_date[1] - 1);
										$temp_time = timelocal($temp_orig_time[2],$temp_orig_time[1],$temp_orig_time[0],$temp_orig_date[2],$temp_orig_date[1],$temp_orig_date[0]);

										$recheduled_first_date_calculated=0;
										@SCARreschedule_valueARY = split('_',$SCARreschedule_value);
										if ($SCARreschedule_valueARY[0] eq 'DAY')
											{$temp_time_next = ($temp_time + (86400 * $SCARreschedule_valueARY[1]) );   $recheduled_first_date_calculated++;}
										if ($SCARreschedule_valueARY[0] eq 'WEEK')
											{$temp_time_next = ($temp_time + (604800 * $SCARreschedule_valueARY[1]) );   $recheduled_first_date_calculated++;}
										if ($SCARreschedule_valueARY[0] eq 'MONTH')
											{$temp_time_next = ($temp_time + (2419200 * $SCARreschedule_valueARY[1]) );   $recheduled_first_date_calculated++;}
										if ($recheduled_first_date_calculated < 1)
											{$temp_time_next = ($temp_time + 86400);}

										while ($now_date_epoch > $temp_time_next)
											{$temp_time_next = ($temp_time_next + 86400);}

										$temp_SCAR_lead_id='';   $temp_SCAR_gmt_offset='';   $temp_SCAR_state='';   $temp_SCAR_list_id='';
										$stmtA = "SELECT gmt_offset_now,state,list_id,status FROM vicidial_list where lead_id='$SCARleadid[$vle_count]';";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$temp_SCAR_lead_id =	$SCARleadid[$vle_count];
											$temp_SCAR_gmt_offset = $aryA[0];
											$temp_SCAR_state =		$aryA[1];
											$temp_SCAR_list_id =	$aryA[2];
											$temp_SCAR_status =		$aryA[3];
											}
										$sthA->finish();

										$stmtA = "SELECT local_call_time FROM vicidial_lists where list_id='$temp_SCAR_list_id';";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$temp_list_call_time_override = $aryA[0];
											if ($temp_list_call_time_override ne 'campaign')
												{$SCARcall_time_value = $temp_list_call_time_override;}
											}
										$sthA->finish();

										$temp_time_dialable=0;   $temp_count=0;   $SCARcalldateNEW[$vle_count]='';   $SCARcalldateNEWlocal[$vle_count]='';
										while ( ($temp_time_dialable < 1) && ($temp_count < 58) )
											{
											&temp_time_dialable_check;
											if ($temp_time_dialable < 1)
												{
												if ($DBX > 0) {print "     SCAR next time debug: $temp_count|$temp_time_next|$SCARcalldateNEW[$vle_count]|$SCARcalldateNEWlocal[$vle_count]|\n";}
												# add 6 hours to temp_time_next for the next loop
												$temp_time_next = ($temp_time_next + 21600);
												}
											$temp_count++;
											}
										if ($temp_time_dialable < 1)
											{
											$event_string = "        SCAR auto-reschedule time failed:   $SCARcallerid[$vle_count]|$SCARcallback_id[$vle_count]|$SCARcalldate[$vle_count]|$temp_orig_time|$temp_time_dialable|$temp_time_next|";
											 &event_logger;
											}
										else
											{
											$event_string = "        SCAR auto-reschedule time calculated:   $SCARcallerid[$vle_count]|$SCARcallback_id[$vle_count]|$SCARcalldate[$vle_count]|$temp_orig_time|$SCARcalldateNEW[$vle_count]|";
											 &event_logger;

											### update existing vicidial_callbacks record with auto-reschedule time (,lead_status='$temp_SCAR_status')
											$stmtA="UPDATE vicidial_callbacks SET status='ACTIVE',callback_time='$SCARcalldateNEW[$vle_count]' where callback_id='$SCARcallback_id[$vle_count]' and lead_id='$SCARleadid[$vle_count]';";
											$affected_rowsA = $dbhA->do($stmtA);

											### set vicidial_recent_ascb_calls status non-match, set record to Y for processed, auto-reschedule updated
											$stmtB = "UPDATE vicidial_recent_ascb_calls SET rescheduled='Y' where caller_code='$SCARcallerid[$vle_count]';";
											$affected_rowsB = $dbhA->do($stmtB);

											### update existing vicidial_list record with CBHOLD status
											$stmtC="UPDATE vicidial_list SET status='CBHOLD' where lead_id='$SCARleadid[$vle_count]';";
											$affected_rowsC = $dbhA->do($stmtC);

											$event_string = "        SCAR auto-reschedule updated:   $SCARcallerid[$vle_count]|$affected_rowsA|$affected_rowsB|$affected_rowsC|$stmtA|";
											 &event_logger;
											}
										}
									else
										{
										### set vicidial_recent_ascb_calls record to P for processed as a human-answered call, not-auto-rescheduled
										$stmtA = "UPDATE vicidial_recent_ascb_calls SET rescheduled='P' where caller_code='$SCARcallerid[$vle_count]';";
										$affected_rows = $dbhA->do($stmtA);

										$event_string = "        SCAR match, log processed:   $SCARcallerid[$vle_count]|$affected_rows";
										 &event_logger;
										}
									}
								else
									{
									$event_string = "        SCAR status in-call, do nothing:   $SCARcallerid[$vle_count]|$SCARuniqueid[$vle_count]|$SCARstatus[$vle_count]";
									 &event_logger;
									}
								}
							else
								{
								### set vicidial_recent_ascb_calls record to N for no-auto-reschedule
								$stmtA = "UPDATE vicidial_recent_ascb_calls SET rescheduled='N' where caller_code='$SCARcallerid[$vle_count]';";
								$affected_rows = $dbhA->do($stmtA);
								}
							}
						else
							{
							$sthA->finish();
							$event_string = "        SCAR no log entry, do nothing:   $SCARcallerid[$vle_count]|$SCARcalldate[$vle_count]";
							 &event_logger;
							}
						}
					else
						{
						$event_string = "        SCAR active call, do nothing:   $SCARcallerid[$vle_count]";
						 &event_logger;
						}
					}
				else
					{
					### set vicidial_recent_ascb_calls record to N for no-auto-reschedule because call was not outbound
					$stmtA = "UPDATE vicidial_recent_ascb_calls SET rescheduled='N' where caller_code='$SCARcallerid[$vle_count]';";
					$affected_rows = $dbhA->do($stmtA);
					}
				$vle_count++;
				}
			}
		}
	else
		{
		if ($DB) {print "not the active voicemail server skipped MULTI_LEAD,no-agent-url,no_answer,recent_ascb_calls processing \n";}
		}

	###############################################################################
	###### last, wait for a little bit and repeat the loop
	###############################################################################

		### sleep for 2 and a half seconds before beginning the loop again
		usleep(1*$loop_delay*1000);

		$endless_loop--;
		if($DB){print STDERR "\nloop counter: |$endless_loop|\n";}

		### putting a blank file called "VDAD.kill" in the directory will automatically safely kill this program
		if (-e "$PATHhome/VDAD.kill")
			{
			unlink("$PATHhome/VDAD.kill");
			$endless_loop=0;
			$one_day_interval=0;
			print "\nPROCESS KILLED MANUALLY... EXITING\n\n"
			}
		if ($endless_loop =~ /0$/)	# run every ten cycles (about 25 seconds)
			{
			### Grab Server values from the database
				$stmtA = "SELECT vd_server_logs FROM servers where server_ip = '$VARserver_ip';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				while ($sthArows > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$DBvd_server_logs =		$aryA[0];
					if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
					else {$SYSLOG = '0';}
					$rec_count++;
					}
				$sthA->finish();

			#############################################
			##### START QUEUEMETRICS LOGGING LOOKUP #####
			$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_loginout FROM system_settings;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$enable_queuemetrics_logging =	$aryA[0];
				$queuemetrics_server_ip	=		$aryA[1];
				$queuemetrics_dbname =			$aryA[2];
				$queuemetrics_login=			$aryA[3];
				$queuemetrics_pass =			$aryA[4];
				$queuemetrics_log_id =			$aryA[5];
				$queuemetrics_loginout =		$aryA[6];
				}
			$sthA->finish();
			##### END QUEUEMETRICS LOGGING LOOKUP #####
			###########################################

			### display and delete call records that are LIVE but not updated for over 100 minutes
			$stmtA = "SELECT status,lead_id,call_time FROM vicidial_auto_calls where server_ip='$server_ip' and call_time < '$TDSQLdate' and status NOT IN('XFER','CLOSER');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$dead_delete_string='';
			$dead_delete_ct=0;
			while ($sthArows > $dead_delete_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$dead_delete_string .= "$dead_delete_ct-$aryA[0]-$aryA[1]-$aryA[2]|";
				$dead_delete_ct++;
				}
			$sthA->finish();

			if ($dead_delete_ct > 0)
				{
				### delete call records that are LIVE but not updated for over 100 minutes
				$stmtA = "DELETE FROM vicidial_auto_calls where server_ip='$server_ip' and call_time < '$TDSQLdate' and status NOT IN('XFER','CLOSER');";
				$affected_rows = $dbhA->do($stmtA);

				$event_string = "|     lagged call vac 100-minutes DELETED $affected_rows|$TDSQLdate|LIVE|$dead_delete_string";
				&event_logger;
				}

			### Grab Server values from the database in case they've changed
			$stmtA = "SELECT max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context FROM servers where server_ip = '$server_ip';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$DBmax_vicidial_trunks = 	$aryA[0];
				$DBanswer_transfer_agent = 	$aryA[1];
				$DBSERVER_GMT =				$aryA[2];
				$DBext_context = 			$aryA[3];
					$max_vicidial_trunks = $DBmax_vicidial_trunks;
				if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
				if (length($DBSERVER_GMT) > 0)	{$SERVER_GMT = $DBSERVER_GMT;}
				if ($DBext_context)				{$ext_context = $DBext_context;}
				}
			$sthA->finish();

			$event_string = "|     updating server parameters $max_vicidial_trunks|$answer_transfer_agent|$SERVER_GMT|$ext_context|";
			&event_logger;

				&get_time_now;

			#@psoutput = `/bin/ps -f --no-headers -A`;
			@psoutput = `/bin/ps -o "%p %a" --no-headers -A`;

			$running_listen = 0;

			$i=0;
			foreach (@psoutput)
				{
				chomp($psoutput[$i]);

				@psline = split(/\/usr\/bin\/perl /,$psoutput[$i]);

				if ($psline[1] =~ /AST_manager_li/) {$running_listen++;}

				$i++;
				}

			if (!$running_listen)
				{
				$endless_loop=0;
				$one_day_interval=0;
				print "\nPROCESS KILLED NO LISTENER RUNNING... EXITING\n\n";
				}

			if($DB){print "checking to see if listener is dead |$running_listen|\n";}
			}

		$bad_grabber_counter=0;

		$stat_count++;

		# update debug output
		$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',adapt_output='$debug_shared_output' where campaign_id='-SHARE-' and server_ip='$VARserver_ip';";
		$affected_rows = $dbhA->do($stmtA);

		if ($allow_shared_dial > 1) 
			{
			$temp_dead_debug='';
			if ($dead_call_ct > 0) 
				{$temp_dead_debug="DEAD Call Count: $dead_call_ct";}
			$stmtA = "INSERT INTO vicidial_shared_log SET log_time='$now_date',total_agents='$shared_dial_camp_override',debug_output='BUILD: $build\n$debug_shared_output$temp_dead_debug',campaign_id='-SHARE-',server_ip='$VARserver_ip';";
			$affected_rows = $dbhA->do($stmtA);
			}
		}


	if($DB){print "DONE... Exiting... Goodbye... See you later... Not really, initiating next loop...\n";}

	$event_string='HANGING UP|';
	&event_logger;

	$one_day_interval--;

	}

$event_string='CLOSING DB CONNECTION|';
&event_logger;


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;













sub get_time_now	#get the current date and time and epoch for logging call lengths and datetimes
	{
	$secX = time();
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($secX);
	$LOCAL_GMT_OFF = $SERVER_GMT;
	$LOCAL_GMT_OFF_STD = $SERVER_GMT;
	if ($isdst) {$LOCAL_GMT_OFF++;}
	$check_time = ($secX - 86400);

	$GMT_now = ($secX - ($LOCAL_GMT_OFF * 3600));
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($GMT_now);
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}

	if ($DB) {print "TIME DEBUG: $LOCAL_GMT_OFF_STD|$LOCAL_GMT_OFF|$isdst|   GMT: $hour:$min\n";}

	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	$now_date_epoch = time();
	$now_date = "$year-$mon-$mday $hour:$min:$sec";
	$file_date = "$year-$mon-$mday";
	$CIDdate = "$mon$mday$hour$min$sec";
	$tsSQLdate = "$year$mon$mday$hour$min$sec";
	$SQLdate = "$year-$mon-$mday $hour:$min:$sec";
		while (length($CIDdate) > 9) {$CIDdate =~ s/^.//gi;} # 0902235959 changed to 902235959

	$FVtarget = ($secX - 5);
	($Fsec,$Fmin,$Fhour,$Fmday,$Fmon,$Fyear,$Fwday,$Fyday,$Fisdst) = localtime($FVtarget);
	$Fyear = ($Fyear + 1900);
	$Fmon++;
	if ($Fmon < 10) {$Fmon = "0$Fmon";}
	if ($Fmday < 10) {$Fmday = "0$Fmday";}
	if ($Fhour < 10) {$Fhour = "0$Fhour";}
	if ($Fmin < 10) {$Fmin = "0$Fmin";}
	if ($Fsec < 10) {$Fsec = "0$Fsec";}
	$FVtsSQLdate = "$Fyear-$Fmon-$Fmday $Fhour:$Fmin:$Fsec";

	$BDtarget = ($secX - 10);
	($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
	$Byear = ($Byear + 1900);
	$Bmon++;
	if ($Bmon < 10) {$Bmon = "0$Bmon";}
	if ($Bmday < 10) {$Bmday = "0$Bmday";}
	if ($Bhour < 10) {$Bhour = "0$Bhour";}
	if ($Bmin < 10) {$Bmin = "0$Bmin";}
	if ($Bsec < 10) {$Bsec = "0$Bsec";}
	$BDtsSQLdate = "$Byear$Bmon$Bmday$Bhour$Bmin$Bsec";

	$PDtarget = ($secX - 30);
	($Psec,$Pmin,$Phour,$Pmday,$Pmon,$Pyear,$Pwday,$Pyday,$Pisdst) = localtime($PDtarget);
	$Pyear = ($Pyear + 1900);
	$Pmon++;
	if ($Pmon < 10) {$Pmon = "0$Pmon";}
	if ($Pmday < 10) {$Pmday = "0$Pmday";}
	if ($Phour < 10) {$Phour = "0$Phour";}
	if ($Pmin < 10) {$Pmin = "0$Pmin";}
	if ($Psec < 10) {$Psec = "0$Psec";}
	$PDtsSQLdate = "$Pyear$Pmon$Pmday$Phour$Pmin$Psec";
	$halfminSQLdate = "$Pyear-$Pmon-$Pmday $Phour:$Pmin:$Psec";

	$XDtarget = ($secX - 120);
	($Xsec,$Xmin,$Xhour,$Xmday,$Xmon,$Xyear,$Xwday,$Xyday,$Xisdst) = localtime($XDtarget);
	$Xyear = ($Xyear + 1900);
	$Xmon++;
	if ($Xmon < 10) {$Xmon = "0$Xmon";}
	if ($Xmday < 10) {$Xmday = "0$Xmday";}
	if ($Xhour < 10) {$Xhour = "0$Xhour";}
	if ($Xmin < 10) {$Xmin = "0$Xmin";}
	if ($Xsec < 10) {$Xsec = "0$Xsec";}
	$XDSQLdate = "$Xyear-$Xmon-$Xmday $Xhour:$Xmin:$Xsec";

	$TDtarget = ($secX - 6000);
	($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TDtarget);
	$Tyear = ($Tyear + 1900);
	$Tmon++;
	if ($Tmon < 10) {$Tmon = "0$Tmon";}
	if ($Tmday < 10) {$Tmday = "0$Tmday";}
	if ($Thour < 10) {$Thour = "0$Thour";}
	if ($Tmin < 10) {$Tmin = "0$Tmin";}
	if ($Tsec < 10) {$Tsec = "0$Tsec";}
	$TDtsSQLdate = "$Tyear$Tmon$Tmday$Thour$Tmin$Tsec";
	$TDSQLdate = "$Tyear-$Tmon-$Tmday $Thour:$Tmin:$Tsec";

	$MCDtarget = ($secX - ($DBvicidial_recording_limit * 60));
	($MCsec,$MCmin,$MChour,$MCmday,$MCmon,$MCyear,$MCwday,$MCyday,$MCisdst) = localtime($MCDtarget);
	$MCyear = ($MCyear + 1900);
	$MCmon++;
	if ($MCmon < 10) {$MCmon = "0$MCmon";}
	if ($MCmday < 10) {$MCmday = "0$MCmday";}
	if ($MChour < 10) {$MChour = "0$MChour";}
	if ($MCmin < 10) {$MCmin = "0$MCmin";}
	if ($MCsec < 10) {$MCsec = "0$MCsec";}
	$MCDtsSQLdate = "$MCyear$MCmon$MCmday$MChour$MCmin$MCsec";
	$MCDSQLdate = "$MCyear-$MCmon-$MCmday $MChour:$MCmin:$MCsec";

	$RMtarget = ($secX - 21600);	# 6 hours ago
	($RMsec,$RMmin,$RMhour,$RMmday,$RMmon,$RMyear,$RMwday,$RMyday,$RMisdst) = localtime($RMtarget);
	$RMyear = ($RMyear + 1900);
	$RMmon++;
	if ($RMmon < 10) {$RMmon = "0$RMmon";}
	if ($RMmday < 10) {$RMmday = "0$RMmday";}
	if ($RMhour < 10) {$RMhour = "0$RMhour";}
	if ($RMmin < 10) {$RMmin = "0$RMmin";}
	if ($RMsec < 10) {$RMsec = "0$RMsec";}
	$RMSQLdate = "$RMyear-$RMmon-$RMmday $RMhour:$RMmin:$RMsec";
	}



sub temp_time_dialable_check
	{
	$SERVER_GMT_OFF=0;
	$SERVERsec_diff=0;
	$SERVERtemp_time_next = $temp_time_next;
	$SERVERtempGMT_now = $SERVERtemp_time_next;
	($SXsec,$SXmin,$SXhour,$SXmday,$SXmon,$SXyear,$SXwday,$SXyday,$SXisdst) = localtime($SERVERtemp_time_next);
	if ($SXisdst) {$SERVER_GMT_OFF++;}
	if ( ($SXisdst > 0) && ($isdst < 1) )
		{$SERVERtempGMT_now = ($SERVERtemp_time_next - 3600);   $SERVERsec_diff = -3600;}
	if ( ($SXisdst < 1) && ($isdst  > 0) )
		{$SERVERtempGMT_now = ($SERVERtemp_time_next + 3600);   $SERVERsec_diff = 3600;}
	($SYsec,$SYmin,$SYhour,$SYmday,$SYmon,$SYyear,$SYwday,$SYyday,$SYisdst) = localtime($SERVERtempGMT_now);
	$SYyear = ($SYyear + 1900);
	$SYmon++;
	if ($SYmon < 10) {$SYmon = "0$SYmon";}
	if ($SYmday < 10) {$SYmday = "0$SYmday";}
	if ($SYsec < 10) {$SYsec = "0$SYsec";}
	if ($SYmin < 10) {$SYmin = "0$SYmin";}
	if ($SYhour < 10) {$SYhour = "0$SYhour";}
	$SCARcalldateNEW[$vle_count] = "$SYyear-$SYmon-$SYmday $SYhour:$SYmin:$SYsec";
	if ($DBX > 0) {print "     SCAR debug Server time: |$SERVERtempGMT_now|$SXisdst|$isdst|$SERVERsec_diff|\n";}

	$SCARzone_diff = (($SERVER_GMT + $SERVER_GMT_OFF) - $temp_SCAR_gmt_offset);
	$SCARzone=0;
	if ($SCARzone_diff != 0)
		{$SCARzone = (3600 * $SCARzone_diff);}
	$LOCALnow_date_epoch = ($now_date_epoch + $SCARzone);
	if ($DBX > 0) {print "      SCAR debug Local time: |$SCARzone_diff (($SERVER_GMT + $SERVER_GMT_OFF) - $temp_SCAR_gmt_offset)|$LOCALnow_date_epoch ($now_date_epoch + $SCARzone)|\n";}
	($TNsec,$TNmin,$TNhour,$TNmday,$TNmon,$TNyear,$TNwday,$TNyday,$TNisdst) = localtime($LOCALnow_date_epoch);
	$LOCALsec_diff=0;
	$LOCALtemp_time_next = ($temp_time_next + $SCARzone);
	($TXsec,$TXmin,$TXhour,$TXmday,$TXmon,$TXyear,$TXwday,$TXyday,$TXisdst) = localtime($LOCALtemp_time_next);
	if ( ($TXisdst > 0) && ($TNisdst < 1) )
		{$LOCALtempGMT_now = ($LOCALtemp_time_next - 3600);   $LOCALsec_diff = -3600;}
	if ( ($TXisdst < 1) && ($TNisdst  > 0) )
		{$LOCALtempGMT_now = ($LOCALtemp_time_next + 3600);   $LOCALsec_diff = 3600;}
	$tempGMT_now = ($LOCALtemp_time_next + $LOCALsec_diff);
	if ($DBX > 0) {print "            SCAR debug time: |$TXisdst|$TNisdst|$LOCALtemp_time_next|$LOCALsec_diff|$tempGMT_now|\n";}
	($TYsec,$TYmin,$TYhour,$TYmday,$TYmon,$TYyear,$TYwday,$TYyday,$TYisdst) = localtime($tempGMT_now);
	$TYyear = ($TYyear + 1900);
	$TYmon++;
	if ($TYmon < 10) {$TYmon = "0$TYmon";}
	if ($TYmday < 10) {$TYmday = "0$TYmday";}
	if ($TYsec < 10) {$TYsec = "0$TYsec";}
	if ($TYmin < 10) {$TYmin = "0$TYmin";}
	$SCARhour = "$TYhour$TYmin";   $SCARhour = ($SCARhour + 0);
	if ($TYhour < 10) {$TYhour = "0$TYhour";}
	$SCARcalldateNEWlocal[$vle_count] = "$TYyear-$TYmon-$TYmday $TYhour:$TYmin:$TYsec";
	$SCARdate = "$TYyear-$TYmon-$TYmday";

	$ct_default_start='2400';
	$ct_default_stop='2400';
	$ct_state_call_times='';
	$ct_holidays='';
	$ct_day_start='2400';
	$ct_day_stop='2400';

	$TY_timeSQL=',2400,2400';
	if ($TYwday eq '0') {$TY_timeSQL = ',ct_sunday_start,ct_sunday_stop';}
	if ($TYwday eq '1') {$TY_timeSQL = ',ct_monday_start,ct_monday_stop';}
	if ($TYwday eq '2') {$TY_timeSQL = ',ct_tuesday_start,ct_tuesday_stop';}
	if ($TYwday eq '3') {$TY_timeSQL = ',ct_wednesday_start,ct_wednesday_stop';}
	if ($TYwday eq '4') {$TY_timeSQL = ',ct_thursday_start,ct_thursday_stop';}
	if ($TYwday eq '5') {$TY_timeSQL = ',ct_friday_start,ct_friday_stop';}
	if ($TYwday eq '6') {$TY_timeSQL = ',ct_saturday_start,ct_saturday_stop';}

	### Grab Call Time values from the database
	$stmtA="SELECT ct_default_start,ct_default_stop,ct_state_call_times,ct_holidays $TY_timeSQL FROM vicidial_call_times where call_time_id='$SCARcall_time_value';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$ct_default_start = 	$aryA[0];
		$ct_default_stop = 		$aryA[1];
		$ct_state_call_times =	$aryA[2];
		$ct_holidays = 			$aryA[3];
		$ct_day_start =			$aryA[4];
		$ct_day_stop = 			$aryA[5];
		}
	$sthA->finish();
	if ($DBX > 0) {print "     SCAR call time debug: $sthArows|$stmtA|$SCARcall_time_value|$ct_default_start|$ct_default_stop|$ct_state_call_times|$ct_holidays|$ct_day_start|$ct_day_stop($TYwday)|\n";}

	### Check for outbound holidays ###
	$holiday_id = '';   $holidy_applied=0;
	if (length($ct_holidays) > 2)
		{
		$ct_holidaysSQL = $ct_holidays;
		$ct_holidaysSQL =~ s/\|/','/gi;
		$ct_holidaysSQL = "'".$ct_holidaysSQL."'";

		$stmtA="SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($ct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$SCARdate' order by holiday_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$holiday_id =			$aryA[0];
			$holiday_date =			$aryA[1];
			$holiday_name =			$aryA[2];
			if ( ($ct_default_start < $aryA[3]) && ($ct_default_stop > 0) )	{$ct_default_start = $aryA[3];   $holidy_applied++;}
			if ( ($ct_default_stop > $aryA[4]) && ($ct_default_stop > 0) )	{$ct_default_stop = $aryA[4];   $holidy_applied++;}
			if ( ($ct_day_start < $aryA[3]) && ($ct_day_start > 0) )		{$ct_day_start = $aryA[3];   $holidy_applied++;}
			if ( ($ct_day_stop > $aryA[4]) && ($ct_day_stop > 0) )			{$ct_day_stop = $aryA[4];   $holidy_applied++;}
			}
		$sthA->finish();
		if ($DBX > 0) {print "     SCAR call time holiday debug: $sthArows|$stmtA|$ct_default_start|$ct_default_stop|$ct_state_call_times|$ct_day_start|$ct_day_stop|\n";}
		}
	### BEGIN gather state call times, if configured
	if ( (length($ct_state_call_times) > 2) && (length($temp_SCAR_state) > 0) )
		{
		$state_ct_match=0;
		$b=0;
		@temp_state_rules = split( /\|/,$ct_state_call_times);

		if ($DBX > 0) {print "     SCAR state call time count debug: -$ct_state_call_times-|$temp_SCAR_state|\n";}

		foreach(@temp_state_rules)
			{
			if (length($temp_state_rules[$b]) > 1)
				{
				$sct_default_start='2400';
				$sct_default_stop='2400';
				$sct_state_call_times='';
				$sct_holidays='';
				$sct_day_start='2400';
				$sct_day_stop='2400';
				$TY_StimeSQL=',2400,2400';
				if ($TYwday eq '0') {$TY_StimeSQL = ',sct_sunday_start,sct_sunday_stop';}
				if ($TYwday eq '1') {$TY_StimeSQL = ',sct_monday_start,sct_monday_stop';}
				if ($TYwday eq '2') {$TY_StimeSQL = ',sct_tuesday_start,sct_tuesday_stop';}
				if ($TYwday eq '3') {$TY_StimeSQL = ',sct_wednesday_start,sct_wednesday_stop';}
				if ($TYwday eq '4') {$TY_StimeSQL = ',sct_thursday_start,sct_thursday_stop';}
				if ($TYwday eq '5') {$TY_StimeSQL = ',sct_friday_start,sct_friday_stop';}
				if ($TYwday eq '6') {$TY_StimeSQL = ',sct_saturday_start,sct_saturday_stop';}
				$stmtA="SELECT sct_default_start,sct_default_stop,ct_holidays $TY_StimeSQL from vicidial_state_call_times where state_call_time_id='$temp_state_rules[$b]' and state_call_time_state='$temp_SCAR_state';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$sct_default_start = 	$aryA[0];
					$sct_default_stop = 	$aryA[1];
					$sct_holidays = 		$aryA[2];
					$sct_day_start =		$aryA[3];
					$sct_day_stop = 		$aryA[4];
					$state_ct_match++;
					}
				$sthA->finish();
				if ($DBX > 0) {print "     SCAR state call time debug: $sthArows|$stmtA|$state_ct_match|$temp_state_rules[$b]|$temp_SCAR_state|$sct_default_start|$sct_default_stop|$sct_holidays|$sct_day_start|$sct_day_stop|\n";}

				if (length($sct_holidays) > 2)
					{
					$sholidy_applied=0;
					$sct_holidaysSQL = $sct_holidays;
					$sct_holidaysSQL =~ s/\|/','/gi;
					$sct_holidaysSQL = "'".$sct_holidaysSQL."'";

					$stmtA="SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($sct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$SCARdate' order by holiday_id;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$holiday_id =			$aryA[0];
						$holiday_date =			$aryA[1];
						$holiday_name =			$aryA[2];
						if ( ($sct_default_start < $aryA[3]) && ($sct_default_stop > 0) )	{$sct_default_start = $aryA[3];   $sholidy_applied++;}
						if ( ($sct_default_stop > $aryA[4]) && ($sct_default_stop > 0) )	{$sct_default_stop = $aryA[4];   $sholidy_applied++;}
						if ( ($sct_day_start < $aryA[3]) && ($sct_day_start > 0) )			{$sct_day_start = $aryA[3];   $sholidy_applied++;}
						if ( ($sct_day_stop > $aryA[4]) && ($sct_day_stop > 0) )			{$sct_day_stop = $aryA[4];   $sholidy_applied++;}
						}
					$sthA->finish();
					if ($DBX > 0) {print "     SCAR state call time holiday debug: $sthArows|$stmtA|$sct_default_start|$sct_default_stop|$sct_state_call_times|$sct_day_start|$sct_day_stop|\n";}
					}
				if ( ($sholidy_applied < 1) && ($holidy_applied > 0) )
					{
					if ( ($sct_default_start < $ct_default_start) && ($sct_default_stop > 0) )	{$sct_default_start = $ct_default_start;}
					if ( ($sct_default_stop > $ct_default_stop) && ($sct_default_stop > 0) )	{$sct_default_stop = $ct_default_stop;}
					if ( ($sct_day_start < $ct_default_start) && ($sct_day_start > 0) )			{$sct_day_start = $ct_default_start;}
					if ( ($sct_day_stop > $ct_default_stop) && ($sct_day_stop > 0) )			{$sct_day_stop = $ct_default_stop;}
					}
				}
			$b++;
			}
		}
	### END gather state call times, if configured

	if ($state_ct_match > 0)
		{
		# STATE RULES
		if (($sct_day_start == 0) && ($sct_day_stop == 0))
			{
			if ( ($SCARhour >= $sct_default_start) && ($SCARhour < $sct_default_stop) )
				{$temp_time_dialable=1;}
			}
		else
			{
			if ( ($SCARhour >= $sct_day_start) && ($SCARhour < $sct_day_stop) )
				{$temp_time_dialable=1;}
			}
		}
	else
		{
		#NO STATE RULES
		if ( ($ct_day_start == 0) and ($ct_day_stop == 0) )
			{
			if ( ($SCARhour >= $ct_default_start) and ($SCARhour < $ct_default_stop) )
				{$temp_time_dialable=1;}
			}
		else
			{
			if ( ($SCARhour >= $ct_day_start) and ($SCARhour < $ct_day_stop) )
				{$temp_time_dialable=1;}
			}
		}

	if ($DBX > 0) {print "     SCAR dialable debug: $temp_time_dialable|$state_ct_match|$SCARhour|$sct_default_start|$sct_default_stop|$sct_day_start|$sct_day_stop|$ct_default_start|$ct_default_stop|$ct_day_start|$ct_day_stop|$SCARcalldateNEWlocal[$vle_count]|$SCARdate\n";}
	}


sub check_24hour_call_count
	{
	$passed_24hour_call_count=0;
	$limit_scopeSQL='';
	if ($VD_call_limit_24hour_scope =~ /CAMPAIGN_LISTS/) 
		{
		$limit_scopeCAMP='';
		$stmtY = "SELECT list_id FROM vicidial_lists where campaign_id='$CLcampaign_id';";
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
	if ($VD_call_limit_24hour_method =~ /PHONE_NUMBER/)
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where phone_number='$temp_24hour_phone' and phone_code='$temp_24hour_phone_code' and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	else
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where lead_id='$CLlead_id' and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	if ($DB) {print "     Doing 24-Hour Call Count Check: $CLlead_id|$temp_24hour_phone_code|$temp_24hour_phone|$temp_24hour_state|$temp_24hour_postal_code - $VD_call_limit_24hour_method|$VD_call_limit_24hour_scope|$VD_call_limit_24hour\n";}
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
	$TEMPcall_limit_24hour = $VD_call_limit_24hour;
	if ($DBX) {print "     24-Hour Call Limit Count DEBUG:     $TFhourCOUNT|$stmtA|\n";}

	if ( ($VD_call_limit_24hour_override !~ /^DISABLED$/) && (length($VD_call_limit_24hour_override) > 0) ) 
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
		$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$VD_call_limit_24hour_override';";
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
		if ($DBX) {print "Flagging 24-Hour Call Limit lead:     $CLlead_id ($TFhourCOUNT >= $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	else
		{
		$passed_24hour_call_count=1;
		if ($DBX) {print "     24-Hour Call Limit check passed:     $CLlead_id ($TFhourCOUNT < $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	}

sub event_logger
	{
	if ($DB) {print "$now_date|$event_string|\n";}
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$VDADLOGfile")
				|| die "Can't open $VDADLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}

sub jam_event_logger
	{
	if ($DB) {print "$now_date|$jam_string|\n";}
	if ($useJAMdebugFILE)
		{
		### open the log file for writing ###
		open(Jout, ">>$JAMdebugFILE")
				|| die "Can't open $JAMdebugFILE: $!\n";
		print Jout "$now_date|$jam_string|\n";
		close(Jout);
		}
	$jam_string='';
	}

sub aad_output
	{
	if ( ($SYSLOG > 0) || ($ADB > 0) )
		{
		### open the log file for writing ###
		open(Aout, ">>$AADLOGfile") || die "Can't open $AADLOGfile: $!\n";
		print Aout "$now_date|$script|$aad_string\n";
		close(Aout);
		}
	$aad_string='';
	}


# subroutine to parse the asterisk version
# and return a hash with the various part
sub parse_asterisk_version
{
        # grab the arguments
        my $ast_ver_str = $_[0];

        # get everything after the - and put it in $ast_ver_postfix
        my @hyphen_parts = split( /-/ , $ast_ver_str );

        my $ast_ver_postfix = $hyphen_parts[1];

        # now split everything before the - up by the .
        my @dot_parts = split( /\./ , $hyphen_parts[0] );

        my %ast_ver_hash;

        if ( $dot_parts[0] <= 1 )
                {
                        %ast_ver_hash = (
                                "major" => $dot_parts[0],
                                "minor" => $dot_parts[1],
                                "build" => $dot_parts[2],
                                "revision" => $dot_parts[3],
                                "postfix" => $ast_ver_postfix
                        );
                }

        # digium dropped the 1 from asterisk 10 but we still need it
        if ( $dot_parts[0] > 1 )
                {
                        %ast_ver_hash = (
                                "major" => 1,
                                "minor" => $dot_parts[0],
                                "build" => $dot_parts[1],
                                "revision" => $dot_parts[2],
                                "postfix" => $ast_ver_postfix
                        );
                }

        return ( %ast_ver_hash );
}
