(function($){
  /* ==========================
   * Helpers básicos
   * ==========================*/
  function openModal(sel){ $(sel).attr('aria-hidden','false'); }
  $(document).on('click','[data-close]', function(){
    $(this).closest('.voyect-modal').attr('aria-hidden','true');
  });

  // API guard
  function api(){
    if(!window.VoyectAPI){
      console.error('[Voyect] VoyectAPI no está disponible.');
      return null;
    }
    return window.VoyectAPI;
  }

  // Pequeño debounce
  function debounce(fn, wait){
    let t; return function(...a){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,a), wait); };
  }

  // Escape básico (fix &#39;)
  function escapeHtml(str){
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }

  // Micro-template {{key}}
  function renderTpl(tpl, data){
    return tpl.replace(/{{\s*([\w.]+)\s*}}/g, (_m, k) => (k in data ? data[k] : ''));
  }

  // Slugify (usado en crear y quick-edit)
  function slugify(str){
    return String(str)
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g,'-')
      .replace(/^-+|-+$/g,'')
      .replace(/-{2,}/g,'-');
  }

  // ------ Formato de fechas para el listado (fallback por si el backend no lo envía formateado) ------
  function pad2(n){ n = parseInt(n,10); return (n<10?'0':'') + (isNaN(n)?'00':n); }
  function toLocalFmt(s){
    if(!s) return '—';
    // Acepta "YYYY-MM-DD HH:mm:ss" o cualquier ISO/parseable por Date
    let d;
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(s)) {
      // Convertir a ISO local
      const parts = s.replace(' ', 'T') + (s.length === 16 ? ':00' : '');
      d = new Date(parts);
    } else {
      d = new Date(s);
    }
    if (isNaN(d.getTime())) return '—';
    return d.getFullYear() + '-' + pad2(d.getMonth()+1) + '-' + pad2(d.getDate()) + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
  }

  // ------ Helpers de fecha para Quick Edit ------
  function clamp(n, min, max){
    n = parseInt(n,10); if (isNaN(n)) n = min; return Math.max(min, Math.min(max, n));
  }
  /** Intenta parsear "YYYY-MM-DD HH:mm(:ss)" o cualquier fecha parseable y devuelve partes numéricas */
  function parseMysqlToParts(s){
    if(!s) return null;
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (m){
      return { y:+m[1], m:+m[2], d:+m[3], h:+m[4], i:+m[5] };
    }
    const d = new Date(s);
    if (isNaN(d.getTime())) return null;
    return { y:d.getFullYear(), m:d.getMonth()+1, d:d.getDate(), h:d.getHours(), i:d.getMinutes() };
  }

  /* ======================================================================
   * Media (imagen destacada) – requiere wp_enqueue_media()
   * ====================================================================== */
  let mediaFrame = null;

  // Abrir modal "Crear proyecto"
  $('#voyect-new').on('click', function(e){
    e.preventDefault();
    openModal('#voyect-create');
  });

  // Abrir media frame (CREAR / EDITAR según target)
  $('#pick-thumb, #edit-pick-thumb').on('click', function(e){
    e.preventDefault();

    if (typeof wp === 'undefined' || !wp.media) {
      console.error('[Voyect] wp.media no está disponible. ¿Se llamó wp_enqueue_media()?');
      return;
    }

    const target = this.id === 'pick-thumb' ? 'create' : 'edit';

    if (!mediaFrame) {
      mediaFrame = wp.media({
        title: 'Seleccionar imagen destacada',
        multiple: false,
        library: { type: 'image' },
        button: { text: 'Usar esta imagen' }
      });

      mediaFrame.on('select', function(){
        const at = mediaFrame.state().get('selection').first().toJSON();
        const url = (at.sizes && at.sizes.medium) ? at.sizes.medium.url : at.url;
        const tgt = mediaFrame._voyTarget || 'create';

        if(tgt === 'create'){
          $('#create-thumb-id').val(at.id);
          if (!$('#create-thumb-preview').length) {
            $('<div id="create-thumb-preview" class="voyect-thumb-preview" style="margin-top:8px;"></div>')
              .insertAfter('#pick-thumb');
          }
          $('#create-thumb-preview').html(
            `<div class="voyect-thumb-preview__inner" style="display:flex;align-items:center;gap:10px;">
               <img src="${url}" alt="" style="max-width:120px;height:auto;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.1);" />
               <button type="button" class="button-link" id="remove-thumb">Quitar</button>
             </div>`
          );
        }else{
          $('#edit-thumb-id').val(at.id);
          $('#edit-thumb-preview').remove();
          $(`
            <div id="edit-thumb-preview" class="voyect-thumb-preview" style="margin-top:8px;">
              <div class="voyect-thumb-preview__inner" style="display:flex;align-items:center;gap:10px;">
                <img src="${url}" alt="" style="max-width:120px;height:auto;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.1);" />
                <button type="button" class="button-link" id="remove-thumb-edit">Quitar</button>
              </div>
            </div>
          `).insertAfter('#edit-pick-thumb');
          console.log('[Voyect][QE] thumb seleccionado', { id: at.id, url });
        }
      });
    }

    mediaFrame._voyTarget = target;
    mediaFrame.open();
  });

  // Quitar imagen destacada (CREAR)
  $(document).on('click', '#remove-thumb', function(e){
    e.preventDefault();
    $('#create-thumb-id').val('');
    $('#create-thumb-preview').empty();
  });

  // Quitar imagen destacada (EDITAR)
  $(document).on('click', '#remove-thumb-edit', function(e){
    e.preventDefault();
    $('#edit-thumb-id').val('0');
    $('#edit-thumb-preview').remove();
    console.log('[Voyect][QE] thumb marcado para quitar');
  });

  /* ======================================================================
   * SLUG en tiempo real + unicidad (modal CREAR)
   * ====================================================================== */
  const $title   = $('#create-title');
  const $slug    = $('#create-slug');
  const $btnSave = $('#create-save');

  // Ayudas inline
  if (!$('#create-slug-help').length) {
    $('<div id="create-slug-help" aria-live="polite" style="margin-top:6px;font-size:12px;opacity:.85;"></div>')
      .insertAfter($slug);
  }
  if (!$('#create-title-help').length) {
    $('<div id="create-title-help" aria-live="polite" style="margin-top:6px;font-size:12px;opacity:.85;"></div>')
      .insertAfter($title);
  }
  const $slugHelp  = $('#create-slug-help');
  const $titleHelp = $('#create-title-help');

  let autoSlug   = true;
  let lastAuto   = '';
  let isChecking = false;
  let slugValid  = false;

  function updateCreateBtn(){
    const hasTitle = $title.val().trim().length > 0;
    $btnSave.prop('disabled', !hasTitle || isChecking || !slugValid);
  }

  function setSlugUI({ok, msg, suggested}){
    slugValid = !!ok;
    $slug.toggleClass('is-invalid', !ok);
    $slugHelp.css('color', ok ? '#2271b1' : '#d63638').text(msg || (ok ? '' : ''));
    if (autoSlug && suggested && suggested !== $slug.val()) {
      $slug.val(suggested);
      lastAuto = suggested;
    }
    updateCreateBtn();
  }

  const checkUnique = debounce(async function(){
    if(!api()) return;
    const title = $title.val().trim();
    const val   = $slug.val().trim();

    if (!title){
      $title.addClass('is-invalid');
      $titleHelp.css('color','#d63638').text('El título es obligatorio.');
      setSlugUI({ ok:false, msg:'—' });
      return;
    }else{
      $title.removeClass('is-invalid');
      $titleHelp.text('');
    }

    try{
      isChecking = true; updateCreateBtn();
      const base = val || slugify(title);
      console.log('[Voyect][CREATE] validando slug', {val, base});
      const res  = await api().checkSlugUnique({ slug: base, title, exclude: 0 });
      if (res?.success){
        const { unique, suggested } = res.data;
        setSlugUI({
          ok: unique,
          msg: unique ? 'Slug disponible.' : `No disponible. Sugerencia: ${suggested}`,
          suggested: autoSlug ? suggested : null
        });
      }else{
        setSlugUI({ok:false, msg:'No se pudo validar el slug.'});
      }
    }catch(err){
      console.error('[Voyect][CREATE] Error validando slug', err);
      setSlugUI({ok:false, msg:'Error validando el slug.'});
    }finally{
      isChecking = false; updateCreateBtn();
    }
  }, 280);

  $title.on('input', function(){
    if (!autoSlug) { updateCreateBtn(); return; }
    const base = slugify($(this).val());
    $slug.val(base);
    lastAuto = base;
    checkUnique();
  });

  $slug.on('input', function(){
    const val = $(this).val().trim();
    if (val !== lastAuto && $(this).is(':focus')) autoSlug = false;
    if (val === ''){
      autoSlug = true;
      const base = slugify($title.val());
      $(this).val(base);
      lastAuto = base;
    }
    checkUnique();
  }).on('focus', function(){ autoSlug = false; });

  $title.on('blur', checkUnique);

  $('#create-save').on('click', async function(e){
    e.preventDefault();
    if(!api()) return;

    const title  = $title.val().trim();
    const slug   = $slug.val().trim();
    const thumb  = parseInt($('#create-thumb-id').val() || '0', 10) || 0;
    const cats   = [];

    if (!title){
      $title.addClass('is-invalid'); $titleHelp.css('color','#d63638').text('El título es obligatorio.');
      return;
    }
    if (!slugValid){
      setSlugUI({ ok:false, msg:'El slug no es válido o está en uso.' });
      return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true).text('Guardando…');

    try{
      console.log('[Voyect][CREATE] creando proyecto', {title, slug, thumb, cats});
      const res = await api().createProject({ title, slug, cats, thumb });
      if (res?.success){
        $title.val('').removeClass('is-invalid'); $titleHelp.text('');
        $slug.val(''); $('#create-thumb-id').val(''); $('#create-thumb-preview').empty();
        autoSlug = true; lastAuto = ''; slugValid = false; updateCreateBtn();
        $('[data-close]').first().trigger('click');
        if (typeof loadPage === 'function'){ try{ await loadPage(1); }catch{} }
        else { location.reload(); }
      }else{
        $slugHelp.css('color','#d63638').text(res?.message || 'No se pudo crear el proyecto.');
      }
    }catch(err){
      const msg = err?.response?.data?.message || err.message || 'Error creando el proyecto.';
      $slugHelp.css('color','#d63638').text(msg);
    }finally{
      $btn.prop('disabled', false).text('Crear proyecto');
    }
  });

  /* ======================================================================
   * DASHBOARD LIST – búsqueda, filtros, paginación, render
   * ====================================================================== */
  const $list   = $('#voyect-list');
  const $search = $('#voyect-search');
  const $cats   = $('#voyect-cats');
  const $prev   = $('#voyect-prev');
  const $next   = $('#voyect-next');
  const $pageL  = $('#voyect-page');

  const rawTpl  = document.getElementById('voyect-item-tpl') ? document.getElementById('voyect-item-tpl').innerHTML : '';
  let itemTpl = rawTpl;
  if (rawTpl.includes('voyect-status--{{') || rawTpl.includes('{{status}}')) {
    itemTpl = rawTpl
      .replace(/voyect-status--\s*{{\s*status\s*}}/g, 'voyect-status--{{statusKey}}')
      .replace(/>\s*{{\s*status\s*}}\s*<\/span>/g, '>{{statusLabel}}</span>');
  }

  const itemMap = new Map(); // id -> item

  // ---------- ORDEN DEL DASHBOARD (solo admin; no afecta frontend) ----------
  const SORT_STORE_KEY = 'voyectAdminSort';
  const ALLOWED_ORDERBY = new Set(['date','modified','menu_order','title']); // date = creación, modified = última modificación

  function readStoredSort(){
    try{
      const raw = localStorage.getItem(SORT_STORE_KEY);
      if(!raw) return null;
      const obj = JSON.parse(raw);
      if(!obj || !obj.orderby || !obj.order) return null;
      return obj;
    }catch(_e){ return null; }
  }
  function storeSort({orderby, order}){
    try{
      localStorage.setItem(SORT_STORE_KEY, JSON.stringify({orderby, order}));
    }catch(_e){}
  }

  // Estado del listado (por defecto: último modificado arriba)
  const state = {
    page: 1,
    per_page: 9,
    search: '',
    term: 0,
    orderby: 'modified', // "Fecha de modificación"
    order: 'desc',
    max_pages: 1
  };

  (function initSortFromStorageOrDOM(){
    const saved = readStoredSort();
    const $orderbySel  = $('#voyect-orderby, select[name="voyect-orderby"]'); // admin-dashboard.php
    const $orderdirSel = $('#voyect-orderdir, select[name="voyect-order"]');

    function sanitizeOrderby(v){
      const val = String(v || '').toLowerCase();
      return ALLOWED_ORDERBY.has(val) ? val : 'modified';
    }
    function sanitizeOrder(v){
      return (String(v||'desc').toLowerCase()==='asc') ? 'asc' : 'desc';
    }

    if(saved){
      state.orderby = sanitizeOrderby(saved.orderby);
      state.order   = sanitizeOrder(saved.order);
      console.debug('[Voyect] sort from storage', {orderby: state.orderby, order: state.order});
      if($orderbySel.length){ $orderbySel.val(state.orderby); }
      if($orderdirSel.length){ $orderdirSel.val(state.order); }
    }else{
      const uiOrderby = sanitizeOrderby($orderbySel.val());
      const uiOrder   = sanitizeOrder($orderdirSel.val());
      state.orderby = uiOrderby;
      state.order   = uiOrder;
      storeSort({orderby: state.orderby, order: state.order});
      console.debug('[Voyect] sort initial (UI/default)', {orderby: state.orderby, order: state.order});
    }

    // Nota: si el usuario elige "Orden manual", dejamos la dirección tal cual,
    // pero típicamente se usa ASC (menor menu_order arriba).
    $orderbySel.on('change', function(){
      state.orderby = sanitizeOrderby($(this).val());
      if(state.orderby === 'menu_order'){
        console.log('[Voyect] Orden manual seleccionado (drag & drop). Dirección actual:', state.order);
      }
      storeSort({orderby: state.orderby, order: state.order});
      console.debug('[Voyect] sort changed (orderby)', state);
      state.page = 1; loadPage(1);
    });

    $orderdirSel.on('change', function(){
      state.order = sanitizeOrder($(this).val());
      storeSort({orderby: state.orderby, order: state.order});
      console.debug('[Voyect] sort changed (order)', state);
      state.page = 1; loadPage(1);
    });
  })();

  let ALL_CATS = [];

  function catsToHtml(arr){
    if (!arr || !arr.length) return '';
    return arr.map(name => `<span class="chip">${escapeHtml(name)}</span>`).join(' ');
  }
  function thumbToHtml(url){
    if (!url) {
      return '<div class="no-thumb" style="width:72px;height:48px;background:#f2f4f8;border:1px solid #e6e9ef;border-radius:8px;"></div>';
    }
    return `<img src="${escapeHtml(url)}" alt="" style="width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e6e9ef;">`;
  }
  function mapStatus(k){
    const key = String(k||'draft').toLowerCase();
    const ok  = ['publish','draft','pending','private'].includes(key) ? key : 'draft';
    const label = { publish:'Publicada', draft:'En borrador', pending:'Pendiente', private:'Privado' }[ok] || ok;
    return { statusKey: ok, statusLabel: label };
  }

  function renderList(items){
    console.debug('[Voyect] renderList()', {count: items.length, sample: items[0]});
    itemMap.clear();
    $list.empty();
    if (!items.length){
      $list.append('<li style="opacity:.6;padding:12px">No hay proyectos.</li>');
      return;
    }
    const frag = document.createDocumentFragment();

    items.forEach(it => {
      itemMap.set(String(it.id), it);
      const st = mapStatus(it.status);

      // Fechas: preferimos formateadas desde el backend; si no, damos formato aquí.
      const createdFmt   = it.createdFmt   || toLocalFmt(it.created || it.postDate || it.date);
      const modifiedFmt  = it.modifiedFmt  || toLocalFmt(it.modified || it.postModified || it.modified_date);

      const html = renderTpl(itemTpl, {
        id: String(it.id),
        title: escapeHtml(it.title || '(Sin título)'),
        cats: catsToHtml(it.cats || []),
        thumb: thumbToHtml(it.thumb || ''),
        statusKey: st.statusKey,
        statusLabel: st.statusLabel,
        createdFmt,
        modifiedFmt
      });

      const li = document.createElement('div');
      li.innerHTML = html;
      frag.appendChild(li.firstElementChild);
    });

    $list[0].appendChild(frag);

    // limpiar wrapper .voyect-item__inner si lo trae el HTML del template
    $('#voyect-list .voyect-item__inner').each(function(){
      $(this).replaceWith($(this).contents());
    });
  }

  function renderPager(){
    $pageL.text(state.page);
    $prev.prop('disabled', state.page<=1);
    $next.prop('disabled', state.page>=state.max_pages);
  }

  async function loadPage(p=1){
    if (!api()) return;
    try{
      console.debug('[Voyect] loadPage() -> getProjects params', {
        page: p,
        per_page: state.per_page,
        search: state.search,
        term: state.term,
        orderby: state.orderby,
        order: state.order
      });
      const res = await api().getProjects({
        page: p,
        per_page: state.per_page,
        search: state.search,
        term: state.term,
        orderby: state.orderby,
        order: state.order
      });
      if (!res?.success){
        console.error('[Voyect] getProjects falló', res);
        $list.html('<li style="color:#d63638;padding:12px">Error cargando proyectos.</li>');
        return;
      }
      const d = res.data || {};
      state.page = d.page || p;
      state.max_pages = d.max_pages || 1;
      renderList(d.items || []);
      renderPager();
    }catch(err){
      console.error('[Voyect] Error en loadPage', err);
      $list.html('<li style="color:#d63638;padding:12px">Error cargando proyectos.</li>');
    }
  }
  window.loadPage = loadPage;

  async function loadCats(){
    if (!api()) return;
    try{
      const res = await api().getCategories();
      if (!res?.success){ console.warn('[Voyect] getCategories no success', res); return; }
      const cats = (res.data && res.data.categories) ? res.data.categories : [];
      ALL_CATS = cats;
      console.debug('[Voyect] categorías cargadas', cats);
      $cats.find('button[data-term!="all"]').remove();
      cats.forEach(c=>{
        const btn = $(`<button class="chip" data-term="${c.id}">${escapeHtml(c.name)}</button>`);
        $cats.append(btn);
      });
    }catch(err){ console.error('[Voyect] Error cargando categorías', err); }
  }

  $search.on('input', debounce(function(){
    state.search = $(this).val();
    state.page = 1;
    loadPage(1);
  }, 250));

  $cats.on('click', 'button', function(){
    $cats.find('button').removeClass('is-active');
    $(this).addClass('is-active');
    const t = $(this).data('term');
    state.term = (t === 'all') ? 0 : parseInt(t,10)||0;
    state.page = 1;
    console.debug('[Voyect] filtro por categoría', state.term);
    loadPage(1);
  });

  $prev.on('click', ()=>{ if(state.page>1) loadPage(state.page-1); });
  $next.on('click', ()=>{ if(state.page<state.max_pages) loadPage(state.page+1); });

  // Acciones por item (delegadas)
  $list.on('click', '.vo-view', function(){
    const id = $(this).closest('.voyect-item').data('id');
    const it = itemMap.get(String(id));
    console.debug('[Voyect] ver', id, it?.viewLink);
    if (it?.viewLink) window.open(it.viewLink, '_blank', 'noopener');
  });
  $list.on('click', '.vo-edit', function(){
    const id = $(this).closest('.voyect-item').data('id');
    const it = itemMap.get(String(id));
    console.debug('[Voyect] editar (WP)', id, it?.editLink);
    if (it?.editLink) window.location.href = it.editLink;
  });
  $list.on('click', '.vo-dup', async function(){
    const id = $(this).closest('.voyect-item').data('id');
    try{
      console.debug('[Voyect] duplicando', id);
      const res = await api().duplicateProject({ id });
      if (res?.success){ await loadPage(1); }
      else alert(res?.message || 'No se pudo duplicar.');
    }catch(err){ alert(err?.message || 'Error al duplicar.'); }
  });
  $list.on('click', '.vo-del', async function(){
    const id = $(this).closest('.voyect-item').data('id');
    if (!confirm('¿Eliminar este proyecto?')) return;
    try{
      console.debug('[Voyect] eliminar', id);
      const res = await api().deleteProject({ id, force:false });
      if (res?.success){ await loadPage(1); }
      else alert(res?.message || 'No se pudo eliminar.');
    }catch(err){ alert(err?.message || 'Error al eliminar.'); }
  });

  /* ======================================================================
   * QUICK EDIT – estado + slug + fecha/hora + thumbnail
   * ====================================================================== */
  const $editModal   = $('#voyect-edit');
  const $editId      = $('#edit-id');
  const $editTitle   = $('#edit-title');
  const $editSlug    = $('#edit-slug');
  const $editCatsC   = $('#edit-cats');
  const $editThumbId = $('#edit-thumb-id');
  const $editSave    = $('#edit-save');

  const $editStatus  = $('#edit-status');
  const $editDay     = $('#edit-day');
  const $editMonth   = $('#edit-month');
  const $editYear    = $('#edit-year');
  const $editHour    = $('#edit-hour');
  const $editMin     = $('#edit-min');

  if (!$('#edit-slug-help').length) {
    $('<div id="edit-slug-help" aria-live="polite" style="margin-top:6px;font-size:12px;opacity:.85;"></div>')
      .insertAfter($editSlug);
  }
  const $editSlugHelp = $('#edit-slug-help');

  let qeChecking  = false;
  let qeSlugValid = true;
  let qeAutoSlug  = false;
  let qeLastAuto  = '';
  let originalThumbId = 0;

  // Detectar si el usuario tocó fecha/hora
  let dateDirty = false;
  [$editDay,$editMonth,$editYear,$editHour,$editMin].forEach($el=>{
    $el.on('change input', ()=>{ dateDirty = true; });
  });

  // Normalización visible en los inputs (evita 5300, etc.)
  [$editHour, $editMin].forEach($el=>{
    $el.on('blur', function(){
      const isHour = this === $editHour[0];
      const v = clamp($(this).val(), isHour ? 0 : 0, isHour ? 23 : 59);
      $(this).val(String(v));
    });
  });

  function updateEditBtn(){
    const hasTitle = $editTitle.val().trim().length > 0;
    $editSave.prop('disabled', !hasTitle || qeChecking || !qeSlugValid);
  }

  function setEditSlugUI({ok, msg, suggested}){
    qeSlugValid = !!ok;
    $editSlug.toggleClass('is-invalid', !ok);
    $editSlugHelp.css('color', ok ? '#2271b1' : '#d63638').text(msg || (ok ? '' : ''));
    if (qeAutoSlug && suggested && suggested !== $editSlug.val()) {
      $editSlug.val(suggested);
      qeLastAuto = suggested;
    }
    updateEditBtn();
  }

  const validateQuickSlug = debounce(async function(){
    if(!api()) return;
    const id    = parseInt($editId.val(),10) || 0;
    const title = $editTitle.val().trim();
    let   val   = $editSlug.val().trim();

    if (!val){
      qeAutoSlug = true;
      val = slugify(title || '');
      $editSlug.val(val);
      qeLastAuto = val;
    }

    const clean = slugify(val);
    if (clean !== val){
      console.log('[Voyect][QE] normalizando slug', {from: val, to: clean});
      $editSlug.val(clean);
      val = clean;
    }
    if (!val){
      setEditSlugUI({ ok:false, msg:'Slug vacío. Escribe un slug o título.' });
      return;
    }

    try{
      qeChecking = true; updateEditBtn();
      console.log('[Voyect][QE] validando slug', {id, val, title});
      const res = await api().checkSlugUnique({ slug: val, title, exclude: id });
      if(res?.success){
        const { unique, suggested } = res.data;
        setEditSlugUI({
          ok: unique,
          msg: unique ? 'Slug disponible.' : `No disponible. Sugerencia: ${suggested}`,
          suggested: qeAutoSlug ? suggested : null
        });
      }else{
        setEditSlugUI({ ok:false, msg:'No se pudo validar el slug.' });
      }
    }catch(err){
      console.error('[Voyect][QE] Error validando slug', err);
      setEditSlugUI({ ok:false, msg:'Error validando el slug.' });
    }finally{
      qeChecking = false; updateEditBtn();
    }
  }, 260);

  function renderEditChips(selectedIds){
    const set = new Set((selectedIds || []).map(v=>parseInt(v,10)));
    $editCatsC.empty();
    if(!ALL_CATS.length){
      $editCatsC.append('<span style="font-size:12px;color:#6b7280;">(No hay categorías)</span>');
      return;
    }
    ALL_CATS.forEach(c=>{
      const isOn = set.has(parseInt(c.id,10));
      const $b = $(`<button type="button" class="chip${isOn?' is-active':''}" data-id="${c.id}">${escapeHtml(c.name)}</button>`);
      $editCatsC.append($b);
    });
  }
  $editCatsC.on('click', '.chip', function(){ $(this).toggleClass('is-active'); });

  /** Rellena los inputs de fecha/hora del modal con la fecha de creación del item */
  function setQuickEditDateFromItem(it){
    const raw = it.created || it.createdFmt || it.createdTs || it.postDate || it.date;
    const parts = parseMysqlToParts(raw);
    console.debug('[Voyect][QE] setQuickEditDateFromItem()', { raw, parts, it });
    if (!parts) return; // no tocamos si no pudimos parsear

    // Clamp y set
    const y = parts.y;
    const m = clamp(parts.m, 1, 12);
    const d = clamp(parts.d, 1, 31);
    const h = clamp(parts.h, 0, 23);
    const i = clamp(parts.i, 0, 59);

    $editYear.val(String(y));
    $editMonth.val(String(m));
    $editDay.val(String(d));
    $editHour.val(String(h));
    $editMin.val(String(i));

    // Aseguramos que "dateDirty" esté limpio al abrir
    dateDirty = false;
  }

  $list.on('click', '.vo-quick', async function(){
    const id = $(this).closest('.voyect-item').data('id');
    const it = itemMap.get(String(id));
    console.debug('[Voyect] quick-edit click', { id, it });

    if(!it){
      console.warn('[Voyect] No se encontró el item en cache. Recargando página actual.');
      await loadPage(state.page);
      return;
    }

    // Poblar campos básicos
    $editId.val(id);
    $editTitle.val(it.title || '');
    $editSlug.val((it.slug || '').toString());
    $editStatus.val((it.status || 'draft').toLowerCase());

    // Reset "dateDirty": el usuario aún no tocó fecha/hora
    dateDirty = false;

    // FECHA: usar fecha de creación real del item
    setQuickEditDateFromItem(it);

    originalThumbId = parseInt(it.thumbId || '0', 10) || 0;
    $editThumbId.val(originalThumbId);
    console.log('[Voyect][QE] thumb original', { originalThumbId });

    if (it.thumb){
      $('#edit-thumb-preview').remove();
      $(`
        <div id="edit-thumb-preview" class="voyect-thumb-preview" style="margin-top:8px;">
          <div class="voyect-thumb-preview__inner" style="display:flex;align-items:center;gap:10px;">
            <img src="${escapeHtml(it.thumb)}" alt="" style="max-width:120px;height:auto;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,.1);" />
            <button type="button" class="button-link" id="remove-thumb-edit">Quitar</button>
          </div>
        </div>
      `).insertAfter('#edit-pick-thumb');
    }else{
      $('#edit-thumb-preview').remove();
    }

    // Categorías
    let selectedIds = it.catIds || [];
    if ((!selectedIds || !selectedIds.length) && it.cats && ALL_CATS.length){
      const nameToId = new Map(ALL_CATS.map(o=>[String(o.name).toLowerCase(), o.id]));
      selectedIds = (it.cats || []).map(n=> nameToId.get(String(n).toLowerCase())).filter(Boolean);
    }
    renderEditChips(selectedIds);

    // Estado validación
    qeAutoSlug  = !$editSlug.val();
    qeLastAuto  = $editSlug.val();
    qeSlugValid = true;
    $editSlugHelp.text('');
    updateEditBtn();

    openModal('#voyect-edit');
    validateQuickSlug();
  });

  $editTitle.on('input', function(){
    if (qeAutoSlug){
      const base = slugify($(this).val());
      $editSlug.val(base);
      qeLastAuto = base;
    }
    updateEditBtn();
    validateQuickSlug();
  });
  $editSlug.on('input', function(){
    const val = $(this).val().trim();
    if (val !== qeLastAuto && $(this).is(':focus')) { qeAutoSlug = false; }
    validateQuickSlug();
  });

  // Construir fecha/hora local a formato MySQL
  function buildDateFromInputs(){
    const pad = n => String(n).padStart(2,'0');
    const y = parseInt($editYear.val(),10);
    const m = clamp($editMonth.val(), 1, 12);
    const d = clamp($editDay.val(),   1, 31);
    const h = clamp($editHour.val(),  0, 23);
    const i = clamp($editMin.val(),   0, 59);
    if (isNaN(y)||isNaN(m)||isNaN(d)||isNaN(h)||isNaN(i)) return null;
    return `${y}-${pad(m)}-${pad(d)} ${pad(h)}:${pad(i)}:00`;
  }

  // Guardar quick edit (sin alerts – UI no intrusiva)
  $('#edit-save').on('click', async function(e){
    e.preventDefault();
    if(!api()) return;

    const id     = parseInt($editId.val(),10);
    const title  = $editTitle.val().trim();
    let   slug   = $editSlug.val().trim();
    const status = String($editStatus.val() || '').toLowerCase();
    const thumb  = parseInt($editThumbId.val() || '0', 10) || 0;
    const cats   = $editCatsC.find('.chip.is-active').map(function(){ return parseInt($(this).data('id'),10); }).get();

    if(!id){ console.warn('[Voyect][QE] ID inválido'); return; }
    if(!title){ $editTitle.addClass('is-invalid'); updateEditBtn(); return; }
    $editTitle.removeClass('is-invalid');

    slug = slugify(slug);
    if (!slug){ setEditSlugUI({ ok:false, msg:'Slug vacío o inválido.' }); return; }
    if (qeChecking){ return; }
    if (!qeSlugValid){ setEditSlugUI({ ok:false, msg:'El slug no es válido o está en uso.' }); return; }

    // Construimos payload
    const payload = { id, title, slug, cats, thumb, status };
    if (dateDirty){
      const dateLocal = buildDateFromInputs();
      if (dateLocal){ payload.date = dateLocal; } // el controller deberá aceptarlo
      console.log('[Voyect][QE] dateDirty -> enviando fecha', payload.date);
    }

    const $btn = $(this);
    $btn.prop('disabled', true).text('Actualizando…');

    try{
      const svc = api();
      const fn  = svc.updateProject || svc.quickUpdateProject || svc.saveProject;
      if(!fn){ throw new Error('No existe método de actualización en VoyectAPI.'); }

      console.debug('[Voyect][QE] payload updateProject', payload);
      const res = await fn.call(svc, payload);

      let ok = !!res?.success;
      if(!ok){ $editSlugHelp.css('color','#d63638').text(res?.message || 'No se pudo actualizar.'); }

      if (ok && thumb !== originalThumbId){
        const fb = await (async function tryUpdateThumbAfter(id, newThumbId){
          const svc = api();
          const methods = ['updateThumbnail','setThumbnail','updateProjectThumb','setFeatured','setFeaturedMedia']
            .filter(m => typeof svc[m] === 'function');
          if (!methods.length){ return { tried:false }; }
          for (const m of methods){
            try{
              const r = await svc[m]({ id, thumb: newThumbId, thumbnail_id: newThumbId, featured_media: newThumbId });
              if (r?.success){ return { tried:true, ok:true }; }
            }catch(_e){} 
          }
          return { tried:true, ok:false };
        })(id, thumb);
        if (fb.tried && !fb.ok){ console.warn('[Voyect][QE] Fallback de thumbnail no logró aplicar el cambio'); }
      }

      if (ok){
        console.info('[Voyect] actualizado con éxito');
        $editModal.attr('aria-hidden','true');
        await loadPage(state.page);
      }
    }catch(err){
      console.error('[Voyect] Error al actualizar', err);
      $editSlugHelp.css('color','#d63638').text(err?.message || 'Error al actualizar.');
    }finally{
      $btn.prop('disabled', false).text('Guardar cambios');
    }
  });

  /* ======================================================================
   * SAFE INIT
   * ====================================================================== */
  $(function(){
    updateCreateBtn();
    // (el botón de quick edit se habilita al abrir el modal)
    window.loadInitial = async function(){
      console.debug('[Voyect] loadInitial() – init dashboard');
      await loadCats();
      await loadPage(1);
    };
    if (typeof loadInitial === 'function') {
      try { loadInitial(); } catch(err){ console.error('[Voyect] Error en loadInitial():', err); }
    } else {
      console.info('[Voyect] admin listo (modales / media / slug).');
    }
  });
})(jQuery);
