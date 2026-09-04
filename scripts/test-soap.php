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

$record = str_repeat(' ', 168);

function w(string &$s, string $val, int $start, int $len): void
{
    $s = substr_replace($s, str_pad(substr($val, 0, $len), $len), $start, $len);
}

w($record, '18', 0, 2);
w($record, 'ROSSI', 2, 50);
w($record, 'MARIO', 52, 30);
w($record, 'M', 82, 1);
w($record, '15061985', 83, 8);
// comune/provincia vuoti = spaces (estero)
w($record, '201', 97, 3);   // stato nascita DE
w($record, '201', 100, 3);  // cittadinanza DE
w($record, 'APA', 103, 3);  // tipo doc (3 chars)
w($record, 'AB123456', 106, 15);
w($record, '201', 121, 4);  // luogo rilascio DE

echo 'Record length: '.strlen($record)."\n";
echo 'Record repr: ['.str_replace(["\r", "\n"], ['\\r', '\\n'], $record)."]\n\n";

$elenco = ['string' => [$record]];

$result = $client->Test(['Utente' => $utente, 'token' => $token, 'ElencoSchedine' => $elenco]);
echo "ArrayOfString result:\n".json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
echo 'Request ElencoSchedine in XML: '.(preg_match('/<ns\d:ElencoSchedine[^/]/', $client->__getLastRequest()) ? 'HAS CONTENT' : 'empty')."\n";
