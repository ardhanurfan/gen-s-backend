<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Type\Integer;

class AdsController extends Controller
{
    public function all(Request $request) {
        $location = $request->input('location');

        $ads = Ads::where('location', $location);

        return ResponseFormatter::success(
            $ads->get(),
            'Get ads data successfully'
        );
    }

    public function add(Request $request) {
        try {
            $request->validate([
            'frequency' => 'required',
            'link' => 'required|string',
            'title' => 'required|string',
            'adsFile' => 'required',
            'location' => 'required|string',
            ]);

            $adsFile = $request->file('adsFile');
            $adsPath = $adsFile->storeAs('public/ads', 'ads_'.uniqid().'.'.$adsFile->extension());

            // masukkan ke tabel ads
            $ads = Ads::create([
                'title' => $request->title,
                'frequency' => (int)$request->frequency,
                'url' => $adsPath,
                'link' => $request->link,
                'location' => $request->location,
            ]);

            return ResponseFormatter::success(
                $ads,
                'Add ads successfully'
            );
        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Create ads failed', 
                500,
            );
        } 
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $ads = Ads::find($request->id);

        if (!$ads) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        // delete ads from storage
        unlink(public_path(str_replace(config('app.url'),'',$ads->url)));

        $ads->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete ads successfully'
        );
    }

    public function edit(Request $request) {
        $request->validate([
            'id' => 'required|integer',
            'frequency' => 'required|integer',
            'link' => 'required|string',
            'title' => 'required|string',
            'location' => 'required|string',
        ]);

        $ads = Ads::find($request->id);

        if (!$ads) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        $ads->update([
            'frequency' => $request->frequency,
            'link' => $request->link,
            'title' => $request->title,
            'location' => $request->location,
        ]);

        return ResponseFormatter::success(
            $ads,
            'Edit ads successfully'
        );
    }

}
