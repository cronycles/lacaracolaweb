<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\NewsletterDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewsletterDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $deliveryId) {}

    public function handle(): void
    {
        $delivery = NewsletterDelivery::with('campaign')->find($this->deliveryId);
        if (! $delivery || $delivery->status === 'sent') {
            return;
        }

        $delivery->update(['status' => 'sending', 'error' => null]);

        try {
            Mail::to($delivery->email)->send(new NewsletterMail($delivery->campaign, $delivery->email));
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'error' => $exception->getMessage()]);
            throw $exception;
        } finally {
            $this->refreshCampaignCounts($delivery->newsletter_campaign_id);
        }
    }

    private function refreshCampaignCounts(int $campaignId): void
    {
        $campaign = $this->campaignQuery($campaignId);
        if (! $campaign) {
            return;
        }

        $sent = $campaign->deliveries()->where('status', 'sent')->count();
        $failed = $campaign->deliveries()->where('status', 'failed')->count();
        $pending = $campaign->deliveries()->whereIn('status', ['pending', 'sending'])->count();

        $campaign->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $pending > 0 ? 'sending' : ($failed > 0 ? 'completed_with_errors' : 'sent'),
            'completed_at' => $pending === 0 ? now() : null,
        ]);
    }

    private function campaignQuery(int $campaignId): ?\App\Models\NewsletterCampaign
    {
        return \App\Models\NewsletterCampaign::query()->find($campaignId);
    }
}