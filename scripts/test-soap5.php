<?php

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->safeLoad();

$utente = $_ENV['GUEST_REPORTING_UTENTE'] ?? '';
$password = $_ENV['GUEST_REPORTING_PASSWORD'] ?? '';
$wsKey = $_ENV['GUEST_REPORTING_WS_KEY'] ?? '';

$client = new SoapClient(
    'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl',
    ['trace' => true, 'exceptions' => false, 'encoding' => 'UTF-8', 'soap_version' => SOAP_1_1, 'cache_wsdl' => WSDL_CACHE_DISK]
);

$tr = $client->GenerateToken(['Utente' => $utente, 'Password' => $password, 'WsKey' => $wsKey]);
$token = (string) ($tr->GenerateTokenResult->token ?? '');
echo 'Token OK: '.strlen($token)." chars\n\n";

function w(string &$s, string $val, int $start, int $len): void
{
    $s = substr_replace($s, str_pad(substr($val, 0, $len), $len), $start, $len);
}

function testRecord(SoapClient $client, string $utente, string $token, string $label, string $record): void
{
    $elenco = ['string' => [$record]];
    $result = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => $elenco]);
    if ($result instanceof SoapFault) {
        echo "$label → FAULT: ".$result->getMessage()."\n";

        return;
    }
    $esito = $result->TestResult->esito ?? false;
    $detail = $result->result->Dettaglio->EsitoOperazioneServizio ?? null;
    $error = $detail ? $detail->ErroreDettaglio : '??';
    $valid = $result->result->SchedineValide ?? 0;
    echo "$label → esito=".($esito ? 'true' : 'false')." valid=$valid error=$error\n";
}

$r4 = str_repeat(' ', 168);

w($r4, '16', 0, 2);               // Tipo Alloggiato (16 = Ospite Singolo)
w($r4, '01/07/2026', 2, 10);      // Data Arrivo (Formato gg/mm/aaaa) --> non puo essere nel futuro, ma puo essere al massimo 1 giorno dopo
w($r4, '02', 12, 2);              // Numero Giorni di Permanenza (Es. 2 giorni)

w($r4, 'ROSSI', 14, 50);          // Cognome
w($r4, 'MARIO', 64, 30);          // Nome
w($r4, '1', 94, 1);               // Sesso (1 = Maschio, 2 = Femmina)

w($r4, '15/06/1985', 95, 10);     // Data Nascita (Formato gg/mm/aaaa)

w($r4, '407010025', 105, 9);      // Comune Nascita (407010025 = GENOVA, vedi tabella comuni.csv)
w($r4, 'GE', 114, 2);             // Provincia Nascita (GE) --> 2 caratteri

w($r4, '100000100', 116, 9);      // Stato Nascita (100000100 = ITALIA) --> preso dalla tabella stati.csv
w($r4, '100000100', 125, 9);      // Cittadinanza (100000100 = ITALIA) --> preso dalla tabella stati.csv

w($r4, 'IDENT', 134, 5);          // Tipo Documento --> preso dalla tabella documenti.csv
w($r4, 'AB1234567', 139, 20);     // Numero Documento (fino a 20 caratteri)
w($r4, '407010025', 159, 9);      // Luogo Rilascio Documento (407010025 = GENOVA) --> preso dalla tabella comuni.csv ma puo anche essere messo uno stato dalla tabella stati.csv

// Taglio e controllo finale della stringa a esattamente 168 caratteri
$r4 = substr(str_pad($r4, 168, ' '), 0, 168);

echo 'Lunghezza stringa r4: '.strlen($r4)." caratteri\n";

testRecord($client, $utente, $token, 'Test finale con codici estratti dalle tabelle', $r4);

// ---------------------------------------------------------------------------
// Test ospite STRANIERO (tipo 16 — singolo, tedesco, nato in Germania)
// Stato nascita / cittadinanza = GERMANIA (100000216)
// Tipo doc PASOR (passaporto), luogo rilascio = stato 100000216
// ---------------------------------------------------------------------------
$rStraniero = str_repeat(' ', 168);

w($rStraniero, '16', 0, 2);                // Tipo Alloggiato: ospite singolo
w($rStraniero, '01/07/2026', 2, 10);       // Data Arrivo
w($rStraniero, '03', 12, 2);              // Giorni permanenza
w($rStraniero, 'MUELLER', 14, 50);         // Cognome
w($rStraniero, 'HANS', 64, 30);            // Nome
w($rStraniero, '1', 94, 1);               // Sesso: maschio
w($rStraniero, '20/03/1990', 95, 10);      // Data Nascita
// Comune e Provincia Nascita: blanks (nascita estera)
w($rStraniero, '100000216', 116, 9);       // Stato Nascita: GERMANIA
w($rStraniero, '100000216', 125, 9);       // Cittadinanza: GERMANIA
w($rStraniero, 'PASOR', 134, 5);           // Tipo Documento: passaporto ordinario
w($rStraniero, 'C00000001', 139, 20);      // Numero Documento
w($rStraniero, '100000216', 159, 9);       // Luogo Rilascio: GERMANIA (stato)

$rStraniero = substr(str_pad($rStraniero, 168, ' '), 0, 168);
echo "\nLunghezza stringa straniero: ".strlen($rStraniero)." caratteri\n";

testRecord($client, $utente, $token, 'Test ospite straniero (DE, tipo 16, PASOR)', $rStraniero);

echo "\nDone.\n";
