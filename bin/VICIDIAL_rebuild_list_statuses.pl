#!/usr/bin/perl
#
# VICIDIAL_rebuild_lead_statuses.pl version 2.12
#
# DESCRIPTION:
# resets the status in vicidial_list for leads marked in status NOUSE
# Very useful if you manually mess up the list statuses with a SQL query
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130422-0905 - first build
# 151209-1620 - Added user field update from logs, 
#               Added --no-cc-and-llct-update option to not update called_count, last_local_call_time
#               Added --use-lead-id option to search logs by lead_id instead of phone number
#

$secX = time();

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
$pulldate0 = "$year-$mon-$mday $hour:$min:$sec";
$inSD = $pulldate0;
$dsec = ( ( ($hour * 3600) + ($min * 60) ) + $sec );



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

if (!$VDHLOGfile) {$VDHLOGfile = "$PATHlogs/dupleads.$year-$mon-$mday";}

print "\n\n\n\n\n\n\n\n\n\n\n\n-- VICIDIAL_rebuild_list_statuses.pl --\n\n";
print "This program is designed to scan all leads marked NOUSE and set them to their proper status, called_count, last_local_call_time and user according to the logs. \n\n";


$oldlistidSQL='';
$newlistidSQL='';

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
		print "  [--debug] = debugging enabled\n";
		print "  [--debugX] = extra debugging enabled\n";
		print "  [--oldlistid=1234] = restrict log search to this list_id\n";
		print "  [--newlistid=1234] = restrict updates to this list_id\n";
		print "  [--no-cc-and-llct-update] = do not update called_count, last_local_call_time\n";
		print "  [--use-lead-id] = search the logs by the lead_id instead of phone number\n";
		print "  [--help] = this help screen\n\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /-debug/i)
			{
			$DB=1;
			print "\n-----DEBUGGING -----\n\n";
			}
		if ($args =~ /-debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER-DUPER DEBUGGING -----\n\n";
			}
		if ($args =~ /-quiet/i)
			{
			$q=1;
			}
		if ($args =~ /-test/i)
			{
			$T=1;
			$TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--no-cc-and-llct-update/i)
			{
			$no_cc_and_llct_update=1;
			if ($q < 1) {print "\n----- NO called_count AND last_local_call_time UPDATES -----\n\n";}
			}
		if ($args =~ /--use-lead-id/i)
			{
			$use_lead_id=1;
			if ($q < 1) {print "\n----- SEARCH LOGS BY LEAD ID -----\n\n";}
			}
		if ($args =~ /-oldlistid=/i)
			{
			@data_in = split(/-oldlistid=/,$args);
			$oldlistid = $data_in[1];
			$oldlistid =~ s/ .*//gi;
			if ($q < 1) {print "\n----- OLD list_id RESTRICTION: $oldlistid -----\n\n";}
			if (length($oldlistid)>1)
				{$oldlistidSQL = "and list_id='$oldlistid'";}
			}
		if ($args =~ /-newlistid=/i)
			{
			@data_in = split(/-newlistid=/,$args);
			$newlistid = $data_in[1];
			$newlistid =~ s/ .*//gi;
			if ($q < 1) {print "\n----- NEW list_id RESTRICTION: $newlistid -----\n\n";}
			if (length($newlistid)>1)
				{$newlistidSQL = "and list_id='$newlistid'";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	$args = "";
	$i=0;
	$campdup = '';
	$liveupdate=0;
	$duplicatelist = '998';
	}
### end parsing run-time options ###

$US = '_';
$phone_list = '|';
$MT[0]='';


# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$DB=1;

$liveupdate=0;


$stmtA = "select lead_id,phone_number,alt_phone from vicidial_list where status='NOUSE' $newlistidSQL;";
if($DBX){print STDERR "\n|$stmtA|\n";}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$i=0;
$nonDUP='0';
while ( ($sthArows > $i) && ($nonDUP=='0') )
	{
	@aryA = $sthA->fetchrow_array;
	if ($aryA[0] > 1)
		{
		$lead_id[$i] =			$aryA[0];
		$phone_number[$i] =		$aryA[1];
		$alt_phone[$i] =		$aryA[2];
		}
	$i++;
	}
$sthA->finish();

$found_count=0;
$not_found_count=0;
$b=0;
foreach(@lead_id)
	{
	$Nstatus='';
	$Nepoch=0;
	$Nlead_id='';
	$Ncall_date='';
	$Nuser='';
	$Cstatus='';
	$Cepoch=0;
	$Clead_id='';
	$Ccall_date='';
	$Cuser='';
	$NEWstatus='';
	$OLDlead_id='';
	$NEWcall_date='';
	$NEWuser='';
	$LEADfound=0;

	if ($use_lead_id > 0) 
		{$searchSQL = "lead_id='$lead_id[$b]'";}
	else
		{$searchSQL = "phone_number='$phone_number[$b]'";}

	$stmtA = "SELECT status,start_epoch,lead_id,call_date,user from vicidial_log where $searchSQL $oldlistidSQL order by call_date desc LIMIT 1;";
		if($DBX){print STDERR "\n|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$Nstatus =		$aryA[0];
		$Nepoch =		$aryA[1];
		$Nlead_id =		$aryA[2];
		$Ncall_date =	$aryA[3];
		$Nuser =		$aryA[4];
		$LEADfound++;
		}
	$sthA->finish();

	$stmtA = "SELECT status,start_epoch,lead_id,call_date,user from vicidial_closer_log where $searchSQL $oldlistidSQL order by closecallid desc LIMIT 1;";
		if($DBX){print STDERR "\n|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$Cstatus =		$aryA[0];
		$Cepoch =		$aryA[1];
		$Clead_id =		$aryA[2];
		$Ccall_date =	$aryA[3];
		$Cuser =		$aryA[4];
		$LEADfound++;
		}
	$sthA->finish();

	if ($LEADfound < 1)
		{
		$stmtA = "SELECT status,start_epoch,lead_id,call_date,user from vicidial_log where $searchSQL $oldlistidSQL order by call_date desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$Nstatus =		$aryA[0];
			$Nepoch =		$aryA[1];
			$Nlead_id =		$aryA[2];
			$Ncall_date =	$aryA[3];
			$Nuser =		$aryA[4];
			$LEADfound++;
			}
		$sthA->finish();
		}

	if ($LEADfound > 0)
		{
		if ($Cepoch > $Nepoch) 
			{
			$NEWstatus =	$Cstatus;
			$OLDlead_id =	$Clead_id;
			$NEWcall_date =	$Ccall_date;
			$NEWuser =		$Cuser;
			}
		else 
			{
			$NEWstatus =	$Nstatus;
			$OLDlead_id =	$Nlead_id;
			$NEWcall_date =	$Ncall_date;
			$NEWuser =		$Nuser;
			}

		$stmtA = "SELECT count(*) from vicidial_log where lead_id='$OLDlead_id' $oldlistidSQL;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$NEWcount =		$aryA[0];
			}
		$sthA->finish();

		if ($no_cc_and_llct_update > 0) 
			{$cc_and_llct_updateSQL = '';}
		else
			{$cc_and_llct_updateSQL = ",called_count='$NEWcount',last_local_call_time='$NEWcall_date'";}

		$stmtA = "UPDATE vicidial_list set status='$NEWstatus',user='$NEWuser' $cc_and_llct_updateSQL where lead_id='$lead_id[$b]';";
		if ($TEST < 1)
			{$affected_rows = $dbhA->do($stmtA);} #  or die  "Couldn't execute query:|$stmtA|\n";
		if($DB){print STDERR "|$b|$lead_id[$b]|$Nstatus|$Cstatus|$phone_number[$b]|   |$affected_rows|$stmtA|\n";}
		$found_count++;
		}
	else
		{
		$not_found_count++;
		if($DB){print STDERR "NOT FOUND: |$b|$lead_id[$b]|$phone_number[$b]|\n";}
		}
	$b++;

	if ($b =~ /10$/i) {print STDERR "  *     $b\r";}
	if ($b =~ /20$/i) {print STDERR "   *    $b\r";}
	if ($b =~ /30$/i) {print STDERR "    *   $b\r";}
	if ($b =~ /40$/i) {print STDERR "     *  $b\r";}
	if ($b =~ /50$/i) {print STDERR "      * $b\r";}
	if ($b =~ /60$/i) {print STDERR "     *  $b\r";}
	if ($b =~ /70$/i) {print STDERR "    *   $b\r";}
	if ($b =~ /80$/i) {print STDERR "   *    $b\r";}
	if ($b =~ /90$/i) {print STDERR "  *     $b\r";}
	if ($b =~ /00$/i) {print STDERR " *      $b\r";}
	if ($b =~ /00$/i) {print "        |$b|$found_count|$not_found_count|    |$lead_id[$b]|$OLDlead_id|$Nstatus|$Cstatus|$list_id[$b]|\n";}
	}

$dbhA->disconnect();


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

print "DONE:      |$b|$found_count|$not_found_count|\n";
print "script execution time in seconds: $secZ     minutes: $secZm\n";

exit;

