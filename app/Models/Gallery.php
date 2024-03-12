<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'parentId',
    ];

    public function images()
    {
        return $this->hasMany(Image::class, 'galleryId', 'id');
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class, 'parentId', 'id');
    }
}
