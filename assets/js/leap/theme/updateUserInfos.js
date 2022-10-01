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
        (option) => option.value === $("input.update-user-form#city").val()
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
    validateCity($("input.update-user-form#city").val());
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
          validateCity($("input.update-user-form#city").val());
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
        validateCity($("input.update-user-form#city").val());
      }
  );
}

function validateUserInfos() {
  firstname = validateFirstname($("input.update-user-form#firstname").val());
  city = validateCity($("input.update-user-form#city").val());
  return firstname === true && city === true;
}

$("input.update-user-form#firstname").on("change", function () {
  validateFirstname($(this).val());
});

$("input.update-user-form#city").on("keyup", function () {
  searchCity(this.value);
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