<?php

enum JobType: string {
	case Categories_crawl = 'Categories_crawl';
	case Products_crawl = 'Products_crawl';
	case Images_crawl = 'Images_crawl';
	case Woocommerce_out_of_stock = 'Woocommerce_out_of_stock';
	case Woocommerce_update = 'Woocommerce_update';
	case Woocommerce_create = 'Woocommerce_create';
	case Names_translation = 'Names_translation';
	case Descriptions_translation = 'Descriptions_translation';
	case Specifications_translation = 'Specifications_translation';
}
