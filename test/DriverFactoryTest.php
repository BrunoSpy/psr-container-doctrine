<?php
declare(strict_types=1);

namespace RoaveTest\PsrContainerDoctrine;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Mapping\Driver;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Container\ContainerInterface;
use Roave\PsrContainerDoctrine\DriverFactory;

class DriverFactoryTest extends TestCase
{
    public function testMissingClassKeyWillReturnOutOfBoundException()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $factory = new DriverFactory();

        $this->setExpectedException(OutOfBoundsException::class, 'Missing "class" config key');

        $factory($container->reveal());
    }

    public function testItSupportsGlobalBasenameOptionOnFileDrivers()
    {
        $globalBasename = 'foobar';

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'doctrine' => [
                'driver' => [
                    'orm_default' => [
                        'class' => TestAsset\StubFileDriver::class,
                        'global_basename' => $globalBasename
                    ],
                ],
            ],
        ]);

        $factory = new DriverFactory();

        $driver = $factory($container->reveal());
        $this->assertSame($globalBasename, $driver->getGlobalBasename());
    }

    /**
     * @param string $driverClass
     *
     * @dataProvider simplifiedDriverClassProvider
     */
    public function testItSupportsSettingExtensionInDriversUsingSymfonyFileLocator($driverClass)
    {
        $extension = '.foo.bar';

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'doctrine' => [
                'driver' => [
                    'orm_default' => [
                        'class' => $driverClass,
                        'extension' => $extension,
                    ],
                ],
            ],
        ]);

        $factory = new DriverFactory();

        /** @var Driver\SimplifiedXmlDriver $driver */
        $driver = $factory($container->reveal());
        $this->assertInstanceOf($driverClass, $driver);
        $this->assertSame($extension, $driver->getLocator()->getFileExtension());
    }

    public function simplifiedDriverClassProvider()
    {
        return [
            [ Driver\SimplifiedXmlDriver::class ],
            [ Driver\SimplifiedYamlDriver::class ],
        ];
    }

    public function testItSupportsSettingDefaultDriverUsingMappingDriverChain()
    {

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'doctrine' => [
                'driver' => [
                    'orm_default' => [
                        'class' => MappingDriverChain::class,
                        'default_driver' => 'orm_stub'
                    ],
                    'orm_stub' => [
                        'class' => TestAsset\StubFileDriver::class,
                    ],
                ],
            ],
        ]);

        $factory = new DriverFactory();

        $driver = $factory($container->reveal());
        $this->assertInstanceOf(TestAsset\StubFileDriver::class, $driver->getDefaultDriver());
    }
}
