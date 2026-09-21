// Holat ohanglari. Sahifalardagi xxxChip() funksiyalari Axelit
// "text-light-*" klassini qaytaradi; toneOf uni ohang nomiga o'giradi,
// toneBadge esa ohangni Axelit badge rangiga.
export type Tone = 'ok' | 'info' | 'warn' | 'danger' | 'neutral';

export const toneOf = (chipClass?: string): Tone => {
  const value = String(chipClass || '');
  if (value.includes('success')) return 'ok';
  if (value.includes('info')) return 'info';
  if (value.includes('warning')) return 'warn';
  if (value.includes('danger')) return 'danger';
  return 'neutral';
};

const BADGE: Record<Tone, string> = {
  ok: 'text-light-success',
  info: 'text-light-info',
  warn: 'text-light-warning',
  danger: 'text-light-danger',
  neutral: 'text-light-secondary',
};

/** Ohang ('ok' | 'warn' ...) yoki chip klassidan Axelit badge rangi. */
export const toneBadge = (tone?: string): string => BADGE[(tone && tone in BADGE ? tone : toneOf(tone)) as Tone];
