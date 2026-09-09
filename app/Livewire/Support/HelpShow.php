<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\HelpArticle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Bantuan')]
class HelpShow extends Component
{
    public HelpArticle $article;

    public function mount(HelpArticle $article): void
    {
        $this->authorize('view', $article);
        $this->article = $article;
    }

    public function render(): View
    {
        return view('livewire.support.help-show', [
            'html' => Str::markdown($this->article->body_markdown),
        ]);
    }
}
