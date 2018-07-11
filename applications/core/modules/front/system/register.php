<?php
/**
 * @brief		Register
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 July 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Register
 */
class _register extends \IPS\Dispatcher\Controller
{
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->_fromLogin ) )
		{
			\IPS\Output::i()->redirect( \IPS\Settings::i()->base_url );
		}
		
		if( \IPS\Request::i()->do !== 'complete'
			and \IPS\Request::i()->do !== 'changeEmail' and \IPS\Request::i()->do !== 'validate'
			and \IPS\Request::i()->do !== 'validating' and \IPS\Request::i()->do !== 'reconfirm'
			and \IPS\Request::i()->do !== 'finish' and \IPS\Request::i()->do !== 'cancel' )
		{
			if ( \IPS\Login::registrationType() == 'redirect' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::external( \IPS\Settings::i()->allow_reg_target ) );
			}
			elseif ( \IPS\Login::registrationType() == 'disabled' )
			{
				\IPS\Output::i()->error( 'reg_disabled', '2S129/5', 403, '' );
			}
		}
		
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
	}
	
	/**
	 * Register
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if( !\IPS\Settings::i()->site_online )
		{
			\IPS\Output::i()->showOffline();
		}

		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=system&controller=register', NULL, 'register' ), array(), 'loc_registering' );
		\IPS\Output::i()->allowDefaultWidgets = FALSE;
		
		/* Quick registration does not work with COPPA */
		if ( \IPS\Login::registrationType() == 'normal' )
		{
			$form = $this->_registrationForm();
			$form->class = 'ipsForm_fullWidth';
			
			\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('registration');

			if( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupRegisterTemplate' ), new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Login::LOGIN_REGISTRATION_FORM ) );
			}
			else
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->register( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupRegisterTemplate' ), new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Login::LOGIN_REGISTRATION_FORM ) ), new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) ) );
			}
			
			return;
		}
				
		if( isset( $_SESSION['coppa_user'] ) AND ( \IPS\Settings::i()->use_coppa OR \IPS\Settings::i()->minimum_age > 0 ) )
		{
			if ( \IPS\Settings::i()->minimum_age > 0 )
			{
				$message = \IPS\Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->minimum_age ) ) );
				\IPS\Output::i()->error( $message, '2C223/7', 403, '' );
			}
			else
			{
				\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('reg_awaiting_validation');
				return \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->notCoppaValidated();
			}
		}
		
		/* Set up the step array */
		$steps = array();
				
		/* If coppa is enabled we need to add a birthday verification */
		if ( \IPS\Settings::i()->use_coppa OR \IPS\Settings::i()->minimum_age > 0 )
		{
			$steps['coppa'] = function( $data )
			{
				/* Build the form */
				$form = new \IPS\Helpers\Form( 'coppa', 'register_button' );
				$form->add( new \IPS\Helpers\Form\Date( 'bday', NULL, TRUE, array( 'max' => \IPS\DateTime::create() ) ) );
				
				if( $values = $form->values() )
				{
					/* Did we pass the minimum age requirement? */
					if ( \IPS\Settings::i()->minimum_age > 0 AND $values['bday']->diff( \IPS\DateTime::create() )->y < \IPS\Settings::i()->minimum_age )
					{
						$_SESSION['coppa_user'] = TRUE;
						
						$message = \IPS\Member::loggedIn()->language()->addToStack( 'register_denied_age', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->minimum_age ) ) );
						\IPS\Output::i()->error( $message, '2C223/8', 403, '' );
					}
					/* We did, but we should check normal COPPA too */
					else if( ( $values['bday']->diff( \IPS\DateTime::create() )->y < 13 ) )
					{
						$_SESSION['coppa_user'] = TRUE;
						return \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->notCoppaValidated();
					}
								
					return $values;
				}
				
				return \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->coppa( $form );
			};
		}

		$self = $this;
		
		$steps['basic_info'] = function ( $data ) use ( $self )
		{
			$form = $this->_registrationForm();
	
			return \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->register( $form, new \IPS\Login( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Login::LOGIN_REGISTRATION_FORM ) );
		};
		
		/* Output */
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('registration');
		\IPS\Output::i()->output = (string) new \IPS\Helpers\Wizard( $steps,	\IPS\Http\Url::internal( 'app=core&module=system&controller=register' ), FALSE );
	}
	
	/**
	 * Normal registration form
	 *
	 * @return \IPS\Form
	 */
	protected function _registrationForm()
	{
		$form = \IPS\core\modules\front\system\register::buildRegistrationForm();

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( !\IPS\Settings::i()->quick_register )
			{
				foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::REG ) as $group => $fields )
				{
					foreach ( $fields as $id => $field )
					{
						if( $field->required and ( $values[ $field->name ] === NULL or !isset( $values[ $field->name ] ) ) )
						{
							\IPS\Output::i()->error( 'reg_required_fields', '1C223/5', 403, '' );
						}

						$profileFields[ "field_{$id}" ] = $field::stringValue( $values[ $field->name ] );

						if ( $fields instanceof \IPS\Helpers\Form\Editor )
						{
							$field->claimAttachments( $this->id );
						}
					}
				}
			}
			else
			{
				$profileFields = array();
			}

			if ( \IPS\Settings::i()->security_questions_enabled and ( \IPS\Settings::i()->security_questions_prompt === 'register' or ( \IPS\Settings::i()->security_questions_prompt === 'optional' and !$values['security_questions_optout_title'] ) ) )
			{
				$answers = array();
				foreach ( $values as $k => $v )
				{
					if ( preg_match( '/^security_question_q_(\d+)$/', $k, $matches ) )
					{
						if ( isset( $answers[ $v ] ) )
						{
							$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'security_questions_unique', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->security_questions_number ?: 3 ) ) );
							break;
						}
						else
						{
							$answers[ $v ] = $v;
						}
					}
				}
			}
			
			if ( !$form->error )
			{
				/* Create Member */
				$member = \IPS\core\modules\front\system\register::_createMember( $values, $profileFields );
				
				/* Log them in */
				\IPS\Session::i()->setMember( $member );
				\IPS\Member\Device::loadOrCreate( $member, FALSE )->updateAfterAuthentication( TRUE, NULL );

				if ( isset( \IPS\Request::i()->ref ) and $ref = @base64_decode( \IPS\Request::i()->ref ) )
				{
					try
					{
						$ref = \IPS\Http\Url::createFromString( $ref );
						if ( !( $ref instanceof \IPS\Http\Url\Internal ) or $ref->base !== 'front' )
						{
							throw new \Exception;
						}
					}
					catch ( \Exception $e )
					{
						$ref = \IPS\Http\Url::internal('');
					}
				}
				else
				{
					$ref = \IPS\Http\Url::internal('');
				}
				
				/* Redirect */
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( array( 'redirect' => (string) $ref ) );
				}
				else
				{
					\IPS\Output::i()->redirect( $ref );
				}
			}
		}
		
		return $form;
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{
		/* You must be logged in for this action */
		if( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C223/B', 403, '' );
		}

		$steps = \IPS\Member\ProfileStep::loadAll();
		
		/* Do we need to bother? We should only show this form if there are required items, but will show both required and suggested where possible to allow the user to complete as much of their profile as possible */
		$haveRequired = FALSE;
		foreach( $steps AS $id => $step )
		{
			if ( $step->required AND !$step->completed( \IPS\Member::loggedIn() ) )
			{
				$haveRequired = TRUE;
				break;
			}
		}
		
		if ( $haveRequired === FALSE )
		{
			/* Nope, forward */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
		}

		$wizardSteps = array();
		$url = \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=finish', 'front', 'register' );
		foreach( \IPS\Application::allExtensions( 'core', 'ProfileSteps' ) AS $extension )
		{
			if ( method_exists( $extension, 'wizard') AND count( $extension::wizard() ) )
			{
				$wizardSteps = array_merge( $wizardSteps, $extension::wizard() );
			}
		}

		$wizardSteps = \IPS\Member\ProfileStep::setOrder( $wizardSteps );

		$wizardSteps = array_merge( $wizardSteps, array( 'profile_done' => function( $data ) {
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ), 'saved' );
		} ) );
		
		$wizard = new \IPS\Helpers\Wizard( $wizardSteps, $url, TRUE, NULL, TRUE );
	
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( '2fa.css', 'core', 'global' ) );
		\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings.css' ) );
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('complete_profile_registration');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->finishRegistration( $wizard );
	}

	/**
	 * Build Registration Form
	 *
	 * @return	\IPS\Helpers\Form
	 */
	public static function buildRegistrationForm()
	{
		/* Build the form */
		$form = new \IPS\Helpers\Form( 'form', 'register_button', NULL, array( 'data-controller' => 'core.front.system.register') );
		$form->add( new \IPS\Helpers\Form\Text( 'username', NULL, TRUE, array( 'accountUsername' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE, array( 'accountEmail' => TRUE, 'maxLength' => 150, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array( 'showMeter' => \IPS\Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password_confirm', NULL, TRUE, array( 'confirm' => 'password', 'bypassProfanity' => TRUE ) ) );
	
		/* Profile fields */
		if ( \IPS\Login::registrationType() == 'full' )
		{
			foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::REG ) as $group => $fields )
			{
				foreach ( $fields as $field )
				{
					$form->add( $field );
				}
			}
			$form->addSeparator();
		}
		else
		{
			$form->class = 'ipsForm_vertical';
		}
		
		$question = FALSE;
		try
		{
			$question = \IPS\Db::i()->select( '*', 'core_question_and_answer', NULL, "RAND()" )->first();
		}
		catch ( \UnderflowException $e ) {}
		
		/* 2FA Q&A? */
		static::addQuestion2FA( $form );
		
		/* Random Q&A */
		if( $question )
		{
			$form->hiddenValues['q_and_a_id'] = $question['qa_id'];
	
			$form->add( new \IPS\Helpers\Form\Text( 'q_and_a', NULL, TRUE, array(), function( $val )
			{
				$qanda  = intval( \IPS\Request::i()->q_and_a_id );
				$pass = true;
			
				if( $qanda )
				{
					try
					{
						$question = \IPS\Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $qanda ) )->first();
					}
					catch( \UnderflowException $e )
					{
						throw new \DomainException( 'q_and_a_incorrect' );
					}

					$answers = json_decode( $question['qa_answers'] );

					if( $answers )
					{
						$answers = is_array( $answers ) ? $answers : array( $answers );
						$pass = FALSE;
					
						foreach( $answers as $answer )
						{
							$answer = trim( $answer );

							if( mb_strlen( $answer ) AND mb_strtolower( $answer ) == mb_strtolower( $val ) )
							{
								$pass = TRUE;
							}
						}
					}
				}
				else
				{
					$questions = \IPS\Db::i()->select( 'count(*)', 'core_question_and_answer', 'qa_id > 0' )->first();
					if( $questions )
					{
						$pass = FALSE;
					}
				}
				
				if( !$pass )
				{
					throw new \DomainException( 'q_and_a_incorrect' );
				}
			} ) );
			
			/* Set the form label */
			\IPS\Member::loggedIn()->language()->words['q_and_a'] = \IPS\Member::loggedIn()->language()->addToStack( 'core_question_and_answer_' . $question['qa_id'], FALSE );
		}
		
		$captcha = new \IPS\Helpers\Form\Captcha;
		
		if ( (string) $captcha !== '' )
		{
			$form->add( $captcha );
		}
		
		if ( $question OR (string) $captcha !== '' )
		{
			$form->addSeparator();
		}
		
		$form->add( new \IPS\Helpers\Form\Checkbox( 'reg_admin_mails', \IPS\Settings::i()->updates_consent_default == 'enabled' ? TRUE : FALSE, FALSE ) );
		
		\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] = sprintf( \IPS\Member::loggedIn()->language()->get("reg_agreed_terms"), \IPS\Http\Url::internal( 'app=core&module=system&controller=terms', 'front', 'terms', array(), \IPS\Http\Url::PROTOCOL_RELATIVE ) );
		
		/* Build the appropriate links for registration terms & privacy policy */
		if ( \IPS\Settings::i()->privacy_type == "internal" )
		{
			\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( \IPS\Member::loggedIn()->language()->get("reg_privacy_link"), \IPS\Http\Url::internal( 'app=core&module=system&controller=privacy', 'front', 'privacy', array(), \IPS\Http\Url::PROTOCOL_RELATIVE ), 'data-ipsDialog data-ipsDialog-size="wide" data-ipsDialog-title="' . \IPS\Member::loggedIn()->language()->get("privacy") . '"' );
		}
		else if ( \IPS\Settings::i()->privacy_type == "external" )
		{
			\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( \IPS\Member::loggedIn()->language()->get("reg_privacy_link"), \IPS\Http\Url::external( \IPS\Settings::i()->privacy_link ), 'target="_blank"' );
		}
		
		$form->add( new \IPS\Helpers\Form\Checkbox( 'reg_agreed_terms', NULL, TRUE, array(), function( $val )
		{
			if ( !$val )
			{
				throw new \InvalidArgumentException('reg_not_agreed_terms');
			}
		} ) );
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_system.js', 'core', 'front' ), \IPS\Output::i()->js( 'front_templates.js', 'core', 'front' ) );
		
		return $form;
	}
	
	/**
	 * Add in the Q&A 2FA if it is enforced
	 *
	 * @param	\IPS\Form	$form		Form
	 * @return void
	 */
	protected static function addQuestion2FA( &$form )
	{
		/* Security Questions */
		if ( \IPS\Settings::i()->security_questions_enabled and in_array( \IPS\Settings::i()->security_questions_prompt, array( 'register', 'optional' ) ) )
		{
			$numberOfQuestions = \IPS\Settings::i()->security_questions_number ?: 3;
			$securityQuestions = array();
			foreach ( \IPS\MFA\SecurityQuestions\Question::roots() as $securityQuestion )
			{
				$securityQuestions[ $securityQuestion->id ] = $securityQuestion->_title;
			}
			
			$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack('security_questions_setup_blurb', FALSE, array( 'pluralize' => array( $numberOfQuestions ) ) ) );
			
			if ( \IPS\Settings::i()->security_questions_prompt === 'optional' )
			{
				$securityOptoutToggles = array();
				foreach ( range( 1, min( $numberOfQuestions, count( $securityQuestions ) ) ) as $i )
				{
					$securityOptoutToggles[] = 'security_question_q_' . $i;
					$securityOptoutToggles[] = 'security_question_a_' . $i;
				}
				
				$optOutCheckbox = new \IPS\Helpers\Form\Checkbox( 'security_questions_optout_title', FALSE, FALSE, array( 'togglesOff' => $securityOptoutToggles ) );
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists('security_questions_opt_out_warning_value') )
				{
					$optOutCheckbox->description = \IPS\Member::loggedIn()->language()->addToStack('security_questions_opt_out_warning_value', TRUE, array( 'returnBlank' => TRUE ) );
				}
				$form->add( $optOutCheckbox );
			}
			foreach ( range( 1, min( $numberOfQuestions, count( $securityQuestions ) ) ) as $i )
			{
				$securityValidation = function( $val ) {
					if ( !$val and ( \IPS\Settings::i()->security_questions_prompt === 'register' or !isset( \IPS\Request::i()->security_questions_optout_title_checkbox ) ) )
					{
						throw new \DomainException('form_required');
					}
				};
				
				$questionField = new \IPS\Helpers\Form\Select( 'security_question_q_' . $i, NULL, FALSE, array( 'options' => $securityQuestions ), $securityValidation, NULL, NULL, 'security_question_q_' . $i );
				$questionField->label = \IPS\Member::loggedIn()->language()->addToStack('security_question_q');
	
				$answerField = new \IPS\Helpers\Form\Text( 'security_question_a_' . $i, NULL, NULL, array(), $securityValidation, NULL, NULL, 'security_question_a_' . $i );
				$answerField->label = \IPS\Member::loggedIn()->language()->addToStack('security_question_a');
				
				$form->add( $questionField );
				$form->add( $answerField );
			}
			$form->addSeparator();
		}
	}

	/**
	 * Create Member
	 *
	 * @param array $values       Values from form
	 * @param array $profileFields    Profile field values from registration
	 * @return  \IPS\Member
	 */
	public static function _createMember( $values, $profileFields )
	{
		/* Create */
		$member = new \IPS\Member;
		$member->name	   = $values['username'];
		$member->email		= $values['email_address'];
		$member->setLocalPassword( $values['password'] );
		$member->allow_admin_mails  = $values['reg_admin_mails'];
		$member->member_group_id	= \IPS\Settings::i()->member_group;
		$member->members_bitoptions['view_sigs'] = TRUE;
		$member->last_visit = time();
		
		/* Query spam service */
		$spamCode = NULL;
		$spamAction = NULL;
		if( \IPS\Settings::i()->spam_service_enabled )
		{
			$spamAction = $member->spamService( 'register', NULL, $spamCode );
			if( $spamAction == 4 )
			{
				\IPS\Output::i()->error( 'spam_denied_account', '2S129/1', 403, '' );
			}
		}
		
		if ( \IPS\Settings::i()->quick_register )
		{
			/* Initial Save */
			$member->save();
			
			/* This looks a bit weird, but the extensions expect an account to exist at this point, so we'll let the system save it now, then do what we need to do, then save again */
			foreach( \IPS\Member\ProfileStep::loadAll() AS $step )
			{
				$extension = $step->extension;
				$extension::formatFormValues( $values, $member, $form );
			}
		}
		
		/* Save anything the profile extensions did */
		$member->save();
		$member->logHistory( 'core', 'account', array( 'type' => 'register', 'spamCode' => $spamCode, 'spamAction' => $spamAction ), FALSE );
		
		/* Security Questions */
		if ( \IPS\Settings::i()->security_questions_enabled and in_array( \IPS\Settings::i()->security_questions_prompt, array( 'register', 'optional' ) ) )
		{
			if ( isset( $values['security_questions_optout_title'] ) )
			{
				$member->members_bitoptions['security_questions_opt_out'] = TRUE;

				/* Log MFA Opt-out */
				$member->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => FALSE, 'optout' => TRUE ) );
			}
			else
			{
				$answers = array();
				
				foreach ( $values as $k => $v )
				{
					if ( preg_match( '/^security_question_q_(\d+)$/', $k, $matches ) )
					{
						$answers[ $v ] = array(
							'answer_question_id'	=> $v,
							'answer_member_id'		=> $member->member_id,
							'answer_answer'			=> \IPS\Text\Encrypt::fromPlaintext( $values[ 'security_question_a_' . $matches[1] ] )->tag()
						);
					}
				}
								
				if ( count( $answers ) )
				{
					\IPS\Db::i()->insert( 'core_security_answers', $answers );
				}
				
				$member->members_bitoptions['has_security_answers'] = TRUE;

				/* Log MFA Enable */
				$member->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => TRUE ) );
			}
			$member->save();
		}

		/* Save custom field values */
		if ( ! \IPS\Settings::i()->quick_register )
		{
			\IPS\Db::i()->replace( 'core_pfields_content', array_merge( array( 'member_id' => $member->member_id ), $profileFields ) );
		}
		
		/* Log that we gave consent for admin emails */
		$member->logHistory( 'core', 'admin_mails', array( 'enabled' => (boolean) $member->allow_admin_mails ) );
		
		/* Log that we gave consent for terms and privacy */
		if ( \IPS\Settings::i()->privacy_type != 'none' )
		{
			$member->logHistory( 'core', 'terms_acceptance', array( 'type' => 'privacy' ) );
		}
		
		$member->logHistory( 'core', 'terms_acceptance', array( 'type' => 'terms' ) );
			
		/* Handle validation */
		$member->postRegistration();

		/* Save and return */
		return $member;
	}
	
	/**
	 * A printable coppa form
	 *
	 * @return	void
	 */
	protected function coppaForm()
	{
		$output = \IPS\Theme::i()->getTemplate( 'system' )->coppaConsent();
		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $output );
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}

	/**
	 * Awaiting Validation
	 *
	 * @return	void
	 */
	protected function validating()
	{
		if( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
		}
		
		/* Fetch the validating record to see what we're dealing with */
		try
		{
			$validating = \IPS\Db::i()->select( '*', 'core_validating', array( 'member_id=? AND ( new_reg=? OR email_chg=? )', \IPS\Member::loggedIn()->member_id, 1, 1 ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'validate_no_record', '2S129/4', 404, '' );
		}
		
		/* They're not validated but in what way? */
		if ( $validating['reg_cancelled'] )
		{
			/* They are cancelled and will be deleted, haha, etc */
			\IPS\Output::i()->error( 'reg_is_cancelled', '2C223/9', 403, '' );
			
		}
		else if ( $validating['user_verified'] )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->notAdminValidated();
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->notValidated( $validating );
		}
		
		/* Display */
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('reg_awaiting_validation');
	}
	
	/**
	 * Resend validation email
	 *
	 * @return	void
	 */
	protected function resend()
	{
		\IPS\Session::i()->csrfCheck();

		$validating = \IPS\Db::i()->select( '*', 'core_validating', array( 'member_id=?', \IPS\Member::loggedIn()->member_id ) );
	
		if ( !count( $validating ) )
		{
			\IPS\Output::i()->error( 'validate_no_record', '2S129/3', 404, '' );
		}
	
		foreach( $validating as $reg )
		{
			if ( $reg['email_sent'] and $reg['email_sent'] > ( time() - 900 ) )
			{
				\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack('validation_email_rate_limit', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( $reg['email_sent'] )->relative( \IPS\DateTime::RELATIVE_FORMAT_LOWER ) ) ) ), '1C223/4', 429, '', array( 'Retry-After' => \IPS\DateTime::ts( $reg['email_sent'] )->add( new \DateInterval( 'PT15M' ) )->format('r') ) );
			}
			
			\IPS\Email::buildFromTemplate( 'core', $reg['email_chg'] ? 'email_change' : 'registration_validate', array( \IPS\Member::loggedIn(), $reg['vid'] ), \IPS\Email::TYPE_TRANSACTIONAL )->send( \IPS\Member::loggedIn() );
			
			\IPS\Db::i()->update( 'core_validating', array( 'email_sent' => time() ), array( 'vid=?', $reg['vid'] ) );
		}
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ), 'reg_email_resent' );
	}
	
	/**
	 * Validate
	 *
	 * @return	void
	 */
	protected function validate()
	{
		if( \IPS\Request::i()->vid AND \IPS\Request::i()->mid )
		{
			/* Load record */
			try
			{
				$record = \IPS\Db::i()->select( '*', 'core_validating', array( 'vid=? AND member_id=? AND ( new_reg=? or email_chg=? )', \IPS\Request::i()->vid, \IPS\Request::i()->mid, 1, 1 ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				\IPS\Output::i()->error( 'validate_no_record', '2S129/2', 404, '' );
			}
			
			/* Validate */
			$member = \IPS\Member::load( \IPS\Request::i()->mid );
			if ( $record['new_reg'] )
			{
				$member->emailValidationConfirmed( $record );
			}
			else
			{
				$member->members_bitoptions['validating'] = FALSE;
				$member->save();
				
				\IPS\Db::i()->delete( 'core_validating', array( 'member_id=?', $member->member_id ) );

				/* Send a confirmation email */
				\IPS\Email::buildFromTemplate( 'core', 'email_address_changed', array( $member, $record['prev_email'] ), \IPS\Email::TYPE_TRANSACTIONAL )->send( $record['prev_email'], array(), array(), NULL, NULL, array( 'Reply-To' => \IPS\Settings::i()->email_in ) );
			}
			
			/* Log in and Redirect */
			\IPS\Session::i()->setMember( $member );
			\IPS\Member\Device::loadOrCreate( $member )->updateAfterAuthentication( TRUE );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ), 'validate_email_confirmation' );
		}
	}

	/**
	 * Complete Profile
	 *
	 * @return	void
	 */
	protected function complete()
	{
		/* Check we are an incomplete member */
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		elseif ( \IPS\Member::loggedIn()->real_name and \IPS\Member::loggedIn()->email )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
		}
				
		/* Build the form */
		$form = new \IPS\Helpers\Form( 'form', 'register_button' );
		if ( isset( \IPS\Request::i()->ref ) )
		{
			$form->hiddenValues['ref'] = \IPS\Request::i()->ref;
		}
		if( !\IPS\Member::loggedIn()->real_name OR \IPS\Member::loggedIn()->name === \IPS\Member::loggedIn()->language()->get('guest') )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'username', NULL, TRUE, array( 'accountUsername' => \IPS\Member::loggedIn() ) ) );
		}
		if( !\IPS\Member::loggedIn()->email )
		{
			$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE, array( 'accountEmail' => TRUE ) ) );
		}
		if ( \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'] )
		{
			\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] = sprintf( \IPS\Member::loggedIn()->language()->get("reg_agreed_terms"), \IPS\Http\Url::internal( 'app=core&module=system&controller=terms', 'front', 'terms' ) );
			$form->add( new \IPS\Helpers\Form\Checkbox( 'reg_agreed_terms', NULL, TRUE, array(), function( $val )
			{
				if ( !$val )
				{
					throw new \InvalidArgumentException('reg_not_agreed_terms');
				}
			} ) );
		}
		$form->addButton( 'cancel', 'link', \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=cancel', 'front', 'register' )->csrf() );

		/* Handle the submission */
		if ( $values = $form->values() )
		{
			if( isset( $values['username'] ) )
			{
				\IPS\Member::loggedIn()->name = $values['username'];
			}
			$spamCode = NULL;
			$spamAction = NULL;
			if( isset( $values['email_address'] ) )
			{
				\IPS\Member::loggedIn()->email = $values['email_address'];

				if( \IPS\Settings::i()->spam_service_enabled )
				{
					$spamAction = \IPS\Member::loggedIn()->spamService( 'register', NULL, $spamCode );
					if( $spamAction == 4 )
					{
						$action = \IPS\Settings::i()->spam_service_action_4;

						/* Any other action will automatically be handled by the call to spamService() */
						if( $action == 4 )
						{
							\IPS\Member::loggedIn()->delete();
						}

						\IPS\Output::i()->error( 'spam_denied_account', '2S272/1', 403, '' );
					}
				}
			}
			\IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'] = FALSE;
			
			/* Save */
			\IPS\Member::loggedIn()->save();
			\IPS\Member::loggedIn()->logHistory( 'core', 'account', array( 'type' => 'complete' ), FALSE );
			
			/* Handle validation */
			\IPS\Member::loggedIn()->postRegistration( ( isset( $values['email_address'] ) ) ? FALSE : TRUE );
			
			/* Set member as a full member in the session table */
			\IPS\Session::i()->setType( \IPS\Session\Front::LOGIN_TYPE_MEMBER );
			
			/* Redirect */
			if ( isset( \IPS\Request::i()->ref ) and $ref = @base64_decode( \IPS\Request::i()->ref ) )
			{
				try
				{
					$ref = \IPS\Http\Url::createFromString( $ref );
					if ( !( $ref instanceof \IPS\Http\Url\Internal ) or $ref->base !== 'front' )
					{
						throw new \Exception;
					}
				}
				catch ( \Exception $e )
				{
					$ref = \IPS\Http\Url::internal('');
				}
			}
			else
			{
				$ref = \IPS\Http\Url::internal('');
			}
			\IPS\Output::i()->redirect( $ref );
		}

		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('reg_complete_details');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->completeProfile( $form );
	}

	/**
	 * Change Email
	 *
	 * @return	void
	 */
	protected function changeEmail()
	{
		/* Are we logged in and pending validation? */
		if( !\IPS\Member::loggedIn()->member_id OR !\IPS\Member::loggedIn()->members_bitoptions['validating'] )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C223/2', 403, '' );
		}

		/* Do we have any pending validation emails? */
		try
		{
			$pending = \IPS\Db::i()->select( '*', 'core_validating', array( 'member_id=? AND ( new_reg=1 or email_chg=1 )', \IPS\Member::loggedIn()->member_id ), 'entry_date DESC' )->first();
		}
		catch( \UnderflowException $e )
		{
			$pending = null;
		}
				
		/* Build the form */
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Email( 'new_email', '', TRUE, array( 'accountEmail' => \IPS\Member::loggedIn() ) ) );
		$captcha = new \IPS\Helpers\Form\Captcha;
		if ( (string) $captcha !== '' )
		{
			$form->add( $captcha );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Check spam defense whitelist */
			if( \IPS\Settings::i()->spam_service_enabled AND ( isset( $pending['new_reg'] ) AND $pending['new_reg'] ) AND \IPS\Member::loggedIn()->spamDefenseWhitelist() )
			{
				/* We specifically say it's 'register' so that actions are still performed on the account */
				$newEmailScore = \IPS\Member::loggedIn()->spamService( 'register', $values['new_email'] );

				/* Is it a ban response? */
				if( $newEmailScore == 4 )
				{
					\IPS\Output::i()->error( 'spam_denied_account', '2C223/A', 403, '' );
				}
			}

			/* Change the email */
			$oldEmail = \IPS\Member::loggedIn()->email;
			\IPS\Member::loggedIn()->email = $values['new_email'];
			\IPS\Member::loggedIn()->save();
			foreach ( \IPS\Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( \IPS\Member::loggedIn(), $oldEmail, $values['new_email'] );
				}
				catch( \BadMethodCallException $e ) {}
			}
			\IPS\Member::loggedIn()->memberSync( 'onEmailChange', array( $values['new_email'], $oldEmail ) );
			\IPS\Member::loggedIn()->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => $values['new_email'], 'by' => 'manual' ) );
			
			/* Invalidate sessions except this one */
			\IPS\Member::loggedIn()->invalidateSessionsAndLogins( \IPS\Session::i()->id );
			if( isset( \IPS\Request::i()->cookie['login_key'] ) )
			{
				\IPS\Member\Device::loadOrCreate( \IPS\Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* If email validation is required, do that... */
			if ( in_array( \IPS\Settings::i()->reg_auth_type, array( 'user', 'admin_user' ) ) )
			{
				/* Delete any pending validation emails */
				if ( $pending['vid'] )
				{
					\IPS\Db::i()->delete( 'core_validating', array( 'member_id=? AND ( new_reg=1 or email_chg=1 )', \IPS\Member::loggedIn()->member_id ) );
				}
			
				$vid = \IPS\Login::generateRandomString();
		
				\IPS\Db::i()->insert( 'core_validating', array(
					'vid'			=> $vid,
					'member_id'		=> \IPS\Member::loggedIn()->member_id,
					'entry_date'	=> time(),
					'new_reg'		=> !$pending or $pending['new_reg'],
					'email_chg'		=> $pending and $pending['email_chg'],
					'user_verified'	=> ( \IPS\Settings::i()->reg_auth_type == 'admin' ) ?: FALSE,
					'ip_address'	=> \IPS\Request::i()->ipAddress(),
					'email_sent'	=> time(),
				) );
							
				\IPS\Email::buildFromTemplate( 'core', $pending['email_chg'] ? 'email_change' : 'registration_validate', array( \IPS\Member::loggedIn(), $vid ), \IPS\Email::TYPE_TRANSACTIONAL )->send( \IPS\Member::loggedIn() );
			}
			
			/* Redirect */				
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
		}
		
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Cancel Registration
	 *
	 * @return	void
	 */
	protected function cancel()
	{
		/* This bit is kind of important */
		\IPS\Session::i()->csrfCheck();
		if ( \IPS\Member::loggedIn()->name and \IPS\Member::loggedIn()->email and !\IPS\Db::i()->select( 'COUNT(*)', 'core_validating', array( 'member_id=? AND new_reg=1', \IPS\Member::loggedIn()->member_id ) )->first() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C223/1', 403, '' );
		}

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete( 'reg_cancel', 'reg_cancel_confirm', 'reg_cancel' );

		/* Log the user out. Previously, we immediately deleted the account however this has been changed to let the cleanup task handle this instead. */
		\IPS\Request::i()->clearLoginCookies();

		/* Reset session global parameters */
		$_SESSION	= array();

		/* We have to explicitly reset the session cookie
			@link http://php.net/manual/en/function.session-destroy.php */
		$params = session_get_cookie_params();

		setcookie( session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"] );

		/* Then destroy the session itself */
		session_destroy();

		/* Member sync callback */
		\IPS\Member::loggedIn()->memberSync( 'onLogout', array( \IPS\Http\Url::internal( '' ) ) );
				
		/* Flag user as having cancelled their registration */
		\IPS\Db::i()->update( 'core_validating', array( 'reg_cancelled' => time() ), array( 'member_id=? AND new_reg=1', \IPS\Member::loggedIn()->member_id ) );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ), 'reg_canceled' );
	}
	
	/**
	 * Reconfirm terms or privacy policy
	 *
	 * @return	void
	 */
	protected function reconfirm()
	{
		/* You must be logged in for this action */
		if( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C223/C', 403, '' );
		}

		/* Generate form */
		$form = new \IPS\Helpers\Form( 'reconfirm_checkbox', 'reconfirm_checkbox' );
		if ( isset( \IPS\Request::i()->ref ) )
		{
			$form->hiddenValues['ref'] = \IPS\Request::i()->ref;
		}
		$form->class = 'ipsForm_vertical';
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Log that we gave consent */
			if ( \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] )
			{
				\IPS\Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'privacy' ) );
			}
			
			if ( \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'] )
			{
				\IPS\Member::loggedIn()->logHistory( 'core', 'terms_acceptance', array( 'type' => 'terms' ) );
			}
			
			\IPS\Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] = FALSE;
			\IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'] = FALSE;
			\IPS\Member::loggedIn()->save();
			
			if ( isset( \IPS\Request::i()->ref ) )
			{
				try
				{
					$ref = \IPS\Http\Url\Friendly::createFromString( base64_decode( \IPS\Request::i()->ref ) );
					if ( $ref instanceof \IPS\Http\Url\Internal )
					{
						\IPS\Output::i()->redirect( $ref );
					}
				}
				catch ( \Exception $e ) { }
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('') );
		}
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('terms_of_use');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('system')->reconfirmTerms(  \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'],  \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_privacy'], $form );
	}
}