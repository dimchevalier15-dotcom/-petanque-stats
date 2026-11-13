<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Entity\User;
use App\Service\Tournament\TournamentAccessException;
use App\Service\Tournament\TournamentNotFoundException;
use App\Service\Tournament\TournamentRuleException;
use App\Service\Tournament\TournamentValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TournamentHttp
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function user(?UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw new TournamentAccessException();
        }

        return $user;
    }

    public function body(Request $request, string $class): object
    {
        $content = $request->getContent();
        $dto = $this->serializer->deserialize($content === '' ? '{}' : $content, $class, 'json');
        if (!is_object($dto)) {
            throw new TournamentValidationException(['body' => 'Invalid payload.']);
        }
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
            }
            throw new TournamentValidationException($errors);
        }

        return $dto;
    }

    public function respond(callable $action, int $status = 200): Response
    {
        try {
            $payload = $action();
        } catch (TournamentValidationException $exception) {
            return new JsonResponse(['errors' => $exception->errors], 400);
        } catch (TournamentNotFoundException) {
            return new JsonResponse(['error' => 'not_found', 'message' => 'Tournament not found.'], 404);
        } catch (TournamentAccessException) {
            return new JsonResponse(['error' => 'forbidden', 'message' => 'Forbidden.'], 403);
        } catch (TournamentRuleException $exception) {
            return new JsonResponse([
                'error' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ], $exception->statusCode);
        }

        if ($status === 204) {
            return new Response(null, 204);
        }

        return new JsonResponse($this->serializer->serialize($payload, 'json'), $status, [], true);
    }
}
