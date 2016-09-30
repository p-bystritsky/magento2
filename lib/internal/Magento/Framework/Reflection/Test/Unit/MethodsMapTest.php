<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Reflection\Test\Unit;

use Magento\Framework\Json\JsonInterface;
use Magento\Framework\Reflection\MethodsMap;
use Magento\Framework\Reflection\TypeProcessor;

/**
 * MethodsMap test
 */
class MethodsMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MethodsMap
     */
    private $object;

    /** @var JsonInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $jsonMock;

    /**
     * Set up helper.
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $cacheMock = $this->getMockBuilder(\Magento\Framework\Cache\FrontendInterface::class)
            ->getMockForAbstractClass();
        $cacheMock->expects($this->any())
            ->method('save');
        $cacheMock->expects($this->any())
            ->method('load')
            ->will($this->returnValue(null));

        $attributeTypeResolverMock = $this->getMockBuilder(\Magento\Framework\Api\AttributeTypeResolverInterface::class)
            ->getMockForAbstractClass();
        $fieldNamerMock = $this->getMockBuilder(\Magento\Framework\Reflection\FieldNamer::class)
            ->getMockForAbstractClass();
        $this->object = $objectManager->getObject(
            \Magento\Framework\Reflection\MethodsMap::class,
            [
                'cache' => $cacheMock,
                'typeProcessor' => new TypeProcessor(),
                'typeResolver' => $attributeTypeResolverMock,
                'fieldNamer' => $fieldNamerMock,
            ]
        );
        $this->jsonMock = $this->getMock(JsonInterface::class);
        $objectManager->setBackwardCompatibleProperty(
            $this->object,
            'json',
            $this->jsonMock
        );
    }

    public function testGetMethodReturnType()
    {
        $this->assertEquals(
            'string',
            $this->object->getMethodReturnType(
                \Magento\Framework\Reflection\FieldNamer::class,
                'getFieldNameForMethodName'
            )
        );
        $this->assertEquals(
            'mixed',
            $this->object->getMethodReturnType(
                \Magento\Framework\Reflection\TypeCaster::class,
                'castValueToType'
            )
        );
        $this->assertEquals(
            'array',
            $this->object->getMethodReturnType(
                \Magento\Framework\Reflection\MethodsMap::class,
                'getMethodsMap'
            )
        );
    }

    public function testGetMethodsMap()
    {
        $this->jsonMock->expects($this->once())
            ->method('encode')
            ->willReturnCallback(function ($data) {
                return json_encode($data);
            });
        $methodsMap = $this->object->getMethodsMap(\Magento\Framework\Reflection\MethodsMap::class);
        $this->assertEquals(
            [
                'getMethodReturnType' => [
                    'type' => 'string',
                    'isRequired' => true,
                    'description' => null,
                    'parameterCount' => 2,
                ],
                'getMethodsMap' => [
                    'type' => 'array',
                    'isRequired' => true,
                    'description' => "<pre> Service methods' reflection data stored in cache as 'methodName' => "
                        . "'returnType' ex. [ 'create' => '\Magento\Customer\Api\Data\Customer', 'validatePassword' "
                        . "=> 'boolean' ] </pre>",
                    'parameterCount' => 1,
                ],
                'getMethodParams' => [
                    'type' => 'array',
                    'isRequired' => true,
                    'description' => null,
                    'parameterCount' => 2
                ],
                'isMethodValidForDataField' => [
                    'type' => 'bool',
                    'isRequired' => true,
                    'description' => null,
                    'parameterCount' => 2,
                ],
                'isMethodReturnValueRequired' => [
                    'type' => 'bool',
                    'isRequired' => true,
                    'description' => null,
                    'parameterCount' => 2,
                ],
            ],
            $methodsMap
        );
    }

    /**
     * @param string $type
     * @param string $methodName
     * @param bool $expectedResult
     * @dataProvider isMethodValidForDataFieldProvider
     */
    public function testIsMethodValidForDataField($type, $methodName, $expectedResult)
    {
        $this->assertEquals($this->object->isMethodValidForDataField($type, $methodName), $expectedResult);
    }

    /**
     * @return array
     */
    public function isMethodValidForDataFieldProvider()
    {
        return [
            'MethodsMap#isMethodValidForDataField' => [\Magento\Framework\Reflection\MethodsMap::class,
                'isMethodValidForDataField',
                false,
            ],
            'DataObject#getAttrName' => [\Magento\Framework\Reflection\Test\Unit\DataObject::class,
                'getAttrName',
                true,
            ],
            'DataObject#isActive' => [\Magento\Framework\Reflection\Test\Unit\DataObject::class,
                'isActive',
                true,
            ],
        ];
    }

    /**
     * @param string $type
     * @param string $methodName
     * @param bool $expectedResult
     * @dataProvider isMethodReturnValueRequiredProvider
     */
    public function testIsMethodReturnValueRequired($type, $methodName, $expectedResult)
    {
        $this->assertEquals($this->object->isMethodValidForDataField($type, $methodName), $expectedResult);
    }

    /**
     * @return array
     */
    public function isMethodReturnValueRequiredProvider()
    {
        return [
            'DataObject#getAttrName' => [\Magento\Framework\Reflection\Test\Unit\DataObject::class,
                'getAttrName',
                true,
            ],
            'DataObject#isActive' => [\Magento\Framework\Reflection\Test\Unit\DataObject::class,
                'isActive',
                true,
            ],
            'FieldNamer#getFieldNameForMethodName' => [\Magento\Framework\Reflection\FieldNamer::class,
                'getFieldNameForMethodName',
                false,
            ],
        ];
    }
}
