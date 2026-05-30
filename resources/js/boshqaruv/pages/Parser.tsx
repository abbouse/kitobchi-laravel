import { useState } from 'react';
// Parser sahifasi

export default function Parser() {
  const [url, setUrl] = useState('');
  const [log, setLog] = useState<string[]>(['Ready to import...']);
  const [selectedItems, setSelectedItems] = useState<string[]>([]);
  const [items] = useState([
    { id: 1, title: "O'tkan kunlar", author: 'Abdulla Qodiriy', price: 85000, hasStock: true },
    { id: 2, title: 'Mehrobdan chayon', author: 'Abdulla Qodiriy', price: 72000, hasStock: true },
    { id: 3, title: 'Sarob', author: 'Abdulla Qahhor', price: 65000, hasStock: false },
    { id: 4, title: 'Kecha va kunduz', author: "Cho'lpon", price: 78000, hasStock: true },
    { id: 5, title: "Ulug'bek xazinasi", author: "O'. Yoqubov", price: 55000, hasStock: false },
  ]);
  const addLog = (s: string) => setLog(prev => [s, ...prev].slice(0, 20));

  const handleSync = () => {
    addLog('⏳ Katalog sinxronizatsiya boshlandi...');
    setTimeout(() => addLog('✅ Katalog sinxronizatsiya yakunlandi! 34 ta yangi kitob qo\'shildi.'), 1000);
  };

  const handleSingleImport = (title: string) => {
    addLog(`📥 Import qilinmoqda: ${title}...`);
    setTimeout(() => addLog(`✅ ${title} muvaffaqiyatli import qilindi!`), 800);
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Parser — book.uz dan import</h1>
          <p className="page-subtitle">Kitoblarni avtomatik import qilish tizimi</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-outline-secondary" onClick={handleSync}><i className="bi bi-arrow-repeat me-1"></i>Katalog sync</button>
          <button className="btn btn-primary-gradient" onClick={() => { addLog('📥 Bulk import boshlandi...'); setTimeout(() => addLog('✅ 12 ta kitob import qilindi!'), 2000); }}><i className="bi bi-cloud-arrow-down me-1"></i>Stock borlarni bulk import</button>
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-8">
          <div className="card-panel">
            <div className="panel-head">
              <div className="panel-title">Topilgan kitoblar</div>
            </div>
            <div className="d-flex gap-2 mb-3">
              <div className="input-group">
                <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
                <input className="form-control" placeholder="book.uz dan URL yoki qidiruv..." value={url} onChange={e => setUrl(e.target.value)} />
              </div>
              <button className="btn btn-primary-gradient" onClick={() => addLog('🔍 Qidirilmoqda...')}><i className="bi bi-search"></i></button>
            </div>
            <div className="table-responsive">
              <table className="data-table">
                <thead>
                  <tr>
                    <th><input type="checkbox" className="form-check-input" checked={selectedItems.length === items.length} onChange={() => setSelectedItems(selectedItems.length === items.length ? [] : items.map(i => String(i.id)))} /></th>
                    <th>Kitob</th><th>Muallif</th><th>Narx</th><th>Stock</th><th>Amallar</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map(i => (
                    <tr key={i.id}>
                      <td><input type="checkbox" className="form-check-input" checked={selectedItems.includes(String(i.id))} onChange={() => setSelectedItems(prev => prev.includes(String(i.id)) ? prev.filter(x => x !== String(i.id)) : [...prev, String(i.id)])} /></td>
                      <td className="fw-semibold">{i.title}</td>
                      <td className="text-muted">{i.author}</td>
                      <td className="fw-semibold">{i.price.toLocaleString()} so'm</td>
                      <td>{i.hasStock ? <span className="chip chip-success" style={{ fontSize: 9 }}>Bor</span> : <span className="chip chip-danger" style={{ fontSize: 9 }}>Yo'q</span>}</td>
                      <td><button className="btn btn-sm btn-light" onClick={() => handleSingleImport(i.title)}><i className="bi bi-download"></i> Import</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="panel-title mb-3">📋 Import loglari</div>
            <div style={{ maxHeight: 340, overflowY: 'auto' }}>
              {log.map((l, i) => (
                <div key={i} className="py-1 border-bottom small" style={{ fontFamily: 'monospace', fontSize: 11 }}>{l}</div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
