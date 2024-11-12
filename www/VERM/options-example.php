<?php
# options.php - manually defined options for vicidial admin scripts
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
#
# rename this file to options.php for the settings here to go into effect
#
# CHANGELOG
# 240802-1250 - First Build

# If this option is set to 1, then the error_reporting in php.ini will be ignored and settings below will be used for this directory
$PHP_error_reporting_OVERRIDE =	0;
	# PHP error reporting options, set to 1 to keep the type of error from being displayed, either on-screen or to the error logs.
$PHP_error_reporting_HIDE_ERRORS =		0;	# STRONGLY advise leaving this value alone, but you do you.
$PHP_error_reporting_HIDE_WARNINGS =	0;
$PHP_error_reporting_HIDE_PARSES =		0;
$PHP_error_reporting_HIDE_NOTICES =		0;
$PHP_error_reporting_HIDE_DEPRECATIONS=	0;
?>
