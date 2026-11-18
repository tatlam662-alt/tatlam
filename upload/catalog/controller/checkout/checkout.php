<?php
namespace Opencart\Catalog\Controller\Checkout;
/**
 * Class Checkout
 *
 * @package Opencart\Catalog\Controller\Checkout
 */
class Checkout extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Preserve the store's account-only checkout rule.
		if (!$this->customer->isLogged() && (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload() || $this->cart->hasSubscription())) {
			$checkout_url = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&redirect=' . urlencode($checkout_url), true));
			return;
		}

		// Validate cart to see if it has products and has stock.
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$this->response->redirect($this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
			return;
		}

		$this->load->language('checkout/checkout');

		// Vietnam is the default country for checkout address selectors.
		$data['country_id'] = 230;
		$this->load->model('localisation/zone');
		$data['zones'] = $this->model_localisation_zone->getZonesByCountryId(230);
		$data['province_url'] = html_entity_decode($this->url->link('checkout/checkout.provinces', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		$data['ward_url'] = html_entity_decode($this->url->link('checkout/checkout.wards', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		$data['hamlets_url'] = html_entity_decode($this->url->link('checkout/checkout.hamlets', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		$data['geocode_url'] = $this->customer->isLogged()
			? html_entity_decode($this->url->link('account/address.geocode', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . (string)($this->session->data['customer_token'] ?? '')))
			: '';
		$data['save_address_url'] = $this->postActionUrl('checkout/checkout.saveAddressSelection');
		$data['save_recipient_url'] = $this->postActionUrl('checkout/checkout.saveRecipient');
		$addressLanguage = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));
		$customerToken = (string)($this->session->data['customer_token'] ?? '');
		$data['address_edit_url'] = html_entity_decode($this->url->link('account/address.form', $addressLanguage . '&customer_token=' . $customerToken . '&return=checkout'));
		$data['address_delete_url'] = html_entity_decode($this->url->link('account/address.delete', $addressLanguage . '&customer_token=' . $customerToken));

		$this->document->setTitle($this->language->get('heading_title'));
		$data['checkout_product_count'] = count($this->cart->getProducts());
		$data['cart_url'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		if ($this->customer->isLogged()) {
			$data['checkout_customer'] = [
				'firstname' => $this->customer->getFirstName(),
				'lastname' => $this->customer->getLastName(),
				'telephone' => $this->customer->getTelephone(),
				'email' => $this->customer->getEmail()
			];
		} else {
			$data['checkout_customer'] = [];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		if (!$this->customer->isLogged()) {
			$data['register'] = $this->load->controller('checkout/register');
		} else {
			$data['register'] = '';
		}

		if ($this->customer->isLogged() && $this->config->get('config_checkout_payment_address')) {
			$data['payment_address'] = $this->load->controller('checkout/payment_address');
		} else {
			$data['payment_address'] = '';
		}

		if ($this->customer->isLogged() && $this->cart->hasShipping()) {
			$data['shipping_address'] = $this->load->controller('checkout/shipping_address');
		} else {
			$data['shipping_address'] = '';
		}

		if ($this->cart->hasShipping()) {
			$data['shipping_method'] = $this->load->controller('checkout/shipping_method');
		} else {
			$data['shipping_method'] = '';
		}

		$data['payment_method'] = $this->load->controller('checkout/payment_method');
		$data['confirm'] = $this->load->controller('checkout/confirm');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		// Use a fresh Twig cache key so an old compiled checkout template cannot
		// keep hiding the country selector after the template was corrected.
		$this->response->setOutput($this->load->view('checkout/checkout_vietnam', $data));
	}

	/** Return the official 34 Vietnamese provinces for the searchable selector. */
	public function provinces(): void {
		$query = $this->db->query("SELECT `code`, `name` FROM `" . DB_PREFIX . "vn_province` ORDER BY `name` ASC");
		$this->json($query->rows);
	}

	/** Return wards belonging directly to a province, optionally filtered by text. */
	public function wards(): void {
		$provinceCode = $this->safeCode($this->request->get['province_code'] ?? $this->request->post['province_code'] ?? null);
		$zoneId = $this->positiveId($this->request->get['zone_id'] ?? $this->request->post['zone_id'] ?? null);
		if ($provinceCode === '' && $zoneId) {
			$province = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "vn_province` WHERE `zone_id` = '" . $zoneId . "' LIMIT 1");
			$provinceCode = $province->num_rows ? (string)$province->row['code'] : '';
		}
		$search = $this->request->get['q'] ?? $this->request->post['q'] ?? '';
		if ($provinceCode === '') {
			$this->json(['error' => 'Vui lòng chọn tỉnh/thành phố.'], 400);
			return;
		}
		$province = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "vn_province` WHERE `code` = '" . $this->db->escape($provinceCode) . "' LIMIT 1");
		if (!$province->num_rows) {
			$this->json(['error' => 'Tỉnh/thành phố không hợp lệ.'], 404);
			return;
		}
		$where = "`province_code` = '" . $this->db->escape($provinceCode) . "'";
		if (is_scalar($search) && trim((string)$search) !== '') {
			$like = $this->db->escape('%' . trim((string)$search) . '%');
			$where .= " AND (`name` LIKE '" . $like . "' OR `full_name` LIKE '" . $like . "')";
		}
		$query = $this->db->query("SELECT `code`, `name`, `full_name` FROM `" . DB_PREFIX . "vn_ward` WHERE " . $where . " ORDER BY `name` ASC");
		$this->json($query->rows);
	}

	/** Verify and retain the selected current Vietnamese address hierarchy. */
	/** Return matching hamlet suggestions for a current ward. */
	public function hamlets(): void {
		$wardCode = $this->safeCode($this->request->get['ward_code'] ?? $this->request->post['ward_code'] ?? null);
		$search = $this->request->get['q'] ?? $this->request->post['q'] ?? $this->request->get['filter'] ?? $this->request->post['filter'] ?? '';
		$category = $this->request->get['category'] ?? $this->request->post['category'] ?? $this->request->get['type'] ?? $this->request->post['type'] ?? 'to_dan_pho_thon';
		$categoryMap = [
			'hamlet' => 'hamlet',
			'to_dan_pho_thon' => 'hamlet',
			'subhamlet' => 'subhamlet',
			'xom_ap' => 'subhamlet'
		];
		$category = is_scalar($category) && isset($categoryMap[(string)$category]) ? $categoryMap[(string)$category] : 'hamlet';
		if ($wardCode === '') {
			$this->json(['error' => 'Vui lòng chọn phường/xã trước.'], 400);
			return;
		}

		$ward = $this->db->query("SELECT `code`, `name`, `full_name` FROM `" . DB_PREFIX . "vn_ward` WHERE `code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		if (!$ward->num_rows) {
			$this->json(['error' => 'Phường/xã không hợp lệ.'], 404);
			return;
		}

		$wardNames = [];
		foreach ([$ward->row['name'], $ward->row['full_name'] ?? ''] as $wardName) {
			$wardName = trim((string)$wardName);
			if ($wardName !== '') {
				$wardNames[] = $wardName;
				$wardNames[] = trim((string)preg_replace('/^(?:Phường|Xã|Thị trấn|Đặc khu)\s+/iu', '', $wardName));
			}
		}
		$wardNames = array_unique(array_filter($wardNames));
		$excludeWardName = '';
		foreach ($wardNames as $wardName) {
			$excludeWardName .= " AND `name` <> '" . $this->db->escape($wardName) . "'";
		}
		$where = "`ward_code` = '" . $this->db->escape($wardCode) . "' AND `category` = '" . $this->db->escape($category) . "' AND `status` = '1' AND (`is_verified` = '1' OR `source_name` LIKE 'OpenStreetMap%' OR `source_name` LIKE 'Khách hàng PanDaShop%')" . $excludeWardName;
		if (is_scalar($search) && trim((string)$search) !== '') {
			$where .= " AND `name` LIKE '" . $this->db->escape('%' . trim((string)$search) . '%') . "'";
		}
		$query = $this->db->query("SELECT `name`, `source_name`, `source_url`, `is_verified` FROM `" . DB_PREFIX . "vn_hamlet` WHERE " . $where . " ORDER BY `name` ASC LIMIT 200");
		$this->json($query->rows);
	}

	public function saveAddressSelection(): void {
		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->json(['error' => 'Phương thức gửi không hợp lệ.'], 405);
			return;
		}
		$zoneId = $this->positiveId($this->request->post['zone_id'] ?? null);
		$wardCode = $this->safeCode($this->request->post['ward_code'] ?? null);
		if (!$zoneId || $wardCode === '') {
			$this->json(['error' => 'Vui lòng chọn tỉnh/thành phố và phường/xã.'], 400);
			return;
		}
		$query = $this->db->query("SELECT `p`.`code` AS `province_code`, `p`.`name` AS `province_name`, `w`.`code` AS `ward_code`, `w`.`name` AS `ward_name`, `w`.`full_name` AS `ward_full_name` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`zone_id` = '" . $zoneId . "' AND `z`.`country_id` = '230' AND `z`.`status` = '1' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		if (!$query->num_rows) {
			$this->json(['error' => 'Địa chỉ đã chọn không khớp. Vui lòng chọn lại.'], 422);
			return;
		}
		$this->session->data['vn_address_selection'] = $query->row;
		$this->json(['success' => true] + $query->row);
	}

	/** Validate and retain checkout contact details for this order session. */
	public function saveRecipient(): void {
		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->response->addHeader('Allow: POST');
			$this->json(['error' => 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.'], 405);
			return;
		}

		if (!$this->customer->isLogged()) {
			$language_input = $this->request->get['language'] ?? $this->config->get('config_language');
			$language = is_scalar($language_input) ? (string)$language_input : (string)$this->config->get('config_language');
			$this->json(['redirect' => $this->url->link('account/login', 'language=' . $language . '&redirect=' . urlencode($this->url->link('checkout/checkout', 'language=' . $language, true)), true)]);
			return;
		}

		// OpenCart's logged-in Customer object is authoritative. Some login
		// integrations only set customer_id, so rebuild the checkout session
		// customer payload before later checkout controllers rely on it.
		if (!isset($this->session->data['customer']) || !is_array($this->session->data['customer'])) {
			$this->load->model('account/customer');
			$customer = $this->model_account_customer->getCustomer((int)$this->customer->getId());
			if (!$customer) {
				$this->json(['error' => 'Không tải được thông tin tài khoản. Vui lòng đăng nhập lại.'], 401);
				return;
			}
			$this->session->data['customer'] = [
				'customer_id' => (int)$customer['customer_id'],
				'customer_group_id' => (int)$customer['customer_group_id'],
				'firstname' => (string)$customer['firstname'],
				'lastname' => (string)$customer['lastname'],
				'email' => (string)$customer['email'],
				'telephone' => (string)$customer['telephone'],
				'custom_field' => is_array($customer['custom_field'] ?? null) ? $customer['custom_field'] : []
			];
		}

		// Prefer submitted values; if the request has no fields, retain the
		// logged-in customer's saved data.
		$customer = $this->session->data['customer'];
		$firstname_input = $this->request->post['firstname'] ?? $customer['firstname'] ?? '';
		$lastname_input = $this->request->post['lastname'] ?? $customer['lastname'] ?? '';
		$firstname = is_scalar($firstname_input) ? trim((string)$firstname_input) : '';
		$lastname = is_scalar($lastname_input) ? trim((string)$lastname_input) : '';
		$telephoneInput = $this->request->post['telephone'] ?? $customer['telephone'] ?? '';
		$telephone = is_scalar($telephoneInput) ? (preg_replace('/[^0-9]+/', '', (string)$telephoneInput) ?? '') : '';
		// Normalize Vietnam's international dialing prefix to the local 0 prefix.
		if (strlen($telephone) === 11 && str_starts_with($telephone, '84')) {
			$telephone = '0' . substr($telephone, 2);
		}
		$email_input = $this->request->post['email'] ?? $customer['email'] ?? '';
		$email_is_scalar = is_scalar($email_input);
		$email = $email_is_scalar ? trim((string)$email_input) : '';
		$errors = [];
		if (!oc_validate_length($firstname, 1, 32)) $errors['firstname'] = 'Vui lòng nhập tên (tối đa 32 ký tự).';
		if (!oc_validate_length($lastname, 1, 32)) $errors['lastname'] = 'Vui lòng nhập họ (tối đa 32 ký tự).';
		$telephoneLength = strlen($telephone);
		$isVietnamMobile = $telephoneLength === 10
			&& $telephone[0] === '0'
			&& in_array($telephone[1], ['3', '5', '7', '8', '9'], true)
			&& ctype_digit($telephone);
		if (!$isVietnamMobile) {
			$errors['telephone'] = 'Số điện thoại phải là số di động Việt Nam hợp lệ.';
		}
		if (!$email_is_scalar || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) $errors['email'] = 'Email chưa đúng định dạng.';
		if ($errors) {
			$this->json(['error' => $errors], 422);
			return;
		}

		$recipient = ['firstname' => $firstname, 'lastname' => $lastname, 'telephone' => $telephone, 'email' => $email];
		$this->session->data['checkout_recipient'] = $recipient;
		$this->json(['success' => true]);
	}

	private function positiveId(mixed $value): int {
		if (!is_scalar($value)) {
			return 0;
		}

		$id = filter_var((string)$value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		return $id === false ? 0 : (int)$id;
	}

	private function safeCode(mixed $value): string {
		if (!is_scalar($value)) return '';
		$code = trim((string)$value);
		return preg_match('/^[A-Za-z0-9-]{1,32}$/', $code) ? $code : '';
	}

	private function json(array $payload, int $status = 200): void {
		if ($status !== 200) {
			$reason = $status === 400 ? ' Bad Request' : ($status === 405 ? ' Method Not Allowed' : ' Error');
			$this->response->addHeader('HTTP/1.1 ' . $status . $reason);
		}
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
	}

	/** Ensure AJAX POST actions do not hit Apache's directory-slash redirect. */
	private function postActionUrl(string $route): string {
		$url = html_entity_decode($this->url->link($route, 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));

		return preg_replace('~(?<!/)\?route=~', '/?route=', $url, 1) ?? $url;
	}
}
