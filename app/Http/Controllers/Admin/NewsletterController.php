<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterDelivery;
use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSuppression;
use App\Models\NewsletterTemplate;
use App\Models\Person;
use App\Services\Newsletter\NewsletterBlockDocument;
use App\Services\Newsletter\NewsletterRecipientResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterBlockDocument $blocks,
        private readonly NewsletterRecipientResolver $recipients,
    ) {}

    public function index(Request $request): View
    {
        $query = $this->subscriberQuery($request);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->withCount('bookings')
            ->orderBy('last_name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletter', [
            'subscribers' => $subscribers,
            'templates' => NewsletterTemplate::query()->latest()->get(),
            'campaigns' => NewsletterCampaign::query()->latest()->withCount(['deliveries as sent_deliveries' => fn ($q) => $q->where('status', 'sent')])->get(),
            'suppressions' => NewsletterSuppression::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.newsletter-form', ['template' => new NewsletterTemplate(['content_it' => [], 'content_en' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = $this->saveTemplate(new NewsletterTemplate, $request);
        return redirect()->route('admin.newsletter')->with('success', "Template '{$template->title}' salvato.");
    }

    public function edit(NewsletterTemplate $newsletterTemplate): View
    {
        return view('admin.newsletter-form', ['template' => $newsletterTemplate]);
    }

    public function update(Request $request, NewsletterTemplate $newsletterTemplate): RedirectResponse
    {
        $template = $this->saveTemplate($newsletterTemplate, $request);
        return redirect()->route('admin.newsletter')->with('success', "Template '{$template->title}' aggiornato.");
    }

    public function archive(NewsletterTemplate $newsletterTemplate): RedirectResponse
    {
        $newsletterTemplate->update(['archived_at' => now()]);
        return redirect()->back()->with('success', 'Template archiviato.');
    }

    public function preview(NewsletterTemplate $newsletterTemplate): Response
    {
        abort_if($newsletterTemplate->archived_at !== null, 404);
        return response($this->renderMail($newsletterTemplate, config('apartment.email')));
    }

    public function testSend(NewsletterTemplate $newsletterTemplate): RedirectResponse
    {
        $recipient = config('newsletter.test_recipient');
        try {
            Mail::to($recipient)->send(new NewsletterMail($this->campaignFromTemplate($newsletterTemplate), $recipient));
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->back()->withErrors(['test_send' => 'Invio di prova non riuscito: '.$exception->getMessage()]);
        }

        return redirect()->back()->with('success', "Email di prova inviata a {$recipient}.");
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:10240']]);
        $path = $request->file('image')->store('newsletters', 'public');
        return response()->json(['path' => $path, 'url' => Storage::disk('public')->url($path)]);
    }

    public function send(Request $request, NewsletterTemplate $newsletterTemplate): RedirectResponse
    {
        abort_if($newsletterTemplate->archived_at !== null, 404);
        $request->validate([
            'person_ids' => ['nullable', 'array'],
            'person_ids.*' => ['integer'],
            'select_all' => ['nullable', 'boolean'],
            'manual_emails' => ['nullable', 'string'],
        ]);

        $people = $request->boolean('select_all')
            ? $this->subscriberQuery($request)->get()
            : Person::query()->where('newsletter_subscribed', true)->whereIn('id', $request->input('person_ids', []))->get();
        $manualEmails = preg_split('/[\s,;]+/', (string) $request->input('manual_emails', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $recipientRows = $this->recipients->resolve($people, $manualEmails);
        if ($recipientRows->isEmpty()) {
            return redirect()->back()->withErrors(['recipients' => 'Seleziona almeno un destinatario valido.'])->withInput();
        }

        $campaign = DB::transaction(function () use ($newsletterTemplate, $recipientRows, $request): NewsletterCampaign {
            $campaign = NewsletterCampaign::create([
                'newsletter_template_id' => $newsletterTemplate->id,
                'created_by' => $request->user()->id,
                'title' => $newsletterTemplate->title,
                'subject' => $newsletterTemplate->subject,
                'content_it' => $newsletterTemplate->content_it,
                'content_en' => $newsletterTemplate->content_en,
                'status' => 'pending',
                'total_recipients' => $recipientRows->count(),
            ]);
            foreach ($recipientRows as $recipient) {
                $campaign->deliveries()->create($recipient + ['newsletter_campaign_id' => $campaign->id]);
            }
            return $campaign;
        });

        $campaign->update(['status' => 'sending', 'started_at' => now()]);
        $campaign->deliveries()->each(fn (NewsletterDelivery $delivery) => SendNewsletterDelivery::dispatch($delivery->id));

        return redirect()->route('admin.newsletter')->with('success', "Campagna avviata per {$campaign->total_recipients} destinatari.");
    }

    public function confirmSend(Request $request, NewsletterTemplate $newsletterTemplate): View|RedirectResponse
    {
        abort_if($newsletterTemplate->archived_at !== null, 404);
        if ($request->isMethod('GET')) {
            return redirect()->route('admin.newsletter')->withErrors([
                'recipients' => 'La conferma di invio è scaduta. Seleziona di nuovo i destinatari.',
            ]);
        }

        $request->validate([
            'person_ids' => ['nullable', 'array'],
            'person_ids.*' => ['integer'],
            'select_all' => ['nullable', 'boolean'],
            'manual_emails' => ['nullable', 'string'],
        ]);

        $people = $request->boolean('select_all')
            ? $this->subscriberQuery($request)->get()
            : Person::query()->where('newsletter_subscribed', true)->whereIn('id', $request->input('person_ids', []))->get();
        $manualEmails = preg_split('/[\s,;]+/', (string) $request->input('manual_emails', ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $recipientRows = $this->recipients->resolve($people, $manualEmails);
        if ($recipientRows->isEmpty()) {
            return redirect()->back()->withErrors(['recipients' => 'Seleziona almeno un destinatario valido.'])->withInput();
        }

        return view('admin.newsletter-confirm', [
            'template' => $newsletterTemplate,
            'recipients' => $recipientRows,
            'manualEmails' => $manualEmails,
            'requestData' => $request->only(['person_ids', 'select_all', 'q', 'filter']),
        ]);
    }

    public function campaign(NewsletterCampaign $newsletterCampaign): View
    {
        return view('admin.newsletter-campaign', ['campaign' => $newsletterCampaign->load('deliveries.person')]);
    }

    public function campaignPreview(NewsletterCampaign $newsletterCampaign): Response
    {
        return response($this->renderMail($newsletterCampaign, config('apartment.email')));
    }

    public function retry(NewsletterCampaign $newsletterCampaign): RedirectResponse
    {
        $deliveries = $newsletterCampaign->deliveries()->where('status', 'failed')->get();
        foreach ($deliveries as $delivery) {
            $suppressed = NewsletterSuppression::query()->where('email', $delivery->email)->exists()
                || ($delivery->person && (! $delivery->person->newsletter_subscribed || $delivery->person->newsletter_opted_out));
            if ($suppressed) {
                $delivery->update(['status' => 'failed', 'error' => 'Destinatario disiscritto; riattivarlo prima del reinvio.']);
                continue;
            }
            $delivery->update(['status' => 'pending', 'error' => null]);
            SendNewsletterDelivery::dispatch($delivery->id);
        }
        $newsletterCampaign->update(['status' => 'sending', 'started_at' => now(), 'completed_at' => null]);
        return redirect()->back()->with('success', "Ritentativo avviato per {$deliveries->count()} destinatari.");
    }

    public function reactivate(Request $request): RedirectResponse
    {
        $email = $this->recipients->normalize($request->input('email'));
        abort_if($email === null, 404);
        NewsletterSuppression::query()->where('email', $email)->delete();
        Person::query()->whereRaw('LOWER(email) = ?', [$email])->update(['newsletter_opted_out' => false, 'newsletter_subscribed' => true, 'newsletter_subscribed_at' => now()]);
        return redirect()->back()->with('success', 'Indirizzo riattivato.');
    }

    public function toggle(Person $person): RedirectResponse
    {
        if ($person->newsletter_subscribed) {
            $person->unsubscribeFromNewsletter();
        } else {
            $person->subscribeToNewsletter();
        }

        return redirect()->back()->with('success', 'Iscrizione aggiornata.');
    }

    public function unsubscribe(Request $request): View
    {
        $email = $this->recipients->normalize($request->input('email'));
        if ($email === null) {
            return view('public.newsletter-unsubscribed', ['success' => false, 'message' => 'Il link di disiscrizione non è valido.']);
        }

        Person::query()->whereRaw('LOWER(email) = ?', [$email])->update([
            'newsletter_subscribed' => false,
            'newsletter_subscribed_at' => null,
            'newsletter_opted_out' => true,
        ]);
        NewsletterSuppression::query()->updateOrCreate(
            ['email' => $email],
            ['suppressed_at' => now()],
        );

        return view('public.newsletter-unsubscribed', ['success' => true, 'message' => 'Non riceverai più le nostre newsletter. / You will no longer receive our newsletters.']);
    }

    private function saveTemplate(NewsletterTemplate $template, Request $request): NewsletterTemplate
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'content_it' => ['nullable', 'json'],
            'content_en' => ['nullable', 'json'],
        ]);
        $contentIt = $this->blocks->validate(json_decode($validated['content_it'] ?? '[]', true));
        $contentEn = $this->blocks->validate(json_decode($validated['content_en'] ?? '[]', true));
        $template->fill([
            'created_by' => $template->created_by ?? $request->user()->id,
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'content_it' => $contentIt,
            'content_en' => $contentEn,
            'archived_at' => null,
        ])->save();
        return $template;
    }

    private function subscriberQuery(Request $request): Builder
    {
        $query = Person::query()->where('newsletter_subscribed', true);
        if ($request->input('filter') === 'guests') {
            $query->has('bookings');
        } elseif ($request->input('filter') === 'non_guests') {
            $query->doesntHave('bookings');
        }
        if ($search = $request->input('q')) {
            $query->where(fn (Builder $q) => $q->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        return $query;
    }

    private function campaignFromTemplate(NewsletterTemplate $template): NewsletterCampaign
    {
        return new NewsletterCampaign([
            'title' => $template->title,
            'subject' => $template->subject,
            'content_it' => $template->content_it,
            'content_en' => $template->content_en,
        ]);
    }

    private function renderMail(NewsletterTemplate|NewsletterCampaign $source, string $recipient): string
    {
        return (new NewsletterMail($source instanceof NewsletterCampaign ? $source : $this->campaignFromTemplate($source), $recipient))->render();
    }
}
