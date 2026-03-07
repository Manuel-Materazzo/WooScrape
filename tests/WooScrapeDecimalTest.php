<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( WooScrapeDecimal::class )]
class WooScrapeDecimalTest extends TestCase {

	// ── Constructor ────────────────────────────────────────────────────────

	public function test_construct_from_string(): void {
		$d = new WooScrapeDecimal( '12.50' );
		$this->assertSame( '12.50', (string) $d );
	}

	public function test_construct_from_integer(): void {
		$d = new WooScrapeDecimal( 7 );
		$this->assertSame( '7.00', (string) $d );
	}

	public function test_construct_from_float(): void {
		$d = new WooScrapeDecimal( 3.1 );
		$this->assertSame( '3.10', (string) $d );
	}

	public function test_construct_from_another_decimal(): void {
		$a = new WooScrapeDecimal( '9.99' );
		$b = new WooScrapeDecimal( $a );
		$this->assertSame( '9.99', (string) $b );
	}

	public function test_construct_trims_whitespace(): void {
		$d = new WooScrapeDecimal( '  5.00  ' );
		$this->assertSame( '5.00', (string) $d );
	}

	public function test_construct_negative(): void {
		$d = new WooScrapeDecimal( '-4.25' );
		$this->assertSame( '-4.25', (string) $d );
	}

	public function test_construct_zero(): void {
		$d = new WooScrapeDecimal( 0 );
		$this->assertSame( '0.00', (string) $d );
	}

	public function test_construct_large_number(): void {
		$d = new WooScrapeDecimal( '99999.99' );
		$this->assertSame( '99999.99', (string) $d );
	}

	public function test_construct_throws_on_invalid_string(): void {
		$this->expectException( InvalidArgumentException::class );
		new WooScrapeDecimal( 'abc' );
	}

	public function test_construct_throws_on_empty_string(): void {
		$this->expectException( InvalidArgumentException::class );
		new WooScrapeDecimal( '' );
	}

	// ── Truncation (not rounding) ──────────────────────────────────────────

	public function test_construct_truncates_to_two_decimals(): void {
		$d = new WooScrapeDecimal( '1.999' );
		$this->assertSame( '1.99', (string) $d );
	}

	// ── equals() ───────────────────────────────────────────────────────────

	public function test_equals_same_value(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertTrue( $d->equals( '10.00' ) );
	}

	public function test_equals_different_value(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertFalse( $d->equals( '10.01' ) );
	}

	public function test_equals_with_int(): void {
		$d = new WooScrapeDecimal( '5.00' );
		$this->assertTrue( $d->equals( 5 ) );
	}

	public function test_equals_with_float(): void {
		$d = new WooScrapeDecimal( '5.50' );
		$this->assertTrue( $d->equals( 5.5 ) );
	}

	public function test_equals_with_decimal_instance(): void {
		$a = new WooScrapeDecimal( '7.77' );
		$b = new WooScrapeDecimal( '7.77' );
		$this->assertTrue( $a->equals( $b ) );
	}

	// ── greater_than() ─────────────────────────────────────────────────────

	public function test_greater_than_true(): void {
		$d = new WooScrapeDecimal( '10.01' );
		$this->assertTrue( $d->greater_than( '10.00' ) );
	}

	public function test_greater_than_false_when_equal(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertFalse( $d->greater_than( '10.00' ) );
	}

	public function test_greater_than_false_when_less(): void {
		$d = new WooScrapeDecimal( '9.99' );
		$this->assertFalse( $d->greater_than( '10.00' ) );
	}

	// ── lower_than() ───────────────────────────────────────────────────────

	public function test_lower_than_true(): void {
		$d = new WooScrapeDecimal( '9.99' );
		$this->assertTrue( $d->lower_than( '10.00' ) );
	}

	public function test_lower_than_false_when_equal(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertFalse( $d->lower_than( '10.00' ) );
	}

	public function test_lower_than_false_when_greater(): void {
		$d = new WooScrapeDecimal( '10.01' );
		$this->assertFalse( $d->lower_than( '10.00' ) );
	}

	// ── greater_than_or_equal() ────────────────────────────────────────────

	public function test_greater_than_or_equal_when_greater(): void {
		$d = new WooScrapeDecimal( '10.01' );
		$this->assertTrue( $d->greater_than_or_equal( '10.00' ) );
	}

	public function test_greater_than_or_equal_when_equal(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertTrue( $d->greater_than_or_equal( '10.00' ) );
	}

	public function test_greater_than_or_equal_when_less(): void {
		$d = new WooScrapeDecimal( '9.99' );
		$this->assertFalse( $d->greater_than_or_equal( '10.00' ) );
	}

	// ── lower_than_or_equal() ──────────────────────────────────────────────

	public function test_lower_than_or_equal_when_less(): void {
		$d = new WooScrapeDecimal( '9.99' );
		$this->assertTrue( $d->lower_than_or_equal( '10.00' ) );
	}

	public function test_lower_than_or_equal_when_equal(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->assertTrue( $d->lower_than_or_equal( '10.00' ) );
	}

	public function test_lower_than_or_equal_when_greater(): void {
		$d = new WooScrapeDecimal( '10.01' );
		$this->assertFalse( $d->lower_than_or_equal( '10.00' ) );
	}

	// ── add() ──────────────────────────────────────────────────────────────

	public function test_add_basic(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$result = $d->add( '5.50' );
		$this->assertSame( '15.50', (string) $result );
		$this->assertSame( $d, $result, 'add() should return the same instance' );
	}

	public function test_add_with_int(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$d->add( 3 );
		$this->assertSame( '13.00', (string) $d );
	}

	public function test_add_with_decimal_instance(): void {
		$a = new WooScrapeDecimal( '10.00' );
		$b = new WooScrapeDecimal( '2.50' );
		$a->add( $b );
		$this->assertSame( '12.50', (string) $a );
	}

	public function test_add_negative(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$d->add( '-3.00' );
		$this->assertSame( '7.00', (string) $d );
	}

	// ── subtract() ─────────────────────────────────────────────────────────

	public function test_subtract_basic(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$result = $d->subtract( '3.50' );
		$this->assertSame( '6.50', (string) $result );
		$this->assertSame( $d, $result, 'subtract() should return the same instance' );
	}

	public function test_subtract_to_negative(): void {
		$d = new WooScrapeDecimal( '5.00' );
		$d->subtract( '10.00' );
		$this->assertSame( '-5.00', (string) $d );
	}

	// ── multiply() ─────────────────────────────────────────────────────────

	public function test_multiply_basic(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$result = $d->multiply( '2.5' );
		$this->assertSame( '25.00', (string) $result );
		$this->assertSame( $d, $result, 'multiply() should return the same instance' );
	}

	public function test_multiply_by_zero(): void {
		$d = new WooScrapeDecimal( '99.99' );
		$d->multiply( 0 );
		$this->assertSame( '0.00', (string) $d );
	}

	public function test_multiply_truncation(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$d->multiply( '0.33' );
		// bcmul truncates: 10.00 * 0.33 = 3.30 (2 scale)
		$this->assertSame( '3.30', (string) $d );
	}

	// ── divide() ───────────────────────────────────────────────────────────

	public function test_divide_basic(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$result = $d->divide( '4' );
		$this->assertSame( '2.50', (string) $result );
		$this->assertSame( $d, $result, 'divide() should return the same instance' );
	}

	public function test_divide_truncation(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$d->divide( '3' );
		// bcdiv truncates: 10/3 = 3.33
		$this->assertSame( '3.33', (string) $d );
	}

	// ── clone() ────────────────────────────────────────────────────────────

	public function test_clone_returns_new_instance(): void {
		$a = new WooScrapeDecimal( '42.00' );
		$b = $a->clone();
		$this->assertSame( '42.00', (string) $b );
		$this->assertNotSame( $a, $b );
	}

	public function test_clone_is_independent(): void {
		$a = new WooScrapeDecimal( '42.00' );
		$b = $a->clone();
		$b->add( '1.00' );
		$this->assertSame( '42.00', (string) $a );
		$this->assertSame( '43.00', (string) $b );
	}

	// ── __toString() ───────────────────────────────────────────────────────

	public function test_toString(): void {
		$d = new WooScrapeDecimal( '123.45' );
		$this->assertSame( '123.45', $d->__toString() );
	}

	// ── Chaining ───────────────────────────────────────────────────────────

	public function test_chained_operations(): void {
		$d = new WooScrapeDecimal( '100.00' );
		$result = $d->add( '50.00' )->subtract( '20.00' )->multiply( '2' )->divide( '5' );
		// (100 + 50 - 20) * 2 / 5 = 130 * 2 / 5 = 260 / 5 = 52
		$this->assertSame( '52.00', (string) $result );
	}

	// ── convert_and_check validation for comparison methods ────────────────

	public function test_equals_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->equals( 'invalid' );
	}

	public function test_add_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->add( 'invalid' );
	}

	public function test_subtract_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->subtract( 'invalid' );
	}

	public function test_multiply_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->multiply( 'invalid' );
	}

	public function test_divide_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->divide( 'invalid' );
	}

	public function test_greater_than_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->greater_than( 'not_a_number' );
	}

	public function test_lower_than_throws_on_invalid(): void {
		$d = new WooScrapeDecimal( '10.00' );
		$this->expectException( InvalidArgumentException::class );
		$d->lower_than( 'not_a_number' );
	}
}
