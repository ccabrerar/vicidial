<?php 
# xml_rpc_audio_server_vicidial.php
# 
# Used for integration with QueueMetrics of audio recordings
#
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
# Copyright (C) 2007  Lenz Emilitri <lenz.loway@gmail.com> LICENSE: ????
# 
# CHANGES
# 90525-1141 - First build
# 90529-2041 - Added Realtime monitoring
# 91012-0641 - Fixed to work with non-sequential time_ids in the queue_log
# 100127-0603 - Added OpenSuSE install instructions
# 100903-0041 - Changed lead_id max length to 10 digits
# 120621-0728 - Added commented-out debug logging
# 130610-1100 - Finalized changing of all ereg instances to preg
# 130615-2324 - Added filtering of input to prevent SQL injection attacks
# 130901-0829 - Changed to mysqli PHP functions
# 180529-1016 - Added debug logging
# 191016-0129 - Added alternate recording lookup method
#

// $Id: xmlrpc_audio_server.php,v 1.3 2007/11/12 17:53:09 lenz Exp $
//
// This is an example of a remote audio XML-RPC server for QueueMetrics.
// In QueueMetrics, calls to this client are activated by entering its URL in
// the 'default.audioRpcServer' property.
//
// In order to make it very easy to customize, you should change the contents of the
// functions 'find_file()' and 'listen_call()' only. You can populate some or all of the 
// return fields, according to the data you actually have. As the calling user is passed along,
// it's pretty easy to log who listened to which calls if you need to do it.
// 
//
// IMPORTANT:
// in order to run this, you must have the XML_RPC module that comes with the PEAR library
// correctly installed on your PHP server. See http://pear.php.net 
// on OpenSuSE: 
//     sudo zypper install php5-pear
//     sudo zypper install php5-pear-xml_rpc
//     sudo zypper install php5-pear-xml_parser
//     sudo zypper install php5-pear-xml_util
// on system with basic pear already installed:
//  "pear install XML_RPC-1.5.1"
//

$DBlogfile=0; # set to 1 for logfile writing

if ($DBlogfile > 0)
	{
	$logfile=fopen('qm_rpc_debug.txt', "a");
	fwrite($logfile, "Starting QM XML/RPC query: " . date("U") . ' - ' . date("Y-m-d H:i:s") . "\n");
	fclose($logfile);
	}

require_once 'XML/RPC/Server.php';


// the following variables hold the return status for your file.

// Listening to a stored call
$FILE_FOUND      = false;
$FILE_LISTEN_URL = "";
$FILE_LENGTH     = "";
$FILE_ENCODING   = "";
$FILE_DURATION   = ""; 

// Listening to an ongoing call
$CALL_FOUND        = false;
$CALL_LISTEN_URL   = "";
$CALL_POPUP_WIDTH  = "";
$CALL_POPUP_HEIGHT = "";


//
// This function must be implemented by the user.
//
function find_file( $ServerID, $AsteriskID, $QMUserID, $QMUserName ) 
	{
	global $FILE_FOUND;
	global $FILE_LISTEN_URL;
	global $FILE_LENGTH;
	global $FILE_ENCODING;
	global $FILE_DURATION;
	global $FIND_LAST;
	global $DBlogfile;

	if ($DBlogfile > 0)
		{
		$logfile=fopen('qm_rpc_debug.txt', "a");
		fwrite($logfile, date("U") . "|$ServerID|$AsteriskID|$QMUserID|$QMUserName|\n");
		fclose($logfile);
		}

	require("dbconnect_mysqli.php");
	require("functions.php");

	#############################################
	##### START QUEUEMETRICS LOGGING LOOKUP #####
	$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id FROM system_settings;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$qm_conf_ct = mysqli_num_rows($rslt);
	if ($qm_conf_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$enable_queuemetrics_logging =	$row[0];
		$queuemetrics_server_ip	=		$row[1];
		$queuemetrics_dbname =			$row[2];
		$queuemetrics_login	=			$row[3];
		$queuemetrics_pass =			$row[4];
		$queuemetrics_log_id =			$row[5];
		}
	##### END QUEUEMETRICS LOGGING LOOKUP #####
	###########################################
	if ($enable_queuemetrics_logging > 0)
		{
		#$linkB=mysql_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
		#mysql_select_db("$queuemetrics_dbname", $linkB);
		$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass", "$queuemetrics_dbname");

		$AsteriskID = preg_replace('/[^\.0-9a-zA-Z]/','',$AsteriskID);

		$stmt="SELECT time_id from queue_log where call_id='$AsteriskID' order by time_id limit 1;";
		$rslt=mysql_to_mysqli($stmt, $linkB);
		if ($DB) {echo "$stmt\n";}
		$QM_ql_ct = mysqli_num_rows($rslt);
		if ($QM_ql_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$time_id	= $row[0];
			$time_id_end = ($time_id + 14400);
			$lead_id_test = substr($AsteriskID, -10,1);
			if ($lead_id_test=='0')
				{$lead_id = substr($AsteriskID, -10);}
			else
				{$lead_id = substr($AsteriskID, -9);}
			$lead_id = ($lead_id + 0);
			}
		else
			{
			$stmt = "SELECT lead_id,UNIX_TIMESTAMP(call_date),uniqueid from vicidial_log_extended where caller_code='$AsteriskID' limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$VICI_ql_ct = mysqli_num_rows($rslt);
			if ($VICI_ql_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$lead_id =		$row[0];
				$time_id =		$row[1];
				$uniqueid =		$row[2];
				$time_id_end = ($time_id + 14400);
				}
			}
		if ( ($QM_ql_ct > 0) or ($VICI_ql_ct > 0) )
			{
			$stmt = "SELECT start_epoch,length_in_sec,location from recording_log where start_epoch>=$time_id and start_epoch<=$time_id_end and lead_id='$lead_id' order by recording_id limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$rl_ct = mysqli_num_rows($rslt);
			if ($rl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$start_epoch =		$row[0];
				$length_in_sec =	$row[1];
				$location =			$row[2];
				}
			else
				{
				$stmt = "SELECT lead_id,UNIX_TIMESTAMP(call_date),uniqueid from vicidial_log_extended where caller_code='$AsteriskID' limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$VICI_ql_ct = mysqli_num_rows($rslt);
				if ($VICI_ql_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$lead_id =		$row[0];
					$time_id =		$row[1];
					$uniqueid =		$row[2];

					$stmt = "SELECT start_epoch,length_in_sec,location from recording_log where start_epoch>=$time_id and start_epoch<=$time_id_end and lead_id='$lead_id' order by recording_id limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$rl_ct = mysqli_num_rows($rslt);
					if ($rl_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$start_epoch =		$row[0];
						$length_in_sec =	$row[1];
						$location =			$row[2];
						}
					}
				}
			if ($rl_ct > 0)
				{
				if (strlen($location)>2)
					{
					$extension = substr($location, strrpos($location, '.') + 1);
					if (preg_match("/mp3|gsm/",$extension))
						{$filesize = (2000 * $length_in_sec);}
					else
						{$filesize = (15660 * $length_in_sec);}
					$URLserver_ip = $location;
					$URLserver_ip = preg_replace('/http:\/\//i', '',$URLserver_ip);
					$URLserver_ip = preg_replace('/https:\/\//i', '',$URLserver_ip);
					$URLserver_ip = preg_replace('/\/.*/i', '',$URLserver_ip);
					$stmt="select count(*) from servers where server_ip='$URLserver_ip';";
					$rsltx=mysql_to_mysqli($stmt, $link);
					$rowx=mysqli_fetch_row($rsltx);
					
					if ($rowx[0] > 0)
						{
						$stmt="select recording_web_link,alt_server_ip from servers where server_ip='$URLserver_ip';";
						$rsltx=mysql_to_mysqli($stmt, $link);
						$rowx=mysqli_fetch_row($rsltx);
						
						if (preg_match("/ALT_IP/i",$rowx[0]))
							{
							$location = preg_replace("/$URLserver_ip/i", "$rowx[1]", $location);
							}
						}
					$FILE_FOUND      = true;
					$FILE_LISTEN_URL = "$location";
					$FILE_LENGTH     = "$filesize";
					$FILE_ENCODING   = "$extension";	
					$FILE_DURATION   = sec_convert($length_in_sec,'H');
					$FIND_LAST       = "FOUND";
					}
				else
					{
					$FILE_FOUND      = false;
					$FILE_LISTEN_URL = "";
					$FILE_LENGTH     = "0";
					$FILE_ENCODING   = "wav";	
					$FILE_DURATION   = "0:00";
					$FIND_LAST       = "No Location: $stmt";
					}
				}
			else
				{
				$FILE_FOUND      = false;
				$FILE_LISTEN_URL = "";
				$FILE_LENGTH     = "0";
				$FILE_ENCODING   = "wav";	
				$FILE_DURATION   = "0:00";
				$FIND_LAST       = "$stmt";
				}
			}
		else
			{
			$FILE_FOUND      = false;
			$FILE_LISTEN_URL = "";
			$FILE_LENGTH     = "0";
			$FILE_ENCODING   = "wav";	
			$FILE_DURATION   = "0:00";
			$FIND_LAST       = "$stmt";
			}
		mysqli_close($linkB);
		}

	if ($DBlogfile > 0)
		{
		$logfile=fopen('qm_rpc_debug.txt', "a");
		fwrite($logfile, date("U") . "|find_file|$FILE_FOUND|$FILE_LISTEN_URL|$FILE_LENGTH|$FILE_ENCODING|$FILE_DURATION|$FIND_LAST|\n");
		fclose($logfile);
		}

#	$FILE_FOUND      = true;
#	$FILE_LISTEN_URL = "http://listennow.server/$ServerID/$AsteriskID/$QMUserID/$QMUserName";
#	$FILE_LENGTH     = "125000";
#	$FILE_ENCODING   = "mp3";	
#	$FILE_DURATION   = "1:12"; 	
	}

function listen_call( $ServerID, $AsteriskID, $Agent, $QMUserID, $QMUserName ) 
	{
	global $CALL_FOUND;
	global $CALL_LISTEN_URL;
	global $CALL_POPUP_WIDTH;
	global $CALL_POPUP_HEIGHT;
	global $FIND_LAST;
	global $DBlogfile;

	require("dbconnect_mysqli.php");
	require("functions.php");

	$CALL_POPUP_WIDTH = "250";
	$CALL_POPUP_HEIGHT = "250";

	#############################################
	##### START QUEUEMETRICS LOGGING LOOKUP #####
	$stmt = "SELECT enable_queuemetrics_logging FROM system_settings;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$qm_conf_ct = mysqli_num_rows($rslt);
	if ($qm_conf_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$enable_queuemetrics_logging =	$row[0];
		}
	##### END QUEUEMETRICS LOGGING LOOKUP #####
	###########################################
	if ($enable_queuemetrics_logging > 0)
		{
		$AsteriskID = preg_replace('/[^0-9a-zA-Z]/','',$AsteriskID);

		$stmt = "SELECT user,server_ip,conf_exten,comments FROM vicidial_live_agents where callerid='$AsteriskID';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$vla_conf_ct = mysqli_num_rows($rslt);
		if ($vla_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$VLAuser =			$row[0];
			$VLAserver_ip =		$row[1];
			$VLAconf_exten =	$row[2];

			$stmt = "SELECT campaign_id,phone_number,call_type FROM vicidial_auto_calls where callerid='$AsteriskID';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$vla_conf_ct = mysqli_num_rows($rslt);
			if ($vla_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VACcampaign =	$row[0];
				$VACphone =		$row[1];
				$VACtype =		$row[2];

				$script_name = getenv("SCRIPT_NAME");
				$server_name = getenv("SERVER_NAME");
				$server_port = getenv("SERVER_PORT");
				if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
				  else {$HTTPprotocol = 'http://';}
				$admDIR = "$HTTPprotocol$server_name$script_name";
				$admDIR = preg_replace('/xml_rpc_audio_server_vicidial\.php/i', '',$admDIR);
				$monitor_script = 'QM_live_monitor.php';

				$CALL_FOUND      = true;
				$CALL_LISTEN_URL = "$admDIR$monitor_script?campaign=$VACcampaign&user=$VLAuser&server_ip=$VLAserver_ip&session=$VLAconf_exten&phone=$VACphone&type=$VACtype&call=$AsteriskID&QMuser=$QMUserID";
				}
			else
				{
				$CALL_FOUND      = false;
				$CALL_LISTEN_URL = "";
				}
			}
		else
			{
			$CALL_FOUND      = false;
			$CALL_LISTEN_URL = "";
			}
		}

	if ($DBlogfile > 0)
		{
		$logfile=fopen('qm_rpc_debug.txt', "a");
		fwrite($logfile, date("U") . "|listen_call|$CALL_FOUND|$CALL_LISTEN_URL|\n");
		fclose($logfile);
		}

#	$CALL_FOUND      = true;
#	$CALL_LISTEN_URL = "http://listennow.server/$ServerID/$AsteriskID/$QMUserID/$QMUserName/$Agent";
	}


// 
// This function does the XML-RPC call handling
// All the PHP's XML-RPC details are handled here.
//
function xmlrpc_find_file( $params ) {
	global $FILE_FOUND;
	global $FILE_LISTEN_URL;
	global $FILE_LENGTH;
	global $FILE_ENCODING;	
	global $FILE_DURATION;
	global $FIND_LAST;
	global $DBlogfile;
	
	$p0 = $params->getParam(0)->scalarval(); // server ID
	$p1 = $params->getParam(1)->scalarval(); // Asterisk call ID
	$p2 = $params->getParam(2)->scalarval(); // QM User ID
	$p3 = $params->getParam(3)->scalarval(); // Qm user name

	if ($DBlogfile > 0)
		{
		$logfile=fopen('qm_rpc_debug.txt', "a");
		fwrite($logfile, date("U") . "|TRIGGER_find_file|$p0|$p1|$p2|$p3|\n");
		fclose($logfile);
		}

	find_file( $p0, $p1, $p2, $p3 ); 		
	
	$response = new XML_RPC_Value(array(
        new XML_RPC_Value( $FILE_FOUND, 'boolean' ),
        new XML_RPC_Value( $FILE_LISTEN_URL ),
        new XML_RPC_Value( $FILE_LENGTH ),
        new XML_RPC_Value( $FILE_ENCODING ),
        new XML_RPC_Value( $FILE_DURATION ),        
    ), "array");
	
	return new XML_RPC_Response($response);
}



function xmlrpc_listen_call( $params ) {
	global $CALL_FOUND;
	global $CALL_LISTEN_URL;
	global $CALL_POPUP_WIDTH;
	global $CALL_POPUP_HEIGHT;
	global $FIND_LAST;
	global $DBlogfile;

	$p0 = $params->getParam(0)->scalarval(); // server ID
	$p1 = $params->getParam(1)->scalarval(); // asterisk call ID
	$p2 = $params->getParam(2)->scalarval(); // agent code
	$p3 = $params->getParam(3)->scalarval(); // QM user ID
	$p4 = $params->getParam(3)->scalarval(); // QM user name

	if ($DBlogfile > 0)
		{
		$logfile=fopen('qm_rpc_debug.txt', "a");
		fwrite($logfile, date("U") . "|TRIGGER_listen_call|$p0|$p1|$p2|$p3|$p4|\n");
		fclose($logfile);
		}

	listen_call( $p0, $p1, $p2, $p3, $p4 ); 		
	
	$response = new XML_RPC_Value(array(
        new XML_RPC_Value( $CALL_FOUND, 'boolean' ),
        new XML_RPC_Value( $CALL_LISTEN_URL ),
        new XML_RPC_Value( $CALL_POPUP_WIDTH, 'int' ),
        new XML_RPC_Value( $CALL_POPUP_HEIGHT, 'int' ),             
    ), "array");
	
	return new XML_RPC_Response($response);
}



if ($DBlogfile > 0)
	{
	if (getenv('REQUEST_METHOD') == 'POST') 
		{
		$client_data = file_get_contents("php://input");
		}
	$logfile=fopen('qm_rpc_debug.txt', "a");
	fwrite($logfile, date("U") . "|START_request|\n$client_data\nEND_request|\n");
	fclose($logfile);
	}

//
// Instantiates a very simple XML-RPC audio server for QueueMetrics
//
$server = new XML_RPC_Server(
    array(
        'QMAudio.findStoredFile' => array(
            'function' => 'xmlrpc_find_file'
        ),    
        'QMAudio.listenOngoingCall' => array(
            'function' => 'xmlrpc_listen_call'
        ),
        'QMAudio.listenOngoingCallOutbound' => array(
            'function' => 'xmlrpc_listen_call'
        ),
        
    ),
    1  // serviceNow
);


// $Log: xmlrpc_audio_server.php,v $
// Revision 1.3  2007/11/12 17:53:09  lenz
// Bug #182: queue direction for inbound/outbound call listen.
//
// Revision 1.2  2007/05/12 20:45:06  lenz
// Merge IMM4
//
// Revision 1.1.2.1  2007/04/03 17:43:06  lenz
// Prima versione.
//
//
//
//

?>
