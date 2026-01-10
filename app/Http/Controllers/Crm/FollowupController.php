<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Followup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowupController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', Followup::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'scheduled_at';
        $dir = $request->SortType ?? 'asc';

        $followups = Followup::with(['deal', 'client', 'assignedUser'])
            ->whereNull('deleted_at')
            ->when($request->filled('deal_id'), function ($q) use ($request) {
                return $q->where('deal_id', $request->deal_id);
            })
            ->when($request->filled('client_id'), function ($q) use ($request) {
                return $q->where('client_id', $request->client_id);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->filled('assigned_to'), function ($q) use ($request) {
                return $q->where('assigned_to', $request->assigned_to);
            })
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('subject', 'LIKE', "%{$request->search}%")
                        ->orWhere('description', 'LIKE', "%{$request->search}%");
                }
            });

        $totalRows = $followups->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $followups = $followups->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'followups' => $followups,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Followup::class);

        $request->validate([
            'deal_id' => 'nullable|integer|exists:deals,id',
            'client_id' => 'required|integer|exists:clients,id',
            'type' => 'required|in:call,meeting,email,task,note',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'reminder_at' => 'nullable|date',
        ]);

        $data = $request->only([
            'deal_id', 'client_id', 'type', 'subject', 'description',
            'scheduled_at', 'assigned_to', 'reminder_at'
        ]);
        $data['created_by'] = Auth::id();
        $data['status'] = $request->status ?? 'scheduled';

        $followup = Followup::create($data);

        return response()->json(['success' => true, 'followup' => $followup->load(['deal', 'client', 'assignedUser'])], 201);
    }

    public function show($id)
    {
        $followup = Followup::with(['deal', 'client', 'assignedUser', 'creator'])->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $followup);

        return response()->json($followup);
    }

    public function update(Request $request, $id)
    {
        $followup = Followup::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $followup);

        $request->validate([
            'deal_id' => 'nullable|integer|exists:deals,id',
            'client_id' => 'sometimes|required|integer|exists:clients,id',
            'type' => 'sometimes|required|in:call,meeting,email,task,note',
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'reminder_at' => 'nullable|date',
        ]);

        $followup->update($request->only([
            'deal_id', 'client_id', 'type', 'subject', 'description',
            'scheduled_at', 'completed_at', 'status', 'assigned_to', 'reminder_at'
        ]));

        return response()->json(['success' => true, 'followup' => $followup->load(['deal', 'client', 'assignedUser'])]);
    }

    public function destroy($id)
    {
        $followup = Followup::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $followup);

        $followup->delete();

        return response()->json(['success' => true]);
    }

    public function markComplete(Request $request, $id)
    {
        $followup = Followup::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $followup);

        $followup->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'followup' => $followup]);
    }

    public function scheduled(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', Followup::class);

        $followups = Followup::with(['deal', 'client', 'assignedUser'])
            ->where('status', 'scheduled')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($request) {
                if ($request->filled('from')) {
                    $q->where('scheduled_at', '>=', $request->from);
                }
                if ($request->filled('to')) {
                    $q->where('scheduled_at', '<=', $request->to);
                }
                if ($request->filled('assigned_to')) {
                    $q->where('assigned_to', $request->assigned_to);
                }
            })
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json(['followups' => $followups]);
    }

    public function byDeal($dealId)
    {
        $this->authorizeForUser(request()->user('api'), 'viewAny', Followup::class);

        $followups = Followup::with(['client', 'assignedUser'])
            ->where('deal_id', $dealId)
            ->whereNull('deleted_at')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return response()->json(['followups' => $followups]);
    }

    public function byClient($clientId)
    {
        $this->authorizeForUser(request()->user('api'), 'viewAny', Followup::class);

        $followups = Followup::with(['deal', 'assignedUser'])
            ->where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return response()->json(['followups' => $followups]);
    }
}
