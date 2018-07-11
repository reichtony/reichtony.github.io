<?php
/**
 * @brief		Elasticsearch Search Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 Oct 2017
*/

namespace IPS\Content\Search\Elastic;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Elasticsearch Search Index
 */
class _Index extends \IPS\Content\Search\Index
{
	/**
	 * @brief	The server URL
	 */
	protected $url;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	$url	The server URL
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url )
	{
		$this->url = $url;
	}
	
	/**
	 * Initalize when first setting up
	 *
	 * @return	void
	 */
	public function init()
	{		
		$this->url->request()->delete();
		$this->url->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->put( json_encode( array(
			'settings'	=> array(
				'max_result_window'	=> \IPS\Settings::i()->search_index_maxresults
			),
			'mappings'	=> array(
				'content' 	=> array(
					'properties'	=> array(
						'index_class'				=> array( 'type' => 'keyword' ),
						'index_object_id'			=> array( 'type' => 'long' ),
						'index_item_id'				=> array( 'type' => 'long' ),
						'index_container_class'		=> array( 'type' => 'keyword' ),
						'index_container_id'		=> array( 'type' => 'long' ),
						'index_title'				=> array(
							'type' 		=> 'text',
							'analyzer'	=> \IPS\Settings::i()->search_elastic_analyzer,
						),
						'index_content'				=> array(
							'type' 		=> 'text',
							'analyzer'	=> \IPS\Settings::i()->search_elastic_analyzer,
						),
						'index_permissions'			=> array( 'type' => 'keyword' ),
						'index_date_created'		=> array(
							'type' 		=> 'date',
							'format'	=> 'epoch_second',
						),
						'index_date_updated'		=> array(
							'type' 		=> 'date',
							'format'	=> 'epoch_second',
						),
						'index_date_commented'		=> array(
							'type' 		=> 'date',
							'format'	=> 'epoch_second',
						),
						'index_author'				=> array( 'type' => 'long' ),
						'index_tags'				=> array( 'type' => 'keyword' ),
						'index_prefix'				=> array( 'type' => 'keyword' ),
						'index_hidden'				=> array( 'type' => 'byte' ),
						'index_item_index_id'		=> array( 'type' => 'keyword' ),
						'index_item_author'			=> array( 'type' => 'long' ),
						'index_is_last_comment'		=> array( 'type' => 'boolean' ),
						'index_club_id'				=> array( 'type' => 'long' ),
						'index_class_type_id_hash'	=> array( 'type' => 'keyword' ),
						'index_views'				=> array( 'type' => 'long' ),
						'index_comments'			=> array( 'type' => 'long' ),
						'index_reviews'				=> array( 'type' => 'long' ),
						'index_participants'		=> array( 'type' => 'long' ),
					)
				)
			)
		) ) );		
	}
	
	/**
	 * Get index data
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	array|NULL
	 */
	public function indexData( \IPS\Content\Searchable $object )
	{
		if ( $indexData = parent::indexData( $object ) )
		{
			$indexData['index_permissions'] = explode( ',', $indexData['index_permissions'] );
			$indexData['index_tags'] = $indexData['index_tags'] ? explode( ',', $indexData['index_tags'] ) : array();
			$indexData['index_is_last_comment'] = (bool) $indexData['index_is_last_comment'];
			
			if ( $object instanceof \IPS\Content\Item )
			{
				$indexData += $this->metaCounts( $object );
			}
			else
			{
				$indexData += $this->metaCounts( $object->item() );
			}

			return $indexData;
		}
		
		return NULL;
	}
			
	/**
	 * Index an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	public function index( \IPS\Content\Searchable $object )
	{
		if ( $indexData = $this->indexData( $object ) )
		{
			/* If nobody has permission to access it, just remove it */
			if ( !$indexData['index_permissions'] )
			{
				$this->removeFromSearchIndex( $object );
			}
			/* Otherwise, go ahead... */
			else
			{
				try
				{
					$class = get_class( $object );
					
					$resetLastComment = FALSE;				
					/* Do not allow hidden comments to be flagged as latest */
					if ( $object instanceof \IPS\Content\Comment and $indexData['index_is_last_comment'] and $indexData['index_item_id'] and $indexData['index_hidden'] !== 0 ) // 
					{
						$resetLastComment = TRUE;
						
						$indexData['index_is_last_comment'] = false;
					}
										
					/* Insert into index */
					$r = $this->url
						->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/' . $this->getIndexId( $object ) )
						->request()
						->setHeaders( array( 'Content-Type' => 'application/json' ) )
						->put( json_encode( $indexData ) );
						
					/* Views / Comments / Reviews */
					if ( $object instanceof \IPS\Content\Item )
					{
						$item = $object;
					}
					elseif ( $object instanceof \IPS\Content\Comment )
					{
						$item = $object->item();
					}
					$this->rebuildMetaCounts( $item, $indexData['index_is_last_comment'] ? $this->getIndexId( $object ) : NULL );
				}
				catch ( \IPS\Http\Request\Exception $e )
				{
					\IPS\Log::log( $e, 'elasticsearch' );
				}
				
				if ( $resetLastComment )
				{
					$this->resetLastComment( array( $indexData['index_class'] ), $indexData['index_item_id'] );
				}
			}
		}
	}
	
	/**
	 * Update view count
	 *
	 * @param	string	$class  Class to update
	 * @param	int		$id		ID of item
	 * @param	int		$count	Count to update
	 * @throws \OutOfRangeException	When table to update no longer exists
	 */
	public function updateViewCounts( $class, $id, $count )
	{
		$classes = array( $class );
		if ( isset( $class::$commentClass ) )
		{
			$classes[] = $class::$commentClass;
		}
		if ( isset( $class::$reviewClass ) )
		{
			$classes[] = $class::$reviewClass;
		}
		
		$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
			'script'	=> array(
				'inline'	=> "ctx._source.index_views += ". intval( $count ) . ";",
				'lang'		=> 'painless'
			),
			'query'		=> array(
				'bool'		=> array(
					'must'		=> array(
						array(
							'terms'	=> array(
								'index_class' => $classes
							)
						),
						array(
							'term'	=> array(
								'index_item_id' => $id
							)
						),
					)
				)
			)
		) ) );
	}
	
	/**
	 * Get the comment / review counts for an item
	 *
	 * @param	\IPS\Content\Searchable	$item					The content item
	 * @return	void
	 */
	protected function metaCounts( $item )
	{
		$databaseColumnId = $item::$databaseColumnId;
		
		$participants = array( $item->mapped('author') );
		if ( isset( $item::$commentClass ) )
		{
			$commentClass = $item::$commentClass;
			$participants += iterator_to_array( \IPS\Db::i()->select( 'DISTINCT ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'], $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$databaseColumnId ) ) );
		}
		if ( isset( $item::$reviewClass ) )
		{
			$reviewClass = $item::$reviewClass;
			$participants += iterator_to_array( \IPS\Db::i()->select( 'DISTINCT ' . $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['author'], $reviewClass::$databaseTable, array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['item'] . '=?', $item->$databaseColumnId ) ) );
		}
		$participants = array_values( array_unique( $participants ) );
		
		return array(
			'index_views'			=> $item->mapped('views'),
			'index_comments'		=> $item->mapped('num_comments'),
			'index_reviews'			=> $item->mapped('num_reviews'),
			'index_participants'	=> $participants
		);
	}
	
	/**
	 * Rebuild the comment / review counts for an item
	 *
	 * @param	\IPS\Content\Searchable	$item					The content item
	 * @param	string					$lastCommentIndexId		The index ID of the last comment, if you want to reset others
	 * @return	void
	 */
	protected function rebuildMetaCounts( $item, $lastCommentIndexId = NULL )
	{
		$databaseColumnId = $item::$databaseColumnId;
		$class = get_class( $item );
		$classes = array( $class );
		if ( isset( $class::$commentClass ) )
		{
			$classes[] = $class::$commentClass;
		}
		if ( isset( $class::$reviewClass ) )
		{
			$classes[] = $class::$reviewClass;
		}
		
		try
		{			
			$updates = array();
			foreach ( $this->metaCounts( $item ) as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$updates[] = "ctx._source.{$k} = [" . implode( ',', array_map( 'intval', $v ) ) . "];";
				}
				elseif ( is_null( $v ) )
				{
					$updates[] = "ctx._source.{$k} = null;";
				}
				else
				{
					$updates[] = "ctx._source.{$k} = " . intval( $v ) . ';';
				}
			}
			if ( !is_null( $lastCommentIndexId ) )
			{
				$updates[] = "ctx._source.index_is_last_comment = ( ctx._id == '{$lastCommentIndexId}' );";
			}
			$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
				'script'	=> array(
					'inline'	=> implode( ' ', $updates ),
					'lang'		=> 'painless'
				),
				'query'		=> array(
					'bool'		=> array(
						'must'		=> array(
							array(
								'terms'	=> array(
									'index_class' => $classes
								)
							),
							array(
								'term'	=> array(
									'index_item_id' => $item->$databaseColumnId
								)
							),
						)
					)
				)
			) ) );
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Retrieve the search ID for an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	public function getIndexId( \IPS\Content\Searchable $object )
	{
		$databaseColumnId = $object::$databaseColumnId;
		return \strtolower( str_replace( '\\', '_', \substr( get_class( $object ), 4 ) ) ) . '-' . $object->$databaseColumnId;
	}
	
	/**
	 * Remove item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to remove
	 * @return	void
	 */
	public function removeFromSearchIndex( \IPS\Content\Searchable $object )
	{
		try
		{
			$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/' . $this->getIndexId( $object ) )->request()->delete();
			
			if ( $object instanceof \IPS\Content\Item )
			{				
				$class = get_class( $object );
				$idColumn = $class::$databaseColumnId;
				if ( isset( $class::$commentClass ) )
				{
					$commentClass = $class::$commentClass;
					$response = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_delete_by_query' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
						'query'	=> array(
							'bool' => array(
								'must' => array(
									array(
										'term'	=> array(
											'index_class' => $commentClass
										)
									),
									array(
										'term'	=> array(
											'index_item_id' => $object->$idColumn
										)
									),
								)
							)
									
						)
					) ) );
				}
				if ( isset( $class::$reviewClass ) )
				{
					$reviewClass = $class::$reviewClass;
					$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_delete_by_query' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
						'query'	=> array(
							'bool' => array(
								'must' => array(
									array(
										'term'	=> array(
											'index_class' => $reviewClass
										)
									),
									array(
										'term'	=> array(
											'index_item_id' => $object->$idColumn
										)
									),
								)
							)
									
						)
					) ) );
				}
			}	
			else
			{
				$this->rebuildMetaCounts( $object->item() );
			}		
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Removes all content for a classs
	 *
	 * @param	string		$class 	The class
	 * @param	int|NULL	$containerId		The container ID to delete, or NULL
	 * @param	int|NULL	$authorId			The author ID to delete, or NULL
	 * @return	void
	 */
	public function removeClassFromSearchIndex( $class, $containerId=NULL, $authorId=NULL )
	{
		try
		{
			if ( $containerId or $authorId )
			{
				$query = array(
					'bool'	=> array(
						'must'	=> array(
							array(
								'term'	=> array(
									'index_class' => $class
								)
							)
						)
					)
				);
				
				if ( $containerId )
				{
					$query['bool']['must'][] = array(
						'term'	=> array(
							'index_container_id' => $containerId
						)
					);
				}
				
				if ( $authorId )
				{
					$query['bool']['must'][] = array(
						'term'	=> array(
							'index_author' => $authorId
						)
					);
				}				
				
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_delete_by_query' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> $query
				) ) );
			}
			else
			{
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_delete_by_query' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> array(
						'term'	=> array(
							'index_class' => $class
						)
					)
				) ) );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Mass Update (when permissions change, for example)
	 *
	 * @param	string				$class 						The class
	 * @param	int|NULL			$containerId				The container ID to update, or NULL
	 * @param	int|NULL			$itemId						The item ID to update, or NULL
	 * @param	string|NULL			$newPermissions				New permissions (if applicable)
	 * @param	int|NULL			$newHiddenStatus			New hidden status (if applicable) special value 2 can be used to indicate hidden only by parent
	 * @param	int|NULL			$newContainer				New container ID (if applicable)
	 * @param	int|NULL			$authorId					The author ID to update, or NULL
	 * @param	int|NULL			$newItemId					The new item ID (if applicable)
	 * @param	int|NULL			$newItemAuthorId			The new item author ID (if applicable)
	 * @param	bool				$addAuthorToPermissions		If true, the index_author_id will be added to $newPermissions - used when changing the permissions for a node which allows access only to author's items
	 * @return	void
	 */
	public function massUpdate( $class, $containerId = NULL, $itemId = NULL, $newPermissions = NULL, $newHiddenStatus = NULL, $newContainer = NULL, $authorId = NULL, $newItemId = NULL, $newItemAuthorId = NULL, $addAuthorToPermissions = FALSE )
	{
		try
		{
			$conditions = array();
			$conditions['must'][] = array(
				'term'	=> array(
					'index_class' => $class
				)
			);
			if ( $containerId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_container_id' => $containerId
					)
				);
			}
			if ( $itemId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_item_id' => $itemId
					)
				);
			}
			if ( $authorId !== NULL )
			{
				$conditions['must'][] = array(
					'term'	=> array(
						'index_item_author' => $authorId
					)
				);
			}
			
			$updates = array();
			if ( $newPermissions !== NULL )
			{
				$updates[] = "ctx._source.index_permissions = " . json_encode( explode( ',', $newPermissions ) ) . ";";
			}
			if ( $newContainer )
			{
				$updates[] = "ctx._source.index_container_id = " . intval( $newContainer ) . ";";
				
				if ( $itemClass = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) ? $class : $class::$itemClass ) and $containerClass = $itemClass::$containerNodeClass and \IPS\IPS::classUsesTrait( $containerClass, 'IPS\Content\ClubContainer' ) and $clubIdColumn = $containerClass::clubIdColumn() )
				{
					try
					{
						$updates[] = "ctx._source.index_club_id = " . intval( $containerClass::load( $newContainer )->$clubIdColumn ) . ";";
					}
					catch ( \OutOfRangeException $e )
					{
						$updates[] = "ctx._source.index_club_id = null;";
					}
				}
			}
			if ( $newItemId )
			{
				$updates[] = "ctx._source.index_item_id = " . intval( $newItemId ) . ";";
			}
			if ( $newItemAuthorId )
			{
				$updates[] = "ctx._source.index_item_author = " . intval( $newItemAuthorId ) . ";";
			}
			
			if ( count( $updates ) )
			{
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'inline'	=> implode( ' ', $updates ),
						'lang'		=> 'painless'
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
			
			if ( $addAuthorToPermissions )
			{
				$addAuthorToPermissionsConditions = $conditions;
				$addAuthorToPermissionsConditions['must_not'][] = array(
					'term'	=> array(
						'index_author' => 0
					)
				);
				
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'inline'	=> "ctx._source.index_permissions.add( 'm.' + ctx._source.index_author );",
						'lang'		=> 'painless'
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
			
			if ( $newHiddenStatus !== NULL )
			{
				if ( $newHiddenStatus === 2 )
				{
					$conditions['must'][] = array(
						'term'	=> array(
							'index_item_author' => '0'
						)
					);
				}
				else
				{
					$conditions['must'][] = array(
						'term'	=> array(
							'index_item_author' => '2'
						)
					);
				}
							
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'inline'	=> "ctx._source.index_permissions = " . intval( $newHiddenStatus ) . ";",
						'lang'		=> 'painless'
					),
					'query'		=> array(
						'bool'		=> $conditions
					)
				) ) );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
		}		
	}
	
	/**
	 * Convert an arbitary number of elasticsearch conditions into a query
	 *
	 * @param	array	$conditions	Conditions
	 * @return	array
	 */
	public static function convertConditionsToQuery( $conditions )
	{
		if ( count( $conditions ) == 1 )
		{
			return $conditions[0];
		}
		elseif ( count( $conditions ) == 0 )
		{
			return array( 'match_all' => new \StdClass );
		}
		else
		{
			return array(
				'bool' => array(
					'must' => array( $conditions )
				)
			);
		}
	}
	
	/**
	 * Update data for the first and last comment after a merge
	 * Sets index_is_last_comment on the last comment, and, if this is an item where the first comment is indexed rather than the item, sets index_title and index_tags on the first comment
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	void
	 */
	public function rebuildAfterMerge( \IPS\Content\Item $item )
	{
		if ( $item::$commentClass )
		{
			$firstComment = $item->comments( 1, 0, 'date', 'asc', NULL, FALSE, NULL, NULL, TRUE, FALSE, FALSE );
			$lastComment = $item->comments( 1, 0, 'date', 'desc', NULL, FALSE, NULL, NULL, TRUE, FALSE, FALSE );
			
			$idColumn = $item::$databaseColumnId;
			$update = array( 'index_is_last_comment' => false );
			if ( $item::$firstCommentRequired )
			{
				$update['index_title'] = NULL;
			}
			
			try
			{
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/' . $this->getIndexId( $item ) . '/_update' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'doc'	=> $update
				) ) );
				
				if ( $firstComment )
				{
					$this->index( $firstComment );
				}
				if ( $lastComment )
				{
					$this->index( $lastComment );
				}
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				\IPS\Log::log( $e, 'elasticsearch' );
			}			
		}
	}
	
	/**
	 * Prune search index
	 *
	 * @param	\IPS\DateTime|NULL	$cutoff	The date to delete index records from, or NULL to delete all
	 * @return	void
	 */
	public function prune( \IPS\DateTime $cutoff = NULL )
	{
		if ( $cutoff )
		{			
			try
			{
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_delete_by_query' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'query'	=> array(
						'range'	=> array(
							'index_date_updated' => array(
								'lt' => $cutoff->getTimestamp()
							)
						)
					)
				) ) );
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				\IPS\Log::log( $e, 'elasticsearch' );
			}
		}
		else
		{
			$this->init();
		}		
	}
	
	/**
	 * Reset the last comment flag in any given class/index_item_id
	 *
	 * @param	array				$classes					The classes (when first post is required, this is typically just \IPS\forums\Topic\Post but for others, it will be both item and comment classes)
	 * @param	int|NULL			$indexItemId				The index item ID
	 * @return 	void
	 */
	public function resetLastComment( $classes, $indexItemId )
	{
		try
		{			
			/* Remove the flag */
			$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
				'script'	=> array(
					'inline'	=> "ctx._source.index_is_last_comment = false;",
					'lang'		=> 'painless'
				),
				'query'		=> array(
					'bool'		=> array(
						'must'		=> array(
							array(
								'terms'	=> array(
									'index_class' => $classes
								)
							),
							array(
								'term'	=> array(
									'index_item_id' => $indexItemId
								)
							),
						)
					)
				)
			) ) );
			
			/* Get the latest comment */
			$latest = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_search' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'query'		=> array(
					'bool'		=> array(
						'must'		=> array(
							array(
								'terms'	=> array(
									'index_class' => $classes
								)
							),
							array(
								'term'	=> array(
									'index_item_id' => $indexItemId
								)
							),
							array(
								'term'	=> array(
									'index_hidden' => 0
								)
							),
						)
					)
				),
				'sort'		=> array(
					array(
						'index_date_created' => 'desc'
					)
				),
				'size'		=> 1
			) ) )->decodeJson();
			
			/* If we got it... */		
			if ( $latest['hits']['total'] )
			{
				$result = $latest['hits']['hits'][0];
				
				/* Set that it is the latest comment */
				$r = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/' . $result['_type'] . '/' . $result['_id'] . '/_update' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'doc'	=> array(
						'index_is_last_comment' => true
					)
				) ) );
				
				/* And set the updated time on the main item (done as _update_by_query because it might not exist if the first comment is required) */
				$this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_update_by_query' )->setQueryString( 'conflicts', 'proceed' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->post( json_encode( array(
					'script'	=> array(
						'inline'	=> "ctx._source.index_date_updated = " . intval( $latest['_source']['index_date_updated'] ) ."; ctx._source.index_date_commented = " . intval( $latest['_source']['index_date_commented'] ) .";",
						'lang'		=> 'painless'
					),
					'query'		=> array(
						'bool'		=> array(
							'must'		=> array(
								array(
									'terms'	=> array(
										'index_class' => $classes
									)
								),
								array(
									'term'	=> array(
										'index_item_id' => $indexItemId
									)
								),
								array(
									'term'	=> array(
										'index_object_id' => $indexItemId
									)
								),
							)
						)
					)
				) ) );
			}
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
		}
	}
	
	/**
	 * Given a list of item index IDs, return the ones that a given member has participated in
	 *
	 * @param	array		$itemIndexIds	Item index IDs
	 * @param	\IPS\Member	$member			The member
	 * @return 	array
	 */
	public function iPostedIn( array $itemIndexIds, \IPS\Member $member )
	{
		try
		{
			$results = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_search' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'query'	=> array(
					'bool'	=> array(
						'filter' => array(
							array(
								'terms'	=> array(
									'index_item_index_id' => $itemIndexIds
								),
							),
							array(
								'term'	=> array(
									'index_author' => $member->member_id
								)
							)
						)
					)
				)
			) ) )->decodeJson();
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
			return array();
		}
		
		$iPostedIn = array();
		foreach ( $results['hits']['hits'] as $result )
		{
			$iPostedIn[] = $result['_source']['index_item_index_id'];
		}
		
		return $iPostedIn;
	}
	
	/**
	 * Given a list of "index_class_type_id_hash"s, return the ones that a given member has permission to view
	 *
	 * @param	array		$itemIndexIds	Item index IDs
	 * @param	\IPS\Member	$member			The member
	 * @return 	array
	 */
	public function hashesWithPermission( array $hashes, \IPS\Member $member )
	{
		try
		{
			$results = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_search' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'query'	=> array(
					'bool'	=> array(
						'filter' => array(
							array(
								'terms' => array(
									'index_class_type_id_hash' => $hashes
								)
							),
							array(
								'terms' => array(
									'index_permissions' => array_merge( $member->permissionArray(), array( '*' ) )
								)
							),
							array(
								'term'	=> array(
									'index_hidden' => 0
								)
							)
						)
					)
				)
			) ) )->decodeJson();
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
			return array();
		}
		
		$hashesWithPermission = array();
		foreach ( $results['hits']['hits'] as $result )
		{
			$hashesWithPermission[ $result['_source']['index_class_type_id_hash'] ] = $result['_source']['index_class_type_id_hash'];
		}
		
		return $hashesWithPermission;
	}
	
	/**
	 * Get timestamp of oldest thing in index
	 *
	 * @return 	int|null
	 */
	public function firstIndexDate()
	{
		try
		{
			$results = $this->url->setPath( $this->url->data[ \IPS\Http\Url::COMPONENT_PATH ] . '/content/_search' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'size'	=> 1,
				'sort'	=> array( array( 'index_date_updated' => 'asc' ) )
			) ) )->decodeJson();
			
			if ( isset( $results['hits']['hits'][0] ) )
			{
				return $results['hits']['hits'][0]['_source']['index_date_updated'];
			}
			
			return NULL;
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'elasticsearch' );
			return NULL;
		}
	}
	
	/**
	 * Convert terms into stemmed terms for the highlighting JS
	 *
	 * @param	array	$terms	Terms
	 * @return	array
	 */
	public function stemmedTerms( $terms )
	{
		try
		{
			$results = $this->url->setPath( '/_analyze' )->request()->setHeaders( array( 'Content-Type' => 'application/json' ) )->get( json_encode( array(
				'analyzer'	=> \IPS\Settings::i()->search_elastic_analyzer,
				'text'		=> implode( ' ', $terms )
			) ) )->decodeJson();
			
			if ( isset( $results['tokens'] ) )
			{
				$stemmed = array();
				foreach ( $results['tokens'] as $token )
				{
					$stemmed[] = $token['token'];
				}
				return $stemmed;
			}
			
			return $terms;
		}
		catch ( \Exception $e )
		{
			return $terms;
		}
	}
}