<!DOCTYPE html>
<html>

<head>
    <title>Realtime Driver Tracking</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        #map {
            height: 400px;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
        var map = L.map('map').setView([-7.28388, 112.66911], 15); // Centered between start and end markers

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var startMarker = L.marker([-7.281496979895277, 112.65797805166837]).addTo(map);
        var endMarker = L.marker([-7.2842639725678255, 112.68166732145956]).addTo(map);

        // // Create a route polyline between start and end markers
        // var routePolyline = L.polyline([
        //     startMarker.getLatLng(),
        //     endMarker.getLatLng()
        // ]).addTo(map);

        // Initialize Leaflet Routing Machine
        var routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startMarker.getLatLng()),
                L.latLng(endMarker.getLatLng())
            ],
            routeWhileDragging: true
        }).addTo(map);

        // Create a marker for the current position
        var currentMarker = L.marker([0, 0]).addTo(map);

        function updateRoute(routeCoordinates) {
            startMarker.setLatLng(routeCoordinates[0]);
            endMarker.setLatLng(routeCoordinates[routeCoordinates.length - 1]);

            // Update the polyline coordinates
            routePolyline.setLatLngs(routeCoordinates);

            // Focus the map on the last position of the driver
            map.setView(routeCoordinates[routeCoordinates.length - 1], map.getZoom());

            // Update the current position marker
            var currentPosition = routeCoordinates[routeCoordinates.length - 1];
            currentMarker.setLatLng(currentPosition);
        }

        const pusher = new Pusher('2245e684f71b879fa442', {
            cluster: 'ap1'
        });

        const channel = pusher.subscribe('driver-location');
        channel.bind('App\\Events\\DriverLocationUpdated', function(data) {
            var routeCoordinates = JSON.parse(data.routeCoordinates);
            updateRoute(routeCoordinates);
        });

        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        function sendLocation(driver, latitude, longitude, routeCoordinates) {
            $.ajax({
                url: '/update-location',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: {
                    driver: driver,
                    latitude: latitude,
                    longitude: longitude,
                    route_coordinates: JSON.stringify(routeCoordinates)
                },
                success: function(response) {
                    console.log(response.message);
                }
            });
        }

        function sendDriverLocation(driver) {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    console.log("Latitude:", latitude);
                    console.log("Longitude:", longitude);

                    // Update the current position marker
                    var currentPosition = L.latLng(latitude, longitude);
                    currentMarker.setLatLng(currentPosition);

                    var routeCoordinates = [
                        [latitude, longitude],
                        [latitude + 0.01, longitude + 0.01],
                        [latitude + 0.02, longitude + 0.02]
                    ];

                    sendLocation(driver, latitude, longitude, routeCoordinates);
                });
            }
        }

        setInterval(function() {
            sendDriverLocation('Driver 1');
        }, 1000); // Update every 10 seconds
    </script>
</body>

</html>
