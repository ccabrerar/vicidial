#!/usr/bin/perl
#
# ADMIN_custom_table_clean.pl                version: 2.14
#
# This script is designed to check for "custom_" database tables from deleted lists with no leads assigned to them
#
# NOTE!!! For this script to work, you must add the "DROP" database privledge to your "VARDB_custom_user" database account
# GRANT ALTER,CREATE,DROP on asterisk.* TO custom@'%' IDENTIFIED BY 'custom1234';
# GRANT ALTER,CREATE,DROP on asterisk.* TO custom@localhost IDENTIFIED BY 'custom1234';
#
# /usr/share/astguiclient/ADMIN_custom_table_clean.pl --debug
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 200728-2033 - First version
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;

$OUTcalls=0;
$OUTtalk=0;
$OUTtalkmin=0;
$INcalls=0;
$INtalk=0;
$INtalkmin=0;
$email_post_audio=0;
$did_only=0;

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
	if ( ($line =~ /^VARDB_custom_user/) && ($CLIDB_custom_user < 1) )
		{$VARDB_custom_user = $line;   $VARDB_custom_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_custom_pass/) && ($CLIDB_custom_pass < 1) )
		{$VARDB_custom_pass = $line;   $VARDB_custom_pass =~ s/.*=//gi;}
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
		print "  [--quiet] = quiet\n";
		print "  [--test] = testing only, no deletions\n";
		print "  [--remove-field-defs] = remove field definitions in addition to custom_ db tables\n";
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
		if ($args =~ /--test/i)
			{
			$TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--remove-field-defs/i)
			{
			$remove_field_defs=1;
			print "\n----- REMOVE FIELD DEFINITIONS -----\n\n";
			}
		}
	}
else
	{
	print "no command line options set, exiting.\n";
	exit;
	}
### end parsing run-time options ###



if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- ADMIN_custom_table_clean.pl --\n\n";
	print "This program is designed to clean custom_ field database tables from the database if the list has been deleted and no leads are assigned them. \n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_custom_user", "$VARDB_custom_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


$TOTAL_CUSTOM=0;
$INVALID_CUSTOM_TABLE=0;
$CUSTOM_TABLE_FOUND=0;
$CUSTOM_TABLE_LEADS_FOUND=0;
$TOTAL_DELETED=0;


$stmtB = "SHOW TABLES LIKE \"custom\\_\%\";";
$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
$sthBcustrows=$sthB->rows;
$col_ct=0;
if ($DBX) {print "$sthBcustrows|$stmtB|\n";}
while ($sthBcustrows > $col_ct) 
	{
	@aryB = $sthB->fetchrow_array;
	$custom_table =		$aryB[0];
	$temp_table =		$aryB[0];
	$temp_table =~ s/custom_//gi;
	$temp_table =~ s/\D//gi;

	if (length($temp_table) > 0) 
		{
		$temp_list_found=0;
		$stmtA = "SELECT count(*) from vicidial_lists where list_id='$temp_table';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
			if ($DB) {print "$sthArows|$stmtA|\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$temp_list_found =					$aryA[0];
			}
		if ($temp_list_found > 0) 
			{
			if ($DBX) {print "CUSTOM TABLE FOUND: $aryB[0]|$temp_table|$temp_list_found \n";}
			$CUSTOM_TABLE_FOUND++;
			}
		else
			{
			if ($DBX) {print "CUSTOM TABLE NOT FOUND, checking for leads tied to this custom table: $aryB[0]|$temp_table|$temp_list_found \n";}

			$temp_leads_found=0;
			$stmtA = "SELECT count(*) from vicidial_list where entry_list_id='$temp_table';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
				if ($DB) {print "$sthArows|$stmtA|\n";}
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$temp_leads_found =					$aryA[0];
				}
			if ($temp_leads_found > 0) 
				{
				if ($DBX) {print "LEADS FOUND USING CUSTOM TABLE: $aryB[0]|$temp_table|$temp_leads_found \n";}
				$CUSTOM_TABLE_LEADS_FOUND++;
				}
			else
				{
				if ($DBX) {print "NO LEADS FOUND USING CUSTOM TABLE: $aryB[0]|$temp_table|$temp_leads_found \n";}

				$Caffected_rows=0;
				$stmtC = "DROP TABLE $custom_table;";
				if($DBX){print STDERR "\n|$stmtC|\n";}
				if (!$TEST) 
					{
					$Caffected_rows = $dbhC->do($stmtC);
					$TOTAL_DELETED++;
					$dropped_tables .= "$custom_table|";

					# remove field definitions
					if ($remove_field_defs > 0)
						{
						$stmtA="DELETE FROM vicidial_lists_fields where list_id='$temp_table';";
						$Iaffected_rows = $dbhA->do($stmtA);
						if ($DBX) {print " - field definitions removed debug: |$Iaffected_rows|$stmtA|\n";}
						}
					}
				if($DB){print STDERR "\n|$custom_table table dropped $Caffected_rows|\n";}
				}
			}
		}
	else
		{
		if ($DBX) {print "INVALID CUSTOM TABLE: $aryB[0]|$temp_table \n";}
		$INVALID_CUSTOM_TABLE++;
		}

	$col_ct++;
	}
$sthB->finish();


if ($TOTAL_DELETED > 0) 
	{
	$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='LISTS', event_type='OTHER', record_id='lists', event_code='CUSTOM TABLES DROPPED', event_sql=\"Dropped tables: $dropped_tables\", event_notes='tables scanned: $col_ct   tables dropped: $TOTAL_DELETED';";
	if (!$TEST) {$Iaffected_rows = $dbhA->do($stmtA);}
	if ($DBX) {print " - admin log insert debug: |$Iaffected_rows|$stmtA|\n";}
	}

if (!$Q) 
	{
	print "PROCESS COMPLETE: \n";
	print "   Total custom tables:        $col_ct \n";
	print "   Invalid custom tables:      $INVALID_CUSTOM_TABLE \n";
	print "   Custom tables found:        $CUSTOM_TABLE_FOUND \n";
	print "   Custom tables leads found:  $CUSTOM_TABLE_LEADS_FOUND \n";
	print "   Custom tables deleted:      $TOTAL_DELETED \n";
	}

exit;
