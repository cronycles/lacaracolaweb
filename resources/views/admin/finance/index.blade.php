@extends('layouts.admin')

@section('title', 'Contabilità ' . $year)

@section('content')
    {{-- Year filter --}}
    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div style="font-size:1.1rem;font-weight:700;color:#1a2e3a">Contabilità</div>
        <div style="display:flex;gap:.5rem;align-items:center">
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

    {{-- Totals --}}
    <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card" style="border-left:4px solid #2e7d32">
            <div class="stat-card__number" style="color:#2e7d32">
                € {{ number_format($totals['income'], 2, ',', '.') }}
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
            <div class="stat-card__number" style="color:#c62828">
                € {{ number_format($totals['expenses'], 2, ',', '.') }}
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
            <div class="stat-card__number" style="color:{{ $totals['balance'] >= 0 ? '#1976d2' : '#c62828' }}">
                € {{ number_format($totals['balance'], 2, ',', '.') }}
            </div>
            <div class="stat-card__label">Saldo {{ $year }}</div>
        </div>
    </div>

    {{-- Monthly breakdown --}}
    <div class="a-card" style="margin-bottom:1.5rem">
        <div class="a-card__title">Andamento mensile {{ $year }}</div>
        <div style="overflow-x:auto">
            <table class="a-table">
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

    {{-- Extra entries list --}}
    <div class="a-card">
        <div class="a-card__title">Voci extra {{ $year }}</div>

        @if ($entries->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna voce extra per {{ $year }}.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Descrizione</th>
                        <th style="text-align:right">Importo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td style="white-space:nowrap">{{ $entry->entry_date->format('d/m/Y') }}</td>
                            <td>
                                @if ($entry->isIncome())
                                    <span class="badge" style="background:#e8f5e9;color:#2e7d32">Ingresso</span>
                                @else
                                    <span class="badge" style="background:#ffebee;color:#c62828">Uscita</span>
                                @endif
                            </td>
                            <td>{{ $entry->category }}</td>
                            <td style="color:#6b7f89;font-size:.85rem">{{ $entry->description ?? '—' }}</td>
                            <td style="text-align:right;font-weight:600;color:{{ $entry->isIncome() ? '#2e7d32' : '#c62828' }}">
                                {{ $entry->isIncome() ? '+' : '-' }} € {{ number_format((float)$entry->amount, 2, ',', '.') }}
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.finance.edit', $entry) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.finance.destroy', $entry) }}" style="display:inline"
                                      onsubmit="return confirm('Eliminare questa voce?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
