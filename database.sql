-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-05-12 06:22:46
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
  `created` datetime DEFAULT current_timestamp(),
  `variation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created`, `variation_id`) VALUES
(1, '681e2002654fb', 39, 1, '2025-05-10 00:32:18', NULL);

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
(1, '衣類', '様々な種類の衣類商品', '2025-05-09 16:39:06'),
(2, '家電', '便利な家電製品', '2025-05-09 16:39:06'),
(3, '食品', '美味しい食料品', '2025-05-09 16:39:06'),
(4, '書籍', '人気の書籍や雑誌', '2025-05-09 16:39:06'),
(5, '健康・美容', '健康維持と美容のためのアイテム', '2025-05-09 22:39:18'),
(6, 'スポーツ・アウトドア', 'スポーツやアウトドア活動に最適な製品', '2025-05-09 22:39:18'),
(7, 'インテリア・雑貨', '暮らしを彩るインテリアと雑貨', '2025-05-09 22:39:18'),
(8, 'ホビー・ゲーム', '趣味やエンターテイメントのための商品', '2025-05-09 22:39:18');

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

-- --------------------------------------------------------

--
-- テーブルの構造 `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `variation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- テーブルの構造 `preorders`
--

CREATE TABLE `preorders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `status` enum('pending','confirmed','production','shipped','delivered','cancelled') DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created` datetime DEFAULT current_timestamp(),
  `is_preorder` tinyint(1) DEFAULT 0 COMMENT '受注生産フラグ（0: 通常販売, 1: 受注生産）',
  `preorder_period` varchar(100) DEFAULT NULL COMMENT '受注生産期間',
  `min_stock` int(11) DEFAULT 0 COMMENT '最小在庫数',
  `max_stock` int(11) DEFAULT 0 COMMENT '最大在庫数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `products`
--

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
(10, 'キャンバストート', '頑丈な素材で作られた大容量のトートバッグ。日常使いからアウトドアまで活躍します。', 3800.00, 1, 'bag.jpg', 60, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(11, 'ヨガマット', '滑り止め加工が施された環境に優しい素材のヨガマット。', 4500.00, 1, 'yogamat.jpg', 40, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(12, 'ポータブル充電器', '大容量で複数のデバイスを同時に充電できるポータブルバッテリー。', 5800.00, 2, 'charger.jpg', 45, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(13, 'デジタルノート', 'メモを取るとスマホと同期できるスマートなデジタルノート。', 7200.00, 4, 'notebook.jpg', 30, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(14, 'アロマディフューザー', '静音設計で寝室にも最適な超音波式アロマディフューザー。', 4300.00, 2, 'diffuser.jpg', 25, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(15, 'エッセンシャルオイルセット', 'リラックス効果のある厳選されたエッセンシャルオイル6種セット。', 4500.00, 3, 'essential_oils.jpg', 35, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(16, 'スタイリッシュなデスクチェア', '長時間のデスクワークでも快適な姿勢を保てる人間工学に基づいたデザインのオフィスチェア。', 25000.00, 1, 'chair.jpg', 15, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(17, 'プログラミング実践ガイド', 'プログラミングの基礎から応用まで幅広く学べる実践的な入門書。', 3500.00, 4, 'programming_book.jpg', 50, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(18, 'ワイヤレスキーボード', 'タイピング音が静かで操作感が良いワイヤレスキーボード。バッテリー寿命も長く、複数のデバイスとペアリング可能。', 8000.00, 2, 'keyboard.jpg', 30, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(19, 'オーガニックコーヒー豆セット', '世界各国から厳選した有機栽培のコーヒー豆セット。自宅で本格的なコーヒーを楽しめます。', 2800.00, 3, 'coffee_beans.jpg', 40, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(20, 'フィットネストラッカー', '心拍数や睡眠の質を測定し、健康管理をサポートするスマートバンド。', 7500.00, 2, 'fitness_tracker.jpg', 25, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(21, 'リュックサック', '耐水性があり、ラップトップも収納できる多機能リュックサック。通勤や旅行に最適。', 6200.00, 1, 'backpack.jpg', 20, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(22, 'スマートLED電球', 'スマートフォンで色や明るさを調整できるLED電球。音声アシスタントとも連携可能。', 3800.00, 2, 'smart_bulb.jpg', 60, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(23, 'フルーツティーアソート', '天然のフルーツを使用した香り豊かなティーアソートセット。', 1500.00, 3, 'fruit_tea.jpg', 70, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(24, 'モバイルスタンド', 'スマートフォンやタブレットを最適な角度で固定できる折りたたみ式スタンド。', 1200.00, 2, 'mobile_stand.jpg', 100, '2025-05-09 16:39:07', 0, NULL, 0, 0),
(25, 'オーガニックフェイスクリーム', '天然成分100%の保湿効果の高いフェイスクリーム。敏感肌の方にもおすすめです。', 3800.00, 5, 'face_cream.jpg', 45, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(26, 'ヨガセット', 'ヨガマット、ブロック、ストラップがセットになったヨガ初心者向けキット。', 5500.00, 5, 'yoga_set.jpg', 30, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(27, '携帯用空気清浄機', '持ち運び可能なコンパクトサイズの空気清浄機。USB充電式で場所を選ばず使用できます。', 7800.00, 5, 'air_purifier.jpg', 25, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(28, '折りたたみ自転車', '軽量で持ち運びやすい折りたたみ自転車。通勤や旅行に最適です。', 29800.00, 6, 'folding_bike.jpg', 15, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(29, 'キャンプテント', '2〜3人用の防水加工されたキャンプテント。設営が簡単で初心者にもおすすめ。', 12500.00, 6, 'tent.jpg', 20, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(30, 'トレッキングポール', '軽量で丈夫なアルミニウム製トレッキングポール。長さ調節可能で様々な地形に対応。', 4500.00, 6, 'trekking_poles.jpg', 40, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(31, '北欧風ベッドサイドランプ', 'シンプルでスタイリッシュな北欧デザインのベッドサイドランプ。柔らかな光で寝室を演出します。', 6200.00, 7, 'bedside_lamp.jpg', 30, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(32, 'マルチ収納ボックス', '折りたたみ可能な布製収納ボックス。カラフルなデザインでインテリアのアクセントにも。', 1800.00, 7, 'storage_box.jpg', 50, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(33, '観葉植物セット', '育てやすい小型の観葉植物3種セット。鉢付きでインテリアにすぐに馴染みます。', 5400.00, 7, 'plants.jpg', 25, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(34, 'ボードゲームコレクション', '人気のボードゲーム3種セット。家族や友人との時間を楽しく過ごせます。', 8900.00, 8, 'board_games.jpg', 20, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(35, 'デジタルイラストタブレット', 'イラスト制作に最適なペン付きグラフィックタブレット。感度調整可能で初心者から上級者まで対応。', 15800.00, 8, 'drawing_tablet.jpg', 15, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(36, 'ミニドローン', '初心者向けの小型ドローン。室内飛行も可能で、カメラ付きでスマホと連携できます。', 9800.00, 8, 'mini_drone.jpg', 30, '2025-05-09 22:39:18', 0, NULL, 0, 0),
(37, 'プレミアムスマートフォン', '最新技術を搭載した高性能スマートフォン。美しいディスプレイと高速プロセッサを備えています。', 75000.00, 2, 'smartphone.jpg', 0, '2025-05-10 00:31:32', 0, NULL, 0, 0),
(38, 'プレミアムコットンTシャツ', '100%オーガニックコットンを使用した高品質なTシャツ。肌触りが良く、耐久性にも優れています。', 3500.00, 1, 'premium_tshirt.jpg', 0, '2025-05-10 00:31:33', 0, NULL, 0, 0),
(39, 'ウルトラスリムノートPC', '薄型軽量で持ち運びに便利なノートパソコン。長時間バッテリーと高性能プロセッサを搭載。', 95000.00, 2, 'laptop.jpg', 0, '2025-05-10 00:31:33', 0, NULL, 0, 0),
(40, 'カスタムスマートフォン', 'お客様のご要望に合わせてカスタマイズ可能なオーダーメイドスマートフォン。ご注文から約3週間でお届けします。', 120000.00, 2, 'custom_phone.jpg', 0, '2025-05-12 13:17:13', 1, '約3週間', 0, 0),
(41, 'プロフェッショナルヘッドホン', 'プロの音楽制作者にも愛用される高音質ヘッドホン。', 28000.00, 2, 'pro_headphones.jpg', 25, '2025-05-12 13:17:13', 0, NULL, 0, 0),
(42, 'アートブック・コレクション', '世界の名画を収録した美術書セット。', 15000.00, 4, 'art_books.jpg', 12, '2025-05-12 13:17:13', 0, NULL, 0, 0),
(43, 'オーガニック・ハニーセット', '国産の純粋はちみつ6種類のセット。', 8500.00, 3, 'honey_set.jpg', 8, '2025-05-12 13:17:13', 0, NULL, 0, 0);

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
(12, 12, 'charger.jpg', 1, '2025-05-09 16:39:07'),
(13, 13, 'notebook.jpg', 1, '2025-05-09 16:39:07'),
(14, 14, 'diffuser.jpg', 1, '2025-05-09 16:39:07'),
(15, 15, 'essential_oils.jpg', 1, '2025-05-09 16:39:07'),
(16, 16, 'chair.jpg', 1, '2025-05-09 16:39:07'),
(17, 17, 'programming_book.jpg', 1, '2025-05-09 16:39:07'),
(18, 18, 'keyboard.jpg', 1, '2025-05-09 16:39:07'),
(19, 19, 'coffee_beans.jpg', 1, '2025-05-09 16:39:07'),
(20, 20, 'fitness_tracker.jpg', 1, '2025-05-09 16:39:07'),
(21, 21, 'backpack.jpg', 1, '2025-05-09 16:39:07'),
(22, 22, 'smart_bulb.jpg', 1, '2025-05-09 16:39:07'),
(23, 23, 'fruit_tea.jpg', 1, '2025-05-09 16:39:07'),
(24, 24, 'mobile_stand.jpg', 1, '2025-05-09 16:39:07'),
(38, 16, 'chair_2.jpg', 0, '2025-05-09 17:23:28'),
(39, 18, 'keyboard_2.jpg', 0, '2025-05-09 17:27:58'),
(40, 19, 'coffee_beans_2.jpg', 0, '2025-05-09 17:34:15'),
(41, 23, 'fruit_tea_2.jpg', 0, '2025-05-09 17:43:05'),
(42, 37, 'smartphone.jpg', 1, '2025-05-10 00:31:33'),
(45, 38, 'premium_tshirt.jpg', 1, '2025-05-10 00:31:33'),
(48, 39, 'laptop.jpg', 1, '2025-05-10 00:31:33'),
(51, 40, 'custom_phone.jpg', 1, '2025-05-12 13:17:13'),
(52, 40, 'custom_phone_2.jpg', 0, '2025-05-12 13:17:13'),
(53, 41, 'pro_headphones.jpg', 1, '2025-05-12 13:17:13'),
(54, 41, 'pro_headphones_2.jpg', 0, '2025-05-12 13:17:13'),
(55, 43, 'honey_set.jpg', 1, '2025-05-12 13:17:13');

-- --------------------------------------------------------

--
-- テーブルの構造 `product_stock_logs`
--

CREATE TABLE `product_stock_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `type` enum('in','out','adjust') NOT NULL COMMENT '入庫、出庫、調整',
  `quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `product_variations`
--

CREATE TABLE `product_variations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_name` varchar(100) NOT NULL,
  `variation_value` varchar(100) NOT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `product_variations`
--

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
(10, 39, 'モデル', 'スタンダード（Core i5/8GB/256GB）', -15000.00, 30, '2025-05-10 00:31:33'),
(11, 39, 'モデル', 'パフォーマンス（Core i7/16GB/512GB）', 0.00, 25, '2025-05-10 00:31:33'),
(12, 39, 'モデル', 'プロフェッショナル（Core i9/32GB/1TB）', 35000.00, 15, '2025-05-10 00:31:33'),
(13, 40, 'カスタマイズレベル', 'ベーシック', 0.00, 99, '2025-05-12 13:17:13'),
(14, 40, 'カスタマイズレベル', 'プレミアム', 20000.00, 99, '2025-05-12 13:17:13'),
(15, 40, 'カスタマイズレベル', 'アルティメット', 40000.00, 99, '2025-05-12 13:17:13'),
(16, 41, 'カラー', 'ブラック', 0.00, 15, '2025-05-12 13:17:13'),
(17, 41, 'カラー', 'ホワイト', 0.00, 10, '2025-05-12 13:17:13'),
(18, 41, 'カラー', 'シルバー', 2000.00, 5, '2025-05-12 13:17:13'),
(19, 43, 'セット内容', 'スタンダード（6種）', 0.00, 8, '2025-05-12 13:17:13'),
(20, 43, 'セット内容', 'デラックス（12種）', 7500.00, 3, '2025-05-12 13:17:13');

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
(1, 'admin', 'admin@example.com', '$2y$10$8KbM0ZvTXR7MZnG0QL5gGOLR6vO.TSoihFnM7qwXjUY3qSN8EQIfK', 1, '2025-05-09 16:39:07'),
(2, 'testuser', 'user@example.com', '$2y$10$IQJahDxZUVwupeWCbv1QZer2hcVAB6BwNIftcXyYNAiNXjSvkOPXG', 0, '2025-05-09 16:39:07'),
(3, 'a', 'user@gmail.com', '$2y$10$tjATxAIlOOPveR8DcqM.CudokyyyfPt1pi8s9BPm9miYKxXB9zRpa', 0, '2025-05-10 00:33:47');

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
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variation_id` (`variation_id`);

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
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variation_id` (`variation_id`);

--
-- テーブルのインデックス `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- テーブルのインデックス `preorders`
--
ALTER TABLE `preorders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variation_id` (`variation_id`);

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
-- テーブルのインデックス `product_stock_logs`
--
ALTER TABLE `product_stock_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variation_id` (`variation_id`);

--
-- テーブルのインデックス `product_variations`
--
ALTER TABLE `product_variations`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `preorders`
--
ALTER TABLE `preorders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- テーブルの AUTO_INCREMENT `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- テーブルの AUTO_INCREMENT `product_stock_logs`
--
ALTER TABLE `product_stock_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `product_variations`
--
ALTER TABLE `product_variations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- テーブルの AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`);

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
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`);

--
-- テーブルの制約 `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- テーブルの制約 `preorders`
--
ALTER TABLE `preorders`
  ADD CONSTRAINT `preorders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `preorders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `preorders_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`);

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
-- テーブルの制約 `product_stock_logs`
--
ALTER TABLE `product_stock_logs`
  ADD CONSTRAINT `product_stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_stock_logs_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`);

--
-- テーブルの制約 `product_variations`
--
ALTER TABLE `product_variations`
  ADD CONSTRAINT `product_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
