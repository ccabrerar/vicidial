#!/usr/bin/perl
#
# AST_VDcall_quotas.pl version 2.14
#
# DESCRIPTION:
# Updates the vicidial_list table for the call quota lead ranking system 
#
# SUMMARY:
# For VICIDIAL outbound dialing, this program must be in the crontab on only one
# server, running once an hour during operating hours.
#
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 190705-1353 - First build
# 190722-1005 - Added more log-audit code and log-fixing
#

# constants
$build = '190705-1353';
$DB=0;  # Debug flag, set to 0 for no debug messages. Can be overriden with CLI --debug flag
$US='__';
$MT[0]='';

# options
$VLforce_index = 'FORCE INDEX(list_id)'; # to disable, set to ''
$force_process_all_leads=0;
$populate_call_quota_logs=0;
$no_insert_call_quota_logs=0;

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
$VDL_hour_epoch = ($secX - (60 * 60));
($Vsec,$Vmin,$Vhour,$Vmday,$Vmon,$Vyear,$Vwday,$Vyday,$Visdst) = localtime($VDL_hour_epoch);
$Vyear = ($Vyear + 1900);
$Vmon++;
if ($Vmon < 10) {$Vmon = "0$Vmon";}
if ($Vmday < 10) {$Vmday = "0$Vmday";}
if ($Vhour < 10) {$Vhour = "0$Vhour";}
if ($Vmin < 10) {$Vmin = "0$Vmin";}
if ($Vsec < 10) {$Vsec = "0$Vsec";}
$VDL_hour = "$Vyear-$Vmon-$Vmday $Vhour:$Vmin:$Vsec";

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
		print "  [--help] = this screen\n";
		print "  [--version] = print version of this script, then exit\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--campaign=XXX] = run for campaign XXX only(or more campaigns if separated by triple dash ---)\n";
		print "  [--force-process-all-leads] = run call quota ranking on all leads, not just leads modified since last run\n";
		print "  [--populate-call-quota-logs] = run through the last 7 days of call logs for a campaign and add/update missing call quota log entries, MUST SET CAMPAIGN!\n";
		print "  [--no-insert-call-quota-logs] = if running the populate process, do not insert new log records\n";
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
		if ($args =~ /--populate-call-quota-logs/i)
			{
			$populate_call_quota_logs=1;
			print "\n----- POPULATE CALL QUOTA LOGS: $populate_call_quota_logs -----\n\n";
			}
		if ($args =~ /--no-insert-call-quota-logs/i)
			{
			$no_insert_call_quota_logs=1;
			print "\n----- POPULATE NO INSERT CALL QUOTA LOGS: $no_insert_call_quota_logs -----\n\n";
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
			{
			$CLIcampaign = '';
			if ($populate_call_quota_logs > 0) 
				{
				$populate_call_quota_logs=0;
				print "\n----- POPULATE CALL QUOTA LOGS ERROR: No Campaign specified ($populate_call_quota_logs) -----\n\n";
				exit;
				}
			}
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
		if ($args =~ /--force-process-all-leads/i)
			{
			$force_process_all_leads=1;
			print "\n----- FORCE PROCESS ALL LEADS: $force_process_all_leads -----\n\n";
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

if (!$VDHLOGfile) {$VDHLOGfile = "$PATHlogs/call_quotas.$year-$mon-$mday";}
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::Local;
use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


#############################################
##### SYSTEM SETTINGS LOOKUP #####
$SScall_quota_lead_ranking=0;
$stmtA = "SELECT call_quota_lead_ranking,timeclock_end_of_day FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SScall_quota_lead_ranking =		$aryA[0];
	$SStimeclock_end_of_day =			$aryA[1];
	}
$sthA->finish();
##### END SYSTEM SETTINGS LOOKUP #####
###########################################

if ($SScall_quota_lead_ranking < 1) 
	{
	print "Call Quota Lead Ranking is disabled on this system, exiting...\n";
	exit;
	}

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


$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($secX);
$mon++;
$year = ($year + 1900);
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$now_hourmin = "$hour$min";
$LOCAL_GMT_OFF = $SERVER_GMT;
$LOCAL_GMT_OFF_STD = $SERVER_GMT;
if ($isdst) {$LOCAL_GMT_OFF++;} 

$GMT_now = ($secX - ($LOCAL_GMT_OFF * 3600));
($GMTsec,$GMTmin,$GMThour,$GMTmday,$GMTmon,$GMTyear,$GMTwday,$GMTyday,$GMTisdst) = localtime($GMT_now);
$GMTmon++;
$GMTyear = ($GMTyear + 1900);
if ($GMTmon < 10) {$GMTmon = "0$GMTmon";}
if ($GMTmday < 10) {$GMTmday = "0$GMTmday";}
if ($GMThour < 10) {$GMThour = "0$GMThour";}
if ($GMTmin < 10) {$GMTmin = "0$GMTmin";}
if ($GMTsec < 10) {$GMTsec = "0$GMTsec";}

if ($DB) {print "TIME DEBUG: $LOCAL_GMT_OFF_STD|$LOCAL_GMT_OFF|$isdst|   GMT: $GMThour:$GMTmin \n";}

if (length($SStimeclock_end_of_day) < 1) {$SStimeclock_end_of_day='0000';}
$timeclock_end_of_day_hour = (substr($SStimeclock_end_of_day, 0, 2) + 0);
$timeclock_end_of_day_min = (substr($SStimeclock_end_of_day, 2, 2) + 0);
$today_start_epoch = timelocal('0',$timeclock_end_of_day_min,$timeclock_end_of_day_hour,$mday,($mon-1),$year);
if ($SStimeclock_end_of_day > $now_hourmin)
	{$today_start_epoch = ($today_start_epoch - 86400);}
# calculate back to day 7 starting with day 1 = $today_start_epoch
$day_two_start_epoch = ($today_start_epoch - (86400 * 1));
$day_three_start_epoch = ($today_start_epoch - (86400 * 2));
$day_four_start_epoch = ($today_start_epoch - (86400 * 3));
$day_five_start_epoch = ($today_start_epoch - (86400 * 4));
$day_six_start_epoch = ($today_start_epoch - (86400 * 5));
$day_seven_start_epoch = ($today_start_epoch - (86400 * 6));

$SVtarget = ($today_start_epoch - (86400 * 6));	# look back to the start of day 7
($SVsec,$SVmin,$SVhour,$SVmday,$SVmon,$SVyear,$SVwday,$SVyday,$SVisdst) = localtime($SVtarget);
$SVyear = ($SVyear + 1900);
$SVmon++;
if ($SVmon < 10) {$SVmon = "0$SVmon";}
if ($SVmday < 10) {$SVmday = "0$SVmday";}
if ($SVhour < 10) {$SVhour = "0$SVhour";}
if ($SVmin < 10) {$SVmin = "0$SVmin";}
if ($SVsec < 10) {$SVsec = "0$SVsec";}
$SVSQLdate = "$SVyear-$SVmon-$SVmday $SVhour:$SVmin:$SVsec";

if ($DBX > 0) {print "TIME DEBUG: |$SStimeclock_end_of_day|$now_hourmin|$timeclock_end_of_day_hour|$timeclock_end_of_day_min|$today_start_epoch|$SVtarget|$SVSQLdate|\n";}



##################################################
##### BEGIN populate call quota logs process #####
##################################################
if ($populate_call_quota_logs > 0) 
	{
	$log_updated=0;
	$log_not_updated=0;
	$log_inserted=0;
	$total_call_count=0;
	$update_status_ct=0;
	$update_session_ct=0;
	$update_day_ct=0;
	$skip_dial_log_no_uid=0;
	$updated_dial_log_no_uid=0;

	# Gather a list of all lists within a campaign
	$stmtA = "SELECT list_id from vicidial_lists where campaign_id IN('$CLIcampaign');";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DBX > 0) {print "$sthArows|$stmtA\n";}
	$list_ct=0;
	while ($sthArows > $list_ct)
		{
		@aryA = $sthA->fetchrow_array;
		if ($list_ct > 0) {$list_idsSQL .= ",";}
		$list_idsSQL .= 	"'$aryA[0]'";
		$list_ct++;
		}
	$sthA->finish();

	if (length($list_idsSQL) < 4) 
		{
		if ($DB > 0) {print "NO LISTS ARE ASSIGNED TO CAMPAIGN: $CLIcampaign|$sthArows \n";}
		}
	else
		{
		# Gather a list of all leads called within the last 7 days within the lists of the specified campaign ( and lead_id='819938')
		$stmtA = "SELECT lead_id,list_id from vicidial_list where list_id IN($list_idsSQL) and last_local_call_time >= \"$SVSQLdate\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsLEADS=$sthA->rows;
		if ($DBX > 0) {print "$sthArowsLEADS|$stmtA\n";}
		$lead_ct=0;
		while ($sthArowsLEADS > $lead_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$LEADlead_id[$lead_ct] = 	$aryA[0];
			$LEADlist_id[$lead_ct] = 	$aryA[1];
			$lead_ct++;
			}
		$sthA->finish();

		if ($lead_ct < 1) 
			{
			if ($DB > 0) {print "NO LEADS CALLED IN THE LAST 7 DAYS ON THIS CAMPAIGN: $CLIcampaign|$lead_ct \n";}
			}
		else
			{
			if ($DBX > 0) {print "CALLS FOUND FOR THIS CAMPAIGN: $CLIcampaign|$lead_ct \n";}

			$stmtA = "SELECT call_quota_lead_ranking from vicidial_campaigns where campaign_id IN('$CLIcampaign');";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($DBX > 0) {print "$sthArows|$stmtA\n";}
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$call_quota_lead_ranking =		$aryA[0];
				$rec_count++;
				}
			$sthA->finish();

			$TEMPcontainer_entry='';
			$TEMPcall_quota_lead_ranking = $call_quota_lead_ranking;
			&gather_call_quota_settings;

			if ( ($settings_core_score >= 10) && ($settings_session_score >= 1) ) 
				{
				### BEGIN Gather calls for each lead, analyze and update call_quota log record ###
				$lead_ct=0;
				while ($sthArowsLEADS > $lead_ct)
					{
					$stmtA = "SELECT call_date,UNIX_TIMESTAMP(call_date),uniqueid,caller_code from vicidial_dial_log where lead_id='$LEADlead_id[$lead_ct]' and call_date >= \"$SVSQLdate\" order by call_date limit 1000;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArowsCALLS=$sthA->rows;
					if ($DBX > 0) {print "$sthArowsCALLS|$stmtA\n";}
					$call_ct=0;
					while ($sthArowsCALLS > $call_ct)
						{
						@aryA = $sthA->fetchrow_array;
						$LEADcall_date[$call_ct] = 		$aryA[0];
						$LEADcall_epoch[$call_ct] = 	$aryA[1];
						$LEADuniqueid[$call_ct] = 		$aryA[2];
						$LEADcaller_code[$call_ct] = 	$aryA[3];
						$call_ct++;
						}
					$sthA->finish();

					$call_ct=0;
					while ($sthArowsCALLS > $call_ct)
						{
						if (length($LEADuniqueid[$call_ct]) < 9) 
							{
							# Look for extended log from dial log entry with no uniqueid
							$stmtA = "SELECT uniqueid from vicidial_log_extended where caller_code='$LEADcaller_code[$call_ct]' and lead_id='$LEADlead_id[$lead_ct]';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsVLEUID=$sthA->rows;
							if ($DBX > 0) {print "$sthArowsVLEUID|$stmtA\n";}
							if ($sthArowsVLEUID > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$LEADuniqueid[$call_ct] = 		$aryA[0];
								}
							$sthA->finish();
							if ( ($sthArowsVLEUID > 0) && (length($LEADuniqueid[$call_ct]) >= 9) )
								{
								# Update list record with cache counts
								$stmtA = "UPDATE vicidial_dial_log SET uniqueid='$LEADuniqueid[$call_ct]' where caller_code='$LEADcaller_code[$call_ct]' and lead_id='$LEADlead_id[$lead_ct]' limit 1;";
								$affected_rows = $dbhA->do($stmtA);
								if ($DBX) {print "vicidial_dial_log UPDATED: $affected_rows|$stmtA|\n";}
								$event_string = "--    vicidial_dial_log record updated:   $affected_rows|$stmtA|$update_debug|";   &event_logger;
								$updated_dial_log_no_uid = ($updated_dial_log_no_uid + $affected_rows);
								}
							}
						$call_ct++;
						}

					if ($call_ct < 1) 
						{
						if ($DBX > 0) {print "NO CALLS FOUND IN THE LAST 7 DAYS ON THIS LEAD: $LEADlead_id[$lead_ct]|$LEADlist_id[$lead_ct]|$call_ct \n";}
						}
					else
						{
						# Gather current lead information
						$stmtA = "SELECT list_id,status,rank,called_count from vicidial_list where lead_id='$LEADlead_id[$lead_ct]' and list_id='$LEADlist_id[$lead_ct]';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArowsLI=$sthA->rows;
						if ($DBX > 0) {print "$sthArowsLI|$stmtA\n";}
						if ($sthArowsLI > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$LEADINFOlist_id = 			$aryA[0];
							$LEADINFOstatus = 			$aryA[1];
							$LEADINFOrank = 			$aryA[2];
							$LEADINFOcalled_count = 	$aryA[3];
							}
						$sthA->finish();
						if ($sthArowsLI < 1)
							{
							if ($DBX > 0) {print "LEAD NOT FOUND IN THIS LIST: $LEADlead_id[$lead_ct]|$LEADlist_id[$lead_ct]| \n";}
							}
						else
							{
							# Go through each call for this lead and calculate session and day call counts
							$session_one_calls=0;
							$session_two_calls=0;
							$session_three_calls=0;
							$session_four_calls=0;
							$session_five_calls=0;
							$session_six_calls=0;
							$session_one_today_calls=0;
							$session_two_today_calls=0;
							$session_three_today_calls=0;
							$session_four_today_calls=0;
							$session_five_today_calls=0;
							$session_six_today_calls=0;
							$day_one_calls=0;
							$day_two_calls=0;
							$day_three_calls=0;
							$day_four_calls=0;
							$day_five_calls=0;
							$day_six_calls=0;
							$day_seven_calls=0;
							$call_ct=0;
							$start_day=1;
							while ($sthArowsCALLS > $call_ct)
								{
								if (length($LEADuniqueid[$call_ct]) >= 9) 
									{
									@VDLcall_datetimeARY = split(/ /,$LEADcall_date[$call_ct]);
									@VDLcall_timeARY = split(/:/,$VDLcall_datetimeARY[1]);
									$VDLcall_hourmin = "$VDLcall_timeARY[0]$VDLcall_timeARY[1]";

									if ( ($session_one_start <= $VDLcall_hourmin) and ($session_one_end > $VDLcall_hourmin) ) {$session_one_calls++;}
									if ( ($session_two_start <= $VDLcall_hourmin) and ($session_two_end > $VDLcall_hourmin) ) {$session_two_calls++;}
									if ( ($session_three_start <= $VDLcall_hourmin) and ($session_three_end > $VDLcall_hourmin) ) {$session_three_calls++;}
									if ( ($session_four_start <= $VDLcall_hourmin) and ($session_four_end > $VDLcall_hourmin) ) {$session_four_calls++;}
									if ( ($session_five_start <= $VDLcall_hourmin) and ($session_five_end > $VDLcall_hourmin) ) {$session_five_calls++;}
									if ( ($session_six_start <= $VDLcall_hourmin) and ($session_six_end > $VDLcall_hourmin) ) {$session_six_calls++;}

									$VLCQClast_call_epoch = $LEADcall_epoch[$call_ct];   $VLCQClast_call_date = $LEADcall_date[$call_ct];
									if ($call_ct < 1) 
										{
										$VLCQCfirst_call_epoch = $LEADcall_epoch[$call_ct];
										$VLCQCfirst_call_date = $LEADcall_date[$call_ct];
										
										if ($VLCQCfirst_call_epoch >= $today_start_epoch) 
											{$start_day_epoch=$today_start_epoch;   $start_day=1;}
										if ( ($VLCQCfirst_call_epoch >= $day_two_start_epoch) and ($VLCQCfirst_call_epoch < $today_start_epoch) ) 
											{$start_day_epoch=$day_two_start_epoch;   $start_day=2;}
										if ( ($VLCQCfirst_call_epoch >= $day_three_start_epoch) and ($VLCQCfirst_call_epoch < $day_two_start_epoch) ) 
											{$start_day_epoch=$day_three_start_epoch;   $start_day=3;}
										if ( ($VLCQCfirst_call_epoch >= $day_four_start_epoch) and ($VLCQCfirst_call_epoch < $day_three_start_epoch) ) 
											{$start_day_epoch=$day_four_start_epoch;   $start_day=4;}
										if ( ($VLCQCfirst_call_epoch >= $day_five_start_epoch) and ($VLCQCfirst_call_epoch < $day_four_start_epoch) ) 
											{$start_day_epoch=$day_five_start_epoch;   $start_day=5;}
										if ( ($VLCQCfirst_call_epoch >= $day_six_start_epoch) and ($VLCQCfirst_call_epoch < $day_five_start_epoch) ) 
											{$start_day_epoch=$day_six_start_epoch;   $start_day=6;}
										if ( ($VLCQCfirst_call_epoch >= $day_seven_start_epoch) and ($VLCQCfirst_call_epoch < $day_six_start_epoch) ) 
											{$start_day_epoch=$day_seven_start_epoch;   $start_day=7;}
										
										$day_one_calls++;

										# calculate back to day 7 starting with day 1 = $start_day_epoch
										$Sday_two_start_epoch = ($start_day_epoch + (86400 * 1));
										$Sday_three_start_epoch = ($start_day_epoch + (86400 * 2));
										$Sday_four_start_epoch = ($start_day_epoch + (86400 * 3));
										$Sday_five_start_epoch = ($start_day_epoch + (86400 * 4));
										$Sday_six_start_epoch = ($start_day_epoch + (86400 * 5));
										$Sday_seven_start_epoch = ($start_day_epoch + (86400 * 6));
										$Sday_seven_end_epoch = ($start_day_epoch + (86400 * 7));
										if ($DBX > 0) {print "CQ-Debug 0: $VLCQCfirst_call_date|$start_day|$VLCQCfirst_call_epoch|$start_day_epoch|$Sday_two_start_epoch|$Sday_three_start_epoch|$Sday_four_start_epoch|$Sday_five_start_epoch|$Sday_six_start_epoch|$Sday_seven_start_epoch|\n";}
										}
									else
										{
										if ($DBX > 0) {print "CQ-Debug 1: $call_ct|$VLCQCfirst_call_date|$start_day|$VLCQCfirst_call_epoch|$start_day_epoch(".($LEADcall_epoch[$call_ct]-$start_day_epoch).")|$Sday_two_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_two_start_epoch).")|$Sday_three_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_three_start_epoch).")|$Sday_four_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_four_start_epoch).")|$Sday_five_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_five_start_epoch).")|$Sday_six_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_six_start_epoch).")|$Sday_seven_start_epoch(".($LEADcall_epoch[$call_ct]-$Sday_seven_start_epoch).")|$Sday_seven_end_epoch(".($LEADcall_epoch[$call_ct]-$Sday_seven_end_epoch).")|   |$LEADcall_epoch[$call_ct]|\n";}
										if ( ($LEADcall_epoch[$call_ct] >= $start_day_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_two_start_epoch) ) {$day_one_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_two_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_three_start_epoch) ) {$day_two_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_three_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_four_start_epoch) ) {$day_three_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_four_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_five_start_epoch) ) {$day_four_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_five_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_six_start_epoch) ) {$day_five_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_six_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_seven_start_epoch) ) {$day_six_calls++;}
										if ( ($LEADcall_epoch[$call_ct] >= $Sday_seven_start_epoch) && ($LEADcall_epoch[$call_ct] < $Sday_seven_end_epoch) ) {$day_seven_calls++;}
										}
									if ($LEADcall_epoch[$call_ct] >= $today_start_epoch) 
										{
										if ( ($session_one_start <= $VDLcall_hourmin) and ($session_one_end > $VDLcall_hourmin) ) {$session_one_today_calls++;}
										if ( ($session_two_start <= $VDLcall_hourmin) and ($session_two_end > $VDLcall_hourmin) ) {$session_two_today_calls++;}
										if ( ($session_three_start <= $VDLcall_hourmin) and ($session_three_end > $VDLcall_hourmin) ) {$session_three_today_calls++;}
										if ( ($session_four_start <= $VDLcall_hourmin) and ($session_four_end > $VDLcall_hourmin) ) {$session_four_today_calls++;}
										if ( ($session_five_start <= $VDLcall_hourmin) and ($session_five_end > $VDLcall_hourmin) ) {$session_five_today_calls++;}
										if ( ($session_six_start <= $VDLcall_hourmin) and ($session_six_end > $VDLcall_hourmin) ) {$session_six_today_calls++;}
										}
									}
								else
									{$skip_dial_log_no_uid++;}
								$call_ct++;
								$total_call_count++;
								}
							if ($DBX > 0) {print "CQ-Debug 2: $VDLcall_datetime|$VDLcall_hourmin|$SStimeclock_end_of_day|$start_day_epoch|$call_ct|   |$session_one_calls|$session_two_calls|$session_three_calls|$session_four_calls|$session_five_calls|$session_six_calls|   |$day_one_calls|$day_two_calls|$day_three_calls|$day_four_calls|$day_five_calls|$day_six_calls|$day_seven_calls|   |$session_one_today_calls|$session_two_today_calls|$session_three_today_calls|$session_four_today_calls|$session_five_today_calls|$session_six_today_calls|\n";}

							# Gather the details on existing vicidial_lead_call_quota_counts for this lead, if there is one
							$stmtA = "SELECT first_call_date,last_call_date,status,called_count,session_one_calls,session_two_calls,session_three_calls,session_four_calls,session_five_calls,session_six_calls,day_one_calls,day_two_calls,day_three_calls,day_four_calls,day_five_calls,day_six_calls,day_seven_calls,rank,modify_date,session_one_today_calls,session_two_today_calls,session_three_today_calls,session_four_today_calls,session_five_today_calls,session_six_today_calls from vicidial_lead_call_quota_counts where lead_id='$LEADlead_id[$lead_ct]' and list_id='$LEADlist_id[$lead_ct]';";
								if ($DBX > 0) {print "|$stmtA|\n";}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$VLCQCinfo_ct=$sthA->rows;
							if ($VLCQCinfo_ct > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$LOGfirst_call_date =			$aryA[0];
								$LOGlast_call_date =			$aryA[1];
								$LOGstatus =					$aryA[2];
								$LOGcalled_count =				$aryA[3];
								$LOGsession_one_calls =			$aryA[4];
								$LOGsession_two_calls =			$aryA[5];
								$LOGsession_three_calls =		$aryA[6];
								$LOGsession_four_calls =		$aryA[7];
								$LOGsession_five_calls =		$aryA[8];
								$LOGsession_six_calls =			$aryA[9];
								$LOGday_one_calls =				$aryA[10];
								$LOGday_two_calls =				$aryA[11];
								$LOGday_three_calls =			$aryA[12];
								$LOGday_four_calls =			$aryA[13];
								$LOGday_five_calls =			$aryA[14];
								$LOGday_six_calls =				$aryA[15];
								$LOGday_seven_calls =			$aryA[16];
								$LOGrank =						$aryA[17];
								$LOGmodify_date =				$aryA[18];
								$LOGsession_one_today_calls =	$aryA[19];
								$LOGsession_two_today_calls =	$aryA[20];
								$LOGsession_three_today_calls =	$aryA[21];
								$LOGsession_four_today_calls =	$aryA[22];
								$LOGsession_five_today_calls =	$aryA[23];
								$LOGsession_six_today_calls =	$aryA[24];
								}
							$sthA->finish();

							if ($VLCQCinfo_ct > 0)
								{
								$log_update=0;
								$log_updateSQL='';
								if ($LOGfirst_call_date ne $VLCQCfirst_call_date)	{$log_update++; $log_updateSQL.=",first_call_date='$VLCQCfirst_call_date'";}
								if ($LOGlast_call_date ne $VLCQClast_call_date)		{$log_update++; $log_updateSQL.=",last_call_date='$VLCQClast_call_date'";}
								if ($LOGstatus ne $LEADINFOstatus)					{$log_update++; $update_status_ct++; $log_updateSQL.=",status='$LEADINFOstatus'";}
								if ($LOGcalled_count ne $LEADINFOcalled_count)		{$log_update++; $log_updateSQL.=",called_count='$LEADINFOcalled_count'";}
								if ($LOGsession_one_calls ne $session_one_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_one_calls='$session_one_calls'";}
								if ($LOGsession_two_calls ne $session_two_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_two_calls='$session_two_calls'";}
								if ($LOGsession_three_calls ne $session_three_calls){$log_update++; $update_session_ct++; $log_updateSQL.=",session_three_calls='$session_three_calls'";}
								if ($LOGsession_four_calls ne $session_four_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_four_calls='$session_four_calls'";}
								if ($LOGsession_five_calls ne $session_five_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_five_calls='$session_five_calls'";}
								if ($LOGsession_six_calls ne $session_six_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_six_calls='$session_six_calls'";}
								if ($LOGday_one_calls ne $day_one_calls)			{$log_update++; $update_day_ct++; $log_updateSQL.=",day_one_calls='$day_one_calls'";}
								if ($LOGday_two_calls ne $day_two_calls)			{$log_update++; $update_day_ct++; $log_updateSQL.=",day_two_calls='$day_two_calls'";}
								if ($LOGday_three_calls ne $day_three_calls)		{$log_update++; $update_day_ct++; $log_updateSQL.=",day_three_calls='$day_three_calls'";}
								if ($LOGday_four_calls ne $day_four_calls)			{$log_update++; $update_day_ct++; $log_updateSQL.=",day_four_calls='$day_four_calls'";}
								if ($LOGday_five_calls ne $day_five_calls)			{$log_update++; $update_day_ct++; $log_updateSQL.=",day_five_calls='$day_five_calls'";}
								if ($LOGday_six_calls ne $day_six_calls)			{$log_update++; $update_day_ct++; $log_updateSQL.=",day_six_calls='$day_six_calls'";}
								if ($LOGday_seven_calls ne $day_seven_calls)		{$log_update++; $update_day_ct++; $log_updateSQL.=",day_seven_calls='$day_seven_calls'";}
								if ($LOGrank ne $LEADINFOrank)						{$log_update++; $update_day_ct++; $log_updateSQL.=",rank='$LEADINFOrank'";}
								if ($LOGsession_one_today_calls ne $session_one_today_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_one_today_calls='$session_one_today_calls'";}
								if ($LOGsession_two_today_calls ne $session_two_today_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_two_today_calls='$session_two_today_calls'";}
								if ($LOGsession_three_today_calls ne $session_three_today_calls){$log_update++; $update_session_ct++; $log_updateSQL.=",session_three_today_calls='$session_three_today_calls'";}
								if ($LOGsession_four_today_calls ne $session_four_today_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_four_today_calls='$session_four_today_calls'";}
								if ($LOGsession_five_today_calls ne $session_five_today_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_five_today_calls='$session_five_today_calls'";}
								if ($LOGsession_six_today_calls ne $session_six_today_calls)	{$log_update++; $update_session_ct++; $log_updateSQL.=",session_six_today_calls='$session_six_today_calls'";}

								if ($log_update < 1) 
									{
									if ($DBX > 0) {print "--    VLCQC record does not need to be updated: |$LEADlead_id[$lead_ct]|$log_update|   |$log_updateSQL|\n";}
									$log_not_updated++;
									}
								else
									{
									$update_debug="$LOGfirst_call_date|$LOGlast_call_date|$LOGstatus|$LOGcalled_count|   |$LOGsession_one_calls|$LOGsession_two_calls|$LOGsession_three_calls|$LOGsession_four_calls|$LOGsession_five_calls|$LOGsession_six_calls|   |$LOGday_one_calls|$LOGday_two_calls|$LOGday_three_calls|$LOGday_four_calls|$LOGday_five_calls|$LOGday_six_calls|$LOGday_seven_calls|   |$LOGrank|$LOGmodify_date|   |$LOGsession_one_today_calls|$LOGsession_two_today_calls|$LOGsession_three_today_calls|$LOGsession_four_today_calls|$LOGsession_five_today_calls|$LOGsession_six_today_calls|";
									# Update in the vicidial_lead_call_quota_counts table for this lead
									$stmtA="UPDATE vicidial_lead_call_quota_counts SET modify_date=NOW() $log_updateSQL where lead_id='$LEADlead_id[$lead_ct]' and list_id='$LEADlist_id[$lead_ct]';";
									$VLCQCaffected_rows_update = $dbhA->do($stmtA);
									$event_string = "--    VLCQC record updated: |$LEADlead_id[$lead_ct]|$VLCQCaffected_rows_update|   |$stmtA|$update_debug|";   &event_logger;
									$log_updated++;
									}
								}
							else
								{
								# Insert new record into vicidial_lead_call_quota_counts table for this lead
								$stmtA="INSERT INTO vicidial_lead_call_quota_counts SET lead_id='$LEADlead_id[$lead_ct]',list_id='$LEADlist_id[$lead_ct]',first_call_date='$VLCQCfirst_call_date',last_call_date='$VLCQClast_call_date',status='$LEADINFOstatus',called_count='$LEADINFOcalled_count',session_one_calls='$session_one_calls',session_two_calls='$session_two_calls',session_three_calls='$session_three_calls',session_four_calls='$session_four_calls',session_five_calls='$session_five_calls',session_six_calls='$session_six_calls',day_one_calls='$day_one_calls',day_two_calls='$day_two_calls',day_three_calls='$day_three_calls',day_four_calls='$day_four_calls',day_five_calls='$day_five_calls',day_six_calls='$day_six_calls',day_seven_calls='$day_seven_calls',rank='$LEADINFOrank',modify_date=NOW(),session_one_today_calls='$session_one_today_calls',session_two_today_calls='$session_two_today_calls',session_three_today_calls='$session_three_today_calls',session_four_today_calls='$session_four_today_calls',session_five_today_calls='$session_five_today_calls',session_six_today_calls='$session_six_today_calls';";
								if ($no_insert_call_quota_logs < 1) 
									{
									$VLCQCaffected_rows_update = $dbhA->do($stmtA);
									$event_string = "--    VLCQC record inserted: |$VLCQCaffected_rows_update|   |$stmtA|";   &event_logger;
									}
								else
									{
									$event_string = "--    VLCQC record inserted skipped: |0|   |$stmtA|";   &event_logger;
									}
								$log_inserted++;
								}
							}
						}
					$lead_ct++;
					if ($DB) 
						{
						if ($lead_ct =~ /100$/i) {print STDERR "0     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /200$/i) {print STDERR "+     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /300$/i) {print STDERR "|     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /400$/i) {print STDERR "\\     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /500$/i) {print STDERR "-     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /600$/i) {print STDERR "/     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /700$/i) {print STDERR "|     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /800$/i) {print STDERR "+     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /900$/i) {print STDERR "0     $lead_ct / $sthArowsLEADS \r";}
						if ($lead_ct =~ /0000$/i) 
							{
							print "$lead_ct / $sthArowsLEADS   ($log_updated|$log_inserted|$total_call_count|$skip_dial_log_no_uid|$updated_dial_log_no_uid|   |$LEADlist_id[$lead_ct]|$LEADINFOrank| \n";
							}
						}
					}
				if ($DB) 
					{
					print "Call Quota log populate process complete: \n";
					print "Logs updated:                    $log_updated \n";
					print "     status updated:                  $update_status_ct \n";
					print "     session count updated:           $update_session_ct \n";
					print "     day count updated:               $update_day_ct \n";
					print "Logs did not need to be updated: $log_not_updated \n";
					if ($no_insert_call_quota_logs < 1)	{print "Logs inserted:                   $log_inserted \n";}
					else								{print "Logs were not inserted:          $log_inserted \n";}
					print "Total leads:                     $lead_ct \n";
					print "Total call entries analyzed:     $total_call_count ($skip_dial_log_no_uid|$updated_dial_log_no_uid) \n";
					}
				### END Gather calls for each lead, analyze and update call_quota log record ###
				}
			else
				{
				if ($DB) {print "ERROR: CALL QUOTA SETTINGS CONTAINER INCOMPLETE or INVALID: $call_quota_lead_ranking ($settings_core_score|$settings_session_score) \n$TEMPcontainer_entry  \n";}
				}
			}
		}

	if($DB)
		{
		### calculate time to run script ###
		$secY = time();
		$secZ = ($secY - $secT);

		if (!$q) {print "DONE. Script execution time in seconds: $secZ\n";}
		}
	exit;
	}
##################################################
##### END populate call quota logs process   #####
##################################################





####################################################################
##### BEGIN gather list of active campaigns and their settings #####
####################################################################
@campaign_id=@MT; 

if (length($CLIcampaign)>1)
	{
	$stmtA = "SELECT campaign_id,auto_active_list_new,call_quota_lead_ranking,call_quota_process_running,call_quota_last_run_date,local_call_time,call_count_limit,local_call_time,lead_filter_id,dial_statuses,drop_lockout_time,lead_order from vicidial_campaigns where campaign_id IN('$CLIcampaign');";
	}
else
	{
	$stmtA = "SELECT campaign_id,auto_active_list_new,call_quota_lead_ranking,call_quota_process_running,call_quota_last_run_date,local_call_time,call_count_limit,local_call_time,lead_filter_id,dial_statuses,drop_lockout_time,lead_order from vicidial_campaigns where active='Y';";
	}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($DBX > 0) {print "$sthArows|$stmtA\n";}
$rec_count=0;
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$campaign_id[$rec_count] =					$aryA[0];
	$auto_active_list_new[$rec_count] =			$aryA[1];
	$call_quota_lead_ranking[$rec_count] =		$aryA[2];
	$call_quota_process_running[$rec_count] =	$aryA[3];
	$call_quota_last_run_date[$rec_count] =		$aryA[4];
	$local_call_time[$rec_count] =				$aryA[5];
	$call_count_limit[$rec_count] =				$aryA[6];
	$local_call_time[$rec_count] =				$aryA[7];
	$lead_filter_id[$rec_count] =				$aryA[8];
	$dial_statuses[$rec_count] =				$aryA[9];
	$drop_lockout_time[$rec_count] =			$aryA[10];
	$lead_order[$rec_count] =					$aryA[11];

	$rec_count++;
	}
$sthA->finish();
if ($DB) {print "CAMPAIGNS TO PROCESSES CALL QUOTAS FOR:  $rec_count|$#campaign_id\n";}
####################################################################
##### END gather list of active campaigns and their settings   #####
####################################################################



######################################################################
##### BEGIN Loop through each active campaign and process lists  #####
######################################################################
$i=0;
foreach(@campaign_id)
	{
	$secC = time();
	$active_dialable_NEW_count=0;
	$next_NEW_list_to_activate='';
	$recent_dial_count=0;
	$rank_updates=0;
	$LEADreset_check_ct=0;
	$LEADreset_ct=0;
	$RUNoutput='';

	if ($call_quota_lead_ranking[$i] =~ /^DISABLED$/i)
		{if ($DB > 0) {print "CALL QUOTAS DISABLED FOR THIS CAMPAIGN: $campaign_id[$i]|$call_quota_lead_ranking[$i]\n";}}
	else
		{
		### BEGIN gather stats on lists for cache ###

		# Get lead filter for this campaign
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
				}
			}
		else
			{
			$lead_filter_sql[$i] = '';
			if ($DB) {print "     no lead filter defined for campaign: $campaign_id[$i]\n";}
			if ($DBX) {print "     |$lead_filter_id[$i]|\n";}
			}

		# Get list details from each list in the campaign
		$stmtA = "SELECT list_id,list_name,active,local_call_time,auto_active_list_rank,cache_count,cache_count_new,cache_count_dialable_new,list_lastcalldate,UNIX_TIMESTAMP(list_lastcalldate) from vicidial_lists where expiration_date >= CURDATE() and campaign_id='$campaign_id[$i]' order by active,auto_active_list_rank desc,list_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsLISTS=$sthA->rows;
		if ($DB > 0) {print "NON-EXPIRED LISTS IN THIS CAMPAIGN: $campaign_id[$i]|$sthArowsLISTS \n";}
		$d=0;
		while ($sthArowsLISTS > $d)
			{
			@aryA = $sthA->fetchrow_array;
			$LISTlist_id[$d] =					$aryA[0];
			$LISTlist_name[$d] =				$aryA[1];
			$LISTactive[$d] =					$aryA[2];
			$LISTlocal_call_time[$d] =			$aryA[3];
			$LISTauto_active_list_rank[$d] =	$aryA[4];
			$LISTcache_count[$d] =				$aryA[5];
			$LISTcache_count_new[$d] =			$aryA[6];
			$LISTcache_count_dialable_new[$d] =	$aryA[7];
			$LISTlist_lastcalldate[$d] =		$aryA[8];
			$LISTlist_lastcalldateUNIX[$d] =	$aryA[9];
			$d++;
			}
		$sthA->finish();
		# look up counts for cache stats for this list
		$d=0;
		while ($sthArowsLISTS > $d)
			{
			# Gather count of number of leads in the list
			$stmtA = "SELECT count(*) from vicidial_list where list_id='$LISTlist_id[$d]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$LISTleads_count[$d] =		$aryA[0];
				}
			if ($DBX > 0) {print "LEADS IN THIS LIST:                          $LISTleads_count[$d]|$sthArows|$LISTlist_id[$d]|$campaign_id[$i]\n";}

			# Gather count of number of NEW leads in the list
			$stmtA = "SELECT count(*) from vicidial_list where list_id='$LISTlist_id[$d]' and status='NEW';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$LISTleads_NEW_count[$d] =		$aryA[0];
				}
			if ($DBX > 0) {print "NEW LEADS IN THIS LIST:                      $LISTleads_NEW_count[$d]|$sthArows|$LISTlist_id[$d]|$campaign_id[$i]\n";}

			$SUB_local_call_time = $local_call_time[$i];
			if ($LISTlocal_call_time[$d] != 'campaign') 
				{
				$SUB_local_call_time = $LISTlocal_call_time[$d];
				if ($DBX > 0) {print "     List Call Time Override:                $SUB_local_call_time\n";}
				}

			$RETURN_default_gmt='';
			$RETURN_all_gmtSQL='';
			$RETURN_del_gmtSQL='';
			&gmt_sql_generate($SUB_local_call_time);
			if ($DBX > 0) {print "     DEBUG GMT Dialable SQL:                 $RETURN_default_gmt\n";}

			# Gather count of number of dialable NEW leads in the list
			$stmtA = "SELECT count(*) FROM vicidial_list $VLforce_index where list_id='$LISTlist_id[$d]' and status='NEW' and ($RETURN_all_gmtSQL) $lead_filter_sql[$i];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$LISTleads_dialable_NEW_count[$d] =		$aryA[0];
				}
			if ($DBX > 0) {print "DIALABLE NEW LEADS IN THIS LIST:             $LISTleads_dialable_NEW_count[$d]|$sthArows|$LISTlist_id[$d]|$campaign_id[$i]|$stmtA\n";}

			# Update list record with cache counts
			$stmtA = "UPDATE vicidial_lists SET cache_count='$LISTleads_count[$d]',cache_count_new='$LISTleads_NEW_count[$d]',cache_count_dialable_new='$LISTleads_dialable_NEW_count[$d]',cache_date=NOW() where list_id='$LISTlist_id[$d]';";
			$affected_rows = $dbhA->do($stmtA);
			if ($DBX) {print "LIST CACHE STATS UPDATED: $affected_rows|$stmtA|\n";}
			$sthA->finish();
			$RUNoutput .= "LIST CACHE STATS UPDATED: $LISTlist_id[$d]   TOTAL: $LISTleads_count[$d]   NEW: $LISTleads_NEW_count[$d]   DIALABLE NEW: $LISTleads_dialable_NEW_count[$d] \n";

			if ($LISTactive[$d] =~ /^Y$/i) 
				{
				$active_dialable_NEW_count = ($active_dialable_NEW_count + $LISTleads_dialable_NEW_count[$d]);
				if ($DBX) {print "LIST ACTIVE DIALABLE LEADS: $LISTleads_dialable_NEW_count[$d]($active_dialable_NEW_count)\n";}
				}
			else
				{
				# set the next list(with diablable NEW leads) in order to be activated, if needed
				if ( ($LISTleads_dialable_NEW_count[$d] > 0) && (length($next_NEW_list_to_activate) < 1) )
					{
					$next_NEW_list_to_activate = $LISTlist_id[$d];
					if ($DBX) {print "     Next NEW list to activate selected: $next_NEW_list_to_activate|$LISTleads_dialable_NEW_count[$d] \n";}
					}
				}

			if ($LISTlist_lastcalldateUNIX[$d] > $VDL_hour_epoch) 
				{
				$recent_dial_count++;
				if ($DBX) {print "LIST RECENTLY DIALED:       ($LISTlist_lastcalldateUNIX[$d] > $VDL_hour)   $recent_dial_count \n";}
				}

			if ($DB) {print "     LIST ".sprintf("%14s", $LISTlist_id[$d]).":       $LISTactive[$d]|$LISTauto_active_list_rank[$d]|$LISTleads_count[$d]|$LISTleads_NEW_count[$d]|$LISTleads_dialable_NEW_count[$d] \n";}

			$d++;
			}
		### END gather stats on lists for cache ###


		### BEGIN auto-activate next dialable-NEW list, if needed ###
		if ($auto_active_list_new[$i] !~ /^DISABLED$/i)
			{
			if ($DB) {print "CHECKING TO SEE IF AUTO-ACTIVE-NEW-LIST NEEDS TO BE ACTIVATED:   $recent_dial_count|$next_NEW_list_to_activate  ($auto_active_list_new[$i] <> $active_dialable_NEW_count) \n";}
			if ( ($recent_dial_count > 0) && ($auto_active_list_new[$i] > $active_dialable_NEW_count) && (length($next_NEW_list_to_activate)>1) ) 
				{
				# Update list record to set list to active
				$stmtB = "UPDATE vicidial_lists SET active='Y',list_changedate=NOW() where list_id='$next_NEW_list_to_activate';";
				$affected_rowsB = $dbhA->do($stmtB);
				if ($DBX) {print "LIST SET TO ACTIVE: $affected_rowsB|$stmtB|\n";}
				$sthA->finish();

				if ($DB) {print "NEXT AUTO-ACTIVE-NEW-LIST SET TO ACTIVE:   $next_NEW_list_to_activate|$affected_rowsB \n";}
				$RUNoutput .= "NEXT AUTO-ACTIVE-NEW-LIST SET TO ACTIVE:   $next_NEW_list_to_activate|$affected_rowsB \n";

				# Insert record into the admin log for this list modification
				$SQL_log = "$stmtB|";
				$SQL_log =~ s/;|\\|\'|\"//gi;
				$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='LISTS', event_type='MODIFY', record_id='$next_NEW_list_to_activate', event_code='auto_active_list_new', event_sql='$SQL_log', event_notes='$affected_rowsB   |$recent_dial_count|$auto_active_list_new[$i]|$active_dialable_NEW_count';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "ADMIN LOG FOR LIST SET TO ACTIVE: $affected_rows|$stmtA|\n";}
				$sthA->finish();
				}
			}
		### END auto-activate next dialable-NEW list, if needed ###


		##### BEGIN run the Quota Ranking for dialed leads that have been modified since last run #####

		$TEMPcontainer_entry='';
		$TEMPcall_quota_lead_ranking = $call_quota_lead_ranking[$i];
		&gather_call_quota_settings;

		if ( ($settings_core_score >= 10) && ($settings_session_score >= 1) ) 
			{
			$test_lead_order = 'UP RANK';
			if ($new_shuffle == 2) {$test_lead_order = 'UP RANK 2nd NEW';}
			if ($new_shuffle == 3) {$test_lead_order = 'UP RANK 3rd NEW';}
			if ($new_shuffle == 4) {$test_lead_order = 'UP RANK 4th NEW';}
			if ($new_shuffle == 5) {$test_lead_order = 'UP RANK 5th NEW';}
			if ($new_shuffle == 6) {$test_lead_order = 'UP RANK 6th NEW';}

			if ( ($test_lead_order !~ /$lead_order[$i]/) || (length($test_lead_order) > (length($lead_order[$i]))) || (length($test_lead_order) < (length($lead_order[$i]))) )
				{
				# Update this campaign to use the proper List Order
				$stmtC = "UPDATE vicidial_campaigns SET lead_order='$test_lead_order' where campaign_id='$campaign_id[$i]';";
				$affected_rowsC = $dbhA->do($stmtC);
				$sthA->finish();

				if ($DB) {print "CAMPAIGN lead_order override set: $affected_rowsC|$test_lead_order|$lead_order[$i]|\n";}
				$RUNoutput .= "CAMPAIGN lead_order override set: $affected_rowsC|$test_lead_order|$lead_order[$i] \n";

				# Insert record into the admin log for this list modification
				$SQL_log = "$stmtC|";
				$SQL_log =~ s/;|\\|\'|\"//gi;
				$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='CAMPAIGNS', event_type='MODIFY', record_id='$campaign_id[$i]', event_code='call_quota_lead_order_change', event_sql='$SQL_log', event_notes='$affected_rowsC|$test_lead_order|$lead_order[$i]|';";
				$affected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "ADMIN LOG FOR CAMPAIGN LIST ORDER CHANGE: $affected_rows|$stmtA|\n";}
				$sthA->finish();
				}

			# Gather list of the lists in the campaign
			$stmtA = "SELECT list_id from vicidial_lists where campaign_id='$campaign_id[$i]' order by auto_active_list_rank desc,list_id;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsDLISTS=$sthA->rows;
			if ($DB > 0) {print "LISTS IN THIS CAMPAIGN: $campaign_id[$i]|$sthArowsDLISTS \n";}
			$d=0;
			while ($sthArowsDLISTS > $d)
				{
				@aryA = $sthA->fetchrow_array;
				$DLISTlist_id[$d] =					$aryA[0];
				$d++;
				}
			$sthA->finish();

			# look up counts for cache stats for this list
			$redial_statusesSQL = "'$redial_statuses'";
			$redial_statusesSQL =~ s/,|\|/','/gi;
			$d=0;
			while ($sthArowsDLISTS > $d)
				{
				# Gather count of number of leads in the list ( and modify_date >= \"$VDL_hour\")
				$stmtA = "SELECT lead_id,first_call_date,status,called_count,session_one_calls,session_two_calls,session_three_calls,session_four_calls,session_five_calls,session_six_calls,day_one_calls,day_two_calls,day_three_calls,day_four_calls,day_five_calls,day_six_calls,day_seven_calls,rank,modify_date,UNIX_TIMESTAMP(first_call_date),UNIX_TIMESTAMP(last_call_date),session_one_today_calls,session_two_today_calls,session_three_today_calls,session_four_today_calls,session_five_today_calls,session_six_today_calls from vicidial_lead_call_quota_counts where list_id='$DLISTlist_id[$d]' and last_call_date < \"$VDL_hour\" and status IN($redial_statusesSQL);";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArowsRANK=$sthA->rows;
				$rc=0;
				while ($sthArowsRANK > $rc)
					{
					@aryA = $sthA->fetchrow_array;
					$RClead_id[$rc] =					$aryA[0];
					$RCfirst_call_date[$rc] =			$aryA[1];
					$RCstatus[$rc] =					$aryA[2];
					$RCcalled_count[$rc] =				$aryA[3];
					$RCsession_one_calls[$rc] =			$aryA[4];
					$RCsession_two_calls[$rc] =			$aryA[5];
					$RCsession_three_calls[$rc] =		$aryA[6];
					$RCsession_four_calls[$rc] =		$aryA[7];
					$RCsession_five_calls[$rc] =		$aryA[8];
					$RCsession_six_calls[$rc] =			$aryA[9];
					$RCday_one_calls[$rc] =				$aryA[10];
					$RCday_two_calls[$rc] =				$aryA[11];
					$RCday_three_calls[$rc] =			$aryA[12];
					$RCday_four_calls[$rc] =			$aryA[13];
					$RCday_five_calls[$rc] =			$aryA[14];
					$RCday_six_calls[$rc] =				$aryA[15];
					$RCday_seven_calls[$rc] =			$aryA[16];
					$RCrank[$rc] =						$aryA[17];
					$RCmodify_date[$rc] =				$aryA[18];
					$RCfirst_call_epoch[$rc] =			$aryA[19];
					$RClast_call_epoch[$rc] =			$aryA[20];
					$RCsession_one_today_calls[$rc] =	$aryA[21];
					$RCsession_two_today_calls[$rc] =	$aryA[22];
					$RCsession_three_today_calls[$rc] =	$aryA[23];
					$RCsession_four_today_calls[$rc] =	$aryA[24];
					$RCsession_five_today_calls[$rc] =	$aryA[25];
					$RCsession_six_today_calls[$rc] =	$aryA[26];
					$rc++;
					}
				if ($sthArowsRANK < 1)
					{
					if ($DBX) {print "NO CALL QUOTA RECYCLABLE LEADS FOUND IN THIS LIST: $sthArowsRANK|$DLISTlist_id[$d]|$stmtA|  \n";}
					}
				else
					{
					if ($DBX > 0) {print "CALL QUOTA RECYCLABLE LEADS FOUND IN THIS LIST:  $sthArowsRANK|$DLISTlist_id[$d]|$stmtA| \n";}

					$rc=0;
					$POINTS_uncalled_session=2000;
					$POINTS_belowgoal_session=1000;
					$POINTS_uncalled_today=2000;
					$POINTS_belowgoal_today=1000;
					$POINTS_goaldiff_multiplier=10;
					$POINTS_daysleft_inverse_multiplier=10;
					$POINTS_atmax_today=-10000;
					$POINTS_atgoal_total=-4000;
					$POINTS_atmax_session_today=-6000;

					# starting the ranking of each call-quota-recyclable lead
					while ($sthArowsRANK > $rc)
						{
						$LEADrank=0;
						# session points added
						if ( ($session_now == 1) && (length($session_one_start) > 0) )
							{
							if ($RCsession_one_calls[$rc] < $session_goal) 
								{
								if ($RCsession_one_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_one_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}
						if ( ($session_now == 2) && (length($session_two_start) > 0) )
							{
							if ($RCsession_two_calls[$rc] < $session_goal) 
								{
								if ($RCsession_two_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_two_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}
						if ( ($session_now == 3) && (length($session_three_start) > 0) )
							{
							if ($RCsession_three_calls[$rc] < $session_goal) 
								{
								if ($RCsession_three_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_three_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}
						if ( ($session_now == 4) && (length($session_four_start) > 0) )
							{
							if ($RCsession_four_calls[$rc] < $session_goal) 
								{
								if ($RCsession_four_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_four_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}
						if ( ($session_now == 5) && (length($session_five_start) > 0) )
							{
							if ($RCsession_five_calls[$rc] < $session_goal) 
								{
								if ($RCsession_five_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_five_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}
						if ( ($session_now == 6) && (length($session_six_start) > 0) )
							{
							if ($RCsession_six_calls[$rc] < $session_goal) 
								{
								if ($RCsession_six_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_session);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_session);}
								}
							if ($RCsession_six_today_calls[$rc] >= $session_day_max) {$LEADrank = ($LEADrank + $POINTS_atmax_session_today);}
							}

						# day points added
						if ($RCfirst_call_epoch[$rc] >= $today_start_epoch) 
							{$start_day_epoch=$today_start_epoch;   $start_day=1;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_two_start_epoch) && ($RCfirst_call_epoch[$rc] < $today_start_epoch) ) 
							{$start_day_epoch=$day_two_start_epoch;   $start_day=2;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_three_start_epoch) && ($RCfirst_call_epoch[$rc] < $day_two_start_epoch) ) 
							{$start_day_epoch=$day_three_start_epoch;   $start_day=3;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_four_start_epoch) && ($RCfirst_call_epoch[$rc] < $day_three_start_epoch) ) 
							{$start_day_epoch=$day_four_start_epoch;   $start_day=4;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_five_start_epoch) && ($RCfirst_call_epoch[$rc] < $day_four_start_epoch) ) 
							{$start_day_epoch=$day_five_start_epoch;   $start_day=5;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_six_start_epoch) && ($RCfirst_call_epoch[$rc] < $day_five_start_epoch) ) 
							{$start_day_epoch=$day_six_start_epoch;   $start_day=6;}
						if ( ($RCfirst_call_epoch[$rc] >= $day_seven_start_epoch) && ($RCfirst_call_epoch[$rc] < $day_six_start_epoch) ) 
							{$start_day_epoch=$day_seven_start_epoch;   $start_day=7;}

						if ($start_day == 1)
							{
							if ($RCday_one_calls[$rc] < $daily_goal)
								{
								if ($RCday_one_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_one_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 2)
							{
							if ($RCday_two_calls[$rc] < $daily_goal)
								{
								if ($RCday_two_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_two_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 3)
							{
							if ($RCday_three_calls[$rc] < $daily_goal)
								{
								if ($RCday_three_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_three_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 4) 
							{
							if ($RCday_four_calls[$rc] < $daily_goal)
								{
								if ($RCday_four_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_four_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 5)
							{
							if ($RCday_five_calls[$rc] < $daily_goal)
								{
								if ($RCday_five_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_five_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 6)
							{
							if ($RCday_six_calls[$rc] < $daily_goal)
								{
								if ($RCday_six_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_six_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}
						if ($start_day == 7)
							{
							if ($RCday_seven_calls[$rc] < $daily_goal)
								{
								if ($RCday_seven_calls[$rc] < 1) {$LEADrank = ($LEADrank + $POINTS_uncalled_today);}
								else {$LEADrank = ($LEADrank + $POINTS_belowgoal_today);}
								}
							if ($RCday_seven_calls[$rc] >= $daily_max) {$LEADrank = ($LEADrank + $POINTS_atmax_today);}
							}

						# daily goal points added
						$temp_total_goal_diff=0;
						$temp_total_calls = ($RCday_one_calls[$rc] + $RCday_two_calls[$rc] + $RCday_three_calls[$rc] + $RCday_four_calls[$rc] + $RCday_five_calls[$rc] + $RCday_six_calls[$rc] + $RCday_seven_calls[$rc]);
						if ($temp_total_calls >= $total_goal) 
							{$LEADrank = ($LEADrank + $POINTS_atgoal_total);}
						else 
							{
							$temp_total_goal_diff = ($total_goal - $temp_total_calls);
							$LEADrank = ($LEADrank + ($temp_total_goal_diff * $POINTS_goaldiff_multiplier));
							}

						# days-left points added, if current day is not greater than min_consecutive_days(weighted for days off)
						if (length($active_days_of_week) >= 7) {$no_call_days=0;}
						else
							{
							$no_call_days=0;
							$temp_ct=1;
							$temp_start_day = $start_day;
							$temp_wday = $wday;
							while ($temp_ct < $temp_start_day) 
								{
								if ($temp_wday == 0) {$temp_wday = 6;}
								else {$temp_wday = ($temp_wday - 1);}
								if ($active_days_of_week !~ /$temp_wday/) 
									{$no_call_days++;}
								$temp_ct++;
							#	print "days-off debug:    $temp_ct   $temp_start_day   $temp_wday   $active_days_of_week   $no_call_days \n";
								}
							}
						$weighted_consecutive_days = ($min_consecutive_days + $no_call_days);
						if ($weighted_consecutive_days >= $start_day) 
							{
							$temp_days_left_points=0;
							$temp_days_left = ($min_consecutive_days - $start_day);
							if ($temp_days_left == 0) {$temp_days_left_points = (128 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 1) {$temp_days_left_points = (64 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 2) {$temp_days_left_points = (32 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 3) {$temp_days_left_points = (16 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 4) {$temp_days_left_points = (8 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 5) {$temp_days_left_points = (4 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 6) {$temp_days_left_points = (2 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left == 7) {$temp_days_left_points = (1 * $POINTS_daysleft_inverse_multiplier);}
							if ($temp_days_left_points > 0) {$LEADrank = ($LEADrank + $temp_days_left_points);}
						#	print "$min_consecutive_days|$start_day|$temp_days_left|   $LEADrank ($temp_days_left_points|$POINTS_daysleft_inverse_multiplier)\n";
							}

						# hours-since-last-call points added, and determine if lead should be reset to call again
						$temp_called_ago_hours=0;
						$called_since_last_resetSQL='';
						if ($LEADrank > 0) 
							{
							if ($RClast_call_epoch[$rc] > 1000000) 
								{
								$temp_called_ago_seconds = ($secX - $RClast_call_epoch[$rc]);
								$temp_called_ago_hours = int($temp_called_ago_seconds / 3600);
								if ($temp_called_ago_hours > 0) 
									{$LEADrank = ($LEADrank + $temp_called_ago_hours);}
								}
							# See if the lead is in a live call
							$LEADagent_check=0;
							$stmtA = "SELECT count(*) from vicidial_live_agents where lead_id='$RClead_id[$rc]';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsAGENT=$sthA->rows;
							if ($sthArowsAGENT > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$LEADagent_check =					$aryA[0];
								}
							$sthA->finish();
							if ($LEADagent_check < 1) 
								{
								# See if the lead is already in a live call
								$LEADcall_check=0;
								$stmtA = "SELECT count(*) from vicidial_auto_calls where lead_id='$RClead_id[$rc]';";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArowsHOPPER=$sthA->rows;
								if ($sthArowsHOPPER > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$LEADcall_check =					$aryA[0];
									}
								$sthA->finish();
								if ($LEADcall_check < 1) 
									{
									# See if the lead is already in the hopper
									$LEADhopper_check=0;
									$stmtA = "SELECT count(*) from vicidial_hopper where lead_id='$RClead_id[$rc]';";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsHOPPER=$sthA->rows;
									if ($sthArowsHOPPER > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$LEADhopper_check =					$aryA[0];
										}
									$sthA->finish();
									if ($LEADhopper_check < 1) 
										{
										# See if the lead is already reset to dial
										$LEADreset_check=0;
										$LEADreset_check_ct++;
										$stmtA = "SELECT count(*) from vicidial_list where lead_id='$RClead_id[$rc]' and called_since_last_reset='N';";
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArowsRESET=$sthA->rows;
										if ($sthArowsRESET > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$LEADreset_check =					$aryA[0];
											}
										$sthA->finish();
										if ($LEADreset_check < 1) 
											{
											$called_since_last_resetSQL=",called_since_last_reset='N'";
											$LEADreset_ct++;
											}
										}
									}
								}
							}
						if ($RCrank[$rc] != $LEADrank)
							{
							# Update lead rank and Call-Quota rank
							$stmtC = "UPDATE vicidial_list SET rank='$LEADrank' $called_since_last_resetSQL where lead_id='$RClead_id[$rc]';";
							$affected_rowsC = $dbhA->do($stmtC);
							$sthA->finish();

							$stmtD = "UPDATE vicidial_lead_call_quota_counts SET rank='$LEADrank',modify_date=NOW() where lead_id='$RClead_id[$rc]' and list_id='$DLISTlist_id[$d]';";
							$affected_rowsD = $dbhA->do($stmtD);
							$sthA->finish();

							$event_string = "LEAD RANK: |$LEADrank|$RClead_id[$rc]|$RCfirst_call_date[$rc]|$start_day|   |$temp_total_goal_diff|$temp_called_ago_hours|   |$RCstatus[$rc]|$RCcalled_count[$rc]|   |$RCsession_one_calls[$rc]|$RCsession_two_calls[$rc]|$RCsession_three_calls[$rc]|$RCsession_four_calls[$rc]|$RCsession_five_calls[$rc]|$RCsession_six_calls[$rc]|   |$RCday_one_calls[$rc]|$RCday_two_calls[$rc]|$RCday_three_calls[$rc]|$RCday_four_calls[$rc]|$RCday_five_calls[$rc]|$RCday_six_calls[$rc]|$RCday_seven_calls[$rc]|   |$RCrank[$rc]|   |$affected_rowsC|$stmtC|$affected_rowsD|$stmtD|";   &event_logger;

							$rank_updates++;
							}
						$rc++;
						if ($DB) 
							{
							if ($rc =~ /100$/i) {print STDERR "0     $rc / $sthArowsRANK \r";}
							if ($rc =~ /200$/i) {print STDERR "+     $rc / $sthArowsRANK \r";}
							if ($rc =~ /300$/i) {print STDERR "|     $rc / $sthArowsRANK \r";}
							if ($rc =~ /400$/i) {print STDERR "\\     $rc / $sthArowsRANK \r";}
							if ($rc =~ /500$/i) {print STDERR "-     $rc / $sthArowsRANK \r";}
							if ($rc =~ /600$/i) {print STDERR "/     $rc / $sthArowsRANK \r";}
							if ($rc =~ /700$/i) {print STDERR "|     $rc / $sthArowsRANK \r";}
							if ($rc =~ /800$/i) {print STDERR "+     $rc / $sthArowsRANK \r";}
							if ($rc =~ /900$/i) {print STDERR "0     $rc / $sthArowsRANK \r";}
							if ($rc =~ /0000$/i) 
								{
								print "$rc / $sthArowsRANK   ($rank_updates|$LEADreset_check_ct|$LEADreset_ct|   |$RClead_id[$rc]|$RCfirst_call_date[$rc]|$start_day| \n";
								}
							}
						}
					}
				$d++;
				}

			# Processing for this campaign is done, update campaign call_quota_last_run_date to now
			$stmtC = "UPDATE vicidial_campaigns SET call_quota_last_run_date=NOW(),call_quota_process_running='0' where campaign_id='$campaign_id[$i]';";
			$affected_rowsC = $dbhA->do($stmtC);
			if ($DBX) {print "CAMPAIGN call_quota_last_run_date set to now: $affected_rowsC|$stmtC|\n";}
			$sthA->finish();
			}
		else
			{
			if ($DB) {print "ERROR: CALL QUOTA SETTINGS CONTAINER INCOMPLETE or INVALID: $call_quota_lead_ranking[$i] ($settings_core_score|$settings_session_score) \n$TEMPcontainer_entry  \n";}
			}

		### calculate time to run script ###
		$secY = time();
		$secZ = ($secY - $secC);

		$ENDoutput = "$campaign_id[$i] CAMPAIGN ACTIVE DIALABLE NEW LEADS COUNT:   $active_dialable_NEW_count \n";
		$ENDoutput .= "$campaign_id[$i] CAMPAIGN LISTS RECENTLY DIALED COUNT:       $recent_dial_count \n";
		$ENDoutput .= "$campaign_id[$i] CAMPAIGN LEAD RANK UPDATES:                 $rank_updates \n";
		$ENDoutput .= "$campaign_id[$i] CAMPAIGN LEAD CALLED RESET UPDATES:         $LEADreset_ct ($LEADreset_check_ct) \n";
		if ($DB) {print $ENDoutput."Call Quota run time: $secZ seconds";}

		$stmtA="INSERT IGNORE INTO vicidial_campaign_stats_debug SET server_ip='CALLQUOTA',campaign_id='$campaign_id[$i]',entry_time=NOW(),debug_output='$ENDoutput$RUNoutput' ON DUPLICATE KEY UPDATE entry_time=NOW(),debug_output='$ENDoutput$RUNoutput Call Quota run time: $secZ seconds';";
		$affected_rows = $dbhA->do($stmtA);
		##### END run the Quota Ranking for dialed leads that have been modified since last run #####
		}
	$i++;
	}
####################################################################
##### END Loop through each active campaign and process lists  #####
####################################################################




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





################################################################################
##### SUBROUTINES #####
################################################################################
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


##### Gather settings container for the campaign's Call Quota Lead Ranking settings #####
sub gather_call_quota_settings
	{
	### BEGIN Gather settings container for Call Quota Lead Ranking ###
	$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$TEMPcall_quota_lead_ranking';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$TEMPcontainer_entry = $aryA[0];
		$TEMPcontainer_entry =~ s/\\//gi;
		}
	$sthA->finish();

	# Define variables for Call Quota settings
	$redial_statuses='';
	$total_goal='';
	$daily_goal='';
	$daily_max='';
	$session_goal='';
	$session_day_max='';
	$new_shuffle='';
	$min_consecutive_days='';
	$active_days_of_week='';
	$zero_rank_after_call='';
	$call_quota_run_time='';
	$settings_core_score=0;
	$session_one='';
	$session_two='';
	$session_three='';
	$session_four='';
	$session_five='';
	$session_six='';
	$settings_session_score=0;

	if (length($TEMPcontainer_entry) > 5) 
		{
		@container_lines = split(/\n/,$TEMPcontainer_entry);
		$c=0;
		foreach(@container_lines)
			{
			$container_lines[$c] =~ s/;.*|\r|\t| //gi;
			if (length($container_lines[$c]) > 5)
				{
				# define core settings
				if ($container_lines[$c] =~ /^redial_statuses/i)
					{
					$redial_statuses = $container_lines[$c];
					$redial_statuses =~ s/redial_statuses=>//gi;
					if ( (length($redial_statuses) > 0) && (length($redial_statuses) <= 700) ) 
						{$settings_core_score++;}
					else {$redial_statuses='';}
					}
				if ($container_lines[$c] =~ /^total_goal/i)
					{
					$total_goal = $container_lines[$c];
					$total_goal =~ s/total_goal=>//gi;
					$total_goal =~ s/\D//gi;
					if ( ($total_goal > 0) && ($total_goal <= 100) ) 
						{$settings_core_score++;}
					else {$total_goal='';}
					}
				if ($container_lines[$c] =~ /^daily_goal/i)
					{
					$daily_goal = $container_lines[$c];
					$daily_goal =~ s/daily_goal=>//gi;
					$daily_goal =~ s/\D//gi;
					if ( ($daily_goal > 0) && ($daily_goal <= 20) ) 
						{$settings_core_score++;}
					else {$daily_goal='';}
					}
				if ($container_lines[$c] =~ /^daily_max/i)
					{
					$daily_max = $container_lines[$c];
					$daily_max =~ s/daily_max=>//gi;
					$daily_max =~ s/\D//gi;
					if ( ($daily_max > 0) && ($daily_max <= 20) ) 
						{$settings_core_score++;}
					else {$daily_max='';}
					}
				if ($container_lines[$c] =~ /^session_goal/i)
					{
					$session_goal = $container_lines[$c];
					$session_goal =~ s/session_goal=>//gi;
					$session_goal =~ s/\D//gi;
					if ( ($session_goal > 0) && ($session_goal <= 20) ) 
						{$settings_core_score++;}
					else {$session_goal='';}
					}
				if ($container_lines[$c] =~ /^session_day_max/i)
					{
					$session_day_max = $container_lines[$c];
					$session_day_max =~ s/session_day_max=>//gi;
					$session_day_max =~ s/\D//gi;
					if ( ($session_day_max > 0) && ($session_day_max <= 20) ) 
						{$settings_core_score++;}
					else {$session_day_max='';}
					}
				if ($container_lines[$c] =~ /^new_shuffle/i)
					{
					$new_shuffle = $container_lines[$c];
					$new_shuffle =~ s/new_shuffle=>//gi;
					$new_shuffle =~ s/\D//gi;
					if ( ($new_shuffle > -1) && ($new_shuffle <= 6) ) 
						{
						if ($new_shuffle == 1) {$new_shuffle=2;}
						$settings_core_score++;
						}
					else {$new_shuffle='';}
					}
				if ($container_lines[$c] =~ /^min_consecutive_days/i)
					{
					$min_consecutive_days = $container_lines[$c];
					$min_consecutive_days =~ s/min_consecutive_days=>//gi;
					$min_consecutive_days =~ s/\D//gi;
					if ( ($min_consecutive_days > 0) && ($min_consecutive_days <= 7) ) 
						{$settings_core_score++;}
					else {$min_consecutive_days='';}
					}
				if ($container_lines[$c] =~ /^active_days_of_week/i)
					{
					$active_days_of_week = $container_lines[$c];
					$active_days_of_week =~ s/active_days_of_week=>//gi;
					$active_days_of_week =~ s/\D//gi;
					if ( (length($active_days_of_week) > 0) && (length($active_days_of_week) <= 7) ) 
						{$settings_core_score++;}
					else {$active_days_of_week='';}
					}
				if ($container_lines[$c] =~ /^zero_rank_after_call/i)
					{
					$zero_rank_after_call = $container_lines[$c];
					$zero_rank_after_call =~ s/zero_rank_after_call=>//gi;
					$zero_rank_after_call =~ s/\D//gi;
					if ( (length($zero_rank_after_call) > 0) && (length($zero_rank_after_call) <= 1) ) 
						{$settings_core_score++;}
					else {$zero_rank_after_call='';}
					}
				if ($container_lines[$c] =~ /^call_quota_run_time/i)
					{
					$call_quota_run_time = $container_lines[$c];
					$call_quota_run_time =~ s/call_quota_run_time=>//gi;
					if ( (length($call_quota_run_time) > 0) && (length($call_quota_run_time) <= 70) ) 
						{$settings_core_score++;}
					else {$call_quota_run_time='';}
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

		$session_now=0;
		if ( ($session_one_start <= $now_hourmin) and ($session_one_end > $now_hourmin) ) {$session_now=1;}
		if ( ($session_two_start <= $now_hourmin) and ($session_two_end > $now_hourmin) ) {$session_now=2;}
		if ( ($session_three_start <= $now_hourmin) and ($session_three_end > $now_hourmin) ) {$session_now=3;}
		if ( ($session_four_start <= $now_hourmin) and ($session_four_end > $now_hourmin) ) {$session_now=4;}
		if ( ($session_five_start <= $now_hourmin) and ($session_five_end > $now_hourmin) ) {$session_now=5;}
		if ( ($session_six_start <= $now_hourmin) and ($session_six_end > $now_hourmin) ) {$session_now=6;}


		if ($DB) 
			{
			$DEBUG_settings_output = "Call Quota Lead Ranking settings for $campaign_id[$i] ($call_quota_lead_ranking[$i]) \n";
			$DEBUG_settings_output .= "redial_statuses:      $redial_statuses \n";
			$DEBUG_settings_output .= "total_goal:           $total_goal \n";
			$DEBUG_settings_output .= "daily_goal:           $daily_goal \n";
			$DEBUG_settings_output .= "daily_max:            $daily_max \n";
			$DEBUG_settings_output .= "session_goal:         $session_goal \n";
			$DEBUG_settings_output .= "session_day_max:      $session_day_max \n";
			$DEBUG_settings_output .= "new_shuffle:          $new_shuffle \n";
			$DEBUG_settings_output .= "min_consecutive_days: $min_consecutive_days \n";
			$DEBUG_settings_output .= "active_days_of_week:  $active_days_of_week \n";
			$DEBUG_settings_output .= "zero_rank_after_call: $zero_rank_after_call \n";
			$DEBUG_settings_output .= "call_quota_run_time:  $call_quota_run_time \n";
			$DEBUG_settings_output .= "   settings core score:  $settings_core_score / 11 \n";
			$DEBUG_settings_output .= "session_one:          $session_one ($session_one_start|$session_one_end) \n";
			$DEBUG_settings_output .= "session_two:          $session_two ($session_two_start|$session_two_end)  \n";
			$DEBUG_settings_output .= "session_three:        $session_three ($session_three_start|$session_three_end)  \n";
			$DEBUG_settings_output .= "session_four:         $session_four ($session_four_start|$session_four_end)  \n";
			$DEBUG_settings_output .= "session_five:         $session_five ($session_five_start|$session_five_end)  \n";
			$DEBUG_settings_output .= "session_six:          $session_six ($session_six_start|$session_six_end)  \n";
			$DEBUG_settings_output .= "   sessions score:       $settings_session_score / 6 \n";
			$DEBUG_settings_output .= "session_now:          $session_now \n";
			print "$DEBUG_settings_output";
			}
		}
	else
		{
		if ($DB) {print "ERROR: CALL QUOTA SETTINGS CONTAINER EMPTY: $TEMPcall_quota_lead_ranking|$TEMPcontainer_entry \n";}
		}
	}



##### Generate the SQL segment for the dialable call times #####
sub gmt_sql_generate
	{
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
		
	$stmtA = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times,ct_holidays FROM vicidial_call_times where call_time_id='$SUB_local_call_time';";
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

	$RETURN_default_gmt = "$default_gmt'99'";
	$RETURN_all_gmtSQL = "(gmt_offset_now IN($RETURN_default_gmt) $ct_statesSQL) $ct_state_gmt_SQL";
	$RETURN_del_gmtSQL = "(gmt_offset_now NOT IN($RETURN_default_gmt) $ct_statesSQL) $del_state_gmt_SQL";

	##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###
	}
