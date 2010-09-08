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

                $prov = $doc->getElementsByTagNameNS('http://salmon-protocol.org/ns/magic-env','provenance');
                $magic_pack = $prov->item(0);

                $data = base64url_decode(preg_replace('/\s/','',$magic_pack->getElementsByTagName('data')->item(0)->nodeValue));

                $magic_doc = new DOMDocumentExceptor('1.0', 'UTF-8');
                $magic_doc->loadXML( $data );

                $data_obj = xml_to_object( $magic_doc );
            }

            // we check for excessive data size TODO create GLOBAL
            if( $data_length > 65000 )
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

            // get the entry from local or remote
            $entry  = $this->entry();

            if( is_null($entry->ns_activity_verb) )
                throw new Exception("Missing activity:verb", 5);

            // get verb
            $verb   = $this->activityVerb( $entry->ns_activity_verb );

            switch( $verb )
            {
                case 'post':
                    include( 'lib.portal.php' );
                    PortalPublishing::getInstance()->post( $author, $entry );
                    break;

                case 'update':
                    include( 'lib.portal.php' );
                    PortalPublishing::getInstance()->update( $author, $entry );
                    break;

                case 'delete':
                    include( 'lib.portal.php' );
                    PortalPublishing::getInstance()->delete( $author, $entry );
                    break;
            }

            // pass object to handlers
            Events::getInstance()->emit('portal_activity_' . $verb, $entry);

            // echo entry
            return $entry;
        }
    }

    return "postPortal";


?>