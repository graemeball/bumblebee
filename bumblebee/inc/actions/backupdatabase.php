<?php
/**
* Create a dump of the database for backup purposes
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Actions
*
* path (bumblebee root)/inc/actions/backupdatabase.php
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

require_once 'inc/bb/configreader.php';

/** parent object */
require_once 'inc/actions/bufferedaction.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';

/**
* Create a dump of the database for backup purposes
* @package    Bumblebee
* @subpackage Actions
*/
class ActionBackupDB extends BufferedAction {

  /**
  * Initialising the class
  *
  * @param  BumblebeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionBackupDB($auth, $pdata) {
    parent::BufferedAction($auth, $pdata);
  }

  function go() {
    $success = $this->makeDump();
    //echo $success;
    //echo $this->errorMessage;
    echo $this->reportAction($success,
              array(STATUS_ERR =>  T_('Error making backup: ').$this->errorMessage
                   )
               );
  }

  /**
  * Make the sql dump and save it to memory for output later
  */
  function makeDump() {
    // get a MySQL dump of the database
    $conf = ConfigReader::getInstance();
    $output = array();
    $retstring = exec($this->_mysqldump_invocation() .' 2>&1',
                $output,
                $returnError);
    $dump = join($output, "\n");
    if ($returnError) {
      return $this->unbufferForError($dump);
    } else {
      // $dump now contains the data stream.
      // let's work out a nice filename and dump it out
      $this->filename = $this->getFilename('backup', $conf->value('database', 'dbname'), 'sql');
      $this->bufferedStream = $dump;
      // the data itself will be dumped later by the action driver (index.php)
    }
  }


  /**
  * Send the sql dump to the browser immediately
  * (can't be used if you have heavy HTML templates)
  */
  function godirect() {
    $this->startOutputTextFile($filename);
    system($this->_mysqldump_invocation(),
                $returnError);
  }

  /**
  * Obtain the correct mysqldump command line to make the backup
  */
  function _mysqldump_invocation() {
    $conf = ConfigReader::getInstance();
    return $conf->value('sqldump', 'mysqldump').' '
                .$conf->value('sqldump', 'options')
                .' --host='.escapeshellarg($conf->value('database', 'host'))
                .' --user='.escapeshellarg($conf->value('database', 'username'))
                .' --password='.escapeshellarg($conf->value('database', 'passwd'))
                .' '.escapeshellarg($conf->value('database', 'database'))
            ;
  }
}

?>
