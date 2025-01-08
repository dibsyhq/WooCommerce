<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Dibsy_PAYMENT class.
 *
 * Communicates with Dibsy API.
 */
class WC_Dibsy_Payment
{
    protected $amount;
    protected $redirectUrl;
    protected $method;
    protected $webhookUrl;
    protected $metadata;
    protected $customer_id;
    protected $description;

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }
}
