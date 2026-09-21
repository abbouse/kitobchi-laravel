import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button, Form } from 'react-bootstrap';

// ===== VAKANSIYALAR =====
export function Vakansiyalar() {
  const { vacancies = [] } = usePage<{
    vacancies?: Array<{ id: number; title: string; icon?: string; contractType?: string; location?: string; description?: string; sortOrder?: number; status: string; applicants: number; translations?: Record<string, { title?: string; contract_type?: string; location?: string; description?: string }>; createUrl?: string; updateUrl?: string; toggleUrl?: string; destroyUrl?: string }>;
  }>().props;
  const [editing, setEditing] = useState<(typeof vacancies)[0] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const createUrl = vacancies[0]?.createUrl || '/boshqaruv/vakansiyalar';
  const toggle = (vacancy: (typeof vacancies)[0]) => vacancy.toggleUrl && router.patch(vacancy.toggleUrl, {}, { preserveScroll: true });
  const destroy = (vacancy: (typeof vacancies)[0]) => {
    if (!vacancy.destroyUrl || !confirm(`${vacancy.title} vakansiyasi o'chirilsinmi?`)) return;
    router.delete(vacancy.destroyUrl, { preserveScroll: true });
  };
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const options = { preserveScroll: true, onSuccess: () => { setShowForm(false); setEditing(null); } };
    editing?.updateUrl ? router.put(editing.updateUrl, data, options) : router.post(createUrl, data, options);
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Vakansiyalar</h1><p className="page-subtitle">Jami {vacancies.length} ta vakansiya</p></div>
        <button className="btn btn-primary-gradient" onClick={() => { setEditing(null); setShowForm(true); }}><i className="bi bi-plus-lg me-1"></i>Qo'shish</button>
        </div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Vakansiya</th><th>Shart</th><th>Joylashuv</th><th>Arizalar</th><th>Holat</th><th>Amallar</th></tr></thead>
          <tbody>{vacancies.map(vacancy => (
            <tr key={vacancy.id}>
              <td className="fw-semibold" style={{ color: 'var(--kc-ink)' }}>#{vacancy.id}</td>
              <td className="fw-semibold">{vacancy.title}</td>
              <td>{vacancy.contractType || '—'}</td>
              <td>{vacancy.location || '—'}</td>
              <td>{vacancy.applicants} ta</td>
              <td><div className="form-check form-switch"><input type="checkbox" className="form-check-input" checked={vacancy.status === 'Active'} onChange={() => toggle(vacancy)} /></div></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setEditing(vacancy); setShowForm(true); }}><i className="bi bi-pencil"></i></button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(vacancy)}><i className="bi bi-trash"></i></button>
              </td>
            </tr>
          ))}</tbody>
        </table></div>
      </div>
      <Modal show={showForm} onHide={() => setShowForm(false)} centered size="lg">
        <Form onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{editing ? 'Vakansiyani tahrirlash' : "Vakansiya qo'shish"}</Modal.Title></Modal.Header>
          <Modal.Body>
            <div className="row g-3">
              <div className="col-md-8"><Form.Label>Sarlavha</Form.Label><Form.Control name="title" required defaultValue={editing?.title || ''} /></div>
              <div className="col-md-4"><Form.Label>Icon</Form.Label><Form.Select name="icon" defaultValue={editing?.icon || 'briefcase'}><option value="briefcase">Lavozim</option><option value="code">IT</option><option value="palette">Dizayn</option><option value="shop">Savdo</option><option value="megaphone">Marketing</option><option value="people">HR</option><option value="chart">Analitika</option></Form.Select></div>
              <div className="col-md-4"><Form.Label>Shart turi</Form.Label><Form.Control name="contract_type" defaultValue={editing?.contractType || ''} /></div>
              <div className="col-md-4"><Form.Label>Joylashuv</Form.Label><Form.Control name="location" defaultValue={editing?.location || ''} /></div>
              <div className="col-md-4"><Form.Label>Tartib</Form.Label><Form.Control name="sort_order" type="number" min={0} defaultValue={editing?.sortOrder ?? 0} /></div>
              <div className="col-12"><Form.Label>Tavsif</Form.Label><Form.Control as="textarea" rows={5} name="description" required defaultValue={editing?.description || ''} /></div>
              {[
                ['ru', 'Ruscha'],
                ['en', 'Inglizcha'],
                ['ja', 'Yaponcha'],
              ].map(([locale, label]) => (
                <div className="col-12" key={locale}>
                  <div className="rounded-4 border bg-light-subtle p-3">
                    <div className="fw-semibold mb-3">{label} tarjima</div>
                    <div className="row g-3">
                      <div className="col-md-6">
                        <Form.Label>{label} sarlavha</Form.Label>
                        <Form.Control
                          name={`translations[${locale}][title]`}
                          defaultValue={editing?.translations?.[locale]?.title || ''}
                        />
                      </div>
                      <div className="col-md-3">
                        <Form.Label>{label} shart turi</Form.Label>
                        <Form.Control
                          name={`translations[${locale}][contract_type]`}
                          defaultValue={editing?.translations?.[locale]?.contract_type || ''}
                        />
                      </div>
                      <div className="col-md-3">
                        <Form.Label>{label} joylashuv</Form.Label>
                        <Form.Control
                          name={`translations[${locale}][location]`}
                          defaultValue={editing?.translations?.[locale]?.location || ''}
                        />
                      </div>
                      <div className="col-12">
                        <Form.Label>{label} tavsif</Form.Label>
                        <Form.Control
                          as="textarea"
                          rows={4}
                          name={`translations[${locale}][description]`}
                          defaultValue={editing?.translations?.[locale]?.description || ''}
                        />
                      </div>
                    </div>
                  </div>
                </div>
              ))}
              <div className="col-12">
                {/* BUG TUZATILDI (2026-09): ilgari faqat checkbox (hidden
                    fallback'siz) yuborilardi — "Faol"ni O'CHIRIB saqlansa,
                    unchecked checkbox FormData'ga UMUMAN kirmasdi, backend
                    esa yo'q maydonni "true" deb hisoblab, vakansiyani
                    xato ravishda qayta faollashtirib qo'yardi. Endi
                    (Settings.tsx dagi Toggle komponenti bilan bir xil,
                    loyihada sinovdan o'tgan) hidden-fallback + checkbox
                    kombinatsiyasi ishlatiladi. */}
                <input type="hidden" name="is_active" value="0" />
                <Form.Check type="switch" name="is_active" value="1" label="Faol" defaultChecked={editing ? editing.status === 'Active' : true} />
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer><Button variant="light" onClick={() => setShowForm(false)}>Bekor qilish</Button><Button type="submit" className="btn-primary-gradient border-0">Saqlash</Button></Modal.Footer>
        </Form>
      </Modal>
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
  const [replyText, setReplyText] = useState('');
  const [sendingReply, setSendingReply] = useState(false);
  const updateStatus = (status: string) => {
    if (!selected?.statusUrl) return;
    router.patch(selected.statusUrl, { status }, { preserveScroll: true });
  };
  const sendReply = () => {
    if (!selected?.replyUrl || replyText.trim().length < 5) return;
    setSendingReply(true);
    router.post(selected.replyUrl, { body: replyText }, {
      preserveScroll: true,
      onSuccess: () => setReplyText(''),
      onFinish: () => setSendingReply(false),
    });
  };

  return (
    <div>
      <div className="page-head"><div><h1 className="page-title">Karyera arizalari</h1><p className="page-subtitle">Jami {applications.length} ta ariza</p></div></div>
      <div className="card-panel">
        <div className="table-responsive"><table className="data-table">
          <thead><tr><th>ID</th><th>Nomzod</th><th>Vakansiya</th><th>Kontakt</th><th>Sana</th><th>Status</th><th>Amallar</th></tr></thead>
          <tbody>{applications.map(application => (
            <tr key={application.id}>
              <td className="fw-semibold" style={{ color: 'var(--kc-ink)' }}>#{application.id}</td>
              <td className="fw-semibold">{application.name}</td>
              <td>{application.vacancy}</td>
              <td className="text-muted small">{application.email}<br />{application.telegram}</td>
              <td className="text-muted">{application.date || '—'}</td>
              <td><span className={`chip ${application.status === 'new' ? 'chip-info' : application.status === 'reviewed' ? 'chip-warning' : 'chip-success'}`} style={{ fontSize: 9 }}>{application.status}</span></td>
              <td>
                <button className="btn btn-sm btn-light me-1" onClick={() => { setSelected(application); setReplyText(''); setShowDetail(true); }}><i className="bi bi-eye"></i></button>
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
            {selected?.replyUrl ? (
              <div className="col-12 mt-3">
                <label className="form-label small text-muted fw-semibold">Nomzodga javob yozish</label>
                <textarea
                  className="form-control"
                  rows={4}
                  placeholder="Javob matnini kiriting (kamida 5 ta belgi)..."
                  value={replyText}
                  onChange={(event) => setReplyText(event.target.value)}
                ></textarea>
                <div className="form-text">Javob nomzodning email manziliga ({selected?.email}) yuboriladi.</div>
                <div className="d-flex justify-content-end mt-2">
                  <button
                    className="btn btn-primary-gradient btn-sm"
                    disabled={sendingReply || replyText.trim().length < 5}
                    onClick={sendReply}
                  >
                    <i className="bi bi-send me-1"></i>{sendingReply ? 'Yuborilmoqda...' : 'Yuborish'}
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light" onClick={() => setShowDetail(false)}>Yopish</Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
}
