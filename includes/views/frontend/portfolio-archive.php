<?php if ( ! defined('ABSPATH') ) exit; ?>
<?php
/**
 * Vista: Frontend Archive (UI dark, sin Vue, sin APIs)
 * - Recibe $voyect_props y $voyect_props_json
 * - Mantiene data-props + evento 'voyect:frontend:ready'
 * - No añade JS nuevo de comportamiento (tu frontend actual pinta el grid)
 */

// Defaults seguros
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

// JSON para data-attribute
if ( ! isset($voyect_props_json) || ! is_string($voyect_props_json) || $voyect_props_json === '' ) {
    $voyect_props_json = esc_attr( wp_json_encode( $voyect_props ) );
}

// Flags UI
$show_search     = ! empty($voyect_props['search']);
$show_filters    = ! empty($voyect_props['filters']);
$show_pagination = ! empty($voyect_props['pagination']);

error_log('[Voyect][frontend-view] render UI • props=' . wp_json_encode($voyect_props));
?>
<div id="voyect-archive"
     class="voyect-archive voyect-ui"
     data-props="<?php echo $voyect_props_json; ?>">

  <!-- Header: buscador + filtros (UI nueva) -->
  <div class="portafolio__filtros">
    <?php if ( $show_search ): ?>
      <div class="portafolio__buscador--box">
        <input class="portafolio__buscador"
               type="search"
               id="voyect-search-frontend"
               placeholder="<?php esc_attr_e('Buscar proyectos...', 'voyect'); ?>">
      </div>
    <?php endif; ?>

    <?php if ( $show_filters ): ?>
      <div id="voyect-filters-frontend" class="portafolio__filtros--box"><!-- tu JS inyecta botones aquí --></div>
    <?php endif; ?>
  </div>

  <!-- Listado -->
  <div class="portafolio__listado">
    <div class="portafolio__proyectos-wrapper">
      <!-- IMPORTANTE: tu JS actual pinta aquí las cards -->
      <div class="voyect-grid portafolio__proyectos"><!-- JS pintará aquí --></div>
    </div>

    <?php if ( $show_pagination ): ?>
      <div class="portafolio__paginador">
        <button id="voyect-prev-frontend" type="button" aria-label="<?php esc_attr_e('Anterior','voyect'); ?>">&laquo;</button>
        <span id="voyect-page-frontend">1</span>
        <button id="voyect-next-frontend" type="button" aria-label="<?php esc_attr_e('Siguiente','voyect'); ?>">&raquo;</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>

</style>

<script>
(function(){
  // === EXACTO como tu versión original: solo inicializa props y emite el evento ===
  try {
    var root = document.getElementById('voyect-archive');
    if(!root){ return; }
    var raw = root.getAttribute('data-props') || '{}';
    var props = {};
    try { props = JSON.parse(raw); } catch(e){ props = {}; }

    // En frontend SOLO publicados
    props.status      = 'publish';
    props.onlyPublish = true;

    // Exponer props para tu JS actual
    window.VoyectFrontendProps = props;

    // Log útil para depuración
    console.debug('[Voyect][frontend-view] props:', props);

    // Disparar el evento que ya usas para que tu JS pinte el grid
    var ev = new CustomEvent('voyect:frontend:ready', { detail: props });
    document.dispatchEvent(ev);
  } catch(e) {
    console.error('[Voyect][frontend-view] Error inicializando props', e);
  }
})();
</script>
