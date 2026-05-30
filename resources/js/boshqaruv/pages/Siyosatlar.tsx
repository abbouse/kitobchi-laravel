import { router, usePage } from '@inertiajs/react';

interface Policy {
  id: number;
  title: string;
  slug: string;
  status: string;
  showInApp: boolean;
  sortOrder: number;
  createUrl?: string;
  editUrl?: string;
  toggleUrl?: string;
  destroyUrl?: string;
}

export default function Siyosatlar() {
  const { policies = [] } = usePage<{ policies?: Policy[] }>().props;
  const createUrl = '/boshqaruv/siyosatlar';

  const toggle = (policy: Policy) => policy.toggleUrl && router.patch(policy.toggleUrl, {}, { preserveScroll: true });
  const destroy = (policy: Policy) => {
    if (!policy.destroyUrl || !confirm(`${policy.title} siyosati o'chirilsinmi?`)) return;
    router.delete(policy.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Siyosatlar va qoidalar</h1>
          <p className="page-subtitle">Legal sahifalar, appda ko'rinishi va aktiv holat</p>
        </div>
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
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(policy)}><i className="bi bi-trash"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
