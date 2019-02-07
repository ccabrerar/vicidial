#!/usr/bin/perl
#
# AST_DNCcom_filter.pl version 2.12
#
# This script filters lists against dnc.com
#
# !!! REQUIRES A SETTINGS CONTAINER CALLED "DNCDOTCOM" TO BE SET UP TO USE !!!
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 151004-1606 - Initial Build
# 151005-1256 - Added logging
# 151019-1456 - Reorganzied to reduce vicidial_list locks and improve dnc.com response
# 151022-1900 - Code clean up
# 151202-1435 - Added URL option for settings container
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages per minute

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
		print "  [--debug] = verbose debug messages\n";
		print "  [--lists=LISTID1-LISTID2] = the list ids to be filters\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			print "-- DEBUGGING ENABLED --\n\n";
			}
		if ($args =~ /--lists=/i)
			{
			@data_in = split(/--lists=/,$args);
			$CLIlists = $data_in[1];
			$CLIlists =~ s/ .*$//gi;
			if ($CLIlists =~ /-/)
				{
				$CLIlists =~ s/-/,/gi;
				}
			}
		if (length($CLIlists)<2)
			{
			print "no lists defined, exiting.\n";
			exit;
			}
		if ($DB) {print "|$CLIlists|\n";}
		}
	}
else
	{
	print "no command line options set\n";
	exit;
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

use DBI;
use Time::HiRes qw( gettimeofday );
use WWW::Curl::Easy;
use WWW::Curl::Form;
	  
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$stmtA = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = 'DNCDOTCOM';";
if ($DB) {print "|$stmtA|\n";}
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($sthArows > 0) 
	{

	# log to the vicidial_admin_log the scrub
	foreach $list_id (split /,/ ,$CLIlists)
		{
		$now = get_mysql_date_string(time());
		$stmtA = "INSERT INTO vicidial_admin_log SET event_date = '$now', user = 'VDAD', ip_address = '1.1.1.1', event_section = 'LISTS', event_type = 'OTHER', record_id = '$list_id', event_sql='', event_code='DNC.com SCRUB STARTED', event_notes='DNC.com scrub of list $list_id started at $now';";
		if ($DB) { print "|$stmtA|\n"; }
		$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;
		}


	@aryA = $sthA->fetchrow_array;
	$container_entry = $aryA[0];
	%dnccom_settings = get_container_settings($container_entry);

	if ( 
		exists($dnccom_settings{'LOGIN_ID'}) && 
		exists($dnccom_settings{'CAMPAIGN_ID'}) && 
		exists($dnccom_settings{'PROJ_ID'}) && 
		exists($dnccom_settings{'VERSION'}) 
	)
		{
	#	$base_url = "http://www.dncscrub.com/app/main/rpc/scrub";
		$base_url = $dnccom_settings{"DNC_DOT_COM_URL"};
		$url = "$base_url?loginId=$dnccom_settings{'LOGIN_ID'}&version=$dnccom_settings{'VERSION'}&projId=$dnccom_settings{'PROJ_ID'}&campaignId=$dnccom_settings{'CAMPAIGN_ID'}&phoneList=";

		if ($DB) {print "|$url|\n";}

		$skip_statuses = $dnccom_settings{'VICI_STATUS_SKIP'};
		$skip_statuses = "'$skip_statuses'";
		$skip_statuses =~ s/-/','/gi;	

		if ($DB) {print "|$skip_statuses|\n";}


		$lead_id_pos = 0;
		$lead_grab = 1000; # number of leads to grab at a time
		$scrub_grab = 50000; # number of leads to send to DNC.com
		$scrub_attempts = 5; # number of times to attempt to connect to DNC.com before erroring out
		$loop = 1;
		$scrub_count = 0;

		$x_count=0;
		$c_count=0;
		$o_count=0;
		$e_count=0;
		$r_count=0;
		$w_count=0;
		$g_count=0;
		$h_count=0;
		$l_count=0;
		$f_count=0;
		$v_count=0;
		$i_count=0;
		$m_count=0;
		$b_count=0;
		$p_count=0;
		$d_count=0;
		$s_count=0;
		$t_count=0;
		$y_count=0;
		

		while ( $loop > 0 ) 
			{
			$scrub_success = 0;
			$good_num_count = 0;
			$phone_string = '';

			my @leads;

			# Grab leads till we have enough to call the DNC.com URL
			$grabbed_leads = 0;
			while (( $good_num_count < $scrub_grab ) && ( $loop > 0 ))
				{
				# grab a chuck of leads
				$stmtA = "SELECT lead_id, phone_number, status, list_id FROM vicidial_list WHERE list_id IN ($CLIlists) AND status NOT IN ($skip_statuses) AND lead_id > $lead_id_pos ORDER BY lead_id LIMIT $lead_grab;" ;
				if ($DB) {print "|$stmtA|\n";}
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;

				# if we got results
				if ( $sthArows > 0 ) 
					{
					$grabbed_leads = 1;
					$rec_count=0;

					while ( $sthArows > $rec_count )
						{
						@aryA = $sthA->fetchrow_array;
						$lead_id = $aryA[0];
						$phone_number = $aryA[1];
						$status = $aryA[2];
						$list_id = $aryA[3];

						# verify the lead is 10 digits
						if ( $phone_number =~ /^\d\d\d\d\d\d\d\d\d\d$/ ) 
							{
						#	if ($DB) { print "$good_num_count|$phone_number is in NANPA\n"; }
							if ( $good_num_count == 0 ) { $phone_string = "$phone_number"; }
							else { $phone_string = "$phone_string,$phone_number"; }
	
							# add the numbers to our array
							$leads[$good_num_count][0] = $lead_id;
							$leads[$good_num_count][1] = $phone_number;
							$leads[$good_num_count][2] = $status;
							$leads[$good_num_count][3] = $list_id;

							$good_num_count++;
							$scrub_count++;
							} 
						else 
							{
							if ($DB) { print "$phone_number is not 10 digits\n"; }
							}
			
						# keep track of the last lead we grabbed
						$lead_id_pos = $lead_id;
						$rec_count++;
						}

					}
				# if not stop looping
				else
					{
 				      	$loop = 0;
					}

				}


			while ( ($scrub_attempts > 0) && ($scrub_success != 1) && ($grabbed_leads == 1) )
				{

				# scrub the leads
				if ($DB) {print "|$phone_string|\n";}

				my $dnccom_body; # string to hold their response

				# prepare the curl object
				my $curl = WWW::Curl::Easy->new;
				$curl->setopt(CURLOPT_HEADER,0);
				$curl->setopt(CURLOPT_URL, "$base_url");
				$curl->setopt(CURLOPT_WRITEDATA,\$dnccom_body);

				# set the form data
				my $curlf = WWW::Curl::Form->new;
				$curlf->formadd("loginId", "$dnccom_settings{'LOGIN_ID'}");
				$curlf->formadd("version", "$dnccom_settings{'VERSION'}");
				$curlf->formadd("projId", "$dnccom_settings{'PROJ_ID'}");
				$curlf->formadd("campaignId", "$dnccom_settings{'CAMPAIGN_ID'}");
				$curlf->formadd("phoneList", "$phone_string");

				# attach the form data
				$curl->setopt(CURLOPT_HTTPPOST, $curlf);

				# time before URL
				($before_seconds, $before_microseconds) = gettimeofday();

				# call the URL
				my $retcode = $curl->perform;

				# time after URL
				($after_seconds, $after_microseconds) = gettimeofday();

				# calculate their response time
				$url_seconds = $after_seconds - $before_seconds;

				if ($DB) {print "$before_seconds|$after_seconds|request time = $url_seconds\n";}

				if ( $retcode == 0 )
					{
					$scrub_success = 1;
					$result_count = 0;

					# split their response into single lines
					if ($DB) {print "Spliting\n";}
					foreach $line (split /\n/ ,$dnccom_body) 
						{
						#if ($DB) {print "$line\n";}
						@line_results = split /,/, $line;

						$dnccom_pn = $line_results[0];
						$dnccom_rc = $line_results[1];

						# verify that the result phone number matches the one in the leads array
						if ( $dnccom_pn eq $leads[$result_count][1] )
							{
							# keep track of how many we got of each
							if ( $dnccom_rc eq 'X' ) { $x_count++; $up_status=$dnccom_settings{"STATUS_X"};}
							if ( $dnccom_rc eq 'C' ) { $c_count++; $up_status=$dnccom_settings{"STATUS_C"};}
							if ( $dnccom_rc eq 'O' ) { $o_count++; $up_status=$dnccom_settings{"STATUS_O"};}
							if ( $dnccom_rc eq 'E' ) { $e_count++; $up_status=$dnccom_settings{"STATUS_E"};}
							if ( $dnccom_rc eq 'R' ) { $r_count++; $up_status=$dnccom_settings{"STATUS_R"};}
							if ( $dnccom_rc eq 'W' ) { $w_count++; $up_status=$dnccom_settings{"STATUS_W"};}
							if ( $dnccom_rc eq 'G' ) { $g_count++; $up_status=$dnccom_settings{"STATUS_G"};}
							if ( $dnccom_rc eq 'H' ) { $h_count++; $up_status=$dnccom_settings{"STATUS_H"};}
							if ( $dnccom_rc eq 'L' ) { $l_count++; $up_status=$dnccom_settings{"STATUS_L"};}
							if ( $dnccom_rc eq 'F' ) { $f_count++; $up_status=$dnccom_settings{"STATUS_F"};}
							if ( $dnccom_rc eq 'V' ) { $v_count++; $up_status=$dnccom_settings{"STATUS_V"};}
							if ( $dnccom_rc eq 'I' ) { $i_count++; $up_status=$dnccom_settings{"STATUS_I"};}
							if ( $dnccom_rc eq 'M' ) { $m_count++; $up_status=$dnccom_settings{"STATUS_M"};}
							if ( $dnccom_rc eq 'B' ) { $b_count++; $up_status=$dnccom_settings{"STATUS_B"};}
							if ( $dnccom_rc eq 'P' ) { $p_count++; $up_status=$dnccom_settings{"STATUS_P"};}
							if ( $dnccom_rc eq 'D' ) { $d_count++; $up_status=$dnccom_settings{"STATUS_D"};}
							if ( $dnccom_rc eq 'S' ) { $s_count++; $up_status=$dnccom_settings{"STATUS_S"};}
							if ( $dnccom_rc eq 'T' ) { $t_count++; $up_status=$dnccom_settings{"STATUS_T"};}
							if ( $dnccom_rc eq 'Y' ) { $y_count++; $up_status=$dnccom_settings{"STATUS_Y"};}


							# update the lead
							if ( $dnccom_settings{'ADD_INFO_TO_COMMENTS'} eq 'YES' ) 
								{
								$stmtA = "UPDATE vicidial_list SET status = '$up_status', comments = CONCAT(comments,'!N$leads[$result_count][2]!N$line') where lead_id=$leads[$result_count][0] and phone_number='$leads[$result_count][1]';";
								} 
							else 
								{
								$stmtA = "UPDATE vicidial_list SET status = '$up_status' where lead_id=$leads[$result_count][0] and phone_number='$leads[$result_count][1]';";
								}
							if ($DB) { print "|$stmtA|\n"; }
							$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;


							# insert a log record
							$now = get_mysql_date_string(time());
							$stmtA = "INSERT INTO vicidial_dnccom_filter_log SET lead_id = $leads[$result_count][0], list_id = $leads[$result_count][3], filter_date = '$now', new_status = '$up_status', old_status= '$leads[$result_count][2]', phone_number = '$leads[$result_count][1]', dnccom_data = '$line';";
							if ($DB) { print "|$stmtA|\n"; }
							$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;


							}
						else
							{
							# something went very wrong with the code. This should not happen
							print "leads phone_number doesnt match DNC.com. Exiting.\n";
							exit();
							}
						
						$result_count++;
						}

					}
				else
					{
					# Error code, type of error, error message
					$error = "An error happened: $retcode ".$curl->strerror($retcode)." ".$curl->errbuf;
					$scrub_attempts--;
					if ($DB) { print("$error\n"); }

					# log the error to the vicidial_admin_log
					foreach $list_id (split /,/ ,$CLIlists)
						{
						$now = get_mysql_date_string(time());
						$stmtA = "INSERT INTO vicidial_admin_log SET event_date = '$now', user = 'VDAD', ip_address = '1.1.1.1', event_section = 'LISTS', event_type = 'OTHER', record_id = '$list_id', event_sql='', event_code='DNC.com SCRUB ERROR', event_notes='DNC.com scrub of lists $CLIlists had an error: \'$error\' after processing $scrub_count leads.';";
						if ($DB) { print "|$stmtA|\n"; }
						$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;
						}


					}
				}

			if ( $scrub_attempts == 0 )
				{
				# log the error to the vicidial_admin_log
				foreach $list_id (split /,/ ,$CLIlists)
					{
					$now = get_mysql_date_string(time());
					$stmtA = "INSERT INTO vicidial_admin_log SET event_date = '$now', user = 'VDAD', ip_address = '1.1.1.1', event_section = 'LISTS', event_type = 'OTHER', record_id = '$list_id', event_sql='', event_code='DNC.com SCRUB FAILED', event_notes='DNC.com scrub of lists $CLIlists failed after processing $scrub_count leads due to connection errors.';";
					if ($DB) { print "|$stmtA|\n"; }
					$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;
					}	
				}

			}

			$result_str = "|X$x_count|C$c_count|O$o_count|E$e_count|R$r_count|W$w_count|G$g_count|H$h_count|L$l_count|F$f_count|V$v_count|I$i_count|M$m_count|B$b_count|P$p_count|D$d_count|S$s_count|T$t_count|Y$y_count|";
			if ($DB) { print "$result_str\n"; }

			# log the error to the vicidial_admin_log
			foreach $list_id (split /,/ ,$CLIlists)
				{
				$now = get_mysql_date_string(time());
				$stmtA = "INSERT INTO vicidial_admin_log SET event_date = '$now', user = 'VDAD', ip_address = '1.1.1.1', event_section = 'LISTS', event_type = 'OTHER', record_id = '$list_id', event_sql='', event_code='DNC.com SCRUB FINISHED', event_notes='DNC.com scrub of lists $CLIlists finished after processing $scrub_count leads with the following results:\n$result_str';";
				if ($DB) { print "|$stmtA|\n"; }
				$dbhA->do($stmtA) or die "executing: $stmtA ", $dbhA->errstr;
				}

		}
	else 
		{
		print "DNC.com support not setup properly. Exiting.";
		exit();
		}
	}
else
	{
	print "DNC.com support not setup properly. You must have a Settings Container called 'DNCDOTCOM' set up on your Vicidial System. Exiting.";
	exit();
	}


sub get_container_settings
{
	my %settings_hash;

	# the container entry from the DB
	my $container_entry = $_[0];
	
	# loop through the container
	foreach $line (split /\n/ ,$container_entry) {

		$line =~ s/[#;].*$//gi;	# remove comment
		$line =~ s/^\s+//gi;	# remove leading whitespace
		$line =~ s/\s+$//gi;	# remove trailing whitespace

		if ( $line ne "" )
			{
			my @setting_parts = split( /=>/ , $line );
			$setting_parts[0] =~ s/^\s+//gi;
			$setting_parts[0] =~ s/\s+$//gi;
			$setting_parts[1] =~ s/^\s+//gi;
			$setting_parts[1] =~ s/\s+$//gi;
			$settings_hash{"$setting_parts[0]"} = $setting_parts[1] ;
			}

	}

	if ($DB) 
		{
		foreach (sort keys %settings_hash) {
			print "$_ : $settings_hash{$_}\n";
			}
		}

	return %settings_hash;
}

sub get_mysql_date_string
{
	my $time = $_[0];

	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	$mysql_date = "$year-$mon-$mday $hour:$min:$sec";

	return $mysql_date;
}
