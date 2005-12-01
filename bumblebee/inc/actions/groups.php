<?php
/**
* Interface for editing details of groups
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** Group object */
include_once 'inc/bb/group.php';
/** list of choices */
include_once 'inc/formslib/anchortablelist.php';
/** parent object */
include_once 'inc/actions/actionaction.php';

/**
* Interface for editing details of groups
*
* @package    Bumblebee
* @subpackage Actions
*/
class ActionGroup extends ActionAction  {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionGroup($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='$BASEURL/groups'>Return to group list</a>";
  }

  function select($deleted=false) {
    global $BASEURL;
    $select = new AnchorTableList('Group', 'Select which group to view');
    $select->deleted = $deleted;
    $select->connectDB('groups', array('id', 'name', 'longname'));
    $select->list->prepend(array('-1','Create new group'));
    $select->list->append(array('showdeleted','Show deleted groups'));
    $select->hrefbase = $BASEURL.'/groups/';
    $select->setFormat('id', '%s', array('name'), ' %50.50s', array('longname'));
    #echo $select->list->text_dump();
    echo $select->display();
  }
  
  function edit() {
    $group = new Group($this->PD['id']);
    $group->update($this->PD);
    $group->checkValid();
    echo $this->reportAction($group->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Group created' : 'Group updated'),
              STATUS_ERR =>  'Group could not be changed: '.$group->errorMessage
          )
        );
    echo $group->display();
    if ($group->id < 0) {
      $submit = 'Create new group';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = $group->isDeleted ? 'Undelete entry' : 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $group = new Group($this->PD['id']);
    echo $this->reportAction($group->delete(), 
              array(
                  STATUS_OK =>   $group->isDeleted ? 'Group undeleted' : 'Group deleted',
                  STATUS_ERR =>  'Group could not be deleted:<br/><br/>'.$group->errorMessage
              )
            );  
  }
}
?> 
