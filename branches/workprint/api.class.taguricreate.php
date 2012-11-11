<?php
    
    /*
        TagURI create
        create id for taguri
    */

    require_once('abstract.apimethod.php');
    require_once('class.taguri.php');
    require_once('lib.taguri.php');
    require_once('class.nuinsert.php');


    class TagURICreate extends NuclearAPIMethod
    {
        protected function process()
        {
            $auth   = AuthorizedUser::getInstance();
            $taguri = $this->call->taguri;

            // test for data
            if( !$taguri )
                throw new Exception("Missing taguri", 4);

            // send auth to handlers
            NuEvent::action('taguri_create_auth', $auth);

            // create object
            $tag = TagURI::fromDecoded( $taguri );

            // test for object
            if( is_null($tag) )
                throw new Exception("Malformed taguri", 5);

            // get tuple
            $tuple = $tag->tuple();

            // get authority id
            $authority = NuTagURI::authorityId( $tuple['authority'], true );

            // create tag
            $insert = new NuInsert('nu_taguri');
            $insert->field( array("tag_authority", "tag_date", "tag_specific") );
            $insert->value( array("{$authority}", "'{$tuple['date']}'", "'{$tuple['specific']}'") );

            $tag->id = $insert->id('Unable to create taguri');

            // raise event
            if( $tag->id > 0 )
               NuEvent::action('taguri_created', $tag);

            return $tag;
        }
    }

?>
