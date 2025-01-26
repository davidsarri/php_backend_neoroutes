<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();

        // Simula una petició POST a l'endpoint amb dades vàlides
        $client->request('POST', '/api/user/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'testlogin@example.com',
            'username' => 'testlogin',
            'password' => 'securepassword2'
        ]));

        // Dades de prova
        $userEmail = 'testlogin@example.com';
        $userPassword = 'securepassword2';

        // Verifica que la resposta és un 201 Created
        $this->assertResponseStatusCodeSame(201);

        // Simula una petició POST a l'endpoint de login
        $client->request('POST', '/api/user/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $userEmail,
            'password' => $userPassword,
        ]));

        // Verifica que la resposta és un 200 OK
        $this->assertResponseStatusCodeSame(200);

        // Verifica que el contingut de la resposta és JSON
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        // Deserialitza la resposta
        $responseData = json_decode($response->getContent(), true);

        // Verifica que hi ha un token en la resposta
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertNotEmpty($responseData['token']);

        // Opcional: verifica que el token té un format JWT vàlid
        $parts = explode('.', $responseData['token']);
        $this->assertCount(3, $parts, 'El token JWT ha de tenir 3 parts.');
    }
}