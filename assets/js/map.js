// ============================================================
// HPP CaseTrack — Leaflet.js Map Module
// ============================================================

let map = null;
let marker = null;

// Initialize map centered on Indonesia
function initMap(lat = -2.5, lng = 118, zoom = 5) {
  if (document.getElementById('map')) {
    map = L.map('map').setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19
    }).addTo(map);

    // Allow manual pin placement
    map.on('click', function(e) {
      placeMarker(e.latlng.lat, e.latlng.lng);
    });
  }
}

function placeMarker(lat, lng, label = '') {
  if (marker) map.removeLayer(marker);

  const customIcon = L.divIcon({
    html: `<div style="
      width:36px; height:36px;
      background:#800000; border-radius:50% 50% 50% 0;
      transform:rotate(-45deg); border:3px solid #fff;
      box-shadow:0 4px 12px rgba(128,0,0,0.4);
    "></div>`,
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    className: ''
  });

  marker = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(map);

  marker.on('dragend', function(e) {
    const pos = e.target.getLatLng();
    updateCoords(pos.lat, pos.lng);
    reverseGeocode(pos.lat, pos.lng);
  });

  updateCoords(lat, lng);
  reverseGeocode(lat, lng);
  map.setView([lat, lng], 15);
}

function updateCoords(lat, lng) {
  const latIn  = document.getElementById('lat-input');
  const lngIn  = document.getElementById('lng-input');
  const disp   = document.getElementById('coords-display');

  if (latIn)  latIn.value  = lat.toFixed(7);
  if (lngIn)  lngIn.value  = lng.toFixed(7);
  if (disp)   disp.innerHTML = `<span class="material-symbols-outlined text-[14px] text-green-600">location_on</span>
    Koordinat: <strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong>`;
}

async function reverseGeocode(lat, lng) {
  try {
    const res  = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
    const data = await res.json();
    const name = data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;

    const locIn  = document.getElementById('loc-name-input');
    const status = document.getElementById('location-status');
    const disp   = document.getElementById('coords-display');

    if (locIn)  locIn.value = name;
    if (status) status.innerHTML = `<span style="color:#22c55e; font-weight:600">✓ Lokasi terdeteksi:</span> ${name.substring(0, 80)}...`;
    if (disp)   disp.innerHTML   = `<span class="material-symbols-outlined text-[14px]" style="color:#22c55e">location_on</span>
      Koordinat: <strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong> — ${name.substring(0, 60)}...`;

    if (marker) marker.bindPopup(`<div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;max-width:200px">
      <strong style="color:#800000">📍 Lokasi Aktivitas</strong><br/>
      <span style="color:#5a413d">${name.substring(0, 100)}</span>
    </div>`).openPopup();
  } catch(e) {
    console.log('Reverse geocode failed:', e);
  }
}

function getLocation() {
  const btn    = document.getElementById('get-location-btn');
  const status = document.getElementById('location-status');

  if (!navigator.geolocation) {
    if (status) status.innerHTML = '<span style="color:#ef4444">GPS tidak didukung browser ini.</span>';
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span> Mendeteksi...';
  }
  if (status) status.innerHTML = 'Mendeteksi lokasi GPS Anda...';

  navigator.geolocation.getCurrentPosition(
    function(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      placeMarker(lat, lng);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">gps_fixed</span> Perbarui GPS';
        btn.style.borderColor = '#22c55e';
        btn.style.color = '#16a34a';
      }
    },
    function(err) {
      const msgs = {
        1: 'Izin lokasi ditolak. Aktifkan akses lokasi di browser Anda.',
        2: 'Lokasi tidak tersedia.',
        3: 'Timeout deteksi lokasi.'
      };
      if (status) status.innerHTML = `<span style="color:#ef4444">${msgs[err.code] || 'Gagal mendeteksi lokasi.'}</span>`;
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">gps_fixed</span> Coba Lagi';
      }
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  initMap();
});

// ---- Case update markers (for case detail map view) ----
function initCaseMap(updates) {
  if (!document.getElementById('map')) return;

  const jakartaCoords = [-6.2088, 106.8456];
  map = L.map('map').setView(jakartaCoords, 10);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  const bounds = [];

  updates.forEach(function(u, idx) {
    if (!u.lat || !u.lng) return;
    const lat = parseFloat(u.lat);
    const lng = parseFloat(u.lng);

    const customIcon = L.divIcon({
      html: `<div style="
        width:30px; height:30px;
        background:#800000; border-radius:50%;
        border:3px solid #fff; box-shadow:0 2px 8px rgba(128,0,0,0.4);
        display:flex; align-items:center; justify-content:center;
        color:#fff; font-weight:700; font-size:12px;
      ">${idx + 1}</div>`,
      iconSize: [30, 30],
      iconAnchor: [15, 15],
      className: ''
    });

    const m = L.marker([lat, lng], { icon: customIcon }).addTo(map);
    m.bindPopup(`<div style="font-family:'Plus Jakarta Sans';font-size:13px;">
      <strong style="color:#800000">${u.status}</strong><br/>
      ${u.desc}<br/>
      <small style="color:#8e706c">${u.date}</small>
      ${u.location ? `<br/><small>📍 ${u.location}</small>` : ''}
    </div>`);

    bounds.push([lat, lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [30, 30] });
  }
}
