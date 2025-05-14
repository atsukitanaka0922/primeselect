<?php
// admin/templates/footer.php の修正版
?>
    </main>
    
    <!-- フッター -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Prime Select 管理パネル. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries - 確実に読み込み -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
    
    <!-- jQueryとBootstrapの読み込み確認 -->
    <script>
    $(document).ready(function() {
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap version loaded');
    });
    </script>
</body>
</html>