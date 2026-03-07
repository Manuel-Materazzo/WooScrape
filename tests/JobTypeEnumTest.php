<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass( JobType::class )]
class JobTypeEnumTest extends TestCase {

	public function test_all_cases_exist(): void {
		$expected = array(
			'Categories_crawl',
			'Products_crawl',
			'Images_crawl',
			'Woocommerce_out_of_stock',
			'Woocommerce_update',
			'Woocommerce_create',
			'Names_translation',
			'Descriptions_translation',
			'Specifications_translation',
		);

		$cases = array_map( fn( JobType $c ) => $c->value, JobType::cases() );

		$this->assertSame( $expected, $cases );
	}

	public function test_case_values(): void {
		$this->assertSame( 'Categories_crawl', JobType::Categories_crawl->value );
		$this->assertSame( 'Products_crawl', JobType::Products_crawl->value );
		$this->assertSame( 'Images_crawl', JobType::Images_crawl->value );
		$this->assertSame( 'Woocommerce_out_of_stock', JobType::Woocommerce_out_of_stock->value );
		$this->assertSame( 'Woocommerce_update', JobType::Woocommerce_update->value );
		$this->assertSame( 'Woocommerce_create', JobType::Woocommerce_create->value );
		$this->assertSame( 'Names_translation', JobType::Names_translation->value );
		$this->assertSame( 'Descriptions_translation', JobType::Descriptions_translation->value );
		$this->assertSame( 'Specifications_translation', JobType::Specifications_translation->value );
	}

	public function test_from_valid_string(): void {
		$this->assertSame( JobType::Categories_crawl, JobType::from( 'Categories_crawl' ) );
		$this->assertSame( JobType::Woocommerce_update, JobType::from( 'Woocommerce_update' ) );
	}

	public function test_from_invalid_string_throws(): void {
		$this->expectException( ValueError::class );
		JobType::from( 'invalid_type' );
	}

	public function test_tryFrom_returns_null_for_invalid(): void {
		$this->assertNull( JobType::tryFrom( 'nonexistent' ) );
	}

	public function test_total_case_count(): void {
		$this->assertCount( 9, JobType::cases() );
	}
}
