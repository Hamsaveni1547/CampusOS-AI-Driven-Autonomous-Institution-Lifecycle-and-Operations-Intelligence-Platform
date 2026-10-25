<?php

namespace App\Auth;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

/**
 * Keeps the API token and a snapshot of `/me` in the encrypted session.
 * The token never reaches the browser.
 */
class SessionAuth
{
    private const TOKEN = 'campus.token';

    private const EXPIRES = 'campus.token_expires_at';

    private const ME = 'campus.me';

    private const ME_AT = 'campus.me_refreshed_at';

    private const INSTITUTION = 'campus.institution';

    private const CHALLENGE = 'campus.mfa_challenge';

    public function __construct(private Session $session) {}

    public function check(): bool
    {
        return $this->token() !== null;
    }

    public function token(): ?string
    {
        $token = $this->session->get(self::TOKEN);

        if (! is_string($token) || $token === '') {
            return null;
        }

        $expiresAt = $this->session->get(self::EXPIRES);

        if (is_string($expiresAt) && Carbon::parse($expiresAt)->isPast()) {
            $this->forget();

            return null;
        }

        return $token;
    }

    /**
     * Store a successful sign-in (login or MFA verify response).
     *
     * @param  array<string, mixed>  $result  { access_token, expires_at, user }
     */
    public function signIn(array $result): void
    {
        $this->session->regenerate();
        $this->session->put(self::TOKEN, (string) $result['access_token']);
        $this->session->put(self::EXPIRES, $result['expires_at'] ?? null);
        $this->session->forget(self::CHALLENGE);
        $this->storeMe((array) ($result['user'] ?? []));

        $institution = $result['user']['institution']['id'] ?? null;
        $this->session->put(self::INSTITUTION, is_string($institution) ? $institution : null);
    }

    public function user(): ?CurrentUser
    {
        $me = $this->session->get(self::ME);

        return $this->check() && is_array($me) ? new CurrentUser($me) : null;
    }

    /**
     * @param  array<string, mixed>  $me
     */
    public function storeMe(array $me): void
    {
        $this->session->put(self::ME, $me);
        $this->session->put(self::ME_AT, now()->getTimestamp());
    }

    public function meIsStale(): bool
    {
        $refreshedAt = (int) $this->session->get(self::ME_AT, 0);

        return $refreshedAt === 0 || now()->getTimestamp() - $refreshedAt > (int) config('campusos.me_refresh_seconds');
    }

    public function markMeStale(): void
    {
        $this->session->put(self::ME_AT, 0);
    }

    public function institutionId(): ?string
    {
        $id = $this->session->get(self::INSTITUTION);

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function switchInstitution(string $institutionId): void
    {
        $this->session->put(self::INSTITUTION, $institutionId);
        $this->session->regenerate();
        $this->markMeStale();
    }

    /**
     * Pending second step of sign-in.
     *
     * @param  array<string, mixed>  $challenge  { challenge_token, challenge_expires_at, methods }
     */
    public function storeChallenge(array $challenge, string $login): void
    {
        $this->session->put(self::CHALLENGE, [
            'token' => (string) $challenge['challenge_token'],
            'expires_at' => $challenge['challenge_expires_at'] ?? null,
            'methods' => array_values((array) ($challenge['methods'] ?? [])),
            'login' => $login,
        ]);
    }

    /**
     * @return array{token: string, expires_at: string|null, methods: array<int, string>, login: string}|null
     */
    public function challenge(): ?array
    {
        $challenge = $this->session->get(self::CHALLENGE);

        if (! is_array($challenge) || ! isset($challenge['token'])) {
            return null;
        }

        if (is_string($challenge['expires_at'] ?? null) && Carbon::parse($challenge['expires_at'])->isPast()) {
            $this->session->forget(self::CHALLENGE);

            return null;
        }

        /** @var array{token: string, expires_at: string|null, methods: array<int, string>, login: string} */
        return $challenge;
    }

    public function forgetChallenge(): void
    {
        $this->session->forget(self::CHALLENGE);
    }

    /**
     * Sign out locally (the API token is revoked separately by the logout call).
     */
    public function forget(): void
    {
        $this->session->forget([self::TOKEN, self::EXPIRES, self::ME, self::ME_AT, self::INSTITUTION, self::CHALLENGE]);
    }
}
