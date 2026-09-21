import { useState } from 'react';
import { PageCrumbs } from '../Layout';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';
import { ProfileCard, Avatar as PAvatar } from '../components/Profile';

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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Nashriyotlar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {publishers.length} ta nashriyot</p></div>
        <button className="btn btn-primary" onClick={() => setEditing({ name: '' })}><i className="ti ti-plus me-1"></i>Nashriyot qo'shish</button>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>Nashriyot</th><th>Kitoblar</th><th>Rasm</th><th>Amallar</th></tr></thead>
              <tbody>
                {pagination.paginated.map((publisher, i) => (
                  <tr key={publisher.id}>
                    <td><div className="d-flex align-items-center gap-2"><PAvatar square src={publisher.image} name={publisher.name} icon="ti ti-building" size="lg" /><div><div className="f-w-600">{publisher.name}</div><div className="text-muted f-s-13">#{publisher.id}</div></div></div></td>
                    <td className="f-w-600">{fmt(publisher.books)}</td>
                    <td><span className={`badge ${publisher.image ? 'text-light-success' : 'text-light-secondary'}`}>{publisher.image ? 'Bor' : "Yo'q"}</span></td>
                    <td><div className="d-flex gap-2"><button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" onClick={() => openDetail(publisher)}><i className="ti ti-eye"></i></button><button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => setEditing(publisher)}><i className="ti ti-pencil"></i></button><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(publisher)}><i className="ti ti-trash"></i></button></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationControls {...pagination} onPageChange={pagination.setPage} />
        </div>
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
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{isEdit ? 'Nashriyotni tahrirlash' : "Nashriyot qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <div className="col-12"><label className="form-label">Nomi</label><input name="name" defaultValue={publisher?.name || ''} required className="form-control" /></div>
          <div className="col-12"><label className="form-label">Rasm fayl</label><input name="image" type="file" accept="image/*" className="form-control" /></div>
          {isEdit && publisher?.image ? <div className="col-12"><div className="h-45 w-45 d-flex-center b-r-10 bg-light-primary f-w-600 f-s-16 overflow-hidden flex-shrink-0 mb-2">{<img className="w-100 h-100 object-fit-cover" src={publisher.image} alt={publisher.name} />}</div><label className="form-check"><input className="form-check-input" type="checkbox" checked={removeImage} onChange={(e) => setRemoveImage(e.target.checked)} /><span className="form-check-label ms-2">Hozirgi rasmni olib tashlash</span></label></div> : null}
        </div></Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function PublisherDetailModal({ publisher, detail, loading, onHide, onEdit, onDelete }: { publisher: Pub | null; detail: PubDetail | null; loading: boolean; onHide: () => void; onEdit: () => void; onDelete: (publisher: Pub) => void }) {
  return (
    <Modal show={!!publisher} onHide={onHide} size="xl" centered>
      <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{publisher?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {loading ? <div className="text-center py-5"><span className="spinner-border text-primary"></span><p className="text-secondary mt-2 mb-0">Ma'lumot yuklanmoqda...</p></div> : !detail ? <div className="text-muted">Nashriyot tanlanmagan.</div> : <div className="row">
          <div className="col-lg-4 col-xxl-3"><ProfileCard square image={detail.image || null} icon="ti ti-building" name={detail.name} subtitle="Nashriyot" stats={[{ label: 'Kitoblar', value: fmt(detail.booksCount) }]} /></div>
          <div className="col-lg-8 col-xxl-9"><div className="card"><div className="card-header"><h5 className="mb-0">Ulangan kitoblar</h5></div><div className="card-body"><BooksTable rows={detail.books} /></div></div></div>
        </div>}
      </Modal.Body>
      <Modal.Footer>{detail ? <Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button> : null}{publisher ? <Button variant="outline-danger" onClick={() => onDelete(publisher)}>O'chirish</Button> : null}<Button variant="light-secondary" onClick={onHide}>Yopish</Button></Modal.Footer>
    </Modal>
  );
}

function BooksTable({ rows }: { rows: BookRow[] }) {
  if (!rows.length) return <div className="text-muted f-s-13">Ulangan kitob topilmadi.</div>;

  return <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle mb-0"><thead><tr><th>ID</th><th>Kitob</th><th>Muallif</th><th>Kategoriya</th><th>Seller</th><th>Narx</th><th>Qoldiq</th><th>Sotildi</th><th>Holat</th></tr></thead><tbody>{rows.map((book) => <tr key={book.id}><td>#{book.id}</td><td className="f-w-600">{book.name}</td><td>{book.author || '—'}</td><td>{book.category || '—'}</td><td>{book.seller || '—'}</td><td>{fmt(book.price)}</td><td>{book.stock}</td><td>{book.sold}</td><td><span className={`badge ${book.hidden || !book.approved ? 'text-light-warning' : 'text-light-success'}`}>{book.hidden ? 'Yashirin' : book.status}</span></td></tr>)}</tbody></table></div>;
}
