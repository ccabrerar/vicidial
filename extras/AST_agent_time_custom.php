<?php 
# AST_agent_time_custom.php
# 
# Pulls a single time stat per agent selectable by user group
# NOTE: THIS SCRIPT IS DESIGNED TO BE CUSTOMIZED!
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 170318-1001 - First build


$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["time_in_sec"]))			{$time_in_sec=$_GET["time_in_sec"];}
	elseif (isset($_POST["time_in_sec"]))	{$time_in_sec=$_POST["time_in_sec"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if ($search_archived_data=="checked") 
	{
	$agent_log_table="vicidial_agent_log_archive";
	} 
else 
	{
	$agent_log_table="vicidial_agent_log";
	}

$report_name = 'Agent Time Custom';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

$user_case = '';
$TIME_agenttimedetail = '';
if (strlen($TIME_agenttimedetail)<1)
	{$TIME_agenttimedetail = 'H';}
if ($time_in_sec)
	{
	$TIME_agenttimedetail = 'S';
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}


$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	}
else
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}

$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$LOGserver_name' and hostname='$LOGhostname' LIMIT 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$webserver_id_ct = mysqli_num_rows($rslt);
if ($webserver_id_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$webserver_id = $row[0];
	}
else
	{
	##### insert webserver entry
	$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$LOGserver_name','$LOGhostname');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
#	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}


for ($i=0; $i<count($user_group); $i++)
	{
	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
	}
$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$TCuser_group_SQL = $user_group_SQL;
	$user_group_SQL = "and ".$agent_log_table.".user_group IN($user_group_SQL)";
	$TCuser_group_SQL = "and user_group IN($TCuser_group_SQL)";
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

	
if (strlen($user_group[0]) < 1)
	{
	echo _QXZ("PLEASE SELECT A USER GROUP AND DATE-TIME BELOW AND CLICK SUBMIT")."\n";
	echo _QXZ(" NOTE: stats taken from shift specified")."\n";
	}

else
	{
	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
	if (strlen($time_END) < 6) {$time_END = "23:59:59";}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	$ASCII_text.=""._QXZ("$report_name",40)." $NOW_TIME ($db_source)\n";
	$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";




	############################################################################
	##### BEGIN gathering information from the database section
	############################################################################

	### BEGIN gather user IDs and names for matching up later
	$stmt="select full_name,user from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user limit 100000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$users_to_print = mysqli_num_rows($rslt);
	$i=0;
	$graph_stats=array();
	$max_calls=1;
	$max_timeclock=1;
	$max_agenttime=1;
	$max_wait=1;
	$max_talk=1;
	$max_dispo=1;
	$max_pause=1;
	$max_dead=1;
	$max_customer=1;

	while ($i < $users_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$ULname[$i] =	$row[0];
		$ULuser[$i] =	$row[1];
		$i++;
		}
	### END gather user IDs and names for matching up later



	##### BEGIN Gather all agent time records and parse through them in PHP to save on DB load
	$stmt="select user,wait_sec,talk_sec,pause_sec,sub_status from ".$agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' $user_group_SQL limit 10000000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.= "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	$j=0;
	$k=0;
	$uc=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$user =			$row[0];
		$wait =			$row[1];
		$talk =			$row[2];
		$pause =		$row[3];
		$pcode =		$row[4];
		if ($wait > 65000) {$wait=0;}
		if ($talk > 65000) {$talk=0;}
		if ($pause > 65000) {$pause=0;}
		# Custom pause codes to include go here:
		if (!preg_match("/NXDIAL|MANUAL/",$pcode))
			{$pause=0;}
		$TOTwait =	($TOTwait + $wait);
		$TOTtalk =	($TOTtalk + $talk);
		$TOTpause =	($TOTpause + $pause);
		$TOTALtime = ($TOTALtime + $pause + $talk + $wait);
		
		$user_found=0;
		if ($uc < 1) 
			{
			$Suser[$uc] = $user;
			$uc++;
			}
		$m=0;
		while ( ($m < $uc) and ($m < 50000) )
			{
			if ($user == "$Suser[$m]")
				{
				$user_found++;

				$Swait[$m] =	($Swait[$m] + $wait);
				$Stalk[$m] =	($Stalk[$m] + $talk);
				$Spause[$m] =	($Spause[$m] + $pause);
				}
			$m++;
			}
		if ($user_found < 1)
			{
			$Scalls[$uc] =	0;
			$Suser[$uc] =	$user;
			$Swait[$uc] =	$wait;
			$Stalk[$uc] =	$talk;
			$Spause[$uc] =	$pause;
			$uc++;
			}

		$i++;
		}
	if ($DB) {echo "Done gathering $i records, analyzing...<BR>\n";}
	##### END Gather all agent time records and parse through them in PHP to save on DB load

	############################################################################
	##### END gathering information from the database section
	############################################################################




	##### BEGIN print the output to screen or put into file output variable
	$ASCII_text.="<PRE>";
	$ASCII_text.=_QXZ("AGENT TIME CUSTOM BREAKDOWN").":\n";
	$ASCII_text.="+---------------------------+----------+------------+\n";
	$ASCII_text.="| "._QXZ("USER NAME",25)." | "._QXZ("ID",8)." | "._QXZ("TIME",10)." |\n";
	$ASCII_text.="+---------------------------+----------+------------+\n";
	##### END print the output to screen or put into file output variable





	############################################################################
	##### BEGIN formatting data for output section
	############################################################################

	##### BEGIN loop through each user formatting data for output
	$AUTOLOGOUTflag=0;
	$m=0;
	while ( ($m < $uc) and ($m < 50000) )
		{
		$SstatusesHTML='';
		$SstatusesFILE='';
		$Stime[$m] = ($Swait[$m] + $Stalk[$m] + $Spause[$m]);
		$Swaitpct[$m]=MathZDC(100*$Swait[$m], $Stime[$m]);
		$Stalkpct[$m]=MathZDC(100*$Stalk[$m], $Stime[$m]);
		$Spausepct[$m]=MathZDC(100*$Spause[$m], $Stime[$m]);
		$RAWuser = $Suser[$m];
		$RAWtimeSEC = $Stime[$m];

		if (trim($Stime[$m])>$max_agenttime) {$max_agenttime=trim($Stime[$m]);}
		if (trim($Swait[$m])>$max_wait) {$max_wait=trim($Swait[$m]);}
		if (trim($Stalk[$m])>$max_talk) {$max_talk=trim($Stalk[$m]);}
		if (trim($Spause[$m])>$max_pause) {$max_pause=trim($Spause[$m]);}

		$Swait[$m]=		sec_convert($Swait[$m],$TIME_agenttimedetail);
		$Stalk[$m]=		sec_convert($Stalk[$m],$TIME_agenttimedetail);
		$Spause[$m]=	sec_convert($Spause[$m],$TIME_agenttimedetail);
		$Stime[$m]=		sec_convert($Stime[$m],$TIME_agenttimedetail);

		$RAWtime = $Stime[$m];
		$RAWwait = $Swait[$m];
		$RAWtalk = $Stalk[$m];
		$RAWpause = $Spause[$m];

		$n=0;
		$user_name_found=0;
		while ($n < $users_to_print)
			{
			if ($Suser[$m] == "$ULuser[$n]")
				{
				$user_name_found++;
				$RAWname = $ULname[$n];
				$Sname[$m] = $ULname[$n];
				}
			$n++;
			}
		if ($user_name_found < 1)
			{
			$RAWname =		"NOT IN SYSTEM";
			$Sname[$m] =	$RAWname;
			}

		$Swait[$m]=		sprintf("%10s", $Swait[$m]); 
		$Stalk[$m]=		sprintf("%10s", $Stalk[$m]); 
		$Spause[$m]=	sprintf("%10s", $Spause[$m]); 
		$Stime[$m]=		sprintf("%10s", $Stime[$m]); 

		if ($non_latin < 1)
			{
			$Sname[$m]=	sprintf("%-25s", $Sname[$m]); 
			while(strlen($Sname[$m])>25) {$Sname[$m] = substr("$Sname[$m]", 0, -1);}
			$Suser[$m] =		sprintf("%-8s", $Suser[$m]);
			while(strlen($Suser[$m])>8) {$Suser[$m] = substr("$Suser[$m]", 0, -1);}
			}
		else
			{	
			$Sname[$m]=	sprintf("%-75s", $Sname[$m]); 
			while(mb_strlen($Sname[$m],'utf-8')>25) {$Sname[$m] = mb_substr("$Sname[$m]", 0, -1,'utf-8');}
			$Suser[$m] =	sprintf("%-24s", $Suser[$m]);
			while(mb_strlen($Suser[$m],'utf-8')>8) {$Suser[$m] = mb_substr("$Suser[$m]", 0, -1,'utf-8');}
			}

		$Toutput = "| $Sname[$m] | <a href=\"./user_stats.php?user=$RAWuser&begin_date=$query_date&end_date\">$Suser[$m]</a> | $Stime[$m] |\n";
		
		$user_IDs[$m]=$Suser[$m];

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;

		$ASCII_text.="$Toutput";

	#	echo "$Suser[$m]|$Sname[$m]|$Swait[$m]|$Stalk[$m]|$Spause[$m]|\n";
		$m++;
		}
	##### END loop through each user formatting data for output


	$TOT_AGENTS = $m;
	$hTOT_AGENTS = sprintf("%5s", $TOT_AGENTS);
	$k=$m;

	if ($DB) {echo "Done analyzing...   $TOTwait|$TOTtalk|$TOTpause|$TOTALtime|$uc|<BR>\n";}


	### BEGIN sort through output to display properly ###
	if ( ($TOT_AGENTS > 0) and (preg_match('/NAME|ID|TIME|LEADS|TCLOCK/',$stage)) )
		{
		sort($TOPsort, SORT_NUMERIC);

		$m=0;
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{$ASCII_text.="$TOPsorted_output[$i]";}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###

	############################################################################
	##### END formatting data for output section
	############################################################################




	############################################################################
	##### BEGIN last line totals output section
	############################################################################
	$SUMstatusesHTML='';
	$SUMstatusesFILE='';
	$TOTtotPAUSE=0;
	$n=0;
	while ($n < $sub_status_count)
		{
		$Scalls=0;
		$Sstatus=$sub_statusesARY[$n];
		$SUMstatusTXT='';
		$total_var=$Sstatus."_total";
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $subs_to_print)
			{
			if ($Sstatus=="$sub_status[$i]")
				{
				$Scalls =		($Scalls + $PCpause_sec[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTML .= "          0 |";
			$$total_var="0";
			}
		else
			{
			$TOTtotPAUSE = ($TOTtotPAUSE + $Scalls);

			$USERsumstatPAUSE_MS =		sec_convert($Scalls,$TIME_agenttimedetail);
			$pfUSERsumstatPAUSE_MS =	sprintf("%11s", $USERsumstatPAUSE_MS);
			$$total_var="$pfUSERsumstatPAUSE_MS";

			$SUMstatusTXT = sprintf("%10s", $pfUSERsumstatPAUSE_MS);
			$SUMstatusesHTML .= "$SUMstatusTXT |";
			$SUMstatusesFILE .= ",$USERsumstatPAUSE_MS";
			}
		$n++;
		}
	### END loop through each status ###

	### call function to calculate and print dialable leads
	$TOTwait = sec_convert($TOTwait,$TIME_agenttimedetail);
	$TOTtalk = sec_convert($TOTtalk,$TIME_agenttimedetail);
	$TOTpause = sec_convert($TOTpause,$TIME_agenttimedetail);
	$TOTALtime = sec_convert($TOTALtime,$TIME_agenttimedetail);
	$TOTtimeTC = sec_convert($TOTtimeTC,$TIME_agenttimedetail);

	$hTOTwait =	sprintf("%11s", $TOTwait);
	$hTOTtalk =	sprintf("%11s", $TOTtalk);
	$hTOTpause =	sprintf("%11s", $TOTpause);
	$hTOTALtime = sprintf("%11s", $TOTALtime);
	$hTOTtimeTC = sprintf("%11s", $TOTtimeTC);
	###### END LAST LINE TOTALS FORMATTING ##########


 
	$ASCII_text.="+---------------------------+----------+------------+\n";
	$ASCII_text.="| "._QXZ("TOTALS",10)." "._QXZ("AGENTS",9,"r").":$hTOT_AGENTS           |$hTOTALtime |\n";
	$ASCII_text.="+--------------------------------------+------------+\n";

	$ASCII_text.="\n\n</PRE>";
	}
	############################################################################
	##### END formatting data for output section
	############################################################################






############################################################################
##### BEGIN HTML form section
############################################################################
$JS_onload.="}\n";
if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
$JS_text.="</script>\n";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 BGCOLOR=\"#e3e3ff\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

?>
<script language="JavaScript">
function openNewWindow(url)
	{
	window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
	}

var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'query_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php

echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

?>
<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'end_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php

echo "</TD><TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
echo "<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {echo "<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	  else {echo "<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "</TD><TD VALIGN=TOP>";
echo "<input type='checkbox' name='time_in_sec' value='checked' $time_in_sec>"._QXZ("Time in seconds")."<BR>";
echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
echo "</TD><TD VALIGN=TOP>\n<BR><BR>";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
echo " <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";
############################################################################
##### END HTML form section
############################################################################

echo $ASCII_text;


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "<font size=1 color=white>$RUNtime</font>\n";


if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	}

$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

?>

</BODY></HTML>
