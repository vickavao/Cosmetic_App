<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\SaleType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'client_id',
        'user_id',
        'traite_par',
        'traite_le',
        'statut',
        'total',
        'date_commande',
        'notes',
        'motif_rejet',
        'type_vente',
        'date_echeance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statut' => OrderStatus::class,
            'type_vente' => SaleType::class,
            'total' => 'decimal:2',
            'date_commande' => 'date',
            'date_echeance' => 'date',
            'traite_le' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The staff member (Chef Marketing / Admin) who validated or rejected the order.
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function goodsIssueNote(): HasOne
    {
        return $this->hasOne(GoodsIssueNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'order_id');
    }

    /**
     * Pending orders.
     *
     * @param  Builder<self>  $query
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', OrderStatus::EnAttente->value);
    }
}
