#!/usr/bin/perl
#
# nanpa_type_preload.pl version 2.10
#
# DESCRIPTION:
# This script is designed to filter the leads in a list by phone number and 
# put the lead into a different list if it is a cellphone or invalid
#
# It is recommended that you run this program on the local Asterisk machine
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130914-1710 - first build
# 131003-1743 - Added exclude filter settings
# 140624-1551 - Added more options to segment leads
# 140702-2242 - Added prefix phone type of V as landline(S)
#

$PATHconf =			'/etc/astguiclient.conf';
$PATHconfPREFIX =	'/etc/vicidial_prefix.conf';

$|=1;

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

open(viciprefix, "$PATHconfPREFIX") || die "can't open $PATHconfPREFIX: $!\n";
@viciprefix = <viciprefix>;
close(viciprefix);
$i=0;
foreach(@viciprefix)
	{
	$line = $viciprefix[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PREFIX_DB_server/) && ($CLIDB_server < 1) )
		{$PREFIX_DB_server = $line;   $PREFIX_DB_server =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_database/) && ($CLIDB_database < 1) )
		{$PREFIX_DB_database = $line;   $PREFIX_DB_database =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_user/) && ($CLIDB_user < 1) )
		{$PREFIX_DB_user = $line;   $PREFIX_DB_user =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_pass/) && ($CLIDB_pass < 1) )
		{$PREFIX_DB_pass = $line;   $PREFIX_DB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_port/) && ($CLIDB_port < 1) )
		{$PREFIX_DB_port = $line;   $PREFIX_DB_port =~ s/.*=//gi;}
	$i++;
	}
#;;;;;;;;;;;; 


$secX = time();
$MT[0]='';

$list_id='';
$cellphone_list_id='';
$landline_list_id='';
$invalid_list_id='';
$invalid=0;
$cellphone=0;
$landline=0;

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
		print "  [--quiet] = supress all normal output\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--user=XXX] = system user for lookups\n";
		print "  [--pass=XXX] = system password for lookups\n";
		print "  [--list-id=XXX] = list(s) that you want to filter, double-dash-delimited if more than one: 111--222\n";
		print "                    double-dash-delimited if more than one: 111--222\n";
		print "                    or you can use ---ALL--- for all lists in the system\n";
		print "  [--cellphone-list-id=XXX] = list that you want to move cellphones into\n";
		print "  [--landline-list-id=XXX] = OPTIONAL, list that you want to move landline phones into\n";
		print "  [--invalid-list-id=XXX] = OPTIONAL, list that you want to move invalid prefix phone numbers into\n";
		print "  [--vl-field-update=XXX] = OPTIONAL, field that you want to update with the phone type information\n";
		print "  [--exclude-field=XXX] = OPTIONAL, field that you want to check in vicidial_list to exclude from processing\n";
		print "  [--exclude-value=XXX] = OPTIONAL, value of the above field that you want to check in vicidial_list to exclude from processing\n";
		print "  [--batch-flag-vl-field] = OPTIONAL, before processing, append F to all vl-field values of S, C, I\n";
		print "  [--batch-quantity=XXX] = OPTIONAL, process only XXX number of vicidial_list records, must run with --batch-flag-vl-field first time\n";
		print "                           This option will only process records that have blank, NULL, SF, CF or IF as vl-field values\n";
		print "\n";
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
			$DB=1;
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			}
		if ($args =~ /--output-to-db/i)
			{
			$output_to_db=1;
			}
		if ($args =~ /--db-output-code=/i)
			{
			@data_in = split(/--db-output-code=/,$args);
			$db_output_code = $data_in[1];
			$db_output_code =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- DB OUTPUT CODE: $db_output_code -----\n\n";}
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			}
		if ($args =~ /--user=/i)
			{
			@data_in = split(/--user=/,$args);
			$user = $data_in[1];
			$user =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- USER: $user -----\n\n";}
			}
		if ($args =~ /--pass=/i)
			{
			@data_in = split(/--pass=/,$args);
			$pass = $data_in[1];
			$pass =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- PASS: XXXX -----\n\n";}
			}
		if ($args =~ /--list-id=/i)
			{
			@data_in = split(/--list-id=/,$args);
			$list_id = $data_in[1];
			$list_id =~ s/ .*//gi;
			if ($list_id =~ /---ALL---/) 
				{
				$list_idSQL = '';
				}
			else
				{
				if ($list_id =~ /--/) 
					{
					$list_idTEMP = $list_id;
					$list_idTEMP =~ s/--/','/gi;
					$list_idSQL = "where list_id IN('$list_idTEMP')";
					}
				else
					{
					$list_idSQL = "where list_id='$list_id'";
					}
				}
			if ($DB > 0) {print "\n----- LIST ID: $list_id ($list_idSQL) -----\n\n";}
			}
		if ($args =~ /--cellphone-list-id=/i)
			{
			@data_in = split(/--cellphone-list-id=/,$args);
			$cellphone_list_id = $data_in[1];
			$cellphone_list_id =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- CELLPHONE LIST ID: $cellphone_list_id -----\n\n";}
			}
		if ($args =~ /--landline-list-id=/i)
			{
			@data_in = split(/--landline-list-id=/,$args);
			$landline_list_id = $data_in[1];
			$landline_list_id =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- LANDLINE LIST ID: $landline_list_id -----\n\n";}
			}
		if ($args =~ /--invalid-list-id=/i)
			{
			@data_in = split(/--invalid-list-id=/,$args);
			$invalid_list_id = $data_in[1];
			$invalid_list_id =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- INVALID LIST ID: $invalid_list_id -----\n\n";}
			}
		if ($args =~ /--vl-field-update=/i)
			{
			@data_in = split(/--vl-field-update=/,$args);
			$vl_field_update = $data_in[1];
			$vl_field_update =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- VL FIELD UPDATE: $vl_field_update -----\n\n";}
			}
		if ($args =~ /--exclude-field=/i)
			{
			@data_in = split(/--exclude-field=/,$args);
			$exclude_field = $data_in[1];
			$exclude_field =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- EXCLUDE FIELD: $exclude_field -----\n\n";}
			}
		if ($args =~ /--exclude-value=/i)
			{
			@data_in = split(/--exclude-value=/,$args);
			$exclude_value = $data_in[1];
			$exclude_value =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- EXCLUDE VALUE: $exclude_value -----\n\n";}
			}
		if ( ($args =~ /--batch-quantity=/i) && (length($vl_field_update)>2) )
			{
			@data_in = split(/--batch-quantity=/,$args);
			$batch_quantity = $data_in[1];
			$batch_quantity =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- BATCH QUANTITY VALUE: $batch_quantity -----\n\n";}
			}
		if ( ($args =~ /--batch-flag-vl-field/i) && (length($vl_field_update)>2) )
			{
			$batch_flag_vl_field=1;
			if ($DB > 0) {print "\n----- BATCH VL FIELD FLAG VALUE: $batch_flag_vl_field -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	exit;
	}
# 107104
if (length($list_id)<2)
	{
	print "list-id option must be set for this script to run: |$list_id|\n";
	exit;
	}
### end parsing run-time options ###

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;
$dbh = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	or die "Couldn't connect to database: " . DBI->errstr;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

### connect to phone type prefix DB
$dbhN = DBI->connect("DBI:mysql:$PREFIX_DB_database:$PREFIX_DB_server:$PREFIX_DB_port", "$PREFIX_DB_user", "$PREFIX_DB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

if (!%six_digit_wireless_hash) {&CompilePrefixHashes;}

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

@lead_ids=@MT;
@phones=@MT;

$excludeSQL='';
if ( (length($exclude_field) > 1) && (length($exclude_value) > 0) ) 
	{
	if (length($list_idSQL) > 5)
		{
		$excludeSQL = "and $exclude_field!='$exclude_value'";
		}
	else
		{
		$excludeSQL = "where $exclude_field!='$exclude_value'";
		}
	}

$limitSQL='';
if ( (length($batch_quantity) > 1) && (length($vl_field_update) > 2) ) 
	{
	if ( (length($list_idSQL) > 5) || (length($excludeSQL) > 5) )
		{
		$limitSQL = "and ( ($vl_field_update is NULL) or ($vl_field_update IN('','SF','CF','IF')) ) limit $batch_quantity";
		}
	else
		{
		$limitSQL = "where ( ($vl_field_update is NULL) or ($vl_field_update IN('','SF','CF','IF')) ) limit $batch_quantity";
		}
	}

if ($batch_flag_vl_field > 0)
	{
	if ($DB) {print "FLAGGING PREVIOUSLY TAGGED LEADS...\n";}

	$clausesSQL = "$list_idSQL $excludeSQL";
	$clausesSQL =~ s/^where/and/gi;

	$stmtA = "UPDATE vicidial_list SET $vl_field_update='SF' where $vl_field_update='S' $clausesSQL;";
	if($DBX){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA);
	if($DB){print "|$affected_rows records changed from S to SF|\n";}

	$stmtA = "UPDATE vicidial_list SET $vl_field_update='CF' where $vl_field_update='C' $clausesSQL;";
	if($DBX){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA);
	if($DB){print "|$affected_rows records changed from C to CF|\n";}

	$stmtA = "UPDATE vicidial_list SET $vl_field_update='IF' where $vl_field_update='I' $clausesSQL;";
	if($DBX){print STDERR "\n|$stmtA|\n";}
	$affected_rows = $dbhA->do($stmtA);
	if($DB){print "|$affected_rows records changed from I to IF|\n";}
	}

$stmtA = "SELECT lead_id,phone_number from vicidial_list $list_idSQL $excludeSQL $limitSQL;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($DB) {print "LEADS FOUND IN LIST $list_id: |$sthArows|\n";}
if ($DBX) {print "|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$lead_ids[$rec_count] =		$aryA[0];
	$phones[$rec_count] =		$aryA[1];
	$rec_count++;

	if ($DB > 0)
		{
		if ($rec_count =~ /100$/i) {print STDERR "  *     $rec_count\r";}
		if ($rec_count =~ /200$/i) {print STDERR "   *    $rec_count\r";}
		if ($rec_count =~ /300$/i) {print STDERR "    *   $rec_count\r";}
		if ($rec_count =~ /400$/i) {print STDERR "     *  $rec_count\r";}
		if ($rec_count =~ /500$/i) {print STDERR "      * $rec_count\r";}
		if ($rec_count =~ /600$/i) {print STDERR "     *  $rec_count\r";}
		if ($rec_count =~ /700$/i) {print STDERR "    *   $rec_count\r";}
		if ($rec_count =~ /800$/i) {print STDERR "   *    $rec_count\r";}
		if ($rec_count =~ /900$/i) {print STDERR "  *     $rec_count\r";}
		if ($rec_count =~ /000$/i) {print STDERR " *      $rec_count\r";}
		}
	}

if ($DB) {print "STARTING LOOKUP OF NANPA PHONE TYPE, ($rec_count records)\n";}
$end_time=time;
if (!$Q) {print "Pre-scrub Elapsed time: ".($end_time-$start_time)." sec\n\n\n";}

$rec_count=0;
$batch_count=0;
$batch_phones='';
# open(STORAGE, "> test_nanpa.txt");
%type_hash=();
while ($sthArows > $rec_count)
	{
	$rec_next = ($rec_count + 1);
	if ( ($batch_count < 1000) && ($rec_next < $sthArows) )
		{
#		$batch_phones .= $phones[$rec_count] . '\|';
		$batch_count++;
		}
	else
		{
#		$batch_phones .= $phones[$rec_count];
		$secTEMP = time();
		$batch_count=0;
		}
		
	# NANPA prefix check
	$type='I';
	if ( (length($phones[$rec_count])>9) && (length($phones[$rec_count])<11) ) 
		{
		$pre4=substr($phones[$rec_count],0,4);
		$pre5=substr($phones[$rec_count],0,5);
		$pre6=substr($phones[$rec_count],0,6);
		$pre7=substr($phones[$rec_count],0,7);
		if ($four_digit_prefix_hash{$pre4}) 
			{
			if ( ($four_digit_prefix_hash{$pre4} ne "S") && ($four_digit_prefix_hash{$pre4} ne "V") )
				{
				$type="C";
				Wireless_to_Wired($phones[$rec_count]);
				} 
			else 
				{
				$type="S";
				Wired_to_Wireless($phones[$rec_count]);
				}
			} 
		elsif ($five_digit_prefix_hash{$pre5}) 
			{
			if ( ($five_digit_prefix_hash{$pre5} ne "S") && ($five_digit_prefix_hash{$pre5} ne "V") )
				{
				$type="C";
				Wireless_to_Wired($phones[$rec_count]);
				} 
			else 
				{
				$type="S";
				Wired_to_Wireless($phones[$rec_count]);
				}
			} 
		elsif ($six_digit_prefix_hash{$pre6}) 
			{
			if ( ($six_digit_prefix_hash{$pre6} ne "S") && ($six_digit_prefix_hash{$pre6} ne "V") )
				{
				$type="C";
				Wireless_to_Wired($phones[$rec_count]);
				} 
			else 
				{
				$type="S";
				Wired_to_Wireless($phones[$rec_count]);
				}
			} 
		elsif ($seven_digit_prefix_hash{$pre7}) 
			{
			if ( ($seven_digit_prefix_hash{$pre7} ne "S") && ($seven_digit_prefix_hash{$pre7} ne "V") )
				{
				$type="C";
				Wireless_to_Wired($phones[$rec_count]);
				} 
			else 
				{
				$type="S";
				Wired_to_Wireless($phones[$rec_count]);
				}
			}
		}
	# print STORAGE "$phones[$rec_count]|$type\n";

	#### CHECK IF 1,000 TYPES have been compiled #####
	$type_hash{$type}.=$lead_ids[$rec_count].",";
	@lead_count=($type_hash{$type}=~/,/g);
	if (@lead_count==1000) 
		{
		$stmtA="";
		$type_hash{$type}=~s/,$//;
		if ( ($type =~ /I/) && (length($invalid_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$invalid_list_id where lead_id in ($type_hash{$type});";}
		if ( ($type =~ /C/) && (length($cellphone_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$cellphone_list_id where lead_id in ($type_hash{$type});";}
		if ( ($type =~ /S|V/) && (length($landline_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$landline_list_id where lead_id in ($type_hash{$type});";}
		if (length($stmtA)>0) 
			{
			if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
			$affected_rows = $dbhA->do($stmtA);
			# print STORAGE $stmtA."\n";
			if($DBX){print STDERR "|$affected_rows records changed|\n";}
			}

		if ( (length($vl_field_update)>2) && ($TEST < 1) )
			{
			$stmtA = "UPDATE vicidial_list SET $vl_field_update='$type' where lead_id in ($type_hash{$type});";
			if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
			$affected_rows = $dbhA->do($stmtA);
			# print STORAGE $stmtA."\n";
			if($DBX){print STDERR "|$affected_rows records changed|\n";}
			}
		$type_hash{$type}="";
		if ($DB) {print STDERR "$rec_count / $sthArows\r";}
		}
	##################################################

	if ($type =~ /S|V/) {$landline++;}
	if ($type =~ /C/) {$cellphone++;}
	if ($type =~ /I/) {$invalid++;}
	$rec_count++;
	if ( ($rec_count =~ /00000$/i) && ($DB) ) {print STDERR "$rec_count / $sthArows (S:$landline | C:$cellphone | I:$invalid)\n";}
	}

#### Update remaining leads to the appropriate type #####
foreach $type (keys %type_hash) 
	{
	@lead_count=($type_hash{$type}=~/,/g);

	$stmtA="";
	if (@lead_count>0) 
		{
		$type_hash{$type}=~s/,$//;
		if ( ($type =~ /I/) && (length($invalid_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$invalid_list_id where lead_id in ($type_hash{$type});";}
		if ( ($type =~ /C/) && (length($cellphone_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$cellphone_list_id where lead_id in ($type_hash{$type});";}
		if ( ($type =~ /S|V/) && (length($landline_list_id) > 1) )
			{$stmtA = "UPDATE vicidial_list SET list_id=$landline_list_id where lead_id in ($type_hash{$type});";}
		if (length($stmtA)>0) 
			{
			if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
			$affected_rows = $dbhA->do($stmtA);
			# print STORAGE $stmtA."\n";
			if($DBX){print STDERR "|$affected_rows records changed|\n";}
			}

		if ( (length($vl_field_update)>2) && ($TEST < 1) )
			{
			$stmtA = "UPDATE vicidial_list SET $vl_field_update='$type' where lead_id in ($type_hash{$type});";
			if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
			$affected_rows = $dbhA->do($stmtA);
			# print STORAGE $stmtA."\n";
			if($DBX){print STDERR "|$affected_rows records changed|\n";}
			}
		$type_hash{$type}="";
		if ($DB) {print STDERR "$sthArows / $sthArows\r";}
		}
	}
#########################################################

$end_time=time;

$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='SERVERS', event_type='OTHER', record_id='$server_ip', event_code='NANPA preload filter run', event_sql='', event_notes='$rec_count / $sthArows (S:$landline | C:$cellphone | I:$invalid) TOTAL Elapsed time: ".($end_time-$start_time)." sec  |$args|';";
$Iaffected_rows = $dbhA->do($stmtA);
if ($DBX) {print "Admin logged: |$Iaffected_rows|$stmtA|\n";}

if (!$Q) {print STDERR "$rec_count / $sthArows (S:$landline | C:$cellphone | I:$invalid)\n";}
if (!$Q) {print "TOTAL Elapsed time: ".($end_time-$start_time)." sec\n\n";}


### 
sub Wired_to_Wireless {
	$phone=$_[0];
	$wtw6=substr($phone,0,6);
	$wtw7=substr($phone,0,7);
	$wtw8=substr($phone,0,8);
	$wtw9=substr($phone,0,9);

	## May be faster if you do one giant if with "or" for each hash

	if ($six_digit_wireless_hash{$wtw6}) {
		$type="C";
	} elsif ($seven_digit_wireless_hash{$wtw7}) {
		$type="C";
	} elsif ($eight_digit_wireless_hash{$wtw8}) {
		$type="C";
	} elsif ($nine_digit_wireless_hash{$wtw9}) {
		$type="C";
	} elsif ($ten_digit_wireless_hash{$phone}) {
		$type="C";
	}
}

sub Wireless_to_Wired {
	$phone=$_[0];
	$wtw6=substr($phone,0,6);
	$wtw7=substr($phone,0,7);
	$wtw8=substr($phone,0,8);
	$wtw9=substr($phone,0,9);

	## May be faster if you do one giant if with "or" for each hash

	if ($six_digit_wired_hash{$wtw6}) {
		$type="S";
	} elsif ($seven_digit_wired_hash{$wtw7}) {
		$type="S";
	} elsif ($eight_digit_wired_hash{$wtw8}) {
		$type="S";
	} elsif ($nine_digit_wired_hash{$wtw9}) {
		$type="S";
	} elsif ($ten_digit_wired_hash{$phone}) {
		$type="S";
	}
}

### Makes a group of hashes to do a quicker check than querying a database
sub CompilePrefixHashes {
	$start_time=time;

	$exc_clause="";
	$exc_clause_prefix="";
	$ct_stmt="select count(*), count(distinct ac_prefix) from nanpa_prefix_exchanges_fast;";
	$ct_rslt=$dbhN->prepare($ct_stmt);
	$ct_rslt->execute();
	@ct_row=$ct_rslt->fetchrow_array;
	if ($ct_row[0]==$ct_row[1]) {
		$stmt="select substr(ac_prefix,1,4) as prefix, type, count(*) as ct from nanpa_prefix_exchanges_fast group by prefix, type order by ct desc";
		$rslt=$dbhN->prepare($stmt);
		$rslt->execute();
		%four_digit_prefix_hash=();
		$current_time=time;
		$exc_clause4="substr(ac_prefix,1,4) not in (";
		if ($DB==1) {print "4-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
		while (@row=$rslt->fetchrow_array) {
			if ($row[2]==1000) {
				$exc_clause4.="$row[0],";
				$four_digit_prefix_hash{$row[0]}=$row[1];
			} else {
				last;
			}
		}
		$exc_clause4=~s/,$//gi;
		$exc_clause4.=")";
		if (length($exc_clause4)<35) {
			$exc_clause4="";
		} else {
			$exc_clause_prefix=" where ";
			$exc_clause=$exc_clause4;
		}
		if ($DB==1) {print "Prefixes: ".scalar(keys %four_digit_prefix_hash)."\n"; }

		$stmt="select substr(ac_prefix,1,5) as prefix, type, count(*) as ct from nanpa_prefix_exchanges_fast ".$exc_clause_prefix.$exc_clause." group by prefix, type order by ct desc";
		$rslt=$dbhN->prepare($stmt);
		$rslt->execute();
		%five_digit_prefix_hash=();
		$current_time=time;
		$exc_clause5="substr(ac_prefix,1,5) not in (";
		if ($DB==1) {print "5-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
		while (@row=$rslt->fetchrow_array) {
			$ch4=substr($row[0],0,4);
			if ($row[2]==100 && !$four_digit_prefix_hash{$ch4}) {
				$exc_clause5.="$row[0],";
				$five_digit_prefix_hash{$row[0]}=$row[1];
			} else {
				last;
			}
		}
		$exc_clause5=~s/,$//gi;
		$exc_clause5.=")";
		if (length($exc_clause5)<35) {
			$exc_clause5="";
		} else {
			$exc_clause_prefix=" where ";
			$exc_clause.=" and $exc_clause5";
		}
		if ($DB==1) {print "Prefixes: ".scalar(keys %five_digit_prefix_hash)."\n";}
		$exc_clause=~s/^\sand\s//gi;

		$stmt="select substr(ac_prefix,1,6) as prefix, type, count(*) as ct from nanpa_prefix_exchanges_fast ".$exc_clause_prefix.$exc_clause." group by prefix, type order by ct desc";
		$rslt=$dbhN->prepare($stmt);
		$rslt->execute();
		%six_digit_prefix_hash=();
		$current_time=time;
		$exc_clause6="substr(ac_prefix,1,6) not in (";
		if ($DB==1) {print "6-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
		while (@row=$rslt->fetchrow_array) {
			$ch4=substr($row[0],0,4);
			$ch5=substr($row[0],0,5);
			if ($row[2]==10 && !$four_digit_prefix_hash{$ch4} && !$five_digit_prefix_hash{$ch5}) {
				$exc_clause6.="$row[0],";
				$six_digit_prefix_hash{$row[0]}=$row[1];
			} else {
				last;
			}
		}
		$exc_clause6=~s/,$//gi;
		$exc_clause6.=")";
		if (length($exc_clause6)<35) {
			$exc_clause6="";
		} else {
			$exc_clause_prefix=" where ";
			$exc_clause.=" and $exc_clause6";
		}
		if ($DB==1) {print "Prefixes: ".scalar(keys %six_digit_prefix_hash)."\n";}
		$exc_clause=~s/^\sand\s//gi;

		$stmt="select ac_prefix, type, count(*) as ct from nanpa_prefix_exchanges_fast ".$exc_clause_prefix.$exc_clause." group by ac_prefix, type order by ct desc";
		$rslt=$dbhN->prepare($stmt);
		$rslt->execute();
		%seven_digit_prefix_hash=();
		$current_time=time;
		if ($DB==1) {print "7-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
		while (@row=$rslt->fetchrow_array) {
			$ch4=substr($row[0],0,4);
			$ch5=substr($row[0],0,5);
			$ch6=substr($row[0],0,6);
			if (!$four_digit_prefix_hash{$ch4} && !$five_digit_prefix_hash{$ch5} && !$six_digit_prefix_hash{$ch6}) {
				$seven_digit_prefix_hash{$row[0]}=$row[1];
			} else {
				last;
			}
		}
		if ($DB==1) {print "Prefixes: ".scalar(keys %seven_digit_prefix_hash)."\n";}
	}


	$stmt="select substr(phone,1,6) as prefix, count(*) as ct from nanpa_wired_to_wireless group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%six_digit_wireless_hash=();
	$current_time=time;
	$exc_clause="";
	$exc_clause_prefix="";
	$exc_clause6="substr(phone,1,6) not in (";
	if ($DB==1) {print "6-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		if ($row[1]==10000) {
			$exc_clause6.="$row[0],";
			$six_digit_wireless_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause6=~s/,$//gi;
	$exc_clause6.=")";
	if (length($exc_clause6)<30) {
		$exc_clause6="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause=$exc_clause6;
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %six_digit_wireless_hash)."\n"; }


	$stmt="select substr(phone,1,7) as prefix, count(*) as ct from nanpa_wired_to_wireless ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%seven_digit_wireless_hash=();
	$current_time=time;
	$exc_clause7="substr(phone,1,7) not in (";
	if ($DB==1) {print "7-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		if ($row[1]==1000 && !$six_digit_wireless_hash{$ch6}) {
			$exc_clause7.="$row[0],";
			$seven_digit_wireless_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause7=~s/,$//gi;
	$exc_clause7.=")";
	if (length($exc_clause7)<30) {
		$exc_clause7="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause7";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %seven_digit_wireless_hash)."\n";}
	$exc_clause=~s/^\sand\s//gi;

	$stmt="select substr(phone,1,8) as prefix, count(*) as ct from nanpa_wired_to_wireless ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%eight_digit_wireless_hash=();
	$current_time=time;
	$exc_clause8="substr(phone,1,8) not in (";
	if ($DB==1) {print "8-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		if ($row[1]==100 && !$six_digit_wireless_hash{$ch6} && !$seven_digit_wireless_hash{$ch7}) {
			$exc_clause8.="$row[0],";
			$eight_digit_wireless_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause8=~s/,$//gi;
	$exc_clause8.=")";
	if (length($exc_clause8)<30) {
		$exc_clause8="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause8";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %eight_digit_wireless_hash)."\n";}
	$exc_clause=~s/^\sand\s//gi;

	$stmt="select substr(phone,1,9) as prefix, count(*) as ct from nanpa_wired_to_wireless ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%nine_digit_wireless_hash=();
	$current_time=time;
	$exc_clause9="substr(phone,1,9) not in (";
	if ($DB==1) {print "9-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		$ch8=substr($row[0],0,8);
		if ($row[1]==10 && !$six_digit_wireless_hash{$ch6} && !$seven_digit_wireless_hash{$ch7} && !$eight_digit_wireless_hash{$ch8}) {
			$exc_clause9.="$row[0],";
			$nine_digit_wireless_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause9=~s/,$//gi;
	$exc_clause9.=")";
	if (length($exc_clause9)<30) {
		$exc_clause9="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause9";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %nine_digit_wireless_hash)."\n";}

	$stmt="select phone from nanpa_wired_to_wireless ".$exc_clause_prefix.$exc_clause;
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%ten_digit_wireless_hash=();
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		$ch8=substr($row[0],0,8);
		$ch9=substr($row[0],0,9);
		if (!$six_digit_wireless_hash{$ch6} && !$seven_digit_wireless_hash{$ch7} && !$eight_digit_wireless_hash{$ch8} && !$nine_digit_wireless_hash{$ch9}) {
			$ten_digit_wireless_hash{$row[0]}++;
		}
	}
	if ($DB==1) {print "10-digit prefixes: ".scalar(keys %ten_digit_wireless_hash)."\n\n";}

	###########################################

	$stmt="select substr(phone,1,6) as prefix, count(*) as ct from nanpa_wireless_to_wired group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%six_digit_wired_hash=();
	$current_time=time;
	$exc_clause="";
	$exc_clause_prefix="";
	$exc_clause6="substr(phone,1,6) not in (";
	if ($DB==1) {print "6-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		if ($row[1]==10000) {
			$exc_clause6.="$row[0],";
			$six_digit_wired_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause6=~s/,$//gi;
	$exc_clause6.=")";
	if (length($exc_clause6)<30) {
		$exc_clause6="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause=$exc_clause6;
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %six_digit_wired_hash)."\n";}


	$stmt="select substr(phone,1,7) as prefix, count(*) as ct from nanpa_wireless_to_wired ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%seven_digit_wired_hash=();
	$current_time=time;
	$exc_clause7="substr(phone,1,7) not in (";
	if ($DB==1) {print "7-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		if ($row[1]==1000 && !$six_digit_wired_hash{$ch6}) {
			$exc_clause7.="$row[0],";
			$seven_digit_wired_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause7=~s/,$//gi;
	$exc_clause7.=")";
	if (length($exc_clause7)<30) {
		$exc_clause7="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause7";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %seven_digit_wired_hash)."\n";}
	$exc_clause=~s/^\sand\s//gi;

	$stmt="select substr(phone,1,8) as prefix, count(*) as ct from nanpa_wireless_to_wired ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%eight_digit_wired_hash=();
	$current_time=time;
	$exc_clause8="substr(phone,1,8) not in (";
	if ($DB==1) {print "8-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		if ($row[1]==100 && !$six_digit_wired_hash{$ch6} && !$seven_digit_wired_hash{$ch7}) {
			$exc_clause8.="$row[0],";
			$eight_digit_wired_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause8=~s/,$//gi;
	$exc_clause8.=")";
	if (length($exc_clause8)<30) {
		$exc_clause8="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause8";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %eight_digit_wired_hash)."\n";}
	$exc_clause=~s/^\sand\s//gi;

	$stmt="select substr(phone,1,9) as prefix, count(*) as ct from nanpa_wireless_to_wired ".$exc_clause_prefix.$exc_clause." group by prefix order by ct desc";
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%nine_digit_wired_hash=();
	$current_time=time;
	$exc_clause9="substr(phone,1,9) not in (";
	if ($DB==1) {print "9-digit check complete.  Elapsed time: ".($current_time-$start_time)." sec\n";}
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		$ch8=substr($row[0],0,8);
		if ($row[1]==10 && !$six_digit_wired_hash{$ch6} && !$seven_digit_wired_hash{$ch7} && !$eight_digit_wired_hash{$ch8}) {
			$exc_clause9.="$row[0],";
			$nine_digit_wired_hash{$row[0]}++;
		} else {
			last;
		}
	}
	$exc_clause9=~s/,$//gi;
	$exc_clause9.=")";
	if (length($exc_clause9)<30) {
		$exc_clause9="";
	} else {
		$exc_clause_prefix=" where ";
		$exc_clause.=" and $exc_clause9";
	}
	if ($DB==1) {print "Prefixes: ".scalar(keys %nine_digit_wired_hash)."\n";}

	$stmt="select phone from nanpa_wireless_to_wired ".$exc_clause_prefix.$exc_clause;
	$rslt=$dbhN->prepare($stmt);
	$rslt->execute();
	%ten_digit_wired_hash=();
	while (@row=$rslt->fetchrow_array) {
		$ch6=substr($row[0],0,6);
		$ch7=substr($row[0],0,7);
		$ch8=substr($row[0],0,8);
		$ch9=substr($row[0],0,9);
		if (!$six_digit_wired_hash{$ch6} && !$seven_digit_wired_hash{$ch7} && !$eight_digit_wired_hash{$ch8} && !$nine_digit_wired_hash{$ch9}) {
			$ten_digit_wired_hash{$row[0]}++;
		}
	}
	if ($DB==1) {print "10-digit prefixes: ".scalar(keys %ten_digit_wired_hash)."\n";}


	$end_time=time;
	if (!$Q) {print "Elapsed time: ".($end_time-$start_time)." sec\n\n\n";}
}
