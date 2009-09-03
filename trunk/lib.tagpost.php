<?php
	/*
		nuclear.framework
		altman,ryan,2008

		TagPost
		==========================================
			library for general tag handling
			allows events for tag manipulation
	*/

	require_once('class.eventlibrary.php');
	require_once('wrap.mysql.php');

	class TagPost extends EventLibrary
	{
		protected static $driver = null;

		//
		// simple walk which slashes tags
		//
		public static function walk( $t )
		{
			return "('" . trim(str_replace("'", "\'", $t)) . "')";
		}

		//
		// identify tags posted
		// tags as array, o is object to pass on firing
		// tmp is optional specification of temporary table
		//
		public static function id( $tags, $o=null, $tmp='tag_mix_tmp' )
		{
			//
			// check for tag array
			if( !is_array( $tags ) ) $tags = array($tags);

			//
			// create temporary table for identification
			mysql_query("CREATE TEMPORARY TABLE $tmp (id INT UNSIGNED, tag VARCHAR(128) COLLATE utf8_general_ci);");

			//
			// do insert
			$c = WrapMySQL::affected("INSERT INTO $tmp (tag) VALUES " . implode(',', array_map( array("TagPost","walk"), $tags ) ) . ";");

			//
			// do update id
			mysql_query("INSERT IGNORE INTO tag (SELECT NULL AS id, tag FROM $tmp);");

			//
			// get ids into tmp
			mysql_query("UPDATE $tmp LEFT JOIN tag ON tag.tag={$tmp}.tag SET {$tmp}.id=tag.id;");

			//
			// prepare to fire on identified
			if( is_null($o) || !is_object($o) )
			{
				$o = new Object();
			}

			// set fields
			$o->tags = $tags;
			$o->table = $tmp;

			// fire on identified
			self::fire("Identified", $o);

			// remove table
			WrapMySQL::void("DROP TEMPORARY TABLE $tmp;");

			return $c;
		}
	}

	// begin handling
	TagPost::init();

?>
