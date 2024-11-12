<?php
# VERM_options.php - Vicidial Enhanced Reporting options holder
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1604 - First build
# 230106-1355 - Added auto-download limit and special status settings for outcomes report
#

/*
####################
$VERM_default_report_queue="000_ALL_Calls";
$VERM_default_outb_widget_queue="000_ALL_OUT";
$VERM_default_inb_widget_queue1="SALESLINE";
$VERM_default_inb_widget_queue2="SUPPORT";

$exc_addtl_statuses=" and status not in ('AFTHRS') ";
# $and_NANQUE_clause="and status!='NANQUE'";
# $or_NANQUE_clause="or status!='NANQUE'";

$SLA_LEVEL_PCT_clause=" and campaign_id in ('SALESLINE', 'SUPPORT') ";

$remote_agents_clause=" and extension not like 'R/%'";

$ivr_survey_ingroups_detail["521205"]=array("561401", "561402", "561403", "561404", "561505");
$ivr_survey_ingroups_voicemails["561505"]="t";

print "VERM_default_report_queue: $VERM_default_report_queue\n";
print "VERM_default_outb_widget_queue: $VERM_default_outb_widget_queue\n";
print "VERM_default_inb_widget_queue1: $VERM_default_inb_widget_queue1\n";
print "VERM_default_inb_widget_queue2: $VERM_default_inb_widget_queue2\n\n";
print "exc_addtl_statuses: $exc_addtl_statuses\n";
print "SLA_LEVEL_PCT_clause: $SLA_LEVEL_PCT_clause\n";
print "remote_agents_clause: $remote_agents_clause\n";

print "ivr_survey_ingroups_detail: ";
print_r($ivr_survey_ingroups_detail);
print "ivr_survey_ingroups_voicemails: ";
print_r($ivr_survey_ingroups_voicemails);
print "\n\n\n";
###################
*/

$exc_addtl_statuses="";
$auto_download_limit=99999999999;

$lost_statuses_array=array();
$known_outcome_statuses=array();
$unknown_network_statuses=array();
$outcome_status_overrides=array();
$outcome_lagged_status_overrides=array();
$lost_statuses="";
$exc_lost_statuses="";

$ivr_survey_ingroups_detail=array();
$ivr_survey_ingroups_voicemails=array();

$VERM_default_report_queue="";
$VERM_default_outb_widget_queue="";
$VERM_default_inb_widget_queue1="";
$VERM_default_inb_widget_queue2="";
$SLA_LEVEL_PCT_clause="";
$remote_agents_clause="";

$options_stmt="select container_entry from vicidial_settings_containers where container_id='VERM_REPORT_OPTIONS'";
$options_rslt=mysql_to_mysqli($options_stmt, $link);
if (mysqli_num_rows($options_rslt)>0)
	{
	$options_row=mysqli_fetch_row($options_rslt);
	$container_entry=explode("\n", $options_row[0]);

	for ($q=0; $q<count($container_entry); $q++)
		{
		if (!preg_match('/^\;/', $container_entry[$q]) && preg_match('/\s\=\>\s/', $container_entry[$q]))
			{
			$container_setting=explode(" => ", trim($container_entry[$q]));
			$setting_name=$container_setting[0];
			$setting_value=$container_setting[1];

			if ($setting_name=="omit_remote_agents" && $setting_value)
				{$remote_agents_clause=" and extension not like 'R/%'";} 
			if ($setting_name=="VERM_default_report_queue" && $setting_value)
				{$VERM_default_report_queue=$setting_value;}

			if ($setting_name=="VERM_default_outb_widget_queue" && $setting_value)
				{
				$setting_value_ary=explode("|", $setting_value);
				$VERM_default_outb_widget_queue=$setting_value_ary[0];
				if (strlen($setting_value_ary[1])>0)
					{
					$VERM_default_outb_widget_queue_name=$setting_value_ary[1];
					}
				else
					{
					$VERM_default_outb_widget_queue_name=GetQueueName($VERM_default_outb_widget_queue);
					}
				}
			if ($setting_name=="VERM_default_inb_widget_queue1" && $setting_value)
				{
				$setting_value_ary=explode("|", $setting_value);
				$VERM_default_inb_widget_queue1=$setting_value[0];
				if (strlen($setting_value_ary[1])>0)
					{
					$VERM_default_inb_widget_queue1_name=$setting_value_ary[1];
					}
				else
					{
					$VERM_default_inb_widget_queue1_name=GetQueueName($VERM_default_inb_widget_queue1);
					}
				}
			if ($setting_name=="VERM_default_inb_widget_queue2" && $setting_value)
				{
				$setting_value_ary=explode("|", $setting_value);
				$VERM_default_inb_widget_queue2=$setting_value[0];
				if (strlen($setting_value_ary[1])>0)
					{
					$VERM_default_inb_widget_queue2_name=$setting_value_ary[1];
					}
				else
					{
					$VERM_default_inb_widget_queue2_name=GetQueueName($VERM_default_inb_widget_queue2);
					}
				}
			
			if ($setting_name=="show_full_agent_info" && $setting_value)
				{$show_full_agent_info=$setting_value;}

			if ($setting_name=="auto_download_limit" && $setting_value)
				{$auto_download_limit=$setting_value;}

			if ($setting_name=="exc_addtl_statuses" && $setting_value)
				{
				$exc_statuses=explode(",", $setting_value);
				if (count($exc_statuses)>0)
					{
					$exc_addtl_statuses=" and status not in ('".implode("', '", $exc_statuses)."') ";
					}
				}

			if ($setting_name=="lost_statuses" && $setting_value)
				{
				$lost_statuses_array=explode(",", $setting_value);
				if (count($lost_statuses_array)>0)
					{
					$lost_statuses=" and status in ('".implode("', '", $lost_statuses_array)."') ";
					$exc_lost_statuses=" and status NOT in ('".implode("', '", $lost_statuses_array)."') ";
					}
				}

			if ($setting_name=="outcome_lagged_status_overrides" && $setting_value)
				{
				$outcome_lagged_status_overrides=explode(",", $setting_value);
				}

			if ($setting_name=="unknown_network_statuses" && $setting_value)
				{
				$unknown_network_statuses=explode(",", $setting_value);
				}

/*
			if ($setting_name=="outcome_lagged_status_overrides" && $setting_value)
				{
				$setting_value_ary=explode("|", $setting_value);
				for ($i=0; $i<count($setting_value_ary); $i++)
					{
					$olso_ary=explode(",", $setting_value_ary[$i]);
					if (strlen($olso_ary[0])>0 && strlen($olso_ary[1])>0)
						{
						$outcome_lagged_status_overrides["$olso_ary[0]"]=explode("-", $olso_ary[1]);
						}
					}
				}
*/

			if ($setting_name=="outcome_status_overrides" && $setting_value)
				{
				$setting_value_ary=explode("|", $setting_value);
				for ($i=0; $i<count($setting_value_ary); $i++)
					{
					$oso_ary=explode(",", $setting_value_ary[$i]);
					if (strlen($oso_ary[0])>0 && strlen($oso_ary[1])>0)
						{
						$outcome_status_overrides["$oso_ary[0]"]=$oso_ary[1];
						}
					}
				}

			if ($setting_name=="SLA_LEVEL_PCT_ingroups" && $setting_value)
				{
				$sla_percents=explode(",", $setting_value);
				if (count($sla_percents)>0)
					{
					$SLA_LEVEL_PCT_clause=" and campaign_id in ('".implode("', '", $sla_percents)."') ";
					}
				}

			if ($setting_name=="ivr_survey_ingroups_detail" && $setting_value && preg_match('/\|/', $setting_value))
				{
				$isid_array=explode("|", $setting_value);
				$ingroup=$isid_array[0];
				$callmenus=explode(",", $isid_array[1]);
				if (count($callmenus)>0)
					{
					$ivr_survey_ingroups_detail["$ingroup"]=$callmenus;
					}
				}

			if ($setting_name=="ivr_survey_ingroups_voicemails" && $setting_value && preg_match('/\|/', $setting_value))
				{
				$isid_array=explode("|", $setting_value);
				$ingroup=$isid_array[0];
				$options=$isid_array[1];
				if (strlen($options)>0)
					{
					$ivr_survey_ingroups_voicemails["$ingroup"]=$options;
					}
				}

			if ($setting_name=="caller_id_override" && $setting_value)
				{$caller_id_override=$setting_value;}

			# print "$container_entry[$q]\n";
			}
		}
	}

function GetQueueName($temp_queue)
	{
	global $DB, $link;
	$queue_name_stmt="select queue_group_name from vicidial_queue_groups where queue_group='$temp_queue'";
	$queue_name_rslt=mysql_to_mysqli($queue_name_stmt, $link);
	$temp_queue_name="";
	while ($queue_name_row=mysqli_fetch_row($queue_name_rslt))
		{
		$temp_queue_name=$queue_name_row[0];
		}
	return $temp_queue_name;
	}

/*
print "VERM_default_report_queue: $VERM_default_report_queue\n";
print "VERM_default_outb_widget_queue: $VERM_default_outb_widget_queue\n";
print "VERM_default_inb_widget_queue1: $VERM_default_inb_widget_queue1\n";
print "VERM_default_inb_widget_queue2: $VERM_default_inb_widget_queue2\n\n";
print "exc_addtl_statuses: $exc_addtl_statuses\n";
print "SLA_LEVEL_PCT_clause: $SLA_LEVEL_PCT_clause\n";
print "remote_agents_clause: $remote_agents_clause\n";

print "ivr_survey_ingroups_detail: ";
print_r($ivr_survey_ingroups_detail);
print "ivr_survey_ingroups_voicemails: ";
print_r($ivr_survey_ingroups_voicemails);
print "\n\n\n";
*/
?>
