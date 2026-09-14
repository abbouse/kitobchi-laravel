import { router, usePage } from '@inertiajs/react';

type HubApplication = {
  id: number;
  name: string;
  phone: string;
  region: string;
  position?: string | null;
  tashkent: 'yes' | 'no' | 'unsure';
  status: string;
  note?: string | null;
  date?: string;
  statusUrl: string;
  destroyUrl: string;
};

const STATUS_LABELS: Record<string, string> = {
  new: 'Yangi',
  reviewed: "Ko'rildi",
  contacted: "Bog'lanildi",
  closed: 'Yopilgan',
};

const STATUS_CHIP: Record<string, string> = {
  new: 'chip-info',
  reviewed: 'chip-warning',
  contacted: 'chip-success',
  closed: 'chip-gray',
};

const TASHKENT_LABELS: Record<string, string> = {
  yes: 'Ha',
  no: "Yo'q",
  unsure: 'Bilmayman',
};

const TASHKENT_CHIP: Record<string, string> = {
  yes: 'chip-success',
  no: 'chip-danger',
  unsure: 'chip-gray',
};

export default function HubApplications() {
  const { hubApplications = [] } = usePage<{ hubApplications?: HubApplication[] }>().props;

  const setStatus = (app: HubApplication, status: string) =>
    router.patch(app.statusUrl, { status }, { preserveScroll: true });

  const destroy = (app: HubApplication) => {
    if (!confirm(`${app.name} arizasi o'chirilsinmi?`)) return;
    router.delete(app.destroyUrl, { preserveScroll: true });
  };

  const newCount = hubApplications.filter((a) => a.status === 'new').length;

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Hub arizalari</h1>
          <p className="page-subtitle">
            Jami {hubApplications.length} ta ariza{newCount > 0 ? ` · ${newCount} ta yangi` : ''}
          </p>
        </div>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Ism</th>
                <th>Telefon</th>
                <th>Viloyat</th>
                <th>Lavozim</th>
                <th>Toshkent</th>
                <th>Holat</th>
                <th>Sana</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {hubApplications.map((app) => (
                <tr key={app.id}>
                  <td className="fw-semibold" style={{ color: '#0B0342' }}>#{app.id}</td>
                  <td className="fw-semibold">{app.name}</td>
                  <td><a href={`tel:${app.phone}`} style={{ textDecoration: 'none' }}>{app.phone}</a></td>
                  <td>{app.region}</td>
                  <td>{app.position || <span className="text-muted">—</span>}</td>
                  <td>
                    <span className={`chip ${TASHKENT_CHIP[app.tashkent] ?? 'chip-gray'}`} style={{ fontSize: 10 }}>
                      {TASHKENT_LABELS[app.tashkent] ?? app.tashkent}
                    </span>
                  </td>
                  <td>
                    <select
                      className={`form-select form-select-sm chip ${STATUS_CHIP[app.status] ?? 'chip-gray'}`}
                      style={{ width: 130, fontWeight: 600 }}
                      value={app.status}
                      onChange={(e) => setStatus(app, e.target.value)}
                    >
                      {Object.entries(STATUS_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                      ))}
                    </select>
                  </td>
                  <td className="text-muted small">{app.date}</td>
                  <td>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(app)}>
                      <i className="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              ))}
              {hubApplications.length === 0 ? (
                <tr>
                  <td colSpan={9} className="text-center text-muted py-5">Hali ariza yo&apos;q</td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
