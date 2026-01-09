<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Month Selector --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn p-6">
                <form wire:submit.prevent>
                    {{ $this->form }}
                </form>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Pemasukan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            IDR {{ number_format($totalIncome, 0, ",", ".") }}
                        </p>
                    </div>
                    <x-heroicon-o-arrow-trending-up class="h-8 w-8 text-success-500" />
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            IDR {{ number_format($totalExpense, 0, ",", ".") }}
                        </p>
                    </div>
                    <x-heroicon-o-arrow-trending-down class="h-8 w-8 text-danger-500" />
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Balance</p>
                        <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ $balance >= 0 ? '+' : '-' }} IDR {{ number_format(abs($balance), 0, ",", ".") }}
                        </p>
                    </div>
                    <x-heroicon-o-scale class="h-8 w-8 text-warning-500" />
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Piutang</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            IDR {{ number_format($totalReceivables, 0, ",", ".") }}
                        </p>
                    </div>
                    <x-heroicon-o-credit-card class="h-8 w-8 text-warning-500" />
                </div>
            </x-filament::section>
        </div>

        {{-- Income and Expense Tables --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Income Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Detail Pemasukan (Paid Invoices)
                </x-slot>
                @php
                    [$year, $month] = explode('-', $this->selectedMonth);

                    $incomeData = \App\Models\Invoice::query()
                        ->where('status', 'paid')
                        ->where(function ($query) use ($year, $month) {
                            $query->whereYear('updated_at', $year)->whereMonth('updated_at', $month);
                        })
                        ->orWhere(function ($query) use ($year, $month) {
                            $query
                                ->where('status', 'paid')
                                ->whereYear('updated_at', $year)
                                ->whereMonth('updated_at', $month);
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(10, ['*'], 'incomePage');
                @endphp
                @if ($incomeData->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                    <th class="px-4 py-2 text-left">No. Invoice</th>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($incomeData as $invoice)
                                    <tr>
                                        <td class="px-4 py-2">
                                            {{ \Carbon\Carbon::parse($invoice->updated_at)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2">{{ $invoice->invoice_number }}</td>
                                        <td class="px-4 py-2">
                                            {{ $invoice->name_customer ?? ($invoice->customer->name ?? '-') }}
                                        </td>
                                        <td class="px-4 py-2 text-right">IDR
                                            {{ number_format($invoice->grand_total, 0, ",", ".") }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $incomeData->links() }}
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        Tidak ada data pemasukan untuk bulan ini
                    </div>
                @endif
            </x-filament::section>

            {{-- Expense Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Detail Pengeluaran
                </x-slot>
                @php
                    $expenseData = \App\Models\Expense::query()
                        ->whereYear('expense_date', $year)
                        ->whereMonth('expense_date', $month)
                        ->orderBy('expense_date', 'desc')
                        ->paginate(10, ['*'], 'expensePage');
                @endphp
                @if ($expenseData->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                    <th class="px-4 py-2 text-left">Deskripsi</th>
                                    <th class="px-4 py-2 text-right">Jumlah</th>
                                    <th class="px-4 py-2 text-left">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($expenseData as $expense)
                                    <tr>
                                        <td class="px-4 py-2">
                                            {{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2">{{ $expense->description }}</td>
                                        <td class="px-4 py-2 text-right">IDR
                                            {{ number_format($expense->amount, 0, ",", ".") }}
                                        </td>
                                        <td class="px-4 py-2">{{ $expense->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $expenseData->links() }}
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        Tidak ada data pengeluaran untuk bulan ini
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>


{{-- YG DESIGNNYA BAGUS DIBAWAH --}}

<x-filament-panels::page>
    {{-- Filter Bulan --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    {{-- Widget Statistik --}}
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
        <x-filament::card>
            <div class="flex items-center gap-4">
                <div class="p-3 text-green-500 bg-green-100 rounded-full dark:bg-green-900/50 dark:text-green-400">
                    <x-heroicon-o-arrow-trending-up class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pemasukan</p>
                    <p class="text-2xl font-bold dark:text-white">
                        {{ Illuminate\Support\Number::currency($totalIncome, 'IDR') }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center gap-4">
                <div class="p-3 text-red-500 bg-red-100 rounded-full dark:bg-red-900/50 dark:text-red-400">
                    <x-heroicon-o-arrow-trending-down class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                    <p class="text-2xl font-bold dark:text-white">
                        {{ Illuminate\Support\Number::currency($totalExpense, 'IDR') }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center gap-4">
                <div
                    class="p-3 rounded-full text-primary-500 bg-primary-100 dark:bg-primary-900/50 dark:text-primary-400">
                    <x-heroicon-o-scale class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Balance</p>
                    <p
                        class="text-2xl font-bold {{ $balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ Illuminate\Support\Number::currency($balance, 'IDR') }}
                    </p>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center gap-4">
                <div class="p-3 text-yellow-500 bg-yellow-100 rounded-full dark:bg-yellow-900/50 dark:text-yellow-400">
                    <x-heroicon-o-credit-card class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Piutang</p>
                    <p class="text-2xl font-bold dark:text-white">
                        {{ Illuminate\Support\Number::currency($totalReceivables, 'IDR') }}
                    </p>
                </div>
            </div>
        </x-filament::card>
    </div>

    {{-- Tabel Detail --}}
    <div class="grid grid-cols-1 gap-8">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold dark:text-white">Detail Pemasukan (Paid Invoices)</h3>
            {{ $this->incomeTable }}
        </div>

        <div class="space-y-4">
            <h3 class="text-lg font-semibold dark:text-white">Detail Pengeluaran</h3>
            {{ $this->expenseTable }}
        </div>
    </div>

</x-filament-panels::page>

