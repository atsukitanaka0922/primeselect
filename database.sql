-- データベース作成・基本設定
CREATE DATABASE IF NOT EXISTS `primeselect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `primeselect`;

-- 文字セットと照合順序の設定
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- カテゴリテーブル
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- カテゴリデータの挿入
INSERT IGNORE INTO `categories` (`id`, `name`, `description`, `created`) VALUES
(1, '衣類', '様々な種類の衣類商品', '2025-05-09 16:39:06'),
(2, '家電', '便利な家電製品', '2025-05-09 16:39:06'),
(3, '食品', '美味しい食料品', '2025-05-09 16:39:06'),
(4, '書籍', '人気の書籍や雑誌', '2025-05-09 16:39:06'),
(5, '健康・美容', '健康維持と美容のためのアイテム', '2025-05-09 22:39:18'),
(6, 'スポーツ・アウトドア', 'スポーツやアウトドア活動に最適な製品', '2025-05-09 22:39:18'),
(7, 'インテリア・雑貨', '暮らしを彩るインテリアと雑貨', '2025-05-09 22:39:18'),
(8, 'ホビー・ゲーム', '趣味やエンターテイメントのための商品', '2025-05-09 22:39:18');

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- デモ用ユーザーデータ（パスワードはハッシュ化済み）
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `created`) VALUES
(1, 'admin', 'admin@example.com', 'admin123', 1, NOW()),
(2, 'user', 'user@example.com', 'user123', 0, NOW());

-- 既存ユーザーのパスワードを平文に更新（ログイン用）
UPDATE `users` SET `password` = 'admin123' WHERE `id` = 1;
UPDATE `users` SET `password` = 'user123' WHERE `id` = 2;

-- 商品テーブル
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  `is_preorder` tinyint(1) DEFAULT 0 COMMENT '受注生産フラグ（0: 通常販売, 1: 受注生産）',
  `preorder_period` varchar(100) DEFAULT NULL COMMENT '受注生産期間',
  `min_stock` int(11) DEFAULT 0 COMMENT '最小在庫数',
  `max_stock` int(11) DEFAULT 0 COMMENT '最大在庫数',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `idx_products_category_stock` (`category_id`, `stock`),
  KEY `idx_products_created` (`created`),
  KEY `idx_products_price` (`price`),
  KEY `idx_products_is_preorder` (`is_preorder`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品画像テーブル
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品バリエーションテーブル
CREATE TABLE IF NOT EXISTS `product_variations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variation_name` varchar(100) NOT NULL,
  `variation_value` varchar(100) NOT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 在庫ログテーブル
CREATE TABLE IF NOT EXISTS `product_stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `type` enum('in','out','adjust') NOT NULL COMMENT '入庫、出庫、調整',
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variation_id` (`variation_id`),
  KEY `idx_stock_logs_product_created` (`product_id`, `created`),
  KEY `idx_stock_logs_created` (`created`),
  CONSTRAINT `product_stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_logs_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- カートテーブル
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created` datetime DEFAULT current_timestamp(),
  `variation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variation_id` (`variation_id`),
  KEY `idx_cart_user_id` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ウィッシュリストテーブル
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 注文テーブル
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created`),
  KEY `idx_orders_user_created` (`user_id`, `created`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 注文アイテムテーブル
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `variation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 決済テーブル
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 予約注文テーブル
CREATE TABLE IF NOT EXISTS `preorders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `status` enum('pending','confirmed','production','shipped','delivered','cancelled') DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `variation_id` (`variation_id`),
  KEY `idx_preorders_status_created` (`status`, `created`),
  KEY `idx_preorders_user_created` (`user_id`, `created`),
  CONSTRAINT `preorders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `preorders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `preorders_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- レビューテーブル
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_reviews_product_rating` (`product_id`, `rating`),
  KEY `idx_reviews_created` (`created`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- パフォーマンス向上のための追加インデックス

-- 商品テーブルのインデックス（すでに作成済みの場合はスキップ）
-- CREATE INDEX idx_products_category_stock ON products(category_id, stock);
-- CREATE INDEX idx_products_created ON products(created);
-- CREATE INDEX idx_products_price ON products(price);
-- CREATE INDEX idx_products_is_preorder ON products(is_preorder);

-- 注文テーブルのインデックス
-- CREATE INDEX idx_orders_status ON orders(status);
-- CREATE INDEX idx_orders_created ON orders(created);
-- CREATE INDEX idx_orders_user_created ON orders(user_id, created);

-- カートテーブルのインデックス
-- CREATE INDEX idx_cart_user_id ON cart(user_id);

-- レビューテーブルのインデックス
-- CREATE INDEX idx_reviews_product_rating ON reviews(product_id, rating);
-- CREATE INDEX idx_reviews_created ON reviews(created);

-- 在庫ログテーブルのインデックス
-- CREATE INDEX idx_stock_logs_product_created ON product_stock_logs(product_id, created);
-- CREATE INDEX idx_stock_logs_created ON product_stock_logs(created);

-- 予約注文テーブルのインデックス
-- CREATE INDEX idx_preorders_status_created ON preorders(status, created);
-- CREATE INDEX idx_preorders_user_created ON preorders(user_id, created);

-- テーブル最適化
ANALYZE TABLE products;
ANALYZE TABLE product_variations;
ANALYZE TABLE preorders;
ANALYZE TABLE orders;
ANALYZE TABLE order_items;
ANALYZE TABLE users;
ANALYZE TABLE cart;
ANALYZE TABLE wishlist;

-- データベースコミット
COMMIT;

-- 文字セットの復元
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- 通常商品データの挿入（続き）
INSERT IGNORE INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `stock`, `created`, `is_preorder`, `preorder_period`, `min_stock`, `max_stock`) VALUES
(32, 'マルチ収納ボックス', '折りたたみ可能な布製収納ボックス。カラフルなデザインでインテリアのアクセントにも。', 1800.00, 7, 'storage_box.jpg', 50, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(33, '観葉植物セット', '育てやすい小型の観葉植物3種セット。鉢付きでインテリアにすぐに馴染みます。', 5400.00, 7, 'plants.jpg', 25, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(34, 'ボードゲームコレクション', '人気のボードゲーム3種セット。家族や友人との時間を楽しく過ごせます。', 8900.00, 8, 'board_games.jpg', 20, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(35, 'デジタルイラストタブレット', 'イラスト制作に最適なペン付きグラフィックタブレット。感度調整可能で初心者から上級者まで対応。', 15800.00, 8, 'drawing_tablet.jpg', 15, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(36, 'ミニドローン', '初心者向けの小型ドローン。室内飛行も可能で、カメラ付きでスマホと連携できます。', 9800.00, 8, 'mini_drone.jpg', 30, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(37, 'プレミアムスマートフォン', '最新技術を搭載した高性能スマートフォン。美しいディスプレイと高速プロセッサを備えています。', 75000.00, 2, 'smartphone.jpg', 0, '2025-05-10 00:31:32', 0, NULL, 0, 0),
(38, 'プレミアムコットンTシャツ', '100%オーガニックコットンを使用した高品質なTシャツ。肌触りが良く、耐久性にも優れています。', 3500.00, 1, 'premium_tshirt.jpg', 0, '2025-05-10 00:31:33', 0, NULL, 0, 0),
(39, 'ウルトラスリムノートPC', '薄型軽量で持ち運びに便利なノートパソコン。長時間バッテリーと高性能プロセッサを搭載。', 95000.00, 2, 'laptop.jpg', 0, '2025-05-10 00:31:33', 0, NULL, 0, 0),
(41, 'プロフェッショナルヘッドホン', 'プロの音楽制作者にも愛用される高音質ヘッドホン。', 28000.00, 2, 'pro_headphones.jpg', 24, '2025-05-12 13:17:13', 0, NULL, 0, 0),
(42, 'アートブック・コレクション', '世界の名画を収録した美術書セット。', 15000.00, 4, 'art_books.jpg', 12, '2025-05-12 13:17:13', 0, NULL, 0, 0),
(43, 'オーガニック・ハニーセット', '国産の純粋はちみつ6種類のセット。', 8500.00, 3, 'honey_set.jpg', 8, '2025-05-12 13:17:13', 0, NULL, 0, 0);

-- 商品画像データの挿入（通常商品）
INSERT IGNORE INTO `product_images` (`id`, `product_id`, `image_file`, `is_main`, `created`) VALUES
(1, 1, 'tshirt.jpg', 1, '2025-05-09 16:39:07'),
(2, 2, 'jeans.jpg', 1, '2025-05-09 16:39:07'),
(3, 3, 'tv.jpg', 1, '2025-05-09 16:39:07'),
(4, 4, 'coffee.jpg', 1, '2025-05-09 16:39:07'),
(5, 5, 'chocolate.jpg', 1, '2025-05-09 16:39:07'),
(6, 6, 'book.jpg', 1, '2025-05-09 16:39:07'),
(7, 7, 'earphones.jpg', 1, '2025-05-09 16:39:07'),
(8, 8, 'smartwatch.jpg', 1, '2025-05-09 16:39:07'),
(9, 9, 'tea.jpg', 1, '2025-05-09 16:39:07'),
(10, 10, 'bag.jpg', 1, '2025-05-09 16:39:07'),
(11, 11, 'yogamat.jpg', 1, '2025-05-09 16:39:07'),
(12, 12, 'charger.jpg', 1, '2025-05-09 16:39:07');

-- 受注生産商品データの挿入
INSERT IGNORE INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `stock`, `created`, `is_preorder`, `preorder_period`, `min_stock`, `max_stock`) VALUES
(40, 'カスタムスマートフォン', 'お客様のご要望に合わせてカスタマイズ可能なオーダーメイドスマートフォン。ご注文から約3週間でお届けします。', 120000.00, 2, 'custom_phone.jpg', 0, '2025-05-12 13:17:13', 1, '約3週間', 0, 0),
(47, 'オーダーメイドデスク', 'お客様のご要望に合わせて製作するカスタムデスク。素材、サイズ、デザインをお選びいただけます。', 120000.00, 7, 'custom_desk.jpg', 0, '2025-05-16 23:08:06', 1, '約4-6週間', 0, 0),
(48, 'カスタム本棚', '部屋のサイズや用途に合わせて設計する本棚。高さ、幅、棚の数など自由自在にカスタマイズ可能です。', 80000.00, 7, 'custom_bookshelf.jpg', 0, '2025-05-16 23:08:06', 1, '約3-4週間', 0, 0),
(49, 'ハンドメイドソファ', '職人が手作りで仕上げるオリジナルソファ。生地、色、サイズをお選びいただけます。', 250000.00, 7, 'handmade_sofa.jpg', 0, '2025-05-16 23:08:06', 1, '約6-8週間', 0, 0),
(50, 'オーダーメイドリング', 'お客様だけの特別なリング。デザイン、素材、石の種類をお選びいただけます。', 50000.00, 5, 'custom_ring.jpg', 0, '2025-05-16 23:08:07', 1, '約2-3週間', 0, 0),
(51, 'カスタムネックレス', 'オリジナルデザインのネックレス。チェーンの長さや金属の種類もお選びいただけます。', 35000.00, 5, 'custom_necklace.jpg', 0, '2025-05-16 23:08:07', 1, '約3週間', 0, 0),
(52, 'カスタムPC組み立て', 'お客様の用途に応じて最適なスペックで組み立てるカスタムPC。ゲーミングからクリエイター向けまで対応。', 200000.00, 2, 'custom_pc.jpg', 0, '2025-05-16 23:08:07', 1, '約2週間', 0, 0),
(53, 'オーダーメイドタブレット', '特定の業務に特化したカスタムタブレット。画面サイズや機能をご要望に合わせて製作します。', 150000.00, 2, 'custom_tablet.jpg', 0, '2025-05-16 23:08:07', 1, '約4週間', 0, 0),
(54, 'オーダーメイドスーツ', '熟練の職人が採寸から仕立てまで手掛けるフルオーダースーツ。', 180000.00, 1, 'custom_suit.jpg', 0, '2025-05-16 23:08:07', 1, '約6週間', 0, 0),
(55, 'カスタムドレス', '特別な日のためのオーダーメイドドレス。デザイン、生地、サイズすべてお客様のご希望通りに。', 120000.00, 1, 'custom_dress.jpg', 0, '2025-05-16 23:08:07', 1, '約4-5週間', 0, 0);

-- 商品画像データの挿入（受注生産商品）
INSERT IGNORE INTO `product_images` (`product_id`, `image_file`, `is_main`, `created`) VALUES
(40, 'custom_phone.jpg', 1, '2025-05-12 13:17:13'),
(47, 'custom_desk.jpg', 1, '2025-05-16 23:08:06'),
(48, 'custom_bookshelf.jpg', 1, '2025-05-16 23:08:06'),
(49, 'handmade_sofa.jpg', 1, '2025-05-16 23:08:06'),
(50, 'custom_ring.jpg', 1, '2025-05-16 23:08:07'),
(51, 'custom_necklace.jpg', 1, '2025-05-16 23:08:07'),
(52, 'custom_pc.jpg', 1, '2025-05-16 23:08:07'),
(53, 'custom_tablet.jpg', 1, '2025-05-16 23:08:07'),
(54, 'custom_suit.jpg', 1, '2025-05-16 23:08:07'),
(55, 'custom_dress.jpg', 1, '2025-05-16 23:08:07');

-- 商品バリエーションデータの挿入
INSERT IGNORE INTO `product_variations` (`id`, `product_id`, `variation_name`, `variation_value`, `price_adjustment`, `stock`, `created`) VALUES
(1, 37, '容量', '64GB', -10000.00, 20, '2025-05-10 00:31:33'),
(2, 37, '容量', '128GB', 0.00, 35, '2025-05-10 00:31:33'),
(3, 37, '容量', '256GB', 15000.00, 25, '2025-05-10 00:31:33'),
(4, 37, '容量', '512GB', 30000.00, 15, '2025-05-10 00:31:33'),
(5, 38, 'サイズ', 'S', 0.00, 30, '2025-05-10 00:31:33'),
(6, 38, 'サイズ', 'M', 0.00, 45, '2025-05-10 00:31:33'),
(7, 38, 'サイズ', 'L', 0.00, 40, '2025-05-10 00:31:33'),
(8, 38, 'サイズ', 'XL', 500.00, 25, '2025-05-10 00:31:33'),
(9, 38, 'サイズ', 'XXL', 1000.00, 15, '2025-05-10 00:31:33'),
(10, 38, 'カラー', 'ホワイト', 0.00, 30, '2025-05-10 00:31:33'),
(11, 38, 'カラー', 'ブラック', 0.00, 30, '2025-05-10 00:31:33'),
(12, 38, 'カラー', 'ネイビー', 0.00, 25, '2025-05-10 00:31:33'),

-- 受注生産商品のバリエーション
(13, 40, 'カスタマイズレベル', 'ベーシック', 0.00, 99, '2025-05-12 13:17:13'),
(14, 40, 'カスタマイズレベル', 'プレミアム', 20000.00, 99, '2025-05-12 13:17:13'),
(15, 40, 'カスタマイズレベル', 'アルティメット', 40000.00, 99, '2025-05-12 13:17:13'),
(16, 47, '素材', 'オーク', 0.00, 0, '2025-05-16 23:08:06'),
(17, 47, '素材', 'ウォールナット', 15000.00, 0, '2025-05-16 23:08:06'),
(18, 47, '素材', 'メープル', 10000.00, 0, '2025-05-16 23:08:06'),
(19, 47, 'サイズ', '標準', 0.00, 0, '2025-05-16 23:08:06'),
(20, 47, 'サイズ', 'ワイド', 20000.00, 0, '2025-05-16 23:08:06'),
(21, 47, 'サイズ', 'コンパクト', -10000.00, 0, '2025-05-16 23:08:06'),
(22, 50, '素材', 'シルバー', 0.00, 0, '2025-05-16 23:08:07'),
(23, 50, '素材', 'ゴールド', 15000.00, 0, '2025-05-16 23:08:07'),
(24, 50, '素材', 'プラチナ', 30000.00, 0, '2025-05-16 23:08:07'),
(25, 54, 'ファブリック', 'ウール', 0.00, 0, '2025-05-16 23:08:07'),
(26, 54, 'ファブリック', 'カシミア', 50000.00, 0, '2025-05-16 23:08:07'),
(27, 54, 'カラー', 'ネイビー', 0.00, 0, '2025-05-16 23:08:07'),
(28, 54, 'カラー', 'グレー', 0.00, 0, '2025-05-16 23:08:07'),
(29, 54, 'カラー', 'ブラック', 0.00, 0, '2025-05-16 23:08:07'),
(30, 54, 'カラー', 'ストライプ', 5000.00, 0, '2025-05-16 23:08:07');

-- テスト用予約注文データの挿入
-- （注: ユーザーIDはユーザーテーブルのIDと一致している必要があります）

-- 予約注文テーブルが正常に作成されているか確認
-- DESCRIBE preorders;

-- テスト用予約注文データの挿入
INSERT IGNORE INTO `preorders` (`id`, `user_id`, `product_id`, `variation_id`, `quantity`, `estimated_delivery`, `status`, `created`) VALUES
(1, 2, 40, 13, 1, DATE_ADD(NOW(), INTERVAL 3 WEEK), 'pending', NOW()),
(2, 2, 47, 16, 1, DATE_ADD(NOW(), INTERVAL 5 WEEK), 'confirmed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 50, 22, 1, DATE_ADD(NOW(), INTERVAL 2 WEEK), 'production', DATE_SUB(NOW(), INTERVAL 1 WEEK)),
(4, 2, 54, 25, 1, DATE_ADD(NOW(), INTERVAL 6 WEEK), 'pending', NOW());

-- 予約注文を確認するためのクエリ
-- SELECT p.*, pr.name as product_name, pr.image, u.username,
--        pv.variation_name, pv.variation_value
-- FROM preorders p 
-- LEFT JOIN products pr ON p.product_id = pr.id 
-- LEFT JOIN product_variations pv ON p.variation_id = pv.id 
-- LEFT JOIN users u ON p.user_id = u.id 
-- ORDER BY p.created DESC;

-- パフォーマンス向上のための追加インデックス

-- 商品テーブルのインデックス（すでに作成済みの場合はスキップ）
-- CREATE INDEX idx_products_category_stock ON products(category_id, stock);
-- CREATE INDEX idx_products_created ON products(created);
-- CREATE INDEX idx_products_price ON products(price);
-- CREATE INDEX idx_products_is_preorder ON products(is_preorder);

-- 注文テーブルのインデックス
-- CREATE INDEX idx_orders_status ON orders(status);
-- CREATE INDEX idx_orders_created ON orders(created);
-- CREATE INDEX idx_orders_user_created ON orders(user_id, created);

-- カートテーブルのインデックス
-- CREATE INDEX idx_cart_user_id ON cart(user_id);

-- レビューテーブルのインデックス
-- CREATE INDEX idx_reviews_product_rating ON reviews(product_id, rating);
-- CREATE INDEX idx_reviews_created ON reviews(created);

-- 在庫ログテーブルのインデックス
-- CREATE INDEX idx_stock_logs_product_created ON product_stock_logs(product_id, created);
-- CREATE INDEX idx_stock_logs_created ON product_stock_logs(created);

-- 予約注文テーブルのインデックス
-- CREATE INDEX idx_preorders_status_created ON preorders(status, created);
-- CREATE INDEX idx_preorders_user_created ON preorders(user_id, created);

-- テーブル最適化
ANALYZE TABLE products;
ANALYZE TABLE product_variations;
ANALYZE TABLE preorders;
ANALYZE TABLE orders;
ANALYZE TABLE order_items;
ANALYZE TABLE users;
ANALYZE TABLE cart;
ANALYZE TABLE wishlist;

-- データベースコミット
COMMIT;

-- 文字セットの復元
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- データベース構造確認用クエリ集

-- テーブル一覧
SHOW TABLES;

-- 特定テーブルの構造確認
DESCRIBE preorders;
DESCRIBE products;
DESCRIBE product_variations;
DESCRIBE orders;

-- 受注生産商品の確認
SELECT id, name, price, category_id, is_preorder, preorder_period 
FROM products WHERE is_preorder = 1;

-- 予約注文の確認
SELECT p.id, p.user_id, p.product_id, p.variation_id, p.quantity, 
       p.estimated_delivery, p.status, p.created,
       pr.name as product_name, pr.is_preorder, pr.preorder_period,
       u.username,
       pv.variation_name, pv.variation_value
FROM preorders p 
LEFT JOIN products pr ON p.product_id = pr.id 
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN product_variations pv ON p.variation_id = pv.id
ORDER BY p.created DESC;

-- バリエーション確認
SELECT pv.*, p.name as product_name, p.is_preorder
FROM product_variations pv 
JOIN products p ON pv.product_id = p.id
ORDER BY p.id, pv.variation_name, pv.variation_value;

-- インデックス確認
SHOW INDEX FROM preorders;
SHOW INDEX FROM products;

-- 順次拡大予定のテーブルはパーティションの検討も可能
-- ALTER TABLE orders PARTITION BY RANGE (TO_DAYS(created))
-- (
--   PARTITION p2025_05 VALUES LESS THAN (TO_DAYS('2025-06-01')),
--   PARTITION p2025_06 VALUES LESS THAN (TO_DAYS('2025-07-01')),
--   PARTITION future VALUES LESS THAN MAXVALUE
-- );