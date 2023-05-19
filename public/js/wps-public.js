// Google map

jQuery(document).ready(function () {

    initmap();


    function initmap() {
        var directionsService = new google.maps.DirectionsService();
        var directionsDisplay = new google.maps.DirectionsRenderer();
        var map = new google.maps.Map(jQuery('#map')['0'], {
            zoom: 15,
            center: { lat: 0, lng: 0 }
        });
        var mapvalue = jQuery(document).find("#wps_tofw_google_distance_map").val();
        console.log(mapvalue);
        var mapdata = jQuery.parseJSON(mapvalue);
        directionsDisplay.setMap(map);
        calculateAndDisplayRoute(directionsService, directionsDisplay, mapdata);
    }

    function calculateAndDisplayRoute(directionsService, directionsDisplay, mapdata) {
        var waypts = [];
        var checkboxArray = mapdata;
        for (var i = 0; i < checkboxArray.length; i++) {
            waypts.push({
                location: checkboxArray[i],
                stopover: true
            });
        }
        var lats = parseFloat(jQuery(document).find("#start_hidden").val());

        var longs = parseFloat(jQuery(document).find("#end_hidden").val());
   
        var end = jQuery(document).find("#billing_hidden").val();
        directionsService.route({
            origin: { lat: lats, lng: longs },
            destination: end,
            waypoints: waypts,
            optimizeWaypoints: true,
            travelMode: 'DRIVING'
        }, function (response, status) {
            if (status === 'OK') {
                directionsDisplay.setDirections(response);
                var route = response.routes[0];
                var summaryPanel = jQuery('#directions-panel')['0'];
                summaryPanel.innerHTML = '';
            } else {
                window.alert('Directions request failed due to ' + status);
            }
        });



    }



});
