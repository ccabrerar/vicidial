<?php
# crm_settings.php - settings for crm_example.php and front.php to use
# 
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGELOG
# 151229-1556 - First Build 
#

# The full URL to the Vicidial Agent Screen, (usually something like "http://server/agc/vicidial.php")
$agent_screen_url = '';

# The full URL to the Vicidial Agent API, (usually something like "http://server/agc/api.php")
$api_url = '';

# The user and pass of a level 8 user that has API and modify lead permissions
$api_user = '';
$api_pass = '';


# The name of the CRM IFRAME that will be used for the CRM portion(default is 'crmagent')
$frame_id = 'crmagent';

# The URL of the CRM welcome page (Don't forget to also set the Start Call URL in your campaign!)
$crm_url = './crm_example.php?stage=welcome';


# The URL of the front page (the page that loads the CRM and Vicidial IFRAMEs)
$front_url = './front.php';


##### interface size parameters #####

# vicidial screen width and height
$agent_screen_width = 1000;
$agent_screen_height = 550;

# vicidial screen width and height
$crm_screen_width = 1100;
$crm_screen_height = 600;


?>
