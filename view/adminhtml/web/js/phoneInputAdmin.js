define([
    'jquery',
    'Magecomp_Countryflag/js/intlTelInput.min',
    'jquery/validate',
    'mage/validation'
], function($) {
    'use strict';

    return function(config) {
        if (window.magecompCountryflagAdminBooted) {
            return;
        }
        window.magecompCountryflagAdminBooted = true;

        var pinnedCountries = config.pinnedCountries || [];
        var availableCountries = config.availableCountries || [];
        var defaultCountry = config.defaultCountry || 'us';
        var utilsScriptUrl = config.utilsScriptUrl || '';
        var validationRules = config.validationRules || [];
        var countryCodes = config.countryCodes || {};

        function getRequiredDigits(iso2) {
            if (!iso2) {
                return 10;
            }
            var iso = iso2.toString().toUpperCase();
            var defaultDigit = 10;
            if (Array.isArray(validationRules)) {
                for (var i = 0; i < validationRules.length; i++) {
                    var rule = validationRules[i];
                    if (rule && rule.country && rule.country.toString().toUpperCase() === iso) {
                        return parseInt(rule.digit, 10);
                    }
                    if (rule && (rule.country == 1 || rule.country === '1' || rule.country === 'default')) {
                        defaultDigit = parseInt(rule.digit, 10);
                    }
                }
            }
            return defaultDigit;
        }

        function getCountryDialCode(iso2) {
            if (!iso2) {
                return '';
            }
            return countryCodes[iso2.toString().toUpperCase()] || '';
        }

        function detectCountryFromPhone(phoneNumber) {
            if (!phoneNumber) {
                return null;
            }
            var clean = phoneNumber.toString().trim();
            var digitsOnly = clean.replace(/\D/g, '');
            if (!digitsOnly) {
                return null;
            }

            var startsWithPlus = (clean.charAt(0) === '+');
            var sortedIsos = Object.keys(countryCodes).sort(function(a, b) {
                return (countryCodes[b] || '').length - (countryCodes[a] || '').length;
            });

            for (var i = 0; i < sortedIsos.length; i++) {
                var iso = sortedIsos[i];
                var dCode = countryCodes[iso];
                if (dCode && digitsOnly.indexOf(dCode) === 0) {
                    if (!startsWithPlus && digitsOnly.length <= 10) {
                        continue;
                    }
                    var candidate = iso.toLowerCase();
                    if (dCode === '1') {
                        var areaCode = digitsOnly.substring(1, 4);
                        var caAreaCodes = /^(204|226|236|249|250|289|306|343|354|365|367|403|416|418|431|437|438|450|506|514|519|548|579|581|587|600|604|613|639|647|672|705|709|742|778|780|782|807|819|825|867|873|902|905)$/;
                        candidate = caAreaCodes.test(areaCode) ? 'ca' : 'us';
                    }
                    return candidate;
                }
            }
            return null;
        }

        function extractNationalDigits(phoneNumber, iso2) {
            if (!phoneNumber) {
                return '';
            }
            var str = phoneNumber.toString().trim();
            var digitsOnly = str.replace(/\D/g, '');
            if (!digitsOnly) {
                return '';
            }
            var dialCode = getCountryDialCode(iso2);
            if (dialCode && (str.charAt(0) === '+' || digitsOnly.length > 10)) {
                if (digitsOnly.indexOf(dialCode) === 0) {
                    return digitsOnly.substring(dialCode.length);
                }
            }
            return digitsOnly;
        }

        function findRelatedCountrySelect($phone) {
            if ($phone.is('#order-billing_address_telephone')) {
                return $('#order-billing_address_country_id');
            }
            if ($phone.is('#order-shipping_address_telephone')) {
                return $('#order-shipping_address_country_id');
            }

            var $container = $phone.closest([
                '.customer_address_update_modal',
                '[data-role="address-form"]',
                '.admin__collapsible-block',
                '[data-repeat-index]',
                'fieldset.admin__fieldset',
                'fieldset',
                'form',
                '.modal-content',
                '.modal-inner-wrap',
                '#customer_address_form'
            ].join(','));

            if ($container.length) {
                var $sel = $container.find([
                    'select[name="country_id"]',
                    'div[data-index="country_id"] select',
                    'select[name*="[country_id]"]',
                    'select[name*="country_id"]',
                    '.admin__control-select[name*="country"]'
                ].join(',')).first();

                if ($sel.length) {
                    return $sel;
                }
            }

            var $modal = $('.modal-slide._show, .modal-popup._show, ._show[data-role="modal"]');
            if ($modal.length) {
                var $modalSel = $modal.find('select[name*="country_id"], div[data-index="country_id"] select').first();
                if ($modalSel.length) {
                    return $modalSel;
                }
            }

            return $();
        }

        function resolveInitialCountry($phone, $countrySelect) {
            var rawVal = $.trim($phone.val() || '');
            if (rawVal) {
                var detected = detectCountryFromPhone(rawVal);
                if (detected) {
                    return detected.toLowerCase();
                }
            }
            if ($countrySelect && $countrySelect.length) {
                var selectVal = $.trim($countrySelect.val() || $countrySelect.find('option:selected').val() || '').toLowerCase();
                if (selectVal) {
                    if (!availableCountries || availableCountries.length === 0 || availableCountries.indexOf(selectVal) !== -1) {
                        return selectVal;
                    }
                }
            }
            var fallback = (defaultCountry || '').toLowerCase();
            if (fallback && (!availableCountries || availableCountries.length === 0 || availableCountries.indexOf(fallback) !== -1)) {
                return fallback;
            }
            if (availableCountries && availableCountries.length > 0) {
                return availableCountries[0].toLowerCase();
            }
            return 'us';
        }

        function findTargetInputs() {
            var elements = [];
            var targetSelectors = [
                '#order-billing_address_telephone',
                '#order-shipping_address_telephone',
                'input[name="order[billing_address][telephone]"]',
                'input[name="order[shipping_address][telephone]"]',
                'input[name*="[telephone]"]',
                '#telephone',
                'input[name="telephone"]',
                'div[data-index="telephone"] input',
                '.customer_address_update_modal input[name*="telephone"]',
                '#customer_address_form input[name="telephone"]'
            ];

            $(targetSelectors.join(',')).each(function() {
                var $el = $(this);
                var type = ($el.attr('type') || 'text').toLowerCase();
                var name = ($el.attr('name') || '').toLowerCase();
                var id = ($el.attr('id') || '').toLowerCase();
                var dIdx = ($el.closest('[data-index]').attr('data-index') || '').toLowerCase();

                // Exclude mobile number fields and verification fields
                if (name.indexOf('verify') !== -1 || id.indexOf('verify') !== -1 || dIdx.indexOf('verify') !== -1 ||
                    name.indexOf('mobile') !== -1 || id.indexOf('mobile') !== -1 || dIdx.indexOf('mobile') !== -1) {
                    return;
                }

                if (type !== 'hidden' && type !== 'checkbox' && type !== 'radio' && type !== 'button') {
                    if (elements.indexOf(this) === -1) {
                        elements.push(this);
                    }
                }
            });

            $('.admin__field').each(function() {
                var $field = $(this);
                var $label = $field.find('.admin__field-label, label').first();
                if (!$label.length) {
                    return;
                }

                var labelText = $.trim($label.text() || '').toLowerCase();
                var isPhoneLabel = (
                    labelText === 'phone number' ||
                    labelText === 'phone number *' ||
                    labelText.indexOf('phone number') === 0 ||
                    labelText === 'telephone' ||
                    labelText === 'telephone *' ||
                    labelText === 'phone'
                );

                if (isPhoneLabel && labelText.indexOf('verify') === -1 && labelText.indexOf('mobile') === -1) {
                    var $inp = $field.find('input.admin__control-text, input[type="text"], input[type="tel"]').first();
                    if ($inp.length && $inp.attr('type') !== 'hidden' && $inp.attr('type') !== 'checkbox' && $inp.attr('type') !== 'radio') {
                        var inpName = ($inp.attr('name') || '').toLowerCase();
                        var inpId = ($inp.attr('id') || '').toLowerCase();
                        if (inpName.indexOf('mobile') === -1 && inpId.indexOf('mobile') === -1) {
                            if (elements.indexOf($inp[0]) === -1) {
                                elements.push($inp[0]);
                            }
                        }
                    }
                }
            });

            return $(elements);
        }

        function validateSinglePhone($phone) {
            if (!$phone || !$phone.length || !$phone.data('plugin_intlTelInput')) {
                return true;
            }

            var val = $.trim($phone.val() || '');
            var $wrapper = $phone.closest('.intl-tel-input');
            var errId = ($phone.attr('id') || 'phone_' + Math.random().toString(36).substring(7)) + '-custom-error';
            var $errEl = $('#' + errId);

            if (!$errEl.length) {
                $errEl = $('<div class="mage-error phone-validation-error" id="' + errId + '"></div>');
                if ($wrapper.length) {
                    $errEl.insertAfter($wrapper);
                } else {
                    $errEl.insertAfter($phone);
                }
            }

            if (val === '') {
                $errEl.text('').hide();
                $phone.removeClass('mage-error');
                return true;
            }

            if (!/^\d+$/.test(val)) {
                $errEl.text($.mage.__('Please, Enter Valid Mobile Number.')).show();
                $phone.addClass('mage-error');
                return false;
            }

            var countryData = $phone.intlTelInput('getSelectedCountryData');
            var iso = (countryData && countryData.iso2) ? countryData.iso2 : '';
            var requiredDigits = getRequiredDigits(iso);

            if (requiredDigits && val.length !== requiredDigits) {
                var msg = $.mage.__('Your Mobile Number Must be %1 digit long without country code.').replace('%1', requiredDigits);
                $errEl.text(msg).show();
                $phone.addClass('mage-error');
                return false;
            }

            $errEl.text('').hide();
            $phone.removeClass('mage-error');
            return true;
        }

        function refreshPhoneField($phone) {
            if (!$phone || !$phone.length || !$phone.data('plugin_intlTelInput')) {
                return;
            }

            var rawVal = $.trim($phone.val() || '');
            var currentCountryData = $phone.intlTelInput('getSelectedCountryData');
            var currentIso = (currentCountryData && currentCountryData.iso2) ? currentCountryData.iso2.toLowerCase() : '';
            var targetIso = currentIso;

            if (rawVal) {
                var detected = detectCountryFromPhone(rawVal);
                if (detected) {
                    targetIso = detected;
                }
            }

            if (!targetIso) {
                targetIso = defaultCountry || 'us';
            }

            if (!currentIso || currentIso !== targetIso) {
                $phone.intlTelInput('setCountry', targetIso);
            }

            var activeCountryData = $phone.intlTelInput('getSelectedCountryData') || {};
            var activeIso = (activeCountryData.iso2 || targetIso || 'us').toLowerCase();
            var activeDial = activeCountryData.dialCode ? ('+' + activeCountryData.dialCode) : ('+' + getCountryDialCode(activeIso));

            var $wrap = $phone.closest('.intl-tel-input');
            if ($wrap.length) {
                var $flg = $wrap.find('.iti-flag');
                if ($flg.length && !$flg.hasClass(activeIso)) {
                    $flg.attr('class', 'iti-flag ' + activeIso);
                }
                var $dialEl = $wrap.find('.selected-dial-code');
                if ($dialEl.length && (!$dialEl.text().trim() || $dialEl.text().trim() !== activeDial)) {
                    $dialEl.text(activeDial);
                }
                if (activeDial) {
                    $wrap.removeClass('iti-sdc-2 iti-sdc-3 iti-sdc-4 iti-sdc-5').addClass('iti-sdc-' + activeDial.length);
                }
            }

            if (rawVal) {
                var nationalDigits = extractNationalDigits(rawVal, targetIso);
                if (nationalDigits !== rawVal) {
                    $phone.val(nationalDigits);
                }
            }

            var reqDigits = getRequiredDigits(targetIso);
            if (reqDigits) {
                $phone.attr('maxlength', reqDigits);
            }

            if (typeof intlTelInputUtils !== 'undefined') {
                if (targetIso === 'in') {
                    $phone.attr('placeholder', '98123 45678');
                } else {
                    var exR = intlTelInputUtils.getExampleNumber(targetIso, true, intlTelInputUtils.numberType.MOBILE);
                    if (exR) {
                        var dOnlyR = exR.replace(/\D/g, '');
                        if (reqDigits && dOnlyR.length > reqDigits && dOnlyR.charAt(0) === '0') {
                            exR = exR.replace(/^0\s*/, '');
                        }
                        $phone.attr('placeholder', exR);
                    }
                }
            } else if (targetIso === 'in') {
                $phone.attr('placeholder', '98123 45678');
            }
        }

        function initPhoneField(inputEl) {
            var $phone = $(inputEl);
            if (!$phone.length) {
                return;
            }
            if ($phone.data('plugin_intlTelInput') || $phone.closest('.intl-tel-input, .iti').length) {
                refreshPhoneField($phone);
                return;
            }

            var $countrySelect = findRelatedCountrySelect($phone);
            var initialCountry = resolveInitialCountry($phone, $countrySelect);
            var rawVal = $.trim($phone.val() || '');

            var initialVal = rawVal;
            if (rawVal) {
                initialVal = extractNationalDigits(rawVal, initialCountry);
                $phone.val(initialVal);
            }

            var options = {
                initialCountry: initialCountry,
                separateDialCode: true,
                nationalMode: true,
                autoPlaceholder: 'polite',
                customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                    var cIso = (selectedCountryData && selectedCountryData.iso2) ? selectedCountryData.iso2.toLowerCase() : '';
                    if (cIso === 'in') {
                        return '98123 45678';
                    }
                    if (selectedCountryPlaceholder) {
                        var req = getRequiredDigits(cIso);
                        var dOnly = selectedCountryPlaceholder.replace(/\D/g, '');
                        if (req && dOnly.length > req && dOnly.charAt(0) === '0') {
                            return selectedCountryPlaceholder.replace(/^0\s*/, '');
                        }
                        return selectedCountryPlaceholder;
                    }
                    return '';
                },
                utilsScript: utilsScriptUrl
            };

            if (pinnedCountries && pinnedCountries.length > 0) {
                options.preferredCountries = pinnedCountries;
            }
            if (availableCountries && availableCountries.length > 0) {
                if (initialCountry && availableCountries.indexOf(initialCountry) === -1) {
                    var expandedCountries = availableCountries.slice(0);
                    expandedCountries.push(initialCountry);
                    options.onlyCountries = expandedCountries;
                } else {
                    options.onlyCountries = availableCountries;
                }
            }

            try {
                $phone.intlTelInput(options);
            } catch (e) {
                console.error('intlTelInput init error:', e);
                return;
            }

            var finalIso = (initialCountry || 'us').toLowerCase();
            $phone.intlTelInput('setCountry', finalIso);

            var countryData = $phone.intlTelInput('getSelectedCountryData') || {};
            var currentIso = (countryData.iso2 || finalIso).toLowerCase();
            var currentDialCode = countryData.dialCode ? ('+' + countryData.dialCode) : ('+' + getCountryDialCode(currentIso));

            var $container = $phone.closest('.intl-tel-input');
            if ($container.length) {
                var $flagInner = $container.find('.iti-flag');
                if ($flagInner.length && !$flagInner.hasClass(currentIso)) {
                    $flagInner.attr('class', 'iti-flag ' + currentIso);
                }
                var $sdc = $container.find('.selected-dial-code');
                if ($sdc.length && !$sdc.text().trim() && currentDialCode) {
                    $sdc.text(currentDialCode);
                }
                if (currentDialCode) {
                    var sdcLen = currentDialCode.length;
                    $container.removeClass('iti-sdc-2 iti-sdc-3 iti-sdc-4 iti-sdc-5').addClass('iti-sdc-' + sdcLen);
                }
            }

            if (initialVal) {
                $phone.val(initialVal);
            }

            var reqDigits = getRequiredDigits(finalIso);
            if (reqDigits) {
                $phone.attr('maxlength', reqDigits);
            }

            if (typeof intlTelInputUtils !== 'undefined') {
                if (finalIso === 'in') {
                    $phone.attr('placeholder', '98123 45678');
                } else {
                    var ex = intlTelInputUtils.getExampleNumber(finalIso, true, intlTelInputUtils.numberType.MOBILE);
                    if (ex) {
                        var dOnly = ex.replace(/\D/g, '');
                        if (reqDigits && dOnly.length > reqDigits && dOnly.charAt(0) === '0') {
                            ex = ex.replace(/^0\s*/, '');
                        }
                        $phone.attr('placeholder', ex);
                    }
                }
            } else if (finalIso === 'in') {
                $phone.attr('placeholder', '98123 45678');
            }

            // Flag change updates validation and maxlength (without changing address country)
            $phone.off('countrychange.iti_sync').on('countrychange.iti_sync', function(e, countryData) {
                if (countryData && countryData.iso2) {
                    var newIso = countryData.iso2.toLowerCase();
                    var newReq = getRequiredDigits(newIso);
                    if (newReq) {
                        $phone.attr('maxlength', newReq);
                    } else {
                        $phone.removeAttr('maxlength');
                    }
                    if (typeof intlTelInputUtils !== 'undefined') {
                        if (newIso === 'in') {
                            $phone.attr('placeholder', '98123 45678');
                        } else {
                            var ex2 = intlTelInputUtils.getExampleNumber(newIso, true, intlTelInputUtils.numberType.MOBILE);
                            if (ex2) {
                                var dOnly2 = ex2.replace(/\D/g, '');
                                if (newReq && dOnly2.length > newReq && dOnly2.charAt(0) === '0') {
                                    ex2 = ex2.replace(/^0\s*/, '');
                                }
                                $phone.attr('placeholder', ex2);
                            }
                        }
                    } else if (newIso === 'in') {
                        $phone.attr('placeholder', '98123 45678');
                    }
                    validateSinglePhone($phone);
                }
            });

            // Auto-detect country if user types international dialing code
            $phone.off('input.iti_detect paste.iti_detect').on('input.iti_detect paste.iti_detect', function() {
                var self = this;
                setTimeout(function() {
                    var v = $.trim($(self).val() || '');
                    if (v) {
                        var iso = detectCountryFromPhone(v);
                        if (iso) {
                            var currentData = $(self).intlTelInput('getSelectedCountryData') || {};
                            if ((currentData.iso2 || '').toLowerCase() !== iso.toLowerCase()) {
                                $(self).intlTelInput('setCountry', iso);
                            }
                            var dCode = getCountryDialCode(iso);
                            var digitsOnly = v.replace(/\D/g, '');
                            if (dCode && digitsOnly.indexOf(dCode) === 0 && (v.charAt(0) === '+' || digitsOnly.length > 10)) {
                                var nat = digitsOnly.substring(dCode.length);
                                $(self).val(nat);
                            }
                        }
                    }
                    validateSinglePhone($(self));
                }, 10);
            });

            $phone.off('keyup.iti_val blur.iti_val').on('keyup.iti_val blur.iti_val', function() {
                validateSinglePhone($phone);
            });

            $phone.addClass('validate-phone-digits');
        }

        function formatSinglePhoneBeforeSubmit($p) {
            if (!$p || !$p.length || !$p.data('plugin_intlTelInput')) {
                return true;
            }
            var rawVal = $.trim($p.val() || '');
            if (!rawVal) {
                return true;
            }

            var isVal = validateSinglePhone($p);
            if (!isVal) {
                return false;
            }

            var full = '';
            try {
                full = $p.intlTelInput('getNumber');
            } catch (e) {}

            if (!full || full.indexOf('+') !== 0) {
                var countryData = $p.intlTelInput('getSelectedCountryData');
                var dialCode = (countryData && countryData.dialCode) ? String(countryData.dialCode).replace(/\D/g, '') : '';
                var digitsOnly = rawVal.replace(/\D/g, '');
                if (dialCode && digitsOnly) {
                    if (digitsOnly.indexOf(dialCode) === 0) {
                        full = '+' + digitsOnly;
                    } else {
                        full = '+' + dialCode + digitsOnly;
                    }
                } else {
                    full = rawVal;
                }
            }

            if (full) {
                $p.val(full);
                try {
                    if (typeof ko !== 'undefined' && ko.dataFor) {
                        var koData = ko.dataFor($p[0]);
                        if (koData && typeof koData.value === 'function') {
                            koData.value(full);
                        }
                    }
                } catch (err) {}
                $p.trigger('change').trigger('input');
            }
            return true;
        }

        function formatAllPhonesBeforeSubmit() {
            var allValid = true;
            findTargetInputs().each(function() {
                var valid = formatSinglePhoneBeforeSubmit($(this));
                if (!valid) {
                    allValid = false;
                }
            });
            return allValid;
        }

        function ensureOrderHooks() {
            if (!window.order) {
                return;
            }

            if (typeof window.order.fillAddressFields === 'function' && !window.order.fillAddressFields._itiWrapped) {
                var origFill = window.order.fillAddressFields;
                window.order.fillAddressFields = function(container, data) {
                    var res = origFill.apply(this, arguments);
                    try {
                        setTimeout(function() {
                            initAllPhones();
                            $(container).find('input[name*="[telephone]"]').each(function() {
                                var $p = $(this);
                                if (!$p.data('plugin_intlTelInput')) {
                                    initPhoneField(this);
                                } else {
                                    refreshPhoneField($p);
                                }
                            });
                        }, 50);
                    } catch (e) {}
                    return res;
                };
                window.order.fillAddressFields._itiWrapped = true;
            }

            if (typeof window.order.loadAreaResponseHandler === 'function' && !window.order.loadAreaResponseHandler._itiWrapped) {
                var origHandler = window.order.loadAreaResponseHandler;
                window.order.loadAreaResponseHandler = function(response) {
                    var res = origHandler.apply(this, arguments);
                    setTimeout(initAllPhones, 50);
                    setTimeout(initAllPhones, 300);
                    return res;
                };
                window.order.loadAreaResponseHandler._itiWrapped = true;
            }

            if (typeof window.order.dataLoaded === 'function' && !window.order.dataLoaded._itiWrapped) {
                var origDataLoaded = window.order.dataLoaded;
                window.order.dataLoaded = function() {
                    var res = origDataLoaded.apply(this, arguments);
                    setTimeout(initAllPhones, 50);
                    setTimeout(initAllPhones, 300);
                    return res;
                };
                window.order.dataLoaded._itiWrapped = true;
            }

            if (typeof window.order.dataShow === 'function' && !window.order.dataShow._itiWrapped) {
                var origDataShow = window.order.dataShow;
                window.order.dataShow = function() {
                    var res = origDataShow.apply(this, arguments);
                    setTimeout(initAllPhones, 50);
                    return res;
                };
                window.order.dataShow._itiWrapped = true;
            }

            if (typeof window.order.setShippingAsBilling === 'function' && !window.order.setShippingAsBilling._itiWrapped) {
                var origShippingAsBilling = window.order.setShippingAsBilling;
                window.order.setShippingAsBilling = function(flag) {
                    var res = origShippingAsBilling.apply(this, arguments);
                    if (flag) {
                        setTimeout(function() {
                            var $bPhone = $('#order-billing_address_telephone');
                            var $sPhone = $('#order-shipping_address_telephone');
                            if ($bPhone.data('plugin_intlTelInput') && $sPhone.data('plugin_intlTelInput')) {
                                var bData = $bPhone.intlTelInput('getSelectedCountryData');
                                if (bData && bData.iso2) {
                                    $sPhone.intlTelInput('setCountry', bData.iso2);
                                }
                                $sPhone.val($bPhone.val());
                                validateSinglePhone($sPhone);
                            }
                        }, 50);
                    }
                    return res;
                };
                window.order.setShippingAsBilling._itiWrapped = true;
            }

            if (typeof window.order.submit === 'function' && !window.order.submit._itiWrapped) {
                var originalOrderSubmit = window.order.submit;
                window.order.submit = function() {
                    var valid = formatAllPhonesBeforeSubmit();
                    if (!valid) {
                        return false;
                    }
                    return originalOrderSubmit.apply(this, arguments);
                };
                window.order.submit._itiWrapped = true;
            }
        }

        function initAllPhones() {
            ensureOrderHooks();
            findTargetInputs().each(function() {
                initPhoneField(this);
            });
        }

        // Custom validation method
        try {
            if ($.validator) {
                $.validator.addMethod(
                    'validate-phone-digits',
                    function(value, element) {
                        var $el = $(element);
                        if (!$el.data('plugin_intlTelInput')) {
                            return true;
                        }
                        var clean = (value || '').trim();
                        if (clean === '') {
                            return true;
                        }
                        if (!/^\d+$/.test(clean)) {
                            return false;
                        }
                        var cData = $el.intlTelInput('getSelectedCountryData');
                        var iso2 = cData ? cData.iso2 : '';
                        var req = getRequiredDigits(iso2);
                        return !req || clean.length === req;
                    },
                    function(params, element) {
                        var clean = ($(element).val() || '').trim();
                        if (clean !== '' && !/^\d+$/.test(clean)) {
                            return $.mage.__('Please, Enter Valid Mobile Number.');
                        }
                        var cData = $(element).intlTelInput('getSelectedCountryData');
                        var iso2 = cData ? cData.iso2 : '';
                        var req = getRequiredDigits(iso2);
                        if (req) {
                            return $.mage.__('Your Mobile Number Must be %1 digit long without country code.').replace('%1', req);
                        }
                        return $.mage.__('Please, Enter Valid Mobile Number.');
                    }
                );
            }
        } catch (e) {}

        // Form submit listeners
        $(document).on('submit', '#customer_form, #edit_form, form#order-create-form, #customer_address_form, .customer-address-form form', function(e) {
            var valid = formatAllPhonesBeforeSubmit();
            if (!valid) {
                e.preventDefault();
                return false;
            }
        });

        // Save button click listeners
        $(document).on('click', [
            '#save',
            '#save_and_continue',
            '[data-ui-id="save-button"]',
            '[data-ui-id="save-and-continue-button"]',
            'button.save',
            '[form-role="save"]',
            'button[data-action="save-address"]',
            '.action-save-address',
            '.customer_address_update_modal button.action-primary',
            '#order-create-form button.save'
        ].join(','), function(e) {
            var valid = formatAllPhonesBeforeSubmit();
            if (!valid) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });

        // Handle Order "Same As Billing" checkbox
        $(document).on('change', '#order-shipping_same_as_billing', function() {
            if ($(this).is(':checked')) {
                setTimeout(function() {
                    var $bPhone = $('#order-billing_address_telephone');
                    var $sPhone = $('#order-shipping_address_telephone');
                    if ($bPhone.data('plugin_intlTelInput') && $sPhone.data('plugin_intlTelInput')) {
                        var bCountry = $bPhone.intlTelInput('getSelectedCountryData');
                        if (bCountry && bCountry.iso2) {
                            $sPhone.intlTelInput('setCountry', bCountry.iso2);
                        }
                        $sPhone.val($bPhone.val());
                        validateSinglePhone($sPhone);
                    }
                }, 50);
            }
        });

        // Handle Order existing address selection
        $(document).on('change', '#order-billing_address_customer_address_id, #order-shipping_address_customer_address_id', function() {
            setTimeout(function() {
                initAllPhones();
                $('#order-billing_address_telephone, #order-shipping_address_telephone').each(function() {
                    var $p = $(this);
                    if ($p.data('plugin_intlTelInput')) {
                        refreshPhoneField($p);
                        validateSinglePhone($p);
                    }
                });
            }, 200);
        });

        // Handle navigation tabs, modal opens, and accordion clicks
        $(document).on('click', [
            '.admin__page-nav-item',
            '[data-role="title"]',
            '.admin__collapsible-title',
            'button[data-action="open-modal"]',
            '.action-default',
            '.add-new-address-button',
            '.edit-default-billing-address-button',
            '.edit-default-shipping-address-button'
        ].join(','), function() {
            setTimeout(initAllPhones, 200);
            setTimeout(initAllPhones, 500);
        });

        $(document).on('modalopened', function() {
            setTimeout(initAllPhones, 100);
            setTimeout(initAllPhones, 300);
        });

        // Handle Prototype Ajax completions
        if (window.Ajax && window.Ajax.Responders && !window.Ajax.Responders._itiHooked) {
            window.Ajax.Responders.register({
                onComplete: function() {
                    setTimeout(initAllPhones, 150);
                }
            });
            window.Ajax.Responders._itiHooked = true;
        }

        // Handle jQuery AJAX completions
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings && settings.url && (
                settings.url.indexOf('order') !== -1 ||
                settings.url.indexOf('customer') !== -1 ||
                settings.url.indexOf('address') !== -1 ||
                settings.url.indexOf('render_handle') !== -1
            )) {
                setTimeout(initAllPhones, 150);
            }
        });

        // Mutation observer for dynamically inserted fields
        var mutationTimer = null;
        if (window.MutationObserver) {
            var observer = new window.MutationObserver(function(mutations) {
                var hasNodes = false;
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes && mutations[i].addedNodes.length > 0) {
                        hasNodes = true;
                        break;
                    }
                }
                if (hasNodes) {
                    if (mutationTimer) {
                        clearTimeout(mutationTimer);
                    }
                    mutationTimer = setTimeout(initAllPhones, 200);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        // Ensure country dropdown opens reliably in admin forms
        $(document).on('click', '.intl-tel-input .selected-flag', function(e) {
            var $flag = $(this);
            var $wrapper = $flag.closest('.intl-tel-input');
            var $list = $wrapper.find('.country-list');
            var $input = $wrapper.find('input[type="text"], input[type="tel"]').first();

            $('.intl-tel-input .country-list').not($list).addClass('hide');
            $('.intl-tel-input .selected-flag .iti-arrow').not($flag.find('.iti-arrow')).removeClass('up');

            if ($input.data('plugin_intlTelInput')) {
                return;
            }

            if (!$list.length) {
                return;
            }

            if ($list.hasClass('hide')) {
                $list.removeClass('hide');
                $flag.find('.iti-arrow').addClass('up');
                var $active = $list.find('.country.active');
                if ($active.length) {
                    var pos = $active.position();
                    if (pos) {
                        $list.scrollTop($list.scrollTop() + pos.top - 50);
                    }
                }
            } else {
                $list.addClass('hide');
                $flag.find('.iti-arrow').removeClass('up');
            }
            e.stopPropagation();
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.intl-tel-input').length) {
                $('.intl-tel-input .country-list').addClass('hide');
                $('.intl-tel-input .selected-flag .iti-arrow').removeClass('up');
            }
        });

        // Click on country item
        $(document).on('click', '.intl-tel-input .country-list .country:not(.no-results)', function(e) {
            var $item = $(this);
            var iso = ($item.attr('data-country-code') || '').toLowerCase();
            var dialCode = $item.attr('data-dial-code') || '';
            var $wrapper = $item.closest('.intl-tel-input');
            var $input = $wrapper.find('input[type="text"], input[type="tel"]').first();

            if ($input.length && iso) {
                if ($input.data('plugin_intlTelInput')) {
                    $input.intlTelInput('setCountry', iso);
                } else {
                    $wrapper.find('.iti-flag').attr('class', 'iti-flag ' + iso);
                    $wrapper.find('.selected-dial-code').text('+' + dialCode);
                }
                $input.trigger('countrychange', [{ iso2: iso, dialCode: dialCode }]);
                $input.trigger('input').trigger('change');
            }

            $wrapper.find('.country-list').addClass('hide');
            $wrapper.find('.selected-flag .iti-arrow').removeClass('up');
            e.stopPropagation();
        });

        $(document).ready(function() {
            initAllPhones();

            var counter = 0;
            var interval = setInterval(function() {
                initAllPhones();
                counter++;
                if (counter >= 40) {
                    clearInterval(interval);
                }
            }, 250);

            var orderHookCount = 0;
            var orderInterval = setInterval(function() {
                orderHookCount++;
                if (window.order) {
                    ensureOrderHooks();
                    if (window.order.loadAreaResponseHandler && window.order.loadAreaResponseHandler._itiWrapped) {
                        clearInterval(orderInterval);
                    }
                }
                if (orderHookCount >= 60) {
                    clearInterval(orderInterval);
                }
            }, 300);
        });
    };
});
