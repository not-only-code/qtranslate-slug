<?php
/**
 * Class QtranslateSlug_Test
 *
 * @package Qtranslate_Slug
 */

/**
 * Base tests for QTS test
 */
namespace QTS;
class QtranslateSlug_Test extends \WP_UnitTestCase {

	/**
	 * check if qtranslate-slug is active
	 */

	function testActive() {
		$this->assertTrue( ! empty( QTS_VERSION ), 'Is QtranslateSlug Plugin active?' );
	}
}

