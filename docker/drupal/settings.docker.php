<?php

/**
 * @file
 * Docker environment settings for Drupal.
 *
 * This file reads database credentials from environment variables.
 * Include this file from settings.php after Drupal installation.
 *
 * Add to settings.php:
 *   if (file_exists($app_root . '/' . $site_path . '/settings.docker.php')) {
 *     include $app_root . '/' . $site_path . '/settings.docker.php';
 *   }
 */

// Database configuration from environment variables.
if (getenv('DRUPAL_DB_HOST')) {
  $databases['default']['default'] = [
    'database' => getenv('DRUPAL_DB_NAME') ?: 'drupal',
    'username' => getenv('DRUPAL_DB_USER') ?: 'drupal',
    'password' => getenv('DRUPAL_DB_PASSWORD') ?: '',
    'host' => getenv('DRUPAL_DB_HOST') ?: 'postgres',
    'port' => getenv('DRUPAL_DB_PORT') ?: '5432',
    'driver' => 'pgsql',
    'prefix' => '',
    'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
    'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
  ];
}

// Hash salt from environment.
if (getenv('DRUPAL_HASH_SALT')) {
  $settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');
}

// Trusted host patterns from environment.
if (getenv('DRUPAL_TRUSTED_HOST')) {
  $settings['trusted_host_patterns'] = [
    '^' . preg_quote(getenv('DRUPAL_TRUSTED_HOST')) . '$',
    '^localhost$',
    '^127\.0\.0\.1$',
  ];
}

// File paths for Docker volumes.
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/default/private';

// Disable CSS/JS aggregation in development (enable in production).
// $config['system.performance']['css']['preprocess'] = FALSE;
// $config['system.performance']['js']['preprocess'] = FALSE;

// Reverse proxy settings for Traefik.
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1', '::1'];
$settings['reverse_proxy_trusted_headers'] = 
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
  \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;

// Force HTTPS when behind Traefik.
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
  $_SERVER['HTTPS'] = 'on';
}

// Radicale server configuration override from environment.
if (getenv('RADICALE_SERVER_URL')) {
  $config['radicale_calendar.settings']['radicale_server_url'] = getenv('RADICALE_SERVER_URL');
  $config['radicale_calendar.settings']['radicale_username'] = getenv('RADICALE_USERNAME') ?: 'admin';
  $config['radicale_calendar.settings']['radicale_password'] = getenv('RADICALE_PASSWORD') ?: '';
}
