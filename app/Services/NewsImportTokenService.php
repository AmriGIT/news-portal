<?php

namespace App\Services;

use App\Models\ImportToken;
use App\Models\User;
use Illuminate\Support\Str;

class NewsImportTokenService
{
    /**
     * @param  array<int, string>  $abilities
     * @return array{token: ImportToken, plain_text_token: string}
     */
    public function create(string $name, User $creator, ?User $user = null, array $abilities = ['news:import'], ?\DateTimeInterface $expiresAt = null): array
    {
        $plainTextToken = 'bin_'.Str::random(64);

        $token = ImportToken::query()->create([
            'name' => $name,
            'token_hash' => $this->hash($plainTextToken),
            'abilities' => array_values(array_unique($abilities)),
            'created_by' => $creator->id,
            'user_id' => ($user ?: $creator)->id,
            'expires_at' => $expiresAt ?: now()->addDays((int) config('news-import.token_expiry_days', 90)),
        ]);

        return [
            'token' => $token,
            'plain_text_token' => $plainTextToken,
        ];
    }

    public function findUsableToken(?string $plainTextToken): ?ImportToken
    {
        if (blank($plainTextToken)) {
            return null;
        }

        $token = ImportToken::query()
            ->with('user')
            ->where('token_hash', $this->hash((string) $plainTextToken))
            ->first();

        if (! $token?->isUsable()) {
            return null;
        }

        return $token;
    }

    public function markUsed(ImportToken $token): void
    {
        $token->forceFill(['last_used_at' => now()])->save();
    }

    private function hash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
