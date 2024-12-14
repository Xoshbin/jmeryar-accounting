<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Pages;

use Xoshbin\JmeryarAccounting\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

//    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public ?array $data = [];

    protected static ?string $model = Setting::class;

    protected static ?string $navigationGroup = 'Configuration';

    protected static string $view = 'jmeryar-accounting::pages.settings';

    public Setting $setting;

    public function mount(): void
    {
        $this->setting = Setting::firstOrNew([]);
        $this->form->fill($this->setting->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->required(),
                                Forms\Components\TextInput::make('company_email')
                                    ->label('Company Email')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('company_phone')
                                    ->label('Company Phone')
                                    ->required(),
                                Forms\Components\TextInput::make('company_address')
                                    ->label('Company Address')
                                    ->required(),
                                Forms\Components\TextInput::make('company_website')
                                    ->label('Company Website')
                                    ->url()
                                    ->required(),
                                Forms\Components\Select::make('currency_id')
                                    ->label('Default Currency')
                                    ->relationship('currency', 'name')
                                    ->required(),
                            ])->columnSpan(2),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\FileUpload::make('company_logo')
                                            ->label('Company Logo')
                                            ->image()
                                            ->disk('public')
                                            ->columnSpanFull(),
                                    ])
                            ])
                            ->columnSpan(1)
                    ])
            ])
            ->model($this->setting) // Bind the form to the `Setting` model
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('JmeryarPanel-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $this->setting->fill($data)->save();
        } catch (Halt $th) {
            return;
        }
        Notification::make()
            ->success()
            ->title(__('JmeryarPanel-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }
}
