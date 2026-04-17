<?php

namespace simialbi\bexio;

use simialbi\bexio\models\Contact;
use simialbi\bexio\models\Country;
use simialbi\bexio\models\Invoice;
use simialbi\bexio\models\Language;
use simialbi\bexio\models\Offer;
use simialbi\bexio\models\Salutation;
use simialbi\bexio\models\Title;
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
                    'timeout' => 10,
                    'ssl_verifypeer' => false,
                    'ssl_verifyhost' => false
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
     * Searches for invoices based on the provided criteria.
     *
     * @param int|null $id The ID of the invoice.
     * @param string|null $nr The invoice number.
     * @param int|null $contact_id The contact ID associated with the invoice.
     * @param int|null $user_id The user ID associated with the invoice.
     * @param int|null $project_id The project ID associated with the invoice.
     * @param string|null $title The title of the invoice.
     * @param int|null $api_reference The API reference.
     * @param string|null $updated_at The last updated timestamp.
     *
     * @return Invoice[] An array of Invoice objects that match the search criteria.
     */
    public function searchInvoice(
        ?int $id = null,
        ?string $nr = null,
        ?int $contact_id = null,
        ?int $user_id = null,
        ?int $project_id = null,
        ?string $title = null,
        ?int $api_reference = null,
        ?string $updated_at = null
    ): array
    {
        $i = new Invoice();
        $criteria = [];
        foreach ($i->attributes() as $key) {
            if (isset($$key) && $$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/kb_invoice/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Invoice($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the invoice corresponding to the given ID.
     *
     * @param int $id The identifier of the invoice to retrieve.
     *
     * @return ?Invoice The invoice object if found, or null if unavailable.
     */
    public function getInvoice(int $id): ?Invoice
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_invoice/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Invoice($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new invoice using the provided invoice data.
     *
     * @param Invoice $invoice The invoice object containing the details to be created.
     *
     * @return Invoice The created invoice object if successful, or null if the operation fails.
     *
     * @throws BexioException
     */
    public function createInvoice(Invoice $invoice): Invoice
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/kb_invoice')
                ->setMethod('post')
                ->setContent(Json::encode($invoice->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Invoice($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create invoice: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create invoice: {$response->getData()['message']}");
    }

    /**
     * Updates the specified invoice with the given details.
     *
     * @param Invoice $invoice The invoice object containing updated information.
     *
     * @return Invoice The updated invoice object if the operation is successful.
     *
     * @throws BexioException If the update request fails.
     */
    public function updateInvoice(Invoice $invoice): Invoice
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_invoice/{$invoice->id}")
                ->setMethod('post')
                ->setContent(Json::encode($invoice->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Invoice($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update invoice: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update invoice: {$response->getData()['message']}");
    }

    /**
     * Deletes the specified invoice from the system.
     *
     * @param Invoice $invoice The invoice object to delete.
     *
     * @return bool True if the invoice was successfully deleted.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteInvoice(Invoice $invoice): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_invoice/{$invoice->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete invoice: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete invoice: {$response->getData()['message']}");
    }

    /**
     * Searches for offers based on the provided criteria.
     *
     * @param int|null $id The ID of the offer.
     * @param int|null $kb_item_status_id The status ID of the offer.
     * @param string|null $document_nr The offer number.
     * @param string|null $title The title of the offer.
     * @param int|null $contact_id The contact ID associated with the offer.
     * @param int|null $contact_sub_id The contact sub ID associated with the offer.
     * @param int|null $user_id The user ID associated with the offer.
     * @param int|null $currency_id The currency ID.
     * @param string|null $total_gross The total gross amount.
     * @param string|null $total_net The total net amount.
     * @param string|null $total The total amount.
     * @param string|null $is_valid_from The valid from date.
     * @param string|null $is_valid_to The valid to date.
     * @param string|null $is_valid_until The valid until date.
     * @param string|null $updated_at The last updated timestamp.
     *
     * @return Offer[] An array of Offer objects that match the search criteria.
     */
    public function searchOffer(
        ?int $id = null,
        ?int $kb_item_status_id = null,
        ?string $document_nr = null,
        ?string $title = null,
        ?int $contact_id = null,
        ?int $contact_sub_id = null,
        ?int $user_id = null,
        ?int $currency_id = null,
        ?string $total_gross = null,
        ?string $total_net = null,
        ?string $total = null,
        ?string $is_valid_from = null,
        ?string $is_valid_to = null,
        ?string $is_valid_until = null,
        ?string $updated_at = null
    ): array
    {
        $o = new Offer();
        $criteria = [];
        foreach ($o->attributes() as $key) {
            if (isset($$key) && $$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/kb_offer/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Offer($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the offer corresponding to the given ID.
     *
     * @param int $id The identifier of the offer to retrieve.
     *
     * @return ?Offer The offer object if found, or null if unavailable.
     */
    public function getOffer(int $id): ?Offer
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_offer/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Offer($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new offer using the provided offer data.
     *
     * @param Offer $offer The offer object containing the details to be created.
     *
     * @return Offer The created offer object if successful, or null if the operation fails.
     *
     * @throws BexioException
     */
    public function createOffer(Offer $offer): Offer
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/kb_offer')
                ->setMethod('post')
                ->setContent(Json::encode($offer->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Offer($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create offer: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create offer: {$response->getData()['message']}");
    }

    /**
     * Updates the specified offer with the given details.
     *
     * @param Offer $offer The offer object containing updated information.
     *
     * @return Offer The updated offer object if the operation is successful.
     *
     * @throws BexioException If the update request fails.
     */
    public function updateOffer(Offer $offer): Offer
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_offer/{$offer->id}")
                ->setMethod('post')
                ->setContent(Json::encode($offer->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Offer($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update offer: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update offer: {$response->getData()['message']}");
    }

    /**
     * Deletes the specified offer from the system.
     *
     * @param Offer $offer The offer object to delete.
     *
     * @return bool True if the offer was successfully deleted.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteOffer(Offer $offer): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/kb_offer/{$offer->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete offer: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete offer: {$response->getData()['message']}");
    }

    /**
     * Searches for contacts based on the provided criteria.
     *
     * @param int|null $id The ID of the contact.
     * @param string|null $name_1 The first name of the contact.
     * @param string|null $name_2 The last name of the contact.
     * @param string|null $nr The contact number.
     * @param string|null $address The address of the contact.
     * @param string|null $mail The primary email of the contact.
     * @param string|null $mail_second The secondary email of the contact.
     * @param string|null $postcode The postcode of the contact's address.
     * @param string|null $city The city of the contact's address.
     * @param int|null $country_id The country ID associated with the contact.
     * @param string|null $contact_group_ids A string of contact group IDs.
     * @param string|null $contact_type_id The contact type ID.
     * @param string|null $updated_at The last updated timestamp.
     * @param int|null $user_id The user ID associated with the contact.
     * @param string|null $phone_fixed The fixed phone number of the contact.
     * @param string|null $phone_mobile The mobile phone number of the contact.
     * @param string|null $fax The fax number of the contact.
     *
     * @return array An array of Contact objects that match the search criteria.
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
        $c = new Contact();
        $criteria = [];
        foreach ($c->attributes() as $key) {
            if ($$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/contact/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Contact($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the contact corresponding to the given ID.
     *
     * @param int $id The identifier of the contact to retrieve.
     *
     * @return ?Contact The contact object if found, or null if unavailable.
     */
    public function getContact(int $id): ?Contact
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/contact/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Contact($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new contact using the provided contact data.
     *
     * @param Contact $contact The contact object containing the details to be created.
     *
     * @return Contact The created contact object if successful, or null if the operation fails.
     *
     * @throws BexioException
     */
    public function createContact(Contact $contact): Contact
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/contact')
                ->setMethod('post')
                ->setContent(Json::encode($contact->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Contact($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create contact: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create contact: {$response->getData()['message']}");
    }

    /**
     * Updates the specified contact with the given details.
     *
     * @param Contact $contact The contact object containing updated information.
     *
     * @return Contact The updated contact object if the operation is successful.
     *
     * @throws BexioException If the update request fails.
     */
    public function updateContact(Contact $contact): Contact
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/contact/{$contact->id}")
                ->setMethod('post')
                ->setContent(Json::encode($contact->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Contact($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update contact: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update contact: {$response->getData()['message']}");
    }

    /**
     * Deletes the specified contact from the system.
     *
     * @param Contact $contact The contact object to delete.
     *
     * @return bool True if the contact was successfully deleted.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteContact(Contact $contact): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/contact/{$contact->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete contact: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete contact: {$response->getData()['message']}");
    }

    /**
     * Searches for salutations based on the provided name.
     *
     * @param string|null $name The name or part of the name to search for. Can be null to apply no name filtering.
     *
     * @return Salutation[] An array of salutation objects matching the search criteria, or an empty array if no matches
     * are found.
     */
    public function searchSalutation(?string $name = null): array
    {
        $s = new Salutation();
        $criteria = [];
        foreach ($s->attributes() as $key) {
            if ($$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/salutation/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Salutation($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the salutation corresponding to the given ID.
     *
     * @param int $id The identifier of the salutation to retrieve.
     *
     * @return Salutation|null The salutation object if found, or null if unavailable.
     */
    public function getSalutation(int $id): ?Salutation
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/salutation/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Salutation($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new salutation with the given data.
     *
     * @param Salutation $salutation The salutation object containing the data to be created.
     *
     * @return Salutation The created salutation object.
     *
     * @throws BexioException If the creation request fails.
     */
    public function createSalutation(Salutation $salutation): Salutation
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/salutation')
                ->setMethod('post')
                ->setContent(Json::encode($salutation->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Salutation($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create salutation: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create salutation: {$response->getData()['message']}");
    }

    /**
     * Updates the specified salutation with the given details.
     *
     * @param Salutation $salutation The salutation object containing updated information.
     *
     * @return Salutation The updated salutation object if the operation is successful.
     *
     * @throws BexioException If the update request fails.
     */
    public function updateSalutation(Salutation $salutation): Salutation
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/salutation/{$salutation->id}")
                ->setMethod('post')
                ->setContent(Json::encode($salutation->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Salutation($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update salutation: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update salutation: {$response->getData()['message']}");
    }

    /**
     * Deletes the specified salutation from the system.
     *
     * @param Salutation $salutation The salutation object to delete.
     *
     * @return bool True if the salutation was successfully deleted.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteSalutation(Salutation $salutation): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/salutation/{$salutation->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete salutation: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete salutation: {$response->getData()['message']}");
    }

    /**
     * Searches for titles based on the provided name.
     *
     * @param string|null $name The name or part of the name to search for. Can be null to apply no name filtering.
     *
     * @return Title[] An array of title objects matching the search criteria, or an empty array if no matches
     * are found.
     */
    public function searchTitle(?string $name = null): array
    {
        $t = new Title();
        $criteria = [];
        foreach ($t->attributes() as $key) {
            if ($$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/title/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Title($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the title corresponding to the given ID.
     *
     * @param int $id The identifier of the title to retrieve.
     *
     * @return Title|null The title object if found, or null if unavailable.
     */
    public function getTitle(int $id): ?Title
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/title/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Title($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new title with the given data.
     *
     * @param Title $title The title object containing the data to be created.
     *
     * @return Title The created title object.
     *
     * @throws BexioException If the creation request fails.
     */
    public function createTitle(Title $title): Title
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/title')
                ->setMethod('post')
                ->setContent(Json::encode($title->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Title($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create title: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create title: {$response->getData()['message']}");
    }

    /**
     * Updates the specified title with the given details.
     *
     * @param Title $title The title object containing updated information.
     *
     * @return Title The updated title object if the operation is successful.
     *
     * @throws BexioException If the update request fails.
     */
    public function updateTitle(Title $title): Title
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/title/{$title->id}")
                ->setMethod('post')
                ->setContent(Json::encode($title->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Title($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update title: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update title: {$response->getData()['message']}");
    }

    /**
     * Deletes the specified title from the system.
     *
     * @param Title $title The title object to delete.
     *
     * @return bool True if the title was successfully deleted.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteTitle(Title $title): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/title/{$title->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete title: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete title: {$response->getData()['message']}");
    }

    /**
     * Searches for countries based on the provided parameters.
     *
     * @param string|null $name The name of the country.
     * @param string|null $name_short The short name of the country.
     * @param string|null $iso3166_alpha2 The ISO 3166-1 alpha-2 code of the country.
     *
     * @return Country[] An array of country objects matching the search criteria.
     */
    public function searchCountry(
        ?string $name = null,
        ?string $name_short = null,
        ?string $iso3166_alpha2 = null
    ): array
    {
        $c = new Country();
        $criteria = [];
        foreach ($c->attributes() as $key) {
            if ($$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/country/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Country($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the country corresponding to the given ID.
     *
     * @param int $id The identifier of the country to retrieve.
     *
     * @return Country|null The country object if found, or null if unavailable.
     */
    public function getCountry(int $id): ?Country
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/country/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Country($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new country.
     *
     * @param Country $country The country object to create.
     *
     * @return Country The created country object.
     *
     * @throws BexioException If the creation fails.
     */
    public function createCountry(Country $country): Country
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/country')
                ->setMethod('post')
                ->setContent(Json::encode($country->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Country($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create country: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create country: {$response->getData()['message']}");
    }

    /**
     * Updates an existing country.
     *
     * @param Country $country The country object to update.
     *
     * @return Country The updated country object.
     *
     * @throws BexioException If the update fails.
     */
    public function updateCountry(Country $country): Country
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/country/{$country->id}")
                ->setMethod('post')
                ->setContent(Json::encode($country->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Country($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update country: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update country: {$response->getData()['message']}");
    }

    /**
     * Deletes a country.
     *
     * @param Country $country The country object to delete.
     *
     * @return bool True if the deletion was successful.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteCountry(Country $country): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/country/{$country->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete country: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete country: {$response->getData()['message']}");
    }

    /**
     * Searches for languages based on the provided parameters.
     *
     * @param string|null $name The name of the language.
     * @param string|null $iso_639_1 The ISO 639-1 code of the language.
     *
     * @return Language[] An array of language objects matching the search criteria.
     */
    public function searchLanguage(?string $name = null, ?string $iso_639_1 = null): array
    {
        $l = new Language();
        $criteria = [];
        foreach ($l->attributes() as $key) {
            if ($$key !== null) {
                $criteria[] = [
                    'field' => $key,
                    'value' => $$key,
                    'criteria' => ($key === 'id' || str_ends_with($key, '_id')) ? '=' : 'like'
                ];
            }
        }

        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/language/search')
                ->setMethod('post')
                ->setContent(Json::encode($criteria));
            $response = $request->send();

            if ($response->isOk) {
                return array_map(function (array $data) {
                    return new Language($data);
                }, $response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return [];
    }

    /**
     * Retrieves the language corresponding to the given ID.
     *
     * @param int $id The identifier of the language to retrieve.
     *
     * @return Language|null The language object if found, or null if unavailable.
     */
    public function getLanguage(int $id): ?Language
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/language/$id")
                ->setMethod('get');
            $response = $request->send();

            if ($response->isOk) {
                return new Language($response->getData());
            }
        } catch (Exception|InvalidConfigException) {}

        return null;
    }

    /**
     * Creates a new language.
     *
     * @param Language $language The language object to create.
     *
     * @return Language The created language object.
     *
     * @throws BexioException If the creation fails.
     */
    public function createLanguage(Language $language): Language
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl('/language')
                ->setMethod('post')
                ->setContent(Json::encode($language->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Language($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to create language: {$e->getMessage()}");
        }

        throw new BexioException("Failed to create language: {$response->getData()['message']}");
    }

    /**
     * Updates an existing language.
     *
     * @param Language $language The language object to update.
     *
     * @return Language The updated language object.
     *
     * @throws BexioException If the update fails.
     */
    public function updateLanguage(Language $language): Language
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/language/{$language->id}")
                ->setMethod('post')
                ->setContent(Json::encode($language->toArray()));
            $response = $request->send();

            if ($response->isOk) {
                return new Language($response->getData());
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to update language: {$e->getMessage()}");
        }

        throw new BexioException("Failed to update language: {$response->getData()['message']}");
    }

    /**
     * Deletes a language.
     *
     * @param Language $language The language object to delete.
     *
     * @return bool True if the deletion was successful.
     *
     * @throws BexioException If the deletion fails.
     */
    public function deleteLanguage(Language $language): bool
    {
        try {
            $request = $this->_connection->createRequest()
                ->setUrl("/language/{$language->id}")
                ->setMethod('delete');
            $response = $request->send();

            if ($response->isOk) {
                return true;
            }
        } catch (Exception|InvalidConfigException $e) {
            throw new BexioException("Failed to delete language: {$e->getMessage()}");
        }

        throw new BexioException("Failed to delete language: {$response->getData()['message']}");
    }
}
