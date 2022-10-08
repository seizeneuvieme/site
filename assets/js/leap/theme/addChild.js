
function validateChildFirstname(childFirstname) {
  if (childFirstname.length < 3) {
    $("#child-firstname-control-label-too-short").show();
    return false;
  } else if (childFirstname.length > 125) {
    $("#child-firstname-control-label-too-long").show();
    return false;
  } else {
    $("#child-firstname-control-label-too-short").hide();
    $("#child-firstname-control-label-too-long").hide();
    return true;
  }
}

function validateChildBirthdayDate(birthdayDate) {
  if (birthdayDate === "") {
    $("#child-birth-date-control-label").show();
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
 return childFirstname === true && childBirthdayDate === true;
}

$("input.add-child-form#child-firstname").on("change", function () {
  validateChildFirstname($(this).val());
});

$("input.add-child-form#child-birth-date").on("change", function () {
  validateChildBirthdayDate($(this).val());
});


$("#btn-add-child-form").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateAddChild() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateAddChild() === true) {
      $('.form').submit();
    }
  }
})

