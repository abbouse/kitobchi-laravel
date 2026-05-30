import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface Author {
  id: number;
  name: string;
  country?: string;
  books: number;
  followers?: number;
  bio?: string;
  image?: string | null;
  needsAiPortrait?: boolean;
  createUrl?: string;
  editUrl?: string;
  destroyUrl?: string;
  generateImagePromptUrl?: string;
}

export default function Authors() {
  const { authors = [] } = usePage<{ authors?: Author[] }>().props;
  const [showView, setShowView] = useState(false);
  const [selectedAuthor, setSelectedAuthor] = useState<Author | null>(null);
  const pagination = useClientPagination(authors, 24);

  const destroy = (author: Author) => {
    if (!author.destroyUrl || !confirm(`${author.name} muallifini o'chirasizmi?`)) return;
    router.delete(author.destroyUrl, { preserveScroll: true });
  };

  const generatePrompt = (author: Author) => {
    if (!author.generateImagePromptUrl) return;
    router.post(author.generateImagePromptUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Mualliflar</h1>
          <p className="page-subtitle">Jami {authors.length} ta muallif</p>
        </div>
      </div>

      <div className="row g-3">
        {pagination.paginated.map((author, i) => (
          <div className="col-xl-4 col-md-6" key={author.id}>
            <div className="card-panel h-100 d-flex flex-column justify-content-between">
              <div>
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div className="resource-avatar" style={{ background: `linear-gradient(135deg, hsl(${i * 47},70%,58%), hsl(${i * 47 + 35},70%,48%))` }}>
                    {author.image ? <img src={author.image} alt={author.name} /> : author.name.split(' ').map(n => n[0]).join('').slice(0, 2)}
                  </div>
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div className="fw-bold text-truncate">{author.name}</div>
                    <div className="text-muted small mt-1 text-truncate">{author.bio || 'Muallif katalogi'}</div>
                  </div>
                </div>
                <div className="d-flex justify-content-around pt-3 border-top text-center">
                  <div><div className="fw-bold text-primary" style={{ fontSize: 20 }}>{author.books}</div><small className="text-muted">Kitob</small></div>
                  <div><div className="fw-bold text-danger" style={{ fontSize: 20 }}>{fmt(author.followers || 0)}</div><small className="text-muted">Obunachi</small></div>
                  <div><div className="fw-bold text-success" style={{ fontSize: 20 }}>{author.needsAiPortrait ? 'AI' : 'OK'}</div><small className="text-muted">Rasm</small></div>
                </div>
              </div>
              <div className="d-flex gap-2 mt-3 pt-2">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelectedAuthor(author); setShowView(true); }}><i className="bi bi-eye"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(author)} disabled={author.books > 0}><i className="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        ))}
      </div>
      <PaginationControls {...pagination} onPageChange={pagination.setPage} />

      <Modal show={showView} onHide={() => setShowView(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Muallif profili</Modal.Title></Modal.Header>
        <Modal.Body className="text-center">
          <div className="resource-avatar mx-auto mb-3" style={{ width: 92, height: 92, fontSize: 32 }}>
            {selectedAuthor?.image ? <img src={selectedAuthor.image} alt={selectedAuthor.name} /> : selectedAuthor?.name.split(' ').map(n => n[0]).join('').slice(0, 2)}
          </div>
          <h4 className="fw-bold mt-3 mb-1">{selectedAuthor?.name}</h4>
          <p className="bg-light p-3 rounded text-start text-muted small mb-4">{selectedAuthor?.bio || 'Maʼlumot yoq'}</p>
          <div className="row g-2 border-top pt-3 text-start">
            <div className="col-6"><span className="text-muted small">Bog'langan kitoblar:</span></div>
            <div className="col-6 fw-semibold text-primary">{selectedAuthor?.books} ta</div>
            <div className="col-6"><span className="text-muted small">AI portret kerak:</span></div>
            <div className="col-6 fw-semibold">{selectedAuthor?.needsAiPortrait ? 'Ha' : "Yo'q"}</div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selectedAuthor?.needsAiPortrait ? <Button variant="outline-secondary" onClick={() => generatePrompt(selectedAuthor)}>AI prompt</Button> : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
