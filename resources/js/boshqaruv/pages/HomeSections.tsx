import { FormEvent, useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import FormAction from '../components/FormAction';

type Section = {
  id: number;
  key: string;
  type: string;
  typeLabel: string;
  titleUz?: string | null;
  titleRu?: string | null;
  titleEn?: string | null;
  isActive: boolean;
  position: number;
  itemLimit: number;
  settings: { category_id?: number; collection_id?: number };
  isCustom: boolean;
  updateUrl: string;
  deleteUrl: string;
};

type Option = { id: number; name: string };

// Mahsulot ro'yxati bo'lmagan bo'limlar — "nechta" sozlamasi kerak emas
const NO_LIMIT = ['center_banners', 'shops'];

const typeIcon: Record<string, string> = {
  genres: 'ti-category',
  recently_viewed: 'ti-history',
  for_you: 'ti-sparkles',
  bestsellers: 'ti-flame',
  center_banners: 'ti-photo',
  new_arrivals: 'ti-book-2',
  coming_soon: 'ti-calendar-time',
  discount_ending: 'ti-discount-2',
  collections: 'ti-stack-2',
  club_trending: 'ti-messages',
  category: 'ti-bookmarks',
  collection: 'ti-layout-list',
  shops: 'ti-building-store',
};

export default function HomeSections() {
  const { sections = [], categories = [], collections = [], storeUrl, reorderUrl } = usePage<{
    sections: Section[];
    categories: Option[];
    collections: Option[];
    storeUrl: string;
    reorderUrl: string;
  }>().props;

  // Do'konlar doim eng pastda — tartibga kirmaydi
  const [order, setOrder] = useState<Section[]>(sections.filter((s) => s.type !== 'shops'));
  const shops = sections.find((s) => s.type === 'shops');
  const [dirty, setDirty] = useState(false);
  const [newType, setNewType] = useState<'category' | 'collection'>('category');

  useEffect(() => {
    setOrder(sections.filter((s) => s.type !== 'shops'));
    setDirty(false);
  }, [sections]);

  const move = (index: number, dir: -1 | 1) => {
    const target = index + dir;
    if (target < 0 || target >= order.length) return;
    const next = [...order];
    [next[index], next[target]] = [next[target], next[index]];
    setOrder(next);
    setDirty(true);
  };

  const saveOrder = () => {
    router.post(reorderUrl, { ids: order.map((s) => s.id) }, { preserveScroll: true });
  };

  const toggle = (s: Section) => {
    router.put(s.updateUrl, { is_active: !s.isActive }, { preserveScroll: true });
  };

  const submitUpdate = (s: Section) => (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.put(s.updateUrl, Object.fromEntries(new FormData(event.currentTarget)) as Record<string, string>, {
      preserveScroll: true,
    });
  };

  const submitCreate = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post(storeUrl, new FormData(event.currentTarget), { preserveScroll: true });
  };

  const remove = (s: Section) => {
    if (!confirm(`"${s.titleUz || s.typeLabel}" bo'limi o'chirilsinmi?`)) return;
    router.delete(s.deleteUrl, { preserveScroll: true });
  };

  const settingName = (s: Section) => {
    if (s.type === 'category') return categories.find((c) => c.id === s.settings?.category_id)?.name;
    if (s.type === 'collection') return collections.find((c) => c.id === s.settings?.collection_id)?.name;
    return null;
  };

  const editForm = (s: Section) => (
    <div className="row g-3">
      <div className="col-md-4">
        <label className="form-label">Sarlavha (uz)</label>
        <input name="title_uz" className="form-control" defaultValue={s.titleUz || ''} />
      </div>
      <div className="col-md-4">
        <label className="form-label">Sarlavha (ru)</label>
        <input name="title_ru" className="form-control" defaultValue={s.titleRu || ''} />
      </div>
      <div className="col-md-4">
        <label className="form-label">Sarlavha (en)</label>
        <input name="title_en" className="form-control" defaultValue={s.titleEn || ''} />
      </div>
      {!NO_LIMIT.includes(s.type) ? (
        <div className="col-md-4">
          <label className="form-label">Nechta ko'rsatilsin</label>
          <input name="item_limit" type="number" min={4} max={30} className="form-control" defaultValue={s.itemLimit} />
        </div>
      ) : null}
      {s.type === 'category' ? (
        <div className="col-md-8">
          <label className="form-label">Janr</label>
          <select name="category_id" className="form-select" defaultValue={s.settings?.category_id || ''}>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
        </div>
      ) : null}
      {s.type === 'collection' ? (
        <div className="col-md-8">
          <label className="form-label">To'plam</label>
          <select name="collection_id" className="form-select" defaultValue={s.settings?.collection_id || ''}>
            {collections.map((c) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
        </div>
      ) : null}
    </div>
  );

  const row = (s: Section, index: number | null) => (
    <div
      key={s.id}
      className={`d-flex align-items-center gap-3 py-3 px-2 b-b-1-light ${s.isActive ? '' : 'opacity-50'}`}
    >
      {index !== null ? (
        <div className="d-flex flex-column">
          <button type="button" className="btn btn-sm btn-light-secondary icon-btn w-30 h-30 mb-1" disabled={index === 0} onClick={() => move(index, -1)} title="Yuqoriga">
            <i className="ti ti-chevron-up"></i>
          </button>
          <button type="button" className="btn btn-sm btn-light-secondary icon-btn w-30 h-30" disabled={index === order.length - 1} onClick={() => move(index, 1)} title="Pastga">
            <i className="ti ti-chevron-down"></i>
          </button>
        </div>
      ) : (
        <span className="w-30 text-center text-muted"><i className="ti ti-pin"></i></span>
      )}
      <span className="h-40 w-40 d-flex-center b-r-10 f-s-20 flex-shrink-0 text-light-primary">
        <i className={`ti ${typeIcon[s.type] || 'ti-layout'}`}></i>
      </span>
      <div className="flex-grow-1 min-w-0">
        <div className="f-w-600">{s.titleUz || s.typeLabel}</div>
        <div className="f-s-12 text-secondary">
          {s.typeLabel}
          {settingName(s) ? ` · ${settingName(s)}` : ''}
          {!NO_LIMIT.includes(s.type) && s.type !== 'genres' ? ` · ${s.itemLimit} ta` : ''}
        </div>
      </div>
      <div className="form-check form-switch m-0" title={s.isActive ? "O'chirish" : 'Yoqish'}>
        <input className="form-check-input" type="checkbox" checked={s.isActive} onChange={() => toggle(s)} />
      </div>
      <FormAction
        label=""
        icon="ti ti-edit"
        variant="light-primary"
        title={`Bo'limni tahrirlash — ${s.typeLabel}`}
        submitLabel="Saqlash"
        modalSize="lg"
        onSubmit={submitUpdate(s)}
      >
        {editForm(s)}
      </FormAction>
      {s.isCustom ? (
        <button type="button" className="btn btn-sm btn-light-danger icon-btn w-30 h-30" onClick={() => remove(s)} title="O'chirish">
          <i className="ti ti-trash"></i>
        </button>
      ) : (
        <span className="w-30"></span>
      )}
    </div>
  );

  return (
    <div className="container-fluid py-3">
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-3">
        <div>
          <h4 className="main-title mb-0">Ilova bosh sahifasi</h4>
          <PageCrumbs />
          <p className="mb-0 text-secondary">
            Mijozlar ilovasidagi bo'limlar tartibi. O'zgarish ilovada 1 daqiqa ichida ko'rinadi — yangi versiya chiqarish shart emas.
          </p>
        </div>
        <div className="d-flex gap-2">
          <FormAction
            label="Bo'lim qo'shish"
            icon="ti ti-plus"
            variant="primary"
            title="Yangi bo'lim"
            submitLabel="Qo'shish"
            modalSize="lg"
            onSubmit={submitCreate}
          >
            <div className="row g-3">
              <div className="col-12">
                <label className="form-label">Turi</label>
                <select name="type" className="form-select" value={newType} onChange={(e) => setNewType(e.target.value as 'category' | 'collection')}>
                  <option value="category">Tanlangan janr kitoblari</option>
                  <option value="collection">Tanlangan to'plam kitoblari</option>
                </select>
              </div>
              {newType === 'category' ? (
                <div className="col-12">
                  <label className="form-label">Janr</label>
                  <select name="category_id" className="form-select" required>
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
              ) : (
                <div className="col-12">
                  <label className="form-label">To'plam</label>
                  <select name="collection_id" className="form-select" required>
                    {collections.map((c) => (
                      <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                  </select>
                </div>
              )}
              <div className="col-md-4">
                <label className="form-label">Sarlavha (uz)</label>
                <input name="title_uz" className="form-control" required placeholder="Masalan: Bolalar uchun" />
              </div>
              <div className="col-md-4">
                <label className="form-label">Sarlavha (ru)</label>
                <input name="title_ru" className="form-control" />
              </div>
              <div className="col-md-4">
                <label className="form-label">Sarlavha (en)</label>
                <input name="title_en" className="form-control" />
              </div>
              <div className="col-md-4">
                <label className="form-label">Nechta ko'rsatilsin</label>
                <input name="item_limit" type="number" min={4} max={30} defaultValue={12} className="form-control" />
              </div>
            </div>
          </FormAction>
        </div>
      </div>

      <div className="row g-3">
        <div className="col-xl-8">
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between">
              <h5 className="f-w-600 mb-0">Bo'limlar tartibi</h5>
              <button type="button" className="btn btn-sm btn-primary" disabled={!dirty} onClick={saveOrder}>
                <i className="ti ti-device-floppy me-1"></i>Tartibni saqlash
              </button>
            </div>
            <div className="card-body pt-0">
              {order.map((s, i) => row(s, i))}
              {shops ? row(shops, null) : null}
            </div>
          </div>
        </div>
        <div className="col-xl-4">
          <div className="card">
            <div className="card-body f-s-13 text-secondary">
              <h6 className="f-w-600 text-dark">Qanday ishlaydi</h6>
              <ul className="ps-3 mb-0">
                <li>Tepada doim bannerlar va faol buyurtmalar turadi, keyin shu ro'yxat tartibida bo'limlar.</li>
                <li>Har kitob bitta kartochkada chiqadi (bir nechta do'konda bo'lsa — eng yaxshi taklif).</li>
                <li>Bo'sh bo'lim (masalan predzakaz yo'q bo'lsa "Tez orada") ilovada ko'rinmaydi.</li>
                <li>"Siz uchun" va "Oxirgi ko'rilganlar" har mijozga alohida.</li>
                <li>Do'konlar doim eng pastda; mijoz pastga tushgan sari 4 tadan yuklanadi.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
