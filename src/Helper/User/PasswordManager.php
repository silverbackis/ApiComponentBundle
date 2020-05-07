<?php

/*
 * This file is part of the Silverback API Components Bundle Project
 *
 * (c) Daniel West <daniel@silverback.is>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Silverback\ApiComponentsBundle\Helper\User;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Silverback\ApiComponentsBundle\Entity\User\AbstractUser;
use Silverback\ApiComponentsBundle\Exception\InvalidArgumentException;
use Silverback\ApiComponentsBundle\Exception\UnexpectedValueException;
use Silverback\ApiComponentsBundle\Repository\User\UserRepository;
use Silverback\ApiComponentsBundle\Security\TokenGenerator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Daniel West <daniel@silverback.is>
 */
class PasswordManager
{
    private UserMailer $userMailer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private int $tokenTtl;

    public function __construct(UserMailer $userMailer, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository, int $tokenTtl = 8600)
    {
        $this->userMailer = $userMailer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->tokenTtl = $tokenTtl;
    }

    public function requestResetEmail(string $usernameQuery): void
    {
        $user = $this->userRepository->findOneBy(['username' => $usernameQuery]);
        if (!$user) {
            throw new UnexpectedValueException('Username not found');
        }

        if ($user->isPasswordRequestLimitReached($this->tokenTtl)) {
            return;
        }

        $username = $user->getUsername();
        if (!$username) {
            throw new InvalidArgumentException(sprintf('The entity %s should have a username set to send a password reset email.', AbstractUser::class));
        }
        $user->setNewPasswordConfirmationToken(TokenGenerator::generateToken());
        $user->setPasswordRequestedAt(new DateTime());
        $this->userMailer->sendPasswordResetEmail($user);
        $this->entityManager->flush();
    }

    public function passwordReset(string $username, string $token, string $newPassword): void
    {
        $user = $this->userRepository->findOneByPasswordResetToken($username, $token);
        if (!$user) {
            throw new UnexpectedValueException('Username not found');
        }

        $user->setPlainPassword($newPassword);
        $user->setNewPasswordConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $errors = $this->validator->validate($user, null, ['User:password:create']);
        if (\count($errors)) {
            throw new ValidationException($errors);
        }
        $this->persistPlainPassword($user);
    }

    public function persistPlainPassword(AbstractUser $user): AbstractUser
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $user->eraseCredentials();

        return $user;
    }
}