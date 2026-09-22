import { useState, type FormEvent, type ReactNode } from 'react';
import Modal from './AppModal';

/**
 * Sahifa ichidagi tahrirlash formalari o'rniga: tugma → Axelit modal ichidagi forma.
 * Forma maydonlari (name=...) o'zgarmaydi, shuning uchun backend so'rovlari avvalgidek.
 */
export interface FormActionProps {
  /** Tugma matni */
  label: ReactNode;
  icon?: string;
  /** Axelit tugma rangi: light-primary, primary, light-danger, outline-danger ... */
  variant?: string;
  size?: 'sm' | 'md';
  block?: boolean;
  disabled?: boolean;
  title: ReactNode;
  description?: ReactNode;
  submitLabel?: ReactNode;
  submitVariant?: string;
  /** Formani yuborish (event.currentTarget — forma). true qaytarsa (yoki hech narsa) oyna yopiladi */
  onSubmit: (event: FormEvent<HTMLFormElement>) => void | boolean;
  confirmText?: string;
  modalSize?: 'lg';
  children: ReactNode;
  className?: string;
  buttonTitle?: string;
}

export default function FormAction({ label, icon, variant = 'light-primary', size = 'sm', block, disabled, title, description, submitLabel = 'Saqlash', submitVariant = 'primary', onSubmit, confirmText, modalSize, children, className = '', buttonTitle }: FormActionProps) {
  const [open, setOpen] = useState(false);
  const submit = (event: FormEvent<HTMLFormElement>) => {
    if (confirmText && !window.confirm(confirmText)) { event.preventDefault(); return; }
    const result = onSubmit(event);
    if (result !== false) setOpen(false);
  };
  return (
    <>
      <button type="button" title={buttonTitle} className={`btn btn-${variant} ${size === 'sm' ? 'btn-sm' : ''} ${block ? 'w-100' : ''} d-inline-flex align-items-center justify-content-center gap-1 ${className}`} disabled={disabled} onClick={() => setOpen(true)}>
        {icon ? <i className={`${icon} f-s-16`}></i> : null}{label}
      </button>
      <Modal show={open} onHide={() => setOpen(false)} page={modalSize === 'lg' ? false : undefined} size={modalSize}>
        <form className="app-form" onSubmit={submit}>
          <Modal.Header closeButton><Modal.Title className="f-s-20 f-w-600">{title}</Modal.Title></Modal.Header>
          <Modal.Body>
            {description ? <p className="text-secondary f-s-13 mb-3">{description}</p> : null}
            {children}
          </Modal.Body>
          <Modal.Footer>
            <button type="button" className="btn btn-light-secondary" onClick={() => setOpen(false)}>Bekor qilish</button>
            <button type="submit" className={`btn btn-${submitVariant}`}>{submitLabel}</button>
          </Modal.Footer>
        </form>
      </Modal>
    </>
  );
}

/** Ko'rish rejimidagi "sozlama" qatori: ikonka + nom + qiymat + o'ngda tahrirlash tugmasi. */
export function ActionRow({ icon, tone = 'primary', title, value, meta, action }: { icon: string; tone?: string; title: ReactNode; value?: ReactNode; meta?: ReactNode; action?: ReactNode }) {
  return (
    <div className="d-flex flex-wrap align-items-center gap-3 py-3 b-b-1-light kc-action-row">
      <span className={`h-40 w-40 d-flex-center b-r-10 f-s-20 flex-shrink-0 text-light-${tone}`}><i className={icon}></i></span>
      <div className="flex-grow-1 min-w-0 kc-action-text">
        <h6 className="mb-0 f-w-600 f-s-14">{title}</h6>
        {value !== undefined && value !== null && value !== '' ? <div className="f-s-13 text-dark text-break">{value}</div> : null}
        {meta ? <div className="f-s-12 text-secondary">{meta}</div> : null}
      </div>
      {action ? <div className="flex-shrink-0 d-flex flex-wrap gap-2 ms-auto">{action}</div> : null}
    </div>
  );
}
