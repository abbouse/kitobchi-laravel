import { useState } from 'react';
import { Button } from 'react-bootstrap';
import Modal from './AppModal';

/**
 * Mahsulotni rad etish sababini so'raydigan oyna. Ilgari bu o'rinda native
 * `window.prompt()` ishlatilardi — u ilova uslubiga mos kelmaydi, mobil va
 * ba'zi brauzerlarda chalkash ko'rinadi va uni yopish/bekor qilish tugmalari
 * ilova dizayniga mos emas edi. Endi bir xil komponent Kitoblar va
 * Kanstovarlar sahifalarida qayta ishlatiladi.
 */
export default function ModerationRejectModal({
  show,
  itemLabel,
  onCancel,
  onConfirm,
}: {
  show: boolean;
  itemLabel?: string;
  onCancel: () => void;
  onConfirm: (reason: string) => void;
}) {
  const [reason, setReason] = useState('');

  const handleHide = () => {
    setReason('');
    onCancel();
  };

  const handleConfirm = () => {
    onConfirm(reason.trim());
    setReason('');
  };

  return (
    <Modal show={show} onHide={handleHide} centered>
      <Modal.Header closeButton>
        <Modal.Title className="f-s-20 f-w-600">Rad etish sababi{itemLabel ? ` — ${itemLabel}` : ''}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <label className="form-label f-s-13 text-muted">Sabab (ixtiyoriy, sellerga ko'rinadi)</label>
        <textarea
          className="form-control"
          rows={3}
          maxLength={500}
          autoFocus
          value={reason}
          onChange={(event) => setReason(event.target.value)}
          placeholder="Masalan: rasm sifati past, tavsif to'liq emas..."
        />
        <div className="form-text">Bo'sh qoldirilsa, standart sabab ko'rsatiladi.</div>
      </Modal.Body>
      <Modal.Footer>
        <Button variant="light-secondary" onClick={handleHide}>Bekor qilish</Button>
        <Button variant="danger" onClick={handleConfirm}>Rad etish</Button>
      </Modal.Footer>
    </Modal>
  );
}
