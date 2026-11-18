<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Product
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Product extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return ?\Opencart\System\Engine\Action
	 */
	public function index(): ?\Opencart\System\Engine\Action {
		$this->load->language('product/product');

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$recent_products = $this->session->data['nexora_recent_products'] ?? [];
			$recent_products = array_values(array_unique(array_merge([$product_id], array_map('intval', (array)$recent_products))));
			$this->session->data['nexora_recent_products'] = array_slice($recent_products, 0, 8);

			$this->document->setTitle($product_info['meta_title']);
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id), 'canonical');

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
			];

			// Category
			$this->load->model('catalog/category');

			if (isset($this->request->get['path'])) {
				$path = '';

				$parts = explode('_', (string)$this->request->get['path']);

				$category_id = (int)array_pop($parts);

				foreach ($parts as $path_id) {
					if (!$path) {
						$path = $path_id;
					} else {
						$path .= '_' . $path_id;
					}

					$category_info = $this->model_catalog_category->getCategory((int)$path_id);

					if ($category_info) {
						$data['breadcrumbs'][] = [
							'text' => $category_info['name'],
							'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $path)
						];
					}
				}

				// Set the last category breadcrumb
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
					$url = '';

					if (isset($this->request->get['sort'])) {
						$url .= '&sort=' . $this->request->get['sort'];
					}

					if (isset($this->request->get['order'])) {
						$url .= '&order=' . $this->request->get['order'];
					}

					if (isset($this->request->get['page'])) {
						$url .= '&page=' . $this->request->get['page'];
					}

					if (isset($this->request->get['limit'])) {
						$url .= '&limit=' . $this->request->get['limit'];
					}

					$data['breadcrumbs'][] = [
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path'] . $url)
					];
				}
			}

			// Manufacturer
			$this->load->model('catalog/manufacturer');

			if (isset($this->request->get['manufacturer_id'])) {
				$data['breadcrumbs'][] = [
					'text' => $this->language->get('text_brand'),
					'href' => $this->url->link('product/manufacturer', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
				];

				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);

				if ($manufacturer_info) {
					$data['breadcrumbs'][] = [
						'text' => $manufacturer_info['name'],
						'href' => $this->url->link('product/manufacturer.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&manufacturer_id=' . $this->request->get['manufacturer_id'] . $url)
					];
				}
			}

			if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
				$url = '';

				if (isset($this->request->get['search'])) {
					$url .= '&search=' . $this->request->get['search'];
				}

				if (isset($this->request->get['tag'])) {
					$url .= '&tag=' . $this->request->get['tag'];
				}

				if (isset($this->request->get['description'])) {
					$url .= '&description=' . $this->request->get['description'];
				}

				if (isset($this->request->get['category_id'])) {
					$url .= '&category_id=' . $this->request->get['category_id'];
				}

				if (isset($this->request->get['sub_category'])) {
					$url .= '&sub_category=' . $this->request->get['sub_category'];
				}

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['page'])) {
					$url .= '&page=' . $this->request->get['page'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$data['breadcrumbs'][] = [
					'text' => $this->language->get('text_search'),
					'href' => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url)
				];
			}

			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['manufacturer_id'])) {
				$url .= '&manufacturer_id=' . $this->request->get['manufacturer_id'];
			}

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . $this->request->get['search'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . $this->request->get['tag'];
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = [
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url . '&product_id=' . $product_id)
			];

			$this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');

			$data['heading_title'] = $product_info['name'];

			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_in_stock'] = $this->language->get('text_in_stock');
			$data['text_out_of_stock'] = $this->language->get('text_out_of_stock');
			$data['text_cart_added'] = $this->language->get('text_cart_added');
			$data['button_buy_now'] = $this->language->get('button_buy_now');
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))), $this->url->link('account/register', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
			$data['text_reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['product_review_count'] = (int)$product_info['reviews'];

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

			$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

			$this->session->data['upload_token'] = oc_token(32);

			$data['upload'] = $this->url->link('tool/upload', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&upload_token=' . $this->session->data['upload_token']);

			$data['product_id'] = $product_id;

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			} else {
				$data['manufacturer'] = '';
			}

			$data['manufacturers'] = $this->url->link('product/manufacturer.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];

			$data['product_codes'] = [];

			$results = $this->model_catalog_product->getCodes($product_id);

			foreach ($results as $result) {
				if ($result['status']) {
					$data['product_codes'][] = $result;
				}
			}

			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
			if (($this->request->get['language'] ?? $this->config->get('config_language')) === 'en-gb') {
				$product_descriptions_english = require DIR_APPLICATION . 'data/product_descriptions_en_gb.php';
				$data['description'] = $product_descriptions_english[$product_info['model']] ?? str_replace(
					'Sản phẩm theo tên và cấu hình đã cung cấp. Liên hệ PanDaShop để được tư vấn trước khi đặt hàng.',
					'Product details are based on the supplied name and configuration. Contact PanDaShop for assistance before ordering.',
					$data['description']
				);
			}

			// Stock Status
			if ($product_info['quantity'] <= 0 || $this->hasRequiredOptionsWithoutStock($product_info)) {
				$stock_status_id = $product_info['stock_status_id'];
			} elseif (!$this->config->get('config_stock_display')) {
				$stock_status_id = (int)$this->config->get('config_stock_status_id');
			} else {
				$stock_status_id = 0;
			}

			// Stock Status
			$this->load->model('localisation/stock_status');

			$stock_status_info = $this->model_localisation_stock_status->getStockStatus($stock_status_id);

			if ($stock_status_info) {
				$data['stock'] = $stock_status_info['name'];
			} else {
				$data['stock'] = $product_info['quantity'];
			}

			$data['stock_available'] = ($product_info['quantity'] > 0 && !$this->hasRequiredOptionsWithoutStock($product_info));
			$data['stock_quantity'] = (int)$product_info['quantity'];
			$data['stock_alert_needed'] = (int)$product_info['quantity'] <= 0;
			$data['stock_alert_logged'] = $this->customer->isLogged();
			$data['stock_alert_action'] = $this->url->link('product/stock.subscribe', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			if (empty($this->session->data['stock_alert_token'])) {
				$this->session->data['stock_alert_token'] = oc_token(32);
			}

			$data['stock_alert_token'] = $this->session->data['stock_alert_token'];
			$data['stock_alert_message'] = $this->session->data['stock_alert_message'] ?? '';
			$data['stock_alert_error'] = $this->session->data['stock_alert_error'] ?? '';
			unset($this->session->data['stock_alert_message'], $this->session->data['stock_alert_error']);

			$data['rating'] = $product_info['rating'];
			$data['review_status'] = (int)$this->config->get('config_review_status');
			$data['review'] = $this->load->controller('product/review');

			$data['wishlist_add'] = $this->url->link('account/wishlist.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$data['compare_add'] = $this->url->link('product/compare.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$data['cart_info'] = $this->url->link('common/cart.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			// Image
			$this->load->model('tool/image');

			if ($product_info['image'] && is_file(DIR_IMAGE . html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
				$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_thumb_width'), $this->config->get('config_image_thumb_height'));
			} else {
				$data['popup'] = '';
				$data['thumb'] = '';
			}

			$data['images'] = [];

			$results = $this->model_catalog_product->getImages($product_id);

			foreach ($results as $result) {
				if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
					$data['images'][] = [
						'popup' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height')),
						'thumb' => $this->model_tool_image->resize($result['image'], $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'))
					];
				}
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			if ((float)$product_info['special']) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['special'] = false;
			}

			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			$discounts = $this->model_catalog_product->getDiscounts($product_id);

			$data['discounts'] = [];

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				foreach ($discounts as $discount) {
					$data['discounts'][] = ['price' => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])] + $discount;
				}
			}

			$data['options'] = [];

			// Check if product is variant
			if ($product_info['master_id']) {
				$master_id = (int)$product_info['master_id'];
			} else {
				$master_id = (int)$product_id;
			}

			$product_options = $this->model_catalog_product->getOptions($master_id);

			foreach ($product_options as $option) {
				if ($product_id && !isset($product_info['override']['variant'][$option['product_option_id']])) {
					$product_option_value_data = [];

					foreach ($option['product_option_value'] as $option_value) {
						if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
							if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
								$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
							} else {
								$price = false;
							}

							if ($option_value['image'] && is_file(DIR_IMAGE . html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'))) {
								$image = $option_value['image'];
							} else {
								$image = '';
							}

							$product_option_value_data[] = [
								'image' => $this->model_tool_image->resize($image, 50, 50),
								'price' => $price
							] + $option_value;
						}
					}

					$data['options'][] = ['product_option_value' => $product_option_value_data] + $option;
				}
			}

			// Subscriptions
			$data['subscription_plans'] = [];

			$results = $this->model_catalog_product->getSubscriptions($product_id);

			foreach ($results as $result) {
				$description = '';

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					if ($result['duration']) {
						$price = ($product_info['special'] ?: $product_info['price']) / $result['duration'];
					} else {
						$price = ($product_info['special'] ?: $product_info['price']);
					}

					$price = $this->currency->format($this->tax->calculate($price, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$cycle = $result['cycle'];
					$frequency = $this->language->get('text_' . $result['frequency']);
					$duration = $result['duration'];

					if ($duration) {
						$description = sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
					} else {
						$description = sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
					}
				}

				$data['subscription_plans'][] = ['description' => $description] + $result;
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['share'] = $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id);

			$data['attribute_groups'] = $this->model_catalog_product->getAttributes($product_id);

			$product_details_catalog = require DIR_APPLICATION . 'data/product_details.php';
			$product_detail_model_aliases = [
				'DJI-MINI4PRO-FMC' => 'FLY-DJI-01',
				'FLY-DJI-02'       => 'DJI-AIR3S',
				'DJI-NEO-FMC'      => 'DJI-NEO',
				'CAM-DJI-01'       => 'DJI-POCKET3'
			];
			$product_detail_model = $product_detail_model_aliases[$product_info['model']] ?? $product_info['model'];
			$product_details = $product_details_catalog[$product_detail_model] ?? [];
			if (($this->request->get['language'] ?? $this->config->get('config_language')) === 'en-gb') {
				$product_details_english = require DIR_APPLICATION . 'data/product_details_en_gb.php';
				$product_details = array_replace_recursive($product_details, $product_details_english[$product_detail_model] ?? []);
			}
			$data['product_detail_specs'] = $product_details['specs'] ?? [];
			$data['product_detail_source'] = $product_details['source'] ?? '';
			$data['product_in_box'] = $product_details['in_box'] ?? [];
			$data['product_detail_highlights'] = $product_details['highlights'] ?? [];
			$data['product_detail_video'] = $product_details['video'] ?? '';
			$data['product_detail_video_title'] = $product_details['video_title'] ?? $product_info['name'];
			$data['product_detail_sections'] = $product_details['sections'] ?? [];
			$data['compatible_accessories'] = [];

			foreach ($product_details['compatible_accessories'] ?? [] as $accessory_model) {
				$accessory = $this->model_catalog_product->getProductByModel((string)$accessory_model);

				if (!$accessory || (int)$accessory['quantity'] <= 0) {
					continue;
				}

				if ($accessory['image'] && is_file(DIR_IMAGE . html_entity_decode($accessory['image'], ENT_QUOTES, 'UTF-8'))) {
					$accessory_image = $accessory['image'];
				} else {
					$accessory_image = 'placeholder.png';
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$accessory_price = $this->currency->format($this->tax->calculate($accessory['price'], $accessory['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$accessory_price = false;
				}

				$accessory_special = (float)$accessory['special']
					? $this->currency->format($this->tax->calculate($accessory['special'], $accessory['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
					: false;

				$data['compatible_accessories'][] = [
					'product_id' => (int)$accessory['product_id'],
					'name' => $accessory['name'],
					'model' => $accessory['model'],
					'thumb' => $this->model_tool_image->resize($accessory_image, 120, 120),
					'price' => $accessory_price,
					'special' => $accessory_special,
					'href' => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . (int)$accessory['product_id'])
				];
			}

			if (!$data['product_detail_highlights']) {
				foreach ($data['product_detail_specs'] as $spec_group) {
					foreach ($spec_group['attributes'] as $attribute) {
						$data['product_detail_highlights'][] = $attribute['name'] . ': ' . $attribute['text'];

						if (count($data['product_detail_highlights']) >= 3) {
							break 2;
						}
					}
				}
			}

			if (!$data['product_detail_highlights']) {
				foreach ($data['attribute_groups'] as $attribute_group) {
					foreach ($attribute_group['attribute'] as $attribute) {
						$data['product_detail_highlights'][] = $attribute['name'] . ': ' . $attribute['text'];

						if (count($data['product_detail_highlights']) >= 3) {
							break 2;
						}
					}
				}
			}

			$data['related'] = $this->load->controller('product/related');
			$data['has_related_products'] = trim($data['related']) !== '';

			$data['tags'] = [];

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = [
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&tag=' . trim($tag))
					];
				}
			}

			if ($this->config->get('config_product_report_status')) {
				$this->model_catalog_product->addReport($product_id, oc_get_ip());
			}

			$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));
			$this->load->language('common/footer');
			foreach (['text_delivery_title', 'text_delivery_fee', 'text_delivery_free', 'text_delivery_eta', 'text_returns_title', 'text_returns_summary', 'text_warranty_title', 'text_warranty_summary', 'text_policy_details', 'text_compatible_accessories', 'text_compatibility_note', 'text_add_accessory', 'text_reviews_first', 'text_review_prompt', 'text_box_contents_list', 'text_no_reviews_yet'] as $key) {
				$data[$key] = $this->language->get($key);
			}
			$data['delivery_policy_url'] = $this->url->link('information/information', 'language=' . $data['language'] . '&information_id=4');
			$data['returns_policy_url'] = $this->url->link('information/information', 'language=' . $data['language'] . '&information_id=5');
			$data['warranty_policy_url'] = $this->url->link('information/information', 'language=' . $data['language'] . '&information_id=6');
			foreach (['text_footer_support', 'text_footer_contact', 'text_footer_hours', 'text_footer_hours_value', 'text_contact'] as $key) {
				$data[$key] = $this->language->get($key);
			}
			$data['contact_telephone'] = trim((string)$this->config->get('config_telephone'));
			$data['contact_telephone_uri'] = preg_replace('/[^0-9+]/', '', $data['contact_telephone']);
			$data['contact_email'] = trim((string)$this->config->get('config_email'));
			$data['contact_url'] = $this->url->link('information/contact', 'language=' . $data['language']);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['recently_viewed'] = $this->load->controller('extension/pandashop/recently_viewed', $product_id);
			$data['product_questions'] = $this->load->controller('extension/pandashop/questions', $product_id);
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		return null;
	}

	/**
	 * Check to make sure that none of the required product options is without stock
	 *
	 * @param array<string, mixed> $product_info
	 *
	 * @return bool
	 */
	protected function hasRequiredOptionsWithoutStock(array $product_info): bool {
		if ($product_info['master_id']) {
			$master_id = (int)$product_info['master_id'];
		} else {
			$master_id = (int)$product_info['product_id'];
		}

		$product_options = $this->model_catalog_product->getOptions($master_id);

		foreach ($product_options as $product_option) {
			if (!$product_option['required']) {
				continue;
			}
			$product_option_id = $product_option['product_option_id'];

			$has_stock = false;

			$type = $product_option['type'];
			if ($type == 'select' || $type == 'radio' || $type == 'checkbox') {
				foreach ($product_option['product_option_value'] as $product_option_value) {
					$product_option_value_id = $product_option_value['product_option_value_id'];
					if (!empty($product_info['override']['variant'][$product_option_id])) {
						if (!isset($product_info['variant'][$product_option_id])) {
							// option value is not used in variant product
							continue;
						}
						$value = $product_info['variant'][$product_option_id];
						if (!is_array($value)) {
							$value = [$value];
						}
						if (!in_array($product_option_value_id, $value)) {
							// option value is not used in variant product
							continue;
						}
					}
					if (!$product_option_value['subtract']) {
						// at least one required product option value can still be chosen
						$has_stock = true;
						break;
					}
					if ($product_option_value['quantity'] > 0) {
						// at least one required product option value still has some stock
						$has_stock = true;
						break;
					}
				}
				if (!$has_stock) {
					return true;
				}
			} else {
				$has_stock = true;
			}

			if (!$has_stock) {
				return true;
			}
		}

		return false;
	}
}
