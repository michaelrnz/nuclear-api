<?php

	/* 
		nuclear.framework
		altman,ryan,2008
		handling methods from php.net
		open,close,read,write,destroy,gc

		Sessions
		=========================================
			Remaps php's standard session
			handling, expiration, path
	*/

	class Sessions
	{
		public static function ipToINT($ipstr){
			$segs = explode('.', $ipstr);
			$ip = $segs[0] * 0x1000000
				+$segs[1] * 0x10000
				+$segs[2] * 0x100
				+$segs[3];
			return $ip;
		}
		public static function ipFromINT($int){
			$v4ip = array(0=> floor($int / 0x1000000));
			$v4ip[1] = ($int & 0xFF0000) >> 16;
			$v4ip[2] = ($int & 0xFF00) >> 8;
			$v4ip[3] = $int & 0xFF;
			$str = implode('.', $v4ip);
			return $str;
		}
		public static function open($save_path, $session_name)
		{
		  global $sess_save_path;

		  $sess_save_path = $save_path;
		  return(true);
		}

		public static function close()
		{
		  return(true);
		}

		public static function read($id)
		{
		  global $sess_save_path;

		  $sess_file = "$sess_save_path/sess_$id";
		  return (string) @file_get_contents($sess_file);
		}

		public function write($id, $sess_data)
		{
		  global $sess_save_path;

		  //
		  // NOTICE temp file used for clients quickly opening multiple requests
		  //
		  $sess_file = "$sess_save_path/sess_$id";
		  $tmp_sess = $sess_file . microtime(true);
		  if( $fp = @fopen($tmp_sess, "w") )
		  {
		    $return = fwrite($fp, $sess_data);
		    fclose($fp);

		    return ($return && rename($tmp_sess, $sess_file));
		  }
		  else
		  {
		    return(false);
		  }
		}

		public static function destroy($id)
		{
		  global $sess_save_path;

		  $sess_file = "$sess_save_path/sess_$id";
		  return(@unlink($sess_file));
		}

		public static function gc($maxlifetime)
		{
		  global $sess_save_path;

		  $c = time();
		  $maxl = intval( $maxlifetime );
		  foreach (glob("$sess_save_path/sess_*") as $filename) {
		    if ( (filemtime($filename) + $maxl) < $c) {
		      @unlink($filename);
		    }
		  }
		  return true;
		}

		//
		// SESSION INIT
		//
		function sessionLogged()
		{
		  $n = $GLOBALS['APPLICATION_SESSION'];
		  if( isset($_COOKIE[$n]) && preg_match('/^[a-zA-Z0-9]+$/', $_COOKIE[$n]) )
		  {
		    
		    // was or is logged in
		    // begin session
		    if( file_exists(session_save_path() . "/sess_{$_COOKIE[$n]}") && session_start() )
		    {
		      // check if really logged, else kill session
		      if( !isset($_SESSION['logged']) )
		      {
			setcookie($n, false, time()-3600, '/', $GLOBALS['APPLICATION_DOMAIN'], 1);
			session_destroy();
		      }
		    }
		    else
		    {
		      setcookie($n, false, time()+3600);
		    }
		  }
		}

		//
		// kill the session
		//
		public static function killSession()
		{
		  $n = $GLOBALS['APPLICATION_SESSION'];
		  $id = session_id();

		  //
		  // remove session data
		  $_SESSION = array();

		  //
		  // remove the cookie
		  if( isset($_COOKIE[$n]) )
		  {
		    //error_log("Removing cookie");
		    setcookie($n, false, time()-3600, '/', $GLOBALS['APPLICATION_DOMAIN'], 1);
		  }

		  //
		  // destroy
		  session_destroy();

		  return $id;
		}
	}

	//
	//  REMAP SESSION HANDLING
	//
	//session_cache_expire( 90000 );
	$sess_expire = isset( $GLOBALS['APPLICATION_SESSION_EXPIRE'] ) ? $GLOBALS['APPLICATION_SESSION_EXPIRE'] : "86400";

	if( !isset($GLOBALS['APPLICATION_SESSION']) )
	{
	  $GLOBALS['APPLICATION_SESSION'] = "sesshou";
	}

	ini_set('session.hash_function',1);
	ini_set("session.name", $GLOBALS['APPLICATION_SESSION']);
	ini_set("session.gc_maxlifetime", intval($sess_expire) + 7200); // ADDING TIME S\T the gc last's longer that the cookie
	ini_set("session.cookie_lifetime", $sess_expire);
	ini_set("session.cookie_path", "/");
	ini_set("session.cookie_domain", ".". $GLOBALS['APPLICATION_DOMAIN']);

	session_save_path( $GLOBALS['CACHE'] . 'sessions' );

	session_set_save_handler(
		array("Sessions","open"), 
		array("Sessions","close"), 
		array("Sessions","read"), 
		array("Sessions","write"), 
		array("Sessions","destroy"), 
		array("Sessions","gc"));

	//
	// only session if logged
	Sessions::sessionLogged();

?>
