<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Faker\Core\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;


class GalleryController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');


        if ($id) {
            $gallery = Gallery::with(['images'])->find($id);

            if ($gallery) {
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

        if ($name) {
            $galleries->where('name', 'like', '%' . $name . '%');
        }

        $galleries = $galleries->orderBy('name', 'DESC')->get();
        $tree = $this->buildTree($galleries);

        return ResponseFormatter::success(
            $tree,
            'Get galleries data successfully'
        );
    }

    protected function buildTree(Collection $elements, $parentId = 0)
    {
        $branch = collect();

        foreach ($elements as $element) {
            if ($element->parentId == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                $element->setAttribute('children', $children->isNotEmpty() ? $children : []);
                $branch->push($element);
            }
        }

        return $branch;
    }

    public function create(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:galleries,name',
                'parentId' => 'sometimes|exists:galleries,id',
            ]);

            $gallery = Gallery::create([
                'name' => $request->name,
                'parentId' => $request->parentId ? $request->parentId : 0,
            ]);

            return ResponseFormatter::success(
                $gallery->load('images'),
                'Create gallery successfully'
            );
        } catch (ValidationException $error) {
            return ResponseFormatter::error(
                [
                    'message' => 'Something when wrong',
                    'error' => array_values($error->errors())[0][0],
                ],
                'Create gallery failed',
                500,
            );
        }
    }

    public function delete(Request $request)
    {
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
        foreach ($gallery->images as $image) {
            unlink(public_path(str_replace(config('app.url'), '', $image['url'])));
        }

        $gallery->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete gallery successfully'
        );
    }

    public function rename(Request $request)
    {
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
