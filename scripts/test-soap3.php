<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$utente = $_ENV['GUEST_REPORTING_UTENTE'] ?? '';
$password = $_ENV['GUEST_REPORTING_PASSWORD'] ?? '';
$wsKey = $_ENV['GUEST_REPORTING_WS_KEY'] ?? '';

$client = new SoapClient(
    'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl',
    ['trace' => true, 'exceptions' => true, 'encoding' => 'UTF-8', 'soap_version' => SOAP_1_1, 'cache_wsdl' => WSDL_CACHE_NONE]
);
$tr = $client->GenerateToken(['Utente' => $utente, 'Password' => $password, 'WsKey' => $wsKey]);
$token = (string)($tr->GenerateTokenResult->token ?? '');
echo "Token len=" . strlen($token) . "\n";

function w(string &$s, string $val, int $start, int $len): void {
    $s = substr_replace($s, str_pad(substr($val, 0, $len), $len), $start, $len);
}

function testRecord(SoapClient $c, string $utente, string $token, string $record, string $label): void {
    $r = $c->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => ['string' => [$record]]]);
    $detail = $r->result->Dettaglio->EsitoOperazioneServizio ?? null;
    $valid = $r->result->SchedineValide ?? 0;
    echo "$label (len=" . strlen($record) . ", valid=$valid): " . ($detail ? $detail->ErroreDettaglio : 'SUCCESSO!') . "\n";
}

// Test 1: Original layout (cognome at pos 2) with tipo 16, doc type PASOR (5 chars)
// Original layout: tipo(2) cognome(50) nome(30) sesso(1) data_nasc(8) comune(4) prov(2) stato(3) naz(3) tipo_doc(5) num_doc(15) luogo(4) filler(41)
// Total: 2+50+30+1+8+4+2+3+3+5+15+4+41 = 168
$r1 = str_repeat(' ', 168);
w($r1, '16', 0, 2);
w($r1, 'ROSSI', 2, 50);
w($r1, 'MARIO', 52, 30);
w($r1, 'M', 82, 1);
w($r1, '15061985', 83, 8);
// comune/prov vuoti = spaces (estero)
w($r1, '201', 97, 3);   // stato nascita
w($r1, '201', 100, 3);  // cittadinanza
w($r1, 'PASOR', 103, 5); // tipo doc 5 chars
w($r1, 'AB123456', 108, 15);
w($r1, '201', 123, 4);  // luogo ril
// filler 127-167 = spaces
testRecord($client, $utente, $token, $r1, 'Test1: tipo16, PASOR, no data_arrivo, filler=41');

// Test 2: New layout with data_arrivo at pos 2, tipo 16, doc type PASOR (5 chars)
// New layout: tipo(2) data_arr(8) cognome(50) nome(30) sesso(1) data_nasc(8) comune(4) prov(2) stato(3) naz(3) tipo_doc(5) num_doc(15) luogo(4) filler(33)
// Total: 2+8+50+30+1+8+4+2+3+3+5+15+4+33 = 168
$r2 = str_repeat(' ', 168);
w($r2, '16', 0, 2);
w($r2, date('dmY'), 2, 8);  // data arrivo oggi
w($r2, 'ROSSI', 10, 50);
w($r2, 'MARIO', 60, 30);
w($r2, 'M', 90, 1);
w($r2, '15061985', 91, 8);
// comune/prov = spaces
w($r2, '201', 105, 3);
w($r2, '201', 108, 3);
w($r2, 'PASOR', 111, 5);
w($r2, 'AB123456', 116, 15);
w($r2, '201', 131, 4);
// filler 135-167 = spaces
testRecord($client, $utente, $token, $r2, 'Test2: tipo16, data_arr pos2, PASOR, filler=33');

// Test 3: New layout with 10+10 padding after data_arrivo, tipo 16, PASOR
// tipo(2) data_arr(8) ora(10) prov_da(10) cognome(50) nome(30) sesso(1) data_nasc(8) comune(4) prov(2) stato(3) naz(3) tipo_doc(5) num_doc(15) luogo(4) filler(13)
// Total: 2+8+10+10+50+30+1+8+4+2+3+3+5+15+4+13 = 168
$r3 = str_repeat(' ', 168);
w($r3, '16', 0, 2);
w($r3, date('dmY'), 2, 8);
// 10-19: ora = spaces
// 20-29: prov_da = spaces
w($r3, 'ROSSI', 30, 50);
w($r3, 'MARIO', 80, 30);
w($r3, 'M', 110, 1);
w($r3, '15061985', 111, 8);
// comune/prov = spaces
w($r3, '201', 125, 3);
w($r3, '201', 128, 3);
w($r3, 'PASOR', 131, 5);
w($r3, 'AB123456', 136, 15);
w($r3, '201', 151, 4);
// filler 155-167 = spaces
testRecord($client, $utente, $token, $r3, 'Test3: tipo16, data_arr pos2+10+10, PASOR, filler=13');

// Test 4: Original layout with tipo 16, original 3-char doc type 'APA' (check if it's the date that drives error)
$r4 = str_repeat(' ', 168);
w($r4, '16', 0, 2);
w($r4, 'ROSSI', 2, 50);
w($r4, 'MARIO', 52, 30);
w($r4, 'M', 82, 1);
w($r4, '15061985', 83, 8);
w($r4, '201', 97, 3);
w($r4, '201', 100, 3);
w($r4, 'APA', 103, 3);
w($r4, 'AB123456', 106, 15);
w($r4, '201', 121, 4);
testRecord($client, $utente, $token, $r4, 'Test4: tipo16, no data_arr, APA (3-char)');
