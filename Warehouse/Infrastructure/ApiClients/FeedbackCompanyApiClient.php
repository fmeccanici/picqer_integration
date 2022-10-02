<?php

namespace App\Warehouse\Infrastructure\ApiClients;

use App\Warehouse\Infrastructure\Exceptions\FeedbackCompanyApiClientException;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyCustomer;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyInvitation;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyReviewRequest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FeedbackCompanyApiClient extends ApiClient
{
    protected int $expireDaysToken = 20;
    protected string $clientId;
    protected string $clientSecret;

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function __construct()
    {
        $this->clientId = config('warehouse.feedback_company.client_id');
        $this->clientSecret = config('warehouse.feedback_company.client_secret');
    }

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function oauthRefreshtoken()
    {
        $url = 'https://www.feedbackcompany.com/api/v2/oauth2/token';

        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code'
        ];

        $result = $this->httpCall($url,'get', $params);

        if (isset($result->access_token))
        {
            Cache::add('feedback_company.access_token', $result->access_token, 86400 * $this->expireDaysToken);
        } else {
            throw new FeedbackCompanyApiClientException('Could not get access token, error: ' . $result->error);
        }
    }

    /**
     * @throws GuzzleException
     * @throws FeedbackCompanyApiClientException
     */
    public function httpCall(string $url, string $method, array $params, $retry = true)
    {
        $params['headers'] = array();

        if (Cache::get('feedback_company.access_token'))
        {
            $params['headers']['Authorization'] = 'Bearer ' . Cache::get('feedback_company.access_token');
        }

        if (Str::lower($method) == 'get')
        {
            $response = Http::withHeaders($params['headers'])->get($url, $params);

        } else if (Str::lower($method) == 'post')
        {
            $response = Http::withHeaders($params['headers'])->post($url, $params);
        } else {
            throw new FeedbackCompanyApiClientException('HTTP method ' . $method . ' does not exist');
        }

        $response = json_decode($response->getBody());

        if (isset($response->error) && $response->error == 'Unauthorized' && $retry)
        {
            Cache::forget('feedback_company.access_token');
            $this->oauthRefreshtoken();

            if (Str::lower($method) == 'get')
            {
                return $this->httpCall($url, 'get', $params, false);
            } else if (Str::lower($method) == 'post')
            {
                return $this->httpCall($url, 'post', $params, false);
            }

        }

        return $response;
    }


    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function createOrder(string $externalId, FeedbackCompanyCustomer $customer, ?FeedbackCompanyInvitation $invitation, ?Collection $products, ?string $filterCode = null): bool
    {
        $reviewRequest = new FeedbackCompanyReviewRequest($externalId, $customer, $invitation, $products, $filterCode);
        $url = 'https://feedbackcompany.com/api/v2/orders';
        $body = $reviewRequest->toArray();
        $response = $this->httpCall($url, 'post', $body);

        $response = json_decode(json_encode($response), true);

        if (! Arr::get($response, 'success'))
        {
            throw new FeedbackCompanyApiClientException('Could not create order, error: ' . Arr::get($response, 'error'));
        }

        return true;
    }

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function listInvitations(?int $page = null, ?int $perPage = null, array $filters = [])
    {
        $params = [];
        if ($page)
        {
            $params['page'] = $page;
        }

        if ($perPage)
        {
            $params['per_page'] = $perPage;
        }

        $url = 'https://www.feedbackcompany.com/api/v2/invitations';
        $response = $this->httpCall($url, 'get', $params);

        if (! $response->success)
        {
            throw new FeedbackCompanyApiClientException('Could not list invitations, error: ' . $response->error);
        }
    }

    public function getClient()
    {
        return $this;
    }
}
