<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use Illuminate\Contracts\Support\Arrayable;

class FeedbackCompanyProduct implements Arrayable
{
    protected string $externalId;
    protected string $name;
    protected string $url;
    protected string $sku;
    protected string $gtin;
    protected string $imageUrl;

    /**
     * @param string $externalId
     * @param string $name
     * @param string $url
     * @param string $sku
     * @param string $gtin
     * @param string $imageUrl
     */
    public function __construct(string $externalId, string $name, string $url, string $sku, string $gtin, string $imageUrl)
    {
        $this->externalId = $externalId;
        $this->name = $name;
        $this->url = $url;
        $this->sku = $sku;
        $this->gtin = $gtin;
        $this->imageUrl = $imageUrl;
    }

    public function toArray()
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'url' => $this->url,
            'sku' => $this->sku,
            'gtin' => $this->gtin,
            'image_url' => $this->imageUrl
        ];
    }
}
