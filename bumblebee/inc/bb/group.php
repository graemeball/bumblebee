<?php
# $Id$
# Group object (extends dbo)

include_once 'dbobject.php';
include_once 'textfield.php';

class Group extends DBO {
  
  function Group($id) {
    DBO::DBO("groups", $id);
    $this->editable = 1;
    $f = new TextField("id", "Group ID");
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField("name", "Name");
    #echo $f->editable;
    $attrs = array('size' => "48");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("longname", "");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("addr1", "Address 1");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("addr2", "Address 2");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("suburb", "Suburb");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("state", "State");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("code", "Postcode");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("country", "Country");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("email", "email");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("fax", "Fax");
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField("account", "Account code");
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = "Group object";
  }

  function display() {
    return $this->displayAsTable();
  }

  function displayAsTable() {
    $t = "<table class='tabularobject'>";
    foreach ($this->fields as $k => $v) {
      $t .= $v->displayInTable(2);
    }
    $t .= "</table>";
    return $t;
  }

} //class Group