<?php
# VERM_global_vars.inc - Vicidial Enhanced Reporting global variables
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1622 - First build
# 230106-1317 - Added missing QXZ, added pull of selectable statuses, human answered statues now include DISPO and SYSTEM
# 240801-1130 - Code updates for PHP8 compatibility
#

if (isset($_GET["start_date"]))			{$start_date=$_GET["start_date"];}
	elseif (isset($_POST["start_date"]))	{$start_date=$_POST["start_date"];}
if (isset($_GET["start_time"]))			{$start_time=$_GET["start_time"];}
	elseif (isset($_POST["start_time"]))	{$start_time=$_POST["start_time"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["end_time"]))			{$end_time=$_GET["end_time"];}
	elseif (isset($_POST["end_time"]))	{$end_time=$_POST["end_time"];}
if (isset($_GET["campaigns"]))			{$campaigns=$_GET["campaigns"];}
	elseif (isset($_POST["campaigns"]))	{$campaigns=$_POST["campaigns"];}
if (isset($_GET["ingroups"]))			{$ingroups=$_GET["ingroups"];}
	elseif (isset($_POST["ingroups"]))	{$ingroups=$_POST["ingroups"];}
if (isset($_GET["did"]))			{$did=$_GET["did"];}
	elseif (isset($_POST["did"]))	{$did=$_POST["did"];}
if (isset($_GET["users"]))			{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))	{$users=$_POST["users"];}
if (isset($_GET["user_groups"]))			{$user_groups=$_GET["user_groups"];}
	elseif (isset($_POST["user_groups"]))	{$user_groups=$_POST["user_groups"];}
if (isset($_GET["outcomes"]))			{$outcomes=$_GET["outcomes"];}
	elseif (isset($_POST["outcomes"]))	{$outcomes=$_POST["outcomes"];}

$report_name="VICIdial Enhanced Reporting Module";

$start_date=preg_replace('/[^-0-9]/', '', $start_date);
$start_time=preg_replace('/[^:0-9]/', '', $start_time);
$end_date=preg_replace('/[^-0-9]/', '', $end_date);
$end_time=preg_replace('/[^:0-9]/', '', $end_time);
$did = preg_replace('/[^0-9]/','',$did);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);

	$campaigns = preg_replace('/[^-_0-9a-zA-Z]/','',$campaigns);
	$ingroups = preg_replace('/[^-_0-9a-zA-Z]/','',$ingroups);
	$users = preg_replace('/[^-_0-9a-zA-Z]/','',$users);
	$user_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$user_groups);
	$outcomes = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$outcomes);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);

	$campaigns = preg_replace('/[^-_0-9\p{L}]/u','',$campaigns);
	$ingroups = preg_replace('/[^-_0-9\p{L}]/u','',$ingroups);
	$users = preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$user_groups = preg_replace('/[^-_0-9\p{L}]/u','',$user_groups);
	$outcomes = preg_replace('/[^\s\-_0-9\p{L}]/u','',$outcomes);
	}


#### User account filtering ####

## ACCESS CONTROLLED BY USER GROUPS ##
$stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level,alter_admin_interface_options,modify_custom_dialplans,modify_languages,selected_language,user_choose_language,modify_colors,api_only_user,modify_auto_reports,modify_ip_lists,export_gdpr_leads,mobile_number,two_factor_override from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfull_name				=$row[3];
$LOGuser_level				=$row[4];
$LOGuser_group				=$row[5];
$LOGdelete_users			=$row[8];
$LOGdelete_user_groups		=$row[9];
$LOGdelete_lists			=$row[10];
$LOGdelete_campaigns		=$row[11];
$LOGdelete_ingroups			=$row[12];
$LOGdelete_remote_agents	=$row[13];
$LOGload_leads				=$row[14];
$LOGcampaign_detail			=$row[15];
$LOGast_admin_access		=$row[16];
$LOGast_delete_phones		=$row[17];
$LOGdelete_scripts			=$row[18];
$LOGmodify_leads			=$row[19];
$LOGdelete_filters			=$row[29];
$LOGalter_agent_interface	=$row[30];
$LOGdelete_call_times		=$row[32];
$LOGmodify_call_times		=$row[33];
$LOGmodify_users			=$row[34];
$LOGmodify_campaigns		=$row[35];
$LOGmodify_lists			=$row[36];
$LOGmodify_scripts			=$row[37];
$LOGmodify_filters			=$row[38];
$LOGmodify_ingroups			=$row[39];
$LOGmodify_usergroups		=$row[40];
$LOGmodify_remoteagents		=$row[41];
$LOGmodify_servers			=$row[42];
$LOGview_reports			=$row[43];
$LOGmodify_dids				=$row[56];
$LOGdelete_dids				=$row[57];
$LOGmanager_shift_enforcement_override=$row[61];
$LOGexport_reports			=$row[64];
$LOGdelete_from_dnc			=$row[65];
$LOGemail					=$row[66];
$LOGcallcard_admin			=$row[70];
$LOGforce_change_password	=$row[71];
$LOGmodify_shifts			=$row[72];
$LOGmodify_phones			=$row[73];
$LOGmodify_carriers			=$row[74];
$LOGmodify_labels			=$row[75];
$LOGmodify_statuses			=$row[76];
$LOGmodify_voicemail		=$row[77];
$LOGmodify_audiostore		=$row[78];
$LOGmodify_moh				=$row[79];
$LOGmodify_tts				=$row[80];
$LOGmodify_contacts			=$row[81];
$LOGmodify_same_user_level	=$row[82];
$LOGalter_admin_interface	=$row[83];
$LOGmodify_custom_dialplans =$row[84];
$LOGmodify_languages		=$row[85];
$LOGselected_language		=$row[86];
$LOGuser_choose_language	=$row[87];
$LOGmodify_colors			=$row[88];
$LOGapi_only_user			=$row[89];
$LOGmodify_auto_reports		=$row[90];
$LOGmodify_ip_lists			=$row[91];
$LOGexport_gdpr_leads		=$row[92];
$LOGmobile_number			=$row[93];
$LOGtwo_factor_override		=$row[94];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times,qc_allowed_campaigns,qc_allowed_inbound_groups from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];
$LOGqc_allowed_campaigns =		$row[4];
$LOGqc_allowed_inbound_groups =	$row[5];

$LOGallowed_campaignsSQL='';
$notLOGallowed_campaignsSQL = "and campaign_id IN('')";
$campLOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$notLOGallowed_campaignsSQL = "and campaign_id NOT IN('$rawLOGallowed_campaignsSQL')";
	$campLOGallowed_campaignsSQL = "and camp.campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

if (preg_match("/DRA/",$SShosted_settings))
	{$LOGmodify_remoteagents=0;}

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

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}
$regexLOGadmin_viewable_call_times = " $LOGadmin_viewable_call_times ";

$UUgroups_list='';
if ($admin_viewable_groupsALL > 0)
	{$UUgroups_list .= "<option value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";}
$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
$UUgroups_to_print = mysqli_num_rows($rslt);
$o=0;
while ($UUgroups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$UUgroups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

$LOGqc_allowed_campaignsSQL='';
$whereLOGqc_allowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGqc_allowed_campaigns)) )
	{
	$rawLOGqc_allowed_campaignsSQL = preg_replace("/ -/",'',$LOGqc_allowed_campaigns);
	$rawLOGqc_allowed_campaignsSQL = preg_replace("/ /","','",$rawLOGqc_allowed_campaignsSQL);
	$LOGqc_allowed_campaignsSQL = "and campaign_id IN('$rawLOGqc_allowed_campaignsSQL')";
	$whereLOGqc_allowed_campaignsSQL = "where campaign_id IN('$rawLOGqc_allowed_campaignsSQL')";
	}

$LOGqc_allowed_inbound_groupsSQL='';
$whereLOGqc_allowed_inbound_groupsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGqc_allowed_inbound_groups)) )
	{
	$rawLOGqc_allowed_inbound_groupsSQL = preg_replace("/ -/",'',$LOGqc_allowed_inbound_groups);
	$rawLOGqc_allowed_inbound_groupsSQL = preg_replace("/ /","','",$rawLOGqc_allowed_inbound_groupsSQL);
	$LOGqc_allowed_inbound_groupsSQL = "and group_id IN('$rawLOGqc_allowed_inbound_groupsSQL')";
	$whereLOGqc_allowed_inbound_groupsSQL = "where group_id IN('$rawLOGqc_allowed_inbound_groupsSQL')";
	}

################################


### Full information assembly - needed for display #################
$did_stmt="select did_id, did_pattern, did_description from vicidial_inbound_dids";
$did_rslt=mysql_to_mysqli($did_stmt, $link);
while ($did_row=mysqli_fetch_array($did_rslt))
	{
	$did_id_info["$did_row[0]"]=$did_row[2];
	$did_pattern_info["$did_row[1]"]=$did_row[2];
	}

$user_stmt="select user, full_name, user_group from vicidial_users";
$user_rslt=mysql_to_mysqli($user_stmt, $link);
while ($user_row=mysqli_fetch_array($user_rslt))
	{
	$fullname_info["$user_row[0]"]=$user_row[1];
	$users_group_info["$user_row[0]"]=$user_row[2];
	}
$PHP_AUTH_USER_fullname=$fullname_info["$PHP_AUTH_USER"];

$ug_stmt="select user_group, group_name from vicidial_user_groups";
$ug_rslt=mysql_to_mysqli($ug_stmt, $link);
while($ug_row=mysqli_fetch_array($ug_rslt))
	{
	$usergroup_info["$ug_row[0]"]=$ug_row[1];
	}

# $queueSQL_where="where campaign_id in ('".implode("','", $report_queues)."')";
# $queueSQL_and="where campaign_id in ('".implode("','", $report_queues)."')";

# Compile queue names into an array for later
$queue_names=array();
$queue_stmt="select group_id, group_name from vicidial_inbound_groups UNION select campaign_id, campaign_name from vicidial_campaigns $queueSQL_where";
$queue_rslt=mysql_to_mysqli($queue_stmt, $link);
while ($queue_row=mysqli_fetch_row($queue_rslt))
	{
	$queue_names["$queue_row[0]"]="$queue_row[1]";
	}


# Compile group names into an array for later
$ingroup_stmt="select group_id, group_name from vicidial_inbound_groups order by group_id asc";
$ingroup_rslt=mysql_to_mysqli($ingroup_stmt, $link);
while ($ingroup_row=mysqli_fetch_row($ingroup_rslt))
	{
	$ingroup_names["$ingroup_row[0]"]="$ingroup_row[1]";
	}

# Compile callmenu names into an array for later
$callmenu_stmt="select menu_id, menu_name from vicidial_call_menu order by menu_id asc";
$callmenu_rslt=mysql_to_mysqli($callmenu_stmt, $link);
while ($callmenu_row=mysqli_fetch_row($callmenu_rslt))
	{
	$callmenu_names["$callmenu_row[0]"]="$callmenu_row[1]";
	}

# Compile closer campaigns for waiting calls
$campaign_closer_names=array();
# 7/31/24 - below arrays moved here to be declared as they need to exist for every campaign in the queue, not just ones where there are pause codes.
# Null campaign entries for the pause code arrays will cause an error;
$billable_pause_codes=array();
$payable_pause_codes=array();
$sale_array=array("SYSTEM" => array());
$selectable_array=array("SYSTEM" => array());
$human_ans_array=array("SYSTEM" => array());
$contact_array=array("SYSTEM" => array());
$campaign_closer_stmt="select campaign_id, closer_campaigns from vicidial_campaigns $queueSQL_where";
$campaign_closer_rslt=mysql_to_mysqli($campaign_closer_stmt, $link);
while ($campaign_closer_row=mysqli_fetch_row($campaign_closer_rslt))
	{
	$campaign_closer_names["$campaign_closer_row[0]"]="$campaign_closer_row[1]";
	$billable_pause_codes["$campaign_closer_row[0]"]=array();
	$payable_pause_codes["$campaign_closer_row[0]"]=array();
	$sale_array["$campaign_closer_row[0]"]=array();
	$selectable_array["$campaign_closer_row[0]"]=array();
	$human_ans_array["$campaign_closer_row[0]"]=array();
	$contact_array["$campaign_closer_row[0]"]=array();
	}

# Compile closer campaigns for waiting calls
$status_names=array();
$status_stmt="select status, status_name from vicidial_statuses UNION select status, status_name from vicidial_campaign_statuses";
$status_rslt=mysql_to_mysqli($status_stmt, $link);
while ($status_row=mysqli_fetch_row($status_rslt))
	{
	$status_names["$status_row[0]"]="$status_row[1]";
	}

# Compile "terminal" names into an array for later
$terminal_names=array();
$terminal_stmt="select user, full_name from vicidial_users";
$terminal_rslt=mysql_to_mysqli($terminal_stmt, $link);
while ($terminal_row=mysqli_fetch_row($terminal_rslt))
	{
	$terminal_names["$terminal_row[0]"]="$terminal_row[1]";
	}

# $sale_array=array("SYSTEM" => array());
$sale_stmt="select distinct status, 'SYSTEM' as campaign_id from vicidial_statuses where sale='Y' UNION select distinct status, campaign_id from vicidial_campaign_statuses where sale='Y'";
$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
while ($sale_row=mysqli_fetch_array($sale_rslt))
	{
	if (!array_key_exists("$sale_row[campaign_id]", $sale_array)) {$sale_array["$sale_row[campaign_id]"]=array();}
	array_push($sale_array["$sale_row[campaign_id]"], $sale_row["status"]);
	}

# $selectable_array=array("SYSTEM" => array());
$selectable_stmt="select distinct status, 'SYSTEM' as campaign_id from vicidial_statuses where selectable='Y' UNION select distinct status, campaign_id from vicidial_campaign_statuses where selectable='Y'";
$selectable_rslt=mysql_to_mysqli($selectable_stmt, $link);
while ($selectable_row=mysqli_fetch_array($selectable_rslt))
	{
	if (!array_key_exists("$selectable_row[campaign_id]", $selectable_array)) {$selectable_array["$selectable_row[campaign_id]"]=array();}
	array_push($selectable_array["$selectable_row[campaign_id]"], $selectable_row["status"]);
	}

# $human_ans_array=array("SYSTEM" => array());
$human_ans_stmt="select distinct status, 'SYSTEM' as campaign_id from vicidial_statuses where human_answered='Y' UNION select distinct status, campaign_id from vicidial_campaign_statuses where human_answered='Y' UNION select 'DISPO', 'SYSTEM'";
$human_ans_rslt=mysql_to_mysqli($human_ans_stmt, $link);
while ($human_ans_row=mysqli_fetch_array($human_ans_rslt))
	{
	if (!array_key_exists("$human_ans_row[campaign_id]", $human_ans_array)) {$human_ans_array["$human_ans_row[campaign_id]"]=array();}
	array_push($human_ans_array["$human_ans_row[campaign_id]"], $human_ans_row["status"]);
	}

# $contact_array=array("SYSTEM" => array());
$contact_stmt="select distinct status, 'SYSTEM' as campaign_id from vicidial_statuses where customer_contact='Y' UNION select distinct status, campaign_id from vicidial_campaign_statuses where customer_contact='Y'";
$contact_rslt=mysql_to_mysqli($contact_stmt, $link);
while ($contact_row=mysqli_fetch_array($contact_rslt))
	{
	if (!array_key_exists("$contact_row[campaign_id]", $contact_array)) {$contact_array["$contact_row[campaign_id]"]=array();}
	array_push($contact_array["$contact_row[campaign_id]"], $contact_row["status"]);
	}

### OVERRIDE ANY AND ALL STATUS NAMES FROM SETTING CONTAINERS
$container_stmt="select * from vicidial_settings_containers where container_id='VERM_STATUS_NAMES_OVERRIDE'";
$container_rslt=mysql_to_mysqli($container_stmt, $link);
if (mysqli_num_rows($container_rslt)>0)
	{
	while($container_row=mysqli_fetch_array($container_rslt))
		{
		$container_entry=$container_row["container_entry"];
		$container_array=explode("\n", $container_entry);
		for ($i=0; $i<count($container_array); $i++)
			{
			# Assumed format of status|status_name
			if (!preg_match('/^;/', $container_array[$i]))
				{
				$new_status=explode("|", $container_array[$i]);
				if (strlen($new_status[0])>0)
					{
					$status_names["$new_status[0]"]="$new_status[1]";
					}
				}
			}
		}
	}

$pause_code_stmt="select * from vicidial_pause_codes";
$pause_code_rslt=mysql_to_mysqli($pause_code_stmt, $link);
# $billable_pause_codes=array();
# $payable_pause_codes=array();
$pause_code_names=array();
while ($pause_code_row=mysqli_fetch_array($pause_code_rslt))
	{
	$pause_code_campaign_id=$pause_code_row["campaign_id"];
	$pause_code=$pause_code_row["pause_code"];
	$pause_code_name=$pause_code_row["pause_code_name"];
	$billable=$pause_code_row["billable"];
	$pause_name_key=$pause_code_campaign_id."-".$pause_code;
	$pause_name_login=$pause_code_campaign_id."-LOGIN";
	$pause_name_lagged=$pause_code_campaign_id."-LAGGED";

	$pause_code_names["$pause_name_key"]=$pause_code_name;
	$pause_code_names["$pause_name_login"]="Login";
	$pause_code_names["$pause_name_lagged"]="Network Delay";
	if (!$billable_pause_codes["$pause_code_campaign_id"])
		{
		$billable_pause_codes["$pause_code_campaign_id"]=array();
		}
	if (!$payable_pause_codes["$pause_code_campaign_id"])
		{
		$payable_pause_codes["$pause_code_campaign_id"]=array();
		}

	if (!in_array($pause_code, $billable_pause_codes["$pause_code_campaign_id"]) && $billable=="YES")
		{
		array_push($billable_pause_codes["$pause_code_campaign_id"], $pause_code);
		}
	if (!in_array($pause_code, $payable_pause_codes["$pause_code_campaign_id"]))
		{
		
		}
	}

$billable_pause_codes["SYSTEM"]=array();
array_push($billable_pause_codes["SYSTEM"], "-");
array_push($billable_pause_codes["SYSTEM"], "BLANK");
array_push($billable_pause_codes["SYSTEM"], "LOGIN");
array_push($billable_pause_codes["SYSTEM"], "LAGGED");

$payable_pause_codes["SYSTEM"]=array();
array_push($payable_pause_codes["SYSTEM"], "-");
array_push($payable_pause_codes["SYSTEM"], "BLANK");
array_push($payable_pause_codes["SYSTEM"], "LOGIN");
array_push($payable_pause_codes["SYSTEM"], "LAGGED");

#print_r($billable_pause_codes);
#print_r($payable_pause_codes);
#print_r($pause_code_names); die;
#####################################################


### SLA query assembly ###
if (!$SLA_initial_interval) {$SLA_initial_interval=5;}

if (!$SLA_initial_period) {$SLA_initial_period=20;}
if ($SLA_initial_period<$SLA_initial_interval) {$SLA_initial_period=$SLA_initial_interval;}

if (!$SLA_interval) {$SLA_interval=10;}

if (!$SLA_max_period) {$SLA_max_period=120;}
if ($SLA_max_period<$SLA_interval) {$SLA_max_period=$SLA_interval;}

$SLA_query=""; $SLA_column_names=array();
for($i=$SLA_initial_interval; $i<$SLA_initial_period; $i+=$SLA_initial_interval)
	{
	$SLA_query.="sum(if(queue_seconds<=".$i.", 1, 0)) as within_".$i.", ";
	$SLA_column_names[]="< $i";
	}

for($i=$SLA_initial_period; $i<=$SLA_max_period; $i+=$SLA_interval)
	{
	$SLA_query.="sum(if(queue_seconds<=".$i.", 1, 0)) as within_".$i.", ";
	$SLA_column_names[]="< $i";
	}

$SLA_query=preg_replace('/, $/', '', $SLA_query);
############################


### NANQUE CLAUSES - MIGHT RECOGNIZE NANQUE ###
# $and_NANQUE_clause="and status!='NANQUE'";
# $or_NANQUE_clause="or status!='NANQUE'";
###################################################################


### GRAPH COLOR ARRAYS ###
$background_colors=array("#009900", "#990000", "#000099", "#999900", "#990099", "#009999", "#999999", "#339933", "#993333", "#333399", "#999933", "#993399", "#339999", "#339900", "#993300", "#330099", "#009933", "#990033", "#003399", "#00CC00", "#CC0000", "#0000CC", "#CCCC00", "#CC00CC", "#00CCCC", "#33CC33", "#CC3333", "#3333CC", "#CCCC33", "#CC33CC", "#33CCCC", "#33CC00", "#CC3300", "#3300CC", "#00CC33", "#CC0033", "#0033CC", "#66CC00", "#CC6600", "#6600CC", "#00CC66", "#CC0066", "#0066CC", "#33CC66", "#CC3366", "#3366CC", "#66CC33", "#CC6633", "#6633CC", "#66CC66", "#CC6666", "#6666CC", "#CCCC66", "#CC66CC", "#66CCCC", "#CCCCCC");
$border_colors=array("#00FF00", "#FF0000", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#FFFFFF", "#00FF00", "#FF0000", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#00FF00", "#FF0000", "#0000FF", "#00FF00", "#FF0000", "#0000FF", "#00FF00", "#FF0000", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#00FF00", "#FF0000", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#00FF00", "#FF0000", "#0000FF", "#00FF00", "#FF0000", "#0000FF", "#00FF00", "#FF0000", "#0000FF", "#00FF00", "#FF0000", "#0000FF", "#66FF66", "#FF6666", "#6666FF", "#66FF66", "#FF6666", "#6666FF", "#00FF00", "#FF0000", "#0000FF", "#FFFF00", "#FF00FF", "#00FFFF", "#FFFFFF");
###########################

### REPORT FULL NAMES ###
$report_full_names_array["ANSWERED"]=_QXZ("Answered calls");
$report_full_names_array["ANSWERED_DT"]=_QXZ("Answered calls details");
$report_full_names_array["UNANSWERED"]=_QXZ("Unanswered calls");
$report_full_names_array["UNANSWERED_DT"]=_QXZ("Unanswered calls details");
$report_full_names_array["IVR"]=_QXZ("IVR call statistics");
$report_full_names_array["AREA"]=_QXZ("Answered/unanswered call counts by area code");
$report_full_names_array["ATT"]=_QXZ("Inbound call distribution");
$report_full_names_array["DAY"]=_QXZ("Call reports - day/date breakdown");
$report_full_names_array["HOUR"]=_QXZ("Call reports - hourly breakdown");
$report_full_names_array["DOW"]=_QXZ("Call reports - day-of-week breakdown");
$report_full_names_array["AGENTS"]=_QXZ("Agent statistics");
$report_full_names_array["AGENTS_DT"]=_QXZ("Agent call details");
$report_full_names_array["OUTCOMES"]=_QXZ("Outcome/status call statistics");
$report_full_names_array["STATS"]=_QXZ("Selected call statistics");
$report_full_names_array["CALLS"]=_QXZ("Combined answered/unanswered call details");
#########################
?>
