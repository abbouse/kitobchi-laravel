import { useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  orders: number;
  spent: number;
  status: string;
  role: string;
  showUrl?: string;
  editUrl?: string;
  blockUrl?: string;
  unblockUrl?: string;
  destroyUrl?: string;
}

export default function Users() {
  const { users: serverUsers = [] } = usePage<{ users?: User[] }>().props;
  const list = useMemo(() => serverUsers, [serverUsers]);
  const pagination = useClientPagination(list, 30);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [showView, setShowView] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);

  // Form states
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState('Customer');
  const [status, setStatus] = useState('Active');

  const handleOpenAdd = () => {
    setName('');
    setEmail('');
    setPhone('');
    setRole('Customer');
    setStatus('Active');
    setShowAdd(true);
  };

  const handleOpenEdit = (u: User) => {
    setSelectedUser(u);
    setName(u.name);
    setEmail(u.email);
    setPhone(u.phone);
    setRole(u.role);
    setStatus(u.status);
    setShowEdit(true);
  };

  // Actions
  const handleAdd = (e: React.FormEvent) => {
    e.preventDefault();
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser) return;
    setShowEdit(false);
  };

  const handleDelete = (user: User) => {
    if (confirm("Foydalanuvchini o'chirishni tasdiqlaysizmi?")) {
      if (user.destroyUrl) {
        router.delete(user.destroyUrl);
      }
    }
  };

  const handleToggleBlock = (user: User) => {
    const url = user.status === 'Blocked' ? user.unblockUrl : user.blockUrl;
    if (url) {
      router.patch(url, {}, { preserveScroll: true });
    }
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Foydalanuvchilar</h1>
          <p className="page-subtitle">Mijozlar, sotuvchilar va administratorlar</p>
        </div>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami foydalanuvchilar', val: list.length, icon: 'bi-people', color: '#4f46e5' },
          { label: 'VIP mijozlar', val: list.filter(u => u.status === 'VIP').length, icon: 'bi-star', color: '#f59e0b' },
          { label: 'Sotuvchilar', val: list.filter(u => u.role === 'Seller').length, icon: 'bi-shop', color: '#10b981' },
          { label: 'Faol a\'zolar', val: list.filter(u => u.status === 'Active').length, icon: 'bi-person-check', color: '#7c3aed' },
        ].map((s) => (
          <div className="col-xl-3 col-md-6" key={s.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={{ background: s.color }}><i className={`bi ${s.icon}`}></i></div>
                <div>
                  <div className="stat-value">{s.val}</div>
                  <div className="stat-label">{s.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th></th>
                <th>Ism</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Buyurtmalar</th>
                <th>Sarflangan</th>
                <th>Rol</th>
                <th>Status</th>
                <th>Amallar</th>
              </tr>
            </thead>
            <tbody>
              {pagination.paginated.map((u) => (
                <tr key={u.id}>
                  <td>
                    <div style={{ width: 40, height: 40, borderRadius: '50%', background: 'linear-gradient(135deg,#c7d2fe,#f3e8ff)', display: 'grid', placeItems: 'center', fontWeight: 700, color: '#4f46e5' }}>
                      {u.name.split(' ').map(n => n[0]).join('')}
                    </div>
                  </td>
                  <td className="fw-semibold">{u.name}</td>
                  <td className="text-muted">{u.email}</td>
                  <td>{u.phone}</td>
                  <td>{u.orders}</td>
                  <td className="fw-semibold">{fmt(u.spent)} so'm</td>
                  <td><span className="chip chip-purple">{u.role}</span></td>
                  <td>
                    <span className={`chip ${u.status === 'VIP' ? 'chip-warning' : u.status === 'Blocked' ? 'chip-danger' : 'chip-success'}`}>
                      {u.status}
                    </span>
                  </td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setSelectedUser(u); setShowView(true); }} title="Ko'rish">
                      <i className="bi bi-eye"></i>
                    </button>
                    {(u.blockUrl || u.unblockUrl) ? (
                      <button className="btn btn-sm btn-light me-1" onClick={() => handleToggleBlock(u)} title={u.status === 'Blocked' ? 'Blokdan chiqarish' : 'Bloklash'}>
                        <i className={`bi ${u.status === 'Blocked' ? 'bi-unlock' : 'bi-lock'}`}></i>
                      </button>
                    ) : null}
                    <button className="btn btn-sm btn-light text-danger" onClick={() => handleDelete(u)} title="O'chirish">
                      <i className="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...pagination} onPageChange={pagination.setPage} />
      </div>

      <Modal show={showView} onHide={() => setShowView(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title className="fs-5 fw-bold">{selectedUser?.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="row g-3">
            <div className="col-md-6"><div className="text-muted small">Email</div><div className="fw-semibold">{selectedUser?.email || '—'}</div></div>
            <div className="col-md-6"><div className="text-muted small">Telefon</div><div className="fw-semibold">{selectedUser?.phone || '—'}</div></div>
            <div className="col-md-6"><div className="text-muted small">Buyurtmalar</div><div className="fw-semibold">{selectedUser?.orders || 0}</div></div>
            <div className="col-md-6"><div className="text-muted small">Sarflangan</div><div className="fw-semibold">{fmt(selectedUser?.spent || 0)} so'm</div></div>
            <div className="col-md-6"><div className="text-muted small">Rol</div><span className="chip chip-purple">{selectedUser?.role}</span></div>
            <div className="col-md-6"><div className="text-muted small">Status</div><span className={`chip ${selectedUser?.status === 'Blocked' ? 'chip-danger' : selectedUser?.status === 'VIP' ? 'chip-warning' : 'chip-success'}`}>{selectedUser?.status}</span></div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selectedUser && (selectedUser.blockUrl || selectedUser.unblockUrl) ? (
            <Button variant="outline-secondary" onClick={() => handleToggleBlock(selectedUser)}>
              {selectedUser.status === 'Blocked' ? 'Blokdan chiqarish' : 'Bloklash'}
            </Button>
          ) : null}
          <Button variant="light" onClick={() => setShowView(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>

      {/* ADD MODAL */}
      <Modal show={showAdd} onHide={() => setShowAdd(false)} centered>
        <Form onSubmit={handleAdd}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Yangi foydalanuvchi qo'shish</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Ism-sharifi</Form.Label>
              <Form.Control required placeholder="Alisher Navoiy" value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Email</Form.Label>
                <Form.Control type="email" required placeholder="alisher@mail.uz" value={email} onChange={e => setEmail(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Telefon</Form.Label>
                <Form.Control required placeholder="+998 90 123 45 67" value={phone} onChange={e => setPhone(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Roli</Form.Label>
                <Form.Select value={role} onChange={e => setRole(e.target.value)}>
                  <option value="Customer">Customer</option>
                  <option value="Seller">Seller</option>
                  <option value="Admin">Admin</option>
                </Form.Select>
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Statusi</Form.Label>
                <Form.Select value={status} onChange={e => setStatus(e.target.value)}>
                  <option value="Active">Active</option>
                  <option value="VIP">VIP</option>
                  <option value="Blocked">Blocked</option>
                </Form.Select>
              </Form.Group>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowAdd(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Qo'shish</Button>
          </Modal.Footer>
        </Form>
      </Modal>

      {/* EDIT MODAL */}
      <Modal show={showEdit} onHide={() => setShowEdit(false)} centered>
        <Form onSubmit={handleEdit}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">Foydalanuvchini tahrirlash</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold">Ism-sharifi</Form.Label>
              <Form.Control required value={name} onChange={e => setName(e.target.value)} />
            </Form.Group>
            <div className="row g-3 mb-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Email</Form.Label>
                <Form.Control type="email" required value={email} onChange={e => setEmail(e.target.value)} />
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Telefon</Form.Label>
                <Form.Control required value={phone} onChange={e => setPhone(e.target.value)} />
              </Form.Group>
            </div>
            <div className="row g-3">
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Roli</Form.Label>
                <Form.Select value={role} onChange={e => setRole(e.target.value)}>
                  <option value="Customer">Customer</option>
                  <option value="Seller">Seller</option>
                  <option value="Admin">Admin</option>
                </Form.Select>
              </Form.Group>
              <Form.Group className="col-md-6">
                <Form.Label className="small fw-semibold">Statusi</Form.Label>
                <Form.Select value={status} onChange={e => setStatus(e.target.value)}>
                  <option value="Active">Active</option>
                  <option value="VIP">VIP</option>
                  <option value="Blocked">Blocked</option>
                </Form.Select>
              </Form.Group>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => setShowEdit(false)}>Bekor qilish</Button>
            <Button variant="primary" type="submit" className="btn-primary-gradient">Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
