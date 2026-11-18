<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Address
 *
 * @package Opencart\Catalog\Controller\Account
 */
class Address extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('account/address');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['add'] = $this->url->link('account/address.form', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['back'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

		$data['list'] = $this->getList();

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		$data['customer_token'] = $this->session->data['customer_token'];

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/address', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('account/address');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	protected function getList(): string {
		// Addresses
		$data['addresses'] = [];
		$data['text_delete_default'] = $this->language->get('text_delete_default');

		$this->load->model('account/address');

		$results = $this->model_account_address->getAddresses($this->customer->getId());
		$total_addresses = count($results);

		foreach ($results as $result) {
			$find = [
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			];

			$replace = [
				'firstname' => htmlspecialchars((string)$result['firstname'], ENT_QUOTES, 'UTF-8'),
				'lastname'  => htmlspecialchars((string)$result['lastname'], ENT_QUOTES, 'UTF-8'),
				'company'   => htmlspecialchars((string)$result['company'], ENT_QUOTES, 'UTF-8'),
				'address_1' => htmlspecialchars((string)$result['address_1'], ENT_QUOTES, 'UTF-8'),
				'address_2' => htmlspecialchars((string)$result['address_2'], ENT_QUOTES, 'UTF-8'),
				'city'      => htmlspecialchars((string)$result['city'], ENT_QUOTES, 'UTF-8'),
				'postcode'  => htmlspecialchars((string)$result['postcode'], ENT_QUOTES, 'UTF-8'),
				'zone'      => htmlspecialchars((string)$result['zone'], ENT_QUOTES, 'UTF-8'),
				'zone_code' => htmlspecialchars((string)$result['zone_code'], ENT_QUOTES, 'UTF-8'),
				'country'   => htmlspecialchars((string)$result['country'], ENT_QUOTES, 'UTF-8')
			];

			$data['addresses'][] = [
				'address' => str_replace(["\r\n", "\r", "\n"], '<br/>', preg_replace(["/\\s\\s+/", "/\r\r+/", "/\n\n+/"], '<br/>', trim(str_replace($find, $replace, $result['address_format'])))),
				'name' => trim((string)$result['firstname'] . ' ' . (string)$result['lastname']),
				'telephone' => (string)$this->customer->getTelephone(),
				'can_delete' => ((int)$result['address_id'] !== (int)$this->customer->getAddressId()) || $total_addresses <= 1,
				'edit'    => $this->url->link('account/address.form', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&address_id=' . $result['address_id']),
				'delete'  => $this->url->link('account/address.delete', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&address_id=' . $result['address_id'])
			] + $result;
		}

		return $this->load->view('account/address_list', $data);
	}

	/**
	 * Form
	 *
	 * @return void
	 */
	public function form(): void {
		$this->load->language('account/address');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['text_address'] = !isset($this->request->get['address_id']) ? $this->language->get('text_address_add') : $this->language->get('text_address_edit');

		$data['error_upload_size'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size'));

		$data['config_file_max_size'] = ((int)$this->config->get('config_file_max_size') * 1024 * 1024);

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		if (!isset($this->request->get['address_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_address_add'),
				'href' => $this->url->link('account/address.form', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_address_edit'),
				'href' => $this->url->link('account/address.form', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&address_id=' . (int)$this->request->get['address_id'])
			];
		}

		if (!isset($this->request->get['address_id'])) {
			$data['save'] = $this->url->link('account/address.save', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		} else {
			$return = (($this->request->get['return'] ?? '') === 'checkout') ? '&return=checkout' : '';
			$data['save'] = $this->url->link('account/address.save', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&address_id=' . (int)$this->request->get['address_id'] . $return);
		}
		$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);

		$this->session->data['upload_token'] = oc_token(32);

		$data['upload'] = $this->url->link('tool/upload', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&upload_token=' . $this->session->data['upload_token']);

		// Customer
		$address_info = [];

		if (isset($this->request->get['address_id'])) {
			$this->load->model('account/address');

			$address_info = $this->model_account_address->getAddress($this->customer->getId(), (int)$this->request->get['address_id']) ?: [];
		}

		$data['firstname'] = $address_info ? $address_info['firstname'] : '';
		$data['lastname'] = $address_info ? $address_info['lastname'] : '';
		$data['telephone'] = $this->customer->getTelephone();
		$data['company'] = $address_info ? $address_info['company'] : '';
		$data['address_1'] = $address_info ? $address_info['address_1'] : '';
		$data['address_2'] = $address_info ? $address_info['address_2'] : '';
		$data['hamlet_name'] = '';
		$data['subhamlet_name'] = '';
		if ($address_info) {
			foreach (array_slice(explode('|', (string)$address_info['address_2']), 1) as $addressPart) {
				if (!preg_match('/^\s*([^:]+):\s*(.*)$/u', trim($addressPart), $addressMatch)) {
					continue;
				}

				$label = mb_strtolower(trim($addressMatch[1]), 'UTF-8');
				$normalizedLabel = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label);
				if (str_contains($normalizedLabel, 'to') || str_contains($normalizedLabel, 'thon') || str_contains($normalizedLabel, 'khu pho')) {
					$data['hamlet_name'] = trim($addressMatch[2]);
				} elseif (str_contains($normalizedLabel, 'xom') || str_contains($normalizedLabel, 'ap')) {
					$data['subhamlet_name'] = trim($addressMatch[2]);
				}
			}
		}
		$data['postcode'] = '';
		$data['city'] = $address_info ? $address_info['city'] : '';

		// Countries
		$data['country_id'] = 230;

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Zones
		$data['zone_id'] = $address_info ? $this->resolveVietnamZoneId((int)$address_info['zone_id'], (string)$address_info['city']) : 0;
		$data['vn_ward_code'] = '';
		if ($address_info && (int)$address_info['country_id'] === 230 && preg_match('/^[^:]+:\s*(.*?)(?:\s+\|\s+.*)?$/u', (string)$address_info['address_2'], $vn_address_parts)) {
			$vn_address_query = $this->db->query("SELECT `w`.`code` AS `ward_code`, `p`.`zone_id` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`country_id` = '230' AND `z`.`status` = '1' AND (`w`.`full_name` = '" . $this->db->escape(trim($vn_address_parts[1])) . "' OR `w`.`name` = '" . $this->db->escape(trim($vn_address_parts[1])) . "') AND (`p`.`zone_id` = '" . (int)$data['zone_id'] . "' OR `p`.`name` = '" . $this->db->escape((string)$address_info['city']) . "') LIMIT 1");
			if ($vn_address_query->num_rows) {
				$data['vn_ward_code'] = $vn_address_query->row['ward_code'];
				$data['zone_id'] = (int)$vn_address_query->row['zone_id'];
			}
		}

		$this->load->model('localisation/zone');

		$data['zones'] = $this->model_localisation_zone->getZonesByCountryId($data['country_id']);

		// Custom Fields
		$data['custom_fields'] = [];

		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($this->customer->getGroupId());

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				$data['custom_fields'][] = $custom_field;
			}
		}

		$data['address_custom_field'] = $address_info ? $address_info['custom_field'] : [];

		$data['default'] = $address_info ? (bool)$address_info['default'] : !$this->customer->getAddressId();

		$data['back'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));
		$data['vn_wards_url'] = html_entity_decode($this->url->link('checkout/checkout.wards', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		$data['vn_hamlets_url'] = html_entity_decode($this->url->link('checkout/checkout.hamlets', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		$data['geocode_url'] = html_entity_decode($this->url->link('account/address.geocode', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/address_form', $data));
	}

	/**
	 * Reverse geocode coordinates through the storefront server to avoid browser CORS failures.
	 *
	 * @return void
	 */
	public function geocode(): void {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');

		if (!$this->load->controller('account/login.validate')) {
			$this->response->addHeader('HTTP/1.1 401 Unauthorized');
			$this->response->setOutput(json_encode(['error' => 'Vui lòng đăng nhập lại rồi thử định vị.']));
			return;
		}

		$latValue = null;
		$lonValue = null;
		foreach ([$this->request->post['lat'] ?? null, $this->request->get['lat'] ?? null] as $value) {
			$coordinate = is_scalar($value) ? str_replace(',', '.', trim((string)$value)) : '';
			if ($coordinate !== '' && is_numeric($coordinate)) {
				$latValue = (float)$coordinate;
				break;
			}
		}
		foreach ([$this->request->post['lon'] ?? null, $this->request->get['lon'] ?? null] as $value) {
			$coordinate = is_scalar($value) ? str_replace(',', '.', trim((string)$value)) : '';
			if ($coordinate !== '' && is_numeric($coordinate)) {
				$lonValue = (float)$coordinate;
				break;
			}
		}

		if ($latValue === null || $lonValue === null) {
			$this->response->addHeader('HTTP/1.1 400 Bad Request');
			$this->response->setOutput(json_encode(['error' => 'Tọa độ GPS không hợp lệ.']));
			return;
		}

		$latitude = $latValue;
		$longitude = $lonValue;

		if (!is_finite($latitude) || !is_finite($longitude) || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
			$this->response->addHeader('HTTP/1.1 400 Bad Request');
			$this->response->setOutput(json_encode(['error' => 'Tọa độ GPS nằm ngoài phạm vi hợp lệ.']));
			return;
		}

		if (!function_exists('curl_init')) {
			$this->response->addHeader('HTTP/1.1 503 Service Unavailable');
			$this->response->setOutput(json_encode(['error' => 'Máy chủ chưa bật thành phần kết nối bản đồ (cURL).']));
			return;
		}

		$lockPath = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'pandashop-nominatim.lock';
		$lockHandle = @fopen($lockPath, 'c+');

		if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
			if (is_resource($lockHandle)) {
				fclose($lockHandle);
			}
			$this->response->addHeader('HTTP/1.1 503 Service Unavailable');
			$this->response->setOutput(json_encode(['error' => 'Dịch vụ định vị đang bận. Vui lòng thử lại sau.']));
			return;
		}

		$ch = null;
		$response = false;
		$httpCode = 0;
		$curlError = '';

		try {
			$lastRequestAt = (float)trim((string)stream_get_contents($lockHandle));
			$waitMicroseconds = 1000000 - (int)((microtime(true) - $lastRequestAt) * 1000000);
			if ($lastRequestAt > 0 && $waitMicroseconds > 0) {
				usleep($waitMicroseconds);
			}

			$query = http_build_query([
				'format' => 'jsonv2',
				'lat' => sprintf('%.7F', $latitude),
				'lon' => sprintf('%.7F', $longitude),
				'addressdetails' => 1,
				'zoom' => 18,
				'accept-language' => 'vi'
			], '', '&', PHP_QUERY_RFC3986);
			$ch = curl_init('https://nominatim.openstreetmap.org/reverse?' . $query);

			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_USERAGENT => 'PanDaShop/1.0 (' . rtrim((string)$this->config->get('config_url'), '/') . ')',
				CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: vi'],
				CURLOPT_REFERER => $this->config->get('config_url')
			]);

			$response = curl_exec($ch);
			$curlError = curl_error($ch);
			$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		} finally {
			// Record completion time and release the shared lock, including on upstream failures.
			ftruncate($lockHandle, 0);
			rewind($lockHandle);
			fwrite($lockHandle, sprintf('%.6F', microtime(true)));
			fflush($lockHandle);
			flock($lockHandle, LOCK_UN);
			fclose($lockHandle);
			if ($ch) {
				curl_close($ch);
			}
		}

		if ($response === false || $httpCode !== 200) {
			error_log('PanDaShop reverse geocode upstream failure: HTTP ' . $httpCode . ($curlError !== '' ? ' (' . $curlError . ')' : ''));
			$this->response->addHeader('HTTP/1.1 502 Bad Gateway');
			$this->response->setOutput(json_encode(['error' => 'Máy chủ không kết nối được dịch vụ bản đồ. Vui lòng thử lại hoặc nhập địa chỉ thủ công.']));
			return;
		}

		$place = json_decode($response, true);
		if (!is_array($place) || empty($place['address']) || !is_array($place['address'])) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			$this->response->setOutput(json_encode(['error' => 'Không tìm thấy địa chỉ bản đồ gần vị trí này. Bạn có thể nhập địa chỉ thủ công.']));
			return;
		}

		$this->response->setOutput(json_encode([
			'address' => $place['address'],
			'display_name' => (string)($place['display_name'] ?? '')
		]));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('account/address');

		$json = [];

		$required = [
			'firstname'  => '',
			'lastname'   => '',
			'telephone'  => '',
			'address_1'  => '',
			'address_2'  => '',
			'hamlet_name' => '',
			'subhamlet_name' => '',
			'city'       => '',
			'postcode'   => '',
			'country_id' => 0,
			'zone_id'    => 0,
			'default'    => 0
		];

		$post_info = $this->request->post + $required;
		$post_info['firstname'] = is_scalar($post_info['firstname']) ? trim((string)$post_info['firstname']) : '';
		$post_info['lastname'] = is_scalar($post_info['lastname']) ? trim((string)$post_info['lastname']) : '';
		$post_info['address_1'] = is_scalar($post_info['address_1']) ? trim((string)$post_info['address_1']) : '';
		// A missing radio value should preserve the current default address when editing it.
		if (!array_key_exists('default', $this->request->post) && isset($this->request->get['address_id']) && $this->customer->getAddressId() === (int)$this->request->get['address_id']) {
			$post_info['default'] = 1;
		}
		// A customer with no remaining default address must make the next saved address default.
		if (!$this->customer->getAddressId()) {
			$post_info['default'] = 1;
		}
		$post_info['country_id'] = 230;
		// Accept canonical native-select names first, with aliases for legacy posts.
		// Keep an already supplied valid ID while resolving display labels.
		$postedZoneId = $this->request->post['zone_id'] ?? $this->request->post['zone'] ?? $this->request->post['province_id'] ?? $post_info['zone_id'];
		$post_info['zone_id'] = is_scalar($postedZoneId) ? (int)$postedZoneId : 0;
		$postedWardCode = $this->request->post['ward_code'] ?? $this->request->post['ward'] ?? $this->request->post['ward_id'] ?? '';
		$post_info['ward_code'] = is_scalar($postedWardCode) ? trim((string)$postedWardCode) : '';
		$post_info['postcode'] = '';
		$post_info['company'] = '';
		// Preserve the labels accompanying the native province and ward values.
		if (is_scalar($this->request->post['province_name'] ?? null) && trim((string)$this->request->post['province_name']) !== '') {
			$post_info['city'] = trim((string)$this->request->post['province_name']);
		}
		if (is_scalar($this->request->post['ward_name'] ?? null) && trim((string)$this->request->post['ward_name']) !== '') {
			$post_info['address_2'] = 'Phường/Xã: ' . trim((string)$this->request->post['ward_name']);
		}
		$post_info['telephone'] = preg_replace('/\D+/', '', (string)$post_info['telephone']);
		if (is_scalar($post_info['address_1']) && strtolower(trim((string)$post_info['address_1'])) === 'admin') $post_info['address_1'] = '';
		$post_info['hamlet_name'] = is_scalar($post_info['hamlet_name']) ? trim((string)$post_info['hamlet_name']) : '';
		$post_info['subhamlet_name'] = is_scalar($post_info['subhamlet_name']) ? trim((string)$post_info['subhamlet_name']) : '';
		if ((int)$post_info['country_id'] === 230) {
			$post_info['zone_id'] = $this->resolveVietnamZoneId((int)$post_info['zone_id'], is_scalar($post_info['city']) ? trim((string)$post_info['city']) : '');
			$post_info['ward_code'] = $this->resolveVietnamWardCode((int)$post_info['zone_id'], $post_info['ward_code'], is_scalar($post_info['address_2']) ? (string)$post_info['address_2'] : '');
		}

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$json['redirect'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
		}

		if (!$json) {
			if (!$this->validateUtf8Length((string)$post_info['firstname'], 1, 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			if (!$this->validateUtf8Length((string)$post_info['lastname'], 1, 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}
			if (!preg_match('/^0\d{9}$/', (string)$post_info['telephone'])) {
				$json['error']['telephone'] = 'Số điện thoại phải có 10 chữ số và bắt đầu bằng số 0.';
			}

			if (!$this->validateUtf8Length((string)$post_info['address_1'], 3, 128)) {
				$json['error']['address_1'] = $this->language->get('error_address_1');
			}

			// Country
			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry((int)$post_info['country_id']);

			if ($country_info && (int)$post_info['country_id'] !== 230 && $country_info['postcode_required'] && !oc_validate_length((string)$post_info['postcode'], 2, 10)) {
				$json['error']['postcode'] = $this->language->get('error_postcode');
			}

			if (!$country_info) {
				$json['error']['country'] = $this->language->get('error_country');
			}

			if ((int)$post_info['country_id'] === 230) {
				if ($post_info['hamlet_name'] !== '' && !oc_validate_length($post_info['hamlet_name'], 1, 128)) {
					$json['error']['hamlet_name'] = 'Tên Tổ dân phố, Khu phố hoặc Thôn không hợp lệ.';
				}
				if ($post_info['subhamlet_name'] !== '' && !oc_validate_length($post_info['subhamlet_name'], 1, 128)) {
					$json['error']['subhamlet_name'] = 'Tên xóm/ấp không được vượt quá 128 ký tự.';
				}
				$vn_address = $this->getVietnamAddress((int)$post_info['zone_id'], is_scalar($post_info['ward_code']) ? (string)$post_info['ward_code'] : '');
				// Ward codes are the stable identifier in the Vietnam address dataset.
				// Zone IDs can become stale when province records are refreshed/merged.
				// If the selected ward is valid but its province zone changed, use its
				// canonical province instead of rejecting an otherwise valid address.
				if (!$vn_address && is_scalar($post_info['ward_code'])) {
					$vn_address = $this->getVietnamAddressByWardCode((string)$post_info['ward_code']);
					if ($vn_address) {
						$post_info['zone_id'] = (int)$vn_address['zone_id'];
					}
				}
				if (!$vn_address) {
					if ($post_info['ward_code'] === '') {
						$json['error']['ward_code'] = $this->language->get('error_ward');
					}
					$json['error']['warning'] = 'Vui lòng chọn Tỉnh/Thành phố và Phường/Xã.';
				} else {
					$post_info['city'] = $vn_address['province_name'];
					$post_info['address_2'] = 'Phường/Xã: ' . $vn_address['ward_full_name'] . ' | Tổ dân phố/Khu phố/Thôn: ' . $post_info['hamlet_name'];
					if ($post_info['subhamlet_name'] !== '') {
						$post_info['address_2'] .= ' | Xóm/Ấp: ' . $post_info['subhamlet_name'];
					}
				}
			}

			// Validate the final province after resolving the selected ward. Doing
			// this earlier incorrectly kept a zone error even when the ward lookup
			// had already recovered the current zone mapping.
			$this->load->model('localisation/zone');
			$zone_total = $this->model_localisation_zone->getTotalZonesByCountryId((int)$post_info['country_id']);
			if ($zone_total && (int)$post_info['zone_id'] < 1) {
				$json['error']['zone'] = 'Vui lòng chọn tỉnh/thành phố.';
			}

			// Vietnamese city is derived from the validated province/ward pair above.
			if ((int)$post_info['country_id'] !== 230 && !oc_validate_length((string)$post_info['city'], 2, 128)) {
				$json['error']['city'] = $this->language->get('error_city');
			}

			// Custom fields validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($this->customer->getGroupId());

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address') {
					if ($custom_field['required'] && empty($post_info['custom_field'][$custom_field['custom_field_id']])) {
						$json['error']['custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !oc_validate_regex((string)$post_info['custom_field'][$custom_field['custom_field_id']], $custom_field['validation'])) {
						$json['error']['custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
					}
				}
			}

			if (isset($this->request->get['address_id']) && ($this->customer->getAddressId() == (int)$this->request->get['address_id']) && !(bool)$post_info['default']) {
				$json['error']['warning'] = $this->language->get('error_default');
			}
		}

		if (!$json) {
			$this->load->model('account/address');
			$this->load->model('account/customer');

			// Add Address
			if (!isset($this->request->get['address_id'])) {
				$this->model_account_address->addAddress($this->customer->getId(), $post_info);

				$this->session->data['success'] = $this->language->get('text_add');

				$json['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'], true);
			}

			// Edit Address
			if (isset($this->request->get['address_id'])) {
				$this->model_account_address->editAddress($this->customer->getId(), (int)$this->request->get['address_id'], $post_info);

				// If address is in session update it.
				if (isset($this->session->data['shipping_address']) && ($this->session->data['shipping_address']['address_id'] == (int)$this->request->get['address_id'])) {
					$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getId(), (int)$this->request->get['address_id']);

					unset($this->session->data['order_id']);
					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}

				// If address is in session update it.
				if (isset($this->session->data['payment_address']) && ($this->session->data['payment_address']['address_id'] == (int)$this->request->get['address_id'])) {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getId(), (int)$this->request->get['address_id']);

					unset($this->session->data['order_id']);
					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}

				$json['success'] = $this->language->get('text_edit');
				if (($this->request->get['return'] ?? '') === 'checkout') {
					$json['redirect'] = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
				}
			}

			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
			if ($customer_info) {
				$customer_info['telephone'] = $post_info['telephone'];
				$this->model_account_customer->editCustomer($this->customer->getId(), $customer_info);
				if (isset($this->session->data['customer'])) $this->session->data['customer']['telephone'] = $post_info['telephone'];
			}
		}

		// Local-only diagnostics contain lengths and selected IDs, never names,
		// phone numbers, street addresses, or the raw POST body.
		if (isset($json['error']) && in_array($this->request->server['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
			$json['_debug'] = [
				'post_has_zone_id' => array_key_exists('zone_id', $this->request->post),
				'zone_id_received' => is_scalar($this->request->post['zone_id'] ?? null) ? (int)$this->request->post['zone_id'] : null,
				'zone_id_resolved' => (int)$post_info['zone_id'],
				'post_has_ward_code' => array_key_exists('ward_code', $this->request->post),
				'ward_code_received' => is_scalar($this->request->post['ward_code'] ?? null) ? (string)$this->request->post['ward_code'] : null,
				'ward_code_resolved' => is_scalar($post_info['ward_code'] ?? null) ? (string)$post_info['ward_code'] : '',
				'firstname_length_utf8' => mb_strlen(trim((string)$post_info['firstname']), 'UTF-8'),
				'lastname_length_utf8' => mb_strlen(trim((string)$post_info['lastname']), 'UTF-8'),
				'address_1_length_utf8' => mb_strlen(trim((string)$post_info['address_1']), 'UTF-8'),
				'error_fields' => is_array($json['error']) ? array_keys($json['error']) : ['warning']
			];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/** Count user-entered address text as UTF-8 characters, not bytes. */
	private function validateUtf8Length(string $value, int $minimum, int $maximum): bool {
		$length = mb_strlen(trim($value), 'UTF-8');
		return $length >= $minimum && $length <= $maximum;
	}

	/** Validate that the selected current ward belongs to the chosen Vietnamese province. */
	private function getVietnamAddress(int $zoneId, string $wardCode): array {
		if ($zoneId < 1 || !preg_match('/^[A-Za-z0-9-]{1,32}$/', $wardCode)) {
			return [];
		}

		$query = $this->db->query("SELECT `p`.`name` AS `province_name`, `w`.`full_name` AS `ward_full_name` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`zone_id` = '" . $zoneId . "' AND `z`.`country_id` = '230' AND `z`.`status` = '1' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		return $query->row ?: [];
	}

	/** Resolve a current Vietnamese ward by its stable code if its zone mapping changed. */
	private function getVietnamAddressByWardCode(string $wardCode): array {
		if (!preg_match('/^[A-Za-z0-9-]{1,32}$/', $wardCode)) {
			return [];
		}

		$query = $this->db->query("SELECT `p`.`name` AS `province_name`, `w`.`full_name` AS `ward_full_name`, `p`.`zone_id` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`country_id` = '230' AND `z`.`status` = '1' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		return $query->row ?: [];
	}

	/** Resolve a valid Vietnamese zone from the selected province label when its ID is missing or stale. */
	private function resolveVietnamZoneId(int $zoneId, string $provinceName): int {
		$provinceName = trim($provinceName);
		if ($provinceName !== '') {
			$query = $this->db->query("SELECT `p`.`zone_id` FROM `" . DB_PREFIX . "vn_province` `p` INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`country_id` = '230' AND `z`.`status` = '1' AND (`p`.`name` = '" . $this->db->escape($provinceName) . "' OR `p`.`full_name` = '" . $this->db->escape($provinceName) . "') LIMIT 1");
			if ($query->num_rows) {
				return (int)$query->row['zone_id'];
			}
		}

		if ($zoneId > 0) {
			$query = $this->db->query("SELECT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE `zone_id` = '" . $zoneId . "' AND `country_id` = '230' AND `status` = '1' LIMIT 1");
			if ($query->num_rows) {
				return $zoneId;
			}
		}

		return 0;
	}

	/** Resolve the ward code from the selected ward label if the browser did not submit its option value. */
	private function resolveVietnamWardCode(int $zoneId, string $wardCode, string $address2): string {
		if ($this->getVietnamAddress($zoneId, $wardCode)) {
			return $wardCode;
		}
		// Keep a valid ward code even if the province-to-zone link has just changed;
		// getVietnamAddressByWardCode() will validate it against the live mapping.
		if (preg_match('/^[A-Za-z0-9-]{1,32}$/', $wardCode)) {
			$query = $this->db->query("SELECT `w`.`code` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`country_id` = '230' AND `z`.`status` = '1' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
			if ($query->num_rows) {
				return (string)$query->row['code'];
			}
		}

		if (!preg_match('/^\\s*[^:]+:\\s*(.*?)\\s*(?:\\|.*)?$/u', trim($address2), $matches)) {
			return '';
		}

		$wardName = trim($matches[1]);
		if ($zoneId < 1 || $wardName === '') {
			return '';
		}

		$query = $this->db->query("SELECT `w`.`code` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`zone_id` = '" . $zoneId . "' AND `z`.`country_id` = '230' AND `z`.`status` = '1' AND (`w`.`full_name` = '" . $this->db->escape($wardName) . "' OR `w`.`name` = '" . $this->db->escape($wardName) . "') LIMIT 1");
		return $query->num_rows ? (string)$query->row['code'] : '';
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('account/address');

		$json = [];

		if (isset($this->request->get['address_id'])) {
			$address_id = (int)$this->request->get['address_id'];
		} else {
			$address_id = 0;
		}

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$json['redirect'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
		}

		if (!$json) {
			$this->load->model('account/address');
			$total_addresses = $this->model_account_address->getTotalAddresses($this->customer->getId());
			$address_info = $address_id > 0 ? $this->model_account_address->getAddress($this->customer->getId(), $address_id) : [];

			if (!$address_info) {
				$json['error'] = $this->language->get('error_address_missing');
			}

			// Keep a default when other addresses exist; deleting the final address is allowed.
			if (!$json && $this->customer->getAddressId() == $address_id && $total_addresses > 1) {
				$json['error'] = $this->language->get('error_default');
			}

			// Total Subscriptions
			$this->load->model('account/subscription');

			$subscription_total = $this->model_account_subscription->getTotalSubscriptionByShippingAddressId($address_id);

			if ($subscription_total) {
				$json['error'] = sprintf($this->language->get('error_subscription'), $subscription_total);
			}

			$subscription_total = $this->model_account_subscription->getTotalSubscriptionByPaymentAddressId($address_id);

			if ($subscription_total) {
				$json['error'] = sprintf($this->language->get('error_subscription'), $subscription_total);
			}
		}

		if (!$json) {
			// Delete address from database.
			$this->model_account_address->deleteAddresses($this->customer->getId(), $address_id);

			// Delete address from session.
			if (isset($this->session->data['shipping_address']['address_id']) && ($this->session->data['shipping_address']['address_id'] == $address_id)) {
				unset($this->session->data['order_id']);
				unset($this->session->data['shipping_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}

			// Delete address from session.
			if (isset($this->session->data['payment_address']['address_id']) && ($this->session->data['payment_address']['address_id'] == $address_id)) {
				unset($this->session->data['order_id']);
				unset($this->session->data['payment_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}

			$json['success'] = $this->language->get('text_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
