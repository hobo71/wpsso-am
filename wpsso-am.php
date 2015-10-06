<?php
/*
 * Plugin Name: WPSSO App Meta (WPSSO AM)
 * Plugin Slug: wpsso-am
 * Text Domain: wpsso-am
 * Domain Path: /languages
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso-am/
 * Author: Jean-Sebastien Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO extension to provide Apple Store / iTunes and Google Play App meta tags for Apple's mobile Safari and Twitter's App Card.
 * Requires At Least: 3.1
 * Tested Up To: 4.3.1
 * Version: 1.3.7
 * 
 * Copyright 2014-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoAm' ) ) {

	class WpssoAm {

		public $p;			// Wpsso
		public $reg;			// WpssoAmRegister

		protected static $instance = null;

		private static $wpsso_short = 'WPSSO';
		private static $wpsso_name = 'WordPress Social Sharing Optimization (WPSSO)';
		private static $wpsso_min_version = '3.10.3';
		private static $wpsso_has_min_ver = true;
		private static $opt_version_suffix = 'am7';

		public static function &get_instance() {
			if ( self::$instance === null )
				self::$instance = new self;
			return self::$instance;
		}

		public function __construct() {

			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoAmConfig::set_constants( __FILE__ );
			WpssoAmConfig::require_libs( __FILE__ );
			$this->reg = new WpssoAmRegister();		// activate, deactivate, uninstall hooks

			if ( is_admin() )
				add_action( 'admin_init', array( &$this, 'check_for_wpsso' ) );

			add_filter( 'wpsso_get_config', array( &$this, 'wpsso_get_config' ), 10, 1 );
			add_action( 'wpsso_init_options', array( &$this, 'wpsso_init_options' ), 10 );
			add_action( 'wpsso_init_objects', array( &$this, 'wpsso_init_objects' ), 10 );
			add_action( 'wpsso_init_plugin', array( &$this, 'wpsso_init_plugin' ), 10 );
		}

		public function check_for_wpsso() {
			if ( ! class_exists( 'Wpsso' ) )
				add_action( 'all_admin_notices', array( __CLASS__, 'wpsso_missing_notice' ) );
		}

		public static function wpsso_missing_notice( $deactivate = false ) {
			$lca = 'wpssoam';
			$info = WpssoAmConfig::$cf['plugin'][$lca];
			load_plugin_textdomain( $info['text_domain'], false, $info['slug'].$info['domain_path'] );

			if ( $deactivate === true ) {
				require_once( ABSPATH.'wp-admin/includes/plugin.php' );
				deactivate_plugins( $info['base'] );

				wp_die( '<p>'.sprintf( __( 'The %1$s extension requires the %2$s plugin &mdash; '.
					'please install and activate the %3$s plugin before trying to re-activate the %4$s extension.',
						'wpsso-am' ), $info['name'], self::$wpsso_name, self::$wpsso_short, $info['short'] ).'</p>' );

			} else echo '<div class="error"><p>'.sprintf( __( 'The %1$s extension requires the %2$s plugin &mdash; '.
				'please install and activate the %3$s plugin.',
					'wpsso-am' ), $info['name'], self::$wpsso_name, self::$wpsso_short ).'</p></div>';
		}

		public function wpsso_get_config( $cf ) {
			if ( version_compare( $cf['plugin']['wpsso']['version'], self::$wpsso_min_version, '<' ) ) {
				self::$wpsso_has_min_ver = false;
				return $cf;
			}
			$cf['opt']['version'] .= '-'.self::$opt_version_suffix.
				( is_dir( trailingslashit( dirname( __FILE__ ) ).'lib/pro/' ) ? 'pro' : 'gpl' );
			$cf = SucomUtil::array_merge_recursive_distinct( $cf, WpssoAmConfig::$cf );
			return $cf;
		}

		public function wpsso_init_options() {
			$this->p =& Wpsso::get_instance();
			if ( self::$wpsso_has_min_ver === false )
				return;		// stop here
			$this->p->is_avail['am'] = true;
			$this->p->is_avail['admin']['am-general'] = true;
			$this->p->is_avail['head']['twittercard'] = true;
		}

		public function wpsso_init_objects() {
			if ( self::$wpsso_has_min_ver === false )
				return;		// stop here
			WpssoAmConfig::load_lib( false, 'filters' );
			$this->p->am = new WpssoAmFilters( $this->p, __FILE__ );
		}

		public function wpsso_init_plugin() {
			if ( self::$wpsso_has_min_ver === false )
				return $this->warning_wpsso_version( WpssoAmConfig::$cf['plugin']['wpssoam'] );
		}

		private function warning_wpsso_version( $info ) {
			$wpsso_version = $this->p->cf['plugin']['wpsso']['version'];
			if ( $this->p->debug->enabled )
				$this->p->debug->log( $info['name'].' requires '.self::$wpsso_short.' version '.
					self::$wpsso_min_version.' or newer ('.$wpsso_version.' installed)' );
			if ( is_admin() )
				$this->p->notice->err( sprintf( __( 'The %1$s extension version %2$s requires the use of '.
					'%3$s version %4$s or newer (version %5$s is currently installed).',
						'wpsso-am' ), $info['name'], $info['version'], self::$wpsso_short, 
							self::$wpsso_min_version, $wpsso_version ), true );
		}
	}

        global $wpssoam;
	$wpssoam = WpssoAm::get_instance();
}

?>