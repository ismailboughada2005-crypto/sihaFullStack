<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = InsuranceClaim::with(['patient', 'insuranceCompany'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'           => 'required|exists:patients,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_number'        => 'required|string',
            'claimed_amount'       => 'required|numeric|min:0',
            'notes'                => 'nullable|string',
        ]);

        $claim = InsuranceClaim::create(array_merge($validated, [
            'approved_amount' => 0,
            'status'          => 'pending',
        ]));

        return response()->json($claim->load(['patient', 'insuranceCompany']), 201);
    }

    public function show(InsuranceClaim $insuranceClaim)
    {
        return response()->json(
            $insuranceClaim->load(['patient', 'insuranceCompany', 'invoices'])
        );
    }

    public function update(Request $request, InsuranceClaim $insuranceClaim)
    {
        $validated = $request->validate([
            'status'          => 'sometimes|in:pending,approved,rejected,partially_approved',
            'approved_amount' => 'sometimes|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $insuranceClaim->update($validated);
        return response()->json($insuranceClaim->load(['patient', 'insuranceCompany']));
    }

    public function destroy(InsuranceClaim $insuranceClaim)
    {
        if ($insuranceClaim->invoices()->exists()) {
            return response()->json(['error' => 'Cannot delete a claim linked to invoices.'], 422);
        }
        $insuranceClaim->delete();
        return response()->json(['message' => 'Insurance claim deleted.']);
    }

    // List all insurance companies (returns plain array, not paginated)
    public function companies()
    {
        return response()->json(InsuranceCompany::all());
    }

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'name'                        => 'required|string',
            'contact_person'              => 'nullable|string',
            'phone'                       => 'nullable|string',
            'email'                       => 'nullable|email',
            'default_coverage_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $company = InsuranceCompany::create($validated);
        return response()->json($company, 201);
    }
}
