<?php
	
	/*
		nuclear.framework
		altman,ryan,2009

		Relation
		===========================
		simple social user handling
		local and federated
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

		public static function update( $user, $party, $model=0, $e=null )
		{
			if( is_null( $e ) )
			  $o = new Object();
			else
			  $o = &$e;

			$o->user   = $user;
			$o->party  = $party;

			// raise pre event
			NuEvent::raise( 'pre_nu_relation_update', $o );

			// halt, unset user/party
			if( $o->user<1 || $o->party<1 ) return false;

			// model
			if( !is_null($o->model) )
			  $model = $o->model;

			// query affected
			$c = WrapMySQL::affected( 
			      "insert into nu_relation (user, party, model) ".
			      "values ({$o->user}, {$o->party}, {$model}) ".
			      "on duplicate key update model=values(model);",
			      "Unable to update relation");

			$o->success = $c>0;

			// raise post event
			NuEvent::raise( 'post_nu_relation_update', $o );

			return $c;
		}

		// friend removal is one way
		public static function destroy( $user, $party, $e=null )
		{
			if( is_null( $e ) )
			  $o = new Object();
			else
			  $o = &$e;

			$o->user   = $user;
			$o->party  = $party;

			// raise pre event
			NuEvent::raise( 'pre_nu_relation_destroy', $o );

			// halt, unset user/party
			if( !is_numeric($o->user) || !is_numeric($o->party) ) return false;

			$c = WrapMySQL::affected(
			      "delete from nu_relation ".
			      "where user={$o->user} && party={$o->party} limit 1;",
			      "Unable to delete relation");
			
			$o->success = $c>0;

			// raise post event
			NuEvent::raise( 'post_nu_relation_destroy', $o );

			return $c;
		}

		public static function userlist( $user, $select, $model=0, $paging=false )
		{
		  if( !isType("user|party", $select) )
		    return null;

		  $join   = $select == 'user' ? 'party' : 'user';

		  $limit  = isset($paging['limit']) ? $paging['limit'] : 50;
		  $offset = isset($paging['offset']) ? $paging['offset'] : 0;

		  $q = "select R.{$join} as id, N.name, D.name as domain, R.ts ".
		       "from nu_relation as R ".
		       "left join nu_user as U on U.id=R.{$join} ".
		       "left join nu_name as N on N.id=U.name ".
		       "left join nu_domain as D on D.id=U.domain ".
		       "where R.{$select}={$user} && R.model={$model} ".
		       "limit {$limit} offset {$offset};";

		  return WrapMySQL::q( $q, "Error fetching relation list" );
		}

	}

?>
