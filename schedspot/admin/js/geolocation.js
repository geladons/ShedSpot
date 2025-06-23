/**
 * SchedSpot Geolocation Admin JavaScript
 *
 * @package SchedSpot
 * @version 2.0.0
 */

(function($) {
    'use strict';

    var SchedSpotAdminGeo = {
        map: null,
        drawingManager: null,
        serviceAreas: [],
        currentArea: null,

        /**
         * Initialize admin geolocation functionality
         */
        init: function() {
            this.initializeGoogleMaps();
            this.bindEvents();
            this.loadExistingAreas();
        },

        /**
         * Initialize Google Maps components
         */
        initializeGoogleMaps: function() {
            if (typeof google !== 'undefined' && google.maps) {
                this.initializeMap();
                this.initializeDrawingManager();
            }
        },

        /**
         * Initialize the map
         */
        initializeMap: function() {
            var mapElement = document.getElementById('schedspot-service-area-map');
            if (!mapElement) return;

            var defaultCenter = { lat: 40.7128, lng: -74.0060 }; // New York City default
            
            this.map = new google.maps.Map(mapElement, {
                zoom: 10,
                center: defaultCenter,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });
        },

        /**
         * Initialize drawing manager for service areas
         */
        initializeDrawingManager: function() {
            if (!this.map) return;

            this.drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        google.maps.drawing.OverlayType.CIRCLE,
                        google.maps.drawing.OverlayType.POLYGON
                    ]
                },
                circleOptions: {
                    fillColor: '#0073aa',
                    fillOpacity: 0.3,
                    strokeWeight: 2,
                    strokeColor: '#0073aa',
                    clickable: true,
                    editable: true,
                    zIndex: 1
                },
                polygonOptions: {
                    fillColor: '#0073aa',
                    fillOpacity: 0.3,
                    strokeWeight: 2,
                    strokeColor: '#0073aa',
                    clickable: true,
                    editable: true,
                    zIndex: 1
                }
            });

            this.drawingManager.setMap(this.map);

            // Listen for overlay completion
            google.maps.event.addListener(this.drawingManager, 'overlaycomplete', (event) => {
                this.handleOverlayComplete(event);
            });
        },

        /**
         * Handle overlay completion
         */
        handleOverlayComplete: function(event) {
            var overlay = event.overlay;
            var type = event.type;

            // Disable drawing mode
            this.drawingManager.setDrawingMode(null);

            // Create service area object
            var serviceArea = {
                id: 'area_' + Date.now(),
                type: type === google.maps.drawing.OverlayType.CIRCLE ? 'radius' : 'polygon',
                overlay: overlay,
                name: ''
            };

            if (type === google.maps.drawing.OverlayType.CIRCLE) {
                serviceArea.center = {
                    lat: overlay.getCenter().lat(),
                    lng: overlay.getCenter().lng()
                };
                serviceArea.radius = overlay.getRadius() / 1000; // Convert to kilometers
            } else if (type === google.maps.drawing.OverlayType.POLYGON) {
                serviceArea.coordinates = [];
                var path = overlay.getPath();
                for (var i = 0; i < path.getLength(); i++) {
                    var point = path.getAt(i);
                    serviceArea.coordinates.push({
                        lat: point.lat(),
                        lng: point.lng()
                    });
                }
            }

            // Add click listener for editing
            google.maps.event.addListener(overlay, 'click', () => {
                this.editServiceArea(serviceArea);
            });

            // Add to service areas array
            this.serviceAreas.push(serviceArea);

            // Show edit dialog
            this.showEditDialog(serviceArea);
        },

        /**
         * Show edit dialog for service area
         */
        showEditDialog: function(serviceArea) {
            var name = prompt(schedspot_admin_geo.strings.draw_service_area || 'Enter a name for this service area:', serviceArea.name || '');
            
            if (name !== null) {
                serviceArea.name = name;
                this.updateServiceAreasList();
            } else {
                // User cancelled, remove the area
                this.removeServiceArea(serviceArea);
            }
        },

        /**
         * Edit service area
         */
        editServiceArea: function(serviceArea) {
            this.currentArea = serviceArea;
            this.showEditDialog(serviceArea);
        },

        /**
         * Remove service area
         */
        removeServiceArea: function(serviceArea) {
            // Remove from map
            if (serviceArea.overlay) {
                serviceArea.overlay.setMap(null);
            }

            // Remove from array
            var index = this.serviceAreas.indexOf(serviceArea);
            if (index > -1) {
                this.serviceAreas.splice(index, 1);
            }

            this.updateServiceAreasList();
        },

        /**
         * Update service areas list in UI
         */
        updateServiceAreasList: function() {
            var container = $('#schedspot-service-areas-list');
            if (!container.length) return;

            container.empty();

            if (this.serviceAreas.length === 0) {
                container.html('<p>' + (schedspot_admin_geo.strings.no_areas || 'No service areas defined. Use the drawing tools above to create service areas.') + '</p>');
                return;
            }

            var html = '<h4>' + (schedspot_admin_geo.strings.service_areas || 'Service Areas') + '</h4>';
            html += '<div class="schedspot-areas-list">';

            this.serviceAreas.forEach((area, index) => {
                var typeLabel = area.type === 'radius' ? 'Circle' : 'Polygon';
                var details = '';
                
                if (area.type === 'radius') {
                    details = `Radius: ${area.radius.toFixed(1)} km`;
                } else {
                    details = `${area.coordinates.length} points`;
                }

                html += `
                    <div class="schedspot-area-item" data-area-id="${area.id}">
                        <div class="area-info">
                            <h5>${area.name || 'Unnamed Area'}</h5>
                            <div class="area-details">${typeLabel} - ${details}</div>
                        </div>
                        <div class="area-actions">
                            <button type="button" class="button button-small edit-area" data-area-index="${index}">
                                ${schedspot_admin_geo.strings.edit || 'Edit'}
                            </button>
                            <button type="button" class="button button-small delete-area" data-area-index="${index}">
                                ${schedspot_admin_geo.strings.delete_area || 'Delete'}
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += `<button type="button" class="button button-primary save-areas" style="margin-top: 15px;">
                        ${schedspot_admin_geo.strings.save_area || 'Save Service Areas'}
                     </button>`;

            container.html(html);
        },

        /**
         * Save service areas
         */
        saveServiceAreas: function() {
            var areasData = this.serviceAreas.map((area) => {
                var data = {
                    type: area.type,
                    name: area.name
                };

                if (area.type === 'radius') {
                    data.center = area.center;
                    data.radius = area.radius;
                } else {
                    data.coordinates = area.coordinates;
                }

                return data;
            });

            $.post(schedspot_admin_geo.ajax_url, {
                action: 'schedspot_save_service_area',
                service_areas: JSON.stringify(areasData),
                nonce: schedspot_admin_geo.nonce
            }, (response) => {
                if (response.success) {
                    this.showNotice('success', response.data.message || schedspot_admin_geo.strings.area_saved);
                } else {
                    this.showNotice('error', response.data.message || schedspot_admin_geo.strings.save_failed);
                }
            });
        },

        /**
         * Load existing service areas
         */
        loadExistingAreas: function() {
            // This would load existing areas from user meta
            // For now, we'll skip this implementation
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = `<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`;
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $('.notice.is-dismissible').fadeOut();
            }, 5000);
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Edit area button
            $(document).on('click', '.edit-area', (e) => {
                e.preventDefault();
                var index = $(e.target).data('area-index');
                if (this.serviceAreas[index]) {
                    this.editServiceArea(this.serviceAreas[index]);
                }
            });

            // Delete area button
            $(document).on('click', '.delete-area', (e) => {
                e.preventDefault();
                var index = $(e.target).data('area-index');
                if (this.serviceAreas[index] && confirm('Are you sure you want to delete this service area?')) {
                    this.removeServiceArea(this.serviceAreas[index]);
                }
            });

            // Save areas button
            $(document).on('click', '.save-areas', (e) => {
                e.preventDefault();
                this.saveServiceAreas();
            });

            // Clear all areas button
            $(document).on('click', '.clear-all-areas', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to clear all service areas?')) {
                    this.clearAllAreas();
                }
            });
        },

        /**
         * Clear all service areas
         */
        clearAllAreas: function() {
            this.serviceAreas.forEach((area) => {
                if (area.overlay) {
                    area.overlay.setMap(null);
                }
            });

            this.serviceAreas = [];
            this.updateServiceAreasList();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SchedSpotAdminGeo.init();
    });

    // Make available globally
    window.SchedSpotAdminGeo = SchedSpotAdminGeo;

})(jQuery);
