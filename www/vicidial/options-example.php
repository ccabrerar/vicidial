<?php
# options.php - manually defined options for vicidial admin scripts
# 
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
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
$RS_PAUSEcodes =		'N';
$RS_with_inbound =		'Y';
$RS_CARRIERstats =		0;	# 0=no, 1=yes
$RS_PRESETstats =		0;	# 0=no, 1=yes
$RS_AGENTtimeSTATS =	0;	# 0=no, 1=yes
$RS_droppedOFtotal =	0;	# 0=no, 1=yes

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

?>
