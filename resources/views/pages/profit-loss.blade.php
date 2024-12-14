<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Revenue Section -->
        <div>
            <h2 class="text-lg font-bold">Revenue</h2>
            <div class="space-y-4">
                @foreach ($this->getIncomeStatementData()['revenues'] as $account)
                    <div class="flex justify-between border-b pb-1">
                        <span>{{ $account->name }}</span>
                        <span>{{ number_format($account->journalEntries->sum('credit') - $account->journalEntries->sum('debit'), 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold">
                    <span>Total Revenue</span>
                    <span>{{ number_format($this->getIncomeStatementData()['totalRevenue'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Operating Expenses Section -->
        <div>
            <h2 class="text-lg font-bold">Less Operating Expenses</h2>
            <div class="space-y-4">
                @foreach ($this->getIncomeStatementData()['expenses'] as $account)
                    <div class="flex justify-between border-b pb-1">
                        <span>{{ $account->name }}</span>
                        <span>{{ number_format($account->journalEntries->sum('debit') - $account->journalEntries->sum('credit'), 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold">
                    <span>Total Expenses</span>
                    <span>{{ number_format($this->getIncomeStatementData()['totalExpenses'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Gross Profit Section -->
        <div class="flex justify-between font-bold text-lg border-t pt-4">
            <span>Net Profit</span>
            <span>{{ number_format($this->getIncomeStatementData()['grossProfit'], 2) }}</span>
        </div>
    </div>
</x-filament-panels::page>
