<?php
/**
 * @brief		rankprogression
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		27 Jun 2022
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rankprogression
 */
class _rankprogression extends \IPS\Dispatcher\Controller
{
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'overview_manage' );
		parent::execute();
	}

	/**
	 * Points earned activity chart
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart = new \IPS\Helpers\Chart;
		$chart->addHeader( "Rank", 'string' );
		$chart->addHeader( "Days", 'number' );

		$data = \IPS\Db::i()->select( 'new_rank, AVG(time_to_new_rank) as time_to_rank', 'core_points_log', array('time_to_new_rank IS NOT NULL'), 'core_member_ranks.points ASC', NULL, ['new_rank'] );
		$data->join( 'core_member_ranks', 'core_member_ranks.id = core_points_log.new_rank' );

		foreach ( $data as $row )
		{
			try
			{
				$rank = \IPS\core\Achievements\Rank::load( $row['new_rank'] );
			}
			catch ( \Exception $e )
			{
				continue;
			}
			$chart->addRow( array( $rank->_title, floor( $row['time_to_rank'] / 86400 ) ) );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_rankprogression');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'stats' )->rankprogressionmessage();
		\IPS\Output::i()->output .= $chart->render( 'ScatterChart', array(
			'is3D'	=> TRUE,
			'vAxis'	=> array( 'title' => \IPS\Member::loggedIn()->language()->addToStack("core_stats_rank_progression_v") ),
			'legend' => 'none'
		) );
	}
}