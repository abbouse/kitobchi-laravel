import { router, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Button, Form, Modal } from 'react-bootstrap';
import { splitPolicyPreset } from './policyPresets';

type TranslateLocale = 'ru' | 'en' | 'ja';
type StatusFilter = 'all' | 'active' | 'inactive' | 'in_app' | 'needs_translation';

interface Policy {
  id: number;
  title: string;
  slug: string;
  content?: string;
  publicUrl?: string;
  status: string;
  showInApp: boolean;
  sortOrder: number;
  updatedAtLabel?: string;
  translationsCompleted?: number;
  translations?: Record<TranslateLocale, { title?: string; content?: string }>;
  createUrl?: string;
  updateUrl?: string;
  toggleUrl?: string;
  destroyUrl?: string;
}

type PolicyFormState = {
  title: string;
  slug: string;
  content: string;
  sortOrder: number;
  isActive: boolean;
  showInApp: boolean;
  translations: Record<TranslateLocale, { title: string; content: string }>;
};

type PolicyPayload = {
  title: string;
  slug: string;
  content: string;
  sort_order: number;
  is_active: boolean;
  show_in_app: boolean;
  translations: Record<TranslateLocale, { title: string; content: string }>;
};

const locales: TranslateLocale[] = ['ru', 'en', 'ja'];

const localeLabels: Record<TranslateLocale, string> = {
  ru: 'RU',
  en: 'EN',
  ja: 'JA',
};

const localeTitles: Record<TranslateLocale, string> = {
  ru: 'Ruscha',
  en: 'Inglizcha',
  ja: 'Yaponcha',
};

const defaultTranslations = (): PolicyFormState['translations'] => ({
  ru: { title: '', content: '' },
  en: { title: '', content: '' },
  ja: { title: '', content: '' },
});

const defaultForm = (): PolicyFormState => ({
  title: '',
  slug: '',
  content: '',
  sortOrder: 0,
  isActive: true,
  showInApp: true,
  translations: defaultTranslations(),
});

const slugify = (value: string) =>
  value
    .toLowerCase()
    .trim()
    .replace(/['"`]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

const stripHtml = (value?: string) => (value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

const translationReady = (policy: Policy, locale: TranslateLocale) =>
  Boolean((policy.translations?.[locale]?.title || '').trim() || (policy.translations?.[locale]?.content || '').trim());

const completedTranslations = (policy: Policy) => locales.filter((locale) => translationReady(policy, locale)).length;

const policyCoverage = (policy: Policy) => Math.round(((policy.content ? 1 : 0) + completedTranslations(policy)) / 4 * 100);

const formCoverage = (form: PolicyFormState) => {
  const translations = locales.filter((locale) => form.translations[locale].title.trim() || form.translations[locale].content.trim()).length;
  return Math.round(((form.content.trim() ? 1 : 0) + translations) / 4 * 100);
};

const policyHealth = (policy: Policy) => {
  if (policy.status !== 'Active') return { label: 'Draft', chip: 'chip-gray', icon: 'bi-eye-slash' };
  if (!policy.content) return { label: 'Matn kerak', chip: 'chip-danger', icon: 'bi-exclamation-triangle' };
  if (completedTranslations(policy) < 3) return { label: 'Tarjima kerak', chip: 'chip-warning', icon: 'bi-translate' };
  if (!policy.showInApp) return { label: 'Web only', chip: 'chip-info', icon: 'bi-globe2' };
  return { label: 'Published', chip: 'chip-success', icon: 'bi-shield-check' };
};

export default function Siyosatlar() {
  const {
    policies = [],
    errors = {},
  } = usePage<{
    policies?: Policy[];
    errors?: Record<string, string>;
  }>().props;

  const createUrl = policies[0]?.createUrl || '/boshqaruv/siyosatlar';
  const [editing, setEditing] = useState<Policy | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [activeLocale, setActiveLocale] = useState<TranslateLocale>('ru');
  const [form, setForm] = useState<PolicyFormState>(defaultForm);

  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      setShowForm(true);
    }
  }, [errors]);

  const counts = useMemo(() => {
    const active = policies.filter((policy) => policy.status === 'Active').length;
    const inactive = policies.length - active;
    const inApp = policies.filter((policy) => policy.showInApp).length;
    const needsTranslation = policies.filter((policy) => completedTranslations(policy) < 3).length;
    const avgCoverage = policies.length
      ? Math.round(policies.reduce((sum, policy) => sum + policyCoverage(policy), 0) / policies.length)
      : 0;

    return { all: policies.length, active, inactive, inApp, needsTranslation, avgCoverage };
  }, [policies]);

  const filteredPolicies = useMemo(() => {
    const clean = query.trim().toLowerCase();

    return policies.filter((policy) => {
      if (statusFilter === 'active' && policy.status !== 'Active') return false;
      if (statusFilter === 'inactive' && policy.status === 'Active') return false;
      if (statusFilter === 'in_app' && !policy.showInApp) return false;
      if (statusFilter === 'needs_translation' && completedTranslations(policy) >= 3) return false;

      if (!clean) return true;

      const searchPool = [
        policy.title,
        policy.slug,
        stripHtml(policy.content),
        policy.translations?.ru?.title || '',
        policy.translations?.en?.title || '',
        policy.translations?.ja?.title || '',
        stripHtml(policy.translations?.ru?.content),
        stripHtml(policy.translations?.en?.content),
        stripHtml(policy.translations?.ja?.content),
      ]
        .join(' ')
        .toLowerCase();

      return searchPool.includes(clean);
    });
  }, [policies, query, statusFilter]);

  const topPolicies = useMemo(() => [...policies].sort((a, b) => a.sortOrder - b.sortOrder).slice(0, 4), [policies]);

  const resetForm = () => {
    setEditing(null);
    setActiveLocale('ru');
    setForm(defaultForm());
  };

  const clearDraft = () => {
    setForm(defaultForm());
    setActiveLocale('ru');
  };

  const hydrateForm = (policy?: Policy | null) => {
    if (!policy) {
      resetForm();
      return;
    }

    setEditing(policy);
    setActiveLocale('ru');
    setForm({
      title: policy.title || '',
      slug: policy.slug || '',
      content: policy.content || '',
      sortOrder: policy.sortOrder ?? 0,
      isActive: policy.status === 'Active',
      showInApp: !!policy.showInApp,
      translations: {
        ru: {
          title: policy.translations?.ru?.title || '',
          content: policy.translations?.ru?.content || '',
        },
        en: {
          title: policy.translations?.en?.title || '',
          content: policy.translations?.en?.content || '',
        },
        ja: {
          title: policy.translations?.ja?.title || '',
          content: policy.translations?.ja?.content || '',
        },
      },
    });
  };

  const openCreate = () => {
    resetForm();
    setShowForm(true);
  };

  const openEdit = (policy: Policy) => {
    hydrateForm(policy);
    setShowForm(true);
  };

  const closeModal = () => {
    setShowForm(false);
    setEditing(null);
  };

  const toggle = (policy: Policy) => {
    if (!policy.toggleUrl) return;
    router.patch(policy.toggleUrl, {}, { preserveScroll: true });
  };

  const destroy = (policy: Policy) => {
    if (!policy.destroyUrl) return;
    if (!window.confirm(`${policy.title} siyosati o'chirilsinmi?`)) return;
    router.delete(policy.destroyUrl, { preserveScroll: true });
  };

  const copyPublicUrl = async (policy: Policy) => {
    if (!policy.publicUrl) return;
    try {
      await navigator.clipboard.writeText(policy.publicUrl);
    } catch {
      window.prompt('Ochiq sahifa havolasi', policy.publicUrl);
    }
  };

  const applySplitPreset = () => {
    const hasContent = form.title.trim() || form.slug.trim() || form.content.trim();
    if (hasContent && !window.confirm("Joriy matn nasiya shabloni bilan almashtirilsinmi?")) {
      return;
    }

    setForm((prev) => ({
      ...prev,
      title: splitPolicyPreset.title,
      slug: splitPolicyPreset.slug,
      content: splitPolicyPreset.content,
      showInApp: true,
      sortOrder: prev.sortOrder || 30,
    }));
  };

  const fieldError = (key: string) => errors[key];

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const payload: PolicyPayload = {
      title: form.title,
      slug: form.slug,
      content: form.content,
      sort_order: Number(form.sortOrder || 0),
      is_active: form.isActive,
      show_in_app: form.showInApp,
      translations: {
        ru: {
          title: form.translations.ru.title || '',
          content: form.translations.ru.content || '',
        },
        en: {
          title: form.translations.en.title || '',
          content: form.translations.en.content || '',
        },
        ja: {
          title: form.translations.ja.title || '',
          content: form.translations.ja.content || '',
        },
      },
    };

    const options = {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeModal();
        resetForm();
      },
    };

    if (editing?.updateUrl) {
      router.put(editing.updateUrl, payload, options);
      return;
    }

    router.post(createUrl, payload, options);
  };

  const currentPreview = activeLocale === 'ru'
    ? form.translations.ru
    : activeLocale === 'en'
      ? form.translations.en
      : form.translations.ja;

  return (
    <div>
      <div className="page-head">
        <div>
          <div className="d-flex flex-wrap gap-2 mb-2">
            <span className="chip chip-purple"><i className="bi bi-shield-lock me-1"></i>Legal center</span>
            <span className="chip chip-info"><i className="bi bi-globe2 me-1"></i>Web + App</span>
          </div>
          <h1 className="page-title">Siyosatlar va qoidalar</h1>
          <p className="page-subtitle">
            Platforma shartlari, maxfiylik hujjatlari va app ichidagi legal matnlarni bitta joydan boshqaring.
          </p>
        </div>
        <button className="btn btn-primary-gradient" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i>Yangi siyosat
        </button>
      </div>

      <div className="row g-3 mb-3">
        {[
          { label: 'Jami hujjatlar', value: counts.all, icon: 'bi-file-earmark-text', chip: 'chip-info' },
          { label: 'Published', value: counts.active, icon: 'bi-check-circle', chip: 'chip-success' },
          { label: 'Appda ko‘rinadi', value: counts.inApp, icon: 'bi-phone', chip: 'chip-purple' },
          { label: 'O‘rtacha tayyorlik', value: `${counts.avgCoverage}%`, icon: 'bi-activity', chip: counts.avgCoverage >= 75 ? 'chip-success' : 'chip-warning' },
        ].map((item) => (
          <div className="col-md-3" key={item.label}>
            <div className="card-panel h-100">
              <div className="d-flex justify-content-between align-items-start">
                <div>
                  <div className="small text-muted">{item.label}</div>
                  <div className="fs-4 fw-bold mt-2">{item.value}</div>
                </div>
                <span className={`chip ${item.chip}`}><i className={`bi ${item.icon}`}></i></span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="row g-3 mb-3">
        <div className="col-xl-8">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Policy registry</div>
                <div className="small text-muted">Qidiruv, status va tarjima holati bo‘yicha nazorat.</div>
              </div>
              <span className="chip chip-gray">{filteredPolicies.length} natija</span>
            </div>
            <div className="row g-2 align-items-end">
              <div className="col-lg-6">
                <Form.Label>Qidiruv</Form.Label>
                <Form.Control
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  placeholder="Sarlavha, slug, matn yoki tarjima bo‘yicha qidiring"
                />
              </div>
              <div className="col-lg-3">
                <Form.Label>Filter</Form.Label>
                <Form.Select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}>
                  <option value="all">Barchasi</option>
                  <option value="active">Published</option>
                  <option value="inactive">Draft / yashirin</option>
                  <option value="in_app">Appda ko‘rinadi</option>
                  <option value="needs_translation">Tarjima kerak</option>
                </Form.Select>
              </div>
              <div className="col-lg-3">
                <Form.Label>Quick action</Form.Label>
                <button type="button" className="btn btn-light w-100" onClick={() => { setQuery(''); setStatusFilter('all'); }}>
                  <i className="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
              </div>
            </div>
          </div>
        </div>
        <div className="col-xl-4">
          <div className="card-panel h-100">
            <div className="panel-head">
              <div>
                <div className="panel-title">Review queue</div>
                <div className="small text-muted">E’tibor kerak bo‘lgan hujjatlar.</div>
              </div>
              <span className={`chip ${counts.needsTranslation ? 'chip-warning' : 'chip-success'}`}>{counts.needsTranslation}</span>
            </div>
            <div className="d-grid gap-2">
              {topPolicies.length ? topPolicies.map((policy) => {
                const health = policyHealth(policy);
                return (
                  <button key={policy.id} type="button" className="btn btn-light text-start" onClick={() => openEdit(policy)}>
                    <div className="d-flex justify-content-between gap-2">
                      <span className="fw-semibold text-truncate">{policy.title}</span>
                      <span className={`chip ${health.chip}`}><i className={`bi ${health.icon} me-1`}></i>{health.label}</span>
                    </div>
                    <div className="progress mt-2" style={{ height: 5 }}>
                      <div className="progress-bar" style={{ width: `${policyCoverage(policy)}%` }}></div>
                    </div>
                  </button>
                );
              }) : (
                <div className="text-muted small">Hali siyosat yo‘q.</div>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3">
        {filteredPolicies.map((policy) => {
          const health = policyHealth(policy);
          const coverage = policyCoverage(policy);
          const excerpt = stripHtml(policy.content).slice(0, 150);

          return (
            <div className="col-xl-6" key={policy.id}>
              <div className="card-panel h-100 d-flex flex-column">
                <div className="d-flex justify-content-between align-items-start gap-3 mb-3">
                  <div className="min-w-0">
                    <div className="d-flex flex-wrap align-items-center gap-2 mb-1">
                      <span className="chip chip-gray">#{policy.id}</span>
                      <span className={`chip ${health.chip}`}><i className={`bi ${health.icon} me-1`}></i>{health.label}</span>
                      {policy.showInApp ? <span className="chip chip-purple">App</span> : <span className="chip chip-gray">Web only</span>}
                    </div>
                    <h5 className="mb-1">{policy.title}</h5>
                    <div className="small text-muted">
                      <code>{policy.slug}</code>
                      {policy.updatedAtLabel ? <span className="ms-2">Yangilangan: {policy.updatedAtLabel}</span> : null}
                    </div>
                  </div>
                  <div className="form-check form-switch">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      checked={policy.status === 'Active'}
                      onChange={() => toggle(policy)}
                      title="Published/Draft"
                    />
                  </div>
                </div>

                <p className="text-muted small flex-grow-1 mb-3">{excerpt || 'Matn hali kiritilmagan.'}{excerpt.length >= 150 ? '…' : ''}</p>

                <div className="mb-3">
                  <div className="d-flex justify-content-between small mb-1">
                    <span className="text-muted">Tayyorlik</span>
                    <strong>{coverage}%</strong>
                  </div>
                  <div className="progress" style={{ height: 6 }}>
                    <div className={`progress-bar ${coverage < 50 ? 'bg-danger' : coverage < 100 ? 'bg-warning' : 'bg-success'}`} style={{ width: `${coverage}%` }}></div>
                  </div>
                </div>

                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <div className="d-flex flex-wrap gap-1">
                    {locales.map((locale) => {
                      const ready = translationReady(policy, locale);
                      return (
                        <span key={locale} className={`chip ${ready ? 'chip-success' : 'chip-gray'}`}>
                          {localeLabels[locale]}
                        </span>
                      );
                    })}
                    <span className="chip chip-gray">Sort: {policy.sortOrder}</span>
                  </div>
                  <div className="d-flex gap-1">
                    <button className="btn btn-sm btn-light" onClick={() => openEdit(policy)}>
                      <i className="bi bi-pencil me-1"></i>Edit
                    </button>
                    {policy.publicUrl ? (
                      <>
                        <a className="btn btn-sm btn-light" href={policy.publicUrl} target="_blank" rel="noreferrer">
                          <i className="bi bi-box-arrow-up-right"></i>
                        </a>
                        <button className="btn btn-sm btn-light" onClick={() => copyPublicUrl(policy)}>
                          <i className="bi bi-link-45deg"></i>
                        </button>
                      </>
                    ) : null}
                    <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(policy)}>
                      <i className="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          );
        })}

        {filteredPolicies.length === 0 ? (
          <div className="col-12">
            <div className="card-panel text-center py-5">
              <div className="fs-2 text-muted mb-2"><i className="bi bi-search"></i></div>
              <div className="fw-semibold">Mos siyosat topilmadi</div>
              <div className="text-muted small mt-1">Filterlarni tozalang yoki yangi siyosat qo‘shing.</div>
            </div>
          </div>
        ) : null}
      </div>

      <Modal show={showForm} onHide={closeModal} centered size="xl">
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <div>
              <Modal.Title className="fs-5 fw-bold">
                {editing ? 'Siyosatni tahrirlash' : "Yangi siyosat"}
              </Modal.Title>
              <div className="small text-muted">
                UZ asosiy matn, public URL, app visibility va tarjimalar bitta workflowda boshqariladi.
              </div>
            </div>
          </Modal.Header>
          <Modal.Body style={{ maxHeight: 'calc(100vh - 140px)', overflowY: 'auto' }}>
            <div className="row g-3">
              {Object.keys(errors).length > 0 ? (
                <div className="col-12">
                  <div className="alert alert-danger rounded-4 mb-0">
                    <div className="fw-semibold mb-1">Saqlashda xatolik bor.</div>
                    <div className="small">To‘ldirilgan maydonlar saqlanmasa, sahifani yangilamasdan qayta saqlang. Endi forma oddiy payload bilan yuboriladi.</div>
                  </div>
                </div>
              ) : null}

              <div className="col-xl-8">
                <div className="rounded-4 border p-3 h-100">
                  <div className="d-flex flex-wrap justify-content-between gap-3 mb-3">
                    <div>
                      <div className="fw-semibold">Document setup</div>
                      <div className="small text-muted">Legal hujjat identifikatori, holati va platformalarda ko‘rinishi.</div>
                    </div>
                    <div className="d-flex flex-wrap gap-2">
                      <button type="button" className="btn btn-sm btn-dark" onClick={applySplitPreset}>
                        <i className="bi bi-file-earmark-richtext me-1"></i>Nasiya shabloni
                      </button>
                      <button
                        type="button"
                        className="btn btn-sm btn-light"
                        onClick={() => setForm((prev) => ({ ...prev, slug: slugify(prev.slug || prev.title) }))}
                      >
                        Slug yaratish
                      </button>
                      <button type="button" className="btn btn-sm btn-light" onClick={clearDraft}>
                        Tozalash
                      </button>
                    </div>
                  </div>

                  <div className="row g-3">
                    <div className="col-md-7">
                      <Form.Label>Sarlavha (UZ)</Form.Label>
                      <Form.Control
                        required
                        value={form.title}
                        onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))}
                        placeholder="Masalan: Kitobchi nasiya xizmati shartlari"
                        isInvalid={!!fieldError('title')}
                      />
                      <Form.Control.Feedback type="invalid">{fieldError('title')}</Form.Control.Feedback>
                    </div>
                    <div className="col-md-5">
                      <Form.Label>Slug</Form.Label>
                      <Form.Control
                        value={form.slug}
                        onChange={(event) => setForm((prev) => ({ ...prev, slug: event.target.value }))}
                        placeholder="nasiya-shartlari"
                        isInvalid={!!fieldError('slug')}
                      />
                      <Form.Control.Feedback type="invalid">{fieldError('slug')}</Form.Control.Feedback>
                    </div>
                    <div className="col-md-3">
                      <Form.Label>Tartib</Form.Label>
                      <Form.Control
                        type="number"
                        min={0}
                        value={form.sortOrder}
                        onChange={(event) =>
                          setForm((prev) => ({ ...prev, sortOrder: Number(event.target.value || 0) }))
                        }
                        isInvalid={!!fieldError('sort_order')}
                      />
                      <Form.Control.Feedback type="invalid">{fieldError('sort_order')}</Form.Control.Feedback>
                    </div>
                    <div className="col-md-3 d-flex align-items-end">
                      <Form.Check
                        type="switch"
                        label="Published"
                        checked={form.isActive}
                        onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
                      />
                    </div>
                    <div className="col-md-3 d-flex align-items-end">
                      <Form.Check
                        type="switch"
                        label="Appda ko‘rinsin"
                        checked={form.showInApp}
                        onChange={(event) => setForm((prev) => ({ ...prev, showInApp: event.target.checked }))}
                      />
                    </div>
                    <div className="col-md-3 d-flex align-items-end">
                      {editing?.publicUrl ? (
                        <a href={editing.publicUrl} target="_blank" rel="noreferrer" className="btn btn-light w-100">
                          <i className="bi bi-box-arrow-up-right me-1"></i>Preview
                        </a>
                      ) : (
                        <div className="small text-muted">Saqlangach preview havolasi chiqadi.</div>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              <div className="col-xl-4">
                <div className="rounded-4 border p-3 h-100">
                  <div className="fw-semibold mb-2">Publishing readiness</div>
                  <div className="d-flex justify-content-between small mb-1">
                    <span className="text-muted">Tayyorlik</span>
                    <strong>{formCoverage(form)}%</strong>
                  </div>
                  <div className="progress mb-3" style={{ height: 7 }}>
                    <div className="progress-bar" style={{ width: `${formCoverage(form)}%` }}></div>
                  </div>
                  <div className="d-grid gap-2 small">
                    <div className="d-flex justify-content-between"><span>UZ matn</span><span className={`chip ${form.content.trim() ? 'chip-success' : 'chip-danger'}`}>{form.content.trim() ? 'Ready' : 'Required'}</span></div>
                    {locales.map((locale) => (
                      <div className="d-flex justify-content-between" key={locale}>
                        <span>{localeLabels[locale]} tarjima</span>
                        <span className={`chip ${(form.translations[locale].title.trim() || form.translations[locale].content.trim()) ? 'chip-success' : 'chip-gray'}`}>
                          {(form.translations[locale].title.trim() || form.translations[locale].content.trim()) ? 'Ready' : 'Optional'}
                        </span>
                      </div>
                    ))}
                    <div className="d-flex justify-content-between"><span>Platforma</span><span className="chip chip-purple">{form.showInApp ? 'Web + App' : 'Web only'}</span></div>
                  </div>
                </div>
              </div>

              <div className="col-xl-7">
                <Form.Label>Matn (UZ, HTML bo‘lishi mumkin)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={20}
                  required
                  value={form.content}
                  onChange={(event) => setForm((prev) => ({ ...prev, content: event.target.value }))}
                  style={{ fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace' }}
                  isInvalid={!!fieldError('content')}
                />
                <Form.Control.Feedback type="invalid">{fieldError('content')}</Form.Control.Feedback>
              </div>
              <div className="col-xl-5">
                <div className="rounded-4 border h-100 overflow-hidden">
                  <div className="px-3 py-2 border-bottom bg-light fw-semibold d-flex justify-content-between">
                    <span>UZ live preview</span>
                    <span className="text-muted small">{stripHtml(form.content).length} belgi</span>
                  </div>
                  <div
                    className="p-3"
                    style={{ minHeight: 420, maxHeight: 620, overflowY: 'auto', background: '#fff' }}
                  >
                    {form.content.trim() ? (
                      <div dangerouslySetInnerHTML={{ __html: form.content }} />
                    ) : (
                      <div className="text-muted small">Bu yerda HTML preview ko‘rinadi.</div>
                    )}
                  </div>
                </div>
              </div>

              <div className="col-12">
                <div className="rounded-4 border overflow-hidden">
                  <div className="px-3 py-2 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                      <div className="fw-semibold">Translations</div>
                      <div className="small text-muted">Har bir til alohida title va HTML matn sifatida saqlanadi.</div>
                    </div>
                    <div className="btn-group btn-group-sm">
                      {locales.map((locale) => (
                        <button
                          key={locale}
                          type="button"
                          className={`btn ${activeLocale === locale ? 'btn-dark' : 'btn-light'}`}
                          onClick={() => setActiveLocale(locale)}
                        >
                          {localeLabels[locale]}
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="p-3">
                    <div className="row g-3">
                      <div className="col-lg-5">
                        <Form.Label>{localeTitles[activeLocale]} sarlavha</Form.Label>
                        <Form.Control
                          value={form.translations[activeLocale].title}
                          onChange={(event) =>
                            setForm((prev) => ({
                              ...prev,
                              translations: {
                                ...prev.translations,
                                [activeLocale]: {
                                  ...prev.translations[activeLocale],
                                  title: event.target.value,
                                },
                              },
                            }))
                          }
                          isInvalid={!!fieldError(`translations.${activeLocale}.title`)}
                        />
                        <Form.Control.Feedback type="invalid">{fieldError(`translations.${activeLocale}.title`)}</Form.Control.Feedback>
                      </div>
                      <div className="col-lg-7">
                        <div className="d-flex flex-wrap gap-2 pt-lg-4">
                          {locales.map((locale) => (
                            <span key={locale} className={`chip ${(form.translations[locale].title.trim() || form.translations[locale].content.trim()) ? 'chip-success' : 'chip-gray'}`}>
                              {localeLabels[locale]}
                            </span>
                          ))}
                        </div>
                      </div>
                      <div className="col-lg-7">
                        <Form.Label>{localeTitles[activeLocale]} matn</Form.Label>
                        <Form.Control
                          as="textarea"
                          rows={12}
                          value={form.translations[activeLocale].content}
                          onChange={(event) =>
                            setForm((prev) => ({
                              ...prev,
                              translations: {
                                ...prev.translations,
                                [activeLocale]: {
                                  ...prev.translations[activeLocale],
                                  content: event.target.value,
                                },
                              },
                            }))
                          }
                          isInvalid={!!fieldError(`translations.${activeLocale}.content`)}
                        />
                        <Form.Control.Feedback type="invalid">{fieldError(`translations.${activeLocale}.content`)}</Form.Control.Feedback>
                      </div>
                      <div className="col-lg-5">
                        <div className="rounded-4 border h-100 overflow-hidden">
                          <div className="px-3 py-2 border-bottom bg-light fw-semibold">{localeLabels[activeLocale]} preview</div>
                          <div className="p-3" style={{ minHeight: 260, maxHeight: 380, overflowY: 'auto' }}>
                            <h5>{currentPreview.title || 'Sarlavha kiritilmagan'}</h5>
                            {currentPreview.content.trim() ? (
                              <div dangerouslySetInnerHTML={{ __html: currentPreview.content }} />
                            ) : (
                              <div className="text-muted small">Tarjima matni kiritilmagan.</div>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={closeModal}>
              Bekor qilish
            </Button>
            <Button type="submit" className="btn-primary-gradient border-0">
              <i className="bi bi-check2-circle me-1"></i>Saqlash
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
