<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface FieldInterface
{
    /** @var int  */
    public const INTEGER=0b1;
    public const DOUBLE=0b10;
    public const STRING=0b100;
    public const BLOB=0b1000;
    public const PRIMARY_KEY=0b10000;
    public const AUTO_INCREMENT=0b100000;
    public const TIME_CREATE=0b1000000;
    public const TIME_UPDATE=0b10000000;

    /** @var string  */
    public const INSERT_IGNORE = ' IGNORE';
}