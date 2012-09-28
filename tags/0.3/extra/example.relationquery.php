<?php
  include('var.global.php');
  include('lib.nuevent.php');
  include('class.nuquery.php');
  include('class.relationquery.php');

  function my_custom_filter( $q )
  {
    $q->join('kronblr_profile KP', 'KP.user=U.id');
    $q->field( array('KP.name', 'KP.image') );
    return $q;
  }

  NuEvent::hook('nu_relation_query', 'my_custom_filter');

  $t0 = microtime(true);
  $q1 = new FollowerQuery(3);
  //echo $q1;
  //echo "\n";

  //$q1 = new FollowingQuery(3);
  //echo $q1;
  //echo "\n";
  echo "Time: " . (microtime(true) - $t0)*1000 . "\n";

?>
