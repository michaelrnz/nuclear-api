<?php
	/*
		nuclear.framework
		altman,ryan,2008

		Files
		====================================
			simple file methods
	*/

	class Files
	{

		public static function &read($fn)
		{
			if( ($p=@fopen($fn, "rb")) && ($sz=filesize( $fn )) ){
				if( $sz>0 ){
					$r = fread( $p, $sz );
					fclose($p);
					return $r;
				}
			} $r=false; return $r;
		}

		public static function passthrough($fn, $content=false)
		{
			if( $p=@fopen($fn, "rb") )
			{
				if($content){header("Content-Type: $content");}
				fpassthru($p);
				fclose($p);
			}
		}


		public static function uri( $uri, $limit=false )
		{
			if( !$urlsrc = @fopen( $uri , 'r' ) ){ return false; }

			if( is_numeric($limit) ) return fread( $urlsrc, $limit );

			$rf = "";
			do
			{
				$b = fread( $urlsrc, 1024 );
				$rf .= $b;
			} while ( strlen($b)>0 );

			return $rf;
		}

	}

?>
