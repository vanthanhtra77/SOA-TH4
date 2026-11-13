<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * Tên bảng trong cơ sở dữ liệu
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * Khóa chính của bảng
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Các trường cho phép gán hàng loạt (Mass Assignable)
     *
     * @var array
     */
    protected $fillable = [
        'customer_name',
        'customer_email',
        'total_amount',
        'status',
        'user_id',
        'shipping_address',
        'notes',
    ];

    /**
     * Ép kiểu dữ liệu cho các trường
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tự động nối thêm các thuộc tính tương thích ngược (Backward Compatibility)
     * Giúp tương thích liền mạch với Service 4 (Reporting Service)
     *
     * @var array
     */
    protected $appends = [
        'total_price',
        'quantity',
        'product_name',
        'product_id',
        'user_name',
    ];

    /**
     * Quan hệ: Một Đơn hàng có nhiều Chi tiết mặt hàng (Order Items)
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Accessor total_price (alias cho total_amount)
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->total_amount;
    }

    /**
     * Accessor user_name (alias cho customer_name)
     */
    public function getUserNameAttribute(): string
    {
        return (string) ($this->customer_name ?? 'Khách hàng');
    }

    /**
     * Accessor quantity (tổng số lượng sản phẩm từ các order items)
     */
    public function getQuantityAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('quantity');
        }
        return (int) $this->items()->sum('quantity');
    }

    /**
     * Accessor product_name (tên mặt hàng đầu tiên hoặc tóm tắt)
     */
    public function getProductNameAttribute(): string
    {
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? $firstItem->product_name : 'Đơn hàng';
    }

    /**
     * Accessor product_id (ID mặt hàng đầu tiên)
     */
    public function getProductIdAttribute(): ?int
    {
        $firstItem = $this->relationLoaded('items') ? $this->items->first() : $this->items()->first();
        return $firstItem ? $firstItem->product_id : null;
    }

    /**
     * Tính toán lại tổng tiền của đơn hàng từ các chi tiết mặt hàng
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = (float) $this->items()->sum('total_price');
        $this->save();
    }
}
