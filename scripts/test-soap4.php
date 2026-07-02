<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$utente   = $_ENV['GUEST_REPORTING_UTENTE'] ?? '';
$password = $_ENV['GUEST_REPORTING_PASSWORD'] ?? '';
$wsKey    = $_ENV['GUEST_REPORTING_WS_KEY'] ?? '';

$client = new SoapClient(
    'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl',
    ['trace' => true, 'exceptions' => false, 'encoding' => 'UTF-8', 'soap_version' => SOAP_1_1, 'cache_wsdl' => WSDL_CACHE_DISK]
);

$tr    = $client->GenerateToken(['Utente' => $utente, 'Password' => $password, 'WsKey' => $wsKey]);
$token = (string) ($tr->GenerateTokenResult->token ?? '');
echo "Token OK: " . strlen($token) . " chars\n\n";

function w(string &$s, string $val, int $start, int $len): void
{
    $s = substr_replace($s, str_pad(substr($val, 0, $len), $len), $start, $len);
}

function testRecord(SoapClient $client, string $utente, string $token, string $label, string $record): void
{
    $elenco = ['string' => [$record]];
    $result = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => $elenco]);
    if ($result instanceof SoapFault) {
        echo "$label → FAULT: " . $result->getMessage() . "\n";
        return;
    }
    $esito = $result->TestResult->esito ?? false;
    $detail = $result->result->Dettaglio->EsitoOperazioneServizio ?? null;
    $error = $detail ? $detail->ErroreDettaglio : '??';
    $valid = $result->result->SchedineValide ?? 0;
    echo "$label → esito=" . ($esito ? 'true' : 'false') . " valid=$valid error=$error\n";
}

$today = date('dmY'); // GGMMAAAA
$future = date('dmY', strtotime('+7 days'));

// TEST 1: tipo 16, date at pos 2 (8 chars), cognome at 10
$r1 = str_repeat(' ', 168);
w($r1, '16', 0, 2);
w($r1, $today, 2, 8);   // data arrivo at pos 2
w($r1, 'ROSSI', 10, 50);
w($r1, 'MARIO', 60, 30);
w($r1, 'M', 90, 1);
w($r1, '15061985', 91, 8);
w($r1, '201', 103, 3);   // stato nascita
w($r1, '201', 106, 3);   // cittadinanza
w($r1, 'PASOR', 109, 5); // tipo doc (5 chars)
w($r1, 'AB123456789', 114, 15);
w($r1, '201', 129, 4);   // luogo ril
testRecord($client, $utente, $token, "T1: tipo16, date@2, cognome@10, doc5ch@109", $r1);

// TEST 2: same but tipo 18
$r2 = $r1;
w($r2, '18', 0, 2);
testRecord($client, $utente, $token, "T2: tipo18, date@2, cognome@10, doc5ch@109", $r2);

// TEST 3: tipo 16, no data_arrivo (cognome at pos 2 like OLD format), but 5-char doc
$r3 = str_repeat(' ', 168);
w($r3, '16', 0, 2);
w($r3, 'ROSSI', 2, 50);
w($r3, 'MARIO', 52, 30);
w($r3, 'M', 82, 1);
w($r3, '15061985', 83, 8);
w($r3, '201', 97, 3);
w($r3, '201', 100, 3);
w($r3, 'PASOR', 103, 5); // 5-char doc type
w($r3, 'AB123456789', 108, 15);
w($r3, '201', 123, 4);
testRecord($client, $utente, $token, "T3: tipo16, no date, cognome@2, doc5ch@103", $r3);

// TEST 4: tipo 16, date at pos 2, then DATE PARTENZA at 10, then cognome at 18
$r4 = str_repeat(' ', 168);
w($r4, '16', 0, 2);
w($r4, $today, 2, 8);     // data arrivo at pos 2
w($r4, $future, 10, 8);   // data partenza at pos 10
w($r4, 'ROSSI', 18, 50);
w($r4, 'MARIO', 68, 30);
w($r4, 'M', 98, 1);
w($r4, '15061985', 99, 8);
w($r4, '201', 111, 3);   // stato nascita
w($r4, '201', 114, 3);   // cittadinanza
w($r4, 'PASOR', 117, 5); // tipo doc
w($r4, 'AB123456789', 122, 15);
w($r4, '201', 137, 4);   // luogo ril
testRecord($client, $utente, $token, "T4: tipo16, arrivo@2, partenza@10, cognome@18, doc5ch@117", $r4);

echo "\nDone.\n";
