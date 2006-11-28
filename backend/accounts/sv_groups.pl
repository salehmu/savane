#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2001-2006 (c) Loic Dachary <loic--gnu.org> (sv_cvs.pl)
#                          Mathieu Roy <yeupou--gnu.org> 
#                          Sylvain Beucler <beuc--beuc.net>
#                          Timothee Besset <ttimo--ttimo.net>
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

##
## This script should be used via a cronjob to update the system
## by reading the database about groups.
##
## It will add create/update a group for each group. 
## It will also create a download area, a web area, and a cvs repository 
## if in the database the fields dir_cvs dir_homepage and dir_download 
## contains the string %PROJECT
## (if they don't, it means that no group specific directories like that
## are desired).
## (the web area can be also a cvs repository 
##
## WARNING: sv_groups should run before sv_users.
## 

use strict;
use Savane;
use Savane::Download;
use Savane::Cvs;
use Savane::Svn;
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);

# Import
our $sys_cron_groups;

my $script = "sv_groups";
my $logfile = "/var/log/sv_database2system.log";
my $lockfile = "groups-users.lock";
my $getopt;
my $help;
my $debug;
my $cron;
my $version = GetVersion();

my $no_etc = 0;
my $no_cvs = 0; 
my $no_arch = 0; 
my $no_svn = 0;
my $no_homepage = 0;
my $no_download = 0;

my $only_etc;
my $only_cvs;
my $only_arch;
my $only_svn;
my $only_homepage;
my $only_download;

# deprecated, replaced by webgroup
my $one_group = 0;
my $webgroup = 0;

my $min_gid = "5000";

#my $subversions = AreWeOnSubversions();

# get options
eval {
    $getopt = GetOptions("help" => \$help,
			 "cron" => \$cron,
			 "debug" => \$debug,
			 "no-etc" => \$no_etc,
			 "no-cvs" => \$no_cvs,
			 "no-svn" => \$no_svn,
			 "no-arch" => \$no_arch,
			 "no-homepage" => \$no_homepage,
			 "no-download" => \$no_download,
			 "only-etc" => \$only_etc,
			 "only-cvs" => \$only_cvs,
			 "only-svn" => \$only_svn,
			 "only-arch" => \$only_arch,
			 "only-homepage" => \$only_homepage,
			 "only-download" => \$only_download,
			 "webgroup" => \$webgroup,
			 "one-group" => \$one_group);
};

if($help) {
    print STDERR <<EOF;
Usage: $0 [OPTIONS] 

Update the system to reflect the database, about groups.
Normally, sv_users should run just after.

  -h, --help                   Show this help and exit
  -d, --debug                  Do nothing, print everything
      --cron                   Option to set when including this script
                               in a crontab

      --no-etc                 Do not update system groups
      --no-cvs                 Do not update cvs trees
      --no-arch                Do not update arch trees
      --no-svn                 Do not update svn trees
      --no-homepage            Do not update homepage dirs
      --no-download            Do not update download dirs

      --only-etc               Only update system groups
      --only-cvs               Only update cvs trees
      --only-arch              Only update arch trees
      --only-svn               Only update svn trees
      --only-homepage          Only update homepage dirs
      --only-download          Only update download dirs

      --webgroup               For each projects, add users in two group,
                               including one with the prefix web.
                               (this was the default behavior in =< 1.0.4)

Savane version: $version
EOF
exit(1);
}

# If we are asked to create only a few services, set everything to "no"
# and later add the only one(s) asked for.
if ($only_etc ||
    $only_cvs ||
    $only_arch ||
    $only_svn ||
    $only_homepage ||
    $only_download) {
 
    
    $no_etc = 1;
    $no_cvs = 1;
    $no_arch = 1;
    $no_svn = 1;
    $no_homepage = 1;
    $no_download = 1;

    $no_etc = 0 if $only_etc;
    $no_cvs = 0 if $only_cvs;
    $no_arch = 0 if $only_arch;
    $no_svn = 0 if $only_svn;
    $no_homepage = 0 if $only_homepage;
    $no_download = 0 if $only_download;
}

# Test if we should run, according to conffile
exit if ($cron && ! $sys_cron_groups);

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;


# Locks: There are several sv_db2sys scripts but they should not run
#        concurrently.
AcquireReplicationLock($lockfile);


#######################################################################
##
## Grabbing database informations.
## 
## - db_groups items
## - db_group_type items 
##
#######################################################################

# db_group_type:
#    Create an hash that contains group type infos from the table group_type,
#    as lists for each group type
#    ( @{$db_group_type{$id}} )
#    Note that we store the data with the id as key, not the name, because
#    in the group db are only used the ids.
#
#    To limit the number of request, we use only one very long SQL request. 
my %db_group_type;
foreach my $line (GetDB("group_type", 
			0,
			"name,type_id,dir_type_homepage,dir_type_cvs,dir_type_arch,dir_type_svn,dir_type_download,dir_homepage,dir_cvs,dir_arch,dir_svn,dir_download,can_use_homepage,can_use_cvs,can_use_arch,can_use_svn,can_use_download")) {
    chomp($line);
    my ($name, $id, $dir_type_homepage, $dir_type_cvs, $dir_type_arch, $dir_type_svn, $dir_type_download, $dir_homepage, $dir_cvs, $dir_arch, $dir_svn, $dir_download, $can_use_homepage, $can_use_cvs, $can_use_arch, $can_use_svn, $can_use_download) = split(",", $line);
    print "DBG db: get $line from database\n" if $debug;
    $db_group_type{$id} = [ ($name, $id, $dir_type_homepage, $dir_type_cvs, $dir_type_arch, $dir_type_svn, $dir_type_download, $dir_homepage, $dir_cvs, $dir_arch, $dir_svn, $dir_download, $can_use_homepage, $can_use_cvs, $can_use_arch, $can_use_svn, $can_use_download) ];
}

# db_groups:
#    Create an hash that contains group infos from the table groups,
#    as lists for each group
#    ( @{$db_group{$name}} )
#    Additionally, create a list of groups.
#    Additionally, create an hash to find easily which groups are 
#    in the database
#
#    To limit the number of request, we use only one very long SQL request. 
#
# Only groups in Active status will be handled!
my %db_groups;
my @db_groups;
foreach my $line (GetDB("groups", 
			"status='A'",
			"unix_group_name,type,is_public,use_homepage,use_cvs,use_arch,use_svn,use_download,dir_homepage,dir_cvs,dir_arch,dir_svn,dir_download")) {
    chomp($line);
    my ($name, $type, $is_public, $use_homepage, $use_cvs, $use_arch, $use_svn, $use_download, $dir_homepage, $dir_cvs, $dir_arch, $dir_svn, $dir_download) = split(",", $line);
    print "DBG db: get group $line from database\n" if $debug;
    $db_groups{$name} = [ ($name, $type, $is_public, $use_homepage, $use_cvs, $use_arch, $use_svn, $use_download, $dir_homepage, $dir_cvs, $dir_arch, $dir_svn, $dir_download) ];
    push(@db_groups, $name);
}

print LOG strftime "[$script] %c - database infos grabbed\n", localtime;

#######################################################################
##
## Grabbing system informations, doing comparisons.
## 
## - etc_group* items
##
#######################################################################

# /etc/group:
#    Create a list of groups which are missing on the the system. We ignore 
#    groups that are not in the database.
#    Find what is the maximum id number known.
my @only_in_db;
foreach my $group (@db_groups)
{
    my ($exists) = getgrnam($group);
    unless ($exists) {
	print "DBG etc+compare: get group $group, is missing on the system\n" if $debug;
	push(@only_in_db, $group);
    } else {
	print "DBG etc+compare: get group $group, is present on the system\n" if $debug;
    }
}

# /etc/group:
#    Find what is the maximum id number known.
#    Save also the nogroup id.
my $etc_group_maxid = -1;
my $nogroup_gid = 65534;
while(my @entry = getgrent()) {    
    if($entry[0] ne 'nogroup') {
	$etc_group_maxid = $entry[2] > $etc_group_maxid ? $entry[2] : $etc_group_maxid;
    } else {
	$nogroup_gid = $entry[2];
    }
    print "DBG etc: group $entry[0]\t\t maxid $etc_group_maxid\n" if $debug;
}
$etc_group_maxid++; 
# If we did not reached the minimal gid, set it as maxid
$etc_group_maxid = $min_gid if $min_gid > $etc_group_maxid;



print LOG strftime "[$script] %c - system infos grabbed\n", localtime;
print LOG strftime "[$script] %c - comparison done\n", localtime;


#######################################################################
##
## Finally, update the system
##
#######################################################################

# Make sure that the group svusers and anoncvs exists
# sv_users and sv_groups would broke without these groups
for ("svusers", "anoncvs") {
    unless (getgrnam($_)) {   
	unless ($debug || $no_etc)	{
	    system("/usr/sbin/groupadd", "-g", $etc_group_maxid, $_);
	}
	print LOG strftime "[$script] %c ---- groupadd -g $etc_group_maxid $_ (required by the savane backend)\n", localtime;
	$etc_group_maxid++; 
    }
}

# Add groups only in database, missing on the system

foreach my $group (@only_in_db){
    
    my ($name, $type, $is_public, $use_homepage, $use_cvs, $use_arch, $use_svn, $use_download) = @{$db_groups{$group}};
    
    print "DBG create: $name and web$name\n" if $debug;
    
    unless ($debug || $no_etc) {
	
	# Actually add the groups
	system("/usr/sbin/groupadd", "-g", $etc_group_maxid, $name);
	if ($webgroup) {
	    $etc_group_maxid++;
	    system("/usr/sbin/groupadd", "-g", $etc_group_maxid, "web$name");
	}
	
	# We do not at that moment create the directories, we just relying
	# on the next step
    }
    
    # Increment the gid for the next group, avoid the special value 
    # attributed to nogroup.
    $etc_group_maxid++;	
    $etc_group_maxid++ if $etc_group_maxid == $nogroup_gid;
    
    print LOG strftime "[$script] %c ---- groupadd $name,web$name\n", localtime;
}

print LOG strftime "[$script] %c - account creation done\n", localtime;


# Update existing groups, including the ones just created
# These groups are in the database and on the system.

foreach my $group (@db_groups) {
    # If we run in no etc mode, we cannot handle group missing on the system 
    my ($exists) = getgrnam($group);
    if ($no_etc && !$exists) {
	print LOG strftime "[$script] %c ---- no way to update $group, the group is missing on the system\n", localtime;

    } else {
	
	my ($name, $type, $is_public, $use_homepage, $use_cvs, $use_arch, $use_svn, $use_download, $group_dir_homepage, $group_dir_cvs, $group_dir_arch, $group_dir_svn, $group_dir_download) = @{$db_groups{$group}};
	my ($type_name, $type_id, $dir_type_homepage, $dir_type_cvs, $dir_type_arch, $dir_type_svn, $dir_type_download, $dir_homepage, $dir_cvs, $dir_arch, $dir_svn, $dir_download, $can_use_homepage, $can_use_cvs, $can_use_arch, $can_use_svn, $can_use_download) = @{$db_group_type{$type}};
        
	# If a use in the database is empty, set it to the group type can_use
	$use_homepage = $can_use_homepage if $use_homepage eq '';
	$use_cvs = $can_use_cvs if $use_cvs eq '';
	$use_arch = $can_use_arch if $use_arch eq '';
	$use_svn = $can_use_svn if $use_svn eq '';
	$use_download = $can_use_download if $use_download eq '';
	
	# If a group_dir in the database is not empty, set reset the default
	# (it means that the group does not respect the group type settings)
	$dir_homepage = $group_dir_homepage if $group_dir_homepage;
	$dir_cvs = $group_dir_cvs if $group_dir_cvs;
	$dir_arch = $group_dir_arch if $group_dir_arch;
	$dir_svn = $group_dir_svn if $group_dir_svn;
	$dir_download = $group_dir_download if $group_dir_download;
	
	print "DBG update: $name, type:$type, public:$is_public, homepage:$use_homepage, cvs:$use_cvs, arch:$use_arch, svn:$use_svn, download:$use_download\n\t $dir_homepage $dir_cvs $dir_download\n" if $debug;
	
	my $madesomething;
	
	
	# FIXME: in a future, we may create a table of method associating
	# method -> perl module -> sub name
	# Currently it is hardcoded.
	
	unless ($debug) {
	    
	    # Create the cvs area, by relying on the library, that should
	    # make the appropriate decision (ie if no %PROJECT string is
	    # provided, nothing should be done ; check if the directory does
	    # not already exists)
	    if ($use_cvs && ! $no_cvs) {
		if ($dir_type_cvs eq "basiccvs"){ 
		    $madesomething .= CvsMakeArea($name,$dir_cvs,$is_public); 
		} elsif ($dir_type_cvs eq "basicdirectory"){ 
		    $madesomething .= DownloadMakeArea($name,$dir_cvs,$is_public); 
		} elsif ($dir_type_cvs eq "cvsattic") {
		    $madesomething .= CvsMakeAreaAttic($name,$dir_cvs,$is_public);
		} elsif ($dir_type_cvs eq "savannah-gnu" or $dir_type_cvs eq "savannah-nongnu") {
		    $madesomething .= CvsMakeAreaSavannah($name,$dir_cvs,$is_public);
		} elsif ($dir_type_cvs eq "basicsvn") {
		    $madesomething .= SvnMakeArea($name,$dir_cvs,$is_public);
		} elsif ($dir_type_cvs eq "svnattic") {
		    $madesomething .= SvnMakeAreaAttic($name,$dir_svn,$is_public); 
		} elsif ($dir_type_cvs eq "svnatticwebsite") {
		    $madesomething .= SvnMakeAreaAtticWebsite($name,$dir_svn,$is_public); 
		}
	    }
	    
	    # Create the web area, by relying on the library, that should
	    # make the appropriate decision (ie if no %PROJECT string is
	    # provided, nothing should be done)
	    if ($use_homepage && ! $no_homepage) {
		if ($dir_type_homepage eq "basiccvs"){ 
		    $madesomething .= CvsMakeArea($name,$dir_homepage,$is_public); 
		} elsif ($dir_type_homepage eq "basicdirectory"){ 
		    $madesomething .= DownloadMakeArea($name,$dir_homepage,$is_public); 
		} elsif ($dir_type_homepage eq "cvsattic") {
		    $madesomething .= CvsMakeAreaAttic($name,$dir_homepage,$is_public);
		} elsif ($dir_type_homepage eq "savannah-gnu") {
		    $madesomething .= WebCvsMakeAreaSavannahGNU($name,$dir_homepage,$is_public);
		} elsif ($dir_type_homepage eq "savannah-nongnu") {
		    $madesomething .= WebCvsMakeAreaSavannahNonGNU($name,$dir_homepage,$is_public);
		} elsif ($dir_type_homepage eq "basicsvn") {
		    $madesomething .= SvnMakeArea($name,$dir_homepage,$is_public);
		} elsif ($dir_type_homepage eq "svnattic") {
		    $madesomething .= SvnMakeAreaAttic($name,$dir_svn,$is_public); 
		} elsif ($dir_type_homepage eq "svnatticwebsite") {
		    $madesomething .= SvnMakeAreaAtticWebsite($name,$dir_svn,$is_public); 
		} 
	    }
	    
	    # Create the download area, by relying on the library, that should
	    # make the appropriate decision (ie if no %PROJECT string is
	    # provided, nothing should be done)
	    if ($use_download && ! $no_download) {
		if ($dir_type_download eq "basiccvs"){ 
		    $madesomething .= CvsMakeArea($name,$dir_download,$is_public); 
		} elsif ($dir_type_download eq "basicdirectory"){ 
		    $madesomething .= DownloadMakeArea($name,$dir_download,$is_public); 
		} elsif ($dir_type_download eq "cvsattic") {
		    $madesomething .= CvsMakeAreaAttic($name,$dir_download,$is_public);
		} elsif ($dir_type_download eq "savannah-gnu" or $dir_type_download eq "savannah-nongnu") {
		    $madesomething .= DownloadMakeAreaSavannah($name,$dir_download,$is_public);
		} elsif ($dir_type_download eq "basicsvn") {
		    $madesomething .= SvnMakeArea($name,$dir_download,$is_public);
		}  elsif ($dir_type_download eq "svnattic") {
		    $madesomething .= SvnMakeAreaAttic($name,$dir_svn,$is_public);  
		}
	    }

	    # Create the arch area, by relying on the library, that should
	    # make the appropriate decision (ie if no %PROJECT string is
	    # provided, nothing should be done)
	    if ($use_arch && ! $no_arch) {
		if ($dir_type_arch eq "basiccvs"){ 
		    $madesomething .= CvsMakeArea($name,$dir_arch,$is_public); 
		} elsif ($dir_type_arch eq "basicdirectory"){ 
		    $madesomething .= DownloadMakeArea($name,$dir_arch,$is_public); 
		} elsif ($dir_type_arch eq "cvsattic") {
		    $madesomething .= CvsMakeAreaAttic($name,$dir_arch,$is_public);
		} elsif ($dir_type_arch eq "savannah-gnu" or $dir_type_arch eq "savannah-nongnu") {
		    $madesomething .= DownloadMakeAreaSavannah($name,$dir_arch,$is_public);
		} elsif ($dir_type_arch eq "basicsvn") {
		    $madesomething .= SvnMakeArea($name,$dir_arch,$is_public);
		} elsif ($dir_type_arch eq "svnattic") {
		    $madesomething .= SvnMakeAreaAttic($name,$dir_svn,$is_public); 
		}
	    }

	    # Create the svn area, by relying on the library, that should
	    # make the appropriate decision (ie if no %PROJECT string is
	    # provided, nothing should be done)
	    if ($use_svn && !$no_svn) {
		if ($dir_type_svn eq "basiccvs"){ 
		    $madesomething .= CvsMakeArea($name,$dir_svn,$is_public); 
		} elsif ($dir_type_svn eq "basicdirectory"){ 
		    $madesomething .= DownloadMakeArea($name,$dir_svn,$is_public); 
		} elsif ($dir_type_svn eq "cvsattic") {
		    $madesomething .= CvsMakeAreaAttic($name,$dir_svn,$is_public);
		} elsif ($dir_type_svn eq "savannah-gnu" or $dir_type_svn eq "savannah-nongnu") {
		    $madesomething .= DownloadMakeAreaSavannah($name,$dir_svn,$is_public);
		} elsif ($dir_type_svn eq "basicsvn") {
		    $madesomething .= SvnMakeArea($name,$dir_svn,$is_public);
		} elsif ($dir_type_svn eq "svnattic") {
		    $madesomething .= SvnMakeAreaAttic($name,$dir_svn,$is_public);
		} elsif ($dir_type_svn eq "svnatticwebsite") {
		    $madesomething .= SvnMakeAreaAtticWebsite($name,$dir_svn,$is_public); 
		}
	    }
	}
	
	print LOG strftime "[$script] %c ---- update $name ($madesomething built)\n", localtime if $madesomething;
    }
    
}


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";

# EOF
