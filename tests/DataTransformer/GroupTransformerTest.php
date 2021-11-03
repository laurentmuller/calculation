<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\DataTransformer;

use App\Entity\Group;
use App\Form\DataTransformer\GroupTransformer;
use App\Repository\GroupRepository;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test for the GroupTransformer class.
 *
 * @author Laurent Muller
 */
class GroupTransformerTest extends KernelTestCase
{
    use DatabaseTrait;

    private ?Group $group;
    private ?GroupTransformer $transformer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->group = new Group();
        $this->group->setCode('Test');

        $manager = $this->getManager();
        $manager->persist($this->group);
        $manager->flush();

        $repository = $this->getGroupRepository();
        $translator = $this->getTranslator();
        $this->transformer = new GroupTransformer($repository, $translator);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        if (null !== $this->group) {
            $manager = $this->getManager();
            $manager->remove($this->group);
            $manager->flush();
            $this->group = null;
        }

        parent::tearDown();
    }

    public function testReverseTransform(): void
    {
        $result = $this->transformer->reverseTransform(1);
        $this->assertNotNull($result);
    }

    public function testTransform(): void
    {
        $result = $this->transformer->transform($this->group);
        $this->assertNotNull($result);
    }

    protected function getGroupRepository(): GroupRepository
    {
        /** @var GroupRepository $repository */
        $repository = static::getContainer()->get(GroupRepository::class);

        return $repository;
    }

    protected function getManager(): EntityManager
    {
        /** @var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        /** @var EntityManager $manager */
        $manager = $registry->getManager();

        return $manager;
    }

    protected function getTranslator(): TranslatorInterface
    {
        /** @var TranslatorInterface $translator */
        $translator = static::getContainer()->get(TranslatorInterface::class);

        return $translator;
    }
}
