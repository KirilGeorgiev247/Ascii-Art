function showDialog(message, type = "info", options = {}) {
  const dialog = document.getElementById("customDialog");
  const dialogTitle = document.getElementById("dialogTitle");
  const dialogMessage = document.getElementById("dialogMessage");
  const dialogOkBtn = document.getElementById("dialogOkBtn");

  // Remove previous type classes
  dialog.classList.remove("dialog-success", "dialog-error", "dialog-info", "dialog-question");

  // Add new type class
  dialog.classList.add(`dialog-${type}`);

  // Optionally show an icon
  let icon = "";
  if (type === "success") icon = '<i class="fas fa-check-circle"></i>';
  else if (type === "error") icon = '<i class="fas fa-times-circle"></i>';
  else if (type === "question") icon = '<i class="fas fa-question-circle"></i>';
  else icon = '<i class="fas fa-info-circle"></i>';

  dialogTitle.innerHTML = icon;
  dialogMessage.textContent = message;

  // Remove any previous extra buttons
  let dialogNoBtn = document.getElementById("dialogNoBtn");
  if (dialogNoBtn) dialogNoBtn.remove();

  // If it's a question, add a No button
  if (type === "question") {
    dialogOkBtn.textContent = "Yes";
    dialogOkBtn.classList.add("custom-dialog-yes");
    dialogNoBtn = document.createElement("button");
    dialogNoBtn.id = "dialogNoBtn";
    dialogNoBtn.type = "button";
    dialogNoBtn.className = "custom-dialog-no";
    dialogNoBtn.textContent = "No";
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
    dialogOkBtn.textContent = "OK";
    dialogOkBtn.classList.remove("custom-dialog-yes");
  }

  function closeDialog() {
    dialog.setAttribute("hidden", "");
    dialogOkBtn.removeEventListener("click", okHandler);
    if (dialogNoBtn) dialogNoBtn.removeEventListener("click", noHandler);
    document.removeEventListener("keydown", escListener);
  }

  function okHandler() {
    closeDialog();
    if (options.onOk) options.onOk();
  }

  function escListener(e) {
    if (e.key === "Escape") closeDialog();
  }

  dialogOkBtn.addEventListener("click", okHandler);
  document.addEventListener("keydown", escListener);

  dialog.removeAttribute("hidden");
  dialogOkBtn.focus();
}