<?php
namespace CarloNicora\Minimalism\Services\MySQL\Enums;

enum SqlOptions
{
    case DisableForeignKeyCheck;

    /**
     * @return string
     */
    public function on(
    ): string
    {
        return match($this){
            self::DisableForeignKeyCheck => 'SET foreign_key_checks=0;',
        };
    }

    /**
     * @return string
     */
    public function off(
    ): string
    {
        return match($this){
            self::DisableForeignKeyCheck => 'SET foreign_key_checks=1;',
        };
    }
}