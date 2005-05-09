<?php
# $Id$
# User object (extends dbo), with extra customisations for other links

include_once 'inc/formslib/dbrow.php';
include_once 'inc/formslib/textfield.php';
include_once 'inc/formslib/radiolist.php';
include_once 'inc/formslib/checkbox.php';
include_once 'inc/formslib/passwdfield.php';

class User extends DBRow {
  
  var $_localAuthPermitted;
  var $_authList;
  var $_magicPassList;
  var $_authMethod;

  function User($id, $passwdOnly=false) {
    $this->DBRow('users', $id);
    $this->editable = ! $passwdOnly;
    $this->use2StepSync = 1;
    $f = new IdField('id', 'UserID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('username', 'Username');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', 'Email');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('phone', 'Phone');
    $f->required = 1;
    $f->isValidTest = 'is_empty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new CheckBox('suspended', 'Suspended');
    
    // association of user with an authentication method
    $this->_findAuthMethods();
    $f = new RadioList('auth_method', 'User authentication method');
    $f->sqlHidden = 1;
    $f->setValuesArray($this->_authList, 'id', 'iv');
    $f->setFormat('id', '%s', array('iv'));
    $f->setAttr($attrs);
    $f->required = 1;
    $f->hidden = $passwdOnly;
    $this->addElement($f);
    if ($this->_localAuthPermitted) {
      $password = new PasswdField('passwd','Password (for local login)');
      $password->setAttr(array('size' => 24));
      //$password->isValidTest = 'is_nonempty_string';
      $password->suppressValidation = 0;
      $password->editable = 1;
      //$f->list->append(array('local','Local login: '), $password);
      $this->addElement($password);
    }
    
    if (! $passwdOnly) {
      // association of users to projects
      $f = new JoinData('userprojects',
                        'userid', $this->id,
                        'projects', 'Project membership');
      $projectfield = new DropList('projectid', 'Project');
      $projectfield->connectDB('projects', array('id', 'name', 'longname'));
      $projectfield->prepend(array('0','(none)', 'no selection'));
      $projectfield->setDefault(0);
      $projectfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($projectfield);
      $f->joinSetup('projectid', array('minspare' => 2));
      $f->colspan = 2;
      $this->addElement($f);
  
      // association of users with instrumental permissions
      $f = new JoinData('permissions',
                        'userid', $this->id,
                        'instruments', 'Instrument permissions');
      $instrfield = new DropList('instrid', 'Instrument');
      $instrfield->connectDB('instruments', array('id', 'name', 'longname'));
      $instrfield->prepend(array('0','(none)', 'no selection'));
      $instrfield->setDefault(0);
      $instrfield->setFormat('id', '%s', array('name'), ' (%25.25s)', array('longname'));
      $f->addElement($instrfield);
      $subscribeAnnounce = new CheckBox('announce', 'Subscribe: announce');
      $f->addElement($subscribeAnnounce);
      $unbookAnnounce = new CheckBox('unbook', 'Subscribe: unbook');
      $f->addElement($unbookAnnounce);
      $instradmin = new CheckBox('isadmin', 'Instrument admin');
      $f->addElement($instradmin);
      /*  
      //Add these fields in once we need this functinality
      $hasPriority = new CheckBox('haspriority', 'Booking priority');
      $f->addElement($hasPriority);
      $bookPoints = new TextField('points', 'Booking points');
      $f->addElement($bookPoints);
      $bookPointsRecharge = new TextField('pointsrecharge', 'Booking points recharge');
      $f->addElement($bookPointsRecharge);
      */
      $f->joinSetup('instrid', array('minspare' => 2));
      $f->colspan = 2;
      $this->addElement($f);
    }
    
    $this->fill($id);
    $this->dumpheader = 'User object';
  }

  function _findAuthMethods() {
    global $CONFIG;
    $this->_localAuthPermitted = isset($CONFIG['auth']['useLocal']) 
                                        && $CONFIG['auth']['useLocal'];
    $this->_authList = array();
    foreach ($CONFIG['auth'] as $key => $val) {
      if (strpos($key, 'use') === 0 && $val) {
        $method = substr($key,3);
        $this->_authList[$method] = $method;
        $this->_magicPassList[$method] = $CONFIG['auth'][$method.'PassToken'];
      }
    }  
  }

  function fill() {
    parent::fill();
    //now edit the passwd/auth fields
    $this->_authMethod = 'Local';
    foreach($this->_magicPassList as $meth => $passtok) {
      if ($this->fields['passwd']->value == $passtok) {
        $this->_authMethod = $meth;
      }
    }
    if ($this->_authMethod != 'Local') {
     $this->fields['passwd']->crypt_method = '';
    } else {
     $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
    }
    $this->fields['auth_method']->set($this->_authMethod);
    //echo $this->fields['passwd']->value;
  }
  
  function sync() {
    //monkey the passwd/auth fields
    //echo $this->_authMethod. '-';
    //preDump($this->fields['passwd']);
    //echo $this->fields['passwd']->value;
    $this->_authMethod = $this->fields['auth_method']->getValue();
    if ($this->_authMethod != 'Local' 
            && $this->fields['passwd']->value != ''
            && $this->fields['passwd']->value != $this->_magicPassList[$this->_authMethod]) {
      $this->log('User::sync(): indulging in password munging, '. $this->_authMethod);
      $this->fields['passwd']->set($this->_magicPassList[$this->_authMethod]);
      $this->fields['passwd']->crypt_method = '';
      $this->fields['passwd']->changed = 1;
      $this->changed = 1;
    } else {
      $this->fields['passwd']->crypt_method = $this->_magicPassList['Local'];
    }
    return parent::sync();
  }
  
  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= '</table>';
    return $t;
  }

} //class User
