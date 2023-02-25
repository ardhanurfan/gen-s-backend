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
                $history->sortBy('count')->paginate($limit),
                'Get history data successfully'
            );
        }

        return ResponseFormatter::success(
            $history->sortBy('updated_at')->paginate($limit),
            'Get history data successfully'
        );
    }
}
