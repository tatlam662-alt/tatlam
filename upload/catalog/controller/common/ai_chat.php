<?php
namespace Opencart\Catalog\Controller\Common;

/** Product-aware PanDaShop chat endpoint; replies use local rules and live catalog data. */
class AiChat extends \Opencart\System\Engine\Controller {
	public function send(): void {
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->load->language('common/ai_chat');

		$request_method = strtoupper((string)($this->request->server['REQUEST_METHOD'] ?? ''));
		if ($request_method !== 'POST') {
			$this->response->addHeader('Allow: POST');
			$this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
			$this->respond(['success' => false, 'error' => $this->language->get('error_invalid_request')]);
			return;
		}

		$message_value = $this->request->post['message'] ?? ($_POST['message'] ?? null);

		// Accept JSON clients as well as OpenCart/PHP form submissions.
		if ($message_value === null) {
			$raw_input = file_get_contents('php://input');
			if (is_string($raw_input) && $raw_input !== '') {
				$decoded = json_decode($raw_input, true);
				if (is_array($decoded) && isset($decoded['message'])) {
					$message_value = $decoded['message'];
				}
			}
		}

		$message = is_scalar($message_value) ? trim((string)$message_value) : '';
		if ($message === '') {
			$this->respond(['success' => true, 'reply' => $this->language->get('text_enter_question'), 'products' => []]);
			return;
		}

		if (oc_strlen($message) > 1000) {
			$this->response->addHeader('HTTP/1.1 400 Bad Request');
			$this->respond(['success' => false, 'error' => $this->language->get('error_message_length')]);
			return;
		}

		$now = time();
		$requests = array_values(array_filter($this->session->data['panda_ai_requests'] ?? [], static fn ($time): bool => (int)$time > $now - 60));
		if (count($requests) >= 20) {
			$this->response->addHeader('HTTP/1.1 429 Too Many Requests');
			$this->respond(['success' => false, 'error' => $this->language->get('error_rate_limit')]);
			return;
		}
		$requests[] = $now;
		$this->session->data['panda_ai_requests'] = $requests;

		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$normalized = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : oc_strtolower($message);
		$is_greeting = preg_match('/(^|[^\p{L}])(xin chào|xin chao|chào|chao|hello|hi)([^\p{L}]|$)/u', $normalized) === 1;
		$is_order_question = preg_match('/đơn hàng|tra cứu|vận chuyển|giao hàng|theo dõi đơn|order|shipping|delivery|track/u', $normalized) === 1;

		$search_text = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', ' ', $normalized) ?? $normalized;
		$search_text = preg_replace('/\s+/u', ' ', $search_text) ?? $search_text;
		$stop_words = ['xin', 'chào', 'chao', 'hello', 'hi', 'mình', 'tôi', 'em', 'bạn', 'shop', 'ơi', 'cho', 'hỏi', 'tìm', 'tư', 'vấn', 'giúp', 'muốn', 'cần', 'có', 'không', 'nào', 'loại', 'sản', 'phẩm', 'giá', 'bao', 'nhiêu', 'với', 'một', 'về', 'là', 'the', 'a', 'an', 'please', 'price'];
		$terms = array_values(array_filter(explode(' ', $search_text), static fn (string $word): bool => oc_strlen($word) >= 2 && !in_array($word, $stop_words, true)));
		$search = implode(' ', array_slice(array_unique($terms), 0, 6));
		$products = [];
		$used_featured_fallback = false;
		$show_price = !$this->config->get('config_customer_price') || $this->customer->isLogged();

		if ($search !== '' && !$is_order_question && !$is_greeting) {
			$results = [];
			$search_terms = array_slice(array_unique($terms), 0, 6);
			while ($search_terms && !$results) {
				$results = $this->model_catalog_product->getProducts([
					'filter_search' => implode(' ', $search_terms),
					'sort'          => 'pd.name',
					'order'         => 'ASC',
					'start'         => 0,
					'limit'         => 3
				]);
				array_pop($search_terms);
			}

			// Fall back to useful individual terms (e.g. "PC Gaming" => "Gaming").
			if (!$results) {
				foreach (array_reverse(array_slice(array_unique($terms), 0, 6)) as $term) {
					if (oc_strlen($term) < 3 || in_array($term, ['cấu', 'hình', 'build', 'pc'], true)) {
						continue;
					}
					$results = $this->model_catalog_product->getProducts([
						'filter_search' => $term,
						'sort'          => 'pd.name',
						'order'         => 'ASC',
						'start'         => 0,
						'limit'         => 3
					]);
					if ($results) {
						break;
					}
				}
			}
			if (!$results) {
				$results = $this->model_catalog_product->getProducts([
					'sort'  => 'p.sort_order',
					'order' => 'ASC',
					'start' => 0,
					'limit' => 3
				]);
				$used_featured_fallback = true;
			}
			$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
			$language = $this->request->get['language'] ?? $this->config->get('config_language');

			foreach ($results as $result) {
				$image_path = !empty($result['image']) ? html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8') : '';
				$image = $image_path !== '' && is_file(DIR_IMAGE . $image_path) ? $image_path : 'placeholder.png';
				$amount = !empty($result['special']) ? (float)$result['special'] : (float)$result['price'];
				$products[] = [
					'product_id' => (int)$result['product_id'],
					'name'       => html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),
					'image'      => $this->model_tool_image->resize($image, 96, 96),
					'price'      => $show_price ? $this->currency->format($this->tax->calculate($amount, $result['tax_class_id'], $this->config->get('config_tax')), $currency) : '',
					'href'       => html_entity_decode($this->url->link('product/product', 'language=' . $language . '&product_id=' . (int)$result['product_id']), ENT_QUOTES, 'UTF-8')
				];
			}
		}

		if ($is_greeting) {
			$reply = $this->language->get('text_greeting_reply');
		} elseif ($is_order_question) {
			$reply = $this->language->get('text_order_reply');
		} elseif ($used_featured_fallback) {
			$reply = $this->language->get('text_featured_reply');
		} elseif ($products) {
			$reply = sprintf($this->language->get('text_products_found'), count($products));
		} else {
			$reply = $this->language->get('text_no_products');
		}

		$this->respond(['success' => true, 'reply' => $reply, 'products' => $products]);
	}

	private function respond(array $data): void {
		$this->response->setOutput(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
	}
}
