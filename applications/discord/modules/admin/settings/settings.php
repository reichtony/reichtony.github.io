<?php

namespace IPS\discord\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Discord settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return	void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
        parent::execute();

        \IPS\Output::i()->jsFiles = array_merge(
            \IPS\Output::i()->jsFiles,
            \IPS\Output::i()->js( 'admin_settings.js', 'discord', 'admin' )
        );
    }

    /**
     * Show settings form.
     *
     * @return	void
     */
    protected function manage()
    {
        $settings = \IPS\Settings::i();
        $redirectUris = [
            (string) \IPS\Http\Url::internal( 'app=discord&module=register&controller=link&do=admin', 'front' ),
            (string) \IPS\Http\Url::internal( 'applications/discord/interface/oauth/auth.php', 'none' )
        ];

        $form = new \IPS\Helpers\Form;

        if ( $settings->discord_bot_token )
        {
            $form->addButton( 'discord_handshake', 'button', NULL, 'ipsButton ipsButton_alternate', [
                'data-controller' => 'discord.admin.settings.handshake',
                'data-token' => $settings->discord_bot_token
            ] );
        }

        $form->addTab( 'discord_connection_settings' );
        $form->addMessage(
            \IPS\Member::loggedIn()->language()->addToStack( 'discord_redirect_uris', FALSE, [
                'sprintf' => $redirectUris
            ]),
            'ipsMessage ipsMessage_info'
        );
        $form->add(
            new \IPS\Helpers\Form\Text( 'discord_client_id', $settings->discord_client_id ?: NULL, TRUE )
        );
        $form->add(
            new \IPS\Helpers\Form\Password( 'discord_client_secret', $settings->discord_client_secret ?: NULL, TRUE )
        );
        $form->add(
            new \IPS\Helpers\Form\Password( 'discord_bot_token', $settings->discord_bot_token ?: NULL, TRUE )
        );
        $form->add(
            new \IPS\Helpers\Form\Text( 'discord_guild_id', $settings->discord_guild_id ?: NULL, FALSE )
        );

        $form->addTab( 'discord_map_settings' );
        $form->add(
            new \IPS\Helpers\Form\YesNo( 'discord_remove_unmapped', $settings->discord_remove_unmapped ?: FALSE )
        );
        $form->add(
            new \IPS\Helpers\Form\YesNo( 'discord_sync_bans', $settings->discord_sync_bans ?: FALSE )
        );
        $form->add(
            new \IPS\Helpers\Form\YesNo( 'discord_sync_names', $settings->discord_sync_names ?: FALSE )
        );

        if ( $values = $form->values() )
        {
            if ( empty( $settings->discord_guild_id ) || empty( $values['discord_guild_id'] ) )
            {
                $redirect = \IPS\Http\Url::external( 'https://discordapp.com/api/v6/oauth2/authorize' )
                    ->setQueryString([
                        'client_id' => $values['discord_client_id'],
                        'permissions' => 0x00000008,
                        'response_type' => 'code',
                        'scope' => 'bot',
                        'redirect_uri' => $redirectUris[0]
                    ]);
            }
            else
            {
                $redirect = \IPS\Http\Url::internal( 'app=discord&module=settings&controller=settings' );
            }

            $form->saveAsSettings( $values );

            \IPS\Output::i()->redirect( $redirect );
        }

        /* Output */
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'discord_setting_title' );
        \IPS\Output::i()->output = (string) $form;
    }
}