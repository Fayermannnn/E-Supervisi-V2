<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

afterEach(function () {
    URL::forceScheme(null);
});

it('leaves URL generation alone by default', function () {
    expect(url('/ping'))->toStartWith('http://');
});

it('forces https URL generation when security.force_https is enabled', function () {
    config(['security.force_https' => true]);
    app()->getProvider(AppServiceProvider::class)->boot();

    expect(url('/ping'))->toStartWith('https://');
    expect(route('login'))->toStartWith('https://');
});
