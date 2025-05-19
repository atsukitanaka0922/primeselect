<?php
/**
 * admin/templates/footer.php - 管理者用フッターテンプレート
 * 
 * 管理画面フッター部分の定義。
 * すべての管理ページの下部に表示される共通部分です。
 * 
 * 含まれる内容:
 * - コピーライト表示
 * - 閉じタグなど
 * 
 * 注意点:
 * - メインコンテンツの終了タグ</main>があることを確認してください
 * - JavaScriptライブラリは重複読み込みを避けるため、各ページで個別に読み込みます
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.1
 */
?>
    </main>
    
    <!-- フッター（シンプル） -->
    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container-fluid">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Prime Select 管理パネル. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- 
    注意: JavaScriptライブラリは各ページで個別に読み込む
    重複読み込みを避けるため、ここでは読み込まない
    -->
</body>
</html>