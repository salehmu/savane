#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: index.pl 4566 2005-06-30 16:57:48Z toddy $
#
# Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
#   USA
#
# $Id: perl-template.pl,v 1.4 2005/04/23 09:52:15 moa Exp $

## Functions required for the perl frontend part

use strict;
use CGI qw(:standard Link);
use CGI::Carp qw(fatalsToBrowser);

# Load modules
use Savane;
use Savane::Session;
use Savane::Frontend::Exit;

return "true";
