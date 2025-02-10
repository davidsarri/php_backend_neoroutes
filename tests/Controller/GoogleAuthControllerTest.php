<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GoogleAuthControllerTest extends WebTestCase
{
    public function testGoogleAuthSuccessful(): void
    {
        $client = static::createClient();

        // Mock del token de Google
        $idTokenMock = 'mock-google-id-token';

        // Simula la resposta de l'API de Google
        $mockGoogleResponse = [
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ];

        // Simula la petició
        $client->request('POST', '/api/auth/google', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['idToken' => $idTokenMock]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // Comprova que el token JWT és generat
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseContent);

        // Comprova que el token JWT és vàlid (opcional)
        $token = $responseContent['token'];
        $this->assertNotEmpty($token);

        // Valida el token decodificant-lo (opcional)
        $jwtConfig = \Lcobucci\JWT\Configuration::forAsymmetricSigner(
            new \Lcobucci\JWT\Signer\Rsa\Sha256(),
            \Lcobucci\JWT\Signer\Key\InMemory::file($_ENV['JWT_SECRET_KEY'], $_ENV['JWT_PASSPHRASE']),
            \Lcobucci\JWT\Signer\Key\InMemory::file($_ENV['JWT_PUBLIC_KEY'])
        );
        $parsedToken = $jwtConfig->parser()->parse($token);
        $this->assertTrue($jwtConfig->validator()->validate($parsedToken, ...$jwtConfig->validationConstraints()));
    }
}
