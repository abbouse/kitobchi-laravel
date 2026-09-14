import { ChangeEvent, useEffect, useMemo, useState } from 'react';

/**
 * Katalog mahsuloti (kitob/kanstovar) rasmlarini boshqarish uchun qulay UI.
 *
 * Ilgari bu joyda oddiy <textarea> bo'lardi — admin rasmni o'chirish uchun
 * xom URL matnidan kerakli qatorni qo'lda topib o'chirishi kerak edi, birinchi
 * rasmni (muqova/asosiy rasm) almashtirish uchun esa qatorlarni qo'lda
 * qayta tartiblashi kerak edi. Backend (`syncCatalogImages`) hech qanday
 * o'zgarishsiz shu formatga tayanadi: u `images_text` maydonidagi (har
 * qatorda bitta URL) ro'yxatni joriy rasmlar bilan solishtirib, tushib
 * qolganlarini diskdan o'chiradi. Shu sababli bu komponent faqat vizual
 * qatlam — pastda hamon xuddi shu formatdagi (lekin ko'rinmas) maydonni
 * to'ldirib beradi, forma submit qilinganda backend hech narsani sezmaydi.
 *
 * Yangi fayl yuklash uchun native <input type="file" multiple> o'zgarishsiz
 * qoladi — brauzerning o'zi bir nechta faylni bir martada tanlashni allaqachon
 * qo'llab-quvvatlaydi, shuning uchun bu yerga qo'shimcha murakkablik
 * kiritilmadi (faqat tanlangan fayllar uchun kichik oldindan ko'rish qo'shildi).
 */
export default function ImageGalleryEditor({
  images,
  fileFieldName = 'images',
  textFieldName = 'images_text',
  cardHeight = 90,
}: {
  images: string[];
  fileFieldName?: string;
  textFieldName?: string;
  cardHeight?: number;
}) {
  const [kept, setKept] = useState<string[]>(images);
  const [pendingFiles, setPendingFiles] = useState<File[]>([]);

  useEffect(() => {
    setKept(images);
  }, [images]);

  const previews = useMemo(
    () => pendingFiles.map((file) => ({ file, url: URL.createObjectURL(file) })),
    [pendingFiles],
  );
  useEffect(() => () => previews.forEach((item) => URL.revokeObjectURL(item.url)), [previews]);

  const removeExisting = (url: string) => setKept((current) => current.filter((item) => item !== url));
  const makeCover = (url: string) => setKept((current) => [url, ...current.filter((item) => item !== url)]);
  const onFilesSelected = (event: ChangeEvent<HTMLInputElement>) => {
    setPendingFiles(Array.from(event.target.files || []));
  };

  return (
    <div>
      <input type="hidden" name={textFieldName} value={kept.join('\n')} readOnly />

      {kept.length ? (
        <div className="d-flex flex-wrap gap-2 mb-2">
          {kept.map((url, index) => (
            <div
              key={url}
              className="position-relative"
              style={{ width: cardHeight, height: cardHeight, border: '1px solid #e5e7eb', borderRadius: 8, overflow: 'hidden', background: '#f9fafb' }}
            >
              <img src={url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
              {index === 0 ? (
                <span className="chip chip-success" style={{ position: 'absolute', top: 2, left: 2, fontSize: 8, padding: '1px 5px' }}>Asosiy</span>
              ) : (
                <button
                  type="button"
                  title="Asosiy rasm qilish"
                  className="btn btn-light btn-sm"
                  style={{ position: 'absolute', top: 2, left: 2, padding: '1px 5px', fontSize: 10, lineHeight: 1 }}
                  onClick={() => makeCover(url)}
                >
                  <i className="bi bi-star"></i>
                </button>
              )}
              <button
                type="button"
                title="Rasmni o'chirish"
                className="btn btn-danger btn-sm"
                style={{ position: 'absolute', top: 2, right: 2, padding: '1px 5px', fontSize: 10, lineHeight: 1 }}
                onClick={() => removeExisting(url)}
              >
                <i className="bi bi-x"></i>
              </button>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-muted small mb-2">Hozircha rasm yo'q.</div>
      )}

      <input className="form-control form-control-sm" type="file" name={fileFieldName} multiple accept="image/*" onChange={onFilesSelected} />

      {previews.length ? (
        <div className="d-flex flex-wrap gap-2 mt-2">
          {previews.map((item) => (
            <div
              key={item.url}
              className="position-relative"
              style={{ width: cardHeight, height: cardHeight, border: '1px dashed #3A3475', borderRadius: 8, overflow: 'hidden' }}
              title={item.file.name}
            >
              <img src={item.url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
              <span className="chip chip-info" style={{ position: 'absolute', bottom: 2, left: 2, fontSize: 8, padding: '1px 5px' }}>Yangi</span>
            </div>
          ))}
        </div>
      ) : null}
    </div>
  );
}
