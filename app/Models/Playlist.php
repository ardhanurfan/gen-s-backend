<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Playlist extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'userId',
        'sequence',
    ];

    public function audios() {
        return $this->belongsToMany(Audio::class, 'audio_playlists', 'playlistId', 'audioId')->orderByPivot('sequence');
    }
}
