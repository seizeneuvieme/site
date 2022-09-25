function passEmailRgx(mail) {
    if (
        String(mail)
            .toLowerCase()
            .match(
                /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]| \\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?| \[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/
            )
    ) {
        return true;
    }
    return false;
}

function validateEmail(email) {
    if (passEmailRgx(email) === false) {
        if (email !== "") {
            $("#email-control-label").show();
        } else {
            $("#email-control-label").hide();
        }
        return false;
    } else {
        $("#email-control-label").hide();
        return true;
    }
}

function validateUpdateEmailForm() {
    email = validateEmail($("input.update-email#email").val());
    if (email === false) {
        $("#update-email-btn").attr("disabled", "disabled");
    } else {
        $("#update-email-btn").removeAttr("disabled");
    }
}

$("input.update-email#email").on("keyup", function () {
    validateUpdateEmailForm();
});
