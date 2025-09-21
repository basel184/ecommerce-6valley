"use strict";

$(document).ready(function() {
    try {
        initAutocomplete();
    } catch (error) {}
    try {
        billingMap();
    } catch (error) {}

    if($('input[name="shipping_method_id"]').is(':checked')){
        let selectedInput = $('[name="shipping_method_id"]:checked');
        let shippingValue = selectedInput.siblings('.selected_' + selectedInput.attr('id')).val();
        if (shippingValue) {
            // Call the global shipping.js function with proper data format
            if (typeof window.shipping_method_select === 'function') {
                window.shipping_method_select(shippingValue);
            }
        }
    }
    if($('input[name="billing_method_id"]').is(':checked')){
        let selectedInput = $('[name="billing_method_id"]:checked');
        let billingValue = selectedInput.siblings('.selected_' + selectedInput.attr('id')).val();
        if (billingValue) {
            // Call the global shipping.js function with proper data format
            if (typeof window.billing_method_select === 'function') {
                window.billing_method_select(billingValue);
            }
        }
    }

    try {
        initializePhoneInput(".phone-input-with-country-picker-shipping", ".country-picker-phone-number-shipping");
    } catch (error) {}

});

$('[name="shipping_method_id"]').on('change', function(){
    let selectedAddressId = $(this).val();
    let parentCard = $(this).closest('.address-card');
    let cardBody = parentCard.find('.address-card-body');
    
    // Extract data from the card body spans
    let shippingData = {
        name: cardBody.find('.shipping-contact-person').text() || '',
        phone: cardBody.find('.shipping-contact-phone').text() || '',
        address: cardBody.find('.shipping-contact-address').text() || '',
        city: cardBody.find('.shipping-contact-city').text() || '',
        zip: cardBody.find('.shipping-contact-zip').text() || '',
        country: cardBody.find('.shipping-contact-country').text() || '',
        address_type: cardBody.find('.shipping-contact-address_type').text() || ''
    };
    
    // Use our local function
    shipping_method_select_local(shippingData);
    
    // Update the hidden input
    $('#shipping_method_id').val(selectedAddressId);
});


$('[name="billing_method_id"]').on('change', function(){
    let selectedAddressId = $(this).val();
    let parentCard = $(this).closest('.address-card');
    let cardBody = parentCard.find('.address-card-body');
    
    // Extract data from the card body spans
    let billingData = {
        name: cardBody.find('.billing-contact-name').text() || '',
        phone: cardBody.find('.billing-contact-phone').text() || '',
        address: cardBody.find('.billing-contact-address').text() || '',
        city: cardBody.find('.billing-contact-city').text() || '',
        zip: cardBody.find('.billing-contact-zip').text() || '',
        country: cardBody.find('.billing-contact-country').text() || '',
        address_type: cardBody.find('.billing-contact-address_type').text() || ''
    };
    
    // Use our local function
    billing_method_select_local(billingData);
    
    // Update the hidden input
    $('#billing_method_id').val(selectedAddressId);
});

$('#same_as_shipping_address').on('click', function(){
    let check_same_as_shippping = $('#same_as_shipping_address').is(":checked");
    if (check_same_as_shippping) {
        $('#hide_billing_address').slideUp();
    } else {
        $('#hide_billing_address').slideDown();
    }
})


function shipping_method_select_local(shippingData){
    let update_this_address = $('.text-custom-storage').data('update-this-address') || 'Update this address';
    let shipping_method_id = $('[name="shipping_method_id"]:checked').val();
    
    // Handle both object data and cardBody element
    let shipping_person, shipping_phone, shipping_address, shipping_city, shipping_zip, shipping_country, shipping_contact_address_type;
    
    if (typeof shippingData === 'object' && shippingData.name) {
        // Data from object (saved addresses)
        shipping_person = shippingData.name;
        shipping_phone = shippingData.phone;
        shipping_address = shippingData.address;
        shipping_city = shippingData.city;
        shipping_zip = shippingData.zip;
        shipping_country = shippingData.country;
        shipping_contact_address_type = shippingData.address_type;
    } else {
        // Data from cardBody element (legacy support)
        shipping_person = shippingData.find('.shipping-contact-person').text();
        shipping_phone = shippingData.find('.shipping-contact-phone').text();
        shipping_address = shippingData.find('.shipping-contact-address').text();
        shipping_city = shippingData.find('.shipping-contact-city').text();
        shipping_zip = shippingData.find('.shipping-contact-zip').text();
        shipping_country = shippingData.find('.shipping-contact-country').text();
        shipping_contact_address_type = shippingData.find('.shipping-contact-address_type').text();
    }
    
    let update_address = `
        <input type="hidden" name="shipping_method_id" id="shipping_method_id" value="${shipping_method_id}">
        <input type="checkbox" name="update_address" id="update_address" class="form-check-input dark-form-check-input"> ${update_this_address}`;

    // Fill form fields if they exist
    const nameField = $('#name');
    if (nameField.length) {
        nameField.val(shipping_person);
    }
    
    const phoneNumberField = $('#phone_number');
    const phoneHiddenField = $('#phone_hidden');
    if (phoneNumberField.length) {
        phoneNumberField.val(shipping_phone);
    }
    if (phoneHiddenField.length) {
        phoneHiddenField.val(shipping_phone);
    }
    
    const addressField = $('#address');
    if (addressField.length) {
        addressField.val(shipping_address);
    }
    
    const cityField = $('#city');
    if (cityField.length) {
        cityField.val(shipping_city);
    }
    
    const zipField = $('#zip');
    if (zipField.length) {
        zipField.val(shipping_zip);
        $('#select2-zip-container').text(shipping_zip);
    }
    
    const countryField = $('#country');
    if (countryField.length) {
        countryField.val(shipping_country);
        $('#select2-country-container').text(shipping_country);
    }
    
    const addressTypeField = $('#address_type');
    if (addressTypeField.length) {
        addressTypeField.val(shipping_contact_address_type);
    }
    
    const saveLabel = $('#save_address_label');
    if (saveLabel.length) {
        saveLabel.html(update_address);
    }
    
    console.log('✅ Shipping address data loaded:', {
        name: shipping_person,
        city: shipping_city,
        address: shipping_address,
        id: shipping_method_id
    });
}

function billing_method_select_local(billingData){
    let update_this_address = $('.text-custom-storage').data('update-this-address') || 'Update this address';
    let billing_method_id = $('[name="billing_method_id"]:checked').val();
    
    // Handle both object data and cardBody element
    let billing_person, billing_phone, billing_address, billing_city, billing_zip, billing_country, billing_contact_address_type;
    
    if (typeof billingData === 'object' && billingData.name) {
        // Data from object (saved addresses)
        billing_person = billingData.name;
        billing_phone = billingData.phone;
        billing_address = billingData.address;
        billing_city = billingData.city;
        billing_zip = billingData.zip;
        billing_country = billingData.country;
        billing_contact_address_type = billingData.address_type;
    } else {
        // Data from cardBody element (legacy support)
        billing_person = billingData.find('.billing-contact-name').text();
        billing_phone = billingData.find('.billing-contact-phone').text();
        billing_address = billingData.find('.billing-contact-address').text();
        billing_city = billingData.find('.billing-contact-city').text();
        billing_zip = billingData.find('.billing-contact-zip').text();
        billing_country = billingData.find('.billing-contact-country').text();
        billing_contact_address_type = billingData.find('.billing-contact-address_type').text();
    }
    
    let update_address_billing = `
        <input type="hidden" name="billing_method_id" id="billing_method_id" value="${billing_method_id}">
        <input type="checkbox" name="update_billing_address" id="update_billing_address" class="form-check-input dark-form-check-input"> ${update_this_address}`;

    // Fill the form fields
    const billingNameField = $('#billing_contact_person_name');
    if (billingNameField.length) {
        billingNameField.val(billing_person);
    }
    
    const billingPhoneField = $('#billing_phone_hidden');
    if (billingPhoneField.length) {
        billingPhoneField.val(billing_phone);
    }
    
    const billingAddressField = $('#billing_address');
    if (billingAddressField.length) {
        billingAddressField.val(billing_address);
    }
    
    const billingCityField = $('#billing_city');
    const billingCitySearchField = $('#billing_city_search');
    if (billingCityField.length) {
        billingCityField.val(billing_city || 'جدة');
    }
    if (billingCitySearchField.length) {
        billingCitySearchField.val(billing_city || 'جدة');
    }
    
    const billingZipField = $('#billing_zip');
    if (billingZipField.length) {
        billingZipField.val(billing_zip || 'permanent');
        $('#select2-billing_zip-container').text(billing_zip || 'permanent');
    }
    
    const billingCountryField = $('#billing_country');
    if (billingCountryField.length) {
        billingCountryField.val(billing_country || 'Saudi Arabia');
        $('#select2-billing_country-container').text(billing_country || 'Saudi Arabia');
    }
    
    // Handle billing_address_type - set to permanent if element exists
    const billingAddressTypeElement = $('#billing_address_type');
    if (billingAddressTypeElement.length) {
        billingAddressTypeElement.val(billing_contact_address_type || 'permanent');
    }
    
    const saveLabel = $('#save-billing-address-label');
    if (saveLabel.length) {
        saveLabel.html(update_address_billing);
    }
    
    console.log('✅ Billing address data loaded:', {
        name: billing_person,
        city: billing_city,
        address: billing_address,
        id: billing_method_id
    });
}

async function initAutocomplete() {
    // Check if Google Maps API is loaded
    if (typeof google === 'undefined' || !google.maps) {
        console.warn('Google Maps API not loaded. Skipping map initialization.');
        return;
    }
    
    try {
        let myLatLng = {
            lat: $('#shippingaddress-storage').data('latitude') || 24.7136,
            lng: $('#shippingaddress-storage').data('longitude') || 46.6753,
        };
        const { Map } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
        const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
            center: myLatLng,
            zoom: 13,
            mapId: "roadmap",
        });

        let marker = new AdvancedMarkerElement({
            map,
            position: myLatLng,
        });

        marker.setMap( map );
        var geocoder = geocoder = new google.maps.Geocoder();
    google.maps.event.addListener(map, 'click', function (mapsMouseEvent) {
        var coordinate = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
        var coordinates = JSON.parse(coordinate);
        var latlng = new google.maps.LatLng( coordinates['lat'], coordinates['lng'] ) ;
        marker.position={lat:coordinates['lat'], lng:coordinates['lng']};
        map.panTo( latlng );

        document.getElementById('latitude').value = coordinates['lat'];
        document.getElementById('longitude').value = coordinates['lng'];

        geocoder.geocode({ 'latLng': latlng }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[1]) {
                    document.getElementById('address').value = results[1].formatted_address;
                    console.log(results[1].formatted_address);
                }
            }
        });
    });

    const input = document.getElementById("pac-input");

    const searchBox = new google.maps.places.SearchBox(input);

    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
    map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
    });
    let markers = [];
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];

        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                console.log("Returned place contains no geometry");
                return;
            }
            var mrkr = new AdvancedMarkerElement({
                map,
                title: place.name,
                position: place.geometry.location,
            });

            google.maps.event.addListener(mrkr, "click", function (event) {
                document.getElementById('latitude').value = this.position.lat();
                document.getElementById('longitude').value = this.position.lng();

            });

            markers.push(mrkr);

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
    } catch (error) {
        console.error('Error initializing Google Maps autocomplete:', error);
    }
}

async function billingMap() {
    // Check if Google Maps API is loaded
    if (typeof google === 'undefined' || !google.maps) {
        console.warn('Google Maps API not loaded. Skipping billing map initialization.');
        return;
    }
    
    try {
    let myLatLng = {
        lat: $('#shippingaddress-storage').data('latitude'),
        lng: $('#shippingaddress-storage').data('longitude'),
     };
    const { Map } = await google.maps.importLibrary("maps");
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
    const map = new google.maps.Map(document.getElementById("billing_location_map_canvas"), {
        center: {
            lat: $('#shippingaddress-storage').data('latitude'),
            lng: $('#shippingaddress-storage').data('longitude'),
        },
        zoom: 13,
        mapId: "roadmap",
    });

    let marker = new AdvancedMarkerElement({
        map,
        position: myLatLng,
    });

    marker.setMap( map );
    var geocoder = geocoder = new google.maps.Geocoder();
    google.maps.event.addListener(map, 'click', function (mapsMouseEvent) {
        var coordinate = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
        var coordinates = JSON.parse(coordinate);
        var latlng = new google.maps.LatLng( coordinates['lat'], coordinates['lng'] ) ;
        marker.position={lat:coordinates['lat'], lng:coordinates['lng']};
        map.panTo( latlng );

        document.getElementById('billing_latitude').value = coordinates['lat'];
        document.getElementById('billing_longitude').value = coordinates['lng'];

        geocoder.geocode({ 'latLng': latlng }, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[1]) {
                    document.getElementById('billing_address').value = results[1].formatted_address;
                    console.log(results[1].formatted_address);
                }
            }
        });
    });

    const input = document.getElementById("pac-input-billing");

    const searchBox = new google.maps.places.SearchBox(input);

    map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
    map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
    });
    let markers = [];
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];

        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                console.log("Returned place contains no geometry");
                return;
            }
            var mrkr = new AdvancedMarkerElement({
                map,
                title: place.name,
                position: place.geometry.location,
            });

            google.maps.event.addListener(mrkr, "click", function (event) {
                document.getElementById('billing_latitude').value = this.position.lat();
                document.getElementById('billing_longitude').value = this.position.lng();

            });

            markers.push(mrkr);

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
    } catch (error) {
        console.error('Error initializing billing map:', error);
    }
}

$(document).on("keydown", "input", function(e) {
    if (e.which==13) e.preventDefault();
});

function mapsShopping() {
    try {
        initAutocomplete();
    } catch (error) {}
    try {
        billingMap();
    } catch (error) {}
}

$('#proceed_to_next_action').on('click', function(){
    let physical_product = $('#physical_product').val();

    // Sync billing data to shipping form before validation
    if (typeof syncBillingDataToShippingForm === 'function') {
        syncBillingDataToShippingForm();
    } else {
        // Fallback sync function - copy billing data to shipping hidden form
        const mappings = [
            {billing: 'billing_contact_person_name', shipping: 'hidden_contact_person_name'},
            {billing: 'billing_phone_hidden', shipping: 'hidden_phone'},
            {billing: 'billing_contact_email', shipping: 'hidden_email'},
            {billing: 'billing_country', shipping: 'hidden_country'},
            {billing: 'billing_city', shipping: 'hidden_city'},
            {billing: 'billing_zip', shipping: 'hidden_zip'},
            {billing: 'billing_address_type', shipping: 'hidden_address_type'},
            {billing: 'billing_address', shipping: 'hidden_address'},
            {billing: 'billing_method_id', shipping: 'hidden_shipping_method_id'},
            {billing: 'billing_latitude', shipping: 'hidden_latitude'},
            {billing: 'billing_longitude', shipping: 'hidden_longitude'}
        ];
        
        mappings.forEach(mapping => {
            const billingElement = document.getElementById(mapping.billing);
            const shippingElement = document.getElementById(mapping.shipping);
            
            if (billingElement && shippingElement) {
                shippingElement.value = billingElement.value || '';
            }
        });
        
        const saveBillingCheckbox = document.getElementById('save_address_billing');
        const hiddenSaveAddress = document.getElementById('hidden_save_address');
        if (saveBillingCheckbox && hiddenSaveAddress) {
            hiddenSaveAddress.value = saveBillingCheckbox.checked ? 'on' : '';
        }
    }

    if(physical_product === 'yes') {
        var billing_addresss_same_shipping = $('#same_as_shipping_address').is(":checked");

        let allAreFilled = true;
        // Use address-form (hidden) for shipping validation
        let shippingForm = document.getElementById("address-form");
        if (shippingForm) {
            shippingForm.querySelectorAll("[required]").forEach(function (i) {
                if (!allAreFilled) return;
                if (!i.value) allAreFilled = false;
                if (i.type === "radio") {
                    let radioValueCheck = false;
                    shippingForm.querySelectorAll(`[name=${i.name}]`).forEach(function (r) {
                        if (r.checked) radioValueCheck = true;
                    });
                    allAreFilled = radioValueCheck;
                }
            });
        }

        let allAreFilled_shipping = true;

        if (billing_addresss_same_shipping != true && $('#billing_input_enable').val() == 1) {
            // Use billing-address-form for billing validation
            let billingForm = document.getElementById("billing-address-form");
            if (billingForm) {
                billingForm.querySelectorAll("[required]").forEach(function (i) {
                    // Skip billing phone validation - it's optional now
                    if (i.name === 'billing_phone' || i.id === 'billing_phone' || i.id === 'billing_phone_hidden') {
                        return;
                    }
                    if (!allAreFilled_shipping) return;
                    if (!i.value) allAreFilled_shipping = false;
                    if (i.type === "radio") {
                        let radioValueCheck = false;
                        billingForm.querySelectorAll(`[name=${i.name}]`).forEach(function (r) {
                            if (r.checked) radioValueCheck = true;
                        });
                        allAreFilled_shipping = radioValueCheck;
                    }
                });
            }
        }
    }else {
        var billing_addresss_same_shipping = false;
    }

    let redirect_url = $(this).data('checkoutpayment');
    let form_url = $(this).data('gotocheckout');

    let isCheckCreateAccount = $('#is_check_create_account');
    let customerPassword = $('#customer_password');
    let customerConfirmPassword = $('#customer_confirm_password');

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") || $('meta[name="_token"]').attr("content"),
        },
    });
    
    // Auto-fill default values before submitting
    if (document.getElementById('billing_country')) {
        document.getElementById('billing_country').value = document.getElementById('billing_country').value || 'Saudi Arabia';
    }
    if (document.getElementById('billing_zip')) {
        document.getElementById('billing_zip').value = document.getElementById('billing_zip').value || 'permanent';
    }
    if (document.getElementById('billing_address_type')) {
        document.getElementById('billing_address_type').value = document.getElementById('billing_address_type').value || 'permanent';
    }
    
    // Set default city to Jeddah if empty
    if (document.getElementById('billing_city') && !document.getElementById('billing_city').value) {
        document.getElementById('billing_city').value = 'جدة';
    }
    if (document.getElementById('billing_city_search') && !document.getElementById('billing_city_search').value) {
        document.getElementById('billing_city_search').value = 'جدة';
    }
    
    // Ensure required billing fields have values
    if (document.getElementById('billing_contact_person_name') && !document.getElementById('billing_contact_person_name').value) {
        if (document.getElementById('name')) {
            document.getElementById('billing_contact_person_name').value = document.getElementById('name').value || 'Customer';
        } else {
            document.getElementById('billing_contact_person_name').value = 'Customer';
        }
    }
    
    // Set default billing address if empty
    if (document.getElementById('billing_address') && !document.getElementById('billing_address').value) {
        document.getElementById('billing_address').value = 'Jeddah, Saudi Arabia';
    }
    
    // Ensure shipping hidden form has the billing data
    if (document.getElementById('hidden_country')) {
        document.getElementById('hidden_country').value = document.getElementById('billing_country') ? document.getElementById('billing_country').value : 'Saudi Arabia';
    }
    if (document.getElementById('hidden_city')) {
        document.getElementById('hidden_city').value = document.getElementById('billing_city') ? document.getElementById('billing_city').value : 'جدة';
    }
    if (document.getElementById('hidden_zip')) {
        document.getElementById('hidden_zip').value = document.getElementById('billing_zip') ? document.getElementById('billing_zip').value : 'permanent';
    }
    if (document.getElementById('hidden_address_type')) {
        document.getElementById('hidden_address_type').value = document.getElementById('billing_address_type') ? document.getElementById('billing_address_type').value : 'permanent';
    }
    if (document.getElementById('hidden_contact_person_name')) {
        document.getElementById('hidden_contact_person_name').value = document.getElementById('billing_contact_person_name') ? document.getElementById('billing_contact_person_name').value : 'Customer';
    }
    if (document.getElementById('hidden_address')) {
        document.getElementById('hidden_address').value = document.getElementById('billing_address') ? document.getElementById('billing_address').value : 'Jeddah, Saudi Arabia';
    }
    
    // Debug: Validate required fields before sending
    const requiredBillingFields = [
        'billing_contact_person_name',
        'billing_address_type', 
        'billing_address',
        'billing_city',
        'billing_zip',
        'billing_country'
    ];
    
    const missingFields = [];
    requiredBillingFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field || !field.value || field.value.trim() === '') {
            missingFields.push(fieldId);
        }
    });
    
    if (missingFields.length > 0) {
        console.warn('⚠️ Missing required billing fields:', missingFields);
    }
    
    // Debug: Log the form data being sent
    const requestData = {
        _token: $('meta[name="csrf-token"]').attr("content") || $('meta[name="_token"]').attr("content"),
        physical_product: physical_product,
        shipping: physical_product === 'yes' ? $('#address-form').serialize() : null,
        billing: $('#billing-address-form').serialize(),
        billing_addresss_same_shipping: billing_addresss_same_shipping,
        is_check_create_account: isCheckCreateAccount && isCheckCreateAccount.prop("checked") ? 1 : 0,
        customer_password: customerPassword ? customerPassword.val() : null,
        customer_confirm_password: customerConfirmPassword ? customerConfirmPassword.val() : null,
    };
    
    console.log('🔍 Sending request to:', form_url);
    console.log('🔍 Request data:', requestData);
    console.log('🔍 CSRF Token:', requestData._token ? requestData._token.substring(0, 10) + '...' : 'MISSING');
    console.log('🔍 Billing form data:', requestData.billing);
    console.log('🔍 Shipping form data:', requestData.shipping);
    
    $.post({
        url: form_url,
        data: requestData,

        beforeSend: function () {
            $('#loading').addClass('d-grid');
        },
        success: function (data) {
            console.log(data)
            if (data.errors) {
                for (var i = 0; i < data.errors.length; i++) {
                    toastr.error(data.errors[i].message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            } else {
                location.href = redirect_url;
            }
        },
        complete: function () {
            $('#loading').removeClass('d-grid');
        },
        error: function (xhr, status, error) {
            console.error('AJAX Error Details:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                responseJSON: xhr.responseJSON,
                url: form_url,
                requestHeaders: xhr.getAllResponseHeaders()
            });
            
            // Try to parse response for more details
            let responseData = null;
            try {
                responseData = JSON.parse(xhr.responseText);
                console.log('Parsed error response:', responseData);
            } catch (e) {
                console.log('Could not parse error response as JSON');
            }
            
            let error_msg;
            if (xhr.status === 403) {
                error_msg = 'Access forbidden. ';
                if (responseData && responseData.message) {
                    error_msg += responseData.message;
                } else {
                    error_msg += 'This could be due to: session expiry, missing authentication, or CSRF token issues.';
                }
            } else if (xhr.status === 405) {
                error_msg = 'Method not allowed. There may be a routing issue.';
            } else if (xhr.status === 419) {
                error_msg = 'Page expired. Please refresh the page and try again.';
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                error_msg = xhr.responseJSON.errors;
            } else if (responseData && responseData.message) {
                error_msg = responseData.message;
            } else {
                error_msg = `Error ${xhr.status}: ${xhr.statusText || error}`;
            }
            
            toastr.error(error_msg, {
                CloseButton: true,
                ProgressBar: true
            });
        }
    });
});

$('#is_check_create_account').on('change', function() {
    if($(this).is(':checked')) {
        $('.is_check_create_account_password_group').fadeIn();
    } else {
        $('.is_check_create_account_password_group').fadeOut();
    }
});
