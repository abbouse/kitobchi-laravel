import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';

interface SearchItem {
  id: number;
  text?: string;
  user: string;
  resultCount: number;
  resultName?: string;
  resultType?: string;
  searchCount: number;
  draft: boolean;
  date?: string;
}

export default function SearchHistory() {
  const { searchHistory = [], searchHistoryPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, searchHistoryTypes = [], searchHistoryFilters = {} } = usePage<{ searchHistory?: SearchItem[]; searchHistoryPagination?: { page: number; totalPages: number; from: number; to: number; total: number }; searchHistoryTypes?: string[]; searchHistoryFilters?: { type?: string; search?: string } }>().props;
  const [filter, setFilter] = useState(searchHistoryFilters.type || 'all');
  const [search, setSearch] = useState(searchHistoryFilters.search || '');
  const load = (page = 1, type = filter, term = search) => router.get('/boshqaruv/search-history', { search_history_page: page, search_history_type: type, search_history_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  const types = ['all', ...searchHistoryTypes];

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Qidiruv tarixi</h1>
          <p className="page-subtitle">Foydalanuvchilar nimalarni qidiryapti va natija sifati</p>
        </div>
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          <form className="input-group" style={{ maxWidth: 320 }} onSubmit={(event) => { event.preventDefault(); load(); }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Qidiruv so'zi yoki foydalanuvchi..." value={search} onChange={e => setSearch(e.target.value)} />
          </form>
          <select className="form-select" style={{ width: 'auto' }} value={filter} onChange={e => { setFilter(e.target.value); load(1, e.target.value); }}>
            {types.map((type) => <option key={type} value={type}>{type === 'all' ? 'Barchasi' : type}</option>)}
          </select>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>So'z</th><th>Foydalanuvchi</th><th>Natija</th><th>Turi</th><th>Topilgan</th><th>Qidirilgan</th><th>Draft</th><th>Oxirgi</th></tr></thead>
            <tbody>
              {searchHistory.map((item) => (
                <tr key={item.id}>
                  <td className="fw-semibold">{item.text || '—'}</td>
                  <td>{item.user}</td>
                  <td>{item.resultName || '—'}</td>
                  <td><span className="chip chip-gray">{item.resultType || '—'}</span></td>
                  <td>{item.resultCount}</td>
                  <td><strong>{item.searchCount} marta</strong></td>
                  <td><span className={`chip ${item.draft ? 'chip-warning' : 'chip-success'}`}>{item.draft ? 'Ha' : "Yo'q"}</span></td>
                  <td className="text-muted">{item.date || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...searchHistoryPagination} onPageChange={(page) => load(page)} />
      </div>
    </div>
  );
}
