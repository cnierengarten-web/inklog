<?php declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter
{
    public const string DELETE = 'USER_DELETE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false; // anonyme
        }

        /** @var User $target */
        $target = $subject;

        // 1) User cannot delete himself
        if ($target->getId() !== null && $current->getId() === $target->getId()) {
            return false;
        }

        // 2) Only super admin can delete super admin
        if (in_array('ROLE_SUPER_ADMIN', $target->getRoles(), true)
            && !$this->security->isGranted('ROLE_SUPER_ADMIN')
        ) {
            return false;
        }

        // 3) otherwise user must be at least admin
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
