<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\utils\helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', Deal::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $user = Auth::user();
        $helpers = new helpers;

        $deals = Deal::with(['client', 'pipeline', 'stage', 'assignedUser'])
            ->whereNull('deleted_at')
            ->when($request->filled('pipeline_id'), function ($q) use ($request) {
                return $q->where('pipeline_id', $request->pipeline_id);
            })
            ->when($request->filled('stage_id'), function ($q) use ($request) {
                return $q->where('pipeline_stage_id', $request->stage_id);
            })
            ->when($request->filled('assigned_to'), function ($q) use ($request) {
                return $q->where('assigned_to', $request->assigned_to);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhereHas('client', function ($q) use ($request) {
                            $q->where('name', 'LIKE', "%{$request->search}%");
                        });
                }
            });

        $totalRows = $deals->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $deals = $deals->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'deals' => $deals,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Deal::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|integer|exists:clients,id',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
            'pipeline_stage_id' => 'required|integer|exists:pipeline_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $data = $request->only([
            'name', 'description', 'client_id', 'pipeline_id', 'pipeline_stage_id',
            'value', 'currency', 'expected_close_date', 'probability', 'assigned_to'
        ]);
        $data['created_by'] = Auth::id();
        $data['status'] = $request->status ?? 'open';
        $data['value'] = $data['value'] ?? 0;
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['probability'] = $data['probability'] ?? 0;

        $deal = Deal::create($data);

        return response()->json(['success' => true, 'deal' => $deal->load(['client', 'pipeline', 'stage'])], 201);
    }

    public function show($id)
    {
        $deal = Deal::with(['client', 'pipeline', 'stage', 'assignedUser', 'followups', 'creator'])->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $deal);

        return response()->json($deal);
    }

    public function update(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $deal);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'client_id' => 'sometimes|required|integer|exists:clients,id',
            'pipeline_id' => 'sometimes|required|integer|exists:pipelines,id',
            'pipeline_stage_id' => 'sometimes|required|integer|exists:pipeline_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'expected_close_date' => 'nullable|date',
            'actual_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'status' => 'nullable|in:open,closed,won,lost',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $deal->update($request->only([
            'name', 'description', 'client_id', 'pipeline_id', 'pipeline_stage_id',
            'value', 'currency', 'expected_close_date', 'actual_close_date',
            'probability', 'status', 'assigned_to'
        ]));

        return response()->json(['success' => true, 'deal' => $deal->load(['client', 'pipeline', 'stage'])]);
    }

    public function destroy($id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $deal);

        $deal->delete();

        return response()->json(['success' => true]);
    }

    public function moveToStage(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $deal);

        $request->validate([
            'pipeline_stage_id' => 'required|integer|exists:pipeline_stages,id',
        ]);

        $stage = PipelineStage::findOrFail($request->pipeline_stage_id);
        $deal->update(['pipeline_stage_id' => $stage->id]);

        return response()->json(['success' => true, 'deal' => $deal->load('stage')]);
    }

    public function assign(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'assign', $deal);

        $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $deal->update(['assigned_to' => $request->assigned_to]);

        return response()->json(['success' => true, 'deal' => $deal->load('assignedUser')]);
    }

    public function byStage($stageId)
    {
        $this->authorizeForUser(request()->user('api'), 'viewAny', Deal::class);

        $deals = Deal::with(['client', 'assignedUser'])
            ->where('pipeline_stage_id', $stageId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['deals' => $deals]);
    }

    public function byAgent($userId)
    {
        $this->authorizeForUser(request()->user('api'), 'viewAny', Deal::class);

        $deals = Deal::with(['client', 'pipeline', 'stage'])
            ->where('assigned_to', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['deals' => $deals]);
    }
}
