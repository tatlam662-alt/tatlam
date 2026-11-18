<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Footer
 *
 * Can be called from $this->load->controller('common/footer');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Footer extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/footer');

		foreach (['text_footer_categories', 'text_pc_category', 'text_footer_support', 'text_footer_contact', 'text_footer_description', 'text_footer_guide', 'text_footer_terms', 'text_footer_privacy', 'text_footer_shipping', 'text_footer_returns', 'text_footer_warranty', 'text_footer_payment', 'text_footer_drone_notice', 'text_chat_channel', 'text_open_support', 'text_close_support', 'text_send_support', 'text_support_label', 'text_footer_hours', 'text_footer_hours_value', 'text_footer_chat', 'text_footer_bottom_tagline', 'text_manufacturer', 'text_account', 'text_order', 'text_wishlist', 'text_newsletter', 'text_affiliate', 'text_gdpr', 'text_contact', 'text_sitemap', 'text_special', 'text_back_to_top', 'text_open_assistant', 'text_ai_close_dialog', 'text_assistant_description', 'text_assistant_greeting', 'text_assistant_typing', 'text_ai_flycam_prompt', 'text_ai_flycam_label', 'text_ai_pc_prompt', 'text_ai_order_prompt', 'text_ai_deals_prompt', 'text_ai_chat_label', 'text_ai_suggestions', 'text_ai_message_label', 'text_ai_send_message', 'text_ai_placeholder', 'text_ai_note', 'text_ai_send_error', 'text_ai_connection_error', 'text_ai_no_reply'] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$data['home'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));
		$data['ai_chat_url'] = $this->url->link('common/ai_chat.send', $language);
		$this->load->model('catalog/category');
		$category_lookup = [];
		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$category_lookup[(int)$category['category_id']] = $category;
		}

		$data['footer_categories'] = [];
		foreach ([59, 65, 74, 71] as $category_id) {
			if (isset($category_lookup[$category_id])) {
				$data['footer_categories'][] = [
					'name' => $category_lookup[$category_id]['name'],
				'href' => $this->url->link('product/category', $language . '&path=' . $category_id)
				];
			}
		}
		$data['footer_categories'][] = ['name' => $this->language->get('text_special'), 'href' => $this->url->link('product/special', $language)];
		$data['customer_logged'] = $this->customer->isLogged();
		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		$data['store_name'] = $this->config->get('config_name');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['email'] = $this->config->get('config_email');
		$data['zalo'] = 'https://zalo.me/0359563209';

		// Optional direct support profiles can be set in store settings. Validate
		// scheme and host before rendering external links in the floating widget.
		$support_url = static function (string $url, array $allowed_hosts, string $fallback): string {
			$parts = parse_url(trim($url));
			$host = strtolower((string)($parts['host'] ?? ''));
			$allowed = isset($parts['scheme']) && strtolower($parts['scheme']) === 'https' && in_array($host, $allowed_hosts, true);

			return $allowed ? trim($url) : $fallback;
		};
		$data['facebook_support_url'] = $support_url((string)$this->config->get('config_pandashop_facebook_url'), ['facebook.com', 'www.facebook.com', 'm.me', 'messenger.com', 'www.messenger.com'], 'https://www.facebook.com/messages/');
		$data['telegram_support_url'] = $support_url((string)$this->config->get('config_pandashop_telegram_url'), ['t.me', 'telegram.me'], 'https://t.me/');

		$address = $this->config->get('config_address');

		$data['address'] = $address ? nl2br($address) : '';

		// Article
		$this->load->model('cms/article');

		$article_total = $this->model_cms_article->getTotalArticles();

		if ($article_total) {
			$data['blog'] = $this->url->link('cms/blog', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		} else {
			$data['blog'] = '';
		}

		// Information
		$data['informations'] = [];
		$information_lookup = [];

		$this->load->model('catalog/information');

		$results = $this->model_catalog_information->getInformations();

		foreach ($results as $result) {
			$information = ['href' => $this->url->link('information/information', $language . '&information_id=' . (int)$result['information_id'])] + $result;
			$data['informations'][] = $information;
			$information_lookup[(int)$result['information_id']] = $information;
		}

		$data['footer_support_links'] = [
			['title' => $this->language->get('text_footer_guide'), 'href' => $this->url->link('information/guides', $language)],
			['title' => $this->language->get('text_contact'), 'href' => $this->url->link('information/contact', $language)],
			['title' => $this->language->get('text_order'), 'href' => $this->url->link('account/order', $language . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''))]
		];

		foreach ([4, 5, 6, 3, 2, 8] as $information_id) {
			if (isset($information_lookup[$information_id])) {
				$data['footer_support_links'][] = $information_lookup[$information_id];
			}
		}

		$data['contact'] = $this->url->link('information/contact', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['return'] = $this->url->link('account/returns.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		if ($this->config->get('config_gdpr_id')) {
			$data['gdpr'] = $this->url->link('information/gdpr', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		} else {
			$data['gdpr'] = '';
		}

		$data['sitemap'] = $this->url->link('information/sitemap', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['manufacturer'] = $this->url->link('product/manufacturer', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		if ($this->config->get('config_affiliate_status')) {
			$data['affiliate'] = $this->url->link('account/affiliate', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		} else {
			$data['affiliate'] = '';
		}

		$data['special'] = $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['account'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['order'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['newsletter'] = $this->url->link('account/newsletter', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Who's Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = (!empty($this->request->server['HTTPS']) ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline(oc_get_ip(), $this->customer->getId(), $url, $referer);
		}

		$data['bootstrap'] = 'catalog/view/javascript/bootstrap/js/bootstrap.bundle.min.js';
		$data['scripts'] = $this->document->getScripts('footer');
		$data['cookie'] = $this->load->controller('common/cookie');

		return $this->load->view('common/footer', $data);
	}

}
