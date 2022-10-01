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
      password !== $("input.reset#password").val()
  ) {
    $("#confirm-password-control-label").show();
    return false;
  } else {
    $("#confirm-password-control-label").hide();
    return true;
  }
}

function validateResetForm() {
  password = validatePassword($("input.reset#password").val());
  confirmPassword = validateConfirmPassword($("input.reset#confirm-password").val());
  return password === true && confirmPassword === true;
}

$("input.reset#password").on("change", function () {
  validatePassword($(this).val());
});

$("input.reset#confirm-password").on("change", function () {
  validateConfirmPassword($(this).val());
});

$("#btn-reset-form").on('click', function() {
  if (validateResetForm() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateResetForm() === true) {
      $('.form').submit();
    }
  }
})
