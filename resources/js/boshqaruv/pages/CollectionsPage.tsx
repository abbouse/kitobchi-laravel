import { ChangeEvent, DragEvent, FormEvent, useEffect, useMemo, useState } from 'react';
import { PageCrumbs } from '../Layout';
import { router, usePage } from '@inertiajs/react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';

import { StatWidget } from '../components/Axelit';

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

// Backend payloaddagi bo'lim (nested, 2 daraja)
type ApiCollectionSection = {
  id: number;
  nameUz: string;
  nameRu?: string | null;
  nameEn?: string | null;
  nameJa?: string | null;
  customTotalPrice?: number | null;
  sortOrder: number;
  items: CollectionItem[];
  children?: ApiCollectionSection[];
};

// Forma holatidagi bo'lim
type SectionNode = {
  key: string;
  nameUz: string;
  nameRu: string;
  nameEn: string;
  nameJa: string;
  price: string;
  items: CollectionItem[];
  children: SectionNode[];
};

type CollectionRow = {
  id: number;
  slug: string;
  isActive: boolean;
  festiveEffect?: boolean;
  sortOrder: number;
  customTotalPrice?: number | null;
  deliveryPrice?: number | null;
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
  sections?: ApiCollectionSection[];
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

type AiBook = {
  id: number; name: string; author?: string; seller?: string;
  price: number; stock: number; commission_percent: number;
  sales: number; carts: number; views: number; score: number; image?: string | null; quantity?: number;
};

type AiRecommendation = {
  theme: { title_uz: string; title_ru: string; subtitle_uz: string; subtitle_ru: string; description_uz: string; description_ru: string };
  books: AiBook[];
  pricing: {
    currency: string; gross_retail: number; total_commission: number; seller_payout: number;
    target_discount_percent: number; applied_discount_percent: number; applied_discount: number;
    bundle_price: number; payment_fee: number; tax: number; tax_mode: string; platform_net: number;
    margin_floor: number; margin_floor_percent: number; max_safe_discount: number; discount_clamped: boolean;
  };
  market_analysis: string;
  reasoning: string;
  demand: { window_days: number; candidate_count: number; top_searches: string[]; method: string };
};

const money = (n: number) => `${(Number(n) || 0).toLocaleString('ru-RU')} so'm`;

const InfoRow = ({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) => (
  <div className="col-md-6 d-flex justify-content-between b-b-1-light py-1" style={{ gap: 8 }}>
    <span className="text-muted">{label}</span>
    <span className={strong ? 'f-w-600' : 'f-w-600'} style={{ textAlign: 'right' }}>{value}</span>
  </div>
);

const genKey = () => Math.random().toString(36).slice(2, 10) + Date.now().toString(36).slice(-4);

const mapApiSections = (secs?: ApiCollectionSection[]): SectionNode[] =>
  (secs || []).map((s) => ({
    key: genKey(),
    nameUz: s.nameUz || '',
    nameRu: s.nameRu || '',
    nameEn: s.nameEn || '',
    nameJa: s.nameJa || '',
    price: s.customTotalPrice ? String(s.customTotalPrice) : '',
    items: (s.items || []).map((it) => ({ ...it, productType: it.productType || 'book' })),
    children: mapApiSections(s.children),
  }));

// Karta preview uchun: root + barcha bo'lim mahsulotlarini yassilaydi (bo'lim nomi bilan)
const flattenCollectionItems = (collection: CollectionRow): Array<CollectionItem & { sectionName?: string }> => {
  const out: Array<CollectionItem & { sectionName?: string }> = collection.items.map((it) => ({ ...it }));
  (collection.sections || []).forEach((s) => {
    (s.items || []).forEach((it) => out.push({ ...it, sectionName: s.nameUz }));
    (s.children || []).forEach((c) => (c.items || []).forEach((it) => out.push({ ...it, sectionName: `${s.nameUz} · ${c.nameUz}` })));
  });
  return out;
};

const emptySection = (): SectionNode => ({
  key: genKey(),
  nameUz: '',
  nameRu: '',
  nameEn: '',
  nameJa: '',
  price: '',
  items: [],
  children: [],
});

const makeCollectionItem = (product: SearchProduct, order: number): CollectionItem => ({
  productId: product.id,
  productType: product.productType,
  name: product.name,
  author: product.author,
  seller: product.seller,
  quantity: 1,
  sortOrder: order,
  price: product.price,
  stock: product.stock,
  available: product.stock > 0,
  image: product.image,
});

const addToItemList = (list: CollectionItem[], product: SearchProduct): CollectionItem[] => {
  const idx = list.findIndex((it) => it.productId === product.id && it.productType === product.productType);
  if (idx >= 0) {
    return list.map((it, i) => (i === idx ? { ...it, quantity: it.quantity + 1 } : it));
  }
  return [...list, makeCollectionItem(product, list.length)];
};

// 2-darajali daraxtda kalit bo'yicha tugunni yangilaydi
const walkSectionTree = (secs: SectionNode[], key: string, fn: (n: SectionNode) => SectionNode): SectionNode[] =>
  secs.map((s) => (s.key === key ? fn(s) : { ...s, children: s.children.map((c) => (c.key === key ? fn(c) : c)) }));

const updateSectionItems = (secs: SectionNode[], key: string, itemsFn: (list: CollectionItem[]) => CollectionItem[]): SectionNode[] =>
  walkSectionTree(secs, key, (n) => ({ ...n, items: itemsFn(n.items) }));

// Bo'lim mahsulotlaridan avto narx (yig'indi). Narx bo'sh bo'lsa shu ishlatiladi.
const sectionItemsTotal = (node: SectionNode): number =>
  node.items.reduce((sum, it) => sum + (it.price || 0) * (it.quantity || 1), 0);

const defaultForm = {
  slug: '',
  sortOrder: 0,
  isActive: true,
  festiveEffect: true,
  customTotalPrice: '',
  deliveryPrice: '',
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
    <span className="badge text-light-primary"><i className="ti ti-pencil me-1"></i>Kanselyariya</span>
  ) : (
    <span className="badge text-light-secondary"><i className="ti ti-book me-1"></i>Kitob</span>
  );

export default function CollectionsPage() {
  const { collections = [], errors = {}, translateUrl = '/boshqaruv/content/translate' } = usePage<{
    collections?: CollectionRow[];
    translateUrl?: string;
    errors?: Record<string, string>;
  }>().props;
  const baseSearchUrl = collections[0]?.bookSearchUrl || '/boshqaruv/collections/book-search';
  const baseCreateUrl = collections[0]?.createUrl || '/boshqaruv/collections';
  const aiRecommendUrl = '/boshqaruv/collections/ai-recommend';

  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<CollectionRow | null>(null);
  const [heroFile, setHeroFile] = useState<File | null>(null);
  const [heroPreview, setHeroPreview] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [searchType, setSearchType] = useState<ProductType>('book');
  const [searchLoading, setSearchLoading] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchProduct[]>([]);
  const [items, setItems] = useState<CollectionItem[]>([]);
  const [sections, setSections] = useState<SectionNode[]>([]);
  const [activeKey, setActiveKey] = useState<string>('root');
  const [form, setForm] = useState(defaultForm);
  const [aiOpen, setAiOpen] = useState(false);
  const [aiLoading, setAiLoading] = useState(false);
  const [aiError, setAiError] = useState<string | null>(null);
  const [aiResult, setAiResult] = useState<AiRecommendation | null>(null);
  const [aiThemeHint, setAiThemeHint] = useState('');
  const [aiSize, setAiSize] = useState('');
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
    setSections([]);
    setActiveKey('root');
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
    setSections(mapApiSections(collection.sections));
    setActiveKey('root');
    setForm({
      slug: collection.slug,
      sortOrder: collection.sortOrder,
      isActive: collection.isActive,
      festiveEffect: collection.festiveEffect ?? true,
      customTotalPrice: collection.customTotalPrice ? String(collection.customTotalPrice) : '',
      deliveryPrice: collection.deliveryPrice ? String(collection.deliveryPrice) : '',
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

  const findNode = (key: string): SectionNode | null => {
    for (const s of sections) {
      if (s.key === key) return s;
      for (const c of s.children) if (c.key === key) return c;
    }
    return null;
  };

  const addProduct = (product: SearchProduct) => {
    if (activeKey === 'root') {
      if (sections.length > 0) {
        window.alert("Bo'limli to'plamga bo'limsiz mahsulot qo'shib bo'lmaydi. Avval bo'lim tanlang.");
        return;
      }
      setItems((current) => addToItemList(current, product));
      return;
    }
    const node = findNode(activeKey);
    if (node && node.children.length > 0) {
      window.alert("Bu bo'lim ichki bo'limlarga bo'lingan — mahsulotni ichki bo'limga qo'shing.");
      return;
    }
    setSections((current) => updateSectionItems(current, activeKey, (list) => addToItemList(list, product)));
  };

  // --- Bo'lim boshqaruvi (2 daraja) ---
  const addSection = () => {
    const sec = emptySection();
    setSections((cur) => [...cur, sec]);
    if (sections.length === 0) setActiveKey(sec.key);
  };
  const addChildSection = (sectionKey: string) => {
    const child = emptySection();
    setSections((cur) =>
      cur.map((s) => {
        if (s.key !== sectionKey) return s;
        // Leaf bo'lim (mahsuloti bor) ichki bo'limga aylanmoqda — mavjud
        // mahsulotlarni yangi ichki bo'limga ko'chiramiz (yo'qolmasligi uchun).
        const moved = s.children.length === 0 ? s.items : [];
        return {
          ...s,
          items: s.children.length === 0 ? [] : s.items,
          price: s.children.length === 0 ? '' : s.price,
          children: [...s.children, { ...child, items: moved }],
        };
      }),
    );
    setActiveKey(child.key); // yangi ichki bo'limni faol qilamiz
  };
  // Bo'limni (1 yoki 2-daraja) yuqori/pastga ko'chirish.
  const moveNode = (key: string, dir: -1 | 1) =>
    setSections((cur) => {
      const i = cur.findIndex((s) => s.key === key);
      if (i >= 0) {
        const j = i + dir;
        if (j < 0 || j >= cur.length) return cur;
        const next = [...cur];
        [next[i], next[j]] = [next[j], next[i]];
        return next;
      }
      return cur.map((s) => {
        const ci = s.children.findIndex((c) => c.key === key);
        if (ci < 0) return s;
        const cj = ci + dir;
        if (cj < 0 || cj >= s.children.length) return s;
        const kids = [...s.children];
        [kids[ci], kids[cj]] = [kids[cj], kids[ci]];
        return { ...s, children: kids };
      });
    });
  const removeSection = (key: string) => {
    setSections((cur) => cur.filter((s) => s.key !== key).map((s) => ({ ...s, children: s.children.filter((c) => c.key !== key) })));
    setActiveKey((prev) => (prev === key ? 'root' : prev));
  };
  const updateSection = (key: string, patch: Partial<SectionNode>) =>
    setSections((cur) => walkSectionTree(cur, key, (n) => ({ ...n, ...patch })));
  const updateSectionItem = (key: string, index: number, patch: Partial<CollectionItem>) =>
    setSections((cur) => updateSectionItems(cur, key, (list) => list.map((it, i) => (i === index ? { ...it, ...patch } : it))));
  const removeSectionItem = (key: string, index: number) =>
    setSections((cur) => updateSectionItems(cur, key, (list) => list.filter((_, i) => i !== index).map((it, o) => ({ ...it, sortOrder: o }))));

  const findNodeName = (key: string): string => {
    if (key === 'root') return "To'plam (umumiy)";
    for (const s of sections) {
      if (s.key === key) return s.nameUz || "Bo'lim";
      for (const c of s.children) {
        if (c.key === key) return c.nameUz || "Ichki bo'lim";
      }
    }
    return "To'plam (umumiy)";
  };

  const hasAnyProduct =
    items.length > 0 ||
    sections.some((s) => s.items.length > 0 || s.children.some((c) => c.items.length > 0));

  const renderNode = (node: SectionNode, level: 1 | 2) => {
    const isGroup = node.children.length > 0; // ichki bo'limi bor → guruh (mahsulot qabul qilmaydi)
    const total = sectionItemsTotal(node);
    return (
      <div
        key={node.key}
        className={`b-1-light b-r-15 p-2 mb-2 ${level === 2 ? 'ms-3' : ''}`}
        style={{ borderColor: activeKey === node.key ? 'rgba(var(--primary), 1)' : undefined, background: activeKey === node.key ? 'rgba(var(--primary), .1)' : undefined }}
      >
        <div className="d-flex gap-2 align-items-center mb-2 flex-wrap">
          <span className={`badge ${isGroup ? 'text-light-primary' : 'text-light-secondary'}`}>
            {level === 2 ? "Ichki bo'lim" : isGroup ? "Bo'lim (guruh)" : "Bo'lim"}
          </span>
          {!isGroup ? (
            <button
              type="button"
              className={`btn btn-sm py-0 ${activeKey === node.key ? 'btn-primary' : 'btn-light-secondary'}`}
              onClick={() => setActiveKey(node.key)}
              title="Chapdagi qidiruvdan mahsulot shu bo'limga qo'shiladi"
            >
              {activeKey === node.key ? '◉ Faol' : '◉ Shu yerga'}
            </button>
          ) : null}
          <span className="f-s-13 text-muted">
            {isGroup ? `${node.children.length} ichki bo'lim` : `${node.items.length} mahsulot`}
          </span>
          <div className="ms-auto d-flex gap-1">
            <button type="button" className="btn btn-sm btn-light-secondary py-0" title="Yuqoriga" onClick={() => moveNode(node.key, -1)}><i className="ti ti-arrow-up"></i></button>
            <button type="button" className="btn btn-sm btn-light-secondary py-0" title="Pastga" onClick={() => moveNode(node.key, 1)}><i className="ti ti-arrow-down"></i></button>
            <button type="button" className="btn btn-sm btn-light-secondary text-danger py-0" title="O'chirish" onClick={() => removeSection(node.key)}><i className="ti ti-trash"></i></button>
          </div>
        </div>
        <div className="row g-1 mb-2">
          <div className="col-6 col-md-3"><Form.Control size="sm" placeholder="Nom UZ*" value={node.nameUz} onChange={(e) => updateSection(node.key, { nameUz: e.target.value })} /></div>
          <div className="col-6 col-md-3"><Form.Control size="sm" placeholder="RU" value={node.nameRu} onChange={(e) => updateSection(node.key, { nameRu: e.target.value })} /></div>
          <div className="col-4 col-md-2"><Form.Control size="sm" placeholder="EN" value={node.nameEn} onChange={(e) => updateSection(node.key, { nameEn: e.target.value })} /></div>
          <div className="col-4 col-md-2"><Form.Control size="sm" placeholder="JA" value={node.nameJa} onChange={(e) => updateSection(node.key, { nameJa: e.target.value })} /></div>
          {!isGroup ? (
            <div className="col-4 col-md-2"><Form.Control size="sm" type="number" placeholder={`avto ${total.toLocaleString('ru-RU')}`} value={node.price} onChange={(e) => updateSection(node.key, { price: e.target.value })} /></div>
          ) : null}
        </div>
        {!isGroup ? (
          <>
            <div className="text-muted mb-2 f-s-11">
              Avto narx: <b>{total.toLocaleString('ru-RU')} so'm</b>. Bo'sh = avto; kamaytirmoqchi bo'lsangiz yozing.
            </div>
            {node.items.length > 0 ? (
              <div className="mb-2">
                {node.items.map((item, index) => (
                  <div key={`${item.productType}-${item.productId}-${index}`} className="d-flex align-items-center gap-2 f-s-13 b-1-light b-r-10 p-1 mb-1" style={{ borderColor: !item.available ? 'rgba(var(--danger), 1)' : undefined }}>
                    {item.image ? <img className="b-r-4 object-fit-cover" src={item.image} alt="" width={24} height={24} /> : null}
                    <span className="text-truncate flex-fill">{item.name}</span>
                    <div className="input-group input-group-sm" style={{ width: 96 }}>
                      <button type="button" className="btn btn-light-secondary" onClick={() => updateSectionItem(node.key, index, { quantity: Math.max(1, item.quantity - 1) })}>−</button>
                      <span className="form-control text-center bg-white">{item.quantity}</span>
                      <button type="button" className="btn btn-light-secondary" onClick={() => updateSectionItem(node.key, index, { quantity: item.quantity + 1 })}>+</button>
                    </div>
                    <button type="button" className="btn btn-sm btn-light-secondary text-danger py-0" onClick={() => removeSectionItem(node.key, index)}><i className="ti ti-x"></i></button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="f-s-13 text-muted mb-2">Mahsulot yo'q — "◉ Shu yerga" ni bosing, so'ng chapdan qidiruvdan qo'shing.</div>
            )}
          </>
        ) : (
          <div className="f-s-13 text-muted mb-2"><i className="ti ti-info-circle me-1"></i>Bu bo'lim ichki bo'limlarga bo'lingan — mahsulotlar faqat ichki bo'limlarga qo'shiladi.</div>
        )}
        {level === 1 ? (
          <div>
            {node.children.map((child) => renderNode(child, 2))}
            <button type="button" className="btn btn-sm btn-light-secondary" onClick={() => addChildSection(node.key)}><i className="ti ti-plus me-1"></i>Ichki bo'lim</button>
          </div>
        ) : null}
      </div>
    );
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

    // Bo'lim (parent + child) nomlarini ham tarjimaga qo'shamiz — har biriga `section_<key>`.
    const collectSectionNames = (nodes: SectionNode[]) => {
      nodes.forEach((n) => {
        if (n.nameUz.trim()) texts[`section_${n.key}`] = n.nameUz.trim();
        collectSectionNames(n.children);
      });
    };
    collectSectionNames(sections);

    if (Object.keys(texts).length === 0) {
      window.alert("Avval UZ maydonlarini (yoki bo'lim nomlarini) to'ldiring.");
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

      // Bo'lim nomlari tarjimasini daraxtga (parent + child) qo'llaymiz.
      setSections((cur) => {
        const applyNode = (n: SectionNode): SectionNode => {
          const patch: Partial<SectionNode> = {};
          targetLocales.forEach((locale) => {
            const translated = payload.data?.[locale] || {};
            const val = translated[`section_${n.key}`];
            if (val) {
              (patch as any)[`name${locale.charAt(0).toUpperCase()}${locale.slice(1)}`] = val;
            }
          });
          return { ...n, ...patch, children: n.children.map(applyNode) };
        };
        return cur.map(applyNode);
      });
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'AI tarjima vaqtincha ishlamadi.');
    } finally {
      setTranslatingLocales([]);
    }
  };

  const runAiRecommend = async () => {
    setAiLoading(true);
    setAiError(null);
    try {
      const response = await fetch(aiRecommendUrl, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({
          theme_hint: aiThemeHint.trim() || null,
          size: aiSize ? Number(aiSize) : null,
        }),
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || payload?.status !== 'success') {
        throw new Error(payload?.message || 'AI tavsiya xatosi');
      }
      setAiResult(payload.recommendation as AiRecommendation);
    } catch (error) {
      setAiResult(null);
      setAiError(error instanceof Error ? error.message : 'AI tavsiya vaqtincha ishlamadi.');
    } finally {
      setAiLoading(false);
    }
  };

  const applyAiRecommendation = () => {
    if (!aiResult) return;
    resetForm();
    setItems(aiResult.books.map((b, index) => ({
      productId: b.id,
      productType: 'book' as ProductType,
      name: b.name,
      author: b.author,
      seller: b.seller,
      quantity: b.quantity || 1,
      sortOrder: index,
      price: b.price,
      stock: b.stock,
      available: true,
      image: b.image,
    })));
    setForm({
      ...defaultForm,
      titleUz: aiResult.theme.title_uz || '',
      titleRu: aiResult.theme.title_ru || '',
      subtitleUz: aiResult.theme.subtitle_uz || '',
      subtitleRu: aiResult.theme.subtitle_ru || '',
      descriptionUz: aiResult.theme.description_uz || '',
      descriptionRu: aiResult.theme.description_ru || '',
      customTotalPrice: aiResult.pricing.bundle_price ? String(aiResult.pricing.bundle_price) : '',
    });
    setEditing(null);
    setAiOpen(false);
    setShowForm(true);
  };

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const payload = new FormData();
    payload.append('slug', form.slug);
    payload.append('sort_order', String(form.sortOrder || 0));
    payload.append('is_active', form.isActive ? '1' : '0');
    payload.append('festive_effect', form.festiveEffect ? '1' : '0');
    payload.append('custom_total_price', String((form as any).customTotalPrice || ''));
    payload.append('delivery_price', String((form as any).deliveryPrice || ''));
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
    const serializeItems = (list: CollectionItem[]) =>
      list.map((item, index) => ({
        product_id: item.productId,
        product_type: item.productType,
        quantity: item.quantity,
        sort_order: item.sortOrder ?? index,
      }));

    payload.append('items_json', JSON.stringify(serializeItems(items)));
    payload.append(
      'sections_json',
      JSON.stringify(
        sections.map((s, si) => ({
          name_uz: s.nameUz,
          name_ru: s.nameRu,
          name_en: s.nameEn,
          name_ja: s.nameJa,
          custom_total_price: s.price ? Number(s.price) : null,
          sort_order: si,
          items: serializeItems(s.items),
          children: s.children.map((c, ci) => ({
            name_uz: c.nameUz,
            name_ru: c.nameRu,
            name_en: c.nameEn,
            name_ja: c.nameJa,
            custom_total_price: c.price ? Number(c.price) : null,
            sort_order: ci,
            items: serializeItems(c.items),
          })),
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

      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">To'plamlar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Banner orqali ochiladigan tayyor kitob va kanselyariya to'plamlari, ularning sahifa dizayni</p>
        </div>
        <div className="d-flex gap-2">
          <button className="btn btn-light-secondary" onClick={() => { setAiError(null); setAiOpen(true); }}>
            <i className="ti ti-sparkles me-1"></i>AI tavsiya
          </button>
          <button
            className="btn btn-primary"
            onClick={() => {
              hydrateForm(null);
              setShowForm(true);
            }}
          >
            <i className="ti ti-plus me-1"></i>To'plam qo'shish
          </button>
        </div>
      </div>

      <Modal show={aiOpen} onHide={() => setAiOpen(false)} centered size="lg" scrollable>
        <Modal.Header closeButton>
          <Modal.Title className="f-s-20 f-w-600"><i className="ti ti-sparkles me-2 text-warning"></i>AI to'plam tavsiyasi</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="b-r-15 b-1-light p-3 mb-3 f-s-13 text-muted">
            AI bizning real talab ma'lumotimiz (sotuv, savat, ko'rish, qidiruv) va bozor bilimi asosida ombordagi kitoblardan mavzuli to'plam hamda marketing narxini (seller komissiyasi + soliq hisobga olingan) tavsiya qiladi.
          </div>
          <div className="row g-2 align-items-end mb-3">
            <div className="col-md-6">
              <label className="form-label f-s-13 text-muted f-w-600">Mavzu (ixtiyoriy)</label>
              <input className="form-control" placeholder="masalan: Shaxsiy rivojlanish" value={aiThemeHint} onChange={(e) => setAiThemeHint(e.target.value)} />
            </div>
            <div className="col-md-3">
              <label className="form-label f-s-13 text-muted f-w-600">Kitob soni</label>
              <input className="form-control" type="number" min={2} max={12} placeholder="auto" value={aiSize} onChange={(e) => setAiSize(e.target.value)} />
            </div>
            <div className="col-md-3">
              <button className="btn btn-primary w-100" onClick={runAiRecommend} disabled={aiLoading}>
                {aiLoading ? <span className="spinner-border spinner-border-sm" /> : <><i className="ti ti-wand me-1"></i>Tahlil</>}
              </button>
            </div>
          </div>

          {aiError ? <div className="alert alert-light-danger py-2 px-3 f-s-13 mb-3">{aiError}</div> : null}
          {aiLoading ? <div className="text-center text-muted py-4"><span className="spinner-border spinner-border-sm me-2" />Chuqur tahlil qilinmoqda…</div> : null}

          {aiResult ? (
            <div>
              <div className="mb-3">
                <div className="f-w-600 f-s-20">{aiResult.theme.title_uz || '—'}</div>
                {aiResult.theme.subtitle_uz ? <div className="text-muted">{aiResult.theme.subtitle_uz}</div> : null}
                {aiResult.theme.description_uz ? <div className="f-s-13 text-muted mt-1">{aiResult.theme.description_uz}</div> : null}
              </div>

              <div className="table-responsive app-scroll mb-3">
                <table className="table table-bottom-border align-middle">
                  <thead><tr><th></th><th>Kitob</th><th>Narx</th><th>Talab (sot/savat/ko'r)</th></tr></thead>
                  <tbody>
                    {aiResult.books.map((b) => (
                      <tr key={b.id}>
                        <td><div className="b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 w-35 h-45">{b.image ? <img className="w-100 h-100 object-fit-cover" src={b.image} alt="" /> : <i className="ti ti-book"></i>}</div></td>
                        <td><div className="f-w-600">{b.name}</div><small className="text-muted">{[b.author, b.seller].filter(Boolean).join(' · ')}</small></td>
                        <td className="f-w-600">{money(b.price)}</td>
                        <td><span className="badge text-light-secondary">{b.sales} / {b.carts} / {b.views}</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="b-r-15 b-1-light p-3 mb-3">
                <div className="f-w-600 mb-2"><i className="ti ti-coins me-1 text-success"></i>Narx tahlili — marketing, margin-himoyalangan</div>
                <div className="row g-2 f-s-13">
                  <InfoRow label="Alohida narx (jami)" value={money(aiResult.pricing.gross_retail)} />
                  <InfoRow label="Chegirma" value={`−${money(aiResult.pricing.applied_discount)} · ${aiResult.pricing.applied_discount_percent}%`} />
                  <InfoRow label="To'plam narxi" value={money(aiResult.pricing.bundle_price)} strong />
                  <InfoRow label="Seller to'lovi" value={money(aiResult.pricing.seller_payout)} />
                  <InfoRow label="Platforma komissiyasi" value={money(aiResult.pricing.total_commission)} />
                  <InfoRow label={`Soliq (${aiResult.pricing.tax_mode})`} value={money(aiResult.pricing.tax)} />
                  <InfoRow label="To'lov xizmati" value={money(aiResult.pricing.payment_fee)} />
                  <InfoRow label="Platforma sof margini" value={money(aiResult.pricing.platform_net)} strong />
                  <InfoRow label={`Margin poli (${aiResult.pricing.margin_floor_percent}%)`} value={money(aiResult.pricing.margin_floor)} />
                </div>
                {aiResult.pricing.discount_clamped ? <div className="f-s-13 text-warning mt-2"><i className="ti ti-shield-check me-1"></i>Chegirma margin-poliga qarab avtomatik cheklandi (margin himoyalandi).</div> : null}
              </div>

              {aiResult.market_analysis ? <div className="b-r-15 b-1-light p-3 mb-3"><div className="f-w-600 mb-1"><i className="ti ti-trending-up me-1 text-primary"></i>Bozor tahlili (AI bilimi)</div><div className="f-s-13 text-muted">{aiResult.market_analysis}</div></div> : null}

              {aiResult.reasoning ? <div className="b-r-15 b-1-light p-3 mb-3"><div className="f-w-600 mb-1"><i className="ti ti-bulb me-1 text-warning"></i>AI izohi</div><div className="f-s-13 text-muted">{aiResult.reasoning}</div></div> : null}

              <div className="f-s-13 text-muted">
                <i className="ti ti-chart-line me-1"></i>{aiResult.demand.method} · {aiResult.demand.window_days} kun · {aiResult.demand.candidate_count} nomzod
                {aiResult.demand.top_searches.length ? <div className="mt-1">Top qidiruvlar: {aiResult.demand.top_searches.slice(0, 8).join(', ')}</div> : null}
              </div>
            </div>
          ) : null}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="light-secondary" onClick={() => setAiOpen(false)}>Yopish</Button>
          <Button variant="success" disabled={!aiResult} onClick={applyAiRecommendation}><i className="ti ti-check me-1"></i>Qabul qilish va tahrirlash</Button>
        </Modal.Footer>
      </Modal>

      <div className="row">
        {[
          { label: 'Jami to‘plam', value: collections.length, icon: 'ti-stack-2' },
          { label: 'Faol', value: collections.filter((item) => item.isActive).length, icon: 'ti-circle-check' },
          { label: 'Mahsulotlar', value: collections.reduce((sum, item) => sum + item.itemCount, 0), icon: 'ti-package' },
          {
            label: totalUnavailable > 0 ? 'Tugagan mahsulot' : 'Jami summa',
            value: totalUnavailable > 0 ? totalUnavailable : `${fmt(collections.reduce((sum, item) => sum + item.totalAmount, 0))} so'm`,
            icon: totalUnavailable > 0 ? 'ti-alert-triangle' : 'ti-cash',
            danger: totalUnavailable > 0,
          },
        ].map((stat, kpiIndex) => (<div className="col-xl-3 col-md-6" key={stat.label}>
          <StatWidget index={kpiIndex} variant={(stat as { danger?: boolean }).danger ? 'danger' : undefined} label={stat.label} value={stat.value} />
        </div>))}
      </div>

      {/* Qidiruv + filtr paneli */}
      <div className="card">
<div className="card-body">
          <div className="row g-2 align-items-center">
            <div className="col-lg-5">
              <div className="position-relative">
                <i className="ti ti-search position-absolute text-secondary" style={{ left: 14, top: 11 }}></i>
                <Form.Control
                  value={listQuery}
                  onChange={(event) => setListQuery(event.target.value)}
                  placeholder="To'plam nomi yoki slug bo'yicha qidiring"
                  style={{ paddingLeft: 38 }}
                />
              </div>
            </div>
            <div className="col-lg-4">
              <div className="nav kc-segment w-100" role="group">
                {([
                  ['all', 'Barchasi'],
                  ['active', 'Faol'],
                  ['hidden', 'Yashirin'],
                ] as [StatusFilter, string][]).map(([value, label]) => (
                  <div key={value} className="nav-item"><button
                      type="button"
                      className={`nav-link ${statusFilter === value ? 'active' : ''}`}
                      onClick={() => setStatusFilter(value)}>
                      {label}
                    </button></div>
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
</div>

      {visibleCollections.length === 0 ? (
        <div className="card">
          <div className="card-body py-5 text-center">
            <i className="ti ti-stack-2 f-s-40 text-muted"></i>
            <div className="mt-2 f-w-600">To'plam topilmadi</div>
            <div className="text-muted f-s-13">Qidiruv yoki filtrlarni o'zgartiring, yoki yangi to'plam qo'shing.</div>
          </div>
        </div>
      ) : null}

      <div className="row">
        {visibleCollections.map((collection) => {
          const outOfStock = collection.itemCount - collection.availableItemCount;
          return (
            <div className="col-xl-6" key={collection.id}>
              <div className="card h-100">
                <div className="card-body">
                  <div
                    className="b-r-15 p-3 mb-3 text-white"
                    style={{ background: `linear-gradient(135deg, ${collection.gradientFrom}, ${collection.gradientTo})` }}
                  >
                    <div className="d-flex justify-content-between align-items-start gap-3">
                      <div>
                        <div className="f-s-13 opacity-75">/{collection.slug}</div>
                        <div className="f-w-600 f-s-24">{collection.titleUz}</div>
                        <div className="f-s-13 mt-1" style={{ maxWidth: 420 }}>{collection.subtitleUz || 'Subtitle kiritilmagan'}</div>
                      </div>
                      <span className={`badge ${collection.isActive ? 'text-light-success' : 'text-light-secondary'}`}>{collection.isActive ? 'Faol' : 'Yashirin'}</span>
                    </div>
                  </div>

                  <div className="d-flex flex-wrap gap-2 mb-3">
                    <span className="badge text-light-secondary">{collection.itemCount} ta mahsulot</span>
                    <span className="badge text-light-secondary">{collection.availableItemCount} ta tayyor</span>
                    {outOfStock > 0 ? <span className="badge text-danger" style={{ background: 'rgba(var(--danger), .3)' }}>{outOfStock} ta tugagan</span> : null}
                    {collection.customTotalPrice ? <span className="badge text-light-primary">Qo'lda narx</span> : null}
                    {(collection.sections?.length ?? 0) > 0 ? <span className="badge text-primary" style={{ background: 'rgba(var(--primary), .1)' }}><i className="ti ti-hierarchy me-1"></i>{collection.sections!.length} bo'lim</span> : null}
                    <span className="badge text-light-secondary">{fmt(collection.totalAmount)} so'm</span>
                  </div>

                  {collection.customTotalPrice ? (
                    <div className="f-s-13 text-muted mb-3">Asl yig'indi: {fmt(collection.baseTotalAmount)} so'm</div>
                  ) : null}

                  <div className="table-responsive app-scroll mb-3 overflow-y-auto" style={{ maxHeight: 220 }}>
                    <table className="table table-bottom-border align-middle">
                      <thead>
                        <tr>
                          <th>Mahsulot</th>
                          <th>Tur</th>
                          <th>Soni</th>
                          <th>Narx</th>
                        </tr>
                      </thead>
                      <tbody>
                        {flattenCollectionItems(collection).map((item, idx) => (
                          <tr key={`${collection.id}-${item.productType}-${item.productId}-${idx}`} style={!item.available ? { opacity: 0.55 } : undefined}>
                            <td>
                              <div className="d-flex align-items-center gap-2">
                                {item.image ? (
                                  <img className="b-r-8 object-fit-cover" src={item.image} alt="" width={34} height={34} />
                                ) : (
                                  <div className="b-r-8 bg-light-secondary d-flex align-items-center justify-content-center" style={{ width: 34, height: 34 }}>
                                    <i className={`ti ${item.productType === 'stationery' ? 'ti-pencil' : 'ti-book'} text-muted`}></i>
                                  </div>
                                )}
                                <div className="min-w-0">
                                  <div className="f-w-600 text-truncate" style={{ maxWidth: 180 }}>{item.name}</div>
                                  {item.sectionName ? <small className="text-primary d-block"><i className="ti ti-hierarchy me-1"></i>{item.sectionName}</small> : null}
                                  <p className="mb-0 text-secondary">{item.author || (item.available ? item.seller : 'Tugagan')}</p>
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
                    <button className="btn btn-sm btn-light-secondary flex-fill" onClick={() => { hydrateForm(collection); setShowForm(true); }}>
                      <i className="ti ti-pencil me-1"></i>Tahrirlash
                    </button>
                    <button className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" title="Nusxa olish" onClick={() => duplicate(collection)}>
                      <i className="ti ti-files"></i>
                    </button>
                    <button className="btn btn-sm btn-light-secondary" title={collection.isActive ? 'Yashirish' : 'Faollashtirish'} onClick={() => toggle(collection)}>
                      <i className={`ti ${collection.isActive ? 'ti-eye-off' : 'ti-eye'}`}></i>
                    </button>
                    <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" title="O'chirish" onClick={() => destroy(collection)}>
                      <i className="ti ti-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      <Modal show={showForm} onHide={() => { setShowForm(false); resetForm(); }} size="xl" centered>
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <Modal.Title className="f-s-20 f-w-600">{editing ? "To'plamni tahrirlash" : "To'plam qo'shish"}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {Object.keys(errors).length > 0 ? (
              <div className="alert alert-light-danger">
                <div className="f-w-600 mb-1">To'plamni saqlashda xatolik bor.</div>
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
                  <div className="col-md-6">
                    <Form.Label>Yetkazish narxi</Form.Label>
                    <Form.Control
                      type="number"
                      min={0}
                      value={(form as any).deliveryPrice}
                      onChange={(event) => setForm((prev) => ({ ...prev, deliveryPrice: event.target.value }))}
                      placeholder="Bo'sh yoki 0 = bepul yetkazish"
                    />
                    <div className="form-text">
                      {Number((form as any).deliveryPrice || 0) > 0
                        ? `Buyurtmaga ${fmt(Number((form as any).deliveryPrice || 0))} so'm qo'shiladi — qayerdan buyurtma qilinishidan qat'i nazar.`
                        : 'Bepul yetkazish — mijozdan qo\'shimcha haq olinmaydi.'}
                    </div>
                  </div>

                  <div className="col-12">
                    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 b-r-15 b-1-light px-3 py-2">
                      <div>
                        <div className="f-w-600">UZ matndan AI tarjima</div>
                        <div className="f-s-13 text-muted">Nomi, subtitle, tavsif va bo'lim nomlari RU, EN, JA maydonlariga to'ldiriladi.</div>
                      </div>
                      <div className="d-flex flex-wrap gap-2">
                        {(['ru', 'en', 'ja'] as TranslateLocale[]).map((locale) => (
                          <button
                            key={locale}
                            type="button"
                            className="btn btn-sm btn-light-secondary"
                            disabled={translatingLocales.length > 0}
                            onClick={() => translateFromUz([locale])}
                          >
                            {translatingLocales.includes(locale) ? '...' : translateLocaleLabels[locale]}
                          </button>
                        ))}
                        <button
                          type="button"
                          className="btn btn-sm btn-primary"
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
                    {heroPreview ? <img src={heroPreview} alt="" className="mt-2 b-r-10" style={{ maxHeight: 90 }} /> : null}
                  </div>
                  <div className="col-12 d-flex flex-wrap gap-4">
                    <Form.Check type="switch" label="Faol" checked={form.isActive} onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))} />
                    <Form.Check
                      type="switch"
                      id="festive-effect-switch"
                      label={<span><i className="ti ti-sparkles text-warning me-1"></i>Bayramona effekt (yulduzcha animatsiyasi)</span>}
                      checked={form.festiveEffect}
                      onChange={(event) => setForm((prev) => ({ ...prev, festiveEffect: event.target.checked }))}
                    />
                  </div>
                </div>
              </div>

              <div className="col-lg-5">
                <div className="b-r-15 p-3 mb-3 text-white" style={{ background: `linear-gradient(135deg, ${form.gradientFrom}, ${form.gradientTo})` }}>
                  <div className="f-s-13 opacity-75">Preview</div>
                  <div className="f-w-600 f-s-24 mt-2">{form.titleUz || "To'plam nomi"}</div>
                  <div className="f-s-13 mt-2">{form.subtitleUz || 'Subtitle shu yerda ko‘rinadi'}</div>
                  {Number((form as any).customTotalPrice || 0) > 0 ? (
                    <div className="f-s-13 mt-2 opacity-75">Asl yig'indi: {fmt(totalAmount)} so'm</div>
                  ) : null}
                  <button
                    type="button"
                    className="btn mt-3 b-r-50"
                    style={{ background: form.buttonBgColor, color: form.buttonTextColor, paddingInline: 18 }}
                  >
                    {fmt(Number((form as any).customTotalPrice || 0) > 0 ? Number((form as any).customTotalPrice || 0) : totalAmount)} so'mga sotib olish
                  </button>
                </div>

                <div className="card">
                  <div className="card-body">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                      <div className="f-w-600">Mahsulot qo'shish</div>
                      <span className="text-muted f-s-13">{bookCount} kitob · {stationeryCount} kanselyariya</span>
                    </div>

                    {/* Kitob / Kanselyariya tab */}
                    <div className="nav kc-segment mb-2" role="group">
                      <div className="nav-item"><button
                          type="button"
                          className={`nav-link ${searchType === 'book' ? 'active' : ''}`}
                          onClick={() => { setSearchType('book'); setSearchResults([]); }}
                        >
                          <i className="ti ti-book me-1"></i>Kitob
                        </button></div>
                      <div className="nav-item"><button
                          type="button"
                          className={`nav-link ${searchType === 'stationery' ? 'active' : ''}`}
                          onClick={() => { setSearchType('stationery'); setSearchResults([]); }}
                        >
                          <i className="ti ti-pencil me-1"></i>Kanselyariya
                        </button></div>
                    </div>

                    <Form.Control
                      value={search}
                      onChange={(event) => setSearch(event.target.value)}
                      placeholder={searchType === 'book' ? "Nomi, muallif yoki artikul bo'yicha" : "Nomi, artikul yoki barkod bo'yicha"}
                      className="mb-2"
                    />

                    <div className="d-flex align-items-center gap-2 mb-2 f-s-13 flex-wrap">
                      <span className="text-muted">Qo'shilmoqda:</span>
                      <span className={`badge ${activeKey === 'root' && sections.length > 0 ? 'text-light-warning' : 'text-light-primary'}`}>
                        {activeKey === 'root' && sections.length > 0 ? "Bo'lim tanlang!" : findNodeName(activeKey)}
                      </span>
                      {activeKey !== 'root' && sections.length === 0 ? (
                        <button type="button" className="btn btn-sm btn-light-secondary py-0" onClick={() => setActiveKey('root')}>To'plamga (umumiy)</button>
                      ) : null}
                    </div>

                    <div className="b-1-light b-r-15 p-2 mb-3 overflow-y-auto" style={{ minHeight: 112, maxHeight: 220 }}>
                      {searchLoading ? <div className="text-muted f-s-13">Qidirilmoqda...</div> : null}
                      {!searchLoading && searchResults.length === 0 ? <div className="text-muted f-s-13">Qidirsangiz natijalar shu yerda chiqadi.</div> : null}
                      {searchResults.map((product) => (
                        <button
                          type="button"
                          key={itemKey(product.productType, product.id)}
                          className="btn btn-light-secondary w-100 text-start mb-2 d-flex align-items-center gap-2"
                          onClick={() => addProduct(product)}
                        >
                          {product.image ? (
                            <img className="b-r-8 object-fit-cover" src={product.image} alt="" width={36} height={36} />
                          ) : (
                            <div className="b-r-8 bg-light-secondary d-flex align-items-center justify-content-center" style={{ width: 36, height: 36 }}>
                              <i className={`ti ${product.productType === 'stationery' ? 'ti-pencil' : 'ti-book'} text-muted`}></i>
                            </div>
                          )}
                          <div className="min-w-0">
                            <div className="f-w-600 text-truncate">{product.name}</div>
                            <div className="f-s-13 text-muted text-truncate">
                              {product.author || product.seller || '—'} · {fmt(product.price)} so'm · {product.stock > 0 ? `${product.stock} dona` : 'tugagan'}
                            </div>
                          </div>
                        </button>
                      ))}
                    </div>

                    {sections.length > 0 ? (
                      <div className="alert alert-border-secondary py-2 px-3 f-s-13 mb-2">
                        <i className="ti ti-info-circle me-1"></i>Bu bo'limli to'plam — mahsulotlar faqat bo'limlar ichida boshqariladi (bo'limsiz mahsulot qo'shilmaydi).
                      </div>
                    ) : (
                      <>
                        <div className="d-flex justify-content-between align-items-center mb-1">
                          <div className="f-w-600 f-s-13">Tanlangan ({items.length})</div>
                          {unavailableCount > 0 ? <span className="f-s-13 text-danger">{unavailableCount} ta tugagan</span> : null}
                        </div>
                        <div className="f-s-13 text-muted mb-2">Tartibni sudrab (drag) o'zgartiring.</div>
                      </>
                    )}

                    <div className="b-1-light b-r-15 p-2 overflow-y-auto" style={{ maxHeight: 360, display: sections.length > 0 ? 'none' : undefined }}>
                      {items.length === 0 ? <div className="text-muted f-s-13">Hali mahsulot tanlanmagan.</div> : null}
                      {items.map((item, index) => (
                        <div
                          key={`${item.productType}-${item.productId}-${index}`}
                          className="b-1-light b-r-15 p-2 mb-2"
                          draggable
                          onDragStart={() => onDragStart(index)}
                          onDragOver={onDragOver}
                          onDrop={() => onDrop(index)}
                          style={{ cursor: 'grab',
                            background: dragIndex === index ? 'rgba(var(--primary), .1)' : undefined,
                            borderColor: !item.available ? 'rgba(var(--danger), 1)' : undefined,
                          }}
                        >
                          <div className="d-flex justify-content-between gap-2">
                            <div className="d-flex align-items-center gap-2 min-w-0">
                              <i className="ti ti-grip-vertical text-muted"></i>
                              {item.image ? (
                                <img className="b-r-6 object-fit-cover" src={item.image} alt="" width={32} height={32} />
                              ) : null}
                              <div className="min-w-0">
                                <div className="f-w-600 text-truncate">{item.name}</div>
                                <div className="f-s-13 text-muted text-truncate">
                                  {item.productType === 'stationery' ? 'Kanselyariya' : 'Kitob'} · {item.seller || '—'}
                                  {!item.available ? ' · tugagan' : ''}
                                </div>
                              </div>
                            </div>
                            <button type="button" className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => removeItem(index)}>
                              <i className="ti ti-trash"></i>
                            </button>
                          </div>
                          <div className="row g-2 mt-1">
                            <div className="col-6">
                              <Form.Label className="f-s-13 text-muted mb-1">Soni</Form.Label>
                              <div className="input-group input-group-sm">
                                <button type="button" className="btn btn-light-secondary" onClick={() => updateItem(index, { quantity: Math.max(1, item.quantity - 1) })}>−</button>
                                <Form.Control
                                  type="number"
                                  min={1}
                                  className="text-center"
                                  value={item.quantity}
                                  onChange={(event) => updateItem(index, { quantity: Math.max(1, Number(event.target.value || 1)) })}
                                />
                                <button type="button" className="btn btn-light-secondary" onClick={() => updateItem(index, { quantity: item.quantity + 1 })}>+</button>
                              </div>
                            </div>
                            <div className="col-6">
                              <Form.Label className="f-s-13 text-muted mb-1">Jami narx</Form.Label>
                              <Form.Control value={`${fmt(item.price * item.quantity)} so'm`} disabled />
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>

                    <div className="mt-3">
                      <div className="d-flex justify-content-between align-items-center mb-1">
                        <div className="f-w-600 f-s-13"><i className="ti ti-hierarchy me-1"></i>Bo'limlar (ixtiyoriy)</div>
                        <button type="button" className="btn btn-sm btn-light-secondary" onClick={addSection}><i className="ti ti-plus me-1"></i>Bo'lim</button>
                      </div>
                      <div className="f-s-13 text-muted mb-2">Bo'lim qo'shsangiz to'plam sinf/tur bo'yicha bo'linadi (har biriga 4 tilda nom + alohida narx). Bo'lim ichida ichki bo'lim bo'lsa — u <b>guruh</b>ga aylanadi (mahsulot faqat ichki bo'limlarga). Bo'lim qo'shmasangiz oddiy to'plam bo'lib qoladi.</div>
                      {sections.length > 0 ? (() => {
                        let leaves = 0;
                        let products = 0;
                        let autoTotal = 0;
                        const walk = (n: SectionNode) => {
                          if (n.children.length > 0) {
                            n.children.forEach(walk);
                          } else {
                            leaves += 1;
                            products += n.items.length;
                            autoTotal += sectionItemsTotal(n);
                          }
                        };
                        sections.forEach(walk);
                        return (
                          <div className="d-flex flex-wrap gap-2 mb-2">
                            <span className="badge text-light-primary">{sections.length} bosh bo'lim</span>
                            <span className="badge text-light-secondary">{leaves} sotiladigan bo'lim</span>
                            <span className="badge text-light-secondary">{products} mahsulot</span>
                            <span className="badge text-light-success">avto jami: {autoTotal.toLocaleString('ru-RU')} so'm</span>
                          </div>
                        );
                      })() : null}
                      {sections.length === 0 ? <div className="text-muted f-s-13">Bo'lim yo'q. "Bo'lim" tugmasini bosib qo'shing.</div> : null}
                      {sections.map((section) => renderNode(section, 1))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={() => { setShowForm(false); resetForm(); }}>Bekor qilish</Button>
            <Button type="submit" className="btn-primary border-0" disabled={!hasAnyProduct}>Saqlash</Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
