import { useEffect, useState } from 'react';

/**
 * Kategoriya rasmi.
 *
 * Ilgari bu maydon oddiy matn edi va u yerga emoji yozilardi — bosh
 * sahifadagi kataloglar esa rasm kutardi, shuning uchun ular doim umumiy
 * logotip bilan ko'rinardi. Endi bu yerga haqiqiy rasm yuklanadi:
 *   • yangi fayl tanlansa — darhol ko'rinadi (preview);
 *   • hech narsa tanlanmasa — mavjud rasm o'zgarmaydi;
 *   • "O'chirish" belgilansa — rasm olib tashlanadi.
 */
export function categoryImageUrl(icon?: string | null): string | null {
  if (!icon) return null;
  // Eski qiymatlar emoji bo'lishi mumkin — ular rasm yo'li emas.
  if (!/[/.]/.test(icon)) return null;
  return icon.startsWith('http') ? icon : `/storage/${icon}`;
}

export default function CategoryImageField({ current }: { current?: string | null }) {
  const [preview, setPreview] = useState<string | null>(null);
  const [remove, setRemove] = useState(false);
  const currentUrl = categoryImageUrl(current);

  useEffect(() => {
    setPreview(null);
    setRemove(false);
  }, [current]);

  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

  const shown = preview || (remove ? null : currentUrl);

  return (
    <div className="col-12">
      <label className="form-label" htmlFor="category-icon-image">Kategoriya rasmi</label>
      <div className="d-flex align-items-center gap-3 flex-wrap">
        <div className="b-r-10 overflow-hidden d-flex-center bg-light-primary flex-shrink-0 w-55 h-55">
          {shown
            ? <img className="w-100 h-100 object-fit-cover" src={shown} alt="" />
            : <i className="ti ti-photo f-s-18"></i>}
        </div>
        <div className="flex-grow-1" style={{ minWidth: 220 }}>
          <input
            id="category-icon-image"
            type="file"
            name="icon_image"
            accept="image/png,image/jpeg,image/webp,image/svg+xml"
            className="form-control form-control-sm"
            onChange={(event) => {
              const file = event.target.files?.[0];
              setPreview(file ? URL.createObjectURL(file) : null);
              if (file) setRemove(false);
            }}
          />
          <div className="f-s-13 text-secondary mt-1">
            Kvadrat PNG yoki SVG, 2 MB gacha. Bo'sh qolsa bosh sahifada kategoriya nomining bosh harfi ko'rsatiladi.
          </div>
        </div>
        {currentUrl ? (
          <label className="form-check mb-0">
            <input
              className="form-check-input"
              type="checkbox"
              name="icon_remove"
              value="1"
              checked={remove}
              onChange={(event) => { setRemove(event.target.checked); if (event.target.checked) setPreview(null); }}
            />
            <span className="form-check-label ms-2 f-s-13 text-secondary">Rasmni o'chirish</span>
          </label>
        ) : null}
      </div>
    </div>
  );
}
