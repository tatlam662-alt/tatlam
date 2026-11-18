# Dữ liệu địa danh trong PanDaShop

## Danh sách chính thức tỉnh Thanh Hóa năm 2026

`official_thanhhoa_localities_2026.json` chứa danh sách 1.933 tên thôn, tổ dân phố thuộc 166 xã/phường sau sắp xếp. Danh sách được Sở Nội vụ Thanh Hóa tổng hợp theo Công văn 5487/SNV-XDCQ&CTTN; Báo và Phát thanh, Truyền hình Thanh Hóa đăng thông tin và đính kèm danh sách. Nguồn: <https://baothanhhoa.vn/danh-sach-ten-goi-thon-to-dan-pho-thuoc-cac-xa-phuong-tren-dia-ban-tinh-thanh-hoa-296782.htm>.

Nạp hoặc cập nhật danh sách bằng lệnh trong thư mục gốc dự án:

```powershell
C:\xampp\php\php.exe import_official_localities.php
```

Importer chỉ ghi dữ liệu sau khi mọi tên xã/phường khớp duy nhất với danh mục Thanh Hóa hiện hành. Các tên được đánh dấu đã xác minh và giữ tên nguồn trong cơ sở dữ liệu. Lệnh có thể chạy lại an toàn.

## Danh sách thôn theo Quyết định 803/QĐ-BDTTG

`official_bdttg_803_localities_2026.json` chứa 14.692 mục đọc được và đối chiếu theo thứ tự trong các phụ lục PDF của Bộ Dân tộc và Tôn giáo. Các mục được nạp chỉ khi toàn bộ danh sách của xã/phường trong phụ lục khớp đủ số lượng và thứ tự dòng; 68 nhóm không trích xuất đủ đã bị bỏ qua. Dữ liệu đến từ 27 tỉnh/thành và hiện có gợi ý ở 1.329 phường/xã. Lệnh nạp:

```powershell
C:\xampp\php\php.exe import_bdttg_803_localities.php
```

Nguồn chính thức: <https://bdttg.gov.vn/thong-bao/danh-sach-thon-xa-vung-dtts-mn-xa-khu-vuc-i-ii-iii-giai-doan-2026-2030.htm>. Danh sách kèm Quyết định 803 chỉ bao gồm thôn thuộc vùng đồng bào dân tộc thiểu số và miền núi và thôn đặc biệt khó khăn ở 27 tỉnh/thành; không phải danh mục đầy đủ thôn, tổ dân phố của các địa phương đó. Các mục này vẫn được lưu là dữ liệu đã xác minh vì tên xuất hiện trong phụ lục chính thức.

## Gợi ý bản đồ

`vietnam_osm_localities_2026-10-18.json` gồm tên địa danh OpenStreetMap được ghép duy nhất với phường/xã qua ranh giới bản đồ. Đây là gợi ý chưa xác minh hành chính, không thay thế danh sách địa phương công bố. Ghi công và giấy phép ở `OPENSTREETMAP-LICENSE.md`.

## Phạm vi

Không có một tệp công khai duy nhất chứa tên hiện hành của toàn bộ thôn, tổ dân phố, xóm và ấp trên cả nước để có thể nạp đủ 34 tỉnh/thành. Danh mục thôn/tổ dân phố được các địa phương rà soát và công bố riêng; dữ liệu NSO về thôn/tổ được lập trong quy trình điều tra thống kê. Vì vậy, dữ liệu chính thức được nhập theo từng danh sách địa phương có nguồn cụ thể; không tự sinh tên hoặc gắn tên bản đồ thành dữ liệu chính thức.
