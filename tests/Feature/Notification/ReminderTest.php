<?php

declare(strict_types=1);

use App\Domain\Notification\DispatchesReminders;
use App\Domain\Notification\GenericReminderNotification;
use App\Models\ReminderSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('dispatches only due, scheduled reminders', function () {
    Notification::fake();

    $user = User::factory()->create(['notification_preferences' => ['app' => true, 'mail' => false]]);

    $due = ReminderSchedule::create([
        'remindable_type' => 'test', 'remindable_id' => Illuminate\Support\Str::uuid7()->toString(),
        'user_id' => $user->id, 'kind' => 'test.due', 'send_at' => now()->subMinute(),
        'payload' => ['title' => 'Halo', 'body' => 'Isi'],
    ]);

    ReminderSchedule::create([
        'remindable_type' => 'test', 'remindable_id' => Illuminate\Support\Str::uuid7()->toString(),
        'user_id' => $user->id, 'kind' => 'test.future', 'send_at' => now()->addDay(),
    ]);

    $sent = app(DispatchesReminders::class)->dispatchDue();

    expect($sent)->toBe(1);
    expect($due->fresh()->status)->toBe('sent');
    Notification::assertSentTo($user, GenericReminderNotification::class);
});

it('cancels reminders for an inactive user without notifying', function () {
    Notification::fake();

    $user = User::factory()->inactive()->create();
    $reminder = ReminderSchedule::create([
        'remindable_type' => 'test', 'remindable_id' => Illuminate\Support\Str::uuid7()->toString(),
        'user_id' => $user->id, 'kind' => 'test.due', 'send_at' => now()->subMinute(),
    ]);

    app(DispatchesReminders::class)->dispatchDue();

    expect($reminder->fresh()->status)->toBe('canceled');
    Notification::assertNothingSent();
});

it('respects the user opting out of the app channel', function () {
    Notification::fake();

    $user = User::factory()->create(['notification_preferences' => ['app' => false, 'mail' => false]]);
    ReminderSchedule::create([
        'remindable_type' => 'test', 'remindable_id' => Illuminate\Support\Str::uuid7()->toString(),
        'user_id' => $user->id, 'kind' => 'test.due', 'send_at' => now()->subMinute(), 'channel' => 'app',
    ]);

    app(DispatchesReminders::class)->dispatchDue();

    Notification::assertNothingSent();
});
