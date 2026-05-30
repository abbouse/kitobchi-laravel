import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

interface Post {
  id: number;
  author: string;
  text?: string;
  productType?: string;
  repost: boolean;
  likes: number;
  comments: number;
  aiStatus?: string;
  aiScore?: number;
  warning: boolean;
  date?: string;
  showUrl?: string;
  editUrl?: string;
  warnUrl?: string;
  destroyUrl?: string;
}

export default function BookClub() {
  const { bookClubPosts = [] } = usePage<{ bookClubPosts?: Post[] }>().props;
  const [selectedPost, setSelectedPost] = useState<Post | null>(null);

  const destroy = (post: Post) => {
    if (!post.destroyUrl || !confirm(`#${post.id} post o'chirilsinmi?`)) return;
    router.delete(post.destroyUrl, { preserveScroll: true });
  };

  const warn = (post: Post) => {
    const note = prompt('Ogohlantirish izohi');
    if (!note || !post.warnUrl) return;
    router.post(post.warnUrl, { note }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Book Club postlari</h1>
          <p className="page-subtitle">O'quvchilar hamjamiyati, AI moderation va shikoyat nazorati</p>
        </div>
        <a className="btn btn-outline-secondary" href="/a122/book-club">Eski filtr</a>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          {bookClubPosts.map((post, index) => (
            <div className="card-panel mb-3" key={post.id}>
              <div className="d-flex align-items-start gap-3">
                <div style={{ width: 48, height: 48, borderRadius: '50%', background: `linear-gradient(135deg, hsl(${index * 70},70%,60%), hsl(${index * 70 + 40},70%,50%))`, color: 'white', display: 'grid', placeItems: 'center', fontWeight: 700, flexShrink: 0 }}>
                  {post.author.split(' ').map(n => n[0]).join('').slice(0, 2)}
                </div>
                <div style={{ flex: 1 }}>
                  <div className="d-flex align-items-center gap-2 flex-wrap">
                    <span className="fw-semibold">{post.author}</span>
                    <span className="text-muted small">· {post.date || '—'}</span>
                    <span className={`chip ${post.repost ? 'chip-purple' : 'chip-info'}`}>{post.repost ? 'Repost' : 'Post'}</span>
                    {post.warning ? <span className="chip chip-danger">Warning</span> : null}
                  </div>
                  <p className="text-muted my-3" style={{ cursor: 'pointer' }} onClick={() => setSelectedPost(post)}>
                    {(post.text || '').slice(0, 260) || 'Matn yo‘q'}
                  </p>
                  <div className="d-flex align-items-center gap-3 flex-wrap">
                    <span className="btn btn-sm btn-light"><i className="bi bi-heart-fill text-danger"></i> {post.likes}</span>
                    <button className="btn btn-sm btn-light" onClick={() => setSelectedPost(post)}><i className="bi bi-chat"></i> {post.comments}</button>
                    <span className="chip chip-gray">AI: {post.aiStatus || '—'} {post.aiScore ?? ''}</span>
                    {post.editUrl ? <a className="btn btn-sm btn-light ms-auto" href={post.editUrl}><i className="bi bi-pencil"></i></a> : null}
                    <button className="btn btn-sm btn-light" onClick={() => warn(post)}><i className="bi bi-flag"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(post)}><i className="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="col-xl-4">
          <div className="card-panel mb-3">
            <div className="panel-title mb-3">Hamjamiyat statistikasi</div>
            <div className="mb-3"><div className="text-muted small">Jami postlar</div><div className="fw-bold fs-4">{bookClubPosts.length}</div></div>
            <div className="mb-3"><div className="text-muted small">Izohlar</div><div className="fw-bold fs-4">{bookClubPosts.reduce((acc, post) => acc + post.comments, 0)}</div></div>
            <div><div className="text-muted small">Ogohlantirishli postlar</div><div className="fw-bold fs-4 text-warning">{bookClubPosts.filter(post => post.warning).length}</div></div>
          </div>
        </div>
      </div>

      <Modal show={!!selectedPost} onHide={() => setSelectedPost(null)} centered size="lg">
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Post #{selectedPost?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="d-flex align-items-center gap-2 mb-3">
            <span className="fw-bold">{selectedPost?.author}</span>
            <span className="text-muted small">· {selectedPost?.date}</span>
          </div>
          <p className="text-muted mb-4" style={{ lineHeight: 1.6 }}>{selectedPost?.text || 'Matn yo‘q'}</p>
          <div className="row g-2">
            <div className="col-4"><small className="text-muted">Like</small><div className="fw-bold">{selectedPost?.likes || 0}</div></div>
            <div className="col-4"><small className="text-muted">Izoh</small><div className="fw-bold">{selectedPost?.comments || 0}</div></div>
            <div className="col-4"><small className="text-muted">AI</small><div className="fw-bold">{selectedPost?.aiStatus || '—'}</div></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selectedPost?.showUrl ? <a className="btn btn-primary-gradient" href={selectedPost.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setSelectedPost(null)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
