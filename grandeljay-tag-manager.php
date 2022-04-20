<?php
/**
 * Tag Manager
 *
 * @package           TagManager
 * @author            Jay Trees <github.jay@grandel.anonaddy.me>
 *
 * @wordpress-plugin
 * Plugin Name:       Tag Manager
 * Plugin URI:        https://example.com/plugin-name
 * Description:       The Tag Manager helps you reduce the amount of tags you are using by suggesting better tags.
 * Version:           0.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jay Trees
 * Author URI:        https://github.com/grandeljay/
 * Text Domain:       grandeljay-tag-manager
 */

/**
 * Create taxonomy "Suggested Tags"
 */
function grandeljay_tag_manager_taxonomoy_create() {
	$labels = array(
		'name'                       => _x( 'Suggested Tags', 'taxonomy general name', 'grandeljay-tag-manager' ),
		'singular_name'              => _x( 'Suggested Tag', 'taxonomy singular name', 'grandeljay-tag-manager' ),
		'search_items'               => __( 'Search Suggested Tags', 'grandeljay-tag-manager' ),
		'popular_items'              => __( 'Popular Suggested Tags', 'grandeljay-tag-manager' ),
		'all_items'                  => __( 'All Suggested Tags', 'grandeljay-tag-manager' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Suggested Tag', 'grandeljay-tag-manager' ),
		'update_item'                => __( 'Update Suggested Tag', 'grandeljay-tag-manager' ),
		'add_new_item'               => __( 'Add New Suggested Tag', 'grandeljay-tag-manager' ),
		'new_item_name'              => __( 'New Suggested Tag Name', 'grandeljay-tag-manager' ),
		'separate_items_with_commas' => __( 'Separate suggested tags with commas', 'grandeljay-tag-manager' ),
		'add_or_remove_items'        => __( 'Add or remove suggested tags', 'grandeljay-tag-manager' ),
		'choose_from_most_used'      => __( 'Choose from the most used suggested tags', 'grandeljay-tag-manager' ),
		'not_found'                  => __( 'No suggested tags found.', 'grandeljay-tag-manager' ),
		'menu_name'                  => __( 'Suggested Tags', 'grandeljay-tag-manager' ),
	);

	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => false,
	);

	register_taxonomy( 'post_tag_suggested', 'post', $args );
}

add_action( 'init', 'grandeljay_tag_manager_taxonomoy_create' );

/**
 * Register taxonomy "Suggested Tags"
 *
 * @return void
 */
function grandeljay_tag_manager_taxonomy_register() {
	register_taxonomy_for_object_type( 'post_tag_suggested', 'post' );
}

add_action( 'init', 'grandeljay_tag_manager_taxonomy_register' );


/**
 * Initialise
 */
function grandeljay_tag_manager_current_screen() {
	$screen = get_current_screen();

	if ( 'edit-post' === $screen->id ) {
		$wp_posts = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'post',
			)
		);
		$wp_tags  = get_tags(
			array(
				'taxonomy' => 'post_tag',
			)
		);

		$suggested_tags = array(
			'gun' => 'weapon',
		);

		foreach ( $wp_posts as $wp_post ) {
			$wp_post_tags = wp_get_post_tags( $wp_post->ID );

			foreach ( $wp_post_tags as $wp_post_tag ) {
				$key = $wp_post_tag->slug;

				/**
				 * Determine if a suggested tag exists for the WP_Post post_tag
				 */
				if ( isset( $suggested_tags[ $key ] ) ) {
					/**
					 * Determine if the suggested post_tag exists as a post_tag
					 */
					foreach ( $wp_tags as $wp_tag ) {
						if ( $wp_tag->slug === $suggested_tags[ $key ] ) {
							/**
							 * Set the post_tag as post_tag_suggested
							 */
							global $wpdb;

							/**
							 * Insert "post_tag_suggested" into wp_term_taxonomy
							 */
							$wpdb->insert(
								$wpdb->term_taxonomy,
								array(
									'term_id'  => $wp_tag->term_id,
									'taxonomy' => 'post_tag_suggested',
								)
							);

							/**
							 * Get the newly created term
							 */
							$post_tag_suggested = get_term( $wp_tag->term_id, 'post_tag_suggested' );

							/**
							 * Insert the term relationship into wp_term_relationships
							 */
							$wpdb->insert(
								$wpdb->term_relationships,
								array(
									'object_id'        => $wp_post->ID,
									'term_taxonomy_id' => $post_tag_suggested->term_taxonomy_id,
								)
							);

							break;
						}
					}
				}
			}
		}
	}

}

add_action( 'current_screen', 'grandeljay_tag_manager_current_screen' );
