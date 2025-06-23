/**
 * SchedSpot Geolocation Frontend JavaScript
 *
 * @package SchedSpot
 * @version 2.0.0
 */

(function($) {
    'use strict';

    var SchedSpotGeo = {
        map: null,
        geocoder: null,
        userLocation: null,
        markers: [],

        /**
         * Initialize geolocation functionality
         */
        init: function() {
            this.initializeGoogleMaps();
            this.bindEvents();
            this.detectUserLocation();
        },

        /**
         * Initialize Google Maps components
         */
        initializeGoogleMaps: function() {
            if (typeof google !== 'undefined' && google.maps) {
                this.geocoder = new google.maps.Geocoder();
                this.initializeMaps();
            }
        },

        /**
         * Initialize maps on the page
         */
        initializeMaps: function() {
            // Initialize booking form map
            var bookingMapElement = document.getElementById('schedspot-booking-map');
            if (bookingMapElement) {
                this.initializeBookingMap(bookingMapElement);
            }

            // Initialize service list map
            var serviceMapElement = document.getElementById('schedspot-service-map');
            if (serviceMapElement) {
                this.initializeServiceMap(serviceMapElement);
            }
        },

        /**
         * Initialize booking form map
         */
        initializeBookingMap: function(mapElement) {
            var defaultCenter = { lat: 40.7128, lng: -74.0060 }; // New York City default
            
            this.map = new google.maps.Map(mapElement, {
                zoom: 12,
                center: defaultCenter,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });

            // Add click listener for location selection
            this.map.addListener('click', (event) => {
                this.selectLocation(event.latLng);
            });

            // Initialize autocomplete for address input
            var addressInput = document.getElementById('schedspot-client-address');
            if (addressInput) {
                this.initializeAutocomplete(addressInput);
            }
        },

        /**
         * Initialize service list map
         */
        initializeServiceMap: function(mapElement) {
            var defaultCenter = { lat: 40.7128, lng: -74.0060 };
            
            this.map = new google.maps.Map(mapElement, {
                zoom: 10,
                center: defaultCenter,
                mapTypeControl: false,
                streetViewControl: false
            });

            this.loadNearbyWorkers();
        },

        /**
         * Initialize address autocomplete
         */
        initializeAutocomplete: function(input) {
            var autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['address']
            });

            autocomplete.addListener('place_changed', () => {
                var place = autocomplete.getPlace();
                if (place.geometry) {
                    this.selectLocation(place.geometry.location);
                    this.map.setCenter(place.geometry.location);
                    this.map.setZoom(15);
                }
            });
        },

        /**
         * Select a location on the map
         */
        selectLocation: function(latLng) {
            // Clear existing markers
            this.clearMarkers();

            // Add new marker
            var marker = new google.maps.Marker({
                position: latLng,
                map: this.map,
                title: schedspot_geo.strings.selected_location || 'Selected Location',
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#e74c3c"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(24, 24)
                }
            });

            this.markers.push(marker);

            // Update form fields
            this.updateLocationFields(latLng.lat(), latLng.lng());

            // Reverse geocode to get address
            this.reverseGeocode(latLng);

            // Check for nearby workers
            this.checkNearbyWorkers(latLng.lat(), latLng.lng());
        },

        /**
         * Update location form fields
         */
        updateLocationFields: function(lat, lng) {
            var latField = document.getElementById('schedspot-client-lat');
            var lngField = document.getElementById('schedspot-client-lng');

            if (latField) latField.value = lat;
            if (lngField) lngField.value = lng;

            // Trigger change event for form validation
            $(latField).trigger('change');
            $(lngField).trigger('change');
        },

        /**
         * Reverse geocode coordinates to address
         */
        reverseGeocode: function(latLng) {
            if (!this.geocoder) return;

            this.geocoder.geocode({ location: latLng }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    var addressField = document.getElementById('schedspot-client-address');
                    if (addressField) {
                        addressField.value = results[0].formatted_address;
                        $(addressField).trigger('change');
                    }
                }
            });
        },

        /**
         * Check for nearby workers
         */
        checkNearbyWorkers: function(lat, lng) {
            var serviceId = $('#schedspot-service-id').val();
            
            $.post(schedspot_geo.ajax_url, {
                action: 'schedspot_get_nearby_workers',
                lat: lat,
                lng: lng,
                service_id: serviceId || '',
                nonce: schedspot_geo.nonce
            }, (response) => {
                if (response.success) {
                    this.displayNearbyWorkers(response.data.workers);
                }
            });
        },

        /**
         * Display nearby workers
         */
        displayNearbyWorkers: function(workers) {
            var container = $('#schedspot-nearby-workers');
            if (!container.length) return;

            container.empty();

            if (workers.length === 0) {
                container.html('<p>' + (schedspot_geo.strings.no_workers_nearby || 'No service providers found in your area.') + '</p>');
                return;
            }

            var html = '<h4>' + (schedspot_geo.strings.nearby_workers || 'Nearby Service Providers') + '</h4>';
            html += '<div class="schedspot-workers-list">';

            workers.forEach((worker) => {
                html += `
                    <div class="schedspot-worker-item" data-worker-id="${worker.id}">
                        <div class="worker-avatar">
                            <img src="${worker.avatar}" alt="${worker.name}">
                        </div>
                        <div class="worker-info">
                            <h5>${worker.name}</h5>
                            <div class="worker-distance">${worker.distance} km away</div>
                            <div class="worker-rating">Rating: ${worker.rating}/5</div>
                            <div class="worker-rate">$${worker.hourly_rate}/hour</div>
                        </div>
                        <div class="worker-actions">
                            <button type="button" class="button select-worker" data-worker-id="${worker.id}">
                                ${schedspot_geo.strings.select_worker || 'Select'}
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.html(html);
        },

        /**
         * Load nearby workers for service map
         */
        loadNearbyWorkers: function() {
            if (!this.userLocation) return;

            $.post(schedspot_geo.ajax_url, {
                action: 'schedspot_get_nearby_workers',
                lat: this.userLocation.lat,
                lng: this.userLocation.lng,
                nonce: schedspot_geo.nonce
            }, (response) => {
                if (response.success) {
                    this.addWorkersToMap(response.data.workers);
                }
            });
        },

        /**
         * Add workers to map as markers
         */
        addWorkersToMap: function(workers) {
            workers.forEach((worker) => {
                // This would require worker coordinates from the backend
                // For now, we'll skip this implementation
            });
        },

        /**
         * Detect user's current location
         */
        detectUserLocation: function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Update map center if available
                        if (this.map) {
                            this.map.setCenter(this.userLocation);
                            this.selectLocation(new google.maps.LatLng(this.userLocation.lat, this.userLocation.lng));
                        }
                    },
                    (error) => {
                        console.log('Geolocation error:', error);
                        // Fallback to IP-based location or default
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 300000 // 5 minutes
                    }
                );
            }
        },

        /**
         * Clear all markers from map
         */
        clearMarkers: function() {
            this.markers.forEach((marker) => {
                marker.setMap(null);
            });
            this.markers = [];
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Get current location button
            $(document).on('click', '.schedspot-get-location', (e) => {
                e.preventDefault();
                this.detectUserLocation();
            });

            // Worker selection
            $(document).on('click', '.select-worker', (e) => {
                e.preventDefault();
                var workerId = $(e.target).data('worker-id');
                this.selectWorker(workerId);
            });

            // Address input change
            $(document).on('change', '#schedspot-client-address', (e) => {
                var address = $(e.target).val();
                if (address) {
                    this.geocodeAddress(address);
                }
            });
        },

        /**
         * Select a worker
         */
        selectWorker: function(workerId) {
            var workerField = document.getElementById('schedspot-worker-id');
            if (workerField) {
                workerField.value = workerId;
                $(workerField).trigger('change');
            }

            // Update UI to show selection
            $('.schedspot-worker-item').removeClass('selected');
            $(`.schedspot-worker-item[data-worker-id="${workerId}"]`).addClass('selected');
        },

        /**
         * Geocode an address
         */
        geocodeAddress: function(address) {
            if (!this.geocoder) return;

            this.geocoder.geocode({ address: address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    var location = results[0].geometry.location;
                    this.selectLocation(location);
                    
                    if (this.map) {
                        this.map.setCenter(location);
                        this.map.setZoom(15);
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SchedSpotGeo.init();
    });

    // Make available globally
    window.SchedSpotGeo = SchedSpotGeo;

})(jQuery);
