<?php
# crm_example.php - example of using a crm to launch vicidial in a hidden iframe
# 
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGELOG
# 151229-1510 - First Build 
#

require_once("crm_settings.php");

$testing_mode=0;

if (isset($_GET["DB"]))						    {$DB=$_GET["DB"];}
        elseif (isset($_POST["DB"]))            {$DB=$_POST["DB"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
        elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["step"]))						{$step=$_GET["step"];}
        elseif (isset($_POST["step"]))			{$step=$_POST["step"];}
if (isset($_GET["VD_login"]))					{$VD_login=$_GET["VD_login"];}
        elseif (isset($_POST["VD_login"]))      {$VD_login=$_POST["VD_login"];}
if (isset($_GET["VD_pass"]))					{$VD_pass=$_GET["VD_pass"];}
        elseif (isset($_POST["VD_pass"]))       {$VD_pass=$_POST["VD_pass"];}
if (isset($_GET["VD_campaign"]))                {$VD_campaign=$_GET["VD_campaign"];}
        elseif (isset($_POST["VD_campaign"]))   {$VD_campaign=$_POST["VD_campaign"];}
if (isset($_GET["relogin"]))					{$relogin=$_GET["relogin"];}
        elseif (isset($_POST["relogin"]))       {$relogin=$_POST["relogin"];}

if (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["vendor_id"]))	{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))	{$vendor_id=$_POST["vendor_id"];}
	$vendor_lead_code = $vendor_id;
if (isset($_GET["list_id"]))	{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["gmt_offset_now"]))	{$gmt_offset_now=$_GET["gmt_offset_now"];}
	elseif (isset($_POST["gmt_offset_now"]))	{$gmt_offset_now=$_POST["gmt_offset_now"];}
if (isset($_GET["phone_code"]))	{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))	{$phone_code=$_POST["phone_code"];}
if (isset($_GET["phone_number"]))	{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["title"]))	{$title=$_GET["title"];}
	elseif (isset($_POST["title"]))	{$title=$_POST["title"];}
if (isset($_GET["first_name"]))	{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))	{$first_name=$_POST["first_name"];}
if (isset($_GET["middle_initial"]))	{$middle_initial=$_GET["middle_initial"];}
	elseif (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
if (isset($_GET["last_name"]))	{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))	{$last_name=$_POST["last_name"];}
if (isset($_GET["address1"]))	{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))	{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))	{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))	{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))	{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))	{$address3=$_POST["address3"];}
if (isset($_GET["city"]))	{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))	{$city=$_POST["city"];}
if (isset($_GET["state"]))	{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))	{$state=$_POST["state"];}
if (isset($_GET["province"]))	{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))	{$province=$_POST["province"];}
if (isset($_GET["postal_code"]))	{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))	{$postal_code=$_POST["postal_code"];}
if (isset($_GET["country_code"]))	{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))	{$country_code=$_POST["country_code"];}
if (isset($_GET["gender"]))	{$gender=$_GET["gender"];}
	elseif (isset($_POST["gender"]))	{$gender=$_POST["gender"];}
if (isset($_GET["date_of_birth"]))	{$date_of_birth=$_GET["date_of_birth"];}
	elseif (isset($_POST["date_of_birth"]))	{$date_of_birth=$_POST["date_of_birth"];}
if (isset($_GET["alt_phone"]))	{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))	{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["email"]))	{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))	{$email=$_POST["email"];}
if (isset($_GET["security_phrase"]))	{$security_phrase=$_GET["security_phrase"];}
	elseif (isset($_POST["security_phrase"]))	{$security_phrase=$_POST["security_phrase"];}
if (isset($_GET["comments"]))	{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))	{$comments=$_POST["comments"];}
if (isset($_GET["user"]))	{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["pass"]))	{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))	{$pass=$_POST["pass"];}
if (isset($_GET["campaign"]))	{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))	{$campaign=$_POST["campaign"];}
if (isset($_GET["phone_login"]))	{$phone_login=$_GET["phone_login"];}
	elseif (isset($_POST["phone_login"]))	{$phone_login=$_POST["phone_login"];}
if (isset($_GET["original_phone_login"]))	{$original_phone_login=$_GET["original_phone_login"];}
	elseif (isset($_POST["original_phone_login"]))	{$original_phone_login=$_POST["original_phone_login"];}
if (isset($_GET["phone_pass"]))	{$phone_pass=$_GET["phone_pass"];}
	elseif (isset($_POST["phone_pass"]))	{$phone_pass=$_POST["phone_pass"];}
if (isset($_GET["fronter"]))	{$fronter=$_GET["fronter"];}
	elseif (isset($_POST["fronter"]))	{$fronter=$_POST["fronter"];}
if (isset($_GET["closer"]))	{$closer=$_GET["closer"];}
	elseif (isset($_POST["closer"]))	{$closer=$_POST["closer"];}
if (isset($_GET["group"]))	{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))	{$group=$_POST["group"];}
if (isset($_GET["channel_group"]))	{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))	{$channel_group=$_POST["channel_group"];}
if (isset($_GET["SQLdate"]))	{$SQLdate=$_GET["SQLdate"];}
	elseif (isset($_POST["SQLdate"]))	{$SQLdate=$_POST["SQLdate"];}
if (isset($_GET["epoch"]))	{$epoch=$_GET["epoch"];}
	elseif (isset($_POST["epoch"]))	{$epoch=$_POST["epoch"];}
if (isset($_GET["uniqueid"]))	{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["customer_zap_channel"]))	{$customer_zap_channel=$_GET["customer_zap_channel"];}
	elseif (isset($_POST["customer_zap_channel"]))	{$customer_zap_channel=$_POST["customer_zap_channel"];}
if (isset($_GET["customer_server_ip"]))	{$customer_server_ip=$_GET["customer_server_ip"];}
	elseif (isset($_POST["customer_server_ip"]))	{$customer_server_ip=$_POST["customer_server_ip"];}
if (isset($_GET["server_ip"]))	{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["SIPexten"]))	{$SIPexten=$_GET["SIPexten"];}
	elseif (isset($_POST["SIPexten"]))	{$SIPexten=$_POST["SIPexten"];}
if (isset($_GET["session_id"]))	{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["phone"]))	{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))	{$phone=$_POST["phone"];}
if (isset($_GET["parked_by"]))	{$parked_by=$_GET["parked_by"];}
	elseif (isset($_POST["parked_by"]))	{$parked_by=$_POST["parked_by"];}
if (isset($_GET["dispo"]))	{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))	{$dispo=$_POST["dispo"];}
if (isset($_GET["dialed_number"]))	{$dialed_number=$_GET["dialed_number"];}
	elseif (isset($_POST["dialed_number"]))	{$dialed_number=$_POST["dialed_number"];}
if (isset($_GET["dialed_label"]))	{$dialed_label=$_GET["dialed_label"];}
	elseif (isset($_POST["dialed_label"]))	{$dialed_label=$_POST["dialed_label"];}
if (isset($_GET["source_id"]))	{$source_id=$_GET["source_id"];}
	elseif (isset($_POST["source_id"]))	{$source_id=$_POST["source_id"];}
if (isset($_GET["rank"]))	{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))	{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))	{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))	{$owner=$_POST["owner"];}
if (isset($_GET["camp_script"]))	{$camp_script=$_GET["camp_script"];}
	elseif (isset($_POST["camp_script"]))	{$camp_script=$_POST["camp_script"];}
if (isset($_GET["in_script"]))	{$in_script=$_GET["in_script"];}
	elseif (isset($_POST["in_script"]))	{$in_script=$_POST["in_script"];}
if (isset($_GET["script_width"]))	{$script_width=$_GET["script_width"];}
	elseif (isset($_POST["script_width"]))	{$script_width=$_POST["script_width"];}
if (isset($_GET["script_height"]))	{$script_height=$_GET["script_height"];}
	elseif (isset($_POST["script_height"]))	{$script_height=$_POST["script_height"];}
if (isset($_GET["fullname"]))	{$fullname=$_GET["fullname"];}
	elseif (isset($_POST["fullname"]))	{$fullname=$_POST["fullname"];}
if (isset($_GET["agent_email"]))	{$agent_email=$_GET["agent_email"];}
	elseif (isset($_POST["agent_email"]))	{$agent_email=$_POST["agent_email"];}
if (isset($_GET["recording_filename"]))	{$recording_filename=$_GET["recording_filename"];}
	elseif (isset($_POST["recording_filename"]))	{$recording_filename=$_POST["recording_filename"];}
if (isset($_GET["recording_id"]))	{$recording_id=$_GET["recording_id"];}
	elseif (isset($_POST["recording_id"]))	{$recording_id=$_POST["recording_id"];}
if (isset($_GET["user_custom_one"]))	{$user_custom_one=$_GET["user_custom_one"];}
	elseif (isset($_POST["user_custom_one"]))	{$user_custom_one=$_POST["user_custom_one"];}
if (isset($_GET["user_custom_two"]))	{$user_custom_two=$_GET["user_custom_two"];}
	elseif (isset($_POST["user_custom_two"]))	{$user_custom_two=$_POST["user_custom_two"];}
if (isset($_GET["user_custom_three"]))	{$user_custom_three=$_GET["user_custom_three"];}
	elseif (isset($_POST["user_custom_three"]))	{$user_custom_three=$_POST["user_custom_three"];}
if (isset($_GET["user_custom_four"]))	{$user_custom_four=$_GET["user_custom_four"];}
	elseif (isset($_POST["user_custom_four"]))	{$user_custom_four=$_POST["user_custom_four"];}
if (isset($_GET["user_custom_five"]))	{$user_custom_five=$_GET["user_custom_five"];}
	elseif (isset($_POST["user_custom_five"]))	{$user_custom_five=$_POST["user_custom_five"];}
if (isset($_GET["preset_number_a"]))	{$preset_number_a=$_GET["preset_number_a"];}
	elseif (isset($_POST["preset_number_a"]))	{$preset_number_a=$_POST["preset_number_a"];}
if (isset($_GET["preset_number_b"]))	{$preset_number_b=$_GET["preset_number_b"];}
	elseif (isset($_POST["preset_number_b"]))	{$preset_number_b=$_POST["preset_number_b"];}
if (isset($_GET["preset_number_c"]))	{$preset_number_c=$_GET["preset_number_c"];}
	elseif (isset($_POST["preset_number_c"]))	{$preset_number_c=$_POST["preset_number_c"];}
if (isset($_GET["preset_number_d"]))	{$preset_number_d=$_GET["preset_number_d"];}
	elseif (isset($_POST["preset_number_d"]))	{$preset_number_d=$_POST["preset_number_d"];}
if (isset($_GET["preset_number_e"]))	{$preset_number_e=$_GET["preset_number_e"];}
	elseif (isset($_POST["preset_number_e"]))	{$preset_number_e=$_POST["preset_number_e"];}
if (isset($_GET["preset_number_f"]))	{$preset_number_f=$_GET["preset_number_f"];}
	elseif (isset($_POST["preset_number_f"]))	{$preset_number_f=$_POST["preset_number_f"];}
if (isset($_GET["preset_dtmf_a"]))	{$preset_dtmf_a=$_GET["preset_dtmf_a"];}
	elseif (isset($_POST["preset_dtmf_a"]))	{$preset_dtmf_a=$_POST["preset_dtmf_a"];}
if (isset($_GET["preset_dtmf_b"]))	{$preset_dtmf_b=$_GET["preset_dtmf_b"];}
	elseif (isset($_POST["preset_dtmf_b"]))	{$preset_dtmf_b=$_POST["preset_dtmf_b"];}
if (isset($_GET["did_id"]))				{$did_id=$_GET["did_id"];}
	elseif (isset($_POST["did_id"]))	{$did_id=$_POST["did_id"];}
if (isset($_GET["did_extension"]))			{$did_extension=$_GET["did_extension"];}
	elseif (isset($_POST["did_extension"]))	{$did_extension=$_POST["did_extension"];}
if (isset($_GET["did_pattern"]))			{$did_pattern=$_GET["did_pattern"];}
	elseif (isset($_POST["did_pattern"]))	{$did_pattern=$_POST["did_pattern"];}
if (isset($_GET["did_description"]))			{$did_description=$_GET["did_description"];}
	elseif (isset($_POST["did_description"]))	{$did_description=$_POST["did_description"];}
if (isset($_GET["closecallid"]))			{$closecallid=$_GET["closecallid"];}
	elseif (isset($_POST["closecallid"]))	{$closecallid=$_POST["closecallid"];}
if (isset($_GET["xfercallid"]))				{$xfercallid=$_GET["xfercallid"];}
	elseif (isset($_POST["xfercallid"]))	{$xfercallid=$_POST["xfercallid"];}
if (isset($_GET["agent_log_id"]))			{$agent_log_id=$_GET["agent_log_id"];}
	elseif (isset($_POST["agent_log_id"]))	{$agent_log_id=$_POST["agent_log_id"];}
if (isset($_GET["ScrollDIV"]))			{$ScrollDIV=$_GET["ScrollDIV"];}
	elseif (isset($_POST["ScrollDIV"]))	{$ScrollDIV=$_POST["ScrollDIV"];}
if (isset($_GET["ignore_list_script"]))				{$ignore_list_script=$_GET["ignore_list_script"];}
	elseif (isset($_POST["ignore_list_script"]))	{$ignore_list_script=$_POST["ignore_list_script"];}
if (isset($_GET["CF_uses_custom_fields"]))			{$CF_uses_custom_fields=$_GET["CF_uses_custom_fields"];}
	elseif (isset($_POST["CF_uses_custom_fields"]))	{$CF_uses_custom_fields=$_POST["CF_uses_custom_fields"];}
if (isset($_GET["entry_list_id"]))			{$entry_list_id=$_GET["entry_list_id"];}
	elseif (isset($_POST["entry_list_id"]))	{$entry_list_id=$_POST["entry_list_id"];}
if (isset($_GET["call_id"]))			{$call_id=$_GET["call_id"];}
	elseif (isset($_POST["call_id"]))	{$call_id=$_POST["call_id"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["web_vars"]))			{$web_vars=$_GET["web_vars"];}
	elseif (isset($_POST["web_vars"]))	{$web_vars=$_POST["web_vars"];}
if (isset($_GET["orig_pass"]))			{$orig_pass=$_GET["orig_pass"];}
	elseif (isset($_POST["orig_pass"]))	{$orig_pass=$_POST["orig_pass"];}
if (isset($_GET["called_count"]))			{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))	{$called_count=$_POST["called_count"];}
if (isset($_GET["script_override"]))			{$script_override=$_GET["script_override"];}
	elseif (isset($_POST["script_override"]))	{$script_override=$_POST["script_override"];}
if (isset($_GET["session_name"]))			{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))	{$session_name=$_POST["session_name"];}
if (isset($_GET["entry_date"]))				{$entry_date=$_GET["entry_date"];}
	elseif (isset($_POST["entry_date"]))	{$entry_date=$_POST["entry_date"];}
if (isset($_GET["did_custom_one"]))				{$did_custom_one=$_GET["did_custom_one"];}
	elseif (isset($_POST["did_custom_one"]))	{$did_custom_one=$_POST["did_custom_one"];}
if (isset($_GET["did_custom_two"]))				{$did_custom_two=$_GET["did_custom_two"];}
	elseif (isset($_POST["did_custom_two"]))	{$did_custom_two=$_POST["did_custom_two"];}
if (isset($_GET["did_custom_three"]))			{$did_custom_three=$_GET["did_custom_three"];}
	elseif (isset($_POST["did_custom_three"]))	{$did_custom_three=$_POST["did_custom_three"];}
if (isset($_GET["did_custom_four"]))			{$did_custom_four=$_GET["did_custom_four"];}
	elseif (isset($_POST["did_custom_four"]))	{$did_custom_four=$_POST["did_custom_four"];}
if (isset($_GET["did_custom_five"]))			{$did_custom_five=$_GET["did_custom_five"];}
	elseif (isset($_POST["did_custom_five"]))	{$did_custom_five=$_POST["did_custom_five"];}

if (!isset($phone_login)) 
	{
	if (isset($_GET["pl"]))                {$phone_login=$_GET["pl"];}
			elseif (isset($_POST["pl"]))   {$phone_login=$_POST["pl"];}
	}
if (!isset($phone_pass))
	{
	if (isset($_GET["pp"]))                {$phone_pass=$_GET["pp"];}
			elseif (isset($_POST["pp"]))   {$phone_pass=$_POST["pp"];}
	}

$VD_login=preg_replace("/\'|\"|\\\\|;| /","",$VD_login);

### security strip all non-alphanumeric characters out of the variables ###
$DB=preg_replace("/[^0-9a-z]/","",$DB);
$stage=preg_replace("/[^\,0-9a-zA-Z]/","",$stage);
$step=preg_replace("/[^-_0-9a-zA-Z]/","",$step);
$VD_login=preg_replace("/\'|\"|\\\\|;| /","",$VD_login);
$VD_pass=preg_replace("/\'|\"|\\\\|;| /","",$VD_pass);
$VD_campaign = preg_replace("/[^-_0-9a-zA-Z]/","",$VD_campaign);

$StarTtimE = date("U");
$NOW_TIME = date("Y-m-d H:i:s");
$PHP_SELF=$_SERVER['PHP_SELF'];
$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
$CL=':';
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
else {$server_port = "$CL$server_port";}
$crmPAGE = "$HTTPprotocol$server_name$server_port$script_name";


?>
	<script language="Javascript">
	
	var needToConfirmExit = true;
	var MTvar;
	var NOW_TIME = '<?php echo $NOW_TIME ?>';
	var SQLdate = '<?php echo $NOW_TIME ?>';
	var StarTtimE = '<?php echo $StarTtimE ?>';
	var UnixTime = '<?php echo $StarTtimE ?>';
	var UnixTimeMS = 0;
	</script>

<?php
echo "</head>\n";

$zi=2;

##### Welcome screen, with links to RESUME or LOGOUT #####
if ($stage == 'welcome')
	{
	echo "Welcome to the CRM!\n";
	echo "<BR><BR>\n";
	sleep(5);
	echo "<a href=\"$crmPAGE?stage=resume&VD_login=$VD_login\">Start taking calls</a>\n";
	echo "<BR><BR>\n";
	echo "<a href=\"$crmPAGE?stage=logout&VD_login=$VD_login\">Log out</a>\n";
	echo "\n";
	exit;
	}

##### Logout screen, with link to log in screen #####
if ($stage == 'logout')
	{
	echo "Logging out of the CRM...\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=LOGOUT&user=$api_user&pass=$api_pass&function=logout&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	echo "<a href=\"$front_url?VD_login=$VD_login\" target=\"_parent\">Back to log in screen</a>\n";
	echo "\n";
	exit;
	}

##### Hangup call screen, with links to disposition the call #####
if ($stage == 'hangup')
	{
	echo "Call has been hung up\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=1&user=$api_user&pass=$api_pass&function=external_hangup&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	echo "<a href=\"$crmPAGE?stage=dispo&dispo=SALE&VD_login=$VD_login\">Set call as a SALE</a>\n";
	echo "<BR><BR>\n";
	echo "<a href=\"$crmPAGE?stage=dispo&dispo=NP&VD_login=$VD_login\">Set call as a NO SALE</a>\n";

	echo "\n";
	exit;
	}

##### Disposition call screen with pause after call #####
if ($stage == 'hanguppause')
	{
	$API_logout_URL = "$api_url?source=crmexample&value=PAUSE&user=$api_user&pass=$api_pass&function=external_pause&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	echo "Call has been hung up\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=1&user=$api_user&pass=$api_pass&function=external_hangup&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	echo "<a href=\"$crmPAGE?stage=dispo&dispo=SALE&step=PAUSE&VD_login=$VD_login\">Set call as a SALE</a>\n";
	echo "<BR><BR>\n";
	echo "<a href=\"$crmPAGE?stage=dispo&dispo=NP&step=PAUSE&VD_login=$VD_login\">Set call as a NO SALE</a>\n";

	echo "\n";
	exit;
	}

##### Disposition call screen #####
if ($stage == 'dispo')
	{
	echo "Call has been set as status $dispo\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=$dispo&user=$api_user&pass=$api_pass&function=external_status&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	if ($step == 'PAUSE')
		{
		echo "<a href=\"$crmPAGE?stage=resume&VD_login=$VD_login\">Start taking calls</a>\n";
		echo "<BR><BR>\n";
		echo "<a href=\"$crmPAGE?stage=logout&VD_login=$VD_login\">Log out</a>\n";
		echo "\n";
		}
	else
		{
		echo "You are now ACTIVE!\n";
		echo "<BR><BR>\n";

		echo "<a href=\"$crmPAGE?stage=pause&VD_login=$VD_login\">Stop taking calls</a>\n";
		echo "\n";
		}
	exit;
	}

##### Resume screen, to stop taking calls, with link to pause #####
if ($stage == 'resume')
	{
	echo "You are now ACTIVE!\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=RESUME&user=$api_user&pass=$api_pass&function=external_pause&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output[0]<BR>\n";

	sleep(2);

	echo "<a href=\"$crmPAGE?stage=pause&VD_login=$VD_login\">Stop taking calls</a>\n";
	echo "\n";
	exit;
	}

##### Pause screen, to start taking calls or log out #####
if ($stage == 'pause')
	{
	echo "You are now PAUSED!\n";
	echo "<BR><BR>\n";
	
	$API_logout_URL = "$api_url?source=crmexample&value=PAUSE&user=$api_user&pass=$api_pass&function=external_pause&agent_user=$VD_login";
	$API_output = file($API_logout_URL);

	#	echo "$API_logout_URL<BR>\n";
	#	echo "$API_output$API_output[0]<BR>\n";

	sleep(2);

	echo "<a href=\"$crmPAGE?stage=resume&VD_login=$VD_login\">Start taking calls</a>\n";
	echo "<BR><BR>\n";
	echo "<a href=\"$crmPAGE?stage=logout&VD_login=$VD_login\">Log out</a>\n";
	echo "\n";
	exit;
	}

##### Update customer information, hangup the call and give dispo options #####
if ($stage == 'hangupupdate')
	{
	# encode variables before sending
	if ($IFRAMEencode=='1')
		{
		$lead_id = urlencode(trim($lead_id));
		$vendor_id = urlencode(trim($vendor_id));
		$vendor_lead_code = urlencode(trim($vendor_lead_code));
		$list_id = urlencode(trim($list_id));
		$list_name = urlencode(trim($list_name));
		$list_description = urlencode(trim($list_description));
		$gmt_offset_now = urlencode(trim($gmt_offset_now));
		$phone_code = urlencode(trim($phone_code));
		$phone_number = urlencode(trim($phone_number));
		$title = urlencode(trim($title));
		$first_name = urlencode(trim($first_name));
		$middle_initial = urlencode(trim($middle_initial));
		$last_name = urlencode(trim($last_name));
		$address1 = urlencode(trim($address1));
		$address2 = urlencode(trim($address2));
		$address3 = urlencode(trim($address3));
		$city = urlencode(trim($city));
		$state = urlencode(trim($state));
		$province = urlencode(trim($province));
		$postal_code = urlencode(trim($postal_code));
		$country_code = urlencode(trim($country_code));
		$gender = urlencode(trim($gender));
		$date_of_birth = urlencode(trim($date_of_birth));
		$alt_phone = urlencode(trim($alt_phone));
		$email = urlencode(trim($email));
		$security_phrase = urlencode(trim($security_phrase));
		$comments = urlencode(trim($comments));
		$campaign = urlencode(trim($campaign));
		$phone_login = urlencode(trim($phone_login));
		$original_phone_login = urlencode(trim($original_phone_login));
		$phone_pass = urlencode(trim($phone_pass));
		$fronter = urlencode(trim($fronter));
		$closer = urlencode(trim($closer));
		$group = urlencode(trim($group));
		$channel_group = urlencode(trim($channel_group));
		$SQLdate = urlencode(trim($SQLdate));
		$epoch = urlencode(trim($epoch));
		$uniqueid = urlencode(trim($uniqueid));
		$customer_zap_channel = urlencode(trim($customer_zap_channel));
		$customer_server_ip = urlencode(trim($customer_server_ip));
		$server_ip = urlencode(trim($server_ip));
		$SIPexten = urlencode(trim($SIPexten));
		$session_id = urlencode(trim($session_id));
		$phone = urlencode(trim($phone));
		$parked_by = urlencode(trim($parked_by));
		$dispo = urlencode(trim($dispo));
		$dialed_number = urlencode(trim($dialed_number));
		$dialed_label = urlencode(trim($dialed_label));
		$source_id = urlencode(trim($source_id));
		$rank = urlencode(trim($rank));
		$owner = urlencode(trim($owner));
		$camp_script = urlencode(trim($camp_script));
		$in_script = urlencode(trim($in_script));
		$script_width = urlencode(trim($script_width));
		$script_height = urlencode(trim($script_height));
		$fullname = urlencode(trim($fullname));
		$agent_email = urlencode(trim($agent_email));
		$recording_filename = urlencode(trim($recording_filename));
		$recording_id = urlencode(trim($recording_id));
		$user_custom_one = urlencode(trim($user_custom_one));
		$user_custom_two = urlencode(trim($user_custom_two));
		$user_custom_three = urlencode(trim($user_custom_three));
		$user_custom_four = urlencode(trim($user_custom_four));
		$user_custom_five = urlencode(trim($user_custom_five));
		$preset_number_a = urlencode(trim($preset_number_a));
		$preset_number_b = urlencode(trim($preset_number_b));
		$preset_number_c = urlencode(trim($preset_number_c));
		$preset_number_d = urlencode(trim($preset_number_d));
		$preset_number_e = urlencode(trim($preset_number_e));
		$preset_number_f = urlencode(trim($preset_number_f));
		$preset_dtmf_a = urlencode(trim($preset_dtmf_a));
		$preset_dtmf_b = urlencode(trim($preset_dtmf_b));
		$did_id = urlencode(trim($did_id));
		$did_extension = urlencode(trim($did_extension));
		$did_pattern = urlencode(trim($did_pattern));
		$did_description = urlencode(trim($did_description));
		$called_count = urlencode(trim($called_count));
		$session_name = urlencode(trim($session_name));
		$entry_date = urlencode(trim($entry_date));
		$did_custom_one = urlencode(trim($did_custom_one));
		$did_custom_two = urlencode(trim($did_custom_two));
		$did_custom_three = urlencode(trim($did_custom_three));
		$did_custom_four = urlencode(trim($did_custom_four));
		$did_custom_five = urlencode(trim($did_custom_five));
		$web_vars = urlencode(trim($web_vars));
		}
	else
		{
		$lead_id = preg_replace('/\s/i','+',$lead_id);
		$vendor_id = preg_replace('/\s/i','+',$vendor_id);
		$vendor_lead_code = preg_replace('/\s/i','+',$vendor_lead_code);
		$list_id = preg_replace('/\s/i','+',$list_id);
		$list_name = preg_replace('/\s/i','+',$list_name);
		$list_description = preg_replace('/\s/i','+',$list_description);
		$gmt_offset_now = preg_replace('/\s/i','+',$gmt_offset_now);
		$phone_code = preg_replace('/\s/i','+',$phone_code);
		$phone_number = preg_replace('/\s/i','+',$phone_number);
		$title = preg_replace('/\s/i','+',$title);
		$first_name = preg_replace('/\s/i','+',$first_name);
		$middle_initial = preg_replace('/\s/i','+',$middle_initial);
		$last_name = preg_replace('/\s/i','+',$last_name);
		$address1 = preg_replace('/\s/i','+',$address1);
		$address2 = preg_replace('/\s/i','+',$address2);
		$address3 = preg_replace('/\s/i','+',$address3);
		$city = preg_replace('/\s/i','+',$city);
		$state = preg_replace('/\s/i','+',$state);
		$province = preg_replace('/\s/i','+',$province);
		$postal_code = preg_replace('/\s/i','+',$postal_code);
		$country_code = preg_replace('/\s/i','+',$country_code);
		$gender = preg_replace('/\s/i','+',$gender);
		$date_of_birth = preg_replace('/\s/i','+',$date_of_birth);
		$alt_phone = preg_replace('/\s/i','+',$alt_phone);
		$email = preg_replace('/\s/i','+',$email);
		$security_phrase = preg_replace('/\s/i','+',$security_phrase);
		$comments = preg_replace('/\s/i','+',$comments);
		$campaign = preg_replace('/\s/i','+',$campaign);
		$phone_login = preg_replace('/\s/i','+',$phone_login);
		$original_phone_login = preg_replace('/\s/i','+',$original_phone_login);
		$phone_pass = preg_replace('/\s/i','+',$phone_pass);
		$fronter = preg_replace('/\s/i','+',$fronter);
		$closer = preg_replace('/\s/i','+',$closer);
		$group = preg_replace('/\s/i','+',$group);
		$channel_group = preg_replace('/\s/i','+',$channel_group);
		$SQLdate = preg_replace('/\s/i','+',$SQLdate);
		$epoch = preg_replace('/\s/i','+',$epoch);
		$uniqueid = preg_replace('/\s/i','+',$uniqueid);
		$customer_zap_channel = preg_replace('/\s/i','+',$customer_zap_channel);
		$customer_server_ip = preg_replace('/\s/i','+',$customer_server_ip);
		$server_ip = preg_replace('/\s/i','+',$server_ip);
		$SIPexten = preg_replace('/\s/i','+',$SIPexten);
		$session_id = preg_replace('/\s/i','+',$session_id);
		$phone = preg_replace('/\s/i','+',$phone);
		$parked_by = preg_replace('/\s/i','+',$parked_by);
		$dispo = preg_replace('/\s/i','+',$dispo);
		$dialed_number = preg_replace('/\s/i','+',$dialed_number);
		$dialed_label = preg_replace('/\s/i','+',$dialed_label);
		$source_id = preg_replace('/\s/i','+',$source_id);
		$rank = preg_replace('/\s/i','+',$rank);
		$owner = preg_replace('/\s/i','+',$owner);
		$camp_script = preg_replace('/\s/i','+',$camp_script);
		$in_script = preg_replace('/\s/i','+',$in_script);
		$script_width = preg_replace('/\s/i','+',$script_width);
		$script_height = preg_replace('/\s/i','+',$script_height);
		$fullname = preg_replace('/\s/i','+',$fullname);
		$agent_email = preg_replace('/\s/i','+',$agent_email);
		$recording_filename = preg_replace('/\s/i','+',$recording_filename);
		$recording_id = preg_replace('/\s/i','+',$recording_id);
		$user_custom_one = preg_replace('/\s/i','+',$user_custom_one);
		$user_custom_two = preg_replace('/\s/i','+',$user_custom_two);
		$user_custom_three = preg_replace('/\s/i','+',$user_custom_three);
		$user_custom_four = preg_replace('/\s/i','+',$user_custom_four);
		$user_custom_five = preg_replace('/\s/i','+',$user_custom_five);
		$preset_number_a = preg_replace('/\s/i','+',$preset_number_a);
		$preset_number_b = preg_replace('/\s/i','+',$preset_number_b);
		$preset_number_c = preg_replace('/\s/i','+',$preset_number_c);
		$preset_number_d = preg_replace('/\s/i','+',$preset_number_d);
		$preset_number_e = preg_replace('/\s/i','+',$preset_number_e);
		$preset_number_f = preg_replace('/\s/i','+',$preset_number_f);
		$preset_dtmf_a = preg_replace('/\s/i','+',$preset_dtmf_a);
		$preset_dtmf_b = preg_replace('/\s/i','+',$preset_dtmf_b);
		$did_id = preg_replace('/\s/i','+',$did_id);
		$did_extension = preg_replace('/\s/i','+',$did_extension);
		$did_pattern = preg_replace('/\s/i','+',$did_pattern);
		$did_description = preg_replace('/\s/i','+',$did_description);
		$called_count = preg_replace('/\s/i','+',$called_count);
		$session_name = preg_replace('/\s/i','+',$session_name);
		$entry_date = preg_replace('/\s/i','+',$entry_date);
		$did_custom_one = preg_replace('/\s/i','+',$did_custom_one);
		$did_custom_two = preg_replace('/\s/i','+',$did_custom_two);
		$did_custom_three = preg_replace('/\s/i','+',$did_custom_three);
		$did_custom_four = preg_replace('/\s/i','+',$did_custom_four);
		$did_custom_five = preg_replace('/\s/i','+',$did_custom_five);
		$web_vars = preg_replace('/\s/i','+',$web_vars);
		}

	$API_update_URL = "$api_url?source=crmexample&function=update_fields&user=$api_user&pass=$api_pass&agent_user=$VD_login&first_name=$first_name&middle_initial=$middle_initial&last_name=$last_name&address1=$address1&address2=$address2&address3=$address3&city=$city&state=$state&postal_code=$postal_code&email=$email&security_phrase=$security_phrase&phone_number=$phone_number";
	$API_output = file($API_update_URL);

	#	echo "$API_update_URL<BR>\n";
	#	echo "$API_output[0] $API_output[1]<BR>\n";

	sleep(2);

	echo "Customer information updated<BR>\n";

	echo "<BR><BR>\n";
	
	if (isset($_GET["after_call"]))				{$after_call=$_GET["after_call"];}
		elseif (isset($_POST["after_call"]))	{$after_call=$_POST["after_call"];}
	$k=0;
	$multi_count = count($after_call);
	$multi_array = $after_call;
	while ($k < $multi_count)
		{
		$new_field_value .= "$multi_array[$k],";
		$k++;
		}
	$after_call_value = preg_replace("/,$/","",$new_field_value);
	if (preg_match("/PAUSE/",$after_call_value))
		{
		$API_logout_URL = "$api_url?source=crmexample&value=PAUSE&user=$api_user&pass=$api_pass&function=external_pause&agent_user=$VD_login";
		$API_output = file($API_logout_URL);

		#	echo "$API_logout_URL<BR>\n";
		#	echo "$API_output$API_output[0]<BR>\n";

		sleep(2);

		$API_logout_URL = "$api_url?source=crmexample&value=1&user=$api_user&pass=$api_pass&function=external_hangup&agent_user=$VD_login";
		$API_output = file($API_logout_URL);

		#	echo "$API_logout_URL<BR>\n";
		#	echo "$API_output$API_output[0]<BR>\n";

		sleep(2);

		echo "Call has been hung up\n";
		echo "<BR><BR>\n";

		echo "<a href=\"$crmPAGE?stage=dispo&dispo=SALE&step=PAUSE&VD_login=$VD_login\">Set call as a SALE</a>\n";
		echo "<BR><BR>\n";
		echo "<a href=\"$crmPAGE?stage=dispo&dispo=NP&step=PAUSE&VD_login=$VD_login\">Set call as a NO SALE</a>\n";
		echo "\n";
		exit;
		}
	else
		{
		$API_logout_URL = "$api_url?source=crmexample&value=1&user=$api_user&pass=$api_pass&function=external_hangup&agent_user=$VD_login";
		$API_output = file($API_logout_URL);

		#	echo "$API_logout_URL<BR>\n";
		#	echo "$API_output$API_output[0]<BR>\n";

		sleep(2);

		echo "Call has been hung up\n";
		echo "<BR><BR>\n";

		echo "<a href=\"$crmPAGE?stage=dispo&dispo=SALE&VD_login=$VD_login\">Set call as a SALE</a>\n";
		echo "<BR><BR>\n";
		echo "<a href=\"$crmPAGE?stage=dispo&dispo=NP&VD_login=$VD_login\">Set call as a NO SALE</a>\n";
		echo "\n";
		exit;
		}

	}

##### Lead infomation screen, launched by webform, with links to hang up call #####
if ($lead_id > 0)
	{
	if ($testing_mode > 0)
		{
		echo "variable output test:<br>\n";
		echo "<table border=0 cellspacing=3 cellpadding=3><tr><td>\n";
		echo "<font size=1>\n";
		echo "lead_id: $lead_id<BR>\n";
		echo "vendor_id: $vendor_id<BR>\n";
		echo "vendor_lead_code: $vendor_lead_code<BR>\n";
		echo "list_id: $list_id<BR>\n";
		echo "list_name: $list_name<BR>\n";
		echo "list_description: $list_description<BR>\n";
		echo "gmt_offset_now: $gmt_offset_now<BR>\n";
		echo "phone_code: $phone_code<BR>\n";
		echo "phone_number: $phone_number<BR>\n";
		echo "title: $title<BR>\n";
		echo "first_name: $first_name<BR>\n";
		echo "middle_initial: $middle_initial<BR>\n";
		echo "last_name: $last_name<BR>\n";
		echo "address1: $address1<BR>\n";
		echo "address2: $address2<BR>\n";
		echo "address3: $address3<BR>\n";
		echo "city: $city<BR>\n";
		echo "state: $state<BR>\n";
		echo "province: $province<BR>\n";
		echo "postal_code: $postal_code<BR>\n";
		echo "country_code: $country_code<BR>\n";
		echo "gender: $gender<BR>\n";
		echo "date_of_birth: $date_of_birth<BR>\n";
		echo "alt_phone: $alt_phone<BR>\n";
		echo "email: $email<BR>\n";
		echo "security_phrase: $security_phrase<BR>\n";
		echo "comments: $comments<BR>\n";
		echo "campaign: $campaign<BR>\n";
		echo "phone_login: $phone_login<BR>\n";
		echo "original_phone_login: $original_phone_login<BR>\n";
		echo "phone_pass: $phone_pass<BR>\n";
		echo "fronter: $fronter<BR>\n";
		echo "closer: $closer<BR>\n";
		echo "group: $group<BR>\n";
		echo "channel_group: $channel_group<BR>\n";
		echo "SQLdate: $SQLdate<BR>\n";
		echo "epoch: $epoch<BR>\n";
		echo "uniqueid: $uniqueid<BR>\n";
		echo "customer_zap_channel: $customer_zap_channel<BR>\n";
		echo "customer_server_ip: $customer_server_ip<BR>\n";
		echo "server_ip: $server_ip<BR>\n";
		echo "SIPexten: $SIPexten<BR>\n";
		echo "session_id: $session_id<BR>\n";

		echo "</td><td>\n";
		echo "<font size=1>\n";
		echo "phone: $phone<BR>\n";
		echo "parked_by: $parked_by<BR>\n";
		echo "dispo: $dispo<BR>\n";
		echo "dialed_number: $dialed_number<BR>\n";
		echo "dialed_label: $dialed_label<BR>\n";
		echo "source_id: $source_id<BR>\n";
		echo "rank: $rank<BR>\n";
		echo "owner: $owner<BR>\n";
		echo "camp_script: $camp_script<BR>\n";
		echo "in_script: $in_script<BR>\n";
		echo "script_width: $script_width<BR>\n";
		echo "script_height: $script_height<BR>\n";
		echo "fullname: $fullname<BR>\n";
		echo "agent_email: $agent_email<BR>\n";
		echo "recording_filename: $recording_filename<BR>\n";
		echo "recording_id: $recording_id<BR>\n";
		echo "user_custom_one: $user_custom_one<BR>\n";
		echo "user_custom_two: $user_custom_two<BR>\n";
		echo "user_custom_three: $user_custom_three<BR>\n";
		echo "user_custom_four: $user_custom_four<BR>\n";
		echo "user_custom_five: $user_custom_five<BR>\n";
		echo "preset_number_a: $preset_number_a<BR>\n";
		echo "preset_number_b: $preset_number_b<BR>\n";
		echo "preset_number_c: $preset_number_c<BR>\n";
		echo "preset_number_d: $preset_number_d<BR>\n";
		echo "preset_number_e: $preset_number_e<BR>\n";
		echo "preset_number_f: $preset_number_f<BR>\n";
		echo "preset_dtmf_a: $preset_dtmf_a<BR>\n";
		echo "preset_dtmf_b: $preset_dtmf_b<BR>\n";
		echo "did_id: $did_id<BR>\n";
		echo "did_extension: $did_extension<BR>\n";
		echo "did_pattern: $did_pattern<BR>\n";
		echo "did_description: $did_description<BR>\n";
		echo "called_count: $called_count<BR>\n";
		echo "session_name: $session_name<BR>\n";
		echo "entry_date: $entry_date<BR>\n";
		echo "did_custom_one: $did_custom_one<BR>\n";
		echo "did_custom_two: $did_custom_two<BR>\n";
		echo "did_custom_three: $did_custom_three<BR>\n";
		echo "did_custom_four: $did_custom_four<BR>\n";
		echo "did_custom_five: $did_custom_five<BR>\n";
		echo "user: $user<BR>\n";
		echo "web_vars: $web_vars<BR>\n";
		echo "</font>\n";
		echo "</td></tr></table>\n";

		echo "<a href=\"$crmPAGE?stage=hangup&VD_login=$user\">Hang up this call</a>\n";
		echo "<BR><BR>\n";
		echo "<a href=\"$crmPAGE?stage=hanguppause&VD_login=$user\">Hang up this call and stop taking calls</a>\n";
		}

    echo "<form name=\"crm_form\" id=\"crm_form\" action=\"$crmPAGE\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"DB\" id=\"DB\" value=\"$DB\" />\n";
    echo "<input type=\"hidden\" name=\"lead_id\" id=\"lead_id\" value=\"$lead_id\" />\n";
    echo "<input type=\"hidden\" name=\"VD_login\" id=\"VD_login\" value=\"$user\" />\n";
    echo "<input type=\"hidden\" name=\"stage\" id=\"stage\" value=\"hangupupdate\" />\n";
    echo "<br /><br /><br /><center><table width=\"460px\" cellpadding=\"0\" cellspacing=\"0\" bgcolor=\"#E6E6E6\"><tr bgcolor=\"white\">";
    echo "<td align=\"left\" valign=\"bottom\" colspan=2>Customer Information:</td>";
    echo "</tr>\n";
    echo "<tr><td align=\"left\" colspan=\"2\"><font size=\"1\"> &nbsp; </font></td></tr>\n";
    echo "<tr><td align=\"right\">Name: </td>";
    echo "<td align=\"left\">\n";
    echo "<input type=\"text\" name=\"first_name\" size=\"10\" maxlength=\"30\" value=\"$first_name\" /> &nbsp;\n";
    echo "<input type=\"text\" name=\"middle_initial\" size=\"1\" maxlength=\"1\" value=\"$middle_initial\" /> &nbsp;\n";
    echo "<input type=\"text\" name=\"last_name\" size=\"15\" maxlength=\"30\" value=\"$last_name\" /> &nbsp;\n";
    echo "</td></tr>\n";
    echo "<tr><td align=\"right\" valign=\"top\">Address: </td>";
    echo "<td align=\"left\">\n";
    echo "<input type=\"text\" name=\"address1\" size=\"25\" maxlength=\"100\" value=\"$address1\" /> <br>\n";
    echo "<input type=\"text\" name=\"address2\" size=\"25\" maxlength=\"100\" value=\"$address2\" /> <br>\n";
    echo "<input type=\"text\" name=\"address3\" size=\"25\" maxlength=\"100\" value=\"$address3\" /> <br>\n";
    echo "</td></tr>\n";
    echo "<tr><td align=\"right\">City, State, Zip: </td>";
    echo "<td align=\"left\">\n";
    echo "<input type=\"text\" name=\"city\" size=\"20\" maxlength=\"50\" value=\"$city\" /> &nbsp;\n";
    echo "<input type=\"text\" name=\"state\" size=\"2\" maxlength=\"2\" value=\"$state\" /> &nbsp;\n";
    echo "<input type=\"text\" name=\"postal_code\" size=\"10\" maxlength=\"10\" value=\"$postal_code\" /> &nbsp;\n";
    echo "</td></tr>\n";
    echo "<tr><td align=\"right\">Phone number:  </td>";
    echo "<td align=\"left\"><input type=\"text\" name=\"phone_number\" size=\"15\" maxlength=\"17\" value=\"$phone_number\" /></td></tr>\n";
    echo "<tr><td align=\"right\">Email:  </td>";
    echo "<td align=\"left\"><input type=\"text\" name=\"email\" size=\"30\" maxlength=\"70\" value=\"$email\" /></td></tr>\n";
    echo "<tr><td align=\"right\">Security:  </td>";
    echo "<td align=\"left\"><input type=\"text\" name=\"security_phrase\" size=\"30\" maxlength=\"100\" value=\"$security_phrase\" /></td></tr>\n";

    echo "<tr><td align=\"center\" colspan=2>Stay active: <input type=RADIO name=after_call[] id=after_call[] value=\"RESUME\" CHECKED> &nbsp Stop taking calls:
<input type=RADIO name=after_call[] id=after_call[] value=\"PAUSE\"></td></tr>";

    echo "<tr><td align=\"center\" colspan=\"2\"><input type=\"submit\" name=\"SUBMIT\" value=\"HANG UP CALL\" /> &nbsp; </td></tr>\n";
    echo "</table></center>\n";
    echo "</form>\n\n";

	echo "";


	exit;
	}

?>
