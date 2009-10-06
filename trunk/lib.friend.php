<?php
	
	/*
		nuclear.framework
		altman,ryan,2009

		Friend
		===========================
		simple social user handling
	*/

	require_once( 'class.eventlibrary.php' );

	class NuclearFriend extends EventLibrary
	{
		protected static $driver;

		private static function checkRelation( $user0, $user1 )
		{
			return WrapMySQL::single( "SELECT 'user1',model FROM nuclear_friendship WHERE user0=$user0 && user1=$user1 LIMIT 1;",
				"Unable to check relation");
		}

		private static function checkRequested( $user_to, $user_from )
		{
		  return WrapMySQL::single( "SELECT 'from_user' FROM nuclear_request WHERE user_from=$user_from && user_to=$user_to LIMIT 1;",
		    "Unable to check requested");
		}

		private static function removeRequest( $user_to, $user_from )
		{
			return WrapMySQL::affected( "DELETE FROM nuclear_request WHERE user_to=$user_to && user_from=$user_from;",
				"Error deleting from requests" );
		}
		
		private static function addPending( $id, $inc=1 )
		{
			WrapMySQL::void( "INSERT INTO nuclear_pending_request (id) VALUES ($id) ".
					 "ON DUPLICATE KEY UPDATE SET pending+=$inc;" );
		}

		private static function removePending( $id, $dec=1 )
		{
			WrapMySQL::void( "INSERT INTO nuclear_pending_request (id) VALUES ($id) ".
					 "ON DUPLICATE KEY UPDATE SET pending-=$dec;" );
		}

		public static function updateRequestStatus( $to, $from, $status )
		{
			if( strpos("-|new|read|denied|ignored|", "|{$status}|")===false )
				return false;

			return WrapMySQL::affected( "UPDATE nuclear_request SET status='$status' WHERE user_to=$to && user_from=$from LIMIT 1;",
							"Unable to update request status");
		}

		public static function clearRequests( $to )
		{
			if( is_numeric($to) && $to>0 )
			{
				return WrapMySQL::affected( "DELETE FROM nuclear_request WHERE user_to=$to;", "Unable to clear requests" );
			}
			return 0;
		}
		
		public static function request( $to, $from, $reason )
		{
			// simple check for valid users
			if( $to>0 && $from>0 )
			{
				// check for prior relation between ids
				if( self::checkRelation( $to, $from )!=null )
					throw new Exception("Relationship exists");

				// check for bi request
				if( self::checkRequested( $from, $to )!=null )
				{
				  self::accept( $from, $to );
				  return 1;
				}

				$reason = strlen($reason)>0? "'". safe_slash($reason) ."'" : "NULL";
				$q = "INSERT INTO nuclear_request (user_to, user_from, reason)
					VALUES ($to, $from, $reason);";

				$c = WrapMySQL::affected($q, "Unable to request friend");

				if( $c>0 )
				{
					self::addPending( $to );

					// need event
					$o = new Object();
					$o->to = $to;
					$o->from=$from;
					$o->reason=$reason;
					self::fire( 'Request', $o );
				}

				return $c;
			}
			return false;
		}

		public static function accept( $to, $from )
		{
			if( $to>0 && $from>0 )
			{
				if( self::removeRequest( $to, $from )==1 )
				{
					$ct = WrapMySQL::affected("INSERT INTO nuclear_friendship (user0,user1,model) VALUES ($to,$from,0),($from,$to,0) ".
								  "ON DUPLICATE KEY UPDATE model=0;",
								  "Unable to insert friendship");

					if( $ct>0 )
					{
						// need event
						$o = new Object();
						$o->to = $to;
						$o->from=$from;
						self::fire( 'Accept', $o );

						return true;
					}
				}
			}
			return false;
		}

		public static function follow( $to, $from, $e=null )
		{
			// simple check for valid users
			if( $to>0 && $from>0 )
			{
			  if( is_null($e) )
			    $o = new Object();
			  else
			    $o = $e;

			  $o->to = $to;
			  $o->from=$from;

			  // BeforeFollow Event
			  self::fire( 'BeforeFollow', $o );

			  // check for prior relation between ids
			  $rel = self::checkRelation( $to, $from );

			  if( $rel != null && $rel[1] == 1 )
			  {
			    // user being followed
			    $ct = WrapMySQL::affected("INSERT INTO nuclear_friendship (user0,user1,model) VALUES ($to,$from,0),($from,$to,0) ".
						      "ON DUPLICATE KEY UPDATE model=0;",
						      "Unable to insert friendship");
			    return array($ct,"Followee existed, model changed to friend");
			  }

			  // do Follow
			  $q = "INSERT INTO nuclear_friendship (user0, user1, model)
				VALUES ($from, $to, 1);";

			  $c = WrapMySQL::affected($q, "Unable to follow");

			  if( $c>0 )
			  {
			    self::fire( 'Follow', $o );
			  }

			  return $c;
			}
			return false;
		}

		// friend removal is one way
		public static function remove( $user0, $user1 )
		{
			if( $user0>0 && $user1>0 )
			{
				return WrapMySQL::affected("DELETE FROM nuclear_friendship WHERE user0=$user0 && user1=$user1;",
								"Error removing friendship");
			}
			return false;
		}

		public static function deleteFriend( $uid, $friendID )
		{
			$dQ = "DELETE FROM friend WHERE (uid1=$uid && uid2=$friendID) || (uid1=$friendID && uid2=$uid) LIMIT 2;";

			if( !($dR = mysql_query( $dQ )) ) throw new Exception("Unable to delete friendship");

			return true;
		}

		public static function requests( $user_id, $status='new', $page=0 )
		{
			$offset = $page * 10;
			$stats_q = $status ? " && status='$status'" : false;

			$q = "select U.id, user_from, name AS user, status, reason, ts from nuclear_request as R ".
			     "left join nuclear_username as U on U.id=R.user_from "
			     "where user_to={$user_id}{$stats_q} order by R.ts desc limit 10 offset $offset;";

			return WrapMySQL::q( $q, "Error fetching friend requests" );
		}

		public static function get( $id, $page=0, $order=false, $order_field=false )
		{
			$limit = 50;

			if( $id>0 )
			{
				if( !is_numeric($page) )
				{
					$page = 0;
				}
				else if( $page>0 )
				{
					$page = floor($page-1);
				}

				$offset = $page * $limit;

				// do fields
				$order = strpos('-|asc|desc|', '|'.strtolower($order).'|' ) ? strtolower($order) : 'DESC';
				$order_field = strtolower($order_field);
				$order_field = strpos("-|ts|name|", "|".$order_field."|")>0 ? $order_field : "ts";

		  $q = "SELECT Friends.user1 AS id, Users.name, Friends.ts FROM nuclear_friendship AS Friends ".
		       "left join nuclear_username AS Users On Users.id=Friends.user1 ".
		       "where Friends.user0={$id} && Friends.model=0 order by ts {$order} limit {$limit} offset {$offset};";

				return WrapMySQL::q( $q, "Error fetching friends" );
			}
			return false;
		}

		public static function following( $user )
		{
		  $limit = 50;
		  if( $user>0 )
		  {
		    if( !is_numeric($page) )
		    {
		      $page = 0;
		    }
		    else if( $page>0 )
		    {
		      $page = floor($page-1);
		    }

		    $offset = $page * $limit;

		  $q = "SELECT Friends.user1 AS id, Users.name, Friends.ts FROM nuclear_friendship AS Friends ".
		       "left join nuclear_username AS Users On Users.id=Friends.user1 ".
		       "where Friends.user0={$user} && Friends.model=1 limit {$limit} offset {$offset};";

		  return WrapMySQL::q( $q, "Error fetching followers" );
		  }

		  return false;
		}

		public static function followers( $user )
		{
		  $limit = 50;
		  if( $user>0 )
		  {
		    if( !is_numeric($page) )
		    {
		      $page = 0;
		    }
		    else if( $page>0 )
		    {
		      $page = floor($page-1);
		    }

		    $offset = $page * $limit;

		  $q = "SELECT Friends.user0 AS id, Users.name, Friends.ts FROM nuclear_friendship AS Friends ".
		       "left join nuclear_username AS Users On Users.id=Friends.user0 ".
		       "where Friends.user1={$user} && Friends.model=1 limit {$limit} offset {$offset};";

		  return WrapMySQL::q( $q, "Error fetching followers" );
		  }

		  return false;
		}
	}

	//NuclearFriend::init();

?>
