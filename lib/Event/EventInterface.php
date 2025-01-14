<?php

namespace Bendersay\Entityadmin\Event;

/**
 * Интерфейс события модуля
 *
 * @author bendersay
 */
interface EventInterface
{
    /**
     * Получить код модуля на который подписываемся
     *
     * @return string
     */
    public function getModule(): string;

    /**
     * Получить событие модуля на которое подписываемся
     *
     * @return string
     */
    public function getEventType(): string;

    /**
     * Получить класс, который обрабатывает событие
     *
     * @return string
     */
    public function getToClass(): string;

    /**
     * Получить имя метода класса, обработки события
     *
     * @return string
     */
    public function getToMethod(): string;

    /**
     * Получить сортировку события
     *
     * @return int
     */
    public function getSort(): int;
}
