function validatePassword(password) {
  if (password !== "" && password.length < 8) {
    $("#password-control-label").show();
    if (
      password !== $("input.reset#confirm-password").val() &&
      $("input.reset#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.reset#confirm-password").val() &&
      $("input.reset#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    } else {
      $("#confirm-password-control-label").hide();
      return false;
    }
  } else {
    $("#password-control-label").hide();
    if (
      password !== $("input.reset#confirm-password").val() &&
      $("input.reset#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.reset#confirm-password").val() &&
      $("input.reset#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    } else {
      $("#confirm-password-control-label").hide();
      return false;
    }
  }
}

function validateResetForm() {
  password = validatePassword($("input.reset#password").val());
  if (password === false) {
    $("#btn-reset-form").attr("disabled", "disabled");
  } else {
    $("#btn-reset-form").removeAttr("disabled");
  }
}

$("input.reset#password").on("keyup", function () {
  validateResetForm();
});

$("input.reset#confirm-password").on("keyup", function () {
  validateResetForm();
});