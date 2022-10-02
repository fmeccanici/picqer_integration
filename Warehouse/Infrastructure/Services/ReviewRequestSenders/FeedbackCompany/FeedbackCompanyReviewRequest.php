<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class FeedbackCompanyReviewRequest implements Arrayable
{
    protected string $externalId;
    protected FeedbackCompanyCustomer $customer;
    protected ?FeedbackCompanyInvitation $invitation = null;

    /**
     * @var ?Collection<FeedbackCompanyProduct>
     */
    protected ?Collection $products = null;

    protected ?string $filterCode = null;

    /**
     * @param string $externalId
     * @param FeedbackCompanyCustomer $customer
     * @param ?FeedbackCompanyInvitation $invitation
     * @param ?Collection $products
     * @param ?string $filterCode
     */
    public function __construct(string $externalId, FeedbackCompanyCustomer $customer, ?FeedbackCompanyInvitation $invitation = null, ?Collection $products = null, ?string $filterCode = null)
    {
        $this->externalId = $externalId;
        $this->customer = $customer;
        $this->invitation = $invitation;
        $this->products = $products;
        $this->filterCode = $filterCode;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function customer(): FeedbackCompanyCustomer
    {
        return $this->customer;
    }

    public function invitation(): ?FeedbackCompanyInvitation
    {
        return $this->invitation;
    }

    public function products(): ?Collection
    {
        return $this->products;
    }

    public function filterCode(): ?string
    {
        return $this->filterCode;
    }

    public function toArray(): array
    {
        $order = [
            'External_id' => $this->externalId,
            'Customer' => $this->customer->toArray(),
            'invitation' => $this->invitation?->toArray(),
            'products' => $this->products?->toArray(),
            'filtercode' => $this->filterCode
        ];

        return array_filter($order);
    }
}
