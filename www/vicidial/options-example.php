<?php
# options.php - manually defined options for vicidial admin scripts
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# rename this file to options.php for the settings here to go into effect
#
# CHANGELOG
# 101216-1043 - First Build 
# 110307-1039 - Added upper-case/lower-case user setting
# 110708-1730 - Added precision time setting
# 120102-2112 - Added inventory_allow_realtime option
# 120213-1500 - Added option to log non-agent-API calls
# 120713-1915 - Added option for extended vicidial_list fields for long surveys
# 130123-1950 - Added option for using non-selectable statuses in admin_modify_lead.php
# 130124-1735 - Added option to display first and last name in user stats results
# 140124-1003 - Removed extra newline at end of file
# 140624-1422 - Added droppedOFtotal options.php option
# 150619-0135 - Added DROPANSWERpercent_adjustment options.php option for AST_VDADstats.php
# 150909-1417 - Added $active_only_default_campaigns option for admin.php
# 160102-1249 - Added htmlconvert option for modify lead pages
# 160106-1321 - Added disable_user_group_bulk_change option
# 160715-0752 - Added graph_canvas_size option for HTML reports
# 190414-1121 - Added RS_logoutLINK
# 190420-1722 - Added RS_ListenBarge
# 190503-1544 - Added enable_status_mismatch_leadloader_option
# 190525-2145 - Added RS_agentWAIT option
# 200115-1157 - Added call report export ALTERNATE_2 header
# 200428-1336 - Added RS_INcolumnsHIDE, RS_report_default_format & RS_AGENTstatusTALLY options
# 200506-1628 - Added RS_CUSTINFOdisplay & RS_CUSTINFOminUL options
# 201107-2257 - Added RS_parkSTATS option
# 210314-2101 - Added RS_DIDdesc option
# 210618-0937 - Added CORS support
# 210625-1425 - Added RS_BargeSwap option for blind monitoring
# 211022-0733 - Added IR_SLA_all_statuses option for Inbound Reports
# 220120-0926 - Added audio_store_GSM_allowed option for the Audio Store
#

# used by the realtime_report.php script
$webphone_width =	'460';
$webphone_height =	'500';
$webphone_left =	'600';
$webphone_top =		'27';
$webphone_bufw =	'250';
$webphone_bufh =	'1';
$webphone_pad =		'10';
$webphone_clpos =	"<BR>  &nbsp; <a href=\"#\" onclick=\"hideDiv('webphone_content');\">webphone -</a>";

# example using thin webphone
#$webphone_width =       '1135';
#$webphone_height =      '36';
#$webphone_left =        '0';
#$webphone_top =         '50';
#$webphone_bufw =        '1300';
#$webphone_bufh =        '37';
#$webphone_pad =         '0';
#$webphone_clpos =       ' ';

# used by the realtime report
$RS_DB =				0;		# 1=debug on, 0=debug off
$RS_RR =				40;		# refresh rate
$RS_group =				'ALL-ACTIVE';	# selected campaign(s)
$RS_usergroup =			'';		# user group defined
$RS_UGdisplay =			0;		# 0=no, 1=yes
$RS_UidORname =			1;		# 0=id, 1=name
$RS_orderby =			'timeup';
$RS_SERVdisplay =		0;	# 0=no, 1=yes
$RS_CALLSdisplay =		1;	# 0=no, 1=yes
$RS_PHONEdisplay =		0;	# 0=no, 1=yes
$RS_CUSTPHONEdisplay =	0;	# 0=no, 1=yes
$RS_CUSTINFOdisplay =	0;	# 0=no, 1=yes
$RS_CUSTINFOminUL =		9;	# 7-9 (minimum user level to use CUST INFO option)
$RS_PAUSEcodes =		'N';
$RS_with_inbound =		'Y';
$RS_CARRIERstats =		0;	# 0=no, 1=yes
$RS_PRESETstats =		0;	# 0=no, 1=yes
$RS_AGENTtimeSTATS =	0;	# 0=no, 1=yes
$RS_droppedOFtotal =	0;	# 0=no, 1=yes
$RS_logoutLINK =		0;	# 0=no, 1=yes
$RS_parkSTATS =			0;	# 0=no, 1=yes, 2=limited
$RS_SLAinSTATS =		0;	# 0=no, 1=yes, 2=TMA
$RS_ListenBarge =		'MONITOR|BARGE|WHISPER';	# list of listen-related features separated by pipes: "MONITOR|BARGE|WHISPER"
$RS_BargeSwap =			0;	# 0=no, 1=yes   reverse the order of who is called first on barge calls
$RS_agentWAIT =			3;	# 3 or 4
$RS_INcolumnsHIDE =		0;	# 0=no, 1=yes  # whether to hide the 'HOLD' & 'IN-GROUP' columns in the agent detail section
$RS_DIDdesc =			0;	# 0=no, 1=yes  # whether to show a 'DID DESCRIPTION' column in the agent detail section
$RS_report_default_format = '';	# 'TEXT', 'HTML' or '': If set, this will override the System Setting for this report only
$RS_AGENTstatusTALLY =	'';	# <any valid status>: If set, will look at the number of calls statused by the agent in this status for today
							# WARNING!!! Using the above option may cause system lag issues, USE WITH CAUTION!

# used by agent reports
$user_case =			0;		# 1=upper-case, 2-lower-case, 0-no-case-change

# force time precision for reports
$TIME_agenttimedetail = 'H';	# H=hour, M=minute, S=second, HF=force hour

# used by inventory report
$inventory_allow_realtime = 0;	# allow real-time report generation for inventory report

# used by non-agent-API for non-admin functions
$api_url_log = 0;				# log non-agent-api calls to the vicidial_url_log

# extended vicidial_list fields for long surveys(requires database schema change)
$extended_vl_fields = 0;

# allow non-selectable statuses on the modify lead page
$nonselectable_statuses = 0;

# display first and last name in user stats results
$firstlastname_display_user_stats = 0;

# agent time detail report login/logout link for user
$atdr_login_logout_user_link = 0;

# alternate calculation of 'Percent of DROP Calls taken out of Answers' in AST_VDADstats.php 
$DROPANSWERpercent_adjustment = 0;

# Display only active campaigns by default in admin.php Campaigns Listings page 
$active_only_default_campaigns = 0;

# convert data to html readable in modify lead page fields
$htmlconvert=1;

# disable the user_group_bulk_change.php utility
$disable_user_group_bulk_change=0;

# canvas size in pixels for Chartjs-style graphs (width and height)
$graph_canvas_size=600;

# enable the leadloader's duplicate-with-status-mismatch setting
$enable_status_mismatch_leadloader_option=0;

# call report export ALTERNATE_2 header
$call_export_report_ALTERNATE_2_header="address3\tfirst_name\tlast_name\tphone_number\tstatus_name\tstatus_date\r\n";

# Inbound reports, use all statuses for SLA calculation
$IR_SLA_all_statuses=0;

# Allow GSM audio files to be manually uploaded to the Audio Store
$audio_store_GSM_allowed=0;


# CORS settings: (to enable, customize the variables below, and uncomment the "require_once('adminCORS.php');" line at the bottom)
# (NOTE: The first 3 variables must be set for these features to be active)
$CORS_allowed_origin		= '';	# if multiple origins allowed, separate them by a pipe (also allows PHP preg syntax)
									# examples: 'https://acme.org|https://internal.acme.org' or "https?:\/\/(.*\\.?example\\.com|localhost):?[0-9]*|null"
$CORS_allowed_methods		= '';	# if multiple methods allowed, separate them by a comma 
									# example: 'GET,POST,OPTIONS,HEAD'
$CORS_affected_scripts		= '';	# If multiple(but less than all) scripts affected, separate them by a space (see CORS_SUPPORT.txt doc for list of files)
									# examples: 'non_agent_api.php vdremote.php' or 'non_agent_api.php'
$CORS_allowed_headers		= '';	# passed in Access-Control-Allow-Headers http response header, 
									# examples: X-Requested-With, X-Forwarded-For, X-Forwarded-Proto, Authorization, Cookie, Content-Type
$CORS_allowed_credentials	= 'N';	# 'Y' or 'N', whether to send credentials to browser or not
$Xframe_options				= 'N';	# Not part of CORS, but can prevent Iframe/embed/etc... use by foreign website, will populate for all affected scripts
									# examples: 'N', 'SAMEORIGIN', 'DENY'   NOTE: using 'DENY' may break some admin screen functionality
$CORS_debug					= 0;	# 0 = no, 1 = yes (default is no) This will generate a lot of log entries in a CORSdebug_log.txt file
#	require_once('adminCORS.php');

?>
