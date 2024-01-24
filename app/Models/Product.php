<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function Stock()
    {
        return $this->hasOne(Stock::class);
    }
    public function Imports()
    {
        return $this->hasMany(Import::class);
    }
    public function Exports()
    {
        return $this->hasMany(Export::class);
    }
}