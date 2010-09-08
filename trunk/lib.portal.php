<?php
      /*
        nuclear.framework
        altman,ryan,2010

        Portal Library
        ================================
            Federated endpoint

    */

    require_once('interface.nuclear.php');

    class PortalEntry implements iSingleton
    {
        private static $_instance;
        private $db;
        private $events;
        private $id;
        private $guid;
        private $published;
        private $updated;


        function __construct()
        {
            $this->db       = Database::getInstance();
            $this->events   = Events::getInstance();
        }


        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new PortalEntry();

            return self::$_instance;
        }


        //
        // Take int timestamps for creating
        //
        public function create( $publisher, $published, $updated=false, $guid=false )
        {
            if( !$updated )
                $updated = $published;

            if( !$guid )
                $guid   = $this->guid();

            $sql =      "insert into ". nu_db() ."nu_entry_index ".
                        "(publisher, guid, published, updated) ".
                        "values ({$publisher}, UNHEX(REVERSE('{$guid}')), {$published}, {$updated}) ";

            // store creation data inside the singleton
            $this->id       = $this->db->id( $sql, "Error creating portal entry (index)" );
            $this->guid     = to_base( from_hex($guid) );

            // do not use gmdate because the timestamps are already utc
            $this->published= date('Y-m-d\TH:i:s\Z', $published);
            $this->updated  = date('Y-m-d\TH:i:s\Z', $updated);

            return $this->id;
        }


        //
        // TODO why do we need GUID in update?
        //
        public function updateIndex( $entry_id, $publisher, $published=false, $updated=false, $guid=false )
        {
            $fields = array('id','publisher');
            $values = array($entry_id, $publisher);
            $dups   = array();

            if( $published )
            {
                $fields[]   = 'published';
                $values[]   = $published;
                $dups[]     = "published=values(published)";
            }

            if( $updated )
            {
                $fields[]   = 'updated';
                $values[]   = $updated;
                $dups[]     = "updated=values(updated)";
            }

            if( preg_match('/^[0-9a-f]+$/', $guid) )
            {
                $fields[]   = 'guid';
                $values[]   = $guid;
                $dups[]     = "guid=values(guid)";
            }

            if( count($dups)==0 )
                return 0;

            $sql =      "insert into ". nu_db() ."nu_entry_index (".
                        implode(', ', $fields).
                        ") values (". implode(', ', $values) .") ".
                        "on duplicate key update ". implode(', ', $dups);

            return $this->db->affected( $sql, "Error updating portal entry (index)" );
        }


        public function delete( $entry_id )
        {
            $this->db->void(
                "delete from nu_entry_data where entry={$entry_id} limit 1",
                "Error removing entry_data");

            $this->db->void(
                "delete from nu_entry_index where id={$entry_id} limit 1",
                "Error removing entry_index");
        }


        //
        // get hex-based UUID
        //
        public function guid()
        {
            $guid = $this->db->single( "select REPLACE(UUID(),'-','')" );
            return $guid[0];
        }


        //
        // set Object saves entry to entry_id
        //
        public function setObject( $entry_id, $entry )
        {
            $restore = array();

            // remove indexed attributes from entry
            foreach( array('id','guid','published','updated') as $f )
            {
                $restore[$f] = $entry->$f;
                unset($entry->$f);
            }

            // remove activity streams data
            if( $entry->ns_activity_verb == 'http://activitystrea.ms/schema/1.0/post' || $entry->ns_activity_verb == 'http://activitystrea.ms/schema/1.0/update' )
            {
                $verb = $entry->ns_activity_verb;
                unset($entry->ns_activity_verb);
            }

            // downmix entry to data
            $data   = json_encode( $entry );

            if( $data && $entry_id )
            {
                $sql =      "insert into ". nu_db() ."nu_entry_data ".
                            "(entry, data) values ({$entry_id}, '". safe_slash( $data ) ."') ".
                            "on duplicate key update data=values(data)";

                $this->db->void( $sql,
                    "Error downmixing entry data");
            }

            // restore indexed attributes for echo
            foreach( array('id','guid','published','updated') as $f )
            {
                $entry->$f  = $restore[$f];
            }

            if( !isset($entry->ns_activity_verb) )
                $entry->ns_activity_verb = $verb;

            return $entry;
        }


        //
        // downmix created entry by entry_id
        //
        public function downmix( $entry_id, $entry )
        {
            // downmix current dynamic attributes
            $entry->id          = $entry_id;
            $entry->guid        = $this->guid;
            $entry->published   = $this->published;
            $entry->updated     = $this->updated;

            return $this->setObject( $entry_id, $entry );
        }
    }

    /**
     *
     * PortalPublishing singleton
     * used by portal API to interface PortalEntry singleton
     *
    **/
    class PortalPublishing implements iSingleton
    {
        private static $_instance;
        private $db;
        private $events;
        private $publisher;
        private $entry;
        private $entry_id;
        private $mode;


        function __construct()
        {
            $this->db       = Database::getInstance();
            $this->events   = Events::getInstance();
        }


        public static function getInstance()
        {
            if( is_null(self::$_instance) )
                self::$_instance = new PortalPublishing();

            return self::$_instance;
        }


        //
        // Maps to AS verb = post
        //
        public function post( $publisher, $entry )
        {
            $this->mode         = "post";
            $this->publisher    = $publisher;
            $this->entry        = $entry;

            // adjust for providing only published/updated
            if( $entry->published && !isset($entry->updated) )
            {
                $entry->updated = $entry->published;
            }

            $entry_id = PortalEntry::getInstance()->create(
                            $this->publisher->id,
                            $this->timestamp( $entry->published ),
                            $this->timestamp( $entry->updated ),
                            ($publisher->isLocal() ? false : $this->guid( $entry->guid )) );

            $entry    = PortalEntry::getInstance()->downmix( $entry_id, $entry );

            // emit published, use for local subs
            $this->events->emit('portal_posted', $entry, $publisher);

            return $entry;
        }


        //
        // Maps to AS verb = update
        //
        public function update( $publisher, $entry )
        {
            $this->mode         = "update";
            $this->publisher    = $publisher;
            $this->entry        = $entry;
            $this->entry_id     = $this->identify();

            // update the meta
            PortalEntry::getInstance()->updateIndex(
                $this->entry_id,
                $publisher->id,
                $this->timestamp($entry->published),
                $this->timestamp($entry->updated) );

            // set the object
            PortalEntry::getInstance()->setObject( $this->entry_id, $entry );

            // emit published, use for local subs
            $this->events->emit('portal_updated', $entry, $publisher);

            return $entry;
        }


        //
        // Maps to AS verb = delete
        //
        public function delete( $publisher, $entry )
        {
            $this->mode         = "delete";
            $this->publisher    = $publisher;
            $this->entry        = $entry;
            $this->entry_id     = $this->identify();

            PortalEntry::getInstance()->delete( $this->entry_id );

            // emit published, use for local subs
            $this->events->emit('portal_deleted', $entry, $publisher);
        }


        public function identify()
        {
            $id     = isset($this->entry->id) ? intval($this->entry->id) : null;
            $guid   = $this->entry->guid;

            if( is_null($id) && $guid )
            {
                $guid = $this->guid( $guid );
                $data = $this->db->single(
                            "select id from ". nu_db() . "nu_entry_index ".
                            "where publisher={$this->publisher->id} && guid=UNHEX(REVERSE('{$guid}') ".
                            "limit 1");

                if( !is_array($data) )
                    throw new Exception("Could not identify entry {$this->entry->guid}");

                return $data[0];
            }

            if( $id>0 )
            {
                $data = $this->db->single(
                            "select id from ". nu_db() ."nu_entry_index ".
                            "where id={$id} && publisher={$this->publisher->id} ".
                            "limit 1");

                if( !is_array($data) )
                    throw new Exception("Could not identify entry {$id}", 2);

                return $id;
            }

            // emit unidentified entry to observers
            if( $this->events->isObserved('portal_publish_unidentified') )
                $this->events->emit('portal_publish_unidentified', $this->entry, $this->publisher);

            return null;
        }

        public function timestamp( $str=null, $auto=NU_ACCESS_TIME )
        {
            if( !is_null($str) )
            {
                if( substr($str,-1)==='Z' )
                    $str = substr($str,0,-1) .'+0000';

                $ts = strtotime( $str );

                // adjust for server DST
                if( date("I", $ts)==1 )
                    $ts -= 3600;
            }

            return gmstrftime("%s", $ts ? $ts : $auto);
        }

        public function guid( $guid )
        {
            if( preg_match('/^[a-zA-Z0-9]+$/', $guid) )
            {
                return to_hex( from_base( $guid ) );
            }

            if( preg_match('/^[0-9a-f\-]+$/i', $guid) )
            {
                return str_replace('-', '', $guid);
            }

            if( is_numeric( $guid ) )
            {
                return to_hex( $guid );
            }

            return false;
        }
    }

?>
