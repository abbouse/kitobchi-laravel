import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

interface InfoHintProps {
  title?: string;
  text: string;
  tone?: 'light' | 'dark';
}

export default function InfoHint({ title = 'Bu qanday hisoblanadi?', text, tone = 'light' }: InfoHintProps) {
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
        className={`info-hint-btn ${tone === 'dark' ? 'is-dark' : ''}`}
        aria-label={title}
        aria-expanded={open}
        onClick={(event) => {
          event.preventDefault();
          event.stopPropagation();
          setOpen((value) => !value);
        }}
      >
        ?
      </button>
      {open && createPortal(
        <div className={`info-hint-popover ${tone === 'dark' ? 'is-dark' : ''}`} style={position} role="dialog">
          <div className="info-hint-title">{title}</div>
          <div className="info-hint-text">{text}</div>
        </div>,
        document.body,
      )}
    </>
  );
}
