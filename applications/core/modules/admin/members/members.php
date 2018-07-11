<?php
/**
 * @brief		Manage Members
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Mar 2013
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manage Members
 */
class _members extends \IPS\Helpers\CoverPhoto\Controller
{
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( isset( \IPS\Request::i()->searchResult ) )
		{
			\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core' )->message( sprintf( \IPS\Member::loggedIn()->language()->get('search_results_in_nodes'), mb_strtolower( \IPS\Member::loggedIn()->language()->get('members') ) ), 'information' );
		}
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_members', \IPS\Http\Url::internal( 'app=core&module=members&controller=members' ), array( array( 'email<>?', '', '' ) ), 'joined' );
		$table->langPrefix = 'members_';
				
		/* Columns we need */
		$table->include = array( 'photo', 'name', 'email', 'joined', 'group_name', 'ip_address' );
		$table->mainColumn = 'name';
		$table->noSort	= array( 'photo' );
		$table->rowClasses = array( 'email' => array( 'ipsTable_wrap' ), 'group_name' => array( 'ipsTable_wrap' ) );
		
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'joined';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Groups for advanced filter (need to do it this way because array_merge renumbers the result) */
		$groups     = array( '' => 'any_group' );
		$joinFields = array( 'core_members.member_id as member_id' );
		
		foreach ( \IPS\Member\Group::groups() as $k => $v )
		{
			$groups[ $k ] = $v->name;
		}
		
		$fieldsToAdd	= array();
		
		/* Profile fields */
		foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::STAFF ) as $group => $fields )
		{
			/* Header */
			\IPS\Member::loggedIn()->language()->words[ "members_core_pfieldgroups_{$group}" ] = \IPS\Member::loggedIn()->language()->addToStack( "core_pfieldgroups_{$group}", FALSE );
			
			/* Fields */
			foreach ( $fields as $id => $field )
			{
				/* Alias the lang keys */
				$realLangKey = "core_pfield_{$id}";
				$fakeLangKey = "members_field_{$id}";
				\IPS\Member::loggedIn()->language()->words[ $fakeLangKey ] = \IPS\Member::loggedIn()->language()->addToStack( $realLangKey, FALSE );

				/* Work out the object type so we can show the appropriate field */
				$type = get_class( $field );
				$helper = NULL;

				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						$helper = \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
						break;
					case 'IPS\Helpers\Form\Date':
						$helper = \IPS\Helpers\Table\SEARCH_DATE_RANGE;
						break;
					case 'IPS\Helpers\Form\Number':
						$helper = \IPS\Helpers\Table\SEARCH_NUMERIC_TEXT;
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\Radio':
						$options = array( '' => "");
						if( count( $field->options['options'] ) )
						{
							foreach ( $field->options['options'] as $option )
							{
								$options[$option] = $option;
							}
						}

						$helper = array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $options ) );
						break;
				}
								
				if ( $helper )
				{
					$fieldsToAdd[ "field_{$id}" ] = $helper;
				}

				/* Set fields we need for the table joins below */
				$joinFields[] = "field_{$id}";
			}
		}

		/* Joins */
		$table->joins = array(
			array(
				'select' => 'v.vid, v.coppa_user, v.lost_pass, v.forgot_security, v.new_reg, v.email_chg, v.user_verified, v.spam_flag, v.reg_cancelled',
				'from' => array( 'core_validating', 'v' ),
				'where' => 'v.member_id=core_members.member_id AND v.lost_pass != 1 AND v.forgot_security != 1' ),
			array(
				'select' => implode( ',', $joinFields ),
				'from' => array( 'core_pfields_content', 'p' ),
				'where' => 'p.member_id=core_members.member_id' ),
			array(
				'select' => 'm.row_id',
				'from' => array( 'core_admin_permission_rows', 'm' ),
				'where' => "m.row_id=core_members.member_id AND m.row_id_type='member'" ),
			array(
				'select' => 'g.row_id',
				'from' => array( 'core_admin_permission_rows', 'g' ),
				'where' => array( 'g.row_id', \IPS\Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) )
		);
		
		/* Makes query less efficient */
		if ( $table->filter !== 'members_filter_administrators' )
		{
			unset( $table->joins[2] );
			unset( $table->joins[3] );
		}
		
		/* Search */
		$table->quickSearch = 'name';
		$table->advancedSearch = array(
			'name'				=> \IPS\Helpers\Table\SEARCH_QUERY_TEXT,
			'member_id'			=> array( \IPS\Helpers\Table\SEARCH_NUMERIC, array(), function( $v ){
				switch ( $v[0] )
				{
					case 'gt':
						return array( "core_members.member_id>?", (float) $v[1] );
					case 'lt':
						return array( "core_members.member_id<?", (float) $v[1] );
					case 'eq':
						return array( "core_members.member_id=?", (float) $v[1] );
				}
			} ),
			'email'				=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'ip_address'		=> array( \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT, array(), function( $val )
			{
				return array( "core_members.ip_address LIKE ?", '%' . $val . '%' );
			} ),
			'member_group_id'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $groups ), function( $val )
			{
				return array( '( member_group_id=? OR FIND_IN_SET( ?, mgroup_others ) )', $val, $val );
			} ),
			'joined'			=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'member_last_post'			=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'last_visit'		=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'member_posts'				=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			);
		
		if( count( $fieldsToAdd ) )
		{
			$table->advancedSearch[ "core_pfieldgroups_{$group}" ] = \IPS\Helpers\Table\HEADER;

			$table->advancedSearch	= array_merge( $table->advancedSearch, $fieldsToAdd );
		}
						
		/* Filters */
		$table->filters = array(
			'members_filter_banned'			=> 'temp_ban<>0',
			'members_filter_spam'			=> \IPS\Db::i()->bitwiseWhere( \IPS\Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ),
			'members_filter_validating'		=> '( v.lost_pass=0 AND v.forgot_security=0 AND v.vid IS NOT NULL )',
			'members_filter_administrators'	=> '( m.row_id IS NOT NULL OR g.row_id IS NOT NULL )'
		);
		
		if ( \IPS\Db::i()->select( 'COUNT(*)', 'core_members', array( "email <> '' and name=''" ) )->first() )
		{
			$table->filters['members_filtered_reserved'] = "(name='')";
		}
		
		if( \IPS\Settings::i()->ipb_bruteforce_attempts )
		{
			/* We do this so we can put the locked filter at the 'end' of the buttons - array_unshift does not give us a simple method to retain keys */
			$table->filters = array( 'members_filter_locked' => '( failed_login_count>=' . (int) \IPS\Settings::i()->ipb_bruteforce_attempts . ' OR failed_mfa_attempts>=' . (int) \IPS\Settings::i()->security_questions_tries . ')' ) + $table->filters;
		}
		
		/* Custom parsers */
		$table->parsers = array(
			'email'				=> function( $val, $row )
			{
				if ( $row['vid'] )
				{
					return \IPS\Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberEmailCell( \IPS\Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberValidatingCell( $val, \IPS\Member::constructFromData( $row )->validatingDescription( $row ) ) );
				}
				else
				{
					return \IPS\Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberEmailCell( htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) );
				}				
			},
			'photo'				=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate( 'global', 'core' )->userPhoto( \IPS\Member::constructFromData( $row ), 'tiny' );
			},
			'joined'			=> function( $val, $row )
			{
				return \IPS\DateTime::ts( $val )->localeDate();
			},
			'group_name'	=> function( $val, $row )
			{
				$secondary = \IPS\Member::constructFromData( $row )->groups;
				
				foreach( $secondary as $k => $v )
				{
					if( $v == $row['member_group_id'] or $v == 0 )
					{
						unset( $secondary[ $k ] );
						continue;
					}
					
					$secondary[ $k ] = \IPS\Member\Group::load( $v );
				}

				return \IPS\Theme::i()->getTemplate( 'members', 'core', 'admin' )->groupCell( \IPS\Member\Group::load( $row['member_group_id'] ), $secondary );
			},
			'ip_address'	=> function( $val, $row )
			{
				if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
				{
					return "<a href='" . \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
				}
				return $val;
			},
			'member_last_post' => function( $val, $row )
			{
				return ( $val ) ? \IPS\DateTime::ts( $val )->localeDate() : \IPS\Member::loggedIn()->language()->addToStack( 'never' );
			},
			'last_visit' => function( $val, $row )
			{
				return ( $val ) ? \IPS\DateTime::ts( $val )->localeDate() : \IPS\Member::loggedIn()->language()->addToStack( 'never' );
			},
			'name' => function( $val, $row )
			{
				if ( $val )
				{
					$member = \IPS\Member::constructFromData( $row );
	
					if ( $banned = $member->isBanned() )
					{
						if ( $banned instanceof \IPS\DateTime )
						{
							$title = \IPS\Member::loggedIn()->language()->addToStack( 'suspended_until', FALSE, array( 'sprintf' => array( $banned->localeDate() ) ) );
						}
						else
						{
							$title = \IPS\Member::loggedIn()->language()->addToStack( 'banned' );
						}
						return "<a href='" . \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=view&id=' ) . $row['member_id'] .  "'>" . htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) . "</a> &nbsp; <span class='ipsBadge ipsBadge_negative'>" . $title ."</span> ";
					}
					else
					{
						return "<a href='" . \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=view&id=' ) . $row['member_id'] . "'>" . htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE ) . "</a>";
					}
				}
				else
				{
					return \IPS\Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberReserved( \IPS\Member::constructFromData( $row ) );
				}

			},
		);
		
		/* Specify the buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add' ) and \IPS\Login\Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
		{
			\IPS\Output::i()->sidebar['actions']['add'] = array(
				'primary'	=> true,
				'icon'		=> 'plus',
				'title'		=> 'members_add',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=add' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('members_add') )
			);
		}
		$table->rowButtons = function( $row )
		{
			$member = \IPS\Member::constructFromData( $row );
			
			$return = array();
			
			if ( isset( $row['vid'] ) and $row['vid'] and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_validating' ) )
			{
				$return['approve'] = array(
					'icon'		=> 'check-circle',
					'title'		=> 'approve',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=approve&id=' ) . $member->member_id,
					'id'		=> "{$member->member_id}-approve",
					'data'		=> array(
						'bubble' 		=> '',
					)
				);
				$return['ban'] = array(
					'icon'		=> 'times',
					'title'		=> 'ban',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=ban&id=' ) . $member->member_id . '&permban=1',
					'id'		=> "{$member->member_id}-ban",
					'data'		=> array(
						'bubble'		=> '',
					)
				);
				
				if ( !$row['user_verified'] )
				{
					$return['resend_email'] = array(
						'icon'		=> 'envelope-o',
						'title'		=> 'resend_validation_email',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=resendEmail&id=' ) . $member->member_id,
						'data' 		=> array( 'doajax' => '' ),
						'id'		=> "{$member->member_id}-resend",
					);
				}
			}
			
			if (
				\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_locked' ) and
				(
					\IPS\Settings::i()->ipb_bruteforce_attempts and $row['failed_login_count'] >= (int) \IPS\Settings::i()->ipb_bruteforce_attempts
					or
					\IPS\Settings::i()->security_questions_tries and $row['failed_mfa_attempts'] >= (int) \IPS\Settings::i()->security_questions_tries
				)
			) {
				$return['unlock'] = array(
					'icon'		=> 'unlock',
					'title'		=> 'unlock',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=unlock&id=' ) . $member->member_id,
					'data'		=> array( 'bubble' => '' )
				);
			}
			
			$return['view'] = array(
				'icon'		=> 'search',
				'title'		=> 'view',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=view&id=' ) . $member->member_id,
			);
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) )
			{
				if ( $member->member_id != \IPS\Member::loggedIn()->member_id )
				{
					$return['flag'] = array(
						'icon'		=> 'flag',
						'title'		=> 'spam_flag',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=spam&id=' ) . $member->member_id . '&status=1',
						'hidden'	=> $member->members_bitoptions['bw_is_spammer'],
						'id'		=> "{$member->member_id}-flag",
						'data'		=> array(
							'confirm'		=> '',
						)
					);
					$return['unflag'] = array(
						'icon'		=> 'flag ipsType_spammer',
						'title'		=> 'spam_unflag',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=spam&id=' ) . $member->member_id . '&status=0',
						'hidden'	=> !$member->members_bitoptions['bw_is_spammer'],
						'id'		=> "{$member->member_id}-unflag",
						'data'		=> array(
							'confirm'		=> '',
						)
					);
				}
			}
						
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) or !$member->isAdmin() ) and $member->member_id != \IPS\Member::loggedIn()->member_id )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=delete&id=' ) . $member->member_id,
					'data'      => array(
						'delete' => '',
						'delete-message' => \IPS\Member::loggedIn()->language()->addToStack('member_delete_message', FALSE, array( 'sprintf' => array( htmlentities( $member->name, ENT_DISALLOWED, 'UTF-8', FALSE ) ) ) ),
						'delete-warning' => \IPS\Member::loggedIn()->language()->addToStack( 'member_delete_confirm_desc' ),
						'noajax' => ''
					)
				);
			}
			
			return $return;
		};
		
		/* Display */
		if( \IPS\Request::i()->advanced_search_submitted OR \IPS\Request::i()->quicksearch )
		{
			$link = ' <a href="' . \IPS\Http\Url::internal( 'app=core&module=members&controller=members' ) . '">' . \IPS\Member::loggedIn()->language()->addToStack('member_view_full_list') . '</a>';
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) OR \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
			{
				$query = array(
					'members_name'				=> \IPS\Request::i()->quicksearch ?: \IPS\Request::i()->members_name,
                    //'members_member_id'			=> ( \IPS\Request::i()->members_member_id[0] != 'any' ) ? \IPS\Request::i()->members_member_id[1] : 0,
                    'members_member_id'			=> \IPS\Request::i()->members_member_id,
					'members_email'				=> \IPS\Request::i()->members_email,
					'members_ip_address'		=> \IPS\Request::i()->members_ip_address,
					'members_member_group_id'	=> \IPS\Request::i()->members_member_group_id,
					'members_joined'			=> \IPS\Request::i()->members_joined,
					'members_last_post'			=> \IPS\Request::i()->members_member_last_post,
					'members_last_visit'		=> \IPS\Request::i()->members_last_visit,
					'members_posts'				=> \IPS\Request::i()->members_member_posts,
					'filter'					=> \IPS\Request::i()->filter,
				);
				
				foreach ( \IPS\Request::i() as $k => $v )
				{
					/* Add profile fields */
					if ( mb_substr( $k, 0, 14 ) === 'members_field_' )
					{
						$query[ $k ] = $v;
						
						/* And also add them as parsers so they display properly */
						$table->parsers[ str_replace( 'members_', '', $k ) ] = function( $val, $row ) use ( $k )
						{
							try
							{
								return \IPS\core\ProfileFields\Field::load( str_replace( 'members_field_', '', $k ) )->displayValue( $val );
							}
							catch ( \Exception $e )
							{
								return NULL;
							}
						};
					}
				}
				
				if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) )
				{
					$link .= ' &mdash; <a href="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=massManage&action=prune" )->setQueryString( $query ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'member_search_prune' ) . '</a>';
				}
				
				if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
				{
					$link .= ' &mdash; <a href="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=massManage&action=move" )->setQueryString( $query ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'member_search_move' ) . '</a>';
				}
			}
			
			$table->extraHtml = \IPS\Theme::i()->getTemplate( 'global', 'core' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'member_search_results' ) . $link, 'info', NULL, FALSE );
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add' ) and \IPS\Login\Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
		{
			\IPS\Output::i()->sidebar['actions']['import'] = array(
				'icon'		=> 'cloud-upload',
				'title'		=> 'members_import',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=import&_new=1' )
			);
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_export' ) )
		{
			\IPS\Output::i()->sidebar['actions']['export'] = array(
				'icon'		=> 'cloud-download',
				'title'		=> 'members_export',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=export&_new=1' )
			);
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('members');
		\IPS\Output::i()->output	.= (string) $table;
	}

	/**
	 * Prune members
	 *
	 * @return	void
	 */
	public function massManage()
	{
		switch( \IPS\Request::i()->action )
		{
			case 'prune':
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_delete' );
			break;
			
			case 'move':
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
			break;
		}
		
		$where = array();
		
		if ( \IPS\Request::i()->members_name )
		{
			if ( is_array( \IPS\Request::i()->members_name ) )
			{
				if ( \IPS\Request::i()->members_name[1] )
				{
					switch( \IPS\Request::i()->members_name[0] )
					{
						case 'c':
							$operator = "LIKE CONCAT( '%', ?, '%' )";
						break;
						
						case 'bw':
							$operator = "LIKE CONCAT( ?, '%' )";
						break;
						
						case 'eq':
							$operator = "=?";
						break;
					}
					$where[] = array( 'name ' . $operator , \IPS\Request::i()->members_name[1] );
				}
			}
			else
			{
				$where[] = array( "name LIKE CONCAT( '%', ?, '%' )", \IPS\Request::i()->members_name );
			}
		}
		
		if ( isset( \IPS\Request::i()->members_member_id ) AND isset( \IPS\Request::i()->members_member_id[1] ) AND \IPS\Request::i()->members_member_id[1] )
		{
			switch ( \IPS\Request::i()->members_member_id[0] )
			{
				case 'gt':
					$where[] = array( "core_members.member_id>?", \IPS\Request::i()->members_member_id[1] );
				break;
				case 'lt':
					$where[] = array( "core_members.member_id<?", \IPS\Request::i()->members_member_id[1] );
				break;
				case 'eq':
					$where[] = array( "core_members.member_id=?", \IPS\Request::i()->members_member_id[1] );
				break;
			}
		}
		
		if ( \IPS\Request::i()->members_email )
		{
			$where[] = array( "email LIKE CONCAT( '%', ?, '%' )", \IPS\Request::i()->members_email );
		}
		
		if ( \IPS\Request::i()->members_ip_address )
		{
			$where[] = array( "core_members.ip_address LIKE CONCAT( '%', ?, '%' )", (string) \IPS\Request::i()->members_ip_address );
		}
		
		if ( \IPS\Request::i()->members_member_group_id )
		{
			$adminGroups	= array_keys( \IPS\Member::administrators()['g'] );
			
			/* We do a generic permissions check here, then later on when the process is actually running, we check each individual one to make sure we don't do something we shouldn't do */
			if
			(
				(
					(
						\IPS\Request::i()->action === 'prune' AND \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' )
					)
					OR
					(
						\IPS\Request::i()->action === 'move' AND
						(
							\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) OR
							\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin1' ) OR
							\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' )
						)
					)
				)
				OR
				!in_array( \IPS\Request::i()->members_member_group_id, $adminGroups )
			)
			{
				$where[] = array( '( member_group_id=? OR FIND_IN_SET( ?, mgroup_others ) )', (int) \IPS\Request::i()->members_member_group_id,(int) \IPS\Request::i()->members_member_group_id );
			}
		}
		
		foreach ( array( 'joined', 'last_post', 'last_visit' ) as $k )
		{
			$requestKey = "members_{$k}";
			if ( $k === 'last_post' )
			{
				$k = 'member_last_post';
			}
			
			$request = \IPS\Request::i()->$requestKey;
			
			if ( $request['start'] or $request['end'] )
			{
				$start = NULL;
				$end = NULL;
				if ( $request['start'] )
				{
					$start = new \IPS\DateTime( \IPS\Helpers\Form\Date::_convertDateFormat( $request['start'] ) );
					$start = $start->setTime( 0, 0, 0 );
				}
				if ( $request['end'] )
				{
					$end = new \IPS\DateTime( \IPS\Helpers\Form\Date::_convertDateFormat( $request['end'] ) );
					$end = $end->setTime( 23, 59, 59 );
				}
				
				if ( $start and $end )
				{
					$where[] = array( "{$k} BETWEEN ? AND ?", $start->getTimestamp(), $end->getTimestamp() );
				}
				elseif ( $start )
				{
					$where[] = array( "{$k}>?", $start->getTimestamp() );
				}
				elseif ( $end )
				{
					$where[] = array( "{$k}<?", $end->getTimestamp() );
				}
			}
		}

		if ( ( isset( \IPS\Request::i()->members_posts[0] ) AND \IPS\Request::i()->members_posts[0] != 'any' ) AND isset( \IPS\Request::i()->members_posts[1] ) )
		{
			switch( \IPS\Request::i()->members_posts[0] )
			{
				case 'gt':
					$operator = '>';
				break;
				
				case 'lt':
					$operator = '<';
				break;
				
				case 'eq':
					$operator = '=';
				break;
			}
			$where[] = array( 'member_posts'.$operator.'?', (int) \IPS\Request::i()->members_posts[1] );
		}

		$joinValidating = $joinAdmins = FALSE;

		if( isset( \IPS\Request::i()->filter ) )
		{
			switch ( \IPS\Request::i()->filter )
			{
				case 'members_filter_banned':
					$where[] = array( 'temp_ban<>0' );
					break;
				case 'members_filter_locked':
					$where[] = array( 'failed_login_count>=' . (int) \IPS\Settings::i()->ipb_bruteforce_attempts );
					break;
				case 'members_filter_spam':
					$where[] = array( \IPS\Db::i()->bitwiseWhere( \IPS\Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) );
					break;
				case 'members_filter_validating':
					$where[] = array( '( v.lost_pass=0 AND v.forgot_security=0 AND v.vid IS NOT NULL )' );
					$joinValidating	= TRUE;
					break;
				case 'members_filter_administrators':
					$where[] = array( '( m.row_id IS NOT NULL OR g.row_id IS NOT NULL )' );
					$joinAdmins		= TRUE;
					break;
			}
		}
		
		foreach ( \IPS\Request::i() as $k => $v )
		{
			if ( mb_substr( $k, 0, 14 ) === 'members_field_' )
			{
				try
				{
					/* Only include these for a non-empty value */
					if ( !empty( $v ) )
					{
						$field = \IPS\core\ProfileFields\Field::load( mb_substr( $k, 14 ) );
						switch ( $field->type )
						{
							case 'Text':
							case 'Tel':
							case 'Editor':
							case 'TextArea':
							case 'Url':
								$where[] = array( "field_{$field->id} LIKE CONCAT( '%', ?, '%' )", $v );
								break;
							case 'Date':
								if ( isset( $v['start'] ) and $v['start'] )
								{
									$where[] = array( "field_{$field->id}>?", ( new \IPS\DateTime( $v['start'] ) )->getTimestamp() );
								}
								if ( isset( $v['end'] ) and $v['end'] )
								{
									$where[] = array( "field_{$field->id}<?", ( new \IPS\DateTime( $v['end'] ) )->setTime( 23, 59, 59 )->getTimestamp() );
								}
								break;
							case 'Number':
								switch ( $v[0] )
								{
									case 'gt':
										$where[] = array( "field_{$field->id}>?", intval( $v[1] ) );
										break;
									case 'lt':
										$where[] = array( "field_{$field->id}<?", intval( $v[1] ) );
										break;
									case 'eq':
										$where[] = array( "field_{$field->id}=?", intval( $v[1] ) );
										break;
								}
								break;
							case 'Select':
							case 'Radio':
								$where[] = array( "field_{$field->id}=?", $v );
								break;
						}
					}
				}
				catch ( \OutOfRangeException $e ) { }
			}
		}

		if ( !count( $where ) )
		{
			if ( \IPS\Request::i()->action === 'prune' )
			{
				\IPS\Output::i()->error( 'member_prune_no_results', '2C114/E', 404, '' );
			}
			else
			{
				\IPS\Output::i()->error( 'member_move_no_results', '2C114/G', 404, '' );
			}
		}
		
		/* Unset any previous session data */
		$_SESSION['members_manage_where']	= $where;
		$_SESSION['members_manage_action']	= \IPS\Request::i()->action;

		if ( \IPS\Request::i()->action === 'prune' )
		{
			$count = \IPS\Db::i()->select( 'COUNT(*) AS count', 'core_members', $where )
				->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' );

			if( $joinValidating )
			{
				$count = $count->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id');
			}

			if( $joinAdmins )
			{
				$count = $count->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
					->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', \IPS\Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );
			}

            \IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'members' )->confirmMassAction( $count->first(), 'prune' );
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'member_prune_confirm' );
		}
		else
		{
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\Select( 'move_to_group', NULL, TRUE, array( 'options'	=> \IPS\Member\Group::groups( TRUE, FALSE ), 'parse' => 'normal' ) ) );
			
			if ( $values = $form->values() )
			{
				$group = \IPS\Member\Group::load( $values['move_to_group'] );
				
				if ( in_array( $group->g_id, array_keys( \IPS\Member::administrators()['g'] ) ) AND !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ) )
				{
					\IPS\Output::i()->error( 'member_move_admin_group', '2C114/H', 403, '' );
				}
				
				$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_members', $_SESSION['members_manage_where'] )
					->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' );

				if( $joinValidating )
				{
					$count = $count->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id');
				}

				if( $joinAdmins )
				{
					$count = $count->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
						->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', \IPS\Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );
				}

				$_SESSION['members_manage_group']	= $group->g_id;

				/* We need to remember the group we are moving *from*, if that is what we are doing */
				if( isset( \IPS\Request::i()->members_member_group_id ) )
				{
					$_SESSION['members_manage_old_group']	= \IPS\Request::i()->members_member_group_id;
				}

				\IPS\Output::i()->output			= \IPS\Theme::i()->getTemplate( 'members' )->confirmMassAction( $count->first(), 'move', $group );
				\IPS\Output::i()->title				= \IPS\Member::loggedIn()->language()->addToStack( 'member_move_confirm' );
			}
			else
			{
				\IPS\Output::i()->output	= $form;
				\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'member_search_move' );
			}
		}
	}
	
	/**
	 * Move Members
	 *
	 * @return	void
	 */
	public function doMove()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );

		/* Don't queue the task if the session variables are null (i.e. the page has been reloaded) */
		if( isset( $_SESSION['members_manage_where'] ) AND $_SESSION['members_manage_where'] !== NULL
			AND isset( $_SESSION['members_manage_action'] ) AND $_SESSION['members_manage_action'] !== NULL )
		{
			\IPS\Task::queue( 'core', 'MoveMembers', array( 'where' => $_SESSION['members_manage_where'], 'group' => $_SESSION['members_manage_group'], 'oldGroup' => ( isset( $_SESSION['members_manage_old_group'] ) ) ? $_SESSION['members_manage_old_group'] : NULL, 'by' => \IPS\Member::loggedIn()->member_id ), 2 );

			$_SESSION['members_manage_where']		= NULL;
			$_SESSION['members_manage_action']		= NULL;
			$_SESSION['members_manage_old_group']	= NULL;
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members" ), 'members_queued_for_moving' );
		}

		\IPS\Output::i()->error( 'members_manage_error', '2C114/K', 404, '' );
	}

	/**
	 * Prune members
	 *
	 * @return	void
	 */
	public function doPrune()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_delete' );

		/* Don't queue the task if the session variables are null (i.e. the page has been reloaded) */
		if( isset( $_SESSION['members_manage_where'] ) AND $_SESSION['members_manage_where'] !== NULL
			AND isset( $_SESSION['members_manage_action'] ) AND $_SESSION['members_manage_action'] !== NULL )
		{
			\IPS\Task::queue( 'core', 'PruneMembers', array( 'where' => $_SESSION['members_manage_where'] ), 2 );

			$_SESSION['members_manage_where']	= NULL;
			$_SESSION['members_manage_action']	= NULL;
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members" ), 'members_queued_for_pruning' );
		}

		\IPS\Output::i()->error( 'members_manage_error', '2C114/L', 404, '' );
	}

	/**
	 * Add Member
	 *
	 * @return	void
	 */
	public function add()
	{
		/* Check permissions */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_add' );
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'username', NULL, TRUE, array( 'accountUsername' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array( 'showMeter' => \IPS\Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE, array( 'maxLength' => 150, 'accountEmail' => TRUE, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'group', \IPS\Settings::i()->member_group, TRUE, array( 'options' => \IPS\Member\Group::groups( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add_admin' ), FALSE ), 'parse' => 'normal' ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'secondary_groups', array(), FALSE, array( 'options' => \IPS\Member\Group::groups( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_add_admin' ), FALSE ), 'multiple' => TRUE, 'parse' => 'normal' ) ) );
		foreach ( \IPS\Lang::languages() as $lang )
		{
			$languages[ $lang->id ] = $lang->title;
		}
		$form->add( new \IPS\Helpers\Form\Select( 'language', NULL, TRUE, array( 'options' => $languages ) ) );
		
		$themes = array( 0 => 'skin_none' );
		foreach( \IPS\Theme::themes() as $theme )
		{
			$themes[ $theme->id ] = $theme->_title;
		}
		
		$form->add( new \IPS\Helpers\Form\Select( 'skin', NULL, TRUE, array( 'options' => $themes ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'member_add_confirmemail', TRUE ) );
		
		if( \IPS\Settings::i()->use_coppa )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'member_add_coppa_user', FALSE, FALSE, array(), NULL, NULL, NULL, 'member_add_coppa_user' ) );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$member = new \IPS\Member;
			$member->name				= $values['username'];
			$member->email				= $values['email_address'];
			$member->member_group_id	= $values['group'];
			$member->mgroup_others		= implode( ',', $values['secondary_groups'] );
			$member->language			= $values['language'];
			$member->skin				= $values['skin'];
			$member->setLocalPassword( $values['password'] );
			
			if( \IPS\Settings::i()->use_coppa )
			{
				$member->members_bitoptions['coppa_user'] = ( $values['member_add_coppa_user'] ) ?: FALSE;
			}
			
			$member->save();
			$member->logHistory( 'core', 'account', array( 'type' => 'register_admin' ) );
			
			/* Reset statistics */
			\IPS\Widget::deleteCaches( 'stats', 'core' );
			
			\IPS\Session::i()->log( 'acplog__members_created', array( $member->name => FALSE ) );
				
			if ( $values['member_add_confirmemail'] )
			{
				\IPS\Email::buildFromTemplate( 'core', 'admin_reg', array( $member, $values['password'] ), \IPS\Email::TYPE_TRANSACTIONAL )->send( $member );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $member->member_id ), 'saved' );
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack( 'members_add' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'members_add', $form, FALSE );
	}
	
	/**
	 * View Member
	 *
	 * @return	void
	 */
	public function view()
	{
		/* Load the member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/O', 404, '' );
		}
		
		/* Get the available tabs */
		$extensions = array();
		foreach( \IPS\Application::allExtensions( 'core', 'MemberACPProfileTabs', TRUE, 'core', 'Main', FALSE ) AS $key => $ext )
		{
			$class = new $ext( $member );
			if ( $class->canView() )
			{
				$extensions[ $key ] = $ext;
			}
		}
		
		/* What's our active tab? */
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $extensions ) ) ? \IPS\Request::i()->tab : 'core_Main';
		$classname = $extensions[ $activeTab ];
		$tab = new $classname( $member );
						
		/* Output */
		\IPS\Output::i()->title = $member->name ? $member->name : \IPS\Member::loggedIn()->language()->addToStack('members_name_missing_as_reserved');
		if ( \IPS\Request::i()->isAjax() )
		{
			if ( isset( \IPS\Request::i()->blockKey ) )
			{
				$exploded = explode( '_', \IPS\Request::i()->blockKey );
				$class = 'IPS\\' . $exploded[0] . '\\extensions\\core\\MemberACPProfileBlocks\\' . $exploded[1];
				if ( class_exists( $class ) )
				{
					$block = new $class( $member );
					\IPS\Output::i()->output = $block->tabOutput( \IPS\Request::i()->block[ \IPS\Request::i()->blockKey ] );
				}
				else
				{
					\IPS\Output::i()->output = $class;
				}
			}
			else
			{
				\IPS\Output::i()->output = $tab->output();
			}
		}
		else
		{
			$history = NULL;
			$historyFilters = array();
			if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_history' ) )
			{
				$history = new \IPS\Member\History( $member->acpUrl()->setQueryString( array( 'do' => 'history' ) ), array( array( 'log_member=?', $member->member_id ) ), TRUE, FALSE, TRUE );
				$history->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyTable' );
				$history->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyRows' );
				$history->limit = 20;
				
				$historyFilters = iterator_to_array( \IPS\Db::i()->select( 'log_app, log_type, COUNT(*) AS count', 'core_member_history', array( 'log_member=?', $member->member_id ), 'log_app, log_type', NULL, array( 'log_app', 'log_type' ) ) );

				$history = \IPS\Theme::i()->getTemplate('memberprofile')->history( $member, $history, $historyFilters );
			}
						
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'members/view.css', 'core', 'admin' ) );
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_members.js', 'core' ) );
			\IPS\Output::i()->hiddenElements = array('acpHeader');
			\IPS\Output::i()->bodyClasses = array('acpNoPadding');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('memberprofile')->mainTemplate( $member, $extensions, $activeTab, $tab->output(), $history );
		}
	}
	
	/**
	 * Edit window for a block
	 *
	 * @return	void
	 */
	public function editBlock()
	{
		/* Load the member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/S', 404, '' );
		}
		
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		if ( $member->isAdmin() )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
		}
		
		/* Display */
		$class = \IPS\Request::i()->block;
		$object = new $class( $member );
		\IPS\Output::i()->output = $object->edit();
	}
	
	/**
	 * View Member: Lazy-Loading Block
	 *
	 * @return	void
	 */
	public function lazyBlock()
	{
		/* Load the member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( !$member->member_id )
		{
			\IPS\Output::i()->output = '';
		}
		
		$class = \IPS\Request::i()->block;
		$object = new $class( $member );
		
		\IPS\Output::i()->output = $object->lazyOutput();
	}
	
	/**
	 * Edit Member
	 *
	 * @return	void
	 */
	public function edit()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );

		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/1', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
		}

		/* Get extensions */
		$extensions = \IPS\Application::allExtensions( 'core', 'MemberForm', FALSE, 'core', 'Preferences', TRUE );
		
		/* Build form */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_members.js', 'core', 'admin' ) );
		\IPS\Output::i()->sidebar['actions'] = array();
		$form = new \IPS\Helpers\Form;
		
		foreach ( $extensions as $k => $class )
		{
			$form->addTab( 'member__' . $k );
			$class->process( $form, $member );
			
			if ( method_exists( $class, 'actionButtons' ) )
			{
				\IPS\Output::i()->sidebar['actions'] = array_merge( \IPS\Output::i()->sidebar['actions'], $class->actionButtons( $member ) );
			}
		}
				
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			foreach ( $extensions as $class )
			{
				$class->save( $values, $member );
			}
			$member->save();
			
			\IPS\Session::i()->log( 'acplog__members_edited_prefs', array( $member->name => FALSE ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $member->member_id ), 'saved' );
		}
		
		/* Display */	
		\IPS\Output::i()->title		= $member->name;
		\IPS\Output::i()->output	= $form;
	}
	
	/**
	 * Change Password
	 *
	 * @return	void
	 */
	protected function password()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/2', 404, '' );
		}
		
		/* Show Form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Password( 'password', '', TRUE, array( 'confirm' => 'password_confirm', 'showMeter' => \IPS\Settings::i()->password_strength_meter, 'minimumStrength' => 1, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password_confirm', '', TRUE, array( 'bypassProfanity' => TRUE ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			require_once \IPS\ROOT_PATH . "/system/3rd_party/phpass/phpass.php";
			$phpass = new \PasswordStrength();
			$strength = $phpass->classify( $values['password'] );

			if( !isset( \IPS\Request::i()->proceed ) and ( \IPS\Settings::i()->password_strength_meter_enforce and $strength < \IPS\Settings::i()->password_strength_option ) )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'err_acp_password_strength', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'strength_' . $strength ), \IPS\Member::loggedIn()->language()->addToStack( 'strength_' . \IPS\Settings::i()->password_strength_option ) ) ) );
				$form->hiddenValues['proceed'] = TRUE;
				$form->actionButtons = array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( 'continue', 'submit', null, 'ipsButton ipsButton_primary', array( 'tabindex' => '2', 'accesskey' => 's' ) ) );
			}
			else
			{
				$changed = $member->changePassword( $values['password'] );
				if ( !$changed and \IPS\Login\Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
				{
					$member->setLocalPassword( $values['password'] );
					$member->save();
				}
				$member->invalidateSessionsAndLogins( TRUE, \IPS\Session::i()->id );

				\IPS\Session::i()->log( 'acplog__members_edited_password', array( $member->name => FALSE ) );

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
		}
		
		/* Display */
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack('change_password_for', FALSE, array( 'sprintf' => array( $member->name ) ) );
		\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'password', $form, FALSE );
	}
	
	
	/**
	 * Change Display Name
	 *
	 * @return	void
	 */
	protected function name()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/U', 404, '' );
		}
		
		/* Did we change it? */
		$error = NULL;
		if ( \IPS\Request::i()->name and \IPS\Request::i()->name != $member->name )
		{
			/* Validate */
			$error = NULL;
			if ( mb_strlen( \IPS\Request::i()->name ) < \IPS\Settings::i()->min_user_name_length )
			{
				$error = \IPS\Member::loggedIn()->language()->addToStack( 'form_minlength', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->min_user_name_length ) ) );
			}
			elseif ( mb_strlen( \IPS\Request::i()->name ) > \IPS\Settings::i()->max_user_name_length )
			{
				$error = \IPS\Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->max_user_name_length ) ) );
			}
			elseif ( \IPS\Settings::i()->username_characters and !preg_match( '/^[' . str_replace( '\-', '-', preg_quote( \IPS\Settings::i()->username_characters, '/' ) ) . ']*$/iu', \IPS\Request::i()->name ) )
			{
				$error = \IPS\Member::loggedIn()->language()->addToStack('form_bad_value');
			}
			elseif ( $message = \IPS\Login::usernameIsInUse( \IPS\Request::i()->name, $member ) )
			{
				$error = $message;
			}
			else
			{
				foreach( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
				{
					if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', \IPS\Request::i()->name ) )
					{
						$error = \IPS\Member::loggedIn()->language()->addToStack('form_name_banned');
						break;
					}
				}
			}
			if ( $error )
			{
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( $error, 403 );
				}
				else
				{
					\IPS\Output::i()->error( $error, '2C114/V', 403, '' );
				}
			}
			
			/* Change */
			$member->logHistory( 'core', 'display_name', array( 'old' => $member->name, 'new' => \IPS\Request::i()->name, 'by' => 'manual' ) );
			foreach ( \IPS\Login::methods() as $method )
			{
				try
				{
					$method->changeUsername( $member, $member->name, \IPS\Request::i()->name );
				}
				catch( \BadMethodCallException $e ){}
			}
			$member->name = \IPS\Request::i()->name;
			$member->save();
			\IPS\Widget::deleteCaches();
			\IPS\Session::i()->log( 'acplog__members_edited_name', array( $member->name => FALSE ) );
		}
		
		/* OK */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( true );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
	
	/**
	 * Change Email
	 *
	 * @return	void
	 */
	protected function email()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/W', 404, '' );
		}
		
		/* Did we change it? */
		if ( \IPS\Request::i()->email and \IPS\Request::i()->email != $member->email )
		{
			/* Validate */
			$error = NULL;
			if ( filter_var( \IPS\Request::i()->email, FILTER_VALIDATE_EMAIL ) === FALSE )
			{
				$error = \IPS\Member::loggedIn()->language()->addToStack( 'form_email_bad' );
			}
			elseif ( $message = \IPS\Login::emailIsInUse( \IPS\Request::i()->email, $member ) )
			{
				$error = $message;
			}
			else
			{
				foreach ( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
	 			{	 			
		 			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', \IPS\Request::i()->email ) )
		 			{
		 				$error = \IPS\Member::loggedIn()->language()->addToStack( 'form_email_banned' );
		 			}
	 			}
	 			if ( \IPS\Settings::i()->allowed_reg_email !== '' AND $allowedEmailDomains = explode( ',', \IPS\Settings::i()->allowed_reg_email )  )
				{
					$matched = FALSE;
					foreach ( $allowedEmailDomains AS $domain )
					{
						if( \mb_stripos( \IPS\Request::i()->email,  "@" . $domain ) !== FALSE )
						{
							$matched = TRUE;
						}
					}
					if ( count( $allowedEmailDomains ) AND !$matched )
					{
						$error = \IPS\Member::loggedIn()->language()->addToStack( 'form_email_banned' );
					}
				}
			}
			if ( $error )
			{
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( $error, 403 );
				}
				else
				{
					\IPS\Output::i()->error( $error, '2C114/X', 403, '' );
				}
			}
			
			/* Change */
			$oldEmail = $member->email;
			$member->email = \IPS\Request::i()->email;
			$member->save();
			foreach ( \IPS\Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( $member, $oldEmail, \IPS\Request::i()->email );
				}
				catch( \BadMethodCallException $e ) {}
			}
			$member->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => \IPS\Request::i()->email, 'by' => 'manual' ) );
			$member->memberSync( 'onEmailChange', array( \IPS\Request::i()->email, $oldEmail ) );
			$member->invalidateSessionsAndLogins( TRUE, \IPS\Session::i()->id );
			\IPS\Session::i()->log( 'acplog__members_edited_email', array( $member->name => FALSE ) );
		}
		
		/* OK */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( true );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
	
	/**
	 * Change Photo
	 *
	 * @return	void
	 */
	public function photo()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/3', 404, '' );
		}
		
		/* Are we just removing? */
		if ( isset( \IPS\Request::i()->remove ) )
		{
			$member->pp_photo_type = 'none';
			$member->pp_main_photo = NULL;
			$member->pp_thumb_photo = NULL;
			$member->members_bitoptions['bw_disable_gravatar'] = 1;
			$member->photo_last_update = NULL;
			$member->save();
			\IPS\Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'remove' ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* What options do we have? */
		$options = array( 'custom' => 'member_photo_upload', 'url' => 'member_photo_url' );
		$toggles = array( 'custom' => array( 'member_photo_upload' ), 'url' => array( 'member_photo_url' ) );
		$extra = array();
		if ( \IPS\Settings::i()->allow_gravatars )
		{
			$options['gravatar'] = 'member_photo_gravatar';
			$extra[] = new \IPS\Helpers\Form\Email( 'photo_gravatar_email', $member->pp_gravatar, FALSE, array( 'maxLength' => 255, 'placeholder' => $member->email ), NULL, NULL, NULL, 'member_photo_gravatar' );
			$toggles['gravatar'] = array( 'member_photo_gravatar' );
		}
		
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->ajaxOutput = TRUE;
		$form->add( new \IPS\Helpers\Form\Radio( 'pp_photo_type', array_key_exists( $member->pp_photo_type, $options ) ? $member->pp_photo_type : 'custom', TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		$customVal = NULL;
		if ( $member->pp_photo_type === 'custom' )
		{
			$customVal = \IPS\File::get( 'core_Profile', $member->pp_main_photo );
		}
		$photoVars = explode( ':', $member->group['g_photo_max_vars'] );
		$form->add( new \IPS\Helpers\Form\Upload( 'member_photo_upload', $customVal, FALSE, array( 'image' => array( 'maxWidth' => $photoVars[1], 'maxHeight' => $photoVars[2] ), 'storageExtension' => 'core_Profile' ), NULL, NULL, NULL, 'member_photo_upload' ) );
		$form->add( new \IPS\Helpers\Form\Url( 'member_photo_url', NULL, FALSE, array( 'file' => 'core_Profile', 'image' => TRUE ), NULL, NULL, NULL, 'member_photo_url' ) );
		foreach ( $extra as $element )
		{
			$form->add( $element );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$member->pp_photo_type = $values['pp_photo_type'];
						
			/* Save main photo */
			switch ( $values['pp_photo_type'] )
			{
				case 'custom':
					if ( $values['member_photo_upload'] )
					{
						$member->pp_photo_type  = 'custom';
						$member->pp_main_photo  = NULL;
						$member->pp_main_photo  = (string) $values['member_photo_upload'];
						$member->pp_thumb_photo = (string) $values['member_photo_upload']->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE, TRUE );
						$member->photo_last_update = time();
					}
					break;
					
				case 'url':
					$member->pp_photo_type	= 'custom';
					$member->pp_main_photo	= NULL;
					$member->pp_thumb_photo	= NULL;
					$member->pp_main_photo	= (string) $values['member_photo_url'];
					$member->photo_last_update = time();
					break;
					
				case 'gravatar':
					$member->pp_gravatar = ( !isset( $values['photo_gravatar_email'] ) or $values['photo_gravatar_email'] === $member->email ) ? NULL : $values['photo_gravatar_email'];
					break;
			}
												
			/* Save and log */
			$member->save();
			\IPS\Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => $member->pp_photo_type ) );
			
			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'photo', $form, FALSE );
	}
	
	/**
	 * Change Login Method Settings
	 *
	 * @return	void
	 */
	protected function loginEdit()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member and Method */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
			
			$method = \IPS\Login\Handler::load( \IPS\Request::i()->method );
			if ( !$method->canProcess( $member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/Y', 404, '' );
		}
		
		/* Create form */
		$form = new \IPS\Helpers\Form;
		foreach ( $method->syncOptions( $member ) as $option )
		{
			if ( $option == 'photo' and !$member->group['g_edit_profile'] )
			{
				continue;
			}
			if ( $option == 'cover' and ( !$member->group['g_edit_profile'] or !$member->group['gbw_allow_upload_bgimage'] ) )
			{
				continue;
			}
			if ( $option == 'status' and ( !$member->canAccessModule( \IPS\Application\Module::get( 'core', 'status' ) ) or !\IPS\core\Statuses\Status::canCreateFromCreateMenu( $member ) or !\IPS\Settings::i()->profile_comments or $member->group['gbw_no_status_update'] ) )
			{
				continue;
			}
			
			if ( $option == 'status' )
			{
				$checked = ( isset( $member->profilesync[ $option ] ) and array_key_exists( $method->id, $member->profilesync[ $option ]) );
			}
			else
			{
				$checked = ( isset( $member->profilesync[ $option ] ) and $member->profilesync[ $option ]['handler'] == $method->id );
			}
			$field = new \IPS\Helpers\Form\YesNo( "profilesync_{$option}_admin", $checked, FALSE, array(), NULL, NULL, NULL, "profilesync_{$option}_{$method->id}" );
			if ( $checked and ( ( $option == 'status' and $error = $member->profilesync[ $option ][ $method->id ]['error'] ) or ( $option != 'status' and $error = $member->profilesync[ $option ]['error'] ) ) )
			{
				$field->description = \IPS\Theme::i()->getTemplate( 'system' )->settingsLoginMethodSynError( $error );
			}		
			$form->add( $field );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$profileSync = $member->profilesync;
			$changes = array();
			
			foreach ( $values as $k => $v )
			{
				$option = mb_substr( $k, 12, -6 );
				if ( $option === 'status' )
				{
					if ( isset( $member->profilesync[ $option ][ $method->id ] ) )
					{
						if ( !$v )
						{
							unset( $profileSync[ $option ][ $method->id ] );
							$changes[ $option ] = FALSE;
						}
					}
					else
					{
						if ( $v )
						{
							$profileSync[ $option ][ $method->id ] = array( 'lastsynced' => NULL, 'error' => NULL );
							$changes[ $option ] = TRUE;
						}
					}
				}
				else
				{
					if ( isset( $member->profilesync[ $option ] ) and $member->profilesync[ $option ]['handler'] == $method->id )
					{
						if ( !$v )
						{
							unset( $profileSync[ $option ] );
							$changes[ $option ] = FALSE;
						}
					}
					else
					{
						if ( $v )
						{
							$profileSync[ $option ] = array( 'handler' => $method->id, 'ref' => NULL, 'error' => NULL );
							$changes[ $option ] = TRUE;
						}
					}
				}
			}
			
			if ( count( $changes ) )
			{
				$member->logHistory( 'core', 'social_account', array( 'changed' => $changes, 'handler' => $method->id, 'service' => $method::getTitle() ) );
			}
			
			$member->profilesync = $profileSync;
			$member->save();
			$member->profileSync();
			\IPS\Session::i()->log( 'acplog__members_edited_login', array( $member->name => FALSE ) );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Disassociate Login Method
	 *
	 * @return	void
	 */
	protected function loginDelete()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );
		
		/* Load Member and Method */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit_admin' );
			}
			
			$method = \IPS\Login\Handler::load( \IPS\Request::i()->method );
			if ( !$method->canProcess( $member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$canDisassociate = FALSE;
			foreach ( \IPS\Login::methods() as $_method )
			{
				if ( $_method->id != $method->id and $_method->canProcess( $member ) )
				{
					$canDisassociate = TRUE;
					break;
				}
			}
			if ( !$canDisassociate )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/Y', 404, '' );
		}
		
		/* Do it */
		$method->disassociate( $member );
		\IPS\Session::i()->log( 'acplog__members_edited_login_unlink', array( $member->name => FALSE ) );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Find IP Addresses
	 *
	 * @return	void
	 */
	public function ip()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'membertools_ip' );
		
		/* Load Member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/5', 404, '' );
		}
		
		/* Init Table */
		$ips = $member->ipAddresses();
		
		$table = new \IPS\Helpers\Table\Custom( $ips, \IPS\Http\Url::internal( "app=core&module=members&controller=members&id={$member->member_id}&do=ip" ) );
		$table->langPrefix  = 'members_iptable_';
		$table->mainColumn  = 'ip';
		$table->sortBy      = $table->sortBy ?: 'last';
		$table->quickSearch = 'ip';
		$table->include = array( 'ip', 'location', 'count', 'first', 'last' );
		
		/* Parsers */
		$table->parsers = array(
			'first'			=> function( $val )
			{
				return \IPS\DateTime::ts( $val )->localeDate();
			},
			'last'			=> function( $val )
			{
				return \IPS\DateTime::ts( $val )->localeDate();
			},
			'location'	=> function( $val, $row ) {
				try
				{
					return \IPS\GeoLocation::getByIp( $row['ip'] );
				}
				catch ( \BadFunctionCallException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('geolocation_enable_service');
				}
				catch ( \Exception $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('unknown');
				}
			},
		);
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
			return array(
				'view'	=> array(
					'icon'		=> 'search',
					'title'		=> 'see_uses',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=ip&ip=' ) . $row['ip'],
				),
			);
		};
		
		/* Display */
		\IPS\Output::i()->title			= $member->name;
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( "app=core&module=members&controller=members" ), 'members' );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), $member->name );
		\IPS\Output::i()->breadcrumb[] = array( NULL, 'ip_addresses' );
		\IPS\Output::i()->output		= \IPS\Theme::i()->getTemplate( 'forms' )->blurb( \IPS\Member::loggedIn()->language()->addToStack( 'members_ips_info', FALSE, array( 'sprintf' => array( $member->name ) ) ), TRUE, TRUE ) . $table . \IPS\Theme::i()->getTemplate( 'members' )->geoipDisclaimer();
	}
	
	/**
	 * Photo Resize
	 *
	 * @return	void
	 */
	public function photoResize()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/3', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Get photo */
		$image = \IPS\File::get( 'core_Profile', $member->pp_main_photo );
	
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\WidthHeight( 'member_photo_resize', NULL, TRUE, array( 'image' => $image ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Create new file */
			$original = \IPS\File::get( 'core_Profile', $member->pp_main_photo );
			$image = \IPS\Image::create( $original->contents() );
			$image->resize( $values['member_photo_resize'][0], $values['member_photo_resize'][1] );
			
			/* Save the new */
			$member->pp_main_photo = \IPS\File::create( 'core_Profile', $original->filename, (string) $image );
			$member->pp_thumb_photo = (string) $member->pp_main_photo->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE, TRUE );
			$member->save();
			
			/* Delete the original */
			$original->delete();
			
			/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				\IPS\Widget::deleteCaches();
						
			/* Log and redirect */
			\IPS\Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
			$member->logHistory( 'core', 'photo', array( 'action' => 'resize' ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'member_photo_resize', $form, FALSE );
	}
	
	/**
	 * Crop Photo
	 *
	 * @return	void
	 */
	protected function photoCrop()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		/* Load member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/T', 404, '' );
		}
		if ( $member->isAdmin() )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Get the photo */
		$original = \IPS\File::get( 'core_Profile', $member->pp_main_photo );
		$image = \IPS\Image::create( $original->contents() );
		
		/* Work out which dimensions to suggest */
		if ( $image->width < $image->height )
		{
			$suggestedWidth = $suggestedHeight = $image->width;
		}
		else
		{
			$suggestedWidth = $suggestedHeight = $image->height;
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form( 'photo_crop', 'save' );
		$form->class = 'ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original, $member )
			{
				return \IPS\Theme::i()->getTemplate('members', 'core', 'global')->photoCrop( $field->name, $field->value, $member->acpUrl()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				/* Create new file */
				$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );
				
				/* Delete the current thumbnail */					
				if ( $member->pp_thumb_photo )
				{
					try
					{
						\IPS\File::get( 'core_Profile', $member->pp_thumb_photo )->delete();
					}
					catch ( \Exception $e ) { }
				}
								
				/* Save the new */
				$cropped = \IPS\File::create( 'core_Profile', $original->originalFilename, (string) $image );
				$member->pp_thumb_photo = (string) $cropped->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE );
				$member->save();
				\IPS\Session::i()->log( 'acplog__members_edited_photo', array( $member->name => FALSE ) );
				$member->logHistory( 'core', 'photo', array( 'action' => 'crop' ) );

				/* Delete the temporary full size cropped image */
				$cropped->delete();

				/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				\IPS\Widget::deleteCaches();
								
				/* Redirect */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
			catch ( \Exception $e )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('photo_crop_bad');
			}
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Get photo for cropping
	 * If the photo is on a different domain to the JS that handles cropping,
	 * it will be blocked because of CORS. See notes in Cropper documentation.
	 *
	 * @return	void
	 */
	protected function cropPhotoGetPhoto()
	{
		\IPS\Session::i()->csrfCheck();
		$original = \IPS\File::get( 'core_Profile', \IPS\Member::load( \IPS\Request::i()->id )->pp_main_photo );
		$headers = array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'inline', $original->filename ) );
		\IPS\Output::i()->sendOutput( $original->contents(), 200, \IPS\File::getMimeType( $original->filename ), $headers );
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension()
	{
		return 'core_Profile';
	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	\IPS\Helpers\CoverPhoto
	 */
	protected function _coverPhotoGet()
	{		
		
		return \IPS\Member::load( \IPS\Request::i()->id )->coverPhoto();
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	\IPS\Helpers\CoverPhoto	$photo	New Photo
	 * @param	string					$type	'new', 'remove', 'reposition'
	 * @return	void
	 */
	protected function _coverPhotoSet( \IPS\Helpers\CoverPhoto $photo, $type=NULL )
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo' );
		
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( $member->isAdmin() )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'member_photo_admin' );
		}
		
		/* Disable syncing */
		$profileSync = $member->profilesync;
		if ( isset( $profileSync['cover'] ) )
		{
			unset( $profileSync['cover'] );
			$member->profilesync = $profileSync;
		}
		
		$member->pp_cover_photo = (string) $photo->file;
		$member->pp_cover_offset = (int) $photo->offset;
		
		/* Reset Profile Complete flag in case this was an optional step */
		$member->members_bitoptions['profile_completed'] = FALSE;
			
		$member->save();
		if ( $type != 'reposition' )
		{
			$member->logHistory( 'core', 'coverphoto', array( 'action' => $type ) );
		}
		\IPS\Session::i()->log( 'acplog__members_edited_cover_photo', array( $member->name => FALSE ) );
	}
	
	/**
	 * Get URL to return to after editing cover photo
	 *
	 * @return	\IPS\Http\Url
	 */
	protected function _coverPhotoReturnUrl()
	{
		return parent::_coverPhotoReturnUrl()->setQueryString( 'do', 'view' );
	}
	
	/**
	 * Unlock
	 *
	 * @return	void
	 */
	public function unlock()
	{
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/9', 404, '' );
		}
		$member->failed_logins = array();
		$member->failed_mfa_attempts = 0;
		$mfaDetails = $member->mfa_details;
		if ( isset( $mfaDetails['_lockouttime'] ) )
		{
			unset( $mfaDetails['_lockouttime'] );
			$member->mfa_details = $mfaDetails;
		}
		$member->save();
		$member->logHistory( 'core', 'login', array( 'type' => 'unlock' ) );
		
		\IPS\Session::i()->log( 'acplog__members_unlocked', array( $member->name => FALSE ) );
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
		}
	}
			
	/**
	 * Flag as spammer
	 *
	 * @return	void
	 */
	public function spam()
	{
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			
			if ( $member->member_id == \IPS\Member::loggedIn()->member_id or $member->modPermission() or $member->isAdmin() )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_flag_spam_self', '2C114/8', 404, '' );
		}
				
		if ( \IPS\Request::i()->status )
		{
			$member->flagAsSpammer();
			\IPS\Session::i()->log( 'modlog__spammer_flagged', array( $member->name => FALSE ) );
		}
		else
		{
			$member->unflagAsSpammer();
			\IPS\Session::i()->log( 'modlog__spammer_unflagged', array( $member->name => FALSE ) );
		}
				
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), ( \IPS\Request::i()->status ? 'account_flagged' : 'account_unflagged' ) );
		}
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	public function approve()
	{
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/A', 404, '' );
		}
		
		$member->logHistory( 'core', 'account', array( 'type' => 'admin_validated' ) );
		$member->validationComplete();
		
		/* Log */
		\IPS\Session::i()->log( 'acplog__members_approved', array( $member->name => FALSE ) );

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'account_approved' );
		}
	}
	
	/**
	 * Resend Validation Email
	 *
	 * @return	void
	 */
	public function resendEmail()
	{
		/* Load Member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/B', 404, '' );
		}
		
		/* Send */
		foreach ( \IPS\Db::i()->select( '*', 'core_validating', array( 'member_id=?', $member->member_id ) ) as $row )
		{
			if ( !$row['user_verified'] )
			{
				/* Lost Pass */
				if ( $row['lost_pass'] )
				{
					\IPS\Email::buildFromTemplate( 'core', 'lost_password_init', array( $member, $row['vid'] ) )->send( $member );
				}
				/* New Reg */
				elseif ( $row['new_reg'] )
				{
					\IPS\Email::buildFromTemplate( 'core', 'registration_validate', array( $member, $row['vid'] ) )->send( $member );
				}
				/* Email Change */
				elseif ( $row['email_chg'] )
				{
					\IPS\Email::buildFromTemplate( 'core', 'email_change', array( $member, $row['vid'] ) )->send( $member );
				}
				/* Forgot security answers */
				elseif ( $row['forgot_security'] )
				{
					\IPS\Email::buildFromTemplate( 'core', 'forgotSecurityAnswers', array( $member, $row['vid'] ) )->send( $member );
				}
			}
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( \IPS\Member::loggedIn()->language()->get('validation_email_resent') );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'validation_email_resent' );
		}
	}
	
	/**
	 * Merge
	 *
	 * @return	void
	 */
	public function merge()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'members_merge' );
		
		/* Load first member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/6', 404, '' );
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Member( 'member_merge', NULL, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'member_merge_keep', 1, TRUE, array( 'options' => array( 1 => \IPS\Member::loggedIn()->language()->addToStack( 'member_merge_keep_1', FALSE, array( 'sprintf' => array( $member->name ) ) ), 2 => 'member_merge_keep_2' ) ) ) );
		
		/* Merge */
		if ( $values = $form->values() )
		{
			/* Which account are we keeping */
			if ( $values['member_merge_keep'] == 1 )
			{
				$accountToKeep		= $member;
				$accountToDelete	= $values['member_merge'];
			}
			else
			{
				$accountToDelete	= $member;
				$accountToKeep		= $values['member_merge'];
			}
			
			/* Do it */
			try
			{
				$accountToKeep->merge( $accountToDelete );
			}
			catch( \InvalidArgumentException $e )
			{
				\IPS\Output::i()->error( $e->getMessage(), '3C114/J', 403, '' );
			}
						
			/* Delete the account */
			$accountToDelete->delete( FALSE );
			
			/* Log */
			\IPS\Session::i()->log( 'acplog__members_merge', array( $accountToKeep->name => FALSE, $accountToDelete->name => FALSE ) );
			
			/* Boink */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$accountToKeep->member_id}" ), 'saved' );
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'merge', $form, FALSE );
	}
	
	/**
	 * Ban
	 *
	 * @return	void
	 */
	public function ban()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_ban' );
		
		/* Load member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_ban_admin' );
			}
			if ( $member->member_id == \IPS\Member::loggedIn()->member_id )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/7', 404, '' );
		}
		
		/* Just do it? */
		if ( \IPS\Request::i()->permban )
		{
			$member->temp_ban = -1;
			$member->save();

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( 'OK' );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'account_approved' );
			}
		}
		else
		{
			/* Get existing banned IPs */
			$bannedIps = iterator_to_array( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( 'ban_type=?', 'ip' ) )->setKeyField( 'ban_content' ) );
			
			/* Build form */
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\Date( 'member_ban_until', $member->temp_ban, FALSE, array(
				'time'				=> TRUE,
				'unlimited'			=> -1,
				'unlimitedLang'		=> 'permanently',
			), NULL, NULL, NULL, 'member_ban_until' ) );
			
			if ( $member->temp_ban === 0 )
			{
				$form->add( new \IPS\Helpers\Form\Select( 'member_ban_group', $member->member_group_id, FALSE, array( 'options' => \IPS\Member\Group::groups( FALSE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'member_ban_group' ) );
				$memberIps = array_keys( $member->ipAddresses() );
				$form->add( new \IPS\Helpers\Form\Select( 'member_ban_ips', array_intersect( $memberIps, $bannedIps ), FALSE, array( 'options' => $memberIps, 'multiple' => TRUE ), NULL, NULL, NULL, 'member_ban_ips' ) );
			}
			
			/* Ban */
			if ( $values = $form->values() )
			{
				$_existingValue	= $member->temp_ban;

				if ( $values['member_ban_until'] === -1 )
				{
					$member->temp_ban = -1;
				}
				elseif ( !$values['member_ban_until'] )
				{
					$member->temp_ban = 0;
				}
				else
				{
					$member->temp_ban = $values['member_ban_until']->getTimestamp();
				}
				
				if ( $_existingValue != $member->temp_ban )
				{
					$member->logHistory( 'core', 'warning', array( 'restrictions' => array( 'ban' => array( 'old' => $_existingValue, 'new' => $member->temp_ban ) ) ) );
				}
				
				if ( isset( $values['member_ban_group'] ) )
				{
					$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'manual', 'old' => $member->member_group_id, 'new' => $values['member_ban_group'] ) );
					$member->member_group_id = $values['member_ban_group'];
				}

				if ( isset( $values['member_ban_ips'] ) )
				{
					foreach ( $memberIps as $key => $ip )
					{
						if ( in_array( $key, $values['member_ban_ips'] ) and !in_array( $ip, $bannedIps ) )
						{
							\IPS\Db::i()->insert( 'core_banfilters', array( 'ban_type' => 'ip', 'ban_content' => $ip, 'ban_date' => time(), 'ban_reason' => $member->name ) );
						}
						elseif ( !in_array( $key, $values['member_ban_ips'] ) and in_array( $ip, $bannedIps ) )
						{
							\IPS\Db::i()->delete( 'core_banfilters', array( 'ban_content=? AND ban_type=?', $ip, 'ip' ) );
						}
					}

					unset( \IPS\Data\Store::i()->bannedIpAddresses );
				}
				
				$member->save();

				\IPS\Session::i()->log( 'acplog__members_edited', array( $member->name => FALSE ) );
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
			}
			
			/* Display */
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
			}
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'ban', $form, FALSE );
		}
	}
	
	/**
	 * Login as member
	 *
	 * @return	void
	 */
	public function login()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_login' );
		
		/* Load Member and Admin*/
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		$admin = \IPS\Member::loggedIn();
		
		/* Generate a hash and store it in \IPS\Data\Store */
		$key = \IPS\Login::generateRandomString();
		\IPS\Data\Store::i()->admin_login_as_user = $key;
		
		/* Log It */
		\IPS\Session::i()->log( 'acplog__members_loginas', array( $member->name => FALSE ) );
		
		/* Redirect to front controller to update session */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=login&do=loginas&admin={$admin->member_id}&id={$member->member_id}&key={$key}", 'front' ) );
	}
	
	/**
	 * Delete Content
	 *
	 * @return	void
	 */
	public function deleteContent()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'membertools_delete' );
		
		/* Load Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		
		/* Build form */
		$form = new \IPS\Helpers\Form('delete_content', 'delete');
		$form->add( new \IPS\Helpers\Form\Radio( 'hide_or_delete_content', NULL, TRUE, array( 'options' => array( 'hide' => 'hide', 'delete' => 'delete' ) ) ) );
		if ( $values = $form->values() )
		{
			$member->hideOrDeleteAllContent( $values['hide_or_delete_content'] );

			/* Log It */
			\IPS\Session::i()->log( 'acplog__members_' . $values['hide_or_delete_content'] . 'content', array( $member->name => FALSE ) );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'deleted' );
		}
		
		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'deletecontent', $form, FALSE );
	}
	
	/**
	 * Delete Guest Content
	 *
	 * @return	void
	 */
	public function deleteGuestContent()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'membertools_delete' );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'guest_name_to_delete', NULL, TRUE ) );
		if ( $values = $form->values() )
		{
			$classes = array();
			foreach ( \IPS\Content::routedClasses( FALSE, TRUE ) as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) and isset( $class::$databaseColumnMap['author_name'] ) )
				{
					\IPS\Task::queue( 'core', 'MemberContent', array( 'member_id' => 0, 'name' => $values['guest_name_to_delete'], 'class' => $class, 'action' => 'delete' ) );
				}
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=moderation&controller=spam&searchResult=guest_captcha" ), 'deleted' );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_delete' );

		/* Load member */
		try
		{
			$member = \IPS\Member::load( \IPS\Request::i()->id );
			if ( $member->isAdmin() )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'member_delete_admin' );
			}

			if( $member->member_id == \IPS\Member::loggedIn()->member_id )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/7', 404, '' );
		}

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* If they have content, ask what to do with it */
		if ( $member->member_posts )
		{
			/* Build Form */
			$form = new \IPS\Helpers\Form( 'deletiontype', 'continue' );
			$form->hiddenValues['wasConfirmed'] = 1;
			$options = array(
				'keep_name'		=>	'keep_name',
				'remove_name'	=>	'remove_name'
			);
			$form->add( new \IPS\Helpers\Form\Radio( 'member_deletion_keep_name', 'keep_name', FALSE, array( 'options' => $options ) ) );
			\IPS\Member::loggedIn()->language()->words['keep_name'] = sprintf( \IPS\Member::loggedIn()->language()->get('keep_name'), htmlspecialchars( $member->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', false ) );
			\IPS\Member::loggedIn()->language()->words['member_deletion_keep_name_desc'] = sprintf( \IPS\Member::loggedIn()->language()->get('member_deletion_keep_name_desc'), $member->member_id );
			
			/* Handle Submissions */
			if ( $values = $form->values() )
			{
				\IPS\Session::i()->log( 'acplog__members_deleted', array( $member->name => FALSE ) );
				$member->delete( TRUE, ( $values['member_deletion_keep_name'] == 'keep_name' ) );
	
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal("app=core&module=members&controller=members"), 'deleted' );
			}
			
			/* Display Form */
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'delete' );
			\IPS\Output::i()->output = $form;
		}
		
		/* Otherwise just do it */
		else
		{
			\IPS\Session::i()->log( 'acplog__members_deleted', array( $member->name => FALSE ) );
			$member->delete( TRUE, FALSE );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal("app=core&module=members&controller=members"), 'deleted' );
		}
	}
	
	/**
	 * Admin Details
	 *
	 * @return	void
	 */
	public function adminDetails()
	{
		$details = array(
					'username'		=> \IPS\Member::loggedIn()->name,
					'email_address'	=> \IPS\Member::loggedIn()->email,
				);
				
		$canChangePassword = FALSE;
		foreach ( \IPS\Login::methods() as $method )
		{
			if ( $method->canChangePassword( \IPS\Member::loggedIn() ) )
			{
				$details['password'] = \IPS\Member::loggedIn()->language()->addToStack('password_hidden');
			}
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('change_details');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'members', 'core' )->adminDetails( $details );
	}
	
	/**
	 * Admin Password
	 *
	 * @return	void
	 */
	protected function adminPassword()
	{
		$form = new \IPS\Helpers\Form( 'form' );
		$form->add( new \IPS\Helpers\Form\Password( 'current_password', '', TRUE, array( 'validateFor' => \IPS\Member::loggedIn(), 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'new_password', '', TRUE, array( 'showMeter' => \IPS\Settings::i()->password_strength_meter, 'checkStrength' => TRUE, 'bypassProfanity' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'confirm_new_password', '', TRUE, array( 'confirm' => 'new_password', 'bypassProfanity' => TRUE ) ) );
		
		if ( $values = $form->values() )
		{
			/* Save it */
			\IPS\Member::loggedIn()->changePassword( $values['new_password'] );
			
			/* Invalidate sessions except this one */
			\IPS\Member::loggedIn()->invalidateSessionsAndLogins( TRUE, \IPS\Session::i()->id );
			if( isset( \IPS\Request::i()->cookie['login_key'] ) )
			{
				\IPS\Member\Device::loadOrCreate( \IPS\Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* Log */
			\IPS\Session::i()->log( 'acplogs__admin_pass_updated' );
			
			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminDetails" ), 'saved' );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( \IPS\Member::loggedIn()->language()->addToStack('change_password'), $form, FALSE );
	}
	
	/**
	 * Admin Email
	 *
	 * @return	void
	 */
	public function adminEmail()
	{
		$form = new \IPS\Helpers\Form( 'form' );
		$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE, array( 'bypassProfanity' => TRUE ) ) );
		
		if ( $values = $form->values() )
		{
			/* Change email */
			$oldEmail = \IPS\Member::loggedIn()->email;
			\IPS\Member::loggedIn()->email = $values['email_address'];
			\IPS\Member::loggedIn()->save();
			foreach ( \IPS\Login::methods() as $method )
			{
				try
				{
					$method->changeEmail( \IPS\Member::loggedIn(), $oldEmail, $values['email_address'] );
				}
				catch( \BadMethodCallException $e ) {}
			}
			\IPS\Member::loggedIn()->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => $values['email_address'], 'by' => 'manual' ) );
			\IPS\Member::loggedIn()->memberSync( 'onEmailChange', array( $values['email_address'], $oldEmail ) );
			
			/* Invalidate sessions except this one */
			\IPS\Member::loggedIn()->invalidateSessionsAndLogins( TRUE, \IPS\Session::i()->id );
			if( isset( \IPS\Request::i()->cookie['login_key'] ) )
			{
				\IPS\Member\Device::loadOrCreate( \IPS\Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* Log */
			\IPS\Session::i()->log( 'acplogs__admin_email_updated' );
			 	
			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=adminDetails" ), 'saved' );
		}
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( \IPS\Member::loggedIn()->language()->addToStack('change_email'), $form, FALSE );
	}
	
	/**
	 * Recount Content Item Count
	 *
	 * @return	void
	 */
	public function recountContent()
	{
		if ( !\IPS\Request::i()->prompt )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=members&controller=reset&do=posts' ) );
		}
		
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_recount_content' );
		
		/* Load Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		
		/* Rebuild */
		$member->recountContent();
		
		/* redirect */
		\IPS\Session::i()->log( 'acplog__members_edited_content', array( $member->name => FALSE ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Recount Reputation Count
	 *
	 * @return	void
	 */
	public function recountReputation()
	{
		if ( !\IPS\Request::i()->prompt )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=members&controller=reset&do=rep' ) );
		}
		
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_recount_content' );
		
		/* Load Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		
		/* Rebuild */
		$member->recountReputation();
		
		/* redirect */
		\IPS\Session::i()->log( 'acplog__members_edited_rep', array( $member->name => FALSE ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}

	/**
	 * Remove reputation for a member
	 *
	 * @return	void
	 */
	public function removeReputation()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_edit' );

		/* Load Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );

		if( isset( \IPS\Request::i()->type ) and in_array( \IPS\Request::i()->type, array( 'given', 'received' ) ) )
		{
			/* Rebuild */
			$member->removeReputation( \IPS\Request::i()->type );
		}

		/* redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=view&id={$member->member_id}" ), 'saved' );
	}
	
	/**
	 * Import
	 *
	 * @return	void
	 */
	public function import()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_add' );
		
		$wizard = new \IPS\Helpers\Wizard(
			array(
				/* Step 1: Upload .csv file */
				'import_upload_csv'		=> function()
				{
					$form = new \IPS\Helpers\Form( 'csv_form', 'continue' );
					$form->attributes = array( 'data-bypassAjax' => true );
					$form->add( new \IPS\Helpers\Form\Upload( 'import_members_csv_file', NULL, TRUE, array( 'temporary' => TRUE, 'allowedFileTypes' => array( 'csv' ) ), function( $val ) {
						$fh = fopen( $val, 'r' );
						$r = fgetcsv( $fh );
						fclose( $fh );
						if ( empty( $r ) )
						{
							throw new \DomainException('import_members_csv_file_err');
						}
					} ) );
					$form->add( new \IPS\Helpers\Form\YesNo( 'import_members_contains_header', TRUE ) );
					if ( $values = $form->values() )
					{
						$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
						move_uploaded_file( $values['import_members_csv_file'], $tempFile );
			
						return array( 'file' => $tempFile, 'header' => $values['import_members_contains_header'] );
					}
					return (string) $form;
				},
				/* Step 2: Select Columns */
				'import_select_cols'	=> function( $data )
				{										
					/* Init */
					$fh = fopen( $data['file'], 'r' );
					$form = new \IPS\Helpers\Form( 'cols_form', 'continue' );
					
					/* Basic settings like fallback group */
					$form->addHeader( 'import_members_import_settings' );
					$groups = array();
					foreach ( \IPS\Member\Group::groups( TRUE, FALSE ) as $group )
					{
						$groups[ $group->g_id ] = $group->name;
					}
					$form->add( new \IPS\Helpers\Form\Select( 'import_members_fallback_group', \IPS\Settings::i()->member_group, FALSE, array( 'options' => $groups ) ) );
					$form->add( new \IPS\Helpers\Form\YesNo( 'import_members_send_confirmation' ) );
					
					/* Init Matrix */
					$form->addHeader( 'import_members_csv_details' );
					$form->addMessage( 'import_date_explain' );
					$matrix = new \IPS\Helpers\Form\Matrix;
					$matrix->langPrefix = FALSE;
					$matrix->manageable = FALSE;
					
					/* Define matrix columns with available places we can import data to */
					$matrix->columns = array(
						'import_column'	=> function( $key, $value, $data )
						{
							return $value;
						},
						'import_as'	=> function( $key, $value, $data )
						{
							$importOptions =  array(
								NULL	=> 'do_not_import',
								'import_basic_data'	=> array(
									'member_id'		=> 'member_id',
									'name'			=> 'username',
									'email'			=> 'email_address',
									'member_posts'	=> 'members_member_posts',
									'joined'		=> 'import_joined_date',
									'ip_address'	=> 'ip_address',
								),
								'import_group'	=> array(
									'group_id'				=> 'import_group_id',
									'secondary_group_id'	=> 'import_secondary_group_id',
								),
								'import_passwords'	=> array(
									'password_plain'			=> 'import_password_plain',
									'password_blowfish_hash'	=> 'import_password_blowfish_hash',
								),
								'import_member_preferences'	=> array(
									'timezone'			=> 'timezone',
									'birthday'			=> 'import_birthday',
									'allow_admin_mails'	=> 'import_allow_admin_mails',
									'member_title'		=> 'import_member_title',
								),
								'import_member_other'	=> array(
									'last_visit'	=> 'import_last_visit_date',
									'last_post'		=> 'last_post',
								)
							);
							$languages = \IPS\Lang::languages();
							foreach ( $languages as $lang )
							{
								$importOptions['import_group'][ 'group_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_group_name' : \IPS\Member::loggedIn()->language()->addToStack( 'import_group_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
								$importOptions['import_group'][ 'group_secondary_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_secondary_group_name' : \IPS\Member::loggedIn()->language()->addToStack( 'import_secondary_group_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
							}
							if ( \IPS\Settings::i()->signatures_enabled )
							{
								$importOptions['import_member_preferences']['signature'] = 'signature';
							}
							if ( \IPS\Settings::i()->reputation_enabled )
							{
								$importOptions['import_basic_data']['pp_reputation_points'] = 'import_member_reputation';
							}
							if ( \IPS\Settings::i()->warn_on )
							{
								$importOptions['import_basic_data']['warn_level'] = 'import_member_warn_level';
							}
							
							if ( count( \IPS\Theme::themes() ) > 1 )
							{
								$importOptions['import_member_preferences']['skin']		= 'import_theme_id';
								
								foreach ( $languages as $lang )
								{
									$importOptions['import_member_preferences'][ 'skin_name_' . $lang->id ] = count( $languages ) == 1 ? 'import_theme_name' : \IPS\Member::loggedIn()->language()->addToStack( 'import_theme_name_lang', FALSE, array( 'sprintf' => $lang->_title ) );
								}
								
							}
							if ( count( \IPS\Lang::languages() ) > 1 )
							{
								$importOptions['import_member_preferences']['language']		= 'import_language_id';
								$importOptions['import_member_preferences']['language_name']	= 'import_language_name';
							}
							foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::STAFF ) as $groupId => $fields )
							{
								foreach ( $fields as $fieldId => $field )
								{
									$importOptions['import_custom_fields'][ 'pfield_' . $fieldId ] = 'core_pfield_' . $fieldId;
									unset( \IPS\Member::loggedIn()->language()->words[ 'core_pfield_' . $fieldId . '_desc' ] );
								}
							}
							return new \IPS\Helpers\Form\Select( $key, $value, FALSE, array( 'options' => $importOptions ) );
						}
					);
					
					/* Look at the first row in the .csv file and ask where to put each piece of data
						- if the first row is a header, guess from what it says what content it might
						contain (for example, if the header is "email" - that's obviously where the
						email addresses are */
					$headers = fgetcsv( $fh );
					fclose( $fh );
					$i = 0;
					foreach ( $headers as $i => $header )
					{
						if ( $data['header'] )
						{						
							$value = NULL;
							$parsedHeader = preg_replace( '/[-_]/', '', $header );
							switch ( mb_strtolower( $parsedHeader ) )
							{								
								case 'name':
								case 'username':
								case 'displayname':
									$value = 'name';
									break;
								
								case 'email':
								case 'emailaddress':
									$value = 'email';
									break;
									
								case 'memberposts':
								case 'posts':
									$value = 'member_posts';
									break;
									
								case 'joined':
								case 'joineddate':
								case 'joindate':
								case 'regdate':
									$value = 'joined';
									break;
									
								case 'ip':
								case 'ip_address':
									$value = 'ip_address';
									break;
								
								case 'group':
								case 'primarygroup':
								case 'primarygroupid':
									$value = 'group_id';
									break;
								
								case 'groupname':
								case 'primarygroupname':
									$value = 'group_name_' . \IPS\Lang::defaultLanguage();
									break;
								
								case 'secondarygroup':
								case 'secondarygroupids':
									$value = 'secondary_group_id';
									break;
								
								case 'secondarygroupname':
								case 'secondarygroupnames':
									$value = 'group_secondary_name_' . \IPS\Lang::defaultLanguage();
									break;
									
								case 'pass':
								case 'password':
									$value = 'password_plain';
									break;
								
								case 'passhash':
								case 'passwordhash':
									$value = 'password_blowfish_hash';
									break;
																	
								case 'timezone':
									$value = 'timezone';
									break;
								
								case 'bday':
								case 'birthday':
								case 'birthdate':
									$value = 'birthday';
									break;
								
								case 'mailinglist':
								case 'allowadminmails':
								case 'newsletter':
								case 'sendnews':
									$value = 'allow_admin_mails';
									break;
									
								case 'lastvisit':
								case 'lastactivity':
									$value = 'last_visit';
									break;
								
								case 'lastpost':
									$value = 'last_post';
									break;
									
								case 'sig':
								case 'signature':
									$value = 'signature';
									break;
									
								case 'rep':
								case 'reputation':
								case 'ppreputationpoints':
									$value = 'pp_reputation_points';
									break;
									
								case 'warningpoints':
								case 'warnpoints':
								case 'warninglevel':
								case 'warnlevel':
									$value = 'warn_level';
									break;
																
								case 'skin':
									$value = 'skin';
									break;
									
								case 'skinname':
								case 'theme':
									$value = 'skin_name_' . \IPS\Lang::defaultLanguage();
									break;
								
								case 'language':
									$value = 'language';
									break;
									
								case 'language_name':
								case 'lang':
									$value = 'language_name';
									break;
							}
							
							$matrix->rows[] = array( 'import_column' => $header, 'import_as' => $value );
						}
						else
						{
							$matrix->rows[] = array( 'import_column' => \IPS\Member::loggedIn()->language()->addToStack( 'import_column_number', FALSE, array( 'sprintf' => array( ++$i ) ) ), 'import_as' => '' );
						}
					}
					
					/* Add the matrix */
					$form->addMatrix( 'columns', $matrix );
					
					/* Handle submissions */
					if ( $values = $form->values() )
					{						
						$data['import_members_fallback_group'] = $values['import_members_fallback_group'];
						$data['import_members_send_confirmation'] = $values['import_members_send_confirmation'];
						
						foreach ( $values['columns'] as $k => $vals )
						{
							if ( $vals['import_as'] )
							{
								$data['columns'][ $k ] = $vals['import_as'];
							}
						}
						
						if ( !in_array( 'name', $data['columns'] ) and !in_array( 'email', $data['columns'] ) )
						{
							$form->error = \IPS\Member::loggedIn()->language()->addToStack('import_member_no_name_or_email');
						}
						else
						{
							return $data;
						}
					}
					
					/* Display */
					return (string) $form;
				},
				/* Step 3: Import */
				'import_do_import'	=> function( $wizardData )
				{
					return (string) new \IPS\Helpers\MultipleRedirect( \IPS\Http\Url::internal('app=core&module=members&controller=members&do=import'),	function( $mrData ) use ( $wizardData )
					{
						/* Get line from the file */
						$fh = fopen( $wizardData['file'], 'r' );
						if ( $mrData === 0 OR $mrData === NULL )
						{
							/* Ignore the header row */
							if ( $wizardData['header'] )
							{
								fgetcsv( $fh );
							}
							
							/* Set the MultipleRedirect data */
							$mrData = array( 'currentPosition' => 0, 'errors' => array() );
						}
						else
						{
							fseek( $fh, $mrData['currentPosition'] );
						}
						$line = fgetcsv( $fh );
						
						/* Are we done/ */
						if ( !$line )
						{
							fclose( $fh );
							
							\IPS\Widget::deleteCaches( 'stats', 'core' );
							
							if ( isset( $mrData['errors'] ) AND count( $mrData['errors'] ) )
							{
								return array( \IPS\Theme::i()->getTemplate( 'members' )->importMemberErrors( $mrData['errors'] ) );
							}
							else
							{
								return NULL;
							}
						}

						/* Create the member */
						try
						{
							$password = \IPS\Login::generateRandomString( 8 );
							$member = new \IPS\Member;
							$member->member_group_id = $wizardData['import_members_fallback_group'];
							$member->setLocalPassword( $password );
							$profileFields = array();
							foreach ( $line as $k => $v )
							{
								if ( isset( $wizardData['columns'][ $k ] ) )
								{
									if ( mb_substr( $wizardData['columns'][ $k ], 0, 11 ) == 'group_name_' )
									{
										try
										{
											$member->member_group_id = mb_substr( \IPS\Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 11 ), '%core_group_%', $v ) )->first(), 11 );
										}
										catch ( \UnderflowException $e ) { }
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 21 ) == 'group_secondary_name_' )
									{
										$secondaryGroupIds = array();
										foreach ( array_filter( explode( ',', $wizardData['columns'][ $k ] ) ) as $secondaryGroupName )
										{
											try
											{
												$secondaryGroupIds[] = mb_substr( \IPS\Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 11 ), '%core_group_%', $v ) )->first(), 11 );
											}
											catch ( \UnderflowException $e ) { }
										}
										$member->mgroup_others = implode( ',', $secondaryGroupIds );
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 10 ) == 'skin_name_' )
									{
										try
										{
											$member->skin = mb_substr( \IPS\Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ? AND word_custom=?', mb_substr( $wizardData['columns'][ $k ], 10 ), '%core_theme_set_title_%', $v ) )->first(), 21 );
										}
										catch ( \UnderflowException $e ) { }
									}
									elseif ( mb_substr( $wizardData['columns'][ $k ], 0, 7 ) == 'pfield_' )
									{
										$profileFields[ mb_substr( $wizardData['columns'][ $k ], 1 ) ] = $v;
									}
									else
									{
										switch ( $wizardData['columns'][ $k ] )
										{
											case 'member_id':
												$existingMember = \IPS\Member::load( $v );
												if ( $existingMember->member_id )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_member_id_exists' ) ) );
												}
												$member->member_id = $v;
												break;
											
											case 'name':
												if ( !$v )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_no_name' ) ) );
												}
												if ( \IPS\Login::usernameIsInUse( $v, NULL, TRUE ) )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_name_exists' ), $v ) );
												}
												$member->name = $v;
												break;
												
											case 'email':
												/* There may be an erroneous space in the column */
												$v	= trim( $v );

												if ( !$v )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_no_email' ) ) );
												}
												if ( filter_var( $v, FILTER_VALIDATE_EMAIL ) === FALSE )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_email_invalid' ), $v ) );
												}
												if ( \IPS\Login::emailIsInUse( $v, NULL, TRUE ) )
												{
													throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_email_exists' ), $v ) );
												}
												$member->email = $v;
												break;
												
											case 'group_id':
												try
												{
													$member->member_group_id = \IPS\Member\Group::load( $v )->g_id;
												}
												catch ( \OutOfRangeException $e ) { }
												break;
												
											case 'secondary_group_id':
												$secondaryGroupIds = array();
												foreach ( array_filter( explode( ',', $v ) ) as $secondaryGroupId )
												{
													try
													{
														$secondaryGroupIds[] = \IPS\Member\Group::load( $secondaryGroupId )->g_id;
													}
													catch ( \OutOfRangeException $e ) { }
												}
												$member->mgroup_others = implode( ',', $secondaryGroupIds );
												break;
												
											case 'password_plain':
												$member->setLocalPassword( $v );
												$password = NULL;
												break;
												
											case 'password_blowfish_hash':
												$member->members_pass_hash = $v;
												$member->members_pass_salt = NULL;
												break;
																								
											case 'birthday':
												$exploded = explode( '-', $v );

												if ( count( $exploded ) == 2 OR count( $exploded ) == 3 )
												{
													if ( intval( $exploded[0] ) <= 31 and intval( $exploded[1] ) <= 12 )
													{
														$member->bday_day = intval( $exploded[0] );
														$member->bday_month = intval( $exploded[1] );
														if ( isset( $exploded[2] ) AND is_numeric( $exploded[2] ) )
														{
															$member->bday_year = intval( $exploded[2] );
														}
													}
												}
												break;
												
											case 'language_name':
												try
												{
													$member->language = \IPS\Db::i()->select( 'lang_id', 'core_sys_lang', array( 'lang_title=?', $v ) )->first();
												}
												catch ( \UnderflowException $e ) { }
												break;
												
											case 'last_post':
												if( $v AND strtotime( $v ) )
												{
													$member->member_last_post = strtotime( $v );
												}
												break;

											case 'joined':
											case 'last_visit':
												if( $v AND strtotime( $v ) )
												{
													$key = $wizardData['columns'][ $k ];
													$member->$key = strtotime( $v );
												}
												break;
												
											default:
												$key = $wizardData['columns'][ $k ];
												$member->$key = $v;
												break;
										}
									}
								}
							}
							if ( !$member->name and !$member->email )
							{
								throw new \DomainException( sprintf( \IPS\Member::loggedIn()->language()->get( 'import_no_name' ) ) );
							}
							if( !$member->joined )
							{
								$member->joined = time();
							}
							
							$member->members_bitoptions['created_externally'] = TRUE;
							
							$member->save();
							if ( count( $profileFields ) )
							{
								\IPS\Db::i()->replace( 'core_pfields_content', array_merge( array( 'member_id' => $member->member_id ), $profileFields ) );
							}
							\IPS\Session::i()->log( 'acplog__members_created', array( $member->name => FALSE ) );
						}
						catch ( \DomainException $e )
						{
							$mrData['errors'][] = $e->getMessage();
						}
						
						/* Send email */
						if ( $wizardData['import_members_send_confirmation'] )
						{
							\IPS\Email::buildFromTemplate( 'core', 'admin_reg', array( $member, $password ) )->send( $member );
						}
						
						/* Continue */
						$mrData['currentPosition'] = ftell( $fh );
						fclose( $fh );
						return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('import_members_processing'), 100 / filesize( $wizardData['file'] ) * $mrData['currentPosition'] );
					},
					function() use ( $wizardData )
					{
						@unlink( $wizardData['file'] );
						\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=members&controller=members') );
					} );					
				}
			),
			\IPS\Http\Url::internal('app=core&module=members&controller=members&do=import')
		);
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('members_import');
		\IPS\Output::i()->output = $wizard;
	}
	
	/**
	 * Export
	 *
	 * @return	void
	 */
	public function export()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_export' );
		
		/* Define what columns are available */
		$columns = array(
			'member_id'				=> 'member_id',
			'name'					=> 'username',
			'email'					=> 'email',
			'password_hash'			=> 'export_member_password_hash',
			'password_salt'			=> 'export_member_password_salt',
			'member_group_id'		=> 'import_group_id',
			'primary_group_name'	=> 'import_group_name',
			'mgroup_others'			=> 'import_secondary_group_id',
			'secondary_group_names'	=> 'import_secondary_group_name',
			'member_posts'			=> 'members_member_posts',
			'joined'				=> 'import_joined_date',
			'ip_address'			=> 'members_ip_address',
			'timezone'				=> 'timezone',
			'last_visit'			=> 'import_last_visit_date',
			'last_post'				=> 'last_post',
			'birthday'				=> 'import_birthday',
			'allow_admin_mails'		=> 'import_allow_admin_mails',
			'member_title'			=> 'import_member_title'
		);
		if ( \IPS\Settings::i()->signatures_enabled )
		{
			$columns['signature'] = 'signature';
		}
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			$columns['pp_reputation_points'] = 'import_member_reputation';
		}
		if ( \IPS\Settings::i()->warn_on )
		{
			$columns['warn_level'] = 'import_member_warn_level';
		}
		
		if ( count( \IPS\Theme::themes() ) > 1 )
		{
			$columns['skin']		= 'import_theme_id';
			$columns['skin_name']	= 'import_theme_name';
		}
		if ( count( \IPS\Lang::languages() ) > 1 )
		{
			$columns['language']		= 'import_language_id';
			$columns['language_name']	= 'import_language_name';
		}
		foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::STAFF ) as $groupId => $fields )
		{
			foreach ( $fields as $fieldId => $field )
			{
				$columns[ 'pfield_' . $fieldId ] = 'core_pfield_' . $fieldId;
				unset( \IPS\Member::loggedIn()->language()->words[ 'core_pfield_' . $fieldId . '_desc' ] );
			}
		}
		
		$initialData = NULL;
		if ( isset( \IPS\Request::i()->group ) )
		{
			$initialData['filters']['core_Group']['groups'] = \IPS\Request::i()->group;
		}
		
		/* Wizard */
		$wizard = new \IPS\Helpers\Wizard(
			array(
				/* Step 1: Choose data */
				'export_choose_data'	=> function( $wizardData ) use ( $columns )
				{
					if ( isset( \IPS\Request::i()->includeInsecure ) )
					{
						$wizardData['includeInsecure'] = TRUE;
					}
					
					$form = new \IPS\Helpers\Form( 'choose_data', 'continue' );
					
					$form->addHeader( 'export_columns_to_include' );
					$form->add( new \IPS\Helpers\Form\CheckboxSet( 'export_columns_to_include', isset( $wizardData['columns'] ) ? $wizardData['columns'] : array( 'member_id', 'name', 'email', 'primary_group_name', 'secondary_group_names', 'member_posts', 'joined', 'skin_name', 'language_name' ), TRUE, array( 'options' => $columns ) ) );
					
					$form->addHeader( 'generic_bm_filters' );
					$lastApp = 'core';
					foreach ( \IPS\Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
					{
						if( method_exists( $extension, 'getSettingField' ) )
						{
							$_key		= explode( '_', $key );
							if( $_key[0] != $lastApp )
							{
								$lastApp	= $_key[0];
								$form->addHeader( $lastApp . '_bm_filters' );
							}
							
							foreach ( $extension->getSettingField( isset( $wizardData['filters'][ $key ] ) ? $wizardData['filters'][ $key ] : array() ) as $field )
							{
								$form->add( $field );
							}
						}
					}
					
					if ( $values = $form->values() )
					{
						$wizardData['columns'] = $values['export_columns_to_include'];
						
						foreach ( \IPS\Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
						{
							if( method_exists( $extension, 'save' ) )
							{
								$_value = $extension->save( $values );
								if( $_value )
								{
									$wizardData['filters'][ $key ] = $_value;
								}
							}
						}
						
						$wizardData['file'] = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
						$fh = fopen( $wizardData['file'], 'w' );
						$headers = array();
						foreach ( $wizardData['columns'] as $column )
						{
							$headers[] = $column;
						}
						fputcsv( $fh, $headers );
						fclose( $fh );
						
						return $wizardData;
					}
					
					return (string) $form;
				},
				/* Step 2: Build List */
				'export_build_list'		=> function( $wizardData ) use ( $columns )
				{
					$baseUrl = \IPS\Http\Url::internal('app=core&module=members&controller=members&do=export');
					
					if ( isset( \IPS\Request::i()->buildDone ) )
					{
						if ( isset( \IPS\Request::i()->removedData ) )
						{							
							$wizardData['removedData'] = json_decode( base64_decode( \IPS\Request::i()->removedData ), TRUE );
						}
						return $wizardData;
					}
					
					return (string) new \IPS\Helpers\MultipleRedirect(
						$baseUrl,
						function( $mrData ) use ( $wizardData, $baseUrl )
						{
							$doPerLoop = 50;
							if ( !is_array( $mrData ) )
							{
								$mrData = array( 'offset' => 0, 'removedData' => array(), 'total' => 0 );
							}
							
							/* Compile where */
							$where = array();
							foreach ( \IPS\Application::allExtensions( 'core', 'MemberFilter', FALSE, 'core' ) as $key => $extension )
							{
								if( method_exists( $extension, 'getQueryWhereClause' ) )
								{
									/* Grab our fields and add to the form */
									if( isset( $wizardData['filters'][ $key ] ) )
									{
										if( $_where = $extension->getQueryWhereClause( $wizardData['filters'][ $key ] ) )
										{
											if ( is_string( $_where ) )
											{
												$_where = array( $_where );
											}
											$where = array_merge( $where, $_where );
										}
									}
								}
							}
							
							/* Do we need to join profile field data? */
							$select = array( 'core_members.*' );
							$customFields = array();
							foreach ( $wizardData['columns'] as $column )
							{
								if ( mb_substr( $column, 0, 7 ) == 'pfield_' )
								{
									$customFields[] = 'core_pfields_content.field_' . mb_substr( $column, 7 );
								}
							}
							if ( count( $customFields ) )
							{
								$select[] = implode( ',', $customFields );
							}
							
							/* Compile query */
							$query = \IPS\Db::i()->select( implode( ',', $select ), 'core_members', $where, 'core_members.member_id', array( $mrData['offset'], $doPerLoop ), NULL, NULL, !isset( $mrData['count'] ) ? \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS : 0 );
							if ( count( $customFields ) )
							{
								$query->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' );
							}
							
							/* Run callbacks */
							foreach ( \IPS\Application::allExtensions( 'core', 'MemberFilter', TRUE, 'core' ) as $key => $extension )
							{
								if( method_exists( $extension, 'queryCallback' ) )
								{
									/* Grab our fields and add to the form */
									if( !empty( $wizardData['filters'][ $key ] ) )
									{
										$data = $wizardData['filters'][ $key ];
										$extension->queryCallback( $data, $query );
									}
								}
							}
																					
							/* Finished? */
							if ( count( $query ) === 0 )
							{
								\IPS\Output::i()->redirect( $baseUrl->setQueryString( array( 'buildDone' => 1, 'removedData' => base64_encode( json_encode( $mrData['removedData'] ) ) ) ) );
							}
							
							/* Open file */
							$fh = fopen( $wizardData['file'], 'a' );
							
							/* Run */
							foreach ( $query as $member )
							{
								$dataToWrite = array();
								foreach ( $wizardData['columns'] as $column )
								{
									$valueToWrite = '';
									
									switch ( $column )
									{
										case 'password_hash':
											$valueToWrite = $member['members_pass_hash'];
											break;

										case 'password_salt':
											$valueToWrite = $member['members_pass_salt'];
											break;
										
										case 'primary_group_name':
											try
											{
												$valueToWrite = \IPS\Member::loggedIn()->language()->get( 'core_group_' . $member['member_group_id'] );
											}
											catch ( \UnderflowException $e )
											{
												$valueToWrite = '';
											}
											break;
										
										case 'secondary_group_names':
											$secondaryGroupNames = array();
											foreach ( array_filter( explode( ',', $member['mgroup_others'] ) ) as $secondaryGroupId )
											{
												try
												{
													$secondaryGroupNames[] = \IPS\Member::loggedIn()->language()->get( 'core_group_' . $secondaryGroupId );
												}
												catch ( \UnderflowException $e ) { }
											}
											$valueToWrite = implode( ',', $secondaryGroupNames );
											break;

										case 'last_visit':
											$column = $member[ 'last_visit' ] > $member['last_activity'] ? 'last_visit' : 'last_activity';
											$valueToWrite = $member[ $column ] ? date( 'Y-m-d H:i', $member[ $column ] ) : '';
											break;
										case 'joined':
										case 'last_post':
											if ( $column === 'last_post' )
											{
												$column = 'member_last_post';
											}
											
											$valueToWrite = $member[ $column ] ? date( 'Y-m-d H:i', $member[ $column ] ) : '';
											break;
											
										case 'birthday':
											if ( $member['bday_day'] and $member['bday_month'] )
											{
												$valueToWrite = ( $member['bday_year'] ?: '????' ) . '-' . str_pad( $member['bday_month'], 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $member['bday_day'], 2, '0', STR_PAD_LEFT );
											}
											else
											{
												$valueToWrite = '';
											}
											break;
											
										case 'skin_name':
											$themeId = $member['skin'] ?: \IPS\Theme::defaultTheme();
											try
											{
												$valueToWrite = \IPS\Member::loggedIn()->language()->get( 'core_theme_set_title_' . $themeId );
											}
											catch ( \UnderflowException $e )
											{
												$valueToWrite = '';
											}
											break;
											
										case 'language_name':
											$langId = $member['language'] ?: \IPS\Lang::defaultLanguage();
											try
											{
												$valueToWrite = \IPS\Lang::load( $langId )->_title;
											}
											catch ( \OutOfRangeException $e )
											{
												$valueToWrite = '';
											}
											break;
										
										default:
											if ( mb_substr( $column, 0, 7 ) == 'pfield_' )
											{
												$valueToWrite = $member[ 'field_' . mb_substr( $column, 7 ) ];
											}
											else
											{
												$valueToWrite = $member[ $column ];
											}
											break;
									}
									
									/* Cells starting with =, + or - can be a security risk. */
									if ( !isset( $wizardData['includeInsecure'] ) and !in_array( $column, array( 'primary_group_name', 'secondary_group_names', 'skin_name', 'language_name', 'members_pass_hash', 'members_pass_salt' ) ) and in_array( mb_substr( $valueToWrite, 0, 1 ), array( '=', '+', '-', '@' ) ) )
									{										
										$mrData['removedData'][ $member['member_id'] ] = array( $column, base64_encode( $valueToWrite ) );
										continue 2;
									}
									
									/* Add it */
									$dataToWrite[] = $valueToWrite;
								}
								
								/* Write */
								fputcsv( $fh, $dataToWrite );
							}

							/* If we don't have our count (first loop), get it */
							if( !isset( $mrData['count'] ) )
							{
								$mrData['count'] = $query->count( TRUE );
							}
							
							/* Close and loop */
							fclose( $fh );
							$mrData['offset'] += $doPerLoop;
							return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('export_members_processing'), 100 / $mrData['count'] * $mrData['offset'] );
						},
						function() use ( $baseUrl )
						{
							\IPS\Output::i()->redirect( $baseUrl->setQueryString( array( 'buildDone' => 1 ) ) );
						}
					);
				},
				/* Step 3: Show the download link */
				'export_download_file'	=> function( $wizardData )
				{					
					if ( isset( \IPS\Request::i()->download ) )
					{
						\IPS\Output::i()->sendOutput( file_get_contents( $wizardData['file'] ), 200, 'text/csv', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', \IPS\Member::loggedIn()->language()->get('member_pl') . '.csv' ) ), FALSE, FALSE, FALSE );
					}
					
					return \IPS\Theme::i()->getTemplate( 'members' )->downloadMemberList( isset( $wizardData['removedData'] ) ? $wizardData['removedData'] : array(), isset( $wizardData['includeInsecure'] ) ? $wizardData['includeInsecure'] : FALSE );
				}
			),
			\IPS\Http\Url::internal('app=core&module=members&controller=members&do=export'),
			TRUE,
			$initialData
		);
		
		/* Output */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('members_export');
		\IPS\Output::i()->output = $wizard;
	}
	
	/**
	 * Export Personal Information
	 *
	 * @return void
	 */
	public function exportPersonalInfo()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_export_pi' );

		/* Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );

		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/V', 404, '' );
		}
		
		if ( ! isset( \IPS\Request::i()->process ) )
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('member_export_pi_title');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('memberprofile')->downloadPersonalInfo( $member );
		}
		else
		{
			/* Init */
			$xml = \IPS\Xml\SimpleXML::create('member_export');
			$xml->addAttribute( 'created', \IPS\DateTime::ts( time() )->rfc3339() );
			
			/* Get the data */
			foreach( \IPS\Application::allExtensions( 'core', 'MemberExportPersonalInformation', TRUE, 'core' ) AS $key => $ext )
			{
				if ( $data = $ext->getData( $member ) and is_array( $data ) and count( $data ) )
				{
					$child = $xml->addChild( $key );
					foreach( $data as $k => $v )
					{
						$child->addChild( $k, $v );
					}
				}
			}
	
			/* Build */
			\IPS\Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'attachment', $member->name . '_personal_information.xml' ) ), FALSE, FALSE, FALSE );
		}
	}

	/**
	 * Member History
	 *
	 * @return	void
	 */
	public function history()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'member_history' );

		/* Member */
		$member = \IPS\Member::load( \IPS\Request::i()->id );

		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/N', 404, '' );
		}

		$title = 'member_account';
		$link = $member->acpUrl();

		/* Viewer came from commerce customer page */
		if( \IPS\Application::appIsEnabled( 'nexus' ) AND \IPS\Request::i()->nexus_return )
		{
			$title = 'view_account';
			$link = \IPS\nexus\Customer::load( $member->member_id )->acpUrl();
		}

		/* History */
		$history = new \IPS\Member\History( $member->acpUrl()->setQueryString( 'do', 'history' ), array( array( 'log_member=?', $member->member_id ) ) );
		
		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->_fromFilter ) )
		{
			$history->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyTable' );
			$history->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'memberprofile', 'core' ), 'historyRows' );
			$history->limit = 20;
		}

		/* Output */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('member_history_member', FALSE, array( 'sprintf' => array( $member->name ) ) );
		\IPS\Output::i()->output = $history;

		\IPS\Output::i()->sidebar['actions'][] = array(
			'icon'	=> 'arrow-left',
			'title'	=> $title,
			'link'	=> $link
		);
	}
		
	/**
	 * View Warning
	 *
	 * @return	void
	 */
	protected function viewWarning()
	{
		/* Load it */
		try
		{
			$warning = \IPS\core\Warnings\Warning::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/P', 404, '' );
		}
		
		/* Show it */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('memberprofile')->warningView( $warning );
	}
	
	/**
	 * Revoke Warning
	 *
	 * @return	void
	 */
	protected function warningRevoke()
	{
		try
		{
			$item	= \IPS\core\Warnings\Warning::loadAndCheckPerms( \IPS\Request::i()->id );
			$member	= \IPS\Member::load( $item->member );
			
			if ( $item->canDelete() )
			{
				if ( \IPS\Request::i()->prompt )
				{
					$item->undo();
				}
				$item->delete();
				\IPS\Output::i()->redirect( $member->acpUrl(), 'warn_revoked' );
			}
			else
			{
				\IPS\Output::i()->error( 'generic_error', '2C114/R', 403, '' );
			}
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C114/Q', 404, '' );
		}
	}
}