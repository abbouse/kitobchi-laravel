import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Modal, Button } from 'react-bootstrap';
import PaginationControls, { useClientPagination } from '../components/PaginationControls';

interface Cat {
  id: number;
  name: string;
  nameUz?: string;
  nameRu?: string;
  nameEn?: string;
  nameJa?: string;
  icon?: string | null;
  slug?: string;
  active: boolean;
  ofdIkpuCode?: string | null;
  ofdPackageCode?: string | null;
  itemsCount: number;
  updateUrl?: string;
  toggleUrl?: string;
  destroyUrl?: string;
}

export default function StationeryCategories() {
  const { categories = [] } = usePage<{ categories?: Cat[] }>().props;
  const [editing, setEditing] = useState<Partial<Cat> | null>(null);
  const pagination = useClientPagination(categories, 30);

  const destroy = (category: Cat) => {
    if (!category.destroyUrl || !confirm(`${category.name} kategoriyasini o'chirasizmi?`)) return;
    router.delete(category.destroyUrl, { preserveScroll: true });
  };

  const toggle = (category: Cat) => category.toggleUrl && router.patch(category.toggleUrl, {}, { preserveScroll: true });

  return (
    <div>
      <div className="page-head">
        <div><h1 className="page-title">Kanstovar kategoriyalari</h1><p className="page-subtitle">Jami {categories.length} ta kategoriya</p></div>
        <button className="btn btn-primary-gradient" onClick={() => setEditing({ active: true })}><i className="bi bi-plus-lg me-1"></i>Kategoriya qo'shish</button>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
            <thead><tr><th>ID</th><th>Nomi</th><th>Slug</th><th>Mahsulotlar</th><th>Holat</th><th>Amallar</th></tr></thead>
            <tbody>
              {pagination.paginated.map((category) => (
                <tr key={category.id}>
                  <td className="fw-semibold text-primary">#{category.id}</td>
                  <td><div className="fw-semibold">{category.icon ? `${category.icon} ` : ''}{category.name}</div><div className="text-muted small">{[category.nameRu, category.nameEn, category.nameJa].filter(Boolean).join(' / ')}</div></td>
                  <td className="text-muted">{category.slug || '—'}</td>
                  <td>{category.itemsCount} ta</td>
                  <td><button className={`chip border-0 ${category.active ? 'chip-success' : 'chip-gray'}`} onClick={() => toggle(category)}>{category.active ? 'Faol' : 'Nofaol'}</button></td>
                  <td><div className="d-flex gap-2"><button className="btn btn-sm btn-light" onClick={() => setEditing(category)}><i className="bi bi-pencil"></i></button><button className="btn btn-sm btn-light text-danger" onClick={() => destroy(category)} disabled={category.itemsCount > 0}><i className="bi bi-trash"></i></button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <PaginationControls {...pagination} onPageChange={pagination.setPage} />
      </div>
      <CategoryFormModal category={editing} baseUrl="/boshqaruv/stationery-categories" onHide={() => setEditing(null)} />
    </div>
  );
}

function CategoryFormModal({ category, baseUrl, onHide }: { category: Partial<Cat> | null; baseUrl: string; onHide: () => void }) {
  const isEdit = !!category?.id;
  const [active, setActive] = useState(category?.active ?? true);
  useEffect(() => setActive(category?.active ?? true), [category]);

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    form.set('is_active', active ? '1' : '0');
    if (isEdit) form.append('_method', 'put');
    router.post(isEdit ? String(category?.updateUrl) : baseUrl, form, { preserveScroll: true, onSuccess: onHide });
  };

  return (
    <Modal show={!!category} onHide={onHide} centered>
      <form onSubmit={submit}>
        <Modal.Header closeButton><Modal.Title className="fs-5 fw-bold">{isEdit ? 'Kategoriyani tahrirlash' : "Kategoriya qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <Field name="name_uz" label="Nomi UZ" defaultValue={category?.nameUz || category?.name} required />
          <Field name="name_ru" label="Nomi RU" defaultValue={category?.nameRu} required />
          <Field name="name_en" label="Nomi EN" defaultValue={category?.nameEn} />
          <Field name="name_ja" label="Nomi JA" defaultValue={category?.nameJa} />
          <Field name="icon" label="Icon" defaultValue={category?.icon} />
          <div className="col-md-6 d-flex align-items-end"><label className="form-check mb-2"><input className="form-check-input" type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} /><span className="form-check-label ms-2">Faol</span></label></div>
          <Field name="ofd_ikpu_code" label="OFD IKPU (MXIK) kodi" defaultValue={category?.ofdIkpuCode} />
          <Field name="ofd_package_code" label="OFD qadoq kodi" defaultValue={category?.ofdPackageCode} />
          <div className="col-12 small text-muted">Fiskal chek uchun. Bo'sh qolsa .env dagi umumiy kanstovar kodi ishlatiladi. Kodlarni tasnif.soliq.uz dan oling.</div>
        </div></Modal.Body>
        <Modal.Footer><Button variant="light" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function Field({ name, label, defaultValue, required }: { name: string; label: string; defaultValue?: string | null; required?: boolean }) {
  return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} defaultValue={defaultValue || ''} required={required} className="form-control" /></div>;
}
