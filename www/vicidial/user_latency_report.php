<?php 
# user_latency_report.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
#
# CHANGES
# 230421-0843 - First build
# 230422-0820 - Header fixes and no-records output
# 230508-0247 - Graph links added
#

$startMS = microtime();

$report_name='User Latency Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$last_midnight = date("Y-m-d 00:00:00");
$STARTtime = date("U");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$user_territories_active =		$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSallow_shared_dial =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	}

$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign debugging").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

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

$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="select user,full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$users[$i] =$row[0];
	$full_name[$i] =$row[1];
	$i++;
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>
<script type="text/javascript" src="dygraph.js"></script>
<link rel="stylesheet" type="text/css" href="dygraph.css" />
<script language="Javascript">
var RawGraphData="";
function DrawGraph(user, LogDate, IPAddress, API_action)
	{
	document.getElementById('loading_please_wait').style.display='block';
	document.getElementById('chart_div').style.display='none';

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	try {
	xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
		xmlhttp = false;
		}
	}
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		var GraphQuery = "user=" + user + "&log_date=" + LogDate + "&web_ip=" + IPAddress + "&ACTION="+API_action;
		xmlhttp.open('POST', 'dygraph_functions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(GraphQuery); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				RawGraphData = xmlhttp.responseText;

				if (API_action=="all_agent_latency")
					{
					var LSL_value=1;
					var stacked=1;
					}
				else
					{
					var LSL_value=0;
					var stacked=0;
					}

				var graphTitle='Latency';
				if (!user.match("---ALL---"))
					{
					graphTitle+=' for agent '+user;
					}
				else
					{
					graphTitle+=' for ALL agents';
					}

				graphTitle+=' for '+LogDate;

				if (!IPAddress.match("---ALL---"))
					{
					graphTitle+=' on IP address '+IPAddress;
					}
				//var graph_data=[];
				//var maxLatency=0;
				//var graph_array=RawGraphData.split("|");
				//alert(graph_array.length);
				//for (var i=0; i<graph_array.length; i++)
				//	{
				//	dataset=graph_array[i].split(",");
				//	if (i<5) {alert(dataset[0]);}
				//	graph_data.push([dataset[0], parseInt(dataset[1])]);
				//	if (parseInt(dataset[1])>maxLatency)
				//		{
				//		maxLatency=parseInt(dataset[1]);
				//		}
				//	}
				if (!LatencyGraph)
					{
					// alert(RawGraphData);
					var LatencyGraph=new Dygraph(document.getElementById("latency_graph_div"), RawGraphData,
						{
						title: graphTitle,
						labelsDiv: document.getElementById("legend_div"),
						drawPoints: true,
						showRoller: false,
						resizable: "both",
						// legend: 'follow',
						// legendFollowOffsetX: 5,
						// legendFollowOffsetY: -5,
						labelsSeparateLines: LSL_value,
						connectSeparatedPoints: false,
						drawGapEdgePoints: false,
						ylabel: 'Latency',
						xlabel: 'Time (HH:mm:ss)',
						strokeWidth: 1.5
						} );
					}
				else
					{
					LatencyGraph.updateOptions( {'file': RawGraphData, title: graphTitle} );
					}
				
				document.getElementById('loading_please_wait').style.display='none';
				document.getElementById('chart_div').style.display='block';
				}
			}
		}



	}
</script>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

	require("admin_header.php");

echo "<b>"._QXZ("$report_name")."</b> $NWB#user_latency_report$NWE\n";

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=user>\n";
echo "<option ";
if ($user == '--ACTIVE-USERS-TODAY--') {echo "selected ";}
echo "value='--ACTIVE-USERS-TODAY--'>"._QXZ("--ACTIVE-USERS-TODAY--")."</option>\n";
$o=0;
while ($campaigns_to_print > $o)
	{
	if ($users[$o] == $user) {echo "<option selected value=\"$users[$o]\">$users[$o] - $full_name[$o]</option>\n";}
	else {echo "<option value=\"$users[$o]\">$users[$o] - $full_name[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
if ( ($user != '--MOST-RECENT-ACTIVE-ARCHIVE--') and ($user != '--ACTIVE-USERS-TODAY--') )
	{echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("MODIFY")."</a> \n";}
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$user)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT A USER ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$LATheaders = _QXZ("User Latency Summary").": ("._QXZ("in milliseconds").")\n";
	$LATheaders .= "| "._QXZ("USER", 20)." ! "._QXZ("DATE/TIME", 19)." ! "._QXZ("LAST WEB IP", 20)." ! "._QXZ("LATENCY NOW", 11)."! "._QXZ("1 min Avg", 10)." ! "._QXZ("1 min Peak", 10)." ! "._QXZ("1 hr Avg", 10)." ! "._QXZ("1 hr Peak", 10)." | "._QXZ("day Avg", 10)." ! "._QXZ("day Peak", 10)." |\n";
	$LATheaders .= "+----------------------+---------------------+----------------------+------------+------------+------------+------------+------------+------------+------------+\n";

	$multi_user=0;
	$latencies_to_print=0;
	$Hlatencies_to_print=0;
	$stmt="SELECT vlad.user,vlad.update_date,vlad.web_ip,vlad.latency,vlad.latency_min_avg,vlad.latency_min_peak,vlad.latency_hour_avg,vlad.latency_hour_peak,vlad.latency_today_avg,vlad.latency_today_peak from vicidial_live_agents_details vlad where vlad.user='" . mysqli_real_escape_string($link, $user) . "' $LOGadmin_viewable_groupsSQL order by user limit 1000;";
	if ($user == '--ACTIVE-USERS-TODAY--')
		{
		$multi_user=1;
		$stmt="SELECT vlad.user,vlad.update_date,vlad.web_ip,vlad.latency,vlad.latency_min_avg,vlad.latency_min_peak,vlad.latency_hour_avg,vlad.latency_hour_peak,vlad.latency_today_avg,vlad.latency_today_peak from vicidial_live_agents_details vlad where user!='' $LOGadmin_viewable_groupsSQL order by user limit 1000;";
		}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$latencies_to_print = mysqli_num_rows($rslt);
	if ($latencies_to_print > 0)
		{echo $LATheaders;}

	$i=0;   $archive_output='';
	while ($latencies_to_print > $i)
		{
		$row=mysqli_fetch_row($rslt);

		$archive_output .= "| ".sprintf("%-20s", $row[0])." |";
		$archive_output .= " ".sprintf("%-19s", $row[1])." |";
		$archive_output .= " <a href=\"javascript:void(0)\" onClick=\"DrawGraph('$row[0]', '".substr($row[1], 0, 10)."', '---ALL---', 'latency_by_user')\">".sprintf("%-20s", $row[2])."</a> |";
		$archive_output .= " ".sprintf("%-10s", $row[3])." |";
		$archive_output .= " ".sprintf("%-10s", $row[4])." |";
		$archive_output .= " ".sprintf("%-10s", $row[5])." |";
		$archive_output .= " ".sprintf("%-10s", $row[6])." |";
		$archive_output .= " ".sprintf("%-10s", $row[7])." |";
		$archive_output .= " ".sprintf("%-10s", $row[8])." |";
		$archive_output .= " ".sprintf("%-10s", $row[9])." |\n";

		$i++;
		}

	echo $archive_output;

	if ($multi_user < 1)
		{
		$stmt="select user,log_date,web_ip,latency_avg,latency_peak,if(date(log_date)>=date(now()-INTERVAL 7 DAY), 1, 0) as show_link from vicidial_agent_latency_summary_log where user='" . mysqli_real_escape_string($link, $user) . "' $LOGadmin_viewable_groupsSQL order by log_date desc,web_ip limit 1000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$Hlatencies_to_print = mysqli_num_rows($rslt);
		$i=0;
		if ( ($latencies_to_print < 1) and ($Hlatencies_to_print > 0) )
			{echo $LATheaders;}
		while ($Hlatencies_to_print > $i)
			{
			$row=mysqli_fetch_row($rslt);

			echo "| ".sprintf("%-20s", $row[0])." |";
			echo " ".sprintf("%-19s", $row[1])." |";
			if ($row[5])
				{
				echo " <a href=\"javascript:void(0)\" onClick=\"DrawGraph('$row[0]', '".substr($row[1], 0, 10)."', '$row[2]', 'latency_by_user')\">".sprintf("%-20s", $row[2])."</a> |";
				}
			else
				{
				echo " ".sprintf("%-20s", $row[2])." |";
				}
			echo " ".sprintf("%-10s", ' ')." |";
			echo " ".sprintf("%-10s", ' ')." |";
			echo " ".sprintf("%-10s", ' ')." |";
			echo " ".sprintf("%-10s", ' ')." |";
			echo " ".sprintf("%-10s", ' ')." |";
			echo " ".sprintf("%-10s", $row[3])." |";
			echo " ".sprintf("%-10s", $row[4])." |\n";

			$i++;
			}
		}
	else
		{
		echo "+----------------------+---------------------+----------------------+------------+------------+------------+------------+------------+------------+------------+\n";
		echo "| GRAPH AGENTS BY DATE:  <a href=\"javascript:void(0)\" onClick=\"DrawGraph('---ALL---', '".date("Y-m-d")."', '---ALL---', 'all_agent_latency')\">[ ".date("Y-m-d")." ]</a>";
		$date_stmt="select distinct date(log_date) as ldate from vicidial_agent_latency_log_archive order by ldate desc";
		$date_rslt=mysql_to_mysqli($date_stmt, $link);
		$col_width=119;
		while ($date_row=mysqli_fetch_row($date_rslt))
			{
			echo "   <a href=\"javascript:void(0)\" onClick=\"DrawGraph('---ALL---', '".$date_row[0]."', '---ALL---', 'all_agent_latency')\">[ $date_row[0] ]</a>";
			$col_width-=17;
			}
		echo sprintf("%-".$col_width."s", ' ')."|\n";
		echo "+----------------------+---------------------+----------------------+------------+------------+------------+------------+------------+------------+------------+\n";
		}
	if ( ($latencies_to_print < 1) and ($Hlatencies_to_print < 1) )
		{echo _QXZ("No records to report");}
	else
		{
		}

	echo "\n";
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

</PRE>

<table>
<tr><td>
<style>
.dygraph-legend { 
	font-family: Arial, Helvetica, serif;
	font-size: 10pt;
	font-weight: bold;
	}
.dygraph-title {
	font-family: Arial, Helvetica, serif;
	color: #000;
	text-shadow: #999 2px 2px 2px; 
}
dygraph-axis-label {
	font-family: Arial, Helvetica, serif;
	font-size: 10pt;
	color: #900;
	text-shadow: #999 1px 1px 1px; 
}
.dygraph-axis-label-x {
	font-family: Arial, Helvetica, serif;
	font-size: 10pt;
	color: #900;
	text-shadow: #999 1px 1px 1px; 
}

.dygraph-axis-label-y {
	font-family: Arial, Helvetica, serif;
	font-size: 10pt;
	color: #900;
	text-shadow: #999 1px 1px 1px; 
}
.dygraph-xlabel {
	font-family: Arial, Helvetica, serif;
	font-size: 12pt;
	font-weight: bold;
	text-shadow: #999 1px 1px 1px; 
}	

.dygraph-ylabel {
	font-family: Arial, Helvetica, serif;
	font-size: 12pt;
	font-weight: bold;
	text-shadow: #999 1px 1px 1px; 
}

.loading_text {
	font-family: Arial, Helvetica, serif;
	font-size: 36pt;
	font-weight: bold;
	color: #009;
	text-shadow: #999 5px 5px 5px; 
}

</style>
<div class='loading_text' id='loading_please_wait' style="width:800px; height:400px; display:none;">
<center>LOADING, PLEASE WAIT...</center> 
</div>
<div id="chart_div" class="chart" style="width:800px; height:400px;">
<div id='latency_graph_div' style="width:800px;height:400px"></div>
</div>
</td>

<td align='left' valign='top'>
<div id='legend_div' class='dygraph-legend' style="width:200px;height:300px"></div>
</TD>

</TR></TABLE>

</BODY></HTML>
