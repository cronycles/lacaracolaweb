<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

use App\Contracts\GuestReportingDriverInterface;
use App\Services\GuestReporting\Data\ItalianMunicipalities;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use SoapClient;
use SoapFault;
use Throwable;

/**
 * Guest-reporting driver for the Italian Polizia di Stato "Alloggiati Web" SOAP service.
 * Endpoint: https://alloggiatiweb.poliziadistato.it/service/service.asmx
 *
 * This class is the ONLY place in the application that knows about:
 * - The SOAP service URL and WSDL
 * - The positional record format (Tabella 1 / Tabella 2)
 * - Country code mapping (ISO → Alloggiati ISTAT)
 * - Document type mapping (internal → Alloggiati codes)
 * - Municipality code lookup (name → Codice Belfiore)
 * - Token caching
 *
 * IMPORTANT — Tracciato record:
 * The field positions are derived from the prompt specification. Verify against
 * the official Alloggiati Web technical manual before going to production.
 * All field boundaries are named constants to make corrections easy.
 */
class PoliziaStatoAlloggiatiDriver implements GuestReportingDriverInterface
{
    private const WSDL = 'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl';

    // Tabella 1 — single structure mode (no id_appartamento)
    private const RECORD_LENGTH = 170;
    // Tabella 2 — multi-apartment mode (id_appartamento set)
    private const RECORD_LENGTH_APPARTAMENTI = 176;

    // Field positions for Tabella 1 (1-based, inclusive start/length pairs)
    private const FIELD_TIPO_ALLOGGIATO_START  = 0;  private const FIELD_TIPO_ALLOGGIATO_LEN   = 2;
    private const FIELD_COGNOME_START          = 2;  private const FIELD_COGNOME_LEN            = 50;
    private const FIELD_NOME_START             = 52; private const FIELD_NOME_LEN               = 30;
    private const FIELD_SESSO_START            = 82; private const FIELD_SESSO_LEN              = 1;
    private const FIELD_DATA_NASCITA_START     = 83; private const FIELD_DATA_NASCITA_LEN       = 8;
    private const FIELD_COMUNE_NASCITA_START   = 91; private const FIELD_COMUNE_NASCITA_LEN     = 4;
    private const FIELD_PROVINCIA_NASCITA_START = 95; private const FIELD_PROVINCIA_NASCITA_LEN = 2;
    private const FIELD_STATO_NASCITA_START    = 97; private const FIELD_STATO_NASCITA_LEN      = 3;
    private const FIELD_CITTADINANZA_START     = 100; private const FIELD_CITTADINANZA_LEN      = 3;
    private const FIELD_TIPO_DOC_START         = 103; private const FIELD_TIPO_DOC_LEN          = 3;
    private const FIELD_NUM_DOC_START          = 106; private const FIELD_NUM_DOC_LEN           = 15;
    private const FIELD_LUOGO_RIL_START        = 121; private const FIELD_LUOGO_RIL_LEN         = 4;
    private const FIELD_FILLER_START           = 125; private const FIELD_FILLER_LEN            = 43;
    // Tabella 2 only — apartment ID occupies positions 168–173 (0-based: 167–172), length 6
    private const FIELD_ID_APPARTAMENTO_START  = 167; private const FIELD_ID_APPARTAMENTO_LEN   = 6;

    private readonly string $utente;
    private readonly string $password;
    private readonly string $wsKey;
    private readonly ?string $idAppartamento;
    private readonly string $cacheKey;

    public function __construct(array $config)
    {
        $this->utente         = (string) ($config['utente'] ?? '');
        $this->password       = (string) ($config['password'] ?? '');
        $this->wsKey          = (string) ($config['ws_key'] ?? '');
        $this->idAppartamento = isset($config['id_appartamento']) && $config['id_appartamento'] !== ''
            ? (string) $config['id_appartamento']
            : null;
        $this->cacheKey = 'guest_reporting_token_polizia_stato';
    }

    // -------------------------------------------------------------------------
    // Public interface
    // -------------------------------------------------------------------------

    public function checkConnection(): bool
    {
        try {
            $token  = $this->getToken();
            $client = $this->createSoapClient();
            $result = $client->Authentication_Test([
                'Utente' => $this->utente,
                'token'  => $token,
            ]);

            return isset($result->Authentication_TestResult->esito) &&
                   (int) $result->Authentication_TestResult->esito === 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function testDraft(array $guests): SubmissionResult
    {
        try {
            $token      = $this->getToken();
            $client     = $this->createSoapClient();
            $elenco     = $this->buildElenco($guests);
            $raw        = $this->callTestMethod($client, $token, $elenco);
            return $this->parseResponse($raw, 'test');
        } catch (Throwable $e) {
            return SubmissionResult::failure('Errore durante il test: ' . $e->getMessage());
        }
    }

    public function sendGuests(array $guests): SubmissionResult
    {
        try {
            $token  = $this->getToken();
            $client = $this->createSoapClient();
            $elenco = $this->buildElenco($guests);
            $raw    = $this->callSendMethod($client, $token, $elenco);
            return $this->parseResponse($raw, 'send');
        } catch (Throwable $e) {
            return SubmissionResult::failure('Errore durante l\'invio: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Token management
    // -------------------------------------------------------------------------

    private function getToken(): string
    {
        /** @var string|null $cached */
        $cached = Cache::get($this->cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $client = $this->createSoapClient();

        try {
            $result = $client->GenerateToken([
                'Utente'   => $this->utente,
                'Password' => $this->password,
                'WsKey'    => $this->wsKey,
            ]);
        } catch (SoapFault $e) {
            throw new RuntimeException('Alloggiati Web GenerateToken failed: ' . $e->getMessage(), 0, $e);
        }

        // Verified response structure:
        //   $result->GenerateTokenResult->{token, issued, expires}
        //   $result->result->{esito, ErroreCod, ErroreDes, ErroreDettaglio}
        $tokenResult = $result->GenerateTokenResult ?? null;
        $token       = (string) ($tokenResult->token ?? '');

        if ($token === '') {
            $res  = $result->result ?? null;
            $desc = ($res->ErroreDes ?? '') ?: ($res->ErroreDettaglio ?? '') ?: 'token vuoto — verificare utente/password/ws_key';
            throw new RuntimeException("Alloggiati Web authentication error: {$desc}");
        }

        // Use expiry from response (minus 5-minute safety margin); fallback: 55 min
        $expiresStr = $tokenResult->expires ?? null;
        $ttlSeconds = $expiresStr
            ? max(60, (int) (strtotime($expiresStr) - time()) - 300)
            : 55 * 60;

        Cache::put($this->cacheKey, $token, now()->addSeconds($ttlSeconds));

        return $token;
    }

    // -------------------------------------------------------------------------
    // Record building
    // -------------------------------------------------------------------------

    /** Build the full ElencoSchedine string from an array of GuestRecords. */
    private function buildElenco(array $guests): string
    {
        $lines = [];
        foreach ($guests as $guest) {
            $lines[] = $this->buildRecord($guest);
        }

        // CR+LF after every record except the last
        return implode("\r\n", $lines);
    }

    /** Format a single positional record (Tabella 1 or Tabella 2). */
    private function buildRecord(GuestRecord $guest): string
    {
        $useAppartamenti = $this->idAppartamento !== null;
        $expectedLength  = $useAppartamenti ? self::RECORD_LENGTH_APPARTAMENTI : self::RECORD_LENGTH;

        // Build a mutable byte buffer filled with spaces
        $record = str_repeat(' ', $expectedLength - 2); // -2 reserved for CR+LF (not part of data)

        $this->writeField($record, self::FIELD_TIPO_ALLOGGIATO_START, self::FIELD_TIPO_ALLOGGIATO_LEN, $guest->tipoAlloggiato, true);
        $this->writeField($record, self::FIELD_COGNOME_START, self::FIELD_COGNOME_LEN, mb_strtoupper($guest->lastName));
        $this->writeField($record, self::FIELD_NOME_START, self::FIELD_NOME_LEN, mb_strtoupper($guest->firstName));
        $this->writeField($record, self::FIELD_SESSO_START, self::FIELD_SESSO_LEN, mb_strtoupper($guest->gender));
        $this->writeField($record, self::FIELD_DATA_NASCITA_START, self::FIELD_DATA_NASCITA_LEN,
            date('dmY', strtotime($guest->birthDate)));

        // Birth municipality/province/country
        if ($guest->birthCountryCode === 'IT') {
            $municipalCode = ItalianMunicipalities::findCode($guest->birthMunicipality) ?? '    ';
            $this->writeField($record, self::FIELD_COMUNE_NASCITA_START, self::FIELD_COMUNE_NASCITA_LEN, $municipalCode);
            $this->writeField($record, self::FIELD_PROVINCIA_NASCITA_START, self::FIELD_PROVINCIA_NASCITA_LEN,
                mb_strtoupper($guest->birthProvince ?? ''));
        }
        // For foreign citizens, commune and province remain spaces; only country code is set

        $this->writeField($record, self::FIELD_STATO_NASCITA_START, self::FIELD_STATO_NASCITA_LEN,
            $this->countryIsoToAlloggiati($guest->birthCountryCode));
        $this->writeField($record, self::FIELD_CITTADINANZA_START, self::FIELD_CITTADINANZA_LEN,
            $this->countryIsoToAlloggiati($guest->nationalityCode));
        $this->writeField($record, self::FIELD_TIPO_DOC_START, self::FIELD_TIPO_DOC_LEN,
            $this->documentTypeToAlloggiati($guest->documentType));
        $this->writeField($record, self::FIELD_NUM_DOC_START, self::FIELD_NUM_DOC_LEN,
            mb_strtoupper($guest->documentNumber));

        // Document issue place
        if ($guest->documentIssueCountryCode === 'IT') {
            $issueCode = ItalianMunicipalities::findCode($guest->documentIssuePlace) ?? '    ';
            $this->writeField($record, self::FIELD_LUOGO_RIL_START, self::FIELD_LUOGO_RIL_LEN, $issueCode);
        } else {
            $this->writeField($record, self::FIELD_LUOGO_RIL_START, self::FIELD_LUOGO_RIL_LEN,
                $this->countryIsoToAlloggiati($guest->documentIssueCountryCode));
        }

        // Apartment ID (Tabella 2 only)
        if ($useAppartamenti) {
            $this->writeField($record, self::FIELD_ID_APPARTAMENTO_START, self::FIELD_ID_APPARTAMENTO_LEN,
                str_pad($this->idAppartamento, self::FIELD_ID_APPARTAMENTO_LEN, '0', STR_PAD_LEFT));
        }

        // Validate length (catches format bugs at test time)
        $dataLength = $expectedLength - 2;
        if (mb_strlen($record, '8bit') !== $dataLength) {
            throw new RuntimeException(
                sprintf(
                    'Record length mismatch: expected %d data chars, got %d.',
                    $dataLength,
                    mb_strlen($record, '8bit')
                )
            );
        }

        return $record;
    }

    /** Write a value into the buffer at the given 0-based position, truncating/padding as needed. */
    private function writeField(string &$buffer, int $start, int $length, string $value, bool $padLeft = false): void
    {
        // Truncate if longer than the field
        $value = mb_substr($value, 0, $length, '8bit');

        // Pad
        $padded = $padLeft
            ? str_pad($value, $length, ' ', STR_PAD_LEFT)
            : str_pad($value, $length, ' ', STR_PAD_RIGHT);

        // Write into buffer using substr_replace on the raw bytes
        $buffer = substr_replace($buffer, $padded, $start, $length);
    }

    // -------------------------------------------------------------------------
    // SOAP calls
    // -------------------------------------------------------------------------

    private function callTestMethod(SoapClient $client, string $token, string $elenco): object
    {
        if ($this->idAppartamento !== null) {
            return $client->GestioneAppartamenti_Test([
                'Utente'         => $this->utente,
                'token'          => $token,
                'ElencoSchedine' => $elenco,
                'IdAppartamento' => $this->idAppartamento,
            ]);
        }

        return $client->Test([
            'Utente'         => $this->utente,
            'token'          => $token,
            'ElencoSchedine' => $elenco,
        ]);
    }

    private function callSendMethod(SoapClient $client, string $token, string $elenco): object
    {
        if ($this->idAppartamento !== null) {
            return $client->GestioneAppartamenti_Send([
                'Utente'         => $this->utente,
                'token'          => $token,
                'ElencoSchedine' => $elenco,
                'IdAppartamento' => $this->idAppartamento,
            ]);
        }

        return $client->Send([
            'Utente'         => $this->utente,
            'token'          => $token,
            'ElencoSchedine' => $elenco,
        ]);
    }

    // -------------------------------------------------------------------------
    // Response parsing
    // -------------------------------------------------------------------------

    private function parseResponse(object $raw, string $mode): SubmissionResult
    {
        $rawJson = json_encode($raw, JSON_UNESCAPED_UNICODE) ?: null;

        // The result property name differs between Test and Send methods
        $resultKey  = $mode === 'test' ? 'TestResult' : 'SendResult';
        $appartKey  = $mode === 'test' ? 'GestioneAppartamenti_TestResult' : 'GestioneAppartamenti_SendResult';
        $resultProp = $raw->{$resultKey} ?? $raw->{$appartKey} ?? null;

        if ($resultProp === null) {
            return SubmissionResult::failure('Risposta SOAP non riconosciuta.', $rawJson);
        }

        $esito      = (int) ($resultProp->esito ?? -1);
        $descrizione = (string) ($resultProp->descrizione ?? '');

        // Parse per-row details
        $rowDetails = [];
        $righe = $resultProp->DettaglioEsito ?? $resultProp->Risultato ?? null;
        if ($righe !== null) {
            $items = is_array($righe) ? $righe : [$righe];
            foreach ($items as $item) {
                $rowDetails[] = [
                    'row'         => (int) ($item->riga ?? 0),
                    'esito'       => (string) ($item->esito ?? ''),
                    'descrizione' => (string) ($item->descrizione ?? ''),
                ];
            }
        }

        if ($esito === 0) {
            $msg = $mode === 'test'
                ? 'Bozza validata con successo.'
                : 'Schedine inviate con successo.';

            return SubmissionResult::success($msg, $rowDetails, $rawJson);
        }

        return SubmissionResult::failure($descrizione ?: "Errore SOAP (esito: {$esito}).", $rawJson);
    }

    // -------------------------------------------------------------------------
    // Code mapping tables (this driver's internal responsibility)
    // -------------------------------------------------------------------------

    /**
     * Map ISO 3166-1 alpha-2 country code to Alloggiati Web ISTAT numeric code.
     *
     * @throws RuntimeException for unmapped codes
     */
    private function countryIsoToAlloggiati(string $iso): string
    {
        $iso = mb_strtoupper(trim($iso));

        $map = [
            'IT' => '100', 'DE' => '201', 'FR' => '202', 'ES' => '203',
            'GB' => '204', 'IE' => '205', 'NL' => '206', 'CH' => '207',
            'AT' => '208', 'BE' => '209', 'PT' => '210', 'SE' => '211',
            'NO' => '212', 'DK' => '213', 'FI' => '214', 'PL' => '215',
            'CZ' => '216', 'SK' => '217', 'HU' => '218', 'RO' => '219',
            'BG' => '220', 'HR' => '221', 'SI' => '222', 'GR' => '223',
            'LT' => '224', 'LV' => '225', 'EE' => '226', 'LU' => '227',
            'MT' => '228', 'CY' => '229', 'IS' => '230', 'LI' => '231',
            'MC' => '232', 'SM' => '233', 'VA' => '234', 'AL' => '235',
            'BA' => '236', 'MK' => '237', 'RS' => '238', 'ME' => '239',
            'XK' => '240', 'MD' => '241', 'UA' => '242', 'BY' => '243',
            'RU' => '244', 'TR' => '245', 'AM' => '246', 'AZ' => '247',
            'GE' => '248', 'US' => '401', 'CA' => '402', 'MX' => '403',
            'BR' => '501', 'AR' => '502', 'CL' => '503', 'CO' => '504',
            'VE' => '505', 'PE' => '506', 'AU' => '601', 'NZ' => '602',
            'JP' => '701', 'CN' => '702', 'KR' => '703', 'IN' => '704',
            'PK' => '705', 'BD' => '706', 'TH' => '707', 'VN' => '708',
            'PH' => '709', 'MY' => '710', 'SG' => '711', 'ID' => '712',
            'IL' => '713', 'SA' => '714', 'AE' => '715', 'IR' => '716',
            'IQ' => '717', 'EG' => '801', 'MA' => '802', 'TN' => '803',
            'DZ' => '804', 'LY' => '805', 'NG' => '806', 'ZA' => '807',
            'KE' => '808', 'ET' => '809', 'GH' => '810', 'SN' => '811',
        ];

        if (! isset($map[$iso])) {
            throw new RuntimeException("Unmapped ISO country code for Alloggiati Web: [{$iso}]. Add it to PoliziaStatoAlloggiatiDriver::\$countryIsoToAlloggiati().");
        }

        return $map[$iso];
    }

    /**
     * Map internal document type to Alloggiati Web 3-char code.
     * Unknown types fall back to ALTR (altro).
     */
    private function documentTypeToAlloggiati(string $type): string
    {
        return match ($type) {
            'passport'         => 'APAT',
            'id_card'          => 'CRIT',
            'driving_license'  => 'PATO',
            'residence_permit' => 'PPAI',
            default            => 'ALTR',
        };
    }

    // -------------------------------------------------------------------------
    // SOAP client factory
    // -------------------------------------------------------------------------

    private function createSoapClient(): SoapClient
    {
        try {
            return new SoapClient(self::WSDL, [
                'trace'        => false,
                'exceptions'   => true,
                'encoding'     => 'UTF-8',
                'soap_version' => SOAP_1_1,
                'cache_wsdl'   => WSDL_CACHE_BOTH,
            ]);
        } catch (SoapFault $e) {
            throw new RuntimeException('Cannot connect to Alloggiati Web WSDL: ' . $e->getMessage(), 0, $e);
        }
    }
}
