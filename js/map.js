function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById("lat").value = pos.coords.latitude;
            document.getElementById("lng").value = pos.coords.longitude;
            alert("📍 Ubicación guardada: " + pos.coords.latitude + ", " + pos.coords.longitude);
        });
    } else {
        alert("❌ Geolocalización no soportada");
    }
}
