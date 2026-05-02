@extends('layouts.admin')

@section('title', 'Blocco — ' . ucfirst($block->reason))

@section('content')
    <div style="max-width:680px">
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;align-items:center">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn--outline btn--sm">← Prenotazioni</a>
            <a href="{{ route('admin.bookings.edit-block', $block) }}" class="btn btn--primary btn--sm">Modifica</a>
            <form method="POST" action="{{ route('admin.bookings.destroy-block', $block) }}"
                  onsubmit="return confirm('Eliminare questo blocco?')" style="margin-left:auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger btn--sm">Elimina</button>
            </form>
        </div>

        <div class="a-card">
            <div class="a-card__title">Dati blocco</div>
            <table class="a-table">
                <tbody>
                    <tr>
                        <th style="width:160px">Tipo</th>
                        <td>
                            <span class="badge badge--{{ $block->reason }}">
                                {{ $block->reason === 'owner' ? 'Uso proprietario' : 'Manutenzione' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Data inizio</th>
                        <td>{{ $block->start_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Data fine</th>
                        <td>{{ $block->end_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Giorni</th>
                        <td>{{ $block->start_date->diffInDays($block->end_date) }}</td>
                    </tr>
                    @if ($block->notes)
                        <tr>
                            <th>Note</th>
                            <td style="white-space:pre-line">{{ $block->notes }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Creato il</th>
                        <td>{{ $block->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
