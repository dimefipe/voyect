<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Admin_Controller {

    protected $pagehook;
    protected $settings_hook;

    public function __construct() {
        // Mantengo el filtro; el modelo preferirá el slug de la página canónica,
        // pero si no existe, caerá a este option (ver Project_Model::get_cpt_base()).
        add_filter('voyect/cpt_rewrite_slug', [$this, 'filter_cpt_slug_from_option'], 10, 1);

        add_action('admin_init',   [$this, 'handle_settings_post']);
        add_action('admin_notices',[$this, 'maybe_admin_notice']);

        // Reordenar submenús (mover "Configuraciones" al final)
        add_action('admin_menu',   [$this, 'reorder_submenus'], 999);

        // 🔹 Forzar highlight del menú al visitar Categorías del CPT
        add_filter('parent_file',  [$this, 'fix_parent_menu_highlight']);
        add_filter('submenu_file', [$this, 'fix_submenu_highlight']);
    }

    public function register_menu() {
        $this->pagehook = add_menu_page(
            __('Voyect', 'voyect'),
            __('Voyect', 'voyect'),
            'manage_options',
            'voyect',
            [$this, 'render_dashboard'],
            'dashicons-portfolio',
            26
        );

        // Añadimos “Configuraciones”
        $this->settings_hook = add_submenu_page(
            'voyect',
            __('Configuraciones', 'voyect'),
            __('Configuraciones', 'voyect'),
            'manage_options',
            'voyect-settings',
            [$this, 'render_settings']
        );

        error_log('[Voyect][Admin_Controller] Menús registrados: page=' . $this->pagehook . ' settings=' . $this->settings_hook);
    }

    /** Mover “Configuraciones” al final del submenú de Voyect */
    public function reorder_submenus() {
        global $submenu;
        if ( empty($submenu['voyect']) || ! is_array($submenu['voyect']) ) {
            return;
        }
        $settings_index = null;
        foreach ($submenu['voyect'] as $i => $item) {
            if ( isset($item[2]) && $item[2] === 'voyect-settings' ) {
                $settings_index = $i;
                break;
            }
        }
        if ($settings_index === null) return;

        $settings_item = $submenu['voyect'][$settings_index];
        unset($submenu['voyect'][$settings_index]);
        $submenu['voyect'] = array_values($submenu['voyect']);
        $submenu['voyect'][] = $settings_item;

        error_log('[Voyect][Admin_Controller] Submenús reordenados: Configuraciones al final.');
    }

    /** Dashboard principal */
    public function render_dashboard() {
        $view = VOYECT_PATH . 'includes/views/admin/admin-dashboard.php';
        if ( file_exists( $view ) ) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>Voyect</h1><p>' .
                 esc_html__('Vista no encontrada.', 'voyect') .
                 '</p></div>';
        }
    }

    /** Página de ajustes */
    public function render_settings() {
        $view = VOYECT_PATH . 'includes/views/admin/settings.php';
        if ( file_exists( $view ) ) {
            $current_slug  = get_option('voyect_cpt_slug', 'proyectos');
            $canonical_id  = (int) get_option('voyect_canonical_page_id', 0);

            $flush_url     = wp_nonce_url( add_query_arg('voyect_action', 'flush'), 'voyect_settings_action', 'voyect_nonce' );
            $save_url      = admin_url('admin.php?page=voyect-settings');

            // Para la UI: páginas disponibles (también borradores, por si la crean y aún no publican)
            $pages = get_pages([
                'post_status' => ['publish','private','draft'],
                'sort_column' => 'post_title',
                'sort_order'  => 'ASC',
            ]);

            // Preview de URL canónica si existe
            $canonical_url = $canonical_id ? get_permalink($canonical_id) : '';

            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuraciones', 'voyect') . '</h1><p>' .
                 esc_html__('Vista de configuraciones no encontrada.', 'voyect') .
                 '</p></div>';
        }
    }

    /** Filtro: slug del CPT desde opciones (fallback si no hay página canónica) */
    public function filter_cpt_slug_from_option( $default ) {
        $opt  = get_option('voyect_cpt_slug');
        $slug = $opt ? sanitize_title( $opt ) : $default;
        if ( ! $slug ) { $slug = $default ?: 'proyectos'; }
        error_log('[Voyect][Admin_Controller] filter_cpt_slug_from_option => ' . $slug);
        return $slug;
    }

    /**
     * Helpers internos
     */
    protected function get_canonical_page() {
        $pid = (int) get_option('voyect_canonical_page_id', 0);
        if ( $pid ) {
            $p = get_post($pid);
            if ( $p && $p->post_type === 'page' ) {
                return $p;
            }
        }
        return null;
    }

    protected function sync_slug_with_canonical_page( $page_id ) {
        $p = get_post( (int) $page_id );
        if ( ! $p || $p->post_type !== 'page' ) {
            error_log('[Voyect][Admin_Controller] sync_slug_with_canonical_page: página inválida');
            return false;
        }
        $slug = sanitize_title( $p->post_name ?: $p->post_title );
        if ( ! $slug ) $slug = 'proyectos';
        update_option('voyect_cpt_slug', $slug);
        error_log('[Voyect][Admin_Controller] Slug del CPT sincronizado con página canónica: ' . $slug . ' (ID ' . $p->ID . ')');
        return true;
    }

    /** Guardado/flush/crear/quitar canónica */
    public function handle_settings_post() {
        if ( ! is_admin() || ! current_user_can('manage_options') ) return;

        // ====== Guardar ajustes normales ======
        if ( isset($_POST['voyect_settings_submit']) ) {
            check_admin_referer('voyect_settings_action', 'voyect_nonce');

            // ¿Asignaron una página canónica en el selector?
            $posted_pid = isset($_POST['voyect_canonical_page_id']) ? (int) $_POST['voyect_canonical_page_id'] : 0;
            if ( $posted_pid > 0 ) {
                update_option('voyect_canonical_page_id', $posted_pid);
                error_log('[Voyect][Admin_Controller] voyect_canonical_page_id set to ' . $posted_pid);

                // 🔁 Cuando hay página canónica, sincronizamos el slug del CPT con el slug de esa página.
                $this->sync_slug_with_canonical_page( $posted_pid );
                add_option('_voyect_notice', 'slug_synced', '', false);
                update_option('_voyect_notice', 'slug_synced');

            } else {
                // Modo SIN página canónica -> permitimos definir el slug manualmente (fallback).
                $slug_in = isset($_POST['voyect_cpt_slug']) ? sanitize_title( wp_unslash($_POST['voyect_cpt_slug']) ) : '';
                if ( $slug_in === '' ) $slug_in = 'proyectos';
                update_option('voyect_cpt_slug', $slug_in);
                // Si quitan la canónica desde el selector, borramos la opción de canónica.
                update_option('voyect_canonical_page_id', 0);
                error_log('[Voyect][Admin_Controller] Modo sin página canónica. Slug manual guardado=' . $slug_in);
            }

            add_option('_voyect_notice', 'saved', '', false);
            update_option('_voyect_notice', 'saved');

            flush_rewrite_rules();
            return;
        }

        // ====== Quitar (limpiar) página canónica desde botón dedicado ======
        if ( isset($_POST['voyect_action']) && $_POST['voyect_action'] === 'clear_canonical' ) {
            check_admin_referer('voyect_settings_action', 'voyect_nonce');

            // limpiar la canónica
            update_option('voyect_canonical_page_id', 0);

            // conservar/actualizar slug manual si vino posteado
            $slug_in = isset($_POST['voyect_cpt_slug']) ? sanitize_title( wp_unslash($_POST['voyect_cpt_slug']) ) : get_option('voyect_cpt_slug', 'proyectos');
            if ( $slug_in === '' ) $slug_in = 'proyectos';
            update_option('voyect_cpt_slug', $slug_in);

            error_log('[Voyect][Admin_Controller] Página canónica eliminada. Slug actual=' . $slug_in);

            add_option('_voyect_notice', 'canonical_cleared', '', false);
            update_option('_voyect_notice', 'canonical_cleared');

            flush_rewrite_rules();
            wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
            exit;
        }

        // ====== Crear página canónica (tipo WooCommerce) ======
        if ( isset($_POST['voyect_action']) && $_POST['voyect_action'] === 'create_canonical' ) {
            check_admin_referer('voyect_settings_action', 'voyect_nonce');

            // Deseo: si el usuario tipeó un slug manual, úsalo como base del nombre de la nueva página.
            $desired_slug = sanitize_title( wp_unslash($_POST['voyect_cpt_slug'] ?? 'proyectos') );
            if ( $desired_slug === '' ) $desired_slug = 'proyectos';

            // Si ya hay una página canónica, no creamos otra
            $existing_id = (int) get_option('voyect_canonical_page_id', 0);
            if ( $existing_id && get_post($existing_id) ) {
                error_log('[Voyect][Admin_Controller] Ya existe página canónica ID=' . $existing_id);
                add_option('_voyect_notice', 'canonical_exists', '', false);
                update_option('_voyect_notice', 'canonical_exists');
                wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
                exit;
            }

            // Buscar si ya hay una página con ese slug
            $maybe = get_page_by_path( $desired_slug, OBJECT, 'page' );
            if ( $maybe && $maybe->ID ) {
                error_log('[Voyect][Admin_Controller] Página con slug '.$desired_slug.' ya existe (ID '.$maybe->ID.'). Se usará como canónica.');
                update_option('voyect_canonical_page_id', (int)$maybe->ID);
                // Sincronizamos el option del slug del CPT con el de la página
                $this->sync_slug_with_canonical_page( (int)$maybe->ID );

                add_option('_voyect_notice', 'canonical_set', '', false);
                update_option('_voyect_notice', 'canonical_set');
                flush_rewrite_rules();
                wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
                exit;
            }

            // Crear nueva página con shortcode por defecto
            $title   = ucfirst( $desired_slug );
            $content = '[voyect filters="true" pagination="true" search="true" show_cats="true"]';

            $page_id = wp_insert_post([
                'post_title'   => $title,
                'post_name'    => $desired_slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => $content,
            ], true);

            if ( is_wp_error($page_id) ) {
                error_log('[Voyect][Admin_Controller] Error creando página canónica: '.$page_id->get_error_message());
                add_option('_voyect_notice', 'canonical_error', '', false);
                update_option('_voyect_notice', 'canonical_error');
                wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
                exit;
            }

            update_option('voyect_canonical_page_id', (int)$page_id);
            // Sincronizamos el option del slug del CPT con el slug real de la página creada
            $this->sync_slug_with_canonical_page( (int)$page_id );

            error_log('[Voyect][Admin_Controller] Página canónica creada ID='.$page_id.' slug='.$desired_slug);

            add_option('_voyect_notice', 'canonical_created', '', false);
            update_option('_voyect_notice', 'canonical_created');

            flush_rewrite_rules();
            wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
            exit;
        }

        // ====== Flush manual ======
        if ( isset($_GET['voyect_action']) && $_GET['voyect_action'] === 'flush' ) {
            check_admin_referer('voyect_settings_action', 'voyect_nonce');
            flush_rewrite_rules();
            error_log('[Voyect][Admin_Controller] flush_rewrite_rules ejecutado desde botón');

            add_option('_voyect_notice', 'flushed', '', false);
            update_option('_voyect_notice', 'flushed');

            wp_safe_redirect( admin_url('admin.php?page=voyect-settings') );
            exit;
        }
    }

    /** Avisos */
    public function maybe_admin_notice() {
        if ( ! current_user_can('manage_options') ) return;
        $flag = get_option('_voyect_notice');
        if ( ! $flag ) return;

        $msg = '';
        switch ($flag) {
            case 'saved':
                $msg = __('Configuración guardada. Se actualizaron las reglas de enlace permanente.', 'voyect');
                echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'flushed':
                $msg = __('Reglas de enlaces permanentes regeneradas (flush).', 'voyect');
                echo '<div class="notice notice-info is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'canonical_created':
                $msg = __('Página canónica creada y asignada. 🎉', 'voyect');
                echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'canonical_set':
                $msg = __('Se asignó como canónica una página existente con ese slug.', 'voyect');
                echo '<div class="notice notice-info is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'canonical_exists':
                $msg = __('Ya hay una página canónica asignada. No se creó otra.', 'voyect');
                echo '<div class="notice notice-warning is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'canonical_error':
                $msg = __('No se pudo crear la página canónica. Revisa el log.', 'voyect');
                echo '<div class="notice notice-error is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'slug_synced':
                $msg = __('Slug del portafolio sincronizado con la página canónica.', 'voyect');
                echo '<div class="notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
            case 'canonical_cleared':
                $msg = __('Página canónica quitada. Ahora puedes crear/seleccionar otra y/o editar el slug manual.', 'voyect');
                echo '<div class="notice notice-info is-dismissible"><p>'.esc_html($msg).'</p></div>';
                break;
        }
        delete_option('_voyect_notice');
    }

    // =======================
    // 🔸 FIX HIGHLIGHT MENÚ
    // =======================

    public function fix_parent_menu_highlight( $parent_file ) {
        global $pagenow, $typenow, $taxnow;

        // Logs de depuración
        error_log('[Voyect][Admin_Controller] fix_parent_menu_highlight pagenow=' . $pagenow . ' typenow=' . $typenow . ' taxnow=' . (string) $taxnow);

        // Editar términos de la taxonomía del CPT
        if ( $pagenow === 'edit-tags.php'
             && isset($taxnow) && $taxnow === 'voyect_category'
             && ( isset($_GET['post_type']) ? $_GET['post_type'] === 'voyect_project' : ($typenow === 'voyect_project') ) ) {
            $parent_file = 'voyect';
        }

        // También mantenemos activo en la página de configuraciones
        if ( $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'voyect-settings' ) {
            $parent_file = 'voyect';
        }

        return $parent_file;
    }

    public function fix_submenu_highlight( $submenu_file ) {
        global $pagenow, $typenow, $taxnow;

        // Categorías del CPT
        if ( $pagenow === 'edit-tags.php'
             && isset($taxnow) && $taxnow === 'voyect_category'
             && ( isset($_GET['post_type']) ? $_GET['post_type'] === 'voyect_project' : ($typenow === 'voyect_project') ) ) {
            $submenu_file = 'edit-tags.php?taxonomy=voyect_category&post_type=voyect_project';
        }

        // Configuraciones
        if ( $pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'voyect-settings' ) {
            $submenu_file = 'voyect-settings';
        }

        error_log('[Voyect][Admin_Controller] fix_submenu_highlight submenu_file=' . $submenu_file);
        return $submenu_file;
    }
}
