#!/usr/bin/perl
#
# AST_conf_update_screen.pl version 2.14
#
# This script uses the Asterisk Manager interface to update the live_channels
# tables and verify the parked_channels table in the asterisk MySQL database
#
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 220414-1210 - Initial build
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$DBX=0;
$run_check=1; # concurrency check

# loop time in seconds
$loop_time = 0.4;

# how often settings should be reloaded from the DB in seconds
$settings_reload_interval = 10;

# how often to purge the conference records
$conf_purge_interval = 15;

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
	if ($line =~ /^VARDB_custom_user/)	{$VARDB_custom_user = $line;   $VARDB_custom_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_custom_pass/)	{$VARDB_custom_pass = $line;   $VARDB_custom_pass =~ s/.*=//gi;}
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
use POSIX;
use Scalar::Util qw(looks_like_number);
use Term::ANSIColor qw(:constants);


$module = 'String::Escape qw( backslash unbackslash )';
$bs_loaded=0;
if (try_load($module)) 
	{
	$bs_loaded=1;
	}


$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;

### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,vd_server_logs,asterisk_version,conf_engine,conf_update_interval,vicidial_recording_limit FROM servers where server_ip = '$server_ip';";
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
	$conf_engine		=	$aryA[13];
	$conf_update_interval	=	$aryA[14];
	$vicidial_recording_limit	=	$aryA[15];
	if ($DBtelnet_host)		{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)		{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)		{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)		{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMEsend)	{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	if ($DBmax_vicidial_trunks)	{$max_vicidial_trunks = $DBmax_vicidial_trunks;}
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if ($DBSERVER_GMT)		{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)		{$ext_context = $DBext_context;}
	if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
		else {$SYSLOG = '0';}
		if ($vicidial_recording_limit < 60) {$vicidial_recording_limit=60;}
	}

if (!$telnet_port) {$telnet_port = '5038';}

$loop_time = $conf_update_interval;

##### Find date-time one hour in the past
$secX = time();
$TDtarget = ($secX - ($vicidial_recording_limit * 60));
($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TDtarget);
$Tyear = ($Tyear + 1900);
$Tmon++;
if ($Tmon < 10) {$Tmon = "0$Tmon";}
if ($Tmday < 10) {$Tmday = "0$Tmday";}
if ($Thour < 10) {$Thour = "0$Thour";}
if ($Tmin < 10) {$Tmin = "0$Tmin";}
if ($Tsec < 10) {$Tsec = "0$Tsec";}
$TDSQLdate = "$Tyear-$Tmon-$Tmday $Thour:$Tmin:$Tsec";
$TDrand = int( rand(99)) + 100;
$TDnum = "$Tmon$Tmday$Thour$Tmin$Tsec$TDrand";
$TDinc = 1;

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
		&event_logger($SYSLOG,$event_string);
		exit;
		}
	}


$event_string="STARTING NEW MANAGER TELNET CONNECTION||ATTEMPT|ONE DAY INTERVAL:$one_day_interval|";
event_logger($SYSLOG,$event_string);

$max_buffer = 4*1024*1024; # 4 meg buffer

### connect to asterisk manager through telnet
$tn = new Net::Telnet (
	Port => $telnet_port,
	Prompt => '/\r\n/',
	Output_record_separator => "\n\n",
	Max_buffer_length => $max_buffer, 
	Telnetmode => 0,
);

$UPtelnetlog = "$PATHlogs/conf_telnet_log.txt";  # uncomment for telnet log
$fh = $tn->dump_log("$UPtelnetlog");  # uncomment for telnet log

$telnet_login = "confcron";
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
event_logger($SYSLOG,$event_string);

%ast_ver_str = parse_asterisk_version($asterisk_version);
if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 13))
	{
	print "Asterisk version too low for this script. Exiting.\n\n\n";
	$event_string = "Asterisk version too low for this script. Exiting.";
	&event_logger($SYSLOG,$event_string);
	exit;
	}
else 
	{
	### BEGIN manager event handling for asterisk version >= 13
	$endless_loop = 1;
	$loop_count = 0;

	# get the number of microseconds the loop is supposed to take
	$loop_time_usec = $loop_time * 1000000;
	$loop_time_usec = ceil($loop_time_usec); # take care of any floating point math

	# the last time our settings were loaded from the DB
	$last_config_reload = 0;

	# last time we purged conferences	
	$last_conf_purge = 0;

	while($endless_loop)
		{
		# increment total loop counter
		$loop_count++;

		$total_rooms = 0;

		# get the current time
		( $now_sec, $now_micro_sec ) = gettimeofday();

		# figure out how many micro seconds since epoch
		$now_micro_epoch = $now_sec * 1000000;
		$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

		$begin_micro_epoch = $now_micro_epoch;
		
		# create a new action id
		$action_id = "$now_sec.$now_micro_sec";

		$modechange = $tn->errmode('return');

		@room_array = ();

		if ( $conf_engine eq "MEETME" )
			{
			# ask the AMI for the conferences
			$action_string = "Action: MeetmeListRooms\nActionID: $action_id";
			$tn->print($action_string);

			# wait till we get the response
			$pattern = '/Message: Meetme conferences will follow\n\n|Message: No active conferences.\n\n/';
			}
		else
			{
			# ask the AMI for the conferences
			$action_string = "Action: ConfbridgeListRooms\nActionID: $action_id";
			$tn->print($action_string);

			# wait till we get the response
			$pattern = '/Message: Confbridge conferences will follow\n\n|Message: No active conferences.\n\n/';
			}
		( $read_input_buf, $match ) = $tn->waitfor($pattern);
		$msg = $tn->errmsg;
		if (  $msg ne '' ) 
			{
			$event_string =  "WAITFOR ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|Command:\n\n$action_string\n\nPattern:$pattern\n\n";
			&event_logger($SYSLOG,$event_string);
			print $event_string."\n";
			}

		&get_time_now;
		print "\n\n$now_date\n|$conf_engine List:\n";
		event_logger($SYSLOG,"$conf_engine List:"); 

		if ($match eq "Message: No active conferences.\n\n")
			{
			print "|No active conferences.\n";
			$event_string = "|No active conferences.";
	                event_logger($SYSLOG,$event_string);
			}
		else
			{

			# Loop to get all of the channels
			$event_ref = {};

			if ( $conf_engine eq "MEETME" )
				{
				$match_string = '/Event: MeetmeListRoomsComplete\nActionID: ' . $action_id . '\nEventList: Complete\nListItems: \d+/';
				}
			else
				{
				$match_string = '/Event: ConfbridgeListRoomsComplete\nActionID: ' . $action_id . '\nEventList: Complete\nListItems: \d+/';
				}


			# get an entire record
			( $read_input_buf, $match ) = $tn->waitfor(Errmode => Return, Timeout => 1, Match => "$match_string");

			chomp( $read_input_buf );

			if (($bs_loaded) && (length($read_input_buf) > 0)) 
				{ 
				if ($DBX) { print "|read|" . backslash( $read_input_buf ) . "|\n"; }
				event_logger($SYSLOG,"|read|" . backslash( $read_input_buf ) . "|");
				if ($DBX) { print "|match|" . backslash( $match ) . "|\n"; }
				event_logger($SYSLOG,"|match|" . backslash( $match ) . "|");
				}


			( $junk, $list_items) = split( /ListItems: /, $match );

			if ($DBX) 
				{ 
				print "|total_rooms|$list_items\n";
				&event_logger($SYSLOG,"|total_rooms|$list_items");
				}

			# check if there were any errors
			$msg='';
			$msg = $tn->errmsg;
			if ( ( $msg ne '' ) && ( $msg !~  /read timed-out/i ) )
				{
				if ($msg =~ /filehandle isn\'t open/i)
					{
					# we have lost connection
					$endless_loop=0;
					$one_day_interval=0;
					print "ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|\n";
					print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
					$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|$msg|";
					event_logger($SYSLOG,$event_string);
					}
				else
					{
					# something happened
					print "ERRMSG: |$msg|$now_date|" . length($read_input_buf) . "|$endless_loop|$loop_count|Command: $action_string|Match String: $match_string|\n";
					$bad_grab = 1;
					}
				}
			else
				{
				@records = split( /\n\n/, $read_input_buf );
				foreach my $record ( @records )
					{			
					# Loop to get all of the channels
					$event_ref = {};
	
					# break the record into lines
					@lines = split( /\n/, $record );
					foreach my $line ( @lines)
						{
						# add the line to the hash
						($key,$value) = split( /: /, $line );
						if ($key ne "")
							{
							$value =~ s/\n//gi;	# remove new lines
							$value =~ s/^ +//gi;	# remove leading white space
							$value =~ s/ +$//gi;	# remove trailing white space
						
							$event_ref->{"$key"} = $value;
							}
						}
	
					$hash_size = keys(%$event_ref);
	
					if ( $hash_size > 2 )
						{
						$debug_string = '';
						if ( $event_ref->{'Event'} eq "MeetmeListRooms" )
							{
							if ( $total_rooms == 0 )
								{
								$debug_string .= "|Conference|Parties|Marked|Activity|Creation|Locked|";
								print "$debug_string\n";
								event_logger($SYSLOG,$debug_string);
								$debug_string = '';
								}
								
							$debug_string .= "|$event_ref->{'Conference'}";
							$debug_string .= "|$event_ref->{'Parties'}";
							$debug_string .= "|$event_ref->{'Marked'}";
							$debug_string .= "|$event_ref->{'Activity'}";
							$debug_string .= "|$event_ref->{'Creation'}";
							$debug_string .= "|$event_ref->{'Locked'}";
							}
						if ( $event_ref->{'Event'} eq "ConfbridgeListRooms" )
							{
							if ( $total_rooms == 0 )
								{
								$debug_string .= "|Conference|Parties|Marked|Locked|Muted|";
								print "$debug_string\n";
								event_logger($SYSLOG,$debug_string);
								$debug_string = '';
								}
	
							$debug_string .= "|$event_ref->{'Conference'}";
							$debug_string .= "|$event_ref->{'Parties'}";
							$debug_string .= "|$event_ref->{'Marked'}";
							$debug_string .= "|$event_ref->{'Locked'}";
							$debug_string .= "|$event_ref->{'Muted'}";
							}
							print "$debug_string\n";
							event_logger($SYSLOG,$debug_string);
						}

					$event_ref->{'AMIVersion'} = $ami_version;
					$event_ref->{'ServerIP'} = $server_ip;


					$retcode = 1;
					# make sure this is an event not a response
					if (exists($event_ref->{"Event"})) 
						{
						if ( ( $event_ref->{"Event"} eq "ConfbridgeListRooms" ) || ( $event_ref->{"Event"} eq "MeetmeListRooms" ) )
							{
							$total_rooms++;
							# We got a room
							if ( $event_ref->{"ActionID"} eq $action_id )
								{
								push( @room_array, $event_ref );
								}
							else
								{
								if ($DB) { print STDERR "We got the wrong Action ID!!!!\n"; }
								}
							}
						else
							{
							# We got something else???
							}
						}
					elsif ( exists($event_ref->{"Response"}) ) 
						{
						# we got the response. Channels should follow.
						}
					}
				}
		
			if ( $total_rooms != $list_items ) 
				{
				# something is wrong. we did not process the
				# same number of channels as were reported.
				}

			if ($DB) { print STDERR "$total_rooms rooms recieved out of $list_items reported.|\n"; }
			
			$room_array_ref = \@room_array;
		}
		
		######################################################################
		##### CHECK all TABLE #####
		######################################################################
		
		%conf_hash = ();
		if ( $conf_engine eq "MEETME" ) 
			{
			# get conferences table data
			$stmtA = "SELECT extension,conf_exten from conferences where server_ip='$server_ip' and extension is NOT NULL and extension != '';";
			if ($DB) {print "|$stmtA|\n";}
			event_logger($SYSLOG,"|$stmtA|");
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$conf_hash{ "$aryA[1]" } = { "extension" => $aryA[0], "type" => "c" };
				$rec_count++;
				}
			$sthA->finish();
			
			# get vicidial_conferences data
			$stmtA = "SELECT extension,conf_exten from vicidial_conferences where server_ip='$server_ip' and leave_3way='1';";
			if ($DB) {print "|$stmtA|\n";}
			event_logger($SYSLOG,"|$stmtA|");
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$conf_hash{ "$aryA[1]" } = { "extension" => $aryA[0], "type" => "vc" };
				$rec_count++;
				}
			$sthA->finish(); 
			}
		else
			{
			# get vicidial_confbridges data
			$stmtA = "SELECT extension,conf_exten from vicidial_confbridges where server_ip='$server_ip' and leave_3way='1';";
			if ($DB) {print "|$stmtA|\n";}
		       	event_logger($SYSLOG,"|$stmtA|");
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$rec_count=0;
			while ($sthArows > $rec_count)
				{
				@aryA = $sthA->fetchrow_array;
				$conf_hash{ "$aryA[1]" } = { "extension" => $aryA[0], "type" => "vcb" };
				$rec_count++;
				}
			$sthA->finish()
			}
		
		# loop through the room data
		foreach $room (@room_array)
			{
			$conf_id = $room->{'Conference'};
			if (( $room->{'Parties'} == 0 ) && ($conf_hash{"$conf_id"}{"type"} eq "c"))
		       		{
				# we are just a conference so dont kicking people out
				$new_exten = $conf_hash{"$conf_id"}{"extension"};
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout3$/i) {$new_exten =~ s/Xtimeout3$/Xtimeout2/gi;}
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout2$/i) {$new_exten =~ s/Xtimeout2$/Xtimeout1/gi;}
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout1$/i) {$new_exten = '';}
				if ( ($conf_hash{"$conf_id"}{"extension"} !~ /Xtimeout\d$/i) and (length($conf_hash{"$conf_id"}{"extension"})> 0) ) {$new_exten .= 'Xtimeout3';}

				if($DB) { print $conf_hash{"$conf_id"}{"extension"} . "|$new_exten|$leave_3waySQL|\n"; }

				$stmtA = "UPDATE vicidial_conferences set extension='$new_exten' where server_ip='$server_ip' and conf_exten='$conf_id';";
				if($DB){print STDERR "\n|$stmtA|\n";} 
				event_logger($SYSLOG,"|$stmtA|");
				
				$affected_rows = $dbhA->do($stmtA);
				}	
			elsif (( $room->{'Parties'} <= 1 ) && (($conf_hash{"$conf_id"}{"type"} eq "vc") || ($conf_hash{"$conf_id"}{"type"} eq "vcb"))) 
				{
				# we are a vicidial conference so we can single partisipants out.
				$new_exten = $conf_hash{"$conf_id"}{"extension"};
				$leave_3waySQL = '1';
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout3$/i) {$new_exten =~ s/Xtimeout3$/Xtimeout2/gi;}
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout2$/i) {$new_exten =~ s/Xtimeout2$/Xtimeout1/gi;}
				if ($conf_hash{"$conf_id"}{"extension"} =~ /Xtimeout1$/i) {$new_exten = ''; $leave_3waySQL='0';}
				if ( ($conf_hash{"$conf_id"}{"extension"} !~ /Xtimeout\d$/i) and (length($conf_hash{"$conf_id"}{"extension"})> 0) ) {$new_exten .= 'Xtimeout3';}

				if($DB) { print $conf_hash{"$conf_id"}{"extension"} . "|$new_exten|$leave_3waySQL|\n";}
			      	event_logger($SYSLOG, $conf_hash{"$conf_id"}{"extension"} . "|$new_exten|$leave_3waySQL|");

				$kick = 0;
				if ($new_exten =~ /Xtimeout1$/i)
					{
					### Kick all participants if there are any left in the conference so it can be reused
					$local_DEF = 'Local/5555';
					$local_AMP = '@';
					$kick_local_channel = $local_DEF . $conf_id . $local_AMP . $ext_context;
					$padTDinc = sprintf("%03s", $TDinc);    while (length($padTDinc) > 3) {chop($padTDinc);}
					$queryCID = "ULGC$padTDinc$TDnum";

					$stmtA="INSERT INTO vicidial_manager values('','','$now_date','NEW','N','$server_ip','','Originate','$queryCID','Channel: $kick_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','','');";
					$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
					if($DB){print STDERR "\n|$affected_rows|$stmtA|\n";}
					event_logger($SYSLOG,"|$affected_rows|$stmtA|");
					$TDinc++;
					$kick = 1;
					}

				if ($conf_hash{"$conf_id"}{"type"} eq "vc") 
					{
					$stmtA = "UPDATE vicidial_conferences set extension='$new_exten',leave_3way='$leave_3waySQL' where server_ip='$server_ip' and conf_exten='$conf_id';";
					}
				else
					{
					$stmtA = "UPDATE vicidial_confbridges set extension='$new_exten',leave_3way='$leave_3waySQL' where server_ip='$server_ip' and conf_exten='$conf_id';"
					}

				if($DB){print STDERR "\n|$stmtA|\n";}
			       	event_logger($SYSLOG,"\n|$stmtA|");
				$affected_rows = $dbhA->do($stmtA);

				# only sleep if we did a kick
				if ($kick) { usleep(1*100*1000); }

				}
			}
		

		# reload the settings from the DB at a fixed interval
		$epoch = time();
		if ( $epoch - $last_conf_purge >= $conf_purge_interval) 
			{
			clear_conf_records();

			$last_conf_purge = time();
			}


		# reload the settings from the DB at a fixed interval
		$epoch = time();
		if ( $epoch - $last_config_reload >= $settings_reload_interval ) 
			{
			# reload the server settings
			$settings_ref = get_server_settings($dbhA,$server_ip);

			$loop_time_usec = $settings_ref->{'conf_update_interval'} * 1000000;
		        $loop_time_usec = ceil($loop_time_usec); # take care of any floating point math

			$vicidial_recording_limit = $settings_ref->{'vicidial_recording_limit'};

			$conf_engine = $settings_ref->{'conf_engine'};

			$last_config_reload = time();
			}

		# figure out how long that loop took.
		# get the current time
		( $now_sec, $now_micro_sec ) = gettimeofday();

		# figure out how many micro seconds since epoch
		$now_micro_epoch = $now_sec * 1000000;
		$now_micro_epoch = $now_micro_epoch + $now_micro_sec;
		$end_micro_epoch = $now_micro_epoch;

		# subtract the current time in usec from time at the beginning of the loop
		$loop_usec = $end_micro_epoch - $begin_micro_epoch;

		# figure out how long to sleep
		$sleep_usec = $loop_time_usec - $loop_usec;
		# cannot sleep negative time
		if ( $sleep_usec > 0 ) 
			{
			if ($DB) { print STDERR "loop took $loop_usec microseconds. sleeping for $sleep_usec microseconds to compensate\n"; }
			event_logger($SYSLOG,"loop took $loop_usec microseconds. sleeping for $sleep_usec microseconds to compensate\n");
			# sleep it
			usleep($sleep_usec);
			}
			
		}
	}



if($DB){print "DONE... Exiting... Goodbye... See you later... Not really, initiating next loop...$one_day_interval left\n";}

$event_string='HANGING UP|';
event_logger($SYSLOG,$event_string);

@hangup = $tn->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/", Errmode    => Return, Timeout    => 1); 

$tn->buffer_empty;
$tn->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
$ok = $tn->close;


$event_string='CLOSING DB CONNECTION|';
event_logger($SYSLOG,$event_string);


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;

sub get_server_settings 
	{
	my ($dbhA,$server_ip) = @_;

	### Grab Server values from the database
	$stmtA = "SELECT conf_engine,conf_update_interval,vicidial_recording_limit FROM servers where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$settings_ref = $sthA->fetchrow_hashref;
	if ($DB) { print "Settings Reload|$stmtA|$settings_ref->{'conf_engine'}|$settings_ref->{'conf_update_interval'}|$settings_ref->{'vicidial_recording_limit'}\n"; }
	event_logger($SYSLOG,"Settings Reload|$stmtA|$settings_ref->{'conf_engine'}|$settings_ref->{'conf_update_interval'}|$settings_ref->{'vicidial_recording_limit'}");
	
	return $settings_ref;
	}

sub event_logger 
	{
	my ($SYSLOG,$event_string) = @_;
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$PATHlogs/conf_update.$action_log_date")
				|| die "Can't open $PATHlogs/conf_update.$action_log_date: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
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


sub clear_conf_records
	{
	######################################################################
	##### CLEAR vicidial_conferences ENTRIES IN LEAVE-3WAY FOR MORE THAN ONE HOUR
	######################################################################
	
	##### Find date-time one hour in the past
	$secX = time();
	$TDtarget = ($secX - ($vicidial_recording_limit * 60));
	($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TDtarget);
	$Tyear = ($Tyear + 1900);
	$Tmon++;
	if ($Tmon < 10) {$Tmon = "0$Tmon";}
	if ($Tmday < 10) {$Tmday = "0$Tmday";}
	if ($Thour < 10) {$Thour = "0$Thour";}
	if ($Tmin < 10) {$Tmin = "0$Tmin";}
	if ($Tsec < 10) {$Tsec = "0$Tsec";}
	$TDSQLdate = "$Tyear-$Tmon-$Tmday $Thour:$Tmin:$Tsec";
	$TDrand = int( rand(99)) + 100;
	$TDnum = "$Tmon$Tmday$Thour$Tmin$Tsec$TDrand";
	$TDinc = 1;
	
	@PTextensions=@MT; @PT_conf_extens=@MT; @PTmessages=@MT; @PTold_messages=@MT; @NEW_messages=@MT; @OLD_messages=@MT;
	$stmtA = "SELECT conf_exten,extension from vicidial_conferences where server_ip='$server_ip' and leave_3way='1' and leave_3way_datetime < \"$TDSQLdate\";";
	if ($DB) {print "Conf Purge|$stmtA|\n";} 
	event_logger($SYSLOG,"Conf Purge|$stmtA|");
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$PT_conf_extens[$rec_count] =	 $aryA[0];
		$PT_extensions[$rec_count] =	 $aryA[1];
			if ($DB) {print "|$PT_conf_extens[$rec_count]|$PT_extensions[$rec_count]|\n";}
		$rec_count++;
		}
	$sthA->finish();
	$k=0;
	while ($k < $rec_count)
		{
		$local_DEF = 'Local/5555';
		$local_AMP = '@';
		$kick_local_channel = "$local_DEF$PT_conf_extens[$k]$local_AMP$ext_context";
		$padTDinc = sprintf("%03s", $TDinc);	while (length($padTDinc) > 3) {chop($padTDinc);}
		$queryCID = "ULGH$padTDinc$TDnum";

		$stmtA="INSERT INTO vicidial_manager values('','','$now_date','NEW','N','$server_ip','','Originate','$queryCID','Channel: $kick_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','','');";
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

		$stmtA = "UPDATE vicidial_conferences set extension='',leave_3way='0' where server_ip='$server_ip' and conf_exten='$PT_conf_extens[$k]';";
		if($DB){print STDERR "\n|$stmtA|\n";}
	       	event_logger($SYSLOG,"Conf Purge|$stmtA|");
		$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

		$k++;
		$TDinc++;
		}
	}
