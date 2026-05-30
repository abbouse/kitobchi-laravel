import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== VAKANSIYALAR =====
export function Vakansiyalar() {
  const { vacancies = [] } = usePage<{
    vacancies?: Array<{ id: number; title: string; contractType?: string; location?: string; status: string; applicants: number; createUrl?: string; editUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const createUrl = vacancies[0]?.createUrl || '/a122/jobs/create';
  const toggle = (vacancy: (typeof vacancies)[0]) => vacancy.toggleUrl && router.patch(vacancy.toggleUrl, {}, { preserveScroll: true });
  const destroy = (vacancy: (typeof vacancies)[0]) => {
    if (!vacancy.destroyUrl || !confirm(`${vacancy.title} vakansiyasi o'chirilsinmi?`)) return;
    router.delete(vacancy.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Vakansiyalar</h1><p className="page-subtitle">Jami {vacancies.length} ta vakansiya</p></div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi vakansiya</a></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Vakansiya</th><th>Shart</th><th>Joylashuv</th><th>Arizalar</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{vacancies.map(vacancy => (
            <tr key={vacancy.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{vacancy.id}</td>
              <td className="fw-semibold">{vacancy.title}</td>
              <td>{vacancy.contractType || '—'}</td>
              <td>{vacancy.location || '—'}</td>
              <td>{vacancy.applicants} ta</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={vacancy.status === 'Active'} onChange={() => toggle(vacancy)} /></div></td>
              <td>
                {vacancy.editUrl ? <a className="btn btn-sm btn-light me-1" href={vacancy.editUrl}><i className="bi bi-pencil"></i></a> : null}
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(vacancy)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
    </div>
  );
}

// ===== KARYERA ARIZALARI =====
export function KaryeraArizalari() {
  const { applications = [] } = usePage<{
    applications?: Array<{ id: number; name: string; vacancy: string; status: string; email?: string; telegram?: string; message?: string; date?: string; showUrl?: string; cvUrl?: string; replyUrl?: string; statusUrl?: string }>;
  }>().props;
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState<(typeof applications)[0] | null>(null);
  const updateStatus = (status: string) => {
    if (!selected?.statusUrl) return;
    router.patch(selected.statusUrl, { status }, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Karyera arizalari</h1><p className="page-subtitle">Jami {applications.length} ta ariza</p></div><a className="btn btn-outline-secondary" href="/a122/job-applications">Eski filtr</a></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Nomzod</th><th>Vakansiya</th><th>Kontakt</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{applications.map(application => (
            <tr key={application.id}>
              <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{application.id}</td>
              <td className="fw-semibold">{application.name}</td>
              <td>{application.vacancy}</td>
              <td className="text-muted small">{application.email}<br />{application.telegram}</td>
              <td className="text-muted">{application.date || '—'}</td>
              <td><span className={`chip ${application.status === 'new' ? 'chip-info' : application.status === 'reviewed' ? 'chip-warning' : 'chip-success'}`} style={{ fontSize: 9 }}>{application.status}</span></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(application); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
                {application.cvUrl ? <a className="btn btn-sm btn-light" href={application.cvUrl}><i className="bi bi-download"></i></a> : null}
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showDetail} onHide={() => setShowDetail(false)} centered>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{selected?.name}</Modal.Title></Modal.Header>
        <Modal.Body>
          <div className="row g-2">
            <div className="col-6"><small className="text-muted">Vakansiya</small><div className="fw-semibold">{selected?.vacancy}</div></div>
            <div className="col-6"><small className="text-muted">Status</small><div><span className={`chip ${selected?.status === 'New' ? 'chip-info' : selected?.status === 'Reviewed' ? 'chip-warning' : 'chip-success'}`}>{selected?.status}</span></div></div>
            <div className="col-12"><small className="text-muted">Email</small><div>{selected?.email}</div></div>
            <div className="col-12"><small className="text-muted">Telegram</small><div>{selected?.telegram || '—'}</div></div>
            <div className="col-12"><small className="text-muted">Xabar</small><div>{selected?.message || '—'}</div></div>
            <div className="col-12 mt-3">
              <div className="d-flex gap-2">
                {['new', 'reviewed', 'replied', 'closed'].map(s => (
                  <button key={s} className={`btn btn-sm ${selected?.status === s ? 'btn-primary-gradient' : 'btn-outline-secondary'}`} onClick={() => updateStatus(s)}>{s}</button>
                ))}
              </div>
            </div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          {selected?.showUrl ? <a className="btn btn-primary-gradient" href={selected.showUrl}>Eski panelda ochish</a> : null}
          <Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
