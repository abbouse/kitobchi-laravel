import { useEffect, useRef, useState } from 'react';
import { apiJson } from '../utils/realtime';
import { toneBadge } from '../utils/tone';
import { OrderDetailModal, normalizeOrder, statusTone, type Ord } from '../pages/Orders';

/**
 * Support chati → o'ng panel → "Buyurtmalari".
 * Operator suhbatdan chiqmasdan mijozning barcha buyurtmalarini ko'radi va
 * istalganini to'liq ochadi. Amallar (status, bekor qilish, refund, push...)
 * faqat "Buyurtmalar" huquqi bor xodimda; qolganlarga — faqat ko'rish
 * (server amal havolalarini yubormaydi va routelar ham himoyalangan).
 */
type Row = {
  id: number;
  amount: number;
  status: string;
  statusLabel: string;
  paymentCode: string;
  payment: string;
  items: number;
  date: string | null;
  deliveryDate: string | null;
  dataUrl: string;
};
type ListResponse = { items: Row[]; page: number; totalPages: number; total: number; canManage: boolean };

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(Math.round(n || 0));

export default function CustomerOrders({ userId, total, listUrl }: { userId: number; total: number; listUrl: string }) {
  const [open, setOpen] = useState(false);
  const [rows, setRows] = useState<Row[]>([]);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [count, setCount] = useState(total);
  const [canManage, setCanManage] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [selected, setSelected] = useState<Ord | null>(null);
  const [showDetail, setShowDetail] = useState(false);
  const [detailLoading, setDetailLoading] = useState(false);
  const userRef = useRef(userId);

  // Boshqa suhbatga o'tilganda — ro'yxat yangidan
  useEffect(() => {
    userRef.current = userId;
    setOpen(false);
    setRows([]);
    setPage(1);
    setTotalPages(1);
    setCount(total);
    setError(null);
    setShowDetail(false);
    setSelected(null);
  }, [userId]); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { setCount(total); }, [total]);

  const load = async (nextPage = 1, append = false) => {
    const forUser = userId;
    setLoading(true);
    setError(null);
    try {
      const res = await apiJson<ListResponse>(`${listUrl}?user_id=${forUser}&page=${nextPage}`);
      if (userRef.current !== forUser) return;
      setRows((prev) => (append ? [...prev, ...res.items] : res.items));
      setPage(res.page);
      setTotalPages(res.totalPages);
      setCount(res.total);
      setCanManage(res.canManage);
    } catch (e) {
      if (userRef.current === forUser) setError(e instanceof Error ? e.message : 'Buyurtmalarni yuklab bo‘lmadi');
    } finally {
      if (userRef.current === forUser) setLoading(false);
    }
  };

  const toggle = () => {
    const next = !open;
    setOpen(next);
    if (next && !rows.length) void load(1);
  };

  // Tafsilot har doim support endpointi orqali (huquqqa qarab amallar bilan yoki faqat ko'rish)
  const detailUrl = (id: number) => `${listUrl}/${id}`;

  const reload = async (order: Ord) => {
    const id = Number(order.rawId || String(order.id).replace('#', ''));
    if (!id) return;
    setDetailLoading(true);
    try {
      const data = await apiJson<Ord>(detailUrl(id));
      setSelected(normalizeOrder(data));
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Buyurtmani yuklab bo‘lmadi');
    } finally {
      setDetailLoading(false);
    }
  };

  const openRow = (row: Row) => {
    const stub = normalizeOrder({
      id: `#${row.id}`, rawId: row.id, customer: '', items: row.items, total: row.amount,
      status: row.status, date: row.date || '', payment: row.paymentCode, paymentStatus: row.paymentCode,
    } as Ord);
    setSelected(stub);
    setShowDetail(true);
    void reload(stub);
  };

  const closeDetail = () => {
    setShowDetail(false);
    // Status yoki to'lov o'zgargan bo'lishi mumkin — ro'yxat yangilanadi
    if (open) void load(1);
  };

  return (
    <div className="b-1-light b-r-15 mb-3 kb-co">
      <button type="button" className="kb-co-toggle" onClick={toggle} aria-expanded={open}>
        <i className="ti ti-receipt"></i>
        <span className="flex-grow-1 text-start">Buyurtmalari</span>
        <span className="kb-tab-count">{fmt(count)}</span>
        <i className={`ti ${open ? 'ti-chevron-up' : 'ti-chevron-down'} text-secondary`}></i>
      </button>

      {open ? (
        <div className="kb-co-body">
          {canManage === false ? (
            <div className="f-s-11 text-secondary d-flex align-items-center gap-1 mb-2"><i className="ti ti-eye"></i>Faqat ko‘rish — amallar buyurtmalar huquqi bor xodimda</div>
          ) : null}
          {error ? <div className="f-s-12 text-danger mb-2">{error}</div> : null}
          {rows.map((row) => (
            <button type="button" key={row.id} className="kb-co-row" onClick={() => openRow(row)} title="Buyurtmani ochish">
              <span className="min-w-0 flex-grow-1">
                <span className="d-flex align-items-center gap-2">
                  <span className="f-w-600 f-s-13">#{row.id}</span>
                  <span className={`badge ${toneBadge(statusTone(row.status))}`}>{row.statusLabel}</span>
                </span>
                <span className="d-block f-s-11 text-secondary text-truncate">
                  {[row.date, `${row.items} dona`, row.payment, row.deliveryDate ? `yetkazish ${row.deliveryDate}` : null].filter(Boolean).join(' · ')}
                </span>
              </span>
              <span className="f-w-600 f-s-13 text-nowrap kb-num">{fmt(row.amount)}</span>
              <i className="ti ti-chevron-right text-secondary"></i>
            </button>
          ))}
          {loading ? <div className="kb-skel-list mt-1" aria-busy="true"><span></span><span></span></div> : null}
          {!loading && !error && rows.length === 0 ? <div className="f-s-12 text-secondary py-2">Buyurtma yo‘q</div> : null}
          {!loading && page < totalPages ? (
            <button type="button" className="btn btn-link btn-sm p-0 mt-2" onClick={() => load(page + 1, true)}>Yana yuklash</button>
          ) : null}
        </div>
      ) : null}

      <OrderDetailModal
        show={showDetail}
        onHide={closeDetail}
        order={selected}
        setOrder={setSelected}
        loading={detailLoading && !selected?.itemsList}
        reload={reload}
        keepState
      />
    </div>
  );
}
