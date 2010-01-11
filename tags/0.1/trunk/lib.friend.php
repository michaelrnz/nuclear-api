<?php
	
	/*
		nuclear.framework
		altman,ryan,2009

		Friend
		===========================
		simple social user handling
	*/

	require_once( 'class.eventlibrary.php' );
	require_once( 'lib.nurelation.php' );

	class NuclearFriend extends EventLibrary
	{
		protected static $driver;

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
				if( NuRelation::check( $from, $to )!=null )
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
					$o->user = $from;
					$o->party= $to;
					$o->reason=$reason;
					self::fire( 'Request', $o );

					// new events
					NuEvent::raise('friend_requested', $o);
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
				    $c = NuRelation::update( $to, $from, 0 );
				    $c += NuRelation::update( $from, $to, 0 );
				    
					if( $ct>0 )
					{
						// need event
						$o = new Object();
						$o->to = $to;
						$o->from=$from;
						$o->user = $from;
						$o->party= $to;
						self::fire( 'Accept', $o );

						// new events
						NuEvent::raise('friend_accepted', $o );

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

			  $o->to   = $to;
			  $o->from = $from;
			  $o->user = $from;
			  $o->party= $to;

			  // BeforeFollow Event
			  self::fire( 'BeforeFollow', $o );

			  // new events
			  NuEvent::raise('pre_follow', $o);

			  // is followee following new follower
			  $mutual = NuRelation::check( $to, $from );

			  if( $mutual )
			  {
			    if( $mutual[0] == 0 )
			      return false;

			    NuRelation::update( $to, $from, 0 );
			    $o->mutual = true;
			    $model = 0;
			  }
			  else
			  {
			    $model = 1;
			  }

			  // update
			  $c = NuRelation::update( $from, $to, $model );

			  // new events
			  NuEvent::raise('post_follow', $o);

			  return $c;
			}

			return false;
		}

		public static function requests( $user_id, $status='new', $page=0 )
		{
			$offset = $page * 10;
			$stats_q = $status ? " && status='$status'" : false;

			$q = "select U.id, user_from, name AS user, status, reason, ts from nuclear_request as R ".
			     "left join nuclear_username as U on U.id=R.user_from ".
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

				return NuRelation::userlist( $id, 'user', 0, array('limit'=>$limit, 'offset'=>$offset) );
			}
			return false;
		}

		public static function friends( $user )
		{
		  return NuRelation::userlist( $user, 'user', 0 );
		}

		public static function following( $user )
		{
		  return NuRelation::userlist( $user, 'user', 1 );
		}

		public static function followers( $user )
		{
		  return NuRelation::userlist( $user, 'party', 1 );
		}
	}

	NuclearFriend::init();

?>
