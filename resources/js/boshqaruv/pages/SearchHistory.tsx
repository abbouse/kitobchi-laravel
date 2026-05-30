import { useState } from 'react';
import { usePage } from '@inertiajs/react';

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
  const { searchHistory = [] } = usePage<{ searchHistory?: SearchItem[] }>().props;
  const [filter, setFilter] = useState('Barchasi');
  const [search, setSearch] = useState('');

  const filtered = searchHistory.filter((item) => {
    const match = `${item.text || ''} ${item.user} ${item.resultName || ''}`.toLowerCase().includes(search.toLowerCase());
    const type = filter === 'Barchasi' || item.resultType === filter;
    return match && type;
  });
  const types = ['Barchasi', ...Array.from(new Set(searchHistory.map((item) => item.resultType).filter(Boolean)))];

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Qidiruv tarixi</h1>
          <p className="page-subtitle">Foydalanuvchilar nimalarni qidiryapti va natija sifati</p>
        </div>
        <a className="btn btn-outline-secondary" href="/a122/search-history">Eski filtr</a>
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          <div className="input-group" style={{ maxWidth: 320 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Qidiruv so'zi yoki foydalanuvchi..." value={search} onChange={e => setSearch(e.target.value)} />
          </div>
          <select className="form-select" style={{ width: 'auto' }} value={filter} onChange={e => setFilter(e.target.value)}>
            {types.map((type) => <option key={type} value={type}>{type}</option>)}
          </select>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>So'z</th><th>Foydalanuvchi</th><th>Natija</th><th>Turi</th><th>Topilgan</th><th>Qidirilgan</th><th>Draft</th><th>Oxirgi</th></tr></thead>
            <tbody>
              {filtered.map((item) => (
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
      </div>
    </div>
  );
}
