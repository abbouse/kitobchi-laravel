import { createContext, useContext, useEffect, useLayoutEffect, useRef, useState, type CSSProperties, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { Modal as BsModal } from 'react-bootstrap';
import { Link, usePage } from '@inertiajs/react';
import { findCurrentNav } from '../Layout';

/**
 * Panel bo'ylab yagona oyna komponenti (react-bootstrap Modal bilan bir xil API).
 *
 *  • Kichik oynalar (tasdiqlash, qisqa formalar) — Axelit markazdagi modal.
 *  • Katta oynalar (size="lg" | "xl": ko'rish, batafsil, katta tahrirlash formalari)
 *    alohida "show sahifa" bo'lib ochiladi: ro'yxat yashiriladi, o'rniga Axelit
 *    sahifa sarlavhasi (main-title + breadcrumb + Orqaga) va kartalar chiqadi.
 *    Brauzerning "Orqaga" tugmasi ham sahifani yopadi, ro'yxat holati saqlanadi.
 */
type Size = 'sm' | 'lg' | 'xl';
interface Props {
  show: boolean;
  onHide: () => void;
  size?: Size;
  centered?: boolean;
  scrollable?: boolean;
  onExited?: () => void;
  className?: string;
  /** Majburan sahifa (true) yoki modal (false) ko'rinishi */
  page?: boolean;
  children?: ReactNode;
}

const PageCtx = createContext<{ onHide: () => void } | null>(null);
let openPages = 0;

function ShowPage({ show, onHide, onExited, children }: { show: boolean; onHide: () => void; onExited?: () => void; children?: ReactNode }) {
  const [root, setRoot] = useState<HTMLElement | null>(null);
  const hideRef = useRef(onHide);
  hideRef.current = onHide;
  const wasOpen = useRef(false);

  useEffect(() => { setRoot(document.getElementById('kc-show-root')); }, []);

  useEffect(() => {
    if (!show) {
      if (wasOpen.current) { wasOpen.current = false; onExited?.(); }
      return;
    }
    wasOpen.current = true;
    const scrollY = window.scrollY;
    openPages += 1;
    document.body.classList.add('kc-show-open');
    window.scrollTo({ top: 0 });

    // Brauzer "Orqaga" → sahifani yopish (Inertia'ga yetkazmasdan)
    let closedByHistory = false;
    let swallowNext = false;
    try { window.history.pushState({ ...(window.history.state || {}), kcShow: openPages }, ''); } catch { /* */ }
    const onPop = (event: PopStateEvent) => {
      event.stopImmediatePropagation();
      if (swallowNext) { swallowNext = false; return; }
      closedByHistory = true;
      hideRef.current();
    };
    window.addEventListener('popstate', onPop, true);

    return () => {
      openPages = Math.max(0, openPages - 1);
      if (!openPages) document.body.classList.remove('kc-show-open');
      if (!closedByHistory && (window.history.state as { kcShow?: number } | null)?.kcShow) {
        swallowNext = true;
        window.history.back();
        // popstate kelgach tinglovchini olib tashlaymiz
        window.setTimeout(() => window.removeEventListener('popstate', onPop, true), 400);
      } else {
        window.removeEventListener('popstate', onPop, true);
      }
      window.requestAnimationFrame(() => window.scrollTo({ top: scrollY }));
    };
  }, [show]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!show || !root) return null;
  return createPortal(
    <PageCtx.Provider value={{ onHide }}>
      <div className="kc-show">{children}</div>
    </PageCtx.Provider>,
    root,
  );
}

function AppModal({ show, onHide, size, onExited, page, className, children, scrollable }: Props) {
  const asPage = page ?? (size === 'lg' || size === 'xl');
  if (asPage) return <ShowPage show={show} onHide={onHide} onExited={onExited}>{children}</ShowPage>;
  return (
    <BsModal show={show} onHide={onHide} size={size} centered scrollable={scrollable} onExited={onExited} className={`app-modal ${className || ''}`}>
      {children}
    </BsModal>
  );
}

function ShowCrumbs({ title, onHide }: { title: ReactNode; onHide: () => void }) {
  const { url } = usePage();
  const cur = findCurrentNav(url);
  return (
    <ul className="app-line-breadcrumbs mt-1 mb-0">
      {cur ? (
        <li>
          <Link href={cur.match === '/boshqaruv' ? '/boshqaruv' : cur.groupFirst} className="f-s-14 f-w-500">
            <span><i className={`${cur.groupAx} f-s-16 align-text-top`}></i> {cur.group === 'Asosiy' ? 'Boshqaruv' : cur.group}</span>
          </Link>
        </li>
      ) : null}
      {cur ? (
        <li>
          <a href={cur.to} className="f-s-14 f-w-500" onClick={(event) => { event.preventDefault(); onHide(); }}>{cur.label}</a>
        </li>
      ) : null}
      <li className="active"><span className="f-s-14 f-w-500 txt-ellipsis-1 d-inline-block align-bottom">{title}</span></li>
    </ul>
  );
}

function Header({ children, closeButton, className }: { children?: ReactNode; closeButton?: boolean; className?: string }) {
  const ctx = useContext(PageCtx);
  if (!ctx) return <BsModal.Header closeButton={closeButton} className={className}>{children}</BsModal.Header>;
  return (
    <div className="d-flex align-items-center justify-content-between flex-wrap gap-3 mx-1 mb-3">
      <div className="d-flex align-items-center gap-3 min-w-0">
        <button type="button" className="btn btn-light-primary icon-btn b-r-22 flex-shrink-0" onClick={ctx.onHide} title="Orqaga" aria-label="Orqaga">
          <i className="ti ti-arrow-left f-s-18"></i>
        </button>
        <div className="min-w-0">
          {children}
          <ShowCrumbs title={<TitleText>{children}</TitleText>} onHide={ctx.onHide} />
        </div>
      </div>
      {closeButton ? (
        <button type="button" className="btn btn-light-secondary" onClick={ctx.onHide}>
          <i className="ti ti-x me-1"></i>Yopish
        </button>
      ) : null}
    </div>
  );
}

// Breadcrumb'dagi sarlavha: Title shu kontekstda oddiy matn bo'lib chiqadi
const CrumbCtx = createContext(false);
function TitleText({ children }: { children?: ReactNode }) {
  return <CrumbCtx.Provider value>{children}</CrumbCtx.Provider>;
}

function Title({ children, className, as }: { children?: ReactNode; className?: string; as?: 'h4' | 'h5' | 'div' }) {
  const ctx = useContext(PageCtx);
  const inCrumb = useContext(CrumbCtx);
  if (inCrumb) return <>{children}</>;
  if (!ctx) return <BsModal.Title as={as || 'h5'} className={`modal-title ${className || ''}`}>{children}</BsModal.Title>;
  return <h4 className="main-title mb-0 txt-ellipsis-1">{children}</h4>;
}

function Body({ children, className, style }: { children?: ReactNode; className?: string; style?: CSSProperties }) {
  const ctx = useContext(PageCtx);
  if (!ctx) return <BsModal.Body className={className} style={style}>{children}</BsModal.Body>;
  return <PageBody className={className}>{children}</PageBody>;
}

// Sahifa tanasi: ichida o'z kartalari bo'lsa (batafsil ko'rinishlar) — to'g'ridan-to'g'ri,
// aks holda (formalar, oddiy matn) bitta Axelit kartasiga o'raladi.
function PageBody({ children, className }: { children?: ReactNode; className?: string }) {
  const ref = useRef<HTMLDivElement>(null);
  const [bare, setBare] = useState(false);
  useLayoutEffect(() => {
    const el = ref.current;
    if (!el) return;
    const check = () => setBare(!!el.querySelector(':scope > div > .card, :scope > div > .row > [class*="col"] > .card, :scope > div > .row > [class*="col"] > .row > [class*="col"] > .card'));
    check();
    const mo = new MutationObserver(check);
    mo.observe(el, { childList: true, subtree: true });
    return () => mo.disconnect();
  }, []);
  return (
    <div ref={ref} className={bare ? `kc-show-body ${className || ''}` : 'card'}>
      <div className={bare ? '' : `card-body ${className || ''}`}>{children}</div>
    </div>
  );
}

function Footer({ children, className }: { children?: ReactNode; className?: string }) {
  const ctx = useContext(PageCtx);
  if (!ctx) return <BsModal.Footer className={className}>{children}</BsModal.Footer>;
  return (
    <div className="card position-sticky bottom-0 z-2">
      <div className={`card-body d-flex flex-wrap align-items-center justify-content-end gap-2 py-3 ${className || ''}`}>{children}</div>
    </div>
  );
}

const Modal = Object.assign(AppModal, { Header, Title, Body, Footer });
export default Modal;
