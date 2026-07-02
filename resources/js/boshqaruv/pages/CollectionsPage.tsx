import { ChangeEvent, FormEvent, useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Form, Modal } from 'react-bootstrap';

type CollectionItem = {
  id?: number;
  productId: number;
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
  sortOrder: number;
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
  itemCount: number;
  availableItemCount: number;
  totalAmount: number;
  items: CollectionItem[];
  bookSearchUrl: string;
  createUrl: string;
  updateUrl: string;
  toggleUrl: string;
  destroyUrl: string;
};

type SearchBook = {
  id: number;
  name: string;
  author?: string | null;
  artikul?: string | null;
  seller?: string | null;
  price: number;
  base_price: number;
  stock: number;
  image?: string | null;
};

const fmt = (value: number) => new Intl.NumberFormat('uz-UZ').format(value || 0);

const defaultForm = {
  slug: '',
  sortOrder: 0,
  isActive: true,
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

export default function CollectionsPage() {
  const { collections = [] } = usePage<{ collections?: CollectionRow[] }>().props;
  const baseSearchUrl = collections[0]?.bookSearchUrl || '/boshqaruv/collections/book-search';
  const baseCreateUrl = collections[0]?.createUrl || '/boshqaruv/collections';

  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<CollectionRow | null>(null);
  const [heroFile, setHeroFile] = useState<File | null>(null);
  const [search, setSearch] = useState('');
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchBook[]>([]);
  const [items, setItems] = useState<CollectionItem[]>([]);
  const [form, setForm] = useState(defaultForm);

  const resetForm = () => {
    setEditing(null);
    setHeroFile(null);
    setSearch('');
    setSearchResults([]);
    setItems([]);
    setForm(defaultForm);
  };

  const hydrateForm = (collection?: CollectionRow | null) => {
    if (!collection) {
      resetForm();
      return;
    }

    setEditing(collection);
    setHeroFile(null);
    setSearch('');
    setSearchResults([]);
    setItems(collection.items.map((item) => ({ ...item })));
    setForm({
      slug: collection.slug,
      sortOrder: collection.sortOrder,
      isActive: collection.isActive,
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

  const availableCount = useMemo(
    () => items.filter((item) => item.available).length,
    [items],
  );

  const searchBooks = async (query: string) => {
    const clean = query.trim();
    if (!clean) {
      setSearchResults([]);
      return;
    }

    setSearchLoading(true);
    try {
      const response = await fetch(`${baseSearchUrl}?q=${encodeURIComponent(clean)}`, {
        headers: { Accept: 'application/json' },
      });
      const payload = response.ok ? await response.json() : null;
      setSearchResults(Array.isArray(payload?.data) ? payload.data : []);
    } finally {
      setSearchLoading(false);
    }
  };

  useEffect(() => {
    const timer = window.setTimeout(() => {
      searchBooks(search);
    }, 220);

    return () => window.clearTimeout(timer);
  }, [search]);

  const addBook = (book: SearchBook) => {
    setItems((current) => {
      const existingIndex = current.findIndex((item) => item.productId === book.id);
      if (existingIndex >= 0) {
        return current.map((item, index) =>
          index === existingIndex ? { ...item, quantity: item.quantity + 1 } : item,
        );
      }

      return [
        ...current,
        {
          productId: book.id,
          name: book.name,
          author: book.author,
          seller: book.seller,
          quantity: 1,
          sortOrder: current.length,
          price: book.price,
          stock: book.stock,
          available: book.stock > 0,
          image: book.image,
        },
      ];
    });
  };

  const updateItem = (index: number, patch: Partial<CollectionItem>) => {
    setItems((current) => current.map((item, itemIndex) => (itemIndex === index ? { ...item, ...patch } : item)));
  };

  const removeItem = (index: number) => {
    setItems((current) => current.filter((_, itemIndex) => itemIndex !== index).map((item, order) => ({ ...item, sortOrder: order })));
  };

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const payload = new FormData();
    payload.append('slug', form.slug);
    payload.append('sort_order', String(form.sortOrder || 0));
    payload.append('is_active', form.isActive ? '1' : '0');
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
      forceFormData: true,
      onSuccess: () => {
        setShowForm(false);
        resetForm();
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

  const destroy = (collection: CollectionRow) => {
    if (!confirm(`${collection.titleUz} to'plami o'chirilsinmi?`)) return;
    router.delete(collection.destroyUrl, { preserveScroll: true });
  };

  return (
    <div>
      <div className="page-head">
        <div>
          <h1 className="page-title">To'plamlar</h1>
          <p className="page-subtitle">Banner orqali ochiladigan tayyor kitob to'plamlari va ularning sahifa dizayni</p>
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
          { label: 'Kitoblar', value: collections.reduce((sum, item) => sum + item.itemCount, 0), icon: 'bi-book' },
          { label: 'Jami summa', value: `${fmt(collections.reduce((sum, item) => sum + item.totalAmount, 0))} so'm`, icon: 'bi-cash-stack' },
        ].map((stat) => (
          <div className="col-xl-3 col-md-6" key={stat.label}>
            <div className="stat-card">
              <div className="d-flex align-items-center gap-3">
                <div className="stat-icon"><i className={`bi ${stat.icon}`}></i></div>
                <div>
                  <div className="stat-value">{stat.value}</div>
                  <div className="stat-label">{stat.label}</div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3">
        {collections.map((collection) => (
          <div className="col-xl-6" key={collection.id}>
            <div className="card-panel h-100">
              <div
                className="rounded-4 p-3 mb-3 text-white"
                style={{
                  background: `linear-gradient(135deg, ${collection.gradientFrom}, ${collection.gradientTo})`,
                }}
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
                <span className="chip chip-gray">{collection.itemCount} ta kitob</span>
                <span className="chip chip-gray">{collection.availableItemCount} ta tayyor</span>
                <span className="chip chip-gray">{fmt(collection.totalAmount)} so'm</span>
              </div>

              <div className="table-responsive mb-3">
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Kitob</th>
                      <th>Sotuvchi</th>
                      <th>Soni</th>
                      <th>Narx</th>
                    </tr>
                  </thead>
                  <tbody>
                    {collection.items.slice(0, 4).map((item) => (
                      <tr key={`${collection.id}-${item.productId}`}>
                        <td>
                          <div className="fw-semibold">{item.name}</div>
                          <small className="text-muted">{item.author || item.productId}</small>
                        </td>
                        <td>{item.seller || '—'}</td>
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
                <button className="btn btn-sm btn-light" onClick={() => toggle(collection)}>
                  <i className={`bi ${collection.isActive ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                </button>
                <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(collection)}>
                  <i className="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <Modal show={showForm} onHide={() => { setShowForm(false); resetForm(); }} size="xl" centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">{editing ? "To'plamni tahrirlash" : "To'plam qo'shish"}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
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
                    <Form.Control type="file" accept="image/*" onChange={(event: ChangeEvent<HTMLInputElement>) => setHeroFile(event.target.files?.[0] || null)} />
                    {!heroFile && editing?.heroImage ? <div className="form-text">Hozirgi rasm saqlanadi.</div> : null}
                  </div>
                  <div className="col-12">
                    <Form.Check type="switch" label="Faol" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
                  </div>
                </div>
              </div>

              <div className="col-lg-5">
                <div className="rounded-4 p-3 mb-3 text-white" style={{ background: `linear-gradient(135deg, ${form.gradientFrom}, ${form.gradientTo})` }}>
                  <div className="small opacity-75">Preview</div>
                  <div className="fw-bold fs-4 mt-2">{form.titleUz || "To'plam nomi"}</div>
                  <div className="small mt-2">{form.subtitleUz || 'Subtitle shu yerda ko‘rinadi'}</div>
                  <button
                    type="button"
                    className="btn mt-3"
                    style={{ background: form.buttonBgColor, color: form.buttonTextColor, borderRadius: 999, paddingInline: 18 }}
                  >
                    {fmt(totalAmount)} so'mga sotib olish
                  </button>
                </div>

                <div className="card-panel">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <div className="fw-bold">Kitob qo'shish</div>
                    <span className="text-muted small">{items.length} ta</span>
                  </div>
                  <Form.Control value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Nomi, muallif yoki artikul bo'yicha qidiring" className="mb-3" />

                  <div className="border rounded-4 p-2 mb-3" style={{ minHeight: 112, maxHeight: 220, overflowY: 'auto' }}>
                    {searchLoading ? <div className="text-muted small">Qidirilmoqda...</div> : null}
                    {!searchLoading && searchResults.length === 0 ? <div className="text-muted small">Kitob qidirsangiz natijalar shu yerda chiqadi.</div> : null}
                    {searchResults.map((book) => (
                      <button
                        type="button"
                        key={book.id}
                        className="btn btn-light w-100 text-start mb-2"
                        onClick={() => addBook(book)}
                      >
                        <div className="fw-semibold">{book.name}</div>
                        <div className="small text-muted">{book.author || 'Muallif yo‘q'} · {book.seller || 'Do‘kon yo‘q'} · {fmt(book.price)} so'm</div>
                      </button>
                    ))}
                  </div>

                  <div className="border rounded-4 p-2" style={{ maxHeight: 380, overflowY: 'auto' }}>
                    {items.length === 0 ? <div className="text-muted small">Hali kitob tanlanmagan.</div> : null}
                    {items.map((item, index) => (
                      <div key={`${item.productId}-${index}`} className="border rounded-4 p-2 mb-2">
                        <div className="d-flex justify-content-between gap-2">
                          <div style={{ minWidth: 0 }}>
                            <div className="fw-semibold text-truncate">{item.name}</div>
                            <div className="small text-muted text-truncate">{item.author || 'Muallif yo‘q'} · {item.seller || 'Do‘kon yo‘q'}</div>
                          </div>
                          <button type="button" className="btn btn-sm btn-light text-danger" onClick={() => removeItem(index)}>
                            <i className="bi bi-trash"></i>
                          </button>
                        </div>
                        <div className="row g-2 mt-1">
                          <div className="col-4">
                            <Form.Label className="small text-muted">Soni</Form.Label>
                            <Form.Control type="number" min={1} value={item.quantity} onChange={(event) => updateItem(index, { quantity: Number(event.target.value || 1) })} />
                          </div>
                          <div className="col-4">
                            <Form.Label className="small text-muted">Tartib</Form.Label>
                            <Form.Control type="number" min={0} value={item.sortOrder} onChange={(event) => updateItem(index, { sortOrder: Number(event.target.value || 0) })} />
                          </div>
                          <div className="col-4">
                            <Form.Label className="small text-muted">Narx</Form.Label>
                            <Form.Control value={fmt(item.price)} disabled />
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
