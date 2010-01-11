<?php
	/*
	nuclear.framework
	altman,ryan 2009

	Fields Library
	=====================
	  basic field validations
	*/

	class Fields
	{
		public static function isValidEmailAddress($address='') 
		{
			$pattern = '/^(([a-z0-9!#$%&*+-=?^_`{|}~]'.'[a-z0-9!#$%&*+-=?^_`{|}~.]*'.'[a-z0-9!#$%&*+-=?^_`{|}~])'.'|[a-z0-9!#$%&*+-?^_`{|}~]|'.'("[^"]+"))'.'[@]'.'([-a-z0-9]+\.)+'.'([a-z]{2}'.'|com|net|edu|org'.'|gov|mil|int|biz'.'|pro|info|arpa|aero'.'|coop|name|museum)$/ix';
			return preg_match($pattern, $address);
		}

		public static function isValidURL( $url='' )
		{
			$pattern = "/^([a-z]\w+\.)?([a-z][a-zA-Z0-9\-]+)\.([a-z]{2}"."|com|net|edu|org)(\/[^\/]{0,128})?$/";
			return preg_match($pattern, $url);
		}

		public static function isValidPassword( $p='' )
		{
			return preg_match("/^.{6,64}$/", $p);
		}

		//
		// checks various input types
		//
		public static function isValid( $f, $v )
		{
			switch($f)
			{
				case 'username':
				case 'user': return preg_match( '/^([a-zA-Z0-9_]{3,64})$/', $v );

				case 'password':
				case 'pass': return self::isValidPassword($v);

				case 'email': return self::isValidEmailAddress($v);

				case 'domain':
				case 'site': return (strlen($v)==0 ? true : self::isValidURL( $v ));

				default: return false;
			}
		}
	}
?>
