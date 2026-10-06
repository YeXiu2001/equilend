<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\StarterPage;

beforeEach(function () {
    $this->auth = User::factory()->create();
});

it('renders the starter page component', function () {
    Livewire::actingAs($this->auth)
        ->test(StarterPage::class)
        ->assertOk()
        ->assertViewIs('livewire.starter-page')
        ->assertSee('Starter Page')
        ->assertSee('Add Something');
});

it('renders the soft customized flex layout for the card body', function () {
    $html = Livewire::actingAs($this->auth)
        ->test(StarterPage::class)
        ->html();

    expect($html)->toContain('flex items-center justify-between');
});

it('can visit the starter page route', function () {
    $this->actingAs($this->auth)
        ->get(route('starter-page'))
        ->assertOk()
        ->assertSeeLivewire(StarterPage::class);
});
