<?php
# $Id$
# Consumables object (extends dbo)

include_once 'inc/formslib/dbrow.php';
include_once 'inc/formslib/textfield.php';
include_once 'inc/formslib/referencefield.php';

class ConsumableUse extends DBRow {
  
  function ConsumableUse($id, $userid='', $consumableid='', $uid='', $ip='', $today='') {
    $this->DBRow('consumables_use', $id);
    $this->editable = 1;
    $f = new IdField('id', 'Record ID');
    $f->editable = 0;
    $this->addElement($f);
    if ($userid!='' && $consumableid!='' && $uid!='' && $ip!='' && $today!='') {
      $f = new ReferenceField('userid', 'User');
      $f->extraInfo('users', 'id', 'name');
      $f->defaultValue = $userid;
      $f->duplicateName = 'user';
      $f->editable = 0;
      $this->addElement($f);
      $f = new ReferenceField('consumable', 'Consumable');
      $f->extraInfo('consumables', 'id', 'name');
      $f->defaultValue = $consumableid;
      $f->duplicateName = 'consumableid';
      $f->editable = 0;
      $this->addElement($f);
      $f = new ReferenceField('addedby', 'Recorded by');
      $f->extraInfo('users', 'id', 'name');
      $f->value = $uid;
      $f->editable = 0;
      $this->addElement($f);
      $f = new TextField('ip', 'Computer IP');
      $f->value = $ip;
      $f->editable = 0;
      $this->addElement($f);
      $f = new TextField('usewhen', 'Date');
      $f->value = $today;
      $f->required = 1;
      $f->isValidTest = 'is_valid_date';
      $attrs = array('size' => '48');
      $f->setAttr($attrs);
      $this->addElement($f);
      $f = new TextField('quantity', 'Quantity');
      $f->required = 1;
      $f->isValidTest = 'is_number';
      $f->setAttr($attrs);
      $this->addElement($f);
      $f = new TextField('comments', 'Comments');
      $f->setAttr($attrs);
      $this->addElement($f);
      $f = new TextField('log', 'Log entry');
      $f->setAttr($attrs);
      $this->addElement($f);
    }
    $this->fill();
    $this->dumpheader = 'Consumables object';
  }

  function display() {
    return $this->displayAsTable();
  }
} //class Consumable
