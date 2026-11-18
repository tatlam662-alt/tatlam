<?php
namespace Opencart\Catalog\Controller\Account;

class Reward extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('account/reward');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/reward', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
			return;
		}

		$data['checkin_auto_open'] = !empty($this->session->data['pandashop_checkin_prompt']);
		unset($this->session->data['pandashop_checkin_prompt']);
		$data['checkin_global'] = false;
		foreach ([
			'text_checkin_reward',
			'text_checkin_title',
			'text_checkin_description',
			'text_checkin_button',
			'text_checkin_done',
			'text_checkin_modal_kicker',
			'text_checkin_modal_title',
			'text_checkin_open',
			'text_checkin_close',
			'text_checkin_week_title',
			'text_checkin_week_rule',
			'text_checkin_day_done',
			'text_checkin_day_today',
			'text_checkin_day_upcoming',
			'text_checkin_day_missed',
			'text_checkin_cycle_points',
			'text_checkin_cycle_bonus',
			'error_checkin_save'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$language = $this->request->get['language'] ?? $this->config->get('config_language');
		$customer_id = $this->customer->getId();
		$limit = 10;
		$page = max(1, (int)($this->request->get['page'] ?? 1));

		$this->load->model('account/reward');
		$reward_total = $this->model_account_reward->getTotalRewards($customer_id);
		$page_total = max(1, (int)ceil($reward_total / $limit));
		$page = min($page, $page_total);

		$filter_data = [
			'sort'  => 'date_added',
			'order' => 'DESC',
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];

		$data['rewards'] = [];
		foreach ($this->model_account_reward->getRewards($customer_id, $filter_data) as $result) {
			$points = (int)$result['points'];
			$order_id = (int)$result['order_id'];
			$description = (string)$result['description'];

			if (str_starts_with($description, '[PANDASHOP:DAILY_CHECKIN] ')) {
				$description = substr($description, strlen('[PANDASHOP:DAILY_CHECKIN] '));
				$description = preg_replace('/^\[PANDASHOP:STREAK:\d+\]\s*/', '', $description);
			}

			$data['rewards'][] = [
				'order_id'    => $order_id,
				'description' => $description,
				'points'      => ($points > 0 ? '+' : '') . number_format($points, 0, ',', '.'),
				'is_positive' => $points > 0,
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'        => $order_id ? $this->url->link('account/order.info', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $order_id) : ''
			];
		}

		$total_points = (int)$this->customer->getRewardPoints();

		$data['total_points'] = number_format($total_points, 0, ',', '.');
		$data['equivalent_money'] = number_format($total_points * 1000, 0, ',', '.') . ' ₫';
		$data['conversion_rate'] = $this->language->get('text_conversion_rate');
		$data['customer_token'] = $this->session->data['customer_token'];
		$data['checked_in_today'] = $this->model_account_reward->hasDailyCheckin($customer_id);
		require_once DIR_SYSTEM . 'library/pandashop/reward.php';
		$weekly_calendar = $this->model_account_reward->getWeeklyCheckinCalendar($customer_id);
		$data['checkin_week_day'] = $weekly_calendar['current_day'];
		$data['checkin_checked_days'] = $weekly_calendar['checked_days'];
		$data['checkin_streak'] = $this->model_account_reward->getDailyCheckinStreak($customer_id);
		$data['checkin_streak_progress'] = $data['checkin_streak'] > 0
			? \Opencart\System\Library\PanDaShop\Reward::getCheckinCycleDay($data['checkin_streak'])
			: 0;
		$data['checkin_expected_day'] = $data['checked_in_today']
			? max(1, $data['checkin_streak_progress'])
			: \Opencart\System\Library\PanDaShop\Reward::getCheckinCycleDay($data['checkin_streak'] + 1);
		$data['checkin_next_streak_day'] = \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS;
		$data['text_checkin_streak_label'] = $this->language->get('text_checkin_weekly_progress');
		$data['checkin_streak_text'] = sprintf($this->language->get('text_checkin_streak_progress_short'), $data['checkin_streak_progress'], $data['checkin_next_streak_day']);
		$data['checkin_streak_percent'] = (int)round($data['checkin_streak_progress'] / $data['checkin_next_streak_day'] * 100);
		$data['text_checkin_reward'] = $data['checked_in_today']
			? $this->language->get('text_checkin_done')
			: sprintf(
				$this->language->get('text_checkin_reward'),
				$data['checkin_expected_day'] + \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($data['checkin_expected_day'])
			);
		$data['checkin_week_rewards'] = [];
		$week_chain = 0;
		$weekday_names = [
			1 => $this->language->get('text_checkin_weekday_1'),
			2 => $this->language->get('text_checkin_weekday_2'),
			3 => $this->language->get('text_checkin_weekday_3'),
			4 => $this->language->get('text_checkin_weekday_4'),
			5 => $this->language->get('text_checkin_weekday_5'),
			6 => $this->language->get('text_checkin_weekday_6'),
			7 => $this->language->get('text_checkin_weekday_7')
		];

		for ($day = 1; $day <= 7; $day++) {
			$is_checked = in_array($day, $weekly_calendar['checked_days'], true);
			$is_today = $day === $data['checkin_week_day'];
			$is_missed = $day < $data['checkin_week_day'] && !$is_checked;
			if ($day < $data['checkin_week_day'] && $is_checked) {
				$week_chain = $day > 1 && in_array($day - 1, $weekly_calendar['checked_days'], true)
					? min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS)
					: 1;
			} elseif ($day < $data['checkin_week_day']) {
				$week_chain = 0;
			} elseif ($day === $data['checkin_week_day']) {
				$week_chain = $day > 1 && in_array($day - 1, $weekly_calendar['checked_days'], true)
					? min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS)
					: 1;
			} else {
				$week_chain = min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS);
			}

			$bonus = \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($week_chain);
			$day_status = $is_checked
				? $this->language->get('text_checkin_day_done')
				: ($is_today
					? $this->language->get('text_checkin_day_today')
					: ($is_missed ? $this->language->get('text_checkin_day_missed') : $this->language->get('text_checkin_day_upcoming')));

			$data['checkin_week_rewards'][] = [
				'number' => $day,
				'day' => $weekday_names[$day],
				'points' => $is_missed
					? ''
					: sprintf($this->language->get('text_checkin_cycle_points'), \Opencart\System\Library\PanDaShop\Reward::getCheckinBasePoints($week_chain)),
				'bonus' => $bonus ? sprintf($this->language->get('text_checkin_cycle_bonus'), $bonus) : '',
				'status' => $day_status,
				'is_checked' => $is_checked,
				'is_today' => $is_today,
				'is_missed' => $is_missed
			];
		}

		$data['checkin_action'] = $this->url->link('account/reward.dailyCheckin', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'], true);
		$data['text_checkin_button'] = $data['checked_in_today']
			? $this->language->get('text_checkin_done')
			: sprintf(
				$this->language->get('text_checkin_button'),
				$data['checkin_expected_day'] + \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($data['checkin_expected_day'])
			);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $language)
			],
			[
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'])
			],
			[
				'text' => $this->language->get('text_reward'),
				'href' => $this->url->link('account/reward', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'])
			]
		];

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $reward_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/reward', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'] . '&page={page}')
		]);

		$start = $reward_total ? (($page - 1) * $limit) + 1 : 0;
		$end = min($page * $limit, $reward_total);
		$data['results'] = sprintf($this->language->get('text_results'), $start, $end, $reward_total, $page_total);
		$data['reward_count_label'] = sprintf($this->language->get('text_count'), $reward_total);
		$data['text_points'] = $this->language->get('text_points');
		$data['continue'] = $this->url->link('account/account', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token']);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['checkin_modal'] = $this->load->view('account/checkin_modal', $data);
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('account/reward', $data));
	}

	/**
	 * Render the weekly check-in calendar after a successful login.
	 *
	 * @return string
	 */
	public function loginPrompt(): string {
		if (!$this->customer->isLogged()) {
			return '';
		}

		$this->load->language('account/reward');
		$this->load->model('account/reward');
		require_once DIR_SYSTEM . 'library/pandashop/reward.php';

		foreach ([
			'text_checkin_description',
			'text_checkin_button',
			'text_checkin_done',
			'text_checkin_modal_kicker',
			'text_checkin_modal_title',
			'text_checkin_close',
			'text_checkin_week_title',
			'text_checkin_week_rule',
			'text_checkin_day_done',
			'text_checkin_day_today',
			'text_checkin_day_upcoming',
			'text_checkin_day_missed',
			'text_checkin_cycle_points',
			'text_checkin_cycle_bonus',
			'error_checkin_save'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$language = $this->request->get['language'] ?? $this->config->get('config_language');
		$customer_id = $this->customer->getId();
		$weekly_calendar = $this->model_account_reward->getWeeklyCheckinCalendar($customer_id);
		$data['checkin_checked_days'] = $weekly_calendar['checked_days'];
		$data['checkin_week_day'] = $weekly_calendar['current_day'];
		$data['checked_in_today'] = $this->model_account_reward->hasDailyCheckin($customer_id);
		$data['checkin_streak'] = $this->model_account_reward->getDailyCheckinStreak($customer_id);
		$data['checkin_streak_progress'] = $data['checkin_streak'] > 0
			? \Opencart\System\Library\PanDaShop\Reward::getCheckinCycleDay($data['checkin_streak'])
			: 0;
		$data['checkin_expected_day'] = $data['checked_in_today']
			? max(1, $data['checkin_streak_progress'])
			: \Opencart\System\Library\PanDaShop\Reward::getCheckinCycleDay($data['checkin_streak'] + 1);
		$data['checkin_next_streak_day'] = \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS;
		$data['text_checkin_streak_label'] = $this->language->get('text_checkin_weekly_progress');
		$data['checkin_streak_text'] = sprintf($this->language->get('text_checkin_streak_progress_short'), $data['checkin_streak_progress'], $data['checkin_next_streak_day']);
		$data['checkin_streak_percent'] = (int)round($data['checkin_streak_progress'] / $data['checkin_next_streak_day'] * 100);
		$data['checkin_week_rewards'] = [];
		$week_chain = 0;
		$weekday_names = [
			1 => $this->language->get('text_checkin_weekday_1'),
			2 => $this->language->get('text_checkin_weekday_2'),
			3 => $this->language->get('text_checkin_weekday_3'),
			4 => $this->language->get('text_checkin_weekday_4'),
			5 => $this->language->get('text_checkin_weekday_5'),
			6 => $this->language->get('text_checkin_weekday_6'),
			7 => $this->language->get('text_checkin_weekday_7')
		];

		for ($day = 1; $day <= 7; $day++) {
			$is_checked = in_array($day, $weekly_calendar['checked_days'], true);
			$is_today = $day === $data['checkin_week_day'];
			$is_missed = $day < $data['checkin_week_day'] && !$is_checked;

			if ($day < $data['checkin_week_day'] && $is_checked) {
				$week_chain = $day > 1 && in_array($day - 1, $weekly_calendar['checked_days'], true)
					? min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS)
					: 1;
			} elseif ($day < $data['checkin_week_day']) {
				$week_chain = 0;
			} elseif ($day === $data['checkin_week_day']) {
				$week_chain = $day > 1 && in_array($day - 1, $weekly_calendar['checked_days'], true)
					? min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS)
					: 1;
			} else {
				$week_chain = min($week_chain + 1, \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS);
			}

			$bonus = \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($week_chain);
			$day_status = $is_checked
				? $this->language->get('text_checkin_day_done')
				: ($is_today
					? $this->language->get('text_checkin_day_today')
					: ($is_missed ? $this->language->get('text_checkin_day_missed') : $this->language->get('text_checkin_day_upcoming')));

			$data['checkin_week_rewards'][] = [
				'number' => $day,
				'day' => $weekday_names[$day],
				'points' => $is_missed
					? ''
					: sprintf($this->language->get('text_checkin_cycle_points'), \Opencart\System\Library\PanDaShop\Reward::getCheckinBasePoints($week_chain)),
				'bonus' => $bonus ? sprintf($this->language->get('text_checkin_cycle_bonus'), $bonus) : '',
				'status' => $day_status,
				'is_checked' => $is_checked,
				'is_today' => $is_today,
				'is_missed' => $is_missed
			];
		}

		$data['checkin_action'] = $this->url->link('account/reward.dailyCheckin', 'language=' . $language . '&customer_token=' . $this->session->data['customer_token'], true);
		$data['text_checkin_button'] = $data['checked_in_today']
			? $this->language->get('text_checkin_done')
			: sprintf(
				$this->language->get('text_checkin_button'),
				$data['checkin_expected_day'] + \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($data['checkin_expected_day'])
			);
		$data['checkin_auto_open'] = true;
		$data['checkin_global'] = true;
		$data['text_points'] = $this->language->get('text_points');
		$data['error_checkin_save'] = $this->language->get('error_checkin_save');
		$data['checkin_reward_total'] = '';
		$data['checkin_reward_money'] = '';

		return $this->load->view('account/checkin_modal', $data);
	}

	/**
	 * Claim the customer's daily PanDaCoins reward.
	 */
	public function dailyCheckin(): void {
		$this->load->language('account/reward');

		$json = [];
		$status = 200;

		if (!$this->load->controller('account/login.validate')) {
			$status = 401;
			$json['error'] = $this->language->get('error_checkin_login');
		} elseif (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$status = 405;
			$json['error'] = $this->language->get('error_checkin_method');
		} else {
			$this->load->model('account/reward');
			$customer_id = $this->customer->getId();
			require_once DIR_SYSTEM . 'library/pandashop/reward.php';
			$claim = $this->model_account_reward->claimDailyCheckin($customer_id);

			if (is_array($claim)) {
				$total_points = (int)$this->customer->getRewardPoints();
				$json['success'] = sprintf($this->language->get('text_checkin_success'), $claim['base_points']);
				$json['success'] .= $claim['bonus']
					? ' ' . sprintf($this->language->get('text_checkin_streak_success'), $claim['cycle_day'], $claim['bonus'])
					: ' ' . sprintf($this->language->get('text_checkin_streak_progress'), $claim['cycle_day'], \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS);
				$json['checked_in'] = true;
				$json['points_awarded'] = $claim['points'];
				$json['streak'] = $claim['streak'];
				$json['streak_bonus'] = $claim['bonus'];
				$json['week_day'] = $claim['week_day'];
				$json['day_done_text'] = $this->language->get('text_checkin_day_done');
				$json['done_text'] = $this->language->get('text_checkin_done');
				$streak_progress = $claim['cycle_day'];
				$json['streak_progress_text'] = sprintf(
					$this->language->get('text_checkin_streak_progress_short'),
					$streak_progress,
					\Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS
				);
				$json['streak_progress_percent'] = (int)round($streak_progress / \Opencart\System\Library\PanDaShop\Reward::CHECKIN_STREAK_DAYS * 100);
				$json['total_points'] = number_format($total_points, 0, ',', '.');
				$json['equivalent_money'] = number_format($total_points * 1000, 0, ',', '.') . ' ₫';
			} elseif ($claim === false) {
				$status = 409;
				$json['error'] = $this->language->get('error_checkin_done');
			} else {
				$status = 500;
				$json['error'] = $this->language->get('error_checkin_save');
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate');
		$this->response->addHeader('HTTP/1.1 ' . $status . ' ' . ($status === 200 ? 'OK' : ($status === 401 ? 'Unauthorized' : ($status === 405 ? 'Method Not Allowed' : ($status === 409 ? 'Conflict' : 'Internal Server Error')))));
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
	}
}
