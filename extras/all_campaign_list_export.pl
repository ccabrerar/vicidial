#!/usr/bin/perl
#
# all_ccampaign_list_export.pl version 2.10
#
# DESCRIPTION:
# Export all of the leads in all campaigns on a system to separate CSV files in 
# the /tmp/ directory named DATABASE-CAMPAIGN.csv
# This script will not export custom list field contents.
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 150206-1641 - Initial Build <mikec>
# 150220-1221 - Added debug options and more debug output
#

use DBI;

$DB=0;
$DBX=0;
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
		print "allowed run time options(script will run for all campaigns):\n";
		print "  [--debug] = Debug output\n";
		print "  [--debugX] = Extra debug output\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i) # Debug flag
			{
			$DB=1;
			}
		if ($args =~ /--debugX/i) # Debug flag
			{
			$DBX=1;
			}
		}
	}
else
	{
#	print "no command line options set\n";
	}

## end parsing run-time options ###

# get the current time
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$Fhour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}

my $date = "$year-$mon-$mday $hour:$min:$sec";

# default path to astguiclient configuration file:
$PATHconf = '/etc/astguiclient.conf';

# read in the conf file
open(CONFIG, "$PATHconf") || die "can't open $PATHconf: $!\n";
@config = <CONFIG>;
close(CONFIG);
$i=0;
foreach(@config) 
	{
	$line = $config[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ($line =~ /^VARserver_ip/)
		{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ($line =~ /^VARDB_server/)
		{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ($line =~ /^VARDB_database/)
		{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ($line =~ /^VARDB_user/)
		{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_pass/)
		{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_port/)
		{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	or die "Couldn't connect to database: " . DBI->errstr;

# get a list of the campaign ids
$stmt = "SELECT campaign_id FROM vicidial_campaigns;";
$sth = $dbhB->prepare($stmt) or die "preparing: ",$dbhB->errstr;
$sth->execute or die "executing: $stmt ", $dbhb->errstr;
$num_camp = $sth->rows;
$row_count = 0;
$leads=0;

# loop through them
while ($row_count < $num_camp) 
	{
	@row = $sth->fetchrow_array;
	$campaign_id = $row[0];

	$row_count++;

	if ($DB > 0) {print "$campaign_id ( $row_count / $num_camp )\n";}

	$list_string = "";
	$current_row=0;
	$num_lead=0;

	$list_stmt = "SELECT list_id FROM vicidial_lists WHERE campaign_id = '$campaign_id';";
	$list_sth = $dbhB->prepare($list_stmt) or die "preparing: ",$dbhB->errstr;
	$list_sth->execute or die "executing: $list_stmt ", $dbhb->errstr;
	$num_lists = $list_sth->rows;
	$list_row = 0;

	if ($num_lists > 0) 
		{
		while ($list_row < $num_lists) 
			{
			@list_row_ary = $list_sth->fetchrow_array;
			if ( $list_row == 0 ) 
				{
				$list_string = "$list_row_ary[0]";
				}
			else 
				{
				$list_string .= ", $list_row_ary[0]";
				}
			$list_row++;
			}

		$export_stmt = "SELECT lead_id, entry_date, modify_date, status, user, vendor_lead_code, source_id, list_id, gmt_offset_now, called_since_last_reset, phone_code, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time, rank, owner, entry_list_id  from vicidial_list where list_id IN ( $list_string )";


		$export_sth = $dbhB->prepare($export_stmt) or die "executing: $export_stmt ", $dbhb->errstr;
		$export_sth->execute or die "executing: $export_stmt ", $dbhb->errstr;

		$num_lead = $export_sth->rows;
		$current_row = 0;

		if ($DBX > 0) {print "$campaign_id - $num_lead - $export_stmt\n";}

		open( $fh, ">", "/tmp/$VARDB_database-$campaign_id.csv" );

		while ($current_row < $num_lead) 
			{
			@export_row = $export_sth->fetchrow_array;

			$lead_id		= $export_row[0];
			$entry_date	     = $export_row[1];
			$modify_date	    = $export_row[2];
			$status		 = $export_row[3];
			$user		   = $export_row[4];
			$vendor_lead_code       = $export_row[5];
			$source_id	      = $export_row[6];
			$list_id		= $export_row[7];
			$gmt_offset_now	 = $export_row[8];
			$called_since_last_reset= $export_row[9];
			$phone_code	     = $export_row[10];
			$phone_number	   = $export_row[11];
			$title		  = $export_row[12];
			$first_name	     = $export_row[13];
			$middle_initial	 = $export_row[14];
			$last_name	      = $export_row[15];
			$address1	       = $export_row[16];
			$address2	       = $export_row[17];
			$address3	       = $export_row[18];
			$city		   = $export_row[19];
			$state		  = $export_row[20];
			$province	       = $export_row[21];
			$postal_code	    = $export_row[22];
			$country_code	   = $export_row[23];
			$gender		 = $export_row[24];
			$date_of_birth	  = $export_row[25];
			$alt_phone	      = $export_row[26];
			$email		  = $export_row[27];
			$security_phrase	= $export_row[28];
			$comments	       = $export_row[29];
			$called_count	   = $export_row[30];
			$last_local_call_time   = $export_row[31];
			$rank		   = $export_row[32];
			$owner		  = $export_row[33];
			$entry_list_id	  = $export_row[34];


			print $fh "'$lead_id', '$entry_date', '$modify_date', '$status', '$user', '$vendor_lead_code', '$source_id', '$list_id', '$gmt_offset_now', '$called_since_last_reset', '$phone_code', '$phone_number', '$title', '$first_name', '$middle_initial', '$last_name', '$address1', '$address2', '$address3', '$city', '$state', '$province', '$postal_code', '$country_code', '$gender', '$date_of_birth', '$alt_phone', '$email', '$security_phrase', '$comments', '$called_count', '$last_local_call_time', '$rank', '$owner', '$entry_list_id'\n";


			#print "'$lead_id', '$entry_date', '$modify_date', '$status', '$user', '$vendor_lead_code', '$source_id', '$list_id', '$gmt_offset_now', '$called_since_last_reset', '$phone_code', '$phone_number', '$title', '$first_name', '$middle_initial', '$last_name', '$address1', '$address2', '$address3', '$city', '$state', '$province', '$postal_code', '$country_code', '$gender', '$date_of_birth', '$alt_phone', '$email', '$security_phrase', '$comments', '$called_count', '$last_local_call_time', '$rank', '$owner', '$entry_list_id'\n";

			if ($DB > 0)
				{
				if ($leads =~ /1000$/i) {print STDERR "  *     TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /2000$/i) {print STDERR "   *    TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /3000$/i) {print STDERR "    *   TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /4000$/i) {print STDERR "     *  TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /5000$/i) {print STDERR "      * TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /6000$/i) {print STDERR "     *  TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /7000$/i) {print STDERR "    *   TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /8000$/i) {print STDERR "   *    TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /9000$/i) {print STDERR "  *     TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				if ($leads =~ /0000$/i) {print STDERR " *      TOTAL: $leads  CAMPAIGNS:($row_count/$num_camp)  LEADS:($current_row/$num_lead)\r";}
				}

			$leads++;
			$current_row++;
			}
		close( $fh );
		}
	if ( ($current_row == $num_lead) || ($current_row == '0') || ($num_lists == '0') )
		{
		if ($DB > 0)
			{
			print "CAMPAIGN: $campaign_id($row_count/$num_camp)  LISTS: $num_lists  LEADS:($current_row/$num_lead)  TOTAL LEADS PROCESSED: $leads\n";
			}
		}
	}

### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if ($DB > 0) {print "DONE, export of $leads leads finished, exiting...\n";}
if ($DB > 0) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}

exit;
