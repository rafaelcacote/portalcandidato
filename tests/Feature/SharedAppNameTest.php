<?php

test('inertia shares configured application name', function () {
    config(['app.name' => 'Portal Candidato ProEnSP']);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('name', 'Portal Candidato ProEnSP'));
});
