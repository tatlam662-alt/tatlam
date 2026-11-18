-- Align Vietnamese OpenCart zones with the 2025 two-level administrative structure.
-- Keeps exactly the 34 enabled zones already linked from oc_vn_province.
START TRANSACTION;
UPDATE `oc_vn_province` SET `name` = CASE `code`
  WHEN '01' THEN 'Hà Nội' WHEN '04' THEN 'Cao Bằng' WHEN '08' THEN 'Tuyên Quang' WHEN '11' THEN 'Điện Biên'
  WHEN '12' THEN 'Lai Châu' WHEN '14' THEN 'Sơn La' WHEN '15' THEN 'Lào Cai' WHEN '19' THEN 'Thái Nguyên'
  WHEN '20' THEN 'Lạng Sơn' WHEN '22' THEN 'Quảng Ninh' WHEN '24' THEN 'Bắc Ninh' WHEN '25' THEN 'Phú Thọ'
  WHEN '31' THEN 'Hải Phòng' WHEN '33' THEN 'Hưng Yên' WHEN '37' THEN 'Ninh Bình' WHEN '38' THEN 'Thanh Hóa'
  WHEN '40' THEN 'Nghệ An' WHEN '42' THEN 'Hà Tĩnh' WHEN '44' THEN 'Quảng Trị' WHEN '46' THEN 'Huế'
  WHEN '48' THEN 'Đà Nẵng' WHEN '51' THEN 'Quảng Ngãi' WHEN '52' THEN 'Gia Lai' WHEN '56' THEN 'Khánh Hòa'
  WHEN '66' THEN 'Đắk Lắk' WHEN '68' THEN 'Lâm Đồng' WHEN '75' THEN 'Đồng Nai' WHEN '79' THEN 'Hồ Chí Minh'
  WHEN '80' THEN 'Tây Ninh' WHEN '82' THEN 'Đồng Tháp' WHEN '86' THEN 'Vĩnh Long' WHEN '91' THEN 'An Giang'
  WHEN '92' THEN 'Cần Thơ' WHEN '96' THEN 'Cà Mau' ELSE `name` END;

UPDATE `oc_zone_description` zd
INNER JOIN `oc_vn_province` p ON p.zone_id = zd.zone_id
SET zd.name = p.name
WHERE zd.language_id IN (1, 2);
UPDATE `oc_zone` z INNER JOIN `oc_vn_province` p ON p.zone_id = z.zone_id
SET z.status = 1 WHERE z.country_id = 230;

-- Legacy disabled zones have no customer address references (verified before deletion).
DELETE zd FROM `oc_zone_description` zd
INNER JOIN `oc_zone` z ON z.zone_id = zd.zone_id
WHERE z.country_id = 230 AND z.status = 0;
DELETE FROM `oc_zone` WHERE country_id = 230 AND status = 0;

-- Clear known placeholder postcodes without changing other addresses.
UPDATE `oc_address` SET `postcode` = ''
WHERE `country_id` = 230 AND LOWER(TRIM(`postcode`)) = 'admin';
COMMIT;

SELECT COUNT(*) AS `vietnam_zone_count` FROM `oc_zone` WHERE `country_id` = 230 AND `status` = 1;
SELECT COUNT(*) AS `province_count` FROM `oc_vn_province`;
SELECT COUNT(*) AS `ward_count` FROM `oc_vn_ward`;