#!/usr/bin/perl
#
# AST_send_URLbasic.pl   version 2.12
# 
# DESCRIPTION:
# This script is spawned by the agent interface to run for URLs in the background
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 150711-1227 - First Build
#

$|++;
use Getopt::Long;

### Initialize date/time vars ###
my $secX = time(); #Start time

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$now_date = "$year-$mon-$mday $hour:$min:$sec";

### Initialize run-time variables ###
my ($CLOhelp, $SYSLOG,$DB, $DBX);
my ($url_id, $lead_id, $phone_number, $call_type, $user, $uniqueid, $alt_dial, $call_id, $function);
my $FULL_LOG = 1;
$url_function = 'other';
$US='_';

### begin parsing run-time options ###
if (scalar @ARGV) {
	GetOptions('help!' => \$CLOhelp,
		'SYSLOG!' => \$SYSLOG,
		'url_id=s' => \$url_id,
		'debug!' => \$DB,
		'debugX!' => \$DBX,
		'fulllog!' => \$FULL_LOG,
		'compat_url=s' => \$compat_url);

	$DB = 1 if ($DBX);
	if ($DB) 
		{
		print "\n----- DEBUGGING -----\n\n";
		print "\n----- SUPER-DUPER DEBUGGING -----\n\n" if ($DBX);
		print "  SYSLOG:                $SYSLOG\n" if ($SYSLOG);
		print "  url_id:                $url_id\n" if ($url_id);
		print "  compat_url:            $compat_url\n" if ($compat_url);
		print "\n";
		}
	if ($CLOhelp) 
		{
		print "allowed run time options:\n";
		print "  [--help] = this help screen\n";
		print "  [--SYSLOG] = whether to log actions or not\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = extra debug\n";
		print "  [--compat_url] = full compatible URL to send(not implemented)\n";
		print "required flags:\n";
		print "  [--url_id] = URL ID to run\n";
		print "\n";
		print "You may prefix an option with 'no' to disable it.\n";
		print " ie. --noSYSLOG or --noFULLLOG\n";

		exit 0;
		}
	}

# default path to astguiclient configuration file:
$PATHconf =	'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
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

if (!$VARDB_port) {$VARDB_port='3306';}


if (length($url_id) > 0) 
	{
	### find wget binary
	$wgetbin = '';
	if ( -e ('/bin/wget')) {$wgetbin = '/bin/wget';}
	else
		{
		if ( -e ('/usr/bin/wget')) {$wgetbin = '/usr/bin/wget';}
		else
			{
			if ( -e ('/usr/local/bin/wget')) {$wgetbin = '/usr/local/bin/wget';}
			else
				{
				print "Can't find wget binary! Exiting...\n";
				exit;
				}
			}
		}

	use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
	use DBI;	  

	$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	 or die "Couldn't connect to database: " . DBI->errstr;

	### Grab Server values from the database
	$stmtA = "SELECT vd_server_logs,local_gmt,ext_context FROM servers where server_ip = '$VARserver_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$DBvd_server_logs =		$aryA[0];
		$DBSERVER_GMT =			$aryA[1];
		$ext_context =			$aryA[2];
		if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
		else {$SYSLOG = '0';}
		if (length($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
		}
	$sthA->finish();

	$stmtG = "SELECT url FROM vicidial_url_log where url_log_id='$url_id';";
	$sthA = $dbhA->prepare($stmtG) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtG ", $dbhA->errstr;
	$url_ct=$sthA->rows;
	if ($url_ct > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$url =	$aryA[0];
		}
	$sthA->finish();

	if (length($url) > 5) 
		{
		$url =~ s/ /+/gi;
		$url =~ s/&/\\&/gi;
		$url =~ s/'/\\'/gi;
		$url =~ s/"/\\"/gi;

		my $secW = time();

		`$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url `;

		$event_string="$function|$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url|";
		&event_logger;

		my $secY = time();
		my $response_sec = ($secY - $secW);

		open(Wdoc, "/tmp/ASUBtmpD$US$url_id$US$secX") || die "can't open /tmp/ASUBtmpD$US$url_id$US$secX: $!\n";
		@Wdoc = <Wdoc>;
		close(Wdoc);
		$i=0;
		$Wdocline_cat='';
		foreach(@Wdoc)
			{
			$Wdocline = $Wdoc[$i];
			$Wdocline =~ s/\n|\r/!/gi;
			$Wdocline =~ s/  |\t|\'|\`//gi;
			$Wdocline_cat .= "$Wdocline";
			$i++;
			}
		if (length($Wdocline_cat)<1) 
			{$Wdocline_cat='<RESPONSE EMPTY>';}

		open(Wfile, "/tmp/ASUBtmpF$US$url_id$US$secX") || die "can't open /tmp/ASUBtmpF$US$url_id$US$secX: $!\n";
		@Wfile = <Wfile>;
		close(Wfile);
		$i=0;
		$Wfileline_cat='';
		foreach(@Wfile)
			{
			$Wfileline = $Wfile[$i];
			$Wfileline =~ s/\n|\r/!/gi;
			$Wfileline =~ s/  |\t|\'|\`//gi;
			$Wfileline_cat .= "$Wfileline";
			$i++;
			}
		if (length($Wfileline_cat)<1) 
			{$Wfileline_cat='<HEADER EMPTY>';}


		### update url log entry
		$stmtA = "UPDATE vicidial_url_log SET url_response='$Wdocline_cat|$Wfileline_cat',response_sec='$response_sec' where url_log_id='$url_id';";
		$affected_rows = $dbhA->do($stmtA);
		if ($DB) {print "$affected_rows|$stmtA\n";}
		}
	else
		{
		if ($DB) {print "ERROR: no valid URL found: $url_id|$url\n";}
		}
	}


my $secZ = time();
my $script_time = ($secZ - $secX);
if ($DB) {print "DONE execute time: $script_time seconds\n";}

exit 0;
# Program ends.



### Start of subroutines

sub event_logger
	{
	my ($tms) = @_;
	my($sec,$min,$hour,$mday,$mon,$year) = getTime($tms);
	$now_date = $year.'-'.$mon.'-'.$mday.' '.$hour.':'.$min.':'.$sec;
	$log_date = $year . '-' . $mon . '-' . $mday;
	if (!$ASULOGfile) {$ASULOGfile = "$PATHlogs/sendurlbasic.$log_date";}

	if ($DB) {print "$now_date|$event_string|\n";}
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$ASULOGfile")
				|| die "Can't open $VDRLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}


# getTime usage:
#   getTime($SecondsSinceEpoch);
# Options:
#   $SecondsSinceEpoch : Request time in seconds, defaults to current date/time.
# Returns:
#   ($sec, $min, $hour. $day, $mon, $year)
sub getTime 
	{
	my ($tms) = @_;
	$tms = time unless ($tms);
	my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime($tms);
	$year += 1900;
	$mon++;
	$mon = "0" . $mon if ($mon < 10);
	$mday = "0" . $mday if ($mday < 10);
	$min = "0" . $min if ($min < 10);
	$sec = "0" . $sec if ($sec < 10);
	return ($sec,$min,$hour,$mday,$mon,$year);
	}

### End of subs
