import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

interface InfoHintProps {
  title?: string;
  text: string;
  /** eski API bilan moslik uchun (ko'rinishga ta'sir qilmaydi) */
  tone?: 'light' | 'dark';
}

export default function InfoHint({ title = 'Bu qanday hisoblanadi?', text }: InfoHintProps) {
  const [open, setOpen] = useState(false);
  const [position, setPosition] = useState({ top: 0, left: 0 });
  const buttonRef = useRef<HTMLButtonElement | null>(null);

  useEffect(() => {
    if (!open) return;

    const sync = () => {
      const rect = buttonRef.current?.getBoundingClientRect();
      if (!rect) return;

      setPosition({
        top: Math.min(rect.bottom + 8, window.innerHeight - 24),
        left: Math.min(Math.max(12, rect.left - 220), window.innerWidth - 332),
      });
    };

    sync();
    window.addEventListener('resize', sync);
    window.addEventListener('scroll', sync, true);
    return () => {
      window.removeEventListener('resize', sync);
      window.removeEventListener('scroll', sync, true);
    };
  }, [open]);

  useEffect(() => {
    if (!open) return;

    const close = (event: MouseEvent) => {
      const target = event.target as Node;
      if (buttonRef.current?.contains(target)) return;
      setOpen(false);
    };

    document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, [open]);

  return (
    <>
      <button
        ref={buttonRef}
        type="button"
        className="bg-transparent border-0 p-0 text-secondary d-inline-flex align-items-center f-s-16 lh-1 flex-shrink-0"
        aria-label={title}
        aria-expanded={open}
        onClick={(event) => {
          event.preventDefault();
          event.stopPropagation();
          setOpen((value) => !value);
        }}
      >
        <i className="ti ti-info-circle"></i>
      </button>
      {open && createPortal(
        // Bootstrap/Axelit popover markupi
        <div className="popover bs-popover-bottom show" style={{ position: 'fixed', top: position.top, left: position.left, maxWidth: 320 }} role="dialog">
          <div className="popover-header f-w-600">{title}</div>
          <div className="popover-body">{text}</div>
        </div>,
        document.body,
      )}
    </>
  );
}
