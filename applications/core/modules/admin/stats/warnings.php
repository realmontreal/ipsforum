<?php
/**
 * @brief		warnings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		20 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * warnings
 */
class _warnings extends \IPS\Dispatcher\Controller
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'warnings_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$tabs		= array(
			'reason'			=> 'stats_warnings_reason',
			'suspended'		=> 'stats_warnings_suspended',
		);
		$activeTab	= ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'reason';

		if ( $activeTab === 'reason' )
		{
			$chart	= new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=core&module=stats&controller=warnings&tab=' . $activeTab ), 'core_members_warn_logs', 'wl_date', '', array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			 ), 'LineChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'wl_reason', 'wl_date' ), 'warnings' );

			$chart->groupBy = 'wl_reason';

			foreach( \IPS\core\Warnings\Reason::roots() as $reason )
			{
				$chart->addSeries(  \IPS\Member::loggedIn()->language()->addToStack('core_warn_reason_' . $reason->id ), 'number', 'COUNT(*)', TRUE, $reason->id );		
			}

			$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_warnings_title');
			$chart->availableTypes = array( 'LineChart', 'AreaChart', 'ColumnChart', 'BarChart' );
		}
		else if ( $activeTab === 'suspended' )
		{
			$chart	= new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=core&module=stats&controller=warnings&tab=' . $activeTab ), 'core_members_warn_logs', 'wl_date', '', array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			 ), 'LineChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'wl_reason', 'wl_date' ), 'warnings' );

			$chart->where = array( 'wl_suspend IS NOT NULL' );
			
			$chart->addSeries(  \IPS\Member::loggedIn()->language()->addToStack('suspended' ), 'number', 'COUNT(*)', TRUE );		

			$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_warnings_title');
			$chart->availableTypes = array( 'LineChart', 'AreaChart', 'ColumnChart', 'BarChart' );
		}

		$chart->tableParsers = array(
			'wl_date'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			}
		);
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = (string) $chart;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_warnings');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, \IPS\Http\Url::internal( "app=core&module=stats&controller=warnings" ), 'tab', '', 'ipsPad' );
		}
	}
	
}