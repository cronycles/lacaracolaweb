@extends('layouts.admin')

@section('title', 'Contabilità ' . $year)

@section('content')
    {{-- Hero: Saldo totale + cards spese/incassi --}}
    @php $heroColor = $globalBalance >= 0 ? '#2e7d32' : '#c62828'; @endphp
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:stretch;margin-bottom:1.5rem">
        <div style="flex:1 1 200px;background:{{ $globalBalance >= 0 ? '#f1f8f1' : '#fff5f5' }};border:2px solid {{ $heroColor }};border-radius:10px;padding:1.25rem 1.5rem;display:flex;align-items:center">
            <div>
                <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:{{ $heroColor }};opacity:.75;margin-bottom:.2rem">Saldo totale</div>
                <div style="font-size:2.4rem;font-weight:800;color:{{ $heroColor }};line-height:1">
                    € {{ number_format($globalBalance, 2, ',', '.') }}
                </div>
            </div>
        </div>
        <div style="flex:1 1 160px;background:#fff8f0;border:2px solid #92400e;border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:center">
            <div>
                <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#92400e;opacity:.8;margin-bottom:.2rem">Pulizie da pagare</div>
                <div style="font-size:1.8rem;font-weight:800;color:#92400e;line-height:1">
                    {!! $cleaningUnpaid != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($cleaningUnpaid, 2, ',', '.') }}
                </div>
            </div>
        </div>
        <div style="flex:1 1 160px;background:#fff8f0;border:2px solid #92400e;border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:center">
            <div>
                <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#92400e;opacity:.8;margin-bottom:.2rem">Biancheria da pagare</div>
                <div style="font-size:1.8rem;font-weight:800;color:#92400e;line-height:1">
                    {!! $linenUnpaid != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($linenUnpaid, 2, ',', '.') }}
                </div>
            </div>
        </div>
        <div style="flex:1 1 160px;background:#f0f4ff;border:2px solid #1565c0;border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:center">
            <div>
                <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#1565c0;opacity:.8;margin-bottom:.2rem">Posto auto da incassare</div>
                <div style="font-size:1.8rem;font-weight:800;color:#1565c0;line-height:1">
                    {!! $parkingUnpaid != 0 ? '+&nbsp;' : '' !!}€&nbsp;{{ number_format($parkingUnpaid, 2, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Tab nav: Contabilità / Dichiarazione redditi --}}
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <a href="{{ route('admin.finance.index') }}"
           class="btn btn--sm btn--primary">
            Contabilità
        </a>
        <a href="{{ route('admin.tax-declaration.index') }}"
           class="btn btn--sm btn--outline">
            Dichiarazione redditi
        </a>
    </div>

    {{-- Toolbar: anno + nuova voce --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div style="font-size:1rem;font-weight:700;color:#1a2e3a">Contabilità</div>
        <div style="display:flex;gap:.4rem;align-items:center">
            @foreach ($availableYears as $y)
                <a href="{{ route('admin.finance.index', ['year' => $y]) }}"
                   class="btn btn--sm {{ $y == $year ? 'btn--primary' : 'btn--outline' }}">
                    {{ $y }}
                </a>
            @endforeach
        </div>
        <div style="margin-left:auto">
            <a href="{{ route('admin.finance.create') }}" class="btn btn--primary btn--sm">+ Nuova voce</a>
        </div>
    </div>

    {{-- Unified movements list --}}
    <div class="a-card" style="margin-bottom:1.5rem">
        <div class="a-card__title">Voci {{ $year }}</div>

        @if ($movements->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna voce per {{ $year }}.</p>
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
                            <th style="text-align:right">Saldo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
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
                                    {{ $movement['type'] === 'income' ? '+' : '-' }}&nbsp;€&nbsp;{{ number_format($movement['amount'], 2, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:700;color:{{ $movement['running_balance'] >= 0 ? '#1976d2' : '#c62828' }}">
                                    € {{ number_format($movement['running_balance'], 2, ',', '.') }}
                                </td>
                                <td style="white-space:nowrap">
                                    @if ($movement['source'] === 'entry')
                                        <a href="{{ route('admin.finance.edit', $movement['entry']) }}" class="btn btn--outline btn--sm">Modifica</a>
                                        <form method="POST" action="{{ route('admin.finance.destroy', $movement['entry']) }}" style="display:inline"
                                              onsubmit="return confirm('Eliminare questa voce?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                        </form>
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

    {{-- Secondary: yearly analysis --}}
    <div style="margin-bottom:1rem;margin-top:2rem;padding-top:1.25rem;border-top:1px solid #e0e8ed">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7f89;margin-bottom:1rem">Analisi {{ $year }}</div>
        <div class="stats-grid" style="margin-bottom:1.25rem">
            <div class="stat-card" style="border-left:4px solid #2e7d32">
                <div class="stat-card__number" style="color:#2e7d32;font-size:1.3rem">
                    {!! $totals['income'] != 0 ? '+&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['income'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Ingressi {{ $year }}</div>
                <div style="font-size:.75rem;color:#6b7f89;margin-top:.25rem">
                    prenotazioni: € {{ number_format($totals['booking_income'], 2, ',', '.') }}
                    @if ($totals['extra_income'] > 0)
                        &nbsp;+ extra: € {{ number_format($totals['extra_income'], 2, ',', '.') }}
                    @endif
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #c62828">
                <div class="stat-card__number" style="color:#c62828;font-size:1.3rem">
                    {!! $totals['expenses'] != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['expenses'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Uscite {{ $year }}</div>
                <div style="font-size:.75rem;color:#6b7f89;margin-top:.25rem">
                    prenotazioni: € {{ number_format($totals['booking_expenses'], 2, ',', '.') }}
                    @if ($totals['extra_expenses'] > 0)
                        &nbsp;+ extra: € {{ number_format($totals['extra_expenses'], 2, ',', '.') }}
                    @endif
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid {{ $totals['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
                <div class="stat-card__number" style="color:{{ $totals['balance'] >= 0 ? '#1976d2' : '#c62828' }};font-size:1.3rem">
                    € {{ number_format($totals['balance'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Saldo {{ $year }}</div>
            </div>
            <div class="stat-card" style="border-left:4px solid #c62828">
                <div class="stat-card__number" style="color:#c62828;font-size:1.3rem">
                    {!! $totals['cleaning_paid'] != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['cleaning_paid'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Pulizie pagate {{ $year }}</div>
            </div>
            <div class="stat-card" style="border-left:4px solid #c62828">
                <div class="stat-card__number" style="color:#c62828;font-size:1.3rem">
                    {!! $totals['linen_paid'] != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['linen_paid'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Biancheria pagata {{ $year }}</div>
            </div>
        </div>
    </div>

    {{-- Monthly breakdown --}}
    <div class="a-card">
        <div class="a-card__title" style="font-size:.95rem">Andamento mensile {{ $year }}</div>
        <div style="overflow-x:auto">
            <table class="a-table" style="font-size:.875rem">
                <thead>
                    <tr>
                        <th>Mese</th>
                        <th style="text-align:right">Ingressi</th>
                        <th style="text-align:right">Uscite</th>
                        <th style="text-align:right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $monthNames = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
                    @endphp
                    @foreach ($monthlyData as $m => $data)
                        @php $balance = $data['income'] - $data['expenses']; @endphp
                        <tr style="{{ ($data['income'] == 0 && $data['expenses'] == 0) ? 'opacity:.45' : '' }}">
                            <td>{{ $monthNames[$m] }}</td>
                            <td style="text-align:right;color:#2e7d32">
                                @if ($data['income'] > 0) € {{ number_format($data['income'], 2, ',', '.') }} @else — @endif
                            </td>
                            <td style="text-align:right;color:#c62828">
                                @if ($data['expenses'] > 0) € {{ number_format($data['expenses'], 2, ',', '.') }} @else — @endif
                            </td>
                            <td style="text-align:right;font-weight:600;color:{{ $balance > 0 ? '#2e7d32' : ($balance < 0 ? '#c62828' : '#6b7f89') }}">
                                @if ($data['income'] > 0 || $data['expenses'] > 0)
                                    € {{ number_format($balance, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
