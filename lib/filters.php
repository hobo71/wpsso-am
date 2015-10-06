<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoAmFilters' ) ) {

	class WpssoAmFilters {

		protected $p;
		protected $plugin_filepath;

		public static $cf = array(
			'opt' => array(				// options
				'defaults' => array(
					'add_meta_name_apple-itunes-app' => 1,
					'add_meta_name_twitter:app:country' => 1,
					'add_meta_name_twitter:app:name:iphone' => 1,
					'add_meta_name_twitter:app:id:iphone' => 1,
					'add_meta_name_twitter:app:url:iphone' => 1,
					'add_meta_name_twitter:app:name:ipad' => 1,
					'add_meta_name_twitter:app:id:ipad' => 1,
					'add_meta_name_twitter:app:url:ipad' => 1,
					'add_meta_name_twitter:app:name:googleplay' => 1,
					'add_meta_name_twitter:app:id:googleplay' => 1,
					'add_meta_name_twitter:app:url:googleplay' => 1,
					'am_ws_on_index' => 1,
					'am_ws_on_front' => 1,
					'am_ws_add_to_post' => 1,
					'am_ws_add_to_page' => 1,
					'am_ws_add_to_attachment' => 1,
					'am_ws_itunes_app_id' => '',
					'am_ws_itunes_app_aff' => '',
					'am_ws_itunes_app_arg' => '%%request_url%%',
					'am_ap_ast' => 'US',
					'am_ap_add_to_post' => 0,
					'am_ap_add_to_page' => 1,
					'am_ap_add_to_attachment' => 0,
				),
			),
		);

		public function __construct( &$plugin, $plugin_filepath = WPSSOAM_FILEPATH ) {
			$this->p =& $plugin;
			$this->plugin_filepath = $plugin_filepath;
			$this->p->util->add_plugin_filters( $this, array( 
				'get_defaults' => 1,
				'get_post_defaults' => 1,
			) );
			if ( is_admin() ) {
				$this->p->util->add_plugin_filters( $this, array( 
					'option_type' => 2,
					'messages_tooltip_side' => 2,	// tooltip messages for side boxes
					'messages_tooltip_post' => 2,	// tooltip messages for post social settings
					'messages_tooltip' => 2,	// tooltip messages filter
					'messages_info' => 2,		// info messages filter
				) );
				$this->p->util->add_plugin_filters( $this, array( 
					'status_gpl_features' => 3,
					'status_pro_features' => 3,
				), 10, 'wpssoam' );
			} elseif ( ! empty( $this->p->options['am_ws_itunes_app_id'] ) )
				$this->p->util->add_plugin_filters( $this, array( 'meta_name' => 2 ) );
		}

		public function filter_get_defaults( $opts_def ) {
			$opts_def = array_merge( $opts_def, self::$cf['opt']['defaults'] );
			$opts_def = $this->p->util->push_add_to_options( $opts_def, array( 'am_ws' => 'frontend' ) );
			$opts_def = $this->p->util->push_add_to_options( $opts_def, array( 'am_ap' => 'frontend' ) );
			return $opts_def;
		}

		public function filter_get_post_defaults( $opts_def ) {
			$opts_def = array_merge( $opts_def, array(
				'am_ap_ast' => -1,
				'am_iphone_app_id' => '',
				'am_iphone_app_name' => '',
				'am_iphone_app_url' => '',
				'am_ipad_app_id' => '',
				'am_ipad_app_name' => '',
				'am_ipad_app_url' => '',
				'am_gplay_app_id' => '',
				'am_gplay_app_name' => '',
				'am_gplay_app_url' => '',
			) );
			return $opts_def;
		}

		public function filter_option_type( $type, $key ) {
			if ( ! empty( $type ) )
				return $type;

			// remove localization for more generic match
			if ( strpos( $key, '#' ) !== false )
				$key = preg_replace( '/#.*$/', '', $key );

			switch ( $key ) {
				case 'am_iphone_app_id':
				case 'am_ipad_app_id':
					return 'numeric';
					break;
				// text strings that can be blank
				case 'am_ws_itunes_app_id':
				case 'am_ws_itunes_app_aff':
				case 'am_ws_itunes_app_arg':
				case 'am_iphone_app_name':
				case 'am_iphone_app_url':
				case 'am_ipad_app_name':
				case 'am_ipad_app_url':
				case 'am_gplay_app_id':
				case 'am_gplay_app_name':
				case 'am_gplay_app_url':
					return 'ok_blank';
					break;
				case 'am_ap_ast':
					return 'not_blank';
					break;
			}
			return $type;
		}

		public function filter_messages_tooltip_side( $text, $idx ) {
			switch ( $idx ) {
				case 'tooltip-side-website-app-meta':
					$text = __( 'Creates a banner advertisement in Apple\'s mobile Safari for your website\'s mobile App (as an alternative to using a mobile browser).', 'wpsso-am' );
					break;
				case 'tooltip-side-app-product-options':
					$text = __( 'An <em>App Product</em> tab can be added to the Social Settings metabox on Posts, Pages, and custom post types, allowing you to enter specific information about a mobile App.', 'wpsso-am' );
					break;
				case 'tooltip-side-twitter-app-card':
					$text = __( 'The <em>App Product</em> information is used to create meta tags for Twitter\'s App Card (instead of generating a Product Card, for example).', 'wpsso-am' );
					break;
			}
			return $text;
		}

		public function filter_messages_tooltip_post( $text, $idx ) {
			if ( strpos( $idx, 'tooltip-post-am_' ) !== 0 )
				return $text;

			switch ( $idx ) {
				case 'tooltip-post-am_iphone_app_id':
					$text = __( 'The numeric representation of your iPhone App ID in the App Store (example: "307234931").', 'wpsso-am' );
					break;
				case 'tooltip-post-am_iphone_app_name':
					$text = __( 'The name of your iPhone App.', 'wpsso-am' );
					break;
				case 'tooltip-post-am_iphone_app_url':
					$text = __( 'Your iPhone App\'s custom URL scheme (you must include "://" after the scheme name).', 'wpsso-am' );
					break;
				case 'tooltip-post-am_ipad_app_id':
					$text = __( 'The numeric representation of your iPad App ID in the App Store (example: "307234931").', 'wpsso-am' );
					break;
				case 'tooltip-post-am_ipad_app_name':
					$text = __( 'The name of your iPad App.', 'wpsso-am' );
					break;
				case 'tooltip-post-am_ipad_app_url':
					$text = __( 'Your iPad App\'s custom URL scheme (you must include \'://\' after the scheme name).', 'wpsso-am' );
					break;
				case 'tooltip-post-am_gplay_app_id':
					$text = __( 'The fully qualified package name of your Google Play App (example: "com.google.android.apps.maps").', 'wpsso-am' );
					break;
				case 'tooltip-post-am_gplay_app_name':
					$text = __( 'The name of your Google Play App.', 'wpsso-am' );
					break;
				case 'tooltip-post-am_gplay_app_url':
					$text = __( 'Your Google Play App\'s custom URL scheme (you must include \'://\' after the scheme name).', 'wpsso-am' );
					break;
			}
			return $text;
		}

		public function filter_messages_tooltip( $text, $idx ) {
			if ( strpos( $idx, 'tooltip-am_' ) !== 0 )
				return $text;

			switch ( $idx ) {
				case 'tooltip-am_ws_on_index':
					$text = __( 'Add meta tags for the website\'s mobile App to index and archive pages.', 'wpsso-am' );
					break;
				case 'tooltip-am_ws_on_front':
					$text = __( 'Add meta tags for the website\'s mobile App to a static front page.', 'wpsso-am' );
					break;
				case 'tooltip-am_ws_add_to':
					$text = __( 'Add meta tags for the website\'s mobile App to Posts, Pages, and custom post types.', 'wpsso-am' );
					break;
				case 'tooltip-am_ws_itunes_app_id':
					$text = __( 'Your website\'s App ID in the Apple Store (example: "307234931").', 'wpsso-am' );
					break;
				case 'tooltip-am_ws_itunes_app_aff':
					$text = __( 'An optional iTunes affiliate string, if you are an iTunes affiliate.', 'wpsso-am' );
					break;
				case 'tooltip-am_ws_itunes_app_arg':
					$text = __( 'A string, that may include any one or more inline variables, to provide context to your website\'s mobile App. If the user has your mobile App installed, this string may allow them to jump from your website, to the same content in the mobile App.', 'wpsso-am' );
					break;
				case 'tooltip-am_ap_ast':
					$text = __( 'The App Store country providing your App.', 'wpsso-am' );
					break;
				case 'tooltip-am_ap_add_to':
					$text = __( 'Include the <em>App Product</em> tab in the Social Settings metabox on Posts, Pages, etc.', 'wpsso-am' );
					break;
			}
			return $text;
		}

		public function filter_messages_info( $text, $idx ) {
			switch ( $idx ) {
				case 'info-webapp-general':
					$text = '<blockquote class="top-info"><p>'.__( 'If you have a mobile App to access your website (as an alternative to using a mobile web browser), enter its details here.', 'wpsso-am' ).'</p></blockquote>';
					break;
				case 'info-webapp-itunes':
					$text = '<blockquote class="top-info"><p>'.__( 'These values are used to create a <em>banner advertisement</em> in Apple\'s mobile Safari for your <em>website\'s</em> Apple Store App.', 'wpsso-am' ).' '.__( 'The banner advertisement allows users to download and/or switch to the Apple Store App instead of navigating your website using Apple\'s mobile Safari.', 'wpsso-am' ).'</p></blockquote>';
					break;
				case 'info-appmeta-general':
					$text = '<blockquote class="top-info"><p>'.__( 'An <em>App Product</em> tab can be added to the Social Settings metabox on Posts, Pages, and custom post types, allowing you to enter specific information about a mobile App.', 'wpsso-am' ).' '.__( 'The mobile App information is used to create meta tags for Twitter\'s App Card (instead of a Large Image Summary Card, for example).', 'wpsso-am' ).'</p></blockquote>';
					break;
			}
			return $text;
		}

		// adds the website app meta tag to the $mt_name array
		public function filter_meta_name( $mt_name, $use_post = false ) {
			if ( empty( $this->p->options['am_ws_itunes_app_id'] ) )
				return $mt_name;

			if ( ! is_singular() && empty( $this->p->options['am_ws_on_index'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'filter skipped: index page without am_ws_on_index enabled' );
				return $mt_name;
			} elseif ( is_front_page() && empty( $this->p->options['am_ws_on_front'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'filter skipped: front page without am_ws_on_front enabled' );
				return $mt_name;
			} 

			$mt_name['apple-itunes-app'] = 'app-id='.$this->p->options['am_ws_itunes_app_id'];

			if ( ! empty( $this->p->options['am_ws_itunes_app_aff'] ) )
				$mt_name['apple-itunes-app'] .= ', affiliate-data='.$this->p->options['am_ws_itunes_app_aff'];
				
			if ( ! empty( $this->p->options['am_ws_itunes_app_arg'] ) )
				$mt_name['apple-itunes-app'] .= ', app-argument='.$this->p->options['am_ws_itunes_app_arg'];

			return $mt_name;
		}

		public function filter_status_gpl_features( $features, $lca, $info ) {
			$features['Website App Meta'] = array( 
				'status' => $this->p->options['am_ws_itunes_app_id'] ? 'on' : 'off'
			);
			return $features;
		}

		public function filter_status_pro_features( $features, $lca, $info ) {
			$aop = $this->p->check->aop( $lca );
			$features['App Product Options'] = array( 
				'status' => $aop ? 'on' : 'off',
				'td_class' => $aop ? '' : 'blank',
			);
			$features['Twitter App Card'] = array( 
				'status' => $aop ? 'on' : 'off',
				'td_class' => $aop ? '' : 'blank',
			);
			return $features;
		}
	}
}

?>