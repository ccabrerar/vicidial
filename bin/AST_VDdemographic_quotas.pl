#!/usr/bin/perl
#
# AST_VDdemographic_quotas.pl version 2.14
#
# DESCRIPTION:
# Operates the campaign Demographic Quotas features
#
# SUMMARY:
# For VICIDIAL outbound dialing, this program is triggered by another program
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 230427-1424 - First build
# 231116-0921 - Added hopper_hold_inserts option
#

# constants
$build = '231116-0921';
$script='demo_quota';
$DB=0;  # Debug flag, set to 0 for no debug messages. Can be overriden with CLI --debug flag
$US='__';
$MT[0]='';
$force_rerank=0;

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
$reset_test = "$hour$min";

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
		print "  [--force-rerank] = will force re-ranking of leads for this run\n";
		print "  [--rerank-limit=XXX] = force a re-rank limit of XXX\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--container=XXX] = force a container_id of XXX\n";
		print "  [--campaign=XXX] = run for campaign XXX only(or more campaigns if separated by triple dash ---)\n";
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
		if ($args =~ /--container=/i)
			{
			@data_in = split(/--container=/,$args);
			$CLIcontainer = $data_in[1];
			$CLIcontainer =~ s/ .*$//gi;
			print "\n----- CONTAINER OVERRIDE: $CLIcontainer -----\n\n";
			}
		else
			{$CLIcontainer = '';}
		if ($args =~ /--rerank-limit=/i)
			{
			@data_in = split(/--rerank-limit=/,$args);
			$CLIrerank_limit = $data_in[1];
			$CLIrerank_limit =~ s/ .*$//gi;
			$CLIrerank_limit =~ s/\D//gi;
			print "\n----- RE-RANK LIMIT OVERRIDE: $CLIrerank_limit -----\n\n";
			}
		else
			{$CLIrerank_limit = '';}
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
			print "\n-----TESTING -----\n\n";
			}
		if ($args =~ /--count-only/i)
			{
			$count_only=1;
			}
		if ($args =~ /--force-rerank/i)
			{
			$force_rerank=1;
			}
		}
	}
else
	{
	print "no command line options set\n";
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

if (!$DQLOGfile) {$DQLOGfile = "$PATHlogs/demographic_quotas.$year-$mon-$mday";}
if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### Grab system_settings values from the database
$stmtA = "SELECT demographic_quotas,UNIX_TIMESTAMP(NOW()) FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSdemographic_quotas =		$aryA[0];
	$SSdb_now =					$aryA[1];
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

$stmtA = "INSERT IGNORE into vicidial_campaign_stats_debug (campaign_id,server_ip) select campaign_id,'DEMO_QUOTAS' from vicidial_campaigns;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthA->finish();

### Grab container override details, if defined
if (length($CLIcontainer) > 0)
	{
	$dq_container_stmt="select container_entry from vicidial_settings_containers where container_id='$CLIcontainer' and container_entry!=''";
	if ($DBX) {print "$dq_container_stmt\n";}
	$dq_container_rslt=$dbhA->prepare($dq_container_stmt);
	$dq_container_rslt->execute();
	if ($dq_container_rslt->rows < 1) 
		{
		print "Container Override does not exist: $CLIcontainer\n";
		exit;
		}
	$dq_container_rslt->finish;
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

### check if demographic_quotas is enabled in the system
if ($SSdemographic_quotas < 1) 
	{
	print "Demographic Quotas disabled: $SSdemographic_quotas     exiting...\n";
	exit;
	}

$run_check=1;
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

### Count only process
if ($count_only)
	{
	if (length($CLIcampaign)>0)
		{
		$stmtA = "SELECT count(*) from vicidial_demographic_quotas_goals where campaign_id IN('$CLIcampaign');";
		}
	else
		{
		$stmtA = "SELECT count(*) from vicidial_demographic_quotas_goals;";
		}
	$dq_count_only=0;
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$dq_count_only = $aryA[0];
		}
	$sthA->finish();
	if ($DB) 
		{print "Demographic Quota Goals count: $dq_count_only|$stmtA|\n";}
	else 
		{print "Demographic Quota Goals count: $dq_count_only\n";}
	$event_string = "|DEMOGRAPHIC QUOTA GOALS COUNT: $dq_count_only|";
	&event_logger;

	exit;
	}


##### BEGIN check for active campaigns that need the hopper run for them
@campaign_id=@MT; 
$ANY_hopper_vlc_dup_check='N';

if (length($CLIcampaign)>1)
	{
	$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,dial_method,dial_statuses,call_count_limit,call_quota_lead_ranking,demographic_quotas,demographic_quotas_container,demographic_quotas_rerank,demographic_quotas_list_resets,dispo_call_url,demographic_quotas_last_rerank from vicidial_campaigns where campaign_id IN('$CLIcampaign') and active='Y' and demographic_quotas IN('ENABLED','COMPLETE');";
	}
else
	{
	$stmtA = "SELECT campaign_id,lead_order,hopper_level,auto_dial_level,local_call_time,dial_method,dial_statuses,call_count_limit,call_quota_lead_ranking,demographic_quotas,demographic_quotas_container,demographic_quotas_rerank,demographic_quotas_list_resets,dispo_call_url,demographic_quotas_last_rerank from vicidial_campaigns where active='Y' and demographic_quotas IN('ENABLED','COMPLETE');";
	}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$campaign_id[$rec_count] =						$aryA[0];
	$lead_order[$rec_count] =						$aryA[1];
	$hopper_level[$rec_count] =						$aryA[2];
	$auto_dial_level[$rec_count] =					$aryA[3];
	$local_call_time[$rec_count] =					$aryA[4];
	$dial_method[$rec_count] =						$aryA[5];
	$dial_statuses[$rec_count] =					$aryA[6];
	$call_count_limit[$rec_count] =					$aryA[7];
	$call_quota_lead_ranking[$rec_count] =			$aryA[8];
	$demographic_quotas[$rec_count] =				$aryA[9];
	if (length($CLIcontainer) < 1) 
		{$demographic_quotas_container[$rec_count] = $aryA[10];}
	else
		{$demographic_quotas_container[$rec_count] = "$CLIcontainer";}
	$demographic_quotas_rerank[$rec_count] =		$aryA[11];
	$demographic_quotas_list_resets[$rec_count] =	$aryA[12];
	$dispo_call_url[$rec_count] =					$aryA[13];
	$demographic_quotas_last_rerank[$rec_count] =	$aryA[14];
	$ranking_limit[$rec_count] =					999999;
	if ($demographic_quotas_rerank[$rec_count] =~ /MINUTE/) {$ranking_limit[$rec_count] = ($hopper_level[$rec_count] * 6);}
	if ($demographic_quotas_rerank[$rec_count] =~ /HOUR/) {$ranking_limit[$rec_count] = ($hopper_level[$rec_count] * 130);}
	if (length($CLIrerank_limit) > 0) 
		{
		if ($DBX) {print "Re-rank limit override:  |new: $CLIrerank_limit|old: $ranking_limit[$rec_count]|\n";}
		$ranking_limit[$rec_count] = $CLIrerank_limit;
		}

	### Find the number of agents
	$stmtB = "SELECT COUNT(*) FROM vicidial_live_agents WHERE ( (campaign_id='$campaign_id[$rec_count]') or (dial_campaign_id='$campaign_id[$rec_count]') ) and status IN ('READY','QUEUE','INCALL','CLOSER','PAUSED') and last_update_time >= '$VDL_tensec'";
	$sthB = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhA->errstr;
	@aryAgent = $sthB->fetchrow_array;
	$num_agents = $aryAgent[0];
	$sthB->finish();

	$rec_count++;
	}
$sthA->finish();
if ($DB) {print "CAMPAIGNS TO PROCESS DEMOGRAPHIC QUOTAS FOR:  $rec_count|$#campaign_id\n";}
##### END check for active campaigns that need the hopper run for them





##### LOOP THROUGH EACH CAMPAIGN AND PROCESS THE DEMOGRAPHIC QUOTAS #####
$i=0;
foreach(@campaign_id)
	{
	$secC[$i] = time();
	$RUNgoals=1;
	$finished_statusesSQL='';
	$field_valueSQL='';
	@demo_fields=@MT;
	@demo_fields_values=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	@demo_fields_goals=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	@demo_fields_leads_total=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	@demo_fields_leads_active=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	@demo_fields_quota_count=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	@demo_fields_quota_status=([('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11], [('') x 11]);
	$fields_count=0;
	$fields_values_count=0;
	$total_filled_count=0;
	$newly_filled_count=0;
	$quota_status_active_count=0;
	$longest_query_time=0;
	$newly_filledSQL='';
	$existing_filledSQL='';
	$active_demosSQL='';
	$ranked_leads=0;
	$rerank_output='';

	$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$demographic_quotas_container[$i]';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthSCrows=$sthA->rows;
	if ($DB) {print "Checking for $campaign_id[$i] Call Quota settings container: |$sthSCrows|$stmtA|\n";}
	$hopper_begin_output = "Campaign $campaign_id[$i] Demographic Quota Container check: $demographic_quotas_container[$i]\n";
	if ($sthSCrows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$TEMPcontainer_entry = $aryA[0];
		$TEMPcontainer_entry =~ s/\\//gi;
		if (length($TEMPcontainer_entry) > 5) 
			{
			@container_lines = split(/\n/,$TEMPcontainer_entry);
			$c=0;
			foreach(@container_lines)
				{
				$container_lines[$c] =~ s/;.*|\r|\t//gi;
				if (length($container_lines[$c]) > 10)
					{
					# define finished_statuses
					if ($container_lines[$c] =~ /^finished_statuses/i)
						{
						$finished_statuses = $container_lines[$c];
						$finished_statuses =~ s/finished_statuses=>|finished_statuses => //gi;
						if ( (length($finished_statuses) > 0) && (length($finished_statuses) <= 70) ) 
							{
							$TEMPfinished_statuses = $finished_statuses;
							$TEMPfinished_statuses =~ s/,/','/gi;
							$finished_statusesSQL = "'$TEMPfinished_statuses'";
							if ($DBX) {print "Demographic Quota DEBUG: finished_statuses defined - $finished_statuses|$finished_statusesSQL|\n";}
							$hopper_begin_output .= "finished_statuses defined - $finished_statuses|$finished_statusesSQL|\n";
							}
						}
					# define demo fields and values
					if ($container_lines[$c] =~ /^demo\d\d_field/i)
						{
						$demo_field_line = $container_lines[$c];
						$demo_field_line =~ s/^demo\d\d_field=>|^demo\d\d_field => //gi;
						$demo_field_line_number = $container_lines[$c];
						$demo_field_line_number =~ s/^demo|_field.*//gi;
						$demo_field_line_number = ($demo_field_line_number + 0);
						if ( (length($demo_field_line) > 2) && (length($demo_field_line_number) > 0) ) 
							{
							$fields_count++;
							$demo_fields[$demo_field_line_number] = $demo_field_line;
							if ($DBX) {print "Demographic Quota DEBUG: field number $demo_field_line_number defined - $demo_field_line|$fields_count|\n";}
							$hopper_begin_output .= "field $demo_value_line_number defined - $demo_field_line|$fields_count|\n";
							}
						}
					if ($container_lines[$c] =~ /^demo\d\d_value\d\d/i)
						{
						$demo_value_line = $container_lines[$c];
						$demo_value_line =~ s/^demo\d\d_value\d\d=>|^demo\d\d_value\d\d => //gi;
						@demo_value_lineARY = split(/,/,$demo_value_line);

						$demo_value_line_number = $container_lines[$c];
						$demo_value_line_number =~ s/^demo|_value.*//gi;
						$demo_value_line_number = ($demo_value_line_number + 0);
						$demo_value_line_valnum = $container_lines[$c];
						$demo_value_line_valnum =~ s/^demo\d\d_value| => .*|=>.*//gi;
						$demo_value_line_valnum = ($demo_value_line_valnum + 0);
						if ( (length($demo_value_line) > 2) && (length($demo_value_line_number) > 0) && (length($demo_value_line_valnum) > 0) ) 
							{
							$fields_values_count++;
							$demo_fields_values[$demo_value_line_number][$demo_value_line_valnum] = $demo_value_lineARY[0];
							$demo_fields_goals[$demo_value_line_number][$demo_value_line_valnum] = $demo_value_lineARY[1];
							if ($DBX) {print "Demographic Quota DEBUG: field number $demo_value_line_number value/goal defined - |$demo_value_lineARY[0]|$demo_value_lineARY[1]|$fields_values_count|\n";}
							$hopper_begin_output .= "field $demo_value_line_number value/goal defined - |$demo_value_lineARY[0]|$demo_value_lineARY[1]|$fields_values_count|\n";
							}
						}
					}
				$c++;
				}
			}
		else
			{
			$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
			$affected_rowsC = $dbhA->do($stmtB);

			if ($DB) {print "Campaign $campaign_id[$i] Demographic Quota Container is empty: $demographic_quotas_container[$i]|$affected_rowsC \n";}
			$hopper_begin_output .= "Campaign $campaign_id[$i] Demographic Quota Container is empty: $demographic_quotas_container[$i]|$affected_rowsC \n";
			$RUNgoals=0;
			}
		}
	else
		{
		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		if ($DB) {print "Campaign $campaign_id[$i] Demographic Quota Container does not exist: $demographic_quotas_container[$i]|$affected_rowsC \n";}
		$hopper_begin_output .= "Campaign $campaign_id[$i] Demographic Quota Container does not exist: $demographic_quotas_container[$i]|$affected_rowsC \n";
		$RUNgoals=0;
		}
	$sthA->finish();

	if ($DB) {print "DQ DEBUG: Fields defined:	$fields_count, Values defined: $fields_values_count\n";}

	if (length($finished_statusesSQL) < 3)
		{
		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		if ($DB) {print "Campaign $campaign_id[$i] Demographic Quota Container does not define statuses: $demographic_quotas_container[$i]|$finished_statusesSQL|$affected_rowsC \n";}
		$hopper_begin_output .= "Campaign $campaign_id[$i] Demographic Quota Container does not define statuses: $demographic_quotas_container[$i]|$finished_statusesSQL|$affected_rowsC \n";
		$RUNgoals=0;
		}

	if ( ($fields_count < 1) || ($fields_values_count < 1) )
		{
		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		if ($DB) {print "Campaign $campaign_id[$i] Demographic Quota Container is invalid: $demographic_quotas_container[$i]|$fields_count|$fields_values_count|$affected_rowsC \n";}
		$hopper_begin_output .= "Campaign $campaign_id[$i] Demographic Quota Container is invalid: $demographic_quotas_container[$i]|$fields_count|$fields_values_count|$affected_rowsC \n";
		$RUNgoals=0;
		}

	$stmtA = "SELECT list_id FROM vicidial_lists where ( ( (active='N') or ( (active='Y') and (expiration_date < \"$file_date\") ) ) and (campaign_id='$campaign_id[$i]') );";
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
	if (length($inactive_lists) > 3) {$inactive_lists =~ s/,$//gi;}
	if ($DB) {print "Inactive Lists:  $inactive_lists_count |$inactive_lists|\n";}

	$stmtA = "SELECT list_id FROM vicidial_lists where ( (active='Y') and (expiration_date >= \"$file_date\") and (campaign_id='$campaign_id[$i]') );";
	if ($DB) {print $stmtA;}
	$active_lists='';
	$active_lists_count=0;
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $active_lists_count)
		{
		@aryA = $sthA->fetchrow_array;
		$active_list = $aryA[0];
		$active_lists .= "'$active_list',";
		$active_lists_count++;
		}
	$sthA->finish();
	if (length($active_lists) > 3) {$active_lists =~ s/,$//gi;}
	if ($DB) {print "Active Lists:  $active_lists_count |$active_lists|\n";}


	### If Call Quota Lead Ranking is enabled on this campaign, set Demographic Quotas to INVALID
	if ( ($call_quota_lead_ranking[$i] !~ /^DISABLED$/) && (length($call_quota_lead_ranking[$i]) > 0) )
		{
		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		if ($DB) {print "Campaign $campaign_id[$i] Call Quota Lead Ranking enabled: $call_quota_lead_ranking[$i]|$affected_rowsC \n";}
		$hopper_begin_output .= "Campaign $campaign_id[$i] Call Quota Lead Ranking enabled: $call_quota_lead_ranking[$i]|$affected_rowsC \n";
		$RUNgoals=0;
		}


	##### BEGIN check if Dispo Call URL is set up properly in campaign #####
	if ($dispo_call_url[$i] =~ /^ALT$/) 
		{
		# looking up alternate dispo call url entry to confirm DQ_dispo.php is present and configured properly
		$DQ_dispo_url='';
		$stmtA = "SELECT url_address FROM vicidial_url_multi where (campaign_id='$campaign_id[$i]') and (entry_type='CAMPAIGN') and (active='Y') and (url_type='dispo');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DB) {print "$sthArows|$stmtA|\n";}
		$vum=0;
		while ($sthArows > $vum)
			{
			@aryA = $sthA->fetchrow_array;
			if ($aryA[0] =~ /DQ_dispo.php/)
				{$DQ_dispo_url = $aryA[0];}
			$vum++;
			}
		$sthA->finish();
		}
	else
		{$DQ_dispo_url = $dispo_call_url[$i];}

	$campCLItest = "campaign_id=$campaign_id[$i]";
	if ( ($DQ_dispo_url =~ /DQ_dispo.php/) && ($DQ_dispo_url =~ /dispo=--A--dispo--B--/) && ($DQ_dispo_url =~ /$campCLItest/i) ) 
		{
		if ($DB) {print "DQ_dispo Dispo Call URL is configured on this campaign: $campaign_id[$i]|$DQ_dispo_url| \n";}
		$hopper_begin_output .= "DQ_dispo Dispo Call URL is configured on this campaign: $campaign_id[$i]|$DQ_dispo_url| \n";
		}
	else
		{
		$Dmessage='';
		if ($DQ_dispo_url !~ /DQ_dispo.php/) {$Dmessage .= ", Missing 'DQ_dispo.php'";}
		if ($DQ_dispo_url !~ /dispo=--A--dispo--B--/) {$Dmessage .= ", Missing 'dispo=--A--dispo--B--'";}
		if ($DQ_dispo_url !~ /$campCLItest/) {$Dmessage .= ", Missing '$campCLItest'";}

		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='INVALID' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		if ($DB) {print "DQ_dispo Dispo Call URL is NOT configured on this campaign, disabling DQ: $campaign_id[$i]|$DQ_dispo_url|$affected_rowsC|$Dmessage| \n";}
		$hopper_begin_output .= "DQ_dispo Dispo Call URL is NOT configured on this campaign, disabling DQ: $campaign_id[$i]|$DQ_dispo_url|$affected_rowsC|$Dmessage| \n";
		$RUNgoals=0;
		}
	##### END check if Dispo Call URL is set up properly in campaign #####



	##### Go through the demographic quota goals and update settings and counts, and assign ranks to leads in active lists
	if ($RUNgoals > 0)
		{
		$vicidial_log = 'vicidial_log';

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

		$VCSdialable_leads[$i]=0;
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

		if ($DB) {print "\nStarting quota run for $campaign_id[$i] campaign- GMT: $local_call_time[$i]   HOPPER LEVEL: $hopper_level[$i]   RANKING LIMIT: $ranking_limit[$i] ($demographic_quotas_rerank[$i]) \n";}

		$hopper_begin_output .= "Starting quota run for $campaign_id[$i] campaign- GMT: $local_call_time[$i]   HOPPER LEVEL: $hopper_level[$i]   RANKING LIMIT: $ranking_limit[$i] ($demographic_quotas_rerank[$i]) \n";

		### Set all filled vicidial_demographic_quotas_goals records for this campaign to FPENDING status ahead of updates
		$stmtA = "UPDATE vicidial_demographic_quotas_goals SET quota_status='FPENDING' where campaign_id='$campaign_id[$i]' and quota_status='FILLED';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
		$hopper_begin_output .= "Setting all filled vicidial_demographic_quotas_goals records for this campaign to FPENDING status: $affected_rows\n";

		### Set all non-filled vicidial_demographic_quotas_goals records for this campaign to PENDING status ahead of updates
		$stmtA = "UPDATE vicidial_demographic_quotas_goals SET quota_status='PENDING' where campaign_id='$campaign_id[$i]' and quota_status NOT IN('FPENDING','ARCHIVE');";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
		$hopper_begin_output .= "Setting all non-filled vicidial_demographic_quotas_goals records for this campaign to PENDING status: $affected_rows\n";

		if (length($active_lists) > 2) 
			{$all_listsSQL = $active_lists;}
		if (length($inactive_lists) > 2) 
			{
			if (length($all_listsSQL) > 2) {$all_listsSQL .= ",";}
			$all_listsSQL .= "$inactive_lists";
			}

		$g=1;
		while ($g <= 10) 
			{
			$v=1;
			while ($v <= 10) 
				{
				if ( (length($demo_fields_values[$g][$v]) > 0) && (length($demo_fields_goals[$g][$v]) > 0) ) 
					{
					if ($DBX) {print "DQ DEBUG: Field $g Value $v: |val: $demo_fields_values[$g][$v]|   |goal: $demo_fields_goals[$g][$v]|\n";}

					### BEGIN - GATHER STATS FROM THE vicidial_campaign_stats TABLE ###
					$demo_fields_leads_total[$g][$v]=0;
					$demo_fields_leads_active[$g][$v]=0;
					$demo_fields_quota_count[$g][$v]=0;
					$demo_fields_quota_status[$g][$v]='ACTIVE';
					if (length($active_lists) > 2) 
						{
						$temp_start_time = time();
						$stmtA = "SELECT count(*) from vicidial_list where list_id IN($active_lists) and $demo_fields[$g]=\"$demo_fields_values[$g][$v]\";";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$demo_fields_leads_active[$g][$v] = $aryA[0];
							}
						$sthA->finish();
						$temp_end_time = time();
						$temp_query_time = ($temp_end_time - $temp_start_time);
						if ($temp_query_time > $longest_query_time) 
							{$longest_query_time = $temp_query_time;}
						}
					if (length($inactive_lists) > 2) 
						{
						$temp_start_time = time();
						$stmtA = "SELECT count(*) from vicidial_list where list_id IN($inactive_lists) and $demo_fields[$g]=\"$demo_fields_values[$g][$v]\";";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$demo_fields_leads_total[$g][$v] = ($demo_fields_leads_active[$g][$v] + $aryA[0]);
							}
						else
							{$demo_fields_leads_total[$g][$v] = $demo_fields_leads_active[$g][$v];}
						$sthA->finish();

						$temp_end_time = time();
						$temp_query_time = ($temp_end_time - $temp_start_time);
						if ($temp_query_time > $longest_query_time) 
							{$longest_query_time = $temp_query_time;}
						}
					else
						{$demo_fields_leads_total[$g][$v] = $demo_fields_leads_active[$g][$v];}

					if (length($all_listsSQL) > 2) 
						{
						$temp_start_time = time();
						$stmtA = "SELECT count(*) from vicidial_list where list_id IN($all_listsSQL) and $demo_fields[$g]=\"$demo_fields_values[$g][$v]\" and status IN($finished_statusesSQL);";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$demo_fields_quota_count[$g][$v] = $aryA[0];
							}
						$sthA->finish();
						$temp_end_time = time();
						$temp_query_time = ($temp_end_time - $temp_start_time);
						if ($temp_query_time > $longest_query_time) 
							{$longest_query_time = $temp_query_time;}
						}
					
					if ($demo_fields_quota_count[$g][$v] >= $demo_fields_goals[$g][$v]) 
						{
						$total_filled_count++;
						$demo_fields_quota_status[$g][$v]='FILLED';
						$quota_status_already_filled=0;
						$stmtA = "SELECT count(*) from vicidial_demographic_quotas_goals where campaign_id='$campaign_id[$i]' and demographic_quotas_container='$demographic_quotas_container[$i]' and quota_field='$demo_fields[$g]' and quota_field_order='$g' and quota_value=\"$demo_fields_values[$g][$v]\" and quota_value_order='$v' and quota_status='FPENDING';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$quota_status_already_filled = $aryA[0];
							}
						$sthA->finish();
						if ($quota_status_already_filled < 1) 
							{
							$newly_filled_count++;
							$demo_fields_quota_status[$g][$v]='NEW_FILLED';
							if (length($newly_filledSQL) < 1) {$newly_filledSQL .= "and ( ";}
							else {$newly_filledSQL .= " or ";}
							$newly_filledSQL .= "($demo_fields[$g]=\"$demo_fields_values[$g][$v]\")";
							}
						else
							{
							if (length($existing_filledSQL) < 1) {$existing_filledSQL .= "and ( ";}
							else {$existing_filledSQL .= " or ";}
							$existing_filledSQL .= "($demo_fields[$g]=\"$demo_fields_values[$g][$v]\")";
							}
						}
					else
						{
						$active_demosSQL .= " and $demo_fields[$g]='$demo_fields_values[$g][$v]'";
						}

					### Insert/Update the vicidial_demographic_quotas_goals record for this field/value
					$stmtA = "INSERT IGNORE INTO vicidial_demographic_quotas_goals SET campaign_id='$campaign_id[$i]',demographic_quotas_container='$demographic_quotas_container[$i]',quota_field='$demo_fields[$g]',quota_field_order='$g',quota_value=\"$demo_fields_values[$g][$v]\",quota_value_order='$v',quota_goal='$demo_fields_goals[$g][$v]',quota_count='$demo_fields_quota_count[$g][$v]',quota_leads_total='$demo_fields_leads_total[$g][$v]',quota_leads_active='$demo_fields_leads_active[$g][$v]',quota_status='$demo_fields_quota_status[$g][$v]',quota_modify_date=NOW(),last_call_date='2000-01-01 00:00:00' ON DUPLICATE KEY UPDATE quota_goal='$demo_fields_goals[$g][$v]',quota_leads_total='$demo_fields_leads_total[$g][$v]',quota_leads_active='$demo_fields_leads_active[$g][$v]',quota_status='$demo_fields_quota_status[$g][$v]',quota_modify_date=NOW(),quota_count='$demo_fields_quota_count[$g][$v]';";
					$affected_rows = $dbhA->do($stmtA);
					if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
					}
				$v++;
				}
			$g++;
			}

		$stmtA = "SELECT count(*) from vicidial_demographic_quotas_goals where campaign_id='$campaign_id[$i]' and quota_status='ACTIVE';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$quota_status_active_count = $aryA[0];
			}
		$sthA->finish();

		$hopper_begin_output .= "     quota fields: $fields_count \n";
		$hopper_begin_output .= "     quota values: $fields_values_count \n";
		$hopper_begin_output .= "     total filled: $total_filled_count     newly filled: $newly_filled_count \n";
		$hopper_begin_output .= "     total active: $quota_status_active_count \n";

		### archive inactive vicidial_demographic_quotas_goals records for this campaign
		$stmtA = "UPDATE vicidial_demographic_quotas_goals SET quota_status='ARCHIVE' where campaign_id='$campaign_id[$i]' and quota_status IN('FPENDING','PENDING');";
		$affected_rows = $dbhA->do($stmtA);
		if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
		$hopper_begin_output .= "Archiving inactive vicidial_demographic_quotas_goals records for this campaign: $affected_rows\n";

		### Update all newly filled demos in vicidial_list to rank = -9999
		if (length($newly_filledSQL) > 10) 
			{
			$newly_filledSQL .= " )";
			$temp_start_time = time();
			$stmtA = "UPDATE vicidial_list SET rank='-9999' where list_id IN($all_listsSQL) $newly_filledSQL;";
			$affected_rows = $dbhA->do($stmtA);
			$temp_end_time = time();
			$temp_query_time = ($temp_end_time - $temp_start_time);
			if ($temp_query_time > $longest_query_time) 
				{$longest_query_time = $temp_query_time;}
			if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
			$hopper_begin_output .= "Newly filled demographics leads set to low rank: $affected_rows\n";

			### set NEW_FILLED vicidial_demographic_quotas_goals records to FILLED for this campaign
			$stmtA = "UPDATE vicidial_demographic_quotas_goals SET quota_status='FILLED' where campaign_id='$campaign_id[$i]' and quota_status IN('NEW_FILLED');";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
			$hopper_begin_output .= "set NEW_FILLED vicidial_demographic_quotas_goals records to FILLED for this campaign: $affected_rows\n";
			}

		if ($DBX) {print "DQ DEBUG:  Re-Rank tests: |$force_rerank|$newly_filled_count|$demographic_quotas_rerank[$i]|$min|\n";}

		if ( ($force_rerank > 0) || ($newly_filled_count > 0) || ($demographic_quotas_rerank[$i] =~ /MINUTE|NOW/) || ( ($demographic_quotas_rerank[$i] =~ /HOUR/) && ($min < 1) ) || ($demographic_quotas_last_rerank[$i] eq '2000-01-01 00:00:00') ) 
			{
			$rerank_output .= "STARTING LEAD RE-RANKING:  $demographic_quotas_rerank[$i] $now_date\n";

			### Update all existing filled demos in vicidial_list to rank = -9999
			if (length($existing_filledSQL) > 10) 
				{
				$existing_filledSQL .= " )";
				$temp_start_time = time();
				$stmtA = "UPDATE vicidial_list SET rank='-9999' where list_id IN($all_listsSQL) $existing_filledSQL;";
				$affected_rows = $dbhA->do($stmtA);
				$temp_end_time = time();
				$temp_query_time = ($temp_end_time - $temp_start_time);
				if ($temp_query_time > $longest_query_time) 
					{$longest_query_time = $temp_query_time;}
				if ($DBX) {print "DQ DEBUG:  $affected_rows|$stmtA|\n";}
				$hopper_begin_output .= "Existing filled demographics leads set to low rank: $affected_rows\n";
				$rerank_output .= "Existing filled demographics leads set to low rank: $affected_rows\n";
				}

			##### BEGIN loop through the ACTIVE vicidial_demographic_quotas_goals and update the vicidial_list ranks for the leads in this campaign
			$stmtA = "SELECT quota_field,quota_field_order,quota_value,quota_value_order,quota_goal,quota_count,quota_leads_total,quota_leads_active from vicidial_demographic_quotas_goals where campaign_id='$campaign_id[$i]' and quota_status='ACTIVE' order by quota_field_order,quota_value_order;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsT=$sthA->rows;
			$qt=0;
			$field_levels='|';
			$field_val_levels='|';
			$field_levels_ct=0;
			while ($sthArowsT > $qt)
				{
				@aryA = $sthA->fetchrow_array;
				$Tquota_field[$qt] =		$aryA[0];
				$Tquota_field_order[$qt] =	$aryA[1];
				$Tquota_value[$qt] =		$aryA[2];
				$Tquota_value_order[$qt] =	$aryA[3];
				$Tquota_goal[$qt] =			$aryA[4];
				$Tquota_count[$qt] =		$aryA[5];
				$Tquota_leads_total[$qt] =	$aryA[6];
				$Tquota_leads_active[$qt] = $aryA[7];
				if ($qt < 1) 
					{
					$field_levels.="$Tquota_field_order[$qt]|";
					$field_val_levels.="$Tquota_field_order[$qt]x$Tquota_value_order[$qt]|";
					$field_levels_ct++;
					}
				else
					{
					if ($field_levels !~ /\|$Tquota_field_order[$qt]\|/) 
						{
						$field_levels.="$Tquota_field_order[$qt]|";
						$field_val_levels.="$Tquota_field_order[$qt]x$Tquota_value_order[$qt]|";
						$field_levels_ct++;
						}
					}
				$rerank_output .= "     Re-Rank Debug - Active goal $qt: $Tquota_field[$qt]|$Tquota_value[$qt]|$Tquota_goal[$qt]|$Tquota_count[$qt]|$field_levels_ct|\n";
				$qt++;
				}
			$sthA->finish();

			$rs=0;
			$end_of_queries=0;
			$last_firstSQL='';
			@rankingSQL_ary = @MT;

			# if only one field (level) is active, then don't include next level in SQL queries
			if ($field_levels_ct <= 1)
				{
				$rerank_output .= "     Re-Rank Debug - rank SQL only 1 field level: |$field_levels_ct|\n";
				$qt=0;
				$first_sort_field_rank=0;
				$first_sort_value_rank=0;
				$temp_firstSQL='';

				while ($sthArowsT > $qt)
					{
					if ($Tquota_field_order[$qt] > 0) 
						{
						$rankingSQL_ary[$rs] = "and ($Tquota_field[$qt]=\"$Tquota_value[$qt]\")";
						$rerank_output .= "     Re-Rank Debug - rank SQL $rs: |$rankingSQL_ary[$rs]|\n";
						$rs++;
						}
					$qt++;
					}
				}
			else
				{
				# More than one field (level) is active, then include next level in SQL queries
				$rerank_output .= "     Re-Rank Debug - rank SQL multiple field levels: |$field_levels_ct|\n";
				while ($end_of_queries < 10) 
					{
					$qt=0;
					$first_sort_field_rank=0;
					$first_sort_value_rank=0;
					$temp_firstSQL='';
					$field_val_rs='|';

					while ($sthArowsT > $qt)
						{
						if ($first_sort_field_rank < 1) 
							{
							if ($Tquota_field_order[$qt] > 0) 
								{
								$first_sort_field_rank = $Tquota_field_order[$qt];
								$first_sort_value_rank = $Tquota_value_order[$qt];
								$temp_firstSQL = "and ($Tquota_field[$qt]=\"$Tquota_value[$qt]\")";
								$Tquota_field_order[$qt]=0;
								}
							}
						else
							{
							if ($Tquota_field_order[$qt] > $first_sort_field_rank) 
								{
								$rankingSQL_ary[$rs] = "$temp_firstSQL and ($Tquota_field[$qt]=\"$Tquota_value[$qt]\")";
								$rerank_output .= "     Re-Rank Debug - rank SQL $rs: |$rankingSQL_ary[$rs]|\n";
								$rs++;
								}
							}
						$qt++;
						}

					$end_of_queries++;
					}
				}

			$temp_rank=3000;
			$to=0;
			while ( ($rs > $to) && ($ranked_leads < $ranking_limit[$i]) )
				{
				$temp_rank = ($temp_rank - 1);
				print "$to $rankingSQL_ary[$to] \n";

				$temp_start_time = time();
				$stmtA = "UPDATE vicidial_list SET rank='$temp_rank' where list_id IN($all_listsSQL) and (rank < $temp_rank) $rankingSQL_ary[$to];";
				$affected_rows = $dbhA->do($stmtA);
				$ranked_leads = ($ranked_leads + $affected_rows);
				$temp_end_time = time();
				$temp_query_time = ($temp_end_time - $temp_start_time);
				if ($temp_query_time > $longest_query_time) 
					{$longest_query_time = $temp_query_time;}
				if ($DBX) {print "DQ DEBUG:  $affected_rows|$ranked_leads|$stmtA|\n";}
				$hopper_begin_output .= "ORDER LEVEL $to: leads set to rank $temp_rank: $affected_rows |$ranked_leads|$rankingSQL_ary[$to]|\n";
				$rerank_output .= "ORDER LEVEL $to: leads set to rank $temp_rank: $affected_rows |$ranked_leads|$rankingSQL_ary[$to]|\n";

				$to++;
				}
			if ($ranked_leads >= $ranking_limit[$i]) 
				{
				if ($DBX) {print "DQ DEBUG:  REACHED LEAD RANK LIMIT FOR $demographic_quotas_rerank[$i]: ($ranked_leads >= $ranking_limit[$i]) \n";}
				$hopper_begin_output .= "     REACHED LEAD RANK LIMIT FOR $demographic_quotas_rerank[$i]: ($ranked_leads >= $ranking_limit[$i]) \n";
				$rerank_output .= "     REACHED LEAD RANK LIMIT FOR $demographic_quotas_rerank[$i]: ($ranked_leads >= $ranking_limit[$i]) \n";
				}
			##### END loop through the ACTIVE vicidial_demographic_quotas_goals and update the vicidial_list ranks for the leads in this campaign

			$campaign_updated=0;
			if ($demographic_quotas_rerank[$i] =~ /NOW_HOUR/)
				{
				# set demographic_quotas_rerank back to HOUR if set to NOW_HOUR
				$demographic_quotas_rerank[$i]='HOUR';
				$stmtA = "UPDATE vicidial_campaigns SET demographic_quotas_rerank='HOUR',demographic_quotas_last_rerank=NOW() where campaign_id='$campaign_id[$i]' and demographic_quotas_rerank='NOW_HOUR';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DQ DEBUG:  Re-Rank set back to HOUR: |$affected_rows|$stmtA| \n";}
				$campaign_updated++;
				}
			if ($demographic_quotas_rerank[$i] =~ /NOW/)
				{
				# set demographic_quotas_rerank back to NO if set to NOW
				$demographic_quotas_rerank[$i]='NO';
				$stmtA = "UPDATE vicidial_campaigns SET demographic_quotas_rerank='NO',demographic_quotas_last_rerank=NOW() where campaign_id='$campaign_id[$i]' and demographic_quotas_rerank='NOW';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DQ DEBUG:  Re-Rank set back to N: |$affected_rows|$stmtA| \n";}
				$campaign_updated++;
				}
			if ($campaign_updated < 1)
				{
				# set demographic_quotas_rerank back to NO if set to NOW
				$demographic_quotas_rerank[$i]='NO';
				$stmtA = "UPDATE vicidial_campaigns SET demographic_quotas_last_rerank=NOW() where campaign_id='$campaign_id[$i]';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "DQ DEBUG:  Last Re-Rank date updated: |$affected_rows|$stmtA| \n";}
				$campaign_updated++;
				}
			}
		}



	##### BEGIN auto-list-reset process #####
	if ( ($demographic_quotas_list_resets[$i] eq 'AUTO') && ($RUNgoals > 0) )
		{
		if ($DBX) {print "DQ List AUTO Resets enabled, checking hopper count:  $demographic_quotas_list_resets[$i] \n";}
		$hopper_begin_output .= "DQ List AUTO Resets enabled, checking hopper count:  $demographic_quotas_list_resets[$i] \n";
		$hopper_leads=0;
		$stmtA="SELECT count(*) FROM vicidial_hopper where campaign_id='$campaign_id[$i]' and status IN('READY','RHOLD','RQUEUE');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$hopper_leads = $aryA[0];
			}
		$sthA->finish();

		if ($hopper_leads < 1) 
			{
			if ($DBX) {print "DQ List AUTO Resets enabled, hopper empty, checking for dialable leads next:  $hopper_leads \n";}
			$hopper_begin_output .= "DQ List AUTO Resets enabled, hopper empty, checking for dialable leads next:  $hopper_leads \n";
			$dialable_leads=0;
			$stmtA = "SELECT dialable_leads from vicidial_campaign_stats where campaign_id='$campaign_id[$i]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$dialable_leads = $aryA[0];
				}
			$sthA->finish();

			if ($dialable_leads < 1) 
				{
				if ($DBX) {print "DQ List AUTO Resets enabled, no dialable leads, checking for active lists next:  $dialable_leads \n";}
				$hopper_begin_output .= "DQ List AUTO Resets enabled, no dialable leads, checking for active lists next:  $dialable_leads \n";

				if (length($active_lists) > 2)
					{
					if ($DBX) {print "DQ List AUTO Resets enabled, active lists set, checking for list resets in last 5 minutes next:  $active_lists \n";}
					$hopper_begin_output .= "DQ List AUTO Resets enabled, active lists set, checking for list resets in last 5 minutes next:  $active_lists \n";
					$recent_resets=0;
					$temp_start_time = time();
					$stmtA = "SELECT count(*) from vicidial_admin_log where event_section='LISTS' and event_type='RESET' and record_id IN($all_listsSQL) and event_date > NOW()-INTERVAL 5 MINUTE;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$recent_resets = $aryA[0];
						}
					$sthA->finish();
					$temp_end_time = time();
					$temp_query_time = ($temp_end_time - $temp_start_time);
					if ($temp_query_time > $longest_query_time) 
						{$longest_query_time = $temp_query_time;}
				
					if ($recent_resets < 1) 
						{
						if ($DBX) {print "DQ List AUTO Resets enabled, no recent resets, checking for dialable leads within timezones next:  $recent_resets  $temp_query_time sec \n";}
						$hopper_begin_output .= "DQ List AUTO Resets enabled, no recent resets, checking for dialable leads within timezones next:  $recent_resets  $temp_query_time sec \n";

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
						$all_gmtSQL[$i] = "(gmt_offset_now IN($default_gmt))";
					#	$del_gmtSQL[$i] = "(gmt_offset_now NOT IN($default_gmt)";

						##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###

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

						$CCLsql[$i]='';
						if ($call_count_limit[$i] > 0)
							{
							$CCLsql[$i] = "and (called_count < $call_count_limit[$i])";
							if ($DB) {print "     total call count limit $call_count_limit[$i] defined for $campaign_id[$i]\n";}
							if ($DBX) {print "     |$CCLsql[$i]|\n";}
							$hopper_begin_output .= "     total call count limit $call_count_limit[$i] defined for $campaign_id[$i] \n";
							}

						$time_callable_leads=0;
						$temp_start_time = time();
						$stmtA = "SELECT count(*) from vicidial_list where list_id IN($active_lists) and status IN($STATUSsql[$i]) and ($all_gmtSQL[$i]) and (rank != '-9999') $CCLsql[$i];";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$time_callable_leads = $aryA[0];
							}
						$sthA->finish();
						$temp_end_time = time();
						$temp_query_time = ($temp_end_time - $temp_start_time);
						if ($temp_query_time > $longest_query_time) 
							{$longest_query_time = $temp_query_time;}
						$hopper_begin_output .= "     DQ DEBUG: $time_callable_leads|$stmtA| $temp_query_time sec \n";

						if ($time_callable_leads > 0)
							{
							if ($DBX) {print "DQ List AUTO Resets enabled, time-callable leads available, resetting lists next:  $time_callable_leads \n";}
							$hopper_begin_output .= "DQ List AUTO Resets enabled, time-callable leads available, resetting lists next:  $time_callable_leads \n";

							# gather active lists in this campaign that can still be reset
							$stmtA = "SELECT list_id FROM vicidial_lists where ( (active='Y') and (expiration_date >= \"$file_date\") ) and (campaign_id='$campaign_id[$i]') and ( (resets_today < daily_reset_limit) or (daily_reset_limit < 0) ) and (resets_today < 4);";
							$resetable_lists[0]='';
							$resetable_lists_count=0;
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($DB) {print "$sthArows|$stmtA|\n";}
							while ($sthArows > $resetable_lists_count)
								{
								@aryA = $sthA->fetchrow_array;
								$resetable_lists[$resetable_lists_count] = $aryA[0];
								$resetable_lists_count++;
								}
							$sthA->finish();

							if ($resetable_lists_count > 0) 
								{
								if ($DB) {print "DQ List AUTO Reset, checking each list for resets within 3 hours:  $resetable_lists_count \n";}
								$hopper_begin_output .= "DQ List AUTO Reset, checking each list for resets within 3 hours:  $resetable_lists_count \n";
								$tlc=0;
								$lists_reset=0;
								while ($tlc < $resetable_lists_count)
									{
									$reset_3_hours_ago=0;
									$temp_start_time = time();
									$stmtA = "SELECT count(*) from vicidial_admin_log where event_section='LISTS' and event_type='RESET' and record_id='$resetable_lists[$tlc]' and event_date > NOW()-INTERVAL 3 HOUR;";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
									if ($sthArows > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$reset_3_hours_ago = $aryA[0];
										}
									$sthA->finish();
									$temp_end_time = time();
									$temp_query_time = ($temp_end_time - $temp_start_time);
									if ($temp_query_time > $longest_query_time) 
										{$longest_query_time = $temp_query_time;}

									$hopper_begin_output .= "     DQ DEBUG - list $resetable_lists[$tlc]: $reset_3_hours_ago|$stmtA|\n";

									if ($reset_3_hours_ago < 1) 
										{
										if ($DBX) {print "DQ List AUTO Reset, no recent reset for list $resetable_lists[$tlc], resetting this list: $reset_3_hours_ago $temp_query_time \n";}
										$hopper_begin_output .= "DQ List AUTO Reset, no recent reset for list $resetable_lists[$tlc], resetting this list: $reset_3_hours_ago $temp_query_time \n";

										$stmtA="UPDATE vicidial_lists set resets_today=(resets_today + 1) where list_id='$resetable_lists[$tlc]';";
										$affected_rows = $dbhA->do($stmtA);

										$temp_start_time = time();
										$stmtB="UPDATE vicidial_list set called_since_last_reset='N' where list_id='$resetable_lists[$tlc]';";
										$affected_rowsB = $dbhA->do($stmtB);
										$temp_end_time = time();
										$temp_query_time = ($temp_end_time - $temp_start_time);
										if ($temp_query_time > $longest_query_time) 
											{$longest_query_time = $temp_query_time;}

										$SQL_log = "$stmtA|$stmtB|";
										$SQL_log =~ s/;|\\|\'|\"//gi;

										if ($DB) {print "List Reset DONE: $resetable_lists[$tlc]($affected_rows|$affected_rowsB)  $temp_query_time sec \n";}

										$stmtA="INSERT INTO vicidial_admin_log set event_date='$now_date', user='VDAD', ip_address='1.1.1.1', event_section='LISTS', event_type='RESET', record_id='$resetable_lists[$tlc]', event_code='ADMIN DQ RESET LIST', event_sql=\"$SQL_log\", event_notes='$affected_rowsB leads reset, resets count updated $affected_rows';";
										$Iaffected_rows = $dbhA->do($stmtA);
										if ($DB) {print "FINISHED:   $affected_rows|$Iaffected_rows|$stmtA";}

										$lists_reset++;
										}
									$tlc++;
									}
								if ( ($lists_reset > 0) && ($demographic_quotas_rerank[$i] !~ /MINUTE|NOW/) )
									{
									$temp_rerank='NOW';
									if ($demographic_quotas_rerank[$i] =~ /HOUR/) {$temp_rerank='NOW_HOUR';}
									$stmtA = "UPDATE vicidial_campaigns SET demographic_quotas_rerank='$temp_rerank' where campaign_id='$campaign_id[$i]';";
									$affected_rows = $dbhA->do($stmtA);
									if ($DBX) {print "DQ DEBUG:  Force Re-Rank set for campaign: |$affected_rows|$stmtA| \n";}
									$hopper_begin_output .= "DQ DEBUG:  Force Re-Rank set for campaign: |$affected_rows|$stmtA| \n";
									}
								if ($DB) {print "DQ Lists Reset for campaign: $lists_reset|$campaign_id[$i] \n";}
								$hopper_begin_output .= "DQ Lists Reset for campaign: $lists_reset|$campaign_id[$i] \n";
								}
							else
								{
								if ($DB) {print "DQ List AUTO Reset, no resettable lists available:  $resetable_lists_count \n";}
								$hopper_begin_output .= "DQ List AUTO Reset, no resettable lists available:  $resetable_lists_count \n";
								}
							}
						else
							{
							if ($DBX) {print "DQ List AUTO Resets enabled, time-callable leads not available: $time_callable_leads \n";}
							$hopper_begin_output .= "DQ List AUTO Resets enabled, time-callable leads not available: $time_callable_leads \n";
							}
						}
					else
						{
						if ($DBX) {print "DQ List AUTO Resets enabled, recent list resets:  $recent_resets  $temp_query_time sec \n";}
						$hopper_begin_output .= "DQ List AUTO Resets enabled, recent list resets:  $recent_resets  $temp_query_time sec \n";
						}
					}
				else
					{
					if ($DBX) {print "DQ List AUTO Resets enabled, no active lists present: $active_lists \n";}
					$hopper_begin_output .= "DQ List AUTO Resets enabled, no active lists present: $active_lists \n";
					}
				}
			else
				{
				if ($DBX) {print "DQ List AUTO Resets enabled, dialable leads present: $dialable_leads \n";}
				$hopper_begin_output .= "DQ List AUTO Resets enabled, dialable leads present: $dialable_leads \n";
				}
			}
		else
			{
			if ($DBX) {print "DQ List AUTO Resets enabled, hopper has leads: $hopper_leads \n";}
			$hopper_begin_output .= "DQ List AUTO Resets enabled, hopper has leads: $hopper_leads \n";
			}
		}
	##### END auto-list-reset process #####



	##### BEGIN if leads have been ranked, wipe the hopper and force a new hopper load now for this campaign
	if ($ranked_leads > 0) 
		{
		$hopper_leads=0;
		$stmtA="SELECT count(*) FROM vicidial_hopper where campaign_id='$campaign_id[$i]' and status IN('READY','RHOLD','RQUEUE');";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$hopper_leads = $aryA[0];
			}
		$sthA->finish();

		if ($hopper_leads > 0) 
			{
			$run_hopper_loop=0;
			while ($run_hopper_loop < 60) 
				{
				my $grepout = `/bin/ps ax | grep AST_VDhopper.pl | grep -v grep | grep -v '/bin/sh'`;
				my $grepnum=0;
				$grepnum++ while ($grepout =~ m/\n/g);
				if ($grepnum > 0) 
					{
					if ($DB) {print "hopper running, waiting $run_hopper_loop";}
					$hopper_begin_output .= "hopper running, waiting $run_hopper_loop";
					sleep(1);
					}
				else
					{
					$stmtB="DELETE FROM vicidial_hopper WHERE campaign_id='$campaign_id[$i]';";
					$affected_rowsB = $dbhA->do($stmtB);
					if ($DB) {print "\nHopper Reset DONE, launching hopper in screen next: $affected_rowsB  $campaign_id[$i] \n";}
					$hopper_begin_output .= "\nHopper Reset DONE, launching hopper in screen next: $affected_rowsB  $campaign_id[$i] \n";

					# gather hopper run flags
					$hopper_flags='';
					$stmtA="SELECT container_entry FROM vicidial_settings_containers where container_id='HOPPER_CLI_FLAGS';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($DBX) {print "DQ DEBUG: |$sthArows|$stmtA|\n";}
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$hopper_flags = $aryA[0];
						$hopper_flags =~ s/[^a-zA-Z0-9 _-]//gi;
						}
					$sthA->finish();

					$hopper_command = "/usr/bin/screen -d -m -S DQ$reset_test$campaign_id[$i] $PATHhome/AST_VDhopper.pl --debugX --campaign=$campaign_id[$i] $hopper_flags ";
					`$hopper_command`;
					if ($DB) {print "Hopper run triggered: |$hopper_command|\n";}
					$hopper_begin_output .= "Hopper run triggered: |$hopper_command|\n";
					$run_hopper_loop=61;
					}
				$run_hopper_loop++;
				}
			}
		}
	##### END if leads have been ranked, wipe the hopper and force a new hopper load now for this campaign


#	$hopper_begin_output .= "     DQ DEBUG, COMPLETE check: |$newly_filled_count|$demographic_quotas[$i]|$total_filled_count|$quota_status_active_count|\n";

	##### BEGIN set all leads in active campaign lists to rank=-9999 if all quota goals newly filled
	if ( ( ($newly_filled_count > 0) || ( ($demographic_quotas[$i] =~ /ENABLED/) && ($total_filled_count > 0) ) ) && ($quota_status_active_count < 1) ) 
		{
		$stmtB = "UPDATE vicidial_list SET rank='-9999',called_since_last_reset='Y' where list_id IN($active_lists) and (rank != '-9999');";
		$affected_rowsB = $dbhA->do($stmtB);

		$stmtB = "UPDATE vicidial_campaigns SET demographic_quotas='COMPLETE' where campaign_id='$campaign_id[$i]';";
		$affected_rowsC = $dbhA->do($stmtB);

		$stmtB = "DELETE FROM vicidial_hopper WHERE campaign_id='$campaign_id[$i]';";
		$affected_rowsH = $dbhA->do($stmtB);

		if ($DB) {print "Quota Goals Complete, all active leads set to called with -9999 rank: $affected_rowsB $affected_rowsC $campaign_id[$i] \n";}
		$hopper_begin_output .= "Quota Goals Complete, all active leads set to called with -9999 rank: $affected_rowsB $affected_rowsC $affected_rowsH $campaign_id[$i] \n";
		}
	##### END set all leads in active campaign lists to rank=-9999 if all quota goals newly filled


	$secCF[$i] = time();
	$camp_run_time = ($secCF[$i] - $secC[$i]);
	$hopper_begin_output .= "DQ Campaign run time: $camp_run_time seconds     longest query time: $longest_query_time \n";

	# update debug output
	$hopper_begin_output =~ s/"/'/gi;
	$rerank_output =~ s/"/'/gi;
	$rerank_outputSQL='';
	if (length($rerank_output) > 10) 
		{$rerank_outputSQL = ",adapt_output=\"$rerank_output\"";}
	$stmtA = "UPDATE vicidial_campaign_stats_debug SET entry_time='$now_date',debug_output=\"$hopper_begin_output\"$rerank_outputSQL where campaign_id='$campaign_id[$i]' and server_ip='DEMO_QUOTAS';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DBX) {print "vicidial_campaign_stats_debug UPDATE: $affected_rows|$stmtA|\n";}

	$event_string = $hopper_begin_output;
	&event_logger;

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
exit;


##### SUBROUTINES #####
sub event_logger
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$DQLOGfile")
				|| die "Can't open $DQLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}
