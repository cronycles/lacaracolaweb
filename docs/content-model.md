# Modello Contenuti e Dati (Iniziale)

## 1. Principio guida
Usare file di configurazione per contenuti stabili e database per dati operativi dinamici.

## 2. Contenuti in configurazione (MVP)
- Branding (nome, colori, claim).
- Dati fissi immobile (indirizzo, caratteristiche, servizi).
- Testi pagine pubbliche per lingua.
- Regole della casa.
- Useful places.
- Liste stabili usate nei form admin quando devono restare facilmente estendibili senza toccare le view (es. paesi ISO selezionabili per gli ospiti).
- SEO metadata base per pagina/lingua.

## 3. Dati in database (MVP)
- Utente admin.
- Disponibilita calendario.
- Regole pricing (per intervallo date).
- Soggiorno minimo (global o per regola).
- Persone/contatti.
- Ospiti (ruolo di persona come guest).
- Soggiorni/prenotazioni associate agli ospiti.
- Iscritti newsletter (flag e consenso).

## 4. Distinzione Persona vs Ospite
- Persona: entita anagrafica generale (contatto).
- Ospite: persona che ha soggiornato almeno una volta.
- Newsletter: include sia ex ospiti sia iscritti non ospiti, filtrabili.

## 5. Query operative chiave
- Ospiti presenti tra due date.
- Ospiti presenti in mesi specifici (es. marzo-aprile-maggio).
- Numero soggiorni per ospite.
- Disponibilita residua per intervallo richiesto.

## 6. Evoluzioni pianificate
- Parser email automatico per nuove prenotazioni.
- Modalita booking switch in area privata.
- Eventuali integrazioni esterne future.
