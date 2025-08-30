<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Ajax_Controller {

    public function register_hooks( $loader ) {
        $loader->add_action('wp_ajax_voyect_get_projects',        $this, 'get_projects');
        $loader->add_action('wp_ajax_nopriv_voyect_get_projects',  $this, 'get_projects');

        $loader->add_action('wp_ajax_voyect_get_cats',            $this, 'get_cats');
        $loader->add_action('wp_ajax_nopriv_voyect_get_cats',     $this, 'get_cats');

        $loader->add_action('wp_ajax_voyect_create_project',      $this, 'create_project');
        $loader->add_action('wp_ajax_voyect_update_project',      $this, 'update_project');
        $loader->add_action('wp_ajax_voyect_delete_project',      $this, 'delete_project');
        $loader->add_action('wp_ajax_voyect_duplicate_project',   $this, 'duplicate_project');

        $loader->add_action('wp_ajax_voyect_save_order',          $this, 'save_order');

        $loader->add_action('wp_ajax_voyect_check_slug_unique',        $this, 'check_slug_unique');
        $loader->add_action('wp_ajax_nopriv_voyect_check_slug_unique', $this, 'check_slug_unique');
    }

    /** --------------------------------------------------------------
     * Helper: permalink amigable siempre (respeta el slug base)
     * -------------------------------------------------------------- */
    protected function pretty_link_for( $post_id ) {
        $slug = get_post_field( 'post_name', $post_id );
        if ( ! $slug ) {
            return get_permalink( $post_id ); // último recurso
        }
        // base configurable desde "Configuraciones" (voyect/cpt_rewrite_slug)
        $base = apply_filters( 'voyect/cpt_rewrite_slug', 'proyectos' );
        $base = trim( (string) $base, '/' );
        $path = $base ? "$base/$slug" : $slug;

        $url = home_url( user_trailingslashit( $path ) );

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log( "[Voyect][pretty_link_for] id=$post_id slug=$slug base=$base url=$url" );
        }
        return $url;
    }

    /** ========== READ ========== */
    public function get_projects() {
        // ¿Usuario admin con permisos? (sólo entonces verificamos nonce)
        $is_admin = is_user_logged_in() && current_user_can('manage_options');
        if ( $is_admin ) {
            Voyect_Ajax_Handler::verify();
        }

        // ---- Flags para forzar SOLO publicados desde el frontend ----
        $only_publish_flag =
            isset($_REQUEST['only_publish']) ? filter_var($_REQUEST['only_publish'], FILTER_VALIDATE_BOOLEAN)
          : (isset($_REQUEST['onlyPublish']) ? filter_var($_REQUEST['onlyPublish'], FILTER_VALIDATE_BOOLEAN)
          : false);

        $requested_status  = isset($_REQUEST['status']) ? sanitize_key($_REQUEST['status']) : '';
        $force_only_publish = $only_publish_flag || ($requested_status === 'publish');

        $page      = max( 1, intval( $_GET['page']      ?? 1 ) );
        $per_page  = max( 1, intval( $_GET['per_page']  ?? 10 ) );
        $search    = sanitize_text_field( $_GET['search'] ?? '' );
        $term_id   = isset($_GET['term']) ? intval($_GET['term']) : 0;
        $orderby   = sanitize_key( $_GET['orderby'] ?? 'menu_order' );
        $order     = strtoupper( $_GET['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

        $tax_query = [];
        if ( $term_id ) {
            $tax_query[] = [
                'taxonomy' => Voyect_Project_Model::TAXONOMY,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ];
        }

        // Estado
        if ( ! $is_admin ) {
            $post_status = [ 'publish' ];
        } else {
            $post_status = $force_only_publish
                ? [ 'publish' ]
                : [ 'publish', 'draft', 'pending', 'future', 'private' ];
        }

        $args = [
            'post_type'      => Voyect_Project_Model::CPT,
            'post_status'    => $post_status,
            'paged'          => $page,
            'posts_per_page' => $per_page,
            's'              => $search,
            'tax_query'      => $tax_query,
            'orderby'        => $orderby,
            'order'          => $order,
            'no_found_rows'  => false,
        ];

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[Voyect][get_projects] args='. json_encode($args));
        }

        $q = new WP_Query( $args );

        $items = [];
        foreach ( $q->posts as $p ) {
            $cats       = get_the_terms( $p->ID, Voyect_Project_Model::TAXONOMY );
            $thumb_id   = get_post_thumbnail_id( $p->ID );

            $created_raw  = get_post_field('post_date',     $p->ID);
            $modified_raw = get_post_field('post_modified', $p->ID);

            $created_ts   = $created_raw  ? strtotime($created_raw)  : 0;
            $modified_ts  = $modified_raw ? strtotime($modified_raw) : 0;
            $created_fmt  = $created_ts   ? date_i18n('Y-m-d H:i', $created_ts)   : '';
            $modified_fmt = $modified_ts  ? date_i18n('Y-m-d H:i', $modified_ts)  : '';

            $items[] = [
                'id'          => $p->ID,
                'title'       => get_the_title($p),
                'slug'        => $p->post_name,
                'status'      => $p->post_status,
                'order'       => (int) get_post_field('menu_order', $p->ID),
                'cats'        => $cats ? array_values( wp_list_pluck( $cats, 'name' ) ) : [],
                'thumbId'     => $thumb_id ? (int) $thumb_id : 0,
                'thumb'       => $thumb_id ? ( get_the_post_thumbnail_url( $p->ID, 'medium' ) ?: '' ) : '',
                'editLink'    => get_edit_post_link( $p->ID, '' ),
                // Siempre devolvemos permalink amigable
                'viewLink'    => $this->pretty_link_for( $p->ID ),

                'created'     => $created_raw,
                'modified'    => $modified_raw,
                'createdFmt'  => $created_fmt,
                'modifiedFmt' => $modified_fmt,
                'createdTs'   => $created_ts,
                'modifiedTs'  => $modified_ts,
            ];
        }

        Voyect_Ajax_Handler::success([
            'items'       => $items,
            'found_posts' => (int) $q->found_posts,
            'max_pages'   => (int) $q->max_num_pages,
            'page'        => $page,
        ]);
    }

    public function get_cats() {
        $terms = get_terms([
            'taxonomy'   => Voyect_Project_Model::TAXONOMY,
            'hide_empty' => false,
        ]);

        if ( is_wp_error($terms) ) {
            Voyect_Ajax_Handler::error( $terms->get_error_message(), 400 );
        }

        $cats = array_map( function($t){
            return [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ];
        }, $terms );

        Voyect_Ajax_Handler::success([ 'categories' => $cats ]);
    }

    /** ========== CREATE ========== */
    public function create_project() {
        Voyect_Ajax_Handler::verify();

        $title = sanitize_text_field( $_POST['title'] ?? '' );
        if ( $title === '' ) {
            Voyect_Ajax_Handler::error( __('El título es obligatorio','voyect'), 422 );
        }

        $slug  = sanitize_title( $_POST['slug'] ?? '' );
        if ( $slug === '' ) $slug = sanitize_title( $title );
        $s1 = wp_unique_post_slug( $slug, 0, 'draft', Voyect_Project_Model::CPT, 0 );
        $slug_unique = $this->ensure_unique_slug( $s1, 0 );

        $cats   = isset($_POST['cats']) ? (array) $_POST['cats'] : [];
        $thumb  = isset($_POST['thumb']) ? intval($_POST['thumb']) : 0;

        $post_id = wp_insert_post([
            'post_type'   => Voyect_Project_Model::CPT,
            'post_title'  => $title,
            'post_name'   => $slug_unique,
            'post_status' => 'draft',
            'menu_order'  => 0,
        ], true);

        if ( is_wp_error($post_id) ) {
            Voyect_Ajax_Handler::error( $post_id->get_error_message(), 400 );
        }

        if ( ! empty($cats) ) {
            wp_set_object_terms( $post_id, array_map('intval',$cats), Voyect_Project_Model::TAXONOMY );
        }
        if ( $thumb ) {
            set_post_thumbnail( $post_id, $thumb );
        }

        Voyect_Ajax_Handler::success([ 'id' => (int) $post_id ], 201);
    }

    /** ========== UPDATE ========== */
    public function update_project() {
        Voyect_Ajax_Handler::verify();

        $id    = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) Voyect_Ajax_Handler::error( __('ID inválido','voyect'), 422 );

        $title  = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : null;
        $slug   = isset($_POST['slug'])  ? sanitize_title($_POST['slug'])      : null;
        $cats   = isset($_POST['cats'])  ? (array) $_POST['cats']               : null;
        $thumb  = isset($_POST['thumb']) ? intval($_POST['thumb'])              : null;
        $status = isset($_POST['status'])? sanitize_key($_POST['status'])       : null;
        $date_in= isset($_POST['date'])  ? sanitize_text_field($_POST['date'])  : null; // "Y-m-d H:i:s"

        $upd = [ 'ID' => $id ];

        if ( $title !== null ) $upd['post_title'] = $title;

        if ( $slug !== null ) {
            if ( $slug === '' ) {
                $slug = sanitize_title( $title !== null ? $title : get_post_field('post_title', $id) );
            }
            $current_status = get_post_status( $id ) ?: 'draft';
            $s1   = wp_unique_post_slug( $slug, $id, $current_status, Voyect_Project_Model::CPT, 0 );
            $slug = $this->ensure_unique_slug( $s1, $id );
            $upd['post_name'] = $slug;
        }

        // Fechas
        $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone( get_option('timezone_string') ?: 'UTC' );
        $modified_dt = new DateTime( 'now', $tz );
        $upd['post_modified'] = $modified_dt->format('Y-m-d H:i:s');
        $modified_utc = clone $modified_dt; $modified_utc->setTimezone( new DateTimeZone('UTC') );
        $upd['post_modified_gmt'] = $modified_utc->format('Y-m-d H:i:s');

        if ( $date_in !== null && $date_in !== '' ) {
            $dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $date_in, $tz );
            if ( ! $dt ) { $dt = DateTime::createFromFormat( 'Y-m-d H:i', $date_in, $tz ); }
            if ( ! $dt ) {
                $ts = strtotime( $date_in );
                if ( $ts !== false ) $dt = (new DateTime('@'.$ts))->setTimezone( $tz );
            }
            if ( $dt ) {
                $upd['post_date'] = $dt->format('Y-m-d H:i:s');
                $dt_utc = clone $dt; $dt_utc->setTimezone( new DateTimeZone('UTC') );
                $upd['post_date_gmt'] = $dt_utc->format('Y-m-d H:i:s');
                $upd['edit_date'] = true;

                $desired_status = $status !== null ? $status : get_post_status($id);
                $now = new DateTime( 'now', $tz );
                if ( $desired_status === 'publish' && $dt > $now ) $upd['post_status'] = 'future';
                if ( $desired_status === 'future' && $dt <= $now ) $upd['post_status'] = 'publish';
            } else {
                error_log('[Voyect][update_project] Fecha inválida: '.$date_in);
            }
        }

        if ( $status !== null && ! isset($upd['post_status']) ) {
            $upd['post_status'] = $status;
        }

        if ( count($upd) > 1 ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('[Voyect][update_project] payload: '. json_encode($upd));
            }
            $res = wp_update_post( $upd, true );
            if ( is_wp_error($res) ) {
                Voyect_Ajax_Handler::error( $res->get_error_message(), 400 );
            }
            clean_post_cache( $id );
        }

        if ( $cats !== null ) {
            wp_set_object_terms( $id, array_map('intval',$cats), Voyect_Project_Model::TAXONOMY );
        }

        $final_thumb_id = get_post_thumbnail_id( $id );
        if ( $thumb !== null ) {
            if ( $thumb ) {
                if ( set_post_thumbnail( $id, $thumb ) ) { $final_thumb_id = $thumb; }
            } else {
                delete_post_thumbnail( $id );
                $final_thumb_id = 0;
            }
        }

        // Respuesta consistente
        $p            = get_post( $id );
        $cats_terms   = get_the_terms( $id, Voyect_Project_Model::TAXONOMY );
        $cats_names   = $cats_terms && ! is_wp_error($cats_terms) ? array_values( wp_list_pluck( $cats_terms, 'name' ) ) : [];
        $thumb_url    = $final_thumb_id ? ( get_the_post_thumbnail_url( $id, 'medium' ) ?: '' ) : '';

        $created_raw   = get_post_field('post_date',     $id);
        $modified_raw  = get_post_field('post_modified', $id);
        $created_ts    = $created_raw  ? strtotime($created_raw)  : 0;
        $modified_ts   = $modified_raw ? strtotime($modified_raw) : 0;

        $payload = [
            'id'          => $id,
            'title'       => $p ? $p->post_title : '',
            'slug'        => isset($upd['post_name']) ? $upd['post_name'] : get_post_field('post_name', $id),
            'status'      => isset($upd['post_status']) ? $upd['post_status'] : get_post_status($id),
            'order'       => (int) get_post_field('menu_order', $id),
            'cats'        => $cats_names,
            'thumbId'     => (int) $final_thumb_id,
            'thumb'       => $thumb_url,
            'editLink'    => get_edit_post_link( $id, '' ),
            // Siempre permalink amigable:
            'viewLink'    => $this->pretty_link_for( $id ),

            'created'     => $created_raw,
            'modified'    => $modified_raw,
            'createdFmt'  => $created_ts  ? date_i18n('Y-m-d H:i', $created_ts)  : '',
            'modifiedFmt' => $modified_ts ? date_i18n('Y-m-d H:i', $modified_ts) : '',
            'createdTs'   => $created_ts,
            'modifiedTs'  => $modified_ts,
        ];

        Voyect_Ajax_Handler::success( $payload );
    }

    /** ========== DELETE ========== */
    public function delete_project() {
        Voyect_Ajax_Handler::verify();

        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) Voyect_Ajax_Handler::error( __('ID inválido','voyect'), 422 );

        $force = isset($_POST['force']) ? (bool) $_POST['force'] : false;
        $res   = $force ? wp_delete_post( $id, true ) : wp_trash_post( $id );

        if ( ! $res ) Voyect_Ajax_Handler::error( __('No se pudo eliminar','voyect'), 400 );
        Voyect_Ajax_Handler::success([ 'deleted' => true ]);
    }

    /** ========== DUPLICATE ========== */
    public function duplicate_project() {
        Voyect_Ajax_Handler::verify();

        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) Voyect_Ajax_Handler::error( __('ID inválido','voyect'), 422 );

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== Voyect_Project_Model::CPT ) {
            Voyect_Ajax_Handler::error( __('Proyecto no encontrado','voyect'), 404 );
        }

        $base_title  = $post->post_title . ' (Copia)';
        $base_slug   = sanitize_title( $base_title );
        $s1          = wp_unique_post_slug( $base_slug, 0, 'draft', Voyect_Project_Model::CPT, 0 );
        $slug_unique = $this->ensure_unique_slug( $s1, 0 );

        $new_id = wp_insert_post([
            'post_type'    => Voyect_Project_Model::CPT,
            'post_title'   => $base_title,
            'post_name'    => $slug_unique,
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'menu_order'   => (int) get_post_field('menu_order', $id) + 1,
        ], true);

        if ( is_wp_error($new_id) ) {
            Voyect_Ajax_Handler::error( $new_id->get_error_message(), 400 );
        }

        $terms = wp_get_object_terms( $id, Voyect_Project_Model::TAXONOMY, [ 'fields' => 'ids' ] );
        if ( $terms && ! is_wp_error($terms) ) {
            wp_set_object_terms( $new_id, $terms, Voyect_Project_Model::TAXONOMY );
        }

        $thumb_id = get_post_thumbnail_id( $id );
        if ( $thumb_id ) {
            set_post_thumbnail( $new_id, $thumb_id );
        }

        Voyect_Ajax_Handler::success([ 'id' => (int) $new_id ], 201);
    }

    /** ========== ORDER ========== */
    public function save_order() {
        Voyect_Ajax_Handler::verify();

        $order = isset($_POST['order']) ? json_decode( stripslashes($_POST['order']), true ) : [];
        if ( ! is_array($order) ) {
            Voyect_Ajax_Handler::error( __('Formato de orden inválido','voyect'), 422 );
        }

        $pos = 0;
        foreach ( $order as $post_id ) {
            wp_update_post([
                'ID'         => intval($post_id),
                'menu_order' => $pos++
            ]);
        }
        Voyect_Ajax_Handler::success([ 'saved' => true ]);
    }

    /** ========== CHECK SLUG UNIQUE ========== */
    public function check_slug_unique() {
        $title   = sanitize_text_field( $_REQUEST['title']   ?? '' );
        $slug    = sanitize_title(     $_REQUEST['slug']    ?? '' );
        $exclude = intval(             $_REQUEST['exclude'] ?? 0  );

        if ( $slug === '' && $title !== '' ) {
            $slug = sanitize_title( $title );
        }
        if ( $slug === '' ) {
            Voyect_Ajax_Handler::error( __('Slug vacío','voyect'), 422 );
        }

        $suggested = wp_unique_post_slug(
            $slug,
            $exclude,
            'draft',
            Voyect_Project_Model::CPT,
            0
        );
        $suggested = $this->ensure_unique_slug( $suggested, $exclude );

        $unique = ( $suggested === $slug );

        Voyect_Ajax_Handler::success([
            'unique'    => $unique,
            'slug'      => $slug,
            'suggested' => $suggested,
        ]);
    }

    protected function ensure_unique_slug( $slug, $exclude_id = 0 ) {
        $base = $slug;
        $i    = 2;
        while ( $this->slug_exists_in_cpt( $slug, $exclude_id ) ) {
            $slug = $base . '-' . $i;
            $i++;
            if ( $i > 60 ) break;
        }
        return $slug;
    }

    protected function slug_exists_in_cpt( $slug, $exclude_id = 0 ) {
        $args = [
            'post_type'      => Voyect_Project_Model::CPT,
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ];
        if ( $exclude_id ) {
            $args['post__not_in'] = [ $exclude_id ];
        }
        $q = new WP_Query( $args );
        return ! empty( $q->posts );
    }
}
