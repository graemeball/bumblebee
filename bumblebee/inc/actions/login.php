<?php
/**
* Print a polite login form
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Print a polite login form
*
* Authentication is undertaken by the class BumblebeeAuth
* @package    Bumblebee
* @subpackage Actions
*/
class ActionPrintLoginForm extends ActionAction {

  /**
  * Initialising the class
  *
  * @return void nothing
  */
  function ActionPrintLoginForm($auth, $PDATA) {
    parent::ActionAction($auth, $PDATA);
    $this->mungeInputData();
  }

  function go() {
    global $CONFIG;
    if (isset($CONFIG['display']['LoginPage']) && ! empty($CONFIG['display']['LoginPage'])) {
      echo $CONFIG['display']['LoginPage'];
    }
    echo '<h2>' . T_('Login required').'</h2>';
    echo '<p>'  . T_('Please login to view or book instrument usage') . '</p>';
    $this->printLoginForm();
    if (isset($this->PD['changeuser'])) {
      $this->printDataReflectionForm($this->PD);
    }
  }

  function printLoginForm() {
    printf('
      <table>
      <tr>
        <td>%s</td>
        <td><input name="username" type="text" size="16" /></td>
      </tr>
      <tr>
        <td>%s</td>
        <td><input name="pass" type="password" size="16" /></td>
      </tr>
      <tr>
        <td></td>
        <td><input name="submit" type="submit" value="%s" /></td>
      </tr>
      </table>',
      T_('Username:'),
      T_('Password:'),
      T_('login')  );
  }

  function printDataReflectionForm($data) {
    // save the rest of the query string for later use
    foreach ($data as $k => $v) {
      #if ($k == 'action') $k = 'nextaction';
      if ($k != 'changeuser') {
        printf ('<input type="hidden" name="%s" value="%s" />', xssqw($k), xssqw($v));
      }
    }
  }

}

?>
