<?php

namespace simialbi\bexio\models;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use simialbi\bexio\Module;
use Yii;

/**
 * @property-read Salutation $salutation
 * @property-read Title $title
 * @property-read Country $country
 * @property-read Language $language
 * @property-read Invoice[] $invoices
 * @property-read Offer[] $offers
 */
class Contact extends Model
{
    const CONTACT_TYPE_COMPANY = 1;
    const CONTACT_TYPE_PERSON = 2;

    public ?int $id;
    public ?string $nr;
    public int $contact_type_id = self::CONTACT_TYPE_COMPANY;
    public string $name_1;
    public ?string $name_2;
    public ?int $salutation_id;
    public ?string $salutation_form;
    public ?int $title_id;
    public ?string $birthday;
    public ?string $address;
    public ?string $street_name;
    public ?string $house_number;
    public ?string $address_addition;
    public ?string $postcode;
    public ?string $city;
    public ?int $country_id;
    public ?string $mail;
    public ?string $mail_second;
    public ?string $phone_fixed;
    public ?string $phone_fixed_second;
    public ?string $phone_mobile;
    public ?string $fax;
    public ?string $url;
    public ?string $skype_name;
    public ?string $remarks;
    public ?int $language_id;
    public bool $is_lead = false;
    public ?string $contact_group_ids;
    public ?string $contact_branch_ids;
    public ?int $user_id;
    public ?int $owner_id;
    public ?string $updated_at;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'user_id', 'owner_id'], 'integer'],
            ['contact_type_id', 'in', 'range' => [self::CONTACT_TYPE_COMPANY, self::CONTACT_TYPE_PERSON]],
            [['name_1', 'name_2', 'mail', 'salutation_form', 'address', 'street_name', 'house_number', 'address_addition', 'city', 'skype_name', 'remarks'], 'string'],
            [['phone_fixed', 'phone_fixed_second', 'phone_mobile', 'fax'], 'filter', 'filter' => function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                try {
                    $language = explode('-', Yii::$app->language);
                    $region = (count($language) > 1) ? $language[1] : strtoupper($language[0]);
                    $util = PhoneNumberUtil::getInstance();
                    $numberPrototype = $util->parse($value, $region);

                    return $util->format($numberPrototype, PhoneNumberFormat::INTERNATIONAL);
                } catch (NumberParseException) {
                    return $value;
                }
            }],
            [['mail', 'mail_second'], 'email', 'enableIDN' => function_exists('idn_to_ascii')],
            ['updated_at', 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
            ['is_lead', 'boolean'],

            ['is_lead', 'default', 'value' => false],
            ['contact_type_id', 'default', 'value' => self::CONTACT_TYPE_PERSON],

            [['name_1', 'contact_type_id', 'is_lead'], 'required']
        ];
    }

    /**
     * Get associated salutation
     *
     * @return ?Salutation
     */
    public function getSalutation(): ?Salutation
    {
        if ($this->salutation_id === null) {
            return null;
        }

        return Module::getInstance()->getSalutation($this->salutation_id);
    }

    /**
     * Get associated title
     *
     * @return ?Title
     */
    public function getTitle(): ?Title
    {
        if ($this->title_id === null) {
            return null;
        }

        return Module::getInstance()->getTitle($this->title_id);
    }

    /**
     * Get associated country
     *
     * @return ?Title
     */
    public function getCountry(): ?Country
    {
        if ($this->country_id === null) {
            return null;
        }

        return Module::getInstance()->getCountry($this->country_id);
    }

    /**
     * Get associated language
     *
     * @return ?Language
     */
    public function getLanguage(): ?Language
    {
        if ($this->language_id === null) {
            return null;
        }

        return Module::getInstance()->getLanguage($this->language_id);
    }

    /**
     * Get associated invoices
     *
     * @return Invoice[]
     */
    public function getInvoices(): array
    {
        return Module::getInstance()->searchInvoice(contact_id: $this->id);
    }

    /**
     * Get associated offers
     *
     * @return Offer[]
     */
    public function getOffers(): array
    {
        return Module::getInstance()->searchOffer(contact_id: $this->id);
    }
}
