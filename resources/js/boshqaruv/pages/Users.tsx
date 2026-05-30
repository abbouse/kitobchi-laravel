import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';
import { users } from '../data';

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
  const [list, setList] = useState<User[]>(serverUsers.length ? serverUsers : users);

  // Modals
  const [showAdd, setShowAdd] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
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
    const newU: User = {
      id: Date.now(),
      name: name || 'Yangi Foydalanuvchi',
      email: email || 'user@bookhub.uz',
      phone: phone || '+998 90 000 00 00',
      orders: 0,
      spent: 0,
      status,
      role
    };
    setList([newU, ...list]);
    setShowAdd(false);
  };

  const handleEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser) return;
    setList(list.map(u => u.id === selectedUser.id ? {
      ...u,
      name,
      email,
      phone,
      role,
      status
    } : u));
    setShowEdit(false);
  };

  const handleDelete = (user: User) => {
    if (confirm("Foydalanuvchini o'chirishni tasdiqlaysizmi?")) {
      if (user.destroyUrl) {
        router.delete(user.destroyUrl);
        return;
      }

      setList(list.filter(u => u.id !== user.id));
    }
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Foydalanuvchilar</h1>
          <p className="page-subtitle">Mijozlar, sotuvchilar va administratorlar</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/a122/users">
            <i className="bi bi-download me-1"></i>Export CSV
          </a>
          <a className="btn btn-primary-gradient" href="/a122/users/create">
            <i className="bi bi-person-plus me-1"></i>Yangi foydalanuvchi
          </a>
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
              {list.map((u) => (
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
                    <a className="btn btn-sm btn-light me-1" href={u.showUrl || '#'} title="Ko'rish">
                      <i className="bi bi-eye"></i>
                    </a>
                    <a className="btn btn-sm btn-light me-1" href={u.editUrl || '#'} onClick={(e) => { if (!u.editUrl) { e.preventDefault(); handleOpenEdit(u); } }} title="Tahrirlash">
                      <i className="bi bi-pencil"></i>
                    </a>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => handleDelete(u)} title="O'chirish">
                      <i className="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

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
