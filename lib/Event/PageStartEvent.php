<?php

namespace Bendersay\Entityadmin\Event;

/**
 * Событие OnPageStart модуля main
 *
 * @author bendersay
 */
class PageStartEvent extends AbstractEvent
{
    public function getModule(): string
    {
        return 'main';
    }

    public function getEventType(): string
    {
        return 'OnPageStart';
    }

    public function getToClass(): string
    {
        return '';
    }

    public function getToMethod(): string
    {
        return '';
    }
}
