import { useEffect, useMemo, useState, type ChangeEvent } from 'react';

export interface OptionItem { id: number; name: string }

export interface EditionValues {
  title?: string | null;
  author?: string | null;
  isbn?: string | null;
  translator?: string | null;
  categoryId?: number | null;
  publisherId?: number | null;
  lang?: string | null;
  langType?: string | null;
  coverType?: string | null;
  year?: number | null;
  pages?: number | null;
  description?: string | null;
}

export const LANGS = ["O'zbek", 'Rus', 'Ingliz', "Qoraqalpoq"];
export const LANG_TYPES = ['Lotin', 'Kirill'];
export const COVER_TYPES = ['Yumshoq', 'Qattiq'];

export const editionStatus = (status?: string, verified?: boolean): [string, string] => {
  if (status === 'pending') return ['Tekshiruvda', 'text-light-warning'];
  if (status === 'rejected') return ['Rad etilgan', 'text-light-danger'];
  if (status === 'merged') return ['Birlashtirilgan', 'text-light-secondary'];
  return verified ? ['Tasdiqlangan', 'text-light-success'] : ['Tasdiqlanmagan', 'text-light-info'];
};

export const isbnCheckChip = (check?: string | null): [string, string, string] => {
  switch (check) {
    case 'matched': return ['ISBN mos', 'text-light-success', 'ti ti-circle-check'];
    case 'mismatch': return ['ISBN mos emas', 'text-light-danger', 'ti ti-alert-triangle'];
    case 'no_isbn': return ["ISBN yo'q", 'text-light-secondary', 'ti ti-minus'];
    default: return ["O'qilmadi", 'text-light-warning', 'ti ti-help'];
  }
};

/** Katalog kartasi maydonlari (nomlar backend validateEdition() bilan bir xil). */
export function EditionFields({ values = {}, options }: { values?: EditionValues; options: { categories: OptionItem[]; publishers: OptionItem[] } }) {
  const select = (name: string, list: string[], value?: string | null) => (
    <select name={name} className="form-select" defaultValue={value || list[0]}>
      {value && !list.includes(value) ? <option value={value}>{value}</option> : null}
      {list.map((item) => <option key={item} value={item}>{item}</option>)}
    </select>
  );

  return (
    <div className="row g-3">
      <div className="col-md-6"><label className="form-label">Kitob nomi</label><input name="title" className="form-control" defaultValue={values.title || ''} required /></div>
      <div className="col-md-6"><label className="form-label">Muallif</label><input name="author" className="form-control" defaultValue={values.author || ''} required /></div>
      <div className="col-md-4"><label className="form-label">ISBN</label><input name="isbn" className="form-control" defaultValue={values.isbn || ''} placeholder="978…" inputMode="numeric" /></div>
      <div className="col-md-4"><label className="form-label">Tarjimon</label><input name="translator" className="form-control" defaultValue={values.translator || ''} /></div>
      <div className="col-md-4"><label className="form-label">Yil</label><input name="year" type="number" min={1800} className="form-control" defaultValue={values.year || ''} /></div>
      <div className="col-md-6">
        <label className="form-label">Kategoriya</label>
        <select name="category_id" className="form-select" defaultValue={values.categoryId || ''} required>
          <option value="" disabled>Tanlang</option>
          {options.categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </div>
      <div className="col-md-6">
        <label className="form-label">Nashriyot</label>
        <select name="publisher_id" className="form-select" defaultValue={values.publisherId || ''}>
          <option value="">Tanlanmagan</option>
          {options.publishers.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
        </select>
      </div>
      <div className="col-md-3"><label className="form-label">Til</label>{select('lang', LANGS, values.lang)}</div>
      <div className="col-md-3"><label className="form-label">Yozuv</label>{select('langType', LANG_TYPES, values.langType)}</div>
      <div className="col-md-3"><label className="form-label">Muqova</label>{select('coverType', COVER_TYPES, values.coverType)}</div>
      <div className="col-md-3"><label className="form-label">Sahifa</label><input name="pages" type="number" min={0} className="form-control" defaultValue={values.pages || ''} /></div>
      <div className="col-12"><label className="form-label">Tavsif</label><textarea name="description" className="form-control" rows={4} defaultValue={values.description || ''} /></div>
    </div>
  );
}

function FilePreview({ name, label, current, required }: { name: string; label: string; current?: string | null; required?: boolean }) {
  const [file, setFile] = useState<File | null>(null);
  const preview = useMemo(() => (file ? URL.createObjectURL(file) : null), [file]);
  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);
  const src = preview || current || null;

  return (
    <div className="col-sm-6">
      <label className="form-label">{label}</label>
      <div className="d-flex gap-3 align-items-center">
        <div className="w-60 h-80 b-r-10 overflow-hidden d-flex-center bg-light-secondary flex-shrink-0">
          {src ? <img className="w-100 h-100 object-fit-cover" src={src} alt="" /> : <i className="ti ti-photo f-s-20"></i>}
        </div>
        <input className="form-control form-control-sm" type="file" name={name} accept="image/*" required={required && !current} onChange={(event: ChangeEvent<HTMLInputElement>) => setFile(event.target.files?.[0] || null)} />
      </div>
    </div>
  );
}

/** Old va orqa muqova + qo'shimcha rasmlar (yangi karta uchun). */
export function CoverInputs({ requireFront = true, frontUrl, backUrl }: { requireFront?: boolean; frontUrl?: string | null; backUrl?: string | null }) {
  return (
    <div className="row g-3">
      <FilePreview name="front_image" label="Old muqova" current={frontUrl} required={requireFront} />
      <FilePreview name="back_image" label="Orqa muqova (ISBN shtrix-kodi bilan)" current={backUrl} />
      <div className="col-12">
        <label className="form-label">Qo'shimcha rasmlar</label>
        <input className="form-control form-control-sm" type="file" name="images[]" multiple accept="image/*" />
      </div>
    </div>
  );
}

/** Mavjud rasmlar: olib tashlash va tartib (images_keep JSON — xom yo'llar). */
export function KeepImages({ items }: { items: Array<{ path: string; url: string | null }> }) {
  const [kept, setKept] = useState(items.map((item) => item.path));
  useEffect(() => setKept(items.map((item) => item.path)), [items]);
  const byPath = Object.fromEntries(items.map((item) => [item.path, item.url]));

  return (
    <div>
      <input type="hidden" name="images_keep" value={JSON.stringify(kept)} readOnly />
      {kept.length ? (
        <div className="d-flex flex-wrap gap-2">
          {kept.map((path, index) => (
            <div key={path} className="position-relative b-r-10 overflow-hidden bg-light-secondary w-80 h-100">
              {byPath[path] ? <img className="w-100 h-100 object-fit-cover" src={byPath[path] as string} alt="" /> : null}
              {index === 0 ? <span className="badge text-light-success position-absolute top-0 start-0 m-1 f-s-10">Muqova</span> : (
                <button type="button" title="Muqova qilish" className="btn btn-light-secondary icon-btn w-25 h-25 b-r-22 position-absolute top-0 start-0 m-1" onClick={() => setKept((list) => [path, ...list.filter((item) => item !== path)])}>
                  <i className="ti ti-star f-s-12"></i>
                </button>
              )}
              <button type="button" title="Olib tashlash" className="btn btn-light-danger icon-btn w-25 h-25 b-r-22 position-absolute top-0 end-0 m-1" onClick={() => setKept((list) => list.filter((item) => item !== path))}>
                <i className="ti ti-x f-s-12"></i>
              </button>
            </div>
          ))}
        </div>
      ) : <div className="text-secondary f-s-13">Rasm qolmadi — yangi muqova yuklang.</div>}
    </div>
  );
}

/** Katalogdan kitob tanlash (ISBN yoki nom bo'yicha qidiruv). */
export function EditionPicker({ searchUrl, onPick, value }: { searchUrl: string; onPick: (edition: PickedEdition | null) => void; value: PickedEdition | null }) {
  const [term, setTerm] = useState('');
  const [items, setItems] = useState<PickedEdition[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (term.trim().length < 2) { setItems([]); return undefined; }
    const timer = window.setTimeout(() => {
      setLoading(true);
      fetch(`${searchUrl}?q=${encodeURIComponent(term.trim())}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((res) => res.json())
        .then((json) => setItems(json.data || []))
        .catch(() => setItems([]))
        .finally(() => setLoading(false));
    }, 300);
    return () => window.clearTimeout(timer);
  }, [term, searchUrl]);

  if (value) {
    return (
      <div className="d-flex align-items-center gap-3 p-3 b-r-10 bg-light-primary">
        <div className="w-40 h-55 b-r-8 overflow-hidden d-flex-center bg-white flex-shrink-0">
          {value.cover ? <img className="w-100 h-100 object-fit-cover" src={value.cover} alt="" /> : <i className="ti ti-book"></i>}
        </div>
        <div className="flex-grow-1 min-w-0">
          <h6 className="mb-0 f-w-600 text-truncate">{value.title}</h6>
          <small className="text-secondary">{value.author || '—'} · {value.isbn || "ISBN yo'q"} · {value.offersCount} ta taklif</small>
          {value.variant ? <small className="d-block text-primary f-w-500">{value.variant}</small> : null}
        </div>
        <input type="hidden" name="edition_id" value={value.id} />
        <button type="button" className="btn btn-light-secondary btn-sm" onClick={() => onPick(null)}>Almashtirish</button>
      </div>
    );
  }

  return (
    <div>
      <div className="input-group">
        <span className="input-group-text"><i className="ti ti-search"></i></span>
        <input className="form-control" value={term} onChange={(event) => setTerm(event.target.value)} placeholder="ISBN, kitob nomi yoki muallif" />
      </div>
      {loading ? <div className="text-secondary f-s-13 mt-2">Qidirilmoqda…</div> : null}
      {items.length ? (
        <div className="list-group mt-2">
          {items.map((item) => (
            <button type="button" key={item.id} className="list-group-item list-group-item-action d-flex align-items-center gap-3" onClick={() => onPick(item)}>
              <div className="w-30 h-40 b-r-5 overflow-hidden d-flex-center bg-light-secondary flex-shrink-0">
                {item.cover ? <img className="w-100 h-100 object-fit-cover" src={item.cover} alt="" /> : <i className="ti ti-book f-s-12"></i>}
              </div>
              <div className="min-w-0 text-start">
                <div className="f-w-600 text-truncate">{item.title}</div>
                <small className="text-secondary">{item.author || '—'} · {item.isbn || "ISBN yo'q"} · {item.offersCount} ta taklif</small>
                {item.variant ? <small className="d-block text-primary f-w-500">{item.variant}</small> : null}
              </div>
            </button>
          ))}
        </div>
      ) : term.trim().length >= 2 && !loading ? <div className="text-secondary f-s-13 mt-2">Katalogda topilmadi — pastda yangi kitob ma'lumotlarini kiriting.</div> : null}
    </div>
  );
}

export interface PickedEdition {
  id: number;
  title: string;
  author?: string | null;
  isbn?: string | null;
  cover?: string | null;
  offersCount: number;
  minPrice?: number | null;
  status?: string;
  verified?: boolean;
  url?: string;
  /** "Qattiq muqova · O'zbek · Lotin" — bir xil ISBN'li kartalarni farqlash uchun */
  variant?: string;
  variantDiff?: string[];
}
