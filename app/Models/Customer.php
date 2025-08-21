<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Customer extends Model
{
use HasFactory;
protected $fillable = ['external_id','page_id','name','phone','meta'];
protected $casts = ['meta' => 'array'];


public function deals() { return $this->hasMany(CustomerDeal::class); }
public function messages() { return $this->hasMany(ChatMessage::class); }
}