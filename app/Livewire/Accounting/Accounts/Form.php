<?php

declare(strict_types=1);

namespace App\Livewire\Accounting\Accounts;

use App\Http\Requests\Traits\HasMultilingualValidation;
use App\Livewire\Concerns\HandlesErrors;
use App\Models\Account;
use App\Models\Currency;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use HasMultilingualValidation;

    public ?int $accountId = null;

    /**
     * @var array<string,mixed>
     */
    public array $form = [
        'account_number' => '',
        'name' => '',
        'name_ar' => '',
        'type' => 'asset',
        'currency_code' => 'EGP',
        'account_category' => '',
        'description' => '',
        'parent_id' => null,
        'is_active' => true,
    ];

    public function mount(?Account $account = null): void
    {
        $this->authorize('accounting.create');

        $this->accountId = $account?->id;

        if ($account) {
            $this->form['account_number'] = $account->account_number;
            $this->form['name'] = $account->name;
            $this->form['name_ar'] = $account->name_ar ?? '';
            $this->form['type'] = $account->type;
            $this->form['currency_code'] = $account->currency_code ?? 'EGP';
            $this->form['account_category'] = $account->account_category ?? '';
            $this->form['description'] = $account->description ?? '';
            $this->form['parent_id'] = $account->parent_id;
            $this->form['is_active'] = (bool) $account->is_active;
        }
    }

    protected function rules(): array
    {
        return [
            'form.account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts', 'account_number')->ignore($this->accountId),
            ],
            'form.name' => ['required', 'string', 'max:255'],
            'form.name_ar' => ['nullable', 'string', 'max:255'],
            'form.type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'form.currency_code' => ['required', 'string', 'max:10'],
            'form.account_category' => ['nullable', 'string', 'max:100'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch parent account references
            'form.parent_id' => ['nullable', new BranchScopedExists('accounts', 'id', null, true)],
            'form.is_active' => ['boolean'],
        ];
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize($this->accountId ? 'accounting.update' : 'accounting.create');

        $this->validate();
        $data = $this->form;
        $accountId = $this->accountId;

        return $this->handleOperation(
            operation: function () use ($data, $accountId) {
                $user = Auth::user();

                if ($accountId) {
                    $account = Account::findOrFail($accountId);
                } else {
                    $account = new Account;
                    // NEW-V15-HIGH-02 FIX: Do not default branch_id to 1
                    // Require explicit branch selection when user has no branch_id
                    if ($user->branch_id === null) {
                        throw new \Exception(__('Branch selection is required. Please select a branch in the form.'));
                    }
                    $account->branch_id = $user->branch_id;
                    $account->balance = 0.00;
                }

                $account->account_number = $data['account_number'];
                $account->name = $data['name'];
                $account->name_ar = $data['name_ar'] ?: null;
                $account->type = $data['type'];
                $account->currency_code = $data['currency_code'];
                $account->account_category = $data['account_category'] ?: null;
                $account->description = $data['description'] ?: null;
                $account->parent_id = $data['parent_id'] ?: null;
                $account->is_active = (bool) $data['is_active'];

                $account->save();
            },
            successMessage: $this->accountId
                ? __('Account updated successfully.')
                : __('Account created successfully.'),
            redirectRoute: 'app.accounting.index'
        );
    }

    public function render()
    {
        $currencies = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name']);

        $parentAccounts = Account::where('is_active', true)
            ->when($this->accountId, fn ($q) => $q->where('id', '!=', $this->accountId))
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'name']);

        return view('livewire.accounting.accounts.form', [
            'currencies' => $currencies,
            'parentAccounts' => $parentAccounts,
        ]);
    }
}
