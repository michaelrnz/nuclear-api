<?php
	/*
		nuclear.framework
		altman,ryan,2008

		NuclearDataAspect
		==========================================
			aspect of building Data objects
	*/

	class NuclearDataAspect
	{
		public static function build( &$result, &$builder, &$node )
		{
			//
			// expects iBuilder
			if( !($builder instanceof iBuilder) )
				throw new Exception("NuclearDataAspect expects iBuilder object");

			// get shift boolean
			$doShift = $builder->doShift();

			//
			// check the db request, and rows
			if( $result && mysql_num_rows( $result )>0 )
			{
				// begin parent as root
				$current = $node;

				// attributes to map parents
				$node_attrs = array();
				$node_queue = array($node);

				//
				// loop compose rows
				while( $dbrow = mysql_fetch_array( $result ) )
				{
					// build parents
					if( $doShift )
					{
						$current = $builder->shift( $current, $dbrow, $node_queue );
					}

					// build row
					$row = $builder->compose( $dbrow );

					// append to parent
					if( $row )
						$builder->append( $current, $row );
				}
				return true;

			}
			return false;
		}

	}
	
?>
