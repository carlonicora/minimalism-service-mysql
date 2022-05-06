<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

enum SqlFieldType
{
    //string
    //int
    //bool
    case bigint;
    case blob;
    case char;
    case date;
    case datetime;
    case decimal;
    case double;
    case enum;
    case float;
    case int;
    case json;
    case linestring;
    case longblob;
    case mediumblob;
    case mediumint;
    case mediumtext;
    case multilinestring;
    case smallint;
    case text;
    case time;
    case timestamp;
    case tinyblob;
    case tinyint;
    case tinytext;
    case varchar;
    case year;

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
            self::float => 'float',
            self::double, self::decimal => 'double',
            self::char, self::date, self::datetime, self::enum, self::json, self::linestring, self::mediumtext,
                self::multilinestring, self::text, self::time, self::timestamp, self::tinytext, self::varchar,
                self::year, self::blob, self::longblob, self::mediumblob, self::tinyblob => 'string',
            self::bigint, self::int, self::mediumint, self::smallint, self::tinyint => 'int',
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
            self::char, self::date, self::datetime, self::enum, self::json, self::linestring,
                self::mediumtext, self::multilinestring, self::text, self::time, self::timestamp,
                self::tinytext, self::varchar, self::year => FieldType::String,
            self::bigint, self::int, self::mediumint, self::smallint, self::tinyint => FieldType::Integer,
            self::blob, self::longblob, self::mediumblob, self::tinyblob => FieldType::Blob,
        };
    }

    /**
     * FULL LIST
    case binary;
    case bit;
    case blob;
    case char;
    case date;
    case datetime;
    case decimal;
    case double;
    case enum;
    case float;
    case geometry;
    case geomcollection;
    case int;
    case json;
    case linestring;
    case longblob;
    case mediumblob;
    case mediumint;
    case mediumtext;
    case bigint;
    case multilinestring;
    case multipoint;
    case multipolygon;
    case point;
    case polygon;
    case smallint;
    case text;
    case time;
    case timestamp;
    case tinyblob;
    case tinyint;
    case tinytext;
    case varchar;
    case year;
     */
}