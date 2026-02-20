<?php
// includes/updater.php
if (!defined('ABSPATH'))
  exit;

// 1) Ajusta estas 2 líneas
define('ENTRAPOLIS_UPDATE_JSON_URL', 'https://raw.githubusercontent.com/EricGomezPerez/plugin-lista/main/update.json');
define('ENTRAPOLIS_PLUGIN_BASENAME', plugin_basename(realpath(__DIR__ . '/../entrapolis-integration.php')));

// 2) Ajusta este "slug" a tu gusto (solo se usa en "Ver detalles")
define('ENTRAPOLIS_PLUGIN_SLUG', 'entrapolis-plugin-lista');

function entrapolis_updater_get_remote_info()
{
  $cache_key = 'entrapolis_update_info';
  $cached = get_transient($cache_key);
  if ($cached !== false)
    return $cached;

  $res = wp_remote_get(ENTRAPOLIS_UPDATE_JSON_URL, [
    'timeout' => 10,
    'headers' => ['Accept' => 'application/json'],
  ]);

  if (is_wp_error($res))
    return null;

  if (isset($_GET['force_update'])) {
    delete_transient('update_plugins');
    delete_transient('entrapolis_update_info');
  }

  $code = wp_remote_retrieve_response_code($res);
  if ($code !== 200)
    return null;

  $body = wp_remote_retrieve_body($res);
  $data = json_decode($body, true);
  if (!is_array($data) || empty($data['version']) || empty($data['download_url']))
    return null;

  // Cache 6h
  set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);
  return $data;
}

add_filter('site_transient_update_plugins', function ($transient) {
  if (!is_object($transient) || empty($transient->checked))
    return $transient;

  if (!defined('ENTRAPOLIS_VERSION'))
    return $transient;
  $current = ENTRAPOLIS_VERSION;

  $remote = entrapolis_updater_get_remote_info();
  if (!$remote)
    return $transient;

  $new_version = (string) $remote['version'];
  if (version_compare($current, $new_version, '>='))
    return $transient;

  $update = (object) [
    'slug' => ENTRAPOLIS_PLUGIN_SLUG,
    'plugin' => ENTRAPOLIS_PLUGIN_BASENAME,
    'new_version' => $new_version,
    'url' => $remote['homepage'] ?? '',
    'package' => $remote['download_url'],
    'tested' => $remote['tested'] ?? '',
    'requires' => $remote['requires'] ?? '',
    'requires_php' => $remote['requires_php'] ?? '',
  ];

  $transient->response[ENTRAPOLIS_PLUGIN_BASENAME] = $update;
  return $transient;
});

add_filter('plugins_api', function ($result, $action, $args) {
  if ($action !== 'plugin_information')
    return $result;
  if (empty($args->slug) || $args->slug !== ENTRAPOLIS_PLUGIN_SLUG)
    return $result;

  $remote = entrapolis_updater_get_remote_info();
  if (!$remote)
    return $result;

  $info = (object) [
    'name' => 'Entrapolis Plugin',
    'slug' => ENTRAPOLIS_PLUGIN_SLUG,
    'version' => (string) $remote['version'],
    'author' => '<a href="' . esc_url($remote['homepage'] ?? '') . '">Entrapolis</a>',
    'homepage' => $remote['homepage'] ?? '',
    'requires' => $remote['requires'] ?? '',
    'tested' => $remote['tested'] ?? '',
    'requires_php' => $remote['requires_php'] ?? '',
    'download_link' => $remote['download_url'],
    'sections' => [
      'description' => 'Plugin de WordPress para integrar eventos de Entrapolis mediante shortcodes.',
      'changelog' => !empty($remote['changelog']) ? nl2br(esc_html($remote['changelog'])) : 'Sin changelog.',
    ],
  ];

  return $info;
}, 10, 3);

add_action('upgrader_process_complete', function () {
  delete_transient('entrapolis_update_info');
}, 10, 0);
