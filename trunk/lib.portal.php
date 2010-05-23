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

        public function create( $publisher, $published, $updated=false, $guid=false )
        {
            if( !$updated )
                $updated = $published;

            if( !$guid )
                $guid   = $this->guid();

            $sql =      "insert into ". nu_db() ."nu_entry_index ".
                        "(publisher, guid, published, updated) ".
                        "values ({$publisher}, UNHEX(REVERSE('{$guid}')), {$published}, {updated}) ";

            $this->id       = $this->db->id( $sql, "Error creating portal entry (index)" );
            $this->guid     = $guid;
            $this->published= $published;
            $this->updated  = $updated;

            return $this->id;
        }

        public function update( $entry_id, $publisher, $published=false, $updated=false, $guid=false )
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

            $sql =      "insert into ". nu_db() ."nu_entry_index ".
                        implode(', ', $fields).
                        "values (". implode(', ', $values) .") ".
                        "on duplicate key update ". implode(', ', $dups);

            return $this->db->affected( $sql, "Error updating portal entry (index)" );
        }

        public function guid()
        {
            $guid = $this->db->single( "select REPLACE(UUID(),'-','')" );
            return $guid[0];
        }

        public function downmix( $entry, $entry_id=null )
        {
            $entry_id = !is_null($entry_id) ? $entry_id : $this->id;

            // downmix current dynamic attributes
            $entry->id      = $entry_id;
            $entry->guid    = $this->guid;
            $entry->published= $this->published;
            $entry->updated = $this->updated;

            // emit unidentified entry to observers
            if( $this->events->isObserved('portal_publish_identified') )
                $entry = $this->events->filter('portal_publish_identified', $entry, $entry_id);

            // remove dynamic attributes from entry
            unset($entry->id);
            unset($entry->guid);
            unset($entry->published);
            unset($entry->updated);

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

            // restore dynamic attributes for echo
            $entry->id      = $this->id;
            $entry->guid    = $this->guid;
            $entry->published= $this->published;
            $entry->updated = $this->updated;

            return $entry;
        }
    }

    class PortalPublishing implements iSingleton
    {
        private static $_instance;
        private $db;
        private $events;
        private $publisher;
        private $entry;
        private $isLocal;
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
                self::$_instance = new PortalPublisher();

            return sefl::$_instance;
        }

        public function publish( $publisher, $entry, $isLocal )
        {
            $this->mode         = "publish";
            $this->isLocal      = $isLocal;
            $this->publisher    = $publisher;
            $this->entry        = $entry;
            $this->entry_id     = $this->identify();

            $entry              = PortalEntry::getInstance()->downmix( $this->entry );

            // emit published, use for local subs
            $this->events->emit('portal_published', $entry, $publisher);

            // emit published local, use for dispatch
            if( $this->isLocal )
                $this->events->emit('portal_published_local', $entry, $publisher);

            return $entry;
        }

        public function update( $publisher, $entry, $isLocal )
        {
        }

        public function unpublish( $publisher, $entry_guid, $isLocal )
        {
        }

        public function identify()
        {
            $id = is_null($this->entry->id) ? intval($id) : null;

            if( $this->isLocal && $id>0 )
            {
                if( $this->mode == "publish" )
                    throw new Exception("Entry id detected on local publishing portal");

                $owner  = $this->db->single(
                            "select id from ". nu_db() ."nu_entry_index ".
                            "where id={$id} && publisher={$this->publisher->id} ".
                            "limit 1");

                if( !is_array($owner) )
                    throw new Exception("Unauthorized portal publisher for entry {$id}", 2);

                return $id;
            }

            // emit unidentified entry to observers
            if( $this->events->isObserved('portal_publish_unidentified') )
                $this->events->emit('portal_publish_unidentified', $this->entry, $this->publisher);

            $entry_id = PortalEntry::getInstance()->create(
                            $this->publisher->id,
                            $this->timestamp( $this->entry->published ),
                            $this->timestamp( $this->entry->updated ),
                            $this->guid( $this->entry->guid ) );

            return $entry_id;
        }

        public function timestamp( $str=null, $auto=NU_ACCESS_TIME )
        {
            if( !is_null($str) )
            {
                $ts = strtotime( $str );
            }

            return $ts ? $ts : $auto;
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