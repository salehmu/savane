<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2002-2005 (c) Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can reprintdistribute it and/or
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

require_once('../include/init.php');
register_globals_off();
extract(sane_import('get', array('user_id')));

#input_is_safe();
#mysql_is_safe();

# Check whether the user exists
if (!$user_id)
{
  exit_error(_("User not found."));
}
$result = db_execute("SELECT user_name,gpg_key FROM user WHERE user_id=?",
		     array($user_id));

if (!$result || db_numrows($result) < 1)
{
  exit_error(_("User not found."));
}

# Check whether a gpg key was registered
if (!db_result($result,0,'gpg_key'))
{
  exit_error(_("This user hasn't registered a GPG key."));
}

# If we get here, a key exists. Simply print it.
header('Content-Type: application/pgp-keys');
header('Content-Disposition: filename='.db_result($result, 0, 'user_name').'-key.gpg');
header('Content-Description: GPG Key of the user '.db_result($result, 0, 'user_name'));
print db_result($result,0,'gpg_key');
