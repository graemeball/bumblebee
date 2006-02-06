<?php
/**
* Edit/create/delete users, their project associations and permissions
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*/

/** User object */
require_once 'inc/bb/user.php';
/** list of choices */
require_once 'inc/formslib/anchortablelist.php';
/** parent object */
require_once 'inc/actions/actionaction.php';

/**
* Edit/create/delete users, their project associations and permissions
* @package    Bumblebee
* @subpackage Actions
*/
class ActionUsers extends ActionAction {

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionUsers($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }
  
  function go() {
    if (! isset($this->PD['id'])) {
      $this->select(issetSet($this->PD, 'showdeleted', false));
    } elseif (isset($this->PD['delete'])) {
      $this->delete();
    } else {
      $this->edit();
    }
    echo "<br /><br /><a href='".makeURL('users')."'>"._('Return to user list')."</a>";
  }

  function select($deleted=false) {
    $select = new AnchorTableList(_('Users'), _('Select which user to view'));
    $select->deleted = $deleted;
    $select->connectDB('users', array('id', 'name', 'username'));
    $select->list->prepend(array('-1', _('Create new user')));
    $select->list->append(array('showdeleted', _('Show deleted users')));
    $select->hrefbase = makeURL('users', array('id'=>'__id__'));
    $select->setFormat('id', '%s', array('name'), ' %s', array('username'));
    echo $select->display();
  }

  function edit() {
    $user = new User($this->PD['id']);
    $user->update($this->PD);
    #$project->fields['defaultclass']->invalid = 1;
    $user->checkValid();
    echo $this->reportAction($user->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? _('User created') : _('User updated')),
              STATUS_ERR =>  _('User could not be changed:').' '.$user->errorMessage
          )
        );
    echo $user->display();
    if ($user->id < 0) {
      $submit = _('Create new user');
      $delete = '0';
    } else {
      $submit = _('Update entry');
      $delete = $user->isDeleted ? _('Undelete entry') : _('Delete entry');
    }
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function delete() {
    $user = new User($this->PD['id']);
    echo $this->reportAction($user->delete(), 
              array(
                  STATUS_OK =>   $user->isDeleted ? _('User undeleted') : _('User deleted'),
                  STATUS_ERR =>  _('User could not be deleted:').'<br/><br/>'.$user->errorMessage
              )
            );  
  }
}

?> 
