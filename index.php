<?php
/**
 * Plugin Name: LLMs.txt Generator for WordPress
 * Plugin URI: https://github.com/KuboFumiaki/llms-txt-generator
 * Description: WordPressサイトのコンテンツからLLMS.txtファイルを自動生成するプラグインです。投稿、カスタム投稿タイプ、カテゴリ情報を含むマークダウン形式のファイルを生成し、LLMsがサイト内容を理解するのに役立ちます。
 * Version: 1.0.0
 * Author: Kubo Fumiaki
 * Author URI: 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: llms-txt-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.3
 */

// WordPressが読み込まれているかチェック
if (!defined('ABSPATH')) {
    exit; // WordPressが読み込まれていない場合は終了
}

// プラグインの有効化時に実行される処理
function llms_txt_generator_activate() {
    // デフォルト設定を作成
    if (!get_option('llms_encoding')) {
        add_option('llms_encoding', 'SJIS');
    }
    
    if (!get_option('llms_custom_text')) {
        $default_text = "# " . get_bloginfo('name') . "\n\n" . get_bloginfo('description') . "\n\n";
        add_option('llms_custom_text', $default_text);
    }
    
    if (!get_option('llms_post_type_settings')) {
        add_option('llms_post_type_settings', array('enabled' => array(), 'order' => array()));
    }
    
    if (!get_option('llms_page_settings')) {
        add_option('llms_page_settings', array('enabled' => false, 'order' => array()));
    }
    
    // 初回のLLMS.txtファイルを生成
    generate_llms_txt();
}

// プラグインの無効化時に実行される処理
function llms_txt_generator_deactivate() {
    // 設定データは残すが、スケジュールされたイベントがあれば削除
    wp_clear_scheduled_hook('llms_txt_generate_cron');
}

// WordPress環境でのみフックを登録
if (function_exists('register_activation_hook') && function_exists('register_deactivation_hook')) {
    register_activation_hook(__FILE__, 'llms_txt_generator_activate');
    register_deactivation_hook(__FILE__, 'llms_txt_generator_deactivate');
}

// LLMS.txtを自動生成する関数
function generate_llms_txt() {
    // WordPress関数が利用可能かチェック
    if (!function_exists('get_bloginfo') || !function_exists('get_posts')) {
        return false;
    }
    
    $site_name = get_bloginfo('name');
    $site_url = get_site_url();
    $site_description = get_bloginfo('description');
    
    // $content変数を初期化
    $content = '';
    
    // 全投稿記事を取得
    $all_posts = get_posts(array(
        'numberposts' => -1,
        'post_status' => 'publish',
        'post_type' => 'any',
        'orderby' => 'post_type',
        'order' => 'ASC'
    ));
       
    // カスタムテキストがあれば上部に表示
    $custom_text = get_option('llms_custom_text', '');
    if (!empty($custom_text)) {
        $content .= $custom_text . "\n\n";
    }
    
    // $content .= "## サイト情報\n";
    // $content .= "- サイト名: {$site_name}\n";
    // $content .= "- URL: {$site_url}\n";
    // $content .= "- 説明: {$site_description}\n";
    $content .= "# 最終更新: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 投稿タイプの設定を取得
    $post_type_settings = get_option('llms_post_type_settings', array());
    $enabled_post_types = isset($post_type_settings['enabled']) ? $post_type_settings['enabled'] : array();
    $post_type_order = isset($post_type_settings['order']) ? $post_type_settings['order'] : array();
    
    // 固定ページの設定を取得
    $page_settings = get_option('llms_page_settings', array());
    $pages_enabled = isset($page_settings['enabled']) ? $page_settings['enabled'] : false;
    $page_order = isset($page_settings['order']) ? $page_settings['order'] : array();
    
    // 投稿タイプ別に記事を分類
    $posts_by_type = array();
    $pages = array(); // 固定ページ専用配列
    
    foreach ($all_posts as $post) {
        $post_type = get_post_type($post->ID);
        
        // 固定ページの処理
        if ($post_type === 'page') {
            if ($pages_enabled) {
                $pages[] = $post;
            }
            continue;
        }
        
        // 無効化された投稿タイプをスキップ
        if (!empty($enabled_post_types) && !in_array($post_type, $enabled_post_types)) {
            continue;
        }
        
        if (!isset($posts_by_type[$post_type])) {
            $posts_by_type[$post_type] = array();
        }
        $posts_by_type[$post_type][] = $post;
    }
    
    // カスタム順序で投稿タイプを並び替え
    if (!empty($post_type_order)) {
        $ordered_posts_by_type = array();
        
        // 順序設定に従って並び替え
        foreach ($post_type_order as $post_type) {
            if (isset($posts_by_type[$post_type])) {
                $ordered_posts_by_type[$post_type] = $posts_by_type[$post_type];
                unset($posts_by_type[$post_type]);
            }
        }
        
        // 順序設定にない投稿タイプを最後に追加
        $posts_by_type = array_merge($ordered_posts_by_type, $posts_by_type);
    }
    
    // 投稿タイプ別に出力
    foreach ($posts_by_type as $post_type => $posts) {
        $post_type_object = get_post_type_object($post_type);
        $post_type_name = $post_type_object ? $post_type_object->labels->name : $post_type;
        
        $content .= "## {$post_type_name}\n";
        
        // 通常の投稿（post）の場合はカテゴリ別に分類
        if ($post_type === 'post') {
            $posts_by_category = array();
            $uncategorized_posts = array();
            
            foreach ($posts as $post) {
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        if (!isset($posts_by_category[$category->name])) {
                            $posts_by_category[$category->name] = array();
                        }
                        $posts_by_category[$category->name][] = $post;
                        break; // 最初のカテゴリのみ使用
                    }
                } else {
                    $uncategorized_posts[] = $post;
                }
            }
            
            // カテゴリ別に出力
            foreach ($posts_by_category as $category_name => $category_posts) {
                $content .= "### {$category_name}\n";
                foreach ($category_posts as $post) {
                    $post_url = get_permalink($post->ID);
                    // wp_trim_words関数が存在しない場合の代替処理
                    if (function_exists('wp_trim_words')) {
                        $excerpt = wp_trim_words($post->post_content, 15, '...');
                    } else {
                        $excerpt = mb_substr(strip_tags($post->post_content), 0, 50) . '...';
                    }
                    $content .= "- [{$post->post_title}]({$post_url}):{$excerpt}\n";
                }
                $content .= "\n";
            }
            
            // 未分類の投稿
            if (!empty($uncategorized_posts)) {
                $content .= "### 未分類\n";
                foreach ($uncategorized_posts as $post) {
                    $post_url = get_permalink($post->ID);
                    // wp_trim_words関数が存在しない場合の代替処理
                    if (function_exists('wp_trim_words')) {
                        $excerpt = wp_trim_words($post->post_content, 15, '...');
                    } else {
                        $excerpt = mb_substr(strip_tags($post->post_content), 0, 50) . '...';
                    }
                    $content .= "- [{$post->post_title}]({$post_url}):{$excerpt}\n";
                }
                $content .= "\n";
            }
        } else {
            // その他の投稿タイプはそのまま出力
            foreach ($posts as $post) {
                $post_url = get_permalink($post->ID);
                // wp_trim_words関数が存在しない場合の代替処理
                if (function_exists('wp_trim_words')) {
                    $excerpt = wp_trim_words($post->post_content, 15, '...');
                } else {
                    $excerpt = mb_substr(strip_tags($post->post_content), 0, 50) . '...';
                }
                $content .= "- [{$post->post_title}]({$post_url}):{$excerpt}\n";
            }
            $content .= "\n";
        }
    }
    
    // 固定ページの処理
    if (!empty($pages)) {
        // 固定ページの順序設定
        if (!empty($page_order)) {
            $ordered_pages = array();
            $pages_by_id = array();
            
            // ページをIDでインデックス化
            foreach ($pages as $page) {
                $pages_by_id[$page->ID] = $page;
            }
            
            // 順序設定に従って並び替え
            foreach ($page_order as $page_id) {
                if (isset($pages_by_id[$page_id])) {
                    $ordered_pages[] = $pages_by_id[$page_id];
                    unset($pages_by_id[$page_id]);
                }
            }
            
            // 順序設定にない固定ページを最後に追加
            $pages = array_merge($ordered_pages, array_values($pages_by_id));
        }
        
        $content .= "## 固定ページ\n";
        
        foreach ($pages as $page) {
            $page_url = get_permalink($page->ID);
            // wp_trim_words関数が存在しない場合の代替処理
            if (function_exists('wp_trim_words')) {
                $excerpt = wp_trim_words($page->post_content, 15, '...');
            } else {
                $excerpt = mb_substr(strip_tags($page->post_content), 0, 50) . '...';
            }
            $content .= "- [{$page->post_title}]({$page_url}):{$excerpt}\n";
        }
        $content .= "\n";
    }
    
    // ファイルに保存（設定された文字コードで出力）
    $upload_dir = wp_upload_dir();
    $file_path = ABSPATH . 'llms.txt';
    
    // 選択された文字コードを取得（デフォルトはSJIS）
    $encoding = get_option('llms_encoding', 'SJIS');
    
    if ($encoding === 'UTF-8') {
        // UTF-8で保存
        file_put_contents($file_path, $content);
    } else {
        // UTF-8からShift-JISに変換
        $content_sjis = mb_convert_encoding($content, 'SJIS', 'UTF-8');
        file_put_contents($file_path, $content_sjis);
    }
    
    return true;
}

// 記事公開・更新時にLLMS.txtを生成（WordPress環境でのみ実行）
if (function_exists('add_action')) {
    add_action('publish_post', 'generate_llms_txt');
    add_action('post_updated', 'generate_llms_txt');
    add_action('publish_page', 'generate_llms_txt'); // 固定ページ公開時
    add_action('page_updated', 'generate_llms_txt'); // 固定ページ更新時（存在しない場合はpost_updatedで対応）
}

// 管理画面にLLMS.txt生成ボタンを追加（WordPress環境でのみ実行）
if (function_exists('add_action')) {
    add_action('admin_menu', function() {
        if (function_exists('add_management_page')) {
            add_management_page(
                'LLMs.txt Generator',
                'LLMs.txt Generator',
                'manage_options',
                'llms-generator',
                'llms_generator_page'
            );
        }
    });
}

function llms_generator_page() {
    // WordPress関数が利用可能かチェック
    if (!function_exists('get_site_url') || !function_exists('get_option')) {
        echo '<div class="notice notice-error"><p>WordPressの関数が利用できません。</p></div>';
        return;
    }
    
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(__('このページにアクセスする権限がありません。'));
    }
    
    $file_path = ABSPATH . 'llms.txt';
    $site_url = get_site_url();
    $file_url = $site_url . '/llms.txt';
    
    // カスタムテキストの保存処理
    if (isset($_POST['save_custom_text']) && check_admin_referer('llms_custom_text_action', 'llms_custom_text_nonce')) {
        if (function_exists('sanitize_textarea_field') && function_exists('update_option')) {
            $custom_text = sanitize_textarea_field($_POST['llms_custom_text']);
            update_option('llms_custom_text', $custom_text);
            echo '<div class="notice notice-success"><p>カスタムテキストが保存されました！</p></div>';
        }
    }
    
    // 文字コード設定の保存処理
    if (isset($_POST['save_encoding']) && check_admin_referer('llms_encoding_action', 'llms_encoding_nonce')) {
        if (function_exists('update_option')) {
            $encoding = isset($_POST['llms_encoding']) && $_POST['llms_encoding'] === 'UTF-8' ? 'UTF-8' : 'SJIS';
            update_option('llms_encoding', $encoding);
            echo '<div class="notice notice-success"><p>文字コード設定が保存されました！</p></div>';
        }
    }
    
    // 投稿タイプ設定の保存処理
    if (isset($_POST['save_post_types']) && check_admin_referer('llms_post_types_action', 'llms_post_types_nonce')) {
        if (function_exists('update_option')) {
            $enabled_post_types = isset($_POST['enabled_post_types']) ? array_map('sanitize_text_field', $_POST['enabled_post_types']) : array();
            $post_type_order_raw = isset($_POST['post_type_order']) ? trim($_POST['post_type_order']) : '';
                        
            if (!empty($post_type_order_raw)) {
                $post_type_order = array_map('sanitize_text_field', explode(',', $post_type_order_raw));
                $post_type_order = array_filter($post_type_order); // 空の要素を除去
            } else {
                $post_type_order = array();
            }
                        
            $post_type_settings = array(
                'enabled' => $enabled_post_types,
                'order' => $post_type_order
            );
            
            $result = update_option('llms_post_type_settings', $post_type_settings);
            
            if ($result) {
                echo '<div class="notice notice-success"><p>投稿タイプ設定が保存されました！</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>投稿タイプ設定の保存に失敗しました。</p></div>';
            }
        }
    }
    
    // 固定ページ設定の保存処理
    if (isset($_POST['save_page_settings']) && check_admin_referer('llms_page_settings_action', 'llms_page_settings_nonce')) {
        if (function_exists('update_option')) {
            $pages_enabled = isset($_POST['pages_enabled']) ? true : false;
            $page_order_raw = isset($_POST['page_order']) ? trim($_POST['page_order']) : '';
            
            if (!empty($page_order_raw)) {
                $page_order = array_map('intval', explode(',', $page_order_raw));
                $page_order = array_filter($page_order); // 空の要素を除去
            } else {
                $page_order = array();
            }
            
            $page_settings = array(
                'enabled' => $pages_enabled,
                'order' => $page_order
            );
            
            $result = update_option('llms_page_settings', $page_settings);
            
            if ($result !== false) {
                echo '<div class="notice notice-success"><p>固定ページ設定が保存されました！</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>固定ページ設定の保存に失敗しました。</p></div>';
            }
        }
    }
    
    if (isset($_POST['generate_llms']) && check_admin_referer('llms_generate_action', 'llms_generate_nonce')) {
        if (generate_llms_txt()) {
            echo '<div class="notice notice-success"><p>LLMS.txtが生成されました！</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>LLMS.txtの生成に失敗しました。</p></div>';
        }
    }
    
    $current_custom_text = get_option('llms_custom_text', '');
    $current_encoding = get_option('llms_encoding', 'SJIS');
    $post_type_settings = get_option('llms_post_type_settings', array());
    $enabled_post_types = isset($post_type_settings['enabled']) ? $post_type_settings['enabled'] : array();
    $post_type_order = isset($post_type_settings['order']) ? $post_type_settings['order'] : array();
    
    // 固定ページ設定を取得
    $page_settings = get_option('llms_page_settings', array());
    $pages_enabled = isset($page_settings['enabled']) ? $page_settings['enabled'] : false;
    $page_order = isset($page_settings['order']) ? $page_settings['order'] : array();
    
    // 利用可能な投稿タイプを取得（固定ページを除外）
    $available_post_types = array();
    if (function_exists('get_post_types')) {
        $all_post_types = get_post_types(array('public' => true), 'objects');
        foreach ($all_post_types as $post_type_key => $post_type_obj) {
            if ($post_type_key !== 'page' && $post_type_key !== 'attachment') {
                $available_post_types[$post_type_key] = $post_type_obj;
            }
        }
    }
    
    // 利用可能な固定ページを取得
    $available_pages = array();
    if (function_exists('get_posts')) {
        $all_pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        $available_pages = $all_pages;
    }
    
    echo '<div class="wrap">';
    echo '<h1>LLMs.txt Generator for WordPress</h1>';
    
    // カスタムテキスト設定フォーム
    echo '<h2>カスタムテキスト設定</h2>';
    echo '<p>LLMS.txtファイルの上部に表示するテキストを設定できます。</p>';
    echo '<form method="post" style="margin-bottom: 30px;">';
    wp_nonce_field('llms_custom_text_action', 'llms_custom_text_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row">カスタムテキスト</th>';
    echo '<td>';
    $escaped_text = function_exists('esc_textarea') ? esc_textarea($current_custom_text) : htmlspecialchars($current_custom_text, ENT_QUOTES, 'UTF-8');
    echo '<textarea name="llms_custom_text" rows="8" cols="80" class="large-text">' . $escaped_text . '</textarea>';
    echo '<p class="description">Markdownフォーマットで記述してください。このテキストはLLMS.txtの最上部に表示されます。</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="save_custom_text" class="button button-primary" value="カスタムテキストを保存">';
    echo '</p>';
    echo '</form>';
    
    echo '<hr>';
    
    // 文字コード設定フォーム
    echo '<h2>文字コード設定</h2>';
    echo '<p>LLMS.txtファイルの文字コードを選択してください。</p>';
    echo '<form method="post" style="margin-bottom: 30px;">';
    wp_nonce_field('llms_encoding_action', 'llms_encoding_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row">ファイル文字コード</th>';
    echo '<td>';
    echo '<label>';
    echo '<input type="radio" name="llms_encoding" value="UTF-8"' . ($current_encoding === 'UTF-8' ? ' checked' : '') . '>';
    echo ' UTF-8';
    echo '</label><br>';
    echo '<label>';
    echo '<input type="radio" name="llms_encoding" value="SJIS"' . ($current_encoding === 'SJIS' ? ' checked' : '') . '>';
    echo ' Shift-JIS';
    echo '</label>';
    echo '<p class="description">ファイルを保存する際の文字コードを選択してください。デフォルトはShift-JISです。</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="save_encoding" class="button button-primary" value="文字コード設定を保存">';
    echo '</p>';
    echo '</form>';
    
    echo '<hr>';
    
    // 投稿タイプ設定フォーム
    echo '<h2>投稿タイプ設定</h2>';
    echo '<p>LLMS.txtに出力する投稿タイプと順番を設定してください。</p>';
    echo '<form method="post" style="margin-bottom: 30px;">';
    wp_nonce_field('llms_post_types_action', 'llms_post_types_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row">出力する投稿タイプ</th>';
    echo '<td>';
    
    if (!empty($available_post_types)) {
        echo '<div style="margin-bottom: 20px;">';
        echo '<p><strong>チェックした投稿タイプのみが出力されます：</strong></p>';
        foreach ($available_post_types as $post_type_key => $post_type_obj) {
            $checked = empty($enabled_post_types) || in_array($post_type_key, $enabled_post_types) ? ' checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="enabled_post_types[]" value="' . esc_attr($post_type_key) . '"' . $checked . '>';
            echo ' ' . esc_html($post_type_obj->labels->name) . ' (' . esc_html($post_type_key) . ')';
            echo '</label>';
        }
        echo '</div>';
        
        echo '<div>';
        echo '<p><strong>出力順序：</strong></p>';
        echo '<p class="description">上下の矢印ボタンをクリックして順番を変更してください。</p>';
        echo '<div id="post-type-order-list" style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; min-height: 120px;">';
        
        // 全ての投稿タイプをリストアップ（順序設定に基づいて）
        $ordered_types = array();
        if (!empty($post_type_order)) {
            foreach ($post_type_order as $post_type_key) {
                if (isset($available_post_types[$post_type_key])) {
                    $ordered_types[] = $post_type_key;
                }
            }
        }
        
        // 順序設定にない投稿タイプを最後に追加
        foreach ($available_post_types as $post_type_key => $post_type_obj) {
            if (!in_array($post_type_key, $ordered_types)) {
                $ordered_types[] = $post_type_key;
            }
        }
        
        foreach ($ordered_types as $index => $post_type_key) {
            if (isset($available_post_types[$post_type_key])) {
                $post_type_obj = $available_post_types[$post_type_key];
                $is_first = ($index === 0);
                $is_last = ($index === count($ordered_types) - 1);
                
                echo '<div class="post-type-item" data-post-type="' . esc_attr($post_type_key) . '" style="display: flex; align-items: center; padding: 10px; margin: 8px 0; background: #fff; border: 1px solid #ccc; border-radius: 3px;">';
                
                // 投稿タイプ名
                echo '<span style="flex: 1; font-weight: 500;">';
                echo esc_html($post_type_obj->labels->name) . ' (' . esc_html($post_type_key) . ')';
                echo '</span>';
                
                // 矢印ボタン
                echo '<div style="margin-left: 10px;">';
                
                // 上矢印ボタン
                $up_disabled = $is_first ? ' disabled' : '';
                echo '<button type="button" class="move-up button button-small"' . $up_disabled . ' style="margin-right: 5px;" data-post-type="' . esc_attr($post_type_key) . '">';
                echo '↑';
                echo '</button>';
                
                // 下矢印ボタン
                $down_disabled = $is_last ? ' disabled' : '';
                echo '<button type="button" class="move-down button button-small"' . $down_disabled . ' data-post-type="' . esc_attr($post_type_key) . '">';
                echo '↓';
                echo '</button>';
                
                echo '</div>';
                echo '</div>';
            }
        }
        
        echo '</div>';
        echo '<input type="hidden" name="post_type_order" id="post_type_order" value="' . esc_attr(implode(',', $ordered_types)) . '">';
        echo '</div>';
    } else {
        echo '<p>投稿タイプが見つかりませんでした。</p>';
    }
    
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="save_post_types" class="button button-primary" value="投稿タイプ設定を保存">';
    echo '</p>';
    echo '</form>';
    
    // 上下矢印ボタン用JavaScript
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function updateOrder() {
            var order = [];
            $("#post-type-order-list .post-type-item").each(function() {
                var postType = $(this).data("post-type");
                if (postType) {
                    order.push(postType);
                }
            });
            $("#post_type_order").val(order.join(","));
            console.log("Order updated:", order.join(","));
        }
        
        function updateButtons() {
            $("#post-type-order-list .post-type-item").each(function(index) {
                var $item = $(this);
                var totalItems = $("#post-type-order-list .post-type-item").length;
                var isFirst = (index === 0);
                var isLast = (index === totalItems - 1);
                
                $item.find(".move-up").prop("disabled", isFirst);
                $item.find(".move-down").prop("disabled", isLast);
                
                // Update button styles
                if (isFirst) {
                    $item.find(".move-up").css("opacity", "0.3");
                } else {
                    $item.find(".move-up").css("opacity", "1");
                }
                
                if (isLast) {
                    $item.find(".move-down").css("opacity", "0.3");
                } else {
                    $item.find(".move-down").css("opacity", "1");
                }
            });
        }
        
        // Up arrow button click event
        $(document).on("click", ".move-up:not(:disabled)", function(e) {
            e.preventDefault();
            console.log("Up arrow clicked");
            
            var $currentItem = $(this).closest(".post-type-item");
            var $prevItem = $currentItem.prev(".post-type-item");
            
            if ($prevItem.length > 0) {
                console.log("Moving up:", $currentItem.data("post-type"));
                $currentItem.insertBefore($prevItem);
                updateOrder();
                updateButtons();
            }
        });
        
        // Down arrow button click event
        $(document).on("click", ".move-down:not(:disabled)", function(e) {
            e.preventDefault();
            console.log("Down arrow clicked");
            
            var $currentItem = $(this).closest(".post-type-item");
            var $nextItem = $currentItem.next(".post-type-item");
            
            if ($nextItem.length > 0) {
                console.log("Moving down:", $currentItem.data("post-type"));
                $currentItem.insertAfter($nextItem);
                updateOrder();
                updateButtons();
            }
        });
        
        // Initialize
        updateOrder();
        updateButtons();
        
        // Debug info
        console.log("Arrow button functionality initialized");
        console.log("Initial order:", $("#post_type_order").val());
    });
    </script>
    <?php
    
    echo '<hr>';
    
    // 固定ページ出力設定フォーム
    echo '<h2>固定ページ出力設定</h2>';
    echo '<p>LLMS.txtに固定ページを出力するかどうかと、その順番を設定してください。</p>';
    echo '<form method="post" style="margin-bottom: 30px;">';
    wp_nonce_field('llms_page_settings_action', 'llms_page_settings_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row">固定ページの出力</th>';
    echo '<td>';
    
    echo '<div style="margin-bottom: 20px;">';
    echo '<label>';
    echo '<input type="checkbox" name="pages_enabled" value="1"' . ($pages_enabled ? ' checked' : '') . '>';
    echo ' 固定ページをLLMS.txtに出力する';
    echo '</label>';
    echo '<p class="description">チェックを入れると、公開されている固定ページがLLMS.txtに含まれます。</p>';
    echo '</div>';
    
    if (!empty($available_pages)) {
        echo '<div>';
        echo '<p><strong>出力順序：</strong></p>';
        echo '<p class="description">上下の矢印ボタンをクリックして順番を変更してください。</p>';
        echo '<div id="page-order-list" style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; min-height: 120px;">';
        
        // 固定ページの順序設定
        $ordered_pages = array();
        if (!empty($page_order)) {
            foreach ($page_order as $page_id) {
                foreach ($available_pages as $page) {
                    if ($page->ID == $page_id) {
                        $ordered_pages[] = $page;
                        break;
                    }
                }
            }
        }
        
        // 順序設定にない固定ページを最後に追加
        foreach ($available_pages as $page) {
            $already_ordered = false;
            foreach ($ordered_pages as $ordered_page) {
                if ($ordered_page->ID == $page->ID) {
                    $already_ordered = true;
                    break;
                }
            }
            if (!$already_ordered) {
                $ordered_pages[] = $page;
            }
        }
        
        foreach ($ordered_pages as $index => $page) {
            $is_first = ($index === 0);
            $is_last = ($index === count($ordered_pages) - 1);
            
            echo '<div class="page-item" data-page-id="' . esc_attr($page->ID) . '" style="display: flex; align-items: center; padding: 10px; margin: 8px 0; background: #fff; border: 1px solid #ccc; border-radius: 3px;">';
            
            // 固定ページタイトル
            echo '<span style="flex: 1; font-weight: 500;">';
            echo esc_html($page->post_title) . ' (ID: ' . esc_html($page->ID) . ')';
            echo '</span>';
            
            // 矢印ボタン
            echo '<div style="margin-left: 10px;">';
            
            // 上矢印ボタン
            $up_disabled = $is_first ? ' disabled' : '';
            echo '<button type="button" class="page-move-up button button-small"' . $up_disabled . ' style="margin-right: 5px;" data-page-id="' . esc_attr($page->ID) . '">';
            echo '↑';
            echo '</button>';
            
            // 下矢印ボタン
            $down_disabled = $is_last ? ' disabled' : '';
            echo '<button type="button" class="page-move-down button button-small"' . $down_disabled . ' data-page-id="' . esc_attr($page->ID) . '">';
            echo '↓';
            echo '</button>';
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        $page_order_string = array();
        foreach ($ordered_pages as $page) {
            $page_order_string[] = $page->ID;
        }
        echo '<input type="hidden" name="page_order" id="page_order" value="' . esc_attr(implode(',', $page_order_string)) . '">';
        echo '</div>';
    } else {
        echo '<p>公開されている固定ページが見つかりませんでした。</p>';
    }
    
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="save_page_settings" class="button button-primary" value="固定ページ設定を保存">';
    echo '</p>';
    echo '</form>';
    
    // 固定ページ順序変更用JavaScript
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function updatePageOrder() {
            var order = [];
            $("#page-order-list .page-item").each(function() {
                var pageId = $(this).data("page-id");
                if (pageId) {
                    order.push(pageId);
                }
            });
            $("#page_order").val(order.join(","));
            console.log("Page order updated:", order.join(","));
        }
        
        function updatePageButtons() {
            $("#page-order-list .page-item").each(function(index) {
                var $item = $(this);
                var totalItems = $("#page-order-list .page-item").length;
                var isFirst = (index === 0);
                var isLast = (index === totalItems - 1);
                
                $item.find(".page-move-up").prop("disabled", isFirst);
                $item.find(".page-move-down").prop("disabled", isLast);
                
                // Update button styles
                if (isFirst) {
                    $item.find(".page-move-up").css("opacity", "0.3");
                } else {
                    $item.find(".page-move-up").css("opacity", "1");
                }
                
                if (isLast) {
                    $item.find(".page-move-down").css("opacity", "0.3");
                } else {
                    $item.find(".page-move-down").css("opacity", "1");
                }
            });
        }
        
        // Up arrow button click event
        $(document).on("click", ".page-move-up:not(:disabled)", function(e) {
            e.preventDefault();
            console.log("Page up arrow clicked");
            
            var $currentItem = $(this).closest(".page-item");
            var $prevItem = $currentItem.prev(".page-item");
            
            if ($prevItem.length > 0) {
                console.log("Moving page up:", $currentItem.data("page-id"));
                $currentItem.insertBefore($prevItem);
                updatePageOrder();
                updatePageButtons();
            }
        });
        
        // Down arrow button click event
        $(document).on("click", ".page-move-down:not(:disabled)", function(e) {
            e.preventDefault();
            console.log("Page down arrow clicked");
            
            var $currentItem = $(this).closest(".page-item");
            var $nextItem = $currentItem.next(".page-item");
            
            if ($nextItem.length > 0) {
                console.log("Moving page down:", $currentItem.data("page-id"));
                $currentItem.insertAfter($nextItem);
                updatePageOrder();
                updatePageButtons();
            }
        });
        
        // Initialize
        updatePageOrder();
        updatePageButtons();
        
        // Debug info
        console.log("Page arrow button functionality initialized");
        console.log("Initial page order:", $("#page_order").val());
    });
    </script>
    <?php
    
    echo '<hr>';
    
    echo '<h2>LLMS.txt生成</h2>';
    echo '<p><strong>生成先:</strong><br>';
    $escaped_path = function_exists('esc_html') ? esc_html($file_path) : htmlspecialchars($file_path, ENT_QUOTES, 'UTF-8');
    $escaped_url = function_exists('esc_url') ? esc_url($file_url) : htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8');
    $escaped_url_text = function_exists('esc_html') ? esc_html($file_url) : htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8');
    echo 'ファイルパス: <code>' . $escaped_path . '</code><br>';
    echo 'アクセスURL: <a href="' . $escaped_url . '" target="_blank">' . $escaped_url_text . '</a></p>';
    
    // 現在の設定表示
    echo '<p><strong>現在の設定:</strong><br>';
    echo '文字コード: <strong>' . ($current_encoding === 'UTF-8' ? 'UTF-8' : 'Shift-JIS') . '</strong><br>';
    if (!empty($enabled_post_types)) {
        echo '有効な投稿タイプ: <strong>' . implode(', ', $enabled_post_types) . '</strong><br>';
    } else {
        echo '有効な投稿タイプ: <strong>すべて</strong><br>';
    }
    if (!empty($post_type_order)) {
        echo '出力順序: <strong>' . implode(' → ', $post_type_order) . '</strong><br>';
    }
    echo '固定ページ出力: <strong>' . ($pages_enabled ? '有効' : '無効') . '</strong>';
    if ($pages_enabled && !empty($page_order)) {
        $page_titles = array();
        foreach ($page_order as $page_id) {
            foreach ($available_pages as $page) {
                if ($page->ID == $page_id) {
                    $page_titles[] = $page->post_title;
                    break;
                }
            }
        }
        if (!empty($page_titles)) {
            echo '<br>固定ページ順序: <strong>' . implode(' → ', $page_titles) . '</strong>';
        }
    }
    echo '</p>';
    
    if (file_exists($file_path)) {
        $last_modified = date('Y-m-d H:i:s', filemtime($file_path));
        echo '<p><strong>現在のファイル状況:</strong><br>';
        $escaped_modified = function_exists('esc_html') ? esc_html($last_modified) : htmlspecialchars($last_modified, ENT_QUOTES, 'UTF-8');
        echo '最終更新: ' . $escaped_modified . '<br>';
        $file_size = function_exists('size_format') ? size_format(filesize($file_path)) : number_format(filesize($file_path)) . ' bytes';
        echo 'ファイルサイズ: ' . $file_size . '</p>';
    } else {
        echo '<p><strong>現在のファイル状況:</strong> ファイルはまだ生成されていません</p>';
    }
    
    echo '<form method="post">';
    wp_nonce_field('llms_generate_action', 'llms_generate_nonce');
    echo '<input type="submit" name="generate_llms" class="button button-primary" value="LLMS.txtを生成">';
    echo '</form>';
    echo '</div>';
}
?>