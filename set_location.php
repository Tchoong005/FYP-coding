<?php
// set_location.php
session_start(); // 确保 session 已启动

// 根据登录状态包含不同的 header 文件
if (isset($_SESSION['user'])) {
    include 'header_loggedin.php';
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
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
  <!-- Google Maps + Places -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDISzn9S1tfvSkgZm1Zy5GXUUrCkpwy85o&libraries=places"></script>

  <style>
    /* ========== Global & Body ========== */
    * {
      box-sizing: border-box; margin: 0; padding: 0;
    }
    html, body {
      height: 100%;
      font-family: Arial, sans-serif;
      background: url("https://picsum.photos/id/1018/1920/1080") no-repeat center center fixed;
      background-size: cover;
      color: #333;
    }
    /* 半透明容器 */
    .page-wrapper {
      max-width: 1200px;
      margin: 20px auto;
      background: rgba(255,255,255,0.85);
      border-radius: 12px;
      min-height: 80vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* 顶部标题 + 搜索 */
    #topBar {
      padding: 20px;
      border-bottom: 2px solid #ef3c2d;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }
    #topBar h2 {
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    #topBar h2 .fa-location-dot {
      color: #ef3c2d;
    }
    .search-area {
      display: flex; align-items: center;
      position: relative; width: 360px;
    }
    .search-area .icon-left {
      position: absolute;
      left: 8px; top: 8px;
      color: #ef3c2d; font-size: 16px;
    }
    .search-area input {
      width: 100%; font-size: 14px;
      padding: 8px 38px 8px 30px;
      border: 2px solid #ef3c2d;
      border-radius: 20px 0 0 20px;
      outline: none;
    }
    .search-area button {
      border: none; background: #ef3c2d;
      color: #fff; width: 46px;
      font-size: 16px; border-radius: 0 20px 20px 0;
      cursor: pointer;
    }
    .search-area button:hover {
      background: #d32f2f;
    }

    /* 主区: 左店铺 + 右地图 */
    #mainContainer {
      flex: 1; display: flex;
    }
    #storePanel {
      width: 350px;
      max-width: 400px;
      background: #fff;
      border-right: 1px solid #ddd;
      padding: 20px;
      overflow-y: auto;
    }
    #storePanel h3 {
      font-size: 16px;
      margin-bottom: 10px;
      display: flex; 
      align-items: center; gap: 6px;
    }
    .store-card {
      border: 1px solid #eee;
      border-radius: 8px;
      margin-bottom: 10px; padding: 10px;
    }
    .store-card h4 { font-size: 15px; color: #333; margin-bottom: 5px; }
    .store-card p { font-size: 13px; color: #666; margin-bottom: 8px; }
    .store-card button {
      background-color: #ef3c2d; color: #fff;
      border: none; padding: 6px 12px;
      font-size: 13px; border-radius: 16px; cursor: pointer;
    }
    .store-card button:hover {
      background-color: #d32f2f;
    }
    #map {
      flex: 1; min-height: 500px;
    }

    /* KFC-like Modal */
    .kfc-modal-overlay {
      display: none;
      position: fixed; z-index:9999;
      left:0; top:0; width:100%; height:100%;
      background: rgba(0,0,0,0.4);
    }
    .kfc-modal {
      background: #fff; border-radius: 12px;
      max-width: 400px; margin:100px auto 0 auto;
      text-align: center; padding: 20px; position: relative;
    }
    .kfc-modal p {
      font-size: 16px; margin-bottom: 20px;
    }
    .kfc-modal button {
      background-color: #ef3c2d; color: #fff;
      border: none; padding: 10px 20px;
      font-size: 14px; border-radius:20px;
      cursor: pointer;
    }
    .kfc-modal button:hover {
      background-color: #d32f2f;
    }
  </style>

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
      { lat: 0.5, lng: 99.3 },  // approximate SW corner
      { lat: 7.5, lng: 119.3 }  // approximate NE corner
    );

    function initMap() {
      geocoder = new google.maps.Geocoder();

      const defaultCenter = { lat: 3.1390, lng: 101.6869 }; // KL
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

      // Autocomplete with Malaysia restriction
      const input = document.getElementById("locationInput");
      autocomplete = new google.maps.places.Autocomplete(input, {
        componentRestrictions: { country: "my" },
        fields: ["address_components", "geometry"],
        types: ["address"]
      });
      autocomplete.addListener("place_changed", onPlaceChanged);
    }

    // 当用户真的点击了autocomplete建议，或直接按Enter
    function onPlaceChanged() {
      const place = autocomplete.getPlace();
      console.log("DEBUG place =", place);

      if(!place.geometry || !place.geometry.location) {
        // fallback
        doManualGeocode();
        return;
      }
      handlePlaceResult(place.address_components, place.geometry.location);
    }

    // 如果 google autocomplete 没给 geometry 或 user输入random => fallback geocode
    function doManualGeocode() {
      const addr = document.getElementById("locationInput").value.trim();
      if(!addr) {
        showKfcModal("No details found. Please select from suggestions or enter a valid address in Malaysia.");
        return;
      }
      geocoder.geocode({
        address: addr,
        region: "MY",
        bounds: MALAYSIA_BOUNDS, // 限制搜索在大马边界
        // strictBounds: true, // 试图强行禁止边界外匹配
      }, (results, status) => {
        console.log("DEBUG geocoder results=", results, " status=", status);
        if(status==="OK" && results.length>0){
          // 过滤，必须 partial_match=false, 并在大马bounds内, 并 isAddressInMalaysia
          let found = null;
          for(let r of results){
            if(!r.partial_match && MALAYSIA_BOUNDS.contains(r.geometry.location) && isAddressInMalaysia(r.address_components)){
              found = r; 
              break;
            }
          }
          if(found){
            handlePlaceResult(found.address_components, found.geometry.location);
          } else {
            showKfcModal("Sorry, that address might be invalid or outside Malaysia. We currently do not operate across borders.");
          }
        } else {
          showKfcModal("Sorry, that address might be invalid or outside Malaysia. We currently do not operate across borders.");
        }
      });
    }

    // 最终处理地址component + 坐标
    function handlePlaceResult(components, location){
      if(!isAddressInMalaysia(components)){
        showKfcModal("Sorry, that address might be outside Malaysia. We currently do not operate across borders.");
        document.getElementById("locationInput").value = "";
        return;
      }
      const stateName = getStateName(components).toLowerCase();
      if(!stateName.includes("johor")){
        document.getElementById("storeList").innerHTML =
          "<p>Currently we only operate in Johor. No store is available in your region.</p>";
        map.setCenter(location);
        map.setZoom(13);
        mainMarker.setPosition(location);
        return;
      }
      // if Johor => show store
      map.setCenter(location);
      map.setZoom(13);
      mainMarker.setPosition(location);
      renderJohorStores();
    }

    function isAddressInMalaysia(components){
      if(!components) return false;
      for(let c of components){
        if(c.types.includes("country")){
          const sn = (c.short_name||"").trim().toLowerCase();
          const ln = (c.long_name||"").trim().toLowerCase();
          if(sn==="my" || ln==="malaysia"){
            return true;
          }
        }
      }
      return false;
    }
    function getStateName(components){
      for(let c of components){
        if(c.types.includes("administrative_area_level_1")){
          return c.long_name || c.short_name || "";
        }
      }
      return "";
    }
    function renderJohorStores(){
      const container = document.getElementById("storeList");
      container.innerHTML = "";
      johorStores.forEach(st => {
        const div = document.createElement("div");
        div.className = "store-card";
        div.innerHTML=`
          <h4>${st.name}</h4>
          <p>${st.address}</p>
          <button onclick="selectStore(${st.id})"><i class="fa fa-check"></i> Select Store</button>
        `;
        container.appendChild(div);
      });
    }
    function selectStore(storeId){
      const store = johorStores.find(s=>s.id===storeId);
      if(store){
        showKfcModal("You have selected: "+store.name);
      }
    }

    // KFC-like Modal
    function showKfcModal(msg){
      const overlay = document.getElementById("kfcModalOverlay");
      const modalBox= document.getElementById("kfcModalBox");
      overlay.style.display="block";
      modalBox.querySelector("p").textContent=msg;
    }
    function closeKfcModal(){
      document.getElementById("kfcModalOverlay").style.display="none";
    }

    // DOM load
    document.addEventListener("DOMContentLoaded",()=>{
      initMap();
      const input=document.getElementById("locationInput");
      input.addEventListener("keydown",(e)=>{
        if(e.key==="Enter"){
          e.preventDefault();
          google.maps.event.trigger(autocomplete,"place_changed");
        }
      });
    });
  </script>
</head>
<body>
  <div class="page-wrapper">
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
