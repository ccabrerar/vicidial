<?php
# adminCORS.php - CORS processing and responses for Cross-Origin Features
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CORS settings coming from the options.php script (the first 3 variables must be set for these features to be active)
#	$CORS_allowed_origin		= '';	# if multiple origins allowed, separate them by a pipe (also allows PHP preg syntax)
#										# examples: 'https://acme.org|https://internal.acme.org' or "https?:\/\/(.*\\.?example\\.com|localhost):?[0-9]*|null"
#	$CORS_allowed_methods		= '';	# if multiple methods allowed, separate them by a comma 
#										# example: 'GET,POST,OPTIONS,HEAD'
#	$CORS_affected_scripts		= '';	# If multiple(but less than all) scripts affected, separate them by a space (see CORS_SUPPORT.txt doc for list of files)
#										# examples: 'non_agent_api.php vdremote.php' or 'non_agent_api.php'
#	$CORS_allowed_headers		= '';	# passed in Access-Control-Allow-Headers http response header, 
#										# examples: X-Requested-With, X-Forwarded-For, X-Forwarded-Proto, Authorization, Cookie, Content-Type
#	$CORS_allowed_credentials	= 'N';	# 'Y' or 'N', whether to send credentials to browser or not
#	$Xframe_options				= 'N';	# Not part of CORS, but can prevent Iframe/embed/etc... use by foreign website, will populate for all affected scripts
#										# examples: 'N', 'SAMEORIGIN', 'DENY'   NOTE: using 'DENY' may break some admin screen functionality
#	$CORS_debug					= 0;	# 0 = no, 1 = yes (default is no) This will generate a lot of log entries in a CORSdebug_log.txt file
# 
# 
# CHANGELOG
# 210618-0938 - First Build
#

$NOW_TIME = date("Y-m-d H:i:s");

if (strlen($php_script) < 1)
	{$donothing=1;}
else
	{
	$CORS_origin = $_SERVER['HTTP_ORIGIN']; # The client browser origin server
	$CORS_method = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) ? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD']; # Either the requested HTTP method or the current one
	$CORS_affected_scripts = " $CORS_affected_scripts "; # surround with spaces for preg match below

	if ($CORS_debug > 0)
		{
		$fp = fopen ("./CORSdebug_log.txt", "a");
		fwrite ($fp, "$NOW_TIME CORS-Debug 1: BEGIN - |$CORS_allowed_origin($CORS_origin)|$CORS_allowed_methods($CORS_method)|$CORS_affected_scripts($php_script)|$CORS_allowed_credentials|$CORS_allowed_headers|$Xframe_options|$CORS_debug|\n");
		fclose($fp);
		}

	# if options.php $CORS_allowed_origin or $CORS_allowed_methods variables are not set, do nothing
	if ( (strlen($CORS_allowed_origin) < 1) or (strlen($CORS_allowed_methods) < 1) or (strlen($CORS_affected_scripts) < 1) )
		{
		if ($CORS_debug > 0)
			{
			$fp = fopen ("./CORSdebug_log.txt", "a");
			fwrite ($fp, "$NOW_TIME CORS-Debug 2: variable not set - |$CORS_allowed_origin|$CORS_allowed_methods|$CORS_affected_scripts|\n");
			fclose($fp);
			}
		}
	else
		{
		# check for affected scripts match (--ALL--, one-of-many)
		#if ( (preg_match('/ ' . $php_script . ' /i', $CORS_affected_scripts)) or ($CORS_affected_scripts == ' --ALL-- ') )
		if (preg_match('/ ' . $php_script . ' /i', $CORS_affected_scripts))
			{
			# check for allowed origin match (wildcard, one-of-many, preg-match) and check for allowed method match (one-of-many)
			if ( ( ($CORS_allowed_origin == '*') or (stripos($CORS_allowed_origin,$CORS_origin) !== false) or (preg_match('/' . $CORS_allowed_origin . '/i', $CORS_origin)) ) and (preg_match('/' . $CORS_method . '/i', $CORS_allowed_methods)) )
				{
				header('Access-Control-Allow-Origin: ' . $CORS_origin);
				header('Access-Control-Allow-Methods: ' . $CORS_allowed_methods);

				if (strlen($CORS_allowed_headers) > 0)
					{
					header('Access-Control-Allow-Headers: ' . $CORS_allowed_headers);
					}
				if ($CORS_allowed_credentials == 'Y')
					{
					header('Access-Control-Allow-Credentials: true');
					}
				if ($CORS_debug > 0)
					{
					$fp = fopen ("./CORSdebug_log.txt", "a");
					fwrite ($fp, "$NOW_TIME CORS-Debug 3: MATCHES found - |$CORS_allowed_origin($CORS_origin)|$CORS_allowed_methods($CORS_method)|$php_script\n");
					fclose($fp);
					}
				}
			else
				{
				if ($CORS_debug > 0)
					{
					$fp = fopen ("./CORSdebug_log.txt", "a");
					fwrite ($fp, "$NOW_TIME CORS-Debug 4: NO MATCH origin or method - |$CORS_allowed_origin($CORS_origin)|$CORS_allowed_methods($CORS_method)|$php_script\n");
					fclose($fp);
					}
				}
			# For OPTIONS preflight requests, exit without processing the script further
			if ( (strcasecmp($_SERVER['REQUEST_METHOD'], 'OPTIONS') == 0) and (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) )
				{
				flush();
				die();
				}
			}
		else
			{
			if ($CORS_debug > 0)
				{
				$fp = fopen ("./CORSdebug_log.txt", "a");
				fwrite ($fp, "$NOW_TIME CORS-Debug 5: NO AFFECT script - |$CORS_affected_scripts|$php_script|\n");
				fclose($fp);
				}
			}

		# add $Xframe_options if defined
		if ( ($Xframe_options == 'SAMEORIGIN') or ($Xframe_options == 'DENY') )
			{
			header('X-Frame-Options: ' . $Xframe_options);
			if ($CORS_debug > 0)
				{
				$fp = fopen ("./CORSdebug_log.txt", "a");
				fwrite ($fp, "$NOW_TIME CORS-Debug 6: X-frame-Options sent - |$Xframe_options|$php_script\n");
				fclose($fp);
				}
			}
		}
	}

?>