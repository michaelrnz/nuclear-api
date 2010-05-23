<?php

      /*
        nuclear.framework
        altman,ryan,2010

        Portal
        ================================
            Federated endpoint

    */

    require_once( 'abstract.apimethod.php' );

    class postPortalPublish extends NuclearAPIMethod
    {
        //
        // Check for authorized publisher
        //
        protected function publisher()
        {
            if( $publisher = AuthorizedUser::getInstance() )
            {
                $this->local = $publisher->isLocal();
            }
            else
            {
                throw new Exception("Unauthorized publisher", 2);
            }

            return $publisher;
        }

        //
        // Read and render the data in an available format
        //
        protected function data()
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

            if( !$this->local )
            {
                $doc = new DOMDocumentExceptor('1.0', 'UTF-8');
                $doc->loadXML( $data );
                $data_obj = xml_to_object( $doc );
            }

            // we check for excessive data size TODO create GLOBAL
            if( $data_length > 16000 )
            {
                $data_obj = Events::getInstance()->filter('portal_publish_overflow', $data_obj);
            }

            return $data_obj;
        }

        protected function publish()
        {
            $publisher  = $this->publisher();

            if( !is_object($publisher) || !is_numeric($publisher->id) )
                throw new Exception("Invalid publisher", 5);

            $data       = $this->data();

            include( 'lib.portal.php' );
            PortalPublishing::getInstance()->publish( $publisher, $data, $this->local );

            return $data;
        }

        protected function build()
        {
            return $this->publish();
        }
    }

    return "postPortalPublish";

?>