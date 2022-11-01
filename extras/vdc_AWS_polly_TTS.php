<?php
# vdc_AWS_polly_TTS.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to send a request to Amazon's AWS Polly Text-to-Speech
# service and receive back a PCM audio file(8k, 16bit, mono). Then add a WAV
# file header to it so it can be played natively in Asterisk.
#
# !!! THE AWS PHP SDK IS REQUIRED!!! You can download it here:
#		http://docs.aws.amazon.com/aws-sdk-php/v3/download/aws.zip
#	(the above zip file contains over 3,000 files, make sure you have space)
#   (unzip it inside a new 'polly' directory in the 'agc' web directory)
#
# !!! IMPORTANT !!! You must customize this script to your needs, and you must
#                   populate the credentials and region for your AWS account
#
# !!! IMPORTANT !!! This script assumes that the directory exists and is writable:
#                   agc/polly/generated
#
### Example URL to use in a SCRIPT tab Iframe:
# http://127.0.0.1/agc/vdc_AWS_polly_TTS.php?user=--A--user--B--&pass=--A--pass--B--&counter=--A--lead_id--B--&message=message+goes+here
#
# CHANGELOG:
# 220410-2127 - First build of script
#

$version = '2.14-1';
$build = '220410-2127';
$api_script = 'AWS_polly';

$startMS = microtime();

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$query_string = getenv("QUERY_STRING");

if (isset($_GET["user"]))			{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["pass"]))			{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))	{$pass=$_POST["pass"];}
if (isset($_GET["message"]))			{$message=$_GET["message"];}
	elseif (isset($_POST["message"]))	{$message=$_POST["message"];}
if (isset($_GET["counter"]))			{$counter=$_GET["counter"];}
	elseif (isset($_POST["counter"]))	{$counter=$_POST["counter"];}
if (isset($_GET["force"]))			{$force=$_GET["force"];}
	elseif (isset($_POST["force"]))	{$force=$_POST["force"];}
if (isset($_GET["voice"]))			{$voice=$_GET["voice"];}
	elseif (isset($_POST["voice"]))	{$voice=$_POST["voice"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,allow_sipsak_messages,enable_languages,language_method,meetme_enter_login_filename,meetme_enter_leave3way_filename,agent_debug_logging,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =						$row[0];
	$allow_sipsak_messages =			$row[1];
	$SSenable_languages =				$row[2];
	$SSlanguage_method =				$row[3];
	$meetme_enter_login_filename =		$row[4];
	$meetme_enter_leave3way_filename =	$row[5];
	$SSagent_debug_logging =			$row[6];
	$SSallow_web_debug =				$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

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

$message = preg_replace("/\'|\\\\|;/","",$message);
$counter = preg_replace("/[^0-9]/","",$counter);
$force = preg_replace("/[^0-9]/","",$force);
$voice = preg_replace("/[^-_0-9a-zA-Z]/","",$voice);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	}

# check user auth
$auth=0;
$auth_message = user_authorization($user,$pass,'',0,0,0,0,'AWS_polly');
if ($auth_message == 'GOOD')
	{$auth=1;}

if ( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) )
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth|$authlive|$auth_message|\n";
	exit;
	}


if (strlen($message) < 1)
	{
	echo "ERROR: invalid message: |$message|\n";
	}
else
	{
	if (strlen($message) < 10)
		{$message = '<speak>this is a test of the AWS Polly text to speech service. Number test, <say-as interpret-as="characters">1234567890</say-as>. Currency test, 987654321 dollars and 98 cents. Done.</speak>';}
	if (strlen($voice) < 1) {$voice = 'Matthew';}

	require_once './polly/aws-autoloader.php';

	$awsAccessKeyId = 'XXXXXXXXXXXXXXXXXXXX';
	$awsSecretKey   = 'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY';
	$credentials    = new \Aws\Credentials\Credentials($awsAccessKeyId, $awsSecretKey);
	$client         = new \Aws\Polly\PollyClient([
		'version'     => '2016-06-10',
		'credentials' => $credentials,
		'region'      => 'us-east-1',
	]);
	$result         = $client->synthesizeSpeech([
		'Engine'       => 'neural',
		'OutputFormat' => 'pcm',
		'SampleRate'   => '8000',
		'Text'         => $message,
		'TextType'     => 'ssml',
		'VoiceId'      => $voice,
	]);

	$resultData     = $result->get('AudioStream')->getContents();

#	$resultData = file_get_contents('polly.raw');
	//Output file
	$temp_filename = "./polly/generated/".$counter."_TTS.wav";
	$fp = fopen($temp_filename, 'wb');

	$pcm_size = strlen($resultData);
	$size = 36 + $pcm_size;
	$chunk_size = 16;
	$audio_format = 1;
	$channels = 1; //mono
	$sample_rate = 8000; //Hz	#From the AWS Polly documentation: Valid values for pcm are "8000" and "16000" The default value is "16000".
	$bits_per_sample = 16;
	$block_align = $channels * $bits_per_sample / 8;
	$byte_rate = $sample_rate * $channels * $bits_per_sample / 8;

	//RIFF chunk descriptor
	fwrite($fp, 'RIFF');
	fwrite($fp,pack('I', $size));
	fwrite($fp, 'WAVE');
	//fmt sub-chunk
	fwrite($fp, 'fmt ');
	fwrite($fp,pack('I', $chunk_size));
	fwrite($fp,pack('v', $audio_format));
	fwrite($fp,pack('v', $channels));
	fwrite($fp,pack('I', $sample_rate));
	fwrite($fp,pack('I', $byte_rate));
	fwrite($fp,pack('v', $block_align));
	fwrite($fp,pack('v', $bits_per_sample));
	//data sub-chunk
	fwrite($fp, 'data');
	fwrite($fp,pack('i', $pcm_size));
	fwrite($fp, $resultData);

	fclose($fp);


	$endMS = microtime();
	$startMSary = explode(" ",$startMS);
	$endMSary = explode(" ",$endMS);
	$runS = ($endMSary[0] - $startMSary[0]);
	$runM = ($endMSary[1] - $startMSary[1]);
	$TOTALrun = ($runS + $runM);

	echo "SUCCESS|".$counter."_TTS.wav|\n";

# Log request to vicidial_api_log
	$stmt="INSERT INTO vicidial_api_log set user='polly',agent_user='$user',function='aws_polly',value='TTS',result=\"GOOD\",result_reason='$counter ".$counter."_TTS.wav bytes: $size voice: $voice',source='AWS',data='$message',api_date=NOW(),api_script='$api_script',run_time='$TOTALrun',webserver='',api_url='';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ALaffected_rows = mysqli_affected_rows($link);
	if ($DB > 0) {echo "LOG: $ALaffected_rows|$stmt|\n";}



	# example export of mp3 audio data
#	header('Content-Transfer-Encoding: binary');
#	header('Content-Type: audio/wave, audio/wav, audio/x-wav, audio/x-pn-wav');
#	header('Content-Type: audio/mpeg, audio/x-mpeg, audio/x-mpeg-3, audio/mpeg3');
#	header('Content-length: ' . strlen($resultData));
#	header('Content-Disposition: attachment; filename="pollyTTS.wav"');
#	header('X-Pad: avoid browser bug');
#	header('Cache-Control: no-cache');
#	echo $resultData;

	# output as MP3 to listen
#	$size = strlen($resultData); // File size
#	$length = $size; // Content length
#	$start = 0; // Start byte
#	$end = $size - 1; // End byte
#	header('Content-Transfer-Encoding:chunked');
#	header("Content-Type: audio/mpeg");
#	header("Accept-Ranges: 0-$length");
#	header("Content-Range: bytes $start-$end/$size");
#	header("Content-Length: $length");
#	echo $resultData;

	# output as MP3 to download
#	header('Content-length: ' . strlen($resultData));
#	header('Content-Disposition: attachment; filename="polly-text-to-speech.mp3"');
#	header('X-Pad: avoid browser bug');
#	header('Cache-Control: no-cache');
#	echo $resultData;
	}

exit;

?>
