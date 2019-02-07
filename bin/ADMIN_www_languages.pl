#!/usr/bin/perl

# ADMIN_www_languages.pl version 2.12
#
# This script is designed to traverse the vicidial and agc web directories and
# generate a list of phrases that are output from the QXZ PHP functions so that 
# they can be inserted/updated in the database.
#
# NOTES: we used the following database query to set to UTF8:
#  ALTER DATABASE asterisk CHARACTER SET utf8 COLLATE utf8_unicode_ci;
#   you should check your table character sets:
#  select TABLE_SCHEMA,TABLE_NAME,TABLE_TYPE,TABLE_COLLATION,CREATE_OPTIONS from information_schema.`TABLES`;
#   and alter them if necessary:
#  ALTER TABLE audio_store_details CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 141128-0024 - First build
# 141211-2123 - Added --wipe-www-phrases-table CLI option and insert_date field
# 151219-0756 - Added --chat-only option for chat_customer and --one-file-only option
#

$secX = time();

# constants
$SYSLOG=1; # log to a logfile
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$US='_';
$MT[0]='';
$QXZlines=0;
$QXZvalues=0;
$QXZvaronly=0;
$QXZlengthonly=0;
$QXZplaceonly=0;

$secT = time();
$now_date_epoch = $secT;
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$file_date = "$year-$mon-$mday";
$now_date = "$year-$mon-$mday $hour:$min:$sec";

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';


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
		print "ADMIN_www_languages.pl - analyze php scripts for QXZ functions and contents\n";
		print "\n";
		print "options:\n";
		print "  [--help] = this help screen\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--agc-only] = only parse the agc directory\n";
		print "  [--chat-only] = only parse the chat_customer directory\n";
		print "  [--vicidial-only] = only parse the vicidial directory\n";
		print "  [--one-file-only=/path/from/root] = process one php file only\n";
		print "  [--QXZlines] = print full lines that include QXZ functions\n";
		print "  [--QXZvalues] = print only QXZ function values\n";
		print "  [--QXZvaronly] = print only QXZ function values with PHP variables in them\n";
		print "  [--QXZlengthonly] = print only QXZ function values that include a length spec\n";
		print "  [--QXZplaceonly] = print only QXZ function values with placeholder variables in them\n";
		print "  [--conffile=/path/from/root] = define astguiclient.conf config file to use\n";
		print "  [--wipe-www-phrases-table] = deletes all records in www_phrases database table\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /--debug/i) # Debug flag
			{$DB=1;}
		if ($args =~ /--debugX/i) # Extra Debug flag
			{$DBX=1;}
		if ($args =~ /--agc-only/i) # agc flag
			{$agc_only=1;}
		if ($args =~ /--chat-only/i) # agc flag
			{$chat_only=1;}
		if ($args =~ /--vicidial-only/i) # vicidial flag
			{$vicidial_only=1;}
		if ($args =~ /--QXZlines/i) # QXZlines flag
			{$QXZlines=1;}
		if ($args =~ /--QXZvalues/i) # QXZvalues flag
			{$QXZvalues=1;}
		if ($args =~ /--QXZvaronly/i) # QXZvaronly flag
			{$QXZvaronly=1;}
		if ($args =~ /--QXZlengthonly/i) # QXZlengthonly flag
			{$QXZlengthonly=1;}
		if ($args =~ /--QXZplaceonly/i) # QXZplaceonly flag
			{$QXZplaceonly=1;}
		if ($args =~ /--wipe-www-phrases-table/i) # wipewwwphrasestable flag
			{$wipewwwphrasestable=1;}
		if ($args =~ /--one-file-only=/i) # CLI defined file path for one file only
			{
			@CLIonefileARY = split(/--one-file-only=/,$args);
			@CLIonefileARX = split(/ /,$CLIonefileARY[1]);
			if (length($CLIonefileARX[0])>2)
				{
				$PATHone = $CLIonefileARX[0];
				$PATHone =~ s/\/$| |\r|\n|\t//gi;
				print "  CLI defined one-file-only path:  $PATHone\n";
				}
			}
		if ($args =~ /--conffile=/i) # CLI defined file path
			{
			@CLIconffileARY = split(/--conffile=/,$args);
			@CLIconffileARX = split(/ /,$CLIconffileARY[1]);
			if (length($CLIconffileARX[0])>2)
				{
				$PATHconf = $CLIconffileARX[0];
				$PATHconf =~ s/\/$| |\r|\n|\t//gi;
				print "  CLI defined conffile path:  $PATHconf\n";
				}
			}
		}
	}
else
	{
	print "no command line options set, exiting\n";
	exit;
	}
### end parsing run-time options ###


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

if (!$VASLOGfile) {$VASLOGfile = "$PATHlogs/wwwlang.$year-$mon-$mday";}
if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### Grab Server values from the database
$stmtA = "SELECT count(*) FROM www_phrases;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$phrase_count =	$aryA[0];
	}
$sthA->finish();

$event_string = "Existing phrases: $phrase_count";
if ($DB > 0) {print "$event_string\n";}
&event_logger;

if ($wipewwwphrasestable > 0) 
	{
	$stmtA="DELETE FROM www_phrases;";
	$affected_rows = $dbhA->do($stmtA);
	$event_string = "     $affected_rows|$stmtA|";
	if ($DBX > 0) {print "$event_string\n";}
	&event_logger;
	}

$i=0;
$f=0;
if (length($PATHone) > 2) 
	{
	$FILEparseDIR[$f] = "$PATHweb/temp/";
	$FILEparseNAME[$f] = '';
	$FILEparse[$f] = '';
	if ( (length($PATHone) > 2) && (!-d "$PATHone") )
		{
		$FILEparse[$f] = "$PATHone";
		@oneARY = split("/", $PATHone);
		$FILEparseNAME[$f] = $oneARY[-1];
		$event_string = "$PATHone   $FILEparseNAME[$f]";
		if ($DBX > 0) {print "$event_string\n";}
		&event_logger;
		$f++;
		}
	$i++;
	}
else
	{
	$i=0;
	if ( ($vicidial_only < 1) && ($chat_only < 1) )
		{
		opendir(FILE, "$PATHweb/agc/");
		@agcFILES = readdir(FILE);
		### Loop through files first to gather list
		foreach(@agcFILES)
			{
			$FILEparseDIR[$f] = "$PATHweb/agc/";
			$FILEparseNAME[$f] = '';
			$FILEparse[$f] = '';
			if ( (length($agcFILES[$i]) > 2) && (!-d "$PATHweb/agc/$agcFILES[$i]") )
				{
				$FILEparse[$f] = "$PATHweb/agc/$agcFILES[$i]";
				$FILEparseNAME[$f] = $agcFILES[$i];
				$event_string = "$PATHweb/agc/$agcFILES[$i]   $FILEparseNAME[$f]";
				if ($DBX > 0) {print "$event_string\n";}
				&event_logger;
				$f++;
				}
			$i++;
			}
		}

	$i=0;
	if ( ($agc_only < 1) && ($chat_only < 1) )
		{
		opendir(FILE, "$PATHweb/vicidial/");
		@vicidialFILES = readdir(FILE);

		### Loop through files first to gather list
		foreach(@vicidialFILES)
			{
			$FILEparseDIR[$f] = "$PATHweb/vicidial/";
			$FILEparseNAME[$f] = '';
			$FILEparse[$f] = '';
			if ( (length($vicidialFILES[$i]) > 2) && (!-d "$PATHweb/vicidial/$vicidialFILES[$i]") )
				{
				$FILEparse[$f] = "$PATHweb/vicidial/$vicidialFILES[$i]";
				$FILEparseNAME[$f] = $vicidialFILES[$i];
				$event_string = "$PATHweb/vicidial/$vicidialFILES[$i]   $FILEparseNAME[$f]";
				if ($DBX > 0) {print "$event_string\n";}
				&event_logger;
				$f++;
				}
			$i++;
			}
		}

	$i=0;
	if ( ($vicidial_only < 1) && ($agc_only < 1) )
		{
		opendir(FILE, "$PATHweb/chat_customer/");
		@chat_customerFILES = readdir(FILE);

		### Loop through files first to gather list
		foreach(@chat_customerFILES)
			{
			$FILEparseDIR[$f] = "$PATHweb/chat_customer/";
			$FILEparseNAME[$f] = '';
			$FILEparse[$f] = '';
			if ( (length($chat_customerFILES[$i]) > 2) && (!-d "$PATHweb/chat_customer/$chat_customerFILES[$i]") )
				{
				$FILEparse[$f] = "$PATHweb/chat_customer/$chat_customerFILES[$i]";
				$FILEparseNAME[$f] = $chat_customerFILES[$i];
				$event_string = "$PATHweb/chat_customer/$chat_customerFILES[$i]   $FILEparseNAME[$f]";
				if ($DBX > 0) {print "$event_string\n";}
				&event_logger;
				$f++;
				}
			$i++;
			}
		}
	}

$event_string = "Files:            $i   $f";
if ($DB > 0) {print "$event_string\n";}
&event_logger;





$QXZlinecount=0;
$QXZcount=0;
$QXZcount_length=0;
$QXZcount_var=0;
$QXZcount_place=0;
$QXZcount_var_ct=0;
$QXZcount_place_ct=0;
$QXZinserts=0;
$QXZdups=0;

$f=0;
foreach(@FILEparse)
	{
	if (-e "$FILEparse[$f]") 
		{
		$event_string = "file found at: $FILEparse[$f]   $FILEparseNAME[$f]\n";
		if ($DB > 0) {print "$event_string\n";}
		&event_logger;

		open(conf, "$FILEparse[$f]") || die "can't open $FILEparse[$f]: $!\n";
		@conf = <conf>;
		close(conf);
		$i=0;
		foreach(@conf)
			{
			$line = $conf[$i];
			$line =~ s/\n|\r|\t//gi;

			if ( ($line =~ /\_QXZ\(/) && ($line !~ /function \_QXZ/) )
				{
				$QXZlinecount++;
				$QXZ_in_line = () = $line =~ /\_QXZ\(/g;
				$QXZcount = ($QXZcount + $QXZ_in_line);
				@eachQXZ = split(/\_QXZ\(/,$line);
				$each_ct=0;
				while ($each_ct <= $#eachQXZ) 
					{
					if ($each_ct > 0) 
						{
						@eachQXZvalue = split(/\)/,$eachQXZ[$each_ct]);
						if ( ($eachQXZvalue[0] =~ /\(/) && (length($eachQXZvalue[1])>0) )
							{
							$temp_QXZval_str = $eachQXZvalue[0].")".$eachQXZvalue[1];
							if ( ($eachQXZvalue[1] =~ /\(/) && (length($eachQXZvalue[2])>0) )
								{
								$temp_QXZval_str .= ")".$eachQXZvalue[2];
								if ( ($eachQXZvalue[2] =~ /\(/) && (length($eachQXZvalue[3])>0) )
									{
									$temp_QXZval_str .= ")".$eachQXZvalue[3];
									}
								}
							}
						else
							{
							$temp_QXZval_str = $eachQXZvalue[0];
							}
						if ( ($QXZvalues > 0) && ($QXZvaronly < 1) && ($QXZlengthonly < 1) && ($QXZplaceonly < 1) )
							{
							$event_string = "$temp_QXZval_str";
							print "$event_string\n";
							&event_logger;
							}
						if ($temp_QXZval_str =~ /,\d|, \d/) 
							{
							$QXZcount_length++;
							if ($QXZlengthonly > 0)
								{
								$event_string = "$temp_QXZval_str";
								print "$event_string\n";
								&event_logger;
								}
							}
						if ($temp_QXZval_str =~ /\$/) 
							{
							$QXZcount_var++;
							$var_ct_in_line = () = $temp_QXZval_str =~ /\$/g;
							$QXZcount_var_ct = ($QXZcount_var_ct + $var_ct_in_line);
							if ($QXZvaronly > 0)
								{
								$event_string = "$temp_QXZval_str";
								print "$event_string\n";
								&event_logger;
								}
							}
						if ($temp_QXZval_str =~ /%\ds/) 
							{
							$QXZcount_place++;
							$place_ct_in_line = () = $temp_QXZval_str =~ /\$/g;
							$QXZcount_place_ct = ($QXZcount_place_ct + $place_ct_in_line);
							if ($QXZplaceonly > 0)
								{
								$event_string = "$temp_QXZval_str";
								print "$event_string\n";
								&event_logger;
								}
							}
						$temp_QXZval_strSQL = $temp_QXZval_str;
						$temp_QXZval_strSQL =~ s/\"$|^\"|^\'|\'$//gi;
						$temp_QXZval_strSQL =~ s/\",.*|\',.*//gi;

						$phrase_match=0;
						$stmtA = "SELECT count(*) FROM www_phrases where phrase_text='$temp_QXZval_strSQL';";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$phrase_match =	$aryA[0];
							}
						$sthA->finish();

						if ($phrase_match < 1)
							{
							if (length($temp_QXZval_strSQL)<1)
								{
								if ($DB > 0) 
									{print "EMPTY INSERT SKIPPED: |$temp_QXZval_strSQL|$FILEparseNAME[$f]|$FILEparseDIR[$f]|\n";}
								}
							else
								{
								$stmtA="INSERT INTO www_phrases SET phrase_text='$temp_QXZval_strSQL',php_filename='$FILEparseNAME[$f]',php_directory='$FILEparseDIR[$f]',source='www_languages_script',insert_date=NOW();";
								$affected_rows = $dbhA->do($stmtA);
								$QXZinserts++;

								$event_string = "     $affected_rows|$stmtA|";
								if ($DBX > 0) {print "$event_string\n";}
								&event_logger;
								}
							}
						else
							{$QXZdups++;}
						}
					$each_ct++;
					}

				if ($QXZlines > 0)
					{
					$event_string = "$i|$QXZlinecount|$QXZ_in_line|$line";
					print "$event_string\n";
					&event_logger;
					}
				}
			$i++;
			}
		}
	else
		{
		$event_string = "File does not exist: $FILEparse[$f]   $FILEparseNAME[$f]";
		if ($DB > 0) {print "$event_string\n";}
		&event_logger;
		}
	$f++;
	}


$event_string = "\n";
$event_string .= "FILE PARSING FINISHED!\n";
$event_string .= "Files parsed: $f\n";
$event_string .= "ConfFile:     $PATHconf\n";
$event_string .= "QXZ line count:          $QXZlinecount\n";
$event_string .= "QXZ count:               $QXZcount\n";
$event_string .= "QXZ with length set:     $QXZcount_length\n";
$event_string .= "QXZ with PHP vars:       $QXZcount_var\n";
$event_string .= "QXZ with var place set:  $QXZcount_place\n";
$event_string .= "QXZ with PHP vars inst:  $QXZcount_var_ct\n";
$event_string .= "QXZ with var place inst: $QXZcount_place_ct\n";
$event_string .= "\n";
$event_string .= "QXZ DB inserts:    $QXZinserts\n";
$event_string .= "QXZ DB dups:       $QXZdups\n";
$event_string .= "\n";
if ($DB > 0) {print "$event_string\n";}
&event_logger;

$secy = time();		$secz = ($secy - $secX);		$minz = ($secz/60);		# calculate script runtime so far
$event_string = "\n     - process runtime      ($secz sec) ($minz minutes)";
if ($DB > 0) {print "$event_string\n";}
&event_logger;


exit;




sub event_logger
	{
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$VASLOGfile")
				|| die "Can't open $VASLOGfile: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}

