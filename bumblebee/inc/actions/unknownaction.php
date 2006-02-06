<?php
/**
* Error handling class for unknown actions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Error handling class for unknown actions
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUnknown extends ActionAction {
  var $action;
  var $forbiden;

  /**
  * Initialising the class 
  * 
  * @param  string  $action requested action ('verb')
  * @param  boolean $forbidden  (optional) 
  * @return void nothing
  */
  function ActionUnknown($action, $forbidden=0) {
    parent::ActionAction('','');
    $this->action = $action;
    $this->forbidden = $forbidden;
  }

  function go() {
    global $ADMINEMAIL;
    echo '<h2>'._('Error').'</h2><div class="msgerror">';
    if ($this->forbidden) {
      echo '<p>'
          .sprintf(_('Sorry, you don\'t have permission to perform the action "%s".'), $this->action) 
          .'</p>';
    } else {
      echo '<p>'
          .sprintf(_('An unknown error occurred. I was asked to perform the action "%s", but I don\'t know how to do that.'), $this->action)
          .'</p>';
    }
    echo '<p>'
        .sprintf(_('Please contact <a href="mailto:%s">the system administrator</a> for more information.'), $ADMINEMAIL)
        .'</p></div>';
  }
  
  
  
} //ActionUnknown
?> 
