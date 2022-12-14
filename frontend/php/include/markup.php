<?php
# Markup functions.
#
# Copyright (C) 2005-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2005-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2019, 2020, 2021, 2022 Ineiev
#
# This file is part of Savane.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# Return current markup language documentation in Full Markup format.
function markup_get_reminder ()
{
  return
"== " . _("Tag Scope") . " ==\n\n"
. str_replace("\n", ' ',
_("Every markup element should fit in a single line.  For example,
this text isn't converted in two lines of italics:")) . "\n\n"
. "+verbatim+\n"
. _("_First line
Second line_")
. "\n-verbatim-\n\n"
. _("The only exception is the verbatim blocks of text.") . "\n\n"
. "== " . _("Basic Markup") . " ==\n\n"
. _("Basic Markup tags are available almost everywhere.") . ' '
. str_replace("\n", ' ',
_("Multiple subsequent spaces and newlines are collapsed in Basic markup
into single spaces. In Rich and Full markup, they are preserved."))
. "\n\n"
. _("*bold* markup is:")
. "\n+verbatim+\n*"
. _("bold")
. "*\n-verbatim-\n\n"

. _("_italic_ markup is:")

. "\n+verbatim+\n_"
. _("italic")
. "_\n-verbatim-\n\n"

. _("URLs are transformed to links, additionally you can give them a title:")
. "\n\n"
. "\n+verbatim+\n"
. "www.gnu.org
http://www.fsf.org
[http://url " . _('Title') . ']'
. "\n-verbatim-\n\n"

. str_replace("\n", ' ',
_("Also, these texts are made links to comments
(within the same item), tracker items and files:"))

. "\n\n+verbatim+\n"
. "comment #51\n"
. "bug #1419857\n"
. "task #289\n"
. "sr #4913 support #4913\n"
. "patch #119\n"
. "file #83521\n"
. "-verbatim-\n\n"

. str_replace("\n", ' ',
_("Links to files whose names end in '.png', '.jpg', '.jpeg' (case insensitive)
are converted to HTML images, the surrounding parentheses and commas (if any)
are removed:"))
. "\n\n+verbatim+
(file #47102)
-verbatim-\n\n"
. _("You can add the 'alt' attribute within the parentheses:")
. "\n\n+verbatim+
(file #47102 " . _('Flying GNU') . ")
-verbatim-\n\n"
. "== " . _("Rich Markup") . " ==\n\n"

. _('Rich Markup tags are available in comments.') . "\n\n"

. _('Unnumbered list markup is:') . "\n\n"

. "+verbatim+\n"
. _('* item 1
* item 2
** item 2 subitem 1
** item 2 subitem 2
* item 3')
. "\n-verbatim-\n\n"

. _('Numbered list markup is:') . "\n\n"

. "+verbatim+\n"
._('0 item 1
00 item 1 subitem 1
0 item 2')
. "\n-verbatim-\n\n"

. _('Horizontal ruler markup is:') . "\n\n"

. "+verbatim+
----
-verbatim-\n\n"

. _('Verbatim markup (useful for code bits) is:') . "\n\n"

. "+verbatim+\n+verbatim+\n"
. _('seconds = 3600 *days * 24;
_printf (_("Enter something:"));')
. "\n-verbatim-\n-verbatim-\n\n"
. str_replace("\n", ' ',
_("The starting and ending verbatim marks take whole lines; the rest
text that may be on the same lines is ignored.")) . "\n\n"

. str_replace("\n", ' ',
_('The other tag that disables the markup is:')) . "\n\n"

. "+verbatim+\n+nomarkup++verba-nomarkup-tim+\n-verbatim-\n\n"

. str_replace("\n", ' ',
_('Unlike the verbatim tag, it produces no text block and can be used
within a line.')) . "\n\n"

. _('Lines starting with ">" are highlighted as quotes:')

. "\n+verbatim+\n> " . _('Quoted line.') . "\n-verbatim-\n\n"

. "== " . _('Full Markup (Heading Tags)') . " ==\n\n"

. str_replace("\n", ' ',
_("Heading tags are available in rare places like item original
submissions, news items, project description and user's resume."))

. ' ' . _('First level heading markup is:')
. "\n\n+verbatim+\n= " . _('Title') . " =\n-verbatim-\n\n"

. _('Second level heading markup is:')
. "\n\n+verbatim+\n== " . _('Subtitle') . " ==\n-verbatim-\n\n"

. _('Third level heading markup is:')

. "\n\n+verbatim+\n=== " . _('Subsubtitle') . " ===\n-verbatim-\n\n"

. _('Fourth level heading markup is:')

. "\n\n+verbatim+\n==== " . _('Subsubsubtitle') . " ====\n-verbatim-\n\n"
;
}

# Functions to allow users to format the text in a secure way:
#    markup_basic() for very light formatting;
#    markup_rich() for formatting excepting headers;
#    markup_full() for full formatting, including headers.

# Tell the user what is the level of markup available in a uniform way.
# Takes as argument the level, being full / rich / basic / none.
# To avoid making page looking strange, we will put that only on textarea
# where it is supposed to be the most useful.
function markup_info($level)
{
  $link_head = '<a target="_blank" href="/markup-test.php">';
  $link_tail = '</a>';

  if ($level == 'basic')
    {
      $string = _("Basic Markup");
      $text = _("Only basic text tags are available in this input field.");
    }
  elseif ($level == 'rich')
    {
      $string = _("Rich Markup");
      $text = _("Rich and basic text tags are available in this input field.");
    }
  elseif ($level == 'full')
    {
      $string = _("Full Markup");
      $text = _("Every tags are available in this input field.");
    }
  elseif ($level == 'none')
    {
      $string = _("No Markup");
      $text = _("No tags are available in this input field.");
      $link_head = '';
      $link_tail = '';
    }

  $img = html_image ('misc/edit.png', ['class' => 'icon']);
  return '<span class="smaller">('
    . utils_help ("$link_head$img$string$link_tail", $text)
    . ')</span>';
}

# Convert special markup characters in the input text to real HTML.
#
# The following syntax is supported:
# * *word* -> <strong>word</strong>
# * _word_ -> <em>word</em>
# * [http://gna.org/] -> <a href="http://gna.org/">http://gna.org/</a>
# * [http://gna.org/ text] -> <a href="http://gna.org/">text</a>
# * (bug|task|...) #1234 -> Link to corresponding page
function markup_basic($text)
{
  $lines = explode("\n", $text);
  $result = array();

  foreach ($lines as $line)
    {
      $result[] = _markup_inline($line);
    }
  return join("\n", $result);
}

# Convert special markup characters in the input text to real HTML.
#
# This function does the same markup as markup_basic(), plus
# it supports the following:
# * paragraphs
# * lists (<ul> and <ol>)
# * nested lists
# * horizontal rulers
function markup_rich($text)
{
  return markup_full($text, false);
}

# Transform spaces so that they are hopefully preserved in HTML.
function markup_preserve_spaces ($buf)
{
  $buf = preg_replace ('/  *(\n|$)/', '$1', $buf);
  $buf = preg_replace ('/ /', '&nbsp;', $buf);
  $buf = preg_replace ('/(([&]nbsp;)*)[&]nbsp;/', '$1 ', $buf);
  $buf = preg_replace ('/(\n) /', '$1&nbsp;', $buf);
  $buf = preg_replace ('/^((<p>)?) /', '$1&nbsp;', $buf);
  return $buf;
}

# Compile HTML text for a verbatim block, append it to $result;
# the function is used further in markup_full ().
function markup_build_verbatim (&$verbatim_buffer, &$context_stack, &$result)
{
  $line = join ("\n", $context_stack);
  array_shift ($context_stack);

  # Unify line breaks.
  $verbatim_buffer = str_replace ("\r\n", "\n", $verbatim_buffer);
  $verbatim_buffer = str_replace ("\n\r", "\n", $verbatim_buffer);
  $verbatim_buffer = str_replace ("\r", "\n", $verbatim_buffer);
  # Hopefully preserve spaces in HTML allowing line breaking.
  $verbatim_buffer = str_replace ("\t", "        ",
                                  $verbatim_buffer);
  # The leading space will be collapsed in markup_preserve_spaces.
  $verbatim_buffer = ' ' . markup_preserve_spaces ($verbatim_buffer);

  # Preserve line breaks.
  $verbatim_buffer = str_replace ("\n", "<br />\n", $verbatim_buffer);
  # Take into account unclosed paragraphs of surrounding text.
  $closure = $aperture = $prev_line = "";
  if (count ($result) > 0)
    $prev_line = $result[count($result) - 1];
  $len = strlen ($prev_line);
  if ($len >= 6 && substr ($prev_line, $len - 6) === '<br />')
    {
      $closure = "</p>\n";
      $aperture = "<p>";
    }
  $result [] =
    "$closure<blockquote class='verbatim'>"
    . "<p>$verbatim_buffer</p></blockquote>\n$aperture";
  $verbatim_buffer = '';
}

# Convert special markup characters in the input text to real HTML.
#
# This function does exactly the same markup as markup_rich()
# when !$allow_headings, plus it converts headings to <h2> ... <h5>
# when $allow_headings.
function markup_full($text, $allow_headings = true)
{
  $verb_tag = 'verbatim';
  $no_markup_magic = 'no-1a4f67a7-4eae-4aa1-a2ef-eecd8af6a997-markup';
  $lines = explode ("\n", $text);
  $result = array();

  # We use a stack (last in, first out) to track the current
  # context (paragraph, lists) so we can correctly close tags.
  $context_stack = array();

  $quoted_text = false;
  $verbatim = 0;
  extract(sane_import('request', [true => 'printer']));
  $verbatim_buffer = '';
  foreach ($lines as $index => $line)
    {
      $found = strpos ($line, "+$verb_tag+") !== false;
      if ($found)
        $verbatim++;
      # The verbatim tags are not allowed to be nested, because
      # they are translated to HTML code that isn't allowed to be nested.
      if ($verbatim == 1 && $found)
        {
          $line = join("\n", $context_stack);

          if (empty ($printer))
            array_unshift ($context_stack, '</textarea>');
          else
            array_unshift ($context_stack, '</pre>');

          # Jump to the next line, ignoring the rest of the line.
          continue;
        }

      if (strpos ($line, "-$verb_tag-") !== false && --$verbatim <= 0)
        {
          markup_build_verbatim ($verbatim_buffer, $context_stack, $result);
          continue;
        }

      # If we're in the verbatim markup, don't apply the markup.
      if ($verbatim)
        {
          # Disable the +nomarkup+ tags by inserting a unique string.
          # This has to be done in the original string, because that
          # is the one which will be split upon the +nomarkup+ tags,
          # see below.
          $escaped_line = str_replace ('nomarkup', $no_markup_magic, $line);
          $lines[$index] = $escaped_line;
          $verbatim_buffer .= "$escaped_line\n";
          continue;
        }
      # Normal run, do the markup.
      $line =
        _full_markup ($line, $allow_headings, $context_stack, $quoted_text);
      $result[] = markup_preserve_spaces ($line);
    } # foreach ($lines as $index => $line)

  if ($verbatim) # Missing "-$verb_tag-": append accumulated text.
    markup_build_verbatim ($verbatim_buffer, $context_stack, $result);

  # Make sure that all previously used contexts get their
  # proper closing tag by merging in the last closing tags.
  $markup_text = join("\n", array_merge($result, $context_stack));

  # It's easiest to markup everything, without supporting the nomarkup
  # tag. afterwards, we replace every nomarkup tag pair with the content
  # between those tags in the original string.
  $original = preg_split('/([+-]nomarkup[+-])/', join("\n", $lines), -1,
    PREG_SPLIT_DELIM_CAPTURE);
  $markup = preg_split('/([+-]nomarkup[+-])/', $markup_text, -1,
    PREG_SPLIT_DELIM_CAPTURE);
  # Save the HTML tags from the last element in the markup array, see below.
  $last_tags = $markup[count($markup)-1];
  $nomarkup_level = 0;

  foreach ($original as $index => $original_text)
    {
      # Keep track of nomarkup tags.
      if ($original_text == '+nomarkup+') $nomarkup_level++;
      if ($original_text == '-nomarkup-') $nomarkup_level--;

      # If the current match is the nomarkup tag, we don't want it to
      # show up in the markup text -> set it to an empty string.
      if (preg_match('/([+-]nomarkup[+-])/', $original_text))
        {
          $markup[$index] = '';
          $original_text = '';
        }
      # While we're in a nomarkup environment, the already marked up text
      # needs to be replaced with the original content. Also, we need
      # to add <br />  tags for newlines.
      if ($nomarkup_level > 0)
        {
          $markup[$index] = nl2br($original_text);
        }
    }

  # Normally, $nomarkup_level must be zero at this point. However, if
  # the user submits wrong markup and forgets to close the -nomarkup-
  # tag, we need to take care of that.
  # To do this, we need to look for closing tags which have been deleted.
  if ($nomarkup_level > 0)
    {
      $trailing_markup = array_reverse(explode ("\n", $last_tags));
      $restored_tags = '';
      foreach ($trailing_markup as $tag)
        {
          if (preg_match('/^\s*<\/[a-z]+>$/', $tag))
            {
              $restored_tags = "\n$tag$restored_tags";
            }
          else
            {
              $markup[] = $restored_tags;
              break;
            }
        }
    }

  # Lastly, revert the escaping of +nomarkup+ tags done above
  # for verbatim environments.
  return str_replace ($no_markup_magic, 'nomarkup', join ('', $markup));
}

# Convert whatever content that can contain markup to a valid text output
# It won't touch what seems to be valid in text already, or what cannot
# be converted in a very satisfactory way.
# This function should be minimal, just to avoid weird things, not to do
# very fancy things.
function markup_textoutput ($text)
{
  $lines = explode("\n", $text);
  $result = array();

  $protocols = "https?|ftp|sftp|file|afs|nfs";
  $savane_tags = "verbatim|nomarkup";

  foreach ($lines as $line)
    {
      # Handle named hyperlink.
      $line =
        preg_replace(
              # Find the opening brace '['
                     '/\['
              # followed by the protocol, either http:// or https://
                     .'(('.$protocols.'):\/\/'
              # match any character except whitespace or the closing
              # brace ']' for the actual link
                     .'[^\s\]]+)'
              # followed by at least one whitespace
                     .'\s+'
              # followed by any character (non-greedy) and the
              # next closing brace ']'.
                     .'(.+?)\]/', '$3 <$1>', $line);

      # Remove savane-specific tags.
      $line = preg_replace('/\+('.$savane_tags.')\+/', '', $line);
      $line = preg_replace('/\-('.$savane_tags.')\-/', '', $line);
      $result[] = $line;
    }
  return join("\n", $result);
}

# Internal function for recognizing and formatting special markup
# characters in the input line to real HTML.
#
# This function is a helper for markup_full() and should
# not be used otherwise.
function _full_markup($line, $allow_headings, &$context_stack, &$quoted_text)
{
  # Context formatting.

  # The code below marks up recognized special characters,
  # by starting a new context (e.g. headings and lists).

  # Generally, we want to start a new paragraph. this will be set
  # to false, if a new paragraph is no longer appropriate, like
  # for headings or lists.
  $start_paragraph = true;

  # Match the headings, e.g. === heading ===.
  if ($allow_headings)
    {
      $line = _markup_headings($line, $context_stack, $start_paragraph);
    }
  # Match list items.
  $line = _markup_lists($line, $context_stack, $start_paragraph);

  # Replace four '-' sign with a horizontal ruler.
  if (preg_match('/^----\s*$/', $line))
    {
      $line = join("\n", $context_stack).'<hr />';
      $context_stack = array();
      $start_paragraph = false;
    }

  # Inline formatting.

  # The code below marks up recognized special characters,
  # without starting a new context (e.g. <strong> and <em>).
  $line = _markup_inline($line);

  # Paragraph formatting.

  # The code below is responsible for doing the Right Thing(tm)
  # by either starting a new paragraph and closing any previous
  # context or continuing an existing paragraph.

  # Change the quoteing mode when the line start with '>'.
  if (substr($line, 0, 4) == '&gt;')
    {
      # If the previous line was not quoted, start a new quote paragraph.
      if (!$quoted_text)
        {
          $line = join("\n", $context_stack)
                  . "<blockquote><p class=\"quote\">$line";
          # Empty the stack.
          $context_stack = array('</p></blockquote>');
          $start_paragraph = false;
        }
      $quoted_text = true;
    }
  else
    {
      # If the previous line was quoted, end the quote paragraph.
      if ($quoted_text and $start_paragraph and $line != '')
        {
          $line = join("\n", $context_stack)."\n<p>$line";
          # Empty the stack.
          $context_stack = array('</p>');
        }
      $quoted_text = false;
    }
  # Don't start a new paragraph again, if we already did that.
  if (isset ($context_stack[0]) && substr ($context_stack[0], 0, 4) == '</p>')
    {
      $start_paragraph = false;
    }
  # Add proper closing tags when we encounter an empty line.
  # note that there might be no closing tags, in this case
  # the line will remain emtpy.
  if (preg_match('/^(|\s*)$/', $line))
    {
      $line = join("\n", $context_stack)."$line";
      # Empty the stack.
      $context_stack = array();
      $start_paragraph = false;
    }
  # Finally start a new paragraph if appropriate.
  if ($start_paragraph)
    {
      # Make sure that all previously used contexts get their
      # proper closing tag.
      $line = join("\n", $context_stack)."<p>$line";
      # Empty the stack.
      $context_stack = array('</p>');
    }
  # Append a linebreak while in paragraph mode.
  if (isset ($context_stack[0]) && substr ($context_stack[0], 0, 4) == '</p>')
    {
      $line .= '<br />';
    }
  return $line;
}

# Internal function for recognizing and formatting headings.
#
# This function is a helper for _full_markup() and should
# not be used otherwise.
function _markup_headings($line, &$context_stack, &$start_paragraph)
{
  if (preg_match(
    # Find one to four '=' signs at the start of a line
    '/^(={1,4})'
    # followed by exactly one space
    .' '
    # followed by any character
    .'(.+)'
    # followed by exactly one space
    .' '
    # followed by one to four '=' signs at the end of a line (whitespace allowed).
    .'(={1,4})\s*$/', $line, $matches))
    {
      $header_level_start = max(min(strlen($matches[1]), 4), 1);
      $header_level_end = strlen($matches[3]);
      if ($header_level_start == $header_level_end)
        {
          # If the user types '= heading =' (one '=' sign), it will
          # actually be rendered as a level 2 heading <h2>.
          $header_level_start += 1;
          $header_level_end += 1;

          $line = "<h$header_level_start>$matches[2]</h$header_level_end>";
          # Make sure that all previously used contexts get their
          # proper closing tag.
          $line = join("\n", $context_stack).$line;
          # Empty the stack.
          $context_stack = array();
          $start_paragraph = false;
        }
    }
  return $line;
}

# Internal function for recognizing and formatting lists.
#
# This function is a helper for _full_markup() and should
# not be used otherwise.
function _markup_lists($line, &$context_stack, &$start_paragraph)
{
  if (preg_match('/^\s?([*0]+) (.+)$/', $line, $matches))
    {
      # Determine the list level currently in use.
      $current_list_level = 0;
      foreach ($context_stack as $context)
        {
          if ($context == '</ul>' or $context == '</ol>')
            {
              $current_list_level++;
            }
        }
      # Determine whether the user list levels match the list
      # level we have in our context stack.
      #
      # This will catch (potential) errors of the following form:
      # * list start
      # 0 maybe wrong list character
      # * list end
      $markup_position = 0;
      foreach (array_reverse($context_stack) as $context)
        {
          # We only care for the list types.
          if ($context != '</ul>' and $context != '</ol>')
            {
              continue;
            }

          $markup_character = substr($matches[1], $markup_position, 1);

          if (($markup_character === '*' and $context != '</ul>')
              or ($markup_character === '0' and $context != '</ol>'))
            {
              # Force a new and clean list start.
              $current_list_level = 0;
              break;
            }
          else
            {
              $markup_position++;
            }
        }

      # If we are not in a list, close the previous context.
      $line = '';
      if ($current_list_level == 0)
        {
          $line = join("\n", $context_stack);
          $context_stack = array();
        }
      # Determine the list level the user wanted.
      $wanted_list_level = strlen($matches[1]);

      # Start a new list and make sure that the markup
      # is valid, even if the user did skip one or more list levels.
      $list_level_counter = $current_list_level;
      while ($list_level_counter < $wanted_list_level)
        {
          switch (substr($matches[1], $list_level_counter, 1))
            {
              case '*':
                $tag = 'ul';
                break;
              case '0':
                $tag = 'ol';
                break;
            }
          $line .= "<$tag>\n<li>";
          array_unshift($context_stack, "</$tag>");
          array_unshift($context_stack, "</li>");
          $list_level_counter++;
        }
      # End a previous list and make sure that the markup
      # is valid, even if the user did skip one or more list levels.
      $list_level_counter = $current_list_level;
      while ($list_level_counter > $wanted_list_level)
        {
          $line .= array_shift($context_stack)."\n"
            .array_shift($context_stack)."\n";
          $list_level_counter--;
        }
      # Prepare the next item of the same list level.
      if ($current_list_level >= $wanted_list_level)
        {
          $line .= "</li>\n<li>";
        }
      # Finally, append the list item.
      $line .= $matches[2];
      $start_paragraph = false;
    }
  return $line;
}

# Internal function for recognizing and formatting inline tags and links.
#
# This function is a helper for _full_markup() and should
# not be used otherwise.
function _markup_inline($line)
{
  # Group_id may be necessary for recipe #nnn links.
  global $group_id;

  $comingfrom = '';
  if ($group_id)
    $comingfrom = "&amp;comingfrom=$group_id";

  if (strlen($line) == 0)
    return;
  # Replace references to image files with <img>.
  preg_match_all ('/\(?((files? ))#(?P<file_id>\d+)'
                  . '(?P<comment>[^),]*)((\)|, )?)/',
                  $line, $matches);
  foreach ($matches['file_id'] as $key => $file_id)
    {
      $result = db_execute("SELECT filename FROM trackers_file
                            WHERE file_id=? LIMIT 1", array ($file_id));
      if (!$result || db_numrows ($result) < 1)
        continue;
      $file_name = db_result ($result, 0, 0);
      if (!(preg_match ('/\.(jpe?g|png)$/', strtolower($file_name))))
        continue;
      $alt = $matches['comment'][$key];
      if (substr ($alt, 0, 1) === ' ')
        $alt = substr ($alt, 1);
      if ($alt !== '')
        $alt = 'alt="' . htmlspecialchars ($alt) . '" ';
      $line = preg_replace ('/\(?((files? ))#' . $file_id . '[^),]*((\)|, )?)/',
                            '<img src="/file/' . htmlspecialchars ($file_name)
                            . '?file_id=' . $file_id . '" ' . $alt . '/> ',
                            $line);
    }

  # Regexp of protocols supported in hyperlinks (should be protocols that
  # we can expect web browsers to support).
  $protocols = "https?|ftp|sftp|file|afs|nfs";

  # Artificial protocol for protocol-relative links.
  $protocol_relative = "p-r";
  # Make sure $line doesn't contain $protocol_relative.
  $pr_esc = "p-&#83521;-r";
  $line = str_replace ($protocol_relative, $pr_esc, $line);

  # Reword "//" as artificial "protocol".
  $line = preg_replace('#(^|\s|\[)//#', '$1' . $protocol_relative . '://', $line);
  $protocols .= '|' . $protocol_relative;

  # Links between items.
  # FIXME: It should be i18n, but in a clever way, meaning that everytime
  # a form is submitted with such string, the string get converted in
  # english so we always get the links found without having a regexp
  # including every possible language.
  $trackers = array (
      "bugs?" => "bugs/?",
      "support|sr" => "support/?",
      "tasks?" => "task/?",
      "recipes?|rcp" => "cookbook/?func=detailitem$comingfrom&amp;item_id=",
      "patch" => "patch/?",
      # In this case, we make the link pointing to support, it won't matter,
      # the download page is in every tracker and does not check if the tracker
      # is actually used.
      "files?" => "support/download.php?file_id=",
  );
  $artifact_regex = implode ('|', array_keys($trackers)).'|comments?';

  # Modify link texts to disable interpreting them as nested links.
  $line = preg_replace_callback ('/(\[(('
        .$protocols.'|www\.)[^\s]+'
        . '|((' . $artifact_regex . ')\s{0,2}#[0-9]+))\s+)(.*?)\]/',
        function ($matches)
          {
        # Replace '#' in link texts with HTML references;
        # if we don't, we may get links like
        # [bug #3 bug #1] ->
        # <em><a href="/bugs/?3"><em><a href="/bugs/?1">bug #1</a></em></a></em>
            $tail = preg_replace ('/(^|[^&])#/', '$1&#35;', $matches[6]);
        # Add '&#32;' before each word to disable interpreting it as
        # a link in texts like
        # [https://www.gnu.org/home.html home page for www.gnu.org]
            $tail = preg_replace ('/(^|\s)([^\s])/', '$1&#32;$2', $tail);
            return $matches[1].$tail.']';
          }, $line);

  # Prepare usual links: prefix "www." with $protocol_relative . "://"
  # if it is preceded by [ or whitespace or at the beginning of line
  # (don't want to prefix in cases like "//www.." or "ngwww...").
  $line = preg_replace('/(^|\s|\[)(www\.)/i',
                       '$1' . $protocol_relative . '://$2', $line);

  # Prepare the markup for normal links, e.g. http://test.org, by
  # surrounding them with braces []
  # (& = begin of html entities, it means a end of string unless
  # it is &amp; which itself is the entity for &)
  $line = preg_replace('/(^|[^;\[\/])((' . $protocols
                       . '):\/\/(&amp;|[^\s&]+[a-z0-9\/^])+)/i',
    '$1[$2]', $line);
  # Remove spaces added with preg_replace_callback
  # and process links with preceding ';'.
  $line = preg_replace ('/&#32;/', '', $line);
  $line = preg_replace('/(;)(((' . $protocols
                       . '):)?\/\/(&amp;|[^\s&]+[a-z0-9\/^])+)/i',
    '$1[$2]', $line);

  # Replace the @ sign with an HTML entity, if it is used within
  # an URL (e.g. for pointers to mailing lists).  This way, the
  # @ sign doesn't get mangled in the e-mail markup code
  # below.
  $line = preg_replace ("#((" . $protocols . ")://[^<>[:space:]]+)@#i",
                        "$1&#64;", $line);

  # Do a markup for mail links, e.g. info@support.org
  # (do not use utils_emails, this does extensive database
  # search on the string
  # and replace addresses in several fashion. Here we just want to make
  # a link). Make sure that 'cvs -d:pserver:anonymous@cvs.sv.gnu.org:/...'
  # is NOT replaced.
  $line = preg_replace("/(^|\s)([a-z0-9_+-.]+@([a-z0-9_+-]+\.)+[a-z]+)(\s|$)/i",
                       '\1' . utils_email_basic('\2') . '\4', $line);

  # Unreplace the @ sign.
  $line = preg_replace ("%((" . $protocols . ")://[^<>[:space:]]+)[&]#64;%i",
                        "$1@", $line);

  foreach ($trackers as $regexp => $link)
    {
      # Allow only two white spaces between the string and the numeric id
      # to avoid having too time consuming regexp. People just have to pay
      # attention.

      # Handle named links.
      $line = preg_replace("/(^|\s|\W)\[($regexp)\s{0,2}#([0-9]+)\s+(.+?)\]/i",
        '$1<em><a href="'.$GLOBALS['sys_home'].$link.'$3">$4</a></em>', $line);

      # Now process "usual" links.
      $line = preg_replace("/(^|\s|\W)($regexp)\s{0,2}#([0-9]+)/i",
        '$1<em><a href="'.$GLOBALS['sys_home']
        .$link.'$3">$2&nbsp;#$3</a></em>', $line);
    }

  # Add an internal link for comments.
  $line = preg_replace("/(^|\s|\W)\[(comments?)\s{0,2}#([0-9]+)\s+(.+?)\]/i",
    '$1<em><a href="#comment$3">$4</a></em>', $line);
  $line = preg_replace('/(comments?)\s{0,2}#([0-9]+)/i',
    '<em><a href="#comment$2">$1&nbsp;#$2</a></em>', $line);

  # Add support for named hyperlinks, e.g.
  # [http://gna.org/ Text] -> <a href="http://gna.org/">Text</a>
  $line = preg_replace(
    # Find the opening brace '['
    '/\['
    # followed by the protocol
    . '(((' . $protocols . '):)?\/\/'
    # match any character except whitespace or the closing
    # brace ']' for the actual link
    .'[^\s\]]+)'
    # followed by at least one whitespace
    .'\s+'
    # followed by any character (non-greedy) and the
    # next closing brace ']'.
    .'(.+?)\]/', '<a href="$1">$4</a>', $line);

  # Add support for unnamed hyperlinks, e.g.
  # [http://gna.org/] -> <a href="http://gna.org/">http://gna.org/</a>
  # We make sure the string is not too long, otherwise we cut
  # it.
  $line = preg_replace_callback(
    # Find the opening brace '['
    '/\['
    # followed by the protocol
    . '(((' . $protocols . '):)?\/\/'
    # match any character except whitespace (non-greedy) for
    # the actual link, followed by the closing brace ']'.
    . '([^\s]+?))\]/', function ($match_arr) use ($protocol_relative)
                      {
                        $url = $match_arr[1];
                        $string = $url;
                        if ($match_arr[3] == $protocol_relative)
                          $string = $match_arr[4];
                        return '<a href="' . $url . '">' . $string . '</a>';
                      }, $line);

  $line = str_replace ($protocol_relative . "://", "//", $line);
  $line = str_replace ($pr_esc, $protocol_relative, $line);

  # *word* -> <strong>word</strong>
  $line = preg_replace(
    # Find an asterisk after a space or starting the line
    '/(^|\s)\*'
    # then one character (except a space or asterisk)
    .'([^* ]'
    # then (optionally) any character except asterisk
    .'[^*]*?)'
    # then an asterisk.
    .'\*/', '$1<strong>$2</strong>', $line);

  # _word_ -> <em>word</em>
  $line = preg_replace(
    # Allow for the pattern to start at the beginning of a line.
    # if it doesn't start there, the character before the slash
    # must be either whitespace or the closing brace '>', to
    # allow for nested html tags (e.g. <p>_markup_</p>).
    # Additionally, the opening brace may appear.
    # See bug #10571 on http://gna.org/ for reference.
    '/(^|\s+|>|\()'
    # match the underscore
    .'_'
    # match any character (non-greedy)
    .'(.+?)'
    # match the ending underscore and either end of line or
    # a non-word character
    .'_(\W|$)/', '$1<em>$2</em>$3', $line);
  if ($GLOBALS['sys_default_domain'] != $GLOBALS['sys_file_domain'])
    {
      $http = session_issecure()? 'https': 'http';
      $line = preg_replace('/<img src="\/file/',
                           '<img src="' . $http . "://"
                           . $GLOBALS['sys_file_domain'] . '/file', $line);
    }
  return $line;
}

# Process a single line for markup_ascii ().
function markup_ascii_line ($line, &$item_no)
{
  # Purge all list indices starting from $n.
  $markup_trim_item_no = function (&$item_no, $n)
  {
    $cnt = count ($item_no);
    for ($i = $n; $i < $cnt; $i++)
      unset ($item_no[$i]);
  };
  $indent_base = "\t";
  if (!preg_match('/^\s?([0]+) (.+)$/', $line, $matches))
    {
      $item_no = [];
      return $line;
    }
  $n = strlen ($matches[1]);
  $markup_trim_item_no ($item_no, $n);
  $n--;
  if (empty ($item_no[$n]))
    $item_no[$n] = 0;
  $item_no[$n]++;
  return str_repeat ($indent_base, $n) . "{$item_no[$n]}. {$matches[2]}";
}

# Implement applicable parts of tracker comment in ASCII, which currently
# amounts to enumerations in ordered lists, Savannah sr #110621.
function markup_ascii ($text)
{
  $lines = explode ("\n", htmlspecialchars_decode ($text, ENT_QUOTES));
  $item_no = [];
  $ret = '';
  foreach ($lines as $line)
    $ret .= markup_ascii_line ($line, $item_no) . "\n";
  return $ret;
}
?>
