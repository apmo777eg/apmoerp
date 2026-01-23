<?php

declare(strict_types=1);

namespace App\Livewire\Banking\Accounts;

use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use \App\Http\Requests\Traits\HasMultilingualValidation;
    use AuthorizesRequests;

    public ?BankAccount $account = null;

    public bool $isEditing = false;

    // Form fields
    public string $account_number = '';

    public string $account_name = '';

    public string $bank_name = '';

    public string $bank_branch = '';

    public string $swift_code = '';

    public string $iban = '';

    public string $currency = 'USD';

    public string $account_type = 'checking';

    public string $opening_balance = '0';

    public string $opening_date = '';

    public string $notes = '';

    public array $currencies = [];

    protected function rules(): array
    {
        return [
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number,'.($this->account->id ?? 'NULL'),
            'account_name' => $this->multilingualString(required: true, max: 255),
            'bank_name' => $this->multilingualString(required: true, max: 255),
            'bank_branch' => $this->multilingualString(required: false, max: 255),
            'swift_code' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
            'account_type' => 'required|in:checking,savings,credit',
            'opening_balance' => 'required|numeric',
            'opening_date' => 'required|date',
            'notes' => $this->unicodeText(required: false),
        ];
    }

    public function mount(?BankAccount $account = null): void
    {
        // Load currencies with code => name mapping for better UX
        $currencyList = Currency::query()
            ->where('is_active', true)
            ->get(['code', 'name']);

        if ($currencyList->isNotEmpty()) {
            $this->currencies = $currencyList->mapWithKeys(function ($currency) {
                return [$currency->code => $currency->name.' ('.$currency->code.')'];
            })->toArray();
        } else {
            // Fallback currencies if none configured
            $this->currencies = [
                'USD' => 'US Dollar (USD)',
                'EUR' => 'Euro (EUR)',
                'GBP' => 'British Pound (GBP)',
            ];
        }

        if ($account && $account->exists) {
            $this->authorize('banking.edit');
            $this->isEditing = true;
            $this->account = $account;
            $this->fill($account->toArray());
            $this->opening_date = $account->opening_date->format('Y-m-d');
        } else {
            $this->authorize('banking.create');
            $this->opening_date = now()->format('Y-m-d');

            // Set default currency if available
            $defaultCurrency = \App\Models\SystemSetting::where('setting_key', 'default_currency')->value('value');
            // Handle JSON-casted value or string value
            if (is_array($defaultCurrency)) {
                $defaultCurrency = $defaultCurrency['value'] ?? null;
            }
            if ($defaultCurrency && isset($this->currencies[$defaultCurrency])) {
                $this->currency = $defaultCurrency;
            } elseif (! empty($this->currencies)) {
                // Default to first available currency
                $this->currency = array_key_first($this->currencies);
            }
        }
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize($this->isEditing ? 'banking.edit' : 'banking.create');

        $this->validate();

        $data = [
            'branch_id' => auth()->user()->branch_id,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'swift_code' => $this->swift_code,
            'iban' => $this->iban,
            'currency' => $this->currency,
            'account_type' => $this->account_type,
            'opening_balance' => $this->opening_balance,
            'opening_date' => $this->opening_date,
            'notes' => $this->notes,
            'status' => 'active',
        ];

        if ($this->isEditing) {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['updated_by'] = actual_user_id();

            // Check if account has transactions before updating balance
            $hasTransactions = \DB::table('bank_transactions')
                ->where('bank_account_id', $this->account->id)
                ->exists();

            $this->account->update($data);

            // Update current balance if opening balance changed and no transactions exist
            if (! $hasTransactions) {
                $this->account->current_balance = $this->opening_balance;
                $this->account->save();
            }

            session()->flash('success', __('Bank account updated successfully'));
        } else {
            // V33-CRIT-02 FIX: Use actual_user_id() for proper audit attribution during impersonation
            $data['created_by'] = actual_user_id();
            $data['current_balance'] = $this->opening_balance;
            BankAccount::create($data);
            session()->flash('success', __('Bank account created successfully'));
        }

        $this->redirectRoute('app.banking.accounts.index', navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.banking.accounts.form');
    }
}
