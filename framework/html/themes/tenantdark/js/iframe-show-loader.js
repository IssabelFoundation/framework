// Detects if frame source changes to display loader
// and hides the loader on frame onload
function iframeURLChange(iframe, callback) {
    var unloadHandler = function () {
        // Timeout needed because the URL changes immediately after
        // the `unload` event is dispatched.
        setTimeout(function () {
            callback(iframe.contentWindow.location.href);
        }, 0);
    };

    function attachUnload() {
        // Remove the unloadHandler in case it was already attached.
        // Otherwise, the change will be dispatched twice.
        iframe.contentWindow.removeEventListener("unload", unloadHandler);
        iframe.contentWindow.addEventListener("unload", unloadHandler);
    }

    iframe.addEventListener("load", attachUnload);
    attachUnload();
}

if($('#myframe').length>0) {

    iframeURLChange(document.getElementById("myframe"), function (newURL) {
        $('#neo-contentbox').LoadingOverlay('show', {
            image: "/themes/tenant/images/issabel_logo_pattern.png",
        });
    });

    $('#myframe').get(0).onload = function() {
      calcHeight();
      $('#neo-contentbox').LoadingOverlay('hide');
    };

}
