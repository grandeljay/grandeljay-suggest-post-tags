<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase {

	public function test_grandeljay_suggest_post_tags_after_tag_search() {
		$terms = array(
			'food',
			'fruit',
		);

		$this->assertIsArray( $terms );
	}

}
