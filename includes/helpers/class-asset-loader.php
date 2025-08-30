<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Asset_Loader {

    public function enqueue_admin_assets($hook) {

        // NEW: cargamos solo en nuestras pantallas (menú Voyect o subpáginas)
        // Ejemplos de $hook: 'toplevel_page_voyect', 'voyect_page_voyect-settings', etc.
        if ( strpos((string)$hook, 'voyect') === false ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('[Voyect][assets][admin] skip hook='.$hook);
            }
            return;
        }

        // NEW: Media Uploader para selector de imagen destacada en modales
        wp_enqueue_media();

        // ========== CSS Admin ==========
        wp_enqueue_style(
            'voyect-admin',
            VOYECT_URL . 'assets/css/admin/admin-main.css',
            [],
            VOYECT_VERSION
        );
        wp_enqueue_style(
            'voyect-admin-modal',
            VOYECT_URL . 'assets/css/admin/modal.css',
            ['voyect-admin'],
            VOYECT_VERSION
        );
        wp_enqueue_style(
            'voyect-admin-drag',
            VOYECT_URL . 'assets/css/admin/drag-drop.css',
            ['voyect-admin'],
            VOYECT_VERSION
        );

        // Remixicon (iconos) desde CDN
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css',
            [],
            VOYECT_VERSION
        );

        // ========== JS Admin ==========
        // Axios (requerido por async-operations.js)
        wp_enqueue_script(
            'axios',
            'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
            [],
            VOYECT_VERSION,
            true
        );

        // SortableJS para drag & drop
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            VOYECT_VERSION,
            true
        );

        // Helpers de AJAX (expone window.VoyectAPI)
        wp_enqueue_script(
            'voyect-async-ops',
            VOYECT_URL . 'assets/js/admin/async-operations.js',
            ['jquery', 'axios'],
            VOYECT_VERSION,
            true
        );

        // admin-main ahora depende de async-ops
        wp_enqueue_script(
            'voyect-admin',
            VOYECT_URL . 'assets/js/admin/admin-main.js',
            ['jquery', 'voyect-async-ops', 'sortablejs'],
            VOYECT_VERSION,
            true
        );

        // === Variables JS compartidas en admin ===
        $vars = [
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('voyect_admin_nonce'),
            // NUEVO: base del CPT para que los enlaces amigables se construyan igual en admin/JS
            'cpt_base' => method_exists('Voyect_Project_Model','get_cpt_base')
                            ? Voyect_Project_Model::get_cpt_base()
                            : 'proyectos',
            'home_url' => home_url('/'),
            // Por si algún código de front se reutiliza en admin
        ];
        wp_localize_script('voyect-admin', 'VoyectVars', $vars);

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][assets][admin] enqueued. cpt_base='.$vars['cpt_base'].' home_url='.$vars['home_url']);
        }
    }

    public function enqueue_frontend_assets() {

        // ========== CSS Frontend ==========
        wp_enqueue_style(
            'voyect-frontend',
            VOYECT_URL . 'assets/css/frontend/portfolio-archive.css',
            [],
            VOYECT_VERSION
        );

        // Remixicon (iconos) desde CDN
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css',
            [],
            VOYECT_VERSION
        );

        // ========== JS Frontend ==========
        // Axios en frontend para AJAX del shortcode
        if ( ! wp_script_is('axios', 'enqueued') ) {
            wp_enqueue_script(
                'axios',
                'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
                [],
                VOYECT_VERSION,
                true
            );
        }

        wp_enqueue_script(
            'voyect-frontend',
            VOYECT_URL . 'assets/js/frontend/portfolio-archive.js',
            ['jquery', 'axios'],
            VOYECT_VERSION,
            true
        );

        // === Variables JS para el FRONT ===
        $vars = [
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('voyect_admin_nonce'),
            'cpt_base' => method_exists('Voyect_Project_Model','get_cpt_base')
                            ? Voyect_Project_Model::get_cpt_base()
                            : 'proyectos',
            'home_url' => home_url('/'),
        ];
        wp_localize_script('voyect-frontend', 'VoyectVars', $vars);

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][assets][front] enqueued. cpt_base='.$vars['cpt_base'].' home_url='.$vars['home_url']);
        }
    }
}
