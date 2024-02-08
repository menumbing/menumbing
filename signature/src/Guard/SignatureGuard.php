<?php

declare(strict_types=1);

namespace Menumbing\Signature\Guard;

use DateTime;
use DateTimeZone;
use Hyperf\Di\Annotation\Inject;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use HyperfExtension\Auth\Contracts\GuardInterface;
use HyperfExtension\Auth\Contracts\UserProviderInterface;
use HyperfExtension\Auth\Exceptions\AuthenticationException;
use HyperfExtension\Auth\GuardHelpers;
use Menumbing\Contract\Signature\ClaimInterface;
use Menumbing\Contract\Signature\ClientInterface;
use Menumbing\Contract\Signature\SignerInterface;
use Menumbing\Signature\Claim;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class SignatureGuard implements GuardInterface
{
    use GuardHelpers;

    #[Inject]
    protected SignerInterface $signer;

    protected string $headerClientIdKey;

    protected string $headerRequestIdKey;

    protected string $headerRequestTimestampKey;

    protected string $headerSignatureKey;

    protected array $headerKeys = [];

    protected string $timezone;

    protected ?int $ttl;

    public function __construct(
        protected ServerRequestInterface $request,
        UserProviderInterface $provider,
        array $options = []
    ) {
        $this->provider = $provider;
        $this->headerClientIdKey = $options['header_client_id_key'] ?? 'Client-Id';
        $this->headerRequestIdKey = $options['header_request_id_key'] ?? 'Request-Id';
        $this->headerRequestTimestampKey = $options['header_request_timestamp_key'] ?? 'Request-Timestamp';
        $this->headerSignatureKey = $options['header_signature_key'] ?? 'Signature';
        $this->timezone = $options['timezone'] ?? 'UTC';
        $this->ttl = $options['ttl'] ?? null;

        $this->headerKeys = [
            $this->headerClientIdKey,
            $this->headerRequestIdKey,
            $this->headerRequestTimestampKey,
            $this->headerSignatureKey,
        ];
    }

    public function user(): ?AuthenticatableInterface
    {
        $clientId = $this->request->getHeaderLine($this->headerClientIdKey);
        $headers = $this->getHeadersFromRequest($this->request);

        if ($this->validate($headers) && null != $client = $this->findClientId($clientId)) {
            $signature = $this->signer->sign($clientId, $client->getSecret(), $this->getClaimFromRequest($this->request));

            if (!$signature->isValid($this->request)) {
                throw new AuthenticationException('Invalid signature.');
            }

            return $client;
        }

        return null;
    }

    public function validate(array $credentials = []): bool
    {
        foreach ($this->headerKeys as $header) {
            if (empty($credentials[$header] ?? null)) {
                throw new AuthenticationException(sprintf('Missing header "%s"', $header));
            }
        }

        $requestTimestamp = DateTime::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            $credentials[$this->headerRequestTimestampKey],
            new DateTimeZone($this->timezone)
        );

        if (false === $requestTimestamp) {
            throw new AuthenticationException(sprintf(
                'Invalid value for header "%s"',
                $this->headerRequestTimestampKey
            ));
        }

        $this->validateTtl($requestTimestamp);

        return true;
    }

    protected function validateTtl(DateTime $requestTimestamp): void
    {
        if (null !== $this->ttl) {
            $threshold = (clone $requestTimestamp)->modify(sprintf('+%d minutes', $this->ttl));
            $now = new DateTime('now', new DateTimeZone($this->timezone));

            if ($now > $threshold) {
                throw new AuthenticationException('The given signature has been expired.');
            }
        }
    }

    protected function getClaimFromRequest(ServerRequestInterface $request): ClaimInterface
    {
        $requestTimestamp = new DateTime($request->getHeaderLine($this->headerRequestTimestampKey));
        $requestTimestamp->setTimezone(new DateTimeZone($this->timezone));
        $requestBody = (string) $request->getBody();

        return new Claim(
            targetPath: '/' . $request->path(),
            body: !empty($requestBody) ? $requestBody : null,
            algo: $this->getAlgo($request),
            requestId: $request->getHeaderLine($this->headerRequestIdKey),
            requestDateTime: $requestTimestamp,
        );
    }

    protected function getHeadersFromRequest(ServerRequestInterface $request): array
    {
        $headers = [];

        foreach ($this->headerKeys as $header) {
            $headers[$header] = $request->getHeaderLine($header) ?? null;
        }

        return $headers;
    }

    protected function findClientId(string $clientId): ClientInterface|AuthenticatableInterface|null
    {
        if (null !== $client = $this->provider->retrieveById($clientId)) {
            if (!$client instanceof ClientInterface) {
                throw new RuntimeException(sprintf(
                    'Object "%s" should implement "%s"',
                    $client::class,
                    ClientInterface::class
                ));
            }

            if (!$client->isEnabled()) {
                throw new AuthenticationException('The client is disabled.');
            }

            return $client;
        }

        return null;
    }

    protected function getAlgo(ServerRequestInterface $request): string
    {
        $signature = $request->getHeaderLine($this->headerSignatureKey);
        $algo = substr($signature, 0, strpos($signature, '='));

        return strtolower(str_replace('HMAC', '', $algo));
    }
}
