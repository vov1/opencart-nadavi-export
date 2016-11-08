<?php

class ControllerFeedNadavi extends Controller {

    public function index() {

        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/manufacturer');
        $this->load->model('feed/nadavi');
        $this->load->model('tool/image');

        $this->language->load('feed/nadavi');

        if ( $this->config->get('nadavi_status') ) {

            $output = $this->cache->get('feed_nadavi.'.(int)$this->config->get('config_store_id'));

            if ( ! $output ) {
                $output  = '<?xml version="1.0" encoding="UTF-8" ?>';
                $output .= '<yml_catalog date="' . date('Y-m-d H:i', time()) . '">';
                $output .= '<shop>';

                $output .= '<name>' . $this->config->get('config_name') . '</name>';
                $output .= '<url>' . parse_url( $this->config->get('config_url'), PHP_URL_HOST) . '</url>';

                // Currencies
                $output .= '<currencies>';
			        $output .= '<currency id="' . $this->currency->getCode() . '" rate="1"/>';
		        $output .= '</currencies>';

                // Categories
                $categories = $this->model_feed_nadavi->getCategories();

                if ($categories) {
                    $output .= '<catalog>';
                    foreach ($categories as $category) {
                        $output .= '<category id="' . $category['category_id'] . '"';
                        if ($category['parent_id']) {
                            $output .= ' parentId="' . $category['parent_id'] . '"';
                        }
                        $output .= '>' . $category['name'] . '</category>';
                    }
                    $output .= '</catalog>';
                }

                // Products
                $products = $this->model_catalog_product->getProducts(array('start' => 0, 'limit' => 1000000));

                if ($products) {

                    $output .= '<items>';
                    foreach ($products as $product) {
                        if ($product['quantity'] < 1) continue;

                        $output .= '<item id="' . $product['product_id'] . '">';

                        // Get Product Category
                        $category_id = false;
                        $product_categories = $this->model_catalog_product->getCategories($product['product_id']);

                        foreach ($product_categories as $product_category) {
                            $category_id = $product_category['category_id'];

                            // SEO PRO Main Category Support
                            if (isset($product_category['main_category_id']) && $product_category['main_category_id'] == 1) {
                                break;
                            }
                        }

                        $output .= '<name>' . $product['name'] . '</name>';

                        $output .= '<url>' . $this->url->link('product/product', 'product_id=' . (int) $product['product_id'], 'SSL') . '</url>';

                        $output .= '<price>' . number_format($product['price'], 2, '.', '') . '</price>';

                        if ($category_id) {
                            $output .= '<categoryId>' . $category_id . '</categoryId>';
                        }

                        if ($product['manufacturer']) {
                            $output .= '<vendor>' . $product['manufacturer'] . '</vendor>';
                        }

                        if ($product['image']) {
                            $output .= '<image>' . htmlspecialchars( $this->model_tool_image->resize($product['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height')) ) . '</image>';
                        }

                        if ($product['description']) {
                            $output .= '<description>' . trim(str_replace(array('&lt;p&gt;', '&lt;/p&gt;'), '', strip_tags($product['description']))) . '</description>';
                        }

                        $output .= '</item>';
                    }
                    $output .= '</items>';
                }

                $output .= '</shop>';
                $output .= '</yml_catalog>';

                $this->cache->set('feed_nadavi.'.(int)$this->config->get('config_store_id'), $output);
            }

            $this->response->addHeader('Content-Type: application/xml');
            $this->response->setOutput($output);
        }
    }
}
