<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\CrmForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormBuilderController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'viewAny', CrmForm::class);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $forms = CrmForm::whereNull('deleted_at')
            ->when($request->filled('is_active'), function ($q) use ($request) {
                return $q->where('is_active', $request->is_active);
            })
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('description', 'LIKE', "%{$request->search}%");
                }
            });

        $totalRows = $forms->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $forms = $forms->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->withCount('submissions')
            ->get();

        return response()->json([
            'forms' => $forms,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', CrmForm::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_fields' => 'nullable|array',
            'submit_button_text' => 'nullable|string|max:100',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $data = $request->only([
            'name', 'description', 'form_fields', 'submit_button_text',
            'success_message', 'redirect_url', 'is_active'
        ]);
        $data['created_by'] = Auth::id();
        $data['is_active'] = $data['is_active'] ?? true;
        $data['submit_button_text'] = $data['submit_button_text'] ?? 'Submit';

        $form = CrmForm::create($data);

        return response()->json(['success' => true, 'form' => $form], 201);
    }

    public function show($id)
    {
        $form = CrmForm::withCount('submissions')->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $form);

        return response()->json($form);
    }

    public function update(Request $request, $id)
    {
        $form = CrmForm::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $form);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'form_fields' => 'nullable|array',
            'submit_button_text' => 'nullable|string|max:100',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $form->update($request->only([
            'name', 'description', 'form_fields', 'submit_button_text',
            'success_message', 'redirect_url', 'is_active'
        ]));

        return response()->json(['success' => true, 'form' => $form]);
    }

    public function destroy($id)
    {
        $form = CrmForm::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $form);

        $form->delete();

        return response()->json(['success' => true]);
    }

    public function publish(Request $request, $id)
    {
        $form = CrmForm::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'update', $form);

        $form->update(['is_active' => true]);

        return response()->json(['success' => true, 'form' => $form]);
    }

    public function duplicate($id)
    {
        $form = CrmForm::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $form);

        $newForm = $form->replicate();
        $newForm->name = $form->name . ' (Copy)';
        $newForm->is_active = false;
        $newForm->created_by = Auth::id();
        $newForm->save();

        return response()->json(['success' => true, 'form' => $newForm], 201);
    }
}
