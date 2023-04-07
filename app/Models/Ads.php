<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Ads extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "ads";

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [    
        'title',
        'url',
        'link',
        'frequency',
        'location',
    ];

    public function getUrlAttribute($url)
    {
        return config('app.url').Storage::url($url);
    }
}
