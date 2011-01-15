<?php
    
    /*
        nuclear.framework
        altman,ryan,2010

        NuclearAPIMethod
        ====================================
            abstract class for API calls
            revision of CallWrapper
    */

    abstract class NuclearAPIMethod
    {
        protected static $globalField = "APICALL";
        protected $call;
        protected $response;
        protected $time;
        protected $output;


        function __construct($time=false, $output=true)
        {
            $this->call = $GLOBALS[ self::$globalField ];
            $this->time = $time;

            $this->process();

            if( $output )
            {
                echo $this;
            }
        }


        function __toString()
        {
            if( is_callable( array($this->response, "__toString") ) )
            {
                return $this->response->__toString();
            }

            if( $this->call->output == "xml" )
            {
                if( is_a($this->response, "DOMDocument") )
                {
                    return $this->response->saveXML();
                }
                else if( is_object( $this->response ) )
                {
                    $resp = new DOMDocument('1.0', 'UTF-8');
                    $resp->appendChild( object_to_xml( $this->response, $resp, 'response' ) );
                    return $resp->saveXML();
                }
                else if( is_array( $this->response ) )
                {
                    $resp = new DOMDocument('1.0', 'UTF-8');
                    $resp->appendChild( array_to_xml( $this->response, $resp, 'response', 'item' ) );
                    return $resp->saveXML();
                }
            }

            if( is_object( $this->response) || is_array( $this->response ) )
            {
                return json_encode( $this->response );
            }

            return "";
        }


        function __get($f)
        {
            switch($f)
            {

                case 'response':

                    return $this->response;

                default:

                    if( is_object($this->response) )
                    {
                        return $this->response->$f;
                    }
                    else if( is_array($this->response) && isset( $this->response[$f] ) )
                    {
                        return $this->response[$f];
                    }
                    break;
            }

            return null;
        }


        //
        // Process is called on construction
        //
        private function process()
        {
          $this->response = $this->build();
        }


        //
        // build must be implemented
        //
        protected function build()
        {
            throw new Exception("Method does not build", 5);
        }
    }

?>
