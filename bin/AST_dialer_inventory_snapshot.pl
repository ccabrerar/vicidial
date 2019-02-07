#!/usr/bin/perl
#
# AST_dialer_inventory_snapshot.pl
# 
# Copyright (C) 2012  Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#                     Matt Florell <vicidial@gmail.com>
#
# NOTES:
# - run this script from the crontab in off-hours to generate snapshot reporting
#    data for the AST_dialer_inventory_report.php script, here is a recommended
#    crontab entry:
# 34 0,7 * * * /usr/share/astguiclient/AST_dialer_inventory_snapshot.pl
#
#
# CHANGES
# 111013-0054 - First build
# 111106-1317 - Added help screen and debug output, reformatting
# 111110-0652 - Added single campaign/list options and call time override option
# 111230-1140 - Debugging additions and more minor changes
# 120221-2238 - Added list option for inventory_report, use list_description
#

use DBI;
use POSIX;
#use Tie::IxHash;

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
$snapshot_time="$year-$mon-$mday $hour:$min:$sec";

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
	if ( ($line =~ /^PATHDONEmonitor/) && ($CLIDONEmonitor < 1) )
		{$PATHDONEmonitor = $line;   $PATHDONEmonitor =~ s/.*=//gi;}
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
	if ( ($line =~ /^VARFTP_host/) && ($CLIFTP_host < 1) )
		{$VARFTP_host = $line;   $VARFTP_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_user/) && ($CLIFTP_user < 1) )
		{$VARFTP_user = $line;   $VARFTP_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_pass/) && ($CLIFTP_pass < 1) )
		{$VARFTP_pass = $line;   $VARFTP_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_port/) && ($CLIFTP_port < 1) )
		{$VARFTP_port = $line;   $VARFTP_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_dir/) && ($CLIFTP_dir < 1) )
		{$VARFTP_dir = $line;   $VARFTP_dir =~ s/.*=//gi;}
	if ( ($line =~ /^VARHTTP_path/) && ($CLIHTTP_path < 1) )
		{$VARHTTP_path = $line;   $VARHTTP_path =~ s/.*=//gi;}
	$i++;
	}

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
		print "  [--campaign=XXX] = run for only one campaign\n";
		print "  [--list=XXX] = run for only one list\n";
		print "  [--override-24hours] = force no local call time check\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--help] = this screen\n";
		print "\n";
		exit;
		}
	else
		{
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
		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
			$CLIcampaign = $data_in[1];
			$CLIcampaign =~ s/ .*$//gi;
			$campaign_SQL=" and campaign_id='$CLIcampaign' ";
			if ($DB) {print "\n----- SINGLE CAMPAIGN: $CLIcampaign -----\n\n";}
			}
		if ($args =~ /--list=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--list=/,$args);
			$CLIlist = $data_in[1];
			$CLIlist =~ s/ .*$//gi;
			$list_SQL=" and (list_id='$CLIlist') ";
			$campaign_SQL=" and campaign_id=(SELECT campaign_id from vicidial_lists where list_id='$CLIlist') ";
			if ($DB) {print "\n----- SINGLE LIST: $CLIlist -----\n\n";}
			}
		if ($args =~ /--override-24hours/i)
			{
			$override_24hours="yes";
			if ($DB) {print "\n----- OVERRIDE 24 HOURS -----\n\n";}
			}
		}
	}

if ($DB > 0) {print "\nAST_dialer_inventory_snapshot.pl - backend statistical gathering process\n";}


$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhD = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbh = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$single_status=1;
$time_settings[0]="SERVER";
$time_settings[1]="LOCAL";

$gmt_stmt="SELECT default_local_gmt from system_settings";
$gmt_rslt=$dbhA->prepare($gmt_stmt);
$gmt_rslt->execute();
@gmt_row=$gmt_rslt->fetchrow_array;
$local_offset=$gmt_row[0];
		
$gmt_stmt="SELECT distinct gmt_offset_now, gmt_offset_now-($local_offset) from vicidial_list";
$gmt_rslt=$dbhA->prepare($gmt_stmt);
$gmt_rslt->execute();
# tie %gmt_ary, "Tie::IxHash";
while (@gmt_row=$gmt_rslt->fetchrow_array) 
	{
	$gmt_row[1]=~s/25$/15/gi;
	$gmt_row[1]=~s/75$/45/gi;
	$gmt_row[1]=~s/\.5$/30/gi;
	$gmt_row[1]=~s/\./:/gi;
	$gmt_array{"$gmt_row[0]"}=$gmt_row[1];
	}

if ($DB > 0) {print "GMT OFFSET:     $local_offset\nLIST TIMEZONES: $i\n";}

# Get shift information
# tie %shift_ary, "Tie::IxHash";
$shift_stmt="SELECT shift_id, shift_name, str_to_date(shift_start_time, '%H%i') as shift_start_time, addtime(str_to_date(shift_start_time, '%H%i'), shift_length) as shift_end_time, if(addtime(str_to_date(shift_start_time, '%H%i'), shift_length)>'23:59:59', '1', '0') as day_offset, shift_weekdays from vicidial_shifts where report_option='Y' order by shift_start_time asc";
$shift_rslt=$dbhA->prepare($shift_stmt);
$shift_rslt->execute();
while(@shift_row=$shift_rslt->fetchrow_array) 
	{
	$shift_ary{$shift_row[0]}[0]=$shift_row[1];
	$shift_ary{$shift_row[0]}[1]=$shift_row[2];
	$shift_ary{$shift_row[0]}[2]=$shift_row[3];
	$shift_ary{$shift_row[0]}[3]=$shift_row[4];
	$shift_ary{$shift_row[0]}[4]=$shift_row[5];
	}

if ($DB > 0) {print "SHIFT COUNT:    $i\n";}

$campaign_stmt="SELECT campaign_id from vicidial_campaigns  where campaign_id in (SELECT distinct campaign_id from vicidial_lists where inventory_report='Y') $campaign_SQL order by campaign_id;";
$campaign_rslt=$dbhA->prepare($campaign_stmt);
$campaign_rslt->execute();
while (@group=$campaign_rslt->fetchrow_array) 
	{
	$stmt="SELECT call_count_limit, call_count_target, dial_statuses, local_call_time, drop_lockout_time from vicidial_campaigns where campaign_id='$group[0]'";
	if ($DBX > 0) {print "     |$stmt|\n";}
	$rslt=$dbhB->prepare($stmt);
	$rslt->execute();
	@campaign_row=$rslt->fetchrow_array;
	$call_count_limit=$campaign_row[0];
	$call_count_target=$campaign_row[1];
	$active_dial_statuses=$campaign_row[2];
	$local_call_time=$campaign_row[3];
	if ($override_24hours) {$local_call_time="24hours";}
	$drop_lockout_time=$campaign_row[4];

	$stmt="SELECT distinct status from vicidial_statuses where completed='N' UNION SELECT distinct status from vicidial_campaign_statuses where completed='N' and campaign_id='$group[0]'";
	if ($DBX > 0) {print "     |$stmt|\n";}
	$rslt=$dbhB->prepare($stmt);
	$rslt->execute();
	$inactive_dial_statuses=" ";
	$dial_statuses=" ";
	$inventory_statuses=" ";
	$inventory_ptnstr="|";
	while (@row=$rslt->fetchrow_array) 
		{
		$dial_statuses.="$row[0] ";
		$inventory_statuses.="'$row[0]',";
		$inventory_ptnstr.="$row[0]|";
		if ($active_dial_statuses !~ /\s$row[0]\s/i) {$inactive_dial_statuses.="$row[0] ";}
		}
	$inventory_statuses=substr($inventory_statuses, 0, -1);
	if ($DBX > 0) {print "         CAMPAIGN DIAL STATUSES: |$active_dial_statuses|\n";}
	if ($DBX > 0) {print "                  DIAL STATUSES: |$dial_statuses|\n";}
	if ($DBX > 0) {print "     INVENTORY STATUSES RESULTS: |$inventory_statuses|\n";}
	if ($DBX > 0) {print "      INACTIVE STATUSES RESULTS: |$inactive_dial_statuses|\n";}

	$filter_stmt="SELECT lead_filter_sql from vicidial_campaigns v, vicidial_lead_filters vlf where v.campaign_id='$group[0]' and v.lead_filter_id=vlf.lead_filter_id limit 1";
	if ($DBX > 0) {print "     |$filter_stmt|\n";}
	$filter_rslt=$dbhB->prepare($filter_stmt);
	$filter_rslt->execute();
	@filter_row=$filter_rslt->fetchrow_array;
	$filter_row[0] =~ s/\\//gi;
	if (length($filter_row[0])>0) {$filter_SQL=" and $filter_row[0]";} else {$filter_SQL="";}

	if ($DB > 0) {print "CAMPAIGN: $group[0]   LIMIT: $call_count_limit   TARGET: $call_count_target   STATUSES: $i\n";}

	$lists_stmt="SELECT list_id, list_name, list_description, if(list_lastcalldate is null, '*** Not called *** ', list_lastcalldate) as list_lastcalldate from vicidial_lists where (campaign_id='$group[0]') and inventory_report='Y' $list_SQL order by list_id asc";
	if ($DBX > 0) {print "     |$lists_stmt|\n";}
	$lists_rslt=$dbhB->prepare($lists_stmt);
	$lists_rslt->execute();
	while (@lists_row=$lists_rslt->fetchrow_array) 
		{
		$list_id=$lists_row[0];
		$list_name=$lists_row[1];
		$list_description=$lists_row[2];
		$last_calldate=$lists_row[3];

		$list_start_inv=0;
		GetListCount($list_id, $inventory_ptnstr);
		if ($list_start_inv>0) {$average_calls=sprintf("%.1f", $total_calls/$list_start_inv);} else {$average_calls="0.0";}
		$Xdialable_count_nofilter = dialable_leads($local_call_time,$dial_statuses,$list_id,$drop_lockout_time,$call_count_limit,$single_status);
		if (length($inactive_dial_statuses)>1) 
			{
			$Xdialable_inactive_count = dialable_leads($local_call_time,$inactive_dial_statuses,$list_id,$drop_lockout_time,$call_count_limit,$single_status,$filter_SQL);
			} 
		else 
			{
			$Xdialable_inactive_count = 0;
			}

		$oneoff_SQL=$filter_SQL." and (called_count < $call_count_limit-1) ";
		$oneoff_count = dialable_leads($local_call_time,$dial_statuses,$list_id,$drop_lockout_time,$call_count_limit,$single_status,$oneoff_SQL);

		$full_dialable_SQL="";

		$Xdialable_count = dialable_leads($local_call_time,$dial_statuses,$list_id,$drop_lockout_time,$call_count_limit,$single_status,$filter_SQL);
		$dialable_total = ($dialable_total + $Xdialable_count);
		if ($DB > 0) {print "FULL DIALABLE SQL: |$dial_statuses|\n|$full_dialable_SQL|";}

		if ($list_start_inv>0) {$penetration=sprintf("%.2f", (100*($list_start_inv-$Xdialable_count)/$list_start_inv));} else {$penetration="0.00";}

		for ($q=0; $q<scalar(@time_settings); $q++) 
			{
			%shift_ary2=%shift_ary;
			$shift_data="";
			$time_setting=$time_settings[$q];
			foreach $val (keys %shift_ary2) 
				{
				$total_shift_count=0;
				$gmt_stmt="SELECT distinct gmt_offset_now from vicidial_list where list_id='$list_id'";
				if ($DBX > 0) {print "     |$gmt_stmt|\n";}
				$gmt_rslt=$dbhC->prepare($gmt_stmt);
				$gmt_rslt->execute();
				while (@gmt_row=$gmt_rslt->fetchrow_array) 
					{
					if ($time_setting=="LOCAL") 
						{
						$offset_hours=$gmt_array{"$gmt_row[0]"};
						} 
					else 
						{
						$offset_hours=0;
						}

					if (length($shift_ary2{$val}[4])>0) 
						{
						if ($shift_ary2{$val}[3]==0) 
							{
							$shift_days_SQL=" and time(addtime(call_date, '$offset_hours'))>='$shift_ary2{$val}[1]' and time(addtime(call_date, '$offset_hours'))<='$shift_ary2{$val}[2]' ";
							$day_str="";
							for ($j=0; $j<length($shift_ary2{$val}[4]); $j++) 
								{
								$day=substr($shift_ary2{$val}[4], $j, 1);
								$day++;
								$day_str.="$day,";
								}
							$day_str=substr($day_str, 0, -1);
							$shift_days_SQL.="and dayofweek(call_date) in ($day_str)";
							} 
						else 
							{
							$shift_days_SQL=" and (";
							for ($j=0; $j<length($shift_ary2{$val}[4]); $j++) 
								{
								$day=substr($shift_ary2{$val}[4], $j, 1);
								$day++;
								$next_day_hours=(substr("0".substr($shift_ary2{$val}[2], 0, 2)%24, -2).substr($shift_ary2{$val}[2], 2));
								$next_day=($day%7)+1;
								$shift_days_SQL.=" ((time(addtime(call_date, '$offset_hours'))>='$shift_ary2{$val}[1]' and dayofweek(call_date)='$day') or (time(addtime(call_date, '$offset_hours'))<='$next_day_hours' and dayofweek(call_date)='$next_day')) or ";
								# call_date, time(call_date), addtime(call_date, '-1:00:00'), time(addtime(call_date, '-1:30')), time(call_date)>=addtime('09:00', '00:04:00')
								}
							$shift_days_SQL=substr($shift_days_SQL, 0, -3).")";
							}
						} 
					else 
						{
						$shift_days_SQL="";
						}

					$shift_stmt="SELECT count(*) from (SELECT lead_id, count(*) as count from vicidial_log where lead_id in (SELECT lead_id from vicidial_list where $full_dialable_SQL and gmt_offset_now='$gmt_row[0]' $filter_SQL) $shift_days_SQL group by lead_id) as count_table where count_table.count>='$call_count_target'";
					if ($DBX > 0) {print "     |$shift_stmt|\n";}
					$shift_rslt=$dbhD->prepare($shift_stmt);
					$shift_rslt->execute();
					@shift_row=$shift_rslt->fetchrow_array;
					$total_shift_count+=$shift_row[0];
					}
				$shift_count=$Xdialable_count-$total_shift_count;
				$shift_data.="$val,$shift_count|";
				}
			$shift_data=substr($shift_data,0,-1);
			$ins_stmt="insert into dialable_inventory_snapshots(snapshot_time, list_id, list_name, list_description, campaign_id, list_lastcalldate, list_start_inv, dialable_count, dialable_count_nofilter, dialable_count_oneoff, dialable_count_inactive, average_call_count, penetration, shift_data, time_setting) values('$snapshot_time', '$list_id', '$list_name', '$list_description', '$group[0]', '$last_calldate', '$list_start_inv', '$Xdialable_count', '$Xdialable_count_nofilter', '$oneoff_count', '$Xdialable_inactive_count', '$average_calls', '$penetration', '$shift_data', '$time_setting')";
			if ($DBX > 0) {print "     |$ins_stmt|\n";}
			$ins_rslt=$dbh->prepare($ins_stmt);
			$ins_rslt->execute();
			#print "$ins_stmt\n";
			}
		if ($DB > 0) {print "   LIST: $list_id - $list_name   START: $list_start_inv   DIALABLE: $dialable_total   ONEOFF: $oneoff_count\n";}
		}
	}

$secY = time();
$process_time = ($secY - $secX);
if ($DB > 0) {print "\nDONE, process time: $process_time seconds\n";}





##### subroutines #####
sub GetListCount 
	{
	$list_id=$_[0];
	$inventory_ptnstr=$_[1];
	$ct_stmt="SELECT status, called_count, count(*) From vicidial_list where list_id='$list_id' group by status, called_count order by status, called_count";
	if ($DBX > 0) {print "     |$ct_stmt|\n";}
	$ct_rslt=$dbh->prepare($ct_stmt);
	$ct_rslt->execute();
	$new_count=0;  $total_calls=0;
	while (@ct_row=$ct_rslt->fetchrow_array) 
		{
		$list_start_inv+=$ct_row[2];
		$total_calls+=($ct_row[1]*$ct_row[2]);
		if ($inventory_ptnstr=~/\|$ct_row[0]\|/ && $ct_row[1]=="0") {$new_count+=$ct_row[2];} 
		}
	}

sub dialable_leads 
	{
	$local_call_time=$_[0];
	$working_dial_statuses=$_[1];
	$camp_lists=$_[2];
	$drop_lockout_time=$_[3];
	$call_count_limit=$_[4];
	$single_status=$_[5];
	$fSQL=$_[6];

	if (defined($camp_lists))
		{
		if (length($camp_lists)>1)
			{
			if (length($working_dial_statuses)>2)
				{
				$g=0;
				$p='13';
				$GMT_gmt[0] = '';
				$GMT_hour[0] = '';
				$GMT_day[0] = '';
				while ($p > -13)
					{
					$pzone=3600 * $p;
					($sec,$min,$hour,$mday,$mon,$year,$pday,$yday,$isdst)=(gmtime()+$pzone);
					$pmin=substr("0$min", -2);
					$phour=($hour*100);
					#$pmin=(gmdate("i", time() + $pzone));
					#$phour=( (gmdate("G", time() + $pzone)) * 100);
					#$pday=gmdate("w", time() + $pzone);
					$tz = sprintf("%.2f", $p);	
					$GMT_gmt[$g] = "$tz";
					$GMT_day[$g] = "$pday";
					$GMT_hour[$g] = ($phour + $pmin);
					$p = ($p - 0.25);
					$g++;
					}

				$stmt="SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$local_call_time';";
				if ($DBX > 0) {print "     |$stmt|\n";}
				$rslt=$dbh->prepare($stmt);
				$rslt->execute();
				@rowx=$rslt->fetchrow_array;
				$Gct_default_start =	$rowx[3];
				$Gct_default_stop =		$rowx[4];
				$Gct_sunday_start =		$rowx[5];
				$Gct_sunday_stop =		$rowx[6];
				$Gct_monday_start =		$rowx[7];
				$Gct_monday_stop =		$rowx[8];
				$Gct_tuesday_start =	$rowx[9];
				$Gct_tuesday_stop =		$rowx[10];
				$Gct_wednesday_start =	$rowx[11];
				$Gct_wednesday_stop =	$rowx[12];
				$Gct_thursday_start =	$rowx[13];
				$Gct_thursday_stop =	$rowx[14];
				$Gct_friday_start =		$rowx[15];
				$Gct_friday_stop =		$rowx[16];
				$Gct_saturday_start =	$rowx[17];
				$Gct_saturday_stop =	$rowx[18];
				$Gct_state_call_times = $rowx[19];

				$ct_states = '';
				$ct_state_gmt_SQL = '';
				$ct_srs=0;
				$b=0;
				if (length($Gct_state_call_times)>2)
					{
					@state_rules = split(/\|/,$Gct_state_call_times);
					$ct_srs = ((scalar(@state_rules)) - 2);
					}
				while($ct_srs >= $b)
					{
					if (length($state_rules[$b])>1)
						{
						$stmt="SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
						if ($DBX > 0) {print "     |$stmt|\n";}
						$rslt=$dbh->prepare($stmt);
						$rslt->execute();
						@row=$rslt->fetchrow_array;
						$Gstate_call_time_id =		$row[0];
						$Gstate_call_time_state =	$row[1];
						$Gsct_default_start =		$row[4];
						$Gsct_default_stop =		$row[5];
						$Gsct_sunday_start =		$row[6];
						$Gsct_sunday_stop =			$row[7];
						$Gsct_monday_start =		$row[8];
						$Gsct_monday_stop =			$row[9];
						$Gsct_tuesday_start =		$row[10];
						$Gsct_tuesday_stop =		$row[11];
						$Gsct_wednesday_start =		$row[12];
						$Gsct_wednesday_stop =		$row[13];
						$Gsct_thursday_start =		$row[14];
						$Gsct_thursday_stop =		$row[15];
						$Gsct_friday_start =		$row[16];
						$Gsct_friday_stop =			$row[17];
						$Gsct_saturday_start =		$row[18];
						$Gsct_saturday_stop =		$row[19];

						$ct_states .="'$Gstate_call_time_state',";

						$r=0;
						$state_gmt='';
						while($r < $g)
							{
							if ($GMT_day[$r]==0)	#### Sunday local time
								{
								if (($Gsct_sunday_start==0) and ($Gsct_sunday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_sunday_start) and ($GMT_hour[$r]<$Gsct_sunday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==1)	#### Monday local time
								{
								if (($Gsct_monday_start==0) and ($Gsct_monday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_monday_start) and ($GMT_hour[$r]<$Gsct_monday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==2)	#### Tuesday local time
								{
								if (($Gsct_tuesday_start==0) and ($Gsct_tuesday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) and ($GMT_hour[$r]<$Gsct_tuesday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==3)	#### Wednesday local time
								{
								if (($Gsct_wednesday_start==0) and ($Gsct_wednesday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) and ($GMT_hour[$r]<$Gsct_wednesday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==4)	#### Thursday local time
								{
								if (($Gsct_thursday_start==0) and ($Gsct_thursday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_thursday_start) and ($GMT_hour[$r]<$Gsct_thursday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==5)	#### Friday local time
								{
								if (($Gsct_friday_start==0) and ($Gsct_friday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_friday_start) and ($GMT_hour[$r]<$Gsct_friday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							if ($GMT_day[$r]==6)	#### Saturday local time
								{
								if (($Gsct_saturday_start==0) and ($Gsct_saturday_stop==0))
									{
									if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								else
									{
									if ( ($GMT_hour[$r]>=$Gsct_saturday_start) and ($GMT_hour[$r]<$Gsct_saturday_stop) )
										{$state_gmt.="'$GMT_gmt[$r]',";}
									}
								}
							$r++;
							}
						$state_gmt = "$state_gmt'99'";
						$ct_state_gmt_SQL .= "or (state='$Gstate_call_time_state' and gmt_offset_now IN($state_gmt)) ";
						}

					$b++;
					}
				if (length($ct_states)>2)
					{
					$ct_states=~s/\,$//gi;
					$ct_statesSQL = "and state NOT IN($ct_states)";
					}
				else
					{
					$ct_statesSQL = "";
					}

				$r=0;
				$default_gmt='';
				while($r < $g)
					{
					if ($GMT_day[$r]==0)	#### Sunday local time
						{
						if (($Gct_sunday_start==0) and ($Gct_sunday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_sunday_start) and ($GMT_hour[$r]<$Gct_sunday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==1)	#### Monday local time
						{
						if (($Gct_monday_start==0) and ($Gct_monday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_monday_start) and ($GMT_hour[$r]<$Gct_monday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==2)	#### Tuesday local time
						{
						if (($Gct_tuesday_start==0) and ($Gct_tuesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_tuesday_start) and ($GMT_hour[$r]<$Gct_tuesday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==3)	#### Wednesday local time
						{
						if (($Gct_wednesday_start==0) and ($Gct_wednesday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_wednesday_start) and ($GMT_hour[$r]<$Gct_wednesday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==4)	#### Thursday local time
						{
						if (($Gct_thursday_start==0) and ($Gct_thursday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_thursday_start) and ($GMT_hour[$r]<$Gct_thursday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==5)	#### Friday local time
						{
						if (($Gct_friday_start==0) and ($Gct_friday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_friday_start) and ($GMT_hour[$r]<$Gct_friday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					if ($GMT_day[$r]==6)	#### Saturday local time
						{
						if (($Gct_saturday_start==0) and ($Gct_saturday_stop==0))
							{
							if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						else
							{
							if ( ($GMT_hour[$r]>=$Gct_saturday_start) and ($GMT_hour[$r]<$Gct_saturday_stop) )
								{$default_gmt.="'$GMT_gmt[$r]',";}
							}
						}
					$r++;
					}

				$default_gmt = "$default_gmt'99'";
				$all_gmtSQL = "(gmt_offset_now IN($default_gmt) $ct_statesSQL) $ct_state_gmt_SQL";

				$working_dial_statuses=~s/\s\-$//gi;
				@Dstatuses = split(/\s/, $working_dial_statuses);
				$Ds_to_print = (scalar(@Dstatuses) - 0);
				$Dsql = '';
				$o=0;
				while ($Ds_to_print > $o) 
					{
					$o++;
					$Dsql .= "'$Dstatuses[$o]',";
					}
				$Dsql=~s/\,$//gi;
				if (length($Dsql) < 2) {$Dsql = "''";}

				$DLTsql='';
				if ($drop_lockout_time > 0)
					{
					$DLseconds = ($drop_lockout_time * 3600);
					$DLseconds = floor($DLseconds);
					# $DLseconds = intval("$DLseconds");
					$DLTsql = "and ( ( (status IN('DROP','XDROP')) and (last_local_call_time < CONCAT(DATE_ADD(NOW(), INTERVAL -$DLseconds SECOND),' ',CURTIME()) ) ) or (status NOT IN('DROP','XDROP')) )";
					}

				$CCLsql='';
				if ($call_count_limit > 0)
					{
					$CCLsql = "and (called_count < $call_count_limit)";
					}

				$stmt="SELECT count(*) FROM vicidial_list where status IN($Dsql) and list_id IN($camp_lists) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL";
				$full_dialable_SQL="status IN($Dsql) and list_id IN($camp_lists) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL";
				if ($DBX > 0) {print "|$stmt|\n";}
				$rslt=$dbh->prepare($stmt);
				$rslt->execute();
				$rslt_rows = $rslt->rows;
				if ($rslt_rows)
					{
					@rowx=$rslt->fetchrow_array;
					$active_leads = "$rowx[0]";
					}
				else {$active_leads = '0';}

				if ($single_status > 0)
					{return $active_leads;}
				else
					{$HTML_header.="This campaign has $active_leads leads to be dialed in those lists\n";}
				}
			else
				{
				$HTML_header.="no dial statuses selected for this campaign\n";
				}
			}
		else
			{
			$HTML_header.="no active lists selected for this campaign\n";
			}
		}
	else
		{
		$HTML_header.="no active lists selected for this campaign\n";
		}
	##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###
	}
