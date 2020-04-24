<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Exception\User;
use App\Repository\UserRepository;
use App\Service\BaseService;
use App\Service\RedisService;
use Firebase\JWT\JWT;

final class UserService extends BaseService
{
    private const REDIS_KEY = 'user:%s';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var RedisService
     */
    protected $redisService;

    public function __construct(UserRepository $userRepository, RedisService $redisService)
    {
        $this->userRepository = $userRepository;
        $this->redisService = $redisService;
    }

    public function getAll(): array
    {
        return $this->userRepository->getAll();
    }

    public function getOne(int $userId)
    {
        if (self::isRedisEnabled() === true) {
            $user = $this->getUserFromCache($userId);
        } else {
            $user = $this->getUserFromDb($userId);
        }

        return $user;
    }

    public function getUserFromCache(int $userId)
    {
        $redisKey = sprintf(self::REDIS_KEY, $userId);
        $key = $this->redisService->generateKey($redisKey);
        if ($this->redisService->exists($key)) {
            $data = $this->redisService->get($key);
            $user = json_decode(json_encode($data), false);
        } else {
            $user = $this->getUserFromDb($userId);
            $this->redisService->setex($key, $user);
        }

        return $user;
    }

    public function search(string $usersName): array
    {
        return $this->userRepository->search($usersName);
    }

    public function saveInCache($id, $user): void
    {
        $redisKey = sprintf(self::REDIS_KEY, $id);
        $key = $this->redisService->generateKey($redisKey);
        $this->redisService->setex($key, $user);
    }

    public function deleteFromCache($userId): void
    {
        $redisKey = sprintf(self::REDIS_KEY, $userId);
        $key = $this->redisService->generateKey($redisKey);
        $this->redisService->del($key);
    }

    public function create($input)
    {
        $user = new \stdClass();
        $data = json_decode(json_encode($input), false);
        if (!isset($data->name)) {
            throw new User('The field "name" is required.', 400);
        }
        if (!isset($data->email)) {
            throw new User('The field "email" is required.', 400);
        }
        if (!isset($data->password)) {
            throw new User('The field "password" is required.', 400);
        }
        $user->name = self::validateUserName($data->name);
        $user->email = self::validateEmail($data->email);
        $user->password = hash('sha512', $data->password);
        $this->userRepository->checkUserByEmail($user->email);
        $users = $this->userRepository->create($user);
        if (self::isRedisEnabled() === true) {
            $this->saveInCache($users->id, $users);
        }

        return $users;
    }

    public function update(array $input, int $userId)
    {
        $user = $this->getUserFromDb($userId);
        $data = json_decode(json_encode($input), false);
        if (!isset($data->name) && !isset($data->email)) {
            throw new User('Enter the data to update the user.', 400);
        }
        if (isset($data->name)) {
            $user->name = self::validateUserName($data->name);
        }
        if (isset($data->email)) {
            $user->email = self::validateEmail($data->email);
        }
        $users = $this->userRepository->update($user);
        if (self::isRedisEnabled() === true) {
            $this->saveInCache($users->id, $users);
        }

        return $users;
    }

    public function delete(int $userId): string
    {
        $this->getUserFromDb($userId);
        $this->userRepository->deleteUserTasks($userId);
        $data = $this->userRepository->delete($userId);
        if (self::isRedisEnabled() === true) {
            $this->deleteFromCache($userId);
        }

        return $data;
    }

    public function login(?array $input): string
    {
        $data = json_decode(json_encode($input), false);
        if (!isset($data->email)) {
            throw new User('The field "email" is required.', 400);
        }
        if (!isset($data->password)) {
            throw new User('The field "password" is required.', 400);
        }
        $password = hash('sha512', $data->password);
        $user = $this->userRepository->loginUser($data->email, $password);
        $token = [
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
        ];

        return JWT::encode($token, getenv('SECRET_KEY'));
    }

    protected function getUserFromDb(int $userId)
    {
        return $this->userRepository->getUser($userId);
    }
}