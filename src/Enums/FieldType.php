<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

enum FieldType: int
{
    case Integer=0b1;
    case Double=0b10;
    case String=0b100;
    case Blob=0b1000;
}