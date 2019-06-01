#!/usr/bin/perl
#
# AST_flush_DBqueue.pl    version 2.14
#
# DESCRIPTION:
# - clears out mysql records for this server for the ACQS vicidial_manager table
# - OPTIMIZEs tables used frequently by VICIDIAL
#
# !!!!!!!! IMPORTANT !!!!!!!!!!!!!!!!!!
# THIS SCRIPT SHOULD ONLY BE RUN ON ONE SERVER ON YOUR CLUSTER
#
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 60717-1214 - changed to DBI by Marin Blu
# 60717-1536 - changed to use /etc/astguiclient.conf for configs
# 60910-0238 - removed park_log query
# 90628-2358 - Added vicidial_drop_rate_groups optimization
# 91206-2149 - Added vicidial_campaigns and vicidial_lists optimization
# 120404-1315 - Changed to run only on DB server<you should remove from other servers' crontabs>
# 120411-1637 - Added --seconds option
# 150710-0907 - Added flush of vicidial_dtmf_log table
# 160101-1124 - Added flush of routing_initiated_recordings table
# 161214-1039 - Added flush of parked_channels_recent table
# 180112-0915 - Added flush for cid_channels_recent table
# 180511-1223 - Added flush for server-specific cid_channels_recent_ tables
# 180519-1431 - Added vicidial_inbound_groups optimization
# 181003-2115 - Added optimize of cid_channels_recent_ tables
# 190214-1758 - Fix for cid_recent_ table optimization issue
# 190222-1321 - Added optional flushing of vicidial_sessions_recent table
# 190503-1606 - Added flushing of vicidial_sip_event_recent table
#

$session_flush=0;
$SSsip_event_logging=0;

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
		print "  [-q] = quiet\n";
		print "  [-t] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--seconds=XXX] = optional, minimum number of seconds worth of records to keep(default 3600)\n";
		print "  [--session-flush] = flush the vicidial_sessions_recent table\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /-q/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n-----DEBUGGING -----\n\n";
			}
		if ($args =~ /-t|--test/i)
			{
			$T=1; $TEST=1;
			print "\n-----TESTING -----\n\n";
			}
		if ($args =~ /--session-flush/i)
			{
			$session_flush=1;
			if ($Q < 1)
				{print "\n----- SESSION FLUSH(vicidial_sessions_recent) ----- $session_flush \n\n";}
			}
		if ($args =~ /--seconds=/i)
			{
			@data_in = split(/--seconds=/,$args);
			$purge_seconds = $data_in[1];
			$purge_seconds =~ s/ .*$//gi;
			$purge_seconds =~ s/\D//gi;
			$purge_seconds_half = $purge_seconds;
			if ($Q < 1)
				{print "\n----- SECONDS OVERRIDE: $purge_seconds -----\n\n";}
			if (length($purge_seconds) < 3)
				{
				print "ERROR! Invalid Seconds: $purge_seconds     Exiting...\n";
				exit;
				}
			}
		else
			{
			$purge_seconds = '3600';
			$purge_seconds_half = '1800';
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NOW="$year-$mon-$mday $hour:$min:$sec";

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time() - 121);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NEG_2min="$year-$mon-$mday $hour:$min:$sec";

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time() - ($purge_seconds * 2));
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NEG_2hour="$year-$mon-$mday $hour:$min:$sec";

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time() - $purge_seconds);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NEG_1hour="$year-$mon-$mday $hour:$min:$sec";

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time() - $purge_seconds_half);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NEG_halfhour="$year-$mon-$mday $hour:$min:$sec";


if (!$Q) {print "TEST\n\n";}
if (!$Q) {print "NOW DATETIME:         $SQLdate_NOW\n";}
if (!$Q) {print "1 HOUR AGO DATETIME:  $SQLdate_NEG_1hour\n\n";}

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

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtA = "SELECT vd_server_logs FROM servers where server_ip = '$VARserver_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DBvd_server_logs =			$aryA[0];
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
	else {$SYSLOG = '0';}
	}
$sthA->finish();

### Grab system_settings values from the database
$stmtA = "SELECT sip_event_logging FROM system_settings limit 1;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSsip_event_logging =			$aryA[0];
	}
$sthA->finish();

if ($SYSLOG) 
	{$flush_time = $SQLdate_NEG_1hour;}
else
	{$flush_time = $SQLdate_NEG_halfhour;}

$stmtA = "DELETE from vicidial_manager where entry_date < '$flush_time';";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) {	$affected_rows = $dbhA->do($stmtA);}
if (!$Q) {print " - vicidial_manager flush: $affected_rows rows\n";}

$stmtA = "DELETE from vicidial_dtmf_log where dtmf_time < '$flush_time';";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
if (!$Q) {print " - vicidial_dtmf_log flush: $affected_rows rows\n";}

$stmtA = "DELETE from routing_initiated_recordings where launch_time < '$flush_time';";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
if (!$Q) {print " - routing_initiated_recordings flush: $affected_rows rows\n";}

$stmtA = "DELETE from parked_channels_recent where park_end_time < '$flush_time';";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
if (!$Q) {print " - parked_channels_recent flush: $affected_rows rows\n";}

$stmtA = "DELETE from cid_channels_recent where call_date < '$SQLdate_NEG_2min';";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
if (!$Q) {print " - cid_channels_recent flush: $affected_rows rows\n";}

if ($SSsip_event_logging > 0)
	{
	$stmtA = "DELETE from vicidial_sip_event_recent where invite_date < '$SQLdate_NEG_2hour';";
	if($DB){print STDERR "\n|$stmtA|\n";}
	if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
	if (!$Q) {print " - vicidial_sip_event_recent flush: $affected_rows rows\n";}
	}

$stmtA = "OPTIMIZE table vicidial_manager;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_manager          \n";}


$stmtA = "OPTIMIZE table vicidial_dtmf_log;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T)
        {
        $sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
        $sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
        $sthArows=$sthA->rows;
        @aryA = $sthA->fetchrow_array;
        if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
        $sthA->finish();
        }
if (!$Q) {print " - OPTIMIZE vicidial_dtmf_log          \n";}


$stmtA = "OPTIMIZE table routing_initiated_recordings;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T)
        {
        $sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
        $sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
        $sthArows=$sthA->rows;
        @aryA = $sthA->fetchrow_array;
        if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
        $sthA->finish();
        }
if (!$Q) {print " - OPTIMIZE routing_initiated_recordings          \n";}


$stmtA = "OPTIMIZE table parked_channels_recent;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T)
        {
        $sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
        $sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
        $sthArows=$sthA->rows;
        @aryA = $sthA->fetchrow_array;
        if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
        $sthA->finish();
        }
if (!$Q) {print " - OPTIMIZE parked_channels_recent          \n";}


$stmtA = "OPTIMIZE table cid_channels_recent;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T)
        {
        $sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
        $sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
        $sthArows=$sthA->rows;
        @aryA = $sthA->fetchrow_array;
        if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
        $sthA->finish();
        }
if (!$Q) {print " - OPTIMIZE cid_channels_recent          \n";}

if ($SSsip_event_logging > 0)
	{
	$stmtA = "OPTIMIZE table vicidial_sip_event_recent;";
	if($DB){print STDERR "\n|$stmtA|\n";}
	if (!$T)
        {
        $sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
        $sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
        $sthArows=$sthA->rows;
        @aryA = $sthA->fetchrow_array;
        if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
        $sthA->finish();
        }
	if (!$Q) {print " - OPTIMIZE vicidial_sip_event_recent          \n";}
	}


$stmtA = "OPTIMIZE table vicidial_live_agents;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}             
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_live_agents          \n";}


$stmtA = "OPTIMIZE table vicidial_drop_rate_groups;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_drop_rate_groups          \n";}


$stmtA = "OPTIMIZE table vicidial_campaigns;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_campaigns          \n";}


$stmtA = "OPTIMIZE table vicidial_lists;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_lists          \n";}


$stmtA = "OPTIMIZE table vicidial_inbound_groups;";
if($DB){print STDERR "\n|$stmtA|\n";}
if (!$T) 
	{
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	$sthA->finish();
	}
if (!$Q) {print " - OPTIMIZE vicidial_inbound_groups          \n";}


### Gather active servers from the database
$stmtA = "SELECT server_ip,server_id FROM servers where active='Y' and active_asterisk_server='Y';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsSERVERS=$sthA->rows;
$aas=0;
while ($sthArowsSERVERS > $aas)
	{
	@aryA = $sthA->fetchrow_array;
	$dialer_ip[$aas] =			$aryA[0];
	$dialer_id[$aas] =			$aryA[1];
	$PADserver_ip[$aas] =		$aryA[0];
	$PADserver_ip[$aas] =~ s/(\d+)(\.|$)/sprintf "%3.3d$2",$1/eg; 
	$PADserver_ip[$aas] =~ s/\.//eg; 
	$aas++;
	}
$sthA->finish();

$aas=0;
while ($sthArowsSERVERS > $aas)
	{
	$CCRrec=0;
	$stmtA = "SHOW TABLES LIKE \"cid_channels_recent_$PADserver_ip[$aas]\";";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$CCRrec=$sthA->rows;
	if($DB){print STDERR "\n|$CCRrec|$stmtA|\n";}

	if ($CCRrec > 0)
		{
		$stmtA = "DELETE from cid_channels_recent_$PADserver_ip[$aas] where call_date < '$SQLdate_NEG_2min';";
		if($DB){print STDERR "\n|$stmtA|\n";}
		if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
		if (!$Q) {print " - cid_channels_recent_$PADserver_ip[$aas] flush: $affected_rows rows\n";}

		$stmtA = "OPTIMIZE table cid_channels_recent_$PADserver_ip[$aas];";
		if($DB){print STDERR "\n|$stmtA|\n";}
		if (!$T) 
			{
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			@aryA = $sthA->fetchrow_array;
			if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
			$sthA->finish();
			}
		if (!$Q) {print " - OPTIMIZE cid_channels_recent_$PADserver_ip[$aas]          \n";}
		}
	$aas++;
	}
$sthA->finish();


if ($session_flush > 0) 
	{
	$stmtA = "DELETE from vicidial_sessions_recent where call_date < '$SQLdate_NEG_1hour';";
	if($DB){print STDERR "\n|$stmtA|\n";}
	if (!$T) {      $affected_rows = $dbhA->do($stmtA);}
	if (!$Q) {print " - vicidial_sessions_recent flush: $affected_rows rows\n";}

	$stmtA = "OPTIMIZE table vicidial_sessions_recent;";
	if($DB){print STDERR "\n|$stmtA|\n";}
	if (!$T) 
		{
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		@aryA = $sthA->fetchrow_array;
		if (!$Q) {print "|",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
		$sthA->finish();
		}
	if (!$Q) {print " - OPTIMIZE vicidial_sessions_recent          \n";}
	}
$dbhA->disconnect();


exit;
