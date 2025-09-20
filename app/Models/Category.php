<?php

namespace App\Models;

use App\Traits\CacheManagerTrait;
use App\Traits\StorageTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $icon
 * @property int $parent_id
 * @property int $position
 * @property int $home_status
 * @property int $priority
 */
class Category extends Model
{
    use StorageTrait, CacheManagerTrait;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'icon_storage_type',
        'parent_id',
        'position',
        'home_status',
        'priority',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'icon' => 'string',
        'icon_storage_type' => 'string',
        'parent_id' => 'integer',
        'position' => 'integer',
        'home_status' => 'integer',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function translations(): MorphMany
    {
        return $this->morphMany('App\Models\Translation', 'translationable');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id')->orderBy('priority', 'desc');
    }

    public function childes(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('priority', 'asc');
    }

    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    // Old Relation: sub_category_product
    public function subCategoryProduct(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_category_id', 'id');
    }

    // Old Relation: sub_sub_category_product
    public function subSubCategoryProduct(): HasMany
    {
        return $this->hasMany(Product::class, 'sub_sub_category_id', 'id');
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


    public function scopePriority($query): mixed
    {
        return $query->orderBy('priority', 'asc');
    }

    public function getIconFullUrlAttribute():array
    {
        $value = $this->icon;
        return $this->storageLink('category',$value,$this->icon_storage_type ?? 'public');
    }
    protected $appends = ['icon_full_url'];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            cacheRemoveByType(type: 'categories');
        });

        static::deleted(function ($model) {
            cacheRemoveByType(type: 'categories');
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
