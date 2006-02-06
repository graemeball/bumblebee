<?php
/**
* Special costs for project/instrument usage editing
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
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/referencefield.php';
require_once 'inc/formslib/joindata.php';

/**
* Special costs for project/instrument usage editing
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class SpecialCost extends DBRow {
  
  function SpecialCost($id, $project, $instrument) {
    //$this->DEBUG=10;
    $this->DBRow('projectrates', $project, 'projectid');
    $this->editable = 1;
    $this->ignoreId = 1;
    $this->autonumbering = 0;
    $this->restriction = 'instrid='.qw($instrument).' AND projectid='.qw($project);
    //$this->use2StepSync = 1;
    $f = new ReferenceField('projectid', _('Project'));
    $f->extraInfo('projects', 'id', 'name');
    $f->value = $project;
    $f->editable = 0;
    $f->duplicateName = 'project';
    $this->addElement($f);
    $f = new ReferenceField('instrid', _('Instrument'));
    $f->extraInfo('instruments', 'id', 'name');
    $f->value = $instrument;
    $f->editable = 0;
    $f->duplicateName = 'instrument';
    $this->addElement($f);

    $f = new JoinData('costs',
                      'id', $id,
                      'costsettings', _('Charging settings:'));
    //$f->protoRow->DEBUG = 10;
    $f->protoRow->autonumbering = 1;
    //$f->DEBUG=10;
    $f->reportFields[] = array('id' => 'rate');
    
    $rate = new IdField('id', _('Rate ID'), _('Rate ID'));
    $rate->value = $id;
    $f->addElement($rate);
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
                          _('Discount for each successive day&#39;s booking'));
    $discount->setAttr($attrs);
    $f->addElement($discount);

    $f->joinSetup('id', array('total' => 1));
    $f->colspan = 2;
    
    $this->addElement($f);
    
    $this->fill($id);
    $this->dumpheader = 'Cost object';
    $this->insertRow = ($id == -1);
    #preDump($this);
  }

  function delete() {
    //delete our association in the costing table first
    //preDump($this);
    $result = $this->fields['costsettings']->rows[0]->delete();
    $this->errorMessage .= $this->fields['costsettings']->rows[0]->errorMessage;
    if ($result == STATUS_OK) {
      //then gracefully delete ourselves
      $result |= parent::delete();
    }
    return $result;
  }
  
  function display() {
    return $this->displayAsTable();
  }

} //class SpecialCost
