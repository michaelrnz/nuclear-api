<?php
    
    /*
        TagURI destroy 
        destroy id for taguri
    */

    require_once('abstract.apimethod.php');

    class TagURIDestroy extends NuclearAPIMethod
    {
        protected function process()
        {
            $auth   = AuthorizedUser::getInstance();
            $id     = $this->call->id;

            // send auth to handlers
            NuEvent::action('taguri_destroy_auth', $auth);

            // check for id or taguri
            if( !is_numeric($id) && !is_null($this->call->taguri) )
            {
                $id = NuclearAPI::getInstance()->getTaguriID( $this->call, "json" );
            }

            // missing id
            if( !$id )
                throw new Exception("Missing valid taguri id", 5);

            // destroy
            $affected = WrapMySQL::affected(
                            "delete from nu_taguri where id={$id} limit 1;",
                            "Unable to destroy taguri");

            // raise event
            if( $affected )
                NuEvent::action('taguri_destroyed', $id);

            return $affected>0 ? $id : false;
        }
    }

?>
