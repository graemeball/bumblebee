<?php
/**
* Footer HTML that is included on every page
*
* this is only a sample implementation giving credit to the Bumblebee project
* and some feedback on what Bumblebee has been managing.
*
* This is GPL'd software, so it is *not* a requirement that you give credit to
* Bumblebee, link to the site etc. In fact, this is in the theme/ directory to
* allow you to customise it easily, without having to delve into the rest of
* the code.
*
* @author     Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage theme
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/**
* Use the SystemStats object to create some nice info about what the
* system does.
*/
include_once 'inc/systemstats.php';

require_once 'inc/bb/configreader.php';
$conf = ConfigReader::getInstance();

print "<div id='bumblebeefooter'>";
print "<p>";
printf(
  T_(
    'System managed by <a href="http://bumblebeeman.sf.net/">Bumblebee</a> '
    . 'version %s, released under the '
    . '<a href="http://www.gnu.org/licenses/gpl.html">GNU GPL</a>.'
  ),
  $BUMBLEBEEVERSION
);
print "<br />";

if ($conf->status->database) {
  $stat = new SystemStats;

  printf(
    T_(
      'This installation of Bumblebee currently manages %s users, %s projects, '
      . '%s instruments and %s bookings.'
    ),
    $stat->get('users'),
    $stat->get('projects'),
    $stat->get('instruments'),
    $stat->get('bookings')
  );
  print "<br />";
}

if ($conf->value('display','server_signature',true)) {
  printf(
    T_('Running under %s (%s), %s (%s), PHP (%s, %s mode) with %s (%s).'),
    php_uname('s'), php_uname('r'),
    webserver_get_name(), webserver_get_version(),
    phpversion(), PHP_SAPI,
    db_get_name(), db_get_version()
  );
  print "<br />";
}

printf(
  T_('Email the <a href="mailto:%s">system administrator</a> for help.'),
  $conf->AdminEmail
);
print "</p>";
print "<p class='bumblebeecopyright'>";
printf(
  T_('Booking information Copyright &copy; %s %s'),
  date('Y'),
  $conf->value('main', 'CopyrightOwner')
);
print "</p>";
print "</div>";
