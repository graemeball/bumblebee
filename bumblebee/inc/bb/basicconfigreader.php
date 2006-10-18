<?php
/**
* Generic configuration management object
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

class BasicConfigReader {

  var $data;

  var $configError = false;

  function ConfigReader() {
    // note: this function does not seem to be called under PHP5.
    static $constructed = false;
    #echo "Constructor called";
    if ($constructed) {
      trigger_error('ConfigReader is a singleton. Instantiate it only once if you must then use getInstance()', E_USER_ERROR);
    }
    $constructed = true;
  }

  function & getInstance() {
    static $instance = array();
    if (count($instance) < 1 || $instance[0] == null) {
      #echo "Making instance";
      $instance[0] = & new ConfigReader();
    }
    #echo "Returning instance";
    return $instance[0];
  }

  function loadFile($filename, $fatalErrors=true) {
    $this->data = parse_ini_file($filename, 1);
    if (! is_array($this->data)) {
      // if the config file doesn't exist, then we're pretty much stuffed
      $this->configError = true;
      trigger_error("System misconfiguration: I could not find the config file '$filename'. Please give me a config file so I can do something useful.", $fatalErrors ? E_USER_ERROR : E_USER_NOTICE);
    }
  }

  function value($section, $parameter, $default=null) {
    if (isset($this->data[$section]) && is_array($this->data[$section])) {
      return issetSet($this->data[$section], $parameter, $default);
    } else {
      return $default;
    }
  }

}


?>
