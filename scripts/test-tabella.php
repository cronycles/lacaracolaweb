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
echo 'Token OK: '.strlen($token)." chars\n\n";

foreach (['Tipi_Alloggiato', 'Tipi_Documento', 'Luoghi'] as $table) {
    $result = $client->Tabella(['Utente' => $utente, 'token' => $token, 'tipo' => $table]);
    echo "=== $table ===\n";
    if (isset($result->TabellaResult->esito) && $result->TabellaResult->esito === true) {
        echo $result->CSV."\n\n";
    } else {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
    }
}
