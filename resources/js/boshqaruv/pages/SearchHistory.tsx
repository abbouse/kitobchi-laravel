import { useState, type ReactNode } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import PaginationControls from '../components/PaginationControls';
import { Avatar as PAvatar } from '../components/Profile';
import { EmptyState } from '../components/Axelit';

const fmt = (n?: number) => new Intl.NumberFormat('uz-UZ').format(Number(n || 0));

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

  const summary = searchHistoryInsights.summary || { totalRecords: 0, zeroResultRecords: 0, uniqueQueries: 0 };
  const successRate = summary.totalRecords ? Math.round((1 - summary.zeroResultRecords / summary.totalRecords) * 1000) / 10 : 0;
  const typeTone = (type?: string) => (['book', 'kitob'].includes(String(type || '').toLowerCase()) ? 'primary' : String(type || '').toLowerCase().includes('author') ? 'info' : String(type || '').toLowerCase().includes('stationery') ? 'warning' : 'secondary');
  const stats: Array<{ label: string; value: string | number; icon: string; tone: string; note: ReactNode }> = [
    { label: 'Jami qidiruvlar', value: fmt(summary.totalRecords), icon: 'ti ti-search', tone: 'primary', note: <span className="text-secondary">barcha yozuvlar</span> },
    { label: 'Noyob so‘rovlar', value: fmt(summary.uniqueQueries), icon: 'ti ti-fingerprint', tone: 'info', note: <span className="text-secondary">turli so‘zlar</span> },
    { label: 'Natijasiz qidiruvlar', value: fmt(summary.zeroResultRecords), icon: 'ti ti-search-off', tone: 'danger', note: <span className="text-danger f-w-500">{summary.totalRecords ? Math.round(summary.zeroResultRecords / summary.totalRecords * 1000) / 10 : 0}% <span className="text-secondary f-w-400">ulush</span></span> },
    { label: 'Topilish darajasi', value: `${successRate}%`, icon: 'ti ti-target-arrow', tone: 'success', note: <span className={successRate >= 80 ? 'text-success f-w-500' : 'text-warning-dark f-w-500'}><i className={`ti ${successRate >= 80 ? 'ti-arrow-up-right' : 'ti-arrow-down-right'} me-1`}></i>{successRate >= 80 ? 'yaxshi' : 'katalogni to‘ldirish kerak'}</span> },
  ];

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Qidiruv tarixi</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Foydalanuvchilar nimalarni qidiryapti va natija sifati</p>
        </div>
      </div>

      <div className="row">
        {stats.map((stat) => (
          <div className="col-sm-6 col-xl-3" key={stat.label}>
            <div className="card api-eshop-card">
              <div className="card-body">
                <div className="d-flex justify-content-between align-items-center">
                  <h6 className="mb-0">{stat.label}</h6>
                  <span className={`bg-light-${stat.tone} h-40 w-40 d-flex-center b-r-15`}><i className={`${stat.icon} f-s-20`}></i></span>
                </div>
                <h3 className="mt-2 mb-0">{stat.value}</h3>
                <p className="mt-2 f-s-14 mb-0">{stat.note}</p>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row">
        <div className="col-xl-6">
          <div className="card h-100">
            <div className="card-header">
              <h5 className="mb-0">Top so‘rovlar</h5>
              <p className="mb-0 text-secondary f-s-13">Eng ko‘p qidirilgan so‘zlar va topilish darajasi</p>
            </div>
            <div className="card-body">
              <ul className="list-unstyled mb-0 d-flex flex-column gap-3">
                {(searchHistoryInsights.topQueries || []).map((query, index) => (
                  <li key={query.text}>
                    <div className="d-flex align-items-center gap-3">
                      <span className={`h-35 w-35 d-flex-center b-r-50 f-w-600 flex-shrink-0 text-light-${['primary', 'success', 'info', 'warning', 'danger'][index % 5]}`}>{index + 1}</span>
                      <div className="flex-grow-1 min-w-0">
                        <div className="d-flex justify-content-between align-items-center gap-2">
                          <h6 className="mb-0 f-w-600 txt-ellipsis-1">{query.text}</h6>
                          <span className="badge text-light-primary flex-shrink-0">{fmt(query.totalSearches)} marta</span>
                        </div>
                        <div className="progress w-100 h-5 mt-2" role="progressbar" aria-valuenow={query.successRate || 0} aria-valuemin={0} aria-valuemax={100}>
                          <div className={`progress-bar ${Number(query.successRate || 0) >= 70 ? 'bg-success' : Number(query.successRate || 0) >= 30 ? 'bg-warning' : 'bg-danger'}`} style={{ width: `${Math.max(2, Number(query.successRate || 0))}%` }}></div>
                        </div>
                        <div className="d-flex flex-wrap justify-content-between gap-2 mt-1 f-s-12 text-secondary">
                          <span>Topilgan {fmt(query.foundSearches || 0)} · topilmagan {fmt(query.missingSearches || 0)} · {query.successRate || 0}%</span>
                          <span>{query.lastSeenAt || '—'}</span>
                        </div>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
              {(searchHistoryInsights.topQueries || []).length === 0 ? <EmptyState text="Top so‘rovlar topilmadi." /> : null}
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card h-100">
            <div className="card-header">
              <h5 className="mb-0">Katalogga qo‘shish kerak bo‘lishi mumkin</h5>
              <p className="mb-0 text-secondary f-s-13">Ko‘p qidirilgan, lekin natija bermagan so‘rovlar</p>
            </div>
            <div className="card-body">
              <div className="d-flex flex-column gap-2">
                {(searchHistoryInsights.missingDemand || []).map((query) => (
                  <div key={query.text} className="alert alert-border-danger mb-0">
                    <div className="d-flex justify-content-between align-items-start gap-2">
                      <h6 className="mb-1 f-w-600"><i className="ti ti-search-off text-danger me-2"></i>{query.text}</h6>
                      <span className="badge text-light-danger flex-shrink-0">{fmt(query.totalSearches)} marta</span>
                    </div>
                    {query.recommendation ? <p className="text-secondary f-s-13 mb-1">{query.recommendation}</p> : null}
                    <p className="text-secondary f-s-12 mb-0">{query.attempts || 0} ta yozuv · Oxirgi qidiruv: {query.lastSeenAt || '—'}</p>
                  </div>
                ))}
              </div>
              {(searchHistoryInsights.missingDemand || []).length === 0 ? <EmptyState text="Bunday talab hozircha topilmadi." icon="iconoir-check-circle" /> : null}
            </div>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h5 className="mb-0">Barcha qidiruvlar</h5>
            <p className="mb-0 text-secondary f-s-13">{fmt(searchHistoryPagination.total)} ta yozuv</p>
          </div>
          <div className="d-flex flex-wrap align-items-center gap-2">
            <form className="app-form app-icon-form position-relative" style={{ width: 'min(300px, 100%)' }} onSubmit={(event) => { event.preventDefault(); load(); }}>
              <input type="search" className="form-control form-control-sm" placeholder="Qidiruv so'zi yoki foydalanuvchi..." value={search} onChange={e => setSearch(e.target.value)} />
              <i className="ti ti-search"></i>
            </form>
            <select className="form-select form-select-sm w-auto" value={filter} onChange={e => { setFilter(e.target.value); load(1, e.target.value); }}>
              {types.map((type) => <option key={type} value={type}>{type === 'all' ? 'Barcha turlar' : type}</option>)}
            </select>
          </div>
        </div>
        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle mb-0">
              <thead><tr><th>So'z</th><th>Foydalanuvchi</th><th>Natija</th><th>Turi</th><th className="text-end">Topilgan</th><th className="text-end">Qidirilgan</th><th>Draft</th><th>Oxirgi</th></tr></thead>
              <tbody>
                {searchHistory.map((item) => (
                  <tr key={item.id}>
                    <td className="f-w-600"><i className="ti ti-search text-secondary me-2"></i>{item.text || '—'}</td>
                    <td><div className="d-flex align-items-center gap-2"><PAvatar name={String(item.user || '?')} size="xs" /><span className="text-nowrap">{item.user}</span></div></td>
                    <td className="text-secondary">{item.resultName || '—'}</td>
                    <td>{item.resultType ? <span className={`badge text-outline-${typeTone(item.resultType)}`}>{item.resultType}</span> : '—'}</td>
                    <td className="text-end">{item.resultCount > 0 ? <span className="badge text-light-success">{fmt(item.resultCount)}</span> : <span className="badge text-light-danger">Topilmadi</span>}</td>
                    <td className="text-end f-w-600">{fmt(item.searchCount)} marta</td>
                    <td><span className={`badge ${item.draft ? 'text-light-warning' : 'text-light-secondary'}`}>{item.draft ? 'Draft' : "Yo'q"}</span></td>
                    <td className="text-secondary text-nowrap">{item.date || '—'}</td>
                  </tr>
                ))}
                {searchHistory.length === 0 ? <tr><td colSpan={8}><EmptyState text="Qidiruv yozuvlari topilmadi" /></td></tr> : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...searchHistoryPagination} onPageChange={(page) => load(page)} />
        </div>
      </div>
    </div>
  );
}
