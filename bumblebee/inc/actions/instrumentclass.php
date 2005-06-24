<?php
# $Id$
# edit the groups

include_once 'inc/bb/instrumentclass.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

class ActionInstrumentClass extends ActionAction  {

  function ActionInstrumentClass($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectInstrumentClass();
    } elseif (isset($this->PD['delete'])) {
      $this->deleteInstrumentClass();
    } else {
      $this->editInstrumentClass();
    }
    echo "<br /><br /><a href='$BASEURL/instrumentclass/'>Return to instrument class list</a>";
  }

  function editInstrumentClass() {
    $class = new InstrumentClass($this->PD['id']);
    $class->update($this->PD);
    $class->checkValid();
    echo $this->reportAction($class->sync(), 
          array(
              STATUS_OK =>   ($this->PD['id'] < 0 ? 'Instrument class created' : 'Instrument class updated'),
              STATUS_ERR =>  'Instrument class could not be changed: '.$class->errorMessage
          )
        );
        echo $class->display();
    if ($class->id < 0) {
      $submit = 'Create new class';
      $delete = '0';
    } else {
      $submit = 'Update entry';
      $delete = 'Delete entry';
    }
    #$submit = ($PD['id'] < 0 ? "Create new" : "Update entry");
    echo "<input type='submit' name='submit' value='$submit' />";
    if ($delete) echo "<input type='submit' name='delete' value='$delete' />";
  }

  function selectInstrumentClass() {
    global $BASEURL;
    $select = new AnchorTableList('InstrumentClass', 'Select which instrument class to view');
    $select->connectDB('instrumentclass', array('id', 'name'));
    $select->list->prepend(array('-1','Create new instrument class'));
    $select->hrefbase = $BASEURL.'/instrumentclass/';
    $select->setFormat('id', '%s', array('name')/*, ' %50.50s', array('longname')*/);
    #echo $groupselect->list->text_dump();
    $select->numcols = 1;
    echo $select->display();
  }

  function deleteInstrumentClass() {
    $class = new InstrumentClass($this->PD['id']);
    echo $this->reportAction($class->delete(), 
              array(
                  STATUS_OK =>   'Instrument class deleted',
                  STATUS_ERR =>  'Instrument class could not be deleted:<br/><br/>'.$class->errorMessage
              )
            );  
  }
}
?> 