@extends('layouts.app')

@section('title', 'Newsletter')

@section('content')
    <main style="max-width:600px;margin:4rem auto;padding:0 1rem;text-align:center">
        <h1>{{ $success ? 'Iscrizione aggiornata' : 'Link non valido' }}</h1>
        <p>{{ $message }}</p>
    </main>
@endsection