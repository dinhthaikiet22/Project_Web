-- SQL Seed Script: CycleTrust Bikes (16 Realistic Records)
-- Execute this script to replace your current dummy bike data with realistic, high-quality records.

-- 1. Optional: Clear existing dummy data and reset auto-increment
-- NOTE: If your 'orders' table has foreign keys referencing 'bikes', you might need to handle those first, 
-- or temporarily disable foreign key checks using: SET FOREIGN_KEY_CHECKS=0;
DELETE FROM bikes;
ALTER TABLE bikes AUTO_INCREMENT = 1;

-- 2. Insert 16 Realistic Bikes
-- Mapping Category IDs (Assumptions): 1 = Road, 2 = Mountain, 3 = Hybrid/Electric, 4 = Fixie/City
-- Mapping User IDs (Assumptions): 1, 2, 3, 4 are existing active users in your `users` table.

INSERT INTO bikes (user_id, category_id, title, price, brand, condition_status, description, image_url, location, status) VALUES 
(1, 1, 'Trek Madone SLR 9 2023', 120000000, 'Trek', 'Mới', 'Khung carbon OCLV 800 siêu cấp, Groupset Shimano Dura-Ace Di2 12 tốc độ, Phanh đĩa thủy lực. Tuyệt tác xé gió, siêu nhẹ và siêu lướt.', 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=800&q=80', 'Quận 1, TP.HCM', 'available'),
(2, 2, 'Giant Trance X Advanced Pro 29 2022', 85000000, 'Giant', 'Đã sử dụng', 'Khung composite siêu nhẹ, Fox 36 Factory 150mm, Sram GX Eagle 1x12. Xe còn mới 95%, ít đi do đổi đam mê sang Road.', 'https://images.unsplash.com/photo-1576435728678-68ce0db6f38c?auto=format&fit=crop&w=800&q=80', 'Cầu Giấy, Hà Nội', 'available'),
(3, 1, 'Specialized Allez Sprint Comp 2023', 55000000, 'Specialized', 'Mới', 'Khung nhôm E5 đẳng cấp, Groupset Shimano 105, Phanh đĩa thủy lực. Thiết kế khí động học thừa hưởng từ dòng Tarmac danh tiếng.', 'https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?auto=format&fit=crop&w=800&q=80', 'Quận 7, TP.HCM', 'sold'),
(1, 3, 'Cannondale Quick Neo SL 2 2021', 45000000, 'Cannondale', 'Đã sử dụng', 'Xe đạp trợ lực điện đô thị. Pin 250Wh ẩn gọn gàng trong khung, động cơ Mahle ebikemotion. Tình trạng còn rất mới 99%.', 'https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?auto=format&fit=crop&w=800&q=80', 'Hải Châu, Đà Nẵng', 'pending'),
(2, 4, 'Trinx Free 3.0 2022', 6500000, 'Trinx', 'Đã sử dụng', 'Khung nhôm, phanh cơ, groupset Shimano Tourney cơ bản. Lựa chọn tuyệt vời để đạp thể dục hoặc đi làm hàng ngày. Trầy xước nhẹ.', 'https://images.unsplash.com/photo-1511994298241-608e28f14fde?auto=format&fit=crop&w=800&q=80', 'Gò Vấp, TP.HCM', 'available'),
(3, 1, 'Twitter Sniper 2.0 Carbon 2023', 15000000, 'Twitter', 'Mới', 'Khung carbon T800, Groupset Retrospec 2x11s, vành nhôm chém gió cao 4cm. P/P (hiệu năng trên giá thành) tốt nhất phân khúc.', 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?auto=format&fit=crop&w=800&q=80', 'Thanh Xuân, Hà Nội', 'available'),
(1, 2, 'Trek Marlin 5 2022', 11500000, 'Trek', 'Đã sử dụng', 'Xe leo núi nhập khẩu nguyên chiếc. Đã nâng cấp phanh dầu Shimano MT200. Hoạt động hoàn hảo, bảo dưỡng định kỳ thường xuyên.', 'https://images.unsplash.com/photo-1593452410762-b9e73b22ed7d?auto=format&fit=crop&w=800&q=80', 'Ngũ Hành Sơn, Đà Nẵng', 'sold'),
(4, 1, 'Giant Defy Advanced 2 2021', 42000000, 'Giant', 'Đã sử dụng', 'Khung carbon nguyên khối, phanh đĩa dầu. Cọc yên và phuộc D-Fuse giảm chấn siêu êm ái cho những chuyến đi dài (Endurance).', 'https://images.unsplash.com/photo-1528629297340-d1d466945cb5?auto=format&fit=crop&w=800&q=80', 'Quận 3, TP.HCM', 'pending'),
(2, 2, 'Specialized Rockhopper Expert 2023', 28000000, 'Specialized', 'Mới', 'Khung hợp kim nhôm A1 cao cấp, phuộc nhún RockShox Judy, bộ chuyển động SRAM SX Eagle 1x12. Hàng chính hãng, mới đập hộp.', 'https://images.unsplash.com/photo-1563816694801-7c98df5b7e2d?auto=format&fit=crop&w=800&q=80', 'Tân Bình, TP.HCM', 'available'),
(3, 1, 'Cannondale CAAD13 105 2022', 38000000, 'Cannondale', 'Đã sử dụng', 'Huyền thoại khung nhôm CAAD. Trọng lượng siêu nhẹ không thua kém xe carbon, độ cứng ấn tượng. Trang bị Full Shimano 105 R7000.', 'https://images.unsplash.com/photo-1484144709455-ce4e21a22ba3?auto=format&fit=crop&w=800&q=80', 'Ba Đình, Hà Nội', 'available'),
(1, 4, 'Trinx Tempo 1.0 2020', 3500000, 'Trinx', 'Đã sử dụng', 'Xe road cơ bản giá rẻ cho sinh viên hoặc người mới bắt đầu. Tình trạng 90%, đã tra mỡ bảo dưỡng toàn bộ, mua về là chạy.', 'https://images.unsplash.com/photo-1623055416262-6c303f0b24dc?auto=format&fit=crop&w=800&q=80', 'Sơn Trà, Đà Nẵng', 'available'),
(4, 3, 'Giant Roam 2 Disc 2022', 14000000, 'Giant', 'Mới', 'Xe lai hybrid hoàn hảo cho cả đường phố và địa hình gồ ghề nhẹ. Khung nhôm Aluxx siêu bền, phanh đĩa thủy lực Tektro an toàn.', 'https://images.unsplash.com/photo-1601614742635-43ea5605d5c0?auto=format&fit=crop&w=800&q=80', 'Quận 10, TP.HCM', 'sold'),
(2, 1, 'Trek Domane AL 2 Disc 2023', 22000000, 'Trek', 'Mới', 'Road bike thiết kế dành cho đường trường, tư thế lái cực kì thoải mái. Shimano Claris 8 speed, lốp 32c bám đường cực tốt.', 'https://images.unsplash.com/photo-1582650041695-17a41cebfb35?auto=format&fit=crop&w=800&q=80', 'Hoàn Kiếm, Hà Nội', 'available'),
(3, 2, 'Twitter Leopard Pro RS-13 2023', 18500000, 'Twitter', 'Mới', 'MTB Carbon ngoại hình cực kỳ hầm hố, phuộc hơi Twitter có khóa hành trình trên ghi đông, groupset Retrospec 13 tốc độ đỉnh cao.', 'https://images.unsplash.com/photo-1544191696-102dbbce162e?auto=format&fit=crop&w=800&q=80', 'Bình Thạnh, TP.HCM', 'pending'),
(4, 1, 'Specialized Tarmac SL7 Pro 2022', 110000000, 'Specialized', 'Đã sử dụng', 'Một trong những chiếc xe nhanh nhất thế giới. Sram Force eTap AXS. Bánh Carbon Roval Rapide CL. Tình trạng hoàn hảo 95%, chỉ trầy dăm.', 'https://images.unsplash.com/photo-1511994298241-608e28f14fde?auto=format&fit=crop&w=800&q=80', 'Quận 2, TP.HCM', 'available'),
(1, 3, 'Cannondale Treadwell Neo 2 2023', 39000000, 'Cannondale', 'Mới', 'Xe đạp điện dạo phố siêu phong cách. Tích hợp app điện thoại Cannondale theo dõi hành trình và tốc độ ngay trên tay lái.', 'https://images.unsplash.com/photo-1559348349-86f1f65817fe?auto=format&fit=crop&w=800&q=80', 'Thanh Khê, Đà Nẵng', 'available');

-- End of script
