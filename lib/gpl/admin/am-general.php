<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoAmGplAdminAmGeneral' ) ) {

	class WpssoAmGplAdminAmGeneral {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array( 
				'am_appmeta_general_rows' => 2,
			), 100 );
		}

		public function filter_am_appmeta_general_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$table_rows[] = '<td colspan="2">'.$this->p->msgs->get( 'info-appmeta-general' ).'</td>';
			$table_rows[] = '<td colspan="2">'.$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpssoam' ) ).'</td>';

			$add_to_checkboxes = '';
			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {
				$add_to_checkboxes .= '<p>'.$form->get_no_checkbox( 'am_ap_add_to_'.$pt->name ). ' ' .
					$pt->label.( empty( $pt->description ) ? '' : ' ('.$pt->description.')' ).'</p>';
			}

			$table_rows[] = $form->get_th_html( _x( 'Show Tab on Post Types', 'option label', 'wpsso-am' ), '', 'am_ap_add_to' ).
			'<td class="blank">'.$add_to_checkboxes.'</td>';

			$table_rows[] = $form->get_th_html( _x( 'Default App Store Territory', 'option label', 'wpsso-am' ), '', 'am_ap_ast' ).
			'<td class="blank">'.$form->get_no_select( 'am_ap_ast', WpssoAmConfig::$app_stores ).'</td>';

			return $table_rows;
		}
	}
}

