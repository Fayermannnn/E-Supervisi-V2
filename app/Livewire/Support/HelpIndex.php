<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\HelpArticle;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Bantuan')]
class HelpIndex extends Component
{
    public string $search = '';

    public function render(): View
    {
        $articles = HelpArticle::query()
            ->where('is_published', true)
            ->when($this->search !== '', fn ($q) => $q->where('title', 'ilike', "%{$this->search}%"))
            ->orderBy('category')
            ->orderBy('position')
            ->get()
            ->groupBy('category');

        return view('livewire.support.help-index', ['grouped' => $articles]);
    }
}
