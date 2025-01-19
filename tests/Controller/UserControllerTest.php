<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testRegisterUser(): void
    {
        $client = static::createClient();

        // Simula una petició POST a l'endpoint amb dades vàlides
        $client->request('POST', '/api/user/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'securepassword'
        ]));

        // Verifica que la resposta és un 201 Created
        $this->assertResponseStatusCodeSame(201);

        // Verifica el contingut de la resposta
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('User registered', $responseData['message']);
    }

    public function testRegisterUserWithDuplicateEmail(): void
    {
        $client = static::createClient();

        // Simula una petició POST a l'endpoint amb un email duplicat
        $client->request('POST', '/api/user/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'username' => 'newuser',
            'password' => 'securepassword'
        ]));

        // Verifica que la resposta és un 400 Bad Request
        $this->assertResponseStatusCodeSame(400);

        // Verifica el contingut de la resposta
        $response = $client->getResponse();
        $this->assertJson($response->getContent());

        $responseData = json_decode($response->getContent(), true);

        // Verifica que els errors estan estructurats correctament
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertIsArray($responseData['errors']);
        $this->assertGreaterThan(0, count($responseData['errors']));

        $error = $responseData['errors'][0];
        $this->assertArrayHasKey('field', $error);
        $this->assertArrayHasKey('message', $error);

        // Exemple: Verifica un error específic al camp email
        $this->assertEquals('email', $error['field']);
        $this->assertEquals('Email already in use', $error['message']);
    }

}