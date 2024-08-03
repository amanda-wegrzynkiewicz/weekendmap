<?php

namespace App\Tests\Service;

use App\Service\TokenGenerator;
use PHPUnit\Framework\TestCase;

class TokenGeneratorTest extends TestCase
{
    public function testGenerateToken()
    {
        $tokenGenerator = new TokenGenerator();
        $token = $tokenGenerator->generateToken(16);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }
}
