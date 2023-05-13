<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Entity;
use App\Exception\RateLimitExceededException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\RateLimit;
use Doctrine\ORM\EntityManagerInterface;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;

final class LoginAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RateLimit $rateLimit,
        private readonly Entity\Repository\SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $auth = $request->getAuth();
        $acl = $request->getAcl();

        // Check installation completion progress.
        $settings = $this->settingsRepo->readSettings();

        if (!$settings->isSetupComplete()) {
            $num_users = (int)$this->em->createQuery(
                <<<'DQL'
                    SELECT COUNT(u.id) FROM App\Entity\User u
                DQL
            )->getSingleScalarResult();

            if (0 === $num_users) {
                return $response->withRedirect($request->getRouter()->named('setup:index'));
            }
        }

        if ($auth->isLoggedIn()) {
            return $response->withRedirect($request->getRouter()->named('dashboard'));
        }

        $flash = $request->getFlash();

        if ($request->isPost()) {
            try {
                $this->rateLimit->checkRequestRateLimit($request, 'login', 30, 5);
            } catch (RateLimitExceededException) {
                $flash->error(
                    sprintf(
                        '<b>%s</b><br>%s',
                        __('Too many login attempts'),
                        __('You have attempted to log in too many times. Please wait 30 seconds and try again.')
                    ),
                );

                return $response->withRedirect($request->getUri()->getPath());
            }

            $user = $auth->authenticate($request->getParam('username'), $request->getParam('password'));

            if ($user instanceof Entity\User) {
                $session = $request->getSession();

                // If user selects "remember me", extend the cookie/session lifetime.
                if ($session instanceof SessionCookiePersistenceInterface) {
                    $rememberMe = (bool)$request->getParam('remember', 0);
                    /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                    $session->persistSessionFor(($rememberMe) ? 86400 * 14 : 0);
                }

                // Reload ACL permissions.
                $acl->reload();

                // Persist user as database entity.
                $this->em->persist($user);
                $this->em->flush();

                // Redirect for 2FA.
                if (!$auth->isLoginComplete()) {
                    return $response->withRedirect($request->getRouter()->named('account:login:2fa'));
                }

                // Redirect to complete setup if it's not completed yet.
                if (!$settings->isSetupComplete()) {
                    $flash->success(
                        sprintf(
                            '<b>%s</b><br>%s',
                            __('Logged in successfully.'),
                            __('Complete the setup process to get started.')
                        ),
                    );
                    return $response->withRedirect($request->getRouter()->named('setup:index'));
                }

                $flash->success(
                    '<b>' . __('Logged in successfully.') . '</b><br>' . $user->getEmail(),
                );

                $referrer = $session->get('login_referrer');
                return $response->withRedirect(
                    (!empty($referrer)) ? $referrer : $request->getRouter()->named('dashboard')
                );
            }

            $flash->error(
                '<b>' . __('Login unsuccessful') . '</b><br>' . __('Your credentials could not be verified.'),
            );

            return $response->withRedirect((string)$request->getUri());
        }

        return $request->getView()->renderToResponse($response, 'frontend/account/login');
    }
}
