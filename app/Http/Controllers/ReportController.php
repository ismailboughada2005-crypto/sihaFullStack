<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Dashboard summary statistics.
     */
    public function dashboard()
    {
        $today    = now()->toDateString();
        $month    = now()->month;
        $year     = now()->year;

        $dailyRevenue = Payment::whereDate('payment_date', $today)
            ->where('status', 'paid')
            ->sum('amount');

        $monthlyRevenue = Payment::whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->where('status', 'paid')
            ->sum('amount');

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');

        $pendingInvoices = Invoice::whereIn('status', ['unpaid', 'partially_paid'])->count();
        $pendingAmount   = Invoice::whereIn('status', ['unpaid', 'partially_paid'])->sum('remaining_amount');


        return response()->json([
            'daily_revenue'    => round($dailyRevenue, 2),
            'monthly_revenue'  => round($monthlyRevenue, 2),
            'total_revenue'    => round($totalRevenue, 2),
            'pending_invoices' => $pendingInvoices,
            'pending_amount'   => round($pendingAmount, 2),
        ]);
    }

    /**
     * Monthly revenue breakdown for chart display.
     */
    public function monthlyRevenue(Request $request)
    {
        $year = $request->input('year', now()->year);

        $data = Payment::selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->whereYear('payment_date', $year)
            ->where('status', 'paid')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[] = [
                'month'   => date('F', mktime(0, 0, 0, $m, 1)),
                'month_num' => $m,
                'revenue' => isset($data[$m]) ? round($data[$m]->total, 2) : 0,
            ];
        }

        return response()->json($result);
    }

    /**
     * Revenue breakdown per payment method.
     */
    public function paymentMethodBreakdown()
    {
        $data = Payment::selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->where('status', 'paid')
            ->groupBy('payment_method')
            ->get();

        return response()->json($data);
    }

    /**
     * Top patients by total paid amount.
     */
    public function topPatients(Request $request)
    {
        $limit = $request->input('limit', 10);

        $data = Payment::selectRaw('patient_id, SUM(amount) as total_paid, COUNT(*) as payment_count')
            ->with('patient')
            ->where('status', 'paid')
            ->groupBy('patient_id')
            ->orderByDesc('total_paid')
            ->limit($limit)
            ->get();

        return response()->json($data);
    }

    /**
     * Daily revenue for a date range.
     */
    public function dailyRevenue(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $data = Payment::selectRaw('DATE(payment_date) as date, SUM(amount) as total')
            ->where('status', 'paid')
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }
}
