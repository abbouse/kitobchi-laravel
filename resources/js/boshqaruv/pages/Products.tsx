import { useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

import { StatWidget } from '../components/Axelit';

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
  const pagination = useClientPagination(filtered, 30);
  const totalStock = products.reduce((sum, product) => sum + product.stock, 0);
  const lowStock = products.filter((product) => product.stock > 0 && product.stock < 15).length;

  const moderate = (product: Product, approved: boolean) => {
    if (!product.moderateUrl) return;
    router.patch(product.moderateUrl, { is_approved: approved ? 1 : 0 }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Barcha mahsulotlar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Kitob, kanselyariya va sovg'alar umumiy katalogi</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/boshqaruv/books">Kitoblar</a>
          <a className="btn btn-primary" href="/boshqaruv/stationeries"><i className="ti ti-plus me-1"></i>Kanselyariya</a>
        </div>
      </div>

      <div className="row">
        {[
          { label: 'Jami mahsulot', value: products.length, icon: 'ti-package', color: 'rgba(var(--primary), 1)' },
          { label: 'Ombor jami', value: fmt(totalStock), icon: 'ti-stack', color: 'rgba(var(--success), 1)' },
          { label: 'Kam qolgan', value: lowStock, icon: 'ti-alert-triangle', color: 'rgba(var(--warning-dark), 1)' },
          { label: 'Moderatsiyada', value: products.filter((product) => !product.approved).length, icon: 'ti-shield-check', color: 'rgba(var(--primary), 1)' },
        ].map((item, kpiIndex) => (<div className="col-xl-3 col-md-6" key={item.label}>
          <StatWidget index={kpiIndex} label={item.label} value={item.value} />
        </div>))}
      </div>

      <div className="card">
        <div className="card-body">
          <div className="d-flex flex-wrap gap-2 mb-3">
            <div className="app-form app-icon-form position-relative" style={{ width: 'min(320px, 100%)' }}>
              <input type="search" className="form-control" placeholder="Mahsulot, kategoriya, seller..." value={search} onChange={e => setSearch(e.target.value)} />
              <i className="ti ti-search"></i>
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

          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th></th><th>Mahsulot</th><th>Turi</th><th>Seller</th><th>Narx</th><th>Ombor</th><th>Sotilgan</th><th>Status</th><th>Amallar</th></tr></thead>
              <tbody>
                {pagination.paginated.map((product) => (
                  <tr key={product.id}>
                    <td>
                      <div className="w-40 h-55 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 f-s-20">
                        {product.image ? <img className="w-100 h-100 object-fit-cover" src={product.image} alt={product.title} /> : <i className="ti ti-box"></i>}
                      </div>
                    </td>
                    <td><div className="f-w-600">{product.title}</div><small className="text-muted">#{product.rawId} · {product.category || '—'}</small></td>
                    <td><span className={`badge ${product.type === 'Kitob' ? 'text-light-primary' : product.type === 'Kanselyariya' ? 'text-light-info' : 'text-light-warning'}`}>{product.type}</span></td>
                    <td>{product.seller || '—'}</td>
                    <td className="f-w-600">{fmt(product.price)} so'm</td>
                    <td><span className={`badge ${product.stock < 1 ? 'text-light-danger' : product.stock < 15 ? 'text-light-warning' : 'text-light-success'}`}>{product.stock} dona</span></td>
                    <td>{product.sold}</td>
                    <td><span className={`badge ${product.approved ? 'text-light-success' : 'text-light-warning'}`}>{product.approved ? product.status : 'Moderatsiya'}</span></td>
                    <td>
                      <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 me-1" onClick={() => setSelected(product)}><i className="ti ti-eye"></i></button>
                      {product.moderateUrl ? <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => moderate(product, !product.approved)}><i className="ti ti-shield-check"></i></button> : null}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationControls {...pagination} onPageChange={pagination.setPage} />
        </div>
      </div>

      <Modal show={!!selected} onHide={() => setSelected(null)} centered>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{selected?.title}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Turi</p><div className="f-w-600">{selected?.type}</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Kategoriya</p><div>{selected?.category || '—'}</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Narx</p><div className="f-w-600 text-primary">{fmt(selected?.price || 0)} so'm</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Daromad</p><div>{fmt(selected?.revenue || 0)} so'm</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Ombor</p><div>{selected?.stock || 0} dona</div></div>
            <div className="col-6"><p className="mb-1 f-s-13 text-secondary">Sotilgan</p><div>{selected?.sold || 0} dona</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.moderateUrl ? (
            <Button variant="primary" className="btn-primary" onClick={() => selected && moderate(selected, !selected.approved)}>
              {selected.approved ? 'Moderatsiyaga qaytarish' : 'Tasdiqlash'}
            </Button>
          ) : null}
          <Button variant="light-secondary" onClick={() => setSelected(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
