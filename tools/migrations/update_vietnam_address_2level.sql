-- PanDaShop: retain all 34 provinces and 3,321 ward rows, remove obsolete pre-2025 district hierarchy.
-- The previous district data and schema are preserved under .backups/vn-districts-20261018.
ALTER TABLE `oc_vn_ward`
  DROP FOREIGN KEY `fk_vn_ward_district`,
  DROP KEY `idx_vn_ward_district`,
  DROP COLUMN `district_code`;

DROP TABLE `oc_vn_district`;

-- Sanity checks (expected province count 34; ward count 3,321; orphan count 0).
SELECT COUNT(*) AS `province_count` FROM `oc_vn_province`;
SELECT COUNT(*) AS `ward_count` FROM `oc_vn_ward`;
SELECT COUNT(*) AS `orphan_ward_count`
FROM `oc_vn_ward` `w`
LEFT JOIN `oc_vn_province` `p` ON `p`.`code` = `w`.`province_code`
WHERE `p`.`code` IS NULL;