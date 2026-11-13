<?php

declare(strict_types=1);

it('redirects to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
