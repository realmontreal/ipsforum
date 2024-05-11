<?php
/**
 * @brief		GraphQL: Image query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL\Queries;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image query for GraphQL API
 */
class _Image
{
	/*
	 * @brief 	Query description
	 */
	public static $description = "Returns a gallery image";

	/*
	 * Query arguments
	 */
	public function args()
	{
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() 
	{
		return \IPS\gallery\api\GraphQL\TypeRegistry::image();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed 	Value passed into this resolver
	 * @param 	array 	Arguments
	 * @param 	array 	Context values
	 * @return	\IPS\gallery\Image
	 */
	public function resolve($val, $args, $context, $info)
	{
		try
		{
			$topic = \IPS\gallery\Image::loadAndCheckPerms( $args['id'] );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\GraphQL\SafeException( 'NO_IMAGE', '1F294/2', 400 );
		}

		if( !$topic->can('read') )
		{
			throw new \IPS\Api\GraphQL\SafeException( 'INVALID_ID', '2F294/9', 403 );
		}

		return $topic;
	}
}
