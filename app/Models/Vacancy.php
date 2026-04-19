<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    /** @return array<string, string> slug => admin sarlavhasi */
    public static function iconOptions(): array
    {
        return [
            'briefcase' => 'Umumiy / lavozim',
            'code' => 'IT / texnologiya',
            'palette' => 'Dizayn / ijod',
            'shop' => 'Savdo / marketpleys',
            'megaphone' => 'Marketing',
            'people' => 'HR / jamoa',
            'chart' => 'Analitika / moliya',
        ];
    }

    public function resolvedIcon(): string
    {
        $key = $this->icon ?? 'briefcase';

        return array_key_exists($key, self::iconOptions()) ? $key : 'briefcase';
    }

    protected $fillable = [
        'title',
        'icon',
        'contract_type',
        'location',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function careerApplications(): HasMany
    {
        return $this->hasMany(CareerApplication::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(VacancyTranslation::class);
    }

    public function localizedTitle(): string
    {
        return $this->localizedField('title');
    }

    public function localizedContractType(): ?string
    {
        $v = $this->localizedField('contract_type', null);

        return $v === '' ? null : $v;
    }

    public function localizedLocation(): ?string
    {
        $v = $this->localizedField('location', null);

        return $v === '' ? null : $v;
    }

    public function localizedDescription(): string
    {
        return $this->localizedField('description');
    }

    private function localizedField(string $field, ?string $emptyAs = ''): string
    {
        $loc = app()->getLocale();
        if ($loc === 'uz') {
            return (string) ($this->{$field} ?? $emptyAs ?? '');
        }

        $tr = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $loc)
            : $this->translations()->where('locale', $loc)->first();

        $fromTr = $tr?->{$field} ?? null;

        if (is_string($fromTr) && $fromTr !== '') {
            return $fromTr;
        }

        $base = $this->{$field} ?? '';

        return $base === null ? (string) ($emptyAs ?? '') : (string) $base;
    }
}
