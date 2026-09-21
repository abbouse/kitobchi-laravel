import { toneOf, toneBadge } from '../utils/tone';
import { PageCrumbs } from '../Layout';
import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
import { tiIcon } from '../utils/icons';

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
  hiddenByAi?: boolean;
  moderationStatus?: string | null;
  moderationNote?: string | null;
  moderationUrl?: string;
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
  updateUrl?: string;
  destroyUrl?: string;
  moderationUrl?: string;
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
  post: Post & { phone?: string; originalAuthor?: string | null; aiModel?: string; aiCheckedAt?: string; moderationModel?: string; moderatedAt?: string; warning?: { note?: string; date?: string } | null; images?: string[] };
  stats: { likes: number; comments: number; reposts: number; votes: number };
  product?: ProductDetail | null;
  comments: CommentRow[];
  likers: Person[];
  reposters: Person[];
  votes: Array<{ id: number; text: string; count: number; percent: number }>;
  actions: { warnUrl?: string; destroyUrl?: string };
};

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

// AI moderatsiya statusi endi FAQAT tavsiya ma'nosini bildiradi — 2026-09
// dan boshlab AI postni/kommentariyani o'zi yashira olmaydi (buni faqat
// admin qila oladi, quyidagi hiddenByAi/manual_* qiymatlari orqali).
// 'ai_flagged'/'ai_clean' — AI tavsiyasi (eski 'hidden'/'clean' nomlari
// o'rniga, chalkashlikni oldini olish uchun); 'manual_hidden'/'manual_clean'
// — admin qo'lda qabul qilgan qaror; eski ma'lumotlarda hali ham
// 'hidden'/'clean' uchrashi mumkin (migratsiyadan oldingi holat).
function moderationLabel(status?: string | null): string {
  switch (status) {
    case 'ai_flagged':
    case 'hidden':
      return 'AI tavsiyasi: yashirish';
    case 'ai_clean':
    case 'clean':
      return 'AI tavsiyasi: toza';
    case 'manual_hidden':
      return 'Admin yashirgan';
    case 'manual_clean':
      return 'Admin tozalagan';
    case 'pending':
    case undefined:
    case null:
    case '':
      return 'Kutilmoqda';
    default:
      return status;
  }
}

function moderationChipClass(status?: string | null): string {
  switch (status) {
    case 'ai_flagged':
    case 'hidden':
      return 'text-light-warning';
    case 'ai_clean':
    case 'clean':
    case 'manual_clean':
      return 'text-light-success';
    case 'manual_hidden':
      return 'text-light-danger';
    default:
      return 'text-light-secondary';
  }
}

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

  const setModeration = (url: string | undefined, hidden: boolean) => {
    if (!url) return;
    router.patch(url, { action: hidden ? 'hide' : 'show' }, { preserveScroll: true, onSuccess: reloadDetail });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Book Club postlari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Postlar, like bosganlar, commentlar, product view va AI baholash nazorati</p>
        </div>
      </div>

      <div className="row">
        <div className="col-xl-8">
          {bookClubPosts.map((post, index) => (
            <div className="card" key={post.id}>
<div className="card-body">
                <div className="d-flex align-items-start gap-3">
                  <Avatar name={post.author} src={post.avatar} seed={index} />
                  <div className="min-w-0" style={{ flex: 1 }}>
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                      <span className="f-w-600">{post.author}</span>
                      <span className="text-muted f-s-13">· {post.date || '—'}</span>
                      <span className={`badge ${post.repost ? 'text-light-primary' : 'text-light-info'}`}>{post.repost ? 'Repost' : 'Post'}</span>
                      {post.productType ? <span className="badge text-light-secondary">{post.productType} #{post.productId || '—'}</span> : null}
                      {post.warning ? <span className="badge text-light-danger">Warning</span> : null}
                      {post.hiddenByAi ? <span className="badge text-light-danger">Yashirilgan</span> : null}
                    </div>
                    <p className="text-muted my-3 cursor-pointer" style={{ whiteSpace: 'pre-line' }} onClick={() => openDetail(post)}>
                      {(post.text || '').slice(0, 260) || 'Matn yo‘q'}
                    </p>
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                      <span className="btn btn-sm btn-light-secondary"><i className="ti ti-heart-filled text-danger"></i> {post.likes}</span>
                      <button className="btn btn-sm btn-light-secondary" onClick={() => openDetail(post)}><i className="ti ti-message"></i> {post.comments}</button>
                      <span className={`badge ${post.aiStatus === 'scored' ? 'text-light-success' : post.aiStatus === 'failed' ? 'text-light-danger' : 'text-light-secondary'}`}>AI: {post.aiStatus || '—'} {post.aiScore ?? ''}</span>
                      <span className={`badge text-uppercase ${toneBadge(toneOf(moderationChipClass(post.moderationStatus)))}`}>Moderatsiya: {moderationLabel(post.moderationStatus)}</span>
                      <button className="btn btn-light-primary icon-btn w-30 h-30 b-r-22 ms-auto" onClick={() => openDetail(post)} title="Tafsilot"><i className="ti ti-eye"></i></button>
                      <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" onClick={() => warn(post)} title="Ogohlantirish"><i className="ti ti-flag"></i></button>
                      <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(post)} title="O'chirish"><i className="ti ti-trash"></i></button>
                    </div>
                  </div>
                </div>
              </div>
</div>
          ))}
        </div>

        <div className="col-xl-4">
          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">Hamjamiyat statistikasi</h5>
            </div>
            <div className="card-body">

              <Stat label="Jami postlar" value={bookClubPosts.length} />
              <Stat label="Izohlar" value={bookClubPosts.reduce((acc, post) => acc + post.comments, 0)} />
              <Stat label="Like" value={bookClubPosts.reduce((acc, post) => acc + post.likes, 0)} />
              <Stat label="Ogohlantirishli postlar" value={bookClubPosts.filter(post => post.warning).length} tone="warning" />
            </div>
          </div>
        </div>
      </div>

      <Modal show={!!selectedPost} onHide={() => { setSelectedPost(null); setDetail(null); }} centered size="xl" scrollable>
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">Post #{selectedPost?.id}</Modal.Title></Modal.Header>
        <Modal.Body>
          {loading ? <div className="text-center text-muted py-5">Post tafsiloti yuklanmoqda...</div> : !detail ? (
            <div className="text-muted">Tafsilot topilmadi.</div>
          ) : (
            <div className="row">
              <div className="col-xl-8">
                <div className="card"><div className="card-body">
                    <div className="d-flex align-items-center gap-3 mb-3">
                      <Avatar name={detail.post.author} src={detail.post.avatar} />
                      <div>
                        <div className="f-w-600">{detail.post.author}</div>
                        <div className="text-muted f-s-13">{detail.post.phone || 'Telefon yo‘q'} · {detail.post.date || '—'}</div>
                      </div>
                      {detail.post.repost ? <span className="badge text-light-primary ms-auto">Repost: {detail.post.originalAuthor || '—'}</span> : null}
                    </div>
                    <div className="text-break" style={{ whiteSpace: 'pre-line' }}>{detail.post.text || 'Matn yo‘q'}</div>
                    {detail.post.images?.length ? (
                      <div className="d-flex flex-wrap gap-2 mt-3">
                        {detail.post.images.map((image) => <img key={image} className="w-40 h-55 b-r-10 object-fit-cover flex-shrink-0" src={image} alt="" />)}
                      </div>
                    ) : null}
                  </div></div>

                <div className="card"><div className="card-header"><h5 className="mb-0">AI baholash</h5></div><div className="card-body">
                    <div className="row g-3">
                      <Info label="Post holati" value={detail.post.aiStatus || '—'} />
                      <Info label="Post bahosi" value={detail.post.aiScore !== null && detail.post.aiScore !== undefined ? `${detail.post.aiScore} / 5` : '—'} />
                      <Info label="Model" value={detail.post.aiModel || '—'} />
                      <Info label="Tekshirilgan" value={detail.post.aiCheckedAt || '—'} />
                    </div>
                    {detail.post.aiNote ? <div className="text-muted mt-3">{detail.post.aiNote}</div> : null}
                    <div className="row g-3 mt-1">
                      <Info label="Moderatsiya" value={moderationLabel(detail.post.moderationStatus)} />
                      <Info label="Moderatsiya modeli" value={detail.post.moderationModel || '—'} />
                      <Info label="Moderatsiya vaqti" value={detail.post.moderatedAt || '—'} />
                      <Info label="Ko‘rinish" value={detail.post.hiddenByAi ? 'Yashirilgan' : 'Ochiq'} />
                    </div>
                    {detail.post.moderationNote ? <div className="text-muted mt-3">{detail.post.moderationNote}</div> : null}
                    {detail.post.warning ? <div className="alert alert-light-warning mt-3 mb-0">{detail.post.warning.note || 'Ogohlantirish bor'}</div> : null}
                  </div></div>

                <ProductPanel product={detail.product} />

                <div className="card"><div className="card-header d-flex align-items-center justify-content-between">
                    <h5 className="mb-0">Commentlar</h5>
                    <span className="badge text-light-info">{detail.stats.comments} ta</span>
                  </div><div className="card-body">
                    <div className="d-grid gap-3">
                      {detail.comments.map((comment) => (
                        <CommentCard key={comment.id} comment={comment} onEdit={() => editComment(comment)} onDelete={deleteComment} onModerate={setModeration} />
                      ))}
                      {detail.comments.length === 0 ? <div className="text-muted">Comment yo‘q</div> : null}
                    </div>
                  </div></div>
              </div>

              <div className="col-xl-4">
                <div className="card"><div className="card-header"><h5 className="mb-0">Statistika</h5></div><div className="card-body">
                    <Stat label="Like" value={detail.stats.likes} />
                    <Stat label="Comment" value={detail.stats.comments} />
                    <Stat label="Repost" value={detail.stats.reposts} />
                    <Stat label="Ovoz" value={detail.stats.votes} />
                  </div></div>
                <PeoplePanel title="Like bosganlar" people={detail.likers} icon="ti-heart-filled" />
                <PeoplePanel title="Repost qilganlar" people={detail.reposters} icon="ti-repeat" />
                {detail.votes.length ? (
                  <div className="card"><div className="card-header"><h5 className="mb-0">So‘rovnoma</h5></div><div className="card-body">
                      {detail.votes.map((vote) => (
                        <div className="mb-2" key={vote.id}>
                          <div className="d-flex justify-content-between f-s-13"><strong>{vote.text}</strong><span>{vote.count} · {vote.percent}%</span></div>
                          <div className="progress" style={{ height: 6 }}><div className="progress-bar" style={{ width: `${vote.percent}%` }} /></div>
                        </div>
                      ))}
                    </div></div>
                ) : null}
              </div>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          {detail ? <Button variant="outline-warning" onClick={() => warn(detail.post)}>Ogohlantirish</Button> : null}
          {detail ? <Button variant={detail.post.hiddenByAi ? 'outline-success' : 'outline-secondary'} onClick={() => setModeration(detail.post.moderationUrl, !detail.post.hiddenByAi)}>{detail.post.hiddenByAi ? 'Qayta ochish' : 'Yashirish'}</Button> : null}
          {detail ? <Button variant="outline-danger" onClick={() => destroy(detail.post)}>O'chirish</Button> : null}
          <Button variant="light-secondary" onClick={() => { setSelectedPost(null); setDetail(null); }}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}

function Avatar({ name, src, seed = 1 }: { name: string; src?: string | null; seed?: number }) {
  return (
    <div className="d-flex-center b-r-50 bg-light-primary f-w-600 overflow-hidden flex-shrink-0 f-s-15 w-45 h-45">
      {src ? <img className="w-100 h-100 object-fit-cover" src={src} alt="" /> : name.split(' ').map(n => n[0]).join('').slice(0, 2)}
    </div>
  );
}

function Stat({ label, value, tone }: { label: string; value: number; tone?: 'warning' }) {
  return <div className="mb-3"><div className="text-muted f-s-13">{label}</div><div className={`f-w-600 f-s-24 ${tone === 'warning' ? 'text-warning' : ''}`}>{fmt(value)}</div></div>;
}

function Info({ label, value }: { label: string; value?: string | number | null }) {
  return <div className="col-md-6"><small className="text-muted">{label}</small><div className="f-w-600">{value || '—'}</div></div>;
}

function PeoplePanel({ title, people, icon }: { title: string; people: Person[]; icon: string }) {
  return (
    <div className="card"><div className="card-header"><h5 className="mb-0"><i className={`${tiIcon(icon)} me-1`}></i>{title}</h5></div><div className="card-body">
        <div className="d-grid gap-2">
          {people.map((person, index) => (
            <div className="d-flex align-items-center gap-2 b-b-1-light pb-2" key={`${person.id || person.userId}-${index}`}>
              <Avatar name={person.name} src={person.avatar} seed={index} />
              <div className="min-w-0">
                <div className="f-w-600 text-truncate">{person.name}</div>
                <p className="mb-0 text-secondary">{person.phone || person.date || '—'}</p>
              </div>
            </div>
          ))}
          {people.length === 0 ? <div className="text-muted">Ma’lumot yo‘q</div> : null}
        </div>
      </div></div>
  );
}

function CommentCard({ comment, onEdit, onDelete, onModerate }: { comment: CommentRow; onEdit: () => void; onDelete: (url?: string, label?: string) => void; onModerate: (url: string | undefined, hidden: boolean) => void }) {
  return (
    <div className="p-3 b-r-8 b-1-light">
      <div className="d-flex align-items-start gap-2">
        <Avatar name={comment.name} src={comment.avatar} />
        <div className="min-w-0" style={{ flex: 1 }}>
          <div className="d-flex align-items-center gap-2 flex-wrap">
            <strong>{comment.name}</strong>
            <p className="mb-0 text-secondary">{comment.date || '—'}</p>
            <span className="badge text-light-secondary"><i className="ti ti-heart-filled text-danger me-1"></i>{comment.likes || 0}</span>
            {comment.hiddenByAi ? <span className="badge text-light-danger">AI yashirgan</span> : null}
          </div>
          <div className="text-muted mt-2" style={{ whiteSpace: 'pre-line' }}>{comment.content}</div>
          <div className="d-flex gap-2 flex-wrap mt-2">
            <span className="badge text-light-info">AI: {comment.aiStatus || '—'} {comment.aiScore ?? ''}</span>
            {comment.moderationStatus ? <span className={`badge text-uppercase ${toneBadge(toneOf(moderationChipClass(comment.moderationStatus)))}`}>{moderationLabel(comment.moderationStatus)}</span> : null}
            <button className={`btn btn-sm btn-light-secondary ${comment.hiddenByAi ? 'text-success' : 'text-secondary'}`} onClick={() => onModerate(comment.moderationUrl, !comment.hiddenByAi)} title={comment.hiddenByAi ? 'Qayta ochish' : 'Yashirish'}><i className={`ti ${comment.hiddenByAi ? 'ti-eye' : 'ti-eye-off'}`}></i></button>
            <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22 ms-auto" onClick={onEdit}><i className="ti ti-pencil"></i></button>
            <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => onDelete(comment.destroyUrl)}><i className="ti ti-trash"></i></button>
          </div>
          {comment.aiNote || comment.moderationNote ? <div className="f-s-13 text-muted mt-2">{comment.aiNote || comment.moderationNote}</div> : null}
          {comment.replies?.length ? (
            <div className="mt-3 ps-3 b-s-1-light d-grid gap-2">
              {comment.replies.map((reply) => (
                <div key={reply.id} className="f-s-13">
                  <div className="d-flex justify-content-between gap-2"><strong>{reply.name}</strong><span className="text-muted">{reply.date || '—'}</span></div>
                  <div className="text-muted">{reply.content}</div>
                  <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22 mt-1" onClick={() => onDelete(reply.destroyUrl, "Javob o'chirilsinmi?")}><i className="ti ti-trash"></i></button>
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
    <div className="b-1-light b-r-15 p-3 mt-3">
      <div className="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
          <h6 className="f-w-600 mb-1">Bog‘langan product</h6>
          <div className="text-muted f-s-13">{product.type} #{product.id} · {product.seller || 'Seller yo‘q'}</div>
        </div>
        <span className="badge text-light-info">{product.name}</span>
      </div>
      <div className="row g-3 mb-3">
        <Info label="Product view" value={`${fmt(product.views || 0)} / log ${fmt(product.viewLogs || 0)}`} />
        <Info label="AI product bahosi" value={product.aiScore !== null && product.aiScore !== undefined ? `${product.aiScore} / 5` : '—'} />
        <Info label="User commentlari" value={fmt(product.reviewsCount || 0)} />
        <Info label="So‘nggi baholash" value={product.scoredAt || '—'} />
      </div>
      <div className="row g-3">
        <div className="col-lg-6">
          <div className="f-w-600 mb-2">Recent viewlar</div>
          <div className="d-grid gap-2">
            {(product.recentViews || []).map((view) => (
              <div className="b-1-light b-r-15 p-3" key={view.id}>
                <strong className="d-block text-dark f-w-600 f-s-16">{view.user}</strong>
                <span className="d-block text-secondary f-s-13 f-w-500 mt-1">{view.phone || view.device || 'Mehmon'} · {view.date || '—'} {view.recommended ? '· recommendation' : ''}</span>
              </div>
            ))}
            {(product.recentViews || []).length === 0 ? <div className="text-muted f-s-13">View log topilmadi</div> : null}
          </div>
        </div>
        <div className="col-lg-6">
          <div className="f-w-600 mb-2">Product commentlari</div>
          <div className="d-grid gap-2">
            {(product.comments || []).map((comment) => (
              <div className="b-1-light b-r-15 p-3" key={comment.id}>
                <strong className="d-block text-dark f-w-600 f-s-16">{comment.name}</strong>
                <span className="d-block text-secondary f-s-13 f-w-500 mt-1">{comment.content}</span>
                <span className="d-block text-secondary f-s-13 f-w-500 mt-1">AI: {comment.aiStatus || '—'} {comment.aiScore ?? ''} · {comment.date || '—'}</span>
              </div>
            ))}
            {(product.comments || []).length === 0 ? <div className="text-muted f-s-13">Product bo‘yicha comment topilmadi</div> : null}
          </div>
        </div>
      </div>
    </div>
  );
}
