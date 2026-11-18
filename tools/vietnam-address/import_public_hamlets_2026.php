<?php
/** Import named locality lists published by Vietnamese local authorities. */
require_once __DIR__ . '/cli-bootstrap.php';

$db = panda_tools_database();

$table = DB_PREFIX . 'vn_hamlet';
$wardTable = DB_PREFIX . 'vn_ward';
$columns = $db->query("SHOW COLUMNS FROM `{$table}`");
$columnNames = [];
while ($columns && ($column = $columns->fetch_assoc())) $columnNames[$column['Field']] = true;
if (!isset($columnNames['source_name'], $columnNames['source_url'])) {
		fwrite(STDERR, 'Run tools/vietnam-address/sync_vietnam_locations_2026.php first to prepare source metadata columns.' . PHP_EOL);
	exit(1);
}

$datasets = [
	[
		'ward' => 'Xã Đông Trà Bồng',
		'category' => 'hamlet',
		'source_name' => 'UBND xã Đông Trà Bồng - Công văn 504/UBND-VX ngày 08/07/2026',
		'source_url' => 'https://dongtrabong.quangngai.gov.vn/upload/2007036/20260709/kem%20theo%20CV%20504%20Danh%20sach%20thon%2C%20TDP%20sau%20khi%20thuc%20hien%20sap%20xep%20to%20chuc%20lai%20%28sau%2030.6.2026%29.pdf.pdf',
		'names' => [
			'Thôn Bình Đông', 'Thôn Bình Trung', 'Thôn Bình Tân', 'Thôn Bình Thanh',
			'Thôn Phú Tài', 'Thôn Phú Hòa', 'Thôn An Long', 'Thôn Trà Giang'
		]
	],
	[
		'ward' => 'Xã Khánh Thiện',
		'category' => 'subhamlet',
		'source_name' => 'Đảng ủy xã Khánh Thiện - Đề án nhân sự HĐND xã nhiệm kỳ 2026-2031',
		'source_url' => 'https://khanhthien.ninhbinh.gov.vn/uploads/images/A42_74-VBNB_2025-2030-DA-0003-2026_dadongdau.pdf',
		'names' => [
			'Xóm Phong An', 'Xóm Bến Xanh', 'Xóm Chợ Xanh', 'Xóm Tây Phú', 'Xóm Phú Hậu', 'Xóm Cầu',
			'Xóm 2', 'Xóm 4', 'Xóm 5', 'Xóm 6', 'Xóm 8', 'Xóm 9', 'Xóm 10', 'Xóm 11', 'Xóm 12', 'Xóm 3', 'Xóm 7',
			'Xóm Thượng 1', 'Xóm Thượng 2', 'Xóm Đồng', 'Xóm Tân 2', 'Xóm Tân 1', 'Xóm Nội',
			'Xóm Tiên Yên 1', 'Xóm Tiên Yên 2', 'Xóm Trung', 'Xóm Cống', 'Xóm Bắc', 'Xóm Ba Hàng',
			'Xóm 1 Nam Cường', 'Xóm 2 Nam Cường', 'Xóm 3 Nam Cường', 'Xóm 4 Nam Cường', 'Xóm 5 Nam Cường',
			'Xóm 6 Nam Cường', 'Xóm 7 Nam Cường', 'Xóm 8 Nam Cường', 'Xóm 9 Nam Cường', 'Xóm 10 Nam Cường',
			'Xóm 1 Đông Cường', 'Xóm 2 Đông Cường', 'Xóm 3 Đông Cường', 'Xóm 4 Đông Cường', 'Xóm 5 Đông Cường',
			'Xóm 6 Đông Cường', 'Xóm 7 Đông Cường', 'Xóm 8 Đông Cường', 'Xóm 9 Đông Cường', 'Xóm 10 Đông Cường', 'Xóm 11 Đông Cường'
		]
	]
];

$count = 0;
foreach ($datasets as $dataset) {
	$wardName = $db->real_escape_string($dataset['ward']);
	$ward = $db->query("SELECT `code` FROM `{$wardTable}` WHERE `name` = '{$wardName}' OR `full_name` = '{$wardName}' LIMIT 2");
	if (!$ward || $ward->num_rows !== 1) {
		fwrite(STDERR, 'Expected exactly one current ward named ' . $dataset['ward'] . '. Skipping this list.' . PHP_EOL);
		continue;
	}
	$wardCode = (string)$ward->fetch_assoc()['code'];
	$insert = $db->prepare("INSERT INTO `{$table}` (`ward_code`, `category`, `name`, `status`, `is_verified`, `source_name`, `source_url`) VALUES (?, ?, ?, 1, 1, ?, ?) ON DUPLICATE KEY UPDATE `status` = 1, `is_verified` = 1, `source_name` = VALUES(`source_name`), `source_url` = VALUES(`source_url`)");
	if (!$insert) {
		fwrite(STDERR, 'Could not prepare locality import: ' . $db->error . PHP_EOL);
		exit(1);
	}
	foreach ($dataset['names'] as $name) {
		$insert->bind_param('sssss', $wardCode, $dataset['category'], $name, $dataset['source_name'], $dataset['source_url']);
		if (!$insert->execute()) {
			fwrite(STDERR, 'Could not import ' . $name . ': ' . $insert->error . PHP_EOL);
			exit(1);
		}
		$count++;
	}
	echo 'Imported/updated ' . count($dataset['names']) . ' ' . $dataset['category'] . ' suggestions for ' . $dataset['ward'] . ' (ward code ' . $wardCode . ').' . PHP_EOL;
}

echo 'Total imported/updated: ' . $count . ' locality suggestions from official local-government pages.' . PHP_EOL;
