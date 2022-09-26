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
    validateUserInfos();
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
        validateUserInfos();
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
      validateUserInfos();
    }
  );
}

function validateUserInfos() {
  firstname = validateFirstname($("input.update-user-form#firstname").val());
  city = validateCity($("input.update-user-form#city").val());
  if (firstname === false || city === false) {
    $("#btn-update-user-form").attr("disabled", "disabled");
  } else {
    $("#btn-update-user-form").removeAttr("disabled");
  }
}

$("input.update-user-form#firstname").on("keyup", function () {
  validateUserInfos();
});

$("input.update-user-form#city").on("input", function () {
  searchCity(this.value);
});
