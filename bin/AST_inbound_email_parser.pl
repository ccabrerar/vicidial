#!/usr/bin/perl
#use strict;
# use warnings;
use HTML::Entities;
use HTML::Strip;
use Switch;
use Time::Local;
use MIME::Decoder;
use Encode qw(from_to decode encode);
use MIME::Base64;
use MIME::QuotedPrint;

# Copyright (C) 2017  Matt Florell, Joe Johnson <vicidial@gmail.com>    LICENSE: AGPLv2
# 
# AST_inbound_email_parser.pl - This script is essential for periodically checking any active POP3 or IMAP 
# email accounts set up through the Vicidial admin.
# 
# You cannot check an email account more often than 5 minutes at a time.  This is in place because several 
# email providers will lock an email account if it is being checked too often; currently the most restrictive
# email provider encountered allows no more than three logins every 15 minutes, hence 5 minutes being the
# most frequent time allowed.
# 
# Upon execution, this script will query the vicidial_email_accounts table in the asterisk database and 
# gather the information for all email accounts set up through the dialer that are currently active.  It
# will then check the time setting on each of those accounts to see if it is time to check that particular 
# account.  If so, the program then will attempt to access the account using the appropriate Perl module
# depending on the account protocol (POP3 or IMAP).  If the program successfully connects to the email 
# account, then it will download any unread email messages, and grab any important information in the 
# email headers, along with any attachments and the message itself.  Each bit of information will be 
# stored in the vicidial_email_list table, and any attachments will be stored in the 
# inbound_email_attachments table as BLOB data.
# 
# Additionally, depending on the email account settings, prior to putting the email record into the 
# vicidial_email_list table, the accounts can be set up to check if the "from" address is already part 
# of a lead in the vicidial_list table.  The accounts can be set up to not check at all (EMAIL), in 
# which all email messages go in as new leads, first in the vicidial_list table and then in the 
# vicidial_email_list table with the lead_id gleaned from the vicidial_list insert.  They can also be
# set up to check if the email address is already on a lead in a particular list (EMAILLOOKUPRL), or
# if the email address is in a lead belonging to any list in a given campaign (EMAILLOOKUPRC), or if 
# the email address is on any lead currently in the vicidial_list table (EMAILLOOKUP).  In these 
# cases, the script will grab the lead_id of the most recently loaded lead (i.e. highest lead_id value), 
# and use that as the lead_id in the vicidial_email_list table without creating a new lead.
# 
# After checking and downloading any unread messages, the script will then log off the email account, 
# and when all active email accounts have been checked in this manner, the script will exit.
#
# This script should be set up in the cron to run once per minute, not continuously.
# * * * * * /usr/share/astguiclient/AST_inbound_email_parser.pl
#
# Use the --debug variable when executing the script to have it print out what it is attempting to do on a
# step-by-step basis.  Use the --debugX variable to include outputting of mail information and SQL queries the script 
# attempts to execute.
#
# changes:
# 121213-2200 - First Build
# 130127-0025 - Added better non-latin characters support
# 130607-0155 - Encoding fix
# 140212-0719 - Added ignoresizeerrors option, changed debug and added --force-check option
# 140225-1241 - Added option for SSL no-cert-verify (--ssl-no-cert)
# 140313-0905 - Added Debug options when --debugX for both IMAP and POP3
# 140422-1912 - Added 'related' content type
# 150513-2310 - Added pop3_auth_mode
# 151030-0557 - Small change to catch more attachments properly
# 160120-2149 - Added missing entry_date field on vicidial_list insert
# 160414-0930 - Added missing phone_code field on vicidial_list insert
# 161014-2200 - Bug patch for "&nbsp;" string in email message
# 170523-1319 - file attachment patch, issue #1014
# 171026-0106 - Added optional queue_log logging
#

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';
$|=1;
$force_check=0;
$ssl_no_cert=0;
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
		print "AST_inbound_email_parser.pl - This script is essential for periodically checking any active POP3 or IMAP \n";
		print "email accounts set up through the Vicidial admin.\n";
		print "\n";
		print "You cannot check an email account more often than 5 minutes at a time.  This is in place because several \n";
		print "email providers will lock an email account if it is being checked too often; currently the most restrictive\n";
		print "email provider encountered allows no more than three logins every 15 minutes, hence 5 minutes being the\n";
		print "most frequent time allowed.\n";
		print "\n";
		print "Upon execution, this script will query the vicidial_email_accounts table in the asterisk database and \n";
		print "gather the information for all email accounts set up through the dialer that are currently active.  It\n";
		print "will then check the time setting on each of those accounts to see if it is time to check that particular \n";
		print "account.  If so, the program then will attempt to access the account using the appropriate Perl module\n";
		print "depending on the account protocol (POP3 or IMAP).  If the program successfully connects to the email \n";
		print "account, then it will download any unread email messages, and grab any important information in the \n";
		print "email headers, along with any attachments and the message itself.  Each bit of information will be \n";
		print "stored in the vicidial_email_list table, and any attachments will be stored in the \n";
		print "inbound_email_attachments table as BLOB data.\n";
		print "\n";
		print "Additionally, depending on the email account settings, prior to putting the email record into the \n";
		print "vicidial_email_list table, the accounts can be set up to check if the 'from' address is already part \n";
		print "of a lead in the vicidial_list table.  The accounts can be set up to not check at all (EMAIL), in \n";
		print "which all email messages go in as new leads, first in the vicidial_list table and then in the \n";
		print "vicidial_email_list table with the lead_id gleaned from the vicidial_list insert.  They can also be\n";
		print "set up to check if the email address is already on a lead in a particular list (EMAILLOOKUPRL), or\n";
		print "if the email address is in a lead belonging to any list in a given campaign (EMAILLOOKUPRC), or if \n";
		print "the email address is on any lead currently in the vicidial_list table (EMAILLOOKUP).  In these \n";
		print "cases, the script will grab the lead_id of the most recently loaded lead (i.e. highest lead_id value), \n";
		print "and use that as the lead_id in the vicidial_email_list table without creating a new lead.\n";
		print "\n";
		print "After checking and downloading any unread messages, the script will then log off the email account, \n";
		print "and when all active email accounts have been checked in this manner, the script will exit.\n";
		print "\n";
		print "This script should be set up in the cron to run once per minute, not continuously.\n";
		print "* * * * * /usr/share/astguiclient/AST_inbound_email_parser.pl\n";
		print "\n";
		print "Use the --debug variable when executing the script to have it print out what it is attempting to do on a\n";
		print "step-by-step basis.  Use the --debugX variable to include outputting of mail information and SQL queries the script \n";
		print "attempts to execute.\n";
		print "\n";
		print "other allowed run time options:\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--force-check] = forces check of email\n";
		print "  [--ssl-no-cert] = ignores ssl cert verification\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			}
		if ($args =~ /--force-check/i)
			{
			$force_check=1;
			}
		if ($args =~ /--ssl-no-cert/i)
			{
			$ssl_no_cert=1;
			}
		}
	}
else
	{
#	print "no command line options set\n";
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

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhA2 = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhA3 = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
if($min==0) {$min=60;}
$minutes=($hour*60)+$min;
# $minutes=0; # Uncomment if you want to TEST ONLY, so you can test this at any time.

##### Get the settings from system_settings #####
$stmtA = "SELECT allow_emails,default_phone_code,enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_eq_prepend FROM system_settings;";
#	print "$stmtA\n";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSallow_emails =				$aryA[0];
	$SSdefault_phone_code =			$aryA[1];
	$enable_queuemetrics_logging =	$aryA[2];
	$queuemetrics_server_ip	=		$aryA[3];
	$queuemetrics_dbname =			$aryA[4];
	$queuemetrics_login=			$aryA[5];
	$queuemetrics_pass =			$aryA[6];
	$queuemetrics_log_id =			$aryA[7];
	$queuemetrics_eq_prepend =		$aryA[8];
	}
$sthA->finish();

##### exit program if system setting for allow email is not enabled
if ( ($SSallow_emails < 1) && ($force_check < 1) )
	{
	if ($DB) {print "SYSTEM SETTING for allow_email is disabled, exiting...  |$SSallow_emails|$force_check|\n";}
	exit;
	}


$stmt="SELECT email_account_id,email_account_name,email_account_description,user_group,protocol,email_replyto_address,email_account_server,email_account_user,email_account_pass,pop3_auth_mode,active,email_frequency_check_mins,group_id,default_list_id,call_handle_method,agent_search_method,campaign_id,list_id,email_account_type from vicidial_email_accounts where active='Y'";
$rslt=$dbhA->prepare($stmt);
$rslt->execute();

$h=0;
while (@row=$rslt->fetchrow_array) {
	$h++;
	$VARemail_ID=$row[0];
	$VARemail_protocol=$row[4];
	$VARemail_server=$row[6];
	$VARemail_user=$row[7];
	$VARemail_pwd=$row[8];
	$VARpop3_auth_mode=$row[9];
	$VARemail_frequency=$row[11];
	$VARemail_groupid=$row[12];
	$default_list_id=$row[13];
	$call_handle_method=$row[14];
	$agent_search_method=$row[15];
	$campaign_id=$row[16];
	$list_id=$row[17];

	if ($DBX) {print "$h - $VARemail_ID - $VARemail_groupid\n";}

	if ( ($minutes%$VARemail_frequency==0) || ($force_check > 0) ) 
		{
		if ($DB) {print "Attempting to connect to $VARemail_protocol server ($VARemail_server)  -  $h - $VARemail_ID - $VARemail_groupid\n\n";}
		if ($VARemail_protocol eq "IMAP") {
			# if (!$sleep_time || $sleep_time<10) {$sleep_time=10;} # Ten second minimum wait time for IMAP.

			# Connect to IMAP server
			use Mail::IMAPClient;
			use Mail::Message;
			use IO::Socket::SSL;

			if ($ssl_no_cert > 0)
				{
				%args = (
				  User     => "$VARemail_user",
				  Password => "$VARemail_pwd",
				  Ignoresizeerrors => 1,
				  Debug => "$DBX",
				  Socket   => IO::Socket::SSL->new
					(	Proto    => 'tcp',
						PeerAddr => "$VARemail_server",
						PeerPort => 993, # IMAP over SSL standard port
						SSL_verify_mode => 'SSL_VERIFY_NONE',
					),
				 );
				}
			else
				{
				%args = (
				  Server   => "$VARemail_server",
				  User     => "$VARemail_user",
				  Password => "$VARemail_pwd",
				  Port     => 993,
				  Ssl      =>  1,
				  Ignoresizeerrors => 1,
				  Debug => "$DBX",
				  );
				}

				my $client = Mail::IMAPClient->new(%args)
				  or die "Cannot connect through IMAPClient: ($VARemail_ID|$VARemail_server|$VARemail_user|ssl-no-cert:$ssl_no_cert) $@ $!";
			# Include options for folders in IMAP?  POP3 doesn't support this.

			# List folders on remote server (see if all is ok)
			if ( $client->IsAuthenticated() ) {
				#print "Folders:\n";
				$new_messages=0;
				my @folder_array=$client->folders("INBOX");
				for (my $i=0; $i<scalar(@folder_array); $i++) 
					{
					#print "- ".$folder_array[$i]."\n" ;  
					my $msgcount = $client->message_count($folder_array[$i]);
					#print "+---+ Messages: ".$msgcount."\n";
					if ($msgcount>0) {
						$file=open(STORAGE, "> ./raw_emails.txt");
						$client->select($folder_array[$i]);
						my @msgs = $client->messages or die "Could not messages: $@\n";
						my @unseenMsgs = $client->unseen;
						for(my $j=0; $j<scalar(@msgs); $j++) {
							if (grep {$_ eq $msgs[$j]} @unseenMsgs) {
								$new_messages++;

								my $string = $client->message_string($msgs[$j]) or die "Could not message_string: $@\n";
								$client->message_to_file($file,$msgs[$j]) or die "Could not message_to_file: $@\n";

								my $hashref = $client->parse_headers($msgs[$j], 'ALL');
								my %email_values=%{$hashref};
								my $email_to=$email_values{"To"}->[0];
								my $email_from=$email_values{"From"}->[0];
								my $email_date=$email_values{"Date"}->[0];
								my $subject=$email_values{"Subject"}->[0];
								my $mime_type=$email_values{"MIME-Version"}->[0];
								my $content_type=$email_values{"Content-Type"}->[0];
								my $content_transfer_encoding=$email_values{"Content-Transfer-Encoding"}->[0];
								my $x_mailer=$email_values{"X-Mailer"}->[0];
								my $auth_results=$email_values{"Authentication-Results"}->[0];
								my $spf=$email_values{"Received-SPF"}->[0];
								$message = $client->body_string($msgs[$j]);
								$charset="";

								$content_type=~/charset\=\"?(KOI8\-R|ISO\-8859\-[0-9]+|windows\-12[0-9]+|utf\-8|us\-ascii)\"?/i;
								$charset=$&;
								$charset=substr($charset, 8);
								$charset=~s/\"//gi;

								$text_written=0;  ## Keeps track of whether or not text of email was grabbed
								$attach_ct=0; ## Keeps number
								@ins_values=();
								@output_ins_values=();

								if ($email_date=~/[0-9]{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+[0-9]{4}\s+[0-9]{1,2}\:[0-9]{1,2}(\:[0-9]{1,2})?/) {
									$email_date=$&;
									$email_date=~s/\s+/ /gi;
									@date_array=split(/\s/, $email_date);
									$day=substr("0".$date_array[0], -2);
									$month=$date_array[1];
									$year=$date_array[2];
									@time_array=split(/\:/, $date_array[3]);
									$hour=substr("00".$time_array[0], -2);
									$min=substr("00".$time_array[1], -2);
									$sec=substr("00".$time_array[2], -2);
									$email_date="$day $month $year $hour:$min:$sec";
									if ($DB) {print "Time stamp on email is $email_date\n";}
								} elsif ($DB) {print "WARNING: Time stamp on email is $email_date\n";}

	############# MESSAGE ACTIONS
								if ($content_type=~/^text\/plain/i) {
									## Do nothing - it's plain text and needs no further work on it.
									if ($DB) {print "Email message is text/plain.  Nothing needs to be done.\n\n";}
								} elsif ($content_type=~/^text\/html/i) {
									## Message is HTML, so it needs to be stripped.
									if ($DB) {print "Email message is text/html.  Needs to have tags stripped via HTML::Strip.\n\n";}
									StripHTML();
								} elsif ($content_type=~/^multipart\/(alternative|mixed|related)/i) {
									## Message is multipart/alternative, so it needs to be read and partitioned.  The multipart/alternative subtype indicates that each part is an "alternative"
									## version of the same (or similar) content, each in a different format denoted by its "Content-Type" header. The formats are ordered by how faithful they are to 
									## the original, with the least faithful first and the most faithful last. Systems can then choose the "best" representation they are capable of processing; in 
									## general, this will be the last part that the system can understand, although other factors may affect this.
									if ($DB) {print "Email message is multipart.  Need to select the best format type and parse it.\n";}

									## First, get the boundary from the content-type and use it to break the email into it's multiple parts
									if ($content_type=~/boundary\=\"?[^\"]+\"?/i) {
										$boundary=$&;

										$boundary=~s/(^boundary=\"?|\"?$)//gi;
										$boundary="--".$boundary;
										@alternatives_array=split(/$boundary/, $message);

										for ($k=1; $k<(scalar(@alternatives_array)-1); $k++) { # Ignore the first and last entry in the array; it's just a blank entry and the two dashes after the last boundary.
											## Grab the message by using Mail::Message to strip header information
											$alt_email_text=$alternatives_array[$k];
											$alt_email_text=~s/(^(\s|\r|\n)+|(\s|\r|\n)+$)//;  # This needs to be trimmed so the Mail::Message->read command will strip out the headers - leading carriage returns ruin it

											# Reset certain variables;
											$sub_content_disposition="";
											$sub_content_type="";
											$attachment_fulltype="";
											$attachment_filename="";
											$attachment_type="";

											if ($alt_email_text=~/Content\-Type\:\s+(.*?)\n/i) {$sub_content_type=$&;} 
											if ($alt_email_text=~/Content\-Disposition\:\s+(.*?)\n/i) {$sub_content_disposition=$&;}
											if ($alt_email_text=~/Content\-Transfer\-Encoding\:\s+(.*?)\n/i) {
												$encoding_type=$&;
												$encoding_type=~s/(^Content-Transfer-Encoding\:\s+|\;$)//gi;
												$encoding_type=~s/[\r\n]//g;
											}
											$sub_content_type=~s/[\r\n]//g;
											$sub_content_disposition=~s/[\r\n]//g;
											
											my $msg = Mail::Message->read($alt_email_text);
											$body_contents=$msg->body;
											$attachment_filesize=$msg->size();
											$head=$msg->head;
											if ($DBX) {print "Part content disposition is $sub_content_disposition\n Part content type is $sub_content_type.\n";}
											if ($DBX) {print "Part content size is $attachment_filesize\n";}


											## Check if the content-type is plain or html
											if ($alt_email_text=~/Content\-Type\:\s+text\/html/i && $text_written==0) {
												$message=$body_contents;
												$content_transfer_encoding=$encoding_type;
												if ($DB) {print "First acceptable content-type match is text/html.  Stripping headers and stripping tags from the body/message...\n";}
												if ($DBX) {print "$k)\n***Pre-HTML strip:\n$message\n***\n";}
												StripHTML();
												if ($DBX) {print "$k)\n***Post-HTML strip:\n$message\n***\n";}
												$text_written=1;
											} elsif ($alt_email_text=~/Content\-Type\:\s+text\/plain/i && $text_written==0) {
												if ($DB) {print "First acceptable content-type match is text/plain.  Stripping headers to get text...\n";}
												$text_written=1;
												$message=$body_contents;
												$content_transfer_encoding=$encoding_type;
											}

											## Do a second check for a charset in the message, just in case.
											$sub_content_type=~/charset\=\"?(KOI8\-R|ISO\-8859\-[0-9]+|windows\-12[0-9]+|utf\-8|us\-ascii)\"?/i;
											$charset=$&;
											$charset=substr($charset, 8);
											$charset=~s/\"//gi;

											## Check for attachments
											if ($sub_content_disposition=~/attachment/) {
												$attachment_fulltype="";
												$attachment_filename="";

												## Check to see if attachment is an accepted filetype
												$sub_content_type=~/Content-Type\:\s+[^\;]+(\;.*)?$/i; $attachment_type=$&;
												$attachment_type=~s/(^Content-Type\:\s+|(\;.*)?$)//gi;
												$attachment_type=~s/[\r\n]//g;

												switch ($attachment_type) {
													case "application/pdf" {$attachment_fulltype="PDF";}
													case "image/jpeg" {$attachment_fulltype="JPG";}
													case "image/jpg" {$attachment_fulltype="JPG";}
													case "image/gif" {$attachment_fulltype="GIF";}
													case "image/x-png" {$attachment_fulltype="PNG";}
													case "image/png" {$attachment_fulltype="PNG";}
													case "image/bmp" {$attachment_fulltype="BMP";}
													case "image/x-ms-bmp" {$attachment_fulltype="BMP";}
													case "application/msword" {$attachment_fulltype="DOC";}
													case "application/rtf" {$attachment_fulltype="RTF";}
													case "application/vnd.ms-powerpoint" {$attachment_fulltype="PPT";}
													case "application/vnd.ms-excel" {$attachment_fulltype="XLS";}
													case "application/x-msexcel" {$attachment_fulltype="XLS";}
													case "application/ms-excel" {$attachment_fulltype="XLS";}
													case "application/zip" {$attachment_fulltype="ZIP";}
													case "text/csv" {$attachment_fulltype="CSV";}
													case "text/plain" {$attachment_fulltype="TXT";}
													case "application/vnd.oasis.opendocument.text" {$attachment_fulltype="ODT";}
													case "application/vnd.oasis.opendocument.spreadsheet" {$attachment_fulltype="ODS";}
												}
												if ($sub_content_disposition=~/filename\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
												if (length($attachment_filename)==0) {
													if ($DB) {print "Couldn't find file name with content-disposition.  Searching full header for 'filename' value...\n";}
													if ($alt_email_text=~/filename\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
													if (length($attachment_filename)==0) {
														if ($DB) {print "Couldn't find file name anywhere in header.  Third test - searching Content-Type for 'name' value...\n";}
														if ($sub_content_type=~/name\=\"?(.*?)\"?$|name=\"[^\"]+\"/i) {$attachment_filename=$&;}
														if (length($attachment_filename)==0) {
															if ($DB) {print "Couldn't find file 'name' anywhere in Content-Type.  Last test - searching full header for 'name' value...\n";}
															if ($alt_email_text=~/name\=\"?(.*?)\"?$|name=\"[^\"]+\"/i) {$attachment_filename=$&;}
														}
													}
												}
												$attachment_filename=~s/(file)?name\=\"?|\"?$//gi;


												# If the attachment check is good, then proceed with storing the attachment.  Otherwise continue.
												if ($attachment_fulltype && $attachment_fulltype ne "") {
													if ($attachment_filename && $attachment_filename ne "") {
														if ($DB) {print "Found valid attachment, type $attachment_type \n Attachment filename is $attachment_filename ($attachment_fulltype)\nFile can be stored in database....\n";}
														$ins_values[$attach_ct]="'$attachment_filename','$attachment_type','$encoding_type','$attachment_filesize','$attachment_fulltype','$body_contents'";
														$output_ins_values[$attach_ct]="'$attachment_filename','$attachment_type','$encoding_type','$attachment_filesize','$attachment_fulltype','<FILE CONTENTS>'";
														$attach_ct++;
													} else {
														if ($DB) {print "!!!!WARNING - Found valid attachment, type $attachment_type \n Attachment does not have file name.  Skipping...\n";}
													}
												} else {
													if ($DB) {print "!!!!WARNING - Found attachment $attachment_filename, but is NOT a valid file type \n Attachment type is $attachment_type.  Skipping...\n";}
												}
											}
											# print "\n";
										}
									} else {
										if ($DB) {print "!!!!WARNING - Mail is multi-part, but no boundary value was found.  Email will be ignored.\n";}
									}
								}
								#print "\n";

								# Do a clean-and-covert on the message, to decode base64 messages and also to UTF8 decode quoted printable text, in order to pick up special characters
								if ($content_transfer_encoding eq "base64") {
									$message=decode_base64($message);
									if ($charset ne "") {
										# decode to Perl's internal format
										$message=decode($charset, $message);
										#encodetoUTF-8
										$message=encode('utf-8', $message);
										$content_type.="; charset=$charset";
									}
								} elsif ($content_transfer_encoding eq "quoted-printable") {
									$message=decode_qp($message);

									if ($charset ne "") {
										# decode to Perl's internal format
										if ($charset != 'Type: text/html')
											{
											$message=decode($charset, $message);
											#encodetoUTF-8
											$message=encode('utf-8', $message);
											$content_type.="; charset=$charset";
											}
									}
								}

								$message=~s/(\"|\||\'|\;)/\\$&/g;
								$email_to=~s/(\"|\||\'|\;)/\\$&/g;
								$email_from=~s/(\"|\||\'|\;)/\\$&/g;								
								$subject=~s/(\"|\||\'|\;)/\\$&/g;
								$mime_type=~s/(\"|\||\'|\;)/\\$&/g;
								$content_type=~s/(\"|\||\'|\;)/\\$&/g;
								$content_transfer_encoding=~s/(\"|\||\'|\;)/\\$&/g;
								$x_mailer=~s/(\"|\||\'|\;)/\\$&/g;
								$sender_ip=~s/(\"|\||\'|\;)/\\$&/g;
								$message=~s/\s{2,}/ /gi;
								my $status="NEW";

								### Parses the actual email address from the "Email From:" value in order to run it against vicidial_list
								### Not sure how accurate this is
								$email_from_name=$email_from;
								$email_from_name=~s/\<?([^\s\@])+\@(([^\s\@\.])+\.)+[a-zA-Z]{2,}\>?//gi;
								$email_from_name=~s/^\s*(.*?)\s*$/$1/;

								$email_from=~/\<?([^\s\@])+\@(([^\s\@\.])+\.)+[a-zA-Z]{2,}\>?/i;
								$email_from=$&;
								$email_from=~s/(^\<)|(\>$)//gi;

								$auth_results=~/([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])){3}/i;
								my $sender_ip=$&;
								$spf=~/([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])){3}/i;
								my $sender_ip2=$&;
								if (length($sender_ip)==0) {$sender_ip=$sender_ip2;}

								$limit=1;
								$call_handle_clause="";
								if ($call_handle_method eq "EMAILLOOKUP") {
									$call_handle_clause="where email='$email_from' order by lead_id desc";
								} elsif ($call_handle_method eq "EMAILLOOKUPRL") {
									$call_handle_clause="where email='$email_from' and list_id='$list_id' order by lead_id desc";
								} elsif ($call_handle_method eq "EMAILLOOKUPRC") {
									my $list_id_stmt="select list_id from vicidial_lists where campaign_id='$campaign_id'";
									my $list_id_rslt=$dbhA->prepare($list_id_stmt);
									$list_id_rslt->execute();
									$list_id_str="";
									while (@list_id_row=$list_id_rslt->fetchrow_array) {
										$list_id_str.="$list_id_row[0],";
									}
									$list_id_str=substr($list_id_str, 0, -1);
									$call_handle_clause="where email='$email_from' and list_id in ($list_id_str) order by lead_id desc";
								} else {
									$limit=0;
								}

								### CHECK if lead exists in vicidial_list table via a search by email based on the email account settings
								my $vicidial_lead_check_stmt="select lead_id, list_id from vicidial_list $call_handle_clause limit $limit";
								if ($DBX) {print $vicidial_lead_check_stmt;}
								my $vicidial_lead_check_rslt=$dbhA->prepare($vicidial_lead_check_stmt);
								$vicidial_lead_check_rslt->execute();
								if ($vicidial_lead_check_rslt->rows>0) {
									my @lead_id_row=$vicidial_lead_check_rslt->fetchrow_array;
									$lead_id=$lead_id_row[0];
								} else {
									my $vicidial_list_stmt="insert into vicidial_list(list_id, email, comments, status, entry_date, phone_code) values('$default_list_id', '$email_from', '".substr($message,0,255)."', '$status', NOW(), '$SSdefault_phone_code')";
									if ($DBX) {print $vicidial_list_stmt;}
									my $vicidial_list_rslt=$dbhA->prepare($vicidial_list_stmt);
									if ($vicidial_list_rslt->execute()) {
										$lead_id=$dbhA->last_insert_id(undef, undef, 'vicidial_list', 'lead_id');
									} else {
										die "Vicidial list insert failed.  Check SQL in:\n $vicidial_list_stmt\n";
									}
								}

								## Insert a new record into vicidial_email_list.  This is ALWAYS done for new email messages.
								$ins_stmt="insert into vicidial_email_list(lead_id, protocol, email_date, email_to, email_from, email_from_name, subject, mime_type, content_type, content_transfer_encoding, x_mailer, sender_ip, message, email_account_id, group_id, status, direction) values('$lead_id', 'IMAP', STR_TO_DATE('$email_date', '%d %b %Y %T'), '$email_to', '$email_from', '$email_from_name', '$subject', '$mime_type', '$content_type', '$content_transfer_encoding', '$x_mailer', '$sender_ip', trim('$message'), '$VARemail_ID', '$VARemail_groupid', '$status', 'INBOUND')";

								if ($DB) {print $ins_stmt."\n";}

								if ($DBX) {print $ins_stmt."\n";}
								my $ins_rslt=$dbhA->prepare($ins_stmt);
								if ($ins_rslt->execute()) 
									{
									$email_id=$dbhA->last_insert_id(undef, undef, 'vicidial_email_list', 'email_row_id');
									if ($attach_ct>0) 
										{
										$multistmt="";
										for ($k=0; $k<$attach_ct; $k++) 
											{
											$ins_values[$k]="('$email_id',$ins_values[$k])";
											$multistmt.="$ins_values[$k],";
											$output_ins_values[$k]="('$email_id',$output_ins_values[$k])";
											$output_multistmt.="$output_ins_values[$k],";
											}

										$attachment_ins_stmt="insert into inbound_email_attachments(email_row_id, filename, file_type, file_encoding, file_size, file_extension, file_contents) VALUES ".substr($multistmt,0,-1);
										$attachment_ins_rslt=$dbhA->prepare($attachment_ins_stmt);
										$attachment_ins_rslt->execute();

										$output_ins_stmt="insert into inbound_email_attachments(email_row_id, filename, file_type, file_encoding, file_size, file_extension, file_contents) VALUES ".substr($output_multistmt,0,-1);
										if ($DBX) {print $output_ins_stmt."\n";}
										}
									if ($DB) {print "\n Found $attach_ct attachments in email\n";}

									## QM queue_log insert
									if ($enable_queuemetrics_logging > 0)
										{
										$PADlead_id = sprintf("%010s", $lead_id);	while (length($PADlead_id) > 10) {chop($PADlead_id);}
										$PADemail_id = sprintf("%09s", $email_id);	while (length($PADemail_id) > 9) {$PADemail_id = substr($PADemail_id,1);}
										$caller_code = "E$PADemail_id$PADlead_id";

										$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
										 or die "Couldn't connect to database: " . DBI->errstr;

										if ($DBX) {print "CONNECTED TO QueueMetrics DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

										$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='DID',data2='$VARemail_ID',serverid='$queuemetrics_log_id';";
										$Baffected_rows = $dbhB->do($stmtB);

										$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='IVRSTART',data2='$VARemail_groupid',data3='$VARemail_ID',serverid='$queuemetrics_log_id';";
										$Baffected_rows = $dbhB->do($stmtB);

										$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='IVR',data2='$VARemail_groupid',serverid='$queuemetrics_log_id';";
										$Baffected_rows = $dbhB->do($stmtB);

										$dbhB->disconnect();
										}
									}
								else 
									{
									die "Email insert failed.  Check SQL in:\n $ins_stmt\n";
									}
								}
							}
						close(STORAGE);
						}
					}
				if ($DB) {
					if ($DBX) {
						print "Iteration #$q - found ".($new_messages+0)." new messages\n";
					} else {
						# print ".";
					}
				}
				# sleep($sleep_time);
			}
			$client->logout();
		} elsif ($VARemail_protocol eq "POP3") {
			# if (!$sleep_time || $sleep_time<300) {$sleep_time=300;} # Some servers don't allow multiple connections within a certain time frame.
			# if ($DB) {$mail_check_iterations=5;} else {$mail_check_iterations=1000000;}
			use Mail::POP3Client;
			$pop = new Mail::POP3Client( USER     => "$VARemail_user",
										   PASSWORD => "$VARemail_pwd",
										   HOST     => "$VARemail_server",
										   PORT		=> 995,
										   USESSL   => true,
										   AUTH_MODE	=> "$VARpop3_auth_mode",
										   DEBUG => "$DBX",
										 )
			  or die "Cannot connect through POP3Client: $!";

			if ($pop->Count()<0) {die "Error connecting to server.  Please try again later.\n";}
			for( $i = 1; $i <= $pop->Count(); $i++ ) 
				{
				foreach( $pop->Head( $i ) ) 
					{
					# print $_."\n";
					if ($_=~/^(To|From|Date|Subject|MIME-Version|Content-Type|Content-Transfer-Encoding|X-Mailer|Authentication-Results|Message|Body):\s+/i) 
						{
						$value=$_;
						$ptn=$&;
						$value=~s/$ptn//i;
						$ptn=~s/^\s*(.*?)\s*$/$1/;
						$ptn=~s/:$//;
						$ptn=~s/\-/_/gi;
						$varname=lc($ptn);
						$$varname=$value;
						}
					if ($_=~/boundary\=\"?[^\"]+\"?/i) 
						{
						$bkup_boundary=$&;
						}
					}
				#Rename variables so they aren't as vague.
				$email_to=$to;
				$email_from=$from;

				$message=$pop->Body( $i );
				$text_written=0;  ## Keeps track of whether or not text of email was grabbed
				$attach_ct=0; ## Keeps number
				@ins_values=();
				@output_ins_values=();
		
				# Check for charset, in case decoding is necessary
				$content_type=~/charset\=\"?(KOI8\-R|ISO\-8859\-[0-9]+|windows\-12[0-9]+|utf\-8|us\-ascii)\"?/i;
				$charset=$&;
				$charset=substr($charset, 8);
				$charset=~s/\"//gi;

				if ($content_type=~/^text\/plain/i) {
					## Do nothing - it's plain text and needs no further work on it.
					if ($DB) {print "Email message is text/plain.  Nothing needs to be done.\n\n";}
				} elsif ($content_type=~/^text\/html/i) {
					## Message is HTML, so it needs to be stripped.
					if ($DB) {print "Email message is text/html.  Needs to have tags stripped via HTML::Strip.\n\n";}
					StripHTML();
				} elsif ($content_type=~/^multipart\/(alternative|mixed|related)/i) {
					## Message is multipart/alternative, so it needs to be read and partitioned.  The multipart/alternative subtype indicates that each part is an "alternative"
					## version of the same (or similar) content, each in a different format denoted by its "Content-Type" header. The formats are ordered by how faithful they are to 
					## the original, with the least faithful first and the most faithful last. Systems can then choose the "best" representation they are capable of processing; in 
					## general, this will be the last part that the system can understand, although other factors may affect this.
					if ($DB) {print "Email message is multipart.  Need to select the best format type and parse it.\n";}

					## First, get the boundary from the content-type and use it to break the email into it's multiple parts
					if ($content_type=~/boundary\=\"?[^\"]+\"?/i) {
						$boundary=$&;
					} else {
						$boundary=$bkup_boundary;
					}

					if (length($boundary)>0) {
						$boundary=~s/(^boundary=\"?|\"?$)//gi;
						$boundary="--".$boundary;
						@alternatives_array=split(/$boundary/, $message);

						for ($k=1; $k<(scalar(@alternatives_array)-1); $k++) { # Ignore the first and last entry in the array; it's just a blank entry and the two dashes after the last boundary.
							## Grab the message by using Mail::Message to strip header information
							$alt_email_text=$alternatives_array[$k];
							$alt_email_text=~s/(^(\s|\r|\n)+|(\s|\r|\n)+$)//;  # This needs to be trimmed so the Mail::Message->read command will strip out the headers - leading carriage returns ruin it

							# Reset certain variables;
							$sub_content_disposition="";
							$sub_content_type="";
							$attachment_fulltype="";
							$attachment_filename="";
							$attachment_type="";

							if ($alt_email_text=~/Content\-Type\:\s+(.*?)\n/i) {$sub_content_type=$&;} 
							if ($alt_email_text=~/Content\-Disposition\:\s+(.*?)\n/i) {$sub_content_disposition=$&;}
							if ($alt_email_text=~/Content\-Transfer\-Encoding\:\s+(.*?)\n/i) {
								$encoding_type=$&;
								$encoding_type=~s/(^Content-Transfer-Encoding\:\s+|\;$)//gi;
								$encoding_type=~s/[\r\n]//g;
							}
							$sub_content_type=~s/[\r\n]//g;
							$sub_content_disposition=~s/[\r\n]//g;
							
							my $msg = Mail::Message->read($alt_email_text);
							$body_contents=$msg->body;
							$attachment_filesize=$msg->size();
							$head=$msg->head;
							if ($DBX) {print "Part content disposition is $sub_content_disposition\n Part content type is $sub_content_type.\n";}
							if ($DBX) {print "Part content size is $attachment_filesize\n";}


							## Check if the content-type is plain or html
							if ($alt_email_text=~/Content\-Type\:\s+text\/html/i && $text_written==0) {
								$message=$body_contents;
								$content_transfer_encoding=$encoding_type;
								if ($DB) {print "First acceptable content-type match is text/html.  Stripping headers and stripping tags from the body/message...\n";}
								if ($DBX) {print "$k)\n***Pre-HTML strip:\n$message\n***\n";}
								StripHTML();
								if ($DBX) {print "$k)\n***Post-HTML strip:\n$message\n***\n";}
								$text_written=1;
							} elsif ($alt_email_text=~/Content\-Type\:\s+text\/plain/i && $text_written==0) {
								if ($DB) {print "First acceptable content-type match is text/plain.  Stripping headers to get text...\n";}
								$text_written=1;
								$message=$body_contents;
								$content_transfer_encoding=$encoding_type;
							}

							## Do a second check for a charset in the message, just in case.
							$sub_content_type=~/charset\=\"?(KOI8\-R|ISO\-8859\-[0-9]+|windows\-12[0-9]+|utf\-8|us\-ascii)\"?/i;
							$charset=$&;
							$charset=substr($charset, 8);
							$charset=~s/\"//gi;
							$content_type.="; charset=$charset";

							## Check for attachments
							if ($sub_content_disposition=~/attachment/) {
								$attachment_fulltype="";
								$attachment_filename="";
								## Check to see if attachment is an accepted filetype
								$sub_content_type=~/Content-Type\:\s+[^\;]+(\;.*)?$/i; $attachment_type=$&;
								$attachment_type=~s/(^Content-Type\:\s+|(\;.*)?$)//gi;
								$attachment_type=~s/[\r\n]//g;

								switch ($attachment_type) {
									case "application/pdf" {$attachment_fulltype="PDF";}
									case "image/jpeg" {$attachment_fulltype="JPG";}
									case "image/jpg" {$attachment_fulltype="JPG";}
									case "image/gif" {$attachment_fulltype="GIF";}
									case "image/x-png" {$attachment_fulltype="PNG";}
									case "image/png" {$attachment_fulltype="PNG";}
									case "image/bmp" {$attachment_fulltype="BMP";}
									case "image/x-ms-bmp" {$attachment_fulltype="BMP";}
									case "application/msword" {$attachment_fulltype="DOC";}
									case "application/rtf" {$attachment_fulltype="RTF";}
									case "application/vnd.ms-powerpoint" {$attachment_fulltype="PPT";}
									case "application/vnd.ms-excel" {$attachment_fulltype="XLS";}
									case "application/x-msexcel" {$attachment_fulltype="XLS";}
									case "application/ms-excel" {$attachment_fulltype="XLS";}
									case "application/zip" {$attachment_fulltype="ZIP";}
									case "text/csv" {$attachment_fulltype="CSV";}
									case "text/plain" {$attachment_fulltype="TXT";}
									case "application/vnd.oasis.opendocument.text" {$attachment_fulltype="ODT";}
									case "application/vnd.oasis.opendocument.spreadsheet" {$attachment_fulltype="ODS";}
								}
								if ($sub_content_disposition=~/filename\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
								if (length($attachment_filename)==0) {
									if ($DB) {print "Couldn't find file name with content-disposition.  Searching full header for 'filename' value...\n";}
									if ($alt_email_text=~/filename\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
									if (length($attachment_filename)==0) {
										if ($DB) {print "Couldn't find file name anywhere in header.  Third test - searching Content-Type for 'name' value...\n";}
										if ($sub_content_type=~/name\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
										if (length($attachment_filename)==0) {
											if ($DB) {print "Couldn't find file 'name' anywhere in Content-Type line.  Last test - searching full header for 'name' value...\n";}
											if ($alt_email_text=~/name\=\"?(.*?)\"?$|filename=\"[^\"]+\"/i) {$attachment_filename=$&;}
										}
									}
								}
								$attachment_filename=~s/(file)?name\=\"?|\"?$//gi;


								# If the attachment check is good, then proceed with storing the attachment.  Otherwise continue.
								if ($attachment_fulltype && $attachment_fulltype ne "") {
									if ($attachment_filename && $attachment_filename ne "") {
										$body_contents=~s/(\"|\||\'|\;)/\\$&/g;  # This is a problem for POP3 (of course) in the attachment contents.
										if ($DB) {print "Found valid attachment, type $attachment_type \n Attachment filename is $attachment_filename ($attachment_fulltype)\nFile can be stored in database....\n";}
										$ins_values[$attach_ct]="'$attachment_filename','$attachment_type','$encoding_type','$attachment_filesize','$attachment_fulltype','$body_contents'";
										$output_ins_values[$attach_ct]="'$attachment_filename','$attachment_type','$encoding_type','$attachment_filesize','$attachment_fulltype','<FILE CONTENTS>'";
										$attach_ct++;
									} else {
										if ($DB) {print "!!!!WARNING - Found valid attachment, type $attachment_type \n Attachment does not have file name.  Skipping...\n";}
									}
								} else {
									if ($DB) {print "!!!!WARNING - Found attachment $attachment_filename, but is NOT a valid file type \n Attachment type is $attachment_type.  Skipping...\n";}
								}
							}
							# print "\n";
						}
					} else {
						if ($DB) {print "!!!!WARNING - Mail is multi-part, but no boundary value was found.  Email will be ignored.\n";}
					}
				}
				# print "\n";

				# Do a clean-and-covert on the message, to decode base64 messages and also to UTF8 decode quoted printable text, in order to pick up special characters
				if ($content_transfer_encoding eq "base64") {
					$message=decode_base64($message);
					if ($charset ne "") {
						# decode to Perl's internal format
						$message=decode($charset, $message);
						#encodetoUTF-8
						$message=encode('utf-8', $message);
					}
				} elsif ($content_transfer_encoding eq "quoted-printable") {
					$message=decode_qp($message);

					if ($charset ne "") {
						# decode to Perl's internal format
						$message=decode($charset, $message);
						#encodetoUTF-8
						$message=encode('utf-8', $message);
					}
				}

				if ($date=~/[0-9]{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+[0-9]{4}\s+[0-9]{1,2}\:[0-9]{1,2}(\:[0-9]{1,2})?/) {
					$date=$&;
					$date=~s/\s+/ /gi;
					@date_array=split(/\s/, $date);
					$day=substr("0".$date_array[0], -2);
					$month=$date_array[1];
					$year=$date_array[2];
					@time_array=split(/\:/, $date_array[3]);
					$hour=substr("00".$time_array[0], -2);
					$min=substr("00".$time_array[1], -2);
					$sec=substr("00".$time_array[2], -2);
					$date="$day $month $year $hour:$min:$sec";
					if ($DB) {print "Time stamp on email is $date\n";}
				} elsif ($DB) {print "WARNING: Time stamp on email is $date\n";}

				$message=~s/(\"|\||\'|\;)/\\$&/g;
				$email_to=~s/(\"|\||\'|\;)/\\$&/g;
				$email_from=~s/(\"|\||\'|\;)/\\$&/g;
				$subject=~s/(\"|\||\'|\;)/\\$&/g;
				$mime_type=~s/(\"|\||\'|\;)/\\$&/g;
				$content_type=~s/(\"|\||\'|\;)/\\$&/g;
				$content_transfer_encoding=~s/(\"|\||\'|\;)/\\$&/g;
				$x_mailer=~s/(\"|\||\'|\;)/\\$&/g;
				$sender_ip=~s/(\"|\||\'|\;)/\\$&/g;
				$message=~s/\s{2,}/ /gi;

				### Parses the actual email address from the "Email From:" value in order to run it against vicidial_list
				### Not sure how accurate this is
				$email_from_name=$email_from;
				$email_from_name=~s/\<?([^\s\@])+\@(([^\s\@\.])+\.)+[a-zA-Z]{2,}\>?//gi;
				$email_from_name=~s/^\s*(.*?)\s*$/$1/;

				$email_from=~/\<?([^\s\@])+\@(([^\s\@\.])+\.)+[a-zA-Z]{2,}\>?/i;
				$email_from=$&;
				$email_from=~s/(^\<)|(\>$)//gi;

				$status="NEW";
				$authentication_results=~/([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])){3}/i;
				$sender_ip=$&;
				$received_spf=~/([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])){3}/i;
				$sender_ip2=$&;
				if (length($sender_ip)==0) {$sender_ip=$sender_ip2;}

				$limit=1;
				$call_handle_clause="";
				if ($call_handle_method eq "EMAILLOOKUP") {
					$call_handle_clause="where email='$email_from' order by lead_id desc";
				} elsif ($call_handle_method eq "EMAILLOOKUPRL") {
					$call_handle_clause="where email='$email_from' and list_id='$list_id' order by lead_id desc";
				} elsif ($call_handle_method eq "EMAILLOOKUPRC") {
					my $list_id_stmt="select list_id from vicidial_lists where campaign_id='$campaign_id'";
					my $list_id_rslt=$dbhA->prepare($list_id_stmt);
					$list_id_rslt->execute();
					$list_id_str="";
					while (@list_id_row=$list_id_rslt->fetchrow_array) {
						$list_id_str.="$list_id_row[0],";
					}
					$list_id_str=substr($list_id_str, 0, -1);
					$call_handle_clause="where email='$email_from' and list_id in ($list_id_str) order by lead_id desc";
				} else {
					$limit=0;
				}

				### CHECK if lead exists in vicidial_list table via a search by email based on the email account settings
				my $vicidial_lead_check_stmt="select lead_id from vicidial_list $call_handle_clause limit $limit";
				my $vicidial_lead_check_rslt=$dbhA->prepare($vicidial_lead_check_stmt);
				$vicidial_lead_check_rslt->execute();
				if ($vicidial_lead_check_rslt->rows>0) {
					my @lead_id_row=$vicidial_lead_check_rslt->fetchrow_array;
					$lead_id=$lead_id_row[0];
				} else {
					my $vicidial_list_stmt="insert into vicidial_list(list_id, email, comments, status, entry_date, phone_code) values('$default_list_id', '$email_from', '".substr($message,0,255)."', '$status', NOW(), '$SSdefault_phone_code')";
					if ($DBX) {print $vicidial_list_stmt."\n";}
					my $vicidial_list_rslt=$dbhA->prepare($vicidial_list_stmt);
					if ($vicidial_list_rslt->execute()) {
						$lead_id=$dbhA->last_insert_id(undef, undef, 'vicidial_list', 'lead_id');
					} else {
						die "Vicidial list insert failed.  Check SQL in:\n $vicidial_list_stmt\n";
					}
				}

				## Insert a new record into vicidial_email_list.  This is ALWAYS done for new email messages.
				my $ins_stmt="insert into vicidial_email_list(lead_id, protocol, email_date, email_to, email_from, email_from_name, subject, mime_type, content_type, content_transfer_encoding, x_mailer, sender_ip, message, email_account_id, group_id, status, direction) values('$lead_id', 'POP3', STR_TO_DATE('$date', '%d %b %Y %T'), '$email_to', '$email_from', '$email_from_name', '$subject', '$mime_type', '$content_type', '$content_transfer_encoding', '$x_mailer', '$sender_ip', trim('$message'), '$VARemail_ID', '$VARemail_groupid', '$status', 'INBOUND')";
				if ($DBX) {print $ins_stmt."\n";}
				my $ins_rslt=$dbhA->prepare($ins_stmt);
				if ($ins_rslt->execute()) 
					{
					$email_id=$dbhA->last_insert_id(undef, undef, 'vicidial_email_list', 'email_row_id');
					$pop->Delete($i);
					if ($attach_ct>0) 
						{
						$multistmt="";
						for ($k=0; $k<$attach_ct; $k++) 
							{
							$ins_values[$k]="('$email_id',$ins_values[$k])";
							$multistmt.="$ins_values[$k],";
							$output_ins_values[$k]="('$email_id',$output_ins_values[$k])";
							$output_multistmt.="$output_ins_values[$k],";
							}

						$attachment_ins_stmt="insert into inbound_email_attachments(email_row_id, filename, file_type, file_encoding, file_size, file_extension, file_contents) VALUES ".substr($multistmt,0,-1);
						$attachment_ins_rslt=$dbhA->prepare($attachment_ins_stmt);
						$attachment_ins_rslt->execute();
						$output_ins_stmt="insert into inbound_email_attachments(email_row_id, filename, file_type, file_encoding, file_size, file_extension, file_contents) VALUES ".substr($output_multistmt,0,-1);
						if ($DBX) {print $output_ins_stmt."\n";}
						}
					if ($DB) {print "\n $attach_ct attachments in email\n";}

					## QM queue_log insert
					if ($enable_queuemetrics_logging > 0)
						{
						$PADlead_id = sprintf("%010s", $lead_id);	while (length($PADlead_id) > 10) {chop($PADlead_id);}
						$PADemail_id = sprintf("%09s", $email_id);	while (length($PADemail_id) > 9) {$PADemail_id = substr($PADemail_id,1);}
						$caller_code = "E$PADemail_id$PADlead_id";

						$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
						 or die "Couldn't connect to database: " . DBI->errstr;

						if ($DBX) {print "CONNECTED TO QueueMetrics DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

						$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='DID',data2='$VARemail_ID',serverid='$queuemetrics_log_id';";
						$Baffected_rows = $dbhB->do($stmtB);

						$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='IVRSTART',data2='$VARemail_groupid',data3='$VARemail_ID',serverid='$queuemetrics_log_id';";
						$Baffected_rows = $dbhB->do($stmtB);

						$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$secX',call_id='$caller_code',queue='NONE',agent='NONE',verb='INFO',data1='IVR',data2='$VARemail_groupid',serverid='$queuemetrics_log_id';";
						$Baffected_rows = $dbhB->do($stmtB);

						$dbhB->disconnect();
						}
					}
				else 
					{
					die "Email insert failed.  Check SQL in:\n $ins_stmt\n";
					}
				}

			#### END CYCLING THROUGH EMAILS
			if ($DB) {
				if ($DBX) {
					print "Iteration #$q - found ".($pop->Count()+0)." new messages\n";
				}
			}

			if ($DBX) {
				print "Closing connection to finalize deletion of emails\n";
			}
			$pop->Close();
			# sleep($sleep_time);
			if ($DBX) {
				print "Reconnecting to email account...\n";
			}
			$pop = new Mail::POP3Client( USER     => "$VARemail_user",
										   PASSWORD => "$VARemail_pwd",
										   HOST     => "$VARemail_server",
										   PORT		=> 995,
										   USESSL   => true,
										   AUTH_MODE	=> "$VARpop3_auth_mode",
										   DEBUG => "$DBX",
										 )
			or die "Cannot connect through POP3Client: $!";
			$pop->Close();
		} else {
			die "No valid protocol specified for this program: ($VARemail_protocol) is the current protocol - needs to be IMAP or POP3.\n";
		}
	}
else
	{
	if ($DB > 0)
		{
		print "               NO-CHECK -    MIN: $minutes   FREQ: $VARemail_frequency \n";
		}
	}
}

### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if ($DBX) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}


sub StripHTML()
{
	$message=~s/^\s*(.*?)\s*$/$1/;
	$message=~s/[\r\n]/ /g;
	$message=~s/\&nbsp\;/ /gi;
	if ($message=~/<html.*?<\/html>/i) {
		$message=$&;
		my $hs = HTML::Strip->new();
		$message = $hs->parse( $message );
		$hs->eof;
	} 
}
