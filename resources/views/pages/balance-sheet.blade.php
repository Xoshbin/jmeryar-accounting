<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Assets Section -->
        <div>
            <h2 class="text-lg font-bold">ASSETS</h2>
            <div class="space-y-4">
                @foreach ($this->getBalanceData()['data']['assets'] as $account)
                    <div class="flex justify-between border-b pb-1">
                        <span>{{ $account->name }}</span>
                        <span>{{ number_format($account->journalEntries->sum('debit') - $account->journalEntries->sum('credit'), 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold">
                    <span>Total Assets</span>
                    <span>{{ number_format($this->getBalanceData()['totals']['assets'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Liabilities Section -->
        <div>
            <h2 class="text-lg font-bold">LIABILITIES</h2>
            <div class="space-y-4">
                @foreach ($this->getBalanceData()['data']['liabilities'] as $account)
                    <div class="flex justify-between border-b pb-1">
                        <span>{{ $account->name }}</span>
                        <span>{{ number_format($account->journalEntries->sum('credit') - $account->journalEntries->sum('debit'), 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold">
                    <span>Total Liabilities</span>
                    <span>{{ number_format($this->getBalanceData()['totals']['liabilities'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Equity Section -->
        <div>
            <h2 class="text-lg font-bold">EQUITY</h2>
            <div class="space-y-4">
                @foreach ($this->getBalanceData()['data']['equity'] as $account)
                    <div class="flex justify-between border-b pb-1">
                        <span>{{ $account->name }}</span>
                        <span>{{ number_format($account->journalEntries->sum('credit') - $account->journalEntries->sum('debit'), 2) }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between font-bold">
                    <span>Derived Equity</span>
                    <span>{{ number_format($this->getBalanceData()['totals']['equity'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Liabilities + Equity -->
        <div class="flex justify-between font-bold border-t pt-4">
            <span>LIABILITIES + EQUITY</span>
            <span>{{ number_format($this->getBalanceData()['totals']['liabilities'] + $this->getBalanceData()['totals']['equity'], 2) }}</span>
        </div>
    </div>
</x-filament-panels::page>
