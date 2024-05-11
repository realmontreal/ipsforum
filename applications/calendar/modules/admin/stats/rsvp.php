<?php
/**
 * @brief		rsvp
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		10 Sep 2021
 */

namespace IPS\calendar\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rsvp
 */
class _rsvp extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rsvp_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart	= new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=calendar&module=stats&controller=rsvp' ), 'calendar_event_rsvp', 'rsvp_date', '', array( 
			'isStacked' => FALSE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		 ) );
		
		$chart->groupBy = 'rsvp_response';
		
		foreach( array( 0,1,2 ) as $response )
		{
			$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('calendar_stats_rsvp_response_' . $response ), 'number', 'COUNT(*)', FALSE, $response );
		
		}
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('calendar_stats_rsvp_title');
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__calendar_stats_rsvp');
		\IPS\Output::i()->output = (string) $chart;
	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}