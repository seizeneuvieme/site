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
  password = validatePassword($("input.sign-up-form#password").val());
  confirmPassword = validateConfirmPassword($("input.sign-up-form#confirm-password").val());
  return email === true && password === true && confirmPassword === true;
}

$("input.sign-up-form#email").on('change', function() {
  validateEmail($(this).val());
})

$("input.sign-up-form#password").on('change', function() {
  validatePassword($(this).val());
})

$("input.sign-up-form#confirm-password").on('change', function() {
  validateConfirmPassword($(this).val());
})

$("#btn-step-1").on("click", function (e) {
    e.stopImmediatePropagation();
  if (validateStep1() === true) {
    $('.wizard').smartWizard('next');
  }
});


/**
 * STEP 2
 */
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

function validateCity(city) {
  if ($("#city-datalist").length > 0) {
    $('#city-control-label').hide();
    city = Array.from($("#city-datalist")[0].options).filter(
      (option) => option.value === $("input.sign-up-form#city").val()
    );
    if (city.length > 0) {
      $("#city-details").val(city[0].label);
      return true;
    } else {
      $('#city-control-label').show();
      return false;
    }
  } else {
    $('#city-control-label').show();
    return false;
  }
}

function searchCity(keywords) {
  alreadyExist = Array.from($("#city-datalist")[0].options).filter(
    (option) => option.value === keywords
  );
  if (keywords.length === 0 || alreadyExist.length > 0) {
    validateCity($("input.sign-up-form#city").val());
    return;
  }
  $.get(
    "https://api-adresse.data.gouv.fr/search/?q=" +
      keywords +
      "&type=municipality&autocomplete=1",
    function (data) {
      if (
        data.features.length > 0 &&
        data.features[0].properties.city === keywords
      ) {
        validateCity($("input.sign-up-form#city").val());
        return;
      }
      $("#city-datalist").empty();
      data.features.forEach((feature) => {
        $("#city-datalist").append(
          "<option value=" +
            feature.properties.city +
            ">" +
            feature.properties.context +
            "</option>"
        );
      });
      validateCity($("input.sign-up-form#city").val());
    }
  );
}

function validateStep2() {
  firstname = validateFirstname($("input.sign-up-form#firstname").val());
  city = validateCity($("input.sign-up-form#city").val());
  return firstname === true && city === true
}

$("input.sign-up-form#firstname").on("change", function () {
  validateFirstname($(this).val());
});

$("input.sign-up-form#city").on("input", function () {
  searchCity(this.value);
});

$("input.sign-up-form#city").on("change", function () {
  validateCity($(this).val());
});

$("#btn-step-2").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateStep2() === true) {
    $('.wizard').smartWizard('next');
  }
});

/**
 * STEP 3
 */

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

function validateStep3() {
  childFirstname = validateChildFirstname(
    $("input.sign-up-form#child-firstname").val()
  );
  childBirthdayDate = validateChildBirthdayDate(
    $("input.sign-up-form#child-birth-date").val()
  );
  return childFirstname === true && childBirthdayDate === true;
}

$("input.sign-up-form#child-firstname").on("keyup", function () {
  validateChildFirstname($(this).val());
});

$("input.sign-up-form#child-birth-date").on("input", function () {
  validateChildBirthdayDate($(this).val());
});

$("#btn-step-3").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateStep3() === true) {
    $('.wizard').smartWizard('next');
  }
});

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
    if ($(this).hasClass('step-3')) {
      if (validateStep3() === true) {
        $('.wizard').smartWizard('next');
      }
      return;
    }
    $('#sign-up-form').submit();
  }
})

