<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Pipeline;
use App\utils\helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PipelineController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', Pipeline::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'sort_order';
        $dir = $request->SortType ?? 'asc';

        $pipelines = Pipeline::with('stages')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('description', 'LIKE', "%{$request->search}%");
                }
            });

        $totalRows = $pipelines->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $pipelines = $pipelines->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'pipelines' => $pipelines,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Pipeline::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $data = $request->only(['name', 'description', 'color', 'is_default']);
        $data['created_by'] = Auth::id();
        
        // If this is set as default, unset others
        if ($request->is_default) {
            Pipeline::where('is_default', true)->update(['is_default' => false]);
        }

        $pipeline = Pipeline::create($data);

        return response()->json(['success' => true, 'pipeline' => $pipeline], 201);
    }

    public function show($id)
    {
        $pipeline = Pipeline::with(['stages.deals', 'deals.client', 'deals.assignedUser'])->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $pipeline);

        return response()->json($pipeline);
    }

    public function update(Request $request, $id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $pipeline);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $data = $request->only(['name', 'description', 'color', 'is_default']);
        
        if (isset($request->is_default) && $request->is_default) {
            Pipeline::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        $pipeline->update($data);

        return response()->json(['success' => true, 'pipeline' => $pipeline]);
    }

    public function destroy($id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $pipeline);

        $pipeline->delete();

        return response()->json(['success' => true]);
    }

    public function reorderStages(Request $request, $id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $pipeline);

        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|integer|exists:pipeline_stages,id',
            'stages.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->stages as $stage) {
            \DB::table('pipeline_stages')
                ->where('id', $stage['id'])
                ->where('pipeline_id', $id)
                ->update(['sort_order' => $stage['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
