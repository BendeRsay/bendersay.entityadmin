<?php

namespace Bendersay\Entityadmin\Install;

trait ParseTrait
{
    /**
     * Узнаем имя класса
     *
     * @param array $tokens
     *
     * @return string
     */
    private function parseTokens(array $tokens): string
    {
        $classStart = false;

        foreach ($tokens as $token) {
            if ($token[0] === T_CLASS) {
                $classStart = true;
            }
            if ($classStart && $token[0] === T_STRING) {
                return $token[1];
            }
        }

        return '';
    }
}
