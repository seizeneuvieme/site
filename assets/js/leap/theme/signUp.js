/**
 * STEP 1
 */
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
      $("#email-control-label").show();
    return false;
  } else {
    $("#email-control-label").hide();
    return true;
  }
}

function validateFirstname(firstname) {
  if (firstname.length < 3) {
    $("#firstname-control-label-too-short").show();
    return false;
  } else if (firstname.length > 125) {
    $("#firstname-control-label-too-long").show();
    return false;
  } else {
    $("#firstname-control-label-too-short").hide();
    $("#firstname-control-label-too-long").hide();
    return true;
  }
}

function validatePassword(password) {
  if (password.length < 8) {
    $("#password-control-label").show();
    return false;
  } else {
    $("#password-control-label").hide();
    return true;
  }
}

function validateConfirmPassword(password) {
  if (
      password !== $("input.sign-up-form#password").val()
    ) {
      $("#confirm-password-control-label").show();
    return false;
  } else {
    $("#confirm-password-control-label").hide();
    return true;
  }
}

function validateStep1() {
  email = validateEmail($("input.sign-up-form#email").val());
  firstname = validateFirstname($("input.sign-up-form#firstname").val());
  password = validatePassword($("input.sign-up-form#password").val());
  confirmPassword = validateConfirmPassword($("input.sign-up-form#confirm-password").val());
  return email === true && password === true && confirmPassword === true;
}

$("input.sign-up-form#email").on('change', function() {
  validateEmail($(this).val());
});

$("input.sign-up-form#firstname").on("change", function () {
  validateFirstname($(this).val());
});

$("input.sign-up-form#password").on('change', function() {
  validatePassword($(this).val());
});

$("input.sign-up-form#confirm-password").on('change', function() {
  validateConfirmPassword($(this).val());
});

$("#btn-step-1").on("click", function (e) {
    e.stopImmediatePropagation();
  if (validateStep1() === true) {
    $('.wizard').smartWizard('next');
  }
});


/**
 * SUBMIT FORM
 */

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if ($(this).hasClass('step-1')) {
      if (validateStep1() === true) {
        $('.wizard').smartWizard('next');
      }
      return;
    }
    if ($(this).hasClass('step-2')) {
      if (validateStep2() === true) {
        $('.wizard').smartWizard('next');
      }
      return;
    }
    $('#sign-up-form').submit();
  }
})

