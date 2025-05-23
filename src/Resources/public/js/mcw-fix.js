document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.multicolumnwizard .op-delete').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            const row = event.target.closest('tr');
            if (!row) return;

            // Leere alle Input-Felder
            row.querySelectorAll('input, textarea').forEach(el => {
                el.value = '';
            });

            // Leere alle Select-Felder und aktualisiere Chosen, falls aktiv
            row.querySelectorAll('select').forEach(el => {
                el.value = '';

                // Wenn Chosen aktiv ist, aktualisiere die Anzeige
                if (el.classList.contains('tl_chosen') || el.classList.contains('chosen')) {
                    jQuery(el).trigger('chosen:updated');
                }
            });
        });
    });
});
