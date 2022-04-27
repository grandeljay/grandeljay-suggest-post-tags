<?php
/**
 * Suggest Post Tags
 *
 * @package           SuggestPostTags
 * @author            Jay Trees <github.jay@grandel.anonaddy.me>
 *
 * @wordpress-plugin
 * Plugin Name:       Suggest Post Tags
 * Description:       The Suggest Post Tags plugins helps you reduce the amount of tags you are using by suggesting similar, existing tags.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      8.0
 * Author:            Jay Trees
 * Author URI:        https://github.com/grandeljay/
 * Text Domain:       grandeljay-suggest-post-tags
 */

/**
 * Admin initialisation
 */
define( 'GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE', 'grandeljay_suggest_post_tags_options' );
define( 'GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API', 'grandeljay-suggest-post-tags-options-api' );
define( 'GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME', 'grandeljay-suggest-post-tags-api-key' );
define( 'GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_VALUE', '01234567-89ab-cdef-0123-456789abcdef' );

/**
 * Fires as an admin screen or script is being initialized.
 *
 * @return void
 */
function grandeljay_suggest_post_tags_admin_init(): void {
	/**
	 * Settings
	 */
	add_settings_section(
		id:       GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API,
		title:    __( 'API' ),
		callback: '',
		page:     GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE
	);

	add_settings_field(
		id:       GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME,
		title:     __( 'Key' ),
		callback: 'grandeljay_suggest_post_tags_options_api_key',
		page:     GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE,
		section:  GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API
	);

	register_setting(
		option_group: GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE,
		option_name:  GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME,
		args:         array(
			'type'         => 'string',
			'description'  => __( 'desc' ),
			'show_in_rest' => false,
			'default'      => GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_VALUE,
		)
	);
}

add_action( 'admin_init', 'grandeljay_suggest_post_tags_admin_init' );

/**
 * The HTML for setting "grandeljay-suggest-post-tags-options-api-key"
 */
function grandeljay_suggest_post_tags_options_api_key() {
	$value = get_option( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME );
	?>

	<input type="text" name="<?php echo esc_attr( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME ); ?>" value="<?php echo esc_attr( $value ); ?>" />
	<?php
}

/**
 * Add submenu page
 */
define( 'GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_CAPABILITY_REQUIRED', 'manage_options' );

/**
 * Fires before the administration menu loads in the admin.
 *
 * @return void
 */
function grandeljay_suggest_post_tags_add_options_page(): void {
	add_options_page(
		page_title:  __( 'Suggest Post Tags' ),
		menu_title:  __( 'Suggest Post Tags' ),
		capability:  GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_CAPABILITY_REQUIRED,
		menu_slug:   'grandeljay_suggest_post_tags',
		function:    'grandeljay_suggest_post_tags_submenu_page'
	);
}

add_action( 'admin_menu', 'grandeljay_suggest_post_tags_add_options_page' );

/**
 * HTML for the submenu page
 *
 * @link https://dictionaryapi.com/products/api-collegiate-thesaurus
 */
function grandeljay_suggest_post_tags_submenu_page() {
	if ( ! current_user_can( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_CAPABILITY_REQUIRED ) ) {
		return;
	}
	?>

	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<h2><?php echo esc_html( 'Merriam-Webster API' ); ?></h2>
		<p>
			<?php
				printf(
					/* TRANSLATORS: %s: here (link) */
					esc_html__( 'In order to use the Suggest Post Tags a Merriam-Webster API key is required. You can request it %s.', 'grandeljay-suggest-post-tags' ),
					'<a href="https://dictionaryapi.com/register/index" target="_blank">' . esc_html__( 'here' ) . '</a>'
				);
			?>
		</p>

		<form action="options.php" method="post">
			<?php
			settings_fields( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE );
			do_settings_sections( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_PAGE );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

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
	$api_key = get_option( GRANDELJAY_SUGGEST_POST_TAGS_OPTIONS_API_KEY_NAME );

	foreach ( $results as $result ) {
		$definitions = grandeljay_suggest_post_tags_get_definition( $result, 'thesaurus', $api_key );

		$terms_searched = array(
			sanitize_title( $result ),
		);

		foreach ( $definitions as $definition ) {
			$synonyms = $definition->meta->syns ?? array();

			foreach ( $synonyms as $synonyms_group ) {
				foreach ( $synonyms_group as $synonym ) {
					$term = sanitize_title( $synonym );

					if ( in_array( $term, $terms_searched, true ) ) {
						continue;
					}

					$similar_terms    = get_terms(
						array(
							'taxonomy'   => 'post_tag',
							'fields'     => 'names',
							'name__like' => $term,
						)
					);
					$terms_searched[] = $term;

					$results = array_merge( $results, $similar_terms );
				}
			}
		}
	}

	return $results;
}

add_filter( 'wp_after_tag_search', 'grandeljay_suggest_post_tags_after_tag_search', 10, 3 );

/**
 * Get word definition from Merriam-Webster API
 *
 * @param string $word The word to lookup.
 * @param string $ref  The API to use (@see https://dictionaryapi.com/products/index).
 * @param string $key  The Key to use.
 *
 * @see https://dictionaryapi.com/products/api-collegiate-thesaurus
 *
 * @return array
 */
function grandeljay_suggest_post_tags_get_definition( string $word, string $ref, string $key ): array {
	$uri  = 'https://dictionaryapi.com/api/v3/references/' . rawurldecode( $ref ) . '/json/' . rawurldecode( $word ) . '?key=' . rawurldecode( $key );
	$json = wp_remote_get( $uri );

	return $json;
};
