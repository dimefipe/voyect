<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Voyect — Generador de Shortcode (vista admin)
 * - Añade select "Card (Elementor)" y select "Grid (preset)"
 * - Mantiene controles existentes (per_page, search, filters, pagination, show_cats, orderby, order)
 * - Logs: error_log en cargas y console.log en cambios
 */

// === Carga de plantillas de Elementor (si está activo) =======================
$elementor_loaded = did_action('elementor/loaded');
$voyect_templates = [];

if ( $elementor_loaded ) {
  $tpl_q = new WP_Query([
    'post_type'      => 'elementor_library',
    'posts_per_page' => 200,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
    'no_found_rows'  => true,
  ]);
  if ( $tpl_q->have_posts() ) {
    foreach ( $tpl_q->posts as $p ) {
      $voyect_templates[] = ['id' => $p->ID, 'title' => $p->post_title];
    }
  }
  wp_reset_postdata();
  error_log('[Voyect][ShortcodeGen] Elementor detectado. Plantillas: '.count($voyect_templates));
} else {
  error_log('[Voyect][ShortcodeGen][WARN] Elementor no detectado. Ocultaremos el select de Card.');
}

// === Carga de presets de Grid (CPT: voyect_grid) =============================
$voyect_grids = get_posts([
  'post_type'      => 'voyect_grid',
  'numberposts'    => 200,
  'post_status'    => 'publish',
  'orderby'        => 'title',
  'order'          => 'ASC',
  'suppress_filters'=> true,
]);

error_log('[Voyect][ShortcodeGen] Presets de Grid encontrados: '.count($voyect_grids));

// Valores por defecto del generador
$defaults = [
  'per_page'   => 9,
  'search'     => true,
  'filters'    => true,
  'pagination' => true,
  'show_cats'  => true,
  'orderby'    => 'date',
  'order'      => 'desc',
  'template_id'=> '',
  'grid_id'    => '',
];

?>
<div class="wrap voyect-shortcode-generator">
  <h1 style="margin-bottom:14px;">Generador de Shortcode</h1>

  <div class="voyect-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <!-- Columna izquierda: configuración -->
    <div>

      <div class="card" style="padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:16px;">
        <h2 style="margin:0 0 12px;font-size:16px;">Configuración Shortcode</h2>

        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row"><label for="voyect_per_page">Cantidad por página</label></th>
              <td><input type="number" min="1" id="voyect_per_page" value="<?php echo esc_attr($defaults['per_page']); ?>" style="width:120px"></td>
            </tr>

            <tr>
              <th scope="row">Opciones</th>
              <td>
                <label><input type="checkbox" id="voyect_search" checked> Buscador</label><br>
                <label><input type="checkbox" id="voyect_filters" checked> Filtros por categoría</label><br>
                <label><input type="checkbox" id="voyect_pagination" checked> Paginador</label><br>
                <label><input type="checkbox" id="voyect_show_cats" checked> Mostrar categorías como pastillas</label>
              </td>
            </tr>

            <tr>
              <th scope="row"><label for="voyect_orderby">Ordenar por</label></th>
              <td>
                <select id="voyect_orderby">
                  <option value="date" selected>Fecha (creación)</option>
                  <option value="modified">Fecha (modificación)</option>
                  <option value="title">Título</option>
                  <option value="menu_order">Orden manual (menu_order)</option>
                </select>

                <select id="voyect_order">
                  <option value="desc" selected>Descendente</option>
                  <option value="asc">Ascendente</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- NUEVO: Select de Card (Elementor) -->
      <div class="card" style="padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:16px;<?php echo $elementor_loaded ? '' : 'opacity:.6;pointer-events:none;'; ?>">
        <h2 style="margin:0 0 12px;font-size:16px;">Card (Plantilla de Elementor)</h2>
        <?php if ( $elementor_loaded ): ?>
          <select id="voyect_template_id" style="min-width:320px;">
            <option value="">— Usar card por defecto del plugin —</option>
            <?php foreach ($voyect_templates as $t): ?>
              <option value="<?php echo (int) $t['id']; ?>">
                <?php echo esc_html($t['title']); ?> (ID: <?php echo (int) $t['id']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <p style="margin:.5rem 0 0;">
            <a class="button button-secondary" href="<?php echo esc_url( admin_url('edit.php?post_type=elementor_library') ); ?>" target="_blank">Abrir plantillas</a>
          </p>
        <?php else: ?>
          <p><em>Elementor no está activo. Instálalo/actívalo para usar una plantilla como “Card”.</em></p>
        <?php endif; ?>
      </div>

      <!-- NUEVO: Select de Grid (preset) -->
      <div class="card" style="padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:16px;">
        <h2 style="margin:0 0 12px;font-size:16px;">Grid (Preset)</h2>
        <select id="voyect_grid_id" style="min-width:320px;">
          <option value="">— Sin preset (usar valores por defecto del shortcode) —</option>
          <?php foreach ($voyect_grids as $g): ?>
            <option value="<?php echo (int) $g->ID; ?>">
              <?php echo esc_html($g->post_title); ?> (ID: <?php echo (int) $g->ID; ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <p style="margin:.5rem 0 0;">
          <a class="button button-secondary" href="<?php echo esc_url( admin_url('post-new.php?post_type=voyect_grid') ); ?>" target="_blank">+ Nuevo Grid</a>
        </p>
      </div>

    </div>

    <!-- Columna derecha: salida de shortcode -->
    <div>
      <div class="card" style="padding:16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
        <h2 style="margin:0 0 12px;font-size:16px;">Creación de Shortcode</h2>
        <textarea id="voyect_shortcode_output" rows="3" style="width:100%;font-family:monospace;"><?php
          echo esc_textarea('[voyect search="true" filters="true" pagination="true" show_cats="true" per_page="9" orderby="date" order="desc"]');
        ?></textarea>
        <p style="margin-top:10px;">
          <button class="button" id="voyect_copy_btn">📋 Copiar shortcode</button>
        </p>
        <p style="margin-top:10px;color:#64748b"><small>Tip: pega este shortcode en cualquier página. Si seleccionas una Card de Elementor o un Grid preset, los atributos <code>template_id</code> y <code>grid_id</code> se añadirán automáticamente.</small></p>
      </div>
    </div>

  </div><!-- /.voyect-grid -->

  <script>
    (function(){
      // Logs al cargar
      console.log('[Voyect][ShortcodeGen] UI cargada');

      const $ = (sel)=>document.querySelector(sel);

      const els = {
        perPage:   $('#voyect_per_page'),
        search:    $('#voyect_search'),
        filters:   $('#voyect_filters'),
        pagination:$('#voyect_pagination'),
        showCats:  $('#voyect_show_cats'),
        orderby:   $('#voyect_orderby'),
        order:     $('#voyect_order'),
        tpl:       $('#voyect_template_id'),
        grid:      $('#voyect_grid_id'),
        out:       $('#voyect_shortcode_output'),
        copy:      $('#voyect_copy_btn'),
      };

      function buildShortcode(){
        const attrs = [];
        if (els.search.checked)    attrs.push('search="true"');
        if (els.filters.checked)   attrs.push('filters="true"');
        if (els.pagination.checked)attrs.push('pagination="true"');
        if (els.showCats.checked)  attrs.push('show_cats="true"');

        const pp = parseInt(els.perPage.value,10) || 9;
        attrs.push(`per_page="${pp}"`);

        attrs.push(`orderby="${els.orderby.value}"`);
        attrs.push(`order="${els.order.value}"`);

        const tplId = (els.tpl && els.tpl.value || '').trim();
        if (tplId) attrs.push(`template_id="${tplId}"`);

        const gridId = (els.grid && els.grid.value || '').trim();
        if (gridId) attrs.push(`grid_id="${gridId}"`);

        const sc = `[voyect ${attrs.join(' ')}]`;
        els.out.value = sc;

        // Log
        console.log('[Voyect][ShortcodeGen] Shortcode actualizado:', sc);
      }

      ['change','input'].forEach(ev => {
        for (const k in els) {
          if (els[k]) els[k].addEventListener(ev, buildShortcode);
        }
      });

      if (els.copy) {
        els.copy.addEventListener('click', function(e){
          e.preventDefault();
          els.out.select(); document.execCommand('copy');
          console.log('[Voyect][ShortcodeGen] Shortcode copiado al portapapeles');
          this.textContent = '✅ Copiado';
          setTimeout(()=>{ this.textContent = '📋 Copiar shortcode'; }, 1200);
        });
      }

      buildShortcode();
    })();
  </script>

  <style>
    .voyect-shortcode-generator .card h2{font-weight:600}
    .voyect-shortcode-generator .form-table th{width:220px}
  </style>
</div>

<!-- estamos ok sin errores  -->