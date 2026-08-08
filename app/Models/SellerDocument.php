<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Seller hujjatlari — pasport skani, shartnoma PDF, litsenziya va h.k.
 * Admin paneldan yuklanadi, `storage/app/public/seller_documents/...` da saqlanadi.
 */
class SellerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size_kb',
        'uploaded_by',
        'description',
    ];

    protected $casts = [
        'file_size_kb' => 'integer',
    ];

    /**
     * Frontend'da ko'rsatish uchun full URL. Agar file_path http bilan boshlansa
     * (ya'ni tashqi havola), o'zini qaytaradi.
     */
    public function getFileUrlAttribute(): ?string
    {
        $path = is_string($this->file_path) ? trim($this->file_path) : '';

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Hujjat turi uchun foydalanuvchi-do'stona nom (o'zbekcha).
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'passport'        => 'Pasport',
            'contract'        => 'Shartnoma',
            'inn_certificate' => 'STIR guvohnomasi',
            'license'         => 'Litsenziya',
            'bank_details'    => 'Bank rekvizitlari',
            'addendum'        => 'Qo\'shimcha kelishuv',
            'other'           => 'Boshqa',
            default           => $this->type,
        };
    }

    /**
     * Icon nomi (lucide) — UI da ko'rsatish uchun.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'passport'        => 'user-round',
            'contract'        => 'file-signature',
            'inn_certificate' => 'badge-check',
            'license'         => 'award',
            'bank_details'    => 'landmark',
            'addendum'        => 'file-plus',
            default           => 'file',
        };
    }

    // ── Relationships ────────────────────────────────────────────

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Hujjatni yuklagan panel admin. `uploaded_by` faqat boshqaruv panelidan
     * (Auth::guard('panel')) to'ldiriladi — shuning uchun Admin modeliga
     * bog'langan, User'ga emas.
     */
    public function uploader()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by')
            ->select('id', 'name');
    }
}
