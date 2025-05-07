-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-05-07 17:19:00
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created`) VALUES
(2, '681b18632d917', 5, 1, '2025-05-07 17:22:59'),
(3, '681b18632d917', 6, 1, '2025-05-07 17:23:05');

-- --------------------------------------------------------

--
-- テーブルの構造 `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created`) VALUES
(1, '衣類', '様々な種類の衣類商品', '2025-05-07 11:36:57'),
(2, '家電', '便利な家電製品', '2025-05-07 11:36:57'),
(3, '食品', '美味しい食料品', '2025-05-07 11:36:57'),
(4, '書籍', '人気の書籍や雑誌', '2025-05-07 11:36:57');

-- --------------------------------------------------------

--
-- テーブルの構造 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `payment_method`, `status`, `created`) VALUES
(1, 2, 800.00, '', 'credit_card', 'cancelled', '2025-05-07 17:24:15');

-- --------------------------------------------------------

--
-- テーブルの構造 `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 5, 1, 800.00);

-- --------------------------------------------------------

--
-- テーブルの構造 `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `payment_status`, `transaction_id`, `created`) VALUES
(1, 1, 'credit_card', 'completed', 'DEMO_681b18af5f906', '2025-05-07 17:24:15');

-- --------------------------------------------------------

--
-- テーブルの構造 `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `stock`, `created`) VALUES
(1, 'Tシャツ', '高品質の綿100%Tシャツ。様々なサイズとカラーでご用意しています。', 2500.00, 1, 'tshirt.jpg', 100, '2025-05-07 11:36:57'),
(2, 'デニムジーンズ', '丈夫で長持ちするデニムジーンズ。カジュアルスタイルに最適です。', 5000.00, 1, 'jeans.jpg', 50, '2025-05-07 11:36:57'),
(3, 'スマートテレビ', '4K解像度の42インチスマートテレビ。様々なストリーミングサービスに対応。', 60000.00, 2, 'tv.jpg', 20, '2025-05-07 11:36:57'),
(4, 'コーヒーメーカー', '本格的なエスプレッソを自宅で簡単に作れるコーヒーメーカー。', 15000.00, 2, 'coffee.jpg', 30, '2025-05-07 11:36:57'),
(5, 'オーガニックチョコレート', '高カカオ含有のオーガニックチョコレート。砂糖控えめで健康志向の方に。', 800.00, 3, 'chocolate.jpg', 200, '2025-05-07 11:36:57'),
(6, 'プログラミング入門書', 'プログラミングを基礎から学べる入門書。初心者向けの解説が充実。', 3000.00, 4, 'book.jpg', 40, '2025-05-07 11:36:57'),
(7, 'ワイヤレスイヤホン', '高音質で長時間再生可能なワイヤレスイヤホン。防水機能付きでスポーツにも最適です。', 8500.00, 2, 'earphones.jpg', 35, '2025-05-07 21:48:25'),
(8, 'スマートウォッチ', '健康管理や通知確認ができる多機能スマートウォッチ。バッテリー持ちも抜群です。', 12500.00, 2, 'smartwatch.jpg', 25, '2025-05-07 21:48:25'),
(9, 'オーガニックティー', '有機栽培のハーブを使用した、リラックス効果のあるハーブティー。', 1200.00, 3, 'tea.jpg', 80, '2025-05-07 21:48:25'),
(10, 'キャンバストート', '頑丈な素材で作られた大容量のトートバッグ。日常使いからアウトドアまで活躍します。', 3800.00, 1, 'bag.jpg', 60, '2025-05-07 21:48:25'),
(11, 'ヨガマット', '滑り止め加工が施された環境に優しい素材のヨガマット。', 4500.00, 1, 'yogamat.jpg', 40, '2025-05-07 21:48:25'),
(12, 'ポータブル充電器', '大容量で複数のデバイスを同時に充電できるポータブルバッテリー。', 5800.00, 2, 'charger.jpg', 45, '2025-05-07 21:48:25'),
(13, 'デジタルノート', 'メモを取るとスマホと同期できるスマートなデジタルノート。', 7200.00, 4, 'notebook.jpg', 30, '2025-05-07 21:48:25'),
(14, 'アロマディフューザー', '静音設計で寝室にも最適な超音波式アロマディフューザー。', 4300.00, 2, 'diffuser.jpg', 25, '2025-05-07 21:48:25'),
(15, 'スタイリッシュなデスクチェア', '長時間のデスクワークでも快適な姿勢を保てる人間工学に基づいたデザインのオフィスチェア。', 25000.00, 1, 'chair.jpg', 15, '2025-05-07 22:36:37'),
(16, 'プログラミング実践ガイド', 'プログラミングの基礎から応用まで幅広く学べる実践的な入門書。', 3500.00, 4, 'programming_book.jpg', 50, '2025-05-07 22:36:37'),
(17, 'ワイヤレスキーボード', 'タイピング音が静かで操作感が良いワイヤレスキーボード。バッテリー寿命も長く、複数のデバイスとペアリング可能。', 8000.00, 2, 'keyboard.jpg', 30, '2025-05-07 22:36:37'),
(18, 'オーガニックコーヒー豆セット', '世界各国から厳選した有機栽培のコーヒー豆セット。自宅で本格的なコーヒーを楽しめます。', 2800.00, 3, 'coffee_beans.jpg', 40, '2025-05-07 22:36:37'),
(19, 'フィットネストラッカー', '心拍数や睡眠の質を測定し、健康管理をサポートするスマートバンド。', 7500.00, 2, 'fitness_tracker.jpg', 25, '2025-05-07 22:36:37'),
(20, 'リュックサック', '耐水性があり、ラップトップも収納できる多機能リュックサック。通勤や旅行に最適。', 6200.00, 1, 'backpack.jpg', 20, '2025-05-07 22:36:37'),
(21, 'スマートLED電球', 'スマートフォンで色や明るさを調整できるLED電球。音声アシスタントとも連携可能。', 3800.00, 2, 'smart_bulb.jpg', 60, '2025-05-07 22:36:37'),
(22, 'フルーツティーアソート', '天然のフルーツを使用した香り豊かなティーアソートセット。', 1500.00, 3, 'fruit_tea.jpg', 70, '2025-05-07 22:36:37'),
(23, 'モバイルスタンド', 'スマートフォンやタブレットを最適な角度で固定できる折りたたみ式スタンド。', 1200.00, 2, 'mobile_stand.jpg', 100, '2025-05-07 22:36:37'),
(24, 'エッセンシャルオイルセット', 'リラックス効果のある厳選されたエッセンシャルオイル6種セット。', 4500.00, 3, 'essential_oils.jpg', 35, '2025-05-07 22:36:37');

-- --------------------------------------------------------

--
-- テーブルの構造 `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_file`, `is_main`, `created`) VALUES
(1, 1, 'tshirt.jpg', 1, '2025-05-07 22:37:33'),
(2, 2, 'jeans.jpg', 1, '2025-05-07 22:37:33'),
(3, 3, 'tv.jpg', 1, '2025-05-07 22:37:33'),
(4, 4, 'coffee.jpg', 1, '2025-05-07 22:37:33'),
(5, 5, 'chocolate.jpg', 1, '2025-05-07 22:37:33'),
(6, 6, 'book.jpg', 1, '2025-05-07 22:37:33'),
(7, 7, 'earphones.jpg', 1, '2025-05-07 22:37:33'),
(8, 8, 'smartwatch.jpg', 1, '2025-05-07 22:37:33'),
(9, 9, 'tea.jpg', 1, '2025-05-07 22:37:33'),
(10, 10, 'bag.jpg', 1, '2025-05-07 22:37:33'),
(11, 11, 'yogamat.jpg', 1, '2025-05-07 22:37:33'),
(12, 12, 'charger.jpg', 1, '2025-05-07 22:37:33'),
(13, 13, 'notebook.jpg', 1, '2025-05-07 22:37:33'),
(14, 14, 'diffuser.jpg', 1, '2025-05-07 22:37:33'),
(15, 15, 'chair.jpg', 1, '2025-05-07 22:37:33'),
(16, 16, 'programming_book.jpg', 1, '2025-05-07 22:37:33'),
(17, 17, 'keyboard.jpg', 1, '2025-05-07 22:37:33'),
(18, 18, 'coffee_beans.jpg', 1, '2025-05-07 22:37:33'),
(19, 19, 'fitness_tracker.jpg', 1, '2025-05-07 22:37:33'),
(20, 20, 'backpack.jpg', 1, '2025-05-07 22:37:33'),
(21, 21, 'smart_bulb.jpg', 1, '2025-05-07 22:37:33'),
(22, 22, 'fruit_tea.jpg', 1, '2025-05-07 22:37:33'),
(23, 23, 'mobile_stand.jpg', 1, '2025-05-07 22:37:33'),
(24, 24, 'essential_oils.jpg', 1, '2025-05-07 22:37:33');

-- --------------------------------------------------------

--
-- テーブルの構造 `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `created`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$8KbM0ZvTXR7MZnG0QL5gGOLR6vO.TSoihFnM7qwXjUY3qSN8EQIfK', 1, '2025-05-07 11:36:57'),
(2, '', '', '', 0, '2025-05-07 17:23:27');

-- --------------------------------------------------------

--
-- テーブルの構造 `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- テーブルのインデックス `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- テーブルのインデックス `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- テーブルのインデックス `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- テーブルのインデックス `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- テーブルのインデックス `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- テーブルのインデックス `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- テーブルの AUTO_INCREMENT `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- テーブルの AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- テーブルの制約 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- テーブルの制約 `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- テーブルの制約 `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- テーブルの制約 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- テーブルの制約 `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- テーブルの制約 `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
