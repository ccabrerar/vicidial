#!/usr/bin/perl
#
# AST_expired_list_exporter.pl
#
# This program can export and transfer defunct lists/leads from the dialer
# when they are moved to a holding campaign of the user's choice, and then
# delete them from the system when the export and transfer are complete.
# Settings are determined by creation of a setting container in the admin 
# interface, whose name must be passed to the script at the time of it's 
# execution, as shown below.
#
# 0 1 * * * /usr/share/astguiclient/AST_expired_list_exporter.pl --container_id=TEST
#
# This program assumes that a setting container exists in the dialer whose
# name matches the name of the container_id passed to the script. There are
# seven settings that can be recognized by this script:
# --campaign_id (required, name of holding campaign)
# --last_call_date_days (required, number of days list must be inactive before purging)
#    --last_call_date_days_export (separate date option for exporting only)
#    --last_call_date_days_delete (separate date option for deleting only)
# --custom_ftp_host (required, FTP host)
# --custom_ftp_user (required, FTP user)
# --custom_ftp_pwd (required, FTP password)
# --ftp_passive (optional, set to non-zero value to FTP transfer in passive mode, defaults to active if not set)
# --custom_ftp_port (optional, defaults to 21)
# --destination_folder (optional, used for specifying a destination folder to 
#                       transfer to once logged into the FTP account)
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# 180307-2120 - First Build
# 180625-0211 - Added options to use different date ranges for exports and deletes
# 191111-1700 - Committed 'now' option for export range with 5am caveat
#

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
$FTPdate = "$year-$mon-$mday";
$timestamp="$year$mon$mday$hour$min$sec";

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';
$FTPdb=0;
$FTPpassive=0;

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
	if ( ($line =~ /^PATHDONEmonitor/) && ($CLIDONEmonitor < 1) )
		{$PATHDONEmonitor = $line;   $PATHDONEmonitor =~ s/.*=//gi;}
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
	if ( ($line =~ /^VARFTP_host/) && ($CLIFTP_host < 1) )
		{$VARFTP_host = $line;   $VARFTP_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_user/) && ($CLIFTP_user < 1) )
		{$VARFTP_user = $line;   $VARFTP_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_pass/) && ($CLIFTP_pass < 1) )
		{$VARFTP_pass = $line;   $VARFTP_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_port/) && ($CLIFTP_port < 1) )
		{$VARFTP_port = $line;   $VARFTP_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARFTP_dir/) && ($CLIFTP_dir < 1) )
		{$VARFTP_dir = $line;   $VARFTP_dir =~ s/.*=//gi;}
	if ( ($line =~ /^VARHTTP_path/) && ($CLIHTTP_path < 1) )
		{$VARHTTP_path = $line;   $VARHTTP_path =~ s/.*=//gi;}
	$i++;
	}

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
		print "  [--help] = this screen\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--FTPdb] = show FTP debug output\n";
		print "  [--test] = will execute script and transfer files, but not delete lists from dialer\n";
		print "  [--container_id=XXX] = System settings container to pull info from\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG -----\n\n";
			}
		if ($args =~ /--FTPdb/i)
			{
			$FTPdb=1;
			print "\n----- FTP DEBUG -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$T=1;
			print "\n----- TEST EXECUTION -----\n\n";
			}
		if ($args =~ /--container_id/i)
			{
			my @data_in = split(/--container_id=/,$args);
			$container_id = $data_in[1];
			$container_id =~ s/ .*//gi;
			if ($DB) {print "\n----- SYSTEM CONTAINER: $container_id -----\n\n";}
			}
		}
	}

if (!-d "$PATHweb/vicidial/export_bkups") {
    `mkdir $PATHweb/vicidial/export_bkups`;
	if (!-d "$PATHweb/vicidial/export_bkups") {	
		print "Directory $PATHweb/vicidial/export_bkups does not exist and can't be created.\n";
		exit;
	}
} 

if (!$container_id) {
	print "Missing required container_id variable\n";
	exit;
}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhD = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$header_stmt="show columns from vicidial_list";
$header_rslt=$dbhA->prepare($header_stmt);
$header_rslt->execute();
$CSV_header="";
while (@header_row=$header_rslt->fetchrow_array) 
	{
	$CSV_header.="\"$header_row[0]\",";
	# print $header_row[0]."\n";
	}

$stmt="select * from vicidial_settings_containers where container_id='$container_id' and container_type='PERL_CLI'";
if ($DBX) {print "\n$stmt\n\n";}
$rslt=$dbhA->prepare($stmt);
$rslt->execute();
if ($rslt->rows>0) 
	{
	@row=$rslt->fetchrow_array;
	$row[4]=~s/\r//g;
	@container_settings=split(/\n/, $row[4]);
	for ($i=0; $i<scalar(@container_settings); $i++) 
		{
		$args=$container_settings[$i];
		if ($args =~ /--campaign_id/i)
			{
			my @data_in = split(/--campaign_id=/,$args);
			$campaign_id = $data_in[1];
			$campaign_id =~ s/ .*//gi;
			if ($DB) {print "\n----- CAMPAIGN ID: $campaign_id -----\n\n";}
			}
		if ($args =~ /--last_call_date_days\=/i)
			{
			my @data_in = split(/--last_call_date_days=/,$args);
			$last_call_date_days = $data_in[1];
			$last_call_date_days =~ s/ .*//gi;
			$last_call_date_days =~ s/[^0-9|(now)]//gi;
			if ($DB) {print "\n----- LAST CALL DATE EXPIRATION DAYS: $last_call_date_days -----\n\n";}
			}
		if ($args =~ /--last_call_date_days_export/i)
			{
			my @data_in = split(/--last_call_date_days_export=/,$args);
			$last_call_date_days_export = $data_in[1];
			$last_call_date_days_export =~ s/ .*//gi;
			$last_call_date_days_export =~ s/[^0-9|(now)]//gi;
			if ($DB) {print "\n----- LAST CALL DATE EXPIRATION DAYS, EXPORT ONLY: $last_call_date_days_export -----\n\n";}
			}
		if ($args =~ /--last_call_date_days_delete/i)
			{
			my @data_in = split(/--last_call_date_days_delete=/,$args);
			$last_call_date_days_delete = $data_in[1];
			$last_call_date_days_delete =~ s/ .*//gi;
			$last_call_date_days_delete =~ s/[^0-9|(now)]//gi;
			if ($DB) {print "\n----- LAST CALL DATE EXPIRATION DAYS, DELETE ONLY: $last_call_date_days_delete -----\n\n";}
			}
		if ($args =~ /--custom_ftp_host/i)
			{
			my @data_in = split(/--custom_ftp_host=/,$args);
			$custom_ftp_host = $data_in[1];
			$custom_ftp_host =~ s/ .*//gi;
			if ($DBX) {print "\n----- FTP HOST: $custom_ftp_host -----\n\n";}
			}
		if ($args =~ /--custom_ftp_port/i)
			{
			my @data_in = split(/--custom_ftp_port=/,$args);
			$custom_ftp_port = $data_in[1];
			$custom_ftp_port =~ s/ .*//gi;
			$custom_ftp_port =~ s/[^0-9]//gi;
			if ($DBX) {print "\n----- FTP PORT: $custom_ftp_port -----\n\n";}
			}
		if ($args =~ /--custom_ftp_user/i)
			{
			my @data_in = split(/--custom_ftp_user=/,$args);
			$custom_ftp_user = $data_in[1];
			$custom_ftp_user =~ s/ .*//gi;
			if ($DBX) {print "\n----- FTP USER: $custom_ftp_user -----\n\n";}
			}
		if ($args =~ /--ftp_passive/i)
			{
			my @data_in = split(/--ftp_passive=/,$args);
			$FTPpassive = $data_in[1];
			$FTPpassive =~ s/ .*//gi;
			$FTPpassive =~ s/[^0-9]//gi;
			if ($FTPpassive!=0) {$FTPpassive=1;}
			if ($DBX) {print "\n----- FTP PASSIVE: $FTPpassive -----\n\n";}
			}
		if ($args =~ /--custom_ftp_pwd/i)
			{
			my @data_in = split(/--custom_ftp_pwd=/,$args);
			$custom_ftp_pwd = $data_in[1];
			$custom_ftp_pwd =~ s/ .*//gi;
			if ($DBX) {print "\n----- FTP PASSWORD: $custom_ftp_pwd -----\n\n";}
			}
		if ($args =~ /--ftp_persistent/i)
			{
			$FTPpersistent=1;
			if ($DB) {print "\n----- FTP PERSISTENT  -----\n\n";}
			}
		if ($args =~ /--destination_folder/i)
			{
			my @data_in = split(/--destination_folder=/,$args);
			$destination_folder = $data_in[1];
			$destination_folder =~ s/ .*//gi;
			$destination_folder =~ s/-------/ /gi;
			if ($DB) {print "\n----- DESTINATION FOLDER: $destination_folder -----\n\n";}
			}
		}

	if (!$last_call_date_days_export) 
		{
		$last_call_date_days_export=$last_call_date_days;
		}
	if (!$last_call_date_days_delete) 
		{
		$last_call_date_days_delete=$last_call_date_days;
		}

	if (!$campaign_id) 
		{
		print "Script failed - no campaign to pull from.\n";
		exit;
		}
	if (!$last_call_date_days_export) 
		{
		print "Script failed - no last call date range given.\n";
		exit;
		}
	if (!$custom_ftp_host || !$custom_ftp_user || !$custom_ftp_pwd) 
		{
		print "Script failed - missing FTP info\n";
		exit;
		}
	if ($last_call_date_days_export=~/now/i) 
		{
		$last_call_date_days_export=0;
		if ($hour>5) 
			{
			print "Script failed - cannot run 'now' date range after 6am.\n";
			exit;
			}
		}
	if ($last_call_date_days_delete=~/now/i) 
		{
		$last_call_date_days_delete=0;
		if ($hour>5) 
			{
			print "Script failed - cannot run 'now' date range after 6am.\n";
			exit;
			}
		}
	if (!$custom_ftp_port) {$custom_ftp_port=21;}

	#### EXPORT SEGMENT
	$list_export_stmt="select list_id from vicidial_lists where active='N' and campaign_id='$campaign_id' and list_lastcalldate<=now()-INTERVAL $last_call_date_days_export DAY";
	if ($DBX) {print "\n$list_export_stmt\n\n";}
	$list_rslt=$dbhB->prepare($list_export_stmt);
	$list_rslt->execute();
	$lists_to_export=$list_rslt->rows;

	if ($lists_to_export>0) 
		{
		use Net::Ping;
		use Net::FTP;

		$p = Net::Ping->new();
		$ping_good = $p->ping("$VARFTP_host");
		if (!$ping_good)
			{
			$p = Net::Ping->new("icmp");
			$ping_good = $p->ping("$VARFTP_host");
			}
			
		if ($ping_good)
			{	
			$ftp = Net::FTP->new("$custom_ftp_host", Port => $custom_ftp_port, Passive => $FTPpassive, Debug => $FTPdb);
			$ftp->login("$custom_ftp_user","$custom_ftp_pwd");
			if ($destination_folder) {$ftp->cwd("$destination_folder");}
			}
		else
			{
			if($DB){print "ERROR: Could not ping server $VARFTP_host\n";}
			exit;
			}
		}
	else
		{
		if($DB){print "No lists to export\n";}
		# exit;
		}

	$l=0;
	while ($l<$lists_to_export) 
		{
		@list_row=$list_rslt->fetchrow_array;
		$list_id=$list_row[0];

		$export_filename="EXPORT_".$list_id."_".$timestamp.".csv";

		$custom_tbl_stmt="show tables like 'custom_".$list_id."'";
		if ($DBX) {print "\n$custom_tbl_stmt\n";}
		$custom_tbl_rslt=$dbhC->prepare($custom_tbl_stmt);
		$custom_tbl_rslt->execute();
		$CSV_custom_header="";
		if ($custom_tbl_rslt->rows==1) 
			{
			$pull_custom_info=1;
			$col_stmt="show columns from custom_".$list_id;
			$col_rslt=$dbhD->prepare($col_stmt);
			$col_rslt->execute();
			while (@col_row=$col_rslt->fetchrow_array) 
				{
				$CSV_custom_header.="\"$col_row[0]\",";
				}
			$col_rslt->finish;
			}
		$custom_tbl_rslt->finish;
		
		$CSV_full_header=$CSV_header.$CSV_custom_header;
		$CSV_full_header=~s/,$//;

		open(EXPORT, "> $PATHweb/vicidial/export_bkups/$export_filename");
		print EXPORT $CSV_full_header."\n";

		$exp_stmt="select * from vicidial_list where list_id='$list_id' order by lead_id asc";
		if ($DBX) {print "\n$exp_stmt\n";}
		$exp_rslt=$dbhC->prepare($exp_stmt);
		$exp_rslt->execute();
		while (@exp_row=$exp_rslt->fetchrow_array) 
			{
			$CSV_data="";
			$lead_id=$exp_row[0];
			for ($q=0; $q<scalar(@exp_row); $q++) 
				{
				$CSV_data.="\"$exp_row[$q]\",";
				}
			if ($pull_custom_info) 
				{
				$cdata_stmt="select * from custom_".$list_id." where lead_id=$lead_id";
				if ($DBX) {print "\n$cdata_stmt\n";}
				$cdata_rslt=$dbhD->prepare($cdata_stmt);
				$cdata_rslt->execute();
				if ($cdata_rslt->rows>0) 
					{
					@cdata_row=$cdata_rslt->fetchrow_array;
					for ($c=1; $c<scalar(@cdata_row); $c++) 
						{
						$CSV_data.="\"$cdata_row[$c]\",";
						}
					}
				$cdata_rslt->finish;
				}
			$CSV_data=~s/,$//;
			print EXPORT $CSV_data."\n";
			}
		$exp_rslt->finish;

		close(EXPORT);
		$ExportFileSize = (-s "$PATHweb/vicidial/export_bkups/$export_filename");


		$ftp->binary();
		$ftp->put("$PATHweb/vicidial/export_bkups/$export_filename", "$export_filename");
		$FTPfilesize = $ftp->size("$export_filename");
		if($DBX){print STDERR "FTP FILESIZE:   $FTPfilesize/$ExportFileSize | $export_filename\n";}

		if ( !$FTPfilesize || (($ExportFileSize > 100) && ($FTPfilesize < 100)) )
			{
			print "ERROR! File did not transfer, exiting:   $FTPfilesize / $ExportFileSize | $export_filename\n";
			exit;
			}

		$l++;
		}
		
	$list_rslt->finish;

	if ($last_call_date_days_delete<$last_call_date_days_export) {
		$last_call_date_days_delete=$last_call_date_days_export;
		print "WARNING! Current settings delete lists before exporting - delete day range defaulting to export day range of $last_call_date_days_export day(s)\n";
	}

	$list_delete_stmt="select list_id from vicidial_lists where active='N' and campaign_id='$campaign_id' and list_lastcalldate<=now()-INTERVAL $last_call_date_days_delete DAY";
	if ($DBX) {print "\n$list_delete_stmt\n\n";}

	# Put test check here so you can see the delete statement if you're running in debug mode as well.
	if (!$T) 
		{
		$list_rslt=$dbhB->prepare($list_delete_stmt);
		$list_rslt->execute();
		$lists_to_delete=$list_rslt->rows;

		$l=0;
		while ($l<$lists_to_delete) 
			{
			@list_row=$list_rslt->fetchrow_array;
			$list_id=$list_row[0];

			$del_stmt="delete from vicidial_list where list_id='$list_id'";
			if ($DBX) {print "\n\n$del_stmt";}
			$del_rslt=$dbhC->prepare($del_stmt);
			$del_rslt->execute();
			$del_rslt->finish;
				
			$del_stmt="delete from vicidial_lists where list_id='$list_id'";
			if ($DBX) {print "\n$del_stmt";}
			$del_rslt=$dbhC->prepare($del_stmt);
			$del_rslt->execute();
			$del_rslt->finish;

			$del_stmt="delete from vicidial_hopper where list_id='$list_id'";
			if ($DBX) {print "\n$del_stmt\n\n";}
			$del_rslt=$dbhC->prepare($del_stmt);
			$del_rslt->execute();
			$del_rslt->finish;
			$l++;
			}
		}
	}
else
	{
	if ($DB) {print "No setting container \"$conatiner_id\" of type PERL_CLI found.\n";}
	exit;
	}

$rslt->finish;

