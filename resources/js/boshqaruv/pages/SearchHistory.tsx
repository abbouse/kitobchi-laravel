import { useState } from 'react';
// Qidiruv tarixi

interface Search { id: number; query: string; user: string; platform: string; count: number; lastSearched: string; }
const initial: Search[] = [
  { id: 1, query: "o'tkan kunlar", user: 'Aziza K.', platform: 'Android', count: 42, lastSearched: '12 daqiqa oldin' },
  { id: 2, query: 'parker ruchka', user: 'Dilnoza R.', platform: 'iOS', count: 28, lastSearched: '1 soat oldin' },
  { id: 3, query: 'moleskine daftar', user: 'Anonim', platform: 'Android', count: 24, lastSearched: '2 soat oldin' },
  { id: 4, query: 'qalamlar', user: 'Anonim', platform: 'Android', count: 18, lastSearched: '3 soat oldin' },
  { id: 5, query: 'stabilo marker', user: 'Shaxlo Y.', platform: 'Android', count: 15, lastSearched: '4 soat oldin' },
  { id: 6, query: 'daftarlar', user: 'Bobur A.', platform: 'iOS', count: 12, lastSearched: '5 soat oldin' },
  { id: 7, query: "o'tkan kunlar narxi", user: 'Anonim', platform: 'iOS', count: 8, lastSearched: '6 soat oldin' },
];

export default function SearchHistory() {
  const [list] = useState(initial);
  const [filter, setFilter] = useState('Barchasi');
  const [search, setSearch] = useState('');

  const filtered = list.filter(s => {
    const match = s.query.toLowerCase().includes(search.toLowerCase()) || s.user.toLowerCase().includes(search.toLowerCase());
    const plat = filter === 'Barchasi' || s.platform === filter;
    return match && plat;
  });

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Qidiruv tarixi</h1>
          <p className="page-subtitle">Foydalanuvchilar nimalarni qidiryapti — eng ko'p qidirilgan so'zlar</p>
        </div>
      </div>

      <div className="card-panel">
        <div className="d-flex gap-2 mb-3 flex-wrap">
          <div className="input-group" style={{ maxWidth: 300 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Qidiruv so'zi yoki foydalanuvchi..." value={search} onChange={e => setSearch(e.target.value)} />
          </div>
          <select className="form-select" style={{ width: 'auto' }} value={filter} onChange={e => setFilter(e.target.value)}>
            <option value="Barchasi">Barcha platformalar</option>
            <option value="Android">Android</option>
            <option value="iOS">iOS</option>
          </select>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>So'z</th><th>Foydalanuvchi</th><th>Platforma</th><th>Qidirilgan</th><th>Oxirgi</th></tr></thead>
            <tbody>
              {filtered.map(s => (
                <tr key={s.id}>
                  <td className="fw-semibold">{s.query}</td>
                  <td>{s.user}</td>
                  <td><i className={`bi ${s.platform === 'Android' ? 'bi-android2' : 'bi-apple'} me-1`} style={{ color: s.platform === 'Android' ? '#10b981' : '#374151' }}></i>{s.platform}</td>
                  <td><strong>{s.count} marta</strong></td>
                  <td className="text-muted">{s.lastSearched}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
