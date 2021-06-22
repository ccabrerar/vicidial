<?php
# list_download.php
# 
# downloads the entire contents of a vicidial list ID to a flat text file
# that is tab delimited
#
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90209-1310 - First build
# 90508-0644 - Changed to PHP long tags
# 90721-1238 - Added rank and owner as vicidial_list fields
# 100119-1039 - Filtered comments for \n newlines
# 100508-1439 - Added header row to output
# 100702-1335 - Added custom fields
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100804-1745 - Added option to download DNC and FPGN lists
# 100924-1609 - Added ALL-LISTS option for downloading everything
# 100929-1919 - Fixed ALL-LISTS download option to include custom fields
# 101004-2108 - Added generic custom column headers for custom fields
# 120713-2110 - Added extended_vl_fields option
# 120907-1217 - Raised extended fields up to 99
# 130414-0228 - Added report logging
# 130610-0945 - Finalized changing of all ereg instances to preg
# 130618-0043 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0902 - Changed to mysqli PHP functions
# 140108-0723 - Added webserver and hostname to report logging
# 140326-2235 - Changed to allow for custom fields with a different entry_list_id
# 141114-0036 - Finalized adding QXZ translation to all admin files
# 141229-1840 - Added code for on-the-fly language translations display
# 150727-2158 - Enabled user features for hiding phone numbers and lead data
# 150903-1538 - Added compatibility for custom fields data options
# 170306-0858 - Added proper report URL logging
# 170409-1556 - Added IP List validation code
# 180330-1414 - Added option for downloading of CID Groups
# 190926-1015 - Fix for PHP7
# 201208-1630 - Fix for custom fields issue when downloading list with no custom fields itself
# 210308-0939 - Fix for ALL_DNC_CAMPAIGNS dnc download
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["group_id"]))				{$group_id=$_GET["group_id"];}
	elseif (isset($_POST["group_id"]))		{$group_id=$_POST["group_id"];}
if (isset($_GET["download_type"]))			{$download_type=$_GET["download_type"];}
	elseif (isset($_POST["download_type"]))	{$download_type=$_POST["download_type"];}

if (strlen($shift)<2) {$shift='ALL';}
if ($group_id=='SYSTEM_INTERNAL') {$download_type='systemdnc';}

$report_name = 'Download List';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,custom_fields_enabled,enable_languages,language_method,active_modules FROM system_settings;";
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
	$custom_fields_enabled =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$active_modules =				$row[7];
	}
##### END SETTINGS LOOKUP #####
###########################################

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
$list_id = preg_replace('/[^-_0-9a-zA-Z]/','',$list_id);
$group_id = preg_replace('/[^-_0-9a-zA-Z]/','',$group_id);
$download_type = preg_replace('/[^-_0-9a-zA-Z]/','',$download_type);

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($auth < 1)
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

$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and download_lists='1';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$download_auth=$row[0];

if ($download_auth < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
    echo _QXZ("No list download permission").": |$PHP_AUTH_USER|\n";
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

if (!preg_match("/\?/",$LOGfull_url))
	{$LOGfull_url .= "?download_type=$download_type&group_id=$group_id&list_id=$list_id";}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$list_id, $group_id, $download_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="SELECT user_group,admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =				$row[0];
$LOGadmin_hide_lead_data =		$row[1];
$LOGadmin_hide_phone_data =		$row[2];
$LOGadmin_cf_show_hidden =		$row[3];

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns = $row[0];
$LOGallowed_reports =	$row[1];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
	Header ("Content-type: text/html; charset=utf-8");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

if (file_exists('options.php'))
	{require('options.php');}
$extended_vl_fields_SQL='';
$extended_vl_fields_HEADER='';
if ($extended_vl_fields > 0)
	{
	$extended_vl_fields_SQL = ',q01,q02,q03,q04,q05,q06,q07,q08,q09,q10,q11,q12,q13,q14,q15,q16,q17,q18,q19,q20,q21,q22,q23,q24,q25,q26,q27,q28,q29,q30,q31,q32,q33,q34,q35,q36,q37,q38,q39,q40,q41,q42,q43,q44,q45,q46,q47,q48,q49,q50,q51,q52,q53,q54,q55,q56,q57,q58,q59,q60,q61,q62,q63,q64,q65,q66,q67,q68,q69,q70,q71,q72,q73,q74,q75,q76,q77,q78,q79,q80,q81,q82,q83,q84,q85,q86,q87,q88,q89,q90,q91,q92,q93,q94,q95,q96,q97,q98,q99';
	$extended_vl_fields_HEADER = "\tq01\tq02\tq03\tq04\tq05\tq06\tq07\tq08\tq09\tq10\tq11\tq12\tq13\tq14\tq15\tq16\tq17\tq18\tq19\tq20\tq21\tq22\tq23\tq24\tq25\tq26\tq27\tq28\tq29\tq30\tq31\tq32\tq33\tq34\tq35\tq36\tq37\tq38\tq39\tq40\tq41\tq42\tq43\tq44\tq45\tq46\tq47\tq48\tq49\tq50\tq51\tq52\tq53\tq54\tq55\tq56\tq57\tq58\tq59\tq60\tq61\tq62\tq63\tq64\tq65\tq66\tq67\tq68\tq69\tq70\tq71\tq72\tq73\tq74\tq75\tq76\tq77\tq78\tq79\tq80\tq81\tq82\tq83\tq84\tq85\tq86\tq87\tq88\tq89\tq90\tq91\tq92\tq93\tq94\tq95\tq96\tq97\tq98\tq99";
	}

$LOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}

if ($download_type == 'systemdnc')
	{
	##### System DNC list validation #####
	$event_code_type='SYSTEM INTERNAL DNC';
	if (strlen($LOGallowed_campaignsSQL) > 2)
		{
		echo _QXZ("You are not allowed to download this list").": $list_id\n";
		exit;
		}

	$stmt="select count(*) from vicidial_dnc;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$leads_count =$row[0];
		$i++;
		}

	if ($leads_count < 1)
		{
		echo _QXZ("There are no phone numbers in list").": SYSTEM INTERNAL DNC\n";
		exit;
		}
	}
elseif ($download_type == 'dnc')
	{
	##### Campaign DNC list validation #####
	$event_code_type='CAMPAIGN DNC';
	$camp_typeSQL = "campaign_id='$group_id'";

	if (preg_match('/ALL_CAMPAIGNS/',$group_id)) {$camp_typeSQL = "active IN('Y','N')";}
	if (preg_match('/ALL_DNC_CAMPAIGNS/',$group_id)) {$camp_typeSQL = "use_campaign_dnc IN('Y','AREACODE')";}
	if (preg_match('/ALL_ACTIVE_CAMPAIGNS/',$group_id)) {$camp_typeSQL = "active='Y'";}
	if (preg_match('/ALL_ACTIVE_DNC_CAMPAIGNS/',$group_id)) {$camp_typeSQL = "active='Y' and use_campaign_dnc IN('Y','AREACODE')";}

	if (preg_match('/ALL_CAMPAIGNS|ALL_DNC_CAMPAIGNS|ALL_ACTIVE_CAMPAIGNS|ALL_ACTIVE_DNC_CAMPAIGNS/',$group_id)) 
		{
		$stmt = "SELECT campaign_id FROM vicidial_campaigns where $camp_typeSQL order by campaign_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$camp_ct = mysqli_num_rows($rslt);
		if ($DB) {echo "$camp_ct|$stmt\n";}
		$cdnc=0;   $camp_list='';
		while ($camp_ct > $cdnc)
			{
			$row=mysqli_fetch_row($rslt);
			if ($cdnc > 0) {$camp_list.= ",";}
			$camp_list.= "'$row[0]'";
			$cdnc++;
			}
		if ($cdnc > 0) {$camp_typeSQL = "campaign_id IN($camp_list)";}
		}

	$stmt="select count(*) from vicidial_campaigns where $camp_typeSQL $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$count_to_print = mysqli_num_rows($rslt);
	if ($DB) {echo "$count_to_print|$stmt\n";}
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lists_allowed =$row[0];
		$i++;
		}

	if ($lists_allowed < 1)
		{
		echo _QXZ("You are not allowed to download this list").": $group_id\n";
		exit;
		}

	$stmt="select count(*) from vicidial_campaign_dnc where $camp_typeSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$leads_count =$row[0];
		$i++;
		}

	if ($leads_count < 1)
		{
		echo _QXZ("There are no leads in Campaign DNC list").": $group_id\n";
		exit;
		}
	}
elseif ($download_type == 'fpgn')
	{
	##### Filter Phone Group list validation #####
	$event_code_type='FILTER PHONE GROUP';
	$stmt="select count(*) from vicidial_filter_phone_groups where filter_phone_group_id='$group_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lists_allowed =$row[0];
		$i++;
		}

	if ($lists_allowed < 1)
		{
		echo _QXZ("You are not allowed to download this list").": $group_id\n";
		exit;
		}

	$stmt="select count(*) from vicidial_filter_phone_numbers where filter_phone_group_id='$group_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$leads_count =$row[0];
		$i++;
		}

	if ($leads_count < 1)
		{
		echo _QXZ("There are no leads in this filter phone group").": $group_id\n";
		exit;
		}
	}
elseif ($download_type == 'cidgroup')
	{
	##### CID Group list validation #####
	$event_code_type='CID GROUP';
	$stmt="select count(*) from vicidial_cid_groups where cid_group_id='$group_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lists_allowed =$row[0];
		$i++;
		}

	if ($lists_allowed < 1)
		{
		echo _QXZ("You are not allowed to download this list").": $group_id\n";
		exit;
		}

	$stmt="select count(*) from vicidial_campaign_cid_areacodes where campaign_id='$group_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$leads_count =$row[0];
		$i++;
		}

	if ($leads_count < 1)
		{
		echo _QXZ("There are no records in this CID group").": $group_id\n";
		exit;
		}
	}
else
	{
	##### list download validation #####
	$event_code_type='LIST';
	$stmt="select count(*) from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
	if ($list_id=='ALL-LISTS')
		{$stmt="select count(*) from vicidial_lists where list_id > 0 $LOGallowed_campaignsSQL;";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lists_allowed =$row[0];
		$i++;
		}

	if ($lists_allowed < 1)
		{
		echo _QXZ("You are not allowed to download this list").": $list_id\n";
		exit;
		}

	$stmt="select count(*) from vicidial_list where list_id='$list_id';";
	if ($list_id=='ALL-LISTS')
		{$stmt="select count(*) from vicidial_list where list_id > 0;";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$count_to_print = mysqli_num_rows($rslt);
	if ($count_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$leads_count =$row[0];
		$i++;
		}

	if ($leads_count < 1)
		{
		echo _QXZ("There are no leads in list_id").": $list_id\n";
		exit;
		}
	}


$US='_';
$MT[0]='';
$ip = getenv("REMOTE_ADDR");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_TIME = date("Ymd-His");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}


if ($download_type == 'systemdnc')
	{
	$TXTfilename = "SYSTEM_DNC_$FILE_TIME.txt";
	$header_row = "phone_number";
	$header_columns='';
	$stmt="select phone_number from vicidial_dnc;";
	}
elseif ($download_type == 'dnc')
	{
	$TXTfilename = "DNC_$group_id$US$FILE_TIME.txt";
	$header_row = "phone_number";
	$header_columns='';
	$stmt="select phone_number from vicidial_campaign_dnc where $camp_typeSQL;";
	}
elseif ($download_type == 'fpgn')
	{
	$TXTfilename = "FPGN_$group_id$US$FILE_TIME.txt";
	$header_row = "phone_number";
	$header_columns='';
	$stmt="select phone_number from vicidial_filter_phone_numbers where filter_phone_group_id='$group_id';";
	}
elseif ($download_type == 'cidgroup')
	{
	$TXTfilename = "CIDGROUP_$group_id$US$FILE_TIME.txt";
	$header_row = "areacodestate,cid,description,active";
	$header_columns='';
	$stmt="select areacode,outbound_cid,cid_description,active from vicidial_campaign_cid_areacodes where campaign_id='$group_id';";
	}
else
	{
	$TXTfilename = "LIST_$list_id$US$FILE_TIME.txt";
	$list_id_header='';
	$stmt="select lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id $extended_vl_fields_SQL from vicidial_list where list_id='$list_id';";
	if ($list_id=='ALL-LISTS')
		{
		$list_id_header="list_id\t";   
		$stmt="select list_id,lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id $extended_vl_fields_SQL from vicidial_list where list_id > 0;";
		}
	$header_row = $list_id_header . "lead_id\tentry_date\tmodify_date\tstatus\tuser\tvendor_lead_code\tsource_id\tlist_id\tgmt_offset_now\tcalled_since_last_reset\tphone_code\tphone_number\ttitle\tfirst_name\tmiddle_initial\tlast_name\taddress1\taddress2\taddress3\tcity\tstate\tprovince\tpostal_code\tcountry_code\tgender\tdate_of_birth\talt_phone\temail\tsecurity_phrase\tcomments\tcalled_count\tlast_local_call_time\trank\towner\tentry_list_id$extended_vl_fields_HEADER";
	$header_columns='';
	}

// We'll be outputting a TXT file
header('Content-type: application/octet-stream');

// It will be called LIST_101_20090209-121212.txt
header("Content-Disposition: attachment; filename=\"$TXTfilename\"");
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
ob_clean();
flush();



$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$leads_to_print = mysqli_num_rows($rslt);
$row = array();
$row_data = array();
$export_list_id = array();
$export_lead_id = array();
$custom_data = array();
$i=0;
while ($i < $leads_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	if ( ($download_type == 'systemdnc') or ($download_type == 'dnc') or ($download_type == 'fpgn') )
		{
		$row_data[$i] .= "$row[0]";
		}
	elseif ($download_type == 'cidgroup')
		{
		if ($row[3]=='') {$row[3]='N';}
		$row_data[$i] .= "$row[0],$row[1],$row[2],$row[3]";
		}
	else
		{
		if ($LOGadmin_hide_phone_data != '0')
			{
			if ($DB > 0) {echo "HIDEPHONEDATA|$row[11]|$LOGadmin_hide_phone_data|\n";}
			$phone_temp = $row[11];
			if (strlen($phone_temp) > 0)
				{
				if ($LOGadmin_hide_phone_data == '4_DIGITS')
					{$row[11] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
				elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
					{$row[11] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
				elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
					{$row[11] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
				else
					{$row[11] = preg_replace("/./",'X',$phone_temp);}
				}
			}
		if ($LOGadmin_hide_lead_data != '0')
			{
			if ($DB > 0) {echo "HIDELEADDATA|$row[5]|$row[6]|$row[12]|$row[13]|$row[14]|$row[15]|$row[16]|$row[17]|$row[18]|$row[19]|$row[20]|$row[21]|$row[22]|$row[26]|$row[27]|$row[28]|$LOGadmin_hide_lead_data|\n";}
			if (strlen($row[5]) > 0)
				{$data_temp = $row[5];   $row[5] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[6]) > 0)
				{$data_temp = $row[6];   $row[6] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[12]) > 0)
				{$data_temp = $row[12];   $row[12] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[13]) > 0)
				{$data_temp = $row[13];   $row[13] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[14]) > 0)
				{$data_temp = $row[14];   $row[14] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[15]) > 0)
				{$data_temp = $row[15];   $row[15] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[16]) > 0)
				{$data_temp = $row[16];   $row[16] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[17]) > 0)
				{$data_temp = $row[17];   $row[17] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[18]) > 0)
				{$data_temp = $row[18];   $row[18] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[19]) > 0)
				{$data_temp = $row[19];   $row[19] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[20]) > 0)
				{$data_temp = $row[20];   $row[20] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[21]) > 0)
				{$data_temp = $row[21];   $row[21] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[22]) > 0)
				{$data_temp = $row[22];   $row[22] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[26]) > 0)
				{$data_temp = $row[26];   $row[26] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[27]) > 0)
				{$data_temp = $row[27];   $row[27] = preg_replace("/./",'X',$data_temp);}
			if (strlen($row[28]) > 0)
				{$data_temp = $row[28];   $row[28] = preg_replace("/./",'X',$data_temp);}
			}
			
		$row[29] = preg_replace("/\n|\r/",'!N',$row[29]);
		$extended_vl_fields_DATA='';

		if ($list_id=='ALL-LISTS')
			{
			if ($extended_vl_fields > 0)
				{$extended_vl_fields_DATA = "\t$row[36]\t$row[37]\t$row[38]\t$row[39]\t$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t$row[45]\t$row[46]\t$row[47]\t$row[48]\t$row[49]\t$row[50]\t$row[51]\t$row[52]\t$row[53]\t$row[54]\t$row[55]\t$row[56]\t$row[57]\t$row[58]\t$row[59]\t$row[60]\t$row[61]\t$row[62]\t$row[63]\t$row[64]\t$row[65]\t$row[66]\t$row[67]\t$row[68]\t$row[69]\t$row[70]\t$row[71]\t$row[72]\t$row[73]\t$row[74]\t$row[75]\t$row[76]\t$row[77]\t$row[78]\t$row[79]\t$row[80]\t$row[81]\t$row[82]\t$row[83]\t$row[84]\t$row[85]\t$row[86]\t$row[87]\t$row[88]\t$row[89]\t$row[90]\t$row[91]\t$row[92]\t$row[93]\t$row[94]\t$row[95]\t$row[96]\t$row[97]\t$row[98]\t$row[99]\t$row[100]\t$row[101]\t$row[102]\t$row[103]\t$row[104]\t$row[105]\t$row[106]\t$row[107]\t$row[108]\t$row[109]\t$row[110]\t$row[111]\t$row[112]\t$row[113]\t$row[114]\t$row[115]\t$row[116]\t$row[117]\t$row[118]\t$row[119]\t$row[120]\t$row[121]\t$row[122]\t$row[123]\t$row[124]\t$row[125]\t$row[126]\t$row[127]\t$row[128]\t$row[129]\t$row[130]\t$row[131]\t$row[132]\t$row[133]\t$row[134]";}
			$row_data[$i] .= "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]\t$row[35]$extended_vl_fields_DATA";
			$export_list_id[$i] = $row[0];
			if ( ($row[35] > 99) and (strlen($row[35]) > 2) )
				{$export_list_id[$i] = $row[35];}
			$export_lead_id[$i] = $row[1];
			}
		else
			{
			if ($extended_vl_fields > 0)
				{$extended_vl_fields_DATA = "\t$row[35]\t$row[36]\t$row[37]\t$row[38]\t$row[39]\t$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t$row[45]\t$row[46]\t$row[47]\t$row[48]\t$row[49]\t$row[50]\t$row[51]\t$row[52]\t$row[53]\t$row[54]\t$row[55]\t$row[56]\t$row[57]\t$row[58]\t$row[59]\t$row[60]\t$row[61]\t$row[62]\t$row[63]\t$row[64]\t$row[65]\t$row[66]\t$row[67]\t$row[68]\t$row[69]\t$row[70]\t$row[71]\t$row[72]\t$row[73]\t$row[74]\t$row[75]\t$row[76]\t$row[77]\t$row[78]\t$row[79]\t$row[80]\t$row[81]\t$row[82]\t$row[83]\t$row[84]\t$row[85]\t$row[86]\t$row[87]\t$row[88]\t$row[89]\t$row[90]\t$row[91]\t$row[92]\t$row[93]\t$row[94]\t$row[95]\t$row[96]\t$row[97]\t$row[98]\t$row[99]\t$row[100]\t$row[101]\t$row[102]\t$row[103]\t$row[104]\t$row[105]\t$row[106]\t$row[107]\t$row[108]\t$row[109]\t$row[110]\t$row[111]\t$row[112]\t$row[113]\t$row[114]\t$row[115]\t$row[116]\t$row[117]\t$row[118]\t$row[119]\t$row[120]\t$row[121]\t$row[122]\t$row[123]\t$row[124]\t$row[125]\t$row[126]\t$row[127]\t$row[128]\t$row[129]\t$row[130]\t$row[131]\t$row[132]\t$row[133]";}
			$row_data[$i] .= "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]$extended_vl_fields_DATA";
			$export_list_id[$i] = $list_id;
			if ( ($row[34] > 99) and (strlen($row[34]) > 2) )
				{$export_list_id[$i] = $row[34];}
			$export_lead_id[$i] = $row[0];
			}
		}
	$i++;
	}

$custom_list_id = array();
$custom_tablecount = array();
$custom_columns = array();
$column_list_array = array();
$ch=0;
if ( ($custom_fields_enabled > 0) and ($event_code_type=='LIST') )
	{
	$valid_custom_table=0;
	if ($list_id=='ALL-LISTS')
		{
		$stmtA = "SELECT list_id from vicidial_lists;";
		$rslt=mysql_to_mysqli($stmtA, $link);
		if ($DB) {echo "$stmtA\n";}
		$lists_ct = mysqli_num_rows($rslt);
		$u=0;
		while ($lists_ct > $u)
			{
			$row=mysqli_fetch_row($rslt);
			$custom_list_id[$u] =	$row[0];
			$u++;
			}
		$u=0;
		while ($lists_ct > $u)
			{
			$stmt="SHOW TABLES LIKE \"custom_$custom_list_id[$u]\";";
			if ($DB>0) {echo "$stmt";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$tablecount_to_print = mysqli_num_rows($rslt);
			$custom_tablecount[$u] = $tablecount_to_print;
			$u++;
			}
		$u=0;
		while ($lists_ct > $u)
			{
			$custom_columns[$u]=0;
			if ($custom_tablecount[$u] > 0)
				{
				$stmtA = "describe custom_$custom_list_id[$u];";
				$rslt=mysql_to_mysqli($stmtA, $link);
				if ($DB) {echo "$stmtA\n";}
				$columns_ct = mysqli_num_rows($rslt);
				$custom_columns[$u] = $columns_ct;
				}
			if ($DB) {echo "$custom_list_id[$u]|$custom_tablecount[$u]|$custom_columns[$u]\n";}
			$u++;
			}
		$valid_custom_table=1;
		}
	else
		{
		$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$tablecount_to_print = mysqli_num_rows($rslt);
		if ($tablecount_to_print > 0) 
			{
			$stmtA = "describe custom_$list_id;";
			$rslt=mysql_to_mysqli($stmtA, $link);
			if ($DB) {echo "$stmtA\n";}
			$columns_ct = mysqli_num_rows($rslt);
			$u=0;
			while ($columns_ct > $u)
				{
				$row=mysqli_fetch_row($rslt);
				$column =	$row[0];
				if ($u > 0)
					{$header_columns .= "\t$column";}
				$u++;
				}
			if ($columns_ct > 1)
				{
				$valid_custom_table=1;
				}
			}
		if ($valid_custom_table < 1)
			{
			$stmt="SHOW TABLES LIKE \"custom\_%\";";
			if ($DB>0) {echo "$stmt";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$tablecount_to_print = mysqli_num_rows($rslt);
			$valid_custom_table = $tablecount_to_print;
			}
		}
	if ($valid_custom_table > 0)
		{
		$i=0;
		while ($i < $leads_to_print)
			{
			if ($list_id=='ALL-LISTS')
				{
				$valid_custom_table=0;
				$u=0;
				while ($lists_ct > $u)
					{
					if ( ($export_list_id[$i] == "$custom_list_id[$u]") and ($custom_columns[$u] > 1) )
						{
						$valid_custom_table=1;
						$columns_ct = $custom_columns[$u];
						}
					$u++;
					}
				}
			if ($valid_custom_table > 0)
				{
				$column_list='';
				$encrypt_list='';
				$hide_list='';
				$stmt = "DESCRIBE custom_$export_list_id[$i];";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$columns_ct = mysqli_num_rows($rslt);
				$u=0;
				while ($columns_ct > $u)
					{
					$row=mysqli_fetch_row($rslt);
					$column =	$row[0];
					$column_list .= "$row[0],";
					$u++;
					}
				if ($columns_ct > 1)
					{
					$column_list = preg_replace("/lead_id,/",'',$column_list);
					$column_list = preg_replace("/,$/",'',$column_list);
					$column_list_array = explode(',',$column_list);
					if (preg_match("/cf_encrypt/",$active_modules))
						{
						$enc_fields=0;
						$stmt = "SELECT count(*) from vicidial_lists_fields where field_encrypt='Y' and list_id='$export_list_id[$i]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$enc_field_ct = mysqli_num_rows($rslt);
						if ($enc_field_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$enc_fields =	$row[0];
							}
						if ($enc_fields > 0)
							{
							$stmt = "SELECT field_label from vicidial_lists_fields where field_encrypt='Y' and list_id='$export_list_id[$i]';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$enc_field_ct = mysqli_num_rows($rslt);
							$r=0;
							while ($enc_field_ct > $r)
								{
								$row=mysqli_fetch_row($rslt);
								$encrypt_list .= "$row[0],";
								$r++;
								}
							$encrypt_list = ",$encrypt_list";
							}
						if ($LOGadmin_cf_show_hidden < 1)
							{
							$hide_fields=0;
							$stmt = "SELECT count(*) from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$export_list_id[$i]';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$hide_field_ct = mysqli_num_rows($rslt);
							if ($hide_field_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$hide_fields =	$row[0];
								}
							if ($hide_fields > 0)
								{
								$stmt = "SELECT field_label from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$export_list_id[$i]';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$hide_field_ct = mysqli_num_rows($rslt);
								$r=0;
								while ($hide_field_ct > $r)
									{
									$row=mysqli_fetch_row($rslt);
									$hide_list .= "$row[0],";
									$r++;
									}
								$hide_list = ",$hide_list";
								}
							}
						}
					$stmt = "SELECT $column_list from custom_$export_list_id[$i] where lead_id='$export_lead_id[$i]' limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$customfield_ct = mysqli_num_rows($rslt);
					if ($customfield_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$t=0;
						while ($columns_ct >= $t) 
							{
							if ($enc_fields > 0)
								{
								$field_enc='';   $field_enc_all='';
								if ($DB) {echo "|$column_list|$encrypt_list|\n";}
								if ( (preg_match("/,$column_list_array[$t],/",$encrypt_list)) and (strlen($row[$t]) > 0) )
									{
									exec("../agc/aes.pl --decrypt --text=$row[$t]", $field_enc);
									$field_enc_ct = count($field_enc);
									$k=0;
									while ($field_enc_ct > $k)
										{
										$field_enc_all .= $field_enc[$k];
										$k++;
										}
									$field_enc_all = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
									$row[$t] = base64_decode($field_enc_all);
									}
								}
							if ( (preg_match("/,$column_list_array[$t],/",$hide_list)) and (strlen($row[$t]) > 0) )
								{
								$field_temp_val = $row[$t];
								$row[$t] = preg_replace("/./",'X',$field_temp_val);
								}
							$custom_data[$i] .= "\t$row[$t]";
							if ($ch <= $t)
								{
								$ch++;
								$header_columns .= "\tcustom$ch";
								}
							$t++;
							}
						}
					}
				$custom_data[$i] = preg_replace("/\r\n/",'!N',$custom_data[$i]);
				$custom_data[$i] = preg_replace("/\n/",'!N',$custom_data[$i]);
				}
			$i++;
			}
		}
	}



echo "$header_row$header_columns\r\n";

$i=0;
while ($i < $leads_to_print)
	{
	echo "$row_data[$i]$custom_data[$i]\r\n";

	$i++;
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

### LOG INSERTION Admin Log Table ###
$SQL_log = "$stmt|$stmtA|";
$SQL_log = preg_replace('/;/', '', $SQL_log);
$SQL_log = addslashes($SQL_log);
$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LEADS', event_type='EXPORT', record_id='$list_id', event_code='ADMIN EXPORT $event_code_type', event_sql=\"$SQL_log\", event_notes='';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit;

?>
