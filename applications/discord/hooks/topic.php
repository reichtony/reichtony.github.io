//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    exit;
}

class discord_hook_topic extends _HOOK_CLASS_
{
    /**
     * Process created object AFTER the object has been created.
     *
     * @param \IPS\Content\Comment|NULL $comment The first comment
     * @param array $values Values from form
     * @return mixed
     */
    protected function processAfterCreate( $comment, $values )
    {
	try
	{
	        $return = call_user_func_array( 'parent::processAfterCreate', func_get_args() );
	
	        if ( $this->container()->discord_post_topics || ( $this->hidden() && $this->container()->discord_post_unapproved_topics ) )
	        {
	            $message = \IPS\discord\Util\Message::fromForumTopic($this);
	            $channel = new \IPS\discord\Api\Channel();
	            $channel->message($message->getMessage(), $message->getChannelId());
	        }
	
	        return $return;
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
     * Syncing to run when unhiding.
     *
     * @param bool $approving If true, is being approved for the first time
     * @param \IPS\Member|NULL|FALSE $member The member doing the action (NULL for currently logged in member, FALSE for no member)
     * @return mixed
     */
    public function onUnhide( $approving, $member )
    {
	try
	{
	        $return = call_user_func_array( 'parent::onUnhide', func_get_args() );
	
	        if ( $approving && $this->container()->discord_post_topics )
	        {
	            $message = \IPS\discord\Util\Message::fromForumTopic($this);
	            $channel = new \IPS\discord\Api\Channel();
	            $channel->message($message->getMessage(), $message->getChannelId());
	        }
	
	        return $return;
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
