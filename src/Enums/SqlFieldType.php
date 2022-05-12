<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

enum SqlFieldType: string
{
    case bigint = 'bigint';
    case blob = 'blob';
    case char = 'char';
    case date = 'date';
    case datetime = 'datetime';
    case decimal = 'decimal';
    case double = 'double';
    case enum = 'enum';
    case float = 'float';
    case int = 'int';
    case json = 'json';
    case linestring = 'linestring';
    case longblob = 'longblob';
    case longtext = 'longtext';
    case mediumblob = 'mediumblob';
    case mediumint = 'mediumint';
    case mediumtext = 'mediumtext';
    case multilinestring = 'multilinestring';
    case smallint = 'smallint';
    case text = 'text';
    case time = 'time';
    case timestamp = 'timestamp';
    case tinyblob = 'tinyblob';
    case tinyint = 'tinyint';
    case tinytext = 'tinytext';
    case varchar = 'varchar';
    case year = 'year';

    case binary = 'binary';
    case bit = 'bit';
    case geometry = 'geometry';
    case geomcollection = 'geomcollection';
    case multipoint = 'multipoint';
    case multipolygon = 'multipolygon';
    case point = 'point';
    case polygon = 'polygon';

    /**
     * @param int|null $lenght
     * @return string
     */
    public function getPhpType(
        ?int $lenght=null,
    ): string
    {
        if ($lenght !== null && $this === self::tinyint){
            return 'bool';
        }

        return match($this){
            self::double, self::decimal, self::float => 'float',
            self::bigint, self::int, self::mediumint, self::smallint, self::tinyint => 'int',
            default => 'string',
        };
    }

    /**
     * @return FieldType
     */
    public function getFieldType(
    ): FieldType
    {
        return match($this){
            self::float, self::double, self::decimal => FieldType::Double,
            self::bigint, self::int, self::mediumint, self::smallint, self::tinyint => FieldType::Integer,
            self::blob, self::longblob, self::mediumblob, self::tinyblob => FieldType::Blob,
            default => FieldType::String,
        };
    }
}