<?php
/**
 * includes/paging.php - ページネーションヘルパー
 * 
 * ページネーションのUI生成を行うヘルパー関数を提供します。
 * BootstrapのページネーションUIに合わせたHTMLを生成します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

/**
 * ページネーションヘルパー関数
 * 
 * ページネーションのUI生成を行います。
 * 前後のページへのリンク、現在のページの強調表示を含みます。
 * 
 * @param int $page 現在のページ番号
 * @param int $total_rows 総レコード数
 * @param int $records_per_page 1ページあたりのレコード数
 * @param string $page_url ページベースURL
 * @return string ページネーションHTML
 */
function getPaging($page, $total_rows, $records_per_page, $page_url) {
    // ページネーション情報配列
    $paging_arr = array();
    
    // 総ページ数計算
    $total_pages = ceil($total_rows / $records_per_page);
    
    // 0件の場合はページネーション表示しない
    if ($total_pages <= 0) {
        return '';
    }
    
    // クエリ文字列の処理
    $page_url = preg_replace('/&page=[0-9]+/', '', $page_url);
    $separator = (parse_url($page_url, PHP_URL_QUERY) === null) ? '?' : '&';
    
    // 現在のページ
    $paging_arr["current_page"] = $page;
    
    // 前後に表示するページリンクの数
    $max_links = 2;
    
    // 前のページがあるか
    if($page > 1) {
        $paging_arr["previous"] = $page - 1;
    } else {
        $paging_arr["previous"] = null;
    }
    
    // 次のページがあるか
    if($page < $total_pages) {
        $paging_arr["next"] = $page + 1;
    } else {
        $paging_arr["next"] = null;
    }
    
    // ページ範囲の計算
    $start_page = max(1, $page - $max_links);
    $end_page = min($total_pages, $page + $max_links);
    
    // ページ配列
    $paging_arr["pages"] = array();
    for($i = $start_page; $i <= $end_page; $i++) {
        $paging_arr["pages"][] = $i;
    }
    
    // HTMLの生成
    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // 前へボタン
    if($paging_arr["previous"] !== null) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $page_url . $separator . 'page=' . $paging_arr["previous"] . '">前へ</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">前へ</a></li>';
    }
    
    // ページ番号
    foreach($paging_arr["pages"] as $p) {
        if($p == $page) {
            // 現在のページは強調表示
            $html .= '<li class="page-item active"><a class="page-link" href="#">' . $p . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $page_url . $separator . 'page=' . $p . '">' . $p . '</a></li>';
        }
    }
    
    // 次へボタン
    if($paging_arr["next"] !== null) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $page_url . $separator . 'page=' . $paging_arr["next"] . '">次へ</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">次へ</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * 改善提案:
 * 
 * 1. 最初と最後のページへのリンクを追加
 * 2. ページが多い場合の省略表示（...）の追加
 * 3. 表示するレコード数を変更するオプションの追加
 * 4. 総レコード数と表示ページの情報表示
 * 5. AJAX対応（ページ遷移時の非同期読み込み）
 * 6. URLパラメータを維持したままページ切り替え機能の強化
 */