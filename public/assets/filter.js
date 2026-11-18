(function () {
  const input = document.getElementById("product-filter");
  const select = document.getElementById("product_id");
  if (!input || !select) return;
  const reason = document.querySelector('select[name="reason"]');
  function syncReasons() {
    if (!reason) return;
    const typeInput = document.querySelector('input[name="type"]:checked');
    const type = typeInput ? typeInput.value : "in";
    let keep = false;
    Array.prototype.forEach.call(reason.options, function (option) {
      if (!option.dataset.kind) return;
      const match = option.dataset.kind === type;
      option.hidden = !match;
      option.disabled = !match;
      if (match && option.value === reason.value) keep = true;
    });
    if (!keep) reason.value = "";
  }
  document.querySelectorAll('input[name="type"]').forEach(function (radio) {
    radio.addEventListener("change", syncReasons);
  });
  syncReasons();

  input.addEventListener("input", function () {
    const query = input.value.trim().toLowerCase();
    let first = "";
    Array.prototype.forEach.call(select.options, function (option) {
      if (option.value === "") return;
      const match = query === "" || option.text.toLowerCase().includes(query);
      option.hidden = !match;
      if (match && first === "") first = option.value;
    });
    if (query !== "" && first !== "") select.value = first;
  });
})();
