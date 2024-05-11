<?php
/**
 * @brief		posts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		18 Aug 2014
 */

namespace IPS\forums\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * posts
 */
class _posts extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static $allowRWSeparation = TRUE;

	/**
	 * Show solved posts
	 *
	 * @return void
	 */
	protected function showSolvedPosts()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_solved_index', \IPS\Http\Url::internal( 'app=forums&module=stats&controller=posts&do=showSolvedPosts' ), [ 'solved_date BETWEEN ? AND ?', \IPS\Request::i()->startTime, \IPS\Request::i()->endTime ] );
		$table->quickSearch = NULL;
		$table->sortBy = $table->sortBy ?: 'solved_date';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->langPrefix = 'stats_posts_type_';
		$table->include = [ 'comment_id', 'solved_date' ];
		$table->widths = array( 'solved_date' => 25, 'comment_id' => 75 );
		$table->baseUrl = $table->baseUrl->setQueryString( array( 'startTime' => \IPS\Request::i()->startTime, 'endTime' => \IPS\Request::i()->endTime, 'tab' => 'bytype' ) );

		/* Custom parsers */
		$table->parsers = array(
			'solved_date' => function( $val, $row )
			{
				return \IPS\DateTime::ts( $val )->html();
			},
			'comment_id'		=> function( $val, $row )
			{
				$class = $row['comment_class'];

				try
				{
					$item = $class::load( $row['comment_id'] );

					return \IPS\Theme::i()->getTemplate( 'activitystats', 'core' )->contentCell( $item );
				}
				catch ( \Throwable $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'unavailable' );
				}
			}
		);

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global', 'core')->block( NULL, (string) $table, TRUE, 'ipsPad' );
	}

	/**
	 * Show solved posts
	 *
	 * @return void
	 */
	protected function showRecommendedPosts()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_content_meta', \IPS\Http\Url::internal( 'app=forums&module=stats&controller=posts&do=showRecommendedPosts' ), [ 'meta_type=? AND meta_added BETWEEN ? AND ?', 'core_FeaturedComments', \IPS\Request::i()->startTime, \IPS\Request::i()->endTime ] );
		$table->quickSearch = NULL;
		$table->sortBy = $table->sortBy ?: 'meta_added';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->langPrefix = 'stats_posts_type_';
		$table->include = [ 'meta_data', 'meta_added' ];
		$table->widths = array( 'meta_added' => 25, 'meta_data' => 75 );
		$table->baseUrl = $table->baseUrl->setQueryString( array( 'startTime' => \IPS\Request::i()->startTime, 'endTime' => \IPS\Request::i()->endTime, 'tab' => 'bytype' ) );

		/* Custom parsers */
		$table->parsers = array(
			'meta_added' => function( $val, $row )
			{
				return \IPS\DateTime::ts( $val )->html();
			},
			'meta_data'		=> function( $val, $row )
			{
				$data = json_decode( $val, TRUE );

				try
				{
					$item = \IPS\forums\Topic\Post::load( $data['comment'] );

					return \IPS\Theme::i()->getTemplate( 'activitystats', 'core' )->contentCell( $item );
				}
				catch ( \Throwable $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'unavailable' );
				}
			}
		);

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global', 'core')->block( NULL, (string) $table, TRUE, 'ipsPad' );
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posts_manage' );

		$tabs		= array( 'total' => 'stats_posts_tab_total', 'bytype' => 'stats_posts_by_type', 'byforum' => 'stats_posts_tab_byforum' );
		$activeTab	= ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'total';

		if ( $activeTab === 'bytype' )
		{
			$defaults = array( 'start' => \IPS\DateTime::create()->setDate( date('Y'), date('m'), 1 ), 'end' => new \IPS\DateTime );

			if( isset( \IPS\Request::i()->badgeDateStart ) AND isset( \IPS\Request::i()->badgeDateEnd ) )
			{
				$defaults = array( 'start' => \IPS\DateTime::ts( \IPS\Request::i()->badgeDateStart ), 'end' => \IPS\DateTime::ts( \IPS\Request::i()->badgeDateEnd ) );
			}

			$form = new \IPS\Helpers\Form( $activeTab, 'continue' );
			$form->add( new \IPS\Helpers\Form\DateRange( 'date', $defaults, TRUE ) );

			if( $values = $form->values() )
			{
				/* Determine start and end time */
				$startTime	= $values['date']['start']->getTimestamp();
				$endTime	= $values['date']['end']->getTimestamp();

				$start		= $values['date']['start']->html();
				$end		= $values['date']['end']->html();
			}
			else
			{
				/* Determine start and end time */
				$startTime	= $defaults['start']->getTimestamp();
				$endTime	= $defaults['end']->getTimestamp();

				$start		= $defaults['start']->html();
				$end		= $defaults['end']->html();
			}

			/* Get a count of solved posts in this time */
			$solvedCount = \IPS\Db::i()->select( 'count(*)', 'core_solved_index', [
				[ 'solved_date BETWEEN ? AND ?', $startTime, $endTime ],
				[ 'comment_class=?', 'IPS\forums\Topic\Post' ]
			] )->first();

			/* Get a count of recommended posts in this time */
			$recommendedCount = \IPS\Db::i()->select( 'count(*)', 'core_content_meta', [
				[ 'meta_added BETWEEN ? AND ?', $startTime, $endTime ],
				[ 'meta_type=?', 'core_FeaturedComments' ]
			] )->first();

			$formHtml = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'stats', 'core' ), 'filtersFormTemplate' ) );
			$chart = \IPS\Theme::i()->getTemplate( 'stats', 'forums' )->statsTypeWrapper( $formHtml, $solvedCount, $recommendedCount, $startTime, $endTime );
		}
		else if ( $activeTab === 'total' )
		{
			$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts" ), 'forums_posts', 'post_date', '', array(
				'isStacked' => FALSE,
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			) );

			$chart->where = array( array( \IPS\Db::i()->in( 'queued', array( -2, -3 ), TRUE ) ) );
			$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack( 'stats_new_posts' ), 'number', 'COUNT(*)', FALSE );
			$chart->title = \IPS\Member::loggedIn()->language()->addToStack( 'stats_posts_title' );
			$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		}
		else
		{
			$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts&tab=" . $activeTab ), 'forums_posts', 'post_date', '', array( 
					'isStacked' => FALSE,
					'backgroundColor' 	=> '#ffffff',
					'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
					'lineWidth'			=> 1,
					'areaOpacity'		=> 0.4,
					'chartArea'			=> array( 'width' => '70%', 'left' => '5%' ),
					'height'			=> 400,
				),
				'ColumnChart',
				'monthly',
				array( 'start' => ( new \IPS\DateTime )->sub( new \DateInterval('P90D') ), 'end' => new \IPS\DateTime ),
				array(),
				'byforum' 
			);

			$chart->title = \IPS\Member::loggedIn()->language()->addToStack( 'stats_posts_title_byforum' );
			$chart->availableTypes = array( 'ColumnChart' );
			$chart->where = array( array( \IPS\Db::i()->in( 'queued', array( -2, -3 ), TRUE ) ) );
			$chart->groupBy = 'forum_id';
			$chart->joins	= array( array( 'forums_topics', 'forums_topics.tid=forums_posts.topic_id' ) );
			
			$customValues = ( isset( $chart->savedCustomFilters['chart_forums'] ) ? array_values( explode( ',', $chart->savedCustomFilters['chart_forums'] ) ) : 0 );
			
			$chart->customFiltersForm = array(
				'form' => array(
					new \IPS\Helpers\Form\Node( 'chart_forums', $customValues, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'any', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
					{
						return $forum->sub_can_post and !$forum->redirect_url;
					} ), NULL, NULL, NULL, 'chart_forums' )
				),
				'where' => function( $values )
				{
					$forumIds = \is_array( $values['chart_forums'] ) ? array_keys( $values['chart_forums'] ) : explode( ',', $values['chart_forums'] );
					return \IPS\Db::i()->in( 'forums_topics.forum_id', $forumIds );
				},
				'groupBy' => 'forum_id',
				'series'  => function( $values )
				{
					$series = array();
					$forumIds = \is_array( $values['chart_forums'] ) ? array_keys( $values['chart_forums'] ) : explode( ',', $values['chart_forums'] );
					foreach( $forumIds as $id )
					{
						$series[] = array( \IPS\Member::loggedIn()->language()->addToStack( 'forums_forum_' . $id ), 'number', 'COUNT(*)', FALSE, $id );
					}
					return $series;
				},
				'defaultSeries' => function()
				{
                    $series = array();
					foreach( \IPS\Db::i()->select( '*', 'forums_forums', array( 'posts>?', 0 ), 'last_post desc', array( 0, 50 ) ) as $forum )
					{
						$series[] = array( \IPS\Member::loggedIn()->language()->addToStack( 'forums_forum_' . $forum['id'] ), 'number', 'COUNT(*)', FALSE, $forum['id'] );
					}
					
					return $series;
				}
			);
		}

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = (string) $chart;
		}
		else
		{	
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__forums_stats_posts');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts" ) );
		}
	}
}