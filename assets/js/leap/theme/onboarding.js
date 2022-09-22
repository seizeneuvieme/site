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
    if (email !== "") {
      $("#email-control-label").show();
    } else {
      $("#email-control-label").hide();
    }
    return false;
  } else {
    $("#email-control-label").hide();
    return true;
  }
}

function validatePassword(password) {
  if (password !== "" && password.length < 8) {
    $("#password-control-label").show();
    if (
      password !== $("input.onboarding-form#confirm-password").val() &&
      $("input.onboarding-form#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.onboarding-form#confirm-password").val() &&
      $("input.onboarding-form#confirm-password").val() !== ""
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
      password !== $("input.onboarding-form#confirm-password").val() &&
      $("input.onboarding-form#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").show();
      return false;
    } else if (
      password === $("input.onboarding-form#confirm-password").val() &&
      $("input.onboarding-form#confirm-password").val() !== ""
    ) {
      $("#confirm-password-control-label").hide();
      return true;
    } else {
      $("#confirm-password-control-label").hide();
      return false;
    }
  }
}

function validateStep1() {
  email = validateEmail($("input.onboarding-form#email").val());
  password = validatePassword($("input.onboarding-form#password").val());
  if (email === false || password === false) {
    $("#btn-step-1").attr("disabled", "disabled");
  } else {
    $("#btn-step-1").removeAttr("disabled");
  }
}

$("input.onboarding-form#email").on("keyup", function () {
  validateStep1();
});

$("input.onboarding-form#password").on("keyup", function () {
  validateStep1();
});

$("input.onboarding-form#confirm-password").on("keyup", function () {
  validateStep1();
});

/**
 * STEP 2
 */
function validateFirstname(firstname) {
  if (firstname.length < 3 || firstname.length > 125) {
    if (firstname.length > 2) {
      $("#firstname-control-label").show();
    } else {
      $("#firstname-control-label").hide();
    }
    return false;
  } else {
    $("#firstname-control-label").hide();
    return true;
  }
}

function validateCity(city) {
  if ($("#city-datalist").length > 0) {
    city = Array.from($("#city-datalist")[0].options).filter(
      (option) => option.value === $("input.onboarding-form#city").val()
    );
    if (city.length > 0) {
      $("#city-details").val(city[0].label);
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
}

function searchCity(keywords) {
  alreadyExist = Array.from($("#city-datalist")[0].options).filter(
    (option) => option.value === keywords
  );
  if (keywords.length === 0 || alreadyExist.length > 0) {
    validateStep2();
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
        validateStep2();
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
      validateStep2();
    }
  );
}

function validateStep2() {
  firstname = validateFirstname($("input.onboarding-form#firstname").val());
  city = validateCity($("input.onboarding-form#city").val());
  if (firstname === false || city === false) {
    $("#btn-step-2").attr("disabled", "disabled");
  } else {
    $("#btn-step-2").removeAttr("disabled");
  }
}

$("input.onboarding-form#firstname").on("keyup", function () {
  validateStep2();
});

$("input.onboarding-form#city").on("input", function () {
  searchCity(this.value);
});

/**
 * STEP 3
 */

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

function validateStep3() {
  childFirstname = validateChildFirstname(
    $("input.onboarding-form#child-firstname").val()
  );
  childBirthdayDate = validateChildBirthdayDate(
    $("input.onboarding-form#child-birth-date").val()
  );
  if (
    childFirstname === false ||
    childBirthdayDate === false
  ) {
    $("#btn-step-3").attr("disabled", "disabled");
  } else {
    $("#btn-step-3").removeAttr("disabled");
  }
}

$("input.onboarding-form#child-firstname").on("keyup", function () {
  validateStep3();
});

$("input.onboarding-form#child-birth-date").on("input", function () {
  validateStep3();
});
