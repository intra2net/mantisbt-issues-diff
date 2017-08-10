(function() {
    var baseAnchor = document.getElementById("view-diff-anchor");
    var diffPageUrl = baseAnchor.getAttribute('data-base-href');

    baseAnchor.removeAttribute('id');
    baseAnchor.removeAttribute('data-base-href');

    function appendAnchor(cellElement, bug_view_link) {
        var anchor = $.clone(baseAnchor);
        anchor.href = bug_view_link;
        anchor.style.display = 'inline';

        cellElement.appendChild(getSplitter());
        cellElement.appendChild(anchor);
    }

    function getSplitter() {
        var span = document.createElement("SPAN");
        span.innerHTML = " | "

        return span;
    }

    function getQueryString(url) {
        var questionMarkIndex = url.indexOf('?');

        if (questionMarkIndex === -1) return '';

        var newUrl = url.slice(questionMarkIndex + 1).replace("#r", "&last=");

        return '&' + newUrl;
    }

    function addDiffViewLink() {
        var issueHistory = $("#history tbody");
        var rows = issueHistory[0].getElementsByTagName("tr");

        for (var i = 0; i < rows.length; i++) {
            var changeCell = rows[i].lastElementChild;

            if (!changeCell) continue;

            var cellAnchor = changeCell.getElementsByTagName("a");

            if (!cellAnchor.length) continue;

            var revisionUrl = cellAnchor[0].getAttribute("href");

            if (revisionUrl.indexOf("bug_revision_view_page") === -1) {
                continue;
            }

            var queryString = getQueryString(revisionUrl);

            appendAnchor(changeCell, diffPageUrl + queryString);
        }
    }

    if (window.addEventListener){
        window.addEventListener('load', addDiffViewLink)
    } else{
        window.attachEvent('onload', addDiffViewLink)
    }
})();