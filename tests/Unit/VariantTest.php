<?php
/**
 * @package RMD\FocalZoomPoint
 */

declare(strict_types=1);

namespace RMD\FocalZoomPoint\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\FocalZoomPoint\Domain\Variant;

final class VariantTest extends TestCase {

	private function defaults(): array {
		return Variant::list_from(
			[
				[ 'key' => 'wide', 'label' => 'Wide banner', 'ratio' => '16 / 5' ],
				[ 'key' => 'landscape', 'label' => 'Landscape', 'ratio' => '3 / 2' ],
				[ 'key' => 'portrait', 'label' => 'Portrait', 'ratio' => '3 / 4' ],
				[ 'key' => 'round', 'label' => 'Circle', 'ratio' => '1', 'round' => true ],
			]
		);
	}

	public function test_parse_ratio(): void {
		$this->assertSame( 16 / 9, Variant::parse_ratio( '16/9' ) );
		$this->assertSame( 1.5, Variant::parse_ratio( ' 3 / 2 ' ) );
		$this->assertSame( 1.0, Variant::parse_ratio( '1' ) );
		$this->assertSame( 0.75, Variant::parse_ratio( 0.75 ) );
		$this->assertNull( Variant::parse_ratio( 'auto' ) );
		$this->assertNull( Variant::parse_ratio( '' ) );
		$this->assertNull( Variant::parse_ratio( '0/1' ) );
		$this->assertNull( Variant::parse_ratio( null ) );
	}

	public function test_list_from(): void {
		$list = Variant::list_from(
			[
				[ 'label' => 'Team Card', 'ratio' => 'nonsense' ],
				[ 'key' => 'team-card', 'label' => 'Duplicate' ],
				[ 'key' => 'Bad Key', 'label' => 'Bad' ],
				[ 'label' => '' ],
				'not an array',
				[ 'key' => 'round', 'label' => 'Circle', 'round' => 1 ],
			]
		);

		$this->assertSame( [ 'team-card', 'round' ], array_keys( $list ) );
		$this->assertSame( 'Team Card', $list['team-card']->label );
		$this->assertSame( '1', $list['team-card']->ratio );
		$this->assertFalse( $list['team-card']->round );
		$this->assertTrue( $list['round']->round );
		$this->assertSame( [], Variant::list_from( null ) );
	}

	public function test_match_by_ratio(): void {
		$variants = $this->defaults();

		$this->assertSame( 'landscape', Variant::match( $variants, 16 / 9, false ) );
		$this->assertSame( 'landscape', Variant::match( $variants, 4 / 3, false ) );
		$this->assertSame( 'wide', Variant::match( $variants, 21 / 9, false ) );
		$this->assertSame( 'portrait', Variant::match( $variants, 9 / 16, false ) );
		$this->assertSame( 'round', Variant::match( $variants, 1.0, false ) );
		$this->assertNull( Variant::match( $variants, null, false ) );
		$this->assertNull( Variant::match( [], 1.0, false ) );
	}

	public function test_match_round(): void {
		$variants = $this->defaults();

		$this->assertSame( 'round', Variant::match( $variants, null, true ) );
		$this->assertSame( 'round', Variant::match( $variants, 16 / 9, true ) );

		unset( $variants['round'] );
		$this->assertNull( Variant::match( $variants, null, true ) );
	}
}
