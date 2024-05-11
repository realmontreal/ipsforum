<?php
/**
 * @brief		preferences
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * preferences
 */
class _preferences extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'preferences_manage' );
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
			'theme'		=> 'stats_member_pref_theme',
			'lang'		=> 'stats_member_pref_lang',
		);
		$activeTab	= ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'lang';

		switch( $activeTab )
		{
			case 'theme':
				
				$chart	= new \IPS\Helpers\Chart;
				$counts = iterator_to_array( \IPS\Db::i()->select( 'skin, COUNT(member_id) as count', 'core_members', array( "skin > ?", 0), NULL, NULL, "skin" )->setKeyField( 'skin' ) );

				$chart->addHeader( "theme", "string" );
				$chart->addHeader( \IPS\Member::loggedIn()->language()->get('chart_members'), "number" );
				foreach( $counts as $id => $theme )
				{
					$chart->addRow( array( \IPS\Member::loggedIn()->language()->addToStack('core_theme_set_title_' . $id ), $theme['count'] ) );
				}
	
				break;
				
			case 'lang':
				
				$chart	= new \IPS\Helpers\Chart;
				$counts = iterator_to_array( \IPS\Db::i()->select( 'language, COUNT(member_id) as count', 'core_members', array( "language > ?", 0), NULL, NULL, "language" )->setKeyField( 'language' ) );

				$chart->addHeader( "language", "string" );
				$chart->addHeader( \IPS\Member::loggedIn()->language()->get('chart_members'), "number" );
				
				/* We need to make sure the language exists - otherwise apply the count to the default language. */
				$rows = [];
				foreach( $counts as $id => $lang )
				{
					try
					{
						$l = \IPS\Lang::load( $id );
						if ( !isset( $rows[ $id ] ) )
						{
							$rows[ $id ] = array( 'title' => $l->title, 'count' => 0 );
						}
						$rows[ $id ]['count'] += $lang['count'];
					}
					catch( \OutOfRangeException $e )
					{
						if ( !isset( $rows[ \IPS\Lang::defaultLanguage() ] ) )
						{
							$rows[ \IPS\Lang::defaultLanguage() ] = array( 'title' => \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->title, 'count' => 0 );
						}
						$rows[ \IPS\Lang::defaultLanguage() ]['count'] += $lang['count'];
					}
				}
				
				/* Now add the rows to the chart */
				foreach( $rows AS $row )
				{
					$chart->addRow( array( $row['title'], $row['count'] ) );
				}

				break;
		}
		
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_' . $activeTab . '_title');
		
		$chart = $chart->render('PieChart', array( 
				'backgroundColor' 	=> '#ffffff',
				'pieHole' => 0.4,
				'chartArea' => array( 
					'width' =>"90%", 
					'height' => "90%" 
				) 
			) );
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->paddedBlock( (string) $chart, NULL, "ipsPad" );
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_preferences');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, \IPS\Http\Url::internal( "app=core&module=stats&controller=preferences" ), 'tab', '', 'ipsPad' );
		}
			
	}
}