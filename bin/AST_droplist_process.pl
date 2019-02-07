#!/usr/bin/perl
#
# AST_droplist_process.pl                version: 2.14
#
# This script is designed to run the drop list process.
#
# NOTE: You need to use Drop Lists in the admin web screens to configure a drop list process to run in this script
#     example:
# /usr/share/astguiclient/AST_droplist_process.pl --dl-id=test_drop_list
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 170326-1541 - First version
# 170330-0934 - populate new lead with field data from dropped lead
# 170410-1331 - Added dl_minutes option
#

$US = '_';
$MT[0] = '';
$log_to_adminlog=0;
$dl_id='';


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
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$filedate = "$year$mon$mday";
$ABIfiledate = "$mon-$mday-$year$us$hour$min$sec";
$shipdate = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$datestamp = "$year/$mon/$mday $hour:$min";
$hms = "$hour$min$sec";


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
		print "  [--quiet] = quiet\n";
		print "  [--test] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
		print "  [--log-to-adminlog] = Put an entry in the admin log after this process runs\n";
		print "  [--dl-id=XXXXXXXX] = Drop List ID in the system to run the process for\n";
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
		if ($args =~ /-q/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			if ($DB > 0) {print "\n----- TESTING -----\n\n";}
			}
		if ($args =~ /--log-to-adminlog/) 
			{
			$log_to_adminlog=1;
			if ($DB > 0) {print "\n----- LOGGING TO THE ADMIN LOG -----\n\n";}
			}
		if ($args =~ /--dl-id=/i) 
			{
			@data_in = split(/--dl-id=/,$args);
			$dl_id = $data_in[1];
			$dl_id =~ s/ .*//gi;
			$dl_id =~ s/:/,/gi;
			if ($DB > 0) {print "\n----- DROP LIST ID: $dl_id -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set, exiting.\n";
	exit;
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
#	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
#		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
#		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
#		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
#	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
#		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_droplist_process.pl --\n\n";
	print "This program is designed to run the drop list process. \n";
	print "\n";
	}

if (length($dl_id) < 1) 
	{
	print "ERROR, no drop list ID defined\n";
	exit;
	}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmtA = "SELECT enable_drop_lists FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$enable_drop_lists		=		$aryA[0];
	}
$sthA->finish();
##### END SETTINGS LOOKUP #####
###########################################

if ($enable_drop_lists < 1) 
	{
	print "ERROR: enable_drop_lists is disabled in system settings, exiting...\n";
	exit;
	}

### Grab Server values from the database
$stmtA = "SELECT local_gmt FROM servers where server_ip = '$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
@aryA = $sthA->fetchrow_array;
$DBSERVER_GMT		=		$aryA[0];
if (length($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
$sthA->finish();

	$LOCAL_GMT_OFF = $SERVER_GMT;
	$LOCAL_GMT_OFF_STD = $SERVER_GMT;

if ($isdst) {$LOCAL_GMT_OFF++;} 
if ($DB) {print "SEED TIME  $secX      :   $year-$mon-$mday $hour:$min:$sec  LOCAL GMT OFFSET NOW: $LOCAL_GMT_OFF\n";}


### Grab Server values from the database
$stmtA = "SELECT closer_campaigns,drop_statuses,duplicate_check,list_id,dl_minutes from vicidial_drop_lists where dl_id='$dl_id';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$closer_campaigns =	$aryA[0];
	$drop_statuses =	$aryA[1];
	$duplicate_check =	$aryA[2];
	$list_id =			$aryA[3];
	$dl_minutes =		$aryA[4];

	$drop_statusesSQL = $drop_statuses;
	$drop_statusesSQL =~ s/^  |^ | -$//gi;
	$drop_statusesSQL =~ s/ /','/gi;
	$drop_statusesSQL = "and status IN('$drop_statusesSQL')";

	$closer_campaignsSQL = $closer_campaigns;
	$closer_campaignsSQL =~ s/^ | -$//gi;
	$closer_campaignsSQL =~ s/ /','/gi;
	$closer_campaignsSQL = "and campaign_id IN('$closer_campaignsSQL')";

	$drop_dateSQL='';
	if ($dl_minutes > 0) 
		{
		$dl_sec = ($dl_minutes * 60);
		$BDtarget = ($secX - $dl_sec);
		($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
		$Byear = ($Byear + 1900);
		$Bmon++;
		if ($Bmon < 10) {$Bmon = "0$Bmon";}
		if ($Bmday < 10) {$Bmday = "0$Bmday";}
		if ($Bhour < 10) {$Bhour = "0$Bhour";}
		if ($Bmin < 10) {$Bmin = "0$Bmin";}
		if ($Bsec < 10) {$Bsec = "0$Bsec";}
		$BDtsSQLdate = "$Byear-$Bmon-$Bmday $Bhour:$Bmin:$Bsec";
		$drop_dateSQL = "and drop_date >= \"$BDtsSQLdate\"";
		}
	if ($DB) {print "DROP LIST RUN: |$dl_id|$list_id|$dl_minutes($BDtsSQLdate)|\n";}
	}
else
	{
	if (!$Q) {print "ERROR: drop list not found: $dl_id\n";}
	exit;
	}
$sthA->finish();

if (length($list_id) < 2)
	{
	if (!$Q) {print "ERROR: list_id not defined: $list_id\n";}
	exit;
	}

### gather user information for last manager who edited the drop list
$stmtA = "SELECT user from vicidial_admin_log where event_section='DROPLISTS' and record_id='$dl_id' and event_type IN('ADD','MODIFY','COPY','DELETE') order by admin_log_id desc limit 1;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$http_user =	$aryA[0];
	}
else
	{
	if (!$Q) {print "ERROR: No drop list manager user found: $dl_id\n";}
	exit;
	}
$sthA->finish();

### If duplicate check enabled, find campaign lists
$duplicate_checkSQL='';
if ($duplicate_check =~ /LIST/) 
	{
	if ($duplicate_check =~ /LIST_CAMPAIGN_LISTS/) 
		{
		$list_campaign_id='';
		$stmtA = "SELECT campaign_id from vicidial_lists where list_id='$list_id';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$list_campaign_id =	$aryA[0];
			}
		$sthA->finish();
		if (length($list_campaign_id) > 0)
			{
			$lists_SQL='';
			$stmtA = "SELECT list_id from vicidial_lists where campaign_id='$list_campaign_id';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$r=0;
			while ($sthArows > $r)
				{
				@aryA = $sthA->fetchrow_array;
				$lists_SQL .=	"'$aryA[0]',";
				$r++;
				}
			$sthA->finish();
			$lists_SQL =~ s/,$//gi;
			$duplicate_checkSQL = "and list_id IN($lists_SQL)";
			}
		else
			{
			$duplicate_checkSQL = "and list_id='$list_id'";
			}
		}
	else
		{
		$duplicate_checkSQL = "and list_id='$list_id'";
		}
	}


##### BEGIN drop list process #####
$insert_counter=0;
$duplicate_counter=0;
$stmtB="UPDATE vicidial_drop_log set drop_processed='U' where drop_processed='N' $drop_dateSQL $closer_campaignsSQL $drop_statusesSQL;";
$Baffected_rows = $dbhA->do($stmtB);
if ($DB) {print "DROP LOG UPDATE: |$Baffected_rows|$stmtB|\n";}

$stmtA = "SELECT lead_id,drop_date,phone_code,phone_number,campaign_id,status,uniqueid from vicidial_drop_log where drop_processed='U' $drop_dateSQL $closer_campaignsSQL $drop_statusesSQL order by drop_date;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthDROProws=$sthA->rows;
if ($DB) {print "DROP LOG GATHER: |$sthArows|$stmtA|\n";}
$q=0;
while ($sthDROProws > $q)
	{
	@aryA = $sthA->fetchrow_array;
	$Alead_id[$q] =			$aryA[0];
	$Adrop_date[$q] =		$aryA[1];
	$Aphone_code[$q] =		$aryA[2];
	$Aphone_number[$q] =	$aryA[3];
	$Acampaign_id[$q] =		$aryA[4];
	$Astatus[$q] =			$aryA[5];
	$Auniqueid[$q] =		$aryA[6];
	if ($DB) {print "DROP LIST RUN: |$dl_id|$list_id|\n";}
	$q++;
	}
$sthA->finish();

$q=0;
while ($sthDROProws > $q)
	{
	$ingroup_name='';
	$stmtB = "SELECT group_name from vicidial_inbound_groups where group_id='$Acampaign_id[$q]';";
	$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthBrows=$sthA->rows;
	if ($sthBrows > 0) 
		{
		@aryA = $sthA->fetchrow_array;
		$ingroup_name = $aryA[0];
		}
	$sthA->finish();

	$duplicate=0;
	$USarea = 	substr($Aphone_number[$q], 0, 3);
	if ($duplicate_check =~ /LIST/)
		{
		$stmtB = "SELECT count(*) from vicidial_list where phone_number='$Aphone_number[$q]' $duplicate_checkSQL;";
		$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthBrows=$sthA->rows;
		if ($sthBrows > 0) 
			{
			@aryA = $sthA->fetchrow_array;
			$duplicate = $aryA[0];
			}
		$sthA->finish();
		}
	if ($duplicate < 1)
		{
		### BEGIN figure out the timezone of the phone number ###
		$PC_processed=0;
		### UNITED STATES ###
		if ($Aphone_code[$q] =~ /^1$/)
			{
			$stmtA = "SELECT country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$Aphone_code[$q]' and areacode='$USarea';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$gmt_offset =	$aryA[4];  $gmt_offset =~ s/\+| //gi;
				$dst =			$aryA[5];
				$dst_range =	$aryA[6];
				$PC_processed++;
				$rec_count++;
				}
			$sthA->finish();
			}
		### MEXICO ###
		if ($Aphone_code[$q] =~ /^52$/)
			{
			$stmtA = "SELECT country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$Aphone_code[$q]' and areacode='$USarea';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$gmt_offset =	$aryA[4];  $gmt_offset =~ s/\+| //gi;
				$dst =			$aryA[5];
				$dst_range =	$aryA[6];
				$PC_processed++;
				$rec_count++;
				}
			$sthA->finish();
			}
		### AUSTRALIA ###
		if ($Aphone_code[$q] =~ /^61$/)
			{
			$stmtA = "SELECT country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$Aphone_code[$q]' and state='$state';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$gmt_offset =	$aryA[4];  $gmt_offset =~ s/\+| //gi;
				$dst =			$aryA[5];
				$dst_range =	$aryA[6];
				$PC_processed++;
				$rec_count++;
				}
			$sthA->finish();
			}
		### ALL OTHER COUNTRY CODES ###
		if (!$PC_processed)
			{
			$stmtA = "SELECT country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$Aphone_code[$q]';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$gmt_offset =	$aryA[4];  $gmt_offset =~ s/\+| //gi;
				$dst =			$aryA[5];
				$dst_range =	$aryA[6];
				$PC_processed++;
				$rec_count++;
				}
			$sthA->finish();
			}

		### Find out if DST to raise the gmt offset ###
			$AC_GMT_diff = ($area_GMT - $LOCAL_GMT_OFF_STD);
			$AC_localtime = ($secX + (3600 * $AC_GMT_diff));
		($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($AC_localtime);
		$year = ($year + 1900);
		$mon++;
		if ($mon < 10) {$mon = "0$mon";}
		if ($mday < 10) {$mday = "0$mday";}
		if ($hour < 10) {$hour = "0$hour";}
		if ($min < 10) {$min = "0$min";}
		if ($sec < 10) {$sec = "0$sec";}
		$dsec = ( ( ($hour * 3600) + ($min * 60) ) + $sec );
		
		$AC_processed=0;

		if ( (!$AC_processed) && ($dst_range =~ /SSM-FSN/) )
			{
			if ($DBX) {print "     Second Sunday March to First Sunday November\n";}
			&USACAN_dstcalc;
			if ($DBX) {print "     DST: $USACAN_DST\n";}
			if ($USACAN_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /FSA-LSO/) )
			{
			if ($DBX) {print "     First Sunday April to Last Sunday October\n";}
			&NA_dstcalc;
			if ($DBX) {print "     DST: $NA_DST\n";}
			if ($NA_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /LSM-LSO/) )
			{
			if ($DBX) {print "     Last Sunday March to Last Sunday October\n";}
			&GBR_dstcalc;
			if ($DBX) {print "     DST: $GBR_DST\n";}
			if ($GBR_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /LSO-LSM/) )
			{
			if ($DBX) {print "     Last Sunday October to Last Sunday March\n";}
			&AUS_dstcalc;
			if ($DBX) {print "     DST: $AUS_DST\n";}
			if ($AUS_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /FSO-LSM/) )
			{
			if ($DBX) {print "     First Sunday October to Last Sunday March\n";}
			&AUST_dstcalc;
			if ($DBX) {print "     DST: $AUST_DST\n";}
			if ($AUST_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($area_GMT_method =~ /FSO-FSA/) )
			{
			if ($DBX) {print "     First Sunday October to First Sunday April\n";}
			&AUSE_dstcalc;
			if ($DBX) {print "     DST: $AUSE_DST\n";}
			if ($AUSE_DST) {$area_GMT++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /FSO-TSM/) )
			{
			if ($DBX) {print "     First Sunday October to Third Sunday March\n";}
			&NZL_dstcalc;
			if ($DBX) {print "     DST: $NZL_DST\n";}
			if ($NZL_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($area_GMT_method =~ /LSS-FSA/) )
			{
			if ($DBX) {print "     Last Sunday September to First Sunday April\n";}
			&NZLN_dstcalc;
			if ($DBX) {print "     DST: $NZLN_DST\n";}
			if ($NZLN_DST) {$area_GMT++;}
			$AC_processed++;
			}
		if ( (!$AC_processed) && ($dst_range =~ /TSO-LSF/) )
			{
			if ($DBX) {print "     Third Sunday October to Last Sunday February\n";}
			&BZL_dstcalc;
			if ($DBX) {print "     DST: $BZL_DST\n";}
			if ($BZL_DST) {$gmt_offset++;}
			$AC_processed++;
			}
		if (!$AC_processed)
			{
			if ($DBX) {print "     No DST Method Found\n";}
			if ($DBX) {print "     DST: 0\n";}
			$AC_processed++;
			}
		### END figure out the timezone of the phone number ###

		### BEGIN gather existing field data for lead ###
		$stmtB = "SELECT vendor_lead_code,source_id,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,rank,owner from vicidial_list where lead_id='$Alead_id[$q]';";
		$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthBrows=$sthA->rows;
		if ($sthBrows > 0) 
			{
			@aryA = $sthA->fetchrow_array;
			$vendor_lead_code =		$aryA[0];
			$source_id =			$aryA[1];
			$title =				$aryA[2];
			$first_name =			$aryA[3];
			$middle_initial =		$aryA[4];
			$last_name =			$aryA[5];
			$address1 =				$aryA[6];
			$address2 =				$aryA[7];
			$address3 =				$aryA[8];
			$city =					$aryA[9];
			$state =				$aryA[10];
			$province =				$aryA[11];
			$postal_code =			$aryA[12];
			$country_code =			$aryA[13];
			$gender =				$aryA[14];
			$date_of_birth =		$aryA[15];
			$alt_phone =			$aryA[16];
			$email =				$aryA[17];
			$security_phrase =		$aryA[18];
			$comments =				$aryA[19];
			$rank =					$aryA[20];
			$owner =				$aryA[21];
			}
		$sthA->finish();

		if (length($comments)>0) 
			{$comments .= " $Acampaign_id[$q] - $ingroup_name - $Adrop_date[$q]";}
		else 
			{$comments = "$Acampaign_id[$q] - $ingroup_name - $Adrop_date[$q]";}
		if (length($source_id)<1) 
			{$source_id = "$Alead_id[$q]";}
		### END gather existing field data for lead ###

		$stmtC="INSERT INTO vicidial_list set entry_date=NOW(),user='',phone_number='$Aphone_number[$q]',phone_code='$Aphone_code[$q]',list_id='$list_id',last_local_call_time='$Adrop_date[$q]',status='NEW',called_since_last_reset='N',gmt_offset_now='$gmt_offset',vendor_lead_code='$vendor_lead_code',source_id='$source_id',title='$title',first_name='$first_name',middle_initial='$middle_initial',last_name='$last_name',address1='$address1',address2='$address2',address3='$address3',city='$city',state='$state',province='$province',postal_code='$postal_code',country_code='$country_code',gender='$gender',date_of_birth='$date_of_birth',alt_phone='$alt_phone',email='$email',security_phrase='$security_phrase',comments='$comments',rank='$rank',owner='$owner';";
		$Caffected_rows = $dbhA->do($stmtC);
		$insert_counter++;

		if ($DB) {print "DROP LIST INSERT: |$insert_counter|$Caffected_rows|$stmtC|\n";}
		}
	else
		{
		$duplicate_counter++;
		if ($DB) {print "DROP LIST DUPLICATE: |$duplicate_counter|$duplicate|$stmtB|\n";}
		}

	$stmtD="UPDATE vicidial_drop_log set drop_processed='Y' where drop_processed='U' and drop_date='$Adrop_date[$q]' and uniqueid='$Auniqueid[$q]';";
	$Daffected_rows = $dbhA->do($stmtD);
	if ($DB) {print "DROP LOG PROCESSED UPDATE: |$Daffected_rows|$stmtD|\n";}

	$q++;
	}


$secY = time();
$secRUN = ($secY - $secX);
if ($secRUN < 1) {$secRUN=1;}

$stmtA="UPDATE vicidial_drop_lists set last_run=NOW(),run_now_trigger='N' where dl_id='$dl_id';";
$affected_rows = $dbhA->do($stmtA);

if ($log_to_adminlog > 0)
	{
	$SQL_log = "$stmtA";
	$SQL_log =~ s/;|\\|\"//gi;
	$stmtB="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$http_user', ip_address='$server_ip', event_section='DROPLISTS', event_type='EXPORT', record_id='$dl_id', event_code='ADMIN DROP LIST RUN', event_sql=\"$SQL_log\", event_notes='Run time: $secRUN seconds. INSERTS: $insert_counter DUPLICATES: $duplicate_counter TOTAL: $q';";
	$Iaffected_rows = $dbhA->do($stmtB);
	if ($DB) {print "ADMIN LOGGING FINISHED:   $affected_rows|$Iaffected_rows|$stmtA|$stmtB\n";}
	}

exit;




sub USACAN_dstcalc {
#**********************************************************************
# SSM-FSN
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on Second Sunday March to First Sunday November at 2 am.
#     INPUTS:
#       mm              INTEGER       Month.
#       dd              INTEGER       Day of the month.
#       ns              INTEGER       Seconds into the day.
#       dow             INTEGER       Day of week (0=Sunday, to 6=Saturday)
#     OPTIONAL INPUT:
#       timezone        INTEGER       hour difference UTC - local standard time
#                                      (DEFAULT is blank)
#                                     make calculations based on UTC time, 
#                                     which means shift at 10:00 UTC in April
#                                     and 9:00 UTC in October
#     OUTPUT: 
#                       INTEGER       1 = DST, 0 = not DST
#
# S  M  T  W  T  F  S
# 1  2  3  4  5  6  7
# 8  9 10 11 12 13 14
#15 16 17 18 19 20 21
#22 23 24 25 26 27 28
#29 30 31
# 
# S  M  T  W  T  F  S
#    1  2  3  4  5  6
# 7  8  9 10 11 12 13
#14 15 16 17 18 19 20
#21 22 23 24 25 26 27
#28 29 30 31
# 
#**********************************************************************

	$USACAN_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 3 || $mm > 11) {
	$USACAN_DST=0;   return 0;
    } elsif ($mm >= 4 && $mm <= 10) {
	$USACAN_DST=1;   return 1;
    } elsif ($mm == 3) {
	if ($dd > 13) {
	    $USACAN_DST=1;   return 1;
	} elsif ($dd >= ($dow+8)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (7200+$timezone*3600)) {
		    $USACAN_DST=0;   return 0;
		} else {
		    $USACAN_DST=1;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 7200) {
		    $USACAN_DST=0;   return 0;
		} else {
		    $USACAN_DST=1;   return 1;
		}
	    }
	} else {
	    $USACAN_DST=0;   return 0;
	}
    } elsif ($mm == 11) {
	if ($dd > 7) {
	    $USACAN_DST=0;   return 0;
	} elsif ($dd < ($dow+1)) {
	    $USACAN_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (7200+($timezone-1)*3600)) {
		    $USACAN_DST=1;   return 1;
		} else {
		    $USACAN_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 7200) {
		    $USACAN_DST=1;   return 1;
		} else {
		    $USACAN_DST=0;   return 0;
		}
	    }
	} else {
	    $USACAN_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc




sub NA_dstcalc {
#**********************************************************************
# FSA-LSO
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on first Sunday in April and last Sunday in October at 2 am.
#**********************************************************************
    
	$NA_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 4 || $mm > 10) {
	$NA_DST=0;   return 0;
    } elsif ($mm >= 5 && $mm <= 9) {
	$NA_DST=1;   return 1;
    } elsif ($mm == 4) {
	if ($dd > 7) {
	    $NA_DST=1;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (7200+$timezone*3600)) {
		    $NA_DST=0;   return 0;
		} else {
		    $NA_DST=1;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 7200) {
		    $NA_DST=0;   return 0;
		} else {
		    $NA_DST=1;   return 1;
		}
	    }
	} else {
	    $NA_DST=0;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd < 25) {
	    $NA_DST=1;   return 1;
	} elsif ($dd < ($dow+25)) {
	    $NA_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (7200+($timezone-1)*3600)) {
		    $NA_DST=1;   return 1;
		} else {
		    $NA_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 7200) {
		    $NA_DST=1;   return 1;
		} else {
		    $NA_DST=0;   return 0;
		}
	    }
	} else {
	    $NA_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc




sub GBR_dstcalc {
#**********************************************************************
# LSM-LSO
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on last Sunday in March and last Sunday in October at 1 am.
#**********************************************************************
    
	$GBR_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 3 || $mm > 10) {
	$GBR_DST=0;   return 0;
    } elsif ($mm >= 4 && $mm <= 9) {
	$GBR_DST=1;   return 1;
    } elsif ($mm == 3) {
	if ($dd < 25) {
	    $GBR_DST=0;   return 0;
	} elsif ($dd < ($dow+25)) {
	    $GBR_DST=0;   return 0;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $GBR_DST=0;   return 0;
		} else {
		    $GBR_DST=1;   return 1;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $GBR_DST=0;   return 0;
		} else {
		    $GBR_DST=1;   return 1;
		}
	    }
	} else {
	    $GBR_DST=1;   return 1;
	}
    } elsif ($mm == 10) {
	if ($dd < 25) {
	    $GBR_DST=1;   return 1;
	} elsif ($dd < ($dow+25)) {
	    $GBR_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $GBR_DST=1;   return 1;
		} else {
		    $GBR_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $GBR_DST=1;   return 1;
		} else {
		    $GBR_DST=0;   return 0;
		}
	    }
	} else {
	    $GBR_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc




sub AUS_dstcalc {
#**********************************************************************
# LSO-LSM
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on last Sunday in October and last Sunday in March at 1 am.
#**********************************************************************
    
	$AUS_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 3 || $mm > 10) {
	$AUS_DST=1;   return 1;
    } elsif ($mm >= 4 && $mm <= 9) {
	$AUS_DST=0;   return 0;
    } elsif ($mm == 3) {
	if ($dd < 25) {
	    $AUS_DST=1;   return 1;
	} elsif ($dd < ($dow+25)) {
	    $AUS_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $AUS_DST=1;   return 1;
		} else {
		    $AUS_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $AUS_DST=1;   return 1;
		} else {
		    $AUS_DST=0;   return 0;
		}
	    }
	} else {
	    $AUS_DST=0;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd < 25) {
	    $AUS_DST=0;   return 0;
	} elsif ($dd < ($dow+25)) {
	    $AUS_DST=0;   return 0;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $AUS_DST=0;   return 0;
		} else {
		    $AUS_DST=1;   return 1;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $AUS_DST=0;   return 0;
		} else {
		    $AUS_DST=1;   return 1;
		}
	    }
	} else {
	    $AUS_DST=1;   return 1;
	}
    } # end of month checks
} # end of subroutine dstcalc





sub AUST_dstcalc {
#**********************************************************************
# FSO-LSM
#   TASMANIA ONLY
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on first Sunday in October and last Sunday in March at 1 am.
#**********************************************************************
    
	$AUST_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 3 || $mm > 10) {
	$AUST_DST=1;   return 1;
    } elsif ($mm >= 4 && $mm <= 9) {
	$AUST_DST=0;   return 0;
    } elsif ($mm == 3) {
	if ($dd < 25) {
	    $AUST_DST=1;   return 1;
	} elsif ($dd < ($dow+25)) {
	    $AUST_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $AUST_DST=1;   return 1;
		} else {
		    $AUST_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $AUST_DST=1;   return 1;
		} else {
		    $AUST_DST=0;   return 0;
		}
	    }
	} else {
	    $AUST_DST=0;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd >= 8) {
	    $AUST_DST=1;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (7200+$timezone*3600)) {
		    $AUST_DST=0;   return 0;
		} else {
		    $AUST_DST=1;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 3600) {
		    $AUST_DST=0;   return 0;
		} else {
		    $AUST_DST=1;   return 1;
		}
	    }
	} else {
	    $AUST_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc





sub AUSE_dstcalc {
#**********************************************************************
# FSO-FSA
#   2008+ AUSTRALIA ONLY (country code 61)
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on first Sunday in October and first Sunday in April at 1 am.
#**********************************************************************
    
	$AUSE_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 4 || $mm > 10) {
	$AUSE_DST=1;   return 1;
    } elsif ($mm >= 5 && $mm <= 9) {
	$AUSE_DST=0;   return 0;
    } elsif ($mm == 4) {
	if ($dd > 7) {
	    $AUSE_DST=0;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (3600+$timezone*3600)) {
		    $AUSE_DST=1;   return 0;
		} else {
		    $AUSE_DST=0;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 7200) {
		    $AUSE_DST=1;   return 0;
		} else {
		    $AUSE_DST=0;   return 1;
		}
	    }
	} else {
	    $AUSE_DST=1;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd >= 8) {
	    $AUSE_DST=1;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (7200+$timezone*3600)) {
		    $AUSE_DST=0;   return 0;
		} else {
		    $AUSE_DST=1;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 3600) {
		    $AUSE_DST=0;   return 0;
		} else {
		    $AUSE_DST=1;   return 1;
		}
	    }
	} else {
	    $AUSE_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc





sub NZL_dstcalc {
#**********************************************************************
# FSO-TSM
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on first Sunday in October and third Sunday in March at 1 am.
#**********************************************************************
    
	$NZL_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 3 || $mm > 10) {
	$NZL_DST=1;   return 1;
    } elsif ($mm >= 4 && $mm <= 9) {
	$NZL_DST=0;   return 0;
    } elsif ($mm == 3) {
	if ($dd < 14) {
	    $NZL_DST=1;   return 1;
	} elsif ($dd < ($dow+14)) {
	    $NZL_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $NZL_DST=1;   return 1;
		} else {
		    $NZL_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $NZL_DST=1;   return 1;
		} else {
		    $NZL_DST=0;   return 0;
		}
	    }
	} else {
	    $NZL_DST=0;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd >= 8) {
	    $NZL_DST=1;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (7200+$timezone*3600)) {
		    $NZL_DST=0;   return 0;
		} else {
		    $NZL_DST=1;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 3600) {
		    $NZL_DST=0;   return 0;
		} else {
		    $NZL_DST=1;   return 1;
		}
	    }
	} else {
	    $NZL_DST=0;   return 0;
	}
    } # end of month checks
} # end of subroutine dstcalc




sub NZLN_dstcalc {
#**********************************************************************
# LSS-FSA
#   2007+ NEW ZEALAND (country code 64)
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect.
#     Based on last Sunday in September and first Sunday in April at 1 am.
#**********************************************************************
    
	$NZLN_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 4 || $mm > 9) {
	$NZLN_DST=1;   return 1;
    } elsif ($mm >= 5 && $mm <= 9) {
	$NZLN_DST=0;   return 0;
    } elsif ($mm == 4) {
	if ($dd > 7) {
	    $NZLN_DST=0;   return 1;
	} elsif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (3600+$timezone*3600)) {
		    $NZLN_DST=1;   return 0;
		} else {
		    $NZLN_DST=0;   return 1;
		}
	    } else {
		if ($dow == 0 && $ns < 7200) {
		    $NZLN_DST=1;   return 0;
		} else {
		    $NZLN_DST=0;   return 1;
		}
	    }
	} else {
	    $NZLN_DST=1;   return 0;
	}
    } elsif ($mm == 9) {
	if ($dd < 25) {
	    $NZLN_DST=0;   return 0;
	} elsif ($dd < ($dow+25)) {
	    $NZLN_DST=0;   return 0;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $NZLN_DST=0;   return 0;
		} else {
		    $NZLN_DST=1;   return 1;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $NZLN_DST=0;   return 0;
		} else {
		    $NZLN_DST=1;   return 1;
		}
	    }
	} else {
	    $NZLN_DST=1;   return 1;
	}
    } # end of month checks
} # end of subroutine dstcalc





sub BZL_dstcalc {
#**********************************************************************
# TSO-LSF
#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
#       Standard time is in effect. Brazil
#     Based on Third Sunday October to Last Sunday February at 1 am.
#**********************************************************************
    
	$BZL_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 2 || $mm > 10) {
	$BZL_DST=1;   return 1;
    } elsif ($mm >= 3 && $mm <= 9) {
	$BZL_DST=0;   return 0;
    } elsif ($mm == 2) {
	if ($dd < 22) {
	    $BZL_DST=1;   return 1;
	} elsif ($dd < ($dow+22)) {
	    $BZL_DST=1;   return 1;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $BZL_DST=1;   return 1;
		} else {
		    $BZL_DST=0;   return 0;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $BZL_DST=1;   return 1;
		} else {
		    $BZL_DST=0;   return 0;
		}
	    }
	} else {
	    $BZL_DST=0;   return 0;
	}
    } elsif ($mm == 10) {
	if ($dd < 22) {
	    $BZL_DST=0;   return 0;
	} elsif ($dd < ($dow+22)) {
	    $BZL_DST=0;   return 0;
	} elsif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $BZL_DST=0;   return 0;
		} else {
		    $BZL_DST=1;   return 1;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $BZL_DST=0;   return 0;
		} else {
		    $BZL_DST=1;   return 1;
		}
	    }
	} else {
	    $BZL_DST=1;   return 1;
	}
    } # end of month checks
} # end of subroutine dstcalc
