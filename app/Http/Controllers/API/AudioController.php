<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\Gallery;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AudioController extends Controller
{
    public function all(Request $request) {
        $id = $request->input('id');
        $limit = $request->input('limit');
        $title = $request->input('title');

        if($id) 
        {
            $audio = Audio::with(['images'])->find($id);

            if($audio){
                return ResponseFormatter::success(
                    $audio, 
                    'Get audio data successfully'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data not found',
                    404
                );
            }
        }

        $audio = Audio::with(['images'])->where('uploaderId', Auth::user()->id)->orWhere('uploaderRole', $request->audioId);

        if($title) {
            $audio->where('title', 'like', '%' . $title . '%');
        }

        return ResponseFormatter::success(
            $audio->orderBy('created_at', 'DESC')->paginate($limit),
            'Get audio data successfully'
        );
    }

    public function add(Request $request) {
        $request->validate([
            'title' => 'required|string',
            'images' => 'required|array',
        ]);

        $audioFile = $request->file('audioFile');
        $audioPath = $audioFile->storeAs('public/audios', 'audio_'.uniqid().'.'.$audioFile->extension());

        // masukkan ke tabel audios
        $audio = Audio::create([
            'title' => $request->title,
            'url' => $audioPath,
            'uploaderId' => Auth::user()->id,
            'uploaderRole' => Auth::user()->role,
        ]);

        // cek galleries ada root belum
        $gallery = Gallery::find(1);
        if (!$gallery) {
            Gallery::create([
                'name' => 'root',
            ]);
        }

        // masukkan foto ke tabel galleries
        foreach($request->images as $image) {
            $imagePath = $image->storeAs('public/images', 'image_'.uniqid().'.'.$image->extension());

            Image::create([
                'url' => $imagePath,
                'audioId' => $audio->id,
                'galleryId' => 1,
            ]);
        }

        return ResponseFormatter::success(
            $audio->load('images'),
            'Add audio successfully'
        );
    }
}
