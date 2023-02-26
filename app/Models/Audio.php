<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Audio extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "audios";

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'url',
        'uploaderId',
        'uploaderRole',
    ];

    public function images() {
        return $this->hasMany(Image::class, 'audioId', 'id');
    }

    public function getUrlAttribute($url)
    {
        return config('app.url').Storage::url($url);
    }
}
