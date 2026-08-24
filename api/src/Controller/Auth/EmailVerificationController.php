<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\CurrentUserService;
use App\Service\Auth\EmailVerificationService;
use App\Service\Auth\InvalidTokenException;
use App\Service\Auth\TooManyAuthRequestsException;
use App\Service\Auth\AuthRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
        private CurrentUserService $currentUserService,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    #[Route('/api/auth/verify-email', name: 'api_auth_verify_email', methods: ['GET'])]
    public function verify(Request $request): Response
    {
        $status = $this->emailVerificationService->verify((string) $request->query->get('token', ''));

        return $this->render('auth/verify_email.html.twig', [
            'status' => $status,
        ]);
    }

    #[Route('/api/auth/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resend(Request $request): JsonResponse
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $user = $this->currentUserService->getUserFromToken(substr($authHeader, 7));
        } catch (InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->isEmailVerified()) {
            return new JsonResponse(['alreadyVerified' => true]);
        }

        try {
            $this->rateLimiter->consumeResendVerification((string) $request->getClientIp(), (int) $user->getId());
        } catch (TooManyAuthRequestsException) {
            return new JsonResponse(['error' => 'too_many_requests'], 429);
        }

        $this->emailVerificationService->sendForUser($user);

        return new JsonResponse(['alreadyVerified' => false]);
    }
}
