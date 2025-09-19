#!/usr/bin/perl
#
# AST_CRON_audio_1_stereo.pl
#
# This is a STEP-1 program in the audio archival process
#
# IMPORTANT!!! ONLY TO BE USED WHEN STEREO VICIDIAL CALL RECORDINGS ARE ON THE SYSTEM!
#
# runs every 3 minutes if there are stereo call recording files to process.
# Processes audio and moves to other directories for further processing.
# 
# Do NOT put this script in the crontab! It is launched as needed by the 
# "AST_CRON_audio_1_move_VDonly.pl" script, which should be run every 3 minutes
#
# make sure that the following directories exist:
# /var/spool/asterisk/monitorS		# default Asterisk stereo recording directory
# /var/spool/asterisk/monitorP		# default Asterisk stereo parallel recording directory
# /var/spool/asterisk/monitorTRASH	# where the used parallel files are put
# /var/spool/asterisk/monitorDONE	# where the mixed -all files are put
# /var/spool/asterisk/monitor/ORIG	# where the original -in and -out files are put
# 
# This program assumes that recordings are saved by Asterisk as .wav
# 
# Copyright (C) 2025  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# 250909-0955 - First Build
#

$HTTPS=0;
$status_post_logs=0;
$delay_post_live=0;
$ignore_parallel=0;
$now_epoch = int(time());

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
		print "  [--ignore-parallel] = do not process parallel stereo recordings\n";
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
		print "  [--STATUS-POST-LOGS] = use the call logs for POST filename status variable, if found\n";
		print "  [--DELAY-POST-LIVE-CALLS] = delay processing of recordings using POST filename variables if any live calls/agents for lead\n";
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
		if ($args =~ /--ignore-parallel/)
			{
			$ignore_parallel=1;
			if ($q < 1) {print "\n----- IGNORING PARALLEL CALL RECORDINGS -----\n\n";}
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
			if ($args =~ /--STATUS-POST-LOGS/i)
				{
				$status_post_logs=1;
				if ($q < 1) {print "\n----- STATUS POST LOGS SET: $status_post_logs -----\n\n";}
				}
			if ($args =~ /--DELAY-POST-LIVE-CALLS/i)
				{
				$delay_post_live=1;
				if ($q < 1) {print "\n----- DELAY POST LIVE SET: $delay_post_live -----\n\n";}
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

##### Get the settings from system_settings #####
$SSmute_recordings=0;
$stmtA = "SELECT mute_recordings,stereo_recording,stereo_parallel_recording FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSmute_recordings =			$aryA[0];
	$SSstereo_recording =			$aryA[1];
	$SSstereo_parallel_recording =	$aryA[2];
	}
$sthA->finish();

##### Get the settings from servers #####
$vicidial_recording_limit=0;
$stmtA = "SELECT vicidial_recording_limit FROM servers where server_ip='$server_ip';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$vicidial_recording_limit =	$aryA[0];
	$start_epoch_test = ($now_epoch - ( ($vicidial_recording_limit * 2) * 60) );
	}
$sthA->finish();

# calculate server recording limit time, plus 60 minutes
($Rsec,$Rmin,$Rhour,$Rmday,$Rmon,$Ryear,$Rwday,$Ryday,$Risdst) = localtime(time() - (($vicidial_recording_limit + 60) * 60));
$Ryear = ($Ryear + 1900);
$Ryy = $Ryear; $Ryy =~ s/^..//gi;
$Rmon++;
if ($Rmon < 10) {$Rmon = "0$Rmon";}
if ($Rmday < 10) {$Rmday = "0$Rmday";}
if ($Rhour < 10) {$Rhour = "0$Rhour";}
if ($Rmin < 10) {$Rmin = "0$Rmin";}
if ($Rsec < 10) {$Rsec = "0$Rsec";}
$SQLdate_REC_limit="$Ryear-$Rmon-$Rmday $Rhour:$Rmin:$Rsec";

### find soxmix or sox to do the mixing
$soxbin = '';
if ( -e ('/usr/bin/sox')) {$soxbin = '/usr/bin/sox';}
else 
	{
	if ( -e ('/usr/local/bin/sox')) {$soxbin = '/usr/local/bin/sox';}
	else
		{
		print "Can't find sox binary! Exiting...\n";
		exit;
		}
	}

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

if ($SSstereo_recording < 1) 
	{
	if ($DB) {print "Stereo Call Recording is disabled on this system, exiting...   |$SSstereo_recording| \n";}
	exit;
	}

### directory where in/out recordings are saved to by Asterisk
$dir1 =	$PATHmonitor.'S';
$dir2 = "$PATHDONEmonitor";
$PATHmonitorP =	$PATHmonitor.'P';
$PATHmonitorTRASH =	$PATHmonitor.'TRASH';



####################################################
##### BEGIN parallel call recording processing #####
####################################################
if ( ($ignore_parallel < 1) && ($SSstereo_parallel_recording > 0) )
	{
	if (!-e "$PATHmonitorTRASH/one-sec-silence.wav") 
		{
		# generate 1 second silence file, to be used to create stereo files with only one side having audio
		`$soxbin -n -c 1 -r 8k -b 16 "$PATHmonitorTRASH/one-sec-silence.wav" synth 1 sine 0`;
		if($DBX){print "Created one-sec-silence.wav audio file. \n";}
		}

	opendir(pFILE, "$PATHmonitorP/");
	@pFILES = readdir(pFILE);

	### Loop through files first to gather filesizes
	$parallel_files_ct=0;
	$i=0;
	foreach(@pFILES)
		{
		$pFILEsize1[$i] = 0;
		if ( (length($pFILES[$i]) > 4) && (!-d "$PATHmonitorP/$pFILES[$i]") )
			{
			$parallel_files_ct++;
			$pFILEsize1[$i] = (-s "$PATHmonitorP/$pFILES[$i]");
			if ($DBX) {print "$parallel_files_ct $pFILES[$i] $pFILEsize1[$i]\n";}
			}
		$i++;
		}

	if ($parallel_files_ct < 1) 
		{
		if ($DBX) {print "No parallel files found to process: $parallel_files_ct \n";}
		}
	else
		{
		sleep(15);

		### Loop through files a second time to gather filesizes again 5 seconds later
		$i=0;
		$active_recordings=0;
		$delay_ct=0;
		$processed_ct=0;
		$stereo_rec_made=0;
		foreach(@pFILES)
			{
			$lead_id=0;
			$vicidial_id='';
			$pFILEsize2[$i] = 0;

			if ( (length($pFILES[$i]) > 4) && (!-d "$PATHmonitorP/$pFILES[$i]") )
				{
				$pFILEsize2[$i] = (-s "$PATHmonitorP/$pFILES[$i]");
				if ($DBX) {print "$pFILES[$i] $pFILEsize2[$i]\n\n";}

				if ( ($pFILES[$i] !~ /out\.wav|out\.gsm|lost\+found/i) && ($pFILEsize1[$i] eq $pFILEsize2[$i]) && (length($pFILES[$i]) > 4))
					{
					$process_recording=0;
					$INfile = $pFILES[$i];
					$OUTfile = $pFILES[$i];
					$OUTfile =~ s/-in\.wav/-out.wav/gi;
					$OUTfile =~ s/-in\.gsm/-out.gsm/gi;
					$ALLfile = $pFILES[$i];
					$ALLfile =~ s/-in\.wav/-all.wav/gi;
					$ALLfile =~ s/-in\.gsm/-all.gsm/gi;
					$SQLpFILE = $pFILES[$i];
					$SQLpFILE =~ s/-in\.wav|-in\.gsm//gi;
					$filenameSQL='';

					$length_in_sec=0;
					$rec_ended=0;
					$start_epoch=0;
					$stmtA = "SELECT parallel_recording_id,channel,length_in_sec,lead_id,vicidial_id,start_time,end_time,user,UNIX_TIMESTAMP(start_time),recording_status from recording_log_parallel where filename='$SQLpFILE' and server_ip='$server_ip' and start_time >= \"$SQLdate_REC_limit\" order by parallel_recording_id desc LIMIT 1;";
					if($DBX){print STDERR "\n|$stmtA|\n";}
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					$sthArows=$sthA->rows;
					if ($sthArows > 0)
						{
						@aryA = $sthA->fetchrow_array;
						$parallel_recording_id =	$aryA[0];
						$channel =					$aryA[1];
						$length_in_sec =			$aryA[2];
						$lead_id =					$aryA[3];
						$vicidial_id =				$aryA[4];
						$start_time =				$aryA[5];
						$end_time =					$aryA[6];
						$user =						$aryA[7];
						$start_epoch =				$aryA[8];
						$recording_status =			$aryA[9];
						if (length($end_time) > 15) {$rec_ended=1;}
						$process_recording=1;
						}
					$sthA->finish();

					# get the length of the file from soxi
					@soxi_output = `$soxibin -D $PATHmonitorP/$pFILES[$i]`;
					$soxi_sec = $soxi_output[0];
					$soxi_sec =~ s/\..*|\n|\r| //gi;

					### process the recording files
					if ($process_recording > 0) 
						{
						$stereo_rec_ids='Stereo Rec IDs:';
						if ($DB) {print "|$parallel_recording_id|$length_in_sec($soxi_sec)|$INfile| \n";}

						# Look for stereo recordings using this parallel_recording_id
						$stmtA = "SELECT filename,lead_id,recording_id,options,start_time,end_time,UNIX_TIMESTAMP(start_time),UNIX_TIMESTAMP(end_time) FROM recording_log_stereo where start_time >= \"$SQLdate_REC_limit\" and parallel_recording_id='$parallel_recording_id' and server_ip='$server_ip' order by recording_id limit 10;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArowsRECS=$sthA->rows;
						if ($DBX) {print "DEBUG: $sthArowsRECS|$stmtA|\n";}
						$prc=0;
						while ($sthArowsRECS > $prc)
							{
							@aryA = $sthA->fetchrow_array;
							$PR_filenameIN[$prc] =		$aryA[0]."-in.wav";
							$PR_filenameOUT[$prc] =		$aryA[0]."-out.wav";
							$PR_lead_id[$prc] =			$aryA[1];
							$PR_recording_id[$prc] =	$aryA[2];
							$PR_options[$prc] =			$aryA[3];
							$PR_start_time[$prc] =		$aryA[4];
							$PR_end_time[$prc] =		$aryA[5];
							$PR_start_epoch[$prc] =		$aryA[6];
							$PR_end_epoch[$prc] =		$aryA[7];
							if ( ($PR_end_epoch[$prc] =~ /^NULL$/i) || ($PR_end_epoch[$prc] < 1000) || (length($PR_end_epoch[$prc]) < 4) )
								{$PR_end_epoch[$prc] = ($start_epoch + $soxi_sec);}
							$prc++;
							}
						$sthA->finish();

						$prc=0;
						while ($sthArowsRECS > $prc)
							{
							$stereo_file_processed=0;
							if ($PR_options[$prc] =~ /AGENT-CONTROLLED/) 
								{
								$temp_start_test = ($PR_start_epoch[$prc] - $start_epoch);
								$temp_ac_length = ($PR_end_epoch[$prc] - $PR_start_epoch[$prc]);
								$temp_length_test = ($soxi_sec - $temp_ac_length);

								# if agent-controlled file is shorter than, or starts after parallel file, use sox to create shorter file matching start/stop
								if ( ($temp_start_test > 2) || ($temp_length_test > 4) )
									{
									if ($PR_options[$prc] =~ /CUSTOMER_ONLY/) 
										{
										# stereo recording is shorter than parallel recording, copy Left(-in/silence) and trim Right channels to stereo mixing directory
										$mix_commandA = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameIN[$prc]";
										$mix_commandB = "$soxbin \"$PATHmonitorP/$OUTfile\" \"$dir1/$PR_filenameOUT[$prc]\" trim $temp_start_test $temp_ac_length";
										$stereo_file_processed++;
										}
									if ($PR_options[$prc] =~ /CUSTOMER_MUTE/) 
										{
										# stereo recording is shorter than parallel recording, trim Left and copy Right(-out/silence) channels to stereo mixing directory
										$mix_commandA = "$soxbin \"$PATHmonitorP/$INfile\" \"$dir1/$PR_filenameIN[$prc]\" trim $temp_start_test $temp_ac_length";
										$mix_commandB = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameOUT[$prc]";
										$stereo_file_processed++;
										}
									if ( ($stereo_file_processed < 1) || ($PR_options[$prc] =~ /BOTH_CHANNELS/) )
										{
										# stereo recording is shorter than parallel recording, merge Left and Right channels into a single Stereo WAV audio file
										$mix_commandA = "$soxbin \"$PATHmonitorP/$INfile\" \"$dir1/$PR_filenameIN[$prc]\" trim $temp_start_test $temp_ac_length";
										$mix_commandB = "$soxbin \"$PATHmonitorP/$OUTfile\" \"$dir1/$PR_filenameOUT[$prc]\" trim $temp_start_test $temp_ac_length";
										$stereo_file_processed++;
										}
									}
								else
									{
									if ($PR_options[$prc] =~ /CUSTOMER_ONLY/) 
										{
										# stereo recording is same length as parallel recording, copy Left(-in/silence) and Right channels to stereo mixing directory
										$mix_commandA = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameIN[$prc]";
										$mix_commandB = "cp -f $PATHmonitorP/$OUTfile $dir1/$PR_filenameOUT[$prc]";
										$stereo_file_processed++;
										}
									if ($PR_options[$prc] =~ /CUSTOMER_MUTE/) 
										{
										# stereo recording is same length as parallel recording, copy Left and Right(-out/silence) channels to stereo mixing directory
										$mix_commandA = "cp -f $PATHmonitorP/$INfile $dir1/$PR_filenameIN[$prc]";
										$mix_commandB = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameOUT[$prc]";
										$stereo_file_processed++;
										}
									if ( ($stereo_file_processed < 1) || ($PR_options[$prc] =~ /BOTH_CHANNELS/) )
										{
										# stereo recording is same length as parallel recording, copy Left and Right channels to stereo mixing directory
										$mix_commandA = "cp -f $PATHmonitorP/$INfile $dir1/$PR_filenameIN[$prc]";
										$mix_commandB = "cp -f $PATHmonitorP/$OUTfile $dir1/$PR_filenameOUT[$prc]";
										$stereo_file_processed++;
										}
									}
								}

							if ( ($PR_options[$prc] =~ /CUSTOMER-ONLY/) && ($stereo_file_processed < 1) )
								{
								# stereo recording is same length as parallel recording, copy Left(-in/silence) and Right channels to stereo mixing directory
								$mix_commandA = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameIN[$prc]";
								$mix_commandB = "cp -f $PATHmonitorP/$OUTfile $dir1/$PR_filenameOUT[$prc]";
								$stereo_file_processed++;
								}
							if ( ($PR_options[$prc] =~ /CUSTOMER-MUTED/) && ($stereo_file_processed < 1) )
								{
								# stereo recording is same length as parallel recording, copy Left and Right(-out/silence) channels to stereo mixing directory
								$mix_commandA = "cp -f $PATHmonitorP/$INfile $dir1/$PR_filenameIN[$prc]";
								$mix_commandB = "cp -f $PATHmonitorTRASH/one-sec-silence.wav $dir1/$PR_filenameOUT[$prc]";
								$stereo_file_processed++;
								}
							if ( ($PR_options[$prc] =~ /FULL-RECORDING/) && ($stereo_file_processed < 1) )
								{
								# stereo recording is same length as parallel recording, copy Left and Right channels to stereo mixing directory
								$mix_commandA = "cp -f $PATHmonitorP/$INfile $dir1/$PR_filenameIN[$prc]";
								$mix_commandB = "cp -f $PATHmonitorP/$OUTfile $dir1/$PR_filenameOUT[$prc]";
								$stereo_file_processed++;
								}

							if ($stereo_file_processed > 0) 
								{
								`$mix_commandA`;
								`$mix_commandB`;
								$stereo_rec_made++;
								$stereo_rec_ids .= " $PR_recording_id[$prc]";
								if($DBX){print "Copy commands run: |$stereo_rec_made|$prc|$mix_commandA|$mix_commandB|\n";}
								}
							else
								{
								if($DBX){print "ERROR, file cannot be processed: |$prc|$parallel_recording_id|$PR_recording_id[$prc]|$PR_filename[$prc]|\n";}
								}

							$prc++;
							}

						if (!$T)
							{
							`mv -f "$PATHmonitorP/$INfile" "$PATHmonitorTRASH/$INfile"`;
							`mv -f "$PATHmonitorP/$OUTfile" "$PATHmonitorTRASH/$OUTfile"`;
							}

						$lengthSQL='';
						if ( ( ($length_in_sec < 1) || ($length_in_sec =~ /^NULL$/i) || (length($length_in_sec)<1) ) && (length($soxibin) > 3) )
							{
							$lengthSQL = ",length_in_sec='$soxi_sec'";
							}

						$stmtA = "UPDATE recording_log_parallel set recording_status='PROCESSED', processing_log=CONCAT(processing_log,' $stereo_rec_ids') $lengthSQL where parallel_recording_id='$parallel_recording_id';";
							if($DBX){print STDERR "\n|$stmtA|\n";}
						$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

						### sleep for twenty hundredths of a second to not flood the server with disk activity
						usleep(1*200*1000);

						$processed_ct++;
						}
					}
				else
					{$active_recordings++;}
				}
			$i++;
			}

		if($DBX)
			{
			$end_epoch = int(time());
			$run_length = ($end_epoch - $now_epoch);

			print "\nPARALLEL RECORDING Debug output:\n";
			print "Total parallel files:      $i \n";
			print "     Active recordings:    $active_recordings \n";
			print "     Processed files:      $processed_ct \n";
			print "     Stereo files made:    $stereo_rec_made \n";
			print "\n";
			print "Run time: $run_length seconds \n";
			}
		}
	}
##################################################
##### END parallel call recording processing #####
##################################################





##################################################
##### BEGIN stereo call recording processing #####
##################################################

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

sleep(15);


### Loop through files a second time to gather filesizes again 5 seconds later
$i=0;
$active_recordings=0;
$delay_ct=0;
$processed_ct=0;
$post_status_change_ct=0;
foreach(@FILES)
	{
	$lead_id=0;
	$vicidial_id='';
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
			$rec_ended=0;
			$start_epoch=0;
			$stmtA = "SELECT recording_id,length_in_sec,lead_id,vicidial_id,start_time,end_time,user,UNIX_TIMESTAMP(start_time) from recording_log where filename='$SQLFILE' order by recording_id desc LIMIT 1;";
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
				$start_time =		$aryA[4];
				$end_time =			$aryA[5];
				$user =				$aryA[6];
				$start_epoch =		$aryA[7];
				if (length($end_time) > 15) {$rec_ended=1;}
				}
			$sthA->finish();

			$process_recording=1;
			### this is where code for agent-muted recordings went, if found then delay processing(not available for stereo call recordings)

			### check for POST variables, and delay setting
			if ( ($delay_post_live > 0) && ($POST > 0) && ($ALLfile =~ /POSTVLC|POSTSP|POSTADDR3|POSTSTATUS/) )
				{
				if ($lead_id > 0) 
					{
					$VLA_count=0;
					$VAC_count=0;
					if ( ($start_epoch > 0) && ($start_epoch < $start_epoch_test) ) 
						{
						if ($DBX > 0) {print "DEBUG6: delay processing past server limit, process now: ($start_epoch|$start_epoch_test) |$SQLFILE|ended: $rec_ended|\n";}
						}
					else
						{
						if ($DBX > 0) {print "DEBUG7: delay processing not past server limit yet: ($start_epoch|$start_epoch_test) |$SQLFILE|ended: $rec_ended|\n";}

						### check if any live agents are connected to this lead_id right now
						$stmtA = "SELECT count(*) from vicidial_live_agents where lead_id='$lead_id';";
						if($DBX){print STDERR "\n|$stmtA|\n";}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VLA_count = $aryA[0];
							}
						$sthA->finish();

						### check if any live calls are tied to this lead_id right now
						$stmtA = "SELECT count(*) from vicidial_auto_calls where lead_id='$lead_id';";
						if($DBX){print STDERR "\n|$stmtA|\n";}
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						$sthArows=$sthA->rows;
						if ($sthArows > 0)
							{
							@aryA = $sthA->fetchrow_array;
							$VAC_count = $aryA[0];
							}
						$sthA->finish();
						}

					if ( ($VLA_count > 0) || ($VAC_count > 0) ) 
						{
						if ($DBX > 0) {print "DEBUG4: delay processing active, do not process: ($VLA_count|$VAC_count) |$SQLFILE|ended: $rec_ended|\n";}
						$process_recording=0;
						$delay_ct++;
						}
					else
						{
						if ($DBX > 0) {print "DEBUG5: delay processing - no calls or agents found, OK to process: ($VLA_count|$VAC_count) |$SQLFILE|ended: $rec_ended|\n";}
						}
					}
				}

			### process the recording files
			if ($process_recording > 0) 
				{
				if ($DB) {print "|$recording_id|$length_in_sec|$INfile|     |$ALLfile|\n";}

				# merge Left and Right channels into a single Stereo WAV audio file
				`$soxbin -M "$dir1/$INfile" "$dir1/$OUTfile" "$dir2/$ALLfile"`;

				if (!$T)
					{
					`mv -f "$dir1/$INfile" "$dir2/ORIG/$INfile"`;
					`mv -f "$dir1/$OUTfile" "$dir2/ORIG/$OUTfile"`;
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

							if ( ($status_post_logs > 0) && ($ALLfile =~ /POSTSTATUS/) )
								{
								$log_status = $status;
								if ($vicidial_id =~ /\./) 
									{
									$log_lookupSQL = "SELECT status from vicidial_log where uniqueid='$vicidial_id' and lead_id='$lead_id' and user='$user' order by call_date desc limit 1;";
									}
								else
									{
									$log_lookupSQL = "SELECT status from vicidial_closer_log where closecallid='$vicidial_id' and lead_id='$lead_id' and user='$user' order by call_date desc limit 1;";
									}
								if($DBX){print STDERR "\n|$log_lookupSQL|\n";}
								$sthA = $dbhA->prepare($log_lookupSQL) or die "preparing: ",$dbhA->errstr;
								$sthA->execute or die "executing: $log_lookupSQL ", $dbhA->errstr;
								$sthArows=$sthA->rows;
								if ($sthArows > 0)
									{
									@aryA = $sthA->fetchrow_array;
									$log_status =		$aryA[0];
									}
								
								if ($log_status ne $status) 
									{$post_status_change_ct++;}
								if ($DB) {print "    POST processing Log status override: lead: |$status| log: |$log_status|   $post_status_change_ct \n";}
								$status = $log_status;
								}
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

				$stmtA = "UPDATE recording_live set end_time=NOW(),recording_status='FINISHED FILE-MERGE' where recording_id='$recording_id' and recording_status='STARTED';";
					if($DBX){print STDERR "\n|$stmtA|\n";}
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";

				### sleep for twenty hundredths of a second to not flood the server with disk activity
				usleep(1*200*1000);

				$processed_ct++;
				}
			}
		else
			{$active_recordings++;}
		}
	$i++;
	}

if($DBX)
	{
	$end_epoch = int(time());
	$run_length = ($end_epoch - $now_epoch);

	print "\nDebug output:\n";
	print "Total files:               $i \n";
	print "     Active recordings:    $active_recordings \n";
	print "     Delayed processing:   $delay_ct \n";
	print "     Processed files:      $processed_ct \n";
	print "     POST log status diff: $post_status_change_ct \n";
	print "\n";
	print "Run time: $run_length seconds \n";
	}

################################################
##### END stereo call recording processing #####
################################################


if ($DB) {print "DONE... EXITING\n\n";}

$sthA->finish();
$dbhA->disconnect();


exit;
