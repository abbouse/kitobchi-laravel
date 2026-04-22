/* ====================================================================
   Veritas Admin — global JS helpers
   Alpine.js componentlari uchun store va utils
   ==================================================================== */

// ---------- Theme ----------
(function () {
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const dark = saved ? saved === 'dark' : prefersDark;
  if (dark) document.documentElement.classList.add('dark');
})();

document.addEventListener('alpine:init', () => {
  // ---------- Theme store ----------
  Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
      this.dark = !this.dark;
      document.documentElement.classList.toggle('dark', this.dark);
      localStorage.setItem('theme', this.dark ? 'dark' : 'light');
      // Charts qayta render
      window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: this.dark } }));
    },
  });

  // ---------- Sidebar store ----------
  Alpine.store('sidebar', {
    mobileOpen: false,
    open() { this.mobileOpen = true; },
    close() { this.mobileOpen = false; },
    toggle() { this.mobileOpen = !this.mobileOpen; },
  });

  // ---------- Sidebar groups (collapse) ----------
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

  // ---------- CRUD table component ----------
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
      return this.rows.filter(r =>
        Object.values(r).some(v => String(v ?? '').toLowerCase().includes(s))
      );
    },
    get allChecked() {
      return this.filtered.length > 0 && this.selected.length === this.filtered.length;
    },
    toggleAll() {
      this.selected = this.allChecked ? [] : this.filtered.map(r => r.id);
    },
    toggleOne(id) {
      const i = this.selected.indexOf(id);
      if (i > -1) this.selected.splice(i, 1);
      else this.selected.push(id);
    },
    askDelete(id) { this.deleteId = id; this.deleteOpen = true; },
    confirmDelete() {
      this.rows = this.rows.filter(r => r.id !== this.deleteId);
      this.selected = this.selected.filter(s => s !== this.deleteId);
      this.deleteOpen = false; this.deleteId = null;
    },
    bulkDelete() {
      if (!confirm(this.selected.length + ' ta yozuvni o‘chirishga ishonchingiz komilmi?')) return;
      this.rows = this.rows.filter(r => !this.selected.includes(r.id));
      this.selected = [];
    },
    exportCSV() {
      const data = this.selected.length
        ? this.rows.filter(r => this.selected.includes(r.id))
        : this.filtered;
      if (!data.length) { alert('Eksport uchun ma\'lumot yo\'q'); return; }
      const keys = Object.keys(data[0]);
      const csv = [
        keys.join(','),
        ...data.map(r => keys.map(k => JSON.stringify(r[k] ?? '')).join(',')),
      ].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'export-' + Date.now() + '.csv';
      a.click(); URL.revokeObjectURL(url);
    },
    exportJSON() {
      const data = this.selected.length
        ? this.rows.filter(r => this.selected.includes(r.id))
        : this.filtered;
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'export-' + Date.now() + '.json';
      a.click(); URL.revokeObjectURL(url);
    },
    print() { window.print(); },
  }));
});

// ---------- Lucide icons re-render after Alpine updates ----------
document.addEventListener('alpine:initialized', () => {
  if (window.lucide) lucide.createIcons();
  // Re-render after DOM mutations (Alpine renders)
  const observer = new MutationObserver(() => {
    if (window.lucide) lucide.createIcons();
  });
  observer.observe(document.body, { childList: true, subtree: true });
});

// ---------- ApexCharts theme reactivity ----------
window.addEventListener('theme:changed', () => {
  if (window.__charts) {
    Object.values(window.__charts).forEach(c => {
      try {
        c.updateOptions({
          theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
          chart: { background: 'transparent' },
        });
      } catch (e) {}
    });
  }
});
window.__charts = {};
