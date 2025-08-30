<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Carga el template propio del plugin para el archivo del CPT
 * cuando NO hay página canónica seleccionada.
 */
class Voyect_Archive_Router {

    public function __construct() {
        add_filter('template_include', [$this, 'route_template'], 99);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        error_log('[Voyect][Archive_Router] Hooks registrados.');
    }

    /**
     * ¿Estamos en el contexto del archivo del CPT de Voyect?
     */
    protected function is_portfolio_context(): bool {
        $is = ( is_post_type_archive('voyect_project') || is_tax('voyect_category') );
        if ( $is ) {
            error_log('[Voyect][Archive_Router] Contexto portafolio detectado (archive CPT / tax).');
        }
        return $is;
    }

    /**
     * Devuelve la primera ruta de template existente dentro del plugin.
     * Prioriza /frontend/ y luego /includes/views/frontend/ (por compat).
     */
    protected function resolve_template_path(): string {
        $candidates = [
            trailingslashit(VOYECT_PATH) . 'frontend/portfolio-archive.php',
            trailingslashit(VOYECT_PATH) . 'includes/views/frontend/portfolio-archive.php',
        ];
        foreach ($candidates as $tpl) {
            if ( file_exists($tpl) ) {
                error_log('[Voyect][Archive_Router] Template seleccionado: ' . $tpl);
                return $tpl;
            }
        }
        error_log('[Voyect][Archive_Router][WARN] No se encontró portfolio-archive.php en rutas esperadas.');
        return '';
    }

    /**
     * Si no hay página canónica y estamos en el archivo del CPT o su taxonomía,
     * devolvemos el template del plugin.
     */
    public function route_template($template) {
        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);

        if ( $canonical_id > 0 ) {
            // Hay canónica → dejamos al tema manejar la ruta / o la página canónica.
            error_log('[Voyect][Archive_Router] Página canónica activa (ID ' . $canonical_id . '), no se reemplaza template.');
            return $template;
        }

        if ( $this->is_portfolio_context() ) {
            $plugin_tpl = $this->resolve_template_path();
            if ( $plugin_tpl ) {
                return $plugin_tpl;
            }
        }

        return $template;
    }

    /**
     * Encola CSS/JS del archivo del portafolio sólo cuando corresponde.
     */
    public function maybe_enqueue_assets() {
        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);
        if ( $canonical_id > 0 ) {
            return; // Con página canónica, no manipulamos el archivo del CPT.
        }

        if ( ! $this->is_portfolio_context() ) {
            return;
        }

        $css_rel = 'assets/css/frontend/portfolio-archive.css';
        $js_rel  = 'assets/js/frontend/portfolio-archive.js';

        $css_path = trailingslashit(VOYECT_PATH) . $css_rel;
        $js_path  = trailingslashit(VOYECT_PATH) . $js_rel;

        $css_url  = trailingslashit(VOYECT_URL)  . $css_rel;
        $js_url   = trailingslashit(VOYECT_URL)  . $js_rel;

        // Estilos
        if ( file_exists($css_path) ) {
            wp_enqueue_style(
                'voyect-portfolio-archive',
                $css_url,
                [],
                defined('VOYECT_VERSION') ? VOYECT_VERSION : null
            );
            error_log('[Voyect][Archive_Router] CSS encolado: ' . $css_url);
        } else {
            error_log('[Voyect][Archive_Router][INFO] CSS no encontrado (opcional): ' . $css_path);
        }

        // Scripts
        if ( file_exists($js_path) ) {
            wp_enqueue_script(
                'voyect-portfolio-archive',
                $js_url,
                ['jquery'],
                defined('VOYECT_VERSION') ? VOYECT_VERSION : null,
                true
            );
            error_log('[Voyect][Archive_Router] JS encolado: ' . $js_url);
        } else {
            error_log('[Voyect][Archive_Router][INFO] JS no encontrado (opcional): ' . $js_path);
        }
    }
}
