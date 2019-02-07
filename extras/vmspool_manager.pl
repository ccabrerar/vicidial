#!/usr/bin/perl
use strict;
# Copyright 2007 Jay Allen
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program.  If not, see <http://www.gnu.org/licenses/>.
#


# Author:  Jay D. Allen  jay@f2it.com
# Version:  1.4  - July 2007
# 
#  1.  Delete any stale .lock* files.  Stale is defined as ctime/atime more 
#      a few minutes old (maybe 30 minutes to be safe)?  Look into the cause of 
#      these stake lock files?  What versions of Asterisk are effected?
#      Nasty bug!  .locked mailboxes _silently_ drop voicemail recordings.
#  2.  Remove voicemail files older than a policy date
#  3.  Possibly a report mode that just shows the age of the mail/boxes but 
#      does nothing
#      Report on number of messages per box, vs quota max
#      Send whiny emails to users about voicemail quota.
#  4.  Renumber boxes that are missing msg0001.X - since that will prevent
#      playback.

=head1 vmspool_manager.pl

This is a utility which does some checks on existing asterisk voicemail files and deletes old lock files and mail messages.  This is best run on a system that is not being used due the file renumbering and deleting of files which Asterisk could be accessing at the same time.

=head1 Options

--active:          Run in active mode, deleting files.  Default is to 'report'

--age=<#>:         Time after which a message is considered old in days.  Default is 14 days.

--bad:             Remove message files that do not have the expected number of files.

--context=<name>:  The context for Asterisk,  set to 'default' by default.

--debug:           Debug mode.

--group=<name>:    Name of group, default is "asterisk"

--help:            Help message.

--mailbox=<#>:     Run for a particular mailbox.

--spool=<path>:    Set to path to mail spool.  Default is "/var/spool/asterisk/voicemail"

--user=<name>:     Name of user, default is "asterisk"

=head1 USAGE:  To delete files over 30 days old.  Also remove bad lock files.

fixup-voicemail.perl --age=30 --active

=head1 FUNCTIONS

=cut

use Getopt::Long;
use Pod::Usage;


our $DEBUG=0;
our $REPORT=1;
# Voicemail User
our $VM_USER="asterisk";
# Voicemail Group
our $VM_GROUP="asterisk";
#  Sane defaults for Trixbox
our $VM_CONTEXT="default";
our $VM_SPOOL="/var/spool/asterisk/voicemail";
# Developer's box
#our $VM_SPOOL="/home/judith/ast-runtime-1.2.14/var/spool/asterisk/voicemail";
our $MAX_AGE=14;                 # days
our $MAX_LOCK_AGE=30;            # minutes

our $VMDIR;     # This is the directory handle

our @SUFFIXES=("WAV", "gsm", "txt", "wav");
our @MAILBOX_TYPES=("Old", "INBOX");

our $ACTIVE = undef;
our $BAD = undef;
our $HELP = undef;
our $ONE_MAILBOX = undef;

my $result = GetOptions ("active"      => \$ACTIVE,
			 "age=i"       => \$MAX_AGE,      # numeric
                         "bad"         => \$BAD,          # string
                         "context=s"   => \$VM_CONTEXT,   # string
                         "debug"       => \$DEBUG,        # flag
                         "group=s"     => \$VM_GROUP,     # string
                         "help"        => \$HELP,         # flag
                         "mailbox=i"   => \$ONE_MAILBOX,  # flag
                         "spool=s"     => \$VM_SPOOL,
                         "user=s"      => \$VM_USER) or pod2usage(2); 
## Parse options and print usage if there is a syntax error,
## or if usage was explicitly requested.
pod2usage(1) if $HELP;


our $VM_SPOOL_PATH="$VM_SPOOL/$VM_CONTEXT";

if ($ACTIVE){
    $REPORT = undef;
}
#
# Here is the main script.
# Get a list of all the voicemail directorys in this context
opendir(VMDIR,$VM_SPOOL_PATH) || die "Can't open $VM_SPOOL_PATH\n";
my @mailboxes = grep { /^\d./ && -d "$VM_SPOOL_PATH/$_" } readdir(VMDIR);
if ($ONE_MAILBOX){
    if ( -d "$VM_SPOOL_PATH/$ONE_MAILBOX" ){
        @mailboxes = ( $ONE_MAILBOX );
    } else {
        die "Mailbox $VM_SPOOL_PATH/$ONE_MAILBOX does not exist\n";
    }
}

foreach my $vmbox (@mailboxes){
    foreach my $mailbox_type (@MAILBOX_TYPES){
        print "MAILBOX $vmbox/$mailbox_type\n";
        my $MAILBOX;
        my $path = "$VM_SPOOL_PATH/$vmbox/$mailbox_type";
        if(opendir($MAILBOX, $path)){
            delete_old_messages($MAILBOX, $path);
            renumber($MAILBOX, $path);
        } #  end of if has a vmbox/old
    }
}

closedir VMDIR;

=head2 delete_old_messages

Delete the Contents of the passed directory.

=cut

sub delete_old_messages{
    my $MAILBOX=shift;
    my $path=shift;
    
    if (! delete_lock_file( $MAILBOX, $path)){
        foreach my $filename (sort grep ( /^msg.*/, readdir($MAILBOX))){
            my ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$path/$filename");
            if ($DEBUG and !$REPORT){ print "\tFile $size: ".localtime($mtime).": $filename\n"; }
            if( $mtime < (time()-(60*60*24*$MAX_AGE))){
                print "\tDELETE:  $size: ".localtime($mtime).": $filename\n";
                if ($ACTIVE){ unlink "$path/$filename"; }
                if ($DEBUG and !$REPORT){ "DELETE $filename\n"; }
            } else { if ($REPORT){print "\tKEEP:  $size: ".localtime($mtime).": $filename\n"} };
        }
        rewinddir($MAILBOX); 
    } else {
        if ($REPORT){print "Mailbox $MAILBOX is LOCKED\n"};
    }
}

=head2 delete_lock_file

Delete a lockfile older than 30 minutes in the passed mailbox.

=cut

sub delete_lock_file {
    my $MAILBOX=shift;
    my $path=shift;
    my $retval=0;
    foreach my $filename (sort grep ( /^\.lock*/, readdir($MAILBOX))){
        my ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$path/$filename");
        $retval=1;
        if( $mtime < (time()-(60*$MAX_LOCK_AGE))){
            $retval=0;
            if ($REPORT){ print "\tDELETE LOCK:  $size:".localtime($mtime).": $filename\n";}
            else { unlink "$path/$filename";}
        }
    }
    rewinddir($MAILBOX); # Make sure we rewind!
    return $retval;
}

=head2 check_message_files

Check that there are the right number of messages using the array SUFFIXES.  Delete 'bad' messages if in active mode.

=cut

sub check_message_files{
    my $file_names=shift;    # This is an array of the filenames.
    my $path=shift;
    my %mboxindx=();
    foreach my $fixfile (@{$file_names}){
        if($fixfile =~ /^msg/){
            my ($prefix,$suffix)=split(/\./,$fixfile);
            if ($DEBUG){print "\tCheck presence:  $prefix\.$suffix\n";}
            $mboxindx{$prefix}++;
        }
    } # End of foreach $fixfile 
 
    foreach my $index (keys %mboxindx){
        if ($mboxindx{$index} != $#SUFFIXES + 1) { 
            print "Broken Mailbox:  $index:  $mboxindx{$index}\n" ;
#            if ((! $REPORT) and $BAD){
#                # Remove files where incomplete.
#                print "Remove files $path/$index*\n";
#                unlink <$path/$index.*>;
#            }
        }
    }
}

=head2 renumber

Asterisk needs a message with 'msg0000.XXX' in each mailbox to function.  This function will renumber the lowest number message to msg0000.XXX if one does not exist.

=cut

sub renumber {
    my $MAILBOX=shift;
    my $path = shift;
    my @OLDMAILBOX=();
    # Fixup/checkup of Old voicemail box
    @OLDMAILBOX = sort grep (/^msg.*/ , readdir($MAILBOX));
    if($#OLDMAILBOX > -1 ) {
        if ($DEBUG){ print "\tHas mail messages: " . ($#OLDMAILBOX + 1)/($#SUFFIXES + 1) . "\n";}
        if ($DEBUG){ print "\tFIRST FILE:  $OLDMAILBOX[0]\n";}
        if($OLDMAILBOX[0] !~ /msg0000.*/) {
            print "Has renumber broken msglist\n";
            rename_first_message($path, $OLDMAILBOX[0]);
        }
    }
    check_message_files(\@OLDMAILBOX, $path);
    rewinddir($MAILBOX); # Make sure we rewind!
}

=head2 rename_first_message

This function will renumber the passed message to msg0000.XXX.

=cut

sub rename_first_message {
    my $path = shift;
    my $filename = shift;
    if ($filename =~ /^msg.*/){
        my ($prefix,$suffix)=split(/\./,$filename);
        foreach my $suff (@SUFFIXES){
            print "\tRenaming files: $path/$prefix.$suff to $path/msg0000\.$suff\n";
            if ($ACTIVE){ 
                rename "$path/$prefix.$suff", "$path/msg0000\.$suff";
            } else { 
                print "\tWould rename files: $path/$prefix.$suff to $path/msg0000\.$suff\n"; 
            }
        }
    }
}
    
