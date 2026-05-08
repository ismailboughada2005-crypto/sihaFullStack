<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['payment.patient', 'payment.invoice', 'processor'])
            ->orderBy('refund_date', 'desc');

        if ($request->filled('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id'  => 'required|exists:payments,id',
            'amount'      => 'required|numeric|min:0.01',
            'reason'      => 'required|string',
            'refund_date' => 'required|date',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        // Calculate how much has already been refunded for this payment
        $alreadyRefunded = $payment->refunds()->sum('amount');
        $availableForRefund = $payment->amount - $alreadyRefunded;

        if ($validated['amount'] > $availableForRefund) {
            return response()->json([
                'error' => "Refund amount exceeds available balance. Available: {$availableForRefund}"
            ], 422);
        }

        DB::beginTransaction();
        try {
            $refund = Refund::create([
                'payment_id'  => $payment->id,
                'amount'      => $validated['amount'],
                'reason'      => $validated['reason'],
                'refund_date' => $validated['refund_date'],
                'processed_by' => auth()->id(),
            ]);

            // Update payment status if fully refunded
            $totalRefunded = $alreadyRefunded + $validated['amount'];
            if ($totalRefunded >= $payment->amount) {
                $payment->status = 'refunded';
                $payment->save();
            }

            // Update the invoice paid/remaining amounts
            $invoice = $payment->invoice;
            $invoice->paid_amount      -= $validated['amount'];
            $invoice->remaining_amount += $validated['amount'];

            if ($invoice->paid_amount <= 0) {
                $invoice->status = 'unpaid';
            } elseif ($invoice->remaining_amount > 0) {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            DB::commit();
            return response()->json($refund->load(['payment.patient', 'processor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Refund $refund)
    {
        return response()->json(
            $refund->load(['payment.invoice.items', 'payment.patient', 'processor'])
        );
    }

    public function destroy(Refund $refund)
    {
        // Reverse the refund effect on invoice
        DB::beginTransaction();
        try {
            $payment = $refund->payment;
            $invoice = $payment->invoice;

            $invoice->paid_amount      += $refund->amount;
            $invoice->remaining_amount -= $refund->amount;

            if ($invoice->remaining_amount <= 0) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            // Restore payment status if it was refunded
            if ($payment->status === 'refunded') {
                $payment->status = 'paid';
                $payment->save();
            }

            $refund->delete();

            DB::commit();
            return response()->json(['message' => 'Refund reversed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
