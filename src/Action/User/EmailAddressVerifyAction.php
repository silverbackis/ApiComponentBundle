<?php

/*
 * This file is part of the Silverback API Component Bundle Project
 *
 * (c) Daniel West <daniel@silverback.is>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Silverback\ApiComponentBundle\Action\User;

use Silverback\ApiComponentBundle\Action\AbstractAction;
use Silverback\ApiComponentBundle\Manager\User\EmailAddressManager;
use Silverback\ApiComponentBundle\Serializer\RequestFormatResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Daniel West <daniel@silverback.is>
 */
class EmailAddressVerifyAction extends AbstractAction
{
    private EmailAddressManager $emailAddressManager;

    public function __construct(SerializerInterface $serializer, RequestFormatResolver $requestFormatResolver, EmailAddressManager $emailAddressManager)
    {
        parent::__construct($serializer, $requestFormatResolver);
        $this->emailAddressManager = $emailAddressManager;
    }

    public function __invoke(Request $request)
    {
        $data = $this->serializer->decode($request->getContent(), $this->getFormat($request), []);
        $requiredKeys = ['username', 'token', 'email'];
        foreach ($requiredKeys as $requiredKey) {
            if (!isset($data[$requiredKey])) {
                throw new BadRequestHttpException(sprintf('the key `%s` was not found in POST data', $requiredKey));
            }
            ${$requiredKey} = (string) $data[$requiredKey];
        }
        /* @var string $username
         * @var string $token
         * @var string $email
         */
        try {
            $this->emailAddressManager->verifyNewEmailAddress($username, $email, $token);

            return $this->getResponse($request);
        } catch (NotFoundHttpException $exception) {
            return $this->getResponse($request, $exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }
}
