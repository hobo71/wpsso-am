<?php
/*
 * Plugin Name: WPSSO Mobile App Meta (WPSSO AM)
 * Plugin Slug: wpsso-am
 * Text Domain: wpsso-am
 * Domain Path: /languages
 * Plugin URI: https://surniaulula.com/extend/plugins/wpsso-am/
 * Assets URI: https://surniaulula.github.io/wpsso-am/assets/
 * Author: JS Morisset
 * Author URI: https://surniaulula.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO extension to provide Apple Store / iTunes and Google Play App meta tags for Apple's mobile Safari and Twitter's App Card.
 * Requires At Least: 3.8
 * Tested Up To: 4.7.3
 * Version: 1.7.15-rc1
 * 
 * Version Numbering Scheme: {major}.{minor}.{bugfix}-{stage}{level}
 *
 *	{major}		Major code changes / re-writes or significant feature changes.
 *	{minor}		New features / options were added or improved.
 *	{bugfix}	Bugfixes or minor improvements.
 *	{stage}{level}	dev < a (alpha) < b (beta) < rc (release candidate) < # (production).
 *
 * See PHP's version_compare() documentation at http://php.net/manual/en/function.version-compare.php.
 * 
 * Copyright 2014-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoAm' ) ) {

	class WpssoAm {

		public $p;			// Wpsso
		public $reg;			// WpssoAmRegister
		public $filters;		// WpssoAmFilters

		private static $instance;
		private static $have_req_min = true;	// have at least minimum wpsso version

		public function __construct() {

			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoAmConfig::set_constants( __FILE__ );
			WpssoAmConfig::require_libs( __FILE__ );	// includes the register.php class library
			$this->reg = new WpssoAmRegister();		// activate, deactivate, uninstall hooks

			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'required_check' ) );
				add_action( 'wpsso_init_textdomain', array( __CLASS__, 'wpsso_init_textdomain' ) );
			}

			add_filter( 'wpsso_get_config', array( &$this, 'wpsso_get_config' ), 10, 2 );
			add_action( 'wpsso_init_options', array( &$this, 'wpsso_init_options' ), 10 );
			add_action( 'wpsso_init_objects', array( &$this, 'wpsso_init_objects' ), 10 );
			add_action( 'wpsso_init_plugin', array( &$this, 'wpsso_init_plugin' ), 10 );
		}

		public static function &get_instance() {
			if ( ! isset( self::$instance ) )
				self::$instance = new self;
			return self::$instance;
		}

		public static function required_check() {
			if ( ! class_exists( 'Wpsso' ) )
				add_action( 'all_admin_notices', array( __CLASS__, 'required_notice' ) );
		}

		// also called from the activate_plugin method with $deactivate = true
		public static function required_notice( $deactivate = false ) {
			self::wpsso_init_textdomain();
			$info = WpssoAmConfig::$cf['plugin']['wpssoam'];
			$die_msg = __( '%1$s is an extension for the %2$s plugin &mdash; please install and activate the %3$s plugin before activating %4$s.', 'wpsso-am' );
			$err_msg = __( 'The %1$s extension requires the %2$s plugin &mdash; please install and activate the %3$s plugin.', 'wpsso-am' );

			if ( $deactivate === true ) {
				require_once( ABSPATH.'wp-admin/includes/plugin.php' );
				deactivate_plugins( $info['base'] );
				wp_die( '<p>'.sprintf( $die_msg, $info['name'], $info['req']['name'], $info['req']['short'], $info['short'] ).'</p>' );
			} else echo '<div class="notice notice-error error"><p>'.
				sprintf( $err_msg, $info['name'], $info['req']['name'], $info['req']['short'] ).'</p></div>';
		}

		public static function wpsso_init_textdomain() {
			load_plugin_textdomain( 'wpsso-am', false, 'wpsso-am/languages/' );
		}

		public function wpsso_get_config( $cf, $plugin_version = 0 ) {
			$info = WpssoAmConfig::$cf['plugin']['wpssoam'];

			if ( version_compare( $plugin_version, $info['req']['min_version'], '<' ) ) {
				self::$have_req_min = false;
				return $cf;
			}

			if ( is_admin() )
				asort( WpssoAmConfig::$app_stores );

			return SucomUtil::array_merge_recursive_distinct( $cf, WpssoAmConfig::$cf );
		}

		public function wpsso_init_options() {
			if ( method_exists( 'Wpsso', 'get_instance' ) )
				$this->p =& Wpsso::get_instance();
			else $this->p =& $GLOBALS['wpsso'];

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( self::$have_req_min === false )
				return;		// stop here

			$this->p->is_avail['am'] = true;
			$this->p->is_avail['head']['twittercard'] = true;

			if ( is_admin() ) {
				$this->p->is_avail['admin']['am-general'] = true;
				$this->p->is_avail['admin']['post'] = true;
			}
		}

		public function wpsso_init_objects() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( self::$have_req_min === false )
				return;		// stop here

			$this->filters = new WpssoAmFilters( $this->p );
		}

		public function wpsso_init_plugin() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( self::$have_req_min === false )
				return $this->min_version_notice();
		}

		private function min_version_notice() {
			$info = WpssoAmConfig::$cf['plugin']['wpssoam'];
			$wpsso_version = $this->p->cf['plugin']['wpsso']['version'];

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( $info['name'].' requires '.$info['req']['short'].' v'.
					$info['req']['min_version'].' or newer ('.$wpsso_version.' installed)' );
			}

			if ( is_admin() ) {
				$this->p->notice->err( sprintf( __( 'The %1$s extension v%2$s requires %3$s v%4$s or newer (v%5$s currently installed).',
					'wpsso-am' ), $info['name'], $info['version'], $info['req']['short'],
						$info['req']['min_version'], $wpsso_version ) );
			}
		}
	}

        global $wpssoam;
	$wpssoam =& WpssoAm::get_instance();
}

?>
