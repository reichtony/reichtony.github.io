<?php
/**
 * @brief		Base API endpoint for Content Items
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Dec 2015
 */

namespace IPS\Content\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base API endpoint for Content Items
 */
class _ItemController extends \IPS\Api\Controller
{
	/**
	 * List
	 *
	 * @param	array	$where			Extra WHERE clause
	 * @param	string	$containerParam	The parameter which includes the container values
	 * @param	bool	$byPassPerms	If permissions should be ignored
	 * @return	\IPS\Api\PaginatedResponse
	 */
	protected function _list( $where = array(), $containerParam = 'categories', $byPassPerms=FALSE )
	{
		$class = $this->class;
		
		/* Containers */
		if ( $containerParam and isset( \IPS\Request::i()->$containerParam ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->$containerParam ) ) ) ) );
		}
		
		/* Authors */
		if ( isset( \IPS\Request::i()->authors ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['author'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->authors ) ) ) ) );
		}
		
		/* Pinned? */
		if ( isset( \IPS\Request::i()->pinned ) AND in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->pinned )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=0" );
			}
		}
		
		/* Featured? */
		if ( isset( \IPS\Request::i()->featured ) AND in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->featured )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=0" );
			}
		}
		
		/* Locked? */
		if ( isset( \IPS\Request::i()->locked ) AND in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			if ( isset( $class::$databaseColumnMap['locked'] ) )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', intval( \IPS\Request::i()->locked ) );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['state'] . '=?', \IPS\Request::i()->locked ? 'closed' : 'open' );
			}
		}
		
		/* Hidden */
		if ( isset( \IPS\Request::i()->hidden ) AND in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->hidden )
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '<>0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '<>1' );
				}
			}
			else
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		/* Has poll? */
		if ( isset( \IPS\Request::i()->hasPoll ) AND in_array( 'IPS\Content\Polls', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->hasPoll )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . ">0" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . "=0" );
			}
		}
		
		/* Sort */
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'date', 'title' ) ) )
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnMap[ \IPS\Request::i()->sortBy ];
		}
		else
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnId;
		}
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		/* Get results */
		if ( $this->member and !$byPassPerms )
		{
			$query = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'view', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, $this->member )->getInnerIterator();
			$count = $class::getItemsWithPermission( $where, "{$sortBy} {$sortDir}", NULL, 'view', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, $this->member, FALSE, FALSE, FALSE, TRUE );
		}
		else
		{
			$query = \IPS\Db::i()->select( '*', $class::$databaseTable, $where, "{$sortBy} {$sortDir}" );
			$count = \IPS\Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->first();
		}
		
		/* Return */
		return new \IPS\Api\PaginatedResponse(
			200,
			$query,
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			$class,
			$count,
			$this->member,
			isset( \IPS\Request::i()->perPage ) ? \IPS\Request::i()->perPage : NULL
		);
	}
	
	/**
	 * View
	 *
	 * @param	int	$id	ID Number
	 * @return	\IPS\Api\Response
	 */
	protected function _view( $id )
	{
		$class = $this->class;
		
		$item = $class::load( $id );
		if ( $this->member and !$item->can( 'read', $this->member ) )
		{
			throw new \OutOfRangeException;
		}
		
		return new \IPS\Api\Response( 200, $item->apiOutput( $this->member ) );
	}

	/**
	 * Create or update item
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item, $type='add' )
	{
		/* Title */
		if ( isset( \IPS\Request::i()->title ) and isset( $item::$databaseColumnMap['title'] ) )
		{
			$titleColumn = $item::$databaseColumnMap['title'];
			$item->$titleColumn = \IPS\Request::i()->title;
		}
		
		/* Tags */
		if ( ( isset( \IPS\Request::i()->prefix ) or isset( \IPS\Request::i()->tags ) ) and in_array( 'IPS\Content\Tags', class_implements( get_class( $item ) ) ) )
		{
			if ( !$this->member or $item::canTag( $this->member, $item->containerWrapper() ) )
			{			
				$tags = isset( \IPS\Request::i()->tags ) ? array_filter( explode( ',', \IPS\Request::i()->tags ) ) : $item->tags();
				
				if ( !$this->member or $item::canPrefix( $this->member, $item->containerWrapper() ) )
				{
					if ( isset( \IPS\Request::i()->prefix ) )
					{
						if ( \IPS\Request::i()->prefix )
						{
							$tags['prefix'] = \IPS\Request::i()->prefix;
						}
					}
					elseif ( $existingPrefix = $item->prefix() )
					{
						$tags['prefix'] = $existingPrefix;
					}
				}
	
				/* we need to save the item before we set the tags because setTags requires that the item exists */
				$idColumn = $item::$databaseColumnId;
				if ( !$item->$idColumn )
				{
					$item->save();
				}
	
				$item->setTags( $tags );
			}
		}
		
		/* Open/closed */
		if ( isset( \IPS\Request::i()->locked ) and in_array( 'IPS\Content\Lockable', class_implements( get_class( $item ) ) ) )
		{
			if ( !$this->member or ( \IPS\Request::i()->locked and $item->canLock( $this->member ) ) or ( !\IPS\Request::i()->locked and $item->canUnlock( $this->member ) ) )
			{
				if ( isset( $item::$databaseColumnMap['locked'] ) )
				{
					$lockedColumn = $item::$databaseColumnMap['locked'];
					$item->$lockedColumn = intval( \IPS\Request::i()->locked );
				}
				else
				{
					$stateColumn = $item::$databaseColumnMap['status'];
					$item->$stateColumn = \IPS\Request::i()->locked ? 'closed' : 'open';
				}
			}
		}
		
		/* Hidden */
		if ( isset( \IPS\Request::i()->hidden ) and in_array( 'IPS\Content\Hideable', class_implements( get_class( $item ) ) ) )
		{
			if ( !$this->member or ( \IPS\Request::i()->hidden and $item->canHide( $this->member ) ) or ( !\IPS\Request::i()->hidden and $item->canUnhide( $this->member ) ) )
			{
				$idColumn = $item::$databaseColumnId;
				if ( \IPS\Request::i()->hidden )
				{
					if ( $item->$idColumn )
					{
						$item->hide( FALSE );
					}
					else
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$hiddenColumn = $item::$databaseColumnMap['hidden'];
							$item->$hiddenColumn = \IPS\Request::i()->hidden;
						}
						else
						{
							$approvedColumn = $item::$databaseColumnMap['approved'];
							$item->$approvedColumn = ( \IPS\Request::i()->hidden == -1 ) ? -1 : 0;
						}
					}
				}
				else
				{
					if ( $item->$idColumn )
					{
						$item->unhide( FALSE );
					}
					else
					{
						if ( isset( $item::$databaseColumnMap['hidden'] ) )
						{
							$hiddenColumn = $item::$databaseColumnMap['hidden'];
							$item->$hiddenColumn = 0;
						}
						else
						{
							$approvedColumn = $item::$databaseColumnMap['approved'];
							$item->$approvedColumn = 1;
						}
					}
				}
			}
		}
		
		/* Pinned */
		if ( isset( \IPS\Request::i()->pinned ) and in_array( 'IPS\Content\Pinnable', class_implements( get_class( $item ) ) ) )
		{
			if ( !$this->member or ( \IPS\Request::i()->pinned and $item->canPin( $this->member ) ) or ( !\IPS\Request::i()->pinned and $item->canUnpin( $this->member ) ) )
			{
				$pinnedColumn = $item::$databaseColumnMap['pinned'];
				$item->$pinnedColumn = intval( \IPS\Request::i()->pinned );
			}
		}
		
		/* Featured */
		if ( isset( \IPS\Request::i()->featured ) and in_array( 'IPS\Content\Featurable', class_implements( get_class( $item ) ) ) )
		{
			if ( !$this->member or ( \IPS\Request::i()->featured and $item->canFeature( $this->member ) ) or ( !\IPS\Request::i()->featured and $item->canUnfeature( $this->member ) ) )
			{
				$featuredColumn = $item::$databaseColumnMap['featured'];
				$item->$featuredColumn = intval( \IPS\Request::i()->featured );
			}
		}

		/* Update first comment if required, and it's not a new item */
		$field = isset( $item::$databaseColumnMap['first_comment_id'] ) ? $item::$databaseColumnMap['first_comment_id'] : NULL;
		$commentClass = $item::$commentClass;
		$contentField = $commentClass::$databaseColumnMap['content'];
		if ( $item::$firstCommentRequired AND isset( $item->$field ) AND isset( \IPS\Request::i()->$contentField ) AND $type == 'edit' )
		{
			$content = \IPS\Request::i()->$contentField;
			if ( $this->member )
			{
				$content = \IPS\Text\Parser::parseStatic( $content, TRUE, NULL, $this->member, $item::$application . '_' . mb_ucfirst( $item::$module ) );
			}

			try
			{
				$comment = $commentClass::load( $item->$field );
			}
			catch ( \OutOfRangeException $e )
			{
				throw new \IPS\Api\Exception( 'NO_FIRST_POST', '1S377/1', 400 );
			}

			$comment->$contentField = $content;
			$comment->save();

			/* Update Search Index of the first item */
			if ( $item instanceof \IPS\Content\Searchable )
			{
				\IPS\Content\Search\Index::i()->index( $comment );
			}
		}
		
		/* Return */
		return $item;
	}

	
	/**
	 * Create
	 *
	 * @param	\IPS\Node\Model	$container			Container
	 * @param	\IPS\Member		$author				Author
	 * @param	string			$firstPostParam		The parameter which contains the body for the first comment
	 * @return	\IPS\Content\Item
	 */
	protected function _create( \IPS\Node\Model $container = NULL, \IPS\Member $author, $firstPostParam = 'post' )
	{
		$class = $this->class;
		
		/* Work out the date */
		$date = ( !$this->member and \IPS\Request::i()->date ) ? new \IPS\DateTime( \IPS\Request::i()->date ) : \IPS\DateTime::create();
		
		/* Create item */
		$item = $class::createItem( $author, ( !$this->member and \IPS\Request::i()->ip_address ) ? \IPS\Request::i()->ip_address : \IPS\Request::i()->ipAddress(), $date, $container );
		$this->_createOrUpdate( $item, 'add' );
		$item->save();
		
		/* Create post */
		if ( $class::$firstCommentRequired )
		{			
			$postContents = \IPS\Request::i()->$firstPostParam;
			
			if ( $this->member )
			{
				$postContents = \IPS\Text\Parser::parseStatic( $postContents, TRUE, NULL, $this->member, $class::$application . '_' . mb_ucfirst( $class::$module ) );
			}
			
			$commentClass = $item::$commentClass;
			$post = $commentClass::create( $item, $postContents, TRUE, $author->member_id ? NULL : $author->real_name, NULL, $author, $date );
			
			if ( isset( $class::$databaseColumnMap['first_comment_id'] ) )
			{
				$firstCommentColumn = $class::$databaseColumnMap['first_comment_id'];
				$commentIdColumn = $commentClass::$databaseColumnId;
				$item->$firstCommentColumn = $post->$commentIdColumn;
				$item->save();
			}
		}
		
		/* Index */
		if ( $item instanceof \IPS\Content\Searchable )
		{
			\IPS\Content\Search\Index::i()->index( $item );
		}
		
		/* Send notifications */
		if ( !$item->hidden() )
		{
			$item->sendNotifications();
		}
		elseif( $item->hidden() !== -1 )
		{
			$item->sendUnapprovedNotification();
		}
		
		/* Output */
		return $item;
	}
	
	/**
	 * View Comments or Reviews
	 *
	 * @param	int		$id				ID Number
	 * @param	string	$commentClass	The class
	 * @param	array	$where			Base where clause
	 * @return	\IPS\Api\PaginatedResponse
	 */
	protected function _comments( $id, $commentClass, $where = array() )
	{
		/* Init */
		$itemClass = $this->class;
		$item = $itemClass::load( $id );
		if ( $this->member and !$item->can( 'read', $this->member ) )
		{
			throw new \OutOfRangeException;
		}
		$itemIdColumn = $itemClass::$databaseColumnId;
		$where [] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$itemIdColumn );
		
		/* Hidden? */
		if ( isset( \IPS\Request::i()->hidden ) AND in_array( 'IPS\Content\Hideable', class_implements( $commentClass ) ) )
		{
			if ( \IPS\Request::i()->hidden )
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '<>0' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '<>1' );
				}
			}
			else
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}
		
		/* Sort */
		$sortBy = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'];
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', $commentClass::$databaseTable, $where, "{$sortBy} {$sortDir}" ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			$commentClass,
			\IPS\Db::i()->select( 'COUNT(*)', $commentClass::$databaseTable, $where )->first(),
			$this->member,
			isset( \IPS\Request::i()->perPage ) ? \IPS\Request::i()->perPage : NULL
		);
	}
}