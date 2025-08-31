/**
 * portfolio-archive.js
 * Maneja el grid AJAX en el frontend.
 * - Usa VoyectVars.ajaxurl (localize_script).
 * - Renderiza cards con la UI NUEVA (clases: portafolio__*).
 * - Filtros usan clase visual "active" (para la UI dark).
 * - Acepta ?c={slug} (preferido) y ?cat={slug} (compat).
 */
(function($){
  $(document).ready(function(){

    const $root = $('#voyect-archive');
    if(!$root.length) return;

    const DEBUG = true;
    if (DEBUG) console.log('[Voyect] portfolio-archive.js inicializado.');

    // ✅ ajaxurl desde PHP (wp_localize_script)
    const AJAX_URL = (window.VoyectVars && VoyectVars.ajaxurl)
                  || window.ajaxurl
                  || (window.wp && wp.ajax && wp.ajax.settings && wp.ajax.settings.url)
                  || '/wp-admin/admin-ajax.php';

    if (typeof axios === 'undefined') {
      console.error('[Voyect] axios no está disponible. Verifica que se haya encolado.');
      return;
    }

    // Props desde shortcode / vista
    const props = $root.data('props') || {};
    const globalProps = window.VoyectFrontendProps || {};
    const $grid  = $root.find('.voyect-grid');
    const $pager = $root.find('.voyect-pager, .portafolio__paginador'); // compat
    const $pageLabel = $('#voyect-page-frontend');
    const $filtersBar = $('#voyect-filters-frontend');

    // Aseguramos clase de grid para que tome estilos nuevos
    $grid.addClass('portafolio__proyectos');

    // base para construir enlaces bonitos si hace falta
    const CPT_BASE =
      (typeof props.cpt_base === 'string' && props.cpt_base) ||
      (window.VoyectVars && VoyectVars.cpt_base) ||
      'voyect';

    // home base para construir enlaces absolutos
    const HOME =
      (window.VoyectVars && VoyectVars.home_url) ||
      (window.location.origin || '');

    // Catálogo de categorías (id, name, slug) e índice por nombre/slug
    let CATS_ARR = [];
    const CATEGORY_INDEX_BY_NAME = new Map(); // name.toLowerCase() -> id
    const CATEGORY_INDEX_BY_SLUG = new Map(); // slug -> id

    let state = {
      page: 1,
      per_page: parseInt(props.per_page) || 9,
      search: '',
      term: 0,
      orderby: (props.orderby || 'menu_order'),
      order: (props.order || 'asc'),
      max_pages: 1,
    };

    /** Utilitario escape */
    function escapeHtml(str){
      return String(str).replace(/[&<>"']/g, s => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[s]));
    }

    /**
     * Construye permalink "bonito" garantizando /{cpt_base}/{slug}/
     */
    function buildPrettyLink(item){
      const raw  = item.viewLink || '';
      const slug = (item.slug || '').toString().replace(/^\/|\/$/g, '');
      const base = String(CPT_BASE || '').replace(/^\/|\/$/g, '');
      const home = String(HOME || window.location.origin || '').replace(/\/+$/,'');

      if (!slug) {
        if (DEBUG) console.warn('[Voyect] item sin slug, no se puede construir permalink:', item);
        return raw || '#';
      }

      const looksPretty = raw && !/\?post_type=/.test(raw) && !/[?&]p=\d+/.test(raw);
      if (looksPretty) {
        if (DEBUG) console.debug('[Voyect] Enlace ya pretty, se respeta:', raw);
        return raw;
      }

      const pretty = `${home}/${base}/${encodeURIComponent(slug)}/`;
      if (DEBUG) console.info('[Voyect] Fallback permalink construido', { pretty, rawIn: raw });
      return pretty;
    }

    /** Renderiza los chips de categorías en la barra superior (UI nueva) */
    function renderFilters(){
      if(!$filtersBar.length) return;

      const frag = document.createDocumentFragment();

      // Botón "Todo"
      const allBtn = document.createElement('button');
      allBtn.type = 'button';
      allBtn.className = 'voyect-filter-chip' + (state.term === 0 ? ' active' : '');
      allBtn.setAttribute('data-term', '0');
      allBtn.textContent = 'Todo';
      frag.appendChild(allBtn);

      CATS_ARR.forEach(c=>{
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'voyect-filter-chip' + (state.term === c.id ? ' active' : '');
        btn.setAttribute('data-term', String(c.id));
        btn.setAttribute('data-slug', c.slug || '');
        btn.textContent = c.name;
        frag.appendChild(btn);
      });

      $filtersBar.empty()[0].appendChild(frag);
    }

    /** Activa visualmente un chip por id en la barra de filtros */
    function activateFilterChip(termId){
      if(!$filtersBar.length) return;
      $filtersBar.find('.voyect-filter-chip').removeClass('active');
      const sel = termId ? `.voyect-filter-chip[data-term="${termId}"]` : `.voyect-filter-chip[data-term="0"]`;
      $filtersBar.find(sel).addClass('active');
    }

    /** Render items con la UI nueva */
    function renderItems(items){
      $grid.empty();
      if(!items.length){
        $grid.append('<div class="no-results"><p>No hay proyectos.</p></div>');
        return;
      }

      const frag = document.createDocumentFragment();

      items.forEach(it=>{
        const link = buildPrettyLink(it);

        // chips con data-term para permitir click y filtrar
        const catsHtml = (it.cats||[]).map(name=>{
          const id = CATEGORY_INDEX_BY_NAME.get(String(name).toLowerCase()) || 0;
          // Chip UI nueva
          return `<span class="portafolio__categoria" data-term="${id}" role="button" tabindex="0" aria-label="Filtrar por ${escapeHtml(name)}">${escapeHtml(name)}</span>`;
        }).join(' ');

        const hasImg = !!it.thumb;
        const safeTitle = escapeHtml(it.title || '(Sin título)');

        const wrapper = document.createElement('div');
        wrapper.className = 'portafolio__proyecto';
        wrapper.setAttribute('data-id', String(it.id));

        // Estructura UI nueva: <a> envolviendo imagen + título
        wrapper.innerHTML = `
          <a href="${link}" rel="noopener">
            <div class="portafolio__img">
              ${ hasImg ? `<img src="${it.thumb}" alt="${safeTitle}">` : '' }
            </div>
            <div class="portafolio__titulo">
              <h3>${safeTitle}</h3>
            </div>
          </a>
          <div class="portafolio__categorias">${catsHtml}</div>
        `;
        frag.appendChild(wrapper);
      });

      $grid[0].appendChild(frag);
      if (DEBUG) console.log('[Voyect] renderItems →', items.length, 'items');
    }

    /** Render pager */
    function renderPager(){
      if(!$pager.length) return;
      $pageLabel.text(state.page);
      $('#voyect-prev-frontend').prop('disabled', state.page<=1);
      $('#voyect-next-frontend').prop('disabled', state.page>=state.max_pages);
    }

    /** Cargar página vía AJAX */
    async function loadPage(p=1){
      try{
        const params = {
          action: 'voyect_get_projects',
          page: p,
          per_page: state.per_page,
          search: state.search,
          term: state.term,
          orderby: state.orderby,
          order: state.order,
          status: 'publish',
          only_publish: 1,
          _wpnonce: (window.VoyectVars && VoyectVars.nonce) || ''
        };
        if (DEBUG) console.debug('[Voyect][FE] GET params →', params);

        const res = await axios.get(AJAX_URL, { params });
        const d = res.data && res.data.data ? res.data.data : {};
        state.page = d.page || p;
        state.max_pages = d.max_pages || 1;
        if (DEBUG) console.log('[Voyect] Datos recibidos:', d);
        renderItems(d.items || []);
        renderPager();
      }catch(err){
        console.error('[Voyect] Error cargando proyectos', err);
        $grid.html('<p style="color:red">Error cargando proyectos.</p>');
      }
    }

    /** Obtiene el slug inicial del filtro desde URL o props */
    function getInitialFilterSlug(){
      try{
        const url = new URL(window.location.href);
        let slug = url.searchParams.get('c');
        if (slug) return String(slug);
        slug = url.searchParams.get('cat');
        if (slug) return String(slug);
        if (globalProps && typeof globalProps.initial_filter === 'string' && globalProps.initial_filter){
          return String(globalProps.initial_filter);
        }
        const dataAttr = $root.attr('data-initial-filter');
        if (dataAttr) return String(dataAttr);
        return '';
      }catch(_e){ return ''; }
    }

    /** Normaliza la URL a ?c= */
    function normalizeURLWithSlug(slug){
      try{
        const url = new URL(window.location.href);
        if (slug) {
          url.searchParams.set('c', slug);
          url.searchParams.delete('cat');
        } else {
          url.searchParams.delete('c');
          url.searchParams.delete('cat');
        }
        window.history.replaceState({}, '', url.toString());
      }catch(_e){}
    }

    /** Cargar categorías (para filtros y mapeo nombre->id) */
    async function loadCats(){
      try{
        const res = await axios.get(AJAX_URL, { params: { action: 'voyect_get_cats' } });
        const d = res.data && res.data.data ? res.data.data : {};
        const cats = d.categories || [];
        CATS_ARR = cats.map(c => ({ id: parseInt(c.id,10), name: String(c.name), slug: String(c.slug) }));

        CATEGORY_INDEX_BY_NAME.clear();
        CATEGORY_INDEX_BY_SLUG.clear();
        CATS_ARR.forEach(c=>{
          CATEGORY_INDEX_BY_NAME.set(c.name.toLowerCase(), c.id);
          CATEGORY_INDEX_BY_SLUG.set(c.slug, c.id);
        });

        if (DEBUG) console.debug('[Voyect] Categorías cargadas:', CATS_ARR);

        applyInitialFilter();
        renderFilters();
      }catch(err){
        console.error('[Voyect] Error cargando categorías', err);
      }
    }

    /** Aplica filtro inicial (lee ?c / ?cat / props.initial_filter) */
    function applyInitialFilter(){
      const slug = getInitialFilterSlug();
      if (!slug) return;

      let termId = CATEGORY_INDEX_BY_SLUG.get(slug) || 0;

      if (!termId) {
        const lower = String(slug).toLowerCase();
        CATS_ARR.some(c => {
          if (c.name.toLowerCase() === lower) { termId = c.id; return true; }
          return false;
        });
      }

      if (termId) {
        state.term = termId;
        activateFilterChip(termId);
        normalizeURLWithSlug(slug);
        if (DEBUG) console.log('[Voyect] Filtro inicial aplicado', { slug, termId });
      } else if (DEBUG) {
        console.warn('[Voyect] Slug de filtro inicial no corresponde a ninguna categoría', slug);
      }
    }

    /** Actualiza querystring a ?c= para compartir URL */
    function updateURLWithCat(termId){
      try{
        const cat = CATS_ARR.find(c=>c.id === termId);
        const slug = cat ? cat.slug : '';
        normalizeURLWithSlug(slug);
      }catch(_e){}
    }

    // ----------------- Eventos UI -----------------

    // Paginación
    $('#voyect-prev-frontend').on('click', ()=>{ if(state.page>1) loadPage(state.page-1); });
    $('#voyect-next-frontend').on('click', ()=>{ if(state.page<state.max_pages) loadPage(state.page+1); });

    // Búsqueda
    $('#voyect-search-frontend').on('input', function(){
      state.search = $(this).val();
      state.page = 1;
      loadPage(1);
    });

    // Click en barra de filtros (botones dentro del contenedor)
    $filtersBar.on('click', '.voyect-filter-chip, button[data-term]', function(){
      const term = parseInt($(this).attr('data-term'), 10) || 0;
      state.term = term;
      state.page = 1;
      activateFilterChip(term);
      updateURLWithCat(term);
      loadPage(1);
      if (DEBUG) console.log('[Voyect] Filtro por barra de categorías', term);
    });

    // Click en chips dentro de cada card (UI nueva)
    $grid.on('click', '.portafolio__categoria', function(){
      const term = parseInt($(this).attr('data-term'), 10) || 0;
      state.term = term;
      state.page = 1;
      activateFilterChip(term);
      updateURLWithCat(term);
      loadPage(1);
      if (DEBUG) console.log('[Voyect] Filtro desde chip de card', term);
    });

    // ----------------- Init -----------------
    (async function init(){
      if (DEBUG) console.debug('[Voyect] init FE', { HOME, CPT_BASE, globalProps });
      await loadCats();   // primero categorías
      await loadPage(1);  // luego listado
    })();

  });
})(jQuery);
