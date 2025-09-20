<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $image
 * @property int $status
 */
class Brand extends Model
{
    use StorageTrait, CacheManagerTrait;

    protected $fillable = [
        'name',
        'image',
        'image_storage_type',
        'image_alt_text',
        'status'
    ];

    protected $casts = [
        'name' => 'string',
        'image' => 'string',
        'image_storage_type' => 'string',
        'image_alt_text' => 'string',
        'status' => 'integer',
        'brand_products_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive(): mixed
    {
        return $this->where('status', 1);
    }

    public function brandProducts(): HasMany
    {
        return $this->hasMany(Product::class)->active();
    }

    public function brandAllProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function getNameAttribute($name): string|null
    {
        if (strpos(url()->current(), '/admin') || strpos(url()->current(), '/vendor') || strpos(url()->current(), '/seller')) {
            return $name;
        }
        // Prefer translation value for key 'name' when available
        $translated = null;
        foreach ($this->translations as $t) {
            if (($t->key ?? null) === 'name' && isset($t->value)) {
                $translated = $t->value;
                break;
            }
        }
        return $translated ?? $name;
    }

    public function getDefaultNameAttribute(): string|null
    {
        return $this->translations[0]->value ?? $this->name;
    }

    public function storage(): MorphMany
    {
        return $this->morphMany(Storage::class, 'data');
    }

    public function getImageFullUrlAttribute(): array
    {
        $value = $this->image;
        return $this->storageLink('brand', $value, $this->image_storage_type ?? 'public');
    }

    protected $appends = ['image_full_url'];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'brands');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'brands');
        });

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                $currentLocale = strpos(url()->current(), '/api') ? App::getLocale() : getDefaultLanguage();
                $allowedLocales = [$currentLocale];
                try {
                    $languages = getWebConfig('language');
                    if (is_array($languages)) {
                        foreach ($languages as $ln) {
                            if (!empty($ln['code'])) {
                                $langCode = function_exists('getLanguageCode') ? getLanguageCode($ln['code']) : null;
                                if ($langCode && strtolower($langCode) === strtolower($currentLocale)) {
                                    $allowedLocales[] = strtolower($ln['code']);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
                if (count($allowedLocales) === 1) {
                    if (strtolower($currentLocale) === 'ar') {
                        $allowedLocales = array_merge($allowedLocales, ['sa','ae','eg','jo','kw','lb','ly','ma','om','qa','sy','tn','ye','bh','iq']);
                    } elseif (strtolower($currentLocale) === 'en') {
                        $allowedLocales = array_merge($allowedLocales, ['us','gb','au','ca','nz','sg','ie','za','zw','tt','jm','my','bz']);
                    }
                }
                                $allowedLocales = array_unique(array_map('strtolower', $allowedLocales));
                                return $query->where(function($q) use ($allowedLocales, $currentLocale) {
                                        $q->whereIn(DB::raw('LOWER(locale)'), $allowedLocales)
                                            ->orWhereRaw('LOWER(locale) like ?', [strtolower($currentLocale).'%' ]);
                                });
            }]);
        });
    }
}
