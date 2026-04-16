<?php

namespace simialbi\bexio\models;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Yii;
use yii\base\Model;

/**
 * @property int $id
 * @property string $nr
 * @property int $contact_type_id 1 = Company, 2 = Person
 * @property string $name_1
 * @property string $name_2
 * @property int $salutation_id
 * @property string $salutation_form
 * @property int $title_id
 * @property string $birthday
 * @property string $address
 * @property string $street_name
 * @property string $house_number
 * @property string $address_addition
 * @property string $postcode
 * @property string $city
 * @property int $country_id
 * @property string $mail
 * @property string $mail_second
 * @property string $phone_fixed
 * @property string $phone_fixed_second
 * @property string $phone_mobile
 * @property string $fax
 * @property string $url
 * @property string $skype_name
 * @property string $remarks
 * @property int $language_id
 * @property bool $is_lead
 * @property string $contact_group_ids
 * @property string $contact_branch_ids
 * @property int $user_id
 * @property int $owner_id
 * @property string $updated_at
 *
 * @property Salutation $salutation
 * @property Title $title
 * @property Country $country
 * @property Language $language
 */
class Contact extends Model
{
    const CONTACT_TYPE_COMPANY = 1;
    const CONTACT_TYPE_PERSON = 2;

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
}
