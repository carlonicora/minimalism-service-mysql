<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface SQLQueryCreationFacadeInterface
{
    /**
     * SQLQueryGeneratorInterface constructor.
     * @param MySqlTableInterface $table
     */
    public function __construct(
        MySqlTableInterface $table
    );

    /**
     * @return string
     */
    public function SELECT() : string;

    /**
     * @return string
     */
    public function INSERT(): string;

    /**
     * @return string
     */
    public function UPDATE(): string;

    /**
     * @return string
     */
    public function COUNT(): string;

    /**
     * @return string
     */
    public function DELETE(): string;

    /**
     * @return string
     */
    public function generateSelectStatement(): string;

    /**
     * @param int|string $fieldType
     * @return string
     */
    public function convertFieldType(int|string $fieldType): string;

    /**
     * @return array
     */
    public function generateSelectParameters(): array;

    /**
     * @return bool
     */
    public function canUseInsertOnDuplicate(): bool;

    /**
     * @return string
     */
    public function generateInsertOnDuplicateUpdateStart(): string;

    /**
     * @param array $record
     * @return string
     */
    public function generateInsertOnDuplicateUpdateRecord(array $record): string;

    /**
     * @return string
     */
    public function generateInsertOnDuplicateUpdateEnd(): string;

    /**
     * @return string
     */
    public function generateInsertStatement(): string;

    /**
     * @return array
     */
    public function generateInsertParameters(): array;

    /**
     * @return string
     */
    public function generateDeleteStatement(): string;

    /**
     * @return array
     */
    public function generateDeleteParameters(): array;

    /**
     * @return string
     */
    public function generateUpdateStatement(): string;

    /**
     * @return array
     */
    public function generateUpdateParameters(): array;
}