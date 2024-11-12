#!/usr/bin/perl
#
# AST_send_URL.pl   version 2.14
# 
# DESCRIPTION:
# This script is spawned for remote agents when the Start Call URL is set in the
# campaign or in-group that the call came from when sent to the remote agent.
# This script is also used for the Add-Lead-URL feature in In-groups and for
# QM socket-send as well as from call menus using a settings container.
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 100622-0929 - First Build
# 100929-0918 - Added function variable and new Add Lead URL function
# 110731-0127 - Added call_id variable and logging
# 121120-0925 - Added QM socket-send functionality
# 130402-2307 - Added user_group variable
# 141211-1742 - Added list override for no-agent url
# 141219-1218 - url logging fix for some inputs
# 150114-2037 - Added list_name variable
# 150429-0910 - Added campaign variables
# 150609-1931 - Added list_description variable
# 150703-1542 - Fixed issue with list_id check
# 150711-1501 - Changed to add more logging and better HTTPS support
# 170202-2321 - Added SC_CALL_URL function
# 170509-1545 - Added --A--SQLdate--B-- variable
# 180519-1741 - Added INGROUP_WCU_ON & INGROUP_WCU_OFF functions
# 180520-2002 - Added ENTER_INGROUP_URL function
# 180601-0929 - Added full_name and launch_user variable options
# 210519-2208 - Added DB disconnect and reconnect to save DB connections on long response time URLs
# 230204-2145 - Added ability to use ALT na_call_url entries, added status input
# 230531-1139 - Added option to send any URL as POST if "POST" is at the end of the $function var
# 230605-0836 - Added SC_CALL_URL option to add post headers
# 230516-2150 - Added ALT start_call_url code and --version flag
#

$build = '230516-2150';

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
my ($CLOhelp, $CLOversion, $SYSLOG,$DB, $DBX);
my ($campaign, $lead_id, $phone_number, $call_type, $user, $uniqueid, $alt_dial, $call_id, $status, $function, $container);
my $FULL_LOG = 1;
$url_function = 'other';
$US='_';
$post_headers='';

### begin parsing run-time options ###
if (scalar @ARGV) {
	GetOptions('help!' => \$CLOhelp,
		'version!' => \$CLOversion,
		'SYSLOG!' => \$SYSLOG,
		'campaign=s' => \$campaign,
		'lead_id=s' => \$lead_id,
		'phone_number=s' => \$phone_number,
		'call_type=s' => \$call_type,
		'user=s' => \$user,
		'uniqueid=s' => \$uniqueid,
		'alt_dial=s' => \$alt_dial,
		'call_id=s' => \$call_id,
		'list_id:s' => \$list_id,
		'status:s' => \$status,
		'function=s' => \$function,
		'container=s' => \$container,
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
		print "  campaign:              $campaign\n" if ($campaign);
		print "  lead_id:               $lead_id\n" if ($lead_id);
		print "  phone_number:          $phone_number\n" if ($phone_number);
		print "  call_type:             $call_type\n" if ($call_type);
		print "  user:                  $user\n" if ($user);
		print "  uniqueid:              $uniqueid\n" if ($uniqueid);
		print "  alt_dial:              $alt_dial\n" if ($alt_dial);
		print "  call_id:               $call_id\n" if ($call_id);
		print "  list_id:               $list_id\n" if ($list_id);
		print "  status:                $status\n" if ($status);
		print "  function:              $function\n" if ($function);
		print "  container:             $container\n" if ($container);
		print "  compat_url:            $compat_url\n" if ($compat_url);
		print "\n";
		}
	if ($CLOhelp) 
		{
		print "allowed run time options:\n";
		print "  [--help] = this help screen\n";
		print "  [--version] = show build of this script\n";
		print "  [--SYSLOG] = whether to log actions or not\n";
		print "required flags:\n";
		print "  [--campaign] = campaign ID or In-group ID of the call\n";
		print "  [--lead_id] = lead ID for the call\n";
		print "  [--phone_number] = phone number of the call\n";
		print "  [--call_type] = Inbound or outbound call\n";
		print "  [--user] = remote user that received the call\n";
		print "  [--uniqueid] = uniqueid of the call\n";
		print "  [--alt_dial] = label of the phone number dialed\n";
		print "  [--call_id] = call_id or caller_code of the call\n";
		print "  [--list_id] = list_id of the lead on the call\n";
		print "  [--status] = status of the call at the time of the URL trigger\n";
		print "  [--compat_url] = full compatible URL to send\n";
		print "  [--container] = settings container to use if function is SC_CALL_URL\n";
		print "  [--function] = which function is to be run, default is REMOTE_AGENT_START_CALL_URL\n";
		print "      *REMOTE_AGENT_START_CALL_URL - performs a Start Call URL get for Remote Agent Calls\n";
		print "      *INGROUP_ADD_LEAD_URL - performs an Add Lead URL get for In-Groups when a lead is added\n";
		print "      *QM_SOCKET_SEND - performs a queue_log socket send url function\n";
		print "      *NA_CALL_URL - performs a na_call_url send url function\n";
		print "      *SC_CALL_URL - performs a settings container send url function\n";
		print "\n";
		print "You may prefix an option with 'no' to disable it.\n";
		print " ie. --noSYSLOG or --noFULLLOG\n";

		exit 0;
		}
	if ($CLOversion)
		{
		print "script build: $build \n";

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


if (length($lead_id) > 0) 
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

	if ($call_type =~ /IN/)
		{
		$stmtG = "SELECT start_call_url,add_lead_url,na_call_url,waiting_call_url_on,waiting_call_url_off,enter_ingroup_url FROM vicidial_inbound_groups where group_id='$campaign';";
		$alt_type =	'ingroup';
		$alt_id =	$campaign;
		}
	else
		{
		$stmtG = "SELECT start_call_url,'NONE',na_call_url,'NONE','NONE','NONE' FROM vicidial_campaigns where campaign_id='$campaign';";
		$alt_type =	'campaign';
		$alt_id =	$campaign;
		}
	$sthA = $dbhA->prepare($stmtG) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtG ", $dbhA->errstr;
	$start_url_ct=$sthA->rows;
	if ($start_url_ct > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$start_call_url =		$aryA[0];
		$add_lead_url =			$aryA[1];
		$na_call_url =			$aryA[2];
		$waiting_call_url_on =	$aryA[3];
		$waiting_call_url_off =	$aryA[4];
		$enter_ingroup_url =	$aryA[5];
		}
	$sthA->finish();
	
	if ($DBX) {print "DEBUG: $stmtG\n";}

	if ($function =~ /SC_CALL_URL/)
		{
		$sc_call_url='';
		$post_headers='';
		$sc_container_entry='';
		$stmtH= "SELECT container_entry from vicidial_settings_containers where container_id='$container';";
		$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
			if ($AGILOG) {$agi_string = "$sthArows|$stmtH|";   &agi_output;}
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$sc_container_entry	= $aryA[0];
			}
		else
			{$no_container=2;}
		$sthA->finish();

		if (length($sc_container_entry) > 3) 
			{
			@container_lines = split(/\n/,$sc_container_entry);
			$c=0;
			foreach(@container_lines)
				{
				if ($container_lines[$c] =~ /; POST-HEADER=/) 
					{
					$container_lines[$c] =~ s/; POST-HEADER=|\r|\t//gi;
					$post_headers .= " --header=\"$container_lines[$c]\"";
					}
				else
					{
					$container_lines[$c] =~ s/;.*|\r|\t//gi;
					if (length($container_lines[$c]) > 0)
						{
						$sc_call_url .= $container_lines[$c];
						}
					}
				$c++;
				}
			}

		}

	if ( ($function =~ /NA_CALL_URL/) && (length($list_id) > 1) )
		{
		$stmtH = "SELECT na_call_url FROM vicidial_lists where list_id='$list_id';";
		$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
		$list_nacu_url_ct=$sthA->rows;
		if ($DBX) {print "DEBUG: $list_nacu_url_ct|$stmtH\n";}
		if ($list_nacu_url_ct > 0)
			{
			@aryA = $sthA->fetchrow_array;
			if ( (length($aryA[0]) > 3) || ($aryA[0] =~ /^ALT$/) ) 
				{
				$na_call_url =		$aryA[0];
				$alt_type =			'list';
				$alt_id =			$list_id;
				}
			}
		$sthA->finish();
		}

	$list_name='';
	$list_description='';
	if (length($list_id) > 1)
		{
		$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$list_id';";
		$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
		$list_name_ct=$sthA->rows;
		if ($list_name_ct > 0)
			{
			@aryA = $sthA->fetchrow_array;
			if (length($aryA[0])>0) 
				{
				$list_name =		$aryA[0];
				}
			if (length($aryA[1])>0) 
				{
				$list_description =	$aryA[1];
				}
			}
		$sthA->finish();
		}

	$VAR_lead_id =			$lead_id;
	$VAR_user =				$user;
	$VAR_phone_number =		$phone_number;
	$VAR_call_type =		$call_type;
	$VAR_uniqueid =			$uniqueid;
	$VAR_group_id =			$campaign;
	$VAR_campaign_id =		$campaign;
	$VAR_group =			$campaign;
	$VAR_alt_dial =			$alt_dial;
	$VAR_call_id =			$call_id;
	$VAR_list_id =			$list_id;
	$VAR_status =			$status;
	$VAR_phone_code =		'';
	$VAR_vendor_lead_code =	'';
	$VAR_did_id =			'';
	$VAR_did_extension =	'';
	$VAR_did_pattern =		'';
	$VAR_did_description =	'';
	$VAR_closecallid =		'';


	if ($function =~ /QM_SOCKET_SEND/)
		{
		##### BEGIN QM socket-send URL function #####
		$compat_url =~ s/ /+/gi;
		$compat_url =~ s/&/\\&/gi;
		$parse_url = $compat_url;
		$url_function = 'qm_socket';
		##### END QM socket-send URL function #####
		}
	elsif ($function =~ /INGROUP_WCU_ON/)
		{
		##### BEGIN Waiting-Call URL ON function #####
		$waiting_call_url_on =~ s/^VAR//gi;
		$waiting_call_url_on =~ s/--A--SQLdate--B--/$now_date/gi;
		$waiting_call_url_on =~ s/ /+/gi;
		$waiting_call_url_on =~ s/&/\\&/gi;
		$parse_url = $waiting_call_url_on;
		$url_function = 'wcu_on';
		##### END Waiting-Call URL ON function #####
		}
	elsif ($function =~ /INGROUP_WCU_OFF/)
		{
		##### BEGIN Waiting-Call URL OFF function #####
		$waiting_call_url_off =~ s/^VAR//gi;
		$waiting_call_url_off =~ s/--A--SQLdate--B--/$now_date/gi;
		$waiting_call_url_off =~ s/ /+/gi;
		$waiting_call_url_off =~ s/&/\\&/gi;
		$parse_url = $waiting_call_url_off;
		$url_function = 'wcu_off';
		##### END Waiting-Call URL OFF function #####
		}
	elsif ($function =~ /INGROUP_ADD_LEAD_URL/)
		{
		##### BEGIN Add Lead URL function #####
		$stmtA = "SELECT list_id,phone_code,vendor_lead_code FROM vicidial_list where lead_id=$lead_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_list_id =			$aryA[0];
			$VAR_phone_code =		$aryA[1];
			$VAR_vendor_lead_code =	$aryA[2];
			}
		$sthA->finish();

		$stmtA = "SELECT did_id,extension FROM vicidial_did_log where uniqueid='$uniqueid';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_did_id =			$aryA[0];
			$VAR_did_extension =	$aryA[1];
			}
		$sthA->finish();

		$stmtA = "SELECT did_pattern,did_description FROM vicidial_inbound_dids where did_id='$VAR_did_id';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_did_pattern =		$aryA[0];
			$VAR_did_description =	$aryA[1];
			}
		$sthA->finish();

		$stmtA = "SELECT closecallid FROM vicidial_closer_log where uniqueid='$uniqueid' order by closecallid limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_closecallid =		$aryA[0];
			}
		$sthA->finish();

		if ( (length($VAR_list_id) > 1) && ( ( (length($list_name) < 1) && ($add_lead_url =~ /--A--list_name--B--/) ) or ( (length($list_description) < 1) && ($add_lead_url =~ /--A--list_description--B--/) ) ) )
			{
			$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$VAR_list_id';";
			$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
			$list_name_ct=$sthA->rows;
			if ($list_name_ct > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if (length($aryA[0])>0) 
					{
					$list_name =		$aryA[0];
					}
				if (length($aryA[1])>0) 
					{
					$list_description =	$aryA[1];
					}
				}
			$sthA->finish();
			}

		$add_lead_url =~ s/^VAR//gi;
		$add_lead_url =~ s/--A--lead_id--B--/$VAR_lead_id/gi;
		$add_lead_url =~ s/--A--vendor_id--B--/$VAR_vendor_lead_code/gi;
		$add_lead_url =~ s/--A--vendor_lead_code--B--/$VAR_vendor_lead_code/gi;
		$add_lead_url =~ s/--A--list_id--B--/$VAR_list_id/gi;
		$add_lead_url =~ s/--A--list_name--B--/$list_name/gi;
		$add_lead_url =~ s/--A--list_description--B--/$list_description/gi;
		$add_lead_url =~ s/--A--phone_number--B--/$VAR_phone_number/gi;
		$add_lead_url =~ s/--A--phone_code--B--/$VAR_phone_code/gi;
		$add_lead_url =~ s/--A--did_id--B--/$VAR_did_id/gi;
		$add_lead_url =~ s/--A--did_extension--B--/$VAR_did_extension/gi;
		$add_lead_url =~ s/--A--did_pattern--B--/$VAR_did_pattern/gi;
		$add_lead_url =~ s/--A--did_description--B--/$VAR_did_description/gi;
		$add_lead_url =~ s/--A--closecallid--B--/$VAR_closecallid/gi;
		$add_lead_url =~ s/--A--uniqueid--B--/$VAR_uniqueid/gi;
		$add_lead_url =~ s/--A--call_id--B--/$VAR_call_id/gi;
		$add_lead_url =~ s/--A--function--B--/$function/gi;
		$add_lead_url =~ s/--A--SQLdate--B--/$now_date/gi;
		$add_lead_url =~ s/ /+/gi;
		$add_lead_url =~ s/&/\\&/gi;
		$parse_url = $add_lead_url;
		$url_function = 'add_lead';
		##### END Add Lead URL function #####
		}
	elsif ($function =~ /ENTER_INGROUP_URL/)
		{
		##### BEGIN ENTER_INGROUP_URL function #####
		$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,phone_code FROM vicidial_list where lead_id=$lead_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_entry_date =		$aryA[1];
			$VAR_modify_date =		$aryA[2];
			$VAR_status =			$aryA[3];
			$VAR_vendor_lead_code =	$aryA[5];
			$VAR_source_id =		$aryA[6];
			$VAR_list_id =			$aryA[7];
			$VAR_title =			$aryA[9];
			$VAR_first_name =		$aryA[10];
			$VAR_middle_initial =	$aryA[11];
			$VAR_last_name =		$aryA[12];
			$VAR_address1 =			$aryA[13];
			$VAR_address2 =			$aryA[14];
			$VAR_address3 =			$aryA[15];
			$VAR_city =				$aryA[16];
			$VAR_state =			$aryA[17];
			$VAR_province =			$aryA[18];
			$VAR_postal_code =		$aryA[19];
			$VAR_country_code =		$aryA[20];
			$VAR_gender =			$aryA[21];
			$VAR_date_of_birth =	$aryA[22];
			$VAR_alt_phone =		$aryA[23];
			$VAR_email =			$aryA[24];
			$VAR_security_phrase =	$aryA[25];
			$VAR_comments =			$aryA[26];
			$VAR_called_count =		$aryA[27];
			$VAR_last_local_call_time = $aryA[28];
			$VAR_rank =				$aryA[29];
			$VAR_owner =			$aryA[30];
			$VAR_phone_code =		$aryA[31];
			}
		$sthA->finish();

		if ($enter_ingroup_url =~ /--A--did_/)
			{
			$stmtA = "SELECT did_id,extension FROM vicidial_did_log where uniqueid='$uniqueid';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_did_id =			$aryA[0];
				$VAR_did_extension =	$aryA[1];
				}
			$sthA->finish();

			$stmtA = "SELECT did_pattern,did_description FROM vicidial_inbound_dids where did_id='$VAR_did_id';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_did_pattern =		$aryA[0];
				$VAR_did_description =	$aryA[1];
				}
			$sthA->finish();
			}

		if ($enter_ingroup_url =~ /--A--user_custom_|--A--full_name--B--/)
			{
			$stmtA = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five,user_group,full_name from vicidial_users where user='$user';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_user_custom_one =		$aryA[0];
				$VAR_user_custom_two =		$aryA[1];
				$VAR_user_custom_three =	$aryA[2];
				$VAR_user_custom_four =		$aryA[3];
				$VAR_user_custom_five =		$aryA[4];
				$VAR_user_group =			$aryA[5];
				$VAR_full_name =			$aryA[6];
				}
			}

		if ($enter_ingroup_url =~ /--A--closecallid--B--/)
			{
			$stmtA = "SELECT closecallid FROM vicidial_closer_log where uniqueid='$uniqueid' order by closecallid limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_closecallid =		$aryA[0];
				}
			$sthA->finish();
			}

		if ( (length($VAR_list_id) > 1) && ( ( (length($list_name) < 1) && ($enter_ingroup_url =~ /--A--list_name--B--/) ) || ( (length($list_description) < 1) && ($enter_ingroup_url =~ /--A--list_description--B--/) ) ) )
			{
			$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$VAR_list_id';";
			$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
			$list_name_ct=$sthA->rows;
			if ($list_name_ct > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if (length($aryA[0])>0) 
					{
					$list_name =		$aryA[0];
					}
				if (length($aryA[1])>0) 
					{
					$list_description =	$aryA[1];
					}
				}
			$sthA->finish();
			}

		$enter_ingroup_url =~ s/^VAR//gi;
		$enter_ingroup_url =~ s/--A--lead_id--B--/$VAR_lead_id/gi;
		$enter_ingroup_url =~ s/--A--entry_date--B--/$VAR_entry_date/gi;
		$enter_ingroup_url =~ s/--A--modify_date--B--/$VAR_modify_date/gi;
		$enter_ingroup_url =~ s/--A--status--B--/$VAR_status/gi;
		$enter_ingroup_url =~ s/--A--dispo--B--/$VAR_status/gi;
		$enter_ingroup_url =~ s/--A--user--B--/$VAR_user/gi;
		$enter_ingroup_url =~ s/--A--vendor_id--B--/$VAR_vendor_lead_code/gi;
		$enter_ingroup_url =~ s/--A--vendor_lead_code--B--/$VAR_vendor_lead_code/gi;
		$enter_ingroup_url =~ s/--A--source_id--B--/$VAR_source_id/gi;
		$enter_ingroup_url =~ s/--A--list_id--B--/$VAR_list_id/gi;
		$enter_ingroup_url =~ s/--A--list_name--B--/$list_name/gi;
		$enter_ingroup_url =~ s/--A--list_description--B--/$list_description/gi;
		$enter_ingroup_url =~ s/--A--phone_code--B--/$VAR_phone_code/gi;
		$enter_ingroup_url =~ s/--A--phone_number--B--/$VAR_phone_number/gi;
		$enter_ingroup_url =~ s/--A--title--B--/$VAR_title/gi;
		$enter_ingroup_url =~ s/--A--first_name--B--/$VAR_first_name/gi;
		$enter_ingroup_url =~ s/--A--middle_initial--B--/$VAR_middle_initial/gi;
		$enter_ingroup_url =~ s/--A--last_name--B--/$VAR_last_name/gi;
		$enter_ingroup_url =~ s/--A--address1--B--/$VAR_address1/gi;
		$enter_ingroup_url =~ s/--A--address2--B--/$VAR_address2/gi;
		$enter_ingroup_url =~ s/--A--address3--B--/$VAR_address3/gi;
		$enter_ingroup_url =~ s/--A--city--B--/$VAR_city/gi;
		$enter_ingroup_url =~ s/--A--state--B--/$VAR_state/gi;
		$enter_ingroup_url =~ s/--A--province--B--/$VAR_province/gi;
		$enter_ingroup_url =~ s/--A--postal_code--B--/$VAR_postal_code/gi;
		$enter_ingroup_url =~ s/--A--country_code--B--/$VAR_country_code/gi;
		$enter_ingroup_url =~ s/--A--gender--B--/$VAR_gender/gi;
		$enter_ingroup_url =~ s/--A--date_of_birth--B--/$VAR_date_of_birth/gi;
		$enter_ingroup_url =~ s/--A--alt_phone--B--/$VAR_alt_phone/gi;
		$enter_ingroup_url =~ s/--A--email--B--/$VAR_email/gi;
		$enter_ingroup_url =~ s/--A--security_phrase--B--/$VAR_security_phrase/gi;
		$enter_ingroup_url =~ s/--A--comments--B--/$VAR_comments/gi;
		$enter_ingroup_url =~ s/--A--called_count--B--/$VAR_called_count/gi;
		$enter_ingroup_url =~ s/--A--last_local_call_time--B--/$VAR_last_local_call_time/gi;
		$enter_ingroup_url =~ s/--A--rank--B--/$VAR_rank/gi;
		$enter_ingroup_url =~ s/--A--owner--B--/$VAR_owner/gi;
		$enter_ingroup_url =~ s/--A--dialed_number--B--/$VAR_phone_number/gi;
		$enter_ingroup_url =~ s/--A--dialed_label--B--/$VAR_alt_dial/gi;
		$enter_ingroup_url =~ s/--A--user_custom_one--B--/$VAR_user_custom_one/gi;
		$enter_ingroup_url =~ s/--A--user_custom_two--B--/$VAR_user_custom_two/gi;
		$enter_ingroup_url =~ s/--A--user_custom_three--B--/$VAR_user_custom_three/gi;
		$enter_ingroup_url =~ s/--A--user_custom_four--B--/$VAR_user_custom_four/gi;
		$enter_ingroup_url =~ s/--A--user_custom_five--B--/$VAR_user_custom_five/gi;
		$enter_ingroup_url =~ s/--A--full_name--B--/$VAR_full_name/gi;
		$enter_ingroup_url =~ s/--A--launch_user--B--/$user/gi;
		$enter_ingroup_url =~ s/--A--did_id--B--/$VAR_did_id/gi;
		$enter_ingroup_url =~ s/--A--did_extension--B--/$VAR_did_extension/gi;
		$enter_ingroup_url =~ s/--A--did_pattern--B--/$VAR_did_pattern/gi;
		$enter_ingroup_url =~ s/--A--did_description--B--/$VAR_did_description/gi;
		$enter_ingroup_url =~ s/--A--closecallid--B--/$VAR_closecallid/gi;
		$enter_ingroup_url =~ s/--A--uniqueid--B--/$VAR_uniqueid/gi;
		$enter_ingroup_url =~ s/--A--call_id--B--/$VAR_call_id/gi;
		$enter_ingroup_url =~ s/--A--user_group--B--/$VAR_user_group/gi;
		$enter_ingroup_url =~ s/--A--campaign--B--/$VAR_campaign_id/gi;
		$enter_ingroup_url =~ s/--A--campaign_id--B--/$VAR_campaign_id/gi;
		$enter_ingroup_url =~ s/--A--group--B--/$VAR_campaign_id/gi;
		$enter_ingroup_url =~ s/--A--function--B--/$function/gi;
		$enter_ingroup_url =~ s/--A--SQLdate--B--/$now_date/gi;
		$enter_ingroup_url =~ s/ /+/gi;
		$enter_ingroup_url =~ s/&/\\&/gi;
		$parse_url = $enter_ingroup_url;

		$url_function = 'enter_ig';
		##### END ENTER_INGROUP_URL function #####
		}
	elsif ($function =~ /NA_CALL_URL/)
		{
		##### BEGIN NA Call URL function #####
		$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,phone_code FROM vicidial_list where lead_id=$lead_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_entry_date =		$aryA[1];
			$VAR_modify_date =		$aryA[2];
			$VAR_status =			$aryA[3];
			$VAR_vendor_lead_code =	$aryA[5];
			$VAR_source_id =		$aryA[6];
			$VAR_list_id =			$aryA[7];
			$VAR_title =			$aryA[9];
			$VAR_first_name =		$aryA[10];
			$VAR_middle_initial =	$aryA[11];
			$VAR_last_name =		$aryA[12];
			$VAR_address1 =			$aryA[13];
			$VAR_address2 =			$aryA[14];
			$VAR_address3 =			$aryA[15];
			$VAR_city =				$aryA[16];
			$VAR_state =			$aryA[17];
			$VAR_province =			$aryA[18];
			$VAR_postal_code =		$aryA[19];
			$VAR_country_code =		$aryA[20];
			$VAR_gender =			$aryA[21];
			$VAR_date_of_birth =	$aryA[22];
			$VAR_alt_phone =		$aryA[23];
			$VAR_email =			$aryA[24];
			$VAR_security_phrase =	$aryA[25];
			$VAR_comments =			$aryA[26];
			$VAR_called_count =		$aryA[27];
			$VAR_last_local_call_time = $aryA[28];
			$VAR_rank =				$aryA[29];
			$VAR_owner =			$aryA[30];
			$VAR_phone_code =		$aryA[31];
			}
		$sthA->finish();

		### BEGIN section if na_call_url is set to ALT ###
		if ($na_call_url =~ /^ALT$/)
			{
			$stmtA="SELECT url_rank,url_statuses,url_address,url_lists,url_call_length from vicidial_url_multi where campaign_id='$alt_id' and entry_type='$alt_type' and url_type='noagent' and active='Y' order by url_rank limit 1000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$naALTsthArows=$sthA->rows;
			$naALT=0;
			while ($naALTsthArows > $naALT)
				{
				@aryA = $sthA->fetchrow_array;
				$naALT_url_rank[$naALT] =			$aryA[0];
				$naALT_url_statuses[$naALT] =		" $aryA[1] ";
				$naALT_url_address[$naALT] =		$aryA[2];
				$naALT_url_lists[$naALT] =			$aryA[3];
				$naALT_url_call_length[$naALT] =	$aryA[4];
				$naALT_url_failed[$naALT] =		0;
				if ( ($naALT_url_statuses[$naALT] != '---ALL---') && ($naALT_url_statuses[$naALT] !~ / $status /i) )
					{
					if ($DBX) {print "   No-Agent Call URL entry $naALT failed status qualifier: |$status|$naALT_url_statuses[$naALT]|\n";}
					$naALT_url_failed[$naALT]++;
					}
				if ( ($naALT_url_lists[$naALT] > 2) && ($naALT_url_lists[$naALT] !~ / $list_id /i) )
					{
					if ($DBX) {print "   No-Agent Call URL entry $naALT failed list qualifier: |$list_id|$naALT_url_lists[$naALT]|\n";}
					$naALT_url_failed[$naALT]++;
					}
				$naALT++;
				}
			}
		else
			{
			$naALT_url_rank[0] =		1;
			$naALT_url_statuses[0] =	'---ALL---';
			$naALT_url_address[0] =		$na_call_url;
			$naALT_url_lists[0] =		'';
			$naALT_url_call_length[0] =	0;
			$naALT_url_failed[0] =		0;
			$naALTsthArows=1;
			$naALT++;
			}
		### END section if na_call_url is set to ALT ###
		if ($DBX) {print "DEBUG: NCU - $naALT|$alt_type|$alt_id|$na_call_url|\n";}

		$naALT=0;
		while ($naALTsthArows > $naALT)
			{
			if ($naALT_url_failed[$naALT] < 1)
				{
				if ($DB) {print "Runnning No-Agent Call URL entry $naALT:\n";}

				if ($naALT_url_address[$naALT] =~ /--A--user_custom_|--A--full_name--B--/)
					{
					$stmtA = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five,user_group,full_name from vicidial_users where user='$user';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_user_custom_one =		$aryA[0];
						$VAR_user_custom_two =		$aryA[1];
						$VAR_user_custom_three =	$aryA[2];
						$VAR_user_custom_four =		$aryA[3];
						$VAR_user_custom_five =		$aryA[4];
						$VAR_user_group =			$aryA[5];
						$VAR_full_name =			$aryA[6];
						}
					}

				if ($naALT_url_address[$naALT] =~ /--A--did_/)
					{
					$stmtA = "SELECT did_id,extension FROM vicidial_did_log where uniqueid='$uniqueid';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_did_id =			$aryA[0];
						$VAR_did_extension =	$aryA[1];
						}
					$sthA->finish();

					$stmtA = "SELECT did_pattern,did_description FROM vicidial_inbound_dids where did_id='$VAR_did_id';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_did_pattern =		$aryA[0];
						$VAR_did_description =	$aryA[1];
						}
					$sthA->finish();
					}

				if ($naALT_url_address[$naALT] =~ /--A--closecallid--B--/)
					{
					$stmtA = "SELECT closecallid FROM vicidial_closer_log where uniqueid='$uniqueid' order by closecallid limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_closecallid =		$aryA[0];
						}
					$sthA->finish();
					}

				if ( (length($VAR_list_id) > 1) && ( ( (length($list_name) < 1) && ($naALT_url_address[$naALT] =~ /--A--list_name--B--/) ) || ( (length($list_description) < 1) && ($naALT_url_address[$naALT] =~ /--A--list_description--B--/) ) ) )
					{
					$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$VAR_list_id';";
					$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
					$list_name_ct=$sthA->rows;
					if ($list_name_ct > 0)
						{
						@aryA = $sthA->fetchrow_array;
						if (length($aryA[0])>0) 
							{
							$list_name =		$aryA[0];
							}
						if (length($aryA[1])>0) 
							{
							$list_description =	$aryA[1];
							}
						}
					$sthA->finish();
					}

				$naALT_url_address[$naALT] =~ s/^VAR//gi;
				$naALT_url_address[$naALT] =~ s/--A--lead_id--B--/$VAR_lead_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--entry_date--B--/$VAR_entry_date/gi;
				$naALT_url_address[$naALT] =~ s/--A--modify_date--B--/$VAR_modify_date/gi;
				$naALT_url_address[$naALT] =~ s/--A--status--B--/$status/gi;
				$naALT_url_address[$naALT] =~ s/--A--dispo--B--/$status/gi;
				$naALT_url_address[$naALT] =~ s/--A--user--B--/$VAR_user/gi;
				$naALT_url_address[$naALT] =~ s/--A--vendor_id--B--/$VAR_vendor_lead_code/gi;
				$naALT_url_address[$naALT] =~ s/--A--vendor_lead_code--B--/$VAR_vendor_lead_code/gi;
				$naALT_url_address[$naALT] =~ s/--A--source_id--B--/$VAR_source_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--list_id--B--/$VAR_list_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--list_name--B--/$list_name/gi;
				$naALT_url_address[$naALT] =~ s/--A--list_description--B--/$list_description/gi;
				$naALT_url_address[$naALT] =~ s/--A--phone_code--B--/$VAR_phone_code/gi;
				$naALT_url_address[$naALT] =~ s/--A--phone_number--B--/$VAR_phone_number/gi;
				$naALT_url_address[$naALT] =~ s/--A--title--B--/$VAR_title/gi;
				$naALT_url_address[$naALT] =~ s/--A--first_name--B--/$VAR_first_name/gi;
				$naALT_url_address[$naALT] =~ s/--A--middle_initial--B--/$VAR_middle_initial/gi;
				$naALT_url_address[$naALT] =~ s/--A--last_name--B--/$VAR_last_name/gi;
				$naALT_url_address[$naALT] =~ s/--A--address1--B--/$VAR_address1/gi;
				$naALT_url_address[$naALT] =~ s/--A--address2--B--/$VAR_address2/gi;
				$naALT_url_address[$naALT] =~ s/--A--address3--B--/$VAR_address3/gi;
				$naALT_url_address[$naALT] =~ s/--A--city--B--/$VAR_city/gi;
				$naALT_url_address[$naALT] =~ s/--A--state--B--/$VAR_state/gi;
				$naALT_url_address[$naALT] =~ s/--A--province--B--/$VAR_province/gi;
				$naALT_url_address[$naALT] =~ s/--A--postal_code--B--/$VAR_postal_code/gi;
				$naALT_url_address[$naALT] =~ s/--A--country_code--B--/$VAR_country_code/gi;
				$naALT_url_address[$naALT] =~ s/--A--gender--B--/$VAR_gender/gi;
				$naALT_url_address[$naALT] =~ s/--A--date_of_birth--B--/$VAR_date_of_birth/gi;
				$naALT_url_address[$naALT] =~ s/--A--alt_phone--B--/$VAR_alt_phone/gi;
				$naALT_url_address[$naALT] =~ s/--A--email--B--/$VAR_email/gi;
				$naALT_url_address[$naALT] =~ s/--A--security_phrase--B--/$VAR_security_phrase/gi;
				$naALT_url_address[$naALT] =~ s/--A--comments--B--/$VAR_comments/gi;
				$naALT_url_address[$naALT] =~ s/--A--called_count--B--/$VAR_called_count/gi;
				$naALT_url_address[$naALT] =~ s/--A--last_local_call_time--B--/$VAR_last_local_call_time/gi;
				$naALT_url_address[$naALT] =~ s/--A--rank--B--/$VAR_rank/gi;
				$naALT_url_address[$naALT] =~ s/--A--owner--B--/$VAR_owner/gi;
				$naALT_url_address[$naALT] =~ s/--A--dialed_number--B--/$VAR_phone_number/gi;
				$naALT_url_address[$naALT] =~ s/--A--dialed_label--B--/$VAR_alt_dial/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_custom_one--B--/$VAR_user_custom_one/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_custom_two--B--/$VAR_user_custom_two/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_custom_three--B--/$VAR_user_custom_three/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_custom_four--B--/$VAR_user_custom_four/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_custom_five--B--/$VAR_user_custom_five/gi;
				$naALT_url_address[$naALT] =~ s/--A--full_name--B--/$VAR_full_name/gi;
				$naALT_url_address[$naALT] =~ s/--A--launch_user--B--/$user/gi;
				$naALT_url_address[$naALT] =~ s/--A--did_id--B--/$VAR_did_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--did_extension--B--/$VAR_did_extension/gi;
				$naALT_url_address[$naALT] =~ s/--A--did_pattern--B--/$VAR_did_pattern/gi;
				$naALT_url_address[$naALT] =~ s/--A--did_description--B--/$VAR_did_description/gi;
				$naALT_url_address[$naALT] =~ s/--A--closecallid--B--/$VAR_closecallid/gi;
				$naALT_url_address[$naALT] =~ s/--A--uniqueid--B--/$VAR_uniqueid/gi;
				$naALT_url_address[$naALT] =~ s/--A--call_id--B--/$VAR_call_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--user_group--B--/$VAR_user_group/gi;
				$naALT_url_address[$naALT] =~ s/--A--campaign--B--/$VAR_campaign_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--campaign_id--B--/$VAR_campaign_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--group--B--/$VAR_campaign_id/gi;
				$naALT_url_address[$naALT] =~ s/--A--function--B--/$function/gi;
				$naALT_url_address[$naALT] =~ s/--A--SQLdate--B--/$now_date/gi;
				$naALT_url_address[$naALT] =~ s/ /+/gi;
				$naALT_url_address[$naALT] =~ s/&/\\&/gi;
				$parse_url = $naALT_url_address[$naALT];

				$url_function = 'na_callurl';

				### insert a new url log entry
				$SQL_log = "$parse_url";
				$SQL_log =~ s/;|\||\\//gi;
				$stmtA = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$now_date',url_type='$url_function',url='$SQL_log',url_response='';";
				$affected_rows = $dbhA->do($stmtA);
				$stmtB = "SELECT LAST_INSERT_ID() LIMIT 1;";
				$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$url_id = $aryA[0];
					}
				$sthA->finish();

				$url = $parse_url;
				$url =~ s/'/\\'/gi;
				$url =~ s/"/\\"/gi;

				my $secW = time();

				# disconnect from the database to free up the DB connection
				$dbhA->disconnect();

				# request the web URL
				`$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url `;

				# reconnect to the database to log response and response time
				$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
				 or die "Couldn't connect to database: " . DBI->errstr;

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
				if ($DB) {print "Not runnning No-Agent Call URL entry $naALT, it failed qualifiers\n";}
				}
			$naALT++;
			}
		my $secZ = time();
		my $script_time = ($secZ - $secX);
		if ($DB) {print "DONE execute time: $script_time seconds\n";}

		exit 0;
		}
	##### END NA Call URL function #####

	elsif ($function =~ /SC_CALL_URL/)
		{
		$talk_sec=0;   $dead_sec=0;   $dispo_epoch=time();   $now_epoch=time();
		##### BEGIN Settings Container Call URL function #####
		$stmtA = "SELECT user,status,talk_sec,dead_sec,dispo_epoch from vicidial_agent_log where lead_id=$lead_id order by agent_log_id desc limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_user =		$aryA[0];
			$VAR_dispo =	$aryA[1];
			$talk_sec =		$aryA[2];
			$dead_sec =		$aryA[3];
			$dispo_epoch =	$aryA[4];
			}
		$ivr_time = ($now_epoch - $dispo_epoch);
		$talk_time = ($talk_sec - $dead_sec);
		if ($talk_time < 1)
			{$talk_time = 0;}
		else
			{$talk_time_min = ($talk_time / 60);   $talk_time_min = sprintf("%.0f", $talk_time_min);}

		$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,phone_code FROM vicidial_list where lead_id=$lead_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_entry_date =		$aryA[1];
			$VAR_modify_date =		$aryA[2];
			$VAR_status =			$aryA[3];
			$VAR_vendor_lead_code =	$aryA[5];
			$VAR_source_id =		$aryA[6];
			$VAR_list_id =			$aryA[7];
			$VAR_phone_number =		$aryA[8];
			$VAR_title =			$aryA[9];
			$VAR_first_name =		$aryA[10];
			$VAR_middle_initial =	$aryA[11];
			$VAR_last_name =		$aryA[12];
			$VAR_address1 =			$aryA[13];
			$VAR_address2 =			$aryA[14];
			$VAR_address3 =			$aryA[15];
			$VAR_city =				$aryA[16];
			$VAR_state =			$aryA[17];
			$VAR_province =			$aryA[18];
			$VAR_postal_code =		$aryA[19];
			$VAR_country_code =		$aryA[20];
			$VAR_gender =			$aryA[21];
			$VAR_date_of_birth =	$aryA[22];
			$VAR_alt_phone =		$aryA[23];
			$VAR_email =			$aryA[24];
			$VAR_security_phrase =	$aryA[25];
			$VAR_comments =			$aryA[26];
			$VAR_called_count =		$aryA[27];
			$VAR_last_local_call_time = $aryA[28];
			$VAR_rank =				$aryA[29];
			$VAR_owner =			$aryA[30];
			$VAR_phone_code =		$aryA[31];
			}
		$sthA->finish();

		if (length($VAR_dispo) < 1) {$VAR_dispo = $VAR_status;}

		if ($sc_call_url =~ /--A--user_custom_|--A--full_name--B--/)
			{
			$stmtA = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five,user_group,full_name from vicidial_users where user='$user';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_user_custom_one =		$aryA[0];
				$VAR_user_custom_two =		$aryA[1];
				$VAR_user_custom_three =	$aryA[2];
				$VAR_user_custom_four =		$aryA[3];
				$VAR_user_custom_five =		$aryA[4];
				$VAR_user_group =			$aryA[5];
				$VAR_full_name =			$aryA[6];
				}
			}

		if ($sc_call_url =~ /--A--did_/)
			{
			$stmtA = "SELECT did_id,extension FROM vicidial_did_log where uniqueid='$uniqueid';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_did_id =			$aryA[0];
				$VAR_did_extension =	$aryA[1];
				}
			$sthA->finish();

			$stmtA = "SELECT did_pattern,did_description FROM vicidial_inbound_dids where did_id='$VAR_did_id';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_did_pattern =		$aryA[0];
				$VAR_did_description =	$aryA[1];
				}
			$sthA->finish();
			}

		if ($sc_call_url =~ /--A--closecallid--B--/)
			{
			$stmtA = "SELECT closecallid FROM vicidial_closer_log where uniqueid='$uniqueid' order by closecallid limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$VAR_closecallid =		$aryA[0];
				}
			$sthA->finish();
			}

		if ( (length($VAR_list_id) > 1) && ( ( (length($list_name) < 1) && ($sc_call_url =~ /--A--list_name--B--/) ) || ( (length($list_description) < 1) && ($sc_call_url =~ /--A--list_description--B--/) ) ) )
			{
			$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$VAR_list_id';";
			$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
			$list_name_ct=$sthA->rows;
			if ($list_name_ct > 0)
				{
				@aryA = $sthA->fetchrow_array;
				if (length($aryA[0])>0) 
					{
					$list_name =		$aryA[0];
					}
				if (length($aryA[1])>0) 
					{
					$list_description =	$aryA[1];
					}
				}
			$sthA->finish();
			}

		$sc_call_url =~ s/^VAR//gi;
		$sc_call_url =~ s/--A--lead_id--B--/$VAR_lead_id/gi;
		$sc_call_url =~ s/--A--entry_date--B--/$VAR_entry_date/gi;
		$sc_call_url =~ s/--A--modify_date--B--/$VAR_modify_date/gi;
		$sc_call_url =~ s/--A--status--B--/$VAR_dispo/gi;
		$sc_call_url =~ s/--A--dispo--B--/$VAR_dispo/gi;
		$sc_call_url =~ s/--A--user--B--/$VAR_user/gi;
		$sc_call_url =~ s/--A--vendor_id--B--/$VAR_vendor_lead_code/gi;
		$sc_call_url =~ s/--A--vendor_lead_code--B--/$VAR_vendor_lead_code/gi;
		$sc_call_url =~ s/--A--source_id--B--/$VAR_source_id/gi;
		$sc_call_url =~ s/--A--list_id--B--/$VAR_list_id/gi;
		$sc_call_url =~ s/--A--list_name--B--/$list_name/gi;
		$sc_call_url =~ s/--A--list_description--B--/$list_description/gi;
		$sc_call_url =~ s/--A--phone_code--B--/$VAR_phone_code/gi;
		$sc_call_url =~ s/--A--phone_number--B--/$VAR_phone_number/gi;
		$sc_call_url =~ s/--A--title--B--/$VAR_title/gi;
		$sc_call_url =~ s/--A--first_name--B--/$VAR_first_name/gi;
		$sc_call_url =~ s/--A--middle_initial--B--/$VAR_middle_initial/gi;
		$sc_call_url =~ s/--A--last_name--B--/$VAR_last_name/gi;
		$sc_call_url =~ s/--A--address1--B--/$VAR_address1/gi;
		$sc_call_url =~ s/--A--address2--B--/$VAR_address2/gi;
		$sc_call_url =~ s/--A--address3--B--/$VAR_address3/gi;
		$sc_call_url =~ s/--A--city--B--/$VAR_city/gi;
		$sc_call_url =~ s/--A--state--B--/$VAR_state/gi;
		$sc_call_url =~ s/--A--province--B--/$VAR_province/gi;
		$sc_call_url =~ s/--A--postal_code--B--/$VAR_postal_code/gi;
		$sc_call_url =~ s/--A--country_code--B--/$VAR_country_code/gi;
		$sc_call_url =~ s/--A--gender--B--/$VAR_gender/gi;
		$sc_call_url =~ s/--A--date_of_birth--B--/$VAR_date_of_birth/gi;
		$sc_call_url =~ s/--A--alt_phone--B--/$VAR_alt_phone/gi;
		$sc_call_url =~ s/--A--email--B--/$VAR_email/gi;
		$sc_call_url =~ s/--A--security_phrase--B--/$VAR_security_phrase/gi;
		$sc_call_url =~ s/--A--comments--B--/$VAR_comments/gi;
		$sc_call_url =~ s/--A--called_count--B--/$VAR_called_count/gi;
		$sc_call_url =~ s/--A--last_local_call_time--B--/$VAR_last_local_call_time/gi;
		$sc_call_url =~ s/--A--rank--B--/$VAR_rank/gi;
		$sc_call_url =~ s/--A--owner--B--/$VAR_owner/gi;
		$sc_call_url =~ s/--A--dialed_number--B--/$VAR_phone_number/gi;
		$sc_call_url =~ s/--A--dialed_label--B--/$VAR_alt_dial/gi;
		$sc_call_url =~ s/--A--user_custom_one--B--/$VAR_user_custom_one/gi;
		$sc_call_url =~ s/--A--user_custom_two--B--/$VAR_user_custom_two/gi;
		$sc_call_url =~ s/--A--user_custom_three--B--/$VAR_user_custom_three/gi;
		$sc_call_url =~ s/--A--user_custom_four--B--/$VAR_user_custom_four/gi;
		$sc_call_url =~ s/--A--user_custom_five--B--/$VAR_user_custom_five/gi;
		$sc_call_url =~ s/--A--full_name--B--/$VAR_full_name/gi;
		$sc_call_url =~ s/--A--launch_user--B--/$user/gi;
		$sc_call_url =~ s/--A--did_id--B--/$VAR_did_id/gi;
		$sc_call_url =~ s/--A--did_extension--B--/$VAR_did_extension/gi;
		$sc_call_url =~ s/--A--did_pattern--B--/$VAR_did_pattern/gi;
		$sc_call_url =~ s/--A--did_description--B--/$VAR_did_description/gi;
		$sc_call_url =~ s/--A--closecallid--B--/$VAR_closecallid/gi;
		$sc_call_url =~ s/--A--uniqueid--B--/$VAR_uniqueid/gi;
		$sc_call_url =~ s/--A--call_id--B--/$VAR_call_id/gi;
		$sc_call_url =~ s/--A--user_group--B--/$VAR_user_group/gi;
		$sc_call_url =~ s/--A--campaign--B--/$VAR_campaign_id/gi;
		$sc_call_url =~ s/--A--campaign_id--B--/$VAR_campaign_id/gi;
		$sc_call_url =~ s/--A--group--B--/$VAR_campaign_id/gi;
		$sc_call_url =~ s/--A--function--B--/$function/gi;
		$sc_call_url =~ s/--A--ivr_time--B--/$ivr_time/gi;
		$sc_call_url =~ s/--A--talk_time--B--/$talk_time/gi;
		$sc_call_url =~ s/--A--talk_time_min--B--/$talk_time_min/gi;
		$sc_call_url =~ s/--A--SQLdate--B--/$now_date/gi;
		if ($function !~ /POST$/) {$sc_call_url =~ s/ /+/gi;}
		$sc_call_url =~ s/&/\\&/gi;
		$parse_url = $sc_call_url;

		$url_function = 'sc_callurl';
		##### END Settings Container Call URL function #####
		}
	else
		{
		##### BEGIN Start Call URL function #####
		$stmtA = "SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,phone_code FROM vicidial_list where lead_id=$lead_id;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$VAR_entry_date =		$aryA[1];
			$VAR_modify_date =		$aryA[2];
			$VAR_status =			$aryA[3];
			$VAR_vendor_lead_code =	$aryA[5];
			$VAR_source_id =		$aryA[6];
			$VAR_list_id =			$aryA[7];
			$VAR_title =			$aryA[9];
			$VAR_first_name =		$aryA[10];
			$VAR_middle_initial =	$aryA[11];
			$VAR_last_name =		$aryA[12];
			$VAR_address1 =			$aryA[13];
			$VAR_address2 =			$aryA[14];
			$VAR_address3 =			$aryA[15];
			$VAR_city =				$aryA[16];
			$VAR_state =			$aryA[17];
			$VAR_province =			$aryA[18];
			$VAR_postal_code =		$aryA[19];
			$VAR_country_code =		$aryA[20];
			$VAR_gender =			$aryA[21];
			$VAR_date_of_birth =	$aryA[22];
			$VAR_alt_phone =		$aryA[23];
			$VAR_email =			$aryA[24];
			$VAR_security_phrase =	$aryA[25];
			$VAR_comments =			$aryA[26];
			$VAR_called_count =		$aryA[27];
			$VAR_last_local_call_time = $aryA[28];
			$VAR_rank =				$aryA[29];
			$VAR_owner =			$aryA[30];
			$VAR_phone_code =		$aryA[31];
			}
		$sthA->finish();

		### BEGIN section if start_call_url is set to ALT ###
		if ($start_call_url =~ /^ALT$/)
			{
			$stmtA="SELECT url_rank,url_statuses,url_address,url_lists from vicidial_url_multi where campaign_id='$alt_id' and entry_type='$alt_type' and url_type='start' and active='Y' order by url_rank limit 1000;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$scALTsthArows=$sthA->rows;
			$scALT=0;
			while ($scALTsthArows > $scALT)
				{
				@aryA = $sthA->fetchrow_array;
				$scALT_url_rank[$scALT] =			$aryA[0];
				$scALT_url_statuses[$scALT] =		" $aryA[1] ";
				$scALT_url_address[$scALT] =		$aryA[2];
				$scALT_url_lists[$scALT] =			$aryA[3];
				$scALT_url_failed[$scALT] =		0;
				if ( ($scALT_url_lists[$scALT] > 2) && ($scALT_url_lists[$scALT] !~ / $list_id /i) )
					{
					if ($DBX) {print "   Start Call URL entry $scALT failed list qualifier: |$list_id|$scALT_url_lists[$scALT]|\n";}
					$scALT_url_failed[$scALT]++;
					}
				$scALT++;
				}
			}
		else
			{
			$scALT_url_rank[0] =		1;
			$scALT_url_statuses[0] =	'---ALL---';
			$scALT_url_address[0] =		$start_call_url;
			$scALT_url_lists[0] =		'';
			$scALT_url_failed[0] =		0;
			$scALTsthArows=1;
			$scALT++;
			}
		### END section if start_call_url is set to ALT ###
		if ($DBX) {print "DEBUG: NCU - $scALT|$alt_type|$alt_id|$start_call_url|\n";}

		$scALT=0;
		while ($scALTsthArows > $scALT)
			{
			if (length($scALT_url_address[$scALT]) > 5)
				{
				if ($scALT_url_address[$scALT] =~ /--A--user_custom_|--A--full_name--B--/)
					{
					$stmtA = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five,user_group,full_name from vicidial_users where user='$user';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_user_custom_one =		$aryA[0];
						$VAR_user_custom_two =		$aryA[1];
						$VAR_user_custom_three =	$aryA[2];
						$VAR_user_custom_four =		$aryA[3];
						$VAR_user_custom_five =		$aryA[4];
						$VAR_user_group =			$aryA[5];
						$VAR_full_name =			$aryA[6];
						}
					}

				if ($scALT_url_address[$scALT] =~ /--A--did_/)
					{
					$stmtA = "SELECT did_id,extension FROM vicidial_did_log where uniqueid='$uniqueid';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_did_id =			$aryA[0];
						$VAR_did_extension =	$aryA[1];
						}
					$sthA->finish();

					$stmtA = "SELECT did_pattern,did_description FROM vicidial_inbound_dids where did_id='$VAR_did_id';";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_did_pattern =		$aryA[0];
						$VAR_did_description =	$aryA[1];
						}
					$sthA->finish();
					}

				if ($scALT_url_address[$scALT] =~ /--A--closecallid--B--/)
					{
					$stmtA = "SELECT closecallid FROM vicidial_closer_log where uniqueid='$uniqueid' order by closecallid limit 1;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$VAR_closecallid =		$aryA[0];
						}
					$sthA->finish();
					}

				if ( (length($VAR_list_id) > 1) && ( ( (length($list_name) < 1) && ($scALT_url_address[$scALT] =~ /--A--list_name--B--/) ) || ( (length($list_description) < 1) && ($scALT_url_address[$scALT] =~ /--A--list_description--B--/) ) ) )
					{
					$stmtH = "SELECT list_name,list_description FROM vicidial_lists where list_id='$VAR_list_id';";
					$sthA = $dbhA->prepare($stmtH) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtH ", $dbhA->errstr;
					$list_name_ct=$sthA->rows;
					if ($list_name_ct > 0)
						{
						@aryA = $sthA->fetchrow_array;
						if (length($aryA[0])>0) 
							{
							$list_name =		$aryA[0];
							}
						if (length($aryA[1])>0) 
							{
							$list_description =	$aryA[1];
							}
						}
					$sthA->finish();
					}

				$scALT_url_address[$scALT] =~ s/^VAR//gi;
				$scALT_url_address[$scALT] =~ s/--A--lead_id--B--/$VAR_lead_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--entry_date--B--/$VAR_entry_date/gi;
				$scALT_url_address[$scALT] =~ s/--A--modify_date--B--/$VAR_modify_date/gi;
				$scALT_url_address[$scALT] =~ s/--A--status--B--/$VAR_status/gi;
				$scALT_url_address[$scALT] =~ s/--A--user--B--/$VAR_user/gi;
				$scALT_url_address[$scALT] =~ s/--A--vendor_id--B--/$VAR_vendor_lead_code/gi;
				$scALT_url_address[$scALT] =~ s/--A--vendor_lead_code--B--/$VAR_vendor_lead_code/gi;
				$scALT_url_address[$scALT] =~ s/--A--source_id--B--/$VAR_source_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--list_id--B--/$VAR_list_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--list_name--B--/$list_name/gi;
				$scALT_url_address[$scALT] =~ s/--A--list_description--B--/$list_description/gi;
				$scALT_url_address[$scALT] =~ s/--A--phone_code--B--/$VAR_phone_code/gi;
				$scALT_url_address[$scALT] =~ s/--A--phone_number--B--/$VAR_phone_number/gi;
				$scALT_url_address[$scALT] =~ s/--A--title--B--/$VAR_title/gi;
				$scALT_url_address[$scALT] =~ s/--A--first_name--B--/$VAR_first_name/gi;
				$scALT_url_address[$scALT] =~ s/--A--middle_initial--B--/$VAR_middle_initial/gi;
				$scALT_url_address[$scALT] =~ s/--A--last_name--B--/$VAR_last_name/gi;
				$scALT_url_address[$scALT] =~ s/--A--address1--B--/$VAR_address1/gi;
				$scALT_url_address[$scALT] =~ s/--A--address2--B--/$VAR_address2/gi;
				$scALT_url_address[$scALT] =~ s/--A--address3--B--/$VAR_address3/gi;
				$scALT_url_address[$scALT] =~ s/--A--city--B--/$VAR_city/gi;
				$scALT_url_address[$scALT] =~ s/--A--state--B--/$VAR_state/gi;
				$scALT_url_address[$scALT] =~ s/--A--province--B--/$VAR_province/gi;
				$scALT_url_address[$scALT] =~ s/--A--postal_code--B--/$VAR_postal_code/gi;
				$scALT_url_address[$scALT] =~ s/--A--country_code--B--/$VAR_country_code/gi;
				$scALT_url_address[$scALT] =~ s/--A--gender--B--/$VAR_gender/gi;
				$scALT_url_address[$scALT] =~ s/--A--date_of_birth--B--/$VAR_date_of_birth/gi;
				$scALT_url_address[$scALT] =~ s/--A--alt_phone--B--/$VAR_alt_phone/gi;
				$scALT_url_address[$scALT] =~ s/--A--email--B--/$VAR_email/gi;
				$scALT_url_address[$scALT] =~ s/--A--security_phrase--B--/$VAR_security_phrase/gi;
				$scALT_url_address[$scALT] =~ s/--A--comments--B--/$VAR_comments/gi;
				$scALT_url_address[$scALT] =~ s/--A--called_count--B--/$VAR_called_count/gi;
				$scALT_url_address[$scALT] =~ s/--A--last_local_call_time--B--/$VAR_last_local_call_time/gi;
				$scALT_url_address[$scALT] =~ s/--A--rank--B--/$VAR_rank/gi;
				$scALT_url_address[$scALT] =~ s/--A--owner--B--/$VAR_owner/gi;
				$scALT_url_address[$scALT] =~ s/--A--dialed_number--B--/$VAR_phone_number/gi;
				$scALT_url_address[$scALT] =~ s/--A--dialed_label--B--/$VAR_alt_dial/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_custom_one--B--/$VAR_user_custom_one/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_custom_two--B--/$VAR_user_custom_two/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_custom_three--B--/$VAR_user_custom_three/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_custom_four--B--/$VAR_user_custom_four/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_custom_five--B--/$VAR_user_custom_five/gi;
				$scALT_url_address[$scALT] =~ s/--A--full_name--B--/$VAR_full_name/gi;
				$scALT_url_address[$scALT] =~ s/--A--launch_user--B--/$user/gi;
				$scALT_url_address[$scALT] =~ s/--A--did_id--B--/$VAR_did_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--did_extension--B--/$VAR_did_extension/gi;
				$scALT_url_address[$scALT] =~ s/--A--did_pattern--B--/$VAR_did_pattern/gi;
				$scALT_url_address[$scALT] =~ s/--A--did_description--B--/$VAR_did_description/gi;
				$scALT_url_address[$scALT] =~ s/--A--closecallid--B--/$VAR_closecallid/gi;
				$scALT_url_address[$scALT] =~ s/--A--uniqueid--B--/$VAR_uniqueid/gi;
				$scALT_url_address[$scALT] =~ s/--A--call_id--B--/$VAR_call_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--user_group--B--/$VAR_user_group/gi;
				$scALT_url_address[$scALT] =~ s/--A--campaign--B--/$VAR_campaign_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--campaign_id--B--/$VAR_campaign_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--group--B--/$VAR_campaign_id/gi;
				$scALT_url_address[$scALT] =~ s/--A--function--B--/$function/gi;
				$scALT_url_address[$scALT] =~ s/--A--SQLdate--B--/$now_date/gi;
				$scALT_url_address[$scALT] =~ s/ /+/gi;
				$scALT_url_address[$scALT] =~ s/&/\\&/gi;
				$parse_url = $scALT_url_address[$scALT];

				if ($function =~ /REMOTE_AGENT_START_CALL_URL/)
					{
					$url_function = 'start_ra';
					
					$stmtA="UPDATE vicidial_log_extended set start_url_processed='Y' where uniqueid='$uniqueid';";
					$affected_rows = $dbhA->do($stmtA);
					}

				### insert a new url log entry
				$SQL_log = "$parse_url";
				$SQL_log =~ s/;|\||\\//gi;
				$stmtA = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$now_date',url_type='$url_function',url='$SQL_log',url_response='';";
				$affected_rows = $dbhA->do($stmtA);
				$stmtB = "SELECT LAST_INSERT_ID() LIMIT 1;";
				$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$url_id = $aryA[0];
					}
				$sthA->finish();

				$url = $parse_url;
				$url =~ s/'/\\'/gi;
				$url =~ s/"/\\"/gi;

				my $secW = time();

				# disconnect from the database to free up the DB connection
				$dbhA->disconnect();

				# request the web URL
				`$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url `;

				# reconnect to the database to log response and response time
				$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
				 or die "Couldn't connect to database: " . DBI->errstr;

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
				if ($DB) {print "Not runnning Start Call URL entry $scALT, it failed qualifiers\n";}
				}
			##### END Call URL function #####
			$scALT++;
			}

		my $secZ = time();
		my $script_time = ($secZ - $secX);
		if ($DB) {print "DONE execute time: $script_time seconds\n";}

		exit 0;
		}

	### insert a new url log entry
	$SQL_log = "$parse_url";
	$SQL_log =~ s/;|\||\\//gi;
	$stmtA = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$now_date',url_type='$url_function',url='$SQL_log',url_response='';";
	$affected_rows = $dbhA->do($stmtA);
	$stmtB = "SELECT LAST_INSERT_ID() LIMIT 1;";
	$sthA = $dbhA->prepare($stmtB) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$url_id = $aryA[0];
		}
	$sthA->finish();

	$url = $parse_url;
	$url =~ s/'/\\'/gi;
	$url =~ s/"/\\"/gi;

	$post_data='';   $post_note='';
	if ( ($function =~ /POST$/) && ( ($url =~ /\?/) || (length($post_headers) > 5) ) )
		{
		@url_split = split(/\?/,$url);
		$url =		$url_split[0];
		$postvars = $url_split[1];
		$post_data = "--post-data=\"$postvars\"";
		if ($DB) {print "Send-as-POST: |$url|$post_data| \n";}
		$post_note="|HTTP-POST|$post_headers";
		}

	my $secW = time();

	# disconnect from the database to free up the DB connection
	$dbhA->disconnect();

	# request the web URL
	`$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url $post_data $post_headers `;

	# $post_note .= "|$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url $post_data  $post_headers ";

	# reconnect to the database to log response and response time
	$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	 or die "Couldn't connect to database: " . DBI->errstr;

	$event_string="$function|$wgetbin --no-check-certificate --output-document=/tmp/ASUBtmpD$US$url_id$US$secX --output-file=/tmp/ASUBtmpF$US$url_id$US$secX $url $post_data|";
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
	$stmtA = "UPDATE vicidial_url_log SET url_response='$Wdocline_cat|$Wfileline_cat$post_note',response_sec='$response_sec' where url_log_id='$url_id';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DB) {print "$affected_rows|$stmtA\n";}
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
	if (!$ASULOGfile) {$ASULOGfile = "$PATHlogs/sendurl.$log_date";}

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

