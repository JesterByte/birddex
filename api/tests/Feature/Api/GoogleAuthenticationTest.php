
<?php

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function mockGoogleToken(?array $payload): void
{
    $mock = Mockery::mock(GoogleTokenVerifier::class);

    /** @var MockInterface|GoogleTokenVerifier $mock */
    $mock->shouldReceive('verify')
        ->once()
        ->with('test-google-id-token')
        ->andReturn($payload);

    app()->instance(GoogleTokenVerifier::class, $mock);
}

function validGooglePayload(array $overrides = []): array
{
    return array_merge([
        'sub' => 'google-user-123',
        'email' => 'bird@example.com',
        'email_verified' => true,
        'name' => 'BirdDex Tester',
        'picture' => 'https://example.com/avatar.jpg',
    ], $overrides);
}

it('rejects a missing ID token', function () {
    $response = $this->postJson('/api/auth/google', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('id_token');

    $this->assertDatabaseCount('users', 0);
});

it('rejects an invalid Google ID token', function () {
    mockGoogleToken(null);

    $response = $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'message' => 'Invalid or unverified Google account.',
        ]);

    $this->assertDatabaseCount('users', 0);
});

it('registers a new Google user and issues a Sanctum token', function () {
    mockGoogleToken(validGooglePayload());

    $response = $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Authenticated successfully.')
        ->assertJsonPath('user.email', 'bird@example.com')
        ->assertJsonPath('user.name', 'BirdDex Tester')
        ->assertJsonPath(
            'user.avatar',
            'https://example.com/avatar.jpg'
        )
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'avatar'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'bird@example.com',
        'google_id' => 'google-user-123',
    ]);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('reuses an existing Google account instead of duplicating it', function () {
    $user = User::factory()->create([
        'name' => 'Existing BirdDex User',
        'email' => 'bird@example.com',
        'google_id' => 'google-user-123',
    ]);

    mockGoogleToken(validGooglePayload());

    $response = $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('rejects an unverified Google email', function () {
    mockGoogleToken(validGooglePayload([
        'email_verified' => false,
    ]));

    $response = $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ]);

    $response->assertUnauthorized();

    $this->assertDatabaseCount('users', 0);
});

it('requires authentication to view the current user', function () {
    $this->getJson('/api/user')
        ->assertUnauthorized();
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create([
        'name' => 'BirdDex Tester',
        'email' => 'bird@example.com',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'bird@example.com');
});

it('revokes the current Sanctum token on logout', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test-token')->plainTextToken;

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully.',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('does not silently link an existing email account', function () {
    $existingUser = User::factory()->create([
        'email' => 'bird@example.com',
        'google_id' => null,
    ]);

    mockGoogleToken(validGooglePayload());

    $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ])
        ->assertConflict()
        ->assertJsonPath('code', 'account_linking_required');

    $this->assertDatabaseHas('users', [
        'id' => $existingUser->id,
        'email' => 'bird@example.com',
        'google_id' => null,
    ]);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('rejects an expired Sanctum token', function () {
    $user = User::factory()->create();

    $token = $user->createToken(
        'expired-token',
        ['*'],
        now()->subMinute()
    )->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

it('returns JSON for unauthenticated API requests without an Accept header', function () {

    $this->call('GET', '/api/user')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

it('rate limits repeated Google authentication attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/google', [
            'id_token' => '',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/auth/google', [
        'id_token' => '',
    ])->assertTooManyRequests();
});

it('allows an existing Google identity to sign in', function () {
    $user = User::factory()->create([
        'email' => 'bird@example.com',
        'google_id' => 'google-user-123',
    ]);

    mockGoogleToken(validGooglePayload());

    $this->postJson('/api/auth/google', [
        'id_token' => 'test-google-id-token',
    ])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});
