// Generated by CoffeeScript 1.7.1
var Geopicker;

Geopicker = (function() {
  function Geopicker() {
    this.scope = $('.coordinates');
    this.map = null;
    this.marker = null;
    $(':input', this.scope).focus((function(_this) {
      return function() {
        var baseMaps, layers;
        if ($('#map', _this.scope).length > 0) {
          return false;
        }
        layers = [];
        layers.osm = L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {
          attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        });
        layers.esri = L.tileLayer("http://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}", {
          attribution: "Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community"
        });
        layers.thunderforest = L.tileLayer("http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png", {
          attribution: '&copy; <a href="http://www.opencyclemap.org">OpenCycleMap</a>, &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
        });
        baseMaps = {
          "OpenStreetMap": layers.osm,
          "ESRI World Imagery": layers.esri,
          "Thunderforest Landscape": layers.thunderforest
        };
        _this.renderMap(layers, baseMaps);
        return _this.addGeoJson('/proxy/geojson');
      };
    })(this));
  }

  Geopicker.prototype.renderMap = function(layers, baseMaps, overlayMaps) {
    var $container, greenIcon, lat, lng, onMapClick, resourcePath;
    if (overlayMaps == null) {
      overlayMaps = {};
    }
    $container = $('<div id="map-container"><button class="close">&times;</button></div>').css({
      width: this.scope.width()
    }).appendTo(this.scope);
    $container.prepend($('<div id="map"/>').css({
      width: this.scope.width()
    })).slideDown();
    $('.close', $container).hide().click(function(e) {
      e.preventDefault();
      return $container.slideUp(null, function() {
        return $(this).remove();
      });
    });
    $('html').click(function() {
      return $('.close', $container).click();
    });
    this.scope.click(function(event) {
      return event.stopPropagation();
    });
    this.map = L.map("map", {
      center: [51.163, 10.448],
      zoom: 6,
      maxZoom: 19,
      layers: layers
    });
    L.control.layers(baseMaps, overlayMaps).addTo(this.map);
    L.control.scale().addTo(this.map);
    this.map.invalidateSize();
    resourcePath = '_Resources/Static/Packages/Subugoe.GermaniaSacra/';
    greenIcon = L.icon({
      iconUrl: resourcePath + 'Images/marker-icon.png',
      shadowUrl: resourcePath + 'Images/marker-shadow.png',
      iconAnchor: [13, 41]
    });
    lat = $('input[name$="breite[]"]').val() || 0;
    lng = $('input[name$="laenge[]"]').val() || 0;
    this.marker = L.marker([lat, lng], {
      icon: greenIcon
    }).addTo(this.map);
    $('.leaflet-control-layers-base input:eq(0)', this.scope).click();
    return this.map.on('click', onMapClick = (function(_this) {
      return function(e) {
        return _this.setCoordinates(e);
      };
    })(this));
  };

  Geopicker.prototype.addGeoJson = function(src) {
    return $.getJSON(src).success((function(_this) {
      return function(data) {
        var borders, style;
        style = {
          clickable: false,
          color: "#000",
          fillColor: "#000",
          weight: 1.5,
          opacity: 0.3,
          fillOpacity: 0.05
        };
        borders = L.geoJson(data, {
          style: style
        });
        return borders.addTo(_this.map);
      };
    })(this));
  };

  Geopicker.prototype.setCoordinates = function(e) {
    var doit, lat, lng;
    lat = e.latlng.lat.toFixed(6);
    lng = e.latlng.lng.toFixed(6);
    doit = confirm("Sollen die Koordinaten " + lat + ", " + lng + " übernommen werden?");
    if (doit === true) {
      $(':input[name$="breite[]"]', this.scope).val(lat);
      $(':input[name$="laenge[]"]', this.scope).val(lng);
      return this.marker.setLatLng(e.latlng);
    }
  };

  return Geopicker;

})();
