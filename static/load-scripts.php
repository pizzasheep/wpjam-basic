<?php

/**
 * copy from /wp-admin/load-scripts.php
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging.
 */
error_reporting(0);

/** Set ABSPATH for execution */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/' );	// Denis 加多三层
}

define( 'WPINC', 'wp-includes' );

$load = $_GET['load'];
if ( is_array( $load ) )
	$load = implode( '', $load );

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
$load = array_unique( explode( ',', $load ) );

if ( empty($load) )
	exit;

require( ABSPATH . 'wp-admin/includes/noop.php' );
require( ABSPATH . WPINC . '/script-loader.php' );
require( ABSPATH . WPINC . '/version.php' );

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$expires_offset = 31536000; // 1 year
$out = '';

$wp_scripts = new WP_Scripts();
wp_default_scripts( $wp_scripts );

if(function_exists('wp_default_packages_vendor')){
	wp_default_packages_vendor( $wp_scripts );
	wp_default_packages_scripts( $wp_scripts );
}

/** added by denis */ 
function _doing_it_wrong( $function, $message, $version ) {
}

$relative_url	= str_replace(ABSPATH, '', __DIR__);

wp_enqueue_script('wpjam-script',	$relative_url.'/script.js', ['jquery']);
wp_enqueue_script('wpjam-form',		$relative_url.'/form.js', ['jquery']);
wp_enqueue_script('raphael',		$relative_url.'/raphael.min.js', ['jquery']);
wp_enqueue_script('morris',			$relative_url.'/morris.min.js', ['jquery']);


/** end of added by denis */ 

if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) === $wp_version ) {
	$protocol = $_SERVER['SERVER_PROTOCOL'];
	if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ) ) ) {
		$protocol = 'HTTP/1.0';
	}
	header( "$protocol 304 Not Modified" );
	exit();
}

foreach ( $load as $handle ) {
	if ( !array_key_exists($handle, $wp_scripts->registered) )
		continue;

	$path = ABSPATH . $wp_scripts->registered[$handle]->src;
	$out .= get_file($path) . "\n";
}

header("Etag: $wp_version");
header('Content-Type: application/javascript; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
