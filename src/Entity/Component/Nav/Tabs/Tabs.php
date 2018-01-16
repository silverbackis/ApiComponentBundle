<?php

namespace Silverback\ApiComponentBundle\Entity\Component\Nav\Tabs;

use ApiPlatform\Core\Annotation\ApiResource;
use Silverback\ApiComponentBundle\Entity\Component\Nav\Nav;
use Silverback\ApiComponentBundle\Entity\Component\Nav\NavItemInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource()
 * @ORM\Entity()
 */
class Tabs extends Nav
{
    /**
     * @ORM\OneToMany(targetEntity="Tab", mappedBy="nav")
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     * @Groups({"layout", "page"})
     */
    protected $items;

    public function createNavItem(): NavItemInterface
    {
        return new Tab();
    }
}
