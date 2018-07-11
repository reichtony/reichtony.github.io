<?php
/**
 * @brief		Upgrader Language Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Feb 2015
 */

namespace IPS\Lang\Upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Setup Language Class
 */
class _Lang extends \IPS\_Lang
{
	/**
	 * Languages
	 *
	 * @param	null|\IPS\Db\Select	$iterator	Select iterator
	 * @return	array
	 */
	public static function languages( $iterator=NULL )
	{
		if ( !self::$gotAll )
		{
			if( $iterator === NULL )
			{
				if ( isset( \IPS\Data\Store::i()->languages ) )
				{
					$rows = \IPS\Data\Store::i()->languages;
				}
				else
				{
					$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_sys_lang' )->setKeyField('lang_id') );
					\IPS\Data\Store::i()->languages = $rows;
				}
			}
			else
			{
				$rows	= iterator_to_array( $iterator );
			}
			
			foreach( $rows as $id => $lang )
			{
				if ( $lang['lang_default'] )
				{
					self::$defaultLanguageId = $lang['lang_id'];
				}
				self::$multitons[ $id ] = static::constructFromData( $lang );
			}
			
			self::$outputSalt = mt_rand();

			self::$gotAll	= TRUE;
		}
		return self::$multitons;
	}
}