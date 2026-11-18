<?php
/**
 * Import current province/ward names from the official NSO administrative
 * directory web service. CLI only; fetches/validates the complete snapshot
 * before replacing ward rows. Does not touch customer/order data or hamlets.
 *
 * Usage: php tools/vietnam-address/import_official_gso_2026.php [--date=dd/mm/yyyy]
 */
require_once __DIR__ . '/cli-bootstrap.php';

if (!extension_loaded('curl') || !extension_loaded('SimpleXML')) {
	fwrite(STDERR, "PHP extensions curl and SimpleXML are required.\n");
	exit(1);
}

$date = date('d/m/Y');
foreach ($argv as $argument) {
	if (str_starts_with($argument, '--date=')) {
		$date = substr($argument, 7);
	}
}
if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
	fwrite(STDERR, "Date must use dd/mm/yyyy.\n");
	exit(1);
}

const NSO_SERVICE = 'https://danhmuchanhchinh.nso.gov.vn/DMDVHC.asmx';

function nsoCall(string $method, array $parameters): string {
	$fields = '';
	foreach ($parameters as $key => $value) {
		$fields .= '<' . $key . '>' . htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $key . '>';
	}
	$body = '<?xml version="1.0" encoding="utf-8"?>'
		. '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
		. '<soap:Body><' . $method . ' xmlns="http://tempuri.org/">' . $fields . '</' . $method . '></soap:Body></soap:Envelope>';
	$curl = curl_init(NSO_SERVICE);
	curl_setopt_array($curl, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $body,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 15,
		CURLOPT_TIMEOUT => 60,
		CURLOPT_HTTPHEADER => [
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "http://tempuri.org/' . $method . '"',
			'Accept: text/xml'
		]
	]);
	$response = curl_exec($curl);
	$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
	$error = curl_error($curl);
	curl_close($curl);
	if (!is_string($response) || $status < 200 || $status >= 300) {
		throw new RuntimeException($method . ' request failed (HTTP ' . $status . '): ' . $error);
	}
	// The NSO ASMX service wraps its DataSet (schema + diffgram) in a SOAP
	// result node. Extract only the diffgram so inline XSD metadata is not
	// mistaken for address rows by the XML parser.
	if (preg_match('~<((?:[A-Za-z_][\w.-]*:)?diffgram)\b[^>]*>.*?</\1>~s', $response, $matches)) {
		return $matches[0];
	}
	libxml_use_internal_errors(true);
	libxml_clear_errors();
	$soap = simplexml_load_string($response);
	if ($soap) {
		$fault = $soap->xpath('//*[local-name()="faultstring"]');
		if ($fault && isset($fault[0])) throw new RuntimeException($method . ' SOAP fault: ' . (string)$fault[0]);
	}
	$preview = substr(preg_replace('/\s+/', ' ', $response) ?? '', 0, 240);
	throw new RuntimeException($method . ' response did not contain an NSO DataSet diffgram (HTTP ' . $status . ', ' . $contentType . '). Response: ' . $preview);
}

function datasetRows(string $xml, string $kind): array {
	$document = simplexml_load_string($xml);
	if (!$document) {
		throw new RuntimeException('NSO ' . $kind . ' result is not a recognized XML dataset.');
	}
	$nodes = $document->xpath('//*[translate(local-name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="table"] | /*[local-name()="NewDataSet"]/*');
	$rows = [];
	foreach ($nodes ?: [] as $node) {
		$values = [];
		foreach ($node->children() as $key => $value) {
			$values[strtolower((string)$key)] = trim((string)$value);
		}
		if (!$values) continue;
		$pick = static function (array $keys) use ($values): string {
			foreach ($keys as $key) {
				if (isset($values[strtolower($key)]) && $values[strtolower($key)] !== '') return $values[strtolower($key)];
			}
			return '';
		};
		$code = $kind === 'province' ? $pick(['Ma', 'MaTP', 'MaTinh']) : $pick(['Ma', 'MaPX', 'MaXa', 'MaPhuongXa']);
		$name = $kind === 'province' ? $pick(['Ten', 'TenTP', 'TenTinh']) : $pick(['Ten', 'TenPX', 'TenXa', 'TenPhuongXa']);
		if ($code !== '' && $name !== '') {
			$rows[$code] = ['code' => $code, 'name' => $name];
		}
	}
	if (!$rows) {
		throw new RuntimeException('NSO ' . $kind . ' dataset contains no recognized code/name fields. Sample: ' . substr(preg_replace('/\s+/', ' ', $xml) ?? '', 0, 500));
	}
	return array_values($rows);
}

try {
	$provinces = datasetRows(nsoCall('DanhMucTinh', ['DenNgay' => $date]), 'province');
	if (count($provinces) !== 34) {
		throw new RuntimeException('Expected 34 current provinces/cities from NSO; received ' . count($provinces) . '. No database changes were made.');
	}

	$wards = [];
	foreach ($provinces as $province) {
		$result = nsoCall('DanhMucPhuongXa', [
			'DenNgay' => $date,
			'Tinh' => $province['code'],
			'TenTinh' => $province['name'],
			'QuanHuyen' => '',
			'TenQuanHuyen' => ''
		]);
		$provinceWards = datasetRows($result, 'ward');
		if (!$provinceWards) {
			throw new RuntimeException('No ward records returned for province ' . $province['name'] . '. No database changes were made.');
		}
		foreach ($provinceWards as $ward) {
			$ward['province_code'] = $province['code'];
			$wards[$ward['code']] = $ward;
		}
	}
	if (count($wards) < 2000) {
		throw new RuntimeException('NSO returned an incomplete ward dataset (' . count($wards) . ' rows). No database changes were made.');
	}

	$db = panda_tools_database();
	$prefix = DB_PREFIX;
	$zoneMap = [];
	$zoneResult = $db->query("SELECT `code`, `zone_id` FROM `{$prefix}vn_province`");
	while ($row = $zoneResult->fetch_assoc()) $zoneMap[(string)$row['code']] = (int)$row['zone_id'];
	foreach ($provinces as $province) {
		if (empty($zoneMap[$province['code']])) {
			throw new RuntimeException('Province code ' . $province['code'] . ' has no existing OpenCart zone mapping; import aborted without changes.');
		}
	}

	$db->begin_transaction();
	foreach ($provinces as $index => $province) {
		$code = $db->real_escape_string($province['code']);
		$name = $db->real_escape_string($province['name']);
		$zoneId = $zoneMap[$province['code']];
		if (!$db->query("UPDATE `{$prefix}vn_province` SET `name` = '{$name}', `full_name` = '{$name}', `sort_order` = " . ($index + 1) . " WHERE `code` = '{$code}' AND `zone_id` = {$zoneId}")) throw new RuntimeException($db->error);
	}
	if (!$db->query("DELETE FROM `{$prefix}vn_ward`")) throw new RuntimeException($db->error);
	$insert = $db->prepare("INSERT INTO `{$prefix}vn_ward` (`code`, `province_code`, `name`, `full_name`, `sort_order`) VALUES (?, ?, ?, ?, ?)");
	$index = 0;
	foreach ($wards as $ward) {
		$name = $ward['name'];
		$fullName = $name;
		$sort = ++$index;
		$insert->bind_param('ssssi', $ward['code'], $ward['province_code'], $name, $fullName, $sort);
		if (!$insert->execute()) throw new RuntimeException($insert->error);
	}
	$insert->close();
	$db->query("UPDATE `{$prefix}zone_description` `zd` INNER JOIN `{$prefix}vn_province` `p` ON (`p`.`zone_id` = `zd`.`zone_id`) SET `zd`.`name` = `p`.`name`");
	$db->commit();
	echo 'Imported ' . count($provinces) . ' NSO provinces/cities and ' . count($wards) . ' wards for ' . $date . '.' . PHP_EOL;
	echo 'Customer contributed hamlet names were not modified.' . PHP_EOL;
} catch (Throwable $error) {
	if (isset($db) && $db instanceof mysqli) $db->rollback();
	fwrite(STDERR, 'Import aborted: ' . $error->getMessage() . PHP_EOL);
	exit(1);
}
