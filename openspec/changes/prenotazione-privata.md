Dobbiamo implementare un nuovo sviluppo sul modulo di richiesta prenotazione per gestire legalmente e in sicurezza le prenotazioni dirette/private (extra portali come Booking o Airbnb) inferiori ai 30 giorni (noi facciamo gia per configurazione apartmment.php max_night a 28 quindi ok)

### Obiettivo:
Aggiungere una checkbox obbligatoria di accettazione legale subito sopra il bottone "Invia richiesta" nel modulo dei contatti/prenotazione. Se la checkbox non è spuntata, il bottone deve essere disabilitato e il modulo non deve poter essere inviato.

### Requisiti Tecnici e Internazionalizzazione (i18n):
1. La modifica impatta il componente del modulo di richiesta prenotazione. Identifica il file corretto basandoti sulla struttura del progetto.
2. Il sistema deve supportare dinamicamente la lingua corrente della rotta o della sessione (es: `/it/`, `/en/`, `/es/`, `/de/`, `/fr/`).
3. Genera le chiavi di traduzione necessarie nei rispettivi file di lingua per i testi che seguono.
4. "Aggiorna anche il controller dell'invio mail in modo che inserisca nel testo della notifica (sia per il proprietario che per l'ospite) la conferma dell'avvenuta accettazione dei termini e del contratto."

### Struttura dei link dinamici:
Usa le funzioni di routing del progetto per fare in modo che i link puntino alla lingua corretta dell'utente:
- Pagina Regole della casa: `/{locale}/regole-casa`
- Pagina Condizioni Generali: `/{locale}/condizioni-generali-prenotazione`

### Testi da tradurre e implementare:

#### Lingua Italiana (IT)
- **Testo Checkbox:** "Accetto le [Regole della Casa](/{locale}/regole-casa) e lo [Schema di Contratto di Locazione Turistica Breve](/{locale}/condizioni-generali-prenotazione) per questo soggiorno. Sono consapevole che la prenotazione sarà vincolante solo dopo la conferma scritta del proprietario e che prima dell'arrivo sarà obbligatorio completare il check-in online inserendo i dati dei documenti d'identità per le autorità."

#### Lingua Inglese (EN)
- **Testo Checkbox:** "I accept the [House Rules](/{locale}/regole-casa) and the [Short-Term Tourist Lease Agreement Terms](/{locale}/condizioni-generali-prenotazione) for this stay. I acknowledge that the booking will only be binding upon written confirmation from the owner and that completing the online check-in with ID details for government compliance is mandatory prior to arrival."

#### Lingua Spagnola (ES)
- **Testo Checkbox:** "Acepto las [Normas de la Casa](/{locale}/regole-casa) y las [Condiciones del Contrato de Arrendamiento Turístico de Corta Duración](/{locale}/condizioni-generali-prenotazione) para esta estancia. Reconozco que la reserva solo será vinculante tras la confirmación por scritto del propietario y che antes de la llegada será obligatorio completar el registro de entrada en línea con los datos de identidad para las autoridades."


### Testo legale da inserire (te lo do in italiano e tu lo traduci poi alle altre lingue disponibili)
SCHEMA DI CONTRATTO DI LOCAZIONE AD USO TURISTICO
(Ai sensi dell'art. 1, comma 2, lett. c, della legge 9 dicembre 1998, n. 431)

1. Oggetto del Contratto e Finalità
Il Locatore concede in locazione breve per esclusive finalità turistiche all'Ospite (Conduttore), che accetta, l'immobile denominato "La Caracola", sito in Andora (SV), Via Aurelia 64, Int 3. Il presente contratto è regolato dagli articoli 1571 e seguenti del Codice Civile, dall'art. 53 del D.Lgs. 79/2011 e dall'art. 1, comma 2 lett. c) della Legge 431/98. È esclusa qualsiasi forma di sublocazione o concessione in godimento a terzi.

2. Perfezionamento della Prenotazione e Pagamento
La richiesta inviata dal sito web non costituisce prenotazione confermata. Il contratto si intende perfezionato e vincolante solo nel momento in cui:

Il Locatore conferma per iscritto (via email) la disponibilità dell'alloggio.

L'Ospite provvede al pagamento della caparra o del saldo nei termini e con le modalità indicate nella comunicazione di conferma.
In caso di mancato pagamento entro i termini stabiliti, la richiesta di prenotazione verrà automaticamente annullata.

3. Obbligo di Comunicazione Dati (AlloggiatiWeb - Questura)
In ottemperanza all'art. 109 del T.U.L.P.S., l'Ospite principale ha l'obbligo tassativo di compilare, prima dell'arrivo presso la struttura, il modulo di "Check-in Online" fornendo i dati anagrafici completi di tutti i soggiornanti e gli estremi di un documento d'identità valido del capogruppo. L'omissione, il rifiuto o la parzialità di tali dati precluderà tassativamente l'accesso all'immobile, senza alcun diritto al rimborso delle somme versate.

4. Deposito Cauzionale e Regole della Casa

Regole d'uso: L'Ospite si impegna a visionare e rispettare scrupolosamente le "Regole della Casa" pubblicate alla pagina ufficiale /{locale}/regole-casa, che costituiscono parte integrante e sostanziale del presente contratto.

Deposito e Penali: All'arrivo o in fase di check-in, l'Ospite è tenuto al versamento del deposito cauzionale nei termini, modalità e cifre specificati nelle "Regole della Casa" e nella mail di conferma. Lo smarrimento delle chiavi o la violazione delle norme condominiali comporteranno l'addebito dei costi extra e delle penali indicati nelle medesime Regole della Casa. La cauzione verrà restituita al check-out previa verifica dell'assenza di danni.

5. Recesso e Cancellazione

Cancellazione con Rimborso Totale: L’Ospite può cancellare la prenotazione fino a 14 giorni prima della data di check-in prevista (ora locale della struttura). In questo caso, l’intero importo versato per il soggiorno sarà integralmente rimborsato.

Cancellazione Tardiva e No-Show: In caso di cancellazione effettuata nei 14 giorni precedenti l’arrivo, o in caso di mancata presentazione (no-show), l’importo totale versato non sarà rimborsabile e verrà interamente trattenuto dal Locatore a titolo di penale per il recesso tardivo.

6. Foro Competente e Privacy
Per qualsiasi controversia derivante dall'interpretazione o esecuzione del presente contratto, sarà esclusivamente competente il Foro di Savona. I dati personali forniti verranno trattati nel rispetto del Regolamento UE 2016/679 (GDPR) per le sole finalità connesse all'esecuzione del contratto e agli adempimenti di legge.

FINE TESTO


### Check-in Online
il formulario di check-in online non ce l'ho ancora, nel senso che ho una sezione per l'invio dei dati a AlloggiatiWeb. bisognerebbe non duplicarlo ma fare in modo che sia replicabile un form che faccia inserire i dati degli ospiti, uguale ugale a quello che ho io in admin/guest-reporting in modo tale da riutilizzare il codice. gli ospiti potranno inserire i dati nello stesso identico modo. 
Tra l'altro il link che forniró sará legato alla prenotazione e dovrá essere con scandenza o cmq che si veda x tempo e con un id o un hash o qualcosa che non sia facilmente individuabile da altri. giusto? dammi idee
pero questo lo lasciamo per una fase 2? dividiamo questo sviluppo in 2 fasi? 

### Cosa devi fare adesso:
- darmi un plan di sviluppo per questo
- fammi tutte le domande del caso
- 
