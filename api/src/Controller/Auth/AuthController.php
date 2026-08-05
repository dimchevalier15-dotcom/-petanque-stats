<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Dto\Auth\RegisterInput;
use App\Service\Auth\EmailAlreadyUsedException;
use App\Service\Auth\RegistrationService;
use App\Service\Auth\RegistrationValidationException;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private RegistrationService $registrationService,
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTEncoderInterface $jwtEncoder,
    ) {}

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            /** @var RegisterInput $input */
            $input = $this->serializer->deserialize($request->getContent(), RegisterInput::class, 'json');

            $output = $this->registrationService->register($input);

            return $this->json($output, status: 201);
        } catch (RegistrationValidationException $e) {
            return $this->json([
                'error' => 'invalid_request',
                'details' => $e->errors,
            ], status: 400);
        } catch (EmailAlreadyUsedException) {
            return $this->json([
                'error' => 'email_already_used',
            ], status: 409);
        } catch (\Throwable $e) {
            // Do not expose technical details
            return $this->json([
                'error' => $e->getMessage(),
            ], status: 400);
        }
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            /** @var array{email?: string, password?: string} $payload */
            $payload = (array) json_decode($request->getContent(), true);
            $email = isset($payload['email']) && \is_string($payload['email']) ? trim($payload['email']) : '';
            $password = isset($payload['password']) && \is_string($payload['password']) ? $payload['password'] : '';

            if ($email === '' || $password === '') {
                return $this->json(['message' => 'Invalid credentials.'], 401);
            }

            $user = $this->users->findOneBy(['email' => $email]);
            if ($user === null) {
                return $this->json(['message' => 'Invalid credentials.'], 401);
            }

            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                return $this->json(['message' => 'Invalid credentials.'], 401);
            }

            // Build a payload compatible with Lexik configuration (username claim is conventional)
            $token = $this->jwtEncoder->encode([
                'username' => $user->getEmail(),
                'sub' => (string) $user->getId(),
                'roles' => [],
                'exp' => time() + 3600, // 1 hour validity by default
                'iat' => time(),
            ]);

            return $this->json(['token' => $token]);
        } catch (\Throwable) {
            // Never expose technical errors
            return $this->json(['message' => 'Invalid credentials.'], 401);
        }
    }
}
