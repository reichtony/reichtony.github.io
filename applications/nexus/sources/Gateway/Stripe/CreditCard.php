<?php
/**
 * @brief		PayPal Stored Card
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Stored Card
 */
class _CreditCard extends \IPS\nexus\Customer\CreditCard
{
	/**
	 * @brief	Card
	 */
	protected $_card;
	
	/**
	 * Get card
	 *
	 * @return	\IPS\nexus\CreditCard
	 */
	public function get_card()
	{
		if ( !$this->_card )
		{
			if ( mb_substr( $this->data, 0, 4 ) === 'src_' )
			{
				$response = $this->method->api( "sources/{$this->data}", NULL, 'get' );
				
				$this->_card = new \IPS\nexus\CreditCard;
				$this->_card->lastFour = $response['card']['last4'];
				switch ( $response['card']['brand'] )
				{
					case 'Visa':
						$this->_card->type = \IPS\nexus\CreditCard::TYPE_VISA;
						break;
					case 'American Express':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS;
						break;
					case 'MasterCard':
						$this->_card->type = \IPS\nexus\CreditCard::TYPE_MASTERCARD;
						break;
					case 'Discover':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DISCOVER;
						break;
					case 'JCB':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_JCB;
						break;
					case 'Diners Club':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DINERS_CLUB;
						break;
				}
				$this->_card->expMonth = $response['card']['exp_month'];
				$this->_card->expYear = $response['card']['exp_year'];
			}
			else
			{		
				$profiles = $this->member->cm_profiles;
				if ( !isset( $profiles[ $this->method->id ] ) )
				{
					throw new \UnexpectedValueException;
				}
				
				$response = $this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'get' );
						
				$this->_card = new \IPS\nexus\CreditCard;
				$this->_card->lastFour = $response['last4'];
				switch ( $response['type'] )
				{
					case 'Visa':
						$this->_card->type = \IPS\nexus\CreditCard::TYPE_VISA;
						break;
					case 'American Express':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS;
						break;
					case 'MasterCard':
						$this->_card->type = \IPS\nexus\CreditCard::TYPE_MASTERCARD;
						break;
					case 'Discover':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DISCOVER;
						break;
					case 'JCB':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_JCB;
						break;
					case 'Diners Club':
						$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DINERS_CLUB;
						break;
				}
				$this->_card->expMonth = $response['exp_month'];
				$this->_card->expYear = $response['exp_year'];
			}
		}		
		return $this->_card;
	}
	
	/**
	 * Set card
	 *
	 * @param	\IPS\nexus\CreditCard	$card	The card
	 * @return	void
	 */
	public function set_card( \IPS\nexus\CreditCard $card )
	{
		/* Create a customer object if we don't have one */
		$profiles = $this->member->cm_profiles;
		if ( !isset( $profiles[ $this->method->id ] ) )
		{
			$response = $this->method->api( 'customers', array(
				'description'	=> $this->member->cm_name,
				'email'			=> $this->member->email,
				'metadata' 		=> array(
					"Customer ID" => $this->member->member_id
				)
			) );
			$profiles[ $this->method->id ] = $response['id'];
			$this->member->cm_profiles = $profiles;
			$this->member->save();
		}
				
		/* If the source is a 3D Secure Authorization, get the actual card */
		$sourceId = $card->token;
		$response = $this->method->api( "sources/{$card->token}", NULL, 'get' );
		if ( $response['type'] === 'three_d_secure' )
		{
			$sourceId = $response['three_d_secure']['card'];
		}
		
		/* Check it doesn't already exist */
		$otherCards = \IPS\Db::i()->select( 'card_data', 'nexus_customer_cards', array( 'card_member=? AND card_method=?', $this->member->member_id, $this->method->id ) );
		if ( count( $otherCards ) )
		{
			$sourceData = $this->method->api( "sources/{$sourceId}", NULL, 'get' );
			if ( isset( $sourceData['card']['fingerprint'] ) )
			{
				foreach ( $otherCards as $otherCardId )
				{
					$otherCardData = $this->method->api( "sources/{$otherCardId}", NULL, 'get' );
					if ( isset( $otherCardData['card']['fingerprint'] ) and $otherCardData['card']['fingerprint'] === $sourceData['card']['fingerprint'] )
					{
						throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack('card_is_duplicate') );
					}
				}
			}
		}
		
		/* Save the card */
		$response = $this->method->api( "customers/{$profiles[ $this->method->id ]}/sources", array(
			'source' => $sourceId
		) );
		$this->data = $response['id'];
		$this->save();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		$profiles = $this->member->cm_profiles;
		try
		{
			$this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'delete' );
		}
		catch ( \IPS\nexus\Gateway\Stripe\Exception $e ) { }
		return parent::delete();
	}
}