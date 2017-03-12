<?php

namespace QTS;
class QtranslateSlug_Test extends \WP_UnitTestCase {

	function testActive() {
		$this->assertTrue( ! empty( QTS_VERSION ), 'Is QtranslateSlug Plugin active?' );
	}
}

