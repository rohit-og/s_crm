<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', Tag::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'name';
        $dir = $request->SortType ?? 'asc';

        $tags = Tag::withCount('clients')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('name', 'LIKE', "%{$request->search}%");
                }
            });

        $totalRows = $tags->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $tags = $tags->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'tags' => $tags,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Tag::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'color' => 'nullable|string|max:20',
        ]);

        $data = $request->only(['name', 'color']);
        $data['created_by'] = Auth::id();
        $data['color'] = $data['color'] ?? '#6c5ce7';

        $tag = Tag::create($data);

        return response()->json(['success' => true, 'tag' => $tag], 201);
    }

    public function show($id)
    {
        $tag = Tag::with('clients')->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $tag);

        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $tag);

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:tags,name,' . $id,
            'color' => 'nullable|string|max:20',
        ]);

        $tag->update($request->only(['name', 'color']));

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $tag);

        $tag->delete();

        return response()->json(['success' => true]);
    }

    public function attachToContact(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $tag);

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $client = Client::findOrFail($request->client_id);
        $tag->clients()->syncWithoutDetaching([$client->id]);

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function detachFromContact(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $tag);

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $tag->clients()->detach($request->client_id);

        return response()->json(['success' => true, 'tag' => $tag]);
    }
}
