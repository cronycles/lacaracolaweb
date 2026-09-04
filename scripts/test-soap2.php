<?php

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->safeLoad();

$utente = $_ENV['GUEST_REPORTING_UTENTE'] ?? '';
$password = $_ENV['GUEST_REPORTING_PASSWORD'] ?? '';
$wsKey = $_ENV['GUEST_REPORTING_WS_KEY'] ?? '';

$client = new SoapClient(
    'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl',
    ['trace' => true, 'exceptions' => true, 'encoding' => 'UTF-8', 'soap_version' => SOAP_1_1, 'cache_wsdl' => WSDL_CACHE_NONE]
);

$tr = $client->GenerateToken(['Utente' => $utente, 'Password' => $password, 'WsKey' => $wsKey]);
$token = (string) ($tr->GenerateTokenResult->token ?? '');
echo 'Token OK: '.strlen($token)." chars\n";

function w(string &$s, string $val, int $start, int $len): void
{
    $s = substr_replace($s, str_pad(substr($val, 0, $len), $len), $start, $len);
}

// Format A: with data_arrivo at position 2 (new format hypothesis)
// 0-1(2): tipo
// 2-9(8): data arrivo GGMMAAAA
// 10-19(10): ora
// 20-29(10): provenienza
// 30-79(50): cognome
// 80-109(30): nome
// 110(1): sesso
// 111-118(8): data nascita
// 119-122(4): comune nascita
// 123-124(2): provincia nascita
// 125-127(3): stato nascita
// 128-130(3): cittadinanza
// 131-133(3): tipo doc
// 134-148(15): num doc
// 149-152(4): luogo rilascio
// 153-167(15): filler
// Total: 2+8+10+10+50+30+1+8+4+2+3+3+3+15+4+15 = 168

$recordA = str_repeat(' ', 168);
w($recordA, '18', 0, 2);
w($recordA, date('dmY'), 2, 8);   // data arrivo oggi
w($recordA, 'ROSSI', 30, 50);
w($recordA, 'MARIO', 80, 30);
w($recordA, 'M', 110, 1);
w($recordA, '15061985', 111, 8);
w($recordA, '201', 125, 3);
w($recordA, '201', 128, 3);
w($recordA, 'APA', 131, 3);
w($recordA, 'AB123456', 134, 15);
w($recordA, '201', 149, 4);

echo "=== FORMAT A (with data_arrivo at pos 2) ===\n";
echo 'len='.strlen($recordA)."\n";
$elencoA = ['string' => [$recordA]];
$resultA = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => $elencoA]);
echo json_encode($resultA, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";

// Test multiple date formats and also try CRLF-embedded records
$formats = [
    'dmY' => date('dmY'),     // 01072025
    'Ymd' => date('Ymd'),     // 20250701
    'mdY' => date('mdY'),     // 07012025
    'dmy' => date('dmy'),     // 010725
];

foreach ($formats as $fmt => $dateVal) {
    $r = str_repeat(' ', 168);
    w($r, '18', 0, 2);
    w($r, $dateVal, 2, 8);
    w($r, 'ROSSI', 30, 50);
    w($r, 'MARIO', 80, 30);
    w($r, 'M', 110, 1);
    w($r, '15061985', 111, 8);
    w($r, '201', 125, 3);
    w($r, '201', 128, 3);
    w($r, 'APA', 131, 3);
    w($r, 'AB123456', 134, 15);
    w($r, '201', 149, 4);
    $res = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => ['string' => [$r]]]);
    $detail = $res->result->Dettaglio->EsitoOperazioneServizio ?? null;
    echo "date fmt $fmt ($dateVal): ".($detail ? $detail->ErroreDettaglio : 'OK?')."\n";
}

// Also test with CRLF embedded in each record element
$r2 = str_repeat(' ', 168);
w($r2, '18', 0, 2);
w($r2, date('dmY'), 2, 8);
w($r2, 'ROSSI', 30, 50);
w($r2, 'MARIO', 80, 30);
w($r2, 'M', 110, 1);
w($r2, '15061985', 111, 8);
w($r2, '201', 125, 3);
w($r2, '201', 128, 3);
w($r2, 'APA', 131, 3);
w($r2, 'AB123456', 134, 15);
w($r2, '201', 149, 4);
$res2 = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => ['string' => [$r2."\r\n"]]]);
$detail2 = $res2->result->Dettaglio->EsitoOperazioneServizio ?? null;
echo 'With CRLF: '.($detail2 ? $detail2->ErroreDettaglio : 'OK?')."\n";
