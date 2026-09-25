<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Menu
 *
 * Stores all dimsum menu items managed by admin.
 * Soft-deleted records remain accessible via OrderItems (price snapshot).
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property string      $category   siomay|hakau|lumpia|bao|shumai|minuman|lainnya
 * @property float       $price
 * @property string|null $image_path
 * @property bool        $is_available
 * @property int         $sort_order
 * @property int|null    $created_by
 */
class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('customer_all_available_menus');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('customer_all_available_menus');
        });
    }

    // -------------------------------------------------------
    // Mass Assignment
    // -------------------------------------------------------

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'price',
        'image_path',
        'is_available',
        'sort_order',
        'created_by',
        'badge',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'is_available' => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    /**
     * Admin who created this menu item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All order items that reference this menu item.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Master Recipes that are linked to this menu.
     */
    public function masterRecipes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\MasterRecipe::class, 'menu_master_recipes')
                    ->withPivot('multiplier')
                    ->withTimestamps();
    }

    // -------------------------------------------------------
    // Query Scopes
    // -------------------------------------------------------

    /**
     * Scope: only available items (shown to customers).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope: filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: ordered for display on the menu page.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Formatted price in IDR, e.g. "Rp 15.000".
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
