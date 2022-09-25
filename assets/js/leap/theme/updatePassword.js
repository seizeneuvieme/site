function validatePassword(password) {
  if (password !== "" && password.length < 8) {
    $("#password-control-label").show();
    if (
      password !== $("input.update-password#confirm-password").val() &&
      $("input.update-password#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.update-password#confirm-password").val() &&
      $("input.update-password#confirm-password").val() !== ""
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
      password !== $("input.update-password#confirm-password").val() &&
      $("input.update-password#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.update-password#confirm-password").val() &&
      $("input.update-password#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    } else {
      $("#confirm-password-control-label").hide();
      return false;
    }
  }
}

function validateUpdatePassworForm() {
  password = validatePassword($("input.update-password#password").val());
  if (password === false) {
    $("#btn-update-password-form").attr("disabled", "disabled");
  } else {
    $("#btn-update-password-form").removeAttr("disabled");
  }
}

$("input.update-password#password").on("keyup", function () {
  validateUpdatePassworForm();
});

$("input.update-password#confirm-password").on("keyup", function () {
  validateUpdatePassworForm();
});