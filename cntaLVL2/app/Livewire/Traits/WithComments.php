<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait WithComments
{
    /**
     * Cache quick comments per request
     */
    protected ?Collection $quickCommentsCache = null;

    /**
     * Get all available quick comments organized by category (DB driven)
     *
     * @return Collection [Category => Collection<rows>]
     */
    public function getQuickComments(): Collection
    {
        if ($this->quickCommentsCache !== null) {
            return $this->quickCommentsCache;
        }

        $this->quickCommentsCache = DB::table('admn_cnta_note_comnt')
            ->select(
                'Admn_Cnta_Note_Comnt_UIN',
                'Category',
                'Comnt_Text'
            )
            ->where('Stau_UIN', 100201) // status Active
            ->orderBy('Category')
            ->orderBy('Comnt_Text')
            ->get()
            ->groupBy('Category');

        return $this->quickCommentsCache;
    }

    /**
     * Get comments for a specific category
     */
    public function getCommentsByCategory(string $category): Collection
    {
        return $this->getQuickComments()->get($category, collect());
    }

    /**
     * Get all available categories
     */
    public function getAvailableCategories(): array
    {
        return $this->getQuickComments()
            ->keys()
            ->values()
            ->toArray();
    }

    /**
     * Search comments across all categories
     */
    public function searchComments(string $query): Collection
    {
        if (trim($query) === '') {
            return collect();
        }

        return $this->getQuickComments()
            ->map(function (Collection $comments) use ($query) {
                return $comments->filter(fn ($row) => stripos($row->Comnt_Text, $query) !== false
                );
            })
            ->filter(fn (Collection $comments) => $comments->isNotEmpty());
    }

    /**
     * Clear cache (use after admin updates)
     */
    public function refreshQuickComments(): void
    {
        $this->quickCommentsCache = null;
    }
}
