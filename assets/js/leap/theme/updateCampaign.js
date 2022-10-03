function validateSendingDate(sendingDate) {
  if (sendingDate === "") {
    $("#campaign-sending-date-control-label").show();
    return false;
  }
  if (new Date().setHours(0,0,0,0) > new Date(sendingDate)) {
    $("#campaign-sending-date-control-label").show();
    return false;
  }
  $("#campaign-sending-date-control-label").hide();
  return true;
}

function validateUpdateCampaign() {
  sendingDate = validateSendingDate($("input.update-campaign-form#campaign-sending-date").val());
  return sendingDate === true;
}

$("input.update-campaign-form#campaign-sending-date").on("change", function () {
  validateSendingDate($(this).val());
});

$("#btn-update-campaign-form").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateUpdateCampaign() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateUpdateCampaign() === true) {
      $('.form').submit();
    }
  }
})

