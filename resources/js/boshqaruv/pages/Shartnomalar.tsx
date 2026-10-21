import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';

/**
 * Shartnomalar — saytdagi "Huquqiy hujjatlar" bo'limidan yuklab olinadigan shartnoma namunalari.
 * Kompaniya rekvizitlari va shartlar shu yerda kiritiladi; sana va to'lov jadvali yuklab olingan
 * kundan avtomatik tuziladi. Aniq kontragent uchun hujjatni ham shu yerda yaratish mumkin.
 */

interface Field { key: string; label: string; default: string; hint: string | null; value: string }
interface PartyField { key: string; label: string }
interface Template {
  slug: string;
  title: string;
  description: string;
  is_public: boolean;
  public_url: string;
  terms: Field[];
  party: PartyField[];
}
interface PageProps { company: Field[]; templates: Template[] }

const BASE = '/boshqaruv/shartnomalar';

const isBlank = (s: string) => /^[_\s.№/«»"]*$/.test(s) || /^_+/.test(s);

function FieldInput({ f }: { f: Field }) {
  return (
    <div className="col-md-6">
      <label className="form-label f-s-13 mb-1" htmlFor={`f-${f.key}`}>{f.label}</label>
      <input
        id={`f-${f.key}`}
        name={f.key}
        className="form-control"
        defaultValue={f.value}
        placeholder={isBlank(f.default) ? (f.hint ? `Masalan: ${f.hint.replace(/^Masalan:\s*/, '')}` : "Bo'sh — hujjatda chiziq qoladi") : f.default}
        maxLength={500}
      />
    </div>
  );
}

function CompanyCard({ company }: { company: Field[] }) {
  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    router.post(`${BASE}/company`, Object.fromEntries(new FormData(e.currentTarget)), { preserveScroll: true });
  };
  return (
    <div className="card mb-3">
      <div className="card-header">
        <h5 className="mb-0">Kitobchi rekvizitlari</h5>
        <p className="mb-0 f-s-12 text-secondary">Barcha shartnomalarda bir xil. Bo'sh qoldirilsa standart qiymat ishlatiladi (maydonda kulrang ko'rinadi).</p>
      </div>
      <div className="card-body">
        <form className="app-form" onSubmit={submit}>
          <div className="row g-3">{company.map((f) => <FieldInput key={f.key} f={f} />)}</div>
          <button className="btn btn-primary mt-3" type="submit"><i className="ti ti-check me-1"></i>Saqlash</button>
        </form>
      </div>
    </div>
  );
}

function TemplateCard({ t }: { t: Template }) {
  const [isPublic, setIsPublic] = useState(t.is_public);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => setIsPublic(t.is_public), [t.is_public]);

  const save = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const data: Record<string, string> = Object.fromEntries(new FormData(e.currentTarget)) as Record<string, string>;
    data.is_public = isPublic ? '1' : '0';
    router.post(`${BASE}/${t.slug}`, data, { preserveScroll: true });
  };

  const generate = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setBusy(true);
    setError(null);
    const fd = new FormData(e.currentTarget);
    const party: Record<string, string> = {};
    t.party.forEach((p) => { party[p.key] = String(fd.get(`party_${p.key}`) || ''); });
    try {
      const res = await fetch(`${BASE}/${t.slug}/generate`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ date: fd.get('date') || null, party }),
      });
      if (!res.ok) throw new Error(`Xatolik: ${res.status}`);
      const blob = await res.blob();
      const name = /filename="([^"]+)"/.exec(res.headers.get('Content-Disposition') || '')?.[1] || `${t.slug}.docx`;
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      a.click();
      setTimeout(() => URL.revokeObjectURL(a.href), 2000);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Hujjat yaratilmadi');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="card mb-3">
      <div className="card-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
          <h5 className="mb-0">{t.title}</h5>
          <p className="mb-0 f-s-12 text-secondary">{t.description}</p>
        </div>
        <a className="btn btn-light-primary btn-sm" href={t.public_url}>
          <i className="ti ti-download me-1"></i>Saytdagi namunani yuklab olish
        </a>
      </div>
      <div className="card-body">
        <form className="app-form" onSubmit={save}>
          <div className="form-check form-switch d-flex align-items-center gap-2 mb-3 ps-0">
            <input className="form-check-input ms-0" type="checkbox" role="switch" id={`pub-${t.slug}`} checked={isPublic} onChange={(e) => setIsPublic(e.target.checked)} />
            <label className="form-check-label f-w-600" htmlFor={`pub-${t.slug}`}>Saytdagi "Huquqiy hujjatlar" bo'limida ko'rinsin</label>
          </div>
          <h6 className="f-s-14 mb-2">Shartnoma shartlari</h6>
          <div className="row g-3">{t.terms.map((f) => <FieldInput key={f.key} f={f} />)}</div>
          <button className="btn btn-primary mt-3" type="submit"><i className="ti ti-check me-1"></i>Saqlash</button>
        </form>

        <hr className="my-4" />
        <form className="app-form" onSubmit={generate}>
          <h6 className="f-s-14 mb-1">Aniq kontragent uchun hujjat yaratish</h6>
          <p className="f-s-12 text-secondary mb-3">Bu ma'lumotlar saqlanmaydi — faqat yuklab olinadigan faylga yoziladi. Bo'sh maydonlar chiziq bo'lib qoladi.</p>
          <div className="row g-3">
            <div className="col-md-4">
              <label className="form-label f-s-13 mb-1" htmlFor={`date-${t.slug}`}>Shartnoma sanasi</label>
              <input id={`date-${t.slug}`} name="date" type="date" className="form-control" />
            </div>
            {t.party.map((p) => (
              <div className="col-md-4" key={p.key}>
                <label className="form-label f-s-13 mb-1" htmlFor={`p-${t.slug}-${p.key}`}>{p.label}</label>
                <input id={`p-${t.slug}-${p.key}`} name={`party_${p.key}`} className="form-control" maxLength={300} />
              </div>
            ))}
          </div>
          {error ? <div className="alert alert-light-danger f-s-13 py-2 mt-3 mb-0">{error}</div> : null}
          <button className="btn btn-success mt-3" type="submit" disabled={busy}>
            <i className={`ti ${busy ? 'ti-loader-2' : 'ti-file-download'} me-1`}></i>{busy ? 'Tayyorlanmoqda…' : 'Word faylini yaratish'}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function Shartnomalar() {
  const { company = [], templates = [] } = usePage<PageProps>().props;
  return (
    <div>
      <div className="mx-1 mb-3">
        <h4 className="main-title mb-0">Shartnomalar</h4>
        <PageCrumbs />
        <p className="mb-0 text-secondary">Saytdan yuklab olinadigan shartnoma namunalari: rekvizitlar, shartlar va hujjat yaratish. Sana va to'lov jadvali yuklab olingan kundan avtomatik tuziladi.</p>
      </div>
      <div className="row g-3">
        <div className="col-xl-5"><CompanyCard company={company} /></div>
        <div className="col-xl-7">{templates.map((t) => <TemplateCard key={t.slug} t={t} />)}</div>
      </div>
    </div>
  );
}
