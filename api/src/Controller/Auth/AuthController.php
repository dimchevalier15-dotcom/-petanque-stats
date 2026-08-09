<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Dto\Auth\RegisterInput;
use App\Service\Auth\CurrentUserService;
use App\Service\Auth\EmailAlreadyUsedException;
use App\Service\Auth\InvalidCredentialsException;
use App\Service\Auth\RegistrationService;
use App\Service\Auth\RegistrationValidationException;
use App\Service\Account\PlayerAlreadyLinkedException;
use App\Service\Account\PlayerNotFoundException;
use App\Dto\Request\LoginRequest;
use App\Service\Auth\LoginService;
use App\Dto\Response\LoginResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private RegistrationService $registrationService,
        private LoginService $loginService,
        private ValidatorInterface $validator,
        private CurrentUserService $currentUserService,
    ) {}

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            /** @var RegisterInput $input */
            $input = $this->serializer->deserialize($request->getContent(), RegisterInput::class, 'json');

            $output = $this->registrationService->register($input);

            $json = $this->serializer->serialize($output, 'json');
            return new JsonResponse($json, 201, [], true);
        } catch (RegistrationValidationException $e) {
            $payload = [
                'error' => 'invalid_request',
                'details' => $e->errors,
            ];
            return new JsonResponse($payload, 400);
        } catch (EmailAlreadyUsedException) {
            $payload = [
                'error' => 'email_already_used',
            ];
            return new JsonResponse($payload, 409);
        } catch (PlayerNotFoundException) {
            return new JsonResponse(['error' => 'player_not_found'], 404);
        } catch (PlayerAlreadyLinkedException) {
            return new JsonResponse(['error' => 'player_already_linked'], 409);
        } catch (\Throwable $e) {
            // Do not expose technical details
            $payload = [
                'error' => $e->getMessage(),
            ];
            return new JsonResponse($payload, 400);
        }
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            /** @var LoginRequest $input */
            $input = $this->serializer->deserialize($request->getContent(), LoginRequest::class, 'json');
            $violations = $this->validator->validate($input);
            if (\count($violations) > 0) {
                return new JsonResponse(['message' => 'Invalid credentials.'], 401);
            }

            $res = $this->loginService->login($input);
            // Ensure exact payload shape {"token":"..."}
            $json = $this->serializer->serialize($res, 'json');
            return new JsonResponse($json, 200, [], true);
        } catch (InvalidCredentialsException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        } catch (\Throwable) {
            // Never expose technical errors
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $authHeader = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
        $token = substr($authHeader, 7);
        try {
            $res = $this->currentUserService->meFromToken($token);
            $json = $this->serializer->serialize($res, 'json');
            return new JsonResponse($json, 200, [], true);
        } catch (\App\Service\Auth\InvalidTokenException) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }
    }
}
