#!/usr/bin/perl
#
# nanpa_type_filter.pl version 2.10
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
# 130822-1645 - first build
# 131003-1725 - Added exclude filter settings
# 140501-0709 - Added include filter settings
# 140702-2252 - Added prefix phone type of V as landline
#

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
		print "  [--output-to-db] = writes output to db table\n";
		print "  [--db-output-code=XXX] = code used to tag output to db table\n";
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
		print "  [--include-field=XXX] = OPTIONAL, field that you want to check in vicidial_list to include only for processing\n";
		print "  [--include-value=XXX] = OPTIONAL, value of the above field that you want to check in vicidial_list to include only for processing\n";
		print "                                    you can use EMPTY to stand for an empty string\n";
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
			$PREFIX_user = $data_in[1];
			$PREFIX_user =~ s/ .*//gi;
			$TRIGGER_user = $PREFIX_user;
			if ($DB > 0) {print "\n----- USER: $PREFIX_user -----\n\n";}
			}
		if ($args =~ /--pass=/i)
			{
			@data_in = split(/--pass=/,$args);
			$PREFIX_pass = $data_in[1];
			$PREFIX_pass =~ s/ .*//gi;
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
		if ($args =~ /--include-field=/i)
			{
			@data_in = split(/--include-field=/,$args);
			$include_field = $data_in[1];
			$include_field =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- include FIELD: $include_field -----\n\n";}
			}
		if ($args =~ /--include-value=/i)
			{
			@data_in = split(/--include-value=/,$args);
			$include_value = $data_in[1];
			$include_value =~ s/ .*//gi;
			if ($DB > 0) {print "\n----- include VALUE: $include_value -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	exit;
	}

### end parsing run-time options ###


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
			exit
			}
		}
	}


# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';
$PATHconfPREFIX =	'/etc/vicidial_prefix.conf';

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

open(viciprefix, "$PATHconfPREFIX") || die "can't open $PATHconfPREFIX: $!\n";
@viciprefix = <viciprefix>;
close(viciprefix);
$i=0;
foreach(@viciprefix)
	{
	$line = $viciprefix[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PREFIX_url/) && ($CLI_PREFIX_url < 1) )
		{$PREFIX_url = $line;   $PREFIX_url =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_user/) && (length($CLI_PREFIX_user) < 1) )
		{$PREFIX_user = $line;   $PREFIX_user =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_pass/) && (length($CLI_PREFIX_pass) < 1) )
		{$PREFIX_pass = $line;   $PREFIX_pass =~ s/.*=//gi;}
	$i++;
	}

if ( (length($list_id)<2) || (length($PREFIX_user)<2) || (length($PREFIX_pass)<2) || (length($PREFIX_url)<2) )
	{
	print "user, pass, url and list-id options must be set for this script to run: |$PREFIX_user|XXXX|$list_id|\n";
	exit;
	}

if (length($TRIGGER_user) < 2) {$TRIGGER_user = $PREFIX_user;}

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$filedate = "$year$mon$mday$hour$min$sec";
$VDrandom = int( rand(9999999)) + 10000000;

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

if (length($db_output_code)<2) {$db_output_code = $filedate . "_" . $VDrandom;}


use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

if ($output_to_db)
	{
	$stmtA = "INSERT INTO vicidial_nanpa_filter_log SET output_code='$db_output_code',user='$TRIGGER_user',status='LAUNCHED',server_ip='$VARserver_ip',list_id='$list_id',start_time=NOW(),update_time=NOW(),status_line='Starting...',script_output=\"Starting...\nCELLPHONE LIST ID: $cellphone_list_id\nLANDLINE LIST ID: $landline_list_id\nINVALID LIST ID: $invalid_list_id\nVL FIELD UPDATE: $vl_field_update\nEXCLUDE FIELD: $exclude_field\nEXCLUDE VALUE: $exclude_value\nINCLUDE FIELD: $include_field\nINCLUDE VALUE: $include_value\n\" ON DUPLICATE KEY UPDATE status='LAUNCHED',server_ip='$VARserver_ip',list_id='$list_id',update_time=NOW(),status_line=\"Starting...\n\",script_output=CONCAT(script_output,\"Starting...\nCELLPHONE LIST ID: $cellphone_list_id\nLANDLINE LIST ID: $landline_list_id\nINVALID LIST ID: $invalid_list_id\nVL FIELD UPDATE: $vl_field_update\nEXCLUDE FIELD: $exclude_field\nEXCLUDE VALUE: $exclude_value\nINCLUDE FIELD: $include_field\nINCLUDE VALUE: $include_value\n\");";
	$affected_rows = $dbhA->do($stmtA);
	if($DBX){print STDERR "\n|$affected_rows|$stmtA|\n";}
	}

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

$includeSQL='';
if ( (length($include_field) > 1) && (length($include_value) > 0) ) 
	{
	$include_value =~ s/EMPTY//gi;
	if ( (length($list_idSQL) > 5) || (length($excludeSQL) > 5) )
		{
		$includeSQL = "and $include_field='$include_value'";
		}
	else
		{
		$includeSQL = "where $include_field='$include_value'";
		}
	}

$stmtA = "SELECT lead_id,phone_number from vicidial_list $list_idSQL $excludeSQL $includeSQL;";
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
if ($output_to_db)
	{
	$stmtA = "UPDATE vicidial_nanpa_filter_log SET status='LEADS_GATHERED',leads_count='$rec_count',update_time=NOW(),status_line='Leads Gathered: $rec_count',script_output=CONCAT(script_output,\"Leads Gathered: $rec_count\n\") where output_code='$db_output_code';";
	$affected_rows = $dbhA->do($stmtA);
	if($DBX){print STDERR "\n|$affected_rows|$stmtA|\n";}
	}
$rec_count=0;
$batch_count=0;
$batch_phones='';
# open(STORAGE, "> nft_output.txt");
while ($sthArows > $rec_count)
	{
	$rec_next = ($rec_count + 1);
	if ( ($batch_count < 1000) && ($rec_next < $sthArows) )
		{
		$batch_phones .= $phones[$rec_count] . '\|';
		$batch_count++;
		}
	else
		{
		$batch_phones .= $phones[$rec_count];
		$secTEMP = time();
		# http://localhost/vicidial/nanpa_type.php?user=6666&pass=1234&phone_number=7275551212|8135551212|9545551212|9995551212
		$postdata = "user=$PREFIX_user&pass=$PREFIX_pass&phone_number=$batch_phones";
		$tempurl = $PREFIX_url;
		$tempfilename = "$lead_ids[$rec_count]$phones[$rec_count]$secTEMP" . '.txt';
		$temp_wget = "$wgetbin -q --output-document=/tmp/$tempfilename $tempurl --post-data=\"$postdata\" ";
		if ($DBX > 0) {print STDERR "$temp_wget\n";}
		@wget_output = `$temp_wget `;

		open(tempweboutput, "/tmp/$tempfilename") || die "can't open /tmp/$tempfilename: $!\n";
		@tempweboutput = <tempweboutput>;
		close(tempweboutput);
		`rm -f /tmp/$tempfilename `;

		if ($DBX > 0) {print STDERR "$tempweboutput[0]\n";}

		$b=0;
		foreach(@tempweboutput)
			{
			$temp_rec = (($rec_count - $batch_count) + $b);
			chomp($tempweboutput[$b]);
			@result_split = split('|',$tempweboutput[$b]);
			if ( ($phones[$temp_rec] =~ /$result_split[1]/) && (length($lead_ids[$temp_rec]) > 0) )
				{
				# print STORAGE "$phones[$temp_rec]|$result_split[0]\n";
				$stmtA='';
				if ($result_split[0] =~ /I/) {$invalid++;}
				if ($result_split[0] =~ /C/) {$cellphone++;}
				if ($result_split[0] =~ /S|V/) {$landline++;}
				if ( ($result_split[0] =~ /I/) && (length($invalid_list_id) > 1) )
					{$stmtA = "UPDATE vicidial_list SET list_id=$invalid_list_id where lead_id=$lead_ids[$temp_rec];";}
				if ( ($result_split[0] =~ /C/) && (length($cellphone_list_id) > 1) )
					{$stmtA = "UPDATE vicidial_list SET list_id=$cellphone_list_id where lead_id=$lead_ids[$temp_rec];";}
				if ( ($result_split[0] =~ /S|V/) && (length($landline_list_id) > 1) )
					{$stmtA = "UPDATE vicidial_list SET list_id=$landline_list_id where lead_id=$lead_ids[$temp_rec];";}
				if ( (length($stmtA) > 10) && ($TEST < 1) )
					{
					if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
					$affected_rows = $dbhA->do($stmtA);
					if($DBX){print STDERR "|$affected_rows records changed|\n";}
					}
				if ( (length($vl_field_update)>2) && ($TEST < 1) )
					{
					$stmtA = "UPDATE vicidial_list SET $vl_field_update='$result_split[0]' where lead_id=$lead_ids[$temp_rec];";
					if($DBX){print STDERR "\n|$stmtA|$phones[$temp_rec]|\n";}
					$affected_rows = $dbhA->do($stmtA);
					if($DBX){print STDERR "|$affected_rows records changed|\n";}
					}
				}
	#		else
	#			{if ($DBX > 0) {print "ERROR: |$b|$rec_count|$temp_rec|$batch_count|$phones[$temp_rec]|$lead_ids[$temp_rec]|";} }
			$b++;
			}
		$batch_phones='';
		$batch_count=0;

		if ($output_to_db)
			{
			$stmtA = "UPDATE vicidial_nanpa_filter_log SET status='NANPA_LOOKUPS',update_time=NOW(),filter_count='$rec_count',status_line='(INV: $invalid|CEL: $cellphone|LND: $landline)' where output_code='$db_output_code';";
			$affected_rows = $dbhA->do($stmtA);
			if($DBX){print STDERR "\n|$affected_rows|$stmtA|\n";}
			}

		}

	$rec_count++;

	if ($DB > 0)
		{
		$dance = '      ';
		if ($rec_count =~ /1\d00$/i) {$dance = '#     ';}
		if ($rec_count =~ /2\d00$/i) {$dance = ' #    ';}
		if ($rec_count =~ /3\d00$/i) {$dance = '  #   ';}
		if ($rec_count =~ /4\d00$/i) {$dance = '   #  ';}
		if ($rec_count =~ /5\d00$/i) {$dance = '    # ';}
		if ($rec_count =~ /6\d00$/i) {$dance = '     #';}
		if ($rec_count =~ /7\d00$/i) {$dance = '    # ';}
		if ($rec_count =~ /8\d00$/i) {$dance = '   #  ';}
		if ($rec_count =~ /9\d00$/i) {$dance = '  #   ';}
		if ($rec_count =~ /0\d00$/i) {$dance = ' #    ';}
		if ($rec_count =~ /00$/i) {print STDERR " $dance $rec_count    (INV: $invalid|CEL: $cellphone|LND: $landline)\r";}
		}
	}







### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) {print "CELLPHONE SCRUB FOR LIST $list_id\n";}
if (!$Q) {print "TOTAL LEADS: $rec_count\n";}
if (!$Q) {print "   INVALID:    $invalid\n";}
if (!$Q) {print "   CELLPHONE:  $cellphone\n";}
if (!$Q) {print "   LANDLINE:   $landline\n";}
if (!$Q) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}

if ($output_to_db)
	{
	$stmtA = "UPDATE vicidial_nanpa_filter_log SET status='COMPLETED',filter_count='$rec_count',update_time=NOW(),status_line='Process Completed: $secZ seconds',script_output=CONCAT(script_output,\"Process Completed: $secZ seconds\n   INVALID:    $invalid\n   CELLPHONE:  $cellphone\n   LANDLINE:   $landline\n\n\") where output_code='$db_output_code';";
	$affected_rows = $dbhA->do($stmtA);
	if($DBX){print STDERR "\n|$affected_rows|$stmtA|\n";}
	}

exit;

