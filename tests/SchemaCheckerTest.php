<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Tests;

use Gammabeam82\SchemaChecker\SchemaChecker;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Fixtures\Fixtures;

class SchemaCheckerTest extends TestCase
{
    /**
     * @var SchemaChecker
     */
    protected static $checker;

    /**
     * @param string $methodName
     * @param array $params
     *
     * @return mixed
     * @throws \ReflectionException
     */
    private function invokePrivateMethod(string $methodName, array $params = [])
    {
        $reflection = new ReflectionClass(static::$checker);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(static::$checker, $params);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$checker = new SchemaChecker();
    }

    public function testIsIndexed(): void
    {
        $this->assertTrue($this->invokePrivateMethod('isIndexed', [['foo', 'bar']]));
        $this->assertFalse($this->invokePrivateMethod('isIndexed', [['foo' => 'bar']]));
    }

    public function testIsPlain(): void
    {
        $this->assertTrue($this->invokePrivateMethod('isPlain', ['test', ['string']]));
        $this->assertFalse($this->invokePrivateMethod('isPlain', ['test', ['foo' => 'string']]));
    }

    public function testIsNullable(): void
    {
        $this->assertTrue($this->invokePrivateMethod('isNullable', [['string|nullable']]));
        $this->assertTrue($this->invokePrivateMethod('isNullable', [['nullable' => true]]));
        $this->assertFalse($this->invokePrivateMethod('isNullable', [['nullable' => false]]));
    }

    public function testContainsNullableType(): void
    {
        $this->assertTrue($this->invokePrivateMethod('containsNullableType', ['nullable|string']));
        $this->assertFalse($this->invokePrivateMethod('containsNullableType', ['string']));
    }

    public function testIsSchemaValid(): void
    {
        $this->assertTrue($this->invokePrivateMethod('isSchemaValid', ['string|nullable']));
        $this->assertTrue($this->invokePrivateMethod('isSchemaValid', ['*']));
        $this->assertFalse($this->invokePrivateMethod('isSchemaValid', ['string|nullable|']));
        $this->assertFalse($this->invokePrivateMethod('isSchemaValid', ['1']));
    }

    public function testIsIncludes(): void
    {
        $this->assertTrue($this->invokePrivateMethod('isIncludes', ['string', 'string']));
        $this->assertTrue($this->invokePrivateMethod('isIncludes', ['integer', ['string', 'integer']]));
        $this->assertTrue($this->invokePrivateMethod('isIncludes', ['string', '*']));
        $this->assertFalse($this->invokePrivateMethod('isIncludes', ['string', 'integer']));
    }

    public function testValidateKey(): void
    {
        $this->assertTrue($this->invokePrivateMethod('validateKey', ['key', ['key' => 1]]));
        $this->assertFalse($this->invokePrivateMethod('validateKey', [['key'], ['key' => 1]]));
        $this->assertFalse($this->invokePrivateMethod('validateKey', ['key', ['test' => 1]]));
    }

    public function testValidateKeyType(): void
    {
        $this->assertTrue($this->invokePrivateMethod('validateKeyType', ['test', 'string', 'string']));
        $this->assertTrue($this->invokePrivateMethod('validateKeyType', ['test', 'nullable', 'string|nullable']));
        $this->assertTrue($this->invokePrivateMethod('validateKeyType', ['test', 'string', '*']));
        $this->assertFalse($this->invokePrivateMethod('validateKeyType', ['test', 'boolean', 'string']));
    }

    public function testGetViolations(): void
    {
        $this->assertIsString(static::$checker->getViolations());
    }

    /**
     * @expectedException  \Gammabeam82\SchemaChecker\Exception\InvalidSchemaException
     */
    public function testInvalidSchema(): void
    {
        $this->assertFalse(
            static::$checker->assertDataMatchesSchema(FIXTURES::PRODUCT, []),
            static::$checker->getViolations()
        );
    }

    /**
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidData(): void
    {
        $this->assertFalse(
            static::$checker->assertDataMatchesSchema(1, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
    }

    public function testSuccessfullValidation(): void
    {
        $this->assertTrue(
            static::$checker->assertDataMatchesSchema(FIXTURES::PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertDataMatchesSchema(FIXTURES::CATEGORY, FIXTURES::CATEGORY_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertDataMatchesSchema(FIXTURES::CATEGORY_WITH_PRODUCTS, FIXTURES::CATEGORY_WITH_PRODUCTS_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertDataMatchesSchema(FIXTURES::USER, FIXTURES::USER_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertDataMatchesSchema(FIXTURES::USER_WITHOUT_ROLES, FIXTURES::USER_SCHEMA),
            static::$checker->getViolations()
        );
    }

    public function testValidationWithErrors(): void
    {
        $this->assertFalse(
            static::$checker->assertDataMatchesSchema(FIXTURES::MISSING_KEY_PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertFalse(
            static::$checker->assertDataMatchesSchema(FIXTURES::INVALID_TYPE_PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertFalse(
            static::$checker->assertDataMatchesSchema(FIXTURES::CATEGORY, FIXTURES::CATEGORY_WITH_PRODUCTS_SCHEMA),
            static::$checker->getViolations()
        );
    }
}
