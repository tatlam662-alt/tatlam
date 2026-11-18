<?php
namespace Opencart\Catalog\Controller\Checkout;
/**
 * Class Register
 *
 * Can be loaded using $this->load->controller('checkout/register');
 *
 * @package Opencart\Catalog\Controller\Checkout
 */
class Register extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('checkout/register');

		$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&redirect=' . urlencode($this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true))));

		$data['entry_newsletter'] = sprintf($this->language->get('entry_newsletter'), $this->config->get('config_name'));

		$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

		$data['config_checkout_payment_address'] = $this->config->get('config_checkout_payment_address');
		$data['config_checkout_guest'] = ($this->config->get('config_checkout_guest') && !$this->config->get('config_customer_price') && !$this->cart->hasDownload() && !$this->cart->hasSubscription());
		$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);
		// Checkout always collects a Vietnamese contact number, independent of
		// the optional telephone setting used by account registration.
		$data['config_telephone_display'] = true;
		$data['config_telephone_required'] = true;

		$data['shipping_required'] = $this->cart->hasShipping();

		$this->session->data['upload_token'] = oc_token(32);

		$data['upload'] = $this->url->link('tool/upload', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&upload_token=' . $this->session->data['upload_token']);

		// Customer Groups
		$data['customer_groups'] = [];

		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], (array)$this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		if (isset($this->session->data['customer']['customer_id'])) {
			$data['account'] = $this->session->data['customer']['customer_id'];
		} else {
			$data['account'] = $data['config_checkout_guest'] ? 0 : 1;
		}

		if (isset($this->session->data['customer'])) {
			$data['customer_group_id'] = $this->session->data['customer']['customer_group_id'];
			$data['firstname'] = $this->session->data['customer']['firstname'];
			$data['lastname'] = $this->session->data['customer']['lastname'];
			$data['email'] = $this->session->data['customer']['email'];
			$data['telephone'] = $this->session->data['customer']['telephone'];
			$data['address_match'] = (int)$this->session->data['customer']['address_match'];
			$data['account_custom_field'] = $this->session->data['customer']['custom_field'];
		} else {
			$data['customer_group_id'] = (int)$this->config->get('config_customer_group_id');
			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['address_match'] = 1;
			$data['account_custom_field'] = [];
		}

		if (isset($this->session->data['payment_address'])) {
			$data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
			$data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
			$data['payment_company'] = $this->session->data['payment_address']['company'];
			$data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
			$data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
			$data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
			$data['payment_city'] = $this->session->data['payment_address']['city'];
			$data['payment_country_id'] = 230;
			$data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
			$data['payment_custom_field'] = $this->session->data['payment_address']['custom_field'];
		} else {
			$data['payment_firstname'] = '';
			$data['payment_lastname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_address_2'] = '';
			$data['payment_postcode'] = '';
			$data['payment_city'] = '';
			$data['payment_country_id'] = 230;
			$data['payment_zone_id'] = 0;
			$data['payment_custom_field'] = [];
		}

		// Countries
		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Postcode
		$payment_country = $this->model_localisation_country->getCountry($data['payment_country_id']);
		$data['payment_postcode_required'] = $payment_country['postcode_required'] ?? false;

		// Zones
		$this->load->model('localisation/zone');

		$data['payment_zones'] = $this->model_localisation_zone->getZonesByCountryId($data['payment_country_id']);

		if (isset($this->session->data['shipping_address']['address_id'])) {
			$data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
			$data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
			$data['shipping_company'] = $this->session->data['shipping_address']['company'];
			$data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
			$data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
			$data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
			$data['shipping_city'] = $this->session->data['shipping_address']['city'];
			$data['shipping_country_id'] = 230;
			$data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
			$data['shipping_custom_field'] = $this->session->data['shipping_address']['custom_field'];
		} else {
			$data['shipping_firstname'] = '';
			$data['shipping_lastname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_address_2'] = '';

			if (isset($this->session->data['shipping_address']['postcode'])) {
				$data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
			} else {
				$data['shipping_postcode'] = '';
			}

			$data['shipping_city'] = '';

			if (isset($this->session->data['shipping_address']['country_id'])) {
				$data['shipping_country_id'] = 230;
			} else {
				$data['shipping_country_id'] = 230;
			}

			if (isset($this->session->data['shipping_address']['zone_id'])) {
				$data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
			} else {
				$data['shipping_zone_id'] = 0;
			}

			$data['shipping_custom_field'] = [];
		}

		// Remove the stray placeholder value that was previously saved as an
		// address; customers must enter their actual delivery address.
		foreach (['payment_address_1', 'shipping_address_1'] as $addressKey) {
			if (isset($data[$addressKey]) && strtolower(trim((string)$data[$addressKey])) === 'admin') {
				$data[$addressKey] = '';
			}
		}
		$data['payment_ward_code'] = '';
		$data['shipping_ward_code'] = '';
		$payment_saved_selection = $this->getVietnamAddressCodes((int)$data['payment_zone_id'], (string)$data['payment_address_2']);
		$shipping_saved_selection = $this->getVietnamAddressCodes((int)$data['shipping_zone_id'], (string)$data['shipping_address_2']);
		$data['payment_ward_code'] = $payment_saved_selection['ward_code'] ?? '';
		$data['shipping_ward_code'] = $shipping_saved_selection['ward_code'] ?? '';

		// Shipping Zones
		$this->load->model('localisation/zone');

		if ($data['payment_country_id'] == $data['shipping_country_id']) {
			$data['shipping_postcode_required'] = $data['payment_postcode_required'];
			$data['shipping_zones'] = $data['payment_zones'];
		} else {
			$shipping_country = $this->model_localisation_country->getCountry($data['shipping_country_id']);
			$data['shipping_postcode_required'] = $shipping_country['postcode_required'] ?? false;
			$data['shipping_zones'] = $this->model_localisation_zone->getZonesByCountryId($data['shipping_country_id']);
		}

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();

		// Captcha
		$this->load->model('setting/extension');

		$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

		if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
		} else {
			$data['captcha'] = '';
		}

		// Information
		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation((int)$this->config->get('config_account_id'));

		if ($information_info) {
			$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&information_id=' . $this->config->get('config_account_id')), $information_info['title']);
		} else {
			$data['text_agree'] = '';
		}

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		return $this->load->view('checkout/register', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('checkout/register');

		$json = [];

		$required = [
			'account'               => 0,
			'customer_group_id'     => 0,
			'firstname'             => '',
			'lastname'              => '',
			'email'                 => '',
			'telephone'             => '',
			'custom_field'          => [],
			'payment_company'       => '',
			'payment_address_1'     => '',
			'payment_address_2'     => '',
			'payment_hamlet_name'   => '',
			'payment_subhamlet_name' => '',
			'payment_city'          => '',
			'payment_postcode'      => '',
			'payment_country_id'    => 0,
			'payment_zone_id'       => 0,
			'payment_ward_code'     => '',
			'payment_custom_field'  => [],
			'address_match'         => 0,
			'shipping_firstname'    => '',
			'shipping_lastname'     => '',
			'shipping_company'      => '',
			'shipping_address_1'    => '',
			'shipping_address_2'    => '',
			'shipping_hamlet_name'  => '',
			'shipping_subhamlet_name' => '',
			'shipping_city'         => '',
			'shipping_postcode'     => '',
			'shipping_country_id'   => 0,
			'shipping_zone_id'      => 0,
			'shipping_ward_code'    => '',
			'shipping_custom_field' => [],
			'password'              => '',
			'agree'                 => 0,
			'newsletter'            => 0
		];

		$post_info = $this->request->post + $required;
		foreach (['payment_address_1', 'shipping_address_1'] as $address_key) {
			if (is_scalar($post_info[$address_key]) && strtolower(trim((string)$post_info[$address_key])) === 'admin') $post_info[$address_key] = '';
		}

		// This storefront currently accepts domestic delivery addresses only.
		$post_info['payment_country_id'] = 230;
		$post_info['shipping_country_id'] = 230;
		$post_info['payment_postcode'] = '';
		$post_info['shipping_postcode'] = '';
		$post_info['payment_company'] = '';
		$post_info['shipping_company'] = '';
		if (!$this->config->get('config_checkout_payment_address')) {
			// When OpenCart uses only a shipping address, never let a crafted
			// request mark the hidden payment address as the shipping address.
			$post_info['address_match'] = 0;
		}

		// Force account requires subscript or is a downloadable product.
		if ($this->cart->hasDownload() || $this->cart->hasSubscription() || !$this->config->get('config_checkout_guest')) {
			$post_info['account'] = 1;
		}

		// Validate cart has products and has stock.
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$json['redirect'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
		}

		if (!$json) {
			// If not guest checkout disabled, login require price or cart has downloads
			if (!$post_info['account'] && (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price'))) {
				$json['error']['warning'] = $this->language->get('error_guest');
			}

			// Customer Group
			if ($post_info['customer_group_id']) {
				$customer_group_id = (int)$post_info['customer_group_id'];
			} else {
				$customer_group_id = (int)$this->config->get('config_customer_group_id');
			}

			$this->load->model('account/customer_group');

			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

			if (!$customer_group_info || !in_array($customer_group_id, (array)$this->config->get('config_customer_group_display'))) {
				$json['error']['warning'] = $this->language->get('error_customer_group');
			}

			if (!oc_validate_length($post_info['firstname'], 1, 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			if (!oc_validate_length($post_info['lastname'], 1, 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}

			if (($post_info['account'] || trim((string)$post_info['email']) !== '') && !oc_validate_email($post_info['email'])) {
				$json['error']['email'] = $this->language->get('error_email');
			}

			// Total Customers
			$this->load->model('account/customer');

			if ($post_info['account'] && $this->model_account_customer->getTotalCustomersByEmail($post_info['email'])) {
				$json['error']['warning'] = $this->language->get('error_exists');
			}

			// Logged in, so add customer details
			if ($this->customer->isLogged()) {
				$customer_info = $this->model_account_customer->getCustomerByEmail($post_info['email']);

				if ($customer_info['customer_id'] != $this->customer->getId()) {
					$json['error']['warning'] = $this->language->get('error_exists');
				}
			}

			$telephone = preg_replace('/[^0-9]+/', '', (string)$post_info['telephone']);
			if (strlen($telephone) === 11 && str_starts_with($telephone, '84')) {
				$telephone = '0' . substr($telephone, 2);
			}
			if (!preg_match('/^0[35789][0-9]{8}$/D', $telephone)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			} else {
				$post_info['telephone'] = $telephone;
			}

			// Custom fields validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account') {
					if ($custom_field['required'] && empty($post_info['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$json['error']['custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !oc_validate_regex($post_info['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], $custom_field['validation'])) {
						$json['error']['custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
					}
				}
			}

			if ($this->config->get('config_checkout_payment_address')) {
				$post_info['payment_hamlet_name'] = is_scalar($post_info['payment_hamlet_name']) ? trim((string)$post_info['payment_hamlet_name']) : '';
				$post_info['payment_subhamlet_name'] = is_scalar($post_info['payment_subhamlet_name']) ? trim((string)$post_info['payment_subhamlet_name']) : '';
				if ($post_info['payment_hamlet_name'] !== '' && !oc_validate_length($post_info['payment_hamlet_name'], 1, 128)) {
					$json['error']['payment_hamlet_name'] = 'Tên Tổ dân phố, Khu phố hoặc Thôn không hợp lệ.';
				}
				if ($post_info['payment_subhamlet_name'] !== '' && !oc_validate_length($post_info['payment_subhamlet_name'], 1, 128)) {
					$json['error']['payment_subhamlet_name'] = 'Tên xóm/ấp không được vượt quá 128 ký tự.';
				}
				$payment_vn_address = $this->getVietnamAddress((int)$post_info['payment_zone_id'], is_scalar($post_info['payment_ward_code']) ? (string)$post_info['payment_ward_code'] : '');
				if (!$payment_vn_address) {
					$json['error']['warning'] = 'Vui lòng chọn Tỉnh/Thành phố và Phường/Xã.';
				} else {
					$post_info['payment_city'] = $payment_vn_address['province_name'];
					$post_info['payment_address_2'] = 'Phường/Xã: ' . $payment_vn_address['ward_full_name'] . ' | Tổ dân phố/Khu phố/Thôn: ' . $post_info['payment_hamlet_name'];
					if ($post_info['payment_subhamlet_name'] !== '') {
						$post_info['payment_address_2'] .= ' | Xóm/Ấp: ' . $post_info['payment_subhamlet_name'];
					}
				}

				if (!oc_validate_length(trim((string)$post_info['payment_address_1']), 3, 128)) {
					$json['error']['payment_address_1'] = $this->language->get('error_address_1');
				}

				if (!oc_validate_length($post_info['payment_city'], 2, 128)) {
					$json['error']['payment_city'] = $this->language->get('error_city');
				}

				// Country
				$this->load->model('localisation/country');

				$payment_country_info = $this->model_localisation_country->getCountry((int)$post_info['payment_country_id']);

				if ($payment_country_info && (int)$post_info['payment_country_id'] !== 230 && $payment_country_info['postcode_required'] && !oc_validate_length($post_info['payment_postcode'], 2, 10)) {
					$json['error']['payment_postcode'] = $this->language->get('error_postcode');
				}

				if (!$payment_country_info) {
					$json['error']['payment_country'] = $this->language->get('error_country');
				}

				// Total Zones
				$this->load->model('localisation/zone');

				$zone_total = $this->model_localisation_zone->getTotalZonesByCountryId((int)$post_info['payment_country_id']);

				if ($zone_total && !$post_info['payment_zone_id']) {
					$json['error']['payment_zone'] = 'Vui lòng chọn tỉnh/thành phố.';
				}

				$payment_zone_info = $this->model_localisation_zone->getZone((int)$post_info['payment_zone_id']);
				if (!$payment_zone_info || (int)$payment_zone_info['country_id'] !== 230) {
					$json['error']['payment_zone'] = 'Vui lòng chọn tỉnh/thành phố hợp lệ.';
				}

				// Custom fields validation
				foreach ($custom_fields as $custom_field) {
					if ($custom_field['location'] == 'address') {
						if ($custom_field['required'] && empty($post_info['payment_custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
							$json['error']['payment_custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
						} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !oc_validate_regex($post_info['payment_custom_field'][$custom_field['location']][$custom_field['custom_field_id']], $custom_field['validation'])) {
							$json['error']['payment_custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
						}
					}
				}
			}

			if ($this->cart->hasShipping() && !$post_info['address_match']) {
				$post_info['shipping_hamlet_name'] = is_scalar($post_info['shipping_hamlet_name']) ? trim((string)$post_info['shipping_hamlet_name']) : '';
				$post_info['shipping_subhamlet_name'] = is_scalar($post_info['shipping_subhamlet_name']) ? trim((string)$post_info['shipping_subhamlet_name']) : '';
				if ($post_info['shipping_hamlet_name'] !== '' && !oc_validate_length($post_info['shipping_hamlet_name'], 1, 128)) {
					$json['error']['shipping_hamlet_name'] = 'Tên Tổ dân phố, Khu phố hoặc Thôn không hợp lệ.';
				}
				if ($post_info['shipping_subhamlet_name'] !== '' && !oc_validate_length($post_info['shipping_subhamlet_name'], 1, 128)) {
					$json['error']['shipping_subhamlet_name'] = 'Tên xóm/ấp không được vượt quá 128 ký tự.';
				}
				// If payment address not required we need to use the firstname and lastname from the account.
				if ($this->config->get('config_checkout_payment_address')) {
					if (!oc_validate_length($post_info['shipping_firstname'], 1, 32)) {
						$json['error']['shipping_firstname'] = $this->language->get('error_firstname');
					}

					if (!oc_validate_length($post_info['shipping_lastname'], 1, 32)) {
						$json['error']['shipping_lastname'] = $this->language->get('error_lastname');
					}
				}

				if (!oc_validate_length(trim((string)$post_info['shipping_address_1']), 3, 128)) {
					$json['error']['shipping_address_1'] = $this->language->get('error_address_1');
				}

				$shipping_vn_address = $this->getVietnamAddress((int)$post_info['shipping_zone_id'], is_scalar($post_info['shipping_ward_code']) ? (string)$post_info['shipping_ward_code'] : '');
				if (!$shipping_vn_address) {
					$json['error']['warning'] = 'Vui lòng chọn Tỉnh/Thành phố và Phường/Xã cho địa chỉ giao hàng.';
				} else {
					$post_info['shipping_city'] = $shipping_vn_address['province_name'];
					$post_info['shipping_address_2'] = 'Phường/Xã: ' . $shipping_vn_address['ward_full_name'] . ' | Tổ dân phố/Khu phố/Thôn: ' . $post_info['shipping_hamlet_name'];
					if ($post_info['shipping_subhamlet_name'] !== '') {
						$post_info['shipping_address_2'] .= ' | Xóm/Ấp: ' . $post_info['shipping_subhamlet_name'];
					}
				}

				if (!oc_validate_length($post_info['shipping_city'], 2, 128)) {
					$json['error']['shipping_city'] = $this->language->get('error_city');
				}

				// Country
				$this->load->model('localisation/country');

				$shipping_country_info = $this->model_localisation_country->getCountry((int)$post_info['shipping_country_id']);

				if ($shipping_country_info && (int)$post_info['shipping_country_id'] !== 230 && $shipping_country_info['postcode_required'] && !oc_validate_length($post_info['shipping_postcode'], 2, 10)) {
					$json['error']['shipping_postcode'] = $this->language->get('error_postcode');
				}

				if (!$shipping_country_info) {
					$json['error']['shipping_country'] = $this->language->get('error_country');
				}

				// Zones
				$this->load->model('localisation/zone');

				// Total Zones
				$zone_total = $this->model_localisation_zone->getTotalZonesByCountryId((int)$post_info['shipping_country_id']);

				if ($zone_total && !$post_info['shipping_zone_id']) {
					$json['error']['shipping_zone'] = 'Vui lòng chọn tỉnh/thành phố.';
				}

				$shipping_zone_info = $this->model_localisation_zone->getZone((int)$post_info['shipping_zone_id']);
				if (!$shipping_zone_info || (int)$shipping_zone_info['country_id'] !== 230) {
					$json['error']['shipping_zone'] = 'Vui lòng chọn tỉnh/thành phố hợp lệ.';
				}

				// Custom fields validation
				foreach ($custom_fields as $custom_field) {
					if ($custom_field['location'] == 'address') {
						if ($custom_field['required'] && empty($post_info['shipping_custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
							$json['error']['shipping_custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
						} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !oc_validate_regex($post_info['shipping_custom_field'][$custom_field['location']][$custom_field['custom_field_id']], $custom_field['validation'])) {
							$json['error']['shipping_custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
						}
					}
				}
			}

			// If account register password required
			if ($post_info['account']) {
				$password = html_entity_decode($post_info['password'], ENT_QUOTES, 'UTF-8');

				if (!oc_validate_length($password, (int)$this->config->get('config_password_length'), 40)) {
					$json['error']['password'] = sprintf($this->language->get('error_password_length'), $this->config->get('config_password_length'));
				}

				$required = [];

				if ($this->config->get('config_password_uppercase') && !preg_match('/[A-Z]/', $password)) {
					$required[] = $this->language->get('error_password_uppercase');
				}

				if ($this->config->get('config_password_lowercase') && !preg_match('/[a-z]/', $password)) {
					$required[] = $this->language->get('error_password_lowercase');
				}

				if ($this->config->get('config_password_number') && !preg_match('/[0-9]/', $password)) {
					$required[] = $this->language->get('error_password_number');
				}

				if ($this->config->get('config_password_symbol') && !preg_match('/[^a-zA-Z0-9]/', $password)) {
					$required[] = $this->language->get('error_password_symbol');
				}

				if ($required) {
					$json['error']['password'] = sprintf($this->language->get('error_password'), implode(', ', $required), $this->config->get('config_password_length'));
				}

				// Agree to terms
				$this->load->model('catalog/information');

				$information_info = $this->model_catalog_information->getInformation((int)$this->config->get('config_account_id'));

				if ($information_info && !$post_info['agree']) {
					$json['error']['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
				}
			}

			// Captcha
			$this->load->model('setting/extension');

			if (!$this->customer->isLogged()) {
				$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

				if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code'] . '.validate');

					if ($captcha) {
						$json['error']['captcha'] = $captcha;
					}
				}
			}
		}

		if (!$json) {
			// Add customer details into session
			$customer_data = [
				'customer_id'       => 0,
				'customer_group_id' => $customer_group_id,
				'firstname'         => $post_info['firstname'],
				'lastname'          => $post_info['lastname'],
				'email'             => $post_info['email'],
				'telephone'         => $post_info['telephone'],
				'address_match'     => $post_info['address_match'],
				'custom_field'      => $post_info['custom_field']['account'] ?? [],
				'password'          => $post_info['password'],
				'newsletter'        => $post_info['newsletter']
			];

			// Register
			if ($post_info['account']) {
				$customer_data['customer_id'] = $this->model_account_customer->addCustomer($customer_data);
			}

			// Logged in, so edit customer details
			if ($this->customer->isLogged()) {
				$this->model_account_customer->editCustomer($this->customer->getId(), $customer_data);
			}

			unset($customer_data['password']);

			// Check if current customer group requires approval
			if (!$customer_group_info['approval']) {
				$this->session->data['customer'] = $customer_data;
			}

			// Payment Address
			$this->load->model('account/address');

			if ($this->config->get('config_checkout_payment_address')) {
				if (isset($this->session->data['payment_address']['address_id'])) {
					$address_id = $this->session->data['payment_address']['address_id'];
				} else {
					$address_id = 0;
				}

				if ($payment_country_info) {
					$country = $payment_country_info['name'];
					$iso_code_2 = $payment_country_info['iso_code_2'];
					$iso_code_3 = $payment_country_info['iso_code_3'];
					$address_format_id = $payment_country_info['address_format_id'];
				} else {
					$country = '';
					$iso_code_2 = '';
					$iso_code_3 = '';
					$address_format_id = 0;
				}

				// Address Format
				$this->load->model('localisation/address_format');

				$address_format_info = $this->model_localisation_address_format->getAddressFormat($address_format_id);

				if ($address_format_info) {
					$address_format = $address_format_info['address_format'];
				} else {
					$address_format = '';
				}

				// Zone
				$this->load->model('localisation/zone');

				$zone_info = $this->model_localisation_zone->getZone($post_info['payment_zone_id']);

				if ($zone_info) {
					$zone = $zone_info['name'];
					$zone_code = $zone_info['code'];
				} else {
					$zone = '';
					$zone_code = '';
				}

				$payment_address_data = [
					'address_id'     => $address_id,
					'firstname'      => $post_info['firstname'],
					'lastname'       => $post_info['lastname'],
					'company'        => $post_info['payment_company'],
					'address_1'      => $post_info['payment_address_1'],
					'address_2'      => $post_info['payment_address_2'],
					'city'           => $post_info['payment_city'],
					'postcode'       => $post_info['payment_postcode'],
					'zone_id'        => $post_info['payment_zone_id'],
					'zone'           => $zone,
					'zone_code'      => $zone_code,
					'country_id'     => $post_info['payment_country_id'],
					'country'        => $country,
					'iso_code_2'     => $iso_code_2,
					'iso_code_3'     => $iso_code_3,
					'address_format' => $address_format,
					'custom_field'   => $post_info['payment_custom_field']['address'] ?? []
				];

				// Add
				if ($post_info['account']) {
					$payment_address_data['default'] = 1;

					$payment_address_data['address_id'] = $this->model_account_address->addAddress($customer_data['customer_id'], $payment_address_data);
				}

				// Edit
				if ($this->customer->isLogged() && $payment_address_data['address_id']) {
					$this->model_account_address->editAddress($this->customer->getId(), $payment_address_data['address_id'], $payment_address_data);
				}

				// Requires Approval
				if (!$customer_group_info['approval']) {
					$this->session->data['payment_address'] = $payment_address_data;
				}
			}

			// Shipping Address
			if ($this->cart->hasShipping()) {
				if (!$post_info['address_match']) {
					if (isset($this->session->data['shipping_address']['address_id'])) {
						$address_id = $this->session->data['shipping_address']['address_id'];
					} else {
						$address_id = 0;
					}

					if (!$this->config->get('config_checkout_payment_address')) {
						$firstname = $post_info['firstname'];
						$lastname = $post_info['lastname'];
					} else {
						$firstname = $post_info['shipping_firstname'];
						$lastname = $post_info['shipping_lastname'];
					}

					// Address Format
					if ($shipping_country_info) {
						$country = $shipping_country_info['name'];
						$iso_code_2 = $shipping_country_info['iso_code_2'];
						$iso_code_3 = $shipping_country_info['iso_code_3'];
						$address_format_id = $shipping_country_info['address_format_id'];
					} else {
						$country = '';
						$iso_code_2 = '';
						$iso_code_3 = '';
						$address_format_id = 0;
					}

					$this->load->model('localisation/address_format');

					$address_format_info = $this->model_localisation_address_format->getAddressFormat($address_format_id);

					if ($address_format_info) {
						$address_format = $address_format_info['address_format'];
					} else {
						$address_format = '';
					}

					// Zone
					$this->load->model('localisation/zone');

					$zone_info = $this->model_localisation_zone->getZone($post_info['shipping_zone_id']);

					if ($zone_info) {
						$zone = $zone_info['name'];
						$zone_code = $zone_info['code'];
					} else {
						$zone = '';
						$zone_code = '';
					}

					$shipping_address_data = [
						'address_id'     => $address_id,
						'firstname'      => $firstname,
						'lastname'       => $lastname,
						'company'        => $post_info['shipping_company'],
						'address_1'      => $post_info['shipping_address_1'],
						'address_2'      => $post_info['shipping_address_2'],
						'city'           => $post_info['shipping_city'],
						'postcode'       => $post_info['shipping_postcode'],
						'zone_id'        => $post_info['shipping_zone_id'],
						'zone'           => $zone,
						'zone_code'      => $zone_code,
						'country_id'     => $post_info['shipping_country_id'],
						'country'        => $country,
						'iso_code_2'     => $iso_code_2,
						'iso_code_3'     => $iso_code_3,
						'address_format' => $address_format,
						'custom_field'   => $post_info['shipping_custom_field']['address'] ?? []
					];

					// Add Address to account if account is being created.
					if ($post_info['account']) {
						if (!$this->config->get('config_checkout_payment_address')) {
							$shipping_address_data['default'] = 1;
						}

						$shipping_address_data['address_id'] = $this->model_account_address->addAddress($customer_data['customer_id'], $shipping_address_data);
					}

					// Edit
					if ($this->customer->isLogged() && $shipping_address_data['address_id']) {
						$this->model_account_address->editAddress($this->customer->getId(), $shipping_address_data['address_id'], $shipping_address_data);
					}

					// Requires Approval
					if (!$customer_group_info['approval']) {
						$this->session->data['shipping_address'] = $shipping_address_data;
					}
				} elseif (!$customer_group_info['approval'] && $this->config->get('config_checkout_payment_address')) {
					$this->session->data['shipping_address'] = $this->session->data['payment_address'];

					// Remove the address id so if the customer changes their mind and requires changing a different shipping address it will create a new address.
					$this->session->data['shipping_address']['address_id'] = 0;
				}
			}

			// If everything good login
			if (!$customer_group_info['approval']) {
				if ($post_info['account']) {
					if ($this->customer->login($post_info['email'], $password)) {
						$this->session->data['pandashop_checkin_prompt'] = true;
					}

					// Create customer token
					$this->session->data['customer_token'] = oc_token(26);

					$json['success'] = $this->language->get('text_success_add');
				} elseif ($this->customer->isLogged()) {
					$json['success'] = $this->language->get('text_success_edit');
				} else {
					$json['success'] = $this->language->get('text_success_guest');
				}
			} else {
				// If account needs approval, we redirect to the account success / requires approval page.
				$json['redirect'] = $this->url->link('account/success', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
			}

			unset($this->session->data['order_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($post_info['email']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/** Validate that the submitted ward belongs to the selected Vietnamese province. */
	private function getVietnamAddress(int $zoneId, string $wardCode): array {
		if ($zoneId < 1 || !preg_match('/^[A-Za-z0-9-]{1,32}$/', $wardCode)) {
			return [];
		}

		$query = $this->db->query("SELECT `p`.`name` AS `province_name`, `w`.`full_name` AS `ward_full_name` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`zone_id` = '" . $zoneId . "' AND `z`.`country_id` = '230' AND `z`.`status` = '1' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		return $query->row ?: [];
	}

	private function getVietnamAddressCodes(int $zoneId, string $address2): array {
		if ($zoneId < 1 || !preg_match('/Phường\/Xã:\s*(.+?)(?:\s+—\s+Quận\/Huyện:.*)?$/u', $address2, $matches)) return [];
		$query = $this->db->query("SELECT `w`.`code` AS `ward_code` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) WHERE `p`.`zone_id` = '" . $zoneId . "' AND (`w`.`full_name` = '" . $this->db->escape(trim($matches[1])) . "' OR `w`.`name` = '" . $this->db->escape(trim($matches[1])) . "') LIMIT 1");
		return $query->row ?: [];
	}
}
