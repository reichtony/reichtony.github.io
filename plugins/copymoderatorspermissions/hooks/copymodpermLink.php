//<?php

class hook23 extends _HOOK_CLASS_
{
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit()
	{
		try
		{
			$parent = parent::edit();
	
			\IPS\Output::i()->sidebar['actions']['copy'] = array(
				'title'		=> 'copymodperm_copy',
				'icon'		=> 'file',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=copy&id=' . \IPS\Request::i()->id . '&type=' . \IPS\Request::i()->type ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('copymodperm_copy' ) )
			);
	
			return $parent;
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
	 * Copy
	 *
	 * @return	void
	 */
	protected function copy()
	{
		try
		{
			try
			{
				$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( \IPS\Request::i()->id ), \IPS\Request::i()->type ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C118/2', 404, '' );
			}
	
			$form = new \IPS\Helpers\Form();
			$form->hiddenValues['new_id'] 	= \IPS\Request::i()->id;
			$form->hiddenValues['new_type'] = \IPS\Request::i()->type;
					
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
			{
				$form->add( new \IPS\Helpers\Form\Radio( 'moderators_type', NULL, TRUE, array( 'options' => array( 'g' => 'group', 'm' => 'member' ), 'toggles' => array( 'g' => array( 'moderators_group' ), 'm' => array( 'moderators_member' ) ) ) ) );
			}
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
			{
				$form->add( new \IPS\Helpers\Form\Select( 'moderators_group', NULL, FALSE, array( 'options' => \IPS\Member\Group::groups( TRUE, FALSE ), 'parse' => 'normal' ), function( $member ) use ( $form )
				{
					$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_moderators', array( 'type=? AND id=?', 'g', $member ) )->first();
					if ( $count == 1 )
					{
						throw new \InvalidArgumentException( 'copymodperm_mod_already' );
					}
				}, NULL, NULL, 'moderators_group' ) );
			}
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
			{
				$form->add( new \IPS\Helpers\Form\Member( 'moderators_member', NULL, ( \IPS\Request::i()->moderators_type === 'member' ), array(), function( $member ) use ( $form )
				{
					$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_moderators', array( 'type=? AND id=?', 'm', $member->member_id ) )->first();
					if ( $count == 1 )
					{
						throw new \InvalidArgumentException( 'copymodperm_mod_already' );
					}
				}, NULL, NULL, 'moderators_member' ) );
			}
			
			if ( $values = $form->values() )
			{
				$rowId = NULL;
				
				if ( $values['moderators_type'] === 'g' or !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
				{
					\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_add_group' );
					$rowId = $values['moderators_group'];
				}
				elseif ( $values['moderators_member'] )
				{
					\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_add_member' );
					$rowId = $values['moderators_member']->member_id;
				}
	
				if ( $rowId !== NULL )
				{
					try
					{
						$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", $rowId, $values['moderators_type'] ) )->first();
					}
					catch( \UnderflowException $e )
					{
						$current	= array();
					}
	
					try
					{
						$source = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", \IPS\Request::i()->new_id, \IPS\Request::i()->new_type ) )->first();
					}
					catch( \UnderflowException $e )
					{
						$source	= array();
					}
	
					if ( count( $source ) )
					{
						$current = array(
							'id'		=> $rowId,
							'type'		=> $values['moderators_type'],
							'perms'		=> $source['perms'],
							'updated'	=> time()
						);
						
						\IPS\Db::i()->insert( 'core_moderators', $current );
						
						foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
						{
							$ext->onChange( $current, $values );
						}
						
						\IPS\Session::i()->log( 'acplog__moderator_created', array( ( $values['moderators_type'] == 'g' ? \IPS\Member::loggedIn()->language()->get( "core_group_{$values['moderators_group']}" ) : $values['moderators_member']->name ) => FALSE ) );
	
						unset (\IPS\Data\Store::i()->moderators);
					}
	
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=staff&controller=moderators" ), 'copymodperm_copied' );
				}
			}
	
			\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('add_moderator');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'add_moderator', $form, FALSE );
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