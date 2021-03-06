<?php
/**
 * Suggest Post Tags
 *
 * @package           SuggestPostTags
 * @author            Jay Trees <github.jay@grandel.anonaddy.me>
 *
 * @wordpress-plugin
 * Plugin Name:       Suggest Post Tags
 * Description:       The Suggest Post Tags plugin helps you reduce the amount of tags you are using by suggesting similar, existing tags.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      8.0
 * Author:            Jay Trees
 * Author URI:        https://github.com/grandeljay/
 * Text Domain:       grandeljay-suggest-post-tags
 */

/**
 * Filters the tag results.
 *
 * @param array       $results The default search results from `get_terms`.
 * @param WP_Taxonomy $tax     The taxonomy object.
 * @param string      $s       The search term.
 *
 * @return array
 */
function grandeljay_suggest_post_tags_after_tag_search( array $results, WP_Taxonomy $tax, string $s ): array {
	/**
	 * Make API request
	 */
	$request_url = 'https://api.datamuse.com/words?ml=' . rawurlencode( $s );
	$response    = wp_remote_get( $request_url );

	/**
	 * Request failed
	 */
	if ( is_wp_error( $response ) ) {
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			foreach ( $response->errors as $id => $messages ) {
				foreach ( $messages as $message ) {
					/**
					 * Disable warnings
					 *
					 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					 */
					trigger_error( $message, E_USER_WARNING );
					/**
					 * Enable warnings
					 *
					 * phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
					 * phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					 */
				}
			}
		}

		return $results;
	}

	/**
	 * Find existing post tags
	 */
	$s_similar = json_decode( $response['body'] );

	foreach ( $s_similar as $s_result ) {
		$terms_similar = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'fields'     => 'names',
				'name__like' => $s_result->word,
				'hide_empty' => false,
			)
		);

		foreach ( $terms_similar as $term_name ) {
			if ( ! in_array( $term_name, $results, true ) ) {
				$results[] = $term_name;
			}
		}
	}

	return $results;
}

add_filter( 'wp_after_tag_search', 'grandeljay_suggest_post_tags_after_tag_search', 10, 3 );
