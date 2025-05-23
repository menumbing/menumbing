<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Server\Bridge\Repository;

use HyperfExtension\Hashing\Contract\HashInterface;
use InvalidArgumentException;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Menumbing\OAuth2\Server\Bridge\Entity\User;
use Menumbing\OAuth2\Server\Contract\UserModelRepositoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class UserRepository implements UserRepositoryInterface
{
    public function __construct(protected UserModelRepositoryInterface $modelRepository, protected HashInterface $hasher)
    {
    }

    public function getUserEntityByUserCredentials(string $username, string $password, string $grantType, ClientEntityInterface $clientEntity): ?UserEntityInterface
    {
        if ('password' !== $grantType) {
            throw new InvalidArgumentException('Unsupported grant type: ' . $grantType);
        }

        if (null === $user = $this->modelRepository->findActive($username)) {
            return null;
        }

        if (!$this->hasher->check($password, $user->getPassword())) {
            return null;
        }

        return new User($user->getIdentifier());
    }
}
