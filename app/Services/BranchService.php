<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Module;
use App\Models\User;
use App\Services\Contracts\BranchServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchService implements BranchServiceInterface
{
    use HandlesServiceErrors;

    /**
     * Current branch id injected by middleware into the request.
     */
    public function currentId(): ?int
    {
        return request()->attributes->get('branch_id');
    }

    /**
     * Current Branch model injected by middleware (optional).
     */
    public function current(): ?Branch
    {
        return request()->attributes->get('branch');
    }

    /**
     * Branches accessible for a given user (cached).
     *
     * @return Collection<int, Branch>
     */
    public function forUser(?User $user = null): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($user) {
                $user = $user ?: Auth::user();
                if (! $user) {
                    return collect();
                }

                return Cache::remember('u:'.$user->getKey().':branches', 600, function () use ($user) {
                    if (method_exists($user, 'branches')) {
                        return $user->branches()->where('is_active', true)->get();
                    }

                    return Branch::query()->where('is_active', true)->get();
                });
            },
            operation: 'forUser',
            context: ['user_id' => $user?->getKey()],
            defaultValue: collect()
        );
    }

    /**
     * Abort if the user is not allowed to access the branch.
     */
    public function ensureAccess(Branch $branch, ?User $user = null): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $user) {
                $user = $user ?: Auth::user();
                if (! $user) {
                    abort(401);
                }

                $allowed = $this->forUser($user)->pluck('id')->all();

                if (! in_array($branch->getKey(), $allowed, true)) {
                    abort(403, 'You are not allowed to access this branch.');
                }
            },
            operation: 'ensureAccess',
            context: ['branch_id' => $branch->getKey(), 'user_id' => $user?->getKey()]
        );
    }

    /* ================== Extended management helpers ================== */

    public function list(array $filters = []): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($filters) {
                $q = Branch::query();

                if (! empty($filters['active'])) {
                    $q->where('is_active', true);
                }

                if (! empty($filters['search'])) {
                    $term = trim((string) $filters['search']);
                    $q->where(function ($qq) use ($term) {
                        $qq->where('name', 'like', '%'.$term.'%')
                            ->orWhere('code', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%');
                    });
                }

                return $q->orderByDesc('is_main')
                    ->orderBy('name')
                    ->get();
            },
            operation: 'list',
            context: ['filters' => $filters],
            defaultValue: collect()
        );
    }

    public function create(array $data): Branch
    {
        return $this->handleServiceOperation(
            callback: function () use ($data) {
                return DB::transaction(function () use ($data) {
                    /** @var Branch $branch */
                    $branch = Branch::create($data);

                    if (Branch::count() === 1) {
                        $branch->is_main = true;
                        $branch->is_active = true;
                        $branch->save();
                    }

                    $this->logServiceInfo('create', 'Branch created successfully', [
                        'branch_id' => $branch->getKey(),
                        'name' => $branch->name,
                    ]);

                    return $branch;
                });
            },
            operation: 'create',
            context: ['data' => $data]
        );
    }

    public function update(Branch $branch, array $data): Branch
    {
        return $this->handleServiceOperation(
            callback: function () use ($branch, $data) {
                $branch->fill($data);
                $branch->save();

                $this->logServiceInfo('update', 'Branch updated successfully', [
                    'branch_id' => $branch->getKey(),
                ]);

                return $branch->refresh();
            },
            operation: 'update',
            context: ['branch_id' => $branch->getKey(), 'data' => $data]
        );
    }

    public function toggleActive(Branch $branch, ?bool $active = null): Branch
    {
        return $this->handleServiceOperation(
            callback: function () use ($branch, $active) {
                $branch->is_active = $active ?? ! $branch->is_active;
                $branch->save();

                $this->logServiceInfo('toggleActive', 'Branch active status toggled', [
                    'branch_id' => $branch->getKey(),
                    'is_active' => $branch->is_active,
                ]);

                return $branch->refresh();
            },
            operation: 'toggleActive',
            context: ['branch_id' => $branch->getKey(), 'active' => $active]
        );
    }

    public function syncModules(Branch $branch, array $modulesPayload): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $modulesPayload) {
                DB::transaction(function () use ($branch, $modulesPayload) {
                    $sync = [];

                    foreach ($modulesPayload as $item) {
                        if (empty($item['module_key'])) {
                            continue;
                        }

                        $key = (string) $item['module_key'];
                        $module = Module::where('module_key', $key)->first();

                        $pivotData = [
                            'module_key' => $key,
                            'enabled' => (bool) ($item['enabled'] ?? true),
                            'settings' => $item['settings'] ?? [],
                        ];

                        if ($module) {
                            $sync[$module->id] = $pivotData;
                        }
                    }

                    if (! empty($sync)) {
                        $branch->modules()->sync($sync);
                    }
                });

                $this->logServiceInfo('syncModules', 'Branch modules synced', [
                    'branch_id' => $branch->getKey(),
                    'module_count' => count($modulesPayload),
                ]);
            },
            operation: 'syncModules',
            context: ['branch_id' => $branch->getKey()]
        );
    }

    public function attachUser(Branch $branch, User $user): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $user) {
                $branch->users()->syncWithoutDetaching([$user->id]);

                $this->logServiceInfo('attachUser', 'User attached to branch', [
                    'branch_id' => $branch->getKey(),
                    'user_id' => $user->getKey(),
                ]);
            },
            operation: 'attachUser',
            context: ['branch_id' => $branch->getKey(), 'user_id' => $user->getKey()]
        );
    }

    public function detachUser(Branch $branch, User $user): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branch, $user) {
                $branch->users()->detach($user->id);

                $this->logServiceInfo('detachUser', 'User detached from branch', [
                    'branch_id' => $branch->getKey(),
                    'user_id' => $user->getKey(),
                ]);
            },
            operation: 'detachUser',
            context: ['branch_id' => $branch->getKey(), 'user_id' => $user->getKey()]
        );
    }
}
