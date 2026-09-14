import { useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface BookRow {
  id: number;
  name: string;
  category?: string | null;
  seller?: string | null;
  price: number;
  stock: number;
  sold: number;
  status: string;
  approved: boolean;
  hidden: boolean;
}

interface Author {
  id: number;
  name: string;
  books: number;
  bio?: string;
  image?: string | null;
  rawImage?: string | null;
  externalId?: string | null;
  slug?: string | null;
  sourceUrl?: string | null;
  hasMultipleAuthors?: boolean;
  needsAiPortrait?: boolean;
  dataUrl?: string;
  updateUrl?: string;
  destroyUrl?: string;
  generateImagePromptUrl?: string;
}

interface AuthorDetail extends Omit<Author, 'books'> {
  booksCount: number;
  books: BookRow[];
  actions: Record<string, string>;
}

const emptyAuthor: Partial<Author> = { name: '', rawImage: '', externalId: '', slug: '', sourceUrl: '' };

export default function Authors() {
  const { authors = [] } = usePage<{ authors?: Author[] }>().props;
  const [selected, setSelected] = useState<Author | null>(null);
  const [detail, setDetail] = useState<AuthorDetail | null>(null);
  const [loading, setLoading] = useState(false);
  const [editing, setEditing] = useState<Partial<Author> | null>(null);
  const pagination = useClientPagination(authors, 24);

  const openDetail = async (author: Author) => {
    setSelected(author);
    setDetail(null);
    setLoading(true);
    try {
      const response = await fetch(author.dataUrl || `/boshqaruv/authors/${author.id}/data`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('Muallif maʼlumotlari yuklanmadi');
      setDetail(await response.json());
    } finally {
      setLoading(false);
    }
  };

  const destroy = (author: Author) => {
    if (!author.destroyUrl || !confirm(`${author.name} muallifini o'chirasizmi? Ulangan kitoblar muallifsiz qoladi.`)) return;
    router.delete(author.destroyUrl, { preserveScroll: true, onSuccess: () => { setSelected(null); setDetail(null); } });
  };

  const generatePrompt = (author: Author) => {
    if (!author.generateImagePromptUrl) return;
    router.post(author.generateImagePromptUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Mualliflar</h1><p className="page-subtitle">Jami {authors.length} ta muallif</p></div>
        <button className="btn btn-primary-gradient" onClick={() => setEditing(emptyAuthor)}><i className="bi bi-plus-lg me-1"></i>Muallif qo'shish</button>
      </div>

      <div className="row g-3">
        {pagination.paginated.map((author, i) => (
          <div className="col-xl-4 col-md-6" key={author.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div className="resource-avatar">
                    {author.image ? <img src={author.image} alt={author.name} /> : initials(author.name)}
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{author.name}</div>
                    <div className="text-muted small mt-1 text-truncate">{author.bio || 'Muallif katalogi'}</div>
                  </div>
                </div>
                <div className="d-flex justify-content-around pt-3 border-top text-center">
                  <Metric value={author.books} label="Kitob" tone="text-primary" />
                  <Metric value={author.hasMultipleAuthors ? 'Guruh' : 'Yakka'} label="Tur" tone="text-info" />
                  <Metric value={author.needsAiPortrait ? 'AI' : 'OK'} label="Rasm" tone="text-success" />
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => openDetail(author)}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light flex-fill" onClick={() => setEditing(author)}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(author)}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <PaginationControls {...pagination} onPageChange={pagination.setPage} />

      <AuthorDetailModal author={selected} detail={detail} loading={loading} onHide={() => { setSelected(null); setDetail(null); }} onEdit={() => detail && setEditing({ id: detail.id, name: detail.name, rawImage: detail.rawImage, externalId: detail.externalId, slug: detail.slug, sourceUrl: detail.sourceUrl, updateUrl: detail.actions.updateUrl, destroyUrl: detail.actions.destroyUrl, generateImagePromptUrl: detail.actions.generateImagePromptUrl })} onDelete={destroy} onPrompt={generatePrompt} />
      <AuthorFormModal author={editing} onHide={() => setEditing(null)} />
    </div>
  );
}

function AuthorFormModal({ author, onHide }: { author: Partial<Author> | null; onHide: () => void }) {
  const isEdit = !!author?.id;
  const [removeImage, setRemoveImage] = useState(false);

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    if (isEdit) form.append('_method', 'put');
    if (removeImage) form.set('remove_image', '1');
    router.post(isEdit ? String(author?.updateUrl) : '/boshqaruv/authors', form, { preserveScroll: true, forceFormData: true, onSuccess: onHide });
  };

  return (
    <Modal show={!!author} onHide={onHide} size="lg" centered>
      <form onSubmit={submit}>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{isEdit ? 'Muallifni tahrirlash' : "Muallif qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <Field name="name" label="Nomi" defaultValue={author?.name} required />
          <Field name="image" label="Rasm URL yoki storage path" defaultValue={author?.rawImage} />
          <div className="col-md-6"><label className="form-label">Rasm fayl</label><input name="image_file" type="file" accept="image/*" className="form-control" /></div>
          <Field name="external_id" label="External ID" defaultValue={author?.externalId} />
          <Field name="slug" label="Slug" defaultValue={author?.slug} />
          <Field name="source_url" label="Manba URL" defaultValue={author?.sourceUrl} wide />
          {isEdit ? <div className="col-12"><label className="form-check"><input className="form-check-input" type="checkbox" checked={removeImage} onChange={(e) => setRemoveImage(e.target.checked)} /><span className="form-check-label ms-2">Hozirgi rasmni olib tashlash</span></label></div> : null}
        </div></Modal.Body>
        <Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function AuthorDetailModal({ author, detail, loading, onHide, onEdit, onDelete, onPrompt }: { author: Author | null; detail: AuthorDetail | null; loading: boolean; onHide: () => void; onEdit: () => void; onDelete: (author: Author) => void; onPrompt: (author: Author) => void }) {
  return (
    <Modal show={!!author} onHide={onHide} size="xl" centered>
      <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{author?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {loading ? <div className="text-center text-muted py-5">Ma'lumot yuklanmoqda...</div> : !detail ? <div className="text-muted">Muallif tanlanmagan.</div> : <div className="row g-3">
          <div className="col-lg-4"><div className="detail-panel h-100 text-center">
            <div className="resource-avatar mx-auto mb-3" style={{ width: 96, height: 96, fontSize: 32 }}>{detail.image ? <img src={detail.image} alt={detail.name} /> : initials(detail.name)}</div>
            <h4 className="fw-bold">{detail.name}</h4>
            <div className="text-muted small text-break">{detail.sourceUrl || detail.externalId || 'Manba kiritilmagan'}</div>
            <div className="d-grid gap-2 mt-3">
              <span className={`chip ${detail.needsAiPortrait ? 'chip-warning' : 'chip-success'}`}>{detail.needsAiPortrait ? 'AI portret kerak' : 'Rasm holati yaxshi'}</span>
              <span className="chip chip-info">{detail.hasMultipleAuthors ? 'Ko‘p muallifli yozuv' : 'Yakka muallif'}</span>
            </div>
          </div></div>
          <div className="col-lg-8"><div className="detail-panel h-100">
            <h6 className="fw-bold mb-3">Ulangan kitoblar ({fmt(detail.booksCount)})</h6>
            <BooksTable rows={detail.books} />
          </div></div>
        </div>}
      </Modal.Body>
      <Modal.Footer>
        {detail?.needsAiPortrait ? <Button variant="outline-secondary" onClick={() => author && onPrompt(author)}>AI prompt</Button> : null}
        {detail ? <Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button> : null}
        {author ? <Button variant="outline-danger" onClick={() => onDelete(author)}>O'chirish</Button> : null}
        <Button variant="light" onClick={onHide}>Yopish</Button>
      </Modal.Footer>
    </Modal>
  );
}

function BooksTable({ rows }: { rows: BookRow[] }) {
  if (!rows.length) return <div className="text-muted small">Ulangan kitob topilmadi.</div>;

  return <div className="table-responsive"><table className="table data-table mb-0"><thead><tr><th>ID</th><th>Kitob</th><th>Kategoriya</th><th>Seller</th><th>Narx</th><th>Qoldiq</th><th>Sotildi</th><th>Holat</th></tr></thead><tbody>{rows.map((book) => <tr key={book.id}><td>#{book.id}</td><td className="fw-semibold">{book.name}</td><td>{book.category || '—'}</td><td>{book.seller || '—'}</td><td>{fmt(book.price)}</td><td>{book.stock}</td><td>{book.sold}</td><td><span className={`chip ${book.hidden || !book.approved ? 'chip-warning' : 'chip-success'}`}>{book.hidden ? 'Yashirin' : book.status}</span></td></tr>)}</tbody></table></div>;
}

function Field({ name, label, defaultValue, required, wide }: { name: string; label: string; defaultValue?: string | null; required?: boolean; wide?: boolean }) {
  return <div className={wide ? 'col-12' : 'col-md-6'}><label className="form-label">{label}</label><input name={name} defaultValue={defaultValue || ''} required={required} className="form-control" /></div>;
}

function Metric({ value, label, tone }: { value: string | number; label: string; tone: string }) {
  return <div><div className={`fw-bold ${tone}`} style={{ fontSize: 20 }}>{value}</div><small className="text-muted">{label}</small></div>;
}

function initials(name?: string) {
  return (name || 'AU').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}
