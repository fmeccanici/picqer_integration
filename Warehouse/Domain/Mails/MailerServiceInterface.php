<?php


namespace App\Warehouse\Domain\Mails;


interface MailerServiceInterface
{
    public function send(Mail $mail): bool;
}
