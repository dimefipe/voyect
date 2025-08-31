<?php
/**
 * Voyect: Grid Presets (CPT)
 * - CPT: voyect_grid
 * - Metabox: cols_desktop, cols_tablet, cols_mobile, gap, container
 * - Helper: Voyect_Grids::get_preset( $grid_id )
 *
 * Logs de depuración:
 * - error_log en registro, guardado y lectura de presets.
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('Voyect_Grids') ) {

class Voyect_Grids {

  public static function boot() {
    add_action('init',               [__CLASS__, 'register_cpt']);
    add_action('add_meta_boxes',     [__CLASS__, 'add_metabox']);
    add_action('save_post',          [__CLASS__, 'save_metabox']);

    // Log de arranque
    error_log('[Voyect][Grids] boot() inicializado');
  }

  /** ======================
   *  CPT: voyect_grid
   *  ====================== */
  public static function register_cpt() {
    $labels = [
      'name'               => __('Grids', 'voyect'),
      'singular_name'      => __('Grid', 'voyect'),
      'add_new'            => __('Añadir nuevo', 'voyect'),
      'add_new_item'       => __('Nuevo Grid', 'voyect'),
      'edit_item'          => __('Editar Grid', 'voyect'),
      'view_item'          => __('Ver Grid', 'voyect'),
      'search_items'       => __('Buscar Grids', 'voyect'),
      'not_found'          => __('No se encontraron Grids', 'voyect'),
      'not_found_in_trash' => __('No hay Grids en la papelera', 'voyect'),
      'menu_name'          => __('Grids', 'voyect'),
    ];

    register_post_type('voyect_grid', [
      'labels'        => $labels,
      'public'        => false,
      'show_ui'       => true,
      'show_in_menu'  => 'edit.php?post_type=voyect', // aparece bajo "Voyect"
      'menu_icon'     => 'dashicons-screenoptions',
      'supports'      => ['title'],
      'capability_type' => 'post',
      'map_meta_cap'  => true,
    ]);

    error_log('[Voyect][Grids] CPT "voyect_grid" registrado');
  }

  /** ======================
   *  Metabox
   *  ====================== */
  public static function add_metabox() {
    add_meta_box(
      'voyect_grid_meta',
      __('Configuración de Grid', 'voyect'),
      [__CLASS__, 'render_metabox'],
      'voyect_grid',
      'normal',
      'default'
    );
  }

  public static function render_metabox($post){
    wp_nonce_field('voyect_grid_meta', 'voyect_grid_nonce');

    $get = function($k,$d=''){ $v = get_post_meta($post->ID,$k,true); return $v !== '' ? $v : $d; };

    $cols_desktop = (int)$get('_voyect_cols_desktop', 4);
    $cols_tablet  = (int)$get('_voyect_cols_tablet', 2);
    $cols_mobile  = (int)$get('_voyect_cols_mobile', 1);
    $gap          = (int)$get('_voyect_gap', 24);
    $container    = $get('_voyect_container', ''); // px o vacío

    ?>
    <style>
      .voyect-grid-table td{padding:8px 10px; vertical-align: middle;}
      .voyect-grid-table input[type="number"]{width:130px;}
    </style>
    <table class="voyect-grid-table">
      <tr>
        <td><label for="voyect_cols_desktop"><strong><?php _e('Columnas Desktop','voyect'); ?></strong></label></td>
        <td><input type="number" min="1" max="12" id="voyect_cols_desktop" name="voyect_cols_desktop" value="<?php echo esc_attr($cols_desktop); ?>"></td>
      </tr>
      <tr>
        <td><label for="voyect_cols_tablet"><strong><?php _e('Columnas Tablet','voyect'); ?></strong></label></td>
        <td><input type="number" min="1" max="12" id="voyect_cols_tablet" name="voyect_cols_tablet" value="<?php echo esc_attr($cols_tablet); ?>"></td>
      </tr>
      <tr>
        <td><label for="voyect_cols_mobile"><strong><?php _e('Columnas Mobile','voyect'); ?></strong></label></td>
        <td><input type="number" min="1" max="12" id="voyect_cols_mobile" name="voyect_cols_mobile" value="<?php echo esc_attr($cols_mobile); ?>"></td>
      </tr>
      <tr>
        <td><label for="voyect_gap"><strong><?php _e('Gap (px)','voyect'); ?></strong></label></td>
        <td><input type="number" min="0" id="voyect_gap" name="voyect_gap" value="<?php echo esc_attr($gap); ?>"></td>
      </tr>
      <tr>
        <td><label for="voyect_container"><strong><?php _e('Máx. ancho contenedor (px)','voyect'); ?></strong></label></td>
        <td>
          <input type="number" min="0" id="voyect_container" name="voyect_container" value="<?php echo esc_attr($container); ?>">
          <em><?php _e('(vacío = full width)', 'voyect'); ?></em>
        </td>
      </tr>
    </table>
    <p><em><?php _e('Sugerencia: nómbralo según breakpoint, ej. "Desktop 4col / Tablet 2 / Mobile 1".', 'voyect'); ?></em></p>
    <?php
  }

  public static function save_metabox($post_id){
    if ( !isset($_POST['voyect_grid_nonce']) || !wp_verify_nonce($_POST['voyect_grid_nonce'],'voyect_grid_meta') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    $int = function($k,$min=null,$max=null){
      if (!isset($_POST[$k]) || $_POST[$k]==='') return '';
      $v = (int)$_POST[$k];
      if ($min!==null) $v = max($min,$v);
      if ($max!==null) $v = min($max,$v);
      return $v;
    };

    $meta = [
      '_voyect_cols_desktop' => $int('voyect_cols_desktop',1,12),
      '_voyect_cols_tablet'  => $int('voyect_cols_tablet',1,12),
      '_voyect_cols_mobile'  => $int('voyect_cols_mobile',1,12),
      '_voyect_gap'          => $int('voyect_gap',0,120),
      '_voyect_container'    => (isset($_POST['voyect_container']) && $_POST['voyect_container']!=='') ? (int)$_POST['voyect_container'] : '',
    ];

    foreach ($meta as $k=>$v) {
      update_post_meta($post_id, $k, $v);
    }

    // Logs de guardado
    error_log('[Voyect][Grids] Guardado preset #'.$post_id.' -> '. wp_json_encode($meta));
    do_action('voyect/grids/saved', $post_id, $meta);
  }

  /** ======================
   *  Helper público
   *  ====================== */
  public static function get_preset($grid_id){
    $post = get_post($grid_id);
    if (!$post || $post->post_type!=='voyect_grid') {
      error_log('[Voyect][Grids] get_preset(): ID inválido '.$grid_id);
      return null;
    }
    $preset = [
      'cols_desktop' => (int) get_post_meta($grid_id,'_voyect_cols_desktop',true),
      'cols_tablet'  => (int) get_post_meta($grid_id,'_voyect_cols_tablet',true),
      'cols_mobile'  => (int) get_post_meta($grid_id,'_voyect_cols_mobile',true),
      'gap'          => (int) get_post_meta($grid_id,'_voyect_gap',true),
      'container'    => get_post_meta($grid_id,'_voyect_container',true),
    ];
    error_log('[Voyect][Grids] get_preset('.$grid_id.'): '. wp_json_encode($preset));
    return $preset;
  }

} // class

} // if class_exists
