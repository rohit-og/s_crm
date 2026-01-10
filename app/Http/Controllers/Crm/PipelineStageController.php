<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PipelineStageController extends BaseController
{
    public function index(Request $request, $pipelineId)
    {
        $pipeline = Pipeline::findOrFail($pipelineId);
        $this->authorizeForUser($request->user('api'), 'view', $pipeline);

        $stages = PipelineStage::where('pipeline_id', $pipelineId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['stages' => $stages]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Pipeline::class);

        $request->validate([
            'pipeline_id' => 'required|integer|exists:pipelines,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_default_stage' => 'boolean',
        ]);

        $pipeline = Pipeline::findOrFail($request->pipeline_id);
        
        // Get max sort_order for this pipeline
        $maxOrder = PipelineStage::where('pipeline_id', $request->pipeline_id)->max('sort_order') ?? 0;

        $data = $request->only(['pipeline_id', 'name', 'description', 'color', 'is_default_stage']);
        $data['sort_order'] = $maxOrder + 1;

        $stage = PipelineStage::create($data);

        return response()->json(['success' => true, 'stage' => $stage], 201);
    }

    public function show($id)
    {
        $stage = PipelineStage::with('pipeline', 'deals')->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $stage->pipeline);

        return response()->json($stage);
    }

    public function update(Request $request, $id)
    {
        $stage = PipelineStage::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $stage->pipeline);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_default_stage' => 'boolean',
        ]);

        $stage->update($request->only(['name', 'description', 'color', 'is_default_stage']));

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function destroy($id)
    {
        $stage = PipelineStage::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $stage->pipeline);

        $stage->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, $id)
    {
        $request->validate([
            'sort_order' => 'required|integer',
        ]);

        $stage = PipelineStage::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $stage->pipeline);

        $stage->update(['sort_order' => $request->sort_order]);

        return response()->json(['success' => true]);
    }
}
