<?php 
# AST_LAGGED_log_report_summary.php
# 
# Copyright (C) 2023  Mike Coate, Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 231201-1216 - First build, Forked from AST_LAGGED_log_report.php, added summary graph listing
# 231201-2236 - Added onClick event to run selected date in AST_LAGGED_log_report.php
# 231206-0959 - Added agent computer IP breakdown
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");
include("graphcanvas.inc");
include("graph_color_schemas.inc"); 

$report_name='LAGGED Agent Log Summary Report';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_end"]))			{$query_date_end=$_GET["query_date_end"];}
	elseif (isset($_POST["query_date_end"])){$query_date_end=$_POST["query_date_end"];}	
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($query_date_end)) {$query_date_end = $NOW_DATE;}
if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
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
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$query_date_end = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date_end);
$lower_limit = preg_replace('/[^-_0-9a-zA-Z]/', '', $lower_limit);
$upper_limit = preg_replace('/[^-_0-9a-zA-Z]/', '', $upper_limit);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
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
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $lower_limit, $upper_limit, $file_download|', url='$LOGfull_url', webserver='$webserver_id';";
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


$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="   .small_standard {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt}\n";
$HEADER.="   .small_standard_bold {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt; font-weight: bold}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<script src='chart/Chart.js'>Chart.defaults.global.defaultFontSize = 10;</script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 cellspacing=5 cellpadding=5><TR><TD VALIGN=TOP align=center>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Date Begin").":\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script></TD>\n";
$MAIN.="<TD VALIGN=TOP align=center>"._QXZ("Date End").":\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date_end SIZE=10 MAXLENGTH=10 VALUE=\"$query_date_end\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date_end'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script></TD>\n";
$MAIN.="<TD VALIGN=TOP align=center><INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</TD><TD><a href='AST_LAGGED_log_report.php'>"._QXZ("SINGLE DAY")."</a>";
$MAIN.="</TD></TR></TABLE>\n";	

if ($SUBMIT && $query_date && $query_date_end)
	{
	$stmt="SELECT server_ip, count(*) as ct From vicidial_agent_log where event_time>='$query_date 00:00:00' and event_time<='$query_date_end 23:59:59' and sub_status='LAGGED' group by server_ip order by server_ip";
	$rslt=mysql_to_mysqli($stmt, $link);
	$HTML_text="";
	if ($DB) {$ASCII_text.=$stmt."\n";}

	if (mysqli_num_rows($rslt)>0)
		{
		
		// Server breakdown
		$HTML_text.="<table border='0' cellpadding='0' cellspacing='2' width='350'>";
		$HTML_text.="<TR><TH colspan='2' class='small_standard_bold grey_graph_cell'>"._QXZ("SERVER IP BREAKDOWN FOR LAGGED RECORDS <br>")." $query_date "._QXZ("TO")." $query_date_end</TH></TR>";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("SERVER IP")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("COUNT")."</th></tr>";
		$total_count=0;
		while ($row=mysqli_fetch_array($rslt))
			{
			$HTML_text.="<TR><TD class='small_standard'>$row[server_ip]</td><TD class='small_standard'>$row[ct]</td></tr>";
			$total_count+=$row["ct"];
			}
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("TOTAL")."</th><TH class='small_standard_bold grey_graph_cell'>$total_count</th></tr></table>";

		// Summary graph	
		$i = 0;
		$original_date = $query_date;
		while ($query_date <= $query_date_end)
			{
			$sql_date = $query_date;
			$rpt_stmt="SELECT count(*) as lag_count from vicidial_agent_log where sub_status='LAGGED' and event_time>='$sql_date 00:00:00' and event_time<='$sql_date 23:59:59'";
			$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
			$row=mysqli_fetch_array($rpt_rslt);
			if ($DB) {$ASCII_text.=$rpt_stmt."\n";}
			
			$daily_counts.= "\"$row[lag_count]\",";
			$axis_labels.= "\"$query_date\",";
			$bgcolor.= "\"" . $backgroundColor[($i%count($backgroundColor))] . "\",";
			$hbgcolor.= "\"" . $hoverBackgroundColor[($i%count($hoverBackgroundColor))] . "\",";
			$hbcolor.= "\"" . $hoverBorderColor[($i%count($hoverBorderColor))] . "\",";			
			
			$query_date = date('Y-m-d', strtotime($query_date . '+ 1 day'));
			$i++;
			}
		$query_date = $original_date;
		$daily_counts = chop($daily_counts, ",");
		$axis_labels = chop($axis_labels, ",");
		$bgcolor = chop($bgcolor, ",");
		$hbgcolor = chop($hbgcolor, ",");
		$hbcolor = chop($hbcolor, ",");
		
		$HTML_text.="
			<br><table width='600'>
				<th class='small_standard_bold grey_graph_cell'>
				"._QXZ("DAILY LAGGED COUNTS")."
				</th>
				<tr height='600'>
					<td id='chartContainer' colspan=5>
						<canvas id=\"lagged_range_graph\" width=\"300\" height=\"300\">
						</canvas>
					</td>
				</tr>
			</table>
		";
		$HTML_text.="
			<script language='Javascript'>
			function openNewWindow(url)
			  {
			  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
			  }
			var lagged_range_data = {
					labels:[$axis_labels],
					datasets: [
						{
							label: \"\",
							fill: false,
							data: [$daily_counts],
							backgroundColor: [$bgcolor],
							hoverBackgroundColor: [$hbgcolor],
							hoverBorderColor: [$hbcolor],
							hoverBorderWidth: 2,
							tension: 0,
							fillColor: \"rgba(217,230,254,0.5)\",
							borderColor: \"rgba(1,91,145,0.8)\",
							pointBorderColor: \"rgba(1,91,145,1)\",
							pointBackgroundColor: \"#9BB9FB\",
							pointHoverBackgroundColor: \"rgba(1,91,145,0.75)\",
							pointHoverBorderColor: \"rgba(217,230,254,1)\"
						}
					]
				}
			var main_ctx = document.getElementById(\"lagged_range_graph\");
			var summary_chart = new Chart(main_ctx, {type: 'bar', options: { legend: { display: false }, scales: { yAxes: [{ ticks:{beginAtZero: true} }]} }, data: lagged_range_data});
			onload = function() {
			}
			
			main_ctx.addEventListener('click', handleBarClick, false);
			
			function handleBarClick(evt)
				{
				var activeElement = summary_chart.getElementAtEvent(evt);
				var barDate = summary_chart.data.labels[activeElement[0]._index];
				window.location = 'AST_LAGGED_log_report.php?query_date='+barDate+'&query_date_D=00%3A00%3A00&query_date_T=23%3A59%3A59&report_display_type=TEXT&SUBMIT=SUBMIT';
				}
			
			
			</script>
			";
		
		// Agent IP breakdown
		$start_date = date('Y-m-d', strtotime($query_date));
		$end_date = date('Y-m-d', strtotime($query_date_end));
		$total_count = 0;
		while ($start_date <= $end_date) 
			{
			$rpt_stmt="SELECT user, event_time FROM vicidial_agent_log WHERE sub_status = 'LAGGED' AND event_time>='$start_date 00:00:00' AND event_time<='$start_date 23:59:59'";
			if ($DB) {$ASCII_text.=$rpt_stmt."\n";}
			$lagged_rslt=mysql_to_mysqli($rpt_stmt, $link);
			while ( $lagged_row = mysqli_fetch_array($lagged_rslt) )
				{
				$current_event_date = $lagged_row[event_time];
				$current_user = $lagged_row[user];
				$rpt_stmt="SELECT user, event_date, computer_ip FROM vicidial_user_log WHERE event = 'LOGIN' AND event_date>='$start_date 00:00:00' AND event_date<='$current_event_date' AND user = '$current_user' ORDER BY event_date DESC";
				if ($DB) {$ASCII_text.=$rpt_stmt."\n";}
				$login_rslt=mysql_to_mysqli($rpt_stmt, $link);			
				$login_row = mysqli_fetch_row($login_rslt);
				$comp_ip = $login_row[2];
				if ($login_row[2] = "") { $comp_ip = "UNKOWN"; }
				$computer_ip_list[$comp_ip]++;
				$total_count++;
				}
			$start_date = date('Y-m-d', strtotime($start_date . '+ 1 days'));
			}
		if ($DB) {$ASCII_text.=$rpt_stmt."\n";}		
		$HTML_text.="<table border='0' cellpadding='0' cellspacing='2' width='350'>";
		$HTML_text.="<TR><TH colspan='2' class='small_standard_bold grey_graph_cell'>"._QXZ("AGENT IP BREAKDOWN FOR LAGGED RECORDS <br>")." $query_date "._QXZ("TO")." $query_date_end</TH></TR>";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("AGENT IP")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("COUNT")."</th></tr>";	
		foreach ($computer_ip_list as $computer_ip => $count)
			{
			$HTML_text.="<TR><TD class='small_standard'>$computer_ip</td><TD class='small_standard'>$count</td></tr>";
			}
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("TOTAL")."</th><TH class='small_standard_bold grey_graph_cell'>$total_count</th></tr></table>";
		}
	else
		{
		$MAIN.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		}
	$MAIN.=$HTML_text;
	$MAIN.="</form></BODY></HTML>\n";
	}
	    

echo $HEADER;
require("admin_header.php");
echo $MAIN;


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

exit;

?>
