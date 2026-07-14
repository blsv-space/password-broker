<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\Service;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Infrastructure\Security\PasswordHasher;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class AuthDomainService implements DomainServiceInterface
{
    use SingletonTrait;

    private const string JWT_TTL_DEFAULT = '1 day';
    private const string REFRESH_TTL_DEFAULT = '30 days';

    private PasswordHasher $passwordHasher;


    public function __construct()
    {
        $this->passwordHasher = PasswordHasher::getInstance();
    }


    public function verifyPasswordByUser(User $user, string $password): bool
    {
        return $this->passwordHasher->verify(
            plain: $password,
            hashed: $user->hashedPassword->toRaw(),
        );
    }
}
