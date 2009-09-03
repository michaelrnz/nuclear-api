<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Registration Lib
		================================
			methods for dealing with
			registrations
	*/

	require_once('lib.text.php');
	require_once('wrap.mysql.php');

	class Register
	{
		//
		// handle post
		//
		public static function post( &$reg )
		{
			//
			// gather fields
			$u=$reg->user;
			$p=$reg->password;
			$e=$reg->email;
			$s=$reg->site;

			//
			// check for available user
			if( !($available= self::isAvailableUser($u, $e)) )
			{
				return false;
			}

			//
			// insert for verification
			$verify = self::insert( $u, $p, $e, $s );

			//
			// email verification
			if( $verify!==0 )
			{
				self::emailVerification( $e, str_replace(' ','+',$verify.'/'.$u) );

				return $verify;
			}
			else
			{
				return false;
			}
		}

		//
		// insert into verification
		//
		public static function insert( $u, $p, $e, $s )
		{
			require_once('lib.keys.php');

			//
			// this is the storage of passwords, md5 but combination of login and pass
			$pass= Keys::password( $u, $p );

			//
			// compute the verification hash
			$verify= implode( '', Keys::generate( $u . number_format((microtime(true) * rand())) . $e) );

			//
			// compose query
			$q= "INSERT INTO nuclear_verify (user, pass, email, domain, hash) VALUES ('$u', '$pass', '$e', '$s', '$verify');";

			//
			// wrap insert affected
			if( WrapMySQL::affected( $q, "Unable to insert user verification" ) > 0 )
			{
				return $verify;
			}

			return 0;
		}
	
		//
		// check user existence in db
		//
		public static function isAvailableUser($user,$email)
		{
			$q= "SELECT IF(name='$user',1,0) AS name, IF(email='$email',1,0) AS email FROM nuclear_user WHERE name='$user' || email='$email';";
			$r = WrapMySQL::q( $q, "Unable to check user availability");

			if( $r && mysql_num_rows( $r )>0 )
			{
				$row = mysql_fetch_row($r);
				if( $row[0] == 1 )
				{
					$GLOBALS['post_error'] = array('user'=>"User exists");
				}
				else
				{
					$GLOBALS['post_error'] = array('email'=>'Email exists');
				}
				return false;
			}
			else
			{
				return true;
			}
		}

		//
		// send the email verification/could be a bit quarky
		//
		public function emailVerification($rcpt, $verify)
		{

			//
			// body
			$body = "Thank you for registering at {$GLOBALS['APPLICATION_NAME']}!<br />\n".
				"Please click the following link to complete your account verification.<br />\n".
				"<a href=\"http://{$GLOBALS['APPLICATION_DOMAIN']}/verify/$verify\">http://{$GLOBALS['APPLICATION_DOMAIN']}/verify/$verify</a>.<br /><br />\n".
				"Here is your verification code for alternate uses: $verify<br /><br />\n".
				"If you did not sign up for an account, please disregard this e-mail.<br /><br />\n".
				"Thank you";

			//
			// content headers
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8\r\n";

			//
			// Additional headers
			$headers .= "From: {$GLOBALS['APPLICATION_NAME']} <{$GLOBALS['REGISTRATION_MAIL']}>" . "\r\n";
			$subject= 'Your registration is almost complete!';

			//
			// send mail
			mail($rcpt,$subject,$body,$headers);

		}
	}

?>
