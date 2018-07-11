<?php
/**
 * @brief		Redis Storage Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 October 2017
 */

namespace IPS\Data\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Storage Class
 */
class _Redis extends \IPS\Data\Store
{
	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported()
	{
		return class_exists('Redis');
	}
	
	/**
	 * Redis key
	 */
	protected $_redisKey;
		
	/**
	 * Get random string used in the keys to identify this site compared to other sites
	 *
	 * @param   string          $key
	 * @return  string|FALSE    Value from the _datastore; FALSE if key doesn't exist
	 */
	protected function _getRedisKey()
	{
		if ( !$this->_redisKey )
		{
			if ( !( $this->_redisKey = \IPS\Redis::i()->get( 'redisKey_store' ) ) )
			{
				$this->_redisKey = md5( mt_rand() );
				\IPS\Redis::i()->setex( 'redisKey_store', 604800, $this->_redisKey );
			}
		}
		
		return $this->_redisKey . '_str_';
	}
	
	/**
	 * @brief	Cache
	 */
	protected static $cache = array();
	
	/**
	 * Abstract Method: Get
	 *
	 * @param   string          $key
	 * @return  string|FALSE    Value from the _datastore; FALSE if key doesn't exist
	 */
	public function get( $key )
	{
		if( array_key_exists( $key, static::$cache ) )
		{
			return static::$cache[ $key ];
		}

		try
		{
			$return = \IPS\Redis::i()->get( $this->_getRedisKey() . '_' . $key );
			
			if ( $return !== FALSE )
			{
				static::$cache[ $key ] = \IPS\Redis::i()->decode( $return );
				return static::$cache[ $key ];
			}
			else
			{
				throw new \UnderflowException;
			}
		}
		catch( \RedisException $e )
		{
			\IPS\Redis::i()->resetConnection( $e );

			throw new \UnderflowException;
		}
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param	string			$key	Key
	 * @param	string			$value	Value
	 * @return	bool
	 */
	public function set( $key, $value )
	{
		try
		{
			return (bool) \IPS\Redis::i()->setex( $this->_getRedisKey() . '_' . $key, 604800, \IPS\Redis::i()->encode( $value ) );
		}
		catch( \RedisException $e )
		{
			\IPS\Redis::i()->resetConnection( $e );

			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function exists( $key )
	{
		if( array_key_exists( $key, static::$cache ) )
		{
			return ( static::$cache[ $key ] === FALSE ) ? FALSE : TRUE;
		}

		/* We do a get instead of an exists() check because it will cause the cache value to be fetched and cached inline, saving another call to the server */
		try
		{
			return ( $this->get( $key ) === FALSE ) ? FALSE : TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function delete( $key )
	{		
		try
		{
			return (bool) \IPS\Redis::i()->delete( $this->_getRedisKey() . '_' . $key );
		}
		catch( \RedisException $e )
		{
			\IPS\Redis::i()->resetConnection( $e );
			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Clear All
	 *
	 * @return	void
	 */
	public function clearAll()
	{
		$this->_redisKey = md5( mt_rand() );
		\IPS\Redis::i()->setex( 'redisKey_store', 604800, $this->_redisKey );
	}
}