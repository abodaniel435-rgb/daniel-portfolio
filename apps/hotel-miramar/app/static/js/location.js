/* Hotel Miramar SG - Location distance feature.
   Auto-requests location on page load. Falls back to IP location if denied.
   Privacy: precise location stays in browser. IP fallback uses ipapi.co city-level.
 */

(function () {
  function init() {
    var mapEl = document.getElementById('hotelMap');
    if (!mapEl || typeof L === 'undefined') return;
    var hotelLat = window.HOTEL_LAT || 1.2834;
    var hotelLng = window.HOTEL_LNG || 103.8607;

    var map = L.map('hotelMap').setView([hotelLat, hotelLng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    L.marker([hotelLat, hotelLng]).addTo(map)
      .bindPopup('<strong>Hotel Miramar SG</strong><br>18 Marina Boulevard, Singapore').openPopup();

    var userMarker = null;
    var line = null;

    function haversineKm(lat1, lon1, lat2, lon2) {
      var R = 6371;
      var toRad = function (d) { return d * Math.PI / 180; };
      var dLat = toRad(lat2 - lat1);
      var dLon = toRad(lon2 - lon1);
      var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      return 2 * R * Math.asin(Math.sqrt(a));
    }

    function formatDistance(km) {
      if (km < 1) return Math.round(km * 1000) + ' m';
      if (km < 100) return km.toFixed(1) + ' km';
      return Math.round(km) + ' km';
    }

    function showDistance(userLat, userLng, label) {
      var km = haversineKm(userLat, userLng, hotelLat, hotelLng);
      var miles = km * 0.621371;
      var val = document.getElementById('distanceValue');
      var extra = document.getElementById('distanceExtra');
      var text = document.getElementById('distanceText');
      if (!val || !extra || !text) return;
      val.style.display = 'block';
      extra.style.display = 'block';
      val.textContent = '≈ ' + formatDistance(km) + ' away (' + miles.toFixed(1) + ' miles)';
      var driveMin = Math.max(3, Math.round(km / 30 * 60));
      extra.textContent = 'Straight-line distance. Rough drive ≈ ' + driveMin + ' min depending on traffic.';
      text.textContent = label || ('You are here: ' + userLat.toFixed(4) + ', ' + userLng.toFixed(4));

      if (userMarker) map.removeLayer(userMarker);
      if (line) map.removeLayer(line);
      userMarker = L.marker([userLat, userLng]).addTo(map).bindPopup('You are here').openPopup();
      line = L.polyline([[userLat, userLng], [hotelLat, hotelLng]], { dashArray: '6 6' }).addTo(map);
      map.fitBounds(L.latLngBounds([[userLat, userLng], [hotelLat, hotelLng]]).pad(0.25));
    }

    function showHelp(msg) {
      var help = document.getElementById('distanceHelp');
      var manual = document.getElementById('manualBox');
      if (help) { help.style.display = 'block'; help.textContent = msg; }
      if (manual) manual.style.display = 'block';
    }

    function setLabel(text) {
      var el = document.getElementById('distanceText');
      if (el) el.textContent = text;
    }

    function locate(btn, auto) {
      if (!('geolocation' in navigator)) {
        setLabel('Geolocation is not supported by your browser.');
        showHelp('Use Show My Distance Instantly below, pick your area, or click the map.');
        return;
      }
      if (btn) { btn.disabled = true; btn.textContent = 'Locating…'; }
      if (auto) setLabel('Finding your precise location… please Allow when your browser asks.');
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          if (btn) { btn.disabled = false; btn.textContent = 'Update My Distance'; }
          showDistance(pos.coords.latitude, pos.coords.longitude, 'Precise location: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4));
        },
        function (err) {
          if (btn) { btn.disabled = false; btn.textContent = 'Enable Precise Location'; }
          var val = document.getElementById('distanceValue');
          var hasDistance = val && val.style.display !== 'none';
          if (hasDistance) {
            // Keep the instant approximate distance on screen; precise is optional upgrade.
            return;
          }
          if (err && err.code === 1) {
            setLabel('Precise location is blocked. Showing approximate distance below - tap Enable Precise Location and choose Allow for exact.');
          } else if (err && err.code === 2) {
            setLabel('Could not fix GPS position. Showing approximate distance below.');
          } else {
            setLabel('Precise location timed out. Showing approximate distance below.');
          }
          showHelp('Pick your area below or click anywhere on the map for distance. No permission needed.');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
      );
    }

    function parseIPResult(d) {
      if (!d) return null;
      var lat = d.latitude !== undefined ? d.latitude : (d.lat !== undefined ? d.lat : null);
      var lng = d.longitude !== undefined ? d.longitude : (d.lon !== undefined ? d.lon : null);
      if (lat === null || lng === null) return null;
      lat = Number(lat); lng = Number(lng);
      if (isNaN(lat) || isNaN(lng)) return null;
      var city = d.city || '';
      var country = d.country_name || d.country || '';
      return { lat: lat, lng: lng, city: city, country: country };
    }

    function locateByIP(btn, silent) {
      if (btn) { btn.disabled = true; btn.textContent = 'Finding…'; }
      if (!silent) setLabel('Using your approximate location…');
      var providers = ['https://ipwho.is/', 'https://ipapi.co/json/'];
      var i = 0;
      function tryNext() {
        if (i >= providers.length) {
          if (btn) { btn.disabled = false; btn.textContent = 'Show My Distance Instantly'; }
          setLabel('Could not auto-detect location. Pick your area below or click the map.');
          showHelp('Select your area to see distance instantly.');
          return;
        }
        var url = providers[i++];
        fetch(url).then(function (r) { return r.json(); }).then(function (d) {
          if (d && d.success === false) throw new Error('blocked');
          var p = parseIPResult(d);
          if (!p) throw new Error('bad');
          if (btn) { btn.disabled = false; btn.textContent = 'Show My Distance Instantly'; }
          var label = 'Approximate location' + (p.city ? ': ' + p.city + (p.country ? ', ' + p.country : '') : '') + ' (' + p.lat.toFixed(2) + ', ' + p.lng.toFixed(2) + ')';
          showDistance(p.lat, p.lng, label);
        }).catch(function () { tryNext(); });
      }
      tryNext();
    }

    var btn = document.getElementById('locateBtn');
    if (btn) btn.addEventListener('click', function () { locate(btn, false); });
    var ipBtn = document.getElementById('ipBtn');
    if (ipBtn) ipBtn.addEventListener('click', function () { locateByIP(ipBtn, false); });

    // Manual area picker + click-on-map (no permission needed, always works)
    var manual = document.getElementById('manualBox');
    if (manual) manual.style.display = 'block';
    var areaSelect = document.getElementById('areaSelect');
    if (areaSelect) areaSelect.addEventListener('change', function () {
      var v = areaSelect.value;
      if (!v) return;
      var parts = v.split(',');
      var lat = Number(parts[0]); var lng = Number(parts[1]);
      var name = areaSelect.options[areaSelect.selectedIndex].text;
      showDistance(lat, lng, 'Selected area: ' + name);
    });
    map.on('click', function (e) {
      showDistance(e.latlng.lat, e.latlng.lng, 'Custom point: ' + e.latlng.lat.toFixed(4) + ', ' + e.latlng.lng.toFixed(4) + ' (click hotel marker for hotel)');
    });

    // AUTOMATIC: 1) instant approximate distance (no permission), 2) upgrade to precise if allowed.
    setLabel('Finding your location automatically…');
    setTimeout(function () { locateByIP(ipBtn, true); }, 400);
    setTimeout(function () { locate(btn, true); }, 1200);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
