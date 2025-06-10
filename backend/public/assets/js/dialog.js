function showDialog(message, type = "info", options = {}) {
  const dialog = document.getElementById("customDialog");
  const dialogTitle = document.getElementById("dialogTitle");
  const dialogMessage = document.getElementById("dialogMessage");
  const dialogOkBtn = document.getElementById("dialogOkBtn");

  dialog.classList.remove("dialog-success", "dialog-error", "dialog-info", "dialog-question");
  dialog.classList.add(`dialog-${type}`);

  let icon = "";
  if (type === "success") icon = '<i class="fas fa-check-circle"></i>';
  else if (type === "error") icon = '<i class="fas fa-times-circle"></i>';
  else if (type === "question") icon = '<i class="fas fa-question-circle"></i>';
  else icon = '<i class="fas fa-info-circle"></i>';

  dialogTitle.innerHTML = icon;

  let dialogNoBtn = document.getElementById("dialogNoBtn");
  if (dialogNoBtn) dialogNoBtn.remove();
  let dialogInput = document.getElementById("dialogInput");
  if (dialogInput) dialogInput.remove();

  if (options.input) {
    dialogMessage.innerHTML = `<div>${message}</div>`;
    dialogInput = document.createElement("input");
    dialogInput.id = "dialogInput";
    dialogInput.className = "custom-dialog-input";
    dialogInput.type = options.inputType || "text";
    dialogInput.placeholder = options.inputPlaceholder || "";
    dialogInput.value = options.inputValue || "";
    dialogMessage.appendChild(dialogInput);
    setTimeout(() => dialogInput.focus(), 100);
  } else {
    dialogMessage.textContent = message;
  }

  if (type === "question" || options.showCancel) {
    dialogOkBtn.textContent = options.okText || "Yes";
    dialogOkBtn.classList.add("custom-dialog-yes");
    dialogNoBtn = document.createElement("button");
    dialogNoBtn.id = "dialogNoBtn";
    dialogNoBtn.type = "button";
    dialogNoBtn.className = "custom-dialog-no";
    dialogNoBtn.textContent = options.cancelText || "No";
    dialogNoBtn.style.marginLeft = "1rem";
    dialogOkBtn.after(dialogNoBtn);

    dialogNoBtn.onclick = function () {
      dialog.setAttribute("hidden", "");
      dialogOkBtn.removeEventListener("click", okHandler);
      dialogNoBtn.removeEventListener("click", noHandler);
      document.removeEventListener("keydown", escListener);
      if (options.onCancel) options.onCancel();
    };

    var noHandler = dialogNoBtn.onclick;
  } else {
    dialogOkBtn.textContent = options.okText || "OK";
    dialogOkBtn.classList.remove("custom-dialog-yes");
  }

  function closeDialog() {
    dialog.setAttribute("hidden", "");
    dialogOkBtn.removeEventListener("click", okHandler);
    if (dialogNoBtn) dialogNoBtn.removeEventListener("click", noHandler);
    document.removeEventListener("keydown", escListener);
  }

  function okHandler() {
    let inputValue = dialogInput ? dialogInput.value : undefined;
    closeDialog();
    if (options.onOk) options.onOk(inputValue);
  }

  function escListener(e) {
    if (e.key === "Escape") closeDialog();
  }

  dialogOkBtn.addEventListener("click", okHandler);
  document.addEventListener("keydown", escListener);

  dialog.removeAttribute("hidden");
  if (dialogInput) dialogInput.focus();
  else dialogOkBtn.focus();
}