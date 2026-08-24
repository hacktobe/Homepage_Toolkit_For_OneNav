<?php
/**
 * Remove only settings owned by Homepage Toolkit for OneNav.
 *
 * @package Homepage_Toolkit_For_OneNav
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$htfo_options = get_option( 'io_get_option', array() );

if ( is_array( $htfo_options ) ) {
	foreach ( array( 'htfo_enabled', 'htfo_menus', 'htfo_dock_enabled', 'htfo_dock_buttons' ) as $htfo_key ) {
		unset( $htfo_options[ $htfo_key ] );
	}

	update_option( 'io_get_option', $htfo_options );
}
