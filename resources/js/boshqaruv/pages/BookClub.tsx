import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';

interface Post {
  id: number;
  author: string;
  avatar?: string | null;
  text?: string;
  productType?: string;
  productId?: number;
  repost: boolean;
  likes: number;
  comments: number;
  aiStatus?: string;
  aiScore?: number;
  aiNote?: string;
  warning: boolean;
  date?: string;
  dataUrl?: string;
  warnUrl?: string;
  destroyUrl?: string;
}

type Person = { id?: number; userId?: number; name: string; phone?: string; avatar?: string | null; date?: string };
type CommentRow = Person & {
  content: string;
  likes?: number;
  repliesCount?: number;
  replies?: Array<{ id: number; name: string; content: string; date?: string; destroyUrl?: string }>;
  aiStatus?: string;
  aiScore?: number | null;
  aiNote?: string | null;
  hiddenByAi?: boolean;
  moderationStatus?: string | null;
  moderationNote?: string | null;
  kangarooStatus?: string | null;
  updateUrl?: string;
  destroyUrl?: string;
};
type ProductDetail = {
  id: number;
  type: string;
  name: string;
  seller?: string;
  views?: number;
  viewLogs?: number;
  aiScore?: number | null;
  reviewsCount?: number;
  scoredAt?: string | null;
  recentViews?: Array<{ id: number; user: string; phone?: string; device?: string; recommended?: boolean; date?: string }>;
  comments?: Array<{ id: number; postId?: number; name: string; content: string; aiScore?: number | null; aiStatus?: string | null; date?: string }>;
};
type PostDetail = {
  post: Post & { phone?: string; originalAuthor?: string | null; aiModel?: string; aiCheckedAt?: string; warning?: { note?: string; date?: string } | null; images?: string[] };
  stats: { likes: number; comments: number; reposts: number; votes: number };
  product?: ProductDetail | null;
  comments: CommentRow[];
  likers: Person[];
  reposters: Person[];
  votes: Array<{ id: number; text: string; count: number; percent: number }>;
  actions: { warnUrl?: string; destroyUrl?: string };
};

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

export default function BookClub() {
  const { bookClubPosts = [] } = usePage<{ bookClubPosts?: Post[] }>().props;
  const [selectedPost, setSelectedPost] = useState<Post | null>(null);
  const [detail, setDetail] = useState<PostDetail | null>(null);
  const [loading, setLoading] = useState(false);

  const openDetail = async (post: Post) => {
    setSelectedPost(post);
    setDetail(null);
    if (!post.dataUrl) return;
    setLoading(true);
    try {
      const response = await fetch(post.dataUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (!response.ok) throw new Error('Post tafsilotini yuklab bo‘lmadi');
      setDetail(await response.json());
    } finally {
      setLoading(false);
    }
  };

  const reloadDetail = () => selectedPost && openDetail(selectedPost);

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const focusPostId = Number(new URLSearchParams(window.location.search).get('focus_post') || 0);
    if (!focusPostId) return;

    const existing = bookClubPosts.find((post) => post.id === focusPostId);

    if (existing) {
      openDetail(existing);
      return;
    }

    openDetail({
      id: focusPostId,
      author: 'Book Club post',
      repost: false,
      likes: 0,
      comments: 0,
      warning: false,
      dataUrl: `/boshqaruv/book-club/${focusPostId}/data`,
    });
  }, []);

  const destroy = (post: Post) => {
    if (!post.destroyUrl || !confirm(`#${post.id} post o'chirilsinmi?`)) return;
    router.delete(post.destroyUrl, { preserveScroll: true, onSuccess: () => setSelectedPost(null) });
  };

  const warn = (post: Post) => {
    const note = prompt('Ogohlantirish izohi');
    if (!note || !post.warnUrl) return;
    router.post(post.warnUrl, { note }, { preserveScroll: true, onSuccess: reloadDetail });
  };

  const editComment = (comment: CommentRow) => {
    const content = prompt('Izoh matni', comment.content);
    if (!content || !comment.updateUrl) return;
    router.patch(comment.updateUrl, { content }, { preserveScroll: true, onSuccess: reloadDetail });
  };

  const deleteComment = (url?: string, label = "Izoh o'chirilsinmi?") => {
    if (!url || !confirm(label)) return;
    router.delete(url, { preserveScroll: true, onSuccess: reloadDetail });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Book Club postlari</h1>
          <p className="page-subtitle">Postlar, like bosganlar, commentlar, product view va AI baholash nazorati</p>
        </div>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          {bookClubPosts.map((post, index) => (
            <div className="card-panel mb-3" key={post.id}>
              <div className="d-flex align-items-start gap-3">
                <Avatar name={post.author} src={post.avatar} seed={index} />
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div className="d-flex align-items-center gap-2 flex-wrap">
                    <span className="fw-semibold">{post.author}</span>
                    <span className="text-muted small">· {post.date || '—'}</span>
                    <span className={`chip ${post.repost ? 'chip-purple' : 'chip-info'}`}>{post.repost ? 'Repost' : 'Post'}</span>
                    {post.productType ? <span className="chip chip-gray">{post.productType} #{post.productId || '—'}</span> : null}
                    {post.warning ? <span className="chip chip-danger">Warning</span> : null}
                  </div>
                  <p className="text-muted my-3" style={{ cursor: 'pointer', whiteSpace: 'pre-line' }} onClick={() => openDetail(post)}>
                    {(post.text || '').slice(0, 260) || 'Matn yo‘q'}
                  </p>
                  <div className="d-flex align-items-center gap-2 flex-wrap">
                    <span className="btn btn-sm btn-light"><i className="bi bi-heart-fill text-danger"></i> {post.likes}</span>
                    <button className="btn btn-sm btn-light" onClick={() => openDetail(post)}><i className="bi bi-chat"></i> {post.comments}</button>
                    <span className={`chip ${post.aiStatus === 'scored' ? 'chip-success' : post.aiStatus === 'failed' ? 'chip-danger' : 'chip-gray'}`}>AI: {post.aiStatus || '—'} {post.aiScore ?? ''}</span>
                    <button className="btn btn-sm btn-light ms-auto" onClick={() => openDetail(post)} title="Tafsilot"><i className="bi bi-eye"></i></button>
                    <button className="btn btn-sm btn-light" onClick={() => warn(post)} title="Ogohlantirish"><i className="bi bi-flag"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(post)} title="O'chirish"><i className="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="col-xl-4">
          <div className="card-panel mb-3">
            <div className="panel-title mb-3">Hamjamiyat statistikasi</div>
            <Stat label="Jami postlar" value={bookClubPosts.length} />
            <Stat label="Izohlar" value={bookClubPosts.reduce((acc, post) => acc + post.comments, 0)} />
            <Stat label="Like" value={bookClubPosts.reduce((acc, post) => acc + post.likes, 0)} />
            <Stat label="Ogohlantirishli postlar" value={bookClubPosts.filter(post => post.warning).length} tone="warning" />
          </div>
        </div>
      </div>

      <Modal show={!!selectedPost} onHide={() => { setSelectedPost(null); setDetail(null); }} centered size="xl" scrollable>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">Post #{selectedPost?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loading ? <div className="text-center text-muted py-5">Post tafsiloti yuklanmoqda...</div> : !detail ? (
            <div className="text-muted">Tafsilot topilmadi.</div>
          ) : (
            <div className="row g-3">
              <div className="col-xl-8">
                <div className="detail-panel">
                  <div className="d-flex align-items-center gap-3 mb-3">
                    <Avatar name={detail.post.author} src={detail.post.avatar} />
                    <div>
                      <div className="fw-bold">{detail.post.author}</div>
                      <div className="text-muted small">{detail.post.phone || 'Telefon yo‘q'} · {detail.post.date || '—'}</div>
                    </div>
                    {detail.post.repost ? <span className="chip chip-purple ms-auto">Repost: {detail.post.originalAuthor || '—'}</span> : null}
                  </div>
                  <div className="detail-text" style={{ whiteSpace: 'pre-line' }}>{detail.post.text || 'Matn yo‘q'}</div>
                  {detail.post.images?.length ? (
                    <div className="d-flex flex-wrap gap-2 mt-3">
                      {detail.post.images.map((image) => <img key={image} className="thumb" src={image} alt="" />)}
                    </div>
                  ) : null}
                </div>

                <div className="detail-panel mt-3">
                  <h6 className="fw-bold mb-3">AI baholash</h6>
                  <div className="row g-3">
                    <Info label="Post holati" value={detail.post.aiStatus || '—'} />
                    <Info label="Post bahosi" value={detail.post.aiScore !== null && detail.post.aiScore !== undefined ? `${detail.post.aiScore} / 5` : '—'} />
                    <Info label="Model" value={detail.post.aiModel || '—'} />
                    <Info label="Tekshirilgan" value={detail.post.aiCheckedAt || '—'} />
                  </div>
                  {detail.post.aiNote ? <div className="text-muted mt-3">{detail.post.aiNote}</div> : null}
                  {detail.post.warning ? <div className="alert alert-warning mt-3 mb-0">{detail.post.warning.note || 'Ogohlantirish bor'}</div> : null}
                </div>

                <ProductPanel product={detail.product} />

                <div className="detail-panel mt-3">
                  <div className="d-flex align-items-center justify-content-between mb-3">
                    <h6 className="fw-bold mb-0">Commentlar</h6>
                    <span className="chip chip-info">{detail.stats.comments} ta</span>
                  </div>
                  <div className="d-grid gap-3">
                    {detail.comments.map((comment) => (
                      <CommentCard key={comment.id} comment={comment} onEdit={() => editComment(comment)} onDelete={deleteComment} />
                    ))}
                    {detail.comments.length === 0 ? <div className="text-muted">Comment yo‘q</div> : null}
                  </div>
                </div>
              </div>

              <div className="col-xl-4">
                <div className="detail-panel">
                  <h6 className="fw-bold mb-3">Statistika</h6>
                  <Stat label="Like" value={detail.stats.likes} />
                  <Stat label="Comment" value={detail.stats.comments} />
                  <Stat label="Repost" value={detail.stats.reposts} />
                  <Stat label="Ovoz" value={detail.stats.votes} />
                </div>
                <PeoplePanel title="Like bosganlar" people={detail.likers} icon="bi-heart-fill" />
                <PeoplePanel title="Repost qilganlar" people={detail.reposters} icon="bi-repeat" />
                {detail.votes.length ? (
                  <div className="detail-panel mt-3">
                    <h6 className="fw-bold mb-3">So‘rovnoma</h6>
                    {detail.votes.map((vote) => (
                      <div className="mb-2" key={vote.id}>
                        <div className="d-flex justify-content-between small"><strong>{vote.text}</strong><span>{vote.count} · {vote.percent}%</span></div>
                        <div className="progress" style={{ height: 6 }}><div className="progress-bar" style={{ width: `${vote.percent}%` }} /></div>
                      </div>
                    ))}
                  </div>
                ) : null}
              </div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          {detail ? <Button variant="outline-warning" onClick={() => warn(detail.post)}>Ogohlantirish</Button> : null}
          {detail ? <Button variant="outline-danger" onClick={() => destroy(detail.post)}>O'chirish</Button> : null}
          <Button variant="light" onClick={() => { setSelectedPost(null); setDetail(null); }}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

function Avatar({ name, src, seed = 1 }: { name: string; src?: string | null; seed?: number }) {
  return (
    <div style={{ width: 48, height: 48, borderRadius: '50%', background: `linear-gradient(135deg, hsl(${seed * 70},70%,60%), hsl(${seed * 70 + 40},70%,50%))`, color: 'white', display: 'grid', placeItems: 'center', fontWeight: 700, flexShrink: 0, overflow: 'hidden' }}>
      {src ? <img src={src} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} /> : name.split(' ').map(n => n[0]).join('').slice(0, 2)}
    </div>
  );
}

function Stat({ label, value, tone }: { label: string; value: number; tone?: 'warning' }) {
  return <div className="mb-3"><div className="text-muted small">{label}</div><div className={`fw-bold fs-4 ${tone === 'warning' ? 'text-warning' : ''}`}>{fmt(value)}</div></div>;
}

function Info({ label, value }: { label: string; value?: string | number | null }) {
  return <div className="col-md-6"><small className="text-muted">{label}</small><div className="fw-semibold">{value || '—'}</div></div>;
}

function PeoplePanel({ title, people, icon }: { title: string; people: Person[]; icon: string }) {
  return (
    <div className="detail-panel mt-3">
      <h6 className="fw-bold mb-3"><i className={`bi ${icon} me-1`}></i>{title}</h6>
      <div className="d-grid gap-2">
        {people.map((person, index) => (
          <div className="d-flex align-items-center gap-2 border-bottom pb-2" key={`${person.id || person.userId}-${index}`}>
            <Avatar name={person.name} src={person.avatar} seed={index} />
            <div style={{ minWidth: 0 }}>
              <div className="fw-semibold text-truncate">{person.name}</div>
              <small className="text-muted">{person.phone || person.date || '—'}</small>
            </div>
          </div>
        ))}
        {people.length === 0 ? <div className="text-muted">Ma’lumot yo‘q</div> : null}
      </div>
    </div>
  );
}

function CommentCard({ comment, onEdit, onDelete }: { comment: CommentRow; onEdit: () => void; onDelete: (url?: string, label?: string) => void }) {
  return (
    <div className="p-3 rounded border">
      <div className="d-flex align-items-start gap-2">
        <Avatar name={comment.name} src={comment.avatar} />
        <div style={{ flex: 1, minWidth: 0 }}>
          <div className="d-flex align-items-center gap-2 flex-wrap">
            <strong>{comment.name}</strong>
            <small className="text-muted">{comment.date || '—'}</small>
            <span className="chip chip-gray"><i className="bi bi-heart-fill text-danger me-1"></i>{comment.likes || 0}</span>
            {comment.hiddenByAi ? <span className="chip chip-danger">AI yashirgan</span> : null}
          </div>
          <div className="text-muted mt-2" style={{ whiteSpace: 'pre-line' }}>{comment.content}</div>
          <div className="d-flex gap-2 flex-wrap mt-2">
            <span className="chip chip-info">AI: {comment.aiStatus || '—'} {comment.aiScore ?? ''}</span>
            {comment.moderationStatus ? <span className="chip chip-warning">{comment.moderationStatus}</span> : null}
            {comment.kangarooStatus ? <span className="chip chip-purple">{comment.kangarooStatus}</span> : null}
            <button className="btn btn-sm btn-light ms-auto" onClick={onEdit}><i className="bi bi-pencil"></i></button>
            <button className="btn btn-sm btn-light text-danger" onClick={() => onDelete(comment.destroyUrl)}><i className="bi bi-trash"></i></button>
          </div>
          {comment.aiNote || comment.moderationNote ? <div className="small text-muted mt-2">{comment.aiNote || comment.moderationNote}</div> : null}
          {comment.replies?.length ? (
            <div className="mt-3 ps-3 border-start d-grid gap-2">
              {comment.replies.map((reply) => (
                <div key={reply.id} className="small">
                  <div className="d-flex justify-content-between gap-2"><strong>{reply.name}</strong><span className="text-muted">{reply.date || '—'}</span></div>
                  <div className="text-muted">{reply.content}</div>
                  <button className="btn btn-sm btn-light text-danger mt-1" onClick={() => onDelete(reply.destroyUrl, "Javob o'chirilsinmi?")}><i className="bi bi-trash"></i></button>
                </div>
              ))}
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}

function ProductPanel({ product }: { product?: ProductDetail | null }) {
  if (!product) return null;
  return (
    <div className="detail-panel mt-3">
      <div className="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h6 className="fw-bold mb-1">Bog‘langan product</h6>
          <div className="text-muted small">{product.type} #{product.id} · {product.seller || 'Seller yo‘q'}</div>
        </div>
        <span className="chip chip-info">{product.name}</span>
      </div>
      <div className="row g-3 mb-3">
        <Info label="Product view" value={`${fmt(product.views || 0)} / log ${fmt(product.viewLogs || 0)}`} />
        <Info label="AI product bahosi" value={product.aiScore !== null && product.aiScore !== undefined ? `${product.aiScore} / 5` : '—'} />
        <Info label="User commentlari" value={fmt(product.reviewsCount || 0)} />
        <Info label="So‘nggi baholash" value={product.scoredAt || '—'} />
      </div>
      <div className="row g-3">
        <div className="col-lg-6">
          <div className="fw-bold mb-2">Recent viewlar</div>
          <div className="d-grid gap-2">
            {(product.recentViews || []).map((view) => (
              <div className="mini-stat" key={view.id}>
                <strong>{view.user}</strong>
                <span>{view.phone || view.device || 'Mehmon'} · {view.date || '—'} {view.recommended ? '· recommendation' : ''}</span>
              </div>
            ))}
            {(product.recentViews || []).length === 0 ? <div className="text-muted small">View log topilmadi</div> : null}
          </div>
        </div>
        <div className="col-lg-6">
          <div className="fw-bold mb-2">Product commentlari</div>
          <div className="d-grid gap-2">
            {(product.comments || []).map((comment) => (
              <div className="mini-stat" key={comment.id}>
                <strong>{comment.name}</strong>
                <span>{comment.content}</span>
                <span>AI: {comment.aiStatus || '—'} {comment.aiScore ?? ''} · {comment.date || '—'}</span>
              </div>
            ))}
            {(product.comments || []).length === 0 ? <div className="text-muted small">Product bo‘yicha comment topilmadi</div> : null}
          </div>
        </div>
      </div>
    </div>
  );
}
