<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


class CustomerDeal extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id','product_code','price','price_combo','currency','expires_at','source','meta'];
    protected $casts = ['meta' => 'array','expires_at' => 'datetime'];


    public function isExpired(): bool
    {
    return $this->expires_at ? now()->greaterThan($this->expires_at) : false;
    }
}
