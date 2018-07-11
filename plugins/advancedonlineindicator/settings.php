//<?php

$form->add( new \IPS\Helpers\Form\Text( 'onlineIndicator_iconOnline', \IPS\Settings::i()->onlineIndicator_iconOnline, TRUE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineIndicator_iconOnline' ), NULL, NULL, NULL, 'onlineIndicator_iconOnline' ) );
$form->add( new \IPS\Helpers\Form\Number( 'onlineIndicator_inName_size', \IPS\Settings::i()->onlineIndicator_inName_size, FALSE, array(), function( $val )
{
	if ( $val == 0 )
	{
		throw new \DomainException('form_bad_value');
	}
}, NULL, 'px', 'onlineIndicator_inName_size' ) );

$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_inName_offline', \IPS\Settings::i()->onlineIndicator_inName_offline, FALSE, array('togglesOn' => array( 'onlineIndicator_iconOffline' )), NULL, NULL, NULL, 'onlineIndicator_inName_offline' ) );
$form->add( new \IPS\Helpers\Form\Text( 'onlineIndicator_iconOffline', \IPS\Settings::i()->onlineIndicator_iconOffline, TRUE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineIndicator_iconOffline' ), NULL, NULL, NULL, 'onlineIndicator_iconOffline' ) );

$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_Advanced', \IPS\Settings::i()->onlineIndicator_Advanced, FALSE, array(), NULL, NULL, NULL, 'onlineIndicator_Advanced' ) );

$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_inName', \IPS\Settings::i()->onlineIndicator_inName, FALSE, array('togglesOn' => array( 'onlineIndicator_inName_before')), NULL, NULL, NULL, NULL ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_inName_before', \IPS\Settings::i()->onlineIndicator_inName_before, FALSE, array(), NULL, NULL, NULL, 'onlineIndicator_inName_before' ) );


$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_inConversations', \IPS\Settings::i()->onlineIndicator_inConversations, FALSE, array(), NULL, NULL, NULL, NULL ) );


$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_inAvatar', \IPS\Settings::i()->onlineIndicator_inAvatar, FALSE, array( 'togglesOn' => array( 'onlineIndicator_inAvatar_position', 'onlineIndicator_inAvatar_onlineColor', 'onlineIndicator_inAvatar_offlineColor', 'onlineIndicator_roundAvatars' ) ) ) );

$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_roundAvatars', \IPS\Settings::i()->onlineIndicator_roundAvatars, FALSE, array( 'togglesOff' => array( 'onlineIndicator_inAvatar_position' ), 'togglesOn' => array( 'onlineIndicator_roundAvatars_edgeWidth', 'onlineIndicator_roundAvatars_style', 'onlineIndicator_roundAvatars_glow' ) ), NULL, NULL, NULL, 'onlineIndicator_roundAvatars' ) );
$form->add( new \IPS\Helpers\Form\Number( 'onlineIndicator_roundAvatars_edgeWidth', \IPS\Settings::i()->onlineIndicator_roundAvatars_edgeWidth, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineIndicator_roundAvatars_edgeWidth' ), NULL, NULL, NULL, 'onlineIndicator_roundAvatars_edgeWidth' ) );
$form->add( new \IPS\Helpers\Form\Select( 'onlineIndicator_roundAvatars_style', \IPS\Settings::i()->onlineIndicator_roundAvatars_style, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineIndicator_roundAvatars_style', 'options' => array( 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted', 'double' => 'Double' ) ), NULL, NULL, NULL, 'onlineIndicator_roundAvatars_style' ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'onlineIndicator_roundAvatars_glow', \IPS\Settings::i()->onlineIndicator_roundAvatars_glow, FALSE, array(), NULL, NULL, NULL, 'onlineIndicator_roundAvatars_glow' ) );



$form->add( new \IPS\Helpers\Form\Color( 'onlineIndicator_inAvatar_onlineColor', \IPS\Settings::i()->onlineIndicator_inAvatar_onlineColor, FALSE, array(), NULL, NULL, NULL, 'onlineIndicator_inAvatar_onlineColor' ) );
$form->add( new \IPS\Helpers\Form\Color( 'onlineIndicator_inAvatar_offlineColor', \IPS\Settings::i()->onlineIndicator_inAvatar_offlineColor, FALSE, array(), NULL, NULL, NULL, 'onlineIndicator_inAvatar_offlineColor' ) );

$form->add( new \IPS\Helpers\Form\Select( 'onlineIndicator_inAvatar_position', \IPS\Settings::i()->onlineIndicator_inAvatar_position, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineIndicator_inAvatar_position', 'options' => array( 'top_left' => 'Top Left', 'top_right' => 'Top Right', 'bottom_left' => 'Bottom Left', 'bottom_right' => 'Bottom Right' ) ), NULL, NULL, NULL, 'onlineIndicator_inAvatar_position' ) );



if ( $values = $form->values() )
{
	$form->saveAsSettings( $values );
	\IPS\Theme::deleteCompiledCss( 'core', 'front', 'custom' );
	return TRUE;
}

return $form;