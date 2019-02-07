#!/usr/bin/perl
#
# AST_list_export.pl                version: 2.12
#
# This script is designed to gather list data and custom field data and dump 
# into a tab-delimited text file
#
# /usr/share/astguiclient/AST_list_export.pl --list=101-102-103 --output-format=fixed-as400 --debug --filename=101exportMMDD.txt --email-list=test@gmail.com --email-sender=test@test.com
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 151020-2043 - First version based upon AST_recording_export.pl
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$uniqueidLIST='|';
$ftp_audio_transfer=1;
$NODATEDIR = 0;	# Don't use dated directories for audio
$YEARDIR = 1;	# put dated directories in a year directory first

# Default FTP account variables
$VARREPORT_host = '10.0.0.4';
$VARREPORT_user = 'cron';
$VARREPORT_pass = 'test';
$VARREPORT_port = '21';
$VARREPORT_dir  = 'REPORTS';

# default CLI values
$campaign = 'TESTCAMP';
$sale_statuses = 'SALE-UPSELL';
$outbound_calltime_ignore=0;
$totals_only=0;
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
	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
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
		print "  [--list=XXX] = Campaign that sales will be pulled from. REQUIRED\n";
		print "  [--temp-dir=XXX] = If running more than one instance at a time, specify a unique temp directory suffix\n";
		print "  [--quiet] = quiet\n";
		print "  [--test] = test\n";
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
		if ($args =~ /--list=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--list=/,$args);
			$list = $data_in[1];
			$list =~ s/ .*$//gi;
			$listSQL = $list;
			if ($listSQL =~ /-/) 
				{
				$listSQL =~ s/-/','/gi;
				}
			$listSQL = "'$listSQL'";
			}
		else
			{
			print "no list defined, exiting.\n";
			exit;
			}

		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		}
	}
else
	{
	print "no command line options set, exiting.\n";
	exit;
	}
### end parsing run-time options ###



if (length($listSQL) > 1)
	{$listSQL = "list_id IN($listSQL)";}
else
	{
	print "no list defined, exiting...";
	exit;
	}

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- AST_list_export.pl --\n\n";
	print "This program is designed to gather list records including custom fields and export them to a data file. \n";
	print "\n";
	print "List:      $list    $listSQL\n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$TOTAL_LEADS=0;
$TOTAL_CUSTOM=0;


###########################################################################
########### CURRENT DAY SALES GATHERING outbound-only: vicidial_log  ######
###########################################################################
$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from vicidial_list where $listSQL;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
	if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$lead_id =					$aryA[0];
	$entry_date =				$aryA[1];
	$modify_date =				$aryA[2];
	$status =					$aryA[3];
	$user =						$aryA[4];
	$vendor_lead_code =			$aryA[5];
	$source_id =				$aryA[6];
	$list_id =					$aryA[7];
	$gmt_offset_now =			$aryA[8];
	$called_since_last_reset =	$aryA[9];
	$phone_code =				$aryA[10];
	$phone_number =				$aryA[11];
	$title =					$aryA[12];
	$first_name =				$aryA[13];
	$middle_initial =			$aryA[14];
	$last_name =				$aryA[15];
	$address1 =					$aryA[16];
	$address2 =					$aryA[17];
	$address3 =					$aryA[18];
	$city =						$aryA[19];
	$state =					$aryA[20];
	$province =					$aryA[21];
	$postal_code =				$aryA[22];
	$country_code =				$aryA[23];
	$gender =					$aryA[24];
	$date_of_birth =			$aryA[25];
	$alt_phone =				$aryA[26];
	$email =					$aryA[27];
	$security_phrase =			$aryA[28];
	$comments =					$aryA[29];
	$called_count =				$aryA[30];
	$last_local_call_time =		$aryA[31];
	$rank =						$aryA[32];
	$owner =					$aryA[33];
	$entry_list_id =			$aryA[34];

	&select_format_loop;

	$TOTAL_LEADS++;
	}
$sthA->finish();


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) {print "SALES EXPORT FOR $list   ($PATHhome/$file_date$US$list.txt)\n";}
if (!$Q) {print "TOTAL LEADS: $TOTAL_LEADS\n";}
if (!$Q) {print "TOTAL CUSTOM: $TOTAL_CUSTOM\n";}
if (!$Q) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}




### Subroutine for formatting of the output ###
sub select_format_loop
	{
	$str='';
	$call_data = "$lead_id\t$entry_date\t$modify_date\t$status\t$user\t$vendor_lead_code\t$source_id\t$list_id\t$gmt_offset_now\t$called_since_last_reset\t$phone_code\t$phone_number\t$title\t$first_name\t$middle_initial\t$last_name\t$address1\t$address2\t$address3\t$city\t$state\t$province\t$postal_code\t$country_code\t$gender\t$date_of_birth\t$alt_phone\t$email\t$security_phrase\t$comments\t$called_count\t$last_local_call_time\t$rank\t$owner\t$entry_list_id";

	##### BEGIN check for custom fields data #####
	$custom_data = '';
	$custom_columns = '';

	if ( (length($entry_list_id) > 1 ) && ($entry_list_id >= 99) ) 
		{
		$stmtB = "SHOW TABLES LIKE \"custom_$entry_list_id\";";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBcustrows=$sthB->rows;
		if ($DBX) {print "$sthBcustrows|$stmtB|\n";}
		$sthB->finish();
		if ($sthBcustrows > 0) 
			{
			$stmtB = "describe custom_$entry_list_id;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBcolrows=$sthB->rows;
			if ($DBX) {print "$sthBcolrows|$stmtB|\n";}
			$col_ct=0;
			while ($sthBcolrows > $col_ct) 
				{
				@aryB = $sthB->fetchrow_array;
				$column_label =		$aryB[0];
				if ($col_ct > 0)
					{$custom_columns .= "$column_label,";}
				$col_ct++;
				}
			$sthB->finish();
			$custom_columns =~ s/,$//gi;

			if ($col_ct > 0)
				{
				$stmtB = "SELECT $custom_columns from custom_$entry_list_id where lead_id=$lead_id;";
				$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
				$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
				$sthBrows=$sthB->rows;
				if ($DBX) {print "$sthBrows|$stmtB|\n";}
				$rec_countB=0;
				while ($sthBrows > $rec_countB)
					{
					@aryB = $sthB->fetchrow_array;
					$field_ct=1;
					while ($col_ct > $field_ct) 
						{
						$custom_data .= "\t$aryB[$field_ct]";
						$field_ct++;
						}
					$rec_countB++;

					if (length($custom_data) > 0)
						{
						if ($DBX) {print "Custom data for $lead_id found in $entry_list_id: |$custom_data|\n";}
						$call_data .= "$custom_data";
						$TOTAL_CUSTOM++;
						}
					}
				$sthB->finish();
				}
			}
		}
	##### END custom field data lookup #####

	$Ealert .= "$TOTAL_LEADS   $TOTAL_CUSTOM   $rec_countB   $call_data\n"; 

	if ($DBX) {print "$TOTAL_LEADS   $TOTAL_CUSTOM   $rec_countB   $call_data\n";}

	open(Sout, ">>$PATHhome/$file_date$US$list.txt")
			|| die "Can't open $file_date$US$list.txt: $!\n";
	print Sout "$call_data\n";
	close(Sout);


	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /200$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /300$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /400$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /500$/i) {print STDERR " GATHE* $rec_count\r";}
		if ($rec_count =~ /600$/i) {print STDERR " GATH*R $rec_count\r";}
		if ($rec_count =~ /700$/i) {print STDERR " GAT*ER $rec_count\r";}
		if ($rec_count =~ /800$/i) {print STDERR " GA*HER $rec_count\r";}
		if ($rec_count =~ /900$/i) {print STDERR " G*THER $rec_count\r";}
		if ($rec_count =~ /000$/i) {print STDERR " *ATHER $rec_count\r";}
		if ($rec_count =~ /000$/i) {print "        |$rec_count|$TOTAL_LEADS|$TOTAL_CUSTOM|         |$lead_id|\n";}
		}
	$rec_count++;
	}

exit;

#	if ($DB > 0)
#		{
#		if ($rec_count =~ /10$/i) {print STDERR "  *     $rec_count\r";}
#		if ($rec_count =~ /20$/i) {print STDERR "   *    $rec_count\r";}
#		if ($rec_count =~ /30$/i) {print STDERR "    *   $rec_count\r";}
#		if ($rec_count =~ /40$/i) {print STDERR "     *  $rec_count\r";}
#		if ($rec_count =~ /50$/i) {print STDERR "      * $rec_count\r";}
#		if ($rec_count =~ /60$/i) {print STDERR "     *  $rec_count\r";}
#		if ($rec_count =~ /70$/i) {print STDERR "    *   $rec_count\r";}
#		if ($rec_count =~ /80$/i) {print STDERR "   *    $rec_count\r";}
#		if ($rec_count =~ /90$/i) {print STDERR "  *     $rec_count\r";}
#		if ($rec_count =~ /00$/i) {print STDERR " *      $rec_count\r";}
#		if ($rec_count =~ /00$/i) {print "        |$rec_count|$TOTAL_SALES|$CALLTIME_KICK|$TOTAL_RECORDINGS|         |$lead_id|\n";}
#		}
