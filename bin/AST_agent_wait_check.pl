#!/usr/bin/perl
#
# AST_agent_wait_check.pl             version: 2.14
#
# This script is designed to send an email when agents are waiting more than X seconds
#
# /usr/share/astguiclient/AST_agent_wait_check.pl --quiet --seconds=60 --email-list=test@gmail.com --email-sender=test@test.com --campaign=TESTCAMP
# /usr/share/astguiclient/AST_agent_wait_check.pl --container=XXX
#
# You can put this entry into the crontab to have it run automatically every minute during production hours
# * 9,10,11,12,13,14,15,16,17,18,19,20,21 * * * /usr/share/astguiclient/AST_agent_wait_check.pl --container=XXX
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 170731-0821 - First version
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$test=0;

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
		print "  [--seconds=X] = maximum number of wait seconds for agent. Default is 60\n";
		print "  [--campaign=XXX] = REQUIRED! Campaign that sales will be pulled from. For more than 1, separate with dash\n";
		print "  [--conffile=/path/from/root] = define configuration file path from root at runtime\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--container=XXX] = the ID of a settings container to run from the system, overwrites other options\n";
		print "  [--quiet] = quiet\n";
		print "  [--test] = test only, do not email\n";
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
		if ($args =~ /--test/i)
			{
			$test=1;
			}
		if ($args =~ /--seconds=/i)
			{
			@data_in = split(/--seconds=/,$args);
			$seconds = $data_in[1];
			$seconds =~ s/ .*//gi;
			if (!$Q) {print "\n----- SECONDS: $seconds -----\n\n";}
			}
		else
			{$seconds = 60;}

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
		if ($args =~ /--campaign=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign=/,$args);
			$campaign = $data_in[1];
			$campaign =~ s/ .*$//gi;
			if ($campaign =~ /---ALL---/gi) 
				{
				$campaignSQL='';
				}
			else
				{
				$campaignSQL = $campaign;
				if ($campaignSQL =~ /-/) 
					{
					$campaignSQL =~ s/-/','/gi;
					}
				$campaignSQL = "and campaign_id IN('$campaignSQL')";
				}
			}
		if ($args =~ /--container=/i)
			{
			@data_in = split(/--container=/,$args);
			$container = $data_in[1];
			$container =~ s/ .*$//gi;
			if ($DB > 0)
				{print "\n----- SETTINGS CONTAINER: $container -----\n\n";}
			}
		}
	}
else
	{
	print "ERROR: No command line options set, nothing is going to happen.\n";
	exit;
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


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

if (length($container) > 1) 
	{
	### Grab container content from the database
	$container_entry='';
	$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id = '$container';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$container_entry = $aryA[0];
		}
	$sthA->finish();
	if (length($container_entry) < 3) 
		{
		if ($DB > 0) {print "NOTICE: Invalid Container: |$container|$container_entry|\n";}
		}
	else
		{
		$container_entry =~ s/\n|\r|\t/ /gi;

		if ($container_entry =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG MODE -----\n\n";
			}
		if ($container_entry =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG MODE -----\n\n";
			}
		if ($container_entry =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--test/i)
			{
			$test=1;
			}
		if ($container_entry =~ /--seconds=/i)
			{
			@data_in = split(/--seconds=/,$container_entry);
			$seconds = $data_in[1];
			$seconds =~ s/ .*//gi;
			if (!$Q) {print "\n----- SECONDS: $seconds -----\n\n";}
			}
		else
			{$seconds = 60;}

		if ($container_entry =~ /--email-list=/i)
			{
			@data_in = split(/--email-list=/,$container_entry);
			$email_list = $data_in[1];
			$email_list =~ s/ .*//gi;
			$email_list =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";}
			}
		else
			{$email_list = '';}

		if ($container_entry =~ /--email-sender=/i)
			{
			@data_in = split(/--email-sender=/,$container_entry);
			$email_sender = $data_in[1];
			$email_sender =~ s/ .*//gi;
			$email_sender =~ s/:/,/gi;
			if (!$Q) {print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";}
			}
		else
			{$email_sender = 'vicidial@localhost';}

		if ($container_entry =~ /--conffile=/i) # CLI defined conffile path
			{
			@CLIconffileARY = split(/--conffile=/,$container_entry);
			@CLIconffileARX = split(/ /,$CLIconffileARY[1]);
			if (length($CLIconffileARX[0])>2)
				{
				$PATHconf = $CLIconffileARX[0];
				$PATHconf =~ s/\/$| |\r|\n|\t//gi;
				$CLIconffile=1;
				if (!$Q) {print "  CLI defined conffile path:  $PATHconf\n";}
				}
			}
		if ($container_entry =~ /--campaign=/i)
			{
			#	print "\n|$container_entry|\n\n";
			@data_in = split(/--campaign=/,$container_entry);
			$campaign = $data_in[1];
			$campaign =~ s/ .*$//gi;
			if ($campaign =~ /---ALL---/gi) 
				{
				$campaignSQL='';
				}
			else
				{
				$campaignSQL = $campaign;
				if ($campaignSQL =~ /-/) 
					{
					$campaignSQL =~ s/-/','/gi;
					}
				$campaignSQL = "and campaign_id IN('$campaignSQL')";
				}
			}
		}
	}

$web_u_time = time();
$max_wait_epoch = ($web_u_time - $seconds);

if ($DBX) {print "Start time: $timestamp($web_u_time)   AGENT WAIT SECONDS: $seconds($max_wait_epoch)\n";}

if (length($campaign) < 2)
	{
	if (!$Q) {print "ERROR: no campaign defined: |$campaign|$campaignSQL|";}
	}












$stmtA = "SELECT user,status,campaign_id,server_ip,UNIX_TIMESTAMP(last_state_change) FROM vicidial_live_agents where status IN('CLOSER','READY') and UNIX_TIMESTAMP(last_state_change) < '$max_wait_epoch' $campaignSQL order by last_state_change;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsSERVERS=$sthA->rows;
	if ($DBX) {print "   $sthArows|$stmtA|\n";}
$i=0;
while ($sthArowsSERVERS > $i)
	{
	@aryA = $sthA->fetchrow_array;
	$Auser =		$aryA[0];
	$Astatus =		$aryA[1];
	$Acampaign =	$aryA[2];
	$Aserver =		$aryA[3];
	$Awaitsec =		($web_u_time - $aryA[4]);

	$i++;
	if ($DBX) {print "Agent $i: - $Auser ($Astatus - $Acampaign - $Aserver)   Waiting: $Awaitsec sec\n";}
	$Ealert .= "Agent $i: - $Auser ($Astatus - $Acampaign - $Aserver)   Waiting: $Awaitsec sec\n";
	}
$sthA->finish();



###### EMAIL SECTION

if ( (length($Ealert)>5) && (length($email_list) > 5) && ($test < 1) )
	{
	if (!$Q) {print "Sending email: $email_list\n ";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "Agents Waiting Notification: $i   $timestamp";

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$mailsubject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "Agents Waiting Notification: $i\n\n$Ealert\n\n" );

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
