#!/usr/bin/perl
#
# ADMIN_bcrypt_convert.pl    version 2.10
# 
# Bcrypt password hashing conversion script to be used for authentication
#
# This script is to be run once to convert the plaintext passwords into bcrypt
# password hashes.
#
# IMPORTANT !!!!!!!!!!!!!
# The Crypt::Eksblowfish::Bcrypt perl module is REQUIRED for this script
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGES
# 
# 130704-2041 - First build
# 141231-0959 - Added --conffile option
#

$T=0;
$update_override=0;
$clear_plaintext_pass=0;
$DB=1;
$DBX=0;

use DBI;
use Crypt::Eksblowfish::Bcrypt qw(en_base64);

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
		print "Bcrypt password hashing conversion script to be used for authentication\n";
		print "IMPORTANT!!! RUN FIRST WITH THE --test FLAG TO MAKE SURE IT WORKS!!!\n";
		print "\n";
		print "allowed run time options:\n";
		print "  [--conffile=/path/from/root] = define configuration file path from root at runtime\n";
		print "  [--salt=XXX] = overide the system salt\n";
		print "  [--cost=XX] = overide the system cost\n";
		print "  [--test] = testing mode only, no database updates\n";
		print "  [--update-override] = override existing system settings\n";
		print "  [--clear-plaintext-pass] = set plaintext passwords to blank while updating users\n";
		print "  [--debug] = enable debugging output\n";
		print "  [--debugX] = enable extra debugging output\n";
		print "  [--help] = this help screen\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			if ($DB > 0) {print "\n----- DEBUGGING OUTPUT ENABLED: $DB -----\n";}
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			if ($DB > 0) {print "\n----- EXTRA DEBUGGING OUTPUT ENABLED: $DBX -----\n";}
			}
		if ($args =~ /--test/i)
			{
			$T=1;
			open(testfile, ">/etc/vicidial_bcrypt.test") || die "can't open /etc/vicidial_bcrypt.test: $!\n";
			print testfile "TEST RUN";
			close(testfile);

			if ($DB > 0) {print "\n----- TESTING MODE ENABLED: $T -----\n";}
			}
		if ($args =~ /--update-override/i)
			{
			$update_override=1;
			if ($DB > 0) {print "\n----- UPDATE OVERRIDE ENABLED: $update_override -----\n";}
			}
		if ($args =~ /--clear-plaintext-pass/i)
			{
			$clear_plaintext_pass=1;
			if ($DB > 0) {print "\n----- CLEAR PLAINTEXT PASSWORDS: $clear_plaintext_pass -----\n";}
			}
		if ($args =~ /--salt=/i)
			{
			@data_in = split(/--salt=/,$args);
			$CLIsalt = $data_in[1];
			$CLIsalt =~ s/ .*//gi;
			if (length($CLIsalt) eq 16)
				{
				$newCLIsalt = en_base64($CLIsalt);
				if ($DB > 0) 
					{print "\n----- ENCRYPTING SALT OVERRIDE: $CLIsalt -----\n";}
				$CLIsalt = $newCLIsalt;
				}
			if (length($CLIsalt) ne 22) 
				{
				if ($DB > 0) 
					{print "\n----- INVALID SALT OVERRIDE, USING DEFAULT: $CLIsalt -----\n\n";}
				$CLIsalt = '';
				}
			else
				{
				if ($DB > 0) 
					{print "\n----- SALT OVERRIDE: $CLIsalt -----\n\n";}
				}
			}
		if ($args =~ /--cost=/i)
			{
			@data_in = split(/--cost=/,$args);
			$CLIcost = $data_in[1];
			$CLIcost =~ s/ .*//gi;
			if ($DB > 0) 
				{print "\n----- COST OVERRIDE: $CLIcost -----\n\n";}
			}
		if ($args =~ /--conffile=/i) # CLI defined conffile path
			{
			@CLIconffileARY = split(/--conffile=/,$args);
			@CLIconffileARX = split(/ /,$CLIconffileARY[1]);
			if (length($CLIconffileARX[0])>2)
				{
				$PATHconf = $CLIconffileARX[0];
				$PATHconf =~ s/\/$| |\r|\n|\t//gi;
				$CLIconffile=1;
				if (!$Q) {print "  CLI defined conffile path:  $PATHconf\n";}
				}
			}
		}
	}
else
	{
	print "NO INPUT, NOTHING TO DO, EXITING...\n";
	exit;
	}
### end parsing run-time options ###

if ( ($T < 1) && (!-e "/etc/vicidial_bcrypt.test") )
	{
	print "YOU MUST RUN THIS IN TEST MODE FIRST, EXITING...\n";
	exit;
	}

# default path to astguiclient configuration file:
if (length($PATHconf) < 5)
	{$PATHconf =		'/etc/astguiclient.conf';}

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

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

##### Get the settings from system_settings #####
$stmtA = "SELECT pass_hash_enabled,pass_key,pass_cost FROM system_settings;";
#	print "$stmtA\n";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$pass_hash_enabled =	$aryA[0];
	$pass_key =				$aryA[1];
	$pass_cost =			$aryA[2];
	if (length($pass_key) eq 16)
		{$newpass_key = en_base64($pass_key);}
	}
$sthA->finish();
if ($DBX > 0) {print "SYSTEM SETTINGS:     |$pass_hash_enabled|$pass_key|$newpass_key|$pass_cost|\n";}

if ( (length($pass_key) > 15) && ($pass_hash_enabled > 0) && ($update_override < 1) )
	{
	print "System already set for encryption. If you still want to run this script you must use the update override flag.\n";
	exit;
	}

if (length($CLIsalt) > 0)
	{
	if ($DBX > 0) {print "SALT OVERRIDDEN:     |$pass_key|$newpass_key|$CLIsalt|\n";}
	$salt = $CLIsalt;
	}
else
	{$salt = $newpass_key;}

if (length($CLIcost) > 0)
	{
	if ($DBX > 0) {print "COST OVERRIDDEN:     |$pass_cost|$CLIcost|\n";}
	$cost = $CLIcost;
	}
else
	{$cost = $pass_cost;}
while (length($cost) < 2) 
	{$cost = "0$cost";}

$stmtA = "SELECT user,pass,full_name from vicidial_users;";
if($DBX){print STDERR "\n|$stmtA|\n";}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$i=0;
while ($sthArows > $i)
	{
	@aryA = $sthA->fetchrow_array;
	$user[$i] =			$aryA[0];
	$pass[$i] =			$aryA[1];
	$full_name[$i] =	$aryA[2];
	$only_pass_hash[$i] =	'';
	$i++;
	}
$sthA->finish();

if ($DB > 0) {print "$i USERS FOUND IN YOUR SYSTEM\n";}



# Set the cost to $cost and append a NUL
$settings = '$2a$'.$cost.'$'.$salt;

if ($DB > 0) {print "STARTING BCRYPT PROCESS\n";}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl timing of less than one second
($START_s_hires, $START_usec) = gettimeofday();

$i=0;
while ($sthArows > $i)
	{
	# Encrypt it
	$pass_hash = Crypt::Eksblowfish::Bcrypt::bcrypt($pass[$i], $settings);
	$pass_hash_length = length($pass_hash);
	$only_pass_hash[$i] = substr($pass_hash,29,31);
	if ($DB > 0) {print "PASS HASH:     |$user[$i]|$pass[$i]|$full_name[$i]|$pass_hash_length|$pass_hash|$only_pass_hash[$i]|\n";}
	$i++;
	}

($END_s_hires, $END_usec) = gettimeofday();
$START_time = $START_s_hires . '.' . sprintf("%06s", $START_usec);
$END_time = $END_s_hires . '.' . sprintf("%06s", $END_usec);
$RUN_time = ($END_time - $START_time);
$RUN_time = sprintf("%.6f", $RUN_time);
if ($DBX > 0) 
	{print "TOTAL bcrypt time: |$RUN_time ($END_time - $START_time)|\n";}



if ($DB > 0) {print "STARTING USER RECORD UPDATES\n";}

$passSQL='';
if ($clear_plaintext_pass > 0)
	{$passSQL=",pass=''";}

$TOTALaffected_rows=0;
$i=0;
while ($sthArows > $i)
	{
	$affected_rows=0;
	$stmtA = "UPDATE vicidial_users set pass_hash='$only_pass_hash[$i]' $passSQL where user='$user[$i]';";
	if ($T < 1)
		{$affected_rows = $dbhA->do($stmtA);} #  or die  "Couldn't execute query:|$stmtA|\n";
	$TOTALaffected_rows = ($TOTALaffected_rows + $affected_rows);

	if ($DB > 0) {print "USER UPDATE:     |$user[$i]|$pass[$i]|$full_name[$i]|$only_pass_hash[$i]|$affected_rows|\n";}
	if ($DBX > 0) {print "SQL:   |$stmtA|\n";}
	$i++;
	}

if ( ($T < 1) && ($TOTALaffected_rows > 0) )
	{
	open(keyfile, ">/etc/vicidial.key") || die "can't open /etc/vicidial.key: $!\n";
	print keyfile "$salt";
	close(keyfile);

	$stmtA = "UPDATE system_settings set pass_hash_enabled='1';";
	$affected_rows = $dbhA->do($stmtA);
	if ($DB > 0) {print "BCRYPT SET TO ENABLED ON YOUR SYSTEM: |$affected_rows|$stmtA|\n";}
	}


if ($DB > 0) {print "BCRYPT PROCESSES COMPLETE: $TOTALaffected_rows USERS UPDATED\n";}

exit;
