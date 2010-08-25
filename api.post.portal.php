<?php

      /*
        nuclear.framework
        altman,ryan,2010

        Portal
        ================================
            Federated endpoint

    */

    require_once( 'abstract.apimethod.php' );

    class postPortal extends NuclearAPIMethod
    {
        private $local;


        //
        // Check for authorized publisher
        //
        protected function author()
        {
            if( $author = AuthorizedUser::getInstance() )
            {
                $this->local = $author->isLocal();
            }
            else
            {
                throw new Exception("Unauthorized author", 2);
            }

            return $author;
        }


        //
        // Read and render the data in an available format
        //
        protected function entry()
        {
            $data = $this->call->data;

            if( is_object( $data ) )
            {
                return $data;
            }

            $data_length = strlen($data);

            if( $this->call->content_type == "json" )
            {
                $data_obj = json_decode( $data );
            }

            // load non-local data as xml
            if( !$this->local )
            {
                $doc = new DOMDocumentExceptor('1.0', 'UTF-8');
                $doc->loadXML( $data );
                $data_obj = xml_to_object( $doc );
            }

            // we check for excessive data size TODO create GLOBAL
            if( $data_length > 16000 )
            {
                $data_obj = Events::getInstance()->filter('portal_overflow', $data_obj);
            }

            return $data_obj;
        }

        protected function activityVerb( $verb )
        {
            return substr( $verb, strrpos($verb, '/')+1 );
        }

        protected function build()
        {
            $author = $this->author();

            if( !is_object($author) || !is_numeric($author->id) )
                throw new Exception("Invalid publisher", 5);

            $entry  = $this->entry();

            if( is_null($entry->ns_activity_verb) )
                throw new Exception("Missing activity:verb", 5);

            $verb   = $this->activityVerb( $entry->ns_activity_verb );

            include( 'lib.portal.php' );
            PortalPublishing::getInstance()->publish( $publisher, $data, $this->local );

            return $data;

        }
    }

    return "postPortal";


?>