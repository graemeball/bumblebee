<?php
/**
* User/Instrument class matrix 
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/


/** parent object */
require_once 'inc/formslib/dbrow.php';
/** uses fields */
require_once 'inc/formslib/joinmatrix.php';
require_once 'inc/formslib/textfield.php';

/**
* User/Instrument class matrix 
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class ClassCost extends DBRow {
  
  function ClassCost($id) {
    $this->DBRow('userclass', $id);
    $this->editable = 1;
    $this->use2StepSync = 1;
    $f = new IdField('id', _('UserClass ID'));
    $f->editable = 0;
    $f->duplicateName = 'userclass';
    $this->addElement($f);
    $f = new TextField('name', _('Name'));
    $attrs = array('size' => '48');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);

    $f = new JoinMatrix('costs', 'id',
                       'userclass',       'id', 'userclass',
                       'instrumentclass', 'id', 'instrumentclass',
                       'classlabel', _('Cost settings'), 
                       _('Costs for each class of user corresponding to each instrument type'));
    $userfield  = new TextField('name', _('User Class'), _('Classes of users'));
    $instrfield = new TextField('name', _('Instrument Class'), _('Classes of instrument'));
    //$instrfield->setFormat('id', '%s', array('name'), ' (%40.40s)', $classexample);
    $f->addKeys($userfield,$instrfield);

    $cost = new TextField('costfullday', _('Full day cost'), 
                          _('Cost of instrument use for a full day'));
    $attrs = array('size' => '6');
    $cost->setAttr($attrs);
    $f->addElement($cost);
    $hours= new TextField('hourfactor', _('Hourly rate multiplier'), 
                          _('Proportion of daily rate charged per hour'));
    $hours->setAttr($attrs);
    $f->addElement($hours);
    $halfs= new TextField('halfdayfactor', _('Half-day rate multiplier'), 
                          _('Proportion of daily rate charged per half-day'));
    $halfs->setAttr($attrs);
    $f->addElement($halfs);
    $discount= new TextField('dailymarkdown', _('Daily bulk discount %'), 
                          _('Discount for each successive day\'s booking'));
    $discount->setAttr($attrs);
    $f->addElement($discount);
    $f->colspan = 2;
    $f->editable = 1;
    //$f->joinSetup('instrumentclass', array('minspare' => 0));
    $f->setKey($id);
    $this->addElement($f);

    $this->fill($id);
    $this->dumpheader = 'Cost object';
    #preDump($this);
  }

  function display() {
    return $this->displayAsTable();
  }

} //class ClassCost
