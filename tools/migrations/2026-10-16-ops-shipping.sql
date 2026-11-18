-- Local PanDaShop operational settings. No credentials are stored here.

UPDATE `oc_setting` SET `value` = 'PanDaShop' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_name';
UPDATE `oc_setting` SET `value` = REPLACE(`value`, 'NEXORA', 'PanDaShop') WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_description';
UPDATE `oc_article` SET `author` = REPLACE(`author`, 'NEXORA', 'PanDaShop');
UPDATE `oc_article_description` SET `description` = REPLACE(`description`, 'NEXORA', 'PanDaShop'), `tag` = REPLACE(`tag`, 'NEXORA', 'PanDaShop'), `meta_title` = REPLACE(`meta_title`, 'NEXORA', 'PanDaShop'), `meta_keyword` = REPLACE(`meta_keyword`, 'NEXORA', 'PanDaShop');
UPDATE `oc_banner` SET `name` = REPLACE(`name`, 'NEXORA', 'PanDaShop');
UPDATE `oc_category_description` SET `meta_title` = REPLACE(`meta_title`, 'NEXORA', 'PanDaShop'), `meta_description` = REPLACE(`meta_description`, 'NEXORA', 'PanDaShop'), `meta_keyword` = REPLACE(`meta_keyword`, 'NEXORA', 'PanDaShop');
UPDATE `oc_coupon` SET `name` = REPLACE(`name`, 'NEXORA', 'PanDaShop');
UPDATE `oc_coupon` SET `code` = 'PANDASHOP5' WHERE `code` = 'NEXORA5';
UPDATE `oc_coupon` SET `code` = 'PANDASHOP10' WHERE `code` = 'NEXORA10';
UPDATE `oc_information_description` SET `description` = REPLACE(`description`, 'NEXORA', 'PanDaShop'), `meta_title` = REPLACE(`meta_title`, 'NEXORA', 'PanDaShop'), `meta_description` = REPLACE(`meta_description`, 'NEXORA', 'PanDaShop');
UPDATE `oc_manufacturer` SET `name` = REPLACE(`name`, 'NEXORA', 'PanDaShop');
UPDATE `oc_module` SET `setting` = REPLACE(`setting`, 'NEXORA', 'PanDaShop');
UPDATE `oc_order` SET `store_name` = REPLACE(`store_name`, 'NEXORA', 'PanDaShop');
UPDATE `oc_product_attribute` SET `text` = REPLACE(`text`, 'NEXORA', 'PanDaShop');
UPDATE `oc_product_description` SET `name` = REPLACE(`name`, 'NEXORA', 'PanDaShop'), `description` = REPLACE(`description`, 'NEXORA', 'PanDaShop'), `meta_title` = REPLACE(`meta_title`, 'NEXORA', 'PanDaShop'), `meta_description` = REPLACE(`meta_description`, 'NEXORA', 'PanDaShop'), `meta_keyword` = REPLACE(`meta_keyword`, 'NEXORA', 'PanDaShop');
UPDATE `oc_topic_description` SET `name` = REPLACE(`name`, 'NEXORA', 'PanDaShop'), `meta_title` = REPLACE(`meta_title`, 'NEXORA', 'PanDaShop'), `meta_description` = REPLACE(`meta_description`, 'NEXORA', 'PanDaShop'), `meta_keyword` = REPLACE(`meta_keyword`, 'NEXORA', 'PanDaShop');
UPDATE `oc_setting` SET `value` = 'vi-vn' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_language_catalog';
UPDATE `oc_setting` SET `value` = 'VND' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_currency';
UPDATE `oc_setting` SET `value` = 'Asia/Ho_Chi_Minh' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_timezone';
UPDATE `oc_setting` SET `value` = '230' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_country_id';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_zone_id';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_maintenance';
UPDATE `oc_setting` SET `value` = '1' WHERE `store_id` = 0 AND `code` = 'config' AND `key` = 'config_seo_url';
UPDATE `oc_setting` SET `value` = '' WHERE `store_id` = 0 AND `code` = 'config' AND `key` IN ('config_telephone', 'config_address');

UPDATE `oc_order_status` SET `name` = 'Chờ xử lý' WHERE `language_id` = 2 AND `order_status_id` = 17;
UPDATE `oc_order_status` SET `name` = 'Đã xác nhận' WHERE `language_id` = 2 AND `order_status_id` = 18;
UPDATE `oc_order_status` SET `name` = 'Đang giao' WHERE `language_id` = 2 AND `order_status_id` = 19;
UPDATE `oc_order_status` SET `name` = 'Hoàn tất' WHERE `language_id` = 2 AND `order_status_id` = 20;
UPDATE `oc_order_status` SET `name` = 'Đã hủy' WHERE `language_id` = 2 AND `order_status_id` = 21;

UPDATE `oc_currency` SET `title` = 'Đồng Việt Nam', `symbol_left` = '', `symbol_right` = '₫', `decimal_place` = 0, `value` = 1.00000000, `status` = 1, `date_modified` = NOW() WHERE `code` = 'VND';

UPDATE `oc_setting` SET `value` = '30000' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_cost';
UPDATE `oc_setting` SET `value` = '1' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_status';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_geo_zone_id';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_tax_class_id';
UPDATE `oc_setting` SET `value` = '1' WHERE `store_id` = 0 AND `code` = 'shipping_flat' AND `key` = 'shipping_flat_sort_order';

INSERT INTO `oc_extension` (`extension`, `type`, `code`)
SELECT 'opencart', 'shipping', 'free'
WHERE NOT EXISTS (SELECT 1 FROM `oc_extension` WHERE `type` = 'shipping' AND `code` = 'free');
UPDATE `oc_extension` SET `extension` = 'opencart' WHERE `type` = 'shipping' AND `code` = 'free';

INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'shipping_free', 'shipping_free_status', '1', 0 WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_status');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'shipping_free', 'shipping_free_total', '5000000', 0 WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_total');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'shipping_free', 'shipping_free_geo_zone_id', '0', 0 WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_geo_zone_id');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'shipping_free', 'shipping_free_tax_class_id', '0', 0 WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_tax_class_id');
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`)
SELECT 0, 'shipping_free', 'shipping_free_sort_order', '0', 0 WHERE NOT EXISTS (SELECT 1 FROM `oc_setting` WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_sort_order');
UPDATE `oc_setting` SET `value` = '1' WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_status';
UPDATE `oc_setting` SET `value` = '5000000' WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_total';
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` IN ('shipping_free_geo_zone_id', 'shipping_free_tax_class_id');
UPDATE `oc_setting` SET `value` = '0' WHERE `store_id` = 0 AND `code` = 'shipping_free' AND `key` = 'shipping_free_sort_order';

INSERT INTO `oc_coupon` (`name`, `code`, `type`, `discount`, `logged`, `shipping`, `total`, `date_start`, `date_end`, `uses_total`, `uses_customer`, `status`, `date_added`)
SELECT 'PanDaShop giảm 10%', 'PANDASHOP10', 'P', 10, 0, 0, 500000, '2026-10-16', '2035-12-31', 0, 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `oc_coupon` WHERE `code` = 'PANDASHOP10');
UPDATE `oc_coupon` SET `name` = 'PanDaShop giảm 10%', `type` = 'P', `discount` = 10, `logged` = 0, `shipping` = 0, `total` = 500000, `date_start` = '2026-10-16', `date_end` = '2035-12-31', `uses_customer` = 1, `status` = 1 WHERE `code` = 'PANDASHOP10';

UPDATE `oc_information_description`
SET `meta_title` = 'Vận chuyển & giao hàng PanDaShop',
    `meta_description` = 'Phí giao hàng tiêu chuẩn 30.000₫; miễn phí cho đơn từ 5.000.000₫. Thời gian dự kiến 1–3 ngày nội thành, 2–5 ngày toàn quốc; vùng xa được xác nhận trước khi gửi.',
    `description` = '<p>PanDaShop xác nhận phương thức, phí và thời gian giao dự kiến trong quá trình đặt hàng.</p><ul><li>Giao hàng tiêu chuẩn: 30.000₫/đơn.</li><li>Miễn phí vận chuyển với đơn hàng từ 5.000.000₫ (tính trên tiền hàng).</li><li>Dự kiến 1–3 ngày làm việc tại nội thành và 2–5 ngày làm việc tại các tỉnh thành.</li><li>Địa chỉ vùng xa, hải đảo hoặc kiện hàng đặc thù có thể cần xác nhận lại phí trước khi gửi.</li><li>Thời gian thực tế phụ thuộc đơn vị vận chuyển, thời điểm đặt hàng và điều kiện giao nhận.</li></ul>'
WHERE `information_id` = 4 AND `language_id` = 2;
