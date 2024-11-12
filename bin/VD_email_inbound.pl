#!/usr/bin/perl
# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

# Copyright (C) 2024  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# VD_email_inbound.pl
# This script is responsible for assigning transferred and new emails to 
# available agents.  This is done by repeatedly checking for transferred emails
# waiting to be assigned sorted by email group rank, then all new emails
# waiting to be assigned, also sorted by email group rank.  Then it checks
# the vicidial_live_agents table for agents with READY or CLOSER status.   
#
# As it cycles through the prioritized emails to assign, it takes the inbound group
# each email belongs to, selects the agent it should be assigned to based on the
# next_agent_call setting, and puts that lead in QUEUE, as well as putting the 
# chosen agent in a special MQUEUE status.  Programming in other parts of Vicidial
# will cause these emails to be displayed in the agent's interface.
#
# This script should be set up on ONLY ONE SERVER. You set it up using the "E"
# keepalive option in the /etc/astguiclient.conf file on one of the servers in
# your cluster.
# 
# changes:
# 121214-2303 - First Build
# 180610-1812 - Added code to not allow XFER emails to go to user who transferred
# 190216-0810 - Fix for user-group, email-group and campaign allowed/permissions matching issues
# 191017-2043 - Added filtered routing options
# 240219-1526 - Added daily_limit in-group parameter
#

if ($ARGV[0]=~/--help/) 
	{
	print "VD_email_inbound.pl\n";
	print "\n";
	print "This script is responsible for assigning transferred and new emails to\n";
	print "available agents.  This is done by repeatedly checking for transferred emails\n";
	print "waiting to be assigned sorted by email group rank, then all new emails\n";
	print "waiting to be assigned, also sorted by email group rank.  Then it checks\n";
	print "the vicidial_live_agents table for agents with READY or CLOSER status.\n";
	print "\n";
	print "As it cycles through the prioritized emails to assign, it takes the inbound\n";
	print "group each email belongs to, selects the agent it should be assigned to based\n";
	print "on the next_agent_call setting, and puts that lead in QUEUE, as well as putting\n";
	print "the chosen agent in a special MQUEUE status.  Programming in other parts of \n";
	print "Vicidial will cause these emails to be displayed in the agent's interface.\n";
	print "\n";
	print "This script should be set up on ONLY ONE SERVER. You set it up using the \"E\"\n";
	print "keepalive option in the /etc/astguiclient.conf file on one of the servers in\n";
	print "your cluster.\n";
	print "\n";
	print "Use the --debug variable when executing the script to have it print out the SQL\n";
	print "statements it is attempting to execute.\n";
	exit;
	}

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

$|=1;

if (!$VARDB_port) {$VARDB_port='3306';}
if (!$AGILOGfile) {$AGILOGfile = "$PATHlogs/agiout.$year-$mon-$mday";}
if (!$PRSLOGfile) {$PRSLOGfile = "$PATHlogs/prsout.$year-$mon-$mday";}
if (!$PRSTESTfile) {$PRSTESTfile = "$PATHlogs/prstest.$year-$mon-$mday";}
if (!$ERRLOGfile) {$ERRLOGfile = "$PATHlogs/MySQLerror.$year-$mon-$mday";}

use DBI;
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use Asterisk::AGI;
$AGI = new Asterisk::AGI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}

$hm = "$hour$min";
$hm = ($hm + 0);

$now_date_epoch = time();
$start_epoch = $now_date_epoch;
$now_date = "$year-$mon-$mday $hour:$min:$sec";
$CLInow_date = "$year-$mon-$mday\\ $hour:$min:$sec";
$YMD = "$year-$mon-$mday";
$start_time=$now_date;
$CIDdate = "$mon$mday$hour$min$sec";
$tsSQLdate = "$year$mon$mday$hour$min$sec";
$SQLdate = "$year-$mon-$mday $hour:$min:$sec";
$SQLdateBEGIN = $SQLdate;
$ORIGINAL_call_time = $SQLdate;
	while (length($CIDdate) > 9) {$CIDdate =~ s/^.//gi;} # 0902235959 changed to 902235959


$BDtarget = ($now_date_epoch - 5);
($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
$Byear = ($Byear + 1900);
$Bmon++;
if ($Bmon < 10) {$Bmon = "0$Bmon";}
if ($Bmday < 10) {$Bmday = "0$Bmday";}
if ($Bhour < 10) {$Bhour = "0$Bhour";}
if ($Bmin < 10) {$Bmin = "0$Bmin";}
if ($Bsec < 10) {$Bsec = "0$Bsec";}
$BDtsSQLdate = "$Byear$Bmon$Bmday$Bhour$Bmin$Bsec";

#$ADUfindSQL - for AGENTDIRECT, not necessary
#$INBOUNDcampsSQL*
#$BDtsSQLdate*
#$ring_no_answer_agents - not necessary?
#$vlia_users - queue_priority, already handled?
#$qp_groupWAIT_camp_SQL - queue_priority, already handled?
#$qp_groupWAIT_SQL - queue_priority, already handled?
#$vig_stmt="select campaign_id, queue_priority, next_agent_call from vicidial_inbound_groups where group_handling='EMAIL' and active='Y' order by queue_priority desc";
#$vig_rslt=$dbhA->prepare($vig_stmt);
#$vig_rslt->execute();


for ($i=0; $i<=1000000; $i++) 
	{
	$vci=0;
	$INBOUNDcampsSQL='';
	$stmtA = "SELECT campaign_id FROM vicidial_campaigns where active='Y' and campaign_allow_inbound='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$dbhP=$dbhA;   $mysql_count='02033';   $MEL_aff_rows=$sthArows;   &mysql_error_logging;
	while ($sthArows > $vci)
		{
		@aryA = $sthA->fetchrow_array;
		if ($vci < 1) {$INBOUNDcampsSQL	= "'$aryA[0]'";}
		else {$INBOUNDcampsSQL	.= ",'$aryA[0]'";}
		$vci++;
		}
	$sthA->finish();

	# CHECK IF THERE ARE ANY EMAIL GROUPS ACTIVE TO SCAN
	$order_stmt="select group_id, next_agent_call from vicidial_inbound_groups where group_handling='EMAIL' and active='Y'";
	$order_rslt=$dbhA->prepare($order_stmt);
	$order_rslt->execute or die "executing: $order_stmt ", $dbhA->errstr;

	if ($order_rslt->rows>0) 
		{
		%group_orders=();
		while (@order_row=$order_rslt->fetchrow_array) 
			{
			$group_orders{"$order_row[0]"}=$order_row[1];
			}

		$email_ct=0;
		## CHECK IF THERE ARE ANY TRANSFERS TO ASSIGN
		$xfer_stmt="select email_row_id, vicidial_xfer_log.lead_id, vicidial_xfer_log.campaign_id, vicidial_inbound_groups.next_agent_call, vicidial_xfer_log.user from vicidial_email_list, vicidial_inbound_groups, vicidial_xfer_log where vicidial_inbound_groups.group_handling='EMAIL' and vicidial_inbound_groups.active='Y' and vicidial_inbound_groups.group_id=vicidial_xfer_log.campaign_id and vicidial_xfer_log.closer='EMAIL_XFER' and vicidial_xfer_log.lead_id=vicidial_email_list.lead_id and vicidial_xfer_log.xfercallid=vicidial_email_list.xfercallid order by queue_priority desc, email_date asc";
		if ($ARGV[0]=~/debug/i) {print $xfer_stmt."\n";}
		$xfer_rslt=$dbhB->prepare($xfer_stmt);
		$xfer_rslt->execute or die "executing: $xfer_stmt ", $dbhB->errstr;
		$xfers_to_assign=$xfer_rslt->rows;

		if ($xfers_to_assign>0) 
			{
			while (@xfer_row=$xfer_rslt->fetchrow_array) 
				{
				$row_id_ary[$email_ct]=$xfer_row[0];
				$lead_id_ary[$email_ct]=$xfer_row[1];
				$group_id_ary[$email_ct]=$xfer_row[2];
				$user_id_ary[$email_ct]=$xfer_row[3];
				$email_type_ary[$email_ct]="XFER";

				$email_ct++;
				}
			}
		$xfer_rslt->finish();

		## CHECK IF THERE ARE ANY REGULAR EMAILS TO ASSIGN
		$inb_stmt="select email_row_id, lead_id, vicidial_email_list.group_id, vicidial_inbound_groups.next_agent_call, vicidial_email_list.user from vicidial_email_list, vicidial_inbound_groups where group_handling='EMAIL' and active='Y' and vicidial_inbound_groups.group_id=vicidial_email_list.group_id and vicidial_email_list.status='NEW' order by queue_priority desc, email_date asc";
		if ($ARGV[0]=~/debug/i) {print $inb_stmt."\n";}
		$inb_rslt=$dbhB->prepare($inb_stmt);
		$inb_rslt->execute or die "executing: $inb_stmt ", $dbhB->errstr;
		$emails_to_assign=$inb_rslt->rows;
		if ($emails_to_assign>0) 
			{
			while (@inb_row=$inb_rslt->fetchrow_array) 
				{
				$row_id_ary[$email_ct]=$inb_row[0];
				$lead_id_ary[$email_ct]=$inb_row[1];
				$group_id_ary[$email_ct]=$inb_row[2];
				$user_id_ary[$email_ct]=$xfer_row[3];
				$email_type_ary[$email_ct]="INCOMING";

				$email_ct++;
				}
			}
		$inb_rslt->finish();

		if ($email_ct>0) {AssignAgents();} else {if ($ARGV[0]=~/debug/i) {print "**** NO EMAILS TO QUEUE ****\n";}}
		}
	usleep(100000);
	}


sub AssignAgents() 
	{
	if ($ARGV[0]=~/debug/i) {print $xfers_to_assign." transfers to assign\n".$emails_to_assign." incoming emails to assign...\n";}

	for ($q=0; $q<$email_ct; $q++) 
		{
		$CAMP_callorder=$group_orders{"$group_id_ary[$q]"};
		$email_row_id=$row_id_ary[$q];
		$lead_id=$lead_id_ary[$q];
		$email_type=$email_type_ary[$q];
		$group_id=$group_id_ary[$q];
		$user_id=$user_id_ary[$q];

		$ADUfindSQL='';
		$user='';
		$ring_no_answer_agents = "''";
		$aco_sub=0;
		$agent_call_order='order by last_call_finish';
		if ($CAMP_callorder =~ /longest_wait_time/i)	{$agent_call_order = 'order by vicidial_live_agents.last_state_change';}
		if ($CAMP_callorder =~ /overall_user_level/i)	{$agent_call_order = 'order by user_level desc,last_call_finish';}
		if ($CAMP_callorder =~ /oldest_call_start/i)	{$agent_call_order = 'order by vicidial_live_agents.last_call_time';} #
		if ($CAMP_callorder =~ /oldest_call_finish/i)	{$agent_call_order = 'order by vicidial_live_agents.last_call_finish';}
		if ($CAMP_callorder =~ /oldest_inbound_call_start/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_time';} #
		if ($CAMP_callorder =~ /oldest_inbound_call_finish/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_finish';} #
		if ($CAMP_callorder =~ /oldest_inbound_filtered_call_start/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_time_filtered';}
		if ($CAMP_callorder =~ /oldest_inbound_filtered_call_finish/i)	{$agent_call_order = 'order by vicidial_live_agents.last_inbound_call_finish_filtered';}
		if ($CAMP_callorder =~ /random/i)				{$agent_call_order = 'order by random_id';}
		if ($CAMP_callorder =~ /campaign_rank/i)		{$agent_call_order = 'order by campaign_weight desc,last_call_finish';}
		if ($CAMP_callorder =~ /fewest_calls_campaign/i) {$agent_call_order = 'order by vicidial_live_agents.calls_today,vicidial_live_agents.last_call_finish';}
		if ($CAMP_callorder =~ /ingroup_grade_random/i)	{$aco_sub=1;	$agent_call_order = 'order by random_id';}
		if ($CAMP_callorder =~ /campaign_grade_random/i) {$aco_sub=1;	$agent_call_order = 'order by random_id';}
		if ($CAMP_callorder =~ /inbound_group_rank/i)	{$aco_sub=1;	$agent_call_order = 'order by group_weight desc,vicidial_live_inbound_agents.last_call_finish';}
		if ($CAMP_callorder =~ /fewest_calls$/i)		{$aco_sub=1;	$agent_call_order = 'order by vicidial_live_inbound_agents.calls_today,vicidial_live_inbound_agents.last_call_finish';}

		if ($aco_sub > 0)
			{
			$stmtA = "LOCK TABLES vicidial_live_agents WRITE, vicidial_live_inbound_agents WRITE;";
			my $LOCKaffected_rows = $dbhA->do($stmtA);

			$stmtA = "SELECT vicidial_live_agents.user from vicidial_live_agents, vicidial_live_inbound_agents WHERE vicidial_live_agents.user=vicidial_live_inbound_agents.user and status IN('CLOSER','READY') and lead_id<1 $ADUfindSQL and vicidial_live_inbound_agents.group_id='$group_id' and last_update_time > '$BDtsSQLdate' and vicidial_live_agents.user NOT IN($ring_no_answer_agents$vlia_users) and ring_callerid='' and ( (vicidial_live_inbound_agents.daily_limit = '-1') or (vicidial_live_inbound_agents.daily_limit > vicidial_live_inbound_agents.calls_today) ) $qp_groupWAIT_camp_SQL $agent_call_order limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($ARGV[0]=~/debug/i) {print $stmtA."\n";}


			if ($sthArows>0) 
				{
				while (@user_row=$sthA->fetchrow_array)
					{
					$user=$user_row[0];
					}
					$sthA->finish();
				}

			if ($user ne "") 
				{
				# If XFER, cannot go to person who transferred
				if ($email_type eq "XFER") 
					{
					$user_clause=" and user!='$user'";
					} 
				else 
					{
					$user_clause="";
					}

				$upd_stmt="update vicidial_email_list set status='QUEUE', user='$user' where email_row_id='$email_row_id' $user_clause";
				$upd_rslt=$dbhB->prepare($upd_stmt);
				$upd_rslt->execute or die "executing: $upd_stmt ", $dbhB->errstr;
				if ($ARGV[0]=~/debug/i) {print $upd_stmt."\n";}

				if ($upd_rslt->rows>0) 
					{
					$upd_rslt->finish();
					$upd_stmt="update vicidial_live_agents set status='MQUEUE' where user='$user'";
					$upd_rslt=$dbhA->prepare($upd_stmt);
					$upd_rslt->execute or die "executing: $upd_stmt ", $dbhB->errstr;
					if ($ARGV[0]=~/debug/i) {print $upd_stmt."\n";}
					}
				$upd_rslt->finish();
				}

			$stmtA = "UNLOCK TABLES;";
			my $LOCKaffected_rows = $dbhA->do($stmtA);
			}
		else
			{
			$stmtA = "LOCK TABLES vicidial_live_agents WRITE;";
			my $LOCKaffected_rows = $dbhA->do($stmtA);
			$dbhP=$dbhA;   $mysql_count='02158';   $MEL_aff_rows=$LOCKaffected_rows;   &mysql_error_logging;

			$SQL_group_id=$group_id;   $SQL_group_id =~ s/_/\\_/gi;
			$stmtA = "SELECT user FROM vicidial_live_agents where status IN('CLOSER','READY') and lead_id<1 $ADUfindSQL and campaign_id IN($INBOUNDcampsSQL) and closer_campaigns LIKE \"% $SQL_group_id %\" and last_update_time > '$BDtsSQLdate' and vicidial_live_agents.user NOT IN($ring_no_answer_agents) $qp_groupWAIT_SQL $qp_groupWAIT_camp_SQL $agent_call_order limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			if ($ARGV[0]=~/debug/i) {print $stmtA."\n";}
			$sthArows=$sthA->rows;

			if ($sthArows>0) 
				{
				while (@user_row=$sthA->fetchrow_array)
					{
					$user=$user_row[0];
					}
				$sthA->finish();
				}

			if ($user ne "") 
				{
				# If XFER, cannot go to person who transferred
				if ($email_type eq "XFER") 
					{
					$user_clause=" and user!='$user'";
					} 
				else 
					{
					$user_clause="";
					}

				$upd_stmt="update vicidial_email_list set status='QUEUE', user='$user' where email_row_id='$email_row_id' $user_clause";
				$upd_rslt=$dbhB->prepare($upd_stmt);
				$upd_rslt->execute or die "executing: $upd_stmt ", $dbhB->errstr;
				if ($ARGV[0]=~/debug/i) {print $upd_stmt."\n";}

				if ($upd_rslt->rows>0) 
					{
					$upd_rslt->finish();
					$upd_stmt="update vicidial_live_agents set status='MQUEUE' where user='$user'";
					$upd_rslt=$dbhA->prepare($upd_stmt);
					$upd_rslt->execute or die "executing: $upd_stmt ", $dbhB->errstr;
					if ($ARGV[0]=~/debug/i) {print $upd_stmt."\n";}
					}
				$upd_rslt->finish();
				}

			$stmtA = "UNLOCK TABLES;";
			my $LOCKaffected_rows = $dbhA->do($stmtA);
			}

		}
	if ($ARGV[0]=~/debug/i) {print "\n\n";}

	}

sub mysql_error_logging
	{
	($Lsec,$Lmin,$Lhour,$Lmday,$Lmon,$Lyear,$Lwday,$Lyday,$Lisdst) = localtime(time);
	if ($Lhour < 10) {$Lhour = "0$Lhour";}
	if ($Lmin < 10) {$Lmin = "0$Lmin";}
	if ($Lsec < 10) {$Lsec = "0$Lsec";}
	$LOGtime = "$Lhour:$Lmin:$Lsec";

	$errno='';
	$error='';
	if ( ($mel > 0) || ($one_mysql_log > 0) )
		{
		$errno = $dbhP->err();
		if ( ($errno > 0) || ($mel > 1) || ($one_mysql_log > 0) )
			{
			$error = $dbhP->errstr();
			### open the log file for writing ###
			open(Eout, ">>$ERRLOGfile")
					|| die "Can't open $ERRLOGfile: $!\n";
			print Eout "$now_date|$LOGtime|$script|$mysql_count|$MEL_aff_rows|$errno|$error|$stmtA|$callerid|$insert_lead_id|\n";
			close(Eout);
			}
		}
	$one_mysql_log=0;
	}
