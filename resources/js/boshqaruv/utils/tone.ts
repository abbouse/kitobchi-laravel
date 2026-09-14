// Dizayn tizimi: ro'yxatlarda holat "chip" emas, rangli nuqta + matn bilan
// ko'rsatiladi (bir qatorda bir nechta chip bo'lsa jadval rang shovqiniga
// aylanadi). Sahifalardagi mavjud xxxChip() funksiyalari chip-* klassini
// qaytaradi — bu yordamchi o'sha klassni nuqta ohangiga o'giradi.
export type Tone = 'ok' | 'info' | 'warn' | 'danger' | 'neutral';

export const toneOf = (chipClass?: string): Tone => {
  const value = String(chipClass || '');
  if (value.includes('success')) return 'ok';
  if (value.includes('info')) return 'info';
  if (value.includes('warning')) return 'warn';
  if (value.includes('danger')) return 'danger';
  return 'neutral';
};
