<?php
  
  include('lib.keys.php');

  $GLOBALS['APPLICATION_AUTH_SECRET'] = hash('sha256','w&sxifldv0cwl112lrfkcod0 one+_fjd02ldkc');

  $user = 'someffdiowadfkdlj';
  $secret = hash("sha1", "jr340fj2fje02 fjdaklsf");
  $ts = 1251915521;

  $t0 = microtime(true);
  $k = Keys::auth( $user, $ts );
  echo (microtime(true) * $t0)*1000 . "\n";

  $ts = 1251915521;

  echo $k . " // " . Keys::auth( $user, $ts );

  echo "\n";
  
  $mykey = "fLSalw9jjzBi492Y+8nsjcZOrWk=-1251915521";
  $mykey = "hoF3fhIK4w64uxjKgY35Ztgzi+8=-1251915521";

  if( Keys::checkAuth( $user, $mykey ) )
    echo "User authorized";
  else
    echo "User not authorized";

  echo "\n";

?>
