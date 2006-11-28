<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require "../../include/pre.php";
require "../../include/account.php";

# get current information
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1)
{
  exit_error(_("Invalid Group"));
}

#if the project isnt active, require you to be a member of the super-admin group
if (!(db_result($res_grp,0,'status') == 'A'))
{
  session_require (array('group'=>1));
}

session_require(array('group'=>$group_id));

site_project_header(array('group'=>$group_id,'context'=>'ahome'));

print '<p>'._("You can view/change all of your project configuration from here.").'</p>';
utils_get_content("project/admin/index_misc");


###############################

print "\n\n".html_splitpage(1);

print $HTML->box_top(_("Features"));

# Activate features
print '<a href="editgroupfeatures.php?group='.$group_name.'">'._("Select Features").'</a>';
print '<p class="smaller">'._("Define which features you want to use for this project.").'</p>';

unset($i);
print $HTML->box_nextitem(utils_get_alt_row_color($i));

# Feature-specific configuration
$features = array("cookbook" => _("Cookbook"),
		  "support" => _("Support Tracker"),
		  "bugs" => _("Bug Tracker"),
		  "task" => _("Task Manager"),
		  "patch" => _("Patch Tracker"),
		  "news" => _("News Manager"),
		  "mail" => _("Mailing Lists"));
unset($link);
while (list($case, $name) = each($features))
{
  if ($project->Uses($case) || $case == "cookbook")
    {
      $link .= '<a href="../../'.$case.'/admin/?group='.$group_name.'">'.$name.'</a>, ';
    }
}
$link = rtrim($link, ', ');
print sprintf(_("Configure Features: %s"), $link);
print '<p class="smaller">'._("You can manage fields used, define query forms, manage mail notifications, etc.").'</p>';

$i++;

# Mail notifs
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="editgroupnotifications.php?group='.$group_name.'">'._("Set Notifications").'</a>';
print '<p class="smaller">'._("For many features, you can modify the type of email notification (global/per category), the related address lists and the notification triggers.").'</p>';


$i++;

# Conf copy
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="conf-copy.php?group='.$group_name.'">'._("Copy Configuration").'</a>';
print '<p class="smaller">'._("Copy the configuration of trackers of other projects you are member of.").'</p>';


print $HTML->box_bottom();
print "<br />\n";



print html_splitpage(2);

unset($i);
###############################
print $HTML->box_top(_('Information'));

# Public info
print '<a href="editgroupinfo.php?group='.$group_name.'">'._("Edit Public Information").'</a>';
print '<p class="smaller">'.sprintf(_("Your current short description is: %s"), db_result($res_grp,0,'short_description'));
print '</p>';

unset($i);
print $HTML->box_nextitem(utils_get_alt_row_color($i));
# Public info
print '<a href="history.php?group='.$group_name.'">'._("Show History").'</a>';
print '<p class="smaller">'._("This allows you to keep tracks of important changes occuring on your project configuration.").'</p>';

print $HTML->box_bottom();


print '<br />';

unset($i);
###############################
print $HTML->box_top(_('Members'));

# Add/Remove members
print '<a href="useradmin.php?group='.$group_name.'">'._("Manage Members").'</a>';
print '<p class="smaller">'. _("Add, remove members, approve or reject requests for inclusion.").'</p>';

unset($i);
print $HTML->box_nextitem(utils_get_alt_row_color($i));
# Create/Delete Squad, add members to squads
print '<a href="squadadmin.php?group='.$group_name.'">'._("Manage Squads").'</a>';
print '<p class="smaller">'._("Create and delete squads, add members to squads. Members of a squad will share this squad's items assignation, permissions, etc.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
# Edit permissions members
print '<a href="userperms.php?group='.$group_name.'">'._("Set Permissions").'</a>';
print '<p class="smaller">'._("Set members and group default permissions, set posting restrictions.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
# Add job offers
print '<a href="../../people/createjob.php?group='.$group_name.'">'._("Post Jobs").'</a>';
print '<p class="smaller">'._("Add a job offer.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
# Job offers list
print '<a href="../../people/editjob.php?group='.$group_name.'">'._("Edit Jobs").'</a>';
print '<p class="smaller">'._("Edit jobs offers for this project.").'</p>';


print $HTML->box_bottom();


print html_splitpage(3);

###############################

site_project_footer(array());

?>
