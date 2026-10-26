import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import InfoHint from './InfoHint';

/**
 * KPI kartasi (yangi dizayn): oq yuza, 1px chegara, yuqorida nom (rangli nuqta
 * bilan), katta qiymat, ostida izoh/trend. `variant` — nuqta rangi; berilmasa
 * nomdan ma'no bo'yicha aniqlanadi (pastdagi SEMANTIC).
 */
export type StatVariant = 'provided' | 'primary' | 'danger' | 'store' | 'info' | 'warning' | 'success';
const RHYTHM: StatVariant[] = ['provided', 'primary', 'danger', 'store'];
const DOT: Record<StatVariant, string> = {
  provided: 'secondary', primary: 'primary', danger: 'danger', store: 'success', info: 'info', warning: 'warning', success: 'success',
};

// Nuqta rangi ma'no bildiradi: qizil — muammo, sariq — kutilmoqda/jarayonda,
// yashil — yakunlangan/ijobiy, kulrang — jami, ko'k — qolgan hammasi.
// `variant` berilmagan bo'lsa, nomdan aniqlanadi (ritm emas).
const SEMANTIC: Array<[StatVariant, RegExp]> = [
  ['danger', /bekor|xato|muammo|kechik|muddati o't|rad etil|rad qil|blok|shikoyat|qarz|failed|nizo|o'chiril|zarar|xavf|tugagan/],
  ['warning', /magan\b|kutil|jarayon|qayt|ko'rib|navbat|tekshir|hold|pending|moderats|ochiq|kam qol|yo'lda|qadoqla/],
  ['success', /yetkazil|faol|to'lan|tasdiq|muvaffaq|yakunla|tayyor|daromad|tushum|foyda|qabul qil|sotil|bajaril|onlayn|chek tayyor|yetib bor/],
  ['provided', /^jami|^umumiy|^barcha/],
];
function inferVariant(label: ReactNode): StatVariant | null {
  if (typeof label !== 'string') return null;
  const text = label.toLowerCase().replace(/[‘’ʻʼ`]/g, "'");
  const hit = SEMANTIC.find(([, re]) => re.test(text));
  return hit ? hit[0] : 'primary';
}

interface StatWidgetProps {
  label: ReactNode;
  value: ReactNode;
  index?: number;
  variant?: StatVariant;
  sub?: ReactNode;
  trend?: ReactNode;
  trendTone?: 'success' | 'danger';
  help?: string;
  href?: string;
  onClick?: () => void;
  selected?: boolean;
  className?: string;
}

export function StatWidget({ label, value, index = 0, variant, sub, trend, trendTone = 'success', help, href, onClick, selected, className = '' }: StatWidgetProps) {
  const v = variant || inferVariant(label) || RHYTHM[index % RHYTHM.length];
  const dot = DOT[v];

  const cardCls = [
    'card h-100 kc-stat kb-stat',
    href || onClick ? 'kb-stat-action' : '',
    selected ? 'is-selected' : '',
    className,
  ].filter(Boolean).join(' ');

  const body = (
    <div className="card-body">
      <div className="kb-stat-head">
        <span className={`kb-stat-dot bg-${dot}`} aria-hidden="true"></span>
        <span className="kc-stat-label kb-stat-label" title={typeof label === 'string' ? label : undefined}>{label}</span>
        {help ? <InfoHint text={help} /> : null}
        {href ? <i className="ti ti-arrow-up-right kb-stat-go" aria-hidden="true"></i> : null}
      </div>
      <h4 className="kb-stat-value">{value}</h4>
      {sub || trend ? (
        <p className="kb-stat-sub">
          {trend ? <span className={`kb-stat-trend text-${trendTone}-dark`}>{trend}</span> : null}
          {sub ? <span className="txt-ellipsis-1">{sub}</span> : null}
        </p>
      ) : null}
    </div>
  );

  if (href) return <Link href={href} className={`${cardCls} d-block text-decoration-none`}>{body}</Link>;
  if (onClick) return <button type="button" className={`${cardCls} w-100 text-start p-0`} onClick={onClick} aria-pressed={selected}>{body}</button>;
  return <div className={cardCls}>{body}</div>;
}

/**
 * Kichik ko'rsatkich kartasi — Axelit ro'yxat vidjetlaridagi kabi:
 * chapda "text-light-*" fonli ikonka, o'ngda nom, qiymat va izoh.
 */
export type Tone = 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'dark';
interface MiniStatProps {
  label: ReactNode;
  value: ReactNode;
  icon?: string;
  meta?: ReactNode;
  help?: string;
  tone?: Tone;
  valueTone?: Tone | '';
  className?: string;
}
export function MiniStat({ label, value, icon, meta, help, tone = 'primary', valueTone = '', className = '' }: MiniStatProps) {
  return (
    <div className={`kb-mini h-100 d-flex align-items-start gap-3 ${className}`}>
      {icon ? <span className={`kb-mini-ico d-flex-center flex-shrink-0 text-light-${tone}`}><i className={icon}></i></span> : null}
      <div className="min-w-0 flex-grow-1">
        <p className="mb-0 text-secondary f-s-13 f-w-500 d-flex align-items-center gap-1">
          <span className="kc-stat-label" title={typeof label === 'string' ? label : undefined}>{label}</span>{help ? <InfoHint text={help} /> : null}
        </p>
        <h6 className={`mb-0 f-w-600 f-s-16 text-break ${valueTone ? `text-${valueTone}${valueTone === 'warning' ? '-dark' : ''}` : 'text-dark'}`}>{value}</h6>
        {meta ? <p className="mb-0 text-muted f-s-12 txt-ellipsis-1">{meta}</p> : null}
      </div>
    </div>
  );
}

/** Bo'sh holat — Axelit jadval/kartalaridagi arxiv ikonkasi bilan. */
export function EmptyState({ text, icon = 'iconoir-archive', className = '' }: { text: ReactNode; icon?: string; className?: string }) {
  return (
    <div className={`text-center py-4 ${className}`}>
      <i className={`${icon} d-flex justify-content-center mb-2 f-s-28`} style={{ color: '#94A3B8' }}></i>
      <p className="mb-0 text-secondary f-s-14">{text}</p>
    </div>
  );
}
