<?php

declare(strict_types=1);

it('exposes the health check endpoint', function () {
    $this->get('/up')->assertOk();
});
