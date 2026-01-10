document.addEventListener("DOMContentLoaded", function () {

    const meterSelect  = document.getElementById("meterSelect");
    const prevReading  = document.getElementById("prevReading");
    const currReading  = document.getElementById("currReading");
    const unitsField   = document.getElementById("unitsField");
    const tariffSelect = document.getElementById("tariffSelect");

    if (!meterSelect) return;

    meterSelect.addEventListener("change", async function() {
        let id = this.value;
        if (!id) return;

        let util = this.selectedOptions[0].dataset.util;

        try {
            let prevRes = await fetch("reading_adv/get_last_reading.php?meter_id=" + id);
            let prevJson = await prevRes.json();
            prevReading.value = prevJson.previous ?? 0;

            let tRes = await fetch("reading_adv/get_tariffs.php?utility_id=" + util);
            let tariffs = await tRes.json();

            tariffSelect.innerHTML = '<option value="">-- Select Tariff --</option>';
            tariffs.forEach(t => {
                tariffSelect.innerHTML += `<option value="${t.id}">${t.tariff_name}</option>`;
            });

        } catch (e) {
            console.error(e);
            alert("Error loading meter info");
        }
    });

    if (currReading) {
        currReading.addEventListener("input", function () {
            let prev = parseFloat(prevReading.value || 0);
            let curr = parseFloat(currReading.value || 0);
            let diff = curr - prev;
            unitsField.value = diff >= 0 ? diff.toFixed(2) : "ERR";
        });
    }

});
