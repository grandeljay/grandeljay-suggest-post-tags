<?php
class ArrayTest extends WP_UnitTestCase {

	public function test_grandeljay_suggest_post_tags_after_tag_search() {
		$results = array();
		$tax     = new WP_Taxonomy( 'taxonomy-key', 'post_tag' );
		$s       = 'Search query';

		$this->assertIsArray(
			grandeljay_suggest_post_tags_after_tag_search( $results, $tax, $s )
		);
	}

}
