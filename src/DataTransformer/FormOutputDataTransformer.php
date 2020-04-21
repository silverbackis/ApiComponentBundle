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

namespace Silverback\ApiComponentBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use Silverback\ApiComponentBundle\Entity\Component\Form;
use Silverback\ApiComponentBundle\Factory\Form\FormViewFactory;

/**
 * @author Daniel West <daniel@silverback.is>
 */
class FormOutputDataTransformer implements DataTransformerInterface
{
    private FormViewFactory $formViewFactory;

    public function __construct(FormViewFactory $formViewFactory)
    {
        $this->formViewFactory = $formViewFactory;
    }

    /**
     * @param Form $form
     */
    public function transform($form, string $to, array $context = [])
    {
        $form->formView = $this->formViewFactory->create($form);

        return $form;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return $data instanceof Form && Form::class === $to;
    }
}
