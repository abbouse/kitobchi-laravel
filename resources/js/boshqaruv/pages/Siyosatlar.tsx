import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Button, Form } from 'react-bootstrap';
import Modal from '../components/AppModal';
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

    return { all: policies.length, active, inactive, inApp, needsTranslation };
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
      ]
        .join(' ')
        .toLowerCase();

      return searchPool.includes(clean);
    });
  }, [policies, query, statusFilter]);

  const resetForm = () => {
    setEditing(null);
    setActiveLocale('ru');
    setForm(defaultForm());
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

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Siyosatlar va qoidalar</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Legal sahifalar va appda ko‘rinadigan siyosatlarni boshqarish</p>
        </div>
        <button className="btn btn-primary" onClick={openCreate}>
          <i className="ti ti-plus me-1"></i>Siyosat qo‘shish
        </button>
      </div>

      <div className="row">
        <div className="col-md-3">
          <div className="card h-100">
            <div className="card-body">
              <div className="f-s-13 text-muted">Jami siyosat</div>
              <div className="f-s-24 f-w-600 mt-2">{counts.all}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card h-100">
            <div className="card-body">
              <div className="f-s-13 text-muted">Faol</div>
              <div className="f-s-24 f-w-600 mt-2 text-success">{counts.active}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card h-100">
            <div className="card-body">
              <div className="f-s-13 text-muted">Appda ko‘rinadi</div>
              <div className="f-s-24 f-w-600 mt-2 text-primary">{counts.inApp}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card h-100">
            <div className="card-body">
              <div className="f-s-13 text-muted">Tarjima kerak</div>
              <div className="f-s-24 f-w-600 mt-2 text-warning">{counts.needsTranslation}</div>
            </div>
          </div>
        </div>
      </div>

      <div className="card">
<div className="card-body">
          <div className="row g-3 align-items-end">
            <div className="col-lg-6">
              <Form.Label>Qidiruv</Form.Label>
              <Form.Control
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="Sarlavha, slug yoki matn bo‘yicha qidiring"
              />
            </div>
            <div className="col-lg-3">
              <Form.Label>Filter</Form.Label>
              <Form.Select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}>
                <option value="all">Barchasi</option>
                <option value="active">Faqat faol</option>
                <option value="inactive">Faqat yashirin</option>
                <option value="in_app">Appda ko‘rinadi</option>
                <option value="needs_translation">Tarjima kerak</option>
              </Form.Select>
            </div>
            <div className="col-lg-3">
              <button type="button" className="btn btn-light-secondary w-100" onClick={() => { setQuery(''); setStatusFilter('all'); }}>
                Filterlarni tozalash
              </button>
            </div>
          </div>
        </div>
</div>

      <div className="card">
<div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Sarlavha</th>
                  <th>Slug / URL</th>
                  <th>Tillar</th>
                  <th>Tartib</th>
                  <th>App</th>
                  <th>Holat</th>
                  <th>Amallar</th>
                </tr>
              </thead>
              <tbody>
                {filteredPolicies.map((policy) => {
                  const translationsReady = completedTranslations(policy);
                  const excerpt = stripHtml(policy.content).slice(0, 110);

                  return (
                    <tr key={policy.id}>
                      <td className="f-w-600 text-nowrap">#{policy.id}</td>
                      <td>
                        <div className="f-w-600">{policy.title}</div>
                        {policy.updatedAtLabel ? (
                          <div className="f-s-13 text-muted mb-1">Yangilangan: {policy.updatedAtLabel}</div>
                        ) : null}
                        <div className="f-s-13 text-muted">{excerpt || 'Matn yo‘q'}{excerpt.length >= 110 ? '…' : ''}</div>
                      </td>
                      <td>
                        <div><code>{policy.slug}</code></div>
                        {policy.publicUrl ? (
                          <a href={policy.publicUrl} target="_blank" rel="noreferrer" className="f-s-13">
                            Ochiq sahifa
                          </a>
                        ) : null}
                      </td>
                      <td>
                        <div className="d-flex flex-wrap gap-1">
                          {locales.map((locale) => (
                            <span key={locale} className={`badge ${translationReady(policy, locale) ? 'text-light-success' : 'text-light-secondary'}`}>
                              {localeLabels[locale]}
                            </span>
                          ))}
                        </div>
                        <div className="f-s-13 text-muted mt-1">{translationsReady}/3 til</div>
                      </td>
                      <td>{policy.sortOrder}</td>
                      <td>
                        <span className={`badge ${policy.showInApp ? 'text-light-success' : 'text-light-secondary'}`}>
                          {policy.showInApp ? 'Ko‘rinadi' : 'Yashirin'}
                        </span>
                      </td>
                      <td>
                        <div className="form-check form-switch">
                          <input
                            type="checkbox"
                            className="form-check-input"
                            checked={policy.status === 'Active'}
                            onChange={() => toggle(policy)}
                          />
                        </div>
                      </td>
                      <td>
                        <div className="d-flex gap-1">
                          <button className="btn btn-light-success icon-btn w-30 h-30 b-r-22" onClick={() => openEdit(policy)}>
                            <i className="ti ti-pencil"></i>
                          </button>
                          {policy.publicUrl ? (
                            <a className="btn btn-light-secondary icon-btn w-30 h-30 b-r-22" href={policy.publicUrl} target="_blank" rel="noreferrer">
                              <i className="ti ti-external-link"></i>
                            </a>
                          ) : null}
                          <button className="btn btn-light-danger icon-btn w-30 h-30 b-r-22" onClick={() => destroy(policy)}>
                            <i className="ti ti-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {filteredPolicies.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="text-center py-5 text-secondary"><i className="iconoir-archive d-flex justify-content-center mb-2 f-s-30 text-primary"></i>Mos siyosat topilmadi.
                                        </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </div>
</div>

      <Modal show={showForm} onHide={closeModal} centered size="xl">
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <Modal.Title className="f-s-20 f-w-600">
              {editing ? 'Siyosatni tahrirlash' : 'Siyosat qo‘shish'}
            </Modal.Title>
          </Modal.Header>
          <Modal.Body className="overflow-y-auto" style={{ maxHeight: 'calc(100vh - 140px)' }}>
            <div className="row">
              {Object.keys(errors).length > 0 ? (
                <div className="col-12">
                  <div className="alert alert-light-danger mb-0">
                    Majburiy maydonlarni tekshiring va qayta saqlang.
                  </div>
                </div>
              ) : null}

              <div className="col-12">
                <div className="card"><div className="card-body">
                    <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                      <div>
                        <div className="f-w-600">Asosiy sozlamalar</div>
                        <div className="f-s-13 text-muted">Slug, tartib, faollik va appda ko‘rinishini boshqaring.</div>
                      </div>
                      <div className="d-flex flex-wrap gap-2">
                        <button type="button" className="btn btn-sm btn-dark" onClick={applySplitPreset}>
                          Nasiya shabloni
                        </button>
                        <button
                          type="button"
                          className="btn btn-sm btn-light-secondary"
                          onClick={() => setForm((prev) => ({ ...prev, slug: slugify(prev.slug || prev.title) }))}
                        >
                          Slug yaratish
                        </button>
                        <button type="button" className="btn btn-sm btn-light-secondary" onClick={resetForm}>
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
                          onChange={(event) => setForm((prev) => ({ ...prev, sortOrder: Number(event.target.value || 0) }))}
                          isInvalid={!!fieldError('sort_order')}
                        />
                        <Form.Control.Feedback type="invalid">{fieldError('sort_order')}</Form.Control.Feedback>
                      </div>
                      <div className="col-md-3 d-flex align-items-end">
                        <Form.Check
                          type="switch"
                          label="Faol"
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
                          <a href={editing.publicUrl} target="_blank" rel="noreferrer" className="btn btn-light-secondary w-100">
                            Ochiq sahifa
                          </a>
                        ) : (
                          <div className="f-s-13 text-muted">Saqlangandan keyin ochiq sahifa havolasi chiqadi.</div>
                        )}
                      </div>
                    </div>
                  </div></div>
              </div>

              <div className="col-12">
                <Form.Label>Matn (UZ, HTML bo‘lishi mumkin)</Form.Label>
                <Form.Control className="font-monospace"
         as="textarea"
         rows={18}
         required
         value={form.content}
         onChange={(event) => setForm((prev) => ({ ...prev, content: event.target.value }))}
         isInvalid={!!fieldError('content')}
        />
                <Form.Control.Feedback type="invalid">{fieldError('content')}</Form.Control.Feedback>
              </div>

              <div className="col-12">
                <div className="b-r-15 b-1-light overflow-hidden">
                  <div className="px-3 py-2 b-b-1-light bg-light-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                      <div className="f-w-600">Tarjimalar</div>
                      <div className="f-s-13 text-muted">Har bir til uchun sarlavha va matn alohida saqlanadi.</div>
                    </div>
                    <div className="nav nav-tabs app-tabs-primary flex-wrap">
                      {locales.map((locale) => (
                        <div key={locale} className="nav-item"><button
                            type="button"
                            className={`nav-link ${activeLocale === locale ? 'active' : ''}`}
                            onClick={() => setActiveLocale(locale)}>
                            {localeLabels[locale]}
                          </button></div>
                      ))}
                    </div>
                  </div>
                  <div className="p-3">
                    <div className="row g-3">
                      <div className="col-md-5">
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
                      <div className="col-md-7 d-flex align-items-end">
                        <div className="d-flex flex-wrap gap-1">
                          {locales.map((locale) => (
                            <span key={locale} className={`badge ${(form.translations[locale].title.trim() || form.translations[locale].content.trim()) ? 'text-light-success' : 'text-light-secondary'}`}>
                              {localeLabels[locale]}
                            </span>
                          ))}
                        </div>
                      </div>
                      <div className="col-12">
                        <Form.Label>{localeTitles[activeLocale]} matn</Form.Label>
                        <Form.Control
                          as="textarea"
                          rows={10}
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
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light-secondary" onClick={closeModal}>
              Bekor qilish
            </Button>
            <Button type="submit" className="btn-primary border-0">
              Saqlash
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
