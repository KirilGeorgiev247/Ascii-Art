document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".hero-buttons .btn").forEach((btn) => {
    btn.addEventListener("mouseenter", () => {
      btn.classList.add("btn-glow");
    });
    btn.addEventListener("mouseleave", () => {
      btn.classList.remove("btn-glow");
    });
  });
});
