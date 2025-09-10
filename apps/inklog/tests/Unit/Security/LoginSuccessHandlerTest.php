<?php declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\LoginSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class LoginSuccessHandlerTest extends TestCase
{
    private function handler(string $firewall = 'main',
        ?AuthorizationCheckerInterface $auth = null,
        ?UrlGeneratorInterface $url = null
    ): LoginSuccessHandler {
        return new LoginSuccessHandler(
            $url ?? $this->createMock(UrlGeneratorInterface::class),
            $auth ?? $this->createMock(AuthorizationCheckerInterface::class),
            $firewall
        );
    }

    private function reqWithSession(): Request
    {
        $r = new Request();
        $r->setSession(new Session(new MockArraySessionStorage()));
        return $r;
    }

    public function testNoTargetPathRedirectUserToProfile(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, false],
            ['ROLE_USER',  null, true],
        ]);

        $url = $this->createMock(UrlGeneratorInterface::class);
        $url->expects($this->once())
            ->method('generate')
            ->with('app_profile')
            ->willReturn('/profile');

        $resp = $this->handler('main', $auth, $url)
            ->onAuthenticationSuccess(
                new Request(),
                $this->createMock(TokenInterface::class));

        self::assertSame('/profile', $resp->getTargetUrl(), 'Without target path, user logged must be redirect to profile');
    }

    public function testNoTargetPathRedirectAdminToDashboard(): void
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturnMap([
            ['ROLE_ADMIN', null, true],
            ['ROLE_USER',  null, true],
        ]);

        $url = $this->createMock(UrlGeneratorInterface::class);
        $url->expects($this->once())
            ->method('generate')
            ->with('admin_dashboard')
            ->willReturn('/admin/dashboard');

        $response = $this
            ->handler('main', $auth, $url)
            ->onAuthenticationSuccess(
                $this->reqWithSession(),
                $this->createMock(TokenInterface::class)
            );

        self::assertSame('/admin/dashboard', $response->getTargetUrl(), 'Without target path, Admin user must be redirect to admin/dashboard');

    }

    public function testNoTargetPathRedirectNoRoleToDashboard(): void
    {
        $url = $this->createMock(UrlGeneratorInterface::class);
        $url->expects($this->once())
            ->method('generate')
            ->with('app_home')
            ->willReturn('/');

        $response = $this
            ->handler('main', null, $url)
            ->onAuthenticationSuccess(
                $this->reqWithSession(),
                $this->createMock(TokenInterface::class)
            );

        self::assertSame('/', $response->getTargetUrl(), 'Without target path and role, user must be redirect to home');

    }

    public function testMustRedirectToTargetPath(): void
    {
        $request = $this->reqWithSession();
        $request->getSession()->set('_security.other.target_path', '/somewhere');

        $url = $this->createMock(UrlGeneratorInterface::class);
        $url->expects($this->never())->method('generate');

        $resp = $this
            ->handler('other', null, $url)
            ->onAuthenticationSuccess(
                $request,
                $this->createMock(TokenInterface::class),
            );

        self::assertSame('/somewhere', $resp->getTargetUrl(), "Logged user must be redirect to target path url");
        self::assertFalse($request->getSession()->has('_security.other.target_path'), "Target path must be clean after redirect");
    }
}
