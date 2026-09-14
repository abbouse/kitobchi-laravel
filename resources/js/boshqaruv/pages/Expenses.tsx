import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Modal } from 'react-bootstrap';
import PaginationControls from '../components/PaginationControls';

const fmt = (value: number) => new Intl.NumberFormat('uz-UZ').format(value || 0);
type Row = { id: number; category: string; categoryLabel: string; amount: number; spentAt: string; title: string; note?: string; orderId?: number; reference?: string; updateUrl: string; destroyUrl: string };
type Pagination = { page: number; totalPages: number; from: number; to: number; total: number };

export default function Expenses() {
  const { expenses = [], expenseCategories = {}, expenseSummary = { total: 0, month: 0, categories: [] }, expensePagination = { page: 1, totalPages: 1, from: 0, to: 0, total: 0 }, expenseFilters = {}, actions = {} } = usePage<{
    expenses?: Row[]; expenseCategories?: Record<string, string>; expenseSummary?: { total: number; month: number; categories: Array<{ category: string; label: string; total: number }> }; expensePagination?: Pagination; expenseFilters?: { category?: string; search?: string }; actions?: { storeUrl?: string };
  }>().props;
  const [editing, setEditing] = useState<Row | null | undefined>(undefined);
  const [search, setSearch] = useState(expenseFilters.search || '');
  const [category, setCategory] = useState(expenseFilters.category || '');
  const load = (page = 1, nextCategory = category) => router.get('/boshqaruv/expenses', { expenses_page: page, expenses_category: nextCategory, expenses_search: search }, { preserveState: true, preserveScroll: true, replace: true });

  return <div>
    <div className="page-head"><div><h1 className="page-title">Chiqimlar</h1><p className="page-subtitle">Marketplace operatsion xarajatlari va analitika uchun ledger</p></div><Button onClick={() => setEditing(null)}><i className="bi bi-plus-lg me-1"></i>Chiqim qo‘shish</Button></div>
    <div className="row g-3 mb-4">
      <Stat label="Jami chiqim" value={`${fmt(expenseSummary.total)} so'm`} icon="bi-wallet2" />
      <Stat label="Joriy oy" value={`${fmt(expenseSummary.month)} so'm`} icon="bi-calendar3" />
      <Stat label="Yozuvlar" value={String(expensePagination.total)} icon="bi-receipt" />
    </div>
    <div className="card-panel">
      <div className="panel-head"><div><div className="panel-title">Chiqimlar tarixi</div><small className="text-muted">{expensePagination.total} ta yozuv</small></div><div className="d-flex gap-2 flex-wrap"><input value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && load(1)} className="form-control form-control-sm" placeholder="Nomi, izoh, order yoki reference" /><select value={category} onChange={(e) => { setCategory(e.target.value); load(1, e.target.value); }} className="form-select form-select-sm"><option value="">Barcha turlar</option>{Object.entries(expenseCategories).map(([key, label]) => <option value={key} key={key}>{label}</option>)}</select></div></div>
      <div className="table-responsive"><table className="data-table"><thead><tr><th>Sana</th><th>Tur</th><th>Nomi</th><th>Order / reference</th><th>Summa</th><th></th></tr></thead><tbody>
        {expenses.map((row) => <tr key={row.id}><td>{row.spentAt}</td><td><span className="chip chip-gray">{row.categoryLabel}</span></td><td><strong>{row.title}</strong><div className="small text-muted">{row.note || '—'}</div></td><td>{row.orderId ? `#${row.orderId}` : row.reference || '—'}</td><td className="fw-bold text-danger">{fmt(row.amount)} so'm</td><td className="text-end"><button className="btn btn-sm btn-light me-1" onClick={() => setEditing(row)}><i className="bi bi-pencil"></i></button><button className="btn btn-sm btn-light text-danger" onClick={() => confirm("Chiqim o'chirilsinmi?") && router.delete(row.destroyUrl, { preserveScroll: true })}><i className="bi bi-trash"></i></button></td></tr>)}
        {!expenses.length ? <tr><td colSpan={6} className="text-center text-muted py-5">Chiqim yozuvi topilmadi</td></tr> : null}
      </tbody></table></div><PaginationControls {...expensePagination} onPageChange={(page) => load(page)} />
    </div>
    <ExpenseModal show={editing !== undefined} expense={editing || undefined} categories={expenseCategories} storeUrl={actions.storeUrl} onHide={() => setEditing(undefined)} />
  </div>;
}

function Stat({ label, value, icon }: { label: string; value: string; icon: string }) { return <div className="col-md-4"><div className="stat-card"><div className="d-flex gap-3 align-items-center"><div><div className="stat-value">{value}</div><div className="stat-label">{label}</div></div></div></div></div>; }
function ExpenseModal({ show, expense, categories, storeUrl, onHide }: { show: boolean; expense?: Row; categories: Record<string, string>; storeUrl?: string; onHide: () => void }) {
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); const data = Object.fromEntries(new FormData(event.currentTarget).entries()); if (expense) router.put(expense.updateUrl, data, { preserveScroll: true, onSuccess: onHide }); else if (storeUrl) router.post(storeUrl, data, { preserveScroll: true, onSuccess: onHide }); };
  return <Modal show={show} onHide={onHide} centered><form onSubmit={submit}><Modal.Header closeButton><Modal.Title>{expense ? 'Chiqimni tahrirlash' : "Yangi chiqim"}</Modal.Title></Modal.Header><Modal.Body><div className="row g-3"><div className="col-12"><label className="form-label">Xarajat turi</label><select name="category" defaultValue={expense?.category || 'marketing'} className="form-select">{Object.entries(categories).map(([key, label]) => <option value={key} key={key}>{label}</option>)}</select></div><Field name="title" label="Nimaga sarflandi" value={expense?.title} required /><Field name="amount" label="Summa (UZS)" type="number" value={expense?.amount} required /><Field name="spent_at" label="Sana" type="date" value={expense?.spentAt || new Date().toISOString().slice(0, 10)} required /><Field name="order_id" label="Order ID (ixtiyoriy)" type="number" value={expense?.orderId} /><Field name="reference" label="Reference (ixtiyoriy)" value={expense?.reference} /><div className="col-12"><label className="form-label">Izoh</label><textarea name="note" defaultValue={expense?.note || ''} className="form-control" rows={3}></textarea></div></div></Modal.Body><Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit">Saqlash</Button></Modal.Footer></form></Modal>;
}
function Field({ name, label, value, type = 'text', required }: { name: string; label: string; value?: string | number; type?: string; required?: boolean }) { return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} type={type} defaultValue={value ?? ''} required={required} min={type === 'number' ? 0 : undefined} className="form-control" /></div>; }
