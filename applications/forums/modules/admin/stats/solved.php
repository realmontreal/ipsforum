<?php
/**
 * @brief		solved
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		25 Jul 2022
 */

namespace IPS\forums\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * solved
 */
class _solved extends \IPS\Dispatcher\Controller
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
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'topics_manage' );
		parent::execute();
	}

	/**
	 * Show the stats then
	 *
	 * core_statistics mapping:
	 * type: solved
	 * value_1: forum_id
	 * value_2: total topics added
	 * value_3: total solved
	 * value_4: AVG time to solved (in seconds)
	 * time: timestamp of the start of the day (so 0:00:00)
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$tabs = array(
			'time' 		 => 'forums_solved_stats_time',
			'percentage' => 'forums_solved_stats_percentage',
			'solved'	 => 'stats_topics_tab_solved'
		);

		/* Show button to adjust settings */
		\IPS\Output::i()->sidebar['actions']['settings'] = array(
			'icon'		=> 'cog',
			'title'		=> 'solved_stats_rebuild_button',
			'link'		=> \IPS\Http\Url::internal( 'app=forums&module=stats&controller=solved&do=rebuildStats' )->csrf(),
			'data'		=> array( 'confirm' => '' )
		);
		
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'time';
		
		/* Determine minimum date */
		$minimumDate = NULL;
		
		/* We can't retrieve any stats prior to the new tracking being implemented */
		try
		{
			$oldestLog = \IPS\Db::i()->select( 'MIN(time)', 'core_statistics', array( 'type=?', 'solved' ) )->first();
		
			if( !$minimumDate OR $oldestLog < $minimumDate->getTimestamp() )
			{
				$minimumDate = \IPS\DateTime::ts( $oldestLog );
			}
		}
		catch( \UnderflowException $e )
		{
			/* We have nothing tracked, set minimum date to today */
			$minimumDate = \IPS\DateTime::create();
		}
		
		if ( $activeTab === 'time' )
		{
			$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=forums&module=stats&controller=solved&tab=' . $activeTab ), 'core_statistics', 'time', '', array(
				'isStacked' => FALSE,
				'backgroundColor' => '#ffffff',
				'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
				'lineWidth' => 1,
				'areaOpacity' => 0.4
				),
				'AreaChart',
				'daily',
				array('start' => $minimumDate, 'end' => 0),
				array(),
			);

			$chart->description = \IPS\Member::loggedIn()->language()->addToStack( 'solved_stats_chart_desc' );
			$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
			$chart->enableHourly = FALSE;
			$chart->groupBy = 'value_1';
			$chart->title = \IPS\Member::loggedIn()->language()->addToStack('forums_solved_stats_time');
			
			foreach( $validForumIds = $this->getValidForumIds() as $forumId => $forum )
			{
				$chart->addSeries( $forum->_title, 'number', 'AVG(value_4) / 3600', TRUE, $forumId );
			}
		}
		else if ( $activeTab === 'percentage' )
		{
			$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=forums&module=stats&controller=solved&tab=' . $activeTab ), 'core_statistics', 'time', '', array(
				'isStacked' => FALSE,
				'backgroundColor' => '#ffffff',
				'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
				'lineWidth' => 1,
				'areaOpacity' => 0.4
				),
				'AreaChart',
				'daily',
				array('start' => $minimumDate, 'end' => 0),
				array(),
			);

			$chart->description = \IPS\Member::loggedIn()->language()->addToStack( 'solved_stats_chart_desc' );
			$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
			$chart->enableHourly = FALSE;
			$chart->groupBy = 'value_1';
			$chart->title = \IPS\Member::loggedIn()->language()->addToStack('forums_solved_stats_percentage');
			
			foreach( $validForumIds = $this->getValidForumIds() as $forumId => $forum )
			{
				$chart->addSeries( $forum->_title, 'number', '( SUM(value_3) / SUM(value_2) ) * 100', TRUE, $forumId );
			}
		}
		else if( $activeTab === 'solved' )
		{
			$chart = new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( "app=forums&module=stats&controller=solved&tab=" . $activeTab ), 'core_solved_index', 'solved_date', '', array( 
					'isStacked' => FALSE,
					'backgroundColor' 	=> '#ffffff',
					'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
					'lineWidth'			=> 1,
					'areaOpacity'		=> 0.4,
					'chartArea'			=> array( 'width' => '70%', 'left' => '5%' ),
					'height'			=> 400,
				),
				'LineChart',
				'monthly',
				array( 'start' => ( new \IPS\DateTime )->sub( new \DateInterval('P90D') ), 'end' => new \IPS\DateTime ),
				array(),
				'solved' 
			);
			
			$chart->joins = array( array( 'forums_topics', array( 'comment_class=? and core_solved_index.item_id=forums_topics.tid', 'IPS\forums\Topic\Post' ) ) );
			$chart->where = array( array( \IPS\Db::i()->in( 'state', array( 'link', 'merged' ), TRUE ) ), array( \IPS\Db::i()->in( 'approved', array( -2, -3 ), TRUE ) ) ); 
			$chart->title = \IPS\Member::loggedIn()->language()->addToStack( 'stats_topics_title_solved' );
			$chart->availableTypes = array( 'LineChart', 'ColumnChart' );
		
			$chart->groupBy = 'forum_id';
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
					return \IPS\Db::i()->in( 'forum_id', $forumIds );
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
					foreach( \IPS\Db::i()->select( '*', 'forums_forums', array( 'topics>? and ( forums_bitoptions & ? or forums_bitoptions & ? or forums_bitoptions & ? )', 0, 4, 8, 16 ), 'last_post desc', array( 0, 50 ) ) as $forum )
					{
						$series[] = array( \IPS\Member::loggedIn()->language()->addToStack( 'forums_forum_' . $forum['id'] ), 'number', 'COUNT(*)', FALSE, $forum['id'] );
					}
					
					return $series;
				}
			);
		}
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = (string)$chart;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'menu__forums_stats_solved' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string)$chart, \IPS\Http\Url::internal( "app=forums&module=stats&controller=solved" ), 'tab', '', 'ipsPad' );
		}
	}
	
	/**
	 * Kick off a rebuild of the stats
	 *
	 */
	public function rebuildStats()
	{
		\IPS\Session::i()->csrfCheck();

		foreach( \IPS\Db::i()->select( '*', 'forums_forums', array( 'topics>? and ( forums_bitoptions & ? or forums_bitoptions & ? or forums_bitoptions & ? )', 0, 4, 8, 16 ) ) as $forum )
		{
			\IPS\Task::queue( 'forums', 'RebuildSolvedStats', array( 'forum_id' => $forum['id'] ) );
		}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=forums&module=stats&controller=solved'), 'solved_stats_rebuild_started' );
	}
	
	/**
	 * Get valid forum IDs to protect against bad data when a forum is removed
	 *
	 * @return array
	 */
	protected function getValidForumIds()
	{
		$validForumIds = [];
		
		foreach( \IPS\Db::i()->select( 'value_1', 'core_statistics', [ 'type=?', 'solved' ], NULL, NULL, 'value_1' ) as $forumId )
		{
			try
			{
				$validForumIds[ $forumId ] = \IPS\forums\Forum::load( $forumId );
			}
			catch( \Exception $e ) { }
		}
		
		return $validForumIds;
	}
}