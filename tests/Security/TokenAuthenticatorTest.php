<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Security;

use App\Security\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @covers \App\Security\TokenAuthenticator
 */
class TokenAuthenticatorTest extends TestCase
{
    public function testRememberMe()
    {
        $factory = $this->createMock(UserPasswordHasherInterface::class);
        $sut = new TokenAuthenticator($factory);

        self::assertFalse($sut->supportsRememberMe());
    }

    public function testSupports()
    {
        $factory = $this->createMock(UserPasswordHasherInterface::class);
        $sut = new TokenAuthenticator($factory);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'dfghj/api/doc/dfghj']);
        self::assertFalse($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo']);
        self::assertTrue($sut->supports($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-SESSION' => true]);
        self::assertFalse($sut->supports($request));
    }

    public function testGetCredentials()
    {
        $factory = $this->createMock(UserPasswordHasherInterface::class);
        $sut = new TokenAuthenticator($factory);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-SESSION' => true]);
        self::assertEquals(['user' => null, 'token' => null], $sut->getCredentials($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo']);
        self::assertEquals(['user' => 'foo', 'token' => null], $sut->getCredentials($request));

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/api/fooo', 'HTTP_X-AUTH-USER' => 'foo', 'HTTP_X-AUTH-TOKEN' => 'bar']);
        self::assertEquals(['user' => 'foo', 'token' => 'bar'], $sut->getCredentials($request));
    }
}
