<?php
/**
* Edit and create costs for using instruments
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/costs.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** Costs object */
require_once 'inc/bb/costs.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
*  Edit and create costs for using instruments
*
* Costs for instrument usage are calculated using a matrix of the instrument class
* and the user class. See { @link http://bumblebeeman.sf.net/ } for further details.
* @package    Bumblebee
* @subpackage Actions
*/
class ActionCosts extends ActionAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionCosts($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['userclass'])) {
      $this->selectUserClass();
    } else {
      if ($this->readOnly) $this->_dataCleanse('userclass');
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('costs')."'>"
                              .T_('Return to costs list')      ."</a><br /><br />";
    echo "<a href='".makeURL('specialcosts')."'>"
                              .T_('Edit special costs')        ."</a><br />";
    echo "<a href='".makeURL('instrumentclass')."'>"
                              .T_('Edit instrument classes')   ."</a><br />";
    echo "<a href='".makeURL('userclass')."'>"
                              .T_('Edit user classes')         ."</a><br />";
  }

//   function mungeInputData() {
//     $this->PD = array();
//     foreach ($_POST as $k => $v) {
//       $this->PD[$k] = $v;
//     }
//     if (isset($this->PDATA[1]) && ! empty($this->PDATA[1])) {
//       $this->PD['userclass'] = $this->PDATA[1];
//     }
//     echoData($this->PD, 0);
//   }

  /**
  * Generate an HTML form for the user to select which class of user to edit costs
  *
  * - do DB look-ups on what instruments exist
  * - construct a table of links to allow the user to select which userclass to edit
  *
  * @return void nothing
  */
  function selectUserClass() {
    $select = new AnchorTableList(T_('Cost'), T_('Select which user class to view usage costs'), 1);
    $select->connectDB('userclass', array('id', 'name'));
    //$select->list->prepend(array("-1","Create new user class"));
    $select->hrefbase = makeURL('costs', array('userclass'=>'__id__'));
    $select->setFormat('id', '%s', array('name'));
    //echo $select->list->text_dump();
    echo $select->display();
  }

  /**
  * Sync the user's changes with the db and provide feedback
  *
  * @return void nothing
  */
  function edit() {
    $classCost = new ClassCost($this->PD['userclass']);
    $classCost->update($this->PD);
    $classCost->checkValid();
    echo $this->reportAction($classCost->sync(),
          array(
              STATUS_OK =>   ($this->PD['userclass'] < 0
                                    ? T_('Cost schedule created')
                                    : T_('Cost schedule updated')),
              STATUS_ERR =>  T_('Cost schedule could not be changed:').' '.$classCost->errorMessage
          )
        );
    echo $classCost->display();
    echo '<input type="submit" name="submit" value="'.T_('Update entry').'" />';
  }

}

?>
