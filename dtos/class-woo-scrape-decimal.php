<?php

class WooScrapeDecimal {

	//TODO: private
	public string $value;

	/**
	 * @throws InvalidArgumentException when the given value is not a valid number
	 */
	public function __construct( string|int|float|WooScrapeDecimal $value ) {

		$value = $this->convert_and_check( $value );

		// convert to standard notation
		$this->value = bcadd( trim($value), '0', 2 );
	}

	/**
	 * Checks if this istance is equal to the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return bool
	 */
	public function equals( string|int|float|WooScrapeDecimal $value ): bool {

		$value = $this->convert_and_check( $value );

		return bccomp( $this->value, $value, 2 ) === 0;
	}

	/**
	 * Checks if this istance is greater than the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return bool
	 */
	public function greater_than( string|int|float|WooScrapeDecimal $value ): bool {

		$value = $this->convert_and_check( $value );

		return bccomp( $this->value, $value, 2 ) === 1;
	}

	/**
	 * Checks if this istance is lower than the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return bool
	 */
	public function lower_than( string|int|float|WooScrapeDecimal $value ): bool {

		$value = $this->convert_and_check( $value );

		return bccomp( $this->value, $value, 2 ) === - 1;
	}

	/**
	 * Checks if this istance is greater than or equal the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return bool
	 */
	public function greater_than_or_equal( string|int|float|WooScrapeDecimal $value ): bool {

		$value = $this->convert_and_check( $value );

		return bccomp( $this->value, $value, 2 ) >= 0;
	}

	/**
	 * Checks if this istance is lower than or equal  the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return bool
	 */
	public function lower_than_or_equal( string|int|float|WooScrapeDecimal $value ): bool {

		$value = $this->convert_and_check( $value );

		return bccomp( $this->value, $value, 2 ) <= 0;
	}

	/**
	 * Adds the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return WooScrapeDecimal the same Decimal instance with updated value
	 */
	public function add( string|int|float|WooScrapeDecimal $value ): WooScrapeDecimal {
		$value       = $this->convert_and_check( $value );
		$this->value = bcadd( $this->value, $value, 2 );
		return $this;
	}

	/**
	 * Subtracts the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return WooScrapeDecimal the same Decimal instance with updated value
	 */
	public function subtract( string|int|float|WooScrapeDecimal $value ): WooScrapeDecimal {
		$value       = $this->convert_and_check( $value );
		$this->value = bcsub( $this->value, $value, 2 );
		return $this;
	}

	/**
	 * Multiplies for the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return WooScrapeDecimal the same Decimal instance with updated value
	 */
	public function multiply( string|int|float|WooScrapeDecimal $value ): WooScrapeDecimal {
		$value       = $this->convert_and_check( $value );
		$this->value = bcmul( $this->value, $value, 2 );
		return $this;
	}

	/**
	 * Divides for the provided decimal
	 *
	 * @param string|int|float|WooScrapeDecimal $value
	 *
	 * @return WooScrapeDecimal the same Decimal instance with updated value
	 */
	public function divide( string|int|float|WooScrapeDecimal $value ): WooScrapeDecimal {
		$value       = $this->convert_and_check( $value );
		$this->value = bcdiv( $this->value, $value, 2 );
		return $this;
	}

	public function clone(): WooScrapeDecimal {
		return new WooScrapeDecimal( $this->value );
	}

	public function __toString(): string {
		return $this->value;
	}

	private function convert_and_check( string|int|float|WooScrapeDecimal $value ) {
		// if it's decimal instance, unpack it.
		if ( $value instanceof WooScrapeDecimal ) {
			$value = $value->__toString();
		}

		if ( ! is_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Invalid numeric value: ' . $value );
		}

		return (string) $value;
	}
}
