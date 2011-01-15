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
	require_once('lib.nuuser.php');

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

			//
			// check for available user
			if( !($available= self::isAvailableUser($u, $e)) )
			{
				return false;
			}

			//
			// insert for verification
			$verify = self::insert( $u, $p, $e );

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
		public static function insert( $u, $p, $e )
		{
			require_once('lib.keys.php');

			//
			// this is the storage of passwords, md5 but combination of login and pass
                        $pass= new NuclearPassword( $u, $p );

			//
			// compute the verification hash
                        $verify= new NuclearVerification( $u . number_format((microtime(true) * rand())) . $e );

			//
			// compose query
			$q= "INSERT INTO nuclear_verify (user, auth, email, hash) VALUES ('$u', '$pass', '$e', '{$verify->token}');";

			//
			// wrap insert affected
			if( WrapMySQL::affected( $q, "Unable to insert user verification" ) > 0 )
			{
				return $verify->token;
			}

			return 0;
		}
	
		//
		// check user existence in db
		//
		public static function isAvailableUser($user,$email)
		{

			// check GLOBAL user, TODO DOMAIN_ID global, generated
			$id = NuUser::userID( $user, $GLOBALS['DOMAIN'], 0 );
			if( $id>0 )
			{
				$GLOBALS['post_error'] = array('user'=>"User exists");
				return false;
			}

			// check email
			$ct = WrapMySQL::single(
				"select id from nuclear_user where email='$email' limit 1;", 
				"Unable to check user email");

			if( $ct && $ct[0] )
			{
				$GLOBALS['post_error'] = array('email'=>'Email exists');
				return false;
			}

			return true;
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
				"<a href=\"http://{$GLOBALS['DOMAIN']}/verify/$verify\">http://{$GLOBALS['DOMAIN']}/verify/$verify</a>.<br /><br />\n".
				"Here is your verification code for alternate uses: $verify<br /><br />\n".
				"If you did not sign up for an account, please disregard this e-mail.<br /><br />\n".
				"Thank you,<br />{$GLOBALS['APPLICATION_NAME']}";

			//
			// content headers
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= "Content-Type: text/html; charset=utf-8\r\n";

			//
			// Additional headers
			$headers .= "From: {$GLOBALS['REGISTRATION_MAIL']} <{$GLOBALS['REGISTRATION_MAIL']}>" . "\r\n";
			$subject= 'Your registration is almost complete!';

			//
			// send mail
			mail($rcpt,$subject,$body,$headers);

		}
	}

?>
