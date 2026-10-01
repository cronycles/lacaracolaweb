<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use App\Models\NewsletterSuppression;
use App\Models\Person;
use Illuminate\Support\Collection;

class NewsletterRecipientResolver
{
    /** @return Collection<int, array{email: string, person_id: int|null}> */
    public function resolve(Collection $people, array $manualEmails = []): Collection
    {
        $suppressed = NewsletterSuppression::query()->pluck('email')->map(fn (string $email): string => $this->normalize($email))->flip();
        $recipients = collect();

        foreach ($people as $person) {
            $email = $this->normalize($person->email);
            if ($email === null || $person->newsletter_opted_out || $suppressed->has($email)) {
                continue;
            }
            $recipients->put($email, ['email' => $email, 'person_id' => $person->id]);
        }

        foreach ($manualEmails as $email) {
            $normalized = $this->normalize($email);
            if ($normalized !== null && ! $suppressed->has($normalized)) {
                $recipients->put($normalized, ['email' => $normalized, 'person_id' => $recipients->get($normalized)['person_id'] ?? null]);
            }
        }

        return $recipients->values();
    }

    public function normalize(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}