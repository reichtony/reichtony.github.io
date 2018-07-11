<?php
/**
 * @brief		Email input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email input class for Form Builder
 */
class _Email extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'accountEmail' => TRUE,	// If TRUE, additional checks will be performed to ensure provided email address is acceptable for use on a user's account. If an \IPS\Member object, that member will be excluded
	 	);
	 * @endcode
	 */
	public $childDefaultOptions = array(
		'accountEmail'	=> FALSE,
	);
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\DomainException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
		
		/* Check it's generally an acceptable email */
		if ( $this->value !== '' and filter_var( $this->value, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			throw new \InvalidArgumentException('form_email_bad');
		}
		
		/* If it's for a user account, do additional checks */
		if ( $this->options['accountEmail'] )
		{
			/* Check if it exists */
			if ( $error = \IPS\Login::emailIsInUse( $this->value, ( $this->options['accountEmail'] instanceof \IPS\Member ) ? $this->options['accountEmail'] : NULL, \IPS\Member::loggedIn()->isAdmin() ) )
			{
				throw new \DomainException( $error );
			}


			/* Check Banned and Allowed Emails only if the data are not coming from an administrator */
			if ( !\IPS\Member::loggedIn()->isAdmin() )
			{
				/* Check it's not a banned address */
				foreach ( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
				{
					if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', $this->value ) )
					{
						throw new \DomainException( 'form_email_banned' );
					}
				}

				/* Check it's an allowed domain */
				if ( \IPS\Settings::i()->allowed_reg_email !== '' AND $allowedEmailDomains = explode( ',', \IPS\Settings::i()->allowed_reg_email )  )
				{
					$matched = FALSE;
					foreach ( $allowedEmailDomains AS $domain )
					{
						if( \mb_stripos( $this->value,  "@" . $domain ) !== FALSE )
						{
							$matched = TRUE;
						}
					}

					if ( count( $allowedEmailDomains ) AND !$matched )
					{
						throw new \DomainException( 'form_email_banned' );
					}
				}
			}
		}
	}
}