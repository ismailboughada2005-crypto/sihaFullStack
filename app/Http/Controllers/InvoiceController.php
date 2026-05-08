<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['patient', 'items', 'payments', 'insuranceClaim.insuranceCompany'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhereHas('patient', fn($p) => $p->where('nom', 'like', "%$search%")
                      ->orWhere('prenom', 'like', "%$search%"));
            });
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'due_date'          => 'required|date',
            'tax_rate'          => 'sometimes|numeric|min:0|max:100',
            'discount_amount'   => 'sometimes|numeric|min:0',
            'insurance_claim_id'=> 'nullable|exists:insurance_claims,id',
            'items'             => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.type'        => 'required|in:consultation,lab_test,medicine,other',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $taxRate  = $validated['tax_rate'] ?? 0;
            $taxAmount  = round($subtotal * $taxRate / 100, 2);
            $discount   = $validated['discount_amount'] ?? 0;
            $total      = $subtotal + $taxAmount - $discount;

            $invoice = Invoice::create([
                'patient_id'         => $validated['patient_id'],
                'invoice_number'     => 'INV-' . strtoupper(Str::random(8)),
                'subtotal'           => $subtotal,
                'tax_amount'         => $taxAmount,
                'discount_amount'    => $discount,
                'total_amount'       => $total,
                'paid_amount'        => 0,
                'remaining_amount'   => $total,
                'status'             => 'unpaid',
                'due_date'           => $validated['due_date'],
                'insurance_claim_id' => $validated['insurance_claim_id'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'description'  => $item['description'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['quantity'] * $item['unit_price'],
                    'type'         => $item['type'],
                ]);
            }

            DB::commit();
            return response()->json($invoice->load(['patient', 'items']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        return response()->json(
            $invoice->load(['patient', 'items', 'payments.refunds', 'insuranceClaim.insuranceCompany', 'creator'])
        );
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Cannot edit a fully paid invoice.'], 422);
        }

        $validated = $request->validate([
            'due_date'        => 'sometimes|date',
            'discount_amount' => 'sometimes|numeric|min:0',
            'tax_amount'      => 'sometimes|numeric|min:0',
        ]);

        $invoice->update($validated);

        // Recalculate total if tax/discount changed
        if (isset($validated['tax_amount']) || isset($validated['discount_amount'])) {
            $total = $invoice->subtotal + $invoice->tax_amount - $invoice->discount_amount;
            $invoice->total_amount    = $total;
            $invoice->remaining_amount = $total - $invoice->paid_amount;
            $invoice->save();
        }

        return response()->json($invoice->load(['patient', 'items']));
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->paid_amount > 0) {
            return response()->json(['error' => 'Cannot delete an invoice with recorded payments.'], 422);
        }
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    public function patientHistory(Patient $patient)
    {
        $invoices = Invoice::with(['items', 'payments'])
            ->where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_billed'    => $invoices->sum('total_amount'),
            'total_paid'      => $invoices->sum('paid_amount'),
            'total_remaining' => $invoices->sum('remaining_amount'),
            'invoice_count'   => $invoices->count(),
        ];

        return response()->json(['invoices' => $invoices, 'summary' => $summary]);
    }
}
