<?php
/**
 * @brief		moderators
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		22 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * moderators
 */
class _moderators extends \IPS\Dispatcher\Controller
{	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart	= new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=core&module=stats&controller=moderators'), 'core_moderator_logs', 'ctime', '', array( 
			'isStacked' => TRUE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		 ), 'LineChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'member_id', 'ctime' ), 'spam' );
		
		$chart->groupBy = 'member_id';
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_moderator_activity_title');
		$chart->availableTypes = array( 'LineChart', 'AreaChart', 'ColumnChart', 'BarChart' );

		$chart->tableParsers = array(
			'ctime'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			}
		);
		
		/* Didn't find any specified? Do the default of the top 50 moderators */
		$where = array();
		if ( $chart->start instanceof \IPS\DateTime or $chart->end instanceof \IPS\DateTime )
		{
			$start = $chart->start instanceof \IPS\DateTime ? $chart->start->getTimestamp() : 0;
			$end   = $chart->end instanceof \IPS\DateTime ? $chart->end->getTimestamp() : time();
			$where[]  = array( 'ctime BETWEEN ? AND ?', $start, $end );
		}
		
		/* Get actual moderators */
		$modMember = [];
		$modGroup = [];
		
		foreach ( \IPS\Db::i()->select( '*', 'core_moderators' ) as $mod )
		{
			if ( $mod['type'] == 'g' )
			{
				$modGroup[] = $mod['id'];
			}
			else
			{
				$modMember[] = $mod['id'];	
			}
		}
		
		$query = [];
		
		if ( \count( $modGroup ) )
		{
			$query[] = \IPS\Db::i()->in( 'member_group_id', $modGroup );
			$query[] = \IPS\Db::i()->findInSet( 'mgroup_others', $modGroup );
		}
		
		if ( \count( $modMember ) )
		{
			$query[] = \IPS\Db::i()->in( 'member_id', $modMember );
		}
		
		/* I mean $query should never be empty, but lets not wait for a ticket and have to release a patch to find out that it could be... */
		if ( \count( $query ) )
		{
			$where[] = [ 'member_id IN(?)', \IPS\Db::i()->select( 'member_id', 'core_members', implode( ' OR ', $query ) ) ];
		}
		
		$topModerators = \IPS\Db::i()->select( 'member_id, COUNT(*) as _mod_count', 'core_moderator_logs', $where, '_mod_count DESC', 50, array( 'member_id' ) );

		foreach ( $topModerators as $moderatorRow )
		{
			$member = \IPS\Member::load( $moderatorRow['member_id'] );
			$chart->addSeries(
				( $member->member_id ) ? $member->name : \IPS\Member::loggedIn()->language()->addToStack( 'deleted_member' ),
				'number',
				'COUNT(*)',
				TRUE,
				$moderatorRow['member_id']
			);
		}
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = (string) $chart;
		}
		else
		{	
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_moderators');
			\IPS\Output::i()->output = (string) $chart;
		}
	}
}