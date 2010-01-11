<?php
	
	/*
		nuclear.framework
		altman,ryan,2008

		User Registration class
		======================================
			defines valid user and attributes
			for registration. used by API
	*/

	require_once('lib.fields.php');

	class UserRegistration extends Object
	{
		public $user;
		public $email;
		public $password;
		public $site;

		private $valid;

		private static $fields = array('user','email','password','site');
		
		//
		// takes details object, web for 2xpass
		//
		public function __construct( $details, $web=false )
		{
			//
			// set fields to valid
			//
			$this->valid=array(true);

			//
			// build
			//
			$this->build( $details, $web );
		}
		
		public function isValid()
		{
			return $this->valid[0];
		}
		public function invalid($nm)
		{
			return isset($this->valid[$nm]);
		}
		
		//
		// build registration
		//
		private function build( $d, $web=false )
		{
			//
			// test for web post
			//
			if( $web && count($_POST)==0 )
			{
				$this->valid[0]=false;
			}
			else
			{
				//
				// build details
				$details= array();
				foreach(self::$fields as $f)
				{
					//
					// get value from call
					$v = trim( preg_replace('/ [ ]+/', ' ', $d->$f) );

					// check for some value
					if( $v )
					{
						$details[ $f ] = self::clean($f, $v);

						if( Fields::isValid( $f, $v )!=1 )
						{
							$GLOBALS['post_error'] = $f;
							throw new Exception( "Invalid data in registration: " . $f . self::restrictions($f) );
							/*
							$this->valid[0]=false;
							$this->valid[$f]='Error';
							*/
						}
					}
					else if( $f != 'site' )
					{
						throw new Exception( "Missing registration field: " . $f );
					}
				}
				
				//
				// check dup pass
				if( $web && ($details['password'] != $d->vpass) )
				{
					$GLOBALS['post_error'] = 'vpass';
					throw new Exception( "Registration: passwords to not match." );
				}

				// assign
				foreach( self::$fields as $f )
				{
					$this->$f = $details[$f];
				}
			}
		}

		public static function clean( $f, $v )
		{
			switch( $f )
			{
				case 'site':
					//
					// cleans http:// and trailing /
					return preg_replace(array('/^http:\/\//','/\/+$/','/\\\'/'), array('','',''), $v);

				default:
					return $v;
			}
		}

		private static function restrictions( $f )
		{
			switch( $f )
			{
				case 'user':
					return ", may only contain alpha-numerics (spaces are currently disabled)";
				case 'password':
					return ", may only contain alpha-numerics, spaces, and special characters !@#$%^&*_";
				default:
					return "";
			}
		}

	}

?>
