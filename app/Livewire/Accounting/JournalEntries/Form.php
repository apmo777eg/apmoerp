<?php

declare(strict_types=1);

namespace App\Livewire\Accounting\JournalEntries;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Rules\BranchScopedExists;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;

    public ?int $journalEntryId = null;

    /**
     * @var array<string,mixed>
     */
    public array $form = [
        'reference_number' => '',
        'entry_date' => '',
        'description' => '',
        'status' => 'draft',
    ];

    /**
     * @var array<int,array<string,mixed>>
     */
    public array $lines = [];

    public function mount(?JournalEntry $journalEntry = null): void
    {
        $this->authorize('accounting.create');

        $this->journalEntryId = $journalEntry?->id;
        $this->form['entry_date'] = now()->format('Y-m-d');

        if ($journalEntry) {
            // Load lines if not already loaded
            if (! $journalEntry->relationLoaded('lines')) {
                $journalEntry->load('lines');
            }

            $this->form['reference_number'] = $journalEntry->reference_number;
            $this->form['entry_date'] = $journalEntry->entry_date?->format('Y-m-d') ?? '';
            $this->form['description'] = $journalEntry->description ?? '';
            $this->form['status'] = $journalEntry->status;

            foreach ($journalEntry->lines as $line) {
                $this->lines[] = [
                    'account_id' => $line->account_id,
                    'description' => $line->description ?? '',
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                ];
            }
        } else {
            // Initialize with 2 empty lines
            $this->addLine();
            $this->addLine();
        }
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'account_id' => null,
            'description' => '',
            'debit' => 0.00,
            'credit' => 0.00,
        ];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) > 2) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    protected function rules(): array
    {
        return [
            'form.reference_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('journal_entries', 'reference_number')->ignore($this->journalEntryId),
            ],
            'form.entry_date' => ['required', 'date'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.status' => ['required', 'in:draft,posted'],
            'lines' => ['required', 'array', 'min:2'],
            // V57-CRITICAL-03 FIX: Use BranchScopedExists to prevent cross-branch account references
            'lines.*.account_id' => ['required', new BranchScopedExists('accounts')],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check that each line has either debit OR credit (not both, not neither)
            foreach ($this->lines as $index => $line) {
                $debit = (string) ($line['debit'] ?? '0');
                $credit = (string) ($line['credit'] ?? '0');

                // Use bccomp for precise comparisons
                if (bccomp($debit, '0', 2) > 0 && bccomp($credit, '0', 2) > 0) {
                    $validator->errors()->add(
                        "lines.{$index}.debit",
                        __('A line cannot have both debit and credit amounts.')
                    );
                } elseif (bccomp($debit, '0', 2) === 0 && bccomp($credit, '0', 2) === 0) {
                    $validator->errors()->add(
                        "lines.{$index}.debit",
                        __('A line must have either a debit or credit amount.')
                    );
                }
            }

            // Check that total debits equal total credits using bcmath
            $totalDebit = (string) $this->getTotalDebit();
            $totalCredit = (string) $this->getTotalCredit();
            $difference = bcsub($totalDebit, $totalCredit, 2);

            // Check if absolute difference exceeds threshold
            if (bccomp(ltrim($difference, '-'), '0.01', 2) > 0) {
                $validator->errors()->add(
                    'lines',
                    __('Total debits must equal total credits. Difference: :amount', [
                        'amount' => number_format(decimal_float(ltrim($difference, '-')), 2),
                    ])
                );
            }

            // Reject zero-sum entries using bccomp
            if (bccomp($totalDebit, '0', 2) <= 0 && bccomp($totalCredit, '0', 2) <= 0) {
                $validator->errors()->add(
                    'lines',
                    __('Journal entry must have amounts greater than zero.')
                );
            }
        });
    }

    public function save(): mixed
    {
        // V58-HIGH-01 FIX: Re-authorize on mutation to prevent direct method calls
        $this->authorize($this->journalEntryId ? 'accounting.update' : 'accounting.create');

        $this->validate();
        $data = $this->form;
        $lines = $this->lines;
        $journalEntryId = $this->journalEntryId;

        return $this->handleOperation(
            operation: function () use ($data, $lines, $journalEntryId) {
                DB::transaction(function () use ($data, $lines, $journalEntryId) {
                    $user = Auth::user();

                    if ($journalEntryId) {
                        $entry = JournalEntry::findOrFail($journalEntryId);
                        // Delete existing lines
                        $entry->lines()->delete();
                    } else {
                        $entry = new JournalEntry;
                        // NEW-V15-HIGH-02 FIX: Do not default branch_id to 1
                        // Require explicit branch selection when user has no branch_id
                        if ($user->branch_id === null) {
                            throw new \Exception(__('Branch selection is required. Please select a branch in the form.'));
                        }
                        $entry->branch_id = $user->branch_id;
                        $entry->created_by = $user->id;
                    }

                    $entry->reference_number = $data['reference_number'];
                    $entry->entry_date = $data['entry_date'];
                    $entry->description = $data['description'] ?: null;
                    $entry->status = $data['status'];
                    $entry->save();

                    // Create lines
                    foreach ($lines as $line) {
                        $entryLine = new JournalEntryLine;
                        $entryLine->journal_entry_id = $entry->id;
                        $entryLine->account_id = $line['account_id'];
                        $entryLine->description = $line['description'] ?: null;
                        $entryLine->debit = $line['debit'];
                        $entryLine->credit = $line['credit'];
                        $entryLine->save();
                    }
                });
            },
            successMessage: $this->journalEntryId
                ? __('Journal entry updated successfully.')
                : __('Journal entry created successfully.'),
            redirectRoute: 'app.accounting.index'
        );
    }

    public function getTotalDebit(): float
    {
        return array_sum(array_column($this->lines, 'debit'));
    }

    public function getTotalCredit(): float
    {
        return array_sum(array_column($this->lines, 'credit'));
    }

    public function render()
    {
        $accounts = Account::where('is_active', true)
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'name']);

        return view('livewire.accounting.journal-entries.form', [
            'accounts' => $accounts,
            'totalDebit' => $this->getTotalDebit(),
            'totalCredit' => $this->getTotalCredit(),
        ]);
    }
}
