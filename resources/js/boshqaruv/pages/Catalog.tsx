import { useState, type FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageCrumbs } from '../Layout';
import PaginationControls from '../components/PaginationControls';
import FormAction from '../components/FormAction';
import { EmptyState, StatWidget } from '../components/Axelit';
import { CoverInputs, EditionFields, editionStatus, type OptionItem } from '../components/CatalogFields';

const fmt = (n: number) => new Intl.NumberFormat('uz-UZ').format(n || 0);

interface EditionRow {
  id: number;
  title: string;
  author?: string | null;
  isbn?: string | null;
  publisher?: string | null;
  category?: string | null;
  cover?: string | null;
  status: string;
  verified: boolean;
  source?: string;
  offersCount: number;
  inStockOffers: number;
  minPrice?: number | null;
  deleted?: boolean;
  createdAt?: string;
  url: string;
}

type Props = {
  editions: EditionRow[];
  pagination: { page: number; totalPages: number; from: number; to: number; total: number };
  filters: { search?: string; tab?: string };
  counts: Record<string, number>;
  formOptions: { categories: OptionItem[]; publishers: OptionItem[]; sellers: OptionItem[] };
};

const SOURCE: Record<string, string> = { admin: 'Admin', seller: "Do'kon arizasi", backfill: 'Mavjud kitobdan', legacy: 'Eski ilova', parser: 'Import' };

export default function Catalog() {
  const { editions = [], pagination, filters = {}, counts = {}, formOptions } = usePage<Props>().props;
  const [search, setSearch] = useState(filters.search || '');
  const tab = filters.tab || 'all';

  const load = (params: Record<string, string | number>) => {
    router.get('/boshqaruv/catalog', { search, tab, ...params }, { preserveState: true, preserveScroll: true, replace: true });
  };

  const submitCreate = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    router.post('/boshqaruv/catalog', new FormData(event.currentTarget), { forceFormData: true, preserveScroll: true });
  };

  return (
    <div>
      <div className="d-flex align-items-end justify-content-between flex-wrap gap-3 mx-1 mb-3">
        <div>
          <h4 className="main-title mb-0">Global katalog</h4><PageCrumbs />
          <p className="mb-0 text-secondary">Har kitob bitta karta — do'konlar unga faqat narx va qoldiq bilan ulanadi</p>
        </div>
        <div className="d-flex gap-2 flex-wrap">
          <form className="d-flex gap-2" onSubmit={(event) => { event.preventDefault(); load({ page: 1 }); }}>
            <input className="form-control form-control-sm" style={{ minWidth: 260 }} value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ISBN, nom, muallif yoki ID" />
            <button className="btn btn-sm btn-outline-secondary" title="Qidirish"><i className="ti ti-search"></i></button>
          </form>
          <FormAction label="Kitob kartasi" icon="ti ti-plus" variant="primary" modalSize="lg" title="Katalogga yangi kitob" description="Karta darhol tasdiqlangan holda ochiladi. ISBN nazorat raqami tekshiriladi." submitLabel="Qo'shish" onSubmit={submitCreate}>
            <EditionFields options={formOptions} />
            <hr />
            <CoverInputs requireFront />
          </FormAction>
        </div>
      </div>

      <div className="row">
        {[
          { key: 'all', label: 'Kitob kartalari', value: counts.all || 0, sub: `${fmt(counts.unlinked || 0)} ta taklif hali ulanmagan` },
          { key: 'unverified', label: 'Tasdiqlanmagan', value: counts.unverified || 0, sub: "Mavjud kitoblardan avtomatik ochilgan" },
          { key: 'pending', label: "Do'kon arizalari", value: counts.submissions || 0, sub: 'Old/orqa muqova bilan tekshiruvda', href: '/boshqaruv/catalog/submissions' },
          { key: 'duplicates', label: 'Dublikat ISBN', value: undefined, sub: 'Bir ISBN — bir nechta karta' },
        ].map((item, index) => (
          <div className="col-xl-3 col-md-6" key={item.key}>
            <StatWidget
              index={index}
              label={item.label}
              value={item.value ?? '—'}
              sub={item.sub}
              selected={tab === item.key}
              href={item.href}
              onClick={item.href ? undefined : () => load({ tab: item.key, page: 1 })}
            />
          </div>
        ))}
      </div>

      <div className="card">
        <div className="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <div>
            <h5 className="f-w-600">Kartalar</h5>
            <p className="mb-0 text-secondary">{fmt(pagination.total)} ta natija</p>
          </div>
          <div className="nav kc-segment">
            {[
              ['all', 'Barchasi'],
              ['unverified', 'Tasdiqlanmagan'],
              ['pending', 'Tekshiruvda'],
              ['duplicates', 'Dublikatlar'],
              ['no_cover', 'Muqovasiz'],
              ['rejected', 'Rad etilgan'],
              ['deleted', "O'chirilgan"],
            ].map(([key, label]) => (
              <div className="nav-item" key={key}>
                <button type="button" className={`nav-link ${tab === key ? 'active' : ''}`} onClick={() => load({ tab: key, page: 1 })}>
                  {label}{counts[key] !== undefined ? <span className="badge text-light-secondary ms-2">{fmt(counts[key])}</span> : null}
                </button>
              </div>
            ))}
          </div>
        </div>
        <div className="card-body">
          <div className="table-responsive app-scroll">
            <table className="table table-bottom-border align-middle">
              <thead>
                <tr>
                  <th></th>
                  <th>Kitob</th>
                  <th>ISBN</th>
                  <th>Nashriyot</th>
                  <th>Takliflar</th>
                  <th>Eng arzon</th>
                  <th>Holat</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {editions.map((edition) => {
                  const [label, chip] = editionStatus(edition.status, edition.verified);
                  return (
                    <tr key={edition.id}>
                      <td>
                        <div className="w-40 h-55 b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0">
                          {edition.cover ? <img className="w-100 h-100 object-fit-cover" src={edition.cover} alt="" /> : <i className="ti ti-book"></i>}
                        </div>
                      </td>
                      <td>
                        <Link href={edition.url} className="f-w-600 d-block text-truncate text-dark" style={{ maxWidth: 280 }}>{edition.title}</Link>
                        <small className="d-block text-secondary">#{edition.id} · {edition.author || '—'} · {SOURCE[edition.source || ''] || edition.source}</small>
                      </td>
                      <td className="text-secondary">{edition.isbn || '—'}</td>
                      <td className="text-secondary">{edition.publisher || '—'}</td>
                      <td>
                        <span className="f-w-600">{edition.offersCount}</span>
                        <small className="d-block text-secondary">{edition.inStockOffers} tasi sotuvda</small>
                      </td>
                      <td>{edition.minPrice ? `${fmt(edition.minPrice)} so'm` : '—'}</td>
                      <td>
                        <span className={`badge ${chip}`}>{label}</span>
                        {edition.deleted ? <span className="badge text-light-danger ms-1">O'chirilgan</span> : null}
                      </td>
                      <td className="text-end">
                        <Link href={edition.url} className="btn btn-light-primary icon-btn w-30 h-30 b-r-22" title="Ochish"><i className="ti ti-eye"></i></Link>
                      </td>
                    </tr>
                  );
                })}
                {!editions.length ? (
                  <tr><td colSpan={8}><EmptyState text="Bu bo'limda karta topilmadi" /></td></tr>
                ) : null}
              </tbody>
            </table>
          </div>
          <PaginationControls {...pagination} onPageChange={(page) => load({ page })} />
        </div>
      </div>
    </div>
  );
}
