<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface SQLQueryCreationFacadeInterface
{
    /**
     * SQLQueryGeneratorInterface constructor.
     * @param TableInterface $table
     */
    public function __construct(TableInterface $table);

    /**
     * @return string
     */
    public function generateSelectStatementInitial() : string;

    /**
     * @return string
     */
    public function generateInsertStatementInitial(): string;

    /**
     * @return string
     */
    public function generateUpdateStatementInitial(): string;

    /**
     * @return string
     */
    public function generateDeleteStatementInitial(): string;

    /**
     * @return string
     */
    public function generateSelectStatement(): string;

    /**
     * @param int|string $fieldType
     * @return string
     */
    public function convertFieldType($fieldType): string;

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