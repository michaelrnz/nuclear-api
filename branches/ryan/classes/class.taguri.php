<?php
        /*
The way I want this to work:
- use valid taguri externally (percent-encoded)
- use non-percent encoded internally
- construct a tag id based on application
- lookup a tag id based on request
        */
    
    class TagURI
    {
        protected $id;
        protected $authority;
        protected $date;
        protected $specific;

        function __construct( $authority, $date, $specific )
        {
            $this->authority    = $authority;
            $this->date         = $date;
            $this->specific     = $specific;
        }

        function __toString()
        {
            $r = "tag:{$this->authority}," .
                 $this->date .":".
                 $this->specific;

            return $r;
        }

        function __set($f, $v)
        {
            if( $f=='id' )
                $this->id = $v;
        }

        function __get( $f )
        {
            switch( $f )
            {
                case 'id': return $this->id;
                case 'authority': return $this->authority;
                case 'date': return $this->date;
                case 'specific': return $this->specific;
            }
        }


        //
        // tuple data representation
        // safe for 'date' column in db
        //
        public function tuple()
        {
            $tuple = array();
            $tuple['authority'] = $this->authority;

            $date = explode('-', $this->date);
            switch( count($date) )
            {
                case '3':
                    $tuple['date'] = $this->date;
                    break;

                case '2':
                    $tuple['date'] = $this->date . '-00';
                    break;

                case '1':
                    $tuple['date'] = $this->date . '-00-00';
                    break;
            }

            $tuple['specific'] = $this->specific;

            return $tuple;
        }
        
        
        //
        // urlencode the taguri
        // only the specific  section will have pchar
        //
        public function urlencode()
        {
            $r = "tag:{$this->authority},{$this->date}:".
                  self::mapSpecific( 'urlencode', $this->specific);
            return $r;
        }
        
        
        //
        // exploding a taguri
        // we split apart auth/date/spec
        //
        public static function explode( $taguri )
        {
            // remove tag:
            $taguri = str_replace( 'tag:', '', $taguri );
            
            // explode into sections
            $sections = explode(':', $taguri, 2);
            
            // section[0] authority,date
            if( preg_match('/^((?:[0-9a-zA-Z][0-9a-zA-Z\-]*)(?:\.[a-zA-Z0-9][a-zA-Z0-9\-]*)*),(\d{4}(?:\-\d{2}(?:\-\d{2}))?)$/', $sections[0], $auth_date) )
            {
                $authority = $auth_date[1];
                $date           = $auth_date[2];
                
                if( count($sections)>1 )
                {
                    $specific = $sections[1];
                }
                else
                {
                    $specific = null;
                }
                
                return array( "authority"=>$authority, "date"=>$date, "specific"=>$specific );
            }
            
            return null;
        }
        
        
        //
        // map callback over specific
        // segment specific by : and /
        //
        public static function mapSpecific( $callback, $specific )
        {
            $segments   = explode(':', $specific);
            $specifics = array();
             
            foreach( $segments as $segment )
            {
                $specifics[] = implode( '/', array_map( $callback, explode( '/', $segment ) ) );
            }
            
            return implode(':', $specifics);
        }
        
        
        //
        // create new TagURI from encoded (urlencoded spec)
        //
        public static function fromEncoded( $taguri )
        {
            if( $tag = self::explode( $taguri ) )
            {
                if( $tag['specific'] )
                {
                    $specific = self::mapSpecific( 'urldecode', $tag['specific'] );
                    return new TagURI( $tag['authority'], $tag['date'], $specific );
                }
            }
            
            return null;
        }

        
        //
        // create new TagURI from decoded (non urlencoded)
        //
        public static function fromDecoded( $taguri )
        {
            if( $tag = self::explode( $taguri ) )
            {
                if( $tag['specific'] )
                {
                    return new TagURI( $tag['authority'], $tag['date'], $tag['specific'] );
                }
            }
            
            return null;
        }
        
        
        //
        // create TagURI based on regex parse
        //
        public static function parse( $taguri )
        {
            if( preg_match('/^(?:tag:)?((?:[0-9a-zA-Z][0-9a-zA-Z\-]*)(?:\.[a-zA-Z0-9][a-zA-Z0-9\-]*)*),(\d{4}(?:\-\d{2}(?:\-\d{2}))?):([\-_\/a-zA-Z0-9%\?:]+)$/', $taguri, $data) )
            {
                return new TagURI( $data[1], $data[2], $data[3] );
            }

            return null;
        }
    }

?>
