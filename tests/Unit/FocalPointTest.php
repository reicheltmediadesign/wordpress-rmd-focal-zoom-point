<?php
/**
 * @package RMD\FocalZoomPoint
 */

declare(strict_types=1);

namespace RMD\FocalZoomPoint\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\FocalZoomPoint\Domain\FocalPoint;

final class FocalPointTest extends TestCase {

	public function test_rejects_invalid_input(): void {
		$this->assertNull( FocalPoint::from( null ) );
		$this->assertNull( FocalPoint::from( '' ) );
		$this->assertNull( FocalPoint::from( [] ) );
		$this->assertNull( FocalPoint::from( [ 'x' => 10 ] ) );
		$this->assertNull( FocalPoint::from( [ 'x' => 'left', 'y' => 10 ] ) );
	}

	public function test_clamps_and_rounds(): void {
		$point = FocalPoint::from(
			[
				'x'    => 12.34,
				'y'    => 120,
				'zoom' => 1.234,
			]
		);
		$this->assertSame( 12.3, $point->x );
		$this->assertSame( 100.0, $point->y );
		$this->assertSame( 1.23, $point->zoom );

		$point = FocalPoint::from(
			[
				'x'    => -5,
				'y'    => '33.35',
				'zoom' => 9,
			]
		);
		$this->assertSame( 0.0, $point->x );
		$this->assertSame( 33.4, $point->y );
		$this->assertSame( FocalPoint::MAX_ZOOM, $point->zoom );

		$this->assertSame( FocalPoint::MIN_ZOOM, FocalPoint::from( [ 'x' => 1, 'y' => 1, 'zoom' => 0.5 ] )->zoom );
	}

	public function test_zoom_defaults_to_one(): void {
		$point = FocalPoint::from( [ 'x' => 20, 'y' => 30 ] );
		$this->assertSame( 1.0, $point->zoom );
		$this->assertFalse( $point->has_zoom() );
	}

	public function test_accepts_objects(): void {
		$point = FocalPoint::from( (object) [ 'x' => 20, 'y' => 30, 'zoom' => 2 ] );
		$this->assertSame( [ 'x' => 20.0, 'y' => 30.0, 'zoom' => 2.0 ], $point->to_array() );
		$this->assertTrue( $point->has_zoom() );
	}

	public function test_default_is_center_without_zoom(): void {
		$this->assertTrue( FocalPoint::center()->is_default() );
		$this->assertTrue( FocalPoint::from( [ 'x' => '50', 'y' => 50.04, 'zoom' => 1 ] )->is_default() );
		$this->assertFalse( FocalPoint::from( [ 'x' => 50, 'y' => 50, 'zoom' => 1.5 ] )->is_default() );
		$this->assertFalse( FocalPoint::from( [ 'x' => 40, 'y' => 50 ] )->is_default() );
	}

	public function test_declarations(): void {
		$point = FocalPoint::from( [ 'x' => 20, 'y' => 12.5, 'zoom' => 1.5 ] );

		$this->assertSame(
			'--rmd-fzp-x: 20%; --rmd-fzp-y: 12.5%; --rmd-fzp-pos: 20% 12.5%; --rmd-fzp-zoom: 1.5;',
			$point->declarations()
		);
		$this->assertSame(
			'object-position: 20% 12.5%; --rmd-fzp-x: 20%; --rmd-fzp-y: 12.5%; --rmd-fzp-pos: 20% 12.5%; --rmd-fzp-zoom: 1.5; --rmd-fzp-ratio: 1.5;',
			$point->declarations( true, 1.5 )
		);
		$this->assertStringContainsString( '--rmd-fzp-ratio: 0.6667', $point->declarations( false, 2 / 3 ) );
	}
}
