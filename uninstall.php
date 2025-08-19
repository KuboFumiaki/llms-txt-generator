<?php
/**
 * LLMS.txt Generator Uninstall
 * 
 * プラグインが削除された時に実行される処理
 * 
 * @package LLMS_TXT_Generator
 * @since 1.0.0
 */

// WordPressからの正当な削除要求でない場合は終了
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// WordPress関数が利用可能かチェック
if (!function_exists('delete_option') || !function_exists('wp_clear_scheduled_hook')) {
    return;
}

// プラグインの設定データを削除
delete_option('llms_custom_text');
delete_option('llms_encoding');
delete_option('llms_post_type_settings');
delete_option('llms_page_settings');

// 生成されたLLMS.txtファイルを削除
$file_path = ABSPATH . 'llms.txt';
if (file_exists($file_path)) {
    unlink($file_path);
}

// スケジュールされたイベントがあれば削除（将来の機能拡張用）
wp_clear_scheduled_hook('llms_txt_generate_cron');

// 一時ファイルやキャッシュがあれば削除
$upload_dir = wp_upload_dir();
$temp_files = glob($upload_dir['basedir'] . '/llms-txt-*');
if ($temp_files) {
    foreach ($temp_files as $temp_file) {
        if (is_file($temp_file)) {
            unlink($temp_file);
        }
    }
}
