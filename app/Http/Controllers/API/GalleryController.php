<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Faker\Core\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    public function all(Request $request) {
        $id = $request->input('id');
        $name = $request->input('name');

        
        if($id) 
        {
            $gallery = Gallery::with(['images'])->find($id);

            if($gallery){
                return ResponseFormatter::success(
                    $gallery, 
                    'Get gallery data successfully'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data not found',
                    404
                );
            }
        }

        $galleries = Gallery::with(['images']);

        if($name) {
            $galleries->where('name', 'like', '%' . $name . '%');
        }

        return ResponseFormatter::success(
            $galleries->orderBy('name', 'DESC')->get(),
            'Get galleries data successfully'
        );
    }

    public function create(Request $request) {
        try {
            $request->validate([
                'name' => 'required|string|unique:galleries,name',
            ]);

            $gallery = Gallery::create([
                'name' => $request->name,
            ]);

            return ResponseFormatter::success(
                $gallery->load('images'),
                'Create gallery successfully'
            );
        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Create gallery failed', 
                500,
            );
        }
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $gallery = Gallery::with(['images'])->find($request->id);

        if (!$gallery) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        // delete images from storage
        foreach($gallery->images as $image) {
            unlink(public_path(str_replace(config('app.url'),'',$image['url'])));
        }

        $gallery->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete gallery successfully'
        );
    }

    public function rename(Request $request) {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string'
        ]);

        $playlist = Gallery::find($request->id);

        if (!$playlist) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        $playlist->update([
            'name' => $request->name
        ]);

        return ResponseFormatter::success(
            $playlist->load('images'),
            'Rename gallery successfully'
        );
    }
}