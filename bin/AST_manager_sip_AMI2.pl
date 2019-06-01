#!/usr/bin/perl
#
# AST_manager_sip_AMI2.pl version 2.14
#
# DESCRIPTION:
# connects to the Asterisk Manager Interface version 2 and inserts records into 
# the vicidial_sip_log for SIPInvite and SIPResponse events.
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 190528-1304 - Copied AST_manager_listen_AMI2.pl to make the initial version
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$DBX=0;
$full_listen_log=0; # set to 1 to log all output to log file
$run_check=1; # concurrency check
$last_keepalive_epoch = time();
$keepalive_skips=0;
$ami_user='sipcron';

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
		print "  [--test] = test\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = Extra-verbose debug messages\n";
		print "  [--help] = this screen\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			print "\n----- DEBUGGING ENABLED -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER-DUPER DEBUGGING -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		}
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
	if ($line =~ /^PATHhome/)	{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ($line =~ /^PATHlogs/)	{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ($line =~ /^PATHagi/)	{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ($line =~ /^PATHweb/)	{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ($line =~ /^PATHsounds/)	{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ($line =~ /^PATHmonitor/)	{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ($line =~ /^VARserver_ip/)	{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ($line =~ /^VARDB_server/)	{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ($line =~ /^VARDB_database/)	{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ($line =~ /^VARDB_user/)	{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_pass/)	{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_port/)	{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

&get_time_now;

#use lib './lib', '../lib';
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Net::Telnet ();
use Switch;
#use String::Escape qw( backslash unbackslash );

$module = 'String::Escape qw( backslash unbackslash )';
$bs_loaded=0;
if (try_load($module)) 
	{
	$bs_loaded=1;
	}

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,vd_server_logs,asterisk_version FROM servers where server_ip = '$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DBtelnet_host	=		$aryA[0];
	$DBtelnet_port	=		$aryA[1];
	$DBASTmgrUSERNAME =		$aryA[2];
	$DBASTmgrSECRET	=		$aryA[3];
	$DBASTmgrUSERNAMEupdate	=	$aryA[4];
	$DBASTmgrUSERNAMElisten	=	$aryA[5];
	$DBASTmgrUSERNAMEsend	=	$aryA[6];
	$DBmax_vicidial_trunks	=	$aryA[7];
	$DBanswer_transfer_agent=	$aryA[8];
	$DBSERVER_GMT		=	$aryA[9];
	$DBext_context		=	$aryA[10];
	$DBvd_server_logs 	=	$aryA[11];
	$asterisk_version 	=	$aryA[12];
	if ($DBtelnet_host)		{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)		{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)		{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)		{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMElisten)	{$ASTmgrUSERNAMElisten = $DBASTmgrUSERNAMElisten;}
	if ($DBASTmgrUSERNAMEsend)	{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	if ($DBmax_vicidial_trunks)	{$max_vicidial_trunks = $DBmax_vicidial_trunks;}
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if ($DBSERVER_GMT)		{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)		{$ext_context = $DBext_context;}
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
		else {$SYSLOG = '0';}
	}
$sthA->finish();

if (!$telnet_port) {$telnet_port = '5038';}

$event_string='LOGGED INTO MYSQL SERVER ON 1 CONNECTION|';
&event_logger;

### concurrency check (SCREEN uses script path, so check for more than 2 entries)
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 2) 
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		$event_string = "I am not alone! Another $0 is running! Exiting...";
		&event_logger;
		exit;
		}
	}


$event_string="STARTING NEW MANAGER TELNET CONNECTION||ATTEMPT|ONE DAY INTERVAL:$one_day_interval|";
&event_logger;

$max_buffer = 4*1024*1024; # 4 meg buffer

### connect to asterisk manager through telnet
$tn = new Net::Telnet (
	Port => $telnet_port,
	Prompt => '/\r\n/',
	Output_record_separator => "\n\n",
	Max_buffer_length => $max_buffer, 
	Telnetmode => 0,
);

$LItelnetlog = "$PATHlogs/sip_event_telnet_log.txt";  # uncomment for telnet log
#$fh = $tn->dump_log("$LItelnetlog");  # uncomment for telnet log

#if (length($ASTmgrUSERNAMElisten) > 3) {$telnet_login = $ASTmgrUSERNAMElisten;}
#else {$telnet_login = $ASTmgrUSERNAME;}
$telnet_login = $ami_user;

$tn->open("$telnet_host"); 
$tn->waitfor('/Asterisk Call Manager\//');

# get the AMI version number
$ami_version = $tn->getline(Errmode => Return, Timeout => 1,);
$ami_version =~ s/\n//gi;
print "----- AMI Version $ami_version -----\n";

# Login
$tn->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET");
$tn->waitfor('/Authentication accepted/');		# waitfor auth accepted

$tn->buffer_empty;

$event_string="STARTING NEW MANAGER TELNET CONNECTION|$telnet_login|CONFIRMED CONNECTION|AMI Version $ami_version|ONE DAY INTERVAL:$one_day_interval|";
&event_logger;

# initalizing timing variables;
$loop_time = 1*100*1000;	# each loop show take 10 hundredths of a second
$sleep_micro_sec = 0;		# time to actually sleep in micro seconds
$begin_sec = 0;				# the seconds at the beginning of the loop
$begin_micro_sec = 0;		# the microseconds at the beginning of the loop
$end_sec = 0;				# the seconds at the end of the loop
$end_micro_sec = 0;			# the microseconds at the end of the loop
$sleep_diff = 0;			# how off the sleep actually was

$last_keep_alive_epoch = time();
$last_partial_keep_alive_epoch = $last_keep_alive_epoch;
$last_event_epoch = $last_keep_alive_epoch;
$keep_alive_sec = 30;
$keep_alive_skips = 0;
$keep_alive_response = 1;
$keep_alive_no_responses = 0;

%ast_ver_str = parse_asterisk_version($asterisk_version);
if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 13))
	{
	print "Asterisk version too low for this script. Exiting.\n\n\n";
	$event_string = "Asterisk version too low for this script. Exiting.";
	&event_logger;
	exit;
	}
else 
	{
	### BEGIN manager event handling for asterisk version >= 13
	$endless_loop = 1;
	while($endless_loop > 0)
		{
		$breakout = 1;
		$record_line = '';
		%event_hash = ();

		# get an entire record
			( $read_input_buf, $match ) = $tn->waitfor(Errmode => Return, Timeout => 1, Match => "/\n\n/" );

		chomp( $read_input_buf );

		if (($DBX) && ($bs_loaded) && (length($read_input_buf) > 0)) { print "|read|" . backslash( $read_input_buf ) . "|\n"; }

		if (($DBX) && ($bs_loaded) && (length($read_input_buf) > 0)) { print "|match|" . backslash( $match ) . "|\n"; }

		# check if there were any errors
		$msg='';
		$msg = $tn->errmsg;
		if ( ( $msg ne '' ) && ( $msg !~  /pattern match timed-out/i ) )
			{
			if ($msg =~ /filehandle isn\'t open/i)
				{
				# we have lost connection
				$endless_loop=0;
				$one_day_interval=0;
				print "ERRMSG: |$msg|\n";
				print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
				$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|$msg|";
				&event_logger;
				}
			else
				{
				# something happened
				print "ERRMSG: |$msg|\n";
				}
			}
		else
			{
			@lines = split( /\n/, $read_input_buf );
			$line_log='';
			foreach my $line ( @lines )
				{
				$line_log .= "$line\n";
				# add the line to the hash
				($key,$value) = split( /: /, $line );
				if ($key ne "")
					{
					$value =~ s/\n//gi;	# remove new lines
					$value =~ s/^ +//gi;	# remove leading white space
					$value =~ s/ +$//gi;	# remove trailing white space
					$event_hash{"$key"} = $value;
					}
				}
			if ($full_listen_log > 0) 
				{
				$manager_string="$line_log";
				&manager_output_logger;
				}
			}
		
			
		$keep_alive_epoch = time();
			
		# take certain actions every second
		if ( $keep_alive_epoch - $last_partial_keep_alive_epoch >= 1 )
			{
			$last_partial_keep_alive_epoch = $keep_alive_epoch;

			### check if we are getting close to filling the buffer
			if ( $buf_len > ($max_buffer * 0.9) )
				{
				if ($DB) { print "WARNING: BUFFER 90% full!!!! Purging it so we keep running.";}
				$manager_string="WARNING: BUFFER 90% full!!!! Purging it so we keep running.";
				&manager_output_logger;

				# purge the buffer
				$tn->buffer_empty;
				}

			### Grab Server values from the database
			### Also keeps DB connection alive
			$stmtA = "SELECT vd_server_logs FROM servers where server_ip = '$server_ip';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$DBvd_server_logs =		     $aryA[0];
				if ($DBvd_server_logs =~ /Y/)   {$SYSLOG = '1';}
				else {$SYSLOG = '0';}
				}
			$sthA->finish();

			### putting a blank file called "listenmgr.kill" in a directory will automatically safely kill this program
			if ( -e "$PATHhome/listenmgr.kill" ) 
				{
				unlink("$PATHhome/listenmgr.kill");
				$endless_loop=0;
				$one_day_interval=0;
				print "\nPROCESS KILLED MANUALLY... EXITING\n\n";
				}

			### no response to our last keep alive
			if ( $keep_alive_response == 0 ) 
				{
				$response_wait = $keep_alive_epoch - $last_keep_alive_epoch;
				if($DB) { print "No response to keep alive in $response_wait seconds.\n"; }
				}

			if ( $keep_alive_no_responses > 10 ) 
				{
				if($DB) { print "$keep_alive_no_responses failed to get a response. Exiting!!!" }
				$endless_loop=0;
				$one_day_interval=0;
				}

			### check if it has been 40 seconds sense the last keep alive
			&get_time_now;
			if ( $keep_alive_epoch - $last_event_epoch >= $keep_alive_sec )
				{
				if ( $keep_alive_epoch - $last_keep_alive_epoch >= $keep_alive_sec )
					{
					### if so send an AMI command to keep the connection alive
					$keep_alive_skips = 0;
	
					if ( $keep_alive_response == 0 ) { $keep_alive_no_responses++; }
	
					$keep_alive_response = 0;

					$keep_alive_string = "Action: Ping";
					
					$tn->print($keep_alive_string);
	
					$msg = $tn->errmsg;					
					
					$buf_ref = $tn->buffer;
					$buf_len = length( $$buf_ref );
					$output_size = @keep_alive_output;
	
					if($DB) { print "++++++++++++++++sending keepalive |$keep_alive_type|em:$msg|$output_size|$endless_loop|$now_date|$buf_len|$keep_alive_no_responses\n"; }
					if($DBX) { print "---@keep_alive_output---\n"; }
					
					$manager_string="PROCESS: keepalive length: $output_size|$now_date";
					&manager_output_logger;
	
					$last_keep_alive_epoch = time();
					}
				else
					{
					### otherwise
					$keep_alive_skips++;
	
					$buf_ref = $tn->buffer;
					$buf_len = length( $$buf_ref );
					if($DB){print "----------------no keepalive transmit necessary ($keep_alive_skips in a row) $endless_loop|$now_date|$buf_len|$keep_alive_no_responses\n";}
	
					$manager_string="PROCESS: keepalive skip ($keep_alive_skips in a row)|$now_date";
					&manager_output_logger;
					}
				}
			else
				{
				if($DB){ print "Event recieved within the last $keep_alive_sec seconds. No keep alive needed.\n"; }
				}
			}

		$event_hash{'AMIVersion'} = $ami_version;
		$event_hash{'ServerIP'} = $server_ip;

		if ( ($DB) && ( keys %event_hash > 2 ) )
			{
			&get_time_now;
			print "\n\n$now_date|EVENT HASH:\n";
			foreach $key ( sort keys %event_hash )
				{
				$value = $event_hash{"$key"};
				print "  $key -> $value\n";
				} 
			print "\n";
			}

		$retcode = 1;
		# make sure this is an event not a response
		if (exists($event_hash{"Event"})) 
			{
			# handle the event
			$retcode = handle_event( %event_hash );
			$last_event_epoch = time();
			}
		elsif ( ( exists($event_hash{"Response"}) ) && ( exists($event_hash{"Ping"}) ) )
			{
			$keep_alive_response = 1;
			$keep_alive_no_responses = 0;
			}
			

		# handle a shutdown
		if ( $retcode == 0 )
			{
			$endless_loop=0;
			$one_day_interval=0;
			print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
			$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|";
			&event_logger;
			}
		}

	usleep(1*100*1000);
	}



if($DB){print "DONE... Exiting... Goodbye... See you later... Not really, initiating next loop...$one_day_interval left\n";}

$event_string='HANGING UP|';
&event_logger;

@hangup = $tn->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/", Errmode    => Return, Timeout    => 1); 

$tn->buffer_empty;
$tn->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
$ok = $tn->close;


$event_string='CLOSING DB CONNECTION|';
&event_logger;


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;

# function to validate whether a CID Name is a Vicidial Call ID that we care about
sub validate_cid_name
	{
	my ($cid_name) = @_;

	# check if it is a valid CID Name
	if (
		( $cid_name =~ /DC\d\d\d\d\d\dW\d\d\d\d\d\d\d\d\d\dW/ ) ||	# 3way transfers
		( $cid_name =~ /M\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Manual Dials
		( $cid_name =~ /V\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Auto Dials
		( $cid_name =~ /Y\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d\d/) ||	# Inbound Calls
		( $cid_name =~ /^RINGAGENT|^RA_/ )
	) 
		{
		return 1; # if so return 1 for true
		} 
	else 
		{
		return 0; # if not return 0 for false
		}

	}

# function to return the valid callid
sub get_valid_callid
	{
	my ( $CallerIDName, $ConnectedLineName ) = @_;

	# remove everything after the space for Orex
	if ( $CallerIDName =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$CallerIDName =~ s/ .*//gi;}
	if ( $ConnectedLineName =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$ConnectedLineName =~ s/ .*//gi;}
	
	# if the CallerIDName variable does not have a valid Vicidial Call ID
	# but the ConnectedLineName does use the ConnectedLineName
	if (  !(validate_cid_name($CallerIDName)) && (validate_cid_name($ConnectedLineName)) )
		{
		return $ConnectedLineName;
		}
	else
		{
		return $CallerIDName;
		}
	}

# function to handle the AMI events
sub handle_event
	{
	my %event_hash = @_;

	switch ($event_hash{'Event'}) 
		{
		# Asterisk is shutting down so should we
		case "Shutdown" { return 0; }

		# DTMF event
		case "DTMFBegin" { return handle_dtmf_begin_event( %event_hash ); }
		case "DTMFEnd" { return handle_dtmf_end_event( %event_hash ); }		

		# SIPInvite event
		case "SIPInvite" { return handle_sip_invite ( %event_hash ); }

		# SIPResponse event
		case "SIPResponse" { return handle_sip_response ( %event_hash ); }

		else { return 2; }
		}

	}

sub handle_sip_invite
	{

	# Example Event:
	#
	# Event: SIPInvite
	# Privilege: call,all
	# Timestamp: 1559063934.425579
	# Channel: SIP/sgw2-000045be
	# Uniqueid: 1559063934.53768
	# SIPCallID: 1c8e4edf5005173741e02b366aa31e02@208.38.155.226:5060
	# CallerIDName: V5281318520051804811
	# ConnectedLineName: V5281318520051804811

	my %event_hash = @_;

	if ( 
		( exists $event_hash{'Channel'} ) && 
		( exists $event_hash{'Uniqueid'} ) &&
		( exists $event_hash{'CallerIDName'} ) &&
		( exists $event_hash{'ConnectedLineName'} ) &&
		( exists $event_hash{'Timestamp'} ) &&
		( exists $event_hash{'SIPCallID'} )
	) 
		{

		# get the valid Vicidial Call ID
		$call_id = get_valid_callid( $event_hash{'CallerIDName'}, $event_hash{'ConnectedLineName'} ); 

		$stmtA = "INSERT INTO vicidial_sip_event_log SET caller_code ='$call_id', channel='$event_hash{'Channel'}', server_ip='$event_hash{'ServerIP'}', uniqueid='$event_hash{'Uniqueid'}', sip_call_id='$event_hash{'SIPCallID'}', event_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ), sip_event = 'INVITE'";
		if($DB){print STDERR "|$stmtA|\n";}
		$dbhA->do($stmtA);
	

		# update the vicidial_sip_event_recent table

		$stmtA = "INSERT INTO vicidial_sip_event_recent SET caller_code ='$call_id', channel='$event_hash{'Channel'}', server_ip='$event_hash{'ServerIP'}', uniqueid='$event_hash{'Uniqueid'}', invite_date = FROM_UNIXTIME( $event_hash{'Timestamp'} )";
		if($DB){print STDERR "|$stmtA|\n";}
		$dbhA->do($stmtA);

		return 1;
		}
	else 
		{
		print STDERR "SIPInvite Event is missing fields ?!!!\n";
		return 3;
		}
	}

sub handle_sip_response
        {

	# Example Event:
	#
	# Event: SIPResponse
	# Privilege: call,all
	# Timestamp: 1559063934.426206
	# Response: 100
	# Channel: SIP/sgw2-000045be
	# Uniqueid: 1559063934.53768
	# SIPCallID: 1c8e4edf5005173741e02b366aa31e02@208.38.155.226:5060
	# CallerIDName: V5281318520051804811
	# ConnectedLineName: V5281318520051804811

        my %event_hash = @_;

        if (
                ( exists $event_hash{'Channel'} ) &&
                ( exists $event_hash{'Uniqueid'} ) &&
                ( exists $event_hash{'CallerIDName'} ) &&
                ( exists $event_hash{'ConnectedLineName'} ) &&
		( exists $event_hash{'Timestamp'} )&&
		( exists $event_hash{'Response'} ) &&
		( exists $event_hash{'SIPCallID'} )
        )
                {

                # get the valid Vicidial Call ID
                $call_id = get_valid_callid( $event_hash{'CallerIDName'}, $event_hash{'ConnectedLineName'} );

		$stmtA = "INSERT INTO vicidial_sip_event_log SET caller_code ='$call_id', channel='$event_hash{'Channel'}', server_ip='$event_hash{'ServerIP'}', uniqueid='$event_hash{'Uniqueid'}', sip_call_id='$event_hash{'SIPCallID'}', event_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ), sip_event = '$event_hash{'Response'}'";
                if($DB){print STDERR "|$stmtA|\n";}
                $dbhA->do($stmtA);


		# update the vicidial_sip_event_recent table

		# 100 Trying
		if ( $event_hash{'Response'} == 100 ) {
			$stmtA = "UPDATE vicidial_sip_event_recent SET first_100_date = IF( first_100_date IS NULL, FROM_UNIXTIME( $event_hash{'Timestamp'} ), first_100_date), last_100_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ) where caller_code = '$call_id'";
		}
		# 180 Ringing
                if ( $event_hash{'Response'} == 180 ) {
                        $stmtA = "UPDATE vicidial_sip_event_recent SET first_180_date = IF( first_180_date IS NULL, FROM_UNIXTIME( $event_hash{'Timestamp'} ), first_180_date), last_180_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ) where caller_code = '$call_id'";
                }
		# 183 Session Progress
                if ( $event_hash{'Response'} == 183 ) {
                        $stmtA = "UPDATE vicidial_sip_event_recent SET first_183_date = IF( first_183_date IS NULL, FROM_UNIXTIME( $event_hash{'Timestamp'} ), first_183_date), last_183_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ) where caller_code = '$call_id'";
                }
		# 200 OK
                if ( $event_hash{'Response'} == 200 ) {
                        $stmtA = "UPDATE vicidial_sip_event_recent SET 200_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ) where caller_code = '$call_id'";
                }
		# Error Codes
                if ( $event_hash{'Response'} >= 300 ) {
                        $stmtA = "UPDATE vicidial_sip_event_recent SET error_date = FROM_UNIXTIME( $event_hash{'Timestamp'} ) where caller_code = '$call_id'";
                }

		if($DB){print STDERR "|$stmtA|\n";}
		$dbhA->do($stmtA);


		return 1;
                }
        else
                {
                print STDERR "SIPResponse Event is missing fields ?!!!\n";
                return 3;
                }
        }


sub get_time_now	#get the current date and time and epoch for logging call lengths and datetimes
	{
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
	$action_log_date = "$year-$mon-$mday";
	}



sub event_logger 
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$PATHlogs/sip_event_process.$action_log_date")
				|| die "Can't open $PATHlogs/sip_event_process.$action_log_date: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}



sub manager_output_logger
	{
	if ($SYSLOG)
		{
		open(MOout, ">>$PATHlogs/sip_event.$action_log_date")
				|| die "Can't open $PATHlogs/sip_event.$action_log_date: $!\n";
		print MOout "$now_date|$manager_string|\n";
		close(MOout);
		}
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

# try to load a module
sub try_load 
	{
	 my $mod = shift;

	eval("use $mod");

	if ($@) 
		{
		#print "\$@ = $@\n";
		return(0);
		}
	else 
		{
		return(1);
		}
	}
