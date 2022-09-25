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
      (option) => option.value === $("input.update-user-form#city").val()
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
  firstname = validateFirstname($("input.update-user-form#firstname").val());
  city = validateCity($("input.update-user-form#city").val());
  if (firstname === false || city === false) {
    $("#btn-update-user-form").attr("disabled", "disabled");
  } else {
    $("#btn-update-user-form").removeAttr("disabled");
  }
}

$("input.update-user-form#firstname").on("keyup", function () {
  validateStep2();
});

$("input.update-user-form#city").on("input", function () {
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
    $("input.update-user-form#child-firstname").val()
  );
  childBirthdayDate = validateChildBirthdayDate(
    $("input.update-user-form#child-birth-date").val()
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

$("input.update-user-form#child-firstname").on("keyup", function () {
  validateStep3();
});

$("input.update-user-form#child-birth-date").on("input", function () {
  validateStep3();
});
