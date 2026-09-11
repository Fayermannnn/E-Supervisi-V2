<?php

declare(strict_types=1);

use App\Domain\Administration\PolicySettings;
use App\Domain\Observation\Actions\StartObservation;
use App\Models\Observation;
use App\Models\ObservationMedia;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

/**
 * Endpoint: POST /api/v1/observations/{observation}/media (M2).
 * Sumber: docs/testing.md — "upload .php/.svg berbahaya ditolak".
 */
function startTestObservation(): array
{
    $c = fase4Cycle(CycleStatus::Scheduled);
    $observation = app(StartObservation::class)->handle($c['supervisor'], $c['cycle']->refresh());

    return [...$c, 'observation' => $observation];
}

it('rejects a .php upload outright, regardless of content', function () {
    $c = startTestObservation();
    Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'dokumen',
        'file' => UploadedFile::fake()->createWithContent('shell.php', "<?php system(\$_GET['c']); ?>"),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    expect(ObservationMedia::where('observation_id', $c['observation']->id)->count())->toBe(0);
});

it('rejects a .svg upload (not whitelisted — guards against inline stored XSS)', function () {
    $c = startTestObservation();
    Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'foto',
        'file' => UploadedFile::fake()->createWithContent('logo.svg', '<svg onload="alert(1)"><script>alert(1)</script></svg>'),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('rejects a php payload disguised with an image extension', function () {
    // UploadedFile::fake() menebak mime dari NAMA berkas (test double demi
    // kecepatan), bukan isi — jadi ujian "isi asli vs nama" ini butuh
    // UploadedFile sungguhan (Symfony finfo asli) di atas berkas temp nyata.
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, "<?php system(\$_GET['c']); ?>");

    $c = startTestObservation();
    Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'foto',
        'file' => new UploadedFile($path, 'cover.jpg', null, null, true),
    ])->assertStatus(422)->assertJsonValidationErrors('file');

    @unlink($path);
});

it('rejects a file whose real content does not match the claimed tipe', function () {
    $c = startTestObservation();
    Sanctum::actingAs($c['supervisor']);

    // Berkas benar-benar JPEG (lolos mimes:) tapi diklaim sebagai "video".
    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'video',
        'file' => UploadedFile::fake()->image('cover.jpg', 10, 10),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('rejects an oversized upload', function () {
    $c = startTestObservation();
    app(PolicySettings::class)->set('observation.max_media_mb', 1, null);
    Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'foto',
        'file' => UploadedFile::fake()->image('cover.jpg')->size(2048),
    ])->assertStatus(422)->assertJsonValidationErrors('file');
});

it('accepts a genuine upload matching its declared tipe and sanitizes the stored name', function () {
    $c = startTestObservation();
    Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'foto',
        'file' => UploadedFile::fake()->image('../../etc/evil name.jpg', 10, 10),
    ])->assertStatus(201);

    $media = ObservationMedia::where('observation_id', $c['observation']->id)->sole();
    expect($media->tipe)->toBe('foto')
        ->and($media->original_name)->toBe('evil name.jpg')
        ->and($media->original_name)->not->toContain('/');
});

it('forbids uploading media to an observation the caller does not own', function () {
    $c = startTestObservation();
    $stranger = User::factory()->supervisor()->create();
    Sanctum::actingAs($stranger);

    $this->postJson("/api/v1/observations/{$c['observation']->id}/media", [
        'tipe' => 'foto',
        'file' => UploadedFile::fake()->image('cover.jpg', 10, 10),
    ])->assertForbidden();
});

it('requires authentication', function () {
    $observation = Observation::factory()->create();

    $this->postJson("/api/v1/observations/{$observation->id}/media", [
        'tipe' => 'foto',
        'file' => UploadedFile::fake()->image('cover.jpg', 10, 10),
    ])->assertUnauthorized();
});
