#!/usr/bin/perl
#
# AST_manager_listenBUFFER.pl version 2.12
#
# Part of the Asterisk Central Queue System (ACQS)
#
# DESCRIPTION:
# connects to the Asterisk Manager interface and updates records in the 
# vicidial_manager table of the asterisk database in MySQL based upon the 
# events that it receives
# 
# SUMMARY:
# This program was designed as the listen-only part of the ACQS. It's job is to
# look for certain events and based upon either the uniqueid or the callerid of 
# the call update the status and information of an action record in the 
# vicidial_manager table of the asterisk MySQL database. This program is run by
# the ADMIN_keepalive_ALL.pl script, which makes sure it is always running in a
# screen, provided that the astguiclient.conf keepalive setting "2" is set.
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 50322-1300 - changed callerid parsing to remove quotes and number
# 50616-1559 - Added NewCallerID parsing and updating 
# 50621-1406 - Added Asterisk server shutdown and connection dead detection 
# 50810-1534 - Added database server variable definitions lookup
# 50824-1606 - Altered CVS/1.2 support for different output
# 50901-2359 - Another CVS/1.2 output parsing fix
# 51222-1553 - fixed parentheses bug in manager output
# 60403-1230 - Added SVN/1.4 support for different output
# 60718-0909 - changed to DBI by Marin Blu
# 60718-0955 - changed to use /etc/astguiclient.conf for configs
# 60720-1142 - added keepalive to MySQL connection every 50 seconds
# 60814-1733 - added option for no logging to file
# 60906-1714 - added updating for special vicidial conference calls
# 71129-2004 - Fixed SQL error
# 100416-0635 - Added fix for extension append feature
# 100625-1220 - Added waitfors after logout to fix broken pipe errors in asterisk <MikeC>
# 100903-0041 - Changed lead_id max length to 10 digits
# 130108-1705 - Changes for Asterisk 1.8 compatibility
# 130412-1216 - Added sip hangup cause logging from new patch AMI event
# 130418-1946 - Changed asterisk 1.8 compatibility for CPD and SIP Hangup
# 140524-0900 - Fixed issue with consultative agent transfers in Asterisk 1.8
# 141113-1605 - Added concurrency check
# 141124-2309 - Fixed Fhour variable bug
# 141219-1137 - Change to keepalive processes to not run at busy times and not drop buffer
# 150610-1200 - Added support for AMI version 1.3
# 150709-1506 - Added DTMF logging for Asterisk 1.8 and higher
# 151031-0651 - Added perl telnet buffer options, requires newer versions of Net::Telnet CPAN module
# 170920-1417 - Fix for issue with recordings beginning with CALLID variable
# 170921-2208 - DEPRICATED, CODE MERGED TO AST_manager_listen.pl
#

# constants
$DB=1;  # Debug flag, set to 0 for no debug messages, lots of output
$US='__';
$MT[0]='';
$vdcl_update=0;
$vddl_update=0;
$run_check=1; # concurrency check
$last_keepalive_epoch = time();
$keepalive_skips=0;

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
else
	{
	#	print "no command line options set\n";
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

	&get_time_now;

#use lib './lib', '../lib';
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Net::Telnet ();
  
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
	$DBtelnet_host	=			$aryA[0];
	$DBtelnet_port	=			$aryA[1];
	$DBASTmgrUSERNAME	=		$aryA[2];
	$DBASTmgrSECRET	=			$aryA[3];
	$DBASTmgrUSERNAMEupdate	=	$aryA[4];
	$DBASTmgrUSERNAMElisten	=	$aryA[5];
	$DBASTmgrUSERNAMEsend	=	$aryA[6];
	$DBmax_vicidial_trunks	=	$aryA[7];
	$DBanswer_transfer_agent=	$aryA[8];
	$DBSERVER_GMT		=		$aryA[9];
	$DBext_context	=			$aryA[10];
	$DBvd_server_logs =			$aryA[11];
	$asterisk_version = 		$aryA[12];
	if ($DBtelnet_host)				{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)				{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)			{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)			{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMElisten)	{$ASTmgrUSERNAMElisten = $DBASTmgrUSERNAMElisten;}
	if ($DBASTmgrUSERNAMEsend)		{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	if ($DBmax_vicidial_trunks)		{$max_vicidial_trunks = $DBmax_vicidial_trunks;}
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if ($DBSERVER_GMT)				{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)				{$ext_context = $DBext_context;}
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

$one_day_interval = 90;		# 1 day loops for 3 months
while($one_day_interval > 0)
	{
	$event_string="STARTING NEW MANAGER TELNET CONNECTION||ATTEMPT|ONE DAY INTERVAL:$one_day_interval|";
	&event_logger;
	#   Errmode    => Return,

	### connect to asterisk manager through telnet
	$tn = new Net::Telnet (Port => $telnet_port,
						  Prompt => '/.*[\$%#>] $/',
						  Output_record_separator => '',
						  Max_buffer_length => 4*1024*1024, );
#	$LItelnetlog = "$PATHlogs/listen_telnet_log.txt";  # uncomment for telnet log
#	$fh = $tn->dump_log("$LItelnetlog");  # uncomment for telnet log
	if (length($ASTmgrUSERNAMElisten) > 3) {$telnet_login = $ASTmgrUSERNAMElisten;}
	else {$telnet_login = $ASTmgrUSERNAME;}
	$tn->open("$telnet_host"); 
	$tn->waitfor('/[0123]\n$/');			# print login
	$tn->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET\n\n");
	$tn->waitfor('/Authentication accepted/');		# waitfor auth accepted

	$tn->buffer_empty;

	$event_string="STARTING NEW MANAGER TELNET CONNECTION|$telnet_login|CONFIRMED CONNECTION|ONE DAY INTERVAL:$one_day_interval|";
	&event_logger;

	$endless_loop=864000;		# 1 day at .10 seconds per loop

	%ast_ver_str = parse_asterisk_version($asterisk_version);
	if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 6))
		{
		###  BEGIN manager event handling for asterisk version < 1.6
		while($endless_loop > 0)
			{
			### sleep for 10 hundredths of a second(one tenth of a second)
			usleep(1*100*1000);
		
			$msg='';
			$read_input_buf = $tn->get(Errmode => Return, Timeout => 1,);
			$input_buf_length = length($read_input_buf);
			$msg = $tn->errmsg;
			if (($msg =~ /read timed-out/i) || ( $msg eq ''  ))
				{
					# This is normal
				}
			else
				{
					print "ERRMSG: |$msg|\n";
				}
			if ($msg =~ /filehandle isn\'t open/i)
				{
				$endless_loop=0;
				$one_day_interval=0;
				print "ERRMSG: |$msg|\n";
				print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
					$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|$msg|";
				&event_logger;
				}

			if ( ($read_input_buf !~ /\n\n/) or ($input_buf_length < 10) )
				{
				#if ($read_input_buf =~ /\n/) {print "\n|||$input_buf_length|||$read_input_buf|||\n";}
				if ($endless_loop =~ /00$|50$/) 
					{
					$input_buf = "$input_buf$keepalive_lines$read_input_buf";
					$input_buf =~ s/\n\n\n/\n\n/gi;
					$keepalive_lines='';
					}
				else
					{$input_buf = "$input_buf$read_input_buf";}
				}
			else
				{
				$partial=0;
				$partial_input_buf='';
				@input_lines=@MT;

				if ($read_input_buf !~ /\n\n$/)
					{
					$read_input_buf =~ s/\(|\)/ /gi; # replace parens with space
					$partial_input_buf = $read_input_buf;
					$partial_input_buf =~ s/\n/-----/gi;
					$partial_input_buf =~ s/\*/\\\*/gi;
					$partial_input_buf =~ s/.*----------//gi;
					$partial_input_buf =~ s/-----/\n/gi;
					$read_input_buf =~ s/$partial_input_buf$//gi;
					$partial++;
					}

				if ($endless_loop =~ /00$|50$/) 
					{
					$input_buf = "$input_buf$keepalive_lines$read_input_buf";
					$input_buf =~ s/\n\n\n/\n\n/gi;
					$keepalive_lines='';
					}
				else
					{$input_buf = "$input_buf$read_input_buf";}
				@input_lines = split(/\n\n/, $input_buf);

				if($DB){print "input buffer: $input_buf_length     lines: $#input_lines     partial: $partial\n";}
				if ( ($DB) && ($partial) ) {print "-----[$partial_input_buf]-----\n\n";}
				if($DB){print "|$input_buf|\n";}
				
				$manager_string = "$input_buf";
					&manager_output_logger;

				$input_buf = "$partial_input_buf";
				

				@command_line=@MT;
				$ILcount=0;
				foreach(@input_lines)
					{
					##### look for special vicidial conference call event #####
					if ( ($input_lines[$ILcount] =~ /CallerIDName: DCagcW/) && ($input_lines[$ILcount] =~ /Event: Dial|State: Up/) )
						{
						### BEGIN 1.2.X tree versions
						$input_lines[$ILcount] =~ s/^\n|^\n\n//gi;
						@command_line=split(/\n/, $input_lines[$ILcount]);

						if ($input_lines[$ILcount] =~ /Event: Dial/)
							{
							if ($command_line[3] =~ /Destination: /i)
								{
								$channel = $command_line[3];
								$channel =~ s/Destination: |\s*$//gi;
								$callid = $command_line[5];
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/SrcUniqueID: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows Conference DIALs updated|\n";}
									}
								}
							}
						if ($input_lines[$ILcount] =~ /State: Up/)
							{
							if ($command_line[2] =~ /Channel: /i)
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[5];
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/SrcUniqueID: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid' and status='SENT';";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								if($DB){print "|$affected_rows Conference DIALs updated|\n";}
								}
							}
						### END 1.2.X tree versions
						}

					##### parse through all other important events #####
					if ( ($input_lines[$ILcount] =~ /State: Ringing|State: Up|State: Dialing|Event: Newstate|Event: Hangup|Event: Newcallerid|Event: Shutdown|Event: CPD-Result|Event: SIP-Hangup-Cause/) && ($input_lines[$ILcount] !~ /ZOMBIE/) )
						{
						$input_lines[$ILcount] =~ s/^\n|^\n\n//gi;
						@command_line=split(/\n/, $input_lines[$ILcount]);
						
						if($DB)
							{
							### Pring the command_line structure to easily see where elements are
							$cmd_counter = 0;
							foreach ( @command_line )
								{
								print "command_line[$cmd_counter] = $_\n";
								$cmd_counter++;
								}						
							print "\n";
							}
						
						if ($input_lines[$ILcount] =~ /Event: Shutdown/)
							{
							$endless_loop=0;
							$one_day_interval=0;
							print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
								$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|";
							&event_logger;
							}

						if ($input_lines[$ILcount] =~ /Event: Hangup/)
							{
							### post 2005-08-07 CVS and Asterisk 1.8 -- added Privilege line
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[3] =~ /^Uniqueid: /i) ) 
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$uniqueid = $command_line[3];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='DEAD', channel='$channel' where server_ip = '$server_ip' and uniqueid = '$uniqueid' and callerid NOT LIKE \"DCagcW%\" and cmd_line_d!='Exten: 8309' and cmd_line_d!='Exten: 8310';";

								if ( ($channel !~ /local/i) && ($channel !~ /CXFER/i) )
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows HANGUPS updated|\n";}
									}
								else
									{
									print STDERR "Ignoring CXFER Local Hangup: |$channel|$server_ip|$uniqueid|$command_line[5]|\n";
									}
								}
							else
								{
								if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[4] =~ /^Uniqueid: /i) ) ### post 2006-03-20 SVN -- Added Timestamp line
									{
									$channel = $command_line[3];
									$channel =~ s/Channel: |\s*$//gi;
									$uniqueid = $command_line[4];
									$uniqueid =~ s/Uniqueid: |\s*$//gi;
									$stmtA = "UPDATE vicidial_manager set status='DEAD', channel='$channel' where server_ip = '$server_ip' and uniqueid = '$uniqueid' and callerid NOT LIKE \"DCagcW%\" and cmd_line_d!='Exten: 8309' and cmd_line_d!='Exten: 8310';";

									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows HANGUPS updated|\n";}
									}
								else
									{
									$channel = $command_line[1];
									$channel =~ s/Channel: |\s*$//gi;
									$uniqueid = $command_line[2];
									$uniqueid =~ s/Uniqueid: |\s*$//gi;
									$stmtA = "UPDATE vicidial_manager set status='DEAD', channel='$channel' where server_ip = '$server_ip' and uniqueid = '$uniqueid' and callerid NOT LIKE \"DCagcW%\" and cmd_line_d!='Exten: 8309' and cmd_line_d!='Exten: 8310';";

									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows HANGUPS updated|\n";}
									}
								}
							}
			
						if ($input_lines[$ILcount] =~ /State: Dialing/)
							{
							if ( ($command_line[1] =~ /^Channel: /i) && ($command_line[4] =~ /^Uniqueid: /i) ) ### pre 2004-10-07 CVS
								{
								$channel = $command_line[1];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[3];
								$callid =~ s/Callerid: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[4];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='SENT', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								
								if($DB){print "|$affected_rows DIALINGs updated|\n";}
								}
							if ( ($command_line[1] =~ /^Channel: /i) && ($command_line[4] =~ /^CalleridName: /i) ) ### post 2004-10-07 CVS
								{
								$channel = $command_line[1];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='SENT', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								if($DB){print "|$affected_rows DIALINGs updated|\n";}
								}
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[5] =~ /^CalleridName: /i) ) ### post 2005-08-07 CVS
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[5];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='SENT', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								if($DB){print "|$affected_rows DIALINGs updated|\n";}
								}
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[6] =~ /^CalleridName: /i) ) ### post 2006-03-20 -- Added Timestamp line
								{
								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[6];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[7];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='SENT', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								if($DB){print "|$affected_rows DIALINGs updated|\n";}
								}
							}
						if ($input_lines[$ILcount] =~ /State: Ringing|State: Up/)
							{
							if ( ($command_line[1] =~ /^Channel: /i) && ($command_line[4] =~ /^Uniqueid: /i) ) ### pre 2004-10-07 CVS
								{
								$channel = $command_line[1];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[3];
								$callid =~ s/Callerid: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[4];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							if ( ($command_line[1] =~ /^Channel: /i) && ($command_line[4] =~ /^CalleridName: /i) ) ### post 2004-10-07 CVS
								{
								$channel = $command_line[1];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[5] =~ /^CalleridName: /i) ) ### post 2005-08-07 CVS
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[5];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[6] =~ /^CalleridName: /i) ) ### post 2006-03-20 SVN -- Added Timestamp line
								{
								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[6];
								$callid =~ s/CalleridName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[7];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							}
			
						if ($input_lines[$ILcount] =~ /Event: Newcallerid/)
							{
							if ( ($command_line[1] =~ /^Channel: /i) && ($command_line[3] =~ /^Uniqueid: /i) ) 
								{
								$channel = $command_line[1];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[2];
								$callid =~ s/Callerid: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[3];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel =~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[6] =~ /^Uniqueid: /i) ) ### post 2006-03-20 SVN -- Added Timestamp line
								{
								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[5];
								$callid =~ s/Callerid: |\s*$//gi;
						#		$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel =~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								} 
							### post Asterisk 1.8 - Consultative XFER - Added 2014-05-24
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) && ($command_line[4] =~ /^CallerIDName: DC/i) )
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
						#		$callid =~ s/Callerid: |\s*$//gi;
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel =~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows Consultative XFERs updated|\n";}
									}
								}
						#	if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) ) ### post 2006-06-21 SVN -- Changed from CallerID to CallerIDName
						#		{
						#		$channel = $command_line[2];
						#		$channel =~ s/Channel: |\s*$//gi;
						#		$callid = $command_line[4];
						#		$callid =~ s/CallerIDName: |\s*$//gi;
						#		   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
						#		$uniqueid = $command_line[5];
						#		$uniqueid =~ s/Uniqueid: |\s*$//gi;
						#		$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
						#		if ($channel =~ /local/i)
						#			{
						#			print STDERR "|$stmtA|\n";
						#			my $affected_rows = $dbhA->do($stmtA);
						#			if($DB){print "|$affected_rows RINGINGs updated|\n";}
						#			}
						#		}
							}
						if ($input_lines[$ILcount] =~ /Event: CPD-Result/)
							{
							#	Event: CPD-Result
							#	Privilege: system,all
							#	ChannelDriver: SIP
							#	Channel: SIP/paraxip-out-08291448
							#	CallerIDName: V0202034729000030735
							#	Uniqueid: 1233564450.141
							#	Result: Answering-Machine
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) ) 
								{
									&get_time_now;

								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$result = $command_line[6];
								$result =~ s/Result: |\s*$//gi;
								if (length($result)>0)
									{
									# 2011-03-22 13:22:12.123   (1277187888 123 456)
									# ALTER TABLE vicidial_cpd_log ADD hires_time VARCHAR(26) default '';
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

									$lead_id = substr($callid, 10, 10);
									$lead_id = ($lead_id + 0);
								#	$stmtA = "INSERT INTO vicidial_cpd_log set channel='$channel', uniqueid='$uniqueid', callerid='$callid', server_ip='$server_ip', lead_id='$lead_id', event_date='$now_date', result='$result', hires_time='$HRnow_date';";
									$stmtA = "INSERT INTO vicidial_cpd_log set channel='$channel', uniqueid='$uniqueid', callerid='$callid', server_ip='$server_ip', lead_id='$lead_id', event_date='$now_date', result='$result';";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows CPD_log inserted|$HRnow_date|$s_hires|$usec|\n";}
									}
								}
							}
						if ($input_lines[$ILcount] =~ /Event: SIP-Hangup-Cause/)
							{
							#	Event: SIP-Hangup-Cause
							#	Privilege: system,all
							#	ChannelDriver: SIP
							#	Channel: SIP/paraxip-out-08291448
							#	CallerIDName: M4121149450000795193
							#	Uniqueid: 1233564450.141
							#	Result: 603
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) ) 
								{
									&get_time_now;

								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$result = $command_line[6];
								$result =~ s/Result: |\s*$//gi;
								@result_details=split(/\|/, $result);
								if ( (length($result)>0) && ($result_details[0] !~ /^407/) )
									{
									$lead_id = substr($callid, 10, 10);
									$lead_id = ($lead_id + 0);
									$beginUNIQUEID = $uniqueid;
									$beginUNIQUEID =~ s/\..*//gi;
									$stmtA = "UPDATE vicidial_dial_log SET sip_hangup_cause='$result_details[0]',sip_hangup_reason='$result_details[1]',uniqueid='$uniqueid' where caller_code='$callid' and server_ip='$server_ip' and lead_id='$lead_id';";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows dial_log updated|$callid|$server_ip|$result|\n";}
									$vddl_update = ($vddl_update + $affected_rows);

									$preCtarget = ($beginUNIQUEID - 180);	# 180 seconds before call start
									($preCsec,$preCmin,$preChour,$preCmday,$preCmon,$preCyear,$preCwday,$preCyday,$preCisdst) = localtime($preCtarget);
									$preCyear = ($preCyear + 1900);
									$preCmon++;
									if ($preCmon < 10) {$preCmon = "0$preCmon";}
									if ($preCmday < 10) {$preCmday = "0$preCmday";}
									if ($preChour < 10) {$preChour = "0$preChour";}
									if ($preCmin < 10) {$preCmin = "0$preCmin";}
									if ($preCsec < 10) {$preCsec = "0$preCsec";}
									$preCSQLdate = "$preCyear-$preCmon-$preCmday $preChour:$preCmin:$preCsec";

									$postCtarget = ($beginUNIQUEID + 10);	# 10 seconds after call start
									($postCsec,$postCmin,$postChour,$postCmday,$postCmon,$postCyear,$postCwday,$postCyday,$postCisdst) = localtime($postCtarget);
									$postCyear = ($postCyear + 1900);
									$postCmon++;
									if ($postCmon < 10) {$postCmon = "0$postCmon";}
									if ($postCmday < 10) {$postCmday = "0$postCmday";}
									if ($postChour < 10) {$postChour = "0$postChour";}
									if ($postCmin < 10) {$postCmin = "0$postCmin";}
									if ($postCsec < 10) {$postCsec = "0$postCsec";}
									$postCSQLdate = "$postCyear-$postCmon-$postCmday $postChour:$postCmin:$postCsec";

									$stmtA = "UPDATE vicidial_carrier_log SET sip_hangup_cause='$result_details[0]',sip_hangup_reason='$result_details[1]' where server_ip='$server_ip' and caller_code='$callid' and lead_id='$lead_id' and call_date > \"$preCSQLdate\" and call_date < \"$postCSQLdate\" order by call_date desc limit 1;";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows carrier_log updated|$callid|$server_ip|$uniqueid|$result_details[0]|$result_details[1]|\n";}
									$vdcl_update = ($vdcl_update + $affected_rows);
									}
								}
							}
						}
					$ILcount++;
					}
				}


			$endless_loop--;
			$keepalive_count_loop++;

			if($DB){print STDERR "	  loop counter: |$endless_loop|$keepalive_count_loop|     |$vddl_update|$vdcl_update|\r";}

			### putting a blank file called "sendmgr.kill" in a directory will automatically safely kill this program
			if ( (-e "$PATHhome/listenmgr.kill") or ($sendonlyone) )
				{
				unlink("$PATHhome/listenmgr.kill");
				$endless_loop=0;
				$one_day_interval=0;
				print "\nPROCESS KILLED MANUALLY... EXITING\n\n";
				}

			### run a keepalive command to flush whatever is in the buffer through and to keep the connection alive
			### Also, keep the MySQL connection alive by selecting the server_updater time for this server
			if ($endless_loop =~ /00$|50$/) 
				{
				$keepalive_lines='';

				&get_time_now;

				### Grab Server values from the database
				$stmtA = "SELECT vd_server_logs FROM servers where server_ip = '$VARserver_ip';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBvd_server_logs =		$aryA[0];
					if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
					else {$SYSLOG = '0';}
					}
				$sthA->finish();

				### Grab Server values to keep DB connection alive
				$stmtA = "SELECT last_update FROM server_updater where server_ip = '$server_ip';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				@aryA = $sthA->fetchrow_array;
				$last_update = $aryA[0];
				$sthA->finish();

				$keepalive_epoch = time();
				$keepalive_sec = ($keepalive_epoch - $last_keepalive_epoch);
				if ($keepalive_sec > 40) 
					{
					$keepalive_skips=0;
					@keepalive_output = $tn->cmd(String => "Action: Command\nCommand: show uptime\n\n", Prompt => '/--END COMMAND--.*/', Errmode => Return, Timeout => 1); 
					$msg = $tn->errmsg;
					$buf_ref = $tn->buffer;
					$buf_len = length( $$buf_ref );
					if($DB){print "keepalive length: $#keepalive_output|$now_date|$msg|$buf_len|\n";}

					if($DB){print "+++++++++++++++++++++++++++++++sending keepalive transmit line: $keepalive_sec seconds   $endless_loop|$now_date|$last_update|\n";}

					$k=0;
					foreach(@keepalive_output) 
						{
						$keepalive_lines .= "$keepalive_output[$k]";
						$k++;
						}
					$manager_string="PROCESS: keepalive length: $#keepalive_output|$k|$now_date";
					&manager_output_logger;
					$last_keepalive_epoch = time();
					}
				else
					{
					$keepalive_skips++;
					$buf_ref = $tn->buffer;
					$buf_len = length( $$buf_ref );
					if($DB){print "-------------------------------no keepalive transmit necessary: $keepalive_sec seconds($keepalive_skips in a row)   $endless_loop|$now_date|$last_update|$buf_len|\n";}

					$manager_string="PROCESS: keepalive skip: $keepalive_sec seconds($keepalive_skips in a row)|$now_date";
					&manager_output_logger;
					}
				#$last_keepalive_epoch = time();
				$keepalive_count_loop=0;
				}
			}
		### END manager event handling for asterisk version < 1.6
		}
	else 
		{
		### BEGIN manager event handling for asterisk version >= 1.6
		
		while($endless_loop > 0)
			{
			### sleep for 10 hundredths of a second
			usleep(1*100*1000);
			
			$msg='';
			$read_input_buf = $tn->get(Errmode => Return, Timeout => 1,);
			$input_buf_length = length($read_input_buf);
			$msg = $tn->errmsg;
			if (($msg =~ /read timed-out/i) || ( $msg eq ''  ))
				{
					# This is normal
				} 
			else 
				{
					print "ERRMSG: |$msg|\n";
				}
			if ($msg =~ /filehandle isn\'t open/i)
				{
				$endless_loop=0;
				$one_day_interval=0;
				print "ERRMSG: |$msg|\n";
				print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
					$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|$msg|";
				&event_logger;
				}

			if ( ($read_input_buf !~ /\n\n/) or ($input_buf_length < 10) )
				{
				#if ($read_input_buf =~ /\n/) {print "\n|||$input_buf_length|||$read_input_buf|||\n";}
				if ($endless_loop =~ /00$|50$/) 
					{
					$input_buf = "$input_buf$keepalive_lines$read_input_buf";
					$input_buf =~ s/\n\n\n/\n\n/gi;
					$keepalive_lines='';
					}
				else
					{$input_buf = "$input_buf$read_input_buf";}
				}
			else
				{
				$partial=0;
				$partial_input_buf='';
				@input_lines=@MT;

				if ($read_input_buf !~ /\n\n$/)
					{
					$read_input_buf =~ s/\(|\)/ /gi; # replace parens with space
					$partial_input_buf = $read_input_buf;
					$partial_input_buf =~ s/\n/-----/gi;
					$partial_input_buf =~ s/\*/\\\*/gi;
					$partial_input_buf =~ s/.*----------//gi;
					$partial_input_buf =~ s/-----/\n/gi;
					$read_input_buf =~ s/$partial_input_buf$//gi;
					$partial++;
					}

				if ($endless_loop =~ /00$|50$/) 
					{
					$input_buf = "$input_buf$keepalive_lines$read_input_buf";
					$input_buf =~ s/\n\n\n/\n\n/gi;
					$keepalive_lines='';
					}
				else
					{$input_buf = "$input_buf$read_input_buf";}
				@input_lines = split(/\n\n/, $input_buf);


				if($DB){print "input buffer: $input_buf_length     lines: $#input_lines     partial: $partial\n";}
				if ( ($DB) && ($partial) ) {print "-----[$partial_input_buf]-----\n\n";}
				if($DB){print "|$input_buf|\n";}
				
				$manager_string = "$input_buf";
					&manager_output_logger;

				$input_buf = "$partial_input_buf";
				

				@command_line=@MT;
				$ILcount=0;
				foreach(@input_lines)
					{
					##### look for special vicidial conference call event #####
					if ( ($input_lines[$ILcount] =~ /CallerIDName: DCagcW/) && ($input_lines[$ILcount] =~ /Event: Dial|ChannelStateDesc: Up/) )
						{
						$input_lines[$ILcount] =~ s/^\n|^\n\n//gi;
						@command_line=split(/\n/, $input_lines[$ILcount]);
						
						$cmd_counter = 0;
						foreach ( @command_line )
							{
							print "command_line[$cmd_counter] = $_\n";
							$cmd_counter++;
							}						
						print "\n";

						if ($input_lines[$ILcount] =~ /Event: Dial/)
							{
							if ($command_line[4] =~ /Destination: /i)
								{
								$channel = $command_line[4];
								$channel =~ s/Destination: |\s*$//gi;
								$callid = $command_line[6];
								$callid =~ s/CallerIDName: |\s*$//gi;
								$callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[6];
								$uniqueid =~ s/SrcUniqueID: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel !~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows Conference DIALs updated|\n";}
									}
								}
							}
						if ($input_lines[$ILcount] =~ /ChannelStateDesc: Up/)
							{
							if ($command_line[2] =~ /Channel: /i)
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[6];
								$callid =~ s/CallerIDName: |\s*$//gi;
								$callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[9];
								$uniqueid =~ s/SrcUniqueID: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid' and status='SENT';";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);
								if($DB){print "|$affected_rows Conference DIALs updated|\n";}
								}
							}
						}

					##### parse through all other important events #####
					if ( ($input_lines[$ILcount] =~ /Event: Newstate|Event: Hangup|Event: NewCallerid|Event: Shutdown|Event: CPD-Result|Event: SIP-Hangup-Cause|Event: DTMF/) && ($input_lines[$ILcount] !~ /ZOMBIE/) )
						{
						$input_lines[$ILcount] =~ s/^\n|^\n\n//gi;
						@command_line=split(/\n/, $input_lines[$ILcount]);
						
						if($DB)
							{
							### Pring the command_line structure to easily see where elements are
							$cmd_counter = 0;
							foreach ( @command_line )
								{
								print "command_line[$cmd_counter] = $_\n";
								$cmd_counter++;
								}						
							print "\n";
							}
						
						### Event: Shutdown
						if ($input_lines[$ILcount] =~ /Event: Shutdown/)
							{
							$endless_loop=0;
							$one_day_interval=0;
							print "\nAsterisk server shutting down, PROCESS KILLED... EXITING\n\n";
								$event_string="Asterisk server shutting down, PROCESS KILLED... EXITING|ONE DAY INTERVAL:$one_day_interval|";
							&event_logger;
							}
							
						### Event: Hangup
						if ($input_lines[$ILcount] =~ /Event: Hangup/)
							{
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[3] =~ /^Uniqueid: /i) )
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$uniqueid = $command_line[3];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='DEAD', channel='$channel' where server_ip = '$server_ip' and uniqueid = '$uniqueid' and callerid NOT LIKE \"DCagcW%\" and cmd_line_d!='Exten: 8309' and cmd_line_d!='Exten: 8310';";
								if ( ($channel !~ /local/i) && ($channel !~ /CXFER/i) )
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows HANGUPS updated|\n";}
									}
								else
									{
									print STDERR "Ignoring CXFER Local Hangup: |$channel|$server_ip|$uniqueid|$command_line[5]|\n";
									}
								}
							}
							
						### Event: Newstate
						if ($input_lines[$ILcount] =~ /Event: Newstate/)
							{
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[6] =~ /^CallerIDName: /i) )
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$state = $command_line[4];
								$state =~ s/ChannelStateDesc: |\s*$//gi;
								$callid = $command_line[6];
								$callid =~ s/CallerIDName: |\s*$//gi;
								$callid =~ s/^\"//gi;	# remove leading quotes
								$callid =~ s/\".*$//gi;	# remove trailing quotes and anything else
								if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;} # remove everything after the space for Orex
								$uniqueid = $command_line[9];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								
								### ChannelStateDesc = Dialing
								if ($state =~ /Dialing/)
									{
									$stmtA = "UPDATE vicidial_manager set status='SENT', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows DIALINGs updated|\n";}
									}
								
								### ChannelStateDesc = Ringing or Up
								if ($state =~ /Ringing|Up/)
									{
									$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
									print STDERR "|$stmtA|\n";
									if ($channel !~ /local/i)
										{
										print STDERR "|$channel|NON LOCAL CHANNEL >>>> EXECUTING ABOVE STATMENT|\n";
										my $affected_rows = $dbhA->do($stmtA);
										if($DB){print "|$affected_rows RINGINGs updated|\n";}
										}
									else
										{
										print STDERR "|$channel|LOCAL CHANNEL >>>> ABOVE STATMENT IGNORED|\n";
										}
									}								
								}
							}
			
						### Event: NewCallerid
						if ($input_lines[$ILcount] =~ /Event: NewCallerid/)
							{
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) )
								{
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CallerIDName: |\s*$//gi;
								$callid =~ s/^\"//gi;	# remove leading quotes
								$callid =~ s/\".*$//gi;	# remove trailing quotes and anything else
								if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;} # remove everything after the space for Orex
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$stmtA = "UPDATE vicidial_manager set status='UPDATED', channel='$channel', uniqueid = '$uniqueid' where server_ip = '$server_ip' and callerid = '$callid'";
								if ($channel =~ /local/i)
									{
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows RINGINGs updated|\n";}
									}
								}
							}

						### Event: DTMF
						if ($input_lines[$ILcount] =~ /Event: DTMF/)
							{
							if ( ($command_line[2] =~ /^Channel: /i) && ($command_line[3] =~ /^Uniqueid: /i) )
								{
								# get the event info
								$channel = $command_line[2];
								$channel =~ s/Channel: |\s*$//gi;
								$uniqueid = $command_line[3];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$digit = $command_line[4];
								$digit =~ s/Digit: |\s*$//gi;
								$direction = $command_line[5];
								$direction =~ s/Direction: |\s*$//gi;
								$begin =  $command_line[6];
								$begin =~ s/Begin: |\s*$//gi;
								$end = $command_line[7];
								$end =~ s/End: |\s*$//gi;

								# set the state
								$state = '';
								if ($begin eq 'Yes') {$state = 'Begin';}
								else 
									{
									if ($end eq 'Yes') {$state = 'End';}
									}

								# log it to the DB and file
								$stmtA = "INSERT INTO vicidial_dtmf_log SET dtmf_time=NOW(),channel='$channel',server_ip='$server_ip',uniqueid='$uniqueid',digit='$digit',direction='$direction',state='$state'";
								print STDERR "|$stmtA|\n";
								my $affected_rows = $dbhA->do($stmtA);

								($s_hires, $usec) = gettimeofday();   # get seconds and microseconds since the epoch
								$usec = sprintf("%06s", $usec);
								$HRmsec = substr($usec, -6);
								($HRsec,$HRmin,$HRhour,$HRmday,$HRmon,$HRyear,$HRwday,$HRyday,$HRisdst) = localtime($s_hires);
								$HRyear = ($HRyear + 1900);
								$HRmon++;
								if ($HRmon < 10) {$HRmon = "0$HRmon";}
								if ($HRmday < 10) {$HRmday = "0$HRmday";}
								if ($HRhour < 10) {$HRFhour = "0$HRhour";}
								if ($HRmin < 10) {$HRmin = "0$HRmin";}
								if ($HRsec < 10) {$HRsec = "0$HRsec";}
								$HRnow_date = "$HRyear-$HRmon-$HRmday $HRhour:$HRmin:$HRsec.$HRmsec";
								if($DB){print "|$affected_rows vicidial_dtmf inserted|$HRnow_date|$s_hires|$usec|\n";}

								$dtmf_string = "$HRnow_date|$s_hires|$usec|$channel|$uniqueid|$digit|$direction|$state";
								&dtmf_logger;
								}
							}

						### Event: CPD-Result
						if ($input_lines[$ILcount] =~ /Event: CPD-Result/)
							{
							#	Event: CPD-Result
							#	Privilege: system,all
							#	ChannelDriver: SIP
							#	Channel: SIP/paraxip-out-08291448
							#	CallerIDName: V0202034729000030735
							#	Uniqueid: 1233564450.141
							#	Result: Answering-Machine
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) ) 
								{
								&get_time_now;

								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CallerIDName: |\s*$//gi;
								$callid =~ s/^\"//gi;	# remove leading quotes
								$callid =~ s/\".*$//gi;	# remove trailing quotes and anything else
								if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;} # remove everything after the space for Orex
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;

								# Pulled from the CPD-Result SIP Header
								$cpd_result = $command_line[6];
								$cpd_result =~ s/CPDResult: |\s*$//gi;

								# Pulled from the X-Netborder-Detailed-CPD-Result-v2-0 SIP Header
								$cpd_detailed_result = $command_line[7];
								$cpd_detailed_result =~ s/CPDDetailedResult: |\s*$//gi;

								# Pulled from the X-Netborder-Call-ID SIP Header
								$cpd_call_id = $command_line[8];
								$cpd_call_id =~ s/CPDCallID: |\s*$//gi;

								# Pulled from the X-Netborder-Cpa-Reference-ID SIP Header
								$cpd_ref_id = $command_line[9];
								$cpd_ref_id =~ s/CPDReferenceID: |\s*$//gi;

								# Pulled from the X-Netborder-Cpa-Campaign-Name SIP Header
								$cpd_camp_name = $command_line[10];
								$cpd_camp_name =~ s/CPDCampaignName: |\s*$//gi;
							
								print STDERR "|cpd_result = $cpd_result|cpd_detailed_result = $cpd_detailed_result|cpd_call_id = $cpd_call_id|cpd_ref_id = $cpd_ref_id|cpd_camp_name = $cpd_camp_name|\n";	
								
								if (length($cpd_result)>0)
									{
									# 2011-03-22 13:22:12.123   (1277187888 123 456)
									# ALTER TABLE vicidial_cpd_log ADD hires_time VARCHAR(26) default '';
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

									$lead_id = substr($callid, 10, 10);
									$lead_id = ($lead_id + 0);
								#	$stmtA = "INSERT INTO vicidial_cpd_log set channel='$channel', uniqueid='$uniqueid', callerid='$callid', server_ip='$server_ip', lead_id='$lead_id', event_date='$now_date', result='$cpd_result', hires_time='$HRnow_date';";

									# TODO change the cpd log and this insert to include the new SIP Headers for 2.0 CPD
									$stmtA = "INSERT INTO vicidial_cpd_log set channel='$channel', uniqueid='$uniqueid', callerid='$callid', server_ip='$server_ip', lead_id='$lead_id', event_date='$now_date', result='$cpd_result';";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows CPD_log inserted|$HRnow_date|$s_hires|$usec|\n";}
									}
								}
							}
						if ($input_lines[$ILcount] =~ /Event: SIP-Hangup-Cause/)
							{
							#	Event: SIP-Hangup-Cause
							#	Privilege: system,all
							#	ChannelDriver: SIP
							#	Channel: SIP/paraxip-out-08291448
							#	CallerIDName: M4121149450000795193
							#	Uniqueid: 1233564450.141
							#	Result: 603
							if ( ($command_line[3] =~ /^Channel: /i) && ($command_line[5] =~ /^Uniqueid: /i) ) 
								{
									&get_time_now;

								$channel = $command_line[3];
								$channel =~ s/Channel: |\s*$//gi;
								$callid = $command_line[4];
								$callid =~ s/CallerIDName: |\s*$//gi;
								   $callid =~ s/^\"//gi;   $callid =~ s/\".*$//gi;
								   if ($callid =~ /\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S\S/) {$callid =~ s/ .*//gi;}
								$uniqueid = $command_line[5];
								$uniqueid =~ s/Uniqueid: |\s*$//gi;
								$result = $command_line[6];
								$result =~ s/Result: |\s*$//gi;
								@result_details=split(/\|/, $result);
								if ( (length($result)>0) && ($result_details[0] !~ /^407/) )
									{
									$lead_id = substr($callid, 10, 10);
									$lead_id = ($lead_id + 0);
									$beginUNIQUEID = $uniqueid;
									$beginUNIQUEID =~ s/\..*//gi;
									$stmtA = "UPDATE vicidial_dial_log SET sip_hangup_cause='$result_details[0]',sip_hangup_reason='$result_details[1]',uniqueid='$uniqueid' where caller_code='$callid' and server_ip='$server_ip' and lead_id='$lead_id';";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows dial_log updated|$callid|$server_ip|$result|\n";}
									$vddl_update = ($vddl_update + $affected_rows);

									$preCtarget = ($beginUNIQUEID - 180);	# 180 seconds before call start
									($preCsec,$preCmin,$preChour,$preCmday,$preCmon,$preCyear,$preCwday,$preCyday,$preCisdst) = localtime($preCtarget);
									$preCyear = ($preCyear + 1900);
									$preCmon++;
									if ($preCmon < 10) {$preCmon = "0$preCmon";}
									if ($preCmday < 10) {$preCmday = "0$preCmday";}
									if ($preChour < 10) {$preChour = "0$preChour";}
									if ($preCmin < 10) {$preCmin = "0$preCmin";}
									if ($preCsec < 10) {$preCsec = "0$preCsec";}
									$preCSQLdate = "$preCyear-$preCmon-$preCmday $preChour:$preCmin:$preCsec";

									$postCtarget = ($beginUNIQUEID + 10);	# 10 seconds after call start
									($postCsec,$postCmin,$postChour,$postCmday,$postCmon,$postCyear,$postCwday,$postCyday,$postCisdst) = localtime($postCtarget);
									$postCyear = ($postCyear + 1900);
									$postCmon++;
									if ($postCmon < 10) {$postCmon = "0$postCmon";}
									if ($postCmday < 10) {$postCmday = "0$postCmday";}
									if ($postChour < 10) {$postChour = "0$postChour";}
									if ($postCmin < 10) {$postCmin = "0$postCmin";}
									if ($postCsec < 10) {$postCsec = "0$postCsec";}
									$postCSQLdate = "$postCyear-$postCmon-$postCmday $postChour:$postCmin:$postCsec";

									$stmtA = "UPDATE vicidial_carrier_log SET sip_hangup_cause='$result_details[0]',sip_hangup_reason='$result_details[1]' where server_ip='$server_ip' and caller_code='$callid' and lead_id='$lead_id' and call_date > \"$preCSQLdate\" and call_date < \"$postCSQLdate\" order by call_date desc limit 1;";
									print STDERR "|$stmtA|\n";
									my $affected_rows = $dbhA->do($stmtA);
									if($DB){print "|$affected_rows carrier_log updated|$callid|$server_ip|$uniqueid|$result_details[0]|$result_details[1]|\n";}
									$vdcl_update = ($vdcl_update + $affected_rows);
									}
								}
							}
						}
					$ILcount++;
					}
				}


			$endless_loop--;
			$keepalive_count_loop++;

			if($DB){print STDERR "	  loop counter: |$endless_loop|$keepalive_count_loop|     |$vddl_update|$vdcl_update|\r";}

			### putting a blank file called "sendmgr.kill" in a directory will automatically safely kill this program
			if ( (-e "$PATHhome/listenmgr.kill") or ($sendonlyone) )
				{
				unlink("$PATHhome/listenmgr.kill");
				$endless_loop=0;
				$one_day_interval=0;
				print "\nPROCESS KILLED MANUALLY... EXITING\n\n";
				}

			### run a keepalive command to flush whatever is in the buffer through and to keep the connection alive
			### Also, keep the MySQL connection alive by selecting the server_updater time for this server
			if ($endless_loop =~ /00$|50$/) 
				{
				$keepalive_lines='';

				&get_time_now;

				### Grab Server values from the database
				$stmtA = "SELECT vd_server_logs FROM servers where server_ip = '$VARserver_ip';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				if ($sthArows > 0)
					{
					@aryA = $sthA->fetchrow_array;
					$DBvd_server_logs =			$aryA[0];
					if ($DBvd_server_logs =~ /Y/)	{$SYSLOG = '1';}
					else {$SYSLOG = '0';}
					}
				$sthA->finish();

				### Grab Server values to keep DB connection alive
				$stmtA = "SELECT last_update FROM server_updater where server_ip = '$server_ip';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				@aryA = $sthA->fetchrow_array;
				$last_update	=	$aryA[0];
				$sthA->finish();

				$keepalive_epoch = time();
				$keepalive_sec = ($keepalive_epoch - $last_keepalive_epoch);
				if ($keepalive_sec > 40) 
					{
					$keepalive_skips=0;
					@keepalive_output = $tn->cmd(String => "Action: Command\nCommand: core show uptime\n\n", Prompt => '/--END COMMAND--.*/', Errmode => Return, Timeout => 1); 
					$msg = $tn->errmsg;
					$buf_ref = $tn->buffer;
                                        $buf_len = length( $$buf_ref );
					if($DB){print "keepalive length: $#keepalive_output|$now_date|$msg|$buf_len|\n";}

					if($DB){print "+++++++++++++++++++++++++++++++sending keepalive transmit line: $keepalive_sec seconds   $endless_loop|$now_date|$last_update|\n";}

					$k=0;
					foreach(@keepalive_output) 
						{
						$keepalive_lines .= "$keepalive_output[$k]";
						$k++;
						}
					$manager_string="PROCESS: keepalive length: $#keepalive_output|$k|$now_date";
					&manager_output_logger;
					$last_keepalive_epoch = time();
					}
				else
					{
					$keepalive_skips++;
					$buf_ref = $tn->buffer;
                                        $buf_len = length( $$buf_ref );
					if($DB){print "-------------------------------no keepalive transmit necessary: $keepalive_sec seconds($keepalive_skips in a row)   $endless_loop|$now_date|$last_update|$buf_len|\n";}

					$manager_string="PROCESS: keepalive skip: $keepalive_sec seconds($keepalive_skips in a row)|$now_date";
					&manager_output_logger;
					}
				#$last_keepalive_epoch = time();

				$keepalive_count_loop=0;
				}
			}
		### END manager event handling for asterisk version >= 1.6
		}
	


	if($DB){print "DONE... Exiting... Goodbye... See you later... Not really, initiating next loop...$one_day_interval left\n";}

	$event_string='HANGING UP|';
	&event_logger;

	@hangup = $tn->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/", Errmode    => Return, Timeout    => 1); 

	$tn->buffer_empty;
	$tn->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
	$ok = $tn->close;

	$one_day_interval--;
	}

$event_string='CLOSING DB CONNECTION|';
&event_logger;


$dbhA->disconnect();


if($DB){print "DONE... Exiting... Goodbye... See you later... Really I mean it this time\n";}


exit;







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
		open(Lout, ">>$PATHlogs/listen_process.$action_log_date")
				|| die "Can't open $PATHlogs/listen_process.$action_log_date: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}




sub manager_output_logger
	{
	if ($SYSLOG)
		{
		open(MOout, ">>$PATHlogs/listen.$action_log_date")
				|| die "Can't open $PATHlogs/listen.$action_log_date: $!\n";
		print MOout "$now_date|$manager_string|\n";
		close(MOout);
		}
	}

sub dtmf_logger
	{
	if ($SYSLOG)
		{
		open(Dout, ">>$PATHlogs/dtmf.$action_log_date")
				|| die "Can't open $PATHlogs/dttmf.$action_log_date: $!\n";
		print Dout "|$dtmf_string|\n";
		close(Dout);
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
