<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect {
    protected $loader;
    protected $i18n;
    protected $admin;
    protected $shortcode;
    protected $assets;

    // Modelo (CPT + tax)
    protected $project_model;

    // Controlador AJAX
    protected $ajax;

    /**
     * Slugs por defecto para URLs amigables (filtrables)
     */
    const DEFAULT_CPT_REWRITE = 'proyectos';
    const DEFAULT_TAX_REWRITE = 'categoria-proyecto';

    public function __construct() {
        $this->loader    = new Voyect_Loader();
        $this->i18n      = new Voyect_i18n();
        $this->admin     = new Voyect_Admin_Controller();
        $this->shortcode = new Voyect_Shortcode_Controller();
        $this->assets    = new Voyect_Asset_Loader();

        $this->project_model = new Voyect_Project_Model();
        $this->ajax          = new Voyect_Ajax_Controller();

        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();

        // Hooks del modelo (CPT/Tax)
        $this->project_model->register_hooks($this->loader);

        // Hooks AJAX
        $this->ajax->register_hooks($this->loader);

        // Filtros para exponer slugs de reescritura (usar firma del loader)
        $this->register_rewrite_filters();

        // Flush de reglas diferido tras activación/actualización
        $this->schedule_rewrite_flush_on_activation();
        $this->maybe_do_deferred_flush();
    }

    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this->i18n, 'load_textdomain');
    }

    private function define_admin_hooks() {
        $this->loader->add_action('admin_menu',               $this->admin,  'register_menu');
        $this->loader->add_action('admin_enqueue_scripts',    $this->assets, 'enqueue_admin_assets');
    }

    private function define_public_hooks() {
        $this->loader->add_action('wp_enqueue_scripts', $this->assets, 'enqueue_frontend_assets');
    }

    private function define_shortcodes() {
        $this->loader->add_action('init', $this->shortcode, 'register_shortcode');
    }

    public function run() {
        $this->loader->run();
    }

    /* =========================
     * Slugs de reescritura
     * ========================= */
    private function register_rewrite_filters() {
        // ¡Ojo! Tu loader espera (hook, component, callback)
        $this->loader->add_filter('voyect/cpt_rewrite_slug', $this, 'filter_cpt_rewrite_slug');
        $this->loader->add_filter('voyect/tax_rewrite_slug', $this, 'filter_tax_rewrite_slug');
    }

    // Deben ser PUBLIC para que WP pueda llamarlos como callbacks
    public function filter_cpt_rewrite_slug( $slug ) {
        return $slug ?: self::DEFAULT_CPT_REWRITE;
    }
    public function filter_tax_rewrite_slug( $slug ) {
        return $slug ?: self::DEFAULT_TAX_REWRITE;
    }

    /* =========================
     * Flush de rewrite rules
     * ========================= */
    private function schedule_rewrite_flush_on_activation() {
        if ( ! get_option('voyect_flush_rewrite_pending') ) {
            add_option('voyect_flush_rewrite_pending', 1, '', false);
        }
    }

    private function maybe_do_deferred_flush() {
        // Ejecutar después de que el CPT/Tax estén registrados
        $this->loader->add_action('init', function () {
            if ( get_option('voyect_flush_rewrite_pending') ) {
                flush_rewrite_rules(false);
                delete_option('voyect_flush_rewrite_pending');
            }
        }, 20);
    }
}
