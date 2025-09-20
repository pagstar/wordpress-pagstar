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

class Pagstar_logger {
    function pagstar_write_log($message, $level = 'info', $file = 'default', $txid = null) {

        $upload_dir = wp_upload_dir();
        if ($file == 'webhook' && $txid) {
            $log_dir = $upload_dir['basedir'] . '/pagstar-logs-webhooks';
            $file = $log_dir . '/' . $txid . '-' . date('Y-m-d') . '.log';
        } else {
            $log_dir = $upload_dir['basedir'] . '/pagstar-logs';
            $file = $log_dir . '/' . date('Y-m-d') . '.log';
        }


        if (! file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $time = date('Y-m-d H:i:s');
        $entry = "[$time] [$level] $message" . PHP_EOL;

        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
  }
}