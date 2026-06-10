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
                            <th style="text-align:center">Allegati</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php $dialogId = 'tax-att-dialog-' . $movement['model_type'] . '-' . $movement['model_id']; @endphp
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

@push('dialogs')
    @php $renderedDialogs = []; @endphp
    @foreach ($movements as $movement)
        @php
            $key = $movement['model_type'] . '-' . $movement['model_id'];
            if (in_array($key, $renderedDialogs)) continue;
            $renderedDialogs[] = $key;
            $dialogId = 'tax-att-dialog-' . $key;
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
        document.querySelectorAll('dialog').forEach(function (d) {
            d.addEventListener('click', function (e) {
                if (e.target === d) d.close();
            });
        });
    </script>
@endpush
