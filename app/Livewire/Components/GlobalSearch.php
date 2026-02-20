<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Services\GlobalSearchService;
use Livewire\Attributes\On;
use App\Livewire\BaseComponent as Component;
class GlobalSearch extends Component
{
    public string $query = '';

    public ?string $selectedModule = null;

    public array $results = [];

    public array $groupedResults = [];

    public array $recentSearches = [];

    public bool $showResults = false;

    public int $totalResults = 0;

    public function mount(): void
    {
        abort_if(! auth()->check(), 403);
        $this->loadRecentSearches();
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) >= 2) {
            $this->performSearch();
        } else {
            $this->resetResults();
        }
    }

    public function updatedSelectedModule(): void
    {
        if (! empty($this->query)) {
            $this->performSearch();
        }
    }

    public function performSearch(): void
    {
        $user = auth()->user();
        abort_if(! $user, 403);

        $availableModules = array_keys($this->getAvailableModulesProperty());
        if ($this->selectedModule && ! in_array($this->selectedModule, $availableModules, true)) {
            abort(403, __('Unauthorized module selection'));
        }

        try {
            $searchService = app(GlobalSearchService::class);

            $result = $searchService->search(
                $this->query,
                $user,
                $user->current_branch_id ?? null,
                $this->selectedModule,
            );

            $this->results = $result['results'];
            $this->groupedResults = $result['grouped'] ?? [];
            $this->totalResults = $result['count'];
            $this->showResults = true;

        } catch (\Exception $e) {
            $this->dispatch('error', message: __('Search failed: ').$e->getMessage());
            $this->resetResults();
        }
    }

    public function selectResult(string $url): void
    {
        // CRIT-001 FIX: Prevent Open Redirect by validating URL is internal
        // Only allow URLs that start with '/' and don't contain protocol indicators
        if (
            ! str_starts_with($url, '/') ||
            str_starts_with($url, '//') ||
            str_contains($url, '://') ||
            str_contains($url, "\0")
        ) {
            abort(403, __('Invalid redirect URL'));
        }

        // Additional validation: ensure the URL path is in our results
        $urlIsInResults = collect($this->results)->contains(fn ($result) => ($result['url'] ?? null) === $url);

        if (! $urlIsInResults && ! empty($this->results)) {
            abort(403, __('Redirect URL not found in search results'));
        }

        $this->redirect($url, navigate: true);
    }

    public function useRecentSearch(string $query): void
    {
        $this->query = $query;
        $this->performSearch();
    }

    public function clearHistory(): void
    {
        try {
            $searchService = app(GlobalSearchService::class);
            $searchService->clearHistory(auth()->id());
            $this->recentSearches = [];
            $this->dispatch('success', message: __('Search history cleared'));
        } catch (\Exception $e) {
            $this->dispatch('error', message: __('Failed to clear history'));
        }
    }

    #[On('resetSearch')]
    public function resetSearch(): void
    {
        $this->query = '';
        $this->resetResults();
    }

    public function resetResults(): void
    {
        $this->results = [];
        $this->groupedResults = [];
        $this->totalResults = 0;
        $this->showResults = false;
    }

    private function loadRecentSearches(): void
    {
        try {
            $searchService = app(GlobalSearchService::class);
            $userId = auth()->id();

            if (! $userId) {
                $this->recentSearches = [];

                return;
            }

            $this->recentSearches = $searchService->getRecentSearches($userId, 5);
        } catch (\Exception $e) {
            $this->recentSearches = [];
        }
    }

    public function getAvailableModulesProperty(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        try {
            return app(GlobalSearchService::class)->getAvailableModules($user);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function render()
    {
        return view('livewire.components.global-search');
    }
}
