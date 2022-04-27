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
 * Description:       The Tag Manager helps you reduce the amount of tags you are using by suggesting similar, existing tags.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      8.0
 * Author:            Jay Trees
 * Author URI:        https://github.com/grandeljay/
 * Text Domain:       grandeljay-tag-manager
 */

/**
 * Admin initialisation
 */
define( 'GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE', 'grandeljay_tag_manager_options' );
define( 'GRANDELJAY_TAG_MANAGER_OPTIONS_API', 'grandeljay-tag-manager-options-api' );
define( 'GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME', 'grandeljay-tag-manager-api-key' );
define( 'GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_VALUE', '01234567-89ab-cdef-0123-456789abcdef' );

/**
 * Fires as an admin screen or script is being initialized.
 *
 * @return void
 */
function grandeljay_tag_manager_admin_init(): void {
	/**
	 * Settings
	 */
	add_settings_section(
		id:       GRANDELJAY_TAG_MANAGER_OPTIONS_API,
		title:    __( 'API' ),
		callback: '',
		page:     GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE
	);

	add_settings_field(
		id:       GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME,
		title:     __( 'Key' ),
		callback: 'grandeljay_tag_manager_options_api_key',
		page:     GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE,
		section:  GRANDELJAY_TAG_MANAGER_OPTIONS_API
	);

	register_setting(
		option_group: GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE,
		option_name:  GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME,
		args:         array(
			'type'         => 'string',
			'description'  => __( 'desc' ),
			'show_in_rest' => false,
			'default'      => GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_VALUE,
		)
	);
}

add_action( 'admin_init', 'grandeljay_tag_manager_admin_init' );

/**
 * The HTML for setting "grandeljay-tag-manager-options-api-key"
 */
function grandeljay_tag_manager_options_api_key() {
	$value = get_option( GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME );
	?>

	<input type="text" name="<?php echo esc_attr( GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME ); ?>" value="<?php echo esc_attr( $value ); ?>" />
	<?php
}

/**
 * Add submenu page
 */
define( 'GRANDELJAY_TAG_MANAGER_OPTIONS_CAPABILITY_REQUIRED', 'manage_options' );

/**
 * Fires before the administration menu loads in the admin.
 *
 * @return void
 */
function grandeljay_tag_manager_add_options_page(): void {
	add_options_page(
		page_title:  __( 'Tag Manager' ),
		menu_title:  __( 'Tag Manager' ),
		capability:  GRANDELJAY_TAG_MANAGER_OPTIONS_CAPABILITY_REQUIRED,
		menu_slug:   'grandeljay_tag_manager',
		function:    'grandeljay_tag_manager_submenu_page'
	);
}

add_action( 'admin_menu', 'grandeljay_tag_manager_add_options_page' );

/**
 * HTML for the submenu page
 *
 * @link https://dictionaryapi.com/products/api-collegiate-thesaurus
 */
function grandeljay_tag_manager_submenu_page() {
	if ( ! current_user_can( GRANDELJAY_TAG_MANAGER_OPTIONS_CAPABILITY_REQUIRED ) ) {
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
					esc_html__( 'In order to use the Tag Manager a Merriam-Webster API key is required. You can request it %s.', 'grandeljay-tag-manager' ),
					'<a href="https://dictionaryapi.com/register/index" target="_blank">' . esc_html__( 'here' ) . '</a>'
				);
			?>
		</p>

		<form action="options.php" method="post">
			<?php
			settings_fields( GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE );
			do_settings_sections( GRANDELJAY_TAG_MANAGER_OPTIONS_PAGE );
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
function grandeljay_tag_manager_after_tag_search( array $results, WP_Taxonomy $tax, string $s ): array {
	$api_key = get_option( GRANDELJAY_TAG_MANAGER_OPTIONS_API_KEY_NAME );

	foreach ( $results as $result ) {
		$definitions = grandeljay_tag_manager_get_definition( $result, 'thesaurus', $api_key );

		$terms_searched = array(
			sanitize_title( $result ),
		);

		foreach ( $definitions as $definition ) {
			$synonyms = $definition->meta->syns;

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

add_filter( 'wp_after_tag_search', 'grandeljay_tag_manager_after_tag_search', 10, 3 );

/**
 * Get word definition from Merriam-Webster API
 *
 * @param string $word The word to lookup.
 * @param string $ref  The API to use (@see https://dictionaryapi.com/products/index).
 * @param string $key  The KEy to use.
 *
 * @see https://dictionaryapi.com/products/api-collegiate-thesaurus
 *
 * @return string
 */
function grandeljay_tag_manager_get_definition( string $word, string $ref, string $key ): string {
	$uri  = 'https://dictionaryapi.com/api/v3/references/' . rawurldecode( $ref ) . '/json/' . rawurldecode( $word ) . '?key=' . rawurldecode( $key );
	$json = json_decode( wp_remote_get( $uri ) );

	return $json;
};
