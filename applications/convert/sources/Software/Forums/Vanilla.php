<?php
/**
 * @brief		Converter Vanilla Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Forums;
use \IPS\convert\Software\Core\Vanilla as VanillaCore;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Vanilla extends \IPS\convert\Software
{
	/**
	 * @brief	Store the result of the check for reaction support
	 */
	protected static $_supportsReactions = FALSE;

	/**
	 * Constructor
	 *
	 * @param	\IPS\convert\App	The application to reference for database and other information.
	 * @param	bool				Establish a DB connection
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public function __construct( \IPS\convert\App $app, $needDB=TRUE )
	{
		$return = parent::__construct( $app, $needDB );

		/* Check for reaction support - This is a Vanilla2 addon, so it may not be installed */
		if ( $needDB )
		{
			static::$_supportsReactions = $this->db->checkForTable( 'Action' );
		}

		return $return;
	}
	/**
	 * Software Name
	 *
	 * @return	string
	 */
	public static function softwareName()
	{
		/* Child classes must override this method */
		return "Vanilla 2 (2.2.x/2.3.x)";
	}
	
	/**
	 * Software Key
	 *
	 * @return	string
	 */
	public static function softwareKey()
	{
		/* Child classes must override this method */
		return "vanilla";
	}

	/**
	 * Requires Parent
	 *
	 * @return	boolean
	 */
	public static function requiresParent()
	{
		return TRUE;
	}

	/**
	 * Possible Parent Conversions
	 *
	 * @return	array
	 */
	public static function parents()
	{
		return array( 'core' => array( 'vanilla' ) );
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return	array
	 */
	public static function canConvert()
	{
		return array(
			'convertForumsForums' => array(
				'table'     => 'Category',
				'where'     => NULL
			),
			'convertForumsTopics' => array(
				'table'         => 'Discussion',
				'where'         => NULL
			),
			'convertForumsPosts' => array(
				'table'			=> 'Comment',
				'where'			=> NULL,
				'extra_steps'   => array( 'convertForumsPosts2' )
			),
			'convertForumsPosts2'  => array(
				'table'     => 'Comment',
				'where'     => NULL
			),
			'convertAttachments'	=> array(
				'table'		=> 'Media',
				'where'		=> array( 'ForeignTable=? OR ForeignTable=?', 'discussion', 'comment' )
			)
		);
	}

	/**
	 * Allows software to add additional menu row options
	 *
	 * @param	array 	$rows	Existing rows
	 * @return	array
	 */
	public function extraMenuRows( $rows )
	{
		$rows['convertForumsPosts2'] = $rows['convertForumsPosts'];
		$rows['convertForumsPosts2']['step_method'] = 'convertForumsPosts2';

		return $rows;
	}

	/**
	 * Count Source Rows for a specific step
	 *
	 * @param	string		$table		The table containing the rows to count.
	 * @param	array|NULL	$where		WHERE clause to only count specific rows, or NULL to count all.
	 * @param	bool		$recache	Skip cache and pull directly (updating cache)
	 * @return	integer
	 * @throws	\IPS\convert\Exception
	 */
	public function countRows( $table, $where=NULL, $recache=FALSE )
	{
		switch( $table )
		{
			case 'Comment':
				$count = 0;
				$count += $this->db->select( 'COUNT(*)', 'Discussion' )->first();
				$count += $this->db->select( 'COUNT(*)', 'Comment' )->first();
				return $count;
				break;

			default:
				return parent::countRows( $table, $where, $recache );
				break;
		}
	}
	
	/**
	 * Can we convert passwords from this software.
	 *
	 * @return 	boolean
	 */
	public static function loginEnabled()
	{
		return TRUE;
	}

	/**
	 * List of conversion methods that require additional information
	 *
	 * @return	array
	 */
	public static function checkConf()
	{
		return array(
			'convertForumsForums',
			'convertAttachments',
			'convertForumsPosts'
		);
	}

	/**
	 * Get More Information
	 *
	 * @param	string	$method	Conversion method
	 * @return	array
	 */
	public function getMoreInfo( $method )
	{
		$return = array();

		switch( $method )
		{
			case 'convertForumsForums':
				$return['convertForumsForums'] = array();

				/* Find out where the photos live */
				\IPS\Member::loggedIn()->language()->words['attach_location_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'attach_location' );
				$return['convertForumsForums']['attach_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> \IPS\Member::loggedIn()->language()->addToStack('convert_vanilla_photopath'),
				);
				break;
			case 'convertForumsPosts':
				/* Get our reactions to let the admin map them - this is a Vanilla2 addon so it may not be installed */
				if( static::$_supportsReactions )
				{
					$options		= array();
					$descriptions	= array();
					foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_reactions' ), 'IPS\Content\Reaction' ) AS $reaction )
					{
						$options[ $reaction->id ]		= $reaction->_icon->url;
						$descriptions[ $reaction->id ]	= \IPS\Member::loggedIn()->language()->addToStack('reaction_title_' . $reaction->id ) . '<br>' . $reaction->_description;
					}

					$return['convertForumsPosts'] = array();

					foreach( $this->db->select( '*', 'Action' ) as $reaction )
					{
						\IPS\Member::loggedIn()->language()->words['reaction_' . $reaction['ActionID'] ] = $reaction['Name'];
						\IPS\Member::loggedIn()->language()->words['reaction_' . $reaction['ActionID'] . '_desc' ] = \IPS\Member::loggedIn()->language()->addToStack('reaction_convert_help');

						$return['convertForumsPosts']['reaction_' . $reaction['ActionID'] ] = array(
							'field_class'		=> 'IPS\\Helpers\\Form\\Radio',
							'field_default'		=> NULL,
							'field_required'	=> TRUE,
							'field_extra'		=> array( 'parse' => 'image', 'options' => $options, 'descriptions' => $descriptions ),
							'field_hint'		=> NULL,
							'field_validation'	=> NULL,
						);
					}
				}
				break;
			case 'convertAttachments':
				$return['convertAttachments'] = array(
					'attach_location'	=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> \IPS\Member::loggedIn()->language()->addToStack('convert_vanilla_photopath'),
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					),
				);
				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return	array		Messages to display
	 */
	public function finish()
	{
		/* Content Rebuilds */
		\IPS\Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\forums\Forum', 'count' => 0 ), 5, array( 'class' ) );
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'forums_posts', 'class' => 'IPS\forums\Topic\Post' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'convert', 'RebuildFirstPostIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		\IPS\Task::queue( 'convert', 'DeleteEmptyTopics', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );

		/* Rebuild Leaderboard */
		\IPS\Task::queue( 'core', 'RebuildReputationLeaderboard', array(), 4 );
		\IPS\Db::i()->delete('core_reputation_leaderboard_history');

		/* Caches */
		\IPS\Task::queue( 'convert', 'RebuildTagCache', array( 'app' => $this->app->app_id, 'link' => 'forums_topics', 'class' => 'IPS\forums\Topic' ), 3, array( 'app', 'link', 'class' ) );

		return array( "f_forum_last_post_data", "f_rebuild_posts", "f_recounting_forums", "f_recounting_topics", "f_topic_tags_recount" );
	}
	
	/**
	 * Fix post data
	 *
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 */
	public static function fixPostData( $post )
	{
		/**
		 * Vanilla has a quotes plugin that seems to have changed formats quite often. - We'll try to match as many formats as we can
		 */
		$post = preg_replace( '/(?:<blockquote\s+(?:class=\"(?:User)?Quote\")?(?:\s+rel=\"(?:[^\"]+)\")?>)(?:\s+)?<div class=\"QuoteAuthor\">([^\"]+)<\/div>(?:\s+)?<div class=\"QuoteText\">(?:<p>)?(.*?)(?:<\/p>)?<\/div>(?:\s+)?<\/blockquote>/i',
				'[quote name="$1"]$2[/quote]' ,
				$post );

		$post = preg_replace( '/(?:<blockquote\s+(?:class=\"Quote (?:User)?Quote\")?(?:\s+rel=\"(?:[^\"]+)\")?>)(?:\s+)?<div class=\"QuoteText\">(?:<p>)?(.*?)(?:<\/p>)?<\/div>(?:\s+)?<\/blockquote>/i',
				'[quote]$1[/quote]' ,
				$post );

		$post = preg_replace( '/<blockquote\s+rel=\"([^\"]+)\"?>(.*?)<\/blockquote>/i',
				'[quote name="$1"]$2[/quote]' ,
				$post );

		return $post;
	}

	/**
	 * Convert attachments
	 *
	 * @return	void
	 */
	public function convertAttachments()
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'MediaID' );

		foreach( $this->fetch( 'Media', 'MediaID', array( 'ForeignTable=? OR ForeignTable=?', 'discussion', 'comment' ) ) AS $row )
		{
			if( $row['ForeignTable'] == 'discussion' )
			{
				$map = array(
					'id1'	=> 'fp-' . $row['ForeignID'],
					'id2'	=> $row['ForeignID'],
				);
			}
			else
			{
				try
				{
					$discussionId = $this->db->select( 'DiscussionID', 'Comment', array( 'CommentID=?', $row['ForeignID'] ) )->first();
				}
				catch( \UnderflowException $ex )
				{
					$libraryClass->setLastKeyValue( $row['MediaID'] );
				}

				$map = array(
					'id1'	=> $discussionId,
					'id2'	=> $row['ForeignID'],
				);
			}

			/* File extension */
			$ext = explode( '.', $row['Path'] );
			$ext = array_pop( $ext );

			$info = array(
				'attach_id'			=> $row['MediaID'],
				'attach_file'		=> $row['Name'],
				'attach_date'		=> VanillaCore::mysqlToDateTime( $row['DateInserted'] ),
				'attach_member_id'	=> $row['InsertUserID'],
				'attach_hits'		=> 0,
				'attach_ext'		=> $ext,
				'attach_filesize'	=> $row['Size'],
			);

			$libraryClass->convertAttachment( $info, $map, rtrim( $this->app->_session['more_info']['convertAttachments']['attach_location'], '/' ) . '/' . trim( $row['Path'], '/' ) );
			$libraryClass->setLastKeyValue( $row['MediaID'] );
		}
	}

	/**
	 * Convert forums
	 *
	 * @return	void
	 */
	public function convertForumsForums()
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'c.CategoryID' );

		$uploadsPath = $this->app->_session['more_info']['convertForumsForums']['attach_location'];

		$forums = $this->fetch( array( 'Category', 'c' ), 'CategoryID', array( 'c.CategoryID<>?', -1 ), 
			'c.*, lcu.UserID as LastCommentUserID, lcu.Name as LastCommentUserName, ld.Name as LastDiscussionName' 
		);
		$forums->join( array( 'User', 'lcu' ), 'c.LastCommentID=lcu.UserID' );
		$forums->join( array( 'Discussion', 'ld' ), 'c.LastDiscussionID=ld.DiscussionID' );

		foreach( $forums AS $row )
		{
			$icon = ( isset( $row['Icon'] ) AND $row['Icon'] ) ? VanillaCore::parseMediaLocation( $row['Icon'], $uploadsPath ) : NULL;
			$info = [
				'id'                => $row['CategoryID'],
				'name'              => $row['Name'],
				'description'       => $row['Description'],
				'topics'            => $row['CountDiscussions'],
				'posts'             => $row['CountComments'],
				'last_post'         => VanillaCore::mysqlToDateTime( $row['LastDateInserted'] ),
				'last_poster_id'    => $row['LastCommentID'],
				'last_poster_name'  => $row['LastCommentUserName'],
				'parent_id'         => ( (int) $row['ParentCategoryID'] > 0 ) ? $row['ParentCategoryID'] : NULL,
				'position'          => $row['Sort'],
				'last_title'        => $row['LastDiscussionName'],
				'icon'              => $icon,
				'sub_can_post'		=> $row['AllowDiscussions'] ?: 0
			];

			$libraryClass->convertForumsForum( $info, NULL, $icon );
			$libraryClass->setLastKeyValue( $row['CategoryID'] );
		}
	}

	/**
	 * Convert topics
	 *
	 * @return	void
	 */
	public function convertForumsTopics()
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'd.DiscussionID' );

		$discussions = $this->fetch( array( 'Discussion', 'd' ), 'DiscussionID', NULL, 
			'd.*, u.Name as UserName, lcu.UserID as LastCommentUserID, lcu.Name as LastCommentUserName'
		);
		$discussions->join( array( 'User', 'u' ), 'd.InsertUserID=u.UserID' );
		$discussions->join( array( 'User', 'lcu' ), 'd.LastCommentUserID=lcu.UserID' );

		foreach( $discussions AS $row )
		{
			$row['DateLastComment'] = 0;
			$row['LastCommentUserID'] = 0;
			$row['LastCommentUserName'] = '';

			/* If last post info is empty, fetch it */
			if( $row['DateLastComment'] === NULL )
			{
				try
				{
					$data = $this->db->select( 'Comment.InsertUserID, Comment.DateInserted, User.Name', 'Comment', array( 'DiscussionID=?', $row['DiscussionID'] ), 'CommentID DESC', array( 0, 1 ) )
								->join( 'User', 'Comment.InsertUserID=User.UserID' )
								->first();

					$row['DateLastComment'] = $data['DateInserted'];
					$row['LastCommentUserID'] = $data['InsertUserId'];
					$row['LastCommentUserName'] = $data['Name'];
				}
				catch( \UnderflowException $e ) {}
			}

			$info = array(
				'tid'               => $row['DiscussionID'],
				'title'				=> $row['Name'],
				'forum_id'			=> $row['CategoryID'],
				'state'				=> ( $row['Closed'] == 0 ) ? 'open' : 'closed',
				'posts'				=> $row['CountComments'],
				'starter_id'		=> $row['InsertUserID'],
				'start_date'		=> VanillaCore::mysqlToDateTime( $row['DateInserted'] ),
				'last_poster_id'	=> $row['LastCommentUserID'],
				'last_post'			=> VanillaCore::mysqlToDateTime( $row['DateLastComment'] ),
				'starter_name'		=> $row['UserName'],
				'last_poster_name'	=> $row['LastCommentUserName'],
				'views'				=> $row['CountViews'],
			);

			$libraryClass->convertForumsTopic( $info );

			/* Tags */
			if( !empty( $row['Tags'] ) )
			{
				$tags = explode( ',', $row['Tags'] );

				if ( count( $tags ) )
				{
					foreach( $tags AS $tag )
					{
						$toConvert = explode( ' ', $tag );
						foreach( $toConvert as $spacedTag )
						{
							$libraryClass->convertTag( array(
								'tag_meta_app'			=> 'forums',
								'tag_meta_area'			=> 'forums',
								'tag_meta_parent_id'	=> $row['CategoryID'],
								'tag_meta_id'			=> $row['DiscussionID'],
								'tag_text'				=> $spacedTag,
								'tag_member_id'			=> $row['InsertUserID'],
								'tag_prefix'			=> 0,
							) );
						}
					}
				}
			}

			$libraryClass->setLastKeyValue( $row['DiscussionID'] );
		}
	}

	/**
	 * Convert posts
	 *
	 * @return	void
	 */
	public function convertForumsPosts()
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'DiscussionID' );

		foreach( $this->fetch( 'Discussion', 'DiscussionID' ) AS $row )
		{
			$editName = NULL;

			if( $row['UpdateUserID'] )
			{
				try
				{
					$editName = $this->db->select( 'Name', 'User', array( 'UserID=?', $row['UpdateUserID'] ) )->first();
				}
				catch( \UnderflowException $e ) {}
			}

			// First post
			$info = array(
				'pid'           => 'fp-' . $row['DiscussionID'],
				'topic_id'      => $row['DiscussionID'],
				'post'          => $row['Body'],
				'new_topic'     => 1,
				'edit_time'     => ( $editName === NULL ) ? NULL : VanillaCore::mysqlToDateTime( $row['DateUpdated'] ),
				'edit_name'		=> $editName,
				'author_id'     => $row['InsertUserID'],
				'ip_address'    => $row['InsertIPAddress'],
				'post_date'     => VanillaCore::mysqlToDateTime( $row['DateInserted'] ),
			);

			$libraryClass->convertForumsPost( $info );
			$libraryClass->setLastKeyValue( $row['DiscussionID'] );

			/* Reputation - Reactions are only supported if the YAGA addon was used. */
			if( static::$_supportsReactions )
			{
				foreach( $this->db->select( '*', 'Reaction', array( "ParentType=? AND ParentID=?", 'comment', $row['DiscussionID'] ) ) AS $rep )
				{
					$reaction = $this->app->_session['more_info']['convertForumsPosts']['reaction_' .  $rep['ActionID'] ];

					$libraryClass->convertReputation( array(
						'id'				=> $rep['ReactionID'],
						'app'				=> 'forums',
						'type'				=> 'pid',
						'type_id'			=> 'fp-' . $row['DiscussionID'],
						'member_id'			=> $rep['InsertUserID'],
						'member_received'	=> $rep['ParentAuthorID'],
						'rep_date'			=> VanillaCore::mysqlToDateTime( $row['DateInserted'] ),
						'reaction'			=> $reaction
					) );
				}
			}
		}
	}

	/**
	 * Convert other posts
	 *
	 * @return	void
	 */
	public function convertForumsPosts2()
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'CommentID' );

		foreach( $this->fetch( 'Comment', 'CommentID' ) AS $row )
		{
			$editName = NULL;

			if( $row['UpdateUserID'] )
			{
				try
				{
					$editName = $this->db->select( 'Name', 'User', array( 'UserID=?', $row['UpdateUserID'] ) )->first();
				}
				catch( \UnderflowException $e ) {}
			}

			$info = [
				'pid'        => $row['CommentID'],
				'topic_id'   => $row['DiscussionID'],
				'post'       => $row['Body'],
				'edit_time'  => ( $editName === NULL ) ? NULL : VanillaCore::mysqlToDateTime( $row['DateUpdated'] ),
				'edit_name'	 => $editName,
				'author_id'  => $row['InsertUserID'],
				'ip_address' => $row['InsertIPAddress'],
				'post_date'  => VanillaCore::mysqlToDateTime( $row['DateInserted'] ),
			];

			$libraryClass->convertForumsPost( $info );
			$libraryClass->setLastKeyValue( $row['CommentID'] );
		}
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return	NULL|\IPS\Http\Url
	 * @note	Forums and profiles don't use an ID in the URL. While we may be able to somehow cross reference this with our SEO slug, it wouldn't be reliable.
	 */
	public function checkRedirects()
	{
		$url = \IPS\Request::i()->url();

		if( preg_match( '#/discussion/([0-9]+)/#i', $url->data[ \IPS\Http\Url::COMPONENT_PATH ], $matches ) )
		{
			try
			{
				try
				{
					$data = (string) $this->app->getLink( (int) $matches[1], array( 'topics', 'forums_topics' ) );
				}
				catch( \OutOfRangeException $e )
				{
					$data = (string) $this->app->getLink( (int) $matches[1], array( 'topics', 'forums_topics' ), FALSE, TRUE );
				}
				$item = \IPS\forums\Topic::load( $data );

				if( $item->canView() )
				{
					return $item->url();
				}
			}
			catch( \Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}
}