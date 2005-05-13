<?php
# $Id$
# allow the admin user to masquerade as another user to make some 
# bookings. A bit like "su".

include_once 'inc/bb/user.php';
include_once 'inc/formslib/anchortablelist.php';
include_once 'inc/actions/actionaction.php';

class ActionMasquerade extends ActionAction {

  function ActionMasquerade($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungePathData();
  }

  function go() {
    global $BASEURL;
    if (! isset($this->PD['id'])) {
      $this->selectUser();
    } elseif ($this->PD['id'] == -1) {
      $this->removeMasquerade();
    } else {
      $this->assumeMasquerade();
    }
    echo "<br /><br /><a href='$BASEURL/masquerade'>Return to user list</a>";
  }

  function selectUser() {
    global $BASEURL;
    $select = new AnchorTableList('Users', 'Select which user to masquerade as');
    $select->connectDB('users', array('id', 'name', 'username'),'id!='.qw($this->auth->uid));
    $select->hrefbase = $BASEURL.'/masquerade/';
    $select->setFormat('id', '%s', array('name'), ' %s', array('username'));
    if ($this->auth->amMasqed()) {
      $select->list->prepend(array('-1','End current Masquerade'));
      echo 'Currently wearing the mask of:'
        .'<blockquote class="highlight">'
        .$this->auth->ename.' ('.$this->auth->eusername.')</blockquote>';
    }
    echo $select->display();
  }

  function assumeMasquerade() {
  //FIXME: how do we handle instrument admin vs general user admin?
    if ($row = $this->auth->assumeMasq($this->PD['id'])) {
      echo '<h3>Masquerade started</h3>'
            .'<p>The music has started and you are now wearing the mask that looks like:</p>'
            .'<blockquote class="highlight">'.$row['name'].' ('.$row['username'].')</blockquote>'
            .'<p>Is that a scary thought?</p>'
            .'<p>When you are tired of wearing your mask, remove it by returning to the '
            .'Masquerade menu once more.</p>';
      echo '<p>Note that even with your mask on, you can only edit/create bookings on instruments '
            .'for which you have administrative rights.</p>';
    } else {
      echo '<div class="msgerror"><h3>Masquerade Error!</h3>'
          .'<p>Sorry, but if you\'re comming to a masquerade ball, '
          .'you really should wear a decent mask!</p>'
          .'<p>Masquerade didn\'t start properly: mask failed to apply and music didn\'t start.</p>'
          .'<p>Are you sure you\'re allowed to do this?</p></div>';
    }
  }
  
  function removeMasquerade() {
    $this->auth->removeMasq();
    echo '<h3>Masquerade finished</h3>'
          .'<p>Oh well. All good things have to come to an end. '
          .'The music has stopped and you have taken your mask off. </p>'
          .'<p>Hope you didn\'t get too much of a surprise when eveyrone else took their masks off too!</p>';
  }
  
  
  //**** FIXME: need to remind user they are masq'd still.


  function OLDactionMasquerade() {
    if (! isset($_POST['user'])) {
      echo "<h2>User masquerading</h2>";
      selectuser('masquerade','No masquerade','Masquerade');
      return;
    } elseif ($_POST['user'] >= 0) {
      echo "<input type='hidden' name='masquerade' value='".$_POST['user']."' />";
    }
    actionRestart('');
  }

  ### FIXME this is broken. needs to become cookies
  function checkMasquerade() {
    global $MASQUID, $MASQUSER;
    #displayPost();
    if (isset($_POST['masquerade'])) {
      $MASQUID = $_POST['masquerade'];
      $MASQUSER = getUsername($MASQUID);
      echo "<div class='masquerade'>"
          ."Masquerading as ".$MASQUSER[1] ." (" .$MASQUSER[0] .") ";
      echo "<input type='hidden' name='masquerade' value='".$_POST['masquerade']."' />";
      #echo "<button type='submit' name='changemasq' value='1'>Change Masquerade</button>";
      echo "</div>";
    }
  }
}
?>
