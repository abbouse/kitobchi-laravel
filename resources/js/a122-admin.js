import { createIcons, icons } from 'lucide';
import ApexCharts from 'apexcharts';

if (!window.ApexCharts) {
  window.ApexCharts = ApexCharts;
}

(function () {
  const saved = localStorage.getItem('a122-theme') || localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const dark = saved ? saved === 'dark' : prefersDark;
  document.documentElement.classList.toggle('dark', dark);
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
})();

function applyA122Theme(dark) {
  document.documentElement.classList.toggle('dark', dark);
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  localStorage.setItem('a122-theme', dark ? 'dark' : 'light');
  localStorage.setItem('theme', dark ? 'dark' : 'light');
  window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark } }));
}

function setupSidebarController() {
  const shell = document.querySelector('[data-sidebar-shell]');
  const sidebar = document.querySelector('[data-sidebar]');
  const overlay = document.querySelector('[data-sidebar-overlay]');
  const toggleButtons = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
  const closeButtons = Array.from(document.querySelectorAll('[data-sidebar-close]'));
  const sidebarLinks = Array.from(document.querySelectorAll('[data-sidebar-link]'));

  if (!shell || !sidebar || !overlay || !toggleButtons.length) return;

  const state = {
    expanded: JSON.parse(localStorage.getItem('a122-sidebar-expanded') ?? 'true'),
    mobileOpen: false,
    isDesktop: window.innerWidth >= 1024,
  };

  const sync = () => {
    state.isDesktop = window.innerWidth >= 1024;

    shell.classList.toggle('sidebar-collapsed', state.isDesktop && !state.expanded);
    shell.classList.toggle('sidebar-expanded', !state.isDesktop || state.expanded);

    sidebar.classList.toggle('is-collapsed', state.isDesktop && !state.expanded);
    sidebar.classList.toggle('is-expanded', !state.isDesktop || state.expanded);
    sidebar.dataset.collapsed = state.isDesktop && !state.expanded ? 'true' : 'false';

    if (state.isDesktop) {
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      overlay.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
      state.mobileOpen = false;
    } else {
      sidebar.classList.toggle('translate-x-0', state.mobileOpen);
      sidebar.classList.toggle('-translate-x-full', !state.mobileOpen);
      overlay.classList.toggle('hidden', !state.mobileOpen);
      document.body.classList.toggle('overflow-hidden', state.mobileOpen);
    }

    toggleButtons.forEach((button) => {
      button.setAttribute('aria-expanded', String(state.isDesktop ? state.expanded : state.mobileOpen));
    });
  };

  const toggle = () => {
    if (state.isDesktop) {
      state.expanded = !state.expanded;
      localStorage.setItem('a122-sidebar-expanded', JSON.stringify(state.expanded));
    } else {
      state.mobileOpen = !state.mobileOpen;
    }
    sync();
  };

  const close = () => {
    state.mobileOpen = false;
    sync();
  };

  toggleButtons.forEach((button) => {
    button.addEventListener('click', toggle);
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', close);
  });

  overlay.addEventListener('click', close);
  sidebarLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (!state.isDesktop) close();
    });
  });

  window.addEventListener('resize', sync);
  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
  });

  sync();
}

function registerA122Alpine() {
  const Alpine = window.Alpine;
  if (!Alpine || window.__a122AlpineRegistered) return;
  window.__a122AlpineRegistered = true;

  Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
      this.dark = !this.dark;
      applyA122Theme(this.dark);
    },
  });

  Alpine.store('sidebar', {
    mobileOpen: false,
    expanded: true,
    isDesktop: true,
  });

  Alpine.store('sidebarGroups', {
    state: JSON.parse(localStorage.getItem('sidebarGroups') || '{}'),
    isOpen(key, defaultOpen = false) {
      if (key in this.state) return this.state[key];
      return defaultOpen;
    },
    toggle(key, defaultOpen = false) {
      const cur = this.isOpen(key, defaultOpen);
      this.state[key] = !cur;
      localStorage.setItem('sidebarGroups', JSON.stringify(this.state));
    },
  });

  Alpine.data('crudTable', (rows = []) => ({
    rows: rows,
    selected: [],
    search: '',
    filterOpen: false,
    importOpen: false,
    deleteOpen: false,
    deleteId: null,
    get filtered() {
      const s = this.search.trim().toLowerCase();
      if (!s) return this.rows;
      return this.rows.filter(r => Object.values(r).some(v => String(v ?? '').toLowerCase().includes(s)));
    },
    get allChecked() { return this.filtered.length > 0 && this.selected.length === this.filtered.length; },
    toggleAll() { this.selected = this.allChecked ? [] : this.filtered.map(r => r.id); },
    toggleOne(id) { const i = this.selected.indexOf(id); if (i > -1) this.selected.splice(i, 1); else this.selected.push(id); },
    askDelete(id) { this.deleteId = id; this.deleteOpen = true; },
    confirmDelete() { this.rows = this.rows.filter(r => r.id !== this.deleteId); this.selected = this.selected.filter(s => s !== this.deleteId); this.deleteOpen = false; this.deleteId = null; },
    bulkDelete() { if (!confirm(this.selected.length + ' ta yozuvni o‘chirishga ishonchingiz komilmi?')) return; this.rows = this.rows.filter(r => !this.selected.includes(r.id)); this.selected = []; },
    exportCSV() {
      const data = this.selected.length ? this.rows.filter(r => this.selected.includes(r.id)) : this.filtered;
      if (!data.length) { alert('Eksport uchun ma\'lumot yo\'q'); return; }
      const keys = Object.keys(data[0]);
      const csv = [keys.join(','), ...data.map(r => keys.map(k => JSON.stringify(r[k] ?? '')).join(','))].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'export-' + Date.now() + '.csv';
      a.click(); URL.revokeObjectURL(url);
    },
    exportJSON() {
      const data = this.selected.length ? this.rows.filter(r => this.selected.includes(r.id)) : this.filtered;
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'export-' + Date.now() + '.json';
      a.click(); URL.revokeObjectURL(url);
    },
    print() { window.print(); },
  }));
}

function downloadBlob(filename, content, type) {
  const blob = new Blob([content], { type });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

function getVisibleRows(table) {
  return Array.from(table.querySelectorAll('tbody tr')).filter((row) => row.dataset.hiddenBySearch !== 'true');
}

function exportTableAsCsv(table) {
  const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim() || 'Maydon');
  const rows = getVisibleRows(table).map((row) =>
    Array.from(row.children).map((cell) => {
      const text = (cell.innerText || cell.textContent || '').replace(/\s+/g, ' ').trim();
      return JSON.stringify(text);
    }).join(',')
  );

  if (!rows.length) {
    window.alert('Eksport uchun ko‘rinayotgan ma\'lumot yo‘q.');
    return;
  }

  downloadBlob(`export-${Date.now()}.csv`, [headers.join(','), ...rows].join('\n'), 'text/csv;charset=utf-8');
}

function buildIndexToolbar(table, storageKey) {
  const wrapper = table.closest('.table-wrap, .kc-twrap, .table-responsive') || table.parentElement;
  if (!wrapper || wrapper.previousElementSibling?.classList?.contains('index-table-toolbar')) return;

  const toolbar = document.createElement('div');
  toolbar.className = 'index-table-toolbar';
  toolbar.innerHTML = `
    <div class="index-table-toolbar__left">
      <div class="index-table-segment" role="tablist" aria-label="Ko‘rinish">
        <button type="button" data-view="list" class="is-active">
          <i data-lucide="list" class="w-4 h-4"></i>
          <span>List</span>
        </button>
        <button type="button" data-view="grid">
          <i data-lucide="layout-grid" class="w-4 h-4"></i>
          <span>Grid</span>
        </button>
      </div>
    </div>
    <div class="index-table-toolbar__right">
      <span class="index-table-count">0 ta shu betda</span>
      <button type="button" class="index-table-action" data-action="export">
        <i data-lucide="download" class="w-4 h-4"></i>
        Export
      </button>
      <button type="button" class="index-table-action" data-action="import">
        <i data-lucide="upload" class="w-4 h-4"></i>
        Import
      </button>
      <input type="file" class="hidden" accept=".csv,.json" />
    </div>
  `;

  wrapper.parentNode.insertBefore(toolbar, wrapper);
  if (window.lucide) {
    window.lucide.createIcons({ nodes: toolbar.querySelectorAll('[data-lucide]') });
  }

  const countEl = toolbar.querySelector('.index-table-count');
  const fileInput = toolbar.querySelector('input[type="file"]');
  const viewButtons = Array.from(toolbar.querySelectorAll('[data-view]'));

  const savedView = localStorage.getItem(storageKey) || 'grid';
  table.dataset.view = savedView;
  viewButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.view === savedView));

  viewButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const nextView = button.dataset.view;
      table.dataset.view = nextView;
      localStorage.setItem(storageKey, nextView);
      viewButtons.forEach((item) => item.classList.toggle('is-active', item === button));
    });
  });

  toolbar.querySelector('[data-action="export"]').addEventListener('click', () => exportTableAsCsv(table));
  toolbar.querySelector('[data-action="import"]').addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    window.alert(`"${file.name}" tanlandi. Import UI tayyor, serverga import ulanishi modul route'lariga qarab davom ettiriladi.`);
    fileInput.value = '';
  });

  countEl.textContent = `${table.querySelectorAll('tbody tr').length} ta shu betda`;
}

function decorateIndexSearchForms() {
  document.querySelectorAll('form[method="GET"], form[method="get"]').forEach((form) => {
    const searchInput = form.querySelector('input[name="search"]');
    if (!searchInput || form.dataset.indexSearchReady === 'true') return;

    form.classList.add('a122-index-search-form');
    searchInput.classList.add('a122-index-search-input');
    searchInput.setAttribute('type', 'search');

    const buttons = form.querySelectorAll('button, a');
    buttons.forEach((button) => button.classList.add('a122-index-search-action'));

    form.dataset.indexSearchReady = 'true';
  });
}

function prepareResponsiveIndexTables() {
  document.querySelectorAll('table[data-index-grid]').forEach((table, index) => {
    if (table.dataset.indexGridReady === 'true') return;

    const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim());
    table.querySelectorAll('tbody tr').forEach((row) => {
      row.dataset.searchText = (row.innerText || row.textContent || '').replace(/\s+/g, ' ').trim();
      Array.from(row.children).forEach((cell, index) => {
        if (!cell.dataset.label) {
          cell.dataset.label = headers[index] || `Maydon ${index + 1}`;
        }
      });
    });

    buildIndexToolbar(table, `a122-index-view:${window.location.pathname}:${index}`);
    table.dataset.view = localStorage.getItem(`a122-index-view:${window.location.pathname}:${index}`) || 'grid';
    table.dataset.indexGridReady = 'true';
  });
}

document.addEventListener('alpine:init', registerA122Alpine);
registerA122Alpine();
window.lucide = {
  createIcons(options = {}) {
    return createIcons({ icons, ...options });
  },
};

document.addEventListener('DOMContentLoaded', () => {
  setupSidebarController();
  decorateIndexSearchForms();
  prepareResponsiveIndexTables();
  window.lucide.createIcons();

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const Alpine = window.Alpine;
      if (Alpine?.store?.('theme')) {
        Alpine.store('theme').toggle();
        return;
      }

      const dark = !document.documentElement.classList.contains('dark');
      applyA122Theme(dark);
    });
  });
});

document.addEventListener('alpine:initialized', () => {
  decorateIndexSearchForms();
  prepareResponsiveIndexTables();
  window.lucide.createIcons();
  const observer = new MutationObserver(() => window.lucide.createIcons());
  observer.observe(document.body, { childList: true, subtree: true });
});

window.addEventListener('theme:changed', () => {
  if (window.__charts) {
    Object.values(window.__charts).forEach(c => {
      try {
        c.updateOptions({ theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }, chart: { background: 'transparent' } });
      } catch (e) {}
    });
  }
});
window.__charts = {};
