<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kuryer hujjatlari — pasport, haydovchi guvohnomasi, transport texpasporti
 * va boshqa skanlar. Admin paneldan yuklanadi va admin paneldan ko'riladi.
 *
 * `storage/app/public/courier_documents/{courier_id}/...` da saqlanadi.
 */
class CourierDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
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

    public function getFileUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }
        return asset('storage/' . ltrim($this->file_path, '/'));
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'passport'            => 'Pasport',
            'driver_license'      => 'Haydovchi guvohnomasi',
            'vehicle_reg'         => 'Transport guvohnomasi',
            'vehicle_insurance'   => 'Sug\'urta polisi',
            'inn_certificate'     => 'STIR guvohnomasi',
            'medical_cert'        => 'Tibbiy ma\'lumotnoma',
            'photo_with_passport' => 'Pasport bilan selfi',
            'other'               => 'Boshqa',
            default               => $this->type,
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'passport'            => 'user-round',
            'driver_license'      => 'id-card',
            'vehicle_reg'         => 'car',
            'vehicle_insurance'   => 'shield-check',
            'inn_certificate'     => 'badge-check',
            'medical_cert'        => 'stethoscope',
            'photo_with_passport' => 'camera',
            default               => 'file',
        };
    }

    // ── Relationships ────────────────────────────────────────────

    public function courier()
    {
        return $this->belongsTo(Couriers::class, 'courier_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by')
            ->select('id', 'name', 'lastname');
    }
}
