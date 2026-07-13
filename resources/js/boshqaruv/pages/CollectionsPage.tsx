import { ChangeEvent, DragEvent, FormEvent, useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Form, Modal } from 'react-bootstrap';

type ProductType = 'book' | 'stationery';
type TranslateLocale = 'ru' | 'en' | 'ja';

type CollectionItem = {
  id?: number;
  productId: number;
  productType: ProductType;
  name: string;
  author?: string | null;
  seller?: string | null;
  quantity: number;
  sortOrder: number;
  price: number;
  stock: number;
  available: boolean;
  image?: string | null;
};

type CollectionRow = {
  id: number;
  slug: string;
  isActive: boolean;
  festiveEffect?: boolean;
  sortOrder: number;
  customTotalPrice?: number | null;
  titleUz: string;
  titleRu?: string | null;
  titleEn?: string | null;
  titleJa?: string | null;
  subtitleUz?: string | null;
  subtitleRu?: string | null;
  subtitleEn?: string | null;
  subtitleJa?: string | null;
  descriptionUz?: string | null;
  descriptionRu?: string | null;
  descriptionEn?: string | null;
  descriptionJa?: string | null;
  heroImage?: string | null;
  gradientFrom: string;
  gradientTo: string;
  buttonBgColor: string;
  buttonTextColor: string;
  baseTotalAmount: number;
  itemCount: number;
  availableItemCount: number;
  totalAmount: number;
  items: CollectionItem[];
  bookSearchUrl: string;
  createUrl: string;
  updateUrl: string;
  toggleUrl: string;
  duplicateUrl: string;
  destroyUrl: string;
};

type SearchProduct = {
  id: number;
  productType: ProductType;
  name: string;
  author?: string | null;
  artikul?: string | null;
  seller?: string | null;
  price: number;
  base_price: number;
  stock: number;
  image?: string | null;
};

type StatusFilter = 'all' | 'active' | 'hidden';
type SortOption = 'sort' | 'name' | 'items' | 'amount' | 'newest';

const fmt = (value: number) => new Intl.NumberFormat('uz-UZ').format(value || 0);
const itemKey = (type: ProductType, id: number) => `${type}-${id}`;
const translateLocaleLabels: Record<TranslateLocale, string> = { ru: 'RU', en: 'EN', ja: 'JA' };
const getCsrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';

const defaultForm = {
  slug: '',
  sortOrder: 0,
  isActive: true,
  festiveEffect: true,
  customTotalPrice: '',
  titleUz: '',
  titleRu: '',
  titleEn: '',
  titleJa: '',
  subtitleUz: '',
  subtitleRu: '',
  subtitleEn: '',
  subtitleJa: '',
  descriptionUz: '',
  descriptionRu: '',
  descriptionEn: '',
  descriptionJa: '',
  gradientFrom: '#FF8A3D',
  gradientTo: '#FF5A3D',
  buttonBgColor: '#121212',
  buttonTextColor: '#FFFFFF',
};

const TypeBadge = ({ type }: { type: ProductType }) =>
  type === 'stationery' ? (
    <span className="chip chip-purple"><i className="bi bi-pencil-fill me-1"></i>Kanselyariya</span>
  ) : (
    <span className="chip chip-gray"><i className="bi bi-book me-1"></i>Kitob</span>
  );

export default function CollectionsPage() {
  const { collections = [], errors = {}, translateUrl = '/boshqaruv/content/translate' } = usePage<{
    collections?: CollectionRow[];
    translateUrl?: string;
    errors?: Record<string, string>;
  }>().props;
  const baseSearchUrl = collections[0]?.bookSearchUrl || '/boshqaruv/collections/book-search';
  const baseCreateUrl = collections[0]?.createUrl || '/boshqaruv/collections';

  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<CollectionRow | null>(null);
  const [heroFile, setHeroFile] = useState<File | null>(null);
  const [heroPreview, setHeroPreview] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [searchType, setSearchType] = useState<ProductType>('book');
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchProduct[]>([]);
  const [items, setItems] = useState<CollectionItem[]>([]);
  const [form, setForm] = useState(defaultForm);
  const [dragIndex, setDragIndex] = useState<number | null>(null);
  const [translatingLocales, setTranslatingLocales] = useState<TranslateLocale[]>([]);

  // --- Ro'yxat filtrlari ---
  const [listQuery, setListQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [sortOption, setSortOption] = useState<SortOption>('sort');

  const resetForm = () => {
    setEditing(null);
    setHeroFile(null);
    setHeroPreview(null);
    setSearch('');
    setSearchType('book');
    setSearchResults([]);
    setItems([]);
    setForm(defaultForm);
    setDragIndex(null);
  };

  const hydrateForm = (collection?: CollectionRow | null) => {
    if (!collection) {
      resetForm();
      return;
    }

    setEditing(collection);
    setHeroFile(null);
    setHeroPreview(collection.heroImage || null);
    setSearch('');
    setSearchType('book');
    setSearchResults([]);
    setItems(collection.items.map((item) => ({ ...item, productType: item.productType || 'book' })));
    setForm({
      slug: collection.slug,
      sortOrder: collection.sortOrder,
      isActive: collection.isActive,
      festiveEffect: collection.festiveEffect ?? true,
      customTotalPrice: collection.customTotalPrice ? String(collection.customTotalPrice) : '',
      titleUz: collection.titleUz || '',
      titleRu: collection.titleRu || '',
      titleEn: collection.titleEn || '',
      titleJa: collection.titleJa || '',
      subtitleUz: collection.subtitleUz || '',
      subtitleRu: collection.subtitleRu || '',
      subtitleEn: collection.subtitleEn || '',
      subtitleJa: collection.subtitleJa || '',
      descriptionUz: collection.descriptionUz || '',
      descriptionRu: collection.descriptionRu || '',
      descriptionEn: collection.descriptionEn || '',
      descriptionJa: collection.descriptionJa || '',
      gradientFrom: collection.gradientFrom,
      gradientTo: collection.gradientTo,
      buttonBgColor: collection.buttonBgColor,
      buttonTextColor: collection.buttonTextColor,
    });
  };

  const totalAmount = useMemo(
    () => items.reduce((sum, item) => sum + (item.price || 0) * (item.quantity || 1), 0),
    [items],
  );

  const unavailableCount = useMemo(() => items.filter((item) => !item.available).length, [items]);
  const bookCount = useMemo(() => items.filter((item) => item.productType === 'book').length, [items]);
  const stationeryCount = useMemo(() => items.filter((item) => item.productType === 'stationery').length, [items]);

  const searchProducts = async (query: string, type: ProductType) => {
    const clean = query.trim();
    if (!clean) {
      setSearchResults([]);
      return;
    }

    setSearchLoading(true);
    try {
      const response = await fetch(`${baseSearchUrl}?type=${type}&q=${encodeURIComponent(clean)}`, {
        headers: { Accept: 'application/json' },
      });
      const payload = response.ok ? await response.json() : null;
      setSearchResults(Array.isArray(payload?.data) ? payload.data : []);
    } catch {
      setSearchResults([]);
    } finally {
      setSearchLoading(false);
    }
  };

  useEffect(() => {
    const timer = window.setTimeout(() => {
      searchProducts(search, searchType);
    }, 220);

    return () => window.clearTimeout(timer);
  }, [search, searchType]);

  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      setShowForm(true);
    }
  }, [errors]);

  const addProduct = (product: SearchProduct) => {
    setItems((current) => {
      const existingIndex = current.findIndex(
        (item) => item.productId === product.id && item.productType === product.productType,
      );
      if (existingIndex >= 0) {
        return current.map((item, index) =>
          index === existingIndex ? { ...item, quantity: item.quantity + 1 } : item,
        );
      }

      return [
        ...current,
        {
          productId: product.id,
          productType: product.productType,
          name: product.name,
          author: product.author,
          seller: product.seller,
          quantity: 1,
          sortOrder: current.length,
          price: product.price,
          stock: product.stock,
          available: product.stock > 0,
          image: product.image,
        },
      ];
    });
  };

  const updateItem = (index: number, patch: Partial<CollectionItem>) => {
    setItems((current) => current.map((item, itemIndex) => (itemIndex === index ? { ...item, ...patch } : item)));
  };

  const removeItem = (index: number) => {
    setItems((current) =>
      current.filter((_, itemIndex) => itemIndex !== index).map((item, order) => ({ ...item, sortOrder: order })),
    );
  };

  // --- Drag & drop tartiblash ---
  const onDragStart = (index: number) => setDragIndex(index);
  const onDragOver = (event: DragEvent) => event.preventDefault();
  const onDrop = (index: number) => {
    if (dragIndex === null || dragIndex === index) {
      setDragIndex(null);
      return;
    }
    setItems((current) => {
      const next = [...current];
      const [moved] = next.splice(dragIndex, 1);
      next.splice(index, 0, moved);
      return next.map((item, order) => ({ ...item, sortOrder: order }));
    });
    setDragIndex(null);
  };

  const onHeroChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] || null;
    setHeroFile(file);
    setHeroPreview(file ? URL.createObjectURL(file) : editing?.heroImage || null);
  };

  const translateFromUz = async (targetLocales: TranslateLocale[]) => {
    const texts: Record<string, string> = {};

    if (form.titleUz.trim()) texts.title = form.titleUz.trim();
    if (form.subtitleUz.trim()) texts.subtitle = form.subtitleUz.trim();
    if (form.descriptionUz.trim()) texts.description = form.descriptionUz.trim();

    if (Object.keys(texts).length === 0) {
      window.alert("Avval UZ maydonlarini to'ldiring.");
      return;
    }

    setTranslatingLocales(targetLocales);

    try {
      const response = await fetch(translateUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          source_locale: 'uz',
          target_locales: targetLocales,
          texts,
        }),
      });

      const payload = await response.json().catch(() => null);

      if (!response.ok || payload?.status !== 'success') {
        throw new Error(payload?.message || payload?.errors?.texts?.[0] || 'AI tarjima xatosi');
      }

      setForm((prev) => {
        const next = { ...prev };

        targetLocales.forEach((locale) => {
          const translated = payload.data?.[locale] || {};

          if (translated.title) {
            (next as any)[`title${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = translated.title;
          }
          if (translated.subtitle) {
            (next as any)[`subtitle${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = translated.subtitle;
          }
          if (translated.description) {
            (next as any)[`description${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = translated.description;
          }
        });

        return next;
      });
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'AI tarjima vaqtincha ishlamadi.');
    } finally {
      setTranslatingLocales([]);
    }
  };

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const payload = new FormData();
    payload.append('slug', form.slug);
    payload.append('sort_order', String(form.sortOrder || 0));
    payload.append('is_active', form.isActive ? '1' : '0');
    payload.append('festive_effect', form.festiveEffect ? '1' : '0');
    payload.append('custom_total_price', String((form as any).customTotalPrice || ''));
    payload.append('title_uz', form.titleUz);
    payload.append('title_ru', form.titleRu);
    payload.append('title_en', form.titleEn);
    payload.append('title_ja', form.titleJa);
    payload.append('subtitle_uz', form.subtitleUz);
    payload.append('subtitle_ru', form.subtitleRu);
    payload.append('subtitle_en', form.subtitleEn);
    payload.append('subtitle_ja', form.subtitleJa);
    payload.append('description_uz', form.descriptionUz);
    payload.append('description_ru', form.descriptionRu);
    payload.append('description_en', form.descriptionEn);
    payload.append('description_ja', form.descriptionJa);
    payload.append('gradient_from', form.gradientFrom);
    payload.append('gradient_to', form.gradientTo);
    payload.append('button_bg_color', form.buttonBgColor);
    payload.append('button_text_color', form.buttonTextColor);
    payload.append(
      'items_json',
      JSON.stringify(
        items.map((item, index) => ({
          product_id: item.productId,
          product_type: item.productType,
          quantity: item.quantity,
          sort_order: item.sortOrder ?? index,
        })),
      ),
    );

    if (heroFile) {
      payload.append('hero_image', heroFile);
    }

    const options = {
      preserveScroll: true,
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        setShowForm(false);
        resetForm();
      },
      onError: () => {
        setShowForm(true);
      },
    };

    if (editing?.updateUrl) {
      payload.append('_method', 'put');
      router.post(editing.updateUrl, payload, options);
      return;
    }

    router.post(baseCreateUrl, payload, options);
  };

  const toggle = (collection: CollectionRow) => {
    router.patch(collection.toggleUrl, {}, { preserveScroll: true });
  };

  const duplicate = (collection: CollectionRow) => {
    router.post(collection.duplicateUrl, {}, { preserveScroll: true });
  };

  const destroy = (collection: CollectionRow) => {
    if (!confirm(`${collection.titleUz} to'plami o'chirilsinmi?`)) return;
    router.delete(collection.destroyUrl, { preserveScroll: true });
  };

  // --- Filtrlangan + saralangan ro'yxat ---
  const visibleCollections = useMemo(() => {
    const q = listQuery.trim().toLowerCase();
    let list = collections.filter((collection) => {
      if (statusFilter === 'active' && !collection.isActive) return false;
      if (statusFilter === 'hidden' && collection.isActive) return false;
      if (!q) return true;
      return (
        collection.titleUz.toLowerCase().includes(q) ||
        (collection.slug || '').toLowerCase().includes(q) ||
        (collection.subtitleUz || '').toLowerCase().includes(q)
      );
    });

    list = [...list].sort((a, b) => {
      switch (sortOption) {
        case 'name':
          return a.titleUz.localeCompare(b.titleUz, 'uz');
        case 'items':
          return b.itemCount - a.itemCount;
        case 'amount':
          return b.totalAmount - a.totalAmount;
        case 'newest':
          return b.id - a.id;
        default:
          return a.sortOrder - b.sortOrder || b.id - a.id;
      }
    });

    return list;
  }, [collections, listQuery, statusFilter, sortOption]);

  const totalUnavailable = useMemo(
    () => collections.reduce((sum, item) => sum + (item.itemCount - item.availableItemCount), 0),
    [collections],
  );

  return (
    <div>

      <div className="page-head">
        <div>
          <h1 className="page-title">To'plamlar</h1>
          <p className="page-subtitle">Banner orqali ochiladigan tayyor kitob va kanselyariya to'plamlari, ularning sahifa dizayni</p>
        </div>
        <button
          className="btn btn-primary-gradient"
          onClick={() => {
            hydrateForm(null);
            setShowForm(true);
          }}
        >
          <i className="bi bi-plus-lg me-1"></i>To'plam qo'shish
        </button>
      </div>

      <div className="row g-3 mb-4">
        {[
          { label: 'Jami to‘plam', value: collections.length, icon: 'bi-collection' },
          { label: 'Faol', value: collections.filter((item) => item.isActive).length, icon: 'bi-check-circle' },
          { label: 'Mahsulotlar', value: collections.reduce((sum, item) => sum + item.itemCount, 0), icon: 'bi-box-seam' },
          {
            label: totalUnavailable > 0 ? 'Tugagan mahsulot' : 'Jami summa',
            value: totalUnavailable > 0 ? totalUnavailable : `${fmt(collections.reduce((sum, item) => sum + item.totalAmount, 0))} so'm`,
            icon: totalUnavailable > 0 ? 'bi-exclamation-triangle' : 'bi-cash-stack',
            danger: totalUnavailable > 0,
          },
        ].map((stat) => (
          <div className="col-xl-3 col-md-6" key={stat.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon" style={(stat as any).danger ? { background: '#FEE2E2', color: '#DC2626' } : undefined}>
                  <i className={`bi ${stat.icon}`}></i>
                </div>
                <div>
                  <div className="stat-value">{stat.value}</div>
                  <div className="stat-label">{stat.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Qidiruv + filtr paneli */}
      <div className="card-panel mb-3">
        <div className="row g-2 align-items-center">
          <div className="col-lg-5">
            <div className="position-relative">
              <i className="bi bi-search position-absolute" style={{ left: 14, top: 11, color: '#9CA3AF' }}></i>
              <Form.Control
                value={listQuery}
                onChange={(event) => setListQuery(event.target.value)}
                placeholder="To'plam nomi yoki slug bo'yicha qidiring"
                style={{ paddingLeft: 38 }}
              />
            </div>
          </div>
          <div className="col-lg-4">
            <div className="btn-group w-100" role="group">
              {([
                ['all', 'Barchasi'],
                ['active', 'Faol'],
                ['hidden', 'Yashirin'],
              ] as [StatusFilter, string][]).map(([value, label]) => (
                <button
                  key={value}
                  type="button"
                  className={`btn btn-sm ${statusFilter === value ? 'btn-primary-gradient' : 'btn-light'}`}
                  onClick={() => setStatusFilter(value)}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>
          <div className="col-lg-3">
            <Form.Select value={sortOption} onChange={(event) => setSortOption(event.target.value as SortOption)}>
              <option value="sort">Tartib bo'yicha</option>
              <option value="newest">Yangi qo'shilgan</option>
              <option value="name">Nomi (A-Z)</option>
              <option value="items">Ko'p mahsulotli</option>
              <option value="amount">Qimmat summa</option>
            </Form.Select>
          </div>
        </div>
      </div>

      {visibleCollections.length === 0 ? (
        <div className="card-panel text-center py-5">
          <i className="bi bi-collection fs-1 text-muted"></i>
          <div className="mt-2 fw-semibold">To'plam topilmadi</div>
          <div className="text-muted small">Qidiruv yoki filtrlarni o'zgartiring, yoki yangi to'plam qo'shing.</div>
        </div>
      ) : null}

      <div className="row g-3">
        {visibleCollections.map((collection) => {
          const outOfStock = collection.itemCount - collection.availableItemCount;
          return (
            <div className="col-xl-6" key={collection.id}>
              <div className="card-panel h-100">
                <div
                  className="rounded-4 p-3 mb-3 text-white"
                  style={{ background: `linear-gradient(135deg, ${collection.gradientFrom}, ${collection.gradientTo})` }}
                >
                  <div className="d-flex justify-content-between align-items-start gap-3">
                    <div>
                      <div className="small opacity-75">/{collection.slug}</div>
                      <div className="fw-bold fs-4">{collection.titleUz}</div>
                      <div className="small mt-1" style={{ maxWidth: 420 }}>{collection.subtitleUz || 'Subtitle kiritilmagan'}</div>
                    </div>
                    <span className={`chip ${collection.isActive ? 'chip-success' : 'chip-gray'}`}>{collection.isActive ? 'Faol' : 'Yashirin'}</span>
                  </div>
                </div>

                <div className="d-flex flex-wrap gap-2 mb-3">
                  <span className="chip chip-gray">{collection.itemCount} ta mahsulot</span>
                  <span className="chip chip-gray">{collection.availableItemCount} ta tayyor</span>
                  {outOfStock > 0 ? <span className="chip" style={{ background: '#FEE2E2', color: '#DC2626' }}>{outOfStock} ta tugagan</span> : null}
                  {collection.customTotalPrice ? <span className="chip chip-purple">Qo'lda narx</span> : null}
                  <span className="chip chip-gray">{fmt(collection.totalAmount)} so'm</span>
                </div>

                {collection.customTotalPrice ? (
                  <div className="small text-muted mb-3">Asl yig'indi: {fmt(collection.baseTotalAmount)} so'm</div>
                ) : null}

                <div className="table-responsive mb-3" style={{ maxHeight: 220, overflowY: 'auto' }}>
                  <table className="data-table">
                    <thead>
                      <tr>
                        <th>Mahsulot</th>
                        <th>Tur</th>
                        <th>Soni</th>
                        <th>Narx</th>
                      </tr>
                    </thead>
                    <tbody>
                      {collection.items.map((item) => (
                        <tr key={`${collection.id}-${item.productType}-${item.productId}`} style={!item.available ? { opacity: 0.55 } : undefined}>
                          <td>
                            <div className="d-flex align-items-center gap-2">
                              {item.image ? (
                                <img src={item.image} alt="" width={34} height={34} style={{ borderRadius: 8, objectFit: 'cover' }} />
                              ) : (
                                <div style={{ width: 34, height: 34, borderRadius: 8, background: '#F3F4F6', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                  <i className={`bi ${item.productType === 'stationery' ? 'bi-pencil' : 'bi-book'} text-muted`}></i>
                                </div>
                              )}
                              <div style={{ minWidth: 0 }}>
                                <div className="fw-semibold text-truncate" style={{ maxWidth: 180 }}>{item.name}</div>
                                <small className="text-muted">{item.author || (item.available ? item.seller : 'Tugagan')}</small>
                              </div>
                            </div>
                          </td>
                          <td><TypeBadge type={item.productType} /></td>
                          <td>{item.quantity}</td>
                          <td>{fmt(item.price)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <div className="d-flex gap-2">
                  <button className="btn btn-sm btn-light flex-fill" onClick={() => { hydrateForm(collection); setShowForm(true); }}>
                    <i className="bi bi-pencil me-1"></i>Tahrirlash
                  </button>
                  <button className="btn btn-sm btn-light" title="Nusxa olish" onClick={() => duplicate(collection)}>
                    <i className="bi bi-files"></i>
                  </button>
                  <button className="btn btn-sm btn-light" title={collection.isActive ? 'Yashirish' : 'Faollashtirish'} onClick={() => toggle(collection)}>
                    <i className={`bi ${collection.isActive ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                  </button>
                  <button className="btn btn-sm btn-light text-danger" title="O'chirish" onClick={() => destroy(collection)}>
                    <i className="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      <Modal show={showForm} onHide={() => { setShowForm(false); resetForm(); }} size="xl" centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">{editing ? "To'plamni tahrirlash" : "To'plam qo'shish"}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {Object.keys(errors).length > 0 ? (
              <div className="alert alert-danger">
                <div className="fw-semibold mb-1">To'plamni saqlashda xatolik bor.</div>
                <ul className="mb-0 ps-3">
                  {Object.entries(errors).map(([key, value]) => (
                    <li key={key}>{value}</li>
                  ))}
                </ul>
              </div>
            ) : null}
            <div className="row g-4">
              <div className="col-lg-7">
                <div className="row g-3">
                  <div className="col-md-8">
                    <Form.Label>Slug</Form.Label>
                    <Form.Control value={form.slug} onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))} placeholder="summer-reading" />
                  </div>
                  <div className="col-md-4">
                    <Form.Label>Tartib</Form.Label>
                    <Form.Control type="number" min={0} value={form.sortOrder} onChange={(event) => setForm((prev) => ({ ...prev, sortOrder: Number(event.target.value || 0) }))} />
                  </div>
                  <div className="col-md-6">
                    <Form.Label>Umumiy narx</Form.Label>
                    <Form.Control
                      type="number"
                      min={1000}
                      value={(form as any).customTotalPrice}
                      onChange={(event) => setForm((prev) => ({ ...prev, customTotalPrice: event.target.value }))}
                      placeholder="Bo'sh qoldirilsa mahsulotlar yig'indisi ishlaydi"
                    />
                    <div className="form-text">Bundle umumiy narxini admin qo'lda belgilashi mumkin.</div>
                  </div>

                  <div className="col-12">
                    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 rounded-4 border px-3 py-2">
                      <div>
                        <div className="fw-semibold">UZ matndan AI tarjima</div>
                        <div className="small text-muted">Nomi, subtitle va tavsif RU, EN, JA maydonlariga to'ldiriladi.</div>
                      </div>
                      <div className="d-flex flex-wrap gap-2">
                        {(['ru', 'en', 'ja'] as TranslateLocale[]).map((locale) => (
                          <button
                            key={locale}
                            type="button"
                            className="btn btn-sm btn-light"
                            disabled={translatingLocales.length > 0}
                            onClick={() => translateFromUz([locale])}
                          >
                            {translatingLocales.includes(locale) ? '...' : translateLocaleLabels[locale]}
                          </button>
                        ))}
                        <button
                          type="button"
                          className="btn btn-sm btn-primary-gradient"
                          disabled={translatingLocales.length > 0}
                          onClick={() => translateFromUz(['ru', 'en', 'ja'])}
                        >
                          {translatingLocales.length > 0 ? 'Tarjima...' : 'Barchasi'}
                        </button>
                      </div>
                    </div>
                  </div>

                  {[
                    ['titleUz', 'Nomi (UZ)'],
                    ['titleRu', 'Nomi (RU)'],
                    ['titleEn', 'Nomi (EN)'],
                    ['titleJa', 'Nomi (JA)'],
                    ['subtitleUz', 'Subtitle (UZ)'],
                    ['subtitleRu', 'Subtitle (RU)'],
                    ['subtitleEn', 'Subtitle (EN)'],
                    ['subtitleJa', 'Subtitle (JA)'],
                  ].map(([key, label]) => (
                    <div className="col-md-6" key={key}>
                      <Form.Label>{label}</Form.Label>
                      <Form.Control value={(form as any)[key]} onChange={(event) => setForm((prev) => ({ ...prev, [key]: event.target.value }))} />
                    </div>
                  ))}

                  {[
                    ['descriptionUz', 'Tavsif (UZ)'],
                    ['descriptionRu', 'Tavsif (RU)'],
                    ['descriptionEn', 'Tavsif (EN)'],
                    ['descriptionJa', 'Tavsif (JA)'],
                  ].map(([key, label]) => (
                    <div className="col-md-6" key={key}>
                      <Form.Label>{label}</Form.Label>
                      <Form.Control as="textarea" rows={4} value={(form as any)[key]} onChange={(event) => setForm((prev) => ({ ...prev, [key]: event.target.value }))} />
                    </div>
                  ))}

                  <div className="col-md-3">
                    <Form.Label>Gradient start</Form.Label>
                    <Form.Control type="color" value={form.gradientFrom} onChange={(event) => setForm((prev) => ({ ...prev, gradientFrom: event.target.value.toUpperCase() }))} />
                  </div>
                  <div className="col-md-3">
                    <Form.Label>Gradient end</Form.Label>
                    <Form.Control type="color" value={form.gradientTo} onChange={(event) => setForm((prev) => ({ ...prev, gradientTo: event.target.value.toUpperCase() }))} />
                  </div>
                  <div className="col-md-3">
                    <Form.Label>Button bg</Form.Label>
                    <Form.Control type="color" value={form.buttonBgColor} onChange={(event) => setForm((prev) => ({ ...prev, buttonBgColor: event.target.value.toUpperCase() }))} />
                  </div>
                  <div className="col-md-3">
                    <Form.Label>Button text</Form.Label>
                    <Form.Control type="color" value={form.buttonTextColor} onChange={(event) => setForm((prev) => ({ ...prev, buttonTextColor: event.target.value.toUpperCase() }))} />
                  </div>
                  <div className="col-12">
                    <Form.Label>Hero rasm</Form.Label>
                    <Form.Control type="file" accept="image/*" onChange={onHeroChange} />
                    {heroPreview ? <img src={heroPreview} alt="" className="mt-2 rounded-3" style={{ maxHeight: 90 }} /> : null}
                  </div>
                  <div className="col-12 d-flex flex-wrap gap-4">
                    <Form.Check type="switch" label="Faol" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
                    <Form.Check
                      type="switch"
                      id="festive-effect-switch"
                      label={<span><i className="bi bi-stars text-warning me-1"></i>Bayramona effekt (yulduzcha animatsiyasi)</span>}
                      checked={form.festiveEffect}
                      onChange={(event) => setForm((prev) => ({ ...prev, festiveEffect: event.target.checked }))}
                    />
                  </div>
                </div>
              </div>

              <div className="col-lg-5">
                <div className="rounded-4 p-3 mb-3 text-white" style={{ background: `linear-gradient(135deg, ${form.gradientFrom}, ${form.gradientTo})` }}>
                  <div className="small opacity-75">Preview</div>
                  <div className="fw-bold fs-4 mt-2">{form.titleUz || "To'plam nomi"}</div>
                  <div className="small mt-2">{form.subtitleUz || 'Subtitle shu yerda ko‘rinadi'}</div>
                  {Number((form as any).customTotalPrice || 0) > 0 ? (
                    <div className="small mt-2 opacity-75">Asl yig'indi: {fmt(totalAmount)} so'm</div>
                  ) : null}
                  <button
                    type="button"
                    className="btn mt-3"
                    style={{ background: form.buttonBgColor, color: form.buttonTextColor, borderRadius: 999, paddingInline: 18 }}
                  >
                    {fmt(Number((form as any).customTotalPrice || 0) > 0 ? Number((form as any).customTotalPrice || 0) : totalAmount)} so'mga sotib olish
                  </button>
                </div>

                <div className="card-panel">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <div className="fw-bold">Mahsulot qo'shish</div>
                    <span className="text-muted small">{bookCount} kitob · {stationeryCount} kanselyariya</span>
                  </div>

                  {/* Kitob / Kanselyariya tab */}
                  <div className="btn-group w-100 mb-2" role="group">
                    <button
                      type="button"
                      className={`btn btn-sm ${searchType === 'book' ? 'btn-primary-gradient' : 'btn-light'}`}
                      onClick={() => { setSearchType('book'); setSearchResults([]); }}
                    >
                      <i className="bi bi-book me-1"></i>Kitob
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm ${searchType === 'stationery' ? 'btn-primary-gradient' : 'btn-light'}`}
                      onClick={() => { setSearchType('stationery'); setSearchResults([]); }}
                    >
                      <i className="bi bi-pencil me-1"></i>Kanselyariya
                    </button>
                  </div>

                  <Form.Control
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={searchType === 'book' ? "Nomi, muallif yoki artikul bo'yicha" : "Nomi, artikul yoki barkod bo'yicha"}
                    className="mb-3"
                  />

                  <div className="border rounded-4 p-2 mb-3" style={{ minHeight: 112, maxHeight: 220, overflowY: 'auto' }}>
                    {searchLoading ? <div className="text-muted small">Qidirilmoqda...</div> : null}
                    {!searchLoading && searchResults.length === 0 ? <div className="text-muted small">Qidirsangiz natijalar shu yerda chiqadi.</div> : null}
                    {searchResults.map((product) => (
                      <button
                        type="button"
                        key={itemKey(product.productType, product.id)}
                        className="btn btn-light w-100 text-start mb-2 d-flex align-items-center gap-2"
                        onClick={() => addProduct(product)}
                      >
                        {product.image ? (
                          <img src={product.image} alt="" width={36} height={36} style={{ borderRadius: 8, objectFit: 'cover' }} />
                        ) : (
                          <div style={{ width: 36, height: 36, borderRadius: 8, background: '#EEF0F3', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <i className={`bi ${product.productType === 'stationery' ? 'bi-pencil' : 'bi-book'} text-muted`}></i>
                          </div>
                        )}
                        <div style={{ minWidth: 0 }}>
                          <div className="fw-semibold text-truncate">{product.name}</div>
                          <div className="small text-muted text-truncate">
                            {product.author || product.seller || '—'} · {fmt(product.price)} so'm · {product.stock > 0 ? `${product.stock} dona` : 'tugagan'}
                          </div>
                        </div>
                      </button>
                    ))}
                  </div>

                  <div className="d-flex justify-content-between align-items-center mb-1">
                    <div className="fw-semibold small">Tanlangan ({items.length})</div>
                    {unavailableCount > 0 ? <span className="small text-danger">{unavailableCount} ta tugagan</span> : null}
                  </div>
                  <div className="small text-muted mb-2">Tartibni sudrab (drag) o'zgartiring.</div>

                  <div className="border rounded-4 p-2" style={{ maxHeight: 360, overflowY: 'auto' }}>
                    {items.length === 0 ? <div className="text-muted small">Hali mahsulot tanlanmagan.</div> : null}
                    {items.map((item, index) => (
                      <div
                        key={`${item.productType}-${item.productId}-${index}`}
                        className="border rounded-4 p-2 mb-2"
                        draggable
                        onDragStart={() => onDragStart(index)}
                        onDragOver={onDragOver}
                        onDrop={() => onDrop(index)}
                        style={{
                          cursor: 'grab',
                          background: dragIndex === index ? '#EEF2FF' : undefined,
                          borderColor: !item.available ? '#FCA5A5' : undefined,
                        }}
                      >
                        <div className="d-flex justify-content-between gap-2">
                          <div className="d-flex align-items-center gap-2" style={{ minWidth: 0 }}>
                            <i className="bi bi-grip-vertical text-muted"></i>
                            {item.image ? (
                              <img src={item.image} alt="" width={32} height={32} style={{ borderRadius: 6, objectFit: 'cover' }} />
                            ) : null}
                            <div style={{ minWidth: 0 }}>
                              <div className="fw-semibold text-truncate">{item.name}</div>
                              <div className="small text-muted text-truncate">
                                {item.productType === 'stationery' ? 'Kanselyariya' : 'Kitob'} · {item.seller || '—'}
                                {!item.available ? ' · tugagan' : ''}
                              </div>
                            </div>
                          </div>
                          <button type="button" className="btn btn-sm btn-light text-danger" onClick={() => removeItem(index)}>
                            <i className="bi bi-trash"></i>
                          </button>
                        </div>
                        <div className="row g-2 mt-1">
                          <div className="col-6">
                            <Form.Label className="small text-muted mb-1">Soni</Form.Label>
                            <div className="input-group input-group-sm">
                              <button type="button" className="btn btn-light" onClick={() => updateItem(index, { quantity: Math.max(1, item.quantity - 1) })}>−</button>
                              <Form.Control
                                type="number"
                                min={1}
                                className="text-center"
                                value={item.quantity}
                                onChange={(event) => updateItem(index, { quantity: Math.max(1, Number(event.target.value || 1)) })}
                              />
                              <button type="button" className="btn btn-light" onClick={() => updateItem(index, { quantity: item.quantity + 1 })}>+</button>
                            </div>
                          </div>
                          <div className="col-6">
                            <Form.Label className="small text-muted mb-1">Jami narx</Form.Label>
                            <Form.Control value={`${fmt(item.price * item.quantity)} so'm`} disabled />
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={() => { setShowForm(false); resetForm(); }}>Bekor qilish</Button>
            <Button type="submit" className="btn-primary-gradient border-0" disabled={items.length === 0}>Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
