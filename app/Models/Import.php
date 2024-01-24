<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $table = 'imports';

    protected $fillable = ['quantity','import_date']; 

    public function Product()
    {
        return $this->belongsTo(Product::class);
    }
}