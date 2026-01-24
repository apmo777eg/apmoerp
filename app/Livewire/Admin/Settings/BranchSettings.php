<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BranchSettings extends Component
{
    public ?int $branchId = null;

    /**
     * @var array<int,array{id:int,name:string}>
     */
    public array $branches = [];

    public array $rows = [];

    /**
     * Track deleted setting IDs for proper database cleanup
     */
    public array $deletedIds = [];

    public function mount(?int $branch = null): void
    {
        // Authorization check - must have settings.branch permission
        $user = Auth::user();
        if (! $user || ! $user->can('settings.branch')) {
            abort(403, __('Unauthorized access to branch settings'));
        }

        $this->branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name])
            ->toArray();

        $this->branchId = $branch ?? ($this->branches[0]['id'] ?? null);

        $this->loadRows();
    }

    public function updatedBranchId(): void
    {
        $this->loadRows();
    }

    protected function loadRows(): void
    {
        $this->rows = [];

        if (! $this->branchId) {
            return;
        }

        $prefix = 'branch:'.$this->branchId.':';

        $this->rows = SystemSetting::query()
            ->where('setting_key', 'like', $prefix.'%')
            ->orderBy('setting_key')
            ->get()
            ->map(function (SystemSetting $setting) use ($prefix): array {
                $plainKey = preg_replace('/^'.preg_quote($prefix, '/').'/', '', $setting->setting_key) ?? $setting->setting_key;

                return [
                    'id' => $setting->id,
                    'key' => $plainKey,
                    'value' => is_array($setting->value) ? json_encode($setting->value) : ($setting->value ?? ''),
                ];
            })
            ->toArray();

        if (count($this->rows) === 0) {
            $this->addRow();
        }
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'key' => '',
            'value' => '',
        ];
    }

    public function removeRow(int $index): void
    {
        if (isset($this->rows[$index])) {
            // Track the ID for deletion if it exists in database
            if (isset($this->rows[$index]['id']) && $this->rows[$index]['id']) {
                $this->deletedIds[] = $this->rows[$index]['id'];
            }
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
        }
    }

    public function save(): mixed
    {
        if (! $this->branchId) {
            return null;
        }

        $this->validate([
            'branchId' => ['required', 'integer'],
            'rows.*.key' => ['required', 'string', 'max:255'],
        ]);

        $prefix = 'branch:'.$this->branchId.':';

        // نقرأ الإعدادات القديمة للفرع
        $before = SystemSetting::query()
            ->where('setting_group', 'branch')
            ->where('setting_key', 'LIKE', $prefix.'%')
            ->get()
            ->keyBy('setting_key')
            ->map(fn (SystemSetting $s) => [
                'setting_key' => $s->setting_key,
                'value' => $s->value,
            ])
            ->all();

        DB::transaction(function () use ($prefix): void {
            // Delete removed settings first
            if (! empty($this->deletedIds)) {
                SystemSetting::query()
                    ->whereIn('id', $this->deletedIds)
                    ->delete();
            }

            foreach ($this->rows as $row) {
                $plainKey = trim((string) ($row['key'] ?? ''));
                if ($plainKey === '') {
                    continue;
                }

                $value = (string) ($row['value'] ?? '');
                $fullKey = $prefix.$plainKey;

                SystemSetting::query()->updateOrCreate(
                    [
                        'setting_key' => $fullKey,
                        'setting_group' => 'branch',
                    ],
                    [
                        'value' => $value,
                        'type' => 'string',
                        'setting_group' => 'branch',
                    ]
                );
            }
        });

        // الإعدادات بعد التعديل
        $after = SystemSetting::query()
            ->where('setting_group', 'branch')
            ->where('setting_key', 'LIKE', $prefix.'%')
            ->get()
            ->keyBy('setting_key')
            ->map(fn (SystemSetting $s) => [
                'setting_key' => $s->setting_key,
                'value' => $s->value,
            ])
            ->all();

        $changes = [];

        foreach ($after as $key => $row) {
            $beforeRow = $before[$key] ?? null;
            if (! $beforeRow) {
                $changes[] = [
                    'type' => 'created',
                    'key' => $key,
                    'after' => $row,
                ];

                continue;
            }

            if ($beforeRow['value'] !== $row['value']) {
                $changes[] = [
                    'type' => 'updated',
                    'key' => $key,
                    'before' => $beforeRow,
                    'after' => $row,
                ];
            }
        }

        foreach ($before as $key => $row) {
            if (! isset($after[$key])) {
                $changes[] = [
                    'type' => 'deleted',
                    'key' => $key,
                    'before' => $row,
                    'after' => null,
                ];
            }
        }

        if (! empty($changes)) {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'target_user_id' => null,
                'action' => 'branch.settings.updated',
                'meta' => [
                    'branch_id' => $this->branchId,
                    'changes' => $changes,
                ],
            ]);
        }

        // Reset deleted IDs after successful save
        $this->deletedIds = [];

        session()->flash('status', __('Branch settings saved successfully.'));

        $this->loadRows();

        $this->dispatch('settings-saved');

        $this->redirectRoute('admin.settings.branch', ['branch' => $this->branchId], navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.settings.branch-settings');
    }
}
