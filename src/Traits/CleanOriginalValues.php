<?php
namespace CarloNicora\Minimalism\Services\MySQL\Traits;

trait CleanOriginalValues
{
    /**
     * @param array $items
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function cleanOriginalValues(
        array &$items
    ): void
    {
        foreach ($items as &$item) {
            if (isset($item['originalValues'])) {
                unset($item['originalValues']);
            }
        }
    }
}
