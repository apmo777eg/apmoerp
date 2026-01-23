<div>
    {{-- Onboarding Trigger Button (optional - can be placed in header) --}}
    @if(!$showGuide)
        <button 
            wire:click="openGuide"
            type="button"
            class="hidden p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
            data-onboarding-trigger
        >
            <x-icon name="question-mark-circle" class="h-5 w-5" />
        </button>
    @endif

    {{-- Onboarding Modal --}}
    @if($showGuide && count($steps) > 0)
        <div 
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="onboarding-title"
            role="dialog"
            aria-modal="true"
        >
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

            {{-- Modal Container --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-2xl transition-all">
                    
                    {{-- Progress Bar --}}
                    <div class="h-1 bg-slate-100 dark:bg-slate-700">
                        <div 
                            class="h-full bg-gradient-to-r from-primary-500 to-primary-600 transition-all duration-500"
                            style="width: {{ ($currentStep + 1) / count($steps) * 100 }}%"
                        ></div>
                    </div>

                    {{-- Step Indicator --}}
                    <div class="flex items-center justify-center gap-2 pt-4 px-6">
                        @foreach($steps as $index => $step)
                            <button
                                wire:click="goToStep({{ $index }})"
                                type="button"
                                class="w-2.5 h-2.5 rounded-full transition-all duration-300 {{ $currentStep === $index ? 'bg-primary-500 scale-125' : ($index < $currentStep ? 'bg-primary-300 dark:bg-primary-700' : 'bg-slate-200 dark:bg-slate-600') }}"
                                aria-label="{{ __('Step :num', ['num' => $index + 1]) }}"
                            ></button>
                        @endforeach
                    </div>

                    {{-- Content --}}
                    @php $currentStepData = $steps[$currentStep] ?? null; @endphp
                    @if($currentStepData)
                        <div class="p-6 text-center">
                            {{-- Icon --}}
                            <div class="mb-4 text-5xl animate-bounce">
                                {{ $currentStepData['icon'] }}
                            </div>

                            {{-- Title --}}
                            <h3 id="onboarding-title" class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">
                                {{ $currentStepData['title'] }}
                            </h3>

                            {{-- Description --}}
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                {{ $currentStepData['description'] }}
                            </p>

                            {{-- Step Counter --}}
                            <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                                {{ __('Step :current of :total', ['current' => $currentStep + 1, 'total' => count($steps)]) }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-100 dark:border-slate-700">
                            <div>
                                @if($currentStep > 0)
                                    <button
                                        wire:click="previousStep"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors"
                                    >
                                        <x-icon name="arrow-left" class="w-4 h-4 inline-block me-1" />
                                        {{ __('Previous') }}
                                    </button>
                                @else
                                    <button
                                        wire:click="skipOnboarding"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                                    >
                                        {{ __('Skip Tour') }}
                                    </button>
                                @endif
                            </div>
                            
                            <div>
                                <button
                                    wire:click="nextStep"
                                    type="button"
                                    class="px-5 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors shadow-sm"
                                >
                                    @if($currentStep === count($steps) - 1)
                                        {{ __('Get Started!') }}
                                        <x-icon name="rocket-launch" class="w-4 h-4 inline-block ms-1" />
                                    @else
                                        {{ __('Next') }}
                                        <x-icon name="arrow-right" class="w-4 h-4 inline-block ms-1" />
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Close Button --}}
                    <button
                        wire:click="closeGuide"
                        type="button"
                        class="absolute top-3 end-3 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                        aria-label="{{ __('Close') }}"
                    >
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
