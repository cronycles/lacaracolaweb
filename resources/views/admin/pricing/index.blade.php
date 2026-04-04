@extends('layouts.admin')

@section('title', 'Regole di prezzo')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
        <h1 style="font-size:1.1rem;font-weight:700">Regole di prezzo</h1>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a href="{{ route('admin.stay-discounts.index') }}" class="btn btn--outline">Sconti soggiorno</a>
            <a href="{{ route('admin.pricing.create') }}" class="btn btn--primary">+ Nuova regola</a>
        </div>
    </div>

    <div class="a-card">
        @if ($rules->isEmpty())
            <p style="color:#6b7f89;font-size:.875rem">Nessuna regola di prezzo configurata.</p>
        @else
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Periodo ricorrente</th>
                        <th>€ / notte</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules as $rule)
                        <tr>
                            <td style="font-weight:600">{{ $rule->period_label }}</td>
                            <td>{{ number_format($rule->price_euros, 2, ',', '.') }} €</td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.pricing.edit', $rule) }}" class="btn btn--outline btn--sm">Modifica</a>
                                <form method="POST" action="{{ route('admin.pricing.destroy', $rule) }}"
                                      style="display:inline" onsubmit="return confirm('Eliminare questa regola?')">
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
