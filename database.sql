-- データベース作成
CREATE DATABASE IF NOT EXISTS `ecommerce_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ecommerce_db`;

-- 文字セットと照合順序の設定
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- 商品テーブル
CREATE TABLE `products` (
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
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品画像テーブル
CREATE TABLE `product_images` (
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
CREATE TABLE `product_variations` (
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
CREATE TABLE `product_stock_logs` (
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
  CONSTRAINT `product_stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_logs_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- カテゴリテーブル
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- カテゴリデータの挿入
INSERT INTO `categories` (`id`, `name`, `description`, `created`) VALUES
(1, '衣類', '様々な種類の衣類商品', '2025-05-09 16:39:06'),
(2, '家電', '便利な家電製品', '2025-05-09 16:39:06'),
(3, '食品', '美味しい食料品', '2025-05-09 16:39:06'),
(4, '書籍', '人気の書籍や雑誌', '2025-05-09 16:39:06'),
(5, '健康・美容', '健康維持と美容のためのアイテム', '2025-05-09 22:39:18'),
(6, 'スポーツ・アウトドア', 'スポーツやアウトドア活動に最適な製品', '2025-05-09 22:39:18'),
(7, 'インテリア・雑貨', '暮らしを彩るインテリアと雑貨', '2025-05-09 22:39:18'),
(8, 'ホビー・ゲーム', '趣味やエンターテイメントのための商品', '2025-05-09 22:39:18');

-- 商品テーブル
CREATE TABLE `products` (
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
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品画像テーブル
CREATE TABLE `product_images` (
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
CREATE TABLE `product_variations` (
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
CREATE TABLE `product_stock_logs` (
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
  CONSTRAINT `product_stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_logs_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- カートテーブル
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created` datetime DEFAULT current_timestamp(),
  `variation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ウィッシュリストテーブル
CREATE TABLE `wishlist` (
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
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 注文アイテムテーブル
CREATE TABLE `order_items` (
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
CREATE TABLE `payments` (
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
CREATE TABLE `preorders` (
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
  CONSTRAINT `preorders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `preorders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `preorders_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- レビューテーブル
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 商品データの挿入
INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `stock`, `created`, `is_preorder`, `preorder_period`, `min_stock`, `max_stock`) VALUES
(1, 'Tシャツ', '高品質の綿100%Tシャツ。様々なサイズとカラーでご用意しています。', 2500.00, 1, 'tshirt.jpg', 100, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(2, 'デニムジーンズ', '丈夫で長持ちするデニムジーンズ。カジュアルスタイルに最適です。', 5000.00, 1, 'jeans.jpg', 50, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(3, 'スマートテレビ', '4K解像度の42インチスマートテレビ。様々なストリーミングサービスに対応。', 60000.00, 2, 'tv.jpg', 20, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(4, 'コーヒーメーカー', '本格的なエスプレッソを自宅で簡単に作れるコーヒーメーカー。', 15000.00, 2, 'coffee.jpg', 30, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(5, 'オーガニックチョコレート', '高カカオ含有のオーガニックチョコレート。砂糖控えめで健康志向の方に。', 800.00, 3, 'chocolate.jpg', 200, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(6, 'プログラミング入門書', 'プログラミングを基礎から学べる入門書。初心者向けの解説が充実。', 3000.00, 4, 'book.jpg', 40, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(7, 'ワイヤレスイヤホン', '高音質で長時間再生可能なワイヤレスイヤホン。防水機能付きでスポーツにも最適です。', 8500.00, 2, 'earphones.jpg', 35, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(8, 'スマートウォッチ', '健康管理や通知確認ができる多機能スマートウォッチ。バッテリー持ちも抜群です。', 12500.00, 2, 'smartwatch.jpg', 25, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(9, 'オーガニックティー', '有機栽培のハーブを使用した、リラックス効果のあるハーブティー。', 1200.00, 3, 'tea.jpg', 80, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(10, 'キャンバストート', '頑丈な素材で作られた大容量のトートバッグ。日常使いからアウトドアまで活躍します。', 3800.00, 1, 'bag.jpg', 60, '2025-05-09 16:39:07', 0, NULL, 0, 0);

-- バリエーションを持つ商品の追加
INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `stock`, `created`, `is_preorder`, `preorder_period`, `min_stock`, `max_stock`) VALUES
(37, 'プレミアムスマートフォン', '最新技術を搭載した高性能スマートフォン。美しいディスプレイと高速プロセッサを備えています。', 75000.00, 2, 'smartphone.jpg', 0, '2025-05-10 00:31:32', 0, NULL, 0, 0),
(38, 'プレミアムコットンTシャツ', '100%オーガニックコットンを使用した高品質なTシャツ。肌触りが良く、耐久性にも優れています。', 3500.00, 1, 'premium_tshirt.jpg', 0, '2025-05-10 00:31:33', 0, NULL, 0, 0),
(40, 'カスタムスマートフォン', 'お客様のご要望に合わせてカスタマイズ可能なオーダーメイドスマートフォン。ご注文から約3週間でお届けします。', 120000.00, 2, 'custom_phone.jpg', 0, '2025-05-12 13:17:13', 1, '約3週間', 0, 0);

-- 商品画像データの挿入
INSERT INTO `product_images` (`id`, `product_id`, `image_file`, `is_main`, `created`) VALUES
(1, 1, 'tshirt.jpg', 1, '2025-05-09 16:39:07'),
(2, 2, 'jeans.jpg', 1, '2025-05-09 16:39:07'),
(3, 3, 'tv.jpg', 1, '2025-05-09 16:39:07'),
(4, 4, 'coffee.jpg', 1, '2025-05-09 16:39:07'),
(5, 5, 'chocolate.jpg', 1, '2025-05-09 16:39:07'),
(6, 6, 'book.jpg', 1, '2025-05-09 16:39:07'),
(42, 37, 'smartphone.jpg', 1, '2025-05-10 00:31:33'),
(45, 38, 'premium_tshirt.jpg', 1, '2025-05-10 00:31:33'),
(51, 40, 'custom_phone.jpg', 1, '2025-05-12 13:17:13');

-- 商品バリエーションデータの挿入
INSERT INTO `product_variations` (`id`, `product_id`, `variation_name`, `variation_value`, `price_adjustment`, `stock`, `created`) VALUES
(1, 37, '容量', '64GB', -10000.00, 20, '2025-05-10 00:31:33'),
(2, 37, '容量', '128GB', 0.00, 35, '2025-05-10 00:31:33'),
(3, 37, '容量', '256GB', 15000.00, 25, '2025-05-10 00:31:33'),
(4, 37, '容量', '512GB', 30000.00, 15, '2025-05-10 00:31:33'),
(5, 38, 'サイズ', 'S', 0.00, 30, '2025-05-10 00:31:33'),
(6, 38, 'サイズ', 'M', 0.00, 45, '2025-05-10 00:31:33'),
(7, 38, 'サイズ', 'L', 0.00, 40, '2025-05-10 00:31:33'),
(8, 38, 'サイズ', 'XL', 500.00, 25, '2025-05-10 00:31:33'),
(9, 38, 'サイズ', 'XXL', 1000.00, 15, '2025-05-10 00:31:33'),
(13, 40, 'カスタマイズレベル', 'ベーシック', 0.00, 99, '2025-05-12 13:17:13'),
(14, 40, 'カスタマイズレベル', 'プレミアム', 20000.00, 99, '2025-05-12 13:17:13'),
(15, 40, 'カスタマイズレベル', 'アルティメット', 40000.00, 99, '2025-05-12 13:17:13');

-- パフォーマンス向上のための追加インデックス

-- 商品テーブルのインデックス
CREATE INDEX idx_products_category_stock ON products(category_id, stock);
CREATE INDEX idx_products_created ON products(created);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_is_preorder ON products(is_preorder);

-- 注文テーブルのインデックス
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created);
CREATE INDEX idx_orders_user_created ON orders(user_id, created);

-- カートテーブルのインデックス
CREATE INDEX idx_cart_user_id ON cart(user_id);

-- レビューテーブルのインデックス
CREATE INDEX idx_reviews_product_rating ON reviews(product_id, rating);
CREATE INDEX idx_reviews_created ON reviews(created);

-- 在庫ログテーブルのインデックス
CREATE INDEX idx_stock_logs_product_created ON product_stock_logs(product_id, created);
CREATE INDEX idx_stock_logs_created ON product_stock_logs(created);

-- 予約注文テーブルのインデックス
CREATE INDEX idx_preorders_status_created ON preorders(status, created);
CREATE INDEX idx_preorders_user_created ON preorders(user_id, created);

-- 最終設定とコミット
COMMIT;

-- 文字セットの復元
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;