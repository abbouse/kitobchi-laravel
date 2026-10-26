import { useState } from 'react';
import type { FormEvent } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';

/**
 * Kitobxon darajalari — xarid bo'yicha (oxirgi 12 oy). Kelgusidagi chegirma va
 * imtiyozlar shu darajalarga bog'lanadi. Nishonlar — klub faolligi uchun, imtiyozsiz.
 */

interface Level {
  id: number; key: string; sort: number;
  name_uz: string; name_ru: string | null; name_en: string | null; name_ja: string | null;
  min_orders: number; min_spent: number; color: string;
  perks_uz: string | null; perks_ru: string | null; perks_en: string | null; perks_ja: string | null;
  users: number;
}
interface Badge { key: string; metric: string; target: number; users: number }
interface PageProps { levels: Level[]; badges: Badge[]; [k: string]: unknown }

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n);

const BADGES: Record<string, { name: string; emoji: string }> = {
  first_order: { name: 'Ilk sahifa', emoji: '🔖' },
  bookworm: { name: 'Kitob qurti', emoji: '🐛' },
  collector: { name: 'Kolleksioner', emoji: '📚' },
  genre_explorer: { name: 'Janrlar sayyohi', emoji: '🧭' },
  shop_explorer: { name: "Do'konma-do'kon", emoji: '🛍️' },
  reviewer: { name: 'Tanqidchi', emoji: '🖋️' },
  club_voice: { name: "Klub notig'i", emoji: '📣' },
  conversationalist: { name: 'Suhbat ustasi', emoji: '💬' },
  club_star: { name: 'Klub yulduzi', emoji: '🌟' },
  inspirer: { name: 'Ilhom manbai', emoji: '💡' },
  lucky: { name: 'Omad qushi', emoji: '🍀' },
  veteran: { name: "Bir yillik do'st", emoji: '⏳' },
};
const METRIC: Record<string, string> = {
  orders: 'yetkazilgan buyurtma', books: 'turli kitob', categories: 'turli kategoriya', sellers: "turli do'kondan xarid",
  reviews: 'kitob sharhi', posts: 'post', comments: 'izoh', likes: 'boshqalardan layk', followers: 'obunachi',
  game_books: "o'yinda yutilgan kitob", account_days: 'kun Kitobchida',
};

export default function Darajalar() {
  const { levels = [], badges = [] } = usePage<PageProps>().props;
  return (
    <div>
      <div className="mx-1 mb-3">
        <h4 className="main-title mb-0">Darajalar va nishonlar</h4>
        <PageCrumbs />
        <p className="mb-0 text-secondary">Daraja — oxirgi 12 oydagi yetkazilgan va to'langan buyurtmalar soni hamda mahsulotlar summasi (yetkazishsiz) bo'yicha. Ikkala shart ham bajarilishi kerak. Daraja birdan tushmaydi: 90 kunda bir pog'ona.</p>
      </div>
      <div className="row g-3">
        <div className="col-xl-8">
          {levels.map((l, i) => <LevelCard key={l.id} level={l} first={i === 0} />)}
        </div>
        <div className="col-xl-4">
          <div className="card">
            <div className="card-header"><h5 className="mb-0">Nishonlar</h5><p className="mb-0 f-s-12 text-secondary">Faollik uchun avtomatik beriladi, qaytarib olinmaydi. Imtiyoz bermaydi.</p></div>
            <div className="card-body">
              {badges.map((b) => (
                <div key={b.key} className="d-flex align-items-center gap-3 py-2 border-bottom">
                  <span className="h-35 w-35 d-flex-center b-r-10 bg-light-primary f-s-18 flex-shrink-0">{BADGES[b.key]?.emoji ?? '🏷️'}</span>
                  <div className="flex-grow-1 min-w-0">
                    <div className="f-w-600 txt-ellipsis-1">{BADGES[b.key]?.name ?? b.key}</div>
                    <div className="f-s-12 text-secondary">{fmt(b.target)} {METRIC[b.metric] ?? b.metric}</div>
                  </div>
                  <span className="badge text-light-secondary" title="Olganlar soni">{fmt(b.users)}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function LevelCard({ level, first }: { level: Level; first: boolean }) {
  const [busy, setBusy] = useState(false);
  const [lang, setLang] = useState<'uz' | 'ru' | 'en' | 'ja'>('uz');
  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(e.currentTarget)) as Record<string, string>;
    setBusy(true);
    router.put(`/boshqaruv/darajalar/${level.id}`, f, { preserveScroll: true, onFinish: () => setBusy(false) });
  };
  return (
    <div className="card mb-3">
      <form className="app-form" onSubmit={submit}>
        <div className="card-header d-flex align-items-center gap-3">
          <span className="d-inline-block b-r-50 flex-shrink-0" style={{ width: 14, height: 14, background: level.color }} />
          <div className="flex-grow-1">
            <h5 className="mb-0">{level.sort}. {level.name_uz} <span className="f-s-12 text-muted font-monospace">{level.key}</span></h5>
            <div className="f-s-12 text-secondary">{fmt(level.users)} foydalanuvchi</div>
          </div>
          <input type="color" name="color" defaultValue={level.color} className="form-control form-control-color" style={{ width: 44 }} title="Rangi" />
        </div>
        <div className="card-body">
          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label f-s-13">Buyurtmalar (12 oy)</label>
              <div className="input-group"><input name="min_orders" type="number" min={0} defaultValue={level.min_orders} disabled={first} className="form-control" /><span className="input-group-text">ta</span></div>
            </div>
            <div className="col-md-6">
              <label className="form-label f-s-13">Mahsulotlar summasi (12 oy)</label>
              <div className="input-group"><input name="min_spent" type="number" min={0} step={1000} defaultValue={level.min_spent} disabled={first} className="form-control" /><span className="input-group-text">so'm</span></div>
            </div>
            {first ? <input type="hidden" name="min_orders" value="0" /> : null}
            {first ? <input type="hidden" name="min_spent" value="0" /> : null}
            <div className="col-12">
              <div className="btn-group btn-group-sm mb-2">
                {(['uz', 'ru', 'en', 'ja'] as const).map((l) => (
                  <button type="button" key={l} className={`btn ${lang === l ? 'btn-primary' : 'btn-light-secondary'}`} onClick={() => setLang(l)}>{l.toUpperCase()}</button>
                ))}
              </div>
              {(['uz', 'ru', 'en', 'ja'] as const).map((l) => (
                <div key={l} className={lang === l ? 'row g-2' : 'd-none'}>
                  <div className="col-md-5">
                    <label className="form-label f-s-13">Nomi ({l})</label>
                    <input name={`name_${l}`} defaultValue={level[`name_${l}`] ?? ''} required={l === 'uz'} className="form-control" />
                  </div>
                  <div className="col-md-7">
                    <label className="form-label f-s-13">Imtiyozlar ({l}) — har qatorda bittasi</label>
                    <textarea name={`perks_${l}`} defaultValue={level[`perks_${l}`] ?? ''} rows={3} className="form-control" placeholder={l === 'uz' ? "Masalan: 3% keshbek" : ''} />
                  </div>
                </div>
              ))}
            </div>
          </div>
          <div className="d-flex align-items-center justify-content-between mt-3">
            <span className="f-s-12 text-muted">{first ? "Boshlang'ich daraja — hamma uchun." : 'Ikkala shart ham bajarilganda beriladi.'} Imtiyozlar hozircha ilovada matn sifatida ko'rsatiladi.</span>
            <button className="btn btn-primary" type="submit" disabled={busy}><i className="ti ti-check me-1"></i>Saqlash</button>
          </div>
        </div>
      </form>
    </div>
  );
}
