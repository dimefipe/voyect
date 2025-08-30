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

require_once VOYECT_PATH . 'includes/core/class-voyect-activator.php';
require_once VOYECT_PATH . 'includes/core/class-voyect-loader.php';
require_once VOYECT_PATH . 'includes/core/class-voyect-i18n.php';
require_once VOYECT_PATH . 'includes/core/class-voyect.php';

// Controllers / Helpers mínimos
require_once VOYECT_PATH . 'includes/helpers/class-asset-loader.php';
require_once VOYECT_PATH . 'includes/controllers/class-admin-controller.php';
require_once VOYECT_PATH . 'includes/controllers/class-shortcode-controller.php';

// CHANGE: requerimos el modelo de proyectos para CPT + taxonomía
require_once VOYECT_PATH . 'includes/models/class-project-model.php'; // NEW

// NEW: añadimos helper de respuestas AJAX y el controlador AJAX
require_once VOYECT_PATH . 'includes/helpers/class-ajax-handler.php';     // NEW
require_once VOYECT_PATH . 'includes/controllers/class-ajax-controller.php'; // NEW

register_activation_hook(__FILE__, ['Voyect_Activator', 'activate']);

function run_voyect() {
    $plugin = new Voyect();
    $plugin->run();
}
run_voyect();
