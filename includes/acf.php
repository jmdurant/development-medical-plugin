<?php

/**
 * Move the order forms page added by ZM Order Forms to the Great City Medical menu.
 *
 * @param array $page {
 *      @type string        $page_title
 *      @type string        $menu_title
 *      @type string        $menu_slug
 *      @type string        $capability
 *      @type string        $parent_slug
 *      @type null|string   $position
 *      @type false|string  $icon_url
 *      @type bool          $redirect
 *      @type string        $post_id
 *      @type bool          $autoload
 *      @type string        $update_button
 *      @type string        $updated_message
 * }
 * @param string $slug
 *
 * @return array
 */
function gcm_move_orders_menu( $page, $slug ) {
	if ( $page['menu_slug'] === 'zmof-settings' ) {
		$page['title'] = 'Order Forms';
		$page['page_title'] = 'Order Form Settings';
		$page['menu_title'] = 'Order Forms';
		$page['parent_slug'] = 'acf-gcm-settings';
	}
	
	return $page;
}
// $page = apply_filters( 'acf/get_options_page', $page, $slug );
add_filter( 'acf/get_options_page', 'gcm_move_orders_menu', 20, 2 );