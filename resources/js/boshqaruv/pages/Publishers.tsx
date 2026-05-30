import { router, usePage } from '@inertiajs/react';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

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
  const pagination = useClientPagination(publishers, 30);

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
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead>
              <tr><th>Nashriyot</th><th>Kitoblar soni</th><th>Rasm</th><th>Status</th><th>Amallar</th></tr>
            </thead>
            <tbody>
              {pagination.paginated.map((publisher, i) => (
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
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(publisher)} disabled={publisher.books > 0} title="O'chirish">
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
    </div>
  );
}
