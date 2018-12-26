<?php

declare(strict_types=1);

namespace Gammabeam82\SchemaChecker;

use Gammabeam82\SchemaChecker\Exception\InvalidSchemaException;
use InvalidArgumentException;
use LogicException;

/**
 * Class SchemaChecker
 * @package Gammabeam82\SchemaChecker
 */
class SchemaChecker
{
    /**
     * @var string[]
     */
    private $violations;

    /**
     * @var string
     */
    private $currentKey;

    /**
     * SchemaChecker constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @param array|string $data
     * @param array $schema
     *
     * @return bool
     * @throws InvalidSchemaException
     */
    public function assertDataMatchesSchema($data, array $schema): bool
    {
        $this->reset();

        if (false === is_array($data) && false === is_string($data)) {
            throw new InvalidArgumentException('Invalid data format.');
        }

        if (false === is_array($data)) {
            $data = json_decode($data, true);
        }

        if (null === $data) {
            $this->addInvalidDataViolation(json_last_error_msg());

            return false;
        }

        return $this->check($data, $schema);
    }

    /**
     * @return string
     */
    public function getViolations(): string
    {
        return 0 !== count($this->violations) ? implode(",\n", $this->violations) : '';
    }

    /**
     * @param array $data
     * @param array $schema
     *
     * @return bool
     * @throws InvalidSchemaException
     */
    private function check(array $data, array $schema): bool
    {
        if (0 === count($schema)) {
            throw new InvalidSchemaException('Schema cannot be empty.');
        }

        if (0 === count($data)) {
            return $this->isNullable($schema);
        }

        if (false !== $this->isIndexed($data)) {
            $data = reset($data);
        }

        if (false !== $this->isPlain($data, $schema)) {
            if (null === $this->currentKey) {
                throw new LogicException();
            }

            return $this->validateKeyType($this->currentKey, $this->getDataItemType($data), reset($schema));
        }

        if (false === is_array($data)) {
            $this->addInvalidDataViolation('Data is expected to be of type array.');

            return false;
        }

        foreach ($schema as $key => $expectedType) {
            if (false === $this->validateKey($key, $data)) {
                continue;
            }

            $item = $data[$key];
            $type = $this->getDataItemType($item);

            if (false !== is_array($expectedType)) {
                if (false === is_array($item)) {
                    $this->addInvalidTypeViolation($key, $type, 'array');
                    continue;
                }
                $this->currentKey = $key;
                $this->check($item, $expectedType);
                continue;
            }

            $this->validateKeyType($key, $type, $expectedType);
        }

        return 0 === count($this->violations);
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    private function isIndexed(array $array): bool
    {
        return $array === array_values($array);
    }

    /**
     * @param string $type
     * @param string|array $expected
     *
     * @return bool
     */
    private function isIncludes(string $type, $expected): bool
    {
        if (false !== is_array($expected)) {
            return 0 !== count(array_intersect([$type, Types::WILDCARD], $expected));
        }

        return $type === $expected || Types::WILDCARD === $expected;
    }

    /**
     * @param mixed $key
     * @param array $data
     *
     * @return bool
     */
    private function validateKey($key, array $data): bool
    {
        if ('string' !== gettype($key) || Types::NULLABLE === $key) {
            return false;
        }

        $keyExists = array_key_exists($key, $data);

        if (false === $keyExists) {
            $this->addMissingKeyViolation($key);
        }

        return $keyExists;
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $expectedType
     *
     * @return bool
     * @throws InvalidSchemaException
     */
    private function validateKeyType(string $key, string $type, string $expectedType): bool
    {
        if (false === $this->isSchemaValid($expectedType)) {
            throw new InvalidSchemaException(sprintf('Schema contains invalid characters at key: %s', $key));
        }

        if (false !== mb_strpos($expectedType, Types::DELIMITER)) {
            $match = $this->isIncludes($type, explode(Types::DELIMITER, $expectedType));
        } else {
            $match = $this->isIncludes($type, $expectedType);
        }

        if (false === $match) {
            $this->addInvalidTypeViolation($key, $type, $expectedType);
        }

        return $match;
    }

    /**
     * @param string $schema
     *
     * @return bool
     */
    private function isSchemaValid(string $schema): bool
    {
        return (bool)preg_match("/^([a-z]{4,}|\*)(\|[a-z]{4,}|\|\*)*$/", $schema);
    }

    /**
     * @param mixed $data
     * @param array $schema
     *
     * @return bool
     */
    private function isPlain($data, array $schema): bool
    {
        return false === is_array($data) && false !== $this->isIndexed($schema);
    }

    /**
     * @param array $schema
     *
     * @return bool
     */
    private function isNullable(array $schema): bool
    {
        if (false !== $this->isIndexed($schema)) {
            $nullable = $this->containsNullableType(reset($schema));
        } else {
            $nullable = array_key_exists(Types::NULLABLE, $schema) && true === $schema[Types::NULLABLE];
        }

        if (false === $nullable) {
            $this->addInvalidDataViolation('Null value not allowed.');
        }

        return $nullable;
    }

    /**
     * @param string $schemaItem
     *
     * @return bool
     */
    private function containsNullableType(string $schemaItem): bool
    {
        return false !== mb_strpos($schemaItem, Types::NULLABLE);
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    private function getDataItemType($data): string
    {
        $type = gettype($data);

        return 'NULL' === $type ? Types::NULLABLE : $type;
    }

    /**
     * @param string $key
     */
    private function addMissingKeyViolation(string $key): void
    {
        $this->violations[] = sprintf("Key \"%s\" not found", $key);
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $expected
     */
    private function addInvalidTypeViolation(string $key, string $type, string $expected): void
    {
        if (Types::NULLABLE === $type) {
            $type = 'null';
        }

        $this->violations[] = sprintf(
            'Unexpected type of key: "%1$s". Expected: "%3$s", got: "%2$s"',
            $key,
            $type,
            $expected
        );
    }

    /**
     * @param string|null $message
     */
    private function addInvalidDataViolation(?string $message = null): void
    {
        $message = $message ?? 'Invalid data';
        $this->violations[] = $message;
    }

    private function reset(): void
    {
        $this->violations = [];
        $this->currentKey = null;
    }
}
