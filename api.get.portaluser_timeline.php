<?php

      /*
        nuclear.framework
        altman,ryan,2010

        Portal - user_timeline
        ================================
            Federated endpoint

    */

    require_once( 'api.class.usermethod.php' );
    require_once( 'lib.entryquery.php' );
    
    class postPortalUserTimeline extends NuUserMethod
    {
        protected function build()
        {
            $author = $this->getUser();
            $query  = new EntryUserTimelineQuery( 
                        $author->id, 
                        $this->call->page, 
                        $this->call->limit );
            
            $timeline   = array();
            $events     = Events::getInstance();
            $do_filter  = $events->isObserved('portal_timeline_entry');
            
            if( $query->select() )
            {
                require_once('class.entry.php');
                
                while( $data = $query->object() )
                {
                    $entry_obj      = unserialize( $data->entry_data );
                    
                    $entry          = new Entry( $entry_obj );
                    $entry->id      = $data->entry_id;
                    $entry->guid    = to_base( from_hex( $data->entry_guid ) );
                    $entry->published   = date('Y-m-d\TH:i:s\Z', $data->entry_published);
                    $entry->updated     = date('Y-m-d\TH:i:s\Z', $data->entry_updated);
                    
                    $entry->author  = new Author( array(
                                        'id'=>$data->author_id, 
                                        'name'=>$data->author_name, 
                                        'domain'=>$data->author_domain) );
                    
                    if( $do_filter )
                        $entry      = $events->filter( 'portal_timeline_entry', $entry, $data );
                    
                    array_push( $timeline, $entry );
                }
            }
            
            return $timeline;
        }
    }

    return "postPortalUserTimeline";

?>
