<?
/*
Plugin Name: Easy Filter
Plugin URI: http://assorted.sf.net/wp-easy-filter/
Description: Easy filtering of blog posts.
Version: 1.0
Author: Yang Zhang
Author URI: http://www.mit.edu/~y_z/
*/

/*
Copyright 2008 Yang Zhang

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

#
# Configuration options.
#

$tag2cmd = array(
  'pandoc' => '/mit/y_z/.local/armed/bin/mit-pandoc -S --tab-stop=2'
);

#
# More configuration options.
#

$debug = false;
$debugfile = '/mit/y_z/web_scripts/wp/wp-content/plugins/easyfilt/log.txt';
add_filter('the_content', 'filter_custom', 1);
remove_filter('the_content', 'wpautop');

#
# Constants.
#

$descriptorspec = $descriptorspec = array(
  0 => array("pipe", "r"), // stdin is a pipe that the child will read from
  1 => array("pipe", "w"), // stdout is a pipe that the child will write to
  2 => array("pipe", "w")  // stderr is a file to write to
);

#
# Custom filter.
#

function filter_custom($content) {
  global $debug, $tag2cmd, $wp_filter, $descriptorspec;
  $lines = explode("\n", $content);
  $tag = trim(substr($lines[0], 2));
  if (substr($lines[0], 0, 2) === '#!' && $tag2cmd[$tag]) {
    $cmd = $tag2cmd[$tag];
    $process = proc_open($cmd, $descriptorspec, $pipes);
    $body = implode("\n", array_slice($lines, 1));
    if (is_resource($process)) {
      fwrite($pipes[0], $body);
      fclose($pipes[0]);

      $filtered = stream_get_contents($pipes[1]);
      fclose($pipes[1]);

      $retval = proc_close($process);
    }
  } else {
    $filtered = $content;
  }
  if ($debug) {
    $f = fopen($debugfile, 'w');
    fwrite($f, print_r($wp_filter, true));
    fwrite($f, print_r($lines, true));
    fwrite($f, substr($lines[0], 0, 2) . " " . (substr($lines[0], 0, 2) === '#!') . " " .  $cmd . " " . $tag2cmd[$cmd]);
    fwrite($f, "\n===\n$content\n===\n$filtered");
    fclose($f);
  }
  return $filtered;
}
?>
