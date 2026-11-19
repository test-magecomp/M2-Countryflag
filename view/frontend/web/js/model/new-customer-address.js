/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define([
    'underscore',
    'Magento_Checkout/js/model/default-post-code-resolver'
], function (_, DefaultPostCodeResolver) {
    'use strict';

    /**
     * @param {Object} addressData
     * Returns new address object
     */
    return function (addressData) {
        var identifier = Date.now(),
            countryId = addressData['country_id'] || addressData.countryId || window.checkoutConfig.defaultCountryId,
            regionId;

        if (addressData.region && addressData.region['region_id']) {
            regionId = addressData.region['region_id'];
        } else if (
            /* eslint-disable */
            addressData['country_id'] && addressData['country_id'] == window.checkoutConfig.defaultCountryId ||
            !addressData['country_id'] && countryId == window.checkoutConfig.defaultCountryId
            /* eslint-enable */
        ) {
            regionId = window.checkoutConfig.defaultRegionId || undefined;
        }
        // Add dial code and number in new address form(checkout shipping new address)
        var mobile = addressData.telephone;
        var dialcode = jQuery(".form-shipping-address .flag-container > .country-list > .active").attr("data-dial-code");
        if (!dialcode) {
            var shippingSdc = jQuery(".form-shipping-address .selected-dial-code").text();
            if (shippingSdc) {
                dialcode = shippingSdc.replace('+', '').trim();
            }
        }
        if (!dialcode) {
            var activeLi = jQuery(".form-shipping-address .country-list .country.active, .country-list .country.active");
            if (activeLi.length) {
                dialcode = activeLi.attr("data-dial-code");
            }
        }

        if (jQuery("._active .checkout-billing-address .billing-address-form input[name='telephone']").val() == addressData.telephone) {
            var billingDial = jQuery("._active .checkout-billing-address .billing-address-form .flag-container > .country-list > .active").attr("data-dial-code");
            if (!billingDial) {
                var billingSdc = jQuery("._active .checkout-billing-address .billing-address-form .selected-dial-code").text();
                if (billingSdc) {
                    billingDial = billingSdc.replace('+', '').trim();
                }
            }
            if (billingDial) {
                dialcode = billingDial;
            }
        }
        if (jQuery("#isFlagEnable").length) {
            if (dialcode != undefined && dialcode !== '') {
                dialcode = dialcode.replace('+', '').trim();
                if (mobile && !mobile.startsWith('+' + dialcode) && !mobile.startsWith(dialcode)) {
                    mobile = "+" + dialcode + mobile;
                }
            }
        }

        return {
            email: addressData.email,
            countryId: countryId,
            regionId: regionId || addressData.regionId,
            regionCode: addressData.region ? addressData.region['region_code'] : null,
            region: addressData.region ? addressData.region.region : null,
            customerId: addressData['customer_id'] || addressData.customerId,
            street: addressData.street,
            company: addressData.company,
            telephone: mobile,
            fax: addressData.fax,
            postcode: addressData.postcode ? addressData.postcode : DefaultPostCodeResolver.resolve(),
            city: addressData.city,
            firstname: addressData.firstname,
            lastname: addressData.lastname,
            middlename: addressData.middlename,
            prefix: addressData.prefix,
            suffix: addressData.suffix,
            vatId: addressData['vat_id'],
            saveInAddressBook: addressData['save_in_address_book'],
            customAttributes: addressData['custom_attributes'],
            extensionAttributes: addressData['extension_attributes'],

            /**
             * @return {*}
             */
            isDefaultShipping: function () {
                return addressData['default_shipping'];
            },

            /**
             * @return {*}
             */
            isDefaultBilling: function () {
                return addressData['default_billing'];
            },

            /**
             * @return {String}
             */
            getType: function () {
                return 'new-customer-address';
            },

            /**
             * @return {String}
             */
            getKey: function () {
                return this.getType();
            },

            /**
             * @return {String}
             */
            getCacheKey: function () {
                return this.getType() + identifier;
            },

            /**
             * @return {Boolean}
             */
            isEditable: function () {
                return true;
            },

            /**
             * @return {Boolean}
             */
            canUseForBilling: function () {
                return true;
            }
        };
    };
});