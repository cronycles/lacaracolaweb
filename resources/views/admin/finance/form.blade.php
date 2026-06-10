@extends('layouts.admin')

@section('title', $entry->exists ? 'Modifica voce' : 'Nuova voce')

@section('content')
    <div style="max-width:560px">
        <div style="margin-bottom:1rem">
            <a href="{{ route('admin.finance.index') }}" class="btn btn--outline btn--sm">← Torna alla contabilità</a>
        </div>

        <div class="a-card">
            <div class="a-card__title">{{ $entry->exists ? 'Modifica voce' : 'Nuova voce' }}</div>

            <form method="POST"
                  action="{{ $entry->exists ? route('admin.finance.update', $entry) : route('admin.finance.store') }}">
                @csrf
                @if ($entry->exists)
                    @method('PUT')
                @endif

                {{-- Type --}}
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <div style="display:flex;gap:1rem;margin-top:.35rem">
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                            <input type="radio" name="type" value="income"
                                   @checked(old('type', $entry->type ?? 'income') === 'income')>
                            <span style="color:#2e7d32;font-weight:600">Ingresso</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                            <input type="radio" name="type" value="expense"
                                   @checked(old('type', $entry->type) === 'expense')>
                            <span style="color:#c62828;font-weight:600">Uscita</span>
                        </label>
                    </div>
                    @error('type') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Category --}}
                <div class="form-group">
                    <label class="form-label" for="category">Categoria</label>
                    <select id="category" name="category" class="form-input" required>
                        <option value="" disabled @selected(old('category', $entry->category) === '')>— Seleziona categoria —</option>
                        @foreach(config('finance.categories') as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $entry->category) === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('category') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Amount + Date --}}
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="amount">Importo (€)</label>
                        <input type="number" id="amount" name="amount" class="form-input"
                               value="{{ old('amount', $entry->amount) }}"
                               min="0.01" max="99999.99" step="0.01" required>
                        @error('amount') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="entry_date">Data</label>
                        <input type="date" id="entry_date" name="entry_date" class="form-input"
                               value="{{ old('entry_date', $entry->entry_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required>
                        @error('entry_date') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="form-group">
                    <label class="form-label" for="description">Descrizione (opzionale)</label>
                    <textarea id="description" name="description" class="form-textarea" rows="3"
                              maxlength="1000">{{ old('description', $entry->description) }}</textarea>
                    @error('description') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                {{-- Tax declaration --}}
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="hidden"   name="tax_declaration" value="0">
                        <input type="checkbox" name="tax_declaration" value="1" id="tax_declaration" class="form-checkbox"
                               @checked(old('tax_declaration', $entry->tax_declaration ?? false))>
                        <span class="form-label" style="margin:0">Includi nella dichiarazione dei redditi</span>
                    </label>
                    @error('tax_declaration') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex;gap:.75rem;margin-top:.5rem">
                    <button type="submit" class="btn btn--primary">
                        {{ $entry->exists ? 'Salva modifiche' : 'Aggiungi voce' }}
                    </button>
                    <a href="{{ route('admin.finance.index') }}" class="btn btn--outline">Annulla</a>
                </div>
            </form>
        </div>

        {{-- Allegati — solo per voci già salvate --}}
        @if ($entry->exists)
            <div class="a-card" style="margin-top:1.25rem">
                <div class="a-card__title">Allegati</div>

                @if ($entry->attachments->isEmpty())
                    <p style="color:#6b7f89;font-size:.875rem;margin-bottom:1rem">Nessun allegato.</p>
                @else
                    <ul style="list-style:none;margin:0 0 1rem;padding:0;display:flex;flex-direction:column;gap:.4rem">
                        @foreach ($entry->attachments as $att)
                            <li style="display:flex;align-items:center;gap:.6rem;background:#f7fafb;border:1px solid #e0e8ed;border-radius:6px;padding:.5rem .75rem">
                                <span style="font-size:.875rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $att->original_name }}">
                                    {{ $att->original_name }}
                                </span>
                                <span style="font-size:.75rem;color:#6b7f89;white-space:nowrap">
                                    {{ $att->size ? round($att->size / 1024) . ' KB' : '' }}
                                </span>
                                <a href="{{ route('admin.finance.attachments.download', $att) }}"
                                   class="btn btn--outline btn--sm">Scarica</a>
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
                      action="{{ route('admin.finance.attachments.store', ['type' => 'entry', 'id' => $entry->id]) }}"
                      enctype="multipart/form-data"
                      style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                    @csrf
                    <input type="file" name="attachment"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                           style="font-size:.875rem;flex:1;min-width:0"
                           required>
                    <button type="submit" class="btn btn--primary btn--sm">Carica allegato</button>
                </form>
                <p style="font-size:.75rem;color:#6b7f89;margin:.4rem 0 0">PDF, immagini, Word, Excel — max 10 MB</p>
            </div>
        @endif
    </div>
@endsection
