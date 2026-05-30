import { useState } from 'react';
import { Offcanvas, Button, Nav } from 'react-bootstrap';
import { sellers, fmt } from '../data';

export default function SellerOrders() {
  const [showOffcanvas, setShowOffcanvas] = useState(false);
  const [selectedSeller, setSelectedSeller] = useState<typeof sellers[0] | null>(null);

  const totalRevenue = sellers.reduce((a, s) => a + s.revenue, 0);
  const totalCommission = sellers.reduce((a, s) => a + Math.floor(s.revenue * s.commissionRate / 100), 0);
  const unpaid = sellers.filter(s => s.status === 'Active' || s.status === 'Pending').reduce((a, s) => a + s.balance, 0);

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Sellerlar — Marketplace Boshqaruvi</h1>
          <p className="page-subtitle">Jami {sellers.length} ta seller · shartnoma, premium planlar, hodimlar va tranzaksiyalar</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={() => alert("Yangi seller qo'shish formasi ochiladi")}>
          <i className="bi bi-plus-lg me-1"></i>Yangi seller
        </button>
      </div>

      {/* Stats */}
      <div className="row g-2 mb-3">
        {[
          { l: 'Jami sellerlar', v: sellers.length, icon: 'bi-shop', c: '#4f46e5' },
          { l: 'Umumiy daromad', v: fmt(totalRevenue) + ' so\'m', icon: 'bi-cash-stack', c: '#10b981' },
          { l: 'Platforma komissiyasi', v: fmt(totalCommission) + ' so\'m', icon: 'bi-percent', c: '#7c3aed' },
          { l: "To'lanmagan balans", v: fmt(unpaid) + ' so\'m', icon: 'bi-hourglass-split', c: '#f59e0b' },
          { l: 'Enterprise', v: sellers.filter(s => s.tier === 'Enterprise').length, icon: 'bi-star-fill', c: '#059669' },
          { l: 'Premium', v: sellers.filter(s => s.tier === 'Premium').length, icon: 'bi-gem', c: '#ec4899' },
        ].map(s => (
          <div className="col-xl-2 col-md-4 col-6" key={s.l}>
            <div className="stat-card" style={{ padding: 12 }}>
              <div className="d-flex align-items-center gap-2">
                <div className="stat-icon" style={{ width: 36, height: 36, background: s.c, fontSize: 14 }}><i className={`bi ${s.icon}`}></i></div>
                <div>
                  <div className="stat-value" style={{ fontSize: 18 }}>{s.v}</div>
                  <div className="stat-label">{s.l}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Seller Cards */}
      <div className="row g-3">
        {sellers.map((seller) => (
          <div className="col-xl-4 col-md-6" key={seller.id}>
            <div className="card-panel h-100 d-flex flex-column">
              {/* Header */}
              <div className="d-flex justify-content-between align-items-start mb-2">
                <div className="d-flex align-items-center gap-2">
                  <div style={{ width: 44, height: 44, borderRadius: 10, background: seller.tier === 'Enterprise' ? 'linear-gradient(135deg,#f59e0b,#d97706)' : seller.tier === 'Premium' ? 'linear-gradient(135deg,#a855f7,#7c3aed)' : '#6b7280', display: 'grid', placeItems: 'center', fontSize: 18 }}>
                    <i className="bi bi-shop" style={{ color: 'white' }}></i>
                  </div>
                  <div>
                    <div className="fw-bold" style={{ fontSize: 14 }}>{seller.name}</div>
                    <small className="text-muted">{seller.legalName ? seller.legalName.split(' ')[0] : ''}</small>
                  </div>
                </div>
                <span className={`chip ${seller.status === 'Active' ? 'chip-success' : seller.status === 'Suspended' ? 'chip-danger' : 'chip-warning'}`} style={{ fontSize: 9 }}>{seller.status}</span>
              </div>

              {/* Rating & Stats */}
              <div className="d-flex gap-3 mb-2 small">
                <span>⭐ {seller.rating}</span>
                <span className="text-muted">{seller.products} mahsulot</span>
                <span className="text-muted">{seller.orders} buyurtma</span>
              </div>

              {/* Revenue & Balance */}
              <div className="row g-1 mb-2 text-center small">
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ color: '#4f46e5' }}>{fmt(seller.revenue)}</div>
                  <small className="text-muted">Daromad</small>
                </div>
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ color: '#10b981' }}>{fmt(seller.profit)}</div>
                  <small className="text-muted">Foyda</small>
                </div>
                <div className="col-4 p-1 rounded" style={{ background: '#f9fafb' }}>
                  <div className="fw-bold" style={{ color: '#059669' }}>{fmt(seller.balance)}</div>
                  <small className="text-muted">Balans</small>
                </div>
              </div>

              {/* Contract type */}
              <div className="p-2 rounded small mb-2" style={{ background: '#f9fafb', border: '1px solid #eef0f4' }}>
                <div className="d-flex justify-content-between">
                  <span><strong>{seller.contract.type}</strong> · {seller.contract.share}%</span>
                  <span className="fw-semibold">{seller.contract.startDate} — {seller.contract.endDate}</span>
                </div>
              </div>

              {/* Plan */}
              {seller.plan && (
                <div className="d-flex align-items-center gap-2 mb-2 p-2 rounded" style={{ background: 'linear-gradient(135deg, #fef3c7, #fde68a)' }}>
                  <i className="bi bi-gem" style={{ color: '#92400e', fontSize: 16 }}></i>
                  <div style={{ flex: 1 }}>
                    <div className="fw-bold" style={{ fontSize: 11, color: '#92400e' }}>{seller.plan.name}</div>
                    <div style={{ fontSize: 10, color: '#713f12' }}>{fmt(seller.plan.price)}/oy · {seller.plan.discount}% chegirma</div>
                  </div>
                </div>
              )}

              {/* Hubs */}
              <div className="d-flex gap-1 flex-wrap mb-2">
                <small className="text-muted">Hub: </small>
                {seller.hubs.map(h => <span key={h} className="chip chip-info" style={{ fontSize: 9 }}>{h.split('-')[1]}</span>)}
              </div>

              {/* Employees */}
              <div className="small">
                <div className="d-flex justify-content-between text-muted mb-1">
                  <span><i className="bi bi-people me-1"></i>Hodimlar ({seller.employees.length})</span>
                </div>
                {seller.employees.slice(0, 2).map(e => (
                  <div key={e.id} className="d-flex justify-content-between" style={{ fontSize: 10 }}>
                    <span>{e.name} · {e.role}</span>
                    <span className="fw-semibold">{fmt(e.salary)} so'm</span>
                  </div>
                ))}
                {seller.employees.length > 2 && <div className="text-muted" style={{ fontSize: 10 }}>+{seller.employees.length - 2} ta</div>}
              </div>

              <div className="d-flex gap-2 mt-auto pt-2 border-top">
                <button className="btn btn-sm btn-light flex-fill" onClick={() => { setSelectedSeller(seller); setShowOffcanvas(true); }}>
                  <i className="bi bi-eye"></i> Batafsil
                </button>
                <button className="btn btn-sm btn-primary-gradient flex-fill">
                  <i className="bi bi-cash"></i> To'lov
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* OFFCANVAS - Seller batafsil */}
      <Offcanvas show={showOffcanvas} onHide={() => setShowOffcanvas(false)} placement="end" backdrop="static" scroll={true} style={{ width: 'min(600px, 95%)' }}>
        <Offcanvas.Header closeButton className="border-bottom">
          <Offcanvas.Title className="fs-5 fw-bold">{selectedSeller?.name}</Offcanvas.Title>
        </Offcanvas.Header>
        <Offcanvas.Body style={{ maxHeight: '80vh', overflowY: 'auto' }}>
          {selectedSeller && (
            <>
              <Nav variant="tabs" className="mb-3">
                <Nav.Item><Nav.Link eventKey="info" active>Info</Nav.Link></Nav.Item>
                <Nav.Item><Nav.Link eventKey="contract">Shartnoma</Nav.Link></Nav.Item>
                <Nav.Item><Nav.Link eventKey="employees">Hodimlar</Nav.Link></Nav.Item>
                <Nav.Item><Nav.Link eventKey="transactions">Tranzaksiyalar</Nav.Link></Nav.Item>
                <Nav.Item><Nav.Link eventKey="plan">Premium plan</Nav.Link></Nav.Item>
              </Nav>

              {/* Info */}
              <div className="mb-4">
                <h6 className="fw-bold border-bottom pb-2">Tashkilot haqida</h6>
                <div className="row g-2">
                  <div className="col-6"><small className="text-muted d-block">To'liq nomi</small><div className="fw-semibold">{selectedSeller.legalName}</div></div>
                  <div className="col-3"><small className="text-muted d-block">INN</small><div>{selectedSeller.inn}</div></div>
                  <div className="col-3"><small className="text-muted d-block">Komissiya</small><div className="fw-semibold">{selectedSeller.commissionRate}%</div></div>
                  <div className="col-6"><small className="text-muted d-block">Telefon</small><div>{selectedSeller.phone}</div></div>
                  <div className="col-6"><small className="text-muted d-block">Email</small><div>{selectedSeller.email}</div></div>
                  <div className="col-6"><small className="text-muted d-block">Jami yechilgan</small><div className="fw-bold text-primary">{fmt(selectedSeller.totalWithdrawn)} so'm</div></div>
                  <div className="col-6"><small className="text-muted d-block">Qo'shilgan</small><div>{selectedSeller.joinedAt}</div></div>
                </div>
              </div>

              {/* Contract */}
              <div className="mb-4">
                <h6 className="fw-bold border-bottom pb-2">Shartnoma</h6>
                <div className="row g-2">
                  <div className="col-6"><small className="text-muted d-block">Turi</small><div className="fw-semibold">{selectedSeller.contract.type}</div></div>
                  <div className="col-6"><small className="text-muted d-block">Status</small><span className="chip chip-success" style={{ fontSize: 9 }}>{selectedSeller.contract.status}</span></div>
                  <div className="col-6"><small className="text-muted d-block">Boshlanish</small><div>{selectedSeller.contract.startDate}</div></div>
                  <div className="col-6"><small className="text-muted d-block">Tugash</small><div>{selectedSeller.contract.endDate}</div></div>
                  <div className="col-6"><small className="text-muted d-block">Ulush</small><div className="fw-bold text-success">{selectedSeller.contract.share}%</div></div>
                  <div className="col-6"><small className="text-muted d-block">Hujjat</small><div className="fw-semibold" style={{ color: '#4f46e5' }}>{selectedSeller.contract.document}</div></div>
                </div>
              </div>

              {/* Employees */}
              <div className="mb-4">
                <h6 className="fw-bold border-bottom pb-2">Hodimlar ({selectedSeller.employees.length})</h6>
                {selectedSeller.employees.length === 0 ? (
                  <div className="text-muted small">Hodimlar yo'q</div>
                ) : (
                  <div className="table-responsive">
                    <table className="data-table">
                      <thead><tr><th>Ism</th><th>Lavozim</th><th>Telefon</th><th>Oylik</th><th>Status</th></tr></thead>
                      <tbody>
                        {selectedSeller.employees.map(e => (
                          <tr key={e.id}>
                            <td className="fw-semibold">{e.name}</td>
                            <td>{e.role}</td>
                            <td className="text-muted small">{e.phone}</td>
                            <td className="fw-bold">{fmt(e.salary)} so'm</td>
                            <td><span className="chip chip-success" style={{ fontSize: 9 }}>{e.status}</span></td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>

              {/* Transactions */}
              <div className="mb-4">
                <h6 className="fw-bold border-bottom pb-2">Tranzaksiyalar ({selectedSeller.transactions.length})</h6>
                <div className="table-responsive">
                  <table className="data-table">
                    <thead><tr><th>ID</th><th>Turi</th><th>Summa</th><th>Komissiya</th><th>Net</th><th>Sana</th><th>Status</th><th>Izoh</th></tr></thead>
                    <tbody>
                      {selectedSeller.transactions.map(t => (
                        <tr key={t.id}>
                          <td style={{ color: '#4f46e5', fontSize: 11 }}>{t.id}</td>
                          <td><span className={`chip ${t.type === 'payout' || t.type === 'withdrawal' ? 'chip-success' : t.type === 'commission' ? 'chip-info' : 'chip-danger'}`} style={{ fontSize: 9 }}>{t.type}</span></td>
                          <td className={`fw-bold ${t.amount > 0 ? 'text-success' : 'text-danger'}`}>{t.amount > 0 ? '+' : ''}{fmt(t.amount)} so'm</td>
                          <td className="text-muted">{fmt(t.fee)}</td>
                          <td className="fw-semibold">{fmt(t.netAmount)} so'm</td>
                          <td>{t.date}</td>
                          <td><span className={`chip ${t.status === 'Completed' ? 'chip-success' : t.status === 'Pending' ? 'chip-warning' : 'chip-danger'}`} style={{ fontSize: 9 }}>{t.status}</span></td>
                          <td style={{ fontSize: 10 }}>{t.note}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Premium Plan */}
              {selectedSeller.plan && (
                <div>
                  <h6 className="fw-bold border-bottom pb-2">Premium Plan</h6>
                  <div className="p-3 rounded" style={{ background: 'linear-gradient(135deg, #fef3c7, #fde68a)' }}>
                    <div className="d-flex justify-content-between align-items-center">
                      <div className="fw-bold fs-5" style={{ color: '#92400e' }}>{selectedSeller.plan.name}</div>
                      <span className="chip chip-success" style={{ fontSize: 9 }}>{selectedSeller.plan.active ? 'Active' : 'Inactive'}</span>
                    </div>
                    <div className="small" style={{ color: '#713f12' }}>{fmt(selectedSeller.plan.price)}/oy · {selectedSeller.plan.discount}% chegirma</div>
                    <ul className="mt-2 mb-0 small" style={{ color: '#713f12' }}>
                      {selectedSeller.plan.features.map((f: string, i: number) => <li key={i}>{f}</li>)}
                    </ul>
                  </div>
                </div>
              )}
            </>
          )}
        </Offcanvas.Body>
        <div className="border-top p-3 d-flex gap-2">
          <Button variant="light" onClick={() => setShowOffcanvas(false)} className="flex-fill">Yopish</Button>
          <Button variant="primary" className="btn-primary-gradient flex-fill" onClick={() => alert("To'lov yuborildi!")}><i className="bi bi-cash me-1"></i>To'lov yuborish</Button>
        </div>
      </Offcanvas>
    </div>
  );
}