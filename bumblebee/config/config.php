<?php
# $Id$

$CONFIG = parse_ini_file('bumblebee.ini',1);

$ADMINEMAIL = $CONFIG['main']['AdminEmail'];
$BASEPATH   = $CONFIG['main']['BasePath'];
$BASEURL    = $CONFIG['main']['BaseURL'];

$VERBOSESQL = $CONFIG['error_handling']['VerboseSQL'];

ini_set("session.use_only_cookies",1); #don't permit ?PHPSESSID= stuff
#ini_set("session.cookie_lifetime",60*60*1); #login expires after x seconds

if ($CONFIG['error_handling']['AllWarnings']) {
//this is nice for development but probably turn it off for production
  ini_set("error_reporting",E_ALL); #force all warnings to be echoed
}                  

?> 
