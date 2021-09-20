<?php 
# AST_API_log_report.php
#
# This report is for viewing the a report of API activity to the vicidial_api_log table
#
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 160305-0842 - First build
# 170209-1237 - Added URL and IP display
# 170210-0700 - Reworked Show URLs code to be faster
# 170409-1553 - Added IP List validation code
# 170823-2241 - Added HTML formatting and screen colors, fixed CSV output bug
# 180206-2300 - Added option for displaying individual URL variables
# 180301-2303 - Added DATA column to TEXT/HTML output, Added GET-AND-POST URL logging
# 180502-2115 - Added new help display
# 191013-0829 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["api_date_D"]))			{$api_date_D=$_GET["api_date_D"];}
	elseif (isset($_POST["api_date_D"]))	{$api_date_D=$_POST["api_date_D"];}
if (isset($_GET["api_date_end_D"]))			{$api_date_end_D=$_GET["api_date_end_D"];}
	elseif (isset($_POST["api_date_end_D"]))	{$api_date_end_D=$_POST["api_date_end_D"];}
if (isset($_GET["api_date_T"]))			{$api_date_T=$_GET["api_date_T"];}
	elseif (isset($_POST["api_date_T"]))	{$api_date_T=$_POST["api_date_T"];}
if (isset($_GET["api_date_end_T"]))			{$api_date_end_T=$_GET["api_date_end_T"];}
	elseif (isset($_POST["api_date_end_T"]))	{$api_date_end_T=$_POST["api_date_end_T"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["agent_users"]))					{$agent_users=$_GET["agent_users"];}
	elseif (isset($_POST["agent_users"]))			{$agent_users=$_POST["agent_users"];}
if (isset($_GET["functions"]))					{$functions=$_GET["functions"];}
	elseif (isset($_POST["functions"]))			{$functions=$_POST["functions"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["results"]))					{$results=$_GET["results"];}
	elseif (isset($_POST["results"]))			{$results=$_POST["results"];}
if (isset($_GET["order_by"]))					{$order_by=$_GET["order_by"];}
	elseif (isset($_POST["order_by"]))			{$order_by=$_POST["order_by"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["show_urls"]))			{$show_urls=$_GET["show_urls"];}
	elseif (isset($_POST["show_urls"]))	{$show_urls=$_POST["show_urls"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$report_name="API Log Report";
$NOW_DATE = date("Y-m-d");
if (!isset($users)) {$users=array();}
if (!isset($agent_users)) {$agent_users=array();}
if (!isset($functions)) {$functions=array();}
if (!isset($results)) {$results=array();}
if (!isset($api_date_D)) {$api_date_D=$NOW_DATE;}
if (!isset($api_date_end_D)) {$api_date_end_D=$NOW_DATE;}
if (!isset($api_date_T)) {$api_date_T="00:00:00";}
if (!isset($api_date_end_T)) {$api_date_end_T="23:59:59";}
if (!isset($order_by)) {$order_by="api_date";}
$api_date_from="$api_date_D $api_date_T";
$api_date_to="$api_date_end_D $api_date_end_T";
$CSV_text="\""._QXZ("API ACCESS LOG REPORT")."\"\n";
$CSV_text.="\""._QXZ("API DATE RANGE").":\",\"$api_date_from to $api_date_to\"\n";
$ASCII_rpt_header=_QXZ("API DATE RANGE").": $api_date_from to $api_date_to\n";


#if (!isset($recording_date_D)) {$recording_date_D=$NOW_DATE;}
#if (!isset($recording_date_end_D)) {$recording_date_end_D=$NOW_DATE;}
#if (!isset($recording_date_T)) {$recording_date_T="00:00:00";}
#if (!isset($recording_date_end_T)) {$recording_date_end_T="23:59:59";}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
if ($archive_tbl) {$agent_log_table="vicidial_api_log_archive";} else {$agent_log_table="vicidial_api_log";}
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
	}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_api_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_api_log_table=use_archive_table("vicidial_api_log");
	$vicidial_api_urls_table=use_archive_table("vicidial_api_urls");
	}
else
	{
	$vicidial_api_log_table="vicidial_api_log";
	$vicidial_api_urls_table="vicidial_api_urls";
	}
#############

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

$stmt="SELECT selected_language,modify_same_user_level,user_level,modify_users from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$VUmodify_same_user_level =	$row[1];
	$VUuser_level =				$row[2];
	$VUmodify_users =			$row[3];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
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
	if ($auth_message == 'IPBLOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Your IP Address is not allowed") . ": $ip";
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
$LOGbrowser=preg_replace("/\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/\'|\"|\\\\/","",$LOGhttp_referer);
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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
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

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}




$i=0;
$users_string='|';
$users_ct = count($users);
while($i < $users_ct)
	{
	$users_string .= "$users[$i]|";
	$i++;
	}
/*
$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $users_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$i++;
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
	# if (preg_match('/\-ALL/',$user_group_string)) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="select distinct user from vicidial_api_log where user!='' order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($row=mysqli_fetch_row($rslt))
	{
	$user_list[$i]=$row[0];
	$name_stmt="select full_name from vicidial_users where user='$row[0]'";
	$name_rslt=mysql_to_mysqli($name_stmt, $link);
	if (mysqli_num_rows($name_rslt)>0) 
		{
		$name_row=mysqli_fetch_row($name_rslt);
		$user_names[$i]=" - ".$name_row[0];
		}
	$i++;
	}
*/
$stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$user_list=array();
$user_names=array();
$user_directory=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	$user_directory[$row[0]]=" - ".$row[1];
	if ($all_users) {$user_list[$i]=$row[0];}
	$i++;
	}

$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
	$userQS .= "&users[]=$users[$i]";
	$user_SQL="and user in ('".implode("','", $users)."')";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$vu_user_SQL = ""; $vra_user_SQL = ""; $user_SQL="";}
else
	{
	$ASCII_rpt_header.=_QXZ("USERS").": ".implode(", ", $users)."\n";
	$CSV_text.="\""._QXZ("USERS").":\",\"".implode(", ", $users)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED USERS")."</th>";
	}

$i=0;
$agent_user_string='|';
$agent_user_ct = count($agent_users);
while($i < $agent_user_ct)
	{
	$agent_user_string .= "$agent_users[$i]|";
	$agent_userQS .= "&agent_users[]=$agent_users[$i]";
	$agent_user_SQL="and agent_user in ('".implode("','", $agent_users)."')";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$agent_user_string) ) or ($agent_user_ct < 1) )
	{$vu_agent_user_SQL = ""; $vra_agent_user_SQL = ""; $agent_user_SQL="";}
else
	{
	$ASCII_rpt_header.=_QXZ("AGENT USERS").": ".implode(", ", $agent_users)."\n";
	$CSV_text.="\""._QXZ("AGENT USERS").":\",\"".implode(", ", $agent_users)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED AGENTS")."</th>";
	}

$i=0;
$function_string='|';
$function_ct = count($functions);
while($i < $function_ct)
	{
	$function_string .= "$functions[$i]|";
	$functionQS .= "&functions[]=$functions[$i]";
	$function_SQL="and function in ('".implode("','", $functions)."')";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$function_string) ) or ($function_ct < 1) )
	{$vu_function_SQL = ""; $vra_function_SQL = ""; $function_SQL="";}
else
	{
	$ASCII_rpt_header.=_QXZ("FUNCTIONS").": ".implode(", ", $functions)."\n";
	$CSV_text.="\""._QXZ("FUNCTIONS").":\",\"".implode(", ", $functions)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED FUNCTIONS")."</th>";
	}


$i=0;
$result_string='|';
$result_ct = count($results);
while($i < $result_ct)
	{
	$result_string .= "$results[$i]|";
	$resultQS .= "&results[]=$results[$i]";
	$result_SQL="and result in ('".implode("','", $results)."')";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$result_string) ) or ($result_ct < 1) )
	{$vu_result_SQL = ""; $vra_result_SQL = ""; $result_SQL="";}
else
	{
	$ASCII_rpt_header.=_QXZ("RESULTS").": ".implode(", ", $results)."\n";
	$CSV_text.="\""._QXZ("RESULTS").":\",\"".implode(", ", $results)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED RESULTS")."</th>";
	}

/*
$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$user_group_SQL=" and user_group in ('".implode("','", $user_group)."') and vra.user=vu.user and ";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$ASCII_rpt_header.="   User groups: ".implode(", ", $user_group)."\n";
	$CSV_text.="\"User groups:\",\"".implode(", ", $user_group)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>SELECTED USER GROUPS</th>";
	}
*/

$stmt="select distinct user from $vicidial_api_log_table where user!='' order by user";
if ($DB) {echo "$stmt<BR>\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$users_array=array();
$user_names_array=array();
while ($row=mysqli_fetch_array($rslt)) {
	array_push($users_array, $row["user"]);
	array_push($user_names_array, $user_directory[$row["user"]]);
}

$stmt="select distinct function from $vicidial_api_log_table where function!='' order by function";
if ($DB) {echo "$stmt<BR>\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$functions_array=array();
while ($row=mysqli_fetch_array($rslt)) {
	array_push($functions_array, $row["function"]);
}

$stmt="select distinct agent_user from $vicidial_api_log_table where agent_user!='' order by agent_user";
if ($DB) {echo "$stmt<BR>\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$agent_users_array=array();
$agent_user_names_array=array();
while ($row=mysqli_fetch_array($rslt)) {
	array_push($agent_users_array, $row["agent_user"]);
	array_push($agent_user_names_array, $user_directory[$row["agent_user"]]);
}

$stmt="select distinct result from $vicidial_api_log_table where result!='' order by result";
if ($DB) {echo "$stmt<BR>\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$results_array=array();
while ($row=mysqli_fetch_array($rslt)) {
	array_push($results_array, $row["result"]);
}

##### BEGIN Define colors and logo #####
$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='FFFFFF';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

$screen_color_stmt="select admin_screen_colors from system_settings";
$screen_color_rslt=mysql_to_mysqli($screen_color_stmt, $link);
$screen_color_row=mysqli_fetch_row($screen_color_rslt);
$agent_screen_colors="$screen_color_row[0]";

if ($agent_screen_colors != 'default')
	{
	$asc_stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$agent_screen_colors';";
	$asc_rslt=mysql_to_mysqli($asc_stmt, $link);
	$qm_conf_ct = mysqli_num_rows($asc_rslt);
	if ($qm_conf_ct > 0)
		{
		$asc_row=mysqli_fetch_row($asc_rslt);
		$SSmenu_background =            $asc_row[0];
		$SSframe_background =           $asc_row[1];
		$SSstd_row1_background =        $asc_row[2];
		$SSstd_row2_background =        $asc_row[3];
		$SSstd_row3_background =        $asc_row[4];
		$SSstd_row4_background =        $asc_row[5];
		$SSstd_row5_background =        $asc_row[6];
		$SSalt_row1_background =        $asc_row[7];
		$SSalt_row2_background =        $asc_row[8];
		$SSalt_row3_background =        $asc_row[9];
		$SSweb_logo =		           $asc_row[10];
		}
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>\n";}

$LINKbase = "$PHP_SELF?DB=$DB&api_date_D=$api_date_D&api_date_T=$api_date_T&api_date_end_D=$api_date_end_D&api_date_end_T=$api_date_end_T$resultQS$functionQS$agent_userQS$userQS&shift=$shift&DB=$DB&show_percentages=$show_percentages&show_urls=$show_urls&search_archived_data=$search_archived_data&SUBMIT=1";

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


$HTML_head.="<HTML>\n";
$HTML_head.="<HEAD>\n";
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";


$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="	{\n";
$HTML_text.="	window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="	}\n";
$HTML_text.="function ToggleSpan(spanID) {\n";
$HTML_text.="  if (document.getElementById(spanID).style.display == 'none') {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'block';\n";
$HTML_text.="  } else {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'none';\n";
$HTML_text.="  }\n";
$HTML_text.=" }\n";
$HTML_text.="</script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<B>"._QXZ("$report_name")."</B> $NWB#api_log_report$NWE<BR>";
$HTML_text.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3><TR><TD VALIGN=TOP> "._QXZ("Access date range").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=api_date ID=api_date VALUE=\"$api_date\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=api_date_end ID=api_date_end VALUE=\"$api_date_end\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=api_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$api_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'api_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=api_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$api_date_T\">";
$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=api_date_end_D SIZE=11 MAXLENGTH=10 VALUE=\"$api_date_end_D\">";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'api_date_end_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=api_date_end_T SIZE=9 MAXLENGTH=8 VALUE=\"$api_date_end_T\">";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=users[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
for ($o=0; $o<count($users_array); $o++)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string)) {$HTML_text.="<option selected value=\"$users_array[$o]\">$users_array[$o]$user_names_array[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$users_array[$o]\">$users_array[$o]$user_names_array[$o]</option>\n";}
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Agent users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=agent_users[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$agent_user_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL AGENTS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL AGENTS")." --</option>\n";}

for ($o=0; $o<count($agent_users_array); $o++)
	{
	if  (preg_match("/$agent_users_array[$o]\|/i",$agent_user_string)) {$HTML_text.="<option selected value=\"$agent_users_array[$o]\">$agent_users_array[$o]$agent_user_names_array[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$agent_users_array[$o]\">$agent_users_array[$o]$agent_user_names_array[$o]</option>\n";}
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Functions").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=functions[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$function_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL FUNCTIONS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL FUNCTIONS")." --</option>\n";}
for ($o=0; $o<count($functions_array); $o++)
	{
	if  (preg_match("/$functions_array[$o]\|/i",$function_string)) {$HTML_text.="<option selected value=\"$functions_array[$o]\">"._QXZ("$functions_array[$o]")."</option>\n";}
	  else {$HTML_text.="<option value=\"$functions_array[$o]\">"._QXZ("$functions_array[$o]")."</option>\n";}
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Results").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=results[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$result_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL RESULTS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL RESULTS")." --</option>\n";}
$o=0;
for ($o=0; $o<count($results_array); $o++)
	{
	if  (preg_match("/$results_array[$o]\|/i",$result_string)) {$HTML_text.="<option selected value=\"$results_array[$o]\">"._QXZ("$results_array[$o]")."</option>\n";}
	  else {$HTML_text.="<option value=\"$results_array[$o]\">"._QXZ("$results_array[$o]")."</option>\n";}
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN='TOP'>";
$HTML_text.=_QXZ("Display as:")."<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
$HTML_text.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="<BR><BR><input type='checkbox' name='show_urls' value='checked' $show_urls>"._QXZ("Show URLs, slower")."\n";
if ($archives_available=="Y") 
	{
	$HTML_text.="<BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$HTML_text.="</TD>";
$HTML_text.="</TR></TABLE>";
$HTML_text.="</FORM>\n\n";

$TEXT.="<PRE><FONT SIZE=2>\n";

if ($SUBMIT && $api_date_D && $api_date_T && $api_date_end_D && $api_date_end_T) 
	{
	$stmt="select api_id,user,api_date,api_script,function,agent_user,value,result,result_reason,source,data,run_time,webserver,api_url from $vicidial_api_log_table where api_date>='$api_date_D $api_date_T' and api_date<='$api_date_end_D $api_date_end_T' $result_SQL $function_SQL $agent_user_SQL $user_SQL order by $order_by asc";
	if ($DB) {print $stmt."\n";}
	# print $stmt."\n";

	$URL_header='';
	$ASCII_border="+---------------------+--------------------------------+------------+----------------------+--------------------------------+------------+----------------------+----------+-----------+----------------------+-----------+\n";
	if ($show_urls)
		{
		$ASCII_border="+---------------------+--------------------------------+------------+----------------------+--------------------------------+------------+----------------------+----------+-----------+----------------------+-----------+";

		$container_id='API_LOG_URL_COLUMNS';
		$container_stmt="select container_entry from vicidial_settings_containers where container_id='$container_id'";
		$container_rslt=mysql_to_mysqli($container_stmt, $link);
		
		if (mysqli_num_rows($container_rslt)>0) 
			{
			$container_row=mysqli_fetch_row($container_rslt);
			$url_vars=explode("\r\n", $container_row[0]);
			$URL_header="";
			for($u=0; $u<count($url_vars); $u++) 
				{
				$URL_header.=sprintf("%-20s", $url_vars[$u])." | ";
				$ASCII_border.="----------------------+";
				}
			
			}
		$URL_header.=" "._QXZ("REMOTE IP", 19)." |    "._QXZ("URL");
		$ASCII_border.="----------------------+----------\n";
		}

	$TEXT.=$ASCII_rpt_header;
	$TEXT.=sprintf("%110s", " ");
	$TEXT.="<a href=\"$PHP_SELF?api_date_D=$api_date_D&api_date_T=$api_date_T&api_date_end_D=$api_date_end_D&api_date_end_T=$api_date_end_T$resultQS$functionQS$agent_userQS$userQS&file_download=1&show_urls=$show_urls&search_archived_data=$search_archived_data&SUBMIT=$SUBMIT\">"._QXZ("DOWNLOAD")."</a>\n";
	$TEXT.=$ASCII_border;
	$TEXT.="| <a href='$LINKbase&order_by=api_date'>"._QXZ("API DATE", 19)."</a> | <a href='$LINKbase&order_by=user'>"._QXZ("USER", 30)."</a> | <a href='$LINKbase&order_by=api_script'>"._QXZ("API SCRIPT", 10)."</a> | <a href='$LINKbase&order_by=function'>"._QXZ("FUNCTION", 20)."</a> | <a href='$LINKbase&order_by=agent_user'>"._QXZ("AGENT USER", 30)."</a> | <a href='$LINKbase&order_by=result'>"._QXZ("RESULT", 10)."</a> | <a href='$LINKbase&order_by=source'>"._QXZ("SOURCE", 20)."</a> | <a href='$LINKbase&order_by=run_time'>"._QXZ("RUN TIME", 8)."</a> | <a href='$LINKbase&order_by=webserver'>"._QXZ("WEBSERVER", 9)."</a> | <a href='$LINKbase&order_by=data'>"._QXZ("DATA", 20)."</a> | <a href='$LINKbase&order_by=api_url'>"._QXZ("API URL", 9)."</a> | $URL_header\n";
	$TEXT.=$ASCII_border;

	$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
	$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML.="<th colspan='".($show_urls ? (11+count($url_vars)) : "10")."'><font size='2'>$ASCII_rpt_header</font></th>";
	$HTML.="<th><font size='2'><a href=\"$PHP_SELF?api_date_D=$api_date_D&api_date_T=$api_date_T&api_date_end_D=$api_date_end_D&api_date_end_T=$api_date_end_T$resultQS$functionQS$agent_userQS$userQS&file_download=1&show_urls=$show_urls&search_archived_data=$search_archived_data&SUBMIT=$SUBMIT\">"._QXZ("DOWNLOAD")."</a></font></th>";
	$HTML.="</tr>\n";
	$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML.="<th><font size='2'>"._QXZ("API DATE")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("USER")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("API SCRIPT")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("FUNCTION")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("AGENT USER")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("RESULT")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("SOURCE")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("RUN TIME")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("WEBSERVER")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("DATA")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("API URL")."</font></th>";


	if ($show_urls)
		{
		$CSV_text.="\n\""._QXZ("API DATE")."\",\""._QXZ("USER")."\",\""._QXZ("API SCRIPT")."\",\""._QXZ("FUNCTION")."\",\""._QXZ("AGENT USER")."\",\""._QXZ("VALUE")."\",\""._QXZ("RESULT")."\",\""._QXZ("RESULT REASON")."\",\""._QXZ("SOURCE")."\",\""._QXZ("DATA")."\",\""._QXZ("RUN TIME")."\",\""._QXZ("WEBSERVER")."\",\""._QXZ("API URL")."\"";

		for($u=0; $u<count($url_vars); $u++) 
			{
			$CSV_text.=",\"".$url_vars[$u]."\"";
			$HTML.="<th><font size='2'>"._QXZ("$url_vars[$u]")."</font></th>";
			}

		$CSV_text.=",\""._QXZ("REMOTE IP")."\",\""._QXZ("FULL URL STRING")."\"\n";
		$HTML.="<th><font size='2'>"._QXZ("REMOTE IP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("FULL URL STRING")."</font></th>";
		}
	else
		{
		$CSV_text.="\n\""._QXZ("API DATE")."\",\""._QXZ("USER")."\",\""._QXZ("API SCRIPT")."\",\""._QXZ("FUNCTION")."\",\""._QXZ("AGENT USER")."\",\""._QXZ("VALUE")."\",\""._QXZ("RESULT")."\",\""._QXZ("RESULT REASON")."\",\""._QXZ("SOURCE")."\",\""._QXZ("DATA")."\",\""._QXZ("RUN TIME")."\",\""._QXZ("WEBSERVER")."\",\""._QXZ("API URL")."\"\n";
		}

	$HTML.="</tr>\n";

	$rslt=mysql_to_mysqli($stmt, $link);
	$g=0;
	$full_user=array();
	$full_agent_user=array();
	$api_id=array();
	$api_date=array();
	$api_script=array();
	$function=array();
	$result=array();
	$source=array();
	$run_time=array();
	$webserver=array();
	$api_url=array();
	$value=array();
	$result_reason=array();
	$data=array();
	while ($row=mysqli_fetch_array($rslt)) 
		{
		$full_user[$g] =		substr($row["user"].$user_directory[$row["user"]], 0, 30);
		$full_agent_user[$g] =	substr($row["agent_user"].$user_directory[$row["agent_user"]], 0, 30);
		$api_id[$g] =			$row["api_id"];
		$api_date[$g] =			sprintf("%-19s", $row["api_date"]);
		$api_script[$g] =		sprintf("%-10s", _QXZ($row["api_script"], 10));
		$function[$g] =			sprintf("%-20s", _QXZ($row["function"], 20));
		$result[$g] =			sprintf("%-10s", _QXZ($row["result"], 10));
		$source[$g] =			sprintf("%-20s", _QXZ($row["source"], 20));
		$run_time[$g] =			sprintf("%-8s", substr($row["run_time"],0,8));
		$webserver[$g] =		sprintf("%-9s", $row["webserver"]);
		$api_url[$g] =			sprintf("%-9s", $row["api_url"]);
		$value[$g] =			$row["value"];
		$result_reason[$g] =	$row["result_reason"];
		$data[$g] =				$row["data"];
		$g++;
		}

	$h=0;
	while ($h < $g) 
		{
		$TEXT.="| ";
		$TEXT.=sprintf("%-19s", $api_date[$h])." | ";
		$TEXT.=sprintf("%-30s", $full_user[$h])." | ";
		$TEXT.=sprintf("%-10s", $api_script[$h])." | ";
		$TEXT.=sprintf("%-20s", $function[$h])." | ";
		$TEXT.=sprintf("%-30s", $full_agent_user[$h])." | ";
		$TEXT.=sprintf("%-10s", $result[$h])." | ";
		$TEXT.=sprintf("%-20s", $source[$h])." | ";
		$TEXT.=sprintf("%-8s", $run_time[$h])." | ";
		$TEXT.=sprintf("%-9s", $webserver[$h])." | ";
		$TEXT.=sprintf("%-20s", substr($data[$h],0,20))." | ";
		$TEXT.=sprintf("%-9s", $api_url[$h])." | ";

		$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
		$HTML.="<th><font size='2'>".$api_date[$h]."</font></th>";
		$HTML.="<th><font size='2'>".$full_user[$h]."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("$api_script[$h]")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("$function[$h]")."</font></th>";
		$HTML.="<th><font size='2'>".$full_agent_user[$h]."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("$result[$h]")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("$source[$h]")."</font></th>";
		$HTML.="<th><font size='2'>".$run_time[$h]."</font></th>";
		$HTML.="<th><font size='2'>".$webserver[$h]."</font></th>";
		$HTML.="<th><font size='2'>".sprintf("%-20s", substr($data[$h],0,20))."</font></th>";
		$HTML.="<th><font size='2'>".$api_url[$h]."</font></th>";

		if ($show_urls)
			{
			$stmt="select remote_ip,url from $vicidial_api_urls_table where api_id='$api_id[$h]';";
			if ($DB) {print $stmt."\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if (mysqli_num_rows($rslt)>0) 
				{
				while ($row=mysqli_fetch_array($rslt)) 
					{
					$full_url = $row["url"];
					$remote_ip = $row["remote_ip"];
					$key_array=array();
					$val_array=array();
					$CSV_vals="";
					
					$url_ary=preg_split("/\?/", $full_url);
					for($u=2; $u<count($url_ary); $u++)
						{
						$url_ary[1].="?".$url_ary[$u];
						}
					$var_ary=preg_split("/\&/", $url_ary[1]);
					for ($v=0; $v<count($var_ary); $v++)
						{
						$keyval_pair=preg_split("/\=/", $var_ary[$v]);
						array_push($key_array, strtolower($keyval_pair[0]));
						array_push($val_array, substr(urldecode($keyval_pair[1]), 0, 20));
						}

					for ($v=0; $v<count($url_vars); $v++)
						{
						$val="";
						$key=array_search(strtolower($url_vars[$v]), $key_array, false);
						if($key) {$val=$val_array[$key];}
						$TEXT.=sprintf("%-20s", substr($val, 0, 20))." | ";
						$CSV_vals.=",\"$val\"";
						$HTML.="<th><font size='2'>".($val=="" ? "&nbsp;" : $val)."</font></th>";
						}


					if ( ($VUmodify_same_user_level < 1) or ($VUuser_level < 9) or ($VUmodify_users < 1) )
						{$full_url = preg_replace("/&pass=[\s\S]+?&/",'&pass=XXXX&',$full_url);}
					$TEXT.=sprintf("%-20s", $row["remote_ip"])." | ";
					$TEXT.=sprintf("%-2000s", $full_url)." | ";
					}
				} 
			else
				{
				$CSV_vals="";
				for ($v=0; $v<count($url_vars); $v++)
					{
					$val="";
					$TEXT.=sprintf("%-20s", $val)." | ";
					$CSV_vals.=",\"$val\"";
					$HTML.="<th><font size='2'>".($val=="" ? "&nbsp;" : $val)."</font></th>";
					}
				$TEXT.=sprintf("%-20s", "")." | ";
				$TEXT.=sprintf("%-2000s", "")." | ";
				}
				$CSV_text.="\"$api_date[$h]\",\"$full_user[$h]\",\"$api_script[$h]\",\"$function[$h]\",\"$full_agent_user[$h]\",\"$value[$h]\",\"$result[$h]\",\"$result_reason[$h]\",\"$source[$h]\",\"$data[$h]\",\"$run_time[$h]\",\"$webserver[$h]\",\"$api_url[$h]\"$CSV_vals";
				
				$CSV_text.=",\"$remote_ip\",\"$full_url\"\n";
				$HTML.="<th><font size='2'>".($remote_ip=="" ? "&nbsp;" : $remote_ip)."</font></th>";
				$HTML.="<th><font size='2'>".($full_url=="" ? "&nbsp;" : $full_url)."</font></th>";
			}
		else
			{
			$CSV_text.="\"$api_date[$h]\",\"$full_user[$h]\",\"$api_script[$h]\",\"$function[$h]\",\"$full_agent_user[$h]\",\"$value[$h]\",\"$result[$h]\",\"$result_reason[$h]\",\"$source[$h]\",\"$data[$h]\",\"$run_time[$h]\",\"$webserver[$h]\",\"$api_url[$h]\"\n";
			}
		$TEXT.="\n";
		$HTML.="</tr>";
		$h++;
		}

	$TEXT.=$ASCII_border;
	$HTML.="</table>";
	}
$TEXT.="</FONT></PRE>";

if ($report_display_type=="HTML") {
	$HTML_text.=$HTML;
} else {
	$HTML_text.=$TEXT;
}


$HTML_text.="</BODY></HTML>";


if ($file_download == 0 || !$file_download) 
	{
	echo $HTML_head;
	require("admin_header.php");
	echo $HTML_text;
	}
else
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_recording_access_log_report_$US$FILE_TIME.csv";
	$CSV_text=preg_replace('/\n +,/', ',', $CSV_text);
	$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
	$CSV_text=preg_replace('/\" +/', '"', $CSV_text);

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');
	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$CSV_text";
	}


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
