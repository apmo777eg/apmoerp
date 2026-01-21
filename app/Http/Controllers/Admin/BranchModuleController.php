<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchModule;
use App\Services\Contracts\ModuleServiceInterface as ModuleService;
use Illuminate\Http\Request;

class BranchModuleController extends Controller
{
    public function __construct(protected ModuleService $modules) {}

    public function index(Branch $branch)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.view');
        
        $data = $this->modules->allForBranch($branch->id);

        return $this->ok([
            'branch' => $branch,
            'modules' => $data,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'key' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        if ($data['enabled']) {
            $this->modules->enableForBranch($branch, $data['key']);
        } else {
            $this->modules->disableForBranch($branch, $data['key']);
        }

        $bm = BranchModule::where('branch_id', $branch->id)
            ->where('module_key', $data['key'])
            ->first();

        return $this->ok($bm, __('Module status updated'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Attach a module to a branch
     */
    public function attach(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'module_key' => ['required', 'string'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $this->modules->enableForBranch($branch, $data['module_key']);

        $bm = BranchModule::where('branch_id', $data['branch_id'])
            ->where('module_key', $data['module_key'])
            ->first();

        return $this->ok($bm, __('Module attached to branch'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Detach a module from a branch
     */
    public function detach(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'module_key' => ['required', 'string'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $this->modules->disableForBranch($branch, $data['module_key']);

        return $this->ok(null, __('Module detached from branch'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Update module settings for a branch
     */
    public function updateSettings(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'module_key' => ['required', 'string'],
            'settings' => ['required', 'array'],
        ]);

        $bm = BranchModule::where('branch_id', $data['branch_id'])
            ->where('module_key', $data['module_key'])
            ->first();

        if (! $bm) {
            return $this->fail(__('Module not found for this branch'), 404);
        }

        $bm->settings = $data['settings'];
        $bm->save();

        return $this->ok($bm, __('Module settings updated'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Enable a module for a branch
     */
    public function enable(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'module_key' => ['required', 'string'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $this->modules->enableForBranch($branch, $data['module_key']);

        $bm = BranchModule::where('branch_id', $data['branch_id'])
            ->where('module_key', $data['module_key'])
            ->first();

        return $this->ok($bm, __('Module enabled'));
    }

    /**
     * NEW-V15-CRITICAL-02 FIX: Disable a module for a branch
     */
    public function disable(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for branch module management
        $this->authorize('branches.modules.manage');
        
        $data = $this->validate($request, [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'module_key' => ['required', 'string'],
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $this->modules->disableForBranch($branch, $data['module_key']);

        return $this->ok(null, __('Module disabled'));
    }
}
