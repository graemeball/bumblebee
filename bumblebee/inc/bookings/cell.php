<?php
/**
* Booking cell object for display in a table
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Bookings
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** cell is in the middle of a booking */
define('MIDDLE_BOOKING',    1);
/** cell is at the start of a booking */
define('START_BOOKING',     2);
/** cell is at the start of a day's calendar display */
define('START_BOOKING_DAY', 4);

/**
* Booking cell object for display in a table
*
* @package    Bumblebee
* @subpackage Bookings
*/
class BookingCell {
  /** @var TimeSlot   Vacancy or Booking object for this cell    */
  var $booking;
  /** @var boolean    Cell is at start of booking   */
  var $isStart;
  /** @var boolean    Cell is at start of day  */
  var $isStartDay;
  /** @var integer    Number of rows in cell  */
  var $rows;
  /** @var array      list of class names to use for cells */
  var $rotaClass;
  /** @var string     data on which to rotate through classes */
  var $roton;
  /** @var string     class name to use if the cell is today  */
  var $todayClass;

  /**
  *  Create a new display cell
  *
  * @param TimeSlot $booking  the timeslot (Booking or Vacancy object) to be represented
  * @param integer  $start    type of cell (START_BOOKING, START_BOOKING_DAY, MIDDLE_BOOKING)
  * @param integer  $rows     number of rows this cell should occupy
  */
  function BookingCell(&$book, $start=START_BOOKING, $rows=1) {
    $this->booking = $book;
    $this->isStart    = $start & START_BOOKING;
    $this->isStartDay = $start & START_BOOKING_DAY;
    $this->rows    = $rows;
  }

  /**
  * use a number of different html/css classes for displaying cells
  *
  * (unused?)
  *
  * @param array  $arr       list of class names
  * @param string $roton     data on which to rotate through classes
  */
  function addRotateClass($arr, $roton) {
    $this->rotaClass   = $arr;
    $this->roton = $roton;
  }

  /**
  * html/css class to use if this day is today
  *
  * @param string  $c   class name
  */
  function addTodayClass($c) {
    $this->todayClass   = $c;
  }

  /**
  * prepare html representation of the cell
  *
  * @param string  $class   class name to use for the cell
  * @param string  $href    base href to be used for making links to book/edit
  * @param boolean $popup   provide a popup layer with extra details of the booking
  * @param boolean $isadmin provide an admin view of the data
  */
  function display($class, $href, $popup=false, $isadmin=0) {
    $this->_makePopupScript();
    $t = '';
    if ($this->isStart || $this->isStartDay) {
      $class .= ' '.$this->booking->baseclass;
      $popupControl='';
      if ($popup) {
        $message = '<b>'.$this->booking->generateBookingTitle().'</b><br />'
                  .$this->booking->generateLongDescription();
        $message = rawurlencode($message);
        $popupControl = 'onMouseOver="showCalendarPopup(\''.$message.'\');" '
                       .'onMouseOut="hideCalendarPopup();" ';;
      }
      $t .= '<td rowspan="'.$this->rows.'" class="'.$class.'" '
           .$popupControl
           .'title="'.$this->booking->generateBookingTitle().'">';
      $this->booking->href = $href;
      $t .= $this->booking->displayInCell($isadmin);
      $t .= '</td>';
    } else {
      $t .= '<!-- c:'.xssqw($this->booking->id).'-->';
    }
    return $t;
  }

  function _makePopupScript() {
    static $onceOnly = false;

    if ($onceOnly) return;
    $onceOnly = true;

    $width = 500;
    $offsety = 50;
    echo "
      <script type='text/javascript'>
      function showCalendarPopup(message) {
        showPopup(message, $width, $offsety);
      }
      function hideCalendarPopup() {
        return hidePopup();
      }
      </script>
    ";
  }


} //class BookingCell
?>
