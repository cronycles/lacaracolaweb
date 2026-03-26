@extends('layouts.app')

@section('title', __('app.experiences_title') . ' — ' . config('apartment.name'))

@section('content')

<section class="section">
    <div class="container">
        <h1 class="section-title">{{ __('app.experiences_title') }}</h1>
        <p class="section-subtitle">{{ __('app.experiences_subtitle') }}</p>

        <div class="experiences-grid">

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏖️ A 2 minuti</p>
                    <h3 class="card__title">Le Spiagge di Andora</h3>
                    <p class="card__text">Spiagge sabbiose e ghiaiose direttamente raggiungibili a piedi. Stabilimenti balneari, acque cristalline.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏘️ 20 min</p>
                    <h3 class="card__title">Cervo</h3>
                    <p class="card__text">Uno dei borghi medievali più belli della Liguria. Vista panoramica sul mare, centro storico affascinante.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🌊 25 min</p>
                    <h3 class="card__title">Alassio</h3>
                    <p class="card__text">La regina della Riviera ligure. Spiaggia lunga e sabbiosa, shopping, vita notturna, ottima cucina.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🏰 45 min</p>
                    <h3 class="card__title">Finalborgo</h3>
                    <p class="card__text">Borgo medievale patrimonio UNESCO. Mura cinquecentesche, carruggi caratteristici e paradiso dell'arrampicata sportiva.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🎢 30 min</p>
                    <h3 class="card__title">Le Caravelle</h3>
                    <p class="card__text">Il più grande parco acquatico della Liguria, perfetto per famiglie con bambini.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🎲 1h15</p>
                    <h3 class="card__title">Montecarlo</h3>
                    <p class="card__text">Il Principato di Monaco con il famoso casinò, il Grand Prix e il porto degli yacht di lusso.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🌸 45 min</p>
                    <h3 class="card__title">Sanremo</h3>
                    <p class="card__text">La città dei fiori, famosa per il Festival della Canzone Italiana e il mercato dei fiori. Centro storico medievale "La Pigna".</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🇫🇷 1h30</p>
                    <h3 class="card__title">Nizza & Mentone</h3>
                    <p class="card__text">La Francia è a soli 90 minuti. Nizza con la Promenade des Anglais, Mentone con i suoi giardini di agrumi.</p>
                </div>
            </div>

            <div class="card">
                <div class="card__body">
                    <p class="card__label">🧗 Borghi</p>
                    <h3 class="card__title">Borghi dell'Entroterra</h3>
                    <p class="card__text">Triora (il paese delle streghe), Apricale, Seborga, Bussana Vecchia e Diano Castello: arte, storia e natura.</p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- SEO rich text about the area --}}
<section class="seo-section" aria-label="SEO area">
    <div class="container seo-section__content">
        <h2>Cosa fare ad Andora e nella Riviera Ligure</h2>
        <p>
            Andora si trova nella <strong>Riviera Ligure di Ponente</strong>, a metà strada tra Savona e la Costa Azzurra.
            È il punto di partenza ideale per esplorare le eccellenze del territorio: borghi medievali, parchi naturali,
            spiagge incontaminate e città d'arte come <strong>Genova</strong>, <strong>Sanremo</strong> e <strong>Nizza</strong>.
        </p>
        <h3>Arrampicata sportiva in Liguria</h3>
        <p>
            La zona di <strong>Finalborgo</strong> e la Val di Ponci sono riconosciuti a livello internazionale come
            paradiso dell'arrampicata su falesia, con oltre 1000 vie di tutti i livelli.
        </p>
    </div>
</section>

@endsection
