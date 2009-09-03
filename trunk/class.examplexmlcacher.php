<?php
	
	require_once( 'abstract.xmldataobject.php' );

	class ExampleCacheXML extends NuclearXMLDataObject
	{
		protected $rootTag = "";
		private static $nodeAtts = array("id"=>"id");

		// overrides
		protected function rootAttributes()
		{
			return array("attribute"=>$this->request->someattribute);
		}

		public function compose( &$row )
		{
			$rn = $this->object->createElement("row", $row['val']);

			foreach( self::$nodeAtts as $a=>$f )
			{
				$rn->setAttribute($a, $row[$f]);
			}
			
			if( strlen($row['tags'])>0 )
			{
				$tags = explode(',',$row['tags']);
				foreach( $tags as $t )
				{
					$tn = $this->object->createElement("t", $t);
					$rn->appendChild($tn);
				}
			}

			return $rn;
		}

		public function append( &$node, $element )
		{
			$node->appendChild( $element );
		}

		public function query()
		{
			return WrapMySQL::q(
				"SELECT ei.ts, ed.*, username.name, entry_tags.tags, stream.name AS stream FROM streamed 
				LEFT JOIN entry_index AS ei ON ei.id=streamed.entry 
				LEFT JOIN entry_data AS ed ON ed.id=ei.id 
				LEFT JOIN username ON username.id=ei.user 
				LEFT JOIN entry_tags ON entry_tags.entry=streamed.entry
				LEFT JOIN stream ON stream.id=streamed.stream
				ORDER BY ei.ts DESC 
				LIMIT 10 OFFSET 0;",
				"Unable to query community entries");
		}

		public function cacheName()
		{
			// can use more robust caching schemes with directores, if the structure is present
			return $GLOBALS["ID"] . "_call.xml";
		}

		function __toString()
		{
			// relying on XMLContainer
			return $this->object->__toString();
		}
	}

?>
