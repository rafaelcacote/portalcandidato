<?php

test('inertia shares configured application name', function () {
    config(['app.name' => 'Portal Candidato ProEns']);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('name', 'Portal Candidato ProEns'));
});
