<?php


namespace App\Warehouse\Infrastructure\Mails;

use App\Http\Controllers\Api\FlowMailerController;
use App\Http\Requests\Api\FlowMailer\FlowMailerSendRequest;
use App\Warehouse\Domain\Exceptions\EmailProcessException;
use App\Warehouse\Domain\Exceptions\FlowNameMissingException;
use App\Warehouse\Domain\Mails\Mail;
use App\Warehouse\Domain\Mails\MailerServiceInterface;

class FlowMailerMailerService implements MailerServiceInterface
{
    /**
     * @throws FlowNameMissingException
     * @throws EmailProcessException
     */
    public function send(Mail $mail): bool
    {
        // TODO: Make this implementation clean, without FlowMailerController
        // Task 19101: Maak een cleane implementatie van de FlowMailerMailerService, zonder FlowMailerController
        if($flow = $mail->flow()) {
            $request = new FlowMailerSendRequest();
            $request->setMethod('POST');

            $request->request->add([
                'to' => $mail->recipient()->email(),
                'name' => $mail->recipient()->name(),
                'subject' => $mail->subject(),
                'data' => $mail->data(),
                'template' => $flow
            ]);

            $flowMailerController = new FlowMailerController();

            $emailSent = $flowMailerController->store($request)['sent'];
        } else {
            throw new FlowNameMissingException("Mail Class doesn't contain a FLOW_NAME");
        }

        if(!$emailSent) {
            throw new EmailProcessException("Mail could not be processed");
        }

        return $emailSent;
    }

}
