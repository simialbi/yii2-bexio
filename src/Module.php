<?php

namespace simialbi\bexio;

use simialbi\bexio\models\Contact;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Exception;

class Module extends \yii\base\Module
{
    /**
     * @var Client The http client configuration.
     */
    private Client $_connection;

    /**
     * @var string The bearer token
     */
    public string $accessToken;

    public string $baseUrl = 'https://api.bexio.com/2.0';

    public string $clientId;

    public string $clientSecret;

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        $config = [
            'class' => Client::class,
            'baseUrl' => $this->baseUrl,
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'format' => Client::FORMAT_JSON,
                'options' => [
                    'timeout' => 10
                ]
            ],
            'responseConfig' => [
                'format' => Client::FORMAT_JSON
            ]
        ];

        if (isset($this->accessToken)) {
            $config['requestConfig']['headers']['Authorization'] = 'Bearer ' . $this->accessToken;
        } elseif ($this->clientId && $this->clientSecret) {
            // TODO OIDC
        } else {
            throw new InvalidConfigException('Either accessToken or clientId and clientSecret must be set.');
        }

        $this->_connection = Instance::ensure($config, Client::class);

        parent::init();
    }

    /**
     * @param int|null $id
     * @param string|null $name_1
     * @param string|null $name_2
     * @param string|null $nr
     * @param string|null $address
     * @param string|null $mail
     * @param string|null $mail_second
     * @param string|null $postcode
     * @param string|null $city
     * @param int|null $country_id
     * @param string|null $contact_group_ids
     * @param string|null $contact_type_id
     * @param string|null $updated_at
     * @param int|null $user_id
     * @param string|null $phone_fixed
     * @param string|null $phone_mobile
     * @param string|null $fax
     *
     * @return Contact[]
     * @throws InvalidConfigException|Exception
     */
    public function searchContact(
        ?int $id = null,
        ?string $name_1 = null,
        ?string $name_2 = null,
        ?string $nr = null,
        ?string $address = null,
        ?string $mail = null,
        ?string $mail_second = null,
        ?string $postcode = null,
        ?string $city = null,
        ?int $country_id = null,
        ?string $contact_group_ids = null,
        ?string $contact_type_id = null,
        ?string $updated_at = null,
        ?int $user_id = null,
        ?string $phone_fixed = null,
        ?string $phone_mobile = null,
        ?string $fax = null
    ): array
    {
        $criteria = [];
        foreach (func_get_args() as $key => $value) {
            if ($value !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $value,
                    'criteria' => 'like'
                ];
            }
        }

        $response = $this->_connection->createRequest()
            ->setUrl('/client/search')
            ->setMethod('post')
            ->setData(Json::encode($criteria))
            ->send();

        if ($response->isOk) {
            return array_map(function (array $data) {
                return new Contact($data);
            }, $response->getData());
        }

        return [];
    }
}
