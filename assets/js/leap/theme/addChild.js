function validateChildFirstname(childFirstname) {
  if (childFirstname.length < 3 || childFirstname.length > 125) {
    if (childFirstname.length > 2) {
      $("#child-firstname-control-label").show();
    } else {
      $("#child-firstname-control-label").hide();
    }
    return false;
  } else {
    $("#child-firstname-control-label").hide();
    return true;
  }
}

function validateChildBirthdayDate(birthdayDate) {
  if (birthdayDate === "") {
    $("#child-birth-date-control-label").hide();
    return false;
  }
  if (Date.now() < new Date(birthdayDate)) {
    $("#child-birth-date-control-label").show();
    return false;
  }
  ageDifMs = Date.now() - new Date(birthdayDate).getTime();
  ageDate = new Date(ageDifMs);
  age = Math.abs(ageDate.getUTCFullYear() - 1970);

  if (age < 3 || age > 12) {
    $("#child-birth-date-control-label").show();
    return false;
  } else {
    $("#child-birth-date-control-label").hide();
    return true;
  }
}

function validateAddChild() {
  childFirstname = validateChildFirstname(
    $("input.add-child-form#child-firstname").val()
  );
  childBirthdayDate = validateChildBirthdayDate(
    $("input.add-child-form#child-birth-date").val()
  );
  if (
    childFirstname === false ||
    childBirthdayDate === false
  ) {
    $("#btn-add-child-form").attr("disabled", "disabled");
  } else {
    $("#btn-add-child-form").removeAttr("disabled");
  }
}

$("input.add-child-form#child-firstname").on("keyup", function () {
  validateAddChild();
});

$("input.add-child-form#child-birth-date").on("input", function () {
  validateAddChild();
});
