document.addEventListener("DOMContentLoaded", function () {

  // Delete from bill_adv/view.php
  const delBtn = document.getElementById("deleteBillBtn");
  if (delBtn) {
    delBtn.addEventListener("click", function () {
      const id = this.dataset.id;
      if (!id) return;
      if (confirm("Are you sure you want to delete this bill?")) {
        window.location = "delete.php?id=" + id;
      }
    });
  }

});
