$(document).ready(function() {
    $("#button-lucky").click(function() {
        getLocation();
    });
});

function countdown() {
    $(".arrival").each(function(index) {
        var time = parseInt($(this).text(), 10);

        if (time <= 1) {

            $(this).closest(".card").addClass('hidden');
            $(this).closest(".card").stop().fadeOut(700,
                    function() {

                        $(this).closest(".card").hide();
                    });
            $(this).text("Departed");
        }
        else {
            time = time - 1;

            $(this).text(time);
        }
    });
}

// Then do it once a minute.
self.setInterval(function() {

    countdown();
}, 60000);


/** GPS CODE **/

var x = document.getElementById("button-lucky");
function getLocation()
{
    $("#button-lucky").val("Locating your position...");
    if (navigator.geolocation)
    {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    }
    else {
        x.innerHTML = "Geolocation is not supported by this browser.";
    }
}

function showPosition(position)
{
    window.location.href = '?lat=' +
            position.coords.latitude +
            "&long=" +
            position.coords.longitude;
}

function showError(error)
{
    $("#button-lucky").attr(disabled);
    switch (error.code)
    {
        case error.PERMISSION_DENIED:
            $("#button-lucky").val("User denied the request for Geolocation.");
            break;
        case error.POSITION_UNAVAILABLE:
            $("#button-lucky").val("Location information is unavailable.");
            break;
        case error.TIMEOUT:
            $("#button-lucky").val("The request to get user location timed out.");
            break;
        case error.UNKNOWN_ERROR:
            $("#button-lucky").val("An unknown error occurred.");
            break;
    }
}
