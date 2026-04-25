<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Seller shartnomasi bo'yicha audit jurnal.
 *
 * Shartnoma har safar yaratilganda / uzaytirilganda / to'xtatilganda
 * bir qator qo'shiladi. SellerController@update bu modelga yozadi.
 */
class SellerContractHistory extends Model
{
    use HasFactory;

    // Faqat created_at ishlatamiz, updated_at yo'q (immutable log)
    public $timestamps = false;
    protected $table = 'seller_contract_history';

    protected $fillable = [
        'seller_id',
        'action',
        'contract_number',
        'old_expires_at',
        'new_expires_at',
        'notes',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'old_expires_at' => 'date',
        'new_expires_at' => 'date',
        'created_at'     => 'datetime',
    ];

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created'    => 'Yaratildi',
            'extended'   => 'Uzaytirildi',
            'renewed'    => 'Qayta tuzildi',
            'terminated' => 'To\'xtatildi',
            'updated'    => 'Yangilandi',
            default      => $this->action,
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created'    => 'emerald',
            'extended'   => 'blue',
            'renewed'    => 'indigo',
            'terminated' => 'red',
            'updated'    => 'amber',
            default      => 'gray',
        };
    }

    // ── Relationships ────────────────────────────────────────────

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by')
            ->select('id', 'name', 'lastname');
    }
}
