<?php
	
	/*
		nuclear.framework
		altman,ryan,2009

		Relation
		===========================
		simple social user handling
		local and federated

		Default 'models'
		===========================
		0 - subscriber
		1 - publisher
		2 - mutual
		9 - block
		11- proxy
	*/

	require_once( 'lib.nuevent.php' );

	class NuRelation
	{

		public static function check( $user, $party )
		{
		  $r = WrapMySQL::single(
		         "select model from nu_relation ".
			 "where user={$user} && party={$party} limit 1;",
			 "Unable to check relation");

		  return $r ? self::model($r[0]) : null;
		}

		public static function model( $m )
		{
		  if( is_numeric($m) )
		  {
		  switch($m)
		  {
		    case 0:                  return 'subscriber';
		    case 1:                  return 'publisher';
		    case 2:                  return 'mutual';
		    case 9:                  return 'block';
		    case 10:                 return 'proxy_subscriber';
		    case 11:                 return 'proxy_publisher';
		    default: return 0;
		  }
		  }
		  else
		  {
		  switch($m)
		  {
		    case 'subscriber':       return 0;
		    case 'publisher':        return 1;
		    case 'mutual':           return 2;
		    case 'block':	     return 9;
		    case 'proxy_subscriber': return 10;
		    case 'proxy_publisher':  return 11;
		    default: return 0;
		  }
		  }
		}

		private static function __update( $user, $party, $model, $binary=true )
		{
			//
			// determine relationship model
		        $user_model  = $model;

			//
			// basic user-party relation
			$values = array("({$user}, {$party}, {$user_model})");

			//
			// check binary relation, for local
			if( $binary )
			{
			  switch( $user_model )
			  {
			    case 0:
			      $party_model = 1;
			      break;
			    case 1:
			      $party_model = 0;
			      break;
			    case 2:
			      $party_model = 2;
			      break;
			    case 10:
			      $party_model = 11;
			      break;
			    case 11:
			      $party_model = 10;
			      break;
			    default:
			      $party_mode = false;
			  }

			  if( $party_model )
			    $values[] = "({$party}, {$user}, {$party_model})";
			}

			switch( $user_model )
			{
			  case 0:
			    $update_model = "values(model)";
			    break;
			  case 1:
			    $update_model = "values(model)";
			    break;
			  default:
			    $update_model = "values(model)";
			    break;
			}

			//
			// query affected
			$c = WrapMySQL::affected( 
			      "insert into nu_relation (user, party, model) ".
			      "values ". implode(',', $values) .
			      "on duplicate key update model={$update_model}, ts=ts;",
			      "Unable to update relation");

			return $c;
		}

		public static function update( $user, $party, $model='subscriber', $remote=false, $e=null )
		{
			if( is_null( $e ) )
			  $o = new Object();
			else
			  $o = &$e;

			$o->user   = $user;
			$o->party  = $party;

			// raise pre event
			NuEvent::raise( 'nu_pre_relation_update', $o );

			// halt, unset user/party
			if( $o->user<1 || $o->party<1 ) return false;

			// model
			if( !is_null($o->model) )
			{
			  $model = $o->model;
			}
			else
			{
			  $model = self::model($model);
			  $o->model = $model;
			}

                        file_put_contents($GLOBALS['CACHE'] . "/relations.log", "$user => $model => $party\n", FILE_APPEND);

			$o->success = self::__update( $user, $party, $model );

			// raise post event
			NuEvent::raise( 'nu_post_relation_update', $o );

			return $o->success;
		}

		//
		// relation removal defaults to unsubscribing
		// - removing user-party relation model=0
		// - blocking can be achieved by removing model=1
		public static function destroy( $user, $party, $e=null )
		{
			if( is_null( $e ) )
			  $o = new Object();
			else
			  $o = &$e;

			$o->user   = $user;
			$o->party  = $party;

			// raise pre event
			NuEvent::raise( 'nu_pre_relation_destroy', $o );

			// halt, unset user/party
			if( !is_numeric($o->user) || !is_numeric($o->party) ) return false;

			// test the mirrored relation
			$m = WrapMySQL::affected(
			      "update nu_relation set model=0 ".
			      "where user={$party} && party={$user} && model=2 ".
			      "limit 1;",
			      "Error testing two-way relation");

			if( $m==1 )
			{
			  $c = WrapMySQL::affected(
			        "update nu_relation set model=1 ".
				"where user={$user} && party={$party} && model=2 ".
				"limit 1;",
				"Error destroying two-way relation");
			}
			else
			{
			  $c = WrapMySQL::affected(
			        "delete from nu_relation ".
				"where (user={$user} && party={$party} && model=0) ".
				"|| (user={$party} && party={$user} && model=1) ".
				"limit 2;",
				"Error destroying one-way relation");
			}

			$o->success = $c>0;

			// raise post event
			NuEvent::raise( 'nu_post_relation_destroy', $o );

			return $c;
		}

	}

?>
