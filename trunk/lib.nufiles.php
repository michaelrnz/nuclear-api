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
    // Non-blocking url retrieval
    //
    private function deliverNonBlocking( &$sock, $payload, $timeout=60 )
    {
      // unblock
      stream_set_blocking($sock,0);

      $sent     = 0;
      $recv     = 0;

      // stream_select until payload delivered
      while( $sent<strlen($payload) || $recv==0 )
      {

        // muxers
        $read     = array( $sock );
        $write    = array( $sock );
        $error    = NULL;

        if( ($changes = stream_select( $read, $write, $error, $timeout )) !== false )
        {
            // check for input
            if( count($read) )
            {
                $data = fgets( $read[0], 1024 );
                $recv += strlen($data);
                //echo "SOCKET SAYS {$recv}: \n{$data}\n\n";
            }

            if( count($write) && $sent<strlen($payload) )
            {
                $sent += fwrite( $write[0], substr( $payload, $sent ) );
                //echo "SOCKET GETS {$sent}: \n" . substr( $payload, 0, $sent ) . "\n\n";
            }
        }
      }
    }

    public static function ping( $url, $port=false, $sleep=false )
    {
      $url = str_replace('http://','',$url);

      //
      // generate domain/req/headers
      $slash  = strpos($url,'/');
      if( $slash )
      {
        $domain = substr($url,0, $slash);
	$request= substr($url,$slash);
      }
      else
      {
        $domain  = $url;
	$request = '/';
      }

      //
      // header write
      $header = "GET {$request} HTTP/1.1\r\n";
      $header.= "Host: {$domain}\r\n";
      $header.= "Connection: Close\r\n\r\n";

      //
      // create resource
      $fp = fsockopen("tcp://" . $domain, $port ? $port : 80, $errno, $errstr, 10);

      if( !$fp )
      {
        return false;
      }
      else
      {
        self::deliverNonBlocking( $fp, $header );
      }

      return true;
    }

    //
    // File retrieval via curl
    //
    public function curl( $resource, $method="get", $fields=null, $auth=false )
    {
        if( strcasecmp($method,"get")==0 && is_array($fields) )
        {
            $resource .= "?";
            foreach( $fields as $k=>$v )
                $resource .= $k ."=". urlencode($v) ."&";
        }
            
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

      return $resp;
    }

  }

?>
