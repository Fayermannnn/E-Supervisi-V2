<?php

declare(strict_types=1);

use App\Livewire\Reporting\CycleReport;
use App\Support\Enums\CycleStatus;
use Livewire\Livewire;

it('fires a celebrate browser event when the cycle report is compiled', function () {
    $c = fase4Cycle(CycleStatus::FollowUpActive);

    Livewire::actingAs($c['supervisor'])
        ->test(CycleReport::class, ['cycle' => $c['cycle']])
        ->call('compile')
        ->assertDispatched('celebrate')
        ->assertDispatched('notify');

    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::Reported);
});
