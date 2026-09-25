<?php

namespace App\Application\Auth;

use App\Application\Auth\DataTransferObjects\TokenPair;
use App\Application\Auth\Ports\Inbound\IAuthServicePort;
use App\Application\Auth\Ports\Outbound\IAccessTokenIssuerPort;
use App\Application\Auth\Ports\Outbound\IRefreshTokenRepositoryPort;
use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Application\Merchant\Ports\Outbound\IMerchantRepositoryPort;
use App\Application\User\Ports\Outbound\IPasswordHasherPort;
use App\Application\User\Ports\Outbound\IUserRepositoryPort;
use App\Domain\Auth\Exceptions\InvalidCredentials;
use App\Domain\Auth\Exceptions\InvalidRefreshToken;
use App\Domain\Auth\Exceptions\RefreshTokenReuseDetected;
use App\Domain\Auth\RefreshToken;
use App\Domain\Membership\Membership;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\Merchant\ValueObjects\MerchantStatus;
use App\Domain\Merchant\Exceptions\MerchantNotFound;
use App\Domain\User\Exceptions\EmailAlreadyRegistered;
use App\Domain\User\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class AuthService implements IAuthServicePort
{
    public function __construct(
        private readonly IUserRepositoryPort $users,
        private readonly IMerchantRepositoryPort $merchants,
        private readonly IMembershipRepositoryPort $memberships,
        private readonly IRefreshTokenRepositoryPort $refreshTokens,
        private readonly IPasswordHasherPort $passwordHasher,
        private readonly IAccessTokenIssuerPort $accessTokens,
        private readonly ITransactionManagerPort $transactions,
    ) {}

    public function register(string $email, string $password, string $merchantName): TokenPair
    {
        return $this->transactions->run(function () use ($email, $password, $merchantName): TokenPair {
            $emailValue = Email::fromString($email);
            if ($this->users->findByEmail($emailValue) !== null) {
                throw EmailAlreadyRegistered::withEmail($emailValue);
            }

            $user = User::register(UserId::generate(), $emailValue, $this->passwordHasher->hash($password));
            $merchant = Merchant::create(MerchantId::generate(), MerchantName::fromString($merchantName));
            $membership = Membership::create(
                MembershipId::generate(),
                $user->id(),
                $merchant->id(),
                Role::Owner,
            );

            $this->users->save($user);
            $this->merchants->save($merchant);
            $this->memberships->save($membership);

            return $this->issueTokenPair(
                $user->id()->toString(),
                $merchant->id()->toString(),
                $membership->role()->label(),
            );
        });
    }

    public function login(string $email, string $password, string $merchantId): TokenPair
    {
        return $this->transactions->run(function () use ($email, $password, $merchantId): TokenPair {
            $user = $this->users->findByEmail(Email::fromString($email));
            if ($user === null || ! $user->isActive() || ! $this->passwordHasher->verify($password, $user->passwordHash())) {
                throw new InvalidCredentials;
            }

            try {
                $merchant = $this->merchants->get(MerchantId::fromString($merchantId));
            } catch (MerchantNotFound) {
                throw new InvalidCredentials;
            }
            $membership = $this->memberships->findByUserAndMerchant($user->id(), $merchant->id());
            if ($merchant->status() !== MerchantStatus::Active || $membership === null) {
                throw new InvalidCredentials;
            }

            return $this->issueTokenPair(
                $user->id()->toString(),
                $merchant->id()->toString(),
                $membership->role()->label(),
            );
        });
    }

    public function refresh(string $refreshToken): TokenPair
    {
        $result = $this->transactions->run(function () use ($refreshToken): TokenPair|RefreshTokenReuseDetected|InvalidRefreshToken {
            $stored = $this->refreshTokens->findByHash(self::hashRefreshToken($refreshToken));
            if ($stored === null) {
                return new InvalidRefreshToken;
            }

            if ($stored->isRevoked()) {
                $this->refreshTokens->revokeFamily($stored->familyId());

                return new RefreshTokenReuseDetected;
            }

            $now = new DateTimeImmutable;
            if ($stored->isExpired($now)) {
                $stored->revoke($now);
                $this->refreshTokens->save($stored);

                return new InvalidRefreshToken;
            }

            $user = $this->users->get(UserId::fromString($stored->userId()));
            $merchant = $this->merchants->get(MerchantId::fromString($stored->merchantId()));
            $membership = $this->memberships->findByUserAndMerchant($user->id(), $merchant->id());
            if (! $user->isActive() || $merchant->status() !== MerchantStatus::Active || $membership === null) {
                $this->refreshTokens->revokeFamily($stored->familyId());

                return new InvalidRefreshToken;
            }

            $replacementId = (string) Str::uuid();
            $stored->rotate($replacementId, $now);
            $this->refreshTokens->save($stored);

            return $this->issueTokenPair(
                $user->id()->toString(),
                $merchant->id()->toString(),
                $membership->role()->label(),
                $stored->familyId(),
                $replacementId,
            );
        });

        if ($result instanceof RefreshTokenReuseDetected || $result instanceof InvalidRefreshToken) {
            throw $result;
        }

        return $result;
    }

    public function logout(string $refreshToken): void
    {
        $this->transactions->run(function () use ($refreshToken): void {
            $stored = $this->refreshTokens->findByHash(self::hashRefreshToken($refreshToken));
            if ($stored !== null) {
                $this->refreshTokens->revokeFamily($stored->familyId());
            }
        });
    }

    private function issueTokenPair(
        string $userId,
        string $merchantId,
        string $role,
        ?string $familyId = null,
        ?string $refreshTokenId = null,
    ): TokenPair {
        $plainRefreshToken = self::generateRefreshToken();
        $refreshToken = RefreshToken::issue(
            $refreshTokenId ?? (string) Str::uuid(),
            $familyId ?? (string) Str::uuid(),
            $userId,
            $merchantId,
            $role,
            self::hashRefreshToken($plainRefreshToken),
            (new DateTimeImmutable)->modify('+'.(int) config('auth_tokens.refresh_ttl_seconds').' seconds'),
        );
        $this->refreshTokens->save($refreshToken);

        return new TokenPair(
            $this->accessTokens->issue($userId, $merchantId, $role),
            $plainRefreshToken,
            $this->accessTokens->expiresIn(),
            $userId,
            $merchantId,
            $role,
        );
    }

    private static function generateRefreshToken(): string
    {
        return 'rt_'.rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    private static function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
