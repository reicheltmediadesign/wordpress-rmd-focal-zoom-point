<?php
/**
 * @package RMD\FocalZoomPoint
 */

declare(strict_types=1);

namespace RMD\FocalZoomPoint\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\FocalZoomPoint\Domain\InlineStyle;

final class InlineStyleTest extends TestCase {

	public function test_append(): void {
		$this->assertSame( 'a: 1;', InlineStyle::append( '', 'a: 1;' ) );
		$this->assertSame( 'width:320px; a: 1;', InlineStyle::append( 'width:320px', 'a: 1;' ) );
		$this->assertSame( 'width:320px; a: 1;', InlineStyle::append( 'width:320px;  ', 'a: 1;' ) );
		$this->assertSame( 'width:320px', InlineStyle::append( 'width:320px', '' ) );
	}

	public function test_has_property(): void {
		$this->assertTrue( InlineStyle::has_property( 'object-position: 10% 20%', 'object-position' ) );
		$this->assertTrue( InlineStyle::has_property( 'width:1px;OBJECT-POSITION:center', 'object-position' ) );
		$this->assertFalse( InlineStyle::has_property( '--x-object-position: 1; width: 2px', 'object-position' ) );
		$this->assertFalse( InlineStyle::has_property( '', 'object-position' ) );
	}
}
