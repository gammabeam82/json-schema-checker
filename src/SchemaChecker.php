<?php

declare(strict_types = 1);

namespace Gammabeam82\SchemaChecker;

use Gammabeam82\SchemaChecker\Exception\InvalidSchemaException;
use InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public function assertDataMatchesSchema($data, array $schema): bool
    {
        $this->reset();

        if (false === is_array($data) && false === is_string($data)) {
            throw new InvalidArgumentException();
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
     * @param array $data
     * @param array $schema
     *
     * @return bool
     * @throws InvalidSchemaException
     */
    private function check(array $data, array $schema): bool
    {
        if (0 === count($schema)) {
            throw new InvalidSchemaException();
        }

        if (0 === count($data)) {
            return $this->isNullable($schema);
        }

        if (false !== $this->isIndexed($data)) {
            $data = reset($data);
        }

        if (false !== $this->isPlain($data, $schema)) {
            return $this->validateKey($this->currentKey, $this->getDataItemType($data), reset($schema));
        }

        foreach ($schema as $key => $expectedType) {
            if ('string' !== gettype($key) || Types::NULLABLE === $key) {
                continue;
            }

            if (false === array_key_exists($key, $data)) {
                $this->addMissingKeyViolation($key);
                continue;
            }

            if (false !== is_array($expectedType)) {
                $item = $data[$key];
                if (false === is_array($item)) {
                    $this->addInvalidTypeViolation($key, gettype($item), 'array');
                    continue;
                }
                $this->currentKey = $key;
                $this->check($item, $expectedType);
                continue;
            }

            $this->validateKey($key, $this->getDataItemType($data[$key]), $expectedType);
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
     * @param string $key
     * @param string $type
     * @param string $expectedType
     *
     * @return bool
     * @throws InvalidSchemaException
     */
    private function validateKey(string $key, string $type, string $expectedType): bool
    {
        if (false === $this->isSchemaValid($expectedType)) {
            throw new InvalidSchemaException();
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
     * @param string|array $schema
     *
     * @return bool
     */
    private function isNullable($schema): bool
    {
        if (false !== is_array($schema)) {
            $nullable = $this->isIndexed($schema)
                ? false !== mb_strpos(reset($schema), Types::NULLABLE)
                : array_key_exists(Types::NULLABLE, $schema) && true === $schema[Types::NULLABLE];
        } else {
            $nullable = (false !== mb_strpos($schema, Types::NULLABLE));
        }

        if (false === $nullable) {
            $this->addInvalidDataViolation();
        }

        return $nullable;
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
     * @return string
     */
    public function getViolations(): string
    {
        return 0 !== count($this->violations) ? implode(",\n", $this->violations) : '';
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $expected
     */
    private function addInvalidTypeViolation(string $key, string $type, string $expected): void
    {
        $this->violations[] = sprintf(
            'Unexpected type of key: "%1$s". Expected: "%3$s", got: "%2$s"',
            $key,
            $type,
            $expected
        );
    }

    /**
     * @param string $key
     */
    private function addMissingKeyViolation(string $key): void
    {
        $this->violations[] = sprintf("Key \"%s\" not found", $key);
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
