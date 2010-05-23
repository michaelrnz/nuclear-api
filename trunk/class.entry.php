<?php

    /*
        nuclear.framework
        altman,ryan,2010

        Entry
        ================================
            Entry is a general container
            within the native federation
            of Nuclear.

    */

    class SerialObject extends Object
    {
        private $output_mode    = "json";

        function __toString()
        {
            switch( $this->output_mode )
            {
                case 'json':
                    return json_encode( $this );

                case 'xml':
                    return $this->xml_container()->__toString();
            }

            return "";
        }

        public function xml()
        {
            $this->output_mode = "xml";
            return $this;
        }

        public function json()
        {
            $this->output_mode = "json";
            return $this;
        }

        public function xml_container()
        {
            require_once('class.xmlcontainer.php');

            $resp = new XMLContainer('1.0', 'utf-8');
            $resp->preserveWhiteSpace = false;
            $resp->formatOutput = true;
            $resp->appendRoot( object_to_xml( $this, $resp, 'entry' ) );

            return $resp;
        }
    }


    class Author extends SerialObject
    {
        public $id;
        public $name;
        public $display_name;
        public $screen_name;
        public $gravatar;

        /*
        public $location;
        public $time_zone;
        public $gmt_offset;
        public $country;
        public $iso;
        public $language;
        public $description;
        */
    }


    class Entry extends SerialObject
    {
        public $published;
        public $updated;
        public $text;
        public $visibility;
        public $source;
        public $author;

        /*
        public $id;
        public $guid;
        public $type;
        public $title;
        public $summary;
        public $content;

        public $actions;
        public $attachments;

        public $in_reply_to;
        */
    }

?>