#!/usr/bin/perl
#
# AST_CRON_audio_1_move_VDonly.pl
#
# This is a STEP-1 program in the audio archival process
#
# IMPORTANT!!! ONLY TO BE USED WHEN ONLY VICIDIAL RECORDINGS ARE ON THE SYSTEM!
#
# runs every 3 minutes and copies the -in recordings in the monitor to the DONE
# directory for further processing. Very important for RAM-drive usage
# 
# put an entry into the cron of of your asterisk machine to run this script 
# every 3 minutes or however often you desire
#
# ### recording mixing/compressing/ftping scripts
##0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_mix.pl
# 0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * * /usr/share/astguiclient/AST_CRON_audio_1_move_VDonly.pl
# 1,4,7,10,13,16,19,22,25,28,31,34,37,40,43,46,49,52,55,58 * * * * /usr/share/astguiclient/AST_CRON_audio_2_compress.pl --GSM
# 2,5,8,11,14,17,20,23,26,29,32,35,38,41,44,47,50,53,56,59 * * * * /usr/share/astguiclient/AST_CRON_audio_3_ftp.pl --GSM
#
# make sure that the following directories exist:
# /var/spool/asterisk/monitor		# default Asterisk recording directory
# /var/spool/asterisk/monitorDONE	# where the moved -in files are put
# 
# This program assumes that recordings are saved by Asterisk as .wav
# should be easy to change this code if you use .gsm instead
# 
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 80302-1958 - First Build
# 80731-2253 - Changed size comparisons for more efficiency
# 130805-1450 - Added check for length and gather length of recording for database record
# 160523-0654 - Added --HTTPS option to use https instead of http in local location
# 160731-2103 - Added --POST options to change filename with variable lookups
#

$HTTPS=0;

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
		print "  [--quiet] = suppress output, if possible\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--test] = test\n";
		print "  [--HTTPS] = use https instead of http in local location\n";
		print "  [--POST] = post call variable filename replacement, MUST define STATUS and CAMP below\n";
		print "  [--STATUS-POST=X] = only run post call variable filename replacement on specific status calls\n";
		print "                      If multiple statuses, use --- delimiting, i.e.: SALE---PRESALE\n";
		print "                      For status wildcards, use * flag, i.e.: S* to match SALE and SQUAL\n";
		print "                      For all statuses, use ----ALL----\n";
		print "                      \n";
		print "  [--CAMP-POST=X] = only run post call variable filename replacement on specific campaigns or ingroups\n";
		print "                      If multiple campaigns or ingroups, use --- delimiting, i.e.: TESTCAMP---TEST_IN2\n";
		print "                      For all calls, use ----ALL----\n";
		print "  [--CLEAR-POST-NO-MATCH] = clear POST filename variables if no match is found\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--quiet/)
			{
			$q=1;
			}
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
		if ($args =~ /--test/)
			{
			$T=1;   $TEST=1;
			if ($q < 1) {print "\n-----TESTING -----\n\n";}
			}
		if ($args =~ /--HTTPS/i)
			{
			$HTTPS=1;
			if ($DB) {print "HTTPS location option enabled\n";}
			}
		if ($args =~ /--POST/i)
			{
			$POST=1;
			if ($DB) {print "POST post call variable filename replacement\n";}

			if ($args =~ /--STATUS-POST=/i)
				{
				@data_in = split(/--STATUS-POST=/,$args);
				$status_post = $data_in[1];
				$status_post =~ s/ .*//gi;
				if ($q < 1) {print "\n----- STATUS POST SET: $status_post -----\n\n";}
				}
			else
				{
				$POST=0;
				if ($q < 1) {print "\n----- POST disabled, no status set -----\n\n";}
				}

			if ($args =~ /--CAMP-POST=/i)
				{
				@data_in = split(/--CAMP-POST=/,$args);
				$camp_post = $data_in[1];
				$camp_post =~ s/ .*//gi;
				if ($q < 1) {print "\n----- CAMP POST SET: $camp_post -----\n\n";}
				}
			else
				{
				$POST=0;
				if ($q < 1) {print "\n----- POST disabled, no campaigns set -----\n\n";}
				}
			}
		if ($args =~ /--CLEAR-POST-NO-MATCH/i)
			{
			$CLEAR_POST=1;
			if ($DB) {print "--- CLEAR-POST-NO-MATCH ENABLED ---\n";}
			}
		}

	if ( ($POST > 0) && ( (length($camp_post) < 1) || (length($status_post) < 1) ) ) 
		{
		$POST=0;
		if ($q < 1) {print "\n----- POST disabled, status or campaign invalid: |$camp_post|$status_post| -----\n\n";}
		}
	}
else
	{
	#print "no command line options set\n";
	}


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

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP
if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


### find soxi to gather the length info if needed
$soxibin = '';
if ( -e ('/usr/bin/soxi')) {$soxibin = '/usr/bin/soxi';}
else 
	{
	if ( -e ('/usr/local/bin/soxi')) {$soxibin = '/usr/local/bin/soxi';}
	else
		{
		if ( -e ('/usr/sbin/soxi')) {$soxibin = '/usr/sbin/soxi';}
		else 
			{
			if ($DB) {print "Can't find soxi binary! No length calculations will be available...\n";}
			}
		}
	}

### directory where in/out recordings are saved to by Asterisk
$dir1 = "$PATHmonitor";
$dir2 = "$PATHDONEmonitor";

opendir(FILE, "$dir1/");
@FILES = readdir(FILE);


### Loop through files first to gather filesizes
$i=0;
foreach(@FILES)
	{
	$FILEsize1[$i] = 0;
	if ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") )
		{
		$FILEsize1[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize1[$i]\n";}
		}
	$i++;
	}

sleep(5);


### Loop through files a second time to gather filesizes again 5 seconds later
$i=0;
foreach(@FILES)
	{
	$FILEsize2[$i] = 0;

	if ( (length($FILES[$i]) > 4) && (!-d "$dir1/$FILES[$i]") )
		{

		$FILEsize2[$i] = (-s "$dir1/$FILES[$i]");
		if ($DBX) {print "$FILES[$i] $FILEsize2[$i]\n\n";}

		if ( ($FILES[$i] !~ /out\.wav|out\.gsm|lost\+found/i) && ($FILEsize1[$i] eq $FILEsize2[$i]) && (length($FILES[$i]) > 4))
			{
			$INfile = $FILES[$i];
			$OUTfile = $FILES[$i];
			$OUTfile =~ s/-in\.wav/-out.wav/gi;
			$OUTfile =~ s/-in\.gsm/-out.gsm/gi;
			$ALLfile = $FILES[$i];
			$ALLfile =~ s/-in\.wav/-all.wav/gi;
			$ALLfile =~ s/-in\.gsm/-all.gsm/gi;
			$SQLFILE = $FILES[$i];
			$SQLFILE =~ s/-in\.wav|-in\.gsm//gi;
			$filenameSQL='';

			$length_in_sec=0;
			$stmtA = "SELECT recording_id,length_in_sec,lead_id,vicidial_id from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
			if($DBX){print STDERR "\n|$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$recording_id =		$aryA[0];
				$length_in_sec =	$aryA[1];
				$lead_id =			$aryA[2];
				$vicidial_id =		$aryA[3];
				}
			$sthA->finish();

			if ($DB) {print "|$recording_id|$length_in_sec|$INfile|     |$ALLfile|\n";}

			if (!$T)
				{
				`mv -f "$dir1/$INfile" "$dir2/$ALLfile"`;
				`rm -f "$dir1/$OUTfile"`;
				}
			else
				{
				`cp -f "$dir1/$INfile" "$dir2/$ALLfile"`;
				}

			$lengthSQL='';
			if ( ( ($length_in_sec < 1) || ($length_in_sec =~ /^NULL$/i) || (length($length_in_sec)<1) ) && (length($soxibin) > 3) )
				{
				@soxi_output = `$soxibin -D $dir2/$ALLfile`;
				$soxi_sec = $soxi_output[0];
				$soxi_sec =~ s/\..*|\n|\r| //gi;
				$soxi_min = ($soxi_sec / 60);
				$soxi_min = sprintf("%.2f", $soxi_min);
				$lengthSQL = ",length_in_sec='$soxi_sec',length_in_min='$soxi_min'";
				}

			##### BEGIN post call variable replacement #####
			if ($POST > 0) 
				{
				if ($ALLfile =~ /POSTVLC|POSTSP|POSTADDR3|POSTSTATUS/)
					{
					$origALLfile = $ALLfile;
					$origSQLFILE = $SQLFILE;
					$vendor_lead_code='';   $security_phrase='';   $address3='';   $status='';
					$status_ALL=0;
					$camp_ALL=0;
					$camp_status_selected=0;

					if ($status_post =~ /----ALL----/)
						{
						$status_ALL++;
						if($DBX){print "All Statuses:  |$status_post|$status_ALL|\n";} 
						}

					if ($camp_post =~ /----ALL----/)
						{
						$camp_ALL++;
						if($DBX){print "All Campaigs and Ingroups:  |$camp_post|$camp_ALL|\n";} 
						}

					if ( ($camp_ALL < 1) || ($status_ALL < 1) ) 
						{
						$camp_postSQL='';
						if ($camp_ALL < 1)
							{
							$camp_postSQL = $camp_post;
							$camp_postSQL =~ s/---/','/gi;
							$camp_postSQL = "and campaign_id IN('$camp_postSQL')";
							}
						if ($vicidial_id =~ /\./) 
							{
							$log_lookupSQL = "SELECT status from vicidial_log where uniqueid='$vicidial_id' and lead_id='$lead_id' $camp_postSQL;";
							}
						else
							{
							$log_lookupSQL = "SELECT status from vicidial_closer_log where closecallid='$vicidial_id' and lead_id='$lead_id' $camp_postSQL;";
							}
						if($DBX){print STDERR "\n|$log_lookupSQL|\n";}
						$sthA = $dbhA->prepare($log_lookupSQL) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $log_lookupSQL ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$lead_status =		$aryA[0];
							if ($status_ALL > 0) 
								{$camp_status_selected++;}
							else
								{
								@status_vars = split(/---/,$status_post);
								$fc=0;
								foreach(@status_vars)
									{
									$status_temp = $status_vars[$fc];
									if ($status_vars[$fc] =~ /\*/) 
										{
										$status_temp =~ s/\*/.*/gi;
										if ( ($lead_status =~ /^$status_temp/) && ($status_vars[$fc] =~ /\*$/) )
											{$camp_status_selected++;}
										if ( ($lead_status =~ /$status_temp$/) && ($status_vars[$fc] =~ /^\*/) )
											{$camp_status_selected++;}
										}
									else
										{
										if ($lead_status =~ /^$status_temp$/) 
											{$camp_status_selected++;}
										}
									if ($DBX) {print "    POST processing DEBUG: $fc|$status_temp|$lead_status|$lead_id|$vicidial_id|$ALLfile|\n";}
									$fc++;
									}
								}
							if ($DB) {print "    POST processing SELECT: |$camp_status_selected|$sthArows|$camp_postSQL|$camp_ALL|$status_post|$status_ALL|$ALLfile|\n";}
							}
						else
							{if ($DB) {print "    POST processing ERROR: lead not found: |$lead_id|$ALLfile|\n";} }
						$sthA->finish();
						}

					if ( ( ($camp_ALL > 0) && ($status_ALL > 0) ) || ($camp_status_selected > 0) )
						{
						$stmtA = "SELECT vendor_lead_code,security_phrase,address3,status from vicidial_list where lead_id='$lead_id' LIMIT 1;";
						if($DBX){print STDERR "\n|$stmtA|\n";}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$vendor_lead_code =		$aryA[0];
							$security_phrase =		$aryA[1];
							$address3 =				$aryA[2];
							$status =				$aryA[3];

							$vendor_lead_code =~	s/[^a-zA-Z0-9_-]//gi;
							$security_phrase =~		s/[^a-zA-Z0-9_-]//gi;
							$address3 =~			s/[^a-zA-Z0-9_-]//gi;
							$status =~				s/[^a-zA-Z0-9_-]//gi;
							}
						else
							{if ($DB) {print "    POST processing ERROR: lead not found: |$lead_id|$ALLfile|\n";} }
						$sthA->finish();

						$ALLfile =~ s/POSTVLC/$vendor_lead_code/gi;
						$ALLfile =~ s/POSTSP/$security_phrase/gi;
						$ALLfile =~ s/POSTADDR3/$address3/gi;
						$ALLfile =~ s/POSTSTATUS/$status/gi;
						$SQLFILE =~ s/POSTVLC/$vendor_lead_code/gi;
						$SQLFILE =~ s/POSTSP/$security_phrase/gi;
						$SQLFILE =~ s/POSTADDR3/$address3/gi;
						$SQLFILE =~ s/POSTSTATUS/$status/gi;
						$filenameSQL = ",filename='$SQLFILE'";

						`mv -f "$dir2/$origALLfile" "$dir2/$ALLfile"`;

						if ($DB) {print "    POST processing COMPLETE: old: |$origALLfile| new: |$ALLfile|\n";}
						}
					else
						{
						if ($DB) {print "    POST processing SKIPPED: |$camp_ALL|$status_ALL|$camp_status_selected|$lead_id|$vicidial_id|$ALLfile|\n";}

						if ($CLEAR_POST > 0) 
							{
							$ALLfile =~ s/POSTVLC//gi;
							$ALLfile =~ s/POSTSP//gi;
							$ALLfile =~ s/POSTADDR3//gi;
							$ALLfile =~ s/POSTSTATUS//gi;
							$SQLFILE =~ s/POSTVLC//gi;
							$SQLFILE =~ s/POSTSP//gi;
							$SQLFILE =~ s/POSTADDR3//gi;
							$SQLFILE =~ s/POSTSTATUS//gi;
							$filenameSQL = ",filename='$SQLFILE'";

							`mv -f "$dir2/$origALLfile" "$dir2/$ALLfile"`;

							if ($DB) {print "    CLEAR POST COMPLETE: old: |$origALLfile| new: |$ALLfile|\n";}
							}
						}
					}
				else
					{if ($DB) {print "    POST processing ERROR: No variables found: |$ALLfile|\n";} }
				}
			##### END post call variable replacement #####

			$HTTP='http';
			if ($HTTPS > 0) {$HTTP='https';}
			$stmtA = "UPDATE recording_log set location='$HTTP://$server_ip/RECORDINGS/$ALLfile' $filenameSQL $lengthSQL where recording_id='$recording_id';";
				if($DBX){print STDERR "\n|$stmtA|\n";}
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

			### sleep for twenty hundredths of a second to not flood the server with disk activity
			usleep(1*200*1000);
			}
		}
	$i++;
	}

if ($DB) {print "DONE... EXITING\n\n";}

$dbhA->disconnect();


exit;
