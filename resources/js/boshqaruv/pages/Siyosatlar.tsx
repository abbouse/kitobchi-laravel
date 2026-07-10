import { router, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Button, Form, Modal } from 'react-bootstrap';
import { splitPolicyPreset } from './policyPresets';

type TranslateLocale = 'ru' | 'en' | 'ja';
type StatusFilter = 'all' | 'active' | 'inactive';

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

export default function Siyosatlar() {
  const {
    policies = [],
    errors = {},
    flash = {},
  } = usePage<{
    policies?: Policy[];
    errors?: Record<string, string>;
    flash?: Record<string, string>;
  }>().props;

  const createUrl = policies[0]?.createUrl || '/boshqaruv/siyosatlar';
  const [editing, setEditing] = useState<Policy | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
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

    return { all: policies.length, active, inactive, inApp };
  }, [policies]);

  const filteredPolicies = useMemo(() => {
    const clean = query.trim().toLowerCase();

    return policies.filter((policy) => {
      if (statusFilter === 'active' && policy.status !== 'Active') return false;
      if (statusFilter === 'inactive' && policy.status === 'Active') return false;

      if (!clean) return true;

      const searchPool = [
        policy.title,
        policy.slug,
        policy.content || '',
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
    setForm(defaultForm());
  };

  const hydrateForm = (policy?: Policy | null) => {
    if (!policy) {
      resetForm();
      return;
    }

    setEditing(policy);
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

    const payload = new FormData();
    payload.append('title', form.title);
    payload.append('slug', form.slug);
    payload.append('content', form.content);
    payload.append('sort_order', String(form.sortOrder || 0));
    payload.append('is_active', form.isActive ? '1' : '0');
    payload.append('show_in_app', form.showInApp ? '1' : '0');

    (['ru', 'en', 'ja'] as TranslateLocale[]).forEach((locale) => {
      payload.append(`translations[${locale}][title]`, form.translations[locale].title || '');
      payload.append(`translations[${locale}][content]`, form.translations[locale].content || '');
    });

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
      <div className="page-head">
        <div>
          <h1 className="page-title">Siyosatlar va qoidalar</h1>
          <p className="page-subtitle">Legal sahifalarni qo'lda, aniq va ko'p tilli tarzda boshqaring</p>
        </div>
        <button className="btn btn-primary-gradient" onClick={openCreate}>
          <i className="bi bi-plus-lg me-1"></i>Siyosat qo'shish
        </button>
      </div>

      {flash.success ? (
        <div className="alert alert-success rounded-4 mb-3">{flash.success}</div>
      ) : null}
      {flash.error ? (
        <div className="alert alert-danger rounded-4 mb-3">{flash.error}</div>
      ) : null}

      <div className="row g-3 mb-3">
        <div className="col-md-3">
          <div className="card-panel h-100">
            <div className="small text-muted">Jami siyosat</div>
            <div className="fs-4 fw-bold mt-2">{counts.all}</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card-panel h-100">
            <div className="small text-muted">Faol</div>
            <div className="fs-4 fw-bold mt-2 text-success">{counts.active}</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card-panel h-100">
            <div className="small text-muted">Yashirin</div>
            <div className="fs-4 fw-bold mt-2 text-secondary">{counts.inactive}</div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card-panel h-100">
            <div className="small text-muted">Appda ko'rinadi</div>
            <div className="fs-4 fw-bold mt-2 text-primary">{counts.inApp}</div>
          </div>
        </div>
      </div>

      <div className="card-panel mb-3">
        <div className="row g-3 align-items-end">
          <div className="col-lg-7">
            <Form.Label>Qidiruv</Form.Label>
            <Form.Control
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Sarlavha, slug yoki matn bo'yicha qidiring"
            />
          </div>
          <div className="col-lg-3">
            <Form.Label>Holat</Form.Label>
            <Form.Select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}>
              <option value="all">Barchasi</option>
              <option value="active">Faqat faol</option>
              <option value="inactive">Faqat yashirin</option>
            </Form.Select>
          </div>
          <div className="col-lg-2">
            <div className="small text-muted">Natija</div>
            <div className="fs-5 fw-bold">{filteredPolicies.length}</div>
          </div>
        </div>
      </div>

      <div className="card-panel">
        <div className="table-responsive">
          <table className="data-table">
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
                const translationsReady = (['ru', 'en', 'ja'] as TranslateLocale[]).filter(
                  (locale) => (policy.translations?.[locale]?.title || '').trim() || (policy.translations?.[locale]?.content || '').trim(),
                ).length;

                return (
                  <tr key={policy.id}>
                    <td className="fw-semibold text-primary">#{policy.id}</td>
                    <td>
                      <div className="fw-semibold">{policy.title}</div>
                      {policy.updatedAtLabel ? (
                        <div className="small text-muted mb-1">Yangilangan: {policy.updatedAtLabel}</div>
                      ) : null}
                      <div className="small text-muted">{policy.content ? `${policy.content.replace(/<[^>]+>/g, '').slice(0, 92)}...` : 'Matn yo‘q'}</div>
                    </td>
                    <td>
                      <div><code>{policy.slug}</code></div>
                      {policy.publicUrl ? (
                        <a href={policy.publicUrl} target="_blank" rel="noreferrer" className="small">
                          Ochiq sahifa
                        </a>
                      ) : null}
                    </td>
                    <td>
                      <div className="d-flex flex-wrap gap-1">
                        {(['ru', 'en', 'ja'] as TranslateLocale[]).map((locale) => {
                          const ready = (policy.translations?.[locale]?.title || '').trim() || (policy.translations?.[locale]?.content || '').trim();
                          return (
                            <span key={locale} className={`chip ${ready ? 'chip-success' : 'chip-gray'}`}>
                              {localeLabels[locale]}
                            </span>
                          );
                        })}
                      </div>
                      <div className="small text-muted mt-1">{translationsReady}/3 til to'ldirilgan</div>
                    </td>
                    <td>{policy.sortOrder}</td>
                    <td>
                      <span className={`chip ${policy.showInApp ? 'chip-success' : 'chip-gray'}`}>
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
                        <button className="btn btn-sm btn-light" onClick={() => openEdit(policy)}>
                          <i className="bi bi-pencil"></i>
                        </button>
                        {policy.publicUrl ? (
                          <a className="btn btn-sm btn-light" href={policy.publicUrl} target="_blank" rel="noreferrer">
                            <i className="bi bi-box-arrow-up-right"></i>
                          </a>
                        ) : null}
                        <button className="btn btn-sm btn-light text-danger" onClick={() => destroy(policy)}>
                          <i className="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
              {filteredPolicies.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center text-muted py-5">
                    Mos siyosat topilmadi.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </div>

      <Modal show={showForm} onHide={closeModal} centered size="xl">
        <Form onSubmit={submit}>
          <Modal.Header closeButton>
            <Modal.Title className="fs-5 fw-bold">
              {editing ? 'Siyosatni tahrirlash' : "Siyosat qo'shish"}
            </Modal.Title>
          </Modal.Header>
          <Modal.Body style={{ maxHeight: 'calc(100vh - 140px)', overflowY: 'auto' }}>
            <div className="row g-3">
              {Object.keys(errors).length > 0 ? (
                <div className="col-12">
                  <div className="alert alert-danger rounded-4 mb-0">
                    Majburiy maydonlarni tekshiring va qayta saqlang.
                  </div>
                </div>
              ) : null}
              <div className="col-12">
                <div className="rounded-4 border p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                  <div>
                    <div className="fw-semibold">Tezkor yordamchi</div>
                    <div className="small text-muted">Asosiy UZ matnni tayyorlang, keyin boshqa tillarni admin qo'lda alohida to'ldiradi. Slug va tartib shu yerdan boshqariladi.</div>
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
                    <button type="button" className="btn btn-sm btn-light" onClick={resetForm}>
                      Tozalash
                    </button>
                  </div>
                </div>
              </div>

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
                  label="Faol"
                  checked={form.isActive}
                  onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
                />
              </div>
              <div className="col-md-3 d-flex align-items-end">
                <Form.Check
                  type="switch"
                  label="Appda ko'rinsin"
                  checked={form.showInApp}
                  onChange={(event) => setForm((prev) => ({ ...prev, showInApp: event.target.checked }))}
                />
              </div>
              <div className="col-md-3 d-flex align-items-end">
                {editing?.publicUrl ? (
                  <a href={editing.publicUrl} target="_blank" rel="noreferrer" className="btn btn-light w-100">
                    <i className="bi bi-box-arrow-up-right me-1"></i>Ochiq sahifani ko‘rish
                  </a>
                ) : (
                  <div className="small text-muted">Saqlangach ochiq preview havolasi paydo bo'ladi.</div>
                )}
              </div>

              <div className="col-xl-7">
                <Form.Label>Matn (UZ, HTML bo'lishi mumkin)</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={22}
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
                  <div className="px-3 py-2 border-bottom bg-light fw-semibold">UZ preview</div>
                  <div
                    className="p-3"
                    style={{ minHeight: 420, maxHeight: 620, overflowY: 'auto', background: '#fff' }}
                  >
                    {form.content.trim() ? (
                      <div dangerouslySetInnerHTML={{ __html: form.content }} />
                    ) : (
                      <div className="text-muted small">Bu yerda HTML preview ko'rinadi.</div>
                    )}
                  </div>
                </div>
              </div>

              {(['ru', 'en', 'ja'] as TranslateLocale[]).map((locale) => (
                <div className="col-12" key={locale}>
                  <div className="rounded-4 border bg-light-subtle p-3">
                    <div className="d-flex justify-content-between align-items-center gap-2 mb-3">
                      <div>
                        <div className="fw-semibold">{localeTitles[locale]} versiya</div>
                        <div className="small text-muted">Bu til uchun sarlavha va legal matn qo'lda kiritiladi.</div>
                      </div>
                      <span className={`chip ${(form.translations[locale].title.trim() || form.translations[locale].content.trim()) ? 'chip-success' : 'chip-gray'}`}>{localeLabels[locale]}</span>
                    </div>
                    <div className="row g-3">
                      <div className="col-12">
                        <Form.Label>{localeTitles[locale]} sarlavha</Form.Label>
                        <Form.Control
                          value={form.translations[locale].title}
                          onChange={(event) =>
                            setForm((prev) => ({
                              ...prev,
                              translations: {
                                ...prev.translations,
                                [locale]: {
                                  ...prev.translations[locale],
                                  title: event.target.value,
                                },
                              },
                            }))
                          }
                          isInvalid={!!fieldError(`translations.${locale}.title`)}
                        />
                        <Form.Control.Feedback type="invalid">{fieldError(`translations.${locale}.title`)}</Form.Control.Feedback>
                      </div>
                      <div className="col-12">
                        <Form.Label>{localeTitles[locale]} matn</Form.Label>
                        <Form.Control
                          as="textarea"
                          rows={10}
                          value={form.translations[locale].content}
                          onChange={(event) =>
                            setForm((prev) => ({
                              ...prev,
                              translations: {
                                ...prev.translations,
                                [locale]: {
                                  ...prev.translations[locale],
                                  content: event.target.value,
                                },
                              },
                            }))
                          }
                          isInvalid={!!fieldError(`translations.${locale}.content`)}
                        />
                        <Form.Control.Feedback type="invalid">{fieldError(`translations.${locale}.content`)}</Form.Control.Feedback>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="light" onClick={closeModal}>
              Bekor qilish
            </Button>
            <Button type="submit" className="btn-primary-gradient border-0">
              Saqlash
            </Button>
          </Modal.Footer>
        </Form>
      </Modal>
    </div>
  );
}
