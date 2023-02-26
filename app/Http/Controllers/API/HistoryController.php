<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function all(Request $request) {
        $limit = $request->input('limit', 30);
        $menu = $request->input('menu');

        $history = History::with(['audio.images'])->where('userId', Auth::user()->id);

        if ($menu == 'MOST') {
            return ResponseFormatter::success(
                $history->orderBy('count', 'DESC')->paginate($limit),
                'Get history data successfully'
            );
        }

        return ResponseFormatter::success(
            $history->orderBy('updated_at', 'DESC')->paginate($limit),
            'Get history data successfully'
        );
    }

    public function add(Request $request) {
        $request->validate([
            'audioId' => 'required|integer|exists:audios,id',
        ]);

        $history = History::where([['userId', Auth::user()->id], ['audioId', $request->audioId]])->first();

        if ($history) {
            $history->update([
                'count' => $history->count+1,
            ]);
        } else {
            $history = History::create([
                'userId' => Auth::user()->id,
                'audioId' => $request->audioId,
                'count' => 1,
            ]);
        }
       
        return ResponseFormatter::success(
            $history->load('audio.images'),
            'History updated'
        );
    }
}
