<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use App\Domain\Ai\Actions\GenerateAiDraft;
use App\Domain\Ai\Actions\ReviewAiGeneration;
use App\Domain\Feedback\Actions\AcknowledgeFeedback;
use App\Domain\Feedback\Actions\PostFeedbackMessage;
use App\Domain\Feedback\Actions\StartFeedbackSession;
use App\Models\AiGeneration;
use App\Models\FeedbackMessage;
use App\Models\FeedbackSession;
use App\Models\SupervisionCycle;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Ruang Umpan Balik')]
class FeedbackRoom extends Component
{
    public SupervisionCycle $cycle;

    public string $tipe = 'observasi';

    public string $konten = '';

    public string $aiEdit = '';

    public function mount(SupervisionCycle $cycle): void
    {
        $this->authorize('view', $cycle);
        $this->cycle = $cycle;
    }

    public function send(PostFeedbackMessage $action, StartFeedbackSession $start): void
    {
        $this->validate(['konten' => ['required', 'string', 'min:2', 'max:5000'], 'tipe' => ['required']]);
        $session = $start->handle($this->cycle);

        try {
            $action->handle($this->user(), $session, $this->tipe, $this->konten);
        } catch (DomainException $e) {
            $this->addError('konten', $e->getMessage());

            return;
        }

        $this->reset('konten');
        $this->tipe = 'observasi';
    }

    public function requestAiSuggestion(GenerateAiDraft $generate, StartFeedbackSession $start): void
    {
        $session = $start->handle($this->cycle);
        abort_unless($this->cycle->supervisor_id === $this->user()->getKey(), 403);

        $analysis = \App\Models\AnalysisResult::with('findings')->where('cycle_id', $this->cycle->id)->first();
        $growth = $analysis?->findings->where('kategori', 'area_pengembangan')->pluck('deskripsi')->all() ?? [];

        try {
            $generate->handle($this->user(), FeedbackSession::class, $session->id, 'feedback_suggestion', ['growth_areas' => $growth]);
        } catch (RuntimeException $e) {
            $this->addError('konten', $e->getMessage());

            return;
        }
        $this->dispatch('notify', message: 'Saran AI diminta. Segarkan sebentar lagi.');
    }

    public function reviewAi(string $id, string $decision, ReviewAiGeneration $review, PostFeedbackMessage $post, StartFeedbackSession $start): void
    {
        $gen = AiGeneration::findOrFail($id);
        $review->handle($this->user(), $gen, $decision, $decision === 'edit' ? $this->aiEdit : null);

        if (in_array($decision, ['accept', 'edit'], true)) {
            $session = $start->handle($this->cycle);
            $text = $decision === 'edit' ? $this->aiEdit : (string) $gen->output;
            $post->handle($this->user(), $session, FeedbackMessage::TIPE_PERTANYAAN, $text, $gen->fresh());
        }
        $this->aiEdit = '';
        $this->dispatch('notify', message: 'Tinjauan dicatat.');
    }

    public function acknowledge(AcknowledgeFeedback $action): void
    {
        $session = FeedbackSession::where('cycle_id', $this->cycle->id)->firstOrFail();

        try {
            $action->handle($this->user(), $session);
        } catch (Throwable $e) {
            $this->addError('konten', $e->getMessage());

            return;
        }

        $this->cycle->refresh();
        $this->dispatch('notify', message: 'Umpan balik dikonfirmasi. Siklus lanjut ke tindak lanjut.');
    }

    public function render(): View
    {
        $session = FeedbackSession::where('cycle_id', $this->cycle->id)->first();
        $user = $this->user();

        return view('livewire.feedback.feedback-room', [
            'session' => $session,
            'messages' => $session?->messages()->with('pengirim')->get() ?? collect(),
            'pendingAi' => $session === null ? collect() : AiGeneration::where('source_type', FeedbackSession::class)
                ->where('source_id', $session->id)
                ->where('status', 'draft')
                ->whereNotIn('review_status', ['accepted', 'edited', 'rejected'])
                ->get(),
            'isGuru' => $this->cycle->guru_id === $user->getKey(),
            'isSupervisor' => $this->cycle->supervisor_id === $user->getKey(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }
}
