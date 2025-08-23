<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'page_id',
        'openai_token',
        'ai_context',
        'price_per_unit',
        'price_per_combo',
    ];

    // Example relationship (if you have a User model)
    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
}