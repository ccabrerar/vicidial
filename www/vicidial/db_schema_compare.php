<?php 
# db_schema_compare.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 240706-2050 - First build
#

$startMS = microtime();

$report_name='DB Schema Compare';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["first_id"]))			{$first_id=$_GET["first_id"];}
	elseif (isset($_POST["first_id"]))	{$first_id=$_POST["first_id"];}
if (isset($_GET["second_id"]))			{$second_id=$_GET["second_id"];}
	elseif (isset($_POST["second_id"]))	{$second_id=$_POST["second_id"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,qc_features_active,allow_web_debug,slave_db_server,coldstorage_server_ip,coldstorage_dbname,coldstorage_login,coldstorage_pass,coldstorage_port,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass FROM system_settings;";
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
	$SSqc_features_active =			$row[7];
	$SSallow_web_debug =			$row[8];
	$SSslave_db_server =			$row[9];
	$SScoldstorage_server_ip =		$row[10];
	$SScoldstorage_dbname =			$row[11];
	$SScoldstorage_login =			$row[12];
	$SScoldstorage_pass =			$row[13];
	$SScoldstorage_port =			$row[14];
	$SSalt_log_server_ip =			$row[15];
	$SSalt_log_dbname =				$row[16];
	$SSalt_log_login =				$row[17];
	$SSalt_log_pass =				$row[18];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$first_id = preg_replace('/[^-:\+\*\#\.\_0-9\p{L}]/u', '', $first_id);
$second_id = preg_replace('/[^-:\+\*\#\.\_0-9\p{L}]/u', '', $second_id);
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8 and view_reports='1';";
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

$stmt="SELECT user_group,qc_enabled,modify_campaigns,modify_lists,modify_ingroups,modify_inbound_dids,modify_users,modify_usergroups,modify_phones,modify_servers,modify_shifts from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];
$qc_auth =					$row[1];
$LOGmodify_campaigns =		$row[2];
$LOGmodify_lists =			$row[3];
$LOGmodify_ingroups =		$row[4];
$LOGmodify_inbound_dids =	$row[5];
$LOGmodify_users =			$row[6];
$LOGmodify_usergroups =		$row[7];
$LOGmodify_phones =			$row[8];
$LOGmodify_servers =		$row[9];
$LOGmodify_shifts =			$row[10];

if ($LOGmodify_servers < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for server debugging").": |$PHP_AUTH_USER|\n";
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
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}

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

?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}


	.diff table{
	margin          : 1px 1px 1px 1px;
	border-collapse : collapse;
	border-spacing  : 0;
	}

	.diff td{
	vertical-align : top;
	font-family    : monospace;
	font-size      : 9;
	}
	.diff span{
	display:block;
	min-height:1pm;
	margin-top:-1px;
	padding:1px 1px 1px 1px;
	}

	* html .diff span{
	height:1px;
	}

	.diff span:first-child{
	margin-top:1px;
	}

	.diffDeleted span{
	border:1px solid rgb(255,51,0);
	background:rgb(255,173,153);
	}

	.diffInserted span{
	border:1px solid rgb(51,204,51);
	background:rgb(102,255,51);
	}

-->
 </STYLE>

<?php 
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$short_header=1;

require("admin_header.php");

if ( ($stage == 'empty') or (strlen($stage) < 1) )
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("DB Schema Compare Utility")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=0>";
	echo "<FONT SIZE=3 FACE=\"Ariel,Helvetica\"><b>\n";
	echo _QXZ("DB Schema Compare Utility").$NWB."db_schema_compare".$NWE."<br><br>\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET ID='vicidial_report' NAME='vicidial_report'>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<FONT SIZE=2>"._QXZ("DB Type").": </FONT>";
	echo "<select size=1 name=stage>";
	echo "<option value='SECONDARY'>"._QXZ("SECONDARY")."</option>";
	echo "<option value='COLDSTORAGE'>"._QXZ("COLDSTORAGE")."</option>";
	echo "<option value='ALT_LOG_SECONDARY'>"._QXZ("ALT_LOG_SECONDARY")."</option>";
	echo "<option value='ALT_LOG'>"._QXZ("ALT_LOG")."</option>";
	echo "</select>";
	echo " &nbsp; <INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</FORM>\n\n";
	echo "</TD></TR>\n\n";
	echo "</TABLE>\n";
	echo "</BODY></HTML>\n";
	exit;
	}
else
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("DB Schema Compare Utility")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=0>";
	echo "<FONT SIZE=3 FACE=\"Ariel,Helvetica\"><b>\n";
	echo _QXZ("DB Schema Compare Utility").$NWB."db_schema_compare".$NWE." &nbsp; &nbsp; &nbsp; &nbsp; $stage &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF\">"._QXZ("RESET")."</a><br><br>\n";

	$first_id_menu='';
	$second_id_menu='';
	$stmt='';
	if ($stage == 'SECONDARY')
		{
		if (strlen($SSslave_db_server)>4)
			{
			if (preg_match("/\:/", $SSslave_db_server)) 
				{
				$temp_slave_db = explode(':',$SSslave_db_server);
				$CSserver_string =	$temp_slave_db[0];
				$VARDB_port =		$temp_slave_db[1];
				}
			else
				{
				$CSserver_string = $SSslave_db_server;
				}
			$linkCS=mysqli_connect($CSserver_string, "$VARDB_user", "$VARDB_pass", "$VARDB_database", $VARDB_port);
			}
		else
			{
			echo "Error: no secondary server: $SSslave_db_server\n";
			exit;
			}
		}
	if ($stage == 'COLDSTORAGE')
		{
		if ( (strlen($SScoldstorage_server_ip) > 1) and (strlen($SScoldstorage_login) > 0) and (strlen($SScoldstorage_pass) > 0) )
			{
			$CSserver_string = $SScoldstorage_server_ip;
			$linkCS = mysqli_connect("$SScoldstorage_server_ip", "$SScoldstorage_login", "$SScoldstorage_pass", "$SScoldstorage_dbname", $SScoldstorage_port);
			}
		else
			{
			echo "Error: no secondary server: $SSslave_db_server\n";
			exit;
			}
		}
	if ($stage == 'ALT_LOG_SECONDARY')
		{
		if (strlen($SSalt_log_server_ip)>4)
			{
			if (preg_match("/\:/", $SSalt_log_server_ip)) 
				{
				$temp_slave_db = explode(':',$SSalt_log_server_ip);
				$CSserver_string =	$temp_slave_db[0];
				$VARDB_port =		$temp_slave_db[1];
				}
			else
				{
				$CSserver_string = $SSalt_log_server_ip;
				}
			$linkCS=mysqli_connect($CSserver_string, "$VARDB_user", "$VARDB_pass", "$VARDB_database", $VARDB_port);
			}
		else
			{
			echo "Error: no alt-log secondary server: $SSalt_log_server_ip \n";
			exit;
			}
		}
	if ($stage == 'ALT_LOG')
		{
		if (strlen($SSalt_log_server_ip)>4)
			{
			if (preg_match("/\:/", $SSalt_log_server_ip)) 
				{
				$temp_slave_db = explode(':',$SSalt_log_server_ip);
				$CSserver_string =	$temp_slave_db[0];
				$VARDB_port =		$temp_slave_db[1];
				}
			else
				{
				$CSserver_string = $SSalt_log_server_ip;
				}
			$linkCS=mysqli_connect($CSserver_string, "$SSalt_log_login", "$SSalt_log_pass", "$SSalt_log_dbname", $VARDB_port);
			}
		else
			{
			echo "Error: no alt-log server: $SSalt_log_server_ip \n";
			exit;
			}
		}

	if (!$linkCS)
		{
		echo "Error: no DB compare server connection: |$stage|\n" . mysqli_connect_error();;
		exit;
		}



	echo "<HR>SCHEMA DETAILS:\n<BR>";
	$first_tables='|';
	$second_tables='|';

	# get primary server list of DB tables
	$stmtA="SELECT db_schema_version from system_settings;";
	$rslt=mysql_to_mysqli($stmtA, $link);
	if ($DB) {echo "$stmtA\n";}
	$rows_to_printA = mysqli_num_rows($rslt);
	$first_text = "DB Server: $VARDB_server\nSchema version: ";
	if ($rows_to_printA > 0)
		{
		$row = mysqli_fetch_array($rslt);
		$first_text .= $row[0] . "\n";
		}

	# get secondary server list of DB tables
	$rsltB=mysql_to_mysqli($stmtA, $linkCS);
	if ($DB) {echo "$stmtA\n";}
	$rows_to_printB = mysqli_num_rows($rsltB);
	$second_text = "DB Server: $CSserver_string\nSchema version: ";
	if ($rows_to_printB > 0)
		{
		$row = mysqli_fetch_array($rsltB);
		$second_text .= $row[0] . "\n";
		}

	// include the Diff class
	require_once './class.Diff.php';

	echo Diff::toTable(Diff::compare($first_text, $second_text));


	$tables = array();
	$first_tables='|';
	$second_tables='|';
	echo "<HR>TABLES LIST:\n<BR>";

	# get primary server list of DB tables
	$stmtA="SHOW TABLES;";
	$rslt=mysql_to_mysqli($stmtA, $link);
	if ($DB) {echo "$stmtA\n";}
	$tables_to_printA = mysqli_num_rows($rslt);
	$first_text = "DB Server: $VARDB_server\nTABLES: $tables_to_printA\n";
	$i=0;
	while ($tables_to_printA > $i)
		{
		$row = mysqli_fetch_array($rslt);
		$first_text .= $row[0] . "\n";
		$tables[$i] = $row[0];
		$first_tables .= "$tables[$i]|";
		$i++;
		}

	# get secondary server list of DB tables
	$stmtB="SHOW TABLES;";
	$rsltB=mysql_to_mysqli($stmtB, $linkCS);
	if ($DB) {echo "$stmtB\n";}
	$tables_to_printB = mysqli_num_rows($rsltB);
	$second_text = "DB Server: $CSserver_string\nTABLES: $tables_to_printB\n";
	$i=0;
	while ($tables_to_printB > $i)
		{
		$row = mysqli_fetch_array($rsltB);
		$second_text .= $row[0] . "\n";
		$second_tables .= "$row[0]|";
		$i++;
		}

	// include the Diff class
	require_once './class.Diff.php';

	echo Diff::toTable(Diff::compare($first_text, $second_text));





	$first_text='';
	$second_text='';
	echo "<BR><BR><HR>TABLE ROW COUNTS:\n<BR>";

	$i=0;
	while ($tables_to_printA > $i)
		{
		# get primary server list of DB tables
		$stmtA="SELECT count(*) from $tables[$i];";
		$rslt=mysql_to_mysqli($stmtA, $link);
		if ($DB) {echo "$stmtA\n";}
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0)
			{
			$row = mysqli_fetch_array($rslt);
			$first_text .= $tables[$i] . ': ' . $row[0] . "\n";
			}

		if (preg_match("/\|$tables[$i]\|/",$second_tables))
			{
			$rsltB=mysql_to_mysqli($stmtA, $linkCS);
			if ($DB) {echo "$stmtA\n";}
			$rows_to_printB = mysqli_num_rows($rsltB);
			if ($rows_to_printB > 0)
				{
				$row = mysqli_fetch_array($rsltB);
				$second_text .= $tables[$i] . ': ' . $row[0] . "\n";
				}
			}
		else
			{
			$second_text .= "na\n";
			}

		$i++;
		}

	echo Diff::toTable(Diff::compare($first_text, $second_text));





	$first_text='';
	$second_text='';
	echo "<BR><BR><HR>TABLE FIELD COUNTS:\n<BR>";

	$i=0;
	while ($tables_to_printA > $i)
		{
		# get primary server list of DB tables
		$stmtA="SELECT * from $tables[$i] limit 1;";
		$rslt=mysql_to_mysqli($stmtA, $link);
		if ($DB) {echo "$stmtA\n";}
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0)
			{
			$fieldsA = mysqli_num_fields($rslt);
			$row = mysqli_fetch_array($rslt);
			$first_text .= $tables[$i] . ': ' . $fieldsA . "\n";
			}
		else
			{
			$first_text .= $tables[$i] . ": empty\n";
			}

		if (preg_match("/\|$tables[$i]\|/",$second_tables))
			{
			$rsltB=mysql_to_mysqli($stmtA, $linkCS);
			if ($DB) {echo "$stmtA\n";}
			$rows_to_printB = mysqli_num_rows($rsltB);
			if ($rows_to_printB > 0)
				{
				$fieldsB = mysqli_num_fields($rsltB);
				$row = mysqli_fetch_array($rsltB);
				$second_text .= $tables[$i] . ': ' . $fieldsB . "\n";
				}
			else
				{
				$second_text .= $tables[$i] . ": empty\n";
				}
			}
		else
			{
			$second_text .= "na\n";
			}

		$i++;
		}

	echo Diff::toTable(Diff::compare($first_text, $second_text));

	echo "</TD></TR>\n\n";
	echo "</TABLE>\n";
	echo "</BODY></HTML>";
	}
exit;
