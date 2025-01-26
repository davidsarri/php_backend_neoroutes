<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @Route("/api/user/register", name="api_register", methods={"POST"})
     */
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setUsername($data['username'] ?? '');
        $hashedPassword = $passwordEncoder->encodePassword($user, $data['password'] ?? '');
        $user->setPassword($hashedPassword);

        $ip = $this->getClientIp();

        $user->setIp($ip);

        // Validació de camps -- falta validar que el email i l'usuari han de ser unics
        $errors = $validator->validate($user);
        if (count($errors) == 0) {
            $user->setCreatedAt(new \DateTime('now'));

            // Desa l'usuari
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'User registered'], 201);

        } else {
            $errorMessages = $this->geValidationErrorsToString($errors);
            return new JsonResponse(['errors' => $errorMessages], 400);
        }
    }

    /**
     * @return string
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            if(isset( $_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip = '127.0.0.1';
            }
        }
        return $ip;
    }

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface $errors
     * @return array
     */
    public function geValidationErrorsToString(\Symfony\Component\Validator\ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = [
                'field' => $error->getPropertyPath(),
                'message' => $error->getMessage(),
            ];
        }
        return $errorMessages;
    }
}