<?php

namespace App\Shared\Infrastructure\Security;

use App\Shared\Domain\Service\JwtTokenGeneratorInterface;
use App\Shared\Infrastructure\Security\Exception\JwtInvalidTokenException;
use App\Shared\Infrastructure\Security\Exception\JwtTokenExpiredException;
use DateInterval;
use DateMalformedIntervalStringException;
use DateTime;
use Inquisition\Foundation\Singleton\SingletonTrait;

class JwtTokenGenerator
    implements JwtTokenGeneratorInterface
{
    use SingletonTrait;

    public const int TTL = 3600;
    public const string DATE_FORMAT = 'Y-m-d H:i:s O';

    private const string PARAM_ISSUED_AT = 'iat';
    private const string PARAM_EXPIRATION = 'exp';
    private const string PARAM_ALGORITHM = 'alg';
    private const string PARAM_TYPE = 'typ';

    /**
     * @param string $secret
     * @param array|null $payload
     * @param DateInterval|null $ttl
     * @param JwtAlgoEnum|null $algoEnum
     * @return string
     */
    public function generate(string $secret, ?array $payload, ?DateInterval $ttl = null, ?JwtAlgoEnum $algoEnum = null): string
    {
        $payload = $payload ?? [];
        $ttlInterval = $ttl ?? DateInterval::createFromDateString('PT' . self::TTL . 'S');
        $algoEnum = $algoEnum ?? JwtAlgoEnum::default();

        $header = [self::PARAM_ALGORITHM => $algoEnum->name, self::PARAM_TYPE => 'JWT'];
        $dateTime = new DateTime();

        $payload[self::PARAM_ISSUED_AT] = $dateTime->format(static::DATE_FORMAT);
        $payload[self::PARAM_EXPIRATION] = $dateTime->add($ttlInterval)->format(static::DATE_FORMAT);

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->generateSignature($algoEnum, $base64UrlHeader, $base64UrlPayload, $secret);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $signature;
    }

    /**
     * @param string $token
     * @param string $secret
     *
     * @return array
     * @throws JwtInvalidTokenException
     * @throws JwtTokenExpiredException
     */
    public function verify(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new JwtInvalidTokenException('Invalid token format. Expected 3 parts, got ' . count($parts));
        }

        [$header, $payload, $signature] = $parts;

        if (empty($header) || empty($payload) || empty($signature)) {
            throw new JwtInvalidTokenException('Invalid token format. Empty part');
        }

        $headerDecoded = $this->base64UrlDecode($header);
        $headerData = json_decode($headerDecoded, true);
        if (!$headerData
            || !is_array($headerData)
            || !array_key_exists(self::PARAM_ALGORITHM, $headerData)
        ) {
            throw new JwtInvalidTokenException(
                'Invalid token format. Header is not valid JSON or does not contain '
                . self::PARAM_ALGORITHM . ' parameter');
        }

        $algoEnum = JwtAlgoEnum::getFromJWTIdentifier($headerData[self::PARAM_ALGORITHM]);
        $expectedSignature = $this->generateSignature($algoEnum, $header, $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new JwtInvalidTokenException('Invalid token signature');
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        if (!$data
            || !is_array($data)
            || !array_key_exists(self::PARAM_EXPIRATION, $data)
        ) {
            throw new JwtInvalidTokenException('Invalid token format. Payload is not valid JSON or does not contain '
                . self::PARAM_EXPIRATION . ' parameter');
        }

        if (new DateTime() > DateTime::createFromFormat(static::DATE_FORMAT, $data[self::PARAM_EXPIRATION])) {
            throw new JwtTokenExpiredException();
        }

        return $data;
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param JwtAlgoEnum $algoEnum
     * @param string $base64UrlHeader
     * @param string $base64UrlPayload
     * @param string $secret
     * @return string
     */
    public function generateSignature(JwtAlgoEnum $algoEnum, string $base64UrlHeader, string $base64UrlPayload, string $secret): string
    {

        return $this->base64UrlEncode(hash_hmac(
            $algoEnum->value,
            $base64UrlHeader . '.' . $base64UrlPayload,
            $secret,
            true
        ));
    }
}