function validatePassword(password) {
  if (password.length < 8) {
    $("#password-control-label").show();
    if (
      password !== $("input.reset#confirm-password").val()
    ) {
      if($("input.reset#confirm-password").val() !== "") {
        $("#confirm-password-control-label").show();
      }
      return false;
    } else if (
      password === $("input.reset#confirm-password").val()
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    }
  } else {
    $("#password-control-label").hide();
    if (
      password !== $("input.reset#confirm-password").val()
    ) {
      if($("input.reset#confirm-password").val() !== "") {
        $("#confirm-password-control-label").show();
      }
      return false;
    } else if (
      password === $("input.reset#confirm-password").val()
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    }
  }
}

function validateResetForm() {
  return validatePassword($("input.reset#password").val());
}

$("input.reset#password").on("change", function () {
  validatePassword($(this).val());
});

$("input.reset#confirm-password").on("change", function () {
  validatePassword($(this).val());
});

$("#btn-reset-form").on('click', function() {
  if (validateResetForm() === true) {
    $('.form').submit();
  }
});
