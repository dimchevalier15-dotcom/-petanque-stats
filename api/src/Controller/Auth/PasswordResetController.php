<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Dto\Request\ForgotPasswordRequest;
use App\Dto\Request\ResetPasswordRequest;
use App\Service\Auth\InvalidAuthTokenException;
use App\Service\Auth\PasswordResetService;
use App\Service\Auth\RegistrationValidationException;
use App\Service\Auth\TooManyAuthRequestsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordResetController extends AbstractController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/auth/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $payload = ['message' => PasswordResetService::GENERIC_REQUEST_MESSAGE];

        try {
            /** @var ForgotPasswordRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), ForgotPasswordRequest::class, 'json');
        } catch (\Throwable) {
            return new JsonResponse($payload);
        }

        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            return new JsonResponse($payload);
        }

        try {
            $this->passwordResetService->requestReset($input->email, (string) $request->getClientIp());
        } catch (TooManyAuthRequestsException) {
            return new JsonResponse(['error' => 'too_many_requests'], 429);
        }

        return new JsonResponse($payload);
    }

    #[Route('/api/auth/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            /** @var ResetPasswordRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), ResetPasswordRequest::class, 'json');
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'invalid_request'], 400);
        }

        try {
            $this->passwordResetService->resetPassword($input);
        } catch (RegistrationValidationException $e) {
            return new JsonResponse([
                'error' => 'invalid_request',
                'details' => $e->errors,
            ], 400);
        } catch (InvalidAuthTokenException) {
            return new JsonResponse(['error' => 'invalid_token'], 400);
        }

        return new JsonResponse(['message' => 'Password has been reset.']);
    }
}
