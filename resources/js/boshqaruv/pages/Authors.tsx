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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Mualliflar</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {authors.length} ta muallif</p></div>
        <button className="btn btn-primary" onClick={() => setEditing(emptyAuthor)}><i className="ti ti-plus me-1"></i>Muallif qo'shish</button>
      </div>

      <div className="row">
        {pagination.paginated.map((author, i) => (
          <div className="col-xl-4 col-md-6" key={author.id}>
            <div className="card h-100">
              <div className="card-body d-flex flex-column justify-content-between">
                <div>
                  <div className="d-flex align-items-center gap-3 mb-3">
                    <PAvatar src={author.image} name={author.name} size="xl" />
                    <div className="min-w-0" style={{ flex: 1 }}>
                      <div className="f-w-600 text-truncate">{author.name}</div>
                      <div className="text-muted f-s-13 mt-1 text-truncate">{author.bio || 'Muallif katalogi'}</div>
                    </div>
                  </div>
                  <div className="d-flex justify-content-around pt-3 b-t-1-light text-center">
                    <Metric value={author.books} label="Kitob" tone="text-primary" />
                    <Metric value={author.hasMultipleAuthors ? 'Guruh' : 'Yakka'} label="Tur" tone="text-info" />
                    <Metric value={author.needsAiPortrait ? 'AI' : 'OK'} label="Rasm" tone="text-success" />
                  </div>
                </div>
                <div className="d-flex gap-2 mt-3 pt-2">
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => openDetail(author)}><i className="ti ti-eye"></i></button>
                  <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => setEditing(author)}><i className="ti ti-pencil"></i></button>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(author)}><i className="ti ti-trash"></i></button>
                </div>
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
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{isEdit ? 'Muallifni tahrirlash' : "Muallif qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <Field name="name" label="Nomi" defaultValue={author?.name} required />
          <Field name="image" label="Rasm URL yoki storage path" defaultValue={author?.rawImage} />
          <div className="col-md-6"><label className="form-label">Rasm fayl</label><input name="image_file" type="file" accept="image/*" className="form-control" /></div>
          <Field name="external_id" label="External ID" defaultValue={author?.externalId} />
          <Field name="slug" label="Slug" defaultValue={author?.slug} />
          <Field name="source_url" label="Manba URL" defaultValue={author?.sourceUrl} wide />
          {isEdit ? <div className="col-12"><label className="form-check"><input className="form-check-input" type="checkbox" checked={removeImage} onChange={(e) => setRemoveImage(e.target.checked)} /><span className="form-check-label ms-2">Hozirgi rasmni olib tashlash</span></label></div> : null}
        </div></Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function AuthorDetailModal({ author, detail, loading, onHide, onEdit, onDelete, onPrompt }: { author: Author | null; detail: AuthorDetail | null; loading: boolean; onHide: () => void; onEdit: () => void; onDelete: (author: Author) => void; onPrompt: (author: Author) => void }) {
  return (
    <Modal show={!!author} onHide={onHide} size="xl" centered>
      <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{author?.name}</Modal.Title></Modal.Header>
      <Modal.Body>
        {loading ? <div className="text-center py-5"><span className="spinner-border text-primary"></span><p className="text-secondary mt-2 mb-0">Ma'lumot yuklanmoqda...</p></div> : !detail ? <div className="text-muted">Muallif tanlanmagan.</div> : <div className="row">
          <div className="col-lg-4 col-xxl-3">
            <ProfileCard
              image={detail.image || null}
              name={detail.name}
              subtitle={<span className="f-s-13">{detail.sourceUrl || detail.externalId || 'Manba kiritilmagan'}</span>}
              badges={<><span className={`badge ${detail.needsAiPortrait ? 'text-light-warning' : 'text-light-success'}`}>{detail.needsAiPortrait ? 'AI portret kerak' : 'Rasm holati yaxshi'}</span><span className="badge text-light-info">{detail.hasMultipleAuthors ? 'Ko‘p muallifli yozuv' : 'Yakka muallif'}</span></>}
              stats={[{ label: 'Kitoblar', value: fmt(detail.booksCount) }]}
            />
          </div>
          <div className="col-lg-8 col-xxl-9"><div className="card"><div className="card-header"><h5 className="mb-0">Ulangan kitoblar ({fmt(detail.booksCount)})</h5></div><div className="card-body">
              <BooksTable rows={detail.books} />
            </div></div></div>
        </div>}
      </Modal.Body>
      <Modal.Footer>
        {detail?.needsAiPortrait ? <Button variant="outline-secondary" onClick={() => author && onPrompt(author)}>AI prompt</Button> : null}
        {detail ? <Button variant="outline-primary" onClick={onEdit}>Tahrirlash</Button> : null}
        {author ? <Button variant="outline-danger" onClick={() => onDelete(author)}>O'chirish</Button> : null}
        <Button variant="light-secondary" onClick={onHide}>Yopish</Button>
      </Modal.Footer>
    </Modal>
  );
}

function BooksTable({ rows }: { rows: BookRow[] }) {
  if (!rows.length) return <div className="text-muted f-s-13">Ulangan kitob topilmadi.</div>;

  return <div className="table-responsive app-scroll"><table className="table table-bottom-border align-middle mb-0"><thead><tr><th>ID</th><th>Kitob</th><th>Kategoriya</th><th>Seller</th><th>Narx</th><th>Qoldiq</th><th>Sotildi</th><th>Holat</th></tr></thead><tbody>{rows.map((book) => <tr key={book.id}><td>#{book.id}</td><td className="f-w-600">{book.name}</td><td>{book.category || '—'}</td><td>{book.seller || '—'}</td><td>{fmt(book.price)}</td><td>{book.stock}</td><td>{book.sold}</td><td><span className={`badge ${book.hidden || !book.approved ? 'text-light-warning' : 'text-light-success'}`}>{book.hidden ? 'Yashirin' : book.status}</span></td></tr>)}</tbody></table></div>;
}

function Field({ name, label, defaultValue, required, wide }: { name: string; label: string; defaultValue?: string | null; required?: boolean; wide?: boolean }) {
  return <div className={wide ? 'col-12' : 'col-md-6'}><label className="form-label">{label}</label><input name={name} defaultValue={defaultValue || ''} required={required} className="form-control" /></div>;
}

function Metric({ value, label, tone }: { value: string | number; label: string; tone: string }) {
  return <div><div className={`f-w-600 ${tone} f-s-20`}>{value}</div><small className="text-muted">{label}</small></div>;
}

function initials(name?: string) {
  return (name || 'AU').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}
