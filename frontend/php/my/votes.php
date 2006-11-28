<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require '../include/pre.php';
require_directory("trackers");

register_globals_off();

if (user_isloggedin())
{
  $remaining_votes = trackers_votes_user_remains_count(user_getid());

  if (sane_post("submit"))
    {
      $sql = "SELECT vote_id,tracker,item_id FROM user_votes WHERE user_id='".user_getid()."' ORDER BY howmuch DESC , item_id ASC LIMIT 100 ";
      $result = db_query($sql);
      unset($count);
      
      # Build a list of votes to update: we must proceed in two step because
      # we must check that the vote count does not exceed the limit (100)
      $new_votes_list = array();
      $new_votes_list_item_id = array();
      $new_votes_list_tracker = array();

      while ($row = db_fetch_array($result)) 
	{
	  $new_vote = "new_vote_$row[vote_id]";
	  $new_vote = sane_post($new_vote);
	  $count = $count + $new_vote;
	  $new_votes_list[$row[vote_id]] = $new_vote;
	  $new_votes_list_item_id[$row[vote_id]] = $row[item_id];
	  $new_votes_list_tracker[$row[vote_id]] = $row[tracker];
	}
      
      if ($count > 100)
	{
	  fb(_("Vote count exceed limits, your changes have been discarded"), 1);
	}
      else
	{
	  while (list($vote_id,$new_vote) = each($new_votes_list)) 
	    {
	      trackers_votes_update ($new_votes_list_item_id[$vote_id],
				     0,
				     $new_vote,
				     $new_votes_list_tracker[$vote_id]);
	    }	  
	}
      
      $remaining_votes = trackers_votes_user_remains_count(user_getid());
    }

  site_user_header(array('context'=>'votes'));


  # Simple listing. No need of anything really fancy, there will be no more 
  # than hundred entries

  # The SQL is not exactly designed to save requests, just simple stuff.

  print '<p>'._("Here is the list of your votes.").' '.sprintf(ngettext("%s vote remains at your disposal.", "%s votes remain at your disposal.", $remaining_votes), $remaining_votes).'</p>';

  if ($remaining_votes < 100) 
    {
      print '<p>'._("To change your votes, type in new numbers (using zero removes the entry from your votes list).").'</p>';
      
      print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
      
      $sql = "SELECT * FROM user_votes WHERE user_id='".user_getid()."' ORDER BY howmuch DESC , item_id ASC LIMIT 100 ";
      $result = db_query($sql);
      
      while ($row=db_fetch_array($result))
	{
	  $sql = "SELECT summary,vote,status_id,priority,group_id FROM ".$row['tracker']." WHERE bug_id='".$row['item_id']."' LIMIT 1 ";
	  $res_item = db_query($sql);
	  
	  $prefix = utils_get_tracker_prefix($row['tracker']);
	  $icon = utils_get_tracker_icon($row['tracker']);
	  
	  print '<div class="'.utils_get_priority_color(db_result($res_item, 0, 'priority'), db_result($res_item, 0, 'status_id')).'">'.
	    '<input type="text" name="new_vote_'.$row['vote_id'].'" size="3" maxlength="3" value="'.$row['howmuch'].'" /> / '.($row['howmuch']+$remaining_votes).
	    '&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$GLOBALS['sys_home'].$row['tracker'].'/?func=detailitem&amp;item_id='.$row['item_id'].'">'.
	    '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.$icon.'.png" class="icon" alt="'.$row['tracker'].'" />'.
	    ' '.stripslashes(db_result($res_item, 0, 'summary')).', '.sprintf(ngettext("%s vote", "%s votes", db_result($res_item, 0, 'vote')), db_result($res_item, 0, 'vote')).'&nbsp;<span class="xsmall">('.$prefix .' #'.$row['item_id'].', '.group_getname(db_result($res_item, 0, 'group_id')).')</span></div>';
	  
	  
	}
      
#  ################################ Submit
      
      print '<br /><div align="center" class="noprint"><input type="submit" name="submit" class="bold" value="'._("Submit Changes").'" /></div></form>';
      
# End
      print "\n\n".show_priority_colors_key();
      
    }

  $HTML->footer(array());

}
else
{

  exit_not_logged_in();

}

?>
