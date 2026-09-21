import { useState } from 'react';
import { PageCrumbs } from '../Layout';
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

interface QueryInsight {
  text: string;
  totalSearches: number;
  foundSearches?: number;
  missingSearches?: number;
  successRate?: number;
  attempts?: number;
  lastSeenAt?: string;
  recommendation?: string;
}

export default function SearchHistory() {
  const { searchHistory = [], searchHistoryPagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, searchHistoryTypes = [], searchHistoryFilters = {}, searchHistoryInsights = { topQueries: [], missingDemand: [], summary: { totalRecords: 0, zeroResultRecords: 0, uniqueQueries: 0 } } } = usePage<{
    searchHistory?: SearchItem[];
    searchHistoryPagination?: { page: number; totalPages: number; from: number; to: number; total: number };
    searchHistoryTypes?: string[];
    searchHistoryFilters?: { type?: string; search?: string };
    searchHistoryInsights?: {
      topQueries?: QueryInsight[];
      missingDemand?: QueryInsight[];
      summary?: { totalRecords: number; zeroResultRecords: number; uniqueQueries: number };
    };
  }>().props;
  const [filter, setFilter] = useState(searchHistoryFilters.type || 'all');
  const [search, setSearch] = useState(searchHistoryFilters.search || '');
  const load = (page = 1, type = filter, term = search) => router.get('/boshqaruv/search-history', { search_history_page: page, search_history_type: type, search_history_search: term }, { preserveState: true, preserveScroll: true, replace: true });
  const types = ['all', ...searchHistoryTypes];

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Qidiruv tarixi</h1><PageCrumbs />
          <p className="page-subtitle">Foydalanuvchilar nimalarni qidiryapti va natija sifati</p>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-md-4">
          <div className="card-panel h-100">
            <div className="text-muted small mb-2">Jami qidiruv yozuvlari</div>
            <div className="fw-bold fs-3">{searchHistoryInsights.summary?.totalRecords || 0}</div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card-panel h-100">
            <div className="text-muted small mb-2">Natijasiz qidiruvlar</div>
            <div className="fw-bold fs-3 text-danger">{searchHistoryInsights.summary?.zeroResultRecords || 0}</div>
          </div>
        </div>
        <div className="col-md-4">
          <div className="card-panel h-100">
            <div className="text-muted small mb-2">Noyob so‘rovlar</div>
            <div className="fw-bold fs-3">{searchHistoryInsights.summary?.uniqueQueries || 0}</div>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-6">
          <div className="card-panel h-100">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 className="fw-bold mb-1">Top so‘rovlar</h6>
                <div className="text-muted small">Natija topilgan bo‘lsa ham eng ko‘p qidirilgan so‘zlar</div>
              </div>
            </div>
            <div className="d-grid gap-2">
              {(searchHistoryInsights.topQueries || []).map((query) => (
                <div key={query.text} className="border rounded-4 p-3">
                  <div className="d-flex align-items-start justify-content-between gap-2">
                    <div className="fw-semibold">{query.text}</div>
                    <span className="chip chip-info">{query.totalSearches} marta</span>
                  </div>
                  <div className="d-flex flex-wrap gap-2 mt-2">
                    <span className="chip chip-success">Topilgan: {query.foundSearches || 0}</span>
                    <span className="chip chip-gray">Topilmagan: {query.missingSearches || 0}</span>
                    <span className="chip chip-gray">Moslik: {query.successRate || 0}%</span>
                  </div>
                  <div className="text-muted small mt-2">Oxirgi qidiruv: {query.lastSeenAt || '—'}</div>
                </div>
              ))}
              {(searchHistoryInsights.topQueries || []).length === 0 ? <div className="text-muted">Top so‘rovlar topilmadi.</div> : null}
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card-panel h-100">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 className="fw-bold mb-1">Qo‘shib chiqish kerak bo‘lishi mumkin</h6>
                <div className="text-muted small">Ko‘p qidirilgan, lekin natija bermagan so‘rovlar</div>
              </div>
            </div>
            <div className="d-grid gap-2">
              {(searchHistoryInsights.missingDemand || []).map((query) => (
                <div key={query.text} className="border rounded-4 p-3 bg-light-subtle">
                  <div className="d-flex align-items-start justify-content-between gap-2">
                    <div className="fw-semibold">{query.text}</div>
                    <span className="chip chip-danger">{query.totalSearches} marta</span>
                  </div>
                  <div className="text-muted small mt-2">{query.recommendation}</div>
                  <div className="text-muted small mt-2">{query.attempts || 0} ta yozuv · Oxirgi qidiruv: {query.lastSeenAt || '—'}</div>
                </div>
              ))}
              {(searchHistoryInsights.missingDemand || []).length === 0 ? <div className="text-muted">Bunday talab hozircha topilmadi.</div> : null}
            </div>
          </div>
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
          <table className="table table-bottom-border align-middle data-table">
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
