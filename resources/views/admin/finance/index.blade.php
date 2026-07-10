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
                            <th style="text-align:center">Allegati</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php $dialogId = 'att-dialog-' . $movement['model_type'] . '-' . $movement['model_id']; @endphp
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
                                <td style="text-align:center;white-space:nowrap">
                                    @php $attCount = $movement['attachments']->count(); @endphp
                                    <button type="button"
                                            onclick="document.getElementById('{{ $dialogId }}').showModal()"
                                            class="btn btn--outline btn--sm"
                                            style="{{ $attCount > 0 ? 'color:#1565c0;border-color:#1565c0;font-weight:700' : 'color:#6b7f89' }}"
                                            title="Gestisci allegati">
                                        📎{{ $attCount > 0 ? ' ' . $attCount : '' }}
                                    </button>
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
                    {!! $totals['utilities'] != 0 ? '−&nbsp;' : '' !!}€&nbsp;{{ number_format($totals['utilities'], 2, ',', '.') }}
                </div>
                <div class="stat-card__label">Utenze {{ $year }}</div>
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

@push('dialogs')
    {{-- Attachment dialogs — one per unique model (entries + bookings) --}}
    @php
        // Collect unique (model_type, model_id) combinations to avoid duplicate dialogs
        // (e.g. a booking can appear as cleaning + linen in the same year)
        $renderedDialogs = [];
    @endphp
    @foreach ($movements as $movement)
        @php
            $key = $movement['model_type'] . '-' . $movement['model_id'];
            if (in_array($key, $renderedDialogs)) continue;
            $renderedDialogs[] = $key;
            $dialogId = 'att-dialog-' . $key;
        @endphp
        <dialog id="{{ $dialogId }}" style="border:none;border-radius:10px;padding:0;max-width:520px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18)">
            <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e0e8ed;display:flex;align-items:center;justify-content:space-between">
                <div style="font-weight:700;font-size:1rem;color:#1a2e3a">
                    Allegati — {{ $movement['category_label'] }}
                    <span style="font-weight:400;font-size:.8rem;color:#6b7f89;margin-left:.4rem">
                        {{ $movement['date']->format('d/m/Y') }}
                    </span>
                </div>
                <button type="button" onclick="this.closest('dialog').close()"
                        style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6b7f89;line-height:1">×</button>
            </div>

            <div style="padding:1rem 1.5rem">
                {{-- Existing attachments --}}
                @if ($movement['attachments']->isEmpty())
                    <p style="color:#6b7f89;font-size:.875rem;margin:0 0 1rem">Nessun allegato.</p>
                @else
                    <ul style="list-style:none;margin:0 0 1rem;padding:0;display:flex;flex-direction:column;gap:.4rem">
                        @foreach ($movement['attachments'] as $att)
                            <li style="display:flex;align-items:center;gap:.6rem;background:#f7fafb;border:1px solid #e0e8ed;border-radius:6px;padding:.5rem .75rem">
                                <span style="font-size:.85rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $att->original_name }}">
                                    {{ $att->original_name }}
                                </span>
                                <span style="font-size:.75rem;color:#6b7f89;white-space:nowrap">
                                    {{ $att->size ? round($att->size / 1024) . ' KB' : '' }}
                                </span>
                                <a href="{{ route('admin.finance.attachments.download', $att) }}"
                                   class="btn btn--outline btn--sm" style="white-space:nowrap">Scarica</a>
                                <form method="POST"
                                      action="{{ route('admin.finance.attachments.destroy', $att) }}"
                                      onsubmit="return confirm('Eliminare {{ $att->original_name }}?')"
                                      style="display:inline;margin:0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger btn--sm">✕</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Upload new attachment --}}
                <form method="POST"
                      action="{{ route('admin.finance.attachments.store', ['type' => $movement['model_type'], 'id' => $movement['model_id']]) }}"
                      enctype="multipart/form-data"
                      style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                    @csrf
                    <input type="file" name="attachment"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                           style="font-size:.85rem;flex:1;min-width:0"
                           required>
                    <button type="submit" class="btn btn--primary btn--sm" style="white-space:nowrap">Carica</button>
                </form>
                <p style="font-size:.75rem;color:#6b7f89;margin:.4rem 0 0">PDF, immagini, Word, Excel — max 10 MB</p>
            </div>
        </dialog>
    @endforeach

    <script>
        // Close dialog when clicking on backdrop
        document.querySelectorAll('dialog').forEach(function (d) {
            d.addEventListener('click', function (e) {
                if (e.target === d) d.close();
            });
        });
    </script>
@endpush
