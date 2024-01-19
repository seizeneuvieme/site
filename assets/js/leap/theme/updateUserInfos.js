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

function validateUserInfos() {
  firstname = validateFirstname($("input.update-user-form#firstname").val());
  return firstname === true;
}

$("input.update-user-form#firstname").on("change", function () {
  validateFirstname($(this).val());
});

$("#btn-update-user-form").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateUserInfos() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateUserInfos() === true) {
      $('.form').submit();
    }
  }
});