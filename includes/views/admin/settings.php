<?php
if ( ! defined('ABSPATH') ) exit;
/**
 * Vista: Configuraciones de Voyect
 *
 * Variables esperadas:
 * - $current_slug
 * - $canonical_id
 * - $flush_url
 * - $save_url
 * - $pages (WP_Post[])
 * - $canonical_url
 */
$home = home_url('/');
$maybe_conflict_page = get_page_by_path( $current_slug ?: 'proyectos', OBJECT, 'page' );
$has_conflict = $maybe_conflict_page && (int)$maybe_conflict_page->ID !== (int)$canonical_id;

$canonical_page = $canonical_id ? get_post($canonical_id) : null;
$canonical_on   = $canonical_page && $canonical_page->post_type === 'page';

// Mapa ID→permalink para previews en vivo
$page_links = [];
if ( ! empty($pages) ) {
  foreach ($pages as $pg) {
    $page_links[ (int) $pg->ID ] = get_permalink($pg->ID);
  }
}

if ( defined('WP_DEBUG') && WP_DEBUG ) {
  error_log('[Voyect][settings view] slug='.$current_slug.' canonical_id='.$canonical_id.' conflict_page_id='.($maybe_conflict_page?$maybe_conflict_page->ID:0).' canonical_on=' . ($canonical_on?'1':'0'));
}
?>
<div class="wrap voyect-settings-wrap" style="max-width:1200px;">
  <style>
    .voyect-settings-wrap .voyect-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:22px}
    .voyect-settings-wrap .voyect-grid{display:grid;grid-template-columns:3fr 2fr;gap:32px}
    .voyect-settings-wrap code{background:#f8fafc;padding:2px 6px;border-radius:6px}
    .voyect-settings-wrap .muted{opacity:.75}
    .voyect-settings-wrap .inline-help{margin:6px 0 0}

    /* ⛑️ Fijar .row-actions SOLO dentro de nuestra vista para que nunca se oculte */
    .voyect-settings-wrap .row-actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center;
      position:static !important;   /* anula left:-999em de WP en otros contextos */
      left:auto !important;
      transform:none !important;
      visibility:visible !important;/* evita reglas de visibilidad por hover de list-tables */
      max-width:720px;
    }

    .voyect-settings-wrap .tight th{width:260px}
    .voyect-settings-wrap .url-line{margin:6px 0 0}

    /* Mantener visible el input cuando está bloqueado */
    #row-slug input[disabled]{
      background:#f6f7f7;
      color:#1d2327;
      opacity:.6;
      cursor:not-allowed;
    }

    /* Acciones columna derecha */
    .voyect-side-actions .button{width:100%;display:block;text-align:center;margin-top:10px}
    .voyect-side-actions .is-disabled{opacity:.5;pointer-events:none}
  </style>

  <h1 style="display:flex;align-items:center;gap:8px;margin:0 0 12px;">
    <span class="dashicons dashicons-portfolio"></span>
    <?php echo esc_html__('Configuraciones de Voyect', 'voyect'); ?>
  </h1>

  <p class="muted" style="max-width:1000px;">
    <?php echo esc_html__('Define el slug base usado en URLs del portafolio y asigna (o crea) una “página canónica” que renderiza el grid mediante shortcode, al estilo WooCommerce (página de tienda).', 'voyect'); ?>
  </p>

  <?php if ( $has_conflict ): ?>
    <div class="notice notice-warning">
      <p><strong><?php echo esc_html__('Posible conflicto de rutas:', 'voyect'); ?></strong>
        <?php
          printf(
            esc_html__('Existe una página con el slug “%1$s” (%2$s). Si no la seleccionas como canónica, tu tema podría tomar el control del diseño de esa ruta.', 'voyect'),
            esc_html($current_slug ?: 'proyectos'),
            '<em>'.esc_html( get_the_title($maybe_conflict_page) ).'</em>'
          );
        ?>
      </p>
    </div>
  <?php endif; ?>

  <hr class="wp-header-end">

  <div class="voyect-grid">
    <!-- Panel principal -->
    <div class="voyect-card">
      <h2 style="margin-top:0;"><?php echo esc_html__('URLs y Página canónica', 'voyect'); ?></h2>

      <form method="post" action="<?php echo esc_url( $save_url ); ?>" id="voyect-settings-form">
        <?php wp_nonce_field('voyect_settings_action','voyect_nonce'); ?>

        <table class="form-table tight" role="presentation" style="width:100%;">
          <tbody>
            <tr id="row-slug">
              <th scope="row">
                <label for="voyect_cpt_slug"><?php echo esc_html__('Slug base del portafolio (CPT)', 'voyect'); ?></label>
              </th>
              <td>
                <input
                  type="text"
                  id="voyect_cpt_slug"
                  name="voyect_cpt_slug"
                  style="width:100%;max-width:520px;<?php echo $canonical_on ? 'opacity:.6;' : ''; ?>"
                  value="<?php echo esc_attr( $current_slug ?: 'proyectos' ); ?>"
                  placeholder="proyectos"
                  <?php echo $canonical_on ? 'disabled="disabled" aria-disabled="true" data-locked="1"' : ''; ?>
                >
                <p class="description inline-help" id="voyect-slug-help" style="<?php echo $canonical_on ? '' : 'display:none;'; ?>">
                  <?php
                  if ( $canonical_on ) {
                    echo esc_html__('El slug del CPT se sincroniza con el slug de la página canónica seleccionada. Para cambiarlo, edita el slug de esa página.', 'voyect');
                  }
                  ?>
                </p>
                <p class="description inline-help">
                  <?php echo esc_html__('Ejemplo de URL de un proyecto:', 'voyect'); ?>
                  <code id="voyect-slug-preview">
                    <?php
                      if ($canonical_on && $canonical_url) {
                        echo esc_html( trailingslashit( $canonical_url ) . '{mi-proyecto}/' );
                      } else {
                        echo esc_html( trailingslashit($home . ($current_slug ?: 'proyectos')) . 'mi-proyecto/' );
                      }
                    ?>
                  </code>
                </p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="voyect_canonical_page_id"><?php echo esc_html__('Página canónica del portafolio', 'voyect'); ?></label>
              </th>
              <td>
                <div class="row-actions">
                  <select id="voyect_canonical_page_id" name="voyect_canonical_page_id" style="flex:1 1 420px;min-width:280px;">
                    <option value="0"><?php echo esc_html__('— Sin página (usa archivo del CPT) —', 'voyect'); ?></option>
                    <?php foreach ($pages as $pg): ?>
                      <option value="<?php echo (int)$pg->ID; ?>" <?php selected( (int)$canonical_id, (int)$pg->ID ); ?>>
                        <?php
                          $status = $pg->post_status === 'publish' ? '' : ' · ' . strtoupper($pg->post_status);
                          echo esc_html( $pg->post_title . ' (ID: '. $pg->ID . $status . ')' );
                        ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <!-- Quitar: envía el formulario con voyect_action=clear_canonical -->
                  <button type="submit" class="button" name="voyect_action" value="clear_canonical" title="<?php echo esc_attr__('Quitar página canónica','voyect'); ?>">
                    <?php echo esc_html__('Quitar', 'voyect'); ?>
                  </button>
                </div>

                <p class="description inline-help">
                  <?php echo esc_html__('Esta página debe contener el shortcode [voyect ...]. Si la dejas vacía, se usará el archivo del CPT para listar proyectos.', 'voyect'); ?>
                </p>

                <!-- URL canónica actual / preview -->
                <p class="url-line">
                  <strong><?php echo esc_html__('URL canónica:', 'voyect'); ?></strong>
                  <a id="voyect-canon-url" href="<?php echo esc_url($canonical_on && $canonical_url ? $canonical_url : ($home . ($current_slug ?: 'proyectos') . '/')); ?>" target="_blank" rel="noopener">
                    <?php echo esc_html($canonical_on && $canonical_url ? $canonical_url : ($home . ($current_slug ?: 'proyectos') . '/')); ?>
                  </a>
                </p>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="submit" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
          <button type="submit" name="voyect_settings_submit" class="button button-primary button-hero">
            <?php echo esc_html__('Guardar cambios', 'voyect'); ?>
          </button>

          <button type="submit" class="button button-hero" name="voyect_action" value="create_canonical">
            <?php echo esc_html__('Crear página canónica con shortcode', 'voyect'); ?>
          </button>

          <span id="voyect-settings-hint" class="muted"></span>
        </p>
      </form>

      <hr>

      <h3 style="margin:18px 0 8px;"><?php echo esc_html__('Compartir filtros por URL', 'voyect'); ?></h3>
      <p class="muted" style="margin:6px 0;">
        <?php echo esc_html__('El shortcode admite filtros persistentes en la URL (QueryString). Puedes usar:', 'voyect'); ?>
      </p>
      <ul style="margin:0 0 0 18px;line-height:1.6;">
        <li><code>?cat=branding</code> (compatibilidad existente)</li>
        <li><code>?c=branding</code> (alias corto recomendado)</li>
      </ul>
      <p class="description inline-help">
        <?php echo esc_html__('Ejemplo: al seleccionar “branding” en el portafolio, la URL se actualizará a ?c=branding y al compartirla el visitante verá el filtro aplicado automáticamente.', 'voyect'); ?>
      </p>
    </div>

    <!-- Panel lateral de ayuda -->
    <div class="voyect-card">
      <h2 style="margin-top:0;"><?php echo esc_html__('Ayuda rápida', 'voyect'); ?></h2>
      <ol style="margin-left:18px;line-height:1.6;">
        <li><?php echo esc_html__('Elige un “slug” y pulsa “Guardar cambios”.', 'voyect'); ?></li>
        <li><?php echo esc_html__('Asigna una página existente como canónica o crea una nueva con el botón dedicado.', 'voyect'); ?></li>
        <li><?php echo esc_html__('Se hace flush automático; si sigues viendo 404, pulsa también el botón de “Flush”.', 'voyect'); ?></li>
        <li><?php echo esc_html__('Si usas caché (plugin/servidor), purga la caché tras los cambios.', 'voyect'); ?></li>
      </ol>

      <div style="margin-top:16px;padding:12px 14px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;">
        <strong><?php echo esc_html__('Notas sobre conflicto de rutas', 'voyect'); ?>:</strong>
        <p class="muted" style="margin:8px 0 0;">
          <?php echo esc_html__('Si existe una Página con el mismo slug del CPT (p.ej. “proyectos”), el tema podría usar su plantilla de página. Para unificar el layout, asigna esa página como canónica (con shortcode) o cambia el slug del CPT.', 'voyect'); ?>
        </p>
      </div>

      <!-- Acciones columna derecha -->
      <div class="voyect-side-actions" style="margin-top:14px;">
        <?php
          $view_disabled = ! ($canonical_on && $canonical_url);
          $edit_disabled = ! $canonical_on;
          $view_href     = $canonical_on && $canonical_url ? $canonical_url : '#';
          $edit_href     = $canonical_on ? admin_url('post.php?post='.$canonical_id.'&action=edit') : '#';
        ?>
        <a id="voyect-btn-view" class="button button-secondary <?php echo $view_disabled ? 'is-disabled' : ''; ?>" href="<?php echo esc_url($view_href); ?>" target="_blank" rel="noopener">
          <?php echo esc_html__('Ver página canónica','voyect'); ?>
        </a>
        <a id="voyect-btn-edit" class="button <?php echo $edit_disabled ? 'is-disabled' : ''; ?>" href="<?php echo esc_url($edit_href); ?>" target="_blank" rel="noopener">
          <?php echo esc_html__('Editar página canónica','voyect'); ?>
        </a>
        <a class="button" href="<?php echo esc_url( $flush_url ); ?>">
          <?php echo esc_html__('Regenerar enlaces permanentes (Flush)', 'voyect'); ?>
        </a>
      </div>

      <p class="muted" style="margin-top:12px;">
        <?php echo esc_html__('Debug: revisa el log para entradas con el prefijo [Voyect].', 'voyect'); ?>
      </p>
    </div>
  </div>
</div>

<script>
(function(){
  try{
    const slugInput   = document.getElementById('voyect_cpt_slug');
    const slugPrev    = document.getElementById('voyect-slug-preview');
    const hint        = document.getElementById('voyect-settings-hint');
    const help        = document.getElementById('voyect-slug-help');
    const selCanon    = document.getElementById('voyect_canonical_page_id');
    const canonUrlA   = document.getElementById('voyect-canon-url');

    const btnView     = document.getElementById('voyect-btn-view');
    const btnEdit     = document.getElementById('voyect-btn-edit');

    // Mapa de ID->permalink inyectado desde PHP
    const PAGE_LINKS = <?php echo wp_json_encode($page_links); ?>;

    function zeroTrim(v){ return (v||'').replace(/^\/+|\/+$/g,''); }

    function setPreview(textBase, label){
      if (!textBase) return;
      const base = textBase.replace(/\/?$/, '/');
      if (slugPrev) slugPrev.textContent = base + '{mi-proyecto}/';
      if (hint) hint.textContent = '[' + label + '] ' + base + '{slug}/';
      if (canonUrlA){
        canonUrlA.textContent = base;
        canonUrlA.href        = base;
      }
      console.debug('[Voyect][Settings] preview base=%s (%s)', base, label);
    }

    function previewFromSlug(slugBase){
      const s = zeroTrim(slugBase || 'proyectos') || 'proyectos';
      const url = (window.location.origin || '') + '/' + s + '/';
      setPreview(url, 'Preview CPT base');
    }

    function previewFromCanonicalId(pid){
      const url = PAGE_LINKS && PAGE_LINKS[pid] ? PAGE_LINKS[pid] : '';
      if (url) setPreview(url, 'Preview (canónica)');
    }

    function lockSlug(){
      if (!slugInput) return;
      slugInput.setAttribute('disabled','disabled');
      slugInput.setAttribute('aria-disabled','true');
      slugInput.style.opacity = '.6';
      if (help) help.style.display = '';
    }
    function unlockSlug(){
      if (!slugInput) return;
      slugInput.removeAttribute('disabled');
      slugInput.removeAttribute('aria-disabled');
      slugInput.style.opacity = '';
      if (help) help.style.display = 'none';
    }

    function toggleSideButtons(pid){
      const hasCanonical = pid > 0;
      const url = hasCanonical && PAGE_LINKS[pid] ? PAGE_LINKS[pid] : '';
      // Ver
      if (btnView){
        if (hasCanonical && url){
          btnView.classList.remove('is-disabled');
          btnView.href = url;
        }else{
          btnView.classList.add('is-disabled');
          btnView.href = '#';
        }
      }
      // Editar
      if (btnEdit){
        if (hasCanonical){
          btnEdit.classList.remove('is-disabled');
          btnEdit.href = (window.ajaxurl || '<?php echo esc_js( admin_url() ); ?>') + 'post.php?post=' + pid + '&action=edit';
        }else{
          btnEdit.classList.add('is-disabled');
          btnEdit.href = '#';
        }
      }
    }

    function toggleSlugLockAndPreview(){
      const pid = parseInt(selCanon && selCanon.value || '0', 10) || 0;
      const hasCanonical = pid > 0;
      console.debug('[Voyect][Settings] change canonical pid=', pid);
      toggleSideButtons(pid);
      if (hasCanonical){
        lockSlug();
        previewFromCanonicalId(pid);
      }else{
        unlockSlug();
        previewFromSlug(slugInput ? slugInput.value : 'proyectos');
      }
    }

    // Eventos
    selCanon && selCanon.addEventListener('change', toggleSlugLockAndPreview);

    if (slugInput) {
      slugInput.addEventListener('input', function(){
        const pid = parseInt(selCanon && selCanon.value || '0', 10) || 0;
        if (pid > 0) return; // si hay canónica, el preview viene de ella
        previewFromSlug(this.value);
      });
    }

    // Previews iniciales
    <?php if ($canonical_on && $canonical_url): ?>
      setPreview('<?php echo esc_js( trailingslashit($canonical_url) ); ?>', 'Preview (canónica)');
    <?php else: ?>
      previewFromSlug(slugInput ? slugInput.value : 'proyectos');
    <?php endif; ?>

    // Estado inicial
    toggleSlugLockAndPreview();
  }catch(e){ console.warn('[Voyect][Settings] inline script error', e); }
})();
</script>
