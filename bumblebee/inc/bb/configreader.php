<?php
/**
* Configuration reading object
*
* Parses the {@link bumblebee.ini } file
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** include parent */
require_once 'inc/bb/basicconfigreader.php';

class ConfigReader extends BasicConfigReader {

  /** @var string  used for generating links to the administrator for more information, help etc */
  var $AdminEmail;

  var $BasePath;
  /** @var string  prepended to all URLs generated by the system so that the links work */
  var $BaseURL;
  /** @var string  Location of the session array (make sure the session variables don't clash with others systems that are setting session vars) */
  var $SessionIndex;

  /** @var boolean  Echo all SQL statements to the browser for debugging purposes */
  var $VerboseSQL = false;
  /** @var boolean  user supplied data will be dumped to the browser for debugging purposes */
  var $VerboseData = false;

  function ParseConfig($fatalErrors=true) {
    $this->AdminEmail = $this->value('main', 'AdminEmail', '');  ///FIXME: work out a default?

    $this->BasePath   = $this->value('main', 'BasePath');   ///FIXME: work out a default?
    if (substr($this->BasePath, 0, 1) != '/' && ! $this->configError) {
      // the first character of the path must be a slash
      // the user is never going to be able to log on when it's like this, so let's kill it off.
      trigger_error('Bumblebee misconfiguration: please make sure that the BasePath parameter in bumblebee.ini is only the path portion of the URL and does not include the server name. (Hint: should start with a "/" and be something like "/bumblebee" or "/departments/chemistry/equipment")', $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    }

    $this->BaseURL    = $this->value('main', 'BaseURL');    ///FIXME: work out a default?

    if ($fatalErrors) {
      if (defined('LOAD_ALL_PHP_FILES')) {
        if ($this->data['error_handling']['AllWarnings']) {
          //this is nice for development but probably turn it off for production
          #error_reporting(E_ALL | E_STRICT); #force all warnings to be echoed
          error_reporting(E_ALL); #force all warnings to be echoed
          /** load all php files */
          define('LOAD_ALL_PHP_FILES', 1);
        } else {
          error_reporting(E_ERROR); #only errors should be echoed
          /** load only the php files required to fullfill this request) */
          define('LOAD_ALL_PHP_FILES', 0);
        }
      }
    }

    $this->VerboseSQL  = $this->value('error_handling', 'VerboseSQL', false);
    $this->VerboseData = $this->value('error_handling', 'VerboseData', false);

    $this->SessionIndex = md5(dirname(__FILE__));
  }

}

?>
