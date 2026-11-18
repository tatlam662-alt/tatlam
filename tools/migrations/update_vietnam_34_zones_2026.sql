-- PanDaShop: canonical Vietnamese administrative zones (34 provinces/cities).
-- Keeps existing zone_id values so address and order references remain valid.
-- Run against the OpenCart database with DB_PREFIX = oc_.
START TRANSACTION;

UPDATE `oc_vn_province`
SET `name` = CASE `code`
  WHEN '01' THEN 'Thành phố Hà Nội'
  WHEN '04' THEN 'Tỉnh Cao Bằng'
  WHEN '08' THEN 'Tỉnh Tuyên Quang'
  WHEN '11' THEN 'Tỉnh Điện Biên'
  WHEN '12' THEN 'Tỉnh Lai Châu'
  WHEN '14' THEN 'Tỉnh Sơn La'
  WHEN '15' THEN 'Tỉnh Lào Cai'
  WHEN '19' THEN 'Tỉnh Thái Nguyên'
  WHEN '20' THEN 'Tỉnh Lạng Sơn'
  WHEN '22' THEN 'Tỉnh Quảng Ninh'
  WHEN '24' THEN 'Thành phố Bắc Ninh'
  WHEN '25' THEN 'Tỉnh Phú Thọ'
  WHEN '31' THEN 'Thành phố Hải Phòng'
  WHEN '33' THEN 'Tỉnh Hưng Yên'
  WHEN '37' THEN 'Tỉnh Ninh Bình'
  WHEN '38' THEN 'Tỉnh Thanh Hóa'
  WHEN '40' THEN 'Tỉnh Nghệ An'
  WHEN '42' THEN 'Tỉnh Hà Tĩnh'
  WHEN '44' THEN 'Tỉnh Quảng Trị'
  WHEN '46' THEN 'Thành phố Huế'
  WHEN '48' THEN 'Thành phố Đà Nẵng'
  WHEN '51' THEN 'Tỉnh Quảng Ngãi'
  WHEN '52' THEN 'Tỉnh Gia Lai'
  WHEN '56' THEN 'Tỉnh Khánh Hòa'
  WHEN '66' THEN 'Tỉnh Đắk Lắk'
  WHEN '68' THEN 'Tỉnh Lâm Đồng'
  WHEN '75' THEN 'Tỉnh Đồng Nai'
  WHEN '79' THEN 'Thành phố Hồ Chí Minh'
  WHEN '80' THEN 'Tỉnh Tây Ninh'
  WHEN '82' THEN 'Tỉnh Đồng Tháp'
  WHEN '86' THEN 'Tỉnh Vĩnh Long'
  WHEN '91' THEN 'Tỉnh An Giang'
  WHEN '92' THEN 'Thành phố Cần Thơ'
  WHEN '96' THEN 'Tỉnh Cà Mau'
  ELSE `name`
END
WHERE `code` IN ('01','04','08','11','12','14','15','19','20','22','24','25','31','33','37','38','40','42','44','46','48','51','52','56','66','68','75','79','80','82','86','91','92','96');

UPDATE `oc_zone` `z`
INNER JOIN `oc_vn_province` `p` ON `p`.`zone_id` = `z`.`zone_id`
SET `z`.`code` = CASE `p`.`code`
  WHEN '01' THEN 'HN' WHEN '79' THEN 'HCM' WHEN '31' THEN 'HP' WHEN '48' THEN 'DNG'
  WHEN '92' THEN 'CTH' WHEN '46' THEN 'HUE' WHEN '24' THEN 'BNI' WHEN '91' THEN 'AGG'
  WHEN '96' THEN 'CMU' WHEN '04' THEN 'CBG' WHEN '66' THEN 'DLK' WHEN '11' THEN 'DBN'
  WHEN '75' THEN 'DNI' WHEN '82' THEN 'DTP' WHEN '52' THEN 'GLI' WHEN '42' THEN 'HTH'
  WHEN '33' THEN 'HYE' WHEN '56' THEN 'KHA' WHEN '12' THEN 'LCH' WHEN '68' THEN 'LDO'
  WHEN '20' THEN 'LSO' WHEN '15' THEN 'LCA' WHEN '40' THEN 'NAN' WHEN '37' THEN 'NBI'
  WHEN '25' THEN 'PTO' WHEN '51' THEN 'QNG' WHEN '22' THEN 'QNH' WHEN '44' THEN 'QTR'
  WHEN '14' THEN 'SLA' WHEN '80' THEN 'TNI' WHEN '19' THEN 'TNG' WHEN '38' THEN 'THA'
  WHEN '08' THEN 'TQU' WHEN '86' THEN 'VLO'
  ELSE `z`.`code`
END,
`z`.`status` = 1
WHERE `z`.`country_id` = 230;

UPDATE `oc_zone_description` `zd`
INNER JOIN `oc_vn_province` `p` ON `p`.`zone_id` = `zd`.`zone_id`
INNER JOIN `oc_zone` `z` ON `z`.`zone_id` = `p`.`zone_id`
SET `zd`.`name` = `p`.`name`
WHERE `z`.`country_id` = 230;

-- Remove obsolete Vietnamese zones only when no saved address/order references them.
DELETE `zd` FROM `oc_zone_description` `zd`
INNER JOIN `oc_zone` `z` ON `z`.`zone_id` = `zd`.`zone_id`
LEFT JOIN `oc_vn_province` `p` ON `p`.`zone_id` = `z`.`zone_id`
WHERE `z`.`country_id` = 230 AND `p`.`zone_id` IS NULL
  AND NOT EXISTS (SELECT 1 FROM `oc_address` `a` WHERE `a`.`zone_id` = `z`.`zone_id`)
  AND NOT EXISTS (SELECT 1 FROM `oc_order` `o` WHERE `o`.`payment_zone_id` = `z`.`zone_id` OR `o`.`shipping_zone_id` = `z`.`zone_id`);

DELETE `z` FROM `oc_zone` `z`
LEFT JOIN `oc_vn_province` `p` ON `p`.`zone_id` = `z`.`zone_id`
WHERE `z`.`country_id` = 230 AND `p`.`zone_id` IS NULL
  AND NOT EXISTS (SELECT 1 FROM `oc_address` `a` WHERE `a`.`zone_id` = `z`.`zone_id`)
  AND NOT EXISTS (SELECT 1 FROM `oc_order` `o` WHERE `o`.`payment_zone_id` = `z`.`zone_id` OR `o`.`shipping_zone_id` = `z`.`zone_id`);

COMMIT;

-- Expected: 34 enabled Vietnamese zones, 34 province mappings, all 3,321 wards retained.
SELECT `z`.`zone_id`, `z`.`code`, `zd`.`name`
FROM `oc_zone` `z`
INNER JOIN `oc_zone_description` `zd` ON `zd`.`zone_id` = `z`.`zone_id`
INNER JOIN `oc_vn_province` `p` ON `p`.`zone_id` = `z`.`zone_id`
WHERE `z`.`country_id` = 230 AND `zd`.`language_id` = 1
ORDER BY `zd`.`name`;
SELECT COUNT(*) AS `vietnam_zone_count` FROM `oc_zone` WHERE `country_id` = 230 AND `status` = 1;
SELECT COUNT(*) AS `province_count` FROM `oc_vn_province`;
SELECT COUNT(*) AS `ward_count` FROM `oc_vn_ward`;
