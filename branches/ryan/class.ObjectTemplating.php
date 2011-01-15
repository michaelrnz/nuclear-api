<?php

    /**
     *
     * ObjectTemplating singleton
     * basic {block} templating service
     *
    **/

    class ObjectTemplating implements iSinglton
    {
        const start_block   = '{block:';
        const end_block     = '{/block:';

        private static $inst;
        private $cache;
        private $index;
        private $object;
        private $template;
        private $buffer;
        private $save;

        function __construct()
        {
            $this->cache = array();
            $this->index = false;
            $this->save = true;
        }

        public static function getInstance()
        {
            if( is_null(self::$inst) )
                self::$inst = new MelativeToolsTemplating();

            return self::$inst;
        }

        public function load( $file )
        {
            $hash = hash("md5", $file);

            if( array_key_exists($hash, $this->cache) )
                $this->index = $hash;

            if( file_exists($file) )
            {
                $this->cache[$hash] = file_get_contents($file);
                $this->index = $hash;
            }
            else
            {
                $this->index = false;
            }

            return $this;
        }

        public function apply($object)
        {
            $this->object = $object;

            if( $this->index != false )
            {
                $this->template = $this->cache[ $this->index ];
                $this->buffer   = "";

                $index = 0;
                while( $index < strlen($this->template) )
                    $index = $this->text( $index );

                return $this->buffer;
            }

            return "";
        }

        public function list_apply( $arr )
        {
            if( !is_array($arr) ) return "";

            $b = "";
            foreach( $arr as $o )
                $b .= $this->apply( $o );

            return $b;
        }

        private function resolve( $key, $assert=false )
        {
            $props = explode('/', $key);

            $branch = $this->object;
            foreach( $props as $k )
            {
                $k2 = strstr($k, ':');

                if( $k2 )
                {
                    $func = str_replace($k2, '', $k);
                    $k = trim($k2,':');
                }
                else
                {
                    $func = false;
                }

                if( is_object($branch) && isset($branch->$k) )
                {
                    if( is_callable($func) )
                        $branch = call_user_func( $func, $branch->$k );
                    else
                        $branch = $branch->$k;
                }
                else
                {
                    return $assert ? false : null;
                }
            }

            return $assert ? true : $branch;
        }

        private function test( $key )
        {
            return $this->resolve( $key, true );
        }

        private function text( $index=0 )
        {
            $next_brace     = strpos( $this->template, '{', $index );

            if( $next_brace === false )
                $len = -1;
            else
                $len = $next_brace - $index;

            if( $this->save )
                $this->buffer .= substr( $this->template, $index, $len );

            if( $next_brace )
            {
                $index = $next_brace;

                if( substr( $this->template, $index, 7 ) == self::start_block )
                {
                    return $this->open_block( $index+7 );
                }
                else if( substr( $this->template, $index, 8 ) == self::end_block )
                {
                    $this->buffer = rtrim( $this->buffer );
                    return $index;
                }
                else
                {
                    $next_brace = strpos( $this->template, '}', $index );

                    if( $next_brace !== false )
                    {
                        if( $this->save )
                            $this->buffer .= $this->resolve( trim(substr( $this->template, $index, $next_brace-$index ), '{}') );
                        $index = $next_brace + 1;
                    }

                    return $this->text( $index );
                }
            }

            return strlen($this->template);
        }

        private function open_block( $index=0 )
        {
            // create block, and test
            $block_key  = substr( $this->template, $index, 1+ strpos($this->template, '}', $index) - $index );

            // test if block resolves
            $failed  = !$this->test( trim($block_key,'{}') );

            // unfail if already failed
            if( $failed && !$this->save )
                $failed = false;
            else if( $failed )
                $this->save = false;

            // feed past whitespace after {block}
            $index = $this->feed_whitespace( $index + strlen($block_key) );

            // process contents
            $index = $this->text( $index );

            // feed past whitespace before {/block}
            $index = $this->feed_whitespace( $index );

            if( substr( $this->template, $index, 8+strlen($block_key) ) == self::start_block . $block_key )
                $index += 8+strlen($block_key);
            else
                throw new Exception("Recursion error, block not closed");

            // turn saving back on
            if( $failed )
                $this->save = true;

            return $index;
        }

        private function feed_whitespace( $index )
        {
            while( trim(substr($this->template, $index, 1)) == "" )
                $index++;

            return $index;
        }
    }

?>