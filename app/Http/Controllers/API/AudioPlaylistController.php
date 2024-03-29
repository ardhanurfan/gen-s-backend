<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\AudioPlaylist;
use Illuminate\Http\Request;

class AudioPlaylistController extends Controller
{
    public function add(Request $request) {
        $request->validate([
            'audioId' => 'required|integer|exists:audios,id',
            'playlistId' => 'required|integer|exists:playlists,id',
        ]);

        $audioPlaylist = AudioPlaylist::where('audioId', $request->audioId)->where('playlistId', $request->playlistId)->first();

        if ($audioPlaylist) {
            return ResponseFormatter::error(
                null,
                'Audio exist in playlist',
                500,
            );
        }

        $audioPlaylists = AudioPlaylist::where('playlistId', $request->playlistId)->orderBy('sequence', 'DESC')->first();
            
        if ($audioPlaylists) {
            $last = $audioPlaylists->sequence;
        } else {
            $last = 0;
        }

        $audioPlaylist = AudioPlaylist::create([
            'audioId' => $request->audioId,
            'playlistId' => $request->playlistId,
            'sequence' => $last+1,
        ]);

        return ResponseFormatter::success(
            $audioPlaylist,
            'Audio added to playlist'
        );
    }

    public function delete(Request $request) {
        $request->validate([
            'audioId' => 'required|integer',
            'playlistId' => 'required|integer',
        ]);

        $audioPlaylist = AudioPlaylist::where('audioId', $request->audioId)->where('playlistId', $request->playlistId)->first();

        if (!$audioPlaylist) {
            return ResponseFormatter::error(
                null,
                'Data not found',
                404
            );
        }

        $audioPlaylist->forceDelete();

        return ResponseFormatter::success(
            null,
            'Audio removed from playlist'
        );
    }

    public function swap(Request $request) {
        $request->validate([
            'playlistId' => 'required|integer',
        ]);

        foreach($request->input('audios', []) as $row)
        {
            AudioPlaylist::where('playlistId', $request->playlistId)->where('audioId', $row['audioId'])->update([
                'sequence' => $row['sequence']
            ]);

        }

        return ResponseFormatter::success(
            null,
            'Swap audio successfully'
        );
    }
}
