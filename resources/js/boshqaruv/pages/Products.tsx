import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Product {
  id: string;
  rawId: number;
  title: string;
  type: string;
  category?: string;
  seller?: string;
  price: number;
  stock: number;
  sold: number;
  revenue?: number;
  status: string;
  approved?: boolean;
  image?: string | null;
  showUrl?: string;
  editUrl?: string;
  moderateUrl?: string;
}

export default function Products() {
  const { products = [] } = usePage<{ products?: Product[] }>().props;
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('Barchasi');
  const [stockFilter, setStockFilter] = useState('Barchasi');
  const [selected, setSelected] = useState<Product | null>(null);

  const filtered = products.filter((product) => {
    const haystack = `${product.title} ${product.category || ''} ${product.seller || ''}`.toLowerCase();
    const matchSearch = haystack.includes(search.toLowerCase());
    const matchType = typeFilter === 'Barchasi' || product.type === typeFilter;
    const matchStock = stockFilter === 'Barchasi'
      || (stockFilter === 'Omborda' && product.stock > 0)
      || (stockFilter === 'Kam qolgan' && product.stock > 0 && product.stock < 15)
      || (stockFilter === 'Tugagan' && product.stock === 0);
    return matchSearch && matchType && matchStock;
  });

  const types = useMemo(() => ['Barchasi', ...Array.from(new Set(products.map((product) => product.type)))], [products]);
  const totalStock = products.reduce((sum, product) => sum + product.stock, 0);
  const lowStock = products.filter((product) => product.stock > 0 && product.stock < 15).length;

  const moderate = (product: Product, approved: boolean) => {
    if (!product.moderateUrl) return;
    router.patch(product.moderateUrl, { is_approved: approved ? 1 : 0 }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Barcha mahsulotlar</h1>
          <p className="page-subtitle">Kitob, kanselyariya va sovg'alar umumiy katalogi</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/a122/books/create">Kitob qo'shish</a>
          <a className="btn btn-primary-gradient" href="/a122/stationery/create"><i className="bi bi-plus-lg me-1"></i>Kanselyariya qo'shish</a>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami mahsulot', value: products.length, icon: 'bi-box-seam', color: '#4f46e5' },
          { label: 'Ombor jami', value: fmt(totalStock), icon: 'bi-stack', color: '#10b981' },
          { label: 'Kam qolgan', value: lowStock, icon: 'bi-exclamation-triangle', color: '#f59e0b' },
          { label: 'Moderatsiyada', value: products.filter((product) => !product.approved).length, icon: 'bi-shield-check', color: '#7c3aed' },
        ].map((item) => (
          <div className="col-xl-3 col-md-6" key={item.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: item.color }}><i className={`bi ${item.icon}`}></i></div>
                <div><div className="stat-value">{item.value}</div><div className="stat-label">{item.label}</div></div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="d-flex flex-wrap gap-2 mb-3">
          <div className="input-group" style={{ maxWidth: 320 }}>
            <span className="input-group-text bg-white"><i className="bi bi-search text-muted"></i></span>
            <input className="form-control" placeholder="Mahsulot, kategoriya, seller..." value={search} onChange={e => setSearch(e.target.value)} />
          </div>
          <select className="form-select" style={{ width: 'auto' }} value={typeFilter} onChange={e => setTypeFilter(e.target.value)}>
            {types.map((type) => <option key={type} value={type}>{type}</option>)}
          </select>
          <select className="form-select" style={{ width: 'auto' }} value={stockFilter} onChange={e => setStockFilter(e.target.value)}>
            <option value="Barchasi">Barcha holatlar</option>
            <option value="Omborda">Omborda</option>
            <option value="Kam qolgan">Kam qolgan</option>
            <option value="Tugagan">Tugagan</option>
          </select>
        </div>

        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th></th><th>Mahsulot</th><th>Turi</th><th>Seller</th><th>Narx</th><th>Ombor</th><th>Sotilgan</th><th>Status</th><th>Amallar</th></tr></thead>
            <tbody>
              {filtered.map((product) => (
                <tr key={product.id}>
                  <td>
                    <div className="thumb d-grid place-items-center" style={{ fontSize: 20 }}>
                      {product.image ? <img src={product.image} alt={product.title} /> : <i className="bi bi-box"></i>}
                    </div>
                  </td>
                  <td><div className="fw-semibold">{product.title}</div><small className="text-muted">#{product.rawId} · {product.category || '—'}</small></td>
                  <td><span className={`chip ${product.type === 'Kitob' ? 'chip-purple' : product.type === 'Kanselyariya' ? 'chip-info' : 'chip-warning'}`}>{product.type}</span></td>
                  <td>{product.seller || '—'}</td>
                  <td className="fw-semibold">{fmt(product.price)} so'm</td>
                  <td><span className={`chip ${product.stock < 1 ? 'chip-danger' : product.stock < 15 ? 'chip-warning' : 'chip-success'}`}>{product.stock} dona</span></td>
                  <td>{product.sold}</td>
                  <td><span className={`chip ${product.approved ? 'chip-success' : 'chip-warning'}`}>{product.approved ? product.status : 'Moderatsiya'}</span></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => setSelected(product)}><i className="bi bi-eye"></i></button>
                    {product.editUrl ? <a className="btn btn-sm btn-light me-1" href={product.editUrl}><i className="bi bi-pencil"></i></a> : null}
                    {product.moderateUrl ? <button className="btn btn-sm btn-light" onClick={() => moderate(product, !product.approved)}><i className="bi bi-shield-check"></i></button> : null}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><small className="text-muted">Turi</small><div className="fw-semibold">{selected?.type}</div></div>
            <div className="col-6"><small className="text-muted">Kategoriya</small><div>{selected?.category || '—'}</div></div>
            <div className="col-6"><small className="text-muted">Narx</small><div className="fw-bold text-primary">{fmt(selected?.price || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Daromad</small><div>{fmt(selected?.revenue || 0)} so'm</div></div>
            <div className="col-6"><small className="text-muted">Ombor</small><div>{selected?.stock || 0} dona</div></div>
            <div className="col-6"><small className="text-muted">Sotilgan</small><div>{selected?.sold || 0} dona</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.showUrl ? <a className="btn btn-primary-gradient" href={selected.showUrl}>Ko'rish</a> : null}
          <Button variant="light" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
