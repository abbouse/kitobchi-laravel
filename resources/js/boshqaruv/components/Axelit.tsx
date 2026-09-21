import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import InfoHint from './InfoHint';

/**
 * Axelit e-commerce dashboard vidjetlari (ecommerce_dashboard / widget sahifasi):
 *   provided — "card orders-provided-card"   (oq, katak fon, pastda aylana)
 *   primary  — "card bg-primary-300 product-sold-card"
 *   danger   — "card bg-danger-300 product-sold-card"
 *   store    — "card product-store-card"      (oq, yashil, tepada aylana)
 *   info / warning / success — "card bg-*-300 product-sold-card"
 * KPI qatorlarida `index` bo'yicha Axelit ritmi (provided → primary → danger → store) takrorlanadi.
 */
export type StatVariant = 'provided' | 'primary' | 'danger' | 'store' | 'info' | 'warning' | 'success';
const RHYTHM: StatVariant[] = ['provided', 'primary', 'danger', 'store'];

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
  const v = variant || RHYTHM[index % RHYTHM.length];
  const sold = v !== 'provided' && v !== 'store';
  const tone = v === 'store' ? 'success' : v;

  const cardCls = [
    'card h-100 kc-stat',
    v === 'provided' ? 'orders-provided-card' : v === 'store' ? 'product-store-card' : `bg-${tone}-300 product-sold-card`,
    href || onClick ? 'hover-effect' : '',
    selected ? 'b-2-primary' : '',
    className,
  ].filter(Boolean).join(' ');

  const helpEl = help ? <InfoHint text={help} /> : null;
  const subEl = sub || trend ? (
    <p className="mb-0 text-dark f-w-500 mt-1 d-flex align-items-center gap-2 min-w-0">
      {trend ? <span className={`badge flex-shrink-0 ${sold ? `bg-white-300 text-${trendTone}-dark` : `text-light-${trendTone}`}`}>{trend}</span> : null}
      {sub ? <span className="txt-ellipsis-1">{sub}</span> : null}
    </p>
  ) : null;

  const body = sold ? (
    <div className="card-body">
      <div>
        <h5 className={`text-${tone}-dark f-w-600 d-flex align-items-center gap-1`}>
          <span className="txt-ellipsis-1" title={typeof label === 'string' ? label : undefined}>{label}</span>{helpEl}
        </h5>
      </div>
      <div className="mt-3">
        <h4 className={`text-${tone}-dark mb-0`}>{value}</h4>
        {subEl}
      </div>
      {href ? (
        <span className={`bg-${tone} h-35 w-35 d-flex-center b-r-50 product-sold-icon`}>
          <i className="iconoir-arrow-right f-w-600 f-s-18"></i>
        </span>
      ) : null}
    </div>
  ) : (
    <div className="card-body">
      <i className="ph-bold ph-circle circle-bg-img"></i>
      <div>
        <p className={`f-s-18 f-w-600 txt-ellipsis-1 d-flex align-items-center gap-1 ${v === 'store' ? 'text-success' : 'text-dark'}`}>
          <span className="txt-ellipsis-1" title={typeof label === 'string' ? label : undefined}>{label}</span>{helpEl}
        </p>
        <h2 className={`${v === 'store' ? 'text-success-dark' : 'text-secondary-dark'} mb-0`}>{value}</h2>
        {subEl}
      </div>
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
    <div className={`bg-white b-1-light b-r-15 p-3 h-100 d-flex align-items-start gap-3 ${className}`}>
      {icon ? <span className={`h-40 w-40 d-flex-center b-r-10 f-s-20 flex-shrink-0 text-light-${tone}`}><i className={icon}></i></span> : null}
      <div className="min-w-0 flex-grow-1">
        <p className="mb-0 text-secondary f-s-13 f-w-500 d-flex align-items-center gap-1">
          <span className="txt-ellipsis-1">{label}</span>{help ? <InfoHint text={help} /> : null}
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
      <i className={`${icon} d-flex justify-content-center mb-2 f-s-30 text-primary`}></i>
      <p className="mb-0 text-secondary f-s-14">{text}</p>
    </div>
  );
}
