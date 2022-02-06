<?php

namespace Max\Mailer\Contracts;

interface MailInterface
{
    public function getFrom(): string;

    public function getTo(): string;
}
