<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

enum FieldOption: int
{
    case PrimaryKey=0b10000;
    case AutoIncrement=0b110000;
    case TimeCreate=0b1000000;
    case TimeUpdate=0b10000000;
}