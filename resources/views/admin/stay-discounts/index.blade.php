@extends('layouts.admin')

@section('title', 'Sconti soggiorno')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
        <h1 style="font-size:1.1rem;font-weight:700">Sconti soggiorno</h1>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="{{ route('admin.pricing.index') }}" class="btn btn--outline">← Prezzi base</a>
            <a href="{{ route('admin.stay-discounts.create') }}" class="btn btn--primary">+ Nuova regola sconto</a>
        </div>
    </div>

    <div class="a-card">
        <p style="font-size:.85rem;color:#6b7f89;margin-bottom:.9rem">
            Gli sconti si applicano al solo costo soggiorno (non alle pulizie), in base al numero totale di notti.
        </p>

        @if ($rules->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna regola sconto configurata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Intervallo notti</th>
                        <th>Sconto</th>
                        <th>Priorità</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr>
                            <td style="font-weight:600">{{ $rule->range_label }}</td>
                            <td>-{{ $rule->discount_percent }}%</td>
                            <td>{{ $rule->priority }}</td>
                            <td>
                                @if ($rule->is_active)
                                    <span class="badge badge--booked">attiva</span>
                                @else
                                    <span class="badge" style="background:#e5e7eb;color:#374151">disattiva</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.stay-discounts.edit', $rule) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.stay-discounts.destroy', $rule) }}"
                                      style="display:inline" onsubmit="return confirm('Eliminare questa regola sconto?')">
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
