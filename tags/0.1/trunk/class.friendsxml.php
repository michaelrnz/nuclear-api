<?php
	
	require_once( 'abstract.xmldatacontainer.php' );

	class FriendsXML extends NuclearXMLDataContainer
	{
		protected $rootTag = "userlist";
		private static $nodeAtts = array("id","name");

		// overrides
		protected function rootAttributes()
		{
			return array("status"=>"ok","user"=>$this->request->user,"model"=>$this->request->model);
		}

		public function compose( &$row )
		{
			$rn = $this->object->createElement("user");

			foreach( self::$nodeAtts as $a )
			{
				$rn->setAttribute($a, $row[$a]);
			}

			$rn->setAttribute('timestamp', gmdate('r', strtotime($row['ts'])));
			
			return $rn;
		}

		public function query()
		{
			require_once('lib.friend.php');
			$ro = $this->request;

			switch( $this->request->model )
			{
			  case 'followers':
			    return NuclearFriend::followers( $this->request->user_id );

			  case 'following':
			    return NuclearFriend::following( $this->request->user_id );

			  default:
			    return NuclearFriend::get($ro->user_id, $ro->page, $ro->order, $ro->order_field);
			}
		}

		function __toString()
		{
			// relying on XMLContainer
			return $this->object->__toString();
		}
	}

?>
