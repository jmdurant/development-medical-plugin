<?php

// 2023/09/24: Added /blog/ prefix to blog posts in the settings. This filter makes the old urls redirect if 404.
add_action( 'template_redirect', 'gcm_redirect_canonical' );

/**
 * Redirect blog posts without /blog/ prefix to the same URL with /blog/ prefix.
 *
 * @return void
 */
function gcm_redirect_canonical() {
	// Only front end, and only if 404
	if ( is_admin() || ! is_404() ) return;
	
	// Prevent redirect loops when testing validity of the new page
	if ( isset($_GET['gcm_no_redirect']) ) return;
	
	// Assume the url does not have /blog/ prefix, but might have a language prefix. Add /blog/ such as:
	// /my-blog-post/     => /blog/my-blog-post/
	// /es/my-blog-post/  => /es/blog/my-blog-post/
	// /ru/my-blog-post/  => /ru/blog/my-blog-post/
	
	// Get the url requested by the visitor
	$current_url = $_SERVER['REQUEST_URI'];
	
	// Check the cache first, based on path only
	$cached_path = gcm_get_cached_blog_redirect( $current_url );
	
	// If cached, redirect to the new url
	if ( $cached_path ) {
		
		// Update the URL to the new path
		$from_path = key( $cached_path );
		$to_path = $cached_path[ $from_path ];
		
		$redirect_url = str_replace( $from_path, $to_path, $current_url );
		
		// Redirect
		wp_redirect( $redirect_url, 301 );
		exit;
	}
	
	// Get the language prefix, if any
	$lang_prefix = '/';
	$page_url = $current_url;
	
	if ( preg_match( '/^\/[a-z]{2}\//', $current_url, $matches ) ) {
		$lang_prefix = $matches[0];
		$page_url = '/' . substr( $current_url, strlen( $lang_prefix ) );
	}
	
	// Adjust the URL to include /blog/ prefix
	$redirect_url = $lang_prefix . 'blog' . $page_url;
	
	// Check if that URL exists, but without doing another redirect
	$test_url = add_query_arg( array('gcm_no_redirect' => true), site_url($redirect_url) );
	$args = array(
		'timeout'     => 5,
		'redirection' => 1,
		'sslverify' => false,
	);
	$response = wp_remote_get( $test_url, $args );
	
	// If the response code is 200, save to cache, then redirect
	if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
		
		// Save cached target
		gcm_add_cached_blog_redirect( $current_url, $redirect_url );
		
		// Redirect
		wp_redirect( $redirect_url, 301 );
		exit;
		
	}
	
	// Otherwise do nothing, let WP show a 404 page.
}

/**
 * Get the cached blog redirects.
 *
 * @return array|false|mixed
 */
function gcm_get_all_cached_blog_redirects() {
	$cached = get_option( 'gcm_blog_redirects' );
	if ( ! $cached ) $cached = array();
	
	return $cached;
}

/**
 * Get a single cached blog redirect
 *
 * @param string $url   Relative page path such as: /my-blog-post/ or /es/my-blog-post/
 *
 * @return array|false  Returns an array with the key as the old path and the value as the new path. False if not found.
 */
function gcm_get_cached_blog_redirect( $url ) {
	$path = parse_url( $url, PHP_URL_PATH );
	
	$cached = gcm_get_all_cached_blog_redirects();
	
	if ( isset($cached[$path]) ) {
		return array( $path => $cached[$path] );
	}
	
	return false;
}

/**
 * Add a new blog redirect to the cache.
 *
 * @param $old_url
 * @param $new_url
 *
 * @return array|mixed
 */
function gcm_add_cached_blog_redirect( $old_url, $new_url ) {
	$cached = gcm_get_all_cached_blog_redirects();
	
	$old_path = parse_url( $old_url, PHP_URL_PATH );
	$new_path = parse_url( $new_url, PHP_URL_PATH );
	
	if ( $old_path && $new_path ) {
		$cached[ $old_path ] = $new_path;
		update_option( 'gcm_blog_redirects', $cached, false );
	}
	
	return $cached;
}