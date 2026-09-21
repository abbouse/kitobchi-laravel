import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';

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
  new: 'text-light-info',
  reviewed: 'text-light-warning',
  contacted: 'text-light-success',
  closed: 'text-light-secondary',
};

const TASHKENT_LABELS: Record<string, string> = {
  yes: 'Ha',
  no: "Yo'q",
  unsure: 'Bilmayman',
};

const TASHKENT_CHIP: Record<string, string> = {
  yes: 'text-light-success',
  no: 'text-light-danger',
  unsure: 'text-light-secondary',
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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Hub arizalari</h4><PageCrumbs />
          <p className="mb-0 text-secondary">
            Jami {hubApplications.length} ta ariza{newCount > 0 ? ` · ${newCount} ta yangi` : ''}
          </p>
        </div>
      </div>

      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
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
                    <td className="f-w-600 text-primary">#{app.id}</td>
                    <td className="f-w-600">{app.name}</td>
                    <td><a href={`tel:${app.phone}`} style={{ textDecoration: 'none' }}>{app.phone}</a></td>
                    <td>{app.region}</td>
                    <td>{app.position || <span className="text-muted">—</span>}</td>
                    <td>
                      <span className={`badge ${TASHKENT_CHIP[app.tashkent] ?? 'text-light-secondary'} f-s-10`}>
                        {TASHKENT_LABELS[app.tashkent] ?? app.tashkent}
                      </span>
                    </td>
                    <td>
                      <select
            className={`form-select form-select-sm badge ${STATUS_CHIP[app.status] ?? 'text-light-secondary'} w-130 f-w-600`}
            value={app.status}
            onChange={(e) => setStatus(app, e.target.value)}
           >
                        {Object.entries(STATUS_LABELS).map(([value, label]) => (
                          <option key={value} value={value}>{label}</option>
                        ))}
                      </select>
                    </td>
                    <td className="text-muted f-s-13">{app.date}</td>
                    <td>
                      <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(app)}>
                        <i className="ti ti-trash"></i>
                      </button>
                    </td>
                  </tr>
                ))}
                {hubApplications.length === 0 ? (
                  <tr>
                    <td colSpan={9} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Hali ariza yo'q</td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </div>
</div>
    </div>
  );
}
