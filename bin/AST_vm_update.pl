#!/usr/bin/perl
#
# AST_vm_update.pl version 2.14
#
# DESCRIPTION:
# uses the Asterisk Manager interface to update the count of voicemail messages 
# for each mailbox (in the phone and vicidial_voicemail tables) in the voicemail
# table list is used by client apps for voicemail notification
#
# If this script is run ever minute there is a theoretical limit of 
# 1200 mailboxes that it can check due to the wait interval. If you have 
# more than this either change the cron when this script is run or change the 
# wait interval below
#
# NOTE: THIS SHOULD ONLY BE RUN ON THE DESIGNATED VOICEMAIL SERVER IN A CLUSTER!
#
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 50823-1422 - Added database server variable definitions lookup
# 50823-1452 - Added commandline arguments for debug at runtime
# 60717-1204 - Changed to DBI by Marin Blu
# 60715-2301 - Changed to use /etc/astguiclient.conf for configs
# 90919-1739 - Added other voicemail checking from vicidial_voicemail table
# 100625-1220 - Added waitfors after logout to fix broken pipe errors in asterisk <MikeC>
# 130108-1713 - Changes for Asterisk 1.8 compatibility
# 141124-1019 - Changed to only allow running on designated voicemail server, run for all mailboxes
# 150610-1200 - Added support for AMI version 1.3
# 170609-0936 - Added support for Asterisk 13
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages per minute, can be overridden by CLI flag
$DBX=0;  # Extra Debug flag, set to 0 for no debug messages per minute, can be overridden by CLI flag
$US='__';
$MT[0]='';
$secX = time();

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
		print "  [-t] = test\n";
		print "  [-debug] = verbose debug messages\n";
		print "  [-debugX] = extra verbose debug messages\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /-debug/i)
			{
			$DB=1; # Debug flag
			print "Debug output enabled\n";
			}
		if ($args =~ /-debugX/i)
			{
			$DBX=1; # Debug flag
			print "Extra Debug output enabled\n";
			}
		if ($args =~ /-t/i)
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
$voicemail_boxes=0;
$voicemail_updates=0;

if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Net::Telnet ();
	  
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

### find out if this server is the active_voicemail_server, if not, exit.
$stmtA = "SELECT count(*) from system_settings where active_voicemail_server='$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
if ($DB) {print "|$stmtA|\n";}
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$active_voicemail_server = $aryA[0];
	}
$sthA->finish(); 

if ($active_voicemail_server < 1)
	{
	if ($DB) {print "This is not the active voicemail server, exiting: |$server_ip|$active_voicemail_server|\n";}
	exit;
	}

### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,asterisk_version FROM servers where server_ip = '$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
if ($DBX) {print "|$stmtA|\n";}
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
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
	$asterisk_version	=		$aryA[11];
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
	}
$sthA->finish(); 

@PTvoicemail_ids=@MT;
$stmtA = "SELECT distinct voicemail_id from phones;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($DB) {print "$sthArows|$stmtA|\n";}
$rec_count=0;
while ($sthArows > $rec_count)
    {
	@aryA = $sthA->fetchrow_array;
	$PTvoicemail_ids[$rec_count] =	 $aryA[0];
	$rec_count++;
    }
$sthA->finish(); 

$max_buffer = 4*1024*1024; # 4 meg buffer

### connect to asterisk manager through telnet
$t = new Net::Telnet (
        Port => $telnet_port,
        Prompt => '/\r\n/',
        Output_record_separator => "\n\n",
        Max_buffer_length => $max_buffer,
        Telnetmode => 0,
);

##### uncomment both lines below for telnet log
#        $LItelnetlog = "$PATHlogs/listen_telnet_log.txt";
#        $fh = $t->dump_log("$LItelnetlog");

if (length($ASTmgrUSERNAMEsend) > 3) {$telnet_login = $ASTmgrUSERNAMEsend;}
else {$telnet_login = $ASTmgrUSERNAME;}
$t->open("$telnet_host");
$t->waitfor('/Asterisk Call Manager\//');

# get the AMI version number
$ami_version = $t->getline(Errmode => Return, Timeout => 1,);
$ami_version =~ s/\n//gi;
if ($DB) {print "----- AMI Version $ami_version -----\n";}

# Login
$t->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET");
$t->waitfor('/Authentication accepted/');              # waitfor auth accepted

$t->buffer_empty;


$i=0;
foreach(@PTvoicemail_ids)
	{
	@list_channels=@MT;
	$t->buffer_empty;
	%ast_ver_str = parse_asterisk_version($asterisk_version);
	if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 6))
		{
		@list_channels = $t->cmd(String => "Action: MailboxCount\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Pong.*/'); 
		
		$j=0;
		foreach(@list_channels)
			{
			if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
				{
				$NEW_messages[$i] = "$list_channels[$j+1]";
				$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
				$OLD_messages[$i] = "$list_channels[$j+2]";
				$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
				}
			$j++;
			}
		}
	elsif (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 13))
		{
		@list_channels = $t->cmd(String => "Action: MailboxCount\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/');
	
		$j=0;
		foreach(@list_channels)
			{
			if($DBX){print "$j - $list_channels[$j]";}
			if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
				{
				$URG_messages[$i] = "$list_channels[$j+1]";
				$URG_messages[$i] =~ s/UrgMessages: |\n//gi;
				$NEW_messages[$i] = "$list_channels[$j+2]";
				$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
				$OLD_messages[$i] = "$list_channels[$j+3]";
				$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
				}
			$j++;
			}
		}
	else
		{
		# get the current time
			( $now_sec, $now_micro_sec ) = gettimeofday();

			# figure out how many micro seconds since epoch
			$now_micro_epoch = $now_sec * 1000000;
			$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

			$begin_micro_epoch = $now_micro_epoch;

			# create a new action id
			$action_id = "$now_sec.$now_micro_sec";

			@list_channels = $t->cmd(String => "Action: MailboxCount\nActionID: $action_id\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/');

			$j=0;
			foreach(@list_channels)
				{
				if($DBX){print "$j - $list_channels[$j]";}
				if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
					{
					$URG_messages[$i] = "$list_channels[$j+1]";
					$URG_messages[$i] =~ s/UrgMessages: |\n//gi;
					$NEW_messages[$i] = "$list_channels[$j+2]";
					$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
					$OLD_messages[$i] = "$list_channels[$j+3]";
					$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
					}
				$j++;
				}
			}

	@PTextensions=@MT;   @PTmessages=@MT;   @PTold_messages=@MT;   @PTserver_ips=@MT;
	$stmtA = "SELECT extension,messages,old_messages,server_ip from phones where voicemail_id='$PTvoicemail_ids[$i]';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DBX) {print "$sthArows|$stmtA|\n";}
	$rec_countX=0;
	while ($sthArows > $rec_countX)
		{
		@aryA = $sthA->fetchrow_array;
		$PTextensions[$rec_countX] =	$aryA[0];
		$PTmessages[$rec_countX] =		$aryA[1];
		$PTold_messages[$rec_countX] =	$aryA[2];
		$PTserver_ips[$rec_countX] =	$aryA[3];
		$rec_countX++;
		}
	$sthA->finish(); 

	$rec_countX=0;
	while ($sthArows > $rec_countX)
		{
		if($DB){print "MailboxCount- $PTvoicemail_ids[$i]    NEW:|$NEW_messages[$i]|  OLD:|$OLD_messages[$i]|    ";}
		if ( ($NEW_messages[$i] eq $PTmessages[$rec_countX]) && ($OLD_messages[$i] eq $PTold_messages[$rec_countX]) )
			{
			if($DB){print "MESSAGE COUNT UNCHANGED, DOING NOTHING FOR THIS MAILBOX: |$PTserver_ips[$rec_countX]|$PTextensions[$rec_countX]|\n";}
			}
		else
			{
			$stmtA = "UPDATE phones set messages='$NEW_messages[$i]', old_messages='$OLD_messages[$i]' where server_ip='$PTserver_ips[$rec_countX]' and extension='$PTextensions[$rec_countX]'";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
			$voicemail_updates = ($voicemail_updates + $affected_rows);
			}
		$rec_countX++;
		}

	$i++;
	$voicemail_boxes++;
	### sleep for 5 hundredths of a second
	usleep(1*50*1000);
	}


##### BEGIN check vicidial_voicemail entries #####

### find out if this server is the active_voicemail_server
$stmtA = "SELECT count(*) from system_settings where active_voicemail_server='$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
if ($DB) {print "|$stmtA|\n";}
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$active_voicemail_server = $aryA[0];
	}
$sthA->finish(); 

if ($active_voicemail_server > 0)
	{
	if($DB){print "Active Voicemail Server, checking vicidial_voicemail boxes...\n";}
	@PTvoicemail_ids=@MT; @PTmessages=@MT; @PTold_messages=@MT; @NEW_messages=@MT; @OLD_messages=@MT;
	$stmtA = "SELECT voicemail_id,messages,old_messages from vicidial_voicemail where active='Y';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($DB) {print "$sthArows|$stmtA|\n";}
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$PTvoicemail_ids[$rec_count] =	 $aryA[0];
		$PTmessages[$rec_count] =		 $aryA[1];
		$PTold_messages[$rec_count] =	 $aryA[2];
		$rec_count++;
		}
	$sthA->finish(); 

	$i=0;
	foreach(@PTvoicemail_ids)
		{
		@list_channels=@MT;
		$t->buffer_empty;
		%ast_ver_str = parse_asterisk_version($asterisk_version);
		if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 6))
			{
			@list_channels = $t->cmd(String => "Action: MailboxCount\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Pong.*/');
	
			$j=0;
			foreach(@list_channels)
				{
				if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
					{
					$NEW_messages[$i] = "$list_channels[$j+1]";
					$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
					$OLD_messages[$i] = "$list_channels[$j+2]";
					$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
					}
				$j++;
				}
			}
		elsif (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 13))
			{
			@list_channels = $t->cmd(String => "Action: MailboxCount\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/');
	
			$j=0;
			foreach(@list_channels)
				{
				if($DBX){print "$j - $list_channels[$j]";}
				if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
					{
					$URG_messages[$i] = "$list_channels[$j+1]";
					$URG_messages[$i] =~ s/UrgMessages: |\n//gi;
					$NEW_messages[$i] = "$list_channels[$j+2]";
					$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
					$OLD_messages[$i] = "$list_channels[$j+3]";
					$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
					}
				$j++;
				}
			}
		else
			{
			# get the current time
			( $now_sec, $now_micro_sec ) = gettimeofday();

			# figure out how many micro seconds since epoch
			$now_micro_epoch = $now_sec * 1000000;
			$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

			$begin_micro_epoch = $now_micro_epoch;

			# create a new action id
			$action_id = "$now_sec.$now_micro_sec";

			@list_channels = $t->cmd(String => "Action: MailboxCount\nActionID: $action_id\nMailbox: $PTvoicemail_ids[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/');

			$j=0;
			foreach(@list_channels)
				{
				if($DB){print "$j - $list_channels[$j]";}
				if ($list_channels[$j] =~ /Mailbox: $PTvoicemail_ids[$i]/)
					{
					$URG_messages[$i] = "$list_channels[$j+1]";
					$URG_messages[$i] =~ s/UrgMessages: |\n//gi;
					$NEW_messages[$i] = "$list_channels[$j+2]";
					$NEW_messages[$i] =~ s/NewMessages: |\n//gi;
					$OLD_messages[$i] = "$list_channels[$j+3]";
					$OLD_messages[$i] =~ s/OldMessages: |\n//gi;
					}
				$j++;
				}
			}


		if($DB){print "MailboxCount- $PTvoicemail_ids[$i]    NEW:|$NEW_messages[$i]|  OLD:|$OLD_messages[$i]|    ";}
		if ( ($NEW_messages[$i] eq $PTmessages[$i]) && ($OLD_messages[$i] eq $PTold_messages[$i]) )
			{
			if($DB){print "MESSAGE COUNT UNCHANGED, DOING NOTHING FOR THIS MAILBOX: |$PTvoicemail_ids[$i]|\n";}
			}
		else
			{
			$stmtA = "UPDATE vicidial_voicemail set messages='$NEW_messages[$i]',old_messages='$OLD_messages[$i]' where voicemail_id='$PTvoicemail_ids[$i]';";
				if($DB){print STDERR "\n|$stmtA|\n";}
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
			$voicemail_updates = ($voicemail_updates + $affected_rows);
			}

		$i++;
		$voicemail_boxes++;
		### sleep for 5 hundredths of a second
		usleep(1*50*1000);
		}

	}
##### END check vicidial_voicemail entries #####



$t->buffer_empty;
@hangup = $t->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/"); 
$t->buffer_empty;
$t->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
$ok = $t->close;


$dbhA->disconnect();


if($DB)
	{
	$secY = time();
	$runtime = ($secY - $secX);
	print "Summary:\n";
	print "Voicemail Boxes checked:    $voicemail_boxes\n";
	print "Voicemail Boxes updated:    $voicemail_updates\n";
	print "Run time:                   $runtime seconds \n";
	print "\n";
	print "DONE... Exiting... Goodbye... See you later... \n";
	}

exit;


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
