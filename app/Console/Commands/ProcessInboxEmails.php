<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\EmailParseLog;
use App\Services\BookingCreationService;
use App\Services\BookingEmailParser;
use Illuminate\Console\Command;
use Throwable;
use Webklex\IMAP\Facades\Client;

/**
 * Polls the configured IMAP inbox, parses booking emails and auto-creates bookings.
 * Idempotent: messages already logged (by IMAP UID) are skipped.
 *
 * Usage:
 *   php artisan emails:parse-bookings            # normal run
 *   php artisan emails:parse-bookings --dry-run  # parse only, no DB writes
 */
class ProcessInboxEmails extends Command
{
    protected $signature = 'emails:parse-bookings
                                {--dry-run : Parse emails without saving bookings or logs}';

    protected $description = 'Fetch unread emails from IMAP inbox and auto-create bookings';

    public function handle(BookingCreationService $creationService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No database writes will occur.');
        }

        // ── Connect to IMAP ──────────────────────────────────────────────────
        try {
            $client = Client::account('default');
            $client->connect();
        } catch (Throwable $e) {
            $this->error('IMAP connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── Fetch unseen messages ────────────────────────────────────────────
        $folder   = $client->getFolder('INBOX');
        $messages = $folder->query()->unseen()->get();

        $this->info("Found {$messages->count()} unread message(s).");

        $processed = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($messages as $message) {
            $uid     = (string) $message->getUid();
            $fromRaw = $message->getFrom();
            $fromAddress = ($fromRaw->count() > 0) ? (string) $fromRaw->first()->mail : '';
            $subject = (string) $message->getSubject();

            $receivedAt = null;
            try {
                $receivedAt = $message->getDate()->toDate();
            } catch (Throwable) {
                // leave null if date cannot be parsed
            }

            // ── Skip already-processed UIDs ──────────────────────────────────
            if (!$dryRun && EmailParseLog::where('message_uid', $uid)->exists()) {
                $this->line("  UID $uid: already in log, skipping.");
                $skipped++;
                continue;
            }

            // ── Extract plain-text body ──────────────────────────────────────
            $rawText = $message->getTextBody();
            if (empty(trim($rawText))) {
                $rawText = strip_tags($message->getHTMLBody());
            }

            // ── Parse email fields ───────────────────────────────────────────
            $parser = new BookingEmailParser();
            $parsed = $parser->parse($rawText);

            // ── Validate minimum required fields ─────────────────────────────
            if (empty($parsed['checkin']) || empty($parsed['checkout']) || empty($parsed['first_name'])) {
                $reason = "Could not extract required fields (dates/name). from={$fromAddress} subject={$subject}";
                $this->warn("  UID $uid: skipped — $reason");

                if (!$dryRun) {
                    EmailParseLog::create([
                        'message_uid'   => $uid,
                        'from_address'  => $fromAddress,
                        'subject'       => $subject,
                        'received_at'   => $receivedAt,
                        'status'        => 'skipped',
                        'error_message' => $reason,
                    ]);
                    $message->setFlag('Seen');
                }
                $skipped++;
                continue;
            }

            // ── Skip duplicate bookings by external reference ─────────────────
            if (!empty($parsed['external_ref'])) {
                $exists = Booking::where('external_ref', $parsed['external_ref'])->exists();
                if ($exists) {
                    $reason = "Duplicate: booking with external_ref={$parsed['external_ref']} already exists.";
                    $this->line("  UID $uid: duplicate — $reason");

                    if (!$dryRun) {
                        EmailParseLog::create([
                            'message_uid'   => $uid,
                            'from_address'  => $fromAddress,
                            'subject'       => $subject,
                            'received_at'   => $receivedAt,
                            'status'        => 'duplicate',
                            'error_message' => $reason,
                        ]);
                        $message->setFlag('Seen');
                    }
                    $skipped++;
                    continue;
                }
            }

            if ($dryRun) {
                $this->info(sprintf(
                    '  UID %s: [DRY RUN] would create booking for %s %s %s → %s (source: %s)',
                    $uid,
                    $parsed['first_name'],
                    $parsed['last_name'],
                    $parsed['checkin'],
                    $parsed['checkout'],
                    $parsed['source'],
                ));
                $processed++;
                continue;
            }

            // ── Create booking ───────────────────────────────────────────────
            try {
                $booking = $creationService->createFromParsed([
                    'first_name'   => $parsed['first_name'],
                    'last_name'    => $parsed['last_name'],
                    'email'        => $parsed['email'] ?: null,
                    'phone'        => $parsed['phone'] ?: null,
                    'checkin'      => $parsed['checkin'],
                    'checkout'     => $parsed['checkout'],
                    'adults'       => $parsed['adults'],
                    'children'     => $parsed['children'],
                    'babies'       => 0,
                    'source'       => $parsed['source'],
                    'external_ref' => $parsed['external_ref'] ?: null,
                    'notes'        => null,
                ]);

                EmailParseLog::create([
                    'message_uid'  => $uid,
                    'from_address' => $fromAddress,
                    'subject'      => $subject,
                    'received_at'  => $receivedAt,
                    'status'       => 'success',
                    'booking_id'   => $booking->id,
                ]);

                $message->setFlag('Seen');

                $this->info(sprintf(
                    '  UID %s: booking #%d created for %s %s (%s → %s)',
                    $uid,
                    $booking->id,
                    $parsed['first_name'],
                    $parsed['last_name'],
                    $parsed['checkin'],
                    $parsed['checkout'],
                ));
                $processed++;

            } catch (Throwable $e) {
                $error = $e->getMessage();
                $this->error("  UID $uid: error — $error");

                EmailParseLog::create([
                    'message_uid'   => $uid,
                    'from_address'  => $fromAddress,
                    'subject'       => $subject,
                    'received_at'   => $receivedAt,
                    'status'        => 'error',
                    'error_message' => $error,
                ]);
                $failed++;
            }
        }

        $this->info("Done. Processed: $processed, Skipped: $skipped, Failed: $failed");

        return self::SUCCESS;
    }
}
