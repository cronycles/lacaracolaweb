@extends('layouts.admin')

@section('title', 'Dichiarazione dei redditi ' . $year)

@section('content')
    {{-- Tab nav: Contabilità / Dichiarazione redditi --}}
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <a href="{{ route('admin.finance.index') }}"
           class="btn btn--sm btn--outline">
            Contabilità
        </a>
        <a href="{{ route('admin.tax-declaration.index') }}"
           class="btn btn--sm btn--primary">
            Dichiarazione redditi
        </a>
    </div>

    {{-- Toolbar: year tabs --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div style="font-size:1rem;font-weight:700;color:#1a2e3a">Dichiarazione redditi</div>
        <div style="display:flex;gap:.4rem;align-items:center">
            @foreach ($availableYears as $y)
                <a href="{{ route('admin.tax-declaration.index', ['year' => $y]) }}"
                   class="btn btn--sm {{ $y == $year ? 'btn--primary' : 'btn--outline' }}">
                    {{ $y }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Summary boxes: Totale entrate + Totale uscite --}}
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:stretch;margin-bottom:1.5rem">
        <div class="stat-card" style="flex:1 1 200px;border-left:4px solid #2e7d32">
            <div class="stat-card__number" style="color:#2e7d32;font-size:1.6rem">
                {!! $totals['income'] != 0 ? '+&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['income'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Totale entrate {{ $year }}</div>
            <div style="font-size:.75rem;color:#6b7f89;margin-top:.25rem">Solo voci flaggate e incassate</div>
        </div>
        <div class="stat-card" style="flex:1 1 200px;border-left:4px solid #c62828">
            <div class="stat-card__number" style="color:#c62828;font-size:1.6rem">
                {!! $totals['expenses'] != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['expenses'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Totale uscite {{ $year }}</div>
            <div style="font-size:.75rem;color:#6b7f89;margin-top:.25rem">Solo voci flaggate e pagate</div>
        </div>
    </div>

    {{-- Movements table --}}
    <div class="a-card">
        <div class="a-card__title">Voci {{ $year }}</div>

        @if ($movements->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna voce flaggata per {{ $year }}.</p>
        @else
            <div style="overflow-x:auto">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Descrizione</th>
                            <th style="text-align:right">Importo</th>
                            <th>Stato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr style="{{ ! $movement['included'] ? 'opacity:.6' : '' }}">
                                <td style="white-space:nowrap">{{ $movement['date']->format('d/m/Y') }}</td>
                                <td>
                                    @if ($movement['type'] === 'income')
                                        <span class="badge" style="background:#e8f5e9;color:#2e7d32">Ingresso</span>
                                    @else
                                        <span class="badge" style="background:#ffebee;color:#c62828">Uscita</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">{{ $movement['category_label'] }}</td>
                                <td style="color:#6b7f89;font-size:.85rem">{{ $movement['description'] ?? '—' }}</td>
                                <td style="text-align:right;font-weight:600;color:{{ $movement['type'] === 'income' ? '#2e7d32' : '#c62828' }}">
                                    {{ $movement['type'] === 'income' ? '+' : '−' }}&nbsp;€&nbsp;{{ number_format($movement['amount'], 2, ',', '.') }}
                                </td>
                                <td style="white-space:nowrap">
                                    @if ($movement['included'])
                                        <span class="badge badge--paid">Incluso</span>
                                    @else
                                        <span class="badge badge--unpaid">Da pagare</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    @if ($movement['source'] === 'entry' && $movement['entry'])
                                        <a href="{{ route('admin.finance.edit', $movement['entry']) }}" class="btn btn--outline btn--sm">Modifica</a>
                                    @elseif ($movement['booking_id'])
                                        <a href="{{ route('admin.bookings.show', $movement['booking_id']) }}" class="btn btn--outline btn--sm">Prenotazione</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
