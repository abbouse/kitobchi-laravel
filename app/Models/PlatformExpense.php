<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformExpense extends Model
{
    public const CATEGORIES = [
        'marketing' => 'Marketing va reklama',
        'packaging' => 'Qadoqlash materiallari',
        'logistics' => 'Logistika va transport',
        'returns' => 'Qaytim xarajati',
        'resend_subsidy' => "Qayta jo'natish subsidiyasi",
        'infrastructure' => 'Server va IT infratuzilma',
        'rent' => 'Ijara',
        'utilities' => 'Kommunal va aloqa',
        'professional_services' => 'Professional xizmatlar',
        'office' => 'Ofis va operatsion xarajatlar',
        'other' => 'Boshqa',
    ];

    protected $fillable = [
        'category',
        'amount',
        'spent_at',
        'title',
        'note',
        'order_id',
        'reference',
        'created_by',
        'telegram_user_id',
        'telegram_username',
        'telegram_name',
        'telegram_message_id',
        'source',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spent_at' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Sold::class, 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
