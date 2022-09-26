if (false === document.cookie.includes("cookies=true")) {
    $('#cookies-modal').modal('show');
}

$('#cookies-modal-close').on('click', function() {
    document.cookie = "cookies=true";
});