#!/usr/bin/perl
#
# AST_DB_check_tables.pl version 2.12
#
# DESCRIPTION:
# OPTIONAL!!!
# - grabs table list in database and runs a 'chech table' on all non-archive
#    tables, sends optional email report
#
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 151111-0605 - first build
#

$QUICK=0;

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
		print "  [--quick] = run test in QUICK mode, may not catch all issues\n";
		print "  [--email-list=test@test.com:test2@test.com] = send email results to these addresses\n";
		print "  [--email-sender=vicidial@localhost] = sender for the email results\n";
		print "  [--test] = test\n";
		print "  [--quiet] = quiet\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n\n";
		exit;
		}
	else
		{
		if ($args =~ /--quiet/i)
			{
			$Q=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		if ($args =~ /--quick/i)
			{
			$QUICK=1;
			if ($DB > 0) {print "\n----- CHECK TABLE QUICK MODE: $QUICK -----\n\n";}
			}
		if ($args =~ /--email-list=/i)
			{
			@data_in = split(/--email-list=/,$args);
			$email_list = $data_in[1];
			$email_list =~ s/ .*//gi;
			$email_list =~ s/:/,/gi;
			if ($DB > 0) {print "\n----- EMAIL NOTIFICATION: $email_list -----\n\n";}
			}
		else
			{$email_list = '';}

		if ($args =~ /--email-sender=/i)
			{
			@data_in = split(/--email-sender=/,$args);
			$email_sender = $data_in[1];
			$email_sender =~ s/ .*//gi;
			$email_sender =~ s/:/,/gi;
			if ($DB > 0) {print "\n----- EMAIL NOTIFICATION SENDER: $email_sender -----\n\n";}
			}
		else
			{$email_sender = 'vicidial@localhost';}
		}
	}
else
	{
	# print "no command line options set, exiting...\n";
	# exit;
	}
### end parsing run-time options ###


# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

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
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}


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
$shipdate = "$year-$mon-$mday $hour:$min:$sec";

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);


$ARCHIVEcount=0;
$TABLEcount=0;
$CLOSEDcount=0;
$CORRUPTcount=0;
$MEMORYcount=0;
$MT[0]='';

### find stats on dnc lists
$stmtA = "SHOW TABLES;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsT=$sthA->rows;
$k=0;
while ($sthArowsT > $k)
	{
	@aryA = $sthA->fetchrow_array;
	if ($aryA[0] =~ /archive/i) 
		{$ARCHIVEcount++;}
	else
		{
		$check_table[$TABLEcount] = $aryA[0];
		$TABLEcount++;
		}
	$k++;
	}
$sthA->finish();

$check_output='';

if($DB){print "Total database tables: |$k|\n";}
if($DB){print "   Non-archive tables: |$TABLEcount|\n";}
if($DB){print "       Archive tables: |$ARCHIVEcount|\n";}
if($DB){print "\n";}
if($DB){print "Starting table checks:\n";}
if($DB){print "\n";}

$check_output .= "Total database tables: |$k|\n";
$check_output .= "   Non-archive tables: |$TABLEcount|\n";
$check_output .= "       Archive tables: |$ARCHIVEcount|\n";
$check_output .= "\n";
$check_output .= "Starting table checks:\n";
$check_output .= "\n";

$QUICKsql='';
if ($QUICK > 0) 
	{$QUICKsql = ' QUICK';}

$k=0;
while ($TABLEcount > $k) 
	{
	$secY = time();
	$stmtA = "check table $check_table[$k] $QUICKsql;";
	if($DBX){print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	@aryA = $sthA->fetchrow_array;
	$sthA->finish();
	$secZ = time();
	$check_time = ($secZ - $secY);
	if ($DB) {print "($check_time seconds) |",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","\n";}
	if ($aryA[3] =~ /corrupt/) 
		{
		$corrupt_table[$CORRUPTcount] = $aryA[0];
		$CORRUPTcount++;
		}
	if ($aryA[3] =~ /closed the table properly/) 
		{
		$closed_table[$CLOSEDcount] = $aryA[0];
		$CLOSEDcount++;
		}
	if ($aryA[3] =~ /storage engine/) 
		{
		$MEMORYcount++;
		}

	$check_output .= "$check_table[$k]: ($check_time seconds) |",$aryA[0],"|",$aryA[1],"|",$aryA[2],"|",$aryA[3],"|","<br>\n";
	$k++;
	}

if($DB){print "Check table results: |$k|\n";}
if($DB){print "   Corrupted tables: |$CORRUPTcount|\n";}
if($DB){print "  Non-closed tables: |$CLOSEDcount|\n";}
if($DB){print "      Memory tables: |$MEMORYcount|\n";}

$check_output .= "Check table results: |$k|\n";
$check_output .= "   Corrupted tables: |$CORRUPTcount|\n";
$check_output .= "  Non-closed tables: |$CLOSEDcount|\n";
$check_output .= "      Memory tables: |$MEMORYcount|\n";


$secZ = time();
$total_time = ($secZ - $secX);

if ( (length($check_output)>5) && (length($email_list) > 3) )
	{
	if ($DB) {print "Sending email: $email_list\n";}

	use MIME::QuotedPrint;
	use MIME::Base64;
	use Mail::Sendmail;

	$mailsubject = "Database tables check $shipdate";

	%mail = ( To      => "$email_list",
						From    => "$email_sender",
						Subject => "$mailsubject",
				   );
	$boundary = "====" . time() . "====";
	$mail{'content-type'} = "multipart/mixed; boundary=\"$boundary\"";

	$message = encode_qp( "$check_output\n\nTOTAL TIME: ($total_time seconds)\n" );

	$boundary = '--'.$boundary;
	$mail{body} .= "$boundary\n";
	$mail{body} .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
	$mail{body} .= "Content-Transfer-Encoding: quoted-printable\n\n";
	$mail{body} .= "$message\n";
	$mail{body} .= "$boundary\n";
	$mail{body} .= "--\n";

	sendmail(%mail) or die $mail::Sendmail::error;
	if ($DB) {print "ok. log says:\n", $mail::sendmail::log;}  ### print mail log for status
	}


if ($DB) {print "FINISHED... EXITING ($total_time seconds)\n";}
exit;
