(function () {
  const button = document.getElementById("print-ticket");
  if (button) {
    button.addEventListener("click", function () {
      window.print();
    });
  }
  if (document.body.dataset.autoprint === "1") {
    window.addEventListener("load", function () {
      window.print();
    });
  }
})();
