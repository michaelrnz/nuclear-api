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
		2 - block
	*/

	require_once( 'lib.nuevent.php' );

	class NuRelation
	{

		public static function check( $user, $party )
		{
			return WrapMySQL::single(
				"select 'party', model from nu_relation ".
				"where user=$user && party=$party limit 1;",
				"Unable to check relation");
		}

		public static function model( $m )
		{
		  switch($m)
		  {
		    case 'subscriber': return 0;
		    case 'publisher':  return 1;
		    case 'block':     return 2;
		    default: return 0;
		  }
		}

		private static function __update( $user, $party, $model, $binary=true )
		{
			//
			// determine relationship model
		        $user_model  = $model;
			$party_model = $model == 0 ? 1 : 0;

			//
			// basic user-party relation
			$values = array("({$user}, {$party}, {$user_model})");

			//
			// check binary relation, for local
			if( $binary )
			{
			  $values[] = "({$party}, {$user}, {$party_model})";
			}

			//
			// query affected
			$c = WrapMySQL::affected( 
			      "insert into nu_relation (user, party, model) ".
			      "values ". implode(',', $values) .
			      "on duplicate key update model=values(model);",
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
			  $model = $o->model;
			else
			  $model = self::model($model);

			$o->success = self::__update( $user, $party, $model, $remote ? false : true );

			// raise post event
			NuEvent::raise( 'nu_post_relation_update', $o );

			return $o->success;
		}

		//
		// relation removal defaults to unsubscribing
		// - removing user-party relation model=0
		// - blocking can be achieved by removing model=1
		public static function destroy( $user, $party, $model=0, $e=null )
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

			$c = WrapMySQL::affected(
			      "delete from nu_relation ".
			      "where user={$o->user} && party={$o->party} && model={$model} limit 1;",
			      "Unable to delete relation");
			
			$o->success = $c>0;

			// raise post event
			NuEvent::raise( 'nu_post_relation_destroy', $o );

			return $c;
		}

	}

?>
