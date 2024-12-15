<?php

namespace Tests;

use PHPUnit\Runner\Version;

abstract class TestCase extends \WP_UnitTestCase
{
	public function expectDeprecated()
    {
		$version = class_exists( Version::class ) && method_exists( Version::class, 'id' )
			? Version::id()
			: null;

        if ( $version && version_compare( $version, '10', '>=' ) ) {
            return;
        }

        parent::expectDeprecated();
	}
}