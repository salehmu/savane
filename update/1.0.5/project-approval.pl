#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004 (c) Mathieu Roy <yeupou@gnu.org> 
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
use Savannah;
use Savannah::DB;

my $group_id = GetGroupSettings(GetConf("sys_unix_group_name"),"group_id");
our $dbd;

print "Error, unable to determine the unix group id. Check your configuration.\n" unless $group_id ne "";

my $sql = "INSERT INTO task_field_value (bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (103,".$group_id.",1,'Project Approval','Pending project registration',11,'P')";
print "I am going to run:\n$sql\n\n";
print "If this script worked, a category \"Project Approval\" should now be available\nfor the group ".GetConf("sys_unix_group_name").".\nDo not run me more than once!\n";


$dbd->do($sql);
