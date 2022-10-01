$(document).keypress(function(e) {
  if (e.keyCode === 13) {
    e.preventDefault();
    $('.form').submit();
  }
});