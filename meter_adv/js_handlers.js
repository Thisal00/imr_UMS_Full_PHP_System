document.addEventListener("DOMContentLoaded", function () {

  // ---- EDIT ----
  document.querySelectorAll(".mEditBtn").forEach(btn => {
    btn.addEventListener("click", async function () {

      let id = this.dataset.id;

      try {
        let res = await fetch("meter_adv/get.php?id=" + id);
        let json = await res.json();

        if (!json.meter) {
          alert("Error loading meter.");
          return;
        }

        // fill dropdowns
        let custSel = document.getElementById("m_edit_customer");
        let utilSel = document.getElementById("m_edit_utility");
        custSel.innerHTML = "";
        utilSel.innerHTML = "";

        json.customers.forEach(c => {
          let opt = document.createElement("option");
          opt.value = c.id;
          opt.textContent = c.full_name;
          if (c.id == json.meter.customer_id) opt.selected = true;
          custSel.appendChild(opt);
        });

        json.utilities.forEach(u => {
          let opt = document.createElement("option");
          opt.value = u.id;
          opt.textContent = u.name;
          if (u.id == json.meter.utility_id) opt.selected = true;
          utilSel.appendChild(opt);
        });

        // set fields
        document.getElementById("m_edit_id").value       = json.meter.id;
        document.getElementById("m_edit_number").value   = json.meter.meter_number;
        document.getElementById("m_edit_install").value  = json.meter.install_date || "";
        document.getElementById("m_edit_status").value   = json.meter.status || "Active";

        let modal = new bootstrap.Modal(document.getElementById("meterEditModal"));
        modal.show();

      } catch (e) {
        console.error(e);
        alert("Server error");
      }

    });
  });

  // ---- SAVE EDIT ----
  let mForm = document.getElementById("meterEditForm");
  if (mForm) {
    mForm.addEventListener("submit", async function(e) {
      e.preventDefault();

      let fd = new FormData(this);

      try {
        let res = await fetch("meter_adv/update.php", {
          method: "POST",
          body: fd
        });
        let json = await res.json();
        alert(json.message);
        if (json.status === "success") {
          location.reload();
        }
      } catch (e) {
        console.error(e);
        alert("Server error");
      }
    });
  }

  // ---- DELETE ----
  document.querySelectorAll(".mDeleteBtn").forEach(btn => {
    btn.addEventListener("click", async function () {
      if (!confirm("Delete this meter?")) return;

      let id = this.dataset.id;

      try {
        let res = await fetch("meter_adv/remove.php?id=" + id);
        let json = await res.json();
        alert(json.message);
        if (json.status === "success") {
          let row = document.getElementById("mrow_" + id);
          if (row) row.remove();
        }
      } catch (e) {
        console.error(e);
        alert("Server error");
      }
    });
  });

});
