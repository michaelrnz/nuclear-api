<?php
	/*
		nuclear.framework
		altman,ryan

		Time Utility class
		=========================
		various cool things, spans
	*/

	class NuclearTime
	{
		// gets the worded time spance from t1 to t2
		// t2 = now otherwise
		// p = least time period unit
		public static function wordSpan($t1, $t2=false, $p=false)
		{
			$span = self::span($t1, $t2, $p, $units);
			if($span != 1){
				$units.='s';
			}
			return $span . ' ' .$units . " ago";
		}


		// similar to above but stamp
		public static function wordStamp($str,$century=false)
		{
		    if(strlen($str)>0)
		    {
			$t1 = strtotime($str);
			$t2 = time();
			if($century)
			{ 
			    $t1 = self::trueTime($t1);
			    $t2 = self::trueTime($t2);
			}
			return self::wordSpan($t1,$t2);
		    }
		    return "unknown";
		}

		public static function strdate($m,$s)
		{
			return date($s, strtotime($m));
		}

		
		//
		// calculate the second-based difference
		// return the units or stick to a given units
		//
		public static function span($t1, $t2=false, $p=false, &$units=null)
		{
			$t = $t2?$t2:time();

			$int_d = $t-$t1;

			$len = 1;

			switch(true)
			{
				// Years
				case $p=='Y' && $int_d>=31557600:
					$len = 31557600;
					$units='year';
					break;		

				// Weeks, not Day/Hour
				case ($int_d>=604800 && strpos('-|D|H|', "|$p|")==0 ) || $p=='W':
					$len = 604800;
					$units='week';
					break;

				// Days, not Hour
				case ($int_d>=86400 && strpos('-|H|', "|$p|")==0) || $p=='D':
					$len = 86400;
					$units='day';
					break;

				// Hours
				case ($int_d>=3600 && !$p) || $p=='H':
					$len = 3600;
					$units='hour';
					break;

				// Minutes
				default:
					$len = 60;
					$units='minute';
					break;
			}

			return self::approx( $int_d, $len );
		}

		private static function approx($span,$length)
		{
			return round($span/$length);
		}
	}
?>
