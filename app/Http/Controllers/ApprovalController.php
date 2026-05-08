<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalRequest;
use App\Models\RequestPoint;
use App\Models\Point;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Mail\VoteRequestMail;
use Illuminate\Support\Facades\Mail;
class ApprovalController extends Controller
{


    public function getRequest($id)
    {
        try {
            $request = ApprovalRequest::with(['route', 'requestPoints.point'])->find($id);

            if (!$request) {
                return response()->json(['success' => false, 'message' => 'Заявка не найдена'], 404);
            }

            return response()->json(['success' => true, 'data' => $request]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }


    public function deleteRequest($id)
    {
        $request = ApprovalRequest::findOrFail($id);
        $request->delete();
        return response()->json(['success' => true]);
    }

    // 5. Голосование
    public function vote(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:confirmed,rejected',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $requestPoint = RequestPoint::where('token', $token)->firstOrFail();
        $approvalRequest = $requestPoint->request;

        if (Carbon::now()->gt($approvalRequest->deadline)) {
            return response()->json(['success' => false, 'message' => 'Дедлайн истёк'], 403);
        }

        if ($requestPoint->status !== 'waiting') {
            return response()->json(['success' => false, 'message' => 'Уже проголосовали'], 403);
        }

        $requestPoint->update([
            'status' => $request->status,
            'comment' => $request->comment,
            'voted_at' => Carbon::now(),
        ]);

        // Пересчитываем статус заявки
        $this->recalculateRequestStatus($approvalRequest);

        return response()->json(['success' => true]);
    }

    private function recalculateRequestStatus(ApprovalRequest $approvalRequest): void
    {
        $points = $approvalRequest->requestPoints;
        $total = $points->count();
        $rejected = $points->where('status', 'rejected')->count();
        $waiting = $points->where('status', 'waiting')->count();

        if ($rejected >= 2) {
            $approvalRequest->update(['status' => 'cancelled']);
            return;
        }

        if ($waiting === 0) {
            $approvalRequest->update(['status' => 'approved']);
            return;
        }

        // Последние оставшиеся waiting не могут изменить исход
        // (даже если все оставшиеся откажут, будет меньше 2 отказов)

        $confirmed = $points->where('status', 'confirmed')->count();
        $maxPossibleRejected = $rejected + $waiting;

        if ($waiting === 0 || $maxPossibleRejected < 2) {
            $approvalRequest->update(['status' => 'approved']);
            $this->createRouteOrder($approvalRequest);
            return;
        }
        if ($maxPossibleRejected < 2) {
            // Даже если все оставшиеся откажут - не хватит до 2, одобряем
            $approvalRequest->update(['status' => 'approved']);
        }
    }
    private function createRouteOrder(ApprovalRequest $approvalRequest): void
    {

        $approvalRequest->loadMissing('route');
        $route = $approvalRequest->route;
        $date = Carbon::parse(
            $approvalRequest->proposed_date->format('Y-m-d') . ' ' . $approvalRequest->start_time
        );
        \App\Models\routeOrder::create([
            'route_id' => $approvalRequest->route_id,
            'max_users' => $route->participants,
            'ordered_users' => 0,
            'date' => $date,
        ]);
    }


}