<?php declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $auth,
        private readonly string $firewallName = 'main',
    ) {
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $targetPath = $this->getTargetPath($session, $this->firewallName);
            if ($targetPath) {
                $this->removeTargetPath($session, $this->firewallName);

                return new RedirectResponse($targetPath);
            }
        }

        if ($this->auth->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        if ($this->auth->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
