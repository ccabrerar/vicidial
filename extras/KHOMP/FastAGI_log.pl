#!/usr/bin/perl
#
# FastAGI_log.pl version 2.14 (KHOMP VERSION as of svn/trunk rev 3553)
# 
# Experimental Deamon using perl Net::Server that runs as FastAGI to reduce load
# replaces the following AGI scripts:
# - call_log.agi
# - call_logCID.agi
# - VD_hangup.agi
#
# This script needs to be running all of the time for AGI requests to work
# 
# You need to put lines similar to those below in your extensions.conf file:
# 
# ;outbound dialing:
# exten => _91NXXNXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log) 
#
# ;inbound calls:
# exten => 101,1,AGI(agi://127.0.0.1:4577/call_log)
#   or
# exten => 101,1,AGI(agi://127.0.0.1:4577/call_log--fullCID--${EXTEN}-----${CALLERID}-----${CALLERIDNUM}-----${CALLERIDNAME})
#
# 
# ;all hangups:
# exten => h,1,DeadAGI(agi://127.0.0.1:4577/call_log--HVcauses--PRI-----NODEBUG-----${HANGUPCAUSE}-----${DIALSTATUS}-----${DIALEDTIME}-----${ANSWEREDTIME})
# 
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG:
# 61010-1007 - First test build
# 70116-1619 - Added Auto Alt Dial code
# 70215-1258 - Added queue_log entry when deleting vac record
# 70808-1425 - Moved VD_hangup section to the call_log end stage to improve efficiency
# 71030-2039 - Added priority to hopper insertions
# 80224-0040 - Fixed bugs in vicidial_log updates
# 80430-0907 - Added term_reason to vicidial_log and vicidial_closer_log
# 80507-1138 - Fixed vicidial_closer_log CALLER hangups
# 80510-0414 - Fixed crossover logging bugs
# 80510-2058 - Fixed status override bug
# 80525-1040 - Added IVR vac status compatibility for inbound calls
# 80830-0035 - Added auto alt dialing for EXTERNAL leads for each lead
# 80909-0843 - Added support for campaign-specific DNC lists
# 81021-0306 - Added Local channel logging support and while-to-if changes
# 81026-1247 - Changed to allow for better remote agent calling
# 81029-0522 - Changed to disallow queue_log logging of IVR calls
# 90604-1044 - Fixed formatting, added DAHDI support, added carrier hangup code logging
# 90608-0316 - Changed hangup code dispos B and DC to AB and ADC to separate Agent dispos from Auto
# 90630-2253 - Added Sangoma CDP pre-Answer call processing
# 90814-0810 - Added extra logging for vicidial_log in some cases
# 90815-0750 - Fixed extra vicidial_log logging
# 91020-0055 - Fixed several bugs with auto-alt-dial, DNC and extended alt number dialing
# 91026-1148 - Added AREACODE DNC option
# 91213-1213 - Added queue_position to queue_log COMPLETE... and ABANDON records
# 100108-2242 - Added answered_time to vicidial_carrier_log
# 100123-1449 - Added double-log end logging
# 100224-1229 - Fixed manual dial park call bug
# 100903-0041 - Changed lead_id max length to 10 digits
# 101111-1556 - Added source to vicidial_hopper inserts
# 101123-0443 - Fixed minor parked call manual dial bug
# 110224-1854 - Added compatibility with QM phone environment logging
# 110304-0005 - Small changes for CPD and on-hook agent features
# 110324-2336 - Changes to CPD logging of calls and addition of the PDROP status
# 121120-0922 - Added QM socket-send functionality
# 121124-2303 - Added Other Campaign DNC option
# 121129-2322 - Added enhanced_disconnect_logging option
# 130412-1321 - Added sip_hangup_cause to carrier log
# 130531-1619 - Fixed issue with busy agent login calls attempting to be logged
# 130802-0739 - Added CAMPCUST dialplan variable definition for outbound calls
# 130925-1820 - Added variable filter to prevent DID SQL injection attack
# 131209-1559 - Added called_count logging
# 140215-2118 - Added several variable options for QM socket URL
# 150804-1740 - Added code to support in-group drop_lead_reset feature
# 161102-1034 - Fixed QM partition problem
# 161214-1717 - Fix for rare multi-server grab from IVR park issue
# 161226-1135 - rolled back previous fix
# 161226-1211 - Fix for previous rolled-back code
# 170325-1105 - Added optional vicidial_drop_log logging
# 170427-1125 - Fix for drop call logging of answered calls hung up by customer first
# 170508-1148 - Added blind monitor call end logging
# 170527-2340 - Fix for rare inbound logging issue #1017
# 170915-1753 - Asterisk 13 compatibility
# 180521-1153 - Changed closer log logging to use agent if call was AGENTDIRECT
# 180919-1729 - Fix for rare non-NA-set-as-NA call logging issue
# 190626-1100 - Added more logging for Auto-Alt-Dial debug
# 190709-2240 - Added Call Quota logging
# 191001-1509 - Small fix for monitoring issue
# 200318-1054 - Added code for OpenSIPs CallerIDname
# 210314-1015 - Added enhanced_disconnect_logging=2 option
# 210606-1007 - Added TILTX features for pre-carrier call filtering
# 210718-0358 - Fixes for 24-Hour Call Count Limits with standard Auto-Alt-Dialing
# 210719-1521 - Added additional state override methods for call_limit_24hour
# 210827-0936 - Added PJSIP compatibility
# 210907-0841 - Added KHOMP code (install JSON::PP Perl module and remove '#UC#' in the code to enable)
# 220103-1520 - Added timeout fix for manual dial calls, set the CAMPDTO dialplan variable
# 220118-0937 - Added $ADB auto-alt-dial extra debug output option, fix for extended auto-alt-dial issue #1337
# 220118-2207 - Added auto_alt_threshold campaign & list settings
#

# defaults for PreFork
$VARfastagi_log_min_servers =	'3';
$VARfastagi_log_max_servers =	'16';
$VARfastagi_log_min_spare_servers = '2';
$VARfastagi_log_max_spare_servers = '8';
$VARfastagi_log_max_requests =	'1000';
$VARfastagi_log_checkfordead =	'30';
$VARfastagi_log_checkforwait =	'60';
$DB=0;
$DBX=0;
$ADB=0;

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
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_min_servers/) && ($CLIVARfastagi_log_min_servers < 1) )
		{$VARfastagi_log_min_servers = $line;   $VARfastagi_log_min_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_servers/) && ($CLIVARfastagi_log_max_servers < 1) )
		{$VARfastagi_log_max_servers = $line;   $VARfastagi_log_max_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_min_spare_servers/) && ($CLIVARfastagi_log_min_spare_servers < 1) )
		{$VARfastagi_log_min_spare_servers = $line;   $VARfastagi_log_min_spare_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_spare_servers/) && ($CLIVARfastagi_log_max_spare_servers < 1) )
		{$VARfastagi_log_max_spare_servers = $line;   $VARfastagi_log_max_spare_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_requests/) && ($CLIVARfastagi_log_max_requests < 1) )
		{$VARfastagi_log_max_requests = $line;   $VARfastagi_log_max_requests =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_checkfordead/) && ($CLIVARfastagi_log_checkfordead < 1) )
		{$VARfastagi_log_checkfordead = $line;   $VARfastagi_log_checkfordead =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_checkforwait/) && ($CLIVARfastagi_log_checkforwait < 1) )
		{$VARfastagi_log_checkforwait = $line;   $VARfastagi_log_checkforwait =~ s/.*=//gi;}
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

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}

if (!$VARDB_port) {$VARDB_port='3306';}

$SERVERLOG = 'N';
$log_level = '0';

use DBI;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtB = "SELECT vd_server_logs,asterisk_version FROM servers where server_ip = '$VARserver_ip';";
$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
$sthBrows=$sthB->rows;
if ($sthBrows > 0)
	{
	@aryB = $sthB->fetchrow_array;
	$SERVERLOG =		$aryB[0];
	$asterisk_version =	$aryB[1];
	}
$sthB->finish();
$dbhB->disconnect();

if ($SERVERLOG =~ /Y/) 
	{
	$childLOGfile = "$PATHlogs/FastAGIchildLOG.$year-$mon-$mday";
	$log_level = "4";
	print "SERVER LOGGING ON: LEVEL-$log_level FILE-$childLOGfile\n";
	}

package VDfastAGI;

use Net::Server;
use vars qw(@ISA);
use Net::Server::PreFork; # any personality will do
@ISA = qw(Net::Server::PreFork);
use Time::HiRes ('tv_interval','gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use Time::Local;

# Needed for Khomp Integration
use JSON::PP qw(encode_json decode_json);

### find curl binary for KHOMP
$curlbin = '';
$khomp_enabled = 1;
if ( -e ('/bin/curl')) {$curlbin = '/bin/curl';}
else
        {
        if ( -e ('/usr/bin/curl')) {$curlbin = '/usr/bin/curl';}
        else
                {
                if ( -e ('/usr/local/bin/curl')) {$curlbin = '/usr/local/bin/curl';}
                else
                        {
                        if ($AGILOG) {$agi_string = "ERROR: curl binary not found, KHOMP disabled";   &agi_output;}
                        $khomp_enabled = 0;
                        }
                }
        }


sub process_request 
	{
	use Asterisk::AGI;
	$AGI = new Asterisk::AGI;

	$carrier_logging_active=0;
	$process = 'begin';
	$script = 'VDfastAGI';
	$DP_variables_enabled=1;

	########## Get current time, parse configs, get logging preferences ##########
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	$now_date_epoch = time();
	$now_date = "$year-$mon-$mday $hour:$min:$sec";


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

	if (!$VARDB_port) {$VARDB_port='3306';}
	if (!$AGILOGfile) {$AGILOGfile = "$PATHlogs/FASTagiout.$year-$mon-$mday";}
	if (!$AADLOGfile) {$AADLOGfile = "$PATHlogs/auto-alt-dial.$year-$mon-$mday";}

	$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
		or die "Couldn't connect to database: " . DBI->errstr;

	### Grab Server values from the database
	$stmtA = "SELECT agi_output,asterisk_version,external_server_ip FROM servers where server_ip = '$VARserver_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		$AGILOG = '0';
		@aryA = $sthA->fetchrow_array;
		$DBagi_output =			$aryA[0];
		$asterisk_version =		$aryA[1];
		$external_server_ip = 	$aryA[2];
		if ($DBagi_output =~ /STDERR/)	{$AGILOG = '1';}
		if ($DBagi_output =~ /FILE/)	{$AGILOG = '2';}
		if ($DBagi_output =~ /BOTH/)	{$AGILOG = '3';}
		}
	$sthA->finish();

	$h_exten_reason=0;
	%ast_ver_str = parse_asterisk_version($asterisk_version);
	if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} >= 12))
		{$h_exten_reason=1;}

	### Grab KHOMP settings from the KHOMPSETTINGS settings container
	$khomp_api_url =		'';
	$khomp_api_proxied =	'false';
	$khomp_api_login_url =	'';
	$khomp_api_user =		'';
	$khomp_api_pass =		'';
	$khomp_header =			'';
	$khomp_id_format =		'';
	$khomp_sub_account_header =	'';

	$stmtA = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = 'KHOMPSETTINGS';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$container_entry = $aryA[0];

		my @lines = split ( /\n/, $container_entry );
		foreach my $line (@lines)
			{
			# remove comments and blank lines
			$line =~ /^\s*$/ and next; # skip blank lines
			$line =~ /^\s*#/ and next; # skip line that begin with #
			if ( $line =~ /#/ ) # check for comments midway through the line
				{
				# remove them
				@split_line = split( /#/ , $line );
				$line = $split_line[0]; 
				}

			if ( $line =~ /=>/ )
				{
				@setting = split( /=>/ , $line ); 
				$key = $setting[0];
				$key =~ s/^\s+|\s+$//g;
				$value = $setting[1];
				$value =~ s/^\s+|\s+$//g;

				if ( $key eq 'khomp_api_url' )			{ $khomp_api_url = $value; }
				if ( $key eq 'khomp_api_proxied' )    	{ $khomp_api_proxied = $value; }
				if ( $key eq 'khomp_api_user' )			{ $khomp_api_user = $value; }
				if ( $key eq 'khomp_api_pass' )			{ $khomp_api_pass = $value; }
				if ( $key eq 'khomp_header' )			{ $khomp_header = $value; }
				if ( $key eq 'khomp_id_format' )		{ $khomp_id_format = $value; }
				if ( $key eq 'khomp_api_login_url' )	{ $khomp_api_login_url = $value; }
				if ( $key eq 'khomp_sub_account_header' )	{ $khomp_sub_account_header = $value; }
				if ( $key eq 'khomp_api_token' )                { $khomp_api_token = $value; }
				if ( $key eq 'khomp_api_token_expire' )         { $khomp_api_token_expire = $value; }
				}

			}
		$agi_string = "KHOMP Settings: url-$khomp_api_url|user-$khomp_api_user|pass-$khomp_api_pass|header-$khomp_header|format-$khomp_id_format|sub-account-$khomp_sub_account_header";
		#&agi_output;
		}
		
	### load the Khomp status map
	%conclusion_map = {};

	$stmtA = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = 'KHOMPSTATUSMAP';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$container_entry = $aryA[0];

		my @lines = split ( /\n/, $container_entry );
		foreach my $line (@lines)
			{
			# remove comments and blank lines
			$line =~ /^\s*$/ and next; # skip blank lines
			$line =~ /^\s*#/ and next; # skip line that begin with #
			if ( $line =~ /#/ ) # check for comments midway through the line
				{
				# remove them
				@split_line = split( /#/ , $line );
				$line = $split_line[0];
				}

			if ( $line =~ /=>/ )
				{
				@setting = split( /=>/ , $line );
				$conclusion_pattern = $setting[0];
				$conclusion_pattern =~ s/^\s+|\s+$//g;

				$action_status = $setting[1];
				$action_status =~ s/^\s+|\s+$//g;

				# check if the conclusion_pattern looks like "blah"."blahblah" or just "blah"
				if ( $conclusion_pattern =~ /"\."/ )
					{
					# if "blah"."blahblah" break it up

					@rsr = split( /"\."/ , $conclusion_pattern );
					$conclusion = $rsr[0];
					$conclusion =~ s/^\s+|\s+$//g;
					$conclusion =~ s/^"|"$//g;

					$pattern = $rsr[1];
					$pattern =~ s/^\s+|\s+$//g;
					$pattern =~ s/^"|"$//g;
					}
				else
					{
					# otherwise result is the string and pattern is blank

					$conclusion = $conclusion_pattern;
					$conclusion =~ s/^\s+|\s+$//g;
					$conclusion =~ s/^"|"$//g;
					$pattern = "";
					}

				@as = split( /\./ , $action_status );
				$action = @as[0];
				$action =~ s/^\s+|\s+$//g;
				$action =~ s/^"|"$//g;

				$status = @as[1];
				$status =~ s/^\s+|\s+$//g;
				$status =~ s/^"|"$//g;
				
				$dial_status = @as[2];
				$dial_status =~ s/^\s+|\s+$//g;
				$dial_status =~ s/^"|"$//g;

				# load the result map hash with the values
				$conclusion_map{"$conclusion"}{"$pattern"}{'action'} = $action;
				$conclusion_map{"$conclusion"}{"$pattern"}{'status'} = $status;	
				$conclusion_map{"$conclusion"}{"$pattern"}{'dial_status'} = $dial_status;						

				}
			}

		}
		
	if ($AGILOG) 
		{
		$agi_string = "+++++++++++++++++ FastAGI Start ++++ Asterisk version: $ast_ver_str{major} $ast_ver_str{minor} ++++++ hER: $h_exten_reason ++++++"; 
		&agi_output;
		}



	### begin parsing run-time options ###
	if (length($ARGV[0])>1)
		{
		if ($AGILOG) 
			{
			$agi_string = "Perl Environment Dump:"; 
			&agi_output;
			}
		$i=0;
		while ($#ARGV >= $i)
			{
			$args = "$args $ARGV[$i]";
			if ($AGILOG) 
				{
				$agi_string = "$i|$ARGV[$i]";   
				&agi_output;
				}
			$i++;
			}
		}

	$HVcauses=0;
	$fullCID=0;
	$callerid='';
	$calleridname='';
	$|=1;
	while(<STDIN>) 
		{
		chomp;
		last unless length($_);
		if ($AGILOG)
			{
			if (/^agi_(\w+)\:\s+(.*)$/)
				{
					$AGI{$1} = $2;
				}
			}

		if (/^agi_uniqueid\:\s+(.*)$/)		{$unique_id = $1; $uniqueid = $unique_id;}
		if (/^agi_priority\:\s+(.*)$/)		{$priority = $1;}
		if (/^agi_channel\:\s+(.*)$/)		{$channel = $1;}
		if (/^agi_extension\:\s+(.*)$/)		{$extension = $1;}
		if (/^agi_type\:\s+(.*)$/)			{$type = $1;}
		if (/^agi_request\:\s+(.*)$/)		{$request = $1;}
		if ( ($request =~ /--fullCID--/i) && (!$fullCID) )
			{
			$fullCID=1;
			@CID = split(/-----/, $request);
			$callerid =	$CID[2];
			$calleridname =	$CID[3];
			$agi_string = "URL fullCID: |$callerid|$calleridname|$request|";   
			&agi_output;
			}
		if ( ($request =~ /--HVcauses--/i) && (!$HVcauses) )
			{
			$HVcauses=1;
			@ARGV_vars = split(/-----/, $request);
			$PRI = $ARGV_vars[0];
			$PRI =~ s/.*--HVcauses--//gi;
			$DEBUG = $ARGV_vars[1];
			$hangup_cause =			$ARGV_vars[2];
			$dialstatus =			$ARGV_vars[3];
			$dial_time =			$ARGV_vars[4];
			$answered_time =		$ARGV_vars[5];
			$tech_hangup_cause =	$ARGV_vars[6];
            if( $dial_time > $answered_time ) 
				{$ring_time = $dial_time - $answered_time;}
            else 
				{$ring_time = 0;}
			$agi_string = "URL HVcauses: |$PRI|$DEBUG|$hangup_cause|$dialstatus|$dial_time|$ring_time|$tech_hangup_cause|";   
			&agi_output;
			}
		if (!$fullCID)	# if no fullCID sent
			{
			if (/^agi_callerid\:\s+(.*)$/)		{$callerid = $1;}
			if (/^agi_calleridname\:\s+(.*)$/)	{$calleridname = $1;}
			if ( $calleridname =~ /\"/)  {$calleridname =~ s/\"//gi;}
	#	if ( (length($calleridname)>5) && ( (!$callerid) or ($callerid =~ /unknown|private|00000000/i) or ($callerid =~ /5551212/) ) )
		if ( ( 
		(length($calleridname)>5) && ( (!$callerid) or ($callerid =~ /unknown|private|00000000/i) or ($callerid =~ /5551212/) )
		) or ( (length($calleridname)>17) && ($calleridname =~ /\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ) )
			{$callerid = $calleridname;}

			### allow for ANI being sent with the DNIS "*3125551212*9999*"
			if ($extension =~ /^\*\d\d\d\d\d\d\d\d\d\d\*/)
				{
				$callerid = $extension;
				$callerid =~ s/\*\d\d\d\d\*$//gi;
				$callerid =~ s/^\*//gi;
				$extension =~ s/^\*\d\d\d\d\d\d\d\d\d\d\*//gi;
				$extension =~ s/\*$//gi;
				}
			$calleridname = $callerid;
			}
		}
	$callerid =~ s/\'|\\\\|\\\|\\|\\;|\\\;|\;|;//gi;
	$calleridname =~ s/\'|\\\\|\\\|\\|\\;|\\\;|\;|;//gi;
	$extension =~ s/\'|\"|\\\\|\\\|\\|\\;|\\\;|\;|;//gi;
	$agi_called_ext = $extension;

	if ($AGILOG) 
		{
		$agi_string = "AGI Environment Dump:";   
		&agi_output;
		}

	foreach $i (sort keys %AGI) 
		{
		if ($AGILOG) 
			{
			$agi_string = " -- $i = $AGI{$i}";   
			&agi_output;
			}
		}


	if ($AGILOG) 
		{
		$agi_string = "AGI Variables: |$unique_id|$channel|$extension|$type|$callerid|";   
		&agi_output;
		}

	if ( ($extension =~ /h/i) && (length($extension) < 3))  {$stage = 'END';}
	else {$stage = 'START';}

	$process = $request;
	$process =~ s/agi:\/\///gi;
	$process =~ s/.*\/|--.*//gi;
	if ($AGILOG) 
		{
		$agi_string = "Process to run: |$request|$process|$stage|";   
		&agi_output;
		}


	###################################################################
	##### START call_log process ######################################
	###################################################################
	if ($process =~ /^call_log/)
		{
		### call start stage
		if ($stage =~ /START/)
			{
			$callerid =~ s/ .*//gi;
			$channel_group='';

			if ($AGILOG) {$agi_string = "+++++ CALL LOG START : $now_date";   &agi_output;}

			if ($channel =~ /^SIP|^PJSIP/) {$channel =~ s/-.*//gi;}
			if ($channel =~ /^IAX2/) {$channel =~ s/\/\d+$//gi;}
			if ($channel =~ /^Zap\/|^DAHDI\//)
				{
				$channel_line = $channel;
				$channel_line =~ s/^Zap\/|^DAHDI\///gi;

				$stmtA = "SELECT count(*) FROM phones where server_ip='$VARserver_ip' and extension='$channel_line' and protocol='Zap';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				@aryA = $sthA->fetchrow_array;
				$is_client_phone	 = $aryA[0];
				$sthA->finish();

				if ($is_client_phone < 1)
					{
					$channel_group = 'Zap Trunk Line';
					$number_dialed = $extension;
					}
				else
					{
					$channel_group = 'Zap Client Phone';
					}
				if ($AGILOG) {$agi_string = $channel_group . ": $aryA[0]|$channel_line|";   &agi_output;}
				}
			if ($channel =~ /^Local\//)
				{
				$channel_line = $channel;
				$channel_line =~ s/^Local\/|\@.*//gi;

				$stmtA = "SELECT count(*) FROM phones where server_ip='$VARserver_ip' and dialplan_number='$channel_line' and protocol='EXTERNAL';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				@aryA = $sthA->fetchrow_array;
				$is_client_phone	 = $aryA[0];
				$sthA->finish();

				if ($is_client_phone < 1)
					{
					$channel_group = 'Local Channel Line';
					$number_dialed = $extension;
					}
				else
					{
					$channel_group = 'EXTERNAL Client Phone';
					}
				if ($AGILOG) {$agi_string = $channel_group . ": $aryA[0]|$channel_line|";   &agi_output;}
				}
			### This section breaks the outbound dialed number down(or builds it up) to a 10 digit number and gives it a description
			if ( ($channel =~ /^SIP|^PJSIP|^IAX2/) || ( ($is_client_phone > 0) && (length($channel_group) < 1) ) )
				{
				if ( ($extension =~ /^901144/) && (length($extension)==16) )  #test 207 608 6400 
					{$extension =~ s/^9//gi;	$channel_group = 'Outbound Intl UK';}
				if ( ($extension =~ /^901161/) && (length($extension)==15) )  #test  39 417 2011
					{$extension =~ s/^9//gi;	$channel_group = 'Outbound Intl AUS';}
				if ( ($extension =~ /^91800|^91888|^91877|^91866/) && (length($extension)==12) )
					{$extension =~ s/^91//gi;	$channel_group = 'Outbound Local 800';}
				if ( ($extension =~ /^9/) && (length($extension)==8) )
					{$extension =~ s/^9/727/gi;	$channel_group = 'Outbound Local';}
				if ( ($extension =~ /^9/) && (length($extension)==11) )
					{$extension =~ s/^9//gi;	$channel_group = 'Outbound Local';}
				if ( ($extension =~ /^91/) && (length($extension)==12) )
					{$extension =~ s/^91//gi;	$channel_group = 'Outbound Long Distance';}
				if ($is_client_phone > 0)
					{$channel_group = 'Client Phone';}
				
				$SIP_ext = $channel;	$SIP_ext =~ s/PJSIP\/|SIP\/|IAX2\/|Zap\/|DAHDI\/|Local\///gi;

				$number_dialed = $extension;
				$extension = $SIP_ext;
				}

			if ( ($callerid =~ /^V|^M/) && ($callerid =~ /\d\d\d\d\d\d\d\d\d/) && (length($number_dialed)<1) )
				{
				$stmtA = "SELECT cmd_line_b,cmd_line_d FROM vicidial_manager where callerid='$callerid' limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$cmd_line_b	=	$aryA[0];
					$cmd_line_d	=	$aryA[1];
					$cmd_line_b =~ s/Exten: //gi;
					$cmd_line_d =~ s/Channel: Local\/|@.*//gi;
					$rec_count++;
					}
				$sthA->finish();
				if ($callerid =~ /^V/) {$number_dialed = "$cmd_line_d";}
				if ($callerid =~ /^M/) {$number_dialed = "$cmd_line_b";}
				$number_dialed =~ s/\D//gi;
				if (length($number_dialed)<1) {$number_dialed=$extension;}
				}

			### set dialplan variable CAMPCUST to the campaign_id of the outbound auto-dial or manual dial call
			if ( ($callerid =~ /^V|^M/) && ($callerid =~ /\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) && ($DP_variables_enabled > 0) )
				{
				$CAMPCUST='';
				$stmtA = "SELECT campaign_id FROM vicidial_auto_calls where callerid='$callerid' limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$CAMPCUST	=	$aryA[0];
					$campaign =	$aryA[0];
					}
				$sthA->finish();

				### get campaign settings
				$stmtA = "SELECT amd_type,campaign_vdad_exten,dial_timeout,manual_dial_timeout FROM vicidial_campaigns where campaign_id = '$campaign';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$amd_type =     $aryA[0];
					$campaign_vdad_exten =	$aryA[1];
					$dial_timeout =			$aryA[2];
					$man_dial_timeout =		$aryA[3];
					}
				$sthA->finish();
				
				### set dialplan variable CAMPCUST to the campaign_id of the outbound auto-dial or manual dial call
				if (length($CAMPCUST) > 0)
					{
					$AGI->exec("EXEC Set(_CAMPCUST=$CAMPCUST)");
					if ($AGILOG) {$agi_string = "|CAMPCUST: $CAMPCUST|$callerid|";   &agi_output;}
					}

				### on manual dial calls set the CAMPDTO dialplan variable
				if ($callerid =~ /^M/)
					{
					if (length($man_dial_timeout) > 0)
						{
						$dial_timeout = $man_dial_timeout;
						}
					$AGI->exec("EXEC Set(_CAMPDTO=$dial_timeout)");
					if ($AGILOG) {$agi_string = "|CAMPDTO: $dial_timeout|$callerid|";   &agi_output;}
					}

				### BEGIN OpenSIPs CallerIDname code ###
				### get system_settings
				$stmtA = "SELECT opensips_cid_name FROM system_settings";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$opensips_cid_name =     $aryA[0];
					}
				$sthA->finish();
				if ($AGILOG) {$agi_string = "$stmtA|$opensips_cid_name";   &agi_output;}

				### opensips_cid_name is active
				if ( $opensips_cid_name == 1)
					{
					### get the Campaign CID Name
					$stmtA = "SELECT opensips_cid_name FROM vicidial_campaigns where campaign_id = '$CAMPCUST';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$camp_opensips_cid_name =     $aryA[0];
						}
					$sthA->finish();
					if ($AGILOG) {$agi_string = "$stmtA|$camp_opensips_cid_name";   &agi_output;}

					### check that the campaign has a CID Name set
					if ( $camp_opensips_cid_name ne "" ) 
						{
						if ($AGILOG) {$agi_string = "Adding \"X-CIDNAME: $camp_opensips_cid_name\" header to INVITE";   &agi_output;}
						$header = "X-CIDNAME: " . $camp_opensips_cid_name;
						$AGI->exec("EXEC SIPAddHeader(\"$header\")");
						}
					}
				### END OpenSIPs CallerIDname code ###

				if ($AGILOG) {$agi_string = "|KHOMP $amd_type|$HVcauses|$extension|$campaign_vdad_exten"; &agi_output;}

				### Add the KHOMP SIP X header to the outbound call
				if (( $amd_type eq 'KHOMP' ) && ($agi_called_ext!= $campaign_vdad_exten))
					{
					# determin header format
					if ($khomp_id_format eq 'CALLERCODE') 
						{ $khomp_id = $callerid; }
					elsif ($khomp_id_format eq 'CALLERCODE_EXTERNIP') 
						{ $khomp_id = $callerid . '_' . $external_server_ip; }
					elsif ($khomp_id_format eq 'CALLERCODE_CAMP_EXTERNIP') 
						{ $khomp_id = $callerid . '_' . $campaign . '_' . $external_server_ip; }

					$stmtA = "INSERT INTO vicidial_khomp_log SET caller_code = '$callerid', lead_id = '$lead_id', server_ip = '$VARserver_ip', khomp_header = '$khomp_header', khomp_id = '$khomp_id', khomp_id_format = '$khomp_id_format', start_date=NOW()";
					if ($AGILOG) {$agi_string = "--    KHOMP Log Insert: |$stmtA|";   &agi_output;}
					$dbhA->do($stmtA);

					# add the SIP tracking header
					$header = "X-" . $khomp_header . ": " . $khomp_id;
					$AGI->exec("EXEC SIPAddHeader(\"$header\")");
					if ($AGILOG) {$agi_string = "|KHOMP SIP Header= $header|$khomp_id_format|";   &agi_output;}

					if ( $khomp_sub_account_header ne '' )
						{
						$sub_header = "X-KHOMP-Sub-Account:" . $khomp_sub_account_header;
						$AGI->exec("EXEC SIPAddHeader(\"$sub_header\")");
						if ($AGILOG) {$agi_string = "|KHOMP Sub Account Header= $sub_header|";   &agi_output;}
						}
					}
				}

			$stmtA = "INSERT INTO call_log (uniqueid,channel,channel_group,type,server_ip,extension,number_dialed,start_time,start_epoch,end_time,end_epoch,length_in_sec,length_in_min,caller_code) values('$unique_id','$channel','$channel_group','$type','$VARserver_ip','$extension','$number_dialed','$now_date','$now_date_epoch','','','','','$callerid')";

			if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
			$affected_rows = $dbhA->do($stmtA);

			$dbhA->disconnect();
			}


		### call end stage
		else
			{
			if ($AGILOG) {$agi_string = "|CALL HUNG UP|";   &agi_output;}

			$callerid =~ s/ .*//gi;
			$callerid =~ s/\"//gi;
			$CIDlead_id = $callerid;
			$CIDlead_id = substr($CIDlead_id, 10, 10);
			$CIDlead_id = ($CIDlead_id + 0);

			$park_abandon=0;
			### find if call was parked on another server
			$stmtA = "SELECT count(*) from parked_channels where channel_group='$callerid';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$sthA->finish();
				$parked_count = $aryA[0];
				if ($parked_count > 0)
					{
					$stmtA = "SELECT server_ip from parked_channels where channel_group='$callerid';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$sthA->finish();
						$parked_server_ip = $aryA[0];
						if ( ($parked_server_ip =~ /$VARserver_ip/) && (length($VARserver_ip) == length($parked_server_ip)) )
							{
							if ($AGILOG) {$agi_string = "--    PARKED CALL ON-SERVER: |$parked_server_ip|$VARserver_ip|$callerid";   &agi_output;}
							}
						else
							{
							if ($AGILOG) {$agi_string = "--    PARKED CALL NON-SERVER: |$parked_server_ip|$VARserver_ip|$callerid";   &agi_output;}
							$park_abandon=1;
							}
						}
					}
				}
			
			if ($park_abandon < 1)
				{
				if ($request =~ /--HVcauses--/i)
					{
					$HVcauses=1;
					@ARGV_vars = split(/-----/, $request);
					$PRI = $ARGV_vars[0];
					$PRI =~ s/.*--HVcauses--//gi;
					$DEBUG =				$ARGV_vars[1];
					$hangup_cause =			$ARGV_vars[2];
					$dialstatus =			$ARGV_vars[3];
					$dial_time =			$ARGV_vars[4];
					$answered_time =		$ARGV_vars[5];
					$tech_hangup_cause = 	$ARGV_vars[6];
					if( $dial_time > $answered_time ) 
						{$ring_time = $dial_time - $answered_time;}
					else 
						{$ring_time = 0;}
					$agi_string = "URL HVcauses: |$PRI|$DEBUG|$hangup_cause|$dialstatus|$dial_time|$ring_time|$tech_hangup_cause|";
					&agi_output;

					if ( (length($dialstatus) > 0) && ($callerid =~ /^V|^M/) )
						{
						### Grab Server values from the database
						$stmtA = "SELECT carrier_logging_active FROM servers where server_ip = '$VARserver_ip';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							if ($aryA[0] =~ /Y/)
								{$carrier_logging_active = 1;}
							else
								{$carrier_logging_active = 0;}
							}
						$sthA->finish();

						if ($carrier_logging_active > 0)
							{
							$beginUNIQUEID = $unique_id;
							$beginUNIQUEID =~ s/\..*//gi;
							if ($callerid =~ /^M/) 
								{
								$stmtA = "SELECT uniqueid FROM call_log where uniqueid LIKE \"$beginUNIQUEID%\" and caller_code LIKE \"%$CIDlead_id\";";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								$rec_count=0;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$unique_id =	$aryA[0];
									$uniqueid =		$aryA[0];
									}
								$sthA->finish();
								}

							### if using Asterisk 11 and older method for hangup reasons, check vicidial_dial_log
							if ($h_exten_reason < 1) 
								{
								$sip_hangup_cause=0;
								$sip_hangup_reason='';

								$stmtA = "SELECT sip_hangup_cause,sip_hangup_reason FROM vicidial_dial_log where lead_id='$CIDlead_id' and server_ip='$VARserver_ip' and caller_code='$callerid' order by call_date desc limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$sip_hangup_cause =		$aryA[0];
									$sip_hangup_reason =	$aryA[1];
									}
								$sthA->finish();
									if ($AGILOG) {$agi_string = "$sthArows|$stmtA|$sip_hangup_cause|$sip_hangup_reason|";   &agi_output;}
								}

							### if using Asterisk 12 and newer method for hangup reasons, check variables
							else
								{
								if ( $tech_hangup_cause =~ /SIP/ ) 
									{
									# we got the tech hangup cause from Asterisk (available in asterisk 13+)
									( $tech, $sip_hangup_cause, @error ) = split( / /, $tech_hangup_cause );
									$sip_hangup_reason = join( ' ', @error );

									# the vicidial_dial_log does not have the sip hangup data so populate it
									$stmtA = "UPDATE vicidial_dial_log SET sip_hangup_cause='$sip_hangup_cause',sip_hangup_reason='$sip_hangup_reason',uniqueid='$uniqueid' where caller_code='$callerid' and server_ip='$VARserver_ip' and lead_id='$CIDlead_id';";
									$dbhA->do($stmtA);		
									}
								else
									{
									# tech hangup cause was unavailable
									$sip_hangup_cause=0;
									$sip_hangup_reason='';

									$stmtA = "SELECT sip_hangup_cause,sip_hangup_reason FROM vicidial_dial_log where lead_id='$CIDlead_id' and server_ip='$VARserver_ip' and caller_code='$callerid' order by call_date desc limit 1;";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									if ($sthArows > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$sip_hangup_cause =		$aryA[0];
										$sip_hangup_reason =	$aryA[1];
										}
									$sthA->finish();
									}

								if ($AGILOG) {$agi_string = "$sthArows|$stmtA|$sip_hangup_cause|$sip_hangup_reason|";   &agi_output;}
								}

							$stmtA = "INSERT IGNORE INTO vicidial_carrier_log set uniqueid='$uniqueid',call_date='$now_date',server_ip='$VARserver_ip',lead_id='$CIDlead_id',hangup_cause='$hangup_cause',dialstatus='$dialstatus',channel='$channel',dial_time='$dial_time',answered_time='$answered_time',sip_hangup_cause='$sip_hangup_cause',sip_hangup_reason='$sip_hangup_reason',caller_code='$callerid';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$VCARaffected_rows = $dbhA->do($stmtA);
							if ($AGILOG) {$agi_string = "--    CARRIER LOG insert: |$VCARaffected_rows|$CIDlead_id|$hangup_cause|$sip_hangup_cause|$sip_hangup_reason|";   &agi_output;}
							}
						}
					}

				### BEGIN log end of real-time blind monitor calls ###
				if ( ( ($callerid =~ /^BM\d\d\d\d\d\d\d\d/) && ($channel =~ /ASTblind/) ) || ($callerid =~ /^BB\d\d\d\d\d\d\d\d/) || ($callerid =~ /^BW\d\d\d\d\d\d\d\d/) )
					{
					$stmtA = "SELECT monitor_start_time,UNIX_TIMESTAMP(monitor_start_time) from vicidial_rt_monitor_log where caller_code='$callerid' and ( (monitor_end_time is NULL) or (monitor_start_time=monitor_end_time) );";
			#		if ($AGILOG) {$agi_string = "|JCJ|$stmtA|$monitor_start_time|$EPOCHmonitor_start_time|";   &agi_output;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$sthA->finish();
						$monitor_start_time =		$aryA[0];
						$EPOCHmonitor_start_time =	$aryA[1];
						$monitor_sec = ($now_date_epoch - $EPOCHmonitor_start_time);

						$stmtA = "UPDATE vicidial_rt_monitor_log set monitor_sec='$monitor_sec',monitor_end_time='$now_date' where caller_code='$callerid';";
						$affected_rowsBM = $dbhA->do($stmtA);

						if ($AGILOG) {$agi_string = "|$affected_rowsBM|$stmtA|$monitor_start_time|$EPOCHmonitor_start_time|";   &agi_output;}
						}
					}
				### END log end of real-time blind monitor calls ###

				### get uniqueid and start_epoch from the call_log table
				$CALLunique_id = $unique_id;
				$stmtA = "SELECT uniqueid,start_epoch,channel,end_epoch,channel_group FROM call_log where uniqueid='$unique_id';";
				if ($callerid =~ /^M/) 
					{$stmtA = "SELECT uniqueid,start_epoch,channel,end_epoch,channel_group FROM call_log where caller_code='$callerid' and channel NOT LIKE \"Local\/%\";";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				while ($sthArows > $rec_count)
					{
					@aryA = $sthA->fetchrow_array;
					$unique_id =		$aryA[0];
					$uniqueid =			$aryA[0];
					$start_time	=		$aryA[1];
					if ($callerid =~ /^M/)
						{$channel =			$aryA[2];}
					$end_epoch =		$aryA[3];
					$channel_group = 	$aryA[4]; 
					if ($AGILOG) {$agi_string = "|$aryA[0]|$aryA[1]|$aryA[2]|$aryA[3]|";   &agi_output;}
					$rec_count++;
					}
				$sthA->finish();

				$did_log=0;
				if ( ($channel_group =~ /DID_INBOUND/) && ($end_epoch > 1000) )
					{$did_log=1;}
				if ( ($rec_count) && ($did_log < 1) )
					{
					$length_in_sec = ($now_date_epoch - $start_time);
					$length_in_min = ($length_in_sec / 60);
					$length_in_min = sprintf("%8.2f", $length_in_min);

					$stmtA = "UPDATE call_log set end_time='$now_date',end_epoch='$now_date_epoch',length_in_sec=$length_in_sec,length_in_min='$length_in_min',channel='$channel' where uniqueid='$unique_id'";

					if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
					$affected_rowsL = $dbhA->do($stmtA);

					if ($AGILOG) {$agi_string = "QUERY done: start time = $start_time | sec: $length_in_sec | min: $length_in_min |$affected_rowsL";   &agi_output;}

					if ($channel_group =~ /DID_INBOUND/)
						{
						$stmtA = "SELECT recording_id,start_epoch,filename,end_epoch FROM recording_log where vicidial_id='$unique_id' order by start_time desc limit 1;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$CLrecording_id = 	$aryA[0];
							$CLstart_epoch =	$aryA[1];
							$CLfilename = 		$aryA[2];
							$CLend_epoch = 		$aryA[3];

							if (length($CLend_epoch) < 5)
								{
								$CLlength_in_sec = ($now_date_epoch - $CLstart_epoch);
								$CLlength_in_min = ($CLlength_in_sec / 60);
								$CLlength_in_min = sprintf("%8.2f", $CLlength_in_min);

								$stmtA = "UPDATE recording_log set end_time='$now_date',end_epoch='$now_date_epoch',length_in_sec=$CLlength_in_sec,length_in_min='$CLlength_in_min' where recording_id='$CLrecording_id'";
								$affected_rowsRL = $dbhA->do($stmtA);
								if ($AGILOG) {$agi_string = "Recording stopped: $CLstart_epoch|$now_date_epoch|$affected_rowsRL|$stmtA|";   &agi_output;}
								}
							}
						$sthA->finish();
						}
					}
				### BEGIN Double-log end logging ###
				$DOUBLEunique_id = $unique_id . "99";
				$stmtA = "SELECT start_epoch FROM call_log where uniqueid='$DOUBLEunique_id' and channel_group='DOUBLE_LOG' order by start_time desc limit 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DOUBLEstart_epoch =	$aryA[0];

					$DOUBLElength_in_sec = ($now_date_epoch - $DOUBLEstart_epoch);
					$DOUBLElength_in_min = ($DOUBLElength_in_sec / 60);
					$DOUBLElength_in_min = sprintf("%8.2f", $DOUBLElength_in_min);

					$stmtA = "UPDATE call_log set end_time='$now_date',end_epoch='$now_date_epoch',length_in_sec=$DOUBLElength_in_sec,length_in_min='$DOUBLElength_in_min' where uniqueid='$DOUBLEunique_id' and channel_group='DOUBLE_LOG' order by start_time desc limit 1";

					if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
					$affected_rowsDB = $dbhA->do($stmtA);

					if ($AGILOG) {$agi_string = "DOUBLE QUERY done: start time = $DOUBLEstart_epoch | sec: $DOUBLElength_in_sec | min: $DOUBLElength_in_min |$affected_rowsDB";   &agi_output;}
					}
				$sthA->finish();
				### END Double-log end logging ###

				$stmtA = "DELETE from live_inbound where uniqueid IN('$unique_id','$CALLunique_id') and server_ip='$VARserver_ip'";
				if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
				$affected_rows = $dbhA->do($stmtA);

			##### BEGIN Park Log entry check and update #####
				$stmtA = "SELECT UNIX_TIMESTAMP(parked_time),UNIX_TIMESTAMP(grab_time) FROM park_log where uniqueid='$unique_id' and server_ip='$VARserver_ip' and (parked_sec is null or parked_sec < 1) order by parked_time desc LIMIT 1;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$rec_count=0;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$parked_time	=		$aryA[0];
					$grab_time	=			$aryA[1];
					if ($AGILOG) {$agi_string = "|$aryA[0]|$aryA[1]|";   &agi_output;}
					$rec_count++;
					}
				$sthA->finish();

				if ($rec_count)
					{
					if ($AGILOG) {$agi_string = "*****Entry found for $unique_id-$VARserver_ip in park_log: $parked_time|$grab_time";   &agi_output;}
					if ($parked_time > $grab_time)
						{
						$parked_sec=($now_date_epoch - $parked_time);
						$talked_sec=0;
						}
					else
						{
						$talked_sec=($now_date_epoch - $parked_time);
						$parked_sec=($grab_time - $parked_time);
						}

					$stmtA = "UPDATE park_log set status='HUNGUP',hangup_time='$now_date',parked_sec='$parked_sec',talked_sec='$talked_sec' where uniqueid='$unique_id' and server_ip='$VARserver_ip' order by parked_time desc LIMIT 1";
					$affected_rows = $dbhA->do($stmtA);
					}
			##### END Park Log entry check and update #####

			#	$dbhA->disconnect();

				if ($AGILOG) {$agi_string = "+++++ CALL LOG HUNGUP: |$unique_id|$channel|$extension|$now_date|min: $length_in_min|";   &agi_output;}


			##### BEGIN former VD_hangup section functions #####

				$VDADcampaign='';
				$VDADphone='';
				$VDADphone_code='';
				$VDL_status='';

				if ($DEBUG =~ /^DEBUG$/)
					{
					### open the hangup cause out file for writing ###
					open(out, ">>$PATHlogs/HANGUP_cause-output.txt")
							|| die "Can't open $PATHlogs/HANGUP_cause-output.txt: $!\n";

					print out "$now_date|$hangup_cause|$dialstatus|$dial_time|$ring_time|$unique_id|$channel|$extension|$type|$callerid|$calleridname|$priority|\n";

					close(out);
					}
				else 
					{
					if ($AGILOG) {$agi_string = "DEBUG: $DEBUG";   &agi_output;}
					}

				if ($AGILOG) {$agi_string = "VD_hangup : $callerid $channel $priority $CIDlead_id";   &agi_output;}

				$CLauto_alt_threshold=0;
				if ($callerid =~ /^V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/)
					{
					$campaign='';

					# check vac for the call to get the campaign id
					$stmtA = "SELECT campaign_id FROM vicidial_auto_calls where uniqueid = '$unique_id' or callerid = '$callerid' limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$campaign =     $aryA[0];
						}
					$sthA->finish();
					if ($AGILOG) {$agi_string = "|$stmtA|"; &agi_output;}

					# vac does not have a record of the call check the vl
					if ( $campaign eq '' )
						{
						$stmtA = "SELECT campaign_id FROM vicidial_log where uniqueid = '$unique_id' limit 1;"; 
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$campaign =     $aryA[0];
							}
						$sthA->finish();
						if ($AGILOG) {$agi_string = "|$stmtA|"; &agi_output;}
						}

					### get campaign settings
					$stmtA = "SELECT amd_type,auto_alt_threshold FROM vicidial_campaigns where campaign_id = '$campaign';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$amd_type =				$aryA[0];
						$CLauto_alt_threshold = $aryA[1];
						}
					$sthA->finish();

					if (( $amd_type eq 'KHOMP' ) && ( $khomp_enabled ) )
						{
						if ($AGILOG) {$agi_string = "--    KHOMP process call for campaign $campaign callerid $callerid";   &agi_output;}
						( $khomp_action, $khomp_VDL_status, $khomp_VDAC_status ) = process_khomp_analytics(
							$khomp_api_url, 
							$khomp_api_proxied,
							$khomp_api_login_url,
							$khomp_api_user, 
							$khomp_api_pass, 
							$khomp_header, 
							$khomp_id_format, 
							$external_server_ip, 
							$campaign, 
							$callerid, 
							$cpd_amd_action, 
							$cpd_unknown_action,
							$CIDlead_id
							);
						}
					}

				if ($channel =~ /^Local/)
					{
					$enhanced_disconnect_logging=0;
					#############################################
					##### SYSTEM SETTINGS LOOKUP #####
					$stmtA = "SELECT enhanced_disconnect_logging FROM system_settings;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$enhanced_disconnect_logging =	$aryA[0];
						}
					$sthA->finish();
					##### END SYSTEM SETTINGS LOOKUP #####
					###########################################

					$CPDfound=0;

					# V2251502010052435563
					if ($callerid =~ /^V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/)
						{
					#	($s_hires, $usec) = gettimeofday();   # get seconds and microseconds since the epoch
					#	$usec = sprintf("%06s", $usec);
					#	$HRmsec = substr($usec, -6);
					#	($HRsec,$HRmin,$HRhour,$HRmday,$HRmon,$HRyear,$HRwday,$HRyday,$HRisdst) = localtime($s_hires);
					#	$HRyear = ($HRyear + 1900);
					#	$HRmon++;
					#	if ($HRmon < 10) {$HRmon = "0$HRmon";}
					#	if ($HRmday < 10) {$HRmday = "0$HRmday";}
					#	if ($HRhour < 10) {$HRFhour = "0$HRhour";}
					#	if ($HRmin < 10) {$HRmin = "0$HRmin";}
					#	if ($HRsec < 10) {$HRsec = "0$HRsec";}
					#	$HRnow_date = "$HRyear-$HRmon-$HRmday $HRhour:$HRmin:$HRsec.$HRmsec";
					#
					#	if ($AGILOG) {$agi_string = "HiRes Time: $callerid|$channel|$priority|$CIDlead_id|$uniqueid|$HRnow_date|$now_date";   &agi_output;}

						if ( $amd_type eq 'CPD' )
							{
							##############################################################
							### BEGIN - CPD Look for result for B/DC calls
							##############################################################
							sleep(1);

							$stmtA = "SELECT result FROM vicidial_cpd_log where callerid='$callerid' and result NOT IN('Voice','Unknown','???','') order by cpd_id desc limit 1;";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$cpd_result		= $aryA[0];
								$sthA->finish();
								if ($cpd_result =~ /Busy/i)					{$VDL_status='CPDB';	$VDAC_status='BUSY';   $CPDfound++;}
								if ($cpd_result =~ /Unknown/i)				{$VDL_status='CPDUK';	$VDAC_status='NA';   $CPDfound++;}
								if ($cpd_result =~ /All-Trunks-Busy/i)		{$VDL_status='CPDATB';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /No-Answer/i)			{$VDL_status='CPDNA';	$VDAC_status='NA';   $CPDfound++;}
								if ($cpd_result =~ /Reject/i)				{$VDL_status='CPDREJ';	$VDAC_status='DISCONNECT';   $CPDfound++;}
								if ($cpd_result =~ /Invalid-Number/i)		{$VDL_status='CPDINV';	$VDAC_status='DISCONNECT';   $CPDfound++;}
								if ($cpd_result =~ /Service-Unavailable/i)	{$VDL_status='CPDSUA';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /Sit-Intercept/i)		{$VDL_status='CPDSI';	$VDAC_status='DISCONNECT';   $CPDfound++;}
								if ($cpd_result =~ /Sit-No-Circuit/i)		{$VDL_status='CPDSNC';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /Sit-Reorder/i)			{$VDL_status='CPDSR';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /Sit-Unknown/i)			{$VDL_status='CPDSUK';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /Sit-Vacant/i)			{$VDL_status='CPDSV';	$VDAC_status='CONGESTION';   $CPDfound++;}
								if ($cpd_result =~ /\?\?\?/i)				{$VDL_status='CPDERR';	$VDAC_status='NA';   $CPDfound++;}
								if ($cpd_result =~ /Fax|Modem/i)			{$VDL_status='AFAX';	$VDAC_status='FAX';   $CPDfound++;}
								if ($cpd_result =~ /Answering-Machine/i)	{$VDL_status='AA';		$VDAC_status='AMD';   $CPDfound++;}
								}
							$sthA->finish();
								if ($AGILOG) {$agi_string = "$sthArows|$cpd_result|$stmtA|";   &agi_output;}
							##############################################################
							### END - CPD Look for result for B/DC calls
							##############################################################
							}

						##############################################################
						### BEGIN - KHOMP status B/DC calls based off result
						##############################################################
						if (( $amd_type eq 'KHOMP' ) && ( $khomp_VDL_status ne 'ERROR' ) && ( $khomp_VDAC_status ne 'ERROR' ))
							{
							$VDL_status = $khomp_VDL_status;
							$VDAC_status = $khomp_VDAC_status;
							if ($AGILOG) {$agi_string = "--    KHOMP CPDfound=$CPDfound|khomp_action=$khomp_action|$callerid";   &agi_output;}
							if (
								( ( $khomp_action eq 'cpdunknown') && (( $cpd_unknown_action eq 'MESSAGE' ) || ( $cpd_unknown_action eq 'DISPO' ))) ||
								( ( $khomp_action eq 'amdaction') && (( $cpd_amd_action eq 'MESSAGE' ) || ( $cpd_amd_action eq 'DISPO' ))) ||
								( $khomp_action eq 'status') 
							   )
								{ $CPDfound = 1; }
								
							if ($AGILOG) {$agi_string = "--    KHOMP setting VDL_status = $VDL_status | VDAC_status = $VDAC_status";   &agi_output;}
							}
						##############################################################
						### END - KHOMP status B/DC calls based off result
						##############################################################
						}

					if ( ($PRI =~ /^PRI$/) && ($callerid =~ /\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) && ( ( ($dialstatus =~ /BUSY/) || ( ($dialstatus =~ /CHANUNAVAIL/) && ($hangup_cause =~ /^1$|^28$/) ) || ( ($enhanced_disconnect_logging > 0) && ( ( ($dialstatus =~ /CONGESTION/) && ($hangup_cause =~ /^1$|^19$|^21$|^34$|^38$|^102$/) ) || ( ($dialstatus =~ /CHANUNAVAIL/) && ($hangup_cause =~ /^18$/) ) || ($dialstatus =~ /DNC|DISCONNECT/) ) )  || ($CPDfound > 0) && ($callerid !~ /^S\d\d\d\d\d\d\d\d\d\d\d\d/) ) ) )
						{
						if ($CPDfound < 1) 
							{
							if ($enhanced_disconnect_logging == '2') 
								{
								if ($dialstatus =~ /BUSY/) {$VDL_status = 'AB'; $VDAC_status = 'BUSY';}
								if ($dialstatus =~ /CHANUNAVAIL/) {$VDL_status = 'ADC'; $VDAC_status = 'DISCONNECT';}
								if ($enhanced_disconnect_logging > 0)
									{
									if ($dialstatus =~ /CHANUNAVAIL/ && $hangup_cause =~/^18/) {$VDL_status = 'ADCT'; $VDAC_status = 'DISCONNECT';}
									if ($dialstatus =~ /CONGESTION/ && $hangup_cause =~ /^1$/) {$VDL_status = 'ADC'; $VDAC_status = 'DISCONNECT';}
									if (($dialstatus =~ /CONGESTION/ && $hangup_cause =~ /^21$|^34$|^38$|^102$/) || ($dialstatus =~ /BUSY/ && $hangup_cause =~ /^19$/)) {$VDL_status = 'ADCT'; $VDAC_status = 'DISCONNECT';}
									if ($dialstatus =~ /DISCONNECT/) {$VDL_status = 'ADCCAR'; $VDAC_status = 'ADCCAR';} # pre-carrier disconnect filter
									if ($dialstatus =~ /DNC/) {$VDL_status = 'DNCCAR'; $VDAC_status = 'DNCCAR';} # pre-carrier DNC filter
									}
								}
							else
								{
								if ($dialstatus =~ /BUSY/) {$VDL_status = 'AB'; $VDAC_status = 'BUSY';}
								if ($dialstatus =~ /CHANUNAVAIL/) {$VDL_status = 'ADC'; $VDAC_status = 'DISCONNECT';}
								if ($enhanced_disconnect_logging > 0)
									{
									if ($dialstatus =~ /CONGESTION/ && $hangup_cause =~ /^1$/) {$VDL_status = 'ADC'; $VDAC_status = 'DISCONNECT';}
									if ($dialstatus =~ /CONGESTION/ && $hangup_cause =~ /^19$|^21$|^34$|^38$/) {$VDL_status = 'ADCT'; $VDAC_status = 'DISCONNECT';}
									if ($dialstatus =~ /DISCONNECT/) {$VDL_status = 'ADCCAR'; $VDAC_status = 'ADCCAR';} # pre-carrier disconnect filter
									if ($dialstatus =~ /DNC/) {$VDL_status = 'DNCCAR'; $VDAC_status = 'DNCCAR';} # pre-carrier DNC filter
									}
								}
							}

						if (length($VDL_status) > 0) 
							{
							$stmtA = "UPDATE vicidial_list set status='$VDL_status' where lead_id = '$CIDlead_id';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$VDADaffected_rows = $dbhA->do($stmtA);
							if ($AGILOG) {$agi_string = "--    VDAD vicidial_list update: |$VDADaffected_rows|$CIDlead_id";   &agi_output;}

							$stmtA = "UPDATE vicidial_auto_calls set status='$VDAC_status' where callerid = '$callerid';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$VDACaffected_rows = $dbhA->do($stmtA);
							if ($AGILOG) {$agi_string = "--    VDAC update: |$VDACaffected_rows|$CIDlead_id";   &agi_output;}

								$Euniqueid=$uniqueid;
								$Euniqueid =~ s/\.\d+$//gi;
							$stmtA = "UPDATE vicidial_log FORCE INDEX(lead_id) set status='$VDL_status' where lead_id = '$CIDlead_id' and uniqueid LIKE \"$Euniqueid%\";";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$VDLaffected_rows = $dbhA->do($stmtA);
							if ($AGILOG) {$agi_string = "--    VDAD vicidial_log update: |$VDLaffected_rows|$uniqueid|$VDACuniqueid|";   &agi_output;}
							if ($VDLaffected_rows < 1)
								{
								$VD_alt_dial = 'NONE';
								$stmtA = "SELECT lead_id,callerid,campaign_id,alt_dial,stage,UNIX_TIMESTAMP(call_time),uniqueid,status,call_time,phone_code,phone_number,queue_position FROM vicidial_auto_calls where uniqueid = '$uniqueid' or callerid = '$callerid' limit 1;";
									if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								$rec_countCUSTDATA=0;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$VD_lead_id	=			$aryA[0];
									$VD_callerid	=		$aryA[1];
									$VD_campaign_id	=		$aryA[2];
									$VD_alt_dial	=		$aryA[3];
									$VD_stage =				$aryA[4];
									$VD_start_epoch =		$aryA[5];
									$VD_uniqueid =			$aryA[6];
									$VD_status =			$aryA[7];
									$VD_call_time =			$aryA[8];
									$VD_phone_code =		$aryA[9];
									$VD_phone_number =		$aryA[10];
									$VD_queue_position =	$aryA[11];
									$rec_countCUSTDATA++;
									}
								$sthA->finish();

								if ($sthArows > 0)
									{
									$called_count=0;
									$stmtA = "SELECT list_id,called_count FROM vicidial_list where lead_id='$VD_lead_id' limit 1;";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsVLd=$sthA->rows;
									if ($sthArowsVLd > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$VD_list_id	=		$aryA[0];
										$called_count =		$aryA[1];
										}
									$sthA->finish();

									$vl_commentsSQL = '';
									if ($callerid =~ /^M\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/)
										{$vl_commentsSQL=",comments='MANUAL'";}
									$stmtA = "INSERT INTO vicidial_log SET uniqueid='$uniqueid',lead_id='$VD_lead_id',list_id='$VD_list_id',status='$VDL_status',campaign_id='$VD_campaign_id',call_date='$VD_call_time',start_epoch='$VD_start_epoch',phone_code='$VD_phone_code',phone_number='$VD_phone_number',user='VDAD',processed='N',length_in_sec='0',end_epoch='$VD_start_epoch',alt_dial='$VD_alt_dial',called_count='$called_count' $vl_commentsSQL;";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$VDLIaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    VDAD vicidial_log insert: |$VDLIaffected_rows|$uniqueid|$CIDlead_id|$VDL_status|";   &agi_output;}

									$stmtA="INSERT IGNORE INTO vicidial_log_extended SET uniqueid='$uniqueid',server_ip='$VARserver_ip',call_date='$VD_call_time',lead_id='$VD_lead_id',caller_code='$VD_callerid',custom_call_id='' ON DUPLICATE KEY UPDATE server_ip='$VARserver_ip',call_date='$VD_call_time',lead_id='$VD_lead_id',caller_code='$VD_callerid';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$VDLXaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    VDAD vicidial_extended_log insert: |$VDLXaffected_rows|$uniqueid|$CIDlead_id|$VDL_status|";   &agi_output;}

									if ($SScall_quota_lead_ranking > 0)
										{
										### check to see if campaign call quotas enabled
										$VD_call_quota_lead_ranking='DISABLED';
										$stmtA="SELECT call_quota_lead_ranking FROM vicidial_campaigns where campaign_id='$VD_campaign_id';";
											if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$VD_call_quota_lead_ranking =	$aryA[0];
											}

										if ($VD_call_quota_lead_ranking !~ /^DISABLED$/i) 
											{
											$temp_status = $VDL_status;
											&call_quota_logging;
											}
										}
									}
								}
							if ( ($CPDfound > 0) && ($VDL_status !~ /AA/) )
								{
								$stmtA = "DELETE FROM vicidial_auto_calls where callerid = '$callerid';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
								$VDACDELaffected_rows = $dbhA->do($stmtA);
								if ($AGILOG) {$agi_string = "--    CPD VDAC deleted: |$VDACDELaffected_rows|$callerid";   &agi_output;}
								}
							}
						else
							{
							if ($AGILOG) {$agi_string = "--    VD_hangup Local STATUS EMPTY: |$PRI|$callerid|$dialstatus|$hangup_cause|$CPDfound|$VDL_status|";   &agi_output;}
							}
						}
					else
						{
						if ($AGILOG) {$agi_string = "--    VD_hangup Local DEBUG: |$PRI|$callerid|$dialstatus|$hangup_cause|$CPDfound|$VDL_status|";   &agi_output;}
						}

					if ($AGILOG) {$agi_string = "+++++ VDAD START LOCAL CHANNEL: EXITING- $priority";   &agi_output;}
					if ($priority > 2) {sleep(1);}
					}
				else
					{
					########## FIND AND DELETE vicidial_auto_calls ##########
					$VD_alt_dial = 'NONE';
					$stmtA = "SELECT lead_id,callerid,campaign_id,alt_dial,stage,UNIX_TIMESTAMP(call_time),uniqueid,status,call_time,phone_code,phone_number,queue_position,server_ip,agent_only FROM vicidial_auto_calls where uniqueid = '$uniqueid' or callerid = '$callerid' limit 1;";
						if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					$rec_countCUSTDATA=0;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VD_lead_id	=			$aryA[0];
						$VD_callerid	=		$aryA[1];
						$VD_campaign_id	=		$aryA[2];
						$VD_alt_dial	=		$aryA[3];
						$VD_stage =				$aryA[4];
						$VD_start_epoch =		$aryA[5];
						$VD_uniqueid =			$aryA[6];
						$VD_status =			$aryA[7];
						$VD_call_time =			$aryA[8];
						$VD_phone_code =		$aryA[9];
						$VD_phone_number =		$aryA[10];
						$VD_queue_position =	$aryA[11];
						$VD_server_ip =			$aryA[12];
						$VD_agent_only =		$aryA[13];
						$rec_countCUSTDATA++;
						}
					$sthA->finish();

					if (!$rec_countCUSTDATA)
						{
						if ($AGILOG) {$agi_string = "VD hangup: no VDAC record found: $uniqueid $calleridname";   &agi_output;}
						}
					else
						{
						$PC_count_rows=0;
						$PLC_count=0;
						$PCR_count=0;
						$PC_channel='';
						$stmtA = "SELECT channel from parked_channels where channel_group='$callerid';";
							if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$PC_count_rows=$sthA->rows;
						if ($PC_count_rows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$PC_channel = $aryA[0];
							}
						$sthA->finish();

						if ($PC_count_rows < 1)
							{
							$PC_server_ip='';
							# check parked_channels_recent table
							$secX = time();
							$Ptarget = ($secX - 7200);	# look back 2 hours
							($Psec,$Pmin,$Phour,$Pmday,$Pmon,$Pyear,$Pwday,$Pyday,$Pisdst) = localtime($Ptarget);
							$Pyear = ($Pyear + 1900);
							$Pmon++;
							if ($Pmon < 10) {$Pmon = "0$Pmon";}
							if ($Pmday < 10) {$Pmday = "0$Pmday";}
							if ($Phour < 10) {$Phour = "0$Phour";}
							if ($Pmin < 10) {$Pmin = "0$Pmin";}
							if ($Psec < 10) {$Psec = "0$Psec";}
								$PSQLdate = "$Pyear-$Pmon-$Pmday $Phour:$Pmin:$Psec";

							$stmtA = "SELECT channel,server_ip from parked_channels_recent where channel_group='$callerid' and park_end_time > \"$PSQLdate\" order by park_end_time desc limit 1;";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$PC_count_rows=$sthA->rows;
							if ($PC_count_rows > 0)
								{
								$PCR_count++;
								@aryA = $sthA->fetchrow_array;
								$PC_channel =	$aryA[0];
								$PC_server_ip = $aryA[1];
								}
							$sthA->finish();

							if ( (length($PC_server_ip) > 5) && ($PC_server_ip ne $VARserver_ip) )
								{
								$PC_count_rows=0;
								$PLC_count=1;
								if ($AGILOG) {$agi_string = "VD hangup: VDAC record found with park record OTHER SERVER: $channel($PC_count_rows|$PCR_count) $PC_channel $uniqueid $calleridname $VARserver_ip|$PC_server_ip";   &agi_output;}
								}
							else
								{
								$PC_count_rows=0;
								$PC_channel='';
								}
							}

						if ($PC_count_rows > 0)
							{
							sleep(1);

							$stmtA = "SELECT count(*) from live_channels where channel='$PC_channel';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$PLC_count_rows=$sthA->rows;
							if ($PLC_count_rows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$PLC_count = $aryA[0];
								}
							$sthA->finish();
							}
						if ($PLC_count > 0)
							{
							if ($AGILOG) {$agi_string = "VD hangup: VDAC record found with park record: $channel($PC_count_rows|$PCR_count) $PC_channel $uniqueid $calleridname $VD_server_ip";   &agi_output;}
							}
						else
							{
							$stmtA = "DELETE FROM vicidial_auto_calls where ( ( (status!='IVR') and (uniqueid='$uniqueid' or callerid = '$callerid') ) or ( (status='IVR') and (uniqueid='$uniqueid') ) ) order by call_time desc limit 1;";
							$VACaffected_rows = $dbhA->do($stmtA);
							if ($AGILOG) {$agi_string = "--    VDAC record deleted: |$VACaffected_rows|   |$VD_lead_id|$uniqueid|$VD_uniqueid|$VD_callerid|$callerid|$VD_status|$channel($VARserver_ip)|$PC_channel($VD_server_ip)|($PC_count_rows|$PCR_count)|$VD_alt_dial|";   &agi_output;}

							$stmtA = "UPDATE vicidial_live_agents SET ring_callerid='' where ring_callerid='$callerid';";
							$VLACaffected_rows = $dbhA->do($stmtA);

							#############################################
							##### START QUEUEMETRICS LOGGING LOOKUP #####
							$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_socket,queuemetrics_socket_url FROM system_settings;";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$enable_queuemetrics_logging =	$aryA[0];
								$queuemetrics_server_ip	=		$aryA[1];
								$queuemetrics_dbname =			$aryA[2];
								$queuemetrics_login=			$aryA[3];
								$queuemetrics_pass =			$aryA[4];
								$queuemetrics_log_id =			$aryA[5];
								$queuemetrics_socket =			$aryA[6];
								$queuemetrics_socket_url = 		$aryA[7];
								}
							$sthA->finish();
							##### END QUEUEMETRICS LOGGING LOOKUP #####
							###########################################
							if ( ($enable_queuemetrics_logging > 0) && ($VD_status !~ /IVR/) )
								{
								$data_four='';
								$VD_agent='NONE';
								$secX = time();
								$VD_call_length = ($secX - $VD_start_epoch);
								$VD_stage =~ s/.*-//gi;
								if ($VD_stage < 0.25) {$VD_stage=0;}

								$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
								 or die "Couldn't connect to database: " . DBI->errstr;

								if ($DBX) {print "CONNECTED TO DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

								$secX = time();
								$Rtarget = ($secX - 21600);	# look for VDCL entry within last 6 hours
								($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($Rtarget);
								$Ryear = ($Ryear + 1900);
								$Rmon++;
								if ($Rmon < 10) {$Rmon = "0$Rmon";}
								if ($Rmday < 10) {$Rmday = "0$Rmday";}
								if ($Rhour < 10) {$Rhour = "0$Rhour";}
								if ($Rmin < 10) {$Rmin = "0$Rmin";}
								if ($Rsec < 10) {$Rsec = "0$Rsec";}
									$RSQLdate = "$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

								### find original queue position of the call
								$queue_position=1;
								$stmtA = "SELECT queue_position,call_date FROM vicidial_closer_log where uniqueid='$unique_id' and lead_id='$CIDlead_id' and campaign_id='$VD_campaign_id' and call_date > \"$RSQLdate\" order by closecallid desc limit 1;";
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$queue_position =	$aryA[0];
									$VCLcall_date =		$aryA[1];
									}
								$sthA->finish();

								$stmtB = "SELECT agent,data4 from queue_log where call_id='$VD_callerid' and verb='CONNECT' order by time_id desc limit 1;";
								$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
								$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
								$sthBrows=$sthB->rows;
								$rec_count=0;
								if ($sthBrows > 0)
									{
									@aryB = $sthB->fetchrow_array;
									$VD_agent =		$aryB[0];
									$data_four =	$aryB[1];
									$rec_count++;
									}
								$sthB->finish();
								if ($AGILOG) {$agi_string = "$VD_agent|$data_four|$stmtB|";   &agi_output;}

								if ($rec_count < 1)
									{
									### find current number of calls in this queue to find position when channel hung up
									$current_position=1;
									$stmtA = "SELECT count(*) FROM vicidial_auto_calls where status = 'LIVE' and campaign_id='$VD_campaign_id' and call_time < '$VCLcall_date';";
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									if ($sthArows > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$current_position =	($aryA[0] + 1);
										}
									$sthA->finish();

									$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$VD_callerid',queue='$VD_campaign_id',agent='$VD_agent',verb='ABANDON',data1='$current_position',data2='$queue_position',data3='$VD_stage',serverid='$queuemetrics_log_id',data4='$data_four';";
									$Baffected_rows = $dbhB->do($stmtB);
									}
								else
									{
									$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$VD_callerid',queue='$VD_campaign_id',agent='$VD_agent',verb='COMPLETECALLER',data1='$VD_stage',data2='$VD_call_length',data3='$queue_position',serverid='$queuemetrics_log_id',data4='$data_four';";
									$Baffected_rows = $dbhB->do($stmtB);

									if ( ($queuemetrics_socket =~ /CONNECT_COMPLETE/) and (length($queuemetrics_socket_url) > 10) )
										{
										if ($queuemetrics_socket_url =~ /--A--/)
											{
											########## vicidial_list lead data ##########
											$stmtA = "SELECT vendor_lead_code,list_id,phone_code,phone_number,title,first_name,middle_initial,last_name,postal_code FROM vicidial_list where lead_id='$VD_lead_id' LIMIT 1;";
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$vendor_lead_code =		$aryA[0];
												$list_id =				$aryA[1];
												$phone_code =			$aryA[2];
												$phone_number =			$aryA[3];
												$title =				$aryA[4];
												$first_name =			$aryA[5];
												$middle_initial =		$aryA[6];
												$last_name =			$aryA[7];
												$postal_code =			$aryA[8];
												}
											$sthA->finish();

											$queuemetrics_socket_url =~ s/^VAR//gi;
											$queuemetrics_socket_url =~ s/--A--lead_id--B--/$VD_lead_id/gi;
											$queuemetrics_socket_url =~ s/--A--vendor_id--B--/$vendor_lead_code/gi;
											$queuemetrics_socket_url =~ s/--A--vendor_lead_code--B--/$vendor_lead_code/gi;
											$queuemetrics_socket_url =~ s/--A--list_id--B--/$list_id/gi;
											$queuemetrics_socket_url =~ s/--A--phone_number--B--/$phone_number/gi;
											$queuemetrics_socket_url =~ s/--A--title--B--/$title/gi;
											$queuemetrics_socket_url =~ s/--A--first_name--B--/$first_name/gi;
											$queuemetrics_socket_url =~ s/--A--middle_initial--B--/$middle_initial/gi;
											$queuemetrics_socket_url =~ s/--A--last_name--B--/$last_name/gi;
											$queuemetrics_socket_url =~ s/--A--postal_code--B--/$postal_code/gi;
											$queuemetrics_socket_url =~ s/ /+/gi;
											$queuemetrics_socket_url =~ s/&/\\&/gi;
											}
										$socket_send_data_begin='?';
										$socket_send_data = "time_id=$secX&call_id=$VD_callerid&queue=$VD_campaign_id&agent=$VD_agent&verb=COMPLETECALLER&data1=$VD_stage&data2=$VD_call_length&data3=$queue_position&data4=$data_four";
										if ($queuemetrics_socket_url =~ /\?/)
											{$socket_send_data_begin='&';}
										### send queue_log data to the queuemetrics_socket_url ###
										$compat_url = "$queuemetrics_socket_url$socket_send_data_begin$socket_send_data";
										$compat_url =~ s/ /+/gi;
										$compat_url =~ s/&/\\&/gi;

										$launch = $PATHhome . "/AST_send_URL.pl";
										$launch .= " --SYSLOG" if ($SYSLOG);
										$launch .= " --lead_id=" . $VD_lead_id;
										$launch .= " --phone_number=" . $VD_phone_number;
										$launch .= " --user=" . $VD_agent;
										$launch .= " --call_type=X";
										$launch .= " --campaign=" . $VD_campaign_id;
										$launch .= " --uniqueid=" . $uniqueid;
										$launch .= " --call_id=" . $VD_callerid;
										$launch .= " --list_id=" . $list_id;
										$launch .= " --alt_dial=UNKNOWN";
										$launch .= " --function=QM_SOCKET_SEND";
										$launch .= " --compat_url=" . $compat_url;

										system($launch . ' &');

										if ($AGILOG) {$agi_string = "$launch|";   &agi_output;}
										}
									}

								if ($AGILOG) {$agi_string = "|$stmtB|";   &agi_output;}

								$dbhB->disconnect();
								}


							$epc_countCUSTDATA=0;
							$VD_closecallid='';
							$VDL_update=0;
							$Euniqueid=$uniqueid;
							$Euniqueid =~ s/\.\d+$//gi;

							#############################################
							##### SYSTEM SETTINGS LOOKUP #####
							$stmtA = "SELECT enable_drop_lists,call_quota_lead_ranking,timeclock_end_of_day,call_limit_24hour FROM system_settings;";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$enable_drop_lists =			$aryA[0];
								$SScall_quota_lead_ranking =	$aryA[1];
								$timeclock_end_of_day =			$aryA[2];
								$SScall_limit_24hour =			$aryA[3];
								}
							$sthA->finish();
							##### END SYSTEM SETTINGS LOOKUP #####
							###########################################

							if ($calleridname !~ /^Y\d\d\d\d/)
								{
								########## FIND AND UPDATE vicidial_log ##########
								$stmtA = "SELECT start_epoch,status,user,term_reason,comments,alt_dial FROM vicidial_log FORCE INDEX(lead_id) where lead_id = '$VD_lead_id' and uniqueid LIKE \"$Euniqueid%\" limit 1;";
									if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$VD_start_epoch	=	$aryA[0];
									$VD_status =		$aryA[1];
									$VD_user =			$aryA[2];
									$VD_term_reason =	$aryA[3];
									$VD_comments =		$aryA[4];
									$VD_alt_dial_log =	$aryA[5];
									$epc_countCUSTDATA++;
									}
								$sthA->finish();
								}

							if ( (!$epc_countCUSTDATA) || ($calleridname =~ /^Y\d\d\d\d/) )
								{
								if ($AGILOG) {$agi_string = "no VDL record found: $uniqueid $calleridname $VD_lead_id $uniqueid $VD_uniqueid";   &agi_output;}

								$secX = time();
								$Rtarget = ($secX - 21600);	# look for VDCL entry within last 6 hours
								($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime($Rtarget);
								$Ryear = ($Ryear + 1900);
								$Rmon++;
								if ($Rmon < 10) {$Rmon = "0$Rmon";}
								if ($Rmday < 10) {$Rmday = "0$Rmday";}
								if ($Rhour < 10) {$Rhour = "0$Rhour";}
								if ($Rmin < 10) {$Rmin = "0$Rmin";}
								if ($Rsec < 10) {$Rsec = "0$Rsec";}
									$RSQLdate = "$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

								$stmtA = "SELECT start_epoch,status,closecallid,user,term_reason,length_in_sec,queue_seconds,comments FROM vicidial_closer_log where lead_id = '$VD_lead_id' and call_date > \"$RSQLdate\" order by closecallid desc limit 1;";
									if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
								$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								 $epc_countCUSTDATA=0;
								 $VD_closecallid='';
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$VD_start_epoch	=	$aryA[0];
									$VD_status =		$aryA[1];
									$VD_closecallid	=	$aryA[2];
									$VD_user =			$aryA[3];
									$VD_term_reason =	$aryA[4];
									$VD_length_in_sec =	$aryA[5];
									$VD_queue_seconds =	$aryA[6];
									$VD_comments =		$aryA[7];
									 $epc_countCUSTDATA++;
									}
								$sthA->finish();
								}
							if (!$epc_countCUSTDATA)
								{
								if ($AGILOG) {$agi_string = "no VDL or VDCL record found: $uniqueid $calleridname $VD_lead_id $uniqueid $VD_uniqueid |$VACaffected_rows|$VD_callerid|";   &agi_output;}

								### BEGIN if call answers but has not reached routing AGI, then log as a PDROP
								if ( ($callerid =~ /^V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) && ($VD_status =~ /SENT/) )
									{
									$called_count = 0;
									$stmtA = "SELECT list_id,called_count FROM vicidial_list where lead_id='$VD_lead_id' limit 1;";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArowsVLd=$sthA->rows;
									if ($sthArowsVLd > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$VD_list_id	=		$aryA[0];
										$called_count =		$aryA[1];
										}
									$sthA->finish();

									if (length($VDL_status) < 1) {$VDL_status='PDROP';}

									$stmtA = "UPDATE vicidial_list SET status='PDROP' where lead_id='$VD_lead_id';";
									$VLPDaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    PDROP vicidial_list update: |$VLPDaffected_rows|$uniqueid|$CIDlead_id|$VDL_status|";   &agi_output;}

									$stmtA = "INSERT INTO vicidial_log SET uniqueid='$uniqueid',lead_id='$VD_lead_id',list_id='$VD_list_id',status='PDROP',campaign_id='$VD_campaign_id',call_date='$VD_call_time',start_epoch='$VD_start_epoch',phone_code='$VD_phone_code',phone_number='$VD_phone_number',user='VDAD',processed='N',length_in_sec='0',end_epoch='$VD_start_epoch',alt_dial='$VD_alt_dial',called_count='$called_count';";
									$VDLPDaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    PDROP vicidial_log insert: |$VDLPDaffected_rows|$uniqueid|$CIDlead_id|$VDL_status|";   &agi_output;}

									$stmtA="INSERT IGNORE INTO vicidial_log_extended SET uniqueid='$uniqueid',server_ip='$VARserver_ip',call_date='$VD_call_time',lead_id='$VD_lead_id',caller_code='$VD_callerid',custom_call_id='' ON DUPLICATE KEY UPDATE server_ip='$VARserver_ip',call_date='$VD_call_time',lead_id='$VD_lead_id',caller_code='$VD_callerid';";
									$VDLXPDaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    PDROP vicidial_extended_log insert: |$VDLXPDaffected_rows|$uniqueid|$CIDlead_id|$VDL_status|";   &agi_output;}

									if ($enable_drop_lists > 1) 
										{
										$stmtA="INSERT IGNORE INTO vicidial_drop_log SET uniqueid='$uniqueid',server_ip='$VARserver_ip',drop_date=NOW(),lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='PDROP',phone_code='$VD_phone_code',phone_number='$VD_phone_number';";
										$VDDLaffected_rows = $dbhA->do($stmtA);
										if ($AGILOG) {$agi_string = "--    PDROP vicidial_drop_log insert: |$VDDLaffected_rows|$uniqueid|$VD_lead_id|$VDL_status|";   &agi_output;}
										}
									}
								### END if call answers but has not reached routing AGI, then log as a PDROP
								}
							else
								{
								$VD_seconds = ($now_date_epoch - $VD_start_epoch);

								$SQL_status='';
								if ( ($VD_status =~ /^NA$|^NEW$|^QUEUE$|^XFER$/) && ($VD_comments !~ /REMOTE/) )
									{
									if ( ($VD_term_reason !~ /AGENT|CALLER|QUEUETIMEOUT/) && ( ($VD_user =~ /VDAD|VDCL/) || (length($VD_user) < 1) ) )
										{$VDLSQL_term_reason = "term_reason='ABANDON',";}
									else
										{
										if ($VD_term_reason !~ /AGENT|CALLER|QUEUETIMEOUT/)
											{$VDLSQL_term_reason = "term_reason='CALLER',";}
										else
											{$VDLSQL_term_reason = '';}
										}
									$SQL_status = "status='DROP',$VDLSQL_term_reason";
									if (length($VDL_status) < 1) {$VDL_status='DROP';}

									########## FIND AND UPDATE vicidial_list ##########
									$stmtA = "UPDATE vicidial_list set status='DROP' where lead_id = '$VD_lead_id';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$affected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    VDAD vicidial_list update: |$affected_rows|$VD_lead_id";   &agi_output;}
									}
								else 
									{
									$SQL_status = "term_reason='CALLER',";
									}

								if ($calleridname !~ /^Y\d\d\d\d/)
									{
									$VDL_update=1;
									$stmtA = "UPDATE vicidial_log FORCE INDEX(lead_id) set $SQL_status end_epoch='$now_date_epoch',length_in_sec='$VD_seconds' where lead_id = '$VD_lead_id' and uniqueid LIKE \"$Euniqueid%\";";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$VLaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    VDAD vicidial_log update: |$VLaffected_rows|$uniqueid|$VD_status|";   &agi_output;}
									}



								########## UPDATE vicidial_closer_log ##########
								$cslr_reset_check=0;
								if ( (length($VD_closecallid) < 1) || ($VDL_update > 0) )
									{
									if ($AGILOG) {$agi_string = "no VDCL record found: $uniqueid|$calleridname|$VD_lead_id|$uniqueid|$VD_uniqueid|$VDL_update|";   &agi_output;}
									}
								else
									{
									if ($VD_status =~ /^DONE$|^INCALL$|^XFER$/)
										{$VDCLSQL_update = "term_reason='CALLER',";}
									else
										{
										if ( ($VD_term_reason !~ /AGENT|CALLER|QUEUETIMEOUT|AFTERHOURS|HOLDRECALLXFER|HOLDTIME|NOAGENT/) && ( ($VD_user =~ /VDAD|VDCL/) || (length($VD_user) < 1) ) )
											{
											$VDCLSQL_term_reason = "term_reason='ABANDON',";
											$cslr_reset_check=1;
											}
										else
											{
											if ($VD_term_reason !~ /AGENT|CALLER|QUEUETIMEOUT|AFTERHOURS|HOLDRECALLXFER|HOLDTIME|NOAGENT/)
												{$VDCLSQL_term_reason = "term_reason='CALLER',";}
											else
												{$VDCLSQL_term_reason = '';}
											}
										if ($VD_status =~ /QUEUE/)
											{
											$VDCLSQL_status = "status='DROP',";
											$VDCLSQL_queue_seconds = "queue_seconds='$VD_seconds',";
											if (length($VDL_status) < 1) {$VDL_status='DROP';}
											}
										else
											{
											$VDCLSQL_status = '';
											$VDCLSQL_queue_seconds = '';
											}

										$VDCLSQL_update = "$VDCLSQL_status$VDCLSQL_term_reason$VDCLSQL_queue_seconds";
										}

									# set the log-user to the agentdirect destination user, if set
									$LOGuserSQL='';
									if ( ($VD_campaign_id =~ /AGENTDIRECT/i) && (length($VD_agent_only) > 1) )
										{$LOGuserSQL = ",user='$VD_agent_only'";}

									$VD_seconds = ($now_date_epoch - $VD_start_epoch);
									$stmtA = "UPDATE vicidial_closer_log set $VDCLSQL_update end_epoch='$now_date_epoch',length_in_sec='$VD_seconds'$LOGuserSQL where closecallid = '$VD_closecallid';";
										if ($AGILOG) {$agi_string = "|$VDCLSQL_update|$VD_status|$VD_length_in_sec|$VD_term_reason|$VD_queue_seconds|$VD_campaign_id|$VD_agent_only|\n|$stmtA|";   &agi_output;}
									$affected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    VDCL update: |$affected_rows|$uniqueid|$VD_closecallid|";   &agi_output;}

									if ($cslr_reset_check > 0) 
										{
										$drop_lead_reset='';
										$stmtA = "SELECT drop_lead_reset FROM vicidial_inbound_groups where group_id='$VD_campaign_id';";
											if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArowsVIG=$sthA->rows;
										if ($sthArowsVIG > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$drop_lead_reset = 	$aryA[0];
											}
										$sthA->finish();

										if ($drop_lead_reset =~ /Y/)
											{
											$stmtA = "UPDATE vicidial_list set called_since_last_reset='N' where lead_id = '$VD_lead_id';";
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$affected_rows = $dbhA->do($stmtA);
											if ($AGILOG) {$agi_string = "--    VDAD vicidial_list update CSLR: |$affected_rows|$VD_lead_id";   &agi_output;}
											}
										}
									}

								if ( ( ( ($calleridname =~ /^Y\d\d\d\d/) && ($enable_drop_lists > 0) ) || ( ($calleridname !~ /^Y\d\d\d\d/) && ($enable_drop_lists > 1) ) ) && ( ($VD_user =~ /VDAD|VDCL/) || (length($VD_user) < 1) || ($VD_status =~ /QUEUE/) ) )
									{
									$stmtA="INSERT IGNORE INTO vicidial_drop_log SET uniqueid='$uniqueid',server_ip='$VARserver_ip',drop_date=NOW(),lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='DROP',phone_code='$VD_phone_code',phone_number='$VD_phone_number';";
									$VDDLaffected_rows = $dbhA->do($stmtA);
									if ($AGILOG) {$agi_string = "--    DROP vicidial_drop_log insert: |$VDDLaffected_rows|$uniqueid|$VD_lead_id|$VD_campaign_id|";   &agi_output;}
									}
								}

							### check to see if campaign has alt_dial or call quotas enabled
							$VD_auto_alt_dial = 'NONE';
							$VD_auto_alt_dial_statuses='';
							$VD_call_quota_lead_ranking='DISABLED';
							$stmtA="SELECT auto_alt_dial,auto_alt_dial_statuses,use_internal_dnc,use_campaign_dnc,use_other_campaign_dnc,call_quota_lead_ranking,call_limit_24hour_method,call_limit_24hour_scope,call_limit_24hour,call_limit_24hour_override,auto_alt_threshold FROM vicidial_campaigns where campaign_id='$VD_campaign_id';";
								if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							 $epc_countCAMPDATA=0;
							while ($sthArows > $epc_countCAMPDATA)
								{
								@aryA = $sthA->fetchrow_array;
								$VD_auto_alt_dial	=				$aryA[0];
								$VD_auto_alt_dial_statuses	=		$aryA[1];
								$VD_use_internal_dnc =				$aryA[2];
								$VD_use_campaign_dnc =				$aryA[3];
								$VD_use_other_campaign_dnc =		$aryA[4];
								$VD_call_quota_lead_ranking =		$aryA[5];
								$VD_call_limit_24hour_method =		$aryA[6];
								$VD_call_limit_24hour_scope =		$aryA[7];
								$VD_call_limit_24hour =				$aryA[8];
								$VD_call_limit_24hour_override =	$aryA[9];
								$CLauto_alt_threshold =				$aryA[10];
								$epc_countCAMPDATA++;
								}

							$LISTauto_alt_threshold=-1;
							$stmtA="SELECT auto_alt_threshold from vicidial_lists where list_id='$VD_list_id' limit 1;";
								if ($DB) {$event_string = "|$stmtA|";   &event_logger;}
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArowsVLL=$sthA->rows;
							if ($sthArowsVLL > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$LISTauto_alt_threshold = 	$aryA[0];
								}
							$sthA->finish();

							$temp_auto_alt_threshold = $CLauto_alt_threshold;
							if ($LISTauto_alt_threshold > -1 ) {$temp_auto_alt_threshold = $LISTauto_alt_threshold;}
							$auto_alt_lead_disabled=0;
							if ( ($temp_auto_alt_threshold > 0) && ($called_count >= $temp_auto_alt_threshold) ) 
								{
								$auto_alt_lead_disabled=1;
								if ($ADB > 0) {$aad_string = "ALT-20: $CLlead_id|$VD_alt_dial|$VD_auto_alt_dial|$CLauto_alt_threshold|$LISTauto_alt_threshold|($called_count <> $temp_auto_alt_threshold)|auto_alt_lead_disabled: $auto_alt_lead_disabled|";   &aad_output;}
								}

							##### BEGIN Call Quota Lead Ranking logging #####
							if ( ($SScall_quota_lead_ranking > 0) && ($VD_call_quota_lead_ranking !~ /^DISABLED$/i) )
								{
								$temp_status=$VDL_status;
								if ( (length($VDL_status) < 1) || ($VDL_status =~ /^LIVE/i) ) {$temp_status=$VD_status;}
								if ($temp_status =~ /^LIVE/i) {$temp_status='PU';}
								&call_quota_logging;
								}
							##### END Call Quota Lead Ranking logging #####


							##### BEGIN AUTO ALT PHONE DIAL SECTION #####
							$sthA->finish();
				#			if ($AGILOG) {$agi_string = "AUTO-ALT TEST: |$VD_status|$VDL_status|$VD_auto_alt_dial_statuses|$VD_auto_alt_dial|";   &agi_output;}
							if ($ADB > 0) {$aad_string = "ALT-21: $VD_lead_id|$VD_alt_dial|$VD_status|$VDL_status|$VD_auto_alt_dial_statuses|$VD_auto_alt_dial|";   &aad_output;}
							if ( ($VD_auto_alt_dial_statuses =~ / $VD_status | $VDL_status /) && ($auto_alt_lead_disabled < 1) )
								{
								$alt_skip_reason='';   $addr3_skip_reason='';
								if ($AGILOG) {$agi_string = "AUTO-ALT MATCH: |$VD_status|$VDL_status|$VD_auto_alt_dial_statuses|$VD_auto_alt_dial|$VD_alt_dial|$VD_alt_dial_log|";   &agi_output;}
								if ($ADB > 0) {$aad_string = "ALT-22: $VD_lead_id|Alt-Dial Match|";   &aad_output;}
								if ( ($VD_auto_alt_dial =~ /(ALT_ONLY|ALT_AND_ADDR3|ALT_AND_EXTENDED)/) && ($VD_alt_dial =~ /NONE|MAIN/) )
									{
									$alt_dial_skip=0;
									$VD_alt_phone='';
									$stmtA="SELECT alt_phone,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$VD_lead_id';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									$epc_countCAMPDATA=0;
									while ($sthArows > $epc_countCAMPDATA)
										{
										@aryA = $sthA->fetchrow_array;
										$VD_alt_phone =			$aryA[0];
										$VD_alt_phone =~ s/\D//gi;
										$VD_gmt_offset_now =	$aryA[1];
										$VD_state =				$aryA[2];
										$VD_list_id =			$aryA[3];
										$VD_phone_code =		$aryA[4];
										$VD_postal_code =		$aryA[5];
										$epc_countCAMPDATA++;
										}
									$sthA->finish();
									if ($ADB > 0) {$aad_string = "ALT-23: $VD_lead_id|ALT-PHONE: $VD_alt_phone|";   &aad_output;}
									if (length($VD_alt_phone)>5)
										{
										if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
											{
											if ($VD_use_internal_dnc =~ /AREACODE/)
												{
												$alt_areacode = substr($VD_alt_phone, 0, 3);
												$alt_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_alt_phone','$alt_areacode');";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_alt_phone';";}
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	$aryA[0];
												}
											$sthA->finish();
											}
										else {$VD_alt_dnc_count=0;}
										if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
											{
											$temp_campaign_id = $VD_campaign_id;
											if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
											if ($VD_use_campaign_dnc =~ /AREACODE/)
												{
												$alt_areacode = substr($VD_alt_phone, 0, 3);
												$alt_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_alt_phone','$alt_areacode') and campaign_id='$temp_campaign_id';";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_alt_phone' and campaign_id='$temp_campaign_id';";}
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
												}
											$sthA->finish();
											}
										if ($VD_alt_dnc_count < 1)
											{
											$passed_24hour_call_count=1;
											if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
												{
												$temp_24hour_phone =		$VD_alt_phone;
												$temp_24hour_phone_code =	$VD_phone_code;
												$temp_24hour_state =		$VD_state;
												$temp_24hour_postal_code =	$VD_postal_code;
												if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$VD_lead_id|\n";}
												&check_24hour_call_count;
												}
											if ($passed_24hour_call_count > 0) 
												{
												$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='READY',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ALT',user='',priority='25',source='A';";
												$affected_rows = $dbhA->do($stmtA);
												if ($AGILOG) {$agi_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|";   &agi_output;}
												if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_alt_phone|$VD_campaign_id|ALT|25|hopper insert|";   &aad_output;}
												}
											else
												{$alt_dial_skip=1;   $alt_skip_reason='24-hour call count limit failed';}
											}
										else
											{$alt_dial_skip=1;   $alt_skip_reason='DNC check failed';}
										}
									else
										{$alt_dial_skip=1;   $alt_skip_reason='ALT phone invalid';}
									if ($alt_dial_skip > 0)
										{
										if ($ADB > 0) {$aad_string = "ALT-24: $VD_lead_id|$VD_alt_dial|ALT-SKIP: $alt_skip_reason|";   &aad_output;}
										$VD_alt_dial='ALT';
										if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_alt_phone|$VD_campaign_id|ALT|0|hopper skip|$alt_skip_reason|";   &aad_output;}
										}
									}
									if ($ADB > 0) {$aad_string = "ALT-25: $VD_lead_id|$VD_alt_dial|";   &aad_output;}
								if ( ( ($VD_auto_alt_dial =~ /(ADDR3_ONLY)/) && ($VD_alt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /(ALT_AND_ADDR3)/) && ($VD_alt_dial =~ /ALT/) ) )
									{
									$addr3_dial_skip=0;
									$VD_address3='';
									$stmtA="SELECT address3,gmt_offset_now,state,list_id,phone_code,postal_code FROM vicidial_list where lead_id='$VD_lead_id';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									 $epc_countCAMPDATA=0;
									while ($sthArows > $epc_countCAMPDATA)
										{
										@aryA = $sthA->fetchrow_array;
										$VD_address3 =			$aryA[0];
										$VD_address3 =~ s/\D//gi;
										$VD_gmt_offset_now =	$aryA[1];
										$VD_state =				$aryA[2];
										$VD_list_id =			$aryA[3];
										$VD_phone_code =		$aryA[4];
										$VD_postal_code =		$aryA[5];
										$epc_countCAMPDATA++;
										}
									$sthA->finish();
									if ($ADB > 0) {$aad_string = "ALT-26: $VD_lead_id|$VD_alt_dial|ADDR3-PHONE: $VD_address3|";   &aad_output;}
									if (length($VD_address3)>5)
										{
										if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
											{
											if ($VD_use_internal_dnc =~ /AREACODE/)
												{
												$addr3_areacode = substr($VD_address3, 0, 3);
												$addr3_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_address3','$addr3_areacode');";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_address3';";}
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	$aryA[0];
												}
											$sthA->finish();
											}
										else {$VD_alt_dnc_count=0;}
										if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
											{
											$temp_campaign_id = $VD_campaign_id;
											if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
											if ($VD_use_campaign_dnc =~ /AREACODE/)
												{
												$addr3_areacode = substr($VD_address3, 0, 3);
												$addr3_areacode .= "XXXXXXX";
												$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_address3','$alt_areacode') and campaign_id='$temp_campaign_id';";
												}
											else
												{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_address3' and campaign_id='$temp_campaign_id';";}
												if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
											$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
											$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
											$sthArows=$sthA->rows;
											if ($sthArows > 0)
												{
												@aryA = $sthA->fetchrow_array;
												$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
												}
											$sthA->finish();
											}
										if ($VD_alt_dnc_count < 1)
											{
											$passed_24hour_call_count=1;
											if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
												{
												$temp_24hour_phone =		$VD_address3;
												$temp_24hour_phone_code =	$VD_phone_code;
												$temp_24hour_state =		$VD_state;
												$temp_24hour_postal_code =	$VD_postal_code;
												if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$VD_lead_id|\n";}
												&check_24hour_call_count;
												}
											if ($passed_24hour_call_count > 0) 
												{
												$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='READY',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='ADDR3',user='',priority='20',source='A';";
												$affected_rows = $dbhA->do($stmtA);
												if ($AGILOG) {$agi_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|";   &agi_output;}
												if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_address3|$VD_campaign_id|ADDR3|20|hopper insert|";   &aad_output;}
												}
											else
												{$addr3_dial_skip=1;   $addr3_skip_reason='24-hour call count limit failed';}
											}
										else
											{$addr3_dial_skip=1;   $addr3_skip_reason='DNC check failed';}
										}
									else
										{$addr3_dial_skip=1;   $addr3_skip_reason='ADDR3 phone invalid';}
									if ($addr3_dial_skip > 0)
										{
										if ($ADB > 0) {$aad_string = "ALT-27: $VD_lead_id|$VD_alt_dial|ADDR3-SKIP: $addr3_skip_reason|";   &aad_output;}
										$VD_alt_dial='ADDR3';
										if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_address3|$VD_campaign_id|ADDR3|0|hopper skip|$addr3_skip_reason|";   &aad_output;}
										}
									}
								if ($ADB > 0) {$aad_string = "ALT-28: $VD_lead_id|$VD_alt_dial|";   &aad_output;}
								if ( ( ($VD_auto_alt_dial =~ /(EXTENDED_ONLY)/) && ($VD_alt_dial =~ /NONE|MAIN/) ) || ( ($VD_auto_alt_dial =~ /(ALT_AND_EXTENDED)/) && ($VD_alt_dial =~ /ALT/) ) || ( ($VD_auto_alt_dial =~ /ADDR3_AND_EXTENDED|ALT_AND_ADDR3_AND_EXTENDED/) && ($VD_alt_dial =~ /ADDR3/) ) || ( ($VD_auto_alt_dial =~ /(EXTENDED)/) && ($VD_alt_dial =~ /X/) && ($VD_alt_dial !~ /XLAST/) ) )
									{
									if ($VD_alt_dial =~ /ADDR3/) {$Xlast=0;}
									else
										{$Xlast = $VD_alt_dial;}
									$Xlast =~ s/\D//gi;
									if (length($Xlast)<1)
										{$Xlast=0;}
									$VD_altdialx='';
									$stmtA="SELECT gmt_offset_now,state,list_id,postal_code FROM vicidial_list where lead_id='$VD_lead_id';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									$epc_countCAMPDATA=0;
									while ($sthArows > $epc_countCAMPDATA)
										{
										@aryA = $sthA->fetchrow_array;
										$VD_gmt_offset_now =	$aryA[0];
										$VD_state =				$aryA[1];
										$VD_list_id =			$aryA[2];
										$VD_postal_code =		$aryA[3];
										$epc_countCAMPDATA++;
										}
									$sthA->finish();
									$alt_dial_phones_count=0;
									$stmtA="SELECT count(*) FROM vicidial_list_alt_phones where lead_id='$VD_lead_id';";
										if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
									$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
									$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
									$sthArows=$sthA->rows;
									if ($sthArows > 0)
										{
										@aryA = $sthA->fetchrow_array;
										$alt_dial_phones_count = $aryA[0];
										}
									$sthA->finish();
									if ($ADB > 0) {$aad_string = "ALT-29: $VD_lead_id|$VD_alt_dial|$Xlast|$alt_dial_phones_count|";   &aad_output;}

									while ( ($alt_dial_phones_count > 0) && ($alt_dial_phones_count > $Xlast) )
										{
										$Xlast++;
										$stmtA="SELECT alt_phone_id,phone_number,active,phone_code FROM vicidial_list_alt_phones where lead_id='$VD_lead_id' and alt_phone_count='$Xlast';";
											if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
										$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
										$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
										$sthArows=$sthA->rows;
										if ($sthArows > 0)
											{
											@aryA = $sthA->fetchrow_array;
											$VD_altdial_id =			$aryA[0];
											$VD_altdial_phone = 		$aryA[1];
											$VD_altdial_active = 		$aryA[2];
											$VD_altdial_phone_code = 	$aryA[3];
											}
										else
											{$Xlast=99999;}
										$sthA->finish();
										if ($ADB > 0) {$aad_string = "ALT-30: $VD_lead_id|$VD_alt_dial|$Xlast|";   &aad_output;}

										if ($VD_altdial_active =~ /Y/)
											{
											$DNCC=0;
											$DNCL=0;
											if ( ($VD_use_internal_dnc =~ /Y/) || ($VD_use_internal_dnc =~ /AREACODE/) )
												{
												if ($VD_use_internal_dnc =~ /AREACODE/)
													{
													$ad_areacode = substr($VD_altdial_phone, 0, 3);
													$ad_areacode .= "XXXXXXX";
													$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number IN('$VD_altdial_phone','$ad_areacode');";
													}
												else
													{$stmtA="SELECT count(*) FROM vicidial_dnc where phone_number='$VD_altdial_phone';";}
													if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$VD_alt_dnc_count =		$aryA[0];
													$DNCL =					$aryA[0];
													}
												$sthA->finish();
												}
											else {$VD_alt_dnc_count=0;}
											if ( ($VD_use_campaign_dnc =~ /Y/) || ($VD_use_campaign_dnc =~ /AREACODE/) )
												{
												$temp_campaign_id = $VD_campaign_id;
												if (length($VD_use_other_campaign_dnc) > 0) {$temp_campaign_id = $VD_use_other_campaign_dnc;}
												if ($VD_use_campaign_dnc =~ /AREACODE/)
													{
													$ap_areacode = substr($VD_altdial_phone, 0, 3);
													$ap_areacode .= "XXXXXXX";
													$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number IN('$VD_altdial_phone','$ap_areacode') and campaign_id='$temp_campaign_id';";
													}
												else
													{$stmtA="SELECT count(*) FROM vicidial_campaign_dnc where phone_number='$VD_altdial_phone' and campaign_id='$temp_campaign_id';";}
													if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
												$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
												$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
												$sthArows=$sthA->rows;
												if ($sthArows > 0)
													{
													@aryA = $sthA->fetchrow_array;
													$VD_alt_dnc_count =	($VD_alt_dnc_count + $aryA[0]);
													$DNCC =					$aryA[0];
													}
												$sthA->finish();
												}
											if ($VD_alt_dnc_count < 1)
												{
												if ($alt_dial_phones_count eq '$Xlast') 
													{$Xlast = 'LAST';}
												$passed_24hour_call_count=1;
												if ( ($SScall_limit_24hour > 0) && ($VD_call_limit_24hour_method =~ /PHONE_NUMBER|LEAD/) )
													{
													$temp_24hour_phone =		$VD_altdial_phone;
													$temp_24hour_phone_code =	$VD_altdial_phone_code;
													$temp_24hour_state =		$VD_state;
													$temp_24hour_postal_code =	$VD_postal_code;
													if ($DB > 0) {print "24-Hour Call Count Check: $SScall_limit_24hour|$VD_call_limit_24hour_method|$VD_lead_id|\n";}
													&check_24hour_call_count;
													}
												if ($passed_24hour_call_count > 0) 
													{
													$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='READY',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15',source='A';";
													$affected_rows = $dbhA->do($stmtA);
													if ($AGILOG) {$agi_string = "--    VDH record inserted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &agi_output;}
													if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_altdial_phone|$VD_campaign_id|X$Xlast|15|hopper insert|";   &aad_output;}
													if ($ADB > 0) {$aad_string = "ALT-31: $VD_lead_id|$VD_alt_dial|X$Xlast|";   &aad_output;}
													$Xlast=99999;
													$DNC_hopper_trigger=0;
													}
												else
													{$DNC_hopper_trigger=1;}
												}
											else
												{$DNC_hopper_trigger=1;}
											if ($DNC_hopper_trigger > 0)
												{
												if ( ( ($VD_auto_alt_dial_statuses =~ / DNCC /) && ($DNCC > 0) ) || ( ($VD_auto_alt_dial_statuses =~ / DNCL /) && ($DNCL > 0) ) || ( ($auto_alt_dial_statuses[$i] =~ / TFHCCL /) && ($TFHCCL > 0) ) )
													{
													if ($ADB > 0) {$aad_string = "ALT-32: $VD_lead_id|$VD_alt_dial|$Xlast|$alt_dial_phones_count|";   &aad_output;}
													if ($alt_dial_phones_count eq '$Xlast') 
														{$Xlast = 'LAST';}
													$stmtA = "INSERT INTO vicidial_hopper SET lead_id='$VD_lead_id',campaign_id='$VD_campaign_id',status='DNC',list_id='$VD_list_id',gmt_offset_now='$VD_gmt_offset_now',state='$VD_state',alt_dial='X$Xlast',user='',priority='15',source='A';";
													$affected_rows = $dbhA->do($stmtA);
													if ($AGILOG) {$agi_string = "--    VDH record DNC inserted: |$affected_rows|   |$stmtA|X$Xlast|$VD_altdial_id|";   &agi_output;}
													$Xlast=99999;
													if ($AGILOG) {$agi_string = "--    VDH alt dial inserting DNC|X$Xlast|$VD_altdial_phone|";   &agi_output;}
													if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_altdial_phone|$VD_campaign_id|X$Xlast|15|hopper DNC insert|";   &aad_output;}
													if ($ADB > 0) {$aad_string = "ALT-33: $VD_lead_id|$VD_alt_dial|$Xlast|DNC|";   &aad_output;}
													}
												else
													{
													if ($AGILOG) {$agi_string = "--    VDH alt dial not-inserting DNC|X$Xlast|$VD_altdial_phone|";   &agi_output;}
													if ($AGILOG) {$aad_string = "$VD_lead_id|$VD_altdial_phone|$VD_campaign_id|X$Xlast|15|hopper DNC skip|";   &aad_output;}
													if ($ADB > 0) {$aad_string = "ALT-34: $VD_lead_id|$VD_alt_dial|$Xlast|DNC|";   &aad_output;}
													}
												}
											}
										}
									}
								}
							##### END AUTO ALT PHONE DIAL SECTION #####
							}
						}
					}
				}
			$dbhA->disconnect();
			}
		}
	###################################################################
	##### END call_log process ########################################
	###################################################################





	###################################################################
	##### START VD_hangup process #####################################
	###################################################################
	if ($process =~ /^VD_hangup/)
	{
	$nothing=0;
	}
	###################################################################
	##### END VD_hangup process #######################################
	###################################################################


	}


VDfastAGI->run(
				port=>4577,
				user=>'root',
				group=>'root',
				min_servers=>$VARfastagi_log_min_servers,
				max_servers=>$VARfastagi_log_max_servers,
				min_spare_servers=>$VARfastagi_log_min_spare_servers,
				max_spare_servers=>$VARfastagi_log_max_spare_servers,
				max_requests=>$VARfastagi_log_max_requests,
				check_for_dead=>$VARfastagi_log_checkfordead,
				check_for_waiting=>$VARfastagi_log_checkforwait,
				log_file=>$childLOGfile,
				log_level=>$log_level
				);
exit;



##### BEGIN Call Quota Lead Ranking logging #####
sub call_quota_logging
	{
	# Gather settings container for Call Quota Lead Ranking
	$CQcontainer_entry='';
	$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$VD_call_quota_lead_ranking';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$CQcontainer_entry = $aryA[0];
		$CQcontainer_entry =~ s/\\//gi;
		}
	$sthA->finish();

	# Define variables for Call Quota settings
	$session_one='';
	$session_two='';
	$session_three='';
	$session_four='';
	$session_five='';
	$session_six='';
	$settings_session_score=0;
	$zero_rank_after_call=0;

	if (length($CQcontainer_entry) > 5) 
		{
		@container_lines = split(/\n/,$CQcontainer_entry);
		$c=0;
		foreach(@container_lines)
			{
			$container_lines[$c] =~ s/;.*|\r|\t| //gi;
			if (length($container_lines[$c]) > 5)
				{
				# define core settings
				if ($container_lines[$c] =~ /^zero_rank_after_call/i)
					{
					$container_lines[$c] =~ s/zero_rank_after_call=>//gi;
					if ( ($container_lines[$c] >= 0) && ($container_lines[$c] <= 1) ) 
						{
						$zero_rank_after_call = $container_lines[$c];
						}
					}
				# define sessions
				if ($container_lines[$c] =~ /^session_one/i)
					{
					$session_one_valid=0; $session_one_start=''; $session_one_end='';
					$session_one = $container_lines[$c];
					$session_one =~ s/session_one=>//gi;
					if ( (length($session_one) > 0) && (length($session_one) <= 9) && ($session_one =~ /,/) ) 
						{
						@session_oneARY = split(/,/,$session_one);
						$session_one_start = $session_oneARY[0];
						$session_one_end = $session_oneARY[1];
						if ( (length($session_one_start) >= 4) && (length($session_one_end) >= 4) && ($session_one_start < $session_one_end) && ($session_one_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_one_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_two/i)
					{
					$session_two_valid=0; $session_two_start=''; $session_two_end='';
					$session_two = $container_lines[$c];
					$session_two =~ s/session_two=>//gi;
					if ( (length($session_two) > 0) && (length($session_two) <= 9) && ($session_two =~ /,/) ) 
						{
						@session_twoARY = split(/,/,$session_two);
						$session_two_start = $session_twoARY[0];
						$session_two_end = $session_twoARY[1];
						if ( (length($session_two_start) >= 4) && (length($session_two_end) >= 4) && ($session_one_valid > 0) && ($session_one_end <= $session_two_start) && ($session_two_start < $session_two_end) && ($session_two_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_two_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_three/i)
					{
					$session_three_valid=0; $session_three_start=''; $session_three_end='';
					$session_three = $container_lines[$c];
					$session_three =~ s/session_three=>//gi;
					if ( (length($session_three) > 0) && (length($session_three) <= 9) && ($session_three =~ /,/) ) 
						{
						@session_threeARY = split(/,/,$session_three);
						$session_three_start = $session_threeARY[0];
						$session_three_end = $session_threeARY[1];
						if ( (length($session_three_start) >= 4) && (length($session_three_end) >= 4) && ($session_two_valid > 0) && ($session_two_end <= $session_three_start) && ($session_three_start < $session_three_end) && ($session_three_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_three_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_four/i)
					{
					$session_four_valid=0; $session_four_start=''; $session_four_end='';
					$session_four = $container_lines[$c];
					$session_four =~ s/session_four=>//gi;
					if ( (length($session_four) > 0) && (length($session_four) <= 9) && ($session_four =~ /,/) ) 
						{
						@session_fourARY = split(/,/,$session_four);
						$session_four_start = $session_fourARY[0];
						$session_four_end = $session_fourARY[1];
						if ( (length($session_four_start) >= 4) && (length($session_four_end) >= 4) && ($session_three_valid > 0) && ($session_three_end <= $session_four_start) && ($session_four_start < $session_four_end) && ($session_four_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_four_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_five/i)
					{
					$session_five_valid=0; $session_five_start=''; $session_five_end='';
					$session_five = $container_lines[$c];
					$session_five =~ s/session_five=>//gi;
					if ( (length($session_five) > 0) && (length($session_five) <= 9) && ($session_five =~ /,/) ) 
						{
						@session_fiveARY = split(/,/,$session_five);
						$session_five_start = $session_fiveARY[0];
						$session_five_end = $session_fiveARY[1];
						if ( (length($session_five_start) >= 4) && (length($session_five_end) >= 4) && ($session_four_valid > 0) && ($session_four_end <= $session_five_start) && ($session_five_start < $session_five_end) && ($session_five_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_five_valid++;
							}
						}
					}
				if ($container_lines[$c] =~ /^session_six/i)
					{
					$session_six_valid=0; $session_six_start=''; $session_six_end='';
					$session_six = $container_lines[$c];
					$session_six =~ s/session_six=>//gi;
					if ( (length($session_six) > 0) && (length($session_six) <= 9) && ($session_six =~ /,/) ) 
						{
						@session_sixARY = split(/,/,$session_six);
						$session_six_start = $session_sixARY[0];
						$session_six_end = $session_sixARY[1];
						if ( (length($session_six_start) >= 4) && (length($session_six_end) >= 4) && ($session_five_valid > 0) && ($session_five_end <= $session_six_start) && ($session_six_start < $session_six_end) && ($session_six_end <= 2400) ) 
							{
							$settings_session_score++;
							$session_six_valid++;
							}
						}
					}
				}
			else
				{if ($DBX > 0) {print "     blank line: $c|$container_lines[$c]|\n";}}
			$c++;
			}
		if ($settings_session_score >= 1)
			{
			$stmtA = "SELECT list_id,called_count,rank FROM vicidial_list where lead_id='$VD_lead_id';";
				if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VLlist_id =			$aryA[0];
				$VLcalled_count =		$aryA[1];
				$VLrank =				$aryA[2];
				$tempVLrank = $VLrank;
				if ( ($zero_rank_after_call > 0) && ($VLrank > 0) ) {$tempVLrank=0;}
				}
			$sthA->finish();

			$secX = time();
			$CQtarget = ($secX - 14400);	# look back 4 hours
			($CQsec,$CQmin,$CQhour,$CQmday,$CQmon,$CQyear,$CQwday,$CQyday,$CQisdst) = localtime($CQtarget);
			$CQyear = ($CQyear + 1900);
			$CQmon++;
			if ($CQmon < 10) {$CQmon = "0$CQmon";}
			if ($CQmday < 10) {$CQmday = "0$CQmday";}
			if ($CQhour < 10) {$CQhour = "0$CQhour";}
			if ($CQmin < 10) {$CQmin = "0$CQmin";}
			if ($CQsec < 10) {$CQsec = "0$CQsec";}
			$CQSQLdate = "$CQyear-$CQmon-$CQmday $CQhour:$CQmin:$CQsec";

			$VDL_call_datetime='';
			$stmtA = "SELECT call_date from vicidial_dial_log where lead_id='$VD_lead_id' and call_date > \"$CQSQLdate\" and caller_code LIKE \"%$VD_lead_id\" order by call_date desc limit 1;";
				if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VDLcall_datetime = 	$aryA[0];
				@VDLcall_datetimeARY = split(/ /,$VDLcall_datetime);
				@VDLcall_timeARY = split(/:/,$VDLcall_datetimeARY[1]);
				$VDLcall_hourmin = "$VDLcall_timeARY[0]$VDLcall_timeARY[1]";

				if ( ($session_one_start <= $VDLcall_hourmin) and ($session_one_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=1; 
					$session_newSQL=",session_one_calls='1',session_one_today_calls='1'"; 
					$session_updateSQL=",session_one_calls=(session_one_calls + 1),session_one_today_calls=(session_one_today_calls + 1)";
					}
				if ( ($session_two_start <= $VDLcall_hourmin) and ($session_two_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=2; 
					$session_newSQL=",session_two_calls='1',session_two_today_calls='1'"; 
					$session_updateSQL=",session_two_calls=(session_two_calls + 1),session_two_today_calls=(session_two_today_calls + 1)";
					}
				if ( ($session_three_start <= $VDLcall_hourmin) and ($session_three_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=3; 
					$session_newSQL=",session_three_calls='1',session_three_today_calls='1'"; 
					$session_updateSQL=",session_three_calls=(session_three_calls + 1),session_three_today_calls=(session_three_today_calls + 1)";
					}
				if ( ($session_four_start <= $VDLcall_hourmin) and ($session_four_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=4; 
					$session_newSQL=",session_four_calls='1',session_four_today_calls='1'"; 
					$session_updateSQL=",session_four_calls=(session_four_calls + 1),session_four_today_calls=(session_four_today_calls + 1)";
					}
				if ( ($session_five_start <= $VDLcall_hourmin) and ($session_five_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=5; 
					$session_newSQL=",session_five_calls='1',session_five_today_calls='1'"; 
					$session_updateSQL=",session_five_calls=(session_five_calls + 1),session_five_today_calls=(session_five_today_calls + 1)";
					}
				if ( ($session_six_start <= $VDLcall_hourmin) and ($session_six_end > $VDLcall_hourmin) ) 
					{
					$call_in_session=6; 
					$session_newSQL=",session_six_calls='1',session_six_today_calls='1'"; 
					$session_updateSQL=",session_six_calls=(session_six_calls + 1),session_six_today_calls=(session_six_today_calls + 1)";
					}

				if ($AGILOG) {$agi_string = "CQ-Debug 2: $VDLcall_datetime|$VDLcall_hourmin|$timeclock_end_of_day|$session_one_start|$session_one_end|$call_in_session|";   &agi_output;}

				if ($call_in_session > 0)
					{
					if (length($timeclock_end_of_day) < 1) {$timeclock_end_of_day='0000';}
					$timeclock_end_of_day_hour = (substr($timeclock_end_of_day, 0, 2) + 0);
					$timeclock_end_of_day_min = (substr($timeclock_end_of_day, 2, 2) + 0);

					$today_start_epoch = timelocal('0',$timeclock_end_of_day_min,$timeclock_end_of_day_hour,$mday,($mon-1),$year);
					if ($timeclock_end_of_day > $VDLcall_hourmin)
						{$today_start_epoch = ($today_start_epoch - 86400);}
					$day_two_start_epoch = ($today_start_epoch - (86400 * 1));
					$day_three_start_epoch = ($today_start_epoch - (86400 * 2));
					$day_four_start_epoch = ($today_start_epoch - (86400 * 3));
					$day_five_start_epoch = ($today_start_epoch - (86400 * 4));
					$day_six_start_epoch = ($today_start_epoch - (86400 * 5));
					$day_seven_start_epoch = ($today_start_epoch - (86400 * 6));

					# Gather the details on existing vicidial_lead_call_quota_counts for this lead, if there is one
					$stmtA = "SELECT first_call_date,UNIX_TIMESTAMP(first_call_date),last_call_date from vicidial_lead_call_quota_counts where lead_id='$VD_lead_id' and list_id='$VLlist_id';";
						if ($AGILOG) {$agi_string = "|$stmtA|";   &agi_output;}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$VLCQCinfo_ct=$sthA->rows;
					if ($VLCQCinfo_ct > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VLCQCfirst_call_datetime =		$aryA[0];
						$VLCQCfirst_call_epoch =		$aryA[1];
						$VLCQClast_call_date =			$aryA[2];

						if ($VDLcall_datetime ne $VLCQClast_call_date) 
							{
							if ($VLCQCfirst_call_epoch >= $today_start_epoch) 
								{$day_updateSQL=',day_one_calls=(day_one_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_two_start_epoch) and ($VLCQCfirst_call_epoch < $today_start_epoch) )
								{$day_updateSQL=',day_two_calls=(day_two_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_three_start_epoch) and ($VLCQCfirst_call_epoch < $day_two_start_epoch) )
								{$day_updateSQL=',day_three_calls=(day_three_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_four_start_epoch) and ($VLCQCfirst_call_epoch < $day_three_start_epoch) )
								{$day_updateSQL=',day_four_calls=(day_four_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_five_start_epoch) and ($VLCQCfirst_call_epoch < $day_four_start_epoch) )
								{$day_updateSQL=',day_five_calls=(day_five_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_six_start_epoch) and ($VLCQCfirst_call_epoch < $day_five_start_epoch) )
								{$day_updateSQL=',day_six_calls=(day_six_calls+1)';}
							if ( ($VLCQCfirst_call_epoch >= $day_seven_start_epoch) and ($VLCQCfirst_call_epoch < $day_six_start_epoch) )
								{$day_updateSQL=',day_seven_calls=(day_seven_calls+1)';}
							# Update in the vicidial_lead_call_quota_counts table for this lead
							$stmtA="UPDATE vicidial_lead_call_quota_counts SET last_call_date='$VDLcall_datetime',status='$temp_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() $session_updateSQL $day_updateSQL where lead_id='$VD_lead_id' and list_id='$VLlist_id' and modify_date < NOW();";
							}
						else
							{
							# Update in the vicidial_lead_call_quota_counts table for this lead
							$stmtA="UPDATE vicidial_lead_call_quota_counts SET status='$temp_status',called_count='$VLcalled_count',rank='$tempVLrank',modify_date=NOW() where lead_id='$VD_lead_id' and list_id='$VLlist_id';";
							}
						$VLCQCaffected_rows_update = $dbhA->do($stmtA);
						if ($AGILOG) {$agi_string = "--    VLCQC record updated: |$VLCQCaffected_rows_update|   |$stmtA|";   &agi_output;}
						}
					else
						{
						# Insert new record into vicidial_lead_call_quota_counts table for this lead
						$stmtA="INSERT INTO vicidial_lead_call_quota_counts SET lead_id='$VD_lead_id',list_id='$VLlist_id',first_call_date='$VDLcall_datetime',last_call_date='$VDLcall_datetime',status='$temp_status',called_count='$VLcalled_count',day_one_calls='1',rank='$tempVLrank',modify_date=NOW() $session_newSQL;";
						$VLCQCaffected_rows_update = $dbhA->do($stmtA);
						if ($AGILOG) {$agi_string = "--    VLCQC record inserted: |$VLCQCaffected_rows_update|   |$stmtA|";   &agi_output;}
						}

					if ( ($zero_rank_after_call > 0) && ($VLrank > 0) )
						{
						# Update this lead to rank=0
						$stmtA="UPDATE vicidial_list SET rank='0' where lead_id='$VD_lead_id';";
						$VLCQCaffected_rows_zero_rank = $dbhA->do($stmtA);
						if ($AGILOG) {$agi_string = "--    VLCQC lead rank zero: |$VLCQCaffected_rows_zero_rank|   |$stmtA|";   &agi_output;}
						}
					}
				}
			$sthA->finish();
			}
		}
	}

sub check_24hour_call_count
	{
	$passed_24hour_call_count=0;
	$limit_scopeSQL='';
	if ($VD_call_limit_24hour_scope =~ /CAMPAIGN_LISTS/) 
		{
		$limit_scopeCAMP='';
		$stmtY = "SELECT list_id FROM vicidial_lists where campaign_id='$VD_campaign_id';";
		$sthY = $dbhA->prepare($stmtY) or die "preparing: ",$dbhA->errstr;
		$sthY->execute or die "executing: $stmtY", $dbhA->errstr;
		$sthYrows=$sthY->rows;
		$rec_campLISTS=0;
		while ($sthYrows > $rec_campLISTS)
			{
			@aryY = $sthY->fetchrow_array;
			$limit_scopeCAMP .= "'$aryY[0]',";
			$rec_campLISTS++;
			}
		if (length($limit_scopeCAMP) < 2) {$limit_scopeCAMP="'1'";}
		else {chop($limit_scopeCAMP);}
		$limit_scopeSQL = "and list_id IN($limit_scopeCAMP)";
		}
	if ($VD_call_limit_24hour_method =~ /PHONE_NUMBER/)
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where phone_number='$temp_24hour_phone' and phone_code='$temp_24hour_phone_code' and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	else
		{
		$stmtA="SELECT count(*) FROM vicidial_lead_24hour_calls where lead_id='$VD_lead_id' and (call_date >= NOW() - INTERVAL 1 DAY) $limit_scopeSQL;";
		}
	if ($DB) {print "     Doing 24-Hour Call Count Check: $VD_lead_id|$temp_24hour_phone_code|$temp_24hour_phone|$temp_24hour_state|$temp_24hour_postal_code - $VD_call_limit_24hour_method|$VD_call_limit_24hour_scope|$VD_call_limit_24hour\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$TFhourCOUNT=0;
	$TFhourSTATE='';
	$TFhourCOUNTRY='';
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$TFhourCOUNT =		($TFhourCOUNT + $aryA[0]);
		}
	$sthA->finish();
	$TEMPcall_limit_24hour = $VD_call_limit_24hour;
	if ($DBX) {print "     24-Hour Call Limit Count DEBUG:     $TFhourCOUNT|$stmtA|\n";}

	if ( ($VD_call_limit_24hour_override !~ /^DISABLED$/) && (length($VD_call_limit_24hour_override) > 0) ) 
		{
		$TFH_areacode = substr($temp_24hour_phone, 0, 3);
		$stmtY = "SELECT state,country FROM vicidial_phone_codes where country_code='$temp_24hour_phone_code' and areacode='$TFH_areacode';";
		$sthY = $dbhA->prepare($stmtY) or die "preparing: ",$dbhA->errstr;
		$sthY->execute or die "executing: $stmtY", $dbhA->errstr;
		$sthYrows=$sthY->rows;
		if ($sthYrows > 0)
			{
			@aryY = $sthY->fetchrow_array;
			$TFhourSTATE =		$aryY[0];
			$TFhourCOUNTRY =	$aryY[1];
			}
		$sthA->finish();

		$TEMP_TFhour_OR_entry='';
		$TFH_OR_method='state_areacode';
		$TFH_OR_postcode_field_match=0;
		$TFH_OR_state_field_match=0;
		$TFH_OR_postcode_state='';
		$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id='$VD_call_limit_24hour_override';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($DBX) {print "$sthArows|$stmtA\n";}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$TEMP_TFhour_OR_entry = $aryA[0];
			}
		$sthA->finish();

		if (length($TEMP_TFhour_OR_entry) > 2) 
			{
			@container_lines = split(/\n/,$TEMP_TFhour_OR_entry);
			$c=0;
			foreach(@container_lines)
				{
				$container_lines[$c] =~ s/;.*|\r|\t//gi;
				$container_lines[$c] =~ s/ => |=> | =>/=>/gi;
				if (length($container_lines[$c]) > 3)
					{
					# define core settings
					if ($container_lines[$c] =~ /^method/i)
						{
						#$container_lines[$c] =~ s/method=>//gi;
						$TFH_OR_method = $container_lines[$c];
						if ( ($TFH_OR_method =~ /state$/) && ($TFhourSTATE ne $temp_24hour_state) )
							{
							$TFH_OR_state_field_match=1;
							}
						if ( ($TFH_OR_method =~ /postcode/) && (length($temp_24hour_postal_code) > 0) )
							{
							if ($TFhourCOUNTRY == 'USA') 
								{
								$temp_24hour_postal_code =~ s/\D//gi;
								$temp_24hour_postal_code = substr($temp_24hour_postal_code,0,5);
								}
							if ($TFhourCOUNTRY == 'CAN') 
								{
								$temp_24hour_postal_code =~ s/[^a-zA-Z0-9]//gi;
								$temp_24hour_postal_code = substr($temp_24hour_postal_code,0,6);
								}
							$stmtA = "SELECT state FROM vicidial_postal_codes where postal_code='$temp_24hour_postal_code';";
							$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
							$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
							$sthArows=$sthA->rows;
							if ($DBX) {print "$sthArows|$stmtA\n";}
							if ($sthArows > 0)
								{
								@aryA = $sthA->fetchrow_array;
								$TFH_OR_postcode_state =		$aryA[0];
								$TFH_OR_postcode_field_match=1;
								}
							$sthA->finish();
							}
						}
					else
						{
						if ($container_lines[$c] =~ /^state/i)
							{
							$container_lines[$c] =~ s/state=>//gi;	# USA,GA,4
							@TEMP_state_ARY = split(/,/,$container_lines[$c]);
							
							if ($TFhourCOUNTRY eq $TEMP_state_ARY[0]) 
								{
								$TEMP_state_ARY[2] =~ s/\D//gi;
								if ( ($TFhourSTATE eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) )
									{
									if ($DB) {print "     24-Hour Call Count State Override Triggered: $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
									}
								if ( ($TFH_OR_postcode_state eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) && ($TFH_OR_postcode_field_match > 0) )
									{
									if ($DBX) {print "     24-Hour Call Count State Override Match(postcode $TFH_OR_postcode_state): $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									if ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)
										{
										if ($DBX) {print "          POSTCODE field override of override triggered: ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)\n";}
										$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
										}
									}
								if ( ($temp_24hour_state eq $TEMP_state_ARY[1]) && (length($TEMP_state_ARY[2]) > 0) && ($TFH_OR_state_field_match > 0) )
									{
									if ($DBX) {print "     24-Hour Call Count State Override Match(state $temp_24hour_state): $TEMPcall_limit_24hour|$container_lines[$c]\n";}
									if ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)
										{
										if ($DBX) {print "          STATE field override of override triggered: ($TEMP_state_ARY[2] < $TEMPcall_limit_24hour)\n";}
										$TEMPcall_limit_24hour = $TEMP_state_ARY[2];
										}
									}
								}
							}
						}
					}
				if ($DBX) {print "     24-Hour Call Count State Override DEBUG: |$container_lines[$c]|\n";}
				$c++;
				}
			}
		}

	if ( ($TFhourCOUNT > 0) && ($TFhourCOUNT >= $TEMPcall_limit_24hour) )
		{
		$TFHCCLlead=1;
		$TFHCCL++;
		$passed_24hour_call_count=0;
		if ($DBX) {print "Flagging 24-Hour Call Limit lead:     $VD_lead_id ($TFhourCOUNT >= $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	else
		{
		$passed_24hour_call_count=1;
		if ($DBX) {print "     24-Hour Call Limit check passed:     $VD_lead_id ($TFhourCOUNT < $TEMPcall_limit_24hour) $passed_24hour_call_count\n";}
		}
	}

sub agi_output
	{
	if ($AGILOG >=2)
		{
		### open the log file for writing ###
		open(Lout, ">>$AGILOGfile")
				|| die "Can't open $AGILOGfile: $!\n";
		print Lout "$now_date|$script|$process|$agi_string\n";
		close(Lout);
		}
		### send to STDERR writing ###
	if ( ($AGILOG == '1') || ($AGILOG == '3') )
		{
		print STDERR "$now_date|$script|$process|$agi_string\n";
		}
	$agi_string='';
	}

sub aad_output
	{
	if ( ($AGILOG > 0) || ($ADB > 0) )
		{
		### open the log file for writing ###
		open(Aout, ">>$AADLOGfile") || die "Can't open $AADLOGfile: $!\n";
		print Aout "$now_date|$script|$aad_string\n";
		close(Aout);
		}
	$aad_string='';
	}

# subroutine to parse the asterisk version
# and return a hash with the various part
sub parse_asterisk_version
	{
	# grab the arguments
	my $ast_ver_str = $_[0];

	# get everything after the - and put it in $ast_ver_postfix
	my @hyphen_parts = split( /-/ , $ast_ver_str );

	my $ast_ver_postfix = $hyphen_parts[1];

	# now split everything before the - up by the .
	my @dot_parts = split( /\./ , $hyphen_parts[0] );

	my %ast_ver_hash;

	if ( $dot_parts[0] <= 1 )
		{
		%ast_ver_hash = (
				"major" => $dot_parts[0],
				"minor" => $dot_parts[1],
				"build" => $dot_parts[2],
				"revision" => $dot_parts[3],
				"postfix" => $ast_ver_postfix
			);
		}

	# digium dropped the 1 from asterisk 10 but we still need it
	if ( $dot_parts[0] > 1 )
		{
		%ast_ver_hash = (
				"major" => 1,
				"minor" => $dot_parts[0],
				"build" => $dot_parts[1],
				"revision" => $dot_parts[2],
				"postfix" => $ast_ver_postfix
			);
		}

	return ( %ast_ver_hash );
	}

### code for processing KHOMP analytics
sub process_khomp_analytics
	{
	(
		$khomp_api_url, 
		$khomp_api_proxied,
		$khomp_api_login_url,
		$khomp_api_user, 
		$khomp_api_pass, 
		$khomp_header, 
		$khomp_id_format, 
		$external_server_ip, 
		$campaign_id, 
		$callerid, 
		$cpd_amd_action, 
		$cpd_unknown_action,
		$CIDlead_id
	) = @_;

	### Extra SIP Headers begin with an X
	$khomp_header = "X-" . $khomp_header;

	# determin khomp id format to use
	if ($khomp_id_format eq 'CALLERCODE')
		{ $khomp_id = $callerid; }
	elsif ($khomp_id_format eq 'CALLERCODE_EXTERNIP')
		{ $khomp_id = $callerid . '_' . $external_server_ip; }
	elsif ($khomp_id_format eq 'CALLERCODE_CAMP_EXTERNIP')
		{ $khomp_id = $callerid . '_' . $campaign_id . '_' . $external_server_ip; }

	my $api_auth_time = 0;
	if ( ($khomp_api_token_expire < time() ) or ( $khomp_api_token eq 'TOKENTOKENTOKEN' ))
		{
		if ($AGILOG) {$agi_string = "--    KHOMP API Token $khomp_api_token has expired at $khomp_api_token_expire";   &agi_output;}

		# get a new API token
		my $new_khomp_api_token = '';
		if ( $khomp_api_proxied eq 'false' )
			{
			($new_khomp_api_token, $api_auth_time) = khomp_api_login( $khomp_api_login_url, $khomp_api_user, $khomp_api_pass );
			}

		# update the settings container
		my $old_token_string = "khomp_api_token => $khomp_api_token";
		my $new_token_string = "khomp_api_token => $new_khomp_api_token";
		my $new_token_expire_time = time() + 3600;
		my $old_token_expire_string = "khomp_api_token_expire => $khomp_api_token_expire";
		my $new_token_expire_string = "khomp_api_token_expire => $new_token_expire_time";

		# LOCK vicidial_settings_containers
		$stmtA = "LOCK TABLES vicidial_settings_containers WRITE";
		$dbhA->do($stmtA);
		# UPDATE the Token
		$stmtToken = "UPDATE vicidial_settings_containers SET container_entry = REGEXP_REPLACE(container_entry, '$old_token_string', '$new_token_string') WHERE container_id = 'KHOMPSETTINGS';";
		$affected_rows = $dbhA->do($stmtToken);
		# UPDATE the Expire time
		$stmtExpire = "UPDATE vicidial_settings_containers SET container_entry = REGEXP_REPLACE(container_entry, '$old_token_expire_string', '$new_token_expire_string') WHERE container_id = 'KHOMPSETTINGS';";
		$affected_rows = $dbhA->do($stmtExpire);
		# Unlock vicidial_settings_containers
		$stmtA = "UNLOCK TABLES";
		$dbhA->do($stmtA);

		if ($AGILOG) {$agi_string = "--    KHOMP SC TOKEN UPDATE|$affected_rows|$stmtToken|";   &agi_output;}
		if ($AGILOG) {$agi_string = "--    KHOMP SC TOKEN EXPIRE UPDATE|$affected_rows|$stmtExpire|";   &agi_output;}

		# over write the old with the new
		$khomp_api_token = $new_khomp_api_token;
		}
	else
		{
		if ($AGILOG) {$agi_string = "--    KHOMP API Token $khomp_api_token still valid till $khomp_api_token_expire";   &agi_output;}
		}
	
	if ( $khomp_api_token ne 'TOKENTOKENTOKEN' )
		{
		# build JSON object
		my $khomp_request = {
			'id' => 0,
			'method' => 'CallList',
			'params' => {
				'token' => "$khomp_api_token",
				'query' => {
					'type' => "eq",
					'field' => "sip_header:$khomp_header",
					'values' => ["$khomp_id"],
				},
				'selected_fields' => ["sip_header:call-id", "start_stamp", "audio_stamp", "answer_stamp", "end_stamp", "analyzer_stamp", "analyzer_conclusion", "analyzer_pattern", "analyzer_action", "hangup_origin", "hangup_cause", "hangup_cause_sent"]
			},
			'jsonrpc' => '2.0',
		};

		# encode it as JSON
		$khomp_json = encode_json( $khomp_request );

		# call the API
		($result, $api_query_time) = khomp_json_api( $khomp_json, $khomp_api_url );

		# check the result
		if ( $result =~ /^ERROR/ )
			{
			# we got an error so log it
			if ($AGILOG) { $agi_string = "--    KHOMP: $result"; &agi_output; }
			}
		else
			{
			# we got a result
			if ( defined $result->{'result'}->{'calls'}[0]->{'fields'} )
				{
				my $khomp_call_data = $result->{'result'}->{'calls'}[0]->{'fields'};
				%parsed_data = khomp_parse_call_data( $khomp_header, $khomp_id, $callerid, $CIDlead_id, $khomp_id_format, $khomp_call_data );

				$parsed_data{'api_auth_time'} = $api_auth_time;
				$parsed_data{'api_query_time'} = $api_query_time;

				$conclusion = $parsed_data{'conclusion'};
				$pattern = $parsed_data{'pattern'};

				my $khomp_VDL_status = '';
				my $khomp_VDAC_status = '';
				my $khomp_action = '';

				if ( $conclusion ne "" ) 
					{
					# we got a conclusion
					if ( defined ( $conclusion_map{"$conclusion"}{"$pattern"}{'status'} ) )
						{
						# see if there is a conclusion and pattern for it
						$khomp_action = $conclusion_map{"$conclusion"}{"$pattern"}{'action'};
						$khomp_VDL_status = $conclusion_map{"$conclusion"}{"$pattern"}{'status'};
						$khomp_VDAC_status = $conclusion_map{"$conclusion"}{"$pattern"}{'dial_status'};
						}
					elsif ( defined( $conclusion_map{"$conclusion"}{''}{'status'} ) )
						{
						# there is no known pattern for it
						$khomp_action = $conclusion_map{"$conclusion"}{""}{'action'};
						$khomp_VDL_status = $conclusion_map{"$conclusion"}{''}{'status'};
						$khomp_VDAC_status = $conclusion_map{"$conclusion"}{""}{'dial_status'};
						}
					else
						{
						# there isnt even a known conclusion for it
						$khomp_action = 'error';
						$khomp_VDL_status = 'KPEROR';
						$khomp_VDAC_status = 'NA';
						}
					}
				else
					{
					# no conclusion was made
					if ( $parsed_data{'hangup_origin'} eq 'leg a' )
						{
						# Vicidial Hung Up
						# Not sure what to do here
						}
					elsif ( $parsed_data{'hangup_origin'} eq 'leg b' )
						{
						# Khomp or Carrier Did Not Answer
						if ( $parsed_data{'hangup_cause_recv'} eq '20003' )
							{
							# No Channels Available on KHOMP equipment
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPNOCH';
							$khomp_VDAC_status = 'CONGESTION';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '30000' )
							{
							# No sip response was recieved for KHOMP's INVITE
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPTMOT';
							$khomp_VDAC_status = 'CONGESTION';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '486' )
							{
							# Carrier returned "Busy Here"
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPBUSY';
							$khomp_VDAC_status = 'BUSY';
							}
						elsif (
							( $parsed_data{'hangup_cause_recv'} eq '487' ) &&
							( $parsed_data{'answer_epoch'} > 0 )
							)				
							{
							# Call hung up before Khomp had a conclusion
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPDROP';
							$khomp_VDAC_status = 'ANSWER';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '488' )
							{
							# Carrier returned "Not Acceptable Here"
							# Normally an audio codec issue, but could be other things
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPCONG';
							$khomp_VDAC_status = 'CONGESTION';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '500' )
							{
							# Carrier returned "Internal Server Error"
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPCONG';
							$khomp_VDAC_status = 'CONGESTION';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '503' )
							{
							# Carrier returned "Service Unavailable"
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPCONG';
							$khomp_VDAC_status = 'CONGESTION';
							}
						elsif ( $parsed_data{'hangup_cause_recv'} eq '603' )
							{
							# Carrier returned "Decline"
							$khomp_action = 'status';
							$khomp_VDL_status = 'KPCONG';
							$khomp_VDAC_status = 'CONGESTION';
							}
						else	
							{
							$khomp_action = 'error';
							$khomp_VDL_status = 'KPEROR';
							$khomp_VDAC_status = 'NA';
							}
						}
					}
					
				if ( $khomp_action ne '' )		{ $parsed_data{'vici_action'} = "$khomp_action"; }
				if ( $khomp_VDL_status ne '' )	{ $parsed_data{'vici_status'} = "$khomp_VDL_status"; }

				# log the khomp data in the vicidial_khomp_log
				log_khomp_call_data( %parsed_data);

				if ($AGILOG) { $agi_string = "--KHOMP: conclusion = $conclusion|pattern = $pattern|status = $khomp_VDL_status"; &agi_output; }

				return ( $khomp_action, $khomp_VDL_status, $khomp_VDAC_status );
				
				}
			else
				{
				if ($AGILOG) { $agi_string = "--    KHOMP: No Call Record Found"; &agi_output; }
				return ( 'error', 'ERROR', 'ERROR' );
				}

			}
		}
	else
		{
		if ($AGILOG) { $agi_string = "--KHOMP: Login Failed"; &agi_output; }
		return ( 'error', 'ERROR', 'ERROR');
		}
	}

# code to log the khomp data in vicidial_khomp_log
sub log_khomp_call_data
	{
	(%khomp_call_data) = @_;

	$stmtA = "UPDATE vicidial_khomp_log SET caller_code = '$khomp_call_data{'caller_code'}', lead_id = '$khomp_call_data{'lead_id'}', server_ip = '$VARserver_ip', khomp_header = '$khomp_call_data{'khomp_header'}', khomp_id = '$khomp_call_data{'khomp_id'}', khomp_id_format = '$khomp_call_data{'khomp_id_format'}', hangup_auth_time = '$khomp_call_data{'api_auth_time'}', hangup_query_time = '$khomp_call_data{'api_query_time'}'";

	if ( defined( $khomp_call_data{'sip_call_id'} ) ) { $stmtA .= ", sip_call_id = '$khomp_call_data{'sip_call_id'}'"; }
	if ( defined( $khomp_call_data{'start_epoch'} ) ) { $stmtA .= ", start_date = FROM_UNIXTIME($khomp_call_data{'start_epoch'})"; }
	if ( defined( $khomp_call_data{'audio_epoch'} ) ) { $stmtA .= ", audio_date = FROM_UNIXTIME($khomp_call_data{'audio_epoch'})"; }
	if ( defined( $khomp_call_data{'answer_epoch'} ) ) { $stmtA .= ", answer_date = FROM_UNIXTIME($khomp_call_data{'answer_epoch'})"; }
	if ( defined( $khomp_call_data{'end_epoch'} ) ) { $stmtA .= ", end_date = FROM_UNIXTIME($khomp_call_data{'end_epoch'})"; }
	if ( defined( $khomp_call_data{'analyzer_epoch'} ) ) { $stmtA .= ", analyzer_date = FROM_UNIXTIME($khomp_call_data{'analyzer_epoch'})"; }
	if ( defined( $khomp_call_data{'conclusion'} ) ) { $stmtA .= ", conclusion = '$khomp_call_data{'conclusion'}'"; }
	if ( defined( $khomp_call_data{'pattern'} ) ) { $stmtA .= ", pattern = '$khomp_call_data{'pattern'}'"; }
	if ( defined( $khomp_call_data{'action'} ) ) { $stmtA .= ", action = '$khomp_call_data{'action'}'"; }
	if ( defined( $khomp_call_data{'hangup_origin'} ) ) { $stmtA .= ", hangup_origin = '$khomp_call_data{'hangup_origin'}'"; }
	if ( defined( $khomp_call_data{'hangup_cause_recv'} ) ) { $stmtA .= ", hangup_cause_recv = '$khomp_call_data{'hangup_cause_recv'}'"; }
	if ( defined( $khomp_call_data{'hangup_cause_sent'} ) ) { $stmtA .= ", hangup_cause_sent = '$khomp_call_data{'hangup_cause_sent'}'"; }
	if ( defined( $khomp_call_data{'vici_action'} ) ) { $stmtA .= ", vici_action = '$khomp_call_data{'vici_action'}'"; }
	if ( defined( $khomp_call_data{'vici_status'} ) ) { $stmtA .= ", vici_status = '$khomp_call_data{'vici_status'}'"; }

	$stmtA .= " WHERE khomp_id = '$khomp_call_data{'khomp_id'}'";

	$affected_rows = $dbhA->do($stmtA);

	if ($AGILOG) {$agi_string = "--    KHOMP Log Update: |$stmtA|$affected_rows|";   &agi_output;}

	}

### code for parsing the call data into a useful data structure
sub khomp_parse_call_data
	{
	( $khomp_header, $khomp_id, $callerid, $lead_id, $khomp_id_format, $khomp_call_data ) = @_;

	my %new_khomp_call_data;

	
	#KHOMP Field Names - Meaning:
	#	sip_header:call-id - leg A - SIP Call-ID
	#	start_stamp - unix timestamp of the call start (first INVITE)
	#	audio_stamp - unix timestamp of the audio start (183)
	#	answer_stamp - unix timestamp of the call answer (200 OK)
	#	end_stamp - unix timestamp of call end 
	#	analyzer_stamp - unix timestamp of Analytics conclusion
	#	analyzer_conclusion - the Analytics conclusion
	#	analyzer_pattern - The specific pattern that was detected (if any)
	#	analyzer_action - the action taken for the analytics conclusion
	#	hangup_origin - which leg hangup the call: leg a or leg b
	#	hangup_cause - hangup cause received 
	#	hangup_cause_sent - the cause code that was sent
	#

	$new_khomp_call_data{'caller_code'} = $callerid;
	$new_khomp_call_data{'lead_id'} = $lead_id;
	$new_khomp_call_data{'khomp_header'} = $khomp_header;
	$new_khomp_call_data{'khomp_id'} = $khomp_id;
	$new_khomp_call_data{'khomp_id_format'} = $khomp_id_format;
	$new_khomp_call_data{'sip_call_id'} = $khomp_call_data->{'sip_header:call-id'};
	$new_khomp_call_data{'start_epoch'} = $khomp_call_data->{'start_stamp'};
	$new_khomp_call_data{'audio_epoch'} = $khomp_call_data->{'audio_stamp'};
	$new_khomp_call_data{'answer_epoch'} = $khomp_call_data->{'answer_stamp'};
	$new_khomp_call_data{'end_epoch'} = $khomp_call_data->{'end_stamp'};
	$new_khomp_call_data{'analyzer_epoch'} = $khomp_call_data->{'analyzer_stamp'};
	$new_khomp_call_data{'conclusion'} = $khomp_call_data->{'analyzer_conclusion'};
	$new_khomp_call_data{'pattern'} = $khomp_call_data->{'analyzer_pattern'};
	$new_khomp_call_data{'action'} = $khomp_call_data->{'analyzer_action'};
	$new_khomp_call_data{'hangup_origin'} = $khomp_call_data->{'hangup_origin'};
	$new_khomp_call_data{'hangup_cause_recv'} = $khomp_call_data->{'hangup_cause'};
	$new_khomp_call_data{'hangup_cause_sent'} = $khomp_call_data->{'hangup_cause_sent'};

	# handle blank times
	if ($new_khomp_call_data{'start_epoch'} eq '') { $new_khomp_call_data{'start_epoch'} = 0; }
	if ($new_khomp_call_data{'audio_epoch'} eq '') { $new_khomp_call_data{'audio_epoch'} = 0; }
	if ($new_khomp_call_data{'answer_epoch'} eq '') { $new_khomp_call_data{'answer_epoch'} = 0; }
	if ($new_khomp_call_data{'end_epoch'} eq '') { $new_khomp_call_data{'end_epoch'} = 0; }
	if ($new_khomp_call_data{'analyzer_epoch'}  eq '') { $new_khomp_call_data{'analyzer_epoch'} = 0; }

	# time is returned in milliseconds since 1970 we need seconds
	if ($new_khomp_call_data{'start_epoch'} > 0) { $new_khomp_call_data{'start_epoch'} = $new_khomp_call_data{'start_epoch'} / 1000; }
	if ($new_khomp_call_data{'audio_epoch'} > 0) { $new_khomp_call_data{'audio_epoch'} = $new_khomp_call_data{'audio_epoch'} / 1000; }
	if ($new_khomp_call_data{'answer_epoch'} > 0) { $new_khomp_call_data{'answer_epoch'} = $new_khomp_call_data{'answer_epoch'} / 1000; }
	if ($new_khomp_call_data{'end_epoch'} > 0) { $new_khomp_call_data{'end_epoch'} = $new_khomp_call_data{'end_epoch'} / 1000; }
	if ($new_khomp_call_data{'analyzer_epoch'} > 0) { $new_khomp_call_data{'analyzer_epoch'} = $new_khomp_call_data{'analyzer_epoch'} / 1000; }

	return %new_khomp_call_data;
	}

### code for logging into KHOMP api
sub khomp_api_login
	{
	( 
	$khomp_api_login_url,
	$khomp_api_user,
	$khomp_api_pass
	) = @_;

	my $token = 'login';
	my $auth_start_sec;
	my $auth_start_usec;
	my $auth_end_sec;
	my $auth_end_usec;
	my $auth_sec;
	my $auth_usec;
	my $api_auth_time;

	($auth_start_sec, $auth_start_usec) = gettimeofday();

	# build JSON object
	my $login_json = {
		'id' => 0,
		'method' => 'SessionLogin',
		'params' => {
			'username' => "$khomp_api_user",
			'password' => "$khomp_api_pass",
		},
		'jsonrpc' => '2.0',
	};

	# encode it as JSON
	$login_json = encode_json( $login_json );

	my $curl_cmd = "$curlbin -sS --data \'$login_json\' $khomp_api_login_url";

	$agi_string = "--    KHOMP CURL COMMAND: $curl_cmd";   &agi_output;

	$message = `$curl_cmd`;

	chomp($message);

	if ($AGILOG)
		{
		$agi_string = "--    KHOMP LOGIN URL: |$khomp_api_login_url|";   &agi_output;
		$agi_string = "--    KHOMP LOGIN JSON : |$login_json|";   &agi_output;
		$agi_string = "--    KHOMP LOGIN RESPONSE JSON: |$message|";   &agi_output;
		}

	$json = JSON::PP->new->ascii->pretty->allow_nonref;
	$result = $json->decode($message);

	$token = $result->{'result'}->{'token'};

	($auth_end_sec, $auth_end_usec) = gettimeofday();

	$api_auth_time = tv_interval ( [$auth_start_sec, $auth_start_usec], [$auth_end_sec, $auth_end_usec]);

	return ($token, $api_auth_time);
	}

### code for connecting to KHOMP api and passing JSON
sub khomp_json_api
	{
	( $khomp_json, $khomp_api_url ) = @_;

	my $query_start_sec;
	my $query_start_usec;
	my $query_end_sec;
	my $query_end_usec;
	my $query_sec;
	my $query_usec;
	my $api_query_time;

	($query_start_sec, $query_start_usec) = gettimeofday();

	my $curl_cmd = "$curlbin -sS --data \'$khomp_json\' $khomp_api_url";

	$agi_string = "--    KHOMP CURL COMMAND: $curl_cmd";   &agi_output;

	$message = `$curl_cmd`;

	chomp($message);

	if ($AGILOG)
		{
		$agi_string = "--    KHOMP URL: |$khomp_api_url|";   &agi_output;
		$agi_string = "--    KHOMP JSON : |$khomp_json|";   &agi_output;
		$agi_string = "--    KHOMP RESPONSE JSON: |$message|";   &agi_output;
		}

	$json = JSON::PP->new->ascii->pretty->allow_nonref;
	$result = $json->decode($message);

	($query_end_sec, $query_end_usec) = gettimeofday();

	$api_query_time = tv_interval ( [$query_start_sec, $query_start_usec], [$query_end_sec, $query_end_usec]);

	return ($result, $api_query_time);
	}
