#!/usr/bin/perl
#
# AST_timecheck.pl             version: 2.10
#
# This script is designed to send an email when an asterisk server is out of sync
#
# /usr/share/astguiclient/AST_timecheck.pl --quiet --seconds=5 --email-list=test@gmail.com --email-sender=test@test.com
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130503-1515 - First version
# 140509-2214 - Added frozen_server_call_clear system setting
# 141202-1108 - Do not send warning for server set to Active=N
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;

$secX = time();
$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$filedate = "$year$mon$mday";
$ABIfiledate = "$mon-$mday-$year$us$hour$min$sec";
$shipdate = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$datestamp = "$year/$mon/$mday $hour:$min";


### begin parsing run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [--seconds=XXX] = number of seconds considered out of sync. Default is 10\n";
		print "  [--conffile=/path/from/root] = define configuration file path from root at runtime\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--quiet] = quiet\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
		print "\n";


		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG MODE -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG MODE -----\n\n";
			}
		if ($args =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--seconds=/i)
			{
			@data_in = split(/--seconds=/,$args);
			$seconds = $data_in[1];
			$seconds =~ s/ .*//gi;
			if (!$Q) {print "\n----- SECONDS: $seconds -----\n\n";}
			}
		else
			{$seconds = 10;}

		if ($args =~ /--email-list=/i)
			{
			@data_in = split(/--email-list=/,$args);
			$email_list = $data_in[1];
			$email_list =~ s/ .*//gi;
			$email_list =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";}
			}
		else
			{$email_list = '';}

		if ($args =~ /--email-sender=/i)
			{
			@data_in = split(/--email-sender=/,$args);
			$email_sender = $data_in[1];
			$email_sender =~ s/ .*//gi;
			$email_sender =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";}
			}
		else
			{$email_sender = 'vicidial@localhost';}

		if ($args =~ /--conffile=/i) # CLI defined conffile path
			{
			@CLIconffileARY = split(/--conffile=/,$args);
			@CLIconffileARX = split(/ /,$CLIconffileARY[1]);
			if (length($CLIconffileARX[0])>2)
				{
				$PATHconf = $CLIconffileARX[0];
				$PATHconf =~ s/\/$| |\r|\n|\t//gi;
				$CLIconffile=1;
				if (!$Q) {print "  CLI defined conffile path:  $PATHconf\n";}
				}
			}
		}
	}
else
	{
	print "no command line options set, nothing is going to happen.\n";
	}
### end parsing run-time options ###

# default path to astguiclient configuration file:
if (length($PATHconf) < 5)
	{$PATHconf =		'/etc/astguiclient.conf';}

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHhome/) && ($CLIhome < 1) )
		{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^PATHagi/) && ($CLIagi < 1) )
		{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ( ($line =~ /^PATHweb/) && ($CLIweb < 1) )
		{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ( ($line =~ /^PATHsounds/) && ($CLIsounds < 1) )
		{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ( ($line =~ /^PATHmonitor/) && ($CLImonitor < 1) )
		{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ( ($line =~ /^VARserver_ip/) && ($CLIserver_ip < 1) )
		{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_server/) && ($CLIDB_server < 1) )
		{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_database/) && ($CLIDB_database < 1) )
		{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_user/) && ($CLIDB_user < 1) )
		{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_pass/) && ($CLIDB_pass < 1) )
		{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_port/) && ($CLIDB_port < 1) )
		{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP



$web_u_time = time();
$servers=0;

if ($DBX) {print "Start time: $timestamp($web_u_time)   TIME SYNC SECONDS: $seconds\n";}

use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$SSfrozen_server_call_clear=0;
### Grab system settings values from the database
$stmtA = "SELECT frozen_server_call_clear FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSfrozen_server_call_clear =		$aryA[0];
	}
$sthA->finish();

$stmtA = "SELECT su.server_ip,last_update,UNIX_TIMESTAMP(last_update),server_description,UNIX_TIMESTAMP(db_time),server_id,active FROM server_updater su,servers s where s.server_ip=su.server_ip;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsSERVERS=$sthA->rows;
	if ($DBX) {print "   $sthArows|$stmtA|\n";}
$i=0;
while ($sthArowsSERVERS > $i)
	{
	@aryA = $sthA->fetchrow_array;
	$server_ip =	$aryA[0];
	$s_time =		$aryA[1];
	$u_time =		($aryA[2] + $seconds);
	$reset_time =	($aryA[2] + 90);
	$server_name =	$aryA[3];
	$Sdb_time =		($aryA[4] + 10);
	$server_id =	$aryA[5];
	$active =		$aryA[6];

	if ($DBX) {print "Server time: $i - $server_ip($server_id - $server_name)   Last time: $s_time($u_time|$aryA[2])\n";}

	if ( ($web_u_time > $u_time) and ($active =~ /Y/) )
		{
		if ($DBX) {print "Time sync issue - SERVER: $server_ip($server_name)\n   LAST TIME: $s_time($u_time)\n   TIME NOW:  $timestamp($web_u_time)\n";}
		$Ealert .= "SERVER: $server_ip($server_name)\n   LAST TIME: $s_time($u_time)\n   TIME NOW:  $timestamp($web_u_time)\n\n";
		$servers++;

		if ($DBX) {print "     DEBUG: ($web_u_time > $reset_time)|($web_u_time > $Sdb_time)|     |$SSfrozen_server_call_clear|\n";}

		if ( ($web_u_time > $reset_time) && ($web_u_time > $Sdb_time) && ($SSfrozen_server_call_clear > 0) )
			{
			$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
			 or die "Couldn't connect to database: " . DBI->errstr;

			$servercalls_count=0;
			### Grab number of calls on frozen server
			$stmtB = "SELECT count(*) from vicidial_auto_calls where server_ip='$server_ip';";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				@aryB = $sthB->fetchrow_array;
				$servercalls_count =		$aryB[0];
				}
			$sthB->finish();

			if ($DBX) {print "     ServerCalls: $servercalls_count\n";}

			if ($servercalls_count > 0)
				{
				$stmt="DELETE FROM vicidial_auto_calls where server_ip='$server_ip';";
				$FCaffected_rows = $dbhB->do($stmt);
					if($DB){print STDERR "\n$FCaffected_rows|$stmt|\n";}

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|";
				$SQL_log =~ s/;|\\|\'|\"//gi;
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='$server_ip', event_section='SERVERS', event_type='CLEAR', record_id='$server_id', event_code='FROZEN SERVER CALL CLEAR', event_sql=\"$SQL_log\", event_notes='$servercalls_count|$FCaffected_rows|$s_time($u_time|$aryA[2])';";
				$BLaffected_rows = $dbhB->do($stmt);
					if($DB){print STDERR "\n$BLaffected_rows|$stmt|\n";}

				$Ealert .= "     FROZEN CALLS CLEARED: $servercalls_count|$FCaffected_rows\n\n";
				}
			}
		}
	$i++;
	}
$sthA->finish();



###### EMAIL SECTION

if ( (length($Ealert)>5) && (length($email_list) > 3) )
	{
	if (!$Q) {print "Sending email: $email_list\n ";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "Time sync issue: $VARDB_database   $timestamp";

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$mailsubject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "Time sync issue: $VARDB_database\n Servers: $servers\n\n$Ealert\n\n" );

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	if (!$Q) 
		{
		print "ok. log says:\n", $mail::sendmail::log;  ### print mail log for status
		}
	}

exit;
