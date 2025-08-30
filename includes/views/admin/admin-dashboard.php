<?php if ( ! defined('ABSPATH') ) exit; 
// ====== Helpers para debug y datasets ======
$nonce   = wp_create_nonce('voyect_admin_nonce');
$ajaxurl = admin_url('admin-ajax.php'); // fallback para JS (en admin existe ajaxurl, igual lo exponemos)
?>
<div class="voyect-admin wrap" data-ajaxurl="<?php echo esc_url( $ajaxurl ); ?>">
  <h1 class="voyect-title">
    Voyect <span class="voyect-sub"><?php esc_html_e('Gestión de portafolio web','voyect'); ?></span>
  </h1>

  <div class="voyect-grid">
    <!-- Columna Izquierda: Listado -->
    <section class="voyect-col voyect-col--list">
      <header class="voyect-list__header">
        <div class="voyect-search">
          <input type="search" id="voyect-search" placeholder="<?php esc_attr_e('Buscar proyecto por nombre', 'voyect'); ?>">
        </div>

        <!-- Chips de categorías -->
        <div class="voyect-chips" id="voyect-cats">
          <button class="chip is-active" data-term="all"><?php _e('Todas', 'voyect'); ?></button>
          <!-- categorías se inyectan vía JS -->
        </div>

        <!-- Controles de orden -->
        <div class="voyect-order" style="display:flex;gap:8px;align-items:center;margin-left:auto;">
          <label class="voyect-field" style="margin:0;">
            <span style="font-size:12px;opacity:.8;"><?php _e('Ordenar por','voyect'); ?></span>
            <select id="voyect-orderby">
              <option value="modified" selected><?php _e('Fecha de modificación','voyect'); ?></option>
              <option value="date"><?php _e('Fecha (creación)','voyect'); ?></option>
              <option value="title"><?php _e('Nombre','voyect'); ?></option>
              <option value="menu_order"><?php _e('Orden manual','voyect'); ?></option>
            </select>
          </label>
          <label class="voyect-field" style="margin:0;">
            <span style="font-size:12px;opacity:.8;"><?php _e('Dirección','voyect'); ?></span>
            <select id="voyect-order">
              <option value="desc" selected><?php _e('Descendente','voyect'); ?></option>
              <option value="asc"><?php _e('Ascendente','voyect'); ?></option>
            </select>
          </label>

          <button id="voyect-new" class="button button-primary" style="margin-left:8px;">
            <span class="ri-add-line"></span> <?php _e('Nuevo Proyecto','voyect'); ?>
          </button>
        </div>
      </header>

      <!-- Listado -->
      <ul
        id="voyect-list"
        class="voyect-list"
        aria-label="<?php esc_attr_e('Listado de proyectos', 'voyect'); ?>"
        aria-live="polite"
        aria-busy="false"
        data-nonce="<?php echo esc_attr( $nonce ); ?>"
        data-per-page="10"
        data-orderby="modified"
        data-order="desc">
        <!-- items vía JS/AJAX -->
      </ul>

      <!-- Estado vacío -->
      <div id="voyect-empty" class="voyect-empty" hidden>
        <p style="opacity:.75;margin:12px 0;"><?php esc_html_e('No hay proyectos para mostrar.', 'voyect'); ?></p>
      </div>

      <!-- Loader/Skeleton (lo oculta/muestra el JS) -->
      <div id="voyect-loading" class="voyect-loading" hidden>
        <ul class="voyect-list">
          <?php for($i=0;$i<3;$i++): ?>
            <li class="voyect-item is-skeleton">
              <div class="voyect-item__inner">
                <div class="voyect-handle">≡</div>
                <div class="voyect-thumb"><span class="sk-box"></span></div>
                <div class="voyect-meta">
                  <div class="sk-line" style="width:180px"></div>
                  <div class="sk-line" style="width:120px"></div>
                </div>
                <div class="voyect-actions">
                  <span class="sk-btn"></span><span class="sk-btn"></span><span class="sk-btn"></span>
                </div>
              </div>
            </li>
          <?php endfor; ?>
        </ul>
      </div>

      <footer class="voyect-pager">
        <button class="button" id="voyect-prev" disabled>&laquo;</button>
        <span id="voyect-page">1</span>
        <button class="button" id="voyect-next" disabled>&raquo;</button>
      </footer>
    </section>

    <!-- Columna Derecha: Shortcode Builder -->
    <aside class="voyect-col voyect-col--shortcode">
      <div class="voyect-card">
        <h2><?php _e('Creación de Shortcode','voyect'); ?></h2>

        <!-- Shortcode (NO editable): <div> auto-ajustable + botón debajo -->
        <div class="voyect-shortcode__row voyect-shortcode__row--stack" style="gap:8px;align-items:flex-end;">
          <div
            id="voyect-shortcode"
            role="textbox"
            aria-label="<?php esc_attr_e('Shortcode generado','voyect'); ?>"
            aria-readonly="true"
            tabindex="0"
            style="
              width:100%;
              font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
              font-size:12px; line-height:1.5;
              background:#f6f7f7; color:#1d2327;
              border:1px solid #c3c4c7; border-radius:6px;
              padding:10px 12px; white-space:pre-wrap; word-break:break-word;
              min-height:38px; box-sizing:border-box; cursor:text;
            "></div>

          <button id="voyect-copy" class="button" type="button">
            <span class="ri-file-copy-line"></span> <?php _e('Copiar shortcode','voyect'); ?>
          </button>
        </div>

        <hr>
        <h3><?php _e('Configuración Shortcode','voyect'); ?></h3>

        <label class="voyect-field">
          <span><?php _e('Cantidad de proyectos por página','voyect'); ?></span>
          <input id="sc-per_page" type="number" min="1" value="9">
        </label>

        <label class="voyect-check"><input id="sc-search" type="checkbox" checked> <?php _e('Buscador','voyect'); ?></label>
        <label class="voyect-check"><input id="sc-filters" type="checkbox" checked> <?php _e('Filtros por categoría','voyect'); ?></label>
        <label class="voyect-check"><input id="sc-pagination" type="checkbox" checked> <?php _e('Paginador','voyect'); ?></label>
        <label class="voyect-check"><input id="sc-show_cats" type="checkbox" checked> <?php _e('Mostrar categorías como pastillas en las cards (frontend)','voyect'); ?></label>

        <div class="voyect-two">
          <label class="voyect-field">
            <span><?php _e('Tipo de orden','voyect'); ?></span>
            <select id="sc-order">
              <option value="desc" selected><?php _e('Descendente','voyect'); ?></option>
              <option value="asc"><?php _e('Ascendente','voyect'); ?></option>
            </select>
          </label>
          <label class="voyect-field">
            <span><?php _e('Orden por','voyect'); ?></span>
            <select id="sc-orderby">
              <option value="date" selected><?php _e('Fecha (creación)','voyect'); ?></option>
              <option value="modified"><?php _e('Fecha de modificación','voyect'); ?></option>
              <option value="title"><?php _e('Nombre','voyect'); ?></option>
              <option value="menu_order"><?php _e('Orden manual','voyect'); ?></option>
            </select>
          </label>
        </div>
      </div>
    </aside>
  </div>
</div>

<!-- Template del ítem -->
<script type="text/html" id="voyect-item-tpl">
  <li class="voyect-item" data-id="{{id}}">
    <div class="voyect-item__inner">
      <div class="voyect-handle" title="<?php echo esc_attr__('Arrastrar para reordenar','voyect'); ?>">≡</div>

      <div class="voyect-thumb">
        {{thumb}}
      </div>

      <div class="voyect-meta">
        <div class="voyect-title">
          {{title}}
          <!-- Badge de estado al estilo referencia -->
          <span class="voyect-status voyect-status--{{status}}">{{status}}</span>
        </div>
        <!-- Chips de categorías (admin) -->
        <div class="voyect-cats">{{cats}}</div>

        <!-- NUEVO: Fechas -->
        <div class="voyect-dates" style="margin-top:4px;font-size:12px;color:#6b7280;">
          <span><?php echo esc_html__('Creado','voyect'); ?>: <strong>{{createdFmt}}</strong></span>
          <span style="margin:0 6px;">·</span>
          <span><?php echo esc_html__('Modificado','voyect'); ?>: <strong>{{modifiedFmt}}</strong></span>
        </div>
      </div>

      <div class="voyect-actions">
        <button class="button button-small vo-view"  title="<?php echo esc_attr__('Ver','voyect'); ?>"><i class="ri-eye-line"      aria-hidden="true"></i></button>
        <button class="button button-small vo-edit"  title="<?php echo esc_attr__('Editar','voyect'); ?>"><i class="ri-pencil-line"   aria-hidden="true"></i></button>
        <button class="button button-small vo-quick" title="<?php echo esc_attr__('Edición rápida','voyect'); ?>"><i class="ri-flashlight-line" aria-hidden="true"></i></button>
        <button class="button button-small vo-dup"   title="<?php echo esc_attr__('Duplicar','voyect'); ?>"><i class="ri-file-copy-line" aria-hidden="true"></i></button>
        <button class="button button-small vo-del"   title="<?php echo esc_attr__('Eliminar','voyect'); ?>"><i class="ri-delete-bin-6-line" aria-hidden="true"></i></button>
      </div>
    </div>
  </li>
</script>

<?php
// Modales (crear / editar)
$create = VOYECT_PATH . 'includes/views/admin/modal-create.php';
$edit   = VOYECT_PATH . 'includes/views/admin/modal-edit.php';
if ( file_exists($create) ) require $create;
if ( file_exists($edit) )   require $edit;
?>

<!-- JS de la vista: builder de shortcode, copy con feedback, patch de orden -->
<script>
  (function(){
    try{
      const root     = document.querySelector('.voyect-admin');
      const listEl   = document.getElementById('voyect-list');
      const scBox    = document.getElementById('voyect-shortcode'); // <div> no editable

      // Helpers para leer/escribir el shortcode en el <div>
      function setSC(text){
        scBox.textContent = text || '';
      }
      function getSC(){ return scBox.textContent || ''; }

      // Selección rápida al enfocar/click
      scBox.addEventListener('focus', ()=> {
        const sel = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(scBox);
        sel.removeAllRanges();
        sel.addRange(range);
        console.debug('[Voyect][SC] selección automática');
      });
      scBox.addEventListener('click', ()=>scBox.focus());

      // ===== Builder de shortcode =====
      const $ = (s)=>document.querySelector(s);
      const $all = (s)=>Array.from(document.querySelectorAll(s));

      const sc = {
        per_page:   $('#sc-per_page'),
        search:     $('#sc-search'),
        filters:    $('#sc-filters'),
        pagination: $('#sc-pagination'),
        show_cats:  $('#sc-show_cats'),
        order:      $('#sc-order'),
        orderby:    $('#sc-orderby')
      };

      // Shortcode compacto (booleans solo cuando son true; per_page solo si != 9)
      function regenShortcode(){
        const parts = ['voyect'];
        if (sc.search.checked)     parts.push('search="true"');
        if (sc.filters.checked)    parts.push('filters="true"');
        if (sc.pagination.checked) parts.push('pagination="true"');
        if (sc.show_cats.checked)  parts.push('show_cats="true"');
        const per = parseInt(sc.per_page.value || '9', 10);
        if (!Number.isNaN(per) && per !== 9) parts.push(`per_page="${per}"`);
        parts.push(`orderby="${sc.orderby.value}"`);
        parts.push(`order="${sc.order.value}"`);
        const value = '[' + parts.join(' ') + ']';
        setSC(value);
        document.dispatchEvent(new Event('voyect:shortcode:updated'));
        console.debug('[Voyect][SC] regenerado', value);
      }

      $all('#sc-per_page, #sc-search, #sc-filters, #sc-pagination, #sc-order, #sc-orderby, #sc-show_cats')
        .forEach(el=> el && el.addEventListener('change', regenShortcode));
      regenShortcode();

      // ===== Copiar al portapapeles con fallback + feedback suave =====
      const copyBtn = document.getElementById('voyect-copy');

      async function doCopy(text){
        try{
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
          }
          // Fallback execCommand
          const tmp = document.createElement('textarea');
          tmp.value = text;
          tmp.setAttribute('readonly','');
          tmp.style.position = 'fixed';
          tmp.style.left = '-9999px';
          document.body.appendChild(tmp);
          tmp.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(tmp);
          return ok;
        }catch(e){
          console.warn('[Voyect][SC] Fallback copy error', e);
          return false;
        }
      }

      copyBtn && copyBtn.addEventListener('click', async ()=>{
        const ok  = await doCopy(getSC());
        if (ok){
          copyBtn.classList.add('is-success');
          copyBtn.innerHTML = '<span class="ri-check-line"></span> <?php echo esc_js(__('Copiado','voyect')); ?>';
          setTimeout(()=>{
            copyBtn.classList.remove('is-success');
            copyBtn.innerHTML = '<span class="ri-file-copy-line"></span> <?php echo esc_js(__('Copiar shortcode','voyect')); ?>';
          }, 1400);
        }else{
          console.warn('[Voyect][SC] No se pudo copiar (clipboard denegado)');
        }
      });

      // ===== Patch no intrusivo para ordenar el listado del backend =====
      const selOrderBy = document.getElementById('voyect-orderby');
      const selOrder   = document.getElementById('voyect-order');

      let currentOrderBy = selOrderBy ? selOrderBy.value : (listEl?.dataset?.orderby || 'modified');
      let currentOrder   = selOrder   ? selOrder.value   : (listEl?.dataset?.order   || 'desc');

      function patchGetProjects(){
        if (!window.VoyectAPI || typeof window.VoyectAPI.getProjects !== 'function') {
          console.warn('[Voyect][ADMIN] VoyectAPI.getProjects no disponible aún; reintento…');
          setTimeout(patchGetProjects, 300);
          return;
        }
        if (window.VoyectAPI._voyectPatchedOrder) return;

        const original = window.VoyectAPI.getProjects;
        window.VoyectAPI.getProjects = function(opts){
          const merged = Object.assign({}, opts, {
            orderby: currentOrderBy,
            order:   currentOrder
          });
          console.debug('[Voyect][ADMIN] getProjects(patch)', merged);
          return original.call(this, merged);
        };
        window.VoyectAPI._voyectPatchedOrder = true;
      }
      patchGetProjects();

      function reloadWithOrder(){
        if (typeof window.loadPage === 'function') {
          window.loadPage(1);
        } else {
          console.info('[Voyect][ADMIN] loadPage no disponible para recargar con nuevo orden');
        }
      }

      selOrderBy && selOrderBy.addEventListener('change', function(){
        currentOrderBy = this.value || 'modified';
        listEl && listEl.setAttribute('data-orderby', currentOrderBy);
        reloadWithOrder();
      });
      selOrder   && selOrder.addEventListener('change', function(){
        currentOrder = this.value || 'desc';
        listEl && listEl.setAttribute('data-order', currentOrder);
        reloadWithOrder();
      });

      // ===== Debug =====
      console.debug('[Voyect] admin-dashboard view ready', {
        ajaxurl: root?.dataset?.ajaxurl,
        nonce:   listEl?.dataset?.nonce,
        perPage: listEl?.dataset?.perPage,
        orderby: listEl?.dataset?.orderby,
        order:   listEl?.dataset?.order
      });
    }catch(e){ console.warn('[Voyect] debug view error', e); }
  })();
</script>
