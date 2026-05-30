import { router, usePage } from '@inertiajs/react';

interface Cat {
  id: number;
  name: string;
  nameRu?: string;
  nameEn?: string;
  slug?: string;
  active: boolean;
  itemsCount: number;
  createUrl?: string;
  editUrl?: string;
  toggleUrl?: string;
  destroyUrl?: string;
}

export default function StationeryCategories() {
  const { categories = [] } = usePage<{ categories?: Cat[] }>().props;
  const createUrl = categories[0]?.createUrl || '/a122/stationery-categories/create';

  const destroy = (category: Cat) => {
    if (!category.destroyUrl || !confirm(`${category.name} kategoriyasini o'chirasizmi?`)) return;
    router.delete(category.destroyUrl, { preserveScroll: true });
  };

  const toggle = (category: Cat) => {
    if (!category.toggleUrl) return;
    router.patch(category.toggleUrl, {}, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">Kanstovar kategoriyalari</h1>
          <p className="page-subtitle">Jami {categories.length} ta kategoriya</p>
        </div>
        <a className="btn btn-primary-gradient" href={createUrl}><i className="bi bi-plus-lg me-1"></i>Yangi kategoriya</a>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Nomi</th><th>Slug</th><th>Mahsulotlar</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {categories.map((category) => (
                <tr key={category.id}>
                  <td className="fw-semibold" style={{ color: '#4f46e5' }}>#{category.id}</td>
                  <td>
                    <div className="fw-semibold">{category.name}</div>
                    <div className="text-muted small">{[category.nameRu, category.nameEn].filter(Boolean).join(' / ')}</div>
                  </td>
                  <td className="text-muted">{category.slug || '—'}</td>
                  <td>{category.itemsCount} ta</td>
                  <td>
                    <button className={`chip border-0 ${category.active ? 'chip-success' : 'chip-gray'}`} onClick={() => toggle(category)}>
                      {category.active ? 'Faol' : 'Nofaol'}
                    </button>
                  </td>
                  <td>
                    <a className="btn btn-sm btn-light me-1" href={category.editUrl || '#'}><i className="bi bi-pencil"></i></a>
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(category)} disabled={category.itemsCount > 0}>
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
