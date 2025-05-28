<?php
include 'config.php'; // 统一启动 session
if (isset($_SESSION['user_id'])) {
    include 'header_login.php';
} else {
    include 'header_guest.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Set Your Location - KFG Food (Johor Only)</title>

  <!-- Font Awesome for icons -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer">

  <!-- Google Maps + Places -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDISzn9S1tfvSkgZm1Zy5GXUUrCkpwy85o&libraries=places"></script>
  <link rel="stylesheet" href="set_location.css">

  <script>
    let map, mainMarker, autocomplete, geocoder;

    // Johor store data
    const johorStores = [
      { id:1, name:"KFG Taman Nusa Bestari", address:"81300 Skudai, Johor, Malaysia", lat:1.4846, lng:103.6633 },
      { id:2, name:"KFG Bukit Indah", address:"81200 Johor Bahru, Johor, Malaysia", lat:1.4723, lng:103.6627 },
      { id:3, name:"KFG City Square JB", address:"80000 Johor Bahru, Johor, Malaysia", lat:1.4622, lng:103.7618 }
    ];

    // 定义马来西亚的大致边界
    const MALAYSIA_BOUNDS = new google.maps.LatLngBounds(
      { lat: 0.5, lng: 99.3 },
      { lat: 7.5, lng: 119.3 }
    );

    function initMap() {
      geocoder = new google.maps.Geocoder();
      const defaultCenter = { lat: 3.1390, lng: 101.6869 };
      map = new google.maps.Map(document.getElementById("map"), {
        center: defaultCenter,
        zoom: 7
      });
      mainMarker = new google.maps.Marker({
        position: defaultCenter,
        map: map,
        draggable: true
      });
      mainMarker.addListener("dragend", () => {
        map.setCenter(mainMarker.getPosition());
      });
      const input = document.getElementById("locationInput");
      autocomplete = new google.maps.places.Autocomplete(input, {
        componentRestrictions: { country: "my" },
        fields: ["address_components", "geometry"],
        types: ["address"]
      });
      autocomplete.addListener("place_changed", onPlaceChanged);
    }

    function onPlaceChanged() {
      const place = autocomplete.getPlace();
      console.log("DEBUG place =", place);
      if (!place.geometry || !place.geometry.location) {
        doManualGeocode();
        return;
      }
      handlePlaceResult(place.address_components, place.geometry.location);
    }

    function doManualGeocode() {
      const addr = document.getElementById("locationInput").value.trim();
      if (!addr) {
        showKfcModal("No details found. Please enter a valid address in Malaysia.");
        return;
      }
      geocoder.geocode({
        address: addr,
        region: "MY",
        bounds: MALAYSIA_BOUNDS,
      }, (results, status) => {
        console.log("DEBUG geocoder results =", results, " status =", status);
        if (status === "OK" && results.length > 0) {
          let found = null;
          for (let r of results) {
            if (!r.partial_match &&
                MALAYSIA_BOUNDS.contains(r.geometry.location) &&
                isAddressInMalaysia(r.address_components)) {
              found = r;
              break;
            }
          }
          if (found) {
            handlePlaceResult(found.address_components, found.geometry.location);
          } else {
            showKfcModal("Sorry, that address might be invalid or outside Malaysia.");
          }
        } else {
          showKfcModal("Sorry, that address might be invalid or outside Malaysia.");
        }
      });
    }

    function handlePlaceResult(components, location) {
      if (!isAddressInMalaysia(components)) {
        showKfcModal("Sorry, that address might be outside Malaysia. We currently do not operate across borders.");
        document.getElementById("locationInput").value = "";
        return;
      }
      const stateName = getStateName(components).toLowerCase();
      if (!stateName.includes("johor")) {
        document.getElementById("storeList").innerHTML =
          "<p>Currently we only operate in Johor. No store is available in your region.</p>";
        map.setCenter(location);
        map.setZoom(13);
        mainMarker.setPosition(location);
        return;
      }
      map.setCenter(location);
      map.setZoom(13);
      mainMarker.setPosition(location);
      renderJohorStores();
    }

    function isAddressInMalaysia(components) {
      if (!components) return false;
      for (const c of components) {
        if (c.types.includes("country")) {
          const sn = (c.short_name || "").trim().toLowerCase();
          const ln = (c.long_name || "").trim().toLowerCase();
          if (sn === "my" || ln === "malaysia") {
            return true;
          }
        }
      }
      return false;
    }
    function getStateName(components) {
      for (const c of components) {
        if (c.types.includes("administrative_area_level_1")) {
          return c.long_name || c.short_name || "";
        }
      }
      return "";
    }
    function renderJohorStores() {
      const container = document.getElementById("storeList");
      container.innerHTML = "";
      johorStores.forEach(st => {
        const div = document.createElement("div");
        div.className = "store-card";
        div.innerHTML = `
          <h4>${st.name}</h4>
          <p>${st.address}</p>
          <button onclick="selectStore(${st.id})"><i class="fa fa-check"></i> Select Store</button>
        `;
        container.appendChild(div);
      });
    }
    function selectStore(storeId) {
      const store = johorStores.find(s => s.id === storeId);
      if (store) {
        showKfcModal("You have selected: " + store.name);
      }
    }
    function showKfcModal(msg) {
      document.getElementById("kfcModalOverlay").style.display = "block";
      document.getElementById("kfcModalBox").querySelector("p").textContent = msg;
    }
    function closeKfcModal() {
      document.getElementById("kfcModalOverlay").style.display = "none";
    }
    document.addEventListener("DOMContentLoaded", () => {
      initMap();
      const input = document.getElementById("locationInput");
      input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          google.maps.event.trigger(autocomplete, "place_changed");
        }
      });
    });
  </script>
</head>
<body>
  <div class="location-container">
    <!-- Top bar -->
    <div id="topBar">
      <h2><i class="fa fa-location-dot"></i> SET YOUR LOCATION</h2>
      <div class="search-area">
        <i class="fa fa-location-dot icon-left"></i>
        <input type="text" id="locationInput" placeholder="Enter your full address" />
        <button onclick="google.maps.event.trigger(autocomplete, 'place_changed')">
          <i class="fa fa-magnifying-glass"></i>
        </button>
      </div>
    </div>
    <!-- Main area: store list + map -->
    <div id="mainContainer">
      <div id="storePanel">
        <h3><i class="fa fa-store"></i> Nearby Stores</h3>
        <div id="storeList">
          <p>Please enter a location in Johor.</p>
        </div>
      </div>
      <div id="map"></div>
    </div>
  </div>
  <!-- KFC-like Modal -->
  <div class="kfc-modal-overlay" id="kfcModalOverlay">
    <div class="kfc-modal" id="kfcModalBox">
      <p>Some message here</p>
      <button onclick="closeKfcModal()">Okay</button>
    </div>
  </div>
</body>
</html>