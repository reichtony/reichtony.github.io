//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    exit;
}

class discord_hook_forum extends _HOOK_CLASS_
{
    /**
     * [Node] Add/Edit Form
     *
     * @param	\IPS\Helpers\Form	$form	The form
     * @return	void
     */
    public function form( &$form )
    {
	try
	{
	        parent::form( $form );
	
	        $guild = \IPS\discord\Api\Guild::primary();
        // TODO: extract this into an function
	        $channels = $guild->textChannels()->mapWithKeys(function (array $channel) {
	            return [$channel['id'] => $channel['name']];
	        })->toArray();
	
	        $form->addHeader( 'discord_channels' );
	        $form->add(
	            new \IPS\Helpers\Form\Select( 'discord_channel_approved', $this->discord_channel_approved ?: 0, TRUE, [
	                'options' => $channels
	            ] )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\Select( 'discord_channel_unapproved', $this->discord_channel_unapproved ?: 0, TRUE, [
	                'options' => $channels
	            ] )
	        );
	
	        $form->addHeader( 'discord_notifications' );
	        $form->add(
	            new \IPS\Helpers\Form\YesNo( 'discord_post_topics', $this->discord_post_topics ?: FALSE, FALSE, [
	                'togglesOff' => [
	                    'discord_post_unapproved_topics'
	                ]
	            ] )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\YesNo( 'discord_post_unapproved_topics', $this->discord_post_unapproved_topics ?: FALSE,
	                FALSE, [], NULL, NULL, NULL, 'discord_post_unapproved_topics'
	            )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\YesNo( 'discord_post_posts', $this->discord_post_posts ?: FALSE, FALSE, [
	                'togglesOff' => [
	                    'discord_post_unapproved_posts'
	                ]
	            ] )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\YesNo( 'discord_post_unapproved_posts', $this->discord_post_unapproved_posts ?: FALSE,
	                FALSE, [], NULL, NULL, NULL, 'discord_post_unapproved_posts'
	            )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\TextArea(
	                'discord_topic_format',
	                $this->discord_topic_format ?: '{poster} has just posted a new topic called: "{title}". Read more: {link}',
	                TRUE, [], NULL, NULL, NULL, 'discord_topic_format'
	            )
	        );
	        $form->add(
	            new \IPS\Helpers\Form\TextArea(
	                'discord_post_format',
	                $this->discord_post_format ?: '{poster} has just posted a new post to the topic: "{title}". Read more: {link}',
	                TRUE, [], NULL, NULL, NULL, 'discord_post_format'
	            )
	        );
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