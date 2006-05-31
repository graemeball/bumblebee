<?php
/**
* Calendar object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage DBObjects
*/

/** date manipulation routines */
require_once 'inc/date.php';
/** Booking object */
require_once 'inc/bookings/booking.php';
/** Vacancy object */
require_once 'inc/bookings/vacancy.php';
/** Cell object */
require_once 'inc/bookings/cell.php';
/** BookingMatrix object */
require_once 'inc/bookings/matrix.php';
/** BookingData object (list of bookings) */
require_once 'inc/bookings/bookingdata.php';
/** TimeSlot and TimeSlotRule objects */
require_once 'inc/bookings/timeslotrule.php';

/** 
* use start and end times from defined slots 
* @see Calender::_breakAccordingToList 
*/
define('CAL_TIME_SLOTRULE',    1);
/** 
* use start and end times from the bookings
* @see Calender::_breakAccordingToList 
*/
define('CAL_TIME_BOOKING',     2);

/**
* Calendar object
*
* Retrives, sorts and displays bookings for an instrument over a given period of time
*
* @package    Bumblebee
* @subpackage DBObjects
* @todo display bugs for off-slot bookings?
* @todo week-long / multi-day bookings
*/
class Calendar {
  /** @var SimpleDate  start date/time for the calendar    */
  var $start;
  /** @var SimpleDate  stop date/time for the calendar    */
  var $stop;
  /** @var integer     number of days in the calendar  */
  var $numDays;
  /** @var integer     instrument id number for which the calendar is being displayed    */
  var $instrument;
  /** @var boolean     sql errors are fatal   */
  var $fatal_sql = 1;
  /** @var BookingData list of bookings  */
  var $bookinglist;
  /** @var string      prepended to all hrefs generated by in the calendar    */
  var $href = '';
  /** @var string      html/css class to be used for all table cells corresponding to a day    */
  var $dayClass = '';
  /** @var string      html/css class to be used for all table cells corresponding today    */
  var $todayClass = '';
  /** @var mixed       array or string. list of html/css class to be rotated through month by month for the time periods in the calendar   */
  var $rotateDayClass = '';
  /** @var mixed       array or string. list of html/css class to be rotated through month by month for the day header   */
  var $rotateDayClassDatePart = '';
  /** @var TimeSlotRule  time slots that govern this calendar    */
  var $timeslots;
  /** @var boolean     generate the calendar with an admin view (i.e. all times are bookable)  */
  var $isAdminView = 0;
  
  /** @var integer    debug level (0=off, 10=verbose)  */
  var $DEBUG = 0;
  
  /**
  * Create a calendar object, can display bookings in calendar format
  * 
  * @param SimpleDate $start    start time to display bookings from
  * @param SimpleDate $stop     stop time to display bookings until
  * @param integer $instrument  what instrument number to display bookings for
  */ 
  function Calendar($start, $stop, $instrument) {
    $this->start = $start;
    $this->stop  = $stop;
    $this->instrument = $instrument;
    $this->log('Creating calendar from '.$start->dateString().' to '.$stop->dateString(), 5);
    $this->_fill();
    $this->_insertVacancies();
    //print $this->displayAsTable();
  }

  /**
  * set the CSS style names by which 
  *
  * @param string $dayClass   class to use in every day header
  * @param string $today      class to use on today's date
  * @param mixed  $day        string for class on each day, or array to rotate through
  * @param string $dayrotate  time-part ('m', 'd', 'y' etc) on which day CDD should be rotated
  */
  function setOutputStyles($dayClass, $today, $day, $dayrotate='m') {
    $this->dayClass = $dayClass;
    $this->todayClass = $today;
    $this->rotateDayClass = is_array($day) ? $day : array($day);
    $this->rotateDayClassDatePart = $dayrotate;
  }
  
  /**
   * set the time slot picture (passed straight to a TimeSlotRule object) to apply 
   *
   * @param string $pic   timeslot picture for this instrument and this calendar
   */
  function setTimeSlotPicture($pic) {
    $this->timeslots = new TimeSlotRule($pic);
    //break bookings over the predefined pictures
    $this->log('Breaking up bookings according to defined rules');
    $this->_breakAccordingToList($this->timeslots, CAL_TIME_SLOTRULE, CAL_TIME_SLOTRULE);
    //print $this->displayAsTable();
 }

  /**
  * Obtain the booking data for this time period
  *
  * @access private
  */
  function _fill() {
    $bookdata = new BookingData (
          array(
            'instrument' => $this->instrument,
            'start'      => $this->start,
            'stop'       => $this->stop
               )
                               );
    $this->bookinglist = $bookdata->dataArray();
    //preDump($this->bookinglist);
  }

  /**
  * Create pseudo-bookings for all vacancies between the start
  * of this calendar and the end.
  *
  * For example, if we were constructing a calendar from 00:00 on 2004-01-01 to 
  *   23:59 on 2004-01-02, but there was only a booking from 10:00 to 11:00
  *   on 2004-01-01, then we should create vacancy pseudo-bookings from:
  *   - 2004-01-01-00:00 to 2004-01-01-10:00 and from
  *   - 2004-01-01-11:00 to 2004-01-01-24:00 and from
  *   - 2004-01-02-00:00 to 2004-01-02-24:00.
  *
  * Bookings are NOT restricted to remaining on one day (i.e. a booking from
  * 20:00:00 until 10:00:00 the next day is OK.
  *
  */
  function _insertVacancies() {
    $this->numDays = $this->stop->partDaysBetween($this->start);
    $this->log('Creating calendar for '.$this->numDays.' days', 5);
    //blat over the booking list so we can create the normalised list
    $bookings = $this->bookinglist;
    $this->bookinglist = array();
        
    // put a vacancy at the end so we don't run off the end of the list.
    $v = new Vacancy();
    $v->setTimes(clone($this->stop),clone($this->stop));
    $bookings[] = $v;
    
    //insert a vacancy between each non-consecutive booking
    $bvlist = array();
    $booking = 0;
    $now = clone($this->start);
    $this->log('Normalising bookings');    
    while ($now->ticks < $this->stop->ticks) {
      if ($now->ticks < $bookings[$booking]->start->ticks) {
        // then we should create a pseudobooking
        $v = new Vacancy();
        $stoptime = new SimpleDate($bookings[$booking]->start->ticks);
        $v->setTimes(clone($now), clone($stoptime));
        $bvlist[] = $v;
        $now = $stoptime;
        $this->log('Created vacancy: '.$v->start->dateTimeString()
                  .' to '.$v->stop->dateTimeString(), 9);
      } else {
        // then this is the current timeslot
        $bvlist[] = $bookings[$booking];
        $now = $bookings[$booking]->stop;
        $this->log('Included booking: '.
                $bookings[$booking]->start->dateTimeString() .' to '
               .$bookings[$booking]->stop->dateTimeString(), 9);
        $booking++;
      }
    }
    $this->bookinglist = $bvlist;
    $this->bookinglist[0]->arb_start = true;
    $this->bookinglist[count($bvlist)-1]->arb_stop = true;
  }
    
  /**
  * Break up bookings that span days (for display purposes only)
  *
  * For example, if we had a vacancy pseudo-bookings from
  * 2004-01-01-11:00 to 2004-01-02-24:00, then we would 
  * break it up into two bookings as follows:
  *   -  2004-01-01-11:00 to 2004-01-01-24:00 and 
  *   -  2004-01-02-00:00 to 2004-01-02-24:00.
  */
  function _breakAcrossDays() {
    $this->log('Breaking up bookings across days');
    //break bookings over day boundaries
    $daylist = new TimeSlotRule('[0-6]<00:00-24:00/*>');
    $this->_breakAccordingToList($daylist, CAL_TIME_BOOKING, CAL_TIME_BOOKING);
  }    
    
  /**
  * Break up bookings that span elements of a defined list (e.g. allowable times or 
  * days). A TimeSlotRule ($list) is used to define how the times should be broken up
  *
  * $keepTimes(Vacant|Book) are set to CAL_TIME_BOOKING, CAL_TIME_SLOTRULE
  *
  * CAL_TIME_BOOKING:
  *   start|stop are set according to the timeslotrule and will be used to
  *      display the timeslot in a graphica display (i.e. calculating height of boxes)
  *    display(Start|Stop) are set to the values of the original vacancy or booking being examined.
  *
  * CAL_TIME_SLOTRULE:
  *   display(Start|Stop) variables are set to the values of the timeslot rule that breaks up the slots 
  *      with the exception of slots that overlap a booking, where min() or max() is used
  *   start|stop are set to the same as the start/stop vars
  *   Note: vacancies that are at the start or end of the booking list are a corner case 
  *   that is handled respectively as: start = slotrule->stop and stop = slotrule->stop
  *
  * @param $list TimeSlotRule Object   set of rules used to break up booking stream
  * @param $keepTimesVacant enum       how should the display(Start|Stop) and (start|stop) 
  * @param $keepTimesBook   enum       .. variables be set for Vacancy and Booking slots
  */
  function _breakAccordingToList($list, $keepTimesVacant, $keepTimesBook) {
    $bl = $this->bookinglist;
    $this->bookinglist = array();
    $this->log('Breaking up bookings according to list');
    $this->log($list->dump());
    $booking=0;
    for ($bv=0; $bv < count($bl); $bv++) {
/*      $this->log('considering timeslot #'.$bv.': '
                      .$bl[$bv]->start->dateTimeString().' - '.$bl[$bv]->stop->dateTimeString(), 8);*/
      $cbook = $bl[$bv];
      $cbook->original = clone($cbook);
      $isStart = START_BOOKING;
      $slotrule = $list->findSlotFromWithin($bl[$bv]->start);  
      #$start = $list->findSlotStart($bl[$bv]->start);
      if (!is_object($slotrule) && $slotrule == 0) {
        // then the original start time must be outside the proper limits
        $slotrule = $list->findNextSlot($bl[$bv]->start);
      }
      do {  //until the current booking has been broken up across list boundaries
        $this->slotlog('bookingo', $bl[$bv]);
        $this->slotlog('timeslot', $slotrule);
        // push the new booking onto the stack; record if it's the start of a booking or not
        $this->bookinglist[$booking] = clone($cbook);
        $realStart = isset($this->bookinglist[$booking]->displayStart) ? $this->bookinglist[$booking]->displayStart : $this->bookinglist[$booking]->start;
        if ($isStart == MIDDLE_BOOKING && $slotrule->start->dow() != $realStart->dow()) {
          $this->bookinglist[$booking]->isStart |= START_BOOKING_DAY;
        }
        $next_isStart = MIDDLE_BOOKING;
        
        $newstart = clone($this->bookinglist[$booking]->start);
        $newstart->max($slotrule->start);
        $newstop = clone($this->bookinglist[$booking]->stop);
        $newstop->min($slotrule->stop);
        
        if (! $this->bookinglist[$booking]->isVacant) {
          switch ($keepTimesBook) {
            case CAL_TIME_SLOTRULE:   // for bookings
              if ($this->bookinglist[$booking]->arb_start) {
                $newstart = clone($this->bookinglist[$booking]->start);
              }
              if ($this->bookinglist[$booking]->arb_stop) {
                $newstop = clone($this->bookinglist[$booking]->stop);
              }
              $this->bookinglist[$booking]->displayStart = $this->bookinglist[$booking]->original->start;
              $this->bookinglist[$booking]->displayStop  = $this->bookinglist[$booking]->original->stop;
              $this->bookinglist[$booking]->start = $newstart;
              $this->bookinglist[$booking]->stop  = $newstop;
/*              $this->bookinglist[$booking]->displayStart = $newstart;
              $this->bookinglist[$booking]->displayStop  = $newstop;*/
              $this->bookinglist[$booking]->slotRule = $slotrule;
              $this->bookinglist[$booking]->isStart |= $isStart;
              break;
            case CAL_TIME_BOOKING:
              $this->bookinglist[$booking]->start = $newstart;
              $this->bookinglist[$booking]->stop  = $newstop;
//               $this->bookinglist[$booking]->displayStart = $this->bookinglist[$booking]->original->start;
//               $this->bookinglist[$booking]->displayStop  = $this->bookinglist[$booking]->original->stop;
              break;
          }
        } else {
          switch ($keepTimesVacant) {
            case CAL_TIME_SLOTRULE:  // for vacancies
              if ($this->bookinglist[$booking]->arb_start) {
                $newstart = clone($slotrule->start);
              }
              if ($this->bookinglist[$booking]->arb_stop) {
                $newstop = clone($slotrule->stop);
              }
              $this->bookinglist[$booking]->start = $newstart;
              $this->bookinglist[$booking]->stop  = $newstop;
              $this->bookinglist[$booking]->displayStart = $newstart;
              $this->bookinglist[$booking]->displayStop  = $newstop;
              $this->bookinglist[$booking]->isDisabled = ! $slotrule->isAvailable;
              $this->bookinglist[$booking]->slotRule = $slotrule;
              $this->bookinglist[$booking]->isStart |= $isStart;
              break;
            case CAL_TIME_BOOKING:
              $this->bookinglist[$booking]->start = $newstart;
              $this->bookinglist[$booking]->stop  = $newstop;
              $this->bookinglist[$booking]->displayStart = $this->bookinglist[$booking]->original->start;
              $this->bookinglist[$booking]->displayStop  = $this->bookinglist[$booking]->original->stop;
              $this->bookinglist[$booking]->isStart |= $isStart;
              break;
          }
        }
        
        // find the next TimeSlotRule to work out how to chop this booking up again (or how
        // to chop up the next booking)
        $nextslotrule = $list->findNextSlot($slotrule->start);
        $isStart = $next_isStart;
        $this->slotlog('displayv',$this->bookinglist[$booking], true);
        $this->slotlog('realvalu',$this->bookinglist[$booking]);
        $slotrule = $nextslotrule;
        $booking++;
        //$this->log('oticks='.$this->bookinglist[$booking-1]->original->stop->ticks
        //           .'nticks='.$slotrule->start->ticks,10);
        $this->timelog('nextstart=',$slotrule->start);
        //var_dump($this->bookinglist[$booking-1]->original);
        //$this->log('ost='.$this->bookinglist[$booking-1]->original->stop->ticks,10);
        //$this->log('tst='.$slotrule->start->ticks,10);
        $this->log('');
      } while ($this->bookinglist[$booking-1]->original->stop->ticks > $slotrule->start->ticks);
    }
  }

  /**
  * Generate a booking matrix for all the days we are interested in
  */
  function _collectMatrix($daystart, $daystop, $granularity) {
    $matrixlist = array();
    // matrix calculation object is shared from day to day which permits caching of data
    $matrix = new BookingMatrix(clone($daystart), clone($daystop), $granularity, $this->bookinglist);
    for ($day = 0; $day < $this->numDays; $day++) {
      $today = clone($this->start);
      $today->addDays($day);
      $matrix->setDate($today);
      $matrix->prepareMatrix();
      $matrixlist[] = $matrix->getMatrix();
    }
    return $matrixlist;
  }

  /**
  * work out what html/css class this date should be rendered as
  *
  * @param SimpleDate $today  today's date
  * @param SimpleDate $t      the date to check
  * @return string  space-separated css class list for use in class=""
  */
  function _getDayClass($today, $t) {
    $class = $this->dayClass;
    $class .= ' '.$this->rotateDayClass[date($this->rotateDayClassDatePart, $t->ticks) 
                      % count($this->rotateDayClass)];
    if ($today->dateString()==$t->dateString()) {
      $class .= ' '.$this->todayClass;
    }
    return $class;
  }

  /**
  * Display the booking details in a list
  */
  function display() {
    return $this->displayAsTable();
  }

  /**
  * Display the booking details in a list
  */
  function displayAsTable() {
    $t = '<table class="tabularobject">';
    foreach ($this->bookinglist as $v) {
      #$t .= '<tr><td>'.$v[0].'</td><td>'.$v[1].'</td></tr>'."\n";
      $t .= $v->displayShort();
    }
    $t .= '</table>';
    return $t;
  }

  /**
  * Generate html for the booking details in a table with rowspan based on the duration of the booking
  *
  * @param SimpleTime $daystart    time from which bookings should be displayed
  * @param SimpleTime $daystop     time up until which bookings should be displayed
  * @param integer    $granularity seconds per row in display
  * @param integer    $reportPeriod  seconds between reporting the time in a column down the side
  * @return string   html representation of the calendar
  */
  function displayMonthAsTable($daystart, $daystop, $granularity, 
                                    $reportPeriod) {
    global $BASEPATH;
    $this->_breakAcrossDays();
//     echo $this->display();
    $matrix = $this->_collectMatrix($daystart, $daystop, $granularity);
    $numRowsPerDay =  $daystop->subtract($daystart) / $granularity;
    $numRows = ceil($this->numDays/7) * $numRowsPerDay;
   
    #report the time in a time column on the LHS every nth row:
    $timecolumn = array();
    $time = clone($daystart);
    for ($row=0; $row<$numRowsPerDay; $row++) {
      $timecolumn[$row] = clone($time);
      $time->addSecs($granularity);
    }

    $today = new SimpleDate(time());
    
    $t = '<table class="tabularobject calendar">';
    $weekstart = clone($this->start);
    $weekstart->addDays(-7);
    $t .= '<tr><th colspan="2"></th>';
    for ($day=0; $day<7; $day++) {
      $current = clone($weekstart);
      $current->addDays($day);
      $t .= '<th class="caldow">'.T_($current->dowShortStr()).'</th>';
    }
    $t .= '</tr>';
    for ($row = 0; $row < $numRows; $row++) {
      $dayRow = $row % $numRowsPerDay;
      if ($dayRow == 0) {
        $weekstart->addDays(7);
        $t .= '<tr><td colspan="2"></td>';
        for ($day=0; $day<7; $day++) {
          $current = clone($weekstart);
          $current->addDays($day);
          $isodate = $current->dateString();
          $class = $this->_getDayClass($today, $current);
          $zoomwords = sprintf(T_('Zoom in on %s'), $isodate);
          $t .= '<td class="caldatecell '.$class.'">';
          $t .= '<div style="float:right;"><a href="'.$this->href.'&amp;isodate='.$isodate.'" '
                  .'class="but" title="'.$zoomwords .'">'
              .'<img src="'.$BASEPATH.'/theme/images/zoom.png" '
                  .'alt="'.$zoomwords .'" class="calicon" /></a></div>'."\n";
          $t .= '<div class="caldate">' 
                . $current->dom();  
          $t .= '<span class="calmonth '
          #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
            .'startmonth' . '"> '
            . T_($current->moyStr())
          .'</span>';
          $t .= '</div>';
          $t .= '</td>';
        }
        $t .= '</tr>';
      }
      $t .= '<tr><td class="dummy"></td>';
      //$t .= '<tr><td class="dummy"><img src="/1x1.png" height="5" width="1" alt="" /></td>';
      if ($dayRow % $reportPeriod == 0) {
        //$t .= '<td colspan="2" rowspan="'.$reportPeriod.'">';
        $t .= '<td rowspan="'.$reportPeriod.'" class="timemark">';
        $t .= $timecolumn[$dayRow]->timeString();
        $t .= '</td>';
      }
      for ($day=0; $day<7; $day++) {
        $current = clone($weekstart);
        $current->addDays($day);
        #$currentidx = $current->dsDaysBetween($this->start); 
        // calculate the day number directly from the cell information rather than
        // using date-time functions. (add a small qty to the value so that floor doesn't
        // round down to the next integer below due to fp precision)
        $currentidx = floor($row / $numRowsPerDay + 0.05) * 7 + $day;
        if (isset($matrix[$currentidx][$dayRow])) {
          #$t .= '<td>';
          #preDump($matrix[$currentidx]->rows[$dayRow]);
          $b =& $matrix[$currentidx][$dayRow];
          $class = $this->_getDayClass($today, $b->booking->start);
          $class .= ($b->booking->isDisabled ? ' disabled' : '');
          //echo "$class <br />\n";
          $t .= "\n\t".$b->display($class, $this->href, $this->isAdminView)."\n";
          #$t .= '</td>';
        }
      }
      $t .= '</tr>';
    }
    $t .= '</table>';
    return $t;
  }

  /**
  * Display the booking details in a table with rowspan based on the duration of the booking
  *
  * @param SimpleTime $daystart    time from which bookings should be displayed
  * @param SimpleTime $daystop     time up until which bookings should be displayed
  * @param integer    $granularity seconds per row in display
  * @param integer    $reportPeriod  seconds between reporting the time in a column down the side
  * @return string   html representation of the calendar
  */
  function displayDayAsTable($daystart, $daystop, $granularity, 
                                    $reportPeriod) {
    global $BASEPATH;
    $this->_breakAcrossDays();
//     echo $this->display();
    $matrix = $this->_collectMatrix($daystart, $daystop, $granularity);
    $numRowsPerDay =  ceil($daystop->subtract($daystart) / $granularity);
    $numRows = $numRowsPerDay;

    #report the time in a time column on the LHS every nth row:
    $timecolumn = array();
    $time = $daystart;
    for ($row=0; $row<$numRowsPerDay; $row++) {
      $timecolumn[$row] = clone($time);
      $time->addSecs($granularity);
    }

    $today = new SimpleDate(time());
    
    $t = '<table class="tabularobject calendar" summary="'.('Day view of instrument bookings').'">';
    $t .= '<tr><td class="dummy"></td><th></th>';
    $t .= '<td class="caldayzoom">';
    $t .= '<div class="caldate">' 
          . $this->start->dom();
    $t .= '<span class="calmonth '
    #.($month == $lastmonth ? "contmonth" : "startmonth") . "'> "
      .'startmonth' . '"> '
      .T_($this->start->moyStr())
    .'</span>';
    $t .= '</div>';
    $t .= '</td>';
    $t .= '</tr>';
    for ($row = 0; $row < $numRows; $row++) {
      $t .= '<tr><td class="dummy"></td>';
      if ($row % $reportPeriod == 0) {
        $t .= '<td rowspan="'.$reportPeriod.'">';
        $t .= $timecolumn[$row]->timeString();
        $t .= '</td>';
      }
      if (isset($matrix[0][$row])) {
        #$t .= '<td>';
        #preDump($matrix[$currentidx]->rows[$dayRow]);
        $b =& $matrix[0][$row];
        $class = $this->_getDayClass($today, $b->booking->start);
        $t .= "\n\t".$b->display($class, $this->href, $this->isAdminView)."\n";
        #$t .= '</td>';
      }
      $t .= '</tr>';
    }
    $t .= '</table>';
    return $t;
  }
  
  /** 
  * logging function -- logs debug info to stdout
  *
  * The higher $priority, the more verbose (in the debugging sense) the output.
  *
  * @param string $string  text to be logged
  * @param integer $priority (optional, default value 10) debug level of the message 
  */
  function log ($string, $priority=10) {
    if ($priority <= $this->DEBUG) {
      echo $string.'<br />'."\n";
    }
  }
  
  /** 
  * time logging function -- logs the start and stop time of a booking or slot
  *
  * @param string $string  prefix to text to be logged
  * @param TimeSlot $slot    the slot whose start/stop is to be logged
  * @param boolean $display (optional ) use the displayStart rather than the start data in  $slot
  *
  * The higher $prio, the more verbose (in the debugging sense) the output.
  */
  function slotlog ($string, $slot, $display=false) {
    // Efficiency would suggest that &$slot would be better here, but bugs in PHP mean that we can't do that
    // see http://bugs.php.net/bug.php?id=24485 and http://bugs.php.net/bug.php?id=30787

    // short circuit the evaluation here -- if there's no logging going to be done then 
    // we don't want to make expensive dateTimeString() calls.
    if ($this->DEBUG < 10) return;

    if ($display) {
      $this->log($string.':start='.$slot->displayStart->dateTimeString() 
            .' '.$string.':stop='.$slot->displayStop->dateTimeString(), 10);
    } else {
      $this->log($string.':start='.$slot->start->dateTimeString() 
            .' '.$string.':stop='.$slot->stop->dateTimeString(), 10);
    }
  }
  
  /** 
  * time logging function 
  *
  * @param string $string  prefix to text to be logged
  * @param SimpleDate $slot    the time to be logged
  *
  * The higher $prio, the more verbose (in the debugging sense) the output.
  */
  function timelog ($string, $time) {
    // Efficiency would suggest that &$time would be better here, but bugs in PHP mean that we can't do that
    // see http://bugs.php.net/bug.php?id=24485 and http://bugs.php.net/bug.php?id=30787
    
    // short circuit the evaluation here -- if there's no logging going to be done then 
    // we don't want to make expensive dateTimeString() calls.
    if ($this->DEBUG < 10) return;
    
    $this->log($string.' '.$time->dateTimeString(), 10);
  }

    
} //class Calendar
