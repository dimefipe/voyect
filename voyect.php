<?php
/**
 * Plugin Name: Voyect
 * Description: Gestión de portafolio con panel admin y shortcode AJAX (arquitectura enterprise).
 * Version:     1.0.0
 * Author:      Voy.Digital
 * Text Domain: voyect
 * Domain Path: /languages
 */

if ( ! defined('ABSPATH') ) exit;

define('VOYECT_VERSION', '1.0.0');
define('VOYECT_PATH', plugin_dir_path(__FILE__));
define('VOYECT_URL',  plugin_dir_url(__FILE__));

/** ---------------------------------------------------------------------------
 * LOG de arranque
 * ------------------------------------------------------------------------ */
if ( function_exists('error_log') ) {
    error_log('[Voyect][bootstrap] Arrancando plugin v'.VOYECT_VERSION);
}

/** ---------------------------------------------------------------------------
 * Core (orden mantiene tu flujo actual)
 * ------------------------------------------------------------------------ */
require_once VOYECT_PATH . 'includes/core/class-voyect-activator.php';
require_once VOYECT_PATH . 'includes/core/class-voyect-loader.php';
require_once VOYECT_PATH . 'includes/core/class-voyect-i18n.php';
require_once VOYECT_PATH . 'includes/core/class-voyect.php';

/** Controllers / Helpers mínimos */
require_once VOYECT_PATH . 'includes/helpers/class-asset-loader.php';
require_once VOYECT_PATH . 'includes/controllers/class-admin-controller.php';
require_once VOYECT_PATH . 'includes/controllers/class-shortcode-controller.php';

/** CPT + taxonomía (modelo) */
require_once VOYECT_PATH . 'includes/models/class-project-model.php';

/** AJAX */
require_once VOYECT_PATH . 'includes/helpers/class-ajax-handler.php';
require_once VOYECT_PATH . 'includes/controllers/class-ajax-controller.php';

/** ---------------------------------------------------------------------------
 * NUEVO: Grid Presets (CPT) – carga y boot
 *  - No interfiere con tu run_voyect(); solo registra CPT/metabox propio.
 * ------------------------------------------------------------------------ */
$__voyect_grids_file = VOYECT_PATH . 'includes/core/class-voyect-grids.php';
if ( file_exists($__voyect_grids_file) ) {
    require_once $__voyect_grids_file;
    if ( class_exists('Voyect_Grids') ) {
        add_action('init', ['Voyect_Grids','boot'], 0);
        if ( function_exists('error_log') ) error_log('[Voyect][bootstrap] Grids cargado y boot() conectado a init');
    } else {
        if ( function_exists('error_log') ) error_log('[Voyect][bootstrap][ERROR] Clase Voyect_Grids no disponible tras require');
    }
} else {
    if ( function_exists('error_log') ) error_log('[Voyect][bootstrap][WARN] Falta includes/core/class-voyect-grids.php');
}

/** ---------------------------------------------------------------------------
 * Activación
 * ------------------------------------------------------------------------ */
register_activation_hook(__FILE__, ['Voyect_Activator', 'activate']);

/** ---------------------------------------------------------------------------
 * Arranque principal (tu patrón actual)
 * ------------------------------------------------------------------------ */
function run_voyect() {
    if ( function_exists('error_log') ) error_log('[Voyect][bootstrap] run_voyect()');
    $plugin = new Voyect();
    if ( method_exists($plugin, 'run') ) {
        $plugin->run();
        if ( function_exists('error_log') ) error_log('[Voyect][bootstrap] Voyect->run() ejecutado');
    } else {
        if ( function_exists('error_log') ) error_log('[Voyect][bootstrap][WARN] Voyect->run() no existe; hooks deberían registrarse en el constructor');
    }
}
run_voyect();

/** ---------------------------------------------------------------------------
 * Salud (log visual en admin)
 * ------------------------------------------------------------------------ */
add_action('admin_footer', function(){
    if ( ! current_user_can('manage_options') ) return;
    echo '<script>console.log("%c[Voyect] Bootstrap OK • v'.esc_js(VOYECT_VERSION).'", "color:#fff;background:#3b82f6;padding:2px 6px;border-radius:4px")</script>';
});
