<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Shortcode_Controller {

    public function register_shortcode() {
        add_shortcode('voyect', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []) {
        // Valores por defecto del shortcode (solo frontend)
        $atts = shortcode_atts([
            'search'     => 'true',
            'filters'    => 'true',
            'pagination' => 'true',
            'per_page'   => 9,
            // Permitimos: menu_order | date | modified | title
            'orderby'    => 'menu_order',
            // asc|desc
            'order'      => 'asc',
        ], $atts, 'voyect');

        // --- Sanitización y normalización ---
        $bool = function($v){
            $v = is_bool($v) ? $v : strtolower((string)$v);
            return in_array($v, [true, '1', 1, 'true', 'yes', 'on'], true);
        };

        $orderby = strtolower( (string) $atts['orderby'] );
        $allowed_orderby = ['menu_order','date','modified','title'];
        if ( ! in_array($orderby, $allowed_orderby, true) ) {
            $orderby = 'menu_order';
        }

        $order = strtolower( (string) $atts['order'] );
        $order = ($order === 'desc') ? 'desc' : 'asc';

        $per_page = intval($atts['per_page']);
        if ($per_page < 1)  $per_page = 9;
        if ($per_page > 48) $per_page = 48;

        // **Frontend SIEMPRE debe listar SOLO publicados**
        // Añadimos banderas explícitas para que el view/JS lo respete.
        $props = [
            'search'      => $bool($atts['search']),
            'filters'     => $bool($atts['filters']),
            'pagination'  => $bool($atts['pagination']),
            'per_page'    => $per_page,
            'orderby'     => $orderby,
            'order'       => $order,
            'status'      => 'publish',      // <- clave
            'onlyPublish' => true,           // <- redundancia explícita por si el JS usa otra clave
        ];

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][shortcode] props='. wp_json_encode($props));
        }

        // Exponemos tanto en JSON seguro como en array por si el view lo requiere
        $voyect_props       = $props;
        $voyect_props_json  = esc_attr( wp_json_encode($props) );

        // Render del template de frontend
        ob_start();
        include VOYECT_PATH . 'includes/views/frontend/portfolio-archive.php';
        return ob_get_clean();
    }
}
