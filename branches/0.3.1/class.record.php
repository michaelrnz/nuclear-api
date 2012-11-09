<?php

    //
    // Nuclear
    // General Record class
    //
    class Record implements Iterator
    {
        protected $_values;
        protected static $_map = array();

        function __set($k, $v)
        {
            if( array_key_exists( $k, $this->getMap() ) )
            {
                $this->_values[$k] = $v;
            }
        }

        function __get($k)
        {
            if( array_key_exists( $k, $this->_values ) )
            {
                return $this->_values[$k];
            }

            return null;
        }

        function __toString()
        {
            return json_encode( (object) $this->_values );
        }

        protected function getMap()
        {
            return self::$_map;
        }

        public function rewind() {
            reset( $this->_values );
        }

        public function current() {
            return current( $this->_values );
        }

        public function key() {
            return key( $this->_values );
        }

        public function next() {
            return next( $this->_values );
        }

        public function valid() {
            return $this->current() !== false;
        }

        public function getObject()
        {
            $o = new Object();
            foreach( $this as $k=>$v )
            {
                $o->$k = $v;
            }
            return $o;
        }
    }

?>