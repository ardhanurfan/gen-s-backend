<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PlaylistController extends Controller
{
    public function all(Request $request) {
        $id = $request->input('id');
        $name = $request->input('name');

        if($id) 
        {
            $playlist = Playlist::with(['audios.images'])->find($id);

            if($playlist){
                return ResponseFormatter::success(
                    $playlist, 
                    'Get playlist data successfully'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data not found',
                    404
                );
            }
        }

        $playlists = Playlist::with(['audios.images'])->where('userId', Auth::user()->id);

        if($name) {
            $playlists->where('title', 'like', '%' . $name . '%');
        }

        return ResponseFormatter::success(
            $playlists->orderBy('sequence')->get(),
            'Get playlists data successfully'
        );
    }

    public function add(Request $request) {
        try {
            $request->validate([
                'name' => 'required|string|unique:playlists,name',
            ]);

            $playlists = Playlist::where('userId', Auth::user()->id)->orderBy('sequence', 'DESC')->first();
            
            if ($playlists) {
                $last = $playlists->sequence;
            } else {
                $last = 0;
            }

            $playlist = Playlist::create([
                'name' => $request->name,
                'userId' => Auth::user()->id,
                'sequence' => $last+1,
            ]);

            return ResponseFormatter::success(
                $playlist->load('audios.images'),
                'Create playlist successfully'
            );

        } catch (ValidationException $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => array_values($error->errors())[0][0],    
            ], 
                'Create playlists failed', 
                500,
            );
        }
    }

    public function delete(Request $request) {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $playlist = Playlist::find($request->id);

        if (!$playlist) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        $playlist->forceDelete();

        return ResponseFormatter::success(
            null,
            'Delete playlist successfully'
        );
    }

    public function swap(Request $request) {
        foreach($request->input('playlists', []) as $row)
        {
            Playlist::find($row['id'])->update([
                'sequence' => $row['sequence']
            ]);
        }

        return ResponseFormatter::success(
            null,
            'Swap playlist successfully'
        );
    }
}
