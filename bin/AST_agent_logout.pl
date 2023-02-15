#!/usr/bin/perl
#
# AST_agent_logout.pl version 2.14
#
# DESCRIPTION:
# forces logout of agents in a specified campaign or all campaigns
#
# This script is meant to be used in the crontab at scheduled intervals
# 
# EXAMPLE CRONTAB ENTRIES:
#
#	### Force logout at different times
#
#	## 3:15PM and 10:15PM Monday-Thursday
#	15 15,22 * * 1,2,3,4 /usr/share/astguiclient/AST_agent_logout.pl --debugX
#
#	## 8:15PM on Friday
#	15 20 * * 5 /usr/share/astguiclient/AST_agent_logout.pl --debugX
#
#	## 4:15PM on Saturday
#	15 16 * * 6 /usr/share/astguiclient/AST_agent_logout.pl --debugX
#
#	## Every minute between 9:15AM and 10:15PM Monday-Friday with settings container defined
#	* 9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * 1,2,3,4,5 /usr/share/astguiclient/AST_agent_logout.pl --container=XXX
#
# EXAMPLE Settings Container Entry:
#   dial_prefix => 444
#   user_code => backoffice_agent
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 80112-0330 - First Build
# 91129-2138 - Replace SELECT STAR in SQL statement, fixed other formatting
# 140426-1950 - Added pause_type
# 200412-1037 - Updated to match web emergency logout, with QM logging
# 210811-1630 - Added option to logout agents only within campaigns/users with a specific dial_prefix/user_code
# 221202-1639 - Change in CIDname prefix to differentiate from other processes
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, On an active system this will generate lots of lines of output per minute
$US='__';
$MT[0]='';

$secT = time();
$now_date_epoch = $secT;
$check_time = ($now_date_epoch - 86400);
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$Fhour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$file_date = "$year-$mon-$mday";
$now_date = "$year-$mon-$mday $hour:$min:$sec";
$VDL_date = "$year-$mon-$mday 00:00:01";
$inactive_epoch = ($secT - 60);

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
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--test] = test\n";
		print "  [--campaign=XXX] = run for campaign XXX only\n";
		print "  [--container=XXX] = optional, the ID of the settings container to run from the system\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
				$CLIcampaign = $data_in[1];
				$CLIcampaign =~ s/ .*$//gi;
			}
		else
			{$CLIcampaign = '';}
		if ($args =~ /--container=/i)
			{
			@data_in = split(/--container=/,$args);
			$container = $data_in[1];
			$container =~ s/ .*$//gi;
			if ($DB > 0) {print "\n----- SETTINGS CONTAINER: $container -----\n\n";}
			}
		else
			{$container = '';}
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

if (!$VDALOGfile) {$VDALOGfile = "$PATHlogs/agentlogout.$year-$mon-$mday";}
if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### Grab Server values from the database
$stmtA = "SELECT vd_server_logs,local_gmt FROM servers where server_ip = '$VARserver_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
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



### Grab container content from the database
$container_sql='';
$CAMP_sql='';
$USER_sql='';
if (length($container) > 0) 
	{
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
			$container_lines[$i] =~ s/;.*|\r|\t//gi;
			$container_lines[$i] =~ s/ => |=> | =>/=>/gi;
			if (length($container_lines[$i])>5)
				{
				if ($container_lines[$i] =~ /^dial_prefix|^manual_dial_prefix|^campaign_description|^user_code|^custom_1|^custom_2|^custom_3|^custom_4|^custom_5/i)
					{
					if ($container_lines[$i] =~ /^dial_prefix/i)
						{
						$container_lines[$i] =~ s/^dial_prefix=>//gi;
						if (length($CAMP_sql) > 10) {$CAMP_sql .= " or "}
						$CAMP_sql .= "(dial_prefix='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |dial_prefix|$container_lines[$i]|$CAMP_sql|\n";}
						}
					if ($container_lines[$i] =~ /^manual_dial_prefix/i)
						{
						$container_lines[$i] =~ s/^manual_dial_prefix=>//gi;
						if (length($CAMP_sql) > 10) {$CAMP_sql .= " or "}
						$CAMP_sql .= "(manual_dial_prefix='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |manual_dial_prefix|$container_lines[$i]|$CAMP_sql|\n";}
						}
					if ($container_lines[$i] =~ /^campaign_description/i)
						{
						$container_lines[$i] =~ s/^campaign_description=>//gi;
						if (length($CAMP_sql) > 10) {$CAMP_sql .= " or "}
						$CAMP_sql .= "(campaign_description='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |campaign_description|$container_lines[$i]|$CAMP_sql|\n";}
						}
					if ($container_lines[$i] =~ /^user_code/i)
						{
						$container_lines[$i] =~ s/^user_code=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(user_code='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |user_code|$container_lines[$i]|$USER_sql|\n";}
						}
					if ($container_lines[$i] =~ /^custom_1/i)
						{
						$container_lines[$i] =~ s/^custom_1=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(custom_one='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |custom_1|$container_lines[$i]|$USER_sql|\n";}
						}
					if ($container_lines[$i] =~ /^custom_2/i)
						{
						$container_lines[$i] =~ s/^custom_2=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(custom_two='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |custom_2|$container_lines[$i]|$USER_sql|\n";}
						}
					if ($container_lines[$i] =~ /^custom_3/i)
						{
						$container_lines[$i] =~ s/^custom_3=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(custom_three='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |custom_3|$container_lines[$i]|$USER_sql|\n";}
						}
					if ($container_lines[$i] =~ /^custom_4/i)
						{
						$container_lines[$i] =~ s/^custom_4=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(custom_four='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |custom_4|$container_lines[$i]|$USER_sql|\n";}
						}
					if ($container_lines[$i] =~ /^custom_5/i)
						{
						$container_lines[$i] =~ s/^custom_5=>//gi;
						if (length($USER_sql) > 10) {$USER_sql .= " or "}
						$USER_sql .= "(custom_five='$container_lines[$i]')";
						if ($DBX) {print "DEBUG 1: |custom_5|$container_lines[$i]|$USER_sql|\n";}
						}
					}
				else
					{if ($DBX > 0) {print "     not allowed config: $i|$container_lines[$i]|\n";}}
				}
			else
				{if ($DBX > 0) {print "     blank line: $i|$container_lines[$i]|\n";}}
			$i++;
			}
		}
	else
		{
		if ($Q < 1)
			{print "ERROR: SETTINGS CONTAINER EMPTY: $container $container_sql\n";}
		}

	$camp_select_sql='';
	if (length($CAMP_sql) > 5) 
		{
		$stmtA = "SELECT campaign_id from vicidial_campaigns where $CAMP_sql order by campaign_id desc LIMIT 1000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$cct=0;
		while ($sthArows > $cct)
			{
			@aryA = $sthA->fetchrow_array;
			if ($cct > 0) {$camp_select_sql .= ",";}
			$camp_select_sql .=		"'$aryA[0]'";
			$cct++;
			}
		$sthA->finish();
		if ($cct > 0) {$camp_select_sql = "and campaign_id IN($camp_select_sql)";}
		}

	$user_select_sql='';
	if (length($USER_sql) > 5) 
		{
		$stmtA = "SELECT user from vicidial_users where $USER_sql order by user desc LIMIT 100000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		$cct=0;
		while ($sthArows > $cct)
			{
			@aryA = $sthA->fetchrow_array;
			if ($cct > 0) {$user_select_sql .= ",";}
			$user_select_sql .=		"'$aryA[0]'";
			$cct++;
			}
		$sthA->finish();
		if ($cct > 0) {$user_select_sql = "and user IN($user_select_sql)";}
		}
	}


@user=@MT; 

if ($CLIcampaign)
	{
	$stmtA = "SELECT user,server_ip,status,lead_id,campaign_id,uniqueid,callerid,channel,last_call_time,UNIX_TIMESTAMP(last_update_time),last_call_finish,closer_campaigns,call_server_ip,conf_exten from vicidial_live_agents where user!='' and campaign_id='$CLIcampaign' $camp_select_sql $user_select_sql;";
	}
else
	{
	$stmtA = "SELECT user,server_ip,status,lead_id,campaign_id,uniqueid,callerid,channel,last_call_time,UNIX_TIMESTAMP(last_update_time),last_call_finish,closer_campaigns,call_server_ip,conf_exten from vicidial_live_agents where user!='' $camp_select_sql $user_select_sql;";
	}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($DBX) {print "DEBUG2, AGENTS SELECT SQL:  $sthArows|$stmtA|\n";}
$rec_count=0;
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$user[$rec_count] =				$aryA[0];
	$server_ip[$rec_count] =		$aryA[1];
	$status[$rec_count] =			$aryA[2];
	$lead_id[$rec_count] =			$aryA[3];
	$campaign_id[$rec_count] =		$aryA[4];
	$uniqueid[$rec_count] =			$aryA[5];
	$callerid[$rec_count] =			$aryA[6];
	$channel[$rec_count] =			$aryA[7];
	$last_call_time[$rec_count] =	$aryA[8];
	$last_update_time[$rec_count] =	$aryA[9];
	$last_call_finish[$rec_count] =	$aryA[10];
	$closer_campaigns[$rec_count] =	$aryA[11];
	$call_server_ip[$rec_count] =	$aryA[12];
	$conf_exten[$rec_count] =		$aryA[13];

	$rec_count++;
	}
$sthA->finish();
if ($DB) {print "AGENTS TO LOGOUT:  $rec_count\n";}

##### LOOP THROUGH EACH AGENT(USER) AND LOG THEM OUT #####
$i=0;
$output='';
while($rec_count > $i)
	{
	### attempt to gracefully update the timers in the logs before logging out the agent
	if ($last_update_time[$i] > $inactive_epoch)
		{
		$lead_active=0;
		$stmtA = "SELECT agent_log_id,user,server_ip,event_time,lead_id,campaign_id,pause_epoch,pause_sec,wait_epoch,wait_sec,talk_epoch,talk_sec,dispo_epoch,dispo_sec,status,user_group,comments,sub_status,dead_epoch,dead_sec from vicidial_agent_log where user='$user[$i]' and campaign_id='$campaign_id[$i]' order by agent_log_id desc LIMIT 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$agent_log_id[$i] =		$aryA[0];
			$pause_epoch[$i] =		$aryA[6];
			$pause_sec[$i] =		$aryA[7];
			$wait_epoch[$i] =		$aryA[8];
			$wait_sec[$i] =			$aryA[9];
			$talk_epoch[$i] =		$aryA[10];
			$talk_sec[$i] =			$aryA[11];
			$dispo_epoch[$i] =		$aryA[12];
			$dispo_sec[$i] =		$aryA[13];
			$lead_status[$i] =		$row[14];
			$user_group[$i] =		$aryA[15];
			}
		$sthA->finish();

		if ( ($wait_epoch[$i] < 1) || ( ($status[$i] =~ /PAUSE/) && ($dispo_epoch[$i] < 1) ) )
			{
			$pause_sec = ( ($now_date_epoch - $pause_epoch[$i]) + $pause_sec[$i]);
			$stmtA = "UPDATE vicidial_agent_log SET wait_epoch='$now_date_epoch', pause_sec='$pause_sec', pause_type='SYSTEM' where agent_log_id='$agent_log_id[$i]';";
			}
		else
			{
			if ($talk_epoch[$i] < 1)
				{
				$wait_sec = ( ($now_date_epoch - $wait_epoch[$i]) + $wait_sec[$i]);
				$stmtA = "UPDATE vicidial_agent_log SET talk_epoch='$now_date_epoch', wait_sec='$wait_sec' where agent_log_id='$agent_log_id[$i]';";
				}
			else
				{
				$lead_active++;
				$status_update_SQL='';
				if ( ( (length($lead_status[$i]) < 1) or ($lead_status[$i] eq 'NULL') ) and ($lead_id[$i] > 0) )
					{
					$status_update_SQL = ", status='PU'";
					$stmtB="UPDATE vicidial_list SET status='PU' where lead_id='$lead_id[$i]';";
					if ($DBX) {print "<BR>$stmtB\n";}
					$affected_rowsB = $dbhA->do($stmtB);
					$event_string = "   AGENT LEAD UPDATE|$affected_rowsB|$stmtB|";
						&event_logger;
					$output .= "$affected_rowsB|$stmtB\n";
					}
				if ($dispo_epoch[$i] < 1)
					{
					$talk_sec[$i] = ($now_date_epoch - $talk_epoch[$i]);
					$stmtA = "UPDATE vicidial_agent_log SET dispo_epoch='$now_date_epoch', talk_sec='$talk_sec[$i]'$status_update_SQL where agent_log_id='$agent_log_id[$i]';";
					}
				else
					{
					if ($dispo_sec[$i] < 1)
						{
						$dispo_sec[$i] = ($now_date_epoch - $dispo_epoch[$i]);
						$stmtA = "UPDATE vicidial_agent_log SET dispo_sec='$dispo_sec[$i]' where agent_log_id='$agent_log_id[$i]';";
						}
					}
				}
			}
		$affected_rowsA = $dbhA->do($stmtA);
		if ($DBX) {print "UPDATING VAL RECORD:    $affected_rowsA  |$stmtA|\n";}
			$event_string = "AGENT UPDATE|$affected_rowsA|$stmtA|";
			&event_logger;
		$output .= "$affected_rowsA|$stmtA\n";
		}

	$stmtA = "DELETE from vicidial_live_agents where user='$user[$i]' and campaign_id='$campaign_id[$i]' order by live_agent_id LIMIT 1;";
	$affected_rows = $dbhA->do($stmtA);
	if ($DBX) {print "VLA record Deleted:     $affected_rows  $user[$1] $campaign_id[$i] $status[$i] $last_call_time[$i]\n";}
		$event_string = "AGENT LOGOUT|$user[$1]|$campaign_id[$i]|$status[$i]|$last_call_time[$i]";
		&event_logger;
	$output .= "$affected_rows|$stmtA\n";

	### Insert logout record for this user
	if (length($user_group[$i])<1)
		{
		$stmtA = "SELECT user_group FROM vicidial_users where user='$user[$i]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$user_group[$i] =		"$aryA[0]";
			}
		$sthA->finish();
		}

	$stmtD = "INSERT INTO vicidial_user_log (user,event,campaign_id,event_date,event_epoch,user_group) values('$user[$i]','LOGOUT','$campaign_id[$i]','$now_date','$now_date_epoch','$user_group[$i]');";
	$affected_rowsD = $dbhA->do($stmtD);
	$output .= "$affected_rowsD|$stmtD\n";

	### Hangup all calls in the agent's session
	$local_DEF = 'Local/5555';
	$local_AMP = '@';
	$ext_context = 'default';
	$kick_local_channel = "$local_DEF$conf_exten[$i]$local_AMP$ext_context";
	$queryCID = "ULAL3458$now_date_epoch";

	$stmtC="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip[$i]','','Originate','$queryCID','Channel: $kick_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','','');";
	$affected_rowsC = $dbhA->do($stmtC);
	$output .= "$affected_rowsC|$stmtC\n";
		$event_string = "AGENT SESSION CALLS HANGUP|$user[$1]|$server_ip[$i]|$conf_exten[$i]|$queryCID";
		&event_logger;

	##### BEGIN Queuemetrics logging #####

	#############################################
	##### START QUEUEMETRICS LOGGING LOOKUP #####
	$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_eq_prepend,queuemetrics_loginout,queuemetrics_dispo_pause,queuemetrics_pause_type FROM system_settings;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$enable_queuemetrics_logging =	$aryA[0];
		$queuemetrics_server_ip	=	$aryA[1];
		$queuemetrics_dbname =		$aryA[2];
		$queuemetrics_login=		$aryA[3];
		$queuemetrics_pass =		$aryA[4];
		$queuemetrics_log_id =		$aryA[5];
		$queuemetrics_eq_prepend =	$aryA[6];
		$queuemetrics_loginout =	$aryA[7];
		$queuemetrics_dispo_pause = $aryA[8];
		$queuemetrics_pause_type =	$aryA[9];
		}
	$sthA->finish();
	##### END QUEUEMETRICS LOGGING LOOKUP #####
	###########################################

	if ($enable_queuemetrics_logging > 0)
		{
		if ($DB) {print " - Checking queue_log in-queue calls in ViciDial\n";}

		$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
		 or die "Couldn't connect to database: " . DBI->errstr;

		if ($DBX) {print "CONNECTED TO QM DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

		$QM_LOGOFF = 'AGENTLOGOFF';
		if ($queuemetrics_loginout eq 'CALLBACK')
			{$QM_LOGOFF = 'AGENTCALLBACKLOGOFF';}
		$agents='@agents';
		$agent_logged_in='';
		$time_logged_in='0';

		$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/$user[$i]' and verb IN('AGENTLOGIN','AGENTCALLBACKLOGIN') and time_id > $check_time order by time_id desc limit 1;";

		if ($queuemetrics_loginout eq 'NONE')
			{
			$pause_typeSQL='';
			if ($queuemetrics_pause_type > 0)
				{$pause_typeSQL=",data5='ADMIN'";}
			$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='NONE',agent='Agent/$user[$i]',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LOGOFF'$pause_typeSQL;";
			$affected_rowsB = $dbhB->do($stmtB);
			$output .= "$affected_rowsB|$stmtB\n";
				$event_string = "QM PAUSE LOGOUT|$user[$1]|$affected_rowsB|$stmtB";
				&event_logger;

			$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/$user[$i]' and verb IN('ADDMEMBER','ADDMEMBER2') and time_id > $check_time order by time_id desc limit 1;";
			}

		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		if ($sthBrows > 0)
			{
			@aryB = $sthB->fetchrow_array;
			$agent_logged_in =		$aryB[0];
			$time_logged_in =		$aryB[1];
			$RAWtime_logged_in =	$aryB[1];
			$phone_logged_in =		$aryB[2];
			}
		$sthB->finish();

		$time_logged_in = ($now_date_epoch - $time_logged_in);
		if ($time_logged_in > 1000000) {$time_logged_in=1;}

		if ($DBX) {print "QM DEBUG: |$user[$1]($agent_logged_in)|$sthBrows|$stmtB";}

		if ($queuemetrics_addmember_enabled > 0)
			{
			$queuemetrics_phone_environment='';
			$stmtB = "SELECT queuemetrics_phone_environment FROM vicidial_campaigns where campaign_id='$campaign_id[$i]';";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$cqpe_ct=$sthB->rows;
			if ($cqpe_ct > 0)
				{
				@aryB = $sthB->fetchrow_array;
				$queuemetrics_phone_environment =	$aryB[0];
				}
			$sthB->finish();

				$stmt = "SELECT distinct queue FROM queue_log where time_id >= $RAWtime_logged_in and agent='$agent_logged_in' and verb IN('ADDMEMBER','ADDMEMBER2') and queue != '$campaign_id[$i]' order by time_id desc;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$amq_conf_ct=$sthB->rows;
			$qmr=0;
			while ($amq_conf_ct > $qmr)
				{
				@aryB = $sthB->fetchrow_array;
				$AMqueue[$qmr] =		$aryB[0];
				$qmr++;
				}
			$sthB->finish();

			### add the logged-in campaign as well
			$AMqueue[$qmr] = $campaign_id[$i];
			$qmr++;
			$amq_conf_ct++;

			$qmr=0;
			while ($qmr < $amq_conf_ct)
				{
				$pe_append='';
				if ( ($queuemetrics_pe_phone_append > 0) and (length($queuemetrics_phone_environment)>0) )
					{
					@qm_extension = split(/\//,$phone_logged_in);
					$pe_append = "-$qm_extension[1]";
					}
				$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='$AMqueue[$qmr]',agent='$agent_logged_in',verb='REMOVEMEMBER',data1='$phone_logged_in',serverid='$queuemetrics_log_id',data4='$queuemetrics_phone_environment$pe_append';";
				$affected_rowsB = $dbhB->do($stmtB);
				$output .= "$affected_rowsB|$stmtB\n";
					$event_string = "QM REMOVEMEMBER|$user[$1]|$affected_rowsB|$stmtB";
					&event_logger;
				$qmr++;
				}
			}

		if ($queuemetrics_loginout ne 'NONE')
			{
			$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='NONE',agent='$agent_logged_in',verb='$QM_LOGOFF',serverid='$queuemetrics_log_id',data1='$phone_logged_in',data2='$time_logged_in';";
			$affected_rowsB = $dbhB->do($stmtB);
			$output .= "$affected_rowsB|$stmtB\n";
				$event_string = "QM $QM_LOGOFF|$user[$1]|$affected_rowsB|$stmtB";
				&event_logger;
			}
		}
	##### END Queuemetrics logging #####
	$event_string = "USER LOGOFF COMPLETE: $user[$1] $campaign_id[$i] $user_group[$i]";
	&event_logger;

	$i++;
	}

if ($i > 0) 
	{
	$output =~ s/;|\"//gi;
	$stmtB="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='USERS', event_type='OTHER', record_id='$CLIcampaign', event_code='CLI USERS LOGOUT', event_sql=\"$output\", event_notes='Users logged out: $i executed';";
	if (!$T) {	$Iaffected_rows = $dbhA->do($stmtB);}
	if ($DBX) {print " - admin log insert debug: |$Iaffected_rows|$stmtB|\n";}
	}
else
	{
	if ($DB) {print "No users to log out: $i\n";}
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



sub event_logger
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$VDALOGfile")
				|| die "Can't open $VDALOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}

