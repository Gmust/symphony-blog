<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(Request $request): bool
    {
        error_log('Checking if authenticator supports the request');
        $route = $request->attributes->get('_route');
        $isPost = $request->isMethod('POST');
        error_log('Route: ' . $route . ', is POST: ' . ($isPost ? 'true' : 'false'));

        return self::LOGIN_ROUTE === $route && $isPost;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        error_log('Authenticating user: ' . $email);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        error_log('onAuthenticationSuccess called, targetPath: ' . $targetPath);

        if ($targetPath) {
            error_log('Redirecting to target path: ' . $targetPath);
            return new RedirectResponse($targetPath);
        }

        $defaultRedirect = $this->urlGenerator->generate('app_home');
        error_log('Redirecting to default path: ' . $defaultRedirect);

        return new RedirectResponse($defaultRedirect);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}