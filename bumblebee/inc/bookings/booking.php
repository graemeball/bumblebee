<?php
# $Id$
# Booking object

include_once 'inc/dbforms/date.php';
include_once 'timeslot.php';

class Booking extends TimeSlot {
  var $id,
      /*$ishalfday,
      $isfullday,*/
      $discount,
      $log,
      $comments,
      $project,
      $userid,
      $username,
      $name,
      $useremail,
      $masquserid,
      $masquser,
      $masqusername,
      $masqemail;
  
  function Booking($arr) {
    $this->TimeSlot($arr['bookwhen'], $arr['stoptime'], $arr['duration']);
    $this->id = $arr['bookid'];
    /*$this->ishalfday = $arr['ishalfday'];
    $this->isfullday = $arr['isfullday'];*/
    $this->discount = $arr['discount'];
    $this->log = $arr['log'];
    $this->comments = $arr['comments'];
    $this->project = $arr['project'];
    $this->userid = $arr['userid'];
    $this->username = $arr['username'];
    $this->name = $arr['name'];
    $this->useremail = $arr['email'];
    $this->masquserid = $arr['masquserid'];
    $this->masquser = $arr['masquser'];
    $this->masqusername = $arr['masqusername'];
    $this->masqemail = $arr['masqemail'];

    #echo "Booking from ".$this->start->datetimestring." to ".$this->stop->datetimestring."<br />\n";
    $this->baseclass='booking';
  }

  function display($displayAdmin, $displayOwner) {
    return $this->displayInTable(2, $displayAdmin, $displayOwner);
  }

  function displayInTable($cols, $displayAdmin, $displayOwner) {
    /*
    $bookinglength = "";
    if ($isInstrAdmin || $isSystemAdmin || $isOwnBooking) {
      $bookinglength = ($this->ishalfday ? " (half day)" : "");
      $bookinglength = ($this->isfullday ? " (full day)" : "");
    }*/
    $t = '<tr><td>Booking ID</td><td>'.$this->id.'</td></tr>'."\n"
       . '<tr><td>Start</td><td>'.$this->start->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Stop</td><td>'.$this->stop->datetimestring.'</td></tr>'."\n"
       . '<tr><td>Duration</td><td>'.$this->duration->timestring/*.$bookinglength*/.'</td></tr>'."\n"
       . '<tr><td>User</td><td><a href="mailto:'.$this->useremail.'">'.$this->name.'</a> ('.$this->username.')</td></tr>'."\n"
       . '<tr><td>Comments</td><td>'.$this->comments.'</td></tr>'."\n"
       . '<tr><td>Log</td><td>'.$this->log.'</td></tr>'."\n";
    if ($displayAdmin) {
      if ($this->masquser) {
        $t .= '<tr><td>Booked by</td><td><a href="mailto:'.$this->masqemail.'">'.$this->masquser.'</a> ('.$this->masqusername.')</td></tr>'."\n";
      }
    }
    if ($displayAdmin || $displayOwner) {
      $t .= '<tr><td>Project</td><td>'.$this->project.'</td></tr>'."\n";
      if ($this->discount) {
        $t .= '<tr><td>Discount</td><td>'.$this->discount.'</td></tr>'."\n";
      }
    }
    return $t;
  }

  function displayCellDetails() {
    global $BASEPATH;
    $t = '';
    $t .= "<div style='float:right;'><a href='$this->href/$this->id' title='View or edit booking' class='but'><img src='$BASEPATH/theme/images/editbooking.png' alt='View/edit booking' class='calicon' /></a></div>";
    $t .= '<div class="calbookperson">'
         .'<a href="mailto:'.$this->useremail.'">'
         .$this->name.'</a></div>';
    return $t;
  }

  function generateBookingTitle() {
    return 'Booking from '. $this->start->datetimestring
         .' - '. $this->stop->datetimestring;
  }

} //class Booking