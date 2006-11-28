#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org> 
#                          BBN Technologies Corp
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

use strict;
use Savane;
use Savane::Mail;
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);
use Time::Local;
use Date::Calc qw(Add_Delta_YMD);

# Import
our $sys_cron_reminder;
our $sys_name;
our $sys_https_host;
our $sys_default_domain;
our $sys_url_topdir;

my $script = "sv_reminder";
my $logfile = "/var/log/sv_reminder.log";
my $getopt;
my $help;
my $debug;
my $max = "2000";
my $max_items = "50";
my $cron;
my $version = GetVersion();
my @trackers  = ("bugs", "task", "support", "patch");

# get options
eval {
    $getopt = GetOptions("help" => \$help,
			 "cron" => \$cron,
			 "max=s" => \$max,
			 "max-items=s" => \$max_items,
			 "debug" => \$debug);
};

if($help) {
    print STDERR <<EOF;
Usage: $0 [project] [OPTIONS] 
 
This script will send reminder to users in some specific cases. 
Case 1: an item is about to start or finish, all people from the item 
notification list should be warned. 
Case 2: an user decided to receive a batch of open item assigned to
him each x days, minimum priority being > 5
Case 3: a project admin decided that members assigned to some item of 
should receive a batch each x days, minimum priority > 5
(Case 4: a project admin decided that members assigned to some item
whose statut/resolution havent changed for some period should receive
a batch each x days)

This script is supposed to run twice in a day.

  -h, --help                   Show this help and exit
  -d, --debug                  Do nothing, print everything
      --max                    Maximum number of mails sent by the script
                               If that number is reached, next mails are 
                               discarded and tagged as sent.
                               Make sure this parameters fit to your
                               number of users and servers possibilities!
                               (Default: $max)
      --max-items              Maximum number of items mentionned in a mail
                               The purpose of this setting is to avoid
                               sending extremely big mails   
                               (Default: $max_items)
      --cron                   Option to set when including this script
                               in a crontab
Savane version: $version
EOF
exit(1);
}

# Test if we should run, according to conffile
exit if ($cron && ! $sys_cron_reminder);

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;


# Locks: This script should not run concurrently
AcquireReplicationLock();


# Process: - List items+why+user. 
#          - Then compile per user, mentionning why+item, 
#                 adding more info and links
#          - Send all mails
#          - Register in the db with one SQL command would be best (saving
# 	   ressource), but painy  if the script is interrupted. 

# list of users that is supposed to receive a batch
my %user;

# items{user} = (bugs54212, bugs1321)
my %items;

# Not sure well provide this information, to avoid putting too much
# information in mail sent
# why{bugs542} = case1
#my %why;

# summary{bugs542} = summary of the item
my %summary;

# Not sure well provide this information, to avoid running to many sql
# commands
# project{bugs542} = project of the item
#my %project;


#######################################################################
##
## TIMESTAMPS
##
#######################################################################
 
# Determine the timestamp of one month ago, one week ago and one day
# ago.
# With these, we'll run only 3 sql command and find out which users need
# to receive a bach.

my ($year, $month, $day);

my $now = timelocal(localtime());

my ($year, $month, $day) = split(",", `date +%Y,%m,%d`);
($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,0,-1);
my $daybefore = timelocal("0","0","0",$day,($month-1),($year-1900));

my ($year, $month, $day) = split(",", `date +%Y,%m,%d`);
($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,0,-7);
my $weekbefore = timelocal("0","0","0",$day,($month-1),($year-1900));

my ($year, $month, $day) = split(",", `date +%Y,%m,%d`);
($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,1,0);
my $monthbefore = timelocal("0","0","0",$day,($month-1),($year-1900));

print "DBG time: daybefore:$daybefore, weekbefore:$weekbefore; monthbefore:$monthbefore\n" if $debug;

#######################################################################
##
## Case 1: an item is about to start or finish, assigned to person
## should be warned
##
#######################################################################
 
# Not implemented yet.


#######################################################################
##
## Case 2: an user decided to receive a batch of open item 
## assigned to him each x days.
##
#######################################################################

# store user frequency useful to test if they really need to be in user
my %user_frequency;
# store user that need to receive batch for case 2
my @user_case2;

# Make one sql command to catch appropriate users:
#   with batch_frequency pref. set (not to zero)
#   frequency flag: 0 = none, 1 = daily, 2 = weekly, 3 = monthly
foreach my $line (GetDB("user_preferences", 
			"preference_name='batch_frequency' AND preference_value <> '0'",
			"user_id,preference_value")) {
    chomp($line);
    my ($user_id,$frequency) = split(",", $line);
    print "DBG db user: get $user_id, $frequency from database\n" if $debug;
    $user_frequency{$user_id} = $frequency;
}

# Make one sql command to catch defined batch last sent timestamp
# People that got batch_frequency set MUST have batch_lastsent set, otherwise
# it's a bug in the frontend.
# batch_lastsent must be smaller than yesterday 
foreach my $line (GetDB("user_preferences", 
			"preference_name='batch_lastsent' AND preference_value < '".$daybefore."'",
			"user_id,preference_value")) {
    chomp($line);
    my ($user_id,$lastsent) = split(",", $line);
    
    # valid for a monthly batch?
    if ($lastsent < $monthbefore && 
	$user_frequency{$user_id} > 0){	
	push(@user_case2, $user_id);	
	print "DBG case2 valid for monthly/weekly/daily batch : $user_id, $lastsent \n" if $debug;
    } elsif ($lastsent < $weekbefore && 
	     $user_frequency{$user_id} > 0 &&
	     $user_frequency{$user_id} < 3){	
	push(@user_case2, $user_id);	
	print "DBG case2 valid for weekly/daily batch : $user_id, $lastsent \n" if $debug;	
    } elsif ($lastsent < $daybefore && 
	     $user_frequency{$user_id} eq 1){	
	push(@user_case2, $user_id);	
	print "DBG case2 valid for daily batch : $user_id, $lastsent \n" if $debug;
    } 
}

# Now look out which item are opened an assigned to each user that need to 
# receive a batch.
# We have to run this command on the 4 trackers.
foreach my $tracker (@trackers) {
    foreach my $user_id (@user_case2) {
	foreach my $line (GetDB($tracker, 
				"assigned_to='".$user_id."' AND status_id='1' AND priority > 5",
				"bug_id,summary")) {
	    
	    chomp($line);
	    my ($item_id,$summary) = split(",", $line);
	    print "DBG case2 : $user_id, $tracker $item_id $summary \n" if $debug;
	    $user{$user_id} = 1 unless $user{$user_id};
	    push(@{$items{$user_id}}, $tracker.",".$item_id);
	    $summary{$tracker.",".$item_id} = $summary unless $summary{$tracker.",".$item_id};
	}
    }
}

#######################################################################
##
## Case 3: a project admin decide that anybody on his project that got
## open item assigned should receive reminders. The project admin
## set the frequency.
##
#######################################################################

# store user frequency useful to test if they really need to be in user
my %group_frequency;
# store group that needs batch to be sent
my @group_case3;

# Make one sql command to catch appropriate users:
#   with batch_frequency pref. set (not to zero)
#   frequency flag: 0 = none, 1 = daily, 2 = weekly, 3 = monthly
foreach my $line (GetDB("group_preferences", 
			"preference_name='batch_frequency' AND preference_value <> '0'",
			"group_id,preference_value")) {
    chomp($line);
    my ($group_id,$frequency) = split(",", $line);
    print "DBG db group: get $group_id, $frequency from database\n" if $debug;
    $group_frequency{$group_id} = $frequency;
}


# Make one sql command to catch defined batch last sent timestamp
# People that got batch_frequency set MUST have batch_lastsent set, otherwise
# it's a bug in the frontend.
# batch_lastsent must be smaller than yesterday 
foreach my $line (GetDB("group_preferences", 
			"preference_name='batch_lastsent' AND preference_value < '".$daybefore."'",
			"group_id,preference_value")) {
    chomp($line);
    my ($group_id,$lastsent) = split(",", $line);
    
    # valid for a monthly batch?
    if ($lastsent < $monthbefore && 
	$group_frequency{$group_id} > 0){	
	push(@group_case3, $group_id);	
	print "DBG case3 valid for monthly/weekly/daily batch : $group_id, $lastsent \n" if $debug;
    } elsif ($lastsent < $weekbefore && 
	     $group_frequency{$group_id} > 0 &&
	     $group_frequency{$group_id} < 3){	
	push(@group_case3, $group_id);	
	print "DBG case3 valid for weekly/daily batch : $group_id, $lastsent \n" if $debug;	
    } elsif ($lastsent < $daybefore && 
	     $group_frequency{$group_id} eq 1){	
	push(@group_case3, $group_id);	
	print "DBG case3 valid for daily batch : $group_id, $lastsent \n" if $debug;
    } 
}

# Now look out which item are opened an assigned to each user that need to 
# receive a batch.
# We have to run this command on the 4 trackers.
foreach my $tracker (@trackers) {
    foreach my $group_id (@group_case3) {
	foreach my $line (GetDB($tracker, 
				"group_id='".$group_id."' AND status_id='1' AND priority > 5",
				"bug_id,summary,assigned_to")) {
	    
	    chomp($line);
	    my ($item_id,$summary,$user_id) = split(",", $line);
	    print "DBG case3 : $user_id, $tracker $item_id $summary \n" if $debug;
	    $user{$user_id} = 1 unless $user{$user_id};
	    push(@{$items{$user_id}}, $tracker.",".$item_id);
	    $summary{$tracker.",".$item_id} = $summary unless $summary{$tracker.",".$item_id};
	}
    }
}


#######################################################################
##
## Grab db information related to users -- we want to do that in 
## one SQL command
##
#######################################################################

my %user_email;
my %user_name;
foreach my $line (GetDB("user", 
			"status='A'",
			"user_id,email,realname")) {
    chomp($line);
    my ($user_id, $email, $realname) = split(",", $line);
    if ($user{$user_id}) {
	$realname =~ s/\://g;
	$user_name{$user_id} = $realname;
	$user_email{$user_id} = $email; 
	print "DBG get from db : $realname <$email>\n" if $debug;
    }
}


#######################################################################
##
## Send mails:
## Currently the contact is standard. Later it will probably partly
## site-specific
##
## Some test needs to be done to be sure the smtp will accept to send
## a big amount of mails
##
#######################################################################

my $count;
my $basepath;

if ($sys_url_topdir ne "/") {
    # If sys_url_topdir is not simply, an ending slash must be added.
    $sys_url_topdir .= "/";
}

if ($sys_https_host) {
    $basepath .= "https://".$sys_https_host.$sys_url_topdir;
} else {
    $basepath .= "http://".$sys_default_domain.$sys_url_topdir;
}

while (my ($user_id,) = each(%user)) {
    $count++;
    last if $count > $max;
    
    # skip the entry if there is no user_id
    next unless $user_id;
    # skip the entry if there is no user_id email
    next unless $user_email{$user_id};

    my $title = $sys_name." Reminder";

    my $mail = "Hello ".$user_name{$user_id}.",

This reminder is sent to you because of your personal notification
settings or, possibly, configuration settings of projects you are member of.

Follows items, assigned to you, that require your attention (same status for 
too long, high priority, etc...).

";

    my $itemcount;
    foreach my $item (@{$items{$user_id}}) {
	$itemcount++;

	if ($itemcount > $max_items) {
	   $mail .= "\nThere are others item for you but we won't mention more than ".$max_items." items in a mail.\n";
	   last;
	}

	my ($tracker, $item_id) = split(",", $item);
	$mail .= " - $tracker #$item_id: ".$summary{$item}."\n    ";
	$mail .= "<".$basepath."$tracker/?func=detailitem&item_id=$item_id>\n";
    }
    
    $mail .= "\n\nYou can change your personal notification settings at
    <".$basepath."my/admin/change_notifications.php>";

    unless ($debug) {
	MailSend("",$user_email{$user_id},$title,$mail);
#	print LOG strftime "[$script] mail sent to ".$user_name{$user_id}." <".$user_email{$user_id}.">\n", localtime;
    } else {
	print "--------------------For $user_id----------------------------\n".
	    $mail."\n";
    }	

}

# Now reset lastsent value. 
unless ($debug) {
    foreach my $user_id (@user_case2) {
	next unless $user_id;
	SetDBSettings("user_preferences", "user_id='$user_id' AND preference_name='batch_lastsent'", "preference_value='".$now."'");
    }

    foreach my $group_id (@group_case3) {
	next unless $group_id;
	SetDBSettings("group_preferences", "group_id='$group_id' AND preference_name='batch_lastsent'", "preference_value='".$now."'");
    }
}


print LOG strftime "[$script] $count mails sent\n", localtime if $count;


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";

# EOF
