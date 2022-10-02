<?php


namespace App\Warehouse\Application\HandleShipmentCreated;

use PASVL\Validation\ValidatorBuilder;

final class HandleShipmentCreatedInput
{
    protected array $shipment;
    protected bool $sendTrackAndTraceMail;
    protected bool $sendReviewRequest;
    protected string $agent;
    protected string $packedByName;

    public function __construct(array $input)
    {
        $this->validate($input);
        $this->shipment = $input["shipment"];
        $this->sendTrackAndTraceMail = $input['send_track_and_trace_mail'];
        $this->sendReviewRequest = $input['send_review_request'];
        $this->agent = $input['agent'];
        $this->packedByName = $input['packed_by_name'];
    }

    private function validate($order)
    {
        $pattern = [
            'send_track_and_trace_mail' => ':bool',
            'send_review_request' => ':bool',
            'track_and_trace_url' => ':string?',
            'agent' => ':string',
            'packed_by_name' => ':string',
            "shipment" => [
                "reference" => ":string",
                "order_reference" => ":string",
                "picklist_id" => ":string?",
                "ordered_items" => [
                    "*" => [
                        "product_id" => ":string",
                        "product_amount" => ":number :int",
                        "product_group" => ":string?",
                        "shipping_date_estimation" => ":array?",
                        "picklist_id" => ":string?"
                    ]
                ],
                "track_and_trace" => ':any',
                "delivery_date?" => ":string",
                "shipping_explanation" => ":string?",
                "delivery_method" => ":string",
                "carrier_name" => ":string",
                "track_and_trace_mail_sent" => ':bool'
            ]
        ];

        $validator = ValidatorBuilder::forArray($pattern)->build();
//        $validator->validate($order);
    }

    public function shipment(): array
    {
        return $this->shipment;
    }

    public function sendTrackAndTraceMail()
    {
        return $this->sendTrackAndTraceMail;
    }

    public function sendReviewRequest()
    {
        return $this->sendReviewRequest;
    }

    public function agent()
    {
        return $this->agent;
    }

    public function packedByName(): string
    {
        return $this->packedByName;
    }
}
