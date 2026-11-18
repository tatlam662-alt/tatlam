<?php

// Manufacturer-checked details for products whose catalog attributes are incomplete.
// Keep these values grouped by product model so facts never leak between products.
return [
	'FLY-DJI-01' => [
		'source' => 'https://www.dji.com/global/mini-4-pro/specs',
		'compatible_accessories' => ['ACC-DJI-01'],
		'highlights' => [
			'Trọng lượng tiêu chuẩn dưới 249 g với pin Intelligent Flight Battery; pin Plus làm máy bay nặng hơn 249 g.',
			'Camera CMOS 1/1,3 inch, độ phân giải hiệu dụng 48 MP.',
			'Thời lượng bay công bố tối đa 34 phút với pin tiêu chuẩn trong điều kiện thử nghiệm của DJI.',
		],
		'specs' => [['group' => 'Flycam DJI Mini 4 Pro', 'attributes' => [
			['name' => 'Khối lượng cất cánh', 'text' => 'Dưới 249 g với pin Intelligent Flight Battery tiêu chuẩn; pin Plus làm khối lượng vượt 249 g'],
			['name' => 'Cảm biến camera', 'text' => 'CMOS 1/1,3 inch, hiệu dụng 48 MP'],
			['name' => 'Thời lượng bay tối đa', 'text' => '34 phút với pin tiêu chuẩn; 45 phút với pin Plus theo điều kiện thử nghiệm của DJI'],
			['name' => 'Video', 'text' => 'Hỗ trợ quay tối đa 4K/100 fps theo chế độ được hỗ trợ'],
			['name' => 'Lưu ý', 'text' => 'Thời lượng thực tế và quy định bay phụ thuộc điều kiện sử dụng, pin lắp và quy định địa phương'],
		]]],
	],
	'DJI-AIR3S' => [
		'source' => 'https://www.dji.com/global/air-3s/specs',
		'highlights' => [
			'Hệ thống camera kép gồm camera góc rộng CMOS 1 inch 50 MP và camera tele trung bình CMOS 1/1,3 inch 48 MP.',
			'Thời lượng bay công bố tối đa 45 phút theo điều kiện thử nghiệm của DJI.',
			'Bộ nhớ trong 42 GB; video 4K đến 120 fps ở chế độ được hỗ trợ.',
		],
		'specs' => [['group' => 'Flycam DJI Air 3S', 'attributes' => [
			['name' => 'Khối lượng cất cánh', 'text' => '724 g'],
			['name' => 'Camera góc rộng', 'text' => 'CMOS 1 inch, hiệu dụng 50 MP; tiêu cự tương đương 24 mm'],
			['name' => 'Camera tele trung bình', 'text' => 'CMOS 1/1,3 inch, hiệu dụng 48 MP; tiêu cự tương đương 70 mm'],
			['name' => 'Thời lượng bay tối đa', 'text' => '45 phút theo điều kiện thử nghiệm của DJI'],
			['name' => 'Bộ nhớ trong', 'text' => '42 GB'],
			['name' => 'Video', 'text' => '4K đến 120 fps ở chế độ được hỗ trợ'],
		]]],
	],
	'FLY-DJI-03' => [
		'source' => 'https://www.dji.com/global/avata-2/specs',
		'highlights' => [
			'Camera FPV góc rộng 155° với cảm biến CMOS 1/1,3 inch.',
			'Hỗ trợ video 4K đến 100 fps ở chế độ được hỗ trợ cùng RockSteady 3.0+ và HorizonSteady.',
			'Tối đa khoảng 23 phút bay theo điều kiện thử nghiệm của DJI.',
		],
		'specs' => [['group' => 'Flycam DJI Avata 2', 'attributes' => [
			['name' => 'Khối lượng cất cánh', 'text' => 'Khoảng 377 g'],
			['name' => 'Cảm biến camera', 'text' => 'CMOS 1/1,3 inch, hiệu dụng 12 MP'],
			['name' => 'Góc nhìn camera', 'text' => '155°'],
			['name' => 'Video', 'text' => '4K đến 100 fps ở chế độ được hỗ trợ'],
			['name' => 'Chống rung', 'text' => 'RockSteady 3.0+ và HorizonSteady'],
			['name' => 'Thời lượng bay tối đa', 'text' => 'Khoảng 23 phút theo điều kiện thử nghiệm của DJI'],
		]]],
	],
	'DJI-NEO' => [
		'source' => 'https://www.dji.com/global/neo-2/specs',
		'highlights' => [
			'Khối lượng công bố 151 g khi không lắp bộ thu phát kỹ thuật số; 160 g khi lắp bộ này.',
			'Hỗ trợ điều khiển bằng cử chỉ, theo dõi chủ thể và cất/hạ cánh trên lòng bàn tay.',
			'Thời lượng bay tối đa khoảng 19 phút; khoảng 17 phút khi lắp bảo vệ cánh.',
		],
		'specs' => [['group' => 'Flycam DJI Neo 2', 'attributes' => [
			['name' => 'Khối lượng', 'text' => '151 g không gồm bộ thu phát kỹ thuật số; 160 g khi lắp bộ thu phát'],
			['name' => 'Cảm biến camera', 'text' => 'CMOS 1/2 inch'],
			['name' => 'Bộ nhớ trong', 'text' => '49 GB'],
			['name' => 'Thời lượng bay tối đa', 'text' => 'Khoảng 19 phút; khoảng 17 phút khi lắp bảo vệ cánh, theo điều kiện thử nghiệm của DJI'],
			['name' => 'Lưu ý', 'text' => 'Khối lượng và cấu hình phụ kiện thay đổi theo phiên bản bộ sản phẩm'],
		]]],
	],
	'DJI-POCKET3' => [
		'source' => 'https://www.dji.com/global/osmo-pocket-3/specs',
		'highlights' => [
			'Cảm biến CMOS 1 inch kết hợp gimbal chống rung cơ học 3 trục.',
			'Màn hình cảm ứng xoay 2 inch và quay chậm tối đa 4K/120 fps.',
			'Tối đa 166 phút hoạt động trong điều kiện thử nghiệm của DJI.',
		],
		'specs' => [['group' => 'Camera DJI Osmo Pocket 3', 'attributes' => [
			['name' => 'Cảm biến', 'text' => 'CMOS 1 inch'],
			['name' => 'Chống rung', 'text' => 'Gimbal cơ học 3 trục'],
			['name' => 'Màn hình', 'text' => 'Cảm ứng xoay 2 inch, độ sáng 700 nit'],
			['name' => 'Video chậm', 'text' => '4K đến 120 fps theo chế độ được hỗ trợ'],
			['name' => 'Thời lượng hoạt động', 'text' => '166 phút theo điều kiện thử nghiệm của DJI'],
		]]],
	],
	'DJI-RS4MINI' => [
		'source' => 'https://www.dji.com/global/rs-4-mini/specs',
		'highlights' => [
			'Tải trọng thử nghiệm 0,4–2 kg; hỗ trợ quay dọc nguyên bản.',
			'Khóa trục tự động và khả năng xoay trục pan liên tục 360°.',
			'Tối đa 13 giờ hoạt động khi gimbal được cân bằng và đứng yên theo điều kiện thử nghiệm của DJI.',
		],
		'specs' => [['group' => 'Gimbal DJI RS 4 Mini', 'attributes' => [
			['name' => 'Tải trọng thử nghiệm', 'text' => '0,4–2 kg'],
			['name' => 'Khối lượng gimbal', 'text' => 'Khoảng 890 g, gồm đĩa tháo nhanh và chưa gồm tripod'],
			['name' => 'Khóa trục', 'text' => 'Tự động'],
			['name' => 'Quay dọc nguyên bản', 'text' => 'Có hỗ trợ'],
			['name' => 'Thời lượng hoạt động', 'text' => 'Tối đa 13 giờ khi cân bằng và đứng yên; giảm khi gimbal chuyển động'],
		]]],
	],
	'FLY-BETA-01' => [
		'source' => 'https://betafpv.com/products/cetus-pro-fpv-kit',
		'video' => 'https://www.youtube.com/watch?v=p3fHZ8NzS3s',
		'video_title' => 'BETAFPV Cetus Pro FPV Kit',
		'sections' => [
			['title' => 'Cánh quạt thay thế cho Cetus Pro', 'text' => 'Bộ kit gồm 4 cánh quạt 40 mm; chỉ thay bằng phụ tùng đúng kích thước và đúng chiều quay.'],
			['title' => 'Pin FPV thay thế', 'text' => '2 pin BT2.0 450 mAh 1S 75C đi kèm. Thời lượng bay công bố khoảng 4–5 phút mỗi pin.'],
			['title' => 'Phụ kiện trong bộ', 'text' => 'Kính VR02, tay điều khiển LiteRadio 2 SE, bộ sạc/kiểm tra điện áp, cáp Type-C, dụng cụ và túi đựng.'],
		],
		'highlights' => [
			'Bộ FPV RTF gồm flycam, tay điều khiển LiteRadio 2 SE và kính VR02.',
			'Hỗ trợ cảm biến quang học, barometer và laser để hỗ trợ giữ vị trí/độ cao.',
			'Thời lượng bay công bố khoảng 4–5 phút mỗi pin; kết quả thực tế tùy điều kiện bay.',
		],
		'specs' => [
			['group' => 'Flycam Cetus Pro', 'attributes' => [
				['name' => 'Hệ thống động lực', 'text' => 'Động cơ brushless 1102, 18.000 KV'],
				['name' => 'Bộ điều khiển bay', 'text' => 'Lite 1–2S Pro Brushless FC'],
				['name' => 'Camera FPV', 'text' => 'C02 Micro Camera, góc nghiêng 30°'],
				['name' => 'Video transmitter', 'text' => 'Công suất phát 25 mW'],
				['name' => 'Khối lượng cất cánh', 'text' => '33,19 g, chưa gồm pin'],
				['name' => 'Thời lượng bay', 'text' => 'Khoảng 4–5 phút với pin 450 mAh'],
				['name' => 'Khoảng cách bay', 'text' => 'Tối đa khoảng 80 m trong môi trường rộng, ít nhiễu'],
			]],
			['group' => 'Kính và điều khiển', 'attributes' => [
				['name' => 'Kính FPV', 'text' => 'BETAFPV VR02, băng tần 5,8 GHz'],
				['name' => 'Tay điều khiển', 'text' => 'LiteRadio 2 SE, giao thức Frsky D8'],
				['name' => 'Pin đi kèm', 'text' => '2 pin BT2.0 450 mAh 1S 75C theo cấu hình hãng hiện công bố'],
			]],
		],
		'in_box' => [
			'Flycam Cetus Pro', 'Tay điều khiển LiteRadio 2 SE', 'Kính FPV VR02',
			'2 pin BT2.0 450 mAh', 'Bộ sạc pin kiêm kiểm tra điện áp', 'Cáp USB-C và đầu chuyển Type-C',
			'4 cánh quạt 40 mm', 'Dụng cụ tháo cánh, tua vít và túi đựng',
		],
	],
	'DJI-RC2' => [
		'source' => 'https://www.dji.com/rc-2/specs',
		'specs' => [['group' => 'Màn hình và kết nối', 'attributes' => [
			['name' => 'Màn hình', 'text' => '5,5 inch, độ phân giải 1920 × 1080, 60 fps'],
			['name' => 'Độ sáng', 'text' => '700 nit'],
			['name' => 'Bộ nhớ trong', 'text' => '32 GB, có khe microSD mở rộng'],
			['name' => 'Kết nối không dây', 'text' => 'Wi‑Fi 6 và Bluetooth 5.2'],
			['name' => 'Thời lượng hoạt động', 'text' => 'Tối đa khoảng 3 giờ theo điều kiện thử nghiệm của DJI'],
			['name' => 'Tương thích', 'text' => 'Mini 4 Pro, Air 3, Air 3S và Neo; kiểm tra phiên bản firmware trước khi dùng'],
		]]],
	],
	'DJI-BAT-MINI4' => [
		'source' => 'https://www.dji.com/mini-4-pro/specs',
		'specs' => [['group' => 'Pin Intelligent Flight Battery Plus', 'attributes' => [
			['name' => 'Dung lượng', 'text' => '3.850 mAh'],
			['name' => 'Điện áp danh định', 'text' => '7,38 V'],
			['name' => 'Năng lượng', 'text' => '28,4 Wh'],
			['name' => 'Khối lượng', 'text' => 'Khoảng 121 g'],
			['name' => 'Thời lượng bay công bố', 'text' => 'Tối đa 45 phút với DJI Mini 4 Pro trong điều kiện thử nghiệm của hãng'],
			['name' => 'Lưu ý tương thích', 'text' => 'Dùng cho Mini 4 Pro và dòng Mini 3 tương thích; khi lắp pin Plus, khối lượng drone vượt 249 g'],
		]]],
	],
	'FLY-DJI-04' => [
		'source' => 'https://www.dji.com/mavic-3-pro/specs',
		'highlights' => ['Hệ thống ba camera Hasselblad và tele, tiêu cự tương đương 24/70/166 mm.', 'Quay video tối đa 5.1K; thời lượng bay tối đa 43 phút theo điều kiện thử nghiệm của hãng.', 'Phiên bản này đi kèm tay điều khiển DJI RC; phụ kiện combo có thể khác theo tên phiên bản.'],
		'specs' => [['group' => 'Flycam và camera', 'attributes' => [
			['name' => 'Camera Hasselblad', 'text' => 'Cảm biến 4/3 CMOS, 20 MP; tiêu cự tương đương 24 mm'],
			['name' => 'Camera tele trung bình', 'text' => 'Cảm biến 1/1,3 inch CMOS, 48 MP; tiêu cự tương đương 70 mm'],
			['name' => 'Camera tele', 'text' => 'Cảm biến 1/2 inch CMOS, 12 MP; tiêu cự tương đương 166 mm'],
			['name' => 'Độ phân giải video tối đa', 'text' => '5.1K đến 50 fps với camera Hasselblad'],
			['name' => 'Thời lượng bay tối đa', 'text' => '43 phút trong điều kiện thử nghiệm của DJI'],
			['name' => 'Trọng lượng cất cánh', 'text' => 'Khoảng 958 g'],
			['name' => 'Truyền hình ảnh', 'text' => 'DJI O3+, khoảng cách tối đa phụ thuộc khu vực và môi trường'],
		]]],
	],
	'FLY-DJI-05' => [
		'source' => 'https://www.dji.com/neo/specs',
		'highlights' => ['Thiết kế nhỏ gọn 135 g, hỗ trợ cất cánh và hạ cánh trên lòng bàn tay.', 'Quay video 4K/30 fps và QuickShots.', 'Thời lượng bay tối đa khoảng 18 phút theo điều kiện thử nghiệm của DJI.'],
		'specs' => [['group' => 'Flycam DJI Neo', 'attributes' => [
			['name' => 'Khối lượng', 'text' => 'Khoảng 135 g'],
			['name' => 'Video', 'text' => '4K (3840 × 2160) tối đa 30 fps'],
			['name' => 'Thời lượng bay tối đa', 'text' => 'Khoảng 18 phút; khoảng 17 phút khi lắp bảo vệ cánh'],
			['name' => 'Tính năng', 'text' => 'Cất/hạ cánh lòng bàn tay, QuickShots và theo dõi chủ thể'],
			['name' => 'Cấu hình bộ sản phẩm', 'text' => 'Fly More Combo; số pin và phụ kiện theo bộ hàng thực tế'],
		]]],
	],
	'CAM-DJI-02' => [
		'source' => 'https://www.dji.com/rs-4/specs',
		'highlights' => ['Tải trọng thử nghiệm tối đa 3 kg.', 'Khóa trục tự động thế hệ mới và hỗ trợ quay dọc nguyên bản.', 'Thời lượng hoạt động tối đa 12 giờ trong trạng thái cân bằng, đứng yên theo điều kiện thử nghiệm của DJI.'],
		'specs' => [['group' => 'Gimbal DJI RS 4', 'attributes' => [
			['name' => 'Tải trọng thử nghiệm', 'text' => '3 kg'],
			['name' => 'Pin tay cầm', 'text' => '3.000 mAh, LiPo 2S'],
			['name' => 'Thời lượng hoạt động', 'text' => 'Tối đa 12 giờ khi cân bằng và đứng yên; giảm khi gimbal đang chuyển động'],
			['name' => 'Thời gian sạc', 'text' => 'Khoảng 2,5 giờ với bộ sạc hỗ trợ sạc nhanh 18 W'],
			['name' => 'Trọng lượng gimbal', 'text' => 'Khoảng 1.066 g, chưa gồm tay cầm và phụ kiện'],
			['name' => 'Tính năng', 'text' => 'Quay dọc nguyên bản, khóa trục tự động và Bluetooth 5.1'],
		]]],
	],
	'CAM-DJI-03' => [
		'source' => 'https://www.dji.com/osmo-action-5-pro/specs',
		'highlights' => ['Cảm biến 1/1,3 inch CMOS, ảnh tĩnh tối đa khoảng 40 MP.', 'Quay video 4K tối đa 120 fps ở một số tỷ lệ khung hình.', 'Chống nước đến 20 m khi không dùng vỏ chống nước; bộ nhớ trong khả dụng khoảng 47 GB.'],
		'specs' => [['group' => 'Camera hành động', 'attributes' => [
			['name' => 'Cảm biến', 'text' => '1/1,3 inch CMOS'],
			['name' => 'Video tối đa', 'text' => '4K/120 fps ở chế độ được hỗ trợ'],
			['name' => 'Ảnh tĩnh tối đa', 'text' => 'Khoảng 40 MP'],
			['name' => 'Chống nước', 'text' => '20 m không cần vỏ; tối đa 60 m với vỏ chống nước'],
			['name' => 'Bộ nhớ trong', 'text' => '64 GB, khoảng 47 GB khả dụng'],
			['name' => 'Trọng lượng', 'text' => 'Khoảng 146 g'],
		]]],
	],
	'CAM-DJI-04' => [
		'source' => 'https://www.dji.com/mic-2/specs',
		'highlights' => ['Ghi âm nội bộ 32-bit float và khử ồn thông minh.', 'Khoảng cách truyền tối đa 250 m theo tiêu chuẩn FCC trong môi trường thông thoáng.', 'Bộ 2 TX + 1 RX; tổng thời lượng sử dụng công bố đến 18 giờ khi dùng hộp sạc.'],
		'specs' => [['group' => 'Micro không dây DJI Mic 2', 'attributes' => [
			['name' => 'Cấu hình', 'text' => '2 bộ phát (TX), 1 bộ thu (RX) và hộp sạc'],
			['name' => 'Ghi âm nội bộ', 'text' => 'Hỗ trợ 32-bit float; thời lượng ghi nội bộ tối đa 14 giờ theo công bố của hãng'],
			['name' => 'Khoảng cách truyền', 'text' => 'Tối đa 250 m (FCC) hoặc 160 m (CE), trong môi trường thông thoáng'],
			['name' => 'Thời lượng hoạt động', 'text' => 'Tối đa 6 giờ mỗi TX/RX; đến 18 giờ khi bổ sung hộp sạc theo điều kiện thử nghiệm'],
			['name' => 'Kết nối', 'text' => '2,4 GHz; Bluetooth trên TX'],
		]]],
	],
	'PC-GPU-01' => [
		'source' => 'https://rog.asus.com/us/graphics-cards/graphics-cards/rog-strix/rog-strix-rtx4070s-o12g-gaming/spec/',
		'specs' => [['group' => 'Card đồ họa ASUS ROG Strix', 'attributes' => [
			['name' => 'GPU', 'text' => 'NVIDIA GeForce RTX 4070 SUPER, kiến trúc Ada Lovelace'],
			['name' => 'Bộ nhớ đồ họa', 'text' => '12 GB GDDR6X, bus 192-bit'],
			['name' => 'Nhân CUDA', 'text' => '7.168'],
			['name' => 'Xung boost', 'text' => '2.670 MHz ở OC mode; 2.640 MHz ở mặc định'],
			['name' => 'Kết nối màn hình', 'text' => '2 × HDMI 2.1a và 3 × DisplayPort 1.4a'],
		]]],
	],
	'PC-SSD-01' => [
		'source' => 'https://www.samsung.com/uk/memory-storage/nvme-ssd/990-pro-1tb-nvme-pcie-gen-4-mz-v9p1t0bw/',
		'specs' => [['group' => 'SSD Samsung 990 PRO', 'attributes' => [
			['name' => 'Dung lượng', 'text' => '1 TB'],
			['name' => 'Giao tiếp', 'text' => 'PCIe Gen 4.0 ×4, NVMe 2.0'],
			['name' => 'Kích thước', 'text' => 'M.2 2280'],
			['name' => 'Tốc độ đọc tuần tự', 'text' => 'Tối đa 7.450 MB/s'],
			['name' => 'Tốc độ ghi tuần tự', 'text' => 'Tối đa 6.900 MB/s'],
			['name' => 'Bảo hành hãng', 'text' => '5 năm hoặc 600 TBW, tùy điều kiện nào đến trước'],
		]]],
	],
	'PC-CPU-01' => [
		'source' => 'https://www.intel.com/content/www/us/en/products/sku/236780/intel-core-i7-processor-14700k-33m-cache-up-to-5-60-ghz/specifications.html',
		'specs' => [['group' => 'Bộ xử lý Intel Core i7-14700K', 'attributes' => [
			['name' => 'Nhân và luồng', 'text' => '20 nhân (8 P-core + 12 E-core), 28 luồng'],
			['name' => 'Xung turbo tối đa', 'text' => '5,6 GHz'],
			['name' => 'Bộ nhớ đệm', 'text' => '33 MB Intel Smart Cache'],
			['name' => 'Công suất cơ bản', 'text' => '125 W; công suất turbo tối đa 253 W'],
			['name' => 'Socket', 'text' => 'LGA1700'],
		]]],
	],
	'PC-MB-01' => [
		'source' => 'https://www.asus.com/id/news/b6iiku0c98okblaj/',
		'specs' => [['group' => 'Bo mạch chủ ASUS ROG STRIX Z790-A GAMING WIFI', 'attributes' => [
			['name' => 'Socket / chipset', 'text' => 'Intel LGA1700 / Intel Z790'],
			['name' => 'Bộ nhớ', 'text' => '4 khe DDR5, tối đa 192 GB'],
			['name' => 'Kết nối đồ họa', 'text' => '1 khe PCIe 5.0 ×16'],
			['name' => 'Lưu trữ', 'text' => '4 khe M.2 PCIe 4.0 và 4 cổng SATA 6 Gb/s'],
			['name' => 'Mạng', 'text' => 'Intel Wi‑Fi 6E và Ethernet 2,5 Gb'],
		]]],
	],
	'PC-RAM-01' => [
		'source' => 'https://www.corsair.com/ww/en/p/memory/cmh32gx5m2d6000c36/vengeance-rgb-32gb-2x16gb-ddr5-dram-6000mhz-c36-memory-kit-black-cmh32gx5m2d6000c36',
		'specs' => [['group' => 'RAM Corsair Vengeance RGB DDR5', 'attributes' => [
			['name' => 'Dung lượng', 'text' => '32 GB (2 × 16 GB)'],
			['name' => 'Chuẩn bộ nhớ', 'text' => 'DDR5 UDIMM'],
			['name' => 'Tốc độ công bố', 'text' => '6.000 MT/s; phụ thuộc CPU, bo mạch chủ và cấu hình BIOS'],
			['name' => 'Độ trễ', 'text' => 'CL36 theo SKU CMH32GX5M2D6000C36'],
			['name' => 'Tính năng', 'text' => 'RGB 10 vùng, Intel XMP 3.0'],
		]]],
	],
	'KB-AKKO-01' => [
		'source' => 'https://en.akkogear.com/product/mochidango-3068b-plus-mechanical-keyboard/',
		'specs' => [['group' => 'Bàn phím AKKO 3068B Plus', 'attributes' => [
			['name' => 'Bố cục', 'text' => '65%, 68 phím'],
			['name' => 'Kết nối', 'text' => 'Bluetooth 5.0, 2.4 GHz và USB-C'],
			['name' => 'Hot-swap', 'text' => 'Socket 5-pin; một số vị trí có thể giới hạn chân switch theo cấu tạo vỏ'],
			['name' => 'Pin', 'text' => '1.800 mAh'],
			['name' => 'Tính năng', 'text' => 'RGB, N-key rollover và macro qua phần mềm AKKO'],
		]]],
	],
	'KB-MONS-01' => [
		'source' => 'https://www.monsgeek.com/product/m1w-v3-barebone-fully-assembled/',
		'specs' => [['group' => 'Bàn phím MonsGeek M1W V3', 'attributes' => [
			['name' => 'Bố cục', 'text' => '75%, 82 phím'],
			['name' => 'Vỏ', 'text' => 'Nhôm'],
			['name' => 'Kết nối', 'text' => '2.4 GHz, Bluetooth và USB-C'],
			['name' => 'Kết cấu', 'text' => 'Gasket mount, plate foam PORON và plate polycarbonate'],
			['name' => 'Hot-swap', 'text' => 'Có; thông số áp dụng cho bản assembled tương ứng'],
		]]],
	],
	'KB-LOGI-01' => [
		'source' => 'https://www.logitechg.com/en-gb/products/gaming-keyboards/g915-tkl-wireless.920-009719.html',
		'specs' => [['group' => 'Bàn phím Logitech G915 TKL', 'attributes' => [
			['name' => 'Bố cục', 'text' => 'Tenkeyless, không có cụm phím số'],
			['name' => 'Kết nối', 'text' => 'LIGHTSPEED 2.4 GHz và Bluetooth; có thể dùng USB khi sạc'],
			['name' => 'Switch', 'text' => 'Switch cơ low-profile Logitech GL; loại switch tùy SKU'],
			['name' => 'Thời lượng pin', 'text' => 'Khoảng 40 giờ ở độ sáng 100% theo Logitech'],
			['name' => 'Kích thước / khối lượng', 'text' => '368 × 150 × 22 mm; khoảng 810 g'],
		]]],
	],
	'MS-LOGI-01' => [
		'source' => 'https://www.logitechg.com/en-us/products/gaming-mice/pro-x2-superlight-wireless-mouse.html',
		'specs' => [['group' => 'Chuột Logitech G PRO X SUPERLIGHT 2', 'attributes' => [
			['name' => 'Cảm biến', 'text' => 'HERO 2, tối đa 44.000 DPI theo trang thông số hiện tại của Logitech'],
			['name' => 'Khối lượng', 'text' => 'Khoảng 60 g'],
			['name' => 'Switch', 'text' => 'LIGHTFORCE hybrid'],
			['name' => 'Polling rate', 'text' => 'Tối đa 8.000 Hz với cấu hình/phần mềm tương thích'],
			['name' => 'Thời lượng pin', 'text' => 'Tối đa 95 giờ chuyển động liên tục theo Logitech'],
		]]],
	],
	'MS-RAZER-01' => [
		'source' => 'https://www.razer.com/gaming-mice/razer-viper-v3-pro',
		'specs' => [['group' => 'Chuột Razer Viper V3 Pro', 'attributes' => [
			['name' => 'Cảm biến', 'text' => 'Focus Pro 35K Optical Sensor Gen-2'],
			['name' => 'Độ phân giải tối đa', 'text' => '35.000 DPI'],
			['name' => 'Khối lượng', 'text' => '54 g (bản đen), 55 g (bản trắng)'],
			['name' => 'Polling rate', 'text' => 'Tối đa 8.000 Hz với HyperPolling Wireless Dongle đi kèm'],
			['name' => 'Pin', 'text' => 'Tối đa 95 giờ ở 1.000 Hz; thời lượng giảm khi tăng polling rate'],
		]]],
	],
	'MS-RAZER-02' => [
		'source' => 'https://www.razer.com/gaming-mice/razer-deathadder-v3-pro',
		'specs' => [['group' => 'Chuột Razer DeathAdder V3 Pro', 'attributes' => [
			['name' => 'Cảm biến', 'text' => 'Razer Focus Pro 30K Optical Sensor'],
			['name' => 'Độ phân giải tối đa', 'text' => '30.000 DPI'],
			['name' => 'Khối lượng', 'text' => 'Khoảng 63 g (đen) hoặc 64 g (trắng), chưa gồm cáp'],
			['name' => 'Pin', 'text' => 'Tối đa 90 giờ theo Razer'],
			['name' => 'Kết nối', 'text' => 'Razer HyperSpeed Wireless và USB-C'],
		]]],
	],
	'HP-STEEL-01' => [
		'source' => 'https://steelseries.com/gaming-headsets/arctis-nova-pro',
		'specs' => [['group' => 'Tai nghe SteelSeries Arctis Nova Pro Wireless', 'attributes' => [
			['name' => 'Khử ồn', 'text' => 'Chống ồn chủ động ANC và chế độ xuyên âm'],
			['name' => 'Kết nối', 'text' => 'Không dây 2.4 GHz và Bluetooth'],
			['name' => 'Pin', 'text' => 'Hệ thống pin tháo nóng, gồm 2 pin trong hộp'],
			['name' => 'Trạm kết nối', 'text' => 'Wireless Base Station hỗ trợ chuyển giữa thiết bị'],
			['name' => 'Lưu ý phiên bản', 'text' => 'Tương thích PC/PlayStation hoặc Xbox tùy mã SKU'],
		]]],
	],
	'HP-HYPER-01' => [
		'source' => 'https://row.hyperx.com/products/hyperx-cloud-iii-wireless-gaming-headset',
		'specs' => [['group' => 'Tai nghe HyperX Cloud III Wireless', 'attributes' => [
			['name' => 'Driver', 'text' => '53 mm, thiết kế nghiêng'],
			['name' => 'Kết nối', 'text' => 'Không dây 2.4 GHz; phạm vi tối đa khoảng 20 m theo hãng'],
			['name' => 'Thời lượng pin', 'text' => 'Tối đa 120 giờ ở mức âm lượng thử nghiệm 50%'],
			['name' => 'Âm thanh', 'text' => 'DTS Headphone:X Spatial Audio'],
			['name' => 'Tương thích', 'text' => 'PC, PS5, PS4 và Nintendo Switch'],
		]]],
	],
	'MON-ASUS-01' => [
		'source' => 'https://press.asus.com/news/press-releases/rog-announces-availability-of-swift-oled-pg27-aqdm-gaming-monitor',
		'specs' => [['group' => 'Màn hình ASUS ROG Swift OLED PG27AQDM', 'attributes' => [
			['name' => 'Kích thước / tấm nền', 'text' => '26,5 inch, OLED'],
			['name' => 'Độ phân giải', 'text' => 'QHD 2560 × 1440'],
			['name' => 'Tần số quét', 'text' => '240 Hz'],
			['name' => 'Thời gian phản hồi', 'text' => '0,03 ms GTG'],
			['name' => 'Màu sắc', 'text' => '99% DCI-P3, Delta E < 2 theo thông tin ROG'],
		]]],
	],
	'MON-DELL-01' => [
		'source' => 'https://www.dell.com/en-us/shop/dell-ultrasharp-27-monitor-u2724d/apd/210-bksf/monitors-monitor-accessories',
		'specs' => [['group' => 'Màn hình Dell UltraSharp U2724D', 'attributes' => [
			['name' => 'Kích thước / tấm nền', 'text' => '27 inch, IPS Black'],
			['name' => 'Độ phân giải / tần số quét', 'text' => 'QHD 2560 × 1440, tối đa 120 Hz'],
			['name' => 'Độ tương phản', 'text' => '2.000:1 (điển hình)'],
			['name' => 'Độ phủ màu', 'text' => '98% DCI-P3 theo thông số Dell'],
			['name' => 'Độ sáng', 'text' => '350 cd/m² (điển hình)'],
		]]],
	],
];
