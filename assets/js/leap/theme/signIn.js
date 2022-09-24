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

function validatePassword(password) {
    if (password !== "" && password.length < 8) {
        $("#password-control-label").show();
        return false;
    } else if (password.length === 0) {
        $("#password-control-label").hide();
        return false
    } else {
        $("#password-control-label").hide();
        return true;
    }
}

function validateSignForm() {
    email = validateEmail($("input.sign-in#email").val());
    password = validatePassword($("input.sign-in#password").val());
    if (email === false || password === false) {
        $("#sign-in-btn").attr("disabled", "disabled");
    } else {
        $("#sign-in-btn").removeAttr("disabled");
    }
}

$("input.sign-in#email").on("keyup", function () {
    validateSignForm();
});

$("input.sign-in#password").on("keyup", function () {
    validateSignForm();
});
