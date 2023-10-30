function checkNolejUpdates(documentUrl) {
    setInterval(() => {
        il.Util.sendAjaxGetRequestToUrl(
            documentUrl,
            {},
            {},
            function (o) {
                if (o.responseText !== undefined && o.responseText == "reload") {
                    location.reload();
                }
            }
        );
    }, 2000);
}
