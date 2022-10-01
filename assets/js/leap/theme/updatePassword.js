
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
      password !== $("input.update-password#password").val()
  ) {
    $("#confirm-password-control-label").show();
    return false;
  } else {
    $("#confirm-password-control-label").hide();
    return true;
  }
}
function validateUpdatePassworForm() {
  password = validatePassword($("input.update-password#password").val());
  confirmPassword = validateConfirmPassword($("input.update-password#confirm-password").val());
  return password === true && confirmPassword === true;
}

$("input.update-password#password").on("change", function () {
  validatePassword($(this).val());
});

$("input.update-password#confirm-password").on("change", function () {
  validateConfirmPassword($(this).val());
});

$("#btn-update-password-form").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateUpdatePassworForm() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateUpdatePassworForm() === true) {
      $('.form').submit();
    }
  }
});