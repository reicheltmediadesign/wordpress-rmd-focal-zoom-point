<?php
/**
 * @package RMD\FocalZoomPoint
 */

declare(strict_types=1);

namespace RMD\FocalZoomPoint\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\FocalZoomPoint\Domain\FocalPoint;
use RMD\FocalZoomPoint\Domain\ImageFocus;

final class ImageFocusTest extends TestCase {

	public function test_reads_plain_points(): void {
		$focus = ImageFocus::from( [ 'x' => 20, 'y' => 30, 'zoom' => 1.5 ] );
		$this->assertSame( [ 'x' => 20.0, 'y' => 30.0, 'zoom' => 1.5 ], $focus->to_array() );
		$this->assertSame( [], $focus->variants );
	}

	public function test_rejects_invalid_input(): void {
		$this->assertNull( ImageFocus::from( null ) );
		$this->assertNull( ImageFocus::from( 'x' ) );
		$this->assertNull( ImageFocus::from( [] ) );
		$this->assertNull( ImageFocus::from( [ 'variants' => [ 'round' => [ 'x' => 'a' ] ] ] ) );
	}

	public function test_reads_variants_and_drops_invalid_ones(): void {
		$focus = ImageFocus::from(
			(object) [
				'variants' => (object) [
					'round'   => [ 'x' => 70, 'y' => 20, 'zoom' => 2 ],
					'Bad Key' => [ 'x' => 1, 'y' => 1 ],
					'wide'    => [ 'x' => 'left' ],
				],
			]
		);

		$this->assertTrue( $focus->base->is_default() );
		$this->assertSame( [ 'round' ], array_keys( $focus->variants ) );
		$this->assertSame( 70.0, $focus->variants['round']->x );
		$this->assertFalse( $focus->is_empty() );
	}

	public function test_resolve(): void {
		$focus = ImageFocus::from(
			[
				'x'        => 20,
				'y'        => 30,
				'variants' => [ 'round' => [ 'x' => 50, 'y' => 50 ] ],
			]
		);

		$this->assertSame( 20.0, $focus->resolve( null )->x );
		$this->assertSame( 20.0, $focus->resolve( 'portrait' )->x );
		// Own center values count: they differ from the general point.
		$this->assertTrue( $focus->resolve( 'round' )->is_default() );

		$only_variant = ImageFocus::from( [ 'variants' => [ 'round' => [ 'x' => 10, 'y' => 10 ] ] ] );
		$this->assertNull( $only_variant->resolve( null ) );
		$this->assertNull( $only_variant->resolve( 'wide' ) );
		$this->assertSame( 10.0, $only_variant->resolve( 'round' )->x );
	}

	public function test_empty(): void {
		$this->assertTrue( ImageFocus::empty()->is_empty() );
		$this->assertTrue( ImageFocus::from( [ 'x' => 50, 'y' => 50 ] )->is_empty() );
		$this->assertFalse( ImageFocus::empty()->with_variant( 'round', FocalPoint::center() )->is_empty() );
	}

	public function test_with_variant_and_keeping_unknown(): void {
		$stored = ImageFocus::from(
			[
				'x'        => 10,
				'y'        => 10,
				'variants' => [
					'round'      => [ 'x' => 1, 'y' => 1 ],
					'theme-hero' => [ 'x' => 2, 'y' => 2 ],
				],
			]
		);
		$submitted = ImageFocus::of( FocalPoint::from( [ 'x' => 40, 'y' => 40 ] ) )
			->with_variant( 'wide', FocalPoint::from( [ 'x' => 3, 'y' => 3 ] ) )
			->with_variant( 'Not valid', FocalPoint::center() );

		$merged = $submitted->keeping_unknown( $stored, [ 'wide', 'round' ] );

		$this->assertSame( 40.0, $merged->base->x );
		$this->assertSame( [ 'wide', 'theme-hero' ], array_keys( $merged->variants ) );
		$this->assertSame(
			[ 'x' => 2.0, 'y' => 2.0, 'zoom' => 1.0 ],
			$merged->to_array()['variants']['theme-hero']
		);
	}
}
