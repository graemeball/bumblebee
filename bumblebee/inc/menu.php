<?php
/**
* Main menu for admin and normal users
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

/** permission codes */
require_once 'inc/permissions.php';

/**
* Main menu for admin and normal users
*
* @package    Bumblebee
* @subpackage Misc
* @todo //TODO: combine the data storage with action.php for cleaner implementation
*/
class UserMenu {
  /**
  * list of available actions
  * @var ActionListing
  */
  var $actionListing;
  /**
  * text output before start of menu block
  * @var string
  */
  var $menuPrologue   = '';
  /**
  * text output after end of menu block
  * @var string
  */
  var $menuEpilogue   = '';
  /**
  * html id for the enclosing DIV
  * @var string
  */
  var $menuDivId      = 'menulist';
  /**
  * html start-tag for the menu section
  * @var string
  */
  var $menuStart      = '<ul>';
  /**
  * html menu entry (complete!) for the link to the online help
  * @var string
  */
  var $menuHelp;
  /**
  * html stop-tag for the menu section
  * @var string
  */
  var $menuStop       = '</ul>';
  /**
  * html start-tag for each menu entry
  * @var string
  */
  var $itemStart      = '<li>';
  /**
  * html stop-tag for each menu entry
  * @var string
  */
  var $itemStop       = '</li>';
  /**
  * html id for div that alerts to current Masquerade setting
  * @var string
  */
  var $masqDivId      = 'masquerade';
  /**
  * html start-tag for each menu section
  * @var string
  */
  var $headerStart    = '<li class="menuSection">';
  /**
  * html stop-tag for each menu section
  * @var string
  */
  var $headerStop     = '</li>';
  /**
  * text to include at start of main menu section
  * @var string
  */
  var $mainMenuHeader;
  /**
  * text to include at start of admin menu section
  * @var string
  */
  var $adminHeader;
  /**
  * tag to use for the masq alert style (be careful of using div in an ul!)
  * @var string
  */
  var $masqAlertTag  = 'li';
  /**
  * display the menu
  * @var boolean
  */
  var $showMenu       = true;

  /**
  * logged in user's credentials
  * @var BumblebeeAuth
  */
  var $_auth;
  /**
  * currently selected action
  * @var string
  */
  var $_verb;

  /**
  * Constructor
  * @param BumblebeeAuth $auth  user's credentials
  * @param string        $verb  current action
  */
  function UserMenu($auth, $verb) {
    $this->_auth = $auth;
    $this->_verb = $verb;
    $this->menuHelp       = '<li class="last"><a href="http://bumblebeeman.sf.net/docs?section=__section__&amp;version=__version__">' . T_('Help') . '</a></li>';
    $this->mainMenuHeader = T_('Main Menu');
    $this->adminHeader    = T_('Administration');
  }

  /**
  * Generates an html representation of the menu
  * @return string       menu in html format
  */
  function getMenu() {
    if (! $this->showMenu) {
      return '';
    }

    if($this->_auth->anonymous) {
      $this->actionListing->actions[] = new ActionData('ActionLogin', 'login.php',
                  array('login', array('changeuser'=>1)), _('Login'), T_('Login'), BBROLE_NONE);
    } //endif

    $menu  = '<div'.($this->menuDivId ? ' id="'.$this->menuDivId.'"' :'' ).'>';
    $menu .= $this->menuStart;
    $menu .= $this->_constructMenuEntries();
    if ($this->_auth->amMasqed() && $this->_verb != 'masquerade')
          $menu .= $this->_getMasqAlert();
    
    $menu .= $this->_getHelpMenu();
    $menu .= $this->menuStop;
    $menu .= '</div>';
    return $menu;
  }

  /**
  * Generates an html representation of the menu according to the current user's permissions
  * @return string       menu in html format
  */
  function _constructMenuEntries() {
    $t = '';
    if ($this->mainMenuHeader) {
      $t .= $this->headerStart.$this->mainMenuHeader.$this->headerStop;
    }
    $first_admin = true;
    #preDump($this->actionListing->actions);
    foreach ($this->actionListing->actions as $action) {
      #print $action->name()."<br />\n";
      #print $action->name(). " requires " . $action->permissions() . " have " .$this->_auth->system_permissions."<br />\n";
      if ($action->menu_visible() && $this->_auth->permitted($action->permissions())) {
        #print " visible";
        if ($first_admin && $action->requires_admin()) {
          #print " admin header";
          $first_admin = false;
          if ($this->adminHeader) {
            $t .= $this->headerStart.$this->adminHeader.$this->headerStop;
          }
        }

        $name = $action->name();

        if(is_array($name)) { 
          $get_string = makeURL($name[0], $name[1]);
        } else { 
          $get_string = makeURL($name);
        } //end if-else

        $t .= $this->itemStart
              .'<a href="'.$get_string.'">'.$action->menu().'</a>'
            .$this->itemStop;
      }
      #print "<br/>";
    }
    return $t;
  }

  /**
  * Generates an html div to alert the user that masquerading is in action
  * @return string       menu in html format
  */
  function _getMasqAlert() {
    $t = '<'.$this->masqAlertTag.' id="'.$this->masqDivId.'">'
             .'Mask: '.xssqw($this->_auth->eusername)
             .' (<a href="'.makeURL('masquerade', array('id'=>-1)).'">end</a>)'
        .'</'.$this->masqAlertTag.'>';
    return $t;
  }

  /**
  * Generates an html snippet to for the link to the online help
  * @return string       menu in html format
  * @global string       version of the Bumblebee installation (can serve different versions if necessary)
  */
  function _getHelpMenu() {
    global $BUMBLEBEEVERSION;
    $help = $this->menuHelp;
    $help = preg_replace(array('/__version__/',   '/__section__/'),
                         array($BUMBLEBEEVERSION, $this->_verb),
                         $help);
    return $help;
  }

} // class UserMenu


/**
* create a URL for an anchor
* @param string  $action    action to be performed
* @param array   $list      (optional) key => value data to be added to the URL
* @param boolean $escape    use &amp; rather than & in the URL
* @return string URL
*/
function makeURL($action=NULL, $list=NULL, $escape=true) {
  $conf = ConfigReader::getInstance();
  $list = is_array($list) ? $list : array();
  if ($action !== NULL) $list['action'] = $action;
  $args = array();
  foreach ($list as $field => $value) {
    if (is_array($value)) $value = join(',', $value);
    $args[] = $field.'='.urlencode($value);
  }
  $delim = $escape ? '&amp;' : '&';
  if (count($args) > 0) return $conf->BaseURL.'?'.join($delim, $args);

  return $conf->BaseURL;
}


/**
* Create an absolute URL for an anchor (include protocol and port)
*
* @param mixed   $target    what the URL should point to
* @param boolean $escape    use &amp; rather than & in the URL
* @return  string  Absolute URL
*
* If $target is an array, then $target[0] is the action and $target[1] is
* the list of parameters that will be passed to makeURL().
*
* If $target is a scalar, it will be appended to the URL as is.
*/
function makeAbsURL($target=NULL, $escape=true) {

  if (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on')) {
    $protocol = 'https';
  } else {
    $protocol = 'http';
  }

  $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;

  // filter out well known ports from the URL
  if (($protocol == 'http' && $port == 80) || ($protocol == 'https' && $port == 443)) {
    unset($port);
  }

  $host = $_SERVER['SERVER_NAME'];
  if (empty($host)) {
    list($host) = explode(':', $_SERVER['HTTP_HOST']);
  }

  $serverPart = $protocol .'://'. $host . (isset($port) ? ':'. $port : '');

  if (is_array($target) || $target === NULL) {
    $pathPart = makeURL($target[0], $target[1], $escape);
  } else {
    $conf = ConfigReader::getInstance();
    $pathPart = $conf->BasePath . $target;
  }

  $pathPart = preg_replace('@//+@', '/', $pathPart);

  return $serverPart . $pathPart;
}

/**
* Bounces the user to an alternative location, terminating execution of this script
*
* @param    string   $location    URL that the user should be redirected to
* @returns  NEVER RETURNS
*/
function redirectUser($location) {
  header("Location: ". $location);
  exit;
}

?>
