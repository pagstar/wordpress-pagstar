<?php
/**
 * Arquivo de integração com a API da Pagstar
 *
 * @package Pagstar
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Pagstar_logs {
    function pagstar_write_log($message, $level = 'info') {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/pagstar-logs';

    if (! file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $file = $log_dir . '/' . date('Y-m-d') . '.log';
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] [$level] $message" . PHP_EOL;

    file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
  }
}