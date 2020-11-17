<?php
# vdc_LTT_generate.php
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to generate a list of wav audio files to play and then
# insert a record into the vicidial_lead_messages table. This web page should be
# launched from the agent webform or as an IFRAME in a script tab while on a
# call(you can use Get-Call-Launch or the Trigger function to auto-launch a
# webform).
#
# !!! IMPORTANT !!! You must customize this script to your needs, and you must
#                   have the campaign setting for 'Answering Machine Message'
#                   set to 'LTTagent' for this to work.
#
### Example line to put in the campaign WEBFORM field:
# http://127.0.0.1/agc/vdc_LTT_generate.php
#
# CHANGELOG:
# 200424-2013 - First build of script
#

$version = '2.14-1';
$build = '200424-2013';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$query_string = getenv("QUERY_STRING");

if (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["vendor_id"]))	{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))	{$vendor_id=$_POST["vendor_id"];}
if (isset($_GET["vendor_lead_code"]))	{$vendor_lead_code=$_GET["vendor_lead_code"];}
	elseif (isset($_POST["vendor_lead_code"]))	{$vendor_lead_code=$_POST["vendor_lead_code"];}
if (strlen($vendor_lead_code) < 1) {$vendor_lead_code = $vendor_id;}
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
if (isset($_GET["talk_time"]))	{$talk_time=$_GET["talk_time"];}
	elseif (isset($_POST["talk_time"]))	{$talk_time=$_POST["talk_time"];}
if (isset($_GET["talk_time_min"]))	{$talk_time_min=$_GET["talk_time_min"];}
	elseif (isset($_POST["talk_time_min"]))	{$talk_time_min=$_POST["talk_time_min"];}
if (isset($_GET["agent_log_id"]))	{$agent_log_id=$_GET["agent_log_id"];}
	elseif (isset($_POST["agent_log_id"]))	{$agent_log_id=$_POST["agent_log_id"];}
if (isset($_GET["dispo_name"]))	{$dispo_name=$_GET["dispo_name"];}
	elseif (isset($_POST["dispo_name"]))	{$dispo_name=$_POST["dispo_name"];}
if (isset($_GET["event"]))			{$event=$_GET["event"];}
	elseif (isset($_POST["event"]))	{$event=$_POST["event"];}
if (isset($_GET["message"]))			{$message=$_GET["message"];}
	elseif (isset($_POST["message"]))	{$message=$_POST["message"];}
if (isset($_GET["counter"]))			{$counter=$_GET["counter"];}
	elseif (isset($_POST["counter"]))	{$counter=$_POST["counter"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
	$length_in_sec = preg_replace("/[^0-9]/","",$length_in_sec);
	$phone_code = preg_replace("/[^0-9]/","",$phone_code);
	$phone_number = preg_replace("/[^0-9]/","",$phone_number);
	$counter = preg_replace("/[^0-9]/","",$counter);
	}
else
	{
	$user = preg_replace("/\'|\"|\\\\|;/","",$user);
	$pass = preg_replace("/\'|\"|\\\\|;/","",$pass);
	}
$DB=preg_replace("/[^-_0-9a-zA-Z]/","",$DB);

if (strlen($lead_id) < 1)
	{
	echo "ERROR: invalid lead_id: |$lead_id|\n";
	}
else
	{
	# EXAMPLE SCRIPT:
	# Hello, this is a call from ACME Shipping, your order number A1234567 from account 876543 was shipped on December 1st and is insured for $123456.78.
	# If you have any questions, please call us back at 3125551212. Goodbye.
	#
	# VARIABLES:
	#     address3 =			'ACME Shipping'		(company name)
	#     vendor_lead_code =	'A1234567'			(order number)
	#     owner =				'876543'			(account number)
	#     province =			'2020-12-01'		(shipping date)
	#     security_phrase =		'123456.78'			(insured amount)
	#     alt_phone =			'3125551212'		(callback phone number)
	#
	# STATIC PRIMPTS:
	#     EXship01.wav =			'Hello, this is a call from'
	#     EXship02.wav =			'your order number'
	#     EXship03.wav =			'from account'
	#     EXship04.wav =			'was shipped on'
	#     EXship05.wav =			'and is insured for'
	#     EXship06.wav =			'cents. If you have any questions, please call us back at'
	#     EXship07.wav =			'Goodbye'
	#
	# TEST URL: http://127.0.0.1/agc/vdc_LTT_generate.php?lead_id=101&user=6666&address3=ACME+Shipping&vendor_lead_code=A1234567&owner=876543&province=2020-12-01&security_phrase=123456.78&alt_phone=3125551212
	#
	# SAMPLE WEBFORM URL:
	# VARhttp://127.0.0.1/agc/vdc_LTT_generate.php?lead_id=--A--lead_id--B--&user=--A--user--B--&address3=--A--address3--B--&vendor_lead_code=--A--vendor_lead_code--B--&owner=--A--owner--B--&province=--A--province--B--&security_phrase=--A--security_phrase--B--&alt_phone=--A--alt_phone--B--
	#
	# NOTES:
	# After this page loads, the agent will just need to open the transfer panel and click on the 'VM' button to send the call to the customized message(or use a properly-configured HotKey to do so). Keep in mind that there are some campaign settings that can override this function, so you will want to disable 'AM Message Wildcards' and 'VM Message Groups', and also set the 'Answering Machine Message' to 'LTTagent' for this to work.
	#

	if ($address3 == 'ACME Shipping') {$EXship01a = 'EXshipACME.wav';}
	if ($address3 == 'International Parcel Shipping') {$EXship01a = 'EXshipIPS.wav';}
	if ($address3 == 'United States Postal Service') {$EXship01a = 'EXshipUSPS.wav';}

	$EXship02a = convertWordToSpell($DB,"$vendor_lead_code");

	$owner = strtolower($owner);
	$EXship03a = convertWordToSpell($DB,"$owner");

	$provinceMONTH = date("n", strtotime("$province"));
	$provinceDAY = date("j", strtotime("$province"));
	$EXship04a = convertNumberToMonth($DB,"$provinceMONTH");
	$EXship04b = convertNumberToOrder($DB,"$provinceDAY");

	$security_phraseARY = explode('.',$security_phrase);
	if ($security_phraseARY[0] == '0') {$EXship05a = 'digits/0.wav|';}
	else 
		{
		$EXship05a = convertNumberToNumberSpeak("$security_phraseARY[0]");
		$EXship05a = preg_replace("/     |    |   |  /",' ',$EXship05a);
		$EXship05a = preg_replace("/^ | $/",'',$EXship05a);
		$EXship05a = preg_replace("/ /",'.wav|digits/',$EXship05a);
		$EXship05a = "digits/".$EXship05a.".wav|";
		}
	$EXship05a .= 'digits/dollars.wav|';
	if ($security_phraseARY[1] == '0') {$EXship05b = 'digits/0.wav|';}
	else 
		{
		$EXship05b = convertNumberToNumberSpeak("$security_phraseARY[1]");
		$EXship05b = preg_replace("/     |    |   |  /",' ',$EXship05b);
		$EXship05b = preg_replace("/^ | $/",'',$EXship05b);
		$EXship05b = preg_replace("/ /",'.wav|digits/',$EXship05b);
		$EXship05b = "digits/".$EXship05b.".wav|";
		}
	$EXship05b .= 'cents.wav|';

	$EXship06a = convertWordToSpell($DB,"$alt_phone");


	$message_entry = 'EXship01.wav|';
	$message_entry .= "$EXship01a|";
	$message_entry .= "EXship02.wav|";
	$message_entry .= "$EXship02a";
	$message_entry .= "EXship03.wav|";
	$message_entry .= "$EXship03a";
	$message_entry .= "EXship04.wav|";
	$message_entry .= "$EXship04a$EXship04b";
	$message_entry .= "EXship05.wav|";
	$message_entry .= "$EXship05a$EXship05b";
	$message_entry .= "EXship06.wav|";
	$message_entry .= "$EXship06a";
	$message_entry .= "EXship07.wav|";

	$message_entry = preg_replace("/\.wav/",'',$message_entry);
	print "$message_entry \n";

	$stmt="INSERT INTO vicidial_lead_messages (lead_id,call_date,user,message_entry) values('$lead_id',NOW(),'$user','$message_entry');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	}

exit;



### SUBROUTINES ###
function convertWordToSpell($DB,$word)
	{
    if (strlen($word) < 1)
		{
		if ($DB > 0) {print "DEBUG ERROR: ($word)";}
        return false;
		}
	$orderARY = str_split($word);
	$orderARY_ct = count($orderARY);
	if ($DB > 0) {print "DEBUG: ($word $orderARY_ct)";}
	$i=0;	$spell='';
	while($orderARY_ct > $i)
		{
		if (preg_match('/^[0-9]/',$orderARY[$i])) {$spell .= "digits/".$orderARY[$i].".wav|";}
		if (preg_match('/^[a-zA-Z]/',$orderARY[$i])) 
			{
			$orderARY[$i] = strtolower($orderARY[$i]);
			$spell .= "letters/".$orderARY[$i].".wav|";
			}
		if ($orderARY[$i] == '#') {$spell .= "digits/pound.wav|";}
		if ($orderARY[$i] == '*') {$spell .= "digits/star.wav|";}
		if ($orderARY[$i] == '-') {$spell .= "letters/dash.wav|";}
		if ($orderARY[$i] == ' ') {$spell .= "letters/space.wav|";}
		if ($orderARY[$i] == '/') {$spell .= "letters/slash.wav|";}
		if ($orderARY[$i] == '+') {$spell .= "letters/plus.wav|";}
		if ($orderARY[$i] == '!') {$spell .= "letters/exclaimation-point.wav|";}
		if ($orderARY[$i] == '=') {$spell .= "letters/equals.wav|";}
		if ($orderARY[$i] == '@') {$spell .= "letters/at.wav|";}
		if ($orderARY[$i] == '.') {$spell .= "letters/dot.wav|";}
		$i++;
		}
    return $spell;
	}

function convertNumberToOrder($DB,$num)
	{
    if (strlen($num) < 1)
		{
		if ($DB > 0) {print "DEBUG ERROR: ($num)";}
        return false;
		}
	if ($DB > 0) {print "DEBUG: ($num)";}
	$order='';
	if ( ($num >= 1) and ($num <= 20) ) {$order .= "digits/h-".$num.".wav|";}
	if ( ($num >= 21) and ($num <= 99) )
		{
		if (substr($num, -1) == '0') {$order .= "digits/h-".$num.".wav|";}
		else {$order .= "digits/".substr($num,0,1)."0.wav|digits/h-".substr($num, -1).".wav|";}
		}

	return $order;
	}

function convertNumberToMonth($DB,$num)
	{
    if (strlen($num) < 1)
		{
		if ($DB > 0) {print "DEBUG ERROR: ($num)";}
        return false;
		}
	if ($DB > 0) {print "DEBUG: ($num)";}
	$file_month='';
	if ( ($num >= 1) and ($num <= 12) ) 
		{
		$num = ($num -1);
		$file_month = "digits/mon-".$num.".wav|";
		}

	return $file_month;
	}

function convertNumberToWord($num = false)
	{
    $num = str_replace(array(',', ' '), '' , trim($num));
    if(! $num) 
		{
        return false;
		}
    $num = (int) $num;
    $words = array();
    $list1 = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
        'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
    );
    $list2 = array('', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred');
    $list3 = array('', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion',
        'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion',
        'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion'
    );
    $num_length = strlen($num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    for ($i = 0; $i < count($num_levels); $i++) 
		{
        $levels--;
        $hundreds = (int) ($num_levels[$i] / 100);
        $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
        $tens = (int) ($num_levels[$i] % 100);
        $singles = '';
        if ( $tens < 20 ) 
			{
            $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '' );
			}
		else 
			{
            $tens = (int)($tens / 10);
            $tens = ' ' . $list2[$tens] . ' ';
            $singles = (int) ($num_levels[$i] % 10);
            $singles = ' ' . $list1[$singles] . ' ';
			}
		$words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_levels[$i] ) ) ? ' ' . $list3[$levels] . ' ' : '' );
		}
    $commas = count($words);
    if ($commas > 1) 
		{
        $commas = $commas - 1;
		}
    return implode(' ', $words);
	}

function convertNumberToNumberSpeak($num = false)
	{
    $num = str_replace(array(',', ' '), '' , trim($num));
    if(! $num) 
		{
        return false;
		}
    $num = (int) $num;
    $words = array();
    $list1 = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11','12', '13', '14', '15', '16', '17', '18', '19');
    $list2 = array('', '10', '20', '30', '40', '50', '60', '70', '80', '90', 'hundred');
    $list3 = array('', 'thousand', 'million', 'billion', 'trillion');
    $num_length = strlen($num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    for ($i = 0; $i < count($num_levels); $i++) 
		{
        $levels--;
        $hundreds = (int) ($num_levels[$i] / 100);
        $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
        $tens = (int) ($num_levels[$i] % 100);
        $singles = '';
        if ( $tens < 20 ) 
			{
            $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '' );
			}
		else 
			{
            $tens = (int)($tens / 10);
            $tens = ' ' . $list2[$tens] . ' ';
            $singles = (int) ($num_levels[$i] % 10);
            $singles = ' ' . $list1[$singles] . ' ';
			}
		$words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_levels[$i] ) ) ? ' ' . $list3[$levels] . ' ' : '' );
		}
    $commas = count($words);
    if ($commas > 1) 
		{
        $commas = $commas - 1;
		}
    return implode(' ', $words);
	}
?>
