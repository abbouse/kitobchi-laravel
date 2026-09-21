import CategoryImageField, { categoryImageUrl } from '../components/CategoryImageField';
import { PageCrumbs } from '../Layout';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from 'react-bootstrap';
import Modal from '../components/AppModal';
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
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div><h4 className="main-title mb-0">Kanstovar kategoriyalari</h4><PageCrumbs /><p className="mb-0 text-secondary">Jami {categories.length} ta kategoriya</p></div>
        <button className="btn btn-primary" onClick={() => setEditing({ active: true })}><i className="ti ti-plus me-1"></i>Kategoriya qo'shish</button>
      </div>

      <div className="card">
        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead><tr><th>ID</th><th>Nomi</th><th>Slug</th><th>Mahsulotlar</th><th>Holat</th><th>Amallar</th></tr></thead>
              <tbody>
                {pagination.paginated.map((category) => (
                  <tr key={category.id}>
                    <td className="f-w-600 text-nowrap">#{category.id}</td>
                    <td><div className="d-flex align-items-center gap-2"><div className="b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 w-30 h-30">{categoryImageUrl(category.icon) ? <img className="w-100 h-100 object-fit-cover" src={categoryImageUrl(category.icon)!} alt="" /> : <span className="f-w-600 f-s-14 text-primary-dark">{(category.name || '?').slice(0, 1).toUpperCase()}</span>}</div><div><div className="title-text text-nowrap">{category.name}</div><div className="text-muted f-s-13">{[category.nameRu, category.nameEn, category.nameJa].filter(Boolean).join(' / ')}</div></div></div></td>
                    <td className="text-muted">{category.slug || '—'}</td>
                    <td className="text-end text-nowrap">{category.itemsCount} ta</td>
                    <td><button className={`badge border-0 ${category.active ? 'text-light-success' : 'text-light-secondary'}`} onClick={() => toggle(category)}>{category.active ? 'Faol' : 'Nofaol'}</button></td>
                    <td><div className="d-flex gap-2"><button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => setEditing(category)}><i className="ti ti-pencil"></i></button><button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(category)} disabled={category.itemsCount > 0}><i className="ti ti-trash"></i></button></div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <PaginationControls {...pagination} onPageChange={pagination.setPage} />
        </div>
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
        <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{isEdit ? 'Kategoriyani tahrirlash' : "Kategoriya qo'shish"}</Modal.Title></Modal.Header>
        <Modal.Body><div className="row g-3">
          <Field name="name_uz" label="Nomi UZ" defaultValue={category?.nameUz || category?.name} required />
          <Field name="name_ru" label="Nomi RU" defaultValue={category?.nameRu} required />
          <Field name="name_en" label="Nomi EN" defaultValue={category?.nameEn} />
          <Field name="name_ja" label="Nomi JA" defaultValue={category?.nameJa} />
          <CategoryImageField current={category?.icon} />
          <div className="col-md-6 d-flex align-items-end"><label className="form-check mb-2"><input className="form-check-input" type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} /><span className="form-check-label ms-2">Faol</span></label></div>
          <Field name="ofd_ikpu_code" label="OFD IKPU (MXIK) kodi" defaultValue={category?.ofdIkpuCode} />
          <Field name="ofd_package_code" label="OFD qadoq kodi" defaultValue={category?.ofdPackageCode} />
          <div className="col-12 f-s-13 text-muted">Fiskal chek uchun. Bo'sh qolsa .env dagi umumiy kanstovar kodi ishlatiladi. Kodlarni tasnif.soliq.uz dan oling.</div>
        </div></Modal.Body>
        <Modal.Footer><Button variant="light-secondary" onClick={onHide}>Bekor</Button><Button type="submit" variant="primary">{isEdit ? 'Saqlash' : "Qo'shish"}</Button></Modal.Footer>
      </form>
    </Modal>
  );
}

function Field({ name, label, defaultValue, required }: { name: string; label: string; defaultValue?: string | null; required?: boolean }) {
  return <div className="col-md-6"><label className="form-label">{label}</label><input name={name} defaultValue={defaultValue || ''} required={required} className="form-control" /></div>;
}
