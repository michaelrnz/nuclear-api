<?php
    
    /*
        TagURI id
    */

    require_once('abstract.apimethod.php');
    require_once('class.taguri.php');
    require_once('lib.taguri.php');
    require_once('class.nuselect.php');

    class TagURIId extends NuclearAPIMethod
    {
        protected function process()
        {
            $taguri = $this->call->taguri;

            if( !$taguri )
                throw new Exception("Missing taguri", 4);

            $tag = TagURI::fromDecoded( $taguri );

            if( is_null($tag) )
                throw new Exception("Malformed taguri", 5);

            $tuple = $tag->tuple();

            $select = new NuSelect("nu_taguri T");
            $select->join( "nu_authority A", "A.id=T.tag_authority" );
            $select->where( "A.name='{$tuple['authority']}'" );
            $select->where( "T.tag_date='{$tuple['date']}'" );
            $select->where( "T.tag_specific='{$tuple['specific']}'" );
            $select->field( "T.id" );
            $select->page( 1, 1, 1, 1, 1 );

            $id = $select->single("Unable to lookup taguri");

            if( $id )
            {
                $tag->id = $id[0];
            }
            else
            {
                // raise event for application
                $tag = NuEvent::filter('taguri_not_found', $tag);
            }

            return $tag;
        }
    }

?>
