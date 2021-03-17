<?php
# qc_api.php
# 
# Copyright (C) 2012  poundteam.com    LICENSE: AGPLv2
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to allow API functions for QC applications, contributed by poundteam.com
#
# changes:
# 121116-1329 - First build, added to vicidial codebase
# 130622-0001 - Finalized changing of all ereg instances to preg
#             - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0906 - Changed to mysqli PHP functions
# 140706-0834 - Incorporated into standard admin code
# 141007-2120 - Finalized adding QXZ translation to all admin files
# 141229-2015 - Added code for on-the-fly language translations display
# 150626-2120 - Modified mysqli_error() to mysqli_connect_error() where appropriate
# 210306-1051 - Changes for new QC module
#

$version = '2.14-8';
$build = '210306-1051';

require("dbconnect_mysqli.php");
require("functions.php");

$query_string = getenv("QUERY_STRING");

### If you have globals turned off uncomment these lines
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}
if (isset($_GET["agent_user"]))					{$agent_user=$_GET["agent_user"];}
	elseif (isset($_POST["agent_user"]))		{$agent_user=$_POST["agent_user"];}
if (isset($_GET["function"]))					{$function=$_GET["function"];}
	elseif (isset($_POST["function"]))			{$function=$_POST["function"];}
if (isset($_GET["value"]))						{$value=$_GET["value"];}
	elseif (isset($_POST["value"]))				{$value=$_POST["value"];}
if (isset($_GET["vendor_id"]))					{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))			{$vendor_id=$_POST["vendor_id"];}
if (isset($_GET["focus"]))						{$focus=$_GET["focus"];}
	elseif (isset($_POST["focus"]))				{$focus=$_POST["focus"];}
if (isset($_GET["preview"]))					{$preview=$_GET["preview"];}
	elseif (isset($_POST["preview"]))			{$preview=$_POST["preview"];}
if (isset($_GET["notes"]))						{$notes=$_GET["notes"];}
	elseif (isset($_POST["notes"]))				{$notes=$_POST["notes"];}
if (isset($_GET["phone_code"]))					{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))		{$phone_code=$_POST["phone_code"];}
if (isset($_GET["search"]))						{$search=$_GET["search"];}
	elseif (isset($_POST["search"]))			{$search=$_POST["search"];}
if (isset($_GET["group_alias"]))				{$group_alias=$_GET["group_alias"];}
	elseif (isset($_POST["group_alias"]))		{$group_alias=$_POST["group_alias"];}
if (isset($_GET["dial_prefix"]))				{$dial_prefix=$_GET["dial_prefix"];}
	elseif (isset($_POST["dial_prefix"]))		{$dial_prefix=$_POST["dial_prefix"];}
if (isset($_GET["source"]))						{$source=$_GET["source"];}
	elseif (isset($_POST["source"]))			{$source=$_POST["source"];}
if (isset($_GET["format"]))						{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))			{$format=$_POST["format"];}
if (isset($_GET["vtiger_callback"]))			{$vtiger_callback=$_GET["vtiger_callback"];}
	elseif (isset($_POST["vtiger_callback"]))	{$vtiger_callback=$_POST["vtiger_callback"];}
if (isset($_GET["blended"]))					{$blended=$_GET["blended"];}
	elseif (isset($_POST["blended"]))			{$blended=$_POST["blended"];}
if (isset($_GET["ingroup_choices"]))			{$ingroup_choices=$_GET["ingroup_choices"];}
	elseif (isset($_POST["ingroup_choices"]))	{$ingroup_choices=$_POST["ingroup_choices"];}
if (isset($_GET["set_as_default"]))				{$set_as_default=$_GET["set_as_default"];}
	elseif (isset($_POST["set_as_default"]))	{$set_as_default=$_POST["set_as_default"];}
if (isset($_GET["alt_user"]))					{$alt_user=$_GET["alt_user"];}
	elseif (isset($_POST["alt_user"]))			{$alt_user=$_POST["alt_user"];}
if (isset($_GET["lead_id"]))					{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))			{$lead_id=$_POST["lead_id"];}
if (isset($_GET["phone_number"]))				{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))		{$phone_number=$_POST["phone_number"];}
if (isset($_GET["vendor_lead_code"]))			{$vendor_lead_code=$_GET["vendor_lead_code"];}
	elseif (isset($_POST["vendor_lead_code"]))	{$vendor_lead_code=$_POST["vendor_lead_code"];}
if (isset($_GET["source_id"]))					{$source_id=$_GET["source_id"];}
	elseif (isset($_POST["source_id"]))			{$source_id=$_POST["source_id"];}
if (isset($_GET["gmt_offset_now"]))				{$gmt_offset_now=$_GET["gmt_offset_now"];}
	elseif (isset($_POST["gmt_offset_now"]))	{$gmt_offset_now=$_POST["gmt_offset_now"];}
if (isset($_GET["title"]))						{$title=$_GET["title"];}
	elseif (isset($_POST["title"]))				{$title=$_POST["title"];}
if (isset($_GET["first_name"]))					{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))		{$first_name=$_POST["first_name"];}
if (isset($_GET["middle_initial"]))				{$middle_initial=$_GET["middle_initial"];}
	elseif (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
if (isset($_GET["last_name"]))					{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))			{$last_name=$_POST["last_name"];}
if (isset($_GET["address1"]))					{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))			{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))					{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))			{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))					{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))			{$address3=$_POST["address3"];}
if (isset($_GET["city"]))						{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))				{$city=$_POST["city"];}
if (isset($_GET["state"]))						{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))				{$state=$_POST["state"];}
if (isset($_GET["province"]))					{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))			{$province=$_POST["province"];}
if (isset($_GET["postal_code"]))				{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))		{$postal_code=$_POST["postal_code"];}
if (isset($_GET["country_code"]))				{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))		{$country_code=$_POST["country_code"];}
if (isset($_GET["gender"]))						{$gender=$_GET["gender"];}
	elseif (isset($_POST["gender"]))			{$gender=$_POST["gender"];}
if (isset($_GET["date_of_birth"]))				{$date_of_birth=$_GET["date_of_birth"];}
	elseif (isset($_POST["date_of_birth"]))		{$date_of_birth=$_POST["date_of_birth"];}
if (isset($_GET["alt_phone"]))					{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))			{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["email"]))						{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))				{$email=$_POST["email"];}
if (isset($_GET["security_phrase"]))			{$security_phrase=$_GET["security_phrase"];}
	elseif (isset($_POST["security_phrase"]))	{$security_phrase=$_POST["security_phrase"];}
if (isset($_GET["comments"]))					{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))			{$comments=$_POST["comments"];}
if (isset($_GET["rank"]))						{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))				{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))						{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))				{$owner=$_POST["owner"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["status"]))						{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))			{$status=$_POST["status"];}
if (isset($_GET["close_window_link"]))			{$close_window_link=$_GET["close_window_link"];}
	elseif (isset($_POST["close_window_link"]))	{$close_window_link=$_POST["close_window_link"];}
if (isset($_GET["dnc_check"]))					{$dnc_check=$_GET["dnc_check"];}
	elseif (isset($_POST["dnc_check"]))			{$dnc_check=$_POST["dnc_check"];}
if (isset($_GET["campaign_dnc_check"]))				{$campaign_dnc_check=$_GET["campaign_dnc_check"];}
	elseif (isset($_POST["campaign_dnc_check"]))	{$campaign_dnc_check=$_POST["campaign_dnc_check"];}
if (isset($_GET["dial_override"]))				{$dial_override=$_GET["dial_override"];}
	elseif (isset($_POST["dial_override"]))		{$dial_override=$_POST["dial_override"];}
if (isset($_GET["consultative"]))				{$consultative=$_GET["consultative"];}
	elseif (isset($_POST["consultative"]))		{$consultative=$_POST["consultative"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSenable_languages =			$row[1];
	$SSlanguage_method =			$row[2];
	}
##### END SETTINGS LOOKUP #####
###########################################

$ingroup_choices = preg_replace("/\+/"," ",$ingroup_choices);
$query_string = preg_replace("/'|\"|\\\\|;/","",$query_string);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
	$agent_user=preg_replace("/[^0-9a-zA-Z]/","",$agent_user);
	$function = preg_replace("/[^-\_0-9a-zA-Z]/","",$function);
	$value = preg_replace("/[^-\_0-9a-zA-Z]/","",$value);
	$vendor_id = preg_replace("/[^-\.\_0-9a-zA-Z]/","",$vendor_id);
	$focus = preg_replace("/[^-\_0-9a-zA-Z]/","",$focus);
	$preview = preg_replace("/[^-\_0-9a-zA-Z]/","",$preview);
		$notes = preg_replace("/\+/"," ",$notes);
	$notes = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$notes);
	$phone_code = preg_replace("/[^0-9X]/","",$phone_code);
	$search = preg_replace("/[^-\_0-9a-zA-Z]/","",$search);
	$group_alias = preg_replace("/[^0-9a-zA-Z]/","",$group_alias);
	$dial_prefix = preg_replace("/[^0-9a-zA-Z]/","",$dial_prefix);
	$source = preg_replace("/[^0-9a-zA-Z]/","",$source);
	$format = preg_replace("/[^0-9a-zA-Z]/","",$format);
	$vtiger_callback = preg_replace("/[^A-Z]/","",$vtiger_callback);
	$alt_dial = preg_replace("/[^0-9A-Z]/","",$alt_dial);
	$blended = preg_replace("/[^A-Z]/","",$blended);
	$ingroup_choices = preg_replace("/[^- \_0-9a-zA-Z]/","",$ingroup_choices);
	$set_as_default = preg_replace("/[^A-Z]/","",$set_as_default);
	$phone_number = preg_replace("/[^0-9]/","",$phone_number);
	$address1 = preg_replace("/[^- \_0-9a-zA-Z]/","",$address1);
	$address2 = preg_replace("/[^- \_0-9a-zA-Z]/","",$address2);
	$address3 = preg_replace("/[^- \_0-9a-zA-Z]/","",$address3);
	$alt_phone = preg_replace("/[^- \_0-9a-zA-Z]/","",$alt_phone);
	$city = preg_replace("/[^- \_0-9a-zA-Z]/","",$city);
	$comments = preg_replace("/[^- \_0-9a-zA-Z]/","",$comments);
	$country_code = preg_replace("/[^A-Z]/","",$country_code);
	$date_of_birth = preg_replace("/[^- \_0-9]/","",$date_of_birth);
	$email = preg_replace("/[^-\.\:\/\@\_0-9a-zA-Z]/","",$email);
	$first_name = preg_replace("/[^- \_0-9a-zA-Z]/","",$first_name);
	$gender = preg_replace("/[^A-Z]/","",$gender);
	$gmt_offset_now = preg_replace("/[^- \.\_0-9]/","",$gmt_offset_now);
	$last_name = preg_replace("/[^- \_0-9a-zA-Z]/","",$last_name);
	$lead_id = preg_replace("/[^0-9]/","",$lead_id);
	$middle_initial = preg_replace("/[^- \_0-9a-zA-Z]/","",$middle_initial);
	$province = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$province);
	$security_phrase = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$security_phrase);
	$source_id = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$source_id);
	$state = preg_replace("/[^- \_0-9a-zA-Z]/","",$state);
	$title = preg_replace("/[^- \_0-9a-zA-Z]/","",$title);
	$vendor_lead_code = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$vendor_lead_code);
	$rank = preg_replace("/[^-0-9]/","",$rank);
	$owner = preg_replace("/[^-\.\:\/\@\_0-9a-zA-Z]/","",$owner);
	$dial_override = preg_replace("/[^A-Z]/","",$dial_override);
	$consultative = preg_replace("/[^A-Z]/","",$consultative);
		$callback_datetime = preg_replace("/\+/"," ",$callback_datetime);
	$callback_datetime = preg_replace("/[^- \:\.\_0-9a-zA-Z]/","",$callback_datetime);
	$callback_type = preg_replace("/[^A-Z]/","",$callback_type);
		$callback_comments = preg_replace("/\+/"," ",$callback_comments);
	$callback_comments = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$callback_comments);
	$qm_dispo_code = preg_replace("/[^-\.\_0-9a-zA-Z]/","",$qm_dispo_code);
	$alt_user = preg_replace("/[^0-9a-zA-Z]/","",$alt_user);
	$postal_code = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$postal_code);
	}
else
	{
	$user = preg_replace("/'|\"|\\\\|;/","",$user);
	$pass = preg_replace("/'|\"|\\\\|;/","",$pass);
	$source = preg_replace("/'|\"|\\\\|;/","",$source);
	$agent_user = preg_replace("/'|\"|\\\\|;/","",$agent_user);
	$alt_user = preg_replace("/'|\"|\\\\|;/","",$alt_user);
	}

### date and fixed variables
$epoch = date("U");
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';
$api_script = 'agent';
$api_logging = 1;
if ($consultative != 'YES') {$consultative='NO';}

$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

################################################################################
### BEGIN - version - show version and date information for the API
################################################################################
if ($function == 'version')
	{
	$data = _QXZ("VERSION").": $version|"._QXZ("BUILD").": $build|"._QXZ("DATE").": $NOW_TIME|EPOCH: $StarTtime";
	$result = 'SUCCESS';
	echo "$data\n";
	api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
	exit;
	}
################################################################################
### END - version
################################################################################





################################################################################
### BEGIN - user validation section (most functions run through this first)
################################################################################

if ($ACTION == 'LogiNCamPaigns')
	{
	$skip_user_validation=1;
	}
else
	{
	if(strlen($source)<2)
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("Invalid Source");
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		$auth=0;
		$auth_message = user_authorization($user,$pass,'',0,0);
		if ($auth_message == 'GOOD')
			{$auth=1;}

		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1';";
		if ($DB) {echo "|$stmt|\n";}
		if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$auth_api=$row[0];

		if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) or ($auth_api==0))
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("Invalid Username/Password");
			echo "$result: $result_reason: |$user|$pass|$auth|$auth_api|$auth_message|\n";
			$data = "$user|$pass|$auth";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT count(*) from system_settings where vdc_agent_api_active='1';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$SNauth=$row[0];
			if($SNauth==0)
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("System API NOT ACTIVE");
				echo "$result: $result_reason\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				# do nothing for now
				}
			}
		}
	}

if ($format=='debug')
	{
	$DB=1;
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    USER: $user\n";
	echo "<title>"._QXZ("VICIDiaL Agent API");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}
################################################################################
### END - user validation section
################################################################################





################################################################################
### BEGIN - external_dial_lead - manual dial a specific Lead
################################################################################
if ($function == 'external_dial_lead')
	{
	$value = preg_replace("/[^0-9]/","",$value);
	# $DB=1;

	if ( (strlen($value)<1) or ( (strlen($agent_user)<2) and (strlen($alt_user)<2) ) or (strlen($search)<2) or (strlen($preview)<2) or (strlen($focus)<2) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_dial not valid");
		$data = "$phone_code|$search|$preview|$focus";
		echo "$result: $result_reason - $value|$data|$agent_user|$alt_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "SELECT campaign_id FROM vicidial_live_agents where user='$agent_user';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vlac_conf_ct = mysqli_num_rows($rslt);
			if ($vlac_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$vac_campaign_id =	$row[0];
				}
			$stmt = "SELECT api_manual_dial FROM vicidial_campaigns where campaign_id='$vac_campaign_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vcc_conf_ct = mysqli_num_rows($rslt);
			if ($vcc_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$api_manual_dial =	$row[0];
				}

			if ($api_manual_dial=='STANDARD')
				{
				$stmt = "select count(*) from vicidial_live_agents where user='$agent_user' and status='PAUSED' and lead_id < 1;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_ready = $row[0];
				}
			else
				{
				$agent_ready=1;
				}
			if ($agent_ready > 0)
				{
				$stmt = "select count(*) from vicidial_users where user='$agent_user' and agentcall_manual>='1';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					if (strlen($group_alias)>1)
						{
						$stmt = "select caller_id_number from groups_alias where group_alias_id='$group_alias';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$VDIG_cidnum_ct = mysqli_num_rows($rslt);
						if ($VDIG_cidnum_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$caller_id_number	= $row[0];
							if ($caller_id_number < 4)
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("caller_id_number from group_alias is not valid");
								$data = "$group_alias|$caller_id_number";
								echo "$result: $result_reason - $agent_user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("group_alias is not valid");
							$data = "$group_alias";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}

					####### Begin Vtiger CallBack Launching #######
					$vtiger_callback_id='';
					if ( (preg_match("/YES/i",$vtiger_callback)) and (preg_match("/^99/",$value)) )
						{
						$value = preg_replace("/^99/",'',$value);
						$value = ($value + 0);

						$stmt = "SELECT enable_vtiger_integration,vtiger_server_ip,vtiger_dbname,vtiger_login,vtiger_pass,vtiger_url FROM system_settings;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$ss_conf_ct = mysqli_num_rows($rslt);
						if ($ss_conf_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$enable_vtiger_integration =	$row[0];
							$vtiger_server_ip	=			$row[1];
							$vtiger_dbname =				$row[2];
							$vtiger_login =					$row[3];
							$vtiger_pass =					$row[4];
							$vtiger_url =					$row[5];
							}

						if ($enable_vtiger_integration > 0)
							{
							$stmt = "SELECT campaign_id FROM vicidial_live_agents where user='$agent_user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vtc_camp_ct = mysqli_num_rows($rslt);
							if ($vtc_camp_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$campaign_id =		$row[0];
								}
							$stmt = "SELECT vtiger_search_category,vtiger_create_call_record,vtiger_create_lead_record,vtiger_search_dead,vtiger_status_call FROM vicidial_campaigns where campaign_id='$campaign_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vtc_conf_ct = mysqli_num_rows($rslt);
							if ($vtc_conf_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$vtiger_search_category =		$row[0];
								$vtiger_create_call_record =	$row[1];
								$vtiger_create_lead_record =	$row[2];
								$vtiger_search_dead =			$row[3];
								$vtiger_status_call =			$row[4];
								}

							### connect to your vtiger database
							$linkV=mysqli_connect("$vtiger_server_ip", "$vtiger_login", "$vtiger_pass", "$vtiger_dbname");

							if (!$linkV) {die("Could not connect: $vtiger_server_ip|$vtiger_dbname|$vtiger_login|$vtiger_pass" . mysqli_connect_error());}
							echo "Connected successfully\n<BR>\n";

							# make sure the ID is present in Vtiger database as an account
							$stmt="SELECT count(*) from vtiger_seactivityrel where activityid='$value';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $linkV);
							$vt_act_ct = mysqli_num_rows($rslt);
							if ($vt_act_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$activity_check = $row[0];
								}
							if ($activity_check > 0)
								{
								$stmt="SELECT crmid from vtiger_seactivityrel where activityid='$value';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $linkV);
								$vt_actsel_ct = mysqli_num_rows($rslt);
								if ($vt_actsel_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$vendor_id = $row[0];
									}
								if (strlen($vendor_id) > 0)
									{
									$stmt="SELECT phone from vtiger_account where accountid='$vendor_id';";
									if ($DB) {echo "$stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $linkV);
									$vt_acct_ct = mysqli_num_rows($rslt);
									if ($vt_acct_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$vtiger_callback_id="$value";
										$value = $row[0];
										}
									}
								}
							else
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("vtiger callback activity does not exist in vtiger system");
								echo "$result: $result_reason - $value\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						}
					####### End Vtiger CallBack Launching #######

					$success=0;
					### If no errors, run the update to place the call ###
					if ($api_manual_dial=='STANDARD')
						{
						$stmt="UPDATE vicidial_live_agents set external_dial='$value!$phone_code!$search!$preview!$focus!$vendor_id!$epoch!$dial_prefix!$group_alias!$caller_id_number!$vtiger_callback_id!$lead_id!$alt_dial!$dial_ingroup' where user='$agent_user';";
						if ($DB) {echo "$stmt\n";}
						$success=1;
						}
					else
						{
						$stmt = "select count(*) from vicidial_manual_dial_queue where user='$agent_user' and phone_number='$value';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						if ($row[0] < 1)
							{
							$stmt="INSERT INTO vicidial_manual_dial_queue set user='$agent_user',phone_number='$value',entry_time=NOW(),status='READY',external_dial='$value!$phone_code!$search!$preview!$focus!$vendor_id!$epoch!$dial_prefix!$group_alias!$caller_id_number!$vtiger_callback_id';";
        						if ($DB) {echo "$stmt\n";}
							$success=1;
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("phone_number is already in this agents manual dial queue");
							echo "$result: $result_reason - $agent_user|$value\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					if ($success > 0)
						{
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("external_dial function set");
						$data = "$phone_code|$search|$preview|$focus|$vendor_id|$epoch|$dial_prefix|$group_alias|$caller_id_number";
						echo "$result: Dispo in Agent Screen FIRST.\n";
						// echo "$result: $result_reason - $value|$agent_user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("agent_user is not allowed to place manual dial calls");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user is not paused");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}

// external_dial
################################################################################
### END - external_dial
################################################################################





if ($format=='debug') 
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}
	
exit; 



##### FUNCTIONS #####

##### Logging #####
function api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data)
	{
	if ($api_logging > 0)
		{
		$NOW_TIME = date("Y-m-d H:i:s");
		$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$agent_user',function='$function',value='$value',result='$result',result_reason='$result_reason',source='$source',data='$data',api_date='$NOW_TIME',api_script='$api_script';";
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	return 1;
	}
?>
