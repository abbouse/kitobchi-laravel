import { useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface BookRow {
  id: number;
  name: string;
  author?: string | null;
  category?: string | null;
  seller?: string | null;
  price: number;
  stock: number;
  sold: number;
  status: string;
  approved: boolean;
  hidden: boolean;
}

interface Pub {
  id: number;
  name: string;
  books: number;
  image?: string | null;
  rawImage?: string | null;
  dataUrl?: string;
  updateUrl?: string;
  destroyUrl?: string;
}

interface PubDetail extends Omit<Pub, 'books'> {
  booksCount: number;
  books: BookRow[];
  actions: Record<string, string>;
}

export default function Publishers() {
  const { publishers = [] } = usePage<{ publishers?: Pub[] }>().props;
  const [selected, setSelected] = useState<Pub | null>(null);
  const [detail, setDetail] = useState<PubDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [editing, setEditing] = useState<Partial<Pub> | null>(null);
  const pagination = useClientPagination(publishers, 30);

  const openDetail = async (publisher: Pub) => {
    setSelected(publisher);
    setDetail(null);
    setLoading(true);
    try {
      const response = await fetch(publisher.dataUrl || `/boshqaruv/publishers/${publisher.id}/data`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('Nashriyot maʼlumotlari yuklanmadi');
      setDetail(await response.json());
    } finally {
      setLoading(false);
    }
  };

  const destroy = (publisher: Pub) => {
    if (!publisher.destroyUrl || !confirm(`${publisher.name} nashriyotini o'chirasizmi? Ulangan kitoblar nashriyotsiz qoladi.`)) return;
    router.delete(publisher.destroyUrl, { preserveScroll: true, onSuccess: () => { setSelected(null); setDetail(null); } });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Nashriyotlar</h1><PageCrumbs /><p className="page-subtitle">Jami {publishers.length} ta nashriyot</p></div>
        <button className="btn btn-primary" onClick={() => setEditing({ name: '' })}><i className="bi bi-plus-lg me-1"></i>Nashriyot qo'shish</button>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="table table-bottom-border align-middle data-table">
            <thead><tr><th>Nashriyot</th><th>Kitoblar</th><th>Rasm</th><th>Amallar</th></tr></thead>
            <tbody>
              {pagination.paginated.map((publisher, i) => (
                <tr key={publisher.id}>
                  <td><div className="d-flex align-items-center gap-2"><div className="resource-avatar square">{publisher.image ? <img src={publisher.image} alt={publisher.name} /> : <i className="bi bi-building"></i>}</div><div><div className="fw-semibold">{publisher.name}</div><div className="text-muted small">#{publisher.id}</div></div></div></td>
                  <td className="fw-semibold">{fmt(publisher.books)}</td>
                  <td><span className={`chip ${publisher.image ? 'chip-success' : 'chip-gray'}`}>{publisher.image ? 'Bor' : "Yo'q"}</span></td>
                  <td><div className="d-flex gap-2"><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => openDetail(publisher)}><i className="bi bi-eye"></i></button><button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => setEditing(publisher)}><i className="bi bi-pencil"></i></button><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(publisher)}><i className="bi bi-trash"></i></button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...pagination} onPageChange={pagination.setPage} />
      </div>

      <PublisherDetailModal publisher={selected} detail={detail} loading={loading} onHide={() => { setSelected(null); setDetail(null); }} onEdit={() => detail && setEditing({ id: detail.id, name: detail.name, image: detail.image, rawImage: detail.rawImage, updateUrl: detail.actions.updateUrl, destroyUrl: detail.actions.destroyUrl })} onDelete={destroy} />
      <PublisherFormModal publisher={editing} onHide={() => setEditing(null)} />
    </div>
  );
}

function PublisherFormModal({ publisher, onHide }: { publisher: Partial<Pub> | null; onHide: () => void }) {
  const isEdit = !!publisher?.id;
  const [removeImage, setRemoveImage] = useState(false);

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    if (isEdit) form.append('_method', 'put');
    if (removeImage) form.set('remove_image', '1');
    router.post(isEdit ? String(publisher?.updateUrl) : '/boshqaruv/publishers', form, { preserveScroll: true, forceFormData: true, onSuccess: onHide });
  };

  return (
    <Modal show={!!publisher} onHide={onHide} centered>
      <form onSubmit={submit}>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{isEdit ? 'Nashriyotni tahrirlash' : "Nashriyot qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <div className="col-12"><label className="form-label">Nomi</label><input name="name" defaultValue={publisher?.name || ''} required className="form-control" /></div>
          <div className="col-12"><label className="form-label">Rasm fayl</label><input name="image" type="file" accept="image/*" className="form-control" /></div>
          {isEdit && publisher?.image ? <div className="col-12"><div className="resource-avatar square mb-2">{<img src={publisher.image} alt={publisher.name} />}</div><label className="form-check"><input className="form-check-input" type="checkbox" checked={removeImage} onChange={(e) => setRemoveImage(e.target.checked)} /><span className="form-check-label ms-2">Hozirgi rasmni olib tashlash</span></label></div> : null}
        </div></Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function PublisherDetailModal({ publisher, detail, loading, onHide, onEdit, onDelete }: { publisher: Pub | null; detail: PubDetail | null; loading: boolean; onHide: () => void; onEdit: () => void; onDelete: (publisher: Pub) => void }) {
  return (
    <Modal show={!!publisher} onHide={onHide} size="xl" centered>
      <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{publisher?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {loading ? <div className="text-center text-muted py-5">Ma'lumot yuklanmoqda...</div> : !detail ? <div className="text-muted">Nashriyot tanlanmagan.</div> : <div className="row g-3">
          <div className="col-lg-3"><div className="detail-panel h-100 text-center"><div className="resource-avatar square mx-auto mb-3" style={{ width: 96, height: 96 }}>{detail.image ? <img src={detail.image} alt={detail.name} /> : <i className="bi bi-building"></i>}</div><h4 className="fw-bold">{detail.name}</h4><span className="chip chip-info">{fmt(detail.booksCount)} ta kitob</span></div></div>
          <div className="col-lg-9"><div className="detail-panel h-100"><h6 className="fw-bold mb-3">Ulangan kitoblar</h6><BooksTable rows={detail.books} /></div></div>
        </div>}
      </Modal.Body>
      <Modal.Footer>{detail ? <Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button> : null}{publisher ? <Button variant="outline-danger" onClick={() => onDelete(publisher)}>O'chirish</Button> : null}<Button variant="light-secondary" onClick={onHide}>Yopish</Button></Modal.Footer>
    </Modal>
  );
}

function BooksTable({ rows }: { rows: BookRow[] }) {
  if (!rows.length) return <div className="text-muted small">Ulangan kitob topilmadi.</div>;

  return <div className="table-responsive"><table className="table table-bottom-border align-middle data-table mb-0"><thead><tr><th>ID</th><th>Kitob</th><th>Muallif</th><th>Kategoriya</th><th>Seller</th><th>Narx</th><th>Qoldiq</th><th>Sotildi</th><th>Holat</th></tr></thead><tbody>{rows.map((book) => <tr key={book.id}><td>#{book.id}</td><td className="fw-semibold">{book.name}</td><td>{book.author || '—'}</td><td>{book.category || '—'}</td><td>{book.seller || '—'}</td><td>{fmt(book.price)}</td><td>{book.stock}</td><td>{book.sold}</td><td><span className={`chip ${book.hidden || !book.approved ? 'chip-warning' : 'chip-success'}`}>{book.hidden ? 'Yashirin' : book.status}</span></td></tr>)}</tbody></table></div>;
}
