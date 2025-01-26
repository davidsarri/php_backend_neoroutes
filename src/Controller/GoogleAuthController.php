<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
class GoogleAuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/auth/google', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return new JsonResponse(['error' => 'Token d’identitat requerit'], 400);
        }

        // Valida el token amb Google
        $client = new Client();
        $response = $client->get('https://oauth2.googleapis.com/tokeninfo', [
            'query' => ['id_token' => $idToken],
        ]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['error' => 'Token invàlid'], 400);
        }

        $googleData = json_decode($response->getBody()->getContents(), true);

        $email = $googleData['email'];
        $username = $googleData['name'];

        // Busca l'usuari a la base de dades
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Si no existeix, registra l'usuari
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            // Omple els camps necessaris per complir LGPD
            // $user->setPassword(null);

            $em = $this->entityManager->getManager();
            $em->persist($user);
            $em->flush();
        }

        // Genera un token JWT per l'usuari
        $jwt = JWT::encode(
            ['email' => $user->getEmail(), 'exp' => time() + 3600],
            'your-secret-key',
            'HS256'
        );

        return new JsonResponse(['token' => $jwt]);
    }
}