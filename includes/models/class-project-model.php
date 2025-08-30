<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Modelo de datos del Portafolio:
 * - CPT: voyect_project
 * - Taxonomía: voyect_category
 */
class Voyect_Project_Model {

    const CPT      = 'voyect_project';
    const TAXONOMY = 'voyect_category';

    /** Nombre del archivo de plantilla de página del plugin */
    const CANONICAL_PAGE_TEMPLATE = 'voyect-canonical.php';

    /**
     * Devuelve el slug base utilizado para las URLs amigables del CPT.
     * Usamos un helper centralizado para que el mismo valor se comparta
     * entre el registro del CPT y el JS (vía wp_localize_script).
     */
    public static function get_cpt_base(): string {
        $base = apply_filters('voyect/cpt_rewrite_slug', 'proyectos');
        // sanitizar por si acaso
        $base = trim( sanitize_title( $base ) );
        if ( $base === '' ) {
            $base = 'proyectos';
        }
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][Project_Model] get_cpt_base='.$base);
        }
        return $base;
    }

    /**
     * Devuelve el slug base de la taxonomía (por consistencia).
     */
    public static function get_tax_base(): string {
        $base = apply_filters('voyect/tax_rewrite_slug', 'categoria-proyecto');
        $base = trim( sanitize_title( $base ) );
        if ( $base === '' ) {
            $base = 'categoria-proyecto';
        }
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][Project_Model] get_tax_base='.$base);
        }
        return $base;
    }

    public function register_hooks( $loader ) {
        // Registrar CPT y taxonomía primero
        $loader->add_action('init', $this, 'register_post_type', 9);
        $loader->add_action('init', $this, 'register_taxonomy', 9);

        // Redirección del archivo del CPT hacia la página canónica (si existe) + clase body
        $loader->add_action('template_redirect', $this, 'maybe_redirect_archive_to_canonical');
        $loader->add_filter('body_class',        $this, 'maybe_add_body_class');

        // Registrar plantilla de página propia para la canónica (opcional)
        $loader->add_filter('theme_page_templates', $this, 'register_page_template');
        $loader->add_filter('template_include',     $this, 'maybe_use_plugin_page_template');

        // Orden por defecto en admin -> FECHA (CREACIÓN) DESC
        $loader->add_action('pre_get_posts', $this, 'default_admin_order');

        // Columnas admin
        $loader->add_filter('manage_edit-' . self::CPT . '_columns', $this, 'set_admin_columns');
        $loader->add_action('manage_' . self::CPT . '_posts_custom_column', $this, 'render_admin_column', 10, 2);
        $loader->add_filter('manage_edit-' . self::CPT . '_sortable_columns', $this, 'sortable_columns');

        // Submenú nativo de categorías bajo el menú "Voyect"
        $loader->add_action('admin_menu', $this, 'add_taxonomy_submenu');
    }

    /** CPT */
    public function register_post_type() {
        $cpt_slug     = self::get_cpt_base(); // <-- unificado
        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);

        // Si hay página canónica, desactivamos el archivo del CPT para evitar conflicto de layout
        // (las URL de single siguen usando el rewrite 'slug' normalmente).
        $has_archive = $canonical_id ? false : $cpt_slug;

        $labels = [
            'name'               => __('Proyectos', 'voyect'),
            'singular_name'      => __('Proyecto', 'voyect'),
            'menu_name'          => __('Voyect', 'voyect'),
            'add_new'            => __('Añadir nuevo', 'voyect'),
            'add_new_item'       => __('Añadir nuevo proyecto', 'voyect'),
            'edit_item'          => __('Editar proyecto', 'voyect'),
            'new_item'           => __('Nuevo proyecto', 'voyect'),
            'view_item'          => __('Ver proyecto', 'voyect'),
            'search_items'       => __('Buscar proyectos', 'voyect'),
            'not_found'          => __('No se encontraron proyectos', 'voyect'),
            'not_found_in_trash' => __('No hay proyectos en la papelera', 'voyect'),
            'all_items'          => __('Todos los proyectos', 'voyect'),
            'archives'           => __('Archivo de proyectos', 'voyect'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // usamos nuestro propio menú del panel
            'show_in_rest'       => true,
            'hierarchical'       => false,
            // page-attributes => menu_order para drag & drop futuro
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'has_archive'        => $has_archive,
            'rewrite'            => [
                'slug'       => $cpt_slug,
                'with_front' => false,
                'feeds'      => false,
                'pages'      => true,
            ],
            'query_var'          => self::CPT,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-portfolio',
            'capability_type'    => 'post',
            // Asegurar que el metabox y REST muestren la taxonomía
            'taxonomies'         => [ self::TAXONOMY ],
        ];

        register_post_type( self::CPT, $args );
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect] CPT '.self::CPT.' registrado con slug base "'.$cpt_slug.'" has_archive='.var_export($has_archive, true));
        }
    }

    /** Taxonomía de categorías del portafolio */
    public function register_taxonomy() {
        $tax_slug = self::get_tax_base(); // <-- unificado

        $labels = [
            'name'              => __('Categorías', 'voyect'),
            'singular_name'     => __('Categoría', 'voyect'),
            'search_items'      => __('Buscar categorías', 'voyect'),
            'all_items'         => __('Todas las categorías', 'voyect'),
            'edit_item'         => __('Editar categoría', 'voyect'),
            'update_item'       => __('Actualizar categoría', 'voyect'),
            'add_new_item'      => __('Añadir nueva categoría', 'voyect'),
            'new_item_name'     => __('Nombre de nueva categoría', 'voyect'),
            'menu_name'         => __('Categorías', 'voyect'),
        ];

        $args = [
            'hierarchical'       => true,
            'labels'             => $labels,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'rest_base'          => self::TAXONOMY,
            'show_admin_column'  => true,
            'query_var'          => 'voyect_cat',
            'rewrite'            => [
                'slug'         => $tax_slug,
                'with_front'   => false,
                'hierarchical' => true,
            ],
        ];

        register_taxonomy( self::TAXONOMY, [ self::CPT ], $args );
        // Por si algún otro plugin toca args, forzar asociación
        register_taxonomy_for_object_type( self::TAXONOMY, self::CPT );

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect] Tax '.self::TAXONOMY.' registrada y asociada con slug base "'.$tax_slug.'"');
        }
    }

    /** Submenú nativo de categorías bajo el menú principal de Voyect */
    public function add_taxonomy_submenu() {
        $parent_slug = 'voyect'; // admin.php?page=voyect
        $cap         = 'manage_categories'; // o manage_options si prefieres
        $page_title  = __('Categorías', 'voyect');
        $menu_title  = __('Categorías', 'voyect');

        // enlaza al screen nativo de WP para editar términos
        $hook = add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $cap,
            'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::CPT
        );

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect] Submenú de categorías añadido: '.$hook);
        }
    }

    /**
     * Redirige el archivo del CPT (o la query ?post_type=voyect_project)
     * a la página canónica si está configurada. Conserva ?c= y ?cat=.
     */
    public function maybe_redirect_archive_to_canonical() {
        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);
        if ( ! $canonical_id ) return;

        // Si entran por /?post_type=voyect_project o por el archivo del CPT, redirigir.
        if ( is_post_type_archive( self::CPT ) || ( isset($_GET['post_type']) && $_GET['post_type'] === self::CPT ) ) {
            $url = get_permalink( $canonical_id );
            if ( ! $url ) return;

            // Conservar filtros
            $c   = isset($_GET['c'])   ? sanitize_title( wp_unslash($_GET['c']) )   : '';
            $cat = isset($_GET['cat']) ? sanitize_title( wp_unslash($_GET['cat']) ) : '';
            if ( $c || $cat ) {
                $qs = [];
                if ($c)   $qs['c'] = $c;
                elseif($cat) $qs['c'] = $cat; // normalizamos a ?c=
                $url = add_query_arg( $qs, $url );
            }

            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('[Voyect] Redirect archivo CPT -> canónica: '.$url);
            }
            wp_safe_redirect( $url, 301 );
            exit;
        }
    }

    /**
     * Añadimos una clase de ayuda al <body> cuando estamos en la página canónica:
     * permite ocultar título/espaciados del tema con CSS específico si se desea.
     */
    public function maybe_add_body_class( $classes ) {
        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);
        if ( $canonical_id && is_page( $canonical_id ) ) {
            $classes[] = 'voyect-canonical-page';
        }
        return $classes;
    }

    /**
     * Registramos una plantilla de página del plugin para la canónica.
     * Útil para homogeneizar el layout entre temas.
     */
    public function register_page_template( $templates ) {
        $templates[ self::CANONICAL_PAGE_TEMPLATE ] = __('Voyect — Portafolio (plugin)', 'voyect');
        return $templates;
    }

    /**
     * Si la página selecciona nuestra plantilla, cargamos el archivo del plugin.
     */
    public function maybe_use_plugin_page_template( $template ) {
        if ( is_admin() ) return $template;

        $canonical_id = (int) get_option('voyect_canonical_page_id', 0);
        if ( $canonical_id && is_page( $canonical_id ) ) {
            $selected = get_page_template_slug( $canonical_id );
            if ( $selected === self::CANONICAL_PAGE_TEMPLATE ) {
                $tpl = trailingslashit( VOYECT_PATH ) . 'templates/' . self::CANONICAL_PAGE_TEMPLATE;
                if ( file_exists( $tpl ) ) {
                    if ( defined('WP_DEBUG') && WP_DEBUG ) {
                        error_log('[Voyect] Usando plantilla de plugin para canónica: '.$tpl);
                    }
                    return $tpl;
                }
            }
        }
        return $template;
    }

    /**
     * Orden por defecto en admin nativo del CPT:
     * - Si el usuario no especifica, usamos 'date' DESC (fecha de creación).
     * Esto NO afecta al frontend ni a la pantalla AJAX personalizada.
     */
    public function default_admin_order( $query ) {
        if ( is_admin() && $query->is_main_query() && $query->get('post_type') === self::CPT ) {
            if ( ! $query->get('orderby') ) {
                $query->set('orderby', 'date');   // fecha de creación
                $query->set('order',   'DESC');
            }
        }
    }

    /** Columnas personalizadas en listado admin */
    public function set_admin_columns( $columns ) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            if ( $key === 'cb' ) {
                $new[$key] = $label;
            }
            if ( $key === 'title' ) {
                $new['thumbnail']       = __('Imagen', 'voyect');
                $new[$key]              = $label;
                $new['voyect_category'] = __('Categorías', 'voyect');
                $new['menu_order']      = __('Orden', 'voyect');
            }
        }
        return $new;
    }

    public function render_admin_column( $column, $post_id ) {
        switch ( $column ) {
            case 'thumbnail':
                $thumb = get_the_post_thumbnail( $post_id, [60, 60] );
                echo $thumb ? $thumb : '<span style="opacity:.6">'.__('Sin imagen','voyect').'</span>';
                break;
            case 'voyect_category':
                $terms = get_the_terms( $post_id, self::TAXONOMY );
                if ( empty($terms) || is_wp_error($terms) ) {
                    echo '<span style="opacity:.6">'.__('Sin categoría','voyect').'</span>';
                } else {
                    echo esc_html( join(', ', wp_list_pluck($terms, 'name')) );
                }
                break;
            case 'menu_order':
                echo (int) get_post_field( 'menu_order', $post_id );
                break;
        }
    }

    public function sortable_columns( $columns ) {
        $columns['menu_order'] = 'menu_order';
        return $columns;
    }
}
