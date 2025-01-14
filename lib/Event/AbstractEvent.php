<?php

namespace Bendersay\Entityadmin\Event;

/**
 * Базовый, абстрактный класс события
 *
 * @author bendersay
 */
abstract class AbstractEvent implements EventInterface
{
    /**
     * Получить код модуля на который подписываемся
     *
     * @return string
     */
    abstract public function getModule(): string;

    /**
     * Получить событие модуля на которое подписываемся
     *
     * @return string
     */
    abstract public function getEventType(): string;

    /**
     * Получить имя метода класса, обработки события
     *
     * @return string
     */
    abstract public function getToMethod(): string;

    /**
     * Получить класс, который обрабатывает событие
     *
     * @return string
     */
    public function getToClass(): string
    {
        return static::class;
    }

    /**
     * Сортировка события
     *
     * @return integer
     */
    public function getSort(): int
    {
        return 100;
    }
}
