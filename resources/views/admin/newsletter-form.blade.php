@extends('layouts.admin')

@section('title', $template->exists ? 'Modifica template newsletter' : 'Nuovo template newsletter')

@section('content')
    <a href="{{ route('admin.newsletter') }}" class="btn btn--outline btn--sm">← Newsletter</a>
    <div class="a-card" style="margin-top:1rem">
        <form method="POST" action="{{ $template->exists ? route('admin.newsletter.templates.update', $template) : route('admin.newsletter.templates.store') }}">
            @csrf @if($template->exists) @method('PUT') @endif
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
                <div><label class="form-label" for="title">Nome interno</label><input class="form-input" id="title" name="title" required value="{{ old('title', $template->title) }}"></div>
                <div><label class="form-label" for="subject">Oggetto email</label><input class="form-input" id="subject" name="subject" required value="{{ old('subject', $template->subject) }}"></div>
            </div>
            <p style="color:#6b7f89;font-size:.875rem;margin:1rem 0">Editor limitato: testo, titoli, corsivo, grassetto, sottolineato, elenchi, immagini (JPG, PNG, WebP, GIF o BMP fino a 10 MB) e separatori. La parte CTA e disiscrizione viene aggiunta automaticamente.</p>
            <div data-newsletter-editor data-upload-url="{{ route('admin.newsletter.images.store') }}">
                <section>
                    <h2>Italiano</h2>
                    <div data-newsletter-quill="it"></div>
                    <p data-newsletter-status="it" style="color:#6b7f89;font-size:.8rem;min-height:1.2em"></p>
                    <input type="hidden" name="content_it" data-newsletter-html="it" value="{{ old('content_it', $template->content_it ?? '') }}">
                </section>
                <section style="margin-top:1.5rem">
                    <h2>English</h2>
                    <div data-newsletter-quill="en"></div>
                    <p data-newsletter-status="en" style="color:#6b7f89;font-size:.8rem;min-height:1.2em"></p>
                    <input type="hidden" name="content_en" data-newsletter-html="en" value="{{ old('content_en', $template->content_en ?? '') }}">
                </section>
            </div>
            @error('content')<p class="form-error">{{ $message }}</p>@enderror
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.25rem"><button class="btn btn--primary" type="submit">Salva template</button>@if($template->exists)<a class="btn btn--outline" target="_blank" rel="noopener" href="{{ route('admin.newsletter.templates.preview', $template) }}">Anteprima</a>@endif</div>
        </form>
    </div>
@endsection
