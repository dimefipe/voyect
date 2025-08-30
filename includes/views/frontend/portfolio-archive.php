<?php if ( ! defined('ABSPATH') ) exit; ?>
<?php
/**
 * Vista: Frontend Archive
 * - Recibe $voyect_props (array) y $voyect_props_json (string JSON escapado) desde el controlador
 * - Si por algún motivo no vienen, construimos defaults seguros aquí.
 */

// Defaults seguros por si el controlador no seteó variables
if ( ! isset($voyect_props) || ! is_array($voyect_props) ) {
    $voyect_props = [
        'search'      => true,
        'filters'     => true,
        'pagination'  => true,
        'per_page'    => 9,
        'orderby'     => 'menu_order',
        'order'       => 'asc',
        'status'      => 'publish',
        'onlyPublish' => true,
    ];
}
if ( ! isset($voyect_props['status']) )      { $voyect_props['status'] = 'publish'; }
if ( ! isset($voyect_props['onlyPublish']) ) { $voyect_props['onlyPublish'] = true; }

// JSON para data-attribute (si no viene desde el controller lo creamos aquí)
if ( ! isset($voyect_props_json) || ! is_string($voyect_props_json) || $voyect_props_json === '' ) {
    $voyect_props_json = esc_attr( wp_json_encode( $voyect_props ) );
}

// Helpers de flags (para mostrar/ocultar controles en el markup inicial)
$show_search     = ! empty($voyect_props['search']);
$show_filters    = ! empty($voyect_props['filters']);
$show_pagination = ! empty($voyect_props['pagination']);
?>
<div id="voyect-archive"
     class="voyect-archive"
     data-props="<?php echo $voyect_props_json; ?>">
  <div class="voyect-grid"><!-- JS pintará aquí --></div>

  <div class="voyect-controls">
    <?php if ( $show_search ): ?>
      <input type="search" id="voyect-search-frontend" placeholder="<?php esc_attr_e('Buscar...', 'voyect'); ?>">
    <?php endif; ?>

    <?php if ( $show_filters ): ?>
      <div id="voyect-filters-frontend" class="voyect-filters"></div>
    <?php endif; ?>

    <?php if ( $show_pagination ): ?>
      <div class="voyect-pager">
        <button id="voyect-prev-frontend" type="button" aria-label="<?php esc_attr_e('Anterior','voyect'); ?>">&laquo;</button>
        <span id="voyect-page-frontend">1</span>
        <button id="voyect-next-frontend" type="button" aria-label="<?php esc_attr_e('Siguiente','voyect'); ?>">&raquo;</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  try {
    var root = document.getElementById('voyect-archive');
    if(!root){ return; }
    var raw = root.getAttribute('data-props') || '{}';
    var props = {};
    try { props = JSON.parse(raw); } catch(e){ props = {}; }

    // En frontend **SIEMPRE** pedimos SOLO publicados
    props.status      = 'publish';
    props.onlyPublish = true;

    // Exponer props para el JS de frontend (si existe)
    window.VoyectFrontendProps = props;

    // Log útil para depuración
    console.debug('[Voyect][frontend-view] props:', props);

    // Si tu JS escucha este evento, puede iniciar el render aquí
    var ev = new CustomEvent('voyect:frontend:ready', { detail: props });
    document.dispatchEvent(ev);
  } catch(e) {
    console.error('[Voyect][frontend-view] Error inicializando props', e);
  }
})();
</script>
