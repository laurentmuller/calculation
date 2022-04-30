<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
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
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Test for the {@link GroupTransformer} class.
 */
class GroupTransformerTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private ?Group $group = null;
    private ?GroupTransformer $transformer = null;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroup();
        $repository = $this->getService(GroupRepository::class);
        $translator = $this->getService(TranslatorInterface::class);
        $this->transformer = new GroupTransformer($repository, $translator);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $this->group = $this->deleteGroup();
        $this->transformer = null;
        parent::tearDown();
    }

    public function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
    }

    public function getTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
    }

    public function testGroupNotNull(): void
    {
        $this->assertNotNull($this->group);
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider getReverseTransformValues
     */
    public function testReverseTransform($value, $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $actual = $this->transformer->reverseTransform($value);
        $this->assertEquals($expected, $actual);
    }

    public function testReverseTransformInvalid(): void
    {
        $this->expectException(TransformationFailedException::class);
        $actual = $this->transformer->reverseTransform(-1);
        $this->assertEquals($this->group, $actual);
    }

    public function testReverseTransformValid(): void
    {
        $actual = $this->transformer->reverseTransform($this->group->getId());
        $this->assertEquals($this->group, $actual);
    }

    /**
     * @param mixed $value
     * @param mixed $expected
     * @dataProvider getTransformValues
     */
    public function testTransform($value, $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $actual = $this->transformer->transform($value);
        $this->assertEquals($expected, $actual);
    }

    public function testTransformerNotNull(): void
    {
        $this->assertNotNull($this->transformer);
    }

    public function testTransformValid(): void
    {
        $actual = $this->transformer->transform($this->group);
        $this->assertEquals($this->group->getId(), $actual);
    }

    protected function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('Test');

        $manager = $this->getManager();
        $manager->persist($group);
        $manager->flush();

        return $group;
    }

    protected function deleteGroup(): ?Group
    {
        if (null !== $this->group) {
            $manager = $this->getManager();
            $manager->remove($this->group);
            $manager->flush();
            $this->group = null;
        }

        return $this->group;
    }
}
