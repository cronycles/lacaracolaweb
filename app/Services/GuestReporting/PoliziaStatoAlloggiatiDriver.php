<?php

declare(strict_types=1);

namespace App\Services\GuestReporting;

use App\Contracts\GuestReportingDriverInterface;
use App\Models\Country;
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
 * - The positional record format (TracciatoRecord.png — verified against official spec)
 * - Country code mapping (ISO-2 → 9-digit Alloggiati code from stati.csv)
 * - Document type mapping (internal → 5-char Alloggiati code from documenti.csv)
 * - Municipality code lookup (name → 9-digit code from comuni.csv)
 * - Token caching
 *
 * RECORD FORMAT — 168 data chars per guest, plus CRLF after every row except the last:
 *   pos  0- 1 ( 2): Tipo Alloggiato        — Codice Tabella Tipo Alloggiati
 *   pos  2-11 (10): Data Arrivo            — gg/mm/aaaa
 *   pos 12-13 ( 2): Giorni Permanenza      — max 30
 *   pos 14-63 (50): Cognome
 *   pos 64-93 (30): Nome
 *   pos 94    ( 1): Sesso                  — 1=M, 2=F
 *   pos 95-104(10): Data Nascita           — gg/mm/aaaa
 *   pos105-113( 9): Comune Nascita         — Codice Tabella Comuni (obbligatorio se IT)
 *   pos114-115( 2): Provincia Nascita      — sigla (obbligatoria se IT)
 *   pos116-124( 9): Stato Nascita          — Codice Tabella Stati
 *   pos125-133( 9): Cittadinanza           — Codice Tabella Stati
 *   pos134-138( 5): Tipo Documento         — Codice Tabella Documenti (blank per tipo 19-20)
 *   pos139-158(20): Numero Documento       — (blank per tipo 19-20)
 *   pos159-167( 9): Luogo Rilascio Doc     — Codice Tabella Stati o Comuni (blank per tipo 19-20)
 */
class PoliziaStatoAlloggiatiDriver implements GuestReportingDriverInterface
{
    private const WSDL = 'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl';

    // Record data length — 168 chars; CR+LF is added between rows in buildElenco().
    private const RECORD_LENGTH = 168;

    // Field positions (0-indexed start, length) — from TracciatoRecord.png
    private const FIELD_TIPO_ALLOGGIATO_START   = 0;   private const FIELD_TIPO_ALLOGGIATO_LEN   = 2;
    private const FIELD_DATA_ARRIVO_START        = 2;   private const FIELD_DATA_ARRIVO_LEN        = 10;
    private const FIELD_GIORNI_PERM_START        = 12;  private const FIELD_GIORNI_PERM_LEN        = 2;
    private const FIELD_COGNOME_START            = 14;  private const FIELD_COGNOME_LEN            = 50;
    private const FIELD_NOME_START               = 64;  private const FIELD_NOME_LEN               = 30;
    private const FIELD_SESSO_START              = 94;  private const FIELD_SESSO_LEN              = 1;
    private const FIELD_DATA_NASCITA_START       = 95;  private const FIELD_DATA_NASCITA_LEN       = 10;
    private const FIELD_COMUNE_NASCITA_START     = 105; private const FIELD_COMUNE_NASCITA_LEN     = 9;
    private const FIELD_PROVINCIA_NASCITA_START  = 114; private const FIELD_PROVINCIA_NASCITA_LEN  = 2;
    private const FIELD_STATO_NASCITA_START      = 116; private const FIELD_STATO_NASCITA_LEN      = 9;
    private const FIELD_CITTADINANZA_START       = 125; private const FIELD_CITTADINANZA_LEN       = 9;
    private const FIELD_TIPO_DOC_START           = 134; private const FIELD_TIPO_DOC_LEN           = 5;
    private const FIELD_NUM_DOC_START            = 139; private const FIELD_NUM_DOC_LEN            = 20;
    private const FIELD_LUOGO_RIL_START          = 159; private const FIELD_LUOGO_RIL_LEN          = 9;

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

    /**
     * Build the ElencoSchedine array from an array of GuestRecords.
    * The WSDL defines ElencoSchedine as ArrayOfString, so each record
    * is a separate element. Alloggiati Web still requires CR+LF after every
    * row except the last, as specified by the official record layout.
     *
     * @param  GuestRecord[]  $guests
     * @return array{string: string[]}
     */
    private function buildElenco(array $guests): array
    {
        $records = [];
        $lastIndex = count($guests) - 1;
        foreach ($guests as $index => $guest) {
            $records[] = $this->buildRecord($guest) . ($index < $lastIndex ? "\r\n" : '');
        }

        return ['string' => $records];
    }

    /** Format a single positional record (168 data chars, without its row terminator). */
    private function buildRecord(GuestRecord $guest): string
    {
        $record = str_repeat(' ', self::RECORD_LENGTH);

        $this->writeField($record, self::FIELD_TIPO_ALLOGGIATO_START, self::FIELD_TIPO_ALLOGGIATO_LEN,
            $guest->tipoAlloggiato, true);

        $this->writeField($record, self::FIELD_DATA_ARRIVO_START, self::FIELD_DATA_ARRIVO_LEN,
            $guest->arrivalDate);  // already in d/m/Y format

        $this->writeField($record, self::FIELD_GIORNI_PERM_START, self::FIELD_GIORNI_PERM_LEN,
            str_pad((string) min(30, max(1, $guest->stayNights)), 2, '0', STR_PAD_LEFT));

        $this->writeField($record, self::FIELD_COGNOME_START, self::FIELD_COGNOME_LEN,
            mb_strtoupper($guest->lastName));
        $this->writeField($record, self::FIELD_NOME_START, self::FIELD_NOME_LEN,
            mb_strtoupper($guest->firstName));

        $this->writeField($record, self::FIELD_SESSO_START, self::FIELD_SESSO_LEN,
            $this->genderToAlloggiati($guest->gender));

        $this->writeField($record, self::FIELD_DATA_NASCITA_START, self::FIELD_DATA_NASCITA_LEN,
            date('d/m/Y', strtotime($guest->birthDate)));

        // Comune and provincia only for Italian births; foreign births use spaces
        if ($guest->birthCountryCode === 'IT') {
            $municipalCode = ItalianMunicipalities::findCode($guest->birthMunicipality, $guest->birthProvince) ?? str_repeat(' ', 9);
            $this->writeField($record, self::FIELD_COMUNE_NASCITA_START, self::FIELD_COMUNE_NASCITA_LEN, $municipalCode);
            $this->writeField($record, self::FIELD_PROVINCIA_NASCITA_START, self::FIELD_PROVINCIA_NASCITA_LEN,
                mb_strtoupper($guest->birthProvince ?? ''));
        }

        $this->writeField($record, self::FIELD_STATO_NASCITA_START, self::FIELD_STATO_NASCITA_LEN,
            $this->countryIsoToAlloggiati($guest->birthCountryCode));
        $this->writeField($record, self::FIELD_CITTADINANZA_START, self::FIELD_CITTADINANZA_LEN,
            $this->countryIsoToAlloggiati($guest->nationalityCode));

        // Document fields: mandatory for tipo 16/17/18, blank for 19/20
        if (in_array($guest->tipoAlloggiato, ['16', '17', '18'], true)) {
            $this->writeField($record, self::FIELD_TIPO_DOC_START, self::FIELD_TIPO_DOC_LEN,
                $this->documentTypeToAlloggiati($guest->documentType));
            $this->writeField($record, self::FIELD_NUM_DOC_START, self::FIELD_NUM_DOC_LEN,
                mb_strtoupper($guest->documentNumber));

            if ($guest->documentIssueCountryCode === 'IT') {
                $issueCode = ItalianMunicipalities::findCode($guest->documentIssuePlace, null) ?? str_repeat(' ', 9);
                $this->writeField($record, self::FIELD_LUOGO_RIL_START, self::FIELD_LUOGO_RIL_LEN, $issueCode);
            } else {
                $this->writeField($record, self::FIELD_LUOGO_RIL_START, self::FIELD_LUOGO_RIL_LEN,
                    $this->countryIsoToAlloggiati($guest->documentIssueCountryCode));
            }
        }
        // For tipo 19/20 doc fields remain spaces (already filled by str_repeat above)

        if (mb_strlen($record, '8bit') !== self::RECORD_LENGTH) {
            throw new RuntimeException(sprintf(
                'Record length mismatch: expected %d, got %d.',
                self::RECORD_LENGTH, mb_strlen($record, '8bit')
            ));
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

    private function callTestMethod(SoapClient $client, string $token, array $elenco): object
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

    private function callSendMethod(SoapClient $client, string $token, array $elenco): object
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

        // The result property name differs between Test/Send and GestioneAppartamenti variants
        $resultKey  = $mode === 'test' ? 'TestResult' : 'SendResult';
        $appartKey  = $mode === 'test' ? 'GestioneAppartamenti_TestResult' : 'GestioneAppartamenti_SendResult';
        $resultProp = $raw->{$resultKey} ?? $raw->{$appartKey} ?? null;

        if ($resultProp === null) {
            return SubmissionResult::failure('Risposta SOAP non riconosciuta.', $rawJson);
        }

        // The service returns esito as boolean (true = ok, false = error)
        // and error detail in ErroreCod / ErroreDes / ErroreDettaglio
        $esito       = $resultProp->esito ?? false;
        $errorCod    = (string) ($resultProp->ErroreCod ?? '');
        $errorDes    = (string) ($resultProp->ErroreDes ?? '');
        $errorDetail = (string) ($resultProp->ErroreDettaglio ?? '');

        $topLevelOk = $esito === true || $esito === 1 || ($esito !== false && (int) $esito === 0 && $errorCod === '');

        // Top-level failure (auth/transport error)
        if (!$topLevelOk) {
            $description = $errorDetail ?: $errorDes ?: "Errore servizio (cod: {$errorCod}).";
            return SubmissionResult::failure($description, $rawJson);
        }

        // Alloggiati Web returns top-level TestResult/SendResult.esito=true to signal that
        // the request was received, but the actual per-schedina validation results live in
        // $raw->result->Dettaglio->EsitoOperazioneServizio and $raw->result->SchedineValide.
        // We must check those to determine real success or failure.
        $rowDetails   = [];
        $resultData   = $raw->result ?? null;
        $checkedNested = false;

        if ($resultData !== null) {
            $schedineValide = isset($resultData->SchedineValide) ? (int) $resultData->SchedineValide : null;
            $esitoItems     = $resultData->Dettaglio->EsitoOperazioneServizio ?? null;

            if ($esitoItems !== null) {
                $checkedNested = true;
                $items         = is_array($esitoItems) ? $esitoItems : [$esitoItems];
                $errorMessages = [];

                foreach ($items as $i => $item) {
                    $rowEsito   = $item->esito ?? true;
                    $rowOk      = $rowEsito === true || $rowEsito === 1;
                    $rowErrDes  = (string) ($item->ErroreDettaglio ?? $item->ErroreDes ?? '');
                    $rowErrCod  = (string) ($item->ErroreCod ?? '');

                    $rowDetails[] = [
                        'row'         => $i + 1,
                        'esito'       => $rowOk ? '1' : '0',
                        'descrizione' => $rowErrDes ?: ($rowErrCod ? "Errore cod: {$rowErrCod}" : ''),
                    ];

                    if (!$rowOk && $rowErrDes !== '') {
                        $errorMessages[] = $rowErrDes;
                    }
                }

                $hasRowErrors = !empty($errorMessages);
                $noValidRows  = $schedineValide !== null && $schedineValide === 0;

                if ($hasRowErrors || $noValidRows) {
                    $description = $errorMessages
                        ? implode('; ', array_unique($errorMessages))
                        : ($schedineValide !== null
                            ? "Nessuna schedina valida (SchedineValide: {$schedineValide})."
                            : 'Schedine non accettate dal servizio.');
                    return SubmissionResult::failure($description, $rawJson);
                }
            }
        }

        // Fallback: parse per-row details from the old response structure
        if (!$checkedNested) {
            $righe = $resultProp->DettaglioEsito ?? $resultProp->Risultato ?? null;
            if ($righe !== null) {
                $items = is_array($righe) ? $righe : [$righe];
                foreach ($items as $item) {
                    $rowDetails[] = [
                        'row'         => (int) ($item->riga ?? 0),
                        'esito'       => (string) ($item->esito ?? ''),
                        'descrizione' => (string) ($item->descrizione ?? $item->ErroreDes ?? ''),
                    ];
                }
            }
        }

        $msg = $mode === 'test'
            ? 'Bozza validata con successo.'
            : 'Schedine inviate con successo.';

        return SubmissionResult::success($msg, $rowDetails, $rawJson);
    }

    // -------------------------------------------------------------------------
    // Code mapping tables (this driver's internal responsibility)
    // -------------------------------------------------------------------------

    /**
     * Map ISO 3166-1 alpha-2 country code to the 9-digit Alloggiati Web code.
     * Data is looked up from the `countries` table (seeded from stati.csv).
     */
    private function countryIsoToAlloggiati(string $iso): string
    {
        $iso  = mb_strtoupper(trim($iso));
        $code = Country::where('iso2', $iso)->value('alloggiati_code');

        if ($code === null) {
            throw new RuntimeException(
                "Unmapped ISO country code for Alloggiati Web: [{$iso}]. " .
                'Add it to the countries table (run CountriesSeeder or insert manually).'
            );
        }

        return $code;
    }

    /** Map internal document type to 5-char Alloggiati Web code (documenti.csv). */
    private function documentTypeToAlloggiati(string $type): string
    {
        return match ($type) {
            'passport'         => 'PASOR', // PASSAPORTO ORDINARIO
            'id_card'          => 'IDENT', // CARTA DI IDENTITA'
            'driving_license'  => 'PATEN', // PATENTE DI GUIDA
            default            => 'CERID', // CERTIFICATO D'IDENTITA' (fallback)
        };
    }

    /** Map 'M'/'F' gender to Alloggiati Web code '1'/'2'. */
    private function genderToAlloggiati(string $gender): string
    {
        return match (mb_strtoupper($gender)) {
            'M' => '1',
            'F' => '2',
            default => throw new RuntimeException("Invalid gender value: [{$gender}]. Expected 'M' or 'F'."),
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
