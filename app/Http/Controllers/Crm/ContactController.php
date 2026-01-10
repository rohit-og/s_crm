<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\ContactGroup;
use App\Models\Tag;
use App\Models\User;
use App\utils\helpers;
use Illuminate\Http\Request;

class ContactController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Client::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $helpers = new helpers;
        $columns = [0 => 'name', 1 => 'email', 2 => 'phone', 3 => 'city', 4 => 'assigned_agent_id'];
        $param = [0 => 'like', 1 => 'like', 2 => 'like', 3 => 'like', 4 => '='];

        $clients = Client::with(['assignedAgent', 'contactGroups', 'tags', 'deals'])
            ->whereNull('deleted_at');

        // Filter by contact group
        if ($request->filled('contact_group_id')) {
            $clients->whereHas('contactGroups', function ($q) use ($request) {
                $q->where('contact_groups.id', $request->contact_group_id);
            });
        }

        // Filter by tag
        if ($request->filled('tag_id')) {
            $clients->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag_id);
            });
        }

        // Filter by assigned agent
        if ($request->filled('assigned_agent_id')) {
            $clients->where('assigned_agent_id', $request->assigned_agent_id);
        }

        $Filtred = $helpers->filter($clients, $columns, $param, $request)
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%")
                        ->orWhere('phone', 'LIKE', "%{$request->search}%")
                        ->orWhere('company_name', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $Filtred->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $clients = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'contacts' => $clients,
            'totalRows' => $totalRows,
        ]);
    }

    public function show($id)
    {
        $contact = Client::with([
            'assignedAgent', 'contactGroups', 'tags', 'deals.pipeline', 'deals.stage',
            'followups', 'customFieldValues'
        ])->findOrFail($id);
        
        $this->authorizeForUser(request()->user('api'), 'view', $contact);

        return response()->json($contact);
    }

    public function update(Request $request, $id)
    {
        $contact = Client::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $contact);

        $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'assigned_agent_id' => 'nullable|integer|exists:users,id',
        ]);

        $contact->update($request->only([
            'name', 'email', 'phone', 'company_name', 'job_title',
            'source', 'assigned_agent_id', 'country', 'city', 'adresse'
        ]));

        return response()->json(['success' => true, 'contact' => $contact->load(['assignedAgent', 'contactGroups', 'tags'])]);
    }

    public function assignAgent(Request $request, $id)
    {
        $contact = Client::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $contact);

        $request->validate([
            'assigned_agent_id' => 'nullable|integer|exists:users,id',
        ]);

        $contact->update(['assigned_agent_id' => $request->assigned_agent_id]);

        return response()->json([
            'success' => true,
            'contact' => $contact->load('assignedAgent')
        ]);
    }

    public function addToGroup(Request $request, $id)
    {
        $contact = Client::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $contact);

        $request->validate([
            'contact_group_id' => 'required|integer|exists:contact_groups,id',
        ]);

        $contact->contactGroups()->syncWithoutDetaching([$request->contact_group_id]);

        return response()->json([
            'success' => true,
            'contact' => $contact->load('contactGroups')
        ]);
    }

    public function addTags(Request $request, $id)
    {
        $contact = Client::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $contact);

        $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $contact->tags()->syncWithoutDetaching($request->tag_ids);

        return response()->json([
            'success' => true,
            'contact' => $contact->load('tags')
        ]);
    }
}
