<?php
  
  /*
    NuFiles - Basic file retrieval in Nuclear
    altman.ryan,2009
  */

  class NuFiles
  {
    //
    // Read and return
    //
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


    //
    // passthrough heading content-type
    //
    public static function passthrough($fn, $content=false)
    {
      if( $p=@fopen($fn, "rb") )
      {
        if($content){header("Content-Type: $content");}
        fpassthru($p);
        fclose($p);
      }
    }

    //
    // Field retreival via fopen
    //
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

    //
    // File retrieval via curl
    //
    public function curl( $resource, $method="get", $fields=null, $auth=false )
    {
      $csess = curl_init( $resource );

      curl_setopt( $csess, CURLOPT_HEADER, 0 );
      curl_setopt( $csess, CURLOPT_RETURNTRANSFER, 1 );

      if( $auth )
      {
        curl_setopt( $csess, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt( $csess, CURLOPT_USERPWD, "{$auth}");
      }

      if( strcasecmp($method,"post")==0 )
      {
        curl_setopt( $csess, CURLOPT_POST, 1 );
        curl_setopt( $csess, CURLOPT_POSTFIELDS, $fields );
      }

      $resp = curl_exec( $csess );

      curl_close( $csess );
    }

  }

?>
