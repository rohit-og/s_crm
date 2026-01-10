<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactGroupController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', ContactGroup::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $groups = ContactGroup::withCount('clients')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('description', 'LIKE', "%{$request->search}%");
                }
            });

        $totalRows = $groups->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $groups = $groups->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'groups' => $groups,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', ContactGroup::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $data = $request->only(['name', 'description', 'color']);
        $data['created_by'] = Auth::id();
        $data['color'] = $data['color'] ?? '#6c5ce7';

        $group = ContactGroup::create($data);

        return response()->json(['success' => true, 'group' => $group], 201);
    }

    public function show($id)
    {
        $group = ContactGroup::with('clients')->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $group);

        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $group = ContactGroup::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $group);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $group->update($request->only(['name', 'description', 'color']));

        return response()->json(['success' => true, 'group' => $group]);
    }

    public function destroy($id)
    {
        $group = ContactGroup::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $group);

        $group->delete();

        return response()->json(['success' => true]);
    }

    public function addContacts(Request $request, $id)
    {
        $group = ContactGroup::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $group);

        $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'integer|exists:clients,id',
        ]);

        $group->clients()->syncWithoutDetaching($request->client_ids);

        return response()->json(['success' => true, 'group' => $group->load('clients')]);
    }

    public function removeContacts(Request $request, $id)
    {
        $group = ContactGroup::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $group);

        $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'integer|exists:clients,id',
        ]);

        $group->clients()->detach($request->client_ids);

        return response()->json(['success' => true, 'group' => $group->load('clients')]);
    }
}
