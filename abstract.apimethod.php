<?php
    
    /*
        nuclear.framework
        altman,ryan,2010

        NuclearAPIMethod
        ====================================
            abstract class for API calls
            revision of CallWrapper
    */
function json_format($input){
    $tab = 0;
    $out = "";
    $tabs = "";
    for($i=0;$i<strlen($input);$i++){
        $c = $input[$i];
        $tabs = "";
        for($t=1;$t<=$tab;$t++)
            $tabs .= "  ";
        switch($c){
            case "{":
                $tab++;
                $tabs .= "  ";
                $out .= "{\n".$tabs;
                break;
            case "}":
                $tab--;
                $tabs[strlen($tabs)-2] = "";
                $out .= "\n".$tabs."}";
                break;
            case "[":
                $tab++;
                $tabs .= "  ";
                $out .= "[\n".$tabs;
                break;
            case "]":
                $tab--;
                $tabs[strlen($tabs)-2] = "";
                $out .= "\n".$tabs."]";
                break;
            case ",": $out .= ",\n".$tabs; break;
            default: $out .= $c; break;
        }
    }
    $out = str_replace("\x00", "", $out);
    return $out;
}

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
            $this->output = $output;

            $this->process();

            if( $output )
            {
                echo $this;
            }
        }


        function __toString()
        {
            if( $this->call->output != "json" && is_callable( array($this->response, "__toString") ) )
            {
                if( $this->call->output == "atom" )
                    header('Content-type: application/atom+xml');

                if( $this->call->output == "rss" )
                    header('Content-type: application/rss+xml');

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

            if( $this->call->output == "xml" )
            {
                if( is_a($this->response, "DOMDocument") )
                {
                    return $this->response->saveXML();
                }
                else if( is_object( $this->response ) )
                {
                    $resp = new DOMDocument('1.0', 'UTF-8');
                    $resp->formatOutput = true;
                    $resp->appendChild( object_to_xml( $this->response, $resp, 'response' ) );
                    return $resp->saveXML();
                }
                else if( is_array( $this->response ) )
                {
                    $resp = new DOMDocument('1.0', 'UTF-8');
                    $resp->formatOutput = true;
                    $resp->appendChild( array_to_xml( $this->response, $resp, 'response', 'item' ) );
                    return $resp->saveXML();
                }
            }

            if( is_object( $this->response) || is_array( $this->response ) )
            {
                if( !is_null($this->call->pretty_json) )
                    return json_format(json_encode( $this->response ));

                if( is_callable( array($this->response, "__toString") ) )
                    return $this->response->__toString();

                $cb = preg_replace('/[^a-zA-Z0-9_\.\(\)\'"\$, ]/', "", $this->call->callback);

                return ($cb ? "{$cb}(":"") . json_encode( $this->response ) . ($cb ? ");":"");
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
