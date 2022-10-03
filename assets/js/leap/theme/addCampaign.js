
function validateTemplateId(templateId) {
  if (templateId < 0 || templateId == "") {
    $("#campaign-template-id-control-label").show();
    return false;
  } else {
    $("#campaign-template-id-control-label").hide();
    return true;
  }
}

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

function validateAddCampaign() {
  templateId = validateTemplateId($("input.add-campaign-form#template-id").val());
  sendingDate = validateSendingDate($("input.add-campaign-form#campaign-sending-date").val());
  return templateId === true && sendingDate === true;
}

$("input.add-campaign-form#template-id").on("change", function () {
  validateTemplateId($(this).val());
});

$("input.add-campaign-form#campaign-sending-date").on("change", function () {
  validateSendingDate($(this).val());
});


$("#btn-add-campaign-form").on("click", function (e) {
  e.stopImmediatePropagation();
  if (validateAddCampaign() === true) {
    $('.form').submit();
  }
});

$('input').keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    if (validateAddCampaign() === true) {
      $('.form').submit();
    }
  }
})

