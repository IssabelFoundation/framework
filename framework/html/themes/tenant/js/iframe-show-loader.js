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

iframeURLChange(document.getElementById("myframe"), function (newURL) {
    console.log("show loader");
    $('#neo-contentbox').LoadingOverlay('show', {
      image       : "/themes/tenant/images/issabel_logo_pattern.png",
    });
});

$('#myframe').get(0).onload = function() {
  console.log("hide loader");
  $('#neo-contentbox').LoadingOverlay('hide');
};

