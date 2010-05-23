<?php

    /*
        nuclear.framework
        altman,ryan,2010

        Entry Queries
        ================================
            Queries for fetching user and
            home timelines.

    */
    
    class EntryUserTimelineQuery extends NuSelect
    {
        function __construct( $publisher, $page=1, $limit=20 )
        {
            parent::__construct( nu_db() . 'nu_entry_index I' );
            
            $this->join( nu_db() . 'nu_entry_data D', 'D.entry=I.id');
            $this->join( nu_db() . 'NuclearUser U', 'U.id=I.publisher');
            
            $this->field( array(
                'I.id as entry_id',
                'I.published as entry_published',
                'I.updated as entry_updated',
                'D.data as entry_data',
                'U.id as author_id',
                'U.name as author_name',
                'U.domain as author_domain'
            ));
            
            $this->where( "I.publisher={$publisher}" );
            
            $this->filter(
                'portal_user_timeline_select',
                array("fields"=>"premerge", "joins"=>"postmerge", "conditions"=>"postmerge")
            );
            
            // paging, default: 20, min: 10, max: 200
            $this->page( $page, $limit, 20, 10, 200 );
            $this->order( 'I.updated desc' );
        }
    }

    class EntryHomeTimelineQuery extends NuSelect
    {
        function __construct( $subscriber, $page=1, $limit=20 )
        {
            parent::__construct( nu_db() . 'nu_entry_inbox B' );
            
            $this->join( nu_db() . 'nu_entry_index I', 'I.id=B.entry');
            $this->join( nu_db() . 'nu_entry_data D', 'D.entry=B.entry');
            $this->join( nu_db() . 'NuclearUser U', 'U.id=I.publisher');
            
            $this->field( array(
                'I.id as entry_id',
                'I.published as entry_published',
                'I.updated as entry_updated',
                'D.data as entry_data',
                'U.id as author_id',
                'U.name as author_name',
                'U.domain as author_domain'
            ));
            
            $this->where( "B.subscriber={$subscriber}" );
            
            $this->filter(
                'portal_home_timeline_select',
                array("fields"=>"premerge", "joins"=>"postmerge", "conditions"=>"postmerge")
            );
            
            // paging, default: 20, min: 10, max: 200
            $this->page( $page, $limit, 20, 10, 200 );
            $this->order( 'B.updated desc' );
        }
    }
    
?>
