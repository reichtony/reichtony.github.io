//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class hook18 extends _HOOK_CLASS_
{
	/**
	 * Internal subroutine for detection...
	 * @see https://mobiforge.com/design-development/tablet-and-mobile-device-detection-php
	 * with small changes
	 * @param	string	UserAgent
	 * @return	string	'PC' or 'TABLET' or 'PHONE' literal
	 */
	private function __deviceDetector( $uagent )
	{
		try
		{
			$tablet_browser = 0;
			$mobile_browser = 0;
	
			$uagentLowerCase = strtolower( $uagent );
	
			if ( preg_match( '/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $uagentLowerCase ) )
				$tablet_browser++;
	
			if ( preg_match( '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $uagentLowerCase ) )
				$mobile_browser++;
			/*
			if ( ( strpos( strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE']))) )
				$mobile_browser++;
			*/
			//$mobile_ua = strtolower( substr( $uagent, 0, 4 ) );
			$mobile_ua = substr( $uagentLowerCase, 0, 4 );
	
			$mobile_agents = array(
				'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
				'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
				'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
				'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
				'newt','noki','palm','pana','pant','phil','play','port','prox',
				'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
				'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
				'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
				'wapr','webc','winw','winw','xda ','xda-'
			);
	
			if ( in_array( $mobile_ua, $mobile_agents ) )
				$mobile_browser++;
	
			if ( strpos( $uagentLowerCase, 'opera mini' ) > 0 )
			{
				$mobile_browser++;
	
				/*
				//Check for tablets on opera mini alternative headers
				$stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
				if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua))
					$tablet_browser++;
				*/
			}
	
			if ( $tablet_browser > 0 )
				return 'TABLET';
	
			if ( $mobile_browser > 0 )
				return 'PHONE';
	
			return 'PC';
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Common detection utility
	 *
	 * @return	string	'pc' or 'tablet' or 'phone' literal
	 */
	public function isOnlineFrom()
	{
		return strtolower( $this->__deviceDetector( $this->sessionData['browser'] ) );
	}

	/**
	 * returns symbol for FontAwesome :)
	 *
	 * @return	string	'pc' or 'tablet' or 'phone' literal
	 */
	public function _isOnlineFromSymbol()
	{
		switch ( strtolower( $this->__deviceDetector( $this->sessionData['browser'] ) ) )
		{
			case 'pc':
				$symbol = '\uf108';
				break;
			case 'tablet':
				$symbol = '\uf10a';
				break;
			case 'phone':
				$symbol = '\uf10b';
				break;
		}

		return json_decode('"' . $symbol . '"');
	}

	/**
	 * returns text phrase... fu... very poor aproach
	 *
	 * @return	phare string
	 */
	public function _isOnlineFromName()
	{

	switch ( strtolower( $this->__deviceDetector( $this->sessionData['browser'] ) ) )
		{
			case 'pc':
				$phrase = ' с ПК';
				break;
			case 'tablet':
				$phrase = ' с Планшета';
				break;
			case 'phone':
				$phrase = ' с Мобильного';
				break;
		}

		return $phrase;
	}

	/**
	 * Is online from mobile phone?
	 *
	 * @return	bool
	 */
	public function isOnlineFromPhone()
	{
		try
		{
			if ( !$this->isOnline() ) return FALSE;
	
			return ( $this->__deviceDetector( $this->sessionData['browser'] ) === 'PHONE' );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}


	/**
	 * Is online from tablet device?
	 *
	 * @return	bool
	 */
	public function isOnlineFromTablet()
	{
		try
		{
			if ( !$this->isOnline() ) return FALSE;
	
			return ( $this->__deviceDetector( $this->sessionData['browser'] ) === 'TABLET' );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}

  	/**
	 * Is online from personal computer?
	 *
	 * @return	bool
	 */
	public function isOnlineFromPC()
	{
		try
		{
			if ( !$this->isOnline() ) return FALSE;
	
			return ( $this->__deviceDetector( $this->sessionData['browser'] ) === 'PC' );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}

}
