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

        $audio = Audio::with(['images'])->where(function($q) {$q->where('uploaderId', Auth::user()->id)->orWhere('uploaderRole', 'ADMIN');});

        if($title) {
            $audio->where('title', 'like', '%' . $title . '%');
        }

        return ResponseFormatter::success(
            $audio->orderBy('created_at', 'DESC')->get(),
            'Get audios data successfully'
        );
    }

    public function add(Request $request) {
        $request->validate([
            'title' => 'required|string',
            'images' => 'array',
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

        if ($request->images) {
            // cek galleries ada root belum
            $root = Gallery::where('name', 'root')->first();
            if (!$root) {
                $root = Gallery::create([
                    'name' => 'root',
                ]);
            }
    
            // masukkan foto ke tabel galleries
            foreach($request->images as $image) {
                $imagePath = $image->storeAs('public/images', 'image_'.uniqid().'.'.$image->extension());
    
                Image::create([
                    'url' => $imagePath,
                    'audioId' => $audio->id,
                    'galleryId' => $root->id,
                ]);
            }
        }

        return ResponseFormatter::success(
            $audio->load('images'),
            'Add audio successfully'
        );
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $audio = Audio::with(['images'])->find($request->id);

        if (!$audio) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        // delete images from storage
        foreach($audio->images as $image) {
            unlink(public_path(str_replace(config('app.url'),'',$image['url'])));
        }
        
        // delete audio from storage
        unlink(public_path(str_replace(config('app.url'),'',$audio->url)));

        $audio->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete audio successfully'
        );
    }
}
