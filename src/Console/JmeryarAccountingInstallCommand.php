<?php

namespace Xoshbin\JmeryarAccounting\Console;

use Illuminate\Console\Command;
use Xoshbin\JmeryarAccounting\Models\Setting;
use Xoshbin\JmeryarAccounting\Models\Currency;

class JmeryarAccountingInstallCommand extends Command
{
    protected $signature = 'jmeryaraccounting:install {--dummy : Install with dummy data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Jmeryar package migrations and seed the database using FullSeeder';

    public function handle()
    {
        $this->info('Running migrations...');
        $this->call('migrate');

        if ($this->option('dummy')) {
            $this->info('Seeding the database with FullSeeder...');
            $this->call('db:seed', [
                '--class' => 'Xoshbin\JmeryarAccounting\Database\Seeders\FullSeeder',
            ]);
        } else {
            $this->info('Prompting for base settings...');
            $this->call('db:seed', [
                '--class' => 'Xoshbin\JmeryarAccounting\Database\Seeders\BaseSeeder',
            ]);
            $this->promptForBaseSettings();
            $this->info('Seeding the database with BaseSeeder...');
        }

        $this->info('Jmeryar Accounting installation complete!');

        return Command::SUCCESS;
    }

    protected function promptForBaseSettings()
    {
        $data = [
            'company_name' => $this->ask('Company Name'),
            'currency_id' => $this->getCurrencyId(),
        ];

        Setting::create($data);
    }

    protected function getCurrencyId()
    {
        $currencyName = $this->ask('Base Currency Name');
        $currency = Currency::where('code', 'like', "%$currencyName%")->first();

        if ($currency) {
            return $currency->id;
        } else {
            $this->error('Currency not found. Please try again.');
            return $this->getCurrencyId();
        }
    }
}
