<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class GoogleAuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Configuration $jwtConfig;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        // Configuració del JWT
        $privateKeyPath = $_ENV['JWT_SECRET_KEY'];
        $passphrase = $_ENV['JWT_PASSPHRASE'];
        $this->jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath, $passphrase),
            InMemory::file($_ENV['JWT_PUBLIC_KEY'])
        );
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
        $client = new \GuzzleHttp\Client();
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

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Genera un token JWT per l'usuari
        $now = new \DateTimeImmutable();
        $token = $this->jwtConfig->builder()
            ->issuedBy('http://your-app.com') // Issuer
            ->permittedFor('http://your-app.com') // Audience
            ->identifiedBy('4f1g23a12aa', true) // Token ID
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('email', $user->getEmail()) // Custom claim
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return new JsonResponse(['token' => $token->toString()]);
    }
}
