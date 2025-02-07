<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Transformer\UserTransformer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private $userRepository;
    private $passwordHasher;
    private $userTransformer;
    private $userService;

    protected function setUp(): void
    {
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->passwordHasher = Mockery::mock(UserPasswordHasherInterface::class);
        $this->userTransformer = Mockery::mock(UserTransformer::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->passwordHasher,
            $this->userTransformer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testUpdateUserProfile()
    {
        $user = new User();
        $user->setPassword('old_password');

        $this->passwordHasher
            ->shouldReceive('isPasswordValid')
            ->once()
            ->with($user, 'current_password')
            ->andReturn(true);

        $this->passwordHasher
            ->shouldReceive('encodePassword')
            ->once()
            ->with($user, 'new_password')
            ->andReturn('encoded_new_password');

        $this->userRepository
            ->shouldReceive('save')
            ->once()
            ->with($user);

        $this->userService->updateUserProfile($user, 'new_username', 'current_password', 'new_password');

        $this->assertEquals('new_username', $user->getUsername());
        $this->assertEquals('encoded_new_password', $user->getPassword());
    }

    public function testUpdateUserProfileIncorrectCurrentPassword()
    {
        $user = new User();
        $user->setPassword('old_password');

        $this->passwordHasher
            ->shouldReceive('isPasswordValid')
            ->once()
            ->with($user, 'incorrect_current_password')
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Current password is incorrect.');

        $this->userService->updateUserProfile($user, 'new_username', 'incorrect_current_password', 'new_password');
    }

    public function testTransformUser()
    {
        $user = new User();
        $transformedUser = ['id' => 1, 'username' => 'testuser', 'email' => 'test@example.com'];

        $this->userTransformer
            ->shouldReceive('transform')
            ->once()
            ->with($user)
            ->andReturn($transformedUser);

        $result = $this->userService->transformUser($user);

        $this->assertEquals($transformedUser, $result);
    }

    public function testReverseTransformUser()
    {
        $user = new User();
        $data = ['username' => 'updated_user', 'email' => 'updated@example.com'];

        $this->userTransformer
            ->shouldReceive('reverseTransform')
            ->once()
            ->with($data, $user)
            ->andReturn($user);

        $result = $this->userService->reverseTransformUser($data, $user);

        $this->assertEquals($user, $result);
    }
}
