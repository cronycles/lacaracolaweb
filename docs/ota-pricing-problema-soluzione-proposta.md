# Prezzi portali: problema e soluzione proposta

## Problema

Il sito interno e i portali esterni partono dalla stessa tariffa per notte, ma oggi il prezzo suggerito per Booking, Airbnb e HomeToGo include un recupero di costi fissi distribuito sulla durata minima di soggiorno, attualmente 3 notti.

Il recupero comprende:

- biancheria per 2 ospiti;
- il relativo gross-up fiscale;
- una parte legata al costo di pulizia.

Questo importo viene poi applicato a ogni notte. Di conseguenza, su un soggiorno di 11 notti il costo fisso viene recuperato circa 11/3 volte. Il prezzo del portale cresce troppo rispetto al prezzo diretto, soprattutto con 2 ospiti, anche se il costo reale di pulizia e biancheria esiste una sola volta per prenotazione.

Il problema non si risolve modificando soltanto il simulatore: la tariffa mostrata in `/admin/prezzi-portali` è quella che viene inserita manualmente nei portali e deve quindi produrre un totale realistico quando Booking o Airbnb la moltiplicano per le notti effettive.

## Obiettivo

Ridurre il totale visibile all'ospite e il rischio di abbandono dovuto a costi di servizio elevati, mantenendo comunque una remunerazione adeguata rispetto alla prenotazione diretta.

La durata media stimata delle prenotazioni è circa 7 notti e una voce pulizie di 50€ è considerata accettabile, mentre 100€ è già percepita come elevata.

## Soluzione proposta

La proposta è una soluzione intermedia:

1. mostrare una pulizia flat di **50€** sui tre portali;
2. spalmare il costo residuo nella tariffa/notte su una durata di riferimento di **7 notti**;
3. mantenere inizialmente gli stessi sconti settimanali e mensili del sito diretto: **10% da 7 notti** e **20% da 28 notti**;
4. lasciare invariata la commissione specifica di ciascun portale e il margine commerciale configurato;
5. verificare il risultato nel simulatore confrontando sempre il totale portale con il netto proprietario e con il prezzo diretto.

Con i valori di esempio usati nell'analisi (68€/notte, 2 ospiti, Booking al 16,5%, margine OTA del 5%), il risultato indicativo sarebbe:

|   Durata | Diretto | Portale proposto | Netto stimato dopo commissione |
| -------: | ------: | ---------------: | -----------------------------: |
|  3 notti |    385€ |             377€ |                           315€ |
|  7 notti |    610€ |             737€ |                           615€ |
| 11 notti |    855€ |           1.129€ |                           943€ |
| 28 notti |  1.705€ |           2.492€ |                         2.080€ |

I numeri sono una simulazione e devono essere ricalcolati con le tariffe stagionali e le impostazioni effettive prima dell'implementazione.

## Compromesso sui soggiorni brevi

Ottimizzare il modello sulla durata media di 7 notti significa accettare che un soggiorno minimo di 3 notti possa produrre un netto inferiore al prezzo diretto. Nell'esempio, la differenza è circa 70€.

Questo è il limite matematico di nascondere una spesa fissa nella tariffa/notte: se la si spalma su 7 notti, i soggiorni brevi recuperano meno del costo; se la si spalma su 3 notti, i soggiorni lunghi diventano troppo cari.

## Sconti dei portali

Per ora è preferibile mantenere gli sconti dei portali uguali a quelli del sito. Aumentare lo sconto settimanale o mensile sui portali ridurrebbe il prezzo per l'ospite, ma anche il netto del proprietario, e nell'esempio porterebbe il soggiorno di 7 notti sotto il diretto.

Dopo l'implementazione si potrà testare separatamente uno sconto OTA leggermente diverso, ma non dovrebbe essere il primo intervento.

## Nota tecnica importante

`pricing_min_nights` oggi ha due funzioni:

- stabilisce il minimo soggiorno consentito dal sito;
- viene usato dal modello OTA per ammortizzare i costi.

Non bisogna quindi impostarlo semplicemente a 7: si cambierebbe anche il minimo prenotabile del sito. Per implementare correttamente la proposta serve una nuova impostazione separata, ad esempio una **durata di ammortamento OTA**, con valore predefinito 7, lasciando `pricing_min_nights` a 3.

## Decisione da prendere prima dell'implementazione

La proposta da validare è:

- pulizie portali: **50€**;
- durata di ammortamento OTA: **7 notti**;
- sconti portali: inizialmente uguali al sito;
- accettazione consapevole del possibile margine negativo sui soggiorni esattamente di 3 notti;
- nuova impostazione separata per non modificare il minimo soggiorno del sito.
