<?php
namespace CarloNicora\Minimalism\Services\MySQL\Interfaces;

interface FieldTypeInterface
{
    public const Integer=0b1;
    public const Double=0b10;
    public const String=0b100;
    public const Blob=0b1000;
    public const PrimaryKey=0b10000;
    public const AutoIncrement=0b110001;
    public const TimeCreate=0b1000100;
    public const TimeUpdate=0b10000100;
}