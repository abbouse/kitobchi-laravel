import { router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Button, Form, Modal } from 'react-bootstrap';

interface Policy {
  id: number;
  title: string;
  slug: string;
  content?: string;
  status: string;
  showInApp: boolean;
  sortOrder: number;
  createUrl?: string;
  updateUrl?: string;
  toggleUrl?: string;
  destroyUrl?: string;
}

export default function Siyosatlar() {
  const { policies = [] } = usePage<{ policies?: Policy[] }>().props;
  const [editing, setEditing] = useState<Policy | null>(null);
  const [showForm, setShowForm] = useState(false);
  const createUrl = policies[0]?.createUrl || '/boshqaruv/siyosatlar';

  const toggle = (policy: Policy) => policy.toggleUrl && router.patch(policy.toggleUrl, {}, { preserveScroll: true });
  const destroy = (policy: Policy) => {
    if (!policy.destroyUrl || !confirm(`${policy.title} siyosati o'chirilsinmi?`)) return;
    router.delete(policy.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    const options = { preserveScroll: true, onSuccess: () => { setEditing(null); setShowForm(false); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Siyosatlar va qoidalar</h1>
          <p className="page-subtitle">Legal sahifalar, appda ko'rinishi va aktiv holat</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Siyosat qo'shish</button>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Sarlavha</th><th>Slug</th><th>Tartib</th><th>App</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {policies.map((policy) => (
                <tr key={policy.id}>
                  <td className="fw-semibold text-primary">#{policy.id}</td>
                  <td className="fw-semibold">{policy.title}</td>
                  <td><code>{policy.slug}</code></td>
                  <td>{policy.sortOrder}</td>
                  <td><span className={`chip ${policy.showInApp ? 'chip-success' : 'chip-gray'}`}>{policy.showInApp ? 'Ha' : "Yo'q"}</span></td>
                  <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={policy.status === 'Active'} onChange={() => toggle(policy)} /></div></td>
                  <td>
                    <button className="btn btn-sm btn-light me-1" onClick={() => { setEditing(policy); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(policy)}><i className="bi bi-trash"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'Siyosatni tahrirlash' : "Siyosat qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="row g-3">
              <div className="col-md-8"><Form.Label>Sarlavha</Form.Label><Form.Control name="title" required defaultValue={editing?.title || ''} /></div>
              <div className="col-md-4"><Form.Label>Slug</Form.Label><Form.Control name="slug" defaultValue={editing?.slug || ''} /></div>
              <div className="col-md-4"><Form.Label>Tartib</Form.Label><Form.Control name="sort_order" type="number" min={0} defaultValue={editing?.sortOrder ?? 0} /></div>
              <div className="col-md-4 d-flex align-items-end"><Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.status === 'Active' : true} /></div>
              <div className="col-md-4 d-flex align-items-end"><Form.Check type="switch" name="show_in_app" value="1" label="Appda ko'rinsin" defaultChecked={editing ? editing.showInApp : true} /></div>
              <div className="col-12"><Form.Label>Matn</Form.Label><Form.Control as="textarea" rows={8} name="content" required defaultValue={editing?.content || ''} /></div>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
