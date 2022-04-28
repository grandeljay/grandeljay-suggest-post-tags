<?php
/**
 * Set up environment for my plugin's tests suite.
 */

define( 'WWW', 'C:/laragon/www' );

/**
 * The path to the WordPress tests checkout.
 */
define( 'WP_TESTS_DIR', WWW . '/wordpress-develop/tests/phpunit' );

/**
 * The path to the main file of the plugin to test.
 */
define( 'TEST_PLUGIN_FILE', WWW . '/wordpress/wp-content/plugins/grandeljay-suggest-post-tags/grandeljay-suggest-post-tags.php' );

/**
 * The WordPress tests functions.
 *
 * We are loading this so that we can add our tests filter
 * to load the plugin, using tests_add_filter().
 */
require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Manually load the plugin main file.
 *
 * The plugin won't be activated within the test WP environment,
 * that's why we need to load it manually.
 *
 * You will also need to perform any installation necessary after
 * loading your plugin, since it won't be installed.
 */
function _manually_load_plugin() {
	require TEST_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Sets up the WordPress test environment.
 *
 * We've got our action set up, so we can load this now,
 * and viola, the tests begin.
 */
require WP_TESTS_DIR . '/includes/bootstrap.php';
