<?php


namespace Tests\Unit\Warehouse;

use App\Warehouse\Domain\Mails\Mail;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use Illuminate\Support\Collection;

class DummyMailerService implements MailerServiceInterface
{
    private array $sendMails;

    public function __construct()
    {
        $this->sendMails = [];
    }

    public function send(Mail $mail): bool
    {
        $this->sendMails[] = $mail;
        return true;
    }

    public function sentMails(): Collection
    {
        return collect($this->sendMails);
    }

    public function isAlreadySent(Mail $mail): bool
    {
        return true;
    }
}
