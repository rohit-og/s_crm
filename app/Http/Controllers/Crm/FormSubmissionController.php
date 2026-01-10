<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\CrmForm;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormSubmissionController extends BaseController
{
    public function index(Request $request, $formId)
    {
        $form = CrmForm::findOrFail($formId);
        $this->authorizeForUser($request->user('api'), 'view', $form);

        $perPage = $request->limit ?? 10;
        $pageStart = $request->get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'submitted_at';
        $dir = $request->SortType ?? 'desc';

        $submissions = FormSubmission::with('client')
            ->where('form_id', $formId)
            ->where(function ($query) use ($request) {
                if ($request->filled('search')) {
                    return $query->whereRaw('JSON_EXTRACT(data, "$") LIKE ?', ["%{$request->search}%"]);
                }
            });

        $totalRows = $submissions->count();
        if ($perPage == '-1') {
            $perPage = $totalRows ?: 1;
        }

        $submissions = $submissions->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'submissions' => $submissions,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request, $formId)
    {
        // Public endpoint - no auth required for form submissions
        $form = CrmForm::where('id', $formId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $request->validate([
            'data' => 'required|array',
        ]);

        // Validate form fields based on form_fields JSON structure
        if ($form->form_fields) {
            foreach ($form->form_fields as $field) {
                if (isset($field['required']) && $field['required']) {
                    $request->validate([
                        "data.{$field['name']}" => 'required',
                    ]);
                }
            }
        }

        $submission = FormSubmission::create([
            'form_id' => $formId,
            'data' => $request->data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        // Try to match to existing client by email or phone
        if (isset($request->data['email']) || isset($request->data['phone'])) {
            $client = Client::where(function ($q) use ($request) {
                if (isset($request->data['email'])) {
                    $q->where('email', $request->data['email']);
                }
                if (isset($request->data['phone'])) {
                    $q->orWhere('phone', $request->data['phone']);
                }
            })->first();

            if ($client) {
                $submission->update(['client_id' => $client->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $form->success_message ?? 'Form submitted successfully',
        ], 201);
    }

    public function show($id)
    {
        $submission = FormSubmission::with(['form', 'client'])->findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'view', $submission->form);

        return response()->json($submission);
    }

    public function destroy($id)
    {
        $submission = FormSubmission::findOrFail($id);
        $this->authorizeForUser(request()->user('api'), 'delete', $submission->form);

        $submission->delete();

        return response()->json(['success' => true]);
    }

    public function matchToContact(Request $request, $id)
    {
        $submission = FormSubmission::findOrFail($id);
        $this->authorizeForUser($request->user('api'), 'view', $submission->form);

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $submission->update(['client_id' => $request->client_id]);

        return response()->json(['success' => true, 'submission' => $submission->load('client')]);
    }
}
