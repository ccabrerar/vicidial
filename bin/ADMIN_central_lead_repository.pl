#!/usr/bin/perl
#
# ADMIN_central_lead_repository.pl	version 2.14
#
# This script is designed to gather recently changed log entries and look up the lead data for those logs and update a central vicidial_list table on a separate database server. Then after a set cut-off time, check for the previous X hours of leads being modified and update anything that is missing and provide a report. Then early in the morning the changed leads will be sync'd back to the original database server
#
# This script also can sync settings from campaigns, lists, dids, in-groups and users to the CLR database
# 
# WARNING!!! This script is experimental and is not recommended for production systems at this time
#
# NOTE: for more information on using this in a multi-cluster situation, read the MySQL_Multi-source-replication.txt document
# NOTE: this script makes use of some non-standard database fields, to use it you may need to run these, which you should not do during production:
#
# ALTER TABLE vicidial_log ADD modify_stamp TIMESTAMP;
# ALTER TABLE vicidial_closer_log ADD modify_stamp TIMESTAMP;
#
# Place in the crontab and run every month after one in the morning, or whenever
# your server is not busy with other tasks
# 30 1 1 * * /usr/share/astguiclient/ADMIN_central_lead_repository.pl
#
# Copyright (C) 2025  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 241018-1001 - First version work started, ongoing development
#

$DB=0;
$DBX=0;
$CALC_TEST=0;
$QUERY_COUNT_TEST=0;
$NIGHTLY_UPDATE=0;
$BACK_TO_CLUSTERS=0;
$SETTINGS_TO_CLR=0;
$ALL_SETTINGS=0;
$T=0;   $TEST=0;
$MT[0]='';
$u_ct=0;
$process_output='';

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
		print "  [--minutes=XX] = number of minutes to gather leads for, default is 1 \n";
		print "  [--quiet] = quiet\n";
		print "  [--calc-test] = date calculation test only\n";
		print "  [--query-count-test] = run archive counts test only\n";
		print "  [--nightly-update] = update CLR leads with all modified leads from ORIGINAL db \n";
		print "  [--back-to-clusters] = update ORIGINAL leads with all modified leads from CLR db \n";
		print "  [--settings-to-clr] = update ORIGINAL settings to the CLR db \n";
		print "  [--all-settings] = update all settings instead of by date \n";
		print "  [--test] = test\n";
		print "  [--debug] = debug output for some options\n";
		print "  [--debugX] = extra debug output for some options\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- DEBUG EXTRA -----\n\n";
			}
		if ($args =~ /--calc-test/i)
			{
			$CALC_TEST=1;
			print "\n----- DATE CALCULATION TESTING ONLY: $CALC_TEST -----\n\n";
			}
		if ($args =~ /--query-count-test/i)
			{
			$QUERY_COUNT_TEST=1;
			print "\n----- ARCHIVE TABLES QUERY COUNT TESTING ONLY: $QUERY_COUNT_TEST -----\n\n";
			}
		if ($args =~ /--nightly-update/i)
			{
			$NIGHTLY_UPDATE=1;
			print "\n----- NIGHTLY CLR UPDATE: $NIGHTLY_UPDATE -----\n\n";
			}
		if ($args =~ /--back-to-clusters/i)
			{
			$BACK_TO_CLUSTERS=1;
			print "\n----- BACK TO CLUSTERS UPDATE: $BACK_TO_CLUSTERS -----\n\n";
			}
		if ($args =~ /--settings-to-clr/i)
			{
			$SETTINGS_TO_CLR=1;
			print "\n----- SETTINGS TO CLR UPDATE: $SETTINGS_TO_CLR -----\n\n";
			}
		if ($args =~ /--all-settings/i)
			{
			$ALL_SETTINGS=1;
			print "\n----- ALL SETTINGS OVERRIDE: $ALL_SETTINGS -----\n\n";
			}
		if ($args =~ /--minutes=/i)
			{
			@data_in = split(/--minutes=/,$args);
			$CLIminutes = $data_in[1];
			$CLIminutes =~ s/ .*$//gi;
			$CLIminutes =~ s/\D//gi;
			if ($CLIminutes > 999999)
				{$CLIminutes=730;}
			if ($Q < 1) 
				{print "\n----- MINUTES OVERRIDE: $CLIminutes -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###
if ( (length($CLIminutes)<1) || ($CLIminutes < 1) )
	{
	$CLIminutes = 1;
	}

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

$now_epoch = ($secX);   # now
($Nsec,$Nmin,$Nhour,$Nmday,$Nmon,$Nyear,$Nwday,$Nyday,$Nisdst) = localtime($now_epoch);
$Nyear = ($Nyear + 1900);
$Nmon++;
if ($Nmon < 10) {$Nmon = "0$Nmon";}
if ($Nmday < 10) {$Nmday = "0$Nmday";}
if ($Nhour < 10) {$Nhour = "0$Nhour";}
if ($Nmin < 10) {$Nmin = "0$Nmin";}
if ($Nsec < 10) {$Nsec = "0$Nsec";}
$now_time = "$Nyear-$Nmon-$Nmday $Nhour:$Nmin:$Nsec";
$now_date = "$Nyear-$Nmon-$Nmday";
$now_test_date = "$Nyear$Nmon$Nmday$Nhour$Nmin$Nsec";

$del_epoch = ($secX - 60);   # 1 minute ago 0 seconds
($RMsec,$RMmin,$RMhour,$RMmday,$RMmon,$RMyear,$RMwday,$RMyday,$RMisdst) = localtime($del_epoch);
$RMyear = ($RMyear + 1900);
$RMmon++;
if ($RMmon < 10) {$RMmon = "0$RMmon";}
if ($RMmday < 10) {$RMmday = "0$RMmday";}
if ($RMhour < 10) {$RMhour = "0$RMhour";}
if ($RMmin < 10) {$RMmin = "0$RMmin";}
if ($RMsec < 10) {$RMsec = "0$RMsec";}
$del_time = "$RMyear-$RMmon-$RMmday $RMhour:$RMmin:00";

$Bdel_epoch = (($secX - (60 * $CLIminutes)) - 60);   # X+1 minutes ago 0 seconds
($BRMsec,$BRMmin,$BRMhour,$BRMmday,$BRMmon,$BRMyear,$BRMwday,$BRMyday,$BRMisdst) = localtime($Bdel_epoch);
$BRMyear = ($BRMyear + 1900);
$BRMmon++;
if ($BRMmon < 10) {$BRMmon = "0$BRMmon";}
if ($BRMmday < 10) {$BRMmday = "0$BRMmday";}
if ($BRMhour < 10) {$BRMhour = "0$BRMhour";}
if ($BRMmin < 10) {$BRMmin = "0$BRMmin";}
if ($BRMsec < 10) {$BRMsec = "0$BRMsec";}
$Bdel_time = "$BRMyear-$BRMmon-$BRMmday $BRMhour:$BRMmin:00";

if (!$Q) {print "\n\n-- ADMIN_central_lead_repository.pl --\n\n";}
if (!$Q) {print "This script is designed take recently modified lead records from the vicidial_list\n";}
if (!$Q) {print "table and sync them to a central lead repository server during the day, then confirm\n";}
if (!$Q) {print "at night, then push back any updated records from the CLR back to the cluster database\n";}
if (!$Q) {print "early in the morning.\n";}
$begin_output = "$CLIminutes minutes ($Bdel_time -> $del_time |$Bdel_epoch$del_epoch| )    NOW: $now_time \n";
if (!$Q) {print "$begin_output\n";}

$process_output .= $begin_output;

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
	if ( ($line =~ /^VARCS_server/) && ($CLICS_server < 1) )
		{$VARCS_server = $line;   $VARCS_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_database/) && ($CLICS_database < 1) )
		{$VARCS_database = $line;   $VARCS_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_user/) && ($CLICS_user < 1) )
		{$VARCS_user = $line;   $VARCS_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_pass/) && ($CLICS_pass < 1) )
		{$VARCS_pass = $line;   $VARCS_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_port/) && ($CLICS_port < 1) )
		{$VARCS_port = $line;   $VARCS_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

# default path to Central Lead Repository configuration file:
$PATHclr =		'/etc/vicidial_CLR.conf';

open(conf, "$PATHclr") || die "can't open $PATHclr: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^VARCLR_server/) && ($CLICLR_server < 1) )
		{$VARCLR_server = $line;   $VARCLR_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARCLR_database/) && ($CLICLR_database < 1) )
		{$VARCLR_database = $line;   $VARCLR_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARCLR_user/) && ($CLICLR_user < 1) )
		{$VARCLR_user = $line;   $VARCLR_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARCLR_pass/) && ($CLICLR_pass < 1) )
		{$VARCLR_pass = $line;   $VARCLR_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARCLR_port/) && ($CLICLR_port < 1) )
		{$VARCLR_port = $line;   $VARCLR_port =~ s/.*=//gi;}
	$i++;
	}

use DBI;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARCLR_database:$VARCLR_server:$VARCLR_port", "$VARCLR_user", "$VARCLR_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

#############################################
##### Gather system_settings #####
$stmtA = "SELECT count(*) FROM vicidial_list;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$MAINvl_ct =	$aryA[0];
	if ($DB > 0) {print "MAIN Database test: $MAINvl_ct \n";}
	}
$sthA->finish();
###########################################

#############################################
##### Test Central-Lead-Repository Database connection with query of vicidial_list table #####
$stmtB = "SELECT count(*) FROM vicidial_list;";
$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
$sthBrows=$sthB->rows;
if ($sthBrows > 0)
	{
	@aryB = $sthB->fetchrow_array;
	$CLRvl_ct =	$aryB[0];
	if ($DB > 0) {print "CLR Database test:  $CLRvl_ct \n";}
	}
$sthB->finish();
###########################################

if ($CALC_TEST > 0)
	{
	exit;
	}



################################################################################
##### BEGIN settings update ORIGINAL to CLR db updates: campaigns, lists, dids, in-groups, users, etc...
################################################################################
if ($SETTINGS_TO_CLR > 0) 
	{
	if (!$Q) {print "\nStarting settings ORIGINAL to CLR update process...\n";}

	$clr_id='';
	$process_options='';

	# insert clr_log entry into database
	$stmtA = "INSERT INTO clr_log SET start_time=NOW(),begin_range='$Bdel_time',range_minutes='$CLIminutes',phase='BEGIN',server_ip='$server_ip',processing_log='';";
	$affected_rowsA = $dbhA->do($stmtA);
	$stmtC = "SELECT LAST_INSERT_ID() LIMIT 1;";
	$sthA = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtC ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$clr_id = $aryA[0];
		}
	$sthA->finish();
	if ($DB) {print "clr_id: $clr_id \n";}

	$phase='SETTINGS_UPDATE';

	#####################################
	### BEGIN update each setting type
	#####################################

	$v=0;
	$temp_type_ARY[$v] = 'users';
	$temp_typeS_ARY[$v] = 'user';
	$temp_table_ARY[$v] = 'vicidial_users';
	$temp_id_field_ARY[$v] = 'user';
	$temp_date_check_SQL_ARY[$v] = "modify_stamp >= \"$Bdel_time\"";
	$temp_first_fields_ARY[$v] = "user,modify_stamp,UNIX_TIMESTAMP(modify_stamp)";
	$temp_date_field_ARY[$v] = 'modify_stamp';
	$fields_skip_list_ARY[$v] = "user,active";
	$fields_insert_list_ARY[$v] = ",active='N'";
	$SUBS_tables_list_ARY[$v] = "vicidial_inbound_group_agents,vicidial_campaign_agents";
	$SUBS_delete_first_ARY[$v] = 1;
	$SUBS_tables_details_ARY[$v] = "vicidial_inbound_group_agents---group_id,vicidial_campaign_agents---campaign_id";

	$v++;
	$temp_type_ARY[$v] = 'lists';
	$temp_typeS_ARY[$v] = 'list';
	$temp_table_ARY[$v] = 'vicidial_lists';
	$temp_id_field_ARY[$v] = 'list_id';
	$temp_date_check_SQL_ARY[$v] = "list_changedate >= \"$Bdel_time\"";
	$temp_first_fields_ARY[$v] = "list_id,list_changedate,UNIX_TIMESTAMP(list_changedate)";
	$temp_date_field_ARY[$v] = 'list_changedate';
	$fields_skip_list_ARY[$v] = "list_id,active";
	$fields_insert_list_ARY[$v] = ",active='N'";
	$SUBS_tables_list_ARY[$v] = "";
	$SUBS_delete_first_ARY[$v] = 0;
	$SUBS_tables_details_ARY[$v] = "";

	$v++;
	$temp_type_ARY[$v] = 'dids';
	$temp_typeS_ARY[$v] = 'did';
	$temp_table_ARY[$v] = 'vicidial_inbound_dids';
	$temp_id_field_ARY[$v] = 'did_id';
	$temp_date_check_SQL_ARY[$v] = "modify_stamp >= \"$Bdel_time\"";
	$temp_first_fields_ARY[$v] = "did_id,modify_stamp,UNIX_TIMESTAMP(modify_stamp)";
	$temp_date_field_ARY[$v] = 'modify_stamp';
	$fields_skip_list_ARY[$v] = "did_id,did_active";
	$fields_insert_list_ARY[$v] = ",did_active='N'";
	$SUBS_tables_list_ARY[$v] = "";
	$SUBS_delete_first_ARY[$v] = 0;
	$SUBS_tables_details_ARY[$v] = "";

	$v++;
	$temp_type_ARY[$v] = 'in-groups';
	$temp_typeS_ARY[$v] = 'in-group';
	$temp_table_ARY[$v] = 'vicidial_inbound_groups';
	$temp_id_field_ARY[$v] = 'group_id';
	$temp_date_check_SQL_ARY[$v] = "modify_stamp >= \"$Bdel_time\"";
	$temp_first_fields_ARY[$v] = "group_id,modify_stamp,UNIX_TIMESTAMP(modify_stamp)";
	$temp_date_field_ARY[$v] = 'modify_stamp';
	$fields_skip_list_ARY[$v] = "group_id,active";
	$fields_insert_list_ARY[$v] = ",active='N'";
	$SUBS_tables_list_ARY[$v] = "";
	$SUBS_delete_first_ARY[$v] = 0;
	$SUBS_tables_details_ARY[$v] = "";

	$v++;
	$temp_type_ARY[$v] = 'campaigns';
	$temp_typeS_ARY[$v] = 'campaign';
	$temp_table_ARY[$v] = 'vicidial_campaigns';
	$temp_id_field_ARY[$v] = 'campaign_id';
	$temp_date_check_SQL_ARY[$v] = "campaign_changedate >= \"$Bdel_time\"";
	$temp_first_fields_ARY[$v] = "campaign_id,campaign_changedate,UNIX_TIMESTAMP(campaign_changedate)";
	$temp_date_field_ARY[$v] = 'campaign_changedate';
	$fields_skip_list_ARY[$v] = "campaign_id,active";
	$fields_insert_list_ARY[$v] = ",active='N'";
	$SUBS_tables_list_ARY[$v] = "vicidial_campaign_statuses,vicidial_campaign_hotkeys";
	$SUBS_delete_first_ARY[$v] = 1;
	$SUBS_tables_details_ARY[$v] = "vicidial_campaign_statuses---status,vicidial_campaign_hotkeys---hotkey";

	# generate list of local affected tables, so we can back them up before starting sync
	$e_ct=0;
	while ($v >= $e_ct) 
		{
		if ($e_ct > 0) {$dump_table_list .= " ";}
		$dump_table_list .= "$temp_table_ARY[$e_ct]";
		if (length($SUBS_tables_list_ARY[$e_ct]) > 0) 
			{
			$dump_table_list .= " $SUBS_tables_list_ARY[$e_ct]";
			}
		$e_ct++;
		}
	$dump_table_list =~ s/,/ /gi;
	
	print "DEBUG: dump tables: |$dump_table_list|\n";

	$v=0;
	&populate_main_section;

	$secY = time();
	$secZ = ($secY - $secX);

print "DEV EXIT! $secZ seconds\n";
exit;

	# insert clr_log entry into database
	$stmtA = "UPDATE clr_log SET phase='$phase',records_ct='$u_ct',length_in_sec='$secZ',processing_log='$process_output',options='$process_options' where clr_id='$clr_id';";
	$affected_rowsA = $dbhA->do($stmtA);


	#####################################
	### END update each setting type
	#####################################


	$secY = time();
	$secZ = ($secY - $secX);

	# insert clr_log entry into database
	$stmtA = "UPDATE clr_log SET phase='$phase',records_ct='$u_ct',length_in_sec='$secZ',processing_log='$process_output',options='$process_options' where clr_id='$clr_id';";
	$affected_rowsA = $dbhA->do($stmtA);
	}
################################################################################
##### END settings update ORIGINAL to CLR db updates: campaigns, lists, dids, in-groups, users
################################################################################

print "DEV EXIT! $secZ seconds\n";
exit;














################################################################################
##### BEGIN incremental or nightly ORIGINAL to CLR db updates
################################################################################
if ($BACK_TO_CLUSTERS < 1) 
	{
	if (!$Q) {print "\nStarting Central-Lead-Repository calling-time ORIGINAL to CLR process...\n";}

	$clr_id='';
	# insert clr_log entry into database
	$stmtA = "INSERT INTO clr_log SET start_time=NOW(),begin_range='$Bdel_time',range_minutes='$CLIminutes',phase='BEGIN',server_ip='$server_ip',processing_log='';";
	$affected_rowsA = $dbhA->do($stmtA);
	$stmtC = "SELECT LAST_INSERT_ID() LIMIT 1;";
	$sthA = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtC ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$clr_id = $aryA[0];
		}
	$sthA->finish();
	if ($DB) {print "clr_id: $clr_id \n";}

	$vicidial_list_ct=0;
	$vicidial_log_ct=0;
	$vicidial_closer_log_ct=0;

	if ($NIGHTLY_UPDATE > 0) 
		{
		$phase='NIGHTLY_UPDATE';
		### Check if there are any leads modified since X minutes ago
		$stmtA = "SELECT count(*) from vicidial_list where modify_date >= \"$Bdel_time\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_list_ct =	$aryA[0];
			}
		$sthA->finish();
		}
	else
		{
		$phase='DAYTIME_INCREMENTAL';
		### Check if there are any calls within the previous X minutes
		$stmtA = "SELECT count(*) from vicidial_closer_log where modify_stamp >= \"$Bdel_time\" and modify_stamp < \"$del_time\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_closer_log_ct =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_log where modify_stamp >= \"$Bdel_time\" and modify_stamp < \"$del_time\";";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_log_ct =	$aryA[0];
			}
		$sthA->finish();
		}

	if ( ($vicidial_closer_log_ct < 1) && ($vicidial_log_ct < 1) && ($vicidial_list_ct < 1) ) 
		{
		if (!$Q) {print "No recent calls or changed leads, nothing to do, exiting... \n";}
		exit;
		}
	else
		{
		if (!$Q) {print "Recent calls -   Outbound: $vicidial_log_ct   Inbound: $vicidial_closer_log_ct   Leads: $vicidial_list_ct \n";}

		if ($NIGHTLY_UPDATE > 0) 
			{
			$o_ct=0;
			$stmtA = "SELECT lead_id from vicidial_list where modify_date >= \"$Bdel_time\" limit 10000000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			while ($sthArows > $o_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$updated_LEADS[$o_ct] =	$aryA[0];
				$o_ct++;
				}
			$sthA->finish();
			$updated_LEADSsize = $o_ct;
			}
		else
			{
			$o_ct=0;
			$stmtA = "SELECT lead_id from vicidial_log where modify_stamp >= \"$Bdel_time\" and modify_stamp < \"$del_time\" limit 10000000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			while ($sthArows > $o_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$updated_LEADS[$o_ct] =	$aryA[0];
				$o_ct++;
				}
			$sthA->finish();

			$i_ct=0;
			$stmtA = "SELECT lead_id from vicidial_closer_log where modify_stamp >= \"$Bdel_time\" and modify_stamp < \"$del_time\" limit 10000000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			while ($sthArows > $o_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$updated_LEADS[$o_ct] =	$aryA[0];
				$i_ct++;
				$o_ct++;
				}
			$sthA->finish();

			# remove duplicate lead_ids
			my %h1;
			foreach my $x (@updated_LEADS)
				{
				$h1{$x}=1;
				}
			@updated_LEADS=keys%h1;
			$updated_LEADSsize = @updated_LEADS;
			$updated_LEADSdupCT = ($o_ct - $updated_LEADSsize);

			$duplicate_output = "Duplicate check -   Dups: $updated_LEADSdupCT   Unique: $updated_LEADSsize \n";
			if (!$Q) {print $duplicate_output;}
			$process_output .= $duplicate_output;
			}

		### loop through all leads, gather lead data, compare to record on CLR server, update/insert if needed
		$u_ct=0;
		$CLRupdate_ct=0;	$CLRupdate_aff=0;
		$CLRinsert_ct=0;	$CLRinsert_aff=0;
		$CLRnochange_ct=0;
		while ($updated_LEADSsize > $u_ct) 
			{
			$update_phase=1;
			# gather lead data from ORIGINAL database
			$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from vicidial_list where lead_id=$updated_LEADS[$u_ct];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$lead_id =					$aryA[0];
				if (defined $aryA[1]) {$entry_date =				$aryA[1];	$entry_dateSQL =			"\"$aryA[1]\"";} 
				else {$entry_dateSQL = 'NULL';   $entry_date='NULL';}
				if (defined $aryA[2]) {$modify_date =				$aryA[2];	$modify_dateSQL =			"\"$aryA[2]\"";} 
				else {$modify_dateSQL = 'NULL';   $modify_date='NULL';}
				if (defined $aryA[3]) {$status =					$aryA[3];	$statusSQL =				"\"$aryA[3]\"";} 
				else {$statusSQL = 'NULL';   $status='NULL';}
				if (defined $aryA[4]) {$user =						$aryA[4];	$userSQL =					"\"$aryA[4]\"";} 
				else {$userSQL = 'NULL';   $user='NULL';}
				if (defined $aryA[5]) {$vendor_lead_code =			$aryA[5];	$vendor_lead_codeSQL =		"\"$aryA[5]\"";} 
				else {$vendor_lead_codeSQL = 'NULL';   $vendor_lead_code='NULL';}
				if (defined $aryA[6]) {$source_id =					$aryA[6];	$source_idSQL =				"\"$aryA[6]\"";} 
				else {$source_idSQL = 'NULL';   $source_id='NULL';}
				if (defined $aryA[7]) {$list_id =					$aryA[7];	$list_idSQL =				"\"$aryA[7]\"";} 
				else {$list_idSQL = 'NULL';   $list_id='NULL';}
				if (defined $aryA[8]) {$gmt_offset_now =			$aryA[8];	$gmt_offset_nowSQL =		"\"$aryA[8]\"";} 
				else {$gmt_offset_nowSQL = 'NULL';   $gmt_offset_now='NULL';}
				if (defined $aryA[9]) {$called_since_last_reset =	$aryA[9];	$called_since_last_resetSQL = "\"$aryA[9]\"";} 
				else {$called_since_last_resetSQL = 'NULL';   $called_since_last_reset='NULL';}
				if (defined $aryA[10]) {$phone_code =				$aryA[10];	$phone_codeSQL =			"\"$aryA[10]\"";} 
				else {$phone_codeSQL = 'NULL';   $phone_code='NULL';}
				if (defined $aryA[11]) {$phone_number =				$aryA[11];	$phone_numberSQL =			"\"$aryA[11]\"";} 
				else {$phone_numberSQL = 'NULL';   $phone_number='NULL';}
				if (defined $aryA[12]) {$title =					$aryA[12];	$titleSQL =					"\"$aryA[12]\"";} 
				else {$titleSQL = 'NULL';   $title='NULL';}
				if (defined $aryA[13]) {$first_name =				$aryA[13];	$first_nameSQL =			"\"$aryA[13]\"";} 
				else {$first_nameSQL = 'NULL';   $first_name='NULL';}
				if (defined $aryA[14]) {$middle_initial =			$aryA[14];	$middle_initialSQL =		"\"$aryA[14]\"";} 
				else {$middle_initialSQL = 'NULL';   $middle_initial='NULL';}
				if (defined $aryA[15]) {$last_name =				$aryA[15];	$last_nameSQL =				"\"$aryA[15]\"";} 
				else {$last_nameSQL = 'NULL';   $last_name='NULL';}
				if (defined $aryA[16]) {$address1 =					$aryA[16];	$address1SQL =				"\"$aryA[16]\"";} 
				else {$address1SQL = 'NULL';   $address1='NULL';}
				if (defined $aryA[17]) {$address2 =					$aryA[17];	$address2SQL =				"\"$aryA[17]\"";} 
				else {$address2SQL = 'NULL';   $address2='NULL';}
				if (defined $aryA[18]) {$address3 =					$aryA[18];	$address3SQL =				"\"$aryA[18]\"";} 
				else {$address3SQL = 'NULL';   $address3='NULL';}
				if (defined $aryA[19]) {$city =						$aryA[19];	$citySQL =					"\"$aryA[19]\"";} 
				else {$citySQL = 'NULL';   $city='NULL';}
				if (defined $aryA[20]) {$state =					$aryA[20];	$stateSQL =					"\"$aryA[20]\"";} 
				else {$stateSQL = 'NULL';   $state='NULL';}
				if (defined $aryA[21]) {$province =					$aryA[21];	$provinceSQL =				"\"$aryA[21]\"";} 
				else {$provinceSQL = 'NULL';   $province='NULL';}
				if (defined $aryA[22]) {$postal_code =				$aryA[22];	$postal_codeSQL =			"\"$aryA[22]\"";} 
				else {$postal_codeSQL = 'NULL';   $postal_code='NULL';}
				if (defined $aryA[23]) {$country_code =				$aryA[23];	$country_codeSQL =			"\"$aryA[23]\"";} 
				else {$country_codeSQL = 'NULL';   $country_code='NULL';}
				if (defined $aryA[24]) {$gender =					$aryA[24];	$genderSQL =				"\"$aryA[24]\"";} 
				else {$genderSQL = 'NULL';   $gender='NULL';}
				if (defined $aryA[25]) {$date_of_birth =			$aryA[25];	$date_of_birthSQL =			"\"$aryA[25]\"";} 
				else {$date_of_birthSQL = 'NULL';   $date_of_birth='NULL';}
				if (defined $aryA[26]) {$alt_phone =				$aryA[26];	$alt_phoneSQL =				"\"$aryA[26]\"";} 
				else {$alt_phoneSQL = 'NULL';   $alt_phone='NULL';}
				if (defined $aryA[27]) {$email =					$aryA[27];	$emailSQL =					"\"$aryA[27]\"";} 
				else {$emailSQL = 'NULL';   $email='NULL';}
				if (defined $aryA[28]) {$security_phrase =			$aryA[28];	$security_phraseSQL =		"\"$aryA[28]\"";} 
				else {$security_phraseSQL = 'NULL';   $security_phrase='NULL';}
				if (defined $aryA[29]) {$comments =					$aryA[29];	$commentsSQL =				"\"$aryA[29]\"";} 
				else {$commentsSQL = 'NULL';   $comments='NULL';}
				if (defined $aryA[30]) {$called_count =				$aryA[30];	$called_countSQL =			"\"$aryA[30]\"";} 
				else {$called_countSQL = 'NULL';   $called_count='NULL';}
				if (defined $aryA[31]) {$last_local_call_time =		$aryA[31];	$last_local_call_timeSQL =	"\"$aryA[31]\"";} 
				else {$last_local_call_timeSQL = 'NULL';   $last_local_call_time='NULL';}
				if (defined $aryA[32]) {$rank =						$aryA[32];	$rankSQL =					"\"$aryA[32]\"";} 
				else {$rankSQL = 'NULL';   $rank='NULL';}
				if (defined $aryA[33]) {$owner =					$aryA[33];	$ownerSQL =					"\"$aryA[33]\"";} 
				else {$ownerSQL = 'NULL';   $owner='NULL';}
				if (defined $aryA[34]) {$entry_list_id =			$aryA[34];	$entry_list_idSQL =			"\"$aryA[34]\"";} 
				else {$entry_list_idSQL = 'NULL';   $entry_list_id='NULL';}
				$Acompare = "$entry_date|$status|$user|$vendor_lead_code|$source_id|$list_id|$gmt_offset_now|$called_since_last_reset|$phone_code|$phone_number|$title|$first_name|$middle_initial|$last_name|$address1|$address2|$address3|$city|$state|$province|$postal_code|$country_code|$gender|$date_of_birth|$alt_phone|$email|$security_phrase|$comments|$called_count|$last_local_call_time|$rank|$owner|$entry_list_id";
				}
			else
				{
				$update_phase=0;
				if ($DBX > 0) 
					{print "DEBUG: No ORIGINAL lead found for lead $updated_LEADS[$u_ct], skipping \n";}
				}
			$sthA->finish();

			# gather lead data from CLR database
			$stmtB = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from vicidial_list where lead_id=$updated_LEADS[$u_ct];";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				@aryB = $sthB->fetchrow_array;
				$Blead_id =					$aryB[0];
				if (defined $aryB[1]) {$Bentry_date =				$aryB[1];	$Bentry_dateSQL =			"\"$aryB[1]\"";} 
				else {$Bentry_dateSQL = 'NULL';   $Bentry_date='NULL';}
				if (defined $aryB[2]) {$Bmodify_date =				$aryB[2];	$Bmodify_dateSQL =			"\"$aryB[2]\"";} 
				else {$Bmodify_dateSQL = 'NULL';   $Bmodify_date='NULL';}
				if (defined $aryB[3]) {$Bstatus =					$aryB[3];	$BstatusSQL =				"\"$aryB[3]\"";} 
				else {$BstatusSQL = 'NULL';   $Bstatus='NULL';}
				if (defined $aryB[4]) {$Buser =						$aryB[4];	$BuserSQL =					"\"$aryB[4]\"";} 
				else {$BuserSQL = 'NULL';   $Buser='NULL';}
				if (defined $aryB[5]) {$Bvendor_lead_code =			$aryB[5];	$Bvendor_lead_codeSQL =		"\"$aryB[5]\"";} 
				else {$Bvendor_lead_codeSQL = 'NULL';   $Bvendor_lead_code='NULL';}
				if (defined $aryB[6]) {$Bsource_id =					$aryB[6];	$Bsource_idSQL =				"\"$aryB[6]\"";} 
				else {$Bsource_idSQL = 'NULL';   $Bsource_id='NULL';}
				if (defined $aryB[7]) {$Blist_id =					$aryB[7];	$Blist_idSQL =				"\"$aryB[7]\"";} 
				else {$Blist_idSQL = 'NULL';   $Blist_id='NULL';}
				if (defined $aryB[8]) {$Bgmt_offset_now =			$aryB[8];	$Bgmt_offset_nowSQL =		"\"$aryB[8]\"";} 
				else {$Bgmt_offset_nowSQL = 'NULL';   $Bgmt_offset_now='NULL';}
				if (defined $aryB[9]) {$Bcalled_since_last_reset =	$aryB[9];	$Bcalled_since_last_resetSQL = "\"$aryB[9]\"";} 
				else {$Bcalled_since_last_resetSQL = 'NULL';   $Bcalled_since_last_reset='NULL';}
				if (defined $aryB[10]) {$Bphone_code =				$aryB[10];	$Bphone_codeSQL =			"\"$aryB[10]\"";} 
				else {$Bphone_codeSQL = 'NULL';   $Bphone_code='NULL';}
				if (defined $aryB[11]) {$Bphone_number =				$aryB[11];	$Bphone_numberSQL =			"\"$aryB[11]\"";} 
				else {$Bphone_numberSQL = 'NULL';   $Bphone_number='NULL';}
				if (defined $aryB[12]) {$Btitle =					$aryB[12];	$BtitleSQL =					"\"$aryB[12]\"";} 
				else {$BtitleSQL = 'NULL';   $Btitle='NULL';}
				if (defined $aryB[13]) {$Bfirst_name =				$aryB[13];	$Bfirst_nameSQL =			"\"$aryB[13]\"";} 
				else {$Bfirst_nameSQL = 'NULL';   $Bfirst_name='NULL';}
				if (defined $aryB[14]) {$Bmiddle_initial =			$aryB[14];	$Bmiddle_initialSQL =		"\"$aryB[14]\"";} 
				else {$Bmiddle_initialSQL = 'NULL';   $Bmiddle_initial='NULL';}
				if (defined $aryB[15]) {$Blast_name =				$aryB[15];	$Blast_nameSQL =				"\"$aryB[15]\"";} 
				else {$Blast_nameSQL = 'NULL';   $Blast_name='NULL';}
				if (defined $aryB[16]) {$Baddress1 =					$aryB[16];	$Baddress1SQL =				"\"$aryB[16]\"";} 
				else {$Baddress1SQL = 'NULL';   $Baddress1='NULL';}
				if (defined $aryB[17]) {$Baddress2 =					$aryB[17];	$Baddress2SQL =				"\"$aryB[17]\"";} 
				else {$Baddress2SQL = 'NULL';   $Baddress2='NULL';}
				if (defined $aryB[18]) {$Baddress3 =					$aryB[18];	$Baddress3SQL =				"\"$aryB[18]\"";} 
				else {$Baddress3SQL = 'NULL';   $Baddress3='NULL';}
				if (defined $aryB[19]) {$Bcity =						$aryB[19];	$BcitySQL =					"\"$aryB[19]\"";} 
				else {$BcitySQL = 'NULL';   $Bcity='NULL';}
				if (defined $aryB[20]) {$Bstate =					$aryB[20];	$BstateSQL =					"\"$aryB[20]\"";} 
				else {$BstateSQL = 'NULL';   $Bstate='NULL';}
				if (defined $aryB[21]) {$Bprovince =					$aryB[21];	$BprovinceSQL =				"\"$aryB[21]\"";} 
				else {$BprovinceSQL = 'NULL';   $Bprovince='NULL';}
				if (defined $aryB[22]) {$Bpostal_code =				$aryB[22];	$Bpostal_codeSQL =			"\"$aryB[22]\"";} 
				else {$Bpostal_codeSQL = 'NULL';   $Bpostal_code='NULL';}
				if (defined $aryB[23]) {$Bcountry_code =				$aryB[23];	$Bcountry_codeSQL =			"\"$aryB[23]\"";} 
				else {$Bcountry_codeSQL = 'NULL';   $Bcountry_code='NULL';}
				if (defined $aryB[24]) {$Bgender =					$aryB[24];	$BgenderSQL =				"\"$aryB[24]\"";} 
				else {$BgenderSQL = 'NULL';   $Bgender='NULL';}
				if (defined $aryB[25]) {$Bdate_of_birth =			$aryB[25];	$Bdate_of_birthSQL =			"\"$aryB[25]\"";} 
				else {$Bdate_of_birthSQL = 'NULL';   $Bdate_of_birth='NULL';}
				if (defined $aryB[26]) {$Balt_phone =				$aryB[26];	$Balt_phoneSQL =				"\"$aryB[26]\"";} 
				else {$Balt_phoneSQL = 'NULL';   $Balt_phone='NULL';}
				if (defined $aryB[27]) {$Bemail =					$aryB[27];	$BemailSQL =					"\"$aryB[27]\"";} 
				else {$BemailSQL = 'NULL';   $Bemail='NULL';}
				if (defined $aryB[28]) {$Bsecurity_phrase =			$aryB[28];	$Bsecurity_phraseSQL =		"\"$aryB[28]\"";} 
				else {$Bsecurity_phraseSQL = 'NULL';   $Bsecurity_phrase='NULL';}
				if (defined $aryB[29]) {$Bcomments =					$aryB[29];	$BcommentsSQL =				"\"$aryB[29]\"";} 
				else {$BcommentsSQL = 'NULL';   $Bcomments='NULL';}
				if (defined $aryB[30]) {$Bcalled_count =				$aryB[30];	$Bcalled_countSQL =			"\"$aryB[30]\"";} 
				else {$Bcalled_countSQL = 'NULL';   $Bcalled_count='NULL';}
				if (defined $aryB[31]) {$Blast_local_call_time =		$aryB[31];	$Blast_local_call_timeSQL =	"\"$aryB[31]\"";} 
				else {$Blast_local_call_timeSQL = 'NULL';   $Blast_local_call_time='NULL';}
				if (defined $aryB[32]) {$Brank =						$aryB[32];	$BrankSQL =					"\"$aryB[32]\"";} 
				else {$BrankSQL = 'NULL';   $Brank='NULL';}
				if (defined $aryB[33]) {$Bowner =					$aryB[33];	$BownerSQL =					"\"$aryB[33]\"";} 
				else {$BownerSQL = 'NULL';   $Bowner='NULL';}
				if (defined $aryB[34]) {$Bentry_list_id =			$aryB[34];	$Bentry_list_idSQL =			"\"$aryB[34]\"";} 
				else {$Bentry_list_idSQL = 'NULL';   $Bentry_list_id='NULL';}

				$Bcompare = "$Bentry_date|$Bstatus|$Buser|$Bvendor_lead_code|$Bsource_id|$Blist_id|$Bgmt_offset_now|$Bcalled_since_last_reset|$Bphone_code|$Bphone_number|$Btitle|$Bfirst_name|$Bmiddle_initial|$Blast_name|$Baddress1|$Baddress2|$Baddress3|$Bcity|$Bstate|$Bprovince|$Bpostal_code|$Bcountry_code|$Bgender|$Bdate_of_birth|$Balt_phone|$Bemail|$Bsecurity_phrase|$Bcomments|$Bcalled_count|$Blast_local_call_time|$Brank|$Bowner|$Bentry_list_id";
				}
			else
				{
				if ($update_phase > 0) 
					{
					$update_phase=2;
					if ($DBX > 0) {print "DEBUG: No CLR match found for lead $updated_LEADS[$u_ct], inserting instead \n";}
					}
				}
			$sthB->finish();

			if ($update_phase >= 2) 
				{
				# insert lead into CLR database

				$stmtB = "INSERT INTO vicidial_list SET lead_id='$lead_id',entry_date=$entry_dateSQL,modify_date=$modify_dateSQL,status=$statusSQL,user=$userSQL,vendor_lead_code=$vendor_lead_codeSQL,source_id=$source_idSQL,list_id=$list_idSQL,gmt_offset_now=$gmt_offset_nowSQL,called_since_last_reset=$called_since_last_resetSQL,phone_code=$phone_codeSQL,phone_number=$phone_numberSQL,title=$titleSQL,first_name=$first_nameSQL,middle_initial=$middle_initialSQL,last_name=$last_nameSQL,address1=$address1SQL,address2=$address2SQL,address3=$address3SQL,city=$citySQL,state=$stateSQL,province=$provinceSQL,postal_code=$postal_codeSQL,country_code=$country_codeSQL,gender=$genderSQL,date_of_birth=$date_of_birthSQL,alt_phone=$alt_phoneSQL,email=$emailSQL,security_phrase=$security_phraseSQL,comments=$commentsSQL,called_count=$called_countSQL,last_local_call_time=$last_local_call_timeSQL,rank=$rankSQL,owner=$ownerSQL,entry_list_id=$entry_list_idSQL;";
				$affected_rowsB = $dbhB->do($stmtB);
				$CLRinsert_aff = ($CLRinsert_aff + $affected_rowsB);
				if ($DBX > 0) {print "DEBUG: CLR lead inserted $updated_LEADS[$u_ct]: $affected_rowsB|$stmtB| \n";}

				$CLRinsert_ct++;
				}
			else
				{
				# compare ORIGINAL and CLR lead data to see if update is needed
				if ($Acompare eq $Bcompare) 
					{
					$update_phase=0;
					if ($DBX > 0) {print "DEBUG: ORIGINAL and CLR lead data is identical: $updated_LEADS[$u_ct] \n";}
					}
				if ($DBX > 0) {print "$lead_id compare:\n$Acompare \n$Bcompare \n";}

				if ($update_phase >= 1) 
					{
					# update existing lead in CLR database
					$stmtB = "UPDATE vicidial_list SET entry_date=$entry_dateSQL,modify_date=$modify_dateSQL,status=$statusSQL,user=$userSQL,vendor_lead_code=$vendor_lead_codeSQL,source_id=$source_idSQL,list_id=$list_idSQL,gmt_offset_now=$gmt_offset_nowSQL,called_since_last_reset=$called_since_last_resetSQL,phone_code=$phone_codeSQL,phone_number=$phone_numberSQL,title=$titleSQL,first_name=$first_nameSQL,middle_initial=$middle_initialSQL,last_name=$last_nameSQL,address1=$address1SQL,address2=$address2SQL,address3=$address3SQL,city=$citySQL,state=$stateSQL,province=$provinceSQL,postal_code=$postal_codeSQL,country_code=$country_codeSQL,gender=$genderSQL,date_of_birth=$date_of_birthSQL,alt_phone=$alt_phoneSQL,email=$emailSQL,security_phrase=$security_phraseSQL,comments=$commentsSQL,called_count=$called_countSQL,last_local_call_time=$last_local_call_timeSQL,rank=$rankSQL,owner=$ownerSQL,entry_list_id=$entry_list_idSQL where lead_id='$lead_id';";
					$affected_rowsB = $dbhB->do($stmtB);
					$CLRupdate_aff = ($CLRupdate_aff + $affected_rowsB);
					if ($DBX > 0) {print "DEBUG: CLR lead updated $updated_LEADS[$u_ct]: $affected_rowsB|$stmtB| \n";}

					$CLRupdate_ct++;
					}
				else
					{
					# no change needed to lead in CLR database
					$CLRnochange_ct++;
					}
				}

			$u_ct++;
			if ($Q < 1) 
				{
				if ($u_ct =~ /100$/i) {print STDERR ">         $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /200$/i) {print STDERR "->        $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /300$/i) {print STDERR " ->       $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /400$/i) {print STDERR "  ->      $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /500$/i) {print STDERR "   ->     $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /600$/i) {print STDERR "    ->    $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /700$/i) {print STDERR "     ->   $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /800$/i) {print STDERR "      ->  $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /900$/i) {print STDERR "       -> $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /000$/i) {print STDERR "        ->$u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /0000$/i) 
					{
					$secY = time();
					$secZ = ($secY - $secX);
					print "$u_ct|$CLRupdate_ct|$CLRinsert_ct|$CLRnochange_ct|   $secZ sec \n";
					}
				}
			}
		}


	$lead_output  = "CLR lead updates:    $CLRupdate_ct ($CLRupdate_aff) \n";
	$lead_output .= "CLR lead inserts:    $CLRinsert_ct ($CLRinsert_aff) \n";
	$lead_output .= "CLR lead no changes: $CLRnochange_ct \n";
	if (!$Q) {print $lead_output;}

	$process_output .= $lead_output;

	$secY = time();
	$secZ = ($secY - $secX);

	# insert clr_log entry into database
	$stmtA = "UPDATE clr_log SET phase='$phase',records_ct='$u_ct',length_in_sec='$secZ',processing_log='$process_output' where clr_id='$clr_id';";
	$affected_rowsA = $dbhA->do($stmtA);
	}
################################################################################
##### END incremental or nightly ORIGINAL to CLR db updates
################################################################################




################################################################################
##### BEGIN morning CLR to ORIGINAL db updates
################################################################################
else
	{
	if (!$Q) {print "\nStarting Central-Lead-Repository CLR to ORIGINAL process...\n";}

	$clr_id='';
	# insert clr_log entry into database
	$stmtA = "INSERT INTO clr_log SET start_time=NOW(),begin_range='$Bdel_time',range_minutes='$CLIminutes',phase='BEGIN',server_ip='$server_ip',processing_log='';";
	$affected_rowsA = $dbhA->do($stmtA);
	$stmtC = "SELECT LAST_INSERT_ID() LIMIT 1;";
	$sthA = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtC ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$clr_id = $aryA[0];
		}
	$sthA->finish();
	if ($DB) {print "clr_id: $clr_id \n";}

	$vicidial_list_ct=0;
	$vicidial_log_ct=0;
	$vicidial_closer_log_ct=0;

	$phase='MORNING_UPDATE';
	### Check if there are any leads modified since X minutes ago
	$stmtB = "SELECT count(*) from vicidial_list where modify_date >= \"$Bdel_time\";";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$vicidial_list_ct =	$aryB[0];
		}
	$sthB->finish();

	if ($vicidial_list_ct < 1)
		{
		if (!$Q) {print "No recent changed leads, nothing to do, exiting... \n";}
		exit;
		}
	else
		{
		if (!$Q) {print "Recent changed Leads: $vicidial_list_ct \n";}

		$o_ct=0;
		$stmtB = "SELECT lead_id from vicidial_list where modify_date >= \"$Bdel_time\" limit 10000000;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		while ($sthBrows > $o_ct)
			{
			@aryB = $sthB->fetchrow_array;
			$updated_LEADS[$o_ct] =	$aryB[0];
			$o_ct++;
			}
		$sthB->finish();
		$updated_LEADSsize = $o_ct;

		### loop through all leads, gather lead data, compare to record on CLR server, update/insert if needed
		$u_ct=0;
		$CLRupdate_ct=0;	$CLRupdate_aff=0;
		$CLRreverse_update_ct=0;	$CLRreverse_update_aff=0;
		$CLRinsert_ct=0;	$CLRinsert_aff=0;
		$CLRnochange_ct=0;
		while ($updated_LEADSsize > $u_ct) 
			{
			$update_phase=1;
			# gather lead data from CLR database
			$stmtB = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id,UNIX_TIMESTAMP(modify_date) from vicidial_list where lead_id=$updated_LEADS[$u_ct];";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				@aryB = $sthB->fetchrow_array;
				$lead_id =					$aryB[0];
				if (defined $aryB[1]) {$entry_date =				$aryB[1];	$entry_dateSQL =			"\"$aryB[1]\"";} 
				else {$entry_dateSQL = 'NULL';   $entry_date='NULL';}
				if (defined $aryB[2]) {$modify_date =				$aryB[2];	$modify_dateSQL =			"\"$aryB[2]\"";} 
				else {$modify_dateSQL = 'NULL';   $modify_date='NULL';}
				if (defined $aryB[3]) {$status =					$aryB[3];	$statusSQL =				"\"$aryB[3]\"";} 
				else {$statusSQL = 'NULL';   $status='NULL';}
				if (defined $aryB[4]) {$user =						$aryB[4];	$userSQL =					"\"$aryB[4]\"";} 
				else {$userSQL = 'NULL';   $user='NULL';}
				if (defined $aryB[5]) {$vendor_lead_code =			$aryB[5];	$vendor_lead_codeSQL =		"\"$aryB[5]\"";} 
				else {$vendor_lead_codeSQL = 'NULL';   $vendor_lead_code='NULL';}
				if (defined $aryB[6]) {$source_id =					$aryB[6];	$source_idSQL =				"\"$aryB[6]\"";} 
				else {$source_idSQL = 'NULL';   $source_id='NULL';}
				if (defined $aryB[7]) {$list_id =					$aryB[7];	$list_idSQL =				"\"$aryB[7]\"";} 
				else {$list_idSQL = 'NULL';   $list_id='NULL';}
				if (defined $aryB[8]) {$gmt_offset_now =			$aryB[8];	$gmt_offset_nowSQL =		"\"$aryB[8]\"";} 
				else {$gmt_offset_nowSQL = 'NULL';   $gmt_offset_now='NULL';}
				if (defined $aryB[9]) {$called_since_last_reset =	$aryB[9];	$called_since_last_resetSQL = "\"$aryB[9]\"";} 
				else {$called_since_last_resetSQL = 'NULL';   $called_since_last_reset='NULL';}
				if (defined $aryB[10]) {$phone_code =				$aryB[10];	$phone_codeSQL =			"\"$aryB[10]\"";} 
				else {$phone_codeSQL = 'NULL';   $phone_code='NULL';}
				if (defined $aryB[11]) {$phone_number =				$aryB[11];	$phone_numberSQL =			"\"$aryB[11]\"";} 
				else {$phone_numberSQL = 'NULL';   $phone_number='NULL';}
				if (defined $aryB[12]) {$title =					$aryB[12];	$titleSQL =					"\"$aryB[12]\"";} 
				else {$titleSQL = 'NULL';   $title='NULL';}
				if (defined $aryB[13]) {$first_name =				$aryB[13];	$first_nameSQL =			"\"$aryB[13]\"";} 
				else {$first_nameSQL = 'NULL';   $first_name='NULL';}
				if (defined $aryB[14]) {$middle_initial =			$aryB[14];	$middle_initialSQL =		"\"$aryB[14]\"";} 
				else {$middle_initialSQL = 'NULL';   $middle_initial='NULL';}
				if (defined $aryB[15]) {$last_name =				$aryB[15];	$last_nameSQL =				"\"$aryB[15]\"";} 
				else {$last_nameSQL = 'NULL';   $last_name='NULL';}
				if (defined $aryB[16]) {$address1 =					$aryB[16];	$address1SQL =				"\"$aryB[16]\"";} 
				else {$address1SQL = 'NULL';   $address1='NULL';}
				if (defined $aryB[17]) {$address2 =					$aryB[17];	$address2SQL =				"\"$aryB[17]\"";} 
				else {$address2SQL = 'NULL';   $address2='NULL';}
				if (defined $aryB[18]) {$address3 =					$aryB[18];	$address3SQL =				"\"$aryB[18]\"";} 
				else {$address3SQL = 'NULL';   $address3='NULL';}
				if (defined $aryB[19]) {$city =						$aryB[19];	$citySQL =					"\"$aryB[19]\"";} 
				else {$citySQL = 'NULL';   $city='NULL';}
				if (defined $aryB[20]) {$state =					$aryB[20];	$stateSQL =					"\"$aryB[20]\"";} 
				else {$stateSQL = 'NULL';   $state='NULL';}
				if (defined $aryB[21]) {$province =					$aryB[21];	$provinceSQL =				"\"$aryB[21]\"";} 
				else {$provinceSQL = 'NULL';   $province='NULL';}
				if (defined $aryB[22]) {$postal_code =				$aryB[22];	$postal_codeSQL =			"\"$aryB[22]\"";} 
				else {$postal_codeSQL = 'NULL';   $postal_code='NULL';}
				if (defined $aryB[23]) {$country_code =				$aryB[23];	$country_codeSQL =			"\"$aryB[23]\"";} 
				else {$country_codeSQL = 'NULL';   $country_code='NULL';}
				if (defined $aryB[24]) {$gender =					$aryB[24];	$genderSQL =				"\"$aryB[24]\"";} 
				else {$genderSQL = 'NULL';   $gender='NULL';}
				if (defined $aryB[25]) {$date_of_birth =			$aryB[25];	$date_of_birthSQL =			"\"$aryB[25]\"";} 
				else {$date_of_birthSQL = 'NULL';   $date_of_birth='NULL';}
				if (defined $aryB[26]) {$alt_phone =				$aryB[26];	$alt_phoneSQL =				"\"$aryB[26]\"";} 
				else {$alt_phoneSQL = 'NULL';   $alt_phone='NULL';}
				if (defined $aryB[27]) {$email =					$aryB[27];	$emailSQL =					"\"$aryB[27]\"";} 
				else {$emailSQL = 'NULL';   $email='NULL';}
				if (defined $aryB[28]) {$security_phrase =			$aryB[28];	$security_phraseSQL =		"\"$aryB[28]\"";} 
				else {$security_phraseSQL = 'NULL';   $security_phrase='NULL';}
				if (defined $aryB[29]) {$comments =					$aryB[29];	$commentsSQL =				"\"$aryB[29]\"";} 
				else {$commentsSQL = 'NULL';   $comments='NULL';}
				if (defined $aryB[30]) {$called_count =				$aryB[30];	$called_countSQL =			"\"$aryB[30]\"";} 
				else {$called_countSQL = 'NULL';   $called_count='NULL';}
				if (defined $aryB[31]) {$last_local_call_time =		$aryB[31];	$last_local_call_timeSQL =	"\"$aryB[31]\"";} 
				else {$last_local_call_timeSQL = 'NULL';   $last_local_call_time='NULL';}
				if (defined $aryB[32]) {$rank =						$aryB[32];	$rankSQL =					"\"$aryB[32]\"";} 
				else {$rankSQL = 'NULL';   $rank='NULL';}
				if (defined $aryB[33]) {$owner =					$aryB[33];	$ownerSQL =					"\"$aryB[33]\"";} 
				else {$ownerSQL = 'NULL';   $owner='NULL';}
				if (defined $aryB[34]) {$entry_list_id =			$aryB[34];	$entry_list_idSQL =			"\"$aryB[34]\"";} 
				else {$entry_list_idSQL = 'NULL';   $entry_list_id='NULL';}
				$Amodify_epoch =					$aryB[35];
				$Acompare = "$entry_date|$status|$user|$vendor_lead_code|$source_id|$list_id|$gmt_offset_now|$called_since_last_reset|$phone_code|$phone_number|$title|$first_name|$middle_initial|$last_name|$address1|$address2|$address3|$city|$state|$province|$postal_code|$country_code|$gender|$date_of_birth|$alt_phone|$email|$security_phrase|$comments|$called_count|$last_local_call_time|$rank|$owner|$entry_list_id";
				}
			else
				{
				$update_phase=0;
				if ($DBX > 0) 
					{print "DEBUG: No CLR lead found for lead $updated_LEADS[$u_ct], skipping \n";}
				}
			$sthB->finish();

			# gather lead data from ORIGINAL database
			$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id,UNIX_TIMESTAMP(modify_date) from vicidial_list where lead_id=$updated_LEADS[$u_ct];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$Blead_id =					$aryA[0];
				if (defined $aryA[1]) {$Bentry_date =				$aryA[1];	$Bentry_dateSQL =			"\"$aryA[1]\"";} 
				else {$Bentry_dateSQL = 'NULL';   $Bentry_date='NULL';}
				if (defined $aryA[2]) {$Bmodify_date =				$aryA[2];	$Bmodify_dateSQL =			"\"$aryA[2]\"";} 
				else {$Bmodify_dateSQL = 'NULL';   $Bmodify_date='NULL';}
				if (defined $aryA[3]) {$Bstatus =					$aryA[3];	$BstatusSQL =				"\"$aryA[3]\"";} 
				else {$BstatusSQL = 'NULL';   $Bstatus='NULL';}
				if (defined $aryA[4]) {$Buser =						$aryA[4];	$BuserSQL =					"\"$aryA[4]\"";} 
				else {$BuserSQL = 'NULL';   $Buser='NULL';}
				if (defined $aryA[5]) {$Bvendor_lead_code =			$aryA[5];	$Bvendor_lead_codeSQL =		"\"$aryA[5]\"";} 
				else {$Bvendor_lead_codeSQL = 'NULL';   $Bvendor_lead_code='NULL';}
				if (defined $aryA[6]) {$Bsource_id =					$aryA[6];	$Bsource_idSQL =				"\"$aryA[6]\"";} 
				else {$Bsource_idSQL = 'NULL';   $Bsource_id='NULL';}
				if (defined $aryA[7]) {$Blist_id =					$aryA[7];	$Blist_idSQL =				"\"$aryA[7]\"";} 
				else {$Blist_idSQL = 'NULL';   $Blist_id='NULL';}
				if (defined $aryA[8]) {$Bgmt_offset_now =			$aryA[8];	$Bgmt_offset_nowSQL =		"\"$aryA[8]\"";} 
				else {$Bgmt_offset_nowSQL = 'NULL';   $Bgmt_offset_now='NULL';}
				if (defined $aryA[9]) {$Bcalled_since_last_reset =	$aryA[9];	$Bcalled_since_last_resetSQL = "\"$aryA[9]\"";} 
				else {$Bcalled_since_last_resetSQL = 'NULL';   $Bcalled_since_last_reset='NULL';}
				if (defined $aryA[10]) {$Bphone_code =				$aryA[10];	$Bphone_codeSQL =			"\"$aryA[10]\"";} 
				else {$Bphone_codeSQL = 'NULL';   $Bphone_code='NULL';}
				if (defined $aryA[11]) {$Bphone_number =				$aryA[11];	$Bphone_numberSQL =			"\"$aryA[11]\"";} 
				else {$Bphone_numberSQL = 'NULL';   $Bphone_number='NULL';}
				if (defined $aryA[12]) {$Btitle =					$aryA[12];	$BtitleSQL =					"\"$aryA[12]\"";} 
				else {$BtitleSQL = 'NULL';   $Btitle='NULL';}
				if (defined $aryA[13]) {$Bfirst_name =				$aryA[13];	$Bfirst_nameSQL =			"\"$aryA[13]\"";} 
				else {$Bfirst_nameSQL = 'NULL';   $Bfirst_name='NULL';}
				if (defined $aryA[14]) {$Bmiddle_initial =			$aryA[14];	$Bmiddle_initialSQL =		"\"$aryA[14]\"";} 
				else {$Bmiddle_initialSQL = 'NULL';   $Bmiddle_initial='NULL';}
				if (defined $aryA[15]) {$Blast_name =				$aryA[15];	$Blast_nameSQL =				"\"$aryA[15]\"";} 
				else {$Blast_nameSQL = 'NULL';   $Blast_name='NULL';}
				if (defined $aryA[16]) {$Baddress1 =					$aryA[16];	$Baddress1SQL =				"\"$aryA[16]\"";} 
				else {$Baddress1SQL = 'NULL';   $Baddress1='NULL';}
				if (defined $aryA[17]) {$Baddress2 =					$aryA[17];	$Baddress2SQL =				"\"$aryA[17]\"";} 
				else {$Baddress2SQL = 'NULL';   $Baddress2='NULL';}
				if (defined $aryA[18]) {$Baddress3 =					$aryA[18];	$Baddress3SQL =				"\"$aryA[18]\"";} 
				else {$Baddress3SQL = 'NULL';   $Baddress3='NULL';}
				if (defined $aryA[19]) {$Bcity =						$aryA[19];	$BcitySQL =					"\"$aryA[19]\"";} 
				else {$BcitySQL = 'NULL';   $Bcity='NULL';}
				if (defined $aryA[20]) {$Bstate =					$aryA[20];	$BstateSQL =					"\"$aryA[20]\"";} 
				else {$BstateSQL = 'NULL';   $Bstate='NULL';}
				if (defined $aryA[21]) {$Bprovince =					$aryA[21];	$BprovinceSQL =				"\"$aryA[21]\"";} 
				else {$BprovinceSQL = 'NULL';   $Bprovince='NULL';}
				if (defined $aryA[22]) {$Bpostal_code =				$aryA[22];	$Bpostal_codeSQL =			"\"$aryA[22]\"";} 
				else {$Bpostal_codeSQL = 'NULL';   $Bpostal_code='NULL';}
				if (defined $aryA[23]) {$Bcountry_code =				$aryA[23];	$Bcountry_codeSQL =			"\"$aryA[23]\"";} 
				else {$Bcountry_codeSQL = 'NULL';   $Bcountry_code='NULL';}
				if (defined $aryA[24]) {$Bgender =					$aryA[24];	$BgenderSQL =				"\"$aryA[24]\"";} 
				else {$BgenderSQL = 'NULL';   $Bgender='NULL';}
				if (defined $aryA[25]) {$Bdate_of_birth =			$aryA[25];	$Bdate_of_birthSQL =			"\"$aryA[25]\"";} 
				else {$Bdate_of_birthSQL = 'NULL';   $Bdate_of_birth='NULL';}
				if (defined $aryA[26]) {$Balt_phone =				$aryA[26];	$Balt_phoneSQL =				"\"$aryA[26]\"";} 
				else {$Balt_phoneSQL = 'NULL';   $Balt_phone='NULL';}
				if (defined $aryA[27]) {$Bemail =					$aryA[27];	$BemailSQL =					"\"$aryA[27]\"";} 
				else {$BemailSQL = 'NULL';   $Bemail='NULL';}
				if (defined $aryA[28]) {$Bsecurity_phrase =			$aryA[28];	$Bsecurity_phraseSQL =		"\"$aryA[28]\"";} 
				else {$Bsecurity_phraseSQL = 'NULL';   $Bsecurity_phrase='NULL';}
				if (defined $aryA[29]) {$Bcomments =					$aryA[29];	$BcommentsSQL =				"\"$aryA[29]\"";} 
				else {$BcommentsSQL = 'NULL';   $Bcomments='NULL';}
				if (defined $aryA[30]) {$Bcalled_count =				$aryA[30];	$Bcalled_countSQL =			"\"$aryA[30]\"";} 
				else {$Bcalled_countSQL = 'NULL';   $Bcalled_count='NULL';}
				if (defined $aryA[31]) {$Blast_local_call_time =		$aryA[31];	$Blast_local_call_timeSQL =	"\"$aryA[31]\"";} 
				else {$Blast_local_call_timeSQL = 'NULL';   $Blast_local_call_time='NULL';}
				if (defined $aryA[32]) {$Brank =						$aryA[32];	$BrankSQL =					"\"$aryA[32]\"";} 
				else {$BrankSQL = 'NULL';   $Brank='NULL';}
				if (defined $aryA[33]) {$Bowner =					$aryA[33];	$BownerSQL =					"\"$aryA[33]\"";} 
				else {$BownerSQL = 'NULL';   $Bowner='NULL';}
				if (defined $aryA[34]) {$Bentry_list_id =			$aryA[34];	$Bentry_list_idSQL =			"\"$aryA[34]\"";} 
				else {$Bentry_list_idSQL = 'NULL';   $Bentry_list_id='NULL';}
				$Bmodify_epoch =					$aryA[35];

				$Bcompare = "$Bentry_date|$Bstatus|$Buser|$Bvendor_lead_code|$Bsource_id|$Blist_id|$Bgmt_offset_now|$Bcalled_since_last_reset|$Bphone_code|$Bphone_number|$Btitle|$Bfirst_name|$Bmiddle_initial|$Blast_name|$Baddress1|$Baddress2|$Baddress3|$Bcity|$Bstate|$Bprovince|$Bpostal_code|$Bcountry_code|$Bgender|$Bdate_of_birth|$Balt_phone|$Bemail|$Bsecurity_phrase|$Bcomments|$Bcalled_count|$Blast_local_call_time|$Brank|$Bowner|$Bentry_list_id";
				}
			else
				{
				if ($update_phase > 0) 
					{
					$update_phase=2;
					if ($DBX > 0) {print "DEBUG: No ORIGINAL match found for lead $updated_LEADS[$u_ct], inserting instead \n";}
					}
				}
			$sthA->finish();

			if ($update_phase >= 2) 
				{
				# insert lead into ORIGINAL database

				$stmtA = "INSERT INTO vicidial_list SET lead_id='$lead_id',entry_date=$entry_dateSQL,modify_date=$modify_dateSQL,status=$statusSQL,user=$userSQL,vendor_lead_code=$vendor_lead_codeSQL,source_id=$source_idSQL,list_id=$list_idSQL,gmt_offset_now=$gmt_offset_nowSQL,called_since_last_reset=$called_since_last_resetSQL,phone_code=$phone_codeSQL,phone_number=$phone_numberSQL,title=$titleSQL,first_name=$first_nameSQL,middle_initial=$middle_initialSQL,last_name=$last_nameSQL,address1=$address1SQL,address2=$address2SQL,address3=$address3SQL,city=$citySQL,state=$stateSQL,province=$provinceSQL,postal_code=$postal_codeSQL,country_code=$country_codeSQL,gender=$genderSQL,date_of_birth=$date_of_birthSQL,alt_phone=$alt_phoneSQL,email=$emailSQL,security_phrase=$security_phraseSQL,comments=$commentsSQL,called_count=$called_countSQL,last_local_call_time=$last_local_call_timeSQL,rank=$rankSQL,owner=$ownerSQL,entry_list_id=$entry_list_idSQL;";
			#	$affected_rowsA = $dbhA->do($stmtA);
				$CLRinsert_aff = ($CLRinsert_aff + $affected_rowsA);
				if ($DBX > 0) {print "DEBUG: CLR lead inserted $updated_LEADS[$u_ct]: $affected_rowsA|$stmtA| \n";}

				$CLRinsert_ct++;
				}
			else
				{
				# compare ORIGINAL and CLR lead data to see if update is needed
				if ($Acompare eq $Bcompare) 
					{
					$update_phase=0;
					if ($DBX > 0) {print "DEBUG: ORIGINAL and CLR lead data is identical: $updated_LEADS[$u_ct] \n";}
					}
				if ($DBX > 0) {print "$lead_id compare:\n$Acompare \n$Bcompare \n";}

				if ($update_phase >= 1) 
					{
					# If ORIGINAL is newer than CLR record, than update the CLR record instead
					if ($Amodify_epoch < $Bmodify_epoch) 
						{
						if ($DBX > 0) {print "REVERSE UPDATE: ORIGINAL to CLR ($Amodify_epoch < $Bmodify_epoch): $updated_LEADS[$u_ct] \n";}

						$stmtB = "UPDATE vicidial_list SET entry_date=$Bentry_dateSQL,modify_date=$Bmodify_dateSQL,status=$BstatusSQL,user=$BuserSQL,vendor_lead_code=$Bvendor_lead_codeSQL,source_id=$Bsource_idSQL,list_id=$Blist_idSQL,gmt_offset_now=$Bgmt_offset_nowSQL,called_since_last_reset=$Bcalled_since_last_resetSQL,phone_code=$Bphone_codeSQL,phone_number=$Bphone_numberSQL,title=$BtitleSQL,first_name=$Bfirst_nameSQL,middle_initial=$Bmiddle_initialSQL,last_name=$Blast_nameSQL,address1=$Baddress1SQL,address2=$Baddress2SQL,address3=$Baddress3SQL,city=$BcitySQL,state=$BstateSQL,province=$BprovinceSQL,postal_code=$Bpostal_codeSQL,country_code=$Bcountry_codeSQL,gender=$BgenderSQL,date_of_birth=$Bdate_of_birthSQL,alt_phone=$Balt_phoneSQL,email=$BemailSQL,security_phrase=$Bsecurity_phraseSQL,comments=$BcommentsSQL,called_count=$Bcalled_countSQL,last_local_call_time=$Blast_local_call_timeSQL,rank=$BrankSQL,owner=$BownerSQL,entry_list_id=$Bentry_list_idSQL where lead_id='$lead_id';";
						$affected_rowsB = $dbhB->do($stmtB);
						$CLRreverse_update_aff = ($CLRreverse_update_aff + $affected_rowsB);
						if ($DBX > 0) {print "DEBUG: CLR lead updated $updated_LEADS[$u_ct]: $affected_rowsB|$stmtB| \n";}

						$CLRreverse_update_ct++;
						}
					else
						{
						# update existing lead in ORIGINAL database
						$stmtA = "UPDATE vicidial_list SET entry_date=$entry_dateSQL,modify_date=$modify_dateSQL,status=$statusSQL,user=$userSQL,vendor_lead_code=$vendor_lead_codeSQL,source_id=$source_idSQL,list_id=$list_idSQL,gmt_offset_now=$gmt_offset_nowSQL,called_since_last_reset=$called_since_last_resetSQL,phone_code=$phone_codeSQL,phone_number=$phone_numberSQL,title=$titleSQL,first_name=$first_nameSQL,middle_initial=$middle_initialSQL,last_name=$last_nameSQL,address1=$address1SQL,address2=$address2SQL,address3=$address3SQL,city=$citySQL,state=$stateSQL,province=$provinceSQL,postal_code=$postal_codeSQL,country_code=$country_codeSQL,gender=$genderSQL,date_of_birth=$date_of_birthSQL,alt_phone=$alt_phoneSQL,email=$emailSQL,security_phrase=$security_phraseSQL,comments=$commentsSQL,called_count=$called_countSQL,last_local_call_time=$last_local_call_timeSQL,rank=$rankSQL,owner=$ownerSQL,entry_list_id=$entry_list_idSQL where lead_id='$lead_id';";
					#	$affected_rowsA = $dbhA->do($stmtA);
						$CLRupdate_aff = ($CLRupdate_aff + $affected_rowsA);
						if ($DB > 0) {print "DEBUG: CLR lead updated $updated_LEADS[$u_ct]: $affected_rowsA|$stmtA| \n";}

						$CLRupdate_ct++;
						}
					}
				else
					{
					# no change needed to lead in CLR database
					$CLRnochange_ct++;
					}
				}

			$u_ct++;
			if ($Q < 1) 
				{
				if ($u_ct =~ /000$/i) {print STDERR "         <$u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /100$/i) {print STDERR "        < $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /200$/i) {print STDERR "       <- $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /300$/i) {print STDERR "      <-  $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /400$/i) {print STDERR "     <-   $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /500$/i) {print STDERR "    <-    $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /600$/i) {print STDERR "   <-     $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /700$/i) {print STDERR "  <-      $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /800$/i) {print STDERR " <-       $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /900$/i) {print STDERR "<-        $u_ct / $updated_LEADSsize \r";}
				if ($u_ct =~ /0000$/i) 
					{
					$secY = time();
					$secZ = ($secY - $secX);
					print "$u_ct|$CLRupdate_ct/$CLRreverse_update_ct|$CLRinsert_ct|$CLRnochange_ct|   $secZ sec \n";
					}
				}
			}
		}


	$lead_output  = "ORIGINAL lead updates:    $CLRupdate_ct ($CLRupdate_aff) \n";
	$lead_output .= "REV CLR lead updates:     $CLRreverse_update_ct ($CLRreverse_update_aff) \n";
	$lead_output .= "ORIGINAL lead inserts:    $CLRinsert_ct ($CLRinsert_aff) \n";
	$lead_output .= "ORIGINAL lead no changes: $CLRnochange_ct \n";
	if (!$Q) {print $lead_output;}

	$process_output .= $lead_output;

	$secY = time();
	$secZ = ($secY - $secX);

	# insert clr_log entry into database
	$stmtA = "UPDATE clr_log SET phase='$phase',records_ct='$u_ct',length_in_sec='$secZ',processing_log='$process_output' where clr_id='$clr_id';";
	$affected_rowsA = $dbhA->do($stmtA);
	}
################################################################################
##### END morning CLR to ORIGINAL db updates
################################################################################



















### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);
if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

exit;





################################################################################
##### SUBROUTINES
################################################################################

sub populate_main_section
	{
	# break out settings into local variables
	$temp_type = $temp_type_ARY[$v];
	$temp_typeS = $temp_typeS_ARY[$v];
	$temp_table = $temp_table_ARY[$v];
	$temp_id_field = $temp_id_field_ARY[$v];
	$temp_date_check_SQL = $temp_date_check_SQL_ARY[$v];
	$temp_first_fields = $temp_first_fields_ARY[$v];
	$temp_date_field = $temp_date_field_ARY[$v];
	$fields_skip_list = $fields_skip_list_ARY[$v];
	$fields_insert_list = $fields_insert_list_ARY[$v];
	$SUBS_tables_list = $SUBS_tables_list_ARY[$v];
	$SUBS_delete_first = $SUBS_delete_first_ARY[$v];
	$SUBS_tables_details = $SUBS_tables_details_ARY[$v];
	@SUBS_tables_details_split = split(/,/,$SUBS_tables_details);
	$SUBS_tables_details_split_rows = scalar(@SUBS_tables_details_split);
	$d_ct=0;
	while ($d_ct < $SUBS_tables_details_split_rows) 
		{
		@SUBS_tables_details_detail = split(/---/,$SUBS_tables_details_split[$d_ct]);
		$SUB_table[$d_ct] = $SUBS_tables_details_detail[0];
		$SUB_fields[$d_ct] = $SUBS_tables_details_detail[1];
		$d_ct++;
		}
	if ($d_ct < 1) 
		{
		@SUB_table=@MT;
		@SUB_fields=@MT;
		}

	########################################################
	### BEGIN Check if there are any MAIN changes within the previous X minutes, or ALL
	$vicidial_main_ct=0;
	$stmtA = "SELECT count(*) from $temp_table where $temp_date_check_SQL;";
	if ($ALL_SETTINGS > 0) 
		{$stmtA = "SELECT count(*) from $temp_table;";   $process_options.='ALL_SETTINGS';}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_main_ct =	$aryA[0];
		}
	$sthA->finish();

	if ( ($vicidial_main_ct < 1) ) 
		{
		if (!$Q) {print "No $temp_type updated recently, nothing to do, skipping... \n";}
		}
	else
		{
		if (!$Q) {print "Recent changes -   $temp_type: $vicidial_main_ct \n";}

		$o_ct=0;
		$stmtA = "SELECT $temp_first_fields from $temp_table where $temp_date_check_SQL limit 10000000;";
		if ($ALL_SETTINGS > 0) 
			{$stmtA = "SELECT $temp_first_fields from $temp_table;";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		while ($sthArows > $o_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$updated_MAINS[$o_ct] =			$aryA[0];
			$updated_MAINSepoch[$o_ct] =	$aryA[2];
			$o_ct++;
			}
		$sthA->finish();
		$updated_MAINSsize = $o_ct;

		### loop through all mains, compare to record on CLR server, update/insert if needed
		$u_ct=0;
		$CLRupdate_ct=0;	$CLRupdate_aff=0;
		$CLRinsert_ct=0;	$CLRinsert_aff=0;
		$CLRskip_ct=0;
		$CLRnochange_ct=0;
		$MAIN_updated=0;
		while ($updated_MAINSsize > $u_ct) 
			{
			$o_ct=0;
			$fields_list='';
			$stmtA = "SHOW COLUMNS from $temp_table;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			while ($sthArows > $o_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$fields_list .=	"$aryA[0],";
				$o_ct++;
				}
			$sthA->finish();
			$fields_list =~ s/,$//gi;

			$update_phase=1;
			# gather main data from ORIGINAL database
			$stmtA = "SELECT $fields_list from $temp_table where $temp_id_field='$updated_MAINS[$u_ct]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@proc_fields = split(/,/,$fields_list);
				$proc_fields_size = @proc_fields;
				$proc_skip_fields = ",$fields_skip_list,";
				$AupdateSQL='';
				$Acompare='';
				$p_ct=0;
				@aryA = $sthA->fetchrow_array;
				while($proc_fields_size > $p_ct)
					{
					if ($proc_skip_fields =~ /,$proc_fields[$p_ct],/) 
						{if ($DBX > 0) {print "SKIP $p_ct|$proc_fields[$p_ct]|$aryA[$p_ct]| \n";}}
					else
						{
						if (defined $aryA[$p_ct]) 
							{
							$temp_field = $aryA[$p_ct];
							$temp_fieldSQL = "\"$temp_field\"";
							} 
						else 
							{
							$temp_field='NULL';
							$temp_fieldSQL = 'NULL';
							}
						$Acompare	.= "$temp_field|";
						$AupdateSQL	.= "$proc_fields[$p_ct]=$temp_fieldSQL,";
						}
					$p_ct++;
					}
				$AupdateSQL =~ s/,$//gi;
				}
			else
				{
				$update_phase=0;
				if ($DBX > 0) 
					{print "DEBUG: No ORIGINAL $temp_typeS found for $temp_typeS $updated_MAINS[$u_ct], skipping \n";}
				}
			$sthA->finish();

			# gather main data from CLR database
			$stmtB = "SELECT $fields_list,UNIX_TIMESTAMP($temp_date_field) from $temp_table where $temp_id_field='$updated_MAINS[$u_ct]';";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$BupdateSQL='';
				$Bcompare='';
				$Bupdated_MAINSepoch=0;
				$p_ct=0;
				@aryB = $sthB->fetchrow_array;
				while($proc_fields_size > $p_ct)
					{
					if ($proc_skip_fields =~ /,$proc_fields[$p_ct],/) 
						{if ($DBX > 0) {print "SKIP $p_ct|$proc_fields[$p_ct]|$aryA[$p_ct]| \n";}}
					else
						{
						if (defined $aryB[$p_ct]) 
							{
							$temp_field = $aryB[$p_ct];
							$temp_fieldSQL = "\"$temp_field\"";
							} 
						else 
							{
							$temp_field='NULL';
							$temp_fieldSQL = 'NULL';
							}
						$Bcompare	.= "$temp_field|";
						$BupdateSQL	.= "$proc_fields[$p_ct]=$temp_fieldSQL,";
						}
					$p_ct++;
					$Bupdated_MAINSepoch = $aryB[$p_ct];
					}
				$BupdateSQL =~ s/,$//gi;
				}
			else
				{
				if ($update_phase > 0) 
					{
					$update_phase=2;
					if ($DBX > 0) {print "DEBUG: No CLR match found for $temp_typeS $updated_MAINS[$u_ct], inserting instead \n";}
					}
				}
			$sthB->finish();

			if ($update_phase >= 2) 
				{
				# insert main into CLR database

				$stmtB = "INSERT IGNORE INTO $temp_table SET $temp_id_field='$updated_MAINS[$u_ct]'$fields_insert_list,$AupdateSQL;";
				$affected_rowsB = $dbhB->do($stmtB);
				$CLRinsert_aff = ($CLRinsert_aff + $affected_rowsB);
				if ($DBX > 0) {print "DEBUG: CLR $temp_typeS inserted $updated_MAINS[$u_ct]: $affected_rowsB|$stmtB| \n";}

				$CLRinsert_ct++;
				$MAIN_updated++;
				}
			else
				{
				# compare ORIGINAL and CLR main data to see if update is needed
				if ($Acompare eq $Bcompare) 
					{
					$update_phase=0;
					if ($DBX > 0) {print "DEBUG: ORIGINAL and CLR $temp_typeS data is identical: $updated_MAINS[$u_ct] \n";}
					if ($ALL_SETTINGS > 0) 
						{$MAIN_updated++;}
					}
				if ($DBX > 0) {print "$updated_MAINS[$u_ct] compare:\n$Acompare \n$Bcompare \n";}

				if ($update_phase >= 1) 
					{
					if ($updated_MAINSepoch[$u_ct] < $Bupdated_MAINSepoch) 
						{
						if ($DBX > 0) {print "DEBUG: CLR $temp_typeS record is newer than ORIGINAL: $updated_MAINS[$u_ct] ($updated_MAINSepoch[$u_ct] > $Bupdated_MAINSepoch) \n";}
						$CLRskip_ct++;
						}
					else
						{
						if ($DBX > 0) {print "DEBUG: CLR $temp_typeS record is older or same age as ORIGINAL: $updated_MAINS[$u_ct] ($updated_MAINSepoch[$u_ct] > $Bupdated_MAINSepoch) \n";}
						# update existing main in CLR database
						$stmtB = "UPDATE $temp_table SET $AupdateSQL where $temp_id_field='$updated_MAINS[$u_ct]';";
						$affected_rowsB = $dbhB->do($stmtB);
						$CLRupdate_aff = ($CLRupdate_aff + $affected_rowsB);
						if ($DBX > 0) {print "DEBUG: CLR $temp_typeS updated $updated_MAINS[$u_ct]: $affected_rowsB|$stmtB| \n";}

						$CLRupdate_ct++;
						$MAIN_updated++;
						}
					}
				else
					{
					# no change needed to main in CLR database
					$CLRnochange_ct++;
					}
				}

			### If main has been updated or inserted, then insert/update the statuses/hotkeys/pause-codes/presets/etc... data for this main
			if ( ($MAIN_updated > 0) && (length($SUBS_tables_list) > 5) )
				{
				# subs-table processing goes here
				@subs_to_process = split(/,/,$SUBS_tables_list);

				$sub_ct=0;
				foreach(@subs_to_process)
					{
					if ($DBX > 0) {print "DEBUG: sub-category to process:  $subs_to_process[$sub_ct] \n";}

					if ( ($SUBS_delete_first > 0) && ($ALL_SETTINGS > 0) ) 
						{
						# delete and optimize all secondary records from the CLR database for this MAIN before re-populating
						$stmtB = "DELETE from $subs_to_process[$sub_ct] WHERE $temp_id_field='$updated_MAINS[$u_ct]';";
					#	$affected_rowsB = $dbhB->do($stmtB);
						$CLRupdate_aff = ($CLRupdate_aff + $affected_rowsB);
						if ($DB > 0) {print "DEBUG: CLR $subs_to_process[$sub_ct] purged: $affected_rowsB|$stmtB| \n";}

						$stmtB = "OPTIMIZE TABLE $subs_to_process[$sub_ct];";
					#	$affected_rowsB = $dbhB->do($stmtB);
						$CLRupdate_aff = ($CLRupdate_aff + $affected_rowsB);
						if ($DB > 0) {print "DEBUG: CLR $subs_to_process[$sub_ct] optimized: $affected_rowsB|$stmtB| \n";}
						}

					$temp_subs_table = $subs_to_process[$sub_ct];
					$temp_subs_fields = $SUB_table[$sub_ct];

					&populate_subs_section;

					$sub_ct++;
					}
				}

			$u_ct++;
			if ($Q < 1) 
				{
				if ($u_ct =~ /100$/i) {print STDERR ">         $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /200$/i) {print STDERR "->        $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /300$/i) {print STDERR " ->       $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /400$/i) {print STDERR "  ->      $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /500$/i) {print STDERR "   ->     $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /600$/i) {print STDERR "    ->    $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /700$/i) {print STDERR "     ->   $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /800$/i) {print STDERR "      ->  $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /900$/i) {print STDERR "       -> $u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /000$/i) {print STDERR "        ->$u_ct / $updated_MAINSsize \r";}
				if ($u_ct =~ /0000$/i) 
					{
					$secY = time();
					$secZ = ($secY - $secX);
					print "$u_ct|$CLRupdate_ct|$CLRinsert_ct|$CLRnochange_ct|   $secZ sec \n";
					}
				}
			}
		}

	$lead_output  = "CLR $temp_typeS updates:    $CLRupdate_ct ($CLRupdate_aff) \n";
	$lead_output .= "CLR $temp_typeS inserts:    $CLRinsert_ct ($CLRinsert_aff) \n";
	$lead_output .= "CLR $temp_typeS skips:      $CLRskip_ct \n";
	$lead_output .= "CLR $temp_typeS no changes: $CLRnochange_ct \n";
	if (!$Q) {print $lead_output;}

	$process_output .= $lead_output;
	########################################################
	### END Check if there are any main changes within the previous X minutes
	}



sub populate_subs_section
	{
	########################################################
	### BEGIN Check if there are any SUBS changes within the previous X minutes, or ALL
	$temp_subs_table = $subs_to_process[$sub_ct];
	$temp_subs_fields = $SUB_fields[$sub_ct];
	$vicidial_sub_ct=0;
	$stmtA = "SELECT count(*) from $temp_subs_table where $temp_id_field='$updated_MAINS[$u_ct]';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_sub_ct =	$aryA[0];
		}
	$sthA->finish();

	if ( ($vicidial_sub_ct < 1) ) 
		{
		if (!$Q) {print "No $temp_subs_table updated recently, nothing to do, skipping... \n";}
		}
	else
		{
		if (!$Q) {print "Recent changes -   $temp_subs_table: $vicidial_sub_ct \n";}

		$o_ct=0;
		$stmtA = "SELECT $temp_subs_fields from $temp_subs_table where $temp_id_field='$updated_MAINS[$u_ct]' limit 10000000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		while ($sthArows > $o_ct)
			{
			@aryA = $sthA->fetchrow_array;
			$updated_SUBS[$o_ct] =			$aryA[0];
			$o_ct++;
			}
		$sthA->finish();
		$updated_SUBSsize = $o_ct;

		### loop through all subs, compare to record on CLR server, update/insert if needed
		$s_ct=0;
		$sCLRupdate_ct=0;	$sCLRupdate_aff=0;
		$sCLRinsert_ct=0;	$sCLRinsert_aff=0;
		$sCLRskip_ct=0;
		$sCLRnochange_ct=0;
		$SUB_updated=0;
		while ($updated_SUBSsize > $s_ct) 
			{
			$o_ct=0;
			$subs_fields_list='';
			$stmtA = "SHOW COLUMNS from $temp_subs_table;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			while ($sthArows > $o_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$subs_fields_list .=	"$aryA[0],";
				$o_ct++;
				}
			$sthA->finish();
			$subs_fields_list =~ s/,$//gi;

			$update_phase=1;
			# gather subs data from ORIGINAL database
			$stmtA = "SELECT $subs_fields_list from $temp_subs_table where $temp_id_field='$updated_MAINS[$u_ct]' and $temp_subs_fields='$updated_SUBS[$s_ct]';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@tsub_fields = split(/,/,$subs_fields_list);
				$tsub_fields_size = @tsub_fields;
				$tsub_skip_fields = ",$temp_subs_fields,";
				$AupdateSQL='';
				$Acompare='';
				$p_ct=0;
				@aryA = $sthA->fetchrow_array;
				while($tsub_fields_size > $p_ct)
					{
					if ($tsub_skip_fields =~ /,$tsub_fields[$p_ct],/) 
						{if ($DBX > 0) {print "SKIP $p_ct|$tsub_fields[$p_ct]|$aryA[$p_ct]| \n";}}
					else
						{
						if (defined $aryA[$p_ct]) 
							{
							$temp_field = $aryA[$p_ct];
							$temp_fieldSQL = "\"$temp_field\"";
							} 
						else 
							{
							$temp_field='NULL';
							$temp_fieldSQL = 'NULL';
							}
						$Acompare	.= "$temp_field|";
						$AupdateSQL	.= "$tsub_fields[$p_ct]=$temp_fieldSQL,";
						}
					$p_ct++;
					}
				$AupdateSQL =~ s/,$//gi;
				}
			else
				{
				$update_phase=0;
				if ($DBX > 0) 
					{print "DEBUG: No ORIGINAL $temp_subs_table found for $updated_SUBS[$s_ct], skipping \n";}
				}
			$sthA->finish();

			# gather subs data from CLR database
			$stmtB = "SELECT $subs_fields_list from $temp_subs_table where $temp_id_field='$updated_MAINS[$u_ct]' and $temp_subs_fields='$updated_SUBS[$s_ct]';";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$BupdateSQL='';
				$Bcompare='';
				$p_ct=0;
				@aryB = $sthB->fetchrow_array;
				while($tsub_fields_size > $p_ct)
					{
					if ($tsub_skip_fields =~ /,$tsub_fields[$p_ct],/) 
						{if ($DBX > 0) {print "SKIP $p_ct|$tsub_fields[$p_ct]|$aryB[$p_ct]| \n";}}
					else
						{
						if (defined $aryB[$p_ct]) 
							{
							$temp_field = $aryB[$p_ct];
							$temp_fieldSQL = "\"$temp_field\"";
							} 
						else 
							{
							$temp_field='NULL';
							$temp_fieldSQL = 'NULL';
							}
						$Bcompare	.= "$temp_field|";
						$BupdateSQL	.= "$tsub_fields[$p_ct]=$temp_fieldSQL,";
						}
					$p_ct++;
					}
				$BupdateSQL =~ s/,$//gi;
				}
			else
				{
				if ($update_phase > 0) 
					{
					$update_phase=2;
					if ($DBX > 0) {print "DEBUG: No CLR match found for $temp_subs_table $updated_SUBS[$s_ct], inserting instead \n";}
					}
				}
			$sthB->finish();

			if ($update_phase >= 2) 
				{
				# insert subs into CLR database

				$stmtB = "INSERT IGNORE INTO $temp_subs_table SET $temp_subs_fields='$updated_SUBS[$s_ct]',$AupdateSQL;";
				$affected_rowsB = $dbhB->do($stmtB);
				$sCLRinsert_aff = ($sCLRinsert_aff + $affected_rowsB);
				if ($DBX > 0) {print "DEBUG: CLR $temp_typeS inserted $updated_SUBS[$s_ct]: $affected_rowsB|$stmtB| \n";}

				$sCLRinsert_ct++;
				$SUB_updated++;
				}
			else
				{
				# compare ORIGINAL and CLR sub data to see if update is needed
				if ($Acompare eq $Bcompare) 
					{
					$update_phase=0;
					if ($DBX > 0) {print "DEBUG: ORIGINAL and CLR $temp_subs_table data is identical: $updated_SUBS[$s_ct] \n";}
					if ($ALL_SETTINGS > 0) 
						{$SUB_updated++;}
					}
				if ($DBX > 0) {print "$updated_SUBS[$s_ct] compare:\n$Acompare \n$Bcompare \n";}

				if ($update_phase >= 1) 
					{
					if ($DBX > 0) {print "DEBUG: CLR $temp_subs_table record is older or same age as ORIGINAL: $updated_SUBS[$s_ct] ($updated_SUBSepoch[$s_ct] > $Bupdated_SUBSepoch) \n";}
					# update existing sub in CLR database
					$stmtB = "UPDATE $temp_subs_table SET $AupdateSQL where $temp_id_field='$updated_MAINS[$u_ct]' and $temp_subs_fields='$updated_SUBS[$s_ct]';";
					$affected_rowsB = $dbhB->do($stmtB);
					$sCLRupdate_aff = ($sCLRupdate_aff + $affected_rowsB);
					if ($DBX > 0) {print "DEBUG: CLR $temp_typeS updated $updated_SUBS[$s_ct]: $affected_rowsB|$stmtB| \n";}

					$sCLRupdate_ct++;
					$SUB_updated++;
					}
				else
					{
					# no change needed to sub in CLR database
					$sCLRnochange_ct++;
					}
				}

			$s_ct++;
			if ($Q < 1) 
				{
				if ($s_ct =~ /100$/i) {print STDERR ">         $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /200$/i) {print STDERR "->        $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /300$/i) {print STDERR " ->       $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /400$/i) {print STDERR "  ->      $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /500$/i) {print STDERR "   ->     $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /600$/i) {print STDERR "    ->    $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /700$/i) {print STDERR "     ->   $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /800$/i) {print STDERR "      ->  $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /900$/i) {print STDERR "       -> $s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /000$/i) {print STDERR "        ->$s_ct / $updated_SUBSsize \r";}
				if ($s_ct =~ /0000$/i) 
					{
					$secY = time();
					$secZ = ($secY - $secX);
					print "$s_ct|$sCLRupdate_ct|$sCLRinsert_ct|$sCLRnochange_ct|   $secZ sec \n";
					}
				}
			}
		}

	$lead_output  = "   CLR sub $temp_subs_table updates:    $sCLRupdate_ct ($sCLRupdate_aff) \n";
	$lead_output .= "   CLR sub $temp_subs_table inserts:    $sCLRinsert_ct ($sCLRinsert_aff) \n";
	$lead_output .= "   CLR sub $temp_subs_table skips:      $sCLRskip_ct \n";
	$lead_output .= "   CLR sub $temp_subs_table no changes: $sCLRnochange_ct \n";
	if (!$Q) {print $lead_output;}

	$process_output .= $lead_output;
	########################################################
	### END Check if there are any subs changes within the previous X minutes
	}
