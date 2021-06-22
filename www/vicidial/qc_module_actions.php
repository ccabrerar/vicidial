<?php
header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

if ( file_exists("/etc/mysql_enc.conf") ) {
	$DBCagc = file("/etc/mysql_enc.conf");
	foreach ($DBCagc as $DBCline) {
		$DBCline = preg_replace("/ |>|\n|\r|\t|\#.*|;.*/","",$DBCline);
		if (ereg("^enckey", $DBCline)) {$enckey = $DBCline;   $enckey = preg_replace("/.*=/","",$enckey);}
	}
}


$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING = getenv("QUERY_STRING");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,admin_screen_colors,qc_features_active FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSauto_dial_limit =			$row[1];
	$SSuser_territories_active =	$row[2];
	$SSallow_custom_dialplan =		$row[3];
	$SScallcard_enabled =			$row[4];
	$SSadmin_modify_refresh =		$row[5];
	$SSnocache_admin =				$row[6];
	$SSwebroot_writable =			$row[7];
	$SSadmin_screen_colors =		$row[8];
	$SSqc_features_active =			$row[9];

	# slightly increase limit value, because PHP somehow thinks 2.8 > 2.8
	$SSauto_dial_limit = ($SSauto_dial_limit + 0.001);
	}
##### END SETTINGS LOOKUP #####
###########################################

if (isset($_GET["DB"]))	{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["end_date"]))	{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["modify_date"]))	{$modify_date=$_GET["modify_date"];}
	elseif (isset($_POST["modify_date"]))	{$modify_date=$_POST["modify_date"];}
if (isset($_GET["claim_rec"]))	{$claim_rec=$_GET["claim_rec"];}
	elseif (isset($_POST["claim_rec"]))	{$claim_rec=$_POST["claim_rec"];}
if (isset($_GET["recording_id"]))	{$recording_id=$_GET["recording_id"];}
	elseif (isset($_POST["recording_id"]))	{$recording_id=$_POST["recording_id"];}
if (isset($_GET["qc_action"]))	{$qc_action=$_GET["qc_action"];}
	elseif (isset($_POST["qc_action"]))	{$qc_action=$_POST["qc_action"];}
if (isset($_GET["mgr_override"]))	{$mgr_override=$_GET["mgr_override"];}
	elseif (isset($_POST["mgr_override"]))	{$mgr_override=$_POST["mgr_override"];}
if (isset($_GET["scorecard_id"]))	{$scorecard_id=$_GET["scorecard_id"];}
	elseif (isset($_POST["scorecard_id"]))	{$scorecard_id=$_POST["scorecard_id"];}
if (isset($_GET["qc_row_id"]))	{$qc_row_id=$_GET["qc_row_id"];}
	elseif (isset($_POST["qc_row_id"]))	{$qc_row_id=$_POST["qc_row_id"];}
if (isset($_GET["qc_log_id"]))	{$qc_log_id=$_GET["qc_log_id"];}
	elseif (isset($_POST["qc_log_id"]))	{$qc_log_id=$_POST["qc_log_id"];}
if (isset($_GET["qc_status"]))	{$qc_status=$_GET["qc_status"];}
	elseif (isset($_POST["qc_status"]))	{$qc_status=$_POST["qc_status"];}
if (isset($_GET["qc_agent_comments"]))	{$qc_agent_comments=$_GET["qc_agent_comments"];}
	elseif (isset($_POST["qc_agent_comments"]))	{$qc_agent_comments=$_POST["qc_agent_comments"];}
if (isset($_GET["qc_manager_comments"]))	{$qc_manager_comments=$_GET["qc_manager_comments"];}
	elseif (isset($_POST["qc_manager_comments"]))	{$qc_manager_comments=$_POST["qc_manager_comments"];}

if (isset($_GET["field_name"]))	{$field_name=$_GET["field_name"];}
	elseif (isset($_POST["field_name"]))	{$field_name=$_POST["field_name"];}
if (isset($_GET["field_value"]))	{$field_value=$_GET["field_value"];}
	elseif (isset($_POST["field_value"]))	{$field_value=$_POST["field_value"];}

if (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["callback_id"]))	{$callback_id=$_GET["callback_id"];}
	elseif (isset($_POST["callback_id"]))	{$callback_id=$_POST["callback_id"];}
if (isset($_GET["recipient"]))	{$recipient=$_GET["recipient"];}
	elseif (isset($_POST["recipient"]))	{$recipient=$_POST["recipient"];}
if (isset($_GET["CBuser"]))	{$CBuser=$_GET["CBuser"];}
	elseif (isset($_POST["CBuser"]))	{$CBuser=$_POST["CBuser"];}
if (isset($_GET["callback_time"]))	{$callback_time=$_GET["callback_time"];}
	elseif (isset($_POST["callback_time"]))	{$callback_time=$_POST["callback_time"];}
if (isset($_GET["callback_comments"]))	{$callback_comments=$_GET["callback_comments"];}
	elseif (isset($_POST["callback_comments"]))	{$callback_comments=$_POST["callback_comments"];}


if (isset($_GET["qc_checkpoint_log_id"]))	{$qc_checkpoint_log_id=$_GET["qc_checkpoint_log_id"];}
	elseif (isset($_POST["qc_checkpoint_log_id"]))	{$qc_checkpoint_log_id=$_POST["qc_checkpoint_log_id"];}
if (isset($_GET["qc_level"]))	{$qc_level=$_GET["qc_level"];}
	elseif (isset($_POST["qc_level"]))	{$qc_level=$_POST["qc_level"];}
if (isset($_GET["checkpoint_rank"]))	{$checkpoint_rank=$_GET["checkpoint_rank"];}
	elseif (isset($_POST["checkpoint_rank"]))	{$checkpoint_rank=$_POST["checkpoint_rank"];}
if (isset($_GET["checkpoint_points"]))	{$checkpoint_points=$_GET["checkpoint_points"];}
	elseif (isset($_POST["checkpoint_points"]))	{$checkpoint_points=$_POST["checkpoint_points"];}
if (isset($_GET["checkpoint_points_earned"]))	{$checkpoint_points_earned=$_GET["checkpoint_points_earned"];}
	elseif (isset($_POST["checkpoint_points_earned"]))	{$checkpoint_points_earned=$_POST["checkpoint_points_earned"];}
if (isset($_GET["new_checkpoint_points"]))	{$new_checkpoint_points=$_GET["new_checkpoint_points"];}
	elseif (isset($_POST["new_checkpoint_points"]))	{$new_checkpoint_points=$_POST["new_checkpoint_points"];}
if (isset($_GET["checkpoint_points_failed"]))	{$checkpoint_points_failed=$_GET["checkpoint_points_failed"];}
	elseif (isset($_POST["checkpoint_points_failed"]))	{$checkpoint_points_failed=$_POST["checkpoint_points_failed"];}
if (isset($_GET["old_checkpoint_rank"]))	{$old_checkpoint_rank=$_GET["old_checkpoint_rank"];}
	elseif (isset($_POST["old_checkpoint_rank"]))	{$old_checkpoint_rank=$_POST["old_checkpoint_rank"];}
if (isset($_GET["new_checkpoint_rank"]))	{$new_checkpoint_rank=$_GET["new_checkpoint_rank"];}
	elseif (isset($_POST["new_checkpoint_rank"]))	{$new_checkpoint_rank=$_POST["new_checkpoint_rank"];}
if (isset($_GET["new_active"]))	{$new_active=$_GET["new_active"];}
	elseif (isset($_POST["new_active"]))	{$new_active=$_POST["new_active"];}
if (isset($_GET["new_commission_loss"]))	{$new_commission_loss=$_GET["new_commission_loss"];}
	elseif (isset($_POST["new_commission_loss"]))	{$new_commission_loss=$_POST["new_commission_loss"];}
if (isset($_GET["new_instant_fail"]))	{$new_instant_fail=$_GET["new_instant_fail"];}
	elseif (isset($_POST["new_instant_fail"]))	{$new_instant_fail=$_POST["new_instant_fail"];}
if (isset($_GET["new_admin_notes"]))	{$new_admin_notes=$_GET["new_admin_notes"];}
	elseif (isset($_POST["new_admin_notes"]))	{$new_admin_notes=$_POST["new_admin_notes"];}
if (isset($_GET["new_checkpoint_text"]))	{$new_checkpoint_text=$_GET["new_checkpoint_text"];}
	elseif (isset($_POST["new_checkpoint_text"]))	{$new_checkpoint_text=$_POST["new_checkpoint_text"];}
if (isset($_GET["new_checkpoint_text_presets"]))	{$new_checkpoint_text_presets=$_GET["new_checkpoint_text_presets"];}
	elseif (isset($_POST["new_checkpoint_text_presets"]))	{$new_checkpoint_text_presets=$_POST["new_checkpoint_text_presets"];}
if (isset($_GET["checkpoint_row_id"]))	{$checkpoint_row_id=$_GET["checkpoint_row_id"];}
	elseif (isset($_POST["checkpoint_row_id"]))	{$checkpoint_row_id=$_POST["checkpoint_row_id"];}
if (isset($_GET["checkpoint_comment_agent"]))	{$checkpoint_comment_agent=$_GET["checkpoint_comment_agent"];}
	elseif (isset($_POST["checkpoint_comment_agent"]))	{$checkpoint_comment_agent=$_POST["checkpoint_comment_agent"];}
if (isset($_GET["checkpoint_answer"]))	{$checkpoint_answer=$_GET["checkpoint_answer"];}
	elseif (isset($_POST["checkpoint_answer"]))	{$checkpoint_answer=$_POST["checkpoint_answer"];}
if (isset($_GET["instant_fail"]))	{$instant_fail=$_GET["instant_fail"];}
	elseif (isset($_POST["instant_fail"]))	{$instant_fail=$_POST["instant_fail"];}
if (isset($_GET["instant_fail_value"]))	{$instant_fail_value=$_GET["instant_fail_value"];}
	elseif (isset($_POST["instant_fail_value"]))	{$instant_fail_value=$_POST["instant_fail_value"];}
if (isset($_GET["admin_notes"]))	{$admin_notes=$_GET["admin_notes"];}
	elseif (isset($_POST["admin_notes"]))	{$admin_notes=$_POST["admin_notes"];}
if (isset($_GET["commission_loss"]))	{$commission_loss=$_GET["commission_loss"];}
	elseif (isset($_POST["commission_loss"]))	{$commission_loss=$_POST["commission_loss"];}
if (isset($_GET["parameter"]))	{$parameter=$_GET["parameter"];}
	elseif (isset($_POST["parameter"]))	{$parameter=$_POST["parameter"];}
if (isset($_GET["parameter_value"]))	{$parameter_value=$_GET["parameter_value"];}
	elseif (isset($_POST["parameter_value"]))	{$parameter_value=$_POST["parameter_value"];}
if (isset($_GET["recording_timestamp"]))	{$recording_timestamp=$_GET["recording_timestamp"];}
	elseif (isset($_POST["recording_timestamp"]))	{$recording_timestamp=$_POST["recording_timestamp"];}
if (isset($_POST["upd_customer"]))			{$upd_customer=$_POST["upd_customer"];}
	elseif (isset($_GET["upd_customer"]))	{$upd_customer=$_GET["upd_customer"];}
if (isset($_POST["view_epoch"]))			{$view_epoch=$_POST["view_epoch"];}
	elseif (isset($_GET["view_epoch"]))	{$view_epoch=$_GET["view_epoch"];}

$defaultappointment = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");

$rights_stmt = "SELECT modify_leads,qc_enabled,full_name,modify_leads,user_group,selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$modify_leads =			$rights_row[0];
$qc_enabled =			$rights_row[1];
$LOGfullname =			$rights_row[2];
$LOGmodify_leads =		$rights_row[3];
$LOGuser_group =		$rights_row[4];
$VUselected_language =	$rights_row[5];

if ($qc_log_id)
	{
	$queue_stmt="select * from quality_control_queue where qc_log_id='$qc_log_id'";
	$queue_rslt=mysql_to_mysqli($queue_stmt, $link);
	$queue_row=mysqli_fetch_array($queue_rslt);
	$queue_user=$queue_row["user"];
	$queue_user_group=$queue_row["user_group"];
	$queue_list_id=$queue_row["list_id"];
	$queue_campaign_id=$queue_row["campaign_id"];
	$queue_lead_id=$queue_row["lead_id"];
	$queue_qc_user_group=$queue_row["qc_user_group"];
	}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

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

if ( $qc_enabled < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("QC is not enabled for your user account")."\n";
	exit;
	}


$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='A3C3D6';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SSmenu_background =		$row[0];
		$SSframe_background =		$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =				$row[10];
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($qc_checkpoint_log_id && $qc_action=="upd_customer") {
	$upd_stmt="update quality_control_checkpoint_log set checkpoint_comment_agent='".mysqli_escape_string($link, $checkpoint_comment_agent)."', instant_fail_value='".mysqli_escape_string($link, $instant_fail_value)."', checkpoint_points_earned='".mysqli_escape_string($link, $checkpoint_points_earned)."' where qc_checkpoint_log_id='$qc_checkpoint_log_id'";
	$upd_rslt=mysqli_query($link, $upd_stmt);
	#echo mysqli_affected_rows($link);
	if ($DB) {echo "|$upd_stmt|\n";}
	if (mysqli_affected_rows($link)>0)
		{
		# Check if there's not already a log record for this visit...
		$stmt="select count(*) From vicidial_qc_agent_log where lead_id='$queue_lead_id' and view_epoch='$view_epoch' and details='Scorecard modified'";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		
		if ($row[0]==0)
			{
			$elapsed_seconds=$STARTtime-$view_epoch;
			$stmt="INSERT INTO vicidial_qc_agent_log (qc_user,qc_user_group,qc_user_ip,lead_user,web_server_ip,view_datetime,view_epoch,save_epoch,elapsed_seconds,lead_id,list_id,campaign_id,processed,details) values('" . mysqli_real_escape_string($link, $PHP_AUTH_USER) . "','$LOGuser_group','".$_SERVER['REMOTE_ADDR']."','$lead_user','".$_SERVER['SERVER_ADDR']."','$NOW_TIME','$view_epoch','$STARTtime','$elapsed_seconds','" . mysqli_real_escape_string($link, $queue_lead_id) . "','" . mysqli_real_escape_string($link, $queue_list_id) . "','" . mysqli_real_escape_string($link, $queue_campaign_id) . "','N','Scorecard modified')";
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		}
}


if ($qc_action=="check_lead_status" && $modify_date && $lead_id)
	{
	$stmt="select * From vicidial_list where lead_id='$lead_id' and modify_date>'$modify_date' limit 1";
	$rslt=mysql_to_mysqli($stmt, $link);
	$rslt_str=mysqli_num_rows($rslt)."\n";
	while ($row=mysqli_fetch_array($rslt))
		{
		$rslt_str.="$row[modify_date]|";
		$rslt_str.="$row[status]|";
		$rslt_str.="$row[vendor_lead_code]|";
		$rslt_str.="$row[user]|";
		$rslt_str.="$row[called_count]|";
		$rslt_str.="$row[title]|";
		$rslt_str.="$row[first_name]|";
		$rslt_str.="$row[middle_initial]|";
		$rslt_str.="$row[last_name]|";
		$rslt_str.="$row[address1]|";
		$rslt_str.="$row[address2]|";
		$rslt_str.="$row[address3]|";
		$rslt_str.="$row[city]|";
		$rslt_str.="$row[state]|";
		$rslt_str.="$row[postal_code]|";
		$rslt_str.="$row[province]|";
		$rslt_str.="$row[country_code]|";
		$rslt_str.="$row[phone_number]|";
		$rslt_str.="$row[phone_code]|";
		$rslt_str.="$row[alt_phone]|";
		$rslt_str.="$row[email]|";
		$rslt_str.="$row[security_phrase]|";
		$rslt_str.="$row[rank]|";
		$rslt_str.="$row[owner]|";
		$rslt_str.="$row[comments]|";
		}
	echo $rslt_str;
	}

/*
if ($qc_action=="log_responses" && $qc_checkpoint_log_id && ($checkpoint_answer || $recording_timestamp)) {
	$recording_timestamp=preg_replace("/[^0-9\:]/", "", $recording_timestamp);
	$upd_stmt="update quality_control_checkpoint_log set checkpoint_answer='$checkpoint_answer', recording_timestamp='".mysqli_escape_string($link, $recording_timestamp)."' where qc_checkpoint_log_id='$qc_checkpoint_log_id' limit 1";
	if ($DB) {echo "|$upd_stmt|\n";}
	$upd_rslt=mysqli_query($link, $upd_stmt);
	echo mysqli_affected_rows($link);
	if ($instant_fail=="Y" && $checkpoint_answer=="N") {
		# $allq_stmt="select distinct qc_row_id from quality_control_checkpoint_log where qc_checkpoint_log_id='$qc_checkpoint_log_id'";
		# $allq_rslt=mysqli_query($link, $allq_stmt);
		# while ($allq_row=mysqli_fetch_row($allq_rslt)) {
		# 	$qc_row_id=$allq_row[0];
		# 	$upd_stmt="update quality_control_checkpoint_log set checkpoint_points_failed=checkpoint_points_max where qc_row_id='$qc_row_id'";
		#	$upd_rslt=mysqli_query($link, $upd_stmt);
		# }
	}
}
*/

if ($qc_action=="update_vl_field" && $lead_id && $field_name)
	{
	$old_data_stmt="select $field_name from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "'";
	$old_data_rslt=mysql_to_mysqli($old_data_stmt, $link);
	$od_row=mysqli_fetch_row($old_data_rslt);
	$old_value=$od_row[0];

	$stmt="UPDATE vicidial_list set $field_name='" . mysqli_real_escape_string($link, $field_value) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' limit 1";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if (mysqli_affected_rows($link)>0)
		{
		$details="----$field_name----\n" . mysqli_real_escape_string($link, $old_value) . " => " . mysqli_real_escape_string($link, $field_value) . "\n";
		$elapsed_seconds=$STARTtime-$view_epoch;

		$upd_stmt="UPDATE vicidial_qc_agent_log set details=if(details is null, '$details', CONCAT(details, '$details')), save_datetime='$NOW_TIME', save_epoch='$STARTtime', elapsed_seconds='$elapsed_seconds' where view_epoch='$view_epoch' and lead_id='$lead_id' and qc_log_id='$qc_log_id'";
		echo $upd_stmt."\n";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		}
	}

if ($qc_action=="update_callback")
	{
	# $DB=1;
	if ($callback_id && ($recipient || $CBuser || $callback_time))
		{
		$update_cb_SQL="";
		if ($recipient) {$update_cb_SQL.="recipient='$recipient', ";}
		if ($CBuser) {$update_cb_SQL.="user='$CBuser', ";}
		if ($callback_time) {$update_cb_SQL.="callback_time='$callback_time', ";}
		if ($callback_comments) {$update_cb_SQL.="comments='" . mysqli_real_escape_string($link, $callback_comments) . "', ";}
		$update_cb_SQL=preg_replace('/, $/', "", $update_cb_SQL);
		if (strlen($update_cb_SQL)==0) {echo "0"; exit;}

		$update_cb_stmt="UPDATE vicidial_callbacks set $update_cb_SQL where callback_id='" . mysqli_real_escape_string($link, $callback_id) . "'";
		if ($DB) {echo "|$update_cb_stmt|\n";}
		$update_cb_rslt=mysql_to_mysqli($update_cb_stmt, $link);
		echo mysqli_affected_rows($link);
		if (mysqli_affected_rows($link)>0)
			{
			$elapsed_seconds=$STARTtime-$view_epoch;
			$details=preg_replace('/[\"\']/', "", $update_cb_SQL);
			$details=preg_replace('/,\s?/', "\n", $details);

			$stmt="INSERT INTO vicidial_qc_agent_log (qc_user,qc_user_group,qc_user_ip,lead_user,web_server_ip,view_datetime,view_epoch,save_datetime,save_epoch,elapsed_seconds,lead_id,list_id,campaign_id,processed,details)
			values('" . mysqli_real_escape_string($link, $PHP_AUTH_USER) . "','$LOGuser_group','".$_SERVER['REMOTE_ADDR']."','$queue_user','".$_SERVER['SERVER_ADDR']."','$NOW_TIME','$view_epoch','$NOW_TIME','$STARTtime','$elapsed_seconds','" . mysqli_real_escape_string($link, $queue_lead_id) . "','" . mysqli_real_escape_string($link, $queue_list_id) . "','" . mysqli_real_escape_string($link, $queue_campaign_id) . "','N','---updated callback---\n" . mysqli_real_escape_string($link, $details) . "')";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);

			# QC_log();
			}
		}
	else
		{
		echo "0";
		}
	}
if ($qc_action=="update_lead_information" && $lead_id) 
	{
	$stmt="UPDATE vicidial_list set status='" . mysqli_real_escape_string($link, $status) . "',title='" . mysqli_real_escape_string($link, $title) . "',first_name='" . mysqli_real_escape_string($link, $first_name) . "',middle_initial='" . mysqli_real_escape_string($link, $middle_initial) . "',last_name='" . mysqli_real_escape_string($link, $last_name) . "',address1='" . mysqli_real_escape_string($link, $address1) . "',address2='" . mysqli_real_escape_string($link, $address2) . "',address3='" . mysqli_real_escape_string($link, $address3) . "',city='" . mysqli_real_escape_string($link, $city) . "',state='" . mysqli_real_escape_string($link, $state) . "',province='" . mysqli_real_escape_string($link, $province) . "',postal_code='" . mysqli_real_escape_string($link, $postal_code) . "',country_code='" . mysqli_real_escape_string($link, $country_code) . "',alt_phone='" . mysqli_real_escape_string($link, $alt_phone) . "',phone_number='$phone_number',phone_code='$phone_code',email='" . mysqli_real_escape_string($link, $email) . "',security_phrase='" . mysqli_real_escape_string($link, $security) . "',comments='" . mysqli_real_escape_string($link, $comments) . "',rank='" . mysqli_real_escape_string($link, $rank) . "',owner='" . mysqli_real_escape_string($link, $owner) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "'";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	}

if ($qc_action=="save_comments" && $qc_checkpoint_log_id && $checkpoint_comment_agent && $qc_level) {
	if ($qc_level=="agent") {
		$column="checkpoint_comment_agent";
	} else {
		$column="checkpoint_comment_manager";
	}
	$upd_stmt="update quality_control_checkpoint_log set $column='$checkpoint_comment_agent' where qc_checkpoint_log_id='$qc_checkpoint_log_id' limit 1";
	if ($DB) {echo "|$upd_stmt|\n";}
	$upd_rslt=mysqli_query($link, $upd_stmt);
	echo mysqli_affected_rows($link);
}

if ($scorecard_id && $qc_action=="add_checkpoint" && $new_checkpoint_rank && ($new_checkpoint_text || $new_checkpoint_text_presets) && $new_active) {
	$upd_stmt="update quality_control_checkpoints set checkpoint_rank=checkpoint_rank+1, modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_rank>=$new_checkpoint_rank";
	$upd_rslt=mysqli_query($link, $upd_stmt);

	$ins_stmt="insert into quality_control_checkpoints(qc_scorecard_id, checkpoint_rank, checkpoint_text, checkpoint_text_presets, checkpoint_points, active, instant_fail, admin_notes, create_date, create_user) VALUES('".mysqli_real_escape_string($link, $scorecard_id)."', '".mysqli_real_escape_string($link, $new_checkpoint_rank)."', '".mysqli_real_escape_string($link, $new_checkpoint_text)."', '".mysqli_real_escape_string($link, $new_checkpoint_text_presets)."', '".mysqli_real_escape_string($link, $new_checkpoint_points)."', '".mysqli_real_escape_string($link, $new_active)."', '".mysqli_real_escape_string($link, $new_instant_fail)."', '".mysqli_real_escape_string($link, $new_admin_notes)."', now(), '".mysqli_real_escape_string($link, $PHP_AUTH_USER)."')";
	echo $ins_stmt;
	$ins_rslt=mysqli_query($link, $ins_stmt);

	if (mysqli_affected_rows($link)>0) {
		$upd_stmt2="update quality_control_scorecards set last_modified=now() where qc_scorecard_id='$scorecard_id'";
		$upd_rslt2=mysqli_query($link, $upd_stmt2);
	}
}
if ($scorecard_id && $qc_action=="update_scorecard" && $parameter)
	{
	$upd_stmt="update quality_control_scorecards set $parameter='".mysqli_real_escape_string($link, $parameter_value)."' where qc_scorecard_id='$scorecard_id'";
	$upd_rslt=mysqli_query($link, $upd_stmt);
	if (mysqli_affected_rows($link)>0) 
		{
		$upd_stmt2="update quality_control_scorecards set last_modified=now() where qc_scorecard_id='$scorecard_id'";
		$upd_rslt2=mysqli_query($link, $upd_stmt2);
		}
	}
if ($scorecard_id && $qc_action=="update_checkpoint" && $checkpoint_row_id && $parameter) {

	if ($parameter && $parameter_value && $parameter!="checkpoint_rank") {
		$upd_stmt="update quality_control_checkpoints set $parameter='".mysqli_real_escape_string($link, $parameter_value)."', modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_row_id='$checkpoint_row_id'";
		$upd_rslt=mysqli_query($link, $upd_stmt);
		if (mysqli_affected_rows($link)>0) {
			$upd_stmt2="update quality_control_scorecards set last_modified=now() where qc_scorecard_id='$scorecard_id'";
			$upd_rslt2=mysqli_query($link, $upd_stmt2);
		}
	} else if ($parameter=="checkpoint_rank" && $new_checkpoint_rank && $old_checkpoint_rank) {
		$upd_stmt="update quality_control_checkpoints set checkpoint_rank='$new_checkpoint_rank', modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_row_id='$checkpoint_row_id'";
		$upd_rslt=mysqli_query($link, $upd_stmt);	
		if (mysqli_affected_rows($link)>0) {
			$upd_stmt2="update quality_control_scorecards set last_modified=now() where qc_scorecard_id='$scorecard_id'";
			$upd_rslt2=mysqli_query($link, $upd_stmt2);

			if ($new_checkpoint_rank>$old_checkpoint_rank) {
				$upd_stmt2="update quality_control_checkpoints set checkpoint_rank=checkpoint_rank-1, modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_rank>$old_checkpoint_rank and checkpoint_rank<=$new_checkpoint_rank and checkpoint_row_id!='$checkpoint_row_id'";
			} else {
				$upd_stmt2="update quality_control_checkpoints set checkpoint_rank=checkpoint_rank+1, modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_rank>=$new_checkpoint_rank and checkpoint_rank<$old_checkpoint_rank and checkpoint_row_id!='$checkpoint_row_id'";
			}
			$upd_rslt2=mysqli_query($link, $upd_stmt2);
		}
	}
}
if ($scorecard_id && $qc_action=="delete_checkpoint" && $checkpoint_rank && $checkpoint_row_id) {
	$delete_stmt="delete from quality_control_checkpoints where qc_scorecard_id='$scorecard_id' and checkpoint_row_id='$checkpoint_row_id'";
	$delete_rslt=mysqli_query($link, $delete_stmt);
	if (mysqli_affected_rows($link)>0) {
		$upd_stmt="update quality_control_checkpoints set checkpoint_rank=checkpoint_rank-1, modify_user='$PHP_AUTH_USER' where qc_scorecard_id='$scorecard_id' and checkpoint_rank>$checkpoint_rank";
		$upd_rslt=mysqli_query($link, $upd_stmt);

		$upd_stmt2="update quality_control_scorecards set last_modified=now() where qc_scorecard_id='$scorecard_id'";
		$upd_rslt2=mysqli_query($link, $upd_stmt2);
	}
}

if ($qc_action=="new_scorecard") 
	{
	echo "<form action='qc_scorecards.php' method='get'>\n";
	echo "<table width='90%' cellspacing='0' align='center'>\n";
	echo "\t<tr bgcolor=black nowrap>\n";
	echo "\t<th colspan='2'><font color='white'>"._QXZ("ADD NEW SCORECARD")."</font></th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "\t<td align='right'>"._QXZ("Scorecard ID").":</td>\n";
	echo "\t<td align='left'><input type='text' size='6' maxlength='8' name='new_scorecard_id'>$NWB#qc_scorecards-new_scorecard_id$NWE</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "\t<td align='right'>"._QXZ("Scorecard name").":</td>\n";
	echo "\t<td align='left'><input type='text' size='20' maxlength='255' name='new_scorecard_name'>$NWB#qc_scorecards-new_scorecard_name$NWE</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "\t<td align='right'>"._QXZ("Active").":</td>\n";
	echo "\t<td align='left'><select name='new_active'><option value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option></select>$NWB#qc_scorecards-new_active$NWE</td>\n";
	echo "</tr>\n";
	echo "\t<tr bgcolor=black nowrap>\n";
	echo "\t<th colspan='2'><input type='submit' name='submit' class='tiny_green_btn' value='"._QXZ("SUBMIT NEW SCORECARD")."'></th>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
	}

if ($scorecard_id && $qc_action=="load_checkpoints") {
	$new_qstmt="select if(max(checkpoint_rank) is not null, max(checkpoint_rank)+1, 1) from quality_control_checkpoints where qc_scorecard_id='$scorecard_id'";
	$new_qrslt=mysqli_query($link, $new_qstmt);
	$qrow=mysqli_fetch_row($new_qrslt);
	$next_rank=$qrow[0];

	$stmt="select * from quality_control_scorecards where qc_scorecard_id='$scorecard_id'";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_array($rslt);
	$scorecard_name=$row["scorecard_name"];
	$passing_score=$row["passing_score"];

	echo "<table width='100%' cellspacing='0'>\n";
	$stmt="select * from quality_control_checkpoints where qc_scorecard_id='$scorecard_id' order by checkpoint_rank";
	$rslt=mysqli_query($link, $stmt);
	if (mysqli_num_rows($rslt)>0) {
		echo "<tr>";
		echo "<th colspan='8' class='display_header'>"._QXZ("MODIFY QC SCORECARD")." $scorecard_id $NWB#qc_scorecards-modify$NWE</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<th colspan='8' class='small_display_header'>"._QXZ("NAME").": <input type='text' size='20' maxlength='255' id='scorecard_name".$scorecard_id."' name='scorecard_name".$scorecard_id." onBlur=\"ChangeCheckpoint('0', '$scorecard_id', 'update_scorecard', 'scorecard_name')\" value='$scorecard_name'></th>";
		echo "</tr>";
		$i++;
		if ($i%2==0) {$bgcolor=$SSstd_row1_background;} else {$bgcolor=$SSstd_row2_background;}
		echo "<tr bgcolor='".$bgcolor."'>\n";
		echo "<th class='small_display_header' nowrap>"._QXZ("Order")."$NWB#qc_scorecards-order$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Active")."$NWB#qc_scorecards-active_checkpoint$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Checkpoint Text")."$NWB#qc_scorecards-checkpoint_text$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Preset comments")."$NWB#qc_scorecards-preset_comments$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Points")."$NWB#qc_scorecards-points$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Instant Fail")."$NWB#qc_scorecards-instant_fail$NWE</th>";
		echo "<th class='small_display_header' nowrap>"._QXZ("Admin Notes")."$NWB#qc_scorecards-admin_notes$NWE</th>";
		# echo "<th class='small_display_header'>Commission Loss</th>";
		echo "<th class='small_display_header'>&nbsp;</th>";
		echo "</tr>";
		$max_score=0;
		while ($row=mysqli_fetch_array($rslt)) {
			echo "<tr>";
			echo "<td align='center'>\n";
			echo "<select onChange=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'checkpoint_rank', '$row[checkpoint_rank]', this.value)\" name='checkpoint_rank".$row["checkpoint_row_id"]."' id='checkpoint_rank".$row["checkpoint_row_id"]."'>";
			for ($i=1; $i<$next_rank; $i++) {
				echo "<option value='$i'".(($i==$row["checkpoint_rank"]) ? " selected" : "").">$i</option>\n";
			}

			$max_score+=$row["checkpoint_points"];

			echo "</select>\n";
			echo "</td>";
			echo "<td align='center'>";
			echo "<input onClick=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'active')\" type='checkbox' name='active".$row["checkpoint_row_id"]."' id='active".$row["checkpoint_row_id"]."'".(($row["active"]=="Y") ? " checked" : "")." value='Y'>";
			echo "</td>";

			echo "<td align='center'>";
			echo "<textarea class='notes_box' rows='4' cols='40' onBlur=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'checkpoint_text')\" id='checkpoint_text".$row["checkpoint_row_id"]."' name='checkpoint_text".$row["checkpoint_row_id"]."'>".$row["checkpoint_text"]."</textarea>";
			echo "</td>\n";
			echo "<td align='center'>";
			echo "<textarea class='notes_box' rows='4' cols='20' onBlur=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'checkpoint_text_presets')\" id='checkpoint_text_presets".$row["checkpoint_row_id"]."' name='checkpoint_text_presets".$row["checkpoint_row_id"]."'>".$row["checkpoint_text_presets"]."</textarea>";
			echo "</td>\n";

			echo "<td align='center'><input type='text' onBlur=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'checkpoint_points')\" id='checkpoint_points".$row["checkpoint_row_id"]."' name='checkpoint_points".$row["checkpoint_row_id"]."' value='".$row["checkpoint_points"]."' size='2' maxlength='3'></td>\n";
			echo "<td align='center'>";
			echo "<input onClick=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'instant_fail')\" type='checkbox' name='instant_fail".$row["checkpoint_row_id"]."' id='instant_fail".$row["checkpoint_row_id"]."'".(($row["instant_fail"]=="Y") ? " checked" : "")." value='Y'>";
			echo "</td>";
			echo "<td align='center'><textarea class='notes_box' rows='2' cols='25' onBlur=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'admin_notes')\" id='admin_notes".$row["checkpoint_row_id"]."' name='admin_notes".$row["checkpoint_row_id"]."'>".$row["admin_notes"]."</textarea></td>\n";
			# echo "<td align='center'>";
			# echo "<input onClick=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'update_checkpoint', 'commission_loss')\" type='checkbox' name='commission_loss".$row["checkpoint_row_id"]."' id='commission_loss".$row["checkpoint_row_id"]."'".(($row["commission_loss"]=="Y") ? " checked" : "")." value='Y'>";
			# echo "</td>";
			echo "<td align='center'><input type='button' class='tiny_red_btn' onClick=\"ChangeCheckpoint('".$row["checkpoint_row_id"]."', '$scorecard_id', 'delete_checkpoint')\" value='"._QXZ("DELETE")."'></td>";
			echo "</tr>";
		}
		echo "<tr><td colspan='3' class='small_display_header' align='right'>"._QXZ("PASSING SCORE").":</td><td align='center'><input type='text' size='2' maxlength='3' id='passing_score".$scorecard_id."' name='passing_score".$scorecard_id."' onBlur=\"ChangeCheckpoint('0', '$scorecard_id', 'update_scorecard', 'passing_score')\" value='$passing_score'></td><td align='left' class='small_display_header'>/ $max_score</td><td colspan='3'>&nbsp;</td></tr>";
	} else {
		echo "<tr><th colspan='8' class='small_display_header'>** "._QXZ("CURRENTLY NO CHECKPOINTS FOR SCORECARD")." $scorecard_id **</td></tr>\n";
		echo "<tr>";
		echo "<th colspan='8' class='small_display_header'>"._QXZ("NAME").": <input type='text' size='20' maxlength='255' id='scorecard_name".$scorecard_id."' name='scorecard_name".$scorecard_id."' onBlur=\"ChangeCheckpoint('0', '$scorecard_id', 'update_scorecard', 'scorecard_name')\" value='$scorecard_name'></th>";
		echo "</tr>";
	}
	
	echo "<tr height='20'><th colspan='8'>&nbsp;</th></tr>\n";

	echo "<tr>";
	echo "<th colspan='8' class='display_white_header'>"._QXZ("ADD NEW CHECKPOINT FOR SCORECARD")." $scorecard_id</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Order")."$NWB#qc_scorecards-order$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Active")."$NWB#qc_scorecards-active_checkpoint$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Checkpoint Text")."$NWB#qc_scorecards-checkpoint_text$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Preset comments")."$NWB#qc_scorecards-preset_comments$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Points")."$NWB#qc_scorecards-points$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Instant Fail")."$NWB#qc_scorecards-instant_fail$NWE</th>";
	echo "<th class='small_display_header' nowrap>"._QXZ("Admin Notes")."$NWB#qc_scorecards-admin_notes$NWE</th>";
	#echo "<th class='small_display_header'>Commission loss</th>";
	echo "<th class='small_display_header'>&nbsp;</th>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<select name='new_checkpoint_rank' id='new_checkpoint_rank'>";
	for ($i=1; $i<=$next_rank; $i++) {
		echo "<option value='$i'".(($i==$next_rank) ? " selected" : "").">$i</option>\n";
	}
	echo "</select>\n";
	echo "</td>";
	echo "<td align='center'>";
	echo "<input type='checkbox' name='new_active' id='new_active' value='Y' checked>";
	echo "</td>";
	echo "<td align='center'>";
	echo "<textarea class='notes_box' rows=\"4\" cols=\"40\" id=\"new_checkpoint_text\" name=\"new_checkpoint_text\">**"._QXZ("Enter display text here")."**</textarea>";
	echo "</td>\n";
	echo "<td align='center'>";
	echo "<textarea class='notes_box' rows=\"4\" cols=\"20\" id=\"new_checkpoint_text_presets\" name=\"new_checkpoint_text_presets\"></textarea>";
	echo "</td>\n";
	echo "<td align='center'>";
	echo "<input type='text' name='new_checkpoint_points' id='new_checkpoint_points' size='2' maxlength='3' value='0'>";
	echo "</td>";
	echo "<td align='center'>";
	echo "<input type='checkbox' name='new_instant_fail' id='new_instant_fail' value='Y'>";
	echo "</td>";
	echo "<td align='center'><textarea class='notes_box' rows=\"2\" cols=\"25\" id=\"new_admin_notes\" name=\"new_admin_notes\"></textarea></td>\n";
#	echo "<td align='center'>";
#	echo "<input type='checkbox' name='new_commission_loss' id='new_commission_loss' value='Y'>";
#	echo "</td>";
	echo "<td align='center'><input type='button' class='tiny_green_btn' onClick=\"AddCheckpoint('$scorecard_id')\" value='"._QXZ("ADD")."'></td>";
	echo "</tr>";
	echo "</table>\n";
}

if ($qc_action=="reload_scorecard_display") 
	{
	echo "<table cellpadding=2 cellspacing=0 width='100%'>\n";
	echo "\t<tr bgcolor=black nowrap>\n";
	echo "\t\t<td align=left><font size=1 color=white>"._QXZ("Scorecard")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Checkpoints")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Pass/max score")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Campaigns")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("In-groups")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Lists")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Last modified")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Active")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>&nbsp;</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>&nbsp;</font></td>\n";
	echo "\t</tr>\n";
	# <select name='scorecard_id' onChange='LoadCheckpoints(this.value)'>
	$stmt="select * from quality_control_scorecards order by qc_scorecard_id asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	$i=0;
	while ($row=mysqli_fetch_array($rslt)) 
		{
		$i++;
		if ($i%2==0) {$bgcolor=$SSstd_row1_background;} else {$bgcolor=$SSstd_row2_background;}
		echo "\t<tr bgcolor='".$bgcolor."'>\n";
		echo "\t\t\t<td align=left><font size=1>$row[qc_scorecard_id] - $row[scorecard_name]</font></td>\n";

		$qstmt="select count(*), sum(checkpoint_points) from quality_control_checkpoints where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$checkpoints=$qrow[0];
		($checkpoints==0 ? $score_total="--" : $score_total=$qrow[1]);
		echo "\t\t\t<td align=left><font size=1>$checkpoints</font></td>\n";
		echo "\t\t\t<td align=left><font size=1>$row[passing_score] / $score_total</font></td>\n";


		$qstmt="select count(*) from vicidial_campaigns where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$campaigns_in_use=$qrow[0];
		echo "\t\t\t<td align=left><font size=1>$campaigns_in_use</font></td>\n";


		$qstmt="select count(*) from vicidial_inbound_groups where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$ingroups_in_use=$qrow[0];
		echo "\t\t\t<td align=left><font size=1>$ingroups_in_use</font></td>\n";


		$qstmt="select count(*) from vicidial_lists where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$lists_in_use=$qrow[0];
		echo "\t\t\t<td align=left><font size=1>$lists_in_use</font></td>\n";
		echo "\t\t\t<td align=left><font size=1>$row[last_modified]</font></td>\n";
		echo "\t\t\t<td align=left><input type='checkbox' name='active".$row["qc_scorecard_id"]."' id='active".$row["qc_scorecard_id"]."' onClick=\"ChangeCheckpoint('0', '$row[qc_scorecard_id]', 'update_scorecard', 'active')\"".($row["active"]=="Y" ? " checked" : "")."></td>\n";

		echo "\t\t\t<td align=left><input type='button' class='tiny_blue_btn' onClick=\"LoadCheckpoints('$row[qc_scorecard_id]')\" value='"._QXZ("MODIFY")."'></td>\n";
		echo "\t\t\t<td align=left><input type='button' class='tiny_red_btn' onClick=\"window.location.href='$PHP_SELF?scorecard_id=$row[qc_scorecard_id]&action=delete_scorecard'\" value='"._QXZ("DELETE")."'></font></td>\n";
		echo "</tr>";
		}
	echo "\t<tr bgcolor=black>\n";
	echo "\t\t<th colspan='10'><font size=1 color='white'><input type='button' class='tiny_blue_btn' onClick='NewScorecard()' value='"._QXZ("ADD NEW SCORECARD")."'></font></td>\n";
	echo "\t</tr>\n";
	echo "</table>\n";
	}

# QC_log($PHP_AUTH_USER, $queue_qc_user_group, $_SERVER['REMOTE_ADDR'], $queue_user, $_SERVER['SERVER_ADDR'], $queue_lead_id, $queue_list_id, $queue_campaign_id, $details);
function QC_log($qc_user, $queue_user, $queue_lead_id, $queue_list_id, $details)
	{
	global $link, $qc_row_id, $NOW_TIME, $view_epoch;

	$stmt="INSERT INTO vicidial_qc_agent_log (qc_user,qc_user_group,qc_user_ip,lead_user,web_server_ip,view_datetime,view_epoch,lead_id,list_id,campaign_id,processed,details)	values('" . mysqli_real_escape_string($link, $PHP_AUTH_USER) . "','$LOGuser_group','".$_SERVER['REMOTE_ADDR']."','$lead_user','".$_SERVER['SERVER_ADDR']."','$NOW_TIME','$view_epoch','" . mysqli_real_escape_string($link, $queue_lead_id) . "','" . mysqli_real_escape_string($link, $queue_list_id) . "','" . mysqli_real_escape_string($link, $queue_campaign_id) . "','N','$details')";
	$rslt=mysql_to_mysqli($stmt, $link);

	}