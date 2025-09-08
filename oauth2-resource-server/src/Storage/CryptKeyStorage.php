<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Storage;

use Hyperf\Contract\ConfigInterface;
use League\OAuth2\Server\CryptKey;
use Menumbing\OAuth2\ResourceServer\MakeCryptKey;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class CryptKeyStorage
{
    use MakeCryptKey;

    protected ?CryptKey $publicKey = null;

    public function __construct(protected ConfigInterface $config)
    {
    }

    public function getPublicKey(): CryptKey
    {
        if (null !== $this->publicKey) {
            return $this->publicKey;
        }
        
        return $this->publicKey = $this->makeKey(
            $this->config->get('oauth2_resource_server.public_key')
        );
    }
}