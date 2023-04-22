/**
 * Main JS
 * 
 * - initialize - Showing MapBox.
 * 
 * @since 1.0.0
 * @author Oleh
 */

window.MapBox = {};
window.$ = jQuery;

/**
 * Init MapBox
 * 
 */
MapBox.initialize = function () {
    mapboxgl.accessToken = $("#map-box-key").val();

    // create a mapbox
    MapBox.map = new mapboxgl.Map({
        container: "log-map",
        style: "mapbox://styles/mapbox/streets-v12",
        center: [13.405, 52.52],
        zoom: 13
    });

    // initial settings
    MapBox.apiURL = $('#api-url').val();
    MapBox.infoPopup = new mapboxgl.Popup({
        closeButton: false,
        closeOnClick: false
    });
    MapBox.tags = [];
    MapBox.userId = $("#user-id").val();
    MapBox.isLoggedIn = MapBox.userId > 0;
    MapBox.markers = [];
    MapBox.searhResults = [];
    MapBox.isPopupOpened = false;
    MapBox.searchList = [];

    MapBox.updateDataByTag();

    $('#only-mine').change(MapBox.updateMarkers);
    $('#search-input').on('input', MapBox.searchByName);
    $(document).click(function () {
        $("#search-list").attr("style", "display: none");
    })
    $('#search-input').click(function (event) {
        event.stopPropagation();

        if (MapBox.searchList.length > 0 && $('#search-input').val() !== '') {
            $("#search-list").attr("style", "display: block");
        }
    })

    MapBox.isLoggedIn && MapBox.map.on('click', MapBox.addMarkerModal);
};

/**
 * Open a modal on click the map
 * 
 * @param event
 */
MapBox.addMarkerModal = function (event) {
    if (!MapBox.isPopupOpened) {
        MapBox.coordinates = event.lngLat;

        // show setting modal
        MapBox.settingPopup = new mapboxgl.Popup({
            closeOnClick: false,
            closeOnMove: true
        })
            .setLngLat(MapBox.coordinates)
            .setHTML($("#marker-setting").html())
            .addTo(MapBox.map);

        MapBox.isPopupOpened = true;

        // save/cancel modal actions
        $(MapBox.settingPopup._content.querySelector(".chosen-select")).chosen();
        $(MapBox.settingPopup._content.querySelector(".btn-success")).click(MapBox.addMarker);
        $(MapBox.settingPopup._content.querySelector(".btn-danger")).click(function () {
            MapBox.settingPopup.remove();
            MapBox.isPopupOpened = false;
        });
        MapBox.isLoggedIn && MapBox.settingPopup.on('close', () => {
            MapBox.isPopupOpened = false;
        });
    }
}

/**
 * Add a marker
 */
MapBox.addMarker = function () {
    // add a marker on the map
    var marker = new mapboxgl.Marker({ color: 'red' });
    marker.setLngLat(MapBox.coordinates).addTo(MapBox.map);
    MapBox.markers.push(marker);
    MapBox.isPopupOpened = false;
    MapBox.settingPopup.remove();

    // save marker
    const markerDiv = marker.getElement();
    MapBox.saveMarkerSettings(markerDiv);
}

/**
 * Save marker settings
 * 
 * @param event
 */
MapBox.saveMarkerSettings = function (markerDiv) {
    let item = {
        user_id: MapBox.userId,
        name: $(MapBox.settingPopup._content.querySelector(".marker-name")).val(),
        tag: $(MapBox.settingPopup._content.querySelector(".marker-tag")).val(),
        new_tag: $(MapBox.settingPopup._content.querySelector(".new-tag")).val(),
        lat: MapBox.settingPopup._lngLat.lat,
        lng: MapBox.settingPopup._lngLat.lng
    }

    $.ajax({
        type: "post",
        url: MapBox.apiURL,
        data: {
            ...item
        },
        success: function (result) {
            MapBox.updateDataByTag(false);
        }
    })

    markerDiv.addEventListener('mouseenter', () => { MapBox.showPopup(item) });
    markerDiv.addEventListener('mouseleave', MapBox.removePopup);
}

/**
 * Get markers filtered by tag
 * 
 */
MapBox.updateDataByTag = function (updateMarkers = true) {
    $.ajax({
        url: MapBox.apiURL,
        data: { tag: MapBox.tags, user_id: MapBox.userId },
        success: function (result) {
            MapBox.myMakers = result.my_markers;
            MapBox.otherMarkers = result.other_markers;

            // update settings modal
            var options = '<option selected>' + result.tags[0].name + '</option>';
            for (let i = 1; i < result.tags.length; i++) {
                options = options + '<option>' + result.tags[i].name + '</option>';
            }

            $('#marker-setting select').html(options);

            var sidebarTags = "";

            // update sidebar
            for (let i = 0; i < result.tags.length; i++) {
                let tag = result.tags[i];
                sidebarTags = sidebarTags + `<div class="tag-item form-check">
                    <input class="form-check-input" type="checkbox" value="" ${MapBox.tags.includes(tag.slug) ? "checked" : ""} id="${result.tags[i].slug}">
                    <label class="form-check-label" for="${tag.slug}" title="${tag.name} ( ${tag.count} )">
                    ${tag.name} ( ${tag.count} )
                    </label>
                </div>`;
            }

            $("#sidebar-tags").html(sidebarTags);

            $('#sidebar-tags .form-check-input').change(function (event) {
                let value = $(this).attr('id');
                if (event.currentTarget.checked) {
                    MapBox.tags.push(value);
                } else {
                    let index = MapBox.tags.indexOf(value);
                    MapBox.tags.splice(index, 1);
                }

                MapBox.updateDataByTag();
            })

            updateMarkers && MapBox.updateMarkers();
        }
    });
}


/**
 * Get markers filtered by name
 * 
 */
MapBox.searchByName = function () {
    let search = $("#search-input").val();
    let isResult = false;

    if (search.length > 0) {
        $.ajax({
            url: MapBox.apiURL,
            data: { user_id: MapBox.userId, search: search },
            success: function (result) {
                let searchList = "";
                MapBox.searchList = [];
                if (result.my_markers.length + result.other_markers.length > 0) isResult = true;

                [...result.my_markers, ...result.other_markers].forEach(item => {
                    MapBox.searchList.push(item);
                    searchList = searchList + `<li lng="${item.lng}" lat="${item.lat}">${item.name}</li>`
                })

                // update search list
                $("#search-list").html(searchList);
                $("#search-list").attr("style", isResult ? "display: block" : "display: none");
                $("#search-list li").click(MapBox.moveTo);
            }
        });
    } else {
        $("#search-list").attr("style", "display: none");
    }
}

/**
 * Update markers on the map
 * 
 */
MapBox.updateMarkers = function () {
    MapBox.markers.forEach(item => item.remove());

    // add marker info popup / add markers on the map
    MapBox.markers = [];
    MapBox.myMakers.map(item => {
        let marker = new mapboxgl.Marker({ color: 'red' }).setLngLat([item.lng, item.lat])
            .addTo(MapBox.map);

        const markerDiv = marker.getElement();
        markerDiv.addEventListener('mouseenter', () => { MapBox.showPopup(item) });
        markerDiv.addEventListener('mouseleave', MapBox.removePopup);

        MapBox.markers.push(marker);
    })

    if (!$('#only-mine')[0].checked) {
        MapBox.otherMarkers.map(item => {
            let marker = new mapboxgl.Marker({ color: 'black' }).setLngLat([item.lng, item.lat])
                .addTo(MapBox.map);

            const markerDiv = marker.getElement();
            markerDiv.addEventListener('mouseenter', () => { MapBox.showPopup(item) });
            markerDiv.addEventListener('mouseleave', MapBox.removePopup);

            MapBox.markers.push(marker);
        })
    }
}

/**
 * Move to certain position
 * 
 */
MapBox.moveTo = function (event) {
    $("#search-input").val($(event.target).html());

    lng = parseFloat($(event.target).attr('lng'));
    lat = parseFloat($(event.target).attr('lat'));
    MapBox.map.flyTo({ center: [lng, lat], essential: true });
}

/**
 * Show popup on hover a marker
 * 
 */
MapBox.showPopup = function (item) {
    $("#marker-name-info").html(item.name);
    $("#marker-tag-info").html(item.tag);
    $("#marker-lng-info").html(parseFloat(item.lng).toFixed(2));
    $("#marker-lat-info").html(parseFloat(item.lat).toFixed(2));

    MapBox.popup = new mapboxgl.Popup({ closeButton: false })
        .setLngLat([item.lng, item.lat])
        .setHTML($("#marker-info").html())
        .addTo(MapBox.map);
}

/**
 * Remove popup on
 * 
 */
MapBox.removePopup = function () {
    MapBox.popup.remove();
}

/**
 * Init Functions
 * 
 * @since 1.0.0
 */


$(document).ready(function () {
    MapBox.initialize();
});