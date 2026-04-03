<?php

namespace App\Http\Controllers;

use App\Exports\FinancialReportExport;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    public function export(Request $request)
    {
        // Ambil parameter tanggal dari request
        $startDateParam = $request->query('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDateParam = $request->query('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $start = Carbon::parse($startDateParam)->startOfDay();
        $end = Carbon::parse($endDateParam)->endOfDay();

        $income = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $expenses = Expense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->get();

        $totalIncome = $income->sum('grand_total');
        $totalExpense = $expenses->sum('amount');

        $summary = [
            'startDate' => Carbon::parse($startDateParam)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDateParam)->format('d/m/Y'),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];

        $fileName = 'Laporan-Keuangan-' . $start->format('d-m-Y') . '-sd-' . $end->format('d-m-Y') . '.xlsx';

        return Excel::download(new FinancialReportExport($income, $expenses, $summary), $fileName);
    }
}
