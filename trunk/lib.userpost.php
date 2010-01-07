<?php
	
	/*

	nuclear.framework
	altman,ryan,2008

	UserPost library
	==============================================
		various methods for user posting
	
	*/

	require_once('wrap.mysql.php');
	require_once('class.eventlibrary.php');
	require_once('lib.keys.php');

	class UserPost extends EventLibrary
	{
		protected static $listeners = null;
		
		public static function resetPassword( $email )
		{
			// test for email validity
			$e = str_replace("'", "", $email);
			
			// get user or email data
			$r = WrapMySQL::single("SELECT * FROM nuclear_user where email='$email' LIMIT 1;", "Unable to query user email");

			if( !$r ) return array(false, "User does not exist");

			// create hash
			$h = implode( '', Keys::generate( $r['id'] . number_format((microtime(true) * rand())) . $e ) );

			// insert hash to password table
			$c = WrapMySQL::affected( "INSERT INTO nuclear_password_reset (user,name,hash) VALUES ({$r['id']},'{$r['name']}', '$h');", "Unable to create reset hash");

			if( !$c ) return array(false, "Could not insert reset data");

			// send
			self::sendResetVerification( $e, $h );

			// fire onHashed
			$o = new Object();
			$o->details = $r;
			$o->hash = $h;
			self::fire('BeginReset',$o);

			return array($c,"Check email");
		}

		public static function changeEmail( $id, $user, $p, $email )
		{
			require_once('lib.fields.php');
			require_once('lib.id.php');

			// clean for whatever
			$e = preg_replace("/'/","",$email);

			// check if valid
			if( !Fields::isValidEmailAddress( $e ) )
			{
				throw new Exception("Invalid email format.");
			}

			// check user password
			if( ID::checkUserPassword( $id, Keys::password($user, $p) ) )
			{
				// generate hash
				$hash = implode( '', Keys::generate( $id . number_format((microtime(true) * rand())) . $e ) );

				// insert data into table
				WrapMySQL::void( "INSERT INTO nuclear_change_email (user, hash, email) VALUES($id, '$hash', '$e');",
						 "Error while inserting email verification to database." );

				// send validation to new email
				self::sendChangeEmailVerification( $e, $hash );

				return true;
			}
			else
			{
				return false;
			}
		}

		public static function verifyChangeEmail( $id, $h )
		{
			$hash = str_replace(' ','+',$h);

			if( preg_match('/^[0-9a-zA-Z_\+=]{44}$/', $hash)==0 )
				throw new Exception("Invalid hash format.");

			return WrapMySQL::affected(
				"UPDATE nuclear_user LEFT JOIN nuclear_change_email ON nuclear_change_email.nuclear_user=user.id
				SET nuclear_user.email=_change_email.email
				WHERE nuclear_change_email.nuclear_user=$id && nuclear_change_email.hash='$hash';",
				"Error while updating email" );
		}

		public static function verifyResetPassword( $c )
		{
			//
			// basic validations
			//
			if( !($user = str_replace("'","",$c->user)) ) throw new Exception("Missing username for password reset");
			if( !($hash = str_replace(' ','+',$c->hash)) ) throw new Exception("Missing hash for password reset");
			if( !($password = $c->password) ) throw new Exception("Missing password for password reset");

			//
			// check hash size
			if( !(preg_match('/^[a-zA-Z0-9=_\+]{44}$/', $hash)) ) throw new Exception("Invalid hash format");

			//
			// check for hash
			if( !($r = WrapMySQL::single("SELECT user, name FROM nuclear_password_reset WHERE name='$user' && hash='$hash' LIMIT 1;", "Error querying reset hash.")) )
				return array(false, "Invalid reset key");

			// reset password
			if( !self::completeResetPassword( $r['user'], $r['name'], $password ) )
			  $same = true;
			else
			  $same = false;

			// remove hash
			WrapMySQL::affected(
				"DELETE FROM nuclear_password_reset WHERE user={$r['user']} LIMIT 1;",
				"Unable to remove reset hash");
			if( $same )
			  return array(false, "Password not changed; same");

			// return
			return array(true, "Password changed successfully");
		}

		//
		// changing password
		// requires valid u/p of old
		//
		public static function changePassword( $id, $u, $old_p, $new_p )
		{
			require_once('lib.fields.php');
			require_once('lib.id.php');

			// check password format
			if( !Fields::isValidPassword( $new_p ) )
			{
				throw new Exception("Invalid password format {6,64}.");
			}

			$pass_old = Keys::password( $u, $old_p );
			$pass_new = Keys::password( $u, $new_p );

			if( ID::checkUserPassword( $id, $pass_old ) )
			{
				return self::setPassword( $id, $pass_new );
			}

			return false;
		}

		// completion of reset
		private static function completeResetPassword( $id, $u, $p )
		{
			require_once('lib.fields.php');

			// check password format
			if( !Fields::isValidPassword( $p ) )
			{
				throw new Exception("Invalid password format {6,64}.");
			}

			// hash the pass
			$pass= Keys::password( $u, $p );

			return self::setPassword( $id, $pass );
		}

		// hard reset in db
		private static function setPassword( $id, $pass )
		{
			return WrapMySQL::affected("UPDATE nuclear_userkey SET pass='$pass' WHERE id=$id LIMIT 1;", "Error on password update");
		}

		//
		// ACCOUNTS DESTROY
		//

		public static function accountDestroyRequest ( $email )
		{
			// test for email validity
			$e = str_replace("'", "", $email);
			
			// get user or email data
			$r = WrapMySQL::single("SELECT * FROM nuclear_user where email='$email' LIMIT 1;", "Unable to query user email");

			if( !$r ) return array(false, "User does not exist");

			// create hash
			$h = implode( '', Keys::generate( $r['id'] . number_format((microtime(true) * rand())) . $e ) );

			// insert hash to password table
			$c = WrapMySQL::affected( "INSERT INTO nuclear_account_destroy (id,name,hash) VALUES ({$r['id']},'{$r['name']}', '$h');", "Unable to create verification hash");

			if( !$c ) return array(false, "Could not insert destroy data");

			// send
			self::sendDestroyVerification( $e, $h );

			// fire onHashed
			$o = new Object();
			$o->details = $r;
			$o->hash = $h;
			self::fire('NuclearAccountDestroyRequested',$o);

			return array($c,"Please check email for verification");
		}

		public static function accountDestroyVerification( $c )
		{
			//
			// basic validations
			//
			if( !($user = str_replace("'","",$c->user)) ) throw new Exception("Missing username for password reset",4);
			if( !($hash = str_replace(' ','+',$c->hash)) ) throw new Exception("Missing hash for password reset",4);

			//
			// check hash size
			if( !(preg_match('/^[a-zA-Z0-9=_\+]{40,60}$/', $hash)) ) throw new Exception("Invalid hash format", 5);

			//
			// check for hash
			if( !($r = WrapMySQL::single("SELECT id FROM nuclear_account_destroy WHERE name='$user' && hash='$hash' LIMIT 1;", "Error querying reset hash.")) )
		          return array(false, "Invalid destroy verification");

			// destroy account 
			if( !self::accountDestroy( $r['id'], $user ) )
			  return array(false, "Account not destroyed");

			// remove hash
			WrapMySQL::void(
				"DELETE FROM nuclear_account_destroy WHERE id={$r['id']} LIMIT 1;",
				"Unable to remove destroy hash");

			// return
			return array(true, "Account destroyed");
		}

		private static function accountDestroy( $user_id, $user_name )
		{
		  $tables = array(
		    "nuclear_user",
		    "nuclear_username",
		    "nuclear_userkey",
		    "nuclear_system");

		  $a = 0;
		  foreach( $tables as $T )
		  {
		    $a += WrapMySQL::affected(
		     "delete from {$T} where id={$user_id} limit 1;",
		     "Error destroying user data"
		    );
		  }

		  //
		  // relation graphs
		    WrapMySQL::void(
		     "delete from nuclear_friendship where user0={$user_id} || user1={$user_id};"
		    );

		  //
		  // api auth
		    WrapMySQL::void(
		     "delete from nuclear_api_auth where user={$user_id};"
		    );

		  //
		  // log
		    WrapMySQL::void(
		     "insert into nuclear_accounts_destroyed (id, name) values ({$user_id}, '{$user_name}');"
		    );

		  return $a;
		}
		
		//
		// MAILERS
		//

		private static function sendDestroyVerification( $rcpt, $hash )
		{
			$body = "In order to complete your account removal please continue to the following link:<br /><br />" . 
				"<a href=\"http://{$GLOBALS['DOMAIN']}/account/destroy/$hash\">http://{$GLOBALS['DOMAIN']}/account/destroy/$hash</a><br /><br />" . 
				"If you did not initiate a account removal, please report this to {$GLOBALS['SUPPORT_MAIL']}.<br /><br />" . 
				"Thank you,<br />{$GLOBALS['APPLICATION_NAME']}";

			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers.= "Content-Type: text/html; charset=utf-8\r\n";
			$headers.= "From: {$GLOBALS['SUPPORT_MAIL']} <{$GLOBALS['SUPPORT_MAIL']}>\r\n";

			$subject = "Complete your {$GLOBALS['APPLICATION_NAME']} account removal\r\n";

			// send
			mail( $rcpt, $subject, $body, $headers );
		}


		private static function sendResetVerification( $rcpt, $hash )
		{
			$body = "In order to complete your password reset please continue to the following link:<br /><br />" . 
				"<a href=\"http://{$GLOBALS['DOMAIN']}/reset/$hash\">http://{$GLOBALS['DOMAIN']}/reset/$hash</a><br /><br />" . 
				"If you did not initiate a password reset, please report this to {$GLOBALS['SUPPORT_MAIL']}.<br /><br />" . 
				"Thank you,<br />{$GLOBALS['APPLICATION_NAME']}";

			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers.= "Content-Type: text/html; charset=utf-8\r\n";
			$headers.= "From: {$GLOBALS['PASSWORD_MAIL']} <{$GLOBALS['PASSWORD_MAIL']}>\r\n";

			$subject = "Reset your {$GLOBALS['APPLICATION_NAME']} password\r\n";

			// send
			mail( $rcpt, $subject, $body, $headers );
		}

		private static function sendChangeEmailVerification( $rcpt, $hash )
		{
			$body = "In order to complete your change of email, please continue to the following link:<br /><br />" . 
				"<a href=\"http://{$GLOBALS['DOMAIN']}/mailverify/$hash\">http://{$GLOBALS['DOMAIN']}/mailverify/$hash</a><br /><br />" . 
				"If you did not initiate a change of email, please report this to {$GLOBALS['SUPPORT_MAIL']}.<br /><br />" . 
				"Thank you,<br />{$GLOBALS['APPLICATION_NAME']}<br /><br />" . 
				"Note: For security purposes, you must be logged on to complete this process.";

			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers.= "Content-Type: text/html; charset=utf-8\r\n";
			$headers.= "From: {$GLOBALS['AUTH_MAIL']}\r\n";

			$subject = "Complete your change of email on {$GLOBALS['APPLICATION_NAME']}\r\n";

			// send
			mail( $rcpt, $subject, $body, $headers );
		}

	}

	// begin handling
	UserPost::init();

?>
