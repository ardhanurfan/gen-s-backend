<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function add(Request $request) {
        $request->validate([
            'audioId' => 'integer',
            'galleryId' => 'integer',
            'title' =>'string'
        ]);

        $imageFile = $request->file('imageFile');
        $imagePath = $imageFile->storeAs('public/images', $request->title);

        // cek galleries ada root belum
        $root = Gallery::where('name', 'root')->first();
        if (!$root) {
            $root = Gallery::create([
                'name' => 'root',
            ]);
        }

        $image = Image::create([
            'url' => $imagePath,
            'audioId' => $request->audioId,
            'galleryId' => $request->galleryId ?? $root->id,
        ]);

        return ResponseFormatter::success(
            $image,
            'Add image successfully'
        );
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $image = Image::find($request->id);

        if (!$image) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        unlink(public_path(str_replace(config('app.url'),'',$image->url)));
        $image->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete image successfully'
        );
    }

    public function move(Request $request) {
        $request->validate([
            'id' => 'required|integer',
            'toGalleryId' => 'required|integer',
        ]);

        $image = Image::find($request->id);

        if (!$image) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        $image->update([
            'galleryId' => $request->toGalleryId,
        ]);

        return ResponseFormatter::success(
            $image,
            'Move image successfully'
        );
    }
}
