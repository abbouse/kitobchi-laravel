import { router, usePage } from '@inertiajs/react';

interface Pub {
  id: number;
  name: string;
  city?: string;
  books: number;
  contact?: string;
  rating?: number;
  image?: string | null;
  createUrl?: string;
  editUrl?: string;
  destroyUrl?: string;
}

export default function Publishers() {
  const { publishers = [] } = usePage<{ publishers?: Pub[] }>().props;
  const createUrl = publishers[0]?.createUrl || '/a122/publishers/create';

  const destroy = (publisher: Pub) => {
    if (!publisher.destroyUrl || !confirm(`${publisher.name} nashriyotini o'chirasizmi?`)) return;
    router.delete(publisher.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Nashriyotlar</h1>
          <p className="page-subtitle">Jami {publishers.length} ta nashriyot</p>
        </div>
        <div className="d-flex gap-2">
          <a className="btn btn-outline-secondary" href="/a122/publishers">Filtr</a>
          <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi nashriyot</a>
        </div>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr><th>Nashriyot</th><th>Kitoblar soni</th><th>Rasm</th><th>Status</th><th>Amallar</th></tr>
            </thead>
            <tbody>
              {publishers.map((publisher, i) => (
                <tr key={publisher.id}>
                  <td>
                    <div className="d-flex align-items-center gap-2">
                      <div className="resource-avatar square" style={{ background: `linear-gradient(135deg, hsl(${i * 49 + 200},70%,55%), hsl(${i * 49 + 240},70%,45%))` }}>
                        {publisher.image ? <img src={publisher.image} alt={publisher.name} /> : <i className="bi bi-building"></i>}
                      </div>
                      <div>
                        <div className="fw-semibold">{publisher.name}</div>
                        <div className="text-muted small">#{publisher.id}</div>
                      </div>
                    </div>
                  </td>
                  <td className="fw-semibold">{publisher.books}</td>
                  <td><span className={`chip ${publisher.image ? 'chip-success' : 'chip-gray'}`}>{publisher.image ? 'Bor' : "Yo'q"}</span></td>
                  <td><span className="chip chip-success">Faol</span></td>
                  <td>
                    <a className="btn btn-sm btn-light me-1" href={publisher.editUrl || '#'} title="Tahrirlash"><i className="bi bi-pencil"></i></a>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(publisher)} disabled={publisher.books > 0} title="O'chirish">
                      <i className="bi bi-trash"></i>
                    </button>
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
