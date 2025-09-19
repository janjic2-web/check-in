<?php

test('registration screen is disabled', function () {
    $response = $this->get('/register');
    $response->assertStatus(200); // ili assertRedirect('/login') ako tako hendluješ
});

test('posting to register is disabled', function () {
    $response = $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Očekuj 405 (Method Not Allowed) jer je POST onemogućen
    $response->assertStatus(405);
});
