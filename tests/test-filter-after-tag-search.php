<?php
/**
 * Test wp_after_tag_search filter
 *
 * @package SuggestPostTags
 * @author  Jay Trees <github.jay@grandel.anonaddy.me>
 */

/**
 * Test class
 */
class GrandeljaySuggestPostTagsAfterTagSearch extends WP_UnitTestCase {

	/**
	 * Test if results is an array containing string values.
	 */
	public function test_grandeljay_suggest_post_tags_after_tag_search() {
		$results = array(
			'hello',
			'3454',
		);
		$tax     = new WP_Taxonomy( 'taxonomy-key', 'post_tag' );
		$s       = 'Search query';

		$results = grandeljay_suggest_post_tags_after_tag_search( $results, $tax, $s );

		$this->assertIsArray( $results );

		foreach ( $results as $result ) {
			$this->assertIsString( $result );
		}
	}

}
