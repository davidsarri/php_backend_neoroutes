<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LoginController
{
    private $userRepository;
    private $passwordEncoder;
    private $jwtManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->jwtManager = $jwtManager;
    }

    /**
     * @Route("/api/user/login", name="user_login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'message' => 'Login successful',
        ], 200);
    }
}
