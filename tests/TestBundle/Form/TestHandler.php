<?php

namespace Silverback\ApiComponentBundle\Tests\TestBundle\Form;

use Silverback\ApiComponentBundle\Entity\Component\Form\Form;
use Silverback\ApiComponentBundle\Form\Handler\FormHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestHandler implements FormHandlerInterface
{
    public $info;

    public function success(Form $form, $data, Request $request): ?Response
    {
        $this->info = 'Form submitted';
        return null;
    }
}
