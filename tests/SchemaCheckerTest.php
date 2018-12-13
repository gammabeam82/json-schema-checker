<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Tests;

use Gammabeam82\SchemaChecker\SchemaChecker;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Fixtures;

class SchemaCheckerTest extends TestCase
{
    /**
     * @var SchemaChecker
     */
    protected static $checker;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$checker = new SchemaChecker();
    }

    /**
     * @expectedException  \Gammabeam82\SchemaChecker\Exception\InvalidSchemaException
     */
    public function testInvalidSchema(): void
    {
        $this->assertFalse(
            static::$checker->assertSchema(FIXTURES::PRODUCT, []),
            static::$checker->getViolations()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidData(): void
    {
        $this->assertFalse(
            static::$checker->assertSchema(1, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
    }

    public function testSuccessfullValidation(): void
    {
        $this->assertTrue(
            static::$checker->assertSchema(FIXTURES::PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertSchema(FIXTURES::CATEGORY, FIXTURES::CATEGORY_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertSchema(FIXTURES::CATEGORY_WITH_PRODUCTS, FIXTURES::CATEGORY_WITH_PRODUCTS_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertSchema(FIXTURES::USER, FIXTURES::USER_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertTrue(
            static::$checker->assertSchema(FIXTURES::USER_WITHOUT_ROLES, FIXTURES::USER_SCHEMA),
            static::$checker->getViolations()
        );
    }

    public function testValidationWithErrors(): void
    {
        $this->assertFalse(
            static::$checker->assertSchema(FIXTURES::MISSING_KEY_PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertFalse(
            static::$checker->assertSchema(FIXTURES::INVALID_TYPE_PRODUCT, FIXTURES::PRODUCT_SCHEMA),
            static::$checker->getViolations()
        );
        $this->assertFalse(
            static::$checker->assertSchema(FIXTURES::CATEGORY, FIXTURES::CATEGORY_WITH_PRODUCTS_SCHEMA),
            static::$checker->getViolations()
        );
    }
}
