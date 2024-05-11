<?php
/**
 * @brief		deletedcontent
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		23 Sep 2021
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * deletedcontent
 */
class _deletedcontent extends \IPS\Dispatcher\Controller
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'deletedcontent_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart	= new \IPS\Helpers\Chart\Database( \IPS\Http\Url::internal( 'app=core&module=activitystats&controller=deletedcontent' ), 'core_deletion_log', 'dellog_deleted_date', '', array( 
			'isStacked' => TRUE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		 ), 'LineChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'dellog_content_class', 'dellog_deleted_date' ), 'deletedcontent' );
		
		$chart->groupBy = 'dellog_content_class';

		$types = \IPS\Db::i()->select( 'DISTINCT(dellog_content_class)', 'core_deletion_log' );
		
		foreach( $types as $class )
		{
			$lang = $class::$title;
			$chart->addSeries(  \IPS\Member::loggedIn()->language()->addToStack( $lang ), 'number', 'COUNT(*)', TRUE, $class );		
		}

		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_deletedcontent_title');
		$chart->availableTypes = array( 'LineChart', 'AreaChart', 'ColumnChart', 'BarChart' );

		$chart->tableParsers = array(
			'dellog_deleted_date'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			}
		);

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_activitystats_deletedcontent');
		\IPS\Output::i()->output = (string) $chart;
	}
	
}