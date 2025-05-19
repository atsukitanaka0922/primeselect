<?php
/**
 * templates/footer.php - フッターテンプレート
 * 
 * すべてのページで使用される共通のフッター部分を定義します。
 * サイト情報、サイトマップ、連絡先情報、コピーライトなどが含まれます。
 * また、JavaScript読み込みも行います。
 * 
 * 機能：
 * - サイト情報表示
 * - サイトマップ（主要リンク）
 * - 連絡先情報
 * - コピーライト表示
 * - JavaScript読み込み（jQuery、Bootstrap、カスタムJS）
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
?>
</main>
    <!-- フッター部分開始 -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <!-- サイト情報 -->
                <div class="col-md-4">
                    <h5>Prime Select</h5>
                    <p>高品質な商品を厳選してお届けします。</p>
                </div>
                
                <!-- サイトマップ -->
                <div class="col-md-4">
                    <h5>ページ</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">ホーム</a></li>
                        <li><a href="shop.php" class="text-white">商品一覧</a></li>
                        <li><a href="contact.php" class="text-white">お問い合わせ</a></li>
                        <li><a href="about.php" class="text-white">会社概要</a></li>
                    </ul>
                </div>
                
                <!-- 連絡先情報 -->
                <div class="col-md-4">
                    <h5>お問い合わせ</h5>
                    <address>
                        〒123-4567<br>
                        東京都渋谷区〇〇1-2-3<br>
                        <i class="fas fa-phone"></i> 03-1234-5678<br>
                        <i class="fas fa-envelope"></i> info@example.com
                    </address>
                </div>
            </div>
            
            <!-- コピーライト -->
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Prime Select. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript読み込み -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>