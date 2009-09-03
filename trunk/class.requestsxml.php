<?php
	
	require_once( 'abstract.xmldatacontainer.php' );

	class RequestsXML extends NuclearXMLDataContainer
	{
		protected $rootTag = "requests";
		private static $nodeAtts = array("id","user","status","ts");

		// overrides
		protected function rootAttributes()
		{
			return array("user"=>$GLOBALS['USER_CONTROL']['name']);
		}

		public function compose( &$row )
		{
			$rn = $this->object->createElement("req");

			foreach( self::$nodeAtts as $a )
			{
				$rn->setAttribute($a, $row[$a]);
			}

			$rn->setAttribute('image', ImageHash::request('user','user',$row['id'],1) );
			
			if( strlen($row['reason'])>0 )
			{
				$rn->appendChild($this->object->createElement("reason", $row['reason']));
			}

			return $rn;
		}

		public function query()
		{
			$page = ($page = $this->request->page) ? ($page-1) : 0;
			$offset = $page * 10;
			$stats_q = ($status = $this->request->status) ? " && status='$status'" : false;
			$user_id = $this->request->user_id;

			$q = "SELECT user_from AS id,name AS user,status,reason,ts FROM nuclear_view_requests ".
				"WHERE user_to={$user_id}{$stats_q} ORDER BY ts DESC LIMIT 10 OFFSET $offset;";

			return WrapMySQL::q( $q, "Error fetching friend requests" );
		}

		function __toString()
		{
			// relying on XMLContainer
			return $this->object->__toString();
		}
	}

?>
