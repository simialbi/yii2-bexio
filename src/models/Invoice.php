<?php

namespace simialbi\bexio\models;

use simialbi\bexio\Module;

/**
 * @property-read Contact $contact
 * @property-read Language $language
 */
class Invoice extends Model
{
    public ?int $id;
    public ?string $nr;
    public int $contact_id;
    public ?int $contact_sub_id;
    public int $user_id;
    public ?int $project_id;
    public ?string $title;
    public ?string $header;
    public ?string $footer;
    public ?int $mwst_type;
    public ?int $mwst_is_net;
    public ?bool $show_position_taxes;
    public string $is_valid_from;
    public ?string $is_valid_to;
    public ?int $contact_address;
    public ?int $kb_item_status_id;
    public ?int $api_reference;
    public ?int $viewed_on;
    public ?int $last_viewed_on;
    public ?string $template_slug;
    public ?int $tax_id;
    public ?int $tax_type;
    public ?int $payment_type_id;
    public ?string $total_gross;
    public ?string $total_net;
    public ?string $total_taxes;
    public ?string $total_received_payments;
    public ?string $total_credit_vouchers;
    public ?string $total_remaining_payments;
    public ?string $total_waiting_payments;
    public ?string $total;
    public string $currency_id;
    public string $language_id;
    public ?string $updated_at;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'contact_id', 'contact_sub_id', 'user_id', 'project_id', 'mwst_type', 'mwst_is_net', 'contact_address', 'kb_item_status_id', 'api_reference', 'viewed_on', 'last_viewed_on', 'tax_id', 'tax_type', 'payment_type_id'], 'integer'],
            [['nr', 'title', 'header', 'footer', 'template_slug', 'total_gross', 'total_net', 'total_taxes', 'total_received_payments', 'total_credit_vouchers', 'total_remaining_payments', 'total_waiting_payments', 'total', 'currency_id', 'language_id'], 'string'],
            [['is_valid_from', 'is_valid_to', 'updated_at'], 'string'],
            [['show_position_taxes'], 'boolean'],

            [['contact_id', 'user_id', 'is_valid_from', 'currency_id', 'language_id'], 'required']
        ];
    }

    /**
     * Get associated contact
     *
     * @return ?Contact
     */
    public function getContact(): ?Contact
    {
        return Module::getInstance()->getContact($this->contact_id);
    }

    /**
     * Get associated language
     *
     * @return ?Language
     */
    public function getLanguage(): ?Language
    {
        return Module::getInstance()->getLanguage($this->language_id);
    }
}
