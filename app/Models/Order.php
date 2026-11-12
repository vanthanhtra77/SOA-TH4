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
        'user_id',
        'user_name',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'receiver_name',
        'receiver_phone',
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
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
