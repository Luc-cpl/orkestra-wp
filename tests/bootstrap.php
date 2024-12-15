<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package My_Plugin
 */

use PHPUnit\Framework\Exception;

$vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';

if ( file_exists( $vendor_autoload ) ) {
	require_once $vendor_autoload;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/orkestra-wp.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * PHPUnit 10 compat.
 * This classes where removed in PHPUnit 10 without a replacement.
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/5062
 */
class_alias( Exception::class, 'PHPUnit\Framework\Error\Deprecated' );
class_alias( Exception::class, 'PHPUnit\Framework\Error\Notice' );
class_alias( Exception::class, 'PHPUnit\Framework\Error\Warning' );
class_alias( Exception::class, 'PHPUnit\Framework\Warning' );
class_alias( Exception::class, 'PHPUnit\Framework\TestListener' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Load the TestCase class.
if ( ! class_exists( Tests\TestCase::class ) && file_exists( __DIR__ . '/TestCase.php' ) ) {
    require_once __DIR__ . '/TestCase.php';
}