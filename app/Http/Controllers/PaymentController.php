<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'patient', 'processor'])
            ->orderBy('payment_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->whereHas('patient', function($pq) use ($s) {
                    $pq->where('nom', 'like', "%$s%")
                       ->orWhere('prenom', 'like', "%$s%");
                })->orWhereHas('invoice', function($iq) use ($s) {
                    $iq->where('invoice_number', 'like', "%$s%");
                });
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id'     => 'required|exists:invoices,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,credit_card,bank_transfer,mobile_payment',
            'payment_date'   => 'required|date',
            'transaction_id' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice is already fully paid.'], 422);
        }

        if ($validated['amount'] > $invoice->remaining_amount) {
            return response()->json([
                'error' => 'Payment amount exceeds remaining balance of ' . $invoice->remaining_amount
            ], 422);
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'patient_id'     => $invoice->patient_id,
                'amount'         => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date'   => $validated['payment_date'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'status'         => 'paid',
                'notes'          => $validated['notes'] ?? null,
                'processed_by'   => auth()->id(),
            ]);

            // Update invoice paid/remaining amounts
            $invoice->paid_amount      += $validated['amount'];
            $invoice->remaining_amount -= $validated['amount'];

            if ($invoice->remaining_amount <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            DB::commit();
            return response()->json($payment->load(['invoice', 'patient', 'processor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Payment $payment)
    {
        return response()->json(
            $payment->load(['invoice.items', 'patient', 'processor'])
        );
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'payment_method' => 'sometimes|in:cash,credit_card,bank_transfer,mobile_payment',
            'notes'          => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        $payment->update($validated);
        return response()->json($payment->load(['invoice', 'patient']));
    }

    public function destroy(Payment $payment)
    {
        DB::beginTransaction();
        try {
            $invoice = $payment->invoice;
            $invoice->paid_amount      -= $payment->amount;
            $invoice->remaining_amount += $payment->amount;

            if ($invoice->paid_amount <= 0) {
                $invoice->status = 'unpaid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();
            $payment->delete();

            DB::commit();
            return response()->json(['message' => 'Payment deleted and invoice updated.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function receipt(Payment $payment)
    {
        $data = $payment->load(['invoice.items', 'patient', 'processor']);
        return response()->json($data);
    }
}
