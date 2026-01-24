{{-- resources/views/components/dashboard/quick-actions.blade.php --}}
@props([
    'userRoles' => [],
])

@php
use Illuminate\Support\Facades\Route;

$user = auth()->user();
$actions = [];

// Load quick actions from config
$quickActionsConfig = config('quick-actions', []);

// Determine which action groups to show based on user roles
if ($user->hasRole('Super Admin')) {
    // Super admin sees all actions
    foreach ($quickActionsConfig as $group => $groupActions) {
        $actions = array_merge($actions, $groupActions);
    }
} else {
    // Get actions based on roles
    // Map role names to action groups from config
    $roleGroups = [
        'Admin' => ['admin', 'manager', 'sales', 'purchases', 'inventory', 'hrm'],
        'Manager' => ['manager', 'sales', 'purchases', 'inventory'],
        'Accountant' => ['manager'],
        'HR Manager' => ['hrm', 'manager'],
        'Sales Manager' => ['sales', 'manager'],
        'Salesperson' => ['sales'],
        'Warehouse Manager' => ['inventory', 'purchases'],
        'Warehouse Staff' => ['inventory'],
        'Cashier' => ['sales'],
        'Employee' => [], // Basic employees see no quick actions
        'Viewer' => [], // Viewers see no quick actions
        // Legacy mappings for backwards compatibility
        'Sales' => ['sales'],
        'Purchasing' => ['purchases'],
        'Inventory Manager' => ['inventory'],
    ];
    
    $userRoleNames = $user->roles->pluck('name')->toArray();
    $groupsToShow = [];
    
    foreach ($userRoleNames as $roleName) {
        if (isset($roleGroups[$roleName])) {
            $groupsToShow = array_merge($groupsToShow, $roleGroups[$roleName]);
        }
    }
    
    $groupsToShow = array_unique($groupsToShow);
    
    foreach ($groupsToShow as $group) {
        if (isset($quickActionsConfig[$group])) {
            $actions = array_merge($actions, $quickActionsConfig[$group]);
        }
    }
}

// Filter actions based on permissions and route existence
$filteredActions = collect($actions)->filter(function ($action) use ($user) {
    // Check permission
    if (!$user->can($action['permission'])) {
        return false;
    }
    
    // Check if route exists
    if (!Route::has($action['route'])) {
        return false;
    }
    
    return true;
})->take(8); // Limit to 8 quick actions
@endphp

@if($filteredActions->isNotEmpty())
<x-ui.card title="{{ __('Quick Actions') }}" icon="⚡">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($filteredActions as $action)
        <a href="{{ route($action['route']) }}" 
           class="group flex flex-col items-center p-4 rounded-lg border-2 border-slate-200 dark:border-slate-700 hover:border-{{ $action['color'] ?? 'emerald' }}-500 dark:hover:border-{{ $action['color'] ?? 'emerald' }}-500 hover:shadow-lg transition-all duration-200">
            <div class="text-4xl mb-2 group-hover:scale-110 transition-transform duration-200">
                {{ $action['icon'] }}
            </div>
            
            <span class="text-sm font-medium text-slate-900 dark:text-slate-100 text-center">
                {{ __($action['label']) }}
            </span>
            
            @if(isset($action['description']))
            <span class="text-xs text-slate-500 dark:text-slate-400 text-center mt-1 hidden md:block">
                {{ __($action['description']) }}
            </span>
            @endif
        </a>
        @endforeach
    </div>
</x-ui.card>
@endif
