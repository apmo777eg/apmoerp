<div class="space-y-4">
    <h3 class="text-lg font-semibold text-slate-700">{{ __('Quick Actions') }}</h3>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @forelse($actions as $action)
            @php
                // Whitelist allowed colors to prevent CSS injection
                $allowedColors = ['emerald', 'blue', 'purple', 'orange', 'cyan', 'violet', 'red', 'green', 'amber', 'indigo', 'pink', 'slate'];
                $color = in_array($action['color'] ?? 'emerald', $allowedColors) ? ($action['color'] ?? 'emerald') : 'emerald';
            @endphp
            <a href="{{ route($action['route']) }}" 
               class="group flex flex-col items-center p-4 bg-white border border-slate-200 rounded-xl hover:border-{{ $color }}-300 hover:bg-{{ $color }}-50 transition-all duration-200">
                <span class="text-3xl mb-2">{{ $action['icon'] }}</span>
                <span class="text-sm font-medium text-slate-700 text-center group-hover:text-{{ $color }}-700">
                    {{ $action['title'] }}
                </span>
                <span class="text-xs text-slate-500 text-center mt-1 hidden sm:block">
                    {{ $action['description'] }}
                </span>
            </a>
        @empty
            <div class="col-span-full text-center py-8 text-slate-500">
                {{ __('No quick actions available') }}
            </div>
        @endforelse
    </div>
</div>
